<?php

if (!defined('ABSPATH')) exit;

class ThanSoHoc {

    private static ?ThanSoHoc $instance = null;

    private const THS_CACHE = '/than-so-hoc';

    private const NAME_MAX_LENGTH = 80;

    private function __construct() {
        add_filter('cron_schedules', [$this, 'addCacheCronSchedule']);
        add_shortcode('than_so_hoc_landing', [$this, 'renderShortcode']);
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets'], 12);
        add_action('wp_head', [$this, 'preloadAssets'], 0);
        add_filter('lbv_header_title', [$this, 'removePageTitle'], 10, 2);
        add_filter('body_class', [$this, 'addBodyClass']);
        add_action('ths_cache_reset', [$this, 'resetCacheDirectory']);

        if (wp_next_scheduled('ths_daily_cache_reset')) {
            wp_clear_scheduled_hook('ths_daily_cache_reset');
        }

        if (!wp_next_scheduled('ths_cache_reset')) {
            wp_schedule_event(time() + self::cacheTtl(), 'ths_cache_30_days', 'ths_cache_reset');
        }
    }

    public static function get_instance(): self {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private static function loadCalc(): void {
        if (!class_exists('ThanSoHoc_Calc')) {
            require_once THAN_SO_HOC_PLUGIN_DIR . 'includes/calc.php';
        }
    }

    private static function loadRender(): void {
        if (!class_exists('ThanSoHoc_Render')) {
            require_once THAN_SO_HOC_PLUGIN_DIR . 'template/render.php';
        }
    }

    private static function loadAI(): void {
        require_once FORTUNE_TOOLS_PLUGIN_DIR . 'includes/ai.php';
    }

    private static function loadingTexts(): array {
        $labels = ThsI18n::lookup_map('result.loading_texts');
        $text = static fn(string $key, string $fallback): string => (string)($labels[$key] ?? $fallback);

        return [
            'qa' => [
                $text('connecting', 'Connecting...'),
                $text('question', 'Analyzing...'),
                $text('chart', 'Checking chart...'),
                $text('core_numbers', 'Reading numbers...'),
                $text('cycles', 'Checking cycles...'),
                $text('compiling', 'Summarizing...'),
                $text('interpreting', 'Interpreting...'),
                $text('processing', 'Processing...'),
                $text('please_wait', 'Please wait...'),
            ],
            'deep' => [
                $text('connecting', 'Connecting...'),
                $text('initializing', 'Initializing...'),
                $text('chart', 'Building chart...'),
                $text('life_path', 'Reading Life Path...'),
                $text('destiny', 'Reading Destiny...'),
                $text('soul_urge', 'Reading Soul Urge...'),
                $text('personality', 'Reading Personality...'),
                $text('karmic', 'Checking karma...'),
                $text('cycles', 'Checking cycles...'),
                $text('comparing', 'Comparing results...'),
                $text('compiling', 'Summarizing...'),
                $text('accuracy', 'Verifying...'),
                $text('processing', 'Processing...'),
                $text('please_wait', 'Please wait...'),
            ],
        ];
    }

    private static function loadPrompt(): void {
        require_once THAN_SO_HOC_PLUGIN_DIR . 'includes/prompt.php';
    }

    private static function parseQaGateResponse(string $response): ?array {
        if (!preg_match('/\[AST_RESULT\]([\s\S]*?)\[\/AST_RESULT\]/', $response, $matches)) {
            return null;
        }

        $answer = trim((string)($matches[1] ?? ''));
        if ($answer === '') {
            return null;
        }

        $allowed_topics = [
            'TONG_QUAN', 'TINH_CACH', 'SU_NGHIEP', 'TAI_LOC', 'TINH_YEU',
            'SUC_KHOE', 'VAN_HAN', 'BAI_HOC', 'QUAN_HE', 'CHU_DE_KHAC',
        ];

        $tokens = preg_split('/[\s,|]+/', strtoupper($answer), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $topics = [];
        foreach ($tokens as $token) {
            if (in_array($token, $allowed_topics, true) && !in_array($token, $topics, true)) {
                $topics[] = $token;
            }
        }

        if ($topics !== []) {
            if (count($topics) === 1) {
                return ['topic' => $topics[0]];
            }

            if (count($topics) <= 5) {
                return ['topics' => $topics];
            }
        }

        return ['answer' => $answer];
    }

    private static function runQaGate(string $prompt, bool $use_paid): array {
        self::loadAI();
        return Fortune_AI::generateGate(
            $prompt,
            static fn(string $response): ?array => self::parseQaGateResponse($response),
            $use_paid
        );
    }

    public function addCacheCronSchedule(array $schedules): array {
        $schedules['ths_cache_30_days'] = [
            'interval' => self::cacheTtl(),
            'display'  => 'Every 30 days',
        ];
        return $schedules;
    }

    private static function cacheTtl(): int {
        return 30 * DAY_IN_SECONDS;
    }

    public function resetCacheDirectory(): void {
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . self::THS_CACHE;
        if (!is_dir($cache_dir)) {
            return;
        }

        $files = glob($cache_dir . '/*.json');
        if (empty($files)) {
            return;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    private function getCacheFilePath(array $input): string {
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . self::THS_CACHE;
        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
            file_put_contents($target_dir . '/.htaccess', "Deny from all\n");
            file_put_contents($target_dir . '/index.php', '<?php // Silence is golden');
        }

        $hash = md5(
            mb_strtolower(trim((string)($input['full_name'] ?? '')), 'UTF-8') . '|' .
            ($input['dob'] ?? '') . '|' .
            ($input['locale'] ?? '')
        );

        return trailingslashit($target_dir) . $hash . '.json';
    }

    private static function applyRequestLocale(WP_REST_Request $request): string {
        $locale = trim((string)$request->get_param('locale'));
        if ($locale === '') {
            return ThsI18n::current_locale();
        }
        if (!ThsI18n::set_locale($locale)) {
            return '';
        }
        return ThsI18n::current_locale();
    }

    public function enqueueAssets(): void {
        if (!is_singular()) return;
        global $post;
        if (empty($post->post_content)) return;
        if (!has_shortcode($post->post_content, 'than_so_hoc_landing')) return;
        wp_enqueue_style('tsh', THAN_SO_HOC_PLUGIN_URL . 'assets/tsh.css', [], THAN_SO_HOC_VERSION);
        wp_enqueue_script('tsh', THAN_SO_HOC_PLUGIN_URL . 'assets/tsh.min.js', ['jquery'], THAN_SO_HOC_VERSION, true);
        wp_localize_script('tsh', 'ths', [
            'api_url' => esc_url_raw(rest_url('ths/v1/')),
        ]);
    }

    public function preloadAssets(): void {
        if (!is_singular()) return;
        global $post;
        if (empty($post->post_content)) return;
        if (!has_shortcode($post->post_content, 'than_so_hoc_landing')) return;
        echo '<link rel="preload" href="' . esc_url(THAN_SO_HOC_PLUGIN_URL . 'assets/tsh.css' . '?ver=' . THAN_SO_HOC_VERSION) . '" as="style">' . "\n";
    }

    public function registerRestRoutes(): void {
        $args = [
            'full_name' => [
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'dob' => [
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'locale' => [
                'required'          => false,
                'sanitize_callback' => 'sanitize_text_field',
            ]
        ];

        register_rest_route('ths/v1', '/calculate', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handleRestCalc'],
            'permission_callback' => '__return_true',
            'args'                => $args
        ]);

        register_rest_route('ths/v1', '/analyze', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handleRestAnalyze'],
            'permission_callback' => '__return_true',
            'args'                => array_merge($args, [
                'user_question' => ['required' => false, 'sanitize_callback' => 'sanitize_textarea_field'],
                'is_qa_mode'    => ['required' => false, 'sanitize_callback' => 'rest_sanitize_boolean'],
                'ths_hp_tname'  => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
            ])
        ]);
    }

    private static function validateNameInput(string $name): string {
        $name = trim($name);
        if ($name === '') {
            return ThsI18n::lookup('errors', 'missing_info');
        }
        if (mb_strlen($name, 'UTF-8') > self::NAME_MAX_LENGTH) {
            return ThsI18n::lookup('errors', 'name_too_long');
        }
        if (ThsName::contains_han_script($name)) {
            $context = ThsName::reading_context($name);
            $message_id = $context !== '' ? 'invalid_name_' . $context : 'invalid_name';
            return ThsI18n::lookup('errors', $message_id, ThsI18n::lookup('errors', 'invalid_name'));
        }
        return '';
    }

    public function renderShortcode(array $atts): string {
        ob_start();
        include_once THAN_SO_HOC_PLUGIN_DIR . 'includes/svg.php';
        include_once THAN_SO_HOC_PLUGIN_DIR . 'template/landing.php';
        return ob_get_clean();
    }

    private static function classifyEasterEggs(array $easter_eggs): array {
        $hard_block_types = ['future', 'over120'];
        $soft_block_types = ['over100'];

        $hard = array_values(array_filter($easter_eggs, fn($e) => in_array($e['type'], $hard_block_types)));
        $soft = array_values(array_filter($easter_eggs, fn($e) => in_array($e['type'], $soft_block_types)));

        return ['hard' => $hard, 'soft' => $soft];
    }

    public function handleRestCalc(WP_REST_Request $request): WP_REST_Response {
        $locale = self::applyRequestLocale($request);
        if ($locale === '') {
            return new WP_REST_Response(['success' => false, 'message' => ThsI18n::lookup('errors', 'invalid_request')], 200);
        }

        self::loadCalc();
        self::loadRender();

        $name = $request->get_param('full_name');
        $dob  = $request->get_param('dob');

        if (empty($name) || empty($dob)) {
            return new WP_REST_Response(['success' => false, 'message' => ThsI18n::lookup('errors', 'missing_info')], 200);
        }
        $nameError = self::validateNameInput((string)$name);
        if ($nameError !== '') {
            return new WP_REST_Response(['success' => false, 'message' => $nameError], 200);
        }

        $dobDate = ThsHelper::validate_date($dob, $locale);
        if (!$dobDate['valid']) {
            return new WP_REST_Response(['success' => false, 'message' => $dobDate['message']], 200);
        }
        $dobIso = $dobDate['iso'];
        $dobDisplay = $dobDate['display'];

        try {
            $numbers = ThanSoHoc_Calc::calculate($name, $dobIso, $locale);
        } catch (InvalidArgumentException $e) {
            return new WP_REST_Response(['success' => false, 'message' => $e->getMessage()], 200);
        }

        $easter_eggs = $numbers['easter_eggs'] ?? [];
        ['hard' => $hard_blocks] = self::classifyEasterEggs($easter_eggs);

        if (!empty($hard_blocks)) {
            return new WP_REST_Response([
                'success'       => true,
                'html'          => ThanSoHoc_Render::result($numbers, [], $name, $dobDisplay, $dobIso, $locale),
                'loading_texts' => self::loadingTexts(),
            ], 200);
        }

        $calculation = ThanSoHoc_Calc::buildCalculation($name, $dobIso, $numbers, $locale);

        $tabs = [
            'calculation' => $calculation
        ];

        return new WP_REST_Response([
            'success'       => true,
            'html'          => ThanSoHoc_Render::result($numbers, $tabs, $name, $dobDisplay, $dobIso, $locale),
            'loading_texts' => self::loadingTexts(),
        ], 200);
    }

    public function handleRestAnalyze(WP_REST_Request $request): WP_REST_Response {
        $locale = self::applyRequestLocale($request);
        if ($locale === '') {
            return new WP_REST_Response(['success' => false, 'message' => ThsI18n::lookup('errors', 'invalid_request')], 200);
        }

        $locale = ThsI18n::normalize($locale);
        if (!$locale) {
            return new WP_REST_Response(['success' => false, 'message' => ThsI18n::lookup('errors', 'invalid_request')], 200);
        }

        if (!ThanSoHoc_Settings::get_instance()->allowAI()) {
            return new WP_REST_Response(['success' => false, 'message' => ThsI18n::lookup('errors', 'ai_disabled')], 200);
        }

        if (!$this->validate_logged_in()) {
            return new WP_REST_Response([
                'success' => false,
                'code'    => 'login_required',
                'message' => ThsI18n::lookup('errors', 'login_required'),
            ], 200);
        }

        $hp_trap = $request->get_param('ths_hp_tname');
        if (!empty($hp_trap)) {
            return new WP_REST_Response(['success' => false, 'message' => ThsI18n::lookup('errors', 'invalid_request')], 403);
        }

        self::loadCalc();

        $name = $request->get_param('full_name');
        $dob  = $request->get_param('dob');
        $user_question = sanitize_textarea_field($request->get_param('user_question') ?? '');
        $is_qa_mode = rest_sanitize_boolean($request->get_param('is_qa_mode'));

        if (empty($name) || empty($dob)) {
            return new WP_REST_Response(['success' => false, 'message' => ThsI18n::lookup('errors', 'missing_info')], 200);
        }
        $nameError = self::validateNameInput((string)$name);
        if ($nameError !== '') {
            return new WP_REST_Response(['success' => false, 'message' => $nameError], 200);
        }

        if ($is_qa_mode && mb_strlen($user_question, 'UTF-8') < 10) {
            return new WP_REST_Response(['success' => false, 'message' => ThsI18n::lookup('errors', 'question_too_short')], 200);
        }

        if ($is_qa_mode && mb_strlen($user_question, 'UTF-8') > 500) {
            return new WP_REST_Response(['success' => false, 'message' => ThsI18n::lookup('errors', 'question_too_long')], 200);
        }

        $dobDate = ThsHelper::validate_date($dob, $locale);
        if (!$dobDate['valid']) {
            return new WP_REST_Response(['success' => false, 'message' => $dobDate['message']], 200);
        }
        $dobIso = $dobDate['iso'];

        try {
            $numbers = ThanSoHoc_Calc::calculate($name, $dobIso, $locale);
        } catch (InvalidArgumentException $e) {
            return new WP_REST_Response(['success' => false, 'message' => $e->getMessage()], 200);
        }

        $input = [
            'full_name'    => $name,
            'dob'          => $dobIso,
            'locale'       => $locale,
        ];

        $cacheFile = $this->getCacheFilePath($input);
        if (!$is_qa_mode && file_exists($cacheFile)) {
            if (time() - filemtime($cacheFile) > self::cacheTtl()) {
                @unlink($cacheFile);
            } else {
                $cached = json_decode((string)file_get_contents($cacheFile), true);
                if (!empty($cached) && isset($cached['html_detail'])) {
                    return new WP_REST_Response([
                        'success'     => true,
                        'html_detail' => $cached['html_detail'],
                        'is_cached'   => true,
                    ], 200);
                }
            }
        }

        $quota_check = $this->ths_quota();
        if (!is_array($quota_check) || ($quota_check['status'] ?? '') === 'denied') {
            return $this->out_of_credits_response();
        }

        if (!class_exists('Vibhh_Credit')) {
            return new WP_REST_Response(['success' => false, 'message' => ThsI18n::lookup('errors', 'credit_unavailable')], 200);
        }

        $user_id = self::getCurrentUserIdFromCookie();
        $quota_used = Vibhh_Credit::get_instance()->use_quota($user_id, 'than-so-hoc', THAN_SO_HOC_RATE_LIMIT);
        if (($quota_used['status'] ?? '') === 'denied') {
            return $this->out_of_credits_response();
        }

        $use_paid = ($quota_used['status'] ?? '') === 'paid';

        self::loadPrompt();

        if ($is_qa_mode) {
            $gatePrompt = ThanSoHoc_Prompt::build_qa_gate($user_question, $locale);
            $gate = self::runQaGate($gatePrompt, $use_paid);
            if (isset($gate['answer'])) {
                $rawResponse = (string)$gate['answer'];
                $usage = $gate['usage'] ?? null;
                $provider = (string)($gate['provider'] ?? '');
                $this->debug($gatePrompt, $rawResponse);
                $this->log_ths_request($user_id, $quota_used, 'success', $input, true, $user_question, $usage, $provider);
                return new WP_REST_Response([
                    'success' => true,
                    'html_detail' => self::markdownToHtml($rawResponse),
                    'is_cached' => false,
                ], 200);
            }

            $topicCodes = $gate['topics'] ?? ($gate['topic'] ?? '');
            if ($topicCodes === '' || $topicCodes === []) {
                $this->refund_quota($user_id, $quota_used);
                $failed_quota = $this->failed_quota($quota_used);
                $this->log_ths_request($user_id, $failed_quota, 'failed', $input, true, $user_question);
                return new WP_REST_Response([
                    'success' => false,
                    'message' => ThsI18n::lookup('errors', 'service_busy'),
                ], 200);
            }

            $prompt = ThanSoHoc_Prompt::build_qa($numbers, $name, $dobIso, $user_question, $locale, $topicCodes);
        } else {
            $prompt = ThanSoHoc_Prompt::build($numbers, $name, $dobIso, $locale);
        }

        $prompt = self::minifyJsonPrompt($prompt);

        self::loadAI();
        $ai_result = Fortune_AI::generateMain($prompt, $use_paid);
        $rawResponse = $ai_result['response'];
        $usage = $ai_result['usage'];
        $provider = $ai_result['provider'];

        if (!$ai_result['success']) {
            $this->refund_quota($user_id, $quota_used);
            $failed_quota = $this->failed_quota($quota_used);
            $this->log_ths_request($user_id, $failed_quota, 'failed', $input, $is_qa_mode, $user_question, $usage, $provider);
            $message = str_starts_with($rawResponse, '[Error]')
                ? trim(substr($rawResponse, strlen('[Error]')))
                : ThsI18n::lookup('errors', 'service_busy');
            return new WP_REST_Response(['success' => false, 'message' => $message], 200);
        }

        if (!str_contains($rawResponse, '[AST_RESULT]') || !str_contains($rawResponse, '[/AST_RESULT]')) {
            $this->refund_quota($user_id, $quota_used);
            $failed_quota = $this->failed_quota($quota_used);
            $this->log_ths_request($user_id, $failed_quota, 'failed', $input, $is_qa_mode, $user_question, $usage, $provider);
            return new WP_REST_Response(['success' => false, 'message' => ThsI18n::lookup('errors', 'error_occurred')], 200);
        }

        preg_match('/\[AST_RESULT\]([\s\S]*?)\[\/AST_RESULT\]/', $rawResponse, $matches);
        $resultText = trim($matches[1] ?? '');
        if (!$is_qa_mode && mb_strlen($resultText, 'UTF-8') < 300) {
            $this->refund_quota($user_id, $quota_used);
            $failed_quota = $this->failed_quota($quota_used);
            $this->log_ths_request($user_id, $failed_quota, 'failed', $input, $is_qa_mode, $user_question, $usage, $provider);
            return new WP_REST_Response(['success' => false, 'message' => ThsI18n::lookup('errors', 'error_occurred')], 200);
        }

        $this->debug($prompt, $rawResponse);

        $html = self::markdownToHtml($rawResponse);
        if (!$is_qa_mode && str_contains($rawResponse, '[AST_RESULT]') && str_contains($rawResponse, '[/AST_RESULT]')) {
            $payload = ['html_detail' => $html, 'cache_date' => current_time('Y-m-d')];
            file_put_contents($cacheFile, json_encode($payload, JSON_UNESCAPED_UNICODE));
        }

        $this->log_ths_request($user_id, $quota_used, 'success', $input, $is_qa_mode, $user_question, $usage, $provider);

        return new WP_REST_Response([
            'success'     => true,
            'html_detail' => $html,
            'is_cached'   => false,
        ], 200);
    }

    private static function getCurrentUserIdFromCookie(): int {
        $cookie = $_COOKIE[LOGGED_IN_COOKIE] ?? '';
        return $cookie ? (int) wp_validate_auth_cookie($cookie, 'logged_in') : 0;
    }

    public function validate_logged_in(): bool {
        return self::getCurrentUserIdFromCookie() > 0;
    }

    private function ths_quota(): array|bool {
        $user_id = self::getCurrentUserIdFromCookie();
        if (!$user_id) return false;

        $quota = apply_filters('vibhh_check_quota', null, $user_id, 'than-so-hoc', THAN_SO_HOC_RATE_LIMIT);
        return is_array($quota) ? $quota : false;
    }

    private function out_of_credits_response(): WP_REST_Response {
        $user_id = self::getCurrentUserIdFromCookie();
        $balance = ($user_id && class_exists('Vibhh_Credit')) ? Vibhh_Credit::get_instance()->get_balance($user_id) : 0;

        return new WP_REST_Response([
            'success'        => false,
            'out_of_credits' => true,
            'balance'        => $balance,
            'message'        => ThsI18n::lookup('errors', 'out_of_credits'),
        ], 200);
    }

    private function refund_quota(int $user_id, array $quota_used): void {
        if (!$user_id || !class_exists('Vibhh_Credit')) return;
        Vibhh_Credit::get_instance()->refund($user_id, 'than-so-hoc', $quota_used['status'] ?? 'free', $quota_used['cost'] ?? 0, $quota_used['free_source'] ?? '');
    }

    private function failed_quota(array $quota_used): array {
        $failed_quota = array_merge($quota_used, [
            'cost'          => 0,
            'balance_after' => $quota_used['balance_before'] ?? ($quota_used['balance_after'] ?? 0),
        ]);

        if (($quota_used['status'] ?? '') === 'free') {
            $failed_quota['free_used'] = max(0, (int)($quota_used['free_used'] ?? 0) - 1);
        }

        return $failed_quota;
    }

    private function log_ths_request(int $user_id, array $quota_used, string $status, array $input, bool $is_qa_mode, string $user_question, ?array $usage = null, string $provider = ''): void {
        if (!$user_id) return;
        if ($provider === '' && is_array($usage)) {
            $provider = (string)($usage['model'] ?? $usage['serviceTier'] ?? '');
        }



        do_action('vibhh_log_request', $user_id, 'than-so-hoc', $quota_used, [
            'input'          => $input,
            'is_qa_mode'     => $is_qa_mode,
            'user_question'  => $user_question,
            'provider'       => $provider,
            'tier'           => ($quota_used['status'] ?? '') === 'paid' ? 'paid' : 'free',
            'status'         => $status,
            'usageMetadata'  => $usage,
        ]);
    }

    public static function markdownToHtml(string $md): string {
        if (str_contains($md, '[AST_RESULT]') && str_contains($md, '[/AST_RESULT]')) {
            preg_match('/\[AST_RESULT\]([\s\S]*?)\[\/AST_RESULT\]/', $md, $matches);
            if (!empty($matches[1])) {
                $md = trim($matches[1]);
            }
        }

        $md = preg_replace('/^[\-]{3,}$/m', '', $md);
        $md = preg_replace('/^\*{3,}$/m', '', $md);
        $md = preg_replace('/^_{3,}$/m', '', $md);

        if (!class_exists('Parsedown')) {
            require_once THAN_SO_HOC_PLUGIN_DIR . 'lib/Parsedown.php';
        }

        $Parsedown = new Parsedown();
        return $Parsedown->text($md);
    }

    public function removePageTitle($title, $post_id) {
        $post = get_post($post_id);
        if ($post && has_shortcode($post->post_content, 'than_so_hoc_landing')) {
            return '';
        }
        return $title;
    }

    public function addBodyClass(array $classes): array {
        global $post;
        if (!is_a($post, 'WP_Post')) return $classes;

        if (has_shortcode($post->post_content, 'than_so_hoc_landing')) {
            $classes[] = 'ich-tool-page';
        }

        return $classes;
    }

    private static function minifyJsonPrompt(string $prompt): string {
        $decoded = json_decode($prompt, true, 512, JSON_THROW_ON_ERROR);
        return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function debug($prompt, $rawResponse = null) {
        if (!defined('THS_DEBUG') || !THS_DEBUG) return;
        $log_time = time();
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/than-so-hoc/tmp/';
        if (!is_dir($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        file_put_contents($log_dir . "prompt_{$log_time}.log", $prompt);
        if ($rawResponse !== null) {
            file_put_contents($log_dir . "rawResponse_{$log_time}.log", $rawResponse);
        }
    }
}

ThanSoHoc::get_instance();
