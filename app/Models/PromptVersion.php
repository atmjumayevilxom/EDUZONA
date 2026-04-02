<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromptVersion extends Model
{
    protected $fillable = ['template_id', 'version', 'prompt_text', 'status'];

    public function template()
    {
        return $this->belongsTo(GameTemplate::class, 'template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
