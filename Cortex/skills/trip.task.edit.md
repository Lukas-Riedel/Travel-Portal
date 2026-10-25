# Trip Task Edit Skill

1. **Creating a Trip Task**
   - When the user asks to add, create, or record a task for a trip, call `create_trip_task`.
   - Always resolve the trip ID first using trip tools. **NEVER ask the user for a trip ID or UUID.**
   - For `deadline`, use `convert_datetime_to_epoch` with the user's current timezone from Environmental Context as `tz_name`.
   - The `title` is expected to contain no more than 5 words, its purpose is to give a short idea of what the task is about (e.g. "reserve flight", "apply for a visa").
   - The `description` is expected to contain no more than 500 characters, its purpose is to clearly describe the action that needs to be done. **ALWAYS provide a plain text, never use Markdown.**
   - The `notification_interval` requires `deadline` to also be set.
   - The `notification_interval` and `auto_delete` set to `true` are mutually exclusive, reject the combination with a clear explanation.
   - **NEVER create tasks with a deadline in the past.**
   - After creating a task, confirm with a concise summary stating the trip, title, priority, and deadline (if set).
2. **Creating a One-Off Notification**
   - When the user asks to send a one-off notification, create a trip task with `auto_delete` set to `true`. The `deadline` corresponds to the timestamp when the notification is sent.
   - After creating a one-off notification, confirm with a concise summary stating the title, description, and deadline.
3. **Updating a Trip Task Title**
   - When the user asks to rename an existing task or change its title, call `update_trip_task_title`.
   - Always resolve the trip ID first using trip tools, then call `get_tasks` to find the target task and its `id`. **NEVER ask the user for a UUID and NEVER invent one.**
   - If multiple tasks match the user's description, **ASK THE USER** to clarify which task to update.
   - The `title` is expected to contain no more than 5 words.
   - After updating the title, confirm with a concise summary stating the old and the new title.
4. **Updating a Trip Task Description**
   - When the user asks to change, rewrite, or add a description of an existing task, call `update_trip_task_description`.
   - Always resolve the trip ID first using trip tools, then call `get_tasks` to find the target task and its `id`. **NEVER ask the user for a UUID and NEVER invent one.**
   - If multiple tasks match the user's description, **ASK THE USER** to clarify which task to update.
   - The `description` is expected to contain no more than 500 characters. **ALWAYS provide a plain text, never use Markdown.**
   - After updating the description, confirm with a concise summary stating the task and the new description.
5. **Updating a Trip Task Priority**
   - When the user asks to change the priority of an existing task, call `update_trip_task_priority`.
   - Always resolve the trip ID first using trip tools, then call `get_tasks` to find the target task and its `id`. **NEVER ask the user for a UUID and NEVER invent one.**
   - If multiple tasks match the user's description, **ASK THE USER** to clarify which task to update.
   - The `priority` must be one of `lowest`, `low`, `medium`, `high`, `highest`.
   - After updating the priority, confirm with a concise summary stating the task and the new priority.
6. **Deleting a Trip Task**
   - When the user asks to delete, remove, dismiss, or complete a task, call `delete_trip_task`.
   - Always resolve the trip ID first using trip tools, then call `get_tasks` to find the target task and its `id`. **NEVER ask the user for a UUID and NEVER invent one.**
   - If multiple tasks match the user's description, **ASK THE USER** to clarify which task to delete.
   - After deleting the task, confirm with a concise confirmation message.
