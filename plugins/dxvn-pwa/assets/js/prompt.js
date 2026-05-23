(function () {
  if (!window.dxvnPwaData || !dxvnPwaData.enabled) {
    return;
  }

  var deferredPrompt = null;
  var promptEl = null;
  var storageKey = "dxvn_pwa_prompt_dismissed_until";
  var foreverKey = "dxvn_pwa_prompt_hidden_forever";

  function safeStorageGet(key) {
    try {
      return window.localStorage.getItem(key);
    } catch (e) {
      return "";
    }
  }

  function safeStorageSet(key, value) {
    try {
      window.localStorage.setItem(key, value);
    } catch (e) {
      // no-op
    }
  }

  function isIos() {
    return /iphone|ipad|ipod/i.test(navigator.userAgent);
  }

  function isInStandaloneMode() {
    return window.matchMedia("(display-mode: standalone)").matches || window.navigator.standalone === true;
  }

  function canShowPrompt() {
    if (isInStandaloneMode()) return false;
    if (safeStorageGet(foreverKey) === "1") return false;

    var hiddenUntil = parseInt(safeStorageGet(storageKey) || "0", 10);
    if (hiddenUntil && hiddenUntil > Date.now()) return false;

    return true;
  }

  function dismiss(days, forever) {
    if (forever) {
      safeStorageSet(foreverKey, "1");
    } else {
      var until = Date.now() + days * 24 * 60 * 60 * 1000;
      safeStorageSet(storageKey, String(until));
    }
    if (promptEl) {
      promptEl.remove();
      promptEl = null;
    }
  }

  function buildPrompt(isIosGuide) {
    var copy = dxvnPwaData.copy || {};
    var shell = document.createElement("div");
    shell.className = "dxvn-pwa-prompt";

    var body = document.createElement("div");
    body.className = "dxvn-pwa-prompt__body";

    var title = document.createElement("div");
    title.className = "dxvn-pwa-prompt__title";
    title.textContent = isIosGuide ? copy.iosTitle : copy.title;

    var desc = document.createElement("p");
    desc.className = "dxvn-pwa-prompt__desc";

    if (isIosGuide) {
      desc.innerHTML =
        "<span>1. " + copy.iosStepOne + "</span>" +
        "<span>2. " + copy.iosStepTwo + "</span>" +
        "<span>3. " + copy.iosStepThree + "</span>";
    } else {
      desc.textContent = copy.description;
    }

    var actions = document.createElement("div");
    actions.className = "dxvn-pwa-prompt__actions";

    var primary = document.createElement("button");
    primary.type = "button";
    primary.className = "dxvn-pwa-prompt__btn dxvn-pwa-prompt__btn--primary";
    primary.textContent = isIosGuide ? "Đã hiểu" : copy.installCta;

    var later = document.createElement("button");
    later.type = "button";
    later.className = "dxvn-pwa-prompt__btn dxvn-pwa-prompt__btn--ghost";
    later.textContent = copy.laterCta;

    var hide = document.createElement("button");
    hide.type = "button";
    hide.className = "dxvn-pwa-prompt__link";
    hide.textContent = copy.dismissCta;

    primary.addEventListener("click", async function () {
      if (isIosGuide) {
        dismiss(dxvnPwaData.promptCooldown || 7, false);
        return;
      }

      if (!deferredPrompt) {
        dismiss(dxvnPwaData.promptCooldown || 7, false);
        return;
      }

      deferredPrompt.prompt();
      try {
        await deferredPrompt.userChoice;
      } catch (e) {
        // no-op
      }
      deferredPrompt = null;
      dismiss(dxvnPwaData.promptCooldown || 7, false);
    });

    later.addEventListener("click", function () {
      dismiss(dxvnPwaData.promptCooldown || 7, false);
    });

    hide.addEventListener("click", function () {
      dismiss(dxvnPwaData.promptCooldown || 7, true);
    });

    body.appendChild(title);
    body.appendChild(desc);
    actions.appendChild(primary);
    actions.appendChild(later);
    body.appendChild(actions);
    body.appendChild(hide);
    shell.appendChild(body);
    return shell;
  }

  function showPrompt(isIosGuide) {
    if (!canShowPrompt() || promptEl) return;
    promptEl = buildPrompt(isIosGuide);
    document.body.appendChild(promptEl);
  }

  window.addEventListener("beforeinstallprompt", function (event) {
    if (!dxvnPwaData.androidEnabled) return;
    event.preventDefault();
    deferredPrompt = event;

    window.setTimeout(function () {
      showPrompt(false);
    }, Number(dxvnPwaData.promptDelay || 4500));
  });

  window.addEventListener("appinstalled", function () {
    dismiss(365, true);
  });

  if ("serviceWorker" in navigator) {
    window.addEventListener("load", function () {
      navigator.serviceWorker.register(dxvnPwaData.serviceWorkerUrl).catch(function () {
        // no-op
      });
    });
  }

  window.addEventListener("load", function () {
    if (!canShowPrompt()) return;
    if (!isIos() || !dxvnPwaData.iosEnabled) return;

    window.setTimeout(function () {
      showPrompt(true);
    }, Number(dxvnPwaData.promptDelay || 4500));
  });
})();
