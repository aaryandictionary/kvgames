<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameJoins extends Model
{
    protected $table ='game_joins';
    protected $primaryKey = 'join_id';

    protected $fillable=[
        'user_id',
        'firebase_uid',
        'game_date',
        'game_time',
        'join_time',
        'ticket_count',
        'created_at',
        'updated_at',
    ];
}
