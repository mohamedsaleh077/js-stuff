<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Depth, User-Agent, X-File-Size, X-Requested-With, If-Modified-Since, X-File-Name, Cache-Control");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
header('Content-Type: application/json');

$users = [
  ['id'=>1,'name'=>'Alice','email'=>'alice@example.com'],
  ['id'=>2,'name'=>'Bob','email'=>'bob@example.com']
];

$path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

if ($path === '/api/users') {
  echo json_encode($users);
} elseif (preg_match('#^/api/users/(\d+)$#', $path, $m)) {
  $id = (int)$m[1];
  foreach ($users as $u) if ($u['id']===$id) { echo json_encode($u); exit; }
  http_response_code(404); echo json_encode(['error'=>'User not found']);
} else {
  http_response_code(404); echo json_encode(['error'=>'Endpoint not found']);
}
