<?php

use \PHPUnit_Framework_Assert as PHPUnit;

class UserRepositoryTest extends TestCase {

    protected $use_database = true;

    public function testFindUserByUsername() {
        $actual_user = app('UserHelper')->newSampleUser();
        $repo = app('Swapbot\Repositories\UserRepository');

        // load with bad username
        $loaded_user = $repo->findByUsername('baduser');
        PHPUnit::assertNull($loaded_user);

        // load with good user id
        $loaded_user = $repo->findByUsername($actual_user['username']);
        PHPUnit::assertNotNull($loaded_user);
        PHPUnit::assertEquals($actual_user['id'], $loaded_user['id']);
    }


}
