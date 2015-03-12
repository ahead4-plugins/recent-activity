<?php
/*
  Plugin Name: Most recently active posts.
  Plugin URI: http://www.ahead4.biz/plugins/
  Description: Displays posts or custom post types by latest comment or published if no comments
  Version: 1.0
  Author: Andrew Fielden
  Author URI: http://andrew.ahead4.biz/
  License: GPL2
 */

function ah4latest_func( $atts ){

  $a = shortcode_atts( array(
    'title' => '<h2>Latest Activity</h2>',
    'post_type' => 'post',
    'number' => 10,
    'before_list' => '',
    'after_list' => '',
    'before_item' => '',
    'after_item' => '<br>',
  ), $atts );


  $retval = $a['title'];

  $ah4_shortcoder = new ah4_latest_activity;

  //$retval .= $ah4_shortcoder->handle_shortcode($a);
  $post_items = $ah4_shortcoder->handle_shortcode($a);

  //$retval = implode("<br>", $post_items);

  if ($post_items) {

      $retval .= $a['before_list'];

      foreach ($post_items as $orderedpost) {


        $list_item = $a['before_item'] . '<a href="' . get_permalink($orderedpost->ID) . 
          '" >' . $orderedpost->post_title . '</a>' . $a['after_item'] . "\n";

        // allow filters
        if ( has_filter('ah4_recent_activity')){
          $retval .= apply_filters('ah4_recent_activity', $list_item, $orderedpost->ID);
        } else {
           $retval .= $list_item;
        }


      }

      $retval .= $a['after_list'];
    }

  return $retval;

}

add_shortcode( 'ah4latest', 'ah4latest_func' );

// create a class to handle the shortcodes
class ah4_latest_activity
{

  // store the query params with defaults
  protected $query_params = array(
                              'post_type' => 'post',
                              'number' => 10,
                            );


  // Allow updating the post type with sanitisation
  public function set_post_type($post_type){

    if ( post_type_exists($a['post_type'])) {
      // update to needed type and return true
      $this->query_params['post_type'] = $post_type;
      return true;
    } else {
      // duff post type setting - do nothing and return false
      return false;
    }

  }

  
  // Allow updating the return number of posts with sanitisation
  public function set_return_number($number){

    $this->query_params['number'] = $number;

  }
  

  

  // Class properties and methods go here
  public function handle_shortcode($a){

    // sanitise the query params

    // first check the post types which MUST be post, pages or a registered CPT

     if ( post_type_exists($a['post_type']) ) {
      $this->query_params['post_type'] = $a['post_type'];
     }

    // The number must be a number

    $a['number'] = (int) $a['number'];

    $this->query_params['number'] = $a['number'];

    // Get the list

    $retval = $this->get_by_comments();

    return $retval;
  }

  public function get_by_comments(){
    global $wpdb;

    $show_limit = $this->query_params['number'];

    $post_type = $this->query_params['post_type'];

    $querystr = "
    select wp_posts.*,
    coalesce(
        (
            select max(comment_date)
            from $wpdb->comments wpc
            where wpc.comment_post_id = wp_posts.id
        ),
        wp_posts.post_date
    ) as mcomment_date
    from $wpdb->posts wp_posts
    where post_type = '$post_type'
    and post_status = 'publish' 
    order by mcomment_date desc
    limit $show_limit
    ";

    $pageposts = $wpdb->get_results($querystr, OBJECT);

    return $pageposts;

  } // function get_by_comments()

}