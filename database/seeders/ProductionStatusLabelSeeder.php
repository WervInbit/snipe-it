<?php

namespace Database\Seeders;

use App\Models\Statuslabel;
use Illuminate\Database\Seeder;

class ProductionStatusLabelSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->statusLabels() as $data) {
            $stage = $data['lifecycle_stage'] ?? null;

            /** @var Statuslabel $status */
            $status = $stage
                ? Statuslabel::withTrashed()->where('lifecycle_stage', $stage)->first()
                : null;

            $status ??= Statuslabel::withTrashed()->where('name', $data['name'])->first();

            if ($status) {
                if ($stage && $status->lifecycle_stage === null) {
                    $status->lifecycle_stage = $stage;
                    $status->save();
                } elseif ($stage && $status->lifecycle_stage !== $stage) {
                    throw new \RuntimeException(
                        "Status label '{$data['name']}' already has incompatible lifecycle stage '{$status->lifecycle_stage}'."
                    );
                }

                // Existing labels, including deliberate renames and soft deletes, are operator-owned.
                continue;
            }

            Statuslabel::create(array_merge($data, ['created_by' => null]));
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function statusLabels(): array
    {
        return [
            [
                'name' => 'Stand-by',
                'notes' => 'Wacht op intake of triage.',
                'deployable' => 0,
                'pending' => 1,
                'archived' => 0,
                'default_label' => 1,
                'show_in_nav' => 1,
                'color' => '#1abc9c',
            ],
            [
                'name' => 'Being Processed',
                'notes' => 'Actief in test-, wipe- of herstelproces.',
                'deployable' => 0,
                'pending' => 1,
                'archived' => 0,
                'default_label' => 0,
                'show_in_nav' => 1,
                'color' => '#3498db',
            ],
            [
                'name' => 'QA Hold',
                'notes' => 'Geblokkeerd tot accessoires of cosmetica gereed zijn.',
                'deployable' => 0,
                'pending' => 1,
                'archived' => 0,
                'default_label' => 0,
                'show_in_nav' => 1,
                'color' => '#9b59b6',
            ],
            [
                'name' => 'Ready for Sale',
                'lifecycle_stage' => Statuslabel::LIFECYCLE_READY_FOR_SALE,
                'notes' => 'Volledig getest en klaar voor verkoop.',
                'deployable' => 1,
                'pending' => 0,
                'archived' => 0,
                'default_label' => 0,
                'show_in_nav' => 1,
                'color' => '#2ecc71',
            ],
            [
                'name' => 'Sold',
                'lifecycle_stage' => Statuslabel::LIFECYCLE_SOLD,
                'notes' => 'Order afgerond en uit voorraad.',
                'deployable' => 0,
                'pending' => 0,
                'archived' => 1,
                'default_label' => 0,
                'show_in_nav' => 1,
                'color' => '#e67e22',
            ],
            [
                'name' => 'Broken / Parts',
                'lifecycle_stage' => Statuslabel::LIFECYCLE_BROKEN_PARTS,
                'notes' => 'Niet verkoopbaar; gebruikt voor onderdelen of referentie.',
                'deployable' => 0,
                'pending' => 0,
                'archived' => 0,
                'default_label' => 0,
                'show_in_nav' => 1,
                'color' => '#e74c3c',
            ],
            [
                'name' => 'Internal Use',
                'notes' => 'Beschikbaar voor interne teams of labopstellingen.',
                'deployable' => 0,
                'pending' => 0,
                'archived' => 0,
                'default_label' => 0,
                'show_in_nav' => 1,
                'color' => '#34495e',
            ],
            [
                'name' => 'Archived',
                'notes' => 'Gearchiveerd voor naslag, niet actief in omloop.',
                'deployable' => 0,
                'pending' => 0,
                'archived' => 1,
                'default_label' => 0,
                'show_in_nav' => 1,
                'color' => '#7f8c8d',
            ],
            [
                'name' => 'Returned / RMA',
                'lifecycle_stage' => Statuslabel::LIFECYCLE_RETURNED,
                'notes' => 'Retour ontvangen; wacht op herinspectie.',
                'deployable' => 0,
                'pending' => 1,
                'archived' => 0,
                'default_label' => 0,
                'show_in_nav' => 1,
                'color' => '#f1c40f',
            ],
        ];
    }
}
