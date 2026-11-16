<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['servico' => 'oficina-billing-service']));
