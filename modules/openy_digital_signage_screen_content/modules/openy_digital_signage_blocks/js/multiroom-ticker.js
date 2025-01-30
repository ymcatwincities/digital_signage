/**
 * @file
 * Block behaviors.
 */
(function ($, window, Drupal, once) {

  'use strict';

  /**
   * Static bar specific behaviour.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the block.
   */
  Drupal.behaviors.openyDigitalSignageBlockMultiroomTicker = {
    attach: function (context, settings) {
      $(once('BlockMultiroomTicker', '.ds-block-class-multiroom-ticker', context)).each(function () {
        var blockProto = new OpenYDigitalSignageBlockMultiroomTicker(this);
      });
    }
  };

  function OpenYDigitalSignageBlockMultiroomTicker(context) {
    this.context = context;
    var self = this;
    self.currentClasses = [];
    self.template = $(self.context).find('.class-next').html();
    self.contentSelector = '.block-class-ticker__content';

    // General loop – basically swaps classes.
    this.loop = function () {
      var classes = self.getClassesToShow();
      if (self.needsActiveClassActualization(classes)) {
        self.actualizeActiveClasses(classes);
      }
      else {
        self.shiftActiveClass();
      }
      self.updateProgressBars();
    };

    // Fast loop - basically updates the awaiting class.
    this.fastloop = function () {
      // Nothing yet.
    };

    // Updates progress bar of the current class.
    this.updateProgressBars = function () {
      var offset = self.getTimeOffset();
      $('.active-classes .class-active .class', self.context).each(function () {
        var $class = $(this);
        var from = $class.data('from');
        var to = $class.data('to');
        var progress = 100 * (offset - from) / (to - from);
        progress = Math.max(0, progress);
        var $pb = $class
          .find('.class-time-frame-progress-bar')
          .stop()
          .css({ width: progress + '%' });
        if (from <= offset) {
          $pb.animate({ width:'100%' }, (to - offset) * 1000 / window.tm.speed, 'linear');
        }
      });
    };

    // Checks if the current class needs replacing.
    this.needsActiveClassActualization = function (classesToShow) {
      var $activeClassContainer = $('.active-classes .class-active', self.context);
      var $activeClasses = $('.class', $activeClassContainer);

      if (classesToShow.length != self.currentClasses.length) {
        return true;
      }

      for (var i in classesToShow) {
        if (classesToShow[i].data('from') != self.currentClasses[i].from) {
          return true;
        }
      }

      return false;
    };

    // Checks if the current class needs replacing.
    this.shiftActiveClass = function () {
      var $activeClassContainer = $('.active-classes .class-active', self.context);
      var $activeClasses = $('.class', $activeClassContainer);

      var notShifter = $activeClasses.not('.class--hiding');
      if (notShifter.length > 1) {
        var $first = notShifter.eq(0).addClass('class--hiding');
        setTimeout(function() {
          var t = $activeClassContainer.find(self.contentSelector);
          $first.appendTo(t);
          $first.removeClass('class--hiding');
        }, 600);
      }
      else {
        $activeClasses.removeClass('class--hiding');
      }
    };

    // Changes the current class with the upcoming.
    this.actualizeActiveClasses = function (classes) {
      var $activeClasses = $('.active-classes', self.context);
      var $prevClassContainer = $('.active-classes .class-prev', self.context);
      var $activeClassContainer = $('.active-classes .class-active', self.context);
      var $activeClass = $('.class', $activeClassContainer);
      var $upcomingClassContainer = $('.active-classes .class-next', self.context);
      var $upcomingClass = $('.class', $upcomingClassContainer);

      var t = $activeClassContainer.find(self.contentSelector);

      if ($activeClass.length) {
        $activeClass.remove();
        self.currentClasses = [];

        $(classes).each(function() {
          t.append(this.clone());
          self.currentClasses.push({
            from: this.data('from'),
            to: this.data('from')
          });
        });
      }
      else {
        $(classes).each(function() {
          t.append(this.clone());
          self.currentClasses.push({
            from: this.data('from'),
            to: this.data('from')
          });
        });
      }
      self.updateProgressBars();
    };

    // Returns current time.
    this.getTimeOffset = function () {
      return window.tm.getTime();
    };

    // Returns current and the upcoming classes.
    this.getCurrentAndNext = function () {
      var $current = null, $next = null;
      var offset = self.getTimeOffset();
      $('.all-classes .class', self.context).each(function(){
        var $this = $(this);
        if ($this.data('to') >= offset) {
          if (!$current) {
            $current = $this;
          }
          else if ($this.data('from') < $current.data('from')) {
            $current = $this;
          }
          if ($current && $this.data('from') >= $current.data('to')) {
            if (!$next || $this.data('from') < $next.data('from')) {
              $next = $this;
            }
          }
        }
      });

      return {
        last: $current,
        next: $next
      };
    };

    // Returns current and upcoming classes.
    this.getClassesToShow = function () {
      var classes = [];
      var activeRooms = [];
      var offset = self.getTimeOffset();
      var closest = null;
      $('.all-classes .class', self.context).each(function(){
        var $this = $(this);
        if ($this.data('to') > offset) {
          var room = $this.find('.class-room').text();
          var from = $this.data('from');
          // Ongoing class.
          if (from <= offset) {
            classes.push($this);
            activeRooms.push(room);
          }
          // Upcoming class
          else if (from <= offset + 3600*12) {
            if (activeRooms.indexOf(room) < 0) {
              classes.push($this);
              activeRooms.push(room);
            }
          }
          else {
            if (!closest || from < closest.data('from')) {
              closest = $this;
            }
          }
        }
      });

      if (!classes.length) {
        classes.push(closest);
      }

      return classes;
    };

    // Returns the awaiting class.
    this.getAwaitingClass = function () {
      return $('.active-classes .class-awaiting', self.context);
    };

    // Activates the block.
    this.activate = function () {
      self.actualizeActiveClasses(self.getClassesToShow());
      self.fastloop();
      self.timer = setInterval(self.loop, 5000);
      self.fasttimer = setInterval(self.fastloop, 1000 / window.tm.speed);
      self.activated = self.getTimeOffset();
    };

    // Deactivates the block.
    this.deactivate = function () {
      clearInterval(self.timer);
      clearInterval(self.fasttimer);
    };

    self.blockObject = ObjectsManager.getObject(self.context);
    self.blockObject.activate = self.activate;
    self.blockObject.deactivate = self.deactivate;
    if (self.blockObject.isActive() || $(self.context).parents('.screen').length === 0) {
      self.activate();
    }

    return this;
  }

})(jQuery, window, Drupal, once);
