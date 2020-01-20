<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChannelsUShhy extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('channels', function (Blueprint $table) {
			$table->unsignedInteger('channel_group_id')->nullable()->after('company_id');

			$table->foreign('channel_group_id')->references('id')->on('customer_channel_groups')->onDelete('SET NULL')->onUpdate('cascade');

			$table->unique(["company_id", "channel_group_id", "name"]);
			$table->unique(["company_id", "channel_group_id", "short_name"]);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {

		$table->dropUnique('channels_company_id_channel_group_id_name_unique');
		$table->dropUnique('channels_company_id_channel_group_id_short_name_unique');

		$table->dropForeign('channels_channel_group_id_foreign');
		$table->dropColumn('channel_group_id');

	}
}
