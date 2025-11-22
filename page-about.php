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
    margin-bottom: 60px;
}

.about-image img {
    width: 100%;
    height: auto;
    display: block;
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

/* Image grid for additional images */
.about-image-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 40px;
}

.about-grid-item {
    margin: 0;
}

.about-grid-item img {
    width: 100%;
    height: auto;
    display: block;
}

/* Responsive adjustments */
@media (max-width: 900px) {
    .about-main-section {
        grid-template-columns: 1fr;
        gap: 30px;
    }

    .about-content {
        padding: 30px 15px;
    }

    .about-image-grid {
        grid-template-columns: 1fr;
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
}
</style>

<?php get_footer(); ?>
