<?php

use Illuminate\Contracts\Validation\ValidationException;
use Swapbot\Models\Block;
use Swapbot\Repositories\BlockRepository;

class BlockHelper  {

    function __construct(BlockRepository $block_repository) {
        $this->block_repository = $block_repository;
    }


    public function sampleBlockVars($height=300000) {
        return [
            'height' => $height,
            'hash'   => $this->sampleBlockHash($height),
        ];
    }

    public function sampleBlockHash($height) {
        return '00000000000000000000000000000000'.md5('BLOCK'.$height);
    }

    // creates a block
    //   directly in the repository (no validation)
    public function newSampleBlock($height=300000, $block_vars=[]) {
        $attributes = array_replace_recursive($this->sampleBlockVars($height), $block_vars);
        $block_model = $this->block_repository->create($attributes);
        return $block_model;
    }




}
