<?php

use Illuminate\Contracts\Validation\ValidationException;
use Rhumsaa\Uuid\Uuid;
use Swapbot\Models\BotEvent;
use Swapbot\Repositories\BotEventRepository;

class BotEventHelper  {

    function __construct(BotEventRepository $bot_event_repository) {
        $this->bot_event_repository = $bot_event_repository;
    }

    public function sampleBotEventVars() {
        return [
            'level' => BotEvent::LEVEL_INFO,
            'event' => ['msg' => 'test bot event',],
        ];
    }


    // creates a bot
    //   directly in the repository (no validation)
    public function newSampleBotEvent($bot=null, $bot_event_vars=[]) {
        $attributes = array_replace_recursive($this->sampleBotEventVars(), $bot_event_vars);
        if ($bot == null) {
            $bot = app('BotHelper')->getSampleBot();
        }
        $attributes['bot_id'] = $bot['id'];

        try {
            if (!isset($attributes['uuid'])) {
                $uuid = Uuid::uuid4()->toString();
                $attributes['uuid'] = $uuid;
            }

            $bot_event_model = $this->bot_event_repository->create($attributes);
            return $bot_event_model;
        } catch (ValidationException $e) {
            throw new Exception("ValidationException: ".json_encode($e->errors()->all(), 192), $e->getCode());
        }
    }




}
