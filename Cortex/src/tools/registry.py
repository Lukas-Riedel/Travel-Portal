import importlib
import inspect
import pkgutil
from collections.abc import Callable
from pathlib import Path
from typing import Any, Final

from src.client.core_client import CoreClient
from src.client.piston_client import PistonClient
from src.core.logger import logger
from src.tools.base_tools import BaseCoreTools, BasePistonTools, BaseTools

SKILLS_DIR: Final[Path] = Path(__file__).resolve().parent.parent.parent / "skills"
MASTER_SKILL_FILENAME: Final[str] = "SKILL.md"
SKILL_FILE_GLOB: Final[str] = "*.md"


class SkillRegistry:
    def __init__(self, core_client: CoreClient, piston_client: PistonClient) -> None:
        self.core_client: CoreClient = core_client
        self.piston_client: PistonClient = piston_client
        self.master_skill: str = ""
        self.tools: dict[str, tuple[Callable[..., Any], str | None]] = {}
        self.skills: dict[str, str] = {}
        self.skill_descriptions: dict[str, str] = {}
        self._auto_discover_and_register()

    def _auto_discover_and_register(self) -> None:
        self._discover_tools()
        self._discover_skills()

    def _discover_tools(self) -> None:
        import src.tools as tools_package

        for module_info in pkgutil.iter_modules(tools_package.__path__):
            module_name = f"{tools_package.__name__}.{module_info.name}"
            module = importlib.import_module(module_name)

            for _, cls in inspect.getmembers(module, inspect.isclass):
                if (
                    cls.__module__ == module_name
                    and issubclass(cls, BaseTools)
                    and not inspect.isabstract(cls)
                    and cls is not BaseTools
                    and cls is not BaseCoreTools
                ):
                    try:
                        if issubclass(cls, BaseCoreTools):
                            instance = cls(core_client=self.core_client)
                        elif issubclass(cls, BasePistonTools):
                            instance = cls(piston_client=self.piston_client)
                        else:
                            instance = cls()

                        self._register_module_tools(
                            tools=instance._get_tools(),
                            supported_skill=instance._get_supported_skill(),
                            description=instance._get_description(),
                        )
                    except Exception:
                        logger.exception(
                            f"Failed to initialize and register tools from class '{cls.__name__}'."
                        )

        logger.info(f"Loaded {len(self.tools)} tool(s).")

    def _discover_skills(self) -> None:
        main_skill_file = SKILLS_DIR / MASTER_SKILL_FILENAME
        try:
            self.master_skill = main_skill_file.read_text(encoding="utf-8").strip()
        except Exception:
            logger.exception(f"Failed to read master skill file '{main_skill_file}'.")

        for file_path in sorted(SKILLS_DIR.glob(SKILL_FILE_GLOB)):
            if file_path.name == MASTER_SKILL_FILENAME:
                continue
            if file_path.is_file():
                skill_name = file_path.stem
                try:
                    self.skills[skill_name] = file_path.read_text(
                        encoding="utf-8"
                    ).strip()
                except Exception:
                    logger.exception(f"Failed to read skill file '{file_path}'.")

        logger.info(f"Loaded {len(self.skills)} skill(s).")

    def _register_module_tools(
        self,
        tools: list[Callable[..., Any]],
        supported_skill: str | None,
        *,
        description: str | None = None,
    ) -> None:
        if description:
            self.skill_descriptions[supported_skill] = description

        for tool in tools:
            self.tools[tool.__name__] = (tool, supported_skill)

    def execute_tool(self, name: str, args: dict[str, Any]) -> Any:
        if name not in self.tools:
            raise ValueError(f"Tool '{name}' does not exist.")

        tool_func, _ = self.tools[name]

        sig = inspect.signature(tool_func)
        filled_args = dict(args) if args else {}

        for param_name, param in sig.parameters.items():
            if param_name not in filled_args:
                if param.default is not inspect.Parameter.empty:
                    filled_args[param_name] = param.default
                else:
                    filled_args[param_name] = None

        return tool_func(**filled_args)

    def get_callable_tools(
        self, allowed_skills: list[str] | None = None
    ) -> list[Callable[..., Any]]:
        allowed_set = set(allowed_skills or [])
        return [
            tool
            for tool, skill in self.tools.values()
            if skill is None or skill in allowed_set
        ]

    def get_callable_skills(self, allowed_skills: list[str] | None = None) -> str:
        skill_contents = []

        if self.master_skill:
            skill_contents.append(self.master_skill)

        if allowed_skills:
            for skill_name in sorted(set(allowed_skills)):
                if content := self.skills.get(skill_name):
                    skill_contents.append(content)

        return "\n\n---\n\n".join(skill_contents)

    def get_disabled_skills(
        self,
        enabled_skills: list[str] | None = None,
        allowed_skills: list[str] | None = None,
    ) -> list[str]:
        enabled_set = set(enabled_skills or [])
        allowed_set = set(allowed_skills or [])
        disabled_skills = []
        seen_skills = set()

        for _, skill in self.tools.values():
            if (
                skill is None
                or skill in enabled_set
                or skill not in allowed_set
                or skill in seen_skills
            ):
                continue

            disabled_skills.append(skill)
            seen_skills.add(skill)

        return disabled_skills

    def get_skill_description(self, skill: str) -> str:
        return self.skill_descriptions[skill]
