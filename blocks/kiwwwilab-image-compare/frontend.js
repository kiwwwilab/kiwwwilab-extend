document.addEventListener('DOMContentLoaded', function () {
  var sliders = document.querySelectorAll('.kiwwwilab-ic-wrapper .kiwwwilab-ic-slider');
  
  sliders.forEach(function (slider) {
    slider.addEventListener('input', function (e) {
      var container = e.target.closest('.kiwwwilab-ic-wrapper');
      var beforeButton = container.querySelector('.kiwwwilab-ic-slide-button');
      var beforeWrap = container.querySelector('.kiwwwilab-ic-before-wrap');
      if (beforeWrap) {
        beforeWrap.style.width = e.target.value + '%';
        beforeButton.style.left = e.target.value + '%';
      }
    });
  });
});
