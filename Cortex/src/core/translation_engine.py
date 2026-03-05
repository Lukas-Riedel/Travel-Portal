import argostranslate.package
import argostranslate.translate
from typing import Final, Set

TARGET_LANGUAGE: Final[str] = "en"

class TranslationEngine:
    def __init__(self, source_languages: Set[str]) -> None:
        self.source_languages = {lang for lang in source_languages if lang != TARGET_LANGUAGE}
        self._initialize_models()

    def translate(self, text: str, source_language: str) -> str:
        if not text or not text.strip():
            return ""

        if source_language == TARGET_LANGUAGE:
            return self._post_process_text(text)

        translated = argostranslate.translate.translate(
            text, source_language, TARGET_LANGUAGE
        )

        return self._post_process_text(translated)

    def _initialize_models(self) -> None:
        argostranslate.package.update_package_index()
        available_packages = argostranslate.package.get_available_packages()
        installed_langs = argostranslate.translate.get_installed_languages()
        installed_codes = {l.code for l in installed_langs}

        for language_code in self.source_languages:
            if language_code not in installed_codes:
                package_to_install = next(
                    filter(
                        lambda x: x.from_code == language_code and x.to_code == TARGET_LANGUAGE,
                        available_packages
                    ), None
                )

                if package_to_install:
                    argostranslate.package.install_from_path(package_to_install.download())

    @staticmethod
    def _post_process_text(text: str) -> str:
        return text.strip().lower()