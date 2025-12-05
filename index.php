<?php
/**
 * Main Template File
 *
 * @package Portfolio_Migration
 */

// Debug: Log which template is being used
error_log('TEMPLATE DEBUG: index.php loaded');

get_header(); ?>

<main class="site-content">
    
    <?php if (!get_option('portfolio_migration_source_url') && current_user_can('manage_options')) : ?>
        <div class="import-notice">
            <h3><?php _e('Welcome to Sessionale Portfolio!', 'sessionale-portfolio'); ?></h3>
            <p><?php _e('Import your Adobe Portfolio content with one click.', 'sessionale-portfolio'); ?></p>
            <p>
                <a href="<?php echo admin_url('themes.php?page=portfolio-migration-import'); ?>" class="button button-primary">
                    <?php _e('Import Your Portfolio', 'sessionale-portfolio'); ?>
                </a>
            </p>
        </div>
    <?php endif; ?>

    <div class="portfolio-grid">
        <?php
        // Query portfolio projects
        $args = array(
            'post_type'      => 'portfolio',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC'
        );
        
        $portfolio_query = new WP_Query($args);
        
        if ($portfolio_query->have_posts()) :
            while ($portfolio_query->have_posts()) : $portfolio_query->the_post();
                get_template_part('template-parts/content', 'portfolio');
            endwhile;
            wp_reset_postdata();
        else :
            ?>
            <div class="no-projects">
                <p><?php _e('No projects found.', 'sessionale-portfolio'); ?></p>
                <?php if (current_user_can('edit_posts')) : ?>
                    <p>
                        <a href="<?php echo admin_url('post-new.php?post_type=portfolio'); ?>" class="button">
                            <?php _e('Add Your First Project', 'sessionale-portfolio'); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>
            <?php
        endif;
        ?>
    </div>

</main>

<?php get_footer(); ?>
