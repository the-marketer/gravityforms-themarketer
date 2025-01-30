<?php
/**
 * @copyright   Copyright (c) 2024 TheMarketer.com
 * @project     TheMarketer.com
 * @website     https://themarketer.com/
 * @author      TheMarketer
 * @license     https://opensource.org/licenses/osl-3.0.php - Open Software License (OSL 3.0)
 */

class GF_theMarketer_API {
	protected $rest_key;
	protected $customer_id;


	/**
	 * Constructs the API object
	 *
	 * @param string $rest_key rest_key The Rest Key of the domain
	 * @param string $customer_id customer_id The domain ID
	 *
	 * @return void
	 */
	public function __construct( $rest_key, $customer_id ) {
		$this->rest_key    = strtoupper( (string) $rest_key );
		$this->customer_id = strtoupper( (string) $customer_id );
	}

	/**
	 * Verify credentials
	 *
	 * @return bool
	 */
	public function verify_credentials() {
		$response = $this->process_request( 'gravityforms/verify' );

		return boolval( $response );
	}

	/**
	 * Deactivates the integration on the platform
	 *
	 * @return void
	 */
	public function uninstall() {
		$this->process_request( 'gravityforms/uninstall' );
	}

	/**
	 * Add a new subscriber
	 *
	 * @param array $data Data to send, it must contain Subscriber Email, optional: Phone, Firstname, Lastname
	 *
	 * @return array|void
	 */
	public function add_new_subscriber( $data ) {

		if ( array_key_exists( 'email', $data ) ) {
			return $this->process_request( 'add_subscriber', $data );
		} else {
			error_log( "Couldn't add subscriber, the email is NULL" );
		}
	}

	/**
	 * Makes a request to the theMarketer API.
	 *
	 * @param string $path The API action.
	 * @param array $data The request body or query string arguments.
	 * @param string $method The request method; defaults to GET.
	 *
	 * @return array|WP_Error
	 * @throws Exception
	 */
	private function process_request( $path = '', $data = array(), $method = 'POST' ) {
		if ( empty( $path ) ) {
			throw new Exception( 'Path was not provided.' );
		};

		if ( 'GET' === $method ) {
			throw new Exception( "Method not allowed." );
		};

		if ( rgblank( $this->rest_key ) ) {
			throw new Exception( 'Rest key must be defined to make a request.' );
		};

		if ( empty( $this->customer_id ) ) {
			throw new Exception( 'Customer ID must be defined to make a request.' );
		}

		if($path !== 'add_subscriber') {
			$request_url = API_URL . '/' . $path;
		}
		else {
			$request_url = T_API_URL . '/' . $path;
		}

		$args = array(
			'method'    => $method,
			'headers'   => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
			),
			'sslverify' => SSL_MODE,
			'timeout'   => 30
		);

		$data['u']    = $this->customer_id;
		$data['k']    = $this->rest_key;
		$args['body'] = json_encode( $data );

		// TODO: add GET method logic if needed

		$response = wp_remote_request( $request_url, $args );

		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		};

		$response['body'] = json_decode( $response['body'], true );

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( ! in_array( $response_code, array( 200, 204 ) ) ) {
			throw new Exception( 'Error while retrieving response.' );
		};

		if ( ! empty( $return_key ) && isset( $response['body'][ $return_key ] ) ) {
			return $response['body'][ $return_key ];
		}

		return $response['body'];
	}
}
