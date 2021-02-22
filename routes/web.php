<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Gmail\ApiController;
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
Route::get('/', [ApiController::class, 'index'])->name('home');
Route::group(['prefix' => 'gmail'], function () {

    // Setup route started
    Route::get('/oauth/gmail', [ApiController::class, 'login'])->name('gmail.login');
    Route::get('/oauth/gmail/logout', [ApiController::class, 'logout'])->name('gmail.logout');
    // Returned URL
    Route::get('/gmail-api', [ApiController::class, 'gmailCallback'])->name('gmailCallback');
    // Setup Routes: Ended


    // Application Routes: Started
    Route::get('/dashboard/{type}', [ApiController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard/{type}/{id}', [ApiController::class, 'singleEmail'])->name('singleEmail');

    Route::get('/email/reply/{id}', [ApiController::class, 'replyEmail'])->name('replyEmail');
    Route::post('/email/reply/send', [ApiController::class, 'replySend'])->name('gmail.reply');

    Route::get('/draft/{id}', [ApiController::class, 'draft'])->name('gmail.draft');
    Route::post('/draft/send', [ApiController::class, 'draftSend'])->name('gmail.draft.send');

    Route::get('/email/forward/{id}', [ApiController::class, 'forwardEmail'])->name('forwardEmail');
    Route::post('/email/forward/send', [ApiController::class, 'forwardSend'])->name('gmail.forward');

    Route::get('/compose/new', [ApiController::class, 'composeNew'])->name('gmail.new');
    Route::post('/compose/send', [ApiController::class, 'composeSend'])->name('gmail.send');

    Route::post('/search-by-transaction-id', [ApiController::class, 'searchByTransaction'])->name('gmail.searchByTransaction');

    Route::get('/create-email-template', [ApiController::class, 'createETemplate'])->name('gmail.createETemplate');
    Route::post('/save-email-template', [ApiController::class, 'saveETemplate'])->name('gmail.saveETemplate');
    Route::post('/load-email-template', [ApiController::class,'loadETemplate'])->name('gmail.loadETemplate');

    Route::get('/email/delete/{id}', [ApiController::class, 'emailDelete'])->name('gmail.emailDelete');
    Route::get('/email/restore/{id}', [ApiController::class, 'emailRestore'])->name('gmail.emailRestore');

    Route::post('/create-label', [ApiController::class,'createLabel'])->name('gmail.createLabel');
    Route::post('/move-folder', [ApiController::class, 'moveFolder'])->name('gmail.moveFolder');

    // Ajax Routes
    Route::post('/compose/attachment/save', [ApiController::class, 'composeAttchSave'])->name('gmail.composeAttchSave');
    Route::post('/compose/attachment/delete', [ApiController::class, 'composeAttchDelete'])->name('gmail.composeAttchDelete');
    Route::post('/compose/save', [ApiController::class, 'draftSave'])->name('gmail.draftSave');
});
