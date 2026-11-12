(function () {
  var form = document.querySelector('form.ivs-book');
  if (!form) return;
  var cfg;
  try { cfg = JSON.parse(form.getAttribute('data-ivs')); } catch (e) { return; }
  var T = cfg.t;
  var calEl = form.querySelector('.ivs-cal');
  var hint = form.querySelector('.ivs-hint');
  var times = form.querySelector('.ivs-times');
  var sum = form.querySelector('.ivs-sum');
  var btn = form.querySelector('button[type=submit]');
  var inFrom = form.querySelector('[name=ivs_from]');
  var inTo = form.querySelector('[name=ivs_to]');
  var qtySel = form.querySelector('[name=quantity]');
  var free = {};
  var max = 1;
  var from = '', to = '';
  var today = cfg.today;
  var view = today.slice(0, 7); // YYYY-MM

  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function ymd(y, m, d) { return y + '-' + pad(m) + '-' + pad(d); }
  function utc(s) { var p = s.split('-'); return Date.UTC(+p[0], +p[1] - 1, +p[2]); }
  function addDays(s, n) { var d = new Date(utc(s) + n * 86400000); return ymd(d.getUTCFullYear(), d.getUTCMonth() + 1, d.getUTCDate()); }
  function dow(s) { return (new Date(utc(s)).getUTCDay() + 6) % 7; } // 0 = Monday
  function weekend(s) { return dow(s) >= 5; }
  function days(a, b) { return Math.round((utc(b) - utc(a)) / 86400000) + 1; }
  function freeOn(s) { return Object.prototype.hasOwnProperty.call(free, s) ? free[s] : max; }
  var last = addDays(today, cfg.horizon);

  // Цена: самое дешёвое сочетание суток, недель и месяцев (как на сервере).
  function price(n) {
    var units = [[1, cfg.day]];
    if (cfg.week > 0) units.push([7, cfg.week]);
    if (cfg.month > 0) units.push([30, cfg.month]);
    var dp = [0];
    for (var i = 1; i <= n; i++) {
      var best = Infinity;
      for (var u = 0; u < units.length; u++) best = Math.min(best, dp[Math.max(0, i - units[u][0])] + units[u][1]);
      dp[i] = best;
    }
    return Math.round(dp[n] * 100) / 100;
  }
  function money(v) { return (Math.round(v * 100) / 100).toFixed(2).replace('.', ',') + ' ' + cfg.cur; }

  function pickable(s) { return s > today && s <= last && !weekend(s) && freeOn(s) > 0; }

  function render() {
    var y = +view.slice(0, 4), m = +view.slice(5, 7);
    var first = ymd(y, m, 1);
    var count = new Date(Date.UTC(y, m, 0)).getUTCDate();
    var h = '<div class="ivs-cal-head"><button type="button" data-nav="-1" aria-label="‹">‹</button><b>' +
      cfg.months[m - 1] + ' ' + y + '</b><button type="button" data-nav="1" aria-label="›">›</button></div><div class="ivs-grid">';
    for (var i = 0; i < 7; i++) h += '<span class="wd">' + T.wd[i] + '</span>';
    for (i = 0; i < dow(first); i++) h += '<span></span>';
    for (var d = 1; d <= count; d++) {
      var s = ymd(y, m, d), cls = 'd';
      var ok = pickable(s);
      var past = s <= today || s > last;
      if (past) cls += ' off';
      else if (freeOn(s) <= 0) cls += ' busy';
      else if (weekend(s)) cls += ' we';
      if (from && s === from) cls += ' sel start';
      else if (to && s === to) cls += ' sel end';
      else if (from && to && s > from && s < to) cls += ' mid';
      h += '<button type="button" class="' + cls + '" data-d="' + s + '"' + (ok ? '' : ' disabled') + '>' + d + '</button>';
    }
    h += '</div><div class="ivs-legend"><span class="busy">■</span> ' + (cfg.lang === 'ru' ? 'занято' : cfg.lang === 'en' ? 'taken' : 'aizņemts') +
      ' <span class="we">■</span> ' + (cfg.lang === 'ru' ? 'выходные: по договорённости' : cfg.lang === 'en' ? 'weekend: by arrangement' : 'brīvdienas: pēc vienošanās') + '</div>';
    calEl.innerHTML = h;
  }

  function rangeFree(a, b) {
    var n = max;
    for (var s = a; s <= b; s = addDays(s, 1)) n = Math.min(n, freeOn(s));
    return n;
  }

  function update() {
    var ready = from && to;
    times.hidden = !ready;
    sum.hidden = !ready;
    btn.disabled = !ready;
    inFrom.value = from;
    inTo.value = to;
    if (!from) { hint.textContent = T.pick; return; }
    if (!to) { hint.textContent = T.ret; return; }
    hint.textContent = '';
    var n = days(from, to);
    var avail = rangeFree(from, to);
    var cur = +qtySel.value || 1;
    var o = '';
    for (var q = 1; q <= Math.max(1, avail); q++) o += '<option value="' + q + '">' + q + '</option>';
    qtySel.innerHTML = o;
    qtySel.value = Math.min(cur, Math.max(1, avail));
    qtySel.closest('label').style.display = avail > 1 ? '' : 'none';
    renderSum(n);
  }

  function renderSum(n) {
    var q = +qtySel.value || 1;
    var rent = price(n) * q;
    var dep = cfg.deposit * q;
    var h = '<div class="row"><span>' + T.rent + ': ' + n + ' ' + T.days + '</span><span>' + money(rent) + '</span></div>';
    if (dep > 0) h += '<div class="row"><span>' + T.dep + '</span><span>' + money(dep) + '</span></div>';
    sum.innerHTML = h;
  }

  calEl.addEventListener('click', function (e) {
    var nav = e.target.closest('[data-nav]');
    if (nav) {
      var y = +view.slice(0, 4), m = +view.slice(5, 7) + (+nav.getAttribute('data-nav'));
      if (m < 1) { m = 12; y--; } else if (m > 12) { m = 1; y++; }
      var v = y + '-' + pad(m);
      if (v < today.slice(0, 7) || v > last.slice(0, 7)) return;
      view = v; render(); return;
    }
    var b = e.target.closest('[data-d]');
    if (!b || b.disabled) return;
    var s = b.getAttribute('data-d');
    if (!from || to || s < from) { from = s; to = ''; }
    else {
      // Внутри периода не должно быть полностью занятых дней.
      var okRange = true;
      for (var c = from; c <= s; c = addDays(c, 1)) if (freeOn(c) <= 0) okRange = false;
      if (!okRange || weekend(s)) { hint.textContent = T.busy; from = s; to = ''; if (!pickable(s)) from = ''; }
      else to = s;
    }
    render(); update();
  });

  qtySel.addEventListener('change', function () { if (from && to) renderSum(days(from, to)); });

  render(); update();
  fetch(cfg.rest, { cache: 'no-store' }).then(function (r) { return r.json(); }).then(function (j) {
    free = j.free || {}; max = j.max || 1; render(); update();
  }).catch(function () {});
})();
