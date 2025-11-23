<?php
/**
 * Footer Template
 *
 * @package Sessionale_Portfolio
 */

$settings = get_option('sessionale_portfolio_settings', array());
$social_links = isset($settings['social_links']) ? $settings['social_links'] : array();

// Social media icons (SVG)
$social_icons = array(
    'instagram' => '<svg viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>',
    'youtube' => '<svg viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>',
    'vimeo' => '<svg viewBox="0 0 24 24"><path d="M22.875 10.063c-2.442 5.217-8.337 12.319-12.063 12.319-3.672 0-4.203-7.831-6.208-13.043-.987-2.565-1.624-1.976-3.474-.681l-1.13-1.455c2.698-2.372 5.398-5.127 7.057-5.28 1.868-.179 3.018 1.098 3.448 3.832.568 3.593 1.362 9.17 2.748 9.17 1.08 0 3.741-4.424 3.878-6.006.243-2.316-1.703-2.386-3.392-1.663 2.673-8.754 13.793-7.142 9.136 2.807z"/></svg>',
    'linkedin' => '<svg viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>',
    'twitter' => '<svg viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
    'facebook' => '<svg viewBox="0 0 24 24"><path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/></svg>',
    'behance' => '<svg viewBox="0 0 24 24"><path d="M6.938 4.503c.702 0 1.34.06 1.92.188.577.13 1.07.33 1.485.61.41.28.733.65.96 1.12.225.47.34 1.05.34 1.73 0 .74-.17 1.36-.507 1.86-.338.5-.837.9-1.502 1.22.906.26 1.576.72 2.022 1.37.448.66.665 1.45.665 2.36 0 .75-.13 1.39-.41 1.93-.28.55-.67 1-1.16 1.35-.48.348-1.05.6-1.67.767-.61.165-1.252.254-1.91.254H0V4.51h6.938v-.007zM6.545 9.64c.543 0 .97-.13 1.29-.41.32-.28.483-.68.483-1.18 0-.3-.05-.55-.15-.75-.1-.2-.24-.36-.42-.48-.18-.12-.38-.21-.61-.265-.23-.053-.47-.077-.72-.077H3.41v3.16h3.136zm.13 5.25c.27 0 .53-.03.78-.09.25-.06.468-.16.65-.3.19-.14.33-.33.44-.57.11-.24.16-.54.16-.9 0-.7-.18-1.22-.56-1.54-.38-.32-.89-.48-1.53-.48H3.41v3.88h3.266zM15.5 13.78c.29.41.69.62 1.21.62.37 0 .69-.09.96-.27.27-.18.44-.35.5-.52h2.51c-.39 1.15-.96 1.97-1.7 2.46-.73.49-1.62.74-2.66.74-.72 0-1.37-.12-1.95-.36-.58-.24-1.07-.58-1.47-1.02-.4-.44-.71-.97-.92-1.58-.21-.62-.31-1.3-.31-2.04 0-.72.11-1.38.33-2 .22-.6.52-1.13.92-1.57.39-.43.87-.77 1.42-1.01.55-.24 1.16-.36 1.83-.36.72 0 1.36.13 1.91.41.55.27 1.01.65 1.38 1.13.37.47.65 1.03.83 1.66.18.63.27 1.31.27 2.04v.84h-5.81c.02.58.24 1.08.54 1.49zm2.1-4.15c-.27-.34-.65-.51-1.13-.51-.3 0-.56.05-.78.16-.22.11-.4.25-.54.42-.14.17-.25.36-.32.56-.07.2-.11.38-.13.56h3.52c-.06-.49-.27-.86-.62-1.19zM18.5 6.5h-5v1.5h5v-1.5z"/></svg>',
    'dribbble' => '<svg viewBox="0 0 24 24"><path d="M12 24c-6.627 0-12-5.373-12-12s5.373-12 12-12 12 5.373 12 12-5.373 12-12 12zm10.01-10.679c-.348-.113-3.137-.954-6.314-.438 1.326 3.637 1.866 6.602 1.969 7.201 2.536-1.718 4.348-4.458 4.345-6.763zm-6.063 7.623c-.151-.889-.736-3.968-2.154-7.667l-.065.021c-5.689 1.982-7.733 5.922-7.917 6.309 1.715 1.336 3.871 2.135 6.22 2.135 1.39 0 2.714-.283 3.916-.798zm-11.141-2.027c.233-.419 3.011-5.073 8.192-6.789.132-.044.264-.084.397-.121-.254-.576-.524-1.151-.807-1.718-4.987 1.491-9.823 1.428-10.262 1.42l-.005.267c0 2.626.988 5.026 2.485 6.941zm-2.557-9.18c.447.009 4.579.053 9.312-1.186-1.668-2.966-3.472-5.461-3.74-5.829-3.13 1.476-5.411 4.276-5.572 7.015zm7.466-7.616c.284.385 2.12 2.879 3.762 5.916 3.59-1.346 5.111-3.385 5.295-3.65-1.984-1.765-4.595-2.835-7.461-2.835-.533 0-1.059.044-1.596.119zm10.04 3.391c-.22.297-1.89 2.469-5.631 4.008.223.459.437.926.637 1.398.072.17.143.34.212.51 3.369-.423 6.722.257 7.053.326-.016-2.364-.871-4.538-2.271-6.242z"/></svg>'
);
?>

<footer class="site-footer">
    <div class="footer-content">
        <?php if (!empty($social_links)) : ?>
            <div class="social-links">
                <?php foreach ($social_links as $platform => $url) :
                    if (!empty($url) && isset($social_icons[$platform])) :
                ?>
                    <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr(ucfirst($platform)); ?>">
                        <?php echo $social_icons[$platform]; ?>
                    </a>
                <?php
                    endif;
                endforeach; ?>
            </div>
        <?php endif; ?>

        <?php
        // Display footer menu if it exists
        if (has_nav_menu('footer')) :
            wp_nav_menu(array(
                'theme_location' => 'footer',
                'container'      => 'nav',
                'container_class' => 'footer-menu',
                'menu_class'     => 'footer-menu-list',
                'depth'          => 1,
                'fallback_cb'    => false,
            ));
        endif;
        ?>

        <p class="footer-text">
            &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>
        </p>
        <p class="footer-text footer-powered">
            <?php
            printf(
                esc_html__('Powered by %s', 'sessionale-portfolio'),
                '<a href="https://www.sessionale.de" target="_blank" rel="noopener">Sessionale</a>'
            );
            ?>
        </p>
    </div>
</footer>

<!-- Dark Mode Toggle Button -->
<button class="theme-toggle" id="theme-toggle" aria-label="Toggle dark mode">
    <span class="theme-toggle-icon">
        <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="5"></circle>
            <line x1="12" y1="1" x2="12" y2="3"></line>
            <line x1="12" y1="21" x2="12" y2="23"></line>
            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
            <line x1="1" y1="12" x2="3" y2="12"></line>
            <line x1="21" y1="12" x2="23" y2="12"></line>
            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
        </svg>
        <svg class="icon-moon" viewBox="0 0 24 24" fill="currentColor">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
        </svg>
    </span>
    <span class="theme-toggle-text"><?php _e('Dark Mode', 'sessionale-portfolio'); ?></span>
</button>

<script>
// Dark Mode Toggle
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('theme-toggle');
    const toggleText = themeToggle.querySelector('.theme-toggle-text');

    function updateToggleText() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        toggleText.textContent = currentTheme === 'dark' ? '<?php _e('Light Mode', 'sessionale-portfolio'); ?>' : '<?php _e('Dark Mode', 'sessionale-portfolio'); ?>';
    }

    themeToggle.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('sessionale-theme', newTheme);
        updateToggleText();
    });

    // Set initial text
    updateToggleText();

    // Listen for system preference changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
        if (!localStorage.getItem('sessionale-theme')) {
            const newTheme = e.matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', newTheme);
            updateToggleText();
        }
    });
});

// Mobile Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.menu-toggle');
    const primaryMenu = document.querySelector('.primary-menu');
    const body = document.body;

    if (menuToggle && primaryMenu) {
        menuToggle.addEventListener('click', function() {
            this.classList.toggle('is-active');
            primaryMenu.classList.toggle('is-open');
            body.classList.toggle('menu-open');

            // Update aria-expanded
            const isExpanded = this.classList.contains('is-active');
            this.setAttribute('aria-expanded', isExpanded);
        });

        // Close menu when clicking a link
        primaryMenu.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function() {
                menuToggle.classList.remove('is-active');
                primaryMenu.classList.remove('is-open');
                body.classList.remove('menu-open');
                menuToggle.setAttribute('aria-expanded', 'false');
            });
        });
    }
});
</script>

<?php wp_footer(); ?>

</body>
</html>
