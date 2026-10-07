// Regenera las capturas que usa la presentación (presentacion/*.png) a partir de las demos. Ejecutar antes de build_pdf.js.
const path = require('path'); const launch = require('./launch');
const demo = (f) => 'file://' + path.resolve(__dirname, '..', 'demo', f);
const out = (f) => path.resolve(__dirname, '..', 'presentacion', f);
(async () => {
  const b = await launch(); const p = await (await b.newContext({ viewport: { width: 1360, height: 900 }, deviceScaleFactor: 2 })).newPage();
  await p.goto(demo('chat.html'));
  for (let i = 0; i < 3; i++) { await p.click('.cpnnet-chat__chip'); await p.waitForTimeout(1300); }
  await p.locator('.cpnnet-chat__panel').screenshot({ path: out('chat-a.png') });
  await p.goto(demo('panel.html') + '#panel-30'); await p.waitForTimeout(300);
  let box = await p.locator('.page:not([hidden])').boundingBox();
  await p.screenshot({ path: out('panel-comercial.png'), clip: { x: box.x - 4, y: box.y - 4, width: box.width + 8, height: 880 } });
  await p.goto(demo('panel.html') + '#lead-3'); await p.waitForTimeout(300);
  box = await p.locator('.page:not([hidden])').boundingBox();
  await p.screenshot({ path: out('ficha-lead.png'), clip: { x: box.x - 4, y: box.y - 4, width: box.width + 8, height: 700 } });
  await b.close(); console.log('OK capturas en presentacion/');
})();
