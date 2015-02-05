<?php

namespace Swapbot\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Swapbot\Models\Base\APIModel;

class User extends APIModel implements AuthenticatableContract, CanResetPasswordContract {

    protected $api_attributes = ['id', 'name', 'email', 'apitoken', 'apisecretkey', 'privileges',];

    use Authenticatable, CanResetPassword;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'email', 'password'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    public function hasPermission($privilege) {
        $privileges = $this['privileges'];
        return (isset($privileges[$privilege]) AND $privileges[$privilege]);
    }

    public function setPrivilegesAttribute($privileges) { $this->attributes['privileges'] = json_encode($privileges); }
    public function getPrivilegesAttribute() { return isset($this->attributes['privileges']) ? json_decode($this->attributes['privileges'], true) : []; }

}
