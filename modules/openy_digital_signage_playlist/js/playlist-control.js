/**
 * @file
 * Provides OpenY Digital Signage playlist control related behavior.
 */

(function ($, window, Drupal, drupalSettings) {

    'use strict';

    /**
     * Static bar specific behaviour.
     *
     * @type {Drupal~behavior}
     *
     * @prop {Drupal~behaviorAttach} attach
     *   Attaches the behavior for the block.
     */
    Drupal.behaviors.openyDigitalSignagePlaylistControl = {
        attach: function (context, settings) {
            if (context == document) {
                var handler = new OpenYDigitalSignagePlaylistControl();
                handler.drawControlTray();
                window.tm.speed = 1;
            }
        }
    };

    /**
     * Playlist controller.
     *
     * @returns {OpenYDigitalSignagePlaylistControl}
     */
    function OpenYDigitalSignagePlaylistControl() {
        var self = this;

        // General loop – swaps playlist items.
        this.getEditLink = function () {
            return $('.playlist-wrapper').data('edit-link');
        };

        this.drawControlTray = function () {
            let wrapperMarkup = '<div id="playlist-control-tray">' +
                '    <div class="playlist-control-tab-wrapper">' +
                '        <ul class="playlist-control-tabs"></ul>' +
                '        <div class="playlist-control-tabs-content" tabindex="-1"></div>' +
                '    </div>' +
                '</div>';

            let buttonTemplateMarkup = '<li class="playlist-control-tab">' +
                '    <a class="playlist-control-button" href="javascript:;">' +
                '        <span class="playlist-control-icon"></span>' +
                '        <span class="playlist-control-tab-title"></span>' +
                '    </a>' +
                '</li>';

            var buttons = [
                { icon: 'previous', title: 'Previous & play backward', link: 'javascript:;', disabled: true },
                { icon: 'backward', title: 'Backward', link: 'javascript:;', disabled: true },
                { icon: 'pause', title: 'Pause', link: 'javascript:;', disabled: true },
                { icon: 'play2', title: 'Play', link: 'javascript:;', disabled: true },
                { icon: 'forward2', title: 'Fast Forward', link: 'javascript:;', disabled: true },
                { icon: 'next', title: 'Next', link: 'javascript:;', disabled: true },
                { icon: 'edit', title: 'Edit', link: self.getEditLink(), disabled: false },
            ];

            var $wrapper = $(wrapperMarkup).appendTo('body');
            $(buttons).each(function () {
                let $button = $(buttonTemplateMarkup).appendTo($wrapper.find('.playlist-control-tabs'));
                $('.playlist-control-button', $button).attr({
                    title: Drupal.t(this.title),
                    href: this.link
                });
                $('.playlist-control-icon', $button).addClass('playlist-control-icon--' + this.icon);
                if (this.disabled) {
                    $('.playlist-control-icon', $button).addClass(' playlist-control-button--disabled');
                }
                // Set button text like this:
                // $('.playlist-control-tab-title', $button).text(Drupal.t(this.title));
                switch (this.icon) {
                    case 'previous':
                        $('.playlist-control-button', $button).on('click', function () {
                            self.setSpeed(-1);
                            window.tm.offset = window.OpenYDSPlaylistHandler.offset + window.OpenYDSPlaylistHandler.from - 0.01;
                        });
                        break;

                    case 'backward':
                        $('.playlist-control-button', $button).on('click', function () {
                            self.setSpeed(-10);
                        });
                        break;

                    case 'play2':
                        $('.playlist-control-button', $button).on('click', function () {
                            self.setSpeed(1);
                        });
                        break;

                    case 'pause':
                        $('.playlist-control-button', $button).on('click', function () {
                            self.setSpeed(0);
                        });
                        break;

                    case 'forward2':
                        $('.playlist-control-button', $button).on('click', function () {
                            self.setSpeed(10);
                        });
                        break;

                    case 'next':
                        $('.playlist-control-button', $button).on('click', function () {
                            self.setSpeed(1);
                            window.tm.offset = window.OpenYDSPlaylistHandler.offset + window.OpenYDSPlaylistHandler.from + parseInt($('.playlist-item--active').data('duration'));
                        });
                        break;
                }
            });
        };

        this.setSpeed = function (speed = 1) {
            window.tm.offset = window.tm.getTime();
            window.tm.initTime = window.tm.getRealTime();
            window.tm.speed = speed;
        };

        return this;
    }

})(jQuery, window, Drupal, drupalSettings);

