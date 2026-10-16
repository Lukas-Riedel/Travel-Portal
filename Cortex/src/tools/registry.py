import importlib
import inspect
import pkgutil
from typing import Any, Callable

from src.client.core_client import CoreClient
from src.core.logger import logger
from src.tools.base_tools import BaseTools, BaseCoreTools


class ToolRegistry:
    def __init__(self, core_client: CoreClient):
        self.core_client = core_client
        self.tools: dict[str, tuple[Callable[..., Any], str | None]] = {}
        self._auto_discover_and_register()

    def _auto_discover_and_register(self) -> None:
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
                            instance = cls(self.core_client)
                        else:
                            instance = cls()
                        discovered_tools = instance._get_tools()
                        supported_skill = instance._get_supported_skill()
                        self._register_module_tools(discovered_tools, supported_skill)
                    except Exception as exc:
                        logger.error(f"Failed to initialize and register tools from class '{cls.__name__}': {exc}")

        logger.info(f"Loaded {len(self.tools)} tool(s).")

    def _register_module_tools(self, tools: list[Callable[..., Any]], supported_skill: str | None) -> None:
        for tool in tools:
            self.tools[tool.__name__] = (tool, supported_skill)

    def get_callable_tools(self, enabled_skills: list[str] | None = None) -> list[Callable[..., Any]]:
        if enabled_skills is None:
            return [tool for tool, _ in self.tools.values()]

        enabled_set = set(enabled_skills)
        return [
            tool
            for tool, skill in self.tools.values()
            if skill is None or skill in enabled_set
        ]
