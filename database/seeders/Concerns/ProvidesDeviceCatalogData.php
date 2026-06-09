<?php

namespace Database\Seeders\Concerns;

use App\Models\AssetModel;
use App\Models\AttributeDefinition;
use App\Models\Category;
use App\Models\Manufacturer;

trait ProvidesDeviceCatalogData
{
    /**
     * Attribute definitions used across laptops/phones.
     *
     * @return array<string,array<string,mixed>>
     */
    protected function attributeBlueprints(): array
    {
        $blueprints = [
            'release_year' => [
                'label' => 'Introductiejaar',
                'datatype' => AttributeDefinition::DATATYPE_INT,
                'categories' => ['Laptops', 'Mobile Phones'],
                'constraints' => ['min' => 2005, 'max' => (int) date('Y'), 'step' => 1],
                'required' => true,
            ],
            'cpu_model' => [
                'label' => 'Processor',
                'datatype' => AttributeDefinition::DATATYPE_TEXT,
                'categories' => ['Laptops'],
                'required' => true,
            ],
            'cpu_core_count' => [
                'label' => 'Processorkernen',
                'datatype' => AttributeDefinition::DATATYPE_INT,
                'categories' => ['Laptops'],
                'constraints' => ['min' => 2, 'max' => 32, 'step' => 1],
            ],
            'gpu_model' => [
                'label' => 'Grafische kaart',
                'datatype' => AttributeDefinition::DATATYPE_TEXT,
                'categories' => ['Laptops'],
            ],
            'ram_size_gb' => [
                'label' => 'Werkgeheugen',
                'datatype' => AttributeDefinition::DATATYPE_INT,
                'unit' => 'GB',
                'categories' => ['Laptops', 'Mobile Phones'],
                'constraints' => ['min' => 1, 'max' => 64, 'step' => 1],
                'required' => true,
                'allow_asset_override' => true,
            ],
            'ram_type' => [
                'label' => 'Geheugentype',
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
                'categories' => ['Laptops', 'Mobile Phones'],
                'options' => [
                    ['value' => 'ddr4', 'label' => 'DDR4'],
                    ['value' => 'ddr5', 'label' => 'DDR5'],
                    ['value' => 'lpddr4x', 'label' => 'LPDDR4X'],
                    ['value' => 'lpddr5', 'label' => 'LPDDR5'],
                    ['value' => 'lpddr5x', 'label' => 'LPDDR5X'],
                    ['value' => 'lpddr3', 'label' => 'LPDDR3'],
                ],
            ],
            'storage_capacity_gb' => [
                'label' => 'Opslagcapaciteit',
                'datatype' => AttributeDefinition::DATATYPE_INT,
                'unit' => 'GB',
                'categories' => ['Laptops', 'Mobile Phones'],
                'constraints' => ['min' => 16, 'max' => 4096, 'step' => 1],
                'required' => true,
                'allow_asset_override' => true,
            ],
            'storage_type' => [
                'label' => 'Opslagtype',
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
                'categories' => ['Laptops', 'Mobile Phones'],
                'options' => [
                    ['value' => 'nvme', 'label' => 'NVMe-SSD'],
                    ['value' => 'ssd', 'label' => 'SATA-SSD'],
                    ['value' => 'hdd', 'label' => 'Harde schijf'],
                    ['value' => 'ufs', 'label' => 'UFS-opslag'],
                ],
            ],
            'display_size_inches' => [
                'label' => 'Schermgrootte',
                'datatype' => AttributeDefinition::DATATYPE_DECIMAL,
                'unit' => 'in',
                'categories' => ['Laptops', 'Mobile Phones'],
                'constraints' => ['min' => 3.0, 'max' => 18.0, 'step' => 0.1],
            ],
            'display_resolution' => [
                'label' => 'Schermresolutie',
                'datatype' => AttributeDefinition::DATATYPE_TEXT,
                'categories' => ['Laptops', 'Mobile Phones'],
            ],
            'display_panel_type' => [
                'label' => 'Schermtechnologie',
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
                'categories' => ['Laptops', 'Mobile Phones'],
                'options' => [
                    ['value' => 'ips', 'label' => 'IPS'],
                    ['value' => 'oled', 'label' => 'OLED'],
                    ['value' => 'amoled', 'label' => 'AMOLED'],
                    ['value' => 'tn', 'label' => 'TN'],
                ],
            ],
            'display_refresh_rate_hz' => [
                'label' => 'Verversingssnelheid',
                'datatype' => AttributeDefinition::DATATYPE_INT,
                'unit' => 'Hz',
                'categories' => ['Laptops', 'Mobile Phones'],
                'constraints' => ['min' => 30, 'max' => 240, 'step' => 1],
            ],
            'weight_kg' => [
                'label' => 'Gewicht',
                'datatype' => AttributeDefinition::DATATYPE_DECIMAL,
                'unit' => 'kg',
                'categories' => ['Laptops'],
                'constraints' => ['min' => 0.5, 'max' => 5.0, 'step' => 0.01],
            ],
            'battery_capacity' => [
                'label' => 'Batterijcapaciteit',
                'datatype' => AttributeDefinition::DATATYPE_TEXT,
                'categories' => ['Laptops', 'Mobile Phones'],
                'allow_asset_override' => true,
                'allow_custom_values' => true,
                'required' => true,
            ],
            'battery_health_percent' => [
                'label' => 'Batterijgezondheid',
                'datatype' => AttributeDefinition::DATATYPE_INT,
                'unit' => '%',
                'categories' => ['Laptops', 'Mobile Phones'],
                'constraints' => ['min' => 0, 'max' => 100, 'step' => 1],
                'allow_asset_override' => true,
            ],
            'keyboard_layout' => [
                'label' => 'Toetsenbordindeling',
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
                'categories' => ['Laptops'],
                'options' => [
                    ['value' => 'us', 'label' => 'US (ANSI)'],
                    ['value' => 'uk', 'label' => 'UK'],
                    ['value' => 'iso', 'label' => 'EU (ISO)'],
                    ['value' => 'qwerty', 'label' => 'QWERTY'],
                    ['value' => 'qwerty_us_intl', 'label' => 'QWERTY US International'],
                ],
            ],
            'webcam_present' => [
                'label' => 'Webcam aanwezig',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Laptops'],
            ],
            'supports_5g' => [
                'label' => '5G-ondersteuning',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Mobile Phones'],
            ],
            'rear_camera_megapixels' => [
                'label' => 'Hoofdcamera',
                'datatype' => AttributeDefinition::DATATYPE_INT,
                'unit' => 'MP',
                'categories' => ['Mobile Phones'],
                'constraints' => ['min' => 2, 'max' => 200, 'step' => 1],
            ],
            'front_camera_megapixels' => [
                'label' => 'Selfiecamera',
                'datatype' => AttributeDefinition::DATATYPE_INT,
                'unit' => 'MP',
                'categories' => ['Mobile Phones'],
                'constraints' => ['min' => 2, 'max' => 64, 'step' => 1],
            ],
            'os_family' => [
                'label' => 'Besturingssysteem',
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
                'categories' => ['Laptops', 'Mobile Phones'],
                'options' => [
                    ['value' => 'windows', 'label' => 'Windows'],
                    ['value' => 'macos', 'label' => 'macOS'],
                    ['value' => 'chromeos', 'label' => 'ChromeOS'],
                    ['value' => 'linux', 'label' => 'Linux'],
                    ['value' => 'android', 'label' => 'Android'],
                    ['value' => 'ios', 'label' => 'iOS'],
                ],
            ],
            'os_version' => [
                'label' => 'OS-versie',
                'datatype' => AttributeDefinition::DATATYPE_TEXT,
                'categories' => ['Laptops', 'Mobile Phones'],
                'allow_asset_override' => true,
            ],
            'warranty_months' => [
                'label' => 'Garantie',
                'datatype' => AttributeDefinition::DATATYPE_INT,
                'unit' => 'maanden',
                'categories' => ['Laptops', 'Mobile Phones'],
                'constraints' => ['min' => 0, 'max' => 36, 'step' => 1],
            ],
            'condition_grade' => [
                'label' => 'Cosmetische staat',
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
                'categories' => ['Laptops', 'Mobile Phones'],
                'options' => [
                    ['value' => 'grade_a', 'label' => 'Kwaliteit A'],
                    ['value' => 'grade_b', 'label' => 'Kwaliteit B'],
                    ['value' => 'grade_c', 'label' => 'Kwaliteit C'],
                    ['value' => 'grade_d', 'label' => 'Kwaliteit D'],
                ],
                'allow_asset_override' => true,
            ],
            'color' => [
                'label' => 'Kleur',
                'datatype' => AttributeDefinition::DATATYPE_TEXT,
                'categories' => ['Laptops', 'Mobile Phones'],
            ],
            'usb_ports_summary' => [
                'label' => 'USB-aansluitingen',
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
                'categories' => ['Laptops'],
                'options' => [
                    ['value' => '2x USB-A 3.2, 1x USB-C, HDMI 1.4b, RJ-45', 'label' => '2x USB-A 3.2, 1x USB-C, HDMI 1.4b, RJ-45'],
                    ['value' => '2x USB-A 3.1, 1x USB-C, HDMI 1.4b, RJ-45', 'label' => '2x USB-A 3.1, 1x USB-C, HDMI 1.4b, RJ-45'],
                    ['value' => '2x USB-A 3.1, 1x USB-C, HDMI 1.4b', 'label' => '2x USB-A 3.1, 1x USB-C, HDMI 1.4b'],
                    ['value' => '2x USB-A 3.0, 1x USB-A 2.0, VGA, HDMI', 'label' => '2x USB-A 3.0, 1x USB-A 2.0, VGA, HDMI'],
                    ['value' => '1x USB-A 3.0, Mini DisplayPort, Surface Connect', 'label' => '1x USB-A 3.0, Mini DisplayPort, Surface Connect'],
                ],
                'allow_asset_override' => true,
            ],
            'video_outputs_summary' => [
                'label' => 'Video-uitgangen',
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
                'categories' => ['Laptops'],
                'options' => [
                    ['value' => 'HDMI 1.4b, USB-C DisplayPort', 'label' => 'HDMI 1.4b, USB-C DisplayPort'],
                    ['value' => 'HDMI 1.4, VGA', 'label' => 'HDMI 1.4, VGA'],
                    ['value' => 'Mini DisplayPort', 'label' => 'Mini DisplayPort'],
                ],
                'allow_asset_override' => true,
            ],
            'audio_connectors_summary' => [
                'label' => 'Audio-aansluitingen',
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
                'categories' => ['Laptops'],
                'options' => [
                    ['value' => '3,5 mm audio', 'label' => '3,5 mm audio'],
                ],
                'allow_asset_override' => true,
            ],
            'included_accessories' => [
                'label' => 'Meegeleverde accessoires',
                'datatype' => AttributeDefinition::DATATYPE_TEXT,
                'categories' => ['Laptops', 'Mobile Phones'],
                'allow_custom_values' => true,
                'allow_asset_override' => true,
            ],
            'charger_included' => [
                'label' => 'Oplader meegeleverd',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Laptops', 'Mobile Phones'],
                'allow_asset_override' => true,
            ],
            'charging_port_type' => [
                'label' => 'Laadpoort',
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
                'categories' => ['Mobile Phones'],
                'options' => [
                    ['value' => 'usb-c', 'label' => 'USB-C'],
                    ['value' => 'lightning', 'label' => 'Lightning'],
                    ['value' => 'mag-safe', 'label' => 'MagSafe'],
                ],
            ],
            'ip_rating' => [
                'label' => 'IP-classificatie',
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
                'categories' => ['Mobile Phones'],
                'options' => [
                    ['value' => 'ip68', 'label' => 'IP68'],
                    ['value' => 'ip67', 'label' => 'IP67'],
                    ['value' => 'ip54', 'label' => 'IP54'],
                ],
            ],
            'ram_speed_mhz' => [
                'label' => 'Geheugensnelheid',
                'datatype' => AttributeDefinition::DATATYPE_INT,
                'unit' => 'MHz',
                'categories' => ['Memory'],
                'constraints' => ['min' => 400, 'max' => 10000, 'step' => 1],
            ],
            'battery_capacity_wh' => [
                'label' => 'Batterijcapaciteit',
                'datatype' => AttributeDefinition::DATATYPE_DECIMAL,
                'unit' => 'Wh',
                'categories' => ['Battery'],
                'constraints' => ['min' => 1, 'max' => 200, 'step' => 0.1],
                'allow_asset_override' => true,
            ],
            'battery_capacity_mah' => [
                'label' => 'Batterijcapaciteit',
                'datatype' => AttributeDefinition::DATATYPE_INT,
                'unit' => 'mAh',
                'categories' => ['Battery'],
                'constraints' => ['min' => 100, 'max' => 20000, 'step' => 1],
                'allow_asset_override' => true,
            ],
            'camera_position' => [
                'label' => 'Camerapositie',
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
                'categories' => ['Camera'],
                'options' => [
                    ['value' => 'front', 'label' => 'Front'],
                    ['value' => 'rear', 'label' => 'Rear'],
                    ['value' => 'webcam', 'label' => 'Webcam'],
                ],
            ],
            'camera_role' => [
                'label' => 'Camerarol',
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
                'categories' => ['Camera'],
                'options' => [
                    ['value' => 'selfie', 'label' => 'Selfie'],
                    ['value' => 'main', 'label' => 'Main'],
                    ['value' => 'wide', 'label' => 'Wide'],
                    ['value' => 'ultrawide', 'label' => 'Ultrawide'],
                    ['value' => 'telephoto', 'label' => 'Telephoto'],
                    ['value' => 'macro', 'label' => 'Macro'],
                    ['value' => 'depth', 'label' => 'Depth'],
                ],
            ],
            'camera_megapixels' => [
                'label' => 'Camera',
                'datatype' => AttributeDefinition::DATATYPE_DECIMAL,
                'unit' => 'MP',
                'categories' => ['Camera'],
                'constraints' => ['min' => 0.1, 'max' => 250, 'step' => 0.1],
            ],
            'port_connector_type' => [
                'label' => 'Poortconnector',
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
                'component_spec_display_mode' => AttributeDefinition::COMPONENT_SPEC_DISPLAY_COMPONENT_LABELS,
                'categories' => ['Ports'],
                'options' => [
                    ['value' => 'usb_a', 'label' => 'USB-A'],
                    ['value' => 'usb_c', 'label' => 'USB-C'],
                    ['value' => 'hdmi', 'label' => 'HDMI'],
                    ['value' => 'displayport', 'label' => 'DisplayPort'],
                    ['value' => 'mini_displayport', 'label' => 'Mini DisplayPort'],
                    ['value' => 'vga', 'label' => 'VGA'],
                    ['value' => 'rj45', 'label' => 'RJ-45'],
                    ['value' => 'esata', 'label' => 'eSATA'],
                    ['value' => 'sd_card', 'label' => 'SD Card'],
                    ['value' => 'audio_3_5mm', 'label' => '3.5mm'],
                    ['value' => 'surface_connect', 'label' => 'Surface Connect'],
                    ['value' => 'lightning', 'label' => 'Lightning'],
                ],
            ],
            'audio_port_role' => [
                'label' => 'Audiopoortfunctie',
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
                'categories' => ['Ports'],
                'options' => [
                    ['value' => 'headset_combo', 'label' => 'Headset combo'],
                    ['value' => 'headphone_out', 'label' => 'Hoofdtelefoon uitgang'],
                    ['value' => 'microphone_in', 'label' => 'Microfoon ingang'],
                    ['value' => 'line_in', 'label' => 'Line in'],
                    ['value' => 'line_out', 'label' => 'Line out'],
                ],
                'allow_custom_values' => true,
            ],
            'audio_jack_standard' => [
                'label' => 'Audiojack-standaard',
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
                'categories' => ['Ports'],
                'options' => [
                    ['value' => 'trs', 'label' => 'TRS'],
                    ['value' => 'trrs_ctia', 'label' => 'TRRS (CTIA)'],
                    ['value' => 'trrs_omtp', 'label' => 'TRRS (OMTP)'],
                ],
                'allow_custom_values' => true,
            ],
            'usb_standard' => [
                'label' => 'USB-standaard',
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
                'categories' => ['Ports'],
                'options' => [
                    ['value' => 'usb_2_0', 'label' => 'USB 2.0'],
                    ['value' => 'usb_3_0', 'label' => 'USB 3.0'],
                    ['value' => 'usb_3_1_gen1', 'label' => 'USB 3.1 Gen 1'],
                    ['value' => 'usb_3_1_gen2', 'label' => 'USB 3.1 Gen 2'],
                    ['value' => 'usb_3_2_gen1', 'label' => 'USB 3.2 Gen 1'],
                    ['value' => 'usb4', 'label' => 'USB4'],
                ],
                'allow_custom_values' => true,
            ],
            'displayport_alt_mode' => [
                'label' => 'DisplayPort alt-mode',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Ports'],
            ],
            'displayport_version' => [
                'label' => 'DisplayPort-versie',
                'datatype' => AttributeDefinition::DATATYPE_TEXT,
                'categories' => ['Ports'],
                'allow_custom_values' => true,
            ],
            'power_delivery' => [
                'label' => 'USB Power Delivery',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Ports'],
            ],
            'power_delivery_watts' => [
                'label' => 'USB Power Delivery',
                'datatype' => AttributeDefinition::DATATYPE_INT,
                'unit' => 'W',
                'categories' => ['Ports'],
                'constraints' => ['min' => 1, 'max' => 240, 'step' => 1],
            ],
            'thunderbolt' => [
                'label' => 'Thunderbolt',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Ports'],
            ],
            'thunderbolt_version' => [
                'label' => 'Thunderbolt-versie',
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
                'categories' => ['Ports'],
                'options' => [
                    ['value' => '1', 'label' => 'Thunderbolt 1'],
                    ['value' => '2', 'label' => 'Thunderbolt 2'],
                    ['value' => '3', 'label' => 'Thunderbolt 3'],
                    ['value' => '4', 'label' => 'Thunderbolt 4'],
                    ['value' => '5', 'label' => 'Thunderbolt 5'],
                ],
            ],
            'hdmi_version' => [
                'label' => 'HDMI-versie',
                'datatype' => AttributeDefinition::DATATYPE_TEXT,
                'categories' => ['Ports'],
                'allow_custom_values' => true,
            ],
            'ethernet_speed_max' => [
                'label' => 'Ethernet max snelheid',
                'datatype' => AttributeDefinition::DATATYPE_ENUM,
                'categories' => ['Ports'],
                'options' => [
                    ['value' => '1gbe', 'label' => '1GbE'],
                    ['value' => '2_5gbe', 'label' => '2.5GbE'],
                    ['value' => '5gbe', 'label' => '5GbE'],
                    ['value' => '10gbe', 'label' => '10GbE'],
                ],
                'allow_custom_values' => true,
            ],
            'sleep_and_charge' => [
                'label' => 'Sleep-and-charge',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Ports'],
            ],
            // Capability attributes (formerly *_test)
            'wifi' => [
                'label' => 'Wifi',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Laptops', 'Mobile Phones'],
                'allow_asset_override' => false,
            ],
            'bluetooth' => [
                'label' => 'Bluetooth',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Laptops', 'Mobile Phones'],
                'allow_asset_override' => false,
            ],
            'speaker' => [
                'label' => 'Luidspreker',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Laptops', 'Mobile Phones'],
                'allow_asset_override' => false,
            ],
            'microphone' => [
                'label' => 'Microfoon',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Laptops', 'Mobile Phones'],
                'allow_asset_override' => false,
            ],
            'display' => [
                'label' => 'Scherm',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Laptops', 'Mobile Phones'],
                'allow_asset_override' => false,
            ],
            'battery' => [
                'label' => 'Batterij',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Laptops', 'Mobile Phones'],
                'allow_asset_override' => false,
            ],
            'webcam' => [
                'label' => 'Webcam',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Laptops'],
                'allow_asset_override' => false,
            ],
            'front_camera' => [
                'label' => 'Selfiecamera',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Mobile Phones'],
                'allow_asset_override' => false,
            ],
            'rear_camera' => [
                'label' => 'Hoofdcamera',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Mobile Phones'],
                'allow_asset_override' => false,
            ],
            'face_unlock' => [
                'label' => 'Gezichtsherkenning',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Mobile Phones'],
                'allow_asset_override' => false,
            ],
            'ethernet' => [
                'label' => 'Ethernet',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Laptops'],
                'allow_asset_override' => false,
            ],
            'usb_ports' => [
                'label' => 'USB-poorten aanwezig',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Laptops'],
                'allow_asset_override' => false,
            ],
            'sd_card_reader' => [
                'label' => 'SD-kaartlezer',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Laptops'],
                'allow_asset_override' => false,
            ],
            'hdmi' => [
                'label' => 'HDMI',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Laptops'],
                'allow_asset_override' => false,
            ],
            'vga' => [
                'label' => 'VGA',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Laptops'],
                'allow_asset_override' => false,
            ],
            'keyboard' => [
                'label' => 'Toetsenbord aanwezig',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Laptops'],
                'allow_asset_override' => false,
            ],
            'touchpad' => [
                'label' => 'Touchpad aanwezig',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Laptops'],
                'allow_asset_override' => false,
            ],
            'cpu' => [
                'label' => 'Processor OK',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Laptops'],
                'allow_asset_override' => false,
            ],
            'ram' => [
                'label' => 'Geheugen OK',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Laptops'],
                'allow_asset_override' => false,
            ],
            'storage' => [
                'label' => 'Opslag OK',
                'datatype' => AttributeDefinition::DATATYPE_BOOL,
                'categories' => ['Laptops'],
                'allow_asset_override' => false,
            ],
        ];

        return array_diff_key($blueprints, array_flip($this->removedAttributeKeys()));
    }

    /**
     * Primary models that power the demo assets inventory.
     */
    protected function demoModelKeys(): array
    {
        return [
            'HP ProBook 450 G8',
            'HP ProBook 430 G7',
            'Samsung Galaxy A5',
            'Pixel 8 Pro',
        ];
    }

    /**
     * Additional catalog entries we seed for more realistic refurb scenarios.
     */
    protected function expansionModelKeys(): array
    {
        return [
            'HP ProBook 450 G7',
            'HP ProBook 450 G6',
            'HP ProBook 430 G6',
            'HP ProBook 430 G3',
            'Microsoft Surface Pro 4',
            'Microsoft Surface Pro 5',
        ];
    }

    /**
     * Catalog attribute keys intentionally removed from the clean-start seed.
     *
     * Keep this list explicit so future reliance on an old key can be audited
     * and either restored intentionally or replaced with a workflow/component
     * concept.
     *
     * @return array<int,string>
     */
    protected function removedAttributeKeys(): array
    {
        return [
            'audio_connectors_summary',
            'battery',
            'battery_capacity',
            'battery_health_percent',
            'bluetooth',
            'charger_included',
            'charging_port_type',
            'condition_grade',
            'cpu',
            'display',
            'ethernet',
            'face_unlock',
            'front_camera',
            'front_camera_megapixels',
            'hdmi',
            'included_accessories',
            'keyboard',
            'microphone',
            'ram',
            'rear_camera',
            'rear_camera_megapixels',
            'sd_card_reader',
            'speaker',
            'storage',
            'touchpad',
            'usb_ports',
            'usb_ports_summary',
            'vga',
            'video_outputs_summary',
            'warranty_months',
            'webcam',
            'webcam_present',
            'wifi',
        ];
    }

    private function catalogAssetModel(string $name, string $categoryName, string $manufacturerName, ?string $eol = null): AssetModel
    {
        /** @var AssetModel $model */
        $model = AssetModel::withTrashed()->firstOrNew(['name' => $name]);
        $model->fill([
            'category_id' => $this->catalogCategoryId($categoryName, 'asset'),
            'manufacturer_id' => $this->catalogManufacturerId($manufacturerName),
            'eol' => $eol,
        ]);

        if ($model->trashed()) {
            $model->restore();
        }

        $model->save();

        return $model;
    }

    private function catalogCategoryId(string $name, string $categoryType): int
    {
        /** @var Category $category */
        $category = Category::withTrashed()->firstOrNew([
            'name' => $name,
            'category_type' => $categoryType,
        ]);

        if (! $category->exists) {
            $category->created_by = null;
        }

        if ($category->trashed()) {
            $category->restore();
        }

        $category->save();

        return $category->id;
    }

    private function catalogManufacturerId(string $name): int
    {
        /** @var Manufacturer $manufacturer */
        $manufacturer = Manufacturer::withTrashed()->firstOrNew(['name' => $name]);

        if (! $manufacturer->exists) {
            $manufacturer->fill($this->catalogManufacturerDefaults($name));
        }

        if ($manufacturer->trashed()) {
            $manufacturer->restore();
        }

        $manufacturer->save();

        return $manufacturer->id;
    }

    /**
     * @return array<string,string|null>
     */
    private function catalogManufacturerDefaults(string $name): array
    {
        return match ($name) {
            'Apple' => [
                'url' => 'https://apple.com',
                'support_url' => 'https://support.apple.com',
                'warranty_lookup_url' => 'https://checkcoverage.apple.com',
                'image' => 'apple.jpg',
            ],
            'Google' => [
                'url' => 'https://www.google.com',
                'image' => 'google.webp',
            ],
            'HP' => [
                'url' => 'https://hp.com',
                'support_url' => 'https://support.hp.com',
                'image' => 'hp.png',
            ],
            'Microsoft' => [
                'url' => 'https://microsoft.com',
                'support_url' => 'https://support.microsoft.com',
                'warranty_lookup_url' => 'https://account.microsoft.com/devices',
                'image' => 'microsoft.png',
            ],
            'Samsung' => [
                'url' => 'https://www.samsung.com',
                'support_url' => 'https://www.samsung.com/support/',
                'image' => 'samsung.png',
            ],
            default => [],
        };
    }

    /**
     * Reference device presets for the production catalog foundation.
     *
     * @return array<string,array<string,mixed>>
     */
    protected function modelBlueprints(): array
    {
        $blueprints = [
            'HP ProBook 450 G8' => [
                'factory' => fn () => $this->catalogAssetModel('HP ProBook 450 G8', 'Laptops', 'HP', '36'),
                'code' => '2E9F8EA#ABH',
                'label' => 'HP ProBook 450 G8 - i5-1135G7 - 8GB - 256GB',
                'attributes' => [
                    'release_year' => 2021,
                    'cpu_model' => 'Intel Core i5-1135G7',
                    'cpu_core_count' => 4,
                    'gpu_model' => 'Intel Iris Xe Graphics',
                    'ram_size_gb' => 8,
                    'ram_type' => 'ddr4',
                    'storage_capacity_gb' => 256,
                    'storage_type' => 'nvme',
                    'display_size_inches' => 15.6,
                    'display_resolution' => '1920 x 1080',
                    'display_panel_type' => 'ips',
                    'display_refresh_rate_hz' => 60,
                    'weight_kg' => 1.74,
                    'battery_capacity' => '45 Wh',
                    'battery_health_percent' => 94,
                    'keyboard_layout' => 'us',
                    'webcam_present' => true,
                    'usb_ports_summary' => '1x USB-A 3.1 Gen1 (Sleep/Charge), 1x USB-A 3.1 Gen1, 1x USB-A 3.1 Gen1 DP, 1x USB-C 3.1 Gen2 (DP 1.4 alt, PD)',
                    'video_outputs_summary' => 'HDMI 1.4b, USB-C (DP 1.4 alt)',
                    'audio_connectors_summary' => '3,5 mm headset/microphone combo',
                    'charger_included' => true,
                    'included_accessories' => '65W USB-C-voeding, USB-C-oplaadkabel',
                    'os_family' => 'windows',
                    'os_version' => 'Windows 11 Pro',
                    'warranty_months' => 12,
                    'condition_grade' => 'grade_a',
                    'color' => 'Pike Silver',
                    'wifi' => true,
                    'bluetooth' => true,
                    'speaker' => true,
                    'microphone' => true,
                    'webcam' => true,
                    'keyboard' => true,
                    'touchpad' => true,
                    'display' => true,
                    'cpu' => true,
                    'ram' => true,
                    'storage' => true,
                    'battery' => true,
                    'usb_ports' => true,
                    'sd_card_reader' => true,
                    'ethernet' => true,
                ],
            ],
            'HP ProBook 450 G7' => [
                'factory' => fn () => $this->catalogAssetModel('HP ProBook 450 G7', 'Laptops', 'HP', '36'),
                'code' => '8VU81EA#ABH',
                'label' => 'HP ProBook 450 G7 - i5-10210U - 8GB - 256GB',
                'attributes' => [
                    'release_year' => 2020,
                    'cpu_model' => 'Intel Core i5-10210U',
                    'cpu_core_count' => 4,
                    'gpu_model' => 'Intel UHD Graphics',
                    'ram_size_gb' => 8,
                    'ram_type' => 'ddr4',
                    'storage_capacity_gb' => 256,
                    'storage_type' => 'nvme',
                    'display_size_inches' => 15.6,
                    'display_resolution' => '1920 x 1080',
                    'display_panel_type' => 'ips',
                    'display_refresh_rate_hz' => 60,
                    'weight_kg' => 1.74,
                    'battery_capacity' => '45 Wh',
                    'battery_health_percent' => 92,
                    'keyboard_layout' => 'us',
                    'webcam_present' => true,
                    'usb_ports_summary' => '2x USB-A 3.1 Gen1, 1x USB-C 3.1 Gen1 (DP alt, PD)',
                    'video_outputs_summary' => 'HDMI 1.4b, USB-C (DP alt)',
                    'audio_connectors_summary' => '3,5 mm headset/microphone combo',
                    'charger_included' => true,
                    'included_accessories' => '65W USB-C-voeding, USB-C-kabel',
                    'os_family' => 'windows',
                    'os_version' => 'Windows 11 Pro',
                    'warranty_months' => 12,
                    'condition_grade' => 'grade_b',
                    'color' => 'Pike Silver',
                    'wifi' => true,
                    'bluetooth' => true,
                    'speaker' => true,
                    'microphone' => true,
                    'webcam' => true,
                    'keyboard' => true,
                    'touchpad' => true,
                    'display' => true,
                    'cpu' => true,
                    'ram' => true,
                    'storage' => true,
                    'battery' => true,
                    'usb_ports' => true,
                    'sd_card_reader' => true,
                    'ethernet' => true,
                ],
            ],
            'HP ProBook 450 G6' => [
                'factory' => fn () => $this->catalogAssetModel('HP ProBook 450 G6', 'Laptops', 'HP', '36'),
                'code' => '5PP65EA#ABH',
                'label' => 'HP ProBook 450 G6 - i5-8265U - 8GB - 256GB',
                'attributes' => [
                    'release_year' => 2019,
                    'cpu_model' => 'Intel Core i5-8265U',
                    'cpu_core_count' => 4,
                    'gpu_model' => 'Intel UHD Graphics 620',
                    'ram_size_gb' => 8,
                    'ram_type' => 'ddr4',
                    'storage_capacity_gb' => 256,
                    'storage_type' => 'nvme',
                    'display_size_inches' => 15.6,
                    'display_resolution' => '1920 x 1080',
                    'display_panel_type' => 'ips',
                    'display_refresh_rate_hz' => 60,
                    'weight_kg' => 1.96,
                    'battery_capacity' => '45 Wh',
                    'battery_health_percent' => 88,
                    'keyboard_layout' => 'us',
                    'webcam_present' => true,
                    'usb_ports_summary' => '2x USB 3.2 Gen1 Type-A, 1x USB 3.2 Gen1 Type-C (DP alt, PD)',
                    'video_outputs_summary' => 'HDMI 1.4, USB-C (DP alt)',
                    'audio_connectors_summary' => '3,5 mm headset/microphone combo',
                    'charger_included' => true,
                    'included_accessories' => '65W USB-C-voeding',
                    'os_family' => 'windows',
                    'os_version' => 'Windows 11 Pro',
                    'warranty_months' => 12,
                    'condition_grade' => 'grade_c',
                    'color' => 'Ash Silver',
                    'camera_resolution' => '1280x720 (HD)',
                    'wifi' => true,
                    'bluetooth' => true,
                    'speaker' => true,
                    'microphone' => true,
                    'webcam' => true,
                    'keyboard' => true,
                    'touchpad' => true,
                    'display' => true,
                    'cpu' => true,
                    'ram' => true,
                    'storage' => true,
                    'battery' => true,
                    'usb_ports' => true,
                    'sd_card_reader' => true,
                    'ethernet' => true,
                ],
            ],
            'HP ProBook 430 G7' => [
                'factory' => fn () => $this->catalogAssetModel('HP ProBook 430 G7', 'Laptops', 'HP', '36'),
                'code' => '8VT42EA#ABH',
                'label' => 'HP ProBook 430 G7 - i5-10210U - 8GB - 256GB',
                'attributes' => [
                    'release_year' => 2020,
                    'cpu_model' => 'Intel Core i5-10210U',
                    'cpu_core_count' => 4,
                    'gpu_model' => 'Intel UHD Graphics',
                    'ram_size_gb' => 8,
                    'ram_type' => 'ddr4',
                    'storage_capacity_gb' => 256,
                    'storage_type' => 'nvme',
                    'display_size_inches' => 13.3,
                    'display_resolution' => '1920 x 1080',
                    'display_panel_type' => 'ips',
                    'display_refresh_rate_hz' => 60,
                    'weight_kg' => 1.28,
                    'battery_capacity' => '45 Wh',
                    'battery_health_percent' => 91,
                    'keyboard_layout' => 'us',
                    'webcam_present' => true,
                    'usb_ports_summary' => '1x USB-A 3.1, 1x USB-A 3.1, 1x USB-C 3.1 Gen1 (DP alt, PD)',
                    'video_outputs_summary' => 'HDMI 1.4b, USB-C (DP alt)',
                    'audio_connectors_summary' => '3,5 mm headset/microphone combo',
                    'charger_included' => true,
                    'included_accessories' => '45W USB-C-voeding',
                    'os_family' => 'windows',
                    'os_version' => 'Windows 11 Pro',
                    'warranty_months' => 12,
                    'condition_grade' => 'grade_b',
                    'color' => 'Natural Silver',
                    'wifi' => true,
                    'bluetooth' => true,
                    'speaker' => true,
                    'microphone' => true,
                    'webcam' => true,
                    'keyboard' => true,
                    'touchpad' => true,
                    'display' => true,
                    'cpu' => true,
                    'ram' => true,
                    'storage' => true,
                    'battery' => true,
                    'usb_ports' => true,
                    'sd_card_reader' => true,
                    'ethernet' => true,
                ],
            ],
            'HP ProBook 430 G6' => [
                'factory' => fn () => $this->catalogAssetModel('HP ProBook 430 G6', 'Laptops', 'HP', '36'),
                'code' => '5TK76EA#ABH',
                'label' => 'HP ProBook 430 G6 - i5 - 8GB - 128GB',
                'attributes' => [
                    'release_year' => 2019,
                    'cpu_model' => 'Intel Core i5-8265U',
                    'cpu_core_count' => 4,
                    'gpu_model' => 'Intel UHD Graphics 620',
                    'ram_size_gb' => 8,
                    'ram_type' => 'ddr4',
                    'storage_capacity_gb' => 128,
                    'storage_type' => 'nvme',
                    'display_size_inches' => 13.3,
                    'display_resolution' => '1920 x 1080',
                    'display_panel_type' => 'ips',
                    'display_refresh_rate_hz' => 60,
                    'weight_kg' => 1.28,
                    'battery_capacity' => '45 Wh',
                    'battery_health_percent' => null,
                    'keyboard_layout' => 'qwerty',
                    'webcam_present' => true,
                    'usb_ports_summary' => '2x USB-A 3.2 Gen1, 1x USB-A 2.0, 1x USB-C Gen1, HDMI 1.4, RJ45, SD',
                    'video_outputs_summary' => 'HDMI 1.4, USB-C DisplayPort',
                    'audio_connectors_summary' => '3,5 mm audio',
                    'charger_included' => true,
                    'included_accessories' => '45W USB-C-voeding',
                    'os_family' => 'windows',
                    'os_version' => 'Windows 11 Pro',
                    'warranty_months' => 12,
                    'condition_grade' => 'grade_b',
                    'color' => 'Natural Silver',
                    'wifi' => true,
                    'bluetooth' => true,
                    'speaker' => true,
                    'microphone' => true,
                    'webcam' => true,
                    'keyboard' => true,
                    'touchpad' => true,
                    'display' => true,
                    'cpu' => true,
                    'ram' => true,
                    'storage' => true,
                    'battery' => true,
                    'usb_ports' => true,
                    'sd_card_reader' => true,
                    'ethernet' => true,
                ],
            ],
            'HP ProBook 430 G3' => [
                'factory' => fn () => $this->catalogAssetModel('HP ProBook 430 G3', 'Laptops', 'HP', '24'),
                'code' => 'HP-430G3-I3-4-128',
                'label' => 'HP ProBook 430 G3 - i3 - 4GB - 128GB',
                'attributes' => [
                    'release_year' => 2016,
                    'cpu_model' => 'Intel Core i3-6100U',
                    'cpu_core_count' => 2,
                    'gpu_model' => 'Intel HD Graphics 520',
                    'ram_size_gb' => 4,
                    'ram_type' => 'ddr4',
                    'storage_capacity_gb' => 128,
                    'storage_type' => 'ssd',
                    'display_size_inches' => 13.3,
                    'display_resolution' => '1366 x 768',
                    'display_panel_type' => 'tn',
                    'display_refresh_rate_hz' => 60,
                    'weight_kg' => 1.5,
                    'battery_capacity' => null,
                    'battery_health_percent' => null,
                    'keyboard_layout' => 'qwerty_us_intl',
                    'webcam_present' => true,
                    'usb_ports_summary' => '2x USB-A 3.2 Gen1, 1x USB-A 2.0, VGA, HDMI, RJ45',
                    'video_outputs_summary' => 'HDMI 1.4, VGA',
                    'audio_connectors_summary' => '3,5 mm audio',
                    'charger_included' => true,
                    'included_accessories' => '45W barrel-voeding',
                    'os_family' => 'windows',
                    'os_version' => 'Windows 10 Pro',
                    'warranty_months' => 6,
                    'condition_grade' => 'grade_c',
                    'color' => 'Ash Silver',
                    'wifi' => true,
                    'bluetooth' => true,
                    'speaker' => true,
                    'microphone' => true,
                    'webcam' => true,
                    'keyboard' => true,
                    'touchpad' => true,
                    'display' => true,
                    'cpu' => true,
                    'ram' => true,
                    'storage' => true,
                    'battery' => true,
                    'usb_ports' => true,
                    'sd_card_reader' => true,
                    'ethernet' => true,
                ],
            ],
            'Samsung Galaxy A5' => [
                'factory' => fn () => $this->catalogAssetModel('Samsung Galaxy A5', 'Mobile Phones', 'Samsung', '18'),
                'code' => 'SM-A520F',
                'label' => 'Samsung Galaxy A5 (2017) - 32GB - Zwart',
                'attributes' => [
                    'release_year' => 2017,
                    'ram_size_gb' => 3,
                    'ram_type' => 'lpddr4x',
                    'storage_capacity_gb' => 32,
                    'storage_type' => 'ufs',
                    'display_size_inches' => 5.2,
                    'display_resolution' => '1920 x 1080',
                    'display_panel_type' => 'amoled',
                    'display_refresh_rate_hz' => 60,
                    'battery_capacity' => '3000 mAh',
                    'battery_health_percent' => 82,
                    'rear_camera_megapixels' => 16,
                    'front_camera_megapixels' => 16,
                    'supports_5g' => false,
                    'os_family' => 'android',
                    'os_version' => 'Android 8.0',
                    'warranty_months' => 6,
                    'condition_grade' => 'grade_b',
                    'color' => 'Black Sky',
                    'charger_included' => true,
                    'included_accessories' => '15W oplader, USB-C-kabel',
                    'charging_port_type' => 'usb-c',
                    'ip_rating' => 'ip68',
                    'display' => true,
                    'front_camera' => true,
                    'rear_camera' => true,
                    'wifi' => true,
                    'bluetooth' => true,
                    'speaker' => true,
                    'microphone' => true,
                    'battery' => true,
                ],
            ],
            'Microsoft Surface Pro 4' => [
                'factory' => fn () => $this->catalogAssetModel('Microsoft Surface Pro 4', 'Laptops', 'Microsoft', '30'),
                'code' => 'MS-SURFPRO4-I5-4-128',
                'label' => 'Microsoft Surface Pro 4 - i5 - 4GB - 128GB',
                'attributes' => [
                    'release_year' => 2015,
                    'cpu_model' => 'Intel Core i5-6300U',
                    'cpu_core_count' => 2,
                    'gpu_model' => 'Intel HD Graphics 520',
                    'ram_size_gb' => 4,
                    'ram_type' => 'lpddr3',
                    'storage_capacity_gb' => 128,
                    'storage_type' => 'nvme',
                    'display_size_inches' => 12.3,
                    'display_resolution' => '2736 x 1824',
                    'display_panel_type' => 'ips',
                    'display_refresh_rate_hz' => 60,
                    'weight_kg' => 0.79,
                    'battery_capacity' => '38 Wh',
                    'battery_health_percent' => 80,
                    'keyboard_layout' => 'us',
                    'webcam_present' => true,
                    'usb_ports_summary' => '1x USB-A 3.0, Mini DisplayPort, Surface Connect',
                    'video_outputs_summary' => 'Mini DisplayPort',
                    'audio_connectors_summary' => '3,5 mm audio',
                    'charger_included' => true,
                    'included_accessories' => '65W Surface Connect-voeding, Surface Pen',
                    'os_family' => 'windows',
                    'os_version' => 'Windows 10 Pro',
                    'warranty_months' => 6,
                    'condition_grade' => 'grade_b',
                    'color' => 'Platinum',
                    'wifi' => true,
                    'bluetooth' => true,
                    'speaker' => true,
                    'microphone' => true,
                    'webcam' => true,
                    'keyboard' => true,
                    'touchpad' => true,
                    'display' => true,
                    'cpu' => true,
                    'ram' => true,
                    'storage' => true,
                    'battery' => true,
                    'usb_ports' => true,
                    'sd_card_reader' => true,
                    'ethernet' => false,
                ],
            ],
            'Microsoft Surface Pro 5' => [
                'factory' => fn () => $this->catalogAssetModel('Microsoft Surface Pro 5', 'Laptops', 'Microsoft', '30'),
                'code' => 'MS-SURFPRO5-I5-4-128',
                'label' => 'Microsoft Surface Pro 5 - i5 - 4GB - 128GB',
                'attributes' => [
                    'release_year' => 2017,
                    'cpu_model' => 'Intel Core i5-7300U',
                    'cpu_core_count' => 2,
                    'gpu_model' => 'Intel HD Graphics 620',
                    'ram_size_gb' => 4,
                    'ram_type' => 'lpddr3',
                    'storage_capacity_gb' => 128,
                    'storage_type' => 'nvme',
                    'display_size_inches' => 12.3,
                    'display_resolution' => '2736 x 1824',
                    'display_panel_type' => 'ips',
                    'display_refresh_rate_hz' => 60,
                    'weight_kg' => 0.78,
                    'battery_capacity' => '45 Wh',
                    'battery_health_percent' => 87,
                    'keyboard_layout' => 'us',
                    'webcam_present' => true,
                    'usb_ports_summary' => '1x USB-A 3.0, Mini DisplayPort, Surface Connect',
                    'video_outputs_summary' => 'Mini DisplayPort',
                    'audio_connectors_summary' => '3,5 mm audio',
                    'charger_included' => true,
                    'included_accessories' => '65W Surface Connect-voeding, Type Cover',
                    'os_family' => 'windows',
                    'os_version' => 'Windows 11 Pro',
                    'warranty_months' => 12,
                    'condition_grade' => 'grade_a',
                    'color' => 'Platinum',
                    'wifi' => true,
                    'bluetooth' => true,
                    'speaker' => true,
                    'microphone' => true,
                    'webcam' => true,
                    'keyboard' => true,
                    'touchpad' => true,
                    'display' => true,
                    'cpu' => true,
                    'ram' => true,
                    'storage' => true,
                    'battery' => true,
                    'usb_ports' => true,
                    'sd_card_reader' => true,
                    'ethernet' => false,
                ],
            ],
            'iPhone 12' => [
                'factory' => fn () => $this->catalogAssetModel('iPhone 12', 'Mobile Phones', 'Apple', '12'),
                'code' => 'IP12-128-BLUE',
                'label' => 'iPhone 12 – 128GB – Simlockvrij',
                'attributes' => [
                    'release_year' => 2020,
                    'ram_size_gb' => 4,
                    'ram_type' => 'lpddr4x',
                    'storage_capacity_gb' => 128,
                    'storage_type' => 'ufs',
                    'display_size_inches' => 6.1,
                    'display_resolution' => '2532 x 1170',
                    'display_panel_type' => 'oled',
                    'display_refresh_rate_hz' => 60,
                    'battery_capacity' => '2815 mAh',
                    'battery_health_percent' => 89,
                    'rear_camera_megapixels' => 12,
                    'front_camera_megapixels' => 12,
                    'supports_5g' => true,
                    'os_family' => 'ios',
                    'os_version' => 'iOS 18',
                    'warranty_months' => 6,
                    'condition_grade' => 'grade_a',
                    'color' => 'Pacific Blue',
                    'charger_included' => false,
                    'included_accessories' => 'USB-C-naar-Lightning-kabel',
                    'charging_port_type' => 'lightning',
                    'ip_rating' => 'ip68',
                    'display' => true,
                    'front_camera' => true,
                    'rear_camera' => true,
                    'face_unlock' => true,
                    'wifi' => true,
                    'bluetooth' => true,
                    'speaker' => true,
                    'microphone' => true,
                    'battery' => true,
                ],
            ],
            'Pixel 8 Pro' => [
                'factory' => fn () => $this->catalogAssetModel('Pixel 8 Pro', 'Mobile Phones', 'Google'),
                'code' => 'PIXEL8PRO-256-OBSIDIAN',
                'label' => 'Pixel 8 Pro – 256GB – Obsidian',
                'attributes' => [
                    'release_year' => 2023,
                    'ram_size_gb' => 12,
                    'ram_type' => 'lpddr5x',
                    'storage_capacity_gb' => 256,
                    'storage_type' => 'ufs',
                    'display_size_inches' => 6.7,
                    'display_resolution' => '2992 x 1344',
                    'display_panel_type' => 'oled',
                    'display_refresh_rate_hz' => 120,
                    'battery_capacity' => '5050 mAh',
                    'battery_health_percent' => 97,
                    'rear_camera_megapixels' => 50,
                    'front_camera_megapixels' => 10,
                    'supports_5g' => true,
                    'os_family' => 'android',
                    'os_version' => 'Android 14',
                    'warranty_months' => 12,
                    'condition_grade' => 'grade_a',
                    'color' => 'Obsidian',
                    'charger_included' => false,
                    'included_accessories' => 'USB-C-kabel (1 m)',
                    'charging_port_type' => 'usb-c',
                    'ip_rating' => 'ip68',
                    'display' => true,
                    'front_camera' => true,
                    'rear_camera' => true,
                    'wifi' => true,
                    'bluetooth' => true,
                    'speaker' => true,
                    'microphone' => true,
                    'battery' => true,
                ],
            ],
        ];

        $removed = array_flip($this->removedAttributeKeys());

        foreach ($blueprints as $modelName => $config) {
            $blueprints[$modelName]['attributes'] = array_diff_key($config['attributes'] ?? [], $removed);
        }

        return $blueprints;
    }
}

