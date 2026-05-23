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
  });
})(jQuery);
