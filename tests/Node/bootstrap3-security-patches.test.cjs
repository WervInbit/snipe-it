'use strict'

const assert = require('node:assert/strict')
const childProcess = require('node:child_process')
const fs = require('node:fs')
const path = require('node:path')
const test = require('node:test')

const projectRoot = path.resolve(__dirname, '..', '..')

test('the installed Bootstrap 3 runtime has every security backport', () => {
  childProcess.execFileSync(
    process.execPath,
    [path.join(projectRoot, 'scripts', 'patch-bootstrap3-security.cjs'), '--check'],
    { cwd: projectRoot, stdio: 'pipe' }
  )

  const buttonSource = fs.readFileSync(
    path.join(projectRoot, 'node_modules', 'bootstrap', 'js', 'button.js'),
    'utf8'
  )
  const carouselSource = fs.readFileSync(
    path.join(projectRoot, 'node_modules', 'bootstrap', 'js', 'carousel.js'),
    'utf8'
  )
  const tooltipSource = fs.readFileSync(
    path.join(projectRoot, 'node_modules', 'bootstrap', 'js', 'tooltip.js'),
    'utf8'
  )

  assert.match(buttonSource, /\$el\.text\(stateValue\)/)
  assert.doesNotMatch(
    buttonSource,
    /\$el\[val\]\(data\[state\] == null \? this\.options\[state\] : data\[state\]\)/
  )
  assert.match(carouselSource, /if \(!\$target\.hasClass\('carousel'\)\) return false/)
  assert.match(tooltipSource, /new window\.DOMParser\(\)\.parseFromString/)
  assert.match(tooltipSource, /document\.implementation instanceof window\.DOMImplementation/)
  assert.match(tooltipSource, /return createdBody\.innerHTML/)
  assert.doesNotMatch(
    tooltipSource,
    /if \(!document\.implementation \|\| !document\.implementation\.createHTMLDocument\) \{\s+return unsafeHtml/
  )
})

test('the application bundles the patched Bootstrap 3.4.1 package', () => {
  const entrySource = fs.readFileSync(
    path.join(projectRoot, 'resources', 'assets', 'js', 'snipeit.js'),
    'utf8'
  )
  const packageJson = JSON.parse(
    fs.readFileSync(path.join(projectRoot, 'package.json'), 'utf8')
  )

  assert.match(entrySource, /require\('bootstrap'\)/)
  assert.doesNotMatch(entrySource, /require\('bootstrap-less'\)/)
  assert.equal(packageJson.dependencies.bootstrap, '^3.4.1')
  assert.equal(packageJson.dependencies['bootstrap-less'], undefined)
  assert.equal(
    packageJson.scripts.postinstall,
    'node scripts/patch-bootstrap3-security.cjs'
  )
})
