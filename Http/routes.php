<?php

Route::group(['prefix' => 'vkintegration', 'namespace' => 'Modules\VKIntegration\Http\Controllers'], function () {
    Route::post('/webhook', ['uses' => 'VKIntegrationController@webhook'])->name('vkintegrationmodule.external');
});
