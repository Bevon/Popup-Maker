<?php
/*******************************************************************************
 * Copyright (c) 2018, WP Popup Maker
 ******************************************************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PUM_Admin_Themes
 */
class PUM_Admin_Themes {

	/**
	 * Hook the initialize method to the WP init action.
	 */
	public static function init() {
		/** Regitster Metaboxes */
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_box' ) );

		/** Process meta saving. */
		add_action( 'save_post', array( __CLASS__, 'save' ), 10, 2 );
	}

	/**
	 * Registers popup metaboxes.
	 */
	public static function meta_box() {
		/** Settings Box **/
		add_meta_box( 'pum_theme_settings', __( 'Theme Settings', 'popup-maker' ), array( __CLASS__, 'render_settings_meta_box' ), 'popup_theme', 'normal', 'high' );

		/** Preview Window **/
		add_meta_box( 'pum_theme_preview', __( 'Theme Preview', 'popup-maker' ), array( __CLASS__, 'render_preview_meta_box' ), 'popup_theme', 'side', 'high' );
	}

	/**
	 * Render the settings meta box wrapper and JS vars.
	 */
	public static function render_settings_meta_box() {
		global $post;

		$theme = pum_get_theme( $post->ID );

		// Get the meta directly rather than from cached object.
		$settings = $theme->get_settings();

		if ( empty( $settings ) ) {
			$settings = self::defaults();
		}

		wp_nonce_field( basename( __FILE__ ), 'pum_theme_settings_nonce' );
		wp_enqueue_script( 'popup-maker-admin' );
		?>
		<script type="text/javascript">
            window.pum_theme_settings_editor = <?php echo PUM_Utils_Array::safe_json_encode( apply_filters( 'pum_theme_settings_editor_var', array(
				'form_args'      => array(
					'id'       => 'pum-theme-settings',
					'tabs'     => self::tabs(),
					'sections' => self::sections(),
					'fields'   => self::fields(),
				),
				'current_values' => self::parse_values( $settings ),
			) ) ); ?>;

            jQuery(document)
                .ready(function () {
                    jQuery(this).trigger('pum_init');

                    var $container = jQuery('#pum-theme-settings-container'),
                        args = pum_theme_settings_editor.form_args || {},
                        values = pum_theme_settings_editor.current_values || {};

                    if ($container.length) {
                        $container.find('.pum-no-js').hide();
                        PUM_Admin.forms.render(args, values, $container);
                    }
                });
		</script>

		<div id="pum-theme-settings-container" class="pum-theme-settings-container">
			<div class="pum-no-js" style="padding: 0 12px;">
				<p><?php printf( __( 'If you are seeing this, the page is still loading or there are Javascript errors on this page. %sView troubleshooting guide%s', 'popup-maker' ), '<a href="https://docs.wppopupmaker.com/article/373-checking-for-javascript-errors" target="_blank">', '</a>' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 *
	 */
	public static function render_preview_meta_box() {
		// REWRITE This is overdue.

		?>
		<div class="empreview">
			<div id="PopMake-Preview">
				<div class="example-popup-overlay"></div>
				<div class="example-popup">
					<div class="title"><?php _e( 'Title Text', 'popup-maker' ); ?></div>
					<div class="content">
						<?php do_action( 'popmake_example_popup_content' ); ?>
					</div>
					<a class="close-popup">&#215;</a>
				</div>
				<p class="pum-desc"><?php
					$tips = array(
						__( 'If you move this theme preview to the bottom of your sidebar here it will follow you down the page?', 'popup-maker' ),
						__( 'Clicking on an element in this theme preview will take you to its relevant settings in the editor?', 'popup-maker' ),
					);
					$key  = array_rand( $tips, 1 ); ?>
					<i class="dashicons dashicons-info"></i> <?php echo '<strong>' . __( 'Did you know:', 'popup-maker' ) . '</strong>  ' . $tips[ $key ]; ?>
				</p>
			</div>
		</div>

		<?php
	}

	/**
	 * Used to get deprecated fields for metabox saving of old extensions.
	 *
	 * @deprecated 1.8.0
	 *
	 * @return mixed
	 */
	public static function deprecated_meta_fields() {
		$fields = array();
		foreach ( self::deprecated_meta_field_groups() as $group ) {
			foreach ( apply_filters( 'popmake_popup_theme_meta_field_group_' . $group, array() ) as $field ) {
				$fields[] = 'popup_theme_' . $group . '_' . $field;
			}
		}

		return apply_filters( 'popmake_popup_theme_meta_fields', $fields );
	}

	/**
	 * Used to get field groups from extensions.
	 *
	 * @deprecated 1.8.0
	 *
	 * @return mixed
	 */
	public static function deprecated_meta_field_groups() {
		return apply_filters( 'popmake_popup_theme_meta_field_groups', array( 'display', 'close' ) );
	}

	/**
	 * @param $post_id
	 * @param $post
	 *
	 * @return bool
	 */
	public static function can_save( $post_id, $post ) {
		if ( isset( $post->post_type ) && 'popup_theme' != $post->post_type ) {
			return false;
		}

		if ( ! isset( $_POST['pum_theme_settings_nonce'] ) || ! wp_verify_nonce( $_POST['pum_theme_settings_nonce'], basename( __FILE__ ) ) ) {
			return false;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
			return false;
		}

		if ( isset( $post->post_type ) && 'revision' == $post->post_type ) {
			return false;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param $post_id
	 * @param $post
	 */
	public static function save( $post_id, $post ) {

		if ( ! self::can_save( $post_id, $post ) ) {
			return;
		}

		$popup = pum_get_popup( $post_id );

		$settings = ! empty( $_POST['theme_settings'] ) ? $_POST['theme_settings'] : array();

		$settings = wp_parse_args( $settings, self::defaults() );

		$settings = apply_filters( 'pum_theme_setting_pre_save', $settings, $post->ID );

		// Sanitize form values.
		$settings = PUM_Utils_Fields::sanitize_fields( $settings, self::fields() );

		$popup->update_meta( 'theme_settings', $settings );

		// If this is a built in theme and the user has modified it set a key so that we know not to make automatic upgrades to it in the future.
		if ( get_post_meta( $post_id, '_pum_built_in', true ) !== false ) {
			update_post_meta( $post_id, '_pum_user_modified', true );
		}

		self::process_deprecated_saves( $post_id, $post );

		do_action( 'pum_save_theme', $post_id, $post );
	}

	/**
	 * @param $post_id
	 * @param $post
	 */
	public static function process_deprecated_saves( $post_id, $post ) {

		$field_prefix = 'popup_theme_';

		$old_fields = (array) apply_filters( 'popmake_popup_theme_fields', array() );

		foreach ( $old_fields as $section => $fields ) {
			$section_prefix = "{$field_prefix}{$section}";
			$meta_values    = array();

			foreach ( $fields as $field => $args ) {
				$field_name = "{$section_prefix}_{$field}";
				if ( isset( $_POST[ $field_name ] ) ) {
					$meta_values[ $field ] = apply_filters( 'popmake_metabox_save_' . $field_name, $_POST[ $field_name ] );
				}
			}

			update_post_meta( $post_id, "popup_theme_{$section}", $meta_values );
		}

		// TODO Remove this and all other code here. This should be clean and all code more compartmentalized.
		foreach ( self::deprecated_meta_fields() as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$new = apply_filters( 'popmake_metabox_save_' . $field, $_POST[ $field ] );
				update_post_meta( $post_id, $field, $new );
			} else {
				delete_post_meta( $post_id, $field );
			}
		}
	}

	/**
	 * @param $settings
	 *
	 * @return mixed
	 */
	public static function parse_values( $settings ) {

		foreach ( $settings as $key => $value ) {
			$field = PUM_Utils_Fields::get_field( self::fields(), $key );

			if ( $field ) {
				switch ( $field['type'] ) {
					case 'measure':
						break;
				}
			}
		}

		return $settings;
	}

	/**
	 * List of tabs & labels for the settings panel.
	 *
	 * @return array
	 */
	public static function tabs() {
		return apply_filters( 'pum_theme_settings_tabs', array(
			'general'   => __( 'General', 'popup-maker' ),
			'overlay'   => __( 'Overlay', 'popup-maker' ),
			'container' => __( 'Container', 'popup-maker' ),
			'title'     => __( 'Title', 'popup-maker' ),
			'content'   => __( 'Content', 'popup-maker' ),
			'close'     => __( 'Close', 'popup-maker' ),
			'advanced'  => __( 'Advanced', 'popup-maker' ),
		) );
	}

	/**
	 * List of tabs & labels for the settings panel.
	 *
	 * @return array
	 */
	public static function sections() {
		return apply_filters( 'pum_theme_settings_sections', array(
			'general'   => array(
				'main' => __( 'General', 'popup-maker' ),
			),
			'overlay'   => array(
				'background' => __( 'Background', 'popup-maker' ),
			),
			'container' => array(
				'main'       => __( 'Container', 'popup-maker' ),
				'background' => __( 'Background', 'popup-maker' ),
				'border'     => __( 'Border', 'popup-maker' ),
				'boxshadow'  => __( 'Drop Shadow', 'popup-maker' ),
			),
			'title'     => array(
				'typography' => __( 'Typography', 'popup-maker' ),
				'textshadow' => __( 'Text Shadow', 'popup-maker' ),
			),
			'content'   => array(
				'typography' => __( 'Text', 'popup-maker' ),
			),
			'close'     => array(
				'main'       => __( 'Close', 'popup-maker' ),
				'size'       => __( 'Size', 'popup-maker' ),
				'position'   => __( 'Position', 'popup-maker' ),
				'background' => __( 'Background', 'popup-maker' ),
				'border'     => __( 'Border', 'popup-maker' ),
				'boxshadow'  => __( 'Drop Shadow', 'popup-maker' ),
				'typography' => __( 'Typography', 'popup-maker' ),
				'textshadow' => __( 'Text Shadow', 'popup-maker' ),
			),
			'advanced'  => array(
				'main' => __( 'Advanced', 'popup-maker' ),
			),
		) );
	}

	/**
	 * @return mixed
	 */
	public static function border_style_options() {
		return apply_filters( 'pum_theme_border_style_options', array(
			'none'   => __( 'None', 'popup-maker' ),
			'solid'  => __( 'Solid', 'popup-maker' ),
			'dotted' => __( 'Dotted', 'popup-maker' ),
			'dashed' => __( 'Dashed', 'popup-maker' ),
			'double' => __( 'Double', 'popup-maker' ),
			'groove' => __( 'Groove', 'popup-maker' ),
			'inset'  => __( 'Inset', 'popup-maker' ),
			'outset' => __( 'Outset', 'popup-maker' ),
			'ridge'  => __( 'Ridge', 'popup-maker' ),
		) );
	}

	/**
	 * @return mixed
	 */
	public static function size_unit_options() {
		return apply_filters( 'pum_theme_size_unit_options', array(
			'px'  => 'px',
			'%'   => '%',
			'em'  => 'em',
			'rem' => 'rem',
		) );
	}

	/**
	 * @return mixed
	 */
	public static function font_family_options() {
		$fonts = array(
			'inherit'         => __( 'Use Your Themes', 'popup-maker' ),
			'Sans-Serif'      => 'Sans-Serif',
			'Tahoma'          => 'Tahoma',
			'Georgia'         => 'Georgia',
			'Comic Sans MS'   => 'Comic Sans MS',
			'Arial'           => 'Arial',
			'Lucida Grande'   => 'Lucida Grande',
			'Times New Roman' => 'Times New Roman',
		);

		/** @deprecated 1.8.0 This filter is no longer in use */
		$old_fonts = apply_filters( 'popmake_font_family_options', array() );

		$fonts = array_merge( $fonts, array_flip( $old_fonts ) );

		return apply_filters( 'pum_theme_size_unit_options', $fonts );
	}

	/**
	 * @return mixed
	 */
	public static function font_weight_options() {
		return apply_filters( 'pum_theme_size_unit_options', array(
			''     => __( 'Normal', 'popup-maker' ),
			'100 ' => '100',
			'200 ' => '200',
			'300 ' => '300',
			'400 ' => '400',
			'500 ' => '500',
			'600 ' => '600',
			'700 ' => '700',
			'800 ' => '800',
			'900 ' => '900',
		) );
	}

	/**
	 * Returns array of popup settings fields.
	 *
	 * @return mixed
	 */
	public static function fields() {

		static $fields;

		if ( ! isset( $fields ) ) {

			$size_unit_options    = self::size_unit_options();
			$border_style_options = self::border_style_options();
			$font_family_options  = self::font_family_options();
			$font_weight_options  = self::font_weight_options();

			$fields = apply_filters( 'pum_theme_settings_fields', array(
				'general'   => apply_filters( 'pum_popup_general_settings_fields', array(
					'main' => array(),
				) ),
				'overlay'   => apply_filters( 'pum_popup_overlay_settings_fields', array(
					'background' => array(
						'overlay_background_color'   => array(
							'label'    => __( 'Color', 'popup-maker' ),
							'desc'     => __( 'Choose the overlay color.', 'popup-maker' ),
							'type'     => 'color',
							'std'      => '#ffffff',
							'priority' => 10,
						),
						'overlay_background_opacity' => array(
							'label'        => __( 'Opacity', 'popup-maker' ),
							'desc'         => __( 'The opacity value for the overlay.', 'popup-maker' ),
							'type'         => 'rangeslider',
							'force_minmax' => true,
							'std'          => 100,
							'step'         => 1,
							'min'          => 0,
							'max'          => 100,
							'unit'         => '%',
							'priority'     => 20,
						),
					),
				) ),
				'container' => apply_filters( 'pum_popup_container_settings_fields', array(
					'main'       => array(
						'container_padding'       => array(
							'label'    => __( 'Padding', 'popup-maker' ),
							'type'     => 'rangeslider',
							'std'      => 18,
							'priority' => 10,
							'step'     => 1,
							'min'      => 1,
							'max'      => 100,
							'unit'     => 'px',
						),
						'container_border_radius' => array(
							'label'    => __( 'Border Radius', 'popup-maker' ),
							'type'     => 'rangeslider',
							'std'      => 0,
							'priority' => 20,
							'step'     => 1,
							'min'      => 1,
							'max'      => 80,
							'unit'     => 'px',
						),
					),
					'background' => array(
						'container_background_color'   => array(
							'label'    => __( 'Color', 'popup-maker' ),
							'type'     => 'color',
							'std'      => '#f9f9f9',
							'priority' => 10,
						),
						'container_background_opacity' => array(
							'label'        => __( 'Opacity', 'popup-maker' ),
							'type'         => 'rangeslider',
							'force_minmax' => true,
							'std'          => 100,
							'priority'     => 20,
							'step'         => 1,
							'min'          => 0,
							'max'          => 100,
							'unit'         => '%',
						),
					),
					'border'     => array(
						'container_border_style' => array(
							'label'       => __( 'Style', 'popup-maker' ),
							'description' => __( 'Choose a border style for your container button.', 'popup-maker' ),
							'type'        => 'select',
							'std'         => 'none',
							'priority'    => 10,
							'options'     => $border_style_options,
						),
						'container_border_color' => array(
							'label'        => __( 'Color', 'popup-maker' ),
							'type'         => 'color',
							'std'          => '#000000',
							'priority'     => 20,
							'dependencies' => array(
								'container_border_style' => array_keys( PUM_Utils_Array::remove_keys( $border_style_options, array( 'none' ) ) ),
							),
						),
						'container_border_width' => array(
							'label'        => __( 'Thickness', 'popup-maker' ),
							'type'         => 'rangeslider',
							'std'          => 1,
							'priority'     => 30,
							'step'         => 1,
							'min'          => 1,
							'max'          => 5,
							'unit'         => 'px',
							'dependencies' => array(
								'container_border_style' => array_keys( PUM_Utils_Array::remove_keys( $border_style_options, array( 'none' ) ) ),
							),
						),
					),
					'boxshadow'  => array(
						'container_boxshadow_color'      => array(
							'label'    => __( 'Color', 'popup-maker' ),
							'type'     => 'color',
							'std'      => '#020202',
							'priority' => 10,
						),
						'container_boxshadow_opacity'    => array(
							'label'        => __( 'Opacity', 'popup-maker' ),
							'type'         => 'rangeslider',
							'std'          => 23,
							'priority'     => 20,
							'step'         => 1,
							'min'          => 0,
							'max'          => 100,
							'force_minmax' => true,
							'unit'         => '%',
						),
						'container_boxshadow_horizontal' => array(
							'label'    => __( 'Horizontal Position', 'popup-maker' ),
							'type'     => 'rangeslider',
							'std'      => 1,
							'priority' => 30,
							'step'     => 1,
							'min'      => - 50,
							'max'      => 50,
							'unit'     => 'px',
						),
						'container_boxshadow_vertical'   => array(
							'label'    => __( 'Vertical Position', 'popup-maker' ),
							'type'     => 'rangeslider',
							'std'      => 1,
							'priority' => 40,
							'step'     => 1,
							'min'      => - 50,
							'max'      => 50,
							'unit'     => 'px',
						),
						'container_boxshadow_blur'       => array(
							'label'    => __( 'Blur Radius', 'popup-maker' ),
							'type'     => 'rangeslider',
							'std'      => 3,
							'priority' => 50,
							'step'     => 1,
							'min'      => 0,
							'max'      => 100,
							'unit'     => 'px',
						),
						'container_boxshadow_spread'     => array(
							'label'    => __( 'Spread', 'popup-maker' ),
							'type'     => 'rangeslider',
							'std'      => 0,
							'priority' => 60,
							'step'     => 1,
							'min'      => - 100,
							'max'      => 100,
							'unit'     => 'px',
						),
						'container_boxshadow_inset'      => array(
							'label'       => __( 'Inset', 'popup-maker' ),
							'description' => __( 'Set the box shadow to inset (inner shadow).', 'popup-maker' ),
							'type'        => 'select',
							'std'         => 'no',
							'priority'    => 70,
							'options'     => array(
								'no'  => __( 'No', 'popup-maker' ),
								'yes' => __( 'Yes', 'popup-maker' ),
							),
						),
					),
				) ),
				'title'     => apply_filters( 'pum_popup_title_settings_fields', array(
					'typography' => array(
						'title_font_color'  => array(
							'label'    => __( 'Color', 'popup-maker' ),
							'type'     => 'color',
							'std'      => '#000000',
							'priority' => 10,
						),
						'title_font_size'   => array(
							'label'    => __( 'Font Size', 'popup-maker' ),
							'type'     => 'rangeslider',
							'std'      => 32,
							'priority' => 20,
							'step'     => 1,
							'min'      => 8,
							'max'      => 48,
							'unit'     => 'px',
						),
						'title_line_height' => array(
							'label'    => __( 'Line Height', 'popup-maker' ),
							'type'     => 'rangeslider',
							'std'      => 36,
							'priority' => 30,
							'step'     => 1,
							'min'      => 8,
							'max'      => 54,
							'unit'     => 'px',
						),
						'title_font_family' => array(
							'label'    => __( 'Font Family', 'popup-maker' ),
							'type'     => 'select',
							'select2'  => true,
							'std'      => 'inherit',
							'priority' => 40,
							'options'  => $font_family_options,
						),
						'title_font_weight' => array(
							'label'        => __( 'Font Weight', 'popup-maker' ),
							'type'         => 'select',
							'std'          => 'inherit',
							'priority'     => 50,
							'options'      => $font_weight_options,
							'dependencies' => array(
								'title_font_family' => array_keys( PUM_Utils_Array::remove_keys( $font_family_options, array( 'inherit' ) ) ),
							),
						),
						'title_font_style'  => array(
							'label'        => __( 'Style', 'popup-maker' ),
							'type'         => 'select',
							'std'          => 'normal',
							'priority'     => 60,
							'options'      => array(
								''       => __( 'Normal', 'popup-maker' ),
								'italic' => __( 'Italic', 'popup-maker' ),
							),
							'dependencies' => array(
								'title_font_family' => array_keys( PUM_Utils_Array::remove_keys( $font_family_options, array( 'inherit' ) ) ),
							),
						),
						'title_text_align'  => array(
							'label'    => __( 'Alignment', 'popup-maker' ),
							'type'     => 'select',
							'std'      => 'left',
							'priority' => 70,
							'options'  => array(
								'left'    => __( 'Left', 'popup-maker' ),
								'center'  => __( 'Center', 'popup-maker' ),
								'right'   => __( 'Right', 'popup-maker' ),
								'justify' => __( 'Justify', 'popup-maker' ),
							),
						),
					),
					'textshadow' => array(
						'title_textshadow_color'      => array(
							'label'    => __( 'Color', 'popup-maker' ),
							'type'     => 'color',
							'std'      => '#020202',
							'priority' => 10,
						),
						'title_textshadow_opacity'    => array(
							'label'        => __( 'Opacity', 'popup-maker' ),
							'type'         => 'rangeslider',
							'std'          => 23,
							'priority'     => 20,
							'step'         => 1,
							'min'          => 0,
							'max'          => 100,
							'force_minmax' => true,
							'unit'         => '%',
						),
						'title_textshadow_horizontal' => array(
							'label'    => __( 'Horizontal Position', 'popup-maker' ),
							'type'     => 'rangeslider',
							'std'      => 0,
							'priority' => 30,
							'step'     => 1,
							'min'      => - 50,
							'max'      => 50,
							'unit'     => 'px',
						),
						'title_textshadow_vertical'   => array(
							'label'    => __( 'Vertical Position', 'popup-maker' ),
							'type'     => 'rangeslider',
							'std'      => 0,
							'priority' => 40,
							'step'     => 1,
							'min'      => - 50,
							'max'      => 50,
							'unit'     => 'px',
						),
						'title_textshadow_blur'       => array(
							'label'    => __( 'Blur Radius', 'popup-maker' ),
							'type'     => 'rangeslider',
							'std'      => 0,
							'priority' => 50,
							'step'     => 1,
							'min'      => 0,
							'max'      => 100,
							'unit'     => 'px',
						),
					),
				) ),
				'content'   => apply_filters( 'pum_popup_content_settings_fields', array(
					'typography' => array(
						'content_font_color'  => array(
							'label'    => __( 'Color', 'popup-maker' ),
							'type'     => 'color',
							'std'      => '#8c8c8c',
							'priority' => 10,
						),
						'content_font_family' => array(
							'label'    => __( 'Font Family', 'popup-maker' ),
							'type'     => 'select',
							'select2'  => true,
							'std'      => 'inherit',
							'priority' => 20,
							'options'  => $font_family_options,
						),
						'content_font_weight' => array(
							'label'        => __( 'Font Weight', 'popup-maker' ),
							'type'         => 'select',
							'std'          => 'inherit',
							'priority'     => 30,
							'options'      => $font_weight_options,
							'dependencies' => array(
								'content_font_family' => array_keys( PUM_Utils_Array::remove_keys( $font_family_options, array( 'inherit' ) ) ),
							),
						),
						'content_font_style'  => array(
							'label'        => __( 'Style', 'popup-maker' ),
							'type'         => 'select',
							'std'          => 'inherit',
							'priority'     => 40,
							'options'      => array(
								''       => __( 'Normal', 'popup-maker' ),
								'italic' => __( 'Italic', 'popup-maker' ),
							),
							'dependencies' => array(
								'content_font_family' => array_keys( PUM_Utils_Array::remove_keys( $font_family_options, array( 'inherit' ) ) ),
							),
						),
					),
				) ),
				'close'     => apply_filters( 'pum_popup_close_settings_fields', array(
					'main'       => array(
						'close_text' => array(
							'label'       => __( 'Close Text', 'popup-maker' ),
							'placeholder' => __( 'CLOSE', 'popup-maker' ),
							'description' => __( 'Enter the close button text.', 'popup-maker' ),
							'std'         => __( 'CLOSE', 'popup-maker' ),
							'priority'    => 10,
						),
					),
					'size'       => array(
						'close_padding'       => array(
							'label'    => __( 'Padding', 'popup-maker' ),
							'type'     => 'rangeslider',
							'std'      => 8,
							'priority' => 10,
							'step'     => 1,
							'min'      => 0,
							'max'      => 100,
							'unit'     => 'px',
						),
						'close_height'        => array(
							'label'    => __( 'Height', 'popup-maker' ),
							'type'     => 'rangeslider',
							'std'      => 0,
							'priority' => 20,
							'step'     => 1,
							'min'      => 0,
							'max'      => 100,
							'unit'     => 'px',
						),
						'close_width'         => array(
							'label'    => __( 'Width', 'popup-maker' ),
							'type'     => 'rangeslider',
							'std'      => 0,
							'priority' => 30,
							'step'     => 1,
							'min'      => 0,
							'max'      => 100,
							'unit'     => 'px',
						),
						'close_border_radius' => array(
							'label'       => __( 'Border Radius', 'popup-maker' ),
							'description' => __( 'Choose a corner radius for your close button.', 'popup-maker' ),
							'type'        => 'rangeslider',
							'std'         => 0,
							'priority'    => 40,
							'step'        => 1,
							'min'         => 1,
							'max'         => 28,
							'unit'        => 'px',
						),
					),
					'position'   => array(
						'close_location'        => array(
							'label'       => __( 'Location', 'popup-maker' ),
							'description' => __( 'Choose which corner the close button will be positioned.', 'popup-maker' ),
							'type'        => 'select',
							'std'         => 'topright',
							'priority'    => 10,
							'options'     => array(
								'topleft'     => __( 'Top Left', 'popup-maker' ),
								'topright'    => __( 'Top Right', 'popup-maker' ),
								'bottomleft'  => __( 'Bottom Left', 'popup-maker' ),
								'bottomright' => __( 'Bottom Right', 'popup-maker' ),
							),
						),
						'close_position_top'    => array(
							'label'        => __( 'Top', 'popup-maker' ),
							'type'         => 'rangeslider',
							'std'          => 0,
							'priority'     => 20,
							'step'         => 1,
							'min'          => - 100,
							'max'          => 100,
							'unit'         => 'px',
							'dependencies' => array(
								'close_location' => array( 'topleft', 'topright' ),
							),
						),
						'close_position_left'   => array(
							'label'        => __( 'Left', 'popup-maker' ),
							'type'         => 'rangeslider',
							'std'          => 0,
							'priority'     => 30,
							'step'         => 1,
							'min'          => - 100,
							'max'          => 100,
							'unit'         => 'px',
							'dependencies' => array(
								'close_location' => array( 'topleft', 'bottomleft' ),
							),
						),
						'close_position_bottom' => array(
							'label'        => __( 'Bottom', 'popup-maker' ),
							'type'         => 'rangeslider',
							'std'          => 0,
							'priority'     => 40,
							'step'         => 1,
							'min'          => - 100,
							'max'          => 100,
							'unit'         => 'px',
							'dependencies' => array(
								'close_location' => array( 'bottomleft', 'bottomright' ),
							),
						),
						'close_position_right'  => array(
							'label'        => __( 'Right', 'popup-maker' ),
							'type'         => 'rangeslider',
							'std'          => 0,
							'priority'     => 50,
							'step'         => 1,
							'min'          => - 100,
							'max'          => 100,
							'unit'         => 'px',
							'dependencies' => array(
								'close_location' => array( 'topright', 'bottomright' ),
							),
						),
					),
					'background' => array(
						'close_background_color'   => array(
							'label'    => __( 'Color', 'popup-maker' ),
							'type'     => 'color',
							'std'      => '#00b7cd',
							'priority' => 10,
						),
						'close_background_opacity' => array(
							'label'        => __( 'Opacity', 'popup-maker' ),
							'type'         => 'rangeslider',
							'std'          => 100,
							'priority'     => 20,
							'step'         => 1,
							'min'          => 0,
							'max'          => 100,
							'unit'         => '%',
							'force_minmax' => true,
						),
					),
					'typography' => array(
						'close_font_color'  => array(
							'label'    => __( 'Color', 'popup-maker' ),
							'type'     => 'color',
							'std'      => '#ffffff',
							'priority' => 10,
						),
						'close_font_size'   => array(
							'label'    => __( 'Font Size', 'popup-maker' ),
							'type'     => 'rangeslider',
							'std'      => 12,
							'priority' => 20,
							'step'     => 1,
							'min'      => 8,
							'max'      => 32,
							'unit'     => 'px',
						),
						'close_line_height' => array(
							'label'    => __( 'Line Height', 'popup-maker' ),
							'type'     => 'rangeslider',
							'std'      => 36,
							'priority' => 30,
							'step'     => 1,
							'min'      => 8,
							'max'      => 54,
							'unit'     => 'px',
						),
						'close_font_family' => array(
							'label'    => __( 'Font Family', 'popup-maker' ),
							'type'     => 'select',
							'select2'  => true,
							'std'      => 'inherit',
							'priority' => 40,
							'options'  => $font_family_options,
						),
						'close_font_weight' => array(
							'label'        => __( 'Font Weight', 'popup-maker' ),
							'type'         => 'select',
							'std'          => 'inherit',
							'priority'     => 50,
							'options'      => $font_weight_options,
							'dependencies' => array(
								'close_font_family' => array_keys( PUM_Utils_Array::remove_keys( $font_family_options, array( 'inherit' ) ) ),
							),
						),
						'close_font_style'  => array(
							'label'        => __( 'Style', 'popup-maker' ),
							'type'         => 'select',
							'std'          => 'inherit',
							'priority'     => 60,
							'options'      => array(
								''       => __( 'Normal', 'popup-maker' ),
								'italic' => __( 'Italic', 'popup-maker' ),
							),
							'dependencies' => array(
								'close_font_family' => array_keys( PUM_Utils_Array::remove_keys( $font_family_options, array( 'inherit' ) ) ),
							),
						),
					),
					'border'     => array(
						'close_border_style' => array(
							'label'       => __( 'Style', 'popup-maker' ),
							'description' => __( 'Choose a border style for your close button.', 'popup-maker' ),
							'type'        => 'select',
							'std'         => 'none',
							'priority'    => 10,
							'options'     => $border_style_options,
						),
						'close_border_color' => array(
							'label'        => __( 'Color', 'popup-maker' ),
							'type'         => 'color',
							'std'          => '#ffffff',
							'priority'     => 20,
							'dependencies' => array(
								'close_border_style' => array_keys( PUM_Utils_Array::remove_keys( $border_style_options, array( 'none' ) ) ),
							),
						),
						'close_border_width' => array(
							'label'        => __( 'Thickness', 'popup-maker' ),
							'type'         => 'rangeslider',
							'std'          => 1,
							'priority'     => 30,
							'step'         => 1,
							'min'          => 1,
							'max'          => 5,
							'unit'         => 'px',
							'dependencies' => array(
								'close_border_style' => array_keys( PUM_Utils_Array::remove_keys( $border_style_options, array( 'none' ) ) ),
							),
						),
					),
					'boxshadow'  => array(
						'close_boxshadow_color'      => array(
							'label'    => __( 'Color', 'popup-maker' ),
							'type'     => 'color',
							'std'      => '#020202',
							'priority' => 10,
						),
						'close_boxshadow_opacity'    => array(
							'label'        => __( 'Opacity', 'popup-maker' ),
							'type'         => 'rangeslider',
							'std'          => 23,
							'priority'     => 20,
							'step'         => 1,
							'min'          => 0,
							'max'          => 100,
							'unit'         => '%',
							'force_minmax' => true,
						),
						'close_boxshadow_horizontal' => array(
							'label'    => __( 'Horizontal Position', 'popup-maker' ),
							'type'     => 'rangeslider',
							'std'      => 1,
							'priority' => 30,
							'step'     => 1,
							'min'      => - 50,
							'max'      => 50,
							'unit'     => 'px',
						),
						'close_boxshadow_vertical'   => array(
							'label'    => __( 'Vertical Position', 'popup-maker' ),
							'type'     => 'rangeslider',
							'std'      => 1,
							'priority' => 40,
							'step'     => 1,
							'min'      => - 50,
							'max'      => 50,
							'unit'     => 'px',
						),
						'close_boxshadow_blur'       => array(
							'label'    => __( 'Blur Radius', 'popup-maker' ),
							'type'     => 'rangeslider',
							'std'      => 3,
							'priority' => 50,
							'step'     => 1,
							'min'      => 0,
							'max'      => 100,
							'unit'     => 'px',
						),
						'close_boxshadow_spread'     => array(
							'label'    => __( 'Spread', 'popup-maker' ),
							'type'     => 'rangeslider',
							'std'      => 0,
							'priority' => 60,
							'step'     => 1,
							'min'      => - 100,
							'max'      => 100,
							'unit'     => 'px',
						),
						'close_boxshadow_inset'      => array(
							'label'       => __( 'Inset', 'popup-maker' ),
							'description' => __( 'Set the box shadow to inset (inner shadow).', 'popup-maker' ),
							'type'        => 'select',
							'std'         => 'no',
							'priority'    => 70,
							'options'     => array(
								'no'  => __( 'No', 'popup-maker' ),
								'yes' => __( 'Yes', 'popup-maker' ),
							),
						),
					),
					'textshadow' => array(
						'close_textshadow_color'      => array(
							'label'    => __( 'Color', 'popup-maker' ),
							'type'     => 'color',
							'std'      => '#000000',
							'priority' => 10,
						),
						'close_textshadow_opacity'    => array(
							'label'        => __( 'Opacity', 'popup-maker' ),
							'type'         => 'rangeslider',
							'std'          => 23,
							'priority'     => 20,
							'step'         => 1,
							'min'          => 0,
							'max'          => 100,
							'force_minmax' => true,
							'unit'         => '%',
						),
						'close_textshadow_horizontal' => array(
							'label'    => __( 'Horizontal Position', 'popup-maker' ),
							'type'     => 'rangeslider',
							'std'      => 0,
							'priority' => 30,
							'step'     => 1,
							'min'      => - 50,
							'max'      => 50,
							'unit'     => 'px',
						),
						'close_textshadow_vertical'   => array(
							'label'    => __( 'Vertical Position', 'popup-maker' ),
							'type'     => 'rangeslider',
							'std'      => 0,
							'priority' => 40,
							'step'     => 1,
							'min'      => - 50,
							'max'      => 50,
							'unit'     => 'px',
						),
						'close_textshadow_blur'       => array(
							'label'    => __( 'Blur Radius', 'popup-maker' ),
							'type'     => 'rangeslider',
							'std'      => 0,
							'priority' => 50,
							'step'     => 1,
							'min'      => 0,
							'max'      => 100,
							'unit'     => 'px',
						),
					),
				) ),
				'advanced'  => apply_filters( 'pum_popup_advanced_settings_fields', array(
					'main' => array(),
				) ),
			) );

			$fields = PUM_Utils_Fields::parse_tab_fields( $fields, array(
				'has_sections' => true,
				'name'         => 'theme_settings[%s]',
			) );

		}

		return $fields;
	}

	/**
	 * @return array
	 */
	public static function defaults() {
		return PUM_Utils_Fields::get_form_default_values( self::fields() );
	}
}

