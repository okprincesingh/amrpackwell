<?php
require_once __DIR__ . '/php-admin/config/db.php';

$slug = $_GET['slug'] ?? '';

$stmt = db()->prepare("SELECT * FROM products WHERE slug = ? AND status = 'active' LIMIT 1");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    echo '<h1 style="font-family:sans-serif;text-align:center;margin-top:100px;">Product not found</h1>';
    echo '<p style="text-align:center;"><a href="/product">← Back to Products</a></p>';
    exit;
}

// Look up the real category record (for its slug + breadcrumb link)
$catStmt = db()->prepare("SELECT slug FROM categories WHERE name = ? AND status = 'active' LIMIT 1");
$catStmt->execute([$product['category']]);
$categoryRow = $catStmt->fetch();
$categoryUrl = $categoryRow ? 'product.php?category=' . urlencode($categoryRow['slug']) : 'product.php';

$galleryStmt = db()->prepare('SELECT image_path FROM product_images WHERE product_id = ? ORDER BY sort_order ASC');
$galleryStmt->execute([$product['id']]);
$gallery = $galleryStmt->fetchAll();

// Other active products in the same category (excluding this one)
$relatedStmt = db()->prepare("SELECT name, slug, featured_image FROM products
                               WHERE status = 'active' AND category = ? AND id != ?
                               ORDER BY sort_order ASC LIMIT 4");
$relatedStmt->execute([$product['category'], $product['id']]);
$related = $relatedStmt->fetchAll();

// Parse "Label: Value" specification lines
$specs = [];
if (!empty($product['specifications'])) {
    foreach (explode("\n", $product['specifications']) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $parts = explode(':', $line, 2);
        $specs[] = ['label' => trim($parts[0]), 'value' => trim($parts[1] ?? '')];
    }
}

function pfe($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
function product_image_url($path) {
    if (!$path) return 'assets/images/product-img/default-product.png';
    return UPLOAD_URL . '/' . $path;
}

$metaTitle       = $product['meta_title'] ?: ($product['name'] . ' – AMR Packwell');
$metaDescription = $product['meta_description'] ?: $product['short_description'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/png" href="assets/images/logo/logo_140881.png" />
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/output.css">
    <link rel="stylesheet" href="assets/css/styless.css">
    <title><?= pfe($metaTitle) ?></title>
    <meta name="description" content="<?= pfe($metaDescription) ?>" />
    <?php if (!empty($product['meta_keywords'])): ?>
    <meta name="keywords" content="<?= pfe($product['meta_keywords']) ?>" />
    <?php endif; ?>
    <link rel="canonical" href="<?= pfe(!empty($product['canonical']) ? $product['canonical'] : 'https://www.amrpackwell.com/products/' . $product['slug']) ?>" />
    <base href="<?= SITE_URL ?>/" />

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Poppins:wght@300;400;500;600&family=Bebas+Neue&display=swap" rel="stylesheet" />

    <script>
        tailwind.config = {
            theme: { extend: {
                fontFamily: { montserrat: ['Montserrat', 'sans-serif'], poppins: ['Poppins', 'sans-serif'], bebas: ['Bebas Neue', 'cursive'] },
                colors: { orange: { DEFAULT: '#F15A24', light: '#FF7A45' }, navy: { DEFAULT: '#1A2B4A', dark: '#0f1c30' } },
            } }
        };
    </script>
    <style>
        .product-article-body h2 { font-family:'Montserrat',sans-serif; font-weight:800; font-size:1.6rem; color:#1A2B4A; margin:2rem 0 1rem; }
        .product-article-body h3 { font-family:'Montserrat',sans-serif; font-weight:700; font-size:1.25rem; color:#1A2B4A; margin:1.5rem 0 .75rem; }
        .product-article-body p { color:#4b5563; line-height:1.8; margin-bottom:1rem; font-size:15px; }
        .product-article-body img { border-radius:1rem; margin:1.5rem 0; max-width:100%; }
        .product-article-body ul, .product-article-body ol { margin:1rem 0 1rem 1.5rem; color:#4b5563; }
        .product-article-body ul { list-style:disc; }
        .product-article-body ol { list-style:decimal; }
        .product-article-body li { margin-bottom:.5rem; }
        .font-bebas { font-family: 'Bebas Neue', cursive; }
        .font-widest { letter-spacing: 0.2em; }
        .shadow-card { box-shadow: 0 20px 60px rgba(15, 23, 42, 0.08); }
        .accent-line { position: relative; display: inline-block; }
        .accent-line::after { content: ''; position: absolute; left: 0; bottom: -0.75rem; width: 3.5rem; height: 0.4rem; background: #F15A24; border-radius: 999px; opacity: 0.25; }
        .accent-line-center::after { left: 50%; transform: translateX(-50%); }
        .pulse-dot { animation: pulse 1.8s infinite ease-in-out; }
        @keyframes pulse { 0%, 100% { opacity: .25; transform: scale(1); } 50% { opacity: 1; transform: scale(1.2); } }
        .float-badge { background: rgba(255,255,255,0.18); backdrop-filter: blur(8px); }
        .float-badge-delay { background: rgba(255,255,255,0.1); }
        .reveal { opacity: 0; transform: translateY(25px); transition: opacity .8s ease, transform .8s ease; }
        .reveal.revealed { opacity: 1; transform: translateY(0); }
        .prod-card:hover { transform: translateY(-4px); }
        .prod-card { transition: transform .25s ease, box-shadow .25s ease; }
        .card-bar-top { border-top: 3px solid #F15A24; }
        .tab-btn.active { color: #173D74; }
        .thumb { cursor: pointer; border: 2px solid transparent; transition: border-color .2s ease, transform .2s ease; }
        .thumb:hover, .thumb.active { border-color: #F15A24; transform: translateY(-2px); }
        .thumb img { width: 100%; height: 100%; object-fit: cover; }
        .grid-overlay { background-image: radial-gradient(circle, rgba(255,255,255,0.08) 1px, transparent 1px); background-size: 3rem 3rem; }
    </style>
</head>
<body class="bg-[#F5F4F0] text-gray-900 font-poppins scroll-smooth">
    <div id="navbar-header"></div>
    <script>
        fetch('header.html').then(r => r.text()).then(d => { document.getElementById('navbar-header').innerHTML = d; }).catch(() => {});
    </script>

    <div class="bg-white border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-6 py-3">
            <p class="font-poppins text-[11px] tracking-[2px] uppercase text-gray-400">
                <a href="index.html" class="hover:text-orange transition-colors">Home</a>
                &nbsp;/&nbsp;
                <a href="product.php" class="hover:text-orange transition-colors">Products</a>
                &nbsp;/&nbsp;
                <a href="<?= pfe($categoryUrl) ?>" class="hover:text-orange transition-colors"><?= pfe($product['category']) ?></a>
                &nbsp;/&nbsp;
                <span class="text-orange font-semibold"><?= pfe($product['name']) ?></span>
            </p>
        </div>
    </div>

    <section class="max-w-7xl mx-auto px-6 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">

            <div class="reveal from-left lg:sticky lg:top-24 lg:self-start">
                <div class="img-zoom rounded-2xl overflow-hidden bg-gradient-to-br from-amber-50 via-orange-50 to-amber-100 relative shadow-card mb-4" style="height:420px;">
                    <img id="main-img" src="<?= pfe(product_image_url($product['featured_image'])) ?>" alt="<?= pfe($product['name']) ?>" class="w-full h-full object-cover" onerror="this.style.display='none'; document.getElementById('img-placeholder').style.display='flex';" />
                    <div id="img-placeholder" class="absolute inset-0 items-center justify-center" style="display:none;">
                        <svg class="w-48 h-48 opacity-20 text-amber-700" viewBox="0 0 80 80" fill="currentColor">
                            <rect x="10" y="10" width="14" height="60" rx="2" />
                            <rect x="10" y="10" width="60" height="14" rx="2" />
                        </svg>
                    </div>
                    <div class="absolute top-4 left-4 w-10 h-10 border-2 border-orange/20 rounded-full float-badge pointer-events-none"></div>
                    <div class="absolute bottom-6 right-8 w-6 h-6 border-2 border-orange/15 rounded float-badge-delay pointer-events-none"></div>
                    <div class="absolute top-4 right-4">
                        <span class="font-montserrat font-extrabold text-[9px] tracking-[2px] uppercase bg-navy text-white px-3 py-1.5 rounded-full"><?= pfe($product['category']) ?></span>
                    </div>
                </div>

                <div class="flex gap-3 mb-5">
                    <?php
                    $thumbs = [];
                    $thumbs[] = product_image_url($product['featured_image']);
                    foreach ($gallery as $img) {
                        $thumbs[] = UPLOAD_URL . '/' . $img['image_path'];
                    }
                    ?>
                    <?php foreach (array_unique($thumbs) as $index => $src): ?>
                        <div class="thumb rounded-xl overflow-hidden bg-gradient-to-br from-amber-50 to-yellow-100 w-20 h-20 flex items-center justify-center" data-src="<?= pfe($src) ?>" onclick="switchThumb(this)">
                            <img src="<?= pfe($src) ?>" alt="Thumbnail">
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    <div class="flex items-center gap-2 bg-white border border-gray-100 px-3 py-2 rounded-xl shadow-card">
                        <span class="text-green-500 text-base">✓</span>
                        <span class="font-montserrat font-bold text-[10px] tracking-widest uppercase text-gray-600">ISO Compatible</span>
                    </div>
                    <div class="flex items-center gap-2 bg-white border border-gray-100 px-3 py-2 rounded-xl shadow-card">
                        <span class="text-green-500 text-base">✓</span>
                        <span class="font-montserrat font-bold text-[10px] tracking-widest uppercase text-gray-600">GST Registered</span>
                    </div>
                    <div class="flex items-center gap-2 bg-white border border-gray-100 px-3 py-2 rounded-xl shadow-card">
                        <span class="text-green-500 text-base">✓</span>
                        <span class="font-montserrat font-bold text-[10px] tracking-widest uppercase text-gray-600">100% Recyclable</span>
                    </div>
                    <div class="flex items-center gap-2 bg-white border border-gray-100 px-3 py-2 rounded-xl shadow-card">
                        <span class="text-green-500 text-base">✓</span>
                        <span class="font-montserrat font-bold text-[10px] tracking-widest uppercase text-gray-600">PAN India Supply</span>
                    </div>
                </div>
            </div>

            <div class="reveal from-right">
                <div class="inline-flex items-center gap-2 bg-orange/10 border border-orange/20 px-4 py-1.5 rounded-full mb-4">
                    <span class="w-1.5 h-1.5 rounded-full bg-orange pulse-dot"></span>
                    <span class="font-montserrat font-bold text-[10px] tracking-[3px] uppercase text-orange"><?= pfe($product['category']) ?></span>
                </div>

                <h1 class="font-bebas text-5xl lg:text-6xl uppercase text-navy tracking-wide leading-tight mb-1"><?= pfe($product['name']) ?></h1>
                <?php if (!empty($product['short_description'])): ?>
                <p class="font-poppins text-sm text-gray-500 leading-relaxed mb-5"><?= pfe($product['short_description']) ?></p>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white rounded-[2rem] border border-gray-100 shadow-card p-6">
                        <p class="font-montserrat text-[10px] tracking-[3px] uppercase text-gray-400 mb-3">Starting Price</p>
                        <p class="font-bebas text-4xl text-orange tracking-tight mb-2"><?= pfe($product['price'] ?: 'Contact') ?></p>
                        <p class="font-poppins text-xs uppercase tracking-[2px] text-gray-500">INR / Piece</p>
                    </div>
                    <div class="bg-white rounded-[2rem] border border-gray-100 shadow-card p-6">
                        <p class="font-montserrat text-[10px] tracking-[3px] uppercase text-gray-400 mb-3">MOQ</p>
                        <p class="font-bebas text-4xl text-navy tracking-tight mb-2">1,000</p>
                        <p class="font-poppins text-xs uppercase tracking-[2px] text-gray-500">Pieces</p>
                    </div>
                    <div class="bg-white rounded-[2rem] border border-gray-100 shadow-card p-6">
                        <p class="font-montserrat text-[10px] tracking-[3px] uppercase text-gray-400 mb-3">Delivery</p>
                        <p class="font-bebas text-4xl text-navy tracking-tight mb-2">1–7</p>
                        <p class="font-poppins text-xs uppercase tracking-[2px] text-gray-500">Days</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden mb-5">
                    <div class="bg-navy px-5 py-3 flex items-center gap-2">
                        <span class="font-montserrat font-extrabold text-[10px] tracking-[3px] uppercase text-white">Specifications</span>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <?php foreach ($specs as $s): ?>
                        <div class="flex justify-between items-center px-5 py-3">
                            <span class="font-poppins text-sm text-gray-400"><?= pfe($s['label']) ?></span>
                            <span class="font-montserrat font-semibold text-sm text-navy"><?= pfe($s['value']) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($specs)): ?>
                        <div class="flex justify-between items-center px-5 py-3">
                            <span class="font-poppins text-sm text-gray-400">Specifications</span>
                            <span class="font-montserrat font-semibold text-sm text-navy">Available on request</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white rounded-[2rem] border border-gray-100 shadow-card overflow-hidden mb-5">
                    <div class="bg-[#10254d] px-6 py-5 rounded-t-[1.8rem]">
                        <p class="font-montserrat font-bold text-[11px] tracking-[3px] uppercase text-white">Tell us your requirement</p>
                    </div>
                    <div class="p-6 space-y-5">
                        <div class="space-y-3">
                            <p class="font-montserrat text-[10px] tracking-[3px] uppercase text-gray-400">Quantity (pieces)</p>
                            <div class="grid grid-cols-3 gap-3 sm:grid-cols-4">
                                <?php foreach ([1050,1150,1300,1500,1750,2050] as $qty): ?>
                                    <button type="button" data-qty="<?= $qty ?>" class="quote-qty-btn rounded-2xl border border-gray-200 bg-white py-3 text-[13px] font-semibold text-gray-700 hover:border-orange hover:text-navy transition"><?= number_format($qty) ?></button>
                                <?php endforeach; ?>
                            </div>
                            <input id="quoteQuantity" type="text" value="1000" class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-4 text-sm font-semibold text-gray-800 outline-none placeholder:text-gray-400 focus:border-orange focus:ring-orange/20 transition" />
                        </div>

                        <div class="space-y-3">
                            <p class="font-montserrat text-[10px] tracking-[3px] uppercase text-gray-400">Mobile Number</p>
                            <div class="rounded-2xl border border-red-300 bg-white shadow-sm px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-600">+91</span>
                                    <input id="quotePhone" type="tel" placeholder="Enter mobile number" class="w-full border-none bg-transparent text-sm text-gray-700 outline-none placeholder:text-gray-400" />
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <p class="font-montserrat text-[10px] tracking-[3px] uppercase text-gray-400">Additional Details</p>
                            <textarea id="quoteDetails" rows="4" placeholder="Size, thickness, special requirements..." class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-4 text-sm text-gray-700 outline-none placeholder:text-gray-400 focus:border-orange focus:ring-orange/20 transition"></textarea>
                        </div>

                        <button onclick="openQuoteModal('<?= pfe($product['name']) ?>')" class="w-full rounded-[1.5rem] bg-gradient-to-r from-orange to-orange-500 py-5 text-sm font-bold uppercase tracking-[1px] text-white shadow-[0_20px_45px_rgba(241,90,36,0.28)] transition hover:opacity-95">Get a Price / Quote →</button>
                    </div>
                </div>

                <?php if (!empty($product['tags'])): ?>
                <div class="flex flex-wrap gap-2">
                    <?php foreach (array_filter(array_map('trim', explode(',', $product['tags']))) as $tag): ?>
                        <span class="font-montserrat font-bold text-[9px] tracking-[1px] uppercase bg-[#F8F0EB] text-orange px-3 py-2 rounded-full"><?= pfe($tag) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-6 pb-16">
        <div class="flex gap-0 border-b border-gray-200 mb-8 overflow-x-auto">
            <button class="tab-btn active font-montserrat font-bold text-[11px] tracking-widest uppercase px-6 py-4 text-gray-600 whitespace-nowrap" data-tab="about">About Product</button>
            <button class="tab-btn font-montserrat font-bold text-[11px] tracking-widest uppercase px-6 py-4 text-gray-500 whitespace-nowrap hover:text-orange transition-colors" data-tab="trade">Trade Information</button>
            <button class="tab-btn font-montserrat font-bold text-[11px] tracking-widest uppercase px-6 py-4 text-gray-500 whitespace-nowrap hover:text-orange transition-colors" data-tab="applications">Applications</button>
        </div>

        <div id="tab-about" class="tab-content reveal from-bottom">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2">
                    <h2 class="font-bebas text-4xl uppercase text-navy tracking-wide accent-line mb-6">About <?= pfe($product['name']) ?></h2>
                    <div class="space-y-4 font-poppins text-sm text-gray-600 leading-relaxed">
                        <?= $product['description'] /* trusted admin-authored HTML */ ?>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="bg-navy rounded-2xl p-6 relative overflow-hidden">
                        <div class="absolute -top-10 -right-10 w-40 h-40 rounded-full bg-orange opacity-10"></div>
                        <div class="relative z-10">
                            <p class="font-montserrat font-bold text-[10px] tracking-[3px] uppercase text-orange mb-3">Product Snapshot</p>
                            <p class="font-poppins text-sm text-white/70">High-quality industrial packaging product with custom quote and bulk order support. Great for logistics, export, and material protection.</p>
                        </div>
                    </div>
                    <div class="bg-orange/10 border border-orange/20 rounded-2xl p-5">
                        <p class="font-montserrat font-extrabold text-[10px] tracking-[3px] uppercase text-orange mb-3">Quick Contact</p>
                        <p class="font-poppins text-xs text-gray-600 mb-3 leading-relaxed">Need a custom quote or have questions? Our team responds within 24 hours.</p>
                        <button onclick="openQuoteModal('<?= pfe($product['name']) ?>')" class="w-full font-montserrat font-bold text-[10px] tracking-widest uppercase bg-navy text-white py-2.5 rounded-lg hover:bg-orange transition-all duration-200">Request Quote</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="tab-trade" class="tab-content hidden reveal from-bottom">
            <h2 class="font-bebas text-4xl uppercase text-navy tracking-wide accent-line mb-8">Trade Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-card">
                    <div class="w-12 h-12 rounded-xl bg-orange/10 flex items-center justify-center mb-4">
                        <span class="text-2xl">📦</span>
                    </div>
                    <p class="font-montserrat font-extrabold text-[10px] tracking-[2px] uppercase text-gray-400 mb-1">Minimum Order Quantity</p>
                    <p class="font-bebas text-4xl text-navy">1,000</p>
                    <p class="font-poppins text-sm text-gray-400">Pieces</p>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-card">
                    <div class="w-12 h-12 rounded-xl bg-sky-100 flex items-center justify-center mb-4">
                        <span class="text-2xl">⚡</span>
                    </div>
                    <p class="font-montserrat font-extrabold text-[10px] tracking-[2px] uppercase text-gray-400 mb-1">Supply Ability</p>
                    <p class="font-bebas text-4xl text-navy">5,000</p>
                    <p class="font-poppins text-sm text-gray-400">Pieces Per Day</p>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-card">
                    <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center mb-4">
                        <span class="text-2xl">🚚</span>
                    </div>
                    <p class="font-montserrat font-extrabold text-[10px] tracking-[2px] uppercase text-gray-400 mb-1">Delivery Time</p>
                    <p class="font-bebas text-4xl text-navy">1–7</p>
                    <p class="font-poppins text-sm text-gray-400">Business Days</p>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-card">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center mb-4">
                        <span class="text-2xl">💵</span>
                    </div>
                    <p class="font-montserrat font-extrabold text-[10px] tracking-[2px] uppercase text-gray-400 mb-1">Price Range</p>
                    <p class="font-bebas text-4xl grad-text">Contact</p>
                    <p class="font-poppins text-sm text-gray-400">Bulk pricing available</p>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-card">
                    <div class="w-12 h-12 rounded-xl bg-violet-100 flex items-center justify-center mb-4">
                        <span class="text-2xl">🌍</span>
                    </div>
                    <p class="font-montserrat font-extrabold text-[10px] tracking-[2px] uppercase text-gray-400 mb-1">Coverage</p>
                    <p class="font-bebas text-4xl text-navy">PAN</p>
                    <p class="font-poppins text-sm text-gray-400">India — All States</p>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-card">
                    <div class="w-12 h-12 rounded-xl bg-rose-100 flex items-center justify-center mb-4">
                        <span class="text-2xl">🏭</span>
                    </div>
                    <p class="font-montserrat font-extrabold text-[10px] tracking-[2px] uppercase text-gray-400 mb-1">Production</p>
                    <p class="font-bebas text-4xl text-navy">8+</p>
                    <p class="font-poppins text-sm text-gray-400">Years Manufacturing Experience</p>
                </div>
            </div>
        </div>

        <div id="tab-applications" class="tab-content hidden reveal from-bottom">
            <h2 class="font-bebas text-4xl uppercase text-navy tracking-wide accent-line mb-8">Applications & Use Cases</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-card text-center hover:border-orange transition-colors">
                    <div class="text-3xl mb-3">📦</div>
                    <p class="font-montserrat font-bold text-[11px] tracking-widest uppercase text-navy">Pallet Shipment</p>
                </div>
                <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-card text-center hover:border-orange transition-colors">
                    <div class="text-3xl mb-3">🪑</div>
                    <p class="font-montserrat font-bold text-[11px] tracking-widest uppercase text-navy">Furniture</p>
                </div>
                <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-card text-center hover:border-orange transition-colors">
                    <div class="text-3xl mb-3">🖼️</div>
                    <p class="font-montserrat font-bold text-[11px] tracking-widest uppercase text-navy">Glass & Ceramic</p>
                </div>
                <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-card text-center hover:border-orange transition-colors">
                    <div class="text-3xl mb-3">💻</div>
                    <p class="font-montserrat font-bold text-[11px] tracking-widest uppercase text-navy">Electronics</p>
                </div>
                <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-card text-center hover:border-orange transition-colors">
                    <div class="text-3xl mb-3">🏭</div>
                    <p class="font-montserrat font-bold text-[11px] tracking-widest uppercase text-navy">Export Packaging</p>
                </div>
                <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-card text-center hover:border-orange transition-colors">
                    <div class="text-3xl mb-3">🔧</div>
                    <p class="font-montserrat font-bold text-[11px] tracking-widest uppercase text-navy">Machinery</p>
                </div>
                <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-card text-center hover:border-orange transition-colors">
                    <div class="text-3xl mb-3">💊</div>
                    <p class="font-montserrat font-bold text-[11px] tracking-widest uppercase text-navy">Pharmaceuticals</p>
                </div>
                <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-card text-center hover:border-orange transition-colors">
                    <div class="text-3xl mb-3">🏗️</div>
                    <p class="font-montserrat font-bold text-[11px] tracking-widest uppercase text-navy">Construction</p>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-navy py-16 relative overflow-hidden">
        <div class="absolute inset-0 grid-overlay pointer-events-none opacity-60"></div>
        <div class="absolute top-0 left-1/4 w-96 h-96 rounded-full bg-orange opacity-[0.05] blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="text-center mb-10 reveal from-bottom">
                <h2 class="font-bebas text-4xl uppercase text-white tracking-wide accent-line-center">Why Choose AMR Packwell?</h2>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="why-card reveal from-bottom delay-1 text-center p-6 border border-white/[0.08] rounded-2xl cursor-default group">
                    <div class="w-12 h-12 rounded-xl bg-orange/10 flex items-center justify-center mx-auto mb-4 group-hover:bg-orange/20 transition-colors">
                        <span class="text-2xl">🛡️</span>
                    </div>
                    <div class="font-montserrat font-extrabold text-[11px] tracking-[2px] uppercase text-white mb-1">Superior Strength</div>
                    <div class="font-poppins text-[11px] text-white/35">Resists heavy loads</div>
                </div>
                <div class="why-card reveal from-bottom delay-2 text-center p-6 border border-white/[0.08] rounded-2xl cursor-default group">
                    <div class="w-12 h-12 rounded-xl bg-orange/10 flex items-center justify-center mx-auto mb-4 group-hover:bg-orange/20 transition-colors">
                        <span class="text-2xl">♻️</span>
                    </div>
                    <div class="font-montserrat font-extrabold text-[11px] tracking-[2px] uppercase text-white mb-1">Eco Friendly</div>
                    <div class="font-poppins text-[11px] text-white/35">100% recyclable</div>
                </div>
                <div class="why-card reveal from-bottom delay-3 text-center p-6 border border-white/[0.08] rounded-2xl cursor-default group">
                    <div class="w-12 h-12 rounded-xl bg-orange/10 flex items-center justify-center mx-auto mb-4 group-hover:bg-orange/20 transition-colors">
                        <span class="text-2xl">📐</span>
                    </div>
                    <div class="font-montserrat font-extrabold text-[11px] tracking-[2px] uppercase text-white mb-1">Custom Sizes</div>
                    <div class="font-poppins text-[11px] text-white/35">Made to measure</div>
                </div>
                <div class="why-card reveal from-bottom delay-4 text-center p-6 border border-white/[0.08] rounded-2xl cursor-default group">
                    <div class="w-12 h-12 rounded-xl bg-orange/10 flex items-center justify-center mx-auto mb-4 group-hover:bg-orange/20 transition-colors">
                        <span class="text-2xl">💰</span>
                    </div>
                    <div class="font-montserrat font-extrabold text-[11px] tracking-[2px] uppercase text-white mb-1">Cost Effective</div>
                    <div class="font-poppins text-[11px] text-white/35">Bulk discounts</div>
                </div>
                <div class="why-card reveal from-bottom delay-5 text-center p-6 border border-white/[0.08] rounded-2xl cursor-default group">
                    <div class="w-12 h-12 rounded-xl bg-orange/10 flex items-center justify-center mx-auto mb-4 group-hover:bg-orange/20 transition-colors">
                        <span class="text-2xl">🚚</span>
                    </div>
                    <div class="font-montserrat font-extrabold text-[11px] tracking-[2px] uppercase text-white mb-1">PAN India</div>
                    <div class="font-poppins text-[11px] text-white/35">Fast delivery</div>
                </div>
            </div>
        </div>
    </section>

    <?php if (!empty($related)): ?>
    <section class="max-w-7xl mx-auto px-6 py-16">
        <div class="reveal from-bottom mb-10">
            <p class="font-montserrat font-bold text-[10px] tracking-[3px] uppercase text-orange mb-1">Related Products</p>
            <h2 class="font-bebas text-5xl uppercase text-navy tracking-wide accent-line">More in <?= pfe($product['category']) ?></h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
            <?php foreach ($related as $r): ?>
            <a href="/products/<?= pfe($r['slug']) ?>" class="prod-card card-bar-top reveal from-bottom bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden relative">
                <div class="h-40 bg-gradient-to-br from-sky-50 to-blue-100 flex items-center justify-center relative overflow-hidden">
                    <img src="<?= pfe(product_image_url($r['featured_image'])) ?>" alt="<?= pfe($r['name']) ?>" class="w-full h-full object-cover">
                </div>
                <div class="p-4">
                    <h3 class="font-montserrat font-extrabold text-sm uppercase text-navy tracking-tight mb-2"><?= pfe($r['name']) ?></h3>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <div id="footer-bottom"></div>
    <script>
        fetch('footer.php').then(r => r.text()).then(d => { document.getElementById('footer-bottom').innerHTML = d; }).catch(() => {});
    </script>
    <script src="assets/js/header.js"></script>
    <script src="assets/js/footer.js"></script>
    <script>
        function switchThumb(el) {
            document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
            el.classList.add('active');
            const src = el.dataset.src;
            const mainImg = document.getElementById('main-img');
            const placeholder = document.getElementById('img-placeholder');

            if (src && src !== '') {
                mainImg.style.opacity = '0';
                mainImg.style.transition = 'opacity 0.3s ease';
                setTimeout(() => {
                    mainImg.src = src;
                    mainImg.style.display = 'block';
                    placeholder.style.display = 'none';
                    mainImg.onload = () => { mainImg.style.opacity = '1'; };
                    mainImg.onerror = () => {
                        mainImg.style.display = 'none';
                        placeholder.style.display = 'flex';
                    };
                }, 150);
            } else {
                mainImg.style.opacity = '0';
                setTimeout(() => {
                    mainImg.style.display = 'none';
                    placeholder.style.display = 'flex';
                }, 150);
            }
        }

        const observer = new IntersectionObserver(entries => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    e.target.classList.add('revealed');
                    observer.unobserve(e.target);
                }
            });
        }, { threshold: 0.1 });
        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
                btn.classList.add('active');
                const target = document.getElementById('tab-' + btn.dataset.tab);
                if (target) {
                    target.classList.remove('hidden');
                    setTimeout(() => target.classList.add('revealed'), 10);
                }
            });
        });

        document.querySelectorAll('.quote-qty-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.quote-qty-btn').forEach(b => b.classList.remove('border-orange', 'text-navy', 'bg-orange/10'));
                btn.classList.add('border-orange', 'text-navy', 'bg-orange/10');
                const qtyInput = document.getElementById('quoteQuantity');
                if (qtyInput) qtyInput.value = btn.dataset.qty;
            });
        });
    </script>
</body>
</html>