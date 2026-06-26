<?php
// ============================================================
// Cloudinary Configuration for Pawland
// ============================================================
define('CLOUDINARY_CLOUD_NAME', getenv('CLOUDINARY_CLOUD_NAME') ?: 'damzkmceb');
define('CLOUDINARY_API_KEY',    getenv('CLOUDINARY_API_KEY')    ?: '617235175595546');
define('CLOUDINARY_API_SECRET', getenv('CLOUDINARY_API_SECRET') ?: '1ltDguKSSDgr7o82oyXAIuEnuCY');
define('CLOUDINARY_UPLOAD_PRESET', ''); // optional unsigned preset

// Base upload URL
define('CLOUDINARY_UPLOAD_URL',
    'https://api.cloudinary.com/v1_1/' . CLOUDINARY_CLOUD_NAME . '/image/upload');

// ============================================================
// Upload image to Cloudinary and return secure_url
// $filePath  : absolute local path to the file
// $folder    : Cloudinary folder, e.g. 'pawland/places'
// Returns    : secure_url string, or false on failure
// ============================================================
function cloudinaryUpload(string $filePath, string $folder = 'pawland'): string|false
{
    $timestamp = time();
    $params    = [
        'folder'    => $folder,
        'timestamp' => $timestamp,
    ];

    // Build signature
    ksort($params);
    $paramStr  = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    $signature = sha1($paramStr . CLOUDINARY_API_SECRET);

    $postFields = array_merge($params, [
        'file'      => new CURLFile($filePath),
        'api_key'   => CLOUDINARY_API_KEY,
        'signature' => $signature,
    ]);

    $ch = curl_init(CLOUDINARY_UPLOAD_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("Cloudinary upload failed [$httpCode]: $response");
        return false;
    }

    $data = json_decode($response, true);
    return $data['secure_url'] ?? false;
}

// ============================================================
// Upload from a URL (for Google Photos already on the web)
// ============================================================
function cloudinaryUploadFromUrl(string $remoteUrl, string $folder = 'pawland'): string|false
{
    $timestamp = time();
    $params    = [
        'folder'    => $folder,
        'timestamp' => $timestamp,
    ];

    ksort($params);
    $paramStr  = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    $signature = sha1($paramStr . CLOUDINARY_API_SECRET);

    $postFields = array_merge($params, [
        'file'      => $remoteUrl,
        'api_key'   => CLOUDINARY_API_KEY,
        'signature' => $signature,
    ]);

    $ch = curl_init(CLOUDINARY_UPLOAD_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("Cloudinary URL upload failed [$httpCode]: $response");
        return false;
    }

    $data = json_decode($response, true);
    return $data['secure_url'] ?? false;
}

// ============================================================
// Helper: is this string already a Cloudinary URL?
// ============================================================
function isCloudinaryUrl(string $url): bool
{
    return str_contains($url, 'res.cloudinary.com/' . CLOUDINARY_CLOUD_NAME);
}