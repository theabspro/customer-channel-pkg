<?php

Route::group(['namespace' => 'Abs\CustomerChannelPkg', 'middleware' => ['web', 'auth'], 'prefix' => 'customer-channel-pkg'], function () {
	Route::get('/customer-channel-groups/get-list', 'CustomerChannelGroupController@getCustomerChannelGroupList')->name('getCustomerChannelGroupList');
	Route::get('/customer-channel-group/get-form-data/{id?}', 'CustomerChannelGroupController@getCustomerChannelGroupFormData')->name('getCustomerChannelGroupFormData');
	Route::post('/customer-channel-group/save', 'CustomerChannelGroupController@saveCustomerChannelGroup')->name('saveCustomerChannelGroup');
	Route::get('/customer-channel-group/delete/{id}', 'CustomerChannelGroupController@deleteCustomerChannelGroup')->name('deleteCustomerChannelGroup');
});