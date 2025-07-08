<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TireJobOrder extends Model
{
    use HasFactory;

    protected $fillable = ['sn_tire', 'tread', 'sidewall', 'spot', 'patch', 'area_curing_sw', 'area_curing_tread', 'bead', 'chaffer', 'customer_id'];

    public function tireJobOrderTaskDetails()
    {
        return $this->hasMany(TireJobOrderTaskDetail::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
