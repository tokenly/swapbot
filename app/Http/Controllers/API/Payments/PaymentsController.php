<?php

namespace Swapbot\Http\Controllers\API\Payments;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Log;
use Swapbot\Http\Controllers\API\Base\APIController;
use Swapbot\Models\BotLedgerEntry;
use Swapbot\Repositories\BotLedgerEntryRepository;
use Swapbot\Repositories\BotRepository;
use Swapbot\Swap\Logger\OutputTransformer\Facade\BotEventOutputTransformer;
use Tokenly\LaravelApiProvider\Helpers\APIControllerHelper;

class PaymentsController extends APIController {

    /**
     * Display a listing of the resource.
     *
     * @param  Guard               $auth
     * @param  BotRepository       $repository
     * @param  APIControllerHelper $api_helper
     * @return Response
     */
    public function index($botuuid, Guard $auth, BotRepository $bot_repository, BotLedgerEntryRepository $bot_ledger_entry_repository, APIControllerHelper $api_helper)
    {
        $user = $auth->getUser();

        // get the bot
        $bot = $api_helper->requireResourceOwnedByUserOrWithPermssion($botuuid, $user, $bot_repository, 'viewBots');

        // get all payments for this bot
        // $resources = $bot_ledger_entry_repository->findByBot($bot);
        $resources = $bot_ledger_entry_repository->findByBotWithBotEventEntries($bot);

        // add the event msg to the ledger entry
        $prototype_entry = new BotLedgerEntry([]);
        $attributes = $prototype_entry->getAPIAttributes();
        $out = [];
        foreach($resources as $resource) {
            
            $model = new BotLedgerEntry((array)$resource);
            $row = $model->serializeForAPI();

            // foreach($attributes as $attribute) {
            //     $row[$attribute] = $resource->{$attribute};
            // }
            $event = json_decode($resource->event, true);
            // $row['id'] = $resource->uuid;

            $row['msg'] = BotEventOutputTransformer::buildMessageFromEventDetails($resource->event);
            $out[] = $row;
        }

        // format for API
        return $api_helper->transformValueForOutput($out);
    }


    public function balances($botuuid, Guard $auth, BotRepository $bot_repository, BotLedgerEntryRepository $bot_ledger_entry_repository, APIControllerHelper $api_helper)
    {
        $user = $auth->getUser();

        // get the bot
        $bot = $api_helper->requireResourceOwnedByUserOrWithPermssion($botuuid, $user, $bot_repository, 'viewBots');

        // get all payment balances for this bot
        $balances = $bot_ledger_entry_repository->sumCreditsAndDebitsByAsset($bot);

        // format for API
        return $api_helper->transformValueForOutput(['balances' => $balances]);
    }


    // public function prices($botuuid, Guard $auth, PaymentPlans $payment_plans, BotRepository $bot_repository, BotLedgerEntryRepository $bot_ledger_entry_repository, APIControllerHelper $api_helper)
    // {
    //     $user = $auth->getUser();

    //     // get the bot
    //     $bot = $api_helper->requireResourceOwnedByUserOrWithPermssion($botuuid, $user, $bot_repository, 'viewBots');

    //     // get rates for this bot
    //     $rates = $payment_plans->getMonthlyRates($bot['payment_plan']);

    //     // format for API
    //     return $api_helper->transformValueForOutput($rates);
    // }



}
