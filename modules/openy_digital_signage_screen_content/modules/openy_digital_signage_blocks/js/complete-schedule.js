/**
 * @file
 * Block behaviors.
 */
(function ($, window, Drupal) {

  'use strict';

  /**
   * Block current class storage.
   */
  Drupal.openyDigitalSignageBlocks.currentClass = Drupal.openyDigitalSignageBlocks.currentClass || {};

  /**
   * Static bar specific behaviour.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the block.
   */
  Drupal.behaviors.openyDigitalSignageBlockClassCurrent = {
    attach: function (context, settings) {
      $('.block-complete-schedule', context).once().each(function () {
        if (!(Drupal.openyDigitalSignageBlocks.completeSchedule instanceof OpenYDigitalSignageBlockCompleteSchedule)) {
          Drupal.openyDigitalSignageBlocks.completeSchedule = new OpenYDigitalSignageBlockCompleteSchedule(this);
        }
        Drupal.openyDigitalSignageBlocks.completeSchedule.deactivate();
        Drupal.openyDigitalSignageBlocks.completeSchedule.updateContext(this);
        Drupal.openyDigitalSignageBlocks.completeSchedule.init();
      });
    }
  };

  /**
   * Block current class handler.
   *
   * @param context
   *   Block.
   *
   * @returns {OpenYDigitalSignageBlockCompleteSchedule}
   */
  function OpenYDigitalSignageBlockCompleteSchedule(context) {
    var self = this;
    this.context = context;
    this.activated = 0;

    // General loop – basically swaps classes.
    this.loop = function () {

    };

    // Fast loop - basically updates the awaiting class.
    this.fastloop = function () {
      self.updateProgressBars();
    };

    // Formats time.
    this.formatTime = function (seconds) {
      var fHours, fMinutes, fSeconds, separator;
      separator = Math.floor(seconds) % 2 ? '<span class="separator">:</span>' : '<span class="separator odd">:</span>';
      if (seconds < 3600) {
        fMinutes = Math.floor(seconds / 60);
        fSeconds = Math.floor(seconds - fMinutes * 60);
        if (fSeconds < 10) fSeconds = '0' + fSeconds;
        if (fMinutes < 10) fMinutes = '0' + fMinutes;

        return {
          suffix: seconds > 59 ? 'minutes' : 'seconds',
          string: fMinutes + separator + fSeconds
        };
      }

      fHours = Math.floor(seconds / 3600);
      fMinutes = Math.floor((seconds - fHours * 3600) / 60);
      if (fMinutes < 10) fMinutes = '0' + fMinutes;

      return {
        suffix: 'hours',
        string: fHours + separator + fMinutes
      };
    };

    // Updates progress bar of the current class.
    this.updateProgressBars = function () {
      var $classes = $('.schedule-row-class', self.context);
      $classes.each(function () {
        let $class = $(this);
        let offset = self.getTimeOffset();
        let from = $class.data('from');
        let to = $class.data('to');
        let duration = to - from;
        let progress = 100 * (offset - from) / duration;
        let past = offset > (to + 5);
        progress = Math.max(0, Math.min(progress, 100));
        $(this)
            .find('.class-progress-bar')
            .css('width', progress + '%')
        if (offset >= from && offset <= to) {
          $(this).addClass('schedule-row-class-ongoing');
        }
        if (past) {
          $(this).addClass('schedule-row-class-past');
        }
      });
    };

    /**
     * Returns current time.
     */
    this.getTimeOffset = function () {
      return window.tm.getTime();
    };

    /**
     * Activates the block.
     */
    this.activate = function () {
      self.fastloop();
      self.timer = setInterval(self.loop, 5000);
      self.fasttimer = setInterval(self.fastloop, 1000 / window.tm.speed);
      self.activated = self.getTimeOffset();
    };

    /**
     * Deactivates the block.
     */
    this.deactivate = function () {
      clearInterval(self.timer);
      clearInterval(self.fasttimer);
      self.activated = 0;
    };

    /**
     * Initialize block.
     */
    this.init = function () {
      self.blockObject = ObjectsManager.getObject(self.context);
      self.blockObject.activate = self.activate;
      self.blockObject.deactivate = self.deactivate;
      if (self.blockObject.isActive() || $(self.context).parents('.screen').length === 0) {
        self.activate();
      }
    };

    /**
     * Check is block active and initialized or not.
     *
     * @returns {boolean}
     *   Status.
     */
    this.isActive = function () {
      return self.activated !== 0;
    };

    /**
     * Update class context.
     *
     * @param context
     *   Block.
     */
    this.updateContext = function (context) {
      this.context = context;
      self = this;
    };

    return this;
  }

})(jQuery, window, Drupal);
