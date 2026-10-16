<?php
// ローカル検証用：AiNAマイページの verify-login API の代わり（パスワードは全員 Member1!）
// mock: POST /api/external/ainaglam/verify-login
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
header('Content-Type: application/json');
if ($uri === '/api/external/ainaglam/verify-login') {
  $in = json_decode(file_get_contents('php://input'), true) ?: [];
  file_put_contents(sys_get_temp_dir().'/aina-mock-member-api.log', date('c').' '.$in['email']."\n", FILE_APPEND);
  $users = [
    'creator@example.com' => [1001,'クリエイター太郎',3,2],
    'client@example.com'  => [1002,'依頼者花子',3,2],
    'norole@example.com'  => [1003,'未設定次郎',3,2],
    'quit@example.com'    => [1004,'退会三郎',6,2],
    'free@example.com'    => [1005,'フリー四郎',3,1],
    'linkedadmin@example.com' => [1006,'紐づき管理者',3,1], // マイページはフリープラン
  ];
  $e = $in['email'] ?? '';
  if (!isset($users[$e]) || ($in['password'] ?? '') !== 'Member1!') { echo json_encode(['result'=>'success','authenticated'=>false]); exit; }
  [$id,$name,$st,$plan] = $users[$e];
  echo json_encode(['result'=>'success','authenticated'=>true,'user'=>['id'=>$id,'email'=>$e,'name'=>$name,'status_id'=>$st,'plan_id'=>$plan,'plan_display'=>'メンバープラン']]); exit;
}
http_response_code(404); echo '{}';
