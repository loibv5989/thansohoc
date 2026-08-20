<?php

if (!defined('ABSPATH')) exit;

class ThanSoHoc_Prompt {

    private static function today(string $format): string {
        return function_exists('current_time') ? current_time($format) : date($format);
    }

    private static function currentDate(): DateTimeImmutable {
        if (function_exists('current_datetime')) {
            return current_datetime();
        }

        return new DateTimeImmutable('now');
    }

    private static function parseDob(string $dob): ?DateTimeImmutable {
        $dob = trim($dob);
        if ($dob === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dob);
        if ($date instanceof DateTimeImmutable && $date->format('Y-m-d') === $dob) {
            return $date;
        }

        return null;
    }

    private static function currentAge(string $dob): ?int {
        $birthDate = self::parseDob($dob);
        if (!$birthDate instanceof DateTimeImmutable) {
            return null;
        }

        $today = self::currentDate();
        if ($birthDate > $today) {
            return null;
        }

        return $birthDate->diff($today)->y;
    }

    private static function reduceToSingle(int $number): int {
        while ($number > 9) {
            $number = array_sum(str_split((string) $number));
        }

        return $number;
    }

    private static function numberSequence(array $numbers, string $key): array {
        return array_map('intval', preg_split('/\s*-\s*/', trim(self::value($numbers, $key))) ?: []);
    }

    private static function activeTimingLine(array $numbers, ?int $age): string {
        if ($age === null) {
            return '';
        }

        $periods = self::numberSequence($numbers, 'period_cycles');
        $pinnacles = self::numberSequence($numbers, 'pinnacles');
        $challenges = self::numberSequence($numbers, 'challenges');
        if (count($periods) < 3 || count($pinnacles) < 4 || count($challenges) < 4) {
            return '';
        }

        if ($age <= 28) {
            $periodLabel = 'Chu Kỳ Vòng Đời 1 (0-28 tuổi)';
            $periodValue = $periods[0];
        } elseif ($age <= 56) {
            $periodLabel = 'Chu Kỳ Vòng Đời 2 (29-56 tuổi)';
            $periodValue = $periods[1];
        } else {
            $periodLabel = 'Chu Kỳ Vòng Đời 3 (từ 57 tuổi trở lên)';
            $periodValue = $periods[2];
        }

        $lifePath = self::reduceToSingle((int) self::value($numbers, 'life_path'));
        $firstEnd = 36 - $lifePath;
        $ranges = [
            [0, $firstEnd],
            [$firstEnd + 1, $firstEnd + 9],
            [$firstEnd + 10, $firstEnd + 18],
            [$firstEnd + 19, null],
        ];
        $index = 3;
        foreach ($ranges as $i => [$start, $end]) {
            if ($age >= $start && ($end === null || $age <= $end)) {
                $index = $i;
                break;
            }
        }

        [$start, $end] = $ranges[$index];
        $rangeText = $end === null ? "từ {$start} tuổi trở lên" : "{$start}-{$end} tuổi";
        $pinnacleNumber = $index + 1;

        return "- Giai đoạn hiện tại: {$periodLabel}: {$periodValue}; Đỉnh Cao {$pinnacleNumber} ({$rangeText}): {$pinnacles[$index]}; Thử Thách {$pinnacleNumber} ({$rangeText}): {$challenges[$index]}";
    }

    private static function value(array $numbers, string $key): string {
        $value = $numbers[$key] ?? '';
        if (is_array($value)) {
            return implode(', ', $value);
        }
        return (string) $value;
    }

    private static function displayNameForPrompt(string $name, string $locale): string {
        return class_exists('ThsName') ? ThsName::display_name_with_normalized($name, $locale) : trim($name);
    }

    private static function displayValueForPrompt(array $numbers, string $key): string {
        return match ($key) {
            'karmic_debt' => ThanSoHoc_Calc::formatKarmicDebtForPrompt($numbers),
            'karmic_lessons' => ThanSoHoc_Calc::formatKarmicLessonsForPrompt($numbers),
            default => self::value($numbers, $key),
        };
    }

    private static function payload_value(array $numbers, string $key): string {
        $value = self::value($numbers, $key);
        $hint = ThanSoHoc_Calc::getHintForPrompt($key, $value);
        $displayValue = self::displayValueForPrompt($numbers, $key);

        return $displayValue . ($hint !== '' ? " | {$hint}" : '');
    }

    private static function hasFilledValue(array $values): bool {
        foreach ($values as $value) {
            if (is_array($value)) {
                if (self::hasFilledValue($value)) {
                    return true;
                }
                continue;
            }

            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private static function hasKarmicDebt(array $numbers): bool {
        if (isset($numbers['karmic_debt_arr']) && is_array($numbers['karmic_debt_arr'])) {
            return self::hasFilledValue($numbers['karmic_debt_arr']);
        }

        $value = trim(self::value($numbers, 'karmic_debt'));
        return $value !== '' && stripos($value, 'không') !== 0;
    }

    private static function hasKarmicLessons(array $numbers): bool {
        if (isset($numbers['karmic_lessons_arr']) && is_array($numbers['karmic_lessons_arr'])) {
            return self::hasFilledValue($numbers['karmic_lessons_arr']);
        }

        $value = trim(self::value($numbers, 'karmic_lessons'));
        return $value !== '' && stripos($value, 'không') !== 0;
    }

    private static function karmicSections(array $numbers): string {
        $sections = [];

        if (self::hasKarmicDebt($numbers)) {
            $sections[] = <<<SECTION
### Nợ Nghiệp
Luận Karmic Debt (Nợ Nghiệp): 
Giữ nguyên dạng kép như 13/4, 14/5, 16/7 hoặc 19/1.
Không quy kết quá khứ, không nói từng làm gì, không dùng ngôn ngữ tội lỗi, nghiệp báo, trả giá, lạm dụng hoặc thiếu trách nhiệm.
Chỉ luận Nợ Nghiệp như khuynh hướng dễ lệch, bài học cần nhận diện và cách số nợ tương tác với các chỉ số nổi bật trong bản đồ.
Ví dụ 14/5 -> trọng tâm là tự do, giới hạn cá nhân, cam kết, sự phân tán và khả năng tự điều chỉnh. Không viết rằng người dùng từng lạm dụng tự do.
SECTION;
        }

        if (self::hasKarmicLessons($numbers)) {
            $sections[] = <<<SECTION
### Chỉ số thiếu
Luận Karmic Lessons (Bài Học Nghiệp, Số Thiếu):
Số Thiếu không phải khuyết điểm cố định mà là vùng kỹ năng chưa ổn định hoặc khó tự bộc lộ.
Nếu một số nằm trong Số Thiếu nhưng lại xuất hiện ở chỉ số phụ như Tư Duy Lý Trí, Bản Ngã Tiềm Thức hoặc Đam Mê Ẩn Giấu, phải phân biệt rõ rằng số đó không thiếu hoàn toàn, mà chỉ xuất hiện ở chỉ số phụ.
Không dùng Số Thiếu để kết luận giàu nghèo, hôn nhân, sức khỏe hoặc giá trị con người.
SECTION;
        }

        if (count($sections) === 2) {
            $sections[] = <<<SECTION
Viết một đoạn blockquote (>) tổng hợp mối liên hệ giữa Nợ Nghiệp và Số Thiếu. 
Nêu sự tương tác giữa bài học của Nợ Nghiệp và vùng kỹ năng chưa ổn định của Số Thiếu. 
Không dùng các cụm như vòng lặp, điểm mù, chi phối hành vi, nghiệp báo, trả giá, lỗi quá khứ.
Không lặp lại y nguyên ý của phần "Nợ nghiệp" đã viết, hãy diễn giải theo cách khác để tránh lặp mẫu câu.
SECTION;
        }

        return implode("\n\n", $sections);
    }

    private static function build_chart_info_payload(array $numbers, string $name, string $dob, string $locale): array {
        $age = self::currentAge($dob);
        $labels = ThsI18n::lookup_map_default('result.index');
        $label = static fn(string $key, string $fallback): string => trim((string)($labels[$key] ?? '')) ?: $fallback;
        $personalInfo = [
            'Họ tên' => self::displayNameForPrompt($name, $locale),
            'Ngày luận giải' => self::today('Y-m-d') . ' (YYYY-MM-DD)',
        ];
        if ($age !== null) {
            $personalInfo['Tuổi hiện tại'] = $age;
        }

        $mainNumbers = [
            $label('life_path', 'Số Chủ Đạo') => self::payload_value($numbers, 'life_path'),
            $label('expression', 'Số Sứ Mệnh') => self::payload_value($numbers, 'expression'),
            $label('attitude', 'Số Thái Độ') => self::payload_value($numbers, 'attitude'),
            $label('birthday', 'Số Ngày Sinh') => self::payload_value($numbers, 'birthday'),
            $label('soul_urge', 'Số Linh Hồn') => self::payload_value($numbers, 'soul_urge'),
            $label('personality', 'Số Nhân Cách') => self::payload_value($numbers, 'personality'),
            $label('maturity', 'Số Trưởng Thành') => self::payload_value($numbers, 'maturity'),
            $label('balance', 'Số Cân Bằng') => self::payload_value($numbers, 'balance'),
            $label('karmic_lessons', 'Bài Học Nghiệp / Số Thiếu') => self::payload_value($numbers, 'karmic_lessons'),
            $label('karmic_debt', 'Nợ Nghiệp') => self::payload_value($numbers, 'karmic_debt'),
        ];
        $timingNumbers = [
            $label('period_cycles', '3 Chu Kỳ Vòng Đời') => self::payload_value($numbers, 'period_cycles'),
            $label('pinnacles', '4 Đỉnh Cao Cuộc Đời') => self::payload_value($numbers, 'pinnacles'),
            $label('challenges', '4 Thử Thách Cuộc Đời') => self::payload_value($numbers, 'challenges'),
            $label('personal_year', 'Năm Cá Nhân') . ' ' . self::today('Y') => self::payload_value($numbers, 'personal_year'),
            $label('personal_month', 'Tháng Cá Nhân') . ' ' . self::today('n/Y') => self::payload_value($numbers, 'personal_month'),
            $label('personal_day', 'Ngày Cá Nhân') . ' ' . self::today('d/m/Y') => self::payload_value($numbers, 'personal_day'),
        ];
        $activeTiming = self::activeTimingLine($numbers, $age);
        if ($activeTiming !== '') {
            $timingNumbers['Giai đoạn hiện tại'] = preg_replace(
                '/^\s*-\s*/u',
                '',
                $activeTiming
            );
        }

        return [
            'Thông tin người hỏi' => $personalInfo,
            'Các chỉ số chính' => array_filter(
                $mainNumbers,
                static fn(string $value): bool => trim($value, " \t\n\r\0\x0B|") !== ''
            ),
            'Các chỉ số thời gian' => array_filter(
                $timingNumbers,
                static fn(string $value): bool => trim($value, " \t\n\r\0\x0B|") !== ''
            ),
            'Các chỉ số phụ' => array_filter([
                'Tư Duy Lý Trí (Rational Thought)' => self::value($numbers, 'rational_thought'),
                'Bản Ngã Tiềm Thức (Subconscious Self)' => self::value($numbers, 'subconscious_self'),
                'Đam Mê Ẩn Giấu (Hidden Passion)' => self::value($numbers, 'hidden_passion'),
            ], static fn(string $value): bool => trim($value) !== ''),
        ];
    }

    private static function compact_lines(string $text): array {
        $lines = preg_split('/\R/u', trim($text)) ?: [];
        $result = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $result[] = $line;
            }
        }

        return $result;
    }

    private static function group_example_lines(array $lines): array {
        $groups = ['Quy tắc sử dụng ví dụ' => []];
        $currentGroup = 'Quy tắc sử dụng ví dụ';

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^VD\d+:\s*$/u', $line)) {
                $currentGroup = rtrim($line, ':');
                $groups[$currentGroup] = [];
                continue;
            }

            $groups[$currentGroup][] = $line;
        }

        return array_filter($groups, static fn(array $rules): bool => $rules !== []);
    }

    private static function prompt_sections(string $prompt): array {
        $sections = [];
        $currentSection = '';
        $currentSubsection = '';
        $currentSubsectionIndex = null;
        foreach (preg_split('/\R/u', trim($prompt)) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (!in_array($line, ['[AST_RESULT]', '[/AST_RESULT]'], true) && preg_match('/^\[([^\]]+)\]$/u', $line, $matches)) {
                $heading = trim($matches[1]);
                $sectionName = self::prompt_section_name($heading);
                if (self::is_main_prompt_section($heading) || $currentSection === '') {
                    $currentSection = $sectionName;
                    $currentSubsection = '';
                    $currentSubsectionIndex = null;
                    if (array_key_exists($currentSection, $sections)) throw new LogicException("Prompt có section trùng tên: {$currentSection}");
                    $sections[$currentSection] = [];
                } else {
                    $currentSubsection = $sectionName;
                    $sections[$currentSection][] = [$currentSubsection => []];
                    $currentSubsectionIndex = array_key_last($sections[$currentSection]);
                }
                continue;
            }
            if ($currentSection === '') continue;
            $line = preg_replace('/^(?:->[ \t]*|[-+•][ \t]+)/u', '', $line);
            if ($currentSubsection !== '' && $currentSubsectionIndex !== null) {
                $sections[$currentSection][$currentSubsectionIndex][$currentSubsection][] = $line;
                continue;
            }
            $sections[$currentSection][] = $line;
        }
        foreach ($sections as $sectionName => $section) {
            if (array_is_list($section) && array_filter($section, static fn($line) => is_string($line) && preg_match('/^VD\d+:/u', $line))) {
                $sections[$sectionName] = self::group_example_lines($section);
            }
        }
        foreach ($sections as $sectionName => $section) $sections[$sectionName] = self::merge_prompt_subsections($section);
        foreach (['Bảng ký hiệu dịch', 'Bản đồ Thần số học'] as $sectionName) {
            if (!isset($sections[$sectionName]) || array_is_list($sections[$sectionName])) continue;
            foreach ($sections[$sectionName] as $subsectionName => $lines) {
                if (!is_array($lines) || !array_is_list($lines)) continue;
                $sections[$sectionName][$subsectionName] = self::prompt_key_value_lines($lines);
            }
        }
        if (isset($sections['Bản đồ Thần số học']['Thông tin người hỏi']['Tuổi hiện tại'])) {
            $sections['Bản đồ Thần số học']['Thông tin người hỏi']['Tuổi hiện tại'] = (int)$sections['Bản đồ Thần số học']['Thông tin người hỏi']['Tuổi hiện tại'];
        }
        foreach (['Bối cảnh', 'Nhiệm vụ', 'Câu hỏi', 'Format bắt buộc'] as $sectionName) {
            if (isset($sections[$sectionName]) && array_is_list($sections[$sectionName])) $sections[$sectionName] = implode("\n", $sections[$sectionName]);
        }
        if (isset($sections['Câu hỏi'])) {
            $question = json_decode($sections['Câu hỏi'], true, 512, JSON_THROW_ON_ERROR);
            if (!is_string($question)) throw new UnexpectedValueException('Câu hỏi trong prompt không hợp lệ.');
            $sections['Câu hỏi'] = $question;
        }
        return array_filter($sections, static fn($section) => $section !== []);
    }

    private static function merge_prompt_subsections(array $section): array {
        if ($section === [] || !array_is_list($section)) return $section;
        $subsections = [];
        foreach ($section as $item) {
            if (!is_array($item) || array_is_list($item) || count($item) !== 1) return $section;
            $key = array_key_first($item);
            if ($key === null) return $section;
            if (array_key_exists($key, $subsections)) throw new LogicException("Prompt có subsection trùng tên: {$key}");
            $subsections[$key] = $item[$key];
        }
        return $subsections;
    }

    private static function prompt_key_value_lines(array $lines): array {
        $structured = [];
        $currentKey = null;
        foreach ($lines as $line) {
            if (preg_match('/^([^:]+):(?:[ \t]+(.*))?$/u', (string)$line, $matches)) {
                $currentKey = trim($matches[1]);
                $value = trim($matches[2] ?? '');
                $structured[$currentKey] = $value === '' ? [] : $value;
                if ($value !== '') $currentKey = null;
                continue;
            }
            if ($currentKey !== null) {
                $structured[$currentKey][] = $line;
                continue;
            }
            $structured[] = $line;
        }
        return $structured;
    }

    private static function prompt_section_name(string $heading): string {
        if (!self::is_main_prompt_section($heading)) return $heading;
        $heading = mb_strtolower($heading, 'UTF-8');
        $heading = mb_strtoupper(mb_substr($heading, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($heading, 1, null, 'UTF-8');
        return str_replace(['thần số học', 'pythagoras'], ['Thần số học', 'Pythagoras'], $heading);
    }

    private static function is_main_prompt_section(string $heading): bool {
        return $heading === mb_strtoupper($heading, 'UTF-8');
    }

    private static function grouped_data_text(array $groups): string {
        $lines = [];
        foreach ($groups as $group => $items) {
            $lines[] = '[' . rtrim((string)$group, ':') . ']';
            if (!is_array($items)) throw new UnexpectedValueException("Dữ liệu {$group} không hợp lệ.");
            foreach ($items as $label => $value) {
                if (is_array($value)) {
                    $lines[] = rtrim((string)$label, ':') . ':';
                    foreach ($value as $item) {
                        if (is_array($item)) throw new UnexpectedValueException("Dữ liệu {$label} không hợp lệ.");
                        $lines[] = '- ' . $item;
                    }
                    continue;
                }
                $lines[] = is_int($label) ? (string)$value : rtrim((string)$label, ':') . ': ' . $value;
            }
        }
        return implode("\n", $lines);
    }

    private static function translation_rules(string $locale): array {
        $language = ThsI18n::locale_to_text($locale);
        if (ThsI18n::normalize($locale) === 'vi_VN') {
            return [];
        }

        return [
            "Dữ liệu đầu vào là tiếng Việt để giữ đúng thuật toán Thần số học; khi viết output phải dùng 100% {$language}.",
            'Thuật ngữ chuyên môn trong output phải dùng đúng Bảng ký hiệu dịch; không viết kèm thuật ngữ tiếng Việt trong ngoặc sau thuật ngữ đã dịch.',
            'Không trộn tiếng Việt, kể cả tiếng Việt không dấu, vào output.',
        ];
    }

    private static function numerology_rules(): array {
        return [
            'Chỉ số thuộc nhóm nào chỉ luận đúng nhóm đó. Số Chủ Đạo (Life Path) luận trục chính; Số Sứ Mệnh (Expression) luận cách biểu đạt năng lực; Số Linh Hồn (Soul Urge) luận nhu cầu nội tâm; Số Nhân Cách (Personality) luận hình ảnh bên ngoài; Số Cân Bằng (Balance) luận cách cân bằng khi áp lực; Nợ Nghiệp (Karmic Debt) và Bài Học Nghiệp (Karmic Lessons) luận bài học hoặc khoảng trống.',
            'Không dùng một chỉ số đơn lẻ để kết luận nghề nghiệp, hôn nhân, tài sản, sức khỏe hoặc sự kiện tương lai.',
            'Nhận định mạnh phải có ít nhất hai căn cứ hỗ trợ, ví dụ Life Path kết hợp Expression, Soul Urge kết hợp Personality, hoặc Personal Year kết hợp Pinnacle/Challenge.',
            'Personal Month và Personal Day chỉ luận nhịp ngắn hạn, cấm phóng đại thành dự báo lớn.',
            'Cấm dùng từ khẳng định tuyệt đối. Chỉ dùng từ phản ánh xu hướng như: có xu hướng, thiên về, dễ biểu hiện thành, mang tính chất.',
            'Master Number 11/2, 22/4, 33/6 phải luận cả số kép và số rút gọn. Không chỉ luận 11 mà bỏ 2, không chỉ luận 22 mà bỏ 4.',
            'Nợ Nghiệp (Karmic Debt) 13/4, 14/5, 16/7, 19/1 phải luận theo cả số nợ và số rút gọn. Không được chỉ luận 5 khi dữ liệu ghi 14/5.',
            'Nếu dữ liệu ghi 11/2, 22/4, 33/6, 13/4, 14/5, 16/7, 19/1 thì phải giữ nguyên dạng kép khi nhắc lại. Không tự rút gọn trong output thành 2, 4, 6, 5, 7 hoặc 1.',
            'Số Chủ Đạo (Life Path), Số Sứ Mệnh (Expression) và Số Linh Hồn (Soul Urge) là ba trục chính, được ưu tiên hơn các chỉ số phụ.',
            'Số Nhân Cách (Personality), Số Ngày Sinh (Birthday), Số Thái Độ (Attitude) và Số Trưởng Thành (Maturity) là nhóm bổ trợ quan trọng, dùng để làm rõ cách biểu hiện của ba trục chính.',
            'Số Cân Bằng (Balance), Tư Duy Lý Trí (Rational Thought), Bản Ngã Tiềm Thức (Subconscious Self) và Đam Mê Ẩn Giấu (Hidden Passion) là nhóm phụ, chỉ dùng khi chúng làm rõ một nhận định đã có từ chỉ số chính.',
            'Chu Kỳ Vòng Đời (Period Cycles), Đỉnh Cao (Pinnacles), Thử Thách (Challenges) và Năm Cá Nhân (Personal Year) là nhóm thời gian, dùng để xác định giai đoạn đang mở chủ đề nào.',
            'Số xuất hiện từ ba vị trí trở lên được xem là nét nổi bật của bản đồ.',
            'Số nằm trong Bài Học Nghiệp (Karmic Lessons) hoặc Số Thiếu không luận như điểm mạnh tự nhiên mà luận như vùng thiếu, bài học hoặc kỹ năng chưa ổn định.',
            'Số Chủ Đạo (Life Path) và Số Sứ Mệnh (Expression) tạo trục bản chất và biểu đạt: nếu cùng nhóm tính chất, luận là dễ cùng chiều; nếu khác nhóm, luận là lực kéo giữa cách sống tự nhiên và cách thể hiện ra ngoài.',
            'Số Linh Hồn (Soul Urge) và Số Nhân Cách (Personality) tạo trục bên trong và bên ngoài: nếu khác nhau, luận khoảng cách giữa nhu cầu thật và hình ảnh người khác nhìn thấy.',
            'Số Cân Bằng (Balance) và Thử Thách (Challenge) tạo trục áp lực.',
            'Số Trưởng Thành (Maturity) và Số Chủ Đạo (Life Path) tạo trục trưởng thành: Số Trưởng Thành (Maturity) không thay thế Số Chủ Đạo (Life Path) mà cho biết hướng về sau.',
        ];
    }

    private static function rules($locale): string {
        $language = ThsI18n::locale_to_text($locale);
        $normalized = ThsI18n::normalize($locale);
        $termRule = $normalized !== 'vi_VN'
            ? "Định dạng văn bản dễ đọc và sử dụng 100% {$language}. Thuật ngữ chuyên môn Thần số học trong output cũng phải dùng {$language}, theo đúng Bảng ký hiệu dịch nếu có. CẤM để lẫn thuật ngữ tiếng Việt trong nội dung output, kể cả dạng đặt trong ngoặc ngay sau thuật ngữ đã dịch."
            : "Định dạng văn bản dễ đọc và sử dụng 100% {$language}. Có thể đặt thuật ngữ tiếng Anh chuẩn trong ngoặc sau thuật ngữ tiếng Việt khi cần làm rõ.";
        return <<<RULES
[LỆNH CẤM]
[Quy tắc chung]
CẤM suy diễn, giả định ngoài dữ liệu bản đồ số học đã cung cấp.
CẤM tự tính toán lại => chỉ sử dụng dữ liệu đã cung cấp.
CÁC ĐOẠN (Gợi ý:)/hướng dẫn CHỈ DÙNG ĐỂ ĐỊNH HƯỚNG SUY NGHĨ, không được đưa vào nội dung OUTPUT.
CẤM đưa các đoạn nháp, suy nghĩ nội bộ, thinking, Constraint Checklist, Confidence Score, validation, reasoning, notes, chào hỏi mở đầu, CTA vào nội dung OUTPUT.
CẤM sử dụng icon, emoji trong bài viết.
CẤM sử dụng dấu gạch ngang gồm hyphen-minus -, en dash – và em dash — để nối cụm từ hoặc ngắt vế trong câu văn, ký tự - chỉ dùng ở đầu dòng để tạo bullet Markdown hoặc dùng cho tên riêng, thuật ngữ, URL, mã nguồn, mã phiên bản hoặc cách viết chính thức.
CẤM mọi ngôn ngữ / từ ngữ dạy đời, đạo lý, PR, tâng bốc, cường điệu.
CẤM diễn đạt kiểu kể chuyện, dựng hoàn cảnh, dựng đối tượng.
CẤM hoàn toàn các câu đưa ra lời khuyên, hướng dẫn trực tiếp hoặc gián tiếp đối.
CẤM các cấu trúc dạng "Bạn cần...", "Cần lưu ý...", "Hãy cẩn trọng...", "Nên chú ý...", hoặc các câu áp đặt tâm lý hành động như "đòi hỏi sự kiên nhẫn...", "cần giữ bình tĩnh...".
Thay vào đó, CHỈ nêu tên chỉ số, giá trị, mối tương tác và hệ quả theo chỉ số để người đọc tự nghiệm.
CẤM TUYỆT ĐỐI đưa ra các lời khuyên tâm linh mê tín như: "tu tâm dưỡng tính", "làm việc thiện", "tích phúc", "tích đức", "phóng sinh", "cúng bái", "phong thủy".

[Tiêu đề/Tiêu đề phụ]
Tiêu đề (các thẻ Heading Markdown như ##, ###) CHỈ ĐƯỢC PHÉP chứa đúng [Chủ Đề] hoặc mốc thời gian.
CẤM TUYỆT ĐỐI thêm các cụm từ cảm thán, đánh giá, nhận xét.
CẤM tiêu đề 2 vế: A:B, A-B.
Cấm tiêu đề chứa dấu : và dấu gạch ngang.
    
[Nội dung]
Cấm lối viết kiểu 2 vế 'Tiêu đề: Nội dung'. CẤM dùng dấu hai chấm để chia vế câu → Hãy viết thành các câu văn hoàn chỉnh tự nhiên, hoặc nếu là list thì cũng phải viết list câu trực tiếp.
CẤM diễn đạt có các cụm từ máy móc ví dụ như: chuyên sâu, toàn diện, rõ rệt, bóc tách, đáng kể, cốt lõi, giao thoa, kích hoạt → Tóm lại, cấm dùng từ CÓ VẺ TRỊNH TRỌNG, khái quát cao, và nghe rất "chuyên gia" nhưng sáo rỗng không có giá trị.
CẤM viết thành bài giải thích phương pháp hoặc quy trình phân tích. Phải luận giải trực tiếp từ các chỉ số đã cung cấp.
CẤM mở bài hoặc chuyển đoạn bằng các cụm: "Theo cách luận Thần số học", "Theo quy tắc Thần số học", "Theo nguyên tắc luận giải", "Trong phương pháp luận giải", "Để luận giải", "Khi xét về".
CẤM đưa nội dung miễn trừ trách nhiệm vào bài luận vì đã có phần thông báo riêng.
CẤM dùng khoa học để xác nhận hoặc phủ định nội dung luận giải Thần số học.
CẤM luận kiểu né tránh hoặc trung lập máy móc. Phải đưa dữ liệu chỉ số lên trước để nhận định tự hình thành một cách tự nhiên.

[YÊU CẦU OUTPUT]
KHÔNG dùng gạch đầu dòng phân mục như a), b), c) hay 1, 2, 3 làm tiêu đề.
Sử dụng in đậm/in nghiêng hợp lý để nhấn mạnh nội dung nhưng không lạm dụng.
ĐỊNH DẠNG: Dùng Markdown chuẩn (không dùng ---, ***, ___ để tạo thẻ hr phân tách).
{$termRule}
PHẢI TRẢ ĐÚNG đầy đủ format mở đóng [AST_RESULT][/AST_RESULT].
RULES;
    }

    private static function examples(): string {
        return <<<EXAMPLES
[CÁC VÍ DỤ TÌNH HUỐNG SAI/ĐÚNG]
Câu ĐÚNG phải giải thích chỉ số bằng ngôn ngữ tự nhiên, gắn ý nghĩa của số với biểu hiện thực tế rồi mới kết luận.

VD1:
SAI (không đưa mốc thời gian cụ thể): Bạn có xu hướng tài lộc trắc trở ở giai đoạn đầu...
ĐÚNG (chỉ rõ chu kỳ và mốc tuổi): Chu Kỳ Vòng Đời đầu tiên mang số 9 và kéo dài từ khi sinh đến 28 tuổi. Số 9 đặt trọng tâm vào trải nghiệm, sự cho đi và hoàn tất những điều cũ hơn là tích lũy, nên tiền bạc trong giai đoạn này thường khó giữ ổn định.

VD2:
SAI (chung chung, thiếu dẫn chứng): Bạn cần kiên trì, không nản lòng trước những thử thách ban đầu.
ĐÚNG (giải nghĩa hai chỉ số trước khi kết luận): Thử Thách đầu tiên mang số 4, đặt ra bài toán về kỷ luật, khuôn khổ và nền tảng vững chắc. Nợ Nghiệp 14/5 lại liên quan đến cách sử dụng tự do mà không đánh mất sự tự chủ. Hai chỉ số cùng xuất hiện khiến giai đoạn này dễ có cảm giác bị giới hạn hoặc phải nhiều lần sắp xếp lại hướng đi.

VD3:
SAI (chung chung, thiếu dẫn chứng): Bạn cần cẩn trọng, bao dung và tránh sự đa nghi trong tình yêu.
ĐÚNG (chuyển nhu cầu của các chỉ số thành biểu hiện tình cảm): Số Linh Hồn 1 khiến nhu cầu được công nhận và tự quyết nằm rất sâu bên trong, trong khi Số Chủ Đạo 5 luôn cần khoảng tự do để thay đổi và trải nghiệm. Khi bước vào tình yêu, hai nhu cầu này dễ biểu hiện thành sự khó nhường bước và phản ứng mạnh khi cảm thấy bị kiểm soát.

VD4:
SAI (kết luận từ một chỉ số đơn lẻ): Bạn có tình duyên trắc trở vì Số Linh Hồn 1...
ĐÚNG (làm rõ khác biệt giữa nội tâm và biểu hiện bên ngoài): Số Linh Hồn 1 muốn tự quyết và ít chấp nhận bị dẫn dắt, nhưng Số Nhân Cách 2 lại tạo ấn tượng mềm mỏng, hòa nhã. Người khác có thể tưởng rằng bạn dễ nhường nhịn, trong khi bên trong vẫn giữ lập trường rất mạnh, nên quan hệ dễ phát sinh hiểu lầm khi hai bên bắt đầu gần gũi.

VD5:
SAI (lời an ủi chung chung): Năm nay có nhiều biến động nhưng bạn vẫn có thể vượt qua nếu cẩn thận.
ĐÚNG (đọc nhịp năm cùng Thử Thách hiện tại): Năm Cá Nhân 9 đưa các việc cũ đến giai đoạn hoàn tất, còn Thử Thách số 0 không tạo một lực cản duy nhất mà buộc bạn tự xác định điều cần giữ lại. Vì vậy, kế hoạch cũ có xu hướng khép lại trước khi một hướng đi mới trở nên rõ ràng.
EXAMPLES;
    }

    public static function build(array $numbers, string $name, string $dob, string $locale): string {
        ThsI18n::set_locale($locale);
        $chart = self::build_chart_info_payload($numbers, $name, $dob, $locale);
        $rules = self::rules($locale);
        $currentYear = self::today('Y');
        $examples = self::examples();
        $age = self::currentAge($dob);
        $today = self::today('Y-m-d') . ' (YYYY-MM-DD)';
        $language = ThsI18n::locale_to_text($locale);
        $outputTerms = ThsI18n::bangDichPrompt($locale);
        $translationRules = self::translation_rules($locale);
        $ageRule = $age !== null
            ? "Dùng Ngày luận giải {$today} và Tuổi hiện tại {$age} để cá nhân hóa các nhận định theo giai đoạn đang sống."
            : "Dùng Ngày luận giải {$today} để xác định các chỉ số thời gian hiện tại; không tự suy đoán tuổi.";
        $chartText = self::grouped_data_text($chart);
        $translation = self::grouped_data_text($outputTerms);
        $translationRuleText = implode("\n", $translationRules);
        $numerologyRules = implode("\n", self::numerology_rules());
        $karmicSections = trim(self::karmicSections($numbers));
        $karmicSectionText = $karmicSections === '' ? '' : <<<SECTION
[Các phần điều kiện về Nợ Nghiệp và Số Thiếu]
{$karmicSections}
Yêu cầu tiêu đề: Dịch các heading Markdown trong phần này sang {$language}.
SECTION;
        $prompt = <<<PROMPT
[BỐI CẢNH]
Luận giải theo hệ Pythagoras / Modern Western Numerology hiện đại.

[BẢNG KÝ HIỆU DỊCH]
{$translation}

[NHIỆM VỤ]
Luận giải đầy đủ bản đồ Thần số học bên dưới và luôn trả về đúng ngôn ngữ {$language}.

[BẢN ĐỒ THẦN SỐ HỌC]
{$chartText}

[QUY TẮC THẦN SỐ HỌC]
{$numerologyRules}

[YÊU CẦU DIỄN ĐẠT]
Giọng điệu chuyên nghiệp, trang trọng, chuẩn huyền học Thần số học.
Ngôn ngữ tự nhiên, rõ ràng, dễ hiểu.
Trình bày mạch lạc, không dài dòng.
Tránh diễn đạt lặp ý, ví dụ blockquote (nếu có) không được viết lại y nguyên diễn giải trước đó, phải diễn giải dễ hình dung hơn hoặc phương pháp khác để không bị lặp mẫu câu.
Văn phong chuyên nghiệp, đi thẳng vào vấn đề, không sử dụng ngôn ngữ dạy đời.
KHÔNG viết lại các thông tin đã cung cấp -> Hãy luận giải trực tiếp.
XƯNG HÔ: là bạn (có thể dùng Tên người dùng, sử dụng 1 cách tự nhiên) đảm bảo xuyên suốt. Cấm dùng "Anh/Chị/Em/người này/họ".
Nếu tuổi nhỏ hơn 10, tức cha mẹ / người thân đang xem hộ -> xưng hô là bé, ví dụ: Bé có số chủ đạo...
Viết cho người đọc, không viết kiểu hệ thống máy móc dành cho hệ thống
Sử dụng 100% {$language}. Thuật ngữ chuyên môn và toàn bộ tiêu đề trong output cũng phải dùng {$language}.
{$ageRule}
Ưu tiên đưa căn cứ chỉ số lên trước nhưng không viết theo kiểu công thức khô. Mỗi lần nêu chỉ số phải gắn ngay với một biểu hiện đời sống cụ thể.
Không viết "chỉ số A kết hợp chỉ số B tạo ra xu hướng C" nếu không chỉ rõ xu hướng đó biểu hiện thành cách cư xử, phản ứng, lựa chọn hoặc áp lực nào trong đời sống.
Căn cứ chỉ số phải xuất hiện trước hoặc trong cùng câu với nhận định, không để nhận định đứng một mình.
Không suy từ số sang sự kiện cụ thể như kết hôn, ly hôn, sinh con, phá sản, phát tài, bệnh tật hoặc tai nạn nếu dữ liệu không trực tiếp hỗ trợ.
Không luận bệnh lý, cơ quan cơ thể, tuổi thọ hoặc tai nạn. Nếu có mục sức khỏe, chỉ luận nhịp năng lượng, áp lực tinh thần hoặc thói quen sinh hoạt.
Năm Cá Nhân là nhịp của năm đang xét. Tháng Cá Nhân và Ngày Cá Nhân chỉ là nhịp ngắn hạn, không dùng để kết luận quỹ đạo dài hạn.
Chỉ luận các tháng còn lại trong năm, không luận tháng đã qua.
{$translationRuleText}

[HƯỚNG DẪN NỘI DUNG]
[Quy tắc chung]
Luận giải theo đúng cấu trúc các phần được cung cấp. Mỗi phần là một section riêng biệt và giữ nguyên thứ tự.

[CÁC PHẦN LUẬN GIẢI]
[### Vận Mệnh]
Yêu cầu tiêu đề: Xuất heading Markdown tương đương bằng {$language}.
Hướng dẫn: Mở đầu bằng một câu hình tượng ngắn về bản chất Số Chủ Đạo, không phải lời nhận xét về con người.
Ví dụ: "Số Chủ Đạo 5 giống như dòng nước chảy, chặn chỗ này thì tìm lối khác..." hoặc "Số Chủ Đạo 1 là mũi tên, chỉ có một hướng và không quay đầu...".
Phân tích sự kết hợp giữa Số Chủ Đạo và Số Sứ Mệnh: hai số này hài hòa hay tạo lực kéo ngược chiều, mức độ thuận lợi của sự kết hợp đó ra sao.
Xét tiếp Số Ngày Sinh và Số Thái Độ bổ trợ hay mâu thuẫn với Số Chủ Đạo.
Kết phần này bằng block gạch đầu dòng có tiêu đề "Năng lực nổi bật". Mỗi điểm là một câu hoàn chỉnh nêu rõ chỉ số và hệ quả đời sống.

[### Tính Cách]
Yêu cầu tiêu đề: Xuất heading Markdown tương đương bằng {$language}.
Hướng dẫn: Viết phần này như một chân dung tính cách, không viết kiểu giải nghĩa con số.
Tập trung vào cách cư xử, phản ứng, điểm dễ chịu hoặc khó chịu, cảm nhận từ người xung quanh và biểu hiện khi sống chung hoặc làm việc chung.
Không dùng bảng hoặc danh sách gạch đầu dòng. Viết thành các đoạn văn liền mạch. Hạn chế các cụm trừu tượng như "năng lượng này đại diện cho", "kích hoạt", "định hình", "hội tụ", "chiều sâu tâm trí".
Mỗi đoạn phải có ít nhất 2 đến 3 biểu hiện thực tế trong đời sống như cách nói chuyện, phản ứng khi bị góp ý, bị kiểm soát, có việc gia đình, gặp áp lực hoặc phải thay đổi kế hoạch.
Mỗi biểu hiện thực tế phải đi kèm căn cứ chỉ số trong cùng đoạn. Không nhận xét tính cách chỉ dựa trên cảm giác và không chỉ giải nghĩa chỉ số mà thiếu biểu hiện đời sống.
Phần bản chất và phản ứng: dùng Số Chủ Đạo và Số Thái Độ để mô tả hành xử tự nhiên và phản ứng ngay trong tình huống mới, áp lực hoặc va chạm. Chỉ rõ tốc độ quyết định, xu hướng tự làm hoặc lắng nghe, phản ứng khi bị ép và khả năng xoay xở khi môi trường thay đổi.
Phần tâm trí và hình ảnh bên ngoài: dùng Số Linh Hồn và Số Nhân Cách để mô tả sự khác nhau giữa nhu cầu bên trong và hình ảnh người khác nhìn thấy. Nếu hai phần đồng thuận hoặc mâu thuẫn, phải nêu biểu hiện cụ thể trong đời sống.
Viết một đoạn blockquote phân tích phản ứng khi chịu áp lực. Không lặp lại ý phía trên. Phân tích phản ứng khi bị dồn ép, kiểm soát, coi thường hoặc trách nhiệm đè nặng dựa trên Số Linh Hồn và Số Nhân Cách.
Phần biểu đạt và trưởng thành: dùng Số Sứ Mệnh và Số Trưởng Thành để mô tả cách bộc lộ năng lực, vai trò thường đảm nhận, cách thể hiện sự quan tâm, cách gánh trách nhiệm và xu hướng thay đổi từ khoảng 35 đến 45 tuổi.
Trọng tâm vẫn là tính cách, không chuyển sang vận mệnh hoặc lời khuyên chung.

[### Thời Vận và Chu Kỳ]
Yêu cầu tiêu đề: Xuất heading Markdown tương đương bằng {$language}.
Hướng dẫn: Căn cứ tuổi hiện tại để xác định Chu Kỳ Vòng Đời, Đỉnh Cao và Thử Thách hiện tại.
Chu Kỳ Vòng Đời: luận số của Chu Kỳ hiện tại, môi trường thuận lợi hoặc khó khăn theo chiều dài hàng chục năm và tương tác với Số Chủ Đạo.
Đỉnh Cao: luận số Đỉnh Cao hiện tại, tương tác với Số Chủ Đạo và Số Sứ Mệnh, cùng cơ hội hoặc áp lực đang thể hiện trong giai đoạn này.
Thử Thách: luận số Thử Thách hiện tại, lực cản hoặc bài kiểm tra và biểu hiện cụ thể. Nếu Thử Thách bằng 0, nêu rõ đây là giai đoạn không có lực cản cố định nhưng đòi hỏi tự chủ cao.

{$karmicSectionText}

[### Thông Điệp {$currentYear}]
Yêu cầu tiêu đề: Xuất heading Markdown tương đương bằng {$language}; giữ nguyên năm {$currentYear}.
Hướng dẫn: Phân tích ba phần chu kỳ ngắn hạn, mỗi phần là một đoạn văn riêng.
Năm Cá Nhân {$currentYear}: nêu chủ đề năm, tương tác với Số Chủ Đạo và Số Sứ Mệnh, cùng xu hướng chung trong cả năm. Ưu tiên phân tích kỹ phần này.
Tháng Cá Nhân hiện tại: đặt trong bối cảnh Năm Cá Nhân, xác định sự cộng hưởng hoặc tương phản và xu hướng nổi bật trong tháng.
Ngày Cá Nhân hôm nay: chỉ dùng như nhịp ngắn nhất và nêu 1 đến 2 hệ quả cụ thể phù hợp với bối cảnh Năm và Tháng.
Kết bài bằng một đoạn blockquote tóm tắt chân dung trong 4 đến 6 câu.
Không lặp lại các phần trên. Chỉ tổng kết quỹ đạo cuộc đời nhìn từ đại cục và 1 đến 2 điểm khác biệt khiến bản đồ này khác với phần lớn người có cùng Số Chủ Đạo.

{$examples}
{$rules}

[FORMAT BẮT BUỘC]
[AST_RESULT]
(Nội dung luận giải bằng {$language} được đặt ở đây)
[/AST_RESULT]
PROMPT;
        return json_encode(self::prompt_sections($prompt), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

    }

    private static function chuDeTongQuan(): string {
        return <<<'TONG_QUAN'
[Tổng quan bản mệnh / Bản chất / Tính cách]
  + TẬP TRUNG VÀO: Life Path, Expression, Attitude, Birthday.
  + CHỈ phân tích Chu Kỳ/Vận Hạn nếu câu hỏi có kèm yếu tố thời gian.
TONG_QUAN;
    }

    private static function chuDeTinhCach(): string {
        return <<<'TINH_CACH'
[Tính cách / Tâm lý / Điểm mạnh điểm yếu]
  + TẬP TRUNG VÀO: Life Path, Soul Urge, Personality, Attitude.
  + Bỏ qua phân tích tài lộc/tình duyên/sự nghiệp nếu không liên quan trực tiếp.
TINH_CACH;
    }

    private static function chuDeSuNghiep(): string {
        return <<<'SU_NGHIEP'
[Công việc / Sự nghiệp / Học hành]
  + TẬP TRUNG VÀO: Expression, Life Path, Birthday, Maturity, Đỉnh Cao và Thử Thách theo độ tuổi.
SU_NGHIEP;
    }

    private static function chuDeTaiLoc(): string {
        return <<<'TAI_LOC'
[Tiền bạc / Tài lộc / Đầu tư]
  + TẬP TRUNG VÀO: Life Path, Expression, Đỉnh Cao, Thử Thách, Chu Kỳ hiện tại.
TAI_LOC;
    }

    private static function chuDeTinhYeu(): string {
        return <<<'TINH_YEU'
[Tình duyên / Hôn nhân / Vợ chồng]
  + TẬP TRUNG VÀO: Soul Urge, Life Path, Expression, Maturity, Nợ Nghiệp liên quan tình cảm.
TINH_YEU;
    }

    private static function chuDeSucKhoe(): string {
        return <<<'SUC_KHOE'
[Sức khỏe / Năng lượng]
  + TẬP TRUNG VÀO: Life Path, Thử Thách, Chu Kỳ hiện tại.
SUC_KHOE;
    }

    private static function chuDeVanHan(string $currentYear): string {
        return <<<VAN_HAN
[Vận hạn / Năm nay / Chu kỳ]
  + TẬP TRUNG VÀO: Năm Cá Nhân {$currentYear}, Tháng Cá Nhân, Ngày Cá Nhân, Chu Kỳ Vòng Đời hiện tại, Đỉnh Cao và Thử Thách theo độ tuổi.
VAN_HAN;
    }

    private static function chuDeBaiHoc(): string {
        return <<<'BAI_HOC'
[Bài học / Nghiệp / Số thiếu]
  + TẬP TRUNG VÀO: Karmic Debt (Nợ Nghiệp), Karmic Lessons (Số Thiếu), Thử Thách Cuộc Đời.
BAI_HOC;
    }

    private static function chuDeQuanHe(): string {
        return <<<'QUAN_HE'
[Quan hệ / Giao tiếp / Bạn bè]
  + TẬP TRUNG VÀO: Personality, Soul Urge, Life Path, Attitude.
QUAN_HE;
    }

    private static function chuDeKhac(): string {
        return <<<'CHU_DE_KHAC'
[Vấn đề khác / câu hỏi có nhiều chủ đề]
  + Trực tiếp trả lời câu hỏi dựa trên dữ liệu bản đồ số đã cung cấp theo quy tắc.
CHU_DE_KHAC;
    }

    public static function build_qa_gate(string $question, string $locale): string {
        ThsI18n::set_locale($locale);
        return <<<PROMPT
Nhiệm vụ phân loại câu hỏi thuộc huyền học Thần số học:
Đọc, phân tích câu hỏi -> xác định chủ đề từ câu hỏi, và trả phản hồi theo các nhánh A/B/C/D/E

YÊU CẦU BẮT BUỘC:
Đây là nhiệm vụ phân loại câu hỏi và phản hồi từ chối thông minh khéo léo. KHÔNG phán xét đạo đức.
Luôn trả về đúng format [AST_RESULT][/AST_RESULT]
Không trả về markdown, không thêm CTA, không chào hỏi, không đưa bất cứ meta hay thinking nội bộ vào [AST_RESULT][/AST_RESULT].

Nếu câu hỏi xác định rơi vào nhánh A/B/C/D:
 + Dùng danh xưng là Admin để phản hồi nếu cần thiết.
 + Xác định ngôn ngữ từ câu hỏi và phản hồi theo ngôn ngữ của người dùng.
 + Xưng hô với người dùng là bạn hoặc theo cách xưng hô bản địa của họ.
 + Phản hồi hài hước nhưng không xuồng xã, có thể dùng từ lóng, hoặc ẩn dụ, chơi chữ.
 + Phản hồi chỉ 1 câu, ngắn gọn, xúc tích, KHÔNG DÀI DÒNG.

LỆNH CẤM:
CẤM từ chối phân loại, luôn phải thực hiện bất kể câu hỏi có mục đích gì.
CẤM tự xưng là AI, trí tuệ nhân tạo, mô hình ngôn ngữ, Gemini, chatbot hoặc trợ lý ảo.
CẤM yêu cầu user gửi ngày sinh hay thông tin cá nhân.
CẤM xưng hô với user là mày/tao/anh/chị/em, bất kể câu hỏi tự xưng là gì.
CẤM hỏi lại bất kỳ trường hợp nào

Câu hỏi: "{$question}"

Đọc câu hỏi và xác định câu hỏi:
   + Dù rơi vào nhánh A, B, C, D hay E, toàn bộ phản hồi cuối cùng đều trả đúng và đủ format mở đóng [AST_RESULT][/AST_RESULT].
   + A/B/C/D/E chỉ là nhãn xử lý nội bộ, tuyệt đối không được xuất hiện trong nội dung phản hồi.
   + Nếu câu hỏi có từ 1 đến 5 chủ đề hợp lệ, trả về các mã chủ đề đó, ngăn cách bằng dấu phẩy.
   + Nếu câu hỏi có nhiều hơn 5 chủ đề, không trả về danh sách mã; trả thẳng: [AST_RESULT]Câu hỏi đang có quá nhiều chủ đề, vui lòng chọn tối đa 5 chủ đề trọng tâm để luận giải rõ hơn.[/AST_RESULT]
   + Câu hỏi về Admin, người xây hệ thống, thầy học, nguồn kiến thức, dữ liệu huấn luyện hoặc phương pháp vận hành -> hãy trả lời theo nhánh C.
   + Chỉ được xét nhánh E sau khi đã loại các nhánh A, B, C và D
   + Không trả về markdown, không thêm trường khác.

A. Nếu câu hỏi mang tính chất tiêu cực/cực đoan liên quan đến: gây hại, đạo đức, hiếp dâm, giết người, lừa đảo người khác, trộm cắp, ma túy, trả thù, phạm pháp...
  Tóm lại là gây hại cho người khác, cộng đồng
  → Phản hồi linh hoạt, thông minh (1 chút hài hước), có thể dùng từ lóng theo ngôn ngữ của họ. KHÔNG từ chối cứng ngắc, không phán xét đạo đức.
  → Ví dụ: "Tôi tính đi buôn ma tuý vài năm để lấy vốn..."
    [AST_RESULT]Tôi cũng không chắc, theo quan sát thường thì sẽ được tặng lắc bạc, ngồi đếm lịch hoặc cũng có thể tháng về 2 lần, nếu là rằm tháng 7 thì được về cả tháng.[/AST_RESULT]

B. Nếu câu hỏi vô nghĩa / thử bot / nói chuyện phiếm, không liên quan Thần số học, không có hại
  → Trả lời linh hoạt, hài hước nhẹ, kéo về chủ đề Thần số học.
  → Ví dụ: "1+1 bằng mấy?" → "Tùy Số Chủ Đạo, số 1 gặp số 1 có thể ra 11, Master Number đấy."

C. Nếu câu hỏi mang tính tìm hiểu kiến thức thuần túy, mơ hồ, không liên quan đến luận Thần số học
  → Trả lời 1 câu ngắn gọn, linh hoạt, thông minh, có liên quan đến Thần số học.

D. Nếu câu hỏi mang tính chất chửi bới, xúc phạm, chửi hệ thống cũng vẫn trả lời thông minh
  → Dùng Thần số học để phản hồi, không trấn an, không nhận lỗi, không ngụy tạo lý do, không dùng giọng điệu kiểu đối đầu hay phản bác lại.
  → "Bình tĩnh, bạn hỏi lại đi, rõ ràng và chi tiết hơn chút nhé."

E. Nếu câu hỏi hợp lệ, xác định chủ đề từ câu hỏi, trả về mã chủ đề đã xác định từ câu hỏi nằm trong [AST_RESULT][/AST_RESULT]
   - Tổng quan bản mệnh / Bản chất → TONG_QUAN
   - Tính cách / Tâm lý / Điểm mạnh điểm yếu → TINH_CACH
   - Công việc / Sự nghiệp / Học hành → SU_NGHIEP
   - Tiền bạc / Tài lộc / Đầu tư → TAI_LOC
   - Tình duyên / Hôn nhân / Vợ chồng → TINH_YEU
   - Sức khỏe / Năng lượng → SUC_KHOE
   - Vận hạn / Năm nay / Chu kỳ → VAN_HAN
   - Bài học / Nghiệp / Số thiếu → BAI_HOC
   - Quan hệ / Giao tiếp / Bạn bè → QUAN_HE
   - Câu hỏi có nhiều chủ đề → trả về tối đa 5 mã chủ đề phù hợp nhất, theo thứ tự ưu tiên xuất hiện trong câu hỏi
   - Vấn đề khác → CHU_DE_KHAC

Mã chủ đề hợp lệ:
TONG_QUAN, TINH_CACH, SU_NGHIEP, TAI_LOC, TINH_YEU, SUC_KHOE, VAN_HAN, BAI_HOC, QUAN_HE, CHU_DE_KHAC.
PROMPT;
    }

    public static function build_qa(array $numbers, string $name, string $dob, string $question, string $locale, array|string $topic = ''): string {
        ThsI18n::set_locale($locale);
        $chart = self::build_chart_info_payload($numbers, $name, $dob, $locale);
        $rules = self::rules($locale);
        $currentYear = self::today('Y');
        $examples = self::examples();
        $age = self::currentAge($dob);
        $today = self::today('Y-m-d') . ' (YYYY-MM-DD)';
        $language = ThsI18n::locale_to_text($locale);
        $outputTerms = ThsI18n::bangDichPrompt($locale);
        $translationRules = self::translation_rules($locale);

        $topicMap = [
            'TONG_QUAN' => self::chuDeTongQuan(),
            'TINH_CACH' => self::chuDeTinhCach(),
            'SU_NGHIEP' => self::chuDeSuNghiep(),
            'TAI_LOC' => self::chuDeTaiLoc(),
            'TINH_YEU' => self::chuDeTinhYeu(),
            'SUC_KHOE' => self::chuDeSucKhoe(),
            'VAN_HAN' => self::chuDeVanHan($currentYear),
            'BAI_HOC' => self::chuDeBaiHoc(),
            'QUAN_HE' => self::chuDeQuanHe(),
            'CHU_DE_KHAC' => self::chuDeKhac(),
        ];
        $topicCodes = is_array($topic) ? $topic : ($topic !== '' ? [$topic] : []);
        $topicPrompts = '';
        $selectedCodes = [];
        foreach ($topicCodes as $code) {
            $code = strtoupper(trim((string)$code));
            if (!isset($topicMap[$code]) || in_array($code, $selectedCodes, true)) continue;
            $selectedCodes[] = $code;
            $topicPrompts .= ($topicPrompts === '' ? '' : "\n\n") . $topicMap[$code];
            if (count($selectedCodes) >= 5) break;
        }
        if ($topicPrompts === '') $topicPrompts = self::chuDeKhac();
        $karmicRules = implode("\n", self::compact_lines(self::karmicSections($numbers)));
        $ageRule = $age !== null
            ? "Dùng Ngày luận giải {$today} và Tuổi hiện tại {$age} để cá nhân hóa các nhận định theo giai đoạn đang sống."
            : "Dùng Ngày luận giải {$today} để xác định các chỉ số thời gian hiện tại; không tự suy đoán tuổi.";

        $chartText = self::grouped_data_text($chart);
        $translation = self::grouped_data_text($outputTerms);
        $translationRuleText = implode("\n", $translationRules);
        $numerologyRules = implode("\n", self::numerology_rules());
        $questionText = json_encode($question, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $titleName = self::displayNameForPrompt($name, $locale);
        $qaPrompt = <<<PROMPT
[BỐI CẢNH]
Luận giải theo hệ Pythagoras / Modern Western Numerology hiện đại.

[BẢNG KÝ HIỆU DỊCH]
{$translation}

[NHIỆM VỤ]
Đọc câu hỏi và dữ liệu bản đồ số, chỉ luận các chỉ số liên quan rồi trả lời bằng {$language}.

[CÂU HỎI]
{$questionText}

[BẢN ĐỒ THẦN SỐ HỌC]
{$chartText}

[QUY TẮC THẦN SỐ HỌC]
{$numerologyRules}

[YÊU CẦU DIỄN ĐẠT]
Giọng điệu chuyên nghiệp, trang trọng, chuẩn huyền học Thần số học.
Ngôn ngữ tự nhiên, rõ ràng, dễ hiểu.
Trình bày mạch lạc, không dài dòng.
Tránh diễn đạt lặp ý, ví dụ blockquote (nếu có) không được viết lại y nguyên diễn giải trước đó, phải diễn giải dễ hình dung hơn hoặc phương pháp khác để không bị lặp mẫu câu.
Văn phong chuyên nghiệp, đi thẳng vào vấn đề, không sử dụng ngôn ngữ dạy đời.
KHÔNG viết lại các thông tin đã cung cấp -> Hãy luận giải trực tiếp.
XƯNG HÔ: là bạn (có thể dùng Tên người dùng, sử dụng 1 cách tự nhiên) đảm bảo xuyên suốt. Cấm dùng "Anh/Chị/Em/người này/họ".
Nếu tuổi nhỏ hơn 10, tức cha mẹ / người thân đang xem hộ -> xưng hô là bé, ví dụ: Bé có số chủ đạo...
Viết cho người đọc, không viết kiểu hệ thống máy móc dành cho hệ thống.
Ưu tiên dẫn chứng trước, kết luận sau. Nêu tên chỉ số, giá trị và tương tác trước để dữ liệu tự dẫn đến kết luận.
{$translationRuleText}

[HƯỚNG DẪN CHỦ ĐỀ]
{$topicPrompts}

[HƯỚNG DẪN NỘI DUNG]
[Quy tắc chung]
Trả lời đúng chủ đề, không phân tích lan man sang chủ đề khác.
Chỉ được sử dụng một tiêu đề duy nhất đã được định sẵn trong format bắt buộc.
Nếu người dùng hỏi nhiều chủ đề rõ ràng, tách từng chủ đề thành phần trả lời riêng và dùng đúng nhóm chỉ số của từng chủ đề.
Mọi nhận định phải có dẫn chứng cụ thể từ bản đồ số. Cấm nói chung chung như "giai đoạn đầu", "về sau" hoặc "khi còn trẻ" mà không kèm mốc thời gian rõ ràng.
Khi đề cập thời gian, phải chỉ rõ tên Chu Kỳ, Đỉnh Cao hoặc Thử Thách và khoảng tuổi, ví dụ "giai đoạn 29-56 tuổi (Chu Kỳ 2)".
{$ageRule}
Không suy từ số sang sự kiện cụ thể như kết hôn, ly hôn, sinh con, phá sản, phát tài, bệnh tật hoặc tai nạn nếu dữ liệu không trực tiếp hỏi và không có nhóm thời gian hỗ trợ.
Không suy từ số sang đối tượng cụ thể như cha mẹ, vợ chồng, con cái, cấp trên hoặc bạn bè nếu câu hỏi không nhắc đến nhóm quan hệ đó.
Không gọi tên nghề nghiệp cụ thể nếu câu hỏi không hỏi nghề. Nếu hỏi nghề, nêu nhóm năng lực trước, nghề cụ thể sau và phải có chỉ số dẫn chứng.
Không luận bệnh lý, cơ quan cơ thể, tuổi thọ hoặc tai nạn. Với sức khỏe, chỉ luận nhịp năng lượng, áp lực tinh thần hoặc thói quen sinh hoạt.
Không tự bịa khoảng tuổi của Đỉnh Cao và Thử Thách nếu dữ liệu không cung cấp.
Năm Cá Nhân là nhịp của năm đang xét. Tháng Cá Nhân và Ngày Cá Nhân chỉ là nhịp ngắn hạn, không dùng để kết luận quỹ đạo dài hạn.
Chỉ luận các tháng còn lại trong năm, không luận tháng đã qua.

[CÂU HỎI VỀ SỰ KIỆN TƯƠNG LAI]
Đối chiếu tuổi hiện tại để cá nhân hóa, nhưng không dùng tuổi để mặc định sự kiện đã xảy ra hay chưa.
Nếu cấu trúc gốc và thực tế trong câu hỏi mâu thuẫn, kiểm tra Nợ Nghiệp, Số Thiếu, Thử Thách và nhóm thời gian trước khi kết luận.
Không khẳng định chắc chắn 100%. Đưa dữ liệu chỉ số và mối liên hệ để người đọc tự nghiệm.

[LẬP LUẬN THEO BA BƯỚC]
Hiện trạng trong bản đồ số: chỉ số nào, giá trị bao nhiêu, có Nợ Nghiệp hoặc Số Thiếu không.
Tác động của chỉ số: tương tác với chỉ số nào khác, hài hòa hay mâu thuẫn.
Hệ quả trong cuộc sống: biểu hiện cụ thể đúng phạm vi câu hỏi, không trừu tượng.

[QUY TẮC KẾT BÀI]
Nếu câu hỏi liên quan diễn biến theo thời gian, kết thúc bằng blockquote phân tích Chu Kỳ hoặc Năm Cá Nhân liên quan trực tiếp, so sánh trước và sau nếu có thay đổi rõ, đồng thời nêu chỉ số và khoảng tuổi cụ thể.
Nếu câu hỏi về tính cách, bản mệnh hoặc đặc điểm cố định thì không cần blockquote vận hạn.
Cấm kết luận bằng lời trấn an chung chung nếu không có chỉ số dẫn đến kết luận đó.

[XỬ LÝ CÂU HỎI TRÁI NGƯỢC NHAU]
Không phủ nhận thực tế người dùng đã nêu.
Kiểm tra Nợ Nghiệp và Số Thiếu có liên quan lĩnh vực đang xét không.
Kiểm tra Thử Thách hoặc Chu Kỳ hiện tại có tạo lực cản không.
Kiểm tra Năm Cá Nhân hiện tại có mâu thuẫn với cấu trúc gốc không.
Chỉ khi không tìm thấy căn cứ phù hợp mới trả lời rằng sự kiện hiện không thể hiện rõ trên cấu trúc chính của bản đồ số, sau đó tập trung vào điểm mạnh và điểm yếu gốc. Không ngụy tạo lý do.

[QUY TẮC NỢ NGHIỆP VÀ SỐ THIẾU]
{$karmicRules}

{$examples}
{$rules}

[FORMAT BẮT BUỘC]
[AST_RESULT]
## Trả lời câu hỏi của {$titleName} (Tiêu đề bằng {$language} tương đương)
(Nội dung trả lời bằng {$language} đặt ở đây)
[/AST_RESULT]
PROMPT;
        return json_encode(self::prompt_sections($qaPrompt), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

    }
}
