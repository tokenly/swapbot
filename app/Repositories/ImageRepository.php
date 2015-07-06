<?php

namespace Swapbot\Repositories;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Swapbot\Models\Bot;
use Swapbot\Models\Image;
use Swapbot\Models\User;
use Tokenly\LaravelApiProvider\Repositories\APIRepository;
use \Exception;

/*
* ImageRepository
*/
class ImageRepository extends APIRepository
{

    protected $model_type = 'Swapbot\Models\Image';

    public function createForUser(User $user, $image_file) {
        $attributes = [
            'user_id' => $user['id'],
            'image'   => $image_file,
        ];

        return $this->create($attributes);
    }

    public function findByUser(User $user) {
        return $this->prototype_model
            ->where('user_id', $user['id'])
            ->get();
    }

    public function replaceImage(Image $image, $image_file) {
        $this->update($image, ['image' => $image_file]);
        return $image;
    }



    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Modify
    

}
