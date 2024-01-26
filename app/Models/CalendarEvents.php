<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarEvents extends Model
{
    use HasFactory;

    protected $fillable = ['userid', 'title', 'place', 'start_date', 'end_date', 'color'];
}
