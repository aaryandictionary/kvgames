<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameResult extends Model
{
    protected $table ='game_results';
    protected $primaryKey = 'result_id';

    protected $fillable=[
        'game_date',
        'game_time',
        'result_combo',
        'created_at',
        'updated_at',
    ];
}
