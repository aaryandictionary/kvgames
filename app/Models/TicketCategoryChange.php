<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketCategoryChange extends Model
{
    protected $table ='ticket_category_changes';
    protected $primaryKey = 'tc_change_id';

    protected $fillable=[
        'change_for_date',
        'tup_1',
        'tup_2',
        'tup_3',
        'tup_4',
        'change_for_time',
        'status',
        'double_game',
        'created_at',
        'updated_at',
    ];
}
