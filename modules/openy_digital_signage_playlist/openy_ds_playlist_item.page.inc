<?php

/**
 * @file
 * Contains openy_ds_playlist_item.page.inc.
 *
 * Page callback for Digital Signage Playlist Item entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Digital Signage Playlist Item templates.
 *
 * Default template: openy_ds_playlist_item.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_openy_ds_playlist_item(array &$variables) {
  // Fetch OpenYPlaylistItem Entity Object.
  $openy_ds_playlist_item = $variables['elements']['#openy_ds_playlist_item'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
