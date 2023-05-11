<?php

Route::any('laravel-actions/{actionString}', \Lorisleiva\Actions\ActionController::class)->name('laravel-actions.route');
