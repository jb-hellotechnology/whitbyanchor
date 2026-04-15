<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package whitbyanchor
 */

?>

	<footer id="colophon" class="site-footer">
		<div class="menus">
			<div>
			<h2>About Us</h2>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'menu-3',
					'menu_id'        => 'about-us',
				)
			);
			?>
			</div>
			<div>
			<h2>Contact</h2>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'menu-4',
					'menu_id'        => 'contact',
				)
			);
			?>
			</div>
			<div>
			<h2>Policies &amp; Privacy</h2>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'menu-5',
					'menu_id'        => 'links',
				)
			);
			?>
			</div>
		</div>
		<div class="site-info">
			<p><a href="">Designed &amp; Built in Whitby by Hello Technology</a>
			<span class="sep"> | </span>
				&copy; Ink &amp; Tide Publishing <?= date('Y') ?></p>
		</div><!-- .site-info -->
	</footer><!-- #colophon -->
	<button class="mailing-list-trigger">Sign up to our newsletter</button>
</div><!-- #page -->

<div class="mailing-list-wrapper">
	<button class="close">Close</button>
	<iframe width="360" height="560" src="https://55fe7bef.sibforms.com/serve/MUIFAPpLZHCi43qNpINCiqS4InXyrFol5HEKl_Z2TN6HiYowk91-mztfi8dkMZgFLCFn1lUusVf1gTX-1CAsD6g05Em3IkJ9IDtw7wweEHb9Ye0DxYTxZTiJpUa0yN5c0LnZcyxxI5uCBYwcXXkksoejughDncvZDLuOr8xec5FMdLJExjg8KcYBGUFHe_EHK2uo9809gcaH4uRQyA==" frameborder="0" scrolling="auto" allowfullscreen style="display: block;margin-left: auto;margin-right: auto;max-width: 100%;"></iframe>
</div>

<script>
	(function () {
		const STORAGE_KEY = 'mailingListDismissed';
		const DISMISS_DAYS = 7;
		const DELAY_MS = 5000;
	
		function isDismissed() {
			const entry = localStorage.getItem(STORAGE_KEY);
			if (!entry) return false;
	
			const expiry = parseInt(entry, 10);
			if (Date.now() > expiry) {
				localStorage.removeItem(STORAGE_KEY);
				return false;
			}
	
			return true;
		}
	
		function dismiss() {
			const expiry = Date.now() + DISMISS_DAYS * 24 * 60 * 60 * 1000;
			localStorage.setItem(STORAGE_KEY, expiry.toString());
			closePopup();
		}
	
		function closePopup() {
			const wrapper = document.querySelector('.mailing-list-wrapper');
			if (wrapper) {
				wrapper.setAttribute('aria-hidden', 'true');
				wrapper.style.display = 'none';
			}
		}
	
		// Exposed globally so inline buttons and other scripts can trigger the popup
		window.openMailingListPopup = function () {
			const wrapper = document.querySelector('.mailing-list-wrapper');
			if (wrapper) {
				wrapper.removeAttribute('aria-hidden');
				wrapper.style.display = '';
			}
		};
	
		function init() {
			const closeBtn = document.querySelector('.mailing-list-wrapper .close');
			if (closeBtn) {
				closeBtn.addEventListener('click', dismiss);
			}
	
			// Bind any sign-up trigger buttons on the page
			document.querySelectorAll('.mailing-list-trigger').forEach(function (btn) {
				btn.addEventListener('click', window.openMailingListPopup);
			});
	
			if (!isDismissed()) {
				setTimeout(window.openMailingListPopup, DELAY_MS);
			}
		}
	
		// Hide the wrapper immediately before the timer fires
		const wrapper = document.querySelector('.mailing-list-wrapper');
		if (wrapper) {
			wrapper.style.display = 'none';
			wrapper.setAttribute('aria-hidden', 'true');
		}
	
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', init);
		} else {
			init();
		}
	})();
</script>

<?php wp_footer(); ?>
<script>
	var nav = priorityNav.init();
</script>
</body>
</html>
