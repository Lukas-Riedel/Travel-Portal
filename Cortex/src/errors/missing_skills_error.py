class MissingSkillsError(Exception):
    def __init__(self, skills: list[str]) -> None:
        self.skills: list[str] = skills
        super().__init__(
            f"An error occurred when trying to access the skill(s) '{', '.join(skills)}'."
        )
