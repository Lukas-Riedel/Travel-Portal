from abc import ABC
from typing import Any

from src.tools.base_tools import BaseCoreTools


class BaseDocumentTools(BaseCoreTools, ABC):
    def _extract_document(self, document: dict[str, Any]) -> dict[str, Any]:
        return {
            "id": document.get("id"),
            "name": document.get("name"),
            "issuer": document.get("issuer"),
            "expiration": self._epoch_to_iso(
                epoch=document.get("expiration"), user_tz=self._get_user_timezone()
            ),
        }


class DocumentReadTools(BaseDocumentTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to query and view personal documents such as passports, ID cards, and driving licences stored in Travel Portal. Activate and apply this skill when the user asks about their documents, their issuers, or their expiration (e.g. "what documents do I have?", "when does my passport expire?", "which ID cards do I have stored?").'

    def _get_supported_skill(self) -> str:
        return "document.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_documents,
        ]

    def get_documents(self) -> list[dict[str, Any]]:
        """Retrieves all documents (passports, ID cards, driving licences, and others) stored in Travel Portal.
        Use this when the user asks about their documents, their issuers, or their expiration
        (e.g. "what documents do I have?", "when does my passport expire?").

        Returns:
            A list of document objects, where each object contains:
            - id (string): Unique document identifier.
            - name (string): The name of the document (e.g. `"EEA ID Card"`).
            - issuer (string): The issuer of the document (e.g. `"Prague 4"`).
            - expiration (string | null): ISO 8601 format with timezone offset (e.g. `"2025-12-31T23:59:59+0200"`) (if expiration was provided).
        """
        documents = self.core_client.get_documents()
        self._verify_error_response(documents)

        return [self._extract_document(d) for d in documents]


class DocumentEditTools(BaseDocumentTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to create and store personal documents such as passports, ID cards, and driving licences in Travel Portal. Activate and apply this skill when the user asks to save, store, or log a new document (e.g. "save my new passport", "add my driving licence, it expires in 2030").'

    def _get_supported_skill(self) -> str:
        return "document.edit"

    def _get_tools(self) -> list[Any]:
        return [
            self.create_document,
        ]

    def create_document(
        self,
        name: str,
        code: str,
        issuer: str,
        expiration: int | None,
    ) -> dict[str, Any]:
        """Creates and stores a new document (passport, ID card, driving licence, or other) in Travel Portal.
        Use this when the user asks to save, store, or log a document.
        Never guess or invent the issuer, name, or code - ask the user when any of them is missing.

        Args:
            name: The name of the document (e.g. `"EEA ID Card"`, `"Passport"`).
            code: The code (number) of the document (e.g. `"203432977"`).
            issuer: The issuer of the document (e.g. `"Prague 4"`, `"Emirates"`).
            expiration: Optional expiration timestamp in Unix epoch seconds. Pass None if the document does not expire.

        Returns:
            The created document object containing:
            - id (string): Unique document identifier.
            - name (string): The name of the document.
            - issuer (string): The issuer of the document.
            - expiration (string | null): ISO 8601 format with timezone offset (e.g. `"2025-12-31T23:59:59+0200"`) (if expiration was provided).
        """
        document = self.core_client.create_document(
            name=name,
            code=code,
            issuer=issuer,
            expiration=expiration,
        )
        self._verify_error_response(document)

        return self._extract_document(document)
