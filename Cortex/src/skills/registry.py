from pathlib import Path
from typing import Final

from src.core.logger import logger

SKILLS_DIR: Final[Path] = Path(__file__).resolve().parent.parent.parent / "skills"
MASTER_SKILL_FILENAME: Final[str] = "SKILL.md"
SKILL_FILE_GLOB: Final[str] = "*.md"


class SkillRegistry:
    def __init__(self) -> None:
        self.master_skill: str = ""
        self.skills: dict[str, str] = {}
        self._auto_discover_and_register()

    def _auto_discover_and_register(self) -> None:
        if not SKILLS_DIR.exists() or not SKILLS_DIR.is_dir():
            logger.warning(f"Skills directory '{SKILLS_DIR}' does not exist.")
            return

        main_skill_file = SKILLS_DIR / MASTER_SKILL_FILENAME
        if main_skill_file.exists() and main_skill_file.is_file():
            try:
                self.master_skill = main_skill_file.read_text(encoding="utf-8").strip()
            except Exception as exc:
                logger.error(
                    f"Failed to read master skill file '{main_skill_file}': {exc}"
                )

        for file_path in sorted(SKILLS_DIR.glob(SKILL_FILE_GLOB)):
            if file_path.name == MASTER_SKILL_FILENAME:
                continue
            if file_path.is_file():
                skill_name = file_path.stem
                try:
                    self.skills[skill_name] = file_path.read_text(
                        encoding="utf-8"
                    ).strip()
                except Exception as exc:
                    logger.error(f"Failed to read skill file '{file_path}': {exc}")

        logger.info(f"Loaded {len(self.skills)} skill(s).")

    def get_callable_skills(self, enabled_skills: list[str] | None = None) -> str:
        skill_contents = []

        if self.master_skill:
            skill_contents.append(self.master_skill)

        if enabled_skills:
            for skill_name in sorted(set(enabled_skills)):
                if content := self.skills.get(skill_name):
                    skill_contents.append(content)

        return "\n\n---\n\n".join(skill_contents)
