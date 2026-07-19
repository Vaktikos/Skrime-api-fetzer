<?php
$apiKey = $_POST['api_key'] ?? '';
$action = $_POST['action'] ?? '';

function apiRequest($url, $apiKey, $postData = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ]);
    if ($postData !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [
        'http_code' => $httpCode,
        'raw'       => $response,
        'data'      => json_decode($response, true),
    ];
}

$products = [];
$results  = [];
$error    = '';

if ($action === 'load_products' && $apiKey) {
    $res      = apiRequest("https://skrime.eu/api/product/all", $apiKey);
    $products = $res['data']['data'] ?? [];
    if (empty($products)) {
        $error = $res['data']['message'] ?? ('HTTP ' . $res['http_code'] . ' — ' . $res['raw']);
    }
}

if ($action === 'renew' && $apiKey) {
    $productId  = $_POST['product_id'] ?? '';
    $countInput = $_POST['renew_count'] ?? '1';
    $renewCount = $countInput === 'custom'
        ? intval($_POST['custom_count'] ?? 1)
        : intval($countInput);
    $renewCount = max(1, min($renewCount, 9999));

    for ($i = 0; $i < $renewCount; $i++) {
        $res       = apiRequest("https://skrime.eu/api/product/renew", $apiKey, ['productId' => $productId]);
        $results[] = $res;
    }
}
?>

<!doctype html>

<html lang="de" dir="ltr">
<head>
    <meta charset="utf-8">
    <title>Skrime Renewer</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/bootstrap-dark.min.css">
    <link rel="stylesheet" href="assets/libs/@mdi/font/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/libs/@iconscout/unicons/css/line.css">
    <link rel="stylesheet" href="assets/css/style-dark.min.css">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5476773857058753"
     crossorigin="anonymous"></script>
  
  <style>
        body { min-height: 100vh; display: flex; flex-direction: column; }
        .page-wrapper { flex: 1; padding-top: 80px; }
        .counter-badge {
            width: 28px; height: 28px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700; flex-shrink: 0;
        }
        .result-item { border-left: 3px solid; border-radius: 4px; overflow: hidden; }
        .result-item .result-header {
            padding: 8px 14px; font-weight: 600; font-size: 13px;
            cursor: pointer; user-select: none;
            display: flex; align-items: center; gap: 8px;
        }
        .result-item .result-raw {
            padding: 10px 14px; font-family: 'Courier New', monospace;
            font-size: 12px; white-space: pre-wrap; word-break: break-all;
            border-top: 1px solid rgba(255,255,255,.08); display: none; color: #adbac7;
        }
        .result-item.result-ok   { border-color: #3fb950; background: rgba(63,185,80,.06); }
        .result-item.result-fail { border-color: #f85149; background: rgba(248,81,73,.06); }
        .result-item.result-ok   .result-header { color: #3fb950; }
        .result-item.result-fail .result-header { color: #f85149; }
        .result-item.open .result-raw { display: block; }
        .api-key-input { font-family: monospace; letter-spacing: .5px; }
    </style>
</head>
<body>

<header id="topnav" class="defaultscroll sticky">
    <div class="container">
        <a class="logo" href="index.php">
            <span class="fw-bold fs-5 text-white">Skrime <span class="text-primary">Renewer</span></span>
        </a>
    </div>
</header>

<div class="page-wrapper">
    <section class="section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-7 col-md-9">


                <div class="card shadow border-0 rounded mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3 gap-2">
                            <span class="counter-badge bg-primary text-white">1</span>
                            <h6 class="mb-0">API Key eingeben</h6>
                        </div>
                        <form method="post">
                            <input type="hidden" name="action" value="load_products">
                            <div class="input-group">
                                <span class="input-group-text"><i data-feather="key" style="width:16px;height:16px;"></i></span>
                                <input type="text" name="api_key" class="form-control api-key-input"
                                       placeholder="dein-skrime-api-key"
                                       value="<?= htmlspecialchars($apiKey) ?>" required>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i data-feather="refresh-cw" style="width:15px;height:15px;" class="me-1"></i>Laden
                                </button>
                            </div>
                        </form>
                        <?php if ($error): ?>
                        <div class="alert alert-danger mt-3 mb-0 py-2 small">
                            <i data-feather="alert-circle" style="width:14px;height:14px;" class="me-1"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($products): ?>
                <div class="card shadow border-0 rounded mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3 gap-2">
                            <span class="counter-badge bg-primary text-white">2</span>
                            <h6 class="mb-0">Produkt & Anzahl wählen</h6>
                            <span class="ms-auto badge bg-soft-primary text-primary"><?= count($products) ?> Produkte</span>
                        </div>
                        <form method="post" id="renewForm">
                            <input type="hidden" name="action" value="renew">
                            <input type="hidden" name="api_key" value="<?= htmlspecialchars($apiKey) ?>">
                            <div class="mb-3">
                                <label class="form-label text-muted small">Produkt</label>
                                <select name="product_id" class="form-select" required>
                                    <?php foreach ($products as $p): ?>
                                    <option value="<?= htmlspecialchars($p['id']) ?>">
                                        <?= htmlspecialchars($p['customName'] ?? $p['id']) ?>
                                        &nbsp;·&nbsp;<?= htmlspecialchars($p['type'] ?? '') ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-muted small">Anzahl Verlängerungen</label>
                                <div class="d-flex gap-2 align-items-center">
                                    <select name="renew_count" class="form-select" id="renewCount" style="max-width:200px;">
                                        <option value="1">1×</option>
                                        <option value="10">10×</option>
                                        <option value="100">100×</option>
                                        <option value="1000">1.000×</option>
                                        <option value="custom">Benutzerdefiniert</option>
                                    </select>
                                    <input type="number" name="custom_count" id="customCount"
                                           class="form-control" placeholder="Anzahl" min="1"
                                           style="max-width:140px; display:none;">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" id="renewBtn">
                                <i data-feather="zap" style="width:15px;height:15px;" class="me-1"></i>
                                Jetzt verlängern
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($results): ?>
                <?php
                $ok   = count(array_filter($results, fn($r) => !empty($r['data']['success'])));
                $fail = count($results) - $ok;
                ?>
                <div class="card shadow border-0 rounded">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3 gap-2">
                            <span class="counter-badge bg-success text-white">✓</span>
                            <h6 class="mb-0">Ergebnisse</h6>
                            <span class="ms-auto small">
                                <span class="text-success fw-semibold"><?= $ok ?> OK</span>
                                <?php if ($fail): ?>
                                &nbsp;·&nbsp;<span class="text-danger fw-semibold"><?= $fail ?> Fehler</span>
                                <?php endif; ?>
                                &nbsp;<span class="text-muted">· klicken für Details</span>
                            </span>
                        </div>
                        <div class="d-flex flex-column gap-2" style="max-height:500px; overflow-y:auto;">
                            <?php foreach ($results as $i => $r): ?>
                            <?php
                            $isOk   = !empty($r['data']['success']);
                            $expire = $r['data']['data']['expireDate'] ?? null;
                            $msg    = $r['data']['message'] ?? '';
                            $pretty = json_encode($r['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                            ?>
                            <div class="result-item <?= $isOk ? 'result-ok' : 'result-fail' ?>" onclick="this.classList.toggle('open')">
                                <div class="result-header">
                                    <span>#<?= $i + 1 ?> &nbsp;<?= $isOk ? '✓ Verlängert' : '✗ Fehler' ?></span>
                                    <?php if ($expire): ?>
                                    <span class="ms-1 text-muted small fw-normal">· Läuft ab: <?= htmlspecialchars($expire) ?></span>
                                    <?php elseif ($msg): ?>
                                    <span class="ms-1 text-muted small fw-normal">· <?= htmlspecialchars($msg) ?></span>
                                    <?php endif; ?>
                                    <span class="ms-auto text-muted small fw-normal">HTTP <?= $r['http_code'] ?> ▾</span>
                                </div>
                                <div class="result-raw"><?= htmlspecialchars($pretty ?: $r['raw']) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>


</div>

<footer class="footer footer-bar">
    <div class="container text-center">
        <p class="mb-0 text-muted small">Skrime Renewer &mdash; <?= date('Y') ?></p>
    </div>
</footer>

<script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>

<script src="assets/libs/feather-icons/feather.min.js"></script>

<script src="assets/js/plugins.init.js"></script>

<script src="assets/js/app.js"></script>

<script>
feather.replace();

const sel    = document.getElementById('renewCount');
const custom = document.getElementById('customCount');

if (sel) {
    sel.addEventListener('change', () => {
        const show = sel.value === 'custom';
        custom.style.display = show ? 'block' : 'none';
        custom.required = show;
    });
}

const form = document.getElementById('renewForm');
if (form) {
    form.addEventListener('submit', function() {
        const btn = document.getElementById('renewBtn');
        setTimeout(() => {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Läuft...';
        }, 50);
    });
}
</script>

</body>
</html>
