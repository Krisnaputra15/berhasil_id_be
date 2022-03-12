<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\umkmController;
use App\Http\Controllers\postController;
use App\Http\Controllers\userController;
use App\Http\Controllers\premiumController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [userController::class, 'register']);
Route::post('/login', [userController::class, 'login']);

Route::group(['middleware' => ['jwt.verify']], function() {
    //user
    Route::get('/user/{id_user}/profile', [userController::class, 'getProfile']);
    Route::post('/user/{id_user}', [userController::class, 'updateFirstTime']);
    Route::put('/user/{id_user}', [userController::class, 'update']);

    //api for posts, Ads, and Recommendations
    Route::get('/user/posts', [userController::class, 'getPosts']);
    Route::get('/user/ads', [userController::class, 'getAds']);
    Route::get('/user/recommendations', [userController::class, 'getRecommendedUmkm']);//

    //search user
    Route::post('/search',[userController::class, 'search']);

    //umkm
    Route::get('/umkm/{id_umkm}/profile', [umkmController::class, 'getUmkmProfile']);
    Route::put('/umkm/{id_umkm}/update', [umkmController::class, 'update']);
    Route::post('/umkm/post/{id_post}/advertise', [umkmController::class, 'makeAd']);

    //post
    Route::post('/umkm/{id_umkm}/post/', [postController::class, 'createPost']);
    Route::delete('/umkm/post/{id_post}', [postController::class, 'deletePost']);
    Route::get('/umkm/post/{id_post}', [postController::class, 'getPost']);
    Route::post('/umkm/post/{id_post}/comment', [postController::class, 'comment']);
    Route::delete('/umkm/post/comment/{id_comment}/delete', [postController::class, 'deleteComment']);
    Route::get('/user/{id_user}/post/{id_post}/like', [postController::class, 'like']);

    //premium
    Route::get('/premium', [premiumController::class, 'showPremiumPack']);
});
