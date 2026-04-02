<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameSession extends Model
{
    protected $fillable = ['game_id', 'session_code', 'started_at', 'ended_at', 'status'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function results()
    {
        return $this->hasMany(SessionResult::class, 'session_id');
    }
}
