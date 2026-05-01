<?php

const NATIONAL_ID_REGEX = '/^\d{2}-\d{6,7}[A-Z]\d{2}$/';
const ID_UPLOAD_DIR     = __DIR__.'/../uploads/national_ids';
const ID_MAX_BYTES      = 5 * 1024 * 1024;

function isValidNationalIdNumber(string $id): bool {
    return $id === '' || (bool)preg_match(NATIONAL_ID_REGEX, strtoupper(trim($id)));
}

/**
 * Handle a national-ID file upload.
 * Returns the saved filename (basename only), or '' if no file was uploaded,
 * or throws RuntimeException on validation failure.
 */
function handleIdUpload(array $file): string {
    if(!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) return '';
    if($file['error'] !== UPLOAD_ERR_OK){
        throw new RuntimeException('ID upload failed (error code '.$file['error'].').');
    }
    if($file['size'] <= 0 || $file['size'] > ID_MAX_BYTES){
        throw new RuntimeException('ID file must be between 1 byte and 5 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];
    if(!isset($allowed[$mime])){
        throw new RuntimeException('ID file must be a JPG, PNG, WEBP image or PDF.');
    }

    if(!is_dir(ID_UPLOAD_DIR)){
        @mkdir(ID_UPLOAD_DIR, 0755, true);
    }

    $ext  = $allowed[$mime];
    $name = bin2hex(random_bytes(16)).'.'.$ext;
    $dest = ID_UPLOAD_DIR.'/'.$name;
    if(!move_uploaded_file($file['tmp_name'], $dest)){
        throw new RuntimeException('Could not save ID file. Please try again.');
    }
    return $name;
}

function deleteIdFile(?string $name): void {
    if($name === null || $name === '') return;
    if(strpos($name, '/') !== false || strpos($name, '\\') !== false) return;
    $path = ID_UPLOAD_DIR.'/'.$name;
    if(is_file($path)) @unlink($path);
}

function idFileMimeFromName(string $name): string {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return match($ext){
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
        default => 'application/octet-stream',
    };
}
