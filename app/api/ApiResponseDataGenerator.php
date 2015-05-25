<?php namespace uk\co\la1tv\website\api;

use uk\co\la1tv\website\api\transformers\ShowTransformer;
use uk\co\la1tv\website\api\transformers\PlaylistTransformer;
use uk\co\la1tv\website\api\transformers\MediaItemTransformer;
use uk\co\la1tv\website\models\Show;
use uk\co\la1tv\website\models\Playlist;
use uk\co\la1tv\website\models\MediaItem;
use App;
use DebugHelpers;
use Exception;
use Config;

class ApiResponseDataGenerator {
	
	private $showTransformer = null;
	private $playlistTransformer = null;
	private $mediaItemTransformer = null;
	
	public function __construct() {
		$this->showTransformer = new ShowTransformer();
		$this->playlistTransformer = new PlaylistTransformer();
		$this->mediaItemTransformer = new MediaItemTransformer();
	}

	
	public function generateServiceResponseData() {
		$data = [
			"applicationVersion"	=> DebugHelpers::getVersion()
		];
		return new ApiResponseData($data);
	}
	
	public function generatePermissionsResponseData($hasVodUrisPermission, $hasStreamUrisPermission) {
		$data = [
			"vodUris"		=> $hasVodUrisPermission,
			"streamUris"	=> $hasStreamUrisPermission
		];
		return new ApiResponseData($data);
	}
	
	public function generateShowsResponseData() {
		$data = $this->showTransformer->transformCollection(Show::accessible()->orderBy("id")->get()->all());
		return new ApiResponseData($data);
	}
	
	public function generateShowResponseData($id) {
		$show = Show::with("playlists")->accessible()->find(intval($id));
		if (is_null($show)) {
			return $this->generateNotFound();
		}
		$data = [
			"show"		=> $this->showTransformer->transform($show, []),
			"playlists"	=> $this->playlistTransformer->transformCollection($show->playlists()->accessibleToPublic()->orderBy("id")->get()->all())
		];
		return new ApiResponseData($data);
	}
	
	public function generateShowPlaylistsResponseData($id) {
		$show = Show::with("playlists")->accessible()->find(intval($id));
		if (is_null($show)) {
			return $this->generateNotFound();
		}
		$data = $this->playlistTransformer->transformCollection($show->playlists()->accessibleToPublic()->orderBy("id")->get()->all());
		return new ApiResponseData($data);
	}
	
	public function generatePlaylistsResponseData() {
		$data = $this->playlistTransformer->transformCollection(Playlist::accessibleToPublic()->orderBy("id")->get()->all());
		return new ApiResponseData($data);
	}
	
	public function generatePlaylistResponseData($id, $showStreamUris, $showVodUris) {
		$playlist = Playlist::accessible()->find(intval($id));
		if (is_null($playlist)) {
			return $this->generateNotFound();
		}
		$playlist->load("mediaItems.liveStreamItem", "mediaItems.liveStreamItem.stateDefinition", "mediaItems.liveStreamItem.liveStream", "mediaItems.videoItem");
		$mediaItems = $playlist->mediaItems()->accessible()->orderBy("media_item_to_playlist.position")->get()->all();
		$data = [
			"playlist"		=> $this->playlistTransformer->transform($playlist, []),
			"mediaItems"	=> $this->mediaItemTransformer->transformCollection($this->createMediaItemsWithPlaylists($playlist, $mediaItems), $this->getMediaItemTransformerOptions($showStreamUris, $showVodUris))
		];
		return new ApiResponseData($data);
	}
	
	public function generatePlaylistMediaItemsResponseData($id, $showStreamUris, $showVodUris) {
		$playlist = Playlist::accessible()->find(intval($id));
		if (is_null($playlist)) {
			return $this->generateNotFound();
		}
		$playlist->load("mediaItems.liveStreamItem", "mediaItems.liveStreamItem.stateDefinition", "mediaItems.liveStreamItem.liveStream", "mediaItems.videoItem");
		$mediaItems = $playlist->mediaItems()->accessible()->orderBy("media_item_to_playlist.position")->get()->all();
		$data = $this->mediaItemTransformer->transformCollection($this->createMediaItemsWithPlaylists($playlist, $mediaItems), $this->getMediaItemTransformerOptions($showStreamUris, $showVodUris));
		return new ApiResponseData($data);
	}
	
	public function generatePlaylistMediaItemResponseData($playlistId, $mediaItemId, $showStreamUris, $showVodUris) {
		$playlist = Playlist::accessible()->find(intval($playlistId));
		if (is_null($playlist)) {
			return $this->generateNotFound();
		}
		
		$mediaItem = $playlist->mediaItems()->accessible()->find(intval($mediaItemId));
		if (is_null($mediaItem)) {
			return $this->generateNotFound();
		}
		$mediaItem->load("liveStreamItem", "liveStreamItem.stateDefinition", "liveStreamItem.liveStream", "videoItem");
		$data = $this->mediaItemTransformer->transform([$playlist, $mediaItem], $this->getMediaItemTransformerOptions($showStreamUris, $showVodUris));
		return new ApiResponseData($data);
	}
	
	// $limit is the maximum amount of items to be retrieved
	// $sortMode can be "VIEW_COUNT", "SCHEDULED_PUBLISH_TIME"
	// $sortDirection can be "ASC" or "DESC". Only "DESC" supported for "VIEW_COUNT"
	// $vodIncludeSetting can be "VOD_OPTIONAL", "HAS_VOD", "HAS_AVAILABLE_VOD"
	// $streamIncludeSetting can be "STREAM_OPTIONAL", "HAS_STREAM", "HAS_LIVE_STREAM"
	// the $vodIncludeSetting and $streamIncludeSetting are or'd together. E.g if HAS_VOD and HAS_LIVE_STREAM then
	// all items will have either vod, or a stream that's live, or both
	// TODO
	public function generateMediaItemsResponseData($limit, $sortMode, $sortDirection, $vodIncludeSetting, $streamIncludeSetting, $showStreamUris, $showVodUris) {
		$maxLimit = Config::get("api.mediaItemsMaxRetrieveLimit");
		if ($limit > $maxLimit) {
			$limit = $maxLimit;
		}
		
		if ($sortMode === "VIEW_COUNT") {
			// TODO
		}
		else if ($sortMode === "SCHEDULED_PUBLISH_TIME") {
			$mediaItems = MediaItem::with("liveStreamItem", "liveStreamItem.stateDefinition", "liveStreamItem.liveStream", "videoItem")->accessible()
			$mediaItems = $mediaItems->where(function($q) use (&$vodIncludeSetting, &$streamIncludeSetting) {
				if ($vodIncludeSetting === "VOD_OPTIONAL") {
					// intentional
				}
				else if ($vodIncludeSetting === "HAS_VOD") {
					$q->whereHas("videoItem", function($q2) {
						$q2->accessible();
					});
				}
				else if ($vodIncludeSetting === "HAS_AVAILABLE_VOD") {
					$q->whereHas("videoItem", function($q2) {
						$q2->live();
					});
				}
				else {
					throw(new Exception("Invalid vod include setting."));
				}
				
				if ($streamIncludeSetting === "STREAM_OPTIONAL") {
					// intentional
				}
				else if ($vodIncludeSetting === "HAS_STREAM") {
					$q->orWhereHas("liveStreamItem", function($q2) {
						$q2->accessible();
					});
				}
				else if ($vodIncludeSetting === "HAS_LIVE_STREAM") {
					$q->orWhereHas("liveStreamItem", function($q2) {
						$q2->isLive();
					});
				}
				else {
					throw(new Exception("Invalid stream include setting."));
				}
			});
			
			$sortAsc = null;
			if ($sortMode === "ASC") {
				$sortAsc = true;
			}
			else if ($sortMode === "DESC") {
				$sortAsc = false;
			}
			else {
				throw(new Exception("Invalid sort mode."));
			}
			$mediaItems = $mediaItems->orderBy("media_items.scheduled_publish_time", $sortAsc ? "asc" : "desc")->orderBy("id", "asc")->take($limit)->get();
		}
		else {
			throw(new Exception("Invalid sort mode."));
		}
		
		$data = [
			"mediaItems"	=> $this->mediaItemTransformer->transformCollection($mediaItems, $this->getMediaItemTransformerOptions($showStreamUris, $showVodUris))
		];
		return new ApiResponseData($data);
	}
	
	public function generateMediaItemResponseData($mediaItemId, $showStreamUris, $showVodUris) {
		$mediaItem = MediaItem::accessible()->find(intval($mediaItemId));
		if (is_null($mediaItem)) {
			return $this->generateNotFound();
		}
		$mediaItem->load("liveStreamItem", "liveStreamItem.stateDefinition", "liveStreamItem.liveStream", "videoItem");
		
		$playlists = $mediaItem->playlists()->orderBy("id", "asc")->get()->all();
		
		$data = [
			"mediaItem"	=> $this->mediaItemTransformer->transform([null, $mediaItem], $this->getMediaItemTransformerOptions($showStreamUris, $showVodUris)),
			"playlists"	=> $this->playlistTransformer->transformCollection($playlists)
		];
		return new ApiResponseData($data);
	}
	
	public function generateMediaItemPlaylistsResponseData($mediaItemId) {
		$mediaItem = MediaItem::accessible()->find(intval($mediaItemId));
		if (is_null($mediaItem)) {
			return $this->generateNotFound();
		}
		$playlists = $mediaItem->playlists()->orderBy("id", "asc")->get()->all();
		$data = $this->playlistTransformer->transformCollection($playlists);
		return new ApiResponseData($data);
	}
	
	private function generateNotFound() {
		return new ApiResponseData([], 404); 
	}
	
	private function createMediaItemsWithPlaylists($playlist, $mediaItems) {
		$mediaItemsWithPlaylists = [];
		foreach($mediaItems as $mediaItem) {
			$mediaItemsWithPlaylists[] = [$playlist, $mediaItem];
		}
		return $mediaItemsWithPlaylists;
	}
	
	private function getMediaItemTransformerOptions($showStreamUris, $showVodUris) {
		return [
			"showStreamUris"	=> $showStreamUris,
			"showVodUris"		=> $showVodUris
		];
	}
}