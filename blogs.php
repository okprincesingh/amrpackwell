<?php
require_once __DIR__ . '/php-admin/config/db.php';

// Fetch all published blogs, newest first
$stmt = db()->query("SELECT id, title, slug, category, featured_image, excerpt, posted_by, published_at
                      FROM blogs WHERE status = 'published' ORDER BY published_at DESC, id DESC");
$posts = $stmt->fetchAll();

function normalize_category_slug($category) {
    $slug = strtolower(trim((string) ($category ?? '')));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-') ?: 'general';
}

// Build the list of categories actually in use, for the filter buttons
$categories = [];
foreach ($posts as $p) {
    $cat = trim((string) ($p['category'] ?? ''));
    if ($cat === '') {
        continue;
    }

    $slug = normalize_category_slug($cat);
    if (!isset($categories[$slug])) {
        $categories[$slug] = $cat;
    }
}

function bfe($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
function blog_image_url($path) {
    if (!$path) return 'assets/images/blog-image/default-blog.png';
    return UPLOAD_URL . '/' . $path;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <base href="<?= SITE_URL ?>/" />
    <link rel="icon" type="image/png" href="assets/images/logo/logo_140881.png" />
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/output.css">
    <link rel="stylesheet" href="assets/css/styless.css">
    <link rel="canonical" href="https://www.amrpackwell.com/blogs" />
    <title>Packaging Blogs | AMR Packwell Noida</title>
    <meta name="description" content="Stay updated with AMR Packwell's packaging blogs. Learn about corrugated boxes, wooden pallets, paper edge protectors & more. Expert insights from Greater Noida since 2017." />
    <meta name="keywords" content="Plywood Pallet Near Me, Plywood Pallet Manufacturer, Plywood Packaging Box, Plywood Box Manufacturer, Paper Angle Board, Paper Angle Board Manufacturer, Corrugated Box Manufacturer, Corrugated Box Near Me, Corrugated Box in Noida, Wooden Pallet Manufacturer" />

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <style>
        #navbar-header {
            position: relative;
            z-index: 9999;
            width: 100%;
        }

        .is-sticky-active {
            position: fixed !important;
            top: 0;
            left: 0;
            width: 100%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from { transform: translateY(-100%); }
            to { transform: translateY(0); }
        }

        body { padding-top: 0; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        montserrat: ['Montserrat', 'sans-serif'],
                        poppins: ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        orange: { DEFAULT: '#F15A24', light: '#FF7A45', dark: '#c94412' },
                        navy: { DEFAULT: '#1A2B4A', dark: '#0f1c30', light: '#243660' },
                    },
                    animation: {
                        'grid-float': 'gridFloat 20s linear infinite',
                        'tilt-subtle': 'tiltSubtle 5s ease-in-out infinite',
                    },
                    keyframes: {
                        gridFloat: {
                            '0%': { backgroundPosition: '0 0' },
                            '100%': { backgroundPosition: '0 100%' },
                        },
                        tiltSubtle: {
                            '0%, 100%': { transform: 'rotateX(0deg) rotateY(0deg)' },
                            '50%': { transform: 'rotateX(2deg) rotateY(2deg)' },
                        }
                    }
                }
            }
        };
    </script>
    <style>
        .reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s ease-out;
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        .tilt-card {
            transition: transform 0.3s ease-out, box-shadow 0.3s ease-out;
            transform-style: preserve-3d;
        }

        .tilt-card:hover {
            transform: translateY(-10px) rotateX(4deg) rotateY(2deg);
            box-shadow: 0 35px 60px -15px rgba(26, 43, 74, 0.2);
        }

        .grid-pattern {
            background-image: linear-gradient(rgba(241, 90, 36, 0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(241, 90, 36, 0.05) 1px, transparent 1px);
            background-size: 30px 30px;
        }

        .cat-btn.active {
            background-color: #F15A24;
            color: white;
            box-shadow: 0 10px 20px -5px rgba(241, 90, 36, 0.4);
        }

        .blog-card-meta {
            min-height: 20px;
        }

        .blog-card-meta span {
            line-height: 1.4;
        }

        .blog-card-meta span + span::before {
            content: "";
            display: inline-block;
            width: 4px;
            height: 4px;
            margin-right: 10px;
            border-radius: 999px;
            background: #d1d5db;
            vertical-align: middle;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>

<body>
    <div id="navbar-header"></div>

    <script>
        fetch('header.html')
            .then(response => response.text())
            .then(data => {
                const navbarContainer = document.getElementById('navbar-header');
                navbarContainer.innerHTML = data;

                window.addEventListener('scroll', function () {
                    const headerTag = navbarContainer.querySelector('.navbar-header');
                    const topbar = navbarContainer.querySelector('.amr-topbar');

                    if (window.scrollY > 50) {
                        headerTag.classList.add('is-sticky-active');
                    } else {
                        headerTag.classList.remove('is-sticky-active');
                    }
                });
            })
            .catch(error => console.error('Error loading navbar:', error));
    </script>

    <div class="bg-gray-50 font-poppins text-navy overflow-x-hidden">
        <header class="relative bg-navy-dark pt-32 pb-40 overflow-hidden">
            <div class="absolute inset-0 z-0">
                <img class="h-full w-full object-cover opacity-30"
                    src="https://www.shutterstock.com/image-photo/banner-blogger-woman-hands-typing-600nw-2137810931.jpg"
                    alt="Background">
                <div class="absolute inset-0 bg-gradient-to-t from-navy-dark via-navy-dark/50 to-transparent"></div>
            </div>

            <div class="absolute inset-0 grid-pattern animate-grid-float opacity-20 z-0"></div>

            <div class="max-w-7xl mx-auto px-6 relative z-10 text-center">
                <h1 class="font-montserrat text-5xl md:text-7xl font-black text-white leading-tight mb-6 tracking-tight">
                    Packwell <span class="bg-orange text-navy-dark px-4 py-1 rounded-lg inline-block transform -rotate-1">Insights</span>
                </h1>
                <p class="max-w-2xl mx-auto text-gray-300 text-lg md:text-xl font-light leading-relaxed">
                    Your structural guide to the future of <span class="text-white font-medium">industrial packaging</span> and <span class="text-white font-medium">smart logistics</span>.
                </p>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-6 py-20 -mt-20 relative z-20">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                <div class="lg:col-span-8">
                    <div class="flex flex-wrap gap-3 mb-10">
                        <button class="cat-btn active px-5 py-2.5 rounded-xl text-sm font-bold bg-white border border-gray-200 text-navy/70" onclick="filter('all', this)">All</button>
                        <?php foreach ($categories as $slugCat => $label): ?>
                            <button class="cat-btn px-5 py-2.5 rounded-xl text-sm font-bold bg-white border border-gray-200 text-navy/70" onclick="filter('<?= bfe($slugCat) ?>', this)"><?= bfe($label) ?></button>
                        <?php endforeach; ?>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8" id="blog-grid">
                        <?php if (empty($posts)): ?>
                            <p class="text-gray-500 col-span-2">No blog posts published yet. Check back soon!</p>
                        <?php endif; ?>

                        <?php foreach ($posts as $post): ?>
                            <?php $postSlug = normalize_category_slug($post['category']); ?>
                            <article class="tilt-card reveal bg-white p-5 rounded-3xl shadow-sm border border-gray-100"
                                data-category="<?= bfe($postSlug) ?>">
                                <div class="relative h-60 rounded-2xl overflow-hidden mb-6">
                                    <img src="<?= bfe(blog_image_url($post['featured_image'])) ?>" class="w-full h-full object-cover" alt="<?= bfe($post['title']) ?>">
                                </div>
                                <div class="blog-card-meta flex flex-wrap items-center gap-3 mb-4">
                                    <span class="text-xs font-bold text-orange uppercase tracking-widest"><?= bfe($post['category']) ?></span>
                                    <time class="text-xs text-gray-400" datetime="<?= bfe($post['published_at']) ?>">
                                        <?= bfe(date('M j, Y', strtotime($post['published_at']))) ?>
                                    </time>
                                    <span class="text-xs text-gray-400">Posted By: <?= bfe($post['posted_by']) ?></span>
                                </div>
                                <h2 class="font-montserrat text-xl font-extrabold mb-4 text-navy"><?= bfe($post['title']) ?></h2>
                                <p class="text-gray-500 text-sm leading-relaxed mb-6 line-clamp-2"><?= bfe($post['excerpt']) ?></p>
                                <a href="blog/<?= bfe($post['slug']) ?>" class="text-sm font-bold text-navy group flex items-center gap-2">
                                    Read Article <span class="text-orange transition-transform group-hover:translate-x-1">→</span>
                                </a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>

                <aside class="lg:col-span-4 space-y-10">
                    <div class="reveal bg-white p-8 rounded-3xl shadow-lg border-t-4 border-orange">
                        <h3 class="font-montserrat font-extrabold text-navy mb-6">Expertise, Weekly.</h3>
                        <p class="text-sm text-gray-500 mb-6">Packaging costs change rapidly. Our specialists analyze the market to give you actionable insights.</p>
                        <input type="email" placeholder="Your work email" class="w-full px-5 py-4 rounded-xl bg-gray-50 border border-gray-100 mb-4 outline-none focus:border-orange/50 transition-all">
                        <button class="w-full py-4 bg-navy text-white rounded-xl font-bold hover:bg-orange transition-all duration-300">Analyze My Costs →</button>
                    </div>

                    <div class="reveal bg-white p-8 rounded-3xl shadow-lg">
                        <h4 class="font-montserrat font-extrabold text-navy/70 text-xs uppercase tracking-widest mb-6">Recent Posts</h4>
                        <div class="space-y-4">
                            <?php foreach (array_slice($posts, 0, 4) as $recent): ?>
                                <a href="blog/<?= bfe($recent['slug']) ?>" class="flex items-center gap-3 group">
                                    <img src="<?= bfe(blog_image_url($recent['featured_image'])) ?>" class="w-14 h-14 rounded-lg object-cover flex-shrink-0" alt="<?= bfe($recent['title']) ?>">
                                    <span class="text-sm font-semibold text-navy group-hover:text-orange leading-snug"><?= bfe($recent['title']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="reveal bg-white p-8 rounded-3xl shadow-lg">
                        <h4 class="font-montserrat font-extrabold text-navy/70 text-xs uppercase tracking-widest mb-6">Popular Tags</h4>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach (array_slice(array_values($categories), 0, 5) as $tag): ?>
                                <span class="px-4 py-2 bg-gray-100 rounded-xl text-xs font-bold text-navy/70">#<?= bfe($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </aside>
            </div>
        </main>

        <script>
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) entry.target.classList.add('active');
                });
            }, { threshold: 0.1 });

            document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

            function filter(cat, button) {
                document.querySelectorAll('.cat-btn').forEach(btn => btn.classList.remove('active'));
                if (button) button.classList.add('active');

                document.querySelectorAll('.tilt-card').forEach(item => {
                    const shouldShow = cat === 'all' || item.dataset.category === cat;
                    item.style.display = shouldShow ? 'block' : 'none';
                    item.classList.remove('active');
                    if (shouldShow) {
                        setTimeout(() => item.classList.add('active'), 40);
                    }
                });
            }
        </script>
    </div>

    <div id="footer-bottom"></div>
    <script>
        fetch('footer.php')
            .then(response => response.text())
            .then(data => {
                document.getElementById('footer-bottom').innerHTML = data;
            })
            .catch(error => console.error('Error loading footer:', error));
    </script>
    <script src="assets/js/header.js"></script>
    <script src="assets/js/footer.js"></script>
    <script src="assets/js/formsubmit.js"></script>
</body>
</html>