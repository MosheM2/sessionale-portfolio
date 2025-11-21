<footer class="site-footer">
    <div class="footer-content">
        <p class="footer-text">
            &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>
            <?php if (is_active_sidebar('footer-1')) : ?>
                | <?php dynamic_sidebar('footer-1'); ?>
            <?php endif; ?>
        </p>
        <p class="footer-text">
            <?php
            printf(
                esc_html__('Powered by %s', 'sessionale-portfolio'),
                '<a href="https://wordpress.org/" target="_blank" rel="noopener">WordPress</a>'
            );
            ?>
        </p>
    </div>
</footer>

<?php wp_footer(); ?>

</body>
</html>
