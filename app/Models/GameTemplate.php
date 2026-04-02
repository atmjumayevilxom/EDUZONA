<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameTemplate extends Model
{
    protected $fillable = [
        'code', 'name', 'type', 'input_schema', 'output_schema', 'renderer_component', 'status'
    ];

    protected function casts(): array
    {
        return [
            'input_schema' => 'array',
            'output_schema' => 'array',
        ];
    }

    public function games()
    {
        return $this->hasMany(Game::class, 'template_id');
    }

    public function promptVersions()
    {
        return $this->hasMany(PromptVersion::class, 'template_id');
    }

    public function activePromptVersion()
    {
        return $this->hasOne(PromptVersion::class, 'template_id')->where('status', 'active')->latest();
    }
}
