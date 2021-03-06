<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveSourceFileIdFromVideoFiles extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('video_files', function(Blueprint $table)
		{
			$table->dropForeign('source_file_fk');
			$table->dropColumn("source_file_id");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('video_files', function(Blueprint $table)
		{
			$table->integer("source_file_id")->unsigned()->nullable();
			
			$table->index("source_file_id");
			
			$table->foreign("source_file_id", "source_file_fk")->references('id')->on('files')->onUpdate("restrict")->onDelete('set null');
		});

	}

}
