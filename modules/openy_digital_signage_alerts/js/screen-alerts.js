/**
 * @file
 * Provides OpenY Digital Signage alerts related behavior.
 */
(function ($, window, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the behavior to window object once.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Adds proper orientation classes to all the output layouts.
   */
  Drupal.behaviors.ds_alerts = {
    attach: function (context, settings) {
      checkAlerts(settings.ds.screenId);
    },
  };

  /**
   * Checks and redraw DS alerts.
   *
   * @param screenId
   *   DS screen ID.
   */
  function checkAlerts(screenId) {
    if (typeof screenId !== 'undefined' && screenId) {
      $.ajax({
        url: "/ajax/screen-alerts/redraw-alert/" + screenId,
      }).done(function(data) {
        if (data) {
          $('#openy-ds-alerts').replaceWith(data);
        }
        else {
          $('#openy-ds-alerts').empty();
        }
      });
    }

    // Set the function to infinite loop.
    setTimeout(checkAlerts, 60000);
  }

})(jQuery, window, Drupal, drupalSettings);
