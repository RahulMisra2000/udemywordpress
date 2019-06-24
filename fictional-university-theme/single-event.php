<?php
  
  get_header();

  while(have_posts()) {
    the_post();
    pageBanner();
     ?>

    <div class="container container--narrow page-section">
          <div class="metabox metabox--position-up metabox--with-home-link">
        <p><a class="metabox__blog-home-link" href="<?php echo get_post_type_archive_link('event'); ?>"><i class="fa fa-home" aria-hidden="true"></i> Events Home</a> <span class="metabox__main"><?php the_title(); ?></span></p>
      </div>

      <div class="generic-content"><?php the_content(); ?></div>

      <?php
        
        // ---------------------------------------------------------------------------------------------------
        // related_programs is the custom field created in the Events custom post type by using the ACF plugin
        // In the dashboard after creating it, we went into the event record and in the custom field we selected 
        // one or more records from the Program post type. 
        // The related_programs will contain **post objects** for all the programs that were selected
        // ---------------------------------------------------------------------------------------------------
        $relatedPrograms = get_field('related_programs');

        if ($relatedPrograms) {
          echo '<hr class="section-break">';
          echo '<h2 class="headline headline--medium">Related Program(s)</h2>';
          echo '<ul class="link-list min-list">';
          
          // ********* In this loop we will get all the Program records as **POST OBJECTS** that were assigned to the event
          // ********* Because they are post objects, we can use them in functions like get_the_permalink() etc
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
