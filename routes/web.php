<?php

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

use App\Http\Controllers\ExceptionController;
use App\Http\Controllers\ControlMappingController;
use App\Http\Controllers\RiskController;
use App\Http\Controllers\RiskScoringConfigController;

Auth::routes();

/* Socialite (must be reachable while the user is NOT yet authenticated) */
Route::namespace('App\\Http\\Controllers')->group(function () {
    Route::get('auth/redirect/{driver}', 'SocialiteController@redirect')->name('socialite.redirect');
    Route::get('auth/callback/{driver}', 'SocialiteController@callback')->name('socialite.callback');
});

Route::namespace('App\\Http\\Controllers')->middleware('auth')->group(function () {
    /* Index */
    Route::get('/', 'HomeController@index');
    Route::get('/home', 'HomeController@index');
    Route::get('/index', 'HomeController@index');
    Route::redirect('/admin', '/');

    /* Global-search engine */
    Route::get('global-search', 'GlobalSearchController@search');

    /* Profile */
    Route::get('/profile', 'ProfileController@index')->name('profile');
    Route::post('/profile/update', 'ProfileController@updateProfile')->name('profile.update');
    Route::get('/profile/avatar/{id}', 'ProfileController@avatar');

    /* About */
    Route::get('/about', 'HomeController@test');
    Route::view('/about', 'about');

    /* Controls */
    Route::get('/alice/index', 'ControlController@index');
    Route::get('/alice/create', 'ControlController@create');
    Route::post('/alice/store', 'ControlController@store');
    Route::post('/alice/save/{id}', 'ControlController@update');
    Route::get('/alice/{id}/edit', 'ControlController@edit');
    Route::get('/alice/plan/{id}', 'ControlController@plan');
    Route::get('/alice/show/{id}', 'ControlController@show');
    Route::get('/alice/clone/{id}', 'ControlController@clone');
    Route::post('/alice/delete/{id}', 'ControlController@destroy');
    Route::post('/alice/activate/{id}', 'ControlController@activate');
    Route::get('/alice/import', 'MeasureImportController@show');
    Route::post('/alice/import', 'MeasureImportController@import');
    Route::get('/alice/download', 'MeasureImportController@download');

    /* Measures */
    Route::get('/bob/index', 'MeasureController@index');
    Route::get('/bob/create', 'MeasureController@create');
    Route::post('/bob/store', 'MeasureController@store');
    Route::get('/bob/show/{id}', 'MeasureController@show');
    Route::get('/bob/make/{id}', 'MeasureController@make');
    Route::get('/bob/edit/{id}', 'MeasureController@edit');
    Route::post('/bob/template/{id}', 'MeasureController@template');
    Route::get('/bob/clone/{id}', 'MeasureController@clone');
    Route::get('/bob/delete/{id}', 'MeasureController@destroy');
    Route::post('/bob/make', 'MeasureController@doMake');
    Route::post('/bob/accept', 'MeasureController@accept');
    Route::post('/bob/reject', 'MeasureController@reject');
    Route::post('/bob/plan', 'MeasureController@doPlan');
    Route::post('/bob/unplan', 'MeasureController@unplan');
    Route::post('/bob/draft', 'MeasureController@draft');
    Route::post('/bob/save', 'MeasureController@save');
    Route::get('/bob/history', 'MeasureController@history');
    Route::get('/bob/upload/{id}', 'MeasureController@upload');
    Route::get('/bob/plan/{id}', 'MeasureController@plan');

    /* Radars */
    Route::get('/radar/domains', 'MeasureController@domains');
    Route::get('/radar/alice', 'MeasureController@measures');
    Route::get('/radar/attributes', 'MeasureController@attributes');
    Route::get('/radar/bob', 'MeasureController@tempo');
    Route::get('/radar/actions', 'ActionController@chart');

    /* Documents */
    Route::post('/doc/store', 'DocumentController@store');
    Route::get('/doc/delete/{id}', 'DocumentController@delete');
    Route::get('/doc/show/{id}', 'DocumentController@get');

    Route::get('/doc', 'DocumentController@index');
    Route::get('/doc/check', 'DocumentController@check');
    Route::get('/doc/template', 'DocumentController@getTemplate');
    Route::post('/doc/template', 'DocumentController@saveTemplate');
    Route::post('/doc/config', 'DocumentController@saveConfig');
    Route::get('/doc/config', 'DocumentController@index');

    /* Configuration */
    Route::get('/config', 'ConfigurationController@index');
    Route::post('/config/save', 'ConfigurationController@save');

    /* Group view (admin toggle: own data vs all users' data) */
    Route::get('/group/toggle', 'GroupViewController@toggle');

    /* Other */
    Route::resource('domains', 'DomainController');
    Route::resource('attributes', 'AttributeController');
    Route::get('attribute/replace', 'AttributeController@replace');
    Route::post('attribute/replace', 'AttributeController@replaceStore');
    Route::resource('users', 'UserController');
    Route::resource('groups', 'UserGroupController');

    /* Actions */
    Route::get('/actions', 'ActionController@index');
    Route::get('/action/show/{id}', 'ActionController@show');
    Route::get('/action/create', 'ActionController@create');
    Route::get('/action/edit/{id}', 'ActionController@edit');
    Route::get('/action/close/{id}', 'ActionController@close');

    Route::post('/action/store', 'ActionController@store');
    Route::post('/action/update', 'ActionController@update');
    Route::post('/action/save', 'ActionController@save');
    Route::post('/action/close', 'ActionController@doClose');
    Route::post('/action/delete', 'ActionController@delete');

    /* Reports */
    Route::get('/reports', 'ReportController@show');
    Route::get('/reports/pilotage', 'ReportController@pilotage');
    Route::get('/reports/soa', 'ReportController@soa');

    // Audit Logs
    Route::get('/logs', 'AuditLogsController@index');
    Route::get('/logs/show/{id}', 'AuditLogsController@show');
    Route::get('/logs/history/{type}/{id}', 'AuditLogsController@history');

    /* Exports */
    Route::get('/export/domains', 'DomainController@export');
    Route::get('/export/attributes', 'AttributeController@export');
    Route::get('/export/alices', 'ControlController@export');
    Route::get('/export/bobs', 'MeasureController@export');
    Route::get('/export/actions', 'ActionController@export');
    Route::get('/export/users', 'UserController@export');
    Route::get('/export/risks', 'RiskController@export');

    /* Framework crosswalk */
    Route::get('/crosswalk', [ControlMappingController::class, 'index'])->name('crosswalk.index');
    Route::get('/crosswalk/matrix', [ControlMappingController::class, 'matrix'])->name('crosswalk.matrix');
    Route::get('/crosswalk/export', [ControlMappingController::class, 'export'])->name('crosswalk.export');
    Route::get('/crosswalk/create', [ControlMappingController::class, 'create'])->name('crosswalk.create');
    Route::post('/crosswalk', [ControlMappingController::class, 'store'])->name('crosswalk.store');
    Route::get('/crosswalk/{controlMapping}', [ControlMappingController::class, 'show'])->name('crosswalk.show');
    Route::get('/crosswalk/{controlMapping}/edit', [ControlMappingController::class, 'edit'])->name('crosswalk.edit');
    Route::put('/crosswalk/{controlMapping}', [ControlMappingController::class, 'update'])->name('crosswalk.update');
    Route::delete('/crosswalk/{controlMapping}', [ControlMappingController::class, 'destroy'])->name('crosswalk.destroy');

// --- Registre des risques ---
    Route::get('/risk/index',           [RiskController::class, 'index'])->name('risk.index');
    Route::get('/risk/create',          [RiskController::class, 'create'])->name('risk.create');
    Route::post('/risk/store',          [RiskController::class, 'store'])->name('risk.store');
    Route::get('/risk/show/{id}',       [RiskController::class, 'show'])->name('risk.show');
    Route::get('/risk/edit/{id}',       [RiskController::class, 'edit'])->name('risk.edit');
    Route::post('/risk/save',           [RiskController::class, 'update'])->name('risk.save');
    Route::get('/risk/delete/{id}',     [RiskController::class, 'destroy'])->name('risk.destroy');
    Route::get('/risk/matrix',          [RiskController::class, 'matrix'])->name('risk.matrix');
    Route::get('/risk/export',          [RiskController::class, 'export'])->name('risk.export');

    // --- Configuration du scoring (Admin uniquement) ---
    Route::get('/risk/scoring',                     [RiskScoringConfigController::class, 'index'])->name('risk.scoring.index');
    Route::get('/risk/scoring/create',              [RiskScoringConfigController::class, 'create'])->name('risk.scoring.create');
    Route::post('/risk/scoring/store',              [RiskScoringConfigController::class, 'store'])->name('risk.scoring.store');
    Route::get('/risk/scoring/{id}/edit',           [RiskScoringConfigController::class, 'edit'])->name('risk.scoring.edit');
    Route::post('/risk/scoring/{id}/save',          [RiskScoringConfigController::class, 'update'])->name('risk.scoring.update');
    Route::post('/risk/scoring/{id}/activate',      [RiskScoringConfigController::class, 'activate'])->name('risk.scoring.activate');
    Route::get('/risk/scoring/{id}/delete',         [RiskScoringConfigController::class, 'destroy'])->name('risk.scoring.destroy');

    // --- Gestion des exceptions (issue #590) ---
    Route::get('/exception/index',          [ExceptionController::class, 'index'])->name('exception.index');
    Route::get('/exception/create',         [ExceptionController::class, 'create'])->name('exception.create');
    Route::post('/exception/store',         [ExceptionController::class, 'store'])->name('exception.store');
    Route::get('/exception/show/{id}',      [ExceptionController::class, 'show'])->name('exception.show');
    Route::get('/exception/edit/{id}',      [ExceptionController::class, 'edit'])->name('exception.edit');
    Route::post('/exception/save',          [ExceptionController::class, 'update'])->name('exception.save');
    Route::get('/exception/delete/{id}',    [ExceptionController::class, 'destroy'])->name('exception.destroy');

    // Transitions de workflow
    Route::post('/exception/submit',        [ExceptionController::class, 'submit'])->name('exception.submit');
    Route::post('/exception/approve',       [ExceptionController::class, 'approve'])->name('exception.approve');
    Route::post('/exception/reject',        [ExceptionController::class, 'reject'])->name('exception.reject');


});
