<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $guarded=[];

    public function sender(){
        return $this->belongsTo(User::class,'sender_id');
    }
}
