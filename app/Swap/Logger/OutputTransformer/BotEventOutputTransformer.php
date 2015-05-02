<?php

namespace Swapbot\Swap\Logger\OutputTransformer;

use Exception;
use Illuminate\Support\Facades\Blade;
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
        $message_template = $this->getEventTemplate($event_details['name']);
        if (!$message_template) {
            // use the existing message
            return isset($event_details['msg']) ? $event_details['msg'] : null;
        }

        // resolve the template
        $compiled_blade_src = $message_template['msg'];
        $resolved_message = $this->resolveBladeSrc($compiled_blade_src, $event_details, $event);
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



    protected function resolveBladeSrc($__compiled_blade_src, $__data, $__event) {
        try {
            ob_start();
            extract($__data);
            eval('?>'.$__compiled_blade_src.'<?php ');
            return ltrim(ob_get_clean());
        } catch (Exception $e) {
            EventLog::logError('botevent.render.error', $e, ['eventId' => $__event['id'], 'eventName' => $__event['event']['name'], ]);
            throw $e;
        }
    } 

}
