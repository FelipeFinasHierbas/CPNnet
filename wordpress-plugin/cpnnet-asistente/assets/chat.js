(function () {
  'use strict';
  var cfg = window.CPNNET_ASISTENTE;
  if (!cfg || !cfg.endpoint) return;

  var STORE = 'cpnnet_asistente_chat';
  var MAX_SEND = 20;
  var state = { open: false, busy: false, messages: [], convId: '' };

  try {
    var saved = JSON.parse(sessionStorage.getItem(STORE) || 'null');
    if (saved && Array.isArray(saved.messages)) state.messages = saved.messages;
    if (saved && typeof saved.convId === 'string') state.convId = saved.convId;
  } catch (e) {}

  function newId() {
    if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
    var a = new Uint8Array(16); (window.crypto || window.msCrypto).getRandomValues(a);
    return Array.prototype.map.call(a, function (b) { return ('0' + b.toString(16)).slice(-2); }).join('');
  }
  if (!/^[A-Za-z0-9-]{8,40}$/.test(state.convId)) state.convId = newId();

  function persist() {
    try { sessionStorage.setItem(STORE, JSON.stringify({ messages: state.messages, convId: state.convId })); } catch (e) {}
  }

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text != null) n.textContent = text;
    return n;
  }

  // Render mínimo y seguro: todo es texto; solo **negrita** y saltos de línea.
  function renderText(container, text) {
    text.split('\n').forEach(function (line, i) {
      if (i) container.appendChild(document.createElement('br'));
      line.split(/(\*\*[^*]+\*\*)/g).forEach(function (part) {
        if (/^\*\*[^*]+\*\*$/.test(part)) {
          container.appendChild(el('strong', null, part.slice(2, -2)));
        } else if (part) {
          container.appendChild(document.createTextNode(part));
        }
      });
    });
  }

  var root = el('div', 'cpnnet-chat');
  var launcher = el('button', 'cpnnet-chat__launcher');
  launcher.type = 'button';
  launcher.setAttribute('aria-label', 'Abrir asistente de CPNnet');
  launcher.innerHTML = '<svg viewBox="0 0 24 24" width="26" height="26" aria-hidden="true"><path fill="currentColor" d="M12 3C6.5 3 2 6.9 2 11.7c0 2.5 1.2 4.7 3.2 6.3L4.5 21l3.6-1.8c1.2.4 2.5.5 3.9.5 5.5 0 10-3.9 10-8.7S17.5 3 12 3z"/></svg>';

  var panel = el('section', 'cpnnet-chat__panel');
  panel.setAttribute('role', 'dialog');
  panel.setAttribute('aria-label', 'Asistente virtual de CPNnet Security');
  panel.hidden = true;

  var header = el('header', 'cpnnet-chat__header');
  var brand = el('div', 'cpnnet-chat__brand');
  if (cfg.logo) {
    var logo = el('img', 'cpnnet-chat__logo');
    logo.src = cfg.logo; logo.alt = 'CPNnet Security';
    brand.appendChild(logo);
  }
  var title = el('div', 'cpnnet-chat__title');
  title.appendChild(el('strong', null, cfg.logo ? 'Asistente virtual' : 'CPNnet Security'));
  title.appendChild(el('span', null, 'Con inteligencia artificial'));
  brand.appendChild(title);
  var close = el('button', 'cpnnet-chat__close', '×');
  close.type = 'button';
  close.setAttribute('aria-label', 'Cerrar');
  header.appendChild(brand);
  header.appendChild(close);

  var log = el('div', 'cpnnet-chat__log');
  log.setAttribute('aria-live', 'polite');
  var chips = el('div', 'cpnnet-chat__chips');

  var form = el('form', 'cpnnet-chat__form');
  var input = el('textarea', 'cpnnet-chat__input');
  input.rows = 1;
  input.maxLength = 1500;
  input.placeholder = 'Escribe tu consulta…';
  input.setAttribute('aria-label', 'Tu mensaje');
  var hp = el('input'); // honeypot
  hp.type = 'text'; hp.name = 'website'; hp.tabIndex = -1; hp.autocomplete = 'off';
  hp.style.cssText = 'position:absolute;left:-9999px;opacity:0;height:0;width:0';
  var send = el('button', 'cpnnet-chat__send', 'Enviar');
  send.type = 'submit';
  form.appendChild(input);
  form.appendChild(hp);
  form.appendChild(send);

  var note = el('p', 'cpnnet-chat__note', 'Asistente con IA. No compartas contraseñas ni datos sensibles. Al dejar tus datos aceptas que los use el equipo comercial de CPNnet para contactarte.');

  panel.appendChild(header);
  panel.appendChild(log);
  panel.appendChild(chips);
  panel.appendChild(form);
  panel.appendChild(note);
  root.appendChild(panel);
  root.appendChild(launcher);
  document.body.appendChild(root);

  function addBubble(role, text, waUrl) {
    var b = el('div', 'cpnnet-chat__msg cpnnet-chat__msg--' + role);
    renderText(b, text);
    if (waUrl) {
      var a = el('a', 'cpnnet-chat__wa', 'Continuar por WhatsApp con un ejecutivo');
      a.href = waUrl; a.target = '_blank'; a.rel = 'noopener noreferrer';
      b.appendChild(document.createElement('br'));
      b.appendChild(a);
    }
    log.appendChild(b);
    log.scrollTop = log.scrollHeight;
    return b;
  }

  function renderAll() {
    log.textContent = '';
    addBubble('assistant', cfg.welcome || '¡Hola! ¿En qué te puedo ayudar?');
    state.messages.forEach(function (m) { addBubble(m.role, m.content, m.waUrl); });
    chips.hidden = state.messages.length > 0;
  }

  [
    'Soy partner / integrador',
    'Busco una solución para mi empresa'
  ].forEach(function (label) {
    var c = el('button', 'cpnnet-chat__chip', label);
    c.type = 'button';
    c.addEventListener('click', function () { submit(label); });
    chips.appendChild(c);
  });

  function setBusy(b) {
    state.busy = b;
    send.disabled = b;
    input.disabled = b;
  }

  function submit(text) {
    text = (text || '').trim();
    if (!text || state.busy) return;
    chips.hidden = true;
    state.messages.push({ role: 'user', content: text });
    addBubble('user', text);
    input.value = '';
    persist();
    setBusy(true);
    var typing = addBubble('assistant', 'Escribiendo…');
    typing.classList.add('cpnnet-chat__msg--typing');

    var payload = state.messages.slice(-MAX_SEND).map(function (m) { return { role: m.role, content: m.content }; });
    fetch(cfg.endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ messages: payload, website: hp.value, conversation_id: state.convId, page: location.pathname })
    })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
      .then(function (res) {
        typing.remove();
        if (!res.ok || !res.body || !res.body.reply) {
          var msg = (res.body && (res.body.error || res.body.message)) || 'No pude responder en este momento. Intenta nuevamente.';
          addBubble('assistant', msg);
          return;
        }
        state.messages.push({ role: 'assistant', content: res.body.reply, waUrl: res.body.whatsapp_url || null });
        addBubble('assistant', res.body.reply, res.body.whatsapp_url);
        persist();
      })
      .catch(function () {
        typing.remove();
        addBubble('assistant', 'No pude conectarme. Revisa tu conexión e intenta nuevamente.');
      })
      .then(function () { setBusy(false); input.focus(); });
  }

  function toggle(open) {
    state.open = open;
    panel.hidden = !open;
    launcher.hidden = open;
    if (open) { renderAll(); input.focus(); }
  }

  launcher.addEventListener('click', function () { toggle(true); });
  close.addEventListener('click', function () { toggle(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && state.open) toggle(false); });
  form.addEventListener('submit', function (e) { e.preventDefault(); submit(input.value); });
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); submit(input.value); }
  });
})();
