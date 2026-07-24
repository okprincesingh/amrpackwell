// ══════════════════════════════════════════
// AMR PACKWELL — header.js (FULL & FIXED)
// Works on ALL pages, not just index.html
// ══════════════════════════════════════════

function onHeaderReady(callback) {
  const check = () => {
    const header = document.getElementById("mainHeader");
    const drawer = document.getElementById("drawer");
    if (header && drawer) {
      callback();
    } else {
      setTimeout(check, 50);
    }
  };
  check();
}

onHeaderReady(function () {
  const header = document.getElementById("mainHeader");
  const topBar = document.getElementById("topBar");
  const drawer = document.getElementById("drawer");
  const overlay = document.getElementById("overlay");

  // ── 1. Sticky Scroll Effect ──
  window.addEventListener("scroll", () => {
    if (!header) return;
    if (window.scrollY > 40) {
      header.classList.add("scrolled");
      if (topBar && window.innerWidth > 1024) {
        topBar.style.marginTop = "-40px";
        topBar.style.opacity = "0";
      }
    } else {
      header.classList.remove("scrolled");
      if (topBar) {
        topBar.style.marginTop = "0";
        topBar.style.opacity = "1";
      }
    }
  });

  // ── 2. Mobile Drawer Toggle ──
  window.toggleDrawer = function () {
    if (!drawer || !overlay) return;
    drawer.classList.toggle("active");
    overlay.classList.toggle("active");
    document.body.style.overflow = drawer.classList.contains("active")
      ? "hidden"
      : "";
  };

  // ── 3. Mobile Dropdown Toggle ──
  window.toggleMobDrop = function () {
    const drop = document.getElementById("mobDrop");
    const plus = document.getElementById("plus");
    if (!drop || !plus) return;
    drop.classList.toggle("show");
    plus.textContent = drop.classList.contains("show") ? "-" : "+";
  };
// ── reCAPTCHA v2 (Get a Quote forms) — same site key as the admin login ──
  const RECAPTCHA_SITE_KEY = "6LeHo-csAAAAAA-RUUtC-xmee5YqI4LMF75xMXuV";
  let recaptchaApiPromise = null;
  let quoteRecaptchaWidgetId = null;

  function loadRecaptchaApi() {
    if (recaptchaApiPromise) return recaptchaApiPromise;
    recaptchaApiPromise = new Promise((resolve) => {
      window.__onRecaptchaApiLoad = function () { resolve(window.grecaptcha); };
      const script = document.createElement("script");
      script.src = "https://www.google.com/recaptcha/api.js?onload=__onRecaptchaApiLoad&render=explicit";
      script.async = true;
      script.defer = true;
      document.head.appendChild(script);
    });
    return recaptchaApiPromise;
  }

  function renderQuoteRecaptcha() {
    loadRecaptchaApi().then((grecaptcha) => {
      const container = document.getElementById("quoteRecaptcha");
      if (!container) return;
      if (quoteRecaptchaWidgetId === null) {
        quoteRecaptchaWidgetId = grecaptcha.render("quoteRecaptcha", { sitekey: RECAPTCHA_SITE_KEY });
      } else {
        grecaptcha.reset(quoteRecaptchaWidgetId);
      }
    });
  }
  // ── 4. Quote Modal Open ──
   window.openQuoteModal = function (product) {
    const modal = document.getElementById("quoteModal");
    if (!modal) return;
    const productField = modal.querySelector('select[name="product"]');
    if (productField) {
      let match = Array.from(productField.options).find((opt) => opt.value === product || opt.text === product);
      if (!match && product) {
        match = document.createElement('option');
        match.value = product;
        match.textContent = product;
        productField.appendChild(match);
      }
      if (match) {
        productField.value = match.value;
      }
    }

    modal.classList.add("active");
    document.body.style.overflow = "hidden";
    renderQuoteRecaptcha();   // ← added

    fetch("csrf-token.php")
      .then((r) => r.json())
      .then((data) => {
        const tokenField = document.getElementById("quoteCsrfToken");
        if (tokenField && data.csrf_token) tokenField.value = data.csrf_token;
      })
      .catch((e) => console.error("CSRF token load error:", e));
  };

  // ── 5. Quote Modal Close ──
  window.closeQuoteModal = function () {
    const modal = document.getElementById("quoteModal");
    if (!modal) return;
    modal.classList.remove("active");
    document.body.style.overflow = "auto";
  };

  // ── 6. Close modal on outside click ──
  window.addEventListener("click", function (event) {
    const modal = document.getElementById("quoteModal");
    if (modal && event.target === modal) {
      closeQuoteModal();
    }
  });

  // ── 7. Quote Form Submit ──
  // ── 7. Quote Form Submit ──
  const form = document.getElementById("quoteForm");
  if (form) {
    form.onsubmit = function (e) {
      e.preventDefault();

      const msgBox = document.getElementById("quoteFormMsg");
      const submitBtn = document.getElementById("quoteSubmitBtn");
      const honeypot = document.getElementById("quoteHoneypot");

      if (honeypot && honeypot.value !== "") return; // silently drop bots

      const captchaOk = window.grecaptcha && quoteRecaptchaWidgetId !== null && grecaptcha.getResponse(quoteRecaptchaWidgetId);
      if (!captchaOk) {
        if (msgBox) {
          msgBox.style.display = "block";
          msgBox.style.color = "#c0392b";
          msgBox.textContent = "Please complete the \"I'm not a robot\" check.";
        }
        return;
      }

      submitBtn.disabled = true;
      submitBtn.textContent = "Sending...";

      fetch("quote-submit.php", { method: "POST", body: new FormData(form) })
        .then((r) => r.json())
        .then((data) => {
          if (msgBox) {
            msgBox.style.display = "block";
            msgBox.style.color = data.success ? "#1a7a3a" : "#c0392b";
            msgBox.textContent = data.message || (data.success ? "Sent!" : "Something went wrong.");
          }
          if (data.success) {
            form.reset();
            setTimeout(() => {
              closeQuoteModal();
              if (msgBox) msgBox.style.display = "none";
            }, 1800);
          }
        })
        .catch(() => {
          if (msgBox) {
            msgBox.style.display = "block";
            msgBox.style.color = "#c0392b";
            msgBox.textContent = "Something went wrong. Please try again.";
          }
        })
        .finally(() => {
          submitBtn.disabled = false;
          submitBtn.textContent = "Send Inquiry";
          if (window.grecaptcha && quoteRecaptchaWidgetId !== null) grecaptcha.reset(quoteRecaptchaWidgetId);
        });
    };
  }

  // ── 8. Sticky navbar on scroll (is-sticky-active class) ──
  // For pages that use .navbar-header instead of #mainHeader
  const navbarContainer = document.getElementById("navbar-header");
  if (navbarContainer) {
    window.addEventListener("scroll", () => {
      const h = navbarContainer.querySelector(".navbar-header");
      if (h) {
        h.classList.toggle("is-sticky-active", window.scrollY > 50);
      }
    });
  }

  // ── 9. Dynamic "Our Products" navbar menu ──
  fetch("php-admin/navbar-data.php")
    .then((r) => {
      if (!r.ok) throw new Error(`Navbar data failed: ${r.status}`);
      return r.json();
    })
    .then((menu) => {
      const desktopList = document.getElementById("productsDropdownMenu");
      const mobileList = document.getElementById("mobDrop");
      if (!Array.isArray(menu) || menu.length === 0) return;

      const menuItems = Array.isArray(menu) ? menu : [];
      const renderMenuItem = (item) => {
        if (!item || !item.url || !item.name) return '';
        return `<li><a href="${item.url}" class="dropdown-link">${item.name}</a></li>`;
      };

      if (desktopList) {
        desktopList.innerHTML = menuItems.map(renderMenuItem).join("");
      }

      if (mobileList) {
        mobileList.innerHTML = menuItems.map(renderMenuItem).join("");
      }
    })
    .catch((e) => console.error("Navbar menu load error:", e));
});
