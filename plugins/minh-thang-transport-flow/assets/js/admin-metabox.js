(function ($) {
  function renderPreview(ids, previewSelector) {
    var $preview = $(previewSelector);
    $preview.empty();
    ids.forEach(function (id) {
      var attachment = wp.media.attachment(id);
      attachment.fetch().then(function () {
        var sizes = attachment.get("sizes") || {};
        var thumb = sizes.thumbnail || {};
        var url = thumb.url || attachment.get("url");
        if (!url) return;
        $preview.append(
          $("<img>", {
            src: url,
            css: {
              width: "64px",
              height: "64px",
              objectFit: "cover",
              borderRadius: "6px",
              border: "1px solid #ddd",
            },
          })
        );
      });
    });
  }

  function bindGalleryField(config) {
    var $input = $(config.inputSelector);
    if (!$input.length) return;

    var mediaFrame;

    $(config.selectSelector).on("click", function (e) {
      e.preventDefault();

      if (mediaFrame) {
        mediaFrame.open();
        return;
      }

      mediaFrame = wp.media({
        title: config.title,
        button: { text: config.buttonText },
        multiple: true,
      });

      mediaFrame.on("select", function () {
        var selection = mediaFrame.state().get("selection");
        var ids = selection
          .map(function (attachment) {
            return attachment.get("id");
          })
          .filter(Boolean);

        $input.val(ids.join(","));
        renderPreview(ids, config.previewSelector);
      });

      mediaFrame.open();
    });

    $(config.clearSelector).on("click", function (e) {
      e.preventDefault();
      $input.val("");
      $(config.previewSelector).empty();
    });
  }

  $(function () {
    bindGalleryField({
      inputSelector: "#mttf_gallery_ids",
      selectSelector: "#mttf_select_gallery",
      clearSelector: "#mttf_clear_gallery",
      previewSelector: "#mttf_gallery_preview",
      title: "Chọn ảnh cho card",
      buttonText: "Dùng ảnh đã chọn",
    });

    bindGalleryField({
      inputSelector: "#mttf_operator_gallery_ids",
      selectSelector: "#mttf_operator_select_gallery",
      clearSelector: "#mttf_operator_clear_gallery",
      previewSelector: "#mttf_operator_gallery_preview",
      title: "Chọn ảnh gallery cho nhà xe",
      buttonText: "Dùng ảnh đã chọn",
    });
  });
})(jQuery);
