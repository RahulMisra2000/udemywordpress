<?php



add_action('rest_api_init', 'universityLikeRoutes');
function universityLikeRoutes() {
      register_rest_route('university/v1', 'manageLike', array(
        'methods' => 'POST',
        'callback' => 'createLikeCB'
      ));

      register_rest_route('university/v1', 'manageLike', array(
        'methods' => 'DELETE',
        'callback' => 'deleteLikeCB'
      ));
}

function createLikeCB($data) {
   // *************** WP stuffs all the stuff (query params, body etc... all inside an array and provides it in the param ****** /
   
  // ****** alternatively one can code 
  //              if (current_user_can(<some capability like publish_like, etc>){ }                      *********************** /
  //              capabilities we assign to roles using Members plugin and then assign users to those roles 
  //        But here we just care if the ajax request coming to the custom REST API endpoint is coming from a logged in user or not,    /
  //        that is all ... we don't care about the logged in user's capabilities 
  
  // ********** is_user_logged_in() will return true ONLY IF the ajax request has a NONCE in it *************************** /
  if (is_user_logged_in()) {
        $professor = sanitize_text_field($data['professorId']);

        $existQuery = new WP_Query(array(
            'author' => get_current_user_id(),
            'post_type' => 'like',
            'meta_query' => array(
                array(
                  'key' => 'liked_professor_id',          // *** the ACF field
                  'compare' => '=',
                  'value' => $professor
                )
            )
        ));
  
        // **** The condition below ensures that a user can only like a professor only once and the professor id exists in professor table** /
        
        // ***  $existQuery->found_posts == 0             means that the above query returns no record, meaning 
        //                                                that the current logged in user has not liked the professor in the past
        // ***  get_post_type($professor) == 'professor'  means that the professor id actually exists in the professor table
        if ($existQuery->found_posts == 0 AND get_post_type($professor) == 'professor') {    
            // *** the wp_insert_post function returns the id of the newly created record, and that is what the ajax caller will get * /
            return wp_insert_post(array(
              'post_type' => 'like',
              'post_status' => 'publish',
              'post_title' => '2nd PHP Test',
              'meta_input' => array(
                'liked_professor_id' => $professor      // *** this is how we fill up ACF field *************
              )
            ));
        } else {
          die("Invalid professor id");      // *** This basically immediately CACELS the ajax request and sends back ********
                                            // *** whatever is in the quotes as response text to the ajax caller ...
        }
  } else {
        die("Only logged in users can create a like.");
  }
}




function deleteLikeCB($data) {
      $likeId = sanitize_text_field($data['like']);
      // *** get_post_field('post_author', $likeId)   means get me the author of Like record whose id is $LikeId
      // *** and it should be the same as the current user sending the ajax request   get_current_user_id 
      //          the above means that a logged in user can only delete his like record
      // *** get_post_type($likeId) == 'like')        means the $likeId better be an Id in the Like table 
      
      if (get_current_user_id() == get_post_field('post_author', $likeId) AND get_post_type($likeId) == 'like') {
          wp_delete_post($likeId, true);
          return 'Congrats, like deleted.';
      } else {
          die("You do not have permission to delete that.");
      }
}
