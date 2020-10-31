<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tests extends Model
{
    protected $table ='test';

    protected $fillable=[
        'time',
        'message',
        'title',
        'sendto',
        'status',
        'created_at',
        'updated_at',
    ];
}
