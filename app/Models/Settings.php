<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $table ='settings';

    protected $fillable=[
        'OR',
        'A',
        'PP',
        'TNC',
        'FAQS',
        'CU',
        'CP',
        'MID1',
        'MID2',
        'created_at',
        'updated_at',
    ];
}
