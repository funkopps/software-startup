<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\MixAnalysisController;

Route::get('/', function () {
    return Inertia::render('Home', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('about', function () {
    return Inertia::render('About');
})->name('about');

Route::get('contact', function () {
    return Inertia::render('Contact');
})->name('contact');

Route::get('support', function () {
    return Inertia::render('Support');
})->name('support');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/mix-analyzer', function () {
    return Inertia::render('MixAnalyzer', [
        'title' => 'Mix Analyzer MVP',
    ]);
});

Route::post('/analyze-mix', [MixAnalysisController::class, 'analyze']);

Route::post('/upload-audio', [MixAnalysisController::class, 'upload']);

Route::post('/delete-audio', [MixAnalysisController::class, 'delete']);

require __DIR__ . '/settings.php';
