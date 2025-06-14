<?php
require_once __DIR__ . '/api/utils/Response.php';

// Composer installed via npm/composer
require_once __DIR__ . '/../vendor/autoload.php';

$parsedown = new Parsedown();
$markdown = file_get_contents(__DIR__ . '/api/docs/README.md');
$html = $parsedown->text($markdown);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wave API Documentation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bs-body-line-height: 1.6;
        }
        body {
            font-family: -apple-system, system-ui, BlinkMacSystemFont, "Segoe UI", Roboto, Ubuntu;
            color: #1a1a1a;
            line-height: 1.6;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        h1, h2, h3, h4 {
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        h1 { font-size: 2.5rem; }
        h2 { 
            font-size: 1.75rem;
            padding-bottom: .5rem;
            border-bottom: 1px solid #eaecef;
        }
        h3 { font-size: 1.5rem; }
        h4 { font-size: 1.25rem; }
        pre {
            background: #f6f8fa;
            padding: 1rem;
            border-radius: 6px;
            margin: 1rem 0;
            overflow: auto;
        }
        code {
            font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
            background: #f6f8fa;
            padding: 0.2em 0.4em;
            border-radius: 3px;
            font-size: 85%;
        }
        pre code {
            background: none;
            padding: 0;
        }
        table {
            width: 100%;
            margin: 1rem 0;
            border-collapse: collapse;
        }
        th, td {
            padding: 0.75rem;
            border: 1px solid #dfe2e5;
        }
        th {
            background: #f6f8fa;
        }
        blockquote {
            margin: 1rem 0;
            padding: 0 1rem;
            color: #6a737d;
            border-left: 0.25rem solid #dfe2e5;
        }
        p, ul, ol {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php echo $html; ?>
</body>
</html>
