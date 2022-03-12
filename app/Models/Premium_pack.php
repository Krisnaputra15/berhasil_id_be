<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Premium_pack extends Model
{
    protected $table = 'premium_pack';
    protected $primarykey = 'id';
    protected $fillable = [
        'pack_name',
        'price'
    ];
    public $timestamps = false;
    use HasFactory;
}
