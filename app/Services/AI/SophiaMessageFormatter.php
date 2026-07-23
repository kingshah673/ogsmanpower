<?php

namespace App\Services\AI;

/**
 * Converts Sophia chatbot replies (markdown or mixed) into safe HTML for the widget.
 */
class SophiaMessageFormatter
{
    public static function toHtml(string $text): string
    {
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        if (self::looksLikeHtml($text)) {
            return self::sanitize($text);
        }

        return self::sanitize(self::markdownToHtml($text));
    }

    public static function inlineFormat(string $text): string
    {
        $text = e($text);
        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $text);

        return $text;
    }

    protected static function looksLikeHtml(string $text): bool
    {
        return (bool) preg_match('/<(h[1-6]|p|ul|ol|li|strong|em|a|br|div)\b/i', $text);
    }

    protected static function markdownToHtml(string $text): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $text);
        $html = [];
        $inList = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                if ($inList) {
                    $html[] = '</ul>';
                    $inList = false;
                }

                continue;
            }

            if (preg_match('/^###\s+(.+)$/', $trimmed, $m)) {
                if ($inList) {
                    $html[] = '</ul>';
                    $inList = false;
                }
                $html[] = '<h4 class="sophia-h">'.self::inlineFormat($m[1]).'</h4>';

                continue;
            }

            if (preg_match('/^##\s+(.+)$/', $trimmed, $m)) {
                if ($inList) {
                    $html[] = '</ul>';
                    $inList = false;
                }
                $html[] = '<h3 class="sophia-h">'.self::inlineFormat($m[1]).'</h3>';

                continue;
            }

            if (preg_match('/^#\s+(.+)$/', $trimmed, $m)) {
                if ($inList) {
                    $html[] = '</ul>';
                    $inList = false;
                }
                $html[] = '<h3 class="sophia-h">'.self::inlineFormat($m[1]).'</h3>';

                continue;
            }

            if (preg_match('/^[-*•]\s+(.+)$/', $trimmed, $m)) {
                if (! $inList) {
                    $html[] = '<ul class="sophia-list">';
                    $inList = true;
                }
                $html[] = '<li>'.self::inlineFormat($m[1]).'</li>';

                continue;
            }

            if ($inList) {
                $html[] = '</ul>';
                $inList = false;
            }

            $html[] = '<p>'.self::inlineFormat($trimmed).'</p>';
        }

        if ($inList) {
            $html[] = '</ul>';
        }

        return implode('', $html);
    }

    public static function sanitize(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);

        return strip_tags($html, '<h3><h4><p><ul><ol><li><strong><em><a><br><span>');
    }
}
