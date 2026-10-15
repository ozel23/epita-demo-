(() => {
  const body = document.body;
  const mobile = () => window.innerWidth <= 800;
  const sidebar = document.getElementById('sidebar');
  const toggle = document.getElementById('nav-toggle');
  const backdrop = document.getElementById('sidebar-backdrop');
  const profile = document.querySelector('.profile-trigger');
  const dropdown = document.getElementById('profile-dropdown');
  function setNavigation(open) {
    if (!sidebar) return;
    if (mobile()) {
      body.classList.toggle('nav-open', open);
      sidebar.inert = !open;if(open){sidebar.setAttribute('role','dialog');sidebar.setAttribute('aria-modal','true');}else{sidebar.removeAttribute('role');sidebar.removeAttribute('aria-modal');}
      if (backdrop) backdrop.hidden = !open;
      toggle?.setAttribute('aria-expanded', String(open));
      if (open) sidebar.querySelector('a')?.focus();
    } else {
      body.classList.add('nav-transition');setTimeout(()=>body.classList.remove('nav-transition'),280);body.classList.toggle('nav-collapsed', open);
      sidebar.inert = false;sidebar.removeAttribute('role');sidebar.removeAttribute('aria-modal');
      toggle?.setAttribute('aria-expanded', String(!open));
      if (backdrop) backdrop.hidden = true;
      try {localStorage.setItem('siova-sidebar-collapsed',String(open));} catch {}
    }
  }
  if (sidebar) {
    try {if (!mobile()) body.classList.toggle('nav-collapsed',localStorage.getItem('siova-sidebar-collapsed')==='true');} catch {}
    sidebar.inert = mobile();
    toggle?.setAttribute('aria-expanded',String(!mobile()&&!body.classList.contains('nav-collapsed')));
    toggle?.addEventListener('click',() => setNavigation(mobile()?!body.classList.contains('nav-open'):!body.classList.contains('nav-collapsed')));
    backdrop?.addEventListener('click',() => {setNavigation(false);toggle?.focus();});
    window.addEventListener('resize',() => {sidebar.inert=mobile()&&!body.classList.contains('nav-open');toggle?.setAttribute('aria-expanded',String(mobile()?body.classList.contains('nav-open'):!body.classList.contains('nav-collapsed')));if(!mobile()){sidebar.removeAttribute('role');sidebar.removeAttribute('aria-modal');}if(!mobile()){body.classList.remove('nav-open');if(backdrop)backdrop.hidden=true;}});
    sidebar.addEventListener('keydown',event => {
      if(!mobile()||!body.classList.contains('nav-open')||event.key!=='Tab')return;
      const links=[...sidebar.querySelectorAll('a,button')].filter(node=>!node.hidden);
      if(event.shiftKey&&document.activeElement===links[0]){event.preventDefault();links.at(-1)?.focus();}
      else if(!event.shiftKey&&document.activeElement===links.at(-1)){event.preventDefault();links[0]?.focus();}
    });
  }
  profile?.addEventListener('click',() => {dropdown.hidden=!dropdown.hidden;profile.setAttribute('aria-expanded',String(!dropdown.hidden));});
  document.addEventListener('click',event=>{if(dropdown&&!event.target.closest('.profile-menu')){dropdown.hidden=true;profile.setAttribute('aria-expanded','false');}});
  document.addEventListener('keydown',event=>{if(event.key==='Escape'){if(mobile()&&body.classList.contains('nav-open')){setNavigation(false);toggle?.focus();}if(dropdown&&!dropdown.hidden){dropdown.hidden=true;profile.setAttribute('aria-expanded','false');profile.focus();}}});
  document.querySelectorAll('[data-password-toggle]').forEach(button=>button.addEventListener('click',()=>{
    const input=document.getElementById(button.dataset.passwordToggle);if(!input)return;
    const visible=input.type==='password';input.type=visible?'text':'password';button.setAttribute('aria-pressed',String(visible));button.setAttribute('aria-label',visible?'Sembunyikan kata sandi':'Tampilkan kata sandi');
  }));
  const dialog=document.getElementById('confirmation-dialog');
  let pendingForm=null,pendingSubmitter=null;
  const confirmed=new WeakSet();
  document.querySelectorAll('form[data-confirm]').forEach(form=>form.addEventListener('submit',event=>{
    if(confirmed.has(form)){confirmed.delete(form);return;}
    if(!dialog?.showModal){if(!confirm(form.dataset.confirm))event.preventDefault();return;}
    event.preventDefault();pendingForm=form;pendingSubmitter=event.submitter;
    document.getElementById('confirmation-message').textContent=form.dataset.confirm;const destructive=!!form.querySelector('[name=_method][value=DELETE]')||/rollback|cancel/.test(new URL(form.action).pathname);const confirmButton=document.getElementById('confirm-action');confirmButton.classList.toggle('btn-danger',destructive);confirmButton.classList.toggle('btn-primary',!destructive);dialog.classList.toggle('is-destructive',destructive);dialog.showModal();
  }));
  document.querySelectorAll('[data-close-dialog]').forEach(button=>button.addEventListener('click',()=>button.closest('dialog')?.close()));
  document.getElementById('confirm-action')?.addEventListener('click',()=>{const form=pendingForm,submitter=pendingSubmitter;dialog.close();if(form){confirmed.add(form);form.requestSubmit(submitter||undefined);}pendingForm=null;pendingSubmitter=null;});
  document.querySelectorAll('form').forEach(form=>form.addEventListener('submit',event=>{
    if(event.defaultPrevented)return;
    const submitter=event.submitter;
    // Preserve named submit buttons (export format etc.) and native file download behavior.
    if(submitter?.name==='format')return;
    if(form.dataset.submitting==='true'){event.preventDefault();return;}
    form.dataset.submitting='true';form.setAttribute('aria-busy','true');
    if(submitter){submitter.dataset.originalLabel=submitter.innerHTML;submitter.setAttribute('aria-disabled','true');submitter.innerHTML='<span class="button-spinner" aria-hidden="true"></span><span></span>';submitter.lastElementChild.textContent=form.dataset.loadingLabel||'Memproses…';}
    body.classList.add('is-navigating');
  }));
  window.addEventListener('pageshow',()=>{body.classList.remove('is-navigating');document.querySelectorAll('form[data-submitting]').forEach(form=>{delete form.dataset.submitting;form.removeAttribute('aria-busy');form.querySelectorAll('[data-original-label]').forEach(button=>{button.innerHTML=button.dataset.originalLabel;button.removeAttribute('aria-disabled');delete button.dataset.originalLabel;});});});
  document.querySelectorAll('a[href]').forEach(link=>link.addEventListener('click',event=>{if(!event.defaultPrevented&&!event.ctrlKey&&!event.metaKey&&!link.target&&link.origin===location.origin&&!link.hash&&!link.hasAttribute('download')&&!/attachments|\/download|\/template/.test(link.pathname))body.classList.add('is-navigating');}));
  document.querySelectorAll('[data-toast]').forEach(toast=>{let timer=setTimeout(()=>toast.remove(),6500);toast.querySelector('[data-dismiss-toast]')?.addEventListener('click',()=>toast.remove());toast.addEventListener('mouseenter',()=>clearTimeout(timer));toast.addEventListener('focusin',()=>clearTimeout(timer));});
  // Existing Blade forms receive permanent label associations and inline validation support.
  let fieldId=0;
  document.querySelectorAll('label.form-label, .filter-bar label').forEach(label=>{
    if(label.htmlFor)return;const control=label.nextElementSibling?.matches('input,select,textarea')?label.nextElementSibling:label.parentElement.querySelector('input,select,textarea');
    if(control){if(!control.id)control.id='siova-field-'+(++fieldId);label.htmlFor=control.id;if(control.required&&!label.querySelector('.required-mark')){const mark=document.createElement('span');mark.className='required-mark';mark.textContent=' *';mark.setAttribute('aria-hidden','true');label.append(mark);}}
  });
  const validation=document.getElementById('form-validation');
  if(validation){try{const errors=JSON.parse(validation.textContent);const controls=[...document.querySelectorAll('input[name],select[name],textarea[name]')];for(const [field,messages] of Object.entries(errors)){for(const control of controls.filter(node=>node.name.replace(/\[([^\]]*)\]/g,'.$1').replace(/\.$/,'')===field)){if(!control.id)control.id='siova-field-'+(++fieldId);const errorId=control.id+'-error';let error=document.getElementById(errorId);if(!error){error=document.createElement('div');error.id=errorId;error.className='invalid-feedback d-block';error.textContent=messages.join(' ');(control.closest('.password-field')||control).after(error);}control.classList.add('is-invalid');control.setAttribute('aria-invalid','true');const described=new Set((control.getAttribute('aria-describedby')||'').split(' ').filter(Boolean));described.add(errorId);control.setAttribute('aria-describedby',[...described].join(' '));}}}catch{}}
  document.querySelectorAll('input[type=file]').forEach(input=>{
    let preview=null,objectUrl=null;
    const error=document.createElement('p');error.className='file-error';error.setAttribute('role','alert');error.hidden=true;input.after(error);
    input.addEventListener('change',()=>{
      if(objectUrl)URL.revokeObjectURL(objectUrl);preview?.remove();preview=null;error.hidden=true;input.setCustomValidity('');
      const file=input.files?.[0];if(!file)return;
      const isPhoto=/image\//.test(input.accept);
      if(file.size>(isPhoto?5:10)*1024*1024||(isPhoto&&!['image/jpeg','image/png','image/webp'].includes(file.type))){error.textContent=isPhoto?'Gunakan foto JPG, PNG, atau WebP maksimal 5 MB.':'Ukuran file maksimal 10 MB.';error.hidden=false;input.setCustomValidity(error.textContent);return;}
      preview=document.createElement('div');preview.className='file-preview';
      if(isPhoto){const image=document.createElement('img');objectUrl=URL.createObjectURL(file);image.src=objectUrl;image.alt='Preview foto yang akan diunggah';preview.append(image);}
      const meta=document.createElement('div');meta.className='file-meta';meta.textContent=file.name;const size=document.createElement('small');size.textContent=(file.size/1024).toLocaleString('id-ID',{maximumFractionDigits:0})+' KB';meta.append(size);preview.append(meta);
      const remove=document.createElement('button');remove.type='button';remove.className='icon-button';remove.setAttribute('aria-label','Hapus file '+file.name);remove.textContent='×';remove.addEventListener('click',()=>{input.value='';input.dispatchEvent(new Event('change'));input.focus();});preview.append(remove);input.after(preview);
    });
    // Multipart uploads show real transfer progress and preserve server redirect destinations.
    const form=input.closest('form');if(!form||form.enctype!=='multipart/form-data')return;
    form.addEventListener('submit',event=>{
      if(event.defaultPrevented)return;event.preventDefault();body.classList.remove('is-navigating');
      const progress=document.createElement('progress');progress.className='upload-progress';progress.max=100;progress.value=0;progress.setAttribute('aria-label','Progres unggahan foto');input.after(progress);
      const xhr=new XMLHttpRequest();xhr.open(form.method,form.action);xhr.setRequestHeader('Accept','application/json');
      xhr.upload.addEventListener('progress',e=>{if(e.lengthComputable)progress.value=Math.round(e.loaded/e.total*100);});
      function reset(){delete form.dataset.submitting;form.removeAttribute('aria-busy');form.querySelectorAll('[data-original-label]').forEach(button=>{button.innerHTML=button.dataset.originalLabel;button.removeAttribute('aria-disabled');});progress.remove();}
      xhr.addEventListener('load',()=>{reset();if(xhr.status>=200&&xhr.status<300){if(!xhr.getResponseHeader('Content-Type')?.includes('application/json')&&xhr.responseURL&&new URL(xhr.responseURL).origin===location.origin)location.assign(xhr.responseURL);else location.reload();return;}let message='Unggahan gagal. Periksa foto dan coba kembali.';try{const result=JSON.parse(xhr.responseText);message=Object.values(result.errors||{}).flat().join(' ')||result.message||message;}catch{}error.textContent=message;error.hidden=false;});
      xhr.addEventListener('error',()=>{reset();error.textContent='Koneksi terputus. Foto belum tersimpan; coba unggah kembali.';error.hidden=false;});xhr.send(new FormData(form));
    });
  });
  document.querySelectorAll('[data-table-search]').forEach(search=>{
    const region=document.getElementById(search.dataset.tableSearch);if(!region)return;
    const rows=[...region.querySelectorAll('tbody tr')].filter(row=>!row.querySelector('[colspan]'));
    const pagination=document.querySelector('[data-table-pagination="'+region.id+'"]');if(!pagination||!rows.length)return;
    search.closest('[data-table-tools]').hidden=false;pagination.hidden=false;let page=0;const pageSize=5;
    function render(){const filtered=rows.filter(row=>row.textContent.toLocaleLowerCase('id-ID').includes(search.value.toLocaleLowerCase('id-ID')));const pages=Math.max(1,Math.ceil(filtered.length/pageSize));page=Math.min(page,pages-1);rows.forEach(row=>row.hidden=true);filtered.slice(page*pageSize,(page+1)*pageSize).forEach(row=>row.hidden=false);pagination.querySelector('[data-table-result]').textContent=filtered.length?(page*pageSize+1)+'–'+Math.min((page+1)*pageSize,filtered.length)+' dari '+filtered.length+' aktivitas':'Tidak ada aktivitas yang cocok';pagination.querySelector('[data-table-prev]').disabled=page===0;pagination.querySelector('[data-table-next]').disabled=page>=pages-1;}
    search.addEventListener('input',()=>{page=0;render();});pagination.querySelector('[data-table-prev]').addEventListener('click',()=>{page--;render();});pagination.querySelector('[data-table-next]').addEventListener('click',()=>{page++;render();});render();
  });
})();
