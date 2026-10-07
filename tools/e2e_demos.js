// Prueba las demos (index, chat, panel) en un navegador real. Sale con código 1 si algo falla.
const path = require('path'); const launch = require('./launch');
const demo = (f) => 'file://' + path.resolve(__dirname, '..', 'demo', f);
let fails = 0; const ok = (l, c) => { console.log((c ? 'OK   ' : 'FAIL ') + l); if (!c) fails++; };
(async () => {
  const b = await launch(); const p = await (await b.newContext({ viewport: { width: 1360, height: 900 }, acceptDownloads: true })).newPage();
  const errs = []; const ext = [];
  p.on('pageerror', (e) => errs.push(e.message));
  p.on('request', (r) => { const u = r.url(); if (!u.startsWith('file:') && !u.startsWith('data:')) ext.push(u); });
  const vis = () => p.evaluate(() => [...document.querySelectorAll('.page')].filter((x) => !x.hidden).map((x) => x.dataset.page)[0]);
  // portada y chat
  await p.goto(demo('index.html')); ok('portada con dos demos', (await p.locator('a.card').count()) === 2);
  await p.click('a[href="chat.html"]'); await p.waitForSelector('.cpnnet-chat__chip');
  for (let i = 0; i < 3; i++) { await p.click('.cpnnet-chat__chip'); await p.waitForTimeout(1250); }
  ok('chat: la conversación llega al lead de WhatsApp', (await p.textContent('#lead')).startsWith('Nuevo lead') && (await p.locator('.cpnnet-chat__wa').count()) === 1);
  ok('chat: Montserrat cargada', await p.evaluate(() => document.fonts.check('600 14px "Montserrat CPN"')));
  await p.click('a.back'); ok('chat: vuelve a la portada', p.url().endsWith('index.html'));
  // panel
  await p.click('a[href="panel.html"]'); await p.waitForSelector('.page:not([hidden]) .cpn-kpis');
  const k30 = (await p.locator('.page:not([hidden]) .cpn-kpi-value').allTextContents()).join();
  await p.click('.page:not([hidden]) a.cpn-chip:has-text("7 días")'); ok('panel: cambiar de periodo actualiza los números', (await vis()) === 'panel-7' && (await p.locator('.page:not([hidden]) .cpn-kpi-value').allTextContents()).join() !== k30);
  await p.click('#side a[data-nav=leads]'); const total = await p.locator('.page:not([hidden]) .cpn-table tbody tr').count(); ok('panel: lista de leads (' + total + ')', total > 0);
  await p.selectOption('.page:not([hidden]) select[name=status]', 'nuevo'); await p.click('.page:not([hidden]) .cpn-filters button.button-primary');
  const f = await p.locator('.page:not([hidden]) .cpn-table tbody tr:visible').count(); ok('panel: el filtro por estado reduce la lista', f > 0 && f < total);
  await p.selectOption('.page:not([hidden]) select[name=status]', ''); await p.click('.page:not([hidden]) .cpn-filters button.button-primary');
  const [dl] = await Promise.all([p.waitForEvent('download'), p.click('.page:not([hidden]) a:has-text("Exportar CSV")')]); ok('panel: exporta CSV', dl.suggestedFilename().endsWith('.csv'));
  await p.click('.page:not([hidden]) .cpn-table tbody tr:first-child a'); ok('panel: ficha con conversación', /^lead-/.test(await vis()) && (await p.locator('.page:not([hidden]) .cpn-bubble').count()) >= 2);
  await p.click('#side a[data-nav=integ]'); ok('panel: integración CRM', (await p.textContent('.page:not([hidden])')).includes('Webhook'));
  await p.click('#side a[data-nav=cfg]'); await p.click('.page:not([hidden]) a.nav-tab:has-text("Conocimiento")'); ok('panel: editor de marcas', (await p.locator('.page:not([hidden]) table.widefat tbody tr').count()) >= 20);
  ok('sin errores de JavaScript', errs.length === 0); ok('sin recursos externos', ext.length === 0);
  await b.close(); process.exit(fails ? 1 : 0);
})();
