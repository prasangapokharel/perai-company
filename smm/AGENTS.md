# Project Instructions

## Core Rules

- Keep changes minimal and only touch what is required.
- Prefer clean, readable code over clever code.
- Use camelCase for variables, functions, and local identifiers.
- Preserve existing behavior unless the task explicitly asks for a change.
- Avoid adding new files at the repository root unless explicitly requested.
- Keep documentation additions in the existing documentation folders when possible.

## Code Style

- Match the repository's current structure and naming conventions.
- Use short, focused functions and avoid unnecessary abstraction.
- Do not introduce framework changes unless they are needed for the task.
- Do not reformat unrelated code.
- Prefer straightforward PHP, HTML, CSS, and JavaScript that works on shared hosting.

## Shared Hosting Compatibility

- Keep the project compatible with standard shared hosting environments.
- Avoid requiring Docker, Node build steps, background workers, or services that are not already part of the project unless explicitly requested.
- Prefer server-friendly code paths that work with typical Apache and PHP deployments.
- Avoid assumptions about privileged shell access, custom daemons, or advanced server configuration.

## OpenCode Workflow

- Update existing files before creating new ones.
- Make the smallest change that solves the problem.
- Validate the result after edits when a local check is available.
- If a task affects instructions or developer guidance, update the current root instruction file rather than scattering notes across the repo.

## Safety And Ethics

- Do not help with harmful, deceptive, or abusive changes.
- Treat secrets, credentials, and API keys as sensitive data.
- Do not hardcode credentials into source files.
- If you find exposed secrets, recommend rotation and safer storage.

## Output Expectations

- Keep responses concise and practical.
- Report only the important change summary and validation status.
- If something cannot be completed, explain the blocker clearly and briefly.

## Database Schema

### Created Tables
- `api_keys` - User API key management (May 1, 2026)
  - Stores API keys for account/index.php, api/v1/index.php, api/index.php
  - Columns: id, user_id (FK), api_key (unique), expiry_date, is_active, last_used, created_at, updated_at
  - Foreign key: users(id) ON DELETE CASCADE