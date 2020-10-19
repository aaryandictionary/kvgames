<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketCategory extends Model
{
    protected $table ='ticket_category';
    protected $primaryKey = 'ticket_category_id';

    protected $fillable=[
        'ticket_time',
        'tup_1',
        'tup_2',
        'tup_3',
        'tup_4',
        'is_enabled',
        'double_game',
        'created_at',
        'updated_at',
    ];
}
