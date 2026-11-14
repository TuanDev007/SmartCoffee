const http = require('node:http');
const path = require('node:path');
const fs = require('node:fs');
const { spawn } = require('node:child_process');

const introAssets = path.join(__dirname, 'public/assets/intro');
const websiteAssets = path.join(__dirname, 'public/assets/website');
const types = {
  '.html': 'text/html; charset=utf-8', '.css': 'text/css; charset=utf-8',
  '.js': 'application/javascript', '.json': 'application/json',
  '.webp': 'image/webp', '.png': 'image/png', '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg', '.svg': 'image/svg+xml', '.ico': 'image/x-icon',
  '.woff2': 'font/woff2', '.woff': 'font/woff', '.ttf': 'font/ttf',
  '.mp3': 'audio/mpeg', '.ogg': 'audio/ogg', '.mp4': 'video/mp4',
  '.glb': 'model/gltf-binary', '.gltf': 'model/gltf+json', '.wasm': 'application/wasm'
};

const server = http.createServer((req, res) => {
  if (!['GET', 'HEAD'].includes(req.method)) {
    res.writeHead(405, { Allow: 'GET, HEAD' }).end();
    return;
  }
  let pathname;
  try {
    pathname = decodeURIComponent(new URL(req.url, 'http://localhost').pathname);
    if (pathname.includes('\0')) throw new Error('Invalid path');
  } catch {
    res.writeHead(400).end();
    return;
  }
  if (pathname === '/Your-Cafe-3D/index.html') pathname = '/redirect.html';
  const isSmartCoffee = pathname === '/brunivo' || pathname.startsWith('/brunivo/');

  let relativePath = isSmartCoffee ? pathname.slice('/brunivo'.length) : pathname;
  if (!relativePath || relativePath.endsWith('/')) relativePath += 'index.html';
  const isView = relativePath.toLowerCase().endsWith('.html');
  const servedRoot = isView
    ? path.join(__dirname, 'app/Views', isSmartCoffee ? 'brunivo' : 'intro')
    : (isSmartCoffee ? websiteAssets : introAssets);
  if (!isView) {
    if (relativePath.startsWith('/assets/')) relativePath = relativePath.slice('/assets'.length);
    else if (!isSmartCoffee && /^\/[\w-]+\.(jpg|png)$/.test(relativePath)) relativePath = '/images' + relativePath;
    else { res.writeHead(404).end(); return; }
  }
  const file = path.resolve(servedRoot, '.' + relativePath);
  if (!file.startsWith(servedRoot + path.sep)) {
    res.writeHead(403).end();
    return;
  }
  fs.stat(file, (error, stat) => {
    if (error || !stat.isFile()) {
      res.writeHead(404).end();
      return;
    }
    res.writeHead(200, {
      'Content-Type': types[path.extname(file).toLowerCase()] || 'application/octet-stream',
      'Content-Length': stat.size
    });
    if (req.method === 'HEAD') res.end();
    else fs.createReadStream(file).on('error', () => res.destroy()).pipe(res);
  });
});

server.on('error', (error) => {
  if (error.code === 'EADDRINUSE') server.listen(0, '127.0.0.1');
  else { console.error(error.message); process.exitCode = 1; }
});
server.listen(4178, '127.0.0.1', () => {
  const url = 'http://127.0.0.1:' + server.address().port + '/';
  console.log('SMARTCOFFEE  |  ' + url + '\nGiu cua so nay khi xem. Nhan Ctrl+C de dong.');
  if (process.argv.includes('--open') && process.platform === 'win32') {
    const browser = spawn('rundll32.exe', ['url.dll,FileProtocolHandler', url], {
      detached: true, stdio: 'ignore', windowsHide: true
    });
    browser.on('error', () => console.log('Mo dia chi tren bang trinh duyet.'));
    browser.unref();
  }
});


