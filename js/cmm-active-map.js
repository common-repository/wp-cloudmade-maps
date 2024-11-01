jQuery(document).ready(function ($) {

$.fn.addCloudMadeMapMarkers = function( 
                                        containerID,
                                        wp_cmm_map,
                                        markerArray
                                         ) {

/*** @todo 
 *
 *  define hIconAnchor
 *  define vIconAnchor 
 *  **/

 var hIconAnchor  = 8;
 var vIconAnchor  = 24;
 var clusterRadius= 65;

    // create marker Array
    var cmMarkers = [];
    var cmIcon    = [];
    var cmLabels  = [];   

    // Add markers from the array to the map
    for ( var i=0; i<markerArray.length; i++ ){
        var mLat      = markerArray[i][0];
        var mLng      = markerArray[i][1];
        var mTitle    = markerArray[i][2];
        var mIcon     = markerArray[i][3];        
        var mIconW    = markerArray[i][4];             
        var mIconH    = markerArray[i][5];
        var mLabel    = markerArray[i][6];        
        var mContent  = markerArray[i][7];     
        var mDraggable = ( markerArray[i][8] == 1 ) ? true : false;
        
        // decode html entities from php
        // @source:  http://stackoverflow.com/a/2419664
        mContent = $("<div/>").html(mContent).text();
        
        // Build the icon object with path, size and pixelOffset
        cmIcon[i]             = new CM.Icon();
        cmIcon[i].image       = mIcon;
        cmIcon[i].iconSize    = new CM.Size(mIconW, mIconH);
        cmIcon[i].iconAnchor  = new CM.Point(hIconAnchor, vIconAnchor);   
        
        var marker  =  new CM.Marker( new CM.LatLng(mLat, mLng), { title : mTitle, icon : cmIcon[i], draggable : mDraggable } );

        if ( typeof do_cluster == 'undefined' || do_cluster == false  ) {

              wp_cmm_map.addOverlay(marker);
              
              if ( mLabel == 1 ) {
                  var label = new CM.LabeledMarker(new CM.LatLng(mLat, mLng), {	title   : mTitle, draggable  : mDraggable  });
                  wp_cmm_map.addOverlay(label);
                  $( '#' + containerID + ' .wml-labeled-marker-content').css('padding-left',parseInt(mIconW) );
              }
              

           		// moves the label to the marker position
			        CM.Event.addListener(marker, 'drag', function() {
			                label.setLatLng( this.getLatLng() );
			        });
			        //  look for dragging the marker and fires wenn drag ends
			        CM.Event.addListener(marker, 'dragend', function() {
			                $.fn.refreshCoordInputs ( this.getLatLng(), 'cmm_post_meta' );
			        });

           		// moves the marker to the label position
			        CM.Event.addListener(label, 'drag', function() {
			                marker.setLatLng( this.getLatLng() );
			        });
			        //  look for dragging the label and fires wenn drag ends
			        CM.Event.addListener(label, 'dragend', function() {
			                $.fn.refreshCoordInputs ( this.getLatLng(), 'cmm_post_meta' );
			        });
			        
        
              if ( mContent != '' && typeof mContent != 'undefined' ) {
        
                  // Bind content to the infowindow of Marker       
                  marker.bindInfoWindow(mContent, { pixelOffset: new CM.Size(hIconAnchor,-parseInt(mIconH)) } );
                  
                  // open the infoWindow onload, if it's only one marker
                  if ( markerArray.length == 1 ) {
                  CM.Event.fire(marker, 'click');
                  }
/*
                  // show infoWindow of last or hash-called post
                  if (typeof hash == 'undefined' ) {             
                      // show infoWindow of latest post
                      CM.Event.fire(clmarkers[0], 'click');
                  } else if ( typeof clickable === false ) {
                      // do nothing, it's our new marker 
                  } else {
                      // show infoWindow of hash-called post
                      if ( $.fn.getArrayIndexOfHashedPost(PostArray, hash) ) {
                          var key = $.fn.getArrayIndexOfHashedPost(PostArray, hash);
                          CM.Event.fire(clmarkers[key], 'click');
                      // if the hash do not match any post, show the last one
                      } else {
                          CM.Event.fire(clmarkers[0], 'click');
                      }  
                  }
*/

									// Bind infowindow to the label of this Marker            
                  if ( mLabel == 1 ) {
                  		label.bindInfoWindow(mContent, { pixelOffset: new CM.Size(hIconAnchor, -parseInt(mIconH)) } );
                  }        
              }
        
        } else {
        
              // Create marker with latitude, longtitude, title and icon
          	  cmMarkers.push( marker );
          	  
              if ( mLabel == 1 ) {     
              
                  // Create Label with latitude, longtitude and title
                  cmLabels.push( label );
                  
                  // @todo
                  //$('div.wml-labeled-marker-content').css('padding-left',parseInt(mIconW) );
              }   
              
              
              // Bind content to the infowindow of thisMarker
              if ( mContent != '' && typeof mContent != 'undefined' ) {    	  
                  
                  // Bind infowindow to this Marker
                  cmMarkers[i].bindInfoWindow(mContent, { pixelOffset: new CM.Size(hIconAnchor,-parseInt(mIconH)) } );
        
                  // Bind infowindow to the label of this Marker
                  if ( mLabel == 1 ) {
                  		cmLabels[i].bindInfoWindow(mContent, { pixelOffset: new CM.Size(hIconAnchor, -parseInt(mIconH)) } );
                  }
              } 
        }
    }  // end for loop 

    if ( typeof do_cluster == 'undefined' || do_cluster == false  ) {
    
        // do nothing
    
    } else {
    
        // create clustered markers 
        var cluster = new CM.MarkerClusterer(wp_cmm_map, {clusterRadius: clusterRadius});
        var labelcluster = new CM.MarkerClusterer(wp_cmm_map, {clusterRadius: clusterRadius});
        
        // change the cluster icons 
/*
        for ( var i=0; i<5; i++ ){
            CM.MarkerClusterer.ICONS[i].image = MyAjax.pluginDir + "images/markers/clustering/" + i + ".png";
        }
*/
    
        // add clustered markers and clustered labels to the map
        clusterer.addMarkers(cmMarkers);
        labelclusterer.addMarkers(cmLabels);
    }

}   // end up with addCloudMadeMapMarkers function



		/**
		 *  Render the map with given shortcode-attributes
		 *
		 *  @since  0.0.4
		 */
		 
    $.fn.CloudMadeMap = function( containerID, opts ) {

        $( 'body' ).addClass( 'ActiveCloudMadeMap' );
        $( '#' + containerID ).parents( '[id^=post-]' ).addClass( 's-ActiveCloudMadeMap' );
        
        $( "#" + containerID ).css('width', opts.width);
        $( "#" + containerID ).css('height', opts.height);

				// Init standard CloudMade Map setup stuff with unique style-Id from cloudmade.com
    	  if ( typeof cmm_base.ID != 'undefined' ) {
            var cloudmade = new CM.Tiles.CloudMade.Web({  key: cmm_base.key,
                                                          styleId:cmm_base.ID,
                                                        //  outOfRangeTileUrl:MyAjax.pluginDir + 'images/outOfRangeTile.gif', 
                                                          minZoomLevel:opts.minZoom,
                                                          maxZoomLevel:opts.maxZoom
                                                          });
        } else {
            var cloudmade = new CM.Tiles.CloudMade.Web({  key: cmm_base.key,
                                                        //  outOfRangeTileUrl:MyAjax.pluginDir + 'images/outOfRangeTile.gif', 
                                                          minZoomLevel:opts.minZoom,
                                                          maxZoomLevel:opts.maxZoom
                                                          });        
        }                                                      
    		
        var wp_cmm_map = new CM.Map( containerID , cloudmade);
    		
        // Center the map to a standard position
    	  wp_cmm_map.setCenter( new CM.LatLng( opts.lat, opts.lng), opts.zoom );
        
    		// Add map controls
        switch ( opts.zoomControl ) {
          	case "L":
							var largecontrol  = new CM.LargeMapControl();
							largecontrol.zoomLabelConfig = {
							        1: cmm_base.Region,
							        5: cmm_base.County,
							        9: cmm_base.Country,
							        13: cmm_base.Neighborhood,
							        17: cmm_base.Building
							};
	            wp_cmm_map.addControl( largecontrol );
	            break;
	            
          case "S":
	            wp_cmm_map.addControl( new CM.SmallMapControl() );
	            break;
	            
          default:
            	break;
        }

        // translate basic controls
				$('.wml-button-zoom-in').attr('title', cmm_base.zoomIn);
				$('.wml-button-zoom-out').attr('title', cmm_base.zoomOut);

          
        // needed for "show on map link"
        // http://localhost/standard/allgemein/cmm-active-test?lat=48.7577782&lng=9.2556105&zoom=16
        //wp_cmm_map.addControl( new CM.PermalinkControl() );
        

        // Add scale control
        if ( opts.scale == 1 ) {
            wp_cmm_map.addControl( new CM.ScaleControl() );
        }

        // Add minimap control
        if ( opts.minimap == 1 ) {
            wp_cmm_map.addControl( new CM.OverviewMapControl() );
        }

				// move copyright info to the preferred html-element, #id or .class
				if ( opts.copyrightElement != 'undefined' && $( opts.copyrightElement ).length ) {
						var origCopyrightNote = $('.wml-copyright-text').detach();
						if ( ! $( opts.copyrightElement ).is( ":contains('" + origCopyrightNote.text() + "')" ) ) {
								$( opts.copyrightElement ).append(' ');
						    origCopyrightNote.contents().appendTo( opts.copyrightElement );
				    }
		    }
		    
        // Add fullscreen control
        if ( opts.fullscreen == 1 ) {

						// build html
						var fs_control = $('<div class="wml-control wml-map-control fullscreen-control"><div class="wml-map-control-bottom"></div></div>');
						var fs_enable = $('<a href="" title="'+ cmm_base.enable_fullscreen +'" class="wml-button-enable-fullscreen wml-button-fullscreen"></a>');
						var fs_disable = $('<a href="" title="'+ cmm_base.disable_fullscreen +'" class="wml-button-disable-fullscreen wml-button-fullscreen"></a>').hide();
						
						// append html to control
						$( fs_control ).appendTo('#' + containerID + '>.wml-container');
						$( fs_enable ).appendTo('#' + containerID + ' .fullscreen-control');
						$( fs_disable ).appendTo('#' + containerID + ' .fullscreen-control');
						
						// enable fullscreen
						$( fs_enable ).click( function( e ) {
						    e.preventDefault();

								$('#'+containerID).css({ position: 'fixed', 'z-index': 100 });

								var currentCenter = wp_cmm_map.getCenter();

								$('#'+containerID).animate({
                    height: $(window).height(),
                    width: $(window).width(),
                    top: 0,
                    left: 0,
								}, {
								    duration: 2000,
								    complete: function() {
										    $( fs_enable ).hide();
										    $( fs_disable ).show();
												wp_cmm_map.checkResize();
												wp_cmm_map.setCenter( currentCenter, wp_cmm_map.getZoom() );
								    }
								});
						});
						
						// disable fullscreen
						$( fs_disable ).click( function( e ) {
						    e.preventDefault();
								$('#'+containerID).css({ position: 'relative', 'z-index': 0 });

								var currentCenter = wp_cmm_map.getCenter();
								
								$('#'+containerID).animate({
                    height: opts.height,
                    width: opts.width,
								}, {
								    duration: 500,
								    complete: function() {
										    $( fs_disable ).hide();
										    $( fs_enable ).show();
												wp_cmm_map.checkResize();
												wp_cmm_map.setCenter( currentCenter, wp_cmm_map.getZoom() );

								    }
								});
						});
        }



				if ( $('body.wp-admin').is('.post-new-php, .post-php') ) {

				/**
				 *
				 *
				 *
				 */
				$("#cmm_chose_location").mouseenter(function() {
						CM.Event.addListener(wp_cmm_map, 'click', function( latlng ) {
								wp_cmm_map._overlays[0].setLatLng( latlng );
								wp_cmm_map._overlays[1].setLatLng( latlng );
								$.fn.refreshCoordInputs ( latlng, 'cmm_post_meta' );
						});
				});
				}
				
				
				
        // Add Markers to the map
        $.fn.addCloudMadeMapMarkers (containerID, wp_cmm_map, opts.marker );
    }


		/**
		 *  Iterate over each div with relevant div.class and call map creation-fn
		 *
		 *  @since  0.0.4
		 */
		$('.cmm-active-single-map-wrap, .cmm-active-group-map-wrap').each(function( ) {
				var cid = $(this).attr("id");
				var id = 'CMM_' + $(this).attr("id").match(/[\d]+[_][\d]+$/);

				$.fn.CloudMadeMap ( cid, window[id] );

		});
		
		
}); // end jQuery      