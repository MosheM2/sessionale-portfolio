<?php
/**
 * Portfolio Archive Template
 *
 * @package Portfolio_Migration
 */

get_header(); ?>

<main class="site-content">
    
    <header class="page-header">
        <h1 class="page-title">
            <?php
            if (is_tax('portfolio_category')) {
                single_term_title();
            } else {
                _e('Portfolio', 'sessionale-portfolio');
            }
            ?>
        </h1>
        
        <?php if (is_tax('portfolio_category') && term_description()) : ?>
            <div class="taxonomy-description">
                <?php echo term_description(); ?>
            </div>
        <?php endif; ?>
    </header>

    <div class="portfolio-grid">
        <?php
        if (have_posts()) :
            while (have_posts()) : the_post();
                get_template_part('template-parts/content', 'portfolio');
            endwhile;
        else :
            ?>
            <div class="no-projects">
                <p><?php _e('No projects found in this category.', 'sessionale-portfolio'); ?></p>
            </div>
            <?php
        endif;
        ?>
    </div>
    
    <?php
    // Pagination
    the_posts_pagination(array(
        'mid_size'  => 2,
        'prev_text' => __('&larr; Previous', 'sessionale-portfolio'),
        'next_text' => __('Next &rarr;', 'sessionale-portfolio'),
    ));
    ?>

</main>

<?php get_footer(); ?>
