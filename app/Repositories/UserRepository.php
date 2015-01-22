<?php

namespace Swapbot\Repositories;

use Illuminate\Database\Eloquent\Model;
use Rhumsaa\Uuid\Uuid;
use Swapbot\Models\User;
use Swapbot\Repositories\Base\APIRepository;
use Swapbot\Repositories\Contracts\APIResourceRepositoryContract;
use \Exception;

/*
* UserRepository
*/
class UserRepository extends APIRepository implements APIResourceRepositoryContract
{

    protected $model_type = 'Swapbot\Models\User';


    public function findByEmail($email) {
        return User::where('email', $email)->first();
    }

}
