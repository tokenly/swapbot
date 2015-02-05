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

    public function getSampleUser($email='sample@tokenly.co', $token=null, $owner_user_id=null) {
        $user = $this->user_repository->findByEmail($email);
        if (!$user) {
            if ($token === null) { $token = $this->testingTokenFromEmail($email); }
            $user = $this->newSampleUser(['email' => $email, 'apitoken' => $token, 'user_id' => $owner_user_id]);
        }
        return $user;
    }

    public function newSampleUser($override_vars=[]) {
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

    public function testingTokenFromEmail($email) {
        switch ($email) {
            case 'sample@tokenly.co': return 'TESTAPITOKEN';
            default:
                // user2@tokenly.co => TESTUSER2TOKENLYCO
                return substr('TEST'.strtoupper(preg_replace('!^[^a-z0-9]$!i', '', $email)), 0, 16);
        }
        // code
    }

    public function randomEmail() {
        return 'u'.substr(md5(uniqid('', true)), 0, 6).'@tokenly.co';
    }


}