(function (Drupal) {
  Drupal.behaviors.aiElvisDashboard = {
    attach: function (context, settings) {
      const collapseButtons = context.querySelectorAll('.collapse-button');

      collapseButtons.forEach(button => {
        button.addEventListener('click', function () {
          const targetId = this.getAttribute('data-target');
          const targetElement = document.getElementById(targetId);

          if (targetElement) {
            if (targetElement.style.display === 'none') {
              targetElement.style.display = 'block';
              this.innerHTML = `Collapse <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-up h-4 w-4"><path d="m18 15-6-6-6 6"></path></svg>`;
            } else {
              targetElement.style.display = 'none';
              this.innerHTML = `Expand <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-down h-4 w-4"><path d="m6 9 6 6 6-6"></path></svg>`;
            }
          }
        });
      });
    }
  };
})(Drupal);
