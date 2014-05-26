<?php
/**
 * Sparky
 *
 * @package   sparky_admin
 * @author    Scott Evans <git@scott.ee>
 * @license   GPL-2.0+
 * @link      http://scott.ee
 * @copyright 2014 Scott Evans
 */

/**
 * Sparky_Admin class.
 *
 * @package sparky_Admin
 * @author  Scott Evans <git@scott.ee>
 */
class sparky_admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		/*
		 * Call $plugin_slug from public plugin class.
		 */
		$plugin = sparky::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add settings 
		add_action( 'admin_init', array( $this, 'settings' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		// Add admin notice to setup plugin
		add_action( 'admin_notices', array( $this, 'setup_plugin_notice' ) );

		// Add custom meta boxes to the spark post type
		add_action( 'admin_menu', array( $this, 'meta_boxes'), 0 );
		add_action( 'admin_menu', array( $this, 'status_meta_box') );

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		$screen = get_current_screen();

		//if ( $this->plugin_screen_hook_suffix == $screen->id || strpos( $screen->id, 'spark' ) !== false ) {
		wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), sparky::VERSION );
		//}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), sparky::VERSION );
		}

	}

	/**
	 * Setup plugin settings 
	 *
	 * @since  1.0.0
	 * 
	 * @return null
	 */
	public function settings() {

		register_setting( 'sparky_options', 'sparky_options' );

		add_settings_section( 'sparky_api_section', __('API Settings', $this->plugin_slug), array( $this, 'sparky_api_section' ), 'sparky' );
		
		add_settings_field( 'access_token', __('Access Token', $this->plugin_slug ), array( $this, 'sparky_access_token' ), 'sparky', 'sparky_api_section' );

	}

	/**
	 * sparky_api_section - currently empty
	 *
	 * @since  1.0.0
	 * 
	 * @return void
	 */
	public function sparky_api_section() {

	}

	/**
	 * sparky_access_token field for settings API
	 *
	 * @since  1.0.0
	 * 
	 * @return void
	 */
	public function sparky_access_token() {
		$options = get_option( 'sparky_options' );
		$token = isset( $options['access_token'] ) ? $options['access_token'] : '';
		?>
		<input type="text" name="sparky_options[access_token]" value="<?php echo esc_attr( $token ); ?>" size="50" />
		<?php
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		$this->plugin_screen_hook_suffix = add_submenu_page(
			'edit.php?post_type=spark',
			__( 'Spark Core Settings', $this->plugin_slug ),
			__( 'Settings', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {

		include_once( 'views/admin.php' );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'edit.php?post_type=spark&page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}

	/**
	 * setup_plugin_notice
	 *
	 * Show an admin notice when no access token has been set
	 *
	 * @since  1.0.0
	 * 
	 * @return void
	 */
	public function setup_plugin_notice() {

		// only show to users who can manage settings
		if ( ! current_user_can( 'manage_options' ) ) 
			return;

		// only show on the sparky post type
		$screen = get_current_screen();
		if ( strpos( $screen->id, 'spark' ) === false ) 
			return;

		// only show if the option is empty
		$options = get_option( 'sparky_options' );
		if ( ! isset( $options['access_token'] ) || $options['access_token'] == '' ) { 
			$settings_link = admin_url( 'edit.php?post_type=spark&page=' . $this->plugin_slug ); 
			?>
			<div class="error">
				<p><?php echo sprintf( __('Please <a href="%s">add your Spark Core access token</a> before continuing.', $this->plugin_slug), esc_url( $settings_link ) ); ?></a></p>
			</div>
			<?php
		}
	}

	/**
	 * meta_boxes
	 *
	 * Register custom meta boxes for spark CPT
	 *
	 * @since  1.0.0
	 * 
	 * @return void
	 */
	public function meta_boxes() {

		global $pagenow;

		// only add meta boxes to spark post type (as spark_api request can be slow)
		if ( 'post-new.php' == $pagenow || 'post.php' == $pagenow )   {

			if ( isset( $_GET['post'] ) ) {
				if ( 'spark' != get_post_type( $_GET['post'] ) ) return;
			}

			if ( isset( $_GET['post_type'] ) ) {
				if ( 'spark' != $_GET['post_type'] ) return; 
			}

			$sa = new spark_api();
			$getcores = $sa->spark_devices();
			$cores = array();

			if ( ! empty($getcores) ) {
				foreach ($getcores as $core) {
					$cores[$core['id']] = $core['name'] .' ('. $core['id'] .')';;
				}
			}

			$fields = array(
				'core' => array(
					'label'       => __( 'Spark Core ID', $this->plugin_slug ),
					'type'        => 'select',
					'options'	  => $cores,
					'required'    => true,
					'default' 	  => __( 'Choose a core', $this->plugin_slug ) 
				),
				'variable' => array(
					'label'       => __( 'Variable Name', $this->plugin_slug ),
					'type'        => 'text',
					'required'    => true
				),
				'cache' => array(
					'label' => __( 'Cache', $this->plugin_slug ),
					'type' => 'select',
					'options' => apply_filters( 'sparky_cache_values', array(
						'0'			=> __( 'None', $this->plugin_slug ),
						'60'		=> __( '60 Seconds', $this->plugin_slug ),
						'300'		=> __( '5 Minutes', $this->plugin_slug ),
						'600'		=> __( '10 Minutes', $this->plugin_slug ),
						'3600'		=> __( '1 Hour', $this->plugin_slug ),
						'21600'		=> __( '6 Hours', $this->plugin_slug ),
						'86400'		=> __( '1 Day', $this->plugin_slug )
					)),
				)
			);

			WGMetaBox::add_meta_box( 'spark', __( 'Spark', $this->plugin_slug ), $fields, 'spark', 'normal', 'high' );
		}
	}

	public function status_meta_box() {

		add_meta_box( 'sparky_status', __( 'Status', $this->plugin_slug), array( $this, 'status_box' ), 'spark', 'side' );

	}

	public function status_box( $post ) {

		if ( $post->post_status != 'publish') {

			?><p><?php _e('Save your Spark to see status.', $this->plugin_slug ); ?></p><?php
		
		} else {

			$core = get_post_meta( $post->ID, 'spark-core', true );
			$variable = get_post_meta( $post->ID, 'spark-variable', true );
			$cache = get_post_meta( $post->ID, 'spark-cache', true );

			$sa = new spark_api();
			$status = $sa->spark_devices();

			if ( is_array( $status ) ) { 
				foreach ($status as $stat) {
					if ( $stat['id'] == $core) {
						if ( $stat['connected'] ) {
							$online = true;
							$corestatus = __('Online', $this->plugin_slug);
						} else {
							$online = false;
							$corestatus = __('Offline', $this->plugin_slug);
						} 
					}
				}
			} else {
				$corestatus = $status;
			}

			?>
			<h4><?php _e('Live Data', $this->plugin_slug); ?></h4>
			
			<ul>
				<li><?php _e('Core:', $this->plugin_slug); ?> <?php echo $corestatus; ?></li>
				<?php if ($online) { ?>
					<?php
					$value = $sa->spark_variable( $core, $variable, false);

					if ( is_array($value) ) {
						$livevalue = $value['result'];
					} else {
						$livevalue = $value;
					}
					?>
					<li><?php _e('Value:', $this->plugin_slug); ?> <?php echo $livevalue; ?></li>
				<?php } ?>
			</ul>

			<h4><?php _e('Cached Data', $this->plugin_slug); ?></h4>
			
			<?php 
			$value = $sa->spark_variable( $core, $variable, $cache );

			if ( is_array($value) ) {
				$cachevalue = $value['result'];
			} else {
				$cachevalue = __( 'Cache expired.', $this->plugin_slug );
			}
			?>

			<ul>
				<li><?php _e('Value:', $this->plugin_slug); ?> <?php echo $cachevalue; ?></li>
			</ul>

			<?php
		}
	}

}
