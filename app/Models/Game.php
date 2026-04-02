<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'template_id', 'prompt_version_id',
        'topic', 'language', 'students_count', 'generated_json', 'status', 'is_public'
    ];

    protected function casts(): array
    {
        return [
            'generated_json' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function template()
    {
        return $this->belongsTo(GameTemplate::class, 'template_id');
    }

    public function promptVersion()
    {
        return $this->belongsTo(PromptVersion::class, 'prompt_version_id');
    }

    public function sessions()
    {
        return $this->hasMany(GameSession::class);
    }
}
