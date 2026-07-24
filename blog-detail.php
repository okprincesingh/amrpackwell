<?php
require_once __DIR__ . '/php-admin/config/db.php';

$slug = $_GET['slug'] ?? '';

$stmt = db()->prepare("SELECT * FROM blogs WHERE slug = ? AND status = 'published' LIMIT 1");
$stmt->execute([$slug]);
$blog = $stmt->fetch();

if (!$blog) {
    http_response_code(404);
    echo '<h1 style="font-family:sans-serif;text-align:center;margin-top:100px;">Blog post not found</h1>';
    echo '<p style="text-align:center;"><a href="/blogs.php">← Back to Blogs</a></p>';
    exit;
}

$recentStmt = db()->prepare("SELECT title, slug, featured_image FROM blogs
                              WHERE status = 'published' AND id != ?
                              ORDER BY published_at DESC LIMIT 4");
$recentStmt->execute([$blog['id']]);
$recent = $recentStmt->fetchAll();

function bfe($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
function blog_image_url($path) {
    if (!$path) return 'assets/images/blog-image/default-blog.png';
    return UPLOAD_URL . '/' . $path;
}
function slugify($text) {
    $text = strtolower(strip_tags((string) $text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'section';
}

$metaTitle = $blog['meta_title'] ?: ($blog['title'] . ' – AMR Packwell');
$metaDescription = $blog['meta_description'] ?: $blog['excerpt'];
$bodyText = (string) ($blog['content'] ?? '');
$wordCount = str_word_count(strip_tags($bodyText));
$readTime = max(1, (int) ceil($wordCount / 180));

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$articleHtml = $bodyText;
$tocItems = [];
$headingIndex = 0;
$articleHtml = preg_replace_callback('/<h2([^>]*)>(.*?)<\/h2>/is', function ($m) use (&$headingIndex, &$tocItems) {
    $level = 2;
    $attrs = $m[1];
    $inner = $m[2];
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($inner)));

    if (preg_match('/\bid\s*=\s*["\']([^"\']+)["\']/i', $attrs, $idMatch)) {
        $id = $idMatch[1];
    } else {
        $id = slugify($text) . ($headingIndex > 0 ? '-' . $headingIndex : '');
    }

    if ($text !== '') {
        $tocItems[] = ['id' => $id, 'text' => $text, 'level' => $level];
    }

    $headingIndex++;
    return '<h2' . $attrs . ' id="' . $id . '">' . $inner . '</h2>';
}, $articleHtml);
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
    <title><?= bfe($metaTitle) ?></title>
    <meta name="description" content="<?= bfe($metaDescription) ?>" />
    <?php if (!empty($blog['meta_keywords'])): ?>
    <meta name="keywords" content="<?= bfe($blog['meta_keywords']) ?>" />
    <?php endif; ?>
    <link rel="canonical" href="https://www.amrpackwell.com/blog/<?= bfe($blog['slug']) ?>" />

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800;900&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />

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
                }
            }
        };
    </script>

    <style>
        * { box-sizing: border-box; }
        body {
            background: #f7f8fa;
            font-family: 'Poppins', sans-serif;
            color: #1A2B4A;
            margin: 0;
            overflow-x: hidden;
        }
        .grid-pattern {
            background-image:
                linear-gradient(rgba(241, 90, 36, 0.07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(241, 90, 36, 0.07) 1px, transparent 1px);
            background-size: 32px 32px;
            animation: gridScroll 25s linear infinite;
        }
        @keyframes gridScroll {
            from { background-position: 0 0; }
            to { background-position: 0 320px; }
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-up { animation: fadeUp 0.8s ease forwards; }
        .delay-1 { animation-delay: 0.15s; opacity: 0; }
        .delay-2 { animation-delay: 0.3s; opacity: 0; }
        .delay-3 { animation-delay: 0.45s; opacity: 0; }
        .delay-4 { animation-delay: 0.6s; opacity: 0; }
        .reveal {
            opacity: 0;
            transform: translateY(24px);
            transition: all 0.7s ease-out;
        }
        .reveal.active { opacity: 1; transform: translateY(0); }
        .stat-card {
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, transparent 60%, rgba(241, 90, 36, 0.08));
            pointer-events: none;
        }
        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 40px -10px rgba(26, 43, 74, 0.18);
        }
        .toc-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            color: #4a5568;
            text-decoration: none;
            transition: all 0.2s;
        }
        .toc-link:hover, .toc-link.active {
            background: #F15A24;
            color: white;
        }
        .toc-link span.num {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 11px;
            background: rgba(241, 90, 36, 0.12);
            color: #F15A24;
            width: 24px;
            height: 24px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.2s;
        }
        .toc-link:hover span.num, .toc-link.active span.num {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        #reading-progress {
            position: fixed;
            top: 0;
            left: 0;
            height: 4px;
            background: linear-gradient(90deg, #F15A24, #FF7A45);
            z-index: 9999;
            width: 0%;
            transition: width 0.1s;
            border-radius: 0 4px 4px 0;
        }
        .pull-quote {
            position: relative;
            border-left: 4px solid #F15A24;
            background: linear-gradient(135deg, #fff8f5, #fff);
            border-radius: 0 16px 16px 0;
            padding: 24px 28px;
        }
        .pull-quote::before {
            content: '"';
            font-family: 'Montserrat', sans-serif;
            font-size: 80px;
            color: #F15A24;
            opacity: 0.15;
            position: absolute;
            top: -10px;
            left: 16px;
            line-height: 1;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #F15A24;
            text-decoration: none;
            padding: 10px 20px;
            border: 2px solid #F15A24;
            border-radius: 12px;
            transition: all 0.25s;
        }
        .back-btn:hover {
            background: #F15A24;
            color: white;
        }
        .share-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            border: 1.5px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
            color: #1A2B4A;
        }
        .share-pill:hover {
            border-color: #F15A24;
            color: #F15A24;
        }
        html {
            scroll-behavior: smooth;
        }

        .blog-article-body h2,
        .blog-article-body h3 {
            scroll-margin-top: 120px;
        }

        .blog-article-body h2 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 1.6rem;
            color: #1A2B4A;
            margin: 2rem 0 1rem;
        }
        .blog-article-body h3 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1.25rem;
            color: #1A2B4A;
            margin: 1.5rem 0 0.75rem;
        }
        .blog-article-body p {
            color: #4b5563;
            line-height: 1.8;
            margin-bottom: 1rem;
            font-size: 15px;
        }
        .blog-article-body img {
            border-radius: 1rem;
            margin: 1.5rem 0;
            max-width: 100%;
        }
        .blog-article-body ul, .blog-article-body ol {
            margin: 1rem 0 1rem 1.5rem;
            color: #4b5563;
        }
        .blog-article-body ul { list-style: disc; }
        .blog-article-body ol { list-style: decimal; }
        .blog-article-body li { margin-bottom: 0.5rem; line-height: 1.7; }
        .blog-article-body blockquote {
            border-left: 4px solid #F15A24;
            padding: 0.5rem 0 0.5rem 1.25rem;
            margin: 1.5rem 0;
            font-style: italic;
            color: #1A2B4A;
            font-weight: 600;
        }
        .blog-article-body a { color: #F15A24; text-decoration: underline; }
        .blog-article-body pre {
            background: #0f1c30;
            color: #e2e8f0;
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            overflow-x: auto;
            margin: 1.5rem 0;
        }
    </style>
</head>

<body>
    <div id="reading-progress"></div>
    <div id="navbar-header"></div>
    <script>
        fetch('header.html').then(r => r.text()).then(d => { document.getElementById('navbar-header').innerHTML = d; }).catch(() => {});
    </script>

    <header class="relative bg-navy-dark pt-28 pb-44 overflow-hidden" style="background:#0f1c30;">
        <div class="absolute inset-0 grid-pattern opacity-30 z-0"></div>
        <div class="absolute inset-0 z-0">
            <img src="<?= bfe(blog_image_url($blog['featured_image'])) ?>" class="w-full h-full object-cover opacity-20" alt="<?= bfe($blog['title']) ?>">
            <div class="absolute inset-0" style="background:linear-gradient(to top, #0f1c30 30%, rgba(15,28,48,0.6) 100%)"></div>
        </div>

        <div class="max-w-5xl mx-auto px-6 relative z-10">
            <div class="fade-up delay-1 flex items-center gap-2 text-xs text-gray-400 mb-8">
                <a href="blogs.php" class="hover:text-orange transition-colors">Insights</a>
                <span>›</span>
                <span class="text-orange" style="color:#F15A24;"><?= bfe($blog['category']) ?></span>
            </div>

            <div class="fade-up delay-1">
                <span class="inline-block px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest mb-6" style="background:rgba(241,90,36,0.15);color:#F15A24;border:1px solid rgba(241,90,36,0.3)">
                    <?= bfe($blog['category']) ?>
                </span>
            </div>

            <h1 class="fade-up delay-2 font-montserrat font-black text-white leading-tight mb-6" style="font-size:clamp(2.2rem,5vw,3.8rem);">
                <?= bfe($blog['title']) ?>
            </h1>

            <p class="fade-up delay-3 text-gray-300 font-light leading-relaxed mb-10 max-w-2xl" style="font-size:1.1rem;">
                <?= bfe($blog['excerpt']) ?>
            </p>

            <div class="fade-up delay-4 flex flex-wrap items-center gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-montserrat font-bold text-sm text-white" style="background:#F15A24;">
                        <?= bfe(strtoupper(substr($blog['posted_by'], 0, 2))) ?>
                    </div>
                    <div>
                        <p class="text-white font-semibold text-sm"><?= bfe($blog['posted_by']) ?></p>
                        <p class="text-gray-400 text-xs">Packaging Expert, AMR Packwell</p>
                    </div>
                </div>
                <div class="h-8 w-px bg-gray-600"></div>
                <div class="flex items-center gap-4 text-xs text-gray-400">
                    <span>📅 <?= bfe(date('M j, Y', strtotime($blog['published_at']))) ?></span>
                    <span>⏱ <?= bfe($readTime) ?> min read</span>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-16 -mt-24 relative z-20">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
            <article class="lg:col-span-8">
                <div class="reveal rounded-3xl overflow-hidden shadow-2xl mb-14 relative" style="height:420px;">
                    <img src="<?= bfe(blog_image_url($blog['featured_image'])) ?>" class="w-full h-full object-cover" alt="<?= bfe($blog['title']) ?>">
                    <div class="absolute bottom-0 left-0 right-0 p-6" style="background:linear-gradient(to top, rgba(15,28,48,0.85), transparent)">
                        <p class="text-white text-sm font-light"><?= bfe($blog['excerpt']) ?></p>
                    </div>
                </div>

                <div class="reveal grid grid-cols-1 md:grid-cols-3 gap-5 mb-14">
                    <div class="stat-card bg-white rounded-2xl p-6 text-center border border-gray-100 shadow-sm">
                        <div class="font-montserrat font-black text-4xl mb-1" style="color:#F15A24;"><?= bfe($readTime) ?>m</div>
                        <p class="text-xs text-gray-500 font-medium leading-tight">Estimated<br>Read Time</p>
                    </div>
                    <div class="stat-card bg-white rounded-2xl p-6 text-center border border-gray-100 shadow-sm">
                        <div class="font-montserrat font-black text-4xl mb-1" style="color:#1A2B4A;"><?= bfe($blog['category']) ?></div>
                        <p class="text-xs text-gray-500 font-medium leading-tight">Primary<br>Category</p>
                    </div>
                    <div class="stat-card bg-white rounded-2xl p-6 text-center border border-gray-100 shadow-sm">
                        <div class="font-montserrat font-black text-4xl mb-1" style="color:#F15A24;"><?= bfe(date('M Y', strtotime($blog['published_at']))) ?></div>
                        <p class="text-xs text-gray-500 font-medium leading-tight">Published<br>On</p>
                    </div>
                </div>

                <div class="blog-article-body">
                    <?= $articleHtml ?>
                </div>

                <div class="reveal flex flex-wrap gap-2 mb-8 mt-10">
                    <span class="px-4 py-2 rounded-full text-xs font-bold text-navy/60 border border-gray-200 bg-white">#<?= bfe($blog['category']) ?></span>
                    <span class="px-4 py-2 rounded-full text-xs font-bold text-navy/60 border border-gray-200 bg-white">#Packaging</span>
                    <span class="px-4 py-2 rounded-full text-xs font-bold text-navy/60 border border-gray-200 bg-white">#AMRPackwell</span>
                </div>

                <div class="reveal flex flex-wrap items-center gap-3 pt-6 border-t border-gray-100">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Share this</span>
                    <button class="share-pill">🔗 Copy Link</button>
                    <button class="share-pill">📘 LinkedIn</button>
                    <button class="share-pill">🐦 Twitter</button>
                    <button class="share-pill">📧 Email</button>
                </div>

                <div class="reveal mt-10">
                    <a href="blogs.php" class="back-btn">← Back to Insights</a>
                </div>
            </article>

            <aside class="lg:col-span-4 space-y-8">
                <?php if (!empty($tocItems)): ?>
                <div class="reveal bg-white rounded-3xl p-7 shadow-sm border border-gray-100 sticky top-8">
                    <h4 class="font-montserrat font-black text-navy text-sm uppercase tracking-widest mb-5">In This Article</h4>
                    <nav class="space-y-1">
                        <?php foreach ($tocItems as $index => $item): ?>
                            <a href="<?= bfe($currentPath) ?>#<?= bfe($item['id']) ?>" class="toc-link"><span class="num"><?= bfe($index + 1) ?></span><?= bfe($item['text']) ?></a>
                        <?php endforeach; ?>
                    </nav>
                </div>
                <?php endif; ?>

                <div class="reveal bg-white rounded-3xl p-7 shadow-lg border-t-4" style="border-color:#F15A24;">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl mb-5" style="background:#fff8f5;">📊</div>
                    <h3 class="font-montserrat font-black text-navy text-lg mb-3">Need a Custom Packaging Solution?</h3>
                    <p class="text-sm text-gray-500 mb-6 leading-relaxed">Our team can help you find the right packaging, pallet, or protective solution for your business.</p>
                    <a href="contact-us.php" class="block text-center w-full py-4 rounded-xl font-bold text-sm text-white transition-all duration-300" style="background:#1A2B4A;">Get In Touch →</a>
                </div>

                <?php if (!empty($recent)): ?>
                <div class="reveal bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                    <h4 class="font-montserrat font-black text-navy text-xs uppercase tracking-widest mb-5">More Articles</h4>
                    <div class="space-y-4">
                        <?php foreach ($recent as $r): ?>
                            <a href="blog/<?= bfe($r['slug']) ?>" class="flex gap-4 group">
                                <div class="w-20 h-16 rounded-xl overflow-hidden flex-shrink-0">
                                    <img src="<?= bfe(blog_image_url($r['featured_image'])) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" alt="<?= bfe($r['title']) ?>">
                                </div>
                                <div>
                                    <span class="text-xs font-bold uppercase tracking-wider" style="color:#F15A24;"><?= bfe($blog['category']) ?></span>
                                    <h5 class="font-montserrat font-bold text-navy text-sm leading-snug mt-1 group-hover:text-orange transition-colors">
                                        <?= bfe($r['title']) ?>
                                    </h5>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </aside>
        </div>
    </main>

    <div id="footer-bottom"></div>
    <script>
        fetch('footer.php').then(r => r.text()).then(d => { document.getElementById('footer-bottom').innerHTML = d; }).catch(() => {});
    </script>

    <script>
        window.addEventListener('scroll', () => {
            const doc = document.documentElement;
            const scrollTop = doc.scrollTop || document.body.scrollTop;
            const scrollHeight = doc.scrollHeight - doc.clientHeight;
            const progress = scrollHeight > 0 ? (scrollTop / scrollHeight) * 100 : 0;
            document.getElementById('reading-progress').style.width = progress + '%';
        });

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('active'); });
        }, { threshold: 0.1 });
        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

        const tocLinks = Array.from(document.querySelectorAll('.toc-link'));
        const articleHeadings = Array.from(document.querySelectorAll('.blog-article-body h2[id], .blog-article-body h3[id]'));

        if (tocLinks.length && articleHeadings.length) {
            const headingObserver = new IntersectionObserver((entries) => {
                const visibleEntries = entries.filter(entry => entry.isIntersecting).sort((a, b) => b.intersectionRatio - a.intersectionRatio);
                if (visibleEntries.length) {
                    const activeId = visibleEntries[0].target.id;
                    tocLinks.forEach(link => {
                        const isActive = link.getAttribute('href') === '#' + activeId;
                        link.classList.toggle('active', isActive);
                    });
                }
            }, { rootMargin: '-20% 0px -70% 0px', threshold: [0.1, 0.3, 0.6] });

            articleHeadings.forEach(heading => headingObserver.observe(heading));

            tocLinks.forEach(link => {
                link.addEventListener('click', () => {
                    tocLinks.forEach(item => item.classList.remove('active'));
                    link.classList.add('active');
                });
            });
        }
    </script>

    <script src="assets/js/header.js"></script>
    <script src="assets/js/footer.js"></script>
</body>
</html>