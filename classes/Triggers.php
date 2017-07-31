<?php
/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	// Exit if accessed directly
	exit;
}

/**
 * Class PUM_Triggers
 */
class PUM_Triggers {

	/**
	 * @var
	 */
	public static $instance;

	/**
	 * @var array
	 */
	public $triggers;

	/**
	 *
	 */
	public static function init() {
		self::instance();
	}

	/**
	 * @return PUM_Triggers
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * @param array $triggers
	 */
	public function add_triggers( $triggers = array() ) {
		foreach ( $triggers as $key => $trigger ) {

			if ( empty( $trigger['id'] ) && ! is_numeric( $key ) ) {
				$trigger['id'] = $key;
			}

			$this->add_trigger( $trigger );
		}
	}

	/**
	 * @param array $trigger
	 */
	public function add_trigger( $trigger = array() ) {
		if ( ! empty( $trigger['id'] ) && ! isset ( $this->triggers[ $trigger['id'] ] ) ) {
			$trigger = wp_parse_args( $trigger, array(
				'id'              => '',
				'name'            => '',
				'modal_title'     => '',
				'settings_column' => '',
				'priority'        => 10,
				'tabs'            => self::get_tabs(),
				'fields'          => array(),
			) );

			// Here for backward compatibility to merge in labels properly.
			$labels         = self::get_labels();
			$trigger_labels = isset( $labels[ $trigger['id'] ] ) ? $labels[ $trigger['id'] ] : array();

			if ( ! empty( $trigger_labels ) ) {
				foreach ($trigger_labels as $key => $value ) {
					if ( empty( $trigger[ $key ] ) ) {
						$trigger[ $key ] = $value;
					}
				}
			}

			$this->triggers[ $trigger['id'] ] = $trigger;
		}

		return;
	}

	/**
	 * @return array
	 */
	public function get_triggers() {
		if ( ! isset( $this->triggers ) ) {
			$this->register_triggers();
		}

		return $this->triggers;
	}

	/**
	 * @param null $trigger
	 *
	 * @return mixed|null
	 */
	public function get_trigger( $trigger = null ) {
		$triggers = $this->get_triggers();

		return isset( $triggers[ $trigger ] ) ? $triggers[ $trigger ] : null;
	}

	/**
	 * @deprecated
	 *
	 * @param null $trigger
	 * @param array $settings
	 *
	 * @return array
	 */
	public function validate_trigger( $trigger = null, $settings = array() ) {
		return $settings;
	}

	/**
	 * Registers all known triggers when called.
	 */
	public function register_triggers() {
		$triggers = apply_filters( 'pum_registered_triggers', array(
			'click_open' => array(
				'name'            => __( 'Click Open', 'popup-maker' ),
				'modal_title'     => __( 'Click Trigger Settings', 'popup-maker' ),
				'settings_column' => sprintf( '<strong>%1$s</strong>: %2$s', __( 'Extra Selectors', 'popup-maker' ), '{{data.extra_selectors}}' ),
				'fields'          => array(
					'general' => array(
						'extra_selectors' => array(
							'label'       => __( 'Extra CSS Selectors', 'popup-maker' ),
							'desc'        => __( 'This allows custom css classes, ids or selector strings to trigger the popup when clicked. Separate multiple selectors using commas.', 'popup-maker' ),
							'placeholder' => __( '.my-class, #button2', 'popup-maker' ),
							'doclink'     => 'http://docs.wppopupmaker.com/article/147-getting-css-selectors?page-popup-editor=&utm_medium=inline-doclink&utm_campaign=ContextualHelp&utm_content=extra-selectors',
						),
						'do_default'      => array(
							'type'  => 'checkbox',
							'label' => __( 'Do not prevent the default click functionality.', 'popup-maker' ),
							'desc'  => __( 'This prevents us from disabling the browsers default action when a trigger is clicked. It can be used to allow a link to a file to both trigger a popup and still download the file.', 'popup-maker' ),
						),
					),
					'cookie'  => self::cookie_fields(),
				),
			),
			'auto_open'  => array(
				'name'            => __( 'Auto Open', 'popup-maker' ),
				'modal_title'     => __( 'Auto Open Settings', 'popup-maker' ),
				'settings_column' => sprintf( '<strong>%1$s</strong>: %2$s', __( 'Delay', 'popup-maker' ), '{{data.delay}}' ),
				'fields'          => array(
					'general' => array(
						'delay' => array(
							'type'  => 'rangeslider',
							'label' => __( 'Delay', 'popup-maker' ),
							'desc'  => __( 'The delay before the popup will open in milliseconds.', 'popup-maker' ),
							'std'   => 500,
							'min'   => 0,
							'max'   => 10000,
							'step'  => 500,
						),
					),
					'cookie'  => self::cookie_fields(),
				),
			),

		) );

		foreach( $triggers as $key => $trigger ) {
			//$triggers[$key]['updated'] = true;
		}

		// @deprecated filter.
		$triggers = apply_filters( 'pum_get_triggers', $triggers );

		$this->add_triggers( $triggers );
	}


	/**
	 * @return array
	 */
	public function dropdown_list() {
		$_triggers = $this->get_triggers();
		$triggers  = array();

		foreach ( $_triggers as $id => $trigger ) {
			$triggers[ $id ] = $trigger['name'];
		}

		return $triggers;
	}


	/**
	 * Returns the cookie fields used for trigger options.
	 *
	 * @uses filter pum_trigger_cookie_fields
	 *
	 * @return array
	 */
	public static function cookie_fields() {

		/**
		 * Filter the array of default trigger cookie fields.
		 *
		 * @param array $fields The list of trigger cookie fields.
		 */
		return apply_filters( 'pum_trigger_cookie_fields', array(
			'cookie_name' => self::cookie_field(),
		) );
	}

	/**
	 * Returns the cookie field used for trigger options.
	 *
	 * @uses filter pum_trigger_cookie_field
	 *
	 * @return array
	 */
	public static function cookie_field() {

		/**
		 * Filter the array of default trigger cookie field.
		 *
		 * @param array $fields The list of trigger cookie field.
		 */
		return apply_filters( 'pum_trigger_cookie_field', array(
			'label'    => __( 'Cookie Name', 'popup-maker' ),
			'desc'     => __( 'When do you want to create the cookie.', 'popup-maker' ),
			'type'     => 'select',
			'multiple' => true,
			'select2'  => true,
			'priority' => 1,
			'options'  => array(
				__( 'Add New Cookie', 'popup-maker' ) => 'add_new',
			),
		) );
	}


	/**
	 * Returns an array of section labels for all triggers.
	 *
	 * Use the filter pum_get_trigger_section_labels to add or modify labels.
	 *
	 * @return array
	 */
	public function get_tabs() {
		/**
		 * Filter the array of trigger section labels.
		 *
		 * @param array $to_do The list of trigger section labels.
		 */
		return apply_filters( 'pum_get_trigger_tabs', array(
			'general' => __( 'General', 'popup-maker' ),
			'cookie'  => __( 'Cookie', 'popup-maker' ),
		) );
	}

	/**
	 * Returns an array of trigger labels.
	 *
	 * Use the filter pum_get_trigger_labels to add or modify labels.
	 *
	 * @return array
	 */
	public function get_labels() {
		static $labels;

		if ( ! isset( $labels ) ) {
			/**
			 * Filter the array of trigger labels.
			 *
			 * @param array $to_do The list of trigger labels.
			 */
			$labels = apply_filters( 'pum_get_trigger_labels', array() );
		}

		return $labels;
	}


}
