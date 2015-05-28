<?php

namespace Swapbot\Handlers\Monitoring;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tokenly\ConsulHealthDaemon\ServicesChecker;

/**
 * This is invoked when a new block is received
 */
class MonitoringHandler {

    public function __construct(ServicesChecker $services_checker) {
        $this->services_checker = $services_checker;
    }

    public function handleConsoleHealthCheck() {
        if (env('PROCESS_NAME', 'swapbot') == 'swapbotdaemon') {
            $this->handleConsoleHealthCheckForSwapbotDaemon();
        } else {
            $this->handleConsoleHealthCheckForSwapbot();
        }
    }

    public function handleConsoleHealthCheckForSwapbotDaemon() {
        // check all queues
        $this->services_checker->checkQueueSizes([
            'email' => 10,
        ]);

        // check MySQL
        $this->services_checker->checkMySQLConnection();

        // check pusher
        $this->services_checker->checkPusherConnection();
    }

    public function handleConsoleHealthCheckForSwapbot() {
        // check queue
        $this->services_checker->checkQueueConnection();

        // check MySQL
        $this->services_checker->checkMySQLConnection();

        // check pusher
        $this->services_checker->checkPusherConnection();
    }

    public function subscribe($events) {
        $events->listen('consul-health.console.check', 'Swapbot\Handlers\Monitoring\MonitoringHandler@handleConsoleHealthCheck');
    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Checks
    
}
