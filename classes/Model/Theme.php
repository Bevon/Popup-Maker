<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Model_Theme
 *
 * @since 1.7
 */
class PUM_Model_Theme extends PUM_Abstract_Model_Post {

	#region Properties

	/** @var string */
	protected $required_post_type = 'popup_theme';

	/** @var string */
	public $title;

	/** @var array */
	public $settings;

	/** @var bool */
	public $doing_passive_migration = false;

	/**
	 * The current model version.
	 *
	 * 1 - v1.0.0
	 * 2 - v1.4.0
	 * 3 - v1.7.0
	 *
	 * @var int */
	public $model_version = 3;

	/**
	 * The version of the data currently stored for the current item.
	 *
	 * 1 - v1.0.0
	 * 2 - v1.4.0
	 * 3 - v1.7.0
	 *
	 * @var int */
	public $data_version;

	#endregion Properties

	#region General

	/**
	 * Returns the title of a popup.
	 *
	 * @uses filter `pum_popup_get_title`
	 *
	 * @return string
	 */
	public function get_title() {
		$title = $this->get_meta( 'popup_title' );

		return (string) apply_filters( 'pum_popup_get_title', $title, $this->ID );
	}

	/**
	 * Returns the content of a popup.
	 *
	 * @uses filter `pum_popup_content`
	 *
	 * @return string
	 */
	public function get_content() {
		return $this->content = apply_filters( 'pum_popup_content', $this->post_content, $this->ID );
	}

	#endregion General

	#region Settings

	/**
	 * Returns array of all popup settings.
	 *
	 * @param bool $force
	 *
	 * @return array
	 */
	public function get_settings( $force = false ) {
		if ( ! isset( $this->settings ) || $force ) {
			$this->settings = $this->get_meta( 'popup_settings' );

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

	/**
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return bool|int
	 */
	public function update_setting( $key, $value ) {
		$settings = $this->get_settings( true );

		// TODO Once fields have been merged into the model itself, add automatic validation here.
		$settings[ $key ] = $value;

		return $this->update_meta( 'popup_settings', $settings );
	}

	/**
	 * @param array $merge_settings
	 *
	 * @return bool|int
	 */
	public function update_settings( $merge_settings = array() ) {
		$settings = $this->get_settings( true );

		// TODO Once fields have been merged into the model itself, add automatic validation here.
		foreach ( $merge_settings as $key => $value ) {
			$settings[ $key ] = $value;
		}

		return $this->update_meta( 'popup_settings', $settings );
	}

	/**
	 * Returns cleansed public settings for a popup.
	 *
	 * @return array
	 */
	public function get_public_settings() {
		$settings = wp_parse_args( $this->get_settings(), PUM_Admin_Popups::defaults() );

		foreach ( $settings as $key => $value ) {
			$field = PUM_Admin_Popups::get_field( $key );

			if ( $field['private'] ) {
				unset( $settings[ $key ] );
			} elseif ( $field['type'] == 'checkbox' ) {
				$settings[ $key ] = (bool) $value;
			}
		}

		$settings['id']   = $this->ID;
		$settings['slug'] = $this->post_name;

		$filters = array( 'js_only' => true );

		if ( $this->has_conditions( $filters ) ) {
			$settings['conditions'] = $this->get_conditions( $filters );
		}

		return apply_filters( 'pum_popup_get_public_settings', $settings, $this );
	}

	#endregion Settings

	#region Deprecated Getters

	/**
	 * Retrieve settings in the form of deprecated grouped arrays.
	 *
	 * @param $group
	 * @param null $key
	 *
	 * @return mixed
	 */
	public function _dep_get_settings_group( $group, $key = null ) {
		if ( ! $this->$group ) {
			/**
			 * Remap old meta settings to new settings location for v1.7. This acts as a passive migration when needed.
			 */
			$remapped_keys = $this->remapped_meta_settings_keys( $group );

			// This will only return data from extensions as core data has been migrated already.
			$group_values = $this->get_meta( "popup_$group" );

			if ( ! $group_values || ! is_array( $group_values ) ) {
				$group_values = array();
			}

			// Data manipulation begins here. We don't want any of this saved, only returned for backward compatibility.
			foreach ( $remapped_keys as $old_key => $new_key ) {
				$group_values[ $old_key ] = $this->get_setting( $new_key );
			}

			$deprecated_values = popmake_get_popup_meta_group( $group, $this->ID );

			if ( ! empty( $deprecated_values ) ) {
				foreach( $deprecated_values as $old_key => $value ) {

					if ( ! isset( $group_values[ $old_key ] ) ) {
						$group_values[ $old_key ] = $value;
					}

				}
			}



			$this->$group = $group_values;
		}

		$values = apply_filters( "pum_popup_get_$group", $this->$group, $this->ID );

		if ( ! $key ) {
			return $values;
		}

		$value = isset ( $values[ $key ] ) ? $values[ $key ] : null;

		if ( ! isset( $value ) ) {
			$value = $this->get_meta( "popup_{$group}_{$key}" );
		}

		return apply_filters( "pum_popup_get_{$group}_" . $key, $value, $this->ID );
	}

	/**
	 * @param $group
	 *
	 * @return array|mixed
	 */
	public function remapped_meta_settings_keys( $group ) {
		$remapped_meta_settings_keys = array(
			'display' => array(
				'stackable'                 => 'stackable',
				'overlay_disabled'          => 'overlay_disabled',
				'scrollable_content'        => 'scrollable_content',
				'disable_reposition'        => 'disable_reposition',
				'size'                      => 'size',
				'responsive_min_width'      => 'responsive_min_width',
				'responsive_min_width_unit' => 'responsive_min_width_unit',
				'responsive_max_width'      => 'responsive_max_width',
				'responsive_max_width_unit' => 'responsive_max_width_unit',
				'custom_width'              => 'custom_width',
				'custom_width_unit'         => 'custom_width_unit',
				'custom_height'             => 'custom_height',
				'custom_height_unit'        => 'custom_height_unit',
				'custom_height_auto'        => 'custom_height_auto',
				'location'                  => 'location',
				'position_from_trigger'     => 'position_from_trigger',
				'position_top'              => 'position_top',
				'position_left'             => 'position_left',
				'position_bottom'           => 'position_bottom',
				'position_right'            => 'position_right',
				'position_fixed'            => 'position_fixed',
				'animation_type'            => 'animation_type',
				'animation_speed'           => 'animation_speed',
				'animation_origin'          => 'animation_origin',
				'overlay_zindex'            => 'overlay_zindex',
				'zindex'                    => 'zindex',
			),
			'close'   => array(
				'text'          => 'close_text',
				'button_delay'  => 'close_button_delay',
				'overlay_click' => 'close_on_overlay_click',
				'esc_press'     => 'close_on_esc_press',
				'f4_press'      => 'close_on_f4_press',
			),
		);

		return isset( $remapped_meta_settings_keys[ $group ] ) ? $remapped_meta_settings_keys[ $group ] : array();


	}

	/**
	 * Returns all or single display settings.
	 *
	 * @deprecated 1.7.0 Use get_setting instead.
	 *
	 * @param string|null $key
	 *
	 * @return mixed
	 */
	public function get_display( $key = null ) {
		$display = $this->_dep_get_settings_group( 'display', $key );

		foreach( array(
			'responsive_min_width',
			'responsive_max_width',
			'custom_width',
			'custom_height',
		) as $key => $value ) {
			$temp = isset( $display[ $key ] ) ? $display[ $key ] : false;

			if ( $temp && is_string( $temp ) ) {
				$display[ $key ] = preg_replace('/\D/', '', $temp );
				$display[ $key . '_unit' ] = str_replace( $display[ $key ], '', $temp );
			}
		}

		return $display;
	}

	/**
	 * Returns all or single close settings.
	 *
	 * @deprecated 1.7.0 Use get_setting instead.
	 *
	 * @param string|null $key
	 *
	 * @return mixed
	 */
	public function get_close( $key = null ) {
		return $this->_dep_get_settings_group( 'close', $key );
	}

	#endregion Deprecated Getters

	#region Templating & Rendering


	/**
	 * Returns the close button text.
	 *
	 * @return string
	 */
	public function close_text() {
		$text = $this->get_setting( 'close_text', '&#215;' );

		$theme_text = popmake_get_popup_theme_close( $this->get_theme_id(), 'text', false );

		if ( empty( $text ) && ! empty( $theme_text ) ) {
			$text = $theme_text;
		}

		return apply_filters( 'pum_popup_close_text', $text, $this->ID );
	}
	#endregion Templating & Rendering

	#region Migration

	public function setup() {

		if ( ! isset( $this->data_version ) ) {
			$this->data_version = (int) $this->get_meta( 'data_version' );

			if ( ! $this->data_version ) {
				$theme = $this->get_meta( 'popup_theme' );
				$display_settings = $this->get_meta( 'popup_display' );

				// If there are existing settings set the data version to 2 so they can be updated.
				// Otherwise set to the current version as this is a new popup.
				$is_v2  = ( ! empty( $display_settings ) && is_array( $display_settings ) ) || $theme > 0;
				$this->data_version = $is_v2 ? 2 : $this->model_version;

				$this->update_meta( 'data_version', $this->data_version );
			}
		}

		if ( $this->data_version < $this->model_version && pum_passive_popups_enabled() ) {
			/**
			 * Process passive settings migration as each popup is loaded. The will only run each migration routine once for each popup.
			 */
			$this->passive_migration();
		}

	}


	/**
	 * Allows for passive migration routines based on the current data version.
	 */
	public function passive_migration() {
		$this->doing_passive_migration = true;

		for ( $i = $this->data_version; $this->data_version < $this->model_version; $i ++ ) {
			do_action_ref_array( 'pum_popup_passive_migration_' . $this->data_version, array( &$this ) );
			$this->data_version ++;

			/**
			 * Update the popups data version.
			 */
			$this->update_meta( 'data_version', $this->data_version );
		}

		do_action_ref_array( 'pum_popup_passive_migration', array( &$this, $this->data_version ) );

		$this->doing_passive_migration = false;
	}

	#endregion Migration
}

