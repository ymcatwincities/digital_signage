/**
 * @file
 * Provides OpenY Digital Signage playlist item edit form related behavior.
 */

(function ($, window, Drupal, drupalSettings, once) {

  'use strict';

  /**
   * Playlist item edit form behaviour.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the playlist item markup.
   */
  Drupal.behaviors.openyDigitalSignageEditPlaylistItem = {
    attach: function (context, settings) {
      // Playlist autocomplete input is displayed only when item type is 'playlist'.
      // To prevent issues with autocomplete validation we should clean this field for 'media' type.
      $(once('playlist', '.openy-ds-playlist-item-form select[name=type]', context)).on('change', function(e) {
        e.preventDefault();
        if ($(this).val() === 'media') {
          $('.form-type-entity-autocomplete input').val('');
        }
      });
    }
  };

})(jQuery, window, Drupal, drupalSettings, once);