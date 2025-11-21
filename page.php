<?php
/**
 * Page Template
 *
 * @package Portfolio_Migration
 */

get_header(); ?>

<main class="site-content">
    <?php
    while (have_posts()) : the_post();
        ?>
        
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            
            <header class="entry-header" style="max-width: 900px; margin: 0 auto 40px;">
                <h1 class="entry-title" style="font-size: 36px; margin-bottom: 20px;">
                    <?php the_title(); ?>
                </h1>
            </header>
            
            <?php if (has_post_thumbnail()) : ?>
                <div class="entry-thumbnail" style="max-width: 1200px; margin: 0 auto 40px;">
                    <?php the_post_thumbnail('large'); ?>
                </div>
            <?php endif; ?>
            
            <div class="entry-content" style="max-width: 900px; margin: 0 auto; line-height: 1.8;">
                <?php
                the_content();
                
                wp_link_pages(array(
                    'before' => '<div class="page-links">' . __('Pages:', 'sessionale-portfolio'),
                    'after'  => '</div>',
                ));
                ?>
            </div>
            
        </article>
        
    <?php endwhile; ?>
</main>

<?php get_footer(); ?>
