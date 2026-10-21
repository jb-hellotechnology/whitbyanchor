<?php
/**
 * Data behind the Subscribe page.
 *
 * The page's own content block stays in the WordPress editor — that is where the
 * monthly offer, the Stripe buy button and the free mailing list button live.
 * Everything here is the surround: the hero, the figures and the reasons.
 *
 * Audience figures are shared with the Advertising Rates page, so they are only
 * ever entered once, in inc/advertising-rates.php.
 *
 * @package whitbyanchor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Figures for the Subscribe page, framed around the journalism rather than the
 * advertising. Anything without a value is simply left out.
 */
function wa_subscribe_stats() {
	$supplied = function_exists( 'wa_advertising_supplied_stats' ) ? wa_advertising_supplied_stats() : array();
	$value    = function ( $key ) use ( $supplied ) {
		return isset( $supplied[ $key ]['value'] ) && $supplied[ $key ]['value'] ? (int) $supplied[ $key ]['value'] : 0;
	};

	$stats = array();

	$articles = wp_count_posts( 'post' );
	$articles = $articles && isset( $articles->publish ) ? (int) $articles->publish : 0;
	if ( $articles ) {
		$stats[] = array(
			'value'  => $articles,
			'figure' => 'Articles published',
			'label'  => 'All free to read, no paywall',
		);
	}

	if ( $value( 'monthly_readers' ) ) {
		$stats[] = array(
			'value'  => $value( 'monthly_readers' ),
			'figure' => 'Readers a month',
			'label'  => 'Locals and visitors who rely on us',
		);
	}

	if ( $value( 'newsletter' ) ) {
		$stats[] = array(
			'value'  => $value( 'newsletter' ),
			'figure' => 'On the mailing list',
			'label'  => 'Our weekly round-up, straight to the inbox',
		);
	}

	$events = wp_count_posts( 'event' );
	$events = $events && isset( $events->publish ) ? (int) $events->publish : 0;
	if ( $events ) {
		$stats[] = array(
			'value'  => $events,
			'figure' => 'Events listed',
			'label'  => 'Free to list and free to browse',
		);
	}

	return apply_filters( 'wa_subscribe_stats', $stats );
}

/**
 * What a supporter actually gets, with Material Symbols icons the theme already
 * loads in the header.
 */
function wa_subscribe_reasons() {
	return apply_filters(
		'wa_subscribe_reasons',
		array(
			array(
				'icon'  => 'block',
				'title' => 'No pop-ups, no paywalls',
				'copy'  => 'Our journalism stays free to read for everyone in Whitby — no obstructive advertising, and nothing hidden behind a paywall. Supporters are what makes that possible.',
			),
			array(
				'icon'  => 'bolt',
				'title' => 'Breaking news as it happens',
				'copy'  => 'A weekly round-up of everything that mattered, plus breaking news bulletins straight to your inbox when the story cannot wait.',
			),
			array(
				'icon'  => 'redeem',
				'title' => 'A new local offer every month',
				'copy'  => 'Supporters get a fresh offer from a local business every month — past offers have included free meals, family tickets and discounts around the town.',
			),
		)
	);
}

/**
 * Give the pricing columns in the page content an id, so the hero button can
 * jump straight to them. The block itself stays editable in WordPress.
 */
function wa_subscribe_anchor_pricing( $content ) {
	if ( is_admin() || ! is_page() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	if ( false === strpos( $content, 'wp-block-columns subscribe' ) ) {
		return $content;
	}

	return preg_replace(
		'/<div class="wp-block-columns subscribe/',
		'<div id="support" class="wp-block-columns subscribe',
		$content,
		1
	);
}
add_filter( 'the_content', 'wa_subscribe_anchor_pricing', 20 );
