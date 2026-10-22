const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const css = require('css-tree');
const path = require('node:path');
test('all component styles parse as valid CSS',()=>{
  const files=['style.css',...fs.readdirSync('assets/css').filter(file=>file.endsWith('.css')).map(file=>`assets/css/${file}`)];
  for(const file of files)assert.doesNotThrow(()=>css.parse(fs.readFileSync(file,'utf8'),{onParseError(error){throw error}}),file);
});
test('local CSS resource URLs resolve to existing files',()=>{
  for(const file of ['style.css',...fs.readdirSync('assets/css').filter(file=>file.endsWith('.css')).map(file=>`assets/css/${file}`)]) {
    css.walk(css.parse(fs.readFileSync(file,'utf8')),node=>{
      if(node.type!=='Url' || /^(https?:|data:|\/\/|#)/.test(node.value))return;
      const target=path.resolve(path.dirname(file),decodeURI(node.value.split(/[?#]/)[0]));
      assert(fs.existsSync(target),`${file}: missing resource ${node.value}`);
    });
  }
});
