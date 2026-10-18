'use strict'

// Fails if a font CDN URL appears in the theme's shipped CSS, templates or JS.
// Web fonts are self-hosted: see tools/vendor_fonts.py.

const fs = require('fs')
const path = require('path')

const ROOT = path.resolve(__dirname, '..')
const SCANNED = ['css', 'template', 'admin', 'js', 'theme.css']
const EXTENSIONS = new Set(['.css', '.tpl', '.js', '.php'])
const PATTERN = /fonts\.(googleapis|gstatic)\.com/

function walk (target, found) {
  const stat = fs.statSync(target)
  if (stat.isDirectory()) {
    for (const entry of fs.readdirSync(target)) walk(path.join(target, entry), found)
  } else if (EXTENSIONS.has(path.extname(target)) && PATTERN.test(fs.readFileSync(target, 'utf8'))) {
    found.push(path.relative(ROOT, target))
  }
  return found
}

const found = SCANNED
  .map((entry) => path.join(ROOT, entry))
  .filter((target) => fs.existsSync(target))
  .reduce((acc, target) => walk(target, acc), [])

if (found.length) {
  console.error('Font CDN URL found (fonts must be self-hosted):\n  ' + found.join('\n  '))
  process.exit(1)
}
console.log('No font CDN URL.')
