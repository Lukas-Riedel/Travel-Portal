import ast
import sys
from pathlib import Path

IGNORE_MARKER = "# style: ignore"

# Callables whose arguments cannot (or should not) be named: positional-only
# builtins and standard library functions, and the logger.
EXEMPT_NAMES = {
    "isinstance",
    "issubclass",
    "getattr",
    "setattr",
    "hasattr",
    "zip",
    "enumerate",
    "range",
    "max",
    "min",
    "round",
    "sorted",
    "next",
    "super",
    "divmod",
    "pow",
    "slice",
    "map",
    "filter",
    "getenv",
    "wraps",
    "ContextVar",  # the name is a positional-only parameter
}
EXEMPT_ATTRIBUTES = {
    # dict, list, str and set methods
    "get",
    "pop",
    "setdefault",
    "join",
    "replace",
    "split",
    "rsplit",
    "startswith",
    "endswith",
    "strftime",
    "encode",
    "insert",
    "update",
    # logging
    "debug",
    "info",
    "warning",
    "error",
    "exception",
    "critical",
    # standard library
    "getenv",
    "fromtimestamp",
    "strptime",
    "set",
    "reset",
    "verify",
    "import_module",
    "getmembers",
    "ContextVar",  # the name is a positional-only parameter
}


def _call_name(node: ast.Call) -> str | None:
    if isinstance(node.func, ast.Name):
        return node.func.id
    if isinstance(node.func, ast.Attribute):
        return node.func.attr

    return None


def _is_exempt(node: ast.Call) -> bool:
    if isinstance(node.func, ast.Name):
        return node.func.id in EXEMPT_NAMES

    if isinstance(node.func, ast.Attribute):
        return node.func.attr in EXEMPT_ATTRIBUTES

    return False


class StyleChecker(ast.NodeVisitor):
    def __init__(self, path: Path, lines: list[str]) -> None:
        self.path: Path = path
        self.lines: list[str] = lines
        self.findings: list[str] = []
        self.function_depth: int = 0
        self.in_init: bool = False
        self.annotated_attributes: set[str] = set()

    def _report(self, node: ast.AST, message: str) -> None:
        line = node.lineno  # type: ignore[attr-defined]
        if IGNORE_MARKER in self.lines[line - 1]:
            return

        self.findings.append(f"{self.path}:{line}: {message}")

    def visit_FunctionDef(self, node: ast.FunctionDef) -> None:
        self._visit_function(node=node)

    def visit_AsyncFunctionDef(self, node: ast.AsyncFunctionDef) -> None:
        self._visit_function(node=node)

    def _visit_function(self, node: ast.FunctionDef | ast.AsyncFunctionDef) -> None:
        previous_in_init = self.in_init
        previous_attributes = self.annotated_attributes
        self.in_init = node.name == "__init__"
        self.annotated_attributes = set()
        self.function_depth += 1

        self.generic_visit(node)

        self.function_depth -= 1
        self.in_init = previous_in_init
        self.annotated_attributes = previous_attributes

    def visit_AnnAssign(self, node: ast.AnnAssign) -> None:
        if isinstance(node.target, ast.Name) and self.function_depth > 0:
            self._report(
                node=node,
                message=f"B: local variable '{node.target.id}' must not have a type annotation",
            )
        elif self.in_init and self._is_self_attribute(node=node.target):
            self.annotated_attributes.add(node.target.attr)

        self.generic_visit(node)

    def visit_Assign(self, node: ast.Assign) -> None:
        if self.in_init:
            for target in node.targets:
                if (
                    self._is_self_attribute(node=target)
                    and target.attr not in self.annotated_attributes
                ):
                    self._report(
                        node=node,
                        message=f"C: attribute 'self.{target.attr}' must have a type annotation",
                    )

        self.generic_visit(node)

    def visit_Call(self, node: ast.Call) -> None:
        has_positional = any(not isinstance(a, ast.Starred) for a in node.args)
        argument_count = len(node.args) + len(node.keywords)
        if argument_count >= 2 and has_positional and not _is_exempt(node=node):
            self._report(
                node=node,
                message=f"A: call to '{_call_name(node=node)}' must use named arguments",
            )

        self.generic_visit(node)

    @staticmethod
    def _is_self_attribute(node: ast.AST) -> bool:
        return (
            isinstance(node, ast.Attribute)
            and isinstance(node.value, ast.Name)
            and node.value.id == "self"
        )


def collect_files(paths: list[str]) -> list[Path]:
    files = []
    for raw_path in paths:
        path = Path(raw_path)
        if path.is_dir():
            files.extend(
                p for p in sorted(path.rglob("*.py")) if ".venv" not in p.parts
            )
        elif path.suffix == ".py":
            files.append(path)

    return files


def main() -> int:
    findings = []
    for path in collect_files(paths=sys.argv[1:]):
        source = path.read_text(encoding="utf-8")
        checker = StyleChecker(path=path, lines=source.splitlines())
        checker.visit(ast.parse(source=source, filename=str(path)))
        findings.extend(checker.findings)

    for finding in findings:
        print(finding)  # noqa: T201

    return 1 if findings else 0


if __name__ == "__main__":
    sys.exit(main())
