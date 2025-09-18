<?php 
// ===== NAVBAR ACTIVE =====
$current = basename($_SERVER['PHP_SELF']);
function navActive($file) {
  global $current;
  return $current === $file ? 'active' : '';
}
?>

<!doctype html>
<html lang="hu">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nyomtató kezelő</title>

  <!-- Bootstrap -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
    rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    rel="stylesheet">

  
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-modern sticky-top">
    <div class="container">
      <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="index.php">
        <i class="bi bi-printer"></i> Nyomtatókezelő
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="mainNav">
        <ul class="navbar-nav me-auto gap-lg-1">
          <li class="nav-item"><a class="nav-link <?= navActive('index.php') ?>" href="index.php"><i class="bi bi-table"></i> Lista</a></li>
          <li class="nav-item"><a class="nav-link <?= navActive('raktaron.php') ?>" href="raktaron.php"><i class="bi bi-box-seam"></i> Raktáron</a></li>
          <li class="nav-item"><a class="nav-link <?= navActive('kiadas.php') ?>" href="kiadas.php"><i class="bi bi-arrow-left-right"></i> Kiadás</a></li>
          <li class="nav-item"><a class="nav-link <?= navActive('kiadas.php') ?>" href="rendeles.php"><i class="bi bi-cart me-1"></i> Rendelés</a></li>
        </ul>
      </div>
    </div>
  </nav>