const { createServer } = require('node:http');
const { readFile } = require('node:fs/promises');
const { execFile } = require('node:child_process');
const path = require('node:path');

const root = path.join(__dirname, 'frontend');
const host = '127.0.0.1';
const port = 8100;
const url = `http://${host}:${port}`;
const contentTypes = {
    '.html': 'text/html; charset=utf-8',
    '.css': 'text/css; charset=utf-8',
    '.js': 'text/javascript; charset=utf-8',
    '.json': 'application/json; charset=utf-8',
};

const server = createServer(async (request, response) => {
    if (request.method !== 'GET' && request.method !== 'HEAD') {
        response.writeHead(405, { Allow: 'GET, HEAD' }).end();
        return;
    }

    let pathname;
    try {
        pathname = decodeURIComponent(new URL(request.url, url).pathname);
    } catch {
        response.writeHead(400).end();
        return;
    }

    if (pathname === '/') { response.writeHead(302, { Location: '/phuc-vu/' }).end(); return; }
    const file = path.resolve(root, '.' + (pathname.endsWith('/') ? pathname + 'index.html' : pathname));
    const relative = path.relative(root, file);
    const contentType = contentTypes[path.extname(file)];
    if (!contentType || relative.startsWith('..' + path.sep) || path.isAbsolute(relative)) {
        response.writeHead(404).end();
        return;
    }

    try {
        const body = await readFile(file);
        response.writeHead(200, {
            'Content-Type': contentType,
            'Content-Length': body.length,
            'Cache-Control': 'no-store',
        });
        response.end(request.method === 'HEAD' ? undefined : body);
    } catch (error) {
        const missing = ['ENOENT', 'ENOTDIR', 'EISDIR'].includes(error.code);
        response.writeHead(missing ? 404 : 500).end();
    }
});

server.on('error', (error) => {
    console.error(`Could not start SmartCoffee at ${url} (${error.code}).`);
    process.exitCode = 1;
});
server.listen(port, host, () => {
    console.log(`SmartCoffee POS: ${url} (Ctrl+C to stop)`);
    if (!process.argv.includes('--no-open')) {
        execFile('explorer.exe', [url + '/phuc-vu/'], () => {});
    }
});
