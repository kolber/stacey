Gallery = {
  currentImage: 0,
  imageHolders: null,
  imageHolderWidth: null,
  imageCountHolder: null,
  maxCount: null,
  nextButton: null,
  prevButoon: null,
  init: function(imageHolder, imageWrapperWidth, imageCountHolder, nextButton, prevButton, descriptionHolder, descriptionWidth) {
    // set custom variables
    this.imageHolder = imageHolder;
    this.imageWrapperWidth = imageWrapperWidth;
    this.imageCountHolder = imageCountHolder;
    this.maxCount = $("img", imageHolder).length;
    this.nextButton = nextButton;
    this.prevButton = prevButton;
    this.descriptionHolder = descriptionHolder;
    this.descriptionWidth = descriptionWidth;
    
    // check if a specific image has been specified in the URL
    if(document.URL.match(/#[0-9]+/)) {
      this.gotoImage(new Number(new String(document.URL.match(/#[0-9]+/)).replace("#", "")) - 1);
    } else {
      // write maxCount
      this.updateCount(0);
    } 
    // 
    this.attachEvents();
  },
  attachEvents: function() {
    // write next/prev functions
    this.nextButton.click(function() {
      Gallery.next();
      this.blur();
      return false;
    });
    this.prevButton.click(function() {
      Gallery.previous();
      this.blur();
      return false;
    });
  },
  next: function() {
    // show next image
    this.gotoImage(this.currentImage + 1);
  },
  previous: function() {
    // show previous image
    this.gotoImage(this.currentImage - 1);
  },
  updateCount: function(newCount) {
    // set current image
    this.currentImage = newCount;
    // update current image display
    this.imageCountHolder.innerHTML = (newCount + 1) + "/" + this.maxCount;
    // update url hash
    //window.location.hash = (newCount + 1);
  },
  gotoImage: function(num) {
    // if not too high
    if(num >= this.maxCount) {
      num = 0;
    } else if(num < 0) {
      num = this.maxCount - 1;
    }
    //animate
    this.animateContainers(num);
    // update count
    this.updateCount(num);
  },
  animateContainers: function(num) {
    this.imageHolder.animate({
      marginLeft: (num * this.imageWrapperWidth) * -1 + "px"
    }, { duration: 600, queue: false });
    
    // skip attempt to animate description holder if it does not exist
    if(!this.descriptionHolder) return;
    
    this.descriptionHolder.animate({
      marginLeft: (num * this.descriptionWidth) * -1 + "px"
    }, { duration: 600, queue: false });
  }
}