<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\UserController;
use DateTime;
use App\Helpers\ApiHelper;

use App\Models\Tests;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MyTickets;
use App\User;

class notifier extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'minute:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dt = new Carbon();
        $time=$dt->second(0)->toTimeString();
        $test=Tests::where('time',$dt)->where('status',1)->first();

        if($test){
            
        $tokens=$this->getTicketUser($test->sendto);

        if($tokens){
            $this->notification($tokens,$test->title,$test->message);
        }
        }
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
