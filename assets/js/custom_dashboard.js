/**
 * Kanboard Custom Analytics Dashboard
 *
 * Depends on: jQuery (already bundled), C3.js / D3.js (already in vendor.min.js)
 * No external libraries are added.
 */
(function ($) {
  'use strict';

  /* -----------------------------------------------------------------------
   * Colour palette — consistent with the CSS tokens
   * -------------------------------------------------------------------- */
  var COLORS = {
    green:  '#22c55e',
    blue:   '#3b82f6',
    grey:   '#94a3b8',
    red:    '#ef4444',
    orange: '#f97316',
    teal:   '#14b8a6',
    purple: '#8b5cf6'
  };

  /* -----------------------------------------------------------------------
   * Safe JSON parse from a data attribute
   * -------------------------------------------------------------------- */
  function safeJSON(str) {
    try { return JSON.parse(str || '[]'); }
    catch (e) { return []; }
  }
  function safeJSONObj(str) {
    try { return JSON.parse(str || '{}'); }
    catch (e) { return {}; }
  }

  /* -----------------------------------------------------------------------
   * Donut chart (C3)
   * el  — DOM element with data-completed / in-progress / pending / overdue
   * -------------------------------------------------------------------- */
  function renderDonut(el) {
    var $el = $(el);
    var completed   = parseInt($el.data('completed'),   10) || 0;
    var inProgress  = parseInt($el.data('in-progress'), 10) || 0;
    var pending     = parseInt($el.data('pending'),     10) || 0;
    var overdue     = parseInt($el.data('overdue'),     10) || 0;

    if (completed + inProgress + pending + overdue === 0) {
      $el.html('<p style="text-align:center;color:#94a3b8;font-size:.85rem;padding-top:60px;">No data yet</p>');
      return;
    }

    c3.generate({
      bindto: el,
      size:   { height: 180 },
      data: {
        columns: [
          ['Completed',   completed],
          ['In Progress', inProgress],
          ['Pending',     pending],
          ['Overdue',     overdue]
        ],
        type:   'donut',
        colors: {
          'Completed':   COLORS.green,
          'In Progress': COLORS.blue,
          'Pending':     COLORS.grey,
          'Overdue':     COLORS.red
        }
      },
      donut: {
        title:    '',
        label:    { show: false },
        width:    30
      },
      legend:  { show: false },
      tooltip: {
        format: {
          value: function (v) { return v + ' tasks'; }
        }
      }
    });
  }

  /* -----------------------------------------------------------------------
   * Bar chart for daily trend (C3)
   * el — DOM element with data-trend JSON: [{day, count}, ...]
   * -------------------------------------------------------------------- */
  function renderTrendBar(el) {
    var $el   = $(el);
    var trend = safeJSON($el.attr('data-trend'));
    if (!trend.length) {
      $el.html('<p style="text-align:center;color:#94a3b8;font-size:.85rem;padding-top:80px;">No data yet</p>');
      return;
    }

    var days   = trend.map(function (d) { return d.day.slice(5); }); // MM-DD
    var counts = trend.map(function (d) { return d.count; });

    c3.generate({
      bindto: el,
      size:   { height: 220 },
      data: {
        x:       'x',
        columns: [
          ['x'].concat(days),
          ['Completed'].concat(counts)
        ],
        types:  { Completed: 'bar' },
        colors: { Completed: COLORS.blue }
      },
      axis: {
        x: {
          type: 'category',
          tick: {
            rotate: -45,
            multiline: false,
            culling: { max: 10 }
          }
        },
        y: {
          min:  0,
          padding: { bottom: 0 },
          tick: { format: function (d) { return Number.isInteger(d) ? d : ''; } }
        }
      },
      bar:    { width: { ratio: 0.7 } },
      grid:   { y: { show: true } },
      legend: { show: false },
      tooltip: {
        format: {
          title:  function (x) { return trend[x] ? trend[x].day : x; },
          value:  function (v) { return v + ' tasks completed'; }
        }
      }
    });
  }

  /* -----------------------------------------------------------------------
   * Horizontal bar chart for projects (C3)
   * el — data-projects JSON: [{project_name, open_tasks, closed_tasks, total}, ...]
   * -------------------------------------------------------------------- */
  function renderProjectsBar(el) {
    var $el      = $(el);
    var projects = safeJSON($el.attr('data-projects'));
    if (!projects.length) { return; }

    // Limit to top 15 for readability
    projects = projects.slice(0, 15);
    var names    = projects.map(function (p) { return p.project_name.length > 22 ? p.project_name.slice(0, 22) + '…' : p.project_name; });
    var openArr  = projects.map(function (p) { return parseInt(p.open_tasks   || 0, 10); });
    var doneArr  = projects.map(function (p) { return parseInt(p.closed_tasks || p.completed || 0, 10); });

    c3.generate({
      bindto: el,
      size:   { height: Math.max(200, projects.length * 28) },
      data: {
        columns: [
          ['Open'].concat(openArr),
          ['Done'].concat(doneArr)
        ],
        type:   'bar',
        colors: { Open: COLORS.blue, Done: COLORS.green },
        groups: [['Open', 'Done']]
      },
      axis: {
        rotated: true,
        x: {
          type: 'category',
          categories: names,
          tick: { multiline: false }
        },
        y: {
          min:     0,
          padding: { bottom: 0 },
          tick:    { format: function (d) { return Number.isInteger(d) ? d : ''; } }
        }
      },
      bar:    { width: { ratio: 0.6 } },
      grid:   { y: { show: true } },
      legend: { show: true },
      tooltip: {
        format: { value: function (v) { return v + ' tasks'; } }
      }
    });
  }

  /* -----------------------------------------------------------------------
   * Horizontal bar chart for workload
   * el — data-workload JSON: [{username, name, open_tasks, overdue_tasks}, ...]
   * -------------------------------------------------------------------- */
  function renderWorkloadBar(el) {
    var $el      = $(el);
    var workload = safeJSON($el.attr('data-workload'));
    if (!workload.length) { return; }

    workload = workload.slice(0, 20);
    var names   = workload.map(function (w) { return (w.name || w.username).slice(0, 20); });
    var openArr = workload.map(function (w) { return parseInt(w.open_tasks    || 0, 10); });
    var ovArr   = workload.map(function (w) { return parseInt(w.overdue_tasks || 0, 10); });

    c3.generate({
      bindto: el,
      size:   { height: Math.max(220, workload.length * 30) },
      data: {
        columns: [
          ['Open'].concat(openArr),
          ['Overdue'].concat(ovArr)
        ],
        type:   'bar',
        colors: { Open: COLORS.teal, Overdue: COLORS.red },
        groups: [['Open', 'Overdue']]
      },
      axis: {
        rotated: true,
        x: { type: 'category', categories: names, tick: { multiline: false } },
        y: { min: 0, padding: { bottom: 0 }, tick: { format: function (d) { return Number.isInteger(d) ? d : ''; } } }
      },
      bar:    { width: { ratio: 0.6 } },
      grid:   { y: { show: true } },
      legend: { show: true },
      tooltip: {
        format: { value: function (v) { return v + ' tasks'; } }
      }
    });
  }

  /* -----------------------------------------------------------------------
   * Priority chart (horizontal bar)
   * el — data-priorities JSON: [{priority, cnt}, ...], data-labels JSON
   * -------------------------------------------------------------------- */
  function renderPriorityChart(el) {
    var $el    = $(el);
    var pdata  = safeJSON($el.attr('data-priorities'));
    var labels = safeJSONObj($el.attr('data-labels'));
    if (!pdata.length) { return; }

    var pLabels = pdata.map(function (p) { return labels[p.priority] || ('P' + p.priority); });
    var counts  = pdata.map(function (p) { return parseInt(p.cnt || 0, 10); });

    c3.generate({
      bindto: el,
      size:   { height: 220 },
      data: {
        columns: [['Tasks'].concat(counts)],
        type:   'bar',
        colors: { Tasks: COLORS.purple }
      },
      axis: {
        rotated: true,
        x: { type: 'category', categories: pLabels, tick: { multiline: false } },
        y: { min: 0, padding: { bottom: 0 }, tick: { format: function (d) { return Number.isInteger(d) ? d : ''; } } }
      },
      bar:    { width: { ratio: 0.6 } },
      legend: { show: false },
      tooltip: { format: { value: function (v) { return v + ' tasks'; } } }
    });
  }

  /* -----------------------------------------------------------------------
   * Initialise all charts on the page
   * -------------------------------------------------------------------- */
  function initCharts() {
    // Donut charts
    document.querySelectorAll('.kdb-chart-donut').forEach(function (el) {
      renderDonut(el);
    });

    // Trend bars
    document.querySelectorAll('#kdb-user-trend30, #kdb-admin-trend30, #kdb-emp-trend30').forEach(function (el) {
      renderTrendBar(el);
    });
    document.querySelectorAll('#kdb-user-trend7, #kdb-emp-trend7').forEach(function (el) {
      renderTrendBar(el);
    });

    // Project bars
    document.querySelectorAll('#kdb-user-projects-chart, #kdb-projects-bar, #kdb-emp-projects-chart').forEach(function (el) {
      renderProjectsBar(el);
    });

    // Workload bar
    var wlEl = document.getElementById('kdb-workload-chart');
    if (wlEl) { renderWorkloadBar(wlEl); }

    // Priority chart
    var prEl = document.getElementById('kdb-priority-chart');
    if (prEl) { renderPriorityChart(prEl); }
  }

  /* -----------------------------------------------------------------------
   * Date filter: pill clicks + custom range
   * -------------------------------------------------------------------- */
  function initDateFilter() {
    var $filter = $('#kdb-date-filter');
    if (!$filter.length) { return; }

    // Pill clicks
    $filter.on('click', '.kdb-pill', function () {
      var $pill  = $(this);
      var filter = $pill.data('filter');

      $filter.find('.kdb-pill').removeClass('active');
      $pill.addClass('active');

      if (filter === 'custom') {
        $('#kdb-custom-range').addClass('visible');
      } else {
        $('#kdb-custom-range').removeClass('visible');
        applyFilter(filter, '', '');
      }
    });

    // Custom apply button
    $('#kdb-custom-apply').on('click', function () {
      var from = $('#kdb-from').val();
      var to   = $('#kdb-to').val();
      if (!from || !to) { alert('Please select both a start and end date.'); return; }
      applyFilter('custom', from, to);
    });
  }

  function applyFilter(filter, fromStr, toStr) {
    var pageUrl = $('#kdb-date-filter').data('page-url');
    if (!pageUrl) { return; }

    var url = pageUrl + '?date_filter=' + encodeURIComponent(filter);
    if (filter === 'custom' && fromStr && toStr) {
      url += '&custom_from_str=' + encodeURIComponent(fromStr);
      url += '&custom_to_str='   + encodeURIComponent(toStr);
    }
    window.location.href = url;
  }

  /* -----------------------------------------------------------------------
   * Employee table: live search + sort
   * -------------------------------------------------------------------- */
  function initEmployeeTable() {
    var $tbody = $('#kdb-emp-tbody');
    if (!$tbody.length) { return; }

    // --- Search ---
    $('#kdb-emp-search').on('input', function () {
      var q = $(this).val().toLowerCase();
      $tbody.find('tr').each(function () {
        var $tr   = $(this);
        var name  = ($tr.data('name') || '').toLowerCase();
        var uname = ($tr.data('username') || '').toLowerCase();
        $tr.toggle(name.indexOf(q) !== -1 || uname.indexOf(q) !== -1);
      });
    });

    // --- Sort buttons ---
    $('.kdb-sort-btn').on('click', function () {
      var $btn = $(this);
      var col  = $btn.data('sort');
      var dir  = $btn.data('dir') === 'asc' ? 'desc' : 'asc';

      $('.kdb-sort-btn').removeClass('active').data('dir', 'desc');
      $btn.addClass('active').data('dir', dir);

      sortTable($tbody, col, dir);
    });
  }

  function sortTable($tbody, col, dir) {
    var rows = $tbody.find('tr').get();
    rows.sort(function (a, b) {
      var av = parseFloat($(a).data(col)) || 0;
      var bv = parseFloat($(b).data(col)) || 0;
      return dir === 'asc' ? av - bv : bv - av;
    });
    $.each(rows, function (i, row) { $tbody.append(row); });
  }

  /* -----------------------------------------------------------------------
   * Project table: column sort
   * -------------------------------------------------------------------- */
  function initProjectTable() {
    var $tbl = $('#kdb-projects-tbl');
    if (!$tbl.length) { return; }

    var sortState = {};

    $tbl.find('th[data-col]').on('click', function () {
      var col = $(this).data('col');
      sortState[col] = sortState[col] === 'asc' ? 'desc' : 'asc';
      var dir  = sortState[col];
      var $tbody = $tbl.find('tbody');
      var isNum = (col !== 'project_name');

      var rows = $tbody.find('tr').get();
      rows.sort(function (a, b) {
        var av = isNum ? (parseFloat($(a).find('td').eq(colIndex($tbl, col)).text()) || 0) : $(a).find('td').eq(0).text();
        var bv = isNum ? (parseFloat($(b).find('td').eq(colIndex($tbl, col)).text()) || 0) : $(b).find('td').eq(0).text();
        if (isNum) { return dir === 'asc' ? av - bv : bv - av; }
        return dir === 'asc' ? av.localeCompare(bv) : bv.localeCompare(av);
      });
      $.each(rows, function (i, row) { $tbody.append(row); });
    });
  }

  function colIndex($tbl, col) {
    var idx = 0;
    $tbl.find('th').each(function (i) {
      if ($(this).data('col') === col) { idx = i; return false; }
    });
    return idx;
  }

  /* -----------------------------------------------------------------------
   * Collapsible panels
   * -------------------------------------------------------------------- */
  function initCollapsible() {
    $(document).on('click', '.kdb-header-collapsible', function () {
      var targetId = $(this).data('target');
      var $body    = $('#' + targetId);
      $body.toggleClass('collapsed');
      $(this).find('.kdb-toggle-icon .fa')
             .toggleClass('fa-chevron-down fa-chevron-right');
    });
  }

  /* -----------------------------------------------------------------------
   * Live update via JSON endpoint when date filter changes (AJAX path
   * is used if the data-data-url is set on #kdb-date-filter).
   * Currently we do a full page redirect for simplicity; this hook is
   * kept for future progressive-enhancement AJAX refresh.
   * -------------------------------------------------------------------- */

  /* -----------------------------------------------------------------------
   * Boot
   * -------------------------------------------------------------------- */
  $(document).ready(function () {
    var $wrapper = $('.kdb-wrapper');
    if (!$wrapper.length) { return; }

    // Guard: C3 must be available
    if (typeof c3 === 'undefined') {
      console.warn('KDB Dashboard: C3.js not found in vendor bundle. Charts will not render.');
      return;
    }

    initCharts();
    initDateFilter();
    initEmployeeTable();
    initProjectTable();
    initCollapsible();
  });

}(jQuery));
