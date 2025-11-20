# CUPS Setup Guide (LabelWriter, Linux Server)

Use this checklist to set up a shared Dymo LabelWriter queue on a Linux server (CUPS) while developing from Windows. Stick to 99010 (89x28 mm) for now; add more rolls later.

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

## Windows Dev Tips
- You can validate layouts by opening the generated PDF locally (no printer needed).
- For end-to-end testing, run WSL2 with CUPS + a dummy printer, or share a Dymo from Windows and target it via `smb://` from WSL/CUPS. Keep production queue naming consistent (`dymo99010`).
