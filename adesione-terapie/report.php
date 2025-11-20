<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/models/TableResolver.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$token = $_GET['token'] ?? '';
if (!$token) {
    http_response_code(400);
    exit('Token mancante.');
}

try {
    $reportsTable = AdesioneTableResolver::resolve('reports');
    $reportCols = [
        'token' => 'share_token',
        'content' => 'content',
        'valid_until' => 'valid_until',
        'pin_code' => 'pin_code',
        'created_at' => 'created_at',
    ];

    $report = db_fetch_one(
        "SELECT * FROM `{$reportsTable}` WHERE `{$reportCols['token']}` = ?",
        [$token]
    );

    if (!$report) {
        http_response_code(404);
        exit('Report non trovato o non più disponibile.');
    }

    if ($reportCols['valid_until'] && !empty($report[$reportCols['valid_until']])) {
        if (strtotime($report[$reportCols['valid_until']]) < time()) {
            http_response_code(410);
            exit('Il report è scaduto. Contatta la farmacia per un nuovo link.');
        }
    }

    $pinHash = $reportCols['pin_code'] ? ($report[$reportCols['pin_code']] ?? '') : '';
    $pinVerified = !$pinHash;
    $error = null;

    if ($pinHash) {
        $_SESSION['report_tokens'] = $_SESSION['report_tokens'] ?? [];
        if (!empty($_SESSION['report_tokens'][$token])) {
            $pinVerified = true;
        }

        if (!$pinVerified && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $pin = $_POST['pin'] ?? '';
            if ($pin && password_verify($pin, $pinHash)) {
                $_SESSION['report_tokens'][$token] = true;
                $pinVerified = true;
            } else {
                $error = 'PIN non corretto. Riprova.';
            }
        }
    }

    $content = [];
    if ($reportCols['content'] && !empty($report[$reportCols['content']])) {
        $decoded = json_decode($report[$reportCols['content']], true);
        if (is_array($decoded)) {
            $content = $decoded;
        }
    }
} catch (Throwable $e) {
    http_response_code(500);
    exit('Errore durante il caricamento del report.');
}

$therapy = $content['therapy'] ?? [];
$checks = $content['checks'] ?? [];
$generatedAt = $content['generated_at'] ?? date('Y-m-d H:i:s');

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Terapia - Assistente Farmacia</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #f5f7fb;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }
        .report-container {
            max-width: 960px;
            margin: 3rem auto;
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }
        .report-header {
            background: linear-gradient(135deg, #0d6efd, #6610f2);
            color: #fff;
            padding: 2.5rem 3rem;
        }
        .report-body {
            padding: 2.5rem 3rem;
        }
        .badge-status {
            border-radius: 999px;
            padding: 0.35rem 0.75rem;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .timeline-check {
            border-left: 3px solid rgba(13, 110, 253, 0.25);
            padding-left: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .timeline-check h6 {
            font-weight: 600;
            color: #0d6efd;
        }
        .section-title {
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.08em;
            color: #6c757d;
            margin-bottom: 0.75rem;
        }
        .consent-box {
            background: #f8f9ff;
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid rgba(13, 110, 253, 0.1);
        }
        .pin-form {
            max-width: 420px;
            margin: 5rem auto;
            background: #fff;
            border-radius: 18px;
            padding: 2.5rem;
            box-shadow: 0 25px 40px rgba(15, 23, 42, 0.08);
        }
    </style>
</head>
<body>
<?php if (!$pinVerified): ?>
    <div class="container">
        <form method="post" class="pin-form">
            <h1 class="h4 mb-3">Inserisci il PIN di sicurezza</h1>
            <p class="text-muted">Questo report è protetto. Inserisci il PIN comunicato dalla farmacia per visualizzarlo.</p>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label">PIN</label>
                <input type="password" name="pin" class="form-control" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary w-100">Sblocca report</button>
        </form>
    </div>
<?php else: ?>
    <div class="report-container">
        <header class="report-header">
            <p class="text-uppercase small mb-2">Assistente Farmacia</p>
            <h1 class="h3 mb-1">Report percorso terapeutico</h1>
            <p class="mb-0">Generato il <?= date('d/m/Y H:i', strtotime($generatedAt)) ?></p>
        </header>
        <div class="report-body">
            <section class="mb-4">
                <div class="section-title">Dati paziente</div>
                <h2 class="h4 mb-1"><?= htmlspecialchars($therapy['patient_name'] ?? 'Paziente non disponibile') ?></h2>
                <div class="d-flex flex-wrap gap-3 text-muted">
                    <span><strong>Telefono:</strong> <?= htmlspecialchars($therapy['patient_phone'] ?? 'N/D') ?></span>
                    <span><strong>Email:</strong> <?= htmlspecialchars($therapy['patient_email'] ?? 'N/D') ?></span>
                </div>
            </section>

            <section class="mb-4">
                <div class="section-title">Dettagli terapia</div>
                <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                    <span class="badge-status bg-primary-subtle text-primary">Stato: <?= htmlspecialchars($therapy['status'] ?? 'N/A') ?></span>
                    <span><strong>Inizio:</strong> <?= htmlspecialchars($therapy['start_date'] ?? 'N/D') ?></span>
                    <span><strong>Fine:</strong> <?= htmlspecialchars($therapy['end_date'] ?? 'N/D') ?></span>
                </div>
                <p><?= nl2br(htmlspecialchars($therapy['description'] ?? '')) ?></p>
            </section>

            <section class="mb-4">
                <div class="section-title">Caregiver e familiari</div>
                <?php if (!empty($therapy['caregivers'])): ?>
                    <ul class="list-group">
                        <?php foreach ($therapy['caregivers'] as $caregiver): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?= htmlspecialchars($caregiver['name'] ?? 'Caregiver') ?></strong>
                                    <div class="text-muted small"><?= htmlspecialchars($caregiver['relationship'] ?? '') ?></div>
                                </div>
                                <span class="small text-muted"><?= htmlspecialchars($caregiver['phone'] ?? '') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted">Non sono stati registrati caregiver per questa terapia.</p>
                <?php endif; ?>
            </section>

            <section class="mb-4">
                <div class="section-title">Questionario di adesione</div>
                <?php if (!empty($therapy['questionnaire'])): ?>
                    <?php foreach ($therapy['questionnaire'] as $step => $answers): ?>
                        <div class="mb-3">
                            <h6 class="fw-semibold">Step <?= htmlspecialchars($step) ?></h6>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($answers as $question => $answer): if (!$answer) continue; ?>
                                    <li class="list-group-item">
                                        <strong><?= htmlspecialchars($question) ?>:</strong>
                                        <div><?= nl2br(htmlspecialchars($answer)) ?></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">Questionario non disponibile.</p>
                <?php endif; ?>
            </section>

            <section class="mb-4">
                <div class="section-title">Check periodici</div>
                <?php if (!empty($checks)): ?>
                    <?php foreach ($checks as $check): ?>
                        <div class="timeline-check">
                            <h6><?= date('d/m/Y H:i', strtotime($check['scheduled_at'] ?? 'now')) ?></h6>
                            <p class="mb-1"><strong>Valutazione:</strong> <?= nl2br(htmlspecialchars($check['assessment'] ?? '')) ?></p>
                            <?php if (!empty($check['actions'])): ?>
                                <p class="small mb-0"><strong>Azioni:</strong> <?= nl2br(htmlspecialchars($check['actions'])) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($check['notes'])): ?>
                                <p class="small text-muted mb-0">Note: <?= nl2br(htmlspecialchars($check['notes'])) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">Non sono stati registrati controlli periodici.</p>
                <?php endif; ?>
            </section>

            <section class="consent-box">
                <div class="section-title mb-2">Consenso informato</div>
                <?php if (!empty($therapy['consent']['signature_type'])): ?>
                    <p class="mb-1"><strong>Tipo di firma:</strong> <?= htmlspecialchars($therapy['consent']['signature_type']) ?></p>
                <?php endif; ?>
                <?php if (!empty($therapy['consent']['signed_at'])): ?>
                    <p class="mb-1"><strong>Firmato il:</strong> <?= date('d/m/Y H:i', strtotime($therapy['consent']['signed_at'])) ?></p>
                <?php endif; ?>
                <?php if (!empty($therapy['consent']['signature_image'])): ?>
                    <div class="mt-3">
                        <p class="small text-muted mb-2">Firma grafica</p>
                        <img src="<?= htmlspecialchars($therapy['consent']['signature_image']) ?>" alt="Firma" style="max-width: 320px; border:1px solid rgba(13,110,253,0.2); border-radius:12px;">
                    </div>
                <?php endif; ?>
                <?php if (!empty($therapy['consent']['signature_text'])): ?>
                    <p class="mb-0"><strong>Firma digitale:</strong> <?= htmlspecialchars($therapy['consent']['signature_text']) ?></p>
                <?php endif; ?>
            </section>
        </div>
    </div>
<?php endif; ?>
</body>
</html>
