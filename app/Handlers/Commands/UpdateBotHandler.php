<?php namespace Swapbot\Handlers\Commands;

use Illuminate\Support\Facades\Log;
use Swapbot\Commands\UpdateBot;
use Swapbot\Http\Requests\Bot\Transformers\BotTransformer;
use Swapbot\Http\Requests\Bot\Validators\UpdateBotValidator;
use Swapbot\Repositories\BotRepository;
use Swapbot\Repositories\ImageRepository;

class UpdateBotHandler {

    /**
     * Create the command handler.
     *
     * @return void
     */
    public function __construct(UpdateBotValidator $validator, BotTransformer $transformer, BotRepository $repository, ImageRepository $image_repository)
    {
        $this->validator        = $validator;
        $this->transformer      = $transformer;
        $this->repository       = $repository;
        $this->image_repository = $image_repository;
    }

    /**
     * Handle the command.
     *
     * @param  UpdateBot  $command
     * @return void
     */
    public function handle(UpdateBot $command)
    {
        $bot         = $command->bot;
        $update_vars = $command->attributes;
        $user        = $command->user;

        // transform
        Log::debug("\$command->attributes=".json_encode($command->attributes, 192));
        $update_vars = $this->transformer->santizeAttributes($update_vars, $this->validator->getRules());
        Log::debug("\$update_vars=".json_encode($update_vars, 192));

        // validate
        $this->validator->validate($update_vars, $user);

        // find old images
        $old_bg_image_id   = $bot['background_image_id'];
        $old_logo_image_id = $bot['logo_image_id'];

        // if valid, update the bot
        $this->repository->update($bot, $update_vars);

        // delete the old images
        if ($old_bg_image_id != $bot['background_image_id']) {
            $old_image = $this->image_repository->findByID($old_bg_image_id);
            if ($old_image) { $this->image_repository->delete($old_image); }
        }
        if ($old_logo_image_id != $bot['logo_image_id']) {
            $old_image = $this->image_repository->findByID($old_logo_image_id);
            if ($old_image) { $this->image_repository->delete($old_image); }
        }

        // associate image ids with the user of the bot
        $logo_image = $bot->getLogoImage();
        if ($logo_image AND $logo_image['user_id'] != $bot['user_id']) {
            $this->image_repository->update($logo_image, ['user_id' => $bot['user_id']]);
        }
        $background_image = $bot->getBackgroundImage();
        if ($background_image AND $background_image['user_id'] != $bot['user_id']) {
            $this->image_repository->update($background_image, ['user_id' => $bot['user_id']]);
        }

        return null;
    }

}
