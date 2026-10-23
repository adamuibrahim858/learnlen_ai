<?php

use App\Http\Controllers\LearningController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LearningController::class, 'index'])->name('landing');
Route::post('/generate', [LearningController::class, 'generate'])->name('learning.generate');
Route::get('/learn', [LearningController::class, 'learningPath'])->name('learning.path');
Route::post('/quiz/submit', [LearningController::class, 'submitQuiz'])->name('quiz.submit');
Route::post('/reset', [LearningController::class, 'reset'])->name('learning.reset');
