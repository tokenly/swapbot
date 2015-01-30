<?php

namespace Swapbot\Models;

use Swapbot\Models\Base\APIModel;

class Bot extends APIModel {

    protected $api_attributes = ['id', 'name', 'description', 'swaps', 'address', 'active', ];

    public function setSwapsAttribute($swaps) { $this->attributes['swaps'] = json_encode($this->serializeSwaps($swaps)); }
    public function getSwapsAttribute() { return $this->deSerializeSwaps(json_decode($this->attributes['swaps'], true)); }

    public function setActiveAttribute($active) { $this->attributes['active'] = $active ? 1 : 0; }
    public function getActiveAttribute() { return !!$this->attributes['active']; }


    public function serializeSwaps($swaps) {
        $serialized_swaps = [];
        foreach($swaps as $asset) {
            $serialized_swaps[] = [$asset['in'], $asset['out'], $asset['rate']];
        }
        return $serialized_swaps;
    }

    public function deSerializeSwaps($serialized_swaps) {
        $deserialized_swaps = [];
        foreach($serialized_swaps as $asset) {
            $deserialized_swaps[] = [
                'in'   => $asset[0],
                'out'  => $asset[1],
                'rate' => $asset[2],
            ];
        }
        return $deserialized_swaps;
    }

}
