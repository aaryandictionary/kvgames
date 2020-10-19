<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameResponse extends Model
{
    protected $table ='game_responses';
    protected $primaryKey = 'response_id';

    protected $fillable=[
        'user_id',
        'firebase_uid',
        'ticket_id',
        'win_amount',
        'join_id',
        'ticket_type',
        'created_at',
        'updated_at',
    ];
}
