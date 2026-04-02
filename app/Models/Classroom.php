<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    protected $fillable = ['user_id', 'name', 'subject', 'join_code'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function students()
    {
        return $this->hasMany(ClassroomStudent::class)->orderBy('joined_at');
    }
}
