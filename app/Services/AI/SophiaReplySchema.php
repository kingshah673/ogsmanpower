<?php

namespace App\Services\AI;

/**
 * Structured JSON schema for Sophia AI assistant replies (not the Sub-Agent portal role).
 */
class SophiaReplySchema
{
    /**
     * @return array<string, mixed>
     */
    public static function definition(): array
    {
        return [
            'reply_html' => 'string — full answer using HTML only (<h4 class="sophia-h">, <strong>, <ul class="sophia-list"><li>…</li></ul>, <p>, <a href>)',
            'sections' => [
                [
                    'title' => 'Section heading (e.g. Job Seekers)',
                    'items' => ['bullet line 1', 'bullet line 2'],
                ],
            ],
            'summary' => 'optional one-line headline',
        ];
    }

    public static function instructions(): string
    {
        return "OUTPUT FORMAT (JSON only)\n"
            ."- Return a single JSON object.\n"
            ."- Prefer reply_html with clean HTML (NO markdown # or **).\n"
            ."- Section headings: <h4 class=\"sophia-h\">Title</h4>\n"
            ."- Bold labels: <strong>Label</strong>\n"
            ."- Lists: <ul class=\"sophia-list\"><li>item</li></ul>\n"
            ."- You may use sections[] instead of reply_html when listing grouped stats.\n"
            ."- Links: <a href=\"url\">label</a>\n"
            ."- Never use markdown syntax (###, **, -) in output.\n"
            ."- Sophia is the AI chatbot assistant. \"Agent / Facilitator\" is a portal user role — do not call Sophia an agent.";
    }

    /**
     * @param  array<string, mixed>  $json
     */
    public static function toHtml(array $json): ?string
    {
        if (! empty($json['reply_html']) && is_string($json['reply_html'])) {
            return SophiaMessageFormatter::toHtml($json['reply_html']);
        }

        $sections = $json['sections'] ?? [];

        if (! is_array($sections) || $sections === []) {
            return null;
        }

        $html = '';

        if (! empty($json['summary']) && is_string($json['summary'])) {
            $html .= '<p><strong>'.e($json['summary']).'</strong></p>';
        }

        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            $title = trim((string) ($section['title'] ?? ''));
            if ($title !== '') {
                $html .= '<h4 class="sophia-h">'.e($title).'</h4>';
            }

            $items = $section['items'] ?? [];
            if (is_array($items) && $items !== []) {
                $html .= '<ul class="sophia-list">';
                foreach ($items as $item) {
                    $html .= '<li>'.SophiaMessageFormatter::inlineFormat((string) $item).'</li>';
                }
                $html .= '</ul>';
            }
        }

        return $html !== '' ? SophiaMessageFormatter::sanitize($html) : null;
    }
}
