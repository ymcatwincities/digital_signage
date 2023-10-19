/**
 * @file
 * Block behaviors.
 */
(function ($, window, Drupal, once) {

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

      $(once('BlockClassCurrent', '.block-complete-schedule', context)).each(function () {
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
      self.updateTime()
    };

    // Updates time.
    this.updateTime = function () {
      var timestamp = self.getTimeOffset();
      var time = moment(timestamp * 1000).tz('America/New_York');
      var hours = time.format('h');
      var minutes = time.format('mma');
      $('.schedule-time .hours', self.context).html(hours);
      $('.schedule-time .minutes', self.context).html(minutes);
      $('.schedule-time .separator', self.context).css({
        opacity: Math.floor(timestamp) % 2 ? 1 : 0.5,
      });
    };

    // Formats time.
    this.formatTime = function (seconds) {
      var hours, minutes;

      hours = Math.floor(seconds / 3600);
      minutes = Math.ceil((seconds - hours * 3600) / 60);

      var formatted = [];
      if (hours) {
        formatted.push(hours + 'h');
      }
      if (minutes) {
        formatted.push(minutes + 'm');
      }
      if (!formatted.length) {
        formatted.push('a moment');
      }

      return formatted.join(' ');
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
        progress = Math.max(0, Math.min(progress, 100));
        let past = offset > (to + 15);
        $(this)
            .find('.class-progress-bar')
            .css('width', progress + '%');
        if (offset < from) {
          let startsIn = from - offset;
          $(this)
              .find('.class-time-frame-in')
              .text('Starts in ' + self.formatTime(startsIn));
        }
        else if (past) {
          $(this).addClass('schedule-row-class-past');
        }
        else {
          let remaining = to - offset;
          let text = 'Just finished';
          if (remaining > 0) {
            text = self.formatTime(remaining) + ' remaining';
          }
          $(this)
              .addClass('schedule-row-class-ongoing')
              .find('.class-time-frame-in')
              .text(text);
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

})(jQuery, window, Drupal, once);
