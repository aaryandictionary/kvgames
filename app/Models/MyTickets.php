<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MyTickets extends Model
{
    protected $table ='my_tickets';
    protected $primaryKey = 'my_ticket_id';

    protected $fillable=[
        'user_id',
        'my_ticket_date',
        'my_ticket_time',
        'ticket_combo',
        'ticket_unit_price',
        'created_at',
        'updated_at',
    ];
}
