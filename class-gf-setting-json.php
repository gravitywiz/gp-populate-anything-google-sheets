<?php

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

use Gravity_Forms\Gravity_Forms\Settings\Fields;
use Gravity_Forms\Gravity_Forms\Settings\Fields\Textarea;

/**
 * This custom settings field type is needed as the value coming from the database will be parsed into an actual PHP
 * array. This causes errors when trying to display the JSON into the textarea again.
 *
 * Additionally, $allow_html is required for JSON otherwise validation issues will arise.
 */
class GPPA_Sheets_Setting_Field_JSON extends Textarea {

	public $type = 'json';

	public $allow_html = true;

	public function get_value() {
		return json_encode( $this->settings->get_value( $this->name, $this->default_value ) );
	}

}

Fields::register( 'json', 'GPPA_Sheets_Setting_Field_JSON' );
