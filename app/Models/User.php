<?php

namespace Swapbot\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Swapbot\Models\Base\APIModel;
use Tokenly\LaravelApiProvider\Contracts\APIUserContract;

class User extends APIModel implements AuthenticatableContract, CanResetPasswordContract, APIUserContract {

    protected $api_attributes = ['id', 'name', 'username', 'email', 'apitoken', 'apisecretkey', 'privileges',];

    use Authenticatable, CanResetPassword;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    public function hasPermission($privilege) {
        $privileges = $this['privileges'];
        return (isset($privileges[$privilege]) AND $privileges[$privilege]);
    }

    public function setPrivilegesAttribute($privileges) { $this->attributes['privileges'] = json_encode($privileges); }
    public function getPrivilegesAttribute() { return isset($this->attributes['privileges']) ? json_decode($this->attributes['privileges'], true) : []; }


    // APIUserContract
    public function getID() { return $this['id']; }
    public function getUuid() { return $this['uuid']; }
    public function getApiSecretKey() { return $this['apisecretkey']; }

}
