<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Premium_transaction extends Model
{
    protected $table = 'premium_transaction';
    protected $primarykey = 'id';
    protected $fillable = [
        'id_user',
        'id_premium',
        'payment_proof',
        'start_date',
        'end_date'
    ];
    public $timestamps = false;
    use HasFactory;
}
