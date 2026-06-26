<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';

// Validate the requested image record ID.
$imageId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Return a local fallback image whenever the requested image cannot be served.
function showFallback(): never
{
    $fallback = __DIR__ . '/assets/img/dress.png';

    if (is_file($fallback)) {
        header('Content-Type: image/png');
        header('Content-Length: ' . filesize($fallback));
        readfile($fallback);
    } else {
        http_response_code(404);
    }

    exit;
}

if (!$imageId || $imageId < 1) {
    showFallback();
}

// Load the requested image record from the database.
try {
    $stmt = $pdo->prepare(
        'SELECT image_path, image_data, mime_type
         FROM product_images
         WHERE id = ?
         LIMIT 1'
    );
    $stmt->execute([$imageId]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$image) {
        showFallback();
    }

    // Support images stored directly in the database.
    if (!empty($image['image_data'])) {
        $mimeType = !empty($image['mime_type']) ? $image['mime_type'] : 'image/jpeg';
        header('Content-Type: ' . $mimeType);
        echo $image['image_data'];
        exit;
    }

    $path = trim((string)($image['image_path'] ?? ''));
    if ($path === '') {
        showFallback();
    }

    // Support remote image URLs used by older database records.
    if (preg_match('#^https?://#i', $path)) {
        header('Location: ' . $path);
        exit;
    }

    // Normalize and validate local image paths before reading the file.
    $path = str_replace('\\', '/', $path);
    $path = preg_replace('#^/?fashion_store/#i', '', $path);
    $path = ltrim($path, '/');

    $fullPath = realpath(__DIR__ . '/' . $path);
    $projectRoot = realpath(__DIR__);

    if (
        $fullPath === false ||
        $projectRoot === false ||
        strpos($fullPath, $projectRoot) !== 0 ||
        !is_file($fullPath)
    ) {
        showFallback();
    }

    // Send the correct content type and cache headers to the browser.
    $mimeType = mime_content_type($fullPath);
    if ($mimeType === false || strpos($mimeType, 'image/') !== 0) {
        showFallback();
    }

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($fullPath));
    header('Cache-Control: public, max-age=3600');
    readfile($fullPath);
    exit;
} catch (Throwable $exception) {
    showFallback();
}
