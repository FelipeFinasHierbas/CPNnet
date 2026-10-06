<?php
require __DIR__.'/common.php';
function chat($msgs,$extra=[]){ $_SERVER['HTTP_ORIGIN']='https://www.cpnnetsecurity.com'; $_SERVER['REMOTE_ADDR']='1.2.3.4'; return cpnnet_asistente_handle_chat(new WP_REST_Request(array_merge(['messages'=>$msgs],$extra))); }
// --- webhook configurado ---
cpnnet_asistente_integration_update(['webhook_url'=>'http://127.0.0.1:8097/hook.php','webhook_secret'=>'s3cret']);
// --- 1. flujo completo: chat -> lead ---
$r=chat([['role'=>'user','content'=>'Hola'],['role'=>'assistant','content'=>'¿partner o empresa?'],['role'=>'user','content'=>'Quiero una cotización, acepto compartir datos']],['conversation_id'=>'conv-abc-12345','page'=>'https://www.cpnnetsecurity.com/contacto?x=1']);
ok('el chat responde 200 y entrega botón de WhatsApp', $r->status===200 && str_contains((string)$r->data['whatsapp_url'],'wa.me/56911112222'));
$lead=cpnnet_asistente_lead_by('id',1);
ok('el lead queda en el repositorio con uuid, estado y contexto', $lead && strlen($lead['uuid'])===36 && $lead['status']==='nuevo' && $lead['conv_id']==='conv-abc-12345' && $lead['pagina']==='/contacto' && $lead['pais']==='Chile');
$tr=json_decode($lead['transcript'],true); ok('guarda la conversación (3 mensajes) hasta la derivación', count($tr)===3 && $tr[2]['role']==='user');
$u=$GLOBALS['wpdb']->get_row("SELECT * FROM wp_cpnnet_asistente_usage"); ok('el uso queda ligado a la conversación', $u['conv_id']==='conv-abc-12345');
$log=json_decode(trim(file_get_contents(__DIR__.'/hook.log')),true); $body=$log['body'];
ok('el webhook llegó con evento lead.created', json_decode($body,true)['event']==='lead.created' && json_decode($body,true)['data']['id']===$lead['uuid']);
ok('el webhook va firmado (HMAC-SHA256 válido)', ($log['headers']['x-cpnnet-signature']??'')==='sha256='.hash_hmac('sha256',$body,'s3cret'));
$lead=cpnnet_asistente_lead_by('id',1); ok('webhook ok => lead marcado como exportado y con estado HTTP 200', $lead['exported_at']!==null && $lead['webhook_status']==='HTTP 200');
// transcript desactivado
$GLOBALS['O']['cpnnet_asistente_integration']['webhook_url']=''; $GLOBALS['O']['cpnnet_asistente']['save_transcript']=0; chat([['role'=>'user','content'=>'otra cotización por favor']]); ok('sin permiso de transcripción no se guarda la conversación', cpnnet_asistente_lead_by('id',2)['transcript']===null);
$GLOBALS['O']['cpnnet_asistente']['save_transcript']=1;
// --- 2. API ---
function api($path,$q=[],$tok=null,$body=null){ $h=$tok?['authorization'=>"Bearer $tok"]:[]; $req=new WP_REST_Request($body??[],$h,$q); $a=cpnnet_asistente_api_auth($req); if(is_wp_error($a)) return [$a->data['status'],$a->msg];
  return match($path){'list'=>(function()use($req){$r=cpnnet_asistente_api_leads($req);return [$r->status,$r->data];})(),'one'=>(function()use($req){$r=cpnnet_asistente_api_lead($req);return is_wp_error($r)?[$r->data['status'],$r->msg]:[$r->status,$r->data];})(),'mark'=>(function()use($req){$r=cpnnet_asistente_api_mark_exported($req);return is_wp_error($r)?[$r->data['status'],$r->msg]:[$r->status,$r->data];})()}; }
[$c]=api('list'); ok('sin token configurado la API responde 403', $c===403);
$token=cpnnet_asistente_token_generate(); ok('el token se guarda solo como hash', !str_contains(json_encode($GLOBALS['O']),$token) && str_starts_with($token,'cpn_'));
[$c]=api('list',[],'malo'); ok('token inválido => 401', $c===401);
[$c,$d]=api('list',[],$token); ok('token válido => lista con total', $c===200 && $d['total']===2 && count($d['data'])===2);
[$c,$d]=api('list',['exported'=>'0'],$token); ok('filtro exported=0 devuelve el pendiente', $d['total']===1 && $d['data'][0]['id']===cpnnet_asistente_lead_by('id',2)['uuid']);
[$c,$d]=api('list',['include'=>'conversation','limit'=>1],$token); ok('include=conversation y limit', count($d['data'])===1 && isset($d['data'][0]['conversacion']));
[$c,$d]=api('list',[],$token); ok('payload sin datos internos y con ISO-8601', !isset($d['data'][0]['transcript']) && str_ends_with($d['data'][0]['created_at'],'Z') && is_array($d['data'][0]['marcas_interes']));
$uuid=$lead['uuid']; [$c,$d]=api('one',['id'=>$uuid],$token); ok('GET /leads/{id}', $c===200 && $d['data']['nombre']==='Ana <b>Pérez</b>' && isset($d['data']['conversacion']));
[$c]=api('one',['id'=>'00000000-0000-0000-0000-000000000000'],$token); ok('lead inexistente => 404', $c===404);
$u2=cpnnet_asistente_lead_by('id',2)['uuid']; [$c,$d]=api('mark',[],$token,['ids'=>[$u2]]); ok('mark-exported marca el lead', $c===200 && $d['marked']===1 && api('list',['exported'=>'0'],$token)[1]['total']===0);
[$c]=api('mark',[],$token,['ids'=>[]]); ok('mark-exported sin ids => 400', $c===400);
for($i=0;$i<20;$i++) api('list',[],'x'); [$c]=api('list',[],$token); ok('tras 20 intentos fallidos se frena (429), incluso con token válido', $c===429);
$GLOBALS['T']=[]; cpnnet_asistente_token_revoke(); [$c]=api('list',[],$token); ok('token revocado => 403', $c===403);
// --- 3. filtros y CSV ---
$GLOBALS['wpdb']->insert('wp_cpnnet_asistente_leads',['uuid'=>cpnnet_asistente_uuid(),'created_at'=>'2020-01-05 10:00:00','status'=>'ganado','perfil'=>'partner','nombre'=>'Luis, "El Jefe"','empresa'=>'Integra','pais'=>'Perú','contacto'=>'luis@x.pe','marcas'=>'["Appgate"]']);
ok('filtro por estado', cpnnet_asistente_leads_query(['status'=>'ganado'])['total']===1);
ok('filtro por perfil + texto', cpnnet_asistente_leads_query(['perfil'=>'partner','q'=>'appgate'])['total']===1);
ok('filtro por fechas', cpnnet_asistente_leads_query(['from'=>'2020-01-01','to'=>'2020-01-31'])['total']===1 && cpnnet_asistente_leads_query(['from'=>'2021-01-01'])['total']===2);
$csv=cpnnet_asistente_csv_for([]);
ok('CSV con BOM y encabezados', str_starts_with($csv,"\xEF\xBB\xBFid,created_at,status") );
ok('CSV neutraliza fórmulas (=cmd…) ', str_contains($csv,"\"'=cmd|calc\"") && !str_contains($csv,',"=cmd'));
ok('CSV escapa comillas y comas', str_contains($csv,'"Luis, ""El Jefe"""'));
ok('estado inválido no se guarda', (function(){cpnnet_asistente_lead_update(1,['status'=>'hackeado']);return cpnnet_asistente_lead_by('id',1)['status']==='nuevo';})());
// --- 4. estadísticas ---
$db=$GLOBALS['wpdb']; $now=time();
foreach([[0,'c1'],[0,'c1'],[0,'c2'],[1,'c3'],[3,'c4'],[3,'c4'],[40,'c9']] as [$ago,$cv]) $db->insert('wp_cpnnet_asistente_usage',['created_at'=>gmdate('Y-m-d H:i:s',$now-$ago*86400),'model'=>'m','conv_id'=>$cv,'cost_usd'=>0.01]);
$s=cpnnet_asistente_stats(30); ok('estadísticas: 4 conversaciones sembradas + 2 del chat = 6 distintas', $s['conversations']===6 && $s['leads']===2 && $s['conversion']==33.3); echo "     (conversaciones={$s['conversations']}, mensajes={$s['messages']}, leads={$s['leads']}, conv%={$s['conversion']})\n";
ok('series con un valor por día', count($s['labels'])===30 && count($s['conv_series'])===30 && count($s['lead_series'])===30);
ok('el uso fuera del periodo (40 días) no se cuenta: 6 sembrados + 2 del chat = 8', $s['messages']===8);
ok('distribuciones: marcas y países', ($s['marcas']['SonicWall']??0)===2 && ($s['paises']['Chile']??0)===2);
// --- 5. pantallas ---
function page($fn){ ob_start(); $fn(); return ob_get_clean(); }
$_GET=[]; $h=page('cpnnet_asistente_page_panel'); ok('panel se renderiza (KPIs, gráfico, leads)', str_contains($h,'cpn-kpis') && str_contains($h,'<svg class="cpn-chart"') && str_contains($h,'Últimos leads'));
ok('panel escapa HTML del visitante (XSS)', !str_contains($h,'<b>Pérez</b>') && str_contains($h,'&lt;b&gt;P'));
$h=page('cpnnet_asistente_page_leads'); ok('lista de leads con filtros y botón CSV', str_contains($h,'Exportar CSV') && str_contains($h,'cpn-table'));
$_GET=['lead'=>'1']; $h=page('cpnnet_asistente_page_leads'); ok('ficha del lead: datos, seguimiento, CRM y conversación', str_contains($h,'Seguimiento') && str_contains($h,'Envío al CRM') && str_contains($h,'cpn-bubble-user') && !str_contains($h,'<b>Pérez</b>'));
$_GET=['lead'=>'999']; $h=page('cpnnet_asistente_page_leads'); ok('lead inexistente muestra aviso', str_contains($h,'No existe este lead'));
$_GET=[]; $h=page('cpnnet_asistente_page_integracion'); ok('pantalla de integración', str_contains($h,'Webhook') && str_contains($h,'curl -H'));
// --- 6. acciones y permisos ---
$GLOBALS['O']['cpnnet_asistente_integration']['webhook_url']=''; $_POST=['lead_id'=>'1','status'=>'contactado','notes'=>'Llamar mañana'];
foreach($GLOBALS['HOOKS']['admin_post_cpnnet_asistente_lead_save'] as $cb) $cb(); $l=cpnnet_asistente_lead_by('id',1); ok('guardar seguimiento (estado y notas)', $l['status']==='contactado' && $l['notes']==='Llamar mañana');
$caps=array_reduce($GLOBALS['HOOKS']['user_has_cap'],fn($c,$f)=>$f($c),['manage_options'=>true]); ok('los administradores obtienen la capacidad comercial', !empty($caps['cpnnet_asistente_leads']));
$GLOBALS['CAPS']=['read'=>true]; try{ foreach($GLOBALS['HOOKS']['admin_post_cpnnet_asistente_leads_csv'] as $cb) $cb(); ok('sin permiso no exporta',false);}catch(Exception $e){ ok('sin permiso no exporta el CSV', str_contains($e->getMessage(),'wp_die')); }
exit($GLOBALS['fail']??0);
