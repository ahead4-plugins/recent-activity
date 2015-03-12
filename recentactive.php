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

//[foobar]
function ah4latest_func( $atts ){

  $a = shortcode_atts( array(
    'title' => 'Latest Activity',
    'post_type' => 'post',
    'number' => 10,
  ), $atts );


  $retval = '';

  $ah4_shortcoder = new ah4_latest_activity;

  $retval .= $ah4_shortcoder->handle_shortcode($a);

  return '<h2>' . $a['title'] . '</h2>' . $retval;

}

add_shortcode( 'ah4latest', 'ah4latest_func' );

// create a class to handle the shortcodes
class ah4_latest_activity
{

  protected $query_params;

  // Class properties and methods go here
  public function handle_shortcode($a){

    $this->query_params = $a;

    $retval = $this->get_by_comments();

    return $retval;
  }

  public function get_by_comments(){
    global $wpdb;

    $show_limit = $this->query_params['number'];

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
    where post_type = 'post'
    and post_status = 'publish' 
    order by mcomment_date desc
    limit $show_limit
    ";

    $pageposts = $wpdb->get_results($querystr, OBJECT);

    $retval = '';

    if ($pageposts) {

      foreach ($pageposts as $orderedpost) {
        $retval .= $orderedpost->post_title . "<br>\n";
      }
    }

    return $retval;
  }
}