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
                'instructions' => 'Laad de batterij op tot 100%, haal de lader er af, en draai de extreme test 10 minuten. Als de batterij boven 90% is slaagt de test. (Suggestie powercfg /batteryreport /output battery.html)',
            ],
            'bluetooth' => [
                'instructions' => 'Pair een bluetooth apparaat.',
            ],
            'cpu' => [
                'instructions' => 'Draai Prime95 10 minuten op de mode Small FFTs, als er geen errors, crashes, het systeem responsief blijft slaagt de test.',
            ],
            'front_camera' => [
                'instructions' => 'Maak een selfie en controleer autofocus, belichting en eventuele vlekken op de lens.',
            ],
            'rear_camera' => [
                'instructions' => 'Maak meerdere foto\'s met de hoofdcamera en controleer scherpstelling en flitser.',
            ],
            'display' => [
                'instructions' => 'Ga naar https://www.eizo.be/monitor-test/ en draai de test op full screen, zet op max brightness, met spatie haal je de tekst weg. navigeer door de tests heen en let op kleine witte vlekken, krassen, oneffenheden dit moeten er 0 zijn, maak anders een foto.',
            ],
            'ethernet' => [
                'instructions' => 'Verbind een ethernet kabel en zorg dat het internet nog werkt zonder wifi.',
            ],
            'face_unlock' => [
                'instructions' => 'Enroll a face and confirm multiple unlock attempts succeed without error.',
            ],
            'hdmi' => [
                'instructions' => 'Verbind de hdmi kabel en test of beeld en geluid werkt.',
            ],
            'keyboard' => [
                'instructions' => 'Ga naar https://keyboard-test.space/ en test elke toets, voer de test 2x uit, alle toetsen moeten soepel werken.',
            ],
            'microphone' => [
                'instructions' => 'Neem wat woorden geluid op en speel deze terug.',
            ],
            'ram' => [
                'instructions' => 'Draai y-cruncher stress test 5 minuten. 0 errors en geen abort',
            ],
            'sd_card_reader' => [
                'instructions' => 'Insert an SD card and verify it mounts and transfers data successfully.',
            ],
            'speaker' => [
                'instructions' => 'Play audio through the internal speakers and listen for clarity and balance.',
            ],
            'storage' => [
                'instructions' => 'Crystaldisk info portable of smartctl alle SMART moet 100% groen zijn.',
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
            'igpu' => [
                'instructions' => 'Draai GPU-Z 10 minuten, geen artifacts, driver reset, crash.',
            ],
        ];
    }
}

