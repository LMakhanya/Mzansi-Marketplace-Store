<head>
    <?php
    $request = $_SERVER['REQUEST_URI'];
    $parsedUrl = parse_url($request);
    $path = ltrim($parsedUrl['path'] ?? '', '/'); // Get the path part without leading slash
    $IPATH = $_SERVER["DOCUMENT_ROOT"] . "/assets/includes/";
    include($IPATH . "metadata.php"); ?>
    <link rel="stylesheet" href="/styles/pay.css">
</head>