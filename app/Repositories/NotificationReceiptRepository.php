<?php

namespace Swapbot\Repositories;

use Swapbot\Models\NotificationReceipt;
use \Exception;

/*
* NotificationReceiptRepository
*/
class NotificationReceiptRepository
{

    protected $model_type = 'Swapbot\Models\NotificationReceipt';

    public function findByID($id) {
        return call_user_func([$this->model_type, 'find'], $id);
    }

    public function findByNotificationUUID($uuid) {
        return call_user_func([$this->model_type, 'where'], 'notification_uuid', $uuid)->first();
    }

    public function createByUUID($uuid) {
        // create a new model
        $create_vars = ['notification_uuid' => $uuid];
        return $this->create($create_vars);
    }

    public function create($attributes) {
        return call_user_func([$this->model_type, 'create'], $attributes);
    }


}
