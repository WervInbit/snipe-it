# Sequential Asset And Component Identifiers

New automatic asset tags reserve `INBIT-AA0001`, `INBIT-AA0002`, and so on.
Components use an independent sequence: `INBIT-C-AA0001`, `INBIT-C-AA0002`.
After `AA9999` comes `AB0001`; after `AZ9999` comes `BA0001`. Exhaustion at
`ZZ9999` fails explicitly instead of wrapping or switching to random tags.

Existing tags, UUIDs, and printed QR labels remain unchanged. Duplicate
human-readable tags and serials are permitted after explicit confirmation.
Custom tags are supported for new records and permitted edits of existing
records, including the asset case-preservation option. Ordinary edits do not
allocate a replacement tag. Administrators and superusers can change a tracked
component's tag from its detail page; its internal QR UUID stays unchanged.

## Duplicate Confirmation

Asset and tracked-component intake/edit forms check tags and serials while
typing. Each duplicate field has its own unchecked acceptance box. Changing
the value clears its acceptance. A pending or failed check blocks submission;
the server also checks at save time, so JavaScript cannot bypass the rule.
Case and surrounding whitespace are ignored when detecting duplicates, across
both record types, companies, and soft-deleted records. Blank serials do not
count as duplicates. Existing unchanged identifiers do not require repeated
acceptance for ordinary edits, but their detail pages retain duplicate warnings.

The warning links only to records the viewer may access. Other matches are
reported as a count without exposing their details. Confirmed duplicate writes
are recorded in the activity log. A database write guard serializes identifier
checks with persistence to prevent simultaneous unconfirmed duplicate saves.
Bulk database writes outside the application models bypass this guard and
must not be used to modify identifiers.

Web asset batches submit `allow_duplicate_tags[n]` and
`allow_duplicate_serials[n]` for each accepted row. Scalar API/component forms
use `allow_duplicate_tag` and `allow_duplicate_serial`. These flags are explicit
opt-ins; the legacy unique-serial setting no longer disables the warning and
confirmation requirement. Asset CSV imports can map `Allow Duplicate Tag` and
`Allow Duplicate Serial` columns with `true`/`false` values. Updating an asset
by an ambiguous tag requires the CSV `ID` column. Legacy license/component
assignment imports report an error rather than assign to an arbitrary asset.

Scans of ambiguous human-readable tags or manually entered serials present
an authorized record selection. Component `CMP:<uuid>` labels still identify
one component directly; allowing duplicate visible tags never duplicates the
internal UUID. Cached label filenames include the record type and ID to avoid
mixing labels. Existing cache files can remain and new paths regenerate on
demand. Agent reports with an ambiguous tag return HTTP 409 and accept
`asset_id` alongside the matching `asset_tag` to select the destination.

## Reservations And Existing Data

The `identifier_sequences` table holds one counter per prefix. Allocation
updates the counter inside a database transaction before reading the reserved
value. Concurrent requests therefore reserve different numbers. Both asset
and component tables are checked globally, including other companies and
soft-deleted records, before a candidate is returned.

The new sequences start at `AA0001` and skip occupied identifiers;
random legacy letter prefixes do not move the starting point forward.
The legacy asset-tag settings do not control these two fork sequences.

Opening a new asset form reserves its displayed tag. Abandoning that form
can leave a gap; validation redisplays reuse the previous tag. Additional
blank tag fields receive tags when saved. Numbers represent allocation order,
not necessarily save order, and are not a count of inventory records.

## Upgrade And Rollback

Apply both `2026_09_08_120000_create_identifier_sequences_table.php` and
`2026_09_08_130000_allow_confirmed_duplicate_identifiers.php` through the
normal migration process before enabling this code. They create counters and
a write guard, and replace the component-tag unique index with a normal index.
They do not rewrite inventory records. Include the new tables in ordinary
database backups; do not reset them during routine operations. Deploy code and
schema together with application writes paused during the migration window.

Rollback of the duplicate migration refuses to restore a unique component-tag
index while duplicate component tags exist. Resolve those duplicates before
rollback, and review duplicate asset tags before returning to older code that
assumes uniqueness. Counter rollback preserves inventory but loses unused
reservations; close/reload forms during rollback or restore. Label captions
should be reprinted after an intentional tag change.

## Tradeoffs

Numbers are easier to compare, but gaps and independent component/asset
counters are expected. Accepted duplicates add a selection step for ambiguous
labels and integrations must use record IDs. Duplicate checks perform normalized
comparisons across identifier columns and identifier writes share a lock; very
large inventories or unusually heavy concurrent imports should be profiled
before scaling. No new package or production data conversion is required.
