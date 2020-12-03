<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\Concerns\AsController;

class AsControllerTest
{
    use AsController;

    /** @var int */
    public static $constructed = 0;

    /** @var int */
    public static $handled = 0;

    public function __construct()
    {
        static::$constructed++;
    }

    public function handle($left, $right, Filesystem $filesystem)
    {
        static::$handled++;

        return response()->json([
            'addition' => $left + $right,
        ]);
    }
}

it('can be run as a controller', function () {
    // Given the action is registered as a controller.
    Route::get('/calculator/{left}/plus/{right}', AsControllerTest::class);

    // When we call that route.
    $reponse = $this->getJson('/calculator/5/plus/3');

    // Then
    dd($reponse->json());
});
