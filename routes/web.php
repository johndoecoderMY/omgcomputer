<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::redirect('/about', '/#about')->name('about');
Route::redirect('/help', '/#help')->name('help');
Route::redirect('/contact', '/#contact')->name('contact');
Route::view('/privacy', 'pages.privacy')->name('privacy');
Route::view('/toc', 'pages.toc')->name('toc');
Route::view('/terms', 'pages.toc')->name('terms');
Route::redirect('/faq', '/#faq')->name('faq');
Route::redirect('/qna', '/#faq')->name('qna');
Route::redirect('/qa', '/#faq')->name('qa');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
