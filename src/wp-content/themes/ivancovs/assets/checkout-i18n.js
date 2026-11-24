(function () {
  var T = window.IVS_I18N;
  if (!T) return;
  var exact = {
    'Additional order information': T.extra,
    'Add coupons': T.coupon,
    'Phone': T.phone,
    'Proceed to Checkout': T.proceed,
    'Estimated total': T.estimate
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

  /* Кнопка «Заполнить реквизиты» рядом с полем «Фирма»: берёт рег. номер и подставляет название, адрес, PVN. */
  function setVal(input, value) {
    var setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
    setter.call(input, value);
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
    input.dispatchEvent(new Event('blur', { bubbles: true }));
  }
  function near(company, suffix) {
    var form = company.closest('form') || document;
    var list = form.querySelectorAll('input[id$="' + suffix + '"]');
    for (var i = 0; i < list.length; i++) {
      if (/^billing/.test(list[i].id)) return list[i];
    }
    return list[0] || null;
  }
  function addLookup() {
    if (!T.lookupUrl) return;
    var inputs = document.querySelectorAll('input[id$="company"]');
    for (var i = 0; i < inputs.length; i++) {
      var company = inputs[i];
      var box = company.closest('.wc-block-components-text-input') || company.parentNode;
      if (!box || box.parentNode.querySelector('.ivs-lookup')) continue;
      var wrap = document.createElement('div');
      wrap.className = 'ivs-lookup';
      wrap.style.cssText = 'margin:6px 0 12px;flex:0 0 100%;width:100%';
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.textContent = T.lookupBtn;
      btn.style.cssText = 'padding:8px 14px;border:1px solid currentColor;border-radius:6px;background:transparent;color:inherit;cursor:pointer;font:inherit;font-size:14px';
      var msg = document.createElement('div');
      msg.setAttribute('role', 'status');
      msg.style.cssText = 'margin-top:6px;font-size:13px';
      wrap.appendChild(btn);
      wrap.appendChild(msg);
      box.parentNode.insertBefore(wrap, box.nextSibling);
      btn.addEventListener('click', makeHandler(company, btn, msg));
    }
  }
  function makeHandler(company, btn, msg) {
    return function () {
      var regInput = near(company, 'reg-nr');
      var reg = regInput ? regInput.value.replace(/^\s*LV/i, '').replace(/\D+/g, '') : '';
      if (reg.length !== 11) {
        msg.textContent = T.lookupNeed;
        if (regInput) regInput.focus();
        return;
      }
      btn.disabled = true;
      var label = btn.textContent;
      btn.textContent = T.lookupBusy;
      msg.textContent = '';
      fetch(T.lookupUrl + (T.lookupUrl.indexOf('?') > -1 ? '&' : '?') + 'reg=' + reg, { credentials: 'omit' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (!d || !d.found) { msg.textContent = T.lookupNone; return; }
          setVal(company, d.name || '');
          var a = near(company, 'legal-address');
          if (a && d.address) setVal(a, d.address);
          var v = near(company, 'vat-nr');
          if (v && d.vat) setVal(v, d.vat);
          if (regInput) setVal(regInput, reg);
          msg.textContent = d.active === false ? T.lookupOff : T.lookupOk;
        })
        .catch(function () { msg.textContent = T.lookupErr; })
        .then(function () { btn.disabled = false; btn.textContent = label; });
    };
  }
  addLookup();
  new MutationObserver(addLookup).observe(document.body, { childList: true, subtree: true });
})();
