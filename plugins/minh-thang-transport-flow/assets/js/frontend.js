(function () {
  function getSafeTarget(event) {
    var target = event.target;
    if (target && target.nodeType === 3) {
      return target.parentElement;
    }
    return target;
  }

  function isMobileViewport() {
    return (
      window.innerWidth <= 768 ||
      window.matchMedia("(pointer: coarse)").matches ||
      /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent)
    );
  }

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
      // Ignore storage errors (private mode / blocked storage).
    }
  }

  function getQueryParams() {
    var params = new URLSearchParams(window.location.search);
    var keys = [
      "utm_source",
      "utm_medium",
      "utm_campaign",
      "utm_content",
      "utm_term",
      "gclid",
      "fbclid",
      "ttclid",
      "route",
    ];
    var out = {};
    keys.forEach(function (key) {
      var value = params.get(key);
      if (value) {
        out[key] = value;
      }
    });
    return out;
  }

  function setTouchData() {
    var params = getQueryParams();
    if (!Object.keys(params).length) {
      return;
    }

    var first = safeStorageGet("mttf_first_touch");
    if (!first) {
      safeStorageSet("mttf_first_touch", JSON.stringify(params));
    }

    safeStorageSet("mttf_last_touch", JSON.stringify(params));
  }

  function trackClick(el) {
    if (!el || !window.dataLayer) {
      return;
    }

    var card = el.closest(".mttf-card");
    var page = el.closest(".mttf-route-page");
    window.dataLayer.push({
      event: "mttf_" + (el.dataset.trackEvent || "click"),
      cta_label: el.dataset.trackLabel || (el.textContent || "").trim(),
      page_type: (page && page.dataset.pageType) || "",
      route_id: (card && card.dataset.routeId) || "",
      route_slug: (card && card.dataset.routeSlug) || "",
      operator_id: (card && card.dataset.operatorId) || (page && page.dataset.operatorId) || "",
      operator_slug: (card && card.dataset.operatorSlug) || (page && page.dataset.operatorSlug) || "",
    });
  }

  function initLeadCaptureForm(form, options) {
    if (!form) return null;

    var config = options || {};
    var statusEl = config.statusEl || form.querySelector("[data-mttf-status]");
    var intlToggleBtn = config.intlToggleBtn || form.querySelector("[data-mttf-intl-toggle]");
    var intlFields = config.intlFields || form.querySelector("[data-mttf-intl-fields]");
    var submitBtn = form.querySelector('button[type="submit"]');
    var submitDefaultText = submitBtn ? submitBtn.textContent : "Gửi";
    var routeSelect = form.querySelector("[data-mttf-route-select]");
    var cooldownTimer = null;
    var cooldownKey = config.cooldownKey || "mttf_lead_cooldown_until";

    function setFieldValue(name, value) {
      var field = form.querySelector('[name="' + name + '"]');
      if (field) {
        field.value = value || "";
      }
    }

    function getCooldownLeftSeconds() {
      var until = parseInt(safeStorageGet(cooldownKey) || "0", 10);
      if (!until || Number.isNaN(until)) return 0;
      var left = Math.ceil((until - Date.now()) / 1000);
      return left > 0 ? left : 0;
    }

    function renderCooldown() {
      if (!submitBtn) return;
      var left = getCooldownLeftSeconds();
      if (left > 0) {
        submitBtn.disabled = true;
        submitBtn.textContent = "Bạn có thể gửi lại sau " + left + "s";
      } else {
        submitBtn.disabled = false;
        submitBtn.textContent = submitDefaultText;
        safeStorageSet(cooldownKey, "0");
        if (cooldownTimer) {
          clearInterval(cooldownTimer);
          cooldownTimer = null;
        }
      }
    }

    function setCooldown(seconds) {
      var normalized = parseInt(seconds || 0, 10);
      if (!normalized || Number.isNaN(normalized) || normalized <= 0) {
        safeStorageSet(cooldownKey, "0");
        renderCooldown();
        return;
      }
      safeStorageSet(cooldownKey, String(Date.now() + normalized * 1000));
      renderCooldown();
      if (!cooldownTimer) {
        cooldownTimer = setInterval(renderCooldown, 1000);
      }
    }

    function setStatus(message, type) {
      if (!statusEl) return;
      statusEl.textContent = message || "";
      statusEl.classList.remove("is-success", "is-error", "is-loading");
      if (type === "success") statusEl.classList.add("is-success");
      if (type === "error") statusEl.classList.add("is-error");
      if (type === "loading") statusEl.classList.add("is-loading");
    }

    function setInternationalFieldsVisible(visible) {
      var shouldShow = !!visible;
      if (intlToggleBtn) {
        intlToggleBtn.setAttribute("aria-pressed", shouldShow ? "true" : "false");
      }
      if (intlFields) {
        intlFields.hidden = !shouldShow;
      }
      if (!shouldShow) {
        form.querySelectorAll('input[name="contact_apps[]"]').forEach(function (el) {
          el.checked = false;
        });
      }
    }

    function syncSelectedRoute() {
      if (!routeSelect) return;
      var selectedOption = routeSelect.options[routeSelect.selectedIndex];
      if (!selectedOption) return;

      if (
        selectedOption.dataset.routeId ||
        selectedOption.dataset.routeTitle ||
        selectedOption.dataset.routeSlug ||
        selectedOption.dataset.routeRegion
      ) {
        setFieldValue("route_id", selectedOption.dataset.routeId || selectedOption.value || "");
        setFieldValue("route_title", selectedOption.dataset.routeTitle || selectedOption.textContent || "");
        setFieldValue("route_slug", selectedOption.dataset.routeSlug || "");
        setFieldValue("route_region", selectedOption.dataset.routeRegion || "");
      }

      if (
        selectedOption.dataset.operatorId ||
        selectedOption.dataset.operatorName ||
        selectedOption.dataset.operatorSlug
      ) {
        setFieldValue("operator_id", selectedOption.dataset.operatorId || "");
        setFieldValue("operator_name", selectedOption.dataset.operatorName || selectedOption.textContent || "");
        setFieldValue("operator_slug", selectedOption.dataset.operatorSlug || "");
      }

      if (typeof config.onRouteChange === "function") {
        config.onRouteChange(selectedOption);
      }
    }

    if (routeSelect) {
      routeSelect.addEventListener("change", syncSelectedRoute);
    }

    if (intlToggleBtn) {
      intlToggleBtn.addEventListener("click", function () {
        var isOn = intlToggleBtn.getAttribute("aria-pressed") === "true";
        setInternationalFieldsVisible(!isOn);
      });
    }

    form.addEventListener("submit", function (event) {
      event.preventDefault();
      syncSelectedRoute();
      if (getCooldownLeftSeconds() > 0) {
        setStatus("Bạn đang trong thời gian chờ, vui lòng thử lại sau.", "error");
        renderCooldown();
        return;
      }

      if (!form.querySelector('[name="route_id"]') || !form.querySelector('[name="route_id"]').value) {
        setStatus("Vui lòng chọn tuyến cần tư vấn.", "error");
        return;
      }

      setStatus("Đang gửi...", "loading");
      if (submitBtn) submitBtn.disabled = true;

      var payload = new FormData(form);
      payload.append("action", "mttf_capture_lead");
      payload.append("nonce", (window.mttfData && window.mttfData.nonce) || "");
      payload.append("source_page", window.location.href);
      payload.append("first_touch", safeStorageGet("mttf_first_touch") || "");
      payload.append("last_touch", safeStorageGet("mttf_last_touch") || "");

      var params = getQueryParams();
      Object.keys(params).forEach(function (key) {
        payload.append(key, params[key]);
      });

      fetch((window.mttfData && window.mttfData.ajaxUrl) || "", {
        method: "POST",
        body: payload,
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (json) {
          if (!json.success) {
            setStatus((json.data && json.data.message) || "Gửi thất bại.", "error");
            if (json.data && json.data.retry_after) {
              setCooldown(json.data.retry_after);
            } else if (submitBtn) {
              submitBtn.disabled = false;
            }
            return;
          }

          setStatus(
            "Chuyên viên của chúng tôi sẽ gọi lại cho bạn ngay. Vui lòng để chuông điện thoại!",
            "success"
          );
          if (form.phone) {
            form.phone.value = "";
          }
          var cooldownSeconds =
            json &&
            json.data &&
            typeof json.data.cooldown_seconds !== "undefined"
              ? parseInt(json.data.cooldown_seconds, 10)
              : 45;
          setCooldown(cooldownSeconds);

          if (
            typeof window.__mttfApplyActivityPing === "function" &&
            json &&
            json.data &&
            json.data.activity_ping &&
            json.data.activity_ping.message
          ) {
            window.__mttfApplyActivityPing(json.data.activity_ping);
          }

          var mMeas = window.mttfData && window.mttfData.measurement;
          if (window.dataLayer && mMeas && parseInt(mMeas.enabled, 10) === 1) {
            var evName =
              (mMeas.eventName &&
                String(mMeas.eventName).replace(/^\s+|\s+$/g, "")) ||
              "mttf_lead_submit";
            var routeIdField = form.querySelector('[name="route_id"]');
            var routeSlugField = form.querySelector('[name="route_slug"]');
            var basePayload = {
              event: evName,
              route_id: routeIdField ? routeIdField.value : "",
              route_slug: routeSlugField ? routeSlugField.value : "",
            };
            window.dataLayer.push(basePayload);
            if (parseInt(mMeas.duplicateGa4, 10) === 1) {
              window.dataLayer.push({
                event: "generate_lead",
                route_id: basePayload.route_id,
                route_slug: basePayload.route_slug,
              });
            }
          }

          if (typeof config.onSuccess === "function") {
            config.onSuccess(json);
          }
        })
        .catch(function () {
          setStatus("Không thể gửi lúc này. Thử lại sau.", "error");
          if (submitBtn) submitBtn.disabled = false;
        });
    });

    syncSelectedRoute();
    renderCooldown();
    setInternationalFieldsVisible(false);
    if (getCooldownLeftSeconds() > 0 && !cooldownTimer) {
      cooldownTimer = setInterval(renderCooldown, 1000);
    }

    return {
      setStatus: setStatus,
      renderCooldown: renderCooldown,
      setInternationalFieldsVisible: setInternationalFieldsVisible,
      setCooldown: setCooldown,
      syncSelectedRoute: syncSelectedRoute,
    };
  }

  function initModal() {
    var modal = document.getElementById("mttf-modal");
    if (!modal) return;

    var routeNameEl = modal.querySelector("[data-mttf-route-name]");
    var titleEl = modal.querySelector("[data-mttf-title]");
    var routeImageEl = modal.querySelector("[data-mttf-route-image]");
    var form = modal.querySelector(".mttf-lead-form");
    var formApi = initLeadCaptureForm(form);

    function openFromCard(card) {
      if (!card) return;
      var page = card.closest(".mttf-route-page");
      form.route_id.value = card.dataset.routeId || "";
      form.route_title.value = card.dataset.routeTitle || "";
      form.route_slug.value = card.dataset.routeSlug || "";
      form.route_region.value = card.dataset.routeRegion || "";
      form.operator_id.value =
        card.dataset.operatorId || (page && page.dataset.operatorId) || "";
      form.operator_name.value =
        card.dataset.operatorName || (page && page.dataset.operatorName) || "";
      form.operator_slug.value =
        card.dataset.operatorSlug || (page && page.dataset.operatorSlug) || "";
      form.page_type.value = (page && page.dataset.pageType) || "";
      routeNameEl.textContent = "Tuyến bạn chọn: " + (card.dataset.routeTitle || "");
      if (titleEl) {
        titleEl.textContent = "Bạn cần xe đi " + (card.dataset.routeTitle || "tuyến này") + "?";
      }
      if (routeImageEl) {
        routeImageEl.src = card.dataset.routeImage || "";
        routeImageEl.alt = card.dataset.routeTitle || "Ảnh tuyến xe";
      }
      if (formApi) {
        formApi.setInternationalFieldsVisible(false);
        formApi.setStatus("");
        formApi.renderCooldown();
      }
      modal.hidden = false;
    }

    document.addEventListener("click", function (event) {
      var target = getSafeTarget(event);
      if (!target || !target.closest) {
        return;
      }

      var clickedInsideCard = target.closest(".mttf-card");
      if (!clickedInsideCard) {
        return;
      }

      if (clickedInsideCard.classList.contains("mttf-route-discovery-card")) {
        if (target.closest("a, button, input, select, textarea, label")) {
          return;
        }

        if (clickedInsideCard.dataset.detailUrl) {
          window.location.href = clickedInsideCard.dataset.detailUrl;
        }
        return;
      }

      var modalTrigger = target.closest(".mttf-open-modal");
      if (modalTrigger) {
        event.preventDefault();
        openFromCard(clickedInsideCard);
        return;
      }

      if (
        target.closest(".mttf-card__detail-link") ||
        target.closest(".mttf-route-discovery-card__media-link") ||
        target.closest(".mttf-route-discovery-card__link") ||
        target.closest(".mttf-operator-card__link") ||
        target.closest(".mttf-route-operator-card__link")
      ) {
        return;
      }

      if (target.closest(".mttf-btn")) {
        return;
      }

      if (target.closest("#mttf-modal")) {
        return;
      }

      openFromCard(clickedInsideCard);
    });

    document.querySelectorAll(".mttf-close-modal").forEach(function (closer) {
      closer.addEventListener("click", function () {
        modal.hidden = true;
      });
    });
  }

  function initHeroLeadForms() {
    document.querySelectorAll(".mttf-lead-form--hero").forEach(function (form) {
      initLeadCaptureForm(form);
    });
  }

  function initLiveSearch() {
    var searchInput = document.querySelector('.mttf-search input[name="mttf_q"]');
    var tracks = document.querySelectorAll(".mttf-hub__track");
    if (!searchInput || !tracks.length) return;
    var root = document.querySelector(".mttf");
    var suggestBox = document.querySelector(".mttf-suggest");
    var suggestList = suggestBox ? suggestBox.querySelector(".mttf-suggest__list") : null;
    var pinnedRouteId = "";
    var shouldClearSearchOnNextClick = false;
    var trackedSearches = {};
    function trackSearchSelection(routeId, keyword) {
      if (!routeId || !window.mttfData || !window.mttfData.ajaxUrl || !window.mttfData.trackSearchNonce) {
        return;
      }

      var normalizedKeyword = normalizeText(keyword || "");
      var storageKey = String(routeId) + "::" + normalizedKeyword;
      if (trackedSearches[storageKey]) {
        return;
      }
      trackedSearches[storageKey] = true;

      var payload = new FormData();
      payload.append("action", "mttf_track_route_search");
      payload.append("nonce", window.mttfData.trackSearchNonce);
      payload.append("route_id", routeId);
      payload.append("keyword", normalizedKeyword);

      fetch(window.mttfData.ajaxUrl, {
        method: "POST",
        body: payload,
      }).catch(function () {
        trackedSearches[storageKey] = false;
      });
    }


    var originalOrder = new Map();
    tracks.forEach(function (track) {
      originalOrder.set(track, Array.prototype.slice.call(track.children));
    });

    function resetOrder() {
      tracks.forEach(function (track) {
        var cards = originalOrder.get(track) || [];
        cards.forEach(function (card) {
          track.appendChild(card);
        });
      });
    }

    function pinRouteToTop(routeId) {
      if (!routeId || !root) return;
      var card = root.querySelector('.mttf-card[data-route-id="' + routeId + '"]');
      if (!card) return;

      var section = card.closest(".mttf-hub");
      var track = card.closest(".mttf-hub__track");
      if (!section || !track) return;

      track.insertBefore(card, track.firstChild);
      // Ensure the first card is visible immediately on horizontal tracks.
      if (typeof track.scrollTo === "function") {
        track.scrollTo({ left: 0, behavior: isMobileViewport() ? "auto" : "smooth" });
      } else {
        track.scrollLeft = 0;
      }
      var firstSection = root.querySelector(".mttf-hub");
      if (firstSection && firstSection !== section) {
        root.insertBefore(section, firstSection);
      }
    }

    function reorderByMatches(routeIds) {
      if (!routeIds || !routeIds.length) {
        resetOrder();
        return;
      }

      var idSet = {};
      routeIds.forEach(function (id) {
        idSet[String(id)] = true;
      });

      tracks.forEach(function (track) {
        var cards = Array.prototype.slice.call(track.children);
        var matched = [];
        var others = [];

        cards.forEach(function (card) {
          var routeId = card.dataset.routeId || "";
          if (idSet[routeId]) {
            matched.push(card);
          } else {
            others.push(card);
          }
        });

        matched.concat(others).forEach(function (card) {
          track.appendChild(card);
        });
      });
    }

    function normalizeText(value) {
      if (!value) return "";
      try {
        return value
          .toLowerCase()
          .normalize("NFD")
          .replace(/[\u0300-\u036f]/g, "")
          .trim();
      } catch (e) {
        return String(value).toLowerCase().trim();
      }
    }

    var isMobileInput =
      window.matchMedia("(pointer: coarse)").matches ||
      /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent);
    var minCharsLocal = isMobileInput ? 1 : 2;

    function scoreTitleForNeedle(title, needle) {
      var words = title.split(/\s+/).filter(Boolean);
      var startsWithWord = words.some(function (word) {
        return word.indexOf(needle) === 0;
      });
      if (startsWithWord) return 3;

      var wordBoundaryMatch = new RegExp("(^|\\s)" + needle).test(title);
      if (wordBoundaryMatch) return 2;

      if (title.indexOf(needle) !== -1) return 1;
      return 0;
    }

    function reorderByKeywordLocal(keyword) {
      var needle = normalizeText(keyword);
      if (needle.length < minCharsLocal) {
        resetOrder();
        return;
      }

      var sectionScores = [];

      tracks.forEach(function (track) {
        var cards = Array.prototype.slice.call(track.children);
        var matched = [];
        var others = [];
        var bestScore = 0;

        cards.forEach(function (card) {
          // When searching, force all cards visible so matched routes can surface.
          card.style.display = "";
          var title = normalizeText(card.dataset.routeTitle || "");
          var score = scoreTitleForNeedle(title, needle);
          if (score > bestScore) bestScore = score;
          if (score > 0) {
            matched.push({
              card: card,
              score: score,
              title: title,
            });
          } else {
            others.push(card);
          }
        });

        matched
          .sort(function (a, b) {
            if (b.score !== a.score) return b.score - a.score;
            return a.title.length - b.title.length;
          })
          .map(function (item) {
            return item.card;
          })
          .concat(others)
          .forEach(function (card) {
            track.appendChild(card);
        });

        sectionScores.push({
          section: track.closest(".mttf-hub"),
          score: bestScore,
        });
      });

      // Move the section with strongest match to the top.
      var root = document.querySelector(".mttf");
      if (root && sectionScores.length) {
        sectionScores
          .filter(function (row) {
            return row.section;
          })
          .sort(function (a, b) {
            return b.score - a.score;
          });

        var best = sectionScores[0];
        if (best && best.section && best.score > 0) {
          var firstSection = root.querySelector(".mttf-hub");
          if (firstSection && firstSection !== best.section) {
            root.insertBefore(best.section, firstSection);
          }
        }
      }
    }

    function getSuggestionCandidates(keyword) {
      var needle = normalizeText(keyword);
      if (needle.length < 2) return [];

      var seen = {};
      var rows = [];
      tracks.forEach(function (track) {
        Array.prototype.slice.call(track.children).forEach(function (card) {
          var titleRaw = card.dataset.routeTitle || "";
          var title = normalizeText(titleRaw);
          var score = scoreTitleForNeedle(title, needle);
          if (score <= 0) return;

          var key = title;
          if (!seen[key] || seen[key].score < score) {
            seen[key] = {
              titleRaw: titleRaw,
              score: score,
              normTitle: title,
              routeId: card.dataset.routeId || "",
            };
          }
        });
      });

      Object.keys(seen).forEach(function (key) {
        rows.push(seen[key]);
      });

      rows.sort(function (a, b) {
        if (b.score !== a.score) return b.score - a.score;
        return a.normTitle.length - b.normTitle.length;
      });

      return rows.slice(0, 6);
    }

    function getFeaturedCandidates() {
      if (!root) return [];
      var out = [];
      var seen = {};
      Array.prototype.slice
        .call(root.querySelectorAll(".mttf-card"))
        .forEach(function (card) {
          if (out.length >= 5) return;
          var routeId = card.dataset.routeId || "";
          var titleRaw = (card.dataset.routeTitle || "").trim();
          if (!routeId || !titleRaw) return;
          if (seen[routeId]) return;
          seen[routeId] = true;
          out.push({ titleRaw: titleRaw, routeId: routeId });
        });
      return out;
    }

    function hideSuggestions() {
      if (!suggestBox || !suggestList) return;
      suggestList.innerHTML = "";
      suggestBox.hidden = true;
    }

    function scrollToFirstResultCard() {
      if (!root) return;
      if (isMobileViewport()) return;
      var firstVisibleCard = root.querySelector('.mttf-hub .mttf-hub__track .mttf-card:not([style*="display: none"])');
      if (!firstVisibleCard) return;

      var section = firstVisibleCard.closest(".mttf-hub");
      var target = section || firstVisibleCard;
      target.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    function showSuggestions(keyword) {
      if (!suggestBox || !suggestList) return;
      var trimmed = (keyword || "").trim();
      var candidates =
        trimmed === "" ? getFeaturedCandidates() : getSuggestionCandidates(trimmed);
      if (!candidates.length) {
        hideSuggestions();
        return;
      }

      suggestList.innerHTML = "";
      candidates.forEach(function (item) {
        var li = document.createElement("li");
        var btn = document.createElement("button");
        btn.type = "button";
        btn.className = "mttf-suggest__item-btn";
        btn.textContent = item.titleRaw;
        btn.addEventListener("click", function () {
          pinnedRouteId = item.routeId || "";
          searchInput.value = item.titleRaw;
          shouldClearSearchOnNextClick = true;
          reorderByKeywordLocal(item.titleRaw);
          pinRouteToTop(pinnedRouteId);
          trackSearchSelection(pinnedRouteId, item.titleRaw);
          hideSuggestions();
          if (typeof searchInput.blur === "function") {
            searchInput.blur();
          }
          setTimeout(scrollToFirstResultCard, 120);
        });
        li.appendChild(btn);
        suggestList.appendChild(li);
      });
      suggestBox.hidden = false;
    }

    var debounceTimer = null;
    var requestCounter = 0;

    searchInput.addEventListener("input", function () {
      var keyword = (searchInput.value || "").trim();
      pinnedRouteId = "";

      // Reorder immediately on device (especially mobile),
      // then refine with AJAX response.
      reorderByKeywordLocal(keyword);
      pinRouteToTop(pinnedRouteId);
      showSuggestions(keyword);
      if (typeof window.mttfApplyCarTypeFilter === "function") {
        window.mttfApplyCarTypeFilter();
      }

      if (debounceTimer) {
        clearTimeout(debounceTimer);
      }

      debounceTimer = setTimeout(function () {
        if (keyword.length < 2) {
          resetOrder();
          if (keyword.trim() === "") {
            showSuggestions("");
          } else {
            hideSuggestions();
          }
          if (typeof window.mttfApplyCarTypeFilter === "function") {
            window.mttfApplyCarTypeFilter();
          }
          return;
        }

        // Local reorder already handles live sorting instantly on mobile and desktop.
        // Keep debounce block for future extension if server-side search is needed.
      }, 220);
    });

    searchInput.addEventListener("focus", function () {
      var keyword = (searchInput.value || "").trim();
      showSuggestions(keyword);
    });

    searchInput.addEventListener("click", function () {
      if (!shouldClearSearchOnNextClick) return;
      shouldClearSearchOnNextClick = false;
      searchInput.value = "";
      pinnedRouteId = "";
      resetOrder();
      if (typeof window.mttfApplyCarTypeFilter === "function") {
        window.mttfApplyCarTypeFilter();
      }
      hideSuggestions();
    });

    document.addEventListener("click", function (event) {
      var target = getSafeTarget(event);
      if (!target || !target.closest) return;
      if (target.closest(".mttf-search")) return;
      hideSuggestions();
    });
  }

  function initCardSliders() {
    document.querySelectorAll(".mttf-card__media").forEach(function (media) {
      var slides = Array.prototype.slice.call(media.querySelectorAll(".mttf-card__image"));
      if (slides.length <= 1) return;

      var intervalSeconds = parseInt(media.dataset.sliderInterval || "3", 10);
      if (!intervalSeconds || intervalSeconds < 1) intervalSeconds = 3;
      var intervalMs = intervalSeconds * 1000;

      var currentIndex = slides.findIndex(function (slide) {
        return slide.classList.contains("is-active");
      });
      if (currentIndex < 0) currentIndex = 0;

      setInterval(function () {
        slides[currentIndex].classList.remove("is-active");
        currentIndex = (currentIndex + 1) % slides.length;
        slides[currentIndex].classList.add("is-active");
      }, intervalMs);
    });
  }

  function initHeroSlides() {
    document.querySelectorAll(".mttf-directory-hero__media").forEach(function (media) {
      var slides = Array.prototype.slice.call(media.querySelectorAll(".mttf-directory-hero__image"));
      if (slides.length <= 1) return;

      var intervalSeconds = parseInt(media.dataset.heroSlideInterval || "5", 10);
      if (!intervalSeconds || intervalSeconds < 2) intervalSeconds = 5;
      var intervalMs = intervalSeconds * 1000;

      var currentIndex = slides.findIndex(function (slide) {
        return slide.classList.contains("is-active");
      });
      if (currentIndex < 0) currentIndex = 0;

      setInterval(function () {
        slides[currentIndex].classList.remove("is-active");
        currentIndex = (currentIndex + 1) % slides.length;
        slides[currentIndex].classList.add("is-active");
      }, intervalMs);
    });
  }

  function initHeroDescriptionToggles() {
    document.querySelectorAll("[data-mttf-hero-description]").forEach(function (row) {
      var description = row.querySelector(".mttf-directory-hero__description");
      var toggle = row.querySelector("[data-mttf-hero-description-toggle]");
      if (!description || !toggle) return;

      function syncToggleVisibility() {
        var wasExpanded = row.classList.contains("is-expanded");
        if (wasExpanded) {
          row.classList.remove("is-expanded");
        }

        var isOverflowing = description.scrollWidth > description.clientWidth + 1;
        toggle.hidden = !isOverflowing && !wasExpanded;

        if (wasExpanded) {
          row.classList.add("is-expanded");
          toggle.hidden = false;
        }
      }

      toggle.addEventListener("click", function () {
        var isExpanded = row.classList.toggle("is-expanded");
        toggle.setAttribute("aria-expanded", isExpanded ? "true" : "false");
        toggle.textContent = isExpanded ? "Thu gọn" : "Đọc thêm";
        if (!isExpanded) {
          syncToggleVisibility();
        }
      });

      syncToggleVisibility();
      window.addEventListener("resize", syncToggleVisibility);
    });
  }

  function initRoutePageMotion() {
    if (
      !("animate" in Element.prototype) ||
      (window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches)
    ) {
      return;
    }

    var pageRoots = Array.prototype.slice.call(
      document.querySelectorAll(".mttf-route-page--route-detail, .mttf-route-page--operator-detail")
    );
    if (!pageRoots.length) return;

    var enterEase = "cubic-bezier(0.16, 1, 0.3, 1)";
    var softEase = "cubic-bezier(0.22, 1, 0.36, 1)";

    pageRoots.forEach(function (pageRoot) {
      var hero = pageRoot.querySelector(".mttf-directory-hero");
      if (hero && !hero.dataset.motionPlayed) {
        hero.dataset.motionPlayed = "true";

        var overlay = hero.querySelector(".mttf-directory-hero__overlay");
        var media = hero.querySelector(".mttf-directory-hero__media");
        var leadCard = hero.querySelector(".mttf-directory-hero__lead-card");
        var motionTargets = [
          { selector: ".mttf-directory-hero__brand", y: -8, delay: 40, duration: 300 },
          { selector: ".mttf-directory-hero__eyebrow", y: 10, delay: 90, duration: 280 },
          { selector: ".mttf-directory-hero__title", y: 18, delay: 130, duration: 420 },
          { selector: ".mttf-directory-hero__description-row", y: 14, delay: 190, duration: 340 },
          { selector: ".mttf-directory-hero__summary", y: 16, delay: 230, duration: 340 },
          { selector: ".mttf-directory-hero__actions", y: 18, delay: 270, duration: 320 },
        ];

        if (media) {
          media.animate(
            [
              { opacity: 0.01, transform: "scale(1.035)" },
              { opacity: 1, transform: "scale(1)" },
            ],
            {
              duration: 560,
              easing: enterEase,
              fill: "both",
            }
          );
        }

        if (overlay) {
          overlay.animate(
            [
              { opacity: 0.01 },
              { opacity: 1 },
            ],
            {
              duration: 280,
              easing: softEase,
              fill: "both",
            }
          );
        }

        motionTargets.forEach(function (item) {
          var el = hero.querySelector(item.selector);
          if (!el) return;

          el.animate(
            [
              { opacity: 0.01, transform: "translate3d(0, " + item.y + "px, 0)" },
              { opacity: 1, transform: "translate3d(0, 0, 0)" },
            ],
            {
              duration: item.duration,
              delay: item.delay,
              easing: enterEase,
              fill: "both",
            }
          );
        });

        if (leadCard) {
          leadCard.animate(
            [
              { opacity: 0.01, transform: "translate3d(0, 24px, 0) scale(0.985)" },
              { opacity: 1, transform: "translate3d(0, 0, 0) scale(1)" },
            ],
            {
              duration: 420,
              delay: 220,
              easing: enterEase,
              fill: "both",
            }
          );
        }

        hero.classList.add("is-motion-live");
      }

      var revealCards = Array.prototype.slice.call(pageRoot.querySelectorAll(".mttf-card"));
      if (!revealCards.length || !("IntersectionObserver" in window)) {
        return;
      }

      var revealObserver = new IntersectionObserver(
        function (entries, observer) {
          entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;

            var card = entry.target;
            if (card.dataset.motionRevealed === "true") {
              observer.unobserve(card);
              return;
            }

            card.dataset.motionRevealed = "true";
            var cardIndex = parseInt(card.dataset.routePosition || "0", 10) || 0;
            var delay = Math.min((cardIndex % 4) * 55, 165);

            card.animate(
              [
                { opacity: 0.01, transform: "translate3d(0, 22px, 0) scale(0.985)" },
                { opacity: 1, transform: "translate3d(0, 0, 0) scale(1)" },
              ],
              {
                duration: 360,
                delay: delay,
                easing: enterEase,
                fill: "both",
              }
            );

            observer.unobserve(card);
          });
        },
        {
          root: null,
          rootMargin: "0px 0px -10% 0px",
          threshold: 0.16,
        }
      );

      revealCards.forEach(function (card) {
        if (card.dataset.motionObserved === "true") return;
        card.dataset.motionObserved = "true";
        revealObserver.observe(card);
      });
    });
  }

  function initDesktopAutoLoadCards() {
    if (window.innerWidth < 1024) {
      return;
    }

    var initialVisible = 6;
    var loadBatch = 3;
    var tracks = Array.prototype.slice.call(
      document.querySelectorAll(".mttf-hub__track")
    );
    if (!tracks.length) return;

    tracks.forEach(function (track) {
      var cards = Array.prototype.slice.call(track.children).filter(function (el) {
        return el.classList && el.classList.contains("mttf-card");
      });
      if (cards.length <= initialVisible) return;

      cards.forEach(function (card, index) {
        if (index >= initialVisible) {
          card.style.display = "none";
        }
      });

      track.dataset.mttfVisibleCount = String(initialVisible);

      var sentinel = document.createElement("div");
      sentinel.className = "mttf-load-sentinel";
      track.parentNode.appendChild(sentinel);

      var observer = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;

            var visibleCount = parseInt(track.dataset.mttfVisibleCount || "0", 10);
            var nextCount = Math.min(cards.length, visibleCount + loadBatch);

            for (var i = visibleCount; i < nextCount; i += 1) {
              cards[i].style.display = "";
            }

            track.dataset.mttfVisibleCount = String(nextCount);

            if (nextCount >= cards.length) {
              observer.unobserve(sentinel);
              if (sentinel.parentNode) sentinel.parentNode.removeChild(sentinel);
            }
          });
        },
        {
          root: null,
          rootMargin: "0px 0px 220px 0px",
          threshold: 0,
        }
      );

      observer.observe(sentinel);
    });
  }

  function initQuickRegionFilters() {
    var root = document.querySelector(".mttf");
    if (!root) return;

    var buttons = Array.prototype.slice.call(
      root.querySelectorAll("[data-region-filter]")
    );
    if (!buttons.length) return;

    function moveRegionFirst(regionKey) {
      var sections = Array.prototype.slice.call(
        root.querySelectorAll(".mttf-hub[data-mttf-region]")
      );
      if (!sections.length) return;

      var target = sections.find(function (section) {
        return section.dataset.mttfRegion === regionKey;
      });
      if (!target) return;

      var firstSection = sections[0];
      if (firstSection === target) return;

      root.insertBefore(target, firstSection);
    }

    buttons.forEach(function (button) {
      button.addEventListener("click", function () {
        var region = button.dataset.regionFilter || "";
        if (!region) return;

        buttons.forEach(function (btn) {
          btn.classList.remove("is-active");
        });
        button.classList.add("is-active");
        moveRegionFirst(region);
      });
    });
  }

  function initCarTypeFilters() {
    var root = document.querySelector(".mttf");
    if (!root) return;

    var buttons = Array.prototype.slice.call(
      root.querySelectorAll("[data-car-filter]")
    );
    if (!buttons.length) return;

    var activeFilter = "all";

    function updateSectionVisibility() {
      var sections = Array.prototype.slice.call(root.querySelectorAll(".mttf-hub"));
      sections.forEach(function (section) {
        var cards = Array.prototype.slice.call(section.querySelectorAll(".mttf-card"));
        var hasVisible = cards.some(function (card) {
          return card.style.display !== "none";
        });
        section.style.display = hasVisible ? "" : "none";
      });
    }

    function applyFilter() {
      var cards = Array.prototype.slice.call(root.querySelectorAll(".mttf-card"));
      cards.forEach(function (card) {
        var cardType = card.dataset.routeCarType || "";
        var matched = activeFilter === "all" || cardType === activeFilter;
        card.style.display = matched ? "" : "none";
      });
      updateSectionVisibility();
    }

    buttons.forEach(function (button) {
      button.addEventListener("click", function () {
        var nextFilter = button.dataset.carFilter || "all";
        if (nextFilter === activeFilter) {
          activeFilter = "all";
        } else {
          activeFilter = nextFilter;
        }
        buttons.forEach(function (btn) {
          btn.classList.remove("is-active");
        });
        if (activeFilter !== "all") {
          button.classList.add("is-active");
        }
        applyFilter();
      });
    });

    window.mttfApplyCarTypeFilter = applyFilter;
    applyFilter();
  }

  function initDynamicSearchPlaceholder() {
    var searchInput = document.querySelector('.mttf-search input[name="mttf_q"]');
    if (!searchInput) return;

    var places = [];

    function pushPlace(value) {
      var normalized = (value || "").replace(/\s+/g, " ").trim();
      if (!normalized) return;
      if (places.indexOf(normalized) === -1) {
        places.push(normalized);
      }
    }

    function extractPlacesFromTitle(title) {
      var text = (title || "").trim();
      if (!text) return [];

      text = text.replace(/^xe\s*limousine\s*/i, "");
      text = text.replace(/^tuyen\s*/i, "");

      var separators = /\s*(?:⇌|↔|<->|->|–|—|-|\/|\||,|;|to)\s*/i;
      var parts = text.split(separators).map(function (part) {
        return part.trim();
      }).filter(Boolean);

      if (!parts.length) {
        return [text];
      }

      return parts;
    }

    document.querySelectorAll(".mttf-card").forEach(function (card) {
      var title = (card.dataset.routeTitle || "").trim();
      extractPlacesFromTitle(title).forEach(pushPlace);
    });

    if (!places.length) return;

    var index = 0;
    function applyPlaceholder() {
      // Do not override while user is typing.
      if (searchInput.value && searchInput.value.trim() !== "") {
        return;
      }
      searchInput.placeholder = places[index];
      index = (index + 1) % places.length;
    }

    applyPlaceholder();
    setInterval(applyPlaceholder, 2200);
  }

  function initActivityPing() {
    var wrap = document.getElementById("mttf-activity");
    if (!wrap || !window.mttfData || !window.mttfData.activityEnabled) return;

    var textEl = wrap.querySelector("[data-mttf-activity-text]");
    if (!textEl) return;

    var nonce = window.mttfData.activityNonce || "";
    var pollMs = parseInt(window.mttfData.activityPollMs || "28000", 10);
    if (pollMs < 12000 || Number.isNaN(pollMs)) pollMs = 28000;

    var SHOW_MS = 5800;
    var GAP_MS = 4200;
    var HIDE_TRANSITION_MS = 420;
    var MIN_UNIQUE_PINGS = 2;

    var cachedPings = [];
    var lapQueue = [];
    var lapSigAtStart = "";
    var lastCompletedLapSig = "";
    var cycleToken = 0;
    var hideTimer = null;
    var gapTimer = null;
    var cycleRunning = false;

    function clearCycleTimers() {
      if (hideTimer) {
        clearTimeout(hideTimer);
        hideTimer = null;
      }
      if (gapTimer) {
        clearTimeout(gapTimer);
        gapTimer = null;
      }
    }

    function bumpCycle() {
      cycleToken += 1;
      return cycleToken;
    }

    function flashPop() {
      wrap.classList.remove("mttf-activity--pop");
      if (typeof window.requestAnimationFrame === "function") {
        window.requestAnimationFrame(function () {
          void wrap.offsetWidth;
          wrap.classList.add("mttf-activity--pop");
        });
      }
    }

    function showBanner() {
      wrap.classList.add("is-visible");
      wrap.setAttribute("aria-hidden", "false");
      flashPop();
    }

    function hideBanner() {
      wrap.classList.remove("is-visible", "mttf-activity--pop");
      wrap.setAttribute("aria-hidden", "true");
    }

    function normalizeMsgKey(msg) {
      return String(msg || "")
        .replace(/\s+/g, " ")
        .trim()
        .toLowerCase();
    }

    /** Lọc trùng theo id và theo nội dung hiển thị (tránh hai dòng giống hệt). */
    function dedupePings(list) {
      if (!list || !list.length) return [];
      var seenId = {};
      var seenMsg = {};
      var out = [];
      for (var i = 0; i < list.length; i += 1) {
        var p = list[i];
        if (!p || !p.id || !p.message) continue;
        var id = String(p.id);
        var mk = normalizeMsgKey(p.message);
        if (!mk || seenId[id] || seenMsg[mk]) continue;
        seenId[id] = true;
        seenMsg[mk] = true;
        out.push({
          id: id,
          ts: p.ts || 0,
          message: String(p.message),
        });
      }
      return out;
    }

    function signatureForUniq(uniq) {
      if (!uniq.length) return "";
      return uniq.map(function (p) { return String(p.id); }).sort().join("|");
    }

    /** Đặt ping vừa gửi lên đầu hàng chờ trong một vòng. */
    function orderQueuePreferLead(uniq, leadId) {
      var lid = leadId ? String(leadId) : "";
      var head = [];
      var tail = [];
      for (var i = 0; i < uniq.length; i += 1) {
        if (uniq[i].id === lid) {
          head.push(uniq[i]);
        } else {
          tail.push(uniq[i]);
        }
      }
      return head.concat(tail).length ? head.concat(tail) : uniq.slice();
    }

    function mergePingIntoCache(ping) {
      if (!ping || !ping.id) return;
      var pid = String(ping.id);
      var existsId = cachedPings.some(function (p) {
        return p && String(p.id) === pid;
      });
      var mk = normalizeMsgKey(ping.message);
      var existsMsg =
        mk &&
        cachedPings.some(function (p) {
          return p && normalizeMsgKey(p.message) === mk;
        });
      if (existsId || existsMsg) return;
      cachedPings.unshift({
        id: pid,
        ts: ping.ts || 0,
        message: String(ping.message || ""),
      });
      cachedPings = cachedPings.slice(0, 64);
    }

    function stopCycleLoop() {
      bumpCycle();
      clearCycleTimers();
      cycleRunning = false;
      lapQueue = [];
      lapSigAtStart = "";
      hideBanner();
    }

    /** Kết thúc một vòng: không lặp lại cùng bộ id cho đến khi có dữ liệu mới. */
    function completeLapAndMaybeContinue() {
      cycleRunning = false;
      lapQueue = [];
      if (lapSigAtStart) {
        lastCompletedLapSig = lapSigAtStart;
      }
      lapSigAtStart = "";
      hideBanner();

      var uniq = dedupePings(cachedPings);
      if (uniq.length < MIN_UNIQUE_PINGS) {
        lastCompletedLapSig = "";
        return;
      }
      var newSig = signatureForUniq(uniq);
      if (newSig !== lastCompletedLapSig) {
        startLap(uniq, null);
      }
    }

    /** Một ping trong vòng: hiện → ẩn → ping kế hoặc kết vòng */
    function playLapStep(token) {
      if (token !== cycleToken || !cycleRunning) return;
      clearCycleTimers();

      if (!lapQueue.length) {
        completeLapAndMaybeContinue();
        return;
      }

      var pick = lapQueue.shift();
      if (!pick || !pick.message) {
        gapTimer = setTimeout(function () {
          playLapStep(token);
        }, GAP_MS);
        return;
      }

      textEl.textContent = pick.message;
      showBanner();

      hideTimer = setTimeout(function () {
        if (token !== cycleToken) return;
        hideBanner();
        gapTimer = setTimeout(function () {
          if (token !== cycleToken) return;
          playLapStep(token);
        }, GAP_MS + HIDE_TRANSITION_MS);
      }, SHOW_MS);
    }

    function startLap(uniqPreferOrder, preferredLeadId) {
      var uniqBase = uniqPreferOrder && uniqPreferOrder.length ? uniqPreferOrder : dedupePings(cachedPings);
      var uniq =
        preferredLeadId && uniqBase.length
          ? orderQueuePreferLead(uniqBase, preferredLeadId)
          : uniqBase.slice();

      if (uniq.length < MIN_UNIQUE_PINGS) {
        stopCycleLoop();
        lastCompletedLapSig = "";
        return;
      }

      lapSigAtStart = signatureForUniq(uniqBase);

      bumpCycle();
      clearCycleTimers();
      lapQueue = uniq.slice();
      cycleRunning = true;
      playLapStep(cycleToken);
    }

    function tryStartLapFromFetch() {
      if (cycleRunning) return;

      var uniq = dedupePings(cachedPings);
      if (uniq.length < MIN_UNIQUE_PINGS) {
        stopCycleLoop();
        lastCompletedLapSig = "";
        return;
      }

      var sig = signatureForUniq(uniq);
      if (sig === lastCompletedLapSig) {
        return;
      }

      startLap(uniq, null);
    }

    function applyPingFromLead(ping) {
      if (!ping || !ping.message) return;
      mergePingIntoCache(ping);

      var uniq = dedupePings(cachedPings);
      if (uniq.length < MIN_UNIQUE_PINGS) return;

      var sig = signatureForUniq(uniq);

      bumpCycle();
      clearCycleTimers();
      cycleRunning = true;
      lapSigAtStart = sig;
      lapQueue = orderQueuePreferLead(uniq, String(ping.id));
      lapQueue.shift();
      textEl.textContent = ping.message;
      showBanner();

      var token = cycleToken;

      hideTimer = setTimeout(function () {
        if (token !== cycleToken) return;
        hideBanner();
        gapTimer = setTimeout(function () {
          if (token !== cycleToken) return;
          playLapStep(token);
        }, GAP_MS + HIDE_TRANSITION_MS);
      }, SHOW_MS);
    }

    window.__mttfApplyActivityPing = applyPingFromLead;

    function fetchPings() {
      if (!nonce || !window.mttfData.ajaxUrl) return;
      var fd = new FormData();
      fd.append("action", "mttf_get_activity_pings");
      fd.append("nonce", nonce);
      fetch(window.mttfData.ajaxUrl, { method: "POST", body: fd })
        .then(function (r) {
          return r.json();
        })
        .then(function (json) {
          if (!json || !json.success || !json.data || !Array.isArray(json.data.pings)) return;
          cachedPings = json.data.pings;
          setTimeout(tryStartLapFromFetch, 500);
        })
        .catch(function () {});
    }

    fetchPings();
    setInterval(fetchPings, pollMs);
  }

  function focusSearchFromHash() {
    var hash = window.location.hash || "";
    var params = new URLSearchParams(window.location.search || "");
    var shouldFocusByQuery = params.get("focus_search") === "1";
    if (hash !== "#mttf-search-input" && !shouldFocusByQuery) return;
    var searchInput = document.getElementById("mttf-search-input");
    if (!searchInput) return;
    setTimeout(function () {
      if (isMobileViewport()) {
        if (typeof searchInput.scrollIntoView === "function") {
          searchInput.scrollIntoView({ behavior: "smooth", block: "center" });
        }
        return;
      }
      searchInput.focus({ preventScroll: true });
      var len = searchInput.value.length;
      searchInput.setSelectionRange(len, len);
    }, 120);

    if (shouldFocusByQuery && window.history && typeof window.history.replaceState === "function") {
      params.delete("focus_search");
      var query = params.toString();
      var nextUrl = window.location.pathname + (query ? "?" + query : "") + "#mttf-search-input";
      window.history.replaceState({}, "", nextUrl);
    }
  }

  document.addEventListener("click", function (event) {
    var target = getSafeTarget(event);
    if (!target || !target.closest) return;
    var trackEl = target.closest(".mttf-js-track");
    if (!trackEl) return;
    trackClick(trackEl);
  });

  function boot() {
    setTouchData();
    initModal();
    initHeroLeadForms();
    initHeroSlides();
    initHeroDescriptionToggles();
    initRoutePageMotion();
    initCardSliders();
    initDesktopAutoLoadCards();
    initLiveSearch();
    initQuickRegionFilters();
    initCarTypeFilters();
    initDynamicSearchPlaceholder();
    initActivityPing();
    focusSearchFromHash();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
