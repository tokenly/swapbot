<?php

use Swapbot\Models\Bot;
use Swapbot\Models\Image;
use Swapbot\Repositories\ImageRepository;

class ImageHelper  {

    function __construct(ImageRepository $image_repository) {
        $this->image_repository = $image_repository;
    }


    public function sampleImageVars() {
        return [
            'image_file_name'    => 'foo.jpg',
            'image_content_type' => 'image/jpeg',
            'image_file_size'    => 100,
            'image_updated_at'   => '2015-07-01',
        ];
    }

    // creates a sample swap
    //   directly in the repository (no validation)
    public function newSampleImage($user=null, $image_vars=[]) {
        $attributes = array_replace_recursive($this->sampleImageVars(), $image_vars);
        if ($user == null) { $user = app('UserHelper')->newRandomUser(); }

        if (!isset($attributes['user_id'])) { $attributes['user_id'] = $user['id']; }

        $image_model = $this->image_repository->create($attributes);
        return $image_model;
    }


    public function bindMockImageRepository() {
        app()->bind('Swapbot\Repositories\ImageRepository', 'Swapbot\Repositories\Mock\MockImageRepository');
        $this->image_repository = app('Swapbot\Repositories\ImageRepository');
        return $this;
    }

}
