<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package whitbyanchor
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
	<link rel="stylesheet" href="https://use.typekit.net/wth4cri.css">

	<?php wp_head(); ?>
</head>


<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'whitbyanchor' ); ?></a>

	<header id="masthead" class="site-header">
		<div class="data">
			<span>
			<?php $times = get_sunrise_sunset(); ?>
			<p>Sunrise <?= $times['sunrise'] ?> | Sunset <?= $times['sunset'] ?></p>
			<p><?= date('nS F Y') ?></p>
			<p>
			<?php
			$next_tides = get_next_tides();
			
			$parts = array_map( function( $tide ) {
				$label = ucfirst( $tide['type'] );
				return "Next {$label} Tide: {$tide['time']}";
			}, $next_tides );
			
			echo implode( ' | ', $parts );
			?>
			</p>
			</span>
		</div>
		<div class="site-branding">
			<a href="/"><img src="<?= get_template_directory_uri() ?>/masthead.svg?v=1" alt="The Whitby Anchor" /></a>
		</div><!-- .site-branding -->

		<nav id="site-navigation" class="primary-navigation desktop">
			<button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false"><?php esc_html_e( 'Menu', 'whitbyanchor' ); ?></button>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'menu-1',
					'menu_id'        => 'primary-menu',
				)
			);
			echo get_search_form();
			?>
		</nav><!-- #site-navigation -->
		<nav class="secondary-navigation desktop">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'menu-2',
					'menu_id'        => 'secondary-menu',
				)
			);
			?>
		</nav>
		<nav id="site-navigation" class="mobile">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'menu-6',
					'menu_id'        => 'mobile-menu',
				)
			);
			echo get_search_form();
			?>
			
			<button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false"><span>Close</span>&times;</button>
		</nav><!-- #site-navigation -->
	</header><!-- #masthead -->
