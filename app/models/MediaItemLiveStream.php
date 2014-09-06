<?php namespace uk\co\la1tv\website\models;

use Session;
use Carbon;
use Config;

class MediaItemLiveStream extends MyEloquent {

	protected $table = 'media_items_live_stream';
	protected $fillable = array('enabled', 'state_id', 'information_msg', 'being_recorded');
	
	public function mediaItem() {
		return $this->belongsTo(self::$p.'MediaItem', 'media_item_id');
	}
	
	public function liveStream() {
		return $this->belongsTo(self::$p.'LiveStream', 'live_stream_id');
	}
	
	public function stateDefinition() {
		return $this->belongsTo(self::$p.'LiveStreamStateDefinition', 'state_id');
	}
	
	// if the state is set to "live" but there is no live stream attached to this then the resolved version is "Not Live"
	public function getResolvedStateDefinition() {
		$stateDefinition = $this->stateDefinition;
		if (intval($stateDefinition->id) === 2 && is_null($this->liveStream)) {
			// set to "live" but no live stream attached. Pretend "Not Live"
			return LiveStreamStateDefinition::find(1);
		}
		return $stateDefinition;
	}
	
	public function registerViewCount() {	
		if (!$this->getIsAccessible() || intval($this->getResolvedStateDefinition()->id) !== 2) {
			// shouldn't be accessible or stream not live
			return;
		}
	
		$sessionKey = "viewCount-".$this->id;
		$lastTimeRegistered = Session::get($sessionKey, null);
		if (!is_null($lastTimeRegistered) && $lastTimeRegistered >= Carbon::now()->subMinutes(Config::get("custom.interval_between_registering_view_counts"))->timestamp) {
			// already registered view not that long ago.
			return;
		}
		$this->increment("view_count");
		Session::set($sessionKey, Carbon::now()->timestamp);
	}
	
	// returns true if this should be shown with the parent media item. If false then it should like the MediaItem does not have a live stream component.
	// this can still return true even if there is no LiveStream model associated with this.
	// getResolvedStateDefinition() should be used to determine the state of the actual stream.
	public function getIsAccessible() {
		return $this->enabled && $this->mediaItem->getIsAccessible();
	}
	
	public function scopeAccessible($q) {
		return $q->where("enabled", true)->whereHas("mediaItem", function($q2) {
			$q2->accessible();
		});
	}
	
	public function scopeSearch($q, $value) {
		return $value === "" ? $q : $q->whereContains(array("name", "description"), $value);
	}
}