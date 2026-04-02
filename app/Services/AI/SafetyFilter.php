<?php

namespace App\Services\AI;

class SafetyFilter
{
    private array $patterns = [
        'self_harm' => [
            'severity' => 'critical',
            'patterns' => [
                '/\b(kill\s+myself|end\s+my\s+life|want\s+to\s+die|suicide|cut\s+myself|hurt\s+myself)\b/i',
                '/\b(don.?t\s+want\s+to\s+(be\s+here|live|exist))\b/i',
                '/\b(thinking\s+about\s+(hurting|killing)\s+(myself|me))\b/i',
            ],
        ],
        'abuse_disclosure' => [
            'severity' => 'critical',
            'patterns' => [
                '/\b(someone\s+is\s+(hitting|hurting|touching|abusing)\s+me)\b/i',
                '/\b(my\s+(mom|dad|parent|step|uncle|aunt|teacher|coach)\s+(hits|hurts|touches|beats)\s+me)\b/i',
                '/\b(being\s+(abused|hurt|touched)\s+(at\s+home|by\s+an?\s+adult))\b/i',
            ],
        ],
        'bullying' => [
            'severity' => 'high',
            'patterns' => [
                '/\b(they\s+(keep|always)\s+(calling\s+me|making\s+fun|hitting|pushing|excluding))\b/i',
                '/\b(nobody\s+(likes|wants)\s+me|everyone\s+hates\s+me)\b/i',
                '/\b(being\s+bullied|they\s+won.?t\s+stop)\b/i',
            ],
        ],
        'profanity_severe' => [
            'severity' => 'medium',
            'patterns' => [],
        ],
    ];

    public function check(string $content): ?FlagResult
    {
        foreach ($this->patterns as $category => $config) {
            foreach ($config['patterns'] as $pattern) {
                if (! empty($pattern) && preg_match($pattern, $content)) {
                    return new FlagResult(
                        flagged: true,
                        category: $category,
                        severity: $config['severity'],
                    );
                }
            }
        }

        return null;
    }

    public function safeBridgerResponse(string $category): string
    {
        return match ($category) {
            'self_harm', 'abuse_disclosure' => 'It sounds like you might be going through something really difficult right now. '.
                "You don't have to face that alone. Please talk to a trusted adult — your teacher, ".
                'a school counselor, or a parent — as soon as you can. They care about you and want to help.',

            'bullying' => "That sounds really hard, and I'm glad you felt comfortable sharing that. ".
                "It's important to talk to a trusted adult about what's happening — ".
                'your teacher or school counselor can help make it stop.',

            default => "Let's keep our conversation focused on your learning today. ".
                'Is there something about the lesson I can help you with?',
        };
    }
}
