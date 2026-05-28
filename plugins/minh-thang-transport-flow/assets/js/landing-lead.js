(function () {
  "use strict";

  function openLeadFromFirstCard() {
    var root =
      document.querySelector(".mttf-landing--tuyen") ||
      document.querySelector(".mttf-landing--nha-xe");
    if (!root) return;

    var card =
      root.querySelector(".mttf-landing-grid .mttf-card") ||
      root.querySelector(".mttf-card");
    if (!card) return;

    var modalBtn = card.querySelector(".mttf-open-modal");
    if (modalBtn) {
      modalBtn.click();
      return;
    }

    card.click();
  }

  document.addEventListener(
    "click",
    function (event) {
      var trigger = event.target.closest(".mttf-landing-trigger-lead");
      if (!trigger) {
        return;
      }
      event.preventDefault();
      openLeadFromFirstCard();
    },
    false
  );
})();
