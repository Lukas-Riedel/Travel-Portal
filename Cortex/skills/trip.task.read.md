# Trip Task Read Skill

## Purpose & Activation
Provides capabilities to query and view actionable tasks and to-do items recorded for trips in Travel Portal.
Activate and apply this skill when the user asks about their tasks, to-do items, or reminders (e.g. "what tasks do I have?", "show me high-priority tasks", "what do I need to do before my Japan trip?").

## Task Guidelines & Actions
1. **Listing Tasks**
   - When the user asks about tasks or to-do items, call `get_tasks`.
   - Apply filters based on what the user mentions:
      - Provide `trip_id` whenever the user mentions a specific trip (e.g. "what do I need to do before my Japan trip?") - resolve it first using trip tools. **NEVER ask the user for a trip ID or UUID.**
      - Provide `max_deadline` whenever the user mentions a specific deadline. Use `convert_datetime_to_epoch` to convert a user-supplied date when filtering by deadline (e.g. "tasks due by end of month").
   - **Trip objects returned by trip tools do NOT contain task data.** You **MUST ALWAYS** call `get_tasks` explicitly - never conclude that no task exists based on the trip object alone.
   - Present tasks grouped by trip and sorted by priority (highest first) when showing multiple tasks. Display title, priority, deadline (if set), and description (if set).
2. **Extracting Task ID**
   - Once the target task is found, use its `id` for subsequent actions. No actions can be performed upon tasks with no `id`. **NEVER invent a fake UUID.**
