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

        def get_installed_pairs():
            translations = argostranslate.translate.get_all_installed_translations()
            pairs = set()
            for t in translations:
                try:
                    pairs.add((t.from_lang.code, t.to_lang.code))
                except AttributeError:
                    pairs.add((t.from_code, t.to_code))
            return pairs

        installed_pairs = get_installed_pairs()
        
        for source in self.supported_languages:
            for target in self.supported_languages:
                if source == target:
                    continue

                if (source, target) not in installed_pairs:
                    package = next(
                        (pkg for pkg in available_packages 
                        if pkg.from_code == source and pkg.to_code == target),
                        None
                    )

                    if package:
                        download_path = package.download()
                        argostranslate.package.install_from_path(download_path)
                        
                        argostranslate.translate.load_installed_languages()
                        installed_pairs = get_installed_pairs()
                        
    @staticmethod
    def _post_process_text(text: str) -> str:
        return text.strip()
