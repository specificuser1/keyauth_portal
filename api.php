<?php require 'core.php'; header('Content-Type:application/json'); $pdo=getPDO();
$input=$_GET?:$_POST; $appSec=$input['secret']??''; $keyStr=$input['key']??''; $hwid=$input['hwid']??''; $clientIp=$input['ip']??$_SERVER['REMOTE_ADDR'];
if($pdo->query("SELECT value FROM settings WHERE key='maintenance_mode'")->fetchColumn()=='1') { http_response_code(503); echo json_encode(['success'=>false,'message'=>'System under maintenance']); exit; }

$stmt=$pdo->prepare("SELECT k.*,a.secret_key,a.expires_at as app_expires FROM keys k JOIN applications a ON k.app_id=a.id WHERE k.key_string=?");
$stmt->execute([$keyStr]); $key=$stmt->fetch();
if(!$key||$key['secret_key']!==$appSec){ http_response_code(401); echo json_encode(['success'=>false,'message'=>'Invalid key or app secret']); sendDiscordEmbed('validation_fail','❌ Invalid Key',"Key: `$keyStr`\nIP: `$clientIp`",[],0xef4444); exit; }
if($key['app_expires']&&strtotime($key['app_expires'])<time()){ http_response_code(403); echo json_encode(['success'=>false,'message'=>'Application expired']); exit; }
if($key['status']==='blocked'||$key['status']==='paused'){ http_response_code(403); echo json_encode(['success'=>false,'message'=>"Key is {$key['status']}"]); exit; }
if($key['expires_at']&&strtotime($key['expires_at'])<time()){ http_response_code(403); echo json_encode(['success'=>false,'message'=>'Key expired']); exit; }
if($key['max_uses']>0&&$key['usage_count']>=$key['max_uses']){ http_response_code(429); echo json_encode(['success'=>false,'message'=>'Max usage limit reached']); exit; }
$rl=$pdo->query("SELECT value FROM settings WHERE key='api_rate_limit'")->fetchColumn();
$last=$pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE type='api_rate' AND created_at > datetime('now', '-1 minute')");
$last->execute([$key['id']]); if($last->fetchColumn()>$rl){ http_response_code(429); echo json_encode(['success'=>false,'message'=>'Rate limit exceeded']); exit; }
if($key['cooldown']>0&&$key['last_used']){ $diff=time()-strtotime($key['last_used']); if($diff<$key['cooldown']){ http_response_code(429); echo json_encode(['success'=>false,'message'=>"Cooldown active. Wait ".($key['cooldown']-$diff)."s"]); exit; } }
if($key['hwid_locked']&&$key['hwid_value']&&$key['hwid_value']!==$hwid){ http_response_code(403); echo json_encode(['success'=>false,'message'=>'HWID mismatch']); exit; }
if($key['ip_locked']&&$key['ip_value']&&$key['ip_value']!==$clientIp){ http_response_code(403); echo json_encode(['success'=>false,'message'=>'IP mismatch']); exit; }
if(!$key['hwid_value']&&$key['hwid_locked']) $pdo->prepare("UPDATE keys SET hwid_value=? WHERE id=?")->execute([$hwid,$key['id']]);
if(!$key['ip_value']&&$key['ip_locked']) $pdo->prepare("UPDATE keys SET ip_value=? WHERE id=?")->execute([$clientIp,$key['id']]);
$pdo->prepare("UPDATE keys SET usage_count=usage_count+1, last_used=CURRENT_TIMESTAMP WHERE id=?")->execute([$key['id']]);
sendDiscordEmbed('validation_success','✅ Key Validated',"App: `{$key['app_id']}`\nKey: `{$keyStr}`\nIP: `$clientIp`",[["name"=>"Remaining Uses","value"=>$key['max_uses']>0?$key['max_uses']-($key['usage_count']+1):'∞', "inline"=>true],["name"=>"Label","value"=>$key['label']?:'None', "inline"=>true]],0x22c55e);
echo json_encode(['success'=>true,'message'=>'Authenticated','label'=>$key['label'],'variables'=>json_decode($key['variables'],true),'remaining_uses'=>$key['max_uses']>0?$key['max_uses']-($key['usage_count']+1):'∞','hwid_locked'=>(bool)$key['hwid_locked'],'ip_locked'=>(bool)$key['ip_locked']]);
?>
