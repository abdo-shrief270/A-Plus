<?php

namespace App\Support;

class QuestionContentRenderer
{
    public static function render(?string $raw): string
    {
        if ($raw === null || $raw === '') {
            return '';
        }

        $html = $raw;

        $html = preg_replace_callback(
            '/!\[\]\(([^)\s]+)\)/u',
            function ($m) {
                $src = htmlspecialchars($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return '<img src="' . $src . '" alt="" class="question-image" />';
            },
            $html
        );

        $html = preg_replace_callback(
            '#(?<![\"\'=>])(https?://www\.wiris\.net/[^\s<>\"\']+)#u',
            function ($m) {
                $src = htmlspecialchars($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return '<img src="' . $src . '" alt="math" class="wiris-math" />';
            },
            $html
        );

        $html = nl2br($html, false);

        return $html;
    }
}
