<div class="wrap">
	<h2><?php _e( 'vBPress Settings', 'vbpress' ); ?></h2>
	<form method="post" action="options.php">
		<?php settings_fields( 'vbpress_options' ); ?>
		<?php do_settings_sections( 'vbpress' ); ?>
		<input name="Submit" type="submit" value="<?php _e( 'Save Settings', 'vbpress' ); ?>" />
	</form>
	
	<?php if ( false && !empty( $options['vbpress_enabled'] ) ) { ?>
		<h2><?php _e( 'vBulletin Status', 'vbpress' ); ?></h2>
		<?php if ( !empty( $current_user_info ) ) { ?>
			You are currently logged into vBulletin as "<strong><?php echo $current_user_info['username']; ?></strong>".
			<br /><br />
			<pre><?php print_r($current_user_info); ?></pre>
		<?php } else { ?>
			You are NOT currently logged into vBulletin.
		<?php } ?>
	<?php } ?>
</div>