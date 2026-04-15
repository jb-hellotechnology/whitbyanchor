<?php
/**
 * Template part for displaying results in search pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package whitbyanchor
 */


echo '<article class="flow">';
the_post_thumbnail('full');
the_title('<h2>', '</h2>');
echo '<div class="entry-meta">';
whitbyanchor_posted_on();
whitbyanchor_posted_by();
echo '</div>';
echo '<p class="excerpt">' . get_the_excerpt() . '</p>';
echo '<a class="article-link" href="'.get_the_permalink().'"></a>';
echo '</article>';

?>