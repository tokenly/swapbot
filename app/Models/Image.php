<?php

namespace Swapbot\Models;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Swapbot\Models\Base\APIModel;

use Codesleeve\Stapler\ORM\StaplerableInterface;
use Codesleeve\Stapler\ORM\EloquentTrait;

class Image extends APIModel implements StaplerableInterface {


    use EloquentTrait;

    protected $table = 'image';
    protected $api_attributes = ['id', 'image_details', ];



    public function __construct(array $attributes = array()) {

        $this->hasAttachedFile('image', [
            'styles' => [
                'full'   => '1440x632',
                'medium' => '600x90',
                'thumb'  => '90x90'
            ]
        ]);

        parent::__construct($attributes);
    }


    public function getImageDetailsAttribute() {
        $image = $this->image;
        if (!$image) { return []; }

        $details = [
            'id'               => $this['uuid'],
            'fullUrl'          => $image->url('full'),
            'mediumUrl'        => $image->url('medium'),
            'thumbUrl'         => $image->url('thumb'),
            'originalUrl'      => $image->url(),
            'contentType'      => $image->contentType(),
            'size'             => $image->size(),
            'originalFilename' => $image->originalFilename(),
            'updatedAt'        => $image->updatedAt(),
        ];

        return $details;
    }

}
