<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Gmail\ApiController;
use App\Http\Controllers\Gmail\WebController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Gmail Routes: Started
Route::get('/', [WebController::class, 'index'])->name('home');
Route::group(['prefix' => 'gmail'], function () {

    // Setup route started
    Route::get('/oauth/gmail', [WebController::class, 'login'])->name('gmail.login');
    Route::get('/oauth/gmail/logout', [WebController::class, 'logout'])->name('gmail.logout');
    // Returned URL
    Route::get('/gmail-api', [WebController::class, 'gmailCallback'])->name('gmailCallback');
    // Setup Routes: Ended


    // Application Routes: Started
    Route::get('/dashboard/{type}', [WebController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard/{type}/{id}', [WebController::class, 'singleEmail'])->name('singleEmail');

    Route::get('/email/reply/{id}', [WebController::class, 'replyEmail'])->name('replyEmail');
    Route::post('/email/reply/send', [WebController::class, 'replySend'])->name('gmail.reply');

    Route::get('/draft/{id}', [WebController::class, 'draft'])->name('gmail.draft');
    Route::post('/draft/send', [WebController::class, 'draftSend'])->name('gmail.draft.send');

    Route::get('/email/forward/{id}', [WebController::class, 'forwardEmail'])->name('forwardEmail');
    Route::post('/email/forward/send', [WebController::class, 'forwardSend'])->name('gmail.forward');

    Route::get('/compose/new', [WebController::class, 'composeNew'])->name('gmail.new');
    Route::post('/compose/send', [WebController::class, 'composeSend'])->name('gmail.send');

    Route::post('/search-by-transaction-id', [WebController::class, 'searchByTransaction'])->name('gmail.searchByTransaction');

    Route::get('/create-email-template', [WebController::class, 'createETemplate'])->name('gmail.createETemplate');
    Route::post('/save-email-template', [WebController::class, 'saveETemplate'])->name('gmail.saveETemplate');
    Route::post('/load-email-template', [WebController::class,'loadETemplate'])->name('gmail.loadETemplate');

    Route::get('/email/delete/{id}', [WebController::class, 'emailDelete'])->name('gmail.emailDelete');
    Route::get('/email/restore/{id}', [WebController::class, 'emailRestore'])->name('gmail.emailRestore');

    Route::post('/create-label', [WebController::class,'createLabel'])->name('gmail.createLabel');
    Route::post('/move-folder', [WebController::class, 'moveFolder'])->name('gmail.moveFolder');

    // Ajax Routes
    Route::post('/compose/attachment/save', [WebController::class, 'composeAttchSave'])->name('gmail.composeAttchSave');
    Route::post('/compose/attachment/delete', [WebController::class, 'composeAttchDelete'])->name('gmail.composeAttchDelete');
    Route::post('/compose/save', [WebController::class, 'draftSave'])->name('gmail.draftSave');

});

// Gmail Auth (for api controller)
Route::get('gmail/oauth/api', [ApiController::class, 'checkLogin']);
Route::get('gmail/callback', [ApiController::class, 'gmailCallback']);
// refer api.php for other api controller routes