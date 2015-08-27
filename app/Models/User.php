<?php

namespace Swapbot\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Swapbot\Models\Base\APIModel;
use Tokenly\LaravelApiProvider\Contracts\APIPermissionedUserContract;
use Tokenly\LaravelApiProvider\Contracts\APIUserContract;

class User extends APIModel implements AuthenticatableContract, CanResetPasswordContract, APIUserContract, APIPermissionedUserContract {

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

    public function setEmailPreferencesAttribute($email_preferences) { $this->attributes['email_preferences'] = json_encode($email_preferences); }
    public function getEmailPreferencesAttribute() { return $this->decodeEmailPreferences((isset($this->attributes['email_preferences']) AND strlen($this->attributes['email_preferences'])) ? json_decode($this->attributes['email_preferences'], true) : []); }


    public function getEmailPreference($preference_type) {
        $prefs = (isset($this->attributes['email_preferences']) AND strlen($this->attributes['email_preferences'])) ? json_decode($this->attributes['email_preferences'], true) : [];
        return (isset($prefs[$preference_type]) ? !!$prefs[$preference_type] : true);
    }

    public function getAllEmailPreferenceTypes() {
        return [
            'adminEvents' => [
                'name'     => 'adminEvents',
                'label'    => 'Administrative Events For My Swapbots',
                'subtitle' => 'Includes notifications about upcoming swapbot payments and low BTC fuel',
            ],
            // 'swapEvents' => [
            //     'name'     => 'swapEvents',
            //     'label'    => 'Updates to My Swaps',
            //     'subtitle' => 'This includes notifications about each swap',
            // ],
        ];
    }

    // APIUserContract
    public function getID() { return $this['id']; }
    public function getUuid() { return $this['uuid']; }
    public function getApiSecretKey() { return $this['apisecretkey']; }


    ////////////////////////////////////////////////////////////////////////
    
    protected function decodeEmailPreferences($email_preferences) {
        foreach ($this->getAllEmailPreferenceTypes() as $type_info) {
            $name = $type_info['name'];
            if (!isset($email_preferences[$name])) {
                $email_preferences[$name] = true;
            }
        }

        return $email_preferences;
    }
}
