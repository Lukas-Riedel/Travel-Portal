from typing import Any

from src.core.logger import logger
from src.errors import MissingSkillsError, MissingTopicError
from src.tools.base_tools import BasePistonTools


class PlatformTools(BasePistonTools):
    def _get_description(self) -> str | None:
        return None

    def _get_supported_skill(self) -> str | None:
        return None

    def _get_tools(self) -> list[Any]:
        return [
            self.load_skills,
            self.report_missing_functionality,
            self.add_conversation_topic,
            self.execute_python_code,
        ]

    def execute_python_code(self, code: str) -> dict[str, Any]:
        """Execute Python code in the isolated sanfbox runtime.
        Use this for calculations or to run Python code when needed. The result contains the execution status and the program's output.

        Args:
            code: The Python source code to execute.

        Returns:
            An object containing:
            - success (boolean): Whether the program exited with code 0.
            - code (integer): The program's exit code.
            - output (string): Combined standard output and standard error.
            - stdout (string): Standard output from the program.
            - stderr (string): Standard error from the program, including any traceback.
            - signal (string or null): Signal that stopped the program, if any.
            - status (string or null): Sandbox execution status, if set.
            - message (string or null): Additional message from sandbox, if any.
            - version (string or null): Python runtime version selected by sandbox.
        """

        return self.piston_client.execute_python_code(code=code)

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

    def add_conversation_topic(self, topic_ids: list[str]) -> None:
        """Declare primary conversation topics (e.g. place IDs, trip IDs, label IDs, category IDs, or years)
        associated with the current user request and retrieved data.

        Call this to anchor the UI presentation when the user's request focuses on specific entities,
        allowing the system to attach relevant images or highlights to the AI response.

        **IMPORTANT - batching rule:** NEVER call this tool in a standalone turn.
        Always batch it in the same request alongside the primary data-fetching tool that retrieves
        the details for these entities (e.g. call `search_places` and `add_conversation_topic` together).

        Args:
            topic_ids: A list of unique string identifiers representing the main entities or topics discussed.
        """

        raise MissingTopicError(topic_ids)
