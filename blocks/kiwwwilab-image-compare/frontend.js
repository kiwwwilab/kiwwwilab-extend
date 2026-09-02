document.addEventListener('DOMContentLoaded', function () {
  var sliders = document.querySelectorAll('.kiwwwilab-ic-wrapper .kiwwwilab-ic-slider');
  
  sliders.forEach(function (slider) {
    slider.addEventListener('input', function (e) {
      var container = e.target.closest('.kiwwwilab-ic-wrapper');
      var beforeButton = container.querySelector('.kiwwwilab-ic-slide-button');
      var beforeImg = container.querySelector('.kiwwwilab-ic-before');
      var insetRight = 100 - e.target.value;
      if (beforeImg) {
        beforeImg.style.clipPath = 'inset(0 ' + insetRight + '% 0 0)';
        beforeButton.style.left = e.target.value + '%';
      }
    });
  });
});


