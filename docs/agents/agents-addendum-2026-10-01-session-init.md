# Agent Session Addendum - 2026-10-01

## Starting Context

- Continued on `codex/refurbisher-followups-2026-09-22` in the existing
  managed worktree.
- Reviewed `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, and the production
  release record before revalidation.
- Preserved the uncommitted documentation changes that record the 2026-09-29
  HP ProBook production catalogue addition.

## Scope

- Reverify the live HP ProBook 450 G9 model-number baseline and distinguish
  automated acceptance evidence from signed-in UI and physical-device checks.
- Investigate, without implementing, the current programmable-key,
  device-history/data-erasure, and optical-drive workflow coverage.
- After owner approval, correct the programmable-key catalogue label and model
  assignments, add generic optical-drive component choices, and add
  workflow-item quick search locally without rewriting workflow content.

## Safety Boundary

- Production remained read-only during investigation and local implementation.
  The owner's later explicit instruction authorizes a controlled rollout only
  after the release gates, backup, and duplicate/baseline checks pass.

## Outcome

- Live production revalidation passed all 24 expected resolved values, all 11
  model-number component templates, all six motherboard subcomponent
  templates, and all four newly added component definitions.
- Duplicate and cross-type identifier checks remain zero. There are no failed
  or queued jobs and no pending migrations. HTTPS health/login return 200 and
  recent service logs are clean.
- All services are healthy. The queue restart counter reflects its configured
  clean hourly `--max-time=3600` lifecycle; the latest exit code is zero.
- No production write occurred. Signed-in create-form rendering and physical
  verification of a real refurbished unit remain manual acceptance boundaries.

## Workflow Gap Investigation

- The live programmable-key configuration consists of attribute ID 49,
  component definition ID 113, and workflow item ID 37 in Standard
  Diagnostics. The workflow item is correctly scoped to the component, and the
  component contributes the boolean attribute, but no model number or tracked
  component uses it. It is therefore not currently presented for any device.
  The attribute, component, item, and slug contain inconsistent misspellings.
- `Laptop wipen` is active, restricted to the Laptops asset category, blocks
  sale readiness, and is a prerequisite of `Windows Installeren en Updaten`.
  Its required final item confirms that the disk was erased. There is no
  separate step for removing post-installation user accounts, browser/history
  data, downloads, recent files, or other refurbishing residue.
- No optical-drive record was found across production attributes, component
  definitions/categories, model-number expected components, model naming, or
  workflow items. A future implementation should normally use a physical
  optical-drive component plus a component-scoped test; an extra asset
  attribute is unnecessary unless independent filtering is required.
- The repository intentionally leaves programmable-key and erase-history
  content outside the foundation seeder pending approved operator wording and
  applicability. The ownership test asserts those seeded slugs stay absent.
- No application data changed. One PsySH history-directory diagnostic and one
  malformed read-only SQL diagnostic were logged during inspection; corrected
  read-only checks used Laravel bootstrap and in-memory collection filtering.

## Follow-Up Findings

- HP documentation confirms that the ProBook 450 G8 family has an F12
  Programmable Key and that HP's Programmable Key package supports this model.
  Production's `2E9F8EA#ABH` model number has two assets but does not yet carry
  the existing boolean attribute or unused component. The intended future
  configuration is a direct model-number boolean attribute plus an
  attribute-scoped workflow item, not a synthetic physical component.
- The manually added `Geschiedenis wissen` workflow item is required in
  Standard Diagnostics and is correctly scoped to Laptops and NUC. Its wording
  can be expanded later to cover temporary accounts and all refurbishment test
  residue in addition to media and browser history.
- Optical drives should be concrete expected components under a dedicated
  component category and assigned only to model numbers that physically include
  them. Separate read/write applicability can be driven by component category
  and definition without adding detailed optical-drive attributes.
- Both the Workflow Items index and the per-profile item editor currently lack
  search. A client-side quick filter is sufficient because both pages load the
  complete item set. Reordering must be unavailable while filtered to avoid
  persisting a partial-list order.
- One mistaken read-only query used the absent `test_results` table name and
  logged a diagnostic. The corrected `workflow_results` query found no existing
  programmable-key results; no state changed.

## Approved Local Implementation

- Retained the existing `programeerbare_toets` boolean key, corrected its
  visible label to `Programmeerbare toets`, assigned it to the verified HP
  ProBook 450 G8 model number, and added a supplemental assignment for an
  existing HP ProBook 450 G9 `6A140EA#ABH` model number.
- Kept the programmable-key workflow block as dev/operator-managed content.
  The catalogue seeders do not create, rename, rescope, or rewrite that item.
- Added asset-only `Optical Drive - DVD-ROM` and
  `Optical Drive - DVD+/-RW` definitions under a new `Optical Drives` component
  category. No model-number template is created automatically.
- Added live search to the Workflow Items index and separate Included/Available
  searches to the workflow-profile editor. Reordering is disabled while the
  included/full list is filtered.
- Kept the manually authored `Geschiedenis wissen` workflow item unchanged.
- Production remained untouched during local implementation. Focused tests
  passed 39 tests / 284 assertions using guarded in-memory SQLite; PHP syntax,
  Blade compilation, and diff whitespace checks also passed.
- The final dev reconciliation used only `DeviceAttributeSeeder`,
  `DevicePresetSeeder`, and `DeviceComponentCatalogSeeder`. It preserved
  attribute ID 49 and workflow item ID 31, restored the established internal
  key, retained the G8 value, and confirmed two active unassigned optical
  definitions. The production-only G9 catalogue row is absent locally, so its
  supplemental assignment was correctly skipped. Workflow content was
  intentionally excluded from the catalogue rollout.
- The repository-configured `phpcs` check remains non-clean because its legacy
  standard rejects existing CRLF, docblock, private-method, line-length, and
  snake_case PHPUnit conventions throughout the touched files. It is not used
  as passing evidence for this work.

## Production Release Gate

- Committed and pushed the approved catalogue/search implementation as
  `5ee57f58db` and fast-forwarded `origin/master` to that commit.
- A fresh 2026-10-01 Trivy database stopped the rollout before any production
  write because newly published advisories affected locked CommonMark,
  Flysystem, phpseclib, and brace-expansion versions.
- Updated only the affected patch releases: CommonMark 2.10.2, Flysystem
  3.35.3, phpseclib 3.0.57, and brace-expansion 5.0.11. Composer and npm lock
  installs succeed with those versions.
- Extended the expiry of the existing Laravel 11 scanner exceptions to
  2027-02-01. Those exceptions remain limited to checksum-pinned official
  backports with regression tests. The newly reported low-severity Laravel
  debug-page advisory is ignored by Composer only because the production
  entrypoint rejects `APP_DEBUG=true`.
- The feature tests passed in the mounted development checkout. Static release
  tests still show the known Windows CRLF false failures there and must pass
  from the exact LF release archive before deployment.
- Production remains unchanged while the replacement release is committed,
  rebuilt, rescanned, backed up, and qualified.

## Qualified Candidate And Access Blocker

- Committed the dependency refresh as `2f703760d8`, pushed the feature branch,
  and fast-forwarded `origin/master` to the same commit.
- The exact normalized release archive passed 79 tests / 794 assertions across
  the catalogue/search, production-container, backport, and release-policy
  boundaries. Both app and web images passed the production content verifier.
- Trivy 0.66 with the current 2026-10-01 database reported zero unsuppressed
  HIGH or CRITICAL findings for both exact images.
- Candidate image IDs are `sha256:590a16b45eee...` (app) and
  `sha256:47ff9aa73a73...` (web). The prepared source and image bundles are in
  `C:\dev\snipe-it-transfer-2f703760d8`; their SHA-256 values are respectively
  `a1a4c5f662bdcd60a0591fecebcf369999076d224f926e1184e2878e64e7c758`
  and `b7703639972cc04e2627efbbe5c248e20b038dbe95adf6a69c93f4c02435b359`.
- `snipe.inbit` now resolves to the migrated `10.10.10.249` host and its health
  and login endpoints return HTTP 200. Port 22 is reachable, but neither the
  normal existing key nor the documented migration key is authorized there.
  The old `10.10.10.33` host is unreachable.
- No production preflight, backup, transfer, seeder, restart, or write was
  attempted without authenticated host access. Deployment remains blocked
  until the new server authorizes the existing deployment key for an account
  with the documented Docker and backup privileges.

## Production Deployment Completion

- The owner identified the new SSH account as `codex-agent`. Key-based access
  and non-interactive sudo succeeded; Docker access remained intentionally
  mediated through sudo.
- Completed production preflight on `10.10.10.249`, entered maintenance, and
  stopped queue/scheduler writers before data or container changes.
- Created and verified the complete recovery set
  `pre-workflow-catalog-20261001T110153Z` on-host and off-host. The archive
  SHA-256 is
  `159cd2a9edbad8b2063e531f72f1add779823158813ce6d521ee4849c61d8a51`.
- Confirmed no migration was pending and ran only the three approved catalogue
  seeders. The existing programmable-key workflow item compared exactly equal
  before and after; no workflow or permission seeder ran.
- The attribute key remains `programeerbare_toets`, its visible label is now
  `Programmeerbare toets`, and both G8/G9 target model numbers carry true.
  Both optical definitions are asset-only and unassigned.
- Investigated the component-definition count increase before cutover. The
  other four rows are previously documented generic catalogue definitions
  that production lacked; all six new rows have zero templates and zero
  instances, with no duplicate names.
- Promoted release `/srv/snipeit-v1/releases/2f703760d8` and its immutable
  image digests atomically while maintenance remained active, verified app/web
  health, reopened the application, then recreated queue, scheduler, TLS init,
  and edge.
- Post-cutover validation passed for service health/restarts, image identities,
  config, HTTPS, queues, failed jobs, migration status, data counts, duplicate
  checks, catalogue values, and fresh logs. No production credential or bypass
  was introduced; signed-in search UI remains an operator smoke check.
- See `docs/releases/workflow-catalog-search-production-2026-10-01.md` for the
  retained release evidence and rollback anchors.

## Remaining Relevant Guides Block

- Owner authorized generation of all relevant guides. Expand the six priority
  guides to cover implemented account/rights, identifiers and catalogue changes.
  Use the mounted worktree read-only, preserve existing PDFs/selections, and
  retain newly numbered unaccepted drafts with source and evidence provenance.
- Outcome and checks pending in the dated batch review.

## WF-01 V15 Correction

- Responded to owner feedback on the unnatural blocked/Edit first example
  and unclear "afgesproken rij" wording. V15 shows Standard Diagnostics Start
  and waiting Cleaning; 3A uses the same workflow. Read-only new local capture.
- One page, four steps and five visuals retained. Artifact checks and final
  144-dpi review passed; operator/print trials remain pending. V15 is unaccepted.
  WF-02 and accepted selections are unchanged. See [v15 review](../manuals/operator-guides/reviews/WF-01-v15.md).

## WF Revision Block

- Owner authorized new WF-01/WF-02 versions and explicitly withheld acceptance.
  Delivered separate WF-01 v14 / WF-02 v13 working drafts, preserving all
  accepted selections and prior PDFs. See the
  [paired review](../manuals/operator-guides/reviews/workflow-revisions-2026-10-01.md).
- Kept the existing one-page/two-sided task layouts, steps, help and handoffs;
  replaced obsolete UI evidence with six genuine local captures. Local font
  and evidence-column differences are documented as readability tradeoffs.
- Inspected the other agent's mounted application worktree read-only. No
  result, note, photo, status, profile or permission write was submitted. An
  existing answered run's Done return to the same asset's Workflows tab passed.
- Artifact geometry, page/text/hash/A4 checks, shared guide-system checks, and
  final three-page 144-dpi visual review passed. Remaining operational/print/
  reader trials and explicit exact-version decisions stay open.
- No application tests, database commands, deployment or commit were needed.

## Scope And Concurrent Work

- Initialize the manuals session from the repository handbook and relevant
  documentation, then locate application changes requiring guide maintenance.
- Another agent is active in the project. Preserve the existing dirty working
  tree, including manual specifications, generators, evidence, and PDFs.
- Add this session record, a narrow progress entry, and a new dated change map.
  Leave existing guide selections, accepted packages, and historical bytes intact.
- Application implementation, shared runtime changes, database operations,
  deployment, commits, and external messaging are outside this initial block.

## Context Read

- `AGENTS.md`, `PROGRESS.md`, `docs/fork-notes.md`, `README.md`,
  `CONTRIBUTING.md`, and relevant entries in `TODO.md`.
- Operator-guide README, HANDOFF, registry, system, and maintenance contract.
- September owner-review follow-ups, the USR-05/CAT-05/CAT-06 v2 review,
  accepted-package README, and affected WF/AST/AC guide specifications.
- `docs/plans/refurbisher-review-followups-2026-09-17.md`, including the
  owner's September 22 implementation decisions and Guide Updates section.
- Current workflow progression/readiness, run ordering, and asset-status
  guard source, with no runtime mutation.

## Findings And Next Work

- The September 10 accepted snapshot contains 16 guides / 27 pages; acceptance
  here means internal-review clearance, not completed third-party/user trials.
- The September 15 portable current kit records 25 guide codes / 61 pages,
  including unaccepted versions. It is a different selection from the accepted
  package and must not become the accepted baseline implicitly.
- USR-05/CAT-05/CAT-06 v2 remain working drafts. Preserve their trials and
  exact-version review requirements alongside the new application-change work.
- WF-01/WF-02 and AST-05 need early reconciliation with implemented September
  behavior. Pending refurbisher UI, role, status, and attribute work must be
  checked against the other agent's completed changes before guide authoring.
- Initial scope, evidence needs, and revision order:
  [application change map](../manuals/operator-guides/reviews/application-change-map-2026-10-01.md).

## Verification And Limits

- `node scripts/verify.mjs` from
  `resources/manuals/operator-guides/accepted-guides-v1` passed: 16 accepted
  guide codes, seven historical PDFs, and 369 verified files.
- Repository and source inspection only; no application tests, browser trials,
  new screenshots, PDF rendering, or acceptance changes in this block.
- The impact map is an initial source/specification assessment, not a completed
  page-by-page audit of every retained PDF or a production UI validation.

## Printer And CUPS Investigation Session (2026-10-01)

- Initialized from AGENTS.md, progress/fork notes, contributor guidance, current
  QR print code, CUPS setup guide, and production host migration records.
- Investigate current printer display, dispatch, and options for printers in
  other areas. Preserve the concurrent manuals session and all existing changes.
- Scope: repository/configuration investigation and documentation. No printer
  reconfiguration, physical print job, application behavior change, or deployment.
- Findings and validation will be recorded in a dedicated printer investigation.

### Printer Investigation Findings

- Owner confirmed the expansion is for USB DYMO label printers. Recorded the
  current shared asset/component widget, static env queue list, single CUPS
  endpoint, synchronous PDF/lp dispatch, independent bulk PDF path, and global
  media options in docs/plans/network-label-printers-2026-10-01.md.
- Read-only live CUPS HTTP pages on 10.10.10.249 returned HTTP 200. CUPS 2.4.10
  shows dymo330 idle/accepting/shared and dymo99010 paused/rejecting/shared with
  the printer absent. Both use USB URIs, queue-name descriptions, and blank
  locations. No print job, authentication, admin action, or remote change ran.
- Proposed one Linux USB/CUPS host per area with central IPP forwarding queues.
  Existing configuration can expose validated queues; shared discovery/status,
  friendly labels, approved queue validation, and per-printer media handling are
  separate application follow-ups. Added a link from the historical CUPS guide.
- Exact live app env/config and authenticated printer UI were not inspected.
  Physical driver/media/forwarding checks remain for a one-area pilot; choose
  printer models, rolls, locations, and available print hosts before implementation.
- No application tests or builds run because this block changes documentation
  only. Documentation link/whitespace checks recorded at session close.

### Olimex And DYMO Desktop Compatibility

- Owner has Olimex A10 boards and requires continued DYMO desktop software use.
  Updated the printer investigation with a conditional A10-LIME hardware/armhf
  driver assessment and a required pilot of both desktop designs and Snipe PDFs.
- Official Olimex, Debian, and historical DYMO manuals support investigating
  this path, but do not certify the unspecified board/image, desktop version/OS,
  or arbitrary CUPS shares. Exact board and client software information requested.
- No hardware image, package, printer/desktop setting, or runtime changed.
  Local doc links and scoped whitespace checks passed; physical dual-client
  compatibility remains untested. Tests/builds remain skipped for docs-only work.

- Owner clarified that the boards are A10-OLinuXino-LIME and the desktop users
  run standard DYMO Label software on Windows, probably recent. Updated the
  plan to use the confirmed board family; exact DYMO version/Windows release
  and maintained board image remain pilot inputs. No further setup ran.
- Vendor A10 release directory currently lists Bookworm minimal/base images
  built 2026-03-23; the minimal image is a pilot candidate. Debian Bookworm
  provides the legacy DYMO driver for armhf. Recorded official links; no image
  download, flashing, installation, or physical compatibility test performed.

- Investigated cardless A10-LIME testing: Ethernet cannot supply the initial
  bootloader. Network Linux boot requires U-Boot first; USB OTG FEL can load
  the boot stages into RAM without an SD card. Recorded primary U-Boot/sunxi
  sources and switch/direct-link tradeoffs in the printer investigation.
- No board access, flashing, USB driver, DHCP/TFTP/NFS service, or network change.

- Owner intends a common central CUPS server with a USB printer attached to an
  Olimex over Ethernet. Recorded both central IPP forwarding with local CUPS
  instances and a strictly single-CUPS alternative using a USB network relay.
- A relay keeps drivers/spooling central but needs physical DYMO USB/status and
  Windows-software validation. The future central server address is unspecified.
  No relay installation, configuration, network change, or physical job ran.

### A10 LIME And LabelWriter 400 Pilot Preparation

- Owner supplied C:\dev\a10 lime images, a 4 GB microSD card attached to this
  workstation, and a LabelWriter 400 physically connected to the A10-LIME.
  Board is not yet connected/booted. Owner requested OS flashing and setup as
  a USB node for CUPS on the production device.
- Windows identifies Disk 1 as Realtek PCIE Card Reader, 3,951,034,368 bytes,
  non-boot/non-system, with an almost-empty FAT32 D: partition. The historical
  3,980,394,496-byte images exceed this card. Preparing current vendor Bookworm
  minimal image; no card write or production mutation has run at this point.
- Raw disk writing requires Windows elevation; keep target checks and readback
  verification in the flash operation. Preserve all existing experiment images.

- Flashed the explicitly identified card with vendor Bookworm minimal build
  20260323-113959 (1,229,979,648 bytes); vendor MD5, MBR/boot header and full
  raw-card SHA-256 readback passed. Hash and guarded helper/status retained at
  C:\dev\a10 lime images\bookworm-print-node and recorded in the printer plan.
- Production read-only SSH preflight confirmed the regular LabelWriter 400
  driver and existing queue state. Inspected only CUPS_SERVER and LABEL_PRINTER
  /LABEL_PRINT_OPTIONS variables: dymo330 is the sole configured app queue and
  app jobs force w72h72 media with zero margins/scaling=100.
- Card ready for owner to insert and boot via LAN DHCP. Awaiting board IP/boot.
  No node service, central queue, app config, production service, DB, or print
  job changed. Application tests/builds skipped; physical relay tests pending.
- Verified the chosen minimal image package manifest contains SSH server,
  NetworkManager, systemd, udev, and usbutils; no CUPS or p910nd package listed.
  Card verified and ready. Physical insertion/power/LAN connection and a DHCP
  address are the next required owner step; no node configuration can run yet.
- Updated the investigation to distinguish earlier HTTP-only observations
  from the later runtime print-env check and completed card flashing.
  Scoped documentation whitespace checks pass.

### A10 Pilot Ethernet Discovery

- Owner connected the board/switch through this workstation's Ethernet port;
  board power/boot and switch LAN uplink are not yet confirmed.
- Read-only Windows network checks show Ethernet interface 11 Up at 1 Gbps,
  IPv4 169.254.160.192 with no gateway. Wi-Fi remains 10.10.10.245/24 with
  gateway 10.10.10.1. Ethernet counters show zero received packets, and an
  IPv6 local-link probe found no responding peer. These checks do not prove
  the board is off or that boot failed.
- Requested confirmation of the board's separate 5 V supply/LEDs and whether
  the switch has an uplink. No ICS, bridging, static address, DHCP service,
  firewall, production configuration, or further card write was performed.
- Follow-up after owner confirmed an isolated two-device switch and lit board
  Ethernet port: PC Ethernet remains Up with no discovered peer. All incoming
  Ethernet packet counters remain zero; IPv6 link-local probing had no reply.
  A lit PHY does not establish Linux boot. Requested an HDMI/keyboard or serial
  boot check before treating missing DHCP as the only problem. No network
  configuration, sharing, firewall, card, or production change was made.
- Owner has neither HDMI/keyboard nor serial diagnostics available. Enabled
  temporary Windows Internet Connection Sharing only from Wi-Fi to the isolated
  Ethernet interface after verifying no existing ICS configuration. Ethernet is
  now 192.168.137.1/24; Wi-Fi remains 10.10.10.245. The pre-change snapshot and
  guarded helper/status are retained outside the repo in bookworm-print-node.
- Requested an Olimex power cycle to test fresh boot/DHCP. No responding peer
  has appeared yet. Preparing a brief capture restricted to the Ethernet NIC;
  no production queue, relay service, app setting, or print job has changed.
- Temporary ICS is ready at 192.168.137.1/24; original Wi-Fi LAN address remains
  10.10.10.245. Added a guarded restore helper beside the setup/status snapshot.
  Network packet counters rose, but the 192.168.137.204 cache entry does not
  answer ping/SSH and is not a verified live board. A NIC-restricted 30-second
  capture subsequently contained only a PC multicast transmission.
- Awaiting the requested board power-cycle. Updated the printer plan with
  isolated-network status, temporary NAT limits, and restore instructions.
  Production configuration and physical printing remain unchanged.

### A10 Pilot Board Identity Check

- Owner found a power issue and reported boot. The peer at 192.168.137.204
  answered two pings and exposed an OpenSSH 9.2p1 / Debian 12 banner, then
  stopped responding before host-key retrieval or login completed.
- Owner may have connected a second board and explicitly requested validation
  first. Isolated 192.168.137.2-254 probing found no responding peer or new
  resolved MAC. A separate 30-second physical-Ethernet capture contained no
  received packets; evidence is retained outside Git in bookworm-print-node.
- Requested the board with the flashed 4 GB card, stable power, Ethernet, and
  DYMO USB connection. Hardware/image/printer identity remains unverified.
  No node login/configuration, relay, production queue, or print job completed.
  Updated docs/plans/network-label-printers-2026-10-01.md; application tests
  remain inapplicable because application behavior has not changed.
- Owner reconnected the other board. After more than two minutes, isolated
  subnet probing still found no responding peer and Ethernet receive counters
  stayed unchanged. SharedAccess is Running, Ethernet 192.168.137.1/24 is
  Preferred, and physical link is Up. Requested card seating, power source,
  and LED observations. Validation remains pending; no board or production
  printing configuration was changed.
- Owner reports no blinking and says the board is connected. Repeat checks
  still show Ethernet Up but unchanged receive counters and no ping response.
  Asked one focused follow-up to distinguish a steady board power LED from
  all board LEDs being off. No-blink observation alone does not establish boot.

### A10 DYMO Pilot Initialization And Print Tests

- Third board reached 192.168.137.204 and was validated through pinned SSH:
  A10-OLinuXino-LIME/Cortex-A8, Bookworm, kernel 5.10.180-olimex, 4 GB microSD.
  Attached DYMO is regular LabelWriter 400, USB 0922:0019, serial 07051501473265;
  usblp exposes a bidirectional /dev/usb/lp0 device.
- Configured workstation SSH key access, unique host key, locked/default-login
  cleanup, encrypted recovery credential outside Git, hostname, clock/timezone.
  Installed p910nd and configured a native lp-user relay with serial-based USB
  naming, startup/device binding, and a central-path-only nftables rule.
- Corrected a Windows CRLF transfer by switching to SCP for LF shell scripts.
  Removed only the malformed unit mask created by that failed transfer.
  Corrected the root-owned lock left by p910nd's version probe and updated
  helpers so their future use avoids those issues.
- Added temporary PC forwarding 10.10.10.245:19100 to node port 9100, restricted
  to production CUPS source 10.10.10.249. Existing port-631 forwarding preserved;
  guarded forwarding and ICS rollback helpers are retained outside Git.
- Production host TCP validation passed. Created only dymo400_olimex_pilot,
  using the regular 400 driver. Existing queues, app allowlist/config, and
  production app services remain unchanged.
- Small job 10 completed but owner reported blank output. Confirmed visible
  content in its rendered PDF and nonempty driver raster data. A standalone
  driver check without an effective network URI generated a large polling log;
  it finished and its log was reduced to a bounded 8 KiB diagnostic sample.
  Subsequent direct diagnostic was URI-correct, bounded, and did not print.
- Owner replaced the roll with S0929120 (25 x 25 mm). Set pilot default to
  30332 / w72h72.1 and sent one-page PDF job 11 after loading was confirmed.
  Physical result pending. No application tests/builds needed for this block;
  physical output, QR, Windows DYMO, and recovery validation remain outstanding.
- Full setup/status is recorded in docs/plans/network-label-printers-2026-10-01.md.
- Owner confirmed job 11 printed the test text correctly on S0929120. CUPS
  completed it and the queue is idle; central-CUPS-to-node-to-DYMO printing is
  physically validated. Initial blank-label cause remains unproven because
  roll/media/document path changed together before success.
- Initiated an idle-node reboot to verify automatic relay startup. Permanent
  LAN placement, actual QR and Windows DYMO tests remain pending; app queue
  allowlist/default is unchanged. Production dymo330 remains operational.
- Reboot recovery passed: node SSH, stable USB path, firewall and relay returned
  automatically; production host could reconnect through the existing pilot
  forward. Serial-checked usblp software unbind/rebind also passed: relay stopped
  when its device disappeared and restarted automatically after restoration.
  This does not establish physical cable reconnection or Windows DYMO behavior.
- Systemd unit verification and Windows forwarding/sharing restore-helper parser
  checks passed. Requested normal-LAN placement availability/preference; isolated
  test setup remains active pending that answer. No app allowlist/default change.
- Paused at the owner's request to continue next time. Successful S0929120
  text print and reboot/software USB recovery are verified. The pilot still
  depends on workstation ICS and port forwarding; permanent LAN placement,
  Snipe-IT queue exposure/QR checks, and Windows DYMO printing remain pending.
- Read-only Windows inspection found DYMO Label 8.7.4.48936 and the native
  LabelWriter 400 driver installed. Existing central CUPS connections use
  Microsoft's IPP driver. A legacy DYMO COM query crashed its helper process;
  adding a separate Windows pilot printer was rejected before execution with
  "blocked by policy". No Windows pilot connection or compatibility claim.

## Completed Remaining Guide Alignment Batch

- Owner requested all relevant guides. Delivered 19 new unaccepted revisions /
  66 A4 pages with 21 genuine local JPEGs and a 25-guide old/new review kit.
- Current source audited at mounted-worktree c539b9cd18ed; no app code, deploy
  or operator save. Cancelled protected-release preview; identifier reservation
  and recent-history side effects recorded. Result panel is recognition only.
- All new pages rendered/visually checked; focused artifact/hash/layout checks
  passed. Prior workflow PDFs and accepted selections preserved. App tests not
  run for artifact-only edits; operational/user/physical-print trials pending.
- See docs/manuals/operator-guides/reviews/aligned-guides-2026-10-01.md.
