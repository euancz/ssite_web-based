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
    const css = fs.readFileSync('public/css/main-site-dropdowns.css', 'utf8');
    const script = fs.readFileSync('public/js/dropdown-position.js', 'utf8');
    await evaluate(`document.head.innerHTML = '<style>body{margin:0}main{padding:20px;height:1800px}select{display:block;margin-top:430px;width:240px!important}</style>';
        const style=document.createElement('style'); style.textContent=${JSON.stringify(css)}; document.head.append(style);
        document.body.className='site-body main-site-body';
        document.body.innerHTML='<main><label for="choice">Content type</label><select id="choice" name="type">'+Array.from({length:20},(_,i)=>'<option value="'+i+'">Choice '+i+'</option>').join('')+'</select></main>';
        (0,eval)(${JSON.stringify(script)});`);
    assert.equal(await evaluate(`CSS.supports('appearance','base-select')`), true);

    async function openAndMeasure() {
        await evaluate(`choice.focus(); choice.showPicker();`, true);
        return evaluate(`new Promise(resolve=>requestAnimationFrame(()=>requestAnimationFrame(()=>{
            const field=choice.getBoundingClientRect();const option=choice.options[0].getBoundingClientRect();
            const picker=getComputedStyle(choice,'::picker(select)');
            resolve({open:choice.matches(':open'),bottom:field.bottom,top:parseFloat(picker.top),height:parseFloat(picker.height),optionTop:option.top,viewport:innerHeight});
        })));`);
    }
    let metrics = await openAndMeasure();
    assert.equal(metrics.open, true);
    assert.ok(metrics.top >= metrics.bottom, JSON.stringify(metrics));
    assert.ok(metrics.top + metrics.height <= metrics.viewport, JSON.stringify(metrics));
    console.log('PASS: picker opens below a field near the viewport bottom and fits available space');

    await evaluate('scrollTo(0,180)');
    await evaluate('new Promise(resolve=>setTimeout(resolve,80))');
    metrics = await evaluate(`({bottom:choice.getBoundingClientRect().bottom,top:parseFloat(getComputedStyle(choice,'::picker(select)').top),open:choice.matches(':open')})`);
    assert.ok(!metrics.open || metrics.top >= metrics.bottom, JSON.stringify(metrics));
    console.log('PASS: page scrolling never flips the picker above the control');

    await send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Escape', code: 'Escape' }, sessionId);
    await send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Escape', code: 'Escape' }, sessionId);
    await evaluate(`window.changes=0;choice.addEventListener('change',()=>changes++);choice.focus();choice.showPicker()`, true);
    await send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'ArrowDown', code: 'ArrowDown' }, sessionId);
    await send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'ArrowDown', code: 'ArrowDown' }, sessionId);
    await send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Enter', code: 'Enter' }, sessionId);
    await send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Enter', code: 'Enter' }, sessionId);
    assert.equal(await evaluate('choice.value'), '1');
    assert.equal(await evaluate('changes'), 1);
    console.log('PASS: keyboard selection still emits the native change event');

    // Replacing feed markup must not require rebinding the positioning handlers.
    await evaluate(`choice.outerHTML=choice.outerHTML;choice.value='19';`);
    metrics = await openAndMeasure();
    assert.ok(metrics.top >= metrics.bottom, JSON.stringify(metrics));
    assert.ok(metrics.top + metrics.height <= metrics.viewport, JSON.stringify(metrics));
    console.log('PASS: replaced dropdowns and a selected last option still open below');

    await send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Escape', code: 'Escape' }, sessionId);
    await send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Escape', code: 'Escape' }, sessionId);
    await evaluate(`document.body.className='site-body workspace-site-body';
        document.body.innerHTML='<div class="workspace-content"><dialog id="settings"><label for="choice">Role</label><select id="choice" style="margin:0;width:240px"><option>Student</option><option>Officer</option></select></dialog></div>';
        settings.showModal();`);
    metrics = await openAndMeasure();
    assert.ok(metrics.top >= metrics.bottom, JSON.stringify(metrics));
    console.log('PASS: workspace dialog dropdowns open below their controls');
})().catch((error) => { console.error(error); process.exitCode = 1; }).finally(async () => {
    clearTimeout(timeout);
    try { await send('Browser.close'); } catch {}
    browser.kill();
});
