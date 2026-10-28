(()=>{'use strict';
const root=document.documentElement;
try{root.dataset.theme=localStorage.getItem('musonza.theme')||'light'}catch{}
document.getElementById('theme')?.addEventListener('click',()=>{const t=root.dataset.theme==='dark'?'light':'dark';root.dataset.theme=t;try{localStorage.setItem('musonza.theme',t)}catch{}});
const menu=document.getElementById('menu-button'),nav=document.getElementById('navigation');
menu?.addEventListener('click',()=>{const open=menu.getAttribute('aria-expanded')!=='true';menu.setAttribute('aria-expanded',String(open));nav.classList.toggle('open',open)});
document.addEventListener('keydown',e=>{if(e.key==='Escape'){menu?.setAttribute('aria-expanded','false');nav?.classList.remove('open')}});
document.querySelectorAll('form').forEach(form=>form.addEventListener('submit',()=>{if(!form.checkValidity())return;const button=form.querySelector('button[type=submit]');if(button){button.disabled=true;button.dataset.original=button.innerHTML;button.textContent='Sending…';setTimeout(()=>{button.disabled=false;button.innerHTML=button.dataset.original},15000)}}));
})();
