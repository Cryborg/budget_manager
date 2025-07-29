<?php

use Illuminate\Support\Facades\Route;

// Filament gère toutes les routes
// La route par défaut redirige vers l'admin
Route::redirect('/', '/admin');
