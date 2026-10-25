# Document Edit Skill

1. **Creating Documents**
   - When the user asks to save, store, or log a document, call `create_document`.
   - Expiration is optional. If the user provides an expiration date, use `convert_datetime_to_epoch` with the user's current timezone from Environmental Context to convert it. If there is no expiration date, pass None.
   - **NEVER create documents with expiration in the past.**
   - **NEVER guess or invent an issuer** (e.g. do not assume "Emirates if not explicitly mentioned).
   - If the issuer is missing or uncertain, **ASK THE USER FIRST** before calling `create_document`.
   - After creating a document, confirm with a concise summary stating the issuer, name, and expiration.