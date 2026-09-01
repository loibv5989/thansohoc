<?php

if (!defined('ABSPATH')) exit;

class ThanSoHoc_Calc {

    private static array $ths_data = [];

    private static string $current_locale = '';

    private const MASTER = [11, 22];

    private const KARMIC = [13, 14, 16, 19];

    private const PROMPT_SOURCE_LOCALE = 'vi_VN';

    private static function nameProfile(string $name, string $locale): array {
        $profile = ThsName::profile($name, $locale);
        ThsName::validate_profile($profile);
        return $profile;
    }

    private static function nameGroups(string $normalizedName, string $locale): array {
        $names = preg_split('/\s+/u', trim($normalizedName), -1, PREG_SPLIT_NO_EMPTY);
        $groups = [];
        foreach ($names as $name) {
            $parts = ThsName::profile($name, $locale)['parts'] ?? [];
            if ($parts !== []) $groups[] = $parts;
        }
        return $groups;
    }

    private static function nameNumber(array $groups, ?bool $vowelsOnly = null): array {
        $groupParts = [];
        $groupRaw = [];
        $groupValues = [];

        foreach ($groups as $parts) {
            $selected = array_values(array_filter($parts, static function(array $part) use ($vowelsOnly): bool {
                if ((int)($part['value'] ?? 0) <= 0) return false;
                if ($vowelsOnly === null) return true;
                return (bool)($part['is_vowel'] ?? false) === $vowelsOnly;
            }));
            $raw = array_sum(array_column($selected, 'value'));
            $groupParts[] = $selected;
            $groupRaw[] = $raw;
            $groupValues[] = self::reduce($raw);
        }

        $raw = array_sum($groupValues);
        return [
            'parts' => $groupParts,
            'group_raw' => $groupRaw,
            'group_values' => $groupValues,
            'raw' => $raw,
            'value' => self::reduce($raw),
        ];
    }

    private static function today(string $format): string {
        return function_exists('current_time') ? current_time($format) : date($format);
    }

    private static function checkKarmic(int $num): int {
        if (in_array($num, self::KARMIC)) return $num;
        while ($num > 9) {
            $num = array_sum(str_split((string)$num));
            if (in_array($num, self::KARMIC)) return $num;
        }
        return 0;
    }

    private static function normalizeLocale(string $locale): string {
        $locale = trim($locale);
        if ($locale === '' && class_exists('ThsI18n')) {
            $locale = ThsI18n::current_locale();
        }

        $locale = class_exists('ThsI18n') ? ThsI18n::normalize($locale) : str_replace('-', '_', $locale);
        if ($locale === '') {
            throw new InvalidArgumentException('Invalid language.');
        }

        return $locale;
    }

    private static function setLocale(string $locale): string {
        self::$current_locale = self::normalizeLocale($locale);
        return self::$current_locale;
    }

    private static function currentLocale(): string {
        if (self::$current_locale !== '') {
            return self::$current_locale;
        }

        return self::setLocale('');
    }

    private static function loadData(string $locale = ''): array {
        $locale = $locale !== '' ? self::setLocale($locale) : self::currentLocale();

        return self::loadLocaleData($locale);
    }

    private static function loadLocaleData(string $locale): array {
        if (!isset(self::$ths_data[$locale])) {
            $path = THAN_SO_HOC_PLUGIN_DIR . 'data/' . $locale . '.php';
            if (!is_readable($path)) {
                throw new InvalidArgumentException('Numerology data file not found for ' . $locale . '.');
            }

            $data = require $path;
            if (!is_array($data)) {
                throw new InvalidArgumentException('Invalid numerology data file for ' . $locale . '.');
            }

            self::$ths_data[$locale] = $data;
        }

        return self::$ths_data[$locale];
    }

    private static function dayMonthFormulaForLocale(int $day, int $month, string $locale): array {
        $labels = [
            'day' => [ThsI18n::lookup('calculation.ui', 'day'), $day],
            'month' => [ThsI18n::lookup('calculation.ui', 'month'), $month],
        ];

        $parts = [];
        foreach (ThsHelper::date_input_order($locale) as $part) {
            if (isset($labels[$part])) {
                $parts[] = $labels[$part];
            }
        }

        return [
            $parts[0][0] . ' + ' . $parts[1][0],
            $parts[0][1] . ' + ' . $parts[1][1],
        ];
    }

    public static function calculate(string $name, string $dob, string $locale = ''): array {
        $data = self::loadData($locale);
        [$day, $month, $year] = ThsHelper::parse_iso_date($dob, $locale);

        $nameProfile = self::nameProfile($name, $locale);
        $clean = $nameProfile['normalized_name'];

        $nameGroups = self::nameGroups($clean, $locale);
        $expressionCalc = self::nameNumber($nameGroups);
        $soulCalc = self::nameNumber($nameGroups, true);
        $personalityCalc = self::nameNumber($nameGroups, false);

        $freq = array_fill(1, 9, 0);

        foreach ($nameProfile['parts'] as $item) {
            $l = $item['letter'];
            $val = $item['value'];
            if ($val > 0) {
                $freq[$val]++;
            }
        }

        $karmic_lessons = [];
        foreach ($freq as $num => $count) { if ($count === 0) $karmic_lessons[] = $num; }

        $max_freq = max($freq);
        $hidden_passion = [];
        if ($max_freq > 0) {
            foreach ($freq as $num => $count) { if ($count === $max_freq) $hidden_passion[] = $num; }
        }

        $subconscious_self = 9 - count($karmic_lessons);
        $balance_sum = 0;
        foreach ($nameProfile['first_parts'] as $item) {
            $balance_sum += (int)($item['value'] ?? 0);
        }

        $rDay = self::reduce($day);
        $rMonth = self::reduce($month);
        $rYear = self::reduce(array_sum(str_split((string)$year)));
        $lp_raw = $rMonth + $rDay + $rYear;

        $life_path   = self::reduce($lp_raw);
        $expression  = $expressionCalc['value'];
        $soul_urge   = $soulCalc['value'];
        $personality = $personalityCalc['value'];
        $maturity    = self::reduce($life_path + $expression);

        $currentYear = (int) self::today('Y');
        $py_raw = self::reduceToSingle($month) + self::reduceToSingle($day) + self::reduceToSingle(array_sum(str_split((string)$currentYear)));
        $personal_year  = self::reduceToSingle($py_raw);
        $personal_month = self::reduceToSingle($personal_year + (int)self::today('n'));
        $personal_day   = self::reduceToSingle($personal_month + (int)self::today('j'));

        $period_cycles = [$rMonth, $rDay, $rYear];

        $pinnacles = self::calculatePinnacles($rDay, $rMonth, $rYear, $life_path);
        $challenges = self::calculateChallenges($rDay, $rMonth, $rYear);

        $k_day = self::checkKarmic($day);
        $k_lp  = self::checkKarmic($lp_raw);
        $k_exp = self::checkKarmic($expressionCalc['raw']);
        $k_su  = self::checkKarmic($soulCalc['raw']);
        $k_per = self::checkKarmic($personalityCalc['raw']);

        $k_debt_display = [];
        if ($k_day > 0) $k_debt_display[] = $k_day;
        if ($k_lp > 0)  $k_debt_display[] = $k_lp;
        if ($k_exp > 0) $k_debt_display[] = $k_exp;
        if ($k_su > 0)  $k_debt_display[] = $k_su;
        if ($k_per > 0) $k_debt_display[] = $k_per;

        $k_debt_str = empty($k_debt_display)
            ? (string)($data['karmic_debt_meta']['none'] ?? '')
            : implode(', ', array_unique($k_debt_display));
        $k_less_str = empty($karmic_lessons)
            ? (string)($data['karmic_lessons_meta']['none_short'] ?? '')
            : implode(', ', $karmic_lessons);
        $att_raw = $day + $month;

        return [
            'life_path'        => $life_path,
            'expression'       => $expression,
            'attitude'         => self::reduceToSingle($att_raw),
            'birthday'         => $day,
            'soul_urge'        => $soul_urge,
            'personality'      => $personality,
            'maturity'         => $maturity,
            'balance'          => self::reduceToSingle($balance_sum),
            'karmic_lessons'   => $k_less_str,
            'karmic_debt'      => $k_debt_str,
            'karmic_lessons_arr'=> $karmic_lessons,
            'period_cycles'    => implode(' - ', $period_cycles),
            'pinnacles'        => implode(' - ', $pinnacles['peaks']),
            'challenges'       => implode(' - ', $challenges),
            'personal_year'    => $personal_year,
            'personal_month'   => $personal_month,
            'personal_day'     => $personal_day,
            'rational_thought' => self::reduce(self::reduce(array_sum(array_column($nameGroups[0] ?? [], 'value'))) + self::reduce($day)),
            'subconscious_self'=> $subconscious_self,
            'hidden_passion'   => $hidden_passion,
            'pinnacle_details' => $pinnacles,
            'challenge_details'=> [
                'calc_data' => [
                    self::reduceToSingle($rDay),
                    self::reduceToSingle($rMonth),
                    self::reduceToSingle($rYear),
                ],
            ],
            'karmic_debt_arr'  => [
                'birthday'    => ($k_day > 0) ? (string)$k_day : '',
                'life_path'   => ($k_lp > 0)  ? "$k_lp/$life_path" : '',
                'expression'  => ($k_exp > 0) ? "$k_exp/$expression" : '',
                'soul_urge'   => ($k_su > 0)  ? "$k_su/$soul_urge" : '',
                'personality' => ($k_per > 0) ? "$k_per/$personality" : '',
            ],
            'master' => [
                'life_path'  => in_array($life_path,  self::MASTER) ? (string)$life_path  : '',
                'expression' => in_array($expression, self::MASTER) ? (string)$expression : '',
                'soul_urge'  => in_array($soul_urge,  self::MASTER) ? (string)$soul_urge  : '',
                'attitude'   => '',
                'personality'=> in_array($personality,self::MASTER) ? (string)$personality: '',
                'maturity'   => in_array($maturity,   self::MASTER) ? (string)$maturity   : '',
                'balance'    => '',
            ],
            'easter_eggs'     => self::calculateEasterEggs($name, $dob),
        ];
    }

    private static function calculateEasterEggs(string $name, string $dob): array {
        $easter_eggs = [];
        $dob_obj = DateTime::createFromFormat('!Y-m-d', $dob);
        if (!$dob_obj || $dob_obj->format('Y-m-d') !== trim($dob)) return $easter_eggs;

        $now         = function_exists('current_datetime') ? current_datetime() : new DateTime();
        $currentYear = (int) $now->format('Y');
        $birthYear   = (int) $dob_obj->format('Y');
        $birthMonth  = (int) $dob_obj->format('n');
        $birthDay    = (int) $dob_obj->format('j');

        $currentMonth = (int) $now->format('n');
        $currentDay   = (int) $now->format('j');

        $isFuture = false;
        if ($birthYear > $currentYear) {
            $isFuture = true;
        } elseif ($birthYear === $currentYear) {
            if ($birthMonth > $currentMonth) {
                $isFuture = true;
            } elseif ($birthMonth === $currentMonth && $birthDay > $currentDay) {
                $isFuture = true;
            }
        }

        if ($isFuture) {
            $maxAllowed = (clone $now)->modify('+5 years');
            if ($dob_obj > $maxAllowed) {
                $easter_eggs[] = ['type' => 'future', 'name' => $name];
                return $easter_eggs;
            }
            return $easter_eggs;
        }

        $ageByYear = $currentYear - $birthYear;

        if ($ageByYear > 120) {
            $easter_eggs[] = ['type' => 'over120', 'name' => $name];
            return $easter_eggs;
        }

        if ($ageByYear >= 100) {
            $easter_eggs[] = ['type' => 'over100', 'name' => $name];
        }

        return $easter_eggs;
    }

    private static function getReductionLines(int $raw): array {
        $lines = [];
        $current = $raw;
        while ($current > 9 && !in_array($current, self::MASTER, true)) {
            $digits = str_split((string) $current);
            $sum = array_sum($digits);
            $lines[] = "* {$current} → " . implode('+', $digits) . " = {$sum}";
            $current = $sum;
        }
        if (in_array($current, self::MASTER, true)) {
            $single = self::reduceToSingle($current);
            $lines[] = "* " . ThsI18n::lookup('calculation.ui', 'result') . ": {$current}/{$single}";
        }
        return $lines;
    }

    private static function getSingleReductionLines(int $raw): array {
        $lines = [];
        $current = $raw;
        while ($current > 9) {
            $digits = str_split((string)$current);
            $sum = array_sum($digits);
            $lines[] = "* {$current} → " . implode('+', $digits) . " = {$sum}";
            $current = $sum;
        }
        return $lines;
    }

    private static function appendNameCalculation(array &$lines, string $label, array $calculation): void {
        $lines[] = "* {$label}:";
        foreach ($calculation['parts'] as $index => $parts) {
            if ($parts === []) continue;
            $raw = $calculation['group_raw'][$index];
            $value = $calculation['group_values'][$index];
            $suffix = $raw !== $value ? " = {$raw} → {$value}" : " = {$raw}";
            $lines[] = '  * ' . self::formatNamePartsForCalculation($parts) . $suffix;
        }

        if (count($calculation['group_values']) > 1) {
            $lines[] = '* ' . ThsI18n::lookup('calculation.ui', 'result') . ': '
                . implode('+', $calculation['group_values']) . ' = ' . $calculation['raw'];
        }
        $lines = array_merge($lines, self::getReductionLines($calculation['raw']));
    }

    private static function formatVal(int $val): string {
        if (in_array($val, self::MASTER, true)) {
            return $val . '/' . self::reduceToSingle($val);
        }
        return (string)self::reduceToSingle($val);
    }

    private static function formatNamePartsForCalculation(array $parts): string {
        if (empty($parts)) {
            return '';
        }

        $formatted = [];
        $hangulChar = '';
        $hangulValues = [];

        $flushHangul = static function(array &$formatted, string &$hangulChar, array &$hangulValues): void {
            if ($hangulChar === '' || empty($hangulValues)) {
                $hangulChar = '';
                $hangulValues = [];
                return;
            }

            $formatted[] = $hangulChar . '(' . implode('+', $hangulValues) . ')';
            $hangulChar = '';
            $hangulValues = [];
        };

        foreach ($parts as $part) {
            $letter = (string)($part['letter'] ?? '');
            $value = (int)($part['value'] ?? 0);
            if ($value <= 0 || $letter === '') {
                continue;
            }

            if (preg_match('/^(.+)\((initial|medial|final)\)$/', $letter, $matches)) {
                $char = $matches[1];
                if ($hangulChar !== '' && $hangulChar !== $char) {
                    $flushHangul($formatted, $hangulChar, $hangulValues);
                }

                $hangulChar = $char;
                $hangulValues[] = (string)$value;
                continue;
            }

            $flushHangul($formatted, $hangulChar, $hangulValues);
            $formatted[] = "{$letter}({$value})";
        }

        $flushHangul($formatted, $hangulChar, $hangulValues);

        return implode(' + ', $formatted);
    }

    public static function spinText(string $text, ?callable $rand = null): string {
        if (strpos($text, '{') === false) return $text;
        if ($rand === null) $rand = fn($min, $max) => mt_rand($min, $max);

        static $history = [];

        return preg_replace_callback('/\{([^{}]+)\}/', function($matches) use ($rand, &$history) {
            $parts = explode('|', $matches[1]);
            $count = count($parts);
            if ($count <= 1) return $parts[0] ?? '';

            $hash = md5($matches[1]);
            if (!isset($history[$hash])) {
                $history[$hash] = [];
            }

            $available = array_diff(range(0, $count - 1), $history[$hash]);

            if (empty($available)) {
                $history[$hash] = [];
                $available = range(0, $count - 1);
            }

            $available = array_values($available);
            $pickIndex = $available[$rand(0, count($available) - 1)];

            $history[$hash][] = $pickIndex;

            return $parts[$pickIndex];
        }, $text);
    }

    public static function getHint(string $key, string $value, string $locale = ''): string {
        $resolvedLocale = $locale !== '' ? self::setLocale($locale) : self::currentLocale();
        $dict = self::loadLocaleData($resolvedLocale);

        if (empty($dict) || $value === '' || $value === '?' || stripos($value, 'Không') !== false || stripos($value, 'No ') === 0 || stripos($value, 'None') === 0) {
            return '';
        }

        $contextMap = [
            'life_path'      => ['prefix' => 'Số Chủ Đạo',                  'type' => 'core'],
            'expression'     => ['prefix' => 'Số Sứ Mệnh / Biểu Đạt',       'type' => 'core'],
            'attitude'       => ['prefix' => 'Số Thái Độ',                  'type' => 'action'],
            'birthday'       => ['prefix' => 'Số Ngày Sinh',                'type' => 'action'],
            'personality'    => ['prefix' => 'Số Nhân Cách',                'type' => 'action'],
            'soul_urge'      => ['prefix' => 'Số Linh Hồn / Soul Urge',     'type' => 'inner'],
            'balance'        => ['prefix' => 'Số Cân Bằng',                 'type' => 'advice'],
            'maturity'       => ['prefix' => 'Số Trưởng Thành',             'type' => 'advice'],
            'karmic_lessons' => ['prefix' => 'Số Thiếu',                    'type' => 'lesson'],
            'karmic_debt'    => ['prefix' => 'Nợ Nghiệp',                   'type' => 'advice'],
            'period_cycles'  => ['prefix' => '3 Chu Kỳ Vòng Đời',           'type' => 'period'],
            'pinnacles'      => ['prefix' => '4 Đỉnh Cao Cuộc Đời',         'type' => 'period'],
            'challenges'     => ['prefix' => '4 Thử Thách Cuộc Đời',        'type' => 'negative'],
            'personal_year'  => ['prefix' => 'Năm Cá Nhân',                 'type' => 'period'],
            'personal_month' => ['prefix' => 'Tháng Cá Nhân',               'type' => 'period'],
            'personal_day'   => ['prefix' => 'Ngày Cá Nhân',                'type' => 'period'],
        ];

        if (!isset($contextMap[$key])) return '';

        $type   = $contextMap[$key]['type'];

        $noMasterKeys = ['attitude', 'balance', 'personal_year', 'personal_month', 'personal_day', 'challenges'];
        $allowMaster  = !in_array($key, $noMasterKeys);
        $isShortUI    = ($key === 'pinnacles' || $key === 'challenges' || $key === 'period_cycles');

        $resolveNumber = function($raw, $allowMaster) use ($dict) {
            $numStr = preg_replace('/\D/', '', explode('/', $raw)[0]);
            if ($numStr === '') return null;
            $num = (int)$numStr;

            if (!$allowMaster) {
                while ($num > 9) $num = array_sum(str_split((string)$num));
                return $num;
            }

            while (!isset($dict['hint'][$num]) && $num > 9 && !in_array($num, [11, 22, 13, 14, 16, 19, 15])) {
                $num = array_sum(str_split((string)$num));
            }
            return $num;
        };

        $getText = function($num, $type, $isShortUI) use ($dict, $key) {
            if ($key === 'karmic_debt') {
                return $dict['karmic_debt'][$num] ?? '';
            } elseif ($key === 'karmic_lessons') {
                return $dict['karmic_lessons'][$num] ?? '';
            } elseif ($key === 'birthday') {
                return $dict['birthday'][$num] ?? '';
            } elseif ($key === 'challenges') {
                $text = (string) ($dict['challenges'][$num] ?? '');
                if ($text === '') {
                    return '';
                }
                // UI đã gắn "Thử thách 1/2/3/4" — bỏ prefix lặp "Thử thách số N:" / "Challenge N:" trong data.
                $stripped = preg_replace(
                    '/^.{0,40}?' . preg_quote((string) $num, '/') . '\s*[:：]\s*/us',
                    '',
                    $text,
                    1
                );
                return (is_string($stripped) && $stripped !== '') ? $stripped : $text;
            }

            if (!isset($dict['hint'][$num])) return '';
            $h = $dict['hint'][$num];
            $text = $h[$type] ?? $h['core'] ?? $h['action'] ?? $h['advice'] ?? $h['negative'] ?? '';

            if ($text === '') return '';

            if ($isShortUI) {
                $sk = 'short_' . $type;
                if (isset($h[$sk])) {
                    $text = $h[$sk];
                } else {
                    $spunText = ThanSoHoc_Calc::spinText($text);
                    $text = trim(explode(',', $spunText)[0]);
                }
            }

            return ThanSoHoc_Calc::spinText($text);
        };

        $buildText = function($numbers, $separator, $type) use ($resolveNumber, $getText, $allowMaster, $isShortUI) {
            $results = [];
            foreach ($numbers as $n) {
                $num = $resolveNumber(trim($n), $allowMaster);
                if ($num !== null && ($txt = $getText($num, $type, $isShortUI))) {
                    $results[] = $txt;
                }
            }
            return implode($separator, $results);
        };

        if ($key === 'birthday') {
            $numStr = preg_replace('/\D/', '', explode('/', $value)[0]);
            $num = $numStr === '' ? null : (int)$numStr;
            $baseText = $num !== null ? $getText($num, $type, false) : '';
        } elseif ($key === 'challenges') {
            $nums = explode('-', $value);
            $results = [];
            $firstLabelByNum = [];
            $uiLookup = static function (string $uiKey) use ($resolvedLocale): string {
                return $resolvedLocale === self::PROMPT_SOURCE_LOCALE
                    ? ThsI18n::lookup_default('calculation.ui', $uiKey)
                    : ThsI18n::lookup('calculation.ui', $uiKey);
            };
            foreach ($nums as $index => $n) {
                $num = $resolveNumber(trim($n), $allowMaster);
                if ($num === null) {
                    continue;
                }
                $text = $getText($num, $type, $isShortUI);
                if ($text === '') {
                    continue;
                }
                $challengeLabel = $uiLookup('challenge_' . ($index + 1));
                // Hiện số trong nhãn (4-4-0-0) để thấy vì sao giai đoạn khác nhau có thể cùng bài học.
                $labelWithNum = $challengeLabel . ' (' . $num . ')';
                if (isset($firstLabelByNum[$num])) {
                    $sameTpl = $uiLookup('challenge_same_as');
                    if ($sameTpl === '') {
                        $sameTpl = 'Cùng bài học với %s.';
                    }
                    $results[] = $labelWithNum . ': ' . sprintf($sameTpl, $firstLabelByNum[$num]);
                } else {
                    $firstLabelByNum[$num] = $labelWithNum;
                    $results[] = $labelWithNum . ': ' . $text;
                }
            }
            $baseText = implode('<br>- ', $results);
        } elseif ($key === 'pinnacles' || $key === 'period_cycles') {
            $nums = explode('-', $value);
            $baseText = $buildText($nums, ' → ', $type);
        } elseif ($key === 'karmic_lessons') {
            $nums = explode(',', $value);
            $baseText = $buildText($nums, '<br>- ', $type);
        } elseif ($key === 'karmic_debt') {
            $nums = explode(',', $value);
            $baseText = $buildText($nums, '<br>- ', $type);
        } else {
            $num = $resolveNumber($value, $allowMaster);
            $baseText = $num !== null ? $getText($num, $type, false) : '';
        }

        if ($baseText === '' || $baseText === '?') return '';

        return $baseText;
    }

    public static function getHintForPrompt(
        string $key,
        string $value,
        string $locale = self::PROMPT_SOURCE_LOCALE
    ): string {
        $currentLocale = self::currentLocale();
        try {
            $hint = self::getHint($key, $value, $locale);
        } finally {
            self::setLocale($currentLocale);
        }
        if ($hint === '') {
            return '';
        }

        $hint = (string) preg_replace('/<br\s*\/?>(?:\s*)?-(?:\s*)?/i', "\n- ", $hint);
        $hint = (string) preg_replace('/<br\s*\/?>/i', "\n", $hint);

        return trim(strip_tags($hint));
    }

    public static function formatKarmicDebtForPrompt(array $numbers): string {
        $debts = [];
        $hasDebtData = isset($numbers['karmic_debt_arr'])
            && is_array($numbers['karmic_debt_arr']);

        if ($hasDebtData) {
            foreach ($numbers['karmic_debt_arr'] as $debt) {
                $debt = trim((string) $debt);
                if ($debt !== '') {
                    $debts[] = $debt;
                }
            }
        }

        if ($debts !== []) {
            return implode(', ', array_values(array_unique($debts)));
        }

        if ($hasDebtData) {
            $data = self::loadLocaleData(self::PROMPT_SOURCE_LOCALE);
            return (string)($data['karmic_debt_meta']['none'] ?? '');
        }

        $value = (string)($numbers['karmic_debt'] ?? '');
        return (string) preg_replace_callback('/\b(13|14|16|19)\b/', static function (array $match): string {
            return $match[1] . '/' . self::reduceToSingle((int)$match[1]);
        }, $value);
    }

    public static function formatKarmicLessonsForPrompt(array $numbers): string {
        if (!isset($numbers['karmic_lessons_arr']) || !is_array($numbers['karmic_lessons_arr'])) {
            return (string)($numbers['karmic_lessons'] ?? '');
        }

        $lessons = array_values(array_filter(
            array_map(
                static fn($lesson): string => trim((string)$lesson),
                $numbers['karmic_lessons_arr']
            ),
            static fn(string $lesson): bool => $lesson !== ''
        ));
        if ($lessons !== []) {
            return implode(', ', $lessons);
        }

        $data = self::loadLocaleData(self::PROMPT_SOURCE_LOCALE);
        return (string)($data['karmic_lessons_meta']['none_short'] ?? '');
    }

    public static function buildCalculation(string $name, string $dob, array $numbers, string $locale = ''): string {
        $locale = $locale !== '' ? self::setLocale($locale) : self::currentLocale();
        ThsI18n::set_locale($locale);
        [$day, $month, $year] = ThsHelper::parse_iso_date($dob, $locale);
        $nameProfile = self::nameProfile($name, $locale);
        $clean = $nameProfile['normalized_name'];
        $displayName = esc_html($clean);
        $currentYear = (int) self::today('Y');
        $dobDisplay = ThsHelper::format_date_for_locale($day, $month, $year, $locale);
        $personalYearDateDisplay = ThsHelper::format_date_for_locale($day, $month, $currentYear, $locale);

        $rDay = self::reduce($day);
        $rMonth = self::reduce($month);
        $rYear = self::reduce(array_sum(str_split((string)$year)));

        $lp_disp  = self::formatVal($numbers['life_path']);
        $exp_disp = self::formatVal($numbers['expression']);
        $su_disp  = ((int)$numbers['soul_urge'] > 0) ? self::formatVal($numbers['soul_urge']) : ThsI18n::lookup('calculation.ui', 'none');
        $per_disp = ((int)$numbers['personality'] > 0) ? self::formatVal($numbers['personality']) : ThsI18n::lookup('calculation.ui', 'none');

        $nameGroups = self::nameGroups($clean, $locale);
        $expressionCalc = self::nameNumber($nameGroups);
        $soulCalc = self::nameNumber($nameGroups, true);
        $personalityCalc = self::nameNumber($nameGroups, false);

        $lines = [ThsI18n::lookup('calculation.ui', 'norm_name') . " {$displayName}", ""];

        $yearRaw = array_sum(str_split((string)$year));
        $lpRaw = $rMonth + $rDay + $rYear;
        $lines[] = "**① " . ThsI18n::lookup('calculation.ui', 'item_11') . " {$lp_disp}**";
        $lines[] = "* " . ThsI18n::lookup('calculation.ui', 'birth_date') . ": {$dobDisplay}";
        $lines[] = "* " . ThsI18n::lookup('calculation.ui', 'month') . ": {$month} → {$rMonth}; "
            . ThsI18n::lookup('calculation.ui', 'day') . ": {$day} → {$rDay}; "
            . ThsI18n::lookup('calculation.ui', 'year') . ": {$year} → {$yearRaw} → {$rYear}";
        $lines[] = "* {$rMonth}+{$rDay}+{$rYear} = {$lpRaw}";
        $lines = array_merge($lines, self::getReductionLines($lpRaw));
        $lines[] = "";

        $attTotal = $day + $month;
        [$attLabel, $attFormula] = self::dayMonthFormulaForLocale($day, $month, $locale);
        $lines[] = "**② " . ThsI18n::lookup('result.index', 'attitude') . " ({$numbers['attitude']})**";
        $lines[] = "* {$attLabel}: {$attFormula} = {$attTotal}";
        $lines = array_merge($lines, self::getSingleReductionLines($attTotal));
        $lines[] = "";

        $lines[] = "**③ " . ThsI18n::lookup('result.index', 'birthday') . " ({$numbers['birthday']})**";
        $lines[] = "* " . ThsI18n::lookup('calculation.ui', 'keep_birth') . ": {$day}\n";

        $lines[] = "**④ " . ThsI18n::lookup('calculation.ui', 'item_6') . " {$su_disp}**";
        self::appendNameCalculation($lines, ThsI18n::lookup('calculation.ui', 'vowels'), $soulCalc);
        $lines[] = "";

        $lines[] = "**⑤ " . ThsI18n::lookup('result.index', 'expression') . " {$exp_disp}**";
        self::appendNameCalculation($lines, ThsI18n::lookup('calculation.ui', 'letter_sum'), $expressionCalc);
        $lines[] = "";

        $lines[] = "**⑥ " . ThsI18n::t('Số Nhân Cách') . " {$per_disp}** — " . ThsI18n::lookup('calculation.ui', 'cons_sum');
        self::appendNameCalculation($lines, ThsI18n::lookup('calculation.ui', 'consonants'), $personalityCalc);
        $lines[] = "";

        $matRaw = $numbers['life_path'] + $numbers['expression'];
        $lines[] = "**⑦ " . ThsI18n::lookup('result.index', 'maturity') . " " . self::formatVal($numbers['maturity']) . "**";
        $lines[] = "* " . ThsI18n::lookup('calculation.ui', 'item_11') . " ({$numbers['life_path']}) + " . ThsI18n::lookup('result.index', 'expression') . " ({$numbers['expression']}) = {$matRaw}";
        $lines = array_merge($lines, self::getReductionLines($matRaw));
        $lines[] = "";

        $py_disp = self::formatVal($numbers['personal_year']);
        $personalMonthBase = self::reduceToSingle($month);
        $personalDayBase = self::reduceToSingle($day);
        $personalYearDigitSum = array_sum(str_split((string)$currentYear));
        $personalYearBase = self::reduceToSingle($personalYearDigitSum);
        $pyRaw = $personalMonthBase + $personalDayBase + $personalYearBase;
        $lines[] = "**⑧ " . ThsI18n::lookup('result.index', 'personal_year') . " {$currentYear} ({$py_disp})**";
        $lines[] = "* {$personalYearDateDisplay}: {$personalMonthBase}+{$personalDayBase}+{$personalYearBase} = {$pyRaw}";
        $lines = array_merge($lines, self::getSingleReductionLines($pyRaw));
        $lines[] = "";

        $lines[] = "**⑨ " . ThsI18n::lookup('result.index', 'period_cycles') . " ({$numbers['period_cycles']})**";
        $lines[] = "* " . ThsI18n::lookup('calculation.ui', 'cycle_1') . ": {$month} → {$rMonth}";
        $lines[] = "* " . ThsI18n::lookup('calculation.ui', 'cycle_2') . ": {$day} → {$rDay}";
        $lines[] = "* " . ThsI18n::lookup('calculation.ui', 'cycle_3') . ": {$year} → " . implode('+', str_split((string)$year)) . " = {$yearRaw}";
        $lines = array_merge($lines, self::getReductionLines($yearRaw));
        $lines[] = "";

        [$rpDay, $rpMonth, $rpYear, $p1, $p2, $p3, $p4] = $numbers['pinnacle_details']['calc_data'];
        [$p1Raw, $p2Raw, $p3Raw, $p4Raw] = $numbers['pinnacle_details']['raw'] ?? [
            $rpMonth + $rpDay,
            $rpDay + $rpYear,
            $p1 + $p2,
            $rpMonth + $rpYear,
        ];
        $lines[] = "**⑩ " . ThsI18n::lookup('result.index', 'pinnacles') . " ({$numbers['pinnacles']})**";
        $lines[] = "* " . ThsI18n::lookup('calculation.ui', 'reduce') . ": " . ThsI18n::lookup('calculation.ui', 'day') . " {$day}→{$rpDay}, " . ThsI18n::lookup('calculation.ui', 'month') . " {$month}→{$rpMonth}, " . ThsI18n::lookup('calculation.ui', 'year') . " {$year}→{$rpYear}";
        $lines[] = "* " . ThsI18n::lookup('calculation.ui', 'peak_1') . ": " . ThsI18n::lookup('calculation.ui', 'month') . "({$rpMonth}) + " . ThsI18n::lookup('calculation.ui', 'day') . "({$rpDay}) = {$p1Raw} → {$p1}";
        $lines[] = "* " . ThsI18n::lookup('calculation.ui', 'peak_2') . ": " . ThsI18n::lookup('calculation.ui', 'day') . "({$rpDay}) + " . ThsI18n::lookup('calculation.ui', 'year') . "({$rpYear}) = {$p2Raw} → {$p2}";
        $lines[] = "* " . ThsI18n::lookup('calculation.ui', 'peak_3') . ": " . ThsI18n::lookup('calculation.ui', 'peak_1') . "({$p1}) + " . ThsI18n::lookup('calculation.ui', 'peak_2') . "({$p2}) = {$p3Raw} → {$p3}";
        $lines[] = "* " . ThsI18n::lookup('calculation.ui', 'peak_4') . ": " . ThsI18n::lookup('calculation.ui', 'month') . "({$rpMonth}) + " . ThsI18n::lookup('calculation.ui', 'year') . "({$rpYear}) = {$p4Raw} → {$p4}";
        $lines[] = "";

        $cData = array_map('trim', explode('-', $numbers['challenges']));
        [$rcDay, $rcMonth, $rcYear] = $numbers['challenge_details']['calc_data'] ?? [
            self::reduceToSingle($rpDay),
            self::reduceToSingle($rpMonth),
            self::reduceToSingle($rpYear),
        ];
        $lines[] = "**⑪ " . ThsI18n::lookup('result.index', 'challenges') . " ({$numbers['challenges']})**";
        $lines[] = "* " . ThsI18n::lookup('calculation.ui', 'challenge_1') . ": |" . ThsI18n::lookup('calculation.ui', 'month') . "({$rcMonth}) - " . ThsI18n::lookup('calculation.ui', 'day') . "({$rcDay})| = {$cData[0]}";
        $lines[] = "* " . ThsI18n::lookup('calculation.ui', 'challenge_2') . ": |" . ThsI18n::lookup('calculation.ui', 'day') . "({$rcDay}) - " . ThsI18n::lookup('calculation.ui', 'year') . "({$rcYear})| = {$cData[1]}";
        $lines[] = "* " . ThsI18n::lookup('calculation.ui', 'challenge_3') . ": |" . ThsI18n::lookup('calculation.ui', 'c1') . " - " . ThsI18n::lookup('calculation.ui', 'c2') . "| = {$cData[2]}";
        $lines[] = "* " . ThsI18n::lookup('calculation.ui', 'challenge_4') . ": |" . ThsI18n::lookup('calculation.ui', 'month') . "({$rcMonth}) - " . ThsI18n::lookup('calculation.ui', 'year') . "({$rcYear})| = {$cData[3]}";
        $lines[] = "";

        $bal = $numbers['balance'];
        $lines[] = "**⑫ " . ThsI18n::lookup('result.index', 'balance') . " ({$bal})**";
        $balParts = [];
        $balSum = 0;
        foreach ($nameProfile['first_parts'] as $item) {
            $l = $item['letter'];
            $v = (int)($item['value'] ?? 0);
            if ($v > 0) {
                $balParts[] = "{$l}({$v})";
                $balSum += $v;
            }
        }
        $lines[] = "* " . ThsI18n::lookup('calculation.ui', 'initials') . ": " . (empty($balParts) ? '(' . ThsI18n::lookup('calculation.ui', 'none') . ')' : implode(' + ', $balParts) . " = {$balSum}");
        $lines = array_merge($lines, self::getSingleReductionLines($balSum));
        $lines[] = "";

        $lines[] = "**⑬ " . ThsI18n::lookup('calculation.ui', 'passion_section') . "**";
        if (!empty($numbers['hidden_passion'])) {
            $lines[] = "* " . ThsI18n::lookup('calculation.ui', 'passion') . ": " . implode(', ', $numbers['hidden_passion']) . " (" . ThsI18n::lookup('calculation.ui', 'most_common') . ")";
        } else {
            $lines[] = "* " . ThsI18n::lookup('calculation.ui', 'passion') . ": " . ThsI18n::lookup('calculation.ui', 'unknown');
        }
        $lines[] = "* " . ThsI18n::lookup('calculation.ui', 'self_awareness') . ": {$numbers['subconscious_self']} (9 - " . ThsI18n::lookup('calculation.ui', 'karmic_lessons') . ")";
        $lines[] = "";

        $kDebt = $numbers['karmic_debt'];
        $kLess = $numbers['karmic_lessons'];
        $karmicDebtLabels = [
            'birthday'    => ThsI18n::lookup('calculation.labels', 'birthday_index', 'Chỉ số ngày sinh'),
            'life_path'   => ThsI18n::lookup('calculation.labels', 'life_path_index', 'Chỉ số chủ đạo'),
            'expression'  => ThsI18n::lookup('calculation.labels', 'expression_index', 'Chỉ số sứ mệnh'),
            'soul_urge'   => ThsI18n::lookup('calculation.labels', 'soul_urge_index', 'Chỉ số linh hồn'),
            'personality' => ThsI18n::lookup('calculation.labels', 'personality_index', 'Chỉ số nhân cách'),
        ];
        $karmicDebtLabel = ThsI18n::lookup('result.index', 'karmic_debt');
        $missingNumbersLabel = ThsI18n::lookup('result.index', 'karmic_lessons', 'Số Thiếu');
        $karmicDebtNumberLabel = ThsI18n::lookup('calculation.labels', 'karmic_debt_number', 'Số nợ nghiệp');
        $missingNumberIndexLabel = ThsI18n::lookup('calculation.labels', 'missing_number_index', 'Chỉ số thiếu');

        $lines[] = "**⑭ {$karmicDebtLabel}**";
        $lines[] = "* {$karmicDebtNumberLabel}: {$kDebt}";
        if (!empty($numbers['karmic_debt_arr'])) {
            foreach ($numbers['karmic_debt_arr'] as $key => $val) {
                if (!empty($val)) {
                    $label = $karmicDebtLabels[$key] ?? $key;
                    $lines[] = "* {$label}: {$val}";
                }
            }
        }
        $lines[] = "";
        $lines[] = "**⑮ {$missingNumbersLabel}**";
        $lines[] = "* {$missingNumberIndexLabel}: {$kLess}";

        return self::calcToHtml(implode("\n", $lines));
    }

    private static function calculatePinnacles(int $rDay, int $rMonth, int $rYear, int $life_path): array {
        $p1Raw = $rMonth + $rDay;
        $p2Raw = $rDay + $rYear;
        $p1 = self::reduce($p1Raw);
        $p2 = self::reduce($p2Raw);
        $p3Raw = $p1 + $p2;
        $p4Raw = $rMonth + $rYear;
        $p3 = self::reduce($p3Raw);
        $p4 = self::reduce($p4Raw);
        return [
            'peaks' => [$p1, $p2, $p3, $p4],
            'age_peak_1' => (36 - self::reduceToSingle($life_path)),
            'calc_data' => [$rDay, $rMonth, $rYear, $p1, $p2, $p3, $p4],
            'raw' => [$p1Raw, $p2Raw, $p3Raw, $p4Raw],
        ];
    }

    private static function calculateChallenges(int $rDay, int $rMonth, int $rYear): array {
        $rDay = self::reduceToSingle($rDay);
        $rMonth = self::reduceToSingle($rMonth);
        $rYear = self::reduceToSingle($rYear);

        return [abs($rMonth - $rDay), abs($rDay - $rYear), abs(abs($rMonth - $rDay) - abs($rDay - $rYear)), abs($rMonth - $rYear)];
    }

    private static function reduce(int $num): int {
        while ($num > 9 && !in_array($num, self::MASTER, true)) { $num = array_sum(str_split((string) $num)); }
        return $num;
    }

    private static function reduceToSingle(int $num): int {
        while ($num > 9) { $num = array_sum(str_split((string) $num)); }
        return $num;
    }

    public static function calcToHtml(string $raw): string {
        $raw = str_replace('->', '→', preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $raw));
        $html = ''; $groupLines = [];
        foreach (explode("\n", $raw) as $line) {
            $trimmed = rtrim($line);
            if ($trimmed === '') { if (!empty($groupLines)) { $html .= '<div class="ths-calc-group">' . implode('', $groupLines) . '</div>'; $groupLines = []; } continue; }
            $stripped = ltrim($trimmed);
            $indent = strlen($trimmed) - strlen($stripped);
            if (preg_match('/^\*\s+(.+)$/', $stripped, $m)) {
                $cls = $indent >= 2 ? ' class="ths-calc-sub"' : '';
                $groupLines[] = '<p' . $cls . '>▸ ' . $m[1] . '</p>';
            } else { $groupLines[] = '<p>' . $stripped . '</p>'; }
        }
        if (!empty($groupLines)) $html .= '<div class="ths-calc-group">' . implode('', $groupLines) . '</div>';
        return $html;
    }
}

