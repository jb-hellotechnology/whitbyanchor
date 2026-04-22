var btns = document.querySelectorAll('.menu-toggle');
for (var i = 0; i < btns.length; i++) {
  btns[i].addEventListener('click', function () {
    document.querySelector('nav.mobile').classList.toggle('show');
  });
}