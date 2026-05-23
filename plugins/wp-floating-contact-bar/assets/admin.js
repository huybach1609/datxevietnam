(function ($) {
	'use strict';

	function getNextIndex($list) {
		var maxIndex = -1;

		$list.find('.wfcb-item').each(function () {
			var $inputs = $(this).find('input[name*="wfcb_settings[items]"]');

			$inputs.each(function () {
				var name = $(this).attr('name');
				var match = name && name.match(/wfcb_settings\[items]\[(\d+)]/);

				if (match && match[1]) {
					var idx = parseInt(match[1], 10);
					if (!isNaN(idx) && idx > maxIndex) {
						maxIndex = idx;
					}
				}
			});
		});

		return maxIndex + 1;
	}

	function initSortable($list) {
		if (!$list.length || !$list.sortable) {
			return;
		}

		$list.sortable({
			handle: '.wfcb-item-handle',
			placeholder: 'wfcb-item-placeholder'
		});
	}

	function bindRemove($container) {
		$container.on('click', '.wfcb-remove-item', function (e) {
			e.preventDefault();
			$(this).closest('.wfcb-item').remove();
		});
	}

	function bindMediaUploader($container) {
		var mediaFrame;

		$container.on('click', '.wfcb-upload-media', function (e) {
			e.preventDefault();

			var $button  = $(this);
			var $field   = $button.closest('.wfcb-field-group').find('.wfcb-media-url');

			if (mediaFrame) {
				mediaFrame.open();
				mediaFrame.off('select');
				mediaFrame.on('select', function () {
					var attachment = mediaFrame.state().get('selection').first().toJSON();
					$field.val(attachment.url);
				});
				return;
			}

			mediaFrame = wp.media({
				title: $button.data('title') || 'Select image',
				button: {
					text: $button.data('button-text') || 'Use this image'
				},
				multiple: false
			});

			mediaFrame.on('select', function () {
				var attachment = mediaFrame.state().get('selection').first().toJSON();
				$field.val(attachment.url);
			});

			mediaFrame.open();
		});
	}

	$(function () {
		var $list = $('#wfcb-items-list');

		if (!$list.length) {
			return;
		}

		initSortable($list);
		bindRemove($list);
		bindMediaUploader($list);

		var nextIndex = getNextIndex($list);

		$('#wfcb-add-item').on('click', function (e) {
			e.preventDefault();

			if (typeof wfcbAdmin === 'undefined' || !wfcbAdmin.itemTemplate) {
				return;
			}

			var html = wfcbAdmin.itemTemplate.replace(/{{index}}/g, String(nextIndex));
			nextIndex++;

			var $item = $(html);
			$list.append($item);

			initSortable($list);
		});
	});
}(jQuery));

