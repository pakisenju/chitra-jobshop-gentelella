<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TireJobOrder extends Model
{
    use HasFactory;

    protected $fillable = ['sn_tire'];

    public function taskDetails()
    {
        return $this->hasMany(TireJobOrderTaskDetail::class);
    }
}
