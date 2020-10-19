<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Settings;
use Illuminate\Http\Request;
use App\Helpers\ApiHelper;

class SettingController extends Controller
{
    public function getInfo($type){

        $information=Settings::find(0)
                    ->select($type)
                    ->first();

    $response=ApiHelper::createAPIResponse(false,200,"",$information->$type);
    return response()->json($response, 200); 
    }
}
