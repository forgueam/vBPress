<div class="wrap">
	<h2><?php _e( 'vBPress Settings', 'vbpress' ); ?></h2>
	<form method="post" action="options.php">
		<?php settings_fields( 'vbpress_options' ); ?>
		<?php do_settings_sections( 'vbpress' ); ?>
		<input name="Submit" type="submit" value="<?php _e( 'Save Settings', 'vbpress' ); ?>" />
	</form>
</div>