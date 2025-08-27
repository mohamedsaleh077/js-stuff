<?php
// admin_panel.php
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);
session_start();
if(!isset($_SESSION['admin_id'])){ header('Location: admin_login.php'); exit; }

$db = new mysqli('sql111.infinityfree.com','if0_38828136','QTutOaOI2x','if0_38828136_miku');
if($db->connect_error){ die('DB error: '.$db->connect_error); }

if(empty($_SESSION['admin_csrf'])){ $_SESSION['admin_csrf'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['admin_csrf'];

$upload_dir = __DIR__ . '/uploads/';

function del_file_rel($rel){
  if(!$rel) return;
  if(strpos($rel,'uploads/')!==0) return;
  $p = __DIR__ . '/' . $rel;
  if(is_file($p)) @unlink($p);
}
function del_thumb_for($rel){
  if(!$rel) return;
  $thumb = 'uploads/thumb_' . basename($rel);
  del_file_rel($thumb);
}

/* ===== Actions ===== */
$msg = $err = '';
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!isset($_POST['csrf']) || !hash_equals($_SESSION['admin_csrf'], $_POST['csrf'])){ die('Bad CSRF'); }
  $action = $_POST['action'] ?? '';

  if($action==='ban_ip' || $action==='unban_ip'){
    $ip = trim($_POST['ip'] ?? '');
    if(filter_var($ip, FILTER_VALIDATE_IP)){
      $flag = ($action==='ban_ip') ? 1 : 0;
      $stmt = $db->prepare("UPDATE admin SET is_blocked=? WHERE ip_address=?");
      $stmt->bind_param("is", $flag, $ip);
      $stmt->execute();
      $stmt->close();
      // لو الـIP مش موجود في admin، نضيف صف وهمي عشان يظهر بالقائمة
      if($db->affected_rows===0){
        $dummyUser = rand(2000000,2999999);
        $stmt = $db->prepare("INSERT IGNORE INTO admin (userid, ip_address, is_blocked) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $dummyUser, $ip, $flag);
        $stmt->execute(); $stmt->close();
      }
      $msg = ($flag? 'Banned ':'Unbanned ') . $ip;
    } else { $err = 'Invalid IP address.'; }
  }

  if($action==='delete_post'){
    $pid = (int)($_POST['postid'] ?? 0);
    if($pid>0){
      $stmt = $db->prepare("SELECT media_path, media_type FROM posts WHERE postid=?");
      $stmt->bind_param("i",$pid); $stmt->execute(); $res = $stmt->get_result();
      if($row = $res->fetch_assoc()){
        $mp = $row['media_path']; $mt = $row['media_type'];
        if($mp){
          del_file_rel($mp);
          if($mt==='image' && strpos($mp,'.webp')===false){ del_thumb_for($mp); }
        }
      }
      $stmt->close();
      $stmt = $db->prepare("DELETE FROM posts WHERE postid=?");
      $stmt->bind_param("i",$pid); $stmt->execute(); $stmt->close();
      $msg = "Post #$pid deleted";
    }
  }

  if($action==='delete_comment'){
    $cid = (int)($_POST['commentid'] ?? 0);
    if($cid>0){
      $stmt = $db->prepare("SELECT media_path, media_type FROM comments WHERE commentid=?");
      $stmt->bind_param("i",$cid); $stmt->execute(); $res = $stmt->get_result();
      if($row = $res->fetch_assoc()){
        $mp = $row['media_path']; $mt = $row['media_type'];
        if($mp){
          del_file_rel($mp);
          if($mt==='image' && strpos($mp,'.webp')===false){ del_thumb_for($mp); }
        }
      }
      $stmt->close();
      $stmt = $db->prepare("DELETE FROM comments WHERE commentid=?");
      $stmt->bind_param("i",$cid); $stmt->execute(); $stmt->close();
      $msg = "Comment #$cid deleted";
    }
  }

  if($action==='anonymize_user'){
    $uid = trim($_POST['userid'] ?? '');
    if($uid!==''){
      $stmt = $db->prepare("SELECT pfp_path FROM profiles WHERE userid=?");
      $stmt->bind_param("s",$uid); $stmt->execute(); $res=$stmt->get_result();
      if($row=$res->fetch_assoc()){
        if($row['pfp_path']) del_file_rel($row['pfp_path']);
      }
      $stmt->close();
      $username = '[deleted]'; $color = '#777777'; $pfp = null;
      $stmt = $db->prepare("INSERT INTO profiles (userid, username, color, pfp_path)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE username=VALUES(username), color=VALUES(color), pfp_path=VALUES(pfp_path)");
      $stmt->bind_param("ssss", $uid, $username, $color, $pfp);
      $stmt->execute(); $stmt->close();
      $msg = "User $uid anonymized";
    }
  }

  if($action==='hard_delete_user'){
    $uid = trim($_POST['userid'] ?? '');
    if($uid!==''){
      $res = $db->prepare("SELECT commentid, media_path, media_type FROM comments WHERE userid=?");
      $res->bind_param("s",$uid); $res->execute(); $rs=$res->get_result();
      while($c = $rs->fetch_assoc()){
        if($c['media_path']){
          del_file_rel($c['media_path']);
          if($c['media_type']==='image' && strpos($c['media_path'],'.webp')===false){ del_thumb_for($c['media_path']); }
        }
      }
      $res->close();
      $stmt = $db->prepare("DELETE FROM comments WHERE userid=?");
      $stmt->bind_param("s",$uid); $stmt->execute(); $stmt->close();

      $res = $db->prepare("SELECT postid, media_path, media_type FROM posts WHERE userid=?");
      $res->bind_param("s",$uid); $res->execute(); $rs=$res->get_result();
      while($p = $rs->fetch_assoc()){
        if($p['media_path']){
          del_file_rel($p['media_path']);
          if($p['media_type']==='image' && strpos($p['media_path'],'.webp')===false){ del_thumb_for($p['media_path']); }
        }
      }
      $res->close();
      $stmt = $db->prepare("DELETE FROM posts WHERE userid=?");
      $stmt->bind_param("s",$uid); $stmt->execute(); $stmt->close();

      $stmt = $db->prepare("SELECT pfp_path FROM profiles WHERE userid=?");
      $stmt->bind_param("s",$uid); $stmt->execute(); $r=$stmt->get_result();
      if($row=$r->fetch_assoc()){ if($row['pfp_path']) del_file_rel($row['pfp_path']); }
      $stmt->close();
      $stmt = $db->prepare("DELETE FROM profiles WHERE userid=?");
      $stmt->bind_param("s",$uid); $stmt->execute(); $stmt->close();

      $stmt = $db->prepare("DELETE FROM admin WHERE userid=?");
      $stmt->bind_param("s",$uid); $stmt->execute(); $stmt->close();

      $msg = "User $uid fully deleted";
    }
  }

  if($action==='change_pass'){
    $cu = $_SESSION['admin_name'];
    $cur = (string)($_POST['current'] ?? '');
    $nw1 = (string)($_POST['new1'] ?? '');
    $nw2 = (string)($_POST['new2'] ?? '');
    if($nw1!=='' && $nw1===$nw2){
      $stmt = $db->prepare("SELECT pass_hash FROM admin_users WHERE username=?");
      $stmt->bind_param("s",$cu); $stmt->execute(); $res=$stmt->get_result();
      if(($row=$res->fetch_assoc()) && password_verify($cur,$row['pass_hash'])){
        $hash = password_hash($nw1, PASSWORD_DEFAULT);
        $u = $db->prepare("UPDATE admin_users SET pass_hash=? WHERE username=?");
        $u->bind_param("ss",$hash,$cu); $u->execute(); $u->close();
        $msg = "Password changed.";
      } else { $err = 'Current password incorrect.'; }
      $stmt->close();
    } else { $err = 'Passwords do not match.'; }
  }
}

$view = $_GET['view'] ?? 'bans';

/* Data for lists */
// IP summary (status + last seen)
$ips = $db->query("SELECT ip_address, MAX(created_at) AS last_seen, MAX(is_blocked) AS blocked
                   FROM admin GROUP BY ip_address
                   ORDER BY blocked DESC, last_seen DESC LIMIT 200");

// Map: ip_address => [ ['userid'=>..., 'username'=>...], ... ]
$ipUsersMap = [];
$uq = $db->query("SELECT a.ip_address, a.userid, COALESCE(p.username, CONCAT('user', a.userid)) AS uname
                  FROM admin a
                  LEFT JOIN profiles p ON p.userid = a.userid
                  ORDER BY a.ip_address, a.userid");
while($u = $uq->fetch_assoc()){
  $ipUsersMap[$u['ip_address']][] = ['userid'=>$u['userid'], 'uname'=>$u['uname']];
}

$posts = $db->query("SELECT p.*, (SELECT COUNT(*) FROM comments c WHERE c.postid=p.postid) AS ccount
                     FROM posts p ORDER BY upload_date DESC LIMIT 100");

$comments = $db->query("SELECT * FROM comments ORDER BY upload_date DESC LIMIT 200");

$q = trim($_GET['q'] ?? '');
if($q!==''){
  if(ctype_digit($q)){
    $stmt = $db->prepare("SELECT * FROM profiles WHERE userid=?");
    $stmt->bind_param("s",$q);
  } else {
    $like = '%'.$q.'%';
    $stmt = $db->prepare("SELECT * FROM profiles WHERE username LIKE ?");
    $stmt->bind_param("s",$like);
  }
  $stmt->execute(); $profiles = $stmt->get_result();
  $stmt->close();
} else {
  $profiles = $db->query("SELECT * FROM profiles ORDER BY updated_at DESC LIMIT 100");
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Santaroso — Admin Panel</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{
  --bg:#fff4e0;--paper:#fffdf8;--ink:#222;--muted:#555;--border:#c9b49a;--soft:#ffe9c9;--shadow:rgba(0,0,0,.08);
  --good:#0f7b1f; --bad:#b00020;
}
*{box-sizing:border-box} body{margin:0;background:linear-gradient(0,var(--bg),var(--bg));font:14px/1.5 Verdana,Tahoma,Arial,sans-serif;color:var(--ink)}
.top{position:sticky;top:0;background:var(--paper);border-bottom:1px solid var(--border);box-shadow:0 2px 0 var(--soft);z-index:9}
.top .inner{display:flex;gap:12px;align-items:center;padding:8px 12px}
.nav a{display:inline-block;padding:6px 10px;margin:0 4px;border:1px solid var(--border);border-radius:8px;background:#fff;color:var(--ink);text-decoration:none}
.nav a.active{background:var(--soft)}
.container{max-width:1100px;margin:12px auto;padding:12px}
.panel{background:var(--paper);border:1px solid var(--border);border-radius:10px;box-shadow:0 2px 0 var(--shadow);margin:12px 0;overflow:hidden}
.panel h2{margin:0;padding:10px 12px;background:var(--soft);border-bottom:1px solid var(--border);font-size:16px}
.pad{padding:12px}
table{width:100%;border-collapse:collapse}
th,td{border:1px solid var(--border);padding:8px;text-align:left;font-size:13px;vertical-align:top}
.input, .btn, select{padding:8px;border:1px solid var(--border);border-radius:8px;background:#fff;color:var(--ink);font:inherit}
.btn{cursor:pointer;font-weight:700;box-shadow:inset 0 1px 0 rgba(255,255,255,.35),0 2px 0 var(--shadow)}
.row-actions form{display:inline}
.success{color:var(--good);background:#e8f5e9;border:1px solid #b7e0bf;border-radius:8px;padding:8px;margin:8px 0}
.error{color:var(--bad);background:#ffe8ec;border:1px solid #ffb4c2;border-radius:8px;padding:8px;margin:8px 0}
code{background:#000;color:#0f0;padding:2px 4px;border-radius:4px}
.ip-users{margin:0;padding-left:16px;max-height:160px;overflow:auto}
.ip-users li{margin:2px 0}
.muted{color:var(--muted)}
</style>
</head>
<body>
<div class="top">
  <div class="inner">
    <strong>Admin Panel</strong>
    <div class="nav" style="margin-left:auto">
      <a href="?view=bans" class="<?php echo $view==='bans'?'active':''; ?>">Bans</a>
      <a href="?view=posts" class="<?php echo $view==='posts'?'active':''; ?>">Posts</a>
      <a href="?view=comments" class="<?php echo $view==='comments'?'active':''; ?>">Comments</a>
      <a href="?view=accounts" class="<?php echo $view==='accounts'?'active':''; ?>">Accounts</a>
      <a href="?view=settings" class="<?php echo $view==='settings'?'active':''; ?>">Settings</a>
      <a href="admin_logout.php">Logout (<?php echo htmlspecialchars($_SESSION['admin_name']); ?>)</a>
    </div>
  </div>
</div>

<div class="container">
  <?php if($msg): ?><div class="success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
  <?php if($err): ?><div class="error"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

  <?php if($view==='bans'): ?>
    <section class="panel">
      <h2>Ban by IP</h2>
      <div class="pad">
        <form method="post" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
          <input class="input" name="ip" placeholder="e.g. 1.2.3.4" required>
          <button class="btn" name="action" value="ban_ip">Ban</button>
          <button class="btn" name="action" value="unban_ip">Unban</button>
          <span class="help" style="color:var(--muted)">أي IP محظور لن يستطيع فتح الموقع.</span>
        </form>
      </div>
    </section>

    <section class="panel">
      <h2>Known IPs</h2>
      <div class="pad" style="overflow:auto">
        <table>
          <thead>
            <tr>
              <th>IP</th>
              <th>Status</th>
              <th>Users (Name — ID)</th>
              <th>Last Seen</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php while($row=$ips->fetch_assoc()): ?>
            <?php
              $ip = $row['ip_address'];
              $usersForIp = $ipUsersMap[$ip] ?? [];
            ?>
            <tr>
              <td><code><?php echo htmlspecialchars($ip); ?></code></td>
              <td><?php echo $row['blocked']?'🔴 BANNED':'🟢 Allowed'; ?></td>
              <td>
                <?php if($usersForIp): ?>
                  <ul class="ip-users">
                    <?php foreach($usersForIp as $u): ?>
                      <li>
                        <strong><?php echo htmlspecialchars($u['uname']); ?></strong>
                        <span class="muted">— ID: <?php echo htmlspecialchars($u['userid']); ?></span>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <em class="muted">No users recorded for this IP</em>
                <?php endif; ?>
              </td>
              <td><?php echo htmlspecialchars($row['last_seen']); ?></td>
              <td class="row-actions">
                <form method="post">
                  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                  <input type="hidden" name="ip" value="<?php echo htmlspecialchars($ip); ?>">
                  <?php if($row['blocked']): ?>
                    <button class="btn" name="action" value="unban_ip">Unban</button>
                  <?php else: ?>
                    <button class="btn" name="action" value="ban_ip">Ban</button>
                  <?php endif; ?>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>

  <?php if($view==='posts'): ?>
    <section class="panel">
      <h2>Posts</h2>
      <div class="pad" style="overflow:auto">
        <table>
          <thead><tr><th>ID</th><th>User</th><th>Date</th><th>Content</th><th>Media</th><th>Comments</th><th>Actions</th></tr></thead>
          <tbody>
          <?php while($p=$posts->fetch_assoc()): ?>
            <tr>
              <td>#<?php echo (int)$p['postid']; ?></td>
              <td><?php echo htmlspecialchars($p['userid']); ?></td>
              <td><?php echo htmlspecialchars($p['upload_date']); ?></td>
              <td><?php echo nl2br(htmlspecialchars(mb_strimwidth($p['content'],0,160,'…'))); ?></td>
              <td><?php echo $p['media_path'] ? '<code>'.htmlspecialchars($p['media_path']).'</code>' : '-'; ?></td>
              <td><?php echo (int)$p['ccount']; ?></td>
              <td class="row-actions">
                <form method="post" onsubmit="return confirm('Delete post #<?php echo (int)$p['postid']; ?> ?');">
                  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                  <input type="hidden" name="postid" value="<?php echo (int)$p['postid']; ?>">
                  <button class="btn" name="action" value="delete_post">Delete</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>

  <?php if($view==='comments'): ?>
    <section class="panel">
      <h2>Comments</h2>
      <div class="pad" style="overflow:auto">
        <table>
          <thead><tr><th>ID</th><th>Post</th><th>User</th><th>Date</th><th>Content</th><th>Media</th><th>Actions</th></tr></thead>
          <tbody>
          <?php while($c=$comments->fetch_assoc()): ?>
            <tr>
              <td>#<?php echo (int)$c['commentid']; ?></td>
              <td>#<?php echo (int)$c['postid']; ?></td>
              <td><?php echo htmlspecialchars($c['userid']); ?></td>
              <td><?php echo htmlspecialchars($c['upload_date']); ?></td>
              <td><?php echo nl2br(htmlspecialchars(mb_strimwidth($c['content'],0,160,'…'))); ?></td>
              <td><?php echo $c['media_path'] ? '<code>'.htmlspecialchars($c['media_path']).'</code>' : '-'; ?></td>
              <td class="row-actions">
                <form method="post" onsubmit="return confirm('Delete comment #<?php echo (int)$c['commentid']; ?> ?');">
                  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                  <input type="hidden" name="commentid" value="<?php echo (int)$c['commentid']; ?>">
                  <button class="btn" name="action" value="delete_comment">Delete</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>

  <?php if($view==='accounts'): ?>
    <section class="panel">
      <h2>Accounts</h2>
      <div class="pad">
        <form method="get" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
          <input type="hidden" name="view" value="accounts">
          <input class="input" name="q" placeholder="Search by userid or username" value="<?php echo htmlspecialchars($q); ?>">
          <button class="btn">Search</button>
        </form>
      </div>
      <div class="pad" style="overflow:auto">
        <table>
          <thead><tr><th>UserID</th><th>Username</th><th>Color</th><th>PFP</th><th>Updated</th><th>Actions</th></tr></thead>
          <tbody>
          <?php while($pr=$profiles->fetch_assoc()): ?>
            <tr>
              <td><?php echo htmlspecialchars($pr['userid']); ?></td>
              <td><?php echo htmlspecialchars($pr['username']); ?></td>
              <td><?php echo htmlspecialchars($pr['color']); ?></td>
              <td><?php echo $pr['pfp_path'] ? '<code>'.htmlspecialchars($pr['pfp_path']).'</code>' : '-'; ?></td>
              <td><?php echo htmlspecialchars($pr['updated_at']); ?></td>
              <td class="row-actions">
                <form method="post" style="display:inline" onsubmit="return confirm('Anonymize user <?php echo htmlspecialchars($pr['userid']); ?> ?');">
                  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                  <input type="hidden" name="userid" value="<?php echo htmlspecialchars($pr['userid']); ?>">
                  <button class="btn" name="action" value="anonymize_user">Anonymize</button>
                </form>
                <form method="post" style="display:inline" onsubmit="return confirm('HARD DELETE user <?php echo htmlspecialchars($pr['userid']); ?> and ALL content?');">
                  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                  <input type="hidden" name="userid" value="<?php echo htmlspecialchars($pr['userid']); ?>">
                  <button class="btn" name="action" value="hard_delete_user">HARD DELETE</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>

  <?php if($view==='settings'): ?>
    <section class="panel">
      <h2>Settings</h2>
      <div class="pad">
        <form method="post" style="max-width:420px">
          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
          <label>Current password</label>
          <input class="input" type="password" name="current" required>
          <label>New password</label>
          <input class="input" type="password" name="new1" required>
          <label>Repeat new password</label>
          <input class="input" type="password" name="new2" required>
          <button class="btn" name="action" value="change_pass">Change password</button>
        </form>
        <p style="color:var(--muted);font-size:12px;margin-top:8px">Tip: غيّر الباسورد الافتراضي فورًا.</p>
      </div>
    </section>
  <?php endif; ?>
</div>
</body>
</html>
<?php $db->close(); ?>
