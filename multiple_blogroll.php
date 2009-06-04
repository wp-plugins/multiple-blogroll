<?
/*
Plugin Name: Multiple Blogroll
Plugin URI: http://www.zamana.eti.br/blog/2008/12/multiple-blogroll-wordpress-plugin/
Description: With this you can put more than 1 blogroll widget, and separate with the categories
Author: RZamana
Version: 1.2
Author URI: http://zamana.eti.br
*/
//Front-end shows
function wp_widget_multiple_blogroll( $args, $widget_args = 1 ) {
  // Let's begin our widget.
  extract( $args, EXTR_SKIP );
  // Our widgets are stored with a numeric ID, process them as such
  if ( is_numeric($widget_args) )
    $widget_args = array( 'number' => $widget_args );
  // We'll need to get our widget data by offsetting for the default widget
  $widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
  // Offset for this widget
  extract( $widget_args, EXTR_SKIP );
  // We'll get the options and then specific options for our widget further below
  $options = get_option('multiple_blogroll');
  // If we don't have the widget by its ID, then what are we doing?
  if ( !isset($options[$number]) )
    return;
  // We'll use the standard filters from widgets.php for consistency
  $mtbr_title = apply_filters( 'widget_title', $options[$number]['title'] );
  $mtbr_limit_cat = "'".implode("','",explode(',',$options[$number]['category']))."'";

  global $wpdb;
  if ($mtbr_limit_cat){
    $querystr = "SELECT DISTINCT link_url, name, link_name, link_target, link_image, link_description, link_rel FROM $wpdb->links INNER JOIN ($wpdb->term_relationships INNER JOIN( $wpdb->terms INNER JOIN $wpdb->term_taxonomy ON $wpdb->terms.term_id=$wpdb->term_taxonomy.term_id) ON $wpdb->term_taxonomy.term_taxonomy_id=$wpdb->term_relationships.term_taxonomy_id)ON $wpdb->links.link_id=$wpdb->term_relationships.object_id WHERE $wpdb->term_taxonomy.taxonomy='link_category' AND $wpdb->links.link_visible = 'Y' AND $wpdb->terms.name IN ($mtbr_limit_cat) ORDER BY name ASC";
  }else{
    $querystr = "SELECT DISTINCT link_url, name, link_name, link_target, link_image, link_description, link_rel FROM $wpdb->links INNER JOIN ($wpdb->term_relationships INNER JOIN( $wpdb->terms INNER JOIN $wpdb->term_taxonomy ON $wpdb->terms.term_id=$wpdb->term_taxonomy.term_id) ON $wpdb->term_taxonomy.term_taxonomy_id=$wpdb->term_relationships.term_taxonomy_id)ON $wpdb->links.link_id=$wpdb->term_relationships.object_id WHERE $wpdb->term_taxonomy.taxonomy='link_category' AND $wpdb->links.link_visible = 'Y' ORDER BY name ASC";
  }
  $mtbr_links = $wpdb->get_results($querystr, OBJECT);
  echo $before_widget . $before_title . $mtbr_title . $after_title;
  echo '<ul>';
  if (!empty($mtbr_links)) {
    foreach ($mtbr_links as $mtbr_link) {
      $mtbr_link_url = $mtbr_link->link_url;
      $mtbr_link_cat = $mtbr_link->name;
      $mtbr_link_name = $mtbr_link->link_name;
      $mtbr_link_desc = $mtbr_link->link_description;
      $mtbr_link_image = $mtbr_link->link_image;
      $mtbr_link_target = $mtbr_link->link_target;
      $mtbr_link_rel = $mtbr_link->link_rel;
      echo '<li><a';
      if ($mtbr_link_target){
        echo ' target="'.$mtbr_link_target.'"';}
      echo ' href="'.$mtbr_link_url.'" title="'.$mtbr_link_desc.'">';
      if (($mtbr_link_image))
        echo '<img src="'.$mtbr_link_image.'" alt="Click to visit '.$mtbr_link_name.'" /><br />';
      echo $mtbr_link_name;
      echo '</a>';
      echo '</li>';
    }
  } else echo "<li>No Blogroll Links</li>";
  echo '</ul>';
  echo $after_widget;
}
//Widget controls
function wp_widget_multiple_blogroll_control($widget_args) {
	// Establishes what widgets are registered, i.e., in use
	global $wp_registered_widgets;
	// We shouldn't update, i.e., process $_POST, if we haven't updated
	static $updated = false;
	// Our widgets are stored with a numeric ID, process them as such
	if ( is_numeric($widget_args) )
		$widget_args = array( 'number' => $widget_args );
	// We can process the data by numeric ID, offsetting for the '1' default
	$widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
	// Complete the offset with the widget data
	extract( $widget_args, EXTR_SKIP );
	// Get our widget options from the databse
	$options = get_option('multiple_blogroll');
	// If our array isn't empty, process the options as an array
	if ( !is_array($options) )
		$options = array();
	// If we haven't updated (a global variable) and there's no $_POST data, no need to run this
	if ( !$updated && !empty($_POST['sidebar']) ) {
		// If this is $_POST data submitted for a sidebar
		$sidebar = (string) $_POST['sidebar'];
		// Let's konw which sidebar we're dealing with so we know if that sidebar has our widget
		$sidebars_widgets = wp_get_sidebars_widgets();
		// Now we'll find its contents
		if ( isset($sidebars_widgets[$sidebar]) ) {
			$this_sidebar =& $sidebars_widgets[$sidebar];
		} else {
			$this_sidebar = array();
		}
		// We must store each widget by ID in the sidebar where it was saved
		foreach ( $this_sidebar as $_widget_id ) {
			// Process options only if from a Widgets submenu $_POST
			if ( 
        'wp_widget_multiple_blogroll' == $wp_registered_widgets[$_widget_id]['callback'] && 
        isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) 
      ) {
				// Set the array for the widget ID/options
				$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
				// If we have submitted empty data, don't store it in an array.
				if ( !in_array( "multiple-blogroll-$widget_number", $_POST['widget-id'] ) )
					unset($options[$widget_number]);
			}
		}
		// If we are returning data via $_POST for updated widget options, save for each widget by widget ID
		foreach ( (array) $_POST['widget-multiple-blogroll'] as $widget_number => $widget_multiple_blogroll ) {
			// If the $_POST data has values for our widget, we'll save them
      #print '<pre>'.print_r(array($options[$widget_number]),true).'</pre>';die();
			if (!isset($options[$widget_number]) )
				continue;
			// Create variables from $_POST data to save as array below
			$title = strip_tags(stripslashes($widget_multiple_blogroll['title']));
      $category = strip_tags(stripslashes($widget_multiple_blogroll['category']));
			// We're saving as an array, so save the options as such
			$options[$widget_number] = compact( 'title', 'category' );
		}
		// Update our options in the database
		update_option( 'multiple_blogroll', $options );
		// Now we have updated, let's set the variable to show the 'Saved' message
		$updated = true;
	}
	// Variables to return options in widget menu below; first, if
	if ( -1 == $number ) {
		$title      = '';
		$category   = '';
		$number     = '%i%';
	// Otherwise, this widget has stored options to return
	} else {
		$title      = attribute_escape($options[$number]['title']);
    $category   = attribute_escape($options[$number]['category']);
	}
	// Our actual widget options panel
?>
	<p>Put your blogroll in the place, just put the correct category name</p>
	<p>
		<label for="multiple-blogroll-title-<?php echo $number; ?>">Widget Title (optional):</label>
		<input id="multiple-blogroll-title-<?php echo $number; ?>" name="widget-multiple-blogroll[<?php echo $number; ?>][title]" class="widefat" type="text" value="<?php echo $title; ?>" />
	</p>
	<p>
		<label for="multiple-blogroll-category-<?php echo $number; ?>">Category Blogroll (required, comma separated):</label>
    <input id="multiple-blogroll-category-<?php echo $number; ?>" name="widget-multiple-blogroll[<?php echo $number; ?>][category]" class="widefat" type="text" value="<?php echo $category; ?>" />
		<input type="hidden" name="widget-multiple-blogroll[<?php echo $number; ?>][submit]" value="1" />
	</p>
<?php
	// And we're finished with our widget options panel
}
function wp_multiple_blogroll_init() {
	// Do we have options? If so, get info as array
	if ( !$options = get_option('multiple_blogroll') )
		$options = array();
	// Variables for our widget
	$widget_ops = array(
			'classname'   => 'multiple_blogroll',
			'description' => 'Display your blogroll separeted by categories'
		);
	// Variables for our widget options panel
	$control_ops = array(
			'width'   => 375,
			'height'  => 400,
			'id_base' => 'multiple-blogroll'
		);
	// Variable for out widget name
	$name = 'Multiple Blogroll';
	// Assume we have no widgets in play.
	$id = false;
	// Since we're dealing with multiple widgets, we much register each accordingly
	foreach ( array_keys($options) as $o ) {
		// Per Automattic: "Old widgets can have null values for some reason"
		if ( !isset($options[$o]['title']) || !isset($options[$o]['category']) )
			continue;
		// Automattic told me not to translate an ID. Ever.
		$id = "multiple-blogroll-$o"; // "Never never never translate an id" See?
		// Register the widget and then the widget options menu
		wp_register_sidebar_widget( $id, $name, 'wp_widget_multiple_blogroll', $widget_ops, array( 'number' => $o ) );
		wp_register_widget_control( $id, $name, 'wp_widget_multiple_blogroll_control', $control_ops, array( 'number' => $o ) );
	}
	// Create a generic widget if none are in use
	if ( !$id ) {
		// Register the widget and then the widget options menu
		wp_register_sidebar_widget( 'multiple-blogroll-1', $name, 'wp_widget_multiple_blogroll', $widget_ops, array( 'number' => -1 ) );
		wp_register_widget_control( 'multiple-blogroll-1', $name, 'wp_widget_multiple_blogroll_control', $control_ops, array( 'number' => -1 ) );
	}
}

function wp_multiple_blogroll_activation(){
  add_option('multiple_blogroll');
}
function wp_multiple_blogroll_deactivation(){
  delete_option('multiple_blogroll');
}
// Adds filters to custom field values to prettify like other content
add_filter( 'multiple_blogroll_value', 'convert_chars' );
add_filter( 'multiple_blogroll_value', 'stripslashes' );
add_filter( 'multiple_blogroll_value', 'wptexturize' );
// When activating/deactivating, run the appropriate function
register_activation_hook( __FILE__, 'wp_multiple_blogroll_activation' );
register_deactivation_hook( __FILE__, 'wp_multiple_blogroll_deactivation' );
// Initializes the function to make our widget(s) available
add_action( 'init', 'wp_multiple_blogroll_init' );
// The End. :P
?>
