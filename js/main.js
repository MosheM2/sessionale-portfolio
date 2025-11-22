/**
 * Portfolio Migration Theme JavaScript
 *
 * @package Portfolio_Migration
 */

(function($) {
    'use strict';

    /**
     * Detect aspect ratio and apply classes for auto-layout
     */
    function detectAspectRatios() {
        var $article = $('article.single-portfolio');
        var layout = $article.data('layout');

        // Only run for auto layout
        if (layout !== 'auto') {
            return;
        }

        var $content = $article.find('.project-content');

        // Process images
        $content.find('figure.wp-block-image').each(function() {
            var $figure = $(this);
            var $img = $figure.find('img');

            if ($img.length) {
                // Check if image has width/height attributes
                var width = $img.attr('width');
                var height = $img.attr('height');

                if (width && height) {
                    applyAspectClass($figure, parseInt(width), parseInt(height));
                } else {
                    // Wait for image to load to get natural dimensions
                    var img = $img[0];
                    if (img.complete && img.naturalWidth) {
                        applyAspectClass($figure, img.naturalWidth, img.naturalHeight);
                    } else {
                        $img.on('load', function() {
                            applyAspectClass($figure, this.naturalWidth, this.naturalHeight);
                        });
                    }
                }
            }
        });

        // Process videos
        $content.find('figure.wp-block-video').each(function() {
            var $figure = $(this);
            var $video = $figure.find('video');

            if ($video.length) {
                var video = $video[0];

                // Check if video metadata is loaded
                if (video.videoWidth && video.videoHeight) {
                    applyAspectClass($figure, video.videoWidth, video.videoHeight);
                } else {
                    $video.on('loadedmetadata', function() {
                        applyAspectClass($figure, this.videoWidth, this.videoHeight);
                    });

                    // Also try after a short delay in case metadata is already loaded
                    setTimeout(function() {
                        if (video.videoWidth && video.videoHeight) {
                            applyAspectClass($figure, video.videoWidth, video.videoHeight);
                        }
                    }, 500);
                }
            }
        });

        // Process embeds (YouTube/Vimeo) - these are typically 16:9 landscape
        $content.find('.wp-block-embed').each(function() {
            var $embed = $(this);
            // Default embeds to landscape (16:9)
            $embed.addClass('media-landscape');
        });
    }

    /**
     * Apply portrait or landscape class based on dimensions
     */
    function applyAspectClass($element, width, height) {
        // Remove any existing classes
        $element.removeClass('media-portrait media-landscape');

        // Calculate aspect ratio
        var aspectRatio = width / height;

        // Portrait: aspect ratio < 1 (height > width)
        // Landscape: aspect ratio >= 1 (width >= height)
        // We use 0.9 as threshold to account for nearly-square content
        if (aspectRatio < 0.9) {
            $element.addClass('media-portrait');
        } else {
            $element.addClass('media-landscape');
        }
    }

    // Smooth scroll for anchor links
    $('a[href^="#"]').on('click', function(e) {
        var target = $(this.hash);
        if (target.length) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 500);
        }
    });

    // Lazy load images (if needed)
    if ('loading' in HTMLImageElement.prototype) {
        const images = document.querySelectorAll('img[loading="lazy"]');
        images.forEach(img => {
            if (img.dataset.src) {
                img.src = img.dataset.src;
            }
        });
    }

    // Add animation on scroll for portfolio items
    $(window).on('scroll load', function() {
        $('.portfolio-item').each(function() {
            var itemTop = $(this).offset().top;
            var itemBottom = itemTop + $(this).outerHeight();
            var windowTop = $(window).scrollTop();
            var windowBottom = windowTop + $(window).height();

            if (itemBottom > windowTop && itemTop < windowBottom) {
                $(this).addClass('visible');
            }
        });
    });

    // Mobile menu toggle (if needed in future)
    $('.menu-toggle').on('click', function() {
        $(this).toggleClass('active');
        $('.main-navigation').toggleClass('active');
    });

    // Initialize aspect ratio detection on document ready
    $(document).ready(function() {
        detectAspectRatios();
    });

    // Also run on window load to catch any late-loading media
    $(window).on('load', function() {
        detectAspectRatios();
    });

})(jQuery);
