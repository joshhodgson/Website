<?php namespace uk\co\la1tv\website\models;

use uk\co\la1tv\website\helpers\reorderableList\AjaxSelectReorderableList;

class MediaItem extends MyEloquent {
	
	protected $table = 'media_items';
	protected $fillable = array('name', 'description', 'enabled');
	protected $appends = array("related_items_for_orderable_select", "related_items_for_input");

	public function comments() {
		return $this->hasMany(self::$p.'MediaItemComment', 'media_item_id');
	}

	public function likes() {
		return $this->hasMany(self::$p.'MediaItemLike', 'media_item_id');
	}
	
	public function liveStreamItem() {
		return $this->hasOne(self::$p.'MediaItemLiveStream', 'media_item_id');
	}
	
	public function videoItem() {
		return $this->hasOne(self::$p.'MediaItemVideo', 'media_item_id');
	}
	
	public function sideBannerFile() {
		return $this->belongsTo(self::$p.'File', 'side_banner_file_id');
	}
	
	public function coverFile() {
		return $this->belongsTo(self::$p.'File', 'cover_file_id');
	}
	
	public function playlists() {
		return $this->belongsToMany(self::$p.'Playlist', 'media_item_to_playlist', 'media_item_id', 'playlist_id')->withPivot('position', 'from_playlist_id');
	}
	
	public function relatedItems() {
		return $this->belongsToMany(self::$p.'MediaItem', 'related_item_to_media_item', 'media_item_id', 'related_media_item_id')->withPivot('position');
	}
	
	public function itemsRelatedTo() {
		return $this->belongsToMany(self::$p.'MediaItem', 'related_item_to_media_item', 'related_media_item_id', 'media_item_id')->withPivot('position');
	}
	
	public function getRelatedItemIdsForReorderableList() {
		$ids = array();
		$items = $this->relatedItems()->orderBy("related_item_to_media_item.position", "asc")->get();
		foreach($items as $a) {
			$ids[] = intval($a->id);
		}
		return $ids;
	}
	
	public function getRelatedItemsForOrderableListAttribute() {
		$reorderableList = new AjaxSelectReorderableList($this->getRelatedItemIdsForReorderableList(), function() {
			return new MediaItem();
		}, function($model) {
			return $model->name;
		});
		return $reorderableList->getStringForReorderableList();
	}
	
	public static function isValidRelatedItemsFromInput($stringFromInput) {
		$reorderableList = new AjaxSelectReorderableList(json_decode($stringFromInput, true), function() {
			return new MediaItem();
		}, function($model) {
			return $model->name;
		});
		return $reorderableList->isValid();
	}
	
	// should be the string from the input
	public static function generateRelatedItemsForOrderableList($stringFromInput) {
		$reorderableList = new AjaxSelectReorderableList(json_decode($stringFromInput, true), function() {
			return new MediaItem();
		}, function($model) {
			return $model->name;
		});
		return $reorderableList->getStringForReorderableList();
	}
	
	public function getRelatedItemsForInputAttribute() {
		$reorderableList = new AjaxSelectReorderableList($this->getRelatedItemIdsForReorderableList(), function() {
			return new MediaItem();
		}, function($model) {
			return $model->name;
		});
		return $reorderableList->getStringForInput();
	}
	
	
	public function getIsAccessible() {
		if (!$this->enabled) {
			return false;
		}
		
		// check that it's in a playlist that is accessible
		foreach($this->playlists as $a) {
			if ($a->getIsAccessible()) {
				return true;
			}
		}
		return false;
	}
	
	public function scopeSearch($q, $value) {
		return $value === "" ? $q : $q->whereContains(array("name", "description"), $value);
	}
	
	public function isDeletable() {
		// there is currently no condition that should prevent a media item being deleted.
		// the database relation foreign key constraints should handle deletion of related records
		return true;
	}
}