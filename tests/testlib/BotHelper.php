<?php

use Swapbot\Repositories\BotRepository;


class BotHelper  {

    function __construct(BotRepository $bot_repository) {
        $this->bot_repository = $bot_repository;
    }

    public function sampleBotVars() {
        return [
            'name'        => 'Sample Bot One',
            'description' => 'The bot description goes here.',
            'asset_in_1'  => 'BTC',
            'asset_out_1' => 'LTBCOIN',
            'vend_rate_1' => 0.00000150,
        ];
    }

    public function newSampleBot($user=null) {
        $vars = $this->bot_repository->compactSwapAttributes($this->sampleBotVars());
        if ($user == null) {
            $user = app()->make('UserHelper')->getSampleUser();
        }
        $vars['user_id'] = $user['id'];
        return $this->bot_repository->create($vars);
    }

}
