<?php
/**
 * Plugin Name:  GP Populate Anything - Google Sheets Object Type
 * Plugin URI:   https://gravitywiz.com/populate-gravity-forms-with-google-sheets/
 * Description:  Add Object Type for fetching data from Google Sheets.
 * Author:       Gravity Wiz
 * Version: 0.2.3
 * Author URI:   https://gravitywiz.com
 * License: GPL2
 *
 * @package gp-populate-anything-google-sheets
 * @copyright Copyright (c) 2021-2022, Gravity Wiz, LLC
 * @author Gravity Wiz <support@gravitywiz.com>
 * @license GPLv2
 * @link https://github.com/gravitywiz/gp-populate-anything-google-sheets
 */

define( 'GPPA_GS_VERSION', '0.2.3' );

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