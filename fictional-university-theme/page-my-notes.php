<?php

  if (!is_user_logged_in()) {                     // ************** ONLY logged in users, otherwise redirect them to home page ******
    wp_redirect(esc_url(site_url('/')));      
    exit;                                         // Stop processing this page as the redirect is happening .....
  }

  get_header();

  while(have_posts()) {                         // **** We are doing this ONLY to get the title and some ACF fields from the PAGE My Notes
                                                // that we created from Dashboard, so that the function pageBanner() has access to them
    the_post();
    pageBanner();
     ?>
    
    

    <div class="container container--narrow page-section">
      
          <!-- ********************** Here we are creating an area where the user can enter info and create a new note *********** -->
          <div class="create-note">
            <h2 class="headline headline--medium">Create New Note</h2>
            <input class="new-note-title" placeholder="Title">
            <textarea class="new-note-body" placeholder="Your note here..."></textarea>
            <span class="submit-note">Create Note</span>
            <span class="note-limit-message">Note limit reached: delete an existing note to make room for a new one.</span>
          </div>
        <!-- ********************** Here we are creating an area where the user can enter info and create a new note *********** -->




        <!-- ************************************************* DISPLAY existing notes of the user ***************************** -->
          <ul class="min-list link-list" id="my-notes">
            <?php 

              $userNotes = new WP_Query(array(
                  'post_type' => 'note',
                  'posts_per_page' => -1,
                  'author' => get_current_user_id()     // ******** THIS IS IMP .... where clause to make sure logged in user's notes *** */
              ));

              while($userNotes->have_posts()) {
                    $userNotes->the_post(); ?>

                    <li data-id="<?php the_ID(); ?>">   <!-- *** Each note detail is under an <li></li> ************** -->

                        <!-- ************************ Title of Note********************************** ---->  
                        <!-- It starts out as readonly and when the user clicks on the edit button, we will remove the readonly attr ---->  
                        <input readonly class="note-title-field" value="<?php echo str_replace('Private: ', '', esc_attr(get_the_title())); ?>">
                        <!-- ************************ EDIT Button *********************************** ---->  
                        <span class="edit-note"><i class="fa fa-pencil" aria-hidden="true"></i> Edit</span>  

                        <!-- ************************ DELETE Button ********************************* ---->  
                        <span class="delete-note"><i class="fa fa-trash-o" aria-hidden="true"></i> Delete</span>  <!-- DELETE ---->

                        <!-- ************************ Body of Note ********************************** ---->  
                        <!-- It starts out as readonly and when the user clicks on the edit button, we will remove the readonly attr ---->  
                        <textarea readonly class="note-body-field"><?php echo esc_textarea(get_the_content()); ?></textarea>

                        <!-- ************************ SAVE Button *********************************** ---->  
                        <span class="update-note btn btn--blue btn--small"><i class="fa fa-arrow-right" aria-hidden="true"></i> Save</span>
                    </li>
              <?php }

            ?>
          </ul>
        <!-- ************************************************* DISPLAY existing notes of the user ***************************** -->
     
    </div>
    
  <?php }     // ****** OUTER WHILE ******************/

  get_footer();

?>
