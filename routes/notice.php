<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Notice\Application;

Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {    
    Route::controller(Application::class)->group(function () {
        Route::post("read-notice-type", "noticeType");
        Route::post("search-application", "serApplication");
        Route::post("add", "add");
        Route::post("get-notice-list", "noticeList");
    });
});