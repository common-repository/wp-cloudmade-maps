<?php

    //must check that the user has the required capability 
    if (!current_user_can('manage_options'))
    {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }
?>
	<div class="wrap wp-cmm_settings" id="CMM_staticmaps_settings">
		
    	<div id="icon-CMM" class="icon32"></div>
    	<h2><?php echo __('Static Maps', self::LANG)." ".__('Settings'); ?></h2>
  
  		<form method="post" action="options.php">
<?php settings_fields('CMM_static_plugin_options'); ?>

  			<table class="form-table">
					<input type="hidden" id="usage_env" name="CMM_static_opts[usage_env]" value="static" />

<?php self::render_form_fields( $this->defined_static_opts, self::PREFIX, 'options_', 'static', 'CMM_static_opts' ); ?>

  			</table>
  			
  			<p class="submit">
  					<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
  			</p>
  			

<?php if ( $this->static_opts['show_example']  ) { ?>
				<div class="admin-example-map" title="<?php _e('Hover to view full size', self::LANG )?>">
<?php	echo do_shortcode( '[cmm_static align="none" caption=""]' ) ?>
				</div>
<?php } ?>
  		</form>
	</div>