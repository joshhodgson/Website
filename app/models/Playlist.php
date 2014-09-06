<?php namespace uk\co\la1tv\website\models;

use Exception;
use FormHelpers;
use Carbon;
use Config;
use Cache;

class Playlist extends MyEloquent {

	protected $table = 'playlists';
	protected $fillable = array('name', 'enabled', 'scheduled_publish_time', 'description', 'series_no');
	protected $appends = array("scheduled_publish_time_for_input", "playlist_content_for_orderable_list", "playlist_content_for_input");
	
	protected static function boot() {
		parent::boot();
		self::saving(function($model) {
			if ($model->enabled && is_null($model->scheduled_publish_time)) {
				throw(new Exception("A Playlist which is enabled must have a scheduled publish time."));
			}
			else if ($model->show_id === NULL) {
				if ($model->name === NULL) {
					throw(new Exception("A name must be specified."));
				}
				else if ($model->series_no !== NULL) {
					throw(new Exception("A standard playlist cannot have a series number."));
				}
			}
			else {
				if ($model->series_no === NULL) {
					throw(new Exception("Series number required."));
				}
			}
			return true;
		});
	}
	
	public function show() {
		return $this->belongsTo(self::$p.'Show', 'show_id');
	}
	
	public function sideBannerFile() {
		return $this->belongsTo(self::$p.'File', 'side_banner_file_id');
	}
	
	public function coverFile() {
		return $this->belongsTo(self::$p.'File', 'cover_file_id');
	}
	
	public function coverArtFile() {
		return $this->belongsTo(self::$p.'File', 'cover_art_file_id');
	}
	
	public function mediaItems() {
		return $this->belongsToMany(self::$p.'MediaItem', 'media_item_to_playlist', 'playlist_id', 'media_item_id')->withPivot('position', 'from_playlist_id');
	}
	
	public function relatedItems() {
		return $this->belongsToMany(self::$p.'MediaItem', 'related_item_to_playlist', 'media_item_id', 'related_media_item_id')->withPivot('position');
	}
	
	public function itemsRelatedTo() {
		return $this->belongsToMany(self::$p.'MediaItem', 'related_item_to_playlist', 'related_media_item_id', 'media_item_id')->withPivot('position');
	}
	
	public function getScheduledPublishTimeForInputAttribute() {
		if (is_null($this->scheduled_publish_time)) {
			return null;
		}
		return FormHelpers::formatDateForInput($this->scheduled_publish_time->timestamp);
	}
	
	// returns the name from the playlist if set, otherwise the name of the show if it's linked to a show.
	// shouldn't be a case where it's not set on playlist and not part of show
	public function generateName() {
		if (!is_null($this->name)) {
			return $this->name;
		}
		return $this->show->name;
	}
	
	private function getPlaylistContentIdsForOrderableList() {
		$ids = array();
		$items = $this->mediaItems()->orderBy("media_item_to_playlist.position", "asc")->get();
		foreach($items as $a) {
			$ids[] = intval($a->id);
		}
		return $ids;
	}
	
	// returns the media items name with an episode number if this playlist is a series in a show
	public function generateEpisodeTitle($mediaItemParam) {
		$mediaItem = $this->mediaItems->find($mediaItemParam->id);
		if (is_null($mediaItem)) {
			throw(new Exception("Playlist does not contain MediaItem."));
		}
		
		if (is_null($this->show)) {
			return $mediaItem->name;
		}
		
		return ($mediaItem->pivot->position + 1) . ". " . $mediaItem->name;
	}
	
	public function getPlaylistContentForInputAttribute() {
		return MediaItem::generateInputValueForAjaxSelectOrderableList($this->getPlaylistContentIdsForOrderableList());
	}
	
	public function getPlaylistContentForOrderableListAttribute() {
		return MediaItem::generateInitialDataForAjaxSelectOrderableList($this->getPlaylistContentIdsForOrderableList());
	}
	
	private function getRelatedItemsIdsForOrderableList() {
		$ids = array();
		$items = $this->relatedItems()->orderBy("related_item_to_playlist.position", "asc")->get();
		foreach($items as $a) {
			$ids[] = intval($a->id);
		}
		return $ids;
	}
	
	public function getRelatedItemsForInputAttribute() {
		return MediaItem::generateInputValueForAjaxSelectOrderableList($this->getRelatedItemsIdsForOrderableList());
	}
	
	public function getRelatedItemsForOrderableListAttribute() {
		return MediaItem::generateInitialDataForAjaxSelectOrderableList($this->getRelatedItemsIdsForOrderableList());
	}
	
	public function getDates() {
		return array_merge(parent::getDates(), array('scheduled_publish_time'));
	}
	
	public static function getCachedActivePlaylists($includingShows) {
		if ($includingShows) {
			return Cache::remember('activePlaylists', Config::get("custom.cache_time"), function() {
				return self::active()->orderBy("name", "asc")->get();
			});
		}
		else {
			return Cache::remember('activePlaylistsExcludingShows', Config::get("custom.cache_time"), function() {
				return self::belongsToShow(false)->active()->orderBy("name", "asc")->get();
			});
		}
	}
	
	// A playlist is active when:
	//						it's scheduled publish time is not too old (configured in config), or one of the following is true
	//						it contains an active media item.
	public function scopeActive($q) {
		$startTime = Carbon::now()->subDays(Config::get("custom.num_days_active"));
		return $q->accessible()->where(function($q2) use (&$startTime) {
			$q2->where("scheduled_publish_time", ">=", $startTime)
			->orWhereHas("mediaItems", function($q3) {
				$q3->accessible()->active();
			});
		});
	}
	
	public function scopeBelongsToShow($q, $yes=true) {
		return $q->has("show", $yes ? "!=" : "=", 0);
	}
	
	// get the uri that should be used as the media items cover art.
	// if the media item has one it returns that, otherwise it returns the playlist one if it has one
	// if there isn't one it returns the uri to the default cover
	public function getMediaItemCoverArtUri($mediaItemParam, $width, $height) {
		$mediaItem = $this->mediaItems()->find($mediaItemParam->id);
		if (is_null($mediaItem)) {
			throw(new Exception("The media item is not part of the playlist."));
		}
		
		// check on media item
		$coverArtFile = $mediaItem->coverArtFile;
		if (!is_null($coverArtFile)) {
			$coverArtImageFile = $coverArtFile->getImageFileWithResolution($width, $height);
			if (!is_null($coverArtFile) && $coverArtFile->getShouldBeAccessible()) {
				return $coverArtImageFile->getUri();
			}
		}
		
		// check on playlist
		$coverArtFile = $this->coverArtFile;
		if (!is_null($coverArtFile)) {
			$coverArtImageFile = $coverArtFile->getImageFileWithResolution($width, $height);
			if (!is_null($coverArtFile) && $coverArtFile->getShouldBeAccessible()) {
				return $coverArtImageFile->getUri();
			}
		}
		
		// return default cover
		return Config::get("custom.default_cover_uri");
	}
	
	// get the uri that should be used for the media item side banners.
	// if the media item has one it returns that, otherwise it returns the playlist one if it has one
	// if there isn't one it returns null
	public function getMediaItemSideBannerUri($mediaItemParam, $width, $height) {
		$mediaItem = $this->mediaItems()->find($mediaItemParam->id);
		if (is_null($mediaItem)) {
			throw(new Exception("The media item is not part of the playlist."));
		}
		
		// check on media item
		$sideBannerFile = $mediaItem->sideBannerFile;
		if (!is_null($sideBannerFile)) {
			$sideBannerImageFile = $sideBannerFile->getImageFileWithResolution($width, $height);
			if (!is_null($sideBannerFile) && $sideBannerFile->getShouldBeAccessible()) {
				return $sideBannerFile->getUri();
			}
		}
		
		// check on playlist
		$sideBannerFile = $this->sideBannerFile;
		if (!is_null($sideBannerFile)) {
			$sideBannerImageFile = $sideBannerFile->getImageFileWithResolution($width, $height);
			if (!is_null($sideBannerFile) && $sideBannerFile->getShouldBeAccessible()) {
				return $sideBannerFile->getUri();
			}
		}
		return null;
	}
	
	// get the uri that should be used for the media item cover.
	// if the media item has one it returns that, otherwise it returns the playlist one if it has one
	// if there isn't one it returns null
	public function getMediaItemCoverUri($mediaItemParam, $width, $height) {
		$mediaItem = $this->mediaItems()->find($mediaItemParam->id);
		if (is_null($mediaItem)) {
			throw(new Exception("The media item is not part of the playlist."));
		}
		
		// check on media item
		$coverFile = $mediaItem->coverFile;
		if (!is_null($coverFile)) {
			$coverFileImageFile = $coverFile->getImageFileWithResolution($width, $height);
			if (!is_null($coverFileImageFile) && $coverFileImageFile->getShouldBeAccessible()) {
				return $coverFileImageFile->getUri();
			}
		}
		
		// check on playlist
		$coverFile = $this->coverFile;
		if (!is_null($coverFile)) {
			$coverFileImageFile = $coverFile->getImageFileWithResolution($width, $height);
			if (!is_null($coverFileImageFile) && $coverFileImageFile->getShouldBeAccessible()) {
				return $coverFileImageFile->getUri();
			}
		}
		return null;
	}
	
	// returns true if this playlist should be accessible now.
	// see getIsAccessibleToPublic() to determine if the public should have access
	public function getIsAccessible() {
		if (!$this->enabled) {
			return false;
		}
		if (!is_null($this->show)) {
			if (!$this->show->enabled) {
				return false;
			}
		}
		
		$sideBannerFile = $this->sideBannerFile;
		if (!is_null($sideBannerFile) && !$sideBannerFile->getFinishedProcessing()) {
			return false;
		}
		$coverFile = $this->coverFile;
		if (!is_null($coverFile) && !$coverFile->getFinishedProcessing()) {
			return false;
		}
		$coverArtFile = $this->coverArtFile;
		if (!is_null($coverArtFile) && !$coverArtFile->getFinishedProcessing()) {
			return false;
		}
		
		return true;
	}
	
	public function scopeAccessible($q) {

		return $q->where("enabled", true)->where(function($q2) {
			$q2->has("show", "=", 0)
			->orWhereHas("show", function($q3) {
				$q3->accessible();
			});
		})->where(function($q2) {
			$q2->has("sideBannerFile", "=", 0)
			->orWhereHas("sideBannerFile", function($q3) {
				$q3->finishedProcessing();
			});
		})->where(function($q2) {
			$q2->has("coverFile", "=", 0)
			->orWhereHas("coverFile", function($q3) {
				$q3->finishedProcessing();
			});
		})->where(function($q2) {
			$q2->has("coverArtFile", "=", 0)
			->orWhereHas("coverArtFile", function($q3) {
				$q3->finishedProcessing();
			});
		});
	}
	
	// returns true if this playlist should be accessible to the public.
	public function getIsAccessibleToPublic() {
	
		if (!$this->getIsAccessible()) {
			return false;
		}
		$scheduledPublishTime = $this->scheduled_publish_time;
		return is_null($scheduledPublishTime) || $scheduledPublishTime->isPast();
	}
	
	public function scopeAccessibleToPublic($q) {
		return $q->accessible()->where("scheduled_publish_time", "<", Carbon::now());
	}
	
	public function scopeSearch($q, $value) {
		return $value === "" ? $q : $q->whereContains(array("name", "description"), $value);
	}
}