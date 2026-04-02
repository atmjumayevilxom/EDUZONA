<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassroomStudent extends Model
{
    public $timestamps = false;

    protected $fillable = ['classroom_id', 'name', 'joined_at'];

    protected $casts = ['joined_at' => 'datetime'];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }
}
