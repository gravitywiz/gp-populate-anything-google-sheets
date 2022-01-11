<?php

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

use \GP_Populate_Anything_Google_Sheets\Dependencies\Google_Client as Google_Client;
use \GP_Populate_Anything_Google_Sheets\Dependencies\Google\Service\Sheets as Google_Service_Sheets;
use \GP_Populate_Anything_Google_Sheets\Dependencies\Google\Service\Drive as Google_Service_Drive;

class GPPA_Object_Type_Google_Sheet extends GPPA_Object_Type {
	const ROW_NUMBER_ID = 'Row Number';

	protected $google_client;

	protected $service_sheets;

	protected $service_drive;

	public $sheet_values_runtime_cache;

	public function __construct( $id ) {
		parent::__construct( $id );

		$addon    = GPPA_Google_Sheets::get_instance();
		$json_key = $addon->get_plugin_setting( 'gcp_service_account_json' );

		if ( ! $this->validate_json_key( $json_key ) ) {
			return;
		}

		$this->google_client = new Google_Client( array(
			'credentials' => $json_key,
		) );

		$this->google_client->useApplicationDefaultCredentials();
		$this->google_client->addScope( array(
			Google_Service_Sheets::SPREADSHEETS_READONLY,
			Google_Service_Drive::DRIVE_READONLY,
		) );

		$this->service_sheets = new Google_Service_Sheets( $this->google_client );
		$this->service_drive  = new Google_Service_Drive( $this->google_client );

		add_action( 'gppa_pre_object_type_query_google_sheet', array( $this, 'add_filter_hooks' ) );
	}

	public function validate_json_key( $json_key ) {
		$key = GPPA_Google_Sheets::maybe_decode_json( $json_key );

		if ( ! $key ) {
			gppa_google_sheets()->log_debug( __METHOD__ . '(): No key provided.' );
			return false;
		}

		if ( rgar( $key, 'type' ) !== 'service_account' ) {
			gppa_google_sheets()->log_debug( sprintf( __METHOD__ . '(): Key is not of the correct type. Type required: "service_account" Type provided: "%s".', rgar( $key, 'type' ) ) );
			return false;
		}

		if ( ! rgar( $key, 'private_key' ) ) {
			gppa_google_sheets()->log_debug( __METHOD__ . '(): A private key was not provided.' );
			return false;
		}

		return true;
	}

	public function add_filter_hooks() {
		add_filter( 'gppa_object_type_google_sheet_filter', array( $this, 'process_filter_default' ), 10, 4 );
	}

	public function get_object_id( $object, $primary_property_value = null ) {
		return $object[ self::ROW_NUMBER_ID ];
	}

	public function get_label() {
		return esc_html__( 'Google Sheet', 'gp-populate-anything' );
	}

	public function get_primary_property() {
		return array(
			'id'       => 'spreadsheet',
			'label'    => esc_html__( 'Spreadsheets', 'gp-populate-anything' ),
			'callable' => array( $this, 'get_spreadsheets' ),
		);
	}

	public function get_spreadsheets() {
		$available_sheets = array();

		foreach ( $this->service_drive->files->listFiles() as $file ) {
			if ( $file['mimeType'] !== 'application/vnd.google-apps.spreadsheet' ) {
				continue;
			}

			$available_sheets[ $file['id'] ] = $file['name'];
		}

		return $available_sheets;
	}

	public function get_groups() {
		return array();
	}

	/**
	 * @param string $spreadsheet_id
	 * @param null|Google\Service\Sheets\Sheet $sheet
	 *
	 * @return array
	 */
	public function get_sheet_raw_values( $spreadsheet_id, $sheet = null ) {

		if ( ! $this->service_sheets ) {
			return array();
		}

		if ( ! empty( $this->sheet_values_runtime_cache[ $spreadsheet_id ] ) ) {
			return $this->sheet_values_runtime_cache[ $spreadsheet_id ];
		}

		$spreadsheet = $this->service_sheets->spreadsheets->get( $spreadsheet_id );
		$sheets      = $spreadsheet->getSheets();

		// Limitation: use first available sheet. Ideally we would have the ability to drill down in primary properties
		// @todo maybe we list all spreadsheets and their sheets as primary properties?
		if ( ! $sheet ) {
			$sheet = apply_filters( 'gppa_google_sheets_selected_sheet', $sheets[0], $sheets, $spreadsheet_id );
		}

		$response = $this->service_sheets->spreadsheets_values->get( $spreadsheet_id, $sheet->getProperties()->title );
		$values   = $response->getValues();

		foreach ( $values as $value_index => $value ) {
			if ( $value_index === 0 ) {
				array_unshift( $values[ $value_index ], self::ROW_NUMBER_ID );

				continue;
			}

			// Add row number to values to serve as the ID of the object.
			array_unshift( $values[ $value_index ], $value_index );
		}

		$this->sheet_values_runtime_cache[ $spreadsheet_id ] = $values;

		return $values;

	}

	/**
	 * Get columns from first row of sheet.
	 *
	 * @param string $spreadsheet_id
	 * @param null|Google\Service\Sheets\Sheet $sheet
	 *
	 * @return string[]
	 */
	public function get_sheet_columns( $spreadsheet_id, $sheet = null ) {
		$values = $this->get_sheet_raw_values( $spreadsheet_id, $sheet );

		if ( empty( $values ) ) {
			return array();
		}

		return $values[0];
	}

	/**
	 * Combine row data with columns and return associative array.
	 *
	 * @param string $spreadsheet_id
	 * @param null|Google\Service\Sheets\Sheet $sheet
	 *
	 * @return array
	 */
	public function get_sheet_rows( $spreadsheet_id, $sheet = null ) {
		$values       = array_slice( $this->get_sheet_raw_values( $spreadsheet_id, $sheet ), 1 );
		$columns      = $this->get_sheet_columns( $spreadsheet_id, $sheet );
		$column_count = count( $columns );

		return array_map( function ( $row ) use ( $columns, $column_count ) {
			return array_combine( $columns, array_slice( array_pad( $row, $column_count, '' ), 0, $column_count ) );
		}, $values );
	}

	/**
	 * @param string|null $spreadsheet_id
	 *
	 * @return array
	 */
	public function get_properties( $spreadsheet_id = null ) {
		$properties = array();

		if ( ! $spreadsheet_id ) {
			return array( $properties );
		}

		$columns = $this->get_sheet_columns( $spreadsheet_id );

		if ( ! $columns ) {
			return array( $properties );
		}

		/**
		 * Extract column names from the first row.
		 */
		foreach ( $columns as $column ) {
			$properties[ $column ] = array(
				'label'     => $column,
				'value'     => $column,
				'orderby'   => true,
				'callable'  => '__return_empty_array',
				'operators' => array(
					'is',
					'isnot',
					'contains',
				),
			);
		}

		return $properties;
	}

	public function process_filter_default( $search, $args ) {

		/**
		 * @var $filter_value
		 * @var $filter
		 * @var $filter_group
		 * @var $filter_group_index
		 * @var $primary_property_value
		 * @var $property
		 * @var $property_id
		 */
		extract( $args );

		$search[ $filter_group_index ][] = array(
			'property' => $property_id,
			'operator' => $filter['operator'],
			'value'    => $filter_value,
		);

		return $search;

	}

	public function perform_search( $var, $search ) {

		$var_value    = strtolower( $var[ $search['property'] ] );
		$search_value = strtolower( $search['value'] );

		switch ( $search['operator'] ) {
			case 'is':
				return ( $var_value == $search_value );

			case 'isnot':
				return ( $var_value != $search_value );

			case 'contains':
				return ( stripos( $var_value, $search_value ) !== false );

			default:
				throw new Error( 'Invalid operator provided.' );
		}

	}

	/**
	 * @param $var
	 * @param $search_params
	 *
	 * @return bool
	 * @todo Move PHP based filtering and ordering into an Object Type that can be easily extended.
	 *
	 * Each search group is an OR
	 *
	 * If everything matches in one group, we can immediately bail out as we have a positive match.
	 *
	 */
	public function search( $var, $search_params ) {
		foreach ( $search_params as $search_group ) {
			$matches_group = true;

			foreach ( $search_group as $search ) {
				$matches_group = $this->perform_search( $var, $search );

				if ( ! $matches_group ) {
					break;
				}
			}

			if ( $matches_group ) {
				return true;
			}
		}

		return false;
	}

	public function query( $args ) {
		/**
		 * @var $filter_value
		 * @var $filter
		 * @var $filter_group
		 * @var $filter_group_index
		 * @var $primary_property_value
		 * @var $property
		 * @var $property_id
		 */
		extract( $args );

		$results       = $this->get_sheet_rows( $primary_property_value );
		$search_params = $this->process_filter_groups( $args );

		if ( ! empty( $search_params ) ) {
			$results = array_filter( $results, function ( $var ) use ( $search_params ) {
				return $this->search( $var, $search_params );
			} );
		}

		$query_limit   = gp_populate_anything()->get_query_limit( $this, $args['field'] );
		$query_results = array_slice( $results, 0, $query_limit );

		return $query_results;

	}

	public function get_object_prop_value( $object, $prop ) {

		if ( ! isset( $object[ $prop ] ) ) {
			return null;
		}

		return $object[ $prop ];

	}

}
