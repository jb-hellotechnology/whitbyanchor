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
$current_user = wp_get_current_user();
if(!user_can( $current_user, 'administrator' )){
	header("location: /");	 
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Material+Symbols+Outlined&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://use.typekit.net/wth4cri.css">
	
	<link rel="apple-touch-icon" sizes="57x57" href="<?php echo get_stylesheet_directory_uri(); ?>/icons/apple-icon-57x57.png">
	<link rel="apple-touch-icon" sizes="60x60" href="<?php echo get_stylesheet_directory_uri(); ?>/icons/apple-icon-60x60.png">
	<link rel="apple-touch-icon" sizes="72x72" href="<?php echo get_stylesheet_directory_uri(); ?>/icons/apple-icon-72x72.png">
	<link rel="apple-touch-icon" sizes="76x76" href="<?php echo get_stylesheet_directory_uri(); ?>/icons/apple-icon-76x76.png">
	<link rel="apple-touch-icon" sizes="114x114" href="<?php echo get_stylesheet_directory_uri(); ?>/icons/apple-icon-114x114.png">
	<link rel="apple-touch-icon" sizes="120x120" href="<?php echo get_stylesheet_directory_uri(); ?>/icons/apple-icon-120x120.png">
	<link rel="apple-touch-icon" sizes="144x144" href="<?php echo get_stylesheet_directory_uri(); ?>/icons/apple-icon-144x144.png">
	<link rel="apple-touch-icon" sizes="152x152" href="<?php echo get_stylesheet_directory_uri(); ?>/icons/apple-icon-152x152.png">
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo get_stylesheet_directory_uri(); ?>/icons/apple-icon-180x180.png">
	<link rel="icon" type="image/png" sizes="192x192"  href="<?php echo get_stylesheet_directory_uri(); ?>/icons/android-icon-192x192.png">
	<link rel="icon" type="image/png" sizes="32x32" href="<?php echo get_stylesheet_directory_uri(); ?>/icons/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="96x96" href="<?php echo get_stylesheet_directory_uri(); ?>/icons/favicon-96x96.png">
	<link rel="icon" type="image/png" sizes="16x16" href="<?php echo get_stylesheet_directory_uri(); ?>/icons/favicon-16x16.png">
	<link rel="manifest" href="<?php echo get_stylesheet_directory_uri(); ?>/icons/manifest.json">
	<meta name="msapplication-TileColor" content="#ffffff">
	<meta name="msapplication-TileImage" content="<?php echo get_stylesheet_directory_uri(); ?>/icons/ms-icon-144x144.png">
	<meta name="theme-color" content="#ffffff">

	<?php wp_head(); ?>
</head>


<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'whitbyanchor' ); ?></a>

	<header id="masthead" class="site-header">
		<div class="data">
			<div>
			<?php $times = get_sunrise_sunset(); ?>
			<p>Sunrise <?= $times['sunrise'] ?> | Sunset <?= $times['sunset'] ?></p>
			<p><?= date('l M j Y') ?></p>
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
			</div>
		</div>
		<div class="site-branding">
			<a href="/"><img src="<?= get_template_directory_uri() ?>/masthead.svg?v=1" alt="The Whitby Anchor" /></a>
		</div><!-- .site-branding -->
	</header><!-- #masthead -->
	
	<nav id="site-navigation" class="primary-navigation desktop">
		<button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false"><?php esc_html_e( 'Menu', 'whitbyanchor' ); ?></button>
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'menu-1',
				'menu_id'        => 'primary-menu',
			)
		);
		?>
		<img src="<?php echo get_stylesheet_directory_uri(); ?>/icons/apple-icon-180x180.png" alt="Whitby Anchor" class="nav_icon" />
		<?php
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
	<nav class="mobile">
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
