<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Leasing extends Model
{
    protected $table = 'leasings';

    protected $fillable = [
        'name',
        'code',
        'description'
    ];
}
