<?php

namespace Swapbot\Swap\Logger\OutputTransformer;

use Exception;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Swapbot\Models\BotEvent;
use Tokenly\LaravelEventLog\Facade\EventLog;

class BotEventOutputTransformer {

    protected $EVENT_TEMPLATE_DATA = null;

    /**
     */
    public function __construct()
    {
    }


    public function buildMessage(BotEvent $event) {
        $event_details = $event['event'];
        return $this->buildMessageFromEventDetails($event_details, $event);
    }

    public function buildMessageFromEventDetails($event_details) {
        if (is_string($event_details)) { $event_details = json_decode($event_details, true); }

        $name = (isset($event_details['name']) ? $event_details['name'] : 'undefined');
        Log::debug('$event_details='.json_encode($event_details, 192));

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

        $resolved_message = $this->resolveBladeSrc($compiled_blade_src, $event_details);
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
