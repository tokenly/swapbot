<?php

use Swapbot\Util\Validator\ValidatorHelper;
use \PHPUnit_Framework_Assert as PHPUnit;

class StrategyHelpersTest extends TestCase {

    protected $use_database = false;

    public function testValidAssetNames() {
        $this->assertValidAsset('BTC');
        $this->assertValidAsset('XCP');
        $this->assertValidAsset('SOUP');
        $this->assertInvalidAsset('ABLE');

        $this->assertInvalidAsset('A1234567890');
        $this->assertInvalidAsset('A1234567890X');
        $this->assertInvalidAsset('A95428956661682199');
        $this->assertInvalidAsset('A95428956661682200');
        $this->assertValidAsset(  'A95428956661682201');
        $this->assertValidAsset(  'A18446744073709600000');
        $this->assertInvalidAsset('A18446744073709600001');
    }

    ////////////////////////////////////////////////////////////////////////
    
    
    protected function assertValidAsset($name) {
        PHPUnit::assertTrue(ValidatorHelper::isValidAssetName($name), "The name $name was not valid and it should have been");
    }

    protected function assertInvalidAsset($name) {
        PHPUnit::assertFalse(ValidatorHelper::isValidAssetName($name), "The name $name was valid and it should not have been");
    }


}
