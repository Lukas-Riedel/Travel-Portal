from pathlib import Path

from src.core.logger import logger


class SkillLoader:
    def __init__(self) -> None:
        self.skills_dir = Path(__file__).resolve().parent.parent.parent / "skills"

    def load_skills(self) -> str:
        if not self.skills_dir.exists() or not self.skills_dir.is_dir():
            logger.warning(f"Skills directory '{self.skills_dir}' does not exist. No system instructions loaded.")
            return ""

        skill_contents: list[str] = []

        # Load main SKILL.md first if it exists
        main_skill_file = self.skills_dir / "SKILL.md"
        if main_skill_file.exists() and main_skill_file.is_file():
            try:
                skill_contents.append(main_skill_file.read_text(encoding="utf-8").strip())
                logger.info(f"Loaded master skill file '{main_skill_file.name}'.")
            except Exception as exc:
                logger.error(f"Failed to read skill file '{main_skill_file}': {exc}")

        # Load all other *.md files in alphabetical order
        for file_path in sorted(self.skills_dir.glob("*.md")):
            if file_path.name == "SKILL.md":
                continue
            if file_path.is_file():
                try:
                    skill_contents.append(file_path.read_text(encoding="utf-8").strip())
                    logger.info(f"Loaded skill module '{file_path.name}'.")
                except Exception as exc:
                    logger.error(f"Failed to read skill file '{file_path}': {exc}")

        aggregated_instructions = "\n\n---\n\n".join(skill_contents)
        logger.info(f"Aggregated {len(skill_contents)} skill module(s) into system instructions.")
        return aggregated_instructions
