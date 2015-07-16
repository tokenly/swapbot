<?php

namespace Swapbot\Swap\Logger\OutputTransformer\Provider;

use Exception;
use Illuminate\Support\ServiceProvider;
use Swapbot\Swap\Logger\OutputTransformer\BotEventOutputTransformer;

class BotEventOutputTransformerServiceProvider extends ServiceProvider {

    public function register() {
        $this->app->bind('boteventlogoutput', function($app) {
            return new BotEventOutputTransformer(app('Swapbot\Models\Formatting\FormattingHelper'), app('Swapbot\Swap\Logger\BotEventLogger'));
        });

    }



    

}
