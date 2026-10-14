// Genera presentacion-kindforce/Kind-Force-Asistente-y-reservas.pdf y vistas previas PNG de cada diapositiva.
const path = require('path'); const fs = require('fs'); const launch = require('./launch');
const root = path.resolve(__dirname, '..', 'presentacion-kindforce');
(async () => {
  const b = await launch(); const p = await b.newPage({ viewport: { width: 1280, height: 720 } });
  await p.goto('file://' + path.join(root, 'presentacion.html')); await p.evaluate(() => document.fonts.ready); await p.waitForTimeout(500);
  await p.pdf({ path: path.join(root, 'Kind-Force-Asistente-y-reservas.pdf'), width: '1280px', height: '720px', printBackground: true });
  // Versión móvil (vertical 9:16)
  const m = await b.newPage({ viewport: { width: 405, height: 720 } });
  await m.goto('file://' + path.join(root, 'presentacion-movil.html')); await m.evaluate(() => document.fonts.ready); await m.waitForTimeout(500);
  await m.pdf({ path: path.join(root, 'Kind-Force-Asistente-y-reservas-movil.pdf'), width: '405px', height: '720px', printBackground: true });
  if (process.argv[2]) { const s = await m.$$('section.slide'); for (let i = 0; i < s.length; i++) await s[i].screenshot({ path: path.join(process.argv[2], `m${String(i + 1).padStart(2, '0')}.png`) }); }
  if (process.argv[2]) { fs.mkdirSync(process.argv[2], { recursive: true }); const s = await p.$$('section.slide'); for (let i = 0; i < s.length; i++) await s[i].screenshot({ path: path.join(process.argv[2], `s${String(i + 1).padStart(2, '0')}.png`) }); }
  await b.close(); console.log('OK PDF');
})();
