// Genera presentacion/Asistente-comercial-CPNnet.pdf desde presentacion/presentacion.html
const path = require('path'); const launch = require('./launch');
const root = path.resolve(__dirname, '..', 'presentacion');
(async () => {
  const b = await launch(); const p = await b.newPage({ viewport: { width: 1280, height: 720 } });
  await p.goto('file://' + path.join(root, 'presentacion.html'));
  await p.evaluate(() => document.fonts.ready);
  await p.pdf({ path: path.join(root, 'Asistente-comercial-CPNnet.pdf'), width: '1280px', height: '720px', printBackground: true });
  await b.close(); console.log('OK presentacion/Asistente-comercial-CPNnet.pdf');
})();
