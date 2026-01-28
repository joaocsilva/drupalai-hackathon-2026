if (window.self !== window.top || navigator.userAgent.includes('puppeteer')) {
  // the page is in an iframe
  var badge = document.getElementById('lovable-badge');
  if (badge) {
    badge.style.display = 'none';
  }
}

// Add click event listener to close button with animation
var closeButton = document.getElementById('lovable-badge-close');
if (closeButton) {
  closeButton.addEventListener('click', function(event) {
    event.preventDefault();
    event.stopPropagation();
    var badge = document.getElementById('lovable-badge');
    if (badge) {
      badge.classList.add('closing');
      setTimeout(function() {
        if (badge) {
          badge.style.display = 'none';
        }
      }, 240);
    }
  });
}
