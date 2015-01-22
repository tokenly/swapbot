<?php

namespace Swapbot\Repositories;

use Swapbot\Models\Bot;
use Swapbot\Repositories\Base\APIRepository;
use Swapbot\Repositories\Contracts\APIResourceRepositoryContract;
use \Exception;

/*
* BotRepository
*/
class BotRepository extends APIRepository implements APIResourceRepositoryContract
{

    protected $model_type = 'Swapbot\Models\Bot';

    public function compactAssetAttributes($vars) {
        $vars_out = $vars;
        $serialized_asset_value = [];

        for ($i=1; $i <= 5; $i++) { 
            $in_field_name = 'asset_in_'.$i;
            $in_value = isset($vars[$in_field_name]) ? $vars[$in_field_name] : null;
            $out_field_name = 'asset_out_'.$i;
            $out_value = isset($vars[$out_field_name]) ? $vars[$out_field_name] : null;
            $rate_field_name = 'vend_rate_'.$i;
            $rate_value = isset($vars[$rate_field_name]) ? $vars[$rate_field_name] : null;

            unset($vars_out[$in_field_name]);
            unset($vars_out[$out_field_name]);
            unset($vars_out[$rate_field_name]);


            $exists = (strlen($in_value) OR strlen($out_value) OR strlen($rate_value));
            if ($exists) {
                $serialized_asset_value[] = [
                    'in'   => $in_value,
                    'out'  => $out_value,
                    'rate' => $rate_value,
                ];
            }
        }

        $vars_out['assets'] = $serialized_asset_value;

        return $vars_out;
    }

}
