<?php
/**
 * Photo Gallery Custom Post Type
 *
 * A single post type (npg_gallery) where images are added directly in the
 * block editor using standard Image or Gallery blocks. On the front end,
 * images are wrapped in a <div class="npg-gallery__images"> so your
 * JavaScript gallery library (Lightbox, Swiper, GLightbox, etc.) can
 * initialise against a predictable selector.
 *
 * HOW TO INSTALL:
 *   1. Copy this file to your-theme/inc/photo-gallery-cpt.php
 *   2. Add to functions.php:
 *          require_once get_template_directory() . '/inc/photo-gallery-cpt.php';
 *   3. Visit Settings → Permalinks → Save Changes
 *
 * ADMIN WORKFLOW:
 *   • Go to Photo Galleries → Add New Gallery
 *   • Fill in the Title (heading) and the Excerpt (short description shown
 *     in archive and home-page widgets — keep it to 1–2 sentences)
 *   • Add your images in the body using Image blocks or a Gallery block
 *   • Optionally set a Featured Image to use as the archive/home cover
 *
 * URL STRUCTURE:
 *   /photo-galleries/          ← paginated list of all galleries
 *   /photo-galleries/paris/    ← single gallery page
 *
 * TEMPLATE TAGS:
 *   npg_render_gallery_single()    single-npg_gallery.php
 *   npg_render_gallery_archive()   archive-npg_gallery.php
 *   npg_render_latest_gallery()    front-page.php / home page
 *
 * JS GALLERY HOOK:
 *   All images inside a gallery page are wrapped in:
 *       <div class="npg-gallery__images" data-gallery>
 *   Initialise your library against that selector, e.g.:
 *       GLightbox({ selector: '.npg-gallery__images a' });
 *       new Swiper('.npg-gallery__images', { ... });
 */

// =============================================================================
// 1. REGISTER POST TYPE
// =============================================================================

function npg_register_gallery_post_type(): void {

    $labels = [
        'name'               => _x( 'Photo Galleries',  'post type general name', 'newspaper' ),
        'singular_name'      => _x( 'Photo Gallery',    'post type singular name', 'newspaper' ),
        'menu_name'          => _x( 'Photo Galleries',  'admin menu',             'newspaper' ),
        'name_admin_bar'     => _x( 'Gallery',          'add new on admin bar',   'newspaper' ),
        'add_new'            => __( 'Add New Gallery',  'newspaper' ),
        'add_new_item'       => __( 'Add New Gallery',  'newspaper' ),
        'edit_item'          => __( 'Edit Gallery',     'newspaper' ),
        'view_item'          => __( 'View Gallery',     'newspaper' ),
        'all_items'          => __( 'All Galleries',    'newspaper' ),
        'search_items'       => __( 'Search Galleries', 'newspaper' ),
        'not_found'          => __( 'No galleries found.',    'newspaper' ),
        'not_found_in_trash' => __( 'No galleries in trash.', 'newspaper' ),
    ];

    register_post_type( 'npg_gallery', [
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,    // required for the block editor
        'query_var'          => true,
        'rewrite'            => [ 'slug' => 'photo-galleries', 'with_front' => false ],
        'capability_type'    => 'post',
        'has_archive'        => 'photo-galleries',
        'hierarchical'       => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-images-alt2',
        'supports'           => [
            'title',       // gallery heading
            'editor',      // image blocks go here
            'excerpt',     // short description for archive / home page
            'thumbnail',   // cover image for archive cards
            'author',
            'revisions',
        ],
    ] );
}
add_action( 'init', 'npg_register_gallery_post_type' );


// =============================================================================
// 2. FLUSH REWRITE RULES ON THEME ACTIVATION
// =============================================================================

function npg_maybe_flush_rewrite_rules(): void {
    if ( get_transient( 'npg_flush_rewrite_rules' ) ) {
        return;
    }
    npg_register_gallery_post_type();
    flush_rewrite_rules();
    set_transient( 'npg_flush_rewrite_rules', true, HOUR_IN_SECONDS );
}
add_action( 'after_switch_theme', 'npg_maybe_flush_rewrite_rules' );


// =============================================================================
// 3. HELPERS
// =============================================================================

/**
 * Split a gallery post's block content into two parts:
 *
 *   'images'  — rendered HTML for image/gallery blocks only
 *   'other'   — rendered HTML for everything else (paragraphs, headings, etc.)
 *
 * This lets the template place images inside the JS-gallery wrapper div while
 * keeping any additional copy outside it. If the post was not created with
 * the block editor (classic content), all content is returned under 'other'.
 *
 * @param  int|WP_Post|null $post  Defaults to the global post.
 * @return array{ images: string, other: string }
 */
function npg_split_gallery_content( $post = null ): array {
    $post = get_post( $post );

    $result = [ 'images' => '', 'other' => '' ];

    if ( ! $post ) {
        return $result;
    }

    // Image-related block names.
    $image_block_types = [
        'core/image',
        'core/gallery',
        'core/media-text',
    ];

    if ( ! function_exists( 'parse_blocks' ) || ! has_blocks( $post->post_content ) ) {
        // Classic editor fallback — return everything as 'other'.
        $result['other'] = apply_filters( 'the_content', $post->post_content );
        return $result;
    }

    $blocks = parse_blocks( $post->post_content );

    foreach ( $blocks as $block ) {
        if ( in_array( $block['blockName'], $image_block_types, true ) ) {
            $block = npg_upsize_image_block( $block );
        }
        $rendered = render_block( $block );

        if ( in_array( $block['blockName'], $image_block_types, true ) ) {
            $result['images'] .= $rendered;
        } else {
            // Skip empty blocks (whitespace-only).
            if ( '' !== trim( wp_strip_all_tags( $rendered ) ) ) {
                $result['other'] .= $rendered;
            }
        }
    }

    return $result;
}

function npg_upsize_image_block( array $block, string $size = 'full' ): array {
    switch ( $block['blockName'] ) {

        case 'core/image':
            $block = npg_replace_image_src( $block, $size );
            break;

        case 'core/gallery':
            $block['attrs']['sizeSlug'] = $size;
            foreach ( $block['innerBlocks'] as &$inner ) {
                if ( 'core/image' === $inner['blockName'] ) {
                    $inner = npg_replace_image_src( $inner, $size );
                }
            }
            unset( $inner );
            break;

        case 'core/media-text':
            $block['attrs']['mediaSizeSlug'] = $size;
            $block = npg_replace_image_src( $block, $size );
            break;
    }
    return $block;
}

function npg_replace_image_src( array $block, string $size ): array {
    $id = $block['attrs']['id'] ?? null;
    if ( ! $id ) {
        return $block;
    }

    $new_src = wp_get_attachment_image_url( $id, $size );
    if ( ! $new_src ) {
        return $block;
    }

    // Replace the src URL in the raw block HTML.
    $block['innerHTML'] = preg_replace(
        '/(<img[^>]+src=")[^"]+(")/i',
        '$1' . $new_src . '$2',
        $block['innerHTML']
    );

    // innerContent mirrors innerHTML for leaf blocks — update it too.
    foreach ( $block['innerContent'] as &$chunk ) {
        if ( is_string( $chunk ) ) {
            $chunk = preg_replace(
                '/(<img[^>]+src=")[^"]+(")/i',
                '$1' . $new_src . '$2',
                $chunk
            );
        }
    }
    unset( $chunk );

    $block['attrs']['sizeSlug'] = $size;

    return $block;
}


/**
 * Count the images inside a gallery post's content.
 *
 * Parses the block structure; falls back to counting <img> tags for classic
 * content.
 *
 * @param  int|WP_Post|null $post  Defaults to the global post.
 * @return int
 */
function npg_count_gallery_images( $post = null ): int {
    $post = get_post( $post );
    if ( ! $post ) {
        return 0;
    }

    if ( ! function_exists( 'parse_blocks' ) || ! has_blocks( $post->post_content ) ) {
        // Classic fallback: count <img> tags.
        preg_match_all( '/<img\s/i', $post->post_content, $m );
        return count( $m[0] );
    }

    $count       = 0;
    $image_types = [ 'core/image', 'core/gallery', 'core/media-text' ];

    foreach ( parse_blocks( $post->post_content ) as $block ) {
        if ( ! in_array( $block['blockName'], $image_types, true ) ) {
            continue;
        }
        if ( 'core/gallery' === $block['blockName'] ) {
            // A Gallery block contains one core/image inner block per image.
            $count += count( $block['innerBlocks'] );
        } else {
            $count++;
        }
    }

    return $count;
}


/**
 * Return previous and next published gallery posts relative to the given one.
 *
 * @param  int|WP_Post|null $post  Defaults to the global post.
 * @return array{ prev: WP_Post|null, next: WP_Post|null }
 */
function npg_get_adjacent_galleries( $post = null ): array {
    $post = get_post( $post );

    if ( ! $post ) {
        return [ 'prev' => null, 'next' => null ];
    }

    // get_adjacent_post() uses the global post, so set it temporarily.
    $backup = $GLOBALS['post'];
    $GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

    $prev = get_adjacent_post( false, '', true,  'date' ); // older
    $next = get_adjacent_post( false, '', false, 'date' ); // newer

    $GLOBALS['post'] = $backup;

    // get_adjacent_post returns '' when none exists.
    return [
        'prev' => ( $prev instanceof WP_Post ) ? $prev : null,
        'next' => ( $next instanceof WP_Post ) ? $next : null,
    ];
}


// =============================================================================
// 4. TEMPLATE TAG — single gallery page
// =============================================================================

/**
 * Render a single gallery page.
 *
 * Outputs:
 *   • Gallery heading and meta
 *   • Short description (excerpt), if set
 *   • Any non-image block content (captions, text, etc.)
 *   • All image/gallery blocks inside <div class="npg-gallery__images">
 *   • Previous / Next gallery navigation
 *
 * Usage in single-npg_gallery.php:
 *   <?php npg_render_gallery_single(); ?>
 *
 * @param int|WP_Post|null $post  Defaults to the global post.
 */
function npg_render_gallery_single( $post = null ): void {
    $post = get_post( $post );
    if ( ! $post || 'npg_gallery' !== $post->post_type ) {
        return;
    }

    $title       = get_the_title( $post );
    $excerpt     = $post->post_excerpt ? wpautop( $post->post_excerpt ) : '';
    $date        = get_the_date( 'l F j Y', $post );
    $author      = get_the_author_meta( 'display_name', $post->post_author );
    $img_count   = npg_count_gallery_images( $post );
    $content     = npg_split_gallery_content( $post );
    $adjacent    = npg_get_adjacent_galleries( $post );
    ?>

    <article id="npg-gallery-<?php echo esc_attr( $post->ID ); ?>"
             class="npg-gallery npg-gallery--single flow">

        <?php // ── Header ─────────────────────────────────────────────────── ?>
        <header class="npg-gallery__header flow">
            <h1 class="npg-gallery__title"><?php echo esc_html( $title ); ?></h1>
            <div class="entry-meta">
                <img src="<?php echo get_stylesheet_directory_uri(); ?>/icons/apple-icon-180x180.png" alt="Whitby Anchor" />
                <time datetime="<?php echo esc_attr( get_the_date( 'c', $post ) ); ?>">
                    <?php echo esc_html( $date ); ?>
                </time>
                <?php if ( $author ) : ?>
                    by <span class="npg-gallery__author author"><?php echo esc_html( $author ); ?></span>
                <?php endif; ?>
                <?php if ( $img_count > 0 ) : ?>
                    &mdash;
                    <span class="npg-gallery__count">
                        <?php printf(
                            esc_html( _n( '%s photo', '%s photos', $img_count, 'newspaper' ) ),
                            esc_html( number_format_i18n( $img_count ) )
                        ); ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if ( $excerpt ) : ?>
                <div class="npg-gallery__excerpt">
                    <?php echo wp_kses_post( $excerpt ); ?>
                </div>
            <?php endif; ?>
        </header>

        <?php // ── Any non-image block content ────────────────────────────── ?>
        <?php if ( $content['other'] ) : ?>
            <div class="npg-gallery__content">
                <?php echo wp_kses_post( $content['other'] ); ?>
            </div>
        <?php endif; ?>

        <?php // ── Images — wrapped for JS gallery ───────────────────────── ?>
        <?php if ( $content['images'] ) : ?>
            <div class="npg-gallery__images" id="myCarousel" data-gallery>
                <?php echo wp_kses_post( $content['images'] ); ?>
            </div>
        <?php endif; ?>

    </article>

    <?php
}


// =============================================================================
// 5. TEMPLATE TAG — gallery archive
// =============================================================================

/**
 * Render a paginated list of all published galleries.
 *
 * Usage in archive-npg_gallery.php:
 *   <?php npg_render_gallery_archive(); ?>
 *
 * @param array $args  Optional WP_Query argument overrides, e.g.:
 *                       [ 'posts_per_page' => 12 ]
 */
function npg_render_gallery_archive( array $args = [] ): void {

    $paged    = max( 1, get_query_var( 'paged' ) );
    $defaults = [
        'post_type'      => 'npg_gallery',
        'post_status'    => 'publish',
        'posts_per_page' => 9,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'paged'          => $paged,
    ];

    $query = new WP_Query( array_merge( $defaults, $args ) );

    if ( ! $query->have_posts() ) {
        echo '<p class="npg-archive__empty">'
           . esc_html__( 'No photo galleries have been published yet.', 'newspaper' )
           . '</p>';
        return;
    }
    ?>

    <section class="npg-archive npg-archive--galleries flow">

        <header class="npg-archive__header flow">
            <h1 class="npg-archive__title">
                <?php esc_html_e( 'Photo Galleries', 'newspaper' ); ?>
            </h1>
        </header>

        <section class="articles">

            <?php while ( $query->have_posts() ) : $query->the_post();
                $gid       = get_the_ID();
                $img_count = npg_count_gallery_images( $gid );
                $cover     = get_the_post_thumbnail( $gid, 'medium', [
                    'class' => 'npg-gallery__card-image',
                    'alt'   => esc_attr( get_the_title() ),
                ] );
                ?>

                <article class="article npg-gallery npg-gallery--card flow">

                <?php
                    the_post_thumbnail('full');
                    the_title('<h2>', '</h2>');
                    echo '<div class="entry-meta">';
                    echo '<img src="'; echo get_stylesheet_directory_uri(); echo '/icons/apple-icon-180x180.png" alt="Whitby Anchor" />';
                    whitbyanchor_posted_on();
                    whitbyanchor_posted_by();
                    echo '</div>';
                    echo '<p class="excerpt">' . get_the_excerpt() . '</p>';
                    echo '<a class="article-link flow" href="'.get_the_permalink().'"></a>';
                    echo '</article>';
                ?>
                       

                </article>

            <?php endwhile; ?>

        </section>

        <?php
        $pagination = paginate_links( [
            'base'      => str_replace( PHP_INT_MAX, '%#%', esc_url( get_pagenum_link( PHP_INT_MAX ) ) ),
            'format'    => '?paged=%#%',
            'current'   => $paged,
            'total'     => $query->max_num_pages,
            'prev_text' => __( '&larr; Previous', 'newspaper' ),
            'next_text' => __( 'Next &rarr;', 'newspaper' ),
        ] );
        if ( $pagination ) : ?>
            <nav class="npg-archive__pagination"
                 aria-label="<?php esc_attr_e( 'Gallery pages', 'newspaper' ); ?>">
                <?php echo $pagination; ?>
            </nav>
        <?php endif; ?>

    </section>

    <?php
    wp_reset_postdata();
}


// =============================================================================
// 6. TEMPLATE TAG — latest gallery (home page)
// =============================================================================

/**
 * Render the most recently published gallery as a featured home-page block.
 *
 * Usage in front-page.php (or any template):
 *   <?php npg_render_latest_gallery(); ?>
 *
 * Capture as a string instead of echoing:
 *   $html = npg_render_latest_gallery( [ 'echo' => false ] );
 *
 * @param array $args {
 *     @type bool   $echo            Whether to echo output. Default true.
 *     @type string $thumbnail_size  Featured image size. Default 'large'.
 *     @type string $heading         Section label. Default 'Latest Gallery'.
 *     @type int    $preview_count   How many preview thumbs to show. Default 5.
 * }
 * @return string|void  HTML when $echo is false.
 */
function npg_render_latest_gallery( array $args = [] ) {

    $args = wp_parse_args( $args, [
        'echo'           => true,
        'thumbnail_size' => 'full',
        'heading'        => __( 'Latest Gallery', 'newspaper' ),
        'preview_count'  => 5,
        'post_id'        => 0,   // add this
    ] );

    if ( $args['post_id'] ) {
        $post = get_post( $args['post_id'] );
        if ( ! $post || 'npg_gallery' !== $post->post_type || 'publish' !== $post->post_status ) {
            return $args['echo'] ? null : '';
        }
        setup_postdata( $GLOBALS['post'] = $post );
    } else {
        $query = new WP_Query( [
            'post_type'      => 'npg_gallery',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'no_found_rows'  => true,
        ] );
    
        if ( ! $query->have_posts() ) {
            return $args['echo'] ? null : '';
        }
    
        $query->the_post();
    }

    if ( ! $query->have_posts() ) {
        return $args['echo'] ? null : '';
    }

    $query->the_post();

    $gid         = get_the_ID();
    $title       = get_the_title();
    $permalink   = get_permalink();
    $description = has_excerpt()
                   ? get_the_excerpt()
                   : wp_trim_words( get_the_content(), 25, '&hellip;' );
    $cover       = get_the_post_thumbnail( $gid, $args['thumbnail_size'], [
        'class' => 'npg-latest-gallery__cover',
        'alt'   => esc_attr( $title ),
    ] );
    $img_count   = npg_count_gallery_images( $gid );
    $date        = get_the_date( 'l F j Y', $post );

    // Collect preview thumbnails from image blocks inside the post.
    $previews = [];
    if ( has_blocks( get_the_content() ) && $args['preview_count'] > 0 ) {
        foreach ( parse_blocks( get_the_content() ) as $block ) {
            if ( count( $previews ) >= $args['preview_count'] ) {
                break;
            }
            if ( 'core/image' === $block['blockName'] ) {
                $attach_id = $block['attrs']['id'] ?? 0;
                if ( $attach_id ) {
                    $previews[] = wp_get_attachment_image( $attach_id, 'thumbnail', false, [
                        'class' => 'npg-latest-gallery__preview-image',
                        'alt'   => esc_attr( $block['attrs']['alt'] ?? '' ),
                    ] );
                }
            } elseif ( 'core/gallery' === $block['blockName'] ) {
                foreach ( $block['innerBlocks'] as $inner ) {
                    if ( count( $previews ) >= $args['preview_count'] ) {
                        break;
                    }
                    $attach_id = $inner['attrs']['id'] ?? 0;
                    if ( $attach_id ) {
                        $previews[] = wp_get_attachment_image( $attach_id, 'thumbnail', false, [
                            'class' => 'npg-latest-gallery__preview-image',
                            'alt'   => esc_attr( $inner['attrs']['alt'] ?? '' ),
                        ] );
                    }
                }
            }
        }
    }

    wp_reset_postdata();

    ob_start();
    ?>

    <section class="npg-latest-gallery flow">

        <h2 class="npg-latest-gallery__section-heading">
            <?php echo esc_html( $title ); ?>
        </h2>

        <article class="npg-gallery npg-gallery--featured">

            <?php if ( $cover ) : ?>
                <?php echo $cover; ?>
            <?php endif; ?>

            <div class="npg-latest-gallery__body flow">

                <!-- <h3 class="npg-latest-gallery__title">
                    <a href="<?php echo esc_url( $permalink ); ?>">
                        <?php echo esc_html( $title ); ?>
                    </a>
                </h3> -->

                <div class="entry-meta">
                    <img src="<?php echo get_stylesheet_directory_uri(); ?>/icons/apple-icon-180x180.png" alt="Whitby Anchor" />
                    <time datetime="<?php echo esc_attr( get_the_date( 'l F j Y', $gid ) ); ?>">
                        <?php echo esc_html( $date ); ?>
                    </time>
                    <?php if ( $img_count > 0 ) : ?>
                        &mdash;
                        <?php printf(
                            esc_html( _n( '%s photo', '%s photos', $img_count, 'newspaper' ) ),
                            esc_html( number_format_i18n( $img_count ) )
                        ); ?>
                    <?php endif; ?>
                </div>

                <?php if ( $description ) : ?>
                    <p class="npg-latest-gallery__description">
                        <?php echo esc_html( $description ); ?>
                    </p>
                <?php endif; ?>

                <?php if ( ! empty( $previews ) ) : ?>
                    <!-- <ul class="npg-latest-gallery__previews"
                        aria-label="<?php esc_attr_e( 'Preview photos', 'newspaper' ); ?>">
                        <?php foreach ( $previews as $thumb ) : ?>
                            <li class="npg-latest-gallery__preview-item">
                                <a href="<?php echo esc_url( $permalink ); ?>">
                                    <?php echo $thumb; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul> -->
                <?php endif; ?>

                <!-- <a class="npg-latest-gallery__cta" href="<?php echo esc_url( $permalink ); ?>">
                    <?php esc_html_e( 'View Gallery &rarr;', 'newspaper' ); ?>
                </a>

                <a class="npg-latest-gallery__archive-link"
                   href="<?php echo esc_url( get_post_type_archive_link( 'npg_gallery' ) ); ?>">
                    <?php esc_html_e( 'Browse All Galleries', 'newspaper' ); ?>
                </a> -->

            </div>

        </article>

        <a class="npg-latest-gallery__cover-link"
           href="<?php echo esc_url( $permalink ); ?>">
        </a>
    </section>

    <?php

    $output = ob_get_clean();

    if ( $args['echo'] ) {
        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    } else {
        return $output;
    }
}


// =============================================================================
// 7. SUGGESTED THEME TEMPLATE FILES
// =============================================================================
/*

Create the following three files in your theme folder.

─── single-npg_gallery.php ───────────────────────────────────────────────────

<?php
get_header();
while ( have_posts() ) : the_post();
    npg_render_gallery_single();
endwhile;
get_footer();


─── archive-npg_gallery.php ──────────────────────────────────────────────────

<?php
get_header();
npg_render_gallery_archive();
get_footer();


─── front-page.php (add wherever you want the latest gallery block) ──────────

<?php
// ...other home page content...
npg_render_latest_gallery();
// ...



JS GALLERY INITIALISATION EXAMPLE
──────────────────────────────────
The images on a gallery page are wrapped in:

    <div class="npg-gallery__images" data-gallery>

Use that selector with any JS gallery library. Examples:

    // GLightbox
    GLightbox({ selector: '.npg-gallery__images a' });

    // Swiper (wrap in .swiper first, images become .swiper-slide)
    new Swiper('.npg-gallery__images', { loop: true, navigation: true });

    // Fancybox 5
    Fancybox.bind('.npg-gallery__images a', { groupAll: true });

    // Simple custom data attribute targeting
    document.querySelectorAll('[data-gallery] img')

*/

function npg_enqueue_carousel_assets(): void {
    if ( ! is_singular( 'npg_gallery' ) ) {
        return;
    }

    // Carousel
    wp_enqueue_style(
        'fancyapps-carousel',
        'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/carousel/carousel.css',
        [], '5'
    );
    wp_enqueue_script(
        'fancyapps-carousel',
        'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/carousel/carousel.umd.js',
        [], '5', true
    );

    // Fancybox lightbox
    wp_enqueue_style(
        'fancyapps-fancybox',
        'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/fancybox/fancybox.css',
        [], '5'
    );
    wp_enqueue_script(
        'fancyapps-fancybox',
        'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/fancybox/fancybox.umd.js',
        [], '5', true
    );

    // Thumbs plugin (CSS + JS)
    wp_enqueue_style(
        'fancyapps-thumbs',
        'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/carousel/carousel.thumbs.css',
        [ 'fancyapps-fancybox' ], '5'
    );
    wp_enqueue_script(
        'fancyapps-thumbs',
        'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5/dist/carousel/carousel.thumbs.umd.js',
        [ 'fancyapps-fancybox' ], '5', true
    );

    // Your init script
    wp_enqueue_script(
        'npg-gallery-init',
        get_template_directory_uri() . '/js/gallery-init.js?v='.rand(),
        [ 'fancyapps-carousel', 'fancyapps-fancybox', 'fancyapps-thumbs' ],
        '1.1',
        true
    );
}
add_action( 'wp_enqueue_scripts', 'npg_enqueue_carousel_assets' );