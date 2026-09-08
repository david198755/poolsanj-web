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
        // Try full IRR price first (text-left class on profile pages)
        if (preg_match('/<td[^>]*class=["\']text-left["\'][^>]*>([\d,]+)/', $profileHtml, $m)) {
            $all[$slug] = ['p' => $m[1], 'c' => '', 'dir' => ''];
            $found = true;
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
        'geram18'          => ['cat' => 'طلا', 'name' => 'طلای ۱۸ عیار', 'icon' => '🟡'],
        'mesghal'          => ['cat' => 'طلا', 'name' => 'مثقال طلا', 'icon' => '⚖️'],
        'ons'              => ['cat' => 'طلا', 'name' => 'اونس جهانی', 'icon' => '🌍'],
        'gold_futures'     => ['cat' => 'طلا', 'name' => 'طلای آبشده', 'icon' => '💧'],
        // Coin
        'retail_sekee'     => ['cat' => 'سکه', 'name' => 'سکه امامی', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="15" fill="#FFD54F" stroke="#F9A825" stroke-width="2"/><circle cx="16" cy="16" r="11" fill="none" stroke="#F9A825" stroke-width="1.2" opacity="0.6"/><text x="16" y="21" font-size="12" text-anchor="middle" fill="#8D6E00" font-weight="bold">₹</text></svg>'],
        'sekeb'            => ['cat' => 'سکه', 'name' => 'سکه بهار آزادی', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="15" fill="#FFD54F" stroke="#F9A825" stroke-width="2"/><circle cx="16" cy="16" r="11" fill="none" stroke="#F9A825" stroke-width="1.2" opacity="0.6"/><text x="16" y="21" font-size="12" text-anchor="middle" fill="#8D6E00" font-weight="bold">₹</text></svg>'],
        'retail_nim'       => ['cat' => 'سکه', 'name' => 'نیم سکه', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="15" fill="#FFD54F" stroke="#F9A825" stroke-width="2"/><path d="M16 1 A15 15 0 0 1 16 31 Z" fill="#F9A825" opacity="0.5"/><text x="16" y="21" font-size="12" text-anchor="middle" fill="#8D6E00" font-weight="bold">½</text></svg>'],
        'retail_rob'       => ['cat' => 'سکه', 'name' => 'ربع سکه', 'icon' => '¼'],
        'retail_gerami'    => ['cat' => 'سکه', 'name' => 'گرمی', 'icon' => '🔹'],
        'coin_blubber'     => ['cat' => 'سکه', 'name' => 'حباب سکه', 'icon' => '🫧'],
        // Crypto
        'crypto-bitcoin'   => ['cat' => 'کریپتو', 'name' => 'بیتکوین', 'icon' => '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="#F7931A"/><path d="M22.9 14.2c.3-2.1-1.3-3.2-3.4-4l.7-2.7-1.7-.4-.7 2.6c-.4-.1-.9-.2-1.4-.3l.7-2.6-1.7-.4-.7 2.7-1.1-.3-2.3-.6-.5 1.8s1.3.3 1.2.3c.7.2.8.6.8.9l-.8 3.1v.1l-1.1 4.5c-.1.2-.3.5-.8.4 0 0-1.2-.3-1.2-.3l-.8 1.9 2.2.5 1.2.3-.7 2.8 1.7.4.7-2.7c.5.1.9.2 1.4.4l-.7 2.7 1.7.4.7-2.8c2.9.5 5 .3 5.9-2.3.7-2.1 0-3.3-1.5-4.1 1.1-.2 1.9-1 2.1-2.5zm-3.8 5.5c-.5 2.1-4.1 1-5.2.7l.9-3.8c1.1.3 4.8.8 4.3 3.1zm.5-5.5c-.5 2-3.4 1-4.4.7l.8-3.4c1 .2 4.2.7 3.6 2.7z" fill="#fff"/></svg>'],
        'crypto-tether'    => ['cat' => 'کریپتو', 'name' => 'تتر', 'icon' => '💵'],
        'crypto-ethereum'  => ['cat' => 'کریپتو', 'name' => 'اتریوم', 'icon' => '🔷'],
        'crypto-bnb'       => ['cat' => 'کریپتو', 'name' => 'بایننس کوین', 'icon' => '🟡'],
        'crypto-solana'    => ['cat' => 'کریپتو', 'name' => 'سولانا', 'icon' => '🟣'],
        'crypto-ripple'    => ['cat' => 'کریپتو', 'name' => 'ریپل', 'icon' => '✖️'],
        'crypto-dogecoin'  => ['cat' => 'کریپتو', 'name' => 'دوج کوین', 'icon' => '🐕'],
        'crypto-tron'      => ['cat' => 'کریپتو', 'name' => 'ترون', 'icon' => '⚡️'],
        'crypto-cardano'   => ['cat' => 'کریپتو', 'name' => 'کاردانو', 'icon' => '🔵'],
        'crypto-litecoin'  => ['cat' => 'کریپتو', 'name' => 'لایت کوین', 'icon' => '🔘'],
        'crypto-bitcoin-cash' => ['cat' => 'کریپتو', 'name' => 'بیت‌کوین کش', 'icon' => '🔶'],
        'crypto-stellar'   => ['cat' => 'کریپتو', 'name' => 'استellar', 'icon' => '⭐️'],
        'crypto-polkadot'  => ['cat' => 'کریپتو', 'name' => 'پولکادات', 'icon' => '🔮'],
        'crypto-avalanche' => ['cat' => 'کریپتو', 'name' => 'آوالانچ', 'icon' => '🔺'],
        'crypto-dash'      => ['cat' => 'کریپتو', 'name' => 'دش', 'icon' => '🌀'],
        'crypto-shiba-inu' => ['cat' => 'کریپتو', 'name' => 'شیبا اینو', 'icon' => '🐾'],
        'crypto-toncoin'   => ['cat' => 'کریپتو', 'name' => 'تون‌کوین', 'icon' => '💎'],
        'crypto-chainlink' => ['cat' => 'کریپتو', 'name' => 'چینلینک', 'icon' => '🔗'],
        'crypto-polygon'    => ['cat' => 'کریپتو', 'name' => 'پالیگان', 'icon' => '🟥'],
        'crypto-uniswap'    => ['cat' => 'کریپتو', 'name' => 'یونی‌سوآپ', 'icon' => '🦄'],
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
