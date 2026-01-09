(function () {
  function onReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn, { once: true });
    } else {
      fn();
    }
  }

  onReady(function () {
    var el = document.getElementById('staffStatusChart');
    if (!el || !window.Chart) return;

    function cssVar(name, fallback) {
      var v = getComputedStyle(document.documentElement).getPropertyValue(name);
      v = (v || '').trim();
      return v || fallback;
    }

    var cWarning = cssVar('--bs-warning', '#ffc107');
    var cInfo = cssVar('--bs-info', '#0dcaf0');
    var cSuccess = cssVar('--bs-success', '#198754');

    var data = (window.STAFF_COUNTS || { pending: 0, inProgress: 0, completed: 0 });
    var total = (data.pending + data.inProgress + data.completed);
    if (total === 0) {
      data.pending = 1; data.inProgress = 1; data.completed = 1;
    }

    new Chart(el, {
      type: 'doughnut',
      data: {
        labels: ['Pending', 'In progress', 'Completed'],
        datasets: [{
          data: [data.pending, data.inProgress, data.completed],
          backgroundColor: [cWarning, cInfo, cSuccess],
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } },
        cutout: '70%'
      }
    });
  });
})();
