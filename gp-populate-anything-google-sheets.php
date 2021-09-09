<?php
/**
 * Plugin Name:  GP Populate Anything - Google Sheets Object Type
 * Plugin URI:   https://gravitywiz.com/documentation/gravity-forms-populate-anything
 * Description:  Add Object Type for fetching data from Google Sheets.
 * Author:       Gravity Wiz
 * Version: 0.1.2
 * Author URI:   https://gravitywiz.com
 */

define( 'GPPA_GS_VERSION', '0.1.2' );

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

add_action( 'gform_loaded', array( 'GPPA_Google_Sheets_Bootstrap', 'load_addon' ), 5 );

class GPPA_Google_Sheets_Bootstrap {

	public static function load_addon() {
		if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
			return;
		}

		require_once plugin_dir_path( __FILE__ ) . 'class-gppa-google-sheets.php';

		GFAddOn::register( 'GPPA_Google_Sheets' );
	}

}