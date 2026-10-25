class GeneralToolError(Exception):
    def __init__(self, message: str) -> None:
        super().__init__(f"The tool thrown an exception in its execution. {message}")


class MissingSkillsError(Exception):
    def __init__(self, skills: list[str]) -> None:
        self.skills: list[str] = skills
        super().__init__(
            f"An error occurred when trying to access the skill(s) '{', '.join(skills)}'."
        )
