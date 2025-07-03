<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TireJobOrderTaskDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'tire_job_order_id',
        'task_id',
        'qty_calculated',
        'total_duration_calculated',
        'start_time',
        'end_time',
        'status'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function tireJobOrder()
    {
        return $this->belongsTo(TireJobOrder::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function toolUsed()
    {
        return $this->belongsTo(Tool::class, 'tool_id_used');
    }
}
