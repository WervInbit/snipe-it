# Agents Addendum - 2025-12-17 Session Init

## Context
- Reviewed AGENTS.md, PROGRESS.md, docs/fork-notes.md, and recent agent addenda before making changes.
- Started a new PROGRESS.md session section to capture today's work.

## Worklog
- Investigated the hardware view specification table overflow on narrow mobile widths (targeting ~327 px screens).
- Found Bootstrap's responsive table rule (`.table-responsive > .table > tbody > tr > td { white-space: nowrap; }`) forcing the spec values to stretch beyond the container.
- Added a mobile-only override in `resources/views/hardware/view.blade.php` to reset cell white-space and wrap long values so the block stays inside its parent.

## Follow-ups
- Validate on an actual A5/phone viewport after cache clears and asset build/deploy; confirm the spec table stays contained below 350 px widths.
- If overflow persists, inspect any remaining long, unbreakable strings in spec values and consider truncation or hyphenation helpers as a next step.
