<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stop extends Model
{
    protected $fillable = ['name', 'latitude', 'longitude', 'route_id'];
    use HasFactory;
}
