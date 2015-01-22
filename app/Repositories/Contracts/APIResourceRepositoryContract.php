<?php

namespace Swapbot\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;
use \Exception;

/*
* APIResourceRepositoryContract
*/
interface APIResourceRepositoryContract
{


    public function create($attributes);

    public function findAll();

    public function findByUuid($uuid);

    public function deleteByUuid($uuid);

    public function updateByUuid($uuid, $attributes);


}
