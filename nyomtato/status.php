<?php
// ===== Beállítások =====
$SOURCE_DIR = __DIR__ . '/printer_exports'; // ide rakod a TXT/CSV exportokat
$ALLOW_EXTS = ['txt','csv'];
$THRESHOLD  = 20; // % alatt kerül az e-mailbe
$MAIL_TO    = 'beszerzes@ceg.hu';
$CONTACT    = [
  'name'  => 'Németh István',
  'phone' => '+36 30 397 4511',
];

// ===== Session a flashhez és mailto-hoz =====
session_start();

// ===== Helper =====
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function find_latest_file(string $dir, array $exts): ?string {
  if (!is_dir($dir)) return null;
  $files = [];
  foreach ($exts as $ext) {
    foreach (glob(rtrim($dir,'/\\') . '/*.' . $ext) as $p) {
      $files[] = ['p'=>$p, 't'=> @filemtime($p) ?: 0];
    }
  }
  if (!$files) return null;
  usort($files, fn($a,$b)=> $b['t'] <=> $a['t']);
  return $files[0]['p'];
}

function detect_delimiter(string $line): string {
  $c = substr_count($line, ',');
  $s = substr_count($line, ';');
  $t = substr_count($line, "\t");
  $max = max($c,$s,$t);
  return $max===$s ? ';' : ($max===$t ? "\t" : ',');
}

function parse_csv_with_header(string $file): array {
  $fh = @fopen($file, 'r');
  if (!$fh) throw new RuntimeException("Nem sikerült megnyitni: $file");
  $first = fgets($fh);
  if ($first===false){ fclose($fh); return []; }
  $delim = detect_delimiter($first);
  rewind($fh);
  $header = fgetcsv($fh, 0, $delim);
  if (!$header){ fclose($fh); return []; }
  $header = array_map(fn($h)=> preg_replace('/^"(.*)"$/','$1', trim((string)$h)), $header);
  $rows=[];
  while(($row=fgetcsv($fh,0,$delim))!==false){
    if (count($row) < count($header)) $row = array_pad($row, count($header), '');
    $assoc=[];
    foreach($header as $i=>$name){
      $v = $row[$i] ?? '';
      if (is_string($v)) $v = preg_replace('/^"(.*)"$/','$1',$v);
      $assoc[$name] = $v;
    }
    if (implode('', $assoc) === '') continue;
    $rows[] = $assoc;
  }
  fclose($fh);
  return $rows;
}

function extract_supplies(array $row): array {
  $out=[];
  foreach($row as $k=>$v){
    if (stripos($k,'Supplies.')===0){
      $num = null;
      if ($v!=='' && $v!==null){
        if (preg_match('/(\d+(?:[\.,]\d+)?)/', (string)$v, $m)){
          $num = (float)str_replace(',', '.', $m[1]);
          if ($num<0) $num=0; if ($num>100) $num=100;
        }
      }
      $out[$k]=$num; // lehet null is
    }
  }
  return $out;
}

// ===== POST: email draft készítés a 20% alattiakról =====
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='make_low_email') {
  $latest = find_latest_file($SOURCE_DIR, $ALLOW_EXTS);
  if (!$latest) {
    $_SESSION['flash'] = ['msg'=>'Nincs elérhető export fájl a mappában.', 'type'=>'danger'];
    header("Location: ".$_SERVER['PHP_SELF']); exit;
  }
  try {
    $rows = parse_csv_with_header($latest);
    $lines=[];
    foreach($rows as $r){
      $sup = extract_supplies($r);
      $lowParts=[];
      foreach($sup as $name=>$val){
        if ($val!==null && $val < $THRESHOLD){
          // rövid label (szín)
          $label = preg_replace('/^Supplies\.(All|.*)\./i','', $name);
          $lowParts[] = $label . ': ' . (int)round($val) . '%';
        }
      }
      if ($lowParts){
        $disp = $r['DisplayName'] ?? '-';
        $ip   = $r['IPv4Addres'] ?? '-';
        $sn   = $r['SerialNumber'] ?? '-';
        $lines[] = "- {$disp} (IP: {$ip}, SN: {$sn}) — " . implode(', ', $lowParts);
      }
    }

    if (!$lines){
      $_SESSION['flash'] = ['msg'=>"Nincs {$THRESHOLD}% alatti toner a legfrissebb fájlban.", 'type'=>'warning'];
      header("Location: ".$_SERVER['PHP_SELF']); exit;
    }

    $subject = "Toner riasztás – {$THRESHOLD}% alatti eszközök (" . date('Y-m-d H:i') . ")";
    $body =
      "Az alábbi nyomtatóknál {$THRESHOLD}% alatti toner szintet észleltünk.\n".
      "Forrás fájl: ".basename($latest)." (módosítva: ".date('Y-m-d H:i', @filemtime($latest)).")\n\n".
      implode("\n", $lines)."\n\n".
      "Kapcsolattartó neve: {$CONTACT['name']}\n".
      "Kapcsolattartó telefonszáma: {$CONTACT['phone']}\n";

    // mailto draft
    $mailtoUrl = "mailto:".rawurlencode($MAIL_TO)
               . "?subject=".rawurlencode($subject)
               . "&body="   .rawurlencode($body);

    $_SESSION['mailto_url'] = $mailtoUrl;
    $_SESSION['flash'] = ['msg'=>"Összeállítottam az e-mailt (".count($lines)." tétel).", 'type'=>'success'];
    header("Location: ".$_SERVER['PHP_SELF']); exit;

  } catch(Throwable $ex){
    error_log("Low toner email build error: ".$ex->getMessage());
    $_SESSION['flash'] = ['msg'=>'Hiba történt az e-mail összeállításakor.', 'type'=>'danger'];
    header("Location: ".$_SERVER['PHP_SELF']); exit;
  }
}

// ===== Megjelenítés: legfrissebb fájl beolvasása és tábla =====
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$latest = find_latest_file($SOURCE_DIR, $ALLOW_EXTS);
$error  = null; $rows = [];

try {
  if (!$latest) throw new RuntimeException("Nem találtam TXT/CSV fájlt a mappában: " . $SOURCE_DIR);
  $rows = parse_csv_with_header($latest);
} catch (Throwable $ex) {
  $error = $ex->getMessage();
}

// ===== UI =====
require __DIR__ . '/header.php';
?>
<div class="container page-container my-4">
  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <h1 class="h4 mb-1">Toner szintek</h1>
      <div class="text-muted small">Küszöb: <?= e($THRESHOLD) ?>%</div>
    </div>
    <div class="text-end">
      <?php if ($latest): ?>
        <div class="small text-muted mb-2">
          Forrás: <code><?= e(basename($latest)) ?></code><br>
          Módosítva: <?= e(date('Y-m-d H:i', @filemtime($latest))) ?>
        </div>
      <?php endif; ?>
      <form method="post">
        <input type="hidden" name="action" value="make_low_email">
        <button class="btn btn-outline-primary">
          <i class="bi bi-envelope me-1"></i> 20% alattiak – e-mail
        </button>
      </form>
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
  <?php elseif (!$rows): ?>
    <div class="alert alert-warning">A fájl üres vagy nem értelmezhető.</div>
  <?php else: ?>
    <div class="card card-modern">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table align-middle table-hover">
            <thead class="text-nowrap">
              <tr>
                <th>DisplayName</th>
                <th>IPv4Addres</th>
                <th>SerialNumber</th>
                <th>MacAddres</th>
                <th>PageCount</th>
                <th>Színek</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r):
                $supplies = extract_supplies($r);
                uksort($supplies, function($a,$b){
                  $order = ['Black'=>1,'Cyan'=>2,'Magenta'=>3,'Yellow'=>4];
                  $rank = function($k) use($order){ foreach($order as $n=>$i){ if (stripos($k,$n)!==false) return $i; } return 99; };
                  return $rank($a) <=> $rank($b);
                });
              ?>
              <tr>
                <td><?= e($r['DisplayName'] ?? '-') ?></td>
                <td><?= e($r['IPv4Addres'] ?? '-') ?></td>
                <td><?= e($r['SerialNumber'] ?? '-') ?></td>
                <td><?= e($r['MacAddres'] ?? '-') ?></td>
                <td><?= e($r['PageCount'] ?? '-') ?></td>
                <td style="min-width:320px;">
                  <?php if (!$supplies): ?>
                    <span class="text-muted">Nincs toner adat.</span>
                  <?php else: foreach ($supplies as $name=>$val):
                    $label = preg_replace('/^Supplies\.(All|.*)\./i','', $name);
                    $pct   = is_null($val) ? null : (int)round($val);
                    $bar   = 'bg-success';
                    if ($pct !== null) {
                      if ($pct < 20) $bar='bg-danger';
                      elseif ($pct < 50) $bar='bg-warning';
                    }
                  ?>
                    <div class="mb-2">
                      <div class="d-flex justify-content-between">
                        <span class="small text-muted"><?= e($label) ?></span>
                        <span class="small"><?= is_null($pct) ? '–' : ($pct.'%') ?></span>
                      </div>
                      <div class="progress" role="progressbar" aria-valuenow="<?= e($pct ?? 0) ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar <?= $bar ?>" style="width: <?= e($pct ?? 0) ?>%"></div>
                      </div>
                    </div>
                  <?php endforeach; endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        
      </div>
    </div>
  <?php endif; ?>
</div>

<?php if (!empty($_SESSION['mailto_url'])):
  $mailto = $_SESSION['mailto_url']; unset($_SESSION['mailto_url']); ?>
  <script>
    window.addEventListener('load', function(){
      // automatikus megnyitás (ha a böngésző engedi)
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
