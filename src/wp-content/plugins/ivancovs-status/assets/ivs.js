(function () {
  var root = document.getElementById('ivs-app');
  if (!root) return;

  function h(tag, attrs) {
    var el = document.createElement(tag);
    for (var k in attrs || {}) {
      if (k === 'class') el.className = attrs[k];
      else if (k === 'text') el.textContent = attrs[k];
      else if (k.indexOf('on') === 0) el.addEventListener(k.slice(2), attrs[k]);
      else el.setAttribute(k, attrs[k]);
    }
    for (var i = 2; i < arguments.length; i++) {
      var c = arguments[i];
      if (c == null) continue;
      el.appendChild(typeof c === 'string' ? document.createTextNode(c) : c);
    }
    return el;
  }

  var rtf = window.Intl && Intl.RelativeTimeFormat ? new Intl.RelativeTimeFormat('ru', { numeric: 'auto' }) : null;
  function ago(iso) {
    var t = Date.parse(iso);
    if (!t) return '';
    var s = Math.round((t - Date.now()) / 1000), a = Math.abs(s);
    if (!rtf) return new Date(t).toLocaleString();
    if (a < 3600) return rtf.format(Math.round(s / 60), 'minute');
    if (a < 86400) return rtf.format(Math.round(s / 3600), 'hour');
    return rtf.format(Math.round(s / 86400), 'day');
  }

  var state = { filter: 'all', tab: 'activity', open: null };
  var data = null;

  function stats() {
    var done = 0, total = 0, doneP = 0;
    data.phases.forEach(function (p) {
      done += p.done; total += p.total;
      if (p.total && p.done === p.total) doneP++;
    });
    return { done: done, total: total, pct: total ? Math.round(100 * done / total) : 0, doneP: doneP };
  }

  function ring(pct) {
    var R = 56, C = 2 * Math.PI * R;
    var ns = 'http://www.w3.org/2000/svg';
    var svg = document.createElementNS(ns, 'svg');
    svg.setAttribute('width', 132); svg.setAttribute('height', 132); svg.setAttribute('viewBox', '0 0 132 132');
    svg.innerHTML = '<defs><linearGradient id="ivsg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#22d3ee"/><stop offset="1" stop-color="#a78bfa"/></linearGradient></defs>' +
      '<circle class="bg" cx="66" cy="66" r="' + R + '" fill="none" stroke-width="12"/>' +
      '<circle class="fg" cx="66" cy="66" r="' + R + '" fill="none" stroke-width="12" stroke-dasharray="' + C + '" stroke-dashoffset="' + C + '"/>';
    requestAnimationFrame(function () {
      setTimeout(function () { svg.querySelector('.fg').setAttribute('stroke-dashoffset', C * (1 - pct / 100)); }, 60);
    });
    return svg;
  }

  function board() {
    var s = stats();
    var cur = data.phases.filter(function (p) { return p.current; })[0];
    var openPR = data.pulls.filter(function (p) { return p.state === 'open' || p.state === 'draft'; }).length;
    var openIs = data.issues.filter(function (i) { return i.state === 'open'; }).length;
    var lastRun = data.runs[0];

    var hero = h('div', { class: 'ivs-hero' },
      h('div', { class: 'ivs-ring' }, ring(s.pct), h('b', null, s.pct + '', h('small', { text: '%' }))),
      h('div', { class: 'ivs-meta' },
        h('div', { class: 'ivs-big' }, 'Общий прогресс', h('strong', { text: s.done + ' из ' + s.total + ' задач' })),
        h('div', { class: 'ivs-big', style: 'margin:10px 0 6px' }, 'Сейчас в работе', h('strong', { text: cur ? cur.title : 'Все этапы завершены' })),
        (function () { var b = h('div', { class: 'ivs-bar' }, h('i')); requestAnimationFrame(function () { setTimeout(function () { b.firstChild.style.width = s.pct + '%'; }, 60); }); return b; })()
      )
    );

    var st = h('div', { class: 'ivs-stats' },
      stat(s.doneP + '/' + data.phases.length, 'Этапов'),
      stat(openPR, 'PR'),
      stat(openIs, 'Issues'),
      stat(lastRun ? (lastRun.conclusion === 'success' ? '✓' : lastRun.status !== 'completed' ? '…' : '✕') : '—', 'Последний деплой')
    );

    var card1 = h('div', { class: 'ivs-card' }, h('h3', { class: 'ivs-h', text: 'Табло проекта' }), hero, st);

    var tabs = h('div', { class: 'ivs-tabs' });
    [['activity', 'Лог'], ['pulls', 'Pull requests'], ['issues', 'Issues']].forEach(function (t) {
      tabs.appendChild(h('button', { class: 'ivs-tab' + (state.tab === t[0] ? ' on' : ''), type: 'button', text: t[1], onclick: function () { state.tab = t[0]; render(); } }));
    });
    var card2 = h('div', { class: 'ivs-card' }, h('h3', { class: 'ivs-h', text: 'GitHub' }), tabs, feed());
    return h('aside', { class: 'ivs-board' }, card1, card2);
  }

  function stat(v, l) { return h('div', { class: 'ivs-stat' }, h('b', { text: String(v) }), h('span', { text: l })); }

  function item(icon, cls, title, url, meta) {
    return h('li', null,
      h('span', { class: 'ivs-ic ' + cls, text: icon }),
      h('div', null, h('a', { href: url, target: '_blank', rel: 'noopener noreferrer', text: title }), h('div', { class: 'ivs-fm ivs-mono', text: meta }))
    );
  }

  function feed() {
    var ul = h('ul', { class: 'ivs-feed' });
    if (state.tab === 'activity') {
      var ev = [];
      data.runs.forEach(function (r) { ev.push({ d: r.date, r: r }); });
      data.commits.forEach(function (c) { ev.push({ d: c.date, c: c }); });
      ev.sort(function (a, b) { return Date.parse(b.d) - Date.parse(a.d); });
      ev.slice(0, 20).forEach(function (e) {
        if (e.r) {
          var r = e.r, ok = r.conclusion === 'success', run = r.status !== 'completed';
          ul.appendChild(item(run ? '…' : ok ? '✓' : '✕', run ? 'wait' : ok ? 'ok' : 'bad', r.name + ': ' + r.title, r.url, r.branch + ' · ' + r.event + ' · ' + ago(r.date)));
        } else {
          ul.appendChild(item('●', 'cy', e.c.message, e.c.url, e.c.sha + ' · ' + e.c.author + ' · ' + ago(e.c.date)));
        }
      });
      if (!ev.length) ul.appendChild(h('li', { class: 'ivs-empty', text: 'Пока нет событий' }));
    } else if (state.tab === 'pulls') {
      data.pulls.forEach(function (p) {
        var ck = { success: ['✓', 'ok', 'проверки пройдены'], failure: ['✕', 'bad', 'проверки не пройдены'], pending: ['…', 'wait', 'проверки идут'], none: ['—', '', 'без проверок'] }[p.checks] || ['—', '', ''];
        ul.appendChild(item(ck[0], ck[1], '#' + p.number + ' ' + p.title, p.url, p.state + ' · ' + ck[2]));
      });
      if (!data.pulls.length) ul.appendChild(h('li', { class: 'ivs-empty', text: 'Pull requests пока нет' }));
    } else {
      data.issues.forEach(function (i) {
        ul.appendChild(item(i.state === 'open' ? '○' : '✓', i.state === 'open' ? 'vi' : 'ok', '#' + i.number + ' ' + i.title, i.url, i.state));
      });
      if (!data.issues.length) ul.appendChild(h('li', { class: 'ivs-empty', text: 'Issues пока нет' }));
    }
    return ul;
  }

  function plan() {
    var wrap = h('section', { class: 'ivs-card' }, h('h3', { class: 'ivs-h', text: 'План проекта' }));
    var tools = h('div', { class: 'ivs-tools' });
    [['all', 'Все'], ['open', 'В работе'], ['done', 'Готово']].forEach(function (f) {
      tools.appendChild(h('button', { class: 'ivs-chip' + (state.filter === f[0] ? ' on' : ''), type: 'button', text: f[1], onclick: function () { state.filter = f[0]; render(); } }));
    });
    tools.appendChild(h('span', { class: 'sp' }));
    tools.appendChild(h('button', { class: 'ivs-chip', type: 'button', text: 'Развернуть всё', onclick: function () { state.open = 'all'; render(); } }));
    tools.appendChild(h('button', { class: 'ivs-chip', type: 'button', text: 'Свернуть', onclick: function () { state.open = 'none'; render(); } }));
    wrap.appendChild(tools);

    data.phases.forEach(function (p, idx) {
      var full = p.total && p.done === p.total;
      if (state.filter === 'open' && full) return;
      if (state.filter === 'done' && !p.done) return;
      var pct = p.total ? Math.round(100 * p.done / p.total) : 0;
      var isOpen = state.open === 'all' || (state.open !== 'none' && (state.open === idx || (state.open == null && p.current)));
      var m = p.title.match(/^Фаза\s+([^.]+)\.\s*(.*)$/);
      var el = h('div', { class: 'ivs-phase' + (p.current ? ' cur' : '') + (full ? ' done' : '') + (isOpen ? ' open' : '') });
      var head = h('button', { class: 'ivs-ph', type: 'button', 'aria-expanded': String(isOpen), onclick: function () { state.open = isOpen ? 'none' : idx; render(); } },
        h('span', { class: 'ivs-num', text: full ? '✓' : (m ? m[1] : String(idx)) }),
        h('span', { class: 'ivs-pt' }, m ? m[2] : p.title, h('small', { text: p.done + ' из ' + p.total + (p.current ? ' · сейчас' : '') })),
        h('span', { class: 'ivs-pct ivs-mono', text: pct + '%' }),
        h('span', { class: 'ivs-chev', text: '›' })
      );
      el.appendChild(head);
      var bar = h('div', { class: 'ivs-pb' }, h('div', { class: 'ivs-bar sm' }, h('i', { style: 'width:' + pct + '%' })));
      el.appendChild(bar);
      var ul = h('ul', { class: 'ivs-list' });
      p.items.forEach(function (it) {
        if (state.filter === 'open' && it.done) return;
        if (state.filter === 'done' && !it.done) return;
        ul.appendChild(h('li', { class: it.done ? 'done' : '' }, h('span', { class: 'ivs-ck', text: '✓' }), h('span', { text: it.text })));
      });
      el.appendChild(ul);
      wrap.appendChild(el);
    });
    return wrap;
  }

  function render() {
    var upd = Date.parse(data.generated_at);
    var head = h('header', { class: 'ivs-head' },
      h('h2', { class: 'ivs-title' }, 'Ivancovs ', h('span', { text: 'Project Status' })),
      h('div', { class: 'ivs-live ivs-mono' }, h('span', { class: 'ivs-dot' }), 'обновлено ' + (upd ? ago(data.generated_at) : '') + (data.branch ? ' · ' + data.branch : ''))
    );
    root.textContent = '';
    root.appendChild(head);
    root.appendChild(h('div', { class: 'ivs-grid' }, plan(), board()));
  }

  fetch(root.dataset.json + '?t=' + Date.now(), { cache: 'no-store' })
    .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
    .then(function (d) { data = d; render(); })
    .catch(function () { root.textContent = ''; root.appendChild(h('p', { class: 'ivs-err', text: 'Не удалось загрузить статус проекта.' })); });
})();
