import { copyFileSync, mkdirSync, statSync } from 'node:fs';
import { join, resolve } from 'node:path';

const root = resolve(import.meta.dir, '../..');
const outDir = join(root, 'dist/region-map');
const assetsOutDir = join(outDir, 'region-sandbox');

mkdirSync(outDir, { recursive: true });
mkdirSync(assetsOutDir, { recursive: true });

// 1. Bundle JS
const result = await Bun.build({
  entrypoints: [join(root, 'resources/js/region-sandbox/standalone.js')],
  outdir: outDir,
  naming: 'app.js',
  target: 'browser',
  minify: true,
});

if (!result.success) {
  for (const msg of result.logs) {
    console.error(msg);
  }
  throw new Error('Bun.build failed');
}

// 2. Copy HTML template
const templateSrc = join(root, 'scripts/region-sandbox/index.template.html');
const htmlDest = join(outDir, 'index.html');
copyFileSync(templateSrc, htmlDest);

// 3. Copy public assets
const assetsSrc = join(root, 'public/region-sandbox');
const assetFiles = ['heightmap.json', 'features.json', 'cities.json', 'fallback.jpg'];
for (const file of assetFiles) {
  copyFileSync(join(assetsSrc, file), join(assetsOutDir, file));
}

// 4. Report
const written = [
  join(outDir, 'app.js'),
  htmlDest,
  ...assetFiles.map(f => join(assetsOutDir, f)),
];

console.log('\nBuild complete — dist/region-map/');
for (const f of written) {
  const size = statSync(f).size;
  const kb = (size / 1024).toFixed(1);
  console.log(`  ${f.replace(root + '/', '')}  (${kb} KB)`);
}
