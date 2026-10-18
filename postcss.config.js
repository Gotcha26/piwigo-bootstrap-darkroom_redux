'use strict'

const postcss = require('postcss')

// Bootswatch 4.3.1 emits `@import url($web-font-path)` unconditionally: the
// variable can redirect the import but never remove it. Web fonts are
// self-hosted (css/<style>/fonts.css, tools/vendor_fonts.py), so any import
// from a font CDN is dropped here.
const dropRemoteFonts = postcss.plugin('drop-remote-fonts', () => (root) => {
  root.walkAtRules('import', (rule) => {
    if (/fonts\.(googleapis|gstatic)\.com/.test(rule.params)) {
      rule.remove()
    }
  })
})

module.exports = () => ({
  map: false,
  plugins: [
    require('autoprefixer')({ cascade: false }),
    dropRemoteFonts()
  ]
})
