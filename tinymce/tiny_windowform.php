<div id="cmm_tinymce_addshortcode_form" style="display:none;">
		<form class="wp-cmm_settings">
		
		    <div id="cmm_tabs_navi">
						<ul id="sidemenu">
<?php if ( $this->general_opts['pp_static'] ) : ?>
						  <li><a href="#wp-cmm_tiny_static" title="<?php _e( 'Add static map', self::LANG ); ?>"><?php _e( 'Static map', self::LANG ); ?></a></li>
<?php endif; ?>
<?php if ( $this->general_opts['pp_active'] ) : ?>
						  <li><a href="#wp-cmm_tiny_active_single" title="<?php _e( 'Add interactive map for one post', self::LANG ); ?>"><?php _e( 'Interactive single map', self::LANG ); ?></a></li>
		<!-- -->				  <li><a href="#wp-cmm_tiny_active_group" title="<?php _e( 'Add interactive map for multiple posts', self::LANG ); ?>"><?php _e( 'Interactive group map', self::LANG ); ?></a></li>
<?php endif; ?>
						</ul>
		    </div>
		    
		    
<?php if ( $this->general_opts['pp_static'] ) : ?>
				<div id="wp-cmm_tiny_static" class="cmm-tiny-panel">
				    <h3><?php _e( 'Add static map', self::LANG ); ?></h3>
				    
  					<table class="form-table">
<?php self::render_form_fields( $this->defined_static_opts, self::PREFIX, 'tiny_', 'static', 'tiny_static' );  ?>
						</table>
						
						<div class="submitbox">
							<div class="cmm-cancel">
						  <a class="submitdelete deletion" href="#"><?php _e( 'Cancel' ); ?></a>
							</div>
							<div class="cmm-submit">
								<input type="button" id="static" class="button-primary" value="<?php _e( 'Insert map', self::LANG ); ?>" name="submit" />	</div>
						</div>
			   </div>
<?php endif; ?>

<?php if ( $this->general_opts['pp_active'] ) : ?>
			   <div id="wp-cmm_tiny_active_single" class="cmm-tiny-panel">
		    		<h3><?php _e( 'Add interactive map for one post', self::LANG ); ?></h3>
		    		
  					<table class="form-table">
<?php self::render_form_fields( $this->defined_active_opts, self::PREFIX, 'tiny_', 'active', 'tiny_active' );  ?>
						</table>
						
						<div class="submitbox">
							<div class="cmm-cancel">
						  <a class="submitdelete deletion" href="#"><?php _e( 'Cancel' ); ?></a>
							</div>
							<div class="cmm-submit">
								<input type="button" id="active_single" class="button-primary" value="<?php _e( 'Insert map', self::LANG ); ?>" name="submit" />	</div>
						</div>
		   </div>



		   <div id="wp-cmm_tiny_active_group" class="cmm-tiny-panel">
		    		<h3><?php _e( 'Add interactive map for multiple posts', self::LANG ); ?></h3>

						<p class="beta-info"><strong>Beta:</strong> <?php _e( 'This part is still in development. I recomend to not use this in production use.', self::LANG ); ?></p>
   
				    <p class="howto"><?php _e( 'Define your filter properties by selecting some of the given categories, tags , users, etc.', self::LANG ); ?></p>

  					<table class="form-table">
<?php self::render_form_fields( $this->defined_group_opts, self::PREFIX, 'tiny_', 'group', 'tiny_group' );  ?>
						</table>
						
  					    <p class="howto"><?php _e( 'By default the active group-maps are using the same properties, as the active single-maps.', self::LANG ); ?></p>
  					    <p class="howto toggle-arrow"><?php _e( 'Click here to change them for this map.', self::LANG ); ?></p>

								<div class="toggle-options" style="display:none">
  					<table class="form-table">
<?php self::render_form_fields( $this->defined_active_opts, self::PREFIX, 'tiny_', 'group', 'tiny_group' );  ?>
						</table>
								</div>



						<div class="submitbox">
							<div class="cmm-cancel">
						  <a class="submitdelete deletion" href="#"><?php _e( 'Cancel' ); ?></a>
							</div>
							<div class="cmm-submit">
								<input type="button" id="active_group" class="button-primary" value="<?php _e( 'Insert map', self::LANG ); ?>" name="submit" />	</div>
						</div>
		   </div>

<?php endif; ?>
		</form>
</div>