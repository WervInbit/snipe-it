# Manual Session Notes - 2026-10-08

This scoped committed view preserves the manual sections. Other
application/printer session notes remain in the shared working file
for their owning task and are not published by the manual commits.

## Manual Rules Clarity Session

- Resumed manuals here after checkpoint a766abedee; the owner reports no
  intervening work in other locations. Preserve the separate printer work.
- Review the existing rule set and clarify recognition of the screenshot's
  location, focus marks among multiple visible items and proportionate STOP
  wording/placement. Continue the general authoring-method repair.
- Read current standards, method, owner decisions and review/handoff records.
  Scope is documentation; retained PDFs and frozen renderer inputs stay intact.
- Updated the shared contracts and a concise
  [reader criteria entry point](../manuals/operator-guides/authoring-method.md#reader-review-criteria).
  Focus marks resolve selection among visible items; screenshot landmarks
  establish location; warnings distinguish routine work from conditional risk.
- Saved the ambiguity/boundary findings in the
  [rules clarity review](../manuals/operator-guides/reviews/authoring-method-repair-2026-10-06.md#rules-clarity-review-on-2026-10-08)
  and updated the manual handoff. Documentation checks passed; no new PDF,
  application change, reader trial or acceptance decision in this block.
- Follow-up advice distinguishes recorded guide defects, necessary functional
  updates and targeted screenshot/warning reviews. Apply the method across
  guide types, retain useful family appearance, and integrate new choices
  before completion rather than appending a generic procedure.
- Recommendation: small functional revision batches with complete-guide
  baseline comparison, A4 target checks, reader trials and one old/new change
  list. Unchanged tasks need no wholesale rewrite; inspect affected targets
  and warning conditions. No new guide artifacts or acceptance in this review.

### 2026-10-08 - Dev/Production QR And Future CUPS Topology

- Owner expects the current production-attached printing setup to move later;
  both app environments must reach common network CUPS. Olimex nodes remain
  USB relays, central queue names remain stable, and central-server migration
  remains the company server team's work. No migration or configuration change.
- Read-only running-app comparison: LF-normalized template/QrCodeService/
  QrLabelService hashes match; square template and relevant label settings match.
  Production uses dymo330 with zero margins/scaling=100; pilot helper uses 400
  driver/w72h72.1/fit-to-page. Test payload differs from ordinary asset tags.
  These explain pattern/scale differences without claiming measured print parity.
- Documented findings and the current single-CUPS-endpoint application limit in
  docs/agents/printer-development-handoff-2026-10-06.md. No prints, database
  writes, application edits, deployment or Git commit/push. Tests not needed
  for read-only runtime/source/configuration inspection.

## Manuals: Three Reauthored Test Examples - 2026-10-08

- Produced AC-01 v11 (one side), SC-01 v14 (one side) and AST-05 v8 (two
  continuous sides) as Working drafts, not accepted. Kept all historical
  artifacts and accepted/runtime selections unchanged.
- Applied the repaired method: task/decision structure before evidence and
  fitting; familiar useful support retained, Dutch account captures, measured
  focus/target scale, conditional recovery and verifiable completion.
- AC stays login-only with larger useful controls. SC groups scanning OR
  manual lookup and converges on one identity check, including multiple-match
  recovery. AST carries steps 1-6 through clean confirmation and saved status/
  note, with physical/model-assumption checks and separate return/override.
- Four A4 pages rendered at 144 dpi and visually inspected; page/text/scope,
  components, source/crop/focus bounds, old/new hashes and script syntax checked.
  Shared component check passed (25 registry names / five-reference capacity).
  Accepted package integrity passed: 16 selections, seven historical PDFs,
  369 files. Old hash-locked renderer inputs are unchanged. PHP application
  tests were not needed because no application code was changed.
- Evidence uses the existing dev mount at c539b9cd18, with five relevant file
  fingerprints. Changed only the two demo capture accounts from en-US to
  nl-NL; global Dutch was already set. Created only owned synthetic asset 17
  GUIDE-20261008-01 with demonstration runs 17-19. Actually confirmed its clean
  QA Hold -> Ready for Sale change, reopened status and verified the saved
  note in Statusgeschiedenis. No physical test claims or existing-asset edits.
- Remaining product/evidence gaps: untranslated workflow/component/dialog text
  despite Dutch settings and mobile status-history overflow. The guide supplies
  a larger-screen fallback. Reader trials, physical A4, return/override trials
  and exact acceptance remain pending. No deploy, push or commit was performed.
- Review and change list: docs/manuals/operator-guides/reviews/reauthoring-test-2026-10-08.md.
  Retained sources, PDFs, HTML/SVG, comparison, metadata and hash manifest:
  resources/manuals/operator-guides/review-rounds/2026-10-08-test-guides/.
  Concurrent printer/Olimex source, logs and runtime work were preserved.

### Twin right-roll outcome

- Owner confirms left light blinking and suspects a left-roll issue. At their
  explicit request, sent the existing CUPS-TEST PDF through dev to Twin right
  (job23); owner confirms right prints. USB status changed from 0x30 on the
  selected left roll to 0x18 after right output. This is selected-roll status,
  not proof that both rolls are ready. No automatic paper-status pause added.
- Both Twin queues remain registered/available alongside the 400, with their
  fixed roll selections. Left physical output remains unresolved. Right stock
  number and a layout fitting each thin sticker remain open; the square test
  can span the two stickers. No further print or card/image change.
- An optional offline filter probe failed: cupsfilter was outside the SSH PATH,
  and then could not read the protected PPD as the ordinary SSH user. It did
  not send print data or modify the queue. Physical right output subsequently
  validated the actual driver/relay route; no offline success is claimed.

### Owner-requested 400 test after Twin attachment

- Sent one existing 25 mm CUPS-TEST PDF through the dev print service to
  dymo400_olimex_pilot (job24), at the owner's request. No layout/source/roll
  setting change. CUPS reports completed; physical output/clipping feedback
  remains pending. All enrolled queues are online with no outstanding jobs.

### 400 output and reported Twin-left fault

- Owner confirms 400 job24 printed on the shared node, with QR clipping still
  present. Sent existing position-only calibration PDF to 400 as job25 (same
  QR size, down 1 mm); CUPS completed it, physical feedback remains pending.
- Owner reports Twin left is most likely physically damaged. Disabled and
  rejected its central queue with an explicit fault reason, and removed only
  left from the unsaved dev environment list. Queue remains discoverable for
  later repair/re-enabling. Central default remains dymo330; dev choices are
  dymo330, dymo400_olimex_pilot and dymo_twin_right.
- Collector readback preserves left manual-pause and right online. Added a
  focused test preserving a manual left pause through sibling outage/recovery;
  all 31 Python tests pass. Deployed collector hash matches the repository.
- Sequential physical output from both the 400 and Twin right is now owner-
  confirmed. Simultaneous physical output, right individual-sticker layout and
  400/330 dimensional parity remain open. No saved QR template or image changed.

### Photo review of 400 calibration job 25

- Owner identifies the attached 1000023984.jpg as the last print, job25. The
  photo shows all three QR finder squares and the visible matrix boundaries;
  no definite missing QR rows are apparent. Caption is close to the bottom
  edge; physical layout/scanner acceptance is still open.
- Rendered and inspected both existing one-page 25 mm PDFs with Poppler.
  Original QR box: x3.2..22.2/y1.8..20.8 mm; shifted: same 19 mm box at
  y2.8..21.8 mm. Caption text bounds move from y21.113..22.524 to
  y22.113..23.524 mm. No QR resizing occurred; no new PDF was authored.
- A read-only central CUPS raster check with current queue media defaults and
  current fixed-scale/zero-offset options is byte-identical to the earlier
  explicit-media original raster (sha256 35e2325913a03f5afbc12a3446b9ea41b14cf21dd432278730b4dfa809f2c9ca).
  Removing the global paper override has not changed that raster. An optional
  shifted-raster check found the PDF absent on the central host; no success
  claimed for that check. No physical print or saved-layout change this block.
- Source photo remains in its local .codex-remote-attachments location. Private
  PDF previews remain with protected calibration artifacts. Physical dimensions
  were not inferred as ruler measurements from the perspective photo.

### Owner-requested paired shifted-label comparison

- Printed the same existing shifted PDF from dev to LAN330 (job26) and Wi-Fi400
  (job27), one label each at fixed scale. Shared PDF sha256
  1b95d6dfc3ff350d278e0cfac78c4ec0f0414415f745d66144f8832bc5a4388d;
  25 mm page, unchanged 19 mm QR box, 1 mm downward shift.
- Both submissions succeeded and CUPS reports completed, with no pending jobs.
  Owner physical comparison is pending. No new PDF/template, queue configuration
  or image change; no Twin job sent.

### 400 clipping investigation and Twin square-stock comparison

- Owner confirms Twin-right job28 looks fine, like the 330, using the identical
  shifted 25 mm PDF after swapping right to the same square stock. Changed only
  its central paper default to w72h72.1 and its description to square stock.
  Twin left remains manually disabled/rejecting; no SD-image update is needed.
- Owner clarifies the 400 comparison loses a thin strip at the top. Earlier
  coarse photo review of complete finder squares did not prove complete edge
  rows. Hardware specifications confirm 330 Turbo and 400 are both 300 DPI,
  maximum print width 56 mm; width capacity does not explain the small QR crop.
- Completed previously unavailable shifted offline raster check: both model
  rasters byte-identical, 271 x 252 RGB, SHA256
  2d2bb7b02b226b28ddec0126037dd6070529fecabf2e88690e972d66df90ffe3.
  Both final driver streams have identical pixel commands; only the expected
  final form-feed commands differ. No printer backend used for those probes.
- Manufacturer documentation suggests Graphics mode improves dot placement.
  Sent one controlled 400 job29 via dev with only that per-job override. Offline
  command comparison differs in exactly one byte (ESC h -> ESC i). Owner says
  still clipped; mode alone did not fix it. Persistent defaults/templates unchanged.
- Requested manual 400 roll reload and one-label feed check before repeating the
  same test. Feed/registration/mechanical reproduction remain possibilities;
  no hardware damage or measured physical parity is established. Digital checks
  do not replace physical acceptance. Source citations and current stock are in
  docs/print-node-deployment.md; printer-settings doc reconciled accordingly.
- No application/driver code or image change. Existing 31 Python tests remain
  the latest code checks; no new test suite required for this diagnostic block.
  Protected scripts, PDFs, raw diagnostic output and driver sources stay outside
  Git. No commit, push or production application deployment.

### Clipping persists independently of QR orientation

- Owner has reseated the labels and reports more downward placement does not
  remove clipping. Recorded actual IPP options for jobs26..29: identical fixed
  scale/zero additional margins; job29 correctly records Graphics.
- Pixel inspection confirms the PDF and raster contain the full ten-row finder
  top border. Decoding the final DYMO command stream verifies all ten rows match
  the raster exactly, with no horizontal shift. No digital edge loss found.
- Authored and visually inspected a private diagnostic rotating only the QR by
  180 degrees within unchanged bounds, preserving embedded image bytes/caption.
  Printed once each through dev: 330 job30, 400 job31, Twin-right job32.
  Owner confirms 400 still loses the TOP strip: loss did not follow the artwork.
  Owner did not explicitly confirm the other rotated prints. No saved layout changed.
- Queried idle 400 model/firmware through its bidirectional relay using ESC V;
  reply 93089vAF. No print/reset/firmware update was involved in that query.
- Authored and visually inspected one further private diagnostic adding a thin
  vertical line beside the rotated QR, starting before its visible ink. Sent
  only to 400 as job33; physical feedback pending. This isolates possible first-
  printed-row reproduction without another whole-layout move or QR shrink.
  PDF hashes, geometry and current outcomes are in docs/print-node-deployment.md.
- Calibration remains open. No permanent driver/app/image change, tests or commit.

### Photo confirms partial first-row loss; uncompressed comparison

- Reviewed owner photo1000023986.jpg of side-line job33. Thin top-left strokes
  persist; top-right finder starts higher and there is blank stock above.
  The defect is not explained by the complete QR crossing the sticker edge.
- Offline filtered the same diagnostic through current 400 PPD and driver.
  Expanded 212 compressed lines into documented uncompressed SYN data, then
  decoded both streams to prove all pixel/command events equal. Sent this one
  7474-byte stream from dev through central CUPS as raw job34. Physical feedback
  pending; no permanent filter/driver/runtime/image change.
- This is a controlled printer-protocol diagnostic, not a new Snipe-IT print
  feature or a label-layout workaround. Private inputs, decoded output, scripts
  and photos remain outside Git. Documentation captures checksums and limitations.

### Uncompressed output still defective; independent self-test requested

- Owner confirms raw uncompressed job34 remains clipped/thin. Compression
  removal does not resolve the defect. No persistent workaround was adopted.
- Requested the manufacturer's built-in 400 self-test (hold feed about ten
  seconds; stop with another press/release after two or three labels). It
  bypasses app/CUPS/relay; physical result pending. A clean result would require
  further host-command/USB/firmware investigation rather than declaring the
  printer damaged; an imperfect result would support maintenance diagnosis.
- No additional app or image work; current queue defaults, QR sizes and
  operator-stock responsibilities remain unchanged. Calibration is still open.

### 2026-10-08 - 400 self-test outcome and row-command investigation

- Owner reports the internal test prints varied thin/thick lines, squares and
  full blocks covering the area lost in QR output. This makes a dead section
  of the print head less likely; it does not establish uniform physical parity.
- Inspected driver start/row commands and manufacturer's protocol. Sent one
  fixed-width raw diagnostic from dev to the 400 as job35: same pixels as job34,
  but constant dot tab zero and 84-byte head rows instead of changing leading
  offsets/data lengths. Blank-row skip commands are retained.
- Round-trip decoding verifies identical head pixels, all 247 row positions,
  initial commands and final feed sequence. Physical edge feedback and explicit
  confirmation that self-test has stopped are pending. Do not send more tests
  while self-test is active; preceding instructions told the owner to stop it.
- Updated deployment notes and corrected fork notes for Twin-right's current
  square stock. No new PDF, image, application/driver code, queue default,
  package, database or production application deployment. No commit or push.

### 2026-10-08 - Explicit-reset probe and local USB 400 Turbo comparison

- Owner confirms job35 still clipped and self-test manually stopped. Sent
  network-400 job36 with only ESC @ added after synchronization, before current
  row-mode/label commands. Exact other-byte equality checked; physical feedback
  pending. No persistent driver/default/firmware change.
- Owner connected 400 Turbo to this Windows PC. Verified present USB VID/PID
  0922:001a, serial05053114095777, local DYMO driver and USB001. Both Olimex
  inventories are unchanged. Absent central dymo99010 route remains disabled.
- Submitted one Windows-driver comparison from the existing shifted PDF:
  600-DPI rendered page drawn at physical25mm, square30332 form, printer300DPI,
  device-offset compensation, one copy. Owner reports caption too low/half
  outside sticker. QR-top completeness and identical stock confirmation pending.
- File-only Windows-driver capture succeeded without another physical label.
  Decoded native and CUPS output both preserve all ten top-finder rows and y12..246
  ink bounds. Native explicitly sets300DPI, uses600-dot label search maximum and
  ordinary final FF; CUPS leaves resolution implicit, uses300 and G+E feeds.
  These are candidates to investigate, not proof of a driver or hardware fault.
- No new PDF or image authored, no application/relay/driver code or database
  change, no permanent Windows/CUPS defaults saved. Private captures and helper
  scripts remain protected and outside Git. No commit, push or app deployment.

### Native driver mark-search distance comparison

- Native Windows capture contains explicit ESC y300 mode and a600-dot label
  search maximum; CUPS output uses300. Manufacturer defines this value as the
  maximum mark-search distance, normally longer than actual feed pitch, not
  simply white-label height. The stock pitch is not measured; this is a testable
  lead, not a diagnosed fault.
- Sent network400 job37 from dev: same upright shifted QR as the original pair,
  no side line, with only the two label-search parameter bytes changed300->600.
  All image data, mode/offset/reset bytes and final G+E commands preserved.
  Physical feedback pending. No queue paper/driver/default or source change.
- Windows caption remains too low. Requested confirmation whether QR top is
  complete and whether its loaded stock matches the25mm square labels before
  changing that local comparison's placement. No further Windows label sent.
- Native capture was file-only. Inspected local controller's actual output-port
  value before capture to ensure the private filename was selected instead of
  USB/FILE prompt. Captured4326 bytes and decoded all247 rows successfully.

### Owner-requested local USB reprint

- Re-submitted one CUPS-TEST comparison to the verified USB400Turbo on this
  Windows PC, using the same private Windows-driver helper and settings.
  Same shifted25mm PDF,19mm QR box, square30332 paper and300DPI; no placement
  change. Submission succeeded; physical result remains for owner assessment.
- No network print, source/template/default/image change, commit or push.

### Local USB 400 Turbo test acknowledged and disconnected

- Owner confirms the unchanged local reprint works and has disconnected the
  USB400Turbo from this Windows PC. Recorded owner-confirmed local printing;
  did not infer exact dimensions, scanner acceptance or explicit reconciliation
  of the earlier low-caption report. No further print requested/sent.
- Network400 clipping remains unresolved; jobs36/37 physical feedback is still
  unreported. Local driver/central queues unchanged; no new enrollment, image,
  application/source change, commit or push.

### Original 400 calibration investigation

- Owner confirms job37's longer mark-search distance did not fix clipping;
  job36 still lacks physical feedback. DYMO400 documentation describes automatic
  optical/motor-step registration and a self-test, but no user sensor calibration
  was found. Do not confuse self-test or parameter reset with calibration.
- Owner prefers continued Olimex tests. Captured the original400 native Windows
  driver to a verified private file port, without touching USB or its old stale
  Windows job. Decoded247 rows, including all ten top finder rows. Submitted once
  through the existing network route as raw job38; owner confirms still clipped.
  Native rasterization differs slightly from CUPS; no root cause claimed.
- Documented remaining feed/contact, command-sequence and maintenance leads in
  docs/print-node-deployment.md. No new PDF/image, persistent filter/queue/driver,
  firmware, application/database change, local physical print, commit or push.
  Private capture and serial-guarded comparison helpers remain outside Git.
- Sent the exact native stream directly to the400 USB device on Olimex with
  identity/hash/idle checks, brief queue hold and relay-only suspension. Bounded
  write succeeded; cleanup resumed relay and restored accepting/enabled queue.
  No Twin interruption or job cancellation; owner confirms direct USB still
  clips/thins the top. Owner then stopped the broader investigation.

### Owner-requested solid black 400 square

- Sent only requested job39: filled211-dot square /17.865 mm in the earlier
  upright QR's visible ink area, no caption. Normal density, explicit300DPI,
  fixed-width uncompressed rows, normal feed. Pixel/hash and fresh serial/idle
  preflight passed. Physical result pending; no additional diagnostic print.
- Private raw stream/helper remain outside Git; no PDF/image, permanent default,
  firmware, application/database, commit or push. Evidence is in deployment notes.

### 2026-10-08 - Full printable black area requested

- Owner corrected the square test: fill the maximum current printable area,
  rather than QR dimensions. Verified the400 PPD square-stock imageable bounds
  4.08/4.32/69.12/64.80 points and300DPI. Sent one raw job40, filling the complete
  271 x252-dot raster (22.945 x21.336 mm), from its first row and column. All68,292
  black pixels checked; no caption. SHA256
  5bce9e170bdcaeefc68f7cb91db75ae78d557d564d88ddf09a98e85403c0865e.
- Original400 serial, fresh collector and idle preflight passed. Physical result
  pending; no additional print or persistent settings/application/image change.

### 2026-10-08 - Full-fill photo result

- Owner supplied1000023987.jpg after full-area job40. Visible black field is
  continuous with no obvious unprinted vertical stripe or internal white band.
  It lies close to the upper label edge with substantially more white below.
  Lighting/reflection prevents a reliable density assessment from this photo.
- This supports printing a continuous field, but does not establish that all252
  rows advanced distinctly or that the expected21.336 mm height was reproduced.
  Uniform black can hide merged/lost initial rows. No measured geometry, feed
  calibration, root cause or accepted400 profile established. A numbered row/grid
  pattern would distinguish missing/compressed rows from placement; no new print
  sent or permanent settings changed in response to the photo.

### 2026-10-08 - Adjacent fill and QR photo

- Reviewed1000023988.jpg: caption is at photo-right, so logical QR top is at
  photo-left. The full fill appears to reach closer to that label edge than the
  QR's thin leading strokes. This argues against a fixed non-printable band
  accounting for the QR defect. It does not establish exact dimensions/registration
  between separate labels. Loss/overlap during initial printed-row advancement
  remains a useful hypothesis; a uniform field would hide such overlap. No
  confirmed root cause, further print or permanent setting change.

### 2026-10-08 - Proposed QR and price-printer role swap

- Owner is considering moving QR work to Twin Turbo right and price work to400,
  using price stickers described as the square stock split into two narrow
  stickers. Likely candidate is11353 /S0722530 two-up multi-purpose stock,
  nominal13 x25 mm per sticker in DYMO's official rolls datasheet. Exact loaded
  stock identity and split orientation remain unconfirmed.
- Read-only central PPD check: both400 and Twin-right drivers include11353
  w72h72 and30333 (2 up) w72h72.2 profiles, paired72 x72-point page. Both queues
  currently retain square w72h72.1. A two-up page/profile does not automatically
  position separate price content on each half; layout must respect the split.
- No role/default/profile/image changes or print sent.400 defect remains
  unresolved; price layout requires a physical sample before acceptance. Source:
  https://download.dymo.com/dymo/technical-data-sheets/230305_DYMO_LW_Rolls_Technical_Data_Sheet_v7.pdf

### 2026-10-08 - Windows DYMO software network setup advice

- Read-only inspection confirms workstation DYMO Label8.7.4 and native version3
  drivers for required models. Current auto-discovered central Windows entries
  use Microsoft IPP Class Driver. Central330/400/Twin-right queues are shared,
  accepting and advertise raw/octet-stream input. Node TCP relays remain central-only.
- Documented manual Windows shared-printer URL setup with matching DYMO drivers
  in docs/label-printer-settings.md. Twin native streams must select Right in the
  client; CUPS right-queue naming/default cannot enforce roll in bypassed raw data.
- No printer installation, default/server/firewall/image change or test print.
  Windows IPP native-driver selection and DYMO app/physical validation remain open.

### 2026-10-08 - DYMO-listed clients have no network routes

- Owner sees five choices in DYMO but none prints. Windows inventory and DYMO
  COM GetDymoPrinters confirm native entries use disconnected USB001/002/003 or
  PORTPROMPT; generic central Microsoft IPP entries are not enumerated by DYMO.
  No new native HTTP/CUPS entry exists. Old failed400 USB job fromOct1 preserved.
  Central330/400/Twin-right and relay collector remain online; left remains paused.
- Attempted creation of separate DYMO400(CUPS) via native-driver PrintUI HTTP
  installation. Automatic approval review rejected the command before execution,
  returning only blocked by policy. No installation or print occurred. Agent
  token is also non-administrator; no claim of a Windows access-denied result.
- Prepared protected local install-windows-cups-dymo.ps1 for user review/manual
  administrator execution, with installed-driver checks, distinct names, URL
  validation and post-install verification, no print or old-entry modifications.
  Syntax and preview-only mode checked for all three queues. Actual installation,
  native IPP routing and physical DYMO-app output still unverified. Updated
  docs/label-printer-settings.md; no server/firewall/image/app/database or commit.

### 2026-10-08 - Second laptop400 connection advice

- Owner asks how to connect400 from another laptop after moving USB slots.
  Read-only fresh central inventory confirms original serial07051501473265 still
  on Wi-Fi Olimex, lp1/port9100; collector online and CUPS accepting/idle. Serial
  addressing preserves the route across USB-port changes on that same node.
- Provide Windows laptop setup: install DYMO Label8.7.4/native400 driver, add
  shared printer by central HTTP URL and model-specific driver, then choose the
  new network entry and loaded stock in DYMO.400 stays USB-connected to Olimex
  for this route. No change, installation or print on either laptop/node/server.

### 2026-10-08 - Windows Network discovery exposure advice

- Read-only central check: CUPS631 and Avahi active, Browsing Yes with dnssd.
  Samba/smbd and wsdd/wsdd2 inactive, no SMB listener. Current sharing supports
  IPP advertisements but not an active Windows Explorer SMB printer-share path.
- For requested browse/open/connect flow, propose central Samba CUPS printer
  shares plus WS-Discovery host advertisement and correctly associated native
  DYMO Windows drivers. WSD makes the server appear in Explorer Network; it does
  not itself install drivers or advertise each printer as a standalone WSD
  printer. Driver association/first-install policy and physical printing still
  need validation. Proposed UNC path is not currently enabled.
- Existing central-only node relay firewall and images can remain unchanged;
  this exposure belongs on central print server. Discovery across VLANs requires
  appropriate network support, not only enabling sharing. No package, service,
  firewall, DNS, printer/default or image modification and no print performed.

### 2026-10-08 - Central Windows discovery and moved330 connectivity

- Owner explicitly authorized central Windows discovery and requested no login.
  Installed Debian Samba4.22.11/wsdd2 1.8.7/liburing2 without recommends; no
  domain controller/winbind. Backed up original Samba/persistent firewall files
  under root-only /root/print-sharing-backup-20261008 before configuration.
- Enabled boot-persistent LAN-bound INBIT-PRINT WSD/LLMNR and three anonymous,
  printer-only SMB2/3 shares:330,400,Twin-right. Dedicated printguest is a locked
  non-shell system identity. No home/file/driver download exports; Twin-left
  and absent99010 excluded. Existing node raw ports remain central-only.
- Added tagged, LAN-scoped TCP445/3702/5355, UDP3702/5355/5353 and multicast IGMP
  INPUT rules to live iptables and its existing persistent source. Docker live
  rules were not flushed or captured. Samba testparm/firewall syntax passed;
  smbd/wsdd2/CUPS/collector active and smbd/wsdd2 enabled for boot.
- Windows read-only anonymous NetShareEnum returned the3 printer shares;
  unicast/multicast WSD discovery and HTTP200 metadata passed. INBIT-PRINT
  resolves via LLMNR to10.10.10.249. Native Windows driver association/download
  is not configured; client driver choice/installation and printing remain
  pending. Existing PC guest/signing settings were read, not changed.
- Initial load-printers=no caused Samba printer_list.tdb diagnostics. Enabled
  printcap loading with no catch-all printers section; cache created and exact
  three-share allowlist retained. Read-only Windows OpenPrinter330 returned1801
  after a long wait; diagnostic process stopped. No physical print submitted.
- Owner moved330 to final location and tried another switch port. Old27 address
  times out, DNS still27, and bounded SSH/ARP checks did not identify another
  Olimex; collector safely pauses330 as unknown. Owner confirms power and link
  LEDs, power-cycle/boot/DHCP check pending.400/Twin-right stay online; left fault
  pause/rejection preserved. No queue resume, reroute or replay attempted.
- Owner reports current-PC discovery but Windows10 missing and an unspecified
  duplicate. Requested Windows10 direct UNC result and duplicate names/view;
  local inventory still contains old generic IPP/USB/file-prompt entries.
  No printer deletion or advertisement suppression before identification.
- Updated deployment/client docs, README, AGENTS, SECURITY and fork notes.
  No app/image/node/database change, Windows installation, commit or push.
  PHP tests skipped because only central services/firewall and docs changed;
  remaining risks are client driver/RPC compatibility and330 physical network.

- Windows follow-up:400 OpenPrinter still returned1801 after fixing Samba's
  printcap cache. Config exports saved in the protected local deployment folder;
  no client installation or print. Windows10 cannot open the UNC server; exact
  error plus Test-NetConnection445/source address requested before changing
  scope or client policy. Duplicate names/view remain unknown. Owner cannot
  power-cycle330 now; it remains safely paused pending physical boot/network work.

- Owner identified the Windows10 laptop was not on the network; the failed UNC
  attempt is not evidence of a server compatibility failure. Retry browsing
  INBIT-PRINT/current central IP after joining the company LAN. Exact duplicate
  names and post-connection client setup/printing validation remain pending.

### 2026-10-08 - Model names and three published DYMO printers

- Owner can now see printers in Windows search and requested model names plus
  3-4 visible entries. Set CUPS descriptions and Samba share names to DYMO
  LabelWriter 330 Turbo, DYMO LabelWriter 400 and DYMO LabelWriter Twin Turbo
  (Right). Unpublished absent99010 and faulty Twin-left in CUPS; retained queues
  and fault pauses. No old-name SMB aliases that would add duplicate entries.
- Stable CUPS queue IDs, IPP URLs, device URIs, drivers/media, central default
  and Snipe-IT selections preserved. Existing SMB installations with old queue
  names need updated paths; existing Windows cached entries are not deleted.
- Saved pre-naming configs under root-only backup/naming. Samba configuration
  passes testparm; restarted smbd to clear stale RPC share references, then
  anonymous Windows NetShareEnum and Samba RPC list exactly3 model-named
  printer shares. IPP attributes showed old DNS-SD cache despite updated Info;
  with no pending jobs, restarted CUPS and started collector again. New DNS-SD
  names verified; left/99010 unshared and no DNS-SD names. All services active.
- No print, node/image/app/database change, Windows install/delete, commit or
  push.330 remains safely collector-paused because node is unreachable;
  400/Twin-right remain idle. Updated deployment/client docs, README, AGENTS and
  fork notes. PHP tests not applicable to central naming/publishing changes.

### 2026-10-08 - Missing400 in Windows10 search

- Owner reports400 absent in Windows10 laptop Add Printer search. Read-only
  central check confirms400 shared/idle, collector online and correct serial-
  addressed relay route. Anonymous SMB and RPC enumerate its model-named share.
- Queried actual LAN mDNS from this PC using standard-library Python; received
  three central IPP PTR announcements with exact330/400/Twin-right model names.
  The400 announcement is present on the wire, not only in CUPS configuration.
- Laptop-side share listing and any pre-existing local400 entry requested to
  distinguish search behavior/cache/installation from missing publication.
  Root cause not established. No server/client/image settings change, print,
  printer deletion, test suite run, commit or push in this diagnostic block.

- Owner subsequently confirms400 is visible in Windows10 and has been added;
  no pre-existing local400 entry reported. It does not appear in DYMO software.
  Laptop Driver field requested to check generic Microsoft IPP versus native
  DYMO400 binding. The generic-driver explanation is a hypothesis based on this
  workstation's earlier COM enumeration, not a verified laptop driver result.
  Manual native-driver CUPS URL setup is documented; no laptop access or driver
  installation performed by the agent. Search no longer reported missing400.

- Owner confirms DYMO Label v8 on the Windows10 laptop and sees only printer
  search rather than a manual-add option. Clarified Windows versus DYMO setup;
  provide the Microsoft-documented PrintUIEntry /il Windows wizard launch, then
  manual shared HTTP queue URL and native400 driver selection. Driver field
  still not supplied; no confirmed laptop binding/root cause or physical print.

### 2026-10-08 - Current PC has no installed network400

- Owner also cannot find400 in DYMO on this shared PC. Fresh Get-Printer shows
  native400 only on USB002; there is no installed network400 entry. Native
  version3 DYMO400 driver is present. Live32-bit DYMO SDK lists only old
  native USB/file-prompt queues. Open Netwerkprinter toevoegen process is
  DymoPrinterWizard, confirming it is the DYMO wizard rather than Windows UI.
- Central400 remains shared/idle/online; HTTP200 and anonymous SMB model share
  verified. Re-ran protected installer400-only preview successfully; it plans
  DYMO400(CUPS) with native400 driver and the correct central URL, with no
  changes or print. Agent token is non-administrator and earlier blocked
  installation is not retried or bypassed. Operator-run admin helper required.
- Updated client troubleshooting docs. Laptop installed-entry driver remains
  unverified. No client/server/image/app/database mutation, print, commit/push
  or test suite run in this diagnostic block.

### 2026-10-08 - Easier Windows client rollout recommendation

- Owner asks for an easier installation for other staff. Recommend a single
  company Windows setup package (or IT-managed rollout) that installs/checks
  official native DYMO drivers and creates named native-driver IPP connections
  for330/400/Twin-right. Staff should not enter URLs, choose drivers or run
  PowerShell; existing private helper remains a diagnostic prototype.
- A stable central DNS endpoint and configuration-driven queue mappings are
  deployment requirements; current INBIT-PRINT is only WSD/LLMNR advertisement,
  not a newly configured company DNS alias. No image changes needed.
- Server-hosted Point and Print driver publication is another possible route,
  but not implemented/validated here. Modern Windows driver elevation and
  guest-SMB/RPC restrictions mean publishing drivers alone cannot promise
  passwordless/admin-free installation. Preserve Windows protections; IT can
  deploy with system privileges instead of requiring staff credentials.
- First validate one native-driver client in DYMO v8 and a physical print, then
  package the proven setup. No installer build, driver redistribution/server
  change, client install, print, commit or push performed for this advice.
  Sources: Microsoft Point and Print KB5005652; Microsoft SMB signing behavior.

- Clarified that DYMO Labelv8 supports Windows-installed shared network printers
  (official DYMO user guide); current discovery does not complete native-driver
  client setup. This PC still has only USB002 native400 and no network400.
  Earlier SDK checks omit generic central IPP queues. Laptop driver association
  remains unknown. No claim that CUPS cannot be used or that native IPP/DYMO
  end-to-end setup has passed; no configuration change or print.

### 2026-10-08 - Central Windows DYMO driver publication (started)

- Owner authorized native Windows driver publication on the central Samba/CUPS
  server and accepts local administrator approval for client installation.
- Current printer shares have no assigned Windows driver. Preparing protected
  staging, server backups and model mapping for the current three public shares;
  preserve guest printing, existing CUPS routing and fault pauses.
- Client installation and physical DYMO Label v8 printing will require separate
  validation; no claim of success from driver registration alone.

### 2026-10-08 - Central Windows DYMO driver publication (server setup completed)

- Registered version3 Windows x64 native 330 Turbo-USB, 400, 400 Turbo and Twin
  Turbo drivers on central Samba; bound the three public model-named shares.
  Installed vendor INF is 8.5.0.542/2013-02-25 from the existing DYMO Label 8.7.4
  workstation package. Vendor catalog signature valid; original INF/catalog
  package retained for manual fallback. Microsoft Unidrv dependencies came from
  this workstation; cross-client OS installation remains to be qualified.
- Added hidden guest-readable, read-only print$; files root-owned with no group/
  other write permission. Switched registered shares to use-client-driver=no.
  Temporary non-shell staging identity, SePrintOperatorPrivilege and root-only
  random-password authentication file were removed after successful registration.
- Backed up smb.conf and locked Samba TDB snapshots to root-only
  /root/print-sharing-backup-20261008/windows-drivers-150737. Private export,
  manifest and one-time publication scripts remain outside Git. No vendor/Microsoft
  binaries in repository; docs include rollback and future queue binding.
- Verified anonymous four-model enumeration, three correct driver bindings, all 20
  Windows SMB file hashes, original INF/catalog reads, explicit use-access opens
  for all shares, and denial of printer administration/driver-directory writes.
  testparm and active/enabled services passed; no pending CUPS jobs or physical
  print. Default rpcclient getprinter now requests admin access and is denied;
  explicit use access, enumeration and driver metadata retrieval succeed.
- Guest printing, CUPS IDs/routes/media/default, Snipe-IT configuration, node raw
  port scope and fault pauses preserved. 330 remains unreachable/paused; 400 and
  Twin-right idle. Twin-left/absent 99010 stay unpublished. x86/ARM64 not registered.
- Updated deployment, client settings, AGENTS, README, SECURITY and fork notes.
  Windows add-printer/admin approval, native DYMO Label v8 enumeration and a
  physical label are still pending operator testing. Earlier blocked client
  installation not retried; no Windows policy/printer changes, app/database/image
  changes, commit or push. PHP tests not applicable to server driver publication.

### 2026-10-08 - Windows client connection still fails after driver publication

- Owner reports INBIT-PRINT cannot open and manual setup does not expose the
  printer in DYMO, on both Windows10 laptop and this workstation. Asked for
  laptop direct-IP browsing outcome/exact error; response pending.
- Reproduced workstation hostname failure: .NET resolver reports unknown host,
  named UNC driver read fails, while IP UNC driver read succeeds. Current DNS
  endpoint10.10.10.1/home.arpa has no verified central alias. INBIT-PRINT was
  advertised through WSD/LLMNR rather than registered as a company DNS record;
  deployment docs now distinguish this and use IP for current diagnostics.
- Windows read-only NetShareEnum by IP lists the three model shares plus IPC$.
  Explicit OpenPrinter PRINTER_ACCESS_USE by IP still returns1801 after a long
  wait. Publishing native drivers/downloads did not establish client connection.
  The probe ended without installation or printing; no broad security change.
- Current Get-Printer and live32-bit DYMO SDK still show only native USB/file
  queues and older generic IPP entries; no installed native network400 on this
  workstation. Laptop installed entry/driver/port remains unknown.
- Brief Samba diagnostic verbosity restored from3 to its prior1; services active,
  guest/file permissions unchanged, no pending jobs. No Windows install/policy,
  permanent server/node/image/app/database change, print, commit or push.
- Explained that automatic Windows search, a Samba share and manual native-driver
  IPP setup are all Windows installations, differing in connection/driver. DYMO's
  own network-printer search is not evidence that a Windows queue was installed.
  Named-pipe/TCP and guest restrictions remain hypotheses for client connection,
  not confirmed root causes. Further changes require a specific failure result.

### 2026-10-08 - Manual Role And Focus Feedback Corrections

- Owner describes AC-01 v11, SC-01 v14 and AST-05 v8 as degradation: role
  names and redundant rings on complete crops. Recorded requires-rework
  status without changing their exact artifact bytes or accepted selections.
- Owner explicitly chose existing access-group names exactly. Corrected
  context to Iedereen / Refurbisher / Supervisor, omitting the invented
  Supervisor qualification. Verified established names in production seeding.
- Repaired the shared reasoning: review the unmarked crop first; mark a target
  when remaining search/confusion warrants it. Complete joint forms/checks
  need no extra outlines. Updated method, focus/capture/system contracts,
  decisions, guide specs, exact reviews and continuation indexes. See
  docs/manuals/operator-guides/reviews/emphasis-feedback-2026-10-08.md.
- Bounded new drafts AC-01 v12, SC-01 v15, AST-05 v9: four A4 pages. Preserved
  task text, 18 source/crop hashes and all 18 figure frame sizes; only role
  wording, version labels and focus marks changed. All four rendered pages
  inspected at144dpi; role/version-normalized PDF text equality and retained
  previous81-file integrity passed. New review round retains sources by
  immutable reference to the previous round, self-contained HTML/SVG, PDFs,
  crops, rendered comparisons, input snapshots and verification.
- No new capture, application/database mutation, app test, physical print or
  unfamiliar-reader trial. Generator/component/geometry checks cover this
  manual-only change; wider composition alignment, translation and mobile
  history gaps remain open. Drafts remain unaccepted. No commit, push,
  accepted/runtime selection or concurrent printer/Olimex work changed.

### 2026-10-08 - Confirmed Windows connection blockers and repaired name response

- Owner requested a simple network-add flow with automatic native DYMO drivers.
  Confirmed this Windows11 build26200 client has absent RPC transport policy,
  existing guest SMB enabled/signing not required and no native network400 entry.
- Header-only server capture during read-only Windows OpenPrinter showed TCP445
  succeeding, then repeated TCP135 SYNs without response. Installed rpcd_spoolss
  lists only named pipes/local RPC; no TCP endpoint. Windows11 default TCP printer
  RPC is therefore a confirmed blocker here. Opening135 alone would not add a
  printer service. The30-second bounded caller exited; no installation/print.
- Retrieved exact wsdd2 1.8.7 source from Debian for name-response investigation.
  Its -H path leaves hostname initialization unsafe. Removed only the hostname
  override from the LAN-bound unit, retaining INBIT-PRINT via -N, then restarted
  wsdd2. Direct/multicast LLMNR now answer249, Windows resolves INBIT-PRINT and
  named UNC400 driver hash matches. No DNS record was added; cross-LAN stability
  still requires company DNS. Backup under root-only connection-diagnostics.
- Prepared reversible configure-samba-print-transport.ps1 in repo and protected
  staging: administrator Apply on Win11 snapshots/sets only named-pipe RPC client
  policy; Restore restores/removes it. Explicit-policy conflict refused; Win10
  unchanged. Syntax, read-only preview and non-admin refusal verified. No SMB,
  RPC privacy, driver-elevation or firewall change and no Spooler restart.
- Asked owner to run elevated helper on this PC and retry laptop after name fix.
  Pending operator response, native Windows queue install, DYMO SDK enumeration
  and physical label. Win11 trace does not prove laptop root cause; still need
  laptop IP-path/error/driver result. No final staff installer or image accepted.
- All central services active; no pending CUPS jobs. CUPS IDs/routes/media/default,
  fault pauses and printer/driver permissions unchanged. Updated deployment and
  client docs; prior blocked agent Windows installation not retried or bypassed.
  No app/database/node/image change, commit/push or PHP tests (not applicable).

### 2026-10-08 - Native Windows Network Queue; DYMO Application Check Pending

- Applied the prepared named-pipe printer RPC helper through normal UAC on this
  workstation. Verified exit0, DWORD1 and original-value backup; fresh read-only
  OpenPrinter now succeeds immediately. Windows10 owner confirms INBIT-PRINT opens
  but connecting gives "kan geen verbinding maken" without a numeric code.
- Ordinary SMB Add-Printer -ConnectionName fails with0x80070bcb, including elevated
  runs; interactive Windows installation also failed according to the owner.
  Fixed confirmed driver-DLL read/execute denial (eight registered DLLs0644 ->0755,
  root-owned, no guest write); independent Windows error5 probes now pass. DYMO400
  registration now has verified native version/date/provider/hardware metadata
  and all eleven dependencies. These fixes did not resolve Point and Print.
- Explicit local-driver remote-connection API fails1745; bounded Samba RPC logging
  records unsupported spoolss call28. A client-rendering data-value trial did not
  help and was removed; original absence, verbosity and guest-only configuration
  restored. Temporary management accounts removed; no persistent login added.
- Installed DYMO LabelWriter 400 (Network) with native DYMO400 driver and Windows
  Local Monitor port \\INBIT-PRINT\DYMO LabelWriter 400. Live32-bit DYMO SDK lists
  this entry. One Windows-rendered test reached CUPS as job42; owner confirms print.
  Existing USB queues, default printer, old jobs and calibration settings retained.
- Owner correctly stresses that Windows printing was not the goal: actual DYMO
  Label v8 network-printer use is. Asked to reopen Label v8 and check the main
  installed-printer selector for the Network entry. SDK enumeration/GDI printing
  do not establish that UI selection or DYMO's separate network search works.
- Added pilot install-dymo-network-printer.ps1 with preview/default, administrator
  Apply, model selection, conflicting-queue refusal and native SMB local-port setup.
  Missing-driver branch checks pinned INF/catalog/signature, stages vendor package
  through PnPUtil and uses client OS core drivers. Published root-owned read/execute
  helper under hidden print$/setup; syntax/all previews and UNC/hash passed. Fresh
  Windows10 driver staging and330/Twin client qualification remain unperformed.
- Central smbd/wsdd2/CUPS and actual print-node-collector.service active; no pending
  jobs or guest-writable driver files. Prior native-IPP approval rejection was not
  retried: later authorized SMB actions are distinct. No SMB guest/signing, RPC
  privacy, driver-elevation relaxation, node/image/app/database change, commit or
  push. PHP tests not applicable. See docs/print-node-deployment.md.

### 2026-10-08 - DYMO Wireless Scanner Versus Installed Printer Selection

- Owner still sees "er wordt gescand naar printers" / no printers found. Read
  official Label v8 and Wireless PnP guides and inspected installed wizard strings:
  Bonjour/_pdl-datastream._tcp, LabelWriter Wireless, LabelManager Wireless PnP.
  File/Add Network Printer is wireless discovery; shared Windows printers are
  chosen through the printer image in the main Print area after installation.
- DLS restarted (PID14996, Test label28 en25 document open); a fresh live32-bit
  SDK query still lists DYMO LabelWriter 400 (Network). Windows queue retains
  native400 driver and UNC Local Monitor port. Main UI selector remains an
  operator check; do not claim its separate wireless scanner was repaired.
- Provided the precise cancel-scan / main printer-image selection path. Updated
  deployment evidence with primary DYMO references. No additional print, driver/
  policy/server/node/image/app/database change, commit or push; no tests needed
  for this read-only application/manual investigation.

### 2026-10-08 - Windows10 Laptop Is The Active DYMO Client

- Owner clarifies the scanning/no-printers result is on Windows10 laptop, not this
  workstation. Prior native400 installation, live SDK enumeration and job42 print
  were on this workstation only; do not treat them as laptop qualification.
- Rechecked the server-hosted pilot helper's UNC read/hash and400 preview. Provided
  its administrator Apply command for the laptop, then main DYMO Print-area
  selection. This session has no established execution connection to that laptop;
  its actual driver/queue and installer result remain pending.
- No further installation, print, policy/server/node/image/application/database
  change, test, commit or push in this clarification block.

### 2026-10-08 - Windows 10 DYMO Failure; Driver Delivery Withdrawn

- Owner reports RtlLogUnexpectedCodepath missing and DYMO Label cannot start;
  confirmed error path C:\Windows\System32\spool\drivers\x64\3\UNIDRVUI.DLL.
  Server export included this Windows 11 PC's Unidrv components10.0.26100.9457;
  incompatible shared-core replacement is the leading cause, laptop version and
  installation trace not yet verified. Publishing unqualified core files for
  Windows10 was an agent error. Earlier PS/Connect instructions are withdrawn.
- Removed print$; moved all exported drivers/setup into root-only quarantine
  /root/print-sharing-backup-20261008/withdraw-windows-drivers-142325; set the
  three shares use client driver=yes. Checked no jobs before Samba restart.
  testparm passes, anonymous print$ returns BAD_NETWORK_NAME, three printer
  shares/IPC$ only, smbd/wsdd2/CUPS/actual collector active, no staging accounts.
- Disabled repository installer -Apply before queries/mutations. Retained its
  historical code/evidence; no staff PowerShell workflow is accepted. Updated
  handbook, deployment, README/security and fork notes to prevent reuse.
- Owner paused further installation work until Label is restored. No laptop
  execution connection exists; no System Restore points available. Asked for
  affected DLL version and possible parent-folder copy through Explorer only.
  Recovery remains incomplete; do not claim uninstall/reinstall alone fixes
  shared core DLLs. Preserve Windows10's own compatible sources and backups.
- No further print, node/SD image, firewall, app/database, commit or push. PHP
  tests not applicable. Infrastructure checks above validate containment only.

### 2026-10-08 - Laptop DLL Build Identified; Local Recovery Source Pending

- Owner reports faulty UNIDRVUI.DLL version0.3.26100.9457, matching the exported
  Windows11 build/revision26100.9457; preserve reported leading fields rather
  than silently normalize them. Parent x64 folder copy is absent.
- Requested Explorer search in the laptop's DriverStore/FileRepository, then
  WinSxS if needed, for an amd64 Windows10 copy and its path/version. Matching
  UNIDRV.DLL, UNIDRVUI.DLL and UNIRES.DLL are shared core components; the exported
  trio were all10.0.26100.9457. No client file replacement has been performed.
- Recovery only; no installation experiment, server/node/app/database change,
  print, commit or push. Laptop recovery remains unconfirmed.

### 2026-10-08 - Windows 10 Local Recovery Candidate Found

- Owner found UNIDRVUI.DLL10.0.19041.6456 on the laptop. Exact source path is
  not supplied; GUI recovery requires a local amd64 DriverStore/WinSxS folder
  and Windows10 UNIDRV.DLL/UNIRES.DLL companions from that same source.
- Provided targeted GUI backup/Print Spooler stop/copy/restart instructions;
  do not alter source folders, replace other DLLs, use x86 files or delete spool
  folders. Recovery is not yet confirmed; require successful DYMO Label launch.
- No remote laptop access, PowerShell instruction, print, server/node/image/
  application/database change, test, commit or push in this recovery block.

### 2026-10-08 - Recovery Copy Blocked At Destination

- Owner reports permission denial replacing DLLs in spool/drivers/x64/3; source
  copying is not the reported blocker. Requested normal Explorer Continue/UAC
  authorization with Print Spooler stopped, and exact denial if it persists.
- No ownership/ACL, UAC/security policy or other laptop change performed by the
  agent. Recovery remains pending; do not claim DYMO Label is restored.

### 2026-10-08 - Spooler Stopped; Destination Permission Denial Persists

- Owner now confirms Spooler is stopped, superseding the earlier difficulty
  locating it in Task Manager. Do not infer a missing service or recommend a
  Windows reinstall on that basis.
- Copying Windows10 core DLLs into spool/drivers/x64/3 still fails with a rights
  error. Stopping a service releases locks but does not grant write permission.
  Exact denial text/Windows principal remains unknown; request the error dialog
  before deciding between normal administrator authorization and a narrowly
  scoped file-permission repair. No ownership/ACL or security-policy change yet.
- DYMO recovery remains incomplete; no new installation experiment or print.

### 2026-10-08 - GUI Recovery Alternatives Requested

- Owner rejects further manual-copy troubleshooting and asks for quick recovery
  alternatives. Official DYMO FAQ documents LWRemove/LWSupport for Windows driver
  removal; this PC bundles Support/LWSupport.exe with GUI Remove DYMO Printer
  Drivers / Remove actions. Read-only resource inspection confirms it removes
  DYMO drivers/printers; no execution or cleanup was performed here.
- Recommend a bounded DYMO driver-cleanup/reinstall attempt with saved labels
  backed up, clear impact on all DYMO printer entries, normal administrator
  authorization and no PS commands. It is not established that this utility
  clears newer shared Unidrv DLLs; unchanged entry-point error requires targeted
  privileged core repair, not repeated application reinstall attempts.
- Windows in-place installation with Keep personal files and apps is a slower
  GUI recovery fallback, not a guaranteed fix for retained third-party spool
  driver files. No Windows reinstall/reset has been performed or scheduled.
- Laptop recovery remains incomplete. No server/node/app/image/database change,
  test print, commit or push.

### 2026-10-08 - Owner Selects Fast Vendor Cleanup

- Owner accepts removal of all DYMO printer entries and wants one short recovery
  sequence, no further diagnostic back-and-forth. Provide administrator-run
  bundled LWSupport cleanup, uninstall/reinstall official DYMO Label8.7.4 and
  reboot. Do not claim success until Label opens; shared-core cleanup remains
  an unverified limitation. Experimental network installation remains paused.

### 2026-10-08 - Saved Label Backup Must Precede Cleanup

- Owner objects that Label cannot open and worries cleanup prevents recovery of
  saved labels. Clarify saved labels/address books are ordinary files and can
  be backed up through Explorer's Documents/DYMO Label folder without launching
  DLS. Existing custom save locations also need preserving. Do not claim any
  laptop data has been backed up or restored; no remote filesystem access exists.
- Earlier fast-cleanup instructions should have made file backup the first step.
  Pause further cleanup until files are preserved. Official DYMO FAQ documents
  default Documents/DYMO Label storage and retention across software upgrades;
  it is not proof that arbitrary cleanup utilities preserve every custom path.
