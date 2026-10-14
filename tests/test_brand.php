<?php
// Capa de marca: verifica la identidad de la variante instalada (PLUGIN_DIR) sin tocar la base de conocimiento de CPNnet.
require __DIR__.'/common.php';
$b=cpnnet_asistente_brand(); $variant=is_readable(CPNNET_ASISTENTE_DIR.'brand.json');
if($variant){
  $exp=json_decode(file_get_contents(CPNNET_ASISTENTE_DIR.'brand.json'),true);
  ok('variante: nombre y menú propios ('.$exp['short'].')', $b['name']===$exp['name'] && $b['menu']===$exp['menu']);
  ok('variante: colores sobrescritos', $b['colors']['primary']===$exp['colors']['primary']);
  ok('variante: logo propio y no el de CPNnet', str_ends_with(cpnnet_asistente_logo_url(true),$exp['logo_white']) && is_file(CPNNET_ASISTENTE_DIR.'assets/img/'.$exp['logo']) && !is_file(CPNNET_ASISTENTE_DIR.'assets/img/cpnnet-logo.png'));
  ok('variante: el prompt y la base de conocimiento no son los de CPNnet', str_contains(cpnnet_asistente_rules(),$exp['short']) && !str_contains(cpnnet_asistente_rules(),'CPNnet') && !str_contains(json_encode(cpnnet_asistente_kb()),'Sophos'));
  ok('variante: la empresa no menciona productos de CPNnet como propios', !str_contains(cpnnet_asistente_company(),'Segura'));
  $enum=cpnnet_asistente_tools()[0]['inputSchema']['properties']['perfil']['enum'];
  if(($exp['texture']??'')==='grain') ok('variante: textura y tonos neutros de la marca en el CSS', str_contains(cpnnet_asistente_brand_css(),'feTurbulence') && str_contains(cpnnet_asistente_brand_css(),'color:'.$exp['colors']['ink']));
  ok('variante: perfiles propios en la herramienta', $enum===array_merge(array_keys($exp['profiles']??['partner'=>1,'empresa'=>1]),['desconocido']));
}else{
  ok('base: identidad CPNnet por defecto', $b['name']==='CPNnet Security' && $b['colors']['primary']==='#2b4c8c' && $b['lead_title']==='Nuevo lead desde www.cpnnetsecurity.com');
}
$css=cpnnet_asistente_brand_css(); ok('el CSS de marca define las variables del chat y del panel', str_contains($css,'--c-primary:'.$b['colors']['primary']) && str_contains($css,'--p:'.$b['colors']['primary']));
$GLOBALS['O']['x']=1; ok('la herramienta de derivación nombra a la marca', str_contains(cpnnet_asistente_tools()[0]['description'],$b['short']));
$h=''; ob_start(); try{ $GLOBALS['CAPS']=['cpnnet_asistente_leads'=>1,'manage_options'=>1]; $_GET=[]; cpnnet_asistente_page_panel(); }catch(Throwable $e){} $h=ob_get_clean();
ok('el panel muestra el logo de la marca y su nombre', str_contains($h, $b['logo_white']) && str_contains($h,'alt="'.$b['name'].'"'));
$k=array_key_first(cpnnet_asistente_profiles()); $sum=cpnnet_asistente_lead_summary(['perfil'=>$k,'necesidad'=>'N','marcas_interes'=>['X'],'dimensionamiento'=>'D']);
ok('el resumen del lead lleva el título de la marca', str_starts_with($sum,$b['lead_title']));
ok('el resumen usa la etiqueta del perfil y del dimensionamiento de la marca', str_contains($sum,'Perfil: '.cpnnet_asistente_profiles()[$k]) && str_contains($sum,$b['sizing_label'].': D'));
ok('el resumen omite las líneas sin dato', !str_contains($sum,'Empresa:') && !str_contains($sum,'País:'));
ok('un perfil fuera de la lista no filtra el panel', cpnnet_asistente_leads_query(['perfil'=>'hackeado'])['total']===cpnnet_asistente_leads_query([])['total']);
ok('el widget recibe nombre, chips y logo de la marca', (function() use($b){ $GLOBALS['CAPS']=['manage_options'=>1]; $GLOBALS['ENQ']=[]; $GLOBALS['INLINE']=null; foreach($GLOBALS['HOOKS']['wp_enqueue_scripts'] as $cb) $cb(); $j=$GLOBALS['INLINE']; return str_contains($j, json_encode($b['short'])) && str_contains($j, json_encode($b['chips'][0], JSON_UNESCAPED_UNICODE)) || str_contains($j, trim(json_encode($b['chips'][0]),'"')); })());
echo "-- {$GLOBALS['n']} verificaciones de marca\n"; exit($GLOBALS['fail']??0);
