// Lanza Chromium. Si CHROMIUM_PATH está definido se usa ese ejecutable (útil en entornos sin descarga de navegadores).
const { chromium } = require('playwright');
module.exports = (opts = {}) => chromium.launch({ executablePath: process.env.CHROMIUM_PATH || undefined, ...opts });
