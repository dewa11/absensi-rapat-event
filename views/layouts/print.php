<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars((string) ($title ?? 'Ekspor'), ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-size: 13px;
            color: #212529;
        }

        .print-wrapper {
            max-width: 960px;
            margin: 0 auto;
            padding: 1.5rem 2rem;
        }

        .event-section {
            page-break-inside: avoid;
        }

        .event-section + .event-section {
            page-break-before: always;
            margin-top: 0;
            padding-top: 1.5rem;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                margin: 0;
                padding: 0;
            }

            .print-wrapper {
                max-width: 100%;
                padding: 0;
            }

            @page {
                size: A4 portrait;
                margin: 15mm 15mm 15mm 15mm;
            }

            table {
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
<div class="print-wrapper">
    <?= (string) ($content ?? '') ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
