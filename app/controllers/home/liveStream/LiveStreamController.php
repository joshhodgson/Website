<?php namespace uk\co\la1tv\website\controllers\home\liveStream;

use uk\co\la1tv\website\controllers\home\HomeBaseController;
use uk\co\la1tv\website\models\LiveStream;
use App;
use View;
use Response;
use Config;
use URLHelpers;
use Auth;
use Exception;
use Carbon;

class LiveStreamController extends HomeBaseController {

	public function getIndex($id=null) {
		if (is_null($id)) {
			App::abort(404);
		}
		
		$liveStream = LiveStream::showAsLiveStream()->find($id);
		if (is_null($liveStream)) {
			App::abort(404);
		}

		$coverArtResolutions = Config::get("imageResolutions.coverArt");

		$twitterProperties = array();
		$twitterProperties[] = array("name"=> "card", "content"=> "player");
		$openGraphProperties = array();
		$openGraphProperties[] = array("name"=> "og:type", "content"=> "video.other");

		$twitterProperties[] = array("name"=> "player", "content"=> $liveStream->getEmbedUri()."?autoPlayVod=0&autoPlayStream=0&flush=1&disableFullScreen=1&disableRedirect=1");
		$twitterProperties[] = array("name"=> "player:width", "content"=> "1280");
		$twitterProperties[] = array("name"=> "player:height", "content"=> "720");
		
		
		if (!is_null($liveStream->description)) {
			$openGraphProperties[] = array("name"=> "og:description", "content"=> $liveStream->description);
			$twitterProperties[] = array("name"=> "description", "content"=> str_limit($liveStream->description, 197, "..."));
		}
		$openGraphProperties[] = array("name"=> "og:title", "content"=> $liveStream->name);
		$twitterProperties[] = array("name"=> "title", "content"=> $liveStream->name);
		$openGraphCoverArtUri = $liveStream->getCoverArtUri($coverArtResolutions['fbOpenGraph']['w'], $coverArtResolutions['fbOpenGraph']['h']);
		$twitterCardCoverArtUri = $liveStream->getCoverArtUri($coverArtResolutions['twitterCard']['w'], $coverArtResolutions['twitterCard']['h']);
		$openGraphProperties[] = array("name"=> "og:image", "content"=> $openGraphCoverArtUri);
		$twitterProperties[] = array("name"=> "image", "content"=> $twitterCardCoverArtUri);
		
		$view = View::make("home.liveStream.index");
		$view->title = $liveStream->name;
		$view->descriptionEscaped = !is_null($liveStream->description) ? nl2br(URLHelpers::escapeAndReplaceUrls($liveStream->description)) : null;
		$view->playerInfoUri = $this->getInfoUri($liveStream->id);
		$view->registerWatchingUri = $this->getRegisterWatchingUri($liveStream->id);
		$view->scheduleUri = $this->getScheduleUri($liveStream->id);
		$view->loginRequiredMsg = "Please log in to use this feature.";
		$this->setContent($view, "live-stream", "live-stream", $openGraphProperties, $liveStream->name, 200, $twitterProperties);
	}
	
	public function postPlayerInfo($id) {
		
		$liveStream = LiveStream::showAsLiveStream()->find($id);
		if (is_null($liveStream)) {
			App::abort(404);
		}
		$liveStream->load("watchingNows");

		// true if a user is logged into the cms and has permission to view live streams.
		$userHasLiveStreamsPermission = false;
		if (Auth::isLoggedIn()) {
			$userHasLiveStreamsPermission = Auth::getUser()->hasPermission(Config::get("permissions.liveStreams"), 0);
		}

		$streamAccessible = $liveStream->getIsAccessible();

		$coverArtResolutions = Config::get("imageResolutions.coverArt");
		$coverArtUri = Config::get("custom.default_cover_uri");
		if (!Config::get("degradedService.enabled")) {
			$coverArtUri = $liveStream->getCoverArtUri($coverArtResolutions['full']['w'], $coverArtResolutions['full']['h']);
		}

		$id = intval($liveStream->id);
		$uri = $liveStream->getUri();
		$title = $liveStream->name;
		$embedData = $liveStream->getEmbedData();
		$streamState = $streamAccessible ? 2 : 1;
		$minNumWatchingNow = Config::get("custom.min_num_watching_now");
		$numWatchingNow = $liveStream->getNumWatchingNow();
		if (!$userHasLiveStreamsPermission && $numWatchingNow < $minNumWatchingNow) {
			$numWatchingNow = null;
		}
		$streamUris = array();

		if ($streamAccessible) {
			foreach($liveStream->getQualitiesWithUris(array("nativeDvr", "live")) as $qualityWithUris) {
				$streamUris[] = array(
					"quality"	=> array(
						"id"	=> intval($qualityWithUris['qualityDefinition']->id),
						"name"	=> $qualityWithUris['qualityDefinition']->name
					),
					"uris"		=> $qualityWithUris['uris']
				);
			}
		}

		$data = array(
			"id"						=> $id,
			"title"						=> $title, // shown on embeddable player
			"uri"						=> $uri, // used for embeddable player so title can be clickable
			"coverUri"					=> $coverArtUri,
			"embedData"					=> $embedData,
			"hasStream"					=> true,
			"streamState"				=> $streamState, // 1=not live, 2=live (3=show over, null=no livestream)
			"streamUris"				=> $streamUris, // if null this means stream is not live
			"numWatchingNow"			=> $numWatchingNow
		);

		return Response::json($data);
	}

	public function postScheduleInfo($id) {
		
		$liveStream = LiveStream::showAsLiveStream()->find($id);
		if (is_null($liveStream)) {
			App::abort(404);
		}

		$comingUpMediaItem = $liveStream->getComingUpMediaItem();
		// there may be more than one media item live stream which is live at the same time
		// this will just pick the one scheduled later which should be consistant
		// if there is ever more than one media item live at once it would probably only be
		// for a short period of time anyway during a switch over
		$liveMediaItem = $liveStream->getLiveMediaItem();
		$previouslyLiveMediaItem = $liveStream->getPreviouslyLiveMediaItem();
		
		$comingUp = !is_null($comingUpMediaItem) ? $this->getMediaItemArray($comingUpMediaItem) : null;
		$live = !is_null($liveMediaItem) ? $this->getMediaItemArray($liveMediaItem) : null;
		$previouslyLive = !is_null($previouslyLiveMediaItem) ? $this->getMediaItemArray($previouslyLiveMediaItem) : null;

		$data = array(
			"previouslyLive"	=> $previouslyLive,
			"live"				=> $live,
			"comingUp"			=> $comingUp
		);

		return Response::json($data);
	}

	private function getMediaItemArray($mediaItem) {
		$playlist = $mediaItem->getDefaultPlaylist();
		if (is_null($playlist)) {
			throw(new Exception("MediaItem not in an accessible playlist."));
		}
		$uri = $playlist->getMediaItemUri($mediaItem);
		$coverArtResolutions = Config::get("imageResolutions.coverArt");
		$coverArtUri = Config::get("custom.default_cover_uri");
		if (!Config::get("degradedService.enabled")) {
			$coverArtUri = $playlist->getMediaItemCoverArtUri($mediaItem, $coverArtResolutions['thumbnail']['w'], $coverArtResolutions['thumbnail']['h']);
		}
		$seriesName = !is_null($playlist->show) ? $playlist->generateName() : null;
		$name = $playlist->generateEpisodeTitle($mediaItem);
		return array(
			"id"			=> intval($mediaItem->id),
			"uri"			=> $uri,
			"coverArtUri"	=> $coverArtUri,
			"scheduledPublishTime"	=> $mediaItem->scheduled_publish_time->timestamp,
			"seriesName"	=> $seriesName,
			"name"			=> $name
		);
	}

	public function postRegisterWatching($liveStreamId) {
		$liveStream = LiveStream::showAsLiveStream()->find($liveStreamId);
		if (is_null($liveStream)) {
			App::abort(404);
		}

		$success = false;
		if (isset($_POST['playing'])) {
			$playing = $_POST['playing'] === "1";
			$success = $liveStream->registerWatching($playing);
		}
		return Response::json(array("success"=>$success));
	}

	private function getInfoUri($liveStreamId) {
		return Config::get("custom.live_stream_player_info_base_uri")."/".$liveStreamId;
	}
	
	private function getRegisterWatchingUri($liveStreamId) {
		return Config::get("custom.live_stream_player_register_watching_base_uri")."/".$liveStreamId;
	}

	private function getScheduleUri($liveStreamId) {
		return Config::get("custom.live_stream_player_schedule_base_uri")."/".$liveStreamId;
	}

	public function missingMethod($parameters=array()) {
		// redirect /[integer]/[anything] to /index/[integer]/[anything]
		if (count($parameters) >= 1 && ctype_digit($parameters[0])) {
			return call_user_func_array(array($this, "getIndex"), $parameters);
		}
		else {
			return parent::missingMethod($parameters);
		}
	}
}
