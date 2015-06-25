<?php

namespace Swapbot\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Swapbot\Models\Bot;
use Swapbot\Models\BotEvent;
use Swapbot\Models\BotLedgerEntry;
use Swapbot\Models\User;
use Tokenly\CurrencyLib\CurrencyUtil;
use Tokenly\LaravelApiProvider\Repositories\APIRepository;
use \Exception;

/*
* BotLedgerEntryRepository
*/
class BotLedgerEntryRepository extends APIRepository
{

    protected $model_type = 'Swapbot\Models\BotLedgerEntry';

    public function findByBot(Bot $bot) {
        return $this->findByBotId($bot['id']);
    }

    public function findByBotId($bot_id) {
        return $this->prototype_model->where('bot_id', $bot_id)->orderBy('id')->get();
    }

    public function findByBotWithBotEventEntries(Bot $bot) {
        $bot_id = $bot['id'];

        return DB::table('bot_ledger_entries')
            ->join('bot_events', 'bot_events.id', '=', 'bot_ledger_entries.bot_event_id')
            ->where('bot_ledger_entries.bot_id', $bot_id)
            ->orderBy('bot_ledger_entries.id')
            ->get(['bot_ledger_entries.*', 'bot_events.event']);
    }

    public function addCredit(Bot $bot, $float_amount, $asset, BotEvent $bot_event) {
        return $this->addEntryForBot($bot, $float_amount, $asset, true, $bot_event['id']);
    }

    public function addDebit(Bot $bot, $float_amount, $asset, BotEvent $bot_event) {
        return $this->addEntryForBot($bot, $float_amount, $asset, false, $bot_event['id']);
    }

    public function sumCreditsAndDebits(Bot $bot, $asset) {
        $bot_id = $bot['id'];

        $credits_amount = $this->prototype_model
            ->where('bot_id', $bot_id)
            ->where('is_credit', 1)
            ->where('asset', $asset)
            ->sum('amount');

        $debits_amount = $this->prototype_model
            ->where('bot_id', $bot_id)
            ->where('is_credit', 0)
            ->where('asset', $asset)
            ->sum('amount');

        return CurrencyUtil::satoshisToValue($credits_amount - $debits_amount);
    }


    public function sumCreditsAndDebitsByAsset(Bot $bot) {
        $bot_id = $bot['id'];

        
        $all_credit_rows = $this->prototype_model
            ->where('bot_id', $bot_id)
            ->where('is_credit', 1)
            ->groupBy('asset')
            ->select('asset', DB::raw('SUM(amount) AS amount'))->get();

        $all_debit_rows = $this->prototype_model
            ->where('bot_id', $bot_id)
            ->where('is_credit', 0)
            ->groupBy('asset')
            ->select('asset', DB::raw('SUM(amount) AS amount'))->get();

        $satoshi_totals_by_asset = [];

        foreach($all_credit_rows as $credit_row) {
            $asset = $credit_row['asset'];
            if (!isset($satoshi_totals_by_asset[$asset])) { $satoshi_totals_by_asset[$asset] = 0; }
            $satoshi_totals_by_asset[$asset] += $credit_row['amount'];
        }
        foreach($all_debit_rows as $debit_row) {
            $asset = $debit_row['asset'];
            if (!isset($satoshi_totals_by_asset[$asset])) { $satoshi_totals_by_asset[$asset] = 0; }
            $satoshi_totals_by_asset[$asset] -= $debit_row['amount'];
        }

        // convert satoshis to float
        $out = [];
        foreach($satoshi_totals_by_asset as $asset => $satoshis) {
            $out[$asset] = CurrencyUtil::satoshisToValue($satoshis);
        }
        return $out;
    }


    public function update(Model $model, $attributes) { throw new Exception("Updates are not allowed", 1); }

    protected function addEntryForBot(Bot $bot, $float_amount, $asset, $is_credit, $bot_event_id) {
        $create_vars = [
            'user_id'      => $bot['user_id'],
            'bot_id'       => $bot['id'],
            'bot_event_id' => $bot_event_id,
            'is_credit'    => $is_credit,
            'amount'       => CurrencyUtil::valueToSatoshis($float_amount),
            'asset'        => $asset,
        ];
        return $this->create($create_vars);
    }

}
