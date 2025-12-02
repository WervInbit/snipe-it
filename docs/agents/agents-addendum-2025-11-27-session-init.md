# Agents Addendum - 2025-11-27 Session

## Context
- Reviewed existing hardware inventory and seed data to replace placeholder SKUs with real device information and collect missing port/wireless details.

## Worklog
- Added/updated hardware inventory folders: `scripts/450 g8/`, `scripts/450 g7/`, `scripts/450 g6/`, `scripts/430 g7/` with hw-inventory JSON/TXT and SKU detail files.
- Updated seeders with real SKUs and specs:
  - HP ProBook 450 G8 → `2E9F8EA#ABH` (i5-1135G7, 8GB, 256GB NVMe); ports updated; asset override highlights added earlier.
  - HP ProBook 450 G7 → `8VU81EA#ABH` (i5-10210U, 8GB, 256GB NVMe); ports set to 2x USB-A 3.1 Gen1, 1x USB-C Gen1 DP alt/PD, HDMI 1.4b, combo jack.
  - HP ProBook 450 G6 → `5PP65EA#ABH` (i5-8265U, 8GB, 256GB NVMe); Wi-Fi Intel AC 9560; ports set to 2x USB 3.2 Gen1 Type-A, 1x USB 3.2 Gen1 Type-C (DP alt/PD), HDMI 1.4, combo jack; camera resolution set to 1280x720.
  - HP ProBook 430 G7 → `8VT42EA#ABH` (i5-10210U, 8GB, 256GB NVMe); ports set to 2x USB-A 3.1, 1x USB-C Gen1 DP alt/PD, HDMI 1.4b, combo jack.
- Added SKU detail notes: `scripts/450 g8/2E9F8EA.txt`, `scripts/450 g7/8VU81EA.txt`, `scripts/430 g7/8VT42EA.txt`, `scripts/450 g6/Detail.txt` read for SKU; Wi-Fi/Bluetooth/ports captured where known.
- Inventory script (`scripts/hw-inventory.ps1`) and launcher (`scripts/hw-inventory.cmd`) in place; used to gather data per device.

## Open Points / TODO
- Remaining placeholders needing real SKUs/hardware details: HP ProBook 430 G6, HP ProBook 430 G3, Microsoft Surface Pro 4, Microsoft Surface Pro 5, Samsung Galaxy A5, iPhone 12, Pixel 8 Pro.
- For 450 G6, battery stats still missing; display panel tech not confirmed; Bluetooth version inferred (Intel AC 9560 = BT 5.0) but not validated by LMP.
- For all devices, confirm keyboard layout if non-US/ISO defaults apply.
- Run hardware inventory on remaining devices and update seeders with real SKUs/ports/wireless/camera/battery where available; add corresponding SKU note files under `scripts/<device>/`.
- Log today’s changes in `PROGRESS.md` next session.
