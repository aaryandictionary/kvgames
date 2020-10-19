<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prizes extends Model
{
    protected $table ='prizes';
    protected $primaryKey = 'prize_id';

    protected $fillable=[
        'prize_unit_price',
        'prize_count',
        'ef_rate',
        'li_rate',
        'fh_rate',
        'is_active',
        'created_at',
        'updated_at',
    ];
}
