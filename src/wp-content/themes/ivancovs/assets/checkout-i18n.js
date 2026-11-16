(function () {
  var T = window.IVS_I18N;
  if (!T) return;
  var exact = {
    'Additional order information': T.extra,
    'Add coupons': T.coupon,
    'Phone': T.phone
  };
  function fix(s) {
    var t = s.trim();
    if (!t) return s;
    if (Object.prototype.hasOwnProperty.call(exact, t)) return s.replace(t, exact[t]);
    var out = s.replace(/\(optional\)/g, '(' + T.optional + ')').replace(/^(\s*)\+ Add\b/, '$1+ ' + T.add);
    out = out.replace(/^Phone\b/, T.phone);
    out = out.replace(/^Total price for (\d+) .* items?: (.*)$/, T.total + ' $1: $2');
    return out;
  }
  function walk(n) {
    if (n.nodeType === 3) {
      var v = fix(n.nodeValue);
      if (v !== n.nodeValue) n.nodeValue = v;
      return;
    }
    if (n.nodeType !== 1 || /^(SCRIPT|STYLE|TEXTAREA|INPUT)$/.test(n.tagName)) return;
    for (var c = n.firstChild; c; c = c.nextSibling) walk(c);
  }
  function placeholders(root) {
    var els = (root.querySelectorAll ? root.querySelectorAll('label, [placeholder]') : []);
    for (var i = 0; i < els.length; i++) {
      var ph = els[i].getAttribute && els[i].getAttribute('placeholder');
      if (ph) { var f = fix(ph); if (f !== ph) els[i].setAttribute('placeholder', f); }
    }
  }
  var busy = false;
  function run() {
    if (busy) return;
    busy = true;
    walk(document.body);
    placeholders(document);
    busy = false;
  }
  run();
  new MutationObserver(run).observe(document.body, { childList: true, subtree: true, characterData: true });
})();
