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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('changeTicket','Api\TicketController@changeTicket');
Route::get('getickets/{userId}','Api\UserController@getickets');
Route::post('updateimage','Api\UserController@update_profile_image');
Route::post('updateprofile','Api\UserController@updateProfile');

Route::post('register','Api\UserController@register');
Route::post('login','Api\UserController@login');

Route::get('mytickets/{userId}','Api\UserController@myTickets');
Route::get('getinfo/{type}','Api\SettingController@getInfo');

Route::post('createticket','Api\UserController@createTicket');
Route::post('createresult','Api\UserController@createResult');
Route::post('joingame','Api\UserController@joinGame');
Route::post('saveresponse','Api\UserController@saveResponse');

Route::post('createtransaction','Api\UserController@createTransaction');
Route::post('updatetransactions','Api\UserController@updateTransactions');


Route::post('createprize','Api\PrizeController@createPrize');
Route::get('getprizes','Api\PrizeController@getPrizes');
Route::get('getallprizes','Api\PrizeController@getAllPrizes');
Route::get('updateprizestatus/{prizeId}','Api\PrizeController@updatePrizeStatus');
Route::post('updateprize','Api\PrizeController@updatePrize');
Route::get('deleteprize/{prizeId}','Api\PrizeController@deletePrize');

Route::get('getallusers','Api\UserController@getAllUsers');
Route::get('getnextgametime/{type}','Api\UserController@getNextGameTime');
Route::get('getticketfornextgame/{userId}/{time}','Api\UserController@getTicketForNextGame');
Route::get('getuserbalance/{userId}','Api\UserController@getUserBalance');
Route::get('getleaderboard/{type}','Api\UserController@getLeaderboard');

Route::post('shuffleicket','Api\UserController@shuffleTicket');
Route::post('createtransaction','Api\UserController@createTransaction');

Route::get('getgametiming/{date}','Api\TicketController@getGameTimings');
Route::get('gettimes/{date}','Api\TicketController@getTimesForDate');

Route::get('getgameadmin/{date}/{time}','Api\TicketController@getGameAdmin');
Route::get('getticketsdt/{date}/{time}','Api\TicketController@getTicketsDT');


Route::post('updategame','Api\TicketController@updateGameAdmin');
Route::post('addgame','Api\TicketController@addMainGame');

Route::get('deletegame/{time}','Api\TicketController@deleteMainGame');
Route::get('deletegamechange/{date}/{time}','Api\TicketController@deleteGameChange');


Route::get('gettransactions/{status}','Api\TicketController@getTransactions');
Route::get('gettotals','Api\TicketController@getTotals');
// Route::get('getLastJoinId','Api\UserController@getLastJoinId');

Route::get('getPendingTxns/{userId}','Api\UserController@getPendingTxns');


Route::get('findNextGame','Api\UserController@findNextGame');
Route::get('getTicketUser/{type}','Api\UserController@getTicketUser');
Route::get('getTicketsNew/{userId}','Api\UserController@getTicketsNew');

