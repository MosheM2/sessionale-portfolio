<?php
/**
 * TGM Plugin Activation configuration.
 * 
 * @package Sessionale_Portfolio
 */

if (!defined('ABSPATH')) exit;

add_action('tgmpa_register', 'sessionale_register_required_plugins');

function sessionale_register_required_plugins() {

    $plugins = array(
        array(
            'name'     => 'Complianz – GDPR/CCPA Cookie Consent',
            'slug'     => 'complianz-gdpr',
            'required' => false, // false = "Recommended", true = "Required"
        ),
        array(
            'name'     => 'OMGF | GDPR/DSGVO Compliant, Faster Google Fonts. Easy.',
            'slug'     => 'host-webfonts-local',
            'required' => false, // false = "Recommended", true = "Required"
        ),
    );

    $config = array(
        'id'           => 'sessionale-portfolio',      // Unique ID for hashing notices.
        'default_path' => '',                          // Default absolute path to bundled plugins.
        'menu'         => 'tgmpa-install-plugins',     // Menu slug.
        'has_notices'  => true,                        // Show admin notices.
        'dismissable'  => true,                        // User can dismiss the notice.
        'dismiss_msg'  => '',                          // If dismissable, message to display.
        'is_automatic' => true,                        // Automatically activate plugins after install.
        'message'      => '',                          // Message to output right before the plugins table.
        'strings'      => array(
            'page_title'                      => __('Install Recommended Plugins', 'sessionale-portfolio'),
            'menu_title'                      => __('Install Plugins', 'sessionale-portfolio'),
            'installing'                      => __('Installing Plugin: %s', 'sessionale-portfolio'),
            'updating'                        => __('Updating Plugin: %s', 'sessionale-portfolio'),
            'oops'                            => __('Something went wrong with the plugin API.', 'sessionale-portfolio'),
            'notice_can_install_required'     => _n_noop(
                'This theme requires the following plugin: %1$s.',
                'This theme requires the following plugins: %1$s.',
                'sessionale-portfolio'
            ),
            'notice_can_install_recommended'  => _n_noop(
                'This theme recommends the following plugin: %1$s.',
                'This theme recommends the following plugins: %1$s.',
                'sessionale-portfolio'
            ),
            'notice_ask_to_update'            => _n_noop(
                'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.',
                'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.',
                'sessionale-portfolio'
            ),
            'notice_ask_to_update_maybe'      => _n_noop(
                'There is an update available for: %1$s.',
                'There are updates available for the following plugins: %1$s.',
                'sessionale-portfolio'
            ),
            'notice_can_activate_required'    => _n_noop(
                'The following required plugin is currently inactive: %1$s.',
                'The following required plugins are currently inactive: %1$s.',
                'sessionale-portfolio'
            ),
            'notice_can_activate_recommended' => _n_noop(
                'The following recommended plugin is currently inactive: %1$s.',
                'The following recommended plugins are currently inactive: %1$s.',
                'sessionale-portfolio'
            ),
            'install_link'                    => _n_noop(
                'Begin installing plugin',
                'Begin installing plugins',
                'sessionale-portfolio'
            ),
            'update_link'                     => _n_noop(
                'Begin updating plugin',
                'Begin updating plugins',
                'sessionale-portfolio'
            ),
            'activate_link'                   => _n_noop(
                'Begin activating plugin',
                'Begin activating plugins',
                'sessionale-portfolio'
            ),
            'return'                          => __('Return to Recommended Plugins Installer', 'sessionale-portfolio'),
            'plugin_activated'                => __('Plugin activated successfully.', 'sessionale-portfolio'),
            'activated_successfully'          => __('The following plugin was activated successfully:', 'sessionale-portfolio'),
            'plugin_already_active'           => __('No action taken. Plugin %1$s was already active.', 'sessionale-portfolio'),
            'plugin_needs_higher_version'     => __('Plugin not activated. A higher version of %s is needed for this theme. Please update the plugin.', 'sessionale-portfolio'),
            'complete'                        => __('All plugins installed and activated successfully. %1$s', 'sessionale-portfolio'),
            'dismiss'                         => __('Dismiss this notice', 'sessionale-portfolio'),
            'notice_cannot_install_activate'  => __('There are one or more required or recommended plugins to install, update or activate.', 'sessionale-portfolio'),
            'contact_admin'                   => __('Please contact the administrator of this site for help.', 'sessionale-portfolio'),
        ),
    );

    tgmpa($plugins, $config);
}
