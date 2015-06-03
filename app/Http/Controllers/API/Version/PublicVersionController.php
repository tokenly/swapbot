<?php

namespace Swapbot\Http\Controllers\API\Version;

use Exception;
use Illuminate\Support\Facades\Log;
use Swapbot\Http\Controllers\API\Base\APIController;
use Illuminate\Http\JsonResponse;

class PublicVersionController extends APIController {

    protected $protected = false;

    /**
     * Display a version for checking deployment
     *
     * @return Illuminate\Http\JsonResponse
     */
    public function getVersion()
    {
        $result = ['version' => 'unknown',];
        $version_file = base_path().'/../version';
        if (file_exists($version_file)) {
            $version_info = json_decode(file_get_contents($version_file), true);
            if ($version_info) {
                $result['version'] = $version_info['object']['sha'];
                return new JsonResponse($result, 200);
            }
        }

        return new JsonResponse($result, 400);
    }


}
