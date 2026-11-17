'use strict';
// Execute only the owned index's filter logic against an inert DOM-shaped dataset.
// This is not a browser or a visual layout test; it has no network capability.
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const argument = process.argv.indexOf('--root');
const root = argument >= 0 ? path.resolve(process.argv[argument+1]) : path.resolve(__dirname,'../..');
const content = fs.readFileSync(path.join(root,'START HIER.html'),'utf8');
assert(!/<script[^>]+src=|fetch\s*\(|<iframe/i.test(content),'Unexpected external content in offline index');
const scripts = [...content.matchAll(/<script>([\s\S]*?)<\/script>/g)];
assert.equal(scripts.length,1);
const articles = [...content.matchAll(/<article\s+([^>]+)>[\s\S]*?<span class="code">([^<]+)<\/span>/g)].map(match => {
    const category = match[1].match(/data-category="([^"]*)"/)[1];
    const search = match[1].match(/data-search="([^"]*)"/)[1];
    return {code:match[2],dataset:{category,search},hidden:false};
});
assert.equal(articles.length,25);
const elements = Object.fromEntries(['search','category','count','empty'].map(id => [id,{value:'',hidden:false,textContent:'',addEventListener(){}}]));
const context = {document:{getElementById(id){assert(id in elements);return elements[id];},querySelectorAll(selector){assert.equal(selector,'article');return articles;}}};
vm.createContext(context);
vm.runInContext(scripts[0][1],context,{timeout:1000});
const cases = [];
function check(search,category,expected){
    elements.search.value=search;elements.category.value=category;
    context.filter();
    const codes=articles.filter(x=>!x.hidden).map(x=>x.code);
    assert.deepEqual(codes,expected);
    assert.equal(elements.empty.hidden,expected.length!==0);
    cases.push({search,category,codes});
}
check('EIGEN WACHTWOORD','',['AC-02']);
check('wachtwoord','',['AC-02','USR-03']);
check('','Workflows',['WF-01','WF-02']);
check('Supervisor','Apparaten',['AST-03','AST-05']);
check('onbestaande zoekterm','',[]);
check('','',articles.map(x=>x.code));
assert(content.includes('article[hidden]{display:none}'));
console.log(JSON.stringify({passed:true,checks:cases.length,manuals:articles.length,cases,limits:'Offline execution of the actual owned filter script; not a browser, visual or user trial.'},null,2));
