<?php
/**
 * sparky
 *
 * @package   sparky
 * @author    Scott Evans <git@scott.ee>
 * @license   GPL-2.0+
 * @link      http://scott.ee
 * @copyright 2014 Scott Evans
 */

/**
 * sparky class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * If you're interested in introducing administrative or dashboard
 * functionality, then refer to `class-sparky-admin.php`
 *
 * @package sparky
 * @author  Scott Evans <git@scott.ee>
 */
class sparky {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0';

	/**
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'sparky';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Register custom post types & taxonomies
		add_action( 'init', array( $this, 'cpts_taxos') );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Shortcodes in text widgets
		add_filter( 'widget_text', 'do_shortcode' );

	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
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
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
		// @TODO: Define activation functionality here
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/assets/languages/' );

	}

	/**
	 * Register custom post types and taxonomies
	 * 
	 * @since  1.0.0
	 */
	public function cpts_taxos() {

		// sparks (shortcodes)
		
		$labels = array(
			'menu_name'						=> __('Spark Core', $this->plugin_slug ),
			'name' 							=> __('Sparks', $this->plugin_slug ),
			'singular_name' 				=> __('Spark', $this->plugin_slug ),
			'all_items'						=> __('All Sparks', $this->plugin_slug ),
			'add_new' 						=> __('Add Spark', $this->plugin_slug ),
			'add_new_item' 					=> __('Add Spark', $this->plugin_slug ),
			'edit_item' 					=> __('Edit Spark', $this->plugin_slug ),
			'new_item' 						=> __('New Spark', $this->plugin_slug ),
			'search_items' 					=> __('Search Sparks', $this->plugin_slug ),
			'not_found' 					=> __('No shortcodes found',$this->plugin_slug ),
			'not_found_in_trash' 			=> __('No shortcodes found in Trash',$this->plugin_slug )
		);

		register_extended_post_type( 'spark', array(
			'label' 					=> __('Spark Core', $this->plugin_slug ),
			'labels'	 				=> $labels,
			'supports' 					=> false,
			'public' 					=> false, 
			'show_ui' 					=> true,
			'quick_edit'				=> false,
			'menu_icon' 				=> 'dashicons-admin-site',
			'menu_position'				=> 75,
			'hierarchical'				=> false,
			'cols' => array(
				'title' => false,
				'id' => array(
					'title' => __('#ID', $this->plugin_slug ),
					'function' => array( $this, 'id_column'),
					'default' => 'asc'
				),
				'core' => array(
					'title' => __('Core', $this->plugin_slug),
					'meta_key' => 'spark-core'
				),
				'variable' => array(
					'title' => __('Variable', $this->plugin_slug ),
					'meta_key' => 'spark-variable'
				),
				'cache' => array(
					'title' => __('Cache', $this->plugin_slug ),
					'meta_key' => 'spark-cache',
					'function' => array( $this, 'cache_column' )

				),
				'shortcode' => array(
					'title' => __('Shortcodes', $this->plugin_slug ),
					'sortable' => false,
					'function' => array( $this, 'shortcode_column' )
				),
				//'status' => array(
				//	'title' => __('Status', $this->plugin_slug ),
				//),
			),
			'filters' => array(
				'm' => false
			),
		) );
	}

	/**
	 * id_column
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function id_column() {
		global $post;
		echo '<a href="' . admin_url( 'post.php?post=' . $post->ID . '&action=edit' ) . '">' . $post->ID . '</a>';
	}

	/**
	 * cache_column
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function cache_column() {
		global $post;
		
		// get seconds from post meta
		$seconds = get_post_meta( $post->ID, 'spark-cache', true );

		echo $this->seconds_conversion($seconds);
	}

	/**
	 * seconds_conversion
	 *
	 * This function needs some work. Creates a human readable time from a seconds value
	 * http://csl.name/php-secs-to-human-text/
	 * 
	 * @param  int $secs
	 * @since  1.0.0
	 * @return string	 
	 * */
	function seconds_conversion($secs) {
		
		$units = array(
			"week"   => 7*24*3600,
			"day"    =>   24*3600,
			"hour"   =>      3600,
			"minute" =>        60,
			"second" =>         1,
		);

		// specifically handle zero
		if ( $secs == 0 ) return "0 seconds";

		$s = "";

		foreach ( $units as $name => $divisor ) {
			if ( $quot = intval($secs / $divisor) ) {
				$s .= "$quot ".ucfirst($name);
				$s .= (abs($quot) > 1 ? "s" : "") . ", ";
				$secs -= $quot * $divisor;
			}
		}

		return substr($s, 0, -2);
	}

	/**
	 * shortcode_column
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function shortcode_column() {
		global $post;
		echo '[sparky id="' . $post->ID . '"] [sparkystatus id="' . $post->ID . '"]';
	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}

}
