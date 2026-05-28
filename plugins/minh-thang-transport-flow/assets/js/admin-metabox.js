(function ($) {
  function renderPreview(ids) {
    var $preview = $("#mttf_gallery_preview");
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

  $(function () {
    var $input = $("#mttf_gallery_ids");
    var mediaFrame;

    $("#mttf_select_gallery").on("click", function (e) {
      e.preventDefault();

      if (mediaFrame) {
        mediaFrame.open();
        return;
      }

      mediaFrame = wp.media({
        title: "Chọn ảnh cho card",
        button: { text: "Dùng ảnh đã chọn" },
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
        renderPreview(ids);
      });

      mediaFrame.open();
    });

    $("#mttf_clear_gallery").on("click", function (e) {
      e.preventDefault();
      $input.val("");
      $("#mttf_gallery_preview").empty();
    });
  });
})(jQuery);
