<?php
 

class CMM_widget_last_geoposts extends WP_Widget {

#        const LANG = CloudMadeMap::LANG;
		/** constructor */
    public function __construct() {
        $widget_ops = array('classname' => 'wp-cmm_last_geo_posts', 'description' => __('List your recent located posts and show static maps of their positions.', CloudMadeMap::LANG ) );
        $control_ops = array('width' => 300, 'height' => 300);
        $control_ops = array();
        parent::WP_Widget(false, $name = __('Located posts', CloudMadeMap::LANG) , $widget_ops, $control_ops );
#new firePHPdebug( 'widget construct' );

#				include_once dirname( __FILE__ ).'/../class.CloudMadeMap.settings.php';

    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {		

				global $post;

				extract( $args );

        $title = apply_filters('widget_title', $instance['title']);
        
        $shortcode = '[cmm_static align="none"';
        
        if ( $instance['width'] > 0 )
            $shortcode .= ' width="'.$instance['width'].'"';

        if ( $instance['height'] > 0 )
            $shortcode .= ' height="'.$instance['height'].'"';

        if ( $instance['zoom'] > 0 )
            $shortcode .= ' zoom="'.$instance['zoom'].'"';

        $shortcode .= ']';
        
        $recent = new WP_Query("cat=".$instance['categories']."&showposts=".$instance['count']."&meta_key=_wp_cmm_location");

        $output = $before_widget;
				 
        if ( $title )
        $output .= $before_title . $title . $after_title; 

				$output .= '<ul>';

        while($recent->have_posts())  : $recent->the_post();

          	$output .= '<li>';
          	$output .= '<a href="'.get_permalink().'" title="">';
						$output .= '<span class="post-title">'.$post->post_title.'</span>';

						$output .= do_shortcode( $shortcode );

						$output .= '</a>';
						
          	if ( $instance['show_excerpts'] )
            		$output .= '<p class="post-excerpt">'.get_the_excerpt().'</p>';

						$output .= '</li>';

        endwhile; 
        
      

        $output .= '</ul>';
        $output .= $after_widget; 

      	if ( $recent->have_posts() )
        echo $output;

        // Reset Post Data
        wp_reset_postdata();  
    
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
      	$instance = $old_instance;
      	$instance['title']         = wp_filter_nohtml_kses($new_instance['title']);
      	$instance['categories']    = wp_filter_nohtml_kses($new_instance['categories']);
      	$instance['count']         = (int)$new_instance['count'];
      	$instance['show_excerpts'] = (int)$new_instance['show_excerpts'];
      	$instance['width']         = (int)$new_instance['width'];        	
      	$instance['height']        = (int)$new_instance['height'];
      	$instance['zoom']          = (int)$new_instance['zoom'];     
         	
        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {				
        $title          = esc_attr($instance['title']);
        $cats           = esc_attr($instance['categories']);
        $count          = esc_attr($instance['count']);
        $show_excerpts  = $instance['show_excerpts'];

			  $widget_opts['zoom']['id'] = $this->get_field_id('zoom');
			  $widget_opts['zoom']['name'] = $this->get_field_name('zoom');
			  $widget_opts['zoom']['value'] = $instance['zoom'];

			  $widget_opts['width']['id'] = $this->get_field_id('width');
			  $widget_opts['width']['name'] = $this->get_field_name('width');
			  $widget_opts['width']['value'] = $instance['width'];

			  $widget_opts['height']['id'] = $this->get_field_id('height');
			  $widget_opts['height']['name'] = $this->get_field_name('height');
			  $widget_opts['height']['value'] = $instance['height'];
				      
        ?>

<?php
#$cmm = new CloudMadeMap;
#$array = $cmm->get_settings( 'general' );
#$array = CloudMadeMap->defined_general_opts;
/*
<table>
# CloudMadeMap::render_form_fields( $this->defined_static_opts, CloudMadeMap::PREFIX, 'widget_', 'static', 'widget_static' );
</table>

*/
#var_dump( $this->get_field_id('') );
#var_dump( $this->get_field_name('') );
?>

            <p>
              <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?>
              <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label>
            </p>

            <p>
              <label for="<?php echo $this->get_field_id('categories'); ?>"><?php _e('Categories:'); ?>
              <input class="widefat" id="<?php echo $this->get_field_id('categories'); ?>" name="<?php echo $this->get_field_name('categories'); ?>" type="text" value="<?php echo $cats; ?>" /></label>
         			<br />
        			<small><?php _e( 'Category IDs, separated by commas', CloudMadeMap::LANG ); ?></small>
            </p>

        		<p>
              <label for="<?php echo $this->get_field_id('count'); ?>"><?php _e('Number of posts to show:'); ?></label>
          		<input id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" type="text" value="<?php echo $count; ?>" size="3" />
            </p>

            <p>
						  <label><input name="<?php echo $this->get_field_name('show_excerpts'); ?>" type="checkbox" value="1" <?php checked( $instance['show_excerpts'], true ) ?> /> <?php _e('show excerpt(s)',CloudMadeMap::LANG); ?></label>
            </p>
       
        <?php CloudMadeMap::static_maps_opts_interface( $widget_opts );
				
    }

} // class CMM_widget_last_geoposts
?>