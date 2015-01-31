<?php

namespace Swapbot\Models\Base;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use \Exception;

/*
* APIModel
*/
class APIModel extends Model
{

    protected static $unguarded = true;

    protected $api_attributes = ['id'];

    public function serializeForAPI() {
        $out = $this->attributesToArray();

        $out = [];
        foreach($this->api_attributes as $api_attribute) {
            if ($api_attribute == 'id' AND isset($this['uuid'])) {
                $out['id'] = $this['uuid'];
            } else {
                $value = $this[$api_attribute];
                if ($value instanceof Carbon) {
                    $value = $value->toIso8601String();
                }

                $out[camel_case($api_attribute)] = $value;
            }
        }

        return $out;
    }


}
