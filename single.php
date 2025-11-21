<?php
/**
 * Single Portfolio Project Template
 *
 * @package Portfolio_Migration
 */

get_header(); ?>

<main class="site-content">
    <?php
    while (have_posts()) : the_post();
        $year = get_post_meta(get_the_ID(), 'portfolio_year', true);
        $client = get_post_meta(get_the_ID(), 'portfolio_client', true);
        $categories = get_the_terms(get_the_ID(), 'portfolio_category');
        ?>
        
        <article id="post-<?php the_ID(); ?>" <?php post_class('single-portfolio'); ?>>
            
            <div class="project-header">
                <h1 class="project-title"><?php the_title(); ?></h1>
                
                <div class="project-meta">
                    <?php if ($year) : ?>
                        <span class="project-year"><?php echo esc_html($year); ?></span>
                    <?php endif; ?>
                    
                    <?php if ($client) : ?>
                        <span class="project-client"> | <?php echo esc_html($client); ?></span>
                    <?php endif; ?>
                    
                    <?php if ($categories && !is_wp_error($categories)) : ?>
                        <span class="project-categories"> | 
                            <?php
                            $cat_names = array();
                            foreach ($categories as $category) {
                                $cat_names[] = $category->name;
                            }
                            echo esc_html(implode(', ', $cat_names));
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <?php if (has_excerpt()) : ?>
                    <div class="project-description">
                        <?php the_excerpt(); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="project-gallery">
                <?php if (has_post_thumbnail()) : ?>
                    <div class="project-featured-image">
                        <?php the_post_thumbnail('portfolio-large'); ?>
                    </div>
                <?php endif; ?>
                
                <div class="project-content">
                    <?php the_content(); ?>
                </div>
            </div>
            
            <div class="project-navigation">
                <div class="nav-links">
                    <?php
                    $prev_post = get_previous_post();
                    $next_post = get_next_post();
                    
                    if ($prev_post) :
                        ?>
                        <div class="nav-previous">
                            <a href="<?php echo get_permalink($prev_post); ?>">
                                ← <?php _e('Previous Project', 'sessionale-portfolio'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="nav-home">
                        <a href="<?php echo home_url('/'); ?>">
                            <?php _e('Back to Portfolio', 'sessionale-portfolio'); ?>
                        </a>
                    </div>
                    
                    <?php if ($next_post) : ?>
                        <div class="nav-next">
                            <a href="<?php echo get_permalink($next_post); ?>">
                                <?php _e('Next Project', 'sessionale-portfolio'); ?> →
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </article>
        
    <?php endwhile; ?>
</main>

<?php get_footer(); ?>
