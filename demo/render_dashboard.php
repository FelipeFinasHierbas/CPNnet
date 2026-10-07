<?php
// Genera demo/panel.html: las pantallas REALES del plugin (panel, leads, fichas, integración, configuración)
// renderizadas con datos de ejemplo y envueltas en una navegación simulada de WordPress. Uso: ver demo/build_dashboard.sh
require __DIR__.'/common.php'; mt_srand(11);
$out = $argv[1] ?? (__DIR__.'/panel.html');
$db=$GLOBALS['wpdb']; $now=time();
$gente=[['Ana Pérez','ACME','Chile','empresa'],['Luis Rojas','Integra TI','Perú','partner'],['Marcos Díaz','Grupo Andes','Colombia','empresa'],['Carla Núñez','SecureNet','Chile','partner'],['Diego Salas','Retail Sur','Chile','empresa'],['Valentina Cruz','Banco Norte','Perú','empresa'],['Pablo Mena','TechPartners','Colombia','partner'],['Sofía Lagos','Clínica Vida','Chile','empresa'],['Andrés Vega','Soluciones Andinas','Perú','partner'],['Camila Soto','Logística Pacífico','Chile','empresa'],['Javier Mora','Colegio San Pedro','Chile','empresa'],['Elena Ríos','NetSolutions','Colombia','partner'],['Tomás Ibarra','Minera del Norte','Chile','empresa'],['Natalia Quispe','Cooperativa Sol','Perú','empresa'],['Ricardo Peña','Cyber Partners','Colombia','partner'],['Isabel Fuentes','Universidad Austral','Chile','empresa'],['Mateo Castro','Seguros Atlas','Colombia','empresa'],['Lucía Herrera','Inova TI','Chile','partner'],['Gonzalo Pino','Aceros del Sur','Chile','empresa']];
$necs=['Equipo de seguridad pequeño, exceso de alertas y sin cobertura fuera de horario','Reemplazar la VPN por acceso granular para trabajo remoto y terceros','Renovar el firewall y conectar sucursales','Descubrir y clasificar información sensible en Microsoft 365','Controlar equipos compartidos en laboratorios','Reducir el backlog de vulnerabilidades y automatizar parches','Proteger cuentas privilegiadas y accesos de proveedores','Validar si los controles actuales detectan ataques reales'];
$marcas=[['Sophos XDR/MDR','Seceon'],['Appgate','Sophos Network'],['SonicWall'],['Kriptos','Safetica'],['Faronics'],['Action1','Vicarius'],['Segura'],['Cymulate']];
$dims=['~120 usuarios, 15 servidores','~300 usuarios remotos, 20 proveedores','4 sedes, 250 usuarios','500 usuarios Microsoft 365','60 equipos de laboratorio','800 endpoints, 40 servidores','12 administradores, 25 proveedores','1.200 endpoints'];
$pasos=['Revisión de madurez de detección y respuesta','Reemplazo de VPN por ZTNA / renovación de firewall','Sizing de firewall o revisión de arquitectura de sucursales','Data Discovery Assessment','Piloto controlado en 10–20 equipos','Assessment de remediación','PAM Assessment o revisión de accesos de terceros','Baseline de validación de controles'];
$st=['nuevo','nuevo','nuevo','nuevo','contactado','contactado','calificado','calificado','ganado','perdido'];
for($d=89;$d>=0;$d--){ $n=mt_rand(2,9)+($d<30?mt_rand(0,5):0)+($d<7?mt_rand(2,6):0); for($c=0;$c<$n;$c++){ $conv="cv$d-$c"; for($m=0,$k=mt_rand(1,6);$m<$k;$m++) $db->insert('wp_cpnnet_asistente_usage',['created_at'=>gmdate('Y-m-d H:i:s',$now-$d*86400-mt_rand(0,40000)),'model'=>'claude-opus-5-5','conv_id'=>$conv,'input_tokens'=>mt_rand(700,1600),'output_tokens'=>mt_rand(120,520),'cache_read_tokens'=>$m?13000:0,'cache_write_tokens'=>$m?0:13000,'cost_usd'=>$m?mt_rand(90,170)/10000:mt_rand(600,720)/10000]); } }
foreach($gente as $i=>[$n,$e,$p,$pf]){ $ago=($i<5?mt_rand(0,6):mt_rand(5,85))*86400+mt_rand(0,60000); $j=mt_rand(0,7); $mk=$marcas[$j];
  $email=strtolower(strtr(explode(' ',$n)[0],'áéíóúñ','aeioun')).'@'.strtolower(preg_replace('/[^a-z]/i','',strtr($e,'áéíóúñ','aeioun'))).'.com';
  $conv=[['role'=>'user','content'=>$pf==='partner'?'Soy integrador. Un cliente necesita: '.lcfirst($necs[$j]):'Somos una empresa. '.$necs[$j]],['role'=>'assistant','content'=>'Entiendo. Para ese caso recomiendo '.implode(' o ',$mk).'. Para orientarte mejor: ¿cuántos usuarios, sedes o equipos tienen?'],['role'=>'user','content'=>$dims[$j].'. Acepto que compartan mis datos con el equipo comercial.']];
  $db->insert('wp_cpnnet_asistente_leads',['uuid'=>cpnnet_asistente_uuid(),'created_at'=>gmdate('Y-m-d H:i:s',$now-$ago),'status'=>$st[mt_rand(0,9)],'perfil'=>$pf,'nombre'=>$n,'empresa'=>$e,'pais'=>$p,'contacto'=>$email,'necesidad'=>$necs[$j],'marcas'=>json_encode($mk,JSON_UNESCAPED_UNICODE),'dimensionamiento'=>$dims[$j],'siguiente_paso'=>$pasos[$j],'pagina'=>mt_rand(0,2)?'/':'/soluciones','exported_at'=>mt_rand(0,3)?gmdate('Y-m-d H:i:s',$now-$ago+90):null,'webhook_status'=>'HTTP 200','notes'=>mt_rand(0,2)?'':'Llamar el lunes por la mañana.','transcript'=>json_encode($conv,JSON_UNESCAPED_UNICODE)]); }
cpnnet_asistente_integration_update(['token_hash'=>hash('sha256','x'),'token_last4'=>'a41f','token_created'=>gmdate('Y-m-d H:i:s'),'webhook_url'=>'https://crm.cpnnetsecurity.com/api/leads','webhook_secret'=>'4f9a1c0be37d52a8']);
$GLOBALS['O']['cpnnet_asistente']['whatsapp']='56912345678'; $GLOBALS['O']['cpnnet_asistente']['monthly_budget']=25;

function cap($fn,$get=[]){ $_GET=$get; ob_start(); $fn(); return ob_get_clean(); }
$sec=[]; $add=function($id,$html) use(&$sec){ $sec[$id]=$html; };
foreach([7,30,90] as $d) $add("panel-$d", cap('cpnnet_asistente_page_panel',['days'=>$d]));
$add('leads', cap('cpnnet_asistente_page_leads'));
$n=(int)$db->get_var("SELECT COUNT(*) FROM wp_cpnnet_asistente_leads");
for($i=1;$i<=$n;$i++) $add("lead-$i", cap('cpnnet_asistente_page_leads',['lead'=>$i]));
$add('integ', cap('cpnnet_asistente_page_integracion'));
foreach(['general','conocimiento','reglas','uso'] as $t) $add("cfg-$t", cap('cpnnet_asistente_admin_page',['tab'=>$t]));
foreach(cpnnet_asistente_kb()['brands'] as $b) $add('cfg-conocimiento-brand-'.$b['id'], cap('cpnnet_asistente_admin_page',['tab'=>'conocimiento','brand'=>$b['id']]));
$add('cfg-conocimiento-brand-nueva', cap('cpnnet_asistente_admin_page',['tab'=>'conocimiento','brand'=>'nueva']));
$csv=cpnnet_asistente_csv_for([]);
$css=file_get_contents(CPNNET_ASISTENTE_DIR.'assets/admin.css');
$b64=fn($f,$m)=>"data:$m;base64,".base64_encode(file_get_contents(CPNNET_ASISTENTE_DIR.'assets/'.$f));
$css=preg_replace_callback('#url\("fonts/(montserrat-latin-\d+-normal\.woff2)"\)#',fn($m)=>'url("'.$b64('fonts/'.$m[1],'font/woff2').'")',$css);
$logo=$b64('img/cpnnet-logo-white.png','image/png');
$body=''; foreach($sec as $id=>$h) $body.='<section class="page" data-page="'.$id.'" hidden>'.$h.'</section>';
$html=<<<HTML
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Demo · Panel comercial CPNnet</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f0f0f1;font:13px/1.5 "Montserrat CPN",Montserrat,-apple-system,"Segoe UI",Roboto,sans-serif;color:#1d2327}
#bar{position:sticky;top:0;z-index:20;background:#1d2327;color:#f0f0f1;display:flex;align-items:center;justify-content:space-between;padding:0 16px;height:34px;font-size:12px}
#bar b{color:#fff}#bar span{opacity:.8}
#layout{display:flex;min-height:calc(100vh - 34px)}
#side{width:200px;background:#1d2327;flex:none;padding-top:8px}
#side .grp{color:#a7aaad;font-size:11px;text-transform:uppercase;letter-spacing:.06em;padding:12px 14px 6px}
#side a{display:block;padding:9px 14px;color:#c3c4c7;text-decoration:none;font-size:13.5px;border-left:3px solid transparent}
#side a:hover{color:#72aee6}#side a.on{background:#2c3338;color:#fff;border-left-color:#4f9bd6}
#main{flex:1;min-width:0;padding:6px 22px 40px}
.wrap{margin:0}.button{display:inline-block;border:1px solid #2271b1;border-radius:3px;padding:3px 11px;color:#2271b1;background:#f6f7f7;text-decoration:none;font-size:13px;cursor:pointer;line-height:2}
.button-primary{background:#2271b1;color:#fff}.button-small{padding:0 8px;line-height:2}.button-link-delete{color:#b32d2e;border-color:#b32d2e}
input[type=text],input[type=search],input[type=date],input[type=url],input[type=number],input[type=password],select,textarea{border:1px solid #8c8f94;border-radius:4px;padding:3px 8px;min-height:30px;font:inherit;background:#fff;max-width:100%}
.large-text{width:100%}.regular-text{width:25em}.code{font-family:ui-monospace,Menlo,Consolas,monospace}.description{color:#646970;font-size:13px;margin:4px 0}
.form-table{width:100%;border-collapse:collapse}.form-table th{text-align:left;width:210px;padding:14px 10px 14px 0;vertical-align:top}.form-table td{padding:12px 0}
.nav-tab-wrapper{border-bottom:1px solid #c3c4c7;margin:0 0 14px;padding:0}.nav-tab{display:inline-block;padding:7px 14px;border:1px solid #c3c4c7;border-bottom:0;background:#e5e5e5;color:#50575e;text-decoration:none;margin:0 4px -1px 0;font-weight:600}
.nav-tab-active{background:#f0f0f1;color:#000}.widefat{width:100%;border-collapse:collapse;background:#fff;border:1px solid #c3c4c7}.widefat th,.widefat td{padding:9px 10px;text-align:left;border-bottom:1px solid #eee}
.striped tbody tr:nth-child(odd){background:#f6f7f7}.notice{display:none}h2,h3{font-weight:600}
#toast{position:fixed;left:50%;bottom:22px;transform:translateX(-50%);background:#1d2327;color:#fff;padding:11px 18px;border-radius:8px;font-size:13.5px;box-shadow:0 8px 24px rgba(0,0,0,.3);opacity:0;pointer-events:none;transition:.2s;z-index:50;max-width:90vw}
#toast.on{opacity:1}
@media(max-width:820px){#side{display:none}#main{padding:6px 12px}}
</style><style>$css</style></head><body>
<div id="bar"><span><b>DEMO</b> · Panel comercial del Asistente CPNnet · datos de ejemplo</span><span><a href="index.html" style="color:#72aee6;text-decoration:none;margin-right:14px;font-weight:600">← Todas las demos</a>Navega, filtra y abre fichas. Nada de lo que hagas se guarda.</span></div>
<div id="layout"><nav id="side"><div class="grp">Asistente CPNnet</div>
<a href="#panel-30" data-nav="panel">Panel</a><a href="#leads" data-nav="leads">Leads</a><a href="#integ" data-nav="integ">Integración CRM</a><a href="#cfg-general" data-nav="cfg">Configuración</a></nav>
<main id="main">$body</main></div><div id="toast"></div>
<script>
var CSV=%CSV%,LOGO=%LOGO%;[].forEach.call(document.querySelectorAll('img.cpn-logo'),function(i){i.src=LOGO});window.confirm=function(){return true};
var pages=[].slice.call(document.querySelectorAll('.page'));
function show(id){var t=pages.filter(function(p){return p.dataset.page===id})[0];if(!t){toast('En el sitio real aquí se abre esta pantalla.');return}
 pages.forEach(function(p){p.hidden=p!==t});var g=id.split('-')[0];
 [].forEach.call(document.querySelectorAll('#side a'),function(a){a.classList.toggle('on',a.dataset.nav===g)});
 history.replaceState(null,'','#'+id);window.scrollTo(0,0)}
function route(u){var q=new URL(u,location.href).searchParams,p=q.get('page')||'';
 if(q.get('action')==='cpnnet_asistente_leads_csv'){var b=new Blob([CSV],{type:'text/csv;charset=utf-8'}),a=document.createElement('a');a.href=URL.createObjectURL(b);a.download='leads-cpnnet-demo.csv';a.click();toast('Se descargó el CSV de ejemplo.');return}
 if(p==='cpnnet-asistente')return show('panel-'+(q.get('days')||30));
 if(p==='cpnnet-asistente-leads')return show(q.get('lead')?'lead-'+q.get('lead'):'leads');
 if(p==='cpnnet-asistente-integracion')return show('integ');
 if(p==='cpnnet-asistente-config'){var id='cfg-'+(q.get('tab')||'general');if(q.get('brand'))id+='-brand-'+q.get('brand');return show(id)}
 toast('En el sitio real aquí se abre esta pantalla.')}
document.addEventListener('click',function(e){var a=e.target.closest('a');if(!a)return;var h=a.getAttribute('href')||'';
 if(h.charAt(0)==='#'){e.preventDefault();show(h.slice(1));return}if(/^https?:/.test(h)||h.indexOf('admin')>-1){e.preventDefault();route(h)}});
document.addEventListener('submit',function(e){e.preventDefault();var f=e.target;
 if(f.classList.contains('cpn-filters')){filter(f);return}
 toast('Demo: en el sitio real esto se guardaría.')},true);
function filter(f){var q=f.q.value.toLowerCase(),s=f.status.value,p=f.perfil.value,n=0;
 [].forEach.call(f.closest('.page').querySelectorAll('.cpn-table tbody tr'),function(tr){var c=tr.children,
  ok=(!q||tr.textContent.toLowerCase().indexOf(q)>-1)&&(!s||c[5].textContent.trim().toLowerCase()===s)&&(!p||c[2].textContent.trim().toLowerCase()===p);tr.style.display=ok?'':'none';if(ok)n++});
 var h=f.closest('.page').querySelector('.cpn-card-head h3');if(h)h.textContent=n+' leads'}
var tm;function toast(m){var t=document.getElementById('toast');t.textContent=m;t.classList.add('on');clearTimeout(tm);tm=setTimeout(function(){t.classList.remove('on')},2600)}
window.addEventListener('hashchange',function(){show((location.hash||'#panel-30').slice(1))});
show((location.hash||'#panel-30').slice(1));
</script></body></html>
HTML;
$html=str_replace('%CSV%',json_encode($csv,JSON_UNESCAPED_UNICODE),$html);
$html=str_replace('ASSET/assets/img/cpnnet-logo-white.png','data:image/gif;base64,R0lGODlhAQABAAAAACw=',$html); // el logo real se asigna una sola vez desde JS
$html=str_replace('%LOGO%',json_encode($logo),$html);
file_put_contents($out,$html);
echo "panel.html: ".round(strlen($html)/1024)." KB, ".count($sec)." pantallas\n";
