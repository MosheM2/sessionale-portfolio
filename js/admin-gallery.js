/**
 * Portfolio Gallery Admin JavaScript
 * Handles drag-and-drop gallery management
 */

(function($) {
    'use strict';

    var galleryData = [];
    var $container;
    var $dataInput;

    $(document).ready(function() {
        $container = $('#portfolio-gallery-items');
        $dataInput = $('#portfolio-gallery-data');

        // Load existing data
        try {
            galleryData = JSON.parse($dataInput.val()) || [];
        } catch (e) {
            galleryData = [];
        }

        // Initialize sortable
        initSortable();

        // Add images button
        $('#portfolio-add-images').on('click', function() {
            openMediaLibrary('image');
        });

        // Add video button
        $('#portfolio-add-video').on('click', function() {
            openMediaLibrary('video');
        });

        // Add embed button
        $('#portfolio-add-embed').on('click', function() {
            var embedUrl = prompt('Enter YouTube or Vimeo URL:');
            if (embedUrl) {
                addEmbedItem(embedUrl);
            }
        });

        // Remove item
        $container.on('click', '.portfolio-gallery-item-remove', function(e) {
            e.preventDefault();
            $(this).closest('.portfolio-gallery-item').fadeOut(200, function() {
                $(this).remove();
                updateGalleryData();
                checkEmpty();
            });
        });

        // Layout toggle
        $container.on('click', '.portfolio-gallery-item-layout', function(e) {
            e.preventDefault();
            var $select = $(this).closest('.portfolio-gallery-item').find('.portfolio-gallery-item-layout-select');
            $('.portfolio-gallery-item-layout-select').not($select).removeClass('active');
            $select.toggleClass('active');
        });

        // Layout change
        $container.on('change', '.portfolio-gallery-item-layout-select input', function() {
            var $item = $(this).closest('.portfolio-gallery-item');
            var layout = $(this).val();
            $item.attr('data-layout', layout);

            // Update badge
            $item.find('.layout-badge').remove();
            if (layout !== 'auto') {
                var badgeText = {
                    'full': 'Full',
                    'two-thirds': '2/3',
                    'half': '1/2',
                    'two-fifths': '2/5',
                    'third': '1/3',
                    'quarter': '1/4',
                    'fifth': '1/5',
                    'sixth': '1/6',
                    'eighth': '1/8'
                };
                $item.append('<span class="layout-badge">' + (badgeText[layout] || layout) + '</span>');
            }

            // Update grid span
            if (layout === 'full') {
                $item.css('grid-column', 'span 2');
            } else {
                $item.css('grid-column', '');
            }

            $(this).closest('.portfolio-gallery-item-layout-select').removeClass('active');
            updateGalleryData();
        });

        // Close layout select when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.portfolio-gallery-item-layout, .portfolio-gallery-item-layout-select').length) {
                $('.portfolio-gallery-item-layout-select').removeClass('active');
            }
        });
    });

    function initSortable() {
        $container.sortable({
            items: '.portfolio-gallery-item',
            placeholder: 'portfolio-gallery-item ui-sortable-placeholder',
            cursor: 'move',
            opacity: 0.7,
            update: function() {
                updateGalleryData();
            }
        });
    }

    function openMediaLibrary(type) {
        var frame = wp.media({
            title: type === 'video' ? 'Select Videos' : 'Select Images',
            button: { text: 'Add to Gallery' },
            multiple: true,
            library: { type: type }
        });

        frame.on('select', function() {
            var attachments = frame.state().get('selection').toJSON();
            attachments.forEach(function(attachment) {
                addMediaItem(attachment, type);
            });
            updateGalleryData();
            checkEmpty();
        });

        frame.open();
    }

    function addMediaItem(attachment, type) {
        var item = {
            type: type,
            attachment_id: attachment.id,
            url: attachment.url,
            thumb: type === 'image' ? (attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url) : attachment.url,
            width: attachment.width,
            height: attachment.height,
            layout: 'auto'
        };

        galleryData.push(item);

        var $item = createItemElement(item, galleryData.length - 1);
        removeEmptyMessage();
        $container.append($item);
    }

    function addEmbedItem(embedUrl) {
        var item = {
            type: 'embed',
            url: embedUrl,
            thumb: '',
            layout: 'auto'
        };

        galleryData.push(item);

        var $item = createItemElement(item, galleryData.length - 1);
        removeEmptyMessage();
        $container.append($item);
        updateGalleryData();
    }

    function createItemElement(item, index) {
        var mediaHtml;
        if (item.type === 'video') {
            mediaHtml = '<video src="' + item.url + '" muted></video>';
        } else if (item.type === 'embed') {
            mediaHtml = '<div style="height:120px;background:#000;display:flex;align-items:center;justify-content:center;color:#fff;"><span class="dashicons dashicons-video-alt3" style="font-size:40px;"></span></div>';
        } else {
            mediaHtml = '<img src="' + item.thumb + '" alt="">';
        }

        var badgeLabels = {
            'full': 'Full',
            'two-thirds': '2/3',
            'half': '1/2',
            'two-fifths': '2/5',
            'third': '1/3',
            'quarter': '1/4',
            'fifth': '1/5',
            'sixth': '1/6',
            'eighth': '1/8'
        };
        var layoutBadge = item.layout !== 'auto' ? '<span class="layout-badge">' + (badgeLabels[item.layout] || item.layout) + '</span>' : '';

        var html = '<div class="portfolio-gallery-item" data-index="' + index + '" data-type="' + item.type + '" data-layout="' + item.layout + '">' +
            mediaHtml +
            '<div class="portfolio-gallery-item-actions">' +
                '<button type="button" class="portfolio-gallery-item-layout" title="Layout">&#9783;</button>' +
                '<button type="button" class="portfolio-gallery-item-remove" title="Remove">&times;</button>' +
            '</div>' +
            '<div class="portfolio-gallery-item-layout-select">' +
                '<label><input type="radio" name="layout_' + index + '" value="auto"' + (item.layout === 'auto' ? ' checked' : '') + '> Auto</label>' +
                '<label><input type="radio" name="layout_' + index + '" value="full"' + (item.layout === 'full' ? ' checked' : '') + '> Full (100%)</label>' +
                '<label><input type="radio" name="layout_' + index + '" value="two-thirds"' + (item.layout === 'two-thirds' ? ' checked' : '') + '> 2/3 Width</label>' +
                '<label><input type="radio" name="layout_' + index + '" value="half"' + (item.layout === 'half' ? ' checked' : '') + '> 1/2 Width</label>' +
                '<label><input type="radio" name="layout_' + index + '" value="two-fifths"' + (item.layout === 'two-fifths' ? ' checked' : '') + '> 2/5 Width</label>' +
                '<label><input type="radio" name="layout_' + index + '" value="third"' + (item.layout === 'third' ? ' checked' : '') + '> 1/3 Width</label>' +
                '<label><input type="radio" name="layout_' + index + '" value="quarter"' + (item.layout === 'quarter' ? ' checked' : '') + '> 1/4 Width</label>' +
                '<label><input type="radio" name="layout_' + index + '" value="fifth"' + (item.layout === 'fifth' ? ' checked' : '') + '> 1/5 Width</label>' +
                '<label><input type="radio" name="layout_' + index + '" value="sixth"' + (item.layout === 'sixth' ? ' checked' : '') + '> 1/6 Width</label>' +
                '<label><input type="radio" name="layout_' + index + '" value="eighth"' + (item.layout === 'eighth' ? ' checked' : '') + '> 1/8 Width</label>' +
            '</div>' +
            layoutBadge +
            '<div class="portfolio-gallery-item-info">' +
                '<span class="portfolio-gallery-item-type ' + item.type + '">' + item.type.charAt(0).toUpperCase() + item.type.slice(1) + '</span>' +
            '</div>' +
            '<input type="hidden" class="item-data" value="">' +
        '</div>';

        return $(html);
    }

    function updateGalleryData() {
        galleryData = [];
        $container.find('.portfolio-gallery-item').each(function() {
            var $item = $(this);
            var type = $item.data('type');
            var layout = $item.attr('data-layout') || 'auto';

            var item = {
                type: type,
                layout: layout
            };

            // Get data from hidden input or reconstruct
            var existingData = $item.find('.item-data').val();
            if (existingData) {
                try {
                    var parsed = JSON.parse(existingData);
                    item = $.extend(parsed, { layout: layout });
                } catch (e) {}
            }

            // Get URL from media element only if not already set from hidden input
            if (type === 'image') {
                if (!item.url) {
                    item.url = $item.find('img').attr('src');
                }
                if (!item.thumb) {
                    item.thumb = item.url;
                }
            } else if (type === 'video') {
                if (!item.url) {
                    item.url = $item.find('video').attr('src');
                }
            }
            // For embed type, URL should already be preserved from hidden input

            galleryData.push(item);
        });

        $dataInput.val(JSON.stringify(galleryData));
    }

    function removeEmptyMessage() {
        $container.find('.portfolio-gallery-empty').remove();
    }

    function checkEmpty() {
        if ($container.find('.portfolio-gallery-item').length === 0) {
            $container.html('<div class="portfolio-gallery-empty">No gallery items yet. Add images or videos below.</div>');
        }
    }

})(jQuery);
