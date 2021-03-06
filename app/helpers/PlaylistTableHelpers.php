<?php

class PlaylistTableHelpers {
	
	public static function getFooterObj($mediaItem) {
		if ($mediaItem->scheduled_publish_time->isPast()) {
			return null;
		}
		$mediaItem->load("liveStreamItem");
		$isLive = !is_null($mediaItem->liveStreamItem) && $mediaItem->liveStreamItem->getIsAccessible();
		if ($isLive && intval($mediaItem->liveStreamItem->getResolvedStateDefinition()->id) !== 1) {
			return null;
		}
		
		return array(
			"isLive"	=> $isLive,
			"dateTxt"	=> $mediaItem->scheduled_publish_time->format("d/m/y H:i")
		);
	}

	public static function getDuration($mediaItem) {
		$videoItem = $mediaItem->videoItem;
		if (is_null($videoItem) || !$videoItem->getIsLive()) {
			return null;
		}
		return $videoItem->getDurationPretty();
	}
}