(function () {
  const FORM_ENDPOINT = "https://formsubmit.co/amrpackwell@gmail.com";
  const SUBMIT_FUNCTIONS = [
    "submitQuote",
    "submitInquiry",
    "submitModalInquiry",
    "handleSubmit",
  ];

  // Forms handled by local PHP endpoints (quote-submit / contact-submit / sms-submit).
  // formsubmit must never intercept these or admin panels stay empty.
  const BACKEND_FORM_IDS = new Set([
    "quoteForm",
    "mainContactForm",
    "smsForm",
  ]);

  function isBackendManagedForm(form) {
    if (!(form instanceof HTMLFormElement)) return false;

    if (form.dataset.localSubmit === "1" || form.dataset.noFormsubmit === "1") {
      return true;
    }

    if (form.id && BACKEND_FORM_IDS.has(form.id)) return true;

    const action = (form.getAttribute("action") || "").trim();
    if (!action || action === "#") return false;

    // Absolute formsubmit URLs are managed by this script.
    if (/formsubmit\.co/i.test(action)) return false;

    // Relative or same-site .php actions are local backends.
    if (/\.php(\?|#|$)/i.test(action)) return true;

    return false;
  }

  function normalizeName(value, fallback) {
    const source = (value || fallback || "field").toString().trim().toLowerCase();
    return source
      .replace(/[*:/()]+/g, "")
      .replace(/[^a-z0-9]+/g, "_")
      .replace(/^_+|_+$/g, "")
      .slice(0, 50) || "field";
  }

  function getLabelText(field) {
    if (field.id) {
      const label = document.querySelector(`label[for="${field.id}"]`);
      if (label) return label.textContent.trim();
    }

    const wrapper = field.closest("div");
    const label = wrapper ? wrapper.querySelector("label") : null;
    if (label) return label.textContent.trim();

    let previous = field.previousElementSibling;
    while (previous) {
      if (previous.tagName && previous.tagName.toLowerCase() === "label") {
        return previous.textContent.trim();
      }
      previous = previous.previousElementSibling;
    }

    return "";
  }

  function fieldName(field) {
    return normalizeName(
      field.name ||
        field.id ||
        getLabelText(field) ||
        field.getAttribute("placeholder") ||
        field.type,
      "field"
    );
  }

  function getFields(scope) {
    return Array.from(scope.querySelectorAll("input, select, textarea")).filter((field) => {
      const type = (field.getAttribute("type") || "").toLowerCase();
      return !["button", "submit", "reset", "hidden"].includes(type) && !field.disabled;
    });
  }

  function isLikelyRequired(field, forceAll) {
    if (field.required || forceAll) return true;

    const id = (field.id || "").toLowerCase();
    const name = (field.name || "").toLowerCase();
    const label = getLabelText(field).toLowerCase();
    const placeholder = (field.getAttribute("placeholder") || "").toLowerCase();
    const text = `${id} ${name} ${label} ${placeholder}`;

    return (
      label.includes("*") ||
      text.includes("mobile") ||
      text.includes("phone") ||
      text.includes("contact") ||
      text.includes("your name") ||
      text.includes("full name")
    );
  }

  function validateField(field, forceAll) {
    const value = field.value.trim();
    const required = isLikelyRequired(field, forceAll);
    const type = (field.getAttribute("type") || "").toLowerCase();
    const label = getLabelText(field).replace("*", "").trim() || field.getAttribute("placeholder") || "This field";

    field.required = required;

    if (required && !value) {
      return `${label} is required.`;
    }

    if (value && type === "email" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
      return "Please enter a valid email address.";
    }

    if (value && type === "tel") {
      const digits = value.replace(/\D/g, "");
      if (digits.length < 7 || digits.length > 15) {
        return "Please enter a valid phone number.";
      }
    }

    return "";
  }

  function alertValidation(message, field) {
    alert(message);
    if (field && typeof field.focus === "function") field.focus();
  }

  function sourceTitle(scope, trigger) {
    const title =
      scope.querySelector("[id*='modal-title'], [id*='modal-product-name'], h1, h2, h3") ||
      document.querySelector("h1") ||
      trigger;
    return (title && title.textContent ? title.textContent : document.title).trim();
  }

  function appendHidden(form, name, value) {
    const input = document.createElement("input");
    input.type = "hidden";
    input.name = name;
    input.value = value;
    form.appendChild(input);
  }

  function submitToFormSubmit(scope, trigger, options) {
    const forceAll = Boolean(options && options.forceAll);
    const fields = getFields(scope);

    for (const field of fields) {
      const error = validateField(field, forceAll);
      if (error) {
        alertValidation(error, field);
        return false;
      }
    }

    const outbound = document.createElement("form");
    outbound.action = FORM_ENDPOINT;
    outbound.method = "POST";
    outbound.style.display = "none";

    appendHidden(outbound, "_subject", `AMR Packwell Website Inquiry - ${sourceTitle(scope, trigger)}`);
    appendHidden(outbound, "_template", "table");
    appendHidden(outbound, "_captcha", "false");
    appendHidden(outbound, "_next", `${window.location.origin}${window.location.pathname}?submitted=success`);
    appendHidden(outbound, "page_url", window.location.href);
    appendHidden(outbound, "page_title", document.title);

    fields.forEach((field) => {
      const value = field.value.trim();
      if (!value) return;
      appendHidden(outbound, fieldName(field), value);
    });

    document.body.appendChild(outbound);
    HTMLFormElement.prototype.submit.call(outbound);
    return true;
  }

  function configureForm(form) {
    if (isBackendManagedForm(form)) return;

    form.action = FORM_ENDPOINT;
    form.method = "POST";
    getFields(form).forEach((field) => {
      if (!field.name) field.name = fieldName(field);
      if (isLikelyRequired(field, false)) field.required = true;
    });
  }

  function configureAllForms(root) {
    (root || document).querySelectorAll("form").forEach(configureForm);
  }

  function closestSubmissionScope(trigger) {
    return (
      trigger.closest("form") ||
      trigger.closest("#quoteModal, #inquiry-modal") ||
      trigger.closest(".modal-body") ||
      trigger.closest(".shadow-card, .shadow-lg, .rounded-3xl, .rounded-2xl") ||
      trigger.parentElement ||
      document.body
    );
  }

  function buttonIntent(button) {
    const onclick = button.getAttribute("onclick") || "";
    const text = (button.textContent || button.value || "").toLowerCase();
    if (SUBMIT_FUNCTIONS.some((name) => onclick.includes(`${name}(`))) return "configured";
    if (/request free assessment|subscribe|send sms/i.test(text)) return "generic";
    return "";
  }

  document.addEventListener(
    "submit",
    function (event) {
      const form = event.target;
      if (!(form instanceof HTMLFormElement)) return;

      // Let quote / contact / SMS (and any other local PHP form) use their own handlers.
      if (isBackendManagedForm(form)) return;

      if (!getFields(form).length) return;

      event.preventDefault();
      event.stopImmediatePropagation();
      configureForm(form);
      submitToFormSubmit(form, form, { forceAll: false });
    },
    true
  );

  document.addEventListener(
    "click",
    function (event) {
      const button = event.target.closest("button, input[type='submit']");
      if (!button) return;

      const parentForm = button.closest("form");
      if (parentForm) {
        // Backend forms handle their own submit buttons.
        if (isBackendManagedForm(parentForm)) return;
        return;
      }

      const intent = buttonIntent(button);
      if (!intent) return;

      const scope = closestSubmissionScope(button);
      // If the button is inside the quote modal (or another backend form area), do not hijack.
      const scopedForm = scope.querySelector ? scope.querySelector("form") : null;
      if (scopedForm && isBackendManagedForm(scopedForm)) return;
      if (scope.id === "quoteModal" || (scope.closest && scope.closest("#quoteModal"))) return;

      if (!getFields(scope).length) return;

      event.preventDefault();
      event.stopImmediatePropagation();
      submitToFormSubmit(scope, button, { forceAll: intent === "generic" });
    },
    true
  );

  document.addEventListener("DOMContentLoaded", function () {
    configureAllForms(document);

    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (node.nodeType === 1) configureAllForms(node);
        });
      });
    });

    observer.observe(document.documentElement, { childList: true, subtree: true });
  });

  window.AMRFormSubmit = {
    endpoint: FORM_ENDPOINT,
    configureAllForms,
    submitToFormSubmit,
    isBackendManagedForm,
  };
})();
