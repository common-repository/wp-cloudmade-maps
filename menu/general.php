<?php
    //must check that the user has the required capability 
    if (!current_user_can('manage_options'))
    {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }
?>
	<div class="wrap wp-cmm_settings" id="CMM_general_settings">
		
    	<div id="icon-CMM" class="icon32"></div>
    	<h2><?php echo self::NAME." ".__('General')." ".__('Settings'); ?></h2>
  
  		<form method="post" action="options.php">
<?php settings_fields('CMM_general_plugin_options'); ?>
<?php $options = get_option('CMM_general_opts'); ?>

  			<table class="form-table">
					<input type="hidden" id="usage_env" name="CMM_general_opts[usage_env]" value="general" />

<?php self::render_form_fields( $this->defined_general_opts, self::PREFIX, 'options_', 'general', 'CMM_general_opts' ); ?>


<!--
  				<tr>
  					<th scope="row"><?php _e('Default Address',CloudMadeMap::LANG); ?></th>
  					<td>
  						<input type="text" placeholder="<?php _e( 'Street', CloudMadeMap::LANG ) ?>" id="CMM_da_street" name="CMM_general_opts[da_street]" value="<?php if (isset($options['da_street'])) { echo $options['da_street']; }?>" />
  						<input type="text" placeholder="<?php _e( 'Postal Code', CloudMadeMap::LANG ) ?>" id="CMM_da_zip" name="CMM_general_opts[da_zip]" value="<?php if (isset($options['da_zip'])) { echo $options['da_zip']; }?>" />
  						<input type="text" placeholder="<?php _e( 'City', CloudMadeMap::LANG ) ?>" id="CMM_da_city" name="CMM_general_opts[da_city]" value="<?php if (isset($options['da_city'])) { echo $options['da_city']; }?>" /><br />
  						<input type="text" placeholder="<?php _e( 'Region', CloudMadeMap::LANG ) ?>" id="CMM_da_region" name="CMM_general_opts[da_region]" value="<?php if (isset($options['da_region'])) { echo $options['da_region']; }?>" />
  						<input type="text" placeholder="<?php _e( 'Region Code', CloudMadeMap::LANG ) ?>" id="CMM_da_region_code" name="CMM_general_opts[da_region_code]" value="<?php if (isset($options['da_region_code'])) { echo $options['da_region_code']; }?>" />
  						<input type="text" placeholder="<?php _e( 'Country', CloudMadeMap::LANG ) ?>"id="CMM_da_country" name="CMM_general_opts[da_country]" value="<?php if (isset($options['da_country'])) { echo $options['da_country']; }?>" /><br />
  						<input type="text" placeholder="<?php _e( 'Latitude', CloudMadeMap::LANG ) ?>"id="CMM_da_lat" name="CMM_general_opts[da_lat]" value="<?php if (isset($options['da_lat'])) { echo $options['da_lat']; }?>" />
  						<input type="text" placeholder="<?php _e( 'Longitude', CloudMadeMap::LANG ) ?>"id="CMM_da_lng" name="CMM_general_opts[da_lng]" value="<?php if (isset($options['da_lng'])) { echo $options['da_lng']; }?>" /><br />
  					</td>
  				</tr>
    -->

  			</table>

				<p class="submit">
  				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
  			</p>
  			
  		</form>

	</div>