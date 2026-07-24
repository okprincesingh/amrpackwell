<?php
require_once __DIR__ . '/php-admin/config/db.php';
require_once __DIR__ . '/php-admin/includes/auth.php';


// Active categories, with a live count of active products in each
$catStmt = db()->query("SELECT c.id, c.name, c.slug, c.canonical, c.image,
                                (SELECT COUNT(*) FROM products p WHERE p.category = c.name AND p.status = 'active') AS product_count
                         FROM categories c
                         WHERE c.status = 'active'
                         ORDER BY c.sort_order ASC, c.name ASC");
$categories = $catStmt->fetchAll();

// Which category is selected via ?category=slug (if any)
// Which category is selected via ?category=slug (if any)
$selectedSlug = trim($_GET['category'] ?? '');
$selectedName = null;
$selectedCanonical = null;
if ($selectedSlug !== '') {
    foreach ($categories as $c) {
        if ($c['slug'] === $selectedSlug) {
            $selectedName      = $c['name'];
            $selectedCanonical = $c['canonical'];
            break;
        }
    }
}

// Products — filtered server-side so the page works with or without JS
if ($selectedName !== null) {
    $pStmt = db()->prepare("SELECT id, name, slug, category, short_description, featured_image, price, tags, specifications, description
                             FROM products WHERE status = 'active' AND category = ?
                             ORDER BY sort_order ASC, name ASC");
    $pStmt->execute([$selectedName]);
} else {
    $pStmt = db()->query("SELECT id, name, slug, category, short_description, featured_image, price, tags, specifications, description
                           FROM products WHERE status = 'active'
                           ORDER BY sort_order ASC, name ASC");
}
$products = $pStmt->fetchAll();

function pfe($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
function product_image_url($path) {
    if (!$path) return 'assets/images/product-img/default-product.png';
    return UPLOAD_URL . '/' . $path;
}
function category_image_url($path) {
    if (!$path) return 'assets/images/category-img/default-category.png';
    return UPLOAD_URL . '/' . $path;
}
function parse_product_specs($specs) {
    $result = [];
    foreach (preg_split('/\r?\n/', trim((string) $specs)) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (strpos($line, ':') !== false) {
            [$label, $value] = explode(':', $line, 2);
            $label = trim($label);
            $value = trim($value);
            if ($label !== '' && $value !== '') {
                $result[$label] = $value;
                continue;
            }
        }
        $result[] = $line;
    }
    return $result;
}
// Canonical URL: use the admin-provided value if set, otherwise auto-generate from the slug
if (!empty($selectedCanonical)) {
    $canonicalUrl = $selectedCanonical;
} elseif ($selectedName !== null) {
    $canonicalUrl = 'https://www.amrpackwell.com/product?category=' . rawurlencode($selectedSlug);
} else {
    $canonicalUrl = 'https://www.amrpackwell.com/product';
}

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
    <link rel="canonical" href="<?= pfe($canonicalUrl) ?>"/>
    <title><?= $selectedName ? pfe($selectedName) . ' Products | AMR Packwell Noida' : 'Packaging Products | AMR Packwell Noida' ?></title>
    <meta name="description" content="Shop AMR Packwell's complete range of corrugated boxes, wooden pallets, paper edge protectors, angle boards & strapping machines."/>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800;900&family=Poppins:wght@300;400;500;600&family=Bebas+Neue&display=swap" rel="stylesheet" />

    <script>
        tailwind.config = {
            theme: { extend: {
                fontFamily: { montserrat: ['Montserrat', 'sans-serif'], poppins: ['Poppins', 'sans-serif'], bebas: ['Bebas Neue', 'cursive'] },
                colors: {
                    orange: { DEFAULT: '#F15A24', light: '#FF7A45', pale: '#FFF3EE', dark: '#c94412' },
                    navy: { DEFAULT: '#1A2B4A', dark: '#0f1c30', light: '#243660' },
                },
            } }
        };
    </script>
    <style>
        .reveal { opacity: 0; transform: translateY(20px); transition: all 0.6s ease-out; }
        .reveal.active { opacity: 1; transform: translateY(0); }
        .card-bar::after { content:''; position:absolute; bottom:0; left:0; right:0; height:3px; background:linear-gradient(90deg,#F15A24,#FF7A45); transform:scaleX(0); transform-origin:left; transition:transform .3s ease; }
        .prod-card:hover .card-bar::after { transform: scaleX(1); }
        .cat-btn.active { background-color: #F15A24; color: white; border-color: #F15A24 !important; }
        .cat-pill.active { box-shadow: 0 0 0 3px #F15A24; }
        .line-clamp-2, .line-clamp-3 { display:-webkit-box; -webkit-box-orient:vertical; overflow:hidden; }
        .line-clamp-2 { -webkit-line-clamp:2; line-clamp:2; }
        .line-clamp-3 { -webkit-line-clamp:3; line-clamp:3; }
        .accent-line::after { content: ''; display: block; width: 56px; height: 4px; background: linear-gradient(90deg, #F15A24, #FF7A45); border-radius: 3px; margin-top: 10px; }
        .accent-line-center::after { content: ''; display: block; width: 56px; height: 4px; background: linear-gradient(90deg, #F15A24, #FF7A45); border-radius: 3px; margin: 10px auto 0; }
        .shine-btn { position: relative; overflow: hidden; }
        .shine-btn::after { content: ''; position: absolute; inset: 0; background: linear-gradient(105deg, transparent 30%, rgba(255,255,255,0.35) 50%, transparent 70%); background-size: 200% 100%; animation: shimmer 2.5s linear infinite; }
        @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
        .card-pill {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 10;
            padding: 0.6rem 0.9rem;
            border-radius: 999px;
            background: rgba(15, 28, 48, 0.95);
            color: white;
            font-size: 0.65rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 700;
        }
        .card-dot {
            position: absolute;
            top: 1rem;
            left: 1rem;
            width: 1.1rem;
            height: 1.1rem;
            border-radius: 999px;
            border: 2px solid rgba(241,90,36,0.45);
            background: rgba(255,255,255,0.95);
        }
        .spec-row {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            font-size: 0.75rem;
            color: #6b7280;
            line-height: 1.6;
        }
        .spec-row span:first-child {
            color: #9ca3af;
            min-width: 30%;
        }
        .card-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: 1.5rem;
        }
        .card-actions .tag-pill {
            background: #fdf3e8;
            color: #f15a24;
            border-radius: 999px;
            padding: 0.55rem 1rem;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            border: 1px solid rgba(241,90,36,0.12);
        }
        .btn-card {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-width: 110px;
            padding: 0.85rem 1.25rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            transition: all 0.2s ease;
            text-align: center;
        }
        .btn-detail {
            background: #1A2B4A;
            color: white;
            border: 1px solid transparent;
        }
        .btn-detail:hover {
            background: #F15A24;
        }
        .btn-inquire {
            background: white;
            color: #1A2B4A;
            border: 1px solid #d1d5db;
        }
        .btn-inquire:hover {
            border-color: #F15A24;
            color: #F15A24;
        }
        .prod-card .card-subtitle {
            margin-top: 0.75rem;
            margin-bottom: 1.25rem;
            font-size: 0.78rem;
            color: #9ca3af;
            line-height: 1.85;
        }
        .prod-card {
            min-height: 100%;
            display: flex;
            flex-direction: column;
        }
        .prod-card img {
            min-height: 20rem;
            max-height: 20rem;
            width: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div id="navbar-header"></div>
    <script>
        fetch('header.html').then(r => r.text()).then(d => { document.getElementById('navbar-header').innerHTML = d; }).catch(() => {});
    </script>

    <div class="bg-[#F5F4F0] text-gray-900 font-poppins scroll-smooth">
        <section class="relative h-[70vh] md:h-[380px] w-full overflow-hidden flex items-center justify-start font-sans">
            <style>
                @keyframes kenBurns { 0% { transform: scale(1); } 100% { transform: scale(1.15); } }
                @keyframes slideFromTop { 0% { opacity: 0; transform: translateY(-40px); } 100% { opacity: 1; transform: translateY(0); } }
                .kb-zoom { animation: kenBurns 15s infinite alternate ease-in-out; }
                .text-slide-in { opacity: 0; animation: slideFromTop 0.8s ease-out forwards; }
                .delay-1 { animation-delay: 0.3s; }
                .delay-2 { animation-delay: 0.6s; }
            </style>

            <div class="absolute inset-0 z-0">
                <img src="assets/images/product-banners/ourproduct-mobile.jpeg" alt="AMR Packaging Mobile Banner" class="block md:hidden w-full h-full object-cover kb-zoom" />
                <img src="assets/images/product-banners/ourproduct.jpeg" alt="AMR Packaging Industrial Solutions" class="hidden md:block w-full h-full object-cover kb-zoom" />
                <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-black/20 to-transparent"></div>
            </div>

            <div class="relative z-10 max-w-7xl mx-auto px-6 md:px-20 flex flex-col items-start text-left">
                <h1 class="text-slide-in text-3xl md:text-5xl font-black tracking-tight mb-4 leading-tight text-white uppercase">
                    <?php if ($selectedName): ?>
                        Premium <span class="text-[#f47933]"><?= pfe($selectedName) ?></span><br>
                        <span class="text-[#f47933]">Packaging</span> Solutions
                    <?php else: ?>
                        Premium <span class="text-[#f47933]">Industrial</span><br>
                        <span class="text-[#f47933]">Packaging</span> Solutions
                    <?php endif; ?>
                </h1>
                <p class="text-slide-in delay-1 text-base md:text-lg text-gray-200 max-w-xl mb-8 leading-relaxed border-l-4 border-[#f47933] pl-4">
                    <?php if ($selectedName): ?>
                        Explore our premium <strong><?= pfe($selectedName) ?></strong> portfolio. Custom-engineered solutions designed to secure your cargo and streamline your logistics.
                    <?php else: ?>
                        Manufacturer of high-quality <strong>Corrugated Boxes, Wooden Pallets, and Edge Protectors</strong>. Custom-engineered solutions designed to secure your cargo and streamline your logistics.
                    <?php endif; ?>
                </p>
            </div>
        </section>

        <main class="max-w-7xl mx-auto px-6 py-16">


            

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3   xl:grid-cols-3 gap-5" id="product-grid">
                <?php if (empty($products)): ?>
                    <p class="text-gray-500 col-span-3 text-center">
                        <?= $selectedName ? 'No products in this category yet.' : 'No products published yet. Check back soon!' ?>
                    </p>
                <?php endif; ?>

                <?php foreach ($products as $p): ?>
                    <?php $tags = array_filter(array_map('trim', explode(',', $p['tags'] ?? ''))); ?>
                    <?php $specs = parse_product_specs($p['specifications']); ?>
                    <div class="prod-card reveal from-bottom group bg-white rounded-2xl border border-gray-100 overflow-hidden relative card-bar shadow-card transition-all duration-[350ms] hover:-translate-y-2 hover:shadow-[0_24px_60px_rgba(26,43,74,0.16)]">
                        <div class="card-dot"></div>
                        <div class="card-pill"><?= pfe($p['category']) ?></div>
                        <img class="object-cover w-full h-50" src="<?= pfe(product_image_url($p['featured_image'])) ?>" alt="<?= pfe($p['name']) ?>" onerror="this.onerror=null;this.src='assets/images/product-img/default-product.png'">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-3">
                                <h3 class="font-montserrat font-extrabold text-lg uppercase text-navy tracking-tight leading-tight"><?= pfe($p['name']) ?></h3>
                                <svg class="w-5 h-5 text-orange mt-1 flex-shrink-0 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </div>
                                <?php if (!empty($p['short_description'])): ?>
                                <p class="card-subtitle"><?= pfe($p['short_description']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($specs)): ?>
                                <?php $displayedSpecs = 0; ?>
                                <?php foreach ($specs as $label => $value): ?>
                                    <?php if (is_string($label) && $displayedSpecs < 3): ?>
                                        <div class="spec-row">
                                            <span><?= pfe($label) ?></span>
                                            <span class="text-right font-semibold text-navy"><?= pfe($value) ?></span>
                                        </div>
                                        <?php $displayedSpecs++; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <div class="card-actions">
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach (array_slice($tags, 0, 2) as $tag): ?>
                                        <span class="tag-pill"><?= pfe($tag) ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="flex gap-2 flex-wrap">
                                    <?php if (!empty($p['description'])): ?>
                                        <a href="products/<?= pfe($p['slug']) ?>" class="btn-card btn-detail">Details</a>
                                    <?php endif; ?>
                                    <button type="button" onclick='openInquiry(<?= json_encode($p['name']) ?>)' class="btn-card btn-inquire">Inquire</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <div id="product-inquiry-modal" class="fixed inset-0 bg-navy-dark/80 z-[999] items-center justify-center p-4 backdrop-blur-sm" style="display:none;">
        <div class="bg-white rounded-3xl w-full max-w-md shadow-card-lg overflow-hidden">
            <div class="bg-navy px-6 py-5 flex items-center justify-between">
                <div>
                    <h3 id="product-modal-title" class="font-montserrat font-black text-lg uppercase text-white tracking-tight">INQUIRE</h3>
                    <p class="font-poppins text-[11px] text-white/40 mt-1">We respond within 24 hours</p>
                </div>
                <button type="button" onclick="closeInquiry()" class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white/70 hover:text-white transition-all duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <input type="hidden" id="fcsrf" value="<?= pfe(csrf_token()) ?>">
                <input type="hidden" id="f-inquiry-type" value="Discuss Requirement">
                <input type="hidden" id="f-product" value="">

                <div>
                    <label class="font-montserrat font-bold text-[10px] tracking-[2px] uppercase text-gray-400 block mb-1.5">Your Name *</label>
                    <input id="f-name" type="text" placeholder="e.g. Rajesh Kumar" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-poppins outline-none focus:border-orange transition-colors duration-200" />
                </div>
                <div>
                    <label class="font-montserrat font-bold text-[10px] tracking-[2px] uppercase text-gray-400 block mb-1.5">Mobile / Email *</label>
                    <input id="f-contact" type="text" placeholder="+91 98765 43210" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-poppins outline-none focus:border-orange transition-colors duration-200" />
                </div>
                <div>
                    <label class="font-montserrat font-bold text-[10px] tracking-[2px] uppercase text-gray-400 block mb-1.5">Product</label>
                    <input id="f-product-display" type="text" readonly class="w-full border border-gray-100 bg-gray-50 rounded-xl px-4 py-3 text-sm text-gray-500 font-poppins outline-none" />
                </div>
                <div>
                    <label class="font-montserrat font-bold text-[10px] tracking-[2px] uppercase text-gray-400 block mb-1.5">Requirements</label>
                    <textarea id="f-requirement" rows="3" placeholder="Quantity, dimensions, special requirements..." class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-poppins outline-none focus:border-orange transition-colors duration-200 resize-none"></textarea>
                </div>
                <div id="inquiry-error" class="hidden rounded-xl border border-red-100 bg-red-50 p-3 text-sm text-red-700"></div>
                <button type="button" id="submit-inquiry-btn" onclick="submitInquiry()" class="shine-btn relative overflow-hidden w-full font-montserrat font-black text-[12px] tracking-widest uppercase bg-orange text-white py-3.5 rounded-xl hover:bg-orange-light transition-all duration-200 shadow-orange">Submit Inquiry →</button>
            </div>
        </div>
    </div>

    <div id="footer-bottom"></div>
    <script>
        fetch('footer.php').then(r => r.text()).then(d => { document.getElementById('footer-bottom').innerHTML = d; }).catch(() => {});
    </script>
    <script src="assets/js/header.js"></script>
    <script src="assets/js/footer.js"></script>
    <script>
        function openInquiry(product) {
            const modal = document.getElementById('product-inquiry-modal');
            document.getElementById('product-modal-title').textContent = product ? `INQUIRE: ${product}` : 'INQUIRE';
            document.getElementById('f-product').value = product || '';
            document.getElementById('f-product-display').value = product || 'General Inquiry';
            document.getElementById('f-name').value = '';
            document.getElementById('f-contact').value = '';
            document.getElementById('f-requirement').value = '';
            document.getElementById('inquiry-error').classList.add('hidden');
            document.getElementById('inquiry-error').textContent = '';
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeInquiry() {
            const modal = document.getElementById('product-inquiry-modal');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        function submitInquiry() {
            const name = document.getElementById('f-name').value.trim();
            const contact = document.getElementById('f-contact').value.trim();
            const requirement = document.getElementById('f-requirement').value.trim();
            const product = document.getElementById('f-product').value.trim();
            const csrf = document.getElementById('fcsrf').value;
            const errorEl = document.getElementById('inquiry-error');
            const submitBtn = document.getElementById('submit-inquiry-btn');

            errorEl.classList.add('hidden');
            errorEl.textContent = '';

            if (!name || !contact) {
                errorEl.textContent = 'Please enter your name and contact details.';
                errorEl.classList.remove('hidden');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';

            const formData = new FormData();
            formData.append('csrf_token', csrf);
            formData.append('inquiry_type', 'Discuss Requirement');
            formData.append('requirement', requirement);
            formData.append('full_name', name);
            formData.append('mobile_number', contact);
            formData.append('email', '');
            formData.append('product_category', product);
            formData.append('website', '');

            fetch('contact-submit.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        closeInquiry();
                        const toast = document.createElement('div');
                        toast.className = 'fixed bottom-6 right-6 bg-navy text-white px-6 py-4 rounded-3xl shadow-card-lg z-[9999] font-montserrat font-bold text-sm tracking-wide border-l-4 border-orange';
                        toast.textContent = 'Inquiry sent. We will contact you within 24 hours.';
                        document.body.appendChild(toast);
                        setTimeout(() => { toast.remove(); }, 3800);
                    } else {
                        errorEl.textContent = data.message || 'Something went wrong. Please try again.';
                        errorEl.classList.remove('hidden');
                    }
                })
                .catch(() => {
                    errorEl.textContent = 'Network error. Please try again.';
                    errorEl.classList.remove('hidden');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Inquiry →';
                });
        }

        document.getElementById('product-inquiry-modal').addEventListener('click', function (e) {
            if (e.target === this) closeInquiry();
        });
    </script>
    <script>
        const revealEls = document.querySelectorAll('.reveal');
        if (revealEls.length) {
            if ('IntersectionObserver' in window) {
                const revealObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('active');
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.12 });
                revealEls.forEach(el => revealObserver.observe(el));
            } else {
                revealEls.forEach(el => el.classList.add('active'));
            }
        }
    </script>
</body>
</html>