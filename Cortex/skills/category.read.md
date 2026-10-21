# Category Read Skill

## Purpose & Activation
Provides capabilities to list and find categories in Travel Portal.
Activate and apply this skill when the user asks about categories, their types, or a category by name (e.g. "show me all island categories", "list administrative regions in Czechia", "find the category Europe").

## Category Guidelines & Actions
1. **Listing Categories**
   - When the user asks to browse or list categories (e.g. "what categories do I have?", "show me all the seas"), call `get_categories`.
   - Provide `category_type` whenever the user mentions a particular type (e.g. "show me all island categories"). The tool filters by type after retrieving categories.
   - Provide `country` whenever the user asks for administrative categories in a particular country (e.g. "list states in the United States of America"). The tool automatically limits country-filtered results to administrative categories, because only administrative categories are bound to a country.
   - Present category names and relevant details in the user's language.
2. **Finding a Category by Name**
   - When the user refers to a specific category by name (e.g. "find the category Europe", "what is the category for the United Kingdom?"), call `get_category_by_name` with the name as query.
   - If no match is found, ask the user for clarification, or use tools for listing. This can happen fairly often since the tool performs a full-text search.
3. **Category Types**
   - `continent` - a continent (e.g. Europe).
   - `country` - a country (e.g. Czechia).
   - `administrative` - an administrative division of a country (e.g. a state or province).
   - `ocean` - an ocean (e.g. Pacific Ocean).
   - `sea` - a sea (e.g. Mediterranean Sea).
   - `bay` - a bay (e.g. Bay of Biscay).
   - `island` - an island (e.g. Iceland).
   - `region` - a geographical or composite region (e.g. Scandinavia, European Union).
4. **Extracting Category ID**
   - Once the target category is found, use its `id` for subsequent actions. **NEVER invent a fake UUID.**
