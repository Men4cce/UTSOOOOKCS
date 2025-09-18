<?php

session_start();

// Kapcsolat
require __DIR__ . '/db.php';

// hibák exceptionként
try { $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); } catch (Throwable $e) {}

// Segédfüggvény
function e($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

// Kiadási napló tábla biztosítása (ha még nincs)
try {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS kiadasok (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      eszkoz_id INT UNSIGNED NOT NULL,
      vonalkod VARCHAR(64) NOT NULL,
      nyomtato_tipusa VARCHAR(128) NOT NULL,
      toner VARCHAR(128) NOT NULL,
      atvevo_nev VARCHAR(128) NOT NULL,
      kiadas_dt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX (eszkoz_id)
    ) ENGINE=InnoDB
  ");
} catch (Throwable $e) {
  error_log('kiadasok tabla letrehozas hiba: '.$e->getMessage());
}

// POST feldolgozás (minden HTML előtt!)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'kiadas') {
  $vonalkod_post = trim($_POST['vonalkod'] ?? '');
  $atvevo_nev    = trim($_POST['atvevo_nev'] ?? '');

  if ($vonalkod_post === '' || $atvevo_nev === '') {
    $_SESSION['flash'] = ['msg' => "Add meg a vonalkódot és az átvevő nevét.", 'type' => 'danger'];
    header("Location: kiadas.php");
    exit;
  }

  try {
    // Eszköz lekérése
    $st = $pdo->prepare("SELECT * FROM eszkozok WHERE vonalkod = :v LIMIT 1");
    $st->execute([':v' => $vonalkod_post]);
    $eszkoz = $st->fetch(PDO::FETCH_ASSOC);

    if (!$eszkoz) {
      $_SESSION['flash'] = ['msg' => "Nem található eszköz ezzel a vonalkóddal: ".$vonalkod_post, 'type' => 'danger'];
      header("Location: kiadas.php?vonalkod=" . urlencode($vonalkod_post));
      exit;
    }

    $was = (int)$eszkoz['raktaron']; // 0 vagy 1

    // Napló beírása MINDENKÉPP (akkor is, ha DB szerint 0 volt)
    $ins = $pdo->prepare("
      INSERT INTO kiadasok (eszkoz_id, vonalkod, nyomtato_tipusa, toner, atvevo_nev)
      VALUES (:id, :vonalkod, :tipus, :toner, :nev)
    ");
    $ins->execute([
      ':id'       => $eszkoz['id'],
      ':vonalkod' => $eszkoz['vonalkod'],
      ':tipus'    => $eszkoz['nyomtato_tipusa'],
      ':toner'    => $eszkoz['toner'],
      ':nev'      => $atvevo_nev
    ]);

    // Eszköz státusz 0-ra (akkor is, ha már 0)
    $upd = $pdo->prepare("UPDATE eszkozok SET raktaron = 0 WHERE id = :id");
    $upd->execute([':id' => $eszkoz['id']]);

    // Üzenet
    if ($was === 1) {
      $_SESSION['flash'] = ['msg' => "Sikeres kiadás: {$eszkoz['toner']} → {$atvevo_nev}", 'type' => 'success'];
    } else {
      $_SESSION['flash'] = ['msg' => "Kiadás rögzítve (a DB szerint már nem volt raktáron). {$eszkoz['toner']} → {$atvevo_nev}", 'type' => 'warning'];
    }

    header("Location: kiadas.php?vonalkod=" . urlencode($vonalkod_post));
    exit;

  } catch (Throwable $ex) {
    error_log("Kiadás hiba: ".$ex->getMessage());
    $_SESSION['flash'] = ['msg' => "Váratlan hiba történt a kiadás során.", 'type' => 'danger'];
    header("Location: kiadas.php?vonalkod=" . urlencode($vonalkod_post));
    exit;
  }
}

// ===== kimenet =====

// 6) Flash kiolvasás (egyszer használjuk)
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// 7) Keresés (GET-ből)
$vonalkod = trim($_GET['vonalkod'] ?? '');
$found = null;
if ($vonalkod !== '') {
  $st = $pdo->prepare("SELECT * FROM eszkozok WHERE vonalkod = :v LIMIT 1");
  $st->execute([':v' => $vonalkod]);
  $found = $st->fetch(PDO::FETCH_ASSOC);
}

// 8) Legutóbbi kiadások
$last = [];
try {
  $last = $pdo->query("SELECT * FROM kiadasok ORDER BY kiadas_dt DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $last = [];
}

// 9) Fejléc / navbar
require __DIR__ . '/header.php';
?>

<!-- rejtett flash-data div, a footer toast olvassa -->
<?php if ($flash): ?>
  <div id="flash-data" data-message="<?= e($flash['msg']) ?>" data-type="<?= e($flash['type']) ?>"></div>
<?php endif; ?>

<div class="container page-container my-4">
  <h1 class="h3 mb-3">Toner kiadás</h1>

  <!-- KERESŐ + KIADÁS ŰRLAP -->
  <div class="card card-modern mb-3">
    <div class="card-body">
      <form class="row g-3" method="get" action="kiadas.php">
        <div class="col-12 col-md-6">
          <label class="form-label">Vonalkód</label>
          <input type="text"
                 class="form-control"
                 id="vonalkodInput"
                 name="vonalkod"
                 list="barcodeHints"
                 value="<?= e($vonalkod) ?>"
                 placeholder="Pl.: LK235345"
                 autocomplete="off"
                 required>
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
          <h2 class="h5 mb-2">Talált nyomtató</h2>
          <div class="p-3 border rounded-3 bg-light">
            <div class="d-flex flex-wrap gap-3">
              <div><span class="text-muted small">Vonalkód</span><div><strong><?= e($found['vonalkod']) ?></strong></div></div>
              <div><span class="text-muted small">Típus</span><div><?= e($found['nyomtato_tipusa']) ?></div></div>
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

        <!-- KIADÁS FORM -->
        <div class="col-12 col-lg-5">
          <h2 class="h5 mb-2">Kiadás rögzítése</h2>
          <form method="post" class="p-3 border rounded-3">
            <input type="hidden" name="action" value="kiadas">
            <input type="hidden" name="vonalkod" value="<?= e($found['vonalkod']) ?>">
            <div class="mb-3">
              <label class="form-label">Átvevő neve</label>
              <input type="text" class="form-control" name="atvevo_nev" placeholder="Pl.: Kovács Péter" required>
            </div>
            <button class="btn btn-primary w-100">
              <i class="bi bi-check2-circle me-1"></i> Kiadás mentése
            </button>
            <div class="form-text mt-2">Mentés után az eszköz raktár státusza „Nincs”-re vált.</div>
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

  <!-- LEGUTÓBBI KIADÁSOK -->
  <div class="card card-modern">
    <div class="card-body">
      <h2 class="h5 mb-3">Legutóbbi kiadások</h2>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="text-nowrap">
            <tr>
              <th>Időpont</th>
              <th>Vonalkód</th>
              <th>Nyomtató típusa</th>
              <th>Toner</th>
              <th>Átvevő</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($last): foreach ($last as $k): ?>
              <tr>
                <td><?= e($k['kiadas_dt']) ?></td>
                <td><strong><?= e($k['vonalkod']) ?></strong></td>
                <td><?= e($k['nyomtato_tipusa']) ?></td>
                <td><span class="badge-soft"><?= e($k['toner']) ?></span></td>
                <td><?= e($k['atvevo_nev']) ?></td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="5" class="text-center text-muted py-4">Még nincs rögzített kiadás.</td></tr>
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
        const data = await res.json(); // ["LK235345","LK235346", ...]
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

<?php require __DIR__ . '/footer.php'; ?>
