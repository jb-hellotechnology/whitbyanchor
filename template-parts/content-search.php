<?php
/**
 * Template part for displaying results in search pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package whitbyanchor
 */
echo '<article class="flow">';
 echo '<figure>';
 the_post_thumbnail('full');
 echo '<figcaption>';
 the_post_thumbnail_caption();
 echo '</figcaption>';
 echo '</figure>';
 the_title('<h2>', '</h2>');
 echo '<div class="entry-meta">';
 echo '<img src="'; echo get_stylesheet_directory_uri(); echo '/icons/apple-icon-180x180.png" alt="Whitby Anchor" />';
 whitbyanchor_posted_on();
 whitbyanchor_posted_by();
 echo '</div>';
 echo '<p class="excerpt">' . get_the_excerpt() . '</p>';
 echo '<a class="article-link" href="' . get_the_permalink() . '"><span>Read: '.get_the_title().'</a>';
 echo '</article>';
?>