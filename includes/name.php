<?php

if (!defined('ABSPATH')) exit;

class ThsName {

    private const LETTER_MAP = [
        'A'=>1,'J'=>1,'S'=>1, 'B'=>2,'K'=>2,'T'=>2, 'C'=>3,'L'=>3,'U'=>3,
        'D'=>4,'M'=>4,'V'=>4, 'E'=>5,'N'=>5,'W'=>5, 'F'=>6,'O'=>6,'X'=>6,
        'G'=>7,'P'=>7,'Y'=>7, 'H'=>8,'Q'=>8,'Z'=>8, 'I'=>9,'R'=>9
    ];

    private const VOWELS = ['A','E','I','O','U'];

    private const HANGUL_INITIAL_MAP = [
        0=>1,1=>2,2=>3,3=>4,4=>5,5=>6,6=>7,7=>8,8=>9,
        9=>1,10=>2,11=>3,12=>4,13=>5,14=>6,15=>7,16=>8,17=>9,18=>1,
    ];

    private const HANGUL_MEDIAL_MAP = [
        0=>1,1=>2,2=>3,3=>4,4=>5,5=>6,6=>7,7=>8,8=>9,
        9=>1,10=>2,11=>3,12=>4,13=>5,14=>6,15=>7,16=>8,17=>9,18=>1,19=>2,20=>3,
    ];

    private const HANGUL_FINAL_MAP = [
        0=>0, 1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,
        10=>1,11=>2,12=>3,13=>4,14=>5,15=>6,16=>7,17=>8,18=>9,
        19=>1,20=>2,21=>3,22=>4,23=>5,24=>6,25=>7,26=>8,27=>9,
    ];

    public static function profile(string $name, string $locale = ''): array {
        return self::mixed_profile($name, $locale);
    }

    public static function reading_context(string $name): string {
        $scripts = self::script_counts($name);
        if ($scripts['han'] <= 0) {
            return '';
        }
        if ($scripts['japanese'] > 0) {
            return 'japanese_kanji';
        }
        if ($scripts['korean'] > 0) {
            return 'korean_hanja';
        }
        return 'han_name';
    }

    public static function contains_han_script(string $name): bool {
        return self::contains_han($name);
    }

    public static function needs_normalized_display(string $name): bool {
        $scripts = self::script_counts($name);
        foreach (['korean', 'han', 'japanese', 'thai', 'other'] as $script) {
            if (($scripts[$script] ?? 0) > 0) {
                return true;
            }
        }
        return false;
    }

    public static function display_name_with_normalized(string $name, string $locale = ''): string {
        $display_name = preg_replace('/\s+/u', ' ', trim($name)) ?: '';
        if ($display_name === '' || !self::needs_normalized_display($display_name)) {
            return $display_name;
        }

        $normalized_name = self::normalize_name($display_name, $locale);
        if ($normalized_name === '' || self::same_name_text($display_name, $normalized_name)) {
            return $display_name;
        }

        return $display_name . ' (' . $normalized_name . ')';
    }

    public static function same_name_text(string $left, string $right): bool {
        $left = self::comparable_name($left);
        $right = self::comparable_name($right);
        return $left !== '' && $left === $right;
    }

    public static function normalize_name(string $name, string $locale = ''): string {
        return self::profile($name, $locale)['normalized_name'];
    }

    public static function validate_profile(array $profile): void {
        $parts = $profile['parts'] ?? [];
        $valid_count = 0;
        foreach ($parts as $part) {
            if ((int)($part['value'] ?? 0) > 0) {
                $valid_count++;
            }
        }

        if ($valid_count < 2) {
            throw new InvalidArgumentException(ThsI18n::lookup('errors', 'missing_info', 'Please enter a valid full name.'));
        }
    }

    public static function romanize_name(string $name, string $locale = '', string $script = ''): string {
        $name = preg_replace('/[^\p{L}\p{M}\s\'’\-.]/u', ' ', $name);
        $name = preg_replace('/\s+/u', ' ', trim((string)$name));
        if ($name === '') {
            return '';
        }

        if (class_exists('Transliterator')) {
            $transliterator = Transliterator::create(self::transliterator_id($locale, $script));
            if ($transliterator instanceof Transliterator) {
                $romanized = $transliterator->transliterate($name);
                if (is_string($romanized) && trim($romanized) !== '') {
                    $name = $romanized;
                }
            }
        }

        $name = str_replace(['-', '’', "'"], ' ', $name);
        return preg_replace('/\s+/u', ' ', trim($name));
    }

    private static function mixed_profile(string $name, string $locale): array {
        $name = preg_replace('/[^\p{L}\p{M}\s\'’\-.]/u', ' ', $name);
        $words = preg_split('/\s+/u', trim((string)$name), -1, PREG_SPLIT_NO_EMPTY);
        $parts = [];
        $first_parts = [];
        $display_words = [];
        $scripts = [];

        foreach ($words as $word) {
            $buffer = '';
            $buffer_script = '';
            $display_segments = [];
            $has_hangul_first = false;
            $len = mb_strlen($word, 'UTF-8');

            $flush_buffer = function() use (&$buffer, &$buffer_script, &$display_segments, &$parts, &$first_parts, $locale): void {
                $normalized = self::normalize_latin_name($buffer, $locale, $buffer_script);
                $buffer = '';
                $buffer_script = '';
                if ($normalized === '') return;

                $tokens = array_values(array_filter(explode(' ', $normalized), fn($token) => $token !== ''));
                if (empty($tokens)) return;

                $display_segments[] = ['text' => implode(' ', $tokens), 'type' => 'latin'];
                foreach ($tokens as $token) {
                    if ($token === '') continue;
                    $latin_parts = self::latin_word_parts($token);
                    foreach ($latin_parts as $index => $part) {
                        $parts[] = $part;
                        if ($index === 0) {
                            $first_parts[] = $part;
                        }
                    }
                }
            };

            for ($i = 0; $i < $len; $i++) {
                $char = mb_substr($word, $i, 1, 'UTF-8');
                $script = self::char_script($char);
                if ($script !== '') {
                    $scripts[self::script_label($script)] = true;
                }

                if (self::is_hangul_syllable($char)) {
                    $flush_buffer();
                    $display_segments[] = ['text' => $char, 'type' => 'hangul'];

                    foreach (self::hangul_parts($char) as $index => $part) {
                        $parts[] = $part;
                        if (!$has_hangul_first && $index === 0) {
                            $first_parts[] = $part;
                            $has_hangul_first = true;
                        }
                    }
                    continue;
                }

                if ($script !== '' && $buffer !== '' && $buffer_script !== '' && $script !== $buffer_script) {
                    $flush_buffer();
                }

                if ($script !== '' && $buffer_script === '') {
                    $buffer_script = $script;
                }

                $buffer .= $char;
            }

            $flush_buffer();

            if (!empty($display_segments)) {
                $display_words[] = self::display_word($display_segments);
            }
        }

        if (empty($scripts) && !empty($parts)) {
            $scripts['latin'] = true;
        }

        return [
            'script' => count($scripts) === 1 ? array_key_first($scripts) : 'mixed',
            'normalized_name' => implode(' ', $display_words),
            'parts' => $parts,
            'first_parts' => $first_parts,
        ];
    }

    private static function latin_word_parts(string $word): array {
        $parts = [];
        $letters = str_split($word);
        foreach ($letters as $letter) {
            $parts[] = [
                'letter'   => $letter,
                'value'    => self::LETTER_MAP[$letter] ?? 0,
                'is_vowel' => self::is_latin_vowel($letter, $letters),
            ];
        }

        return $parts;
    }

    private static function display_word(array $segments): string {
        $display = '';
        $previous_type = '';

        foreach ($segments as $segment) {
            $text = (string)($segment['text'] ?? '');
            $type = (string)($segment['type'] ?? '');
            if ($text === '') continue;

            if ($display !== '' && !($previous_type === 'hangul' && $type === 'hangul')) {
                $display .= ' ';
            }

            $display .= $text;
            $previous_type = $type;
        }

        return $display;
    }

    private static function normalize_latin_name(string $name, string $locale = '', string $script = ''): string {
        $str = self::romanize_name($name, $locale, $script);
        $str = mb_strtolower($str, 'UTF-8');
        $unicode = [
            'a' => 'á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ',
            'd' => 'đ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
        ];

        foreach ($unicode as $non => $uni) {
            $str = preg_replace("/($uni)/i", $non, $str);
        }

        return strtoupper(preg_replace('/\s+/', ' ', trim(preg_replace('/[^a-z ]/i', '', $str))));
    }

    private static function transliterator_id(string $locale, string $script): string {
        return match ($script) {
            'han' => 'Han-Latin/Names; Latin-ASCII',
            'japanese' => 'Hiragana-Latin; Katakana-Latin; Latin-ASCII',
            'thai' => 'Thai-Latin; Latin-ASCII',
            default => 'Any-Latin; Latin-ASCII',
        };
    }

    private static function contains_han(string $name): bool {
        return self::script_counts($name)['han'] > 0;
    }

    private static function script_counts(string $name): array {
        $counts = ['latin' => 0, 'korean' => 0, 'han' => 0, 'japanese' => 0, 'thai' => 0, 'other' => 0];
        $len = mb_strlen($name, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $script = self::char_script(mb_substr($name, $i, 1, 'UTF-8'));
            if ($script !== '' && isset($counts[$script])) {
                $counts[$script]++;
            }
        }

        return $counts;
    }

    private static function comparable_name(string $name): string {
        $name = mb_strtolower(trim($name), 'UTF-8');
        return preg_replace('/[\s\p{P}\p{S}]+/u', '', $name) ?: '';
    }

    private static function script_label(string $script): string {
        return $script;
    }

    private static function hangul_parts(string $char): array {
        $code = mb_ord($char, 'UTF-8');
        if ($code < 0xAC00 || $code > 0xD7AF) return [];

        $syllable = $code - 0xAC00;
        $initial = intdiv($syllable, 588);
        $medial = intdiv($syllable % 588, 28);
        $final = $syllable % 28;

        $parts = [
            ['letter' => $char . '(initial)', 'value' => self::HANGUL_INITIAL_MAP[$initial] ?? 0, 'is_vowel' => false],
            ['letter' => $char . '(medial)', 'value' => self::HANGUL_MEDIAL_MAP[$medial] ?? 0, 'is_vowel' => true],
        ];

        if ($final > 0) {
            $parts[] = ['letter' => $char . '(final)', 'value' => self::HANGUL_FINAL_MAP[$final] ?? 0, 'is_vowel' => false];
        }

        return $parts;
    }

    private static function is_hangul_syllable(string $char): bool {
        $code = mb_ord($char, 'UTF-8');
        return $code >= 0xAC00 && $code <= 0xD7AF;
    }

    private static function char_script(string $char): string {
        if (preg_match('/^[\p{M}\'’\-.]$/u', $char)) return '';
        if (preg_match('/^\p{Latin}$/u', $char)) return 'latin';

        $code = mb_ord($char, 'UTF-8');
        if (($code >= 0x41 && $code <= 0x5A) || ($code >= 0x61 && $code <= 0x7A)) return 'latin';
        if (($code >= 0xAC00 && $code <= 0xD7AF) || ($code >= 0x1100 && $code <= 0x11FF) || ($code >= 0x3130 && $code <= 0x318F)) return 'korean';
        if (($code >= 0x4E00 && $code <= 0x9FFF) || ($code >= 0x3400 && $code <= 0x4DBF)) return 'han';
        if (($code >= 0x3040 && $code <= 0x30FF) || ($code >= 0x31F0 && $code <= 0x31FF)) return 'japanese';
        if ($code >= 0x0E00 && $code <= 0x0E7F) return 'thai';
        if ($code > 0x7F) return 'other';
        return '';
    }

    private static function is_latin_vowel(string $letter, array $word_letters): bool {
        if (in_array($letter, self::VOWELS, true)) return true;
        if ($letter !== 'Y') return false;

        foreach ($word_letters as $word_letter) {
            if (in_array($word_letter, self::VOWELS, true)) return false;
        }

        return true;
    }
}
