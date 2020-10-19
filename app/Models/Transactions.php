<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    protected $table ='transactions';
    protected $primaryKey = 'id';

    protected $fillable=[
        'bank_txn_id',
        'txn_id',
        'order_id',
        'user_id',
        'txn_type',
        'txn_status',
        'amount',
        'created_at',
        'updated_at',
    ];
}
