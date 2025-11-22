<?php
/**
 * Template for the About Page
 *
 * This template is automatically used for pages with slug "about"
 *
 * @package Portfolio_Migration
 */

get_header(); ?>

<main class="site-content about-page">
    <?php
    while (have_posts()) : the_post();
        ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class('about-article'); ?>>

            <div class="about-content">
                <?php the_content(); ?>
            </div>

        </article>

    <?php endwhile; ?>
</main>

<!-- Lightbox -->
<div id="about-lightbox" class="about-lightbox">
    <button class="lightbox-close" aria-label="Close">&times;</button>
    <button class="lightbox-prev" aria-label="Previous">&lsaquo;</button>
    <button class="lightbox-next" aria-label="Next">&rsaquo;</button>
    <div class="lightbox-content">
        <img src="" alt="">
    </div>
</div>

<style>
/* About Page Styles */
.about-page {
    padding: 0;
}

.about-article {
    max-width: 100%;
    margin: 0;
    padding: 0;
}

.about-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 40px 20px;
}

/* Main section: image + text side by side */
.about-main-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: start;
    margin-bottom: 50px;
}

.about-image img {
    width: 100%;
    height: auto;
    display: block;
    cursor: pointer;
    transition: opacity 0.2s ease;
}

.about-image img:hover {
    opacity: 0.9;
}

.about-text {
    font-size: 18px;
    line-height: 1.8;
    color: #333;
}

.about-text p {
    margin-bottom: 1.5em;
}

.about-text p:last-child {
    margin-bottom: 0;
}

/* Masonry-style image grid - 3 columns */
.about-image-grid {
    column-count: 3;
    column-gap: 12px;
    margin-top: 40px;
}

.about-grid-item {
    margin: 0 0 12px 0;
    break-inside: avoid;
    display: block;
}

.about-grid-item img {
    width: 100%;
    height: auto;
    display: block;
    cursor: pointer;
    transition: opacity 0.2s ease;
}

.about-grid-item img:hover {
    opacity: 0.85;
}

/* Lightbox Styles */
.about-lightbox {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.95);
    z-index: 99999;
    align-items: center;
    justify-content: center;
}

.about-lightbox.active {
    display: flex;
}

.lightbox-content {
    max-width: 90vw;
    max-height: 90vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.lightbox-content img {
    max-width: 100%;
    max-height: 90vh;
    object-fit: contain;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.lightbox-content img.loaded {
    opacity: 1;
}

.lightbox-close {
    position: absolute;
    top: 20px;
    right: 25px;
    background: none;
    border: none;
    color: #fff;
    font-size: 45px;
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.2s;
    line-height: 1;
    padding: 0;
    z-index: 100001;
}

.lightbox-close:hover {
    opacity: 1;
}

.lightbox-prev,
.lightbox-next {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #fff;
    font-size: 60px;
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.2s;
    padding: 20px;
    z-index: 100001;
}

.lightbox-prev {
    left: 10px;
}

.lightbox-next {
    right: 10px;
}

.lightbox-prev:hover,
.lightbox-next:hover {
    opacity: 1;
}

/* Responsive adjustments */
@media (max-width: 1100px) {
    .about-image-grid {
        column-count: 2;
        column-gap: 10px;
    }

    .about-grid-item {
        margin-bottom: 10px;
    }
}

@media (max-width: 900px) {
    .about-main-section {
        grid-template-columns: 1fr;
        gap: 30px;
    }

    .about-content {
        padding: 30px 15px;
    }
}

@media (max-width: 600px) {
    .about-text {
        font-size: 16px;
    }

    .about-main-section {
        gap: 20px;
        margin-bottom: 40px;
    }

    .about-image-grid {
        column-count: 1;
    }

    .lightbox-prev,
    .lightbox-next {
        font-size: 40px;
        padding: 10px;
    }

    .lightbox-close {
        font-size: 35px;
        top: 15px;
        right: 15px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const lightbox = document.getElementById('about-lightbox');
    const lightboxImg = lightbox.querySelector('.lightbox-content img');
    const closeBtn = lightbox.querySelector('.lightbox-close');
    const prevBtn = lightbox.querySelector('.lightbox-prev');
    const nextBtn = lightbox.querySelector('.lightbox-next');

    // Collect all images from about page
    const images = Array.from(document.querySelectorAll('.about-image img, .about-grid-item img'));
    let currentIndex = 0;

    function openLightbox(index) {
        currentIndex = index;
        lightboxImg.classList.remove('loaded');
        lightboxImg.src = images[index].src;
        lightboxImg.onload = function() {
            lightboxImg.classList.add('loaded');
        };
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }

    function showPrev() {
        currentIndex = (currentIndex - 1 + images.length) % images.length;
        lightboxImg.classList.remove('loaded');
        lightboxImg.src = images[currentIndex].src;
        lightboxImg.onload = function() {
            lightboxImg.classList.add('loaded');
        };
    }

    function showNext() {
        currentIndex = (currentIndex + 1) % images.length;
        lightboxImg.classList.remove('loaded');
        lightboxImg.src = images[currentIndex].src;
        lightboxImg.onload = function() {
            lightboxImg.classList.add('loaded');
        };
    }

    // Add click handlers to all images
    images.forEach(function(img, index) {
        img.addEventListener('click', function() {
            openLightbox(index);
        });
    });

    // Lightbox controls
    closeBtn.addEventListener('click', closeLightbox);
    prevBtn.addEventListener('click', showPrev);
    nextBtn.addEventListener('click', showNext);

    // Close on background click
    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox || e.target.classList.contains('lightbox-content')) {
            closeLightbox();
        }
    });

    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (!lightbox.classList.contains('active')) return;

        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') showPrev();
        if (e.key === 'ArrowRight') showNext();
    });
});
</script>

<?php get_footer(); ?>
