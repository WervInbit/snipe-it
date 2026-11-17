# Manual Session Notes - 2026-10-27

This scoped committed view preserves the manual sections. Other
application/printer session notes remain in the shared working file
for their owning task and are not published by the manual commits.

### 2026-10-27 - Operator Guide Navigation And Space Review: Start

- Owner reports lost hints/help, unused screenshot space and assumed navigation
  in the remaining-guide batch. Specific corrections: AC-02 profile/menu focus;
  AST-03 duplicate search, visible creation entry, optional registration note
  and actual Save control. Reconcile reader guidance before layout changes.
- Read current method, shared contracts, AC-02/AST-03 specifications and prior
  review context. Preserve sealed outputs and concurrent printer work. Produce
  new, reviewable versions and a batch-level content/space audit; an Affinity
  migration is under consideration, not accepted as a substitute for completeness.

## Operator guide corrections and continuation - 2026-10-27

- AC-02 v7 and AST-03 v18 are new unaccepted examples, three A4 pages. Use the
  [comparison](../../resources/manuals/operator-guides/review-rounds/2026-10-27-navigation-and-space/review.html)
  and [full feedback/batch audit](../manuals/operator-guides/reviews/navigation-and-space-feedback-2026-10-27.md).
  The other fifteen batch subjects remain unrevised with explicit follow-ups.
- Preserve/relocate useful tips and recovery, teach entry/Save controls and
  terminology, then reflow the actual content. Empty frame geometry and smaller
  source targets must not survive merely because content was removed.
- Root intake notes are nullable; the mounted follow-up StoreAssetRequest is
  older and conditional-required. Do not teach that older rule as current
  registration policy. Asset20 INBIT-AA0024 / GUIDE271027A is an owned synthetic
  fixture, submitted once with a demo note solely for that validator. Several
  form GETs reserved tags; allocation records remain intact. No physical work.
- Three pages visually read back and reproduced identically at144dpi; thirteen
  crop/source, PDF, link, step-containment and script checks passed.1250 files
  across the accepted archive and four prior rounds remain unchanged.
- Editable interchange contains live text and separate screenshot/focus objects.
  Native Affinity import/fonts/layers, paper and unfamiliar-reader trials are
  untested. Do not confuse an editable source with completed guidance. Full
  batch content work remains; no app/permission/source deploy, physical print,
  accepted/runtime selection, commit or concurrent printer/camera/card work.

### 2026-10-27 - AST-03 Entry Substitution: Alignment Follow-Up

- Owner immediately misses the Dashboard Add Asset button in v18. Verified
  the source capture includes Nieuwe Asset/Nieuwe aanmaken; the authored crop
  excludes it and substitutes the asset-list plus. Adding search did not
  require removing that control. The loss was an editorial choice, not missing
  evidence or a screen change.
- Diagnosed preservation at too abstract a level (creation remains possible)
  and review against the rewritten plan. Existing method rules already require
  assessing lost reader support; stating that the reasoning was repaired was
  premature. V18's entry design needs rework and is not an aligned example.
- Recorded feedback/continuation context. The owner requests explanation and
  alignment; no new PDF, screenshot, generator, shared-method or app change.
  Sealed proposals remain unchanged. No tests needed for this discussion.

### 2026-10-27 - Workstation Network Loss Investigation Started

- Owner reports inability to connect to Inbit Wi-Fi and no Internet. Recalled
  the Oct20 camera trial's Internet Connection Sharing refresh and restoration
  of the original Wi-Fi-public/Ethernet-private roles and192.168.137.1/24.
- Started read-only adapter/address/route/service/WLAN diagnostics. No network,
  firewall, profile, sharing or service changes authorized or applied in this
  diagnostic block. Current cause is not yet established.

### 2026-10-27 - Workstation IPv4 Misconfiguration Identified

- Owner confirms switching to a custom Wi-Fi hub to regain Internet. Current
  profile JHS2 reports IPv6 Internet; Wi-Fi IPv4 DHCP is Disabled, with no
  configured static address/gateway and only169.254.70.216. No IPv4 default
  route exists. Ethernet is disconnected; its test-side192.168.137.1 remains.
- WLAN events confirm Inbit association/authentication succeeded at14:20 before
  the owner selected JHS at14:21. This separates the observed IPv4 configuration
  problem from an established Wi-Fi password/radio failure. Exact origin/time
  of DHCP disablement is unproven. Prior Oct20 helpers target Ethernet sharing;
  saved evidence does not establish that they disabled Wi-Fi DHCP.
- ICS enumeration requires elevation and was denied; no elevated probe, sharing
  change, DHCP change, adapter reset or Wi-Fi reconnect performed. The targeted
  remedy is restoring automatic IPv4 assignment on Wi-Fi, then verifying a
  valid lease/default route and company access. Current hotspot left connected.
  No application changes/tests apply. Reference: Microsoft TCP/IP settings,
  https://support.microsoft.com/en-us/windows/experience/connectivity-networking/essential-network-settings-and-tasks-in-windows

### 2026-10-27 - Wi-Fi DHCP Restored

- Owner authorized restoration. Enabled IPv4 DHCP only on verified Wi-Fi
  interface10; readback confirms Enabled. Hotspot did not provide an IPv4 lease
  during the bounded check; IPv6 remained connected. No sharing/firewall/reset.
- Windows location permissions block netsh WLAN inspection/reconnection even
  elevated. Attempted saved Inbit connection was denied; no network switch
  occurred. Owner must select Inbit in the normal Windows Wi-Fi menu; lease,
  gateway and company access still need verification. No app tests apply.
- Before/after state and the completed helper are retained outside Git under
  LOCALAPPDATA/Inbit/NetworkRecovery-20261027. No background repair remains.

### 2026-10-27 - Inbit Connectivity Verified

- Owner selected Inbit after IPv4 DHCP restoration. Read-only verification shows
  profile `Inbit 2`, lease `10.10.10.245`, gateway/DNS `10.10.10.1`, internal
  HTTPS to `10.10.10.249`, external DNS, and an HTTPS 200 from microsoft.com.
- Internet and company-LAN connectivity are restored. The isolated Ethernet test
  configuration was not changed. No application tests apply.

### 2026-10-27 - 330 Offline; Manual 400 Port Mismatch Identified

- Parallel read-only checks confirm the enrolled330 node
  `printnode-f253868b5fa6.home.arpa` is offline/not discoverable. Its old `.27`
  address, known MAC and host key are absent; the collector correctly keeps
  `dymo330` unknown/paused. Power, Ethernet/link and a board power-cycle are the
  next physical checks. Do not force-enable the queue.
- The current `DYMO LabelWriter 400 nuc12` queue has the native400 driver but is
  still attached to the old CUPS/IPP port. The persistent native gateway owns a
  different genuine WSD port, presently without a local queue. Earlier manual
  driver selection therefore changed the renderer without changing the failed
  transport, explaining the EMF/invalid-name/invalid-datatype errors and absence
  of a CUPS job.
- The official legacy DYMO INF has USBPRINT IDs and cannot automatically match
  the WSD identity, which explains `driver unavailable`. The viable manual GUI
  path must select the genuine `DYMO LabelWriter 400 Network` WSD port and local
  native400 driver, avoiding all IPP ports and `@ inbit-snipeit-nuc12` entries.
  The gateway transport is proven by job55; fresh-client onboarding and physical
  acceptance remain required. No queue, service, node or driver was changed.

### 2026-10-27 - Correct 400 WSD Diagnostic Queue Prepared

- After the owner refreshed Add Printer and received `driver unavailable`,
  Windows recreated a genuine WSD port. Verified its printer UUID matches the
  persistent native gateway and its install protocol is WSD, then created the
  separate short local queue `DYMO 400 WSD Test` with the installed native
  `DYMO LabelWriter 400` driver. Queue status is Normal and no job was sent.
- The old `DYMO LabelWriter 400 nuc12` IPP-port queue remains untouched. This
  current-PC diagnostic can prove end-to-end transport but does not satisfy the
  fresh-device onboarding requirement. Owner stopped Computer Use with Escape;
  no further UI automation was issued. Label v8 restart/selection and one
  physical print are next.
- Rechecking after the owner changed the330 switch port still finds no DNS,
  address, MAC, trusted inventory, SSH identity or relay. Power-cycle the board
  with Ethernet connected; collector remains safely paused.

### 2026-10-27 - Local Diagnostic Queue Withdrawn

- Owner correctly rejected the programmatically created current-PC queue as an
  onboarding test: this workstation already contains legacy DYMO drivers, ports
  and prior queues. Verified the owned queue had zero jobs, then removed only
  `DYMO 400 WSD Test`; retained its driver, the gateway-discovery port and every
  pre-existing printer. No job was sent.
- Current-PC configuration cannot satisfy the acceptance requirement. The next
  valid evidence is GUI-only manual creation on another target Windows device,
  using the discovered Network WSD endpoint and its locally installed official
  native400 driver, followed by Label v8 recognition and physical output.

### 2026-10-27 - Persistent Gateway Given Fresh Windows Identity

- Owner redirected work to the earlier onboarding behavior: Windows first adds
  a usable generic queue, then the operator selects the native DYMO driver;
  nobody manually selects a port. Confirmed the earlier setup probe and current
  printing gateway already share the same validated metadata/capabilities.
- The persistent endpoint reused a repeatedly failed/removed Windows device UUID.
  With the400 CUPS queue idle, no active jobs and no established gateway clients,
  stopped only its service, backed up config root-only, replaced only endpoint
  UUID `b479158c...` with fresh `caed6fa2...`, and restarted. Name, TCP3912,
  firewall, native receiver, CUPS queue and physical route are unchanged.
  Service/firewall/CUPS/collector are active and the400 is idle. Other clients
  immediately fetched the new identity. Windows Add result on the target client
  remains pending; rollback is under the dated central backup.
- Restarted only wsdd2 while checking the older shared-printer route. Name DNS
  now resolves and TCP445/IP share enumeration works, though named `net view`
  still returned error53. No share, driver publication or printer changed.

### 2026-10-27 - Fresh Identity Failed; Onboarding Work Stopped

- Owner's single Add attempt against fresh endpoint `caed6fa2...` again reported
  `Stuurprogramma is niet beschikbaar`. This fails the required prerequisite:
  Windows created no usable queue, so Advanced/manual driver selection cannot
  be opened. Further progression through that path is invalid.
- With no CUPS jobs or established WSD client, rolled back only the fresh UUID
  using its exact protected backup. Verified original `b479158c...`, gateway,
  firewall, CUPS and collector active, TCP3912 listening and physical400 queue
  enabled/idle. No stray config, client queue or print job remains from the try.
- Stop onboarding experiments. Current guides that imply driver selection after
  a driver-unavailable Add are wrong and must not be used as operator procedure.
