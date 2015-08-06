<?php

use \PHPUnit_Framework_Assert as PHPUnit;

class BlockRepositoryTest extends TestCase {

    protected $use_database = true;

    public function testBlockRepository()
    {
        $repo = app('Swapbot\Repositories\BlockRepository');
        $helper = app('BlockHelper');

        $helper->newSampleBlock(300002);
        $helper->newSampleBlock(300001);
        $helper->newSampleBlock(300000);

        $best_block = $repo->findBestBlock();
        PHPUnit::assertEquals(300002, $best_block['height']);
        PHPUnit::assertEquals(300002, $repo->findBestBlockHeight());
    }


}
