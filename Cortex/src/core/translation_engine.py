import torch
from transformers import MarianMTModel, MarianTokenizer
from typing import Set, Dict


class TranslationEngine:
    def __init__(self, model_name_format: str, supported_languages: Set[str], device: str) -> None:
        self.model_name_format = model_name_format
        self.supported_languages = supported_languages
        self.device = device
        self.models: Dict[str, MarianMTModel] = {}
        self.tokenizers: Dict[str, MarianTokenizer] = {}
        self._initialize_models()

    def _initialize_models(self) -> None:
        for source in self.supported_languages:
            for target in self.supported_languages:
                if source == target:
                    continue

                model_name = self.model_name_format.format(source=source, target=target)
                tokenizer = MarianTokenizer.from_pretrained(model_name)
                model = MarianMTModel.from_pretrained(model_name)

                model.to(self.device)
                model.eval()

                pair_key = f"{source}-{target}"
                self.tokenizers[pair_key] = tokenizer
                self.models[pair_key] = model

    def translate(self, text: str, source_language: str, target_language: str) -> str:
        if not text or not text.strip():
            return ""

        if source_language == target_language:
            return self._post_process_text(text)

        pair_key = f"{source_language}-{target_language}"

        if pair_key not in self.models:
            return self._post_process_text(text)

        tokenizer = self.tokenizers[pair_key]
        model = self.models[pair_key]

        inputs = tokenizer(text, return_tensors="pt", padding=True, truncation=True).to(
            self.device
        )

        with torch.no_grad():
            translated_tokens = model.generate(**inputs, max_new_tokens=512)

        translated_text = tokenizer.decode(
            translated_tokens[0], skip_special_tokens=True
        )

        return self._post_process_text(translated_text)

    @staticmethod
    def _post_process_text(text: str) -> str:
        return text.strip()
