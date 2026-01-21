<?php

use Illuminate\Support\Facades\Route;

Route::get('/testje', function () {
    $a = app(\App\Actions\Audio\RecognizeAudio::class);

    $res = $a->run(
        base_path('tests/mocks/identify/berlioz_sample.wav'),
        0,
        5,
    );

    dd($res);
});