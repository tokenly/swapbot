<?php

use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Rhumsaa\Uuid\Uuid;
use Swapbot\Commands\CreateBot;
use Swapbot\Repositories\BotRepository;

class BotHelper  {

    use DispatchesCommands;

    function __construct(BotRepository $bot_repository) {
        $this->bot_repository = $bot_repository;
    }

    public function sampleBotVars() {
        return [
            'name'        => 'Sample Bot One',
            'description' => 'The bot description goes here.',
            'swaps' => [
                [
                    'in'   => 'BTC',
                    'out'  => 'LTBCOIN',
                    'rate' => 0.00000150,
                ],
            ],
            'active'      => false,
        ];
    }

    public function newSampleBot($user=null) {
        $attributes = $this->sampleBotVars();
        if ($user == null) {
            $user = app()->make('UserHelper')->getSampleUser();
        }
        $attributes['user_id'] = $user['id'];

        try {
            $uuid = Uuid::uuid4()->toString();
            $attributes['uuid'] = $uuid;
            $this->dispatch(new CreateBot($attributes));

            // now load the model
            return $this->bot_repository->findByUuid($uuid);
        } catch (ValidationException $e) {
            throw new Exception("ValidationException: ".json_encode($e->errors()->all(), 192), $e->getCode());
        }
    }

}
