class GeneralToolError(Exception):
    def __init__(self, message: str) -> None:
        super().__init__(f"The tool thrown an exception in its execution. {message}")


# TODO: This serves as an LLM engine meta-command, which is kind of a hack.
class MissingSkillsError(Exception):
    def __init__(self, skills: list[str]) -> None:
        self.skills: list[str] = skills
        super().__init__(
            f"An error occurred when trying to access the skill(s) '{', '.join(skills)}'."
        )


# TODO: This serves as an LLM engine meta-command, which is kind of a hack.
class MissingTopicError(Exception):
    def __init__(self, topic_ids: list[str]) -> None:
        self.topic_ids: list[str] = topic_ids
        super().__init__(
            f"An error occurred when trying to access the topic '{', '.join(topic_ids)}'."
        )
