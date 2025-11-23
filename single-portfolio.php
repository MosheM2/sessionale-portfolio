<?php
/**
 * Single Portfolio Project Template (Custom Post Type)
 *
 * @package Portfolio_Migration
 */

get_header();

// Get layout setting
$layout = get_post_meta(get_the_ID(), 'portfolio_layout', true);
if (empty($layout)) {
    $layout = 'auto';
}

// Get gallery from meta
$gallery = get_post_meta(get_the_ID(), '_portfolio_gallery', true);
?>

<main class="site-content single-portfolio-content">
    <?php
    while (have_posts()) : the_post();
        $year = get_post_meta(get_the_ID(), 'portfolio_year', true);
        $client = get_post_meta(get_the_ID(), 'portfolio_client', true);
        $categories = get_the_terms(get_the_ID(), 'portfolio_category');
        $description = get_post_meta(get_the_ID(), 'portfolio_description', true);
        ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class('single-portfolio layout-' . esc_attr($layout)); ?> data-layout="<?php echo esc_attr($layout); ?>">

            <!-- Project Header -->
            <header class="project-header">
                <h1 class="project-title"><?php the_title(); ?></h1>

                <?php if ($year || $client || ($categories && !is_wp_error($categories))) : ?>
                    <div class="project-meta">
                        <?php if ($year) : ?>
                            <span class="project-year"><?php echo esc_html($year); ?></span>
                        <?php endif; ?>

                        <?php if ($client) : ?>
                            <span class="project-client"><?php echo esc_html($client); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </header>

            <!-- Project Content (images, videos, text) -->
            <div class="project-gallery">
                <div class="project-content">
                    <?php sessionale_render_gallery($gallery, $description); ?>
                </div>
            </div>

            <!-- Project Navigation -->
            <nav class="project-navigation">
                <div class="nav-links">
                    <?php
                    $prev_post = get_previous_post();
                    $next_post = get_next_post();
                    ?>

                    <div class="nav-previous">
                        <?php if ($prev_post) : ?>
                            <a href="<?php echo get_permalink($prev_post); ?>" class="nav-link">
                                <span class="nav-arrow">&larr;</span>
                                <span class="nav-label"><?php _e('Previous', 'sessionale-portfolio'); ?></span>
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="nav-home">
                        <a href="<?php echo home_url('/'); ?>" class="nav-link nav-link-home">
                            <?php _e('All Projects', 'sessionale-portfolio'); ?>
                        </a>
                    </div>

                    <div class="nav-next">
                        <?php if ($next_post) : ?>
                            <a href="<?php echo get_permalink($next_post); ?>" class="nav-link">
                                <span class="nav-label"><?php _e('Next', 'sessionale-portfolio'); ?></span>
                                <span class="nav-arrow">&rarr;</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </nav>

        </article>

    <?php endwhile; ?>
</main>

<?php get_footer(); ?>
