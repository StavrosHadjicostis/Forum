<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomBookings extends Model
{
    use HasFactory;

    protected $fillable = ['userid', 'room', 'start_date', 'end_date', 'description'];
}
