<?php namespace Swapbot\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Input;
use Swapbot\Models\Image;
use Swapbot\Repositories\BotRepository;
use Swapbot\Repositories\ImageRepository;

class UploadTestController extends Controller {



	public function show()
	{
		$image = Image::first();
		return view('uploadshow', ['image' => $image]);
	}

	public function post(BotRepository $bot_repository, ImageRepository $image_repository)
	{
		$attributes = Input::all();

		$bot = $bot_repository->findByID(2);
		if (!$bot) { throw new Exception("Bot not found", 1); }


		$image = $image_repository->createForBot($bot, 1, $attributes['image']);

		return "image {$image['id']} created";
	}

	public function delete($id, ImageRepository $image_repository)
	{
		$image = $image_repository->findByID($id);
		if (!$image) { throw new Exception("Image not found", 1); }
		$image_repository->delete($image);
		return 'Deleted '.$id;
	}

}
