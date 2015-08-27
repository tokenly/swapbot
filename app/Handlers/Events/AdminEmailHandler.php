<?php

namespace Swapbot\Handlers\Events;

use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Swapbot\Commands\SendEmail;
use Swapbot\Events\BotFuelWasExhausted;
use Swapbot\Events\BotPaymentStateBecameNotice;
use Swapbot\Events\BotPaymentStateBecamePastDue;
use Swapbot\Events\BotPaymentStateBecameSoon;
use Swapbot\Events\BotPaymentStateBecameUrgent;
use Swapbot\Events\Event;
use Swapbot\Models\Formatting\FormattingHelper;


class AdminEmailHandler {

    use DispatchesCommands;

    function __construct(FormattingHelper $formatting_helper) {
        $this->formatting_helper      = $formatting_helper;
    }


    public function fuelWasExhausted(BotFuelWasExhausted $event) {
        $bot  = $event->bot;
        $user = $bot->user;

        if (!$user['email'] OR !$user->getEmailPreference('adminEvents')) { return; }

        // build variables
        $email_vars = $this->buildEmailVariables($bot, $user);

        // send an email
        $send_email = new SendEmail('emails.admin-notifications.fuel-exhausted', $email_vars, "Your Swapbot is Low on Fuel", $user['email'], $user['name']);
        $this->dispatch($send_email);

    }

    public function botPaymentStateBecameNotice(BotPaymentStateBecameNotice $event) {
        $bot  = $event->bot;
        $user = $bot->user;

        if (!$user['email'] OR !$user->getEmailPreference('adminEvents')) { return; }

        // build variables
        $email_vars = $this->buildEmailVariables($bot, $user);

        // send an email
        $send_email = new SendEmail('emails.admin-notifications.notice', $email_vars, "Your Swapbot Expires in a Couple Weeks", $user['email'], $user['name']);
        $this->dispatch($send_email);

    }
    public function botPaymentStateBecameSoon(BotPaymentStateBecameSoon $event) {
        $bot  = $event->bot;
        $user = $bot->user;

        if (!$user['email'] OR !$user->getEmailPreference('adminEvents')) { return; }

        // build variables
        $email_vars = $this->buildEmailVariables($bot, $user);

        // send an email
        $send_email = new SendEmail('emails.admin-notifications.soon', $email_vars, "Your Swapbot is Expiring Soon", $user['email'], $user['name']);
        $this->dispatch($send_email);

    }
    public function botPaymentStateBecameUrgent(BotPaymentStateBecameUrgent $event) {
        $bot  = $event->bot;
        $user = $bot->user;

        if (!$user['email'] OR !$user->getEmailPreference('adminEvents')) { return; }

        // build variables
        $email_vars = $this->buildEmailVariables($bot, $user);

        // send an email
        $send_email = new SendEmail('emails.admin-notifications.urgent', $email_vars, "Your Swapbot will Expire Within A Day", $user['email'], $user['name']);
        $this->dispatch($send_email);

    }
    public function botPaymentStateBecamePastDue(BotPaymentStateBecamePastDue $event) {
        $bot  = $event->bot;
        $user = $bot->user;

        if (!$user['email'] OR !$user->getEmailPreference('adminEvents')) { return; }

        // build variables
        $email_vars = $this->buildEmailVariables($bot, $user);

        // send an email
        $send_email = new SendEmail('emails.admin-notifications.past-due', $email_vars, "Your Swapbot Has Expired", $user['email'], $user['name']);
        $this->dispatch($send_email);

    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     * @return array
     */
    public function subscribe($events) {
        $events->listen('Swapbot\Events\BotFuelWasExhausted',          'Swapbot\Handlers\Events\AdminEmailHandler@fuelWasExhausted');
        $events->listen('Swapbot\Events\BotPaymentStateBecameNotice',  'Swapbot\Handlers\Events\AdminEmailHandler@botPaymentStateBecameNotice');
        $events->listen('Swapbot\Events\BotPaymentStateBecameSoon',    'Swapbot\Handlers\Events\AdminEmailHandler@botPaymentStateBecameSoon');
        $events->listen('Swapbot\Events\BotPaymentStateBecameUrgent',  'Swapbot\Handlers\Events\AdminEmailHandler@botPaymentStateBecameUrgent');
        $events->listen('Swapbot\Events\BotPaymentStateBecamePastDue', 'Swapbot\Handlers\Events\AdminEmailHandler@botPaymentStateBecamePastDue');
    }

    protected function buildEmailVariables($bot, $user) {
        $host = Config::get('swapbot.site_host');
        $admin_url = "$host/admin/view/bot/{$bot['uuid']}";
        $update_profile_link = "$host/account/emails";
        $bot_url = $bot->getPublicBotURL();
        $bot_link = '<a href="'.$bot_url.'">'.$bot['name'].'</a>';

        $email_vars = [
            'bot'                  => $bot->serializeForAPI(),
            'user'                 => $user->serializeForAPI(),
            'host'                 => $host,
            'updateEmailPrefsLink' => $update_profile_link,
            'robohashUrl'          => $bot->getRobohashURL(),
            'adminUrl'             => $admin_url,
            'botUrl'               => $bot_url,
            'botLink'              => $bot_link,
            'botBlacklist'         => $this->formatting_helper->implodeWithConjunction($bot['blacklist_addresses'], 'or'),
        ];
        return $email_vars;
     

    }

}
