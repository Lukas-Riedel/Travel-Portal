import importlib
import inspect
import pkgutil
from collections.abc import Callable
from typing import Any

from src.client.core_client import CoreClient
from src.core.logger import logger
from src.tools.base_tools import BaseCoreTools, BaseTools


class ToolRegistry:
    def __init__(self, core_client: CoreClient) -> None:
        self.core_client: CoreClient = core_client
        self.tools: dict[str, tuple[Callable[..., Any], str | None]] = {}
        self.skill_descriptions: dict[str, str] = {}
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
                            instance = cls(core_client=self.core_client)
                        else:
                            instance = cls()

                        self._register_module_tools(
                            tools=instance._get_tools(),
                            supported_skill=instance._get_supported_skill(),
                            description=instance._get_description(),
                        )
                    except Exception as exc:
                        logger.error(
                            f"Failed to initialize and register tools from class '{cls.__name__}': {exc}"
                        )

        logger.info(f"Loaded {len(self.tools)} tool(s).")

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

    def get_callable_tools(
        self, allowed_skills: list[str] | None = None
    ) -> list[Callable[..., Any]]:
        allowed_set = set(allowed_skills or [])
        return [
            tool
            for tool, skill in self.tools.values()
            if skill is None or skill in allowed_set
        ]

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
