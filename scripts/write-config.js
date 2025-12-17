const fs = require('fs');
const path = require('path');

const backend = process.env.BACKEND_BASE_URL || '';
const targetDir = path.join(__dirname, '..', 'frontend-ecowaste', 'js');
if (!fs.existsSync(targetDir)) fs.mkdirSync(targetDir, { recursive: true });
const out = `window.__BACKEND_BASE_URL__ = ${JSON.stringify(backend)};\n`;
fs.writeFileSync(path.join(targetDir, 'config.js'), out);
console.log('Wrote frontend config.js with BACKEND_BASE_URL =', backend);
