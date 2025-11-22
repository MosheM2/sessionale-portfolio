<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        // Initialize theme immediately to prevent FOUC
        (function() {
            const savedTheme = localStorage.getItem('sessionale-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = savedTheme || (prefersDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <nav class="main-navigation">
        <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
            <span class="burger-icon">
                <span></span>
                <span></span>
                <span></span>
            </span>
        </button>
        <?php
        wp_nav_menu(array(
            'theme_location' => 'primary',
            'menu_class'     => 'primary-menu',
            'menu_id'        => 'primary-menu',
            'container'      => false,
            'fallback_cb'    => false,
        ));
        ?>
    </nav>
</header>
