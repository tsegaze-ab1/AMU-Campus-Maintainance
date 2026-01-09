(function () {
  function onReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn, { once: true });
    } else {
      fn();
    }
  }

  onReady(function () {
    if (!window.bootstrap) return;

    document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function (trigger) {
      bootstrap.Dropdown.getOrCreateInstance(trigger);
    });

    document.querySelectorAll('[data-bs-toggle="offcanvas"]').forEach(function (trigger) {
      var targetSelector = trigger.getAttribute('data-bs-target') || trigger.getAttribute('href');
      if (!targetSelector) return;
      var target = document.querySelector(targetSelector);
      if (!target) return;
      bootstrap.Offcanvas.getOrCreateInstance(target);
    });

    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function (trigger) {
      var targetSelector = trigger.getAttribute('data-bs-target') || trigger.getAttribute('href');
      if (!targetSelector) return;
      var target = document.querySelector(targetSelector);
      if (!target) return;
      bootstrap.Collapse.getOrCreateInstance(target, { toggle: false });
    });
  });
})();
