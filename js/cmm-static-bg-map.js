jQuery(document).ready(function ($) {
  
    /**
     *  Build API query to get staic map from cloudmade as a background-img for given elment
     *
     *  @since  0.0.1
     */
    $.fn.staticCloudMadeMap = function ( zoom, lat, lng, marker, element ) {
        
        $('body').addClass('StaticCloudMadeMap');

        var statMapUrl  = "http://staticmaps.cloudmade.com/" + cmm_base.key + "/staticmap?";
        var statMap         = [];
        if ( typeof cmm_base.ID != 'undefined') {
        statMap["styleid"]  = cmm_base.ID;
        }
        statMap["center"]   = lat + "," + lng;
        statMap["zoom"]     = zoom;
        statMap["marker"]   = "url:"+ marker + "|" + lat + "," + lng;        
        statMap["size"]     = $(element).width() + "x" + $(element).height();
        statMap["format"]   = "jpg";
    
        var mapParams = "";
        for (var i in statMap) {
           mapParams += i + "=" + statMap[i] + "&";
        }
        
        $(element).css('background',"url(" + statMapUrl + mapParams + ") 0 0 no-repeat fixed");      
    
    }

		/**
		 *  Iterate over each div with relevant div.class and call map creation-fn
		 *
		 *  @since  0.0.4
		 */
		$('.cmm-static-bg-map-wrap').each(function( i ) {
				var id = 'CMM_' + $(this).attr("id").match(/[\d]+[_][\d]+$/);

		    $.fn.staticCloudMadeMap (
		    		window[id].zoom,
		    		window[id].lat,
						window[id].lng,
						window[id].marker,
						window[id].bg_element
				) ;

		    $(window).resize(function() {
				    $.fn.staticCloudMadeMap (
				    		window[id].zoom,
				    		window[id].lat,
								window[id].lng,
								window[id].marker,
								window[id].bg_element
						) ;
		    });

		});

}); // end jQuery      