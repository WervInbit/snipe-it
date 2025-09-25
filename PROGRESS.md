# Session Progress (2025-09-25)

## Summary
- Created `AGENTS.md` to consolidate contributor guidance tailored to this fork.
- Linked the agent documentation from README.md and CONTRIBUTING.md for quicker discovery.
- Hardened EULA fallback and asset visibility logic so category listings work before settings seeding.
- Delivered the model-number attribute infrastructure (migrations, Eloquent models, admin UI, and resolver services).
- Wired asset create/edit flows for specification overrides and test runs that honor needs-test attributes.
- Updated contributor guide with documentation alignment requirements for this fork.
- Switched `.env` to local debug mode and recycled docker stack for troubleshooting.
- Fixed Passport key permissions so API endpoints load under the web container user.

## Notes for Follow-up Agents
- Review `AGENTS.md` for contributor guidance updates before expanding documentation.
- Backfill existing model data into the new attribute tables before enforcing required specs in production.
- Extend import/API layers to read/write the new attribute structures and add regression tests.
- If additional work occurs in a new session, create a dated addendum (e.g., `progress-2025-09-26.md`) and reference this log.
- Keep `docs/fork-notes.md` focused on high-level feature deltas; log incremental fixes here in `PROGRESS.md`.
- Keep README.md and CONTRIBUTING.md references in sync if the agent docs move or get renamed.
- Session closed for 2025-09-25; resume outstanding work next shift.
