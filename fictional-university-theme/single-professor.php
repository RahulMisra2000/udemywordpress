<?php
  
  get_header();

  while(have_posts()) {                 // ****** will return the professor record
    the_post();
    pageBanner();
     ?>
    

    <div class="container container--narrow page-section">
          
      <div class="generic-content">
        <div class="row group">

          <div class="one-third">
            <?php the_post_thumbnail('professorPortrait'); ?>
          </div>

          <div class="two-thirds">
            <?php
              // ***** like is a custom post type that has an ACF field called liked_professor_id which is of type number ****************
              //       When a user likes a professor a record of like will be added to the database with the prof id in that field
              //       and of course being a post, it will have the currently logged in user's id as meta information in that record also
              
              // ***** This query will return all the like records of the professor who is showing on the page
              $likeCount = new WP_Query(array(
                    'post_type' => 'like',                // think of it as looping through the records in the like table
                    'meta_query' => array(                // think of it as a where clause
                      array(
                        'key' => 'liked_professor_id',
                        'compare' => '=',
                        'value' => get_the_ID()           // ***** id of the professor showing on the page 
                      )
                    )
              ));

    
    
    
    
    
              $existStatus = 'no';
              // ****** this query will return a record if the currently logged in user has already liked the prof showing on page
              if (is_user_logged_in()) {
                $existQuery = new WP_Query(array(
                  'author' => get_current_user_id(),      // ****** id of the logged in user    
                  'post_type' => 'like',
                  'meta_query' => array(
                    array(
                      'key' => 'liked_professor_id',
                      'compare' => '=',
                      'value' => get_the_ID()
                    )
                  )
                ));

                if ($existQuery->found_posts) {          // true if the current logged in user has already liked the prof
                  $existStatus = 'yes';
                }
              }

              

            ?>

            <span class="like-box" data-like="<?php echo $existQuery->posts[0]->ID; ?>" data-professor="<?php the_ID(); ?>" data-exists="<?php echo $existStatus; ?>">
              <i class="fa fa-heart-o" aria-hidden="true"></i>
              <i class="fa fa-heart" aria-hidden="true"></i>
              <!-- ************************************ Runs the query $likeCount and returns the # of records found ********** -->
              <span class="like-count"><?php echo $likeCount->found_posts; ?></span>
            </span>
            <?php the_content(); ?>
          </div>

        </div>
      </div>

      <?php

        $relatedPrograms = get_field('related_programs');

        if ($relatedPrograms) {
          echo '<hr class="section-break">';
          echo '<h2 class="headline headline--medium">Subject(s) Taught</h2>';
          echo '<ul class="link-list min-list">';
          foreach($relatedPrograms as $program) { ?>
            <li><a href="<?php echo get_the_permalink($program); ?>"><?php echo get_the_title($program); ?></a></li>
          <?php }
          echo '</ul>';
        }

      ?>

    </div>
    
  <?php }

  get_footer();

?>
