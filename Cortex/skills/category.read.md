# Category Read Skill

1. **Listing Categories**
   - When the user asks to browse or list categories (e.g. "what categories do I have?", "show me all the seas"), call `get_categories`.
   - Provide `category_type` whenever the user mentions a particular type (e.g. "show me all island categories"). The tool filters by type after retrieving categories.
   - Provide `country` whenever the user asks for administrative categories in a particular country (e.g. "list states in the United States of America"). The tool automatically limits country-filtered results to administrative categories, because only administrative categories are bound to a country.
   - Present category names and relevant details in the user's language.
2. **Finding a Category by Name**
   - When the user refers to a specific category by name (e.g. "find the category Europe", "what is the category for the United Kingdom?"), call `get_category_by_name` with the name as query.
   - If no match is found, ask the user for clarification, or use tools for listing. This can happen fairly often since the tool performs a full-text search.
3. **Listing Public Holidays**
   - When the user asks about public holidays for a category or administrative region (e.g. "what are the public holidays in California in 2026?", "show me Japan's public holidays"), resolve the category first, then call `get_category_public_holidays` with the category ID and the requested year. If no year is specified, omit it to retrieve holidays for the current year. **NEVER ask the user for a category ID or UUID.**
   - Present the returned holidays in a structured list or table, including their name and date. Do not infer holidays that are not returned by the tool.
4. **Category Types**
   - `continent` - a continent (e.g. Europe).
   - `country` - a country (e.g. Czechia).
   - `administrative` - an administrative division of a country (e.g. a state or province).
   - `ocean` - an ocean (e.g. Pacific Ocean).
   - `sea` - a sea (e.g. Mediterranean Sea).
   - `bay` - a bay (e.g. Bay of Biscay).
   - `island` - an island (e.g. Iceland).
   - `region` - a geographical or composite region (e.g. Scandinavia, European Union).
5. **Extracting Category ID**
   - Once the target category is found, use its `id` for subsequent actions. **NEVER invent a fake UUID.**
