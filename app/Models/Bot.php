<?php

namespace Swapbot\Models;

use Swapbot\Models\Base\APIModel;

class Bot extends APIModel {

    public function setAssetsAttribute($assets) { $this->attributes['assets'] = json_encode($this->serializeAssets($assets)); }
    public function getAssetsAttribute() { return $this->deSerializeAssets(json_decode($this->attributes['assets'], true)); }


    public function serializeAssets($assets) {
        $serialized_assets = [];
        foreach($assets as $asset) {
            $serialized_assets[] = [$asset['in'], $asset['out'], $asset['rate']];
        }
        return $serialized_assets;
    }

    public function deSerializeAssets($serialized_assets) {
        $deserialized_assets = [];
        foreach($serialized_assets as $asset) {
            $deserialized_assets[] = [
                'in'   => $asset[0],
                'out'  => $asset[1],
                'rate' => $asset[2],
            ];
        }
        return $deserialized_assets;
    }

}
