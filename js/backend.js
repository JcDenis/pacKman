/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  $('.checkboxes-helpers').each(function () {
    dotclear.checkboxesHelpers(this);
  });
});