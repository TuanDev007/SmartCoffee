const { createServer } = require('node:http');
const { readFile } = require('node:fs/promises');
const { execFile } = require('node:child_process');
const path = require('node:path');

const { existsSync } = require('node:fs');

const introView = path.join(__dirname, 'old-version', 'app', 'Views', 'intro');
const introAssets = path.join(__dirname, 'old-version', 'public', 'assets', 'intro');
const checkout = path.resolve(__dirname, '..', 'coffee-SC-5-service-checkout', 'frontend');
const checkoutUrl = '/phuc-vu/';
const host = '127.0.0.1';
const port = 4178;
const contentTypes = {
    '.html': 'text/html; charset=utf-8',
    '.css': 'text/css; charset=utf-8',
    '.js': 'text/javascript; charset=utf-8',
    '.json': 'application/json; charset=utf-8',
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.jpeg': 'image/jpeg',
    '.webp': 'image/webp',
    '.svg': 'image/svg+xml',
    '.woff2': 'font/woff2',
    '.woff': 'font/woff',
    '.ttf': 'font/ttf',
};

if (!existsSync(path.join(checkout, 'phuc-vu', 'index.html'))) {
    console.error(`Checkout frontend was not found in ${checkout}`);
    process.exit(1);
}

const server = createServer(async (request, response) => {
    if (request.method !== 'GET' && request.method !== 'HEAD') {
        response.writeHead(405, { Allow: 'GET, HEAD' }).end();
        return;
    }

    let pathname;
    try {
        pathname = decodeURIComponent(new URL(request.url, `http://${host}`).pathname);
    } catch {
        response.writeHead(400).end();
        return;
    }

    let root;
    if (pathname === '/' || pathname === '/index.html') {
        root = introView;
        pathname = '/index.html';
    } else if (pathname.startsWith('/assets/')) {
        root = introAssets;
        pathname = pathname.slice('/assets'.length);
    } else if (/^\/[\w-]+\.(jpg|png)$/.test(pathname)) {
        root = introAssets;
        pathname = '/images' + pathname;
    } else if (pathname.startsWith(checkoutUrl) || pathname.startsWith('/shared/')) {
        root = checkout;
        if (pathname === checkoutUrl) pathname += 'index.html';
    } else {
        response.writeHead(404).end();
        return;
    }

    const file = path.resolve(root, '.' + pathname);
    const relative = path.relative(root, file);
    const contentType = contentTypes[path.extname(file)];
    if (!contentType || relative.startsWith('..' + path.sep) || path.isAbsolute(relative)) {
        response.writeHead(404).end();
        return;
    }

    try {
        let body = await readFile(file);
        if (root === introView) {
            body = Buffer.from(body.toString('utf8')
                .replace('</head>', `<style>
    #btn-cart-toggle { display: none; }
    #loader-overlay .loader-content { width: 92%; max-width: 760px; }
    #loader-overlay .loader-title {
        font-size: clamp(1.5rem, 7vw, 3.4rem);
        letter-spacing: 0.13em;
        white-space: nowrap;
    }
    #loader-overlay .loader-bar-outer { max-width: 500px; margin-inline: auto; }
</style>
</head>`)
                .replace('data-home-url="../../../brunivo/index.html"', `data-home-url="${checkoutUrl}"`)
                .replace('Vào trang chủ SmartCoffee →', 'Vào giao diện Phục vụ →'));
        } else if (root === introAssets && pathname === '/index-Dq-uEGHq.js') {
            // Raise the final brew-studio camera and keep it aimed at the cup.
            body = Buffer.from(body.toString('utf8').replace(
                'x:.8,y:.76,z:-2.9,lx:.9,ly:.18,lz:-4.5,fov:46',
                'x:.8,y:1.08,z:-2.9,lx:.9,ly:.22,lz:-4.5,fov:46',
            ));
        }
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
    if (error.code === 'EADDRINUSE') {
        server.listen(0, host);
        return;
    }
    console.error(`Could not start SmartCoffee (${error.code}).`);
    process.exitCode = 1;
});
server.once('listening', () => {
    const url = `http://${host}:${server.address().port}/`;
    console.log(`SmartCoffee Intro: ${url} (Ctrl+C to stop)`);
    if (!process.argv.includes('--no-open')) {
        execFile('explorer.exe', [url], () => {});
    }
});
server.listen(port, host);
