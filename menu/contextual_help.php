<?php
      	global $CMM_general_page, $CMM_static_page, $CMM_active_page;

        if ($screen_id == $CMM_general_page) {
      		$contextual_help ='';

        }


        if ($screen_id == $CMM_static_page) {
      		$contextual_help ='';
      		
      		$contextual_help .= "<h4>".__('Usage of the Static Maps Shortcode', CloudMadeMap::LANG )."</h4>";
      		$contextual_help .= "<p>".__('For a simple static map inside a post or page use', CloudMadeMap::LANG )." <code>[cmm_static]</code>. " . __('The generated map will use the default values from this page.', CloudMadeMap::LANG )."</p>";
      		$contextual_help .= "<p>".__('To show a personalized static map use one or more of the following attributes:', CloudMadeMap::LANG )."</p>";
          $contextual_help .= "<dl>";

					$contextual_help .= "<dt><code>width=''</code></dt><dd>". __('Width', CloudMadeMap::LANG );
      		$contextual_help .= "<small class='howto'>".__('Awaiting integers (in px) only, do not add any units.', CloudMadeMap::LANG )."</small></dd>";

					$contextual_help .= "<dt><code>height=''</code></dt><dd>". __('Height', CloudMadeMap::LANG );
      		$contextual_help .= "<small class='howto'>".__('Awaiting integers (in px) only, do not add any units.', CloudMadeMap::LANG )."</small></dd>";

					$contextual_help .= "<dt><code>zoom=''</code></dt><dd>". __('Zoom level', CloudMadeMap::LANG );
      		$contextual_help .= "<small class='howto'>".__('use integers between 0 and 18', CloudMadeMap::LANG )."</small></dd>";
      		
					$contextual_help .= "<dt><code>background='true'</code></dt><dd>". __('to show the map as a background-image', CloudMadeMap::LANG ). "</dd>";

					$contextual_help .= "<dt><code>align=''</code></dt><dd>". __('Alignment');
      		$contextual_help .= "<small class='howto'>".sprintf( __('choose between %1$s for left alignment, %2$s for right alignment, %3$s for centered positioning and %4$s for no explicit alignment.', CloudMadeMap::LANG ), '<code>left</code>', '<code>right</code>', '<code>center</code>', '<code>none</code>' )."</small></dd>";

					$contextual_help .= "<dt><code>caption='true'</code></dt><dd>". __('Show adress of the current point on the map as caption of the map', CloudMadeMap::LANG ). "</dd>";

					$contextual_help .= "</dl>";
      		$contextual_help .= "<p>".__('Try adding two shortcodes, one for a small preview and one for the background and give them different zoomlevels ;)', CloudMadeMap::LANG )."</p>";
        }


        if ($screen_id == $CMM_active_page) {
      		$contextual_help ='';
      		$contextual_help .= "<h4>".__('Usage and control of the Interactive Maps', CloudMadeMap::LANG )."</h4>";
      		$contextual_help .= "<p>".__('For a simple active map inside a post or page use', CloudMadeMap::LANG )." <code>[cmm_active_single]</code>. " . __('The generated map will use the default values from this page.', CloudMadeMap::LANG )."</p>";
      		$contextual_help .= "<p>".__('To show a personalized active map use one or more of the following attributes:', CloudMadeMap::LANG )."</p>";
          $contextual_help .= "<dl>";

          $contextual_help .= "<dt><code>width=''</code></dt><dd>". __('Width', CloudMadeMap::LANG );
      		$contextual_help .= "<small class='howto'>".__('Awaiting integers (in px) only, do not add any units.', CloudMadeMap::LANG )."</small></dd>";

          $contextual_help .= "<dt><code>height=''</code></dt><dd>". __('Height', CloudMadeMap::LANG );
      		$contextual_help .= "<small class='howto'>".__('Awaiting integers (in px) only, do not add any units.', CloudMadeMap::LANG )."</small></dd>";

          $contextual_help .= "<dt><code>zoom=''</code></dt><dd>". __('Zoom level', CloudMadeMap::LANG );
      		$contextual_help .= "<small class='howto'>".__('use integers between 0 and 18', CloudMadeMap::LANG )."</small></dd>";

          $contextual_help .= "<dt><code>minzoom=''</code></dt><dd>". __('Minimum zoom level', CloudMadeMap::LANG );
      		$contextual_help .= "<small class='howto'>".__('use integers between 0 and 18', CloudMadeMap::LANG )."</small></dd>";

					$contextual_help .= "<dt><code>maxzoom=''</code></dt><dd>". __('Maximum zoom level', CloudMadeMap::LANG );
      		$contextual_help .= "<small class='howto'>".__('use integers between 0 and 18', CloudMadeMap::LANG )."</small></dd>";
      		
          $contextual_help .= "<dt><code>controls=''</code></dt><dd>". __('Map controls', CloudMadeMap::LANG );
      		$contextual_help .= "<small class='howto'>". __('<strong><code>L</code></strong> for large controll, <strong><code>S</code></strong> for a small one and leave or <strong><code>N</code></strong> for no control.', CloudMadeMap::LANG ). "</small></dd>";

          $contextual_help .= "<dt><code>scale=''</code></dt><dd>". __('Show the displays scale.', CloudMadeMap::LANG );
      		$contextual_help .= "<small class='howto'>". __('Allowed attributes:', CloudMadeMap::LANG )." ". __('are only <strong><code>1</code></strong> to show or <strong><code>0</code></strong> to hide', CloudMadeMap::LANG )."</small></dd>";

          $contextual_help .= "<dt><code>overview='true'</code></dt><dd>". __('Show a small overview map', CloudMadeMap::LANG ). "</dd>";

          $contextual_help .= "<dt><code>labels='true'</code></dt><dd>". __('Add labels to marker', CloudMadeMap::LANG ). "</dd>";

          $contextual_help .= "<dt><code>title=''</code></dt><dd>". __('Description for marker &amp; label instead of post- or page-title.', CloudMadeMap::LANG ). "</dd>";

					$contextual_help .= "<dt><code>align=''</code></dt><dd>". __('Alignment');
      		$contextual_help .= "<small class='howto'>".sprintf( __('choose between %1$s for left alignment, %2$s for right alignment, %3$s for centered positioning and %4$s for no explicit alignment.', CloudMadeMap::LANG ), '<code>left</code>', '<code>right</code>', '<code>center</code>', '<code>none</code>' )."</small></dd>";

        	$contextual_help .= "<dt><code>copyright=''</code></dt><dd>". __('copyright mention', CloudMadeMap::LANG );
      		$contextual_help .= "<small class='howto'>".__('HTML Element, #ID- or .class-Name, where to move the copyright mention from Cloudmade, e.g. <code>#site-generator</code>', CloudMadeMap::LANG)."</small></dd>";
          $contextual_help .= "</dl>";
        }



        if ( $screen_id == $CMM_general_page || $screen_id == $CMM_static_page || $screen_id == $CMM_active_page ) {
      		$contextual_help  = '<div class="wp-cmm_contextual-help">'.$contextual_help;
      		$contextual_help .= '<p><a href="http://wordpress.org/tags/wp-cloudmade-maps?forum_id=10">'.CloudMadeMap::NAME.' '.__( 'Support Forum', CloudMadeMap::LANG ) .'</a></p>';
      		$contextual_help .= '</div>';
        }

?>