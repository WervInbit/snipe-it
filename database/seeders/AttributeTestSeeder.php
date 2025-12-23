<?php

namespace Database\Seeders;

use App\Models\AttributeDefinition;
use App\Models\TestType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AttributeTestSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedAttributeTests();
        });
    }

    protected function seedAttributeTests(): void
    {
        $definitions = AttributeDefinition::whereIn('key', array_keys($this->tests()))
            ->get()
            ->keyBy('key');

        foreach ($this->tests() as $key => $config) {
            /** @var AttributeDefinition|null $definition */
            $definition = $definitions->get($key);

            if (!$definition) {
                continue;
            }

            TestType::updateOrCreate(
                ['slug' => Arr::get($config, 'slug', $key)],
                [
                    'name' => Arr::get($config, 'name', $definition->label),
                    'attribute_definition_id' => $definition->id,
                    'tooltip' => Arr::get($config, 'tooltip'),
                    'instructions' => Arr::get($config, 'instructions'),
                    'is_required' => true,
                ]
            );
        }
    }

    /**
     * @return array<string,array<string,string|null>>
     */
    protected function tests(): array
    {
        return [
            'battery' => [
                'instructions' => 'Charge and discharge the battery to confirm capacity and charging behaviour.',
            ],
            'bluetooth' => [
                'instructions' => 'Pair with a Bluetooth peripheral and confirm successful data transfer.',
            ],
            'cpu' => [
                'instructions' => 'Run a processor stress utility and monitor for throttling or errors.',
            ],
            'front_camera' => [
                'instructions' => 'Maak een selfie en controleer autofocus, belichting en eventuele vlekken op de lens.',
            ],
            'rear_camera' => [
                'instructions' => 'Maak meerdere foto\'s met de hoofdcamera en controleer scherpstelling en flitser.',
            ],
            'display' => [
                'instructions' => 'Inspect the display for brightness consistency, colour accuracy, and stuck pixels.',
            ],
            'ethernet' => [
                'instructions' => 'Connect an ethernet cable and confirm the device obtains a network address.',
            ],
            'face_unlock' => [
                'instructions' => 'Enroll a face and confirm multiple unlock attempts succeed without error.',
            ],
            'hdmi' => [
                'instructions' => 'Connect an external monitor via HDMI and confirm video (and audio where applicable).',
            ],
            'keyboard' => [
                'instructions' => 'Press each key in sequence to ensure every key registers correctly.',
            ],
            'microphone' => [
                'instructions' => 'Record audio and confirm playback is clear and free of distortion.',
            ],
            'ram' => [
                'instructions' => 'Run memory diagnostics to confirm no errors occur across the installed RAM.',
            ],
            'sd_card_reader' => [
                'instructions' => 'Insert an SD card and verify it mounts and transfers data successfully.',
            ],
            'speaker' => [
                'instructions' => 'Play audio through the internal speakers and listen for clarity and balance.',
            ],
            'storage' => [
                'instructions' => 'Run drive health checks (SMART) and read/write tests on the installed storage.',
            ],
            'touchpad' => [
                'instructions' => 'Verify cursor movement, tap-to-click, scrolling, and gesture support.',
            ],
            'usb_ports' => [
                'instructions' => 'Insert a USB device into each port and confirm detection and data transfer.',
            ],
            'vga' => [
                'instructions' => 'Connect an external monitor via VGA and confirm video output is stable.',
            ],
            'webcam' => [
                'instructions' => 'Open the camera application to verify the webcam feed and focus.',
            ],
            'wifi' => [
                'instructions' => 'Connect to the designated Wi-Fi network and confirm internet access.',
            ],
        ];
    }
}

