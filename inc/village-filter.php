<?php
/**
 * Village filter for the Villages category page.
 *
 * The individual village categories are children of Villages. This groups
 * them into the combined areas the paper reports on and renders a dropdown
 * at the top of /category/villages/ that narrows the article grid down to
 * one of those areas.
 *
 * @package whitbyanchor
 */

define( 'WHITBYANCHOR_VILLAGE_PARENT_SLUG', 'villages' );
define( 'WHITBYANCHOR_VILLAGE_QUERY_VAR', 'village' );

/**
 * The combined village areas, in the order they appear in the dropdown.
 *
 * Each 'slugs' entry is a child category slug of Villages. Slugs without a
 * category yet are ignored, so a village joins its group automatically as
 * soon as its category is created.
 *
 * @return array<string, array{label: string, slugs: string[]}>
 */
function whitbyanchor_village_groups(): array {
	$groups = [
		'robin-hoods-bay' => [
			'label' => 'Robin Hood&rsquo;s Bay, Fylingthorpe and Raw',
			'slugs' => [ 'robin-hoods-bay', 'fylingthorpe', 'raw' ],
		],
		'hawsker' => [
			'label' => 'Hawsker and Stainsacre',
			'slugs' => [ 'hawsker', 'stainsacre' ],
		],
		'ruswarp' => [
			'label' => 'Ruswarp and Sneaton',
			'slugs' => [ 'ruswarp', 'sneaton' ],
		],
		'sleights' => [
			'label' => 'Sleights, Briggswath and Ugglebarnby',
			'slugs' => [ 'sleights', 'briggswath', 'ugglebarnby' ],
		],
		'sandsend' => [
			'label' => 'Sandsend and Lythe',
			'slugs' => [ 'sandsend', 'lythe' ],
		],
		'staithes' => [
			'label' => 'Staithes, Runswick and Port Mulgrave',
			'slugs' => [ 'staithes', 'runswick-bay', 'runswick', 'port-mulgrave' ],
		],
		'hinderwell' => [
			'label' => 'Hinderwell, Ellerby, Mickleby, East Barnby and Roxby',
			'slugs' => [ 'hinderwell', 'ellerby', 'mickleby', 'east-barnby', 'roxby' ],
		],
		'egton' => [
			'label' => 'Egton, Egton Bridge and Aislaby',
			'slugs' => [ 'egton', 'egton-bridge', 'aislaby' ],
		],
	];

	return apply_filters( 'whitbyanchor_village_groups', $groups );
}

/**
 * Every village category: the children of the Villages category.
 *
 * @return WP_Term[] Keyed by slug.
 */
function whitbyanchor_village_terms(): array {
	static $terms = null;

	if ( null !== $terms ) {
		return $terms;
	}

	$terms  = [];
	$parent = get_category_by_slug( WHITBYANCHOR_VILLAGE_PARENT_SLUG );

	if ( ! $parent ) {
		return $terms;
	}

	$children = get_terms( [
		'taxonomy'   => 'category',
		'parent'     => $parent->term_id,
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	] );

	if ( is_wp_error( $children ) ) {
		return $terms;
	}

	foreach ( $children as $child ) {
		$terms[ $child->slug ] = $child;
	}

	return $terms;
}

/**
 * The dropdown options: the combined groups that have at least one existing
 * category, followed by any village category not covered by a group.
 *
 * @return array<string, array{label: string, term_ids: int[]}>
 */
function whitbyanchor_village_filter_options(): array {
	$villages = whitbyanchor_village_terms();
	$options  = [];
	$grouped  = [];

	foreach ( whitbyanchor_village_groups() as $key => $group ) {
		$term_ids = [];

		foreach ( $group['slugs'] as $slug ) {
			if ( isset( $villages[ $slug ] ) ) {
				$term_ids[]      = $villages[ $slug ]->term_id;
				$grouped[ $slug ] = true;
			}
		}

		// A group with no categories yet would be a dead end, so leave it out.
		if ( $term_ids ) {
			$options[ $key ] = [
				'label'    => $group['label'],
				'term_ids' => $term_ids,
			];
		}
	}

	// Villages that aren't in any group keep an entry of their own.
	foreach ( $villages as $slug => $term ) {
		if ( ! isset( $grouped[ $slug ] ) ) {
			$options[ $slug ] = [
				'label'    => $term->name,
				'term_ids' => [ $term->term_id ],
			];
		}
	}

	return $options;
}

/**
 * The group currently selected in the dropdown, if any.
 *
 * @return array{key: string, label: string, term_ids: int[]}|null
 */
function whitbyanchor_current_village_group(): ?array {
	$requested = isset( $_GET[ WHITBYANCHOR_VILLAGE_QUERY_VAR ] )
		? sanitize_title( wp_unslash( $_GET[ WHITBYANCHOR_VILLAGE_QUERY_VAR ] ) )
		: '';

	if ( '' === $requested ) {
		return null;
	}

	$options = whitbyanchor_village_filter_options();

	if ( ! isset( $options[ $requested ] ) ) {
		return null;
	}

	return [ 'key' => $requested ] + $options[ $requested ];
}

/**
 * The dropdown itself. Submits with GET, so it works without JavaScript.
 *
 * @param string|null $selected Currently selected option key.
 */
function whitbyanchor_render_village_filter( ?string $selected = null ): void {
	$options = whitbyanchor_village_filter_options();

	if ( count( $options ) < 2 ) {
		return;
	}

	$parent = get_category_by_slug( WHITBYANCHOR_VILLAGE_PARENT_SLUG );
	$action = $parent ? get_category_link( $parent->term_id ) : home_url( '/' );
	?>
	<form class="village-filter" method="get" action="<?php echo esc_url( $action ); ?>">
		<div>
			<label for="village-filter-select">
				<?php esc_html_e( 'Filter by village', 'whitbyanchor' ); ?>
			</label>
			<select id="village-filter-select" name="<?php echo esc_attr( WHITBYANCHOR_VILLAGE_QUERY_VAR ); ?>">
				<option value=""><?php esc_html_e( 'All villages', 'whitbyanchor' ); ?></option>
				<?php foreach ( $options as $key => $option ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected, $key ); ?>>
						<?php echo wp_kses( $option['label'], [] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<button type="submit"><?php esc_html_e( 'Show articles', 'whitbyanchor' ); ?></button>
	</form>
	<script>
	( function () {
		var form = document.querySelector( 'form.village-filter' );
		if ( ! form ) {
			return;
		}
		// The submit button is the no-JS fallback; hide it once we can auto-submit.
		form.classList.add( 'village-filter--auto' );
		form.querySelector( 'select' ).addEventListener( 'change', function () {
			form.submit();
		} );
	} )();
	</script>
	<?php
}
