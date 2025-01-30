/**
 * @file
 * Provides OpenY Digital Signage playlist related behavior.
 */

(function ($, window, Drupal, drupalSettings, once) {

    'use strict';

    /**
     * Playlist-specific behaviour.
     *
     * @type {Drupal~behavior}
     *
     * @prop {Drupal~behaviorAttach} attach
     *   Attaches the behavior for the playlist markup.
     */
    Drupal.behaviors.openyDigitalSignageBlockPlaylist = {
        attach: function (context, settings) {
             $(once('block-playlist', '.block-playlist', context)).each(function () {
                var handler = new OpenYDigitalSignagePlaylist(this);
                handler.deactivate();
                handler.updateContext(this);
                handler.init();
            });
        }
    };

    /**
     * Playlist handler.
     *
     * The block expects the markup where each single playlist item has
     * data-duration attribute and the parent .screen-content element has
     * data-from and data-to attributes.
     *
     * Playlist items can have optional data-rotating-from and data-rotating-to
     * attributes.
     *
     * The math behind this block is the following:
     * - the first playlist item must be shown at the moment the playlist's
     * schedule item starts;
     * - the next playlist item to show is the first following playlist item
     * that have data-rotating-from and data-rotating-to empty or if they
     * match the current time;
     * - the algorithm:
     *   - set current playlist item index to 0
     *   - set the current offset to 0
     *   - loop thru the playlist items from the current playlist item index
     *   adding the duration to the offset
     *   - once the end offset of the playlist items is greater then the
     *   current offset the target playlist item is reached
     *   TODO: rewrite;
     *   - save its end offset for the playlist item, so that next time you
     *   can start the loop from that point
     *
     *
     * @param context
     *   Block.
     *
     * @returns {OpenYDigitalSignagePlaylist}
     */
    function OpenYDigitalSignagePlaylist(context) {
        var self = this;
        this.activated = 0;
        this.animationDuration = 200;
        this.context = context;
        this.element = $(context);
        this.current = null;
        this.imagePreloadDelay = 3000;
        this.index = 0;
        this.loopPeriod = 1000;
        this.offset = 0;
        this.speed = 0;
        this.time = null;

        // General loop –  swaps playlist items.
        this.loop = function () {
            if (self.speed != window.tm.speed) {
                self.reactivateLoop();
            }
            if (self.isEmpty()) {
                return;
            }

            if (self.needsActualization()) {
                self.actualize();
            }
        };

        // Checks if the current class needs replacing.
        this.needsActualization = function () {
            let item = this.getDesiredItem();
            if (self.current == null) {
                return true;
            }
            return item.data('playlist-item-index') !== self.current.data('playlist-item-index');
        };

        // Changes the current class with the upcoming.
        this.actualize = function () {
            var $item = this.getDesiredItem();

            if (!$item.hasClass('playlist-item--background-set')) {
                // Force item to set its background image and start downloading it, if it wasn't set yet.
                $item
                    .css({ backgroundImage: 'url(' + this.getPlaylistItemImage($item) + ')' })
                    .addClass('playlist-item--background-set');
            }

            // There was no slide active, immediately make the desirable one visible and active.
            if (self.current === null) {
                self.current = $item;
                $item.addClass('playlist-item--active');
                return;
            }

            var previous = self.current;
            $item
                .addClass('playlist-item--active')
                .addClass('playlist-item--activating')
                .css({opacity: 0})
                .animate({opacity: 1}, self.animationDuration / Math.abs(window.tm.speed), function() {
                    if (previous !== null) {
                        previous.removeClass('playlist-item--active');
                        $item.removeClass('playlist-item--activating');
                    }
                    self.current = $item;

                    var $items = self.getPlaylistItems();
                    $items.splice(self.index, 1);
                    $($items).each(function() {
                      this
                        .removeClass('playlist-item--active')
                        .removeClass('playlist-item--activating');
                    });
                });

            return false;
        };

        /**
         * Returns current time.
         */
        this.getTimeOffset = function () {
            return window.tm.getTime();
        };

        /**
         * Returns direction of playlist items.
         *
         * @returns {number}
         *   1 for forward direction
         *   0 for pause
         *   -1 for backward direction
         */
        this.getDirection = function () {
            return Math.sign(window.tm.speed);
        };

        /**
         * Returns current and the upcoming classes.
         *
         * @returns {{last: *, next: *}}
         *   Object with next and last classes.
         */
        this.getDesiredItem = function () {
            var $items = self.getPlaylistItems();

            // Pause.
            if (!this.getDirection()) {
                return $items[self.index];
            }

            var i = 0;
            // In the worst case each element is 1s long, the schedule item starts at the midnight
            // and it's couple of seconds before the next midnight.
            while (i < 86400) {
                // If there is nothing valid at the calculated intermediate offset time
                // look for the first valid item and jump to it.
                if (!self.isPlaylistValidAt(self.offset + self.from)) {
                    let nextValidPlaylistItem = self.getNextValidPlaylistItem();
                    self.index = nextValidPlaylistItem.item.data('playlist-item-index');
                    self.offset = nextValidPlaylistItem.offset - self.from;
                }
                // Non-valid playlist item met.
                if (!this.isPlaylistItemValid($items[self.index], self.offset + self.from)) {
                    i++;
                    self.index = ($items.length + self.index + self.getDirection()) % $items.length;
                    continue;
                }
                let newOffset = self.offset;
                if (self.getDirection() > 0) {
                    newOffset += self.getPlaylistItemDuration($items[self.index]);
                    if (newOffset >= self.getTimeOffset() - self.from) {
                        break;
                    }
                }
                else {
                    if (newOffset < self.getTimeOffset() - self.from) {
                        break;
                    }
                    let prevIndex = ($items.length + self.index - 1) % $items.length;
                    newOffset -= self.getPlaylistItemDuration($items[prevIndex]);
                }
                self.offset = newOffset;
                self.index = ($items.length + self.index + self.getDirection()) % $items.length;
                i++;
            }

            return $items[self.index];
        };

        this.getNextValidPlaylistItem = function () {
            // So we are at self.offset + self.from moment of time.
            let timeMoment = self.offset + self.from;
            // The time direction defines where we are looking.
            let timeDirection = self.getDirection();

            let time = moment
                .unix(timeMoment)
                .tz(drupalSettings.digital_signage_playlist.timezone)
                .format('HH:mm:ss');

            let items = self.getPlaylistItems();
            let itemsValidForDay = [];
            $(items).each(function () {
                let $item = $(this);
                // Ignore any playlist items that don't start today.
                if (!self.isPlaylistItemValidForDay($item, timeMoment)) {
                    return;
                }

                if (timeDirection > 0) {
                    // This playlist item finishes earlier, ignore it.
                    if (self.getPlaylistItemTimeTo($item) && self.getPlaylistItemTimeFrom($item) < time) {
                        return;
                    }
                }
                else {
                    // This playlist item finishes earlier, ignore it.
                    if (self.getPlaylistItemTimeTo($item) && self.getPlaylistItemTimeFrom($item) > time) {
                        return;
                    }
                }

                itemsValidForDay.push($item);
            });

            // Only those that start time doesn't overlapped with other items.
            let itemsThatStartSequence = [];
            $(itemsValidForDay).each(function () {
                let checked = this;
                let startsSequence = true;
                $(itemsValidForDay).each(function () {
                    if (
                        (self.getPlaylistItemTimeFrom(this) < self.getPlaylistItemTimeFrom(checked)) &&
                        (self.getPlaylistItemTimeTo(this) > self.getPlaylistItemTimeFrom(checked))
                    ) {
                        startsSequence = false;
                    }
                });
                if (startsSequence) {
                    itemsThatStartSequence.push(checked);
                }
            });

            let closest = null;
            // Now pick the item with the smallest possible start time.
            $(itemsThatStartSequence).each(function () {
                // This playlist item is the first found, save it as the closest.
                if (!closest) {
                    closest = this;
                    return;
                }

                if (timeDirection > 0) {
                    // There has been one playlist item that fits, check if it's closer to the point.
                    if (self.getPlaylistItemTimeFrom(this) >= self.getPlaylistItemTimeFrom(closest)) {
                        return;
                    }
                }
                else {
                    // There has been one playlist item that fits, check if it's closer to the point.
                    if (self.getPlaylistItemTimeFrom(this) <= self.getPlaylistItemTimeFrom(closest)) {
                        return;
                    }
                }

                closest = this;
            });

            let closestTime = self.getPlaylistItemTimeFrom(closest).split(':');
            let offset = moment.unix(timeMoment)
                .tz(drupalSettings.digital_signage_playlist.timezone)
                .set('hour', parseInt(closestTime[0]))
                .set('minute', parseInt(closestTime[1]))
                .set('second', parseInt(closestTime[2]));
            return {
                item: closest,
                offset: offset.unix()
            };
        };

        /**
         * Checks if the playlist item is valid to be shown at the moment.
         *
         * @param $item
         *
         * @returns {boolean}
         */
        this.isPlaylistItemValid = function ($item, timeMoment = null) {
            if (!timeMoment) {
                timeMoment = this.getTimeOffset();
            }
            let datetime = moment.unix(timeMoment).tz(drupalSettings.digital_signage_playlist.timezone);
            let date = datetime.format('YYYY-MM-DD');
            let time = datetime.format('HH:mm:ss');

            if (!this.isPlaylistItemValidForDay($item, timeMoment)) {
                return false;
            }
            if (this.getPlaylistItemTimeFrom($item) && this.getPlaylistItemTimeFrom($item) > time) {
                return false;
            }
            if (this.getPlaylistItemTimeTo($item) && this.getPlaylistItemTimeTo($item) <= time) {
                return false;
            }

            return true;
        };

        /**
         * Checks if the playlist item is valid on the day of the given time moment.
         *
         * @param $item
         *
         * @returns {boolean}
         */
        this.isPlaylistItemValidForDay = function ($item, timeMoment = null) {
            if (!timeMoment) {
                timeMoment = this.getTimeOffset();
            }
            let datetime = moment.unix(timeMoment).tz(drupalSettings.digital_signage_playlist.timezone);
            let date = datetime.format('YYYY-MM-DD');

            if (this.getPlaylistItemDateFrom($item) && this.getPlaylistItemDateFrom($item) > date) {
                return false;
            }
            if (this.getPlaylistItemDateTo($item) && this.getPlaylistItemDateTo($item) < date) {
                return false;
            }

            return true;
        };

        this.getPlaylistItems = function () {
            let $items = [];
            $('.playlist-item', self.context).each(function () {
                $items.push($(this));
            });
            return $items;
        };

        this.getPlaylistItemDuration = function ($el) {
            return $el.data('duration');
        };

        this.getPlaylistItemDateFrom = function ($el) {
            return $el.data('date-from');
        };

        this.getPlaylistItemDateTo = function ($el) {
            return $el.data('date-to');
        };

        this.getPlaylistItemTimeFrom = function ($el) {
            return $el.data('time-from');
        };

        this.getPlaylistItemTimeTo = function ($el) {
            return $el.data('time-to');
        };

        this.getPlaylistItemImage = function ($el) {
            if ($el.data('background') != '') {
                return $el.data('background');
            }
            // TODO: What should be done here?
            let rnd = Math.floor(Math.random() * 206);
            return "https://source.unsplash.com/collection/466697/" + rnd + "";
        };

        /**
         * Checks if the playlist is empty at the moment.
         *
         * @returns {boolean}
         */
        this.isEmpty = function () {
            let $items = self.getPlaylistItems();

            // The playlist is literally empty - there are no playlist items.
            if ($items.length === 0) {
                return true;
            }

            return !self.isPlaylistValidAt();
        };

        /**
         * Checks if the playlist has at least one valid element at the specific moment of time.
         *
         * @param timeMoment
         *   Moment of time to be checked.
         *
         * @returns {boolean}
         */
        this.isPlaylistValidAt = function (timeMoment = null) {
            if (!timeMoment) {
                timeMoment = self.getTimeOffset();
            }

            let validItemsCount = 0;
            let $items = self.getPlaylistItems();
            $($items).each(function () {
                if (self.isPlaylistItemValid(this, timeMoment)) {
                    validItemsCount++;
                }
            });

            return validItemsCount > 0;
        };

        /**
         * Activates the block.
         */
        this.activate = function () {
            var parent = $(self.context).parents('.screen-content').get(0);

            if (typeof parent == 'undefined') {
                // Preview.
                var query_params = window.tm.get_query_param();
                if (query_params.hasOwnProperty('from')) {
                    this.from = parseInt(query_params.from);
                }
                else {
                    this.from = window.tm.getTime();
                }
            }
            else {
                this.from = $(parent).data('from-ts');
            }

            self.reactivateLoop();
            self.loop();
            self.activated = self.getTimeOffset();
        };

        /**
         * Reactivates loop.
         */
        this.reactivateLoop = function () {
            if (self.timer) {
                clearInterval(self.timer);
            }
            self.timer = setInterval(self.loop, self.loopPeriod / Math.abs(window.tm.speed));
            self.speed = window.tm.speed;
        };

        /**
         * Deactivates the block.
         */
        this.deactivate = function () {
            clearInterval(self.timer);
            self.activated = 0;
        };

        /**
         * Initialize block.
         */
        this.init = function () {
            var i = 0;
            $('.playlist-item', self.context).each(function (i) {
                $(this).data('playlist-item-index', i);
                let img = self.getPlaylistItemImage($(this));
                var $playlistItem = $(this);
                self.preloadImage(img, function() {
                    $playlistItem.css({
                        backgroundImage: 'url(' + this + ')'
                    });
                }, self.imagePreloadDelay);
            });

            self.blockObject = ObjectsManager.getObject(self.context);
            if (!(self.blockObject instanceof OpenYDigitalSignagePlaylist)) {
                this.element.data('screenContentBlock', self);
                self.activate();
            }

            // Preview.
            if (self.blockObject.isActive() || $(self.context).parents('.screen').length === 0) {
                self.activate();
                if ($(self.context).parents('.screen').length === 0) {
                    window.OpenYDSPlaylistHandler = self;
                }
            }
        };

        this.preloadImage = function (url, callback, delay) {
            let image = new Image();
            image.onload = function () {
                callback.call(url);
            };
            setTimeout(function () {
                image.src = url;
            }, delay);
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
            self.context = context;
            self.element = $(context);
            // self.element.data('screenContentBlock', self);
            self = this;
        };

        return this;
    }

})(jQuery, window, Drupal, drupalSettings, once);
