<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Referrals extends Model
{
    protected $table ='referral';

    protected $fillable=[
        'referral_user_code',
        'new_user_code',
        'referral_user_id',
        'new_user_id',
        'created_at',
        'updated_at',
    ];
}
