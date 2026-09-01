<?php

declare(strict_types=1);

define('ABSPATH', dirname(__DIR__, 4) . '/');
define('THAN_SO_HOC_PLUGIN_DIR', dirname(__DIR__) . '/');

require_once THAN_SO_HOC_PLUGIN_DIR . 'includes/i18n.php';
require_once THAN_SO_HOC_PLUGIN_DIR . 'includes/name.php';
require_once THAN_SO_HOC_PLUGIN_DIR . 'includes/helper.php';
require_once THAN_SO_HOC_PLUGIN_DIR . 'includes/calc.php';

if (!function_exists('esc_html')) {
    function esc_html(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

$assertions = 0;

$assertSame = static function(mixed $expected, mixed $actual, string $message) use (&$assertions): void {
    $assertions++;
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
};

$calculate = static fn(string $name, string $dob): array => ThanSoHoc_Calc::calculate($name, $dob, 'en_US');

$thomas = $calculate('Thomas John Hancock', '1949-05-25');
$assertSame(7, $thomas['expression'], 'Expression reduces each name separately.');
$assertSame(2, $thomas['soul_urge'], 'Heart’s Desire reduces the vowels of each name separately.');
$assertSame(5, $thomas['personality'], 'Personality reduces the consonants of each name separately.');
$assertSame(2, $thomas['balance'], 'Balance reduces initials to a single digit.');
$assertSame(11, $thomas['rational_thought'], 'Rational Thought uses the full first name and Birth Day.');
$thomasCalculation = ThanSoHoc_Calc::buildCalculation('Thomas John Hancock', '1949-05-25', $thomas, 'en_US');
$assertSame(true, str_contains($thomasCalculation, '22+2+1 = 25'), 'Displayed Expression formula follows per-name reduction.');
$assertSame(true, str_contains($thomasCalculation, '5+7+5 = 17'), 'Displayed Life Path formula follows component reduction.');

$lifePath11 = $calculate('Test Name', '1948-05-29');
$assertSame(11, $lifePath11['life_path'], 'Life Path preserves 11 in date components and the final result.');
$assertSame('11', $lifePath11['master']['life_path'], 'Life Path 11 is marked as a Master Number.');

$karmic16 = $calculate('Test Name', '1998-10-15');
$assertSame(7, $karmic16['life_path'], 'Karmic Life Path 16 reduces to 7.');
$assertSame('16/7', $karmic16['karmic_debt_arr']['life_path'], 'Life Path retains the 16/7 Karmic Debt compound.');

$notMaster33 = $calculate('Test Name', '1980-02-22');
$assertSame(6, $notMaster33['life_path'], '33 reduces to 6 because Decoz 2002 keeps only 11 and 22.');
$assertSame('', $notMaster33['master']['life_path'], '33 is not marked as a Master Number.');

$challengeExample = $calculate('Test Name', '1950-05-29');
$assertSame('3 - 4 - 1 - 1', $challengeExample['challenges'], 'Challenges reduce Master Numbers before subtraction.');

$pinnacleExample = $calculate('Test Name', '1949-05-15');
$assertSame('5 - 6 - 5', $pinnacleExample['period_cycles'], 'Period Cycles preserve component reductions.');
$assertSame('11 - 11 - 22 - 1', $pinnacleExample['pinnacles'], 'Pinnacles preserve 11 and 22.');

$singleDigitIndices = $calculate('Bob Irwin', '2000-02-09');
$assertSame(2, $singleDigitIndices['attitude'], 'Attitude is reduced to a single digit.');
$assertSame(2, $singleDigitIndices['balance'], 'Balance does not retain 11.');
$assertSame('', $singleDigitIndices['master']['attitude'], 'Attitude is not exposed as a Master Number.');
$assertSame('', $singleDigitIndices['master']['balance'], 'Balance is not exposed as a Master Number.');

$yCases = [
    'Lynn' => true,
    'Yvonne' => true,
    'Mary' => true,
    'Betty' => true,
    'Elly' => true,
    'Bryan' => true,
    'Wyatt' => true,
    'Maloney' => false,
    'Murray' => false,
    'Yolanda' => false,
    'Yoda' => false,
];
foreach ($yCases as $name => $expected) {
    $profile = ThsName::profile($name, 'en_US');
    $yPart = array_values(array_filter($profile['parts'], static fn(array $part): bool => $part['letter'] === 'Y'))[0] ?? null;
    $assertSame($expected, $yPart['is_vowel'] ?? null, "Y classification follows Decoz for {$name}.");
}

$currentYear = (int)date('Y');
$universalYear = array_sum(str_split((string)$currentYear));
while ($universalYear > 9) $universalYear = array_sum(str_split((string)$universalYear));
$personalYearRaw = 1 + 9 + $universalYear;
while ($personalYearRaw > 9) $personalYearRaw = array_sum(str_split((string)$personalYearRaw));
$personalCycle = $calculate('Test Name', '2000-01-09');
$assertSame($personalYearRaw, $personalCycle['personal_year'], 'Personal Year always reduces Master Numbers to one digit.');

$landingFiles = ['default.php', 'en_US.php', 'es_MX.php', 'ja.php', 'ko_KR.php', 'th.php', 'zh_CN.php'];
$clientMessageKeys = [
    'date_required', 'date_range', 'date_month', 'date_day', 'date_not_exists', 'date_format',
    'invalid_full_name', 'error_occurred', 'connection_error', 'request_timeout',
    'stream_unsupported', 'response_unreadable', 'analysis_failed', 'stream_incomplete',
    'missing_info', 'question_too_short',
];
foreach ($landingFiles as $file) {
    $dictionary = require THAN_SO_HOC_PLUGIN_DIR . 'i18n/' . $file;
    $landing = $dictionary['landing'] ?? [];
    $landingText = json_encode($landing, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $assertSame(false, array_key_exists('lp_33_title', $landing), "Landing {$file} has no Life Path 33 title.");
    $assertSame(false, str_contains((string)$landingText, '33'), "Landing {$file} contains no Master Number 33 content.");
    $assertSame(true, trim((string)($landing['method_label'] ?? '')) !== '', "Landing {$file} has a method label.");
    $assertSame(true, trim((string)($landing['method_note'] ?? '')) !== '', "Landing {$file} declares the Hans Decoz method.");
    $assertSame(true, trim((string)($dictionary['errors']['free_ai_overloaded'] ?? '')) !== '', "Errors {$file} translates the free AI overload message.");
    foreach ($clientMessageKeys as $key) {
        $assertSame(true, trim((string)($dictionary['errors'][$key] ?? '')) !== '', "Errors {$file} translates {$key}.");
    }
    $assertSame(false, array_key_exists('ai_off', $dictionary['errors'] ?? []), "Errors {$file} contains no dead ai_off key.");
}

$koreanErrors = (require THAN_SO_HOC_PLUGIN_DIR . 'i18n/ko_KR.php')['errors'] ?? [];
$assertSame(true, trim((string)($koreanErrors['credit_unavailable'] ?? '')) !== '', 'Korean errors use the credit_unavailable key read by the endpoint.');
$assertSame(false, array_key_exists('credit_system_unavailable', $koreanErrors), 'Korean errors contain no obsolete credit_system_unavailable key.');

foreach (glob(THAN_SO_HOC_PLUGIN_DIR . 'data/*.php') as $file) {
    $dictionary = require $file;
    $dictionaryText = json_encode($dictionary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $assertSame(0, preg_match('/(?<!\d)33(?!\d)/u', (string)$dictionaryText), basename($file) . ' contains no legacy number 33 data.');
    $assertSame('western_hans_decoz_user_facing', $dictionary['__meta']['scope'] ?? '', basename($file) . ' declares the Hans Decoz dataset scope.');
}

$handleSource = (string)file_get_contents(THAN_SO_HOC_PLUGIN_DIR . 'includes/handle.php');
$javascriptSource = (string)file_get_contents(THAN_SO_HOC_PLUGIN_DIR . 'assets/tsh.js');
$restAllowlistSource = (string)file_get_contents(ABSPATH . 'wp-content/themes/lbv-child/includes/optimization.php');
$assertSame(true, str_contains($handleSource, "application/x-ndjson"), 'AI endpoint declares an NDJSON stream.');
$assertSame(true, str_contains($handleSource, 'Fortune_AI::generateMainStream'), 'AI endpoint uses the streaming provider method.');
$assertSame(true, str_contains($handleSource, 'streamResultMarkdown'), 'AI stream strips protocol markers before preview rendering.');
$assertSame(true, str_contains($handleSource, '$last_preview_length'), 'AI stream limits full Markdown preview reparsing by response growth.');
$assertSame(true, str_contains($handleSource, '$Parsedown->setSafeMode(true)'), 'AI Markdown disables raw HTML.');
$assertSame(true, str_contains($handleSource, 'wp_kses_post($Parsedown->text($md))'), 'AI HTML passes through the WordPress allowlist.');
$assertSame(true, str_contains($handleSource, "'usage_choice_required' => true"), 'AI endpoint requests an explicit free or paid usage choice.');
$assertSame(true, str_contains($handleSource, '$usage_mode ?: \'auto\''), 'Chosen usage mode is passed to the credit transaction.');
$assertSame(true, str_contains($handleSource, "'code'] = 'free_ai_overloaded'"), 'Free provider failures return the overload code after refunding quota.');
$assertSame(true, str_contains($handleSource, "register_rest_route('ths/v1', '/nonce'"), 'The plugin owns its dynamic nonce endpoint.');
$assertSame(true, str_contains($restAllowlistSource, "'/wp-json/ths/v1/nonce'"), 'The site REST allowlist permits the plugin nonce endpoint.');
$assertSame(true, str_contains($handleSource, "'permission_callback' => [\$this, 'authorizeNonce']"), 'The nonce endpoint requires its same-origin permission callback.');
$assertSame(true, str_contains($handleSource, "\$fetch_site === 'same-origin'"), 'Modern browsers must identify nonce and AI requests as same-origin.');
$assertSame(true, str_contains($handleSource, "get_header('Origin')"), 'Same-origin validation checks the Origin header.');
$assertSame(true, str_contains($handleSource, "get_header('Referer')"), 'Same-origin validation supports older browsers through Referer.');
$assertSame(true, str_contains($handleSource, 'nocache_headers();'), 'The dynamic nonce endpoint disables response caching.');
$assertSame(true, str_contains($handleSource, "wp_create_nonce('wp_rest')"), 'The dynamic endpoint creates a REST nonce for the current session.');
$assertSame(true, str_contains($handleSource, "'permission_callback' => [\$this, 'authorizeAnalyze']"), 'AI endpoint requires the nonce-aware permission callback.');
$assertSame(true, str_contains($handleSource, 'get_current_user_id() === $cookie_user_id'), 'Logged-in AI requests must pass WordPress REST cookie authentication.');
$assertSame(true, str_contains($handleSource, 'return is_user_logged_in();'), 'AI login state uses the WordPress current user after nonce authentication.');
$assertSame(true, substr_count($handleSource, '$user_id = get_current_user_id();') >= 3, 'Quota, credit use, and balance read the nonce-authenticated current user.');
$assertSame(false, str_contains($handleSource, "'nonce_url' =>"), 'Cacheable page HTML contains no external nonce endpoint dependency.');
$assertSame(true, str_contains($javascriptSource, "'X-Ths-Stream': '1'"), 'Browser requests a streamed AI response.');
$assertSame(true, str_contains($javascriptSource, "cache: 'no-store'"), 'Dynamic nonce requests bypass browser caches.');
$assertSame(true, str_contains($javascriptSource, "ths.api_url + 'nonce?_=' + Date.now()"), 'Browser requests the plugin-owned dynamic nonce endpoint.');
$assertSame(true, str_contains($javascriptSource, 'if (!res.ok)'), 'Nonce HTTP failures are distinct from logged-out responses.');
$assertSame(true, str_contains($javascriptSource, 'nonceError.userMessage'), 'Nonce transport failures retain a user-facing error.');
$assertSame(2, substr_count($javascriptSource, 'await fetchRestNonce(abortController.signal)'), 'Each AI request fetches a nonce for the current session.');
$assertSame(true, str_contains($javascriptSource, "'X-WP-Nonce': nonce"), 'Browser sends the dynamic REST nonce with AI requests.');
$assertSame(true, str_contains($javascriptSource, 'res.body.getReader()'), 'Browser reads streamed response chunks.');
$assertSame(true, str_contains($javascriptSource, 'finalResponse.dom_finalized = true'), 'Streamed DOM is finalized without replacing the completed preview.');
$assertSame(true, str_contains($javascriptSource, 'window.VibhhUsageModal.choose(preflight)'), 'Browser opens the shared usage choice modal.');
$assertSame(true, str_contains($javascriptSource, 'window.VibhhPayModal.showFreeOverload'), 'Browser offers paid credits when free AI is overloaded.');
$assertSame(true, str_contains($handleSource, "THAN_SO_HOC_PLUGIN_URL . 'assets/tsh.js'"), 'The browser loads the maintained JavaScript source file.');

fwrite(STDOUT, "OK: {$assertions} assertions\n");
