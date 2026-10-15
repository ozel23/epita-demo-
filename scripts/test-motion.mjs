import {readFile} from 'node:fs/promises';
import {runInNewContext} from 'node:vm';
import assert from 'node:assert/strict';
const source=await readFile('resources/js/motion.js','utf8');
function motionRun(reduced){
  let frames=0;const queue=[];const classes=new Set();
  const number={style:{},getBoundingClientRect:()=>({width:80}),dataset:{countValue:'100',countDecimals:'0'},textContent:'100'};
  const body={classList:{add:value=>classes.add(value),remove:value=>classes.delete(value),contains:value=>classes.has(value),toggle:value=>classes.has(value)?classes.delete(value):classes.add(value)}};
  const document={body,getElementById:()=>null,querySelector:()=>null,querySelectorAll:selector=>selector==='[data-count-value]'?[number]:[],addEventListener:()=>{}};
  const window={innerWidth:1366,matchMedia:()=>({matches:reduced}),addEventListener:()=>{}};
  runInNewContext(source,{document,window,sessionStorage:{getItem:()=>null,setItem:()=>{}},localStorage:{},performance:{now:()=>0},requestAnimationFrame:callback=>{frames++;queue.push(callback);},setTimeout:()=>{},WeakSet,console});
  let now=0;while(queue.length&&now<1200){now+=60;queue.shift()(now);}return {frames,classes,number,pending:queue.length};
}
const reduced=motionRun(true);assert.equal(reduced.frames,0,'Reduced motion must not start counters');assert.equal(reduced.number.textContent,'100');assert.equal(reduced.classes.has('motion-initial'),false);
const normal=motionRun(false);assert.ok(normal.frames>0&&normal.frames<=12,'Counter sequence finishes in bounded frames');assert.equal(normal.pending,0);assert.equal(normal.number.textContent,'100');assert.equal(normal.classes.has('motion-initial'),true);
const chartSource=await readFile('resources/js/dashboard.js','utf8');
function chartRun(reduced){
 const instances=[];const data={trend:[{report_date:'2026-10-14',meter_type:'KM',meter_total:10,hours:2}],conditions:{WORKING:1},fuelTrend:[],fuelByEquipment:[],utilization:[],downtimeByType:[]};
 const document={getElementById:id=>id==='dashboard-data'?{textContent:JSON.stringify(data)}:(['trendChart','conditionChart'].includes(id)?{closest:()=>null}:null)};
 function Chart(canvas,config){instances.push(config);}Chart.defaults={font:{}};
 runInNewContext(chartSource,{window:{matchMedia:()=>({matches:reduced,addEventListener:()=>{}})},document,Chart,console});return instances;
}
for(const config of chartRun(true))assert.equal(config.options.animation,false,'Reduced motion charts must be static');
for(const config of chartRun(false))assert.equal(config.options.animation.duration,620,'Chart animation is finite and within the requested range');
console.log('PASS: reduced motion preserves final numbers; no counters/entrances; charts are static. Normal counters and charts run once.');
