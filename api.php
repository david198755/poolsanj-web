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
        'price_aed'        => ['cat' => 'ارز', 'name' => 'درهم امارات', 'icon' => '🇦🇪'],
        'price_try'        => ['cat' => 'ارز', 'name' => 'لیر ترکیه', 'icon' => '🇹🇷'],
        'price_gbp'        => ['cat' => 'ارز', 'name' => 'پوند انگلیس', 'icon' => '🇬🇧'],
        // Gold
        'geram18'          => ['cat' => 'طلا', 'name' => 'طلای ۱۸ عیار', 'icon' => '🟡'],
        'mesghal'          => ['cat' => 'طلا', 'name' => 'مثقال طلا', 'icon' => '⚖️'],
        'ons'              => ['cat' => 'طلا', 'name' => 'اونس جهانی', 'icon' => '🌍'],
        'gold_futures'     => ['cat' => 'طلا', 'name' => 'طلای آبشده', 'icon' => '💧'],
        // Coin
        'retail_sekee'     => ['cat' => 'سکه', 'name' => 'سکه امامی', 'icon' => '🏛️'],
        'sekeb'            => ['cat' => 'سکه', 'name' => 'سکه بهار آزادی', 'icon' => '🌸'],
        'retail_nim'       => ['cat' => 'سکه', 'name' => 'نیم سکه', 'icon' => '½'],
        'retail_rob'       => ['cat' => 'سکه', 'name' => 'ربع سکه', 'icon' => '¼'],
        'retail_gerami'    => ['cat' => 'سکه', 'name' => 'گرمی', 'icon' => '🔹'],
        'coin_blubber'     => ['cat' => 'سکه', 'name' => 'حباب سکه', 'icon' => '🫧'],
        // Crypto
        'crypto-bitcoin'   => ['cat' => 'کریپتو', 'name' => 'بیتکوین', 'icon' => '🟠'],
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
        'crypto-polygon'    => ['cat' => 'کریپتو', 'name' => 'پالیگان', 'icon' => '🟥']],
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
    
    // Match tr with data-market-nameslug
    $pattern = '/data-market-nameslug="([^"]+)".*?<td[^>]*>(.*?)<\/td>.*?<td[^>]*>(.*?)<\/td>/s';
    
    if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $slug = $m[1];
            $price = strip_tags($m[2]);
            $change = strip_tags($m[3]);
            
            // Determine direction from class
            $dir = '';
            if (preg_match('/tg-1/i', $m[0])) $dir = 'up';
            elseif (preg_match('/tg-0/i', $m[0])) $dir = 'down';
            
            if (!isset($prices[$slug])) {
                $prices[$slug] = [
                    'p' => $price,
                    'c' => $change,
                    'dir' => $dir
                ];
            }
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
