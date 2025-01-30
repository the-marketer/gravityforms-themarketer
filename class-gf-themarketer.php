<?php
/**
 * @copyright   Copyright (c) 2024 TheMarketer.com
 * @project     TheMarketer.com
 * @website     https://themarketer.com/
 * @author      TheMarketer
 * @license     https://opensource.org/licenses/osl-3.0.php - Open Software License (OSL 3.0)
 */

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

GFForms::include_feed_addon_framework();

class GFtheMarketer extends GFFeedAddOn {
	protected $_version = GF_THEMARKETER_VERSION;
	private static $_instance = null;
	protected $_min_gravityforms_version = '2.5.0';
	protected $_slug = 'gravityformsthemarketer';
	protected $_path = 'gravityformsthemarketer/themarketer.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Gravity Forms theMarketer Add-On';
	protected $_short_title = 'theMarketer';
	protected $api = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return GFtheMarketer
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFtheMarketer();
		}

		return self::$_instance;
	}


	/**
	 * Autoload the required libraries.
	 * @uses GFAddOn::is_gravityforms_supported()
	 */
	public function pre_init() {
		parent::pre_init();

		if ( $this->is_gravityforms_supported() ) {

			// Load the theMarketer API library.
			if ( ! class_exists( 'GF_theMarketer_API' ) ) {
				require_once( 'includes/class-gf-themarketer-api.php' );
			}

		}
	}

	/**
	 * Plugin starting point.
	 */
	public function init() {
		parent::init();

		$api_initialized = $this->initialize_api();

		if ( $api_initialized ) {
			// Add new subscriber on any form submission
			add_action( 'gform_after_submission', array( $this, 'process_feed' ), 10, 3 );

			// Deactivate on platform if plugin deactivated
			add_action( 'deactivate_gravityformsthemarketer/themarketer.php', array(
				$this,
				'uninstall'
			) );
		};
	}

	/**
	 * Return the plugin's icon for the plugin/form settings menu.
	 *
	 * @return string
	 * @since 4.7
	 *
	 */
	public function get_menu_icon() {
		return 'https://cdn1.mktr2.com/media/logo-for-gravityforms.svg';
	}

	// # FEED PROCESSING -----------------------------------------------------------------------------------------------

	/**
	 * Process the feed e.g. subscribe the user to a list.
	 *
	 * @param array $feed The feed object to be processed.
	 * @param array $entry The entry object currently being processed.
	 * @param array $form The form object currently being processed.
	 *
	 * @return bool|void
	 */
	public function process_feed( $feed, $entry, $form ) {
		$input_ids = array();

		foreach ( $entry['fields'] as $field ) {
			$fieldType   = strtolower( $field['type'] );
			$fieldInputs = $field['inputs'];

			if ( $fieldType === 'name' ) {
				foreach ( $fieldInputs as $input ) {
					if ( strtolower( trim( $input['label'] ) ) === 'first' ) {
						$input_ids['firstName'] = floatval( $input['id'] );
					}
					if ( strtolower( trim( $input['label'] ) ) === 'last' ) {
						$input_ids['lastName'] = floatval( $input['id'] );
					}
				};
			};

			if ( $fieldType === 'email' ) {
				foreach ( $fieldInputs as $input ) {
					$input_ids['email'] = intval( $input['id'] );

				}
			};

			if ( $fieldType === 'phone' ) {
				$input_ids['phone'] = $field['id'];
			};
		}

		$input_values = array();

		foreach ( $feed as $key => $value ) {
			if ( array_key_exists( 'email', $input_ids ) && $key == $input_ids['email'] ) {
				$input_values['email'] = $value;
			}

			if ( array_key_exists( 'firstName', $input_ids ) && $key == $input_ids['firstName'] ) {
				$input_values['firstname'] = $value;
			}

			if ( array_key_exists( 'lastName', $input_ids ) && $key == $input_ids['lastName'] ) {
				$input_values['lastname'] = $value;
			}

			if ( array_key_exists( 'phone', $input_ids ) && $key == $input_ids['phone'] ) {
				$input_values['phone'] = $value;
			}
		}

		$input_values['add_tags'] = 'GravityForms';

		if ( count( $input_values ) ) {
			$this->api->add_new_subscriber( $input_values );
		}

	}

	// # SCRIPTS & STYLES -----------------------------------------------------------------------------------------------

	/**
	 * Return the scripts which should be enqueued.
	 *
	 * @return array
	 */
	public function scripts() {
		$scripts = array(
			array(
				'handle'  => 'script_js',
				'src'     => $this->get_base_url() . '/js/script.js',
				'version' => $this->_version,
				'deps'    => array( 'jquery' ),
				'strings' => array(
					'first'  => esc_html__( 'First Choice', 'themarketer' ),
					'second' => esc_html__( 'Second Choice', 'themarketer' ),
					'third'  => esc_html__( 'Third Choice', 'themarketer' ),
				),
				'enqueue' => array(
					array(
						'admin_page' => array( 'form_settings' ),
						'tab'        => 'themarketer',
					),
				),
			),
		);

		return array_merge( parent::scripts(), $scripts );
	}

	/**
	 * Return the stylesheets which should be enqueued.
	 *
	 * @return array
	 */
	public function styles() {
		$styles = array(
			array(
				'handle'  => 'styles_css',
				'src'     => $this->get_base_url() . '/css/styles.css',
				'version' => $this->_version,
				'enqueue' => array(
					array( 'field_types' => array( 'poll' ) ),
				),
			),
		);

		return array_merge( parent::styles(), $styles );
	}

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		return array(
			array(
				'title'       => '',
				'description' => $this->plugin_settings_description(),
				'fields'      => array(
					array(
						'name'                => 'rest_key',
						'label'               => esc_html__( 'REST Key', 'themarketer' ),
						'type'                => 'text',
						'class'               => 'medium',
						'feedback_callback'   => array( $this, 'initialize_api' ),
						'validation_callback' => array( $this, 'validate_field_setting' ),
						'error_message'       => esc_html__( 'REST KEY could be invalid.', 'themarketer' )
					),
					array(
						'name'                => 'customer_id',
						'label'               => esc_html__( 'Customer ID', 'themarketer' ),
						'type'                => 'text',
						'class'               => 'large',
						'feedback_callback'   => array( $this, 'initialize_api' ),
						'validation_callback' => array( $this, 'validate_field_setting' ),
						'error_message'       => esc_html__( 'Customer ID could be invalid.', 'themarketer' )
					),
					array(
						'name'          => 'validated_credentials',
						'type'          => 'hidden',
						'default_value' => false,
					),
					array(
						'type'     => 'save',
						'messages' => array(
							'success' => esc_html__( 'theMarketer settings have been updated.', 'themarketer' ),
							'error'   => esc_html__( 'Unable to validate credentials!', 'themarketer' )
						),
					),
				),
			),
		);
	}

	/**
	 * Custom setting validation for theMarketer setting fields
	 *
	 * @return void
	 */
	public function validate_field_setting( $field, $field_setting ) {
		if ( ! $this->initialize_api() ) {
			$this->set_field_error( $field, rgar( $field, 'error_message' ) );
		}
	}

	/**
	 * Prepare plugin settings description.
	 *
	 * @return string
	 */
	public function plugin_settings_description() {
		$description = '<p>';
		$description .= esc_html__( 'Use Gravity Forms to collect customer information and automatically add it to your theMarketer account.', 'themarketer' );
		$description .= '</p>';

		if ( ! $this->initialize_api() ) {

			$description .= '<p>';
			$description .= esc_html__( 'Gravity Forms theMarketer Add-On requires your REST Key and Customer ID, which can be found in the Technical Integration tab on the account settings page.', 'themarketer' );
			$description .= '</p>';

			$description .= '<p>';
			$description .= sprintf(
				esc_html__( 'If you don\'t have an account, you can %1$ssign up for one here.%2$s', 'themarketer' ),
				'<a href="https://www.themarketer.com/" target="_blank">',
				'</a>'
			);
			$description .= '</p>';
		}

		return $description;
	}


	/*
	 * Checks validity of theMarketer API credentials and initializes API if valid.
	 *
	 * @return bool|null
	 */
	public function initialize_api() {

		if ( $this->api instanceof GF_theMarketer_API ) {
			return true;
		}

		/* Get the plugin settings */
		$settings = $this->get_saved_plugin_settings();

		/* If any of the account information fields are empty, return null. */
		if ( rgempty( 'rest_key', $settings ) || rgempty( 'customer_id', $settings ) ) {
			return false;
		}

		/* Load theMarketer API library. */
		require_once 'includes/class-gf-themarketer-api.php';

		$this->log_debug( __METHOD__ . "(): Validating API info for {$settings['rest_key']} / {$settings['customer_id']}." );

		$theMarketerAPI = new GF_theMarketer_API( $settings['rest_key'], $settings['customer_id'] );

		if ( $theMarketerAPI->verify_credentials() ) {
			$this->api = $theMarketerAPI;

			return true;
		}

		return false;
	}

	/**
	 * Remove unneeded settings.
	 *
	 * @since  4.0
	 * @access public
	 */
	public function uninstall() {

		parent::uninstall();

		GFCache::delete( 'themarketer_plugin_settings' );
		delete_option( 'gravityformsaddon_gravityformsthemarketer_settings' );
		delete_option( 'gravityformsaddon_gravityformsthemarketer_version' );

		$this->api->uninstall();

	}

	/**
	 * Gets the saved plugin settings, either from the database or the post request.
	 *
	 * This is a helper method that ensures the feedback callback receives the right value if the newest values
	 * are posted to the settings page.
	 *
	 * @return array
	 * @since 1.0
	 *
	 */
	private function get_saved_plugin_settings() {
		$prefix                = $this->is_gravityforms_supported( '2.5' ) ? '_gform_setting' : '_gaddon_setting';
		$rest_key              = rgpost( "{$prefix}_rest_key" );
		$customer_id           = rgpost( "{$prefix}_customer_id" );
		$validated_credentials = rgpost( "{$prefix}_validated_credentials" );

		$settings = $this->get_plugin_settings();
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		if ( ! $this->is_plugin_settings( $this->_slug ) || ! ( $customer_id && $rest_key ) ) {
			return $settings;
		}

		$settings['customer_id']           = sanitize_title( $customer_id );
		$settings['rest_key']              = sanitize_title( $rest_key );
		$settings['validated_credentials'] = false;

		return $settings;
	}


	/**
	 * If the api keys are invalid or empty return the appropriate message.
	 *
	 * @return string
	 */
	public function configure_addon_message() {
		$settings_label = sprintf( esc_html__( '%s Settings', 'gravityforms' ), $this->get_short_title() );
		$settings_link  = sprintf( '<a href="%s">%s</a>', esc_url( $this->get_plugin_settings_url() ), $settings_label );

		if ( ! $this->initialize_api() ) {

			return sprintf( esc_html__( 'To get started, please configure your %s.', 'gravityforms' ), $settings_link );
		}

		return sprintf( esc_html__( 'Please make sure you have entered valid API credentials on the %s page.', 'themarketer' ), $settings_link );
	}

	/**
	 * Configures the settings which should be rendered on the feed edit page in the Form Settings > Feed Add-On area.
	 *
	 * @return array
	 */
	public function feed_settings_fields() {
		return array(
			array(
				'title'  => esc_html__( 'Feed Settings', 'themarketer' ),
				'fields' => array(
					array(
						'label'   => esc_html__( 'Feed name', 'themarketer' ),
						'type'    => 'text',
						'name'    => 'feedName',
						'tooltip' => esc_html__( 'This is the tooltip', 'themarketer' ),
						'class'   => 'small',
					),
					array(
						'label'   => esc_html__( 'Textbox', 'themarketer' ),
						'type'    => 'text',
						'name'    => 'mytextbox',
						'tooltip' => esc_html__( 'This is the tooltip', 'themarketer' ),
						'class'   => 'small',
					),
					array(
						'label'   => esc_html__( 'Encrypted text', 'themarketer' ),
						'type'    => 'text',
						'name'    => 'encryptedtext',
						'encrypt' => true,
					),
					array(
						'label'   => esc_html__( 'My checkbox', 'themarketer' ),
						'type'    => 'checkbox',
						'name'    => 'mycheckbox',
						'tooltip' => esc_html__( 'This is the tooltip', 'themarketer' ),
						'choices' => array(
							array(
								'label' => esc_html__( 'Enabled', 'themarketer' ),
								'name'  => 'mycheckbox',
							),
						),
					),
					array(
						'name'      => 'mappedFields',
						'label'     => esc_html__( 'Map Fields', 'themarketer' ),
						'type'      => 'field_map',
						'field_map' => array(
							array(
								'name'       => 'email',
								'label'      => esc_html__( 'Email', 'themarketer' ),
								'required'   => 0,
								'field_type' => array( 'email', 'hidden' ),
								'tooltip'    => esc_html__( 'This is the tooltip', 'themarketer' ),
							),
							array(
								'name'     => 'name',
								'label'    => esc_html__( 'Name', 'themarketer' ),
								'required' => 0,
							),
							array(
								'name'       => 'phone',
								'label'      => esc_html__( 'Phone', 'themarketer' ),
								'required'   => 0,
								'field_type' => 'phone',
							),
						),
					),
					array(
						'name'           => 'condition',
						'label'          => esc_html__( 'Condition', 'themarketer' ),
						'type'           => 'feed_condition',
						'checkbox_label' => esc_html__( 'Enable Condition', 'themarketer' ),
						'instructions'   => esc_html__( 'Process this feed if', 'themarketer' ),
					),
				),
			),
		);
	}

	/**
	 * Configures which columns should be displayed on the feed list page.
	 *
	 * @return array
	 */
	public function feed_list_columns() {
		return array(
			'feedName'  => esc_html__( 'Name', 'themarketer' ),
			'mytextbox' => esc_html__( 'My Textbox', 'themarketer' ),
		);
	}

	/**
	 * Format the value to be displayed in the mytextbox column.
	 *
	 * @param array $feed The feed being included in the feed list.
	 *
	 * @return string
	 */
	public function get_column_value_mytextbox( $feed ) {
		return '<b>' . rgars( $feed, 'meta/mytextbox' ) . '</b>';
	}

	/**
	 * Prevent feeds being listed or created if an api key isn't valid.
	 *
	 * @return bool
	 */
	public function can_create_feed() {

		// Get the plugin settings.
		$settings    = $this->get_plugin_settings();
		$rest_key    = rgar( $settings, 'rest_key' );
		$customer_id = rgar( $settings, 'customer_id' );

		if ( ! $rest_key || ! $customer_id ) {
			return false;
		}

		return true;
	}


}
