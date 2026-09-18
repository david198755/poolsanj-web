<?php
/**
 * PoolSanj Telegram Bot (PHP) — webhook mode
 * Upload to hodhodcandy.ir/prices/bot.php
 * Set webhook: https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://hodhodcandy.ir/prices/bot.php
 */

// ============ CONFIG ============
define('BOT_TOKEN', '8493445622:AAGgVuD7e3wyMkBweAh44zgz2YI-ebJse4s');
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');
define('CACHE_FILE', __DIR__ . '/cache.json');
define('CACHE_TTL', 30); // seconds

// ============ CATALOG ============
$CATALOG = [
    'ارز' => [
        ['price_dollar_rl', '🇺🇸 دلار آمریکا'],
        ['price_eur',       '🇪🇺 یورو'],
        ['price_gbp',       '🇬🇧 پوند انگلیس'],
        ['price_chf',       '🇨🇭 فرانک سوئیس'],
        ['price_aed',       '🇦🇪 درهم امارات'],
        ['price_sar',       '🇸🇦 ریال عربستان'],
        ['price_qar',       '🇶🇦 ریال قطر'],
        ['price_omr',       '🇴🇲 ریال عمان'],
        ['price_kwd',       '🇰🇼 دینار کویت'],
        ['price_bhd',       '🇧🇭 دینار بحرین'],
        ['price_iqd',       '🇮🇶 دینار عراق'],
        ['price_try',       '🇹🇷 لیر ترکیه'],
        ['price_syp',       '🇸🇾 لیر سوریه'],
        ['price_afn',       '🇦🇫 افغانی'],
        ['price_rub',       '🇷🇺 روبل روسیه'],
        ['price_azn',       '🇦🇿 منات آذربایجان'],
        ['price_amd',       '🇦🇲 درام ارمنستان'],
        ['price_gel',       '🇬🇪 لاری گرجستان'],
        ['price_kgs',       '🇰🇬 سوم قرقیزستان'],
        ['price_tjs',       '🇹🇯 سامانی تاجیکستان'],
        ['price_tmt',       '🇹🇲 منات ترکمنستان'],
        ['price_cny',       '🇨🇳 یوان چین'],
        ['price_jpy',       '🇯🇵 ین ژاپن'],
        ['price_krw',       '🇰🇷 وون کره'],
        ['price_inr',       '🇮🇳 روپیه هند'],
        ['price_pkr',       '🇵🇰 روپیه پاکستان'],
        ['price_myr',       '🇲🇾 رینگیت مالزی'],
        ['price_thb',       '🇹🇭 بات تایلند'],
        ['price_hkd',       '🇭🇰 دلار هنگ‌کنگ'],
        ['price_sgd',       '🇸🇬 دلار سنگاپور'],
        ['price_cad',       '🇨🇦 دلار کانادا'],
        ['price_aud',       '🇦🇺 دلار استرالیا'],
        ['price_nzd',       '🇳🇿 دلار نیوزیلند'],
        ['price_dkk',       '🇩🇰 کرون دانمارک'],
        ['price_sek',       '🇸🇪 کرون سوئد'],
        ['price_nok',       '🇳🇴 کرون نروژ'],
    ],
    'طلا' => [
        ['geram18',      '🏅 طلای ۱۸ عیار'],
        ['geram24',      '🏅 طلای ۲۴ عیار'],
        ['mesghal',      '⚖️ مثقال طلا'],
        ['ons',          '🌍 اونس جهانی'],
        ['gold_futures', '🔥 طلای آبشده'],
    ],
    'سکه' => [
        ['retail_sekee',  '🪙 سکه امامی'],
        ['sekeb',         '🪙 سکه بهار آزادی'],
        ['retail_nim',    '🪙 نیم سکه'],
        ['retail_rob',    '🪙 ربع سکه'],
        ['retail_gerami', '🪙 سکه گرمی'],
        ['coin_blubber',  '🫧 حباب سکه امامی'],
    ],
    'کریپتو' => [
        ['crypto-bitcoin',      '🟠 بیت‌کوین'],
        ['crypto-tether',       '💵 تتر'],
        ['crypto-ethereum',     '🔷 اتریوم'],
        ['crypto-bnb',          '🟡 بایننس کوین'],
        ['crypto-solana',       '🟣 سولانا'],
        ['crypto-ripple',       '✖️ ریپل'],
        ['crypto-dogecoin',     '🐕 دوج کوین'],
        ['crypto-tron',         '⚡️ ترون'],
        ['crypto-cardano',      '🔵 کاردانو'],
        ['crypto-litecoin',     '🔘 لایت کوین'],
        ['crypto-bitcoin-cash', '🔶 بیت‌کوین کش'],
        ['crypto-stellar',      '⭐️ استلار'],
        ['crypto-polkadot',     '🔮 پولکادات'],
        ['crypto-avalanche',    '🔺 آوالانچ'],
        ['crypto-dash',         '🌀 دش'],
        ['crypto-shiba-inu',    '🐾 شیبا اینو'],
        ['crypto-toncoin',      '💎 تون‌کوین'],
        ['crypto-chainlink',    '🔗 چینلینک'],
        ['crypto-polygon',      '🟥 پالیگان'],
        ['crypto-uniswap',      '🦄 یونی‌سوآپ'],
    ],
];

$SUMMARY = [
    ['price_dollar_rl', '💵 دلار'],
    ['price_eur',       '💶 یورو'],
    ['retail_sekee',    '🪙 سکه امامی'],
    ['sekeb',           '🪙 بهار آزادی'],
    ['geram18',         '🏅 طلا ۱۸'],
    ['mesghal',         '⚖️ مثقال'],
    ['ons',             '🌍 اونس جهانی'],
    ['crypto-bitcoin',  '🟠 بیت‌کوین'],
    ['crypto-tether',   '💵 تتر'],
    ['crypto-ethereum', '🔷 اتریوم'],
];

$KEYWORDS = [
    'ارز' => '__ALL_CURRENCY__',
    'دلار' => 'price_dollar_rl', 'dollar' => 'price_dollar_rl', 'usd' => 'price_dollar_rl',
    'یورو' => 'price_eur', 'euro' => 'price_eur',
    'درهم' => 'price_aed',
    'لیر' => 'price_try', 'ترکیه' => 'price_try',
    'پوند' => 'price_gbp',
    'طلا' => 'geram18', 'گرم' => 'geram18', 'عیار' => 'geram18',
    'مثقال' => 'mesghal', 'مسقال' => 'mesghal',
    'اونس' => 'ons',
    'آبشده' => 'gold_futures', 'ابشده' => 'gold_futures',
    'امامی' => 'retail_sekee', 'سکه' => 'retail_sekee', 'سكه' => 'retail_sekee',
    'بهار' => 'sekeb', 'بهار آزادی' => 'sekeb',
    'نیم سکه' => 'retail_nim', 'نیم‌سکه' => 'retail_nim',
    'ربع سکه' => 'retail_rob', 'ربع‌سکه' => 'retail_rob',
    'گرمی' => 'retail_gerami',
    'حباب' => 'coin_blubber',
    'بیت کوین' => 'crypto-bitcoin', 'بیتکوین' => 'crypto-bitcoin', 'bitcoin' => 'crypto-bitcoin', 'btc' => 'crypto-bitcoin',
    'تتر' => 'crypto-tether', 'tether' => 'crypto-tether', 'usdt' => 'crypto-tether',
    'اتریوم' => 'crypto-ethereum', 'ethereum' => 'crypto-ethereum', 'eth' => 'crypto-ethereum',
    'bnb' => 'crypto-bnb', 'بایننس' => 'crypto-bnb',
    'solana' => 'crypto-solana', 'سولانا' => 'crypto-solana',
    'ripple' => 'crypto-ripple', 'ریپل' => 'crypto-ripple', 'xrp' => 'crypto-ripple',
    'doge' => 'crypto-dogecoin', 'دوج' => 'crypto-dogecoin',
    'tron' => 'crypto-tron', 'ترون' => 'crypto-tron',
    'ada' => 'crypto-cardano', 'کاردانو' => 'crypto-cardano',
    'ltc' => 'crypto-litecoin', 'لایت' => 'crypto-litecoin',
    'dash' => 'crypto-dash', 'دش' => 'crypto-dash',
    'shib' => 'crypto-shiba-inu', 'شیبا' => 'crypto-shiba-inu',
    'ton' => 'crypto-toncoin', 'تون' => 'crypto-toncoin',
    'link' => 'crypto-chainlink', 'چینلینک' => 'crypto-chainlink',
    'matic' => 'crypto-polygon', 'پالیگان' => 'crypto-polygon',
    'uni' => 'crypto-uniswap', 'یونی' => 'crypto-uniswap',
];

$CAT_TITLES = [
    'ارز' => '💱 ارزها — بازار آزاد',
    'طلا' => '🥇 طلا',
    'سکه' => '🪙 سکه',
    'کریپتو' => '🌐 کریپتو',
];

// ============ CACHE ============
// Resilient loader: try local cache.json first; if missing/stale, fetch the
// live API (which also regenerates the cache file). Never returns [] on a
// transient cache miss.
function load_cache() {
    static $cache = null;
    if ($cache !== null) return $cache;

    // 1) local cache file, if fresh
    if (file_exists(CACHE_FILE)) {
        $raw = @file_get_contents(CACHE_FILE);
        $data = json_decode($raw, true);
        if (is_array($data) && (time() - ($data['ts_unix'] ?? 0) <= 300)) {
            $cats = $data['categories'] ?? [];
            if ($cats) { $cache = $cats; return $cache; }
        }
    }

    // 2) fall back to the live API (api.php writes cache.json for next time)
    $api = API_URL_SELF();
    $ch = curl_init($api);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($raw, true);
    if (is_array($data)) {
        $cats = $data['categories'] ?? [];
        if ($cats) { $cache = $cats; return $cats; }
    }

    // 3) last resort: serve stale cache rather than nothing
    if (file_exists(CACHE_FILE)) {
        $data = json_decode(@file_get_contents(CACHE_FILE), true);
        if (is_array($data)) {
            $cats = $data['categories'] ?? [];
            if ($cats) { $cache = $cats; return $cats; }
        }
    }

    return [];
}

// absolute URL of api.php in this same directory
function API_URL_SELF() {
    $dir = dirname($_SERVER['SCRIPT_NAME'] ?? '/prices/bot.php');
    $host = $_SERVER['HTTP_HOST'] ?? 'hodhodcandy.ir';
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return "{$proto}://{$host}{$dir}/api.php";
}

function get_item($slug) {
    $cats = load_cache();
    foreach ($cats as $cat) {
        foreach ($cat['items'] as $item) {
            if ($item['slug'] === $slug) return $item;
        }
    }
    return null;
}

function fmt_price($item) {
    if (!$item) return null;
    $dir = $item['direction'] ?? '';
    $arrow = $dir === 'up' ? '🔴' : ($dir === 'down' ? '🟢' : '⚪️');
    $price = $item['price'] ?? '';
    $change = $item['change'] ?? '';
    // Extract percentage from change like "(1.98%) 44,000"
    $pct = '';
    if (preg_match('/\(([\d.]+)%\)/', $change, $m)) {
        $pct = '(' . $m[1] . '%)';
    }
    return "{$arrow} {$price}  {$pct}";
}

function compact_num($nstr) {
    $n = (float)str_replace(',', '', $nstr);
    if ($n >= 1e9) return number_format($n / 1e9, 2) . ' میلیارد';
    return $nstr;
}

// ============ FORMATTERS ============
function fmt_cat($catName) {
    global $CATALOG, $CAT_TITLES;
    $items = $CATALOG[$catName] ?? [];
    if (!$items) return "⚠️ خطا";
    $out = ["<b>{$CAT_TITLES[$catName]}</b>\n"];
    foreach ($items as [$slug, $title]) {
        $item = get_item($slug);
        if ($item) {
            $line = fmt_price($item);
            if ($line) $out[] = "{$title}: {$line}";
        }
    }
    if (count($out) < 2) return "⚠️ خطا در دریافت قیمت‌ها";
    $out[] = "\n🕐 tgju.org";
    return implode("\n", $out);
}

function fmt_summary() {
    global $SUMMARY;
    $out = ["<b>📋 خلاصه بازار</b>\n"];
    foreach ($SUMMARY as [$slug, $title]) {
        $item = get_item($slug);
        if (!$item) continue;
        $line = fmt_price($item);
        if (!$line) continue;
        // keep coins full, compact huge numbers
        $isCoin = (strpos($title, 'سکه') !== false || strpos($title, 'بهار') !== false);
        // $line = "🔴 2,286,000  (0.83%)"; split on the colored dot
        // $line = "🔴 2,286,000  (0.83%)"  ->  keep coins full, compact big currencies
        if (preg_match('/^(🔴|🟢|⚪️)\s+([\d,]+(?:\.\d+)?)\s*(.*)$/', $line, $m)) {
            $num = $isCoin ? $m[2] : compact_num($m[2]);
            $out[] = "{$title} {$m[1]} {$num} {$m[3]}";
        } else {
            $out[] = "{$title}: {$line}";
        }
    }
    if (count($out) < 2) return "⚠️ خطا در دریافت قیمت‌ها";
    $out[] = "\n🕐 tgju.org";
    return implode("\n", $out);
}

// ============ TELEGRAM ============
function tg($method, $params = []) {
    $ch = curl_init(API_URL . $method);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($params),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 10,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true) ?: [];
}

function send_message($chatId, $text, $markup = null) {
    $params = [
        'chat_id' => $chatId,
        'text' => substr($text, 0, 4000),
        'parse_mode' => 'HTML',
    ];
    if ($markup) $params['reply_markup'] = $markup;
    return tg('sendMessage', $params);
}

function main_menu() {
    return json_encode(['inline_keyboard' => [
        [['text' => '📋 خلاصه بازار', 'callback_data' => 'sum']],
        [['text' => '💱 ارز',    'callback_data' => 'cat:ارز'],
         ['text' => '🥇 طلا',    'callback_data' => 'cat:طلا']],
        [['text' => '🪙 سکه',    'callback_data' => 'cat:سکه'],
         ['text' => '🌐 کریپتو', 'callback_data' => 'cat:کریپتو']],
    ]]);
}

function reply_menu() {
    return json_encode(['keyboard' => [
        [['text' => '📋 خلاصه بازار']],
        [['text' => '💱 ارز'], ['text' => '🥇 طلا'],
         ['text' => '🪙 سکه'], ['text' => '🌐 کریپتو']],
    ], 'resize_keyboard' => true]);
}

$REPLY_LABELS = [
    '📋 خلاصه بازار' => 'sum',
    '/prices' => 'sum', '/قیمت' => 'sum',
    '💱 ارز' => 'cat:ارز', '/arz' => 'cat:ارز',
    '🥇 طلا' => 'cat:طلا', '/tala' => 'cat:طلا',
    '🪙 سکه' => 'cat:سکه', '/sekke' => 'cat:سکه',
    '🌐 کریپتو' => 'cat:کریپتو', '/crypto' => 'cat:کریپتو',
];

function route_menu($key) {
    if ($key === 'sum') return fmt_summary();
    if (strpos($key, 'cat:') === 0) return fmt_cat(substr($key, 4));
    return null;
}

// ============ HANDLER ============
$raw = file_get_contents('php://input');
$update = json_decode($raw, true);
if (!$update) {
    http_response_code(200);
    echo 'ok';
    exit;
}

// Message
$msg = $update['message'] ?? null;
$cb = $update['callback_query'] ?? null;

if ($msg) {
    $chatId = $msg['chat']['id'];
    $text = trim($msg['text'] ?? '');
    $low = mb_strtolower($text);

    if (strpos($low, '/start') === 0) {
        send_message($chatId,
            "👋 <b>سلام!</b> به ربات قیمت لحظه‌ای بازار خوش اومدی\n\n"
            . "🔹 روی دکمه‌ها بزن یا\n"
            . "🔹 اسم هر چیزی رو بنویس (مثلاً: دلار، طلا، سکه امامی)\n\n"
            . "منبع: tgju.org",
            main_menu());
        send_message($chatId,
            "⌨️ دکمه‌های پایین هم فعاله!",
            reply_menu());
    } elseif (isset($REPLY_LABELS[$text])) {
        send_message($chatId, route_menu($REPLY_LABELS[$text]), main_menu());
    } elseif ($text) {
        // keyword search
        $found = [];
        foreach ($KEYWORDS as $kw => $slug) {
            if (mb_strpos($low, mb_strtolower($kw)) !== false || mb_strpos($text, $kw) !== false) {
                if ($slug === '__ALL_CURRENCY__') {
                    foreach ($CATALOG['ارز'] as [$s, $t]) $found[$s] = true;
                } else {
                    $found[$slug] = true;
                }
            }
        }
        if ($found) {
            $out = [];
            foreach (array_keys($found) as $slug) {
                $item = get_item($slug);
                if ($item) {
                    // find title
                    $title = $slug;
                    foreach ($CATALOG as $catItems) {
                        foreach ($catItems as [$s, $t]) {
                            if ($s === $slug) { $title = $t; break 2; }
                        }
                    }
                    $line = fmt_price($item);
                    if ($line) $out[] = "{$title}: {$line}";
                }
            }
            send_message($chatId, $out ? implode("\n", $out) : "قیمتی پیدا نشد ⚠️", main_menu());
        } else {
            send_message($chatId,
                "🤔 نفهمیدم چی می‌خواستی!\n"
                . "مثلاً بنویس: <code>دلار</code> یا <code>سکه</code> یا <code>طلا</code>",
                main_menu());
        }
    }
} elseif ($cb) {
    $data = $cb['data'] ?? '';
    $chatId = $cb['message']['chat']['id'];
    $msgId = $cb['message']['message_id'];
    tg('answerCallbackQuery', ['callback_query_id' => $cb['id']]);

    $newText = route_menu($data);
    if ($newText !== null) {
        tg('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $msgId,
            'text' => substr($newText, 0, 4000),
            'parse_mode' => 'HTML',
            'reply_markup' => main_menu(),
        ]);
    }
}

http_response_code(200);
echo 'ok';
