import os
import logging
from typing import Final
from llama_cpp import Llama
from huggingface_hub import hf_hub_download

logger = logging.getLogger(__name__)

TRANSLATION_SYSTEM_PROMPT: Final[str] = (
    "You are an expert translator specialized in travel, tourism, and geography. "
    "Translate the user text from the source language (ISO code) to the target language (ISO code). "
    "Preserve the exact meaning, tone, and formatting (like capitalization for place names). "
    "For geographical names, use local names if there is no translation to the target language. "
    "Assume that geographical names follow these rules and are in the source language. "
    "Do not chat, do not add any explanations, notes, or quotes around the result. "
    "Output only the final translated text."
)


# TODO: Move reusable parts to AiEngine and make TranslationEngine use it.
class TranslationEngine:
    def __init__(
        self, model_repo: str, model_file: str, models_dir: str, n_threads: int = 4
    ) -> None:
        local_model_path = os.path.join(models_dir, model_file)

        if not os.path.exists(local_model_path):
            logger.info(
                f"Model not found at '{local_model_path}'. Downloading from '{model_repo}'..."
            )
            os.makedirs(models_dir, exist_ok=True)

            local_model_path = hf_hub_download(
                repo_id=model_repo,
                filename=model_file,
                local_dir=models_dir,
                local_dir_use_symlinks=False,
            )

        self.llm = Llama(
            model_path=local_model_path, n_ctx=2048, n_threads=n_threads, verbose=False
        )

    def translate(self, text: str, source_language: str, target_language: str) -> str:
        if not text or not text.strip():
            return ""

        if source_language == target_language:
            return text.strip()

        user_prompt = f"Source language: {source_language}\Target language: {target_language}\n\nText to translate:\n{text.strip()}"

        response = self.llm.create_chat_completion(
            messages=[
                {"role": "system", "content": TRANSLATION_SYSTEM_PROMPT},
                {"role": "user", "content": user_prompt},
            ],
            # TODO: Make this configurable?
            temperature=0.1,
            max_tokens=1024,
        )

        return response["choices"][0]["message"]["content"].strip()
