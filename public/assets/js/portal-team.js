/* Role editor: select-all per module, "start from" templates, live counter. Markup: portal/admin/team/role-form.blade.php */
(function () {
  'use strict';

  var form = document.getElementById('roleForm');
  if (!form) { return; }

  var perms = Array.prototype.slice.call(form.querySelectorAll('[data-perm]')),
      modules = Array.prototype.slice.call(form.querySelectorAll('[data-module]')),
      counter = form.querySelector('[data-perm-count]');

  function refresh() {
    modules.forEach(function (m) {
      var boxes = m.querySelectorAll('[data-perm]'), all = m.querySelector('[data-module-all]');
      var on = Array.prototype.filter.call(boxes, function (b) { return b.checked; }).length;
      all.checked = on === boxes.length; all.indeterminate = on > 0 && on < boxes.length;
    });
    counter.textContent = perms.filter(function (p) { return p.checked; }).length;
  }

  form.addEventListener('change', function (e) {
    if (e.target.matches('[data-module-all]')) {
      e.target.closest('[data-module]').querySelectorAll('[data-perm]').forEach(function (b) { b.checked = e.target.checked; });
    }
    refresh();
  });

  form.querySelectorAll('[data-preset]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var keys = []; try { keys = JSON.parse(btn.getAttribute('data-preset')) || []; } catch (e) {}
      perms.forEach(function (p) { p.checked = keys.indexOf(p.value) > -1; });
      refresh();
    });
  });

  refresh();
}());
