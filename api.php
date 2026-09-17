<?php
/**
 * PoolSanj Price API - Scrapes TGJU.org
 * Returns JSON with all market prices
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Access-Control-Allow-Origin: *');

// Cache file
$CACHE = __DIR__ . '/cache.json';
$TTL = 25; // seconds

// Return cache if fresh
if (file_exists($CACHE)) {
    $age = time() - filemtime($CACHE);
    if ($age < $TTL) {
        echo file_get_contents($CACHE);
        exit;
    }
}

// Scrape prices
$prices = scrape_tgju();

if (empty($prices)) {
    // Try to serve stale cache
    if (file_exists($CACHE)) {
        echo file_get_contents($CACHE);
        exit;
    }
    echo json_encode(['error' => 'Failed to fetch prices', 'ts' => date('c')], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = [
    'ts' => date('c'),
    'ts_unix' => time(),
    'source' => 'tgju.org',
    'categories' => $prices
];

$json = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
file_put_contents($CACHE, $json);
echo $json;

function scrape_tgju() {
    $categories = [];
    
    // Fetch main page HTML
    $html = fetch_url('https://www.tgju.org/');
    if (!$html) return null;
    
    // Parse prices from HTML
    $all = parse_prices($html);
    if (empty($all)) return null;
    
    // Fetch missing crypto from TGJU profile pages (coins not on homepage)
    $missingCrypto = array_diff(['crypto-bnb','crypto-chainlink','crypto-polygon','crypto-uniswap'], array_keys($all));
    $dollarRate = isset($all['price_dollar_rl']) ? (float)str_replace(',', '', $all['price_dollar_rl']['p']) : 0;
    foreach ($missingCrypto as $slug) {
        $profileHtml = fetch_url("https://www.tgju.org/profile/$slug");
        if (!$profileHtml) continue;
        $found = false;
        // Try full IRR price first (text-left class, second match = IRR value)
        if (preg_match_all('/<td[^>]*class=["\']text-left["\'][^>]*>\s*([\d,]+)/', $profileHtml, $matches)) {
            // Pick the value with commas (IRR) — typically 2nd match
            foreach ($matches[1] as $val) {
                if (strpos($val, ',') !== false) {
                    $all[$slug] = ['p' => $val, 'c' => '', 'dir' => ''];
                    $found = true;
                    break;
                }
            }
        }
        // Fallback: USD price × dollar rate
        if (!$found && $dollarRate > 0) {
            $tds = [];
            preg_match_all('/<td[^>]*>([\d\.]+)</', $profileHtml, $tds);
            foreach ($tds[1] as $val) {
                if ((float)$val > 0.001) {
                    $all[$slug] = ['p' => number_format((int)round((float)$val * $dollarRate)), 'c' => '', 'dir' => ''];
                    $found = true;
                    break;
                }
            }
        }
        usleep(100000);
    }
    
    // Group by category
    $categories = [
        'ارز' => [
            'title' => '💱 ارز',
            'items' => []
        ],
        'طلا' => [
            'title' => '🥇 طلا',
            'items' => []
        ],
        'سکه' => [
            'title' => '🪙 سکه',
            'items' => []
        ],
        'کریپتو' => [
            'title' => '🌐 کریپتو',
            'items' => []
        ]
    ];
    
    $mapping = [
        // Currency
        'price_dollar_rl'  => ['cat' => 'ارز', 'name' => 'دلار آمریکا', 'icon' => '🇺🇸'],
        'price_eur'        => ['cat' => 'ارز', 'name' => 'یورو', 'icon' => '🇪🇺'],
        'price_gbp'        => ['cat' => 'ارز', 'name' => 'پوند انگلیس', 'icon' => '🇬🇧'],
        'price_chf'        => ['cat' => 'ارز', 'name' => 'فرانک سوئیس', 'icon' => '🇨🇭'],
        'price_aed'        => ['cat' => 'ارز', 'name' => 'درهم امارات', 'icon' => '🇦🇪'],
        'price_sar'        => ['cat' => 'ارز', 'name' => 'ریال عربستان', 'icon' => '🇸🇦'],
        'price_qar'        => ['cat' => 'ارز', 'name' => 'ریال قطر', 'icon' => '🇶🇦'],
        'price_omr'        => ['cat' => 'ارز', 'name' => 'ریال عمان', 'icon' => '🇴🇲'],
        'price_kwd'        => ['cat' => 'ارز', 'name' => 'دینار کویت', 'icon' => '🇰🇼'],
        'price_bhd'        => ['cat' => 'ارز', 'name' => 'دینار بحرین', 'icon' => '🇧🇭'],
        'price_iqd'        => ['cat' => 'ارز', 'name' => 'دینار عراق', 'icon' => '🇮🇶'],
        'price_try'        => ['cat' => 'ارز', 'name' => 'لیر ترکیه', 'icon' => '🇹🇷'],
        'price_syp'        => ['cat' => 'ارز', 'name' => 'لیر سوریه', 'icon' => '🇸🇾'],
        'price_afn'        => ['cat' => 'ارز', 'name' => 'افغانی', 'icon' => '🇦🇫'],
        'price_rub'        => ['cat' => 'ارز', 'name' => 'روبل روسیه', 'icon' => '🇷🇺'],
        'price_azn'        => ['cat' => 'ارز', 'name' => 'منات آذربایجان', 'icon' => '🇦🇿'],
        'price_amd'        => ['cat' => 'ارز', 'name' => 'درام ارمنستان', 'icon' => '🇦🇲'],
        'price_gel'        => ['cat' => 'ارز', 'name' => 'لاری گرجستان', 'icon' => '🇬🇪'],
        'price_kgs'        => ['cat' => 'ارز', 'name' => 'سوم قرقیزستان', 'icon' => '🇰🇬'],
        'price_tjs'        => ['cat' => 'ارز', 'name' => 'سامانی تاجیکستان', 'icon' => '🇹🇯'],
        'price_tmt'        => ['cat' => 'ارز', 'name' => 'منات ترکمنستان', 'icon' => '🇹🇲'],
        'price_cny'        => ['cat' => 'ارز', 'name' => 'یوان چین', 'icon' => '🇨🇳'],
        'price_jpy'        => ['cat' => 'ارز', 'name' => 'ین ژاپن', 'icon' => '🇯🇵'],
        'price_krw'        => ['cat' => 'ارز', 'name' => 'ون کره', 'icon' => '🇰🇷'],
        'price_inr'        => ['cat' => 'ارز', 'name' => 'روپیه هند', 'icon' => '🇮🇳'],
        'price_pkr'        => ['cat' => 'ارز', 'name' => 'روپیه پاکستان', 'icon' => '🇵🇰'],
        'price_myr'        => ['cat' => 'ارز', 'name' => 'رینگیت مالزی', 'icon' => '🇲🇾'],
        'price_thb'        => ['cat' => 'ارز', 'name' => 'بات تایلند', 'icon' => '🇹🇭'],
        'price_hkd'        => ['cat' => 'ارز', 'name' => 'دلار هنگ‌کنگ', 'icon' => '🇭🇰'],
        'price_sgd'        => ['cat' => 'ارز', 'name' => 'دلار سنگاپور', 'icon' => '🇸🇬'],
        'price_cad'        => ['cat' => 'ارز', 'name' => 'دلار کانادا', 'icon' => '🇨🇦'],
        'price_aud'        => ['cat' => 'ارز', 'name' => 'دلار استرالیا', 'icon' => '🇦🇺'],
        'price_nzd'        => ['cat' => 'ارز', 'name' => 'دلار نیوزیلند', 'icon' => '🇳🇿'],
        'price_dkk'        => ['cat' => 'ارز', 'name' => 'کرون دانمارک', 'icon' => '🇩🇰'],
        'price_sek'        => ['cat' => 'ارز', 'name' => 'کرون سوئد', 'icon' => '🇸🇪'],
        'price_nok'        => ['cat' => 'ارز', 'name' => 'کرون نروژ', 'icon' => '🇳🇴'],
        // Gold
        'geram18'          => ['cat' => 'طلا', 'name' => 'طلای ۱۸ عیار', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><rect x="4" y="8" width="24" height="16" rx="3" fill="#FFD54F" stroke="#F9A825" stroke-width="2"/><text x="16" y="21" font-size="9" text-anchor="middle" fill="#8D6E00" font-weight="bold">18</text></svg>'],
        'mesghal'          => ['cat' => 'طلا', 'name' => 'مثقال طلا', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><path d="M16 3l10 5v8c0 6-4.5 10.5-10 13C10.5 26.5 6 22 6 16V8l10-5z" fill="#FFD54F" stroke="#F9A825" stroke-width="1.5"/><circle cx="16" cy="15" r="4" fill="#F9A825"/></svg>'],
        'ons'              => ['cat' => 'طلا', 'name' => 'اونس جهانی', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="13" fill="#1565C0"/><path d="M8 20c2-6 5-9 8-9s6 3 8 9" stroke="#FFD54F" stroke-width="2.5" fill="none"/><path d="M6 16h20" stroke="#FFD54F" stroke-width="2"/><text x="16" y="13" font-size="7" text-anchor="middle" fill="#FFD54F" font-weight="bold">OZ</text></svg>'],
        'gold_futures'     => ['cat' => 'طلا', 'name' => 'طلای آبشده', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><path d="M16 4c4 4 6 7 6 10a6 6 0 11-12 0c0-3 2-6 6-10z" fill="#42A5F5"/><path d="M16 12c2 2 3 3.5 3 5a3 3 0 11-6 0c0-1.5 1-3 3-5z" fill="#B3E5FC"/></svg>'],
        // Coin
        'retail_sekee'     => ['cat' => 'سکه', 'name' => 'سکه امامی', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="15" fill="#FFD54F" stroke="#F9A825" stroke-width="2"/><circle cx="16" cy="16" r="11" fill="none" stroke="#F9A825" stroke-width="1.2" opacity="0.6"/><text x="16" y="21" font-size="12" text-anchor="middle" fill="#8D6E00" font-weight="bold">₹</text></svg>'],
        'sekeb'            => ['cat' => 'سکه', 'name' => 'سکه بهار آزادی', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="15" fill="#FFD54F" stroke="#F9A825" stroke-width="2"/><circle cx="16" cy="16" r="11" fill="none" stroke="#F9A825" stroke-width="1.2" opacity="0.6"/><text x="16" y="21" font-size="12" text-anchor="middle" fill="#8D6E00" font-weight="bold">₹</text></svg>'],
        'retail_nim'       => ['cat' => 'سکه', 'name' => 'نیم سکه', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="15" fill="#FFD54F" stroke="#F9A825" stroke-width="2"/><path d="M16 1 A15 15 0 0 1 16 31 Z" fill="#F9A825" opacity="0.5"/><text x="16" y="21" font-size="12" text-anchor="middle" fill="#8D6E00" font-weight="bold">½</text></svg>'],
        'retail_rob'       => ['cat' => 'سکه', 'name' => 'ربع سکه', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="15" fill="#FFD54F" stroke="#F9A825" stroke-width="2"/><path d="M16 1 A15 15 0 0 1 16 16 Z" fill="#F9A825" opacity="0.35"/><text x="16" y="21" font-size="11" text-anchor="middle" fill="#8D6E00" font-weight="bold">¼</text></svg>'],
        'retail_gerami'    => ['cat' => 'سکه', 'name' => 'گرمی', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="15" fill="#E1F5FE" stroke="#4FC3F7" stroke-width="2"/><circle cx="16" cy="16" r="10" fill="none" stroke="#4FC3F7" stroke-width="1.2" opacity="0.6"/><text x="16" y="20" font-size="8" text-anchor="middle" fill="#0277BD" font-weight="bold">گ</text></svg>'],
        'coin_blubber'     => ['cat' => 'سکه', 'name' => 'حباب سکه', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="20" r="6" fill="#81D4FA" opacity="0.7"/><circle cx="20" cy="14" r="8" fill="#4FC3F7" opacity="0.55"/><circle cx="24" cy="22" r="4" fill="#B3E5FC" opacity="0.8"/></svg>'],
        // Crypto
        'crypto-bitcoin'   => ['cat' => 'کریپتو', 'name' => 'بیتکوین', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="#F7931A"/><path d="M22.9 14.2c.3-2.1-1.3-3.2-3.4-4l.7-2.7-1.7-.4-.7 2.6c-.4-.1-.9-.2-1.4-.3l.7-2.6-1.7-.4-.7 2.7-1.1-.3-2.3-.6-.5 1.8s1.3.3 1.2.3c.7.2.8.6.8.9l-.8 3.1v.1l-1.1 4.5c-.1.2-.3.5-.8.4 0 0-1.2-.3-1.2-.3l-.8 1.9 2.2.5 1.2.3-.7 2.8 1.7.4.7-2.7c.5.1.9.2 1.4.4l-.7 2.7 1.7.4.7-2.8c2.9.5 5 .3 5.9-2.3.7-2.1 0-3.3-1.5-4.1 1.1-.2 1.9-1 2.1-2.5zm-3.8 5.5c-.5 2.1-4.1 1-5.2.7l.9-3.8c1.1.3 4.8.8 4.3 3.1zm.5-5.5c-.5 2-3.4 1-4.4.7l.8-3.4c1 .2 4.2.7 3.6 2.7z" fill="#fff"/></svg>'],
        'crypto-tether'    => ['cat' => 'کریپتو', 'name' => 'تتر', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="#26A17B"/><path d="M17.5 11.5v-2h4v-2h-11v2h4v2c-3.3.2-5.8 1.6-5.8 3.4s2.5 3.2 5.8 3.4v4.6h3v-4.6c3.3-.2 5.8-1.6 5.8-3.4s-2.5-3.2-5.8-3.4zm-1.5 5c-.2 0-.4 0-.5-.1v-3c.2 0 .3-.1.5-.1 1.6 0 2.9.7 2.9 1.6s-1.3 1.6-2.9 1.6z" fill="#fff"/></svg>'],
        'crypto-ethereum'  => ['cat' => 'کریپتو', 'name' => 'اتریوم', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><path d="M16 2l9 14-9 6-9-6 9-14z" fill="#627EEA"/><path d="M16 2v20l-9-6 9-14z" fill="#8FA7F0" opacity="0.7"/><path d="M16 12l9 4-9 6-9-6 9-4z" fill="#fff" opacity="0.9"/><path d="M16 22v8l-9-12 9 4z" fill="#4A5FC9"/><path d="M16 22v8l9-12-9 4z" fill="#8FA7F0"/></svg>'],
        'crypto-bnb'       => ['cat' => 'کریپتو', 'name' => 'بایننس کوین', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="#F3BA2F"/><path d="M12 8l4 4 4-4 2.5 2.5-4 4 4 4L20 25l-4-4-4 4-2.5-2.5 4-4-4-4L12 8z" fill="#fff"/></svg>'],
        'crypto-solana'    => ['cat' => 'کریپتو', 'name' => 'سولانا', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="sg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#9945FF"/><stop offset="1" stop-color="#14F195"/></linearGradient></defs><path d="M9 12h14l3-3-14-0.5L6 11.5l3 0.5z" fill="url(#sg)"/><path d="M9 16h14l3-3H9l-3 3z" fill="url(#sg)" opacity="0.8"/><path d="M9 20h14l3-3H9l-3 3z" fill="url(#sg)" opacity="0.6"/></svg>'],
        'crypto-ripple'    => ['cat' => 'کریپتو', 'name' => 'ریپل', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="#23292F"/><circle cx="12" cy="12" r="3.5" fill="#fff"/><circle cx="21" cy="12" r="3.5" fill="#fff" opacity="0.7"/><circle cx="16" cy="21" r="3.5" fill="#fff" opacity="0.85"/></svg>'],
        'crypto-dogecoin'  => ['cat' => 'کریپتو', 'name' => 'دوج کوین', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="#C2A633"/><path d="M13 8h5c4 0 6.5 3 6.5 8s-2.5 8-6.5 8h-5V8zm3 3v10h1.5c2.5 0 4-2 4-5s-1.5-5-4-5H16z" fill="#fff"/></svg>'],
        'crypto-tron'      => ['cat' => 'کریپتو', 'name' => 'ترون', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="#EF0027"/><path d="M8 10l8 14 8-14-8 3-8-3z" fill="#fff"/><path d="M8 10l8 3 8-3-8-1-8 1z" fill="#fff" opacity="0.6"/></svg>'],
        'crypto-cardano'   => ['cat' => 'کریپتو', 'name' => 'کاردانو', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="#0F4FA8"/><circle cx="16" cy="16" r="7" fill="#fff" opacity="0.95"/><ellipse cx="16" cy="16" rx="7" ry="3" fill="#0F4FA8" opacity="0.25"/></svg>'],
        'crypto-litecoin'  => ['cat' => 'کریپتو', 'name' => 'لایت کوین', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="#345D9D"/><path d="M13 24l3-11-4-1 1-4 6 1-3.5 15H13z" fill="#fff"/></svg>'],
        'crypto-bitcoin-cash' => ['cat' => 'کریپتو', 'name' => 'بیت‌کوین کش', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="#0AC18E"/><path d="M19 8c3 0.5 4.5 2.5 4 5-0.5 2-2 3-4 3.5 2 0.5 3 2 2.5 4.5-0.5 2.5-2.5 4-5.5 3.5l-1 4-3-0.5 1-4-2.5-0.5 1-4 2.5 0.5 4-15.5zm-1.5 6.5c1.5 0.3 2.5-0.3 2.8-1.6 0.3-1.3-0.4-2.1-1.8-2.4l-1 4zm-1.6 6.4c1.6 0.3 2.6-0.3 2.9-1.7 0.3-1.4-0.4-2.3-2-2.6l-0.9 4.3z" fill="#fff"/></svg>'],
        'crypto-stellar'   => ['cat' => 'کریپتو', 'name' => 'استellar', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="#14B6E4"/><path d="M8 22c4-1 7-4 8-8 1 4 4 7 8 8-4 1-7 4-8 8-1-4-4-7-8-8z" fill="#fff" transform="translate(0,-7) scale(1,0.85)"/></svg>'],
        'crypto-polkadot'  => ['cat' => 'کریپتو', 'name' => 'پولکادات', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="#E6007A"/><ellipse cx="16" cy="16" rx="6" ry="11" fill="none" stroke="#fff" stroke-width="2.5"/><ellipse cx="16" cy="16" rx="11" ry="6" fill="none" stroke="#fff" stroke-width="2.5" opacity="0.55"/></svg>'],
        'crypto-avalanche' => ['cat' => 'کریپتو', 'name' => 'آوالانچ', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><rect width="32" height="32" rx="16" fill="#E84142"/><path d="M16 7l7 13H9l7-13z" fill="#fff"/><path d="M16 13l4 7h-8l4-7z" fill="#E84142"/></svg>'],
        'crypto-dash'      => ['cat' => 'کریپتو', 'name' => 'دش', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="#1C7BC9"/><path d="M18 7c4 0.5 6 3 5.5 6.5-0.5 3.5-3.5 5.5-7.5 5l-1.5 6.5-3.5-0.5L13 8l5-1zm-1 4l-1.5 6c2 0.4 3.5-0.6 3.9-2.8 0.4-2.1-0.4-3.2-2.4-3.2z" fill="#fff"/></svg>'],
        'crypto-shiba-inu' => ['cat' => 'کریپتو', 'name' => 'شیبا اینو', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="#F7B531"/><path d="M8 13c0-4 3-6 8-6s8 2 8 6c0 5-3 9-8 9s-8-4-8-9z" fill="#FCE0B8"/><circle cx="12" cy="14" r="1.5" fill="#5D4037"/><circle cx="20" cy="14" r="1.5" fill="#5D4037"/><path d="M16 18c-1.5 0-2.5 0.5-2.5 1.5s1 1.5 2.5 1.5 2.5-0.5 2.5-1.5-1-1.5-2.5-1.5z" fill="#E57373"/><path d="M9 9l2-3 3 2-2 2-3-1zm14 0l-2-3-3 2 2 2 3-1z" fill="#F0A030"/></svg>'],
        'crypto-toncoin'   => ['cat' => 'کریپتو', 'name' => 'تون‌کوین', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="#0098EA"/><path d="M10 12h12l-6 12-6-12z" fill="#fff"/><path d="M13 12h6l-3 6-3-6z" fill="#0098EA"/></svg>'],
        'crypto-chainlink' => ['cat' => 'کریپتو', 'name' => 'چینلینک', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="#2A5ADA"/><path d="M16 6l8 4.5v9L16 24l-8-4.5v-9L16 6z" fill="none" stroke="#fff" stroke-width="2"/><path d="M16 11l4 2.2v4.6L16 20l-4-2.2v-4.6L16 11z" fill="#fff"/></svg>'],
        'crypto-polygon'    => ['cat' => 'کریپتو', 'name' => 'پالیگان', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><path d="M16 2l12 7v14l-12 7L4 23V9l12-7z" fill="#8247E5"/><path d="M16 9l7 4v8l-7 4-7-4v-8l7-4z" fill="#fff" opacity="0.95"/></svg>'],
        'crypto-uniswap'    => ['cat' => 'کریپتو', 'name' => 'یونی‌سوآپ', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="#FF007A"/><path d="M16 8c4 0 7 3 7 7s-3 7-7 7-7-3-7-7 3-7 7-7z" fill="#fff"/><path d="M12 12l2-3 1 3-3 0zm6 2c1 0 2 1 2 2s-1 2-2 2" fill="#FF007A"/><path d="M20 9l3-2-1 3-2-1z" fill="#fff"/></svg>'],
    ];
    
    foreach ($mapping as $slug => $meta) {
        if (isset($all[$slug])) {
            $item = $all[$slug];
            $categories[$meta['cat']]['items'][] = [
                'slug' => $slug,
                'name' => $meta['name'],
                'icon' => $meta['icon'],
                'price' => $item['p'] ?? '',
                'change' => $item['c'] ?? '',
                'direction' => $item['dir'] ?? '',
            ];
        }
    }
    
    // Remove empty categories
    foreach ($categories as $cat => $data) {
        if (empty($data['items'])) {
            unset($categories[$cat]);
        }
    }
    
    return $categories;
}

function parse_prices($html) {
    $prices = [];
    
    // Match each <tr> that contains data-market-nameslug
    $trPattern = '/<tr\b[^>]*data-market-nameslug="([^"]+)"[^>]*>(.*?)<\/tr>/si';
    
    if (preg_match_all($trPattern, $html, $trMatches, PREG_SET_ORDER)) {
        foreach ($trMatches as $tr) {
            $slug = $tr[1];
            $rowHtml = $tr[2];
            
            // Find direction: only inside <span> or <div> (not on <td> market-low/market-high)
            $dir = '';
            if (preg_match('/<(?:span|div)\s+class=["\']low["\']/', $rowHtml)) {
                $dir = 'down';
            } elseif (preg_match('/<(?:span|div)\s+class=["\']high["\']/', $rowHtml)) {
                $dir = 'up';
            }
            
            // Extract all <td> contents
            preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $rowHtml, $tds);
            
            // Find price: prefer market-price-irr (full IRR, e.g. bitcoin ~178,000,000,000)
            // fallback: class="nf" (main table) or class="market-price" (secondary)
            $price = '';
            $priceIrr = '';
            $change = '';
            foreach ($tds[1] as $i => $tdHtml) {
                $tdTag = $tds[0][$i];
                $clean = trim(strip_tags($tdHtml));
                
                // Full IRR price cell (secondary table has it for crypto)
                if ($priceIrr === '' && $clean !== '' && preg_match('/class=["\']market-price-irr["\']/', $tdTag)) {
                    $priceIrr = $clean;
                }
                
                // Price cell: class="nf" or class="market-price"
                if ($price === '' && $clean !== '' && preg_match('/class=["\'](?:nf|market-price)["\']/', $tdTag)) {
                    $price = $clean;
                }
                
                // Change cell: contains <span class="high/low"> or <div class="high/low">
                if ($change === '' && preg_match('/<(?:span|div)\s+class=["\'](?:low|high)["\']/', $tdTag)) {
                    $change = $clean;
                }
            }
            
            // Prefer the full IRR price when available (crypto rows show USD in main table)
            if ($priceIrr !== '') {
                $price = $priceIrr;
            }
            
            // Fallback: if no price found via class, use first non-empty td
            if ($price === '' && !empty($tds[1])) {
                foreach ($tds[1] as $tdHtml) {
                    $clean = trim(strip_tags($tdHtml));
                    if ($clean !== '' && preg_match('/^\d/', $clean)) {
                        $price = $clean;
                        break;
                    }
                }
            }
            
            if ($price !== '') {
                // Always overwrite if this row has a full IRR price (more valuable than USD)
                // or no previous match exists, or new match has direction but old doesn't
                $hasIrr = strpos($price, ',') !== false;
                $prevIrr = isset($prices[$slug]) && strpos($prices[$slug]['p'], ',') !== false;
                if (!isset($prices[$slug]) || $hasIrr || (!$prevIrr && $dir !== '' && ($prices[$slug]['dir'] ?? '') === '')) {
                    $prices[$slug] = [
                        'p' => $price,
                        'c' => $change,
                        'dir' => $dir
                    ];
                }
            }
        }
    }
    
    // For ANY crypto slug where price is USD (no comma-groups) and no IRR was found:
    // convert to IRR using the dollar rate from the same page
    if (isset($prices['price_dollar_rl']['p'])) {
        $dollarRate = (float)str_replace(',', '', $prices['price_dollar_rl']['p']);
        if ($dollarRate > 0) {
            foreach ($prices as $slug => &$item) {
                if (strpos($slug, 'crypto-') === 0 && strpos($item['p'], ',') === false) {
                    $usd = (float)$item['p'];
                    if ($usd > 0) {
                        $item['p'] = number_format((int)round($usd * $dollarRate));
                    }
                }
            }
            unset($item);
        }
    }
    
    // Fallback: try JSON in script tags
    if (empty($prices)) {
        // Try to find __NEXT_DATA__ or similar
        if (preg_match('/__NEXT_DATA__.*?<\/script>/s', $html, $m)) {
            // Extract JSON
            if (preg_match('/\{.*\}/s', $m[0], $j)) {
                $data = json_decode($j[0], true);
                if ($data && isset($data['props']['pageProps']['indicators'])) {
                    foreach ($data['props']['pageProps']['indicators'] as $ind) {
                        $slug = $ind['name_slug'] ?? $ind['slug'] ?? '';
                        if ($slug) {
                            $prices[$slug] = [
                                'p' => $ind['price'] ?? $ind['p'] ?? '',
                                'c' => $ind['change'] ?? $ind['c'] ?? '',
                                'dir' => ($ind['change_percent'] ?? 0) > 0 ? 'up' : 'down'
                            ];
                        }
                    }
                }
            }
        }
    }
    
    // Fallback 2: scrape table rows
    if (empty($prices)) {
        // Simple regex for common TGJU table format
        preg_match_all('/class="[^"]*table-item[^"]*"[^>]*>.*?data-market-nameslug="([^"]+)"[^>]*>.*?<td[^>]*>([\d,\.]+)<\/td>/s', $html, $m2, PREG_SET_ORDER);
        foreach ($m2 as $row) {
            $prices[$row[1]] = ['p' => $row[2], 'c' => '', 'dir' => ''];
        }
    }
    
    return $prices;
}

function fetch_url($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 Chrome/120.0.0.0 Mobile Safari/537.36',
        CURLOPT_ENCODING => 'gzip',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    $html = curl_exec($ch);
    curl_close($ch);
    return $html ?: null;
}
