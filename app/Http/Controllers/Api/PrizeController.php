<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ApiHelper;
use App\Models\Prizes;
use DB;

class PrizeController extends Controller
{
    public function createPrize(Request $request){
        $rules =[
            'prize_unit_price'=>'required',
            'prize_count'=>'required',
            'ef_rate'=>'required',
            'li_rate'=>'required',
            'fh_rate'=>'required',
        ];

        $validator=Validator::make($request->all(),$rules);
        if($validator->fails()){
            $response=ApiHelper::createAPIResponse(true,400,$validator->errors(),null);
            return response()->json($response,400);
        }

        $prize=Prizes::create($request->all());

        $response=ApiHelper::createAPIResponse(false,200,"Prize created successfully",null);
        return response()->json($response, 200); 

    }

    public function getPrizes(){
        $prizes=DB::table('prizes')
                        ->select('*')
                        ->where('is_active','=',1)
                        ->orderBy('prize_unit_price','DESC')
                        ->get();

        $response=ApiHelper::createAPIResponse(false,200,"",$prizes);
        return response()->json($response, 200); 
    }


    public function getAllPrizes(){
        $prizes=DB::table('prizes')
                        ->select('*')
                        ->orderBy('prize_unit_price','DESC')
                        ->get();

        $response=ApiHelper::createAPIResponse(false,200,"",$prizes);
        return response()->json($response, 200); 
    }

    public function updatePrize(Request $request){
        $prize = Prizes::where('prize_id', '=', $request->prize_id)->first();
        $count=$prize->update(['prize_unit_price'=>$request->prize_unit_price,'prize_count'=>$request->prize_count,'ef_rate'=>$request->ef_rate,'li_rate'=>$request->li_rate,'fh_rate'=>$request->fh_rate,'is_active'=>$request->is_active]);

        if($count==1){
            $response=ApiHelper::createAPIResponse(false,200,"Prize updated successfully",$prize);
            return response()->json($response,200);
           }else{
            $response=ApiHelper::createAPIResponse(true,401,"",null);
            return response()->json($response,401);
           }

    }

    public function updatePrizeStatus($prizeId){
        $prize=Prizes::where('prize_id','=',$prizeId)
                        ->first();

        if($prize->is_active==true){
            $prize->is_active=false;
        }else{
            $prize->is_active=true;
        }
        $prize->save();

        $response=ApiHelper::createAPIResponse(false,200,"Prize status updted successfully",$prize);
            return response()->json($response,200);
    }

    public function deletePrize($prizeId){
        $prize=Prizes::where('prize_id','=',$prizeId)->delete();

        $response=ApiHelper::createAPIResponse(false,202,"",null);
        return response()->json($response,202);
    }
}
