<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Helpers\ApiHelper;
use App\Models\MyTickets;
use App\Models\TicketCategory;
use App\Models\TicketCategoryChange;
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

        $response = ApiHelper::createAPIResponse(false, 200, "Ticket changed successfully", $times);
        return response()->json($response, 200);
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
            ->where('is_enabled', 1)
            ->whereNotIn('ticket_time', $removeTicketDay1)
            // ->where('ticket_time','>',$time)
            ->union($addTicketDay1)
            ->orderBy('ticket_time', 'ASC')
            ->pluck('ticket_time');
        // ->get();

        $response = ApiHelper::createAPIResponse(false, 200, "Ticket changed successfully", $ticketDay1);
        return response()->json($response, 200);
    }


    public function getTicketsDT($date, $time)
    {
        if ($time == "ALL") {
            $tickets = DB::table('my_tickets')->where('my_tickets.my_ticket_date', '=', $date)
                ->join('users', 'users.id', '=', 'my_tickets.user_id')
                ->orderBy('my_tickets.ticket_unit_price', 'DESC')
                ->select('users.name', 'users.phone', 'users.id', 'my_tickets.my_ticket_date', 'my_tickets.my_ticket_time', 'my_tickets.ticket_unit_price')
                ->get();
        } else {
            $tickets = DB::table('my_tickets')->where('my_tickets.my_ticket_date', '=', $date)
                ->where('my_tickets.my_ticket_time', '=', $time)
                ->join('users', 'users.id', '=', 'my_tickets.user_id')
                ->orderBy('my_tickets.ticket_unit_price', 'DESC')
                ->select('users.name', 'users.phone', 'users.id', 'my_tickets.my_ticket_date', 'my_tickets.my_ticket_time', 'my_tickets.ticket_unit_price')
                ->get();
        }


        $response = ApiHelper::createAPIResponse(false, 200, "", $tickets);
        return response()->json($response, 200);
    }


    public function getGameAdmin($date, $time)
    {
        $ticket = DB::table('ticket_category_changes')
            ->where('change_for_date', '=', $date)
            ->where('status', '=', 1)
            ->where('change_for_time', '=', $time)
            ->select('change_for_time','tup_1','tup_2','tup_3','tup_4','double_game')
            ->first();

        if (!$ticket) {
            $ticket = DB::table('ticket_category')
                            ->select('ticket_time','tup_1','tup_2','tup_3','tup_4','double_game')
                            ->where('is_enabled', 1)
                            ->where('ticket_time','=',$time)
                            ->first();
        }

        $response = ApiHelper::createAPIResponse(false, 200, "", $ticket);
        return response()->json($response, 200);
    }


    public function updateGameAdmin(Request $request){
        $date=$request->change_for_date;
        $time=$request->change_for_time;

        $ticket = DB::table('ticket_category_changes')
        ->where('change_for_date', '=', $date)
        ->where('status', '=', 1)
        ->where('change_for_time', '=', $time)
        ->select('change_for_time','tup_1','tup_2','tup_3','tup_4','double_game')
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

    public function addMainGame(Request $request){
        $ticket = DB::table('ticket_category')
                            ->select('ticket_time','tup_1','tup_2','tup_3','tup_4','double_game')
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

    public function deleteMainGame($time){
        $ticket = DB::table('ticket_category')
                            ->where('ticket_time','=',$time)
                            ->delete();
        $response = ApiHelper::createAPIResponse(false, 200, "", null);
        return response()->json($response, 200);
    }

    public function deleteGameChange($date,$time){
        $ticket = DB::table('ticket_category_changes')
            ->where('change_for_date', '=', $date)
            ->where('change_for_time', '=', $time)
            ->delete();

        $response = ApiHelper::createAPIResponse(false, 200, "", null);
        return response()->json($response, 200);
    }

    public function getTransactions($status){
        if($status=="ALL"){
            $txns=DB::table('transactions')
            ->whereIn('txn_type',['PAYTM','WITHDRAW'])
            ->select('*')
            ->orderBy('created_at')
            ->get();
        }else{
            $txns=DB::table('transactions')
                    ->where('txn_status','=',$status)
                    ->whereIn('txn_type',['PAYTM','WITHDRAW'])
                    ->select('*')
                    ->orderBy('created_at')
                    ->get();
        }
        
        $response=ApiHelper::createAPIResponse(false,200,"",$txns);
        return response()->json($response,200);
    }


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
