<?php

namespace Swapbot\Statemachines;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Metabor\Statemachine\Process;
use Metabor\Statemachine\Statemachine;
use Metabor\Statemachine\Transition;

/*
* StateMachineFactory
*/
abstract class StateMachineFactory {


    public function buildStateMachineFromModel(Model $model) {
        // build a statemachine
        $process_name = (new \ReflectionClass($model))->getShortName()." Process";
        $state_machine = new Statemachine($model, $this->buildStateMachineProcess($model['state'], $process_name));
        return $state_machine;
    }

    public function buildStateMachineProcess($initial_state_name, $process_name) {
        // build the statues
        $states = $this->buildStates();

        // build transitions
        $this->addTransitionsToStates($states);

        // get the initial state
        if (!isset($states[$initial_state_name])) { throw new Exception("No such state: $initial_state_name", 1); }

        // build a process that handles transitions
        $process = new Process($process_name, $states[$initial_state_name]);

        return $process;
    }

    abstract public function buildStates();

    abstract public function addTransitionsToStates($states);


    protected function addTransitionToStates($states, $starting_state, $ending_state, $state_event, $transition_command) {
        $states[$starting_state]->addTransition(new Transition($states[$ending_state], $state_event));
        $states[$starting_state]->getEvent($state_event)->attach($transition_command);
    }

}
