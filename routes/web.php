<?php

use Illuminate\Support\Facades\Route;




Route::get('/',['App\Http\Controllers\JobsGeController','index'])->name('index');


Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
});
