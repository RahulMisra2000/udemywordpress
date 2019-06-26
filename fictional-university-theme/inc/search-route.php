<?php

add_action('rest_api_init', 'universityRegisterSearch');

function universityRegisterSearch() {
  // *********** Here we are adding a custom REST API   http://domain/wp-json/university/v1/search ********************
  register_rest_route('university/v1', 'search', array(
    'methods' => WP_REST_SERVER::READABLE,                // **********  this api will support http GET verb
    'callback' => 'universitySearchResults'               // **********  this is the handler when the api comes into WP
  ));
}


// *** WP stuffs the first parameter into our handler. It contains a lot of interesting data ...including the url's query string *******
function universitySearchResults($data) {
  $mainQuery = new WP_Query(array(
    'post_type' => array('post', 'page', 'professor', 'program', 'campus', 'event'),    // **** what type of content to search
     // post is the traditional blog post ... the post that comes bundled with wordpress
     // page is the traditional page  that comes bundled with wordpress
     // professor, program, campus and event are custom post types that the author created for this website
    
    //  ALL user entered data should be sanitized
    // ***** So if this is received by WP ... http://domain/wp-json/university/v1/search?term=abc
    // Then WP will search all the above post types TITLE and MAIN CONTENT for the word abc. This is what the s parameter below does
    's' => sanitize_text_field($data['term'])
  ));

  // ************* Creating an associative array in WP ... think of it as analogous to javascript object *****************
  // => is just like : in javascript's literal object definition.       
  // DETOUR:    in php -> is the same as javascript's dot notation to go inside an object
  $results = array(                       // *** creating a nested array
    'generalInfo' => array(),                 // blog posts and pages info will be stuffed in here
                                              // Info pertaining to the custom Post types will be in the next 4 arrays
    'professors' => array(),
    'programs' => array(),
    'events' => array(),
    'campuses' => array()
  );

  while($mainQuery->have_posts()) {
    $mainQuery->the_post();                           // ***** this allows the template tags like get_the_title() to access 
                                                      //       data in the returned record   ++++++++

    if (get_post_type() == 'post' OR get_post_type() == 'page') {
          array_push($results['generalInfo'], array(
            'title' => get_the_title(),
            'permalink' => get_the_permalink(),
            'postType' => get_post_type(),
            'authorName' => get_the_author()
          ));
    }

    if (get_post_type() == 'professor') {
          array_push($results['professors'], array(
            'title' => get_the_title(),                   // ****** this says get the title from the 
            'permalink' => get_the_permalink(),
            'image' => get_the_post_thumbnail_url(0, 'professorLandscape')
          ));
    }

    if (get_post_type() == 'program') {
          $relatedCampuses = get_field('related_campus'); // *** related_campus - this is the ACF field that points to the Campus custom post type
          // ********* So, $relatedCampuses is an array of ALL the Campus records associated with the current record in the loop

          if ($relatedCampuses) {
            foreach($relatedCampuses as $campus) {          // $campus is the individual campus record
              array_push($results['campuses'], array(
                'title' => get_the_title($campus),          // ****** this says get the title from the campus record and NOT from +++++++++
                                                            //        which it would if we didn't specify the $campus
                'permalink' => get_the_permalink($campus)
              ));
            }
          }

          array_push($results['programs'], array(
            'title' => get_the_title(),
            'permalink' => get_the_permalink(),
            'id' => get_the_id()
          ));
    }

    if (get_post_type() == 'campus') {
          array_push($results['campuses'], array(
            'title' => get_the_title(),
            'permalink' => get_the_permalink()
          ));
        }

    if (get_post_type() == 'event') {
          $eventDate = new DateTime(get_field('event_date'));   // *** Wrapping it in DateTime so we can use helper methods
          $description = null;
          if (has_excerpt()) {
            $description = get_the_excerpt();
          } else {
            $description = wp_trim_words(get_the_content(), 18);
          }

          array_push($results['events'], array(
            'title' => get_the_title(),
            'permalink' => get_the_permalink(),
            'month' => $eventDate->format('M'),               // *** this is why we wrapped it in DateTime
            'day' => $eventDate->format('d'),
            'description' => $description
          ));
    }
    
  }

  if ($results['programs']) {
        $programsMetaQuery = array('relation' => 'OR');

        foreach($results['programs'] as $item) {
              array_push($programsMetaQuery, array(
                  'key' => 'related_programs',
                  'compare' => 'LIKE',
                  'value' => '"' . $item['id'] . '"'
                ));
    }

    $programRelationshipQuery = new WP_Query(array(
      'post_type' => array('professor', 'event'),
      'meta_query' => $programsMetaQuery
    ));

    while($programRelationshipQuery->have_posts()) {
      $programRelationshipQuery->the_post();

      if (get_post_type() == 'event') {
        $eventDate = new DateTime(get_field('event_date'));
        $description = null;
        if (has_excerpt()) {
          $description = get_the_excerpt();
        } else {
          $description = wp_trim_words(get_the_content(), 18);
        }

        array_push($results['events'], array(
          'title' => get_the_title(),
          'permalink' => get_the_permalink(),
          'month' => $eventDate->format('M'),
          'day' => $eventDate->format('d'),
          'description' => $description
        ));
      }

      if (get_post_type() == 'professor') {
        array_push($results['professors'], array(
          'title' => get_the_title(),
          'permalink' => get_the_permalink(),
          'image' => get_the_post_thumbnail_url(0, 'professorLandscape')
        ));
      }

    }

    $results['professors'] = array_values(array_unique($results['professors'], SORT_REGULAR));
    $results['events'] = array_values(array_unique($results['events'], SORT_REGULAR));
  }


  return $results;

}
