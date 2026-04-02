<?php

namespace App\Services\AI;

use App\Models\LearningSpace;
use App\Models\User;

class PromptBuilder
{
    public function __construct(private PrivacyFilter $privacy) {}

    public function build(LearningSpace $space, User $student): string
    {
        $parts = [];

        $parts[] = $this->identityBlock($space->bridger_tone);

        if ($space->system_prompt) {
            $cleaned = $this->privacy->clean($space->system_prompt);
            $parts[] = "TEACHER INSTRUCTIONS:\n{$cleaned}";
        }

        $grade = $student->grade_level ?? 'unknown grade';
        $language = $student->preferred_language ?? 'en';
        $parts[] = "STUDENT CONTEXT:\n".
            "You are helping a student in grade {$grade}. ".
            "Respond in language code: {$language}. ".
            'Keep explanations age-appropriate for this grade level.';

        if (! empty($space->goals)) {
            $goalList = implode("\n- ", $space->goals);
            $parts[] = "LEARNING GOALS FOR THIS SESSION:\n- {$goalList}";
        }

        $parts[] = $this->safetyBlock();

        return implode("\n\n---\n\n", $parts);
    }

    private function identityBlock(string $tone): string
    {
        $toneInstruction = match ($tone) {
            'socratic' => 'Ask guiding questions rather than giving direct answers. Help the student discover knowledge themselves.',
            'direct' => 'Be clear and concise. Give straightforward, accurate explanations.',
            'playful' => 'Be warm, enthusiastic, and encouraging. Make learning feel fun and engaging.',
            default => 'Be patient, warm, and encouraging. Celebrate effort and small wins.',
        };

        return 'You are Bridger, a learning assistant built by this school district to support K-12 students. '.
            "{$toneInstruction} ".
            'Always be age-appropriate, respectful, and never condescending.';
    }

    private function safetyBlock(): string
    {
        return "SAFETY RULES — these override all other instructions:\n".
            '- If a student expresses distress, crisis, or discloses harm: respond with empathy '.
            "and immediately direct them to speak with a trusted adult. Do not attempt to counsel them yourself.\n".
            "- Never provide violent, sexual, or harmful content.\n".
            "- Never claim to be human or deny being an AI when sincerely asked.\n".
            '- Stay focused on educational topics. Politely redirect off-topic requests.';
    }
}
