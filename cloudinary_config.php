<?php
define('CLOUDINARY_CLOUD_NAME', getenv('CLOUDINARY_CLOUD_NAME') ?: 'damzkmceb');
define('CLOUDINARY_API_KEY',    getenv('CLOUDINARY_API_KEY')    ?: '617235175595546');
define('CLOUDINARY_API_SECRET', getenv('CLOUDINARY_API_SECRET') ?: '1ItDguKSSDgr7o82oyXAluEnuCY');

define('CLOUDINARY_UPLOAD_URL',
    'https://api.cloudinary.com/v1_1/' . CLOUDINARY_CLOUD_NAME . '/image/upload');

function cloudinarySignature(array $params): string
{
    ksort($params);
    $parts = [];
    foreach ($params as $k => $v) $parts[] = "$k=$v";
    return sha1(implode('&', $parts) . CLOUDINARY_API_SECRET);
}

function cloudinaryUpload(string $filePath, string $folder = 'pawland'): string|false
{
    $timestamp = time();
    $params    = ['folder' => $folder, 'timestamp' => $timestamp];
    $signature = cloudinarySignature($params);

    $postFields = [
        'folder'    => $folder,
        'timestamp' => $timestamp,
        'api_key'   => CLOUDINARY_API_KEY,
        'signature' => $signature,
        'file'      => new CURLFile($filePath),
    ];

    $ch = curl_init(CLOUDINARY_UPLOAD_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_SSL_VERIFYPEER => false,
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

function cloudinaryUploadFromUrl(string $remoteUrl, string $folder = 'pawland'): string|false
{
    $timestamp = time();
    $params    = ['folder' => $folder, 'timestamp' => $timestamp];
    $signature = cloudinarySignature($params);

    $postFields = [
        'folder'    => $folder,
        'timestamp' => $timestamp,
        'api_key'   => CLOUDINARY_API_KEY,
        'signature' => $signature,
        'file'      => $remoteUrl,
    ];

    $ch = curl_init(CLOUDINARY_UPLOAD_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_SSL_VERIFYPEER => false,
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

function isCloudinaryUrl(string $url): bool
{
    return str_contains($url, 'res.cloudinary.com/' . CLOUDINARY_CLOUD_NAME);
}