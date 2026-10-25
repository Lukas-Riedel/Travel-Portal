from typing import Any

from src.core.logger import logger
from src.errors import MissingSkillsError
from src.tools.base_tools import BaseTools


class PlatformTools(BaseTools):
    def _get_description(self) -> str | None:
        return None

    def _get_supported_skill(self) -> str | None:
        return None

    def _get_tools(self) -> list[Any]:
        return [
            self.load_skills,
            self.report_missing_functionality,
        ]

    def load_skills(self, skills: list[str]) -> None:
        """Loads additional capability domains, instructions (skills), and associated tools into the current conversation context on-demand.
        Use this when the user asks about topics outside your core loaded skills.

        Args:
            skills: The list of skills to be loaded (e.g. `"trip.carrental.read"`).
        """

        raise MissingSkillsError(skills)

    def report_missing_functionality(self, description: str) -> None:
        """Signal that a required capability is absent from all currently available tools.

        Call this whenever the user's request cannot be fulfilled because no existing tool
        covers the needed operation - for example, reading ferries data when only
        flight tools are loaded and loading additional skills would not help.

        **IMPORTANT - batching rule:** NEVER call this tool in a standalone turn.
        Always pair it with another tool call in the same request (e.g. call a
        fallback read tool alongside this one, or return a partial answer while
        reporting the gap). This keeps context usage minimal and avoids wasting
        a full conversation turn solely on the error signal.

        Args:
            description: Human-readable description of the missing capability.
        """

        # TODO: Report missing functionality in the request response. Record it in the database in Core.
        logger.error(f"MISSING FUNCTIONALITY: {description}")
        return None
