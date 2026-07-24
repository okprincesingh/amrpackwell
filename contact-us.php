<?php
// contact-us.php
require_once __DIR__ . '/php-admin/includes/auth.php';   // session_start() + csrf_token()
require_once __DIR__ . '/php-admin/config/db.php';

$cs = db()->query('SELECT * FROM contact_page_settings WHERE id = 1')->fetch() ?: [];

function cfe($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

function cs_list($json) {
    $list = json_decode($json ?? '', true);
    if (!is_array($list)) return [];
    return array_values(array_filter(array_map('trim', $list), fn($v) => $v !== ''));
}

$csAddresses = cs_list($cs['addresses'] ?? null);
$csPhones    = cs_list($cs['phones'] ?? null);
$csEmails    = cs_list($cs['emails'] ?? null);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contact AMR Packwell | Packaging Solutions Company in Greater Noida</title>
       <meta name="description" content="Contact AMR Packwell for corrugated boxes, wooden pallets, paper angle boards and trusted industrial packaging solutions across India today for all industries now."/>
       <meta name="keywords" content="Plywood Pallet Near Me, Plywood Pallet Manufacturer, Plywood Packaging Box, Plywood Box Manufacturer, Paper Angle Board, Paper Angle Board Manufacturer, Corrugated Box Manufacturer, Corrugated Box Near Me, Corrugated Box in Noida, Wooden Pallet Manufacturer"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="icon" type="image/png" href="assets/images/logo/logo_140881.png" />
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/output.css">
    <link rel="stylesheet" href="assets/css/styless.css">
    <script src="assets/js/header.js"></script>
<link rel="canonical" href="https://www.amrpackwell.com/contact-us">
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800;900&family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        montserrat: ['Montserrat', 'sans-serif'],
                        poppins: ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        orange: { DEFAULT: '#F15A24', light: '#FF7A45', pale: '#FFF3EE' },
                        navy: { DEFAULT: '#1A2B4A', dark: '#111d33', light: '#243660' },
                    },
                    keyframes: {
                        fadeUp: { from: { opacity: '0', transform: 'translateY(30px)' }, to: { opacity: '1', transform: 'translateY(0)' } },
                        pulse2: { '0%,100%': { transform: 'scale(1)' }, '50%': { transform: 'scale(1.05)' } },
                    },
                    animation: {
                        fadeUp: 'fadeUp 0.6s ease both',
                        'fadeUp-d1': 'fadeUp 0.6s 0.1s ease both',
                        'fadeUp-d2': 'fadeUp 0.6s 0.2s ease both',
                        'fadeUp-d3': 'fadeUp 0.6s 0.3s ease both',
                        'fadeUp-d4': 'fadeUp 0.6s 0.4s ease both',
                        pulse2: 'pulse2 2s ease-in-out infinite',
                    },
                    boxShadow: {
                        card: '0 8px 40px rgba(26,43,74,0.10)',
                        'card-hover': '0 16px 60px rgba(26,43,74,0.16)',
                        orange: '0 8px 28px rgba(241,90,36,0.35)',
                    }
                }
            }
        };
    </script>
    <style>
        /* Minimal non-Tailwind helpers */
        .chip-active {
            background: #1A2B4A;
            color: #fff;
            border-color: #1A2B4A;
        }

        .chip-inactive {
            background: #fff;
            color: #1A2B4A;
            border-color: #d1d5db;
        }

        .chip-inactive:hover {
            border-color: #F15A24;
            color: #F15A24;
        }

        .map-shimmer {
            background: linear-gradient(135deg, #e8edf5 0%, #f3f5f9 50%, #e8edf5 100%);
        }

        .section-line::after {
            content: '';
            display: block;
            width: 60px;
            height: 3px;
            background: #F15A24;
            border-radius: 2px;
            margin-top: 8px;
        }

        .info-card:hover .info-icon {
            background: #F15A24;
            color: #fff;
            transform: scale(1.1);
        }

        .fill-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, #F15A24, #FF7A45);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.28s cubic-bezier(.4, 0, .2, 1);
            border-radius: inherit;
            z-index: 0;
        }

        .fill-btn:hover::before {
            transform: scaleX(1);
        }

        .fill-btn:hover {
            color: #fff !important;
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(241, 90, 36, 0.35);
        }

        .fill-btn span {
            position: relative;
            z-index: 1;
        }

        .input-focus:focus {
            border-color: #F15A24 !important;
            box-shadow: 0 0 0 4px rgba(241, 90, 36, 0.12);
        }

        select option {
            color: #1A2B4A;
        }

        .social-btn:hover svg {
            transform: scale(1.15);
        }

        .breadcrumb-sep::after {
            content: '/';
            margin: 0 8px;
            color: #9ca3af;
        }

        .breadcrumb-sep:last-child::after {
            display: none;
        }

        /* ── Scroll Slide Animations ── */
        .slide-left {
            opacity: 0;
            transform: translateX(-70px);
            transition: opacity 0.75s cubic-bezier(.4, 0, .2, 1), transform 0.75s cubic-bezier(.4, 0, .2, 1);
        }

        .slide-right {
            opacity: 0;
            transform: translateX(70px);
            transition: opacity 0.75s cubic-bezier(.4, 0, .2, 1), transform 0.75s cubic-bezier(.4, 0, .2, 1);
        }

        .slide-up {
            opacity: 0;
            transform: translateY(50px);
            transition: opacity 0.7s cubic-bezier(.4, 0, .2, 1), transform 0.7s cubic-bezier(.4, 0, .2, 1);
        }

        .slide-left.revealed,
        .slide-right.revealed,
        .slide-up.revealed {
            opacity: 1;
            transform: translate(0, 0);
        }

        .delay-1 {
            transition-delay: 0.10s;
        }

        .delay-2 {
            transition-delay: 0.20s;
        }

        .delay-3 {
            transition-delay: 0.30s;
        }

        .delay-4 {
            transition-delay: 0.45s;
        }
    </style>
</head>

<body>
    <div id="navbar-header"></div>

    <script>
        // Navbar Load aur Fix karne ka logic
        fetch('header.html')
            .then(response => response.text())
            .then(data => {
                const navbarContainer = document.getElementById('navbar-header');
                navbarContainer.innerHTML = data;

                // Scroll Event Listener
                window.addEventListener('scroll', function () {
                    // Header element fetch hone ke baad hum header tag ko target karenge
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

    <div class="font-poppins bg-gray-50 text-navy">

        <!-- ══════════════════════════════════
     HERO BANNER
══════════════════════════════════ -->
        <section class="relative bg-navy-dark overflow-hidden">
            <div class="absolute inset-0 z-0">
                <img src="https://www.brtindia.co.in/img/contact-us-banner.png" alt="Background"
                    class="w-full h-full object-cover opacity-20">
                <!-- <div class="absolute inset-0 bg-gradient-to-br from-navy-dark/95 via-navy/90 to-navy-light/80"></div> -->
            </div>

            <div class="absolute -top-20 -right-20 w-96 h-96 rounded-full border border-white/5 opacity-40 z-0"></div>
            <div class="absolute -top-10 -right-10 w-72 h-72 rounded-full border border-white/5 opacity-30 z-0"></div>
            <div class="absolute bottom-0 left-1/4 w-64 h-64 rounded-full bg-orange/5 z-0"></div>

            <div
                class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-[#F15A24] via-[#FF7A45] to-[#F15A24] z-20">
            </div>

            <div class="max-w-6xl mx-auto px-6 py-14 md:py-20 relative z-10">
                <nav class="flex items-center mb-6 text-white/50 font-poppins text-sm">
                    <a href="index.html" class="hover:text-orange transition-colors">Home</a>
                    <span class="mx-2">/</span> <span class="text-white/80 font-medium">Contact Us</span>
                </nav>

                <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6">
                    <div class="slide-left">
                        <span
                            class="inline-block bg-orange/20 text-orange-light border border-orange/30 rounded-full text-xs font-montserrat font-600 px-4 py-1 mb-4 tracking-wider uppercase">
                            Get In Touch
                        </span>
                        <h1 class="font-montserrat text-4xl md:text-5xl lg:text-6xl font-900 text-white leading-tight">
                            We'd Love to<br />
                            <span
                                class="text-transparent bg-clip-text bg-gradient-to-r from-orange to-orange-light">Hear
                                From You</span>
                        </h1>
                        <p class="mt-4 text-white/60 font-poppins text-base max-w-md leading-relaxed">
                            Have a packaging requirement? Need a custom quote? Our team is ready to help you find the
                            perfect solution.
                        </p>
                    </div>

                    <div id="statsBlock"
                        class="flex gap-6 slide-right bg-white/5 backdrop-blur-sm p-6 rounded-2xl border border-white/10">
                        <div class="text-center">
                            <div class="font-montserrat text-3xl font-800 text-orange">
                                <span id="stat-clients">0</span><span class="text-orange">+</span>
                            </div>
                            <div class="text-white/50 text-xs mt-1 font-poppins text-nowrap">Happy Clients</div>
                        </div>
                        <div class="w-px bg-white/10"></div>
                        <div class="text-center">
                            <div class="font-montserrat text-3xl font-800 text-orange">
                                <span id="stat-response">0</span><span class="text-orange">h</span>
                            </div>
                            <div class="text-white/50 text-xs mt-1 font-poppins text-nowrap">Response Time</div>
                        </div>
                        <div class="w-px bg-white/10"></div>
                        <div class="text-center">
                            <div class="font-montserrat text-3xl font-800 text-orange">
                                <span id="stat-years">0</span><span class="text-orange">+</span>
                            </div>
                            <div class="text-white/50 text-xs mt-1 font-poppins text-nowrap">Years Experience</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════
     INFO CARDS ROW
══════════════════════════════════ -->
        <section class="max-w-6xl mx-auto px-6 -mt-8 relative z-20">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                <!-- Card 1: Address -->
                <div
                    class="info-card slide-left bg-white rounded-2xl shadow-card p-5 flex items-start gap-4 transition-shadow transition-transform duration-300 hover:shadow-card-hover hover:-translate-y-1">
                    <div
                        class="info-icon w-11 h-11 rounded-xl bg-orange/10 text-orange flex items-center justify-center shrink-0 transition-all duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-montserrat text-xs font-700 text-gray-400 uppercase tracking-widest mb-1">
                            Address
                        </div>
                        <?php foreach ($csAddresses as $addr): ?>
                        <p class="font-poppins text-sm text-navy leading-snug mb-1 last:mb-0"><?= nl2br(cfe($addr)) ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Card 2: Phone -->
                <div
                    class="info-card slide-up delay-1 bg-white rounded-2xl shadow-card p-5 flex items-start gap-4 transition-shadow transition-transform duration-300 hover:shadow-card-hover hover:-translate-y-1">
                    <div
                        class="info-icon w-11 h-11 rounded-xl bg-orange/10 text-orange flex items-center justify-center shrink-0 transition-all duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498A1 1 0 0121 15.72V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-montserrat text-xs font-700 text-gray-400 uppercase tracking-widest mb-1">
                            Mobile
                        </div>
                        <?php foreach ($csPhones as $phone): ?>
                        <a href="tel:<?= cfe($phone) ?>"
                            class="font-poppins text-sm text-navy hover:text-orange transition-colors font-500 block mt-0.5 first:mt-0"><?= cfe($phone) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Card 3: Email -->
                <div
                    class="info-card slide-up delay-2 bg-white rounded-2xl shadow-card p-5 flex items-start gap-4 transition-shadow transition-transform duration-300 hover:shadow-card-hover hover:-translate-y-1">
                    <div
                        class="info-icon w-11 h-11 rounded-xl bg-orange/10 text-orange flex items-center justify-center shrink-0 transition-all duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-montserrat text-xs font-700 text-gray-400 uppercase tracking-widest mb-1">Email
                        </div>
                        <?php foreach ($csEmails as $email): ?>
                        <a href="mailto:<?= cfe($email) ?>"
                            class="font-poppins text-[12px] text-navy hover:text-orange transition-colors font-500 block break-all mt-0.5 first:mt-0"><?= cfe($email) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Card 4: Working Hours -->
                <div
                    class="info-card slide-right delay-3 bg-white rounded-2xl shadow-card p-5 flex items-start gap-4 transition-shadow transition-transform duration-300 hover:shadow-card-hover hover:-translate-y-1">
                    <div
                        class="info-icon w-11 h-11 rounded-xl bg-orange/10 text-orange flex items-center justify-center shrink-0 transition-all duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" />
                            <path stroke-linecap="round" d="M12 6v6l4 2" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-montserrat text-xs font-700 text-gray-400 uppercase tracking-widest mb-1">
                            Working
                            Hours</div>
                        <p class="font-poppins text-sm text-navy leading-snug"><span
                                class="font-600"><?= cfe($cs['working_hours'] ?? 'Mon - Sat: 9:00 AM - 7:00 PM') ?></span></p>
                        <span class="inline-flex items-center gap-1 mt-1.5 text-xs text-green-600 font-500">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> Currently Open
                        </span>
                    </div>
                </div>

            </div>
        </section>


        <!-- ══════════════════════════════════
     MAIN CONTACT SECTION
══════════════════════════════════ -->
        <section class="max-w-6xl mx-auto px-6 py-14 grid grid-cols-1 lg:grid-cols-5 gap-10">

            <!-- LEFT: Contact Details + Send SMS -->
            <div class="lg:col-span-2 slide-left">

                <!-- Contact Person -->
                <div class="mb-8">
                    <h2 class="font-montserrat text-2xl font-800 text-navy section-line mb-6">Contact Details</h2>

                    <div class="flex items-start gap-4 mb-5">
                        <div class="w-12 h-12 rounded-xl bg-orange/10 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-orange" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <div>
                                <p class="font-montserrat font-700 text-navy text-base">Mr. Kamesh Mandal</p>
                                <p class="font-poppins text-sm text-gray-500 mt-0.5">Founder</p>

                            </div>
                            <div>
                                <p class="font-montserrat font-700 text-navy text-base">Mr. Rajesh Mandal</p>
                                <p class="font-poppins text-sm text-gray-500 mt-0.5">Director</p>

                            </div>
                        </div>

                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <rect x="5" y="2" width="14" height="20" rx="2" />
                                    <path d="M12 18h.01" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <p class="text-xs text-gray-400 font-poppins">Mobile</p>
                                <a href="tel:+919871523344"
                                    class="font-montserrat font-600 text-navy text-sm hover:text-orange transition-colors">+91
                                    9871523344</a>
                                <a href="tel:+917979097021"
                                    class="font-montserrat font-600 text-navy text-sm hover:text-orange transition-colors">+91
                                    7979097021</a>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div
                                class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-poppins">Address</p>
                                <p class="font-poppins text-sm text-navy leading-relaxed">Plot No. N-11, site-5,
                                    Kasna,<br />Greater Noida-201308,<br />Uttar Pradesh, India</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Send SMS Box -->
                <form id="smsForm" data-local-submit="1" class="bg-white rounded-2xl shadow-card p-6 mb-6 border border-gray-100">
                    <input type="hidden" name="csrf_token" value="<?= cfe(csrf_token()) ?>">
                    <input type="text" name="website" value="" style="position:absolute;left:-9999px" tabindex="-1" autocomplete="off">
                    <h3 class="font-montserrat font-700 text-navy text-base mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-orange" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        Send SMS
                    </h3>
                    <p class="font-poppins text-sm text-gray-500 mb-4">Send a quick text message to our team for fast
                        support.</p>
                    <input type="text" name="sms_name" placeholder="Your name" required
                        class="input-focus w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-poppins text-navy outline-none transition-all duration-200 mb-3 bg-gray-50" />
                    <input type="tel" name="sms_mobile" placeholder="Mobile number" required
                        class="input-focus w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-poppins text-navy outline-none transition-all duration-200 mb-3 bg-gray-50" />
                    <input type="text" name="sms_message" placeholder="Your message" required
                        class="input-focus w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm font-poppins text-navy outline-none transition-all duration-200 mb-3 bg-gray-50" />
                    <button type="submit"
                        class="fill-btn relative w-full bg-transparent border-2 border-navy text-navy font-montserrat font-700 text-sm rounded-xl py-2.5 overflow-hidden transition-all duration-200 flex items-center justify-center gap-2">
                        <span class="relative z-10 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            SEND SMS
                        </span>
                    </button>
                    <div id="smsResultMsg" class="hidden mt-3 rounded-xl px-4 py-3 text-sm font-poppins"></div>
                </form>

                <!-- Social Media -->
                <div class="bg-white rounded-2xl shadow-card p-6 border border-gray-100">
                    <h3 class="font-montserrat font-700 text-navy text-base mb-4">Follow Us</h3>
                    <div class="flex gap-3">
                        <a href="#"
                            class="social-btn w-10 h-10 rounded-xl bg-[#1877F2]/10 text-[#1877F2] flex items-center justify-center transition-all duration-200 hover:bg-[#1877F2] hover:text-white">
                            <svg class="w-5 h-5 transition-transform duration-200" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z" />
                            </svg>
                        </a>
                        <a href="#"
                            class="social-btn w-10 h-10 rounded-xl bg-[#E1306C]/10 text-[#E1306C] flex items-center justify-center transition-all duration-200 hover:bg-[#E1306C] hover:text-white">
                            <svg class="w-5 h-5 transition-transform duration-200" fill="none" stroke="currentColor"
                                stroke-width="2" viewBox="0 0 24 24">
                                <rect x="2" y="2" width="20" height="20" rx="5" />
                                <circle cx="12" cy="12" r="4" />
                                <circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none" />
                            </svg>
                        </a>
                        <a href="#"
                            class="social-btn w-10 h-10 rounded-xl bg-[#25D366]/10 text-[#25D366] flex items-center justify-center transition-all duration-200 hover:bg-[#25D366] hover:text-white">
                            <svg class="w-5 h-5 transition-transform duration-200" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z" />
                                <path
                                    d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.978-1.393A9.953 9.953 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2z" />
                            </svg>
                        </a>
                        <a href="#"
                            class="social-btn w-10 h-10 rounded-xl bg-[#0A66C2]/10 text-[#0A66C2] flex items-center justify-center transition-all duration-200 hover:bg-[#0A66C2] hover:text-white">
                            <svg class="w-5 h-5 transition-transform duration-200" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z" />
                                <circle cx="4" cy="4" r="2" />
                            </svg>
                        </a>
                        <a href="#"
                            class="social-btn w-10 h-10 rounded-xl bg-[#FF0000]/10 text-[#FF0000] flex items-center justify-center transition-all duration-200 hover:bg-[#FF0000] hover:text-white">
                            <svg class="w-5 h-5 transition-transform duration-200" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M21.543 6.498C22 8.28 22 12 22 12s0 3.72-.457 5.502c-.254.985-.997 1.76-1.938 2.022C17.896 20 12 20 12 20s-5.893 0-7.605-.476c-.945-.266-1.687-1.04-1.938-2.022C2 15.72 2 12 2 12s0-3.72.457-5.502c.254-.985.997-1.76 1.938-2.022C6.107 4 12 4 12 4s5.896 0 7.605.476c.945.266 1.687 1.04 1.938 2.022zM10 15.5l6-3.5-6-3.5v7z" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Main Contact Form -->
            <div class="lg:col-span-3 slide-right">
                <form id="mainContactForm" data-local-submit="1" class="bg-white rounded-3xl shadow-card p-8 border border-gray-100">
                    <input type="hidden" name="csrf_token" value="<?= cfe(csrf_token()) ?>">
                    <input type="text" name="website" value="" style="position:absolute;left:-9999px" tabindex="-1" autocomplete="off">
                    <h2 class="font-montserrat text-2xl font-800 text-navy mb-1">Contact Us</h2>
                    <p class="font-poppins text-sm text-gray-400 mb-6">Fill the form below and we'll get back to you
                        within
                        24 hours.</p>

                    <!-- Tab Chips -->
                    <div class="flex flex-wrap gap-2 mb-6" id="chipGroup">
                        <input type="hidden" name="inquiry_type" id="inquiryTypeInput" value="Get Quotation" />
                        <button type="button" onclick="selectChip(this)"
                            class="chip chip-active font-poppins text-sm font-500 px-4 py-1.5 rounded-full border transition-all duration-200">Get
                            Quotation</button>
                        <button type="button" onclick="selectChip(this)"
                            class="chip chip-inactive font-poppins text-sm font-500 px-4 py-1.5 rounded-full border transition-all duration-200">Get
                            Price List</button>
                        <button type="button" onclick="selectChip(this)"
                            class="chip chip-inactive font-poppins text-sm font-500 px-4 py-1.5 rounded-full border transition-all duration-200">Discuss
                            Requirement</button>
                        <button type="button" onclick="selectChip(this)"
                            class="chip chip-inactive font-poppins text-sm font-500 px-4 py-1.5 rounded-full border transition-all duration-200">General
                            Inquiry</button>
                    </div>

                    <!-- Requirement Textarea -->
                    <div class="mb-4">
                        <label
                            class="block font-poppins text-xs font-600 text-gray-500 uppercase tracking-wider mb-2">Tell
                            us your requirement *</label>
                        <textarea name="requirement" required rows="4"
                            placeholder="Describe your packaging requirement in detail — product type, quantity, dimensions, material preference..."
                            class="input-focus w-full bg-gray-50 border border-gray-200 rounded-2xl px-5 py-4 font-poppins text-sm text-navy resize-none outline-none transition-all duration-200 placeholder-gray-400"></textarea>
                    </div>

                    <!-- Name + Company Row -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label
                                class="block font-poppins text-xs font-600 text-gray-500 uppercase tracking-wider mb-2">Full
                                Name *</label>
                            <input type="text" name="full_name" required placeholder="Your full name"
                                class="input-focus w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-poppins text-sm text-navy outline-none transition-all duration-200 placeholder-gray-400" />
                        </div>
                        <div>
                            <label
                                class="block font-poppins text-xs font-600 text-gray-500 uppercase tracking-wider mb-2">Company
                                Name</label>
                            <input type="text" name="company_name" placeholder="Your company (optional)"
                                class="input-focus w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-poppins text-sm text-navy outline-none transition-all duration-200 placeholder-gray-400" />
                        </div>
                    </div>

                    <!-- Email + Mobile Row -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label
                                class="block font-poppins text-xs font-600 text-gray-500 uppercase tracking-wider mb-2">Email
                                Address</label>
                            <input type="email" name="email" placeholder="you@company.com"
                                class="input-focus w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-poppins text-sm text-navy outline-none transition-all duration-200 placeholder-gray-400" />
                        </div>
                        <div>
                            <label
                                class="block font-poppins text-xs font-600 text-gray-500 uppercase tracking-wider mb-2">Mobile
                                Number *</label>
                            <div class="flex gap-2">
                                <!-- Country code -->
                                <div
                                    class="flex items-center gap-1.5 bg-gray-50 border border-gray-200 rounded-xl px-3 py-3 shrink-0 input-focus transition-all duration-200 cursor-pointer">
                                    <span class="text-base">🇮🇳</span>
                                    <span class="font-poppins text-sm text-navy font-500">+91</span>
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M7 10l5 5 5-5H7z" />
                                    </svg>
                                </div>
                                <input type="tel" name="mobile_number" required placeholder="Mobile number"
                                    class="input-focus flex-1 bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-poppins text-sm text-navy outline-none transition-all duration-200 placeholder-gray-400" />
                            </div>
                        </div>
                    </div>

                    <!-- Product Category -->
                    <div class="mb-4">
                        <label
                            class="block font-poppins text-xs font-600 text-gray-500 uppercase tracking-wider mb-2">Product
                            Category</label>
                        <select name="product_category"
                            class="input-focus w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-poppins text-sm text-navy outline-none transition-all duration-200 appearance-none cursor-pointer">
                            <option value="">Select a product category</option>
                            <option>Paper Edge Protector</option>
                            <option>Wooden Pallets</option>
                            <option>Wooden Box / Crate</option>
                            <option>Corrugated Boxes</option>
                            <option>Cardboard Tray</option>
                            <option>Box Packaging Services</option>
                            <option>Paper Angle Board</option>
                            <option>Other / Custom</option>
                        </select>
                    </div>

                    <!-- Quantity + Unit -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label
                                class="block font-poppins text-xs font-600 text-gray-500 uppercase tracking-wider mb-2">Quantity</label>
                            <input type="number" name="quantity" placeholder="e.g. 500"
                                class="input-focus w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-poppins text-sm text-navy outline-none transition-all duration-200 placeholder-gray-400" />
                        </div>
                        <div>
                            <label
                                class="block font-poppins text-xs font-600 text-gray-500 uppercase tracking-wider mb-2">Unit</label>
                            <select name="unit"
                                class="input-focus w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 font-poppins text-sm text-navy outline-none transition-all duration-200 appearance-none cursor-pointer">
                                <option>Pieces</option>
                                <option>Kilograms</option>
                                <option>Metric Tonnes</option>
                                <option>Bundles</option>
                                <option>Rolls</option>
                            </select>
                        </div>
                    </div>

                    <!-- Submit -->
                    <button type="submit" id="submitBtn"
                        class="fill-btn relative w-full bg-navy text-white font-montserrat font-800 text-base rounded-2xl py-4 overflow-hidden transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer border-0">
                        <span class="relative z-10 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            Contact Now
                        </span>
                    </button>

                    <!-- Success message (hidden) -->
                    <div id="successMsg"
                        class="hidden mt-4 bg-green-50 border border-green-200 rounded-xl px-5 py-4 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2.5"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <p id="successMsgText" class="font-poppins text-sm text-green-700 font-500">Your enquiry has been sent successfully!
                            We'll get back to you within 24 hours.</p>
                    </div>

                    <!-- Error message (hidden) -->
                    <div id="errorMsg"
                        class="hidden mt-4 bg-red-50 border border-red-200 rounded-xl px-5 py-4 flex items-center gap-3">
                        <p id="errorMsgText" class="font-poppins text-sm text-red-700 font-500"></p>
                    </div>

                    <p class="font-poppins text-xs text-gray-400 text-center mt-4">By submitting, you agree to our <a
                            href="#" class="text-orange hover:underline">Privacy Policy</a>. We never share your
                        details.
                    </p>
                </form>
            </div>
        </section>


        <!-- ══════════════════════════════════
     MAP SECTION
══════════════════════════════════ -->
        <section class="max-w-6xl mx-auto px-6 pb-14 slide-up">
            <div class="bg-white rounded-3xl shadow-card overflow-hidden border border-gray-100">
                <div
                    class="px-8 py-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="font-montserrat text-xl font-800 text-navy">Find Us on the Map</h2>
                        <p class="font-poppins text-sm text-gray-400 mt-0.5">
                            <?php foreach ($csAddresses as $i => $addr): ?><?= $i > 0 ? '<br>' : '' ?><?= nl2br(cfe($addr)) ?><?php endforeach; ?>
                        </p>
                    </div>
                    <a href="https://maps.google.com/?q=E-11+EPIP+Site-5+Kasna+Greater+Noida+201308+Uttar+Pradesh"
                        target="_blank"
                        class="inline-flex items-center gap-2 bg-orange/10 text-orange border border-orange/20 font-poppins text-sm font-600 px-5 py-2.5 rounded-full transition-all duration-200 hover:bg-orange hover:text-white hover:-translate-y-0.5 whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        Open in Google Maps
                    </a>
                </div>
                <!-- Map embed -->
                <div class="relative w-full h-80 md:h-96 map-shimmer">
                    <iframe class="w-full h-full border-0" loading="lazy" allowfullscreen
                        referrerpolicy="no-referrer-when-downgrade"
                        src="https://www.google.com/maps/embed?pb=!1m17!1m12!1m3!1d3334.9657532300253!2d77.53989007549352!3d28.432328275774648!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m2!1m1!2zMjjCsDI1JzU2LjQiTiA3N8KwMzInMzIuOSJF!5e1!3m2!1sen!2sin!4v1776251058059!5m2!1sen!2sin"></iframe>
                </div>
            </div>
        </section>



        <!-- ══════════════════════════════════
     FAQ STRIP
══════════════════════════════════ -->
        <section class="bg-white border-t border-gray-100 py-14">
            <div class="max-w-6xl mx-auto px-6">
                <div class="text-center mb-10">
                    <span
                        class="inline-block bg-orange/10 text-orange text-xs font-montserrat font-700 uppercase tracking-widest px-4 py-1 rounded-full mb-3">FAQ</span>
                    <h2 class="font-montserrat text-3xl font-800 text-navy">Frequently Asked Questions</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-4xl mx-auto" id="faqList">

                    <div class="faq-item slide-left bg-gray-50 rounded-2xl border border-gray-100 overflow-hidden">
                        <button onclick="toggleFAQ(this)"
                            class="w-full flex items-center justify-between px-6 py-4 font-montserrat text-sm font-700 text-navy text-left gap-3 bg-transparent border-0 cursor-pointer">
                            What is your minimum order quantity?
                            <svg class="w-5 h-5 text-orange shrink-0 transition-transform duration-200" fill="none"
                                stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                            </svg>
                        </button>
                        <div class="faq-body hidden px-6 pb-4 font-poppins text-sm text-gray-500 leading-relaxed">
                            Our MOQ varies by product. For paper edge protectors it's 500 pieces, wooden pallets start
                            at 50
                            pieces. Contact us to discuss bulk pricing for larger orders.
                        </div>
                    </div>

                    <div
                        class="faq-item slide-right delay-1 bg-gray-50 rounded-2xl border border-gray-100 overflow-hidden">
                        <button onclick="toggleFAQ(this)"
                            class="w-full flex items-center justify-between px-6 py-4 font-montserrat text-sm font-700 text-navy text-left gap-3 bg-transparent border-0 cursor-pointer">
                            Do you provide custom packaging solutions?
                            <svg class="w-5 h-5 text-orange shrink-0 transition-transform duration-200" fill="none"
                                stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                            </svg>
                        </button>
                        <div class="faq-body hidden px-6 pb-4 font-poppins text-sm text-gray-500 leading-relaxed">
                            Yes! We specialize in custom packaging tailored to your dimensions, material preferences,
                            and
                            industry needs. Share your specs and we'll provide a detailed quote.
                        </div>
                    </div>

                    <div
                        class="faq-item slide-left delay-2 bg-gray-50 rounded-2xl border border-gray-100 overflow-hidden">
                        <button onclick="toggleFAQ(this)"
                            class="w-full flex items-center justify-between px-6 py-4 font-montserrat text-sm font-700 text-navy text-left gap-3 bg-transparent border-0 cursor-pointer">
                            What is your typical delivery timeline?
                            <svg class="w-5 h-5 text-orange shrink-0 transition-transform duration-200" fill="none"
                                stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                            </svg>
                        </button>
                        <div class="faq-body hidden px-6 pb-4 font-poppins text-sm text-gray-500 leading-relaxed">
                            Standard orders are delivered within 5–7 business days. Custom orders may take 10–15 days.
                            We
                            also offer express delivery for urgent requirements at additional cost.
                        </div>
                    </div>

                    <div
                        class="faq-item slide-right delay-3 bg-gray-50 rounded-2xl border border-gray-100 overflow-hidden">
                        <button onclick="toggleFAQ(this)"
                            class="w-full flex items-center justify-between px-6 py-4 font-montserrat text-sm font-700 text-navy text-left gap-3 bg-transparent border-0 cursor-pointer">
                            Are your materials eco-friendly?
                            <svg class="w-5 h-5 text-orange shrink-0 transition-transform duration-200" fill="none"
                                stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                            </svg>
                        </button>
                        <div class="faq-body hidden px-6 pb-4 font-poppins text-sm text-gray-500 leading-relaxed">
                            Absolutely. We prioritize sustainable materials — recycled paper, FSC-certified wood, and
                            biodegradable options. We're ISO certified with a commitment to reducing environmental
                            impact.
                        </div>
                    </div>

                </div>
            </div>
        </section>

    </div>


    <!-- Modern Footer Design start -->
    <div id="footer-bottom"></div>
    <script>
        fetch('footer.php')
            .then(response => response.text())
            .then(data => {
                document.getElementById('footer-bottom').innerHTML = data;
            })
            .catch(error => console.error('Error loading slider:', error));
    </script>
    <!-- Modern Footer Design end -->
    <script src="assets/js/header.js"></script>
</body>


<!-- ══════════════════════════════════
     JAVASCRIPT
══════════════════════════════════ -->
<script>
    /* ── 1. Chip selection ── */
    function selectChip(el) {
        document.querySelectorAll('.chip').forEach(c => {
            c.classList.remove('chip-active'); c.classList.add('chip-inactive');
        });
        el.classList.remove('chip-inactive'); el.classList.add('chip-active');

        const hiddenInput = document.getElementById('inquiryTypeInput');
        if (hiddenInput) hiddenInput.value = el.textContent.trim();
    }

    /* ── 2. Main form submit — real AJAX submission to contact-submit.php ── */
    document.addEventListener('DOMContentLoaded', function () {
        const mainForm  = document.getElementById('mainContactForm');
        const btn       = document.getElementById('submitBtn');
        const btnSpan   = btn ? btn.querySelector('span') : null;
        const successEl = document.getElementById('successMsg');
        const successTx = document.getElementById('successMsgText');
        const errorEl   = document.getElementById('errorMsg');
        const errorTx   = document.getElementById('errorMsgText');
        const originalBtnHTML = btnSpan ? btnSpan.innerHTML : '';

        if (mainForm) {
            mainForm.addEventListener('submit', function (e) {
                e.preventDefault();

                successEl && successEl.classList.add('hidden');
                errorEl && errorEl.classList.add('hidden');

                if (btn) {
                    btn.disabled = true;
                    btnSpan.innerHTML = `
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg> Sending...`;
                }

                const formData = new FormData(mainForm);

                fetch('contact-submit.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            if (successTx) successTx.textContent = data.message;
                            successEl && successEl.classList.remove('hidden');
                            successEl && successEl.classList.add('flex');
                            mainForm.reset();
                            document.querySelectorAll('.chip').forEach(c => {
                                c.classList.remove('chip-active'); c.classList.add('chip-inactive');
                            });
                            const firstChip = document.querySelector('.chip');
                            if (firstChip) { firstChip.classList.add('chip-active'); firstChip.classList.remove('chip-inactive'); }
                            const hiddenInput = document.getElementById('inquiryTypeInput');
                            if (hiddenInput && firstChip) hiddenInput.value = firstChip.textContent.trim();
                            successEl && successEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        } else {
                            if (errorTx) errorTx.textContent = data.message || 'Something went wrong. Please try again.';
                            errorEl && errorEl.classList.remove('hidden');
                            errorEl && errorEl.classList.add('flex');
                        }
                    })
                    .catch(() => {
                        if (errorTx) errorTx.textContent = 'Network error. Please check your connection and try again.';
                        errorEl && errorEl.classList.remove('hidden');
                        errorEl && errorEl.classList.add('flex');
                    })
                    .finally(() => {
                        if (btn) {
                            btn.disabled = false;
                            btnSpan.innerHTML = originalBtnHTML;
                        }
                    });
            });
        }

        /* ── SMS form submit — real AJAX submission to sms-submit.php ── */
        const smsForm = document.getElementById('smsForm');
        const smsMsg  = document.getElementById('smsResultMsg');

        if (smsForm) {
            smsForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const submitBtn = smsForm.querySelector('button[type="submit"]');
                if (submitBtn) submitBtn.disabled = true;
                smsMsg && smsMsg.classList.add('hidden');

                const formData = new FormData(smsForm);

                fetch('sms-submit.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (smsMsg) {
                            smsMsg.textContent = data.message;
                            smsMsg.classList.remove('hidden');
                            smsMsg.classList.remove('bg-green-50', 'text-green-700', 'bg-red-50', 'text-red-700');
                            if (data.success) {
                                smsMsg.classList.add('bg-green-50', 'text-green-700');
                                smsForm.reset();
                            } else {
                                smsMsg.classList.add('bg-red-50', 'text-red-700');
                            }
                        }
                    })
                    .catch(() => {
                        if (smsMsg) {
                            smsMsg.textContent = 'Network error. Please try again.';
                            smsMsg.classList.remove('hidden');
                            smsMsg.classList.add('bg-red-50', 'text-red-700');
                        }
                    })
                    .finally(() => {
                        if (submitBtn) submitBtn.disabled = false;
                    });
            });
        }
    });

    /* ── 3. FAQ toggle ── */
    function toggleFAQ(btn) {
        const body = btn.nextElementSibling;
        const icon = btn.querySelector('svg');
        const isOpen = !body.classList.contains('hidden');
        document.querySelectorAll('.faq-body').forEach(b => b.classList.add('hidden'));
        document.querySelectorAll('.faq-item button svg').forEach(s => s.style.transform = '');
        if (!isOpen) { body.classList.remove('hidden'); icon.style.transform = 'rotate(45deg)'; }
    }

    /* ── 4. Counter animation ── */
    function animateCounter(id, target, duration, suffix) {
        const el = document.getElementById(id);
        if (!el) return;
        const start = performance.now();
        function update(now) {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            // ease-out
            const eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.round(eased * target);
            if (progress < 1) requestAnimationFrame(update);
            else el.textContent = target;
        }
        requestAnimationFrame(update);
    }

    let statsTriggered = false;
    function triggerStats() {
        if (statsTriggered) return;
        statsTriggered = true;
        animateCounter('stat-clients', 500, 2000);
        animateCounter('stat-response', 24, 1200);
        animateCounter('stat-years', 8, 1500);
    }

    /* ── 5. Scroll-reveal (slide left / right / up) ── */
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                // Trigger counters when stats block becomes visible
                if (entry.target.id === 'statsBlock') triggerStats();
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });

    // Observe all slide elements on DOM ready
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.slide-left, .slide-right, .slide-up').forEach(el => {
            revealObserver.observe(el);
        });
    });

    // Fallback: also run on window load (in case DOMContentLoaded already fired)
    window.addEventListener('load', () => {
        document.querySelectorAll('.slide-left, .slide-right, .slide-up').forEach(el => {
            revealObserver.observe(el);
        });
    });
</script>
</html>