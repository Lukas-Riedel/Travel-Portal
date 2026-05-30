import torch
import langcodes
from typing import Final

from transformers import AutoModelForSeq2SeqLM, AutoTokenizer

SHORT_PHRASE_WORDS_THRESHOLD: Final[int] = 5


class TranslationEngine:
    def __init__(self, model_name: str, device: str) -> None:
        self.device = device
        self.tokenizer = AutoTokenizer.from_pretrained(model_name)
        self.model = AutoModelForSeq2SeqLM.from_pretrained(model_name).to(device)
        self.model.eval()

    def translate(
        self,
        text: str,
        source_language: str,
        target_language: str,
    ) -> str:
        if not text or not text.strip():
            return ""

        if source_language == target_language:
            return text.strip()

        source_lang = self._to_nllb_language(source_language)
        target_lang = self._to_nllb_language(target_language)

        self.tokenizer.src_lang = source_lang
        target_lang_id = self.tokenizer.convert_tokens_to_ids(target_lang)

        is_short_phrase = len(text.strip().split()) <= SHORT_PHRASE_WORDS_THRESHOLD
        return self._do_translate(text.strip(), target_lang_id, is_short_phrase)

    def _to_nllb_language(self, iso_code: str) -> str:
        try:
            iso3 = langcodes.Language.get(iso_code).to_alpha3()
        except Exception:
            raise ValueError(f"The language code '{iso_code}' is invalid.")

        matches = [
            code
            for code in self.tokenizer.additional_special_tokens
            if code.startswith(f"{iso3}_")
        ]

        if not matches:
            raise ValueError(
                f"The language '{iso_code}' is not supported by the model."
            )

        return matches[0]

    def _do_translate(
        self, text: str, target_lang_id: int, is_short_phrase: bool
    ) -> str:
        inputs = self.tokenizer(text, return_tensors="pt").to(self.device)

        with torch.no_grad():
            if is_short_phrase:
                translated_tokens = self.model.generate(
                    **inputs,
                    forced_bos_token_id=target_lang_id,
                    max_new_tokens=64,
                    # TODO: Make this configurable?
                    num_beams=5,
                    temperature=0.0,
                    length_penalty=0.6,
                    early_stopping=True,
                )
            else:
                translated_tokens = self.model.generate(
                    **inputs,
                    forced_bos_token_id=target_lang_id,
                    max_new_tokens=1024,
                    num_beams=1,
                    do_sample=False,
                )

        return self.tokenizer.decode(
            translated_tokens[0], skip_special_tokens=True
        ).strip()
