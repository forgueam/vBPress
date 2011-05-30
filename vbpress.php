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
	
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		
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
	}

	/**
	 * vBPress settings page
	 */
	function settings() {
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
	 * Validate submitted options data
	 */
	function options_validate( $data ) {
		return $data;
	}
}

class Vbpress_Error extends WP_Error {}

register_activation_hook( __FILE__, array( 'Vbpress', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'Vbpress', 'plugin_deactivation' ) );

add_action( 'init', array( 'Vbpress', 'init' ) );
add_action( 'admin_init', array( 'Vbpress_Settings', 'init' ) );
