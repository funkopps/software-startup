<?php

use Illuminate\Support\Facades\Route;

Route::get('/testje', function () {
    $a = app(\App\Actions\Audio\CutAudio::class);

    $r = $a->run(
        base_path('tests/mocks/identify/berlioz_sample.wav'),
        storage_path('/app/private/test.wav'),
        1,
        3
    );

    dd($r);
});