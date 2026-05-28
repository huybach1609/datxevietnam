(function ($) {
  function renderPreview($container, url) {
    var $preview = $container.find(".mttf-media-preview");
    $preview.empty();

    if (!url) {
      return;
    }

    $preview.append(
      $("<img>", {
        src: url,
        alt: "",
        css: {
          width: "48px",
          height: "48px",
          objectFit: "cover",
          borderRadius: "6px",
          border: "1px solid #ddd",
        },
      })
    );
  }

  function syncHeroPopularIds($picker) {
    var ids = [];
    $picker.find(".mttf-hero-popular-picker__item").each(function () {
      var id = $(this).attr("data-term-id");
      if (id) {
        ids.push(id);
      }
    });
    $picker.find(".mttf-hero-popular-picker__ids").val(ids.join(","));
  }

  function refreshHeroPopularSelect($picker) {
    var selected = {};
    $picker.find(".mttf-hero-popular-picker__item").each(function () {
      selected[$(this).attr("data-term-id")] = true;
    });

    $picker.find(".mttf-hero-popular-picker__add-select option").each(function () {
      var $opt = $(this);
      var val = $opt.val();
      if (!val) {
        return;
      }
      $opt.prop("disabled", !!selected[val]);
    });
  }

  function initHeroPopularPicker($picker) {
    var $list = $picker.find(".mttf-hero-popular-picker__list");
    var $select = $picker.find(".mttf-hero-popular-picker__add-select");

    if (!$list.length) {
      return;
    }

    $list.sortable({
      handle: ".mttf-hero-popular-picker__handle",
      axis: "y",
      update: function () {
        syncHeroPopularIds($picker);
      },
    });

    $picker.on("click", ".mttf-hero-popular-picker__add-btn", function (e) {
      e.preventDefault();
      var termId = $select.val();
      if (!termId) {
        return;
      }

      var $opt = $select.find('option[value="' + termId + '"]');
      var name = $opt.text();
      var previewHref = $opt.data("preview-url") || "";

      if ($list.find('[data-term-id="' + termId + '"]').length) {
        return;
      }

      var $item = $(
        '<li class="mttf-hero-popular-picker__item" data-term-id="' +
          termId +
          '">' +
          '<span class="mttf-hero-popular-picker__handle dashicons dashicons-menu" aria-hidden="true"></span>' +
          '<span class="mttf-hero-popular-picker__name"></span>' +
          "</li>"
      );
      $item.find(".mttf-hero-popular-picker__name").text(name);

      if (previewHref) {
        $item.append(
          ' <a class="mttf-hero-popular-picker__preview" href="' +
            previewHref +
            '" target="_blank" rel="noopener noreferrer">Xem</a>'
        );
      }

      $item.append(
        ' <button type="button" class="button-link-delete mttf-hero-popular-picker__remove">Xóa</button>'
      );

      $list.append($item);
      $select.val("");
      syncHeroPopularIds($picker);
      refreshHeroPopularSelect($picker);
    });

    $picker.on("click", ".mttf-hero-popular-picker__remove", function (e) {
      e.preventDefault();
      $(this).closest(".mttf-hero-popular-picker__item").remove();
      syncHeroPopularIds($picker);
      refreshHeroPopularSelect($picker);
    });

    refreshHeroPopularSelect($picker);
    syncHeroPopularIds($picker);
  }

  $(function () {
    $(".mttf-media-picker").each(function () {
      var $container = $(this);
      var $input = $container.find(".mttf-media-url");
      var mediaFrame;

      $container.find(".mttf-select-media").on("click", function (e) {
        e.preventDefault();

        if (mediaFrame) {
          mediaFrame.open();
          return;
        }

        mediaFrame = wp.media({
          title: "Chọn ảnh",
          button: { text: "Dùng ảnh này" },
          multiple: false,
          library: { type: "image" },
        });

        mediaFrame.on("select", function () {
          var attachment = mediaFrame.state().get("selection").first();
          var url = attachment ? attachment.get("url") : "";
          $input.val(url);
          renderPreview($container, url);
        });

        mediaFrame.open();
      });

      $container.find(".mttf-clear-media").on("click", function (e) {
        e.preventDefault();
        $input.val("");
        renderPreview($container, "");
      });
    });

    $(".mttf-hero-popular-picker").each(function () {
      initHeroPopularPicker($(this));
    });
  });
})(jQuery);
