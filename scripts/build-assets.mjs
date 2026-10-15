import {mkdir,copyFile} from 'node:fs/promises';
import {build} from 'esbuild';
await mkdir('public/assets/fonts',{recursive:true});
await copyFile('node_modules/@fontsource/inter/LICENSE','public/assets/fonts/LICENSE.txt');
for(const weight of [400,500,600,700])await copyFile(`node_modules/@fontsource/inter/files/inter-latin-${weight}-normal.woff2`,`public/assets/fonts/inter-latin-${weight}-normal.woff2`);
for(const [source,target] of [
 ['node_modules/bootstrap/dist/css/bootstrap.min.css','bootstrap.min.css'],
 ['node_modules/bootstrap/dist/js/bootstrap.bundle.min.js','bootstrap.bundle.min.js'],
 ['node_modules/chart.js/dist/chart.umd.js','chart.umd.js']
])await copyFile(source,'public/assets/'+target);
await build({entryPoints:['resources/js/app.js','resources/js/dashboard.js','resources/js/motion.js'],outdir:'public/assets',minify:true,target:['es2020'],legalComments:'none'});
await build({entryPoints:['resources/css/app.css'],outdir:'public/assets',bundle:false,minify:true,legalComments:'none'});
console.log('EPITA assets built: React/Inertia + Tailwind, local fonts, Chart.js; legacy assets retained for archived views.');

await build({entryPoints:['resources/js/app.jsx'],outdir:'public/assets/react',entryNames:'inertia',chunkNames:'chunks/[name]-[hash]',format:'esm',splitting:true,external:['/assets/*'],bundle:true,minify:true,target:['es2020'],define:{'process.env.NODE_ENV':'\"production\"'},legalComments:'none'});
