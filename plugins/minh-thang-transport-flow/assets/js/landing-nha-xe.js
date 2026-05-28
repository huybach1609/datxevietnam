(function () {
  "use strict";

  var cfg = window.mttfNhaXeLanding || {};

  function getRoot() {
    return document.querySelector(".mttf-landing--nha-xe");
  }

  function getPanel() {
    var root = getRoot();
    return root ? root.querySelector("[data-mttf-routes-panel]") : null;
  }

  function getGrid() {
    var panel = getPanel();
    return panel ? panel.querySelector("[data-mttf-routes-grid]") : null;
  }

  function getEmptyEl() {
    var panel = getPanel();
    return panel ? panel.querySelector("[data-mttf-routes-empty]") : null;
  }

  function getStatusEl() {
    var panel = getPanel();
    return panel ? panel.querySelector("[data-mttf-routes-status]") : null;
  }

  function getFilterButtons() {
    var root = getRoot();
    if (!root) return [];
    return Array.prototype.slice.call(
      root.querySelectorAll(".mttf-landing-route-filter__pill")
    );
  }

  function setLoading(isLoading) {
    var panel = getPanel();
    var grid = getGrid();
    var status = getStatusEl();
    var pills = getFilterButtons();

    if (panel) {
      panel.classList.toggle("is-loading", !!isLoading);
    }
    if (grid) {
      grid.setAttribute("aria-busy", isLoading ? "true" : "false");
    }
    if (status) {
      status.hidden = !isLoading;
      status.textContent = isLoading ? "Đang tải..." : "";
    }
    pills.forEach(function (btn) {
      btn.disabled = !!isLoading;
    });
  }

  function setActivePill(tuyenId) {
    var id = String(tuyenId || 0);
    getFilterButtons().forEach(function (btn) {
      var active = btn.getAttribute("data-tuyen-id") === id;
      btn.classList.toggle("is-active", active);
      btn.setAttribute("aria-selected", active ? "true" : "false");
    });
  }

  function showEmpty(show) {
    var grid = getGrid();
    var empty = getEmptyEl();
    if (grid) {
      grid.hidden = !!show;
    }
    if (empty) {
      empty.hidden = !show;
    }
  }

  function initSlidersInGrid() {
    var grid = getGrid();
    if (!grid || typeof window.mttfInitCardSlidersIn !== "function") {
      return;
    }
    window.mttfInitCardSlidersIn(grid);
  }

  function loadRoutes(tuyenId) {
    if (!cfg.ajaxUrl || !cfg.nonce || !cfg.action) {
      return;
    }

    setLoading(true);

    var body = new FormData();
    body.append("action", cfg.action);
    body.append("nonce", cfg.nonce);
    body.append("nha_xe_id", String(cfg.nhaXeId || 0));
    body.append("tuyen_id", String(tuyenId || 0));

    fetch(cfg.ajaxUrl, {
      method: "POST",
      credentials: "same-origin",
      body: body,
    })
      .then(function (res) {
        return res.json();
      })
      .then(function (json) {
        if (!json || !json.success) {
          throw new Error("Filter failed");
        }

        var grid = getGrid();
        if (!grid) {
          return;
        }

        var data = json.data || {};
        if (data.empty || !data.html) {
          grid.innerHTML = "";
          showEmpty(true);
        } else {
          grid.innerHTML = data.html;
          showEmpty(false);
          initSlidersInGrid();
        }
      })
      .catch(function () {
        var status = getStatusEl();
        if (status) {
          status.hidden = false;
          status.textContent = "Không tải được danh sách. Vui lòng thử lại.";
        }
      })
      .finally(function () {
        setLoading(false);
      });
  }

  function bindFilters() {
    var filterWrap = getRoot();
    if (!filterWrap) {
      return;
    }

    var filterBar = filterWrap.querySelector(".mttf-landing-route-filter");
    if (!filterBar) {
      return;
    }

    filterBar.addEventListener("click", function (event) {
      var btn = event.target.closest(".mttf-landing-route-filter__pill");
      if (!btn || btn.disabled) {
        return;
      }

      var tuyenId = parseInt(btn.getAttribute("data-tuyen-id") || "0", 10);
      if (Number.isNaN(tuyenId)) {
        tuyenId = 0;
      }

      if (btn.classList.contains("is-active")) {
        return;
      }

      setActivePill(tuyenId);

      if (tuyenId === 0) {
        var initial = filterWrap.getAttribute("data-mttf-initial-grid");
        var grid = getGrid();
        if (initial && grid) {
          grid.innerHTML = initial;
          showEmpty(false);
          initSlidersInGrid();
          setLoading(false);
          return;
        }
      }

      loadRoutes(tuyenId);
    });
  }

  function cacheInitialGrid() {
    var root = getRoot();
    var grid = getGrid();
    if (root && grid && !root.getAttribute("data-mttf-initial-grid")) {
      root.setAttribute("data-mttf-initial-grid", grid.innerHTML);
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    if (!getRoot()) {
      return;
    }
    cacheInitialGrid();
    initSlidersInGrid();
    bindFilters();
  });
})();
