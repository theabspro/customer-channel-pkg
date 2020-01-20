<?php
Route::group(['namespace' => 'Abs\CustomerChannelPkg\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'customer-channel-group-pkg/api'], function () {
		Route::group(['middleware' => ['auth:api']], function () {
			// Route::get('taxes/get', 'TaxController@getTaxes');
		});
	});
});