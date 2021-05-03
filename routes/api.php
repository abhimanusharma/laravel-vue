<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Gmail\ApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

//Gmail actions
Route::get('gmail/login', [ApiController::class, 'login']);
Route::group(['middleware' => ['gmailAuth']], function () {
    // Gmail templates //
    Route::get('gmail/templates', [ApiController::class, 'fetchGmailTemplates']);
    Route::post('gmail/templates', [ApiController::class, 'saveGmailTemplates']);
    Route::delete('gmail/templates/{id}', [ApiController::class, 'deleteGmailTemplate']);
    // Gmail Manage //
    Route::get('gmail/{type}', [ApiController::class, 'dashboard']);
    Route::get('gmail/{type}/{id}', [ApiController::class, 'singleEmail']);
    Route::delete('gmail/{type}/{id}/delete', [ApiController::class, 'emailDelete']);
    // Send/Compose Email/Draft Start
    Route::post('gmail/send', [ApiController::class, 'composeSend']);
    Route::post('gmail/attachments', [ApiController::class, 'composeAttchSave']);
    Route::post('gmail/attachments/delete', [ApiController::class, 'composeAttchDelete']);
    Route::post('gmail/drafts', [ApiController::class, 'draftSave']);
});