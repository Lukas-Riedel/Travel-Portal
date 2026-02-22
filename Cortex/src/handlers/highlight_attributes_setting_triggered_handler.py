from src.handlers.base_handler import BaseHandler
from typing import List, Union
from src.core.logger import logger
from src.handlers.base_handler import BaseHandler
from src.core.ai_engine import AiEngine
from src.core.core_client import CoreClient

class HighlightAttributesSettingTriggeredHandler(BaseHandler):
    def __init__(self, ai_engine: AiEngine, core_client: CoreClient) -> None:
        self.ai_engine = ai_engine
        self.core_client = core_client

    def handle(self, args: dict) -> None:
        highlight_id = args.get("highlightId")
        highlight = self.core_client.get_highlight(highlight_id)

        if self._has_attributes_set(highlight):
            return

        all_places = self.core_client.get_places()
        reference_highlights = []

        for p in all_places:
            mh = p.get("mainHighlight")
            if mh and mh.get("attributes"):
                reference_highlights.append(mh)

        candidate_emb = self.ai_engine.get_or_create_photo_embedding(
            highlight.get("photo")
        )
        if candidate_emb is None:
            return

        predicted_attributes = self.ai_engine.estimate_attributes_from_references(
            candidate_emb, reference_highlights
        )

        # TODO: Replace this fake race condition prevention by adding a new 'overwrite' query parameter to the update endpoint.
        if predicted_attributes and not self._has_attributes_set(highlight_id):
            self.core_client.update_highlight_quality_attributes(
                highlight_id, predicted_attributes
            )
    
    def get_timeout_seconds(self) -> int:
        return 300

    def get_handled_event_names(self) -> List[str]:
        return ["HighlightAttributesSettingTriggered"]

    def _has_attributes_set(self, highlight_or_id: Union[str, dict]) -> bool:
        if isinstance(highlight_or_id, str):
            highlight = self.core_client.get_highlight(highlight_or_id)
        else:
            highlight = highlight_or_id
        return not highlight or (highlight.get("attributes") and any(highlight["attributes"].values()))