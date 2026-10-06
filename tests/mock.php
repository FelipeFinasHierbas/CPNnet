<?php
$body=json_decode(file_get_contents('php://input'),true); header('Content-Type: application/json');
$last=end($body['messages']); $isTool=is_array($last['content'])&&($last['content'][0]['type']??'')==='tool_result';
$u=['input_tokens'=>100,'output_tokens'=>50,'cache_creation_input_tokens'=>0,'cache_read_input_tokens'=>13000];
if(!$isTool&&stripos(json_encode($last),'cotiz')!==false){ echo json_encode(['id'=>'m','type'=>'message','role'=>'assistant','model'=>$body['model'],'stop_reason'=>'tool_use','stop_sequence'=>null,'content'=>[['type'=>'tool_use','id'=>'tu1','name'=>'derivar_a_ejecutivo','input'=>['perfil'=>'empresa','nombre'=>'Ana <b>Pérez</b>','empresa'=>'ACME','pais'=>'Chile','contacto'=>'=cmd|calc','necesidad'=>'Renovar firewall','marcas_interes'=>['SonicWall','Sophos Network'],'dimensionamiento'=>'200 usuarios','siguiente_paso'=>'Sizing de firewall','consentimiento'=>true]]],'usage'=>$u]); }
else echo json_encode(['id'=>'m','type'=>'message','role'=>'assistant','model'=>$body['model'],'stop_reason'=>'end_turn','stop_sequence'=>null,'content'=>[['type'=>'text','text'=>'Listo']],'usage'=>$u]);
