<?php

namespace Swapbot\Repositories\Base;

use Illuminate\Database\Eloquent\Model;
use Rhumsaa\Uuid\Uuid;
use Swapbot\Models\User;
use Swapbot\Repositories\Contracts\APIResourceRepositoryContract;
use \Exception;

/*
* APIRepository
*/
abstract class APIRepository implements APIResourceRepositoryContract
{

    // must define this
    protected $model_type = '';


    public function findByID($id) {
        return call_user_func([$this->model_type, 'find'], $id);
    }

    public function update(Model $model, $attributes) {
        return $model->update($attributes);
    }

    public function delete(Model $model) {
        return $model->delete();
    }


    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // API Model Contract

    public function create($attributes) {
        if (!isset($attributes['uuid'])) { $attributes['uuid'] = Uuid::uuid4()->toString(); }

        return call_user_func([$this->model_type, 'create'], $attributes);
    }

    public function findAll() {
        return call_user_func([$this->model_type, 'all']);
    }

    public function findByUuid($uuid) {
        return call_user_func([$this->model_type, 'where'], 'uuid', $uuid)->first();
    }

    public function updateByUuid($uuid, $attributes) {
        $model = $this->findByUuid($uuid);
        if (!$model) { throw new Exception("Unable to find model for uuid $uuid", 1); }
        return $this->update($model, $attributes);
    }

    public function deleteByUuid($uuid) {
        $model = $this->findByUuid($uuid);
        if (!$model) { throw new Exception("Unable to find model for uuid $uuid", 1); }

        return self::delete($model);
    }

}
