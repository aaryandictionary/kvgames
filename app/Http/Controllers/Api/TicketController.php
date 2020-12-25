<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Helpers\ApiHelper;
use App\Models\MyTickets;
use App\Models\TicketCategory;
use App\Models\TicketCategoryChange;
use App\Models\Transactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TicketController extends Controller
{
    public function changeTicket(Request $request)
    {

        $rules = [
            'change_for_date' => 'required',
            'change_for_time' => 'required',
            'tup_1' => 'required',
            'tup_2' => 'required',
            'tup_3' => 'required',
            'tup_4' => 'required',
            'status' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $response = ApiHelper::createAPIResponse(true, 400, $validator->errors(), null);
            return response()->json($response, 400);
        }

        $ticketChangeAdded = TicketCategoryChange::create($request->all());

        $response = ApiHelper::createAPIResponse(false, 200, "Ticket changed successfully", $ticketChangeAdded);
        return response()->json($response, 200);
    }

    public function getTimesForDate($date)
    {
        $times = MyTickets::where('my_ticket_date', '=', $date)
            ->distinct()
            ->pluck('my_ticket_time');

        $response = ApiHelper::createAPIResponse(false, 200, "", $times);
        return response()->json($response, 200);
    }

    public function getPricesForTime($date,$time){
        if($time=="ALL"){
            $prices=MyTickets::where('my_ticket_date','=',$date)
                                ->distinct()
                                ->pluck('ticket_unit_price');
        }else{
            $prices=MyTickets::where('my_ticket_date','=',$date)
                                ->where('my_ticket_time','=',$time)
                                ->distinct()
                                ->pluck('ticket_unit_price');
        }
        $response = ApiHelper::createAPIResponse(false, 200, "", $prices);
        return response()->json($response, 200);
    }

    //Get Master game timings

    public function getMasterGameTimings(){
        $times=TicketCategory::orderBy('ticket_time','ASC')
                                ->pluck('ticket_time');
        
        $response = ApiHelper::createAPIResponse(false, 200, "", $times);
        return response()->json($response, 200);
    }

    //Get Master Game timing Details

    public function getMasterGameDetails($time){
        $ticket=TicketCategory::where('ticket_time',$time)
                                    ->select('ticket_time','tup_1','tup_2','tup_3','tup_4','double_game')
                                    ->addSelect(DB::raw('is_enabled as status'))
                                    ->first();

        $response = ApiHelper::createAPIResponse(false, 200, "", $ticket);
        return response()->json($response, 200);
    }

    public function updateMasterGameDetails(Request $request){
        $ticket=TicketCategory::where('ticket_time',$request->change_for_time)
                                ->first();

        if($ticket){
            $ticket->tup_1=$request->tup_1;
            $ticket->tup_2=$request->tup_2;
            $ticket->tup_3=$request->tup_3;
            $ticket->tup_4=$request->tup_4;
            $ticket->is_enabled=$request->status;
            $ticket->double_game=$request->double_game;

            $ticket->save();

            $response = ApiHelper::createAPIResponse(false, 200, "", $ticket);
            return response()->json($response, 200);
        }else{
            $response = ApiHelper::createAPIResponse(false, 400, "", $ticket);
            return response()->json($response, 200);
        }
    }


    public function getGameTimings($date)
    {
        $today = new Carbon();
        $time = $today->toTimeString();

        $removeTicketDay1 = DB::table('ticket_category_changes')
            ->where('change_for_date', '=', $date)
            // ->where('change_for_time','>',$time)
            ->pluck('change_for_time');

        $addTicketDay1 = DB::table('ticket_category_changes')
            ->where('change_for_date', '=', $date)
            ->where('status', '=', 1)
            // ->where('change_for_time','>',$time)
            ->select('change_for_time');

        $ticketDay1 = DB::table('ticket_category')
            // ->where('is_enabled', 1)
            ->whereNotIn('ticket_time', $removeTicketDay1)
            // ->where('ticket_time','>',$time)
            ->union($addTicketDay1)
            ->orderBy('ticket_time', 'ASC')
            ->pluck('ticket_time');
        // ->get();

        $response = ApiHelper::createAPIResponse(false, 200, "Ticket changed successfully", $ticketDay1);
        return response()->json($response, 200);
    }


    public function getTicketsDT($date, $time,$price)
    {
        if ($time == "ALL") {
            if($price=="ALL"){
                $tickets = DB::table('my_tickets')->where('my_tickets.my_ticket_date', '=', $date)
                ->join('users', 'users.id', '=', 'my_tickets.user_id')
                ->orderBy('my_tickets.ticket_unit_price', 'DESC')
                ->select('users.name', 'users.phone', 'users.id', 'my_tickets.my_ticket_date', 'my_tickets.my_ticket_time', 'my_tickets.ticket_unit_price')
                ->get();
            }else{
                $tickets = DB::table('my_tickets')->where('my_tickets.my_ticket_date', '=', $date)
                ->where('my_tickets.ticket_unit_price','=',$price)
                ->join('users', 'users.id', '=', 'my_tickets.user_id')
                ->orderBy('my_tickets.ticket_unit_price', 'DESC')
                ->select('users.name', 'users.phone', 'users.id', 'my_tickets.my_ticket_date', 'my_tickets.my_ticket_time', 'my_tickets.ticket_unit_price')
                ->get();
            }
            
        } else {
            if($price=="ALL"){
                $tickets = DB::table('my_tickets')->where('my_tickets.my_ticket_date', '=', $date)
                ->where('my_tickets.my_ticket_time', '=', $time)
                ->join('users', 'users.id', '=', 'my_tickets.user_id')
                ->orderBy('my_tickets.ticket_unit_price', 'DESC')
                ->select('users.name', 'users.phone', 'users.id', 'my_tickets.my_ticket_date', 'my_tickets.my_ticket_time', 'my_tickets.ticket_unit_price')
                ->get();
            }else{
                $tickets = DB::table('my_tickets')->where('my_tickets.my_ticket_date', '=', $date)
                ->where('my_tickets.my_ticket_time', '=', $time)
                ->where('my_tickets.ticket_unit_price','=',$price)
                ->join('users', 'users.id', '=', 'my_tickets.user_id')
                ->orderBy('my_tickets.ticket_unit_price', 'DESC')
                ->select('users.name', 'users.phone', 'users.id', 'my_tickets.my_ticket_date', 'my_tickets.my_ticket_time', 'my_tickets.ticket_unit_price')
                ->get();
            }
            
        }


        $response = ApiHelper::createAPIResponse(false, 200, "", $tickets);
        return response()->json($response, 200);
    }

    //Get Admin Game with date and time

    public function getGameAdmin($date, $time)
    {
        $ticket = DB::table('ticket_category_changes')
            ->where('change_for_date', '=', $date)
            ->where('change_for_time', '=', $time)
            ->select('change_for_time','tup_1','tup_2','tup_3','tup_4','double_game','status')
            ->first();

        if (!$ticket) {
            $ticket = DB::table('ticket_category')
                            ->select('ticket_time','tup_1','tup_2','tup_3','tup_4','double_game')
                            ->addSelect(DB::raw('is_enabled as status'))
                            ->where('ticket_time','=',$time)
                            ->first();
        }

        $response = ApiHelper::createAPIResponse(false, 200, "", $ticket);
        return response()->json($response, 200);
    }


    //Update Main Game

    public function updateGameAdmin(Request $request){
        $date=$request->change_for_date;
        $time=$request->change_for_time;

        $ticket = TicketCategoryChange::where('change_for_date', '=', $date)
        ->where('change_for_time', '=', $time)
        ->first();

        if($ticket){
            $ticket->tup_1=$request->tup_1;
            $ticket->tup_2=$request->tup_2;
            $ticket->tup_3=$request->tup_3;
            $ticket->tup_4=$request->tup_4;
            $ticket->status=$request->status;
            $ticket->double_game=$request->double_game;

            $ticket->save();
        }else{
           $ticket= TicketCategoryChange::create($request->all());
        }
        $response = ApiHelper::createAPIResponse(false, 200, "", $ticket);
        return response()->json($response, 200);
    }


    //Add Main Game

    public function addMainGame(Request $request){
        $ticket = DB::table('ticket_category')
                            ->select('ticket_time','tup_1','tup_2','tup_3','tup_4','double_game')
                            ->addSelect(DB::raw('is_enabled as status'))
                            ->where('ticket_time','=',$request->ticket_time)
                            ->first();

        if($ticket){
            $response = ApiHelper::createAPIResponse(false, 400, "", $ticket);
        return response()->json($response, 200);
        }

        $ticket=TicketCategory::create($request->all());

        $response = ApiHelper::createAPIResponse(false, 200, "", $ticket);
        return response()->json($response, 200);
    }


    //Delete main game by time

    public function deleteMainGame($time){
        $ticket = DB::table('ticket_category')
                            ->where('ticket_time','=',$time)
                            ->delete();
        $response = ApiHelper::createAPIResponse(false, 200, "", null);
        return response()->json($response, 200);
    }

    //Delete game change with date and time

    public function deleteGameChange($date,$time){
        $ticket = DB::table('ticket_category_changes')
            ->where('change_for_date', '=', $date)
            ->where('change_for_time', '=', $time)
            ->delete();

        $response = ApiHelper::createAPIResponse(false, 200, "", null);
        return response()->json($response, 200);
    }

    //Delete game completly with time

    public function deleteGameMainandChange($time){
        $ticket = DB::table('ticket_category')
                            ->where('ticket_time','=',$time)
                            ->delete();
    $ticket = DB::table('ticket_category_changes')
                            ->where('change_for_time', '=', $time)
                            ->delete();
    $response = ApiHelper::createAPIResponse(false, 200, "", null);
    return response()->json($response, 200);
    }


//Get transactions

    public function getTransactions($status){
        if($status=="All"){
            $txns=DB::table('transactions')
            ->leftJoin('users','users.id','transactions.user_id')
            ->whereIn('txn_type',array('PAYTM','WITHDRAW'))
            ->select('transactions.*','users.phone')
            ->limit(100)
            ->orderBy('updated_at','DESC')
            ->get();
        }else if($status=="Pending"){
            $txns=DB::table('transactions')
                    ->leftJoin('users','users.id','transactions.user_id')
                    ->where('txn_status','=','PENDING')
                    ->whereIn('txn_type',array('PAYTM','WITHDRAW'))
                    ->select('transactions.*','users.phone')
                    ->orderBy('updated_at','DESC')
                    ->get();
        }else if($status=="Failed"){
            $txns=DB::table('transactions')
            ->leftJoin('users','users.id','transactions.user_id') 
            ->where('txn_status','=','FAILURE')
            ->whereIn('txn_type',array('PAYTM','WITHDRAW'))
            ->select('transactions.*','users.phone')
            ->limit(100)
            ->orderBy('updated_at','DESC')
            ->get();
        }else if($status=="Received"){
            $txns=DB::table('transactions')
                    ->leftJoin('users','users.id','transactions.user_id')
                    ->whereIn('txn_type',array('PAYTM'))
                    ->select('transactions.*','users.phone')
                    ->limit(100)
                    ->orderBy('updated_at','DESC')
                    ->get();
        }else if($status=="Paid"){
            $txns=DB::table('transactions')
            ->leftJoin('users','users.id','transactions.user_id')
            ->whereIn('txn_type',array('WITHDRAW'))
            ->select('transactions.*','users.phone')
            ->orderBy('updated_at','DESC')
            ->limit(100)
            ->get();
        }
        
        $response=ApiHelper::createAPIResponse(false,200,"",$txns);
        return response()->json($response,200);
    }


    //Get total transactions

    public function getTotals(){
        $received=DB::table('transactions')
                ->where('txn_type','=','PAYTM')
                ->where('txn_status','=','SUCCESS')
                ->sum('amount');

        $paid=DB::table('transactions')
                ->where('txn_type','=','WITHDRAW')
                ->where('txn_status','=','SUCCESS')
                ->sum('amount');

        $result=[];
        $result['received']=$received;
        $result['paid']=$paid;

        $response=ApiHelper::createAPIResponse(false,200,"",$result);
        return response()->json($response,200);
    }
}
