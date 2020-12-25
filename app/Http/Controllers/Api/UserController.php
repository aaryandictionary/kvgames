<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiHelper;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\GameResult;
use App\Models\MyTickets;
use App\Models\GameJoins;
use App\Models\GameResponse;
use App\Models\Referrals;
use App\Models\TicketCategory;
use App\Models\Transactions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Facades\Validator;
use App\User;
use Exception;

class UserController extends Controller
{

    public function register(Request $request) { 
        $validator = Validator::make($request->all(), [ 
            'name' => 'required', 
            'phone' => 'required|unique:users', 
            'gms_token'=>'required',
            'password' => 'required', 
        ]);

        $userCheck=User::where('phone','=',$request->phone)->first();

        if($userCheck){
            $userCheck['token']=$userCheck->createToken('MyApp')->accessToken; 

            $response=ApiHelper::createAPIResponse(false,201,"User already exist",$userCheck);
            return response()->json($response, 201);  
        }

        $referralUser=User::where('referral_code','=',$request->referral_user)->first();

        if($referralUser){
            $request['balance']=5;
        }



        do{
            $code=$this->getCode(6);
            $checkCode=User::where('referral_code','=',$code)->first();
        }while($checkCode);

        $request['referral_code']=strtoupper($code);

        if ($validator->fails()) { 
            $response=ApiHelper::createAPIResponse(true,401,$validator->errors(),null);
            return response()->json($response, 401);            
        }
        $input = $request->all(); 
        $input['password'] = bcrypt($input['password']); 
        $user = User::create($input); 

        if($referralUser){
            $referral=[];
            $referral['referral_user_code']=$referralUser['referral_code'];
            $referral['new_user_code']=$user['referral_code'];
            $referral['referral_user_id']=$referralUser['id'];
            $referral['new_user_id']=$user['id'];

            Referrals::create($referral);
        }

        $success=$user;
        $success['token'] =  $user->createToken('MyApp')-> accessToken; 
        $response=ApiHelper::createAPIResponse(false,200,"User created successfully",$success);
        return response()->json($response, 200); 
    }


    function getCode($n) { 
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; 
        $randomString = ''; 
      
        for ($i = 0; $i < $n; $i++) { 
            $index = rand(0, strlen($characters) - 1); 
            $randomString .= $characters[$index]; 
        } 
      
        return $randomString; 
    } 

    public function login(){ 
        if(Auth::attempt(['phone' => request('phone'), 'password' => request('password')])){ 
            $user = User::where('phone',request('phone'))->first();

            if($user){
                $user->gms_token=request('gms_token');
                $user->save();
            }

            if($user->status==0){
                $response=ApiHelper::createAPIResponse(false,201,"User blocked",null);
            return response()->json($response, 201);  
            }

            $success=$user;
            $success['token'] =  $user->createToken('MyApp')->accessToken; 
            $response=ApiHelper::createAPIResponse(false,200,"Login successful",$success);
            return response()->json($response, 200); 
        } 
        else{ 
            $response=ApiHelper::createAPIResponse(true,401,"Unauthorised",null);
            return response()->json($response, 401); 
        } 
    }


    public function loginAdmin(Request $request){
        $admin=Admin::where('phone',$request->phone)
                        ->where('password',$request->password)
                        ->where('secondPassword',$request->secondPassword)
                        ->first();
        if($admin){
            $response=ApiHelper::createAPIResponse(false,200,"Login successful",null);
            return response()->json($response, 200); 
        }else{
            $response=ApiHelper::createAPIResponse(true,400,"Unauthorised",null);
            return response()->json($response, 200); 
        }
    }

    public function changeUserStatus($userId){
        $user=User::find($userId)->first();

        if($user->status==0){
            $user->status=1;
        }else{
            $user->status=0;
        }
        $user->save();

        $response=ApiHelper::createAPIResponse(false,200,"",$user);
            return response()->json($response, 200); 
    }


    public function update_profile_image(Request $request){
        if ($request->hasFile('profile')) {

       
            $image = $request->file('profile');
            $name = time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/images');
            $image->move($destinationPath, $name);

            $path=url('').'/images/'.$name;
            

        $user = User::where('id', '=', $request->id)->first();
        $count= $user->update(['image_url'=>$path]);

        if($count==1){
            $response=ApiHelper::createAPIResponse(false,200,"Profile updated successfully",$user);
            return response()->json($response,200);
           }

    }
    }

    public function updateProfile(Request $request){
        $name=$request->name;
        $email=$request->email;
        $user = User::where('id', '=', $request->id)->first();
        
        if ($request->hasFile('profile')) {

       
            $image = $request->file('profile');
            $name = time().'.'.$image->getClientOriginalExtension();
            $destinationPath = public_path('/images');
            $image->move($destinationPath, $name);

            $path=url('').'/images/'.$name;
            

            $user->update(['image_url'=>$path]);

            $response=ApiHelper::createAPIResponse(false,200,"Profile updated successfully",$user);
            return response()->json($response,200);
        }

        $user->update(['name'=>$name,'email'=>$email]);

        $response=ApiHelper::createAPIResponse(false,200,"Profile updated successfully",$user);
        return response()->json($response,200);
    }


    public function getickets($userId){
        $today=new Carbon();
        $date=$today->toDateString();
        $time=$today->addMinutes(15)->toTimeString();

        $resultTicket=[];

        $i=0;

        do{

        $remove=DB::table('ticket_category_changes') 
                                    ->where('change_for_date','=',$date)
                                    ->where('change_for_time','>',$time)
                                    ->pluck('change_for_time');

        $add=DB::table('ticket_category_changes')
                                    ->where('change_for_date','=',$date)
                                    ->where('status','=',1)
                                    ->where('change_for_time','>',$time)
                                    ->select('change_for_time','tup_1','tup_2','tup_3','tup_4','double_game');
                                    
        $ticket=DB::table('ticket_category')    
                                    ->where('is_enabled',1)
                                    ->whereNotIn('ticket_time',$remove)
                                    ->select('ticket_time','tup_1','tup_2','tup_3','tup_4','double_game')
                                    ->where('ticket_time','>',$time)
                                    ->union($add)
                                    ->orderBy('ticket_time','ASC')
                                    ->get();

        $myticket=DB::table('my_tickets')
                        ->where('my_ticket_date','=',$date)
                        ->where('user_id','=',$userId)
                        ->select(DB::raw("my_ticket_time,ticket_unit_price,COUNT(my_ticket_id) AS ticket_count,my_ticket_date"))
                        ->groupBy('my_ticket_time','ticket_unit_price','my_ticket_date')
                        ->get();

        if(!$ticket->isEmpty()){
            $resultTicket[$i]['date']=$date;
            $resultTicket[$i]['ticket']=$ticket;
            $resultTicket[$i]['myTickets']=$myticket;
            $i++;
        }

        $date=$today->addDay(1)->toDateString();
        $time="00:00:00";
                
        }while($i<2);

        $response=ApiHelper::createAPIResponse(false,200,"",$resultTicket);
        return response()->json($response,200);
    }


    // public function getickets($userId){

    //     $today=new Carbon();

    //     $dates=[];
    //     $dates[0]=$today->toDateString();
    //     $dates[1]=$today->addDay(1)->toDateString();

    //     $time=$today->addMinutes(15)->toTimeString();

    //     $removeTicketDay1=DB::table('ticket_category_changes') 
    //                                 ->where('change_for_date','=',$dates[0])
    //                                 ->where('change_for_time','>',$time)
    //                                 ->select('change_for_time')
    //                                 ->get()->toArray();

    //         $newRTD1=[];
    //         $i=0;
    //         foreach($removeTicketDay1 as $rtd1){
    //             $newRTD1[$i]=$rtd1->change_for_time;
    //             $i++;
    //         }

    //     $addTicketDay1=DB::table('ticket_category_changes')
    //                             ->where('change_for_date','=',$dates[0])
    //                             ->where('status','=',1)
    //                             ->where('change_for_time','>',$time)
    //                             ->select('change_for_time','tup_1','tup_2','tup_3','tup_4','double_game');

    //     $ticketDay1=DB::table('ticket_category')    
    //                         ->where('is_enabled',1)
    //                         ->whereNotIn('ticket_time',$newRTD1)
    //                         ->select('ticket_time','tup_1','tup_2','tup_3','tup_4','double_game')
    //                         ->where('ticket_time','>',$time)
    //                         ->union($addTicketDay1)
    //                         ->orderBy('ticket_time','ASC')
    //                         ->get();


    //     $removeTicketDay2=DB::table('ticket_category_changes') 
    //                         ->where('change_for_date','=',$dates[1])
    //                         ->select('change_for_time')
    //                         ->get();

    //     $newRTD2=[];
    //     $i=0;
    //     foreach($removeTicketDay2 as $rtd2){
    //         $newRTD2[$i]=$rtd2->change_for_time;
    //         $i++;
    //     }

    //     $addTicketDay2=DB::table('ticket_category_changes')
    //                     ->where('change_for_date','=',$dates[1])
    //                     ->where('status','=',1)
    //                     ->select('change_for_time','tup_1','tup_2','tup_3','tup_4','double_game');

    //     $ticketDay2=DB::table('ticket_category')    
    //                 ->where('is_enabled',1)
    //                 ->whereNotIn('ticket_time',(array)$newRTD2)
    //                 ->select('ticket_time','tup_1','tup_2','tup_3','tup_4','double_game')
    //                 ->union($addTicketDay2)
    //                 ->orderBy('ticket_time','ASC')
    //                 ->get();

    //     $myticketDay1=DB::table('my_tickets')
    //                     ->where('my_ticket_date','=',$dates[0])
    //                     ->where('user_id','=',$userId)
    //                     ->select(DB::raw("my_ticket_time,ticket_unit_price,COUNT(my_ticket_id) AS ticket_count,my_ticket_date"))
    //                     ->groupBy('my_ticket_time','ticket_unit_price','my_ticket_date')
    //                     ->get();

    //     $myticketDay2=DB::table('my_tickets')
    //                     ->where('my_ticket_date','=',$dates[1])
    //                     ->where('user_id','=',$userId)
    //                     ->select(DB::raw("my_ticket_time,ticket_unit_price,COUNT(my_ticket_id) AS ticket_count,my_ticket_date"))
    //                     ->groupBy('my_ticket_time','ticket_unit_price','my_ticket_date')
    //                     ->get();

    //     $resultTicket=[];
    //     $resultTicket[0]['date']=$dates[0];
    //     $resultTicket[0]['ticket']=$ticketDay1;
    //     $resultTicket[0]['myTickets']=$myticketDay1;
    //     $resultTicket[1]['date']=$dates[1];
    //     $resultTicket[1]['ticket']=$ticketDay2;
    //     $resultTicket[1]['myTickets']=$myticketDay2;

    //     // $ticketResult=[$dates[0]=>$ticketDay1,$dates[1]=>$ticketDay2];

    //     $response=ApiHelper::createAPIResponse(false,200,"",$resultTicket);
    //     return response()->json($response,200);
    // }


    public function myTickets($userId){
        $today=new Carbon();

        $dates=[];
        $dates[0]=$today->toDateString();
        $dates[1]=$today->addDay(1)->toDateString();

        $time=$today->toTimeString();

        $myTickets1=DB::table('my_tickets')
                    ->where('my_ticket_date',$dates[0])
                    ->where('user_id','=',$userId)
                    ->where('my_ticket_time','>',$time)
                    ->select('*');

        $myTickets=DB::table('my_tickets')
                    ->where('my_ticket_date',$dates[1])
                    ->where('user_id','=',$userId)
                    ->select('*')
                    ->union($myTickets1)
                    ->orderBy('my_ticket_date','ASC')
                    ->orderBy('my_ticket_time','ASC')
                    ->orderBy('ticket_unit_price','ASC')
                    ->get();

        $response=ApiHelper::createAPIResponse(false,200,"",$myTickets);
        return response()->json($response,200);
    }


    public function createTicket(Request $request){
        $rules =[
            'transactions'=>'required',
            'tickets'=>'required',
        ];

        $validator=Validator::make($request->all(),$rules);
        if($validator->fails()){
            $response=ApiHelper::createAPIResponse(true,400,$validator->errors(),null);
            return response()->json($response,400);
        }
        
        $tickets=$request['tickets'];

        $transactions=$request['transactions'];
        $today=new Carbon();
        $time=$today->addMinutes(15)->toTimeString();
        $dates=$today->toDateString();

        $checkTxn=Transactions::where('id','=',$request['transactions'][0]['user_id'])->first();

        if(!$checkTxn){
            $referral=Referrals::where('new_user_id','=',$request['transactions'][0]['user_id'])->first();
            
            if($referral){
                $referralId=$referral->referral_user_id;

            $refUser=User::where('id','=',$referralId)->first();

            $refUser->update(['balance'=>$refUser->balance+5]);
            }
            
        }

        $amount=0;
        foreach($transactions as $transaction){
            if($transaction['amount']>0){
                $amount=$amount+$transaction['amount'];
                $transactionAdded=Transactions::create($transaction);
            }
        }

        $user=User::where('id','=',$request['transactions'][0]['user_id'])->first();

        $walletBalance=$user->balance;

        $walletBalance=$walletBalance+$amount;

        $user->update(['balance'=>$walletBalance]);

        foreach($tickets as $tic){
            if($dates==$tic['my_ticket_date']&&$time>$tic['my_ticket_time']){
                $response=ApiHelper::createAPIResponse(true,300,"Game already started. Try again",null);
                return response()->json($response,200);
            }
        }

        DB::beginTransaction();

        try{


        foreach($tickets as $tic){
        $ticketAdded=MyTickets::create($tic);
        }

        $amount=0;
        foreach($transactions as $transaction){
            if($transaction['amount']<0){
                $amount=$amount+$transaction['amount'];
                $transactionAdded=Transactions::create($transaction);
            }
        }

        
        $walletBalance=$user->balance;

        $walletBalance=$walletBalance+$amount;

        if($walletBalance<0){
            $response=ApiHelper::createAPIResponse(true,101,"Insufficient balance",null);
            return response()->json($response,200);
        }

        $user->update(['balance'=>$walletBalance]);

        DB::commit();

        $response=ApiHelper::createAPIResponse(false,200,"",null);
        return response()->json($response,200);

    }catch(Exception $e){
        DB::rollBack();
        $response=ApiHelper::createAPIResponse(false,301,"",null);
        return response()->json($response,200);
    }
        
    }


    public function shuffleTicket(Request $request){
        $rules =[
            'my_ticket_id'=>'required',
            'ticket_combo'=>'required',
        ];

        $validator=Validator::make($request->all(),$rules);
        if($validator->fails()){
            $response=ApiHelper::createAPIResponse(true,400,$validator->errors(),null);
            return response()->json($response,400);
        }

        $ticket=MyTickets::where('my_ticket_id','=',$request->my_ticket_id)->first();

        $count=$ticket->update(['ticket_combo'=>$request->ticket_combo]);

        if($count==1){
            $response=ApiHelper::createAPIResponse(false,200,"",null);
            return response()->json($response,200);
        }else{
            $response=ApiHelper::createAPIResponse(true,200,"Something went wrong",null);
        return response()->json($response,200);
        }
    }

    

    public function createTransaction(Request $request){
        $rules =[
            'bank_txn_id'=>'required',
            'txn_id'=>'required',
            'order_id'=>'required',
            'user_id'=>'required',
            'amount'=>'required',
        ];

        $validator=Validator::make($request->all(),$rules);
        if($validator->fails()){
            $response=ApiHelper::createAPIResponse(true,400,$validator->errors(),null);
            return response()->json($response,400);
        }

        $checkTxn=Transactions::where('user_id','=',$request->user_id)->first();

        if(!$checkTxn){
            $response=ApiHelper::createAPIResponse(true,405,"Amount cannot be deducted",null);
            return response()->json($response,200);
        }

        $user=User::where('id','=',$request->user_id)->first();

        $walletBalance=$user->balance;

        $amount=$request->amount;

        if($walletBalance-$amount<0){
            $response=ApiHelper::createAPIResponse(true,400,"Insufficient wallet balance",null);
            return response()->json($response,200);  
        }

        $txn=Transactions::where('order_id','=',$request->order_id)->first();

        if(!$txn){
            $transactionAdded=Transactions::create($request->all());


            if($request->txn_status=="SUCCESS"||$request->txn_status=="PENDING"){
            $walletBalance=$walletBalance-$amount;
            $count=$user->update(['balance'=>$walletBalance]);
            }
        }
            $response=ApiHelper::createAPIResponse(false,200,"",null);
            return response()->json($response,200);
        
    }


    public function updateTransactions(Request $request){
        $rules =[
            'bank_txn_id'=>'required',
            'txn_id'=>'required',
            'order_id'=>'required',
            'user_id'=>'required',
            'txn_status'=>'required',
        ];

        $validator=Validator::make($request->all(),$rules);
        if($validator->fails()){
            $response=ApiHelper::createAPIResponse(true,400,$validator->errors(),null);
            return response()->json($response,400);
        }

        $user=User::where('id','=',$request->user_id)->first();

        $walletBalance=$user->balance;
        
        $txn=Transactions::where('user_id','=',$request->user_id)
                            ->where('order_id','=',$request->order_id)
                            ->first();


        if($txn->txn_status=="PENDING"&&$request->txn_status!="PENDING"){
            $amount=$txn->amount;

            if(($request->txn_status=="FAILURE") && ($request->txn_type=="WITHDRAW")){
                $walletBalance=$walletBalance+$amount;
                $count=$user->update(['balance'=>$walletBalance]);
            }
    
            $txn->update(['bank_txn_id'=>$request->bank_txn_id,'txn_id'=>$request->txn_id,'txn_status'=>$request->txn_status]);    
        }

        
        $response=ApiHelper::createAPIResponse(false,200,"",null);
        return response()->json($response,200);

    }


    public function getNextGameTime($type){
        $today=new Carbon();

        $date=$today->toDateString();
        $timeNow=$today->toTimeString();
        if($type==0){
            $time=$today->addMinutes(-10)->toTimeString();
        }else{
            $time=$today->toTimeString();
        }

        $i=0;

        do{
            $i++;
        $minus=DB::table('ticket_category_changes')
            ->where('change_for_date','=',$date)
            ->where('change_for_time','>',$time)
            ->pluck('change_for_time');

        $add=DB::table('ticket_category_changes')
                        ->select(DB::raw('change_for_time AS ticket_time,double_game'))
                        ->where('change_for_time','>',$time)
                        ->where('change_for_date','=',$date)
                        ->where('status','=',1);

        $ticket=DB::table('ticket_category')
                        ->whereNotIn('ticket_time',$minus)
                        ->where('is_enabled',1)
                        ->select('ticket_time','double_game')
                        ->where('ticket_time','>',$time)
                        ->union($add)
                        ->orderBy('ticket_time')
                        ->get();

        $date=$today->addDay(1)->toDateString();
        $time="00:00:00";

        }while($ticket->isEmpty());
            
        //if($i==1){
        $date=$today->addDay(-1)->toDateString();
        //}

        $nextTicketTime=[];
        $nextTicketTime['next_time']=$ticket[0]->ticket_time;
        $nextTicketTime['double_game']=$ticket[0]->double_game;
        $nextTicketTime['time']=$timeNow;
        $nextTicketTime['date']=$date;

        $response=ApiHelper::createAPIResponse(false,200,"",$nextTicketTime);
        return response()->json($response,200);
    }


    // public function getAllUsers(){
    //     $users=DB::table('users')
    //                     ->join('transactions','transactions.user_id','=','users.id')
    //                     ->select(DB::raw("COALESCE(SUM(amount),0)AS total"))
    //                     ->groupBy('users.name','users.phone','users.image_url')
    //                     ->get();
    //     $response=ApiHelper::createAPIResponse(false,200,"",$users);
    //     return response()->json($response,200);
    // }

    public function changeBalance($id,$balance){
        $user=User::find($id);

        $user->balance=$balance;
        $user->save();

        $response=ApiHelper::createAPIResponse(false,200,"",$user);
        return response()->json($response,200);
    }

    public function getAllUsers(){
        $users=DB::table('users')
                        ->select('id','name','phone','email','balance','image_url','status')
                        ->orderBy('balance','DESC')
                        ->get();
        $response=ApiHelper::createAPIResponse(false,200,"",$users);
        return response()->json($response,200);
    }

    public function createResult(Request $request){
        $date=$request->game_date;
        $time=$request->game_time;

        $result=DB::table('game_results')
                    ->where('game_date','=',$date)
                    ->where('game_time','=',$time)
                    ->first();

        if(!$result){

            $numbers=range(1,90);
            // for($i=1;$i<=90;$i++){
            //     $numbers[$i-1]=$i;
            // }
            shuffle($numbers);
            shuffle($numbers);
            shuffle($numbers);

            $comboString="";
            for($i=0;$i<90;$i++){
                $comboString=$comboString.$numbers[$i].",";
            }
            
            $comboNewString=substr($comboString, 0, -1); 

            $record=[];
            $record['game_date']=$date;
            $record['game_time']=$time;
            $record['result_combo']=$comboNewString;

            $result=GameResult::create($record);
        }

        $response=ApiHelper::createAPIResponse(false,200,"",$result);
        return response()->json($response,200);
    }

    public function joinGame(Request $request){

        $rules =[
            'user_id'=>'required',
            'firebase_uid'=>'required',
            'ticket_count'=>'required',
            'game_time'=>'required',
        ];

        $validator=Validator::make($request->all(),$rules);
        if($validator->fails()){
            $response=ApiHelper::createAPIResponse(true,400,$validator->errors(),null);
            return response()->json($response,400);
        }


        $today=new Carbon();

        $nextGameTime=$this->findNextGame();

        if(!$nextGameTime){
            $response=ApiHelper::createAPIResponse(false,-1,"Cannot join game",null);
            return response()->json($response,200);
        }

        // $date=$today->toDateString();
        // $date=$request->game_date;
        $date=$nextGameTime['date'];
        $timeNow=$today->toTimeString();
        // $time=$today->addMinutes(1)->toTimeString();


        // $ticketTime=$this->findNextGame();
        // $ticketTime=$request->game_time;
        $ticketTime=$nextGameTime['time'];

        $gameJoinData=$request->all();

        $gameJoinData['game_date']=$date;
        $gameJoinData['game_time']=$ticketTime;

        $myTickets=DB::table('my_tickets')
                        ->where('user_id','=',$request->user_id)
                        ->where('my_ticket_date','=',$date)
                        ->where('my_ticket_time','=',$ticketTime)
                        ->first();

        if($myTickets){


        // if($ticketTime==null){
        //     $response=ApiHelper::createAPIResponse(false,402,"Cannot join Game",null);
        //     return response()->json($response,402);
        // }

        $joinedGame=null;
        // if($timeNow>=$ticketTime&&$timeNow<=$time){
            $joinedGame=DB::table('game_joins')
                            ->where('user_id','=',$gameJoinData['user_id'])
                            ->where('game_date','=',$date)
                            ->where('game_time','=',$gameJoinData['game_time'])
                            ->first();
                            
            if($joinedGame){
            $response=ApiHelper::createAPIResponse(false,0,"Cannot join Game",null);
            return response()->json($response,200);
            }
            
            if($joinedGame==null){
                $request['join_time']=$timeNow;

                $joinedGame=GameJoins::create($gameJoinData);
            }

        // }

        

        $response=ApiHelper::createAPIResponse(false,1,"",$joinedGame);
        return response()->json($response,200);
    }else{
        $response=ApiHelper::createAPIResponse(false,-1,"Cannot join game",null);
        return response()->json($response,200);
    }
    }

    public function getTicketForNextGame($userId,$time,$date){
        $nextGameTime=$this->findNextGame();

        if($nextGameTime){
            $myTickets=DB::table('my_tickets')
                    ->leftjoin('prizes','my_tickets.ticket_unit_price','=','prizes.prize_unit_price')
                    ->where('prizes.is_active','=',1)
                    ->where('my_tickets.my_ticket_date','=',$nextGameTime['date'])
                    ->where('my_tickets.user_id','=',$userId)
                    ->where('my_tickets.my_ticket_time','=',$nextGameTime['time'])
                    ->orderBy('ticket_unit_price','ASC')
                    ->select('my_tickets.my_ticket_id','my_tickets.user_id','my_tickets.my_ticket_date','my_tickets.my_ticket_time','my_tickets.ticket_combo','my_tickets.ticket_unit_price','prizes.prize_count',
                    'prizes.ef_rate','prizes.li_rate','prizes.fh_rate')
                    ->get();
        }else{
            $myTickets=null;
        }

        $response=ApiHelper::createAPIResponse(false,200,"",$myTickets);
        return response()->json($response,200);
    }

//Function just to find the next game time --NO ROUTE AVALIABLE
public function findNextGame(){
    $today=new Carbon();

    $date=$today->toDateString();
    $timeFrom=$today->addMinutes(-10)->toTimeString();
    $timeTo=$today->addMinutes(10)->toTimeString();

    // echo $date.' '.$timeNow;

    $i=0;

    $ticket=null;

    do{
        $i++;
   
    $minus=DB::table('ticket_category_changes')
                    ->where('change_for_date','=',$date)
                    ->where('change_for_time','>',$timeFrom)
                    ->where('change_for_time','<',$timeTo)
                    ->pluck('change_for_time');

    $add=DB::table('ticket_category_changes')
                    ->select(DB::raw('change_for_time AS ticket_time'))
                    ->where('change_for_time','>',$timeFrom)
                    ->where('change_for_time','<',$timeTo)
                    ->where('change_for_date','=',$date)
                    ->where('status','=',1);

    $ticket=DB::table('ticket_category')
                    ->whereNotIn('ticket_time',$minus)
                    ->where('is_enabled',1)
                    ->select('ticket_time')
                    ->where('ticket_time','>',$timeFrom)
                    ->where('ticket_time','<',$timeTo)
                    ->union($add)
                    ->orderBy('ticket_time','ASC')
                    ->get();
                    
    $date=$today->addDay(1)->toDateString();
    // $timeFrom="00:00:00";
    // $timeTo="23:59:59";
    if($i==1&&$ticket->count()==0){
        return null;
    }
            
    }while($ticket->count()==0);
                        
    $date=$today->addDay(-1)->toDateString();
    
    // if($ticket->isEmpty()){
    //     return null;
    // }
    if($ticket){
        $dateTime['date']=$date;
        $dateTime['time']=$ticket[0]->ticket_time;    
    }else{
        $dateTime=null;
    }
    
    return $dateTime;
}


    public function saveResponse(Request $request){
        $rules =[
            'user_id'=>'required',
            'firebase_uid'=>'required',
            'ticket_id'=>'required',
            'join_id'=>'required',
            'win_amount'=>'required',
            'ticket_type'=>'required',//For Early Five Lines and Full House
        ];

        $validator=Validator::make($request->all(),$rules);
        if($validator->fails()){
            $response=ApiHelper::createAPIResponse(true,400,$validator->errors(),null);
            return response()->json($response,400);
        }

        $savedResponse=DB::table('game_responses')
                                ->where('user_id','=',$request->user_id)
                                ->where('join_id','=',$request->join_id)
                                ->where('ticket_id','=',$request->ticket_id)
                                ->where('ticket_type','=',$request->ticket_type)
                                ->first();

        if(!$savedResponse){
            $savedResponse=GameResponse::create($request->all());
            $user=User::where('id','=',$request->user_id)->first();
            $walletBalance=$user->balance;
            $walletBalance=$walletBalance+$request->win_amount;
            $user->update(['balance'=>$walletBalance]);
        }

        $response=ApiHelper::createAPIResponse(false,200,"",$savedResponse);
        return response()->json($response,200);
    }


    public function getLeaderboard($type){
        if($type==-1){
            $lastJoins=$this->getLastJoinId();


            $lastLeaderBoard=DB::table('game_responses')
                                ->rightJoin('users','game_responses.user_id','=','users.id')
                                ->whereIn('game_responses.join_id',$lastJoins)
                                ->select(DB::raw("sum(IF(game_responses.win_amount,game_responses.win_amount,0)) AS total"))
                                ->groupBy('users.id','users.name','users.image_url')
                                ->addSelect('users.name','users.image_url')
                                ->orderBy('total','DESC')
                                ->limit(100)
                                ->get();

            $response=ApiHelper::createAPIResponse(false,200,"",$lastLeaderBoard);
            return response()->json($response,200);      

        }else if($type==0){

            $now=Carbon::now();
               
            $then=Carbon::now()->subDays(7);

            $weeklyLeaderBoard=DB::table('game_responses')
                                ->rightJoin('users','game_responses.user_id','=','users.id')
                                ->select(DB::raw("sum(IF(game_responses.created_at<'".$now."' AND game_responses.created_at>'".$then."',game_responses.win_amount,0))AS total"))
                                ->groupBy('users.id','users.name','users.image_url')
                                ->addSelect('users.name','users.image_url')
                                ->orderBy('total','DESC')
                                ->limit(100)
                                ->get();

            $response=ApiHelper::createAPIResponse(false,200,"",$weeklyLeaderBoard);
            return response()->json($response,200);   


        }else if($type==1){

            $allTimeLeaderBoard=DB::table('game_responses')
                                ->rightJoin('users','game_responses.user_id','=','users.id')
                                ->select(DB::raw("sum(game_responses.win_amount)AS total"))
                                ->groupBy('users.id','users.name','users.image_url')
                                ->addSelect('users.name','users.image_url')
                                ->orderBy('total','DESC')
                                ->limit(100)
                                ->get();
        
            $response=ApiHelper::createAPIResponse(false,200,"",$allTimeLeaderBoard);
            return response()->json($response,200);
        }
    }


    public function getLastJoinId(){
        $today=new Carbon();

        $date=$today->toDateString();
        // $lastTime=DB::table('game_joins')
                        // ->where('game_date','=',$date)
            $lastTime=GameJoins::
                        select('game_time','game_date')
                        ->orderBy('game_date','DESC')
                        ->orderBy('game_time','DESC')
                        ->first();

        $lastJoins=DB::table('game_joins')
                        ->where('game_date','=',$lastTime->game_date)
                        ->where('game_time',$lastTime->game_time)
                        ->pluck('join_id');

        return $lastJoins;
    }


    public function getUserBalance($userId){
        $walletBalance=DB::table('users')
                            ->where('id','=',$userId)
                            ->select(DB::raw('balance AS amount,status'))
                            ->first();

        $response=ApiHelper::createAPIResponse(false,200,"",$walletBalance);
        return response()->json($response,200);
    }


    public function getPendingTxns($userId){
        $txn=Transactions::where('user_id','=',$userId)
                            ->where('txn_type','=','WITHDRAW')
                            ->where('txn_status','=','PENDING')
                            ->select('bank_txn_id','txn_id','order_id','user_id','txn_type','txn_status','amount')
                            ->first();
    $response=ApiHelper::createAPIResponse(false,200,"",$txn);
    return response()->json($response,200);
    }

    public function getTicketUser($type){
        $dateTime=$this->findNextGame();

        if($type==0){
            $tokens=DB::table('my_tickets')
            ->join('users','users.id','my_tickets.user_id')
            ->where('my_tickets.my_ticket_date',$dateTime['date'])
            ->where('my_tickets.my_ticket_time',$dateTime['time'])
            ->distinct()
            ->pluck('users.token');
        }else{
            $tokens=DB::table('users')
                    ->join('my_tickets','users.id','!=','my_tickets.user_id')
                    ->where('my_tickets.my_ticket_date','=',$dateTime['date'])
                    ->where('my_tickets.my_ticket_time','=',$dateTime['time'])
                    ->distinct()
                    ->pluck('users.token');
        }
        
        
        $response=ApiHelper::createAPIResponse(false,200,"",$tokens);
        return response()->json($response,200);
    }

}
