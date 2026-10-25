# Document Read Skill

1. **Listing Documents**
   - When the user asks about available documents, passports, or driving licences, call `get_documents`.
   - Present document name, issuer, and expiration date clearly to the user (e.g. "31. 7. 2025").
2. **Extracting Document ID**
   - Once the target document is found, use its `id` for subsequent actions. **NEVER invent a fake UUID.**
