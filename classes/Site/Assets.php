<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

class PUM_Site_Assets {

	/**
	 * @var
	 */
	public static $cache_url;

	/**
	 * @var
	 */
	public static $suffix;

	/**
	 * @var
	 */
	public static $js_url;

	/**
	 * @var
	 */
	public static $css_url;

	/**
	 * @var array
	 */
	public static $enqueued_scripts = array();

	/**
	 * @var array
	 */
	public static $enqueued_styles = array();

	/**
	 * @var bool
	 */
	public static $scripts_registered = false;

	/**
	 * @var bool
	 */
	public static $styles_registered = false;

	/**
	 * @var bool Use minified libraries if SCRIPT_DEBUG is turned off.
	 */
	public static $debug;

	/**
	 * Initialize
	 */
	public static function init() {
		$upload_dir      = wp_upload_dir();
		self::$cache_url = trailingslashit( $upload_dir['baseurl'] ) . 'pum';
		self::$debug     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
		self::$suffix    = self::$debug ? '' : '.min';
		self::$js_url    = Popup_Maker::$URL . 'assets/js/';
		self::$css_url   = Popup_Maker::$URL . 'assets/css/';

		// Register assets early.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_styles' ), 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_scripts' ), 1 );

		// Localize after popups rendered in PUM_Site_Popups
		add_action( 'wp_footer', array( __CLASS__, 'localize_scripts' ) );

		// Checks preloaded popups in the head for which assets to enqueue.
		add_action( 'popmake_preload_popup', array( __CLASS__, 'enqueue_popup_assets' ) );

		// Allow forcing assets to load.
		add_action( 'wp_head', array( __CLASS__, 'check_force_script_loading' ) );
	}

	/**
	 * @param int $popup_id
	 */
	public static function enqueue_popup_assets( $popup_id = 0 ) {
		/**
		 * TODO Replace this with a pum_get_popup function after new Popup model is in place.
		 *
		 * $popup = pum_get_popup( $popup_id );
		 *
		 * if ( ! pum_is_popup( $popup ) ) {
		 *        return;
		 * }
		 */

		$popup = new PUM_Popup( $popup_id );

		wp_enqueue_script( 'popup-maker-site' );
		wp_enqueue_style( 'popup-maker-site' );

		if ( $popup->mobile_disabled() || $popup->tablet_disabled() ) {
			wp_enqueue_script( 'mobile-detect' );
		}

		/**
		 * TODO Implement this in core $popup model & advanced targeting conditions.
		 *
		 * if ( $popup->has_condition( array(
		 *    'device_is_mobile',
		 *    'device_is_phone',
		 *    'device_is_tablet',
		 *    'device_is_brand',
		 * ) ) ) {
		 *    self::enqueue_script( 'mobile-detect' );
		 * }
		 */
	}

	/**
	 * Register JS.
	 */
	public static function register_scripts() {
		self::$scripts_registered = true;

		wp_register_script( 'mobile-detect', self::$js_url . 'mobile-detect' . self::$suffix . '.js', null, '1.3.3', true );

		if ( PUM_AssetCache::writeable() ) {
			$cached = get_option( 'pum-has-cached-js' );

			if ( ! $cached || self::$debug ) {
				PUM_AssetCache::cache_js();
				$cached = get_option( 'pum-has-cached-js' );
			}

			// check for multisite
			global $blog_id;
			$is_multisite = ( is_multisite() ) ? '-' . $blog_id : '';

			wp_register_script( 'popup-maker-site', self::$cache_url . '/pum-site-scripts' . $is_multisite . '.js?defer&generated=' . $cached, array(
				'jquery',
				'jquery-ui-core',
				'jquery-ui-position',
			), Popup_Maker::$VER, true );
		} else {
			wp_register_script( 'popup-maker-site', self::$js_url . 'site' . self::$suffix . '.js?defer', array(
				'jquery',
				'jquery-ui-core',
				'jquery-ui-position',
			), Popup_Maker::$VER, true );
		}

		if ( popmake_get_option( 'enable_easy_modal_compatibility_mode', false ) ) {
			wp_register_script( 'popup-maker-easy-modal-importer-site', self::$js_url . 'popup-maker-easy-modal-importer-site' . self::$suffix . '?defer', array( 'popup-maker-site' ), POPMAKE_VERSION, true );
		}
	}

	/**
	 * Localize scripts if enqueued.
	 */
	public static function localize_scripts() {
		if ( wp_script_is( 'popup-maker-site' ) ) {
			wp_localize_script( 'popup-maker-site', 'pum_vars', apply_filters( 'pum_vars', array(
				'version'          => Popup_Maker::$VER,
				'ajaxurl'          => admin_url( 'admin-ajax.php' ),
				'restapi'          => function_exists( 'rest_url' ) ? esc_url_raw( rest_url( 'pum/v1' ) ) : false,
				'rest_nonce'       => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : null,
				'default_theme'    => (string) popmake_get_default_popup_theme(),
				'debug_mode'       => Popup_Maker::debug_mode(),
				'popups'           => self::get_popup_settings(),
				'disable_tracking' => popmake_get_option( 'disable_popup_open_tracking' ),
				'home_url'         => home_url(),
			) ) );

			// TODO Remove all trace usages of these in JS so they can be removed.
			// @deprecated 1.4 Use pum_vars instead.
			wp_localize_script( 'popup-maker-site', 'ajaxurl', admin_url( 'admin-ajax.php' ) );

			wp_localize_script( 'popup-maker-site', 'pum_debug_vars', apply_filters( 'pum_debug_vars', array(
				'debug_mode_enabled'     => __( 'Popup Maker', 'popup-maker' ) . ': ' . __( 'Debug Mode Enabled', 'popup-maker' ),
				'debug_started_at'       => __( 'Debug started at:', 'popup-maker' ),
				'debug_more_info'        => sprintf( __( 'For more information on how to use this information visit %s', 'popup-maker' ), 'http://docs.wppopupmaker.com/?utm_medium=js-debug-info&utm_campaign=ContextualHelp&utm_source=browser-console&utm_content=more-info' ),
				'global_info'            => __( 'Global Information', 'popup-maker' ),
				'localized_vars'         => __( 'Localized variables', 'popup-maker' ),
				'popups_initializing'    => __( 'Popups Initializing', 'popup-maker' ),
				'popups_initialized'     => __( 'Popups Initialized', 'popup-maker' ),
				'single_popup_label'     => __( 'Popup: #', 'popup-maker' ),
				'theme_id'               => __( 'Theme ID: ', 'popup-maker' ),
				'label_method_call'      => __( 'Method Call:', 'popup-maker' ),
				'label_method_args'      => __( 'Method Arguments:', 'popup-maker' ),
				'label_popup_settings'   => __( 'Settings', 'popup-maker' ),
				'label_triggers'         => __( 'Triggers', 'popup-maker' ),
				'label_cookies'          => __( 'Cookies', 'popup-maker' ),
				'label_delay'            => __( 'Delay:', 'popup-maker' ),
				'label_conditions'       => __( 'Conditions', 'popup-maker' ),
				'label_cookie'           => __( 'Cookie:', 'popup-maker' ),
				'label_settings'         => __( 'Settings:', 'popup-maker' ),
				'label_selector'         => __( 'Selector:', 'popup-maker' ),
				'label_mobile_disabled'  => __( 'Mobile Disabled:', 'popup-maker' ),
				'label_tablet_disabled'  => __( 'Tablet Disabled:', 'popup-maker' ),
				'label_display_settings' => __( 'Display Settings:', 'popup-maker' ),
				'label_close_settings'   => __( 'Close Settings:', 'popup-maker' ),
				'label_event'            => __( 'Event: %s', 'popup-maker' ),
				'triggers'               => PUM_Triggers::instance()->dropdown_list(),
				'cookies'                => PUM_Cookies::instance()->dropdown_list(),
			) ) );
		}
	}

	public static function get_popup_settings() {

		// @TODO Left off here.
		// This is running too early and popups not loaded yet apparently.
		$loaded = PUM_Site_Popups::get_loaded_popups();

		$settings = array();

		$current_popup = PUM_Site_Popups::current_popup();

		if ( $loaded->have_posts() ) {
			while ( $loaded->have_posts() ) : $loaded->next_post();
				PUM_Site_Popups::current_popup( $loaded->post );
				$popup                  = pum_get_popup( $loaded->post->ID );
				$settings[ $popup->ID ] = $popup->get_public_settings();
			endwhile;

			PUM_Site_Popups::current_popup( $current_popup );
		}

		return $settings;
	}

	/**
	 * Register CSS.
	 */
	public static function register_styles() {
		self::$styles_registered = true;

		if ( PUM_AssetCache::writeable() ) {
			$cached = get_option( 'pum-has-cached-css' );

			if ( ! $cached || self::$debug ) {
				PUM_AssetCache::cache_css();
				$cached = get_option( 'pum-has-cached-css' );
			}

			// check for multisite
			global $blog_id;
			$is_multisite = ( is_multisite() ) ? '-' . $blog_id : '';

			wp_register_style( 'popup-maker-site', self::$cache_url . '/pum-site-styles' . $is_multisite . '.css?generated=' . $cached, array(), Popup_Maker::$VER );
		} else {
			wp_register_style( 'popup-maker-site', self::$css_url . 'site' . self::$suffix . '.css', array(), Popup_Maker::$VER );
			self::inline_styles();
		}
	}

	/**
	 * Render popup inline styles.
	 */
	public static function inline_styles() {
		if ( ( current_action() == 'wp_head' && popmake_get_option( 'disable_popup_theme_styles', false ) ) || ( current_action() == 'admin_head' && ! popmake_is_admin_popup_page() ) ) {
			return;
		}

		wp_add_inline_style( 'popup-maker-site', PUM_AssetCache::generate_css() );
	}

	/**
	 * Defers loading of scripts with ?defer parameter in url.
	 *
	 * @param string $url URL being cleaned
	 *
	 * @return string $url
	 */
	public static function defer_js_url( $url ) {
		if ( false === strpos( $url, '.js?defer' ) ) {
			// not our file
			return $url;
		}

		return "$url' defer='defer";
	}

	/**
	 *
	 */
	public static function check_force_script_loading() {
		global $wp_query;
		if ( ! empty( $wp_query->post ) && has_shortcode( $wp_query->post->post_content, 'popup' ) || ( defined( "POPMAKE_FORCE_SCRIPTS" ) && POPMAKE_FORCE_SCRIPTS ) ) {
			wp_enqueue_script( 'popup-maker-site' );
			wp_enqueue_style( 'popup-maker-site' );
		}
	}
}
