<?php namespace Swapbot\Http\Controllers;

use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Swapbot\Commands\ReceiveWebhook;
use Swapbot\Http\Controllers\Controller;
use Swapbot\Http\Requests;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\XChainClient\WebHookReceiver;

class WebhookController extends Controller {

	public function receive(WebHookReceiver $webhook_receiver, Request $request) {
        try {
            $data = $webhook_receiver->validateAndParseWebhookNotificationFromRequest($request);

            $payload = $data['payload'];

            $this->dispatch(new ReceiveWebhook($payload));

        } catch (Exception $e) {
            EventLog::logError('webhook.error', $e);
            if ($e instanceof HttpResponseException) { throw $e; }
            throw new HttpResponseException(new Response("An error occurred"), 500);
        }

        return 'ok';
    }

}
