<?php
if (!defined('ABSPATH')) exit;

class ThanSoHoc_Render {

    private const INDEX_COLORS = [
            'life_path'      => ['gradient' => 'linear-gradient(135deg,#8b5cf6,#3b82f6)', 'glow' => 'rgba(139,92,246,.35)'],
            'expression'     => ['gradient' => 'linear-gradient(135deg,#a855f7,#ec4899)', 'glow' => 'rgba(168,85,247,.35)'],
            'attitude'       => ['gradient' => 'linear-gradient(135deg,#ec4899,#f43f5e)', 'glow' => 'rgba(236,72,153,.35)'],
            'birthday'       => ['gradient' => 'linear-gradient(135deg,#f97316,#eab308)', 'glow' => 'rgba(249,115,22,.35)'],
            'soul_urge'      => ['gradient' => 'linear-gradient(135deg,#10b981,#06b6d4)', 'glow' => 'rgba(16,185,129,.35)'],
            'personality'    => ['gradient' => 'linear-gradient(135deg,#06b6d4,#3b82f6)', 'glow' => 'rgba(6,182,212,.35)'],
            'maturity'       => ['gradient' => 'linear-gradient(135deg,#84cc16,#10b981)', 'glow' => 'rgba(132,204,22,.35)'],
            'balance'        => ['gradient' => 'linear-gradient(135deg,#14b8a6,#0ea5e9)', 'glow' => 'rgba(20,184,166,.35)'],
            'karmic_lessons' => ['gradient' => 'linear-gradient(135deg,#ef4444,#f97316)', 'glow' => 'rgba(239,68,68,.35)'],
            'karmic_debt'    => ['gradient' => 'linear-gradient(135deg,#b91c1c,#dc2626)', 'glow' => 'rgba(185,28,28,.35)'],
            'period_cycles'  => ['gradient' => 'linear-gradient(135deg,#8b5cf6,#a855f7)', 'glow' => 'rgba(139,92,246,.35)'],
            'pinnacles'      => ['gradient' => 'linear-gradient(135deg,#eab308,#f59e0b)', 'glow' => 'rgba(234,179,8,.35)'],
            'challenges'     => ['gradient' => 'linear-gradient(135deg,#64748b,#94a3b8)', 'glow' => 'rgba(100,116,139,.35)'],
            'personal_year'  => ['gradient' => 'linear-gradient(135deg,#f59e0b,#ef4444)', 'glow' => 'rgba(245,158,11,.35)'],
            'personal_month' => ['gradient' => 'linear-gradient(135deg,#0ea5e9,#6366f1)', 'glow' => 'rgba(14,165,233,.35)'],
            'personal_day'   => ['gradient' => 'linear-gradient(135deg,#3b82f6,#8b5cf6)', 'glow' => 'rgba(59,130,246,.35)'],
    ];

    private const INDEX_LABELS = [
            'life_path'      => 'Số Chủ Đạo',
            'expression'     => 'Số Sứ Mệnh / Biểu Đạt',
            'attitude'       => 'Số Thái Độ',
            'birthday'       => 'Số Ngày Sinh',
            'soul_urge'      => 'Số Linh Hồn / Soul Urge',
            'personality'    => 'Số Nhân Cách',
            'maturity'       => 'Số Trưởng Thành',
            'balance'        => 'Số Cân Bằng',
            'karmic_lessons' => 'Số Thiếu',
            'karmic_debt'    => 'Nợ Nghiệp',
            'period_cycles'  => '3 Chu Kỳ Vòng Đời',
            'pinnacles'      => '4 Đỉnh Cao Cuộc Đời',
            'challenges'     => '4 Thử Thách Cuộc Đời',
            'personal_year'  => 'Năm Cá Nhân',
            'personal_month' => 'Tháng Cá Nhân',
            'personal_day'   => 'Ngày Cá Nhân',
    ];

    public static function color(string $key): array {
        return self::INDEX_COLORS[$key] ?? self::INDEX_COLORS['life_path'];
    }

    private static function ucfirstUtf8(string $text): string {
        $text = trim($text);
        if ($text === '') return '';

        return mb_strtoupper(mb_substr($text, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($text, 1, null, 'UTF-8');
    }

    private static function chartDisplayName(string $name, array $indexes, string $locale): string {
        return class_exists('ThsName') ? ThsName::display_name_with_normalized($name, $locale) : trim($name);
    }

    private static function renderBlockOnly(string $fatalMessage, string $messageType, string $name, string $dob, string $dobIso = ''): string {
        $lines = [
                ['type' => 'greeting', 'text' => ''],
                ['type' => 'divider',  'text' => ''],
                ['type' => $messageType, 'text' => $fatalMessage],
        ];

        ob_start(); ?>
        <div class="ths-chat-wrap">
            <div class="ths-chat-bubble">
                <div class="ths-chat-body" id="ths-chat-body"
                     data-lines="<?= esc_attr(json_encode($lines, JSON_UNESCAPED_UNICODE)) ?>"
                     data-name="<?= esc_attr($name) ?>"
                     data-dob="<?= esc_attr($dobIso ?: $dob) ?>">
                    <span class="ths-cursor">|</span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function result(array $indexes, array $tabs, string $name, string $dob, string $dobIso = '', string $locale = ''): string {
        $blocks = $indexes['easter_eggs'] ?? [];

        if (!empty($blocks)) {
            foreach ($blocks as $block) {
                $blockName = esc_html($block['name'] ?? '');
                if ($block['type'] === 'future') {
                    $fatalMessage = "<strong>{$blockName}</strong> - " . ThsI18n::lookup('result.ui', 'easter_future');
                    return self::renderBlockOnly($fatalMessage, 'block', $name, $dob, $dobIso);
                }
                if ($block['type'] === 'over120') {
                    $fatalMessage = "👑 <strong>{$blockName}</strong> - " . ThsI18n::lookup('result.ui', 'easter_over120');
                    return self::renderBlockOnly($fatalMessage, 'easter', $name, $dob, $dobIso);
                }
            }
        }

        $currentYear  = function_exists('current_time') ? current_time('Y') : date('Y');
        $currentMonth = function_exists('current_time') ? current_time('n') : date('n');
        $labels       = [];
        foreach (array_keys(self::INDEX_LABELS) as $key) {
            $translated = ThsI18n::lookup('result.index', $key);
            $labels[$key] = $translated !== '' ? $translated : self::INDEX_LABELS[$key];
        }
        $labels['personal_year']  .= ' ' . $currentYear;
        $labels['personal_month'] .= ' ' . $currentMonth . '/' . $currentYear;
        $labels['personal_day']   .= ' ' . ThsI18n::lookup('result.ui', 'today');

        $lines = [];
        if (!empty($blocks)) {
            foreach ($blocks as $egg) {
                if ($egg['type'] === 'over100') {
                    $eggName = esc_html($egg['name'] ?? '');
                    $lines[] = ['type' => 'easter', 'text' => '👑 ' . $eggName . ' — ' . ThsI18n::lookup('result.ui', 'easter_over100')];
                    $lines[] = ['type' => 'divider', 'text' => ''];
                }
            }
        }

        $chartName = self::chartDisplayName($name, $indexes, $locale);
        $lines[] = ['type' => 'greeting', 'text' => sprintf(ThsI18n::lookup('result.ui', 'chart_of'), esc_html($chartName))];
        $lines[] = ['type' => 'divider', 'text' => ''];

        foreach ($labels as $key => $label) {
            $val = $indexes[$key] ?? '?';
            if (in_array($key, ['soul_urge', 'personality'], true) && (int)$val === 0) {
                continue;
            }

            $displayVal = $val;
            if (is_numeric($val) && (int)$val > 9 && !in_array($key, ['personal_year', 'personal_month', 'personal_day'])) {
                $num = (int)$val;
                $root = $num;
                while ($root > 9) $root = array_sum(str_split((string)$root));
                if ($num !== $root) $displayVal = "$num/$root";
            }

            $hintStr = self::ucfirstUtf8(ThanSoHoc_Calc::getHint($key, (string)$val, $locale));
            $lines[] = [
                    'type'  => 'index',
                    'key'   => $key,
                    'label' => $label,
                    'value' => $displayVal,
                    'hint'  => $hintStr ? '- ' . $hintStr : ''
            ];
        }

        $lines[] = ['type' => 'divider', 'text' => ''];
        $lines[] = ['type' => 'closing', 'text' => ThsI18n::lookup('result.ui', 'analyzing_progress')];

        ob_start(); ?>
        <div class="ths-analysis-wrap" id="ths-analysis-wrap">
            <div class="ths-tabs" role="tablist">
                <button class="ths-tab active" data-tab="detail" role="tab"><?= ThsI18n::lookup('result.ui', 'tab_result') ?></button>
                <button class="ths-tab" data-tab="calculation" role="tab"><?= ThsI18n::lookup('result.ui', 'tab_calculation') ?></button>
            </div>
            <div class="ths-tab-pane active" id="ths-tab-detail">
                <div id="ths-step-1-wrap">
                    <div class="ths-chat-wrap">
                        <div class="ths-chat-bubble">
                            <div class="ths-chat-body" id="ths-chat-body"
                                 data-name="<?= esc_attr($name) ?>" data-dob="<?= esc_attr($dobIso ?: $dob) ?>"
                                 data-lines="<?= esc_attr(json_encode($lines, JSON_UNESCAPED_UNICODE)) ?>">
                                <span class="ths-cursor">|</span>
                            </div>
                        </div>
                    </div>
                    <div id="ths-action-next" class="ths-action-next">
                        <?php if (ThanSoHoc_Settings::get_instance()->allowAI()): ?>
                            <p class="ths-note"><?= ThsI18n::lookup('result.ui', 'qa_note') ?></p>
                            <div id="ths-qa-input-area" class="ths-qa-input-area">
                                <textarea id="ths-user-question" class="ths-textarea" maxlength="500" rows="3"
                                          placeholder="<?= esc_attr(ThsI18n::lookup('result.qa', 'placeholder')) ?>"></textarea>
                                <div class="ths-suggested-questions">
                                    <p class="ths-sq-label"><?= ThsI18n::lookup('result.qa', 'hint') ?></p>
                                    <div class="ths-sq-list">
                                        <button type="button" class="ths-sq-btn" data-q="<?= esc_attr(ThsI18n::lookup('result.questions', 'love')) ?>"><?= ThsI18n::lookup('result.qa', 'btn_love') ?></button>
                                        <button type="button" class="ths-sq-btn" data-q="<?= esc_attr(ThsI18n::lookup('result.questions', 'career')) ?>"><?= ThsI18n::lookup('result.qa', 'btn_career') ?></button>
                                        <button type="button" class="ths-sq-btn" data-q="<?= esc_attr(ThsI18n::lookup('result.questions', 'strengths')) ?>"><?= ThsI18n::lookup('result.qa', 'btn_strengths') ?></button>
                                        <button type="button" class="ths-sq-btn" data-q="<?= esc_attr(ThsI18n::lookup('result.questions', 'personal_year')) ?>"><?= ThsI18n::lookup('result.qa', 'btn_personal_year') ?></button>
                                        <button type="button" class="ths-sq-btn" data-q="<?= esc_attr(ThsI18n::lookup('result.questions', 'karma')) ?>"><?= ThsI18n::lookup('result.qa', 'btn_karma') ?></button>
                                    </div>
                                </div>
                            </div>
                            <div class="ths-ai-btn-group">
                                <button type="button" id="ths-btn-deep-analyze" class="ths-btn-submit">
                                    <span class="ths-btn-text"><?= ThsI18n::lookup('result.ui', 'btn_analyze') ?></span>
                                    <span class="ths-btn-loading">
                                        <span class="ths-spinner"></span> <?= ThsI18n::lookup('result.ui', 'loading_analyzing') ?>
                                    </span>
                                </button>
                                <button type="button" id="ths-btn-qa-analyze" class="ths-btn-submit ths-btn-secondary"
                                        data-default-label="<?= esc_attr(ThsI18n::lookup('result.ui', 'btn_ask')) ?>"
                                        data-send-label="<?= esc_attr(ThsI18n::lookup('result.ui', 'btn_send_question')) ?>">
                                    <span class="ths-btn-text"><?= ThsI18n::lookup('result.ui', 'btn_ask') ?></span>
                                    <span class="ths-btn-loading">
                                        <span class="ths-spinner"></span> <?= ThsI18n::lookup('result.ui', 'loading_analyzing') ?>
                                    </span>
                                </button>
                                <button type="button" id="ths-btn-qa-cancel" class="ths-btn-submit ths-btn-cancel">
                                    <span class="ths-btn-text"><?= ThsI18n::lookup('result.ui', 'btn_back') ?></span>
                                </button>
                            </div>
                            <input type="text" id="ths-hp-tname" tabindex="-1" autocomplete="off">
                        <?php endif; ?>
                        <div id="ths-error-api" class="ths-error ths-error-api"></div>
                    </div>
                </div>
                <div id="ths-step-2-wrap" class="ths-step-2-wrap">
                    <div id="ths-analysis-display"></div>
                </div>
            </div>
            <div class="ths-tab-pane" id="ths-tab-calculation">
                <?= wp_kses_post($tabs['calculation'] ?? '') ?>
            </div>
        </div>

        <div class="ths-btn-right">
            <span class="ths-btn-reset"><?= ThsI18n::lookup('result.ui', 'btn_back') ?></span>
        </div>

        <p class="ths-disclaimer" id="ths-disclaimer">
            ✦ <?= ThsI18n::lookup('result.ui', 'disclaimer') ?>
        </p>

        <div class="ths-action-footer" id="ths-action-footer">
            <button type="button" id="ths-btn-comment" class="ths-btn-comment"><?= ThsI18n::lookup('result.ui', 'btn_comment') ?></button>
        </div>
        <?php
        return ob_get_clean();
    }
}
