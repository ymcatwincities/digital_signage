/**
 * @file
 * Provides OpenY Digital Signage playlist edit form related behavior.
 */

(function ($, window, Drupal, drupalSettings) {

  'use strict';

  /**
   * Playlist edit form behaviour.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the playlist markup.
   */
  Drupal.behaviors.openyDigitalSignageEditPlaylist = {
    attach: function (context, settings) {
      var playlistItems = $('.item-wrapper .openy_ds_playlist_item', context);
      var filtersElements = $('.playlist-filters .form-select', context);

      if (playlistItems.length === 0) {
        return;
      }

      // Add classes for parent table elements.
      playlistItems.each(function() {
        $(this).closest('tr.draggable').addClass('item-' + $(this).attr('data-item-status'));
      });

      // Apply filters on each behavior call with not empty playlist items.
      applyPlaylistItemsFilter(playlistItems, filtersElements);

      filtersElements.change(function() {
        // Apply filters on form select change.
        applyPlaylistItemsFilter(playlistItems, filtersElements);
      });
    }
  };

  /**
   * Playlist filters behaviour.
   */
  function applyPlaylistItemsFilter($playlistItems, $filtersElements) {
    // Get active filters.
    var filters = {};
    $filtersElements.each(function() {
      var selectedValue = $(this).val();
      if (selectedValue !== 'all') {
        filters[$(this).attr('data-filter-name')] = selectedValue;
      }
    });

    // Show all items.
    $playlistItems.each(function() {
      var tableRow = $(this).closest('tr.draggable');
      tableRow.css('display', 'table-row');
      tableRow.find('a.tabledrag-handle').show();
    });

    // Apply filters.
    var filterKeys = Object.keys(filters);
    if (filterKeys.length > 0) {
      $playlistItems.each(function() {
        var tableRow = $(this).closest('tr.draggable');
        // Disable rows draggable.
        tableRow.find('a.tabledrag-handle').hide();
        for (var i = 0, len = filterKeys.length; i < len; i++) {
          if ($(this).attr('data-item-' + filterKeys[i]) !== filters[filterKeys[i]]) {
            // Hide inappropriate items rows.
            tableRow.css('display', 'none');
          }
        }
      });
    }
  }

})(jQuery, window, Drupal, drupalSettings);
