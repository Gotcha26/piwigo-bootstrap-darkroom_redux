// EXIF panel of the lightbox (#90): pure helpers of js/exif-panel.js.
// Run: node --test "tests/js/*.test.mjs" (Node 24 does not accept a directory)
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const P = require('../../js/exif-panel.js');

const key = (k, extra = {}) => Object.assign({ key: k, ctrlKey: false, altKey: false, metaKey: false, target: { tagName: 'DIV' } }, extra);

test('i_toggles', () => {
  assert.equal(P.keyAction(key('i'), false), 'toggle');
  assert.equal(P.keyAction(key('i'), true), 'toggle');
  assert.equal(P.keyAction(key('I'), false), 'toggle');
});

test('esc_open_stops', () => {
  assert.equal(P.keyAction(key('Escape'), true), 'close-stop');
});

test('esc_closed_null', () => {
  assert.equal(P.keyAction(key('Escape'), false), null);
});

test('arrows_open_close', () => {
  assert.equal(P.keyAction(key('ArrowLeft'), true), 'close');
  assert.equal(P.keyAction(key('ArrowRight'), true), 'close');
});

test('arrows_closed_null', () => {
  assert.equal(P.keyAction(key('ArrowLeft'), false), null);
});

test('i_in_input_ignored', () => {
  assert.equal(P.keyAction(key('i', { target: { tagName: 'INPUT' } }), false), null);
  assert.equal(P.keyAction(key('i', { target: { tagName: 'TEXTAREA' } }), false), null);
  assert.equal(P.keyAction(key('i', { target: { tagName: 'SELECT' } }), false), null);
  assert.equal(P.keyAction(key('i', { target: { tagName: 'DIV', isContentEditable: true } }), false), null);
});

test('ctrl_i_ignored', () => {
  assert.equal(P.keyAction(key('i', { ctrlKey: true }), false), null);
  assert.equal(P.keyAction(key('i', { altKey: true }), false), null);
  assert.equal(P.keyAction(key('Escape', { metaKey: true }), true), null);
});

const row = (k, v) => ({ key: k, label: k, value: v });

test('summarize_camera_join', () => {
  const s = P.summarize([row('Make', 'Canon'), row('Model', 'Canon EOS 1100D')]);
  assert.deepEqual(s, [{ slot: 'camera', icon: 'camera-compact', text: 'Canon Canon EOS 1100D' }]);
});

test('summarize_lens_fallback', () => {
  const s = P.summarize([row('LensModel', 'EF-S55-250mm')]);
  assert.deepEqual(s, [{ slot: 'lens', icon: 'camera-lens-h', text: 'EF-S55-250mm' }]);
});

test('summarize_aperture_prefix', () => {
  const s = P.summarize([row('FNumber', '13')]);
  assert.equal(s[0].text, 'f/13');
  assert.equal(s[0].icon, 'camera-aperture');
});

test('summarize_order', () => {
  const s = P.summarize([row('Flash', 'no'), row('ISOSpeedRatings', '200'), row('Make', 'Canon')]);
  assert.deepEqual(s.map((e) => e.slot), ['camera', 'iso', 'flash']);
});

test('summarize_empty', () => {
  assert.deepEqual(P.summarize([]), []);
});
