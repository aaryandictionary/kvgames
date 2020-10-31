<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tests;
use Illuminate\Http\Request;
use App\Helpers\ApiHelper;
use App\Models\MyTickets;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    public function addTest(Request $request){
        $test=Tests::create($request->all());

        $response=ApiHelper::createAPIResponse(false,200,"Test added successfully",$test);
        return response()->json($response,200);
    }

    public function changeStatus($id){
        $test=Tests::where('id',$id)->first();

        if($test){
            if($test->status==0){
                $test->status=1;
            }else{
                $test->status=0;
            }
            $test->save();

        $response=ApiHelper::createAPIResponse(false,200,"Status changed successfully",$test);
        return response()->json($response,200);
        
    }else{
        $response=ApiHelper::createAPIResponse(false,101,"Cannot change statsu",null);
        return response()->json($response,200);
        }
    }

    public function updateTest(Request $request){
        $test=Tests::where('id',$request->id)->first();

        if($test){
            $test->time=$request->time;
            $test->sendto=$request->sendto;
            $test->title=$request->title;
            $test->message=$request->message;

            $test->save();

        $response=ApiHelper::createAPIResponse(false,200,"Test updated successfully",$test);
        return response()->json($response,200);
    }else{
        $response=ApiHelper::createAPIResponse(false,101,"Cannot update Test",null);
        return response()->json($response,200);
        }
    }

    public function deleteTest($id){
        $test=Tests::where('id',$id)->delete();

        $response=ApiHelper::createAPIResponse(false,200,"Test deleted successfully",null);
        return response()->json($response,200);
    }

    public function getAllTest(){
        $tests=Tests::select('id','time','sendto','title','message','status')->orderBy('time')->get();

        $response=ApiHelper::createAPIResponse(false,200,"",$tests);
        return response()->json($response,200);
    }


    public function sendCustomNotification(Request $request){
        $tokens=$this->getTicketUser($request->type);

        if($tokens){
            $this->notification($tokens,$request->title,$request->message);
        }

        $response=ApiHelper::createAPIResponse(false,200,"",null);
        return response()->json($response,200);
    }




    public function getTicketUser($type){
        $dateTime=$this->findNextGame();

        if($type==0){
            $usersWithTicket=MyTickets::where('my_ticket_date',$dateTime['date'])
                                    ->where('my_ticket_time',$dateTime['time'])
                                    ->distinct()
                                    ->pluck('user_id');
        $tokens=User::whereIn('id',$usersWithTicket)
                        ->where('gms_token','!=',null)
                        ->distinct()
                        ->pluck('gms_token');
        }else if($type==1){
            $usersWithTicket=MyTickets::where('my_ticket_date',$dateTime['date'])
                                    ->where('my_ticket_time',$dateTime['time'])
                                    ->distinct()
                                    ->pluck('user_id');
        $tokens=User::whereNotIn('id',$usersWithTicket)
                        ->where('gms_token','!=',null)
                        ->distinct()
                        ->pluck('gms_token');
        }else{
            $tokens=DB::table('users')
                    ->where('status',1)
                    ->where('gms_token','!=',null)
                    ->pluck('gms_token');
        }
        
        return $tokens;
        // $response=ApiHelper::createAPIResponse(false,200,"",$tokens);
        // return response()->json($response,200);
    }


    public function findNextGame(){
        $today=new Carbon();

        $date=$today->toDateString();
        $timeNow=$today->toTimeString();
        // $time=$today->addMinutes(-10)->toTimeString();

        // echo $date.' '.$timeNow;

        $i=0;

        do{
            $i++;
       
        $minus=DB::table('ticket_category_changes')
                        ->where('change_for_date','=',$date)
                        ->where('change_for_time','>',$timeNow)
                        // ->where('change_for_time','<',$time)
                        ->pluck('change_for_time');


        $add=DB::table('ticket_category_changes')
                        ->select(DB::raw('change_for_time AS ticket_time'))
                        ->where('change_for_time','>',$timeNow)
                        // ->where('change_for_time','<',$time)
                        ->where('change_for_date','=',$date)
                        ->where('status','=',1);

        $ticket=DB::table('ticket_category')
                        ->whereNotIn('ticket_time',$minus)
                        ->where('is_enabled',1)
                        ->select('ticket_time')
                        ->where('ticket_time','>',$timeNow)
                        // ->where('ticket_time','<',$time)
                        ->union($add)
                        ->orderBy('ticket_time','ASC')
                        ->get();
                        
        $date=$today->addDay(1)->toDateString();
        $timeNow="00:00:00";
        // $time="23:59:59";
                
        }while($ticket->count()==0);
                            
        $date=$today->addDay(-1)->toDateString();
        
        // if($ticket->isEmpty()){
        //     return null;
        // }
        $dateTime['date']=$date;
        $dateTime['time']=$ticket[0]->ticket_time;

        return $dateTime;
    }




    public function notification($tokenList, $title,$message)
    {
        $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
        // $token=$token;

        $notification = [
            'title' => $title,
            'text'=>$message,
            'sound' => true,
        ];
        
        $extraNotificationData = ["message" => $notification];

        $fcmNotification = [
            'registration_ids' => $tokenList, //multple token array
            // 'to'        => $token, //single token
            'notification' => $notification,
            'data' => $extraNotificationData
        ];

        $headers = [
            'Authorization: key=AAAAqniSUAM:APA91bHYc-oz6mYrSbCvsksumemxON1_JDwqYhyNsehcvArYKTxTrbiOJAzljuwv4OdbX2T76w6wU8zw1py46pl3vP-Iszz2paWb8BPTqs_fvFv_yp00FFAGyxFOM2UE1En_-BzeEJHn',
            'Content-Type: application/json'
        ];


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$fcmUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
        $result = curl_exec($ch);
        curl_close($ch);

        return true;
    }
}
