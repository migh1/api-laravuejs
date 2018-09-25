<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
//CRUD usuário
Route::middleware('auth:api')->get('/usuario', 'Usuario@usuario');
Route::middleware('auth:api')->put('/perfil', 'Usuario@perfil');
Route::post('/cadastro', 'Usuario@cadastro');
Route::post('/login', 'Usuario@login');
