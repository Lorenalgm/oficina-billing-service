<?php

use Illuminate\Support\Facades\Route;

Route::get('/orcamentos/{id}', [\App\Http\Controllers\OrcamentoController::class, 'show']);
Route::post('/orcamentos/{id}/aprovar', [\App\Http\Controllers\OrcamentoController::class, 'aprovar']);
Route::post('/orcamentos/{id}/recusar', [\App\Http\Controllers\OrcamentoController::class, 'recusar']);
Route::post('/pagamentos/{id}/simular', [\App\Http\Controllers\PagamentoController::class, 'simular']);
