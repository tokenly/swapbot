<?php

use Codesleeve\Stapler\AttachmentConfig;
use Codesleeve\Stapler\Interpolator;
use Mockery as m;
use Swapbot\Models\Image;
use Tokenly\CurrencyLib\CurrencyUtil;
use Tokenly\RecordLock\Facade\RecordLock;
use \PHPUnit_Framework_Assert as PHPUnit;

class ImageRepositoryTest extends TestCase {

    protected $use_database = true;

    public function testLoadImage()
    {
        $helper = $this->createRepositoryTestHelper();

        $helper->testLoad();
        $helper->cleanup()->testUpdate(['image_file_name' => 'bar.jpg']);
        $helper->cleanup()->testDelete();
        $helper->cleanup()->testFindAll();
    }

    public function testFindOrReplaceImage()
    {
        $image_helper = $this->getImageHelper();
        $image_repository = app('Swapbot\Repositories\Mock\MockImageRepository');
        $bot = app('BotHelper')->newSampleBot();

        $image = $image_helper->newSampleImage();

        $image_repository->replaceImage($image, 'bar2.jpg');

        $reloaded_image = $image_repository->findByID($image['id']);
        PHPUnit::assertEquals('bar2.jpg', $reloaded_image['image_file_name']);

        $loaded_models = array_values(iterator_to_array($image_repository->findAll()));
        PHPUnit::assertCount(1, $loaded_models);
    }



    protected function createRepositoryTestHelper() {
        $create_model_fn = function() {
            return $this->getImageHelper()->newSampleImage();
        };
        $helper = new RepositoryTestHelper($create_model_fn, app('Swapbot\Repositories\Mock\MockImageRepository'));
        return $helper;
    }

    protected function getImageHelper() {
        return app('ImageHelper')->bindMockImageRepository();
    }


}
