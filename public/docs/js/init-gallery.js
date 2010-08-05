$(function() {

  // don't init gallery if it consists of a single photo
  if($("div.image").length > 1) {
    // wrap images
    $("div.image").wrapAll("<div id='image-wrapper'><div id='image-holder'></div></div>");
    $("div.image").show();
    
    // init gallery: Gallery.init(imageHolder, imageWrapperWidth, imageCountHolder, nextButton, prevButton)
    Gallery.init($("div#image-holder"), 560, $("p#gallery-count").children("span")[0], $("a#next-image"), $("a#previous-image"));

  } else {
    // hide gallery count and navigation
    $("p#gallery-count, div#gallery-navigation").hide();
    // show project navigation
    $("p#project-count").show();
  }
});