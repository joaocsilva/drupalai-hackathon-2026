(($, Drupal, once) => {
  Drupal.behaviors.ai_elvis = {
    attach: function (context) {
      const opened = `Collapse <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-up h-4 w-4"><path d="m18 15-6-6-6 6"></path></svg>`;
      const closed = `Details <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-down h-4 w-4"><path d="m6 9 6 6 6-6"></path></svg>`;
      let $buttons = $(once('root', '.collapse-button', context))
      if ($buttons.length) {
        $buttons.on('click', function () {
          console.log('click', $(this).data('target'));
          let $wrapper = $('#' + $(this).data('target'));
          if ($wrapper.css('display') === 'none') {
            $wrapper.css('display', 'block');
            $(this).html(opened);
          }
          else {
            $wrapper.css('display', 'none');
            $(this).html(closed);
          }
        });
      }
    }
  }
})(jQuery, Drupal, once);
