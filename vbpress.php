<?php

/*
 * Plugin Name: vBPress
 * Plugin URI: http://www.vbpress.com
 * Description: vBPress seamlessly integrates WordPress with vBulletin
 * Author: Aaron Forgue, PJ Hile
 * Version: 0.1
 * Author URI: http://www.vbpress.com
 * Text Domain: vbpress
 * Domain Path: /languages/
 */

class Vbpress {

	var $options = array();
	
	/**
	 * Singleton
	 */
	function &init() {
		static $instance = false;

		if ( !$instance ) {
			load_plugin_textdomain( 'vbpress', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
			$instance = new Vbpress;
		}

		return $instance;
	}

	/**
	 * Constructor. Initializes WordPress hooks
	 */
	function Vbpress() {
	
		// Create settings menu in admin control panel
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	
		$options = get_option( 'vbpress_options' );
		
		// Create user hook
		if ( !empty( $options['sync_wp_users_into_vb'] ) ) {
			add_action( 'user_register', array( 'Vbpress_Vb', 'create_user' ) );
		}

		// Auto-login hook
		if ( !empty( $options['auto_login_vb_user'] ) ) {
			add_action( 'wp_login', array( 'Vbpress_Vb', 'login_user' ) );
		}
		
	}

	/**
	 * Activation callback
	 */
	function plugin_activation() {
	
	}

	/**
	 * Deactivation callback
	 */
	function plugin_deactivation() {
		
	}

	/**
	 * Create the vBPress admin menu option
	 */
	function admin_menu() {
		$vbpress_settings = Vbpress_Settings::init();
		$hook = add_menu_page( 'vBPress', 'vBPress', 'manage_options', 'vbpress_settings', array( &$vbpress_settings, 'settings' ), '' );
	}
}

/**
 * This is our "API" for vBulletin. All interaction with the vBulletin core
 * should be handled through this class.
 */
class Vbpress_Vb {
	
	/**
	 * Singleton
	 */
	function &init() {
		static $instance = false;

		if ( !$instance ) {
			load_plugin_textdomain( 'vbpress', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
			$instance = new Vbpress_Vb;
		}

		return $instance;
	}

	/**
	 * Constructor. Initializes WordPress hooks
	 */
	function Vbpress_Vb() {
	
		$this->options = get_option( 'vbpress_options' );
		if ( isset( $GLOBALS['vbulletin'] ) ) {
			$this->vbulletin = $GLOBALS['vbulletin'];
		}
		
	}
	
	/**
	 * If available, returns an array of user info for the currently
	 * logged-in user
	 */
	function create_user( $wp_user_id ) {
		
		$wp_user_data = get_userdata( $wp_user_id );

		$vb_user_data =& datamanager_init( 'User', $GLOBALS['vbulletin'], ERRTYPE_ARRAY );
		$vb_user_data->set( 'email', $wp_user_data->user_email );
		$vb_user_data->set( 'username', $wp_user_data->user_login );
		$vb_user_data->set( 'password', 'zpgh.566' );

		$vb_user_data->pre_save();

		if ( empty( $vb_user_data->errors ) ) {
			$vb_user_id = $vb_user_data->save();
			update_user_meta( $wp_user_id, 'vbulletin_user_id', $vb_user_id );
		} else {
			// TODO: Error, vBulletin threw errors when attempting to create user
		}
		
	}
	
	/**
	 * If available, returns an array of user info for the currently
	 * logged-in user
	 */
	function get_current_user_info() {
		if ( !empty($this->vbulletin->userinfo['userid']) ) {
			return $this->vbulletin->userinfo;
		} else {
			return false;
		}
	}

	/**
	 * If available, returns an array of user info for the specified
	 * user ID
	 */
	function get_user_info( $user_id ) {
		return fetch_userinfo($user_id);
	}

	/**
	 * Logs in the vBulletin user associated with the WordPress user
	 */
	function login_user( $user_login ) {
	
		$options = get_option( 'vbpress_options' );
	
		if ( empty( $options['vbulletin_path'] ) || !file_exists( $options['vbulletin_path'] . '/includes/functions_login.php' ) ) {
			return;
		}
		
		$wp_user_data = get_userdatabylogin( $user_login );
		$vb_user_id = get_user_meta( $wp_user_data->ID, 'vbulletin_user_id', true );
		
		if ( empty( $vb_user_id ) ) {
			return;
		}
		
		include_once( $options['vbulletin_path'] . '/includes/functions_login.php' );
		$GLOBALS['vbulletin']->userinfo = verify_id( 'user', $vb_user_id, true, true, 0 );
		process_new_login( null, 0, null );
		$GLOBALS['vbulletin']->session->save();
		
	}
	
}

/**
 * Manages the vBPress settings
 */
class Vbpress_Settings {

	/**
	 * Singleton
	 */
	function &init() {
		static $instance = false;

		if ( !$instance ) {
			$instance = new Vbpress_Settings;
		}

		return $instance;
	}
	
	/**
	 * Constructor. Registers setting groups and subsequent settings
	 */
	function Vbpress_Settings() {
		
		register_setting( 'vbpress_options', 'vbpress_options', array( $this, 'options_validate' ) );
		
		// General settings
		add_settings_section( 'vbpress_options_general', __( 'General Settings', 'vbpress' ), array( $this, 'output_section_general' ), 'vbpress' );
		add_settings_field( 'vbpress_enabled', __( 'Enable vBPress', 'vbpress' ), array( $this, 'output_option_vbpress_enabled' ), 'vbpress', 'vbpress_options_general' );
		add_settings_field( 'vbulletin_path', __( 'vBulletin Path', 'vbpress' ), array( $this, 'output_option_vbulletin_path' ), 'vbpress', 'vbpress_options_general' );
		add_settings_field( 'sync_wp_users_into_vb', __( 'Sync WordPress Users Into vBulletin', 'vbpress' ), array( $this, 'output_option_sync_wp_users_into_vb' ), 'vbpress', 'vbpress_options_general' );
		add_settings_field( 'auto_login_vb_user', __( 'Automatically log in to vBulletin', 'vbpress' ), array( $this, 'output_option_auto_login_vb_user' ), 'vbpress', 'vbpress_options_general' );
	}

	/**
	 * vBPress settings page
	 */
	function settings() {
	
		$is_logged_in = false;
		$vb_user_info = array();
	
		$options = get_option( 'vbpress_options' );
		if ( !empty( $options['vbpress_enabled'] ) ) {
			$vbpress_vb = Vbpress_Vb::init();
			$current_user_info = $vbpress_vb->get_current_user_info();
		}
		
		// TODO: Could we create 'View' class that is used for loading action views?
		require( dirname( __FILE__ ) . '/core/views/settings.php' );
	}

	/**
	 * Output section HTML
	 */
	function output_section_general() {
		require( dirname( __FILE__ ) . '/core/views/option_section_general.php' );
	}

	/**
	 * Output option field HTML
	 */
	function output_option_vbpress_enabled() {
		$options = get_option( 'vbpress_options' );
		require( dirname( __FILE__ ) . '/core/views/option_field_vbpress_enabled.php' );
	}

	/**
	 * Output option field HTML
	 */
	function output_option_vbulletin_path() {
		$options = get_option( 'vbpress_options' );
		require( dirname( __FILE__ ) . '/core/views/option_field_vbulletin_path.php' );
	}

	/**
	 * Output option field HTML
	 */
	function output_option_sync_wp_users_into_vb() {
		$options = get_option( 'vbpress_options' );
		require( dirname( __FILE__ ) . '/core/views/option_field_sync_wp_users_into_vb.php' );
	}

	/**
	 * Output option field HTML
	 */
	function output_option_auto_login_vb_user() {
		$options = get_option( 'vbpress_options' );
		require( dirname( __FILE__ ) . '/core/views/option_field_auto_login_vb_user.php' );
	}

	/**
	 * Validate submitted options data
	 */
	function options_validate( $data ) {
		return $data;
	}
}

register_activation_hook( __FILE__, array( 'Vbpress', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'Vbpress', 'plugin_deactivation' ) );

add_action( 'init', array( 'Vbpress', 'init' ) );
add_action( 'admin_init', array( 'Vbpress_Settings', 'init' ) );


/*
 * Is vBPress enabled? If so, we need to load the vBulletin core
 */
$vBPressOptions = get_option( 'vbpress_options' );
if ( !empty( $vBPressOptions['vbpress_enabled'] ) && !empty( $vBPressOptions['vbulletin_path'] ) ) {

	if (file_exists($vBPressOptions['vbulletin_path'].'/global.php')) {

		// vBulletin modifies request-related superglobals. Make a back up of them so
		// that we can reset them after vBulletin has been loaded.
		$request_superglobals = array(
			'_GET' => $_GET,
			'_POST' => $_POST,
			'_REQUEST' => $_REQUEST
		);

		// Load the vBulletin core
		$dir = getcwd();
		chdir( $vBPressOptions['vbulletin_path'] );
		require_once( './global.php' );
		chdir( $dir );
		
		// Reset request-related superglobals
		$_GET = $request_superglobals['_GET'];
		$_POST = $request_superglobals['_POST'];
		$_REQUEST = $request_superglobals['_REQUEST'];
		
		// Load the Vbpress_Vb class
		add_action( 'init', array( 'Vbpress_Vb', 'init' ) );
		
	} else {
		// TODO: Error, could not find the vBulletin global.php at this path $vBPressOptions['vbulletin_path']
	}
}