<?php namespace uk\co\la1tv\website\models;

class VideoFileHls extends MyEloquent {

	protected $table = 'video_files_hls';
	protected $guarded = array('*');
	
	public function file() {
		return $this->belongsTo(self::$p.'File', 'file_id');
	}
	
	public function videoFile() {
		return $this->belongsTo(self::$p.'VideoFile', 'video_files_id');
	}

}