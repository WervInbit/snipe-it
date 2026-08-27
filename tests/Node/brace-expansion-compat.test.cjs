'use strict';

const assert = require('node:assert/strict');
const test = require('node:test');
const expand = require('brace-expansion');

test('preserves the callable CommonJS API expected by legacy build tools', () => {
    assert.equal(typeof expand, 'function');
    assert.deepEqual(expand('file-{a,b}.js'), ['file-a.js', 'file-b.js']);
});

test('uses the patched implementation with bounded expansion output', () => {
    assert.equal(expand.EXPANSION_MAX, 100_000);
    assert.equal(expand.EXPANSION_MAX_LENGTH, 4_000_000);

    const output = expand('{a,b}'.repeat(30), {
        max: 1_000,
        maxLength: 40,
    });

    assert.ok(output.length > 0);
    assert.ok(output.reduce((length, value) => length + value.length, 0) <= 40);
});
