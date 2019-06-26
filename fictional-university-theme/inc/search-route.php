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

  
  
  // *************************************** 1st LOOP *********************************************************************
  //  will stuff into separate arrays all these post types --> 'post', 'page', 'professor', 'program', 'campus', 'event' 
  //  whose title or body (main content) has *abc* as the searcg term in the API call
  //  eg. http://domain/wp-json/university/v1/search?term=abc
  
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
    
  }     // WHILE LOOP that cycles through 'post_type' => array('post', 'page', 'professor', 'program', 'campus', 'event')

  // *************************************** END OF 1st LOOP *********************************************************************
  // At this stage all the arrays()   -->>  generalInfo  professors, programs, events, campuses
  // are filled with records of the above post types that matched the search term
    
  
  
  
  
  
   
  // **********************************************************************************************************************
  // Now we will see if there are any professors and events that linked to program records that were found in the 1st Loop
  // and placed in the $results['programs'] array
  // **********************************************************************************************************************
  if ($results['programs']) {             // ********** if we found any program records during the search above
                                          // If so then, let us search for Professor and Event records whose ACF field called
                                          // related_programs, points to the program record 
       
    
    
        // *********** Build the WHERE clause for the query *************
        $programsMetaQuery = array('relation' => 'OR');

        foreach($results['programs'] as $item) {          // *** as many as the number of program records that matched the search
              array_push($programsMetaQuery, array(
                  'key' => 'related_programs',                // ACF field
                  'compare' => 'LIKE',
                  'value' => '"' . $item['id'] . '"'          // program id
                ));
        }

        // ************ Set up the query ***************
        $programRelationshipQuery = new WP_Query(array(
          'post_type' => array('professor', 'event'),           // professor or event records whose ACF related_programs field 
                                                                // has program id of the program records that were found
          'meta_query' => $programsMetaQuery
        ));

    
        // *********** Execute the query which will return the professor and Event records that satisfy the where clause
        while($programRelationshipQuery->have_posts()) {
                $programRelationshipQuery->the_post();

                if (get_post_type() == 'event') {                                         // **** Event record
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
                }  // if

                if (get_post_type() == 'professor') {                                     // ******** professor record
                    array_push($results['professors'], array(
                      'title' => get_the_title(),
                      'permalink' => get_the_permalink(),
                      'image' => get_the_post_thumbnail_url(0, 'professorLandscape')
                    ));
                }

        }   // WHILE LOOP

        // ********* Because the professors and events can be duplicated, the following will remove the duplicates
        // If the search term is let's say biology ... then if the Professor has the word biology in his title (the_title) or 
        // body (the_content) then the 1st loop will add the professor to the professor's array.
        // Now if the word biology appears in a program record (title or body) then that program record will be added to 
        // the programs array in the 1st loop
        // After the 1st loop as we cycle through the programs array and search for professor records that are pointing to the program 
        // record, we may find a professor (whose title or body contains the search term biology and by virtue of that was shoved
        // into the professors array in the 1st loop) who will therefore get added again this time because of the ACF link 
        // ... now the professor will appear twice in the professors array.
    
        // Same above reasoning with Events...
    
        // Hence the need to remove the duplicates from the professors and events array.
        $results['professors'] = array_values(array_unique($results['professors'], SORT_REGULAR));
        $results['events'] = array_values(array_unique($results['events'], SORT_REGULAR));

  } // *********** if ($results['programs']) {

  return $results;
}
