from typing import Set

import argostranslate.package
import argostranslate.translate


class TranslationEngine:
    def __init__(self, supported_languages: Set[str]) -> None:
        self.supported_languages = supported_languages
        self._initialize_models()

    def translate(self, text: str, source_language: str, target_language: str) -> str:
        if not text or not text.strip():
            return ""

        if source_language == target_language:
            return self._post_process_text(text)

        translated = argostranslate.translate.translate(
            text, source_language, target_language
        )

        return self._post_process_text(translated)

    def _initialize_models(self) -> None:
        argostranslate.package.update_package_index()
        available_packages = argostranslate.package.get_available_packages()

        installed_languages = argostranslate.translate.get_installed_languages()
        installed_pairs = {
            (language.code, translation.to_code)
            for language in installed_languages
            for translation in language.translations
        }

        for source_language in self.supported_languages:
            for target_language in self.supported_languages:
                if source_language == target_language:
                    continue

                if (source_language, target_language) not in installed_pairs:
                    package_to_install = next(
                        filter(
                            lambda x: x.from_code == source_language and x.to_code == target_language,
                            available_packages
                        ), None
                    )

                    if package_to_install:
                        argostranslate.package.install_from_path(package_to_install.download())

    @staticmethod
    def _post_process_text(text: str) -> str:
        return text.strip()
