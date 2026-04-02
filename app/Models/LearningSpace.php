<?php

namespace App\Models;

use App\Helpers\JoinCode;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningSpace extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'district_id', 'teacher_id', 'classroom_id', 'title', 'description',
        'subject', 'grade_level', 'cover_image', 'system_prompt', 'goals',
        'restrictions', 'allowed_tools', 'bridger_tone', 'language',
        'max_messages', 'require_teacher_present', 'allow_session_restart',
        'is_published', 'is_public', 'is_archived', 'join_code', 'opens_at', 'closes_at',
    ];

    protected function casts(): array
    {
        return [
            'goals' => 'array',
            'restrictions' => 'array',
            'allowed_tools' => 'array',
            'is_published' => 'boolean',
            'is_public' => 'boolean',
            'is_archived' => 'boolean',
            'require_teacher_present' => 'boolean',
            'allow_session_restart' => 'boolean',
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LearningSpace $space) {
            if (empty($space->join_code)) {
                $space->join_code = JoinCode::generate('learning_spaces');
            }
        });

        static::addGlobalScope('district', function ($query) {
            if (auth()->check()) {
                $query->where('learning_spaces.district_id', auth()->user()->district_id);
            }
        });
    }

    public function district(): BelongsTo { return $this->belongsTo(District::class); }

    public function teacher(): BelongsTo { return $this->belongsTo(User::class, 'teacher_id'); }

    public function classroom(): BelongsTo { return $this->belongsTo(Classroom::class); }

    public function sessions(): HasMany { return $this->hasMany(StudentSession::class, 'space_id'); }
}
