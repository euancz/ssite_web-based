const { spawn } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const assert = require('node:assert/strict');

// Uses an installed browser and an isolated temporary profile; no app database is accessed.
const executable = process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const profile = fs.mkdtempSync(path.join(os.tmpdir(), 'ssite-dropdown-'));
const browser = spawn(executable, ['--headless=new', '--remote-debugging-pipe', '--no-first-run', '--disable-gpu', `--user-data-dir=${profile}`], {
    stdio: ['ignore', 'ignore', 'ignore', 'pipe', 'pipe'], windowsHide: true,
});
let sequence = 0;
let buffer = '';
const pending = new Map();
browser.stdio[4].on('data', (chunk) => {
    buffer += chunk.toString();
    let end;
    while ((end = buffer.indexOf('\0')) >= 0) {
        const message = JSON.parse(buffer.slice(0, end));
        buffer = buffer.slice(end + 1);
        const request = pending.get(message.id);
        if (request) {
            pending.delete(message.id);
            message.error ? request.reject(new Error(JSON.stringify(message.error))) : request.resolve(message.result);
        }
    }
});
function send(method, params = {}, sessionId) {
    if (method === 'Input.dispatchKeyEvent') {
        const keyCode = { Escape: 27, ArrowDown: 40, Enter: 13 }[params.key];
        params = { ...params, windowsVirtualKeyCode: keyCode, nativeVirtualKeyCode: keyCode };
    }
    const id = ++sequence;
    return new Promise((resolve, reject) => {
        pending.set(id, { resolve, reject });
        browser.stdio[3].write(JSON.stringify({ id, method, params, ...(sessionId ? { sessionId } : {}) }) + '\0');
    });
}
const timeout = setTimeout(() => { browser.kill(); throw new Error('Browser check timed out'); }, 30000);

(async () => {
    const { targetId } = await send('Target.createTarget', { url: 'about:blank' });
    const { sessionId } = await send('Target.attachToTarget', { targetId, flatten: true });
    const evaluate = async (expression, userGesture = false) => {
        const result = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true, userGesture }, sessionId);
        if (result.exceptionDetails) throw new Error(result.exceptionDetails.text + ': ' + result.exceptionDetails.exception?.description);
        return result.result.value;
    };
    await send('Emulation.setDeviceMetricsOverride', { width: 1100, height: 700, deviceScaleFactor: 1, mobile: false }, sessionId);
    const { execFileSync } = require('node:child_process');
    const render = (page,total) => execFileSync('php',['-r', String.raw`require 'vendor/autoload.php'; $app = require 'bootstrap/app.php'; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); $p = new Illuminate\Pagination\LengthAwarePaginator(range(1,5), ${total},5,${page},['path'=>'/documents']); echo $p->onEachSide(1)->links('vendor.pagination.ssite')->toHtml();`.replaceAll('\\','\\')],{encoding:'utf8',windowsHide:true});
    const longPagination = render(25,250);
    const secondPagination = render(2,12);
    const cssFiles=['app','post-feed','main-site','ui-foundation','ssite-upgrade','mobile-responsive','workspace-experience','interface-polish','pagination'];
    const css=cssFiles.map(name=>fs.readFileSync('public/css/'+name+'.css','utf8')).join('\n');
    await evaluate(`document.head.innerHTML='<style></style>';document.querySelector('style').textContent=${JSON.stringify(css)};document.body.className='site-body main-site-body';document.body.innerHTML='<main style="padding:20px"><div class="dashboard-pagination">'+${JSON.stringify(longPagination)}+'</div></main>'`);
    const desktop = await evaluate(`({overflow:document.documentElement.scrollWidth>innerWidth,current:document.querySelector('[aria-current="page"]').textContent.trim(),buttons:[...document.querySelectorAll('.ssite-page-button')].map(el=>el.getBoundingClientRect().height),compact:getComputedStyle(document.querySelector('.ssite-pagination-position')).display})`);
    assert.equal(desktop.overflow,false);
    assert.equal(desktop.current,'25');
    assert.equal(desktop.compact,'none');
    for(const height of desktop.buttons)assert.ok(height>=44);
    assert.ok(await evaluate(`document.querySelector('a[aria-label="Go to page 1"]')!==null && document.querySelector('a[aria-label="Go to page 50"]')!==null`));
    if (process.argv.includes('--screenshot')) {
        const screenshot = await send('Page.captureScreenshot', { format: 'png', clip: { x: 0, y: 0, width: 1100, height: 220, scale: 1 } }, sessionId);
        fs.writeFileSync('storage/app/pagination-preview.png', Buffer.from(screenshot.data, 'base64'));
    }
    console.log('PASS: real rendered pagination includes first/last/current pages, gaps and 44px targets');
    for(const width of [640,390,320]){
        await send('Emulation.setDeviceMetricsOverride',{width,height:844,deviceScaleFactor:1,mobile:false},sessionId);
        const mobile=await evaluate(`({overflow:document.documentElement.scrollWidth>innerWidth,number:getComputedStyle(document.querySelector('.ssite-page-number')).display,position:getComputedStyle(document.querySelector('.ssite-pagination-position')).display,prev:document.querySelector('[rel="prev"]').getBoundingClientRect().right,next:document.querySelector('[rel="next"]').getBoundingClientRect().right})`);
        assert.equal(mobile.overflow,false,JSON.stringify(mobile));assert.equal(mobile.number,'none');assert.notEqual(mobile.position,'none');assert.ok(mobile.next<=width);assert.ok(mobile.prev<=width);
    }
    console.log('PASS: compact mobile navigation fits down to 320px without wrapped page-number rows');
    const source=fs.readFileSync('resources/views/components/posts/sort-row.blade.php','utf8').match(/<script>([\s\S]*?)<\/script>/)[1];
    const response='<div class="post-feed-shell"><form class="post-feed-sort"></form><div data-feed-results><div class="post-card">Second page</div><div class="dashboard-pagination">'+secondPagination+'</div></div></div>';
    await evaluate(`document.body.innerHTML='<main><div class="post-feed-shell"><form class="post-feed-sort"></form><p data-feed-status aria-live="polite"></p><p data-feed-error hidden></p><div data-feed-results><div class="dashboard-pagination ssite-pagination"><a id="pageTwo" href="about:blank?page=2&q=workshop&sort=title">2</a></div></div></div></main>';window.requested=[];window.fetch=async url=>{requested.push(String(url));return {ok:true,text:async()=>${JSON.stringify(response)}}};window.history.pushState=()=>{};(0,eval)(${JSON.stringify(source)});pageTwo.click();`);
    await evaluate('new Promise(resolve=>setTimeout(resolve,100))');
    assert.ok((await evaluate('requested[0]')).includes('q=workshop'));
    assert.equal(await evaluate(`document.activeElement.matches('[data-feed-results]')`),true);
    assert.ok((await evaluate(`document.querySelector('[data-feed-status]').textContent`)).includes('Page 2 of 3'));
    assert.equal(await evaluate(`document.querySelector('[data-feed-error]').hidden`),true);
    console.log('PASS: asynchronous page changes retain query parameters, focus new results and announce the current page');
})().catch((error) => { console.error(error); process.exitCode = 1; }).finally(async () => {
    clearTimeout(timeout);
    try { await send('Browser.close'); } catch {}
    browser.kill();
});
