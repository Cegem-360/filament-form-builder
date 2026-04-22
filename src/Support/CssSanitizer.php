<?php

declare(strict_types=1);

namespace Madbox99\FilamentFormBuilder\Support;

final class CssSanitizer
{
    public const DEFAULT_MAX_LENGTH = 20000;

    public static function sanitize(?string $css, ?int $maxLength = null): string
    {
        if ($css === null) {
            return '';
        }

        $css = str_replace(["\r\n", "\r"], "\n", $css);
        $css = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $css);

        $limit = $maxLength ?? self::DEFAULT_MAX_LENGTH;
        if (strlen($css) > $limit) {
            $css = substr($css, 0, $limit);
        }

        $patterns = [
            // HTML/style/script tag breakout
            '#</\s*style\s*>#i',
            '#<\s*/?\s*script[^>]*>#i',
            '#<\s*/?\s*iframe[^>]*>#i',
            '#<!--.*?-->#s',
            // CSS-native import / external fetches
            '#@import\b[^;]*;?#i',
            '#@charset\b[^;]*;?#i',
            // Legacy IE / script-capable declarations
            '#\bexpression\s*\([^)]*\)#i',
            '#\bbehavior\s*:[^;}]*#i',
            '#-moz-binding\s*:[^;}]*#i',
            // JS/data URLs inside url(...) — even with spaces, quotes, escapes
            '#url\s*\(\s*["\']?\s*(?:javascript|vbscript|data|blob|file)\s*:[^)]*\)#i',
            // Remote url(...) — only allow relative, same-origin paths
            '#url\s*\(\s*["\']?\s*(?:https?:)?//[^)]*\)#i',
        ];

        $css = (string) preg_replace($patterns, '', $css);

        return trim($css);
    }

    /**
     * Scope every top-level selector in the given CSS to a prefix selector so
     * that rules cannot escape the form container (e.g. `body { display:none }`
     * becomes `#ffb-form-slug body { display:none }` and only matches a body
     * descendant of the form — effectively neutralising the rule).
     *
     * This is a best-effort parser — it understands block nesting, strings,
     * comments, and at-rules (`@media`, `@supports`), but is not a full CSS
     * grammar. Rules that fail to parse are dropped.
     */
    public static function scope(string $css, string $prefixSelector): string
    {
        if ($css === '' || $prefixSelector === '') {
            return $css;
        }

        $css = self::stripComments($css);

        $output = '';
        $length = strlen($css);
        $cursor = 0;

        while ($cursor < $length) {
            $cursor = self::skipWhitespace($css, $cursor, $length);
            if ($cursor >= $length) {
                break;
            }

            if ($css[$cursor] === '@') {
                $rule = self::readAtRule($css, $cursor, $length);
                if ($rule === null) {
                    break;
                }

                [$atText, $atBlock, $newCursor] = $rule;
                $cursor = $newCursor;

                if ($atBlock === null) {
                    // Font-face / keyframes / etc at-rule without nested rules we can scope —
                    // only emit known-safe ones.
                    if (preg_match('/^@(font-face|page|viewport)\b/i', trim($atText)) === 1) {
                        $output .= $atText.';';
                    }

                    continue;
                }

                $atName = strtolower((string) preg_replace('/\s.*$/s', '', trim($atText)));

                if ($atName === '@keyframes' || $atName === '@-webkit-keyframes') {
                    $output .= $atText.'{'.$atBlock.'}';

                    continue;
                }

                if ($atName === '@media' || $atName === '@supports') {
                    $scopedInner = self::scopeRuleBlock($atBlock, $prefixSelector);
                    $output .= $atText.'{'.$scopedInner.'}';

                    continue;
                }

                // Unknown at-rule: drop silently.
                continue;
            }

            [$selector, $block, $newCursor] = self::readRule($css, $cursor, $length);
            $cursor = $newCursor;

            if ($selector === null || $block === null) {
                break;
            }

            $scopedSelector = self::scopeSelectorList($selector, $prefixSelector);
            if ($scopedSelector === '') {
                continue;
            }

            $output .= $scopedSelector.'{'.trim($block).'}';
        }

        return $output;
    }

    private static function scopeRuleBlock(string $block, string $prefixSelector): string
    {
        return self::scope($block, $prefixSelector);
    }

    private static function scopeSelectorList(string $selectorList, string $prefixSelector): string
    {
        $parts = self::splitTopLevel($selectorList, ',');
        $scoped = [];

        foreach ($parts as $part) {
            $trimmed = trim($part);
            if ($trimmed === '') {
                continue;
            }

            // Never allow :root / html / body to take over the whole document —
            // rewrite them into the form scope.
            $trimmed = (string) preg_replace('/^(:root|html|body)\b/i', '', $trimmed);
            $trimmed = trim($trimmed);

            if ($trimmed === '') {
                $scoped[] = $prefixSelector;

                continue;
            }

            $scoped[] = $prefixSelector.' '.$trimmed;
        }

        return implode(',', $scoped);
    }

    /**
     * @return list<string>
     */
    private static function splitTopLevel(string $input, string $delimiter): array
    {
        $result = [];
        $buffer = '';
        $depth = 0;
        $length = strlen($input);

        for ($i = 0; $i < $length; $i++) {
            $char = $input[$i];

            if ($char === '(' || $char === '[') {
                $depth++;
            } elseif ($char === ')' || $char === ']') {
                $depth = max(0, $depth - 1);
            }

            if ($char === $delimiter && $depth === 0) {
                $result[] = $buffer;
                $buffer = '';

                continue;
            }

            $buffer .= $char;
        }

        if ($buffer !== '') {
            $result[] = $buffer;
        }

        return $result;
    }

    /**
     * @return array{0:string,1:?string,2:int}|null [at-text, block-or-null, new-cursor]
     */
    private static function readAtRule(string $css, int $start, int $length): ?array
    {
        $cursor = $start;
        $header = '';

        while ($cursor < $length) {
            $char = $css[$cursor];

            if ($char === ';') {
                return [$header, null, $cursor + 1];
            }

            if ($char === '{') {
                [$block, $newCursor] = self::readBlock($css, $cursor, $length);

                return [$header, $block, $newCursor];
            }

            $header .= $char;
            $cursor++;
        }

        return null;
    }

    /**
     * @return array{0:?string,1:?string,2:int} [selector, block, new-cursor]
     */
    private static function readRule(string $css, int $start, int $length): array
    {
        $cursor = $start;
        $selector = '';

        while ($cursor < $length) {
            $char = $css[$cursor];

            if ($char === '{') {
                [$block, $newCursor] = self::readBlock($css, $cursor, $length);

                return [$selector, $block, $newCursor];
            }

            if ($char === '}') {
                return [null, null, $cursor + 1];
            }

            $selector .= $char;
            $cursor++;
        }

        return [null, null, $length];
    }

    /**
     * @return array{0:string,1:int} [block-contents, new-cursor]
     */
    private static function readBlock(string $css, int $start, int $length): array
    {
        if (! isset($css[$start]) || $css[$start] !== '{') {
            return ['', $start];
        }

        $cursor = $start + 1;
        $depth = 1;
        $contents = '';

        while ($cursor < $length && $depth > 0) {
            $char = $css[$cursor];

            if ($char === '"' || $char === "'") {
                [$literal, $cursor] = self::readString($css, $cursor, $length);
                $contents .= $literal;

                continue;
            }

            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    return [$contents, $cursor + 1];
                }
            }

            $contents .= $char;
            $cursor++;
        }

        return [$contents, $cursor];
    }

    /**
     * @return array{0:string,1:int} [string-literal-with-quotes, new-cursor]
     */
    private static function readString(string $css, int $start, int $length): array
    {
        $quote = $css[$start];
        $literal = $quote;
        $cursor = $start + 1;

        while ($cursor < $length) {
            $char = $css[$cursor];
            $literal .= $char;

            if ($char === '\\' && $cursor + 1 < $length) {
                $literal .= $css[$cursor + 1];
                $cursor += 2;

                continue;
            }

            if ($char === $quote) {
                return [$literal, $cursor + 1];
            }

            $cursor++;
        }

        return [$literal, $cursor];
    }

    private static function skipWhitespace(string $css, int $start, int $length): int
    {
        while ($start < $length && ctype_space($css[$start])) {
            $start++;
        }

        return $start;
    }

    private static function stripComments(string $css): string
    {
        return (string) preg_replace('#/\*.*?\*/#s', '', $css);
    }
}
