<?php

if (!defined('ABSPATH')) exit;

class ThsHelper {

    private const DATE_INPUT_RULES = [
        'default' => ['order' => ['day', 'month', 'year'], 'separator' => '/'],
        'en'      => ['order' => ['month', 'day', 'year'], 'separator' => '/'],
        'de'      => ['order' => ['day', 'month', 'year'], 'separator' => '.'],
        'ja'      => ['order' => ['year', 'month', 'day'], 'separator' => '/'],
        'ko'      => ['order' => ['year', 'month', 'day'], 'separator' => '.'],
        'zh'      => ['order' => ['year', 'month', 'day'], 'separator' => '/'],
    ];

    private static function language_code(string $locale): string {
        $locale = strtolower(str_replace('_', '-', trim($locale)));
        return explode('-', $locale)[0] ?: 'default';
    }

    private static function date_input_rule(string $locale): array {
        $language = self::language_code($locale);
        return self::DATE_INPUT_RULES[$language] ?? self::DATE_INPUT_RULES['default'];
    }

    private static function date_input_separator(string $locale): string {
        return self::date_input_rule($locale)['separator'];
    }

    public static function date_input_order(string $locale): array {
        return self::date_input_rule($locale)['order'];
    }

    private static function date_input_label(string $locale): string {
        $labels = ['day' => 'DD', 'month' => 'MM', 'year' => 'YYYY'];
        return implode(self::date_input_separator($locale), array_map(fn($part) => $labels[$part], self::date_input_order($locale)));
    }

    private static function date_format_error(string $locale): string {
        $examples = ['day' => '15', 'month' => '8', 'year' => '1990'];
        $separator = self::date_input_separator($locale);
        $order = self::date_input_order($locale);
        $format = self::date_input_label($locale);
        $example = implode($separator, array_map(fn($part) => $examples[$part], $order));

        return "Format: {$format} (e.g. {$example})";
    }

    private static function date_validator_display(int $year, int $month, int $day, string $locale): string {
        $values = ['day' => $day, 'month' => $month, 'year' => $year];
        return implode(self::date_input_separator($locale), array_map(fn($part) => (string)$values[$part], self::date_input_order($locale)));
    }

    public static function format_date_for_locale(int $day, int $month, int $year, string $locale): string {
        if (class_exists('ThsI18n')) {
            return ThsI18n::format_date($day, $month, $year, $locale);
        }

        return self::date_validator_display($year, $month, $day, $locale);
    }

    public static function date_digits_for_locale(int $day, int $month, int $year, string $locale): array {
        $display = self::format_date_for_locale($day, $month, $year, $locale);
        $digits = preg_replace('/\D+/', '', $display);

        return $digits === null ? [] : str_split($digits);
    }

    public static function parse_iso_date(string $dob, string $locale = ''): array {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', trim($dob), $matches)) {
            throw new InvalidArgumentException('Invalid date. Use YYYY-MM-DD.');
        }

        $year = (int)$matches[1];
        $month = (int)$matches[2];
        $day = (int)$matches[3];

        if (!checkdate($month, $day, $year)) {
            $display = $locale !== ''
                ? self::date_validator_display($year, $month, $day, $locale)
                : "{$day}/{$month}/{$year}";
            throw new InvalidArgumentException(sprintf('Date %s does not exist.', $display));
        }

        return [$day, $month, $year];
    }

    private static function date_message(string $key, string $locale, int $day = 0, int $month = 0, int $year = 0): string {
        $date_format = self::date_input_label($locale);
        return match ($key) {
            'required' => 'Please enter your date of birth.',
            'range' => 'Supported years are 1900 to 2100.',
            'month' => "Invalid month. Use {$date_format}.",
            'not_exists' => sprintf('Date %s does not exist.', self::date_validator_display($year, $month, $day, $locale)),
            default => "Invalid day. Use {$date_format}.",
        };
    }

    private static function assign_date_part(array &$parts, string $part, int $value): void {
        if ($part === 'year') {
            $parts['year'] = $value;
        } elseif ($part === 'month') {
            $parts['month'] = $value;
        } else {
            $parts['day'] = $value;
        }
    }

    public static function validate_date(?string $param, string $locale): array {
        $param = trim((string)$param);
        if ($param === '') {
            return ['valid' => false, 'message' => self::date_message('required', $locale)];
        }

        $parts = ['year' => 0, 'month' => 0, 'day' => 0];

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $param, $matches)) {
            $parts['year'] = (int)$matches[1];
            $parts['month'] = (int)$matches[2];
            $parts['day'] = (int)$matches[3];
        } else {
            $order = self::date_input_order($locale);
            $pattern = $order[0] === 'year'
                ? '/^(\d{4})[\/\-.\s](\d{1,2})[\/\-.\s](\d{1,2})$/'
                : '/^(\d{1,2})[\/\-.\s](\d{1,2})[\/\-.\s](\d{4})$/';

            if (!preg_match($pattern, $param, $matches)) {
                return ['valid' => false, 'message' => self::date_format_error($locale)];
            }

            foreach ($order as $index => $part) {
                self::assign_date_part($parts, $part, (int)$matches[$index + 1]);
            }
        }

        $year = $parts['year'];
        $month = $parts['month'];
        $day = $parts['day'];

        if ($year < 1900 || $year > 2100) return ['valid' => false, 'message' => self::date_message('range', $locale)];
        if ($month < 1 || $month > 12) return ['valid' => false, 'message' => self::date_message('month', $locale)];
        if ($day < 1 || $day > 31) return ['valid' => false, 'message' => self::date_message('day', $locale)];
        if (!checkdate($month, $day, $year)) {
            return ['valid' => false, 'message' => self::date_message('not_exists', $locale, $day, $month, $year)];
        }

        return [
            'valid' => true,
            'iso' => sprintf('%04d-%02d-%02d', $year, $month, $day),
            'display' => self::format_date_for_locale($day, $month, $year, $locale),
        ];
    }

    public static function translated_url(string $slug): ?string {
        $pages = get_posts([
            'name'             => $slug,
            'post_type'        => 'page',
            'post_status'      => 'publish',
            'posts_per_page'   => 10,
            'suppress_filters' => true,
        ]);
        if (empty($pages)) return null;

        if (!function_exists('pll_current_language') || !function_exists('pll_default_language')) {
            return get_permalink($pages[0]->ID);
        }

        $current_lang = pll_current_language('slug');
        $default_lang = pll_default_language('slug');

        if (function_exists('pll_get_post_language') && is_string($current_lang) && $current_lang !== '') {
            foreach ($pages as $page) {
                if (pll_get_post_language($page->ID, 'slug') === $current_lang) {
                    return get_permalink($page->ID);
                }
            }
        }

        $default_id = $pages[0]->ID;
        if (function_exists('pll_get_post_language') && is_string($default_lang) && $default_lang !== '') {
            foreach ($pages as $page) {
                if (pll_get_post_language($page->ID, 'slug') === $default_lang) {
                    $default_id = $page->ID;
                    break;
                }
            }
        }

        if (!is_string($current_lang) || $current_lang === '') {
            return get_permalink($default_id);
        }

        if (function_exists('pll_get_post')) {
            $translated_id = pll_get_post($default_id, $current_lang);
            if ($translated_id) {
                return get_permalink($translated_id);
            }
        }

        return null;
    }
}
