<?php
/**
 * spark_api
 *
 * @package   sparky_api
 * @author    Scott Evans <git@scott.ee>
 * @license   GPL-2.0+
 * @link      http://scott.ee
 * @copyright 2014 Scott Evans
 */

/**
 * spark_api
 *
 * PHP wrapper for the Spark Core API
 *
 * @package spark_api
 * @author  Scott Evans <git@scott.ee>
 */

class spark_api {

	private $token = false;
	private $api = 'https://api.spark.io/v1';
	private $errors = false;
	public $status = '';

	public function __construct($token = false) {
		
		// Call $plugin_slug from public plugin class.
		$plugin = sparky::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// setup new error object
		$this->errors = new WP_Error();

		// output errors when using this class in the admin
		//if ( is_admin() )
		//	add_action( 'admin_notices', array( $this, 'notice_errors') );

		// Set the token 
		if ( $token ) {
			$this->token = $token;
		} else {
			$this->token = $this->get_token();
		}

		// No token? fail
		if ( is_wp_error( $this->token ) ) 
			$this->error( $this->token );

	}

	// https://api.spark.io/v1/devices?access_token={TOKEN}

	public function spark_devices($cache = false) {
		
		if ( ! $cache ) {

			$devices = $this->request( $this->api . '/devices?access_token=' . $this->token );

		} else { 

			if (false === ( $devices = get_transient( 'sparky-devices' ) ) ) {

				$devices = $this->request( $this->api . '/devices?access_token=' . $this->token );

				if ( ! is_wp_error( $devices ) ) {

					set_transient( 'sparky-devices', $devices, $cache );

				}
			}
		}

		if ( is_wp_error( $devices ) ) {

			return $devices->get_error_message();		

		} else {

			return $devices;
		}
		
	}

	// https://api.spark.io/v1/devices/{DEVICE_ID}?access_token={TOKEN}

	public function spark_device($device_id = false, $cache = false) {

		if ( ! $device_id ) {

			$error = new WP_Error( 'spark_device', __('A device ID was not provided', $this->plugin_slug ) );
			$this->error( $error );
			return $error->get_error_message();

		};

		if ( ! $cache ) {

			$device = $this->request( $this->api . '/devices/' . $device_id . '?access_token=' . $this->token );

		} else {

			if (false === ( $device = get_transient( 'sparky-device-' . sanitize_title_with_dashes( $device_id ) ) ) ) {
				
				$device = $this->request( $this->api . '/devices/' . $device_id . '?access_token=' . $this->token );

				if ( ! is_wp_error( $device ) ) {

					set_transient( 'sparky-device-' . sanitize_title_with_dashes( $device_id ), $device, $cache );
				}	
			}
		}

		if ( is_wp_error( $device ) ) {

			return $device->get_error_message();

		} else {

			return $device;

		}
	}

	// https://api.spark.io/v1/devices/{DEVICE_ID}/{VARIABLE}?access_token={TOKEN}

	public function spark_variable($device_id = false, $variable = false, $cache = false) {

		if ( ! $device_id ) {

			$error = new WP_Error( 'spark_variable', __('A device ID was not provided', $this->plugin_slug ) );
			$this->error( $error );
			return $error->get_error_message();

		};

		if ( ! $variable) {

			$error = new WP_Error( 'spark_variable', __('A device variable was not provided', $this->plugin_slug ) );
			$this->error( $error );
			return $error->get_error_message();

		};

		if ( ! $cache ) {

			$var = $this->request( $this->api . '/devices/' . $device_id . '/' . $variable . '?access_token=' . $this->token );

		} else {

			if (false === ( $var = get_transient( 'sparky-variable-' . sanitize_title_with_dashes( $variable ) ) ) ) {
				
				$var = $this->request( $this->api . '/devices/' . $device_id . '/' . $variable . '?access_token=' . $this->token );

				if ( ! is_wp_error( $var ) ) {
			
					set_transient( 'sparky-variable-' . sanitize_title_with_dashes( $variable ), $var, $cache );

				}
			}

		}

		if ( is_wp_error( $var ) ) {

			return $var->get_error_message();
				
		} else {

			return $var;
		}
	}

	private function request($url = false) {

		$request = wp_remote_get( $url );

		if ( is_wp_error( $request ) ) {

			$this->error( $request );
			return $request;

		} else if ( $request['response']['code'] != 200 ) {

			$body = json_decode( $request['body'], true );
			$error = new WP_Error( 'spark_request', $body['error_description'] );
			$this->error( $error );
			return $error;

		} else {
			
			$body = json_decode( $request['body'], true );
			return $body;
		}
	}

	private function get_token() {

		$options = get_option( 'sparky_options' );
		return ( isset( $options['access_token'] ) && $options['access_token'] != '' ) ? $options['access_token'] : new WP_Error( 'token', __('Please provide a Spark Core API Access Token.', $this->plugin_slug ) );

	}

	private function error( $error ) {

		// grab the message and error code
		$code = $error->get_error_code();
		$message = $error->get_error_message();

		// add to our existing object of all errors
		$this->errors->add($code, $message);

		// used for logging
		do_action( 'spark_error', array($code, $message) );
		
	}

	public function notice_errors() {

		// only show to users who can manage settings
		if ( ! current_user_can( 'manage_options' ) ) 
			return;

		// only show on the sparky post type
		$screen = get_current_screen();
		if ( strpos( $screen->id, 'spark' ) === false ) 
			return;

		if ( ! empty( $this->errors->errors ) ) {
			?>
			<div class="error">
				<?php foreach ($this->errors->get_error_messages() as $key => $message) { ?>
					<p><?php echo $message; ?></p>
				<?php } ?>
			</div>
			<?php
		}
	}
}
