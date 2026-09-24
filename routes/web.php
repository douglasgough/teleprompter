<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::scripts.index')->name('scripts.index');
Route::livewire('/scripts/{script}', 'pages::scripts.show')->name('scripts.show');
