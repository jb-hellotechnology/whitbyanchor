document.querySelectorAll('.menu-toggle').forEach(function (btn) {
  btn.addEventListener('click', function () {
	document.querySelector('nav.mobile').classList.toggle('show');
  });
});