const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const css = require('css-tree');
test('all component styles parse as valid CSS',()=>{
  const files=['style.css',...fs.readdirSync('assets/css').filter(file=>file.endsWith('.css')).map(file=>`assets/css/${file}`)];
  for(const file of files)assert.doesNotThrow(()=>css.parse(fs.readFileSync(file,'utf8'),{onParseError(error){throw error}}),file);
});
