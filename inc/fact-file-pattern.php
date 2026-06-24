<?php
/**
 * "Fact File" block pattern.
 *
 * Registers a reusable pattern that inserts a styled container with a
 * heading, an introductory paragraph and a bullet point list. Authors
 * find it in the editor inserter under Patterns → Whitby Anchor, and
 * can edit every part inline once inserted.
 *
 * @package whitbyanchor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the pattern category and the Fact File pattern.
 */
function whitbyanchor_register_fact_file_pattern() {

	if ( ! function_exists( 'register_block_pattern' ) ) {
		return;
	}

	if ( function_exists( 'register_block_pattern_category' ) ) {
		register_block_pattern_category(
			'whitbyanchor',
			array( 'label' => __( 'Whitby Anchor', 'whitbyanchor' ) )
		);
	}

	register_block_pattern(
		'whitbyanchor/fact-file',
		array(
			'title'       => __( 'Fact File', 'whitbyanchor' ),
			'description' => __( 'A heading, an intro paragraph and a bullet point list in a styled box.', 'whitbyanchor' ),
			'categories'  => array( 'whitbyanchor' ),
			'keywords'    => array( 'fact', 'file', 'facts', 'box' ),
			'content'     => '
<!-- wp:group {"className":"fact-file"} -->
<div class="wp-block-group fact-file">
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Fact File</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Add a short introduction here.</p>
<!-- /wp:paragraph -->
<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li>First fact</li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li>Second fact</li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li>Third fact</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->
',
		)
	);
}
add_action( 'init', 'whitbyanchor_register_fact_file_pattern' );
