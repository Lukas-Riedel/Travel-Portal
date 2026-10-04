from typing import Any, Callable
from src.service.core_api_client import CoreApiClient
from src.tools.note_tools import NoteTools
from src.tools.trip_tools import TripTools


class ToolRegistry:
    def __init__(self, core_api_client: CoreApiClient):
        self.core_api_client = core_api_client
        self.tools: dict[str, Callable[..., Any]] = {}

        self.trip_tools = TripTools(core_api_client)
        self.note_tools = NoteTools(core_api_client)

        self.register_module_tools(self.trip_tools.get_tools())
        self.register_module_tools(self.note_tools.get_tools())

    def register_module_tools(self, tools: list[Callable[..., Any]]) -> None:
        for tool in tools:
            self.tools[tool.__name__] = tool

    def get_callable_tools(self) -> list[Callable[..., Any]]:
        return list(self.tools.values())

    def execute_tool(self, name: str, args: dict[str, Any]) -> Any:
        if name not in self.tools:
            raise ValueError(f"Tool '{name}' is not registered.")
        return self.tools[name](**args)
