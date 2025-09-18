<?php
require __DIR__ . '/db.php';
require __DIR__ . '/header.php';


$stmt = $pdo->query("SELECT * FROM eszkozok ORDER BY szobaszam, nyomtato_tipusa");
$rows = $stmt->fetchAll();

// Segédfüggvény a biztonságos kiíráshoz
function e($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }


?>


        
        
      </div>
    </div>
  </nav>

  <!-- TARTALOM -->
  <div class="container page-container my-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
      <div>
        <h1 class="h3 mb-1">Nyomtatók listája</h1>
      </div>
      <div class="d-flex controls">
        
      </div>
    </div>

    <div class="card card-modern">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-modern table-hover table-striped align-middle">
            <thead class="text-nowrap">
              <tr>
                <th style="min-width: 220px;">Vonalkód</th>
                <th>Gyáriszám</th>
                <th>Gépkód</th>
                <th>Nyomtató típusa</th>
                <th>Toner</th>
                <th>IP cím</th>
                <th>Szobaszám</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td>
                    <div class="d-flex flex-column">
                      <strong><?= e($r['vonalkod']) ?></strong>
                    </div>
                  </td>
                  <td><span class="badge-soft"><?= e($r['gyariszam']) ?></span></td>
                  <td><?= e($r['gep_kod']) ?></td>
                  <td><?= e($r['nyomtato_tipusa']) ?></td>
                  <td><?= e($r['toner']) ?></td>
                  <td><?= e($r['ip_cim']) ?></td>
                  <td><?= e($r['szobaszam']) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="text-center text-muted py-5">Nincs megjeleníthető adat.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>

 <?php 
 require __DIR__ . '/footer.php';
 ?>
