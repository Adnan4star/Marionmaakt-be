<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::get('/2', function () {
    return "Hello API";
});

//Filters
Route::get('filters',[\App\Http\Controllers\FilterController::class,'index']);
Route::get('edit-filter',[\App\Http\Controllers\FilterController::class,'EditFilter']);
Route::post('filter-save',[\App\Http\Controllers\FilterController::class,'SaveFilter']);
Route::delete('delete-filter',[\App\Http\Controllers\FilterController::class,'DeleteFilter']);
Route::get('update-filter-status',[\App\Http\Controllers\FilterController::class,'UpdateFilterStatus']);

//Blog
Route::get('blogs',[\App\Http\Controllers\BlogController::class,'index']);
Route::get('edit-blog',[\App\Http\Controllers\BlogController::class,'EditBlog']);
Route::get('sync-blogs',[\App\Http\Controllers\BlogController::class,'BlogsSync']);
Route::get('create-blog',[\App\Http\Controllers\BlogController::class,'CreateBlog']);
Route::post('save-blog',[\App\Http\Controllers\BlogController::class,'SaveBlog']);
Route::post('update-blog',[\App\Http\Controllers\BlogController::class,'UpdateBlog']);
Route::delete('delete-blog',[\App\Http\Controllers\BlogController::class,'DeleteBlog']);

//product
Route::get('products',[\App\Http\Controllers\ProductController::class,'ProductsSync']);

Route::get('show-product',[\App\Http\Controllers\ProductController::class, 'showProduct']);

//store front
Route::get('search-blog',[\App\Http\Controllers\StoreFrontController::class,'SearchBlog']);
Route::get('filter-blog',[\App\Http\Controllers\StoreFrontController::class,'FilterBlog']);