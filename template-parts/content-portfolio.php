<?php
/**
 * Template part for displaying portfolio items in the grid
 *
 * @package Portfolio_Migration
 */

$year = get_post_meta(get_the_ID(), 'portfolio_year', true);
$categories = get_the_terms(get_the_ID(), 'portfolio_category');
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('portfolio-item'); ?>>
    <a href="<?php the_permalink(); ?>">
        <?php if (has_post_thumbnail()) : ?>
            <?php the_post_thumbnail('portfolio-thumbnail', array('class' => 'portfolio-item-image')); ?>
        <?php else : ?>
            <div class="portfolio-item-placeholder" style="aspect-ratio: 16/9; background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);"></div>
        <?php endif; ?>
        
        <div class="portfolio-item-content">
            <h2 class="portfolio-item-title"><?php the_title(); ?></h2>
            
            <?php if ($year) : ?>
                <p class="portfolio-item-year"><?php echo esc_html($year); ?></p>
            <?php endif; ?>
            
            <?php if ($categories && !is_wp_error($categories)) : ?>
                <p class="portfolio-item-category">
                    <?php
                    $cat_names = array();
                    foreach ($categories as $category) {
                        $cat_names[] = $category->name;
                    }
                    echo esc_html(implode(', ', $cat_names));
                    ?>
                </p>
            <?php endif; ?>
        </div>
    </a>
</article>
