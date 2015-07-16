<?php

namespace Swapbot\Swap\Logger\OutputTransformer;

use Exception;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Swapbot\Models\BotEvent;
use Swapbot\Models\Formatting\FormattingHelper;
use Swapbot\Models\Swap;
use Swapbot\Swap\Logger\BotEventLogger;
use Tokenly\CurrencyLib\CurrencyUtil;
use Tokenly\LaravelEventLog\Facade\EventLog;

class BotEventOutputTransformer {

    protected $EVENT_TEMPLATE_DATA = null;

    /**
     */
    public function __construct(FormattingHelper $formatting_helper, BotEventLogger $bot_event_logger) {
        $this->formatting_helper = $formatting_helper;
        $this->bot_event_logger = $bot_event_logger;
    }

    public function isMissingStandardSwapEventData($bot_event_data) {
        $event = $bot_event_data['event'];
        if (!isset($event['quantityIn']) OR !isset($event['txidIn']) OR !isset($event['state'])) {
            return true;
        }

        return false;
    }

    public function addMissingStandardSwapEventData(Swap $swap, $bot_event_data) {
        $standard_swap_details = $this->bot_event_logger->standardSwapDetails($swap);
        $bot_event_data['event'] = array_merge($standard_swap_details, $bot_event_data['event']);
        return $bot_event_data;
    }


    public function buildMessage(BotEvent $event) {
        $event_details = $event['event'];
        return $this->buildMessageFromEventDetails($event_details, $event->swap);
    }

    public function buildMessageFromEventDetails($event_details, Swap $swap=null) {
        if (is_string($event_details)) { $event_details = json_decode($event_details, true); }

        if ($swap !== null) { $event_details['swap'] = $swap; }

        $name = (isset($event_details['name']) ? $event_details['name'] : 'undefined');
        // Log::debug('$event_details='.json_encode($event_details, 192));

        $message_template = $this->getEventTemplate($name);
        if (!$message_template) {
            // use the existing message
            return isset($event_details['msg']) ? $event_details['msg'] : null;
        }

        // resolve the template
        $compiled_blade_src = $message_template['msg'];

        // fill missing event vars
        foreach ($message_template['msgVars'] as $var_name) {
            if (!isset($event_details[$var_name])) { $event_details[$var_name] = ''; }
        }

        $blade_vars = $event_details;

        // helpers
        $blade_vars['fmt'] = $this->formatting_helper;
        $blade_vars['currency'] = function($value, $places=null) { return $this->formatting_helper->formatCurrency($value, $places); };

        $resolved_message = $this->resolveBladeSrc($compiled_blade_src, $blade_vars);
        return $resolved_message;
    }

    protected function getEventTemplate($event_name) {
        if (!isset($this->EVENT_TEMPLATE_DATA)) {
            $this->EVENT_TEMPLATE_DATA = include(realpath(base_path('resources/data/events/compiled')).'/allEvents.data.php');
        }

        if (!isset($this->EVENT_TEMPLATE_DATA[$event_name])) {
            return null;
        }

        return $this->EVENT_TEMPLATE_DATA[$event_name];
    }



    protected function resolveBladeSrc($__compiled_blade_src, $__data) {
        try {
            ob_start();
            extract($__data);
            eval('?>'.$__compiled_blade_src.'<?php ');
            return ltrim(ob_get_clean());
        } catch (Exception $e) {
            EventLog::logError('botevent.render.error', $e, ['eventName' => $__data['name'], ]);
            throw $e;
        }
    } 

}
