<?php

use Illuminate\Database\QueryException;
use \PHPUnit_Framework_Assert as PHPUnit;

class NotificationReceiptRepositoryTest extends TestCase {

    protected $use_database = true;

    public function testCreateAndLoadNotificationReceipt() {
        $repo = app('Swapbot\Repositories\NotificationReceiptRepository');
        $created_receipt = $repo->createByUUID('uuid01');
        PHPUnit::assertNotEmpty($created_receipt);
        PHPUnit::assertNotEmpty($created_receipt['id']);

        $loaded_receipt = $repo->findByNotificationUUID('uuid01');
        PHPUnit::assertEquals($created_receipt, $loaded_receipt);

        $loaded_receipt_2 = $repo->findByID($created_receipt['id']);
        PHPUnit::assertEquals($created_receipt, $loaded_receipt_2);
    }

    public function testCreateDuplicateNotificationReceipt() {
        $repo = app('Swapbot\Repositories\NotificationReceiptRepository');
        $created_receipt = $repo->createByUUID('uuid01');

        // try to create it again
        $error_code = null;
        try {
            $repo->createByUUID('uuid01');
        } catch (QueryException $e) {
            $error_code = $e->errorInfo[0];
        }
        PHPUnit::assertEquals(23000, $error_code);
    }


}
