# Clean Catalog Mapping - 2026-05-28

> Historical planning record: the model-number verification decision has since
> changed. Five synthesized identifiers in this document are demo placeholders
> and are no longer created by normal production/additive seeding. See
> [`docs/catalog-model-number-verification.md`](../catalog-model-number-verification.md)
> for the current verified-versus-demo boundary and upgrade behavior.

## Scope

This document maps the current local production-work database catalog to a clean, seedable catalog foundation.

The target is a fresh database where users can be migrated separately, but assets, test runs, workflow photos, and old demo/example component rows are not migrated. Existing assets will be manually recreated against the new seed data.

No database mutation was performed for this investigation.

Removed catalog attribute keys are tracked separately in `docs/plans/catalog-removed-attributes-2026-06-02.md`.

Implementation status as of 2026-06-02: the clean attribute seed, component catalog seed, grouped expected quantities, model-number template seed, and workflow seed cleanup have been implemented in code. The live/dev MySQL database has not been migrated or reseeded.

## Source Snapshot

Source database checked: `snipeit_prod_work`.

Current counts:

| Data set | Count | Clean-start action |
| --- | ---: | --- |
| Users | 18 | Migrate separately, including auth/permission context as needed. |
| Assets | 7 | Do not migrate automatically. Recreate manually. |
| Models | 11 | Seed as real catalog models. |
| Model numbers | 11 | Seed as real catalog model numbers. |
| Model-number attributes | 411 | Remap into a smaller mix of model attributes and expected components. |
| Component definitions | 3 | Do not migrate; user confirmed these are examples/tests. |
| Model-number component templates | 0 | Seed new expected templates from the catalog mapping. |
| Component-definition subcomponent templates | 1 | Do not migrate; belongs to test/example component data. |

Historical candidate model numbers (apply the current verification policy before
using them in production):

| Manufacturer | Model | Model number | Label | Category |
| --- | --- | --- | --- | --- |
| HP | HP ProBook 450 G8 | `2E9F8EA#ABH` | HP ProBook 450 G8 - i5-1135G7 - 8GB - 256GB | Laptops |
| HP | HP ProBook 450 G7 | `8VU81EA#ABH` | HP ProBook 450 G7 - i5-10210U - 8GB - 256GB | Laptops |
| HP | HP ProBook 450 G6 | `5PP65EA#ABH` | HP ProBook 450 G6 - i5-8265U - 8GB - 256GB | Laptops |
| HP | HP ProBook 430 G7 | `8VT42EA#ABH` | HP ProBook 430 G7 - i5-10210U - 8GB - 256GB | Laptops |
| HP | HP ProBook 430 G6 | `5TK76EA#ABH` | HP ProBook 430 G6 - i5 - 8GB - 128GB | Laptops |
| HP | HP ProBook 430 G3 | `HP-430G3-I3-4-128` | HP ProBook 430 G3 - i3 - 4GB - 128GB | Laptops |
| Samsung | Samsung Galaxy A5 | `SM-A520F` | Samsung Galaxy A5 (2017) - 32GB - Zwart | Mobile Phones |
| Microsoft | Microsoft Surface Pro 4 | `MS-SURFPRO4-I5-4-128` | Microsoft Surface Pro 4 - i5 - 4GB - 128GB | Laptops |
| Microsoft | Microsoft Surface Pro 5 | `MS-SURFPRO5-I5-4-128` | Microsoft Surface Pro 5 - i5 - 4GB - 128GB | Laptops |
| Apple | iPhone 12 | `IP12-128-BLUE` | iPhone 12 - 128GB - Simlockvrij | Mobile Phones |
| Google | Pixel 8 Pro | `PIXEL8PRO-256-OBSIDIAN` | Pixel 8 Pro - 256GB - Obsidian | Mobile Phones |

Note: the existing code seed blueprint uses `SM-A520-32-BLACK` for Samsung Galaxy A5, but the live work-copy database uses `SM-A520F`. For this clean-start plan, the database value should win.

## System Constraints Checked

Expected component templates are stored in `model_number_component_templates`. They support:

- model number
- component definition
- expected name
- slot name
- expected quantity
- required flag
- sort order
- notes and metadata

They do not store per-model attribute values. If one model has 8GB DDR4 RAM and another has 4GB LPDDR3 RAM, those must either be separate generic component definitions or remain manual model-number attributes.

Component-definition attributes can contribute to effective specs through `component_definition_attributes`. Current aggregation only rolls up numeric component attributes when `resolves_to_spec=1` and the resolver is in spec-only mode. That means `ram_size_gb` and `storage_capacity_gb` can be calculated from expected/installed components, but non-numeric values such as `ram_type`, `storage_type`, `port_connector_type`, or `usb_standard` will not automatically appear in the main effective attribute list without a code change. They can still be shown on the component list/detail.

The asset component roster already reads expected model-number component templates and installed/custom tracked components. Seeding model-number templates should therefore make expected components visible on the asset component page.

The workflow migration is pending in the local work-copy database. The old `test_types` table still exists locally, while the code has moved toward workflow-compatible models and routes.

## Seed Architecture Recommendation

Split the current seed responsibilities before using this as the clean production foundation:

| Seeder | Responsibility |
| --- | --- |
| `DeviceAttributeSeeder` or renamed catalog attribute seeder | Seed only reusable model/component attributes. Remove present/test booleans and old summary dropdowns. |
| New component catalog seeder | Seed component categories, generic component definitions, and component-definition attributes. |
| New model-number catalog seeder | Seed the 11 real models/model numbers and attach expected component templates. |
| Workflow item/profile seeder | Seed workflow items and profiles without requiring product attributes for applicability. |
| Existing demo asset seeder | Do not call for clean-start production. Keep as optional demo-only data. |

`DatabaseSeeder` currently calls `DemoAssetsSeeder` by default. That is not suitable for the clean-start seed because it creates demo assets and mixes demo inventory with catalog setup.

## Attribute Mapping

| Current key | Current role | New target | Clean-start action |
| --- | --- | --- | --- |
| `release_year` | Model spec | Model-number attribute | Keep. |
| `cpu_model` | Laptop model spec | Model-number attribute | Keep. |
| `cpu_core_count` | Laptop model spec | Model-number attribute | Keep. |
| `gpu_model` | Laptop model spec | Model-number attribute | Keep. |
| `ram_size_gb` | Model spec and override | Component attribute and effective numeric spec | Keep definition. Prefer RAM component definitions/templates for base value. Allow asset override or tracked RAM instance attributes for corrections. |
| `ram_type` | Model spec | Component attribute, optionally manual model attribute | Keep definition. Because non-numeric component values do not roll up today, keep manual model-number assignment if the main attribute list must show it. |
| `ram_speed_mhz` | Missing today | Component attribute | Add. Optional until source data is known. |
| `storage_capacity_gb` | Model spec and override | Component attribute and effective numeric spec | Keep definition. Prefer storage component definitions/templates for base value. |
| `storage_type` | Model spec | Component attribute, optionally manual model attribute | Keep definition. Same non-numeric rollup limitation as RAM type. |
| `display_size_inches` | Model spec | Display component attribute or model attribute | Keep definition. If marked `resolves_to_spec`, only use one expected display per model or avoid numeric rollup because decimals are summed. |
| `display_resolution` | Model spec | Display component attribute, optionally manual model attribute | Keep definition. Non-numeric rollup does not happen today. |
| `display_panel_type` | Model spec | Display component attribute, optionally manual model attribute | Keep definition. |
| `display_refresh_rate_hz` | Model spec | Display component attribute or model attribute | Keep definition. Do not sum multiple displays unless that is intended. |
| `weight_kg` | Model spec | Model-number attribute | Keep. |
| `battery_capacity` | Mixed Wh/mAh text | Battery component attributes | Replace with numeric `battery_capacity_wh` and `battery_capacity_mah`; do not keep mixed-unit text as the foundation. |
| `battery_health_percent` | Asset-specific/calculated condition | Workflow result, future battery measurement, or asset-specific derived field | Remove from base catalog seed. Do not model as product spec. |
| `keyboard_layout` | Laptop spec | Keyboard component attribute or model-number attribute | Keep, but expand options. Current options are only `us`, `uk`, and `iso`; old seed data also used `qwerty` and `qwerty_us_intl`, which did not persist cleanly. |
| `webcam_present` | Presence boolean | Expected webcam component or workflow item | Remove. Presence is implied by expected component/template or by the workflow item being applicable. |
| `webcam` | Test/presence boolean | Workflow item plus optional Webcam Module component | Remove as product attribute. |
| `front_camera` | Test/presence boolean | Workflow item plus camera component | Remove as product attribute. |
| `rear_camera` | Test/presence boolean | Workflow item plus camera component | Remove as product attribute. |
| `front_camera_megapixels` | Phone model spec | Camera component attributes | Replace with generic camera components using `camera_position`, `camera_role`, and `camera_megapixels`. |
| `rear_camera_megapixels` | Phone model spec | Camera component attributes | Replace with generic camera components. This supports phones with multiple rear cameras. |
| `wifi` | Test/presence boolean | Workflow item and optional Wireless Module/Capability component | Remove as product attribute unless it is replaced by a real spec such as wireless standard. |
| `bluetooth` | Test/presence boolean | Workflow item and optional Wireless Module/Capability component | Remove as product attribute unless replaced by a real spec. |
| `speaker` | Test/presence boolean | Workflow item plus audio component | Remove as product attribute. |
| `microphone` | Test/presence boolean | Workflow item plus audio component | Remove as product attribute. |
| `display` | Test/presence boolean | Workflow item plus Display component | Remove as product attribute. |
| `battery` | Test/presence boolean | Workflow item plus Battery component | Remove as product attribute. |
| `ethernet` | Test/presence boolean | RJ-45 port component plus workflow item | Remove as product attribute. |
| `usb_ports` | Test/presence boolean | Port components plus workflow item | Remove as product attribute. |
| `sd_card_reader` | Test/presence boolean | SD card reader port/component plus workflow item | Remove as product attribute. |
| `hdmi` | Test/presence boolean | HDMI port component plus workflow item | Remove as product attribute. |
| `vga` | Test/presence boolean | VGA port component plus workflow item | Remove as product attribute. |
| `keyboard` | Test/presence boolean | Keyboard component plus workflow item | Remove as product attribute. |
| `touchpad` | Test/presence boolean | Touchpad component plus workflow item | Remove as product attribute. |
| `cpu` | Test/presence boolean | CPU workflow item, no product boolean | Remove as product attribute. |
| `ram` | Test/presence boolean | RAM workflow item, no product boolean | Remove as product attribute. |
| `storage` | Test/presence boolean | Storage workflow item, no product boolean | Remove as product attribute. |
| `usb_ports_summary` | Duplicated versioned summary dropdown | Port component templates | Remove. Current DB has three versions, including two active visible versions and assignments on a deprecated version. |
| `video_outputs_summary` | Summary dropdown/text | Port component templates | Remove. |
| `audio_connectors_summary` | Summary dropdown/text | Audio jack component/port template | Remove. |
| `charging_port_type` | Phone port enum | Port component template | Remove as product attribute or keep only as a derived display later. |
| `ip_rating` | Phone model spec | Model-number attribute | Keep. |
| `supports_5g` | Phone model spec | Model-number attribute or Wireless component attribute | Keep if it remains a real catalog spec. |
| `face_unlock` | Phone capability boolean | Model-number attribute or workflow item | Prefer workflow/capability item only if actually checked; otherwise remove from base catalog. |
| `os_family` | Model software family | Model-number attribute | Keep. |
| `os_version` | Default software version, overrideable | Model-number attribute with asset override | Keep, but expect manual asset override for upgraded/reinstalled devices. |
| `warranty_months` | Commercial/refurb policy | Sale/policy handling | Remove from device attribute seed. It is not hardware and should not live on the device catalog foundation. |
| `condition_grade` | Sale/cosmetic state | Asset-level field/attribute or pre-sale workflow | Do not seed on model numbers. Existing asset override for `INBIT-FU0001` can be reentered manually. |
| `color` | Model appearance | Model-number attribute | Keep. |
| `included_accessories` | Sale/shipping/accessory state | Workflow/profile task or asset note | Do not seed on model numbers unless used as expected sale bundle. |
| `charger_included` | Sale/accessory state | Workflow/profile task or asset-level state | Do not seed on model numbers. |
| `imei_1`, `imei_2` | Asset identifiers | Asset fields/overrides | Do not seed on model numbers. Reenter manually per phone asset if needed. |

## New Attribute Definitions To Seed

Port/component attributes:

| Key | Type | Applies to | Notes |
| --- | --- | --- | --- |
| `port_connector_type` | enum | Ports | Values such as `usb_a`, `usb_c`, `hdmi`, `mini_displayport`, `displayport`, `vga`, `rj45`, `sd_card`, `audio_3_5mm`, `surface_connect`, `lightning`. |
| `usb_standard` | enum | USB ports | Values such as `usb_2_0`, `usb_3_0`, `usb_3_1_gen1`, `usb_3_1_gen2`, `usb_3_2_gen1`, `usb4`. |
| `displayport_alt_mode` | bool | USB-C ports | Separate from connector and USB standard. |
| `displayport_version` | text or enum | USB-C/DisplayPort | Example: `1.4`. |
| `power_delivery` | bool | USB-C ports | Separate from connector and USB standard. |
| `power_delivery_watts` | int | USB-C/power ports | Optional. |
| `thunderbolt` | bool | USB-C ports | Separate attribute as requested. |
| `thunderbolt_version` | enum or int | USB-C ports | Optional until data exists. |
| `hdmi_version` | text or enum | HDMI ports | Example: `1.4`, `1.4b`. |
| `sleep_and_charge` | bool | USB-A ports | Needed for the HP ProBook 450 G8 USB-A Sleep/Charge port. |
| `ram_speed_mhz` | int | Memory | Optional, because current data does not include speeds. |
| `battery_capacity_wh` | decimal | Laptop batteries | Replaces mixed text capacity for laptops/tablets. |
| `battery_capacity_mah` | int | Phone batteries | Replaces mixed text capacity for phones. |
| `camera_position` | enum | Cameras | Values `front`, `rear`, `webcam`. |
| `camera_role` | enum | Cameras | Values such as `selfie`, `main`, `wide`, `ultrawide`, `telephoto`, `macro`, `depth`. |
| `camera_megapixels` | decimal/int | Cameras | Replaces separate front/rear megapixel attributes. |

Component categories to seed:

- Memory
- Storage
- Display
- Battery
- Input
- Camera
- Audio
- Network
- Ports
- Power

Current database only has a `RAM` component category, so the clean catalog needs a broader set.

## Component Definition Catalog

Use generic, spec-specific component definitions. Avoid brand-specific rows unless the physical part must be tracked by exact manufacturer/part number.

Base definitions inferred from current model data:

| Category | Component definitions |
| --- | --- |
| Memory | RAM 3GB LPDDR4X; RAM 4GB DDR4; RAM 4GB LPDDR3; RAM 4GB LPDDR4X; RAM 8GB DDR4; RAM 12GB LPDDR5X |
| Storage | Storage 32GB UFS; Storage 128GB SATA SSD; Storage 128GB NVMe; Storage 128GB UFS; Storage 256GB NVMe; Storage 256GB UFS |
| Display | Display 15.6 FHD IPS 60Hz; Display 13.3 FHD IPS 60Hz; Display 13.3 HD TN 60Hz; Display 12.3 2736x1824 IPS 60Hz; Display 5.2 FHD AMOLED 60Hz; Display 6.1 2532x1170 OLED 60Hz; Display 6.7 2992x1344 OLED 120Hz |
| Battery | Battery 45 Wh; Battery 38 Wh; Battery 3000 mAh; Battery 2815 mAh; Battery 5050 mAh |
| Input | Keyboard US; Keyboard QWERTY; Keyboard US International; Touchpad |
| Camera | Webcam Module; Camera - Selfie - 10MP; Camera - Selfie - 12MP; Camera - Selfie - 16MP; Camera - Main - 12MP; Camera - Main - 16MP; Camera - Main - 50MP; Camera - Ultrawide - 12MP; Camera - Ultrawide - 48MP; Camera - Telephoto - 48MP |
| Audio | Speaker; Microphone; 3.5mm Audio Jack |
| Network | Wireless Module or Wireless Capability; RJ-45 Ethernet Port |
| Ports | USB-A Port - USB 2.0; USB-A Port - USB 3.0; USB-A Port - USB 3.1 Gen1; USB-A Port - USB 3.2 Gen1; USB-C Port - USB 3.1 Gen1 - DP Alt - PD; USB-C Port - USB 3.1 Gen2 - DP 1.4 Alt - PD; USB-C Port - USB 3.2 Gen1 - DP Alt - PD; HDMI Port - 1.4; HDMI Port - 1.4b; VGA Port; SD Card Reader; Mini DisplayPort; Surface Connect Port; Lightning Port |

If these definitions become too granular, the alternative is a smaller definition set plus instance/template metadata. The current schema makes definition-level attributes easier to seed and display than template-level attributes.

For repeated ports, prefer one expected component template with `expected_qty` instead of duplicating the same template several times. The UI should display repeated expected ports as a grouped row such as `USB-A Port - USB 3.1 Gen1 x3` where practical, while still allowing individual tracked component instances when a specific port is damaged, replaced, or needs notes.

## Per-Model Catalog Mapping

Keep model-number attributes for stable non-component facts:

- release year
- CPU model/core count
- GPU model
- weight
- color
- OS family/version
- IP rating and 5G where applicable

Move repeatable/replaceable/inspectable hardware into expected component templates.

### HP ProBook 450 G8 - `2E9F8EA#ABH`

Manual model attributes:

- 2021, Intel Core i5-1135G7, 4 cores, Intel Iris Xe Graphics
- weight 1.74kg, color Pike Silver
- OS Windows 11 Pro, warranty 12 months if policy is kept

Expected components:

- RAM 8GB DDR4, qty 1
- Storage 256GB NVMe, qty 1
- Display 15.6 FHD IPS 60Hz, qty 1
- Battery 45 Wh, qty 1
- Keyboard US, qty 1
- Touchpad, qty 1
- Webcam Module, qty 1
- Speaker, qty 1
- Microphone, qty 1
- Wireless Module or Capability, qty 1
- USB-A Port - USB 3.1 Gen1 - Sleep/Charge, qty 1
- USB-A Port - USB 3.1 Gen1, qty 2
- USB-C Port - USB 3.1 Gen2 - DP 1.4 Alt - PD, qty 1
- HDMI Port - 1.4b, qty 1
- 3.5mm Audio Jack, qty 1

Manual asset recreation notes:

- `INBIT-QI0001` currently overrides RAM to 16GB, battery capacity to 35, and OS version to Windows 13. In the new model, recreate this as either a tracked RAM component/instance override and a battery component/instance note/attribute, plus OS override.

### HP ProBook 450 G7 - `8VU81EA#ABH`

Expected components:

- RAM 8GB DDR4
- Storage 256GB NVMe
- Display 15.6 FHD IPS 60Hz
- Battery 45 Wh
- Keyboard US
- Touchpad
- Webcam Module
- Speaker
- Microphone
- Wireless Module or Capability
- USB-A Port - USB 3.1 Gen1, qty 2
- USB-C Port - USB 3.1 Gen1 - DP Alt - PD, qty 1
- HDMI Port - 1.4b, qty 1
- 3.5mm Audio Jack, qty 1

### HP ProBook 450 G6 - `5PP65EA#ABH`

Expected components:

- RAM 8GB DDR4
- Storage 256GB NVMe
- Display 15.6 FHD IPS 60Hz
- Battery 45 Wh
- Keyboard US
- Touchpad
- Webcam Module
- Speaker
- Microphone
- Wireless Module or Capability
- USB-A Port - USB 3.2 Gen1, qty 2
- USB-C Port - USB 3.2 Gen1 - DP Alt - PD, qty 1
- HDMI Port - 1.4, qty 1
- 3.5mm Audio Jack, qty 1

### HP ProBook 430 G7 - `8VT42EA#ABH`

Expected components:

- RAM 8GB DDR4
- Storage 256GB NVMe
- Display 13.3 FHD IPS 60Hz
- Battery 45 Wh
- Keyboard US
- Touchpad
- Webcam Module
- Speaker
- Microphone
- Wireless Module or Capability
- USB-A Port - USB 3.1 Gen1, qty 2
- USB-C Port - USB 3.1 Gen1 - DP Alt - PD, qty 1
- HDMI Port - 1.4b, qty 1
- 3.5mm Audio Jack, qty 1

### HP ProBook 430 G6 - `5TK76EA#ABH`

Expected components:

- RAM 8GB DDR4
- Storage 128GB NVMe
- Display 13.3 FHD IPS 60Hz
- Battery 45 Wh
- Keyboard QWERTY
- Touchpad
- Webcam Module
- Speaker
- Microphone
- Wireless Module or Capability
- USB-A Port - USB 3.2 Gen1, qty 2
- USB-A Port - USB 2.0, qty 1
- USB-C Port - USB-C Gen1 - DisplayPort, qty 1
- HDMI Port - 1.4, qty 1
- RJ-45 Ethernet Port, qty 1
- SD Card Reader, qty 1
- 3.5mm Audio Jack, qty 1

Manual asset recreation notes:

- `INBIT-PJ0001` had an asset override for `audio_connectors_summary=3,5 mm audio`. In the new catalog this should be covered by the expected 3.5mm Audio Jack component unless there is a device-specific exception.

### HP ProBook 430 G3 - `HP-430G3-I3-4-128`

Expected components:

- RAM 4GB DDR4
- Storage 128GB SATA SSD
- Display 13.3 HD TN 60Hz
- Keyboard US International
- Touchpad
- Webcam Module
- Speaker
- Microphone
- Wireless Module or Capability
- USB-A Port - USB 3.2 Gen1, qty 2
- USB-A Port - USB 2.0, qty 1
- HDMI Port - 1.4, qty 1
- VGA Port, qty 1
- RJ-45 Ethernet Port, qty 1
- 3.5mm Audio Jack, qty 1

Battery capacity is absent in the current live attribute assignment. Seed a battery template only after confirming the expected capacity.

### Microsoft Surface Pro 4 - `MS-SURFPRO4-I5-4-128`

Expected components:

- RAM 4GB LPDDR3
- Storage 128GB NVMe
- Display 12.3 2736x1824 IPS 60Hz
- Battery 38 Wh
- Type Cover is a sale accessory workflow item, not an expected hardware component
- Webcam Module
- Speaker
- Microphone
- Wireless Module or Capability
- USB-A Port - USB 3.0, qty 1
- Mini DisplayPort, qty 1
- Surface Connect Port, qty 1
- 3.5mm Audio Jack, qty 1

### Microsoft Surface Pro 5 - `MS-SURFPRO5-I5-4-128`

Expected components:

- RAM 4GB LPDDR3
- Storage 128GB NVMe
- Display 12.3 2736x1824 IPS 60Hz
- Battery 45 Wh
- Type Cover is a sale accessory workflow item, not an expected hardware component
- Webcam Module
- Speaker
- Microphone
- Wireless Module or Capability
- USB-A Port - USB 3.0, qty 1
- Mini DisplayPort, qty 1
- Surface Connect Port, qty 1
- 3.5mm Audio Jack, qty 1

### Samsung Galaxy A5 - `SM-A520F`

Expected components:

- RAM 3GB LPDDR4X
- Storage 32GB UFS
- Display 5.2 FHD AMOLED 60Hz
- Battery 3000 mAh
- Camera - Selfie - 16MP
- Camera - Main - 16MP
- Speaker
- Microphone
- Wireless Module or Capability
- USB-C charging/data port, qty 1

Keep as model attributes:

- Android 8.0
- color Black Sky
- IP68
- `supports_5g=false`

### iPhone 12 - `IP12-128-BLUE`

Expected components:

- RAM 4GB LPDDR4X
- Storage 128GB UFS
- Display 6.1 2532x1170 OLED 60Hz
- Battery 2815 mAh
- Camera - Selfie - 12MP
- Camera - Main - 12MP
- Camera - Ultrawide - 12MP
- Speaker
- Microphone
- Wireless Module or Capability
- Lightning Port, qty 1

Keep as model attributes:

- iOS 18
- color Pacific Blue
- IP68
- `supports_5g=true`

### Pixel 8 Pro - `PIXEL8PRO-256-OBSIDIAN`

Expected components:

- RAM 12GB LPDDR5X
- Storage 256GB UFS
- Display 6.7 2992x1344 OLED 120Hz
- Battery 5050 mAh
- Camera - Selfie - 10MP
- Camera - Main - 50MP
- Camera - Ultrawide - 48MP
- Camera - Telephoto - 48MP
- Speaker
- Microphone
- Wireless Module or Capability
- USB-C charging/data port, qty 1

Keep as model attributes:

- Android 14
- color Obsidian
- IP68
- `supports_5g=true`

## Workflow/Test Mapping

The old `test_types` table currently contains attribute-linked checks such as battery, bluetooth, ethernet, RAM, HDMI, camera, speaker, microphone, storage, display, keyboard, touchpad, USB ports, VGA, webcam, and Wi-Fi.

Clean-start direction:

| Old test item | New workflow item | Attribute link |
| --- | --- | --- |
| Battery | Battery check | Prefer no attribute link. Expected component makes presence visible. |
| Bluetooth | Bluetooth check | No product attribute link unless a real Bluetooth spec exists. |
| CPU | CPU stress/check | No product boolean. |
| RAM | Memory check | Optional expected-value snapshot from `ram_size_gb`; do not require a `ram` boolean. |
| Storage | Storage health/wipe check | Optional expected-value snapshot from `storage_capacity_gb`; do not require a `storage` boolean. |
| Display | Display check | No `display` boolean. |
| HDMI/VGA/USB/Ethernet/SD | Port checks | No product booleans. Applicability should come from category/profile first, and later from expected port templates if needed. |
| Webcam/front camera/rear camera | Camera checks | No present booleans. |
| Case/quality/sale photos | Pre-sale profile items | Keep outside standard diagnostics. |
| Cleaning | Cleaning profile items | Use done/not-done labels. |
| Shipping laptop | Shipping workflow profile | Use done/not-done labels. |

Profiles to seed:

| Profile | Purpose | Blocks sale readiness | Label mode |
| --- | --- | --- | --- |
| Standard Diagnostics | Normal refurbishment checks only | Yes | Pass/fail |
| Pre-Sale Check | Final sale evidence and quality checks | Yes | Pass/fail |
| Cleaning | Cleaning tasks | No | Done/not done |
| Shipping Laptop | Packing/shipping task list | No | Done/not done |

Important: product attributes should not be created only to make a workflow item apply. Keep workflow applicability category/profile based first. A later enhancement can derive port-specific workflow applicability from expected components.

## Manual Asset Recreation Reference

Current assets to recreate manually if still needed:

| Asset tag | Serial | Current model number | Notes |
| --- | --- | --- | --- |
| `INBIT-QI0001` | `5CD120L5MB` | `2E9F8EA#ABH` | Has RAM 16GB, battery capacity 35, OS version Windows 13 overrides. |
| `INBIT-PJ0001` | `Test` | `5TK76EA#ABH` | Audio summary override should become expected audio jack unless exceptional. |
| `INBIT-CG0001` | `R58K12WQRXX` | `SM-A520F` | No special override found. |
| `INBIT-FU0001` | `5CD048P8XH` | `2E9F8EA#ABH` | `condition_grade=grade_b` override. Reenter as asset/sale state. |
| `INBIT-ZX0001` | `R58J71KYEBT` | `SM-A520F` | No special override found. |
| `INBIT-ZM0001` | `5CD115BH8P` | `2E9F8EA#ABH` | No special override found. |
| `INBIT-XR0001` | `5CD14FDX3` | `2E9F8EA#ABH` | No special override found. |

## Implementation Blocks

Recommended order:

1. Clean seed definitions.
   - Remove present/test booleans and summary dropdowns from the clean base attribute seed.
   - Add port/component attributes.
   - Decide whether non-numeric component attributes should remain component-only or also be manually assigned to model numbers.

2. Component catalog seeder.
   - Add component categories.
   - Seed generic component definitions and definition attributes.
   - Use `resolves_to_spec=1` only for numeric values that should roll up, such as RAM size and storage capacity.
   - Use template quantities for repeated ports/components rather than duplicate template rows.

3. Model/model-number catalog seeder.
   - Seed the 11 real model numbers from the current database.
   - Seed expected component templates.
   - Avoid creating assets.

4. Workflow seed cleanup.
   - Rename clean seed classes/data to workflows where appropriate.
   - Seed workflow items and profiles.
   - Remove product-attribute dependency for present-style checks.

5. UI/behavior follow-up.
   - Browser-check expected components on an asset page.
   - Browser-check model-number expected component settings.
   - Browser-check workflow profile selection when starting a workflow.
   - Add grouped quantity display for repeated expected components/ports where the current roster would otherwise show repeated identical rows.
   - Add or adjust tests for clean seeds and stale page assertions.

6. Local work-copy database rehearsal.
   - Back up `snipeit_prod_work` first.
   - Run pending migrations and seed rehearsal only after explicit approval.
   - Confirm manual recreation path against the fresh seed.

## Self-Review

This plan should work for reseeding a fresh database if the implementation keeps the seed split strict: catalog data only in the catalog seeders, no demo assets, no old test runs/photos, and no example component definitions.

The biggest technical pitfall is assuming component attributes fully replace model-number attributes today. They do not. Numeric values can roll up through the current resolver when marked `resolves_to_spec`, but enum/text values remain component-list/detail data unless the resolver is extended. For the first implementation, keep critical non-numeric specs such as RAM type, storage type, display resolution/type, and OS details as manual model-number attributes if they must appear in the main attributes table.

The second pitfall is over-modeling integrated parts. Ports, RAM, storage, battery, display, camera, keyboard, touchpad, and audio can all be expected components, but not every one needs to be a tracked physical instance on every asset. Expected templates are enough for baseline display; tracked component instances should be created only when a part is replaced, removed, moved, or needs specific notes/photos/history.

The third pitfall is making workflow items depend on product attributes again. Standard diagnostics, pre-sale checks, cleaning, and shipping can share the existing run/result/photo/note UI without forcing `*_present` attributes back into the catalog.

Open decisions before implementation:

- Should the main attributes panel continue to show RAM type/storage type/display details from manual model-number attributes, or should the resolver be expanded to surface non-numeric component-derived values?
- HP ProBook 430 G3 battery capacity is intentionally deferred. Expected capacity versus current capacity should come later from actual battery scan/health data.
- `warranty_months` is resolved: move it to sale/policy handling, not device attributes.

## Browser Check

Browser smoke check result on 2026-05-28:

- `https://dev.inbit` loads the local app and reaches the Snipe-IT login page.
- Direct `http://localhost` and `http://127.0.0.1` navigation was blocked by the in-app browser client, but `APP_URL=https://dev.inbit` works.
- A protected asset route redirects to login with the current browser session.
- Internal component/workflow pages were not verified in-browser because no authenticated browser session or test credentials were available, and no temporary login/user/database mutation was created during this investigation.
- The local work-copy database still has the workflow migration pending, so a full browser verification of workflow profile UI should happen after backup, migration, and an authenticated session.
