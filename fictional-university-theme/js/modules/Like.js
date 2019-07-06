import $ from 'jquery';

class Like {
  constructor() {
    this.events();
  }

  events() {
    $(".like-box").on("click", this.x.bind(this));
  }

  // methods
  x(e) {
    var currentLikeBox = $(e.target).closest(".like-box");

    // ****** The reason the author chose NOT to use the jQuery .data method to access the data-* property is because
    //        the .data method checks the data-* attribute only once when the page loads whereas the .attr() method checks it
    //        each time it is executed
    if (currentLikeBox.attr('data-exists') == 'yes') {
      this.deleteLike(currentLikeBox);
    } else {
      this.createLike(currentLikeBox);
    }
  }

  createLike(currentLikeBox) {
    $.ajax({
      beforeSend: (xhr) => {
        xhr.setRequestHeader('X-WP-Nonce', universityData.nonce);
      },
      url: universityData.root_url + '/wp-json/university/v1/manageLike',       // *** Custom REST API Endpoint ************
      type: 'POST',
      data: {'professorId': currentLikeBox.data('professor')},                  // .data accesses the data-* attributes
      success: (response) => {
        currentLikeBox.attr('data-exists', 'yes');
        var likeCount = parseInt(currentLikeBox.find(".like-count").html(), 10);
        likeCount++;
        currentLikeBox.find(".like-count").html(likeCount);
        
        // **** The REST API endpoint returns the ID of the newly created Like record    ... wp_insert_post() in the REST endpoint API 
        //                                                                               returns that 
        currentLikeBox.attr("data-like", response);
        console.log(response);
      },
      error: (response) => {
        console.log(response);
      }
    });
  }

  deleteLike(currentLikeBox) {
    $.ajax({
      beforeSend: (xhr) => {
        xhr.setRequestHeader('X-WP-Nonce', universityData.nonce);
      },
      url: universityData.root_url + '/wp-json/university/v1/manageLike',     // *** Custom REST API Endpoint ************
      
      data: {'like': currentLikeBox.attr('data-like')}, // ***** contains the id of the Like record that we want deleted
      
      type: 'DELETE',
      success: (response) => {
        currentLikeBox.attr('data-exists', 'no');
        var likeCount = parseInt(currentLikeBox.find(".like-count").html(), 10);
        likeCount--;
        currentLikeBox.find(".like-count").html(likeCount);
        currentLikeBox.attr("data-like", '');
        console.log(response);
      },
      error: (response) => {
        console.log(response);
      }
    });
  }
}

export default Like;
