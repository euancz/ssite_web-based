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
    const discover = fs.readFileSync('resources/views/pages/discover.blade.php','utf8');
    const inlineCss = discover.match(/<style>([\s\S]*?)<\/style>/)[1];
    const cssFiles=['app','main-site','ui-foundation','ssite-upgrade','mobile-responsive','workspace-experience','main-site-dropdowns','interface-polish','content-cards'];
    const css=inlineCss+cssFiles.map(name=>fs.readFileSync('public/css/'+name+'.css','utf8')).join(String.fromCharCode(10));
    const titles=['Meet the Student Tech Community and Discover What Comes Next','Staying Safe Online','Building Your First Portfolio'];
    const cards=titles.map((title,index)=>'<article class="discover-story-card"><a class="discover-card-link" href="#story-'+index+'"><img src="data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22320%22 height=%22220%22%3E%3Crect width=%22320%22 height=%22220%22 fill=%22%23d9f3fa%22/%3E%3C/svg%3E" alt=""><div class="discover-story-body"><div class="discover-story-meta"><span>Articles</span></div><h3>'+title+'</h3><p class="discover-card-summary">'+(index===1?'A short description.':'Fictional sample data for demonstrating the SSITE system. A longer description with enough text to wrap onto several lines.')+'</p><div class="discover-story-author">By '+(index===0?'Alex Rivera and the Student Technology Team':'Nicole Santos')+' · November 10, 2026</div><span class="discover-read-story"><span>Read more</span><svg class="ssite-icon card-read-more-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></span></div></a></article>').join('');
    await evaluate(`document.head.innerHTML='<style></style>';document.querySelector('style').textContent=${JSON.stringify(css)};document.body.className='site-body main-site-body';document.body.innerHTML='<main><div class="site-page"><div class="discover-grid">'+${JSON.stringify(cards)}+'</div></div></main>'`);
    const metrics=await evaluate(`Array.from(document.querySelectorAll('.discover-story-card')).map(card=>{const a=card.querySelector('.discover-read-story').getBoundingClientRect();const author=card.querySelector('.discover-story-author').getBoundingClientRect();return {top:a.top,height:a.height,bottomGap:card.getBoundingClientRect().bottom-a.bottom,overlap:a.top<author.bottom}})`);
    assert.ok(Math.max(...metrics.map(m=>m.top))-Math.min(...metrics.map(m=>m.top))<1,JSON.stringify(metrics));
    for(const metric of metrics){assert.ok(metric.height>=44);assert.ok(metric.bottomGap>=19&&metric.bottomGap<=22,JSON.stringify(metric));assert.equal(metric.overlap,false);}
    console.log('PASS: varied titles, summaries and bylines retain one bottom-aligned action row without overlap');
    const target=await evaluate(`(()=>{const rect=document.querySelector('.discover-read-story').getBoundingClientRect();return {x:rect.x+rect.width/2,y:rect.y+rect.height/2}})()`);
    await send('Input.dispatchMouseEvent',{type:'mouseMoved',...target},sessionId);
    await evaluate('new Promise(resolve=>setTimeout(resolve,250))');
    assert.equal(await evaluate(`getComputedStyle(document.querySelector('.discover-read-story')).backgroundColor`),'rgb(217, 243, 250)');
    assert.equal(await evaluate(`getComputedStyle(document.querySelector('.card-read-more-icon')).backgroundColor`),'rgb(21, 85, 113)');
    const hoveredTops = await evaluate(`Array.from(document.querySelectorAll('.discover-read-story')).map(el=>el.getBoundingClientRect().top)`);
    assert.ok(Math.max(...hoveredTops)-Math.min(...hoveredTops)<1,JSON.stringify(hoveredTops));
    console.log('PASS: hover highlights the action and its SVG icon without shifting the aligned row');
    if(process.argv.includes('--screenshot')){
        const shot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:true},sessionId);
        fs.writeFileSync('storage/app/discover-cards-preview.png',Buffer.from(shot.data,'base64'));
    }
    await send('Emulation.setDeviceMetricsOverride',{width:360,height:844,deviceScaleFactor:1,mobile:false},sessionId);
    const mobile=await evaluate(`({overflow:document.documentElement.scrollWidth>innerWidth,actions:[...document.querySelectorAll('.discover-read-story')].map(el=>({height:el.getBoundingClientRect().height,right:el.getBoundingClientRect().right}))})`);
    assert.equal(mobile.overflow,false,JSON.stringify(mobile));
    for(const action of mobile.actions){assert.ok(action.height>=44);assert.ok(action.right<=360);}
    await send('Emulation.setEmulatedMedia',{features:[{name:'prefers-reduced-motion',value:'reduce'}]},sessionId);
    assert.ok(parseFloat(await evaluate(`getComputedStyle(document.querySelector('.discover-read-story')).transitionDuration`)) <= 0.001);
    console.log('PASS: mobile actions fit, retain touch targets and respect reduced motion');

    await send('Emulation.setEmulatedMedia', { features: [] }, sessionId);
    await send('Emulation.setDeviceMetricsOverride', { width: 1100, height: 700, deviceScaleFactor: 1, mobile: false }, sessionId);
    const homeCss = fs.readFileSync('public/css/home.css', 'utf8') + '\n' + css;
    const newsCards = [
        ['Activities', 'Club meeting', 'By Febbie Ann Escoto'],
        ['Achievements', 'Campus Hackathon Champions and Student Technology Award Recipients', 'By Alex Rivera · October 30, 2026'],
    ].map(([category, title, byline]) => `
        <article class="news-card card-hover">
            <div class="news-card-image"></div>
            <div class="news-card-content">
                <p class="article-category">${category}</p>
                <h3><a href="#story">${title}</a></h3>
                <p class="article-byline">${byline}</p>
                <a class="read-more" href="#story">Read more</a>
            </div>
        </article>`).join('');
    const home = `<header class="public-home-header"></header><main><div class="home-page site-page"><div class="news-grid">${newsCards}</div></div></main>`;
    await evaluate(`document.querySelector('style').textContent=${JSON.stringify(homeCss)};document.body.innerHTML=${JSON.stringify(home)}`);
    const homeActionRects = `[...document.querySelectorAll('.news-card .read-more')].map(link => {
        const { top, height } = link.getBoundingClientRect();
        return { top, height };
    })`;
    const assertHomeAlignment = async () => {
        const [first, second] = await evaluate(homeActionRects);
        assert.deepEqual(first, second);
        assert.ok(first.height >= 44);
    };
    await assertHomeAlignment();
    await send('DOM.enable', {}, sessionId);
    await send('CSS.enable', {}, sessionId);
    const { root } = await send('DOM.getDocument', {}, sessionId);
    const { nodeId } = await send('DOM.querySelector', { nodeId: root.nodeId, selector: '.news-card' }, sessionId);
    await send('CSS.forcePseudoState', { nodeId, forcedPseudoClasses: ['hover'] }, sessionId);
    await evaluate('new Promise(resolve => setTimeout(resolve, 250))');
    await assertHomeAlignment();
    await send('CSS.forcePseudoState', { nodeId, forcedPseudoClasses: [] }, sessionId);
    await evaluate(`document.querySelector('.news-card .read-more').focus(); new Promise(resolve => setTimeout(resolve, 250))`);
    await assertHomeAlignment();
    console.log('PASS: Home actions align with different title lengths, including on hover and focus');
})().catch((error) => { console.error(error); process.exitCode = 1; }).finally(async () => {
    clearTimeout(timeout);
    try { await send('Browser.close'); } catch {}
    browser.kill();
});
