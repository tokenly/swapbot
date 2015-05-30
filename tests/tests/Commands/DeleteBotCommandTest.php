<?php

use Illuminate\Foundation\Bus\DispatchesCommands;
use Swapbot\Commands\DeleteBot;
use \PHPUnit_Framework_Assert as PHPUnit;

class DeleteBotCommandTest extends TestCase {

    use DispatchesCommands;

    protected $use_database = true;

    public function testDeleteBotCommand()
    {
        // make a bot
        $bot = app('BotHelper')->newSampleBot();
        $bot_id = $bot['id'];

        // delete
        $this->dispatch(new DeleteBot($bot));

        // confirm it is deleted
        $bot_repository = app('Swapbot\Repositories\BotRepository');
        $bot = $bot_repository->findByID($bot_id);
        PHPUnit::assertEmpty($bot);
    }


    public function testDeleteBotWithOtherTablesCommand()
    {
        $bot_repository = app('Swapbot\Repositories\BotRepository');

        app('ScenarioRunner')->init(null)->runScenarioByNumber(44);

        // load the bot
        $bots = $bot_repository->findAll();
        PHPUnit::assertCount(1, $bots);
        $bot = $bots[0];
        $bot_id = $bot['id'];

        // delete
        $this->dispatch(new DeleteBot($bot));

        // confirm it is deleted
        $bot = $bot_repository->findByID($bot_id);
        PHPUnit::assertEmpty($bot);
    }


}
