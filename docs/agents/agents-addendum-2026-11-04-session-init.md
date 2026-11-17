# Manual Session Notes - 2026-11-04

This scoped committed view preserves the manual sections. Other
application/printer session notes remain in the shared working file
for their owning task and are not published by the manual commits.

## Separate Manual Authoring Session

- Resume guides at the owner's request. Read current handbook, progress/fork
  context, handoff, AST-03 specification and the PDF authoring workflow.
- Produce one AST-03 working draft with a concrete reader walkthrough and
  comparison to the accepted direction and owner feedback. Preserve the named
  Dashboard creation entry alongside duplicate search and the complete task.
- Keep frozen artifacts and concurrent printer/application work untouched.

### Manual Pilot Outcome

- AST-03 v19:2 continuous A4 sides, working draft, not accepted. Named Dashboard
  creation entry and duplicate search coexist; full registration/labeling task,
  optional notes, Save and useful recovery retained. Comparison includes exact
  accepted v15, rejected v18 and central changes on one local review page.
- Source/crop/geometry/browser and historical preservation checks passed.
  Both pages were read at full render and A4 screen scale. No novice/paper/
  physical print/scan trial. Reused Dutch evidence still needs fresh live UI
  reconciliation; current root and captured checkout differ.
- No app/fixture/printer mutation, accepted selection change, batch generation
  or commit. The new round is sealed after QA; future corrections need a new
  version. See docs/manuals/operator-guides/reviews/AST-03-v19.md.

## Separate Final Guide Set And Affinity Planning

- Owner chooses newer versions as the basis and requests final consolidation
  plus native editable output; current authorization is planning/investigation.
- Inventory:25 guides/67 pages; all selected PDF hashes match. Eight thin index
  records resolved via owning frozen manifests. Windows package inventory:
  Canva.Affinity3.2.2.4557; no app launch/import/edit performed.
- Plan preserves concrete reader controls/support and closes recorded15-guide
  content/navigation gaps; native text frames/styles, uncropped picture sources,
  editable diagram/focus vectors and final PDF/native consistency are required.
- Recorded deployed validator still requires some creation notes, contrary to
  owner optional-intake rule. Resolve target/application difference; no source
  or deployed behavior was changed by this planning work.
- Planning document and existing handoff/decisions/backlog links updated. No
  guide generation, conversion, prototype, capture, app test or runtime write.
- Read the plan before execution:
  docs/manuals/operator-guides/reviews/final-set-and-affinity-plan-2026-11-04.md.

### Catalyst Model Confirmed - USB Detection Pending

- Catalyst2960-X; owner confirms mini-USB console and this PC. Tools inspect
  LAP-J-DEV/HP ProBook450G8. No console/COM/unknown USB device is presently
  enumerated by WMI, PnP or pnputil. No switch console was opened.
- Cisco hardware guide confirms mini-TypeB five-pin,9600/8N1/no flow control
  and Windows USB-console driver. No driver compatibility/install action was
  inferred from the old guide. Requested a different data cable/direct PC USB
  port as the next detection test; leave switch power/network unchanged.
- Vendor reference and current blockers are retained in the reconnect plan.
  No driver, serial configuration, switch/network/node setting or printer route
  was changed. No application or physical print test applies to this inspection.

### Cisco USB Enumeration Succeeded; Stable Session Pending

- PC USB-port swap produced Cisco VID05A6/PID0009 COM4/statusOK. The subsequent
  open failed because COM4 disappeared; owner says the cable was changed while
  waiting. This is not proof of a failed driver or switch console.
- Return to that enumerating cable/port combination and keep it fixed. Targeted
  bounded watcher identifies the Cisco device before opening9600/8N1/no-flow.
  No switch CLI command, driver install or network/card change has run.

- The bounded Cisco-device wait ended without detection; its process exited
  and no serial session is open. Await restored, stable cable/port confirmation.

### COM4 Opens But Cannot Write - Linux Host Check Prepared

- Known cable restored and stable: Cisco VID05A6/PID0009, COM4, status OK,
  problem0. PuTTY/plink and native SerialPort open; CR write fails. Native
  semaphore timeout occurs with DTR enabled and disabled. No console prompt or
  switch CLI command; owned serial clients closed/disposed.
- Win11 Pro build26200 uses Microsoft usbser.inf10.0.26100.9278 by CDC class.
  No matching Cisco driver package. Driver/USB-console cause remains unresolved.
- Official Software Download3.1 redirects to Cisco login; no download/install.
  Cisco's model-specific product overview documents Linux support without an
  extra driver. NUC SSH/Python3.13.5 termios/select preflight passed; no current
  USB serial ports. Asked owner to move only the USB host end there if reachable,
  preserving switch power/Ethernet; do not assume this connection has happened.
- See ../plans/olimex-link-reconnect-investigation-2026-11-04.md for evidence and
  the still-pending controlled same-cable/same-port Olimex reconnect capture.
  No switch/network/node/printer/card change, package install or app test.

### 2026-11-04 - All25 Final Guides And Native Affinity: Execution Start

- Owner explicitly authorizes executing the final-set plan for all25 guides,
  including fully editable native Affinity files. This supersedes the earlier
  planning hold. Backlog START-05/NEXT-08, target/configuration START-04.
- Reuse the current25-guide/67-page source selection, preserving frozen history
  and the extensive unrelated dirty worktree. Root revision a766abedee;
  recorded production2f703760d8, captured checkoutc539b9cd18; distinguish these.
- First prove native Affinity3.2.2 import/edit/save/export and reconcile source
  and optional registration-note boundaries. Then complete per-guide content/
  navigation repairs, render/read every page, compare and package exact masters.
- No destructive database action, shared data reset, production deployment or
  physical printing is part of this authoring authorization.

- Console follow-up: owner asks why another host is needed and how Cisco
  documents PuTTY/console connection. Reconfirmed mini-USB Windows driver and
  default9600/8N1/no-flow procedure plus separate RJ-45 serial-console option.
  Current-user PuTTY has no saved serial profile. SetupAPI reports successful
  Microsoft usbser installation/start; actual writes still failed as above.
  No NUC cable move is confirmed; Linux comparison remains optional. No driver
  cause is proven and no package or hardware/configuration change was made.

### Cisco Console History - Microsoft Driver Also Used Earlier

- Nine local Windows SetupAPI logs checked. Same Cisco USB hardware ID installed
  successfully2026-02-24 with usbser2454 and2026-05-28 with8328. Device updated
  to8521 onJune2. Current9278 stagedSeptember15; used on today's attachment.
- Earlier install records support the owner's memory of connecting here, but
  hardware IDs/host USB paths do not uniquely identify the physical switch or
  demonstrate terminal communication. Earlier connections already used Microsoft;
  missing Cisco package alone does not explain the reported change in behavior.
- Corrected current reconnect plan to avoid prescribing a Cisco driver from
  absence alone. Version changes are evidence for comparison, not proof of a
  regression. No matching confirmed primary-source bug found in targeted search.
- COM4 open/write-timeout remains the observed failure. Windows/cable/switch
  isolation is pending; NUC comparison optional and no cable move confirmed.
  No rollback, install, switch reset, node/network/printing change or app test.

### FTDI RJ45 Adapter - Missing COM Driver; Existing D2XX Opens

- New cable is FTDI USB serial VID0403/PID6001/A5069RR4, working FTDIBUS2.12.36.20.
  Its child serial port has Code28/no driver, so it has no COM number. This is
  separate from the Cisco mini-USB fault. No registry/VCP setting changed.
- Old mini-USB COM4 still present. Asked owner to confirm RJ45 CONSOLE socket
  and removal of mini-USB lead at switch before transmit; no confirmation yet.
- Prepared matching Microsoft Catalog Windows11 AMD64 FTDI Ports2.12.36.20 CAB,
  verified exact hardware ID and valid WHCP catalog signatures. See reconnect
  plan for source/hash. Files are local temporary material, not a release asset.
  Current process is not elevated; no driver install or UAC prompt launched.
- Installed signed System32/ftd2xx.dll3.2.21.1 provides direct API. Probe opened
  only serialA5069RR4, queried empty RX queue, closed with FT_OK throughout.
  Zero TX bytes; adapter access proved, switch communication still unproved.
  No new library/package installed; no serial process remains open.
- Owner connection confirmation precedes next CR probe. No switch/node/network,
  CUPS, Windows service/driver, database or card change or app test.

### RJ45 Console Qualified - Current Uplink Baseline And Status Checkpoint

- Physical RJ45 CONSOLE/mini-USB removal confirmed. Direct FTDI CR probe returned
  Inbit-SW01>. COM5 then present with FTDI Ports2.12.36.20/oem205.inf/problem0;
  agent did not install a driver or trigger UAC. PuTTY/plink on COM5 works.
- Switch WS-C2960X-48TS-L/IOS15.2(7)E2. Gi1/0/42 learns original node plus this
  laptop Ethernet MAC, confirming small-switch uplink. Up1Gb/full/accessVLAN1,
  STP forwarding/P2p Edge, no input/CRC/output errors or output drops;1 reset.
  No port-security result at user EXEC; no privilege/configuration attempt.
- Original node still10.10.10.27, trusted SSH pin unchanged, same boot ID,
  end0 up100/full and NM/DHCP route good. RX/TX errors0; RX dropped2787 retained
  as an observation, not evidence of a root cause. No reproduction yet.
- Owner asks for faster clear status. Reported access solved, current network
  works, original failure unexplained. HDMI availability requested before one
  controlled reconnect. Plan now leads with current facts instead of old USB
  failures; checkpoint/TODO point to it. Earlier evidence preserved.
- Owned Plink PID6168/parent4588 verified and closed, COM5 released. A combined
  read command hit automatic policy; simpler scoped reads worked with strict
  saved host pin. No driver/configuration, service, network, CUPS or card change
  by agent. No app test or physical print performed.

### Controlled Cable Trace Reclassifies Fault To Storage Path

- Same cable/same small-switch port reseated about5seconds with power and HDMI
  retained. Console stayed responsive. end0 recovered100/full, DHCP renewed
  10.10.10.27, IPv4/IPv6 SSH worked and interface errors stayed0.
- Immediately after link-up filesystem activity, kernel recorded255 mmcblk0 I/O
  failures across253 sectors (5 reads/250 writes), failed sunxi-mmc status/reset/
  recovery and251 ext4 warnings. DHCP lease fsync/dispatcher disk access failed,
  while network activation itself completed. Errors lasted to capture end.
- ext4 journal-check errors followed at14:05:28-29 local time, near the600-second
  mount commit interval. `/` is now read-only under errors=remount-ro; card block
  ro flag remains0. Several uncached programs/imports fail. No remount/reboot.
- Card observation: SD4GB,0x41,03/2015,serial0x62f005ed,CID
  41343253443447423062f005ed00f389. Card/socket/controller/power distinction is
  unresolved. No power sysfs telemetry or captured voltage warning; this does
  not exclude power. Prior outage may share this cause, but prior journal absent.
- Evidence plus manifest stored protected local-only under
  `%LOCALAPPDATA%/INBIT/diagnostics/olimex/2026-11-04-reconnect-slezu77x`.
  Redacted facts reconciled to plan/TODO/checkpoint/image notes.
- HDMI tty1 shows a temporary diagnostic status using shell built-in output;
  no root/boot file write. Stop live cable tests and preserve current state.
  Offline isolation/card action remains subject to fresh physical target and
  owner confirmation. No production/switch/CUPS/DB/app change or print test.

### Improperly Seated Card Corrected - Repeat Capture Clean

- Owner identifies/corrects improper card seating. Exact same CID and saved node
  identity boot with root rw, ext4 errors/warnings0 and clean MMC boot log.
- Read-only direct checks at all five previously failing regions pass.
- Repeat same cable/port test records expected4.2-second link loss/recovery,
  100/full, IPv4/IPv6 SSH restored, root rw and zero MMC/ext4/network errors.
- This strongly supports seating as cause but does not clear prior filesystem
  damage or long-term media health. Offline fsck, longer commit/storage soak,
  reboot and print acceptance remain before reuse.
- Verification evidence/hash manifest stored protected local-only under
  `%LOCALAPPDATA%/INBIT/diagnostics/olimex/2026-11-04-reseat-verification`.
  Repository handoff docs reconciled; no switch/CUPS/DB/app or card write.

### Switch Configuration Cause Checked Operationally

- Read-only COM5 user-EXEC audit of actual small-switch uplink Gi1/0/42 after
  corrected-card pass. Node and laptop MACs learned; up1Gb/full, accessVLAN1,
  STP forwarding edge, not trunk/channel; VLAN active.
- All observed errors/collisions/carrier faults/discards0; no err-disabled port,
  storm-control or unknown blocking; DHCP snooping disabled.
- Dynamic-auto/DTP administrative default is cleanup only; operational access
  state is working. No switch setting changed. Full running config/auth detail
  unavailable at user EXEC, so conclusion is operational rather than privileged.
- Switch cause is not supported; seating correction explains the A/B behavior.
  Console session closed. Docs reconciled; no CUPS/DB/app/card write.

### Direct Catalyst Link Corrected And Verified

- With Olimex power retained, owner moved the board from the working small-switch
  topology to the prior direct Catalyst path. Both PC and NUC lost10.10.10.27;
  the NUC showed only the stale node neighbor.
- Reconnected FTDI COM5 console and used read-only user-EXEC commands. The switch
  learned no02:4b:0b:c2:98:91 MAC. Gi1/0/42 is enabled but down/down notconnect,
  operational VLAN1, with zero errors, collisions, carrier faults or drops.
- Status reported full/1000, not negotiated a-full/speed. After owner entered the
  enable password, running config confirmed speed1000/duplex full. Authorized
  change removed both fixed settings from Gi1/0/42. Initial auto and bounded
  100/full tests remained down; port was returned to auto. TDR: A/B normal, C/D
  short at0-2m.
- Owner reseated only Ethernet. Link immediately negotiated a-full/a-100, exact
  MAC learned on VLAN1, zero errors. Node restored DHCP10.10.10.27 and trusted
  SSH; end0100/full/errors0, root rw, no real MMC/block-I/O/ext4 error. Saved the
  verified auto configuration to startup config.
- Node has new boot73fb8000-a8b1-42be-ab77-184566803b06 and minutes of uptime;
  no persistent prior-boot journal can classify the reset. TDR C/D short remains
  a gigabit-cable caveat. Offline fsck/long soak and print acceptance remain.
- Final Wi-Fi-only workstation check:4/4 replies at1-3ms; NUC3/3 plus TCP22.
  Strict SSH reconfirmed exact hostname/machine ID/MAC. Olimex remains Ethernet
  end0; only the diagnostic workstation path changed to Wi-Fi.

### 2026-11-04 - All25 Guides Prepared Before Desktop Restart

- START-05/NEXT-08: prepared25 proposed guide versions/68 A4 pages,25 IDML
  preparations and82 retained original image assets. USR-01 now has two sides;
  the other page counts remain. See docs/manuals/operator-guides/reviews/
  final-set-preparation-2026-11-04.md and the working review under output/manuals/
  final-guide-set-2026-11-04/review/index.html for all changes and comparisons.
- Preserved exact group names, Dutch evidence, concrete entry/Save controls,
  real alternatives/continuations, conditional recovery and full-source crops.
  Source-page screening corrected mismatched catalogue use/aggregation examples
  and duplicate inherited footers; geometry alone did not expose those errors.
- All68 source pages pass overflow/intersection/shared-component checks;262
  review links and filtering pass. All25 IDML packages pass ZIP/XML/reference,
  UTF-8, original hashes, image crop/resource and version-footer checks. Original
  selected PDF hashes still match. No same-crop target scale was reduced.
- Both desktop runtimes fail before initialization (failed to write kernel
  assets, Windows error3). Owner instructs continued preparation and will
  restart later. No native .af or final PDF is claimed; native import/edit/save/
  reopen/relocation/export, shared native template and native A4 QA remain.
- Production-note validator contradicts optional registration-note policy;
  START-04 target/configuration checks remain. USR-02's real status dropdowns
  clip native values; final evidence needs opened choices or an actual UI fix.
- No app/fixture/database, printer/node, deployment, physical work, staging or
  commit. PHP tests are not applicable to documentation/artifact preparation.
  Unrelated dirty work and all sealed historical rounds were preserved.
