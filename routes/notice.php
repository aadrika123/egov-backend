<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Notice\NoticeController;

Route::group(['middleware' => ['json.response', 'auth:sanctum', 'request_logger']], function () {    
    Route::controller(NoticeController::class)->group(function () {
        Route::post("read-notice-type", "noticeType");
        Route::post("search-application", "serApplication");
        Route::post("add", "add");
        Route::post("get-notice-list", "noticeList");
        Route::post("application/dtl-by-id", "noticeView");
        Route::post("application/inbox", "inbox");
        Route::post("application/outbox", "outbox");
        Route::post("application/post-next", "postNextLevel");
        Route::post("application/approve-reject", "approveReject");
    });
});