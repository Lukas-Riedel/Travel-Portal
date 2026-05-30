import torch
import re
import langcodes

from transformers import AutoModelForSeq2SeqLM, AutoTokenizer


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

        return self._do_translate(text.strip(), target_lang_id)

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

    def _do_translate(self, text: str, target_lang_id: int) -> str:
        sentences = [
            s.strip() for s in re.split(r"(?<=[.!?])\s+", text.strip()) if s.strip()
        ]
        if not sentences:
            return ""

        inputs = self.tokenizer(sentences, return_tensors="pt", padding=True).to(
            self.device
        )

        with torch.no_grad():
            translated_tokens = self.model.generate(
                **inputs,
                forced_bos_token_id=target_lang_id,
                max_new_tokens=256,
                num_beams=1,
                do_sample=False,
            )

        translated_sentences = [
            self.tokenizer.decode(tokens, skip_special_tokens=True).strip()
            for tokens in translated_tokens
        ]

        return " ".join(translated_sentences)
