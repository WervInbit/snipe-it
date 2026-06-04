# Removed Catalog Attributes - 2026-06-02

These attribute keys are intentionally removed from the clean-start device catalog seed.

The rows are hidden and deprecated by `DeviceAttributeSeeder` when they already exist, instead of being hard-deleted, so future issues can still identify historical usage and replace it intentionally.

## Removed Keys

| Attribute key | Old purpose | Replacement path |
| --- | --- | --- |
| `audio_connectors_summary` | Dropdown/text summary of audio ports. | Expected port/audio components such as `3.5mm Audio Jack`. |
| `battery` | Test/presence boolean. | Workflow item plus expected Battery component where capacity is known. |
| `battery_capacity` | Mixed-unit text capacity. | `battery_capacity_wh` or `battery_capacity_mah` on Battery components; future scan/health comparison. |
| `battery_health_percent` | Asset-specific/calculated condition. | Future battery scan/health workflow or measured battery field. |
| `bluetooth` | Test/presence boolean. | Workflow item; future wireless capability/component if needed. |
| `charger_included` | Sale/accessory state. | Sale/accessory workflow or policy handling. |
| `charging_port_type` | Phone charging port enum. | Expected port component with `port_connector_type`. |
| `condition_grade` | Cosmetic/sale state as a custom attribute. | Asset `quality_grade`/sale workflow handling, not model-number spec. |
| `cpu` | Test/presence boolean. | Workflow item. |
| `display` | Test/presence boolean. | Workflow item plus expected Display component. |
| `ethernet` | Test/presence boolean. | RJ-45 expected port component plus workflow item. |
| `face_unlock` | Phone capability/test boolean. | Workflow item or future capability component if it becomes required. |
| `front_camera` | Test/presence boolean. | Camera workflow item plus expected camera components. |
| `front_camera_megapixels` | Single front camera spec. | Camera components using `camera_position`, `camera_role`, `camera_megapixels`. |
| `hdmi` | Test/presence boolean. | HDMI expected port component plus workflow item. |
| `included_accessories` | Sale/accessory text. | Sale/accessory workflow or policy handling. |
| `keyboard` | Test/presence boolean. | Workflow item plus Keyboard expected component. |
| `microphone` | Test/presence boolean. | Workflow item plus audio component. |
| `ram` | Test/presence boolean. | Workflow item plus RAM expected component. |
| `rear_camera` | Test/presence boolean. | Camera workflow item plus expected camera components. |
| `rear_camera_megapixels` | Single rear camera spec. | Multiple Camera components with camera role/megapixels. |
| `sd_card_reader` | Test/presence boolean. | SD Card Reader expected port component plus workflow item. |
| `speaker` | Test/presence boolean. | Workflow item plus audio component. |
| `storage` | Test/presence boolean. | Workflow item plus Storage expected component. |
| `touchpad` | Test/presence boolean. | Workflow item plus Touchpad expected component. |
| `usb_ports` | Test/presence boolean. | Expected port components plus workflow item. |
| `usb_ports_summary` | Versioned dropdown summary of USB/video/network ports. | Structured expected port components grouped by quantity. |
| `vga` | Test/presence boolean. | VGA expected port component plus workflow item. |
| `video_outputs_summary` | Dropdown/text summary of video outputs. | Expected HDMI/VGA/DisplayPort/USB-C capability components. |
| `warranty_months` | Sale/policy value incorrectly modeled as device spec. | Sale/policy handling. |
| `webcam` | Test/presence boolean. | Workflow item plus optional Webcam expected component. |
| `webcam_present` | Presence boolean. | Expected Webcam component or workflow applicability. |
| `wifi` | Test/presence boolean. | Workflow item; future wireless capability/component if needed. |

## Implementation Notes

- These keys remain in the raw historical blueprint arrays only as source context.
- `attributeBlueprints()` filters them out before seeding current definitions.
- `modelBlueprints()` filters them out before seeding model-number specs.
- `DeviceAttributeSeeder` hides/deprecates existing definitions and deactivates their options.
- Core Snipe-IT columns with similar names, such as asset/model warranty fields, are not removed by this cleanup.
