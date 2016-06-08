<?php

use Illuminate\Support\Facades\Log;
use \PHPUnit_Framework_Assert as PHPUnit;

class ScenariosTest extends TestCase {

    protected $use_database = true;


    public function testSingleScenario() {
        $scenario_number = getenv('SCENARIO');

        if ($scenario_number !== false) {
            $this->runScenario($scenario_number);
        }
    } 

    // -- BEGIN DYNAMICALLY GENERATED TESTS --
    public function testScenario_1() { $this->runScenario(1); } 
    public function testScenario_2() { $this->runScenario(2); } 
    public function testScenario_3() { $this->runScenario(3); } 
    public function testScenario_4() { $this->runScenario(4); } 
    public function testScenario_5() { $this->runScenario(5); } 
    public function testScenario_6() { $this->runScenario(6); } 
    public function testScenario_7() { $this->runScenario(7); } 
    public function testScenario_8() { $this->runScenario(8); } 
    public function testScenario_9() { $this->runScenario(9); } 
    public function testScenario_10() { $this->runScenario(10); } 
    public function testScenario_11() { $this->runScenario(11); } 
    public function testScenario_12() { $this->runScenario(12); } 
    public function testScenario_13() { $this->runScenario(13); } 
    public function testScenario_14() { $this->runScenario(14); } 
    public function testScenario_15() { $this->runScenario(15); } 
    public function testScenario_16() { $this->runScenario(16); } 
    public function testScenario_17() { $this->runScenario(17); } 
    public function testScenario_18() { $this->runScenario(18); } 
    public function testScenario_19() { $this->runScenario(19); } 
    public function testScenario_20() { $this->runScenario(20); } 
    public function testScenario_21() { $this->runScenario(21); } 
    public function testScenario_22() { $this->runScenario(22); } 
    public function testScenario_23() { $this->runScenario(23); } 
    public function testScenario_24() { $this->runScenario(24); } 
    public function testScenario_25() { $this->runScenario(25); } 
    public function testScenario_26() { $this->runScenario(26); } 
    public function testScenario_27() { $this->runScenario(27); } 
    public function testScenario_28() { $this->runScenario(28); } 
    public function testScenario_29() { $this->runScenario(29); } 
    public function testScenario_30() { $this->runScenario(30); } 
    public function testScenario_31() { $this->runScenario(31); } 
    public function testScenario_32() { $this->runScenario(32); } 
    public function testScenario_33() { $this->runScenario(33); } 
    public function testScenario_34() { $this->runScenario(34); } 
    public function testScenario_35() { $this->runScenario(35); } 
    public function testScenario_36() { $this->runScenario(36); } 
    public function testScenario_37() { $this->runScenario(37); } 
    public function testScenario_38() { $this->runScenario(38); } 
    public function testScenario_39() { $this->runScenario(39); } 
    public function testScenario_40() { $this->runScenario(40); } 
    public function testScenario_41() { $this->runScenario(41); } 
    public function testScenario_42() { $this->runScenario(42); } 
    public function testScenario_43() { $this->runScenario(43); } 
    public function testScenario_44() { $this->runScenario(44); } 
    public function testScenario_45() { $this->runScenario(45); } 
    public function testScenario_46() { $this->runScenario(46); } 
    public function testScenario_47() { $this->runScenario(47); } 
    public function testScenario_48() { $this->runScenario(48); } 
    public function testScenario_49() { $this->runScenario(49); } 
    public function testScenario_50() { $this->runScenario(50); } 
    public function testScenario_51() { $this->runScenario(51); } 
    public function testScenario_52() { $this->runScenario(52); } 
    public function testScenario_53() { $this->runScenario(53); } 
    public function testScenario_54() { $this->runScenario(54); } 
    public function testScenario_55() { $this->runScenario(55); } 
    public function testScenario_56() { $this->runScenario(56); } 
    public function testScenario_57() { $this->runScenario(57); } 
    public function testScenario_58() { $this->runScenario(58); } 
    public function testScenario_59() { $this->runScenario(59); } 
    public function testScenario_60() { $this->runScenario(60); } 
    public function testScenario_61() { $this->runScenario(61); } 
    public function testScenario_62() { $this->runScenario(62); } 
    public function testScenario_63() { $this->runScenario(63); } 
    public function testScenario_64() { $this->runScenario(64); } 
    public function testScenario_65() { $this->runScenario(65); } 
    public function testScenario_66() { $this->runScenario(66); } 
    public function testScenario_67() { $this->runScenario(67); } 
    public function testScenario_68() { $this->runScenario(68); } 
    public function testScenario_69() { $this->runScenario(69); } 
    public function testScenario_70() { $this->runScenario(70); } 
    public function testScenario_71() { $this->runScenario(71); } 
    public function testScenario_72() { $this->runScenario(72); } 
    public function testScenario_73() { $this->runScenario(73); } 
    // -- -END- DYNAMICALLY GENERATED TESTS --

    // ------------------------------------------------------------------------
    
    protected function runScenario($scenario_number) {
        $starting_scenario_number = getenv('SCENARIO');
        if ($starting_scenario_number AND $starting_scenario_number > $scenario_number) { return; }

        Log::debug("\n".str_repeat('-', 60)."\n-- RUN SCENARIO: $scenario_number\n".str_repeat('-', 60));

        $filename = "scenario".sprintf('%02d', $scenario_number).".yml";
        $scenario_runner = $this->getScenarioRunner();
        $scenario_data = $scenario_runner->loadScenario($filename);
        $scenario_runner->runScenario($scenario_data);
        $scenario_runner->validateScenario($scenario_data);
    }

    protected function getScenarioRunner() {
        if (!isset($this->scenario_runner)) {
            $this->scenario_runner = $this->app->make('\ScenarioRunner')->init($this);
        }
        return $this->scenario_runner;
    }


    protected function resetForScenario() {
        // reset the DB
        $this->teardownDb();
        $this->setUpDb();

        // reset the scenario runner
        $this->scenario_runner = null;
    }

    protected function drainQueue() {
        // make sure scenario runner exists
        $this->getScenarioRunner();
    }

}
