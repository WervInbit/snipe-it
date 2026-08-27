<?php

namespace App\Importer;

use App\Models\Asset;
use App\Models\Statuslabel;
use Illuminate\Support\Facades\Crypt;

class AssetImporter extends ItemImporter
{
    protected $defaultStatusLabelId;

    public function __construct($filename)
    {
        parent::__construct($filename);

        $this->defaultStatusLabelId = Statuslabel::query()
            ->where(function ($query) {
                $query->whereNull('lifecycle_stage')
                    ->orWhereNotIn('lifecycle_stage', [
                        Statuslabel::LIFECYCLE_READY_FOR_SALE,
                        Statuslabel::LIFECYCLE_SOLD,
                    ]);
            })
            ->orderByDesc('default_label')
            ->orderByDesc('pending')
            ->orderBy('id')
            ->value('id');

        if (is_null($this->defaultStatusLabelId)) {
            $defaultLabel = Statuslabel::create([
                'name' => 'Default Status',
                'deployable' => 0,
                'pending' => 1,
                'archived' => 0,
                'notes' => 'Default status label created by AssetImporter',
            ]);

            $this->defaultStatusLabelId = $defaultLabel->id;
        }
    }

    protected function handle($row)
    {
        // ItemImporter handles the general fetching.
        parent::handle($row);

        if ($this->customFields) {
            foreach ($this->customFields as $customField) {
                $customFieldValue = $this->array_smart_custom_field_fetch($row, $customField);

                if ($customFieldValue) {
                    if ($customField->field_encrypted == 1) {
                        $this->item['custom_fields'][$customField->db_column_name()] = Crypt::encrypt($customFieldValue);
                        $this->log('Custom Field '.$customField->name.': '.Crypt::encrypt($customFieldValue));
                    } else {
                        $this->item['custom_fields'][$customField->db_column_name()] = $customFieldValue;
                        $this->log('Custom Field '.$customField->name.': '.$customFieldValue);
                    }
                } else {
                    // Clear out previous data.
                    $this->item['custom_fields'][$customField->db_column_name()] = null;
                }
            }
        }


        $this->createAssetIfNotExists($row);
    }

    /**
     * Asset assignment is not part of the fork's V1 lifecycle.
     *
     * ItemImporter normally resolves assignee columns before the item is saved,
     * which can create a user or location even when no checkout is performed.
     * Keep those legacy columns inert for Asset imports.
     */
    protected function determineCheckout($row)
    {
        return null;
    }

    /**
     * Create the asset if it does not exist.
     *
     * @author Daniel Melzter
     * @since 3.0
     * @param array $row
     * @return Asset|mixed|null
     */
    public function createAssetIfNotExists(array $row)
    {
        $editingAsset = false;
        $asset_tag = $this->findCsvMatch($row, 'asset_tag');

        if (empty($asset_tag)){
            $asset_tag = Asset::generateTag();
        }



        if ($this->findCsvMatch($row, 'id')!='') {
            // Override asset if an ID was given
            \Log::debug('Finding asset by ID: '.$this->findCsvMatch($row, 'id'));
            $asset = Asset::find($this->findCsvMatch($row, 'id'));
        } else {
            $asset = Asset::where(['asset_tag'=> (string) $asset_tag])->first();
        }
        
        if ($asset) {
            if (! $this->updating) {
                $exists_error = trans('general.import_asset_tag_exists', ['asset_tag' => $asset_tag]);
                $this->log($exists_error);
                $this->addErrorToBag($asset, 'asset_tag', $exists_error);
                return $exists_error;
            }

            $this->log('Updating Asset');
            $editingAsset = true;
        } else {
            $this->log('No Matching Asset, Creating a new one');
            $asset = new Asset;
        }

        // If no status ID is found
        if (! array_key_exists('status_id', $this->item) && ! $editingAsset) {
            $this->log('No status ID field found, defaulting to the preferred safe intake status label.');
            $this->item['status_id'] = $this->defaultStatusLabelId;
        }

        $this->item['notes'] = trim($this->findCsvMatch($row, 'asset_notes'));
        $this->item['image'] = basename(trim($this->findCsvMatch($row, 'image')));
        $this->item['warranty_months'] = intval(trim($this->findCsvMatch($row, 'warranty_months')));
        $this->item['model_id'] = $this->createOrFetchAssetModel($row);
        $byod = trim((string) $this->findCsvMatch($row, 'byod'));
        if ((! $this->updating) || ($byod !== '')) {
            $this->item['byod'] = ($this->fetchHumanBoolean($byod) == 1) ? '1' : 0;
            $asset->byod = $this->item['byod'];
        }
        $this->item['asset_eol_date'] = trim($this->findCsvMatch($row, 'asset_eol_date'));
        $this->item['asset_tag'] = $asset_tag;

        $item = $this->sanitizeItemForStoring($asset, $editingAsset);

        // The location id fetched by the csv reader is actually the rtd_location_id.
        // This will also set location_id, but then that will be overridden by the
        // checkout method if necessary below.
        if (isset($this->item['location_id'])) {
            $item['rtd_location_id'] = $this->item['location_id'];
        }


        if ($this->item['asset_eol_date']!='') {
            $item['asset_eol_date'] = $this->parseOrNullDate('asset_eol_date');
        }


        if ($editingAsset) {
            $asset->update($item);
        } else {
            $asset->fill($item);
        }

        // If we're updating, we don't want to overwrite old fields.
        if (array_key_exists('custom_fields', $this->item)) {
            foreach ($this->item['custom_fields'] as $custom_field => $val) {
                $asset->{$custom_field} = $val;
            }
        }

        // This sets an attribute on the Loggable trait for the action log
        $asset->setImported(true);

        if ($asset->save()) {

            $this->log('Asset '.$this->item['name'].' with serial number '.$this->item['serial'].' was created');

            return;
        }
        $this->logError($asset, 'Asset "'.$this->item['name'].'"');
    }


}
