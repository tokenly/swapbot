<?php

namespace Swapbot\Repositories\Mock;

use Swapbot\Models\Image;
use Swapbot\Repositories\ImageRepository;
use \Exception;

/*
* MockImageRepository
*/
class MockImageRepository extends ImageRepository
{

    protected $model_type = 'Swapbot\Models\Mock\MockImage';



}
