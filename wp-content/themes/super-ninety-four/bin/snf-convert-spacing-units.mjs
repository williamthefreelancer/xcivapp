import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const themeRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

const files = [
  'assets/css/main.css',
  'assets/css/admin-dashboard.css',
  'resources/css/app.css',
  'resources/css/admin.css',
  'assets/fonts/snf-fonts.css',
  'style.css',
  path.resolve(themeRoot, '../../plugins/snf-payments/assets/css/checkout.css'),
].map((f) => (path.isAbsolute(f) ? f : path.join(themeRoot, f)));

function toRem(n) {
  if (!Number.isFinite(n) || n === 0) return '0';
  const rem = n / 16;
  const rounded = Math.round(rem * 10000) / 10000;
  return `${rounded}rem`;
}

function convertCss(css) {
  const holes = [];
  css = css.replace(/url\(([^)]*)\)/g, (m) => {
    holes.push(m);
    return `__SNF_URL_${holes.length - 1}__`;
  });

  css = css.replace(/(-?\d*\.?\d+)px\b/g, (_, n) => toRem(parseFloat(n)));

  css = css.replace(/\bpadding-top\b/g, 'margin-top');
  css = css.replace(/\bpadding-right\b/g, 'margin-right');
  css = css.replace(/\bpadding-bottom\b/g, 'margin-bottom');
  css = css.replace(/\bpadding-left\b/g, 'margin-left');
  css = css.replace(/\bpadding-inline-start\b/g, 'margin-inline-start');
  css = css.replace(/\bpadding-inline-end\b/g, 'margin-inline-end');
  css = css.replace(/\bpadding-block-start\b/g, 'margin-block-start');
  css = css.replace(/\bpadding-block-end\b/g, 'margin-block-end');
  css = css.replace(/\bpadding-inline\b/g, 'margin-inline');
  css = css.replace(/\bpadding-block\b/g, 'margin-block');
  css = css.replace(/(^|[^-])\bpadding\s*:/gm, '$1margin:');

  css = css.replace(/__SNF_URL_(\d+)__/g, (_, i) => holes[Number(i)]);
  return css;
}

for (const full of files) {
  if (!fs.existsSync(full)) {
    console.log('skip', full);
    continue;
  }
  const before = fs.readFileSync(full, 'utf8');
  const after = convertCss(before);
  fs.writeFileSync(full, after);
  const pxLeft = (after.match(/\d+px\b/g) || []).length;
  const padLeft = (after.match(/(^|[^-])\bpadding\s*:/gm) || []).length;
  console.log(path.relative(themeRoot, full), `pxLeft=${pxLeft}`, `paddingProps=${padLeft}`);
}
