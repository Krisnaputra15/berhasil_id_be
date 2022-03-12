<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    protected $table = 'users';
    protected $primarykey = 'id';
    protected $fillable = [
        'email',
        'password',
        'id_level',
        'premium_status',
        'name',
        'birth_date',
        'address',
        'phone_number',
        'job',
        'salary',
        'created_at',
    ];
    public $timestamps = false;
}
