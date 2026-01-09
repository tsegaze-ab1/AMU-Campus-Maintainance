(function () {
  function onReady(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn, { once: true });
    } else {
      fn();
    }
  }

  onReady(function () {
    // Status doughnut
    var statusEl = document.getElementById('statusChart');
    if (statusEl && window.Chart) {
      function cssVar(name, fallback) {
        var v = getComputedStyle(document.documentElement).getPropertyValue(name);
        v = (v || '').trim();
        return v || fallback;
      }
      var cWarning = cssVar('--bs-warning', '#ffc107');
      var cPrimary = cssVar('--bs-primary', '#0d6efd');
      var cSuccess = cssVar('--bs-success', '#198754');

      var counts = (window.ADMIN_COUNTS || { pending: 0, inProgress: 0, completed: 0 });
      var total = counts.pending + counts.inProgress + counts.completed;
      if (total === 0) { counts.pending = 1; counts.inProgress = 1; counts.completed = 1; }

      new Chart(statusEl, {
        type: 'doughnut',
        data: {
          labels: ['Pending', 'In progress', 'Completed'],
          datasets: [{
            data: [counts.pending, counts.inProgress, counts.completed],
            backgroundColor: [cWarning, cPrimary, cSuccess],
            borderWidth: 0
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom' },
            tooltip: {
              callbacks: {
                label: function (ctx) {
                  if (total === 0) return ctx.label + ': 0';
                  var value = ctx.raw || 0;
                  var pct = Math.round((value / total) * 100);
                  return ctx.label + ': ' + value + ' (' + pct + '%)';
                }
              }
            }
          },
          cutout: '68%'
        }
      });
    }

    // Roles bar chart
    var rolesEl = document.getElementById('rolesChart');
    if (rolesEl && window.Chart) {
      function cssVar(name, fallback) {
        var v = getComputedStyle(document.documentElement).getPropertyValue(name);
        v = (v || '').trim();
        return v || fallback;
      }
      var cPrimary = cssVar('--bs-primary', '#0d6efd');
      var cInfo = cssVar('--bs-info', '#0dcaf0');
      var cWarning = cssVar('--bs-warning', '#ffc107');
      var cSuccess = cssVar('--bs-success', '#198754');

      var cPrimaryRgb = cssVar('--bs-primary-rgb', '13,110,253');
      var cInfoRgb = cssVar('--bs-info-rgb', '13,202,240');
      var cWarningRgb = cssVar('--bs-warning-rgb', '255,193,7');
      var cSuccessRgb = cssVar('--bs-success-rgb', '25,135,84');

      var roleData = (window.ADMIN_ROLE_DATA || { students: 0, technicians: 0, staff: 0, admins: 0 });

      new Chart(rolesEl, {
        type: 'bar',
        data: {
          labels: ['Students', 'Technicians', 'Staff', 'Admins'],
          datasets: [{
            label: 'Users',
            data: [roleData.students, roleData.technicians, roleData.staff, roleData.admins],
            backgroundColor: [
              'rgba(' + cInfoRgb + ', .35)',
              'rgba(' + cWarningRgb + ', .35)',
              'rgba(' + cPrimaryRgb + ', .35)',
              'rgba(' + cSuccessRgb + ', .35)'
            ],
            borderColor: [cInfo, cWarning, cPrimary, cSuccess],
            borderWidth: 1,
            borderRadius: 8,
            maxBarThickness: 44
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (ctx) { return 'Users: ' + (ctx.raw || 0); } } } },
          scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
      });
    }
  });
})();
