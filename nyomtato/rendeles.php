<?php
// SESSION minden kimenet előtt
session_start();

// DB
require __DIR__ . '/db.php';
try { $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); } catch(Throwable $e){}

// Segédfüggvény
function e($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

// Tábla biztosítása
try {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS rendelesek (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      eszkoz_id INT UNSIGNED NOT NULL,
      vonalkod VARCHAR(64) NOT NULL,
      nyomtato_tipusa VARCHAR(128) NOT NULL,
      toner VARCHAR(128) NOT NULL,
      statusz ENUM('Rendelve','Megjött') NOT NULL DEFAULT 'Rendelve',
      rendeles_dt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      megerkezes_dt DATETIME NULL,
      INDEX (eszkoz_id),
      INDEX (statusz),
      INDEX (rendeles_dt)
    ) ENGINE=InnoDB
  ");
} catch (Throwable $e) {
  error_log('rendelesek tabla letrehozas hiba: '.$e->getMessage());
}

// POST: rendelés létrehozása
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_order') {
  $vonalkod_post = trim($_POST['vonalkod'] ?? '');
  $megjegyzes    = trim($_POST['megjegyzes'] ?? ''); // ha később használnád, most nem tároljuk
  if ($vonalkod_post === '') {
    $_SESSION['flash'] = ['msg' => 'Add meg a vonalkódot rendeléshez.', 'type' => 'danger'];
    header("Location: rendeles.php");
    exit;
  }
  try {
    $st = $pdo->prepare("SELECT * FROM eszkozok WHERE vonalkod = :v LIMIT 1");
    $st->execute([':v' => $vonalkod_post]);
    $eszkoz = $st->fetch(PDO::FETCH_ASSOC);
    if (!$eszkoz) {
      $_SESSION['flash'] = ['msg' => "Nem található eszköz ezzel a vonalkóddal: ".$vonalkod_post, 'type' => 'danger'];
      header("Location: rendeles.php?vonalkod=".urlencode($vonalkod_post));
      exit;
    }

    // rendelés rögzítése (Rendelve)
    $ins = $pdo->prepare("
      INSERT INTO rendelesek (eszkoz_id, vonalkod, nyomtato_tipusa, toner, statusz)
      VALUES (:id, :vonalkod, :tipus, :toner, 'Rendelve')
    ");
    $ins->execute([
      ':id'       => $eszkoz['id'],
      ':vonalkod' => $eszkoz['vonalkod'],
      ':tipus'    => $eszkoz['nyomtato_tipusa'],
      ':toner'    => $eszkoz['toner']
    ]);
    
    // --- SIKERES INSERT UTÁN: mailto draft összerakása ---
$orderMsg =
  "Helyszín: "    . "Főépület\n" .
  "Cím: "         . "1111 Budapest, Teszt utca 1.\n" .
  "Szoba: "       . ($eszkoz['szobaszam'] ?? '-') . "\n" .
  "Megnevezés: "  . ($eszkoz['nyomtato_tipusa'] ?? '-') . "\n" .
  "Vonalkód: "    . ($eszkoz['vonalkod'] ?? '-') . "\n" .
  "Gyáriszám: "   . ($eszkoz['gyariszam'] ?? '-') . "\n" .
  "Gépkód: "      . ($eszkoz['gep_kod'] ?? '-') . "\n" .
  "Igényelt toner típusa: " . ($eszkoz['toner'] ?? '-') . "\n\n" .
  "Kapcsolattartó neve: Németh István\n" .
  "Kapcsolattartó telefonszáma: +36 30 397 4511\n";

$mailtoTo      = "beszerzes@ceg.hu";
$mailtoSubject = "Fővárosi Törvényszék Toner igénylés";
$mailtoBody    = $orderMsg;

// mailto paraméterek URL-kódolása
$mailtoUrl = "mailto:" . rawurlencode($mailtoTo)
           . "?subject=" . rawurlencode($mailtoSubject)
           . "&body="    . rawurlencode($mailtoBody);

// adjuk át a kliensnek (sesszióba), hogy a render fázisban megnyissuk
$_SESSION['mailto_url'] = $mailtoUrl;

// flash + redirect
$_SESSION['flash'] = ['msg' => "Rendelés rögzítve: {$eszkoz['toner']} ({$eszkoz['nyomtato_tipusa']})", 'type' => 'success'];
header("Location: rendeles.php?vonalkod=".urlencode($vonalkod_post));
exit;

  } catch (Throwable $ex) {
    error_log("Rendelés hiba: ".$ex->getMessage());
    $_SESSION['flash'] = ['msg' => 'Váratlan hiba történt a rendelés rögzítésekor.', 'type' => 'danger'];
    header("Location: rendeles.php?vonalkod=".urlencode($vonalkod_post));
    exit;
  }
}





// POST: megérkezett (státusz frissítés)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_arrived') {
  $order_id = (int)($_POST['order_id'] ?? 0);
  if ($order_id <= 0) {
    $_SESSION['flash'] = ['msg' => 'Ismeretlen rendelési azonosító.', 'type' => 'danger'];
    header("Location: rendeles.php");
    exit;
  }
  try {
    // lekérés
    $st = $pdo->prepare("SELECT * FROM rendelesek WHERE id = :id LIMIT 1");
    $st->execute([':id' => $order_id]);
    $ord = $st->fetch(PDO::FETCH_ASSOC);
    if (!$ord) {
      $_SESSION['flash'] = ['msg' => 'A rendelés nem található.', 'type' => 'danger'];
      header("Location: rendeles.php");
      exit;
    }

    // státusz -> Megjött + idő
    $upd = $pdo->prepare("
      UPDATE rendelesek
      SET statusz = 'Megjött', megerkezes_dt = NOW()
      WHERE id = :id
    ");
    $upd->execute([':id' => $order_id]);

    // opcionális: eszköz raktár státuszát állítsuk 1-re
    try {
      $upd2 = $pdo->prepare("UPDATE eszkozok SET raktaron = 1 WHERE id = :eid");
      $upd2->execute([':eid' => $ord['eszkoz_id']]);
    } catch (Throwable $e) {  }

    $_SESSION['flash'] = ['msg' => "Rendelés megérkezett: {$ord['toner']} ({$ord['vonalkod']})", 'type' => 'success'];
    header("Location: rendeles.php");
    exit;

  } catch (Throwable $ex) {
    error_log("Megjött státusz hiba: ".$ex->getMessage());
    $_SESSION['flash'] = ['msg' => 'Váratlan hiba történt a státusz frissítésekor.', 'type' => 'danger'];
    header("Location: rendeles.php");
    exit;
  }
}

// ====== kimenet ======
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$vonalkod = trim($_GET['vonalkod'] ?? '');
$found = null;
if ($vonalkod !== '') {
  $st = $pdo->prepare("SELECT * FROM eszkozok WHERE vonalkod = :v LIMIT 1");
  $st->execute([':v' => $vonalkod]);
  $found = $st->fetch(PDO::FETCH_ASSOC);
}

// SZŰRŐ: mind / csak „Rendelve” / csak „Megjött”
$filter = $_GET['filter'] ?? 'all';
$where  = '';
$params = [];
if ($filter === 'rendelve') {
  $where = "WHERE statusz = 'Rendelve'";
} elseif ($filter === 'megjott') {
  $where = "WHERE statusz = 'Megjött'";
}
$q = $pdo->prepare("SELECT * FROM rendelesek $where ORDER BY rendeles_dt DESC, id DESC");
$q->execute($params);
$orders = $q->fetchAll(PDO::FETCH_ASSOC);

// fej + navbar
require __DIR__ . '/header.php';
?>

<!-- toast adat -->
<?php if ($flash): ?>
  <div id="flash-data" data-message="<?= e($flash['msg']) ?>" data-type="<?= e($flash['type']) ?>"></div>
<?php endif; ?>

<div class="container page-container my-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-2">Toner rendelés</h1>

    <form class="d-flex gap-2" method="get" action="rendeles.php">
      <select name="filter" class="form-select">
        <option value="all"     <?= $filter==='all'?'selected':'' ?>>Összes</option>
        <option value="rendelve"<?= $filter==='rendelve'?'selected':'' ?>>Csak „Rendelve”</option>
        <option value="megjott" <?= $filter==='megjott'?'selected':'' ?>>Csak „Megjött”</option>
      </select>
      <button class="btn btn-outline-secondary">Szűrés</button>
    </form>
  </div>

  <!-- Rendelés űrlap -->
  <div class="card card-modern mb-3">
    <div class="card-body">
      <h2 class="h5 mb-3">Új rendelés</h2>

      <form class="row g-3" method="get" action="rendeles.php">
        <div class="col-12 col-md-6">
          <label class="form-label">Vonalkód</label>
          <input type="text"
                 class="form-control"
                 id="vonalkodInput"
                 name="vonalkod"
                 list="barcodeHints"
                 value="<?= e($vonalkod) ?>"
                 placeholder="Pl.: LK235345"
                 autocomplete="off">
          <datalist id="barcodeHints"></datalist>
        </div>
        <div class="col-12 col-md-3 align-self-end d-grid">
          <button class="btn btn-outline-primary">Keresés</button>
        </div>
      </form>

      <?php if ($found): ?>
        <hr class="my-4">
        <div class="row g-3">
          <div class="col-12 col-lg-7">
            <div class="p-3 border rounded-3 bg-light">
              <div class="d-flex flex-wrap gap-3">
                <div><span class="text-muted small">Vonalkód</span><div><strong><?= e($found['vonalkod']) ?></strong></div></div>
                <div><span class="text-muted small">Nyomtató</span><div><?= e($found['nyomtato_tipusa']) ?></div></div>
                <div><span class="text-muted small">Toner</span><div class="badge-soft"><?= e($found['toner']) ?></div></div>
                <div><span class="text-muted small">Szoba</span><div><?= e($found['szobaszam']) ?></div></div>
                <div><span class="text-muted small">Raktár státusz</span>
                  <div>
                    <?php if ((int)$found['raktaron'] === 1): ?>
                      <span class="status-pill bg-success text-white">Raktáron</span>
                    <?php else: ?>
                      <span class="status-pill bg-danger text-white">Nincs</span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- rendelés létrehozása -->
          <div class="col-12 col-lg-5">
            <form method="post" class="p-3 border rounded-3">
              <input type="hidden" name="action" value="create_order">
              <input type="hidden" name="vonalkod" value="<?= e($found['vonalkod']) ?>">
              <div class="mb-3">
                <label class="form-label">Megjegyzés (opcionális)</label>
                <input type="text" class="form-control" name="megjegyzes" placeholder="Pl.: sürgős / darabszám">
              </div>
              <button class="btn btn-primary w-100">
                <i class="bi bi-bag-plus me-1"></i> Rendelés rögzítése
              </button>
              <div class="form-text mt-2">A rendelés *Rendelve* státusszal kerül a listába, időbélyeggel.</div>
            </form>
          </div>
        </div>
      <?php elseif ($vonalkod !== ''): ?>
        <hr>
        <div class="alert alert-warning mb-0">
          Nem található eszköz ezzel a vonalkóddal: <strong><?= e($vonalkod) ?></strong>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Rendelések listája -->
  <div class="card card-modern">
    <div class="card-body">
      <h2 class="h5 mb-3">Rendelések</h2>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="text-nowrap">
            <tr>
              <th>#</th>
              <th>Vonalkód</th>
              <th>Nyomtató</th>
              <th>Toner</th>
              <th>Státusz</th>
              <th>Rendelve</th>
              <th>Megjött</th>
              <th class="text-end">Művelet</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($orders): foreach ($orders as $o): ?>
              <tr>
                <td><?= (int)$o['id'] ?></td>
                <td><strong><?= e($o['vonalkod']) ?></strong></td>
                <td><?= e($o['nyomtato_tipusa']) ?></td>
                <td><span class="badge-soft"><?= e($o['toner']) ?></span></td>
                <td>
                  <?php if ($o['statusz'] === 'Rendelve'): ?>
                    <span class="badge bg-warning text-dark rounded-pill px-3 py-2">Rendelve</span>
                  <?php else: ?>
                    <span class="badge bg-success rounded-pill px-3 py-2">Megjött</span>
                  <?php endif; ?>
                </td>
                <td><?= e($o['rendeles_dt']) ?></td>
                <td><?= e($o['megerkezes_dt'] ?? '-') ?></td>
                <td class="text-end">
                  <?php if ($o['statusz'] === 'Rendelve'): ?>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="action" value="mark_arrived">
                      <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                      <button class="btn btn-sm btn-success">
                        <i class="bi bi-check2-circle me-1"></i> Megjött
                      </button>
                    </form>
                  <?php else: ?>
                    <button class="btn btn-sm btn-outline-secondary" disabled>Lezárva</button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="8" class="text-center text-muted py-4">Még nincs rögzített rendelés.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<!-- Élő kereső JS (datalist feltöltés) -->
<script>
  (function(){
    const input = document.getElementById('vonalkodInput');
    const list  = document.getElementById('barcodeHints');
    if (!input || !list) return;

    let lastTerm = '';
    input.addEventListener('input', async () => {
      const term = input.value.trim();
      if (term.length < 2 || term === lastTerm) return;
      lastTerm = term;
      try {
        const res = await fetch('autocomplete.php?term=' + encodeURIComponent(term));
        if (!res.ok) return;
        const data = await res.json();
        list.innerHTML = '';
        data.forEach(v => {
          const opt = document.createElement('option');
          opt.value = v;
          list.appendChild(opt);
        });
      } catch(e) {}
    });
  })();
</script>

<?php if (!empty($_SESSION['mailto_url'])): 
  $mailto = $_SESSION['mailto_url']; unset($_SESSION['mailto_url']); ?>
  <script>
    window.addEventListener('load', function(){
      // Automatikus megnyitás (ha a böngésző engedi)
      window.location.href = <?= json_encode($mailto, JSON_UNESCAPED_SLASHES) ?>;
    });

    document.addEventListener('DOMContentLoaded', function(){
      const wrap = document.createElement('div');
      wrap.className = 'position-fixed bottom-0 end-0 p-3';
      wrap.innerHTML = `<a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($mailto, ENT_QUOTES) ?>">
        ✉️ Levél megnyitása
      </a>`;
      document.body.appendChild(wrap);
    });
  </script>
<?php endif; ?>


<?php require __DIR__ . '/footer.php'; ?>
