<?php
  
  get_header();

  while(have_posts()) {         // ****************************  OUTER PROGRAM RECORD LOOP *****************************************
                                // **** This loop will give us ONE program record because this template is single-program.php
    the_post();                 //      So, after doing the_post() tons of functions are now available
                                //      For instance, get_the_ID() will give is the id of the program record. 
    pageBanner();
     ?>

    <div class="container container--narrow page-section">
          <div class="metabox metabox--position-up metabox--with-home-link">
        <p><a class="metabox__blog-home-link" href="<?php echo get_post_type_archive_link('program'); ?>"><i class="fa fa-home" aria-hidden="true"></i> All Programs</a> <span class="metabox__main"><?php the_title(); ?></span></p>
      </div>

      <div class="generic-content"><?php the_field('main_body_content'); ?></div>

      <?php 
        // ****************************************************************************************************************************
        // *** REMEMBER, in the dashboard using ACF plugin, a M:M relationship was created between Professor and Program custom content
        //     types.   
        //                          M PROFESSOR         :     M PROGRAMS                  M:M relationship
        //          (related_programs custom field 
        //            setup here in professor record)
        //
        //     This was done by configuring a custom field (called related_programs) in ACF and specifying that it should be made 
        //     available in the Professor record. So, when a professor record is added via the dashboard, we can select one or more 
        //     programs (think subjects) that the professor teaches.
        // ****************************************************************************************************************************
    
        $relatedProfessors = new WP_Query(array(
          'posts_per_page' => -1,
          'post_type' => 'professor',                           // *** I want professor records
          'orderby' => 'title',
          'order' => 'ASC',
          'meta_query' => array(                                // ***  Where clause
            array(
              'key' => 'related_programs',                          // the related_programs field in the Professor records
              'compare' => 'LIKE',                                  // contains
              'value' => '"' . get_the_ID() . '"'                   // the program ID
                                                                    // It is the program record's ID because get_the_ID() returns
                                                                    // the ID of the current item in the LOOP ... and at this point
                                                                    // we are in the Program record Loop
            )
          )
        ));

        if ($relatedProfessors->have_posts()) {
            echo '<hr class="section-break">';
            echo '<h2 class="headline headline--medium">' . get_the_title() . ' Professors</h2>';

            echo '<ul class="professor-cards">';

            while($relatedProfessors->have_posts()) {             // INNER PROFESSOR records LOOP ********************************
                  $relatedProfessors->the_post(); ?>
                  <li class="professor-card__list-item">
                    <a class="professor-card" href="<?php the_permalink(); ?>">
                      <img class="professor-card__image" src="<?php the_post_thumbnail_url('professorLandscape') ?>">
                      <span class="professor-card__name"><?php the_title(); ?></span>
                    </a>
                  </li>
            <?php }

            echo '</ul>';
        }

        wp_reset_postdata();

        $today = date('Ymd');
        $homepageEvents = new WP_Query(array(
          'posts_per_page' => 2,
          'post_type' => 'event',
          'meta_key' => 'event_date',
          'orderby' => 'meta_value_num',
          'order' => 'ASC',
          'meta_query' => array(
            array(
              'key' => 'event_date',
              'compare' => '>=',
              'value' => $today,
              'type' => 'numeric'
            ),
            array(
              'key' => 'related_programs',
              'compare' => 'LIKE',
              'value' => '"' . get_the_ID() . '"'
            )
          )
        ));

        if ($homepageEvents->have_posts()) {
          echo '<hr class="section-break">';
        echo '<h2 class="headline headline--medium">Upcoming ' . get_the_title() . ' Events</h2>';

        while($homepageEvents->have_posts()) {
          $homepageEvents->the_post();
          get_template_part('template-parts/content-event');
        }
        }

        wp_reset_postdata();
        $relatedCampuses = get_field('related_campus');

        if ($relatedCampuses) {
          echo '<hr class="section-break">';
          echo '<h2 class="headline headline--medium">' . get_the_title() . ' is Available At These Campuses:</h2>';

          echo '<ul class="min-list link-list">';
          foreach($relatedCampuses as $campus) {
            ?> <li><a href="<?php echo get_the_permalink($campus); ?>"><?php echo get_the_title($campus) ?></a></li> <?php
          }
          echo '</ul>';

        }

      ?>

    </div>
    

    
  <?php }

  get_footer();

?>
