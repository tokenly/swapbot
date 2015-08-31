<?php

namespace Swapbot\Repositories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Swapbot\Models\Bot;
use Swapbot\Models\BotEvent;
use Swapbot\Models\BotLeaseEntry;
use Swapbot\Swap\DateProvider\Facade\DateProvider;
use Tokenly\LaravelApiProvider\Repositories\APIRepository;
use \Exception;

/*
* BotLeaseEntryRepository
*/
class BotLeaseEntryRepository extends APIRepository
{

    protected $model_type = 'Swapbot\Models\BotLeaseEntry';

    public function findByBot(Bot $bot) {
        return $this->findByBotId($bot['id']);
    }

    public function findByBotId($bot_id) {
        return $this->prototype_model->where('bot_id', $bot_id)->orderBy('id')->get();
    }

    public function getLastEntryForBot(Bot $bot) {
        return $this->prototype_model->where('bot_id', $bot['id'])->orderBy('end_date', 'desc')->limit(1)->first();
    }

    public function findByBotWithBotEventEntries(Bot $bot) {
        $bot_id = $bot['id'];

        return DB::table('bot_lease_entries')
            ->join('bot_events', 'bot_events.id', '=', 'bot_lease_entries.bot_event_id')
            ->where('bot_lease_entries.bot_id', $bot_id)
            ->orderBy('bot_lease_entries.id')
            ->get(['bot_lease_entries.*', 'bot_events.event']);
    }

    public function addNewLease(Bot $bot, BotEvent $bot_event, Carbon $start_date, $length_in_months=1) {
        return $this->addEntryForBot($bot, $bot_event['id'], $start_date, $length_in_months);
    }

    public function extendLease(Bot $bot, BotEvent $bot_event, $length_in_months=1) {
        $last_lease = $this->getLastEntryForBot($bot);
        if ($last_lease) {
            return $this->addEntryForBot($bot, $bot_event['id'], Carbon::parse($last_lease['end_date']), $length_in_months);
        }

        // create a new lease if no old one was found
        return $this->addEntryForBot($bot, $bot_event['id'], DateProvider::now(), $length_in_months);

    }

    public function update(Model $model, $attributes) { throw new Exception("Updates are not allowed", 1); }

    protected function addEntryForBot(Bot $bot, $bot_event_id, Carbon $start_date, $length_in_months) {
        $end_date = $start_date->copy();
        for ($i=0; $i < $length_in_months; $i++) { 
            $end_date = $end_date->addMonthNoOverflow(1);
        }

        $create_vars = [
            'user_id'      => $bot['user_id'],
            'bot_id'       => $bot['id'],
            'bot_event_id' => $bot_event_id,
            'start_date'   => $start_date,
            'end_date'     => $end_date,
        ];

        return $this->create($create_vars);
    }

}
