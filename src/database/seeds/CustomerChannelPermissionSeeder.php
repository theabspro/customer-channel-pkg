<?php
namespace Abs\CustomerChannelPkg\Database\Seeds;

use App\Permission;
use Illuminate\Database\Seeder;

class CustomerChannelPermissionSeeder extends Seeder {
	public function run() {
		$permissions = [
			//CHANNEL GROUPS
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'customer-channel-group',
				'display_name' => 'Channel Groups',
			],
			[
				'display_order' => 1,
				'parent' => 'customer-channel-group',
				'name' => 'add-customer-channel-group',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'customer-channel-group',
				'name' => 'edit-customer-channel-group',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'customer-channel-group',
				'name' => 'delete-customer-channel-group',
				'display_name' => 'Delete',
			],

		];
		Permission::createFromArrays($permissions);
	}

}