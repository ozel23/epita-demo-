(() => {
  const reducedMotion=window.matchMedia('(prefers-reduced-motion: reduce)');
  const body=document.body;
  // A stable final number is always present in HTML and available to assistive technology.
  let initial=false;try{initial=sessionStorage.getItem('siova-motion-seen')!=='true';sessionStorage.setItem('siova-motion-seen','true');}catch{}
  if(initial&&!reducedMotion.matches){body.classList.add('motion-initial');setTimeout(()=>body.classList.remove('motion-initial'),850);document.querySelectorAll('[data-count-value]').forEach(node=>{node.style.minWidth=node.getBoundingClientRect().width+'px';const value=Number(node.dataset.countValue),decimals=Number(node.dataset.countDecimals)||0,final=node.textContent,start=performance.now();function tick(now){if(reducedMotion.matches){node.textContent=final;return;}const elapsed=Math.min(1,(now-start)/540),eased=1-Math.pow(1-elapsed,3);node.textContent=(value*eased).toLocaleString('id-ID',{minimumFractionDigits:decimals,maximumFractionDigits:decimals});if(elapsed<1)requestAnimationFrame(tick);else node.textContent=final;}requestAnimationFrame(tick);});}
})();
