<?php
// dashboard.php
session_start();

// ------------- Configuration -------------
$host     = "localhost";
$dbname   = "sungura_enterprises";
$username = "root";
$password = "";
$perPage  = 10; // rows per page (change if needed)
// -----------------------------------------

// Connect
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Helper: set flash message
function flash_set($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function flash_get() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// Whitelists for sorting
$userSortCols = ['id','name','country','email','phone','created_at'];
$codeSortCols = ['id','code','created_at'];

// Validate GET params helpers
function get_int($key, $default=1) {
    return isset($_GET[$key]) ? max(1, (int)$_GET[$key]) : $default;
}
function get_str($key, $default='') {
    return isset($_GET[$key]) ? trim((string)$_GET[$key]) : $default;
}

// ---------- Handle POST Actions (Users / Codes) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf_token']) {
        flash_set('error', 'Invalid CSRF token.');
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }

    $form_type = $_POST['form_type'] ?? '';

    if ($form_type === 'user') {
        // Add or update user
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $name = htmlspecialchars(trim($_POST['name'] ?? ''));
        $country = htmlspecialchars(trim($_POST['country'] ?? ''));
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $phone = htmlspecialchars(trim($_POST['phone'] ?? ''));

        if ($id) {
            $stmt = $conn->prepare("UPDATE signup SET name=?, country=?, email=?, phone=? WHERE id=?");
            $stmt->bind_param("ssssi", $name, $country, $email, $phone, $id);
            $ok = $stmt->execute();
            $stmt->close();
            if ($ok) flash_set('success', "User #{$id} updated.");
            else flash_set('error', "Failed to update user.");
        } else {
            $stmt = $conn->prepare("INSERT INTO signup (name, country, email, phone) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $country, $email, $phone);
            $ok = $stmt->execute();
            $last = $conn->insert_id;
            $stmt->close();
            if ($ok) flash_set('success', "User added (ID: {$last}).");
            else flash_set('error', "Failed to add user.");
        }

        // Return to users tab (preserve some GET state)
        $qs = http_build_query(['tab'=>'users']);
        header("Location: ".$_SERVER['PHP_SELF']."?".$qs);
        exit;
    }

    if ($form_type === 'code') {
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $code = htmlspecialchars(trim($_POST['code'] ?? ''));

        if ($id) {
            $stmt = $conn->prepare("UPDATE chekecha_bongo_codes SET code=? WHERE id=?");
            $stmt->bind_param("si", $code, $id);
            $ok = $stmt->execute();
            $stmt->close();
            if ($ok) flash_set('success', "Code #{$id} updated.");
            else flash_set('error', "Failed to update code.");
        } else {
            $stmt = $conn->prepare("INSERT INTO chekecha_bongo_codes (code) VALUES (?)");
            $stmt->bind_param("s", $code);
            $ok = $stmt->execute();
            $last = $conn->insert_id;
            $stmt->close();
            if ($ok) flash_set('success', "Code added (ID: {$last}).");
            else flash_set('error', "Failed to add code.");
        }

        $qs = http_build_query(['tab'=>'codes']);
        header("Location: ".$_SERVER['PHP_SELF']."?".$qs);
        exit;
    }
}

// ---------- Handle GET Deletions ----------
if (isset($_GET['delete_user'])) {
    $id = (int)$_GET['delete_user'];
    $stmt = $conn->prepare("DELETE FROM signup WHERE id=?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    if ($ok) flash_set('success', "User #{$id} deleted.");
    else flash_set('error', "Failed to delete user #{$id}.");
    $qs = http_build_query(['tab'=>'users']);
    header("Location: ".$_SERVER['PHP_SELF']."?".$qs);
    exit;
}

if (isset($_GET['delete_code'])) {
    $id = (int)$_GET['delete_code'];
    $stmt = $conn->prepare("DELETE FROM chekecha_bongo_codes WHERE id=?");
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    if ($ok) flash_set('success', "Code #{$id} deleted.");
    else flash_set('error', "Failed to delete code #{$id}.");
    $qs = http_build_query(['tab'=>'codes']);
    header("Location: ".$_SERVER['PHP_SELF']."?".$qs);
    exit;
}

// ---------- Prepare Users listing (search / sort / pagination) ----------
$active_tab = get_str('tab', 'users'); // 'users' or 'codes'

// Users search & sort params
$u_search = get_str('u_search', '');
$u_page   = get_int('u_page', 1);
$u_sort   = get_str('u_sort', 'id');
$u_dir    = strtolower(get_str('u_dir', 'asc')) === 'desc' ? 'DESC' : 'ASC';
if (!in_array($u_sort, $userSortCols)) $u_sort = 'id';

// Count users
if ($u_search !== '') {
    $like = "%{$u_search}%";
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM signup WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?");
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $total_users = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
    $stmt->close();
} else {
    $total_users = $conn->query("SELECT COUNT(*) AS cnt FROM signup")->fetch_assoc()['cnt'] ?? 0;
}
$total_pages_users = max(1, ceil($total_users / $perPage));
$u_page = min($u_page, $total_pages_users);
$u_offset = ($u_page - 1) * $perPage;

// Fetch users rows with prepared statements
if ($u_search !== '') {
    $sql = "SELECT * FROM signup WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY {$u_sort} {$u_dir} LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $limit = $perPage;
    $stmt->bind_param("sssii", $like, $like, $like, $limit, $u_offset);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $sql = "SELECT * FROM signup ORDER BY {$u_sort} {$u_dir} LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $limit = $perPage;
    $stmt->bind_param("ii", $limit, $u_offset);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// ---------- Prepare Codes listing (search / sort / pagination) ----------
$c_search = get_str('c_search', '');
$c_page   = get_int('c_page', 1);
$c_sort   = get_str('c_sort', 'id');
$c_dir    = strtolower(get_str('c_dir', 'asc')) === 'desc' ? 'DESC' : 'ASC';
if (!in_array($c_sort, $codeSortCols)) $c_sort = 'id';

if ($c_search !== '') {
    $likec = "%{$c_search}%";
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM chekecha_bongo_codes WHERE code LIKE ?");
    $stmt->bind_param("s", $likec);
    $stmt->execute();
    $total_codes = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
    $stmt->close();
} else {
    $total_codes = $conn->query("SELECT COUNT(*) AS cnt FROM chekecha_bongo_codes")->fetch_assoc()['cnt'] ?? 0;
}
$total_pages_codes = max(1, ceil($total_codes / $perPage));
$c_page = min($c_page, $total_pages_codes);
$c_offset = ($c_page - 1) * $perPage;

if ($c_search !== '') {
    $sql = "SELECT * FROM chekecha_bongo_codes WHERE code LIKE ? ORDER BY {$c_sort} {$c_dir} LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $limit = $perPage;
    $stmt->bind_param("sii", $likec, $limit, $c_offset);
    $stmt->execute();
    $codes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $sql = "SELECT * FROM chekecha_bongo_codes ORDER BY {$c_sort} {$c_dir} LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $limit = $perPage;
    $stmt->bind_param("ii", $limit, $c_offset);
    $stmt->execute();
    $codes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();

// Helper: build query string while preserving some GET params
function build_qs($overrides=[], $skip=['tab']) {
    $params = $_GET;
    foreach ($skip as $k) { unset($params[$k]); }
    foreach ($overrides as $k=>$v) { $params[$k] = $v; }
    return http_build_query($params);
}

// Read flash
$flash = flash_get();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Admin Dashboard - Users & Codes</title>
<style>
  :root { --primary:#196ad4; --accent:#229428; --danger:#d9534f; --bg:#f4f6f9; --card:#fff; }
  body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; margin:0; background:var(--bg); color:#222; }
  header { background: var(--primary); color:#fff; padding:14px 20px; display:flex; align-items:center; justify-content:space-between; gap:12px; }
  header h1 { margin:0; font-size:1.1rem; }
  header nav a { color:#fff; text-decoration:none; margin-left:10px; font-weight:600; }
  .wrap { max-width:1200px; margin:22px auto; padding:0 16px; }
  .tabs { display:flex; gap:8px; margin-bottom:12px; }
  .tabbtn { padding:8px 14px; border-radius:8px; background:#e6e9ee; cursor:pointer; border:none; font-weight:600; }
  .tabbtn.active { background:var(--primary); color:#fff; }
  .card { background:var(--card); padding:16px; border-radius:10px; box-shadow:0 6px 18px rgba(16,24,40,0.06); margin-bottom:18px; }
  .flex { display:flex; gap:12px; align-items:center; }
  form.search { display:flex; gap:8px; align-items:center; }
  input[type="text"], input[type="email"] { padding:8px 10px; border:1px solid #ddd; border-radius:6px; font-size:0.95rem; }
  button.btn { padding:8px 10px; border-radius:6px; border:none; cursor:pointer; font-weight:600; }
  button.btn.primary { background:var(--primary); color:#fff; }
  button.btn.ghost { background:#f3f4f6; }
  .table { width:100%; border-collapse:collapse; margin-top:12px; font-size:0.95rem; }
  .table th, .table td { padding:10px 12px; border-bottom:1px solid #eee; text-align:left; }
  .table th { background:linear-gradient(180deg,#196ad4,#165ea6); color:#fff; position:sticky; top:0; }
  .table tr:nth-child(even) { background:#fff; }
  .table tr:hover { background:#fbfdff; }
  .actions a { padding:6px 10px; border-radius:6px; color:#fff; text-decoration:none; font-size:0.85rem; margin-right:6px; }
  .actions .edit { background:var(--accent); }
  .actions .delete { background:var(--danger); }
  .small { font-size:0.85rem; color:#666; }
  .pagination { display:flex; gap:6px; align-items:center; margin-top:12px; flex-wrap:wrap; }
  .pagebtn { padding:6px 8px; border-radius:6px; border:none; cursor:pointer; background:#f0f0f0; }
  .pagebtn.active { background:var(--primary); color:#fff; }
  .flash { padding:10px 12px; border-radius:8px; margin-bottom:12px; }
  .flash.success { background:#ecfdf3; color:#065f46; border:1px solid #bbf7d0; }
  .flash.error { background:#fff1f2; color:#9f1239; border:1px solid #fecaca; }
  .grid-2 { display:grid; grid-template-columns: 1fr 380px; gap:16px; align-items:start; }
  @media (max-width:900px) { .grid-2 { grid-template-columns: 1fr; } .tabbtn { flex:1; } }
  .sort-link { color:#fff; text-decoration:none; display:inline-block; }
</style>
</head>
<body>
<header>
  <h1>Admin Dashboard</h1>
  <nav>
    <a href="../administration/admistration.html">Administration</a>
  </nav>
</header>

<div class="wrap">
  <?php if ($flash): ?>
    <div class="flash <?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
      <?= htmlspecialchars($flash['msg']) ?>
    </div>
  <?php endif; ?>

  <!-- Tab Buttons -->
  <div class="tabs">
    <button class="tabbtn <?= $active_tab === 'users' ? 'active' : '' ?>" onclick="location.href='?tab=users'">Users</button>
    <button class="tabbtn <?= $active_tab === 'codes' ? 'active' : '' ?>" onclick="location.href='?tab=codes'">Codes</button>
  </div>

  <!-- USERS TAB -->
  <div id="tab-users" style="<?= $active_tab === 'users' ? '' : 'display:none' ?>">
    <div class="card grid-2">
      <div>
        <div class="flex" style="justify-content:space-between; align-items:center;">
          <h3>Users</h3>
          <div class="small">Total: <?= (int)$total_users ?></div>
        </div>

        <!-- Controls: Search + Sort -->
        <div style="margin-top:10px;" class="flex">
          <form method="GET" class="search" style="flex:1;">
            <input type="hidden" name="tab" value="users">
            <input type="text" name="u_search" placeholder="Search name, email or phone" value="<?= htmlspecialchars($u_search) ?>">
            <button class="btn primary" type="submit">Search</button>
            <a class="btn ghost" href="<?= $_SERVER['PHP_SELF'] ?>?tab=users">Reset</a>
          </form>

          <div style="display:flex; gap:8px; align-items:center;">
            <form method="GET" style="display:flex; gap:6px; align-items:center;">
              <input type="hidden" name="tab" value="users">
              <input type="hidden" name="u_search" value="<?= htmlspecialchars($u_search) ?>">
              <label class="small">Sort:</label>
              <select name="u_sort" onchange="this.form.submit()">
                <?php foreach ($userSortCols as $col): ?>
                  <option value="<?= $col ?>" <?= $u_sort === $col ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$col)) ?></option>
                <?php endforeach; ?>
              </select>
              <select name="u_dir" onchange="this.form.submit()">
                <option value="asc" <?= strtolower($u_dir)==='asc' ? 'selected' : '' ?>>ASC</option>
                <option value="desc" <?= strtolower($u_dir)==='desc' ? 'selected' : '' ?>>DESC</option>
              </select>
            </form>
          </div>
        </div>

        <!-- Users Table -->
        <table class="table" aria-describedby="users table">
          <thead>
            <tr>
              <th><a class="sort-link" href="<?= $_SERVER['PHP_SELF'].'?'.build_qs(['tab'=>'users','u_sort'=>'id','u_dir'=>$u_sort==='id' && $u_dir==='ASC' ? 'desc' : 'asc']) ?>">ID</a></th>
              <th><a class="sort-link" href="<?= $_SERVER['PHP_SELF'].'?'.build_qs(['tab'=>'users','u_sort'=>'name','u_dir'=>$u_sort==='name' && $u_dir==='ASC' ? 'desc' : 'asc']) ?>">Name</a></th>
              <th>Country</th>
              <th><a class="sort-link" href="<?= $_SERVER['PHP_SELF'].'?'.build_qs(['tab'=>'users','u_sort'=>'email','u_dir'=>$u_sort==='email' && $u_dir==='ASC' ? 'desc' : 'asc']) ?>">Email</a></th>
              <th>Phone</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($users)): foreach ($users as $u): ?>
              <tr>
                <td><?= (int)$u['id'] ?></td>
                <td><?= htmlspecialchars($u['name']) ?></td>
                <td><?= htmlspecialchars($u['country']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['phone']) ?></td>
                <td class="actions">
                  <a class="edit" href="<?= $_SERVER['PHP_SELF'] . '?tab=users&edit_user=' . (int)$u['id'] ?>">Edit</a>
                  <a class="delete" href="<?= $_SERVER['PHP_SELF'] . '?tab=users&delete_user=' . (int)$u['id'] ?>" onclick="return confirm('Delete this user?')">Delete</a>
                </td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="6" class="small">No users found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
          <?php if ($u_page > 1): ?>
            <a class="pagebtn" href="?<?= build_qs(['tab'=>'users','u_page'=>$u_page-1,'u_search'=>$u_search,'u_sort'=>$u_sort,'u_dir'=>strtolower($u_dir)]) ?>">Prev</a>
          <?php endif; ?>

          <?php
            $start = max(1, $u_page - 3);
            $end = min($total_pages_users, $u_page + 3);
            for ($i=$start;$i<=$end;$i++):
          ?>
            <a class="pagebtn <?= $i === $u_page ? 'active' : '' ?>" href="?<?= build_qs(['tab'=>'users','u_page'=>$i,'u_search'=>$u_search,'u_sort'=>$u_sort,'u_dir'=>strtolower($u_dir)]) ?>"><?= $i ?></a>
          <?php endfor; ?>

          <?php if ($u_page < $total_pages_users): ?>
            <a class="pagebtn" href="?<?= build_qs(['tab'=>'users','u_page'=>$u_page+1,'u_search'=>$u_search,'u_sort'=>$u_sort,'u_dir'=>strtolower($u_dir)]) ?>">Next</a>
          <?php endif; ?>
        </div>
      </div>

      <!-- RIGHT: Add/Edit User form -->
      <div>
        <?php
          $editUser = null;
          if (!empty($_GET['edit_user'])) {
              $id = (int)$_GET['edit_user'];
              foreach ($users as $uu) {
                  if ((int)$uu['id'] === $id) { $editUser = $uu; break; }
              }
          }
        ?>
        <div class="card">
          <h3><?= $editUser ? "Edit User" : "Add User" ?></h3>
          <form method="POST" style="margin-top:8px;">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <input type="hidden" name="form_type" value="user">
            <input type="hidden" name="id" value="<?= $editUser ? (int)$editUser['id'] : '' ?>">
            <div style="margin-bottom:8px;">
              <input type="text" name="name" placeholder="Full name" value="<?= $editUser ? htmlspecialchars($editUser['name']) : '' ?>" required style="width:100%;">
            </div>
            <div style="margin-bottom:8px;">
              <input type="text" name="country" placeholder="Country" value="<?= $editUser ? htmlspecialchars($editUser['country']) : '' ?>" required style="width:100%;">
            </div>
            <div style="margin-bottom:8px;">
              <input type="email" name="email" placeholder="Email" value="<?= $editUser ? htmlspecialchars($editUser['email']) : '' ?>" required style="width:100%;">
            </div>
            <div style="margin-bottom:10px;">
              <input type="text" name="phone" placeholder="Phone" value="<?= $editUser ? htmlspecialchars($editUser['phone']) : '' ?>" required style="width:100%;">
            </div>
            <div style="display:flex; gap:8px;">
              <button class="btn primary" type="submit"><?= $editUser ? 'Update' : 'Add' ?></button>
              <?php if ($editUser): ?>
                <a class="btn" href="<?= $_SERVER['PHP_SELF'] ?>?tab=users">Cancel</a>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- CODES TAB -->
  <div id="tab-codes" style="<?= $active_tab === 'codes' ? '' : 'display:none' ?>">
    <div class="card grid-2">
      <div>
        <div class="flex" style="justify-content:space-between; align-items:center;">
          <h3>Codes</h3>
          <div class="small">Total: <?= (int)$total_codes ?></div>
        </div>

        <div style="margin-top:10px;" class="flex">
          <form method="GET" class="search" style="flex:1;">
            <input type="hidden" name="tab" value="codes">
            <input type="text" name="c_search" placeholder="Search code" value="<?= htmlspecialchars($c_search) ?>">
            <button class="btn primary" type="submit">Search</button>
            <a class="btn ghost" href="<?= $_SERVER['PHP_SELF'] ?>?tab=codes">Reset</a>
          </form>

          <div style="display:flex; gap:8px; align-items:center;">
            <form method="GET" style="display:flex; gap:6px; align-items:center;">
              <input type="hidden" name="tab" value="codes">
              <input type="hidden" name="c_search" value="<?= htmlspecialchars($c_search) ?>">
              <label class="small">Sort:</label>
              <select name="c_sort" onchange="this.form.submit()">
                <?php foreach ($codeSortCols as $col): ?>
                  <option value="<?= $col ?>" <?= $c_sort === $col ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$col)) ?></option>
                <?php endforeach; ?>
              </select>
              <select name="c_dir" onchange="this.form.submit()">
                <option value="asc" <?= strtolower($c_dir)==='asc' ? 'selected' : '' ?>>ASC</option>
                <option value="desc" <?= strtolower($c_dir)==='desc' ? 'selected' : '' ?>>DESC</option>
              </select>
            </form>
          </div>
        </div>

        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Code</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($codes)): foreach ($codes as $cc): ?>
              <tr>
                <td><?= (int)$cc['id'] ?></td>
                <td><?= htmlspecialchars($cc['code']) ?></td>
                <td class="actions">
                  <a class="edit" href="<?= $_SERVER['PHP_SELF'] . '?tab=codes&edit_code=' . (int)$cc['id'] ?>">Edit</a>
                  <a class="delete" href="<?= $_SERVER['PHP_SELF'] . '?tab=codes&delete_code=' . (int)$cc['id'] ?>" onclick="return confirm('Delete this code?')">Delete</a>
                </td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="3" class="small">No codes found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>

        <div class="pagination">
          <?php if ($c_page > 1): ?>
            <a class="pagebtn" href="?<?= build_qs(['tab'=>'codes','c_page'=>$c_page-1,'c_search'=>$c_search,'c_sort'=>$c_sort,'c_dir'=>strtolower($c_dir)]) ?>">Prev</a>
          <?php endif; ?>

          <?php
            $startC = max(1, $c_page - 3);
            $endC = min($total_pages_codes, $c_page + 3);
            for ($i=$startC;$i<=$endC;$i++):
          ?>
            <a class="pagebtn <?= $i === $c_page ? 'active' : '' ?>" href="?<?= build_qs(['tab'=>'codes','c_page'=>$i,'c_search'=>$c_search,'c_sort'=>$c_sort,'c_dir'=>strtolower($c_dir)]) ?>"><?= $i ?></a>
          <?php endfor; ?>

          <?php if ($c_page < $total_pages_codes): ?>
            <a class="pagebtn" href="?<?= build_qs(['tab'=>'codes','c_page'=>$c_page+1,'c_search'=>$c_search,'c_sort'=>$c_sort,'c_dir'=>strtolower($c_dir)]) ?>">Next</a>
          <?php endif; ?>
        </div>
      </div>

      <!-- RIGHT: Add/Edit Code form -->
      <div>
        <?php
          $editCodeForm = null;
          if (!empty($_GET['edit_code'])) {
              $id = (int)$_GET['edit_code'];
              foreach ($codes as $cc) {
                  if ((int)$cc['id'] === $id) { $editCodeForm = $cc; break; }
              }
          }
        ?>
        <div class="card">
          <h3><?= $editCodeForm ? "Edit Code" : "Add Code" ?></h3>
          <form method="POST" style="margin-top:8px;">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <input type="hidden" name="form_type" value="code">
            <input type="hidden" name="id" value="<?= $editCodeForm ? (int)$editCodeForm['id'] : '' ?>">
            <div style="margin-bottom:10px;">
              <input type="text" name="code" placeholder="Enter code" value="<?= $editCodeForm ? htmlspecialchars($editCodeForm['code']) : '' ?>" required style="width:100%;">
            </div>
            <div style="display:flex; gap:8px;">
              <button class="btn primary" type="submit"><?= $editCodeForm ? 'Update' : 'Add' ?></button>
              <?php if ($editCodeForm): ?>
                <a class="btn" href="<?= $_SERVER['PHP_SELF'] ?>?tab=codes">Cancel</a>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
  // Ensure correct tab shown if user uses "tab" param
  (function(){
    const urlParams = new URLSearchParams(location.search);
    const tab = urlParams.get('tab') || 'users';
    document.getElementById('tab-users').style.display = tab === 'users' ? '' : 'none';
    document.getElementById('tab-codes').style.display = tab === 'codes' ? '' : 'none';
    document.querySelectorAll('.tabbtn').forEach(btn=>{
      btn.classList.toggle('active', btn.textContent.trim().toLowerCase() === tab);
    });
  })();
</script>
</body>
</html>
