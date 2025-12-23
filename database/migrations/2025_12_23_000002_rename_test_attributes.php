<?php

use App\Models\AttributeDefinition;
use App\Models\TestType;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Rename legacy *_test attributes, update labels, and disable asset overrides.
     */
    public function up(): void
    {
        $renames = [
            'wifi_test' => ['key' => 'wifi', 'label' => 'Wifi'],
            'bluetooth_test' => ['key' => 'bluetooth', 'label' => 'Bluetooth'],
            'speaker_test' => ['key' => 'speaker', 'label' => 'Luidspreker'],
            'microphone_test' => ['key' => 'microphone', 'label' => 'Microfoon'],
            'display_test' => ['key' => 'display', 'label' => 'Scherm'],
            'battery_test' => ['key' => 'battery', 'label' => 'Batterij'],
            'webcam_test' => ['key' => 'webcam', 'label' => 'Webcam'],
            'front_camera_test' => ['key' => 'front_camera', 'label' => 'Selfiecamera'],
            'rear_camera_test' => ['key' => 'rear_camera', 'label' => 'Hoofdcamera'],
            'face_unlock_test' => ['key' => 'face_unlock', 'label' => 'Gezichtsherkenning'],
            'ethernet_test' => ['key' => 'ethernet', 'label' => 'Ethernet'],
            'usb_ports_test' => ['key' => 'usb_ports', 'label' => 'USB-poorten aanwezig'],
            'sd_card_reader_test' => ['key' => 'sd_card_reader', 'label' => 'SD-kaartlezer'],
            'hdmi_test' => ['key' => 'hdmi', 'label' => 'HDMI'],
            'vga_test' => ['key' => 'vga', 'label' => 'VGA'],
            'keyboard_test' => ['key' => 'keyboard', 'label' => 'Toetsenbord aanwezig'],
            'touchpad_test' => ['key' => 'touchpad', 'label' => 'Touchpad aanwezig'],
            'cpu_test' => ['key' => 'cpu', 'label' => 'Processor OK'],
            'ram_test' => ['key' => 'ram', 'label' => 'Geheugen OK'],
            'storage_test' => ['key' => 'storage', 'label' => 'Opslag OK'],
        ];

        foreach ($renames as $from => $to) {
            /** @var AttributeDefinition|null $definition */
            $definition = AttributeDefinition::withTrashed()->where('key', $from)->first();

            if (!$definition) {
                continue;
            }

            if (!AttributeDefinition::where('key', $to['key'])->exists()) {
                $definition->key = $to['key'];
            }

            $definition->label = $to['label'];
            $definition->allow_asset_override = false;
            $definition->save();

            /** @var TestType|null $legacyType */
            $legacyType = TestType::where('slug', $from)->first();

            if ($legacyType && !TestType::where('slug', $to['key'])->exists()) {
                $legacyType->update([
                    'slug' => $to['key'],
                    'name' => $to['label'],
                ]);
            }
        }
    }

    public function down(): void
    {
        $renames = [
            'wifi' => ['key' => 'wifi_test', 'label' => 'Wifi-test'],
            'bluetooth' => ['key' => 'bluetooth_test', 'label' => 'Bluetooth-test'],
            'speaker' => ['key' => 'speaker_test', 'label' => 'Luidsprekertest'],
            'microphone' => ['key' => 'microphone_test', 'label' => 'Microfoontest'],
            'display' => ['key' => 'display_test', 'label' => 'Schermtest'],
            'battery' => ['key' => 'battery_test', 'label' => 'Batterijtest'],
            'webcam' => ['key' => 'webcam_test', 'label' => 'Webcamtest'],
            'front_camera' => ['key' => 'front_camera_test', 'label' => 'Selfiecamera-test'],
            'rear_camera' => ['key' => 'rear_camera_test', 'label' => 'Hoofdcamera-test'],
            'face_unlock' => ['key' => 'face_unlock_test', 'label' => 'Gezichtsherkenningstest'],
            'ethernet' => ['key' => 'ethernet_test', 'label' => 'Ethernet-test'],
            'usb_ports' => ['key' => 'usb_ports_test', 'label' => 'USB-poortentest'],
            'sd_card_reader' => ['key' => 'sd_card_reader_test', 'label' => 'SD-kaartlezertest'],
            'hdmi' => ['key' => 'hdmi_test', 'label' => 'HDMI-test'],
            'vga' => ['key' => 'vga_test', 'label' => 'VGA-test'],
            'keyboard' => ['key' => 'keyboard_test', 'label' => 'Toetsentest'],
            'touchpad' => ['key' => 'touchpad_test', 'label' => 'Touchpadtest'],
            'cpu' => ['key' => 'cpu_test', 'label' => 'Processortest'],
            'ram' => ['key' => 'ram_test', 'label' => 'Geheugentest'],
            'storage' => ['key' => 'storage_test', 'label' => 'Opslagtest'],
        ];

        foreach ($renames as $from => $to) {
            /** @var AttributeDefinition|null $definition */
            $definition = AttributeDefinition::withTrashed()->where('key', $from)->first();

            if (!$definition) {
                continue;
            }

            if (!AttributeDefinition::where('key', $to['key'])->exists()) {
                $definition->key = $to['key'];
            }

            $definition->label = $to['label'];
            $definition->allow_asset_override = false;
            $definition->save();

            /** @var TestType|null $legacyType */
            $legacyType = TestType::where('slug', $from)->first();

            if ($legacyType && !TestType::where('slug', $to['key'])->exists()) {
                $legacyType->update([
                    'slug' => $to['key'],
                    'name' => $to['label'],
                ]);
            }
        }
    }
};
