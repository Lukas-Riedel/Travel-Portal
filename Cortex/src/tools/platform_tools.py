from typing import Any, NoReturn

from src.errors.missing_skills_error import MissingSkillsError
from src.tools.base_tools import BaseTools


class PlatformTools(BaseTools):
    def _get_description(self) -> str | None:
        return None

    def _get_supported_skill(self) -> str | None:
        return None

    def _get_tools(self) -> list[Any]:
        return [
            self.load_skills,
        ]

    def load_skills(self, skills: list[str]) -> NoReturn:
        """Loads additional capability domains, instructions (skills), and associated tools into the current conversation context on-demand.
        Use this when the user asks about topics outside your core loaded skills.

        Args:
            skills: The list of skills to be loaded (e.g. `"trip.carrental.read"`).
        """

        raise MissingSkillsError(skills)
