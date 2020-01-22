<?php
/*
Plugin Name:       GitHub Icon Link
Version:           1.0.0
Plugin URI:        https://github.com/szepeviktor/github-link
Description:       Displays GitHub icon link on the Plugins page for a given <code>GitHub Plugin URI</code> plugin header.
License:           The MIT License (MIT)
Author:            Viktor Szépe
Domain Path:       /languages
Text Domain:       github-link
GitHub Plugin URI: https://github.com/szepeviktor/github-link
*/

/**
 * Exit if called directly.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

class GitHub_Link {

    /**
     * GitHub_Link constructor.
     */
    private function __construct() {
    }

    /**
     * GitHub_Link init function.
     */
    public static function init() {
      $self = new self();
      $self->init_hooks();
    }


    function init_hooks() {
        include_once ABSPATH . '/wp-admin/includes/plugin.php';

        $this->extra_plugin_headers = [ 'GitHub Plugin URI', 'GitLab Plugin URI', 'Bitbucket Plugin URI' ];

        add_filter( "extra_plugin_headers", [ &$this, "extra_plugin_headers" ] );

        $installed_plugins = get_plugins();

        foreach ( $installed_plugins as $plugin_slug => $plugin_data ) {
          add_filter( "plugin_action_links_{$plugin_slug}", [ &$this, 'plugin_action_links' ], 1000, 4 );
          add_filter( "network_admin_plugin_action_links_{$plugin_slug}", [ &$this, 'plugin_action_links' ], 1000, 4 );
        }

				add_filter( 'github_updater_set_options', [ &$this, 'github_updater_set_options' ], 15, 0 );

        load_plugin_textdomain( 'github-link', false, dirname( __FILE__ ) . '/languages' );
    }

    /**
     * Add custom git plugin headers
     *
     * @param $extra_headers
     *
     * @return array
     */
    function extra_plugin_headers( $extra_headers ) {
        return $extra_headers + $this->extra_plugin_headers;
    }

    /**
    * Add an additional element to the plugin action links.
    *
    * @param $actions
    * @param $plugin_file
    * @param $plugin_data
    * @param $context
    *
    * @return array
    */
    function plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {

        // No GitHub data during search installed plugins.
        if ( 'search' === $context ) {
          return $actions;
        }

        $link_template = '<a href="%s" title="%s" target="_blank" style="color: #32373c;"><img src="%s" style="width: 16px; height: 16px; margin-top: 4px; padding-right: 4px; float: none;" height="16" width="16" alt="%s" /></a>';

        foreach ( $this->extra_plugin_headers as $header ) {
          if ( ! empty( $plugin_data[ $header ] ) ) {
            $githost_name = preg_replace( '/ Plugin URI$/', '', $header );
            $new_action   = [
              strtolower( $githost_name ) => sprintf(
                $link_template,
                $plugin_data[ $header ],
                __( 'Visit ' . $githost_name . ' repository', 'github-link' ),
                plugins_url( 'icon/' . $githost_name . '-Mark-32px.png', __FILE__ ),
                $githost_name
              ),
            ];
            $actions      = $new_action + $actions;
          }
        }

        $plugin_page = $this->get_plugin_page( $plugin_file );
        if ( false !== $plugin_page ) {
          $new_action  = [
            'wordpress_org' => sprintf(
              $link_template,
              $plugin_page,
              __( 'Visit WordPress.org Plugin Page', 'github-link' ),
              plugins_url( 'icon/' . 'wordpress-logo-32.png', __FILE__ ),
              'wp_org',
              ''
            ),
          ];
            $actions = $new_action + $actions;
        }

        return $actions;
    }

    /**
     * Check if a plugin is a official WP.org plugin.
     *
     * @param string $slug       eg askimet/askimet.php
     * @return boolean $on_wporg True if is on WP.org
     */
    function is_on_wporg( $slug ) {
        $on_wporg = false;
        _maybe_update_plugins();
        $plugin_state = get_site_transient( 'update_plugins' );
        if ( isset( $plugin_state->response[ $slug ] )
        || isset( $plugin_state->no_update[ $slug ] )
        ) {
          $on_wporg = true;
        }
        return $on_wporg;
    }

    /**
     * Get WP.org plugin page.
     *
     * @param string $slug eg askimet/askimet.php
     * @return mixed $plugin_page URL if it has a WP.org page (default: false)
     */
    function get_plugin_page( $slug ) {
        $on_wporg = $this->is_on_wporg( $slug );
        if ( $on_wporg ) {
          $plugin_page = '';
          _maybe_update_plugins();
          $plugin_state = get_site_transient( 'update_plugins' );
          if ( isset( $plugin_state->response[ $slug ] )
          && property_exists( $plugin_state->response[ $slug ], 'url' )
          ) {
              $plugin_page = $plugin_state->response[ $slug ]->url;
          } elseif ( isset( $plugin_state->no_update[ $slug ] )
              && property_exists( $plugin_state->no_update[ $slug ], 'url' )
            ) {
              $plugin_page = $plugin_state->no_update[ $slug ]->url;
          }
          return $plugin_page;
        }
        return false;
    }

	/**
	 * Callback function used to override GitHub Updater default options.
	 *
	 * @link https://github.com/afragen/github-updater/wiki/Developer-Hooks
	 *
	 * @return array
	 */
		function github_updater_set_options() {
			return [
				'branch_switch'    => '1'
			];
		}

}

GitHub_Link::init();
