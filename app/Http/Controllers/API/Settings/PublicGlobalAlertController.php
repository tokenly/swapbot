<?php

namespace Swapbot\Http\Controllers\API\Settings;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Swapbot\Http\Controllers\API\Base\APIController;
use Swapbot\Repositories\SettingRepository;
use Swapbot\Swap\Settings\Facade\Settings;
use Tokenly\LaravelApiProvider\Helpers\APIControllerHelper;

class PublicGlobalAlertController extends APIController {

    protected $protected = false;

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @param  Guard               $auth
     * @param  SettingRepository       $repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function getGlobalAlert(Guard $auth, SettingRepository $repository, APIControllerHelper $api_helper)
    {
        $setting_array = Settings::get('globalAlert');
        if (!$setting_array) {
            $setting_array = [
                'status'  => false,
                'content' => '',
            ];
        }

        return $api_helper->transformValueForOutput($setting_array);
    }


}
