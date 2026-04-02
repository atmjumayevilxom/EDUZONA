<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiVideoRequest extends Model
{
    protected $fillable = [
        'user_id',
        'subject',
        'topic',
        'problem_text',
        'video_style',
        'explanation_length',
        'voice_style',
        'language',
        'solution_json',
        'video_prompt',
        'provider_name',
        'provider_request_id',
        'status',
        'error_message',
        'video_url',
        'completed_at',
    ];

    protected $casts = [
        'solution_json' => 'array',
        'completed_at'  => 'datetime',
    ];

    // ── Statuslar ────────────────────────────────────────────────────────────

    const STATUS_PENDING         = 'pending';
    const STATUS_SOLVING         = 'solving';
    const STATUS_BUILDING_PROMPT = 'building_prompt';
    const STATUS_GENERATING      = 'generating';
    const STATUS_COMPLETED       = 'completed';
    const STATUS_FAILED          = 'failed';

    // ── Relationships ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Helper methodlar ──────────────────────────────────────────────────────

    public function markAs(string $status, array $extra = []): void
    {
        $data = array_merge(['status' => $status], $extra);

        if ($status === self::STATUS_COMPLETED) {
            $data['completed_at'] = now();
        }

        $this->update($data);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED]);
    }

    public function isProcessing(): bool
    {
        return in_array($this->status, [
            self::STATUS_SOLVING,
            self::STATUS_BUILDING_PROMPT,
            self::STATUS_GENERATING,
        ]);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeProcessing($query)
    {
        return $query->whereIn('status', [
            self::STATUS_SOLVING,
            self::STATUS_BUILDING_PROMPT,
            self::STATUS_GENERATING,
        ]);
    }

    // ── Computed ──────────────────────────────────────────────────────────────

    public function getSubjectLabelAttribute(): string
    {
        return config('ai_video.subjects')[$this->subject] ?? $this->subject;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING         => 'Kutilmoqda',
            self::STATUS_SOLVING         => 'Yechilmoqda',
            self::STATUS_BUILDING_PROMPT => 'Prompt qurilmoqda',
            self::STATUS_GENERATING      => 'Video yaratilmoqda',
            self::STATUS_COMPLETED       => 'Tayyor',
            self::STATUS_FAILED          => 'Xato',
            default                      => $this->status,
        };
    }
}
