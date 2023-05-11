<?php

Route::any('laravel-actions/{actionString}', \Lorisleiva\Actions\ActionController::class)
    ->middleware('web')
    ->name('laravel-actions.route');
