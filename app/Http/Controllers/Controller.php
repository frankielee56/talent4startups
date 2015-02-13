<?php namespace App\Http\Controllers;

use Auth, Twitter, FeedReader;
use Illuminate\Foundation\Bus\DispatchesCommands;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Config;

abstract class Controller extends BaseController {

	use DispatchesCommands, ValidatesRequests;

	function __construct()
	{
		$this->user = Auth::user();
		$this->twitterFeed = Twitter::getUserTimeline(['screen_name' => Config::get('feeds.twitterScreenName'), 'count' => 3, 'format' => 'object']);
		$this->twitterHomeFeed = Twitter::getHomeTimeline(['screen_name' => Config::get('feeds.twitterScreenName'), 'count' => 3, 'format' => 'object']);
		$this->t4sBlogFeed = FeedReader::read(Config::get('feeds.blogFeed'))->get_items();
		$this->facebookFeed = FeedReader::read(Config::get('feeds.facebookFeed'))->get_items();
		$this->displayAds = getenv('DISPLAY_ADS');

		view()->share('currentUser', $this->user);
		view()->share('twitterFeed', $this->twitterFeed);
		view()->share('twitterHomeFeed', $this->twitterHomeFeed);
		view()->share('t4sBlogFeed', $this->t4sBlogFeed);
		view()->share('facebookFeed', $this->facebookFeed);
		view()->share('displayAds', $this->displayAds);
	}
}
