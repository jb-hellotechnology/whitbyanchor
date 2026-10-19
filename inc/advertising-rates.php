<?php
/**
 * Data behind the Advertising Rates page.
 *
 * Two kinds of figures appear on that page:
 *
 *  - Derived figures, counted live from the site, so they are always accurate.
 *  - Supplied figures (traffic, newsletter, social), which only you can provide.
 *    Fill those in below. A figure left as null is simply left off the public
 *    page — logged-in editors see a reminder in its place instead, so nothing
 *    invented is ever shown to an advertiser.
 *
 * @package whitbyanchor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Audience figures that cannot be counted from the database.
 *
 * Take the traffic numbers from Fathom, the subscriber count from Brevo and the
 * follower count from your social accounts. Use whole numbers — they are
 * formatted for display automatically.
 */
function wa_advertising_supplied_stats() {
	$stats = array(
		'monthly_views'   => array(
			'value'  => null, // e.g. 48000
			'figure' => 'Page views a month',
			'label'  => 'Across news, What’s On and the directory',
			'source' => 'Fathom Analytics',
		),
		'monthly_readers' => array(
			'value'  => null, // e.g. 12500
			'figure' => 'Readers a month',
			'label'  => 'Locals and visitors planning a trip',
			'source' => 'Fathom Analytics',
		),
		'newsletter'      => array(
			'value'  => null, // e.g. 1400
			'figure' => 'Newsletter subscribers',
			'label'  => 'Opened in inboxes every week',
			'source' => 'Brevo',
		),
		'social'          => array(
			'value'  => null, // e.g. 6200
			'figure' => 'Social followers',
			'label'  => 'Facebook and Instagram combined',
			'source' => 'Your social accounts',
		),
	);

	return apply_filters( 'wa_advertising_supplied_stats', $stats );
}

/**
 * Figures counted straight from the site. These are always safe to publish.
 */
function wa_advertising_derived_stats() {
	$cached = get_transient( 'wa_advertising_derived_stats' );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$count = function ( $post_type ) {
		$counts = wp_count_posts( $post_type );
		return $counts && isset( $counts->publish ) ? (int) $counts->publish : 0;
	};

	$stats = array();

	$articles = $count( 'post' );
	if ( $articles ) {
		$stats['articles'] = array(
			'value'  => $articles,
			'figure' => 'Articles published',
			'label'  => 'Independent local journalism',
		);
	}

	$businesses = $count( 'business' );
	if ( $businesses ) {
		$stats['businesses'] = array(
			'value'  => $businesses,
			'figure' => 'Businesses listed',
			'label'  => 'In our growing local directory',
		);
	}

	$events = $count( 'event' );
	if ( $events ) {
		$stats['events'] = array(
			'value'  => $events,
			'figure' => 'Events listed',
			'label'  => 'Things to do across the area',
		);
	}

	$categories = get_categories( array( 'hide_empty' => true ) );
	if ( $categories ) {
		$stats['sections'] = array(
			'value'  => count( $categories ),
			'figure' => 'Sections and villages',
			'label'  => 'From Staithes to Ravenscar',
		);
	}

	set_transient( 'wa_advertising_derived_stats', $stats, HOUR_IN_SECONDS * 6 );

	return $stats;
}

/**
 * How long The Anchor has been publishing, as a readable phrase. Returns an
 * empty string until there is a published article to measure from.
 */
function wa_advertising_publishing_since() {
	$first = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);

	if ( empty( $first ) ) {
		return '';
	}

	return get_the_date( 'F Y', $first[0] );
}

/**
 * The advert placements an advertiser can buy, each with a small wireframe key
 * so they can see where their advert sits before they commit.
 */
function wa_advertising_placements() {
	return apply_filters(
		'wa_advertising_placements',
		array(
			array(
				'name'  => 'Homepage main advert',
				'blurb' => 'Full-width, image-led and impossible to miss, in the middle of the day’s news.',
				'shape' => 'wide',
			),
			array(
				'name'  => 'Section and village pages',
				'blurb' => 'Top and bottom of What’s On, Events, the villages, Wellbeing and Features.',
				'shape' => 'column',
			),
			array(
				'name'  => 'In-article advert',
				'blurb' => 'Dropped in after the third paragraph, at the same width as the story itself.',
				'shape' => 'inline',
			),
			array(
				'name'  => 'Sticky article advert',
				'blurb' => 'Follows the reader down the page on desktop, pinned to the bottom on mobile.',
				'shape' => 'sticky',
			),
		)
	);
}

/**
 * Everything that can be bought, grouped the way an advertiser reads it.
 *
 * Edit prices and features here — the page builds itself from this array.
 */
function wa_advertising_packages() {
	return apply_filters(
		'wa_advertising_packages',
		array(
			array(
				'id'    => 'advertorials',
				'title' => 'Advertorials',
				'intro' => 'Showcase your business and communicate your key messaging with a dedicated news article, written and photographed by us.',
				'cards' => array(
					array(
						'name'     => 'Advertorial',
						'blurb'    => 'An editorial-style article about your business, with professional photography.',
						'prices'   => array(
							array( 'term' => 'One-off', 'price' => '£500' ),
						),
						'features' => array(
							'Image gallery of 5 photographs',
							'Up to 400–500 words, written for you',
							'One week on the homepage',
							'Featured on our social media channels',
							'Online forever',
						),
					),
					array(
						'name'     => 'Advertorial + full media package',
						'badge'    => 'Most popular',
						'featured' => true,
						'blurb'    => 'For businesses ready for new, eye-catching images to use across their own social channels and website. Everything in the advertorial, plus a press-ready package.',
						'prices'   => array(
							array( 'term' => 'One-off', 'price' => '£900' ),
						),
						'features' => array(
							'Up to 20 images for your own use',
							'Words and press release supplied to you',
							'Featured on our social media channels',
							'One week on the homepage',
							'Featured in the weekly newsletter',
							'Online forever',
						),
					),
				),
			),
			array(
				'id'    => 'sponsorship',
				'title' => 'Page Sponsorship',
				'intro' => 'Your advert in the main slot on the pages your customers already visit, with a link straight to your website.',
				'cards' => array(
					array(
						'name'     => 'Headline pages',
						'blurb'    => 'Homepage, What’s On and Events — our three busiest pages.',
						'featured' => true,
						'prices'   => array(
							array( 'term' => 'Monthly', 'price' => '£300' ),
							array( 'term' => '6 months', 'price' => '£275', 'sub' => 'per month · £1,650 total' ),
							array( 'term' => '12 months', 'price' => '£250', 'sub' => 'per month · £3,000 total', 'best' => true ),
						),
						'features' => array(
							'Main advert slot with your image',
							'Direct link to your website',
							'Supporting copy alongside the advert',
							'We advise on placement to suit your audience',
						),
					),
					array(
						'name'     => 'Village, Wellbeing and Features pages',
						'blurb'    => 'Target a village or a single section and reach a sharply defined audience.',
						'prices'   => array(
							array( 'term' => 'Monthly', 'price' => '£50' ),
							array( 'term' => '6 months', 'price' => '£270' ),
							array( 'term' => '12 months', 'price' => '£480', 'best' => true ),
						),
						'features' => array(
							'Main advert slot on your chosen pages',
							'Direct link to your website',
							'Ideal for village-based businesses',
						),
					),
				),
			),
			array(
				'id'    => 'listings',
				'title' => 'Directory and What’s On Listings',
				'intro' => 'Year-round presence in the resources local people use to find businesses and decide what to do.',
				'cards' => array(
					array(
						'name'     => 'Business directory listing',
						'blurb'    => 'Our directory is becoming an invaluable resource for local people looking for businesses, tradespeople and organisations across every sector.',
						'prices'   => array(
							array( 'term' => 'Per year', 'price' => '£120' ),
						),
						'features' => array(
							'Business name and full description',
							'Image, website link, email and phone',
							'Map of your location',
							'Found by people already looking for you',
						),
					),
					array(
						'name'     => 'What’s On premium listing',
						'blurb'    => 'Promote your event, club, society, group or activity. Anyone may submit a free listing — a premium listing makes yours stand out.',
						'prices'   => array(
							array( 'term' => 'Weekly', 'price' => '£10' ),
							array( 'term' => 'Monthly', 'price' => '£30' ),
							array( 'term' => 'Yearly', 'price' => '£300', 'best' => true ),
						),
						'features' => array(
							'Larger, eye-catching design',
							'Image and a longer description',
							'Direct link to your own site',
							'Free listings include title, date, time and a short description',
						),
					),
				),
			),
		)
	);
}

/**
 * Why advertise — the three arguments, with Material Symbols icons that the
 * theme already loads in the header.
 */
function wa_advertising_reasons() {
	return apply_filters(
		'wa_advertising_reasons',
		array(
			array(
				'icon'  => 'newspaper',
				'title' => 'Readers who keep coming back',
				'copy'  => 'The Anchor is first and foremost a local news site. Content is updated regularly, so visitors return regularly — and see your advert again each time.',
			),
			array(
				'icon'  => 'groups',
				'title' => 'Locals and visitors alike',
				'copy'  => 'You reach people who live here and people planning a trip. We will advise on placement so your advert lands in front of the audience you actually want.',
			),
			array(
				'icon'  => 'handshake',
				'title' => 'Supporting local journalism',
				'copy'  => 'Advertising with us funds accurate, independent reporting in Whitby — and your customers know it.',
			),
		)
	);
}
