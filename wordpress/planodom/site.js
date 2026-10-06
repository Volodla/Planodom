(function(){const root=document.getElementById('pd-original');if(!root)return;const lead=root.querySelector('#pd-lead');root.querySelectorAll('.lead').forEach(b=>b.addEventListener('click',()=>{root.querySelectorAll('dialog[open]').forEach(d=>d.close());lead.showModal();}));root.querySelectorAll('.close').forEach(b=>b.addEventListener('click',()=>b.closest('dialog').close()));root.querySelectorAll('.project-open').forEach(b=>b.addEventListener('click',()=>{root.querySelector('#pd-project-title').textContent=b.dataset.project;root.querySelector('#pd-project-dialog').showModal();}));const parts={frame:['Фундамент и каркас',['Железобетонные сваи','Строганная доска камерной сушки','Конструкции, обработанные антисептиком'],'Решение фундамента привязывается к участку и проекту. Сечения элементов и состав работ фиксируются в документации.'],insulation:['Утепление',['Базальтовая вата','Контурное утепление','Мембраны для защиты от влаги и ветра'],'Толщина утепления и состав защитных слоёв указываются в комплектации конкретного проекта.'],systems:['Инженерные системы',['Водоснабжение и канализация','Электроснабжение','Отопление конвекторами или водяные тёплые полы'],'Внутренние системы дома и наружные сети участка указываются отдельно. В итоговой смете будут обозначены границы работ.'],windows:['Окна и двери',['Энергоэффективные стеклопакеты','Панорамное остекление','Фурнитура по спецификации'],'Размеры, цвет профиля, состав стеклопакета и фурнитура согласуются для выбранного дома.']};root.querySelectorAll('[data-part]').forEach(b=>b.addEventListener('click',()=>{root.querySelectorAll('[data-part]').forEach(x=>x.setAttribute('aria-selected',String(x===b)));const p=parts[b.dataset.part];root.querySelector('#pd-part-title').textContent=p[0];root.querySelector('#pd-part-list').replaceChildren(...p[1].map(t=>{const li=document.createElement('li');li.textContent=t;return li;}));root.querySelector('#pd-part-more').textContent=p[2];}));})();


(function(){
  const root=document.getElementById('pd-original');
  const band=root.querySelector('.orange-band');
  if(!band)return;
  const track=band.querySelector('.marquee-track');
  const group=track.querySelector('.marquee-group');
  const viewport=band.querySelector('.marquee-window');
  const pause=band.querySelector('.marquee-pause');
  function measure(){
    const width=group.getBoundingClientRect().width;
    if(!width)return;
    track.querySelectorAll('[data-copy]').forEach(el=>el.remove());
    const copies=Math.ceil(viewport.clientWidth/width)+2;
    for(let i=0;i<copies;i++){const clone=group.cloneNode(true);clone.dataset.copy='true';clone.setAttribute('aria-hidden','true');track.appendChild(clone);}
    track.style.setProperty('--pd-distance',width+'px');
    track.style.setProperty('--pd-duration',(width/25)+'s');
  }
  pause.addEventListener('click',()=>{const paused=band.dataset.paused!=='true';band.dataset.paused=String(paused);pause.setAttribute('aria-pressed',String(paused));pause.textContent=paused?'Пуск':'Пауза';pause.setAttribute('aria-label',paused?'Возобновить бегущую строку':'Приостановить бегущую строку');});
  measure();
  new ResizeObserver(measure).observe(viewport);
  if(document.fonts)document.fonts.ready.then(measure);
})();


(function(){const root=document.getElementById('pd-original');root.querySelectorAll('.project-open').forEach(button=>button.addEventListener('click',()=>{const card=button.dataset.image?button:Array.from(root.querySelectorAll('.catalog-grid .project-open')).find(b=>b.dataset.project===button.dataset.project);if(!card)return;const img=root.querySelector('#pd-detail-image');img.src=card.dataset.image;img.alt=card.dataset.project;root.querySelector('#pd-detail-description').textContent=card.dataset.description;}));})();


(()=>{const players=Array.from(document.querySelectorAll('.video-player'));const posters=new Map(players.map(p=>[p,p.innerHTML]));function stop(){players.forEach(p=>{if(p.querySelector('iframe'))p.innerHTML=posters.get(p);});}document.addEventListener('click',e=>{const button=e.target.closest('.video-start');if(!button)return;stop();const player=button.closest('.video-player');const frame=document.createElement('iframe');frame.src='https://www.youtube.com/embed/'+player.dataset.video+'?autoplay=1&playsinline=1&rel=0';frame.title=player.dataset.title;frame.allow='autoplay; encrypted-media; picture-in-picture; fullscreen';frame.allowFullscreen=true;player.replaceChildren(frame);});document.querySelectorAll('.preview-nav button,.blog-link').forEach(b=>b.addEventListener('click',stop));})();

(()=>{const menu=document.querySelector('.mobile-menu');if(!menu)return;menu.querySelectorAll('a,button').forEach(a=>a.addEventListener('click',()=>menu.open=false));document.addEventListener('keydown',e=>{if(e.key==='Escape')menu.open=false;});document.addEventListener('click',e=>{if(menu.open&&!menu.contains(e.target))menu.open=false;});})();