<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FaceController;

// Redirect root to face recognition dashboard
Route::get('/', [FaceController::class, 'index'])->name('face.index');

// Face recognition routes
Route::prefix('face')->name('face.')->group(function () {
    Route::get('/dashboard', [FaceController::class, 'index'])->name('dashboard');
    
    // Registration routes
    Route::get('/register', [FaceController::class, 'showRegisterForm'])->name('register.form');
    Route::post('/register', [FaceController::class, 'register'])->name('register');
    
    // Matching routes
    Route::get('/match', [FaceController::class, 'showMatchForm'])->name('match.form');
    Route::post('/match', [FaceController::class, 'match'])->name('match');
    
    // People management
    Route::get('/people', [FaceController::class, 'people'])->name('people');
    Route::delete('/people/{person}', [FaceController::class, 'deletePerson'])->name('people.delete');
    
    // Match history
    Route::get('/history', [FaceController::class, 'history'])->name('history');
});