# Session Init - 2026-07-23 HP 430 G8 Network Boot Guidance

## Context

- The user is imaging an HP 430 G8 through a USB-to-RJ45 adapter and currently uses FOG.
- The intended future path is a direct iPXE and TFTP deployment service.
- The central question is how preboot USB-network support relates to operating-system drivers and UEFI network boot.

## Scope

- Verify the model's firmware boot capabilities and the role of a PXE-capable USB Ethernet adapter.
- Describe the firmware, DHCP, boot-file, and iPXE configuration needed for UEFI clients.
- Preserve all unrelated application and documentation changes already present in the worktree.

## Safety Constraints

- No application, database, deployment, or network-service configuration changes are authorized in this session.
- Provide a staged test procedure before recommending any replacement of the working FOG path.

## Outcome

- Confirmed from HP's T70-family firmware release information that current 430 G8 BIOS packages include a PXE UEFI driver.
- Identified HP USB-C to RJ45 Adapter G2 and HP USB 3.0 to Gigabit RJ45 Adapter G2 as vendor-documented PXE-capable adapter choices.
- Established the primary FOG path as x86-64 UEFI plus `snponly.efi`, with FOG's driver-specific USB Ethernet binary or a locally booted full iPXE EFI image reserved for matching chipsets and firmware gaps.
- Kept the later raw-iPXE design split between TFTP for the small first-stage EFI loader and HTTP for scripts and larger boot payloads.
- Added the no-OS firmware path: prepare the correct HP recovery/update USB on a second computer and apply it from Esc/F2 Hardware Diagnostics UEFI. The 430 G8 belongs to a Sure Start generation, so Windows+B crisis recovery is not the routine update mechanism.
- Extended the deployment design with an isolated provisioning VLAN, server-authorized wipe jobs, drive capability discovery, native NVMe/ATA sanitization, completion polling, audit reporting, and HTTP-native Linux/WinPE installer entries rather than generic large-ISO delivery over TFTP.
- Superseding correction: the organization uses YouWipe as its authoritative erasure product. Do not substitute HP Secure Erase or a custom sanitize agent. YouWipe publishes that network boot is supported through a vendor-supplied iPXE package, so the correct integration is to obtain and chain that package while preserving WipeCenter/licensing/reporting behavior.
- No application, database, deployment, or network configuration was changed.
