<?php

namespace App\Http\Controllers;

use App\Actions\Audio\RecogniseAudio;

abstract class Controller
{
    public function a()
    {
        $recognizer = app(RecogniseAudio::class);

        $recognizer->run('', 1, 2);

        // [ 'timestamp' => X, 'music' => Music[] ]
    }
}
