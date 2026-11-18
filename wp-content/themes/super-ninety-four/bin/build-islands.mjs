import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const themeRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const legacyDir = path.join(themeRoot, 'resources/js/legacy');
const islandsDir = path.join(themeRoot, 'resources/js/islands');

fs.mkdirSync(islandsDir, { recursive: true });

function stripOuterIife(src) {
  let body = src.trim();
  body = body.replace(/^\/\*[\s\S]*?\*\//, '').trim();
  if (body.startsWith('(function ($)')) {
    body = body.replace(/^\(function\s*\(\s*\$\s*\)\s*\{/, '');
    body = body.replace(/\}\)\s*\(\s*jQuery\s*\)\s*;?\s*$/, '');
    return { type: 'jquery', body: body.trim() };
  }
  if (body.startsWith('(() =>') || body.startsWith('(function ()') || body.startsWith('(function()')) {
    body = body.replace(/^\(\(\)\s*=>\s*\{/, '');
    body = body.replace(/^\(function\s*\(\s*\)\s*\{/, '');
    body = body.replace(/\}\)\s*\(\s*\)\s*;?\s*$/, '');
    return { type: 'iife', body: body.trim() };
  }
  return { type: 'raw', body };
}

function writeExport(name, body, jquery = false) {
  const file = path.join(islandsDir, `${name}.js`);
  let content;
  if (jquery) {
    content = `export function init${pascal(name)}() {\n  const $ = window.jQuery;\n  if (!$) return;\n${body}\n}\n`;
  } else {
    content = `export function init${pascal(name)}() {\n${body}\n}\n`;
  }
  fs.writeFileSync(file, content);
}

function pascal(slug) {
  return slug
    .split(/[-_]/)
    .map((p) => p.charAt(0).toUpperCase() + p.slice(1))
    .join('');
}

const map = {
  marketplace: 'marketplace',
  'media-library': 'media-library',
  'expo-marketplace': 'expo-marketplace',
  dashboard: 'dashboard',
  hub: 'hub',
  messages: 'messages',
  'service-quote': 'service-quote',
  workshop: 'workshop',
  'event-single': 'event-single',
};

for (const [srcName, outName] of Object.entries(map)) {
  const src = fs.readFileSync(path.join(legacyDir, `${srcName}.js`), 'utf8');
  const { type, body: rawBody } = stripOuterIife(src);
  let body = rawBody;

  if (outName === 'media-library') {
    body = body.replace(/['"]use strict['"];?\s*/m, '');
    body = body.replace(
      /document\.addEventListener\(\s*['"]DOMContentLoaded['"]\s*,\s*function\s*\(\s*\)\s*\{\s*initTabs\(\);\s*initLiveRooms\(\);\s*\}\s*\)\s*;?/m,
      'initTabs();\n\tinitLiveRooms();'
    );
  }

  // export names: marketplace -> Marketplace, media-library -> MediaLibrary, etc.
  const exportBase = outName
    .split('-')
    .map((p) => p.charAt(0).toUpperCase() + p.slice(1))
    .join('');

  const file = path.join(islandsDir, `${outName}.js`);
  if (type === 'jquery') {
    fs.writeFileSync(
      file,
      `export function init${exportBase}() {\n  const $ = window.jQuery;\n  if (!$) return;\n${body}\n}\n`
    );
  } else {
    fs.writeFileSync(file, `export function init${exportBase}() {\n${body}\n}\n`);
  }
}

console.log('Wrote islands:', fs.readdirSync(islandsDir).join(', '));
