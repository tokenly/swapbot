<?php

namespace Swapbot\Models\Mock;

use Exception;
use Swapbot\Models\Base\APIModel;
use Swapbot\Models\Image;
use Codesleeve\Stapler\Factories\Attachment as AttachmentFactory;


class MockImage extends Image {

    public function __construct(array $attributes = array()) {
        APIModel::__construct($attributes);
    }
 

    public function setAttribute($key, $value)
    {
        if ($key == 'image') {
            if ($value) {
                parent::setAttribute('image_file_name', $value);
            }

            return;
        }

        parent::setAttribute($key, $value);
    }

    public function getAttribute($key) {
        if ($key == 'image') {
            $attachment = AttachmentFactory::create('image', []);
            $attachment->setInstance($this);
            return $attachment;
        }

        return parent::getAttribute($key);
    }

}
