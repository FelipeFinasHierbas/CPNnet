// Capturas de las demos de Kind Force para la presentación (presentacion-kindforce/img/).
const path = require('path'); const launch = require('./launch');
const root = path.resolve(__dirname, '..'); const out = f => path.join(root, 'presentacion-kindforce', 'img', f);
const shot = async (p, el, file, maxH = 820) => { await el.scrollIntoViewIfNeeded(); const bb = await el.boundingBox(); await p.screenshot({ path: out(file), fullPage: true, clip: { x: bb.x, y: bb.y + (await p.evaluate(() => scrollY)), width: bb.width, height: Math.min(bb.height, maxH) } }); };
(async () => {
  const b = await launch(); const p = await b.newPage({ viewport: { width: 1280, height: 900 }, deviceScaleFactor: 2 });
  // Chat: escenario 1 (anuncio → clase de prueba) y escenario 2 (planes)
  await p.goto('file://' + path.join(root, 'demo/pilates-chat.html'));
  const play = async (n, file, top = false) => {
    await p.click(`#scen button:nth-child(${n})`);
    for (let i = 0; i < 4; i++) { const c = await p.$('.cpnnet-chat__chips button'); if (!c || !(await c.isVisible())) break; await c.click(); await p.waitForTimeout(1400); }
    await p.evaluate(t => { const l = document.querySelector('.cpnnet-chat__log'); l.scrollTop = t ? 0 : l.scrollHeight; }, top);
    await (await p.$('#chat')).screenshot({ path: out(file) });
    await (await p.$('#lead')).screenshot({ path: out(file.replace('.png', '-lead.png')) });
  };
  await play(1, 'chat-prueba.png'); await play(2, 'chat-planes.png', true); await play(3, 'chat-4clases.png');
  // Reservas
  await p.goto('file://' + path.join(root, 'demo/pilates-reservas.html'));
  const tab = async t => { await p.click(`nav.tabs button[data-tab=${t}]`); await p.waitForTimeout(300); };
  await tab('perfil'); await shot(p, await p.$('section[data-tab=perfil] .site'), 'perfil.png', 900);
  await tab('cambio'); await p.evaluate(() => document.querySelector('section[data-tab=cambio] #horario').scrollIntoView());
  await shot(p, await p.$('section[data-tab=cambio] #horario'), 'cambio.png', 640);
  await tab('correos');
  for (const [i, f] of [[0, 'mail-pago.png'], [1, 'mail-reserva.png'], [2, 'mail-recordatorio.png'], [3, 'mail-cambio.png'], [4, 'mail-vence.png'], [5, 'mail-cancelada.png']]) {
    await p.click(`.mailnav button[data-mail="${i}"]`); await p.waitForTimeout(300);
    const fr = await (await p.$(`.mail[data-mail="${i}"] iframe`)).contentFrame();
    const t = await fr.$('table table'); await t.screenshot({ path: out(f) });
  }
  await tab('agenda'); await shot(p, await p.$('section[data-tab=agenda] .wpadmin'), 'agenda.png', 760);
  await tab('alumnos'); await shot(p, await p.$('section[data-tab=alumnos] .wpadmin'), 'alumnos.png', 760);
  await tab('config'); await shot(p, await p.$('section[data-tab=config] .wpadmin'), 'config.png', 900);
  await p.setViewportSize({ width: 400, height: 860 }); await tab('perfil');
  await shot(p, await p.$('section[data-tab=perfil] .cpn-portal'), 'movil.png', 1100);
  await b.close(); console.log('OK capturas');
})();
