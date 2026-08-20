<?php

if (!defined('ABSPATH')) exit;

class ThsI18n {

    private const DEFAULT_LANG = 'vi_VN';

    private static ?array $dict = null;

    private static ?array $default_dict = null;

    private static ?string $current_lang = null;

    public const SUPPORTED_LANGS = [
        'vi_VN', 'en_US', 'de_DE', 'es_MX', 'fr_FR', 'ja', 'ko_KR', 'th', 'zh_CN'
    ];

    private const ALIASES = [
        'vi' => 'vi_VN',
        'en' => 'en_US',
        'de' => 'de_DE',
        'es' => 'es_MX',
        'fr' => 'fr_FR',
        'ja' => 'ja',
        'ko' => 'ko_KR',
        'th' => 'th',
        'zh' => 'zh_CN',
    ];

    private const DATE_FORMATS = [
        'en_US' => 'm/d/Y',
        'de_DE' => 'd.m.Y',
        'ja'    => 'Y年n月j日',
        'ko_KR' => 'Y년 n월 j일',
        'zh_CN' => 'Y年n月j日',
    ];

    private const MONTH_FORMATS = [
        'en_US' => 'F Y',
        'de_DE' => 'n.Y',
        'ja'    => 'Y年n月',
        'ko_KR' => 'Y년 n월',
        'zh_CN' => 'Y年n月',
    ];

    public static function locale_to_text(string $locale): string {
        $locale = str_replace('-', '_', trim($locale));
        $map = [
            'vi_VN' => 'Tiếng Việt',
            'vi'    => 'Tiếng Việt',
            'en_US' => 'Tiếng Anh (English)',
            'en'    => 'Tiếng Anh (English)',
            'de_DE' => 'Tiếng Đức (Deutsch)',
            'de'    => 'Tiếng Đức (Deutsch)',
            'es_MX' => 'Tiếng Tây Ban Nha (Español)',
            'es'    => 'Tiếng Tây Ban Nha (Español)',
            'fr_FR' => 'Tiếng Pháp (Français)',
            'fr'    => 'Tiếng Pháp (Français)',
            'ja_JP' => 'Tiếng Nhật (日本語)',
            'ja'    => 'Tiếng Nhật (日本語)',
            'ko_KR' => 'Tiếng Hàn (한국어)',
            'ko'    => 'Tiếng Hàn (한국어)',
            'th_TH' => 'Tiếng Thái (ไทย)',
            'th'    => 'Tiếng Thái (ไทย)',
            'zh_CN' => 'Tiếng Trung (中文)',
            'zh'    => 'Tiếng Trung (中文)',
        ];
        return $map[$locale] ?? $locale;
    }

    public static function format_date(int $day, int $month, int $year, string $locale = ''): string {
        $locale = $locale !== '' ? self::normalize($locale) : self::current_locale();
        $format = self::DATE_FORMATS[$locale] ?? 'd/m/Y';

        return strtr($format, [
            'd' => sprintf('%02d', $day),
            'm' => sprintf('%02d', $month),
            'Y' => sprintf('%04d', $year),
            'j' => (string)$day,
            'n' => (string)$month,
        ]);
    }

    public static function format_month(int $month, int $year, string $locale = ''): string {
        $locale = $locale !== '' ? self::normalize($locale) : self::current_locale();
        $format = self::MONTH_FORMATS[$locale] ?? 'n/Y';
        $month_name = DateTimeImmutable::createFromFormat('!m', sprintf('%02d', $month));

        return strtr($format, [
            'F' => $month_name instanceof DateTimeImmutable ? $month_name->format('F') : (string)$month,
            'm' => sprintf('%02d', $month),
            'Y' => sprintf('%04d', $year),
            'n' => (string)$month,
        ]);
    }

    public static function current_locale(): string {
        if (self::$current_lang !== null) {
            return self::$current_lang;
        }
        if (function_exists('pll_current_language')) {
            $pl = pll_current_language('locale');
            if (is_string($pl) && $pl !== '') {
                $normalized = self::normalize($pl);
                if ($normalized !== '') return $normalized;
            }
            $slug = pll_current_language('slug');
            if (is_string($slug) && $slug !== '') {
                $mapped = self::ALIASES[$slug] ?? null;
                if ($mapped && in_array($mapped, self::SUPPORTED_LANGS, true)) {
                    return $mapped;
                }
            }
        }
        $wp_locale = function_exists('get_locale') ? get_locale() : '';
        if (is_string($wp_locale) && $wp_locale !== '') {
            $normalized = self::normalize($wp_locale);
            if ($normalized !== '') return $normalized;
        }
        if (function_exists('determine_locale')) {
            $loc = determine_locale();
            if (is_string($loc) && $loc !== '') {
                $mapped = self::ALIASES[strtolower(substr($loc, 0, 2))] ?? null;
                if ($mapped && in_array($mapped, self::SUPPORTED_LANGS, true)) {
                    return $mapped;
                }
            }
        }
        return self::DEFAULT_LANG;
    }

    public static function normalize(string $locale): string {
        $locale = trim($locale);
        if ($locale === '') return '';
        $locale = str_replace('-', '_', $locale);
        if (in_array($locale, self::SUPPORTED_LANGS, true)) {
            return $locale;
        }
        $alias = self::ALIASES[$locale] ?? null;
        if ($alias && in_array($alias, self::SUPPORTED_LANGS, true)) {
            return $alias;
        }
        $locale_lower = strtolower($locale);
        foreach (self::SUPPORTED_LANGS as $supported) {
            if (strtolower($supported) === $locale_lower) {
                return $supported;
            }
        }
        $language = strtolower(strtok($locale_lower, '_'));
        $alias = self::ALIASES[$language] ?? null;
        if ($alias && in_array($alias, self::SUPPORTED_LANGS, true)) {
            return $alias;
        }
        return '';
    }

    public static function is_supported(string $lang): bool {
        return self::normalize($lang) !== '';
    }

    public static function set_locale(string $locale): bool {
        $normalized = self::normalize($locale);
        if ($normalized === '') {
            return false;
        }
        self::$current_lang = $normalized;
        self::$dict = null;
        return true;
    }

    public static function reset_cache(): void {
        self::$current_lang = null;
        self::$dict = null;
    }

    public static function t(string $text, string $context = ''): string {
        if ($text === '') {
            return $text;
        }
        return $text;
    }

    public static function e(string $text, string $context = ''): void {
        echo esc_html(self::t($text, $context));
    }

    public static function lookup(string $category, ?string $id, string $default = ''): string {
        if ($id === null || $id === '') return $default;
        $dict = self::category($category);
        return $dict[$id] ?? ($default !== '' ? $default : $id);
    }

    public static function lookup_map(string $category): array {
        return self::category($category);
    }

    public static function lookup_default(string $category, string $id): string {
        $dict = self::category_from_dictionary(self::default_dictionary(), $category);
        return (string)($dict[$id] ?? '');
    }

    public static function lookup_map_default(string $category): array {
        return self::category_from_dictionary(self::default_dictionary(), $category);
    }

    public static function bangDichPrompt(string $locale): array {
        $locale = self::normalize($locale);
        if ($locale === '' || $locale === self::DEFAULT_LANG) {
            return [];
        }

        $source = self::default_dictionary();
        $target = self::dictionary_for_locale($locale);
        if ($target === []) {
            return [];
        }

        $ids = [
            'life_path',
            'expression',
            'attitude',
            'birthday',
            'soul_urge',
            'personality',
            'maturity',
            'balance',
            'karmic_lessons',
            'karmic_debt',
            'period_cycles',
            'pinnacles',
            'challenges',
            'personal_year',
            'personal_month',
            'personal_day',
        ];
        $map = [
            'Chỉ số Thần số học' => self::prompt_map(
                $source,
                $target,
                'result.index',
                $ids
            ),
        ];

        return array_filter($map, static fn($items) => is_array($items) && $items !== []);
    }

    private static function prompt_map(
        array $source,
        array $target,
        string $category,
        array $ids
    ): array {
        $sourceMap = self::category_from_dictionary($source, $category);
        $targetMap = self::category_from_dictionary($target, $category);
        $result = [];
        foreach ($ids as $id) {
            $src = trim((string)($sourceMap[$id] ?? ''));
            $dst = trim((string)($targetMap[$id] ?? ''));
            if ($src !== '' && $dst !== '' && $src !== $dst) {
                $result[$src] = $dst;
            }
        }

        return $result;
    }

    private static function category(string $path): array {
        return self::category_from_dictionary(self::dictionary(), $path);
    }

    private static function category_from_dictionary(array $dictionary, string $path): array {
        $value = $dictionary;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return [];
            }
            $value = $value[$segment];
        }
        return is_array($value) ? $value : [];
    }

    private static function dictionary(): array {
        if (self::$current_lang === null) {
            self::$current_lang = self::current_locale();
        }
        $lang = self::$current_lang;

        if (self::$dict !== null && self::$dict !== []) {
            return self::$dict;
        }

        if ($lang === self::DEFAULT_LANG) {
            return self::$dict = self::default_dictionary();
        }

        $path = THAN_SO_HOC_PLUGIN_DIR . 'i18n/' . $lang . '.php';
        if (is_file($path)) {
            $loaded = include $path;
            if (is_array($loaded)) {
                self::$dict = $loaded;
            }
        } else {
            self::$dict = [];
        }
        return self::$dict;
    }

    private static function default_dictionary(): array {
        if (self::$default_dict !== null) {
            return self::$default_dict;
        }

        $path = THAN_SO_HOC_PLUGIN_DIR . 'i18n/default.php';
        if (is_file($path)) {
            $loaded = include $path;
            if (is_array($loaded)) {
                return self::$default_dict = $loaded;
            }
        }

        return self::$default_dict = [];
    }

    private static function dictionary_for_locale(string $locale): array {
        $locale = self::normalize($locale);
        if ($locale === '') {
            return [];
        }

        if ($locale === self::DEFAULT_LANG) {
            return self::default_dictionary();
        }

        $path = THAN_SO_HOC_PLUGIN_DIR . 'i18n/' . $locale . '.php';
        if (!is_file($path)) {
            return [];
        }

        $loaded = include $path;
        return is_array($loaded) ? $loaded : [];
    }
}
