<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionResult extends Model
{
    protected $fillable = ['session_id', 'participant_name', 'score', 'answers_json'];

    protected function casts(): array
    {
        return [
            'answers_json' => 'array',
        ];
    }

    public function session()
    {
        return $this->belongsTo(GameSession::class, 'session_id');
    }
}
