import importlib
import inspect
import pkgutil
from typing import Any, Callable

from src.core.logger import logger
from src.service.core_api_client import CoreApiClient
from src.tools.base_tools import BaseTools, CoreApiTools


class ToolRegistry:
    def __init__(self, core_api_client: CoreApiClient):
        self.core_api_client = core_api_client
        self.tools: dict[str, Callable[..., Any]] = {}
        self._auto_discover_and_register()

    def _auto_discover_and_register(self) -> None:
        import src.tools as tools_package

        for module_info in pkgutil.iter_modules(tools_package.__path__):
            module_name = f"{tools_package.__name__}.{module_info.name}"
            module = importlib.import_module(module_name)

            for _, cls in inspect.getmembers(module, inspect.isclass):
                if cls.__module__ == module_name and issubclass(cls, BaseTools) and cls is not BaseTools and cls is not CoreApiTools:
                    try:
                        if issubclass(cls, CoreApiTools):
                            instance = cls(self.core_api_client)
                        else:
                            instance = cls()
                        discovered_tools = instance.get_tools()
                        self._register_module_tools(discovered_tools)
                        logger.info(f"Automatically registered tools from '{cls.__name__}' ({len(discovered_tools)} tool(s)).")
                    except Exception as exc:
                        logger.error(f"Failed to initialize and register tools from class '{cls.__name__}': {exc}")

    def _register_module_tools(self, tools: list[Callable[..., Any]]) -> None:
        for tool in tools:
            self.tools[tool.__name__] = tool

    def get_callable_tools(self) -> list[Callable[..., Any]]:
        return list(self.tools.values())
