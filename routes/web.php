<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\DatabaseChatController;
use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('chat.index'));

// Document management
Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

Route::get('/documents/sample/download', [DocumentController::class, 'downloadSample'])->name('documents.sample');
Route::post('/documents/db-preview', [DocumentController::class, 'previewDbQuery'])->name('documents.db-preview');

// RAG chat (documents)
Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
Route::post('/chat/ask', [ChatController::class, 'ask'])->name('chat.ask');

// Database chat (Text-to-SQL)
Route::get('/db-chat', [DatabaseChatController::class, 'index'])->name('db-chat.index');
Route::post('/db-chat/ask', [DatabaseChatController::class, 'ask'])->name('db-chat.ask');
Route::post('/db-chat/test', [DatabaseChatController::class, 'testConnection'])->name('db-chat.test');
