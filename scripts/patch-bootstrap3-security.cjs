#!/usr/bin/env node

'use strict'

/**
 * Bootstrap 3.4.1 is the final Bootstrap 3 release, but its npm artifact has
 * no upstream patched release for CVE-2024-6484, CVE-2024-6485, or
 * CVE-2025-1647. Apply the Debian LTS backports at install/build time so the
 * application bundle is hardened without relying on mutable node_modules.
 *
 * References:
 * - https://sources.debian.org/patches/twitter-bootstrap3/3.4.1%2Bdfsg-6/0002-CVE-2024-6484.patch/
 * - https://sources.debian.org/patches/twitter-bootstrap3/3.4.1%2Bdfsg-6/0003-CVE-2024-6485.patch/
 * - https://sources.debian.org/patches/twitter-bootstrap3/3.4.1%2Bdfsg-6/CVE-2025-1647.patch/
 */

const fs = require('node:fs')
const path = require('node:path')

const projectRoot = path.resolve(__dirname, '..')
const bootstrapRoot = path.join(projectRoot, 'node_modules', 'bootstrap')
const bootstrapPackagePath = path.join(bootstrapRoot, 'package.json')
const checkOnly = process.argv.includes('--check')

if (!fs.existsSync(bootstrapPackagePath)) {
  throw new Error('Bootstrap is not installed; run npm install before applying its security patches.')
}

const bootstrapPackage = JSON.parse(fs.readFileSync(bootstrapPackagePath, 'utf8'))

if (bootstrapPackage.version !== '3.4.1') {
  throw new Error(
    `Bootstrap security patches target 3.4.1, but ${bootstrapPackage.version} is installed.`
  )
}

const patches = [
  {
    name: 'CVE-2024-6484 carousel target guard',
    file: 'js/carousel.js',
    vulnerable: "    if (!$target.hasClass('carousel')) return\n",
    hardened: "    if (!$target.hasClass('carousel')) return false\n",
  },
  {
    name: 'CVE-2024-6485 button loading text escaping',
    file: 'js/button.js',
    vulnerable: "      $el[val](data[state] == null ? this.options[state] : data[state])\n",
    hardened: [
      "      var stateValue = data[state] == null ? this.options[state] : data[state]",
      "      if (val == 'html' && state == 'loadingText') {",
      "        $el.text(stateValue)",
      "      } else {",
      "        $el[val](stateValue)",
      "      }",
      '',
    ].join('\n'),
  },
  {
    name: 'CVE-2025-1647 DOM-clobber-resistant tooltip sanitizer',
    file: 'js/tooltip.js',
    vulnerable: [
      "    // IE 8 and below don't support createHTMLDocument",
      "    if (!document.implementation || !document.implementation.createHTMLDocument) {",
      "      return unsafeHtml",
      "    }",
      '',
      "    var createdDocument = document.implementation.createHTMLDocument('sanitization')",
      "    createdDocument.body.innerHTML = unsafeHtml",
      '',
      "    var whitelistKeys = $.map(whiteList, function (el, i) { return i })",
      "    var elements = $(createdDocument.body).find('*')",
    ].join('\n'),
    hardened: [
      "    var createdDocument = null",
      '',
      "    if (window.DOMParser) {",
      "      try {",
      "        createdDocument = new window.DOMParser().parseFromString(unsafeHtml, 'text/html')",
      "      } catch (error) {",
      "        createdDocument = null",
      "      }",
      "    }",
      '',
      "    if (!createdDocument || !createdDocument.documentElement) {",
      "      // Fail closed if DOM clobbering has replaced the browser implementation.",
      "      if (!window.DOMImplementation ||",
      "          !document.implementation ||",
      "          !(document.implementation instanceof window.DOMImplementation) ||",
      "          typeof document.implementation.createHTMLDocument !== 'function') {",
      "        throw new Error('Bootstrap could not create a safe HTML document')",
      "      }",
      '',
      "      createdDocument = document.implementation.createHTMLDocument('sanitization')",
      "      createdDocument.body.innerHTML = unsafeHtml",
      "    }",
      '',
      "    var createdBody = createdDocument.body || createdDocument.documentElement",
      "    var whitelistKeys = $.map(whiteList, function (el, i) { return i })",
      "    var elements = $(createdBody).find('*')",
    ].join('\n'),
  },
  {
    name: 'CVE-2025-1647 sanitized document serialization',
    file: 'js/tooltip.js',
    vulnerable: '    return createdDocument.body.innerHTML\n',
    hardened: '    return createdBody.innerHTML\n',
  },
]

let changed = false

for (const patch of patches) {
  const targetPath = path.join(bootstrapRoot, patch.file)
  let source = fs.readFileSync(targetPath, 'utf8')

  if (source.includes(patch.hardened)) {
    continue
  }

  if (!source.includes(patch.vulnerable)) {
    throw new Error(
      `${patch.name} could not be applied because ${patch.file} has unexpected source.`
    )
  }

  if (checkOnly) {
    throw new Error(`${patch.name} is not applied. Run npm install or the patch script.`)
  }

  source = source.replace(patch.vulnerable, patch.hardened)
  fs.writeFileSync(targetPath, source, 'utf8')
  changed = true
}

if (changed) {
  process.stdout.write('Applied Bootstrap 3.4.1 security backports.\n')
} else {
  process.stdout.write('Bootstrap 3.4.1 security backports are present.\n')
}
