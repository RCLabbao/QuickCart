<?php
declare(strict_types=1);
session_start();
$step = (int)($_GET['step'] ?? 1);
$base = dirname(__DIR__);
$configPath = $base.'/config/config.php';
// If already installed, verify DB connectivity before redirecting; otherwise show a helpful error and DB form
if (file_exists($configPath)) {
  try {
    require $configPath;
    $dbc = (array)(CONFIG['db'] ?? []);
    if (!$dbc || empty($dbc['host']) || empty($dbc['name']) || empty($dbc['user'])) {
      throw new RuntimeException('Config found but database settings are incomplete.');
    }
    if (!extension_loaded('pdo_mysql')) { throw new RuntimeException('PHP PDO MySQL driver (pdo_mysql) is not enabled.'); }

    // Attempt a quick connection test and ensure schema exists
    try {
      $pdo = new PDO('mysql:host='.$dbc['host'].';dbname='.$dbc['name'].';charset=utf8mb4', $dbc['user'], (string)($dbc['pass'] ?? ''), [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    } catch (Throwable $eConn) {
      $msg = $eConn->getMessage();
      if (strpos($msg,'Unknown database')!==false || strpos($msg,'1049')!==false) {
        // Create DB if it does not exist
        $pdoTmp = new PDO('mysql:host='.$dbc['host'].';charset=utf8mb4', $dbc['user'], (string)($dbc['pass'] ?? ''), [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $dbName = str_replace('`','``', (string)$dbc['name']);
        $pdoTmp->exec('CREATE DATABASE IF NOT EXISTS `'.$dbName.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
        $pdo = new PDO('mysql:host='.$dbc['host'].';dbname='.$dbc['name'].';charset=utf8mb4', $dbc['user'], (string)($dbc['pass'] ?? ''), [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
      } else { throw $eConn; }
    }
    $hasUsers = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
    if ($hasUsers) { echo '<meta http-equiv="refresh" content="0;url=/" />Installed'; exit; }
    // Schema not present yet; continue to installer step 3
    $_SESSION['db'] = $dbc; $step = 3;
  } catch (Throwable $e) {
    // Stay on the installer with a clear message and prefill the DB form
    $err = 'Config found but database connection failed: '.htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8').
      ' — please verify the database exists, credentials are correct, and the PDO MySQL driver is installed.';
    $_SESSION['db'] = CONFIG['db'] ?? ($_SESSION['db'] ?? null);
    $step = 2;
  }
}
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function write_config($arr, $path){
  $export = var_export($arr, true);
  $php = "<?php\nconst CONFIG = $export;\n";
  if (!is_dir(dirname($path))) mkdir(dirname($path), 0775, true);
  file_put_contents($path, $php);
}
if ($_SERVER['REQUEST_METHOD']==='POST'){
  if ($step===2){
    $_SESSION['db']= [ 'host'=>$_POST['db_host'],'name'=>$_POST['db_name'],'user'=>$_POST['db_user'],'pass'=>$_POST['db_pass'] ];
    header('Location: ?step=3'); exit;
  }
  if ($step===3){
    $db = $_SESSION['db'] ?? null; if(!$db){ header('Location:?step=1'); exit; }
    try {
      // Connect to DB; create database if it does not exist
      try {
        $pdo = new PDO('mysql:host='.$db['host'].';dbname='.$db['name'].';charset=utf8mb4', $db['user'], $db['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
      } catch (Throwable $eConn) {
        $msg = $eConn->getMessage();
        if (strpos($msg,'Unknown database')!==false || strpos($msg,'1049')!==false) {
          $pdoTmp = new PDO('mysql:host='.$db['host'].';charset=utf8mb4', $db['user'], $db['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
          $dbName = str_replace('`','``', (string)$db['name']);
          $pdoTmp->exec('CREATE DATABASE IF NOT EXISTS `'.$dbName.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
          $pdo = new PDO('mysql:host='.$db['host'].';dbname='.$db['name'].';charset=utf8mb4', $db['user'], $db['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        } else { throw $eConn; }
      }
      // Create schema
      $schema = file_get_contents(__DIR__.'/schema.sql');
      $pdo->exec($schema);
      // Create performance indexes (ignore if they already exist)
      $indexes = [
        "CREATE INDEX idx_products_status_created ON products (status, created_at)",
        "CREATE INDEX idx_products_slug ON products (slug)",
        "CREATE INDEX idx_orders_created ON orders (created_at)",
        "CREATE INDEX idx_orders_email ON orders (email)",
        "CREATE INDEX idx_product_images_pid_order ON product_images (product_id, sort_order)",
        "CREATE INDEX idx_collections_slug ON collections (slug)",
      ];
      foreach ($indexes as $ix) { try { $pdo->exec($ix); } catch (Throwable $e) { /* ignore if exists */ } }
      // Seed admin and products
      $name = trim($_POST['admin_name']); $email = trim($_POST['admin_email']); $pass = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);
      $pdo->prepare('INSERT INTO users (name,email,password_hash,created_at) VALUES (?,?,?,NOW())')->execute([$name,$email,$pass]);
      $uid = (int)$pdo->lastInsertId();
      $pdo->exec("INSERT INTO roles (slug,name) VALUES ('superadmin','Super Admin'),('admin','Admin')");
      $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, 1)')->execute([$uid]);
      // Seed permissions
      $perms = ['products.read','products.write','orders.read','orders.write','users.read','users.write','roles.write','settings.write','collections.write'];
      $ps = $pdo->prepare('INSERT INTO permissions (slug,name) VALUES (?, ?)');
      foreach ($perms as $perm) { $ps->execute([$perm, ucwords(str_replace(['.','_'],' ', $perm))]); }
      // Assign all perms to superadmin role (id=1)
      $permIds = $pdo->query('SELECT id FROM permissions')->fetchAll(PDO::FETCH_COLUMN);
      $insRp = $pdo->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (1, ?)');
      foreach ($permIds as $pid) { $insRp->execute([$pid]); }
      // Settings defaults (store, currency, pickup, shipping, brand color, checkout field toggles)
      $pdo->prepare('INSERT INTO settings (`key`,`value`) VALUES
        ("store_name",?),
        ("currency","PHP"),
        ("pickup_location","Main Store - Set in Admin"),
        ("shipping_fee_cod","0"),
        ("shipping_fee_pickup","0"),
        ("brand_color", "#212529"),
        ("checkout_enable_phone","1"),
        ("checkout_enable_region","1"),
        ("checkout_enable_province","1"),
        ("checkout_enable_city","1"),
        ("checkout_enable_barangay","1"),
        ("checkout_enable_street","1"),
        ("checkout_enable_postal","1")
      ')->execute(['QuickCart']);
      // Seed collections (several categories)
      $pdo->exec("INSERT INTO collections (title,slug,description) VALUES
        ('New Arrivals','new','Fresh picks'),
        ('Apparel','apparel','Clothing and fashion'),
        ('Accessories','accessories','Complete your look'),
        ('Electronics','electronics','Gadgets and devices'),
        ('Home & Living','home-living','For your space'),
        ('Beauty','beauty','Skincare and wellness'),
        ('Sports','sports','Gear and active wear'),
        ('Kids','kids','For the little ones')");
      // Fetch collection IDs
      $collectionIds = $pdo->query('SELECT id FROM collections ORDER BY id')->fetchAll(\PDO::FETCH_COLUMN);
      // Seed many products and assign to collections
      $stmt = $pdo->prepare('INSERT INTO products (title,slug,description,price,status,stock,collection_id,created_at) VALUES (?,?,?,?,"active",?, ?, NOW())');
      $insImg = $pdo->prepare('INSERT INTO product_images (product_id,url,sort_order) VALUES (?,?,?)');
      for ($i=1;$i<=600;$i++){
        $t = "Sample Product $i"; $s = strtolower(preg_replace('/[^a-z0-9]+/','-', $t));
        $d = 'Beautiful modern item perfect for your lifestyle. Lorem ipsum dolor sit amet.';
        $price = rand(199, 9999)/1.0;
        $r = rand(0,100); $stock = $r<10 ? 0 : ($r<30 ? rand(1,3) : rand(4,20));
        $cid = $collectionIds[array_rand($collectionIds)] ?? null;
        $stmt->execute([$t,$s,$d,$price,$stock,$cid]);
        $pid = (int)$pdo->lastInsertId();
        // seed 1-2 placeholder images for demo (remote)
        $count = rand(1,2); for($k=0;$k<$count;$k++){ $insImg->execute([$pid, 'https://picsum.photos/seed/'.($pid*10+$k).'/1000/1000', $k]); }
      }

          // Seed demo customers (users)
          $demoUsers = [
            ['Ava Cruz','ava@example.com'],['Liam Santos','liam@example.com'],['Sofia Reyes','sofia@example.com'],
            ['Noah Garcia','noah@example.com'],['Mia Dela Cruz','mia@example.com'],['Lucas Lim','lucas@example.com'],
            ['Emma Tan','emma@example.com'],['Ethan Ong','ethan@example.com'],['Olivia Go','olivia@example.com'],['Jacob Chua','jacob@example.com']
          ];
          $insUser = $pdo->prepare('INSERT INTO users (name,email,password_hash,created_at) VALUES (?,?,?,?)');
          $now = new DateTime();
          foreach ($demoUsers as $u) {
            $when = (clone $now)->modify('-'.rand(5,30).' days')->format('Y-m-d H:i:s');
            $insUser->execute([$u[0], $u[1], password_hash('password', PASSWORD_DEFAULT), $when]);
          }
          $userIds = $pdo->query('SELECT id,email FROM users WHERE email IN ("ava@example.com","liam@example.com","sofia@example.com","noah@example.com","mia@example.com","lucas@example.com","emma@example.com","ethan@example.com","olivia@example.com","jacob@example.com")')->fetchAll(PDO::FETCH_KEY_PAIR);

          // Seed demo addresses for users
          $insAddr = $pdo->prepare('INSERT INTO addresses (user_id,name,phone,region,province,city,barangay,street,postal_code) VALUES (?,?,?,?,?,?,?,?,?)');
          foreach ($userIds as $uid => $email) {
            $insAddr->execute([$uid,'Demo '.$uid,'09'.rand(100000000,999999999),'Region II','Cagayan','Aparri City','Bagumbayan','123 Demo St','1100']);
          }

          // Seed demo orders with items (last 30 days, including today)
          $productIds = $pdo->query('SELECT id,title,price FROM products ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
          $insOrder = $pdo->prepare('INSERT INTO orders (user_id,email,shipping_method,subtotal,shipping_fee,total,status,notes,created_at) VALUES (?,?,?,?,?,?,?,?,?)');
          $insItem  = $pdo->prepare('INSERT INTO order_items (order_id,product_id,title,unit_price,quantity) VALUES (?,?,?,?,?)');
          $insShip  = $pdo->prepare('INSERT INTO addresses (order_id,name,phone,region,province,city,barangay,street,postal_code) VALUES (?,?,?,?,?,?,?,?,?)');

          $statuses = ['pending','processing','shipped','completed','cancelled'];
          $shippingMethods = ['cod','pickup'];
          $orderCount = 28; // a good spread over ~1 month

          for ($i=0; $i<$orderCount; $i++) {
            $daysAgo = ($i < 6) ? 0 : rand(1, 29); // ensure ~6 today
            $created = (new DateTime('-'.$daysAgo.' days'))->format('Y-m-d '.sprintf('%02d:%02d:%02d', rand(9,20), rand(0,59), rand(0,59)));
            // pick user
            $userId = null; $email = 'guest'.rand(1000,9999).'@example.com';
            if (rand(0,100) < 70) { // 70% registered users
              $keys = array_keys($userIds); $userId = $keys[array_rand($keys)]; $email = $userIds[$userId];
            }
            $method = $shippingMethods[array_rand($shippingMethods)];
            $status = $statuses[array_rand($statuses)];

            // items
            $itemsN = rand(1,3); $subtotal = 0.0; $chosen = [];
            for ($k=0; $k<$itemsN; $k++) {
              $p = $productIds[array_rand($productIds)];
              if (isset($chosen[$p['id']])) continue; $chosen[$p['id']] = true; // no duplicates
              $qty = rand(1,3); $subtotal += ((float)$p['price']) * $qty;
            }
            $shipFee = ($method==='cod') ? 120.00 : 0.00;
            $total = $subtotal + $shipFee;

            $insOrder->execute([$userId,$email,$method,$subtotal,$shipFee,$total,$status, ($i%5===0?'Note: prioritize delivery':''), $created]);
            $oid = (int)$pdo->lastInsertId();

            // re-iterate chosen items to insert rows
            foreach (array_keys($chosen) as $pid) {
              // fetch matching product record (could map by id for speed)
              // but keep simple here
              foreach ($productIds as $p) {
                if ((int)$p['id'] === (int)$pid) {
                  $qty = rand(1,3);
                  $insItem->execute([$oid, $pid, $p['title'], $p['price'], $qty]);
                  break;
                }
              }
            }
            // shipping address snapshot
            $insShip->execute([$oid,'Customer','09'.rand(100000000,999999999),'Region II','Cagayan','Aparri','San Antonio','456 Sample Rd','1600']);
          }

      // Save config
      write_config(['db'=>$db], $configPath);
      header('Location: ?step=4'); exit;
    } catch (Throwable $e){ $err = $e->getMessage(); }
  }
}
?>
<?php $pct = [1=>10,2=>40,3=>85,4=>100][$step] ?? 10; ?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<title>QuickCart Installer</title></head>
<body class="bg-light">
<div class="container py-5" style="max-width:720px">
  <div class="card shadow-sm border-0 rounded-4">
    <div class="card-body p-4 p-md-5">
      <h1 class="h4 mb-3">QuickCart Setup</h1>
      <div class="progress mb-3" style="height:6px;">
        <div class="progress-bar bg-dark" role="progressbar" style="width: <?= (int)$pct ?>%" aria-valuenow="<?= (int)$pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
      </div>
      <ol class="breadcrumb small mb-4">
        <li class="breadcrumb-item<?= $step===1?' active':'' ?>">Welcome</li>
        <li class="breadcrumb-item<?= $step===2?' active':'' ?>">Database</li>
        <li class="breadcrumb-item<?= $step===3?' active':'' ?>">Install</li>
        <li class="breadcrumb-item<?= $step===4?' active':'' ?>">Done</li>
      </ol>
      <?php if (!empty($err)): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>
      <?php if ($step===1): ?>
        <p class="text-muted">Welcome to QuickCart! Before we begin, please ensure you have a MySQL database ready.</p>
        <a href="?step=2" class="btn btn-dark">Continue</a>
      <?php elseif ($step===2): ?>
        <form method="post" class="row g-3">
          <div class="col-md-6"><label class="form-label">DB Host</label><input class="form-control" name="db_host" value="<?= h($_SESSION['db']['host'] ?? 'localhost') ?>" required></div>
          <div class="col-md-6"><label class="form-label">DB Name</label><input class="form-control" name="db_name" value="<?= h($_SESSION['db']['name'] ?? '') ?>" required></div>
          <div class="col-md-6"><label class="form-label">DB User</label><input class="form-control" name="db_user" value="<?= h($_SESSION['db']['user'] ?? '') ?>" required></div>
          <div class="col-md-6"><label class="form-label">DB Pass</label><input class="form-control" type="password" name="db_pass" value="<?= h($_SESSION['db']['pass'] ?? '') ?>"></div>
          <div class="col-12"><button class="btn btn-dark">Continue</button></div>
        </form>
      <?php elseif ($step===3): ?>
        <p class="text-muted">Configure your main admin account.</p>
        <form method="post" class="row g-3">
          <div class="col-md-6"><label class="form-label">Admin Name</label><input class="form-control" name="admin_name" required></div>
          <div class="col-md-6"><label class="form-label">Admin Email</label><input class="form-control" type="email" name="admin_email" required></div>
          <div class="col-md-6"><label class="form-label">Password</label><input class="form-control" type="password" name="admin_pass" required></div>
          <div class="col-12"><button class="btn btn-dark">Install</button></div>
        </form>
      <?php else: ?>
        <div class="alert alert-success">Installation complete!</div>
        <p>
          <a href="/" class="btn btn-dark">Go to Storefront</a>
          <a href="/admin/login" class="btn btn-outline-secondary">Go to Admin</a>
        </p>
      <?php endif; ?>
    </div>
  </div>
</div>
</body></html>

