<?php
/*
    Plugin Name:  WP Cloudmade Maps
    Plugin URI:   https://github.com/carstingaxion/wp-cloudmade-maps
    Description:  Add static and interactive cloudmade maps to your website, using a widget, different shortcodes and a tinymce GUI for user-friendly map-embedding.
    Author:       Carsten Bach
    Author URI:   http://carsten-bach.de
    Version:      0.0.8
    License:      GPL

    Copyright 2011  Carsten Bach  (email: mail@carsten-bach.de)
    
    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License,
    version 2, as published by the Free Software Foundation.
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    
    You should have received a copy of the GNU General Public
    License along with this program; if not, write to the Free
    Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


register_activation_hook( __FILE__, array( 'CloudMadeMap', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CloudMadeMap', 'deactivate' ) );
register_uninstall_hook( __FILE__, array( 'CloudMadeMap', 'uninstall' ) );

if ( !class_exists( 'CloudMadeMap' ) ) {
  class CloudMadeMap {

    const LANG = __CLASS__;
		const VERS = '0.0.8';
		const NAME = 'WP Cloudmade Maps';
		const WPNEED = '3.1';
		const PHPNEED = '5.2.4';
    const PREFIX = 'wp-cmm_';
    
		// error-handling $vars
		private $plugin_deps = array();
		private $error_msg;
		
		// differentiate between map-types
		private $map_case;

		// add wp css align-class
		private $map_align;

		//
		private $map_caption = false;
		
		// counter for several shortcodes in one post
		private $count = 0;
		
		//
    private $localized_vars = array();
    private $cmm_base = array();
    
		public function __construct ( ) {

				// get all options
        $this->general_opts = get_option( 'CMM_general_opts');
        $this->static_opts = get_option( 'CMM_static_opts');
        $this->active_opts = get_option( 'CMM_active_opts');

		    // upgrade DB if neccessary
				$this->upgrade( );

				// Plugin folder
				if ( !defined( 'CMM_PLUGIN_DIR' ) )
				define( 'CMM_PLUGIN_DIR', dirname( plugin_basename( __FILE__ ) ) );

				// plugin file with leading slash
				if ( !defined( 'CMM_PLUGIN_FILE' ) )
				define( 'CMM_PLUGIN_FILE', substr( plugin_basename( __FILE__ ), stripos( plugin_basename( __FILE__ ), '/' ) ) );

        // load translation management
        load_plugin_textdomain( self::LANG, false, CMM_PLUGIN_DIR . '/languages/' );

				// show errors the WP way
        add_action( 'admin_notices', array($this, 'admin_notices') );

        // Init plugin options to white list our options
        add_action('admin_init', array( &$this, 'register_settings'));

        // Add shortcode-explanation to the WP Help-Tab
        add_filter( 'contextual_help', array( &$this, 'contextual_help' ), 10, 3);

        // Display a Settings link on the main Plugins page
        add_filter( 'plugin_action_links', array( &$this, 'plugin_action_links'), 10, 2 );

				// Add additional links to plugin-description-section
				add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );

				// load settings very late, to be sure that theme-stuff is loaded
				add_action('init', array( &$this, 'load_settings'), 999 );

				// check for dependecies and remove shortcodes from content, when errors appear
				if ( ! $this->check_dependecies( ) ){
						
						// load plugin
		        add_action('init', array( &$this, 'init') );

		        // check for existence of "static-maps"- and "Widget"-Pluginpart
						if (  $this->general_opts['pp_static'] && $this->general_opts['pp_widget'] ) {

										// get widget code
										include_once 'widgets/last-posts-widget.php';

										// register "Last geotagged posts" widget
										add_action('widgets_init', create_function('', 'register_widget("CMM_widget_last_geoposts");'));
						}
				} else {
						// Clean content from not working shortcodes
						add_shortcode('cmm_static', create_function('$a', "return null;") );
						add_shortcode('cmm_active_single',create_function('$a', "return null;")  );
						add_shortcode('cmm_active_group', create_function('$a', "return null;")  );
				}

        // add menus to wp-admin
        add_action('admin_menu', array( &$this, 'admin_menu') );

				// prepare wp $post-object for use in example-maps
				global $post;
				if ( is_admin() ) { $post->ID =  '0'; $post->post_title = 'Example Map'; }
		}

		
		public function init ( ) {

		    global $pagenow, $wp_version;

				// check for existence of "static-maps"-Pluginpart
				if ( $this->general_opts['pp_static'] ) {

						// Register Shortcode for static maps
						add_shortcode('cmm_static', array( &$this, 'create_static_map' ) );
				} else {

						// Clean content from not working shortcodes
						add_shortcode('cmm_static', create_function('$a', "return null;") );
				}

				// check for existence of "interactive-maps"-Pluginpart
				if ( $this->general_opts['pp_active'] ) {

						// Register Shortcodes for interactive maps
						add_shortcode('cmm_active_single', array( &$this, 'create_active_single_map' ) );
						add_shortcode('cmm_active_group', array( &$this, 'create_active_group_map' ) );
      } else {

						// Clean content from not working shortcodes
						add_shortcode('cmm_active_single',create_function('$a', "return null;")  );
						add_shortcode('cmm_active_group', create_function('$a', "return null;")  );
				}

				if ( is_admin() ) {
						// get $post-object from ID inside the URL, cause $post is not present on init-hook
						$post = get_post( $_REQUEST['post'] );

						// check for existence of static- or active-map-pluginparts
				    if ( $this->general_opts['pp_active'] ||  $this->general_opts['pp_static'] ) {

								// check if is a case of allowed editscreen
								if (
									// existing post: check if current post-type is choosen, through options panel
									($pagenow == 'post.php' || $pagenow == 'post-new.php' )
											&& (// check if current post-type is not set, in case of new blog post
													( !isset ( $_REQUEST['post_type'] ) && in_array( 'post', $this->general_opts['posttypes'] ) )
													||
													// or is choosen, through options panel
                          ( isset ( $_REQUEST['post_type'] ) && in_array( $_REQUEST['post_type'], $this->general_opts['posttypes'] ) )
													)
									)
								 {
/*
								// check if is a case of allowed editscreen
								if (
									// existing post: check if current post-type is choosen, through options panel
									($pagenow == 'post.php' && in_array( $post->post_type, $this->general_opts['posttypes'] ) )
									||
									// new post :
									($pagenow == 'post-new.php'
											&& (// check if current post-type is not set, in case of new blog post
													( !isset ( $_REQUEST['post_type'] ) && in_array( 'post', $this->general_opts['posttypes'] ) )
													||
													// or is choosen, through options panel
                          ( isset ( $_REQUEST['post_type'] ) && in_array( $_REQUEST['post_type'], $this->general_opts['posttypes'] ) )
													)
									)
								) {
*/

												// Add tinyMCE button and modal dialog
												add_action('admin_init', array( &$this, 'tinymce_init_button'));

												// add tinyMCE form to html of post edit screen
												if ( version_compare ( $wp_version, 3.1, ">" ) ) {
														add_action( 'after_wp_tiny_mce', array( &$this, 'tinymce_form' ) );
												} else {
														// use 3.1. fallback
														add_action( 'tiny_mce_preload_dialogs', array( &$this, 'tinymce_form' ) );
												}
												
												// Add meta-box with map to edit post-/page-screen
												add_action( 'add_meta_boxes', array( &$this, 'meta_box' ) );

												// Register Shortcodes for interactive maps to use the meta-box
												add_shortcode('cmm_active_single', array( &$this, 'create_active_single_map' ) );

												// Add scripts to load the map for the meta-box
             						wp_enqueue_script( 'jquery' );
                        wp_enqueue_script( 'jquery-ui-core' );
             						wp_enqueue_script( 'jquery-ui-widget' );
             						wp_enqueue_script( 'jquery-ui-tabs' );

										    // wp tag auto suggest
										    wp_enqueue_script( 'suggest' );
										    
						            add_action( 'admin_print_styles', array( &$this, 'admin_css') );
												add_action( 'admin_print_scripts', array( &$this, 'active_map_js'));
												add_action( 'admin_print_scripts', array( &$this, 'yql_geo_library'));
												add_action( 'admin_print_scripts', array( &$this, 'admin_scripts'));
												
												// add styles for map-placeholder-image to the visual editor
												add_filter( 'mce_css', array( &$this, 'tinymce_css' ) );
							  }
					  }

						// When a post is saved, save our custom data from the meta_box
						add_action( 'save_post', array( &$this, 'meta_box_save_postdata' ) );

        } else {

				    // Add shortcode support for widgets
				    add_filter('widget_text', 'do_shortcode');
    
						// load scripts and styles conditionally to used shortcodes
		        add_filter('the_posts', array( &$this, 'load_js_and_css'), 1);

		        // Add geo meta-tags to html-head
						add_action( 'wp_head', array( &$this, 'add_geo_meta_tags' ) );

		        // filter the_content to add microformat geo tags
		        add_filter( 'the_content' , array( &$this, 'add_microformat_geo_tags_to_posts') );
				}
		}


		/**
		 *  get predefined options as arrays
		 *  from class.CloudMadeMap.settings.php
		 *
		 *  @since  0.0.4
		 */
		public function load_settings ( ) {
				include_once 'class.CloudMadeMap.settings.php';
		}


		/**
		 *  Prepare JS and CSS for frontend
		 *
		 *  @since  0.0.3
		 */
		public function load_js_and_css ( $posts ) {

				// if geo-html is rendered, use css to hide it
		    if ( $this->general_opts['add_microformat_geo_tag'] ) {
            $this->frontend_map_css();
				}


				// check for existence of "static-maps"-Pluginpart
		    if ( $this->general_opts['pp_static'] ) {

		        // Add static map scripts
						if ( $this->conditionally_load_css_and_js( array( 'cmm_static', 'background' ), $posts ) ) {
								$this->static_map_js();
            		$this->frontend_map_css();
		        }
		    }

				// check for existence of "interactive-maps"-Pluginpart
				if ($this->general_opts['pp_active'] ) {

		        // Add Scripts
						if ( $this->conditionally_load_css_and_js( array( 'cmm_active' ), $posts ) ) {
            		$this->active_map_js();
            		$this->frontend_map_css();
		        }
		    }

				return $posts;
		}


		/**
		 *  Get latitude and longitude from current $post->object
		 *
		 *  @since  0.0.2
		 */
		static function get_coordinates ( $post ) {
		
				if ( is_object( $post ) ) {
						$coordinates  =  get_post_meta($post->ID, '_wp_cmm_location', true );
				} else { return false; }

				if ( !is_array($coordinates) && ( !isset($coordinates['lat']) || !isset($coordinates['lng']) ) ) {
				    $opts 							= get_option( 'CMM_general_opts');
						$coordinates       	= array( 'lat'=>$opts['da_lat'], 'lng'=>$opts['da_lng'] );
				}
				return $coordinates;
		}

		static function get_location ( $post ) {

				if ( is_object( $post ) ) {
						$location  =  get_post_meta($post->ID, '_wp_cmm_location', true );
				} else { return false; }

				if ( !is_array($location)  )
				    return false;

				$loc = '';
				
				if ( isset( $location['street'] ) )
				    $loc  .= $location['street'].', ';

				if ( isset( $location['zip'] ) )
				    $loc  .= $location['zip'].' ';

				if ( isset( $location['city'] ) )
				    $loc  .= $location['city'].', ';

				if ( isset( $location['region'] ) )
				    $loc  .= $location['region'].' ';

				if ( isset( $location['country'] ) )
				    $loc  .= $location['country'];
				    
				return $loc;
		}

		/**
		 *  Generate output for [cmm_static] shortcode
		 *
		 *  @since  0.0.2
		 */
		public function create_static_map ( $atts ) {

				global $post;
        $coordinates = $this->get_coordinates( $post );

				// count number of shortcodes used in this post
				$this->count++;
				
        extract(shortcode_atts(array(
          'width'       => $this->static_opts['width'],
          'height'      => $this->static_opts['height'],
          'zoom'        => $this->static_opts['zoom'],
          'align'       => $this->static_opts['align'],
          'caption'     => $this->static_opts['caption'],
          'background'  => false
				), $atts ));

        if ( !$background ) {

						// set map state
						$this->map_case = 'static';
						
						// set alignment
      			$this->map_align = 'align'.$align;

      			// set caption
      			if ( $caption ) {
      					$this->map_caption = true;
				        $this->localized_vars['width'] = $width;
						}

						// is there a cloudmade styleID to use
		        $styleID  = ( $this->general_opts['style_ID'] ) ? '&amp;styleid='.$this->general_opts['style_ID'] : '';

						// generate html <img>-tag with static-map-url
          	$output  = '<img class="cmm-static-map '.$this->map_align.'" src="http://staticmaps.cloudmade.com/'
                        .$this->general_opts['api_key'].'/staticmap'
                        .'?size='.$width.'x'.$height
                        .$styleID
                        .'&amp;center='.$coordinates['lat'].','.$coordinates['lng']
                        .'&amp;zoom='.$zoom
                        .'&amp;marker=url:'.$this->static_opts['marker_icon']
                        .'|'.$coordinates['lat'].','.$coordinates['lng']
                        .'" alt="'.__('Map of', self::LANG ).' '.$this->get_location( $post ).'" />';

						// echo everything
    				return $this->render_html ( $output );

        } else {
						// set map state
						$this->map_case = 'static-bg';

						// define shortcode attributes
						$this->localized_vars['zoom']        = $zoom;
            $this->localized_vars['marker']      = $this->static_opts['marker_icon'];
            $this->localized_vars['lat']         = $coordinates['lat'];
            $this->localized_vars['lng']         = $coordinates['lng'];
            $this->localized_vars['bg_element']  = $this->static_opts['bg_element'];

						// prepare inline-scripts, then echo everything
						return $this->render_js ( $this->localized_vars );
        }

    }


    public function create_active_single_map ( $atts, $content = null ) {

				global $post, $pagenow;
				
				// get latitude & longitude for the current post
        $coordinates = $this->get_coordinates( $post );

				// set map state
				$this->map_case = 'active-single';
				
				// count number of shortcodes used in this post
				$this->count++;
				
        extract(shortcode_atts(array(
          'width'       		=> $this->active_opts['width'],
          'height'      		=> $this->active_opts['height'],
          'zoom'        		=> $this->active_opts['zoom'],
          'minzoom'     		=> $this->active_opts['minzoom'],
          'maxzoom'     		=> $this->active_opts['maxzoom'],
          'control'    			=> $this->active_opts['control'],
          'scale'       		=> $this->active_opts['scale'],
          'overview'    		=> $this->active_opts['overview'],
          'fullscreen'   		=> $this->active_opts['fullscreen'],
          'marker_labels'		=> $this->active_opts['marker_labels'],
          'title'       		=> $post->post_title,
          'copyright'   		=> $this->active_opts['copyright'],
          'align'      			=> $this->active_opts['align']
        ), $atts ) );


        $this->localized_vars['width']          	= $width;
        $this->localized_vars['height']         	= $height;
        $this->localized_vars['zoom']           	= $zoom;
        $this->localized_vars['lat']            	= $coordinates['lat'];
        $this->localized_vars['lng']            	= $coordinates['lng'];
        $this->localized_vars['minZoom']        	= $minzoom;
        $this->localized_vars['maxZoom']        	= $maxzoom;
        $this->localized_vars['zoomControl']    	= $control;
        $this->localized_vars['scale']          	= $scale;
        $this->localized_vars['minimap']        	= $overview;
        $this->localized_vars['fullscreen']       = $fullscreen;
        $this->localized_vars['copyrightElement'] = $copyright;
        $this->localized_vars['clusterradius'] 		= '';

				// set alignment
    		$this->map_align = 'align'.$align;

				//
#				$this->map_caption = true;
						
        // clean $content from wrongly left <p>-tags
				if ( $content !== null && isset( $content[0] ) && isset( $content[1] ) && isset( $content[2] ) ) {
						if ( $content[0] == '<' && $content[1] == '/' && $content[2] == 'p'  ) {
		            $content = ltrim($content,'</p>');
		            $content = trim( preg_replace( '/\s+/', ' ', $content ) );
		            $content = rtrim($content,'<p>');
		        }
				}

				// Make marker dragable on the admin-map for the meta-box
				$dragable = false;
			  if ( $pagenow == 'post-new.php' || $pagenow == 'post.php' )
			       $dragable = true;

				// build marker array for single marker
				$this->localized_vars['marker']   = "[['".
							$coordinates['lat']."','".
							$coordinates['lng']."','".
							$title."','".
							$this->active_opts['marker_icon']."','".
							$this->active_opts['marker_icon_width']."','".
							$this->active_opts['marker_icon_height']."','".
							$marker_labels."','".
							do_shortcode ( $content )."','".
							$dragable."'".
							"]]";

			// prepare inline-scripts, then echo everything
 			return $this->render_js ( $this->localized_vars );
    }


		/**
		 *  Generate shortcode output for [cmm_active_group]
		 *
		 *  @since  0.0.5
		 *  @todo   choose between excerpt, content, own template or none infowindow-content
		 */
    public function create_active_group_map ( $atts ) {

				global $post;

				// get latitude & longitude for the current post
        $coordinates = $this->get_coordinates( $post );

				// set map state
				$this->map_case = 'active-group';

				// count number of shortcodes used in this post
				$this->count++;

        extract(shortcode_atts(array(
          'width'       		=> $this->active_opts['width'],
          'height'      		=> $this->active_opts['height'],
          'zoom'        		=> $this->active_opts['zoom'],
          'minzoom'     		=> $this->active_opts['minzoom'],
          'maxzoom'     		=> $this->active_opts['maxzoom'],
          'control'    			=> $this->active_opts['control'],
          'scale'       		=> $this->active_opts['scale'],
          'overview'    		=> $this->active_opts['overview'],
          'fullscreen'   		=> $this->active_opts['fullscreen'],
          'marker_labels'   => $this->active_opts['marker_labels'],
          'infowcontent'    => 'none',
          'copyright'   		=> $this->active_opts['copyright'],
          'align'           => $this->active_opts['align'],
          'labels_as_link'  => false,
          'category'        => '',
          'tag'            	=> '',
          'author'          => '',
          'date'            => '',
          'post_type'				=> 'any',
          'exclude'         => '',
          'clusterradius'   => ''
        ), $atts ) );

				// set alignment
    		$this->map_align = 'align'.$align;
						
        $this->localized_vars['width']          	= $width;
        $this->localized_vars['height']         	= $height;
        $this->localized_vars['zoom']           	= $zoom;
        $this->localized_vars['lat']            	= $coordinates['lat'];
        $this->localized_vars['lng']            	= $coordinates['lng'];
        $this->localized_vars['minZoom']        	= $minzoom;
        $this->localized_vars['maxZoom']        	= $maxzoom;
        $this->localized_vars['zoomControl']    	= $control;
        $this->localized_vars['scale']          	= $scale;
        $this->localized_vars['minimap']        	= $overview;
        $this->localized_vars['fullscreen']       = $fullscreen;
        $this->localized_vars['copyrightElement'] = $copyright;
        $this->localized_vars['clusterradius'] 		= $clusterradius;
        
				$args = array();
				$output ='';

				// disable pagination of posts
				$args['nopaging']  = true;

				// get only geo-tagged posts
				$args['meta_key']  = '_wp_cmm_location';

				if ( $category )
				  $args['cat']  = $category;
				  
				if ( $tag )
				  $args['tag']  = $tag;
				  
				if ( $author )
				  $args['author'] = $author;
				  
				if ( $date )
				  $args['date'] = $date;

				if ( $post_type )
				  $args['post_type']  = $post_type;

				if ( $exclude )
				  $args['post__not_in']  = $exclude;
				  
				// make sure that apache wont loop to death, strip all cmm-shortcodes
				add_filter( 'the_content', array( &$this, 'strip_selected_shortcodes' ) );

				// build new WP loop with our geo posts
				$cmm_query = new WP_Query( $args );
				while ( $cmm_query->have_posts() ) : $cmm_query->the_post();

						// get latitude & longitude for the current post
				    $coordinates = $this->get_coordinates( $post );

				    // build infoWindow Content
						if ( $infowcontent && $infowcontent != 'none' ) {

								switch ( $infowcontent ) {
					        case 'excerpt' :
                    $infoWindowContent  = get_the_excerpt();
					        	break;

					        case 'content' :
					          // retrieve unfiltered content
                    $infoWindowContent = get_the_content();
                    
                    // do the same stuff as on the_content()
                    $infoWindowContent = apply_filters('the_content', $infoWindowContent);
										$infoWindowContent = str_replace(']]>', ']]&gt;', $infoWindowContent);
					        	break;

					        case 'tmpl_file' :
                    $infoWindowContent  = $this->get_infoWindow_content();
					        	break;
				        }
				        
								// clean infoWindow-content to make it work with JavaScript
		            $infoWindowContent  = trim( preg_replace( '/\s+/', ' ', htmlentities( $infoWindowContent, ENT_QUOTES, "UTF-8" ) ) );

						} else {	$infoWindowContent	=	'';	}

				    $output .= "['".
									$coordinates['lat']."','".
									$coordinates['lng']."','".
				    			get_the_title()."','".
									$this->active_opts['marker_icon']."','".
									$this->active_opts['marker_icon_width']."','".
									$this->active_opts['marker_icon_height']."','".
									$marker_labels."','".
									$infoWindowContent."','".
									$dragable;


						if( $cmm_query->current_post != $cmm_query->post_count-1 ) {
						        $output .= "'],";
						} else {
						        $output .= "']";
						}

				endwhile;
				wp_reset_postdata();

				// allow doing cmm-shortcodes from now
				remove_filter( 'the_content', array( &$this, 'strip_selected_shortcodes' ) );

				// build marker array for multiple markers
				$this->localized_vars['marker']   = "[".$output."]";

				// prepare inline-scripts, then echo everything
	 			return $this->render_js ( $this->localized_vars );
    }


		/**
		 *  retrieve content for the markers infoWindow from theme-template file
		 *
		 *  @since  0.0.6
		 *  @return string	parsed html content of the template file or error-msg, if file not exists
		 */
		function get_infoWindow_content () {
				$file = get_stylesheet_directory().'/cloudmademaps-infowindow.php';
				if ( file_exists( $file ) ) {
						ob_start();
						include $file;
						$infoWindowContent = ob_get_clean();
				} else {
				    if ( current_user_can( 'publish_posts' ) )
						$infoWindowContent = '<h2>' . __( 'Error - something went wrong', self::LANG ) . '</h2><p class="error wp-cmm_error filemissing">'.sprintf( __( 'Please copy %1$s from the %2$s plugin-folder at %3$s into your theme folder.', self::LANG ), '<strong>cloudmademaps-infowindow.php</strong>', self::NAME, CMM_PLUGIN_DIR ) . '</p>';
				}

        return $infoWindowContent;
		}


		/**
		 *  strip only cmm-shortcodes from content
		 *
		 *  Strips specific start and end shortcodes tags. Preserves contents.
		 *
		 *  @since  0.0.6
		 *  @param  string  the content to be filtered
		 *  @return string  the filtered content
		 *  @source http://pastebin.com/5CJKL5id
		 */
		function strip_selected_shortcodes($text){
		    return preg_replace('%
		        # Match an opening or closing WordPress tag.
		        \[/?                 # Tag opening "[" delimiter.
		        (?:                  # Group for Wordpress tags.
		          cmm_static|cmm_active_single|cmm_active_group     # Add other tags separated by |
		        )\b                  # End group of tag name alternative.
		        (?:                  # Non-capture group for optional attribute(s).
		          \s+                # Attributes must be separated by whitespace.
		          [\w\-.:]+          # Attribute name is required for attr=value pair.
		          (?:                # Non-capture group for optional attribute value.
		            \s*=\s*          # Name and value separated by "=" and optional ws.
		            (?:              # Non-capture group for attrib value alternatives.
		              "[^"]*"        # Double quoted string.
		            | \'[^\']*\'     # Single quoted string.
		            | [\w\-.:]+      # Non-quoted attrib value can be A-Z0-9-._:
		            )                # End of attribute value alternatives.
		          )?                 # Attribute value is optional.
		        )*                   # Allow zero or more attribute=value pairs
		        \s*                  # Whitespace is allowed before closing delimiter.
		        /?                   # Tag may be empty (with self-closing "/>" sequence.
		        \]                   # Opening tag closing "]" delimiter.
		        %six', '', $text);
		}


		/**
		 *  Publish JS Vars for every single shortcode to DOM
		 *
		 *  @since  0.0.4
		 */
		private function render_js ( $localized_script ) {
				global $post;
				
        $output  = '';
        $output .= "\n<script type='text/javascript'>";
        $output .= "/* <![CDATA[ */";

				$output .= "\t var CMM_".$post->ID.'_'.$this->count." = { ";
				foreach ( $localized_script as $key => $value ) {
						if ( $key != 'marker' ) {
		        		$output .= "\t".$key.": '".$value."', ";
		        } else {
		        		$output .= "\t".$key.": ".$value.", ";
						}
        }
				$output .= "};";
        $output .= "/* ]]> */";
        $output .= "</script>\n";

        return $this->render_html ( $output );

		}


		/**
		 *  Render HTML output
		 *
		 *  @since  0.0.3
		 */
		private function render_html ( $html ) {
				global $post;
				$output = '';

				if ( $this->map_case != 'static' )
						$output .=	'<noscript class="no-js error js-error">'. sprintf( __('To see a map here, you should have <a href="%1$s">javascript</a> enabled. <a href="%2$s" title="How to enable Javascript in your browser">Give it a try</a>!', self::LANG ),'http://wikipedia.org/wiki/Javascript','http://www.enable-javascript.com/').'</noscript>';

				$output .=	$html;

				if ( $this->map_case != 'static-bg' && $this->map_caption ) {
						$output =	'<div id="cmm-'.$this->map_case.'-map-id-'.$post->ID.'_'.$this->count.'" class="cmm-'.$this->map_case.'-map-wrap wp-cmm_map-wrap">'.$output.'</div>';
						$output =	do_shortcode( '[caption id="cmm-'.$this->map_case.'-caption-id-'.$post->ID.'_'.$this->count.'" align="'. $this->map_align . '" width="'.$this->localized_vars['width'].'" caption="'.$this->get_location( $post ).'"]'.$output.'[/caption]' );
				} else {
						$output  =	'<div id="cmm-'.$this->map_case.'-map-id-'.$post->ID.'_'.$this->count.'" class="cmm-'.$this->map_case.'-map-wrap '.$this->map_align .' wp-cmm_map-wrap">'.$output.'</div>';
				}

				// unset after use
				$this->map_caption = false;
				
		    return $output;
		}
		
		
		/**
		 *  Translate active map parts
		 *
		 *  @since  0.0.4
		 *  @todo "rename & remove labels for large zoom control" does not work fine
		 */
/*		 public function i18n_map_parts ( ) {

				if ( isset( $this->localized_vars['a_zoomControl'] ) && $this->localized_vars['a_zoomControl'] != "N" ) {
		        $output .= "\n<script type='text/javascript'>";
		        $output .= "/* <![CDATA[ * /";
		        $output .= "\tjQuery(document).ready(function ($) {";

		        // rename & remove labels for large zoom control
		        if ( $this->localized_vars['a_zoomControl'] == "L" ) {
		            $count  = $this->localized_vars['a_minZoom'];
		            while($count <= $this->localized_vars['a_maxZoom']) {
		                $range[] = $count++;
		            }
		            $cm_slider_labels = array(1,5,9,13,17);
		            $cm_slider_label_names = array(__('Region',self::LANG),__('Country',self::LANG),__('County',self::LANG),__('Neighborhood',self::LANG),__('Building',self::LANG));
		            $move_slider_label_down_px  = $this->localized_vars['a_minZoom'] * 9;

		            foreach ($cm_slider_labels as $key => $value) {
		                if (!in_array($value, $range)) {
		                        $output .= "\t\t$('a[href=\"#zoom-to-".$value."\"]').remove();  \n";
		                } else {
		                        $output .= "\t\t$('a[href=\"#zoom-to-".$value."\"]').text('".$cm_slider_label_names[$key]."').css('margin-top','".$move_slider_label_down_px."px');  \n";
		                }
		            }
		        }


        		$output .= "\t});";
		        $output .= "/* ]]> * /";
		        $output .= "</script>\n";
				}
				
		    echo $output;
		 }
*/

    /**
		 *  Enqueue all scripts to load static background-maps
		 *
		 *  @since  0.0.1
		 */
		public function static_map_js ( ) {

				$this->load_jquery();
				
        wp_enqueue_script( 'jquery' );
				$deps = array( 'jquery' );
				if ( is_admin() ) {
						$deps[] = 'cmm-admin-stds';
#						$deps[] = 'yql-geo-library';
				}
				wp_enqueue_script( 'CMM_static_bg_map', plugins_url(  'js/cmm-static-bg-map.js', __FILE__ ), $deps, self::VERS, true );
        wp_localize_script( 'CMM_static_bg_map', 'cmm_base', $this->cmm_base );
		}


    /**
		 *  Enqueue all scripts to load interactive maps
		 *
		 *  @since  0.0.1
		 */
    public function active_map_js ( ) {

				$this->load_jquery();
				
        wp_enqueue_script( 'CMM_active_base', 'http://tile.cloudmade.com/wml/latest/web-maps-lite.js', array('jquery'), 0.4, true );
				$deps = array( 'jquery','CMM_active_base' );
				if ( is_admin() ) {
#						$deps[] = 'cmm-admin-stds';
#						$deps[] = 'yql-geo-library';
				}
        wp_enqueue_script( 'CMM_active_map', plugins_url(  'js/cmm-active-map.js', __FILE__ ), $deps, self::VERS, true );

				$this->cmm_base['key']         = $this->general_opts['api_key'];
		    if (  $this->general_opts['style_ID'] )
		    $this->cmm_base['ID']          =  $this->general_opts['style_ID'];

		    $this->cmm_base['zoomIn'] =  __( 'Zoom in', self::LANG);
		    $this->cmm_base['zoomOut'] =  __( 'Zoom out', self::LANG);

		    $this->cmm_base['enable_fullscreen'] =  __( 'Switch to fullscreen', self::LANG);
		    $this->cmm_base['disable_fullscreen'] =  __( 'Switch to normal size', self::LANG);
		    
        $this->cmm_base['Region'] = __('Region',self::LANG);
        $this->cmm_base['County'] = __('County',self::LANG);
        $this->cmm_base['Country'] = __('Country',self::LANG);
        $this->cmm_base['Neighborhood'] = __('Neighborhood',self::LANG);
        $this->cmm_base['Building'] = __('Building',self::LANG);
        
        if ( is_admin() ) {
        $this->cmm_base['update_map'] = __( 'Update map', self::LANG );
        $this->cmm_base['buttontitle'] = __( 'Insert map', self::LANG ).' ('.self::NAME.')';
				}

        wp_localize_script( 'CMM_active_map', 'cmm_base', $this->cmm_base );
    }

		/**
		 *	check for the existence of jquery and load it if not registered or enqueued
		 *
		 *  @since  0.0.5
		 */
		public function load_jquery () {

				if ( !wp_script_is( 'jquery' ) ) {
						$jquery = 'http://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js';
						if ( !fopen($jquery, 'r') ) {
								$jquery = plugins_url('/js/jquery-1.6.4.min.js', __FILE__ );
						}
						wp_enqueue_script('jquery', $jquery, false, '1.6.4', true);
				}
		}

		/**
		 *  Enqueue all styles to load interactive maps
		 *
		 *  @since  0.0.1
		 */
    public function frontend_map_css ( ) {
		  	wp_enqueue_style('CMM_Active_Map_Styles', plugins_url( 'css/wp-cmm.css', __FILE__ ), false, self::VERS, 'screen' );
    }


		/**
		 *  Instantiate thickbox-media-upload-modal
		 *  to use for uploading the default 'marker-Icon' on the options pages
		 *
		 *  @since  0.0.1
		 */
    public function admin_scripts() {
				global $pagenow;

				wp_enqueue_script('media-upload');
        wp_enqueue_script('thickbox');
/*
        wp_enqueue_script('jquery-ui-custom-core', plugins_url( 'js/jquery-ui-1.7.1.custom.min.js' , __FILE__ ), false, '1.7.1', true );
        wp_enqueue_script('jquery-ui-select-to-slider', plugins_url( 'js/selectToUISlider.jQuery.js' , __FILE__ ), array( 'jquery', 'jquery-ui-custom-core' ), 2.0, true );
*/
				$deps = array('jquery','media-upload','thickbox');
				if ( $pagenow == 'post-new.php' || $pagenow == 'post.php' ) {
						$deps[] = 'CMM_active_map';
						$deps[] = 'yql-geo-library';
				}
				
        wp_enqueue_script('cmm-admin-stds', plugins_url( 'js/cmm-admin.js' , __FILE__ ), $deps, self::VERS, true );

    }
    
    
    public function admin_css() {
/*
        wp_enqueue_style( 'jquery-ui-select-to-slider-styles', plugins_url( 'css/jQuery-slider/redmond/jquery-ui-1.7.1.custom.css' , __FILE__ ), false, '1.7.1' );
        wp_enqueue_style( 'jquery-ui-select-to-slider-extra-styles', plugins_url( 'css/jQuery-slider/ui.slider.extras.css' , __FILE__ ), false, 2.0 );
*/
				wp_enqueue_style( 'CMM-admin-styles', plugins_url( 'css/admin.css' , __FILE__ ), false, self::VERS );
    }


    public function admin_thickbox_css() {
        wp_enqueue_style( 'thickbox' );
    }


    public function yql_geo_library() {
				wp_enqueue_script('yql-geo-library', plugins_url( 'js/yqlgeo.js' , __FILE__ ), array('jquery'), self::VERS, true );
				wp_localize_script('yql-geo-library', 'CMM_geo_lib', array( 'flickr_places_api_key' => $this->general_opts['flickr_places_api_key'] ) );
    }
    


		/**
		 *  Add geo meta-tags to head
		 *
		 *  @since  0.0.4
		 */
		public function add_geo_meta_tags ( ) {
				global $post;
				
				$lat = $lng = $code = $adr = ''; $loc = array();
				if ( $this->general_opts['add_meta_tag'] ) {

						if ( is_singular() && get_post_meta($post->ID, '_wp_cmm_location', true ) ) {

								$loc  = get_post_meta($post->ID, '_wp_cmm_location', true );
				        $lat 	= $loc['lat'];
				        $lng 	= $loc['lng'];
								$code = $loc['region_code'];
								$adr  = $loc['city'];
						} else {

						    $lat 	= $this->general_opts['da_lat'];
				        $lng 	= $this->general_opts['da_lng'];
								$code = $this->general_opts['da_region_code'];
								$adr  = $this->general_opts['da_city'];
						}

						$output  = '<meta name="ICBM" content="' . esc_attr( $lat . ', ' . $lng ) . '">'. "\n";
		        $output .= '<meta name="geo.position" content="' . esc_attr( $lat . ';' . $lng ) . '">' . "\n";
		        $output .= '<meta name="geo.region" content="' . esc_attr( $code ) . '">' . "\n";
		        $output .= '<meta name="geo.placename" content="' . esc_attr( $adr ) . '">' . "\n";

						echo $output;

				}
		}
		
		
		/**
		 *  Add geo-microformat to posts html output
		 *
		 *  @since  0.0.4
		 */
    public function add_microformat_geo_tags_to_posts ( $the_content ) {

        global $post;
				if ( $this->general_opts['add_microformat_geo_tag'] && get_post_meta($post->ID, '_wp_cmm_location' ) ) {

						$coordinates = CloudMadeMap::get_coordinates( $post );

		        $output  =  "\n<p class='cmm-geo-tags geo'>";
		        $output .=  "<span class='latitude'>".$coordinates['lat']."</span>,<span class='longitude'>".$coordinates['lng']."</span>";
		        $output .=  "</p>\n";

		        $the_content  .=  $output;
				}
        return $the_content;

    }


		/**
		 *  Replace the placeholder-image with the real shortcode
		 *  for frontend output
		 *
		 *  @since  0.0.5
		 */
		public function replace_placeholder_image ( $content ) {
				$content = preg_replace('!.*?<img.*?class="wp-cmm_placeholderImage.*?title="(.*?)".*?/>.*?!i', '[$1] ', $content );
        $content =  force_balance_tags( $content );
				return $content;
		}

		/**
		 *  regex search for existence of given shortcode-parts in posts shortcodes
		 *
		 *  @atts     $shortcode_array    array()
		 *  @atts     $posts              array() of $post objects
		 *
		 *  @return   int( 0 | 1 ), false on failure
		 *	@since    0.0.3
		 */
		private function conditionally_load_css_and_js( $shortcode_array, $posts ){

				if (empty($posts)) return false;
				$shortcode_found = false;
				foreach ($posts as $post) {

								// replace map-placeholder-image with correct shortcode
                $post->post_content = $this->replace_placeholder_image ( $post->post_content );

				        // look for searched shortcodes
# 								array_walk( $shortcode_array, create_function( '&$val', '$val = ".*?(?:\b|_)".$val."(?:\b|_)";' ) );
 								array_walk( $shortcode_array, create_function( '&$val', '$val = "(.*?\b".$val.".*?)";' ) );
								$pattern = '~(?<=\[)'.implode( '', $shortcode_array ).'(?=\])~';
								if (preg_match( $pattern, $post->post_content) === 1 ) {
										$shortcode_found = true; // bingo!
								}
				}
				return $shortcode_found;
		}


		/**
		 *  Add and load all settings-pages
		 *
		 *  @since  0.0.1
		 */
    public function admin_menu() {

				if ( current_user_can('manage_options') ) {

						global $CMM_general_page, $CMM_static_page, $CMM_active_page;

		        $CMM_general_page = add_menu_page( self::NAME.' '.__('Options'), self::NAME, 'manage_options', sanitize_title_with_dashes( strtolower( self::NAME ) ).'-general-options',  array( &$this, 'admin_menu_general' ), plugins_url( 'img/CMM_16.png' , __FILE__ ) );
		        $CMM_general_page = add_submenu_page( sanitize_title_with_dashes( strtolower( self::NAME ) ).'-general-options', __('General')." ".__('Settings')." &lsaquo; ".self::NAME, __('General'), 'manage_options',  sanitize_title_with_dashes( strtolower( self::NAME ) ).'-general-options', array( &$this, 'admin_menu_general' ) );
		        add_action( 'admin_print_styles-' . $CMM_general_page, array( &$this, 'admin_css') );
				   	add_action( 'admin_print_scripts-' . $CMM_general_page, array( &$this, 'yql_geo_library'));
		        add_action( 'admin_print_scripts-' . $CMM_general_page, array( &$this, 'admin_scripts'));


						if ( $this->general_opts['pp_static'] ) {

		            $CMM_static_page  = add_submenu_page( sanitize_title_with_dashes( strtolower( self::NAME ) ).'-general-options', __('Static Maps', self::LANG )." ".__('Settings')." &lsaquo; ".self::NAME, __('Static Maps', self::LANG ), 'manage_options',  sanitize_title_with_dashes( strtolower( self::NAME ) ).'-static-options', array( &$this, 'admin_menu_static' ) );
		            add_action( 'admin_print_styles-' . $CMM_static_page, array( &$this, 'admin_css') );
		          	add_action( 'admin_print_styles-' . $CMM_static_page, array( &$this, 'admin_thickbox_css'));

								if ( $this->static_opts['show_example'] ) {

										$this->map_case = 'admin-static-sample';
										add_action( 'admin_print_scripts-' . $CMM_static_page, array( &$this, 'yql_geo_library'));
		            }
								add_action( 'admin_print_scripts-' . $CMM_static_page, array( &$this, 'admin_scripts'));
		        }
		/*
		        if ( array_key_exists('pp_widget', $general_opts) && $general_opts['pp_widget'] ) {
		            include_once 'menu/widget.php';
		            $CMM_widget_page  = add_submenu_page( sanitize_title_with_dashes( strtolower( self::NAME ) ).'-general-options', __('Widgets')." ".__('Settings')." &lsaquo; ".self::NAME, __('Widgets'), 'manage_options',  CMM_PLUGIN_DIR.'/menu/widget.php', 'CMM_Menu_Widget');
		            add_action( 'admin_print_styles-' . $CMM_widget_page, array( &$this, 'admin_css') );
		        }
		*/

		        if ( $this->general_opts['pp_active'] ) {

		            $CMM_active_page  = add_submenu_page( sanitize_title_with_dashes( strtolower( self::NAME ) ).'-general-options', __('Interactive Maps', self::LANG )." ".__('Settings')." &lsaquo; ".self::NAME, __('Interactive Maps', self::LANG), 'manage_options',  sanitize_title_with_dashes( strtolower( self::NAME ) ).'-active-options', array( &$this, 'admin_menu_active' ) );
		            add_action( 'admin_print_styles-' . $CMM_active_page, array( &$this, 'admin_css') );
		          	add_action( 'admin_print_styles-' . $CMM_active_page, array( &$this, 'admin_thickbox_css'));

								if ( $this->active_opts['show_example'] ) {

										$this->map_case = 'admin-active-single-sample';
				            add_action( 'admin_print_scripts-' . $CMM_active_page, array( &$this, 'yql_geo_library'));
				            add_action( 'admin_print_scripts-' . $CMM_active_page, array( &$this, 'active_map_js'));
		            }
		            add_action( 'admin_print_scripts-' . $CMM_active_page, array( &$this, 'admin_scripts'));
		        }


		        $CMM_credits_page = add_submenu_page( sanitize_title_with_dashes( strtolower( self::NAME ) ).'-general-options', __('Credits', self::LANG )." &lsaquo; ".self::NAME, __('Credits', self::LANG), 'manage_options',  sanitize_title_with_dashes( strtolower( self::NAME ) ).'-credits', array( &$this, 'admin_menu_credits' ) );
		        add_action( 'admin_print_styles-' . $CMM_credits_page, array( &$this, 'admin_css') );

				}
    }

		public function admin_menu_general ( ) {
				include_once 'menu/general.php';
		}

		public function admin_menu_static ( ) {
				include_once 'menu/static.php';
		}


		public function admin_menu_active ( ) {
				include_once 'menu/active.php';
		}

		public function admin_menu_credits ( ) {
				include_once 'menu/credits.php';
		}
		
		public function validate_options ( $input ) {

				$valid = array();

				switch ( $input['usage_env'] ) {
						case 'general' :
						  $predefined_opts  = $this->defined_general_opts;
							$old_opts         = $this->general_opts;
							break;
						case 'static' :
						  $predefined_opts  = $this->defined_static_opts;
							$old_opts         = $this->static_opts;
						  break;
						case 'active' :
						  $predefined_opts  = $this->defined_active_opts;
							$old_opts         = $this->active_opts;
						  break;
				}
				unset ( $input['usage_env'] );

				foreach ( $input as $key => $tmp ) {

						switch ( $predefined_opts[$key]['validate'] ) {

								case 'numeric' :
								  if ( is_numeric ( $tmp ) || $tmp == $predefined_opts[$key]['value'] ){
								      $valid[$key]  = $tmp;
								  } else {
/*
								      // check against px or percent
								      if ( ( $key == 'width' && substr ( $tmp, -1) == '%' && is_numeric( substr ( $tmp, 0, -1) ) )
														||
													 ( ( $key == 'width' || $key == 'height' ) && substr ( $tmp, -2) == 'px' && is_numeric( substr ( $tmp, 0, -2) ) ) ) {
								      		$valid[$key]  = $tmp;
											} else {
*/
										      $valid[$key]  = $predefined_opts[$key]['value'];
										      if ( $predefined_opts[$key]['validate_msg'] ){
										      		add_settings_error( $key, $key.'_error', '<em>'.$predefined_opts[$key]['label'].':</em> '.$predefined_opts[$key]['validate_msg'], 'error' );
													} else {
										      		add_settings_error( $key, $key.'_error', '<em>'.$predefined_opts[$key]['label'].':</em> '.__('Only integers are accepted.',self::LANG), 'error' );
													}
#											}
								  }
								  break;


								case 'url' :
								  if ( esc_url ( $tmp ) == $tmp ){
								      $valid[$key]  = $tmp;
								  } else {
								      $valid[$key]  = $predefined_opts[$key]['value'];
								      if ( $predefined_opts[$key]['validate_msg'] ){
								      		add_settings_error( $key, $key.'_error', '<em>'.$predefined_opts[$key]['label'].':</em> '.$predefined_opts[$key]['validate_msg'], 'error' );
											} else {
								      		add_settings_error( $key, $key.'_error', '<em>'.$predefined_opts[$key]['label'].':</em> '.__('Please provide a correct URL.',self::LANG), 'error' );
											}
								  }
								  break;


								case 'text' :
								  if ( wp_filter_nohtml_kses ( $tmp ) == $tmp ){
								      $valid[$key]  = $tmp;
								  } else {
								      $valid[$key]  = $predefined_opts[$key]['value'];
								      if ( $predefined_opts[$key]['validate_msg'] ){
								      		add_settings_error( $key, $key.'_error', '<em>'.$predefined_opts[$key]['label'].':</em> '.$predefined_opts[$key]['validate_msg'], 'error' );
											} else {
								      		add_settings_error( $key, $key.'_error', '<em>'.$predefined_opts[$key]['label'].':</em> '.__('Only integers and simple chars are accepted.',self::LANG), 'error' );
											}
								  }
								  break;



								case 'options' :
								  if ( array_key_exists( $tmp, $predefined_opts[$key]['options'] ) ){
								      $valid[$key]  = $tmp;
								  } else {
								      $valid[$key]  = $predefined_opts[$key]['value'];
								      if ( $predefined_opts[$key]['validate_msg'] ){
								      		add_settings_error( $key, $key.'_error', '<em>'.$predefined_opts[$key]['label'].':</em> '.$predefined_opts[$key]['validate_msg'], 'error' );
											} else {
								      		add_settings_error( $key, $key.'_error', '<em>'.$predefined_opts[$key]['label'].':</em> '.__('Please choose one of the provided options.',self::LANG), 'error' );
											}
								  }
								  break;
								  
								  
/*
								case 'multioptions' :
								  $valid[$key]  = array();
									foreach ( $tmp as $tmp_v ) {
										  if ( array_key_exists( $tmp_v, $predefined_opts[$key]['options'] ) ){
										      array_push($valid[$key], $tmp_v);
										  }
								  }
								  if ( !isset( $valid[$key] ) ) {
								      $valid[$key]  = $predefined_opts[$key]['value'];
								      if ( $predefined_opts[$key]['validate_msg'] ){
								      		add_settings_error( $key, $key.'_error', '<em>'.$predefined_opts[$key]['label'].':</em> '.$predefined_opts[$key]['validate_msg'], 'error' );
											} else {
								      		add_settings_error( $key, $key.'_error', '<em>'.$predefined_opts[$key]['label'].':</em> '.__('Please choose one of the provided options.',self::LANG), 'error' );
											}
								  }
								  break;
*/

								case 'terms_checklist' :
								  $valid[$key]  = array();
#var_dump($tmp);
									foreach ( $tmp as $k => $v ) {
										  if ( is_numeric ( $v ) || $v == $predefined_opts[$key]['value'] ){
										      array_push($valid[$key], $k);
										  }
								  }
								  if ( !isset( $valid[$key] ) ) {
								      $valid[$key]  = $predefined_opts[$key]['value'];
								      if ( $predefined_opts[$key]['validate_msg'] ){
								      		add_settings_error( $key, $key.'_error', '<em>'.$predefined_opts[$key]['label'].':</em> '.$predefined_opts[$key]['validate_msg'], 'error' );
											} else {
								      		add_settings_error( $key, $key.'_error', '<em>'.$predefined_opts[$key]['label'].':</em> '.__('Please choose one of the provided options.',self::LANG), 'error' );
											}
								  }
								  break;

						}

				}

				foreach ( $old_opts as $k => $v ) {
           	$old_opts[$k]	= ( isset( $valid[$k] ) ) ? $valid[$k] : null;
				}

				return $old_opts;
		}


		/**
		 *  Register & whitelist options
		 *
		 *  @since  0.0.1
		 */
    public function register_settings(){
        register_setting( 'CMM_general_plugin_options', 'CMM_general_opts', array( &$this, 'validate_options' ) ) ;
        register_setting( 'CMM_static_plugin_options', 'CMM_static_opts', array( &$this, 'validate_options' ) );
        register_setting( 'CMM_active_plugin_options', 'CMM_active_opts', array( &$this, 'validate_options' ) );
    }


		/**
		 *  Load interface to configure static maps output
		 *  used for: widget
		 *
		 *  @since  0.0.4
		 *  @todo   remove this fn and use render_form_fields()
		 */
		public function static_maps_opts_interface ( $defaults ) {
		?>
		
				<p class="el-box">
				  <label for="<?php echo $defaults['zoom']['id']; ?>" class="main-label"><?php _e('default zoom level', self::LANG ); ?></label>
					<select id="<?php echo $defaults['zoom']['id']; ?>" name="<?php echo $defaults['zoom']['name']; ?>">
						<option value='0' <?php selected('0', $defaults['zoom']['value']); ?>>0 = <?php _e('World',self::LANG); ?></option>
						<option value='1' <?php selected('1', $defaults['zoom']['value']); ?>>1</option>
						<option value='2' <?php selected('2', $defaults['zoom']['value']); ?>>2</option>
						<option value='3' <?php selected('3', $defaults['zoom']['value']); ?>>3</option>
						<option value='4' <?php selected('4', $defaults['zoom']['value']); ?>>4</option>
						<option value='5' <?php selected('5', $defaults['zoom']['value']); ?>>5 = <?php _e('Country',self::LANG); ?></option>
						<option value='6' <?php selected('6', $defaults['zoom']['value']); ?>>6</option>
						<option value='7' <?php selected('7', $defaults['zoom']['value']); ?>>7</option>
						<option value='8' <?php selected('8', $defaults['zoom']['value']); ?>>8</option>
						<option value='9' <?php selected('9', $defaults['zoom']['value']); ?>>9 = <?php _e('County',self::LANG); ?></option>
						<option value='10' <?php selected('10', $defaults['zoom']['value']); ?>>10</option>
						<option value='11' <?php selected('11', $defaults['zoom']['value']); ?>>11</option>
						<option value='12' <?php selected('12', $defaults['zoom']['value']); ?>>12</option>
						<option value='13' <?php selected('13', $defaults['zoom']['value']); ?>>13 = <?php _e('Neighborhood',self::LANG); ?></option>
						<option value='14' <?php selected('14', $defaults['zoom']['value']); ?>>14</option>
						<option value='15' <?php selected('15', $defaults['zoom']['value']); ?>>15</option>
						<option value='16' <?php selected('16', $defaults['zoom']['value']); ?>>16</option>
						<option value='17' <?php selected('17', $defaults['zoom']['value']); ?>>17 = <?php _e('Building',self::LANG); ?></option>
						<option value='18' <?php selected('18', $defaults['zoom']['value']); ?>>18</option>
					</select>
				</p>

				<p class="el-box">
				  <label for="<?php echo $defaults['width']['id']; ?>" class="main-label"><?php _e('Width',self::LANG); ?></label>
					<input id="<?php echo $defaults['width']['id']; ?>" name="<?php echo $defaults['width']['name']; ?>" type="text" value="<?php echo $defaults['width']['value']; ?>" size="3" />
					<span class="howto"><?php _e('in px',self::LANG); ?></span>
				</p>

				<p class="el-box">
				  <label for="<?php echo $defaults['height']['id']; ?>" class="main-label"><?php _e('Height',self::LANG); ?></label>
					<input id="<?php echo $defaults['height']['id']; ?>" name="<?php echo $defaults['height']['name']; ?>" type="text" value="<?php echo $defaults['height']['value']; ?>" size="3" />
				  <span class="howto"><?php _e('in px',self::LANG); ?></span>
				</p>
		
		<?php
		}


		/**
		 *  Instantiate tinymce button
		 *
		 *  @since 0.0.4
		 */
		public function tinymce_init_button ( ) {
		   // Don't bother doing this stuff if the current user lacks permissions
		   if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
		     return;

		   // Add only in Rich Editor mode
		   if ( get_user_option('rich_editing') == 'true') {
		     add_filter( 'mce_external_plugins', array( &$this, 'tinymce_plugin' ) );
		     add_filter( 'mce_buttons', array( &$this, 'tinymce_button' ) );
		   }
		}


		/**
		 *	Load the TinyMCE plugin : editor_plugin.js (wp2.5)
		 */
		public function tinymce_plugin ( $plugin_array ) {
				$plugin_array['cloudmademap'] = plugins_url( 'tinymce/editor_plugin.js' , __FILE__ );
				return $plugin_array;
		}


		/**
		 *  Add CMM Button to editor
		 */
		public function tinymce_button ( $buttons ) {
				array_push($buttons, "|", "cloudmademap");
				return $buttons;
		}
		
		
		/**
		 *  Echo the html form for the tinyMCE Button
		 *
		 *  @since  0.0.5
		 */
		public function tinymce_form (  ) {
				include_once 'tinymce/tiny_windowform.php';
		}


		/**
		 *  Add css to style the map-placeholder-image inside tinyMCE
		 *
		 *  @since  0.0.5
		 */
		public function tinymce_css( $wp ) {
				$wp .= ',' . plugins_url( 'css/admin-editor.css', __FILE__ );
				return trim($wp, ' ,');
		}
		

		/**
		 *  Generate messages for WP_Error Class
		 *
		 *  @since  0.0.3
		 */
    private function check_dependecies( ) {
        global $wp_version;
				$error_return  = false;

				if ( ! version_compare ( $wp_version, self::WPNEED, ">=" ) ) {
						$error_return  = true;
            $this->error_msg[] = sprintf(
                __(
                    'Please %1$s your wordpress to at least version %2$s'
                    ,self::LANG
                )
                ,'<a href="'.admin_url('update-core.php').'" title="'.__('Upgrade', self::LANG ).'">'.__('Upgrade', self::LANG ).'</a>'
                ,self::WPNEED
            );
        }

				if ( ! version_compare ( phpversion(), self::PHPNEED, ">=" ) ) {
						$error_return  = true;
            $this->error_msg[] = sprintf(
                __(
                    'You need to run at least PHP %1$s'
                    ,self::LANG
                )
                ,self::PHPNEED
            );
        }
        
				if ( ! isset( $this->general_opts['api_key'] ) ) {
						$error_return  = true;
            $this->error_msg[] = sprintf(
                __(
                    'Please insert a Cloudmade API key at %1$s.'
                    ,self::LANG
                )
                ,'<a href="'.admin_url('admin.php?page='.sanitize_title_with_dashes( strtolower( self::NAME ) ).'-general-options#api_key').'" title="'.self::NAME." ".__('General')." ".__('Settings').'">'.self::NAME." ".__('General')." ".__('Settings').'</a>'
            );
        }

				if ( ! isset( $this->general_opts['posttypes'] ) ) {
						$error_return  = true;
            $this->error_msg[] = sprintf(
                __(
                    'Please define at least one post_type, where to load the "%2$s" meta box on the edit screen at %1$s.'
                    ,self::LANG
                )
                ,'<a href="'.admin_url('admin.php?page='.sanitize_title_with_dashes( strtolower( self::NAME ) ).'-general-options#posttypes').'" title="'.self::NAME." ".__('General')." ".__('Settings').'">'.self::NAME." ".__('General')." ".__('Settings').'</a>'
                ,__( 'Choose location', self::LANG )
            );
        }
        
        return $error_return;
		}


		/**
		 *  Trigger Errors if we got some
		 *
		 *  @since  0.0.3
		 */
    public function admin_notices ( ) {

        settings_errors(  );

        if ( isset( $this->error_msg ) && current_user_can('manage_options') ) {
            $error_code = sanitize_title_with_dashes( strtolower( self::NAME ) );
            $errors = new WP_Error( $error_code, $this->error_msg );
			      $output =''; $i=0;
            if ( is_wp_error( $errors ) ) {
								foreach( $errors->errors as $k => $v ) {
										foreach ( $v[0] as $error_element ) {
				                $output.=
				                    '<div id="error-'.$k.'-'.$i.'" class="error error-notice error-'.$k.'"><p>'.
				                        '<strong>'.self::NAME.'</strong>: '.
				                        $error_element.
				                    '</p></div>';
												$i++;
				            }
								}
                echo $output;
						}

        }
    }


		/**
		 *  Load contextual help for 'Help'-Tab
		 *  inside the settings-pages, with detail descriptions on the shortcodes
		 *
		 *  @since  0.0.2
		 */
    public function contextual_help($contextual_help, $screen_id, $screen) {
				include_once 'menu/contextual_help.php';
      	return $contextual_help;
    }


		/**
		 *  Add 'Settings'-Link directly inside the plugin-description on the 'Plugins'-Page
		 *
		 *  @since  0.0.1
		 */
    public function plugin_action_links( $links, $file ) {
      	if ( $file == CMM_PLUGIN_DIR.CMM_PLUGIN_FILE ) {
	      		$CMM_links = '<a href="'.get_admin_url().'admin.php?page='.CMM_PLUGIN_DIR.'/menu/general.php">'.__('Settings').'</a>';
	      		array_unshift( $links, $CMM_links );
      	}
      	return $links;
    }


		/**
		 *	Add custom meta links to the plugin listing.
		 *
		 *	plugin_row_meta {@link http://codex.wordpress.org/Plugin_API/Filter_Reference#Advanced_WordPress_Filters filter},
		 *
		 *	@since 0.0.5
		 */
		function plugin_row_meta( $links, $file ) {
			if ( $file == CMM_PLUGIN_DIR.CMM_PLUGIN_FILE ) {
					$links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=XHR4SXESC9RJ6">' . __( 'Donate', self::LANG ) . '</a>';
			}
			return $links;
		}



    /**
     * 	Adds the meta_box container to all post-types
     *  choosen at 'General'-Settings-Page
     *
     *  @since  0.0.4
     */
    public function meta_box ( ) {
        foreach ( $this->general_opts['posttypes'] as $posttype ){
        		add_meta_box( 'cmm_chose_location' ,__( 'Choose location', self::LANG ).' ('.self::NAME.')',array( &$this, 'meta_box_content' ) , trim($posttype) );
        }

    }


    /**
     *	Render meta_box content
     *
     *  @since  0.0.4
     */
    public function meta_box_content ( $post ) {

				// Use nonce for verification
			  wp_nonce_field( plugin_basename( __FILE__ ), sanitize_title_with_dashes( strtolower( self::NAME ) ) );

				// get current location values from post meta
				$loc = get_post_meta($post->ID, '_wp_cmm_location', true );

				//
				if ( !is_array( $loc ) ) {
				$loc = array(); $loc['street'] = $loc['zip'] = $loc['city'] = $loc['region'] = $loc['region_code'] = $loc['country'] = $loc['lat'] = $loc['lng'] = '';
				}
				// generate location form
				$output  = '<div id="cmm_choose_location_legend">';
        $output .= '<p>'.__( 'Click on the map or drag the marker to choose location.', self::LANG ).'</p>';
        $output .= '<input type="text" placeholder="'. __( 'Street', self::LANG ) .'" id="cmm_post_meta_street" name="cmm_post_meta[street]" value="'. $loc['street'] .'" />';
#        $output .= '<input type="text" placeholder="'. __( 'Number', self::LANG ) .'" id="cmm_post_meta_number" name="cmm_post_meta[number]" value="'. $loc['number'] .'" />';
        $output .= '<input type="text" placeholder="'. __( 'Postal Code', self::LANG ) .'" id="cmm_post_meta_zip" name="cmm_post_meta[zip]" value="'. $loc['zip'] .'" />';
        $output .= '<input type="text" placeholder="'. __( 'City', self::LANG ) .'" id="cmm_post_meta_city" name="cmm_post_meta[city]" value="'. $loc['city'] .'" />';
        $output .= '<input type="text" placeholder="'. __( 'Region', self::LANG ) .'" id="cmm_post_meta_region" name="cmm_post_meta[region]" value="'. $loc['region'] .'" />';
        $output .= '<input type="text" placeholder="'. __( 'Region Code', self::LANG ) .'" id="cmm_post_meta_region_code" name="cmm_post_meta[region_code]" value="'. $loc['region_code'] .'" />';
        $output .= '<input type="text" placeholder="'. __( 'Country', self::LANG ) .'" id="cmm_post_meta_country" name="cmm_post_meta[country]" value="'. $loc['country'] .'" />';
        $output .= '<input type="hidden" placeholder="'. __( 'Latitude', self::LANG ) .'" class="hidden" id="cmm_post_meta_lat" name="cmm_post_meta[lat]" value="'. $loc['lat'] .'" />';
        $output .= '<input type="hidden" placeholder="'. __( 'Longitude', self::LANG ) .'" class="hidden" id="cmm_post_meta_lng" name="cmm_post_meta[lng]" value="'. $loc['lng'] .'" />';
        $output .= '<input type="button" id="cmm_find_location_on_map" class="button" value="'. __( 'Find on the map', self::LANG ) .'">';
        $output .= '</div>';

				// generate map
				$output .= do_shortcode('[cmm_active_single width="100%" height="330" marker_labels="1" title="'.__( 'Choose location', self::LANG ).'" scale="0" control="S" minzoom="1" maxzoom="18" zoom="16" overview="0" copyright="#footer-upgrade" align="none" fullscreen="false"]');
        
        // generate error message to remind user to choose location
    		$output .= '<div id="'.self::PREFIX.'reminder-to-choose-location" class="error" style="display:none;"><p><strong>'.self::NAME.'</strong>: '.sprintf( __( 'Please <a href="%1$s">choose a location</a> before saving.', self::LANG ), '#cmm_chose_location') .'</p></div>';
    		
    		// generate GUI handler for editing and deletion
    		$output .= '<div id="'.self::PREFIX.'edit-delete-handler" style="display:none;">'.
											'<img src="'.plugins_url( 'img/CMM_32.png', __FILE__ ).'" id="'.self::PREFIX.'update-handler" alt="'.__( 'Update map', self::LANG ).'" title="'.__( 'Update map', self::LANG ).'" width="24" height="24" />'.
											'<img src="/wp-includes/js/tinymce/plugins/wpeditimage/img/delete.png" id="'.self::PREFIX.'delete-handler" alt="'.__( 'Delete map', self::LANG ).'" title="'.__( 'Delete map', self::LANG ).'" />'.
									 '</div>';
									 
        echo $output;
    }


		/**
		 *	save location data to post_meta
		 *
		 *  @since  0.0.4
		 */
		public function meta_box_save_postdata( $post_id ) {
/*
				// verify this is not an auto save routine.
			  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			      return $post_id;
*/

				// verify this came from our screen and with proper authorization,
			  if ( !isset( $_POST[ sanitize_title_with_dashes( strtolower( self::NAME ) ) ] ) )
			      return $post_id;

				// verify this came from our screen and with proper authorization,
			  if ( !wp_verify_nonce( $_POST[ sanitize_title_with_dashes( strtolower( self::NAME ) ) ], plugin_basename( __FILE__ ) ) )
			      return $post_id;

				// set post ID if is a revision
				if( $parent_id = wp_is_post_revision ( $post_id ) ) {
						$post_id = $parent_id;
				}

				// Check permissions
		    foreach ( $this->general_opts['posttypes'] as $posttype ){
						if ( $posttype == $_POST['post_type'] ) {
		          $posttype_object = get_post_type_object( $posttype );
							if ( !current_user_can( $posttype_object->cap->edit_post, $post_id ) )
					        return $post_id;
					  }
				}

			  // validate inputs
			  $input = $_POST['cmm_post_meta'];
			  $old = get_post_meta($post_id, '_wp_cmm_location', true );

				// street
			  $tmp['street'] =  wp_filter_nohtml_kses($input['street']); // Sanitize textarea input (strip html tags, and escape characters)
			  if ( ($tmp['street'] == $input['street']) && ($input['street']!= '') ) {
			      $valid['street']  = $input['street'];
			  } else {
#			      add_settings_error( 'street', 'street_error',  '<em>'.__('Street and Number',CloudMadeMap::LANG).'</em>: '.__('Only simple chars are accepted.',CloudMadeMap::LANG), 'error' );
			      $valid['street']  = $old['street'];
				}
/*
				// housenumber
			  $tmp['number'] =  wp_filter_nohtml_kses($input['number']); // Sanitize textarea input (strip html tags, and escape characters)
			  if ( ($tmp['number'] == $input['number']) && ($input['number']!= '') ) {
			      $valid['number']  = $input['number'];
			  } else {
#			      add_settings_error( 'street', 'street_error',  '<em>'.__('Street and Number',CloudMadeMap::LANG).'</em>: '.__('Only simple chars are accepted.',CloudMadeMap::LANG), 'error' );
			      $valid['number']  = $old['number'];
				}
*/
				// postal code
			  if ( is_numeric($input['zip']) ) {
			  		$valid['zip']  = $input['zip'];
			  } else {
#			      add_settings_error( 'zip', 'zip_error',  '<em>'.__('Postal Code',CloudMadeMap::LANG).'</em>: '.__('Only integers are accepted.',CloudMadeMap::LANG), 'error' );
			      $valid['zip']  = $old['zip'];
				}

				// city
			  $tmp['city'] =  wp_filter_nohtml_kses($input['city']); // Sanitize textarea input (strip html tags, and escape characters)
			  if ( ($tmp['city'] == $input['city']) && ($input['city']!= '') ) {
			      $valid['city']  = $input['city'];
			  } else {
#			      add_settings_error( 'zip', 'zip_error',  '<em>'.__('Postal Code',CloudMadeMap::LANG).'</em>: '.__('Only integers are accepted.',CloudMadeMap::LANG), 'error' );
			      $valid['city']  = $old['city'];
				}

				// region
				$tmp['region'] =  wp_filter_nohtml_kses($input['region']); // Sanitize textarea input (strip html tags, and escape characters)
			  if ( ($tmp['region'] == $input['region']) && ($input['region']!= '') ) {
			      $valid['region']  = $input['region'];
			  } else {
#			      add_settings_error( 'zip', 'zip_error',  '<em>'.__('Postal Code',CloudMadeMap::LANG).'</em>: '.__('Only integers are accepted.',CloudMadeMap::LANG), 'error' );
			      $valid['region']  = $old['region'];
				}
				
				// region-code
			  $tmp['region_code'] =  wp_filter_nohtml_kses($input['region_code']); // Sanitize textarea input (strip html tags, and escape characters)
			  if ( ($tmp['region_code'] == $input['region_code']) && ($input['region_code']!= '') ) {
			      $valid['region_code']  = $input['region_code'];
			  } else {
#			      add_settings_error( 'zip', 'zip_error',  '<em>'.__('Postal Code',CloudMadeMap::LANG).'</em>: '.__('Only integers are accepted.',CloudMadeMap::LANG), 'error' );
			      $valid['region_code']  = $old['region_code'];
				}

				// country
			  $tmp['country'] =  wp_filter_nohtml_kses($input['country']); // Sanitize textarea input (strip html tags, and escape characters)
			  if ( ($tmp['country'] == $input['country']) && ($input['country']!= '') ) {
			      $valid['country']  = $input['country'];
			  } else {
#			      add_settings_error( 'zip', 'zip_error',  '<em>'.__('Postal Code',CloudMadeMap::LANG).'</em>: '.__('Only integers are accepted.',CloudMadeMap::LANG), 'error' );
			      $valid['country']  = $old['country'];
				}
				
				// Latitude
			  if ( is_numeric($input['lat']) ) {
			  		$valid['lat']  = $input['lat'];
			  } else {
#			      add_settings_error( 'lat', 'lat_error',  '<em>'.__('Latitude',CloudMadeMap::LANG).'</em>: '.__('Only integers are accepted.',CloudMadeMap::LANG), 'error' );
			      $valid['lat']  = $old['lat'];
				}


				// Longitude
			  if ( is_numeric($input['lng']) ) {
			  		$valid['lng']  = $input['lng'];
			  } else {
#			      add_settings_error( 'lng', 'lng_error',  '<em>'.__('Longitude',CloudMadeMap::LANG).'</em>: '.__('Only integers are accepted.',CloudMadeMap::LANG), 'error' );
			      $valid['lng']  = $old['lng'];
				}

				// save input as post_meta
				update_post_meta($post_id, '_wp_cmm_location', $valid );
		}


		/**
		 *  generate form-fields for use on
		 *  * settings-pages
		 *  * tinymce-interface
		 *  * widget-controls
		 *
		 *  @since  0.0.5
		 */
		public function render_form_fields ( $opt_fields, $prefix, $usage, $family, $name_attr = false  ) {

        $first_row = null;
				$rowgroup_counter = false;

		    foreach ($opt_fields as $id => $field) {

						// strip-out unneccessary fields
						if ( in_array( $usage, $field['usage'] ) ) {

								$output = $formfield = '';

								// set name attribute
								$name	=	( $name_attr ) ? $name_attr.'['.$id.']' : $id ;

								// set id and css class
								$css_id = $prefix.$usage.$family.'_'.$id;

								//
								$class = $prefix.$usage.$family.' '.$prefix.$usage.$id.' '.$prefix.$family.$id;
								$class = ( isset( $field['class'] ) ) ? $class.' '.$field['class'] : $class;

								// pre-define if to use scope="rowgroup" or tr without th
								$rowgroup_counter = ( $rowgroup_counter >= 0 ) ? $rowgroup_counter : false ;



								if ( $field['type'] == 'rowgroup' ) {

										//
						        $output .= '<tr class="'.$css_id.' '.$prefix.$id.'"><th scope="rowgroup" rowspan="'.$field['rows'].'">'.$field['label'].'</th>';
				            $rowgroup_counter = $field['rows'];
				            $first_row  = true;
								} else {

										//  go on and do not add another tr
										if ( isset( $first_row ) ) {
		                    unset( $first_row );
		                //
										} elseif ( $rowgroup_counter === null || $rowgroup_counter === false ) {
				 								$output .= '<tr class="'.$css_id.' '.$prefix.$id.'"><th><label for="'.$css_id.'">'.$field['label'].'</label></th>';
										//
										} else {
				 								$output .= '<tr class="'.$css_id.' '.$prefix.$id.'">';
								    }

						        $output .= '<td class="'.$field['type'].'">';

						                switch( $field['type'] ) {


																case 'text':
																	$formfield	= '<input type="text" name="'.$name.'" id="'.$css_id.'" value="'.$field['value'].'" class="'.$class.'"/>';
																break;


																case 'checkbox':
																	$formfield .= '<input type="checkbox" name="'.$name.'" id="'.$css_id.'" value="1" '. checked( $field['value'], 1, false ) .' class="'.$class.'"/>';
																break;


																case 'select':
																	$formfield .= '<select name="'.$name.'" id="'.$css_id.'" class="'.$class.'">';
																	foreach ($field['options'] as $k => $v ) {
																		$formfield .= '<option'. selected( $field['value'], $k, false ) .' value="'.$k.'">'.$v.'</option>';
																	}
																	$formfield .= '</select>';
																break;



																case 'radio':
																	foreach ($field['options'] as $k => $v ) {
																		$formfield .=  '<input type="radio" name="'.$name.'" id="'.$css_id.'-'.$k.'" value="'.$k.'" '. checked( $field['value'], $k, false ) .' class="'.$id.'-'.$k.'" />';
																		$formfield .=  '<label for="'.$css_id.'-'.$k.'" class="'.$id.' '.$id.'-'.$k.'">'.$v.'</label>';
																	}
																break;


																case 'upload':
																	$formfield .= '<input type="text" name="'.$name.'" id="'.$css_id.'" value="'.$field['value'].'" class="'.$class.'"/>';
																	$formfield .= '<input id="'.$css_id.'-upload" class="wp-cmm_call-wp-mediamanagement" type="button" value="'.__( 'Upload' ).'">';
																break;

/*
																case 'multipleselect':
																	$formfield .= '<select name="'.$name.'[]" id="'.$css_id.'" class="'.$class.'" multiple>';
																	foreach ($field['options'] as $value => $label ) {
																	 	$selected = ( in_array( $value, $field['value'] ) ) ? ' selected' : '';
																		$formfield .= '<option'. $selected .' value="'.$value.'">'.$label.'</option>';
																	}
																	$formfield .= '</select>';
																break;
*/

																case 'terms_checklist':
																	$formfield .= '<ul id="'.$css_id.'" class="categorychecklist form-no-clear '.$class.'" >';
																	foreach ($field['options'] as $value => $label ) {
																	 		$checked = ( in_array( $value, $field['value'] ) ) ? ' checked' : '';
																			$formfield .= '<li><label for="'.$css_id.'-'.$value.'"><input'.$checked.' type="checkbox" name="'.$name.'['.$value.']" id="'.$css_id.'-'.$value.'" value="1" />'.$label.'</label></li>';
#																			$formfield .= '<li><label for="'.$css_id.'-'.$value.'"><input'.$checked.' type="checkbox" name="'.$name.'" id="'.$css_id.'-'.$value.'" value="'.$value.'" />'.$label.'</label></li>';
																	}
																	$formfield .= '</ul>';
																break;
																
						                } //end switch

										//
										if ( $rowgroup_counter !== false && $rowgroup_counter !== null && $field['label']  ) {
				 								$output .= '<label for="'.$css_id.'">'.$formfield.$field['label'].'</label>';
								    } else {
												$output .= $formfield;
										}

										//
										if ( $field['desc'] ){
												$output .= '<span class="howto">'.$field['desc'];
						            if ( $field['type'] == 'upload' && $field['value'] != '' ) {
														$output .= ' <span class="current-uploaded-file">'. __( 'Your current marker:', self::LANG ) .' <img src="'. $field['value'] .'" /></span>';

												}
								        $output .= '</span>';
										}
						        $output .= "</td></tr>\n\n";
				        }

								$rowgroup_counter--;

								echo $output;
						} // end strip-out
		    } // end foreach
		}


		/**
		 *  Get default options
		 *  used for activation and upgrade
		 *
		 *  @since  0.0.6
		 */
	 	static function get_default_opts ( $option ) {
        switch ( $option ) {

		        case 'general':
	        		$default_opts = array(
									"version"  								=> CloudMadeMap::VERS,
									"style_ID"  							=> null,
									"api_key"   							=> null,
									"flickr_places_api_key"   => null,
									"pp_static" 							=> null,
									"pp_widget" 							=> null,
									"pp_active" 							=> null,
									"posttypes" 							=> array( 'post', 'page' ),
									"da_street" 							=> null,
									"da_zip" 									=> null,
									"da_city" 								=> null,
									"da_region" 							=> null,
									"da_region_code"					=> null,
									"da_country" 							=> null,
									"da_lat" 									=> null,
									"da_lng" 									=> null,
									"add_meta_tag" 						=> 1,
									"add_microformat_geo_tag" => 1,
									"chk_default_options_db"  => 1
	            );
		        	break;

						case 'static':
	        		$default_opts = array(
									"width"    								=> 600,
	    						"height"   								=> 400,
	    						"zoom"     								=> 9,
	                "marker_icon" 						=> plugins_url( 'img/marker_icon.png' , __FILE__ ),
									"align"       						=> 'none',
									"caption"     						=> NULL,
									"bg_element"  						=> 'html',
	                "show_example"						=> 1,
	    						"chk_default_options_db"  => 1
        			);
		        	break;

						case 'active':
	        		$default_opts = array(
									"width"    								=> 600,
									"height"   								=> 400,
									"zoom"     								=> 9,
			            "minzoom"     						=> 2,
			            "maxzoom"     						=> 18,
			            "control"      						=> 'N',
			            "scale"										=> null,
			            "overview" 								=> null,
                  "fullscreen"              => 1,
									"marker_icon" 						=> plugins_url( 'img/marker_icon.png' , __FILE__ ),
			            "marker_icon_width"   		=> '16',
			            "marker_icon_height"  		=> '16',
			            "marker_labels"       		=> 1,
			            "align"        						=> 'none',
									"caption"     						=> null,
			            "copyright"     					=> null,
									"show_example" 						=> 1,
									"chk_default_options_db"  => 1
	            );
		        	break;
        }
        return $default_opts;
		}


		/**
		 *  Upgrade options in the DB if this is a new version
		 *  keep old settings and set everything new to its defaults
		 *
		 *  @since 0.0.5
		 */
    private function upgrade ( ) {

				if ( $this->general_opts['version'] && version_compare ( $this->general_opts['version'], self::VERS, "<" ) ) {

					 if ( is_array( $this->general_opts ) && is_array( $this->get_default_opts ( 'general' ) ) ) {
							 // set new added general opts to their defaults and keep old settings
							 $updated_general_opts  =  $this->general_opts + $this->get_default_opts ( 'general' );
							 // set new Version number
							 $updated_general_opts['version'] = self::VERS;
							 update_option( 'CMM_general_opts', $updated_general_opts );
					 }
/*
echo '<pre>';
echo '<h1>$this->general_opts</h1>';
var_dump( $this->general_opts );
echo '</pre>';

echo '<pre>';
echo '<h1>$this->get_default_opts ( general )</h1>';
var_dump( $this->get_default_opts ( 'general' ) );
echo '</pre>';

echo '<pre>';
echo '<h1>$updated_general_opts</h1>';
var_dump( $updated_general_opts  );
echo '</pre>';
   */
					 if ( is_array( $this->static_opts ) && is_array( $this->get_default_opts ( 'static' ) ) ) {
							 // set new added static opts to their defaults and keep old settings
							 $updated_static_opts  =  $this->static_opts + $this->get_default_opts ( 'static' );
							 update_option( 'CMM_static_opts', $updated_static_opts );
					 }

					 if ( is_array( $this->active_opts ) && is_array( $this->get_default_opts ( 'active' ) ) ) {
							 // set new added active opts to their defaults and keep old settings
							 $updated_active_opts  =  $this->active_opts + $this->get_default_opts ( 'active' );
							 update_option( 'CMM_active_opts', $updated_active_opts );
					 }
				}
    }


		/**
		 *  Set default options on first install
		 *  or keep user settings on reactivation
		 *
		 *  @since  0.0.1
		 */
    static function activate ( ) {


				$wpcmm = new CloudMadeMap;
        // General Options
    	  $tmp = get_option('CMM_general_opts');
        if( $tmp['chk_default_options_db'] == 1 || !is_array( $tmp ) ) {
        		delete_option('CMM_general_opts');
        		add_option('CMM_general_opts', $wpcmm->get_default_opts ( 'general' ), ' ', 'no' );
    	  }

        // Static Maps Options
    	  $tmp = get_option('CMM_static_opts');
        if( $tmp['chk_default_options_db'] == 1 || !is_array( $tmp ) ) {
        		delete_option('CMM_static_opts');
        		add_option('CMM_static_opts', $wpcmm->get_default_opts ( 'static' ), ' ', 'no' );
    	  }

        // Active Maps Options
    	  $tmp = get_option('CMM_active_opts');
        if( $tmp['chk_default_options_db'] == 1 || !is_array( $tmp ) ) {
        		delete_option('CMM_active_opts');
        		add_option('CMM_active_opts', $wpcmm->get_default_opts ( 'active' ), ' ', 'no' );
    	  }
    }


		/**
		 *  fall asleep and do nothing ;)
		 */
    static function deactivate ( ) {
				// do nothing ;)
    }


		/**
		 *  delete all options
		 *
		 *  @since  0.0.3
		 *  @todo   deletion of widget_opts does not work
		 */
    static function uninstall () {
				delete_option('CMM_general_opts');
        delete_option('CMM_static_opts');
        delete_option('CMM_active_opts');
        delete_option('widget_cmm_last_geoposts-__i__');
        delete_option('widget_cmm_last_geoposts');
    }

    
  } // class
} // if class exists

if ( class_exists("CloudMadeMap") ) { 	$CloudMadeMap = new CloudMadeMap();	}

?>