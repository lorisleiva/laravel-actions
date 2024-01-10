<?php

// The response is empty, because the middleware is supposed to redirect the request to the right action.
Route::any('laravel-actions/{actionString}', fn() => '')
    ->middleware(['web', \Lorisleiva\Actions\Middlewares\RedirectToAction::class])
    ->name('laravel-actions.route');
