<?php

use Swapbot\Repositories\UserRepository;


/**
*  UserHelper
*/
class UserHelper
{

    function __construct(UserRepository $user_repository) {
        $this->user_repository = $user_repository;
    }

    public function getSampleUser($email='sample@tokenly.co') {
        $user = $this->user_repository->findByEmail($email);
        if (!$user) {
            $user = $this->createSampleUser(['email' => $email]);
        }
        return $user;
    }

    public function createSampleUser($override_vars=[]) {
        return $this->user_repository->create(array_merge($this->sampleVars(), $override_vars));
    }

    public function sampleVars($override_vars=[]) {
        return array_merge([
            'name'     => 'Sample User',
            'email'    => 'sample@tokenly.co',
            'password' => 'foopass',

            'apitoken'         => 'TESTAPITOKEN',
            'apisecretkey'     => 'TESTAPISECRET',
        ], $override_vars);
    }


}