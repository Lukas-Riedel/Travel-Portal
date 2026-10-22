from typing import Any

from src.core.generative_content_context import generative_content_skills
from src.tools.base_tools import BaseTools


class PlatformTools(BaseTools):
    def _get_description(self) -> str | None:
        return None

    def _is_enabled_by_default(self) -> bool:
        return True

    def _get_supported_skill(self) -> str | None:
        return None

    def _get_tools(self) -> list[Any]:
        return [
            self.load_skills,
        ]

    def load_skills(self, skills: list[str]) -> list[str]:
        """Loads additional capability domains, instructions (skills), and associated tools into the current conversation context on-demand.
        Use this when the user asks about topics outside your core loaded skills.

        Args:
            skills: The list of skills to be loaded (e.g. `"trip.carrental.read"`).

        Returns:
            A list of all loaded skills.
        """
        current_skills = generative_content_skills.get()
        new_unique_skills = [s for s in skills if s not in current_skills]

        if new_unique_skills:
            generative_content_skills.set(current_skills + new_unique_skills)

        return generative_content_skills.get()
