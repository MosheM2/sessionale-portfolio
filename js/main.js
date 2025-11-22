/**
 * Portfolio Migration Theme JavaScript
 *
 * @package Portfolio_Migration
 */

(function($) {
    'use strict';

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

})(jQuery);
