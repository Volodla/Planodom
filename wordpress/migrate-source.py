"""Build editable WordPress content from the public Planodom source snapshot."""
from pathlib import Path
from lxml import html, etree
from html import escape
import json,re,hashlib,urllib.request,io
from concurrent.futures import ThreadPoolExecutor
from PIL import Image,ImageOps
root=Path(__file__).resolve().parent.parent
scratch=root.parent
theme=root/'wordpress/planodom'
assets=theme/'assets';assets.mkdir(exist_ok=True)
def parse(p): return html.fromstring(p.read_text(encoding='utf-8'))
def inner(e): return (e.text or '')+''.join(html.tostring(c,encoding='unicode') for c in e)
def clean(e):
 e=html.fromstring(html.tostring(e,encoding='unicode'))
 for x in e.xpath('.//script|.//style|.//iframe'): x.drop_tree()
 for x in [e]+list(e.iterdescendants()):
  for a in list(x.attrib):
   if a not in ['href','src','alt','id','class','width','height','loading','type','data-video','data-title','aria-label']:del x.attrib[a]
 return inner(e)
def text(e):return ' '.join(' '.join(e.itertext()).split())
def slug(s):
 table=dict(zip('абвгдеёжзийклмнопрстуфхцчшщъыьэюя',['a','b','v','g','d','e','e','zh','z','i','y','k','l','m','n','o','p','r','s','t','u','f','kh','ts','ch','sh','shch','','y','','e','yu','ya']))
 return re.sub(r'[^a-z0-9]+','-',''.join(table.get(c,c) for c in s.lower())).strip('-')
def item(title,path,content,kind='page',**kw):return dict(title=title,slug=path,content=content,type=kind,**kw)
s=parse(scratch/'old-home.html');home=parse(theme/'home.html');products=[];jobs={}
for e in s.xpath('//*[contains(@class,"t786__product-full")]'):
 name=text(e.xpath('.//*[contains(@class,"js-product-name")]')[0]);desc=e.xpath('.//*[contains(@class,"t786__descr")]')[0]
 gallery=list(dict.fromkeys(e.xpath('.//*[@data-original]/@data-original')))
 images=[]
 for u in gallery:
  filename='source-'+hashlib.sha256(u.encode()).hexdigest()[:16]+'.webp';jobs[u]=filename;images.append(filename)
 short=next((text(x.xpath('.//*[contains(@class,"t786__descr")]')[0]) for x in s.xpath('//*[contains(@class,"t786__col") and @data-product-lid]') if x.get('data-product-lid')==e.get('data-product-lid')),'')
 body='<p>'+clean(desc)+'</p>'
 # Preserve source paragraphs without Tilda's font/color overrides.
 body=re.sub(r'<br\s*/?>\s*<br\s*/?>','</p><p>',body)
 body=re.sub(r'<br\s*/?>',' ',body)
 products.append(item(name,slug(name),body,'pd_project',excerpt=short,images=images,source='https://planodom.ru/#'+e.get('id')))
def fetch(pair):
 u,n=pair;p=assets/n
 if p.exists():return
 for attempt in range(2):
  try:
   b=urllib.request.urlopen(u,timeout=35).read();im=ImageOps.exif_transpose(Image.open(io.BytesIO(b)));im.thumbnail((2000,2000));im.convert('RGB').save(p,'WEBP',quality=88);return
  except Exception as err:
   if attempt:raise RuntimeError(u+': '+str(err))
with ThreadPoolExecutor(max_workers=8) as ex:list(ex.map(fetch,jobs.items()))
items=list(products)
for i,a in enumerate(home.xpath('//*[contains(@class,"realized-grid")]/article')):
 name=text(a.xpath('./h3')[0]);imgs=[x.get('src').split('/')[-1] for x in a.xpath('.//img')]
 content='<p>Реализованный проект Planodom. Фотографии построенного объекта.</p><div class="pd-gallery">'+''.join(f'<a href="{{{{ASSET_URL}}}}/{escape(img)}"><img src="{{{{ASSET_URL}}}}/{escape(img)}" alt="{escape(name)} — вид {n+1}" loading="lazy"></a>' for n,img in enumerate(imgs))+'</div><p>Хотите посмотреть дом вживую? Оставьте заявку — согласуем доступный объект и время посещения.</p><button class="orange lead" type="button">Записаться на просмотр</button>'
 items.append(item(name,slug(name),content,'pd_case',excerpt='Фотографии построенного дома Planodom.',images=imgs))
# Index pages use native records through shortcodes.
items.extend([item('Каталог домов','catalog','<p>Выберите архитектуру и планировку. Комплектацию, адаптацию проекта и стоимость уточним при расчёте.</p>[pd_catalog]'),item('Построенные дома','built','<p>Объекты Planodom: фотографии, архитектура и площадь построенных домов.</p>[pd_cases]')])
sections=[('Комплектация дома','komplektatsiya',['pd-package','pd-comfort']),('Технология строительства','technology',['pd-technology']),('О компании','about',['pd-about','pd-guarantees','pd-architect']),('Строительство в ипотеку','ipoteka',['pd-mortgage']),('Глэмпинги и базы отдыха','glamping',['pd-business']),('Посмотреть дом вживую','visit',['pd-visit']),('Видео наших домов','videos',['pd-home-videos'])]
for title,path,ids in sections:
 blocks=[]
 for id in ids:
  el=home.get_element_by_id(id);blocks.append(html.tostring(el,encoding='unicode'))
 items.append(item(title,path,''.join(blocks)))
items.append(item('Контакты и реквизиты','contacts','''<p>Строим дома в Москве и Московской области.</p><h2>Связаться с нами</h2><p><a href="tel:+79151888569">+7 915 188 85 69</a><br><a href="https://t.me/planodom">Telegram Planodom</a></p><h2>Адрес</h2><p>Москва, м. Менделеевская, ул. Новослободская, 36/1.</p><h2>Реквизиты</h2><p>ИП Лыхно Георгий Александрович<br>ИНН 183403029876<br>ОГРН 322508100294543</p><button type="button" class="orange lead">Заказать звонок</button>'''))
policy=parse(scratch/'policy-content.html');items.append(item('Политика конфиденциальности','politconf',clean(policy),source='https://planodom.ru/politconf'))
items.append(item('Спасибо за заявку','sps','<h2>Спасибо за обращение!</h2><p>Заявка сохранена. Мы свяжемся с вами по указанному номеру телефона.</p><p><a class="orange" href="{{HOME_URL}}">Вернуться на главную</a></p>'))
# Editorial content supplied for the new blog; no invented Tilda publication dates.
blog=parse(root/'index.html').get_element_by_id('pd-blog');article=blog.xpath('.//*[contains(@class,"article-layout")]/article')[0]
items.append(item('Как выбрать планировку одноэтажного дома','kak-vybrat-planirovku',inner(article).replace('assets/','{{ASSET_URL}}/'),'post',excerpt='Спальни, хранение и общая зона: как выбрать планировку под привычки вашей семьи.',images=['original-8.webp'],category='Выбор дома'))
items.append(item('Что входит в каркасный дом под ключ','dom-pod-klyuch', '''<p>«Под ключ» удобно оценивать по конкретному составу работ. Перед подписанием договора проверьте, что входит в ваш проект, и какие работы на участке рассчитываются отдельно.</p><h2>Фундамент и каркас</h2><p>В проектах Planodom используется свайный фундамент и деревянный каркас из доски камерной сушки. Тип фундамента, сечения элементов и состав работ фиксируются для выбранного проекта.</p><h2>Тёплый контур</h2><p>В комплектацию входят утепление, защитные мембраны, кровля, окна и наружные двери. В спецификации должны быть указаны материалы и их характеристики. Состав слоёв пола, стены и кровли проверяйте отдельно.</p><h2>Внутренняя и наружная отделка</h2><p>Уточните покрытие фасада, стен, потолков и пола. Для мокрых зон состав отделки может отличаться от жилых комнат. Цвет, материал и дополнительные работы согласуются до строительства.</p><h2>Инженерные системы</h2><p>Планировка определяет места розеток, выводов воды и оборудования. Отопление, водоснабжение, канализацию и электрику нужно видеть в смете отдельными позициями. Наружные сети участка обсуждаются отдельно.</p><h2>Что проверить в смете</h2><ul><li>Состав работ для дома и террасы.</li><li>Материалы, размеры и характеристики.</li><li>Внутренние системы и наружные подключения.</li><li>Сроки, этапы оплаты и гарантийные условия.</li></ul><p><a href="{{HOME_URL}}komplektatsiya/">Посмотреть комплектацию Planodom</a></p>''','post',excerpt='Фундамент, утепление, отделка и инженерные системы: что проверить в комплектации и смете.',images=['comfort-cutaway-v5.webp'],category='Комплектация'))
items.append(item('Как подготовиться к обсуждению проекта дома','podgotovka-k-proektu', '''<p>Первую консультацию можно сделать предметной, если заранее собрать данные об участке и пожелания к дому. Начните с информации, которая у вас уже есть.</p><h2>Данные об участке</h2><p>Подготовьте кадастровый номер и адрес участка. Они помогут обсудить размещение дома и подготовить визуализацию выбранного проекта. Расскажите о подъезде, рельефе и существующих постройках.</p><h2>Кто будет жить в доме</h2><p>Запишите количество постоянных жителей, нужные спальни и сценарии использования: работа из дома, приём гостей, отдых или сдача в аренду. Отметьте помещения, без которых вы не готовы рассматривать проект.</p><h2>Коммуникации</h2><p>Соберите имеющиеся сведения об электричестве, воде и канализации. Если подключения ещё нет, сообщите об этом при расчёте. Внутренние системы дома и работы на участке обсудите отдельно.</p><h2>Пожелания к планировке</h2><p>Выберите близкий проект из каталога и составьте список изменений. Архитектор проверит, какие изменения можно внести с учётом конструкции, участка и ваших задач.</p><h2>Бюджет и сроки</h2><p>Обозначьте ориентир по бюджету, желаемую комплектацию и время начала работ. Затем сравнивайте предложения по одинаковому составу работ.</p><p><a href="{{HOME_URL}}catalog/">Выбрать проект для обсуждения</a></p>''','post',excerpt='Какие сведения об участке, коммуникациях и планировке подготовить перед консультацией.',images=['architect-4.webp'],category='Подготовка участка'))
# Replace popup-only cards with navigable canonical detail links.
byname={x['title']:x for x in products}
raw=(theme/'home.html').read_text()
raw=re.sub(r'<button\b([^>]*\bclass="[^"]*project-open[^>]+)>(.*?)</button>',lambda m: '<a class="orange" href="{{HOME_URL}}projects/'+byname[html.fromstring('<button '+m.group(1)+'>x</button>').get('data-project')]['slug']+'/">'+m.group(2)+'</a>' if html.fromstring('<button '+m.group(1)+'>x</button>').get('data-project') in byname else m.group(0),raw,flags=re.S)
raw=raw.replace('https://planodom.ru/politconf','{{HOME_URL}}politconf/')
# Dedicated page links in both desktop and mobile navigation/footer.
links={'pd-catalog':'catalog','pd-built':'built','pd-package':'komplektatsiya','pd-mortgage':'ipoteka','pd-about':'about','pd-contact':'contacts','pd-technology':'technology','pd-business':'glamping','pd-architect':'about','pd-visit':'visit','pd-requisites':'contacts','pd-guarantees':'about'}
for anchor,path in links.items():raw=raw.replace('href="#'+anchor+'"','href="{{HOME_URL}}'+path+'/"')
raw=raw.replace('</main>','<section class="pd-blog-preview"><h2>Полезное о строительстве</h2>[pd_articles limit="3"]<p><a class="outline" href="{{BLOG_URL}}">Все статьи</a></p></section></main>')
# These are raw source drafts. Do not overwrite the reviewed content shipped in the theme.
(root/'wordpress/home-source.generated.html').write_text(raw)
# Normalize snippets and standalone blocks.
for x in items:
 x['content']=x['content'].replace('assets/','{{ASSET_URL}}/') if '{{ASSET_URL}}' not in x['content'] else x['content']
 x['content']=x['content'].replace('https://planodom.ru/politconf','{{HOME_URL}}politconf/')
(root/'wordpress/content-source.generated.json').write_text(json.dumps(items,ensure_ascii=False,indent=2))
print(json.dumps({'products':len(products),'cases':sum(x['type']=='pd_case' for x in items),'pages':sum(x['type']=='page' for x in items),'articles':sum(x['type']=='post' for x in items),'downloaded_images':len(jobs),'total_bytes':sum(p.stat().st_size for p in assets.iterdir())}))
