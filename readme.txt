=== WP Cloudmade Maps ===
Contributors:   carstenbach
Donate link:        https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=XHR4SXESC9RJ6
Tags:               geo, geocoding, location, cloudmade, map, osm, openstreetmap, shortcode, tinymcebutton, GUI, widget
Author URI:         http://carsten-bach.de
Author:             Carsten Bach
Requires at least:  3.1
Tested up to:       3.4.2
Stable tag:         0.0.8

Add static and interactive cloudmade maps to your website, using a widget, different shortcodes and a tinymce GUI for user-friendly map-embedding.



== Description ==

With WP Cloudmade Maps you are able to add static and interactive maps to your website using Cloudmades designable OpenStreetMaps-Data.
This way you can add maps that fits your webdesign. Choose from over 50.000 [ready-to-use map styles](http://maps.cloudmade.com/editor "Have a look at the style editor and find your style by color or tag") or create your own with the CloudMade Style Editor.

The Plugin comes with

* a widget to show your last geotagged posts with static maps,
* a shortcode for static maps
* two shortcodes for interactive maps

and countless attributes, to fit the maps your ideas.

All shortcodes are accessible via an user-friendly tinymce button and a lightweight configuration interface for editors.
As Administrator you're able to set everything as defaults, so your editor just have to 'click & drop' a new map inside a post, page or a custom_post_type at all.

= General - Features =

* add maps that go nicely with your webdesign, by choosing from over 50.000 map-styles or painting your own
* works with **posts, pages and custom posttypes**
* adds **microformat geo-markup** to your located content, to make your geo-content machine-readable
* adds **meta-tags with location information** to your html output, to make your website readable to geo-DBs
* ability to **enable or disable unused parts** of this plugin, to keep it editor-friendly
* upload **custom marker images** into the WordPress media-library and use it on your maps
* check all your default settings with **example-maps inside the settings pages** directly
* **inline documentation** inside the WordPress help-tabs
* complete **deactivation and uninstalling routines** to keep your options-table clean
* **JS- and CSS files are loaded conditionally** only when they are needed


= Static Maps - Features =

* easy embed **static map images** into your content using GUI or shortcode
* align maps with the default **WordPress alignment CSS-classes**
* add maps as **background-images** to the *body*-tag, as *header-images* or anywhere you want
* show posts addresses as caption of the maps with WordPress default caption markup


= Active Maps for one Marker - Features =

* easy embed **active maps** into your content using GUI or shortcode
* align maps with the default **WordPress alignment CSS-classes**
* add zoom controls
* show map scale
* navigate within a small overview-map
* add descriptive labels to your map-markers


= Active Maps for multiple Marker - Features =

This part exists, but is still in development and not really production-ready. So, it is a small window into the next releases.

* show one active map with multiple Markers, standing for posts of
	* selectable categories,
	* choosen tags,
	* specific users,
	* defined date-ranges,
	* specific post-types,
	* or anything else you could pick up and filter with a [WP_Query request](http://codex.wordpress.org/Class_Reference/WP_Query "See the codex to get an idea of the possibilities") - this is the higher vision ;)
* attach Info-Window to each marker and show `the_content()`, `the_excerpt()` or own `html` based on a templatefile


= Languages =

* English (en_US)
* German (de_DE)


== Installation ==

1.  Extract the zip file
2.  Drop the contents in the wp-content/plugins/ directory of your WordPress installation
3.  Activate the Plugin from Plugins page
4.  Enter your personal [cloudmade API key](http://developers.cloudmade.com/projects/show/web-maps-studio "Get your personal cloudmade API key here for free")
5.  and a [flickr.places API key](http://www.flickr.com/services/apps/create/apply/ "Get your flickr.places API key here for free") (optionally, but useful to have reverse-geocoding features enabled)
6.  Add maps to your content via the tinyMCE GUI or place
  	* `<?php do_shortcode('[cmm_static]'); ?>`,
  	* `<?php do_shortcode('[cmm_active_single]'); ?>` or
  	* `<?php do_shortcode('[cmm_active_group]'); ?>` in your templates


== Upgrade Notice ==
There a no upgrade issues at the moment ;)


== Frequently Asked Questions ==
In the moment, there is no question I know about.

Maybe you've some?!
Drop me a line at wp-cloudmade-maps@carsten-bach.de


== Screenshots ==
1. GUI for embedding the shortcodes user-friendly
2. General Settings Screen
3. Settings Screen for Static maps options
4. Settings Screen for Active maps options
5. "Choose Location" metabox on the Edit Screen
6. Map inserted in post as "user-friendly" shortcode


== Changelog ==
= 0.0.8 ( September 2012 ) =
*  moved to GitHub for easier development and issue tracking
*  tested with WordPress 3.4.2
*  updated readme.txt
*   **fix**: Adjusted CSS for "Choose Location" meta_box
*   **fix**: Enqueued jQuery properly to make meta_box Map work correct and show TinyMCE GUI with tabs 
*   


= 0.0.7 ( January 2012 ) =
*   **fix**: JS validation: if inserted a map, but no location, is triggered now only if map shortcode is really inserted
*   **add**: update or delete existing maps within the GUI
*   **fix**: moved tinyMCE language strings into .po / .mo files
*   **fix**: Fatal Error "Unsupported operand types" during activation
* 	**add**: fullscreen switcher to active maps
*   **fix**: also delete widget-options on de-activation
*   **fix**: fixed the links to the option-pages inside the Admin-Notices


= 0.0.6 ( January 2012 ) =

*   **add**: optimized the usability of the GUI for mutual dependence of some options
*   **add**: alternate marker-title to marker of interactive single maps using the GUI
*   **fix**: CSS improvements for the GUI
*   **fix**: wrong jQuery fallback path
*   **add**: filter markers on active-group-maps by post_type
*   **add**: choose, via the GUI, what content to show in infoWindow attached to markers on active_group_maps: nothing, `the_excerpt()`, `the_content()` or to use an own templatefile
*   **add**: sample template-file for infoWindow output, ready to modify and use in your theme
*   **fix**: updated the help-section for static_maps and active_single_maps
*   **fix**: updated the german translation
*   **add**: pot-file


= 0.0.5 ( December 2011 ) =

*   first public release



== Arbitrary section ==
All shortcodes and its attributes are described and documented inside the WordPress "help"-tab on the upper right corner of each settings page. Have a look over there if you want to use the shortcodes in your theme files.

If you are having a feature request or bug-reports for this plugin, [open an issue](https://github.com/carstingaxion/wp-cloudmade-maps/issues/new) on github.com.
If there is nothing from you, I'll go on doing one of the following:

= roadmap =

* update the help-section inside the help-tab for the enclosing variant of [cmm_active_single] and for [cmm_active_group]
* upgrading to CloudMades new "leaflet" library, when it supports the cloudmade style-ID for different map styles
* give the ability to define horizontal- and vertical-anchor for larger Marker Icons
* add an option to decide, whether to output xhtml or html markup
* add geo:RSS markup to feeds
* add an error message for non-existing default latitude and longitude in general settings
* add the ability to show the active maps with a caption of the address
* add a "show on map link", to jump directly to a specific point
* upload an "out of range" tile image
* upload an own loading image
* enable / disable Marker clustering for active-group-maps
* upload own cluster images
* add routing between two points, your posts position and your site-visitors position for example
* remove PHP notices and warnings from the widget code
* setup a demo page with example maps
* GUI bugfix: pre-selected checkboxes could't be de-selected by inserting via GUI