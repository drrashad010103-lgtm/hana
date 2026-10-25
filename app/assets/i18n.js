(function () {
  'use strict';
  var lang = window.__RS_LANG || 'ar';
  var base = window.__RS_I18N_BASE || '/assets/i18n/';
  var langs = window.__RS_LANGS || { ar: 'العربية' };
  var dict = {};
  var fallback = {};
  var prefixKeys = [];

  function has(obj, key) {
    return Object.prototype.hasOwnProperty.call(obj, key);
  }

  function lookup(text) {
    if (has(dict, text)) return dict[text];
    if (has(fallback, text)) return fallback[text];
    return null;
  }

  function buildPrefixes() {
    prefixKeys = Object.keys(dict)
      .filter(function (k) { return /[:：]\s*$/.test(k); })
      .sort(function (a, b) { return b.length - a.length; });
  }

  function translateCore(core) {
    var direct = lookup(core);
    if (direct !== null) return direct;

    var trimmed = core.replace(/^[^\p{L}\p{N}]+/u, '').replace(/[^\p{L}\p{N}]+$/u, '');
    if (trimmed !== core) {
      var t = lookup(trimmed);
      if (t !== null) return t;
    }
    return null;
  }

  function translateText(value) {
    if (!value) return value;
    var lead = value.match(/^\s*/)[0];
    var tail = value.match(/\s*$/)[0];
    var core = value.trim();
    if (!core) return value;

    var direct = lookup(core);
    if (direct !== null) return lead + direct + tail;

    for (var i = 0; i < prefixKeys.length; i++) {
      var key = prefixKeys[i];
      if (core.indexOf(key) === 0) {
        var rest = core.slice(key.length);
        var head = lookup(key);
        if (head !== null) return lead + head + rest + tail;
      }
    }

    var trimmed = core.replace(/^[^\p{L}\p{N}]+/u, '');
    if (trimmed !== core) {
      var t = lookup(trimmed);
      if (t !== null) {
        return value.replace(trimmed, t);
      }
    }
    return value;
  }

  function translateTextNode(node) {
    var parent = node.parentNode;
    if (!parent) return;
    var tag = (parent.nodeName || '').toUpperCase();
    if (tag === 'SCRIPT' || tag === 'STYLE' || tag === 'TEXTAREA' || tag === 'NOSCRIPT') return;
    if (parent.closest && parent.closest('.rs-lang-pick,[data-no-i18n],.no-i18n')) return;
    var next = translateText(node.nodeValue);
    if (next !== node.nodeValue) node.nodeValue = next;
  }

  function translateAttributes(el) {
    ['placeholder', 'title', 'aria-label'].forEach(function (attr) {
      if (!el.getAttribute) return;
      var v = el.getAttribute(attr);
      if (!v) return;
      var n = translateText(v);
      if (n !== v) el.setAttribute(attr, n);
    });
  }

  function translateElement(root) {
    if (!root) return;
    if (root.nodeType === 3) {
      translateTextNode(root);
      return;
    }
    if (root.nodeType !== 1 && root.nodeType !== 9) return;
    if (root.nodeType === 1) {
      if (root.matches && root.matches('.rs-lang-pick,[data-no-i18n],.no-i18n,script,style,textarea')) return;
      translateAttributes(root);
      var attrs = root.querySelectorAll ? root.querySelectorAll('[placeholder],[title],[aria-label]') : [];
      for (var a = 0; a < attrs.length; a++) translateAttributes(attrs[a]);
    }
    var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
      acceptNode: function (n) {
        var p = n.parentNode;
        if (!p) return NodeFilter.FILTER_REJECT;
        var tag = (p.nodeName || '').toUpperCase();
        if (tag === 'SCRIPT' || tag === 'STYLE' || tag === 'TEXTAREA' || tag === 'NOSCRIPT') return NodeFilter.FILTER_REJECT;
        if (p.closest && p.closest('.rs-lang-pick,[data-no-i18n],.no-i18n')) return NodeFilter.FILTER_REJECT;
        return n.nodeValue && n.nodeValue.trim() ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
      }
    });
    var node;
    while ((node = walker.nextNode())) translateTextNode(node);
  }

  function buildPicker() {
    if (document.querySelector('.rs-lang-pick')) return;
    var box = document.createElement('div');
    box.className = 'rs-lang-pick no-print';
    box.setAttribute('data-no-i18n', '1');
    box.title = 'Language';
    var globe = document.createElement('span');
    globe.className = 'rs-globe';
    globe.textContent = '🌐';
    var sel = document.createElement('select');
    sel.setAttribute('aria-label', 'Language');
    Object.keys(langs).forEach(function (code) {
      var opt = document.createElement('option');
      opt.value = code;
      opt.textContent = langs[code];
      if (code === lang) opt.selected = true;
      sel.appendChild(opt);
    });
    sel.addEventListener('change', function () {
      var back = location.pathname + location.search + location.hash;
      location.href = '/pages/lang.php?set=' + encodeURIComponent(sel.value) + '&back=' + encodeURIComponent(back);
    });
    box.appendChild(globe);
    box.appendChild(sel);
    document.body.appendChild(box);
  }

  function watch() {
    if (!window.MutationObserver) return;
    var queued = new Set();
    var timer = 0;
    var obs = new MutationObserver(function (items) {
      for (var i = 0; i < items.length; i++) {
        var m = items[i];
        if (m.type === 'characterData') {
          queued.add(m.target);
        } else {
          for (var j = 0; j < m.addedNodes.length; j++) queued.add(m.addedNodes[j]);
        }
      }
      if (!queued.size || timer) return;
      timer = setTimeout(function () {
        timer = 0;
        queued.forEach(function (n) {
          if (n.isConnected !== false) translateElement(n);
        });
        queued.clear();
      }, 30);
    });
    obs.observe(document.body, { childList: true, subtree: true, characterData: true });
  }

  function translateTitle() {
    if (!document.title) return;
    var parts = document.title.split(' — ');
    var changed = false;
    parts = parts.map(function (p) {
      var n = translateText(p);
      if (n !== p) changed = true;
      return n;
    });
    if (changed) document.title = parts.join(' — ');
  }

  function init() {
    buildPicker();
    if (document.body) document.body.setAttribute('data-lang', lang);
    if (lang === 'ar') return;

    var wanted = lang;
    var jobs = [];
    if (wanted !== 'en') jobs.push(fetch(base + 'en.json').then(function (r) { return r.json(); }).then(function (j) { fallback = j || {}; }).catch(function () {}));
    jobs.push(fetch(base + wanted + '.json').then(function (r) { return r.json(); }).then(function (j) { dict = j || {}; }).catch(function () {}));
    Promise.all(jobs).then(function () {
      buildPrefixes();
      translateElement(document.body);
      translateTitle();
      watch();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
