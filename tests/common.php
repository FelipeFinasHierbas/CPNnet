<?php
// Simulación mínima de WordPress + wpdb sobre SQLite, para probar el plugin con SQL real.
error_reporting(E_ALL); set_error_handler(function($n,$s,$f,$l){echo "PHP WARNING: $s (".basename($f).":$l)\n"; return true;});
define('ABSPATH','/x/'); const ARRAY_A='ARRAY_A'; const HOUR_IN_SECONDS=3600; const DAY_IN_SECONDS=86400; const MINUTE_IN_SECONDS=60;
$GLOBALS['O']=[]; $GLOBALS['T']=[]; $GLOBALS['CAPS']=['cpnnet_asistente_leads'=>true,'manage_options'=>true]; $GLOBALS['HOOKS']=[];
function get_option($k,$d=false){return $GLOBALS['O'][$k]??$d;} function update_option($k,$v,$a=null){$GLOBALS['O'][$k]=$v;return true;} function delete_option($k){unset($GLOBALS['O'][$k]);}
function wp_parse_args($a,$d){return array_merge($d,(array)$a);} function get_transient($k){return $GLOBALS['T'][$k]??false;} function set_transient($k,$v,$t=0){$GLOBALS['T'][$k]=$v;} function delete_transient($k){unset($GLOBALS['T'][$k]);}
function wp_unslash($v){return $v;} function wp_strip_all_tags($s){return strip_tags($s);} function wp_json_encode($v,$f=0){return json_encode($v,$f);} function home_url(){return 'https://www.cpnnetsecurity.com';}
function wp_parse_url($u,$c=-1){return parse_url($u,$c);} function add_action($h,$cb,...$a){$GLOBALS['HOOKS'][$h][]=$cb;} function add_filter($h,$cb,...$a){$GLOBALS['HOOKS'][$h][]=$cb;} function register_rest_route(...$a){}
function plugin_dir_path($f){return dirname($f).'/';} function plugin_dir_url($f){return '/';}
function current_time($t,$gmt=false){return $t==='timestamp'?time():gmdate('Y-m-d H:i:s');} function wp_date($f,$ts=null){return gmdate($f,$ts??time());}
function sanitize_text_field($s){return trim(strip_tags((string)$s));} function sanitize_textarea_field($s){return trim(strip_tags((string)$s));} function sanitize_title($s){return trim(preg_replace('/[^a-z0-9]+/','-',strtolower($s)),'-');}
function esc_html($s){return htmlspecialchars((string)$s,ENT_QUOTES);} function esc_attr($s){return htmlspecialchars((string)$s,ENT_QUOTES);} function esc_url($s){return htmlspecialchars((string)$s);} function esc_textarea($s){return htmlspecialchars((string)$s);} function esc_url_raw($s){return filter_var($s,FILTER_SANITIZE_URL);}
function checked($a,$b=true,$e=true){$r=$a==$b?'checked':'';if($e)echo $r;return $r;} function selected($a,$b=true,$e=true){$r=$a==$b?' selected':'';if($e)echo $r;return $r;}
function admin_url($p=''){return "https://x/wp-admin/$p";} function rest_url($p=''){return "https://www.cpnnetsecurity.com/wp-json/$p";} function add_query_arg($a,$u=''){return $u.(str_contains($u,'?')?'&':'?').http_build_query($a);}
function wp_nonce_field($a){echo '<input type="hidden" name="_wpnonce" value="n">';} function wp_nonce_url($u,$a){return $u.'&_wpnonce=n';} function number_format_i18n($n){return number_format($n);} function submit_button($t='',$ty='',$n='',$w=true){echo '<input type="submit" value="'.esc_attr($t).'">';}
function current_user_can($c){return !empty($GLOBALS['CAPS'][$c]);} function get_current_user_id(){return 1;} function is_email($e){return (bool)filter_var($e,FILTER_VALIDATE_EMAIL);} function get_role($r){return null;} function add_role(...$a){}
function settings_fields($g){} function wp_die($m,$c=0){throw new Exception("wp_die:$m");} function check_admin_referer($a){} function nocache_headers(){} function wp_safe_redirect($u){$GLOBALS['REDIR']=$u;}
class WP_Error{public function __construct(public $code='',public $msg='',public $data=[]){}} function is_wp_error($x){return $x instanceof WP_Error;}
class WP_REST_Response{public function __construct(public $data=null,public $status=200){}}
class WP_REST_Request implements ArrayAccess{public function __construct(private array $p=[],private array $h=[],private array $q=[]){} public function get_json_params(){return $this->p;} public function get_param($k){return $this->q[$k]??$this->p[$k]??null;} public function get_header($k){return $this->h[strtolower($k)]??null;} public function offsetGet(mixed $k):mixed{return $this->q[$k]??null;} public function offsetExists(mixed $k):bool{return isset($this->q[$k]);} public function offsetSet(mixed $k,mixed $v):void{} public function offsetUnset(mixed $k):void{}}
function wp_remote_post($url,$args){ $ctx=stream_context_create(['http'=>['method'=>'POST','header'=>implode("\r\n",array_map(fn($k,$v)=>"$k: $v",array_keys($args['headers']),$args['headers'])),'content'=>$args['body'],'timeout'=>5,'ignore_errors'=>true]]);
  $r=@file_get_contents($url,false,$ctx); if($r===false) return new WP_Error('http','conexión rechazada'); preg_match('#HTTP/\S+ (\d+)#',$http_response_header[0]??'',$m); return ['response'=>['code'=>(int)($m[1]??0)],'body'=>$r]; }
function wp_remote_retrieve_response_code($r){return $r['response']['code']??0;}
class WPDB{ public $prefix='wp_'; public $insert_id=0; public PDO $pdo;
  function __construct(){ $this->pdo=new PDO('sqlite::memory:'); $this->pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    $this->pdo->exec("CREATE TABLE wp_cpnnet_asistente_leads (id INTEGER PRIMARY KEY AUTOINCREMENT, uuid TEXT DEFAULT '', created_at TEXT NOT NULL, updated_at TEXT, status TEXT DEFAULT 'nuevo', perfil TEXT DEFAULT '', nombre TEXT DEFAULT '', empresa TEXT DEFAULT '', pais TEXT DEFAULT '', contacto TEXT DEFAULT '', necesidad TEXT, marcas TEXT, dimensionamiento TEXT, siguiente_paso TEXT, pagina TEXT DEFAULT '', conv_id TEXT DEFAULT '', transcript TEXT, notes TEXT, exported_at TEXT, webhook_status TEXT DEFAULT '', resumen TEXT)");
    $this->pdo->exec("CREATE TABLE wp_cpnnet_asistente_usage (id INTEGER PRIMARY KEY AUTOINCREMENT, created_at TEXT NOT NULL, model TEXT DEFAULT '', conv_id TEXT DEFAULT '', input_tokens INT DEFAULT 0, output_tokens INT DEFAULT 0, cache_read_tokens INT DEFAULT 0, cache_write_tokens INT DEFAULT 0, cost_usd REAL DEFAULT 0)"); }
  function prepare($q,...$a){ $i=0; return preg_replace_callback('/%[sdf]/',function($m)use(&$i,$a){$v=$a[$i++]; return $m[0]==='%s'?$this->pdo->quote((string)$v):($m[0]==='%d'?(string)(int)$v:(string)(float)$v);},$q);}
  function esc_like($s){return addcslashes($s,'_%\\');} function get_charset_collate(){return '';}
  function insert($t,$d){ $c=array_keys($d); $st=$this->pdo->prepare("INSERT INTO $t (".implode(',',$c).") VALUES (".implode(',',array_fill(0,count($c),'?')).")"); $st->execute(array_values($d)); $this->insert_id=(int)$this->pdo->lastInsertId(); return 1;}
  function update($t,$d,$w){ $set=implode(',',array_map(fn($k)=>"$k=?",array_keys($d))); $wh=implode(' AND ',array_map(fn($k)=>"$k=?",array_keys($w))); $st=$this->pdo->prepare("UPDATE $t SET $set WHERE $wh"); $st->execute(array_merge(array_values($d),array_values($w))); return $st->rowCount();}
  function get_var($q){ $r=$this->pdo->query($q)->fetchColumn(); return $r===false?null:$r;} function get_row($q,$o=null){ $r=$this->pdo->query($q)->fetch(PDO::FETCH_ASSOC); return $r?:null;} function get_results($q,$o=null){ return $this->pdo->query($q)->fetchAll(PDO::FETCH_ASSOC);} }
$wpdb=new WPDB;
define('CPNNET_ASISTENTE_DIR',__DIR__.'/cpnnet-asistente/'); const CPNNET_ASISTENTE_OPTION='cpnnet_asistente';
require CPNNET_ASISTENTE_DIR.'vendor/autoload.php';
foreach(['settings','leads','usage','kb','prompt','api','integration','admin','dashboard'] as $f) require CPNNET_ASISTENTE_DIR."includes/$f.php";
define('CPNNET_ASISTENTE_VERSION','t');
function ok($l,$c){echo ($c?'OK   ':'FAIL ').$l."\n"; if(!$c) $GLOBALS['fail']=1;}
$GLOBALS['O']['cpnnet_asistente']=['enabled'=>1,'api_key'=>'sk-test','model'=>'claude-opus-5-5','effort'=>'low','whatsapp'=>'56911112222','hourly_limit'=>99,'daily_limit'=>999,'monthly_budget'=>0,'save_transcript'=>1,'welcome'=>'x'];
