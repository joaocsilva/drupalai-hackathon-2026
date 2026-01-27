/**
 * @file
 * JavaScript for similar content linking functionality.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Adds copy link functionality to similar content items.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.similarContentLinking = {
    attach: function (context, settings) {
      // Find all copy link buttons
      const copyButtons = once('similar-content-copy', '.similar-content-linking__copy-link', context);

      copyButtons.forEach(function (button) {
        button.addEventListener('click', function (e) {
          e.preventDefault();

          const nodeUrl = this.getAttribute('data-node-url');
          const nodeTitle = this.getAttribute('data-node-title');

          // Create the link markup
          const linkMarkup = '<a href="' + nodeUrl + '">' + nodeTitle + '</a>';

          // Copy to clipboard
          navigator.clipboard.writeText(linkMarkup).then(function () {
            // Show success feedback
            const originalText = button.textContent;
            button.textContent = Drupal.t('Copied!');
            button.style.background = '#28a745';
            button.style.color = 'white';
            button.style.borderColor = '#28a745';

            // Reset button after 2 seconds
            setTimeout(function () {
              button.textContent = originalText;
              button.style.background = '';
              button.style.color = '';
              button.style.borderColor = '';
            }, 2000);
          }).catch(function (err) {
            console.error('Failed to copy link: ', err);

            // Fallback: show the markup in an alert or prompt
            prompt(Drupal.t('Copy this link markup:'), linkMarkup);
          });
        });
      });
    }
  };

})(Drupal, once);
