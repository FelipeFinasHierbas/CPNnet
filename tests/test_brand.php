<?php
// Capa de marca: verifica la identidad de la variante instalada (PLUGIN_DIR) sin tocar la base de conocimiento de CPNnet.
require __DIR__.'/common.php';
$b=cpnnet_asistente_brand(); $variant=is_readable(CPNNET_ASISTENTE_DIR.'brand.json');
if($variant){
  ok('variante: nombre y menú propios', $b['name']==='The Forest Software Lab' && $b['menu']==='Asistente The Forest');
  ok('variante: colores sobrescritos y los no indicados se conservan', $b['colors']['primary']==='#1f5f3f' && $b['colors']['tint']==='#bfe3cd');
  ok('variante: logo propio y no el de CPNnet', str_ends_with(cpnnet_asistente_logo_url(true),'forest-logo-white.png') && is_file(CPNNET_ASISTENTE_DIR.'assets/img/forest-logo.png') && !is_file(CPNNET_ASISTENTE_DIR.'assets/img/cpnnet-logo.png'));
  ok('variante: el prompt y la base de conocimiento son los de The Forest', str_contains(cpnnet_asistente_rules(),'The Forest') && !str_contains(cpnnet_asistente_rules(),'CPNnet') && str_contains(json_encode(cpnnet_asistente_kb()),'ForestBot') && !str_contains(json_encode(cpnnet_asistente_kb()),'Sophos'));
  ok('variante: la empresa no menciona productos de CPNnet como propios', !str_contains(cpnnet_asistente_company(),'Segura'));
}else{
  ok('base: identidad CPNnet por defecto', $b['name']==='CPNnet Security' && $b['colors']['primary']==='#2b4c8c' && $b['lead_title']==='Nuevo lead desde www.cpnnetsecurity.com');
}
$css=cpnnet_asistente_brand_css(); ok('el CSS de marca define las variables del chat y del panel', str_contains($css,'--c-primary:'.$b['colors']['primary']) && str_contains($css,'--p:'.$b['colors']['primary']));
$GLOBALS['O']['x']=1; ok('la herramienta de derivación nombra a la marca', str_contains(cpnnet_asistente_tools()[0]['description'],$b['short']));
$h=''; ob_start(); try{ $GLOBALS['CAPS']=['cpnnet_asistente_leads'=>1,'manage_options'=>1]; $_GET=[]; cpnnet_asistente_page_panel(); }catch(Throwable $e){} $h=ob_get_clean();
ok('el panel muestra el logo de la marca y su nombre', str_contains($h, $variant?'forest-logo-white.png':'cpnnet-logo-white.png') && str_contains($h,'alt="'.$b['name'].'"'));
$sum=cpnnet_asistente_lead_summary(['perfil'=>'empresa','marcas_interes'=>['X']]); ok('el resumen del lead lleva el título de la marca', str_starts_with($sum,$b['lead_title']));
ok('el widget recibe nombre, chips y logo de la marca', (function() use($b){ $GLOBALS['CAPS']=['manage_options'=>1]; $GLOBALS['ENQ']=[]; $GLOBALS['INLINE']=null; foreach($GLOBALS['HOOKS']['wp_enqueue_scripts'] as $cb) $cb(); $j=$GLOBALS['INLINE']; return str_contains($j, json_encode($b['short'])) && str_contains($j, json_encode($b['chips'][0], JSON_UNESCAPED_UNICODE)) || str_contains($j, trim(json_encode($b['chips'][0]),'"')); })());
echo "-- {$GLOBALS['n']} verificaciones de marca\n"; exit($GLOBALS['fail']??0);
