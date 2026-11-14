/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  const msg = dotclear.getData('packman');

  $('.checkboxes-helpers').each(function () {
    dotclear.checkboxesHelpers(this);
  });

  // Confirm destructive actions on packages
  for (const form of document.querySelectorAll('form[id^="packman-form-"]')) {
    form.addEventListener('submit', (event) => {
      const action = form.querySelector('select[name="action"]')?.value ?? '';
      if (action === 'delete') {
        return dotclear.confirm(msg.confirm_delete, event);
      }
      if (action.startsWith('move_to_')) {
        return dotclear.confirm(msg.confirm_move, event);
      }
      return true;
    });
  }
});
