<?php

// **************************** This just plops the file in here --- just for manageability breaking up physical files ******
require get_theme_file_path('/inc/like-route.php');
require get_theme_file_path('/inc/search-route.php');
// **************************************************************************************************************************


function university_custom_rest() {
  register_rest_field('post', 'authorName', array(
    'get_callback' => function() {return get_the_author();}
  ));

  register_rest_field('note', 'userNoteCount', array(
    'get_callback' => function() {return count_user_posts(get_current_user_id(), 'note');}
  ));
}

add_action('rest_api_init', 'university_custom_rest');

function pageBanner($args = NULL) {
  
  if (!$args['title']) {
    $args['title'] = get_the_title();
  }

  if (!$args['subtitle']) {
    $args['subtitle'] = get_field('page_banner_subtitle');
  }

  if (!$args['photo']) {
    if (get_field('page_banner_background_image')) {
      $args['photo'] = get_field('page_banner_background_image')['sizes']['pageBanner'];
    } else {
      $args['photo'] = get_theme_file_uri('/images/ocean.jpg');
    }
  }

  ?>
  <div class="page-banner">
    <div class="page-banner__bg-image" style="background-image: url(<?php echo $args['photo']; ?>);"></div>
    <div class="page-banner__content container container--narrow">
      <h1 class="page-banner__title"><?php echo $args['title'] ?></h1>
      <div class="page-banner__intro">
        <p><?php echo $args['subtitle']; ?></p>
      </div>
    </div>  
  </div>
<?php }

function university_files() {
  wp_enqueue_script('googleMap', '//maps.googleapis.com/maps/api/js?key=AIzaSyBh9b1rNCp6kOi5JeMHiRP4klDymBeoEWk', NULL, '1.0', true);
  
  // ************************* webpack can be used to bundle all .js files into a bundle called scripts-bundle.js
  wp_enqueue_script('main-university-js', get_theme_file_uri('/js/scripts-bundled.js'), NULL, '1.0', true);
  // ********************************************************************************************************************************
  
  wp_enqueue_style('custom-google-fonts', '//fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i');
  wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
  
// ************** This loads our theme folder's main stylesheet called style.css ****************************************************
  wp_enqueue_style('university_main_styles', get_stylesheet_uri());
  
  
// **************************** A global js variable called universityData is being created here that will contain as its value 
//                              the 3rd parameter. WP actually creates a CDATA section inside the rendered html
//                              Also, I think the variable will only be available to the js file whose handle is the 1st parameter
//                              So, in this example, this variable will only be avialble to scripts-bundled.js because they both 
//                              share the same 1st parameter
  wp_localize_script('main-university-js', 'universityData', array(
    'root_url' => get_site_url(),
    'nonce' => wp_create_nonce('wp_rest')         // Basically WP will create a session id which we HAVE TO pass back as an HTTP header
                                                  // header when doing CUD of CRUD Ajax requests ... 
  // ********************************************************************************************************************************
  ));
}

// ********** THIS EVENT only fires in the front end ... ie when our theme code is executing ... ie our website
//            it DOES NOT fire when the dashboard code (wp-admin is executing)
//            To load our js css and fonts when the Dashboard is active (meaning for example the WP's login form is up)
//            then we need to write event handler for the login_enqueue_scripts event as shown at the end of this file
add_action('wp_enqueue_scripts', 'university_files');

function university_features() {
  add_theme_support('title-tag');
  // *********************************************** So we can assign a featured image to each post
  add_theme_support('post-thumbnails');
  
  // *********************************************** Think of professorLandscape as meta information that says that 
  // from NOW ON when any image is uploaded into WP using the dashboard, then to ALSO create that image in this size 
  // and save it in the the wp-contents/uploads/ folder. professor has NOTHING to do with this ... bad name selection ...
  // should have been called image480260 for example .... something like that
  add_image_size('professorLandscape', 400, 260, true);
  add_image_size('professorPortrait', 480, 650, true);
  add_image_size('pageBanner', 1500, 350, true);
}

add_action('after_setup_theme', 'university_features');

function university_adjust_queries($query) {
  // ****************************** is_admin() will be true during dashboard processing, NOT your front-end (website) processing
  if (!is_admin() AND is_post_type_archive('campus') AND is_main_query()) {
    $query->set('posts_per_page', -1);
  }

  if (!is_admin() AND is_post_type_archive('program') AND is_main_query()) {
    $query->set('orderby', 'title');
    $query->set('order', 'ASC');
    $query->set('posts_per_page', -1);
  }

  if (!is_admin() AND is_post_type_archive('event') AND is_main_query()) {
    $today = date('Ymd');
    $query->set('meta_key', 'event_date');
    $query->set('orderby', 'meta_value_num');
    $query->set('order', 'ASC');
    $query->set('meta_query', array(
              array(
                'key' => 'event_date',
                'compare' => '>=',
                'value' => $today,
                'type' => 'numeric'
              )
            ));
  }
}

add_action('pre_get_posts', 'university_adjust_queries');

function universityMapKey($api) {
  $api['key'] = 'AIzaSyBh9b1rNCp6kOi5JeMHiRP4klDymBeoEWk';
  return $api;
}

add_filter('acf/fields/google_map/api', 'universityMapKey');


// ******************* Redirect users whose ONLY role IS subscriber out of DASHBOARD and onto homepage *************
add_action('admin_init', 'y');
function y() {
      $ourCurrentUser = wp_get_current_user();

      if (count($ourCurrentUser->roles) == 1 AND $ourCurrentUser->roles[0] == 'subscriber') {
          wp_redirect(site_url('/'));
          exit;
      }
}
// ******************************************************************************************************************


// ******************* Don't show black admin bar across the top users whose ONLY role IS subscriber  ***************
add_action('wp_loaded', 'x');
function x() {
  $ourCurrentUser = wp_get_current_user();

  if (count($ourCurrentUser->roles) == 1 AND $ourCurrentUser->roles[0] == 'subscriber') {
    show_admin_bar(false);
  }
}
// ******************************************************************************************************************


// ***** Customize Login Screen which appears when one goes to <a href="echo wp_login_url();" ....  *****************
add_filter('login_headerurl', 'ourHeaderUrl');            // Change href of the image that appears above username / pwd
function ourHeaderUrl() {
  return esc_url(site_url('/'));
}

add_action('login_enqueue_scripts', 'ourLoginCSS');       // **** Load our theme's main css file, style.css on the login page .. 
function ourLoginCSS() {                                  // the wp_enqueue_scripts event ONLY fires on our front-end ....
                                                          // not the backend (dashboard) ...basically not when the code in the wp-admin
                                                          // folder is executing ... which is what creates the login page
                                                          // so we load our css that pertains into dashboard or back-end part
                                                          // Now we just need to F12 inspect the elements on the login page and place the
                                                          // css styles in style.css 
  wp_enqueue_style('university_main_styles', get_stylesheet_uri());
  wp_enqueue_style('custom-google-fonts', '//fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i');
}

add_filter('login_headertitle', 'ourLoginTitle');

function ourLoginTitle() {
  return get_bloginfo('name');
}

// Force note posts to be private
add_filter('wp_insert_post_data', 'makeNotePrivate', 10, 2);

function makeNotePrivate($data, $postarr) {
  if ($data['post_type'] == 'note') {
    if(count_user_posts(get_current_user_id(), 'note') > 4 AND !$postarr['ID']) {
      die("You have reached your note limit.");
    }

    $data['post_content'] = sanitize_textarea_field($data['post_content']);
    $data['post_title'] = sanitize_text_field($data['post_title']);
  }

  if($data['post_type'] == 'note' AND $data['post_status'] != 'trash') {
    $data['post_status'] = "private";
  }
  
  return $data;
}
