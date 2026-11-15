(() => {
  const button = document.querySelector('[data-whatsapp-demo]');
  const notice = document.querySelector('[data-whatsapp-demo-notice]');

  if (!(button instanceof HTMLButtonElement) || !(notice instanceof HTMLElement)) {
    return;
  }

  let hideNoticeTimeout;

  button.addEventListener('click', () => {
    notice.hidden = false;
    window.clearTimeout(hideNoticeTimeout);
    hideNoticeTimeout = window.setTimeout(() => {
      notice.hidden = true;
    }, 5000);
  });
})();
