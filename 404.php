<?php
/**
 * 404 Error Page Template
 *
 * @package Portfolio_Migration
 */

get_header(); ?>

<main class="site-content">
    <div class="error-404" style="text-align: center; padding: 80px 20px;">
        
        <h1 class="page-title" style="font-size: 48px; margin-bottom: 20px;">
            <?php _e('404', 'sessionale-portfolio'); ?>
        </h1>
        
        <h2 style="font-size: 24px; margin-bottom: 20px; color: #666;">
            <?php _e('Page Not Found', 'sessionale-portfolio'); ?>
        </h2>
        
        <p style="margin-bottom: 30px; color: #666;">
            <?php _e('The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.', 'sessionale-portfolio'); ?>
        </p>
        
        <a href="<?php echo home_url('/'); ?>" class="button" style="display: inline-block; padding: 15px 30px; background: #333; color: #fff; border-radius: 4px; text-decoration: none;">
            <?php _e('Back to Home', 'sessionale-portfolio'); ?>
        </a>
        
    </div>
</main>

<?php get_footer(); ?>
