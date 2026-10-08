/**
 * @file
 * Replaces the site logo image with the one uploaded in Content Scrambler.
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.contentScramblerLogo = {
    attach(context) {
      const settings = drupalSettings.contentScrambler;
      if (!settings || !settings.logoUrl || !settings.selector) {
        return;
      }
      once('content-scrambler-logo', settings.selector, context).forEach((img) => {
        img.removeAttribute('srcset');
        img.removeAttribute('sizes');
        img.setAttribute('src', settings.logoUrl);
      });
    },
  };
})(Drupal, drupalSettings, once);
