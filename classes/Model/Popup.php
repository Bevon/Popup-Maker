<?php

// Exit if accessed directly

/*******************************************************************************
 * Copyright (c) 2017, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Model_Popup
 *
 * @since 1.4
 */
class PUM_Model_Popup extends PUM_Model_Post {

	#region Properties

	/** @var string */
	protected $required_post_type = 'popup';

	/** @var array */
	public $cookies;

	/** @var array */
	public $triggers;

	/** @var array */
	public $conditions;

	/** @var array */
	public $conditions_filtered = array();

	/** @var array */
	public $data_attr;

	/** @var string */
	public $title;

	/** @var int */
	public $theme_id;

	/** @var array */
	public $settings;

	/** @var string */
	public $content;

	# TODO Remove these once no longer needed.

	/** @var array */
	public $display;

	/** @var array */
	public $close;

	#endregion Properties

	#region General

	/**
	 * Returns the title of a popup.
	 *
	 * @uses filter `popmake_get_the_popup_title`
	 * @uses filter `pum_popup_get_title`
	 *
	 * @return string
	 */
	public function get_title() {
		if ( ! $this->title ) {
			$title = $this->get_meta( 'popup_title', true );

			// Deprecated
			$this->title = apply_filters( 'popmake_get_the_popup_title', $title, $this->ID );
		}

		return apply_filters( 'pum_popup_get_title', $this->title, $this->ID );
	}

	/**
	 * Returns the content of a popup.
	 *
	 * @uses filter `the_popup_content`
	 * @uses filter `pum_popup_content`
	 *
	 * @return string
	 */
	public function get_content() {
		if ( ! $this->content ) {
			// Deprecated Filter
			$this->content = apply_filters( 'the_popup_content', $this->post_content, $this->ID );
		}

		return $this->content = apply_filters( 'pum_popup_content', $this->content, $this->ID );
	}

	/**
	 * Returns this popups theme id or the default id.
	 *
	 * @todo replace usage of popmake_get_default_popup_theme.
	 *
	 * @uses filter `popmake_get_the_popup_theme`
	 * @uses filter `pum_popup_get_theme_id`
	 *
	 * @return int $theme_id
	 */
	public function get_theme_id() {
		if ( ! $this->theme_id ) {
			$theme_id = $this->get_meta( 'popup_theme', true );

			if ( ! $theme_id ) {
				$theme_id = popmake_get_default_popup_theme();
			}

			// Deprecated filter
			$this->theme_id = apply_filters( 'popmake_get_the_popup_theme', $theme_id, $this->ID );
		}

		return (int) apply_filters( 'pum_popup_get_theme_id', $this->theme_id, $this->ID );
	}

	/**
	 * Returns array of all popup settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		if ( ! isset( $this->settings ) ) {
			$this->settings = $this->get_meta( 'popup_settings', true );

			if ( ! is_array( $this->settings ) ) {
				$this->settings = array();
			}
		}

		return apply_filters( 'pum_popup_settings', $this->settings, $this->ID );
	}

	/**
	 * Returns a specific popup setting with optional default value when not found.
	 *
	 * @param $key
	 * @param bool $default
	 *
	 * @return bool|mixed
	 */
	public function get_setting( $key, $default = false ) {
		$settings = $this->get_settings();

		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	#endregion General

	#region Data Getters

	/**
	 * @return array
	 */
	function get_cookies() {
		if ( ! $this->cookies ) {
			$this->cookies = $this->get_meta( 'popup_cookies', true );

			if ( ! $this->cookies ) {
				$this->cookies = array();
			}
		}

		return apply_filters( 'pum_popup_get_cookies', $this->cookies, $this->ID );
	}

	/**
	 * @return array
	 */
	function get_triggers() {
		if ( ! $this->triggers ) {
			$this->triggers = $this->get_meta( 'popup_triggers', true );

			if ( ! $this->triggers ) {
				$this->triggers = array();
			}

			if ( ! is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				$has_click_trigger = false;

				foreach ( $this->triggers as $trigger ) {
					if ( $trigger['type'] == 'click_open' ) {
						$has_click_trigger = true;
					}
				}

				if ( ! $has_click_trigger ) {
					$this->triggers[] = array(
						'type'     => 'click_open',
						'settings' => array(
							'extra_selectors' => '',
							'cookie'          => array(
								'name' => null,
							),
						),
					);
				}
			}

		}

		return apply_filters( 'pum_popup_get_triggers', $this->triggers, $this->ID );
	}

	/**
	 * Returns all or single display settings.
	 *
	 * @param null $key
	 *
	 * @return mixed
	 */
	public function get_display( $key = null ) {
		if ( ! $this->display ) {
			$display_values = $this->get_meta( 'popup_display', true );

			if ( ! $display_values ) {
				$display_values = apply_filters( "pum_popup_display_defaults", array() );
			}

			$this->display = $display_values;
		}

		$values = apply_filters( 'pum_popup_get_display', $this->display, $this->ID );

		if ( ! $key ) {
			return $values;
		}

		$value = isset ( $values[ $key ] ) ? $values[ $key ] : null;

		return apply_filters( 'pum_popup_get_display_' . $key, $value, $this->ID );
	}

	/**
	 * Returns all or single close settings.
	 *
	 * @param null $key
	 *
	 * @return mixed
	 */
	public function get_close( $key = null ) {
		if ( ! $this->close ) {
			$close_values = $this->get_meta( 'popup_close', true );

			if ( ! $close_values ) {
				$close_values = apply_filters( "pum_popup_close_defaults", array() );
			}

			$this->close = $close_values;
		}

		$values = apply_filters( 'pum_popup_get_close', $this->close, $this->ID );

		if ( ! $key ) {
			return $values;
		}

		$value = isset ( $values[ $key ] ) ? $values[ $key ] : null;

		return apply_filters( 'pum_popup_get_close_' . $key, $value, $this->ID );

	}

	#endregion Data Getters

	#region Templating & Rendering

	/**
	 * Returns array of classes for this popup.
	 *
	 * @uses filter `popmake_get_the_popup_classes`
	 * @uses filter `pum_popup_get_classes`
	 *
	 * @param string $element The key or html element identifier.
	 *
	 * @return array $classes
	 */
	public function get_classes( $element = 'overlay' ) {
		$classes = array(
			'overlay'   => array(
				'pum',
				'pum-overlay',
				'pum-theme-' . $this->get_theme_id(),
				'pum-theme-' . get_post_field( 'post_name', $this->get_theme_id() ),
				'popmake-overlay', // Backward Compatibility
			),
			'container' => array(
				'pum-container',
				'popmake', // Backward Compatibility
				'theme-' . $this->get_theme_id(), // Backward Compatibility
			),
			'title'     => array(
				'pum-title',
				'popmake-title', // Backward Compatibility
			),
			'content'   => array(
				'pum-content',
				'popmake-content', // Backward Compatibility
			),
			'close'     => array(
				'pum-close',
				'popmake-close' // Backward Compatibility
			),
		);

		$classes = apply_filters( 'pum_popup_classes', $classes, $this->ID );

		if ( ! isset( $classes[ $element ] ) ) {
			return array();
		}

		return apply_filters( "pum_popup_{$element}_classes", $classes[ $element ], $this->ID );
	}

	/**
	 * Returns array for data attribute of this popup.
	 *
	 * @todo integrate popmake_clean_popup_data_attr
	 *
	 * @uses deprecated filter `popmake_get_the_popup_data_attr`
	 * @uses filter `pum_popup_get_data_attr`
	 *
	 * @return array
	 */
	public function get_data_attr() {
		if ( ! $this->data_attr ) {

			$data_attr = array(
				'id'              => $this->ID,
				'slug'            => $this->post_name,
				'theme_id'        => $this->get_theme_id(),
				'cookies'         => $this->get_cookies(),
				'triggers'        => $this->get_triggers(),
				'mobile_disabled' => $this->mobile_disabled() ? true : null,
				'tablet_disabled' => $this->tablet_disabled() ? true : null,
				'meta'            => array(
					'display'    => $this->get_display(),
					'close'      => $this->get_close(),
					// Added here for backward compatibility in extensions.
					'click_open' => popmake_get_popup_click_open( $this->ID ),
				),
			);

			$filters = array( 'js_only' => true );

			if ( $this->has_conditions( $filters ) ) {
				$data_attr['conditions'] = $this->get_conditions( $filters );
			}

			// Deprecated
			$this->data_attr = apply_filters( 'popmake_get_the_popup_data_attr', $data_attr, $this->ID );
		}

		return apply_filters( 'pum_popup_data_attr', $this->data_attr, $this->ID );
	}

	/**
	 * Returns the close button text.
	 *
	 * @return string
	 */
	public function close_text() {
		$text = '&#215;';

		/** @deprecated */
		$text = apply_filters( 'popmake_popup_default_close_text', $text, $this->ID );

		// Check to see if popup has close text to over ride default.
		$popup_close_text = $this->get_close( 'text' );
		if ( $popup_close_text && $popup_close_text != '' ) {
			$text = $popup_close_text;
		} else {
			// todo replace this with PUM_Theme class in the future.
			$theme_text = popmake_get_popup_theme_close( $this->get_theme_id(), 'text', false );
			if ( $theme_text && $theme_text != '' ) {
				$text = $theme_text;
			}
		}

		return apply_filters( 'pum_popup_close_text', $text, $this->ID );
	}

	/**
	 * Returns true if the close button should be rendered.
	 *
	 * @uses apply_filters `popmake_show_close_button`
	 * @uses apply_filters `pum_popup_show_close_button`
	 *
	 * @return bool
	 */
	public function show_close_button() {
		// Deprecated filter.
		$show = apply_filters( 'popmake_show_close_button', true, $this->ID );

		return boolval( apply_filters( 'pum_popup_show_close_button', $show, $this->ID ) );
	}

	#endregion Templating & Rendering

	#region Conditions

	/**
	 * Get the popups conditions.
	 *
	 * @param array $filters
	 *
	 * @return array
	 */
	public function get_conditions( $filters = array() ) {

		$filters = wp_parse_args( $filters, array(
			'php_only' => null,
			'js_only'  => null,
		) );

		$cache_key = hash( 'md5', json_encode( $filters ) );

		if ( ! $this->conditions ) {
			$this->conditions = $this->get_setting( 'conditions', array() );
		}

		// Check if these exclusion filters have already been applied and prevent extra processing.
		$conditions = isset( $this->conditions_filtered[ $cache_key ] ) ? $this->conditions_filtered[ $cache_key ] : false;

		if ( ! $conditions ) {
			$conditions = $this->conditions;
			// Sanity Check on the values not operand value.
			foreach ( $conditions as $group_key => $group ) {

				foreach ( $group as $key => $condition ) {

					if ( $this->exclude_condition( $condition, $filters ) ) {
						unset( $conditions[ $group_key ][ $key ] );
						if ( empty( $conditions[ $group_key ] ) ) {
							unset( $conditions[ $group_key ] );
							break;
						}
						continue;
					}

					$conditions[ $group_key ][ $key ] = $this->parse_condition( $condition );
				}

				if ( ! empty( $conditions[ $group_key ] ) ) {
					// Renumber each subarray.
					$conditions[ $group_key ] = array_values( $conditions[ $group_key ] );
				}
			}

			// Renumber top arrays.
			$conditions = array_values( $conditions );

			$this->conditions_filtered[ $cache_key ] = $conditions;
		}

		return apply_filters( 'pum_popup_get_conditions', $conditions, $this->ID, $filters );
	}

	/**
	 * Ensures condition data integrity.
	 *
	 * @param $condition
	 *
	 * @return array
	 */
	public function parse_condition( $condition ) {
		// The not operand value is missing, set it to false.
		return wp_parse_args( $condition, array(
			'not_operand' => false,
		) );
	}

	public function exclude_condition( $condition, $filters = array() ) {

		$exclude = false;

		// The condition target doesn't exist. Lets ignore this condition.
		if ( empty( $condition['target'] ) ) {
			return true;
		}

		$condition_args = PUM_Conditions::instance()->get_condition( $condition['target'] );

		// The condition target doesn't exist. Lets ignore this condition.
		if ( ! $condition_args ) {
			return true;
		}

		if ( $filters['js_only'] && $condition_args['advanced'] != true ) {
			return true;
		} elseif ( $filters['php_only'] && $condition_args['advanced'] != false ) {
			return true;
		}

		return $exclude;
	}

	/**
	 * Checks if this popup has any conditions.
	 *
	 * @param array $filters
	 *
	 * @return bool
	 */
	public function has_conditions( $filters = array() ) {
		return boolval( count( $this->get_conditions( $filters ) ) );
	}

	/**
	 * Checks if the popup has a specific condition.
	 *
	 * Generally used for conditional asset loading.
	 *
	 * @param array|string $conditions
	 *
	 * @return bool
	 */
	public function has_condition( $conditions ) {

		if ( ! $this->has_conditions() ) {
			return false;
		}

		$found = false;

		if ( ! is_array( $conditions ) ) {
			$conditions = array( $conditions );
		}

		foreach ( $this->get_conditions() as $group => $conds ) {

			foreach ( $conds as $condition ) {

				if ( in_array( $condition['target'], $conditions ) ) {
					$found = true;
				}

			}

		}

		return (bool) $found;
	}

	/**
	 * Returns whether or not the popup is visible in the loop.
	 *
	 * @return bool
	 */
	public function is_loadable() {

		// Loadable defaults to true if no conditions. Making the popup available everywhere.
		$loadable = true;

		if ( ! $this->ID ) {
			return false;
			// Published/private
		}

		$filters = array( 'php_only' => true );

		if ( $this->has_conditions( $filters ) ) {

			// All Groups Must Return True. Break if any is false and set $loadable to false.
			foreach ( $this->get_conditions( $filters ) as $group => $conditions ) {

				// Groups are false until a condition proves true.
				$group_check = false;

				// At least one group condition must be true. Break this loop if any condition is true.
				foreach ( $conditions as $condition ) {

					// If any condition passes, set $group_check true and break.
					if ( ! $condition['not_operand'] && $this->check_condition( $condition ) ) {
						$group_check = true;
						break;
					} elseif ( $condition['not_operand'] && ! $this->check_condition( $condition ) ) {
						$group_check = true;
						break;
					}

				}

				// If any group of conditions doesn't pass, popup is not loadable.
				if ( ! $group_check ) {
					$loadable = false;
				}

			}

		}

		return apply_filters( 'pum_popup_is_loadable', $loadable, $this->ID );
	}

	/**
	 * Check an individual condition with settings.
	 *
	 * @param array $args
	 *
	 * @return bool
	 */
	public function check_condition( $args = array() ) {
		$condition = PUM_Conditions::instance()->get_condition( $args['target'] );

		if ( ! $condition ) {
			return false;
		}

		/*
		 * Old way, here until we have patched all conditions.
		 *
		 * return call_user_func( $condition->get_callback(), $args, $this );
		 */

		$settings = isset( $args['settings'] ) ? $args['settings'] : array();

		$settings['condition'] = $args['target'];

		return (bool) call_user_func( $condition['callback'], $settings, $this );
	}

	/**
	 * Check if mobile was disabled
	 *
	 * @return bool
	 */
	public function mobile_disabled() {
		$mobile_disabled = $this->get_meta( 'popup_mobile_disabled', true );

		return (bool) apply_filters( 'pum_popup_mobile_disabled', $mobile_disabled, $this->ID );
	}

	public function tablet_disabled() {
		$tablet_disabled = $this->get_meta( 'popup_tablet_disabled', true );

		return (bool) apply_filters( 'pum_popup_tablet_disabled', $tablet_disabled, $this->ID );
	}

	#endregion Conditions

	#region Analytics

	/**
	 * Get a popups event count.
	 *
	 * @param string $event
	 * @param string $which
	 *
	 * @return int
	 */
	public function get_event_count( $event = 'open', $which = 'current' ) {

		$keys = PUM_Analytics::event_keys( $event );

		switch ( $which ) {
			case 'current' :
				$current = $this->get_meta( 'popup_' . $keys[0] . '_count', true );

				// Save future queries by inserting a valid count.
				if ( $current === false || ! is_numeric( $current ) ) {
					$current = 0;
					$this->update_meta( 'popup_' . $keys[0] . '_count', $current );
				}

				return absint( $current );
			case 'total'   :
				$total = $this->get_meta( 'popup_' . $keys[0] . '_count_total', true );

				// Save future queries by inserting a valid count.
				if ( $total === false || ! is_numeric( $total ) ) {
					$total = 0;
					$this->update_meta( 'popup_' . $keys[0] . '_count_total', $total );
				}

				return absint( $total );
		}

		return 0;
	}

	/**
	 * Increase popup event counts.
	 *
	 * @param string $event
	 */
	public function increase_event_count( $event = 'open' ) {

		/**
		 * This section simply ensures that all keys exist before the below query runs. This should only ever cause extra queries once per popup, usually in the admin.
		 */
		//$this->set_event_defaults( $event );

		$keys = PUM_Analytics::event_keys( $event );

		// Set the current count
		$current = $this->get_event_count( $event );
		if ( ! $current ) {
			$current = 0;
		};
		$current = $current + 1;

		// Set the total count since creation.
		$total = $this->get_event_count( $event, 'total' );
		if ( ! $total ) {
			$total = 0;
		}
		$total = $total + 1;

		$this->update_meta( 'popup_' . $keys[0] . '_count', absint( $current ) );
		$this->update_meta( 'popup_' . $keys[0] . '_count_total', absint( $total ) );
		$this->update_meta( 'popup_last_' . $keys[1], current_time( 'timestamp', 0 ) );

		$site_total = get_option( 'pum_total_' . $keys[0] . '_count', 0 );
		$site_total ++;
		update_option( 'pum_total_' . $keys[0] . '_count', $site_total );

		// If is multisite add this blogs total to the site totals.
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$network_total = get_site_option( 'pum_site_total_' . $keys[0] . '_count', false );
			$network_total = ! $network_total ? $site_total : $network_total + 1;
			update_site_option( 'pum_site_total_' . $keys[0] . '_count', $network_total );
		}
	}

	public function set_event_defaults( $event ) {
		$this->get_event_count( $event );
		$this->get_event_count( $event, 'total' );

		$keys = PUM_Analytics::event_keys( $event );
		$last = $this->get_meta( 'popup_last_' . $keys[1] );

		if ( empty( $last ) || ! is_numeric( $last ) ) {
			$this->update_meta( 'popup_last_' . $keys[1], 0 );
		}
	}

	/**
	 * Log and reset popup open count to 0.
	 */
	public function reset_counts() {
		// Log the reset time and count.
		add_post_meta( $this->ID, 'popup_count_reset', array(
			'timestamp'   => current_time( 'timestamp', 0 ),
			'opens'       => absint( $this->get_event_count( 'open', 'current' ) ),
			'conversions' => absint( $this->get_event_count( 'conversion', 'current' ) ),
		) );

		foreach ( array( 'open', 'conversion' ) as $event ) {
			$keys = PUM_Analytics::event_keys( $event );
			$this->update_meta( 'popup_' . $keys[0] . '_count', 0 );
			$this->update_meta( 'popup_last_' . $keys[1], 0 );
		}

		$this->update_cache();
	}

	/**
	 * Returns the last reset information.
	 *
	 * @return mixed
	 */
	public function get_last_count_reset() {
		$resets = $this->get_meta( 'popup_count_reset', false );

		if ( empty ( $resets ) ) {
			// No results found.
			return false;
		}

		if ( ! empty( $resets['timestamp'] ) ) {
			// Looks like the result is already the last one, return it.
			return $resets;
		}

		if ( count( $resets ) == 1 ) {
			// Looks like we only got one result, return it.
			return $resets[0];
		}

		usort( $resets, array( $this, "compare_resets" ) );

		return $resets[0];
	}

	public function compare_resets( $a, $b ) {
		return ( float ) $a['timestamp'] < ( float ) $b['timestamp'];
	}

	#endregion Analytics
}

