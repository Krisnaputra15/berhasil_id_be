<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class levels extends Model
{
    protected $table = 'levels';
    protected $primarykey = 'id';
    protected $fillable = [
        'level_name',
    ];
    public $timestamps = false;
}
