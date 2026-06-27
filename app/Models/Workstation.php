<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workstation extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
        'active',
    ];
}
