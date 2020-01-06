<?php
/*******************************************************************************
 * Copyright (c) 2019, Code Atlantic LLC
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Integrations
 */
class PUM_Integrations {

	/**
	 * @var PUM_Abstract_Integration|PUM_Abstract_Integration_Form[]
	 */
	public static $integrations = array();

	/**
	 * @var bool
	 */
	public static $preload_posts = false;

	public static $form_success;

	public static function init() {
		self::$integrations = [
			'ninjaforms'     => new PUM_Integration_Form_NinjaForms,
			'gravityforms'   => new PUM_Integration_Form_GravityForms,
//			'contactform7'   => new PUM_Integration_Form_ContactForm7,
//			'calderaforms'   => new PUM_Integration_Form_CalderaForms,
			'kingcomposer'   => new PUM_Integration_Builder_KingComposer,
			'visualcomposer' => new PUM_Integration_Builder_VisualComposer,
		];

		self::$preload_posts = isset( $_GET['page'] ) && $_GET['page'] == 'pum-settings';

		add_filter( 'pum_settings_fields', array( __CLASS__, 'settings_fields' ) );
		add_action( 'pum_preload_popup', array( __CLASS__, 'enqueue_assets' ) );
		add_filter( 'pum_registered_conditions', array( __CLASS__, 'register_conditions' ) );

		add_filter( 'pum_vars', array( __CLASS__, 'pum_vars' ) );

		add_action( 'init', array( __CLASS__, 'wp_init_late' ), 99 );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_filter( 'pum_popup_post_type_args', array( __CLASS__, 'popup_post_type_args' ) );
		add_filter( 'pum_generated_js', array( __CLASS__, 'generated_js' ) );
		add_filter( 'pum_generated_css', array( __CLASS__, 'generated_css' ) );
		add_filter( 'pum_popup_settings', array( __CLASS__, 'popup_settings' ), 10, 2 );

		PUM_Integration_GoogleFonts::init();

	}

	/**
	 * Checks if a 3rd party integration should be enabled.
	 *
	 * @param $key
	 *
	 * @return bool
	 */
	public static function enabled( $key ) {
		return (bool) isset( self::$integrations[ $key ] ) && self::$integrations[ $key ]->enabled();
	}

	/**
	 * @return PUM_Abstract_Integration_Form[]
	 */
	public static function get_enabled_form_integrations() {
		$enabled_forms = [];

		foreach ( self::$integrations as $object ) {
			if ( $object instanceof PUM_Abstract_Integration_Form && $object->enabled() ) {
				$enabled_forms[ $object->key ] = $object;
			}
		}

		return $enabled_forms;
	}

	/**
	 * Returns an array of value=>labels for select fields containing enabled form plugin integrations.
	 *
	 * @return array
	 */
	public static function get_enabled_forms_selectlist() {
		$enabled_form_integrations = self::get_enabled_form_integrations();

		$form_types = [];

		foreach ( $enabled_form_integrations as $key => $object ) {
			$form_types[ $key ] = $object->label();
		}

		return $form_types;
	}

	/**
	 * @param $key
	 *
	 * @return bool|PUM_Abstract_Integration|PUM_Abstract_Integration_Form
	 */
	public static function get_integration_info( $key ) {
		return isset( self::$integrations[ $key ] ) ? self::$integrations[ $key ] : false;
	}

	/**
	 * @param string $key
	 *
	 * @return array
	 */
	public static function get_form_provider_forms( $key ) {
		$integration = self::get_integration_info( $key );

		if ( ! ( $integration instanceof PUM_Abstract_Integration_Form ) || ! $integration->enabled() ) {
			return [];
		}

		return $integration->get_forms();
	}

	/**
	 * @param $key
	 * @param $id
	 *
	 * @return array|mixed
	 */
	public static function get_form_provider_form( $key, $id ) {
		$integration = self::get_integration_info( $key );

		if ( ! ( $integration instanceof PUM_Abstract_Integration_Form ) || ! $integration->enabled() ) {
			return [];
		}

		return $integration->get_form( $id );
	}


	/**
	 * @param $key
	 *
	 * @return array
	 */
	public static function get_form_provider_forms_selectlist( $key ) {
		$integration = self::get_integration_info( $key );

		if ( ! ( $integration instanceof PUM_Abstract_Integration_Form ) || ! $integration->enabled() ) {
			return [];
		}

		return $integration->get_form_selectlist();
	}

	/**
	 * Adds additional settings to help better integrate with 3rd party plugins.
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public static function settings_fields( $fields = array() ) {

		foreach ( self::$integrations as $key => $enabled ) {
			if ( ! $enabled ) {
				continue;
			}

			switch ( $key ) {

			}
		}

		return $fields;
	}

	public static function enqueue_assets( $popup_id = 0 ) {

		$popup = pum_get_popup( $popup_id );

		if ( ! pum_is_popup( $popup ) ) {
			return;
		}

		// Do stuff here.

	}

	public static function register_conditions( $conditions = array() ) {

		foreach ( self::$integrations as $key => $enabled ) {
			if ( ! $enabled ) {
				continue;
			}

			switch ( $key ) {


			}
		}

		return $conditions;
	}

	/**
	 * Runs during init
	 */
	public static function wp_init_late() {

		/**
		 * Force KingComposer support for popups.
		 */
		if ( self::enabled( 'kingcomposer' ) ) {
			global $kc;
			$kc->add_content_type( 'popup' );
		}
	}

	/**
	 * Runs during admin_init
	 */
	public static function admin_init() {
		if ( ! self::enabled( 'visualcomposer' ) && ( is_admin() && isset( $_GET['page'] ) && in_array( $_GET['page'], array(
					'vc_settings',
					'fl-builder-settings',
				) ) ) || pum_is_popup_editor() ) {
			add_filter( 'vc_role_access_with_post_types_get_state', '__return_true' );
			add_filter( 'vc_role_access_with_backend_editor_get_state', '__return_true' );
			add_filter( 'vc_role_access_with_frontend_editor_get_state', '__return_false' );
			add_filter( 'vc_check_post_type_validation', '__return_true' );
		}
	}

	public static function popup_post_type_args( $args = array() ) {

		if ( self::enabled( 'kingcomposer' ) && ( ( is_admin() && isset( $_GET['page'] ) && $_GET['page'] == 'kingcomposer' ) || pum_is_popup_editor() ) ) {
			$args = array_merge( $args, array(
				'public'              => true,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'show_in_nav_menus'   => false,
			) );
		}

		if ( self::enabled( 'visualcomposer' ) && ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) && ( ( isset( $_GET['page'] ) && in_array( $_GET['page'], array(
							'vc_settings',
							'fl-builder-settings',
						) ) ) || ( isset( $_POST['option_page'] ) && $_POST['option_page'] == 'wpb_js_composer_settings_general' ) || pum_is_popup_editor() ) ) ) {
			$args = array_merge( $args, array(
				'public'              => true,
				'exclude_from_search' => true,
				'publicly_queryable'  => false, // Was true, verify this isn't a problem.
				'show_in_nav_menus'   => false,
			) );
		}

		return $args;
	}


	/**
	 * @param array $js
	 *
	 * @return array
	 */
	public static function generated_js( $js = [] ) {

		foreach( self::$integrations as $integration ) {
			if ( $integration->enabled() && method_exists( $integration, 'custom_scripts' ) ) {
				$js = $integration->custom_scripts( $js );
			}
		}

		return $js;
	}

	/**
	 * @param array $css
	 *
	 * @return array $css
	 */
	public static function generated_css( $css = array() ) {

		foreach( self::$integrations as $integration ) {
			if ( $integration->enabled() && method_exists( $integration, 'custom_styles' ) ) {
				$css = $integration->custom_styles( $css );
			}
		}

		// TODO Move this to Integration object class.
		if ( self::enabled( 'calderaforms' ) ) {
			// puts the google places autocomplete dropdown results above the bootstrap modal 1050 zindex.
			$css['calderaforms'] = array( 'content' => ".pac-container { z-index: 2000000000 !important; }\n" );
		}

		return $css;
	}


	/**
	 * Modify popup settings.
	 *
	 * @param array $settings
	 * @param $popup_id
	 *
	 * @return array
	 */
	public static function popup_settings( $settings = array(), $popup_id ) {

		if ( is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return $settings;
		}

		static $form_popup_id;

		/**
		 * Checks for popup form submission.
		 */
		if ( ! isset( $form_popup_id ) ) {
			$form_popup_id = isset( $_REQUEST['pum_form_popup_id'] ) && absint( $_REQUEST['pum_form_popup_id'] ) > 0 ? absint( $_REQUEST['pum_form_popup_id'] ) : false;
		}

		/**
		 * If submission exists for this popup remove auto open triggers and add an admin_debug trigger to reshow the popup.
		 */
		if ( ( empty( $settings['disable_form_reopen'] ) || ! $settings['disable_form_reopen'] ) && $form_popup_id && $popup_id == $form_popup_id ) {
			$triggers = ! empty( $settings['triggers'] ) ? $settings['triggers'] : array();

			foreach ( $triggers as $key => $trigger ) {
				if ( $trigger['type'] == 'auto_open' ) {
					unset( $triggers[ $key ] );
				}
			}

			$settings['triggers'][] = array(
				'type' => 'admin_debug',
			);
		}

		return $settings;
	}


	/**
	 * Add various extra global pum_vars js values.
	 *
	 * @param array $vars
	 *
	 * @return array
	 */
	public static function pum_vars( $vars = array() ) {

		/**
		 * If a form was submitted via non-ajax methods this checks if a successful submission was reported.
		 */
		if ( isset( self::$form_success ) && ! empty( self::$form_success['popup_id'] ) ) {
			self::$form_success['settings'] = wp_parse_args( self::$form_success['settings'], array(
				'openpopup'        => false,
				'openpopup_id'     => 0,
				'closepopup'       => false,
				'closedelay'       => 0,
				'redirect_enabled' => false,
				'redirect'         => '',
				'cookie'           => false,
			) );

			if ( is_array( self::$form_success['settings']['cookie'] ) ) {
				self::$form_success['settings']['cookie'] = wp_parse_args( self::$form_success['settings']['cookie'], array(
					'name'    => 'pum-' . self::$form_success['popup_id'],
					'expires' => '+1 year',
				) );
			}

			$vars['form_success'] = self::$form_success;
		}

		return $vars;
	}

}
