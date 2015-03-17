<?php
/*
  Plugin Name: Most recently active posts
  Plugin URI: http://www.ahead4.biz/plugins/
  Description: Displays posts or custom post types by latest comment or published if no comments.
  Version: 1.0
  Author: Andrew Fielden
  Author URI: http://andrew.ahead4.biz/
  License: GPL2
 */

function recent_activity_func( $atts ){

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
        if ( has_filter('recent_activity')){
          $retval .= apply_filters('recent_activity', $list_item, $orderedpost);
        } else {
           $retval .= $list_item;
        }


      }

      $retval .= $a['after_list'];
    }

  return $retval;

}

add_shortcode( 'recent_activity', 'recent_activity_func' );

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

    $this->query_params['post_type'] = $post_type;

    return true;

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

} // class ah4_latest_activity ends

/***********************************************************
/*
/* The widget - reckon this could be in a different file
/*
/**********************************************************/

// Creating the widget 
class ah4_recent_activity_widget extends WP_Widget 
{

  function __construct() {
    parent::__construct(
      // Base ID of your widget
      'ah4_recent_activity', 

      // Widget name will appear in UI
      __('Recent Activity', 'ah4_recentactive_domain'), 

      // Widget description
      array( 'description' => __( 'Displays posts or custom post types by latest comment or published if no comments.', 'ah4_recentactive_domain' ), ) 
    );
  }

  // Creating widget front-end
  // This is where the action happens
  public function widget( $args, $instance ) {
    $title = apply_filters( 'ah4_ra_widget_title', $instance['title'] );
    // before and after widget arguments are defined by themes
    echo $args['before_widget'];
    if ( ! empty( $title ) )
    echo $args['before_title'] . $title . $args['after_title'];

    // This is where you run the code and display the output

    $ptype = $instance['ptype'];

    $list_number = $instance['list_number'];

    $ah4_ra = new ah4_latest_activity;

    $ah4_ra->set_post_type($ptype);

    $ah4_ra->set_return_number($list_number);

    $post_items = $ah4_ra->get_by_comments();


    if ($post_items) {

      foreach ($post_items as $orderedpost) {


        echo  '<a href="' . get_permalink($orderedpost->ID) . 
          '" >' . $orderedpost->post_title . "</a><br>\n";


      }
      
    }

    echo $args['after_widget'];

  }
      
  // Widget Backend 
  public function form( $instance ) {
    if ( isset( $instance[ 'title' ] ) ) {
      $title = $instance[ 'title' ];
    } else {
    $title = __( 'New title', 'ah4_recentactive_domain' );
    }

    if ( isset( $instance[ 'list_number' ] ) ) {
      $list_number = $instance['list_number'];
    } else {
      $list_number = '5';
    }

    // get and set the post type
    if ( isset( $instance[ 'ptype']) ){
      $post_type = $instance['ptype'];
    } else {
      $post_type = 'post';
    }


    // Widget admin form
    ?>
    <p>
    <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
    <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
    </p>
    
     <p>
      <label for="<?php echo $this->get_field_id('text'); ?>">Post Type: 
        <select class='widefat' id="<?php echo $this->get_field_id('ptype'); ?>"
                name="<?php echo $this->get_field_name('ptype'); ?>" type="text">
          <?php
          // build the post types

          $args = array(
             'public'   => true,
          );

          $output = 'names'; // names or objects, note names is the default
          $operator = 'and'; // 'and' or 'or'

          $post_types = get_post_types( $args, $output, $operator ); 

          //$retval = '<select name="' . $field_id . '" id="' . $field_id . '">';

          foreach ( $post_types  as $type ) {

            if ( $type != 'attachment') {

               echo '<option value="' . $type . '"';

               
               if ($type == $post_type) {
                 echo ' selected';
               }
               

               echo '>' . $type . '</option>';
            }
          }

          ?>
          
        </select>                
      </label>
     </p>



    <p>
    <label for="<?php echo $this->get_field_id( 'list_number' ); ?>"><?php _e( 'Number to show:' ); ?></label> 
    <input class="widefat" id="<?php echo $this->get_field_id( 'list_number' ); ?>" name="<?php echo $this->get_field_name( 'list_number' ); ?>" type="text" value="<?php echo esc_attr( $list_number ); ?>" />
    </p>
    
    <?php 
  }
    
  // Updating widget replacing old instances with new
  public function update( $new_instance, $old_instance ) {
    $instance = array();
    $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
    $instance['list_number'] = ( ! empty( $new_instance['list_number'] ) ) ? strip_tags( $new_instance['list_number'] ) : '';
    $instance['ptype'] = $new_instance['ptype'];
    return $instance;
  }

  // custom function to create a dropdown of public post types with a current selection
  public function gen_post_type_dropdown($current_sel, $field_id){
    $args = array(
       'public'   => true,
       //'_builtin' => false
    );

    $output = 'names'; // names or objects, note names is the default
    $operator = 'and'; // 'and' or 'or'

    $post_types = get_post_types( $args, $output, $operator ); 

    $retval = '<select name="' . $field_id . '" id="' . $field_id . '">';

    foreach ( $post_types  as $post_type ) {

      if (! $post_type == 'attachment'){

         $retval .= '<option value="' . $post_type . '"';

         
         if ($current_sel == $post_type) {
           $retval .= ' selected';
         }
         

         $retval .= '>' . $post_type . '</option>';
      }
    }

    $retval .= '<option>mmm</option>';


    $retval .= '</select>';

    return $retval;

  }

} // Class wpb_widget ends here

// Register and load the widget
function ah4_ra_load_widget() {
  register_widget( 'ah4_recent_activity_widget' );
}
add_action( 'widgets_init', 'ah4_ra_load_widget' );