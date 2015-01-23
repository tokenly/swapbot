<?php

namespace Swapbot\Http\Requests\Bot;

use Swapbot\Http\Requests\Request;

class DectivateBotRequest extends Request {

    public function rules() {
        return [];
    }

    public function authorize() {
        return true;
    }


}
