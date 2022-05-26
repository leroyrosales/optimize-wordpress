<?php
/**
 *
 * Plugin Name: Optimize WordPress
 * Version: 1.0
 * Description: A collection of scripts to optimize any WordPress site.
 * Plugin URI: https://github.com/leroyrosales/optimize-wordpress
 *
 */

if ( ! defined( 'ABSPATH' ) || ! class_exists( 'OptimizeWordPress') ) return;

Class OptimizeWordPress {

	private static $instance = null;

	public function __construct() {

		add_action( 'init', [ $this, 'initialize' ], 0, 0 );

		// Removes block editor
		add_filter( 'use_block_editor_for_post', '__return_false' );
		// Remove Unnecessary Code from wp_head
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'start_post_rel_link' );
		remove_action( 'wp_head', 'index_rel_link' );
		remove_action( 'wp_head', 'adjacent_posts_rel_link' );
		remove_action( 'wp_head', 'feed_links', 2 );

		//Remove JQuery migrate
		add_action( 'wp_default_scripts', [ $this, 'removes_jquery_migrate' ] );

		// Remove oEmbed
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		remove_action('rest_api_init', 'wp_oembed_register_route');
		remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);

		// Disable Trackbacks and Pings
		add_action( 'pre_ping', [ $this, 'c45_internal_pingbacks' ] );
		add_filter( 'wp_headers', [ $this, 'c45_x_pingback' ] );
		add_filter( 'bloginfo_url', [ $this, 'c45_pingback_url' ] ) ;
		add_filter( 'bloginfo', [ $this, 'c45_pingback_url' ] ) ;
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'xmlrpc_methods', [ $this, 'c45_xmlrpc_methods' ] );

		// Disable emojis
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'tiny_mce_plugins', 'disable_emojis_tinymce' );
		add_filter( 'wp_resource_hints', 'disable_emojis_remove_dns_prefetch', 10, 2 );


		add_filter( 'style_loader_tag', [ $this, 'clean_style_tag' ] );

	}

	public static function instance() {
		self::$instance ?? self::$instance;

		return self::$instance = new OptimizeWordPress();
	}

	public function initialize() {
		// Bail early if called directly from functions.php or plugin file.
		if( ! did_action( 'plugins_loaded' ) ) return;

		$this->optimization_scripts();

	}

	public static function removes_jquery_migrate( $scripts ) {
		if ( ! is_admin() && isset( $scripts->registered['jquery'] )) {
			$script = $scripts->registered['jquery'];

			if ( $script->deps ) {
				// Check whether the script has any dependencies
				$script->deps = array_diff( $script->deps, array(
					'jquery-migrate'
				) );
			}
		}
	}

	// Disable internal pingbacks
	public static function c45_internal_pingbacks( &$links ) {
		foreach ( $links as $l => $link ) {
			if ( 0 === strpos( $link, get_option( 'home' ) ) ) {
				unset( $links[$l] );
			}
		}
	}

	// Disable x-pingback
	public static function c45_x_pingback( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	}

	// Disable XML-RPC methods
	public static function c45_xmlrpc_methods( $methods ) {
		unset( $methods['pingback.ping'] );
		return $methods;
	}

	// Remove pingback URLs
	public static function c45_pingback_url( $output, $show='') {
		if ( $show == 'pingback_url' ) $output = '';
		return $output;
	}

	// Filter function used to remove the tinymce emoji plugin.
	public static function disable_emojis_tinymce( $plugins ) {
		if ( is_array( $plugins ) ) {
			return array_diff( $plugins, array( 'wpemoji' ) );
		} else {
			return array();
		}
	}

	// Remove emoji CDN hostname from DNS prefetching hints.
	public static function disable_emojis_remove_dns_prefetch( $urls, $relation_type ) {
		if ( 'dns-prefetch' == $relation_type ) {
			/** This filter is documented in wp-includes/formatting.php */
			$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );
			$urls = array_diff( $urls, array( $emoji_svg_url ) );
		}
		return $urls;
	}

	// Clean up output of stylesheet <link> tags
	public static function clean_style_tag( $input ) {
		preg_match_all("!<link rel='stylesheet'\s?(id='[^']+')?\s+href='(.*)' type='text/css' media='(.*)' />!", $input, $matches);
		if (empty($matches[2])) {
			return $input;
		}
		// Only display media if it is meaningful
		$media = $matches[3][0] !== '' && $matches[3][0] !== 'all' ? ' media="' . $matches[3][0] . '"' : '';
		return '<link rel="stylesheet" href="' . $matches[2][0] . '"' . $media . '>' . "\n";
	}

} // End OptimizeWordPress class

OptimizeWordPress::instance();

