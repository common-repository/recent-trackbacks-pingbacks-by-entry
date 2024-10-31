<?php
/*
Plugin Name: Recent Pingbacks by Entry Widget
Plugin URI: http://www.vjcatkick.com/?page_id=4588
Description: Another style listing recent  pingback.
Version: 0.0.6
Author: V.J.Catkick
Author URI: http://www.vjcatkick.com/
*/

/*
License: GPL
Compatibility: WordPress 2.6 with Widget-plugin.

Installation:
Place the widget_single_photo folder in your /wp-content/plugins/ directory
and activate through the administration panel, and then go to the widget panel and
drag it to where you would like to have it!
*/

/*  Copyright V.J.Catkick - http://www.vjcatkick.com/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


/* Changelog
* Wed Dec 24 2008 - v0.0.1
- Initial release
* Sat Dec 27 2008 - v0.0.2
- svn version
* Mon Dec 29 2008 - v0.0.3
- compatibility bug fix
* TUE Dec 30 2008 - v0.0.4
- compatibility bug fix
* Jan 28 2009 - v0.0.5
- logic change (trackback)
* Jan 30 2009 - v0.0.6
- bug fix: SQL statement
*/

if ( !function_exists('kjgrc_parse_pingback_vjck') ) :
// parsing pingback function by Krischan Jodies http://blog.jodies.de
function kjgrc_parse_pingback_vjck($pingback_author)
{
	$workstring = trim($pingback_author);
	/* most common syntax
	1. author &raquo; title
	2. author &raquo; category &raquo; title
	3. title at author
	4. title - author (too insignificant)
	5. [&raquo;] title &laquo; author
	*/
	$first_delimiter = strpos($workstring,'&raquo;');
	while ($first_delimiter !== false && $first_delimiter == 0) {
		$workstring = trim(substr($workstring,7));
		$first_delimiter = strpos($workstring,'&raquo;');
	}
	if ($first_delimiter !== false) {
		$comment_author = substr($workstring,0,$first_delimiter-1);
		$workstring = trim(substr($workstring,$first_delimiter+7));
		$first_delimiter = strpos($workstring,'&raquo;');
		if ($first_delimiter !== false) {
			$workstring = trim(substr($workstring,$first_delimiter+7));
		}
		return array($comment_author,$workstring);
	}
	foreach (array(' at ','&laquo;',' - ',' auf ',' by ',' // ',' | ',' : ',' @ ',' / ') as $delimiter)
	{
		$first_delimiter = strpos($workstring,$delimiter);
		if ($first_delimiter !== false) {
			$trackback_title = trim(substr($workstring,0,$first_delimiter));
			$comment_author = trim(substr($workstring,$first_delimiter+strlen($delimiter)));
			// kjgrc_log("delimiter match [$delimiter]: $workstring -> a: '$comment_author' t: '$trackback_title' ");
			return array($comment_author,$trackback_title);
		}
	}
	// $comment_author = 'Unknown';
	$comment_author = $pingback_author;
	$trackback_title = '';
	return array($comment_author,$trackback_title);
} /* kjgrc_parse_pingback_vjck () */
endif;

function widget_recent_pingbacks_by_entry_init() {
	if ( !function_exists('register_sidebar_widget') )
		return;

	function widget_recent_pingbacks_by_entry( $args ) {
		extract($args);

		$options = get_option('widget_recent_pingbacks_by_entry');
		$title = $options['rpgbet_src_title'];
		$rpgbet_dst_max_entry =  $options['rpgbet_dst_max_entry'];
//		$rpgbet_dst_max_comments =  $options['rpgbet_dst_max_comments'];

		$output = '<div id="widget_recent_pingbacks_by_entry"><ul>';

		// section main logic from here 

	$myCRLF = "<br />";
	global $wpdb;
	$queryStr = "
		SELECT * FROM $wpdb->comments 
		WHERE $wpdb->comments.comment_approved = '1'
		AND $wpdb->comments.comment_type = 'pingback'
		ORDER BY $wpdb->comments.comment_date_gmt DESC 
		LIMIT " . $rpgbet_dst_max_entry;
	$myResults = $wpdb->get_results( $queryStr, ARRAY_A );

	foreach( $myResults as $myResult ) {
			$thePost = wp_get_single_post( $myResult[comment_post_ID] , ARRAY_A);
			$theOutput =  '<li><a href="' . $thePost[ guid ] . '">' . $thePost[ post_title ] . '</a></li>';
//			if( $isIEorNot ) { $theOutput .= '<br />'; };
			$output  .= $theOutput;

			$output .=  "<div style='font-size:0.9em;margin-left:10px;text-align:left;'>";
			list($com_author,$trackback_title) = kjgrc_parse_pingback_vjck( $myResult[ comment_author ]  );
			$output .= $trackback_title . $myCRLF;
			$output .= ('<a href="' . $myResult[ comment_author_url ]  . '" target="_blank" >' . $com_author . '</a></div>' );

	} /* foreach */

		// These lines generate the output

		$output .= '</ul></div>';

		echo $before_widget . $before_title . $title . $after_title;
		echo $output;
		echo $after_widget;
	} /* widget_recent_pingbacks_by_entry() */

	function widget_recent_pingbacks_by_entry_control() {
		$options = $newoptions = get_option('widget_recent_pingbacks_by_entry');
		if ( $_POST["rpgbet_src_submit"] ) {
			$newoptions['rpgbet_src_title'] = strip_tags(stripslashes($_POST["rpgbet_src_title"]));
			$newoptions['rpgbet_dst_max_entry'] = (int) $_POST["rpgbet_dst_max_entry"];
//			$newoptions['rpgbet_dst_max_comments'] = (int) $_POST["rpgbet_dst_max_comments"];
		}
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('widget_recent_pingbacks_by_entry', $options);
		}

		// those are default value
		if ( !$options['rpgbet_dst_max_entry'] ) $options['rpgbet_dst_max_entry'] = 5;
//		if ( !$options['rpgbet_dst_max_comments'] ) $options['rpgbet_dst_max_comments'] = 5;

		$rpgbet_dst_max_entry = $options['rpgbet_dst_max_entry'];
//		$rpgbet_dst_max_comments = $options['rpgbet_dst_max_comments'];

		$title = htmlspecialchars($options['rpgbet_src_title'], ENT_QUOTES);
?>

	    <?php _e('Title:'); ?> <input style="width: 170px;" id="rpgbet_src_title" name="rpgbet_src_title" type="text" value="<?php echo $title; ?>" /><br />

        <?php _e('Max Entry:'); ?> <input style="width: 75px;" id="rpgbet_dst_max_entry" name="rpgbet_dst_max_entry" type="text" value="<?php echo $rpgbet_dst_max_entry; ?>" /> (max 10)<br />
<!--
        <?php _e('Max Comments:'); ?> <input style="width: 75px;" id="rpgbet_dst_max_comments" name="rpgbet_dst_max_comments" type="text" value="<?php echo $rpgbet_dst_max_comments; ?>" /> (max 10)<br />
-->

  	    <input type="hidden" id="tbr_src_submit" name="rpgbet_src_submit" value="1" />

<?php
	} /* widget_recent_pingbacks_by_entry_control() */

	// This registers our widget so it appears with the other available
	// widgets and can be dragged and dropped into any active sidebars.
	register_sidebar_widget('Recent Pingbacks by Entry', 'widget_recent_pingbacks_by_entry');
	register_widget_control('Recent Pingbacks by Entry', 'widget_recent_pingbacks_by_entry_control' );
} /* widget_recent_pingbacks_by_entry_init() */

// Run our code later in case this loads prior to any required plugins.
add_action('plugins_loaded', 'widget_recent_pingbacks_by_entry_init');

?>