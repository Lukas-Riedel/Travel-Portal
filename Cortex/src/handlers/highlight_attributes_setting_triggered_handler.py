from src.handlers.base_handler import BaseHandler
from typing import List, Final
from src.core.logger import logger
from src.handlers.base_handler import BaseHandler
from src.core.ai_engine import AiEngine
from src.core.core_client import CoreClient

REFERENCE_HIGHLIGHTS_MIN_COUNT: Final[int] = 50


class HighlightAttributesSettingTriggeredHandler(BaseHandler):
    def __init__(self, ai_engine: AiEngine, core_client: CoreClient) -> None:
        self.ai_engine = ai_engine
        self.core_client = core_client

    def handle(self, args: dict) -> None:
        highlight_id = args.get("highlightId")

        highlight = self.core_client.get_highlight(highlight_id)
        if not highlight or (
            highlight.get("attributes") and any(highlight["attributes"].values())
        ):
            return

        all_places = self.core_client.get_places()
        reference_highlights = []

        for p in all_places:
            mh = p.get("mainHighlight")
            if mh and mh.get("attributes"):
                reference_highlights.append(mh)

        if (
            not reference_highlights
            or len(reference_highlights) < REFERENCE_HIGHLIGHTS_MIN_COUNT
        ):
            logger.warning("There are not enough reference highlights. Quality attributes will not be set.")

        candidate_emb = self.ai_engine.get_or_create_photo_embedding(
            highlight.get("photo")
        )
        if candidate_emb is None:
            return

        predicted_attributes = self.ai_engine.estimate_attributes_from_references(
            candidate_emb, reference_highlights
        )

        if predicted_attributes:
            self.core_client.update_highlight_quality_attributes(
                highlight_id, predicted_attributes
            )

    def get_handled_event_names(self) -> List[str]:
        return ["HighlightAttributesSettingTriggered"]
