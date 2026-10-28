// Browser QA dùng Chrome đã cài, không cần cài thêm thư viện.
// Được tests/stock-transfer.php --browser gọi với database test đang hoạt động.
import { spawn } from 'node:child_process';
import { mkdtemp, readFile, rm, writeFile, mkdir } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const base = process.argv[2];
if (!base?.startsWith('http://127.0.0.1:')) throw new Error('Expected local test server URL');
const executable = process.env.CHROME_PATH || 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const profile = await mkdtemp(join(tmpdir(), 'ptud-st-chrome-'));
const chrome = spawn(executable, ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check',
    '--no-sandbox', '--remote-debugging-port=0', `--user-data-dir=${profile}`, 'about:blank'], { windowsHide: true, stdio: 'ignore' });
let ws;
let chromeError;
chrome.on('error', (error) => { chromeError = error; });
const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
async function waitFor(check, label) {
    const deadline = Date.now() + 15000;
    while (Date.now() < deadline) {
        if (chromeError) throw chromeError;
        try { if (await check()) return; } catch (error) { if (error.message?.includes('FAIL:')) throw error; }
        await delay(100);
    }
    throw new Error(`Timed out: ${label}`);
}
let count = 0;
function check(condition, label) {
    if (!condition) throw new Error(`FAIL: ${label}`);
    count++;
    console.log(`BROWSER PASS: ${label}`);
}
try {
    let port;
    await waitFor(async () => {
        port = Number((await readFile(join(profile, 'DevToolsActivePort'), 'utf8')).split('\n')[0]);
        return port > 0;
    }, 'Chrome debugging port');
    let targets;
    await waitFor(async () => {
        targets = await (await fetch(`http://127.0.0.1:${port}/json/list`)).json();
        return targets.some((target) => target.type === 'page');
    }, 'Chrome page target');
    const socketUrl = targets.find((target) => target.type === 'page').webSocketDebuggerUrl.replace('localhost', '127.0.0.1');
    ws = new WebSocket(socketUrl);
    await new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error('Chrome WebSocket handshake timeout')), 10000);
        ws.onopen = () => { clearTimeout(timer); resolve(); };
        ws.onerror = (event) => { clearTimeout(timer); reject(new Error('Chrome WebSocket: ' + (event.message || 'connection failed'))); };
    });
    let sequence = 0;
    const pending = new Map();
    const errors = [];
    let dialogAccept = true;
    let dialogs = 0;
    function send(method, params = {}) {
        return new Promise((resolve, reject) => {
            const id = ++sequence;
            const timer = setTimeout(() => { pending.delete(id); reject(new Error(`CDP timeout: ${method}`)); }, 15000);
            pending.set(id, { resolve, reject, timer });
            ws.send(JSON.stringify({ id, method, params }));
        });
    }
    ws.onmessage = ({ data }) => {
        const message = JSON.parse(data);
        if (message.id && pending.has(message.id)) {
            const item = pending.get(message.id);
            pending.delete(message.id);
            clearTimeout(item.timer);
            if (message.error) item.reject(new Error(message.error.message));
            else item.resolve(message.result);
        }
        if (message.method === 'Runtime.exceptionThrown') errors.push(message.params.exceptionDetails.text);
        if (message.method === 'Page.javascriptDialogOpening') {
            dialogs++;
            send('Page.handleJavaScriptDialog', { accept: dialogAccept }).catch(() => {});
        }
    };
    async function evaluate(expression) {
        const result = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
        if (result.exceptionDetails) throw new Error(result.exceptionDetails.text);
        return result.result.value;
    }
    async function navigate(url) {
        await send('Page.navigate', { url });
        await waitFor(() => evaluate('document.readyState === "complete" && Boolean(document.querySelector(".st-page"))'), 'page loaded');
    }
    await send('Page.enable');
    await send('Runtime.enable');
    await send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 1100, deviceScaleFactor: 1, mobile: false });
    await navigate(base + '&test_actor=1');
    check(await evaluate('Boolean(document.querySelector("#st-form"))'), 'open form');
    await evaluate(`(() => {
        const set = (selector, value) => { const element = document.querySelector(selector); element.value = value; element.dispatchEvent(new Event('input', { bubbles: true })); };
        set('#st-destination', '2'); set('#st-note', 'Phiếu chuyển kho thử');
        set('#st-product-0', '1'); set('#st-quantity-0', '3'); set('#st-price-0', '12.34');
        document.querySelector('#st-add').click();
        set('#st-product-1', '2'); set('#st-quantity-1', '2'); set('#st-price-1', '0.10');
    })()`);
    check(await evaluate('document.querySelector("#st-total").textContent === "37,22 đ"'), 'two lines calculate total');
    check(await evaluate('document.querySelector("#st-count").textContent === "2 mặt hàng"'), 'line count');
    await evaluate(`(() => { const field = document.querySelector('#st-product-1'); field.value = '1'; field.dispatchEvent(new Event('change', { bubbles: true })); })()`);
    check(await evaluate('!document.querySelector("#st-product-1").checkValidity()'), 'duplicate product blocked in browser');
    await evaluate(`(() => { const field = document.querySelector('#st-product-1'); field.value = '2'; field.dispatchEvent(new Event('change', { bubbles: true })); const qty = document.querySelector('#st-quantity-0'); qty.value = '101'; qty.dispatchEvent(new Event('input', { bubbles: true })); })()`);
    check(await evaluate('!document.querySelector("#st-quantity-0").checkValidity()'), 'excess quantity blocked in browser');
    await evaluate(`(() => { const field = document.querySelector('#st-quantity-0'); field.value = '3'; field.dispatchEvent(new Event('input', { bubbles: true })); })()`);
    check(await evaluate('document.querySelector("#st-form").checkValidity()'), 'corrected form becomes valid');
    dialogAccept = false;
    await evaluate('document.querySelector("#st-exit").click()');
    check(dialogs === 1 && await evaluate('Boolean(document.querySelector("#st-form"))'), 'cancel exit preserves form');
    check(await evaluate('document.documentElement.scrollWidth <= window.innerWidth'), 'desktop has no page overflow');
    if (process.env.PTUD_ST_SCREENSHOT_DIR) {
        await mkdir(process.env.PTUD_ST_SCREENSHOT_DIR, { recursive: true });
        const shot = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true });
        await writeFile(join(process.env.PTUD_ST_SCREENSHOT_DIR, 'stock-transfer-desktop.png'), Buffer.from(shot.data, 'base64'));
    }
    await send('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true });
    await waitFor(() => evaluate('window.innerWidth === 390'), 'mobile viewport resized');
    check(await evaluate('document.documentElement.scrollWidth <= 390'), 'mobile has no page overflow');
    check(await evaluate('document.querySelector(".table-wrap").scrollWidth > document.querySelector(".table-wrap").clientWidth'), 'mobile table scrolls inside panel');
    await send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 1100, deviceScaleFactor: 1, mobile: false });
    dialogAccept = true;
    await evaluate('document.querySelector("#st-save").click()');
    await waitFor(() => evaluate('document.body.textContent.includes("Lưu nháp thành công")'), 'saved detail');
    check(await evaluate('document.body.textContent.includes("37,22") && !document.querySelector("#st-form")'), 'browser saves draft and opens detail');
    check(errors.length === 0, 'no JavaScript runtime errors');
    await navigate(base + '&test_actor=1');
    await evaluate('document.querySelector(".st-remove").click(); document.querySelector("#st-destination").value = "2"; document.querySelector("#st-save").click()');
    check(await evaluate('!document.querySelector("#st-client-error").hidden'), 'empty lines show an error');
    console.log(`${count} browser assertions passed.`);
} finally {
    if (ws) ws.close();
    chrome.kill();
    await new Promise((resolve) => { if (chrome.exitCode !== null) resolve(); else { chrome.once('exit', resolve); setTimeout(resolve, 3000); } });
    // Chỉ xóa profile do mkdtemp của lần chạy này tạo ra.
    if (profile.startsWith(join(tmpdir(), 'ptud-st-chrome-'))) await rm(profile, { recursive: true, force: true, maxRetries: 5, retryDelay: 300 });
}
