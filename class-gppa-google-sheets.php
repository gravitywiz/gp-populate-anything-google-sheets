<?php
/**
 * @package gp-populate-anything-google-sheets
 * @copyright Copyright (c) 2021-2022, Gravity Wiz, LLC
 * @author Gravity Wiz <support@gravitywiz.com>
 * @license GPLv2
 * @link https://github.com/gravitywiz/gp-populate-anything-google-sheets
 */
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class GPPA_Google_Sheets extends GFAddOn {

	/**
	 * @var string Version number of the Add-On
	 */
	protected $_version = GPPA_GS_VERSION;
	/**
	 * @var string Gravity Forms minimum version requirement
	 */
	protected $_min_gravityforms_version = '2.4';
	/**
	 * @var string URL-friendly identifier used for form settings, add-on settings, text domain localization...
	 */
	protected $_slug = 'gp-populate-anything-google-sheets';
	/**
	 * @var string Relative path to the plugin from the plugins folder. Example "gravityforms/gravityforms.php"
	 */
	protected $_path = 'gp-populate-anything-google-sheets/gp-populate-anything-google-sheets.php';
	/**
	 * @var string Full path the the plugin. Example: __FILE__
	 */
	protected $_full_path = __FILE__;
	/**
	 * @var string URL to the Gravity Forms website. Example: 'http://www.gravityforms.com' OR affiliate link.
	 */
	protected $_url = 'https://gravitywiz.com/documentation/gravity-forms-populate-anything';
	/**
	 * @var string Title of the plugin to be used on the settings page, form settings and plugins page. Example: 'Gravity Forms MailChimp Add-On'
	 */
	protected $_title = 'GP Populate Anything + Google Sheets';
	/**
	 * @var string Short version of the plugin title to be used on menus and other places where a less verbose string is useful. Example: 'MailChimp'
	 */
	protected $_short_title = 'GPPA + Sheets';

	private static $_instance = null;

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * @credit https://github.com/google/site-kit-wp
	 */
	public function setup_autoload() {
		$class_map = array_merge(
			include plugin_dir_path( __FILE__ ) . 'third-party/vendor/composer/autoload_classmap.php'
		);

		spl_autoload_register(
			function ( $class ) use ( $class_map ) {
				if ( isset( $class_map[ $class ] ) && 'GP_Populate_Anything_Google_Sheets\\Dependencies' === substr( $class, 0, 47 ) ) {
					require_once $class_map[ $class ];
				}
			},
			true,
			true
		);
	}

	public function init() {
		parent::init();

		if ( ! class_exists( 'GPPA_Object_Type' ) ) {
			return;
		}

		$this->setup_autoload();

		require_once plugin_dir_path( __FILE__ ) . 'class-object-type-google-sheet.php';

		gp_populate_anything()->register_object_type( 'google_sheet', 'GPPA_Object_Type_Google_Sheet' );
	}

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		return array(
			array(
				'title'  => esc_html__( 'Google Sheets + GPPA', 'gp-populate-anything-google-sheets' ),
				'fields' => array(
					array(
						'name'    => 'gcp_service_account_json',
						'tooltip' => esc_html__( 'Enter the contents of the JSON key for the desired service account.', 'gp-populate-anything-google-sheets' ),
						'label'   => esc_html__( 'Service Account JSON Key', 'gp-populate-anything-google-sheets' ),
						'type'    => 'json',
						'class'   => 'medium',
					),
				),
			),
		);
	}

	/***
	 * @param array $field - Field array containing the configuration options of this field
	 * @param bool  $echo  = true - true to echo the output to the screen, false to simply return the contents as a string
	 *
	 * @return string The HTML for the field
	 */
	public function settings_json( $field, $echo = true ) {

		// If Settings Renderer is not initialized, return.
		if ( ! $this->get_settings_renderer() ) {
			return null;
		}

		// Force field type.
		$field['type'] = 'json';

		require_once plugin_dir_path( __FILE__ ) . 'class-gf-setting-json.php';

		// Initialize a new field.
		$field = \Gravity_Forms\Gravity_Forms\Settings\Fields::create(
			$field,
			$this->get_settings_renderer()
		);

		// Get markup.
		$html = $field->prepare_markup();

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	public function minimum_requirements() {
		return array(
			'add-ons' => array(
				'gp-populate-anything' => array(
					'Gravity Forms Populate Anything',
				),
			),
			'php' => array(
				'version' => '7.3',
			),
		);
	}

}

function gppa_google_sheets() {
	return GPPA_Google_Sheets::get_instance();
}