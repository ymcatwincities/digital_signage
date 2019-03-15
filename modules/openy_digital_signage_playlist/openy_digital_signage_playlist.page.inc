<?php

/**
 * @file
 * Contains openy_digital_signage_playlist.page.inc.
 *
 * Page callback for Digital Signage Playlist entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Digital Signage Playlist templates.
 *
 * Default template: openy_digital_signage_playlist.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_openy_digital_signage_playlist(array &$variables) {
  // Fetch OpenYPlaylist Entity Object.
  $openy_digital_signage_playlist = $variables['elements']['#openy_digital_signage_playlist'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
