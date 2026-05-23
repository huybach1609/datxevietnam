(function () {
  function initHeaderToggle() {
    var header = document.querySelector(".dxvn-header");
    if (!header) return;

    var toggle = header.querySelector(".dxvn-header__toggle");
    if (!toggle) return;
    var submenuToggles = [];

    function closeAllSubmenus() {
      submenuToggles.forEach(function (btn) {
        var li = btn.closest("li.menu-item-has-children");
        btn.setAttribute("aria-expanded", "false");
        if (li) {
          li.classList.remove("is-submenu-open");
        }
      });
    }

    function initSubmenuToggles() {
      var parentItems = header.querySelectorAll(
        ".dxvn-header__menu li.menu-item-has-children"
      );
      parentItems.forEach(function (item, index) {
        var submenu = item.querySelector(":scope > .sub-menu");
        var link = item.querySelector(":scope > a");
        if (!submenu || !link) return;

        var submenuId = submenu.id || "dxvn-submenu-" + index;
        submenu.id = submenuId;

        var btn = document.createElement("button");
        btn.type = "button";
        btn.className = "dxvn-submenu-toggle";
        btn.setAttribute("aria-expanded", "false");
        btn.setAttribute("aria-controls", submenuId);
        btn.setAttribute("aria-label", "Mở menu con");
        btn.innerHTML = '<span aria-hidden="true"></span>';
        item.appendChild(btn);
        submenuToggles.push(btn);

        btn.addEventListener("click", function (event) {
          event.preventDefault();
          event.stopPropagation();
          var expanded = btn.getAttribute("aria-expanded") === "true";
          btn.setAttribute("aria-expanded", expanded ? "false" : "true");
          item.classList.toggle("is-submenu-open", !expanded);
        });
      });
    }

    toggle.addEventListener("click", function () {
      var expanded = toggle.getAttribute("aria-expanded") === "true";
      toggle.setAttribute("aria-expanded", expanded ? "false" : "true");
      header.classList.toggle("is-open", !expanded);
      if (expanded) {
        closeAllSubmenus();
      }
    });

    document.addEventListener("click", function (event) {
      if (!header.classList.contains("is-open")) return;
      if (header.contains(event.target)) return;
      toggle.setAttribute("aria-expanded", "false");
      header.classList.remove("is-open");
      closeAllSubmenus();
    });

    initSubmenuToggles();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initHeaderToggle);
  } else {
    initHeaderToggle();
  }
})();
