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
    const cssFiles = ['app','post-feed','documents-gallery','site-navigation','main-site','ui-foundation','ssite-upgrade','mobile-responsive','workspace-experience','main-site-dropdowns','interface-polish'];
    const fullCss = cssFiles.map(name => fs.readFileSync('public/css/' + name + '.css','utf8')).join('\n');
    const controlsScript = fs.readFileSync('public/js/search-controls.js','utf8');
    const popoverScript = fs.readFileSync('public/js/search-popover.js', 'utf8');
    const suggestionsScript = fs.readFileSync('public/js/list-search-suggestions.js', 'utf8');
    await evaluate(`document.head.innerHTML='<style></style>';document.querySelector('style').textContent=${JSON.stringify(fullCss)};
        document.body.className='site-body main-site-body';document.body.innerHTML='<header><form class="search-form" action="/search"><input class="site-search-input" name="q" value="global" role="combobox" aria-expanded="false"><button class="search-submit-icon" type="submit"><svg class="site-search-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.34-4.34"></path></svg></button></form></header><main><div class="site-page"><form id="filters" class="discover-filters" action="/discover"><div class="discover-field"><label for="keyword">Search stories</label><div class="main-search-field"><input id="keyword" name="q" type="search" value="workshop"><button class="search-submit-icon" type="submit"><svg class="site-search-icon" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.34-4.34"></path></svg></button></div></div><div class="discover-field"><label for="kind">Content type</label><select id="kind" name="type"><option value="all">All content</option><option value="articles" selected>Articles</option></select></div><div class="discover-field"><label for="period">Date added</label><select id="period" name="period"><option value="all">Any time</option><option value="week" selected>Last 7 days</option></select></div><div class="discover-field"><label for="sort">Sort by</label><select id="sort" name="sort"><option value="recent">Newest added</option><option value="oldest">Oldest added</option></select></div><a class="site-filter-reset" href="/discover">Clear filters</a></form></div></main>';
        window.submissions=[];document.addEventListener('submit',event=>{event.preventDefault();submissions.push(Object.fromEntries(new FormData(event.target)))});(0,eval)(${JSON.stringify(controlsScript)});`);
    const checkClearIcon = async (selector = '.search-form,.main-search-field') => {
        const metrics = await evaluate(`[...document.querySelectorAll(${JSON.stringify(selector)})].map(field=>{const clear=field.querySelector('.search-clear-button');return {clearPath:clear.querySelector('svg').children.length,iconSize:clear.querySelector('svg').getBoundingClientRect().width,clearTarget:clear.getBoundingClientRect().height,searchDisplay:getComputedStyle(field.querySelector('[type="submit"]')).display}})`);
        for (const metric of metrics) assert.deepEqual(metric, {clearPath:1,iconSize:20,clearTarget:44,searchDisplay:'none'});
    };
    await checkClearIcon();
    console.log('PASS: filled search fields show only a plain X, with a 44px touch target');
    assert.equal(await evaluate(`filters.querySelectorAll('.site-filter-chip').length`),3);
    assert.equal(await evaluate(`filters.querySelector('.site-filter-summary .site-filter-reset').getAttribute('aria-label')`),'Clear all filters');
    assert.equal(await evaluate(`filters.querySelector('.site-filter-chip').getAttribute('aria-label')`),'Remove Search: workshop');
    await evaluate(`filters.querySelector('[aria-label="Remove Date added: Last 7 days"]').click()`);
    assert.deepEqual(await evaluate('submissions[0]'),{q:'workshop',type:'articles',period:'all',sort:'recent'});
    assert.equal(await evaluate('document.activeElement.id'),'period');
    console.log('PASS: removable filter chips preserve unrelated filters and retain keyboard focus');
    await evaluate(`keyword.dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true,cancelable:true}))`);
    assert.deepEqual(await evaluate('submissions[1]'),{q:'',type:'articles',period:'all',sort:'recent'});
    assert.equal(await evaluate(`document.querySelector('header input').value`),'global');
    assert.equal(await evaluate(`keyword.nextElementSibling.dataset.searchClear`),'');
    assert.equal(await evaluate(`getComputedStyle(keyword.nextElementSibling).display`),'none');
    console.log('PASS: Escape clears only the scoped keyword; clear is next in the keyboard order and hides when empty');
    await evaluate(`window.headerInput=document.querySelector('header input');headerInput.setAttribute('aria-expanded','true');headerInput.dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true,cancelable:true}))`);
    assert.equal(await evaluate('headerInput.value'),'global');
    await evaluate(`headerInput.setAttribute('aria-expanded','false');headerInput.dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true,cancelable:true}))`);
    assert.equal(await evaluate('headerInput.value'),'');
    assert.equal(await evaluate('submissions.length'),2);
    console.log('PASS: header suggestions get first Escape; clearing header text does not submit a page search');
    await evaluate(`filters.append(filters.querySelector('.site-filter-reset'));filters.querySelector('[data-active-filters]').remove();filters.querySelector('[data-search-clear]').remove();filters.innerHTML=filters.innerHTML;filters.elements.q.value='new keyword';filters.elements.period.value='week';`);
    await evaluate('new Promise(resolve=>requestAnimationFrame(resolve))');
    assert.equal(await evaluate(`filters.querySelectorAll('[data-active-filters]').length`),1);
    assert.equal(await evaluate(`filters.querySelectorAll('[data-search-clear]').length`),1);
    assert.equal(await evaluate(`filters.querySelectorAll('.site-filter-chip').length`),3);
    console.log('PASS: refreshed filter markup recreates working controls without duplicates');
    await send('Emulation.setDeviceMetricsOverride',{width:390,height:844,deviceScaleFactor:1,mobile:false},sessionId);
    await evaluate(`headerInput.value='global';headerInput.dispatchEvent(new Event('input',{bubbles:true}));`);
    await checkClearIcon();
    const mobile = await evaluate(`({overflow:document.documentElement.scrollWidth>innerWidth, controls:[...filters.querySelectorAll('.site-filter-chip,.search-clear-button')].filter(el=>!el.hidden).map(el=>({height:el.getBoundingClientRect().height,right:el.getBoundingClientRect().right})),reset:filters.querySelector('.site-filter-reset').getBoundingClientRect().right})`);
    assert.equal(mobile.overflow,false,JSON.stringify(mobile));
    for(const c of mobile.controls){assert.ok(c.height>=44,JSON.stringify(c));assert.ok(c.right<=390,JSON.stringify(c));}
    assert.ok(mobile.reset<=390,JSON.stringify(mobile));
    console.log('PASS: mobile chips, reset and clear controls fit and retain 44px touch targets');
    await evaluate(`filters.action='about:blank';window.suggestionQuery=null;window.fetch=async url=>{
        suggestionQuery=Object.fromEntries(url.searchParams);
        return {ok:true,json:async()=>({results:['Workshop kickoff']})};
    };(0,eval)(${JSON.stringify(popoverScript)});(0,eval)(${JSON.stringify(suggestionsScript)});
    keyword.focus();keyword.value='wORK';keyword.dispatchEvent(new Event('input',{bubbles:true}));`);
    assert.deepEqual(await evaluate(`new Promise(resolve=>setTimeout(()=>resolve(suggestionQuery),320))`),
        {q:'wORK',type:'articles',period:'week',sort:'recent',_suggest:'1'});
    assert.equal(await evaluate(`keyword.getAttribute('aria-expanded')`),'true');
    await evaluate(`keyword.dispatchEvent(new KeyboardEvent('keydown',{key:'ArrowDown',bubbles:true,cancelable:true}));
        keyword.dispatchEvent(new KeyboardEvent('keydown',{key:'Enter',bubbles:true,cancelable:true}));`);
    assert.deepEqual(await evaluate('submissions.at(-1)'),{q:'Workshop kickoff',type:'articles',period:'week',sort:'recent'});
    assert.equal(await evaluate(`keyword.getAttribute('aria-expanded')`),'false');
    console.log('PASS: scoped suggestions preserve filters and can be submitted from the keyboard');
    await send('Emulation.setDeviceMetricsOverride',{width:1100,height:700,deviceScaleFactor:1,mobile:false},sessionId);
    await evaluate(`document.body.innerHTML='<main><div class="documents-page site-page"><form id="documentFilters" class="documents-filters documents-filters-with-year"><div class="documents-search"><label class="visually-hidden" for="docQuery">Search title</label><input class="site-search-input" id="docQuery" name="q" type="search" placeholder="Search document titles"><button type="submit" class="search-submit-icon"><svg class="site-search-icon" viewBox="0 0 24 24"></svg></button></div><details class="site-more-filters" data-filter-popover><summary>Filters</summary><div class="site-secondary-filters"><label class="documents-select"><span>Sort by</span><select name="sort"><option value="recent">Most recent</option></select></label><label class="documents-select"><span>Academic year</span><select name="academic_year"><option value="">All academic years</option></select></label><label class="documents-select"><span>Category</span><select name="category"><option value="">All categories</option><option value="Minutes">Minutes</option></select></label><label class="documents-select"><span>Semester</span><select name="semester"><option value="">All semesters</option><option value="1">1st Semester</option></select></label></div></details></form></div></main>';(0,eval)(${JSON.stringify(controlsScript)});`);
    const desktop = await evaluate(`({searchY:docQuery.getBoundingClientRect().bottom,filtersY:documentFilters.querySelector('summary').getBoundingClientRect().bottom,hidden:['sort','academic_year','semester'].map(name=>documentFilters.elements[name].getBoundingClientRect().height),overflow:document.documentElement.scrollWidth>innerWidth})`);
    assert.ok(Math.abs(desktop.searchY-desktop.filtersY)<1,JSON.stringify(desktop));
    assert.deepEqual(desktop.hidden,[0,0,0]);
    assert.equal(desktop.overflow,false);
    await evaluate(`documentFilters.querySelector('details').open=true`);
    assert.ok(await evaluate(`documentFilters.elements.semester.getBoundingClientRect().height>=44`));
    console.log('PASS: Documents search and Filters share one desktop row, and the filter fields stay in the popover');
    await evaluate(`documentFilters.querySelector('details').open=false`);
    for(const width of [390,360]){
        await send('Emulation.setDeviceMetricsOverride',{width,height:844,deviceScaleFactor:1,mobile:false},sessionId);
        assert.equal(await evaluate(`document.documentElement.scrollWidth>innerWidth`),false);
        const bounds=await evaluate(`[...documentFilters.querySelectorAll('input,summary')].map(el=>({height:el.getBoundingClientRect().height,right:el.getBoundingClientRect().right}))`);
        for(const b of bounds){assert.ok(b.height>=44,JSON.stringify(b));assert.ok(b.right<=width,JSON.stringify(b));}
    }
    console.log('PASS: simplified Documents filters remain usable at mobile widths');
    await evaluate(`documentFilters.action='about:blank';document.querySelector('main').style.paddingTop='700px';
        docQuery.focus({preventScroll:true});window.scrollTo({top:document.documentElement.scrollHeight,behavior:'instant'});
        window.searchScrollStart=[window.scrollY,document.documentElement.scrollHeight];
        docQuery.value='wORK';docQuery.dispatchEvent(new Event('input',{bubbles:true}));`);
    await evaluate(`new Promise(resolve=>setTimeout(resolve,320))`);
    await checkClearIcon('.documents-search');
    assert.deepEqual(await evaluate(`[window.scrollY,document.documentElement.scrollHeight]`),await evaluate('searchScrollStart'));
    assert.equal(await evaluate(`documentFilters.querySelector('[data-active-filters]')`),null);
    await evaluate(`docQuery.dispatchEvent(new KeyboardEvent('keydown',{key:'ArrowDown',bubbles:true,cancelable:true}));`);
    assert.deepEqual(await evaluate(`[window.scrollY,document.documentElement.scrollHeight]`),await evaluate('searchScrollStart'));
    assert.equal(await evaluate(`document.getElementById(docQuery.getAttribute('aria-activedescendant')).textContent`),'Workshop kickoff');
    console.log('PASS: typing and moving through suggestions near the bottom keep the page in place');
    await evaluate(`docQuery.value='exact phrase';docQuery.dispatchEvent(new Event('input',{bubbles:true}));`);
    await send('Input.dispatchKeyEvent',{type:'keyDown',key:'Enter',code:'Enter'},sessionId);
    await send('Input.dispatchKeyEvent',{type:'keyUp',key:'Enter',code:'Enter'},sessionId);
    assert.equal(await evaluate('submissions.at(-1).q'),'exact phrase');
    await evaluate(`const field=docQuery.parentElement;const refreshed=field.cloneNode(true);refreshed.querySelectorAll('.search-clear-button,.search-suggestions').forEach(node=>node.remove());const input=refreshed.querySelector('input');for(const attr of ['role','aria-expanded','aria-controls','aria-autocomplete'])input.removeAttribute(attr);field.replaceWith(refreshed);input.focus({preventScroll:true});`);
    assert.equal(await evaluate(`docQuery.getAttribute('aria-expanded')`),'false');
    console.log('PASS: Enter submits the typed keyword and restored focus keeps suggestions closed');
})().catch((error) => { console.error(error); process.exitCode = 1; }).finally(async () => {
    clearTimeout(timeout);
    try { await send('Browser.close'); } catch {}
    browser.kill();
});
