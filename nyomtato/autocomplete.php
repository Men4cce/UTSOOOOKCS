<?php
require __DIR__ . '/db.php';
header('Content-Type: application/json; charset=utf-8');

$term = trim($_GET['term'] ?? '');
if ($term === '') { echo json_encode([]); exit; }

// Vonalkód javaslatok
$sql = "
  SELECT vonalkod
  FROM eszkozok
  WHERE vonalkod LIKE :starts OR vonalkod LIKE :any
  ORDER BY vonalkod
  LIMIT 10
";
$st = $pdo->prepare($sql);
$st->execute([
  ':starts' => $term.'%',
  ':any'    => '%'.$term.'%'
]);

$out = [];
while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
  $out[] = $row['vonalkod'];
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
