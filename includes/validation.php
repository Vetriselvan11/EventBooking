<?php
/**
 * Event Booking Management System
 * Input & Data Validation Utilities
 */

/**
 * Validate an email address
 */
function validate_email(string $email): bool {
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password requirements (minimum length, character classes)
 */
function validate_password(string $password, int $minLength = PASSWORD_MIN_LENGTH): bool {
    return strlen($password) >= $minLength;
}

/**
 * Validate date format (YYYY-MM-DD) and check that it is valid calendar date
 */
function validate_date(string $date, string $format = 'Y-m-d'): bool {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate time format (HH:MM or HH:MM:SS)
 */
function validate_time(string $time): bool {
    return preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9](?::[0-5][0-9])?$/', $time) === 1;
}

/**
 * Validate positive integer (for seats, capacity, IDs)
 */
function validate_positive_int($value): bool {
    return filter_var($value, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) !== false;
}

/**
 * Validate non-negative float/decimal (for prices)
 */
function validate_price($value): bool {
    if (!is_numeric($value)) {
        return false;
    }
    return floatval($value) >= 0;
}

/**
 * Validate uploaded image file
 * @param array $file $_FILES['image']
 * @param array $allowedMimes
 * @param int $maxSizeBytes Default 5MB
 * @return array [bool $isValid, string $errorMessage]
 */
function validate_uploaded_image(array $file, array $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'], int $maxSizeBytes = 5242880): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return [false, 'Invalid upload parameters.'];
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return [false, 'No file was uploaded.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [false, 'Upload failed with system error code: ' . $file['error']];
    }

    if ($file['size'] > $maxSizeBytes) {
        return [false, 'File size exceeds maximum allowed limit (' . round($maxSizeBytes / 1048576) . ' MB).'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowedMimes, true)) {
        return [false, 'Invalid image format. Allowed formats: JPG, PNG, WEBP.'];
    }

    return [true, ''];
}
