<?php
    //must check that the user has the required capability 
    if (!current_user_can('manage_options'))
    {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }
?>
	<div class="wrap wp-cmm_settings" id="CMM_activemaps_settings">
		
    	<div id="icon-CMM" class="icon32"></div>
    	<h2><?php echo __('Interactive Maps', self::LANG)." ".__('Settings'); ?></h2>
  
  		<form method="post" action="options.php">
<?php settings_fields('CMM_active_plugin_options'); ?>

  			<table class="form-table">

					<input type="hidden" id="usage_env" name="CMM_active_opts[usage_env]" value="active" />

<?php self::render_form_fields( $this->defined_active_opts, self::PREFIX, 'options_', 'active', 'CMM_active_opts' ); ?>

  			</table>

  			<p class="submit">
  					<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
  			</p>

<?php if ( $this->active_opts['show_example']  ) { ?>
				<div class="admin-example-map" title="<?php _e('Hover to view full size', self::LANG )?>">
<?php	echo do_shortcode( '[cmm_active_single title="'.__('Default marker with your approximated position.', self::LANG ).'" align="none" copyright="#footer-upgrade"]' ) ?>
				</div>
<?php } ?>

  		</form>
	</div>