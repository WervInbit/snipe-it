# CUPS Setup Guide (LabelWriter, Linux Server)

Use this checklist to set up a shared Dymo LabelWriter queue on a Linux server (CUPS) while developing from Windows. Updated for WSL-based dev printing (LabelWriter 330 Turbo with 25x25 S0929120 roll).

## Prerequisites
- SSH access with sudo on the print host.
- LabelWriter connected via USB or reachable over the network.
- Debian/Ubuntu packages assumed; adjust as needed for other distros.

## Installation & Driver
1) Install CUPS + Dymo driver:
   - `sudo apt-get update`
   - `sudo apt-get install cups printer-driver-dymo`
2) Add your admin user to lpadmin and restart CUPS:
   - `sudo usermod -a -G lpadmin $USER`
   - `sudo systemctl restart cups`
   - Re-log your shell if the group was just added.

## Queue Setup
1) Discover the printer URI:
   - USB: `lpinfo -v | grep -i dymo`
   - Network: `lpinfo -v | grep socket` (or `ipp`/`lpd`)
2) Create a queue (example name: `dymo99010`):
   - `sudo lpadmin -p dymo99010 -E -v <printer_uri> -m dymo.ppd`
3) Set defaults for the 99010 roll:
   - `sudo lpoptions -p dymo99010 -o media=99010 -o PageSize=w89h28`
   - Adjust media/PageSize when adding new rolls (30334/30336/99012/30256).
4) Make it the default queue if desired:
   - `sudo lpoptions -d dymo99010`

## Test Print
1) Create a small test PDF (any 89x28 mm sample).
2) Print:
   - `lp -d dymo99010 /path/to/sample.pdf`
3) Check queue if it stalls:
   - `lpstat -t`
   - `sudo tail -f /var/log/cups/error_log`

## Network Access
- If printing from another host, allow CUPS on the LAN (firewall):
  - `sudo ufw allow 631/tcp`
- Optional: enable remote admin in `/etc/cups/cupsd.conf` (limit to trusted CIDRs), then `sudo systemctl restart cups`.

## App Integration Notes
- Expose the queue name via env/config (e.g., `LABEL_PRINTER_QUEUE=dymo99010`).
- Server-side print command: `lp -d "$LABEL_PRINTER_QUEUE" /tmp/label.pdf`.
- Log user, asset id, template, queue, and job id for audits.

## Twin Turbo + .label workflow tips
- The app now exposes only two label sizes: `Dymo 99010 (89 x 28 mm)` and `Dymo S0929120 (25 x 25 mm)`. Pick one in Settings -> Labels; all other rolls are removed from the picker.
- A raw QR-only PNG download is available on the asset page; use it to drop the QR into a custom `.label` design in the Dymo software if you prefer not to use the generated PDF layout.
- For a LabelWriter Twin Turbo on the network, create one CUPS queue per roll (e.g., `dymo99010-left`, `dymo99010-right`) and point `LABEL_PRINTER_QUEUE` or `LABEL_PRINTER_QUEUES` at the queue that matches the loaded roll. This avoids roll auto-detection issues when switching sides.
- When printing from the app, keep the queue defaults aligned with the physical roll size (media/PageSize) to prevent clipping. Re-run `lpoptions -p <queue> -l` to confirm the active roll.

## Windows Dev Tips
- You can validate layouts by opening the generated PDF locally (no printer needed).
- For end-to-end testing, run WSL2 with CUPS + a dummy printer, or share a Dymo from Windows and target it via `smb://` from WSL/CUPS. Keep production queue naming consistent (`dymo99010`).

## Dev Environment (WSL + LabelWriter 330 Turbo, 25x25 S0929120)
- Attach the USB printer to WSL after plug/unplug/standby:
  - `usbipd bind --busid <BUSID>` (from `usbipd list`, e.g., 1-1)
  - `usbipd attach --wsl=Ubuntu --busid <BUSID>`
- Restart CUPS in WSL to pick it back up:
  - `wsl -d Ubuntu --user root -- systemctl restart cups`
  - Verify: `wsl -d Ubuntu -- lpstat -t` (device should be `usb://DYMO/...`; queue `dymo25`)
- Create/refresh the 25x25 queue (once):
  - `sudo lpadmin -p dymo25 -E -v usb://DYMO/DYM0008?... -m dymo:0/cups/model/lw330t.ppd`
  - `sudo lpoptions -p dymo25 -o PageSize=w72h72 -o media=w72h72 -o page-left=0 -o page-right=0 -o page-top=0 -o page-bottom=0`
- App/container wiring:
  - `.env`: `CUPS_SERVER=<WSL IP>`, `LABEL_PRINTER_QUEUE=dymo25`, `LABEL_PRINT_OPTIONS="-o PageSize=Custom.W72H72 -o media=Custom.W72H72 -o page-left=0 -o page-right=0 -o page-top=0 -o page-bottom=0 -o scaling=100"`
  - Ensure `cups-client` is installed in the PHP container after restarts: `docker exec -u root snipeit_app apt-get update && apt-get install -y cups-client`
  - Smoke test from container: `docker exec snipeit_app lpstat -h <WSL IP> -p -d` and `docker exec snipeit_app sh -lc "CUPS_SERVER=<WSL IP> lp -d dymo25 /var/www/html/sample-asset-serial-25x25.pdf"`

## Maintenance & Debugging
- If prints stall or split labels:
  - Reattach USB to WSL (`usbipd bind/attach`) and restart CUPS.
  - Verify queue media: `lpoptions -p dymo25 -l` (look for `*w72h72`); set if missing.
  - Clear app label cache if layouts change: `rm -rf storage/app/public/labels`; `php artisan cache:clear`.
- Inspect CUPS logs for errors:
  - `wsl -d Ubuntu --user root -- tail -n 50 /var/log/cups/error_log`
- Force explicit media per job from container:
  - `docker exec snipeit_app sh -lc "CUPS_SERVER=<WSL IP> lp -d dymo25 -o PageSize=Custom.W72H72 -o media=Custom.W72H72 -o page-left=0 -o page-right=0 -o page-top=0 -o page-bottom=0 -o scaling=100 /var/www/html/sample-asset-serial-25x25.pdf"`
- Confirm app config/state:
  - Template defaults: `config/qr_templates.php` (S0929120 is default, v13).
  - Regenerate samples: `docker exec snipeit_app php scripts/generate-sample-asset-serial.php`.
  - Print test job and note the job id (e.g., `dymo25-25`) for log correlation.
