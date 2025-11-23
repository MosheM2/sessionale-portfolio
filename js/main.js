/**
 * Portfolio Migration Theme JavaScript
 *
 * @package Portfolio_Migration
 */

(function($) {
    'use strict';

    /**
     * Header scroll effect - add background on scroll
     */
    function initHeaderScroll() {
        var header = document.querySelector('.site-header');

        if (header) {
            var handleScroll = function() {
                if (window.scrollY > 10) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            };

            window.addEventListener('scroll', handleScroll, { passive: true });
            handleScroll();
        }
    }

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

                // Function to apply class once we have dimensions
                var applyVideoClass = function() {
                    if (video.videoWidth && video.videoHeight) {
                        applyAspectClass($figure, video.videoWidth, video.videoHeight);
                        return true;
                    }
                    return false;
                };

                // Try immediately
                if (!applyVideoClass()) {
                    // Listen for metadata load
                    $video.on('loadedmetadata', function() {
                        applyAspectClass($figure, this.videoWidth, this.videoHeight);
                    });

                    // Also try with delays as fallback
                    setTimeout(applyVideoClass, 500);
                    setTimeout(applyVideoClass, 1000);
                    setTimeout(applyVideoClass, 2000);
                }

                // Default to landscape for 16:9 videos if detection fails
                // Most professional videos are 16:9 landscape
                setTimeout(function() {
                    if (!$figure.hasClass('media-portrait') && !$figure.hasClass('media-landscape')) {
                        $figure.addClass('media-landscape');
                    }
                }, 3000);
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

    /**
     * Check if all media is landscape and switch to single column if so
     */
    function optimizeGridLayout() {
        var $article = $('article.single-portfolio');
        var layout = $article.data('layout');

        if (layout !== 'auto') {
            return;
        }

        var $content = $article.find('.project-content');
        var $mediaItems = $content.find('figure.wp-block-image, figure.wp-block-video, .wp-block-embed');

        // Count portrait vs landscape
        var portraitCount = $content.find('.media-portrait').length;
        var landscapeCount = $content.find('.media-landscape').length;

        // If no portrait items, switch to single column layout
        if (portraitCount === 0 && landscapeCount > 0) {
            $content.addClass('all-landscape');
        } else {
            $content.removeClass('all-landscape');
        }
    }

    // Initialize on document ready
    $(document).ready(function() {
        initHeaderScroll();
        detectAspectRatios();
        // Check grid optimization after a delay to allow classes to be applied
        setTimeout(optimizeGridLayout, 100);
    });

    // Also run on window load to catch any late-loading media
    $(window).on('load', function() {
        detectAspectRatios();
        setTimeout(optimizeGridLayout, 500);
        setTimeout(optimizeGridLayout, 2000);
    });

})(jQuery);
