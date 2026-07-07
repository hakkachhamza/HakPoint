<?php
/**
 * Signatures module helpers.
 * Stores signature requests in ge_signatures through the existing data_read/data_write layer.
 */
function ge_signature_collection(): string { return 'signatures'; }

function ge_signature_token_hash(string $token): string {
    return hash('sha256', trim($token));
}

function ge_signature_base_url(): string {
    $cfg = app_config();
    $base = trim((string)($cfg['base_url'] ?? ''));
    if ($base !== '') return rtrim($base, '/');
    $https = ge_is_https();
    $scheme = $https ? 'https' : 'http';
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    $script = (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $dir = rtrim(str_replace('\\','/', dirname($script)), '/');
    if ($dir === '' || $dir === '.') $dir = '';
    return $scheme.'://'.$host.$dir;
}

function ge_signature_public_url(string $token, string $page='signature_open'): string {
    return ge_signature_base_url().'/index.php?page='.$page.'&token='.rawurlencode($token);
}

function ge_signature_rows(): array {
    return data_read(ge_signature_collection(), []);
}

function ge_signature_find_by_token(string $token): ?array {
    $hash = ge_signature_token_hash($token);
    foreach (ge_signature_rows() as $row) {
        if (hash_equals((string)($row['token_hash'] ?? ''), $hash)) return $row;
    }
    return null;
}

function ge_signature_update_row(array $updated): void {
    $rows = ge_signature_rows();
    $found = false;
    foreach ($rows as $i => $row) {
        if ((int)($row['id'] ?? 0) === (int)($updated['id'] ?? 0)) {
            $rows[$i] = $updated;
            $found = true;
            break;
        }
    }
    if (!$found) $rows[] = $updated;
    data_write(ge_signature_collection(), $rows, false);
}

function ge_signature_safe_pdf_real(string $relative): ?string {
    $relative = str_replace(['\\', "\0"], ['/', ''], $relative);
    $relative = ltrim($relative, '/');
    if ($relative === '' || str_contains($relative, '..')) return null;
    if (!str_starts_with($relative, 'uploads/')) return null;
    $base = realpath(__DIR__.'/../uploads');
    $real = realpath(__DIR__.'/../'.$relative);
    if (!$base || !$real || !is_file($real)) return null;
    if (!str_starts_with($real, $base.DIRECTORY_SEPARATOR)) return null;
    if (strtolower(pathinfo($real, PATHINFO_EXTENSION)) !== 'pdf') return null;
    return $real;
}


function ge_signature_token_from_public_url(array $row): string {
    $url = (string)($row['public_url'] ?? '');
    if ($url === '') return '';
    $query = (string)(parse_url($url, PHP_URL_QUERY) ?: '');
    parse_str($query, $parts);
    return (string)($parts['token'] ?? '');
}

function ge_signature_row_url(array $row, string $page='signature_open'): string {
    $token = ge_signature_token_from_public_url($row);
    if ($token === '') return '#';
    return ge_signature_public_url($token, $page);
}

function ge_signature_signed_pdf_relative(array $row): string {
    $ref = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string)($row['ref'] ?? ('signature-'.($row['id'] ?? '0'))));
    $ref = trim($ref, '-') ?: ('signature-'.(int)($row['id'] ?? 0));
    return 'uploads/signatures/'.$ref.'-signed.pdf';
}

function ge_signature_png_uint32(string $data, int $offset): int {
    $p = unpack('N', substr($data, $offset, 4));
    return (int)($p[1] ?? 0);
}

function ge_signature_png_unfilter_row(string $row, string $prev, int $filter, int $bpp): string {
    $len = strlen($row);
    $out = '';
    for ($i = 0; $i < $len; $i++) {
        $x = ord($row[$i]);
        $left = ($i >= $bpp) ? ord($out[$i - $bpp]) : 0;
        $up = ($prev !== '' && $i < strlen($prev)) ? ord($prev[$i]) : 0;
        $upperLeft = ($prev !== '' && $i >= $bpp && ($i - $bpp) < strlen($prev)) ? ord($prev[$i - $bpp]) : 0;
        if ($filter === 1) $x = ($x + $left) & 255;
        elseif ($filter === 2) $x = ($x + $up) & 255;
        elseif ($filter === 3) $x = ($x + intdiv($left + $up, 2)) & 255;
        elseif ($filter === 4) {
            $p = $left + $up - $upperLeft;
            $pa = abs($p - $left); $pb = abs($p - $up); $pc = abs($p - $upperLeft);
            $pr = ($pa <= $pb && $pa <= $pc) ? $left : (($pb <= $pc) ? $up : $upperLeft);
            $x = ($x + $pr) & 255;
        }
        $out .= chr($x);
    }
    return $out;
}

function ge_signature_png_to_rgb(string $png, ?int &$width=null, ?int &$height=null): ?string {
    $width = 0; $height = 0;
    if (substr($png, 0, 8) !== "\x89PNG\r\n\x1a\n") return null;
    $offset = 8; $idat = ''; $bitDepth = 0; $colorType = -1; $interlace = 0;
    $len = strlen($png);
    while ($offset + 8 <= $len) {
        $chunkLen = ge_signature_png_uint32($png, $offset); $offset += 4;
        $type = substr($png, $offset, 4); $offset += 4;
        if ($chunkLen < 0 || $offset + $chunkLen + 4 > $len) return null;
        $chunk = substr($png, $offset, $chunkLen); $offset += $chunkLen + 4;
        if ($type === 'IHDR') {
            $width = ge_signature_png_uint32($chunk, 0);
            $height = ge_signature_png_uint32($chunk, 4);
            $bitDepth = ord($chunk[8]); $colorType = ord($chunk[9]); $interlace = ord($chunk[12]);
        } elseif ($type === 'IDAT') {
            $idat .= $chunk;
        } elseif ($type === 'IEND') break;
    }
    if ($width <= 0 || $height <= 0 || $bitDepth !== 8 || $interlace !== 0 || $idat === '') return null;
    $channels = [0=>1, 2=>3, 4=>2, 6=>4][$colorType] ?? 0;
    if ($channels <= 0) return null;
    $raw = @zlib_decode($idat);
    if ($raw === false && function_exists('gzuncompress')) $raw = @gzuncompress($idat);
    if (!is_string($raw)) return null;
    $stride = $width * $channels;
    $pos = 0; $prev = ''; $rgb = '';
    for ($y = 0; $y < $height; $y++) {
        if ($pos >= strlen($raw)) return null;
        $filter = ord($raw[$pos]); $pos++;
        $scan = substr($raw, $pos, $stride); $pos += $stride;
        if (strlen($scan) !== $stride) return null;
        $row = ge_signature_png_unfilter_row($scan, $prev, $filter, max(1, $channels));
        for ($x = 0; $x < $width; $x++) {
            $i = $x * $channels;
            if ($colorType === 6) {
                $r = ord($row[$i]); $g = ord($row[$i+1]); $b = ord($row[$i+2]); $a = ord($row[$i+3]);
                $r = (int)round(($r * $a + 255 * (255 - $a)) / 255);
                $g = (int)round(($g * $a + 255 * (255 - $a)) / 255);
                $b = (int)round(($b * $a + 255 * (255 - $a)) / 255);
                $rgb .= chr($r).chr($g).chr($b);
            } elseif ($colorType === 2) {
                $rgb .= $row[$i].$row[$i+1].$row[$i+2];
            } elseif ($colorType === 4) {
                $gray = ord($row[$i]); $a = ord($row[$i+1]);
                $v = (int)round(($gray * $a + 255 * (255 - $a)) / 255);
                $rgb .= chr($v).chr($v).chr($v);
            } else {
                $gray = ord($row[$i]); $rgb .= chr($gray).chr($gray).chr($gray);
            }
        }
        $prev = $row;
    }
    return $rgb;
}

function ge_signature_decode_signature_pdf_image(string $dataUrl, ?int &$width=null, ?int &$height=null, ?string &$filter=null): ?string {
    $width = 0; $height = 0; $filter = 'FlateDecode';
    if (!preg_match('#^data:image/(png|jpeg|jpg);base64,([A-Za-z0-9+/=]+)$#', $dataUrl, $m)) return null;
    $raw = base64_decode($m[2], true);
    if ($raw === false || strlen($raw) < 32) return null;
    $kind = strtolower($m[1]);
    if ($kind === 'jpg' || $kind === 'jpeg') {
        if (function_exists('getimagesizefromstring')) {
            $info = @getimagesizefromstring($raw);
            if (!$info || ($info[2] ?? 0) !== IMAGETYPE_JPEG) return null;
            $width = (int)$info[0]; $height = (int)$info[1]; $filter = 'DCTDecode';
            return $raw;
        }
        return null;
    }
    $rgb = ge_signature_png_to_rgb($raw, $width, $height);
    if ($rgb === null || $width <= 0 || $height <= 0) return null;
    $compressed = gzcompress($rgb, 6);
    return is_string($compressed) ? $compressed : null;
}

function ge_signature_pdf_objects(string $pdf): array {
    $objects = [];
    if (!preg_match_all('/(\d+)\s+0\s+obj\s*(.*?)\s*endobj/s', $pdf, $matches, PREG_SET_ORDER)) return [];
    foreach ($matches as $m) $objects[(int)$m[1]] = $m[2];
    ksort($objects);
    return $objects;
}

function ge_signature_page_contents_ids(string $pageBody): array {
    if (preg_match('/\/Contents\s+(\d+)\s+0\s+R/s', $pageBody, $m)) return [(int)$m[1]];
    if (preg_match('/\/Contents\s*\[(.*?)\]/s', $pageBody, $m)) {
        preg_match_all('/(\d+)\s+0\s+R/', $m[1], $ids);
        return array_map('intval', $ids[1] ?? []);
    }
    return [];
}

function ge_signature_find_pdf_box(string $contentBody): ?array {
    $labels = ['Signature / validation', 'Cachet, Date, Signature', 'Bon pour Accord'];
    foreach ($labels as $label) {
        $pos = strpos($contentBody, $label);
        if ($pos === false) continue;
        $chunk = substr($contentBody, $pos, 900);
        if (preg_match('/([0-9.]+)\s+([0-9.]+)\s+([0-9.]+)\s+([0-9.]+)\s+re\s+S/s', $chunk, $m)) {
            return ['x'=>(float)$m[1], 'y'=>(float)$m[2], 'w'=>(float)$m[3], 'h'=>(float)$m[4]];
        }
    }
    if (preg_match_all('/([0-9.]+)\s+([0-9.]+)\s+([0-9.]+)\s+([0-9.]+)\s+re\s+S/s', $contentBody, $all, PREG_SET_ORDER)) {
        $best = null;
        foreach ($all as $m) {
            $x=(float)$m[1]; $y=(float)$m[2]; $w=(float)$m[3]; $h=(float)$m[4];
            if ($x >= 300 && $w >= 160 && $w <= 260 && $h >= 35 && $h <= 80) $best = ['x'=>$x,'y'=>$y,'w'=>$w,'h'=>$h];
        }
        if ($best) return $best;
    }
    return null;
}

function ge_signature_add_xobject_to_page(string $pageBody, string $imageName, int $imageObjId): string {
    $entry = ' /'.$imageName.' '.$imageObjId.' 0 R ';
    if (preg_match('/\/XObject\s*<<(.*?)>>/s', $pageBody)) {
        return preg_replace('/\/XObject\s*<<(.*?)>>/s', '/XObject <<$1'.$entry.'>>', $pageBody, 1) ?? $pageBody;
    }
    if (preg_match('/\/Resources\s*<<\s*\/Font\s*<<(.*?)>>/s', $pageBody)) {
        return preg_replace('/\/Resources\s*<<\s*\/Font\s*<<(.*?)>>/s', '/Resources << /Font <<$1>> /XObject <<'.$entry.'>>', $pageBody, 1) ?? $pageBody;
    }
    return $pageBody;
}

function ge_signature_add_content_to_page(string $pageBody, int $contentObjId): string {
    if (preg_match('/\/Contents\s+(\d+)\s+0\s+R/s', $pageBody, $m)) {
        return preg_replace('/\/Contents\s+(\d+)\s+0\s+R/s', '/Contents [$1 0 R '.$contentObjId.' 0 R]', $pageBody, 1) ?? $pageBody;
    }
    if (preg_match('/\/Contents\s*\[(.*?)\]/s', $pageBody)) {
        return preg_replace('/\/Contents\s*\[(.*?)\]/s', '/Contents [$1 '.$contentObjId.' 0 R]', $pageBody, 1) ?? $pageBody;
    }
    return $pageBody;
}

function ge_signature_rebuild_pdf(array $objects, int $rootObjId): string {
    ksort($objects);
    $max = max(array_keys($objects));
    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = array_fill(0, $max + 1, 0);
    for ($i = 1; $i <= $max; $i++) {
        if (!array_key_exists($i, $objects)) continue;
        $offsets[$i] = strlen($pdf);
        $pdf .= $i." 0 obj\n".$objects[$i]."\nendobj\n";
    }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 ".($max + 1)."\n0000000000 65535 f \n";
    for ($i = 1; $i <= $max; $i++) {
        if (!array_key_exists($i, $objects)) $pdf .= "0000000000 65535 f \n";
        else $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size ".($max + 1)." /Root ".$rootObjId." 0 R >>\nstartxref\n".$xref."\n%%EOF";
    return $pdf;
}

function ge_signature_create_signed_pdf_file(array $row, string $destReal): bool {
    $source = ge_signature_safe_pdf_real((string)($row['pdf_file'] ?? ''));
    if (!$source || empty($row['signature_data'])) return false;
    $sigW = 0; $sigH = 0; $imageFilter = 'FlateDecode';
    $imageData = ge_signature_decode_signature_pdf_image((string)$row['signature_data'], $sigW, $sigH, $imageFilter);
    if ($imageData === null || $sigW <= 0 || $sigH <= 0) return false;
    $pdf = @file_get_contents($source);
    if (!is_string($pdf) || !str_starts_with($pdf, '%PDF')) return false;
    $objects = ge_signature_pdf_objects($pdf);
    if (!$objects) return false;
    if (!preg_match('/\/Root\s+(\d+)\s+0\s+R/s', $pdf, $rootMatch)) return false;
    $rootObjId = (int)$rootMatch[1];
    $pageIds = [];
    foreach ($objects as $id=>$body) {
        if (strpos($body, '/Type /Page') !== false && strpos($body, '/Type /Pages') === false) $pageIds[] = (int)$id;
    }
    if (!$pageIds) return false;
    $targetPageId = end($pageIds);
    $targetRect = ['x'=>340.0, 'y'=>86.0, 'w'=>228.0, 'h'=>50.0];
    foreach (array_reverse($pageIds) as $pid) {
        foreach (array_reverse(ge_signature_page_contents_ids($objects[$pid] ?? '')) as $cid) {
            if (!isset($objects[$cid])) continue;
            $rect = ge_signature_find_pdf_box($objects[$cid]);
            if ($rect) { $targetPageId = (int)$pid; $targetRect = $rect; break 2; }
        }
    }
    $maxId = max(array_keys($objects));
    $imgObjId = $maxId + 1;
    $contentObjId = $maxId + 2;
    $imageName = 'SigGE'.$imgObjId;
    $objects[$imgObjId] = "<< /Type /XObject /Subtype /Image /Width ".$sigW." /Height ".$sigH." /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /".$imageFilter." /Length ".strlen($imageData)." >>\nstream\n".$imageData."\nendstream";
    $padX = 10.0; $padY = 7.0;
    $maxW = max(30.0, $targetRect['w'] - ($padX * 2));
    $maxH = max(18.0, $targetRect['h'] - ($padY * 2));
    $ratio = $sigW / max(1, $sigH);
    $drawW = min($maxW, $maxH * $ratio);
    $drawH = min($maxH, $drawW / max(0.01, $ratio));
    $drawX = $targetRect['x'] + (($targetRect['w'] - $drawW) / 2);
    $drawY = $targetRect['y'] + (($targetRect['h'] - $drawH) / 2);
    $overlay = "q\n".round($drawW,2)." 0 0 ".round($drawH,2)." ".round($drawX,2)." ".round($drawY,2)." cm\n/".$imageName." Do\nQ\n";
    $objects[$contentObjId] = "<< /Length ".strlen($overlay)." >>\nstream\n".$overlay."endstream";
    $objects[$targetPageId] = ge_signature_add_xobject_to_page((string)$objects[$targetPageId], $imageName, $imgObjId);
    $objects[$targetPageId] = ge_signature_add_content_to_page((string)$objects[$targetPageId], $contentObjId);
    $out = ge_signature_rebuild_pdf($objects, $rootObjId);
    $dir = dirname($destReal);
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    return @file_put_contents($destReal, $out) !== false && is_file($destReal) && filesize($destReal) > 0;
}

function ge_signature_signed_pdf_real(array $row): ?string {
    $original = ge_signature_safe_pdf_real((string)($row['pdf_file'] ?? ''));
    if ((string)($row['status'] ?? '') !== 'signed') return $original;
    $relative = (string)($row['signed_pdf_file'] ?? '');
    if ($relative === '' || !str_starts_with($relative, 'uploads/signatures/')) $relative = ge_signature_signed_pdf_relative($row);
    $real = __DIR__.'/../'.$relative;
    $signedAt = strtotime((string)($row['signed_at'] ?? '')) ?: 0;
    if (is_file($real) && filesize($real) > 0 && (@filemtime($real) ?: 0) >= $signedAt) return realpath($real) ?: $real;
    if (ge_signature_create_signed_pdf_file($row, $real)) return realpath($real) ?: $real;
    return $original;
}


function ge_signature_upload_dir(): string {
    return __DIR__.'/../uploads/signature_documents';
}

function ge_signature_handle_uploaded_pdf(string $field='pdf_upload'): array {
    if (empty($_FILES[$field]) || !is_array($_FILES[$field])) return ['ok'=>false, 'empty'=>true, 'relative'=>'', 'error'=>''];
    $file = $_FILES[$field];
    $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err === UPLOAD_ERR_NO_FILE) return ['ok'=>false, 'empty'=>true, 'relative'=>'', 'error'=>''];
    if ($err !== UPLOAD_ERR_OK) return ['ok'=>false, 'empty'=>false, 'relative'=>'', 'error'=>'Upload PDF impossible (code '.$err.').'];
    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) return ['ok'=>false, 'empty'=>false, 'relative'=>'', 'error'=>'Fichier uploadé invalide.'];
    $size = (int)($file['size'] ?? 0);
    if ($size <= 0) return ['ok'=>false, 'empty'=>false, 'relative'=>'', 'error'=>'Le PDF uploadé est vide.'];
    if ($size > 20 * 1024 * 1024) return ['ok'=>false, 'empty'=>false, 'relative'=>'', 'error'=>'Le PDF est trop grand (max 20 Mo).'];
    $original = (string)($file['name'] ?? 'document.pdf');
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if ($ext !== 'pdf') return ['ok'=>false, 'empty'=>false, 'relative'=>'', 'error'=>'Seuls les fichiers PDF sont acceptés.'];
    $fh = @fopen($tmp, 'rb');
    $magic = $fh ? fread($fh, 4) : '';
    if ($fh) fclose($fh);
    if ($magic !== '%PDF') return ['ok'=>false, 'empty'=>false, 'relative'=>'', 'error'=>'Le fichier choisi n’est pas un vrai PDF.'];
    $dir = ge_signature_upload_dir();
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    if (!is_dir($dir) || !is_writable($dir)) return ['ok'=>false, 'empty'=>false, 'relative'=>'', 'error'=>'Le dossier uploads/signature_documents n’est pas accessible en écriture.'];
    $safeBase = preg_replace('/[^A-Za-z0-9._-]+/', '-', pathinfo($original, PATHINFO_FILENAME));
    $safeBase = trim((string)$safeBase, '-_.');
    if ($safeBase === '') $safeBase = 'document';
    $name = 'signature_'.date('Ymd_His').'_'.bin2hex(random_bytes(4)).'_'.$safeBase.'.pdf';
    $dest = rtrim($dir, '/\\').DIRECTORY_SEPARATOR.$name;
    if (!@move_uploaded_file($tmp, $dest)) return ['ok'=>false, 'empty'=>false, 'relative'=>'', 'error'=>'Impossible d’enregistrer le PDF uploadé.'];
    @chmod($dest, 0664);
    return ['ok'=>true, 'empty'=>false, 'relative'=>'uploads/signature_documents/'.$name, 'error'=>''];
}

function ge_signature_pdf_type_label(string $relative): string {
    $parts = explode('/', str_replace('\\','/', $relative));
    $folder = $parts[1] ?? '';
    $map = [
        'quotes'=>'Devis',
        'orders'=>'Commande',
        'invoices'=>'Facture client',
        'credit_notes'=>'Avoir client',
        'purchases'=>'Achat fournisseur',
        'documents'=>'Document',
        'signature_documents'=>'PDF uploadé',
        'expeditions'=>'Expédition',
        'receptions'=>'Réception',
        'products'=>'Produit',
    ];
    return $map[$folder] ?? ucfirst(str_replace('_',' ', $folder ?: 'PDF'));
}

function ge_signature_pdf_options(): array {
    $base = realpath(__DIR__.'/../uploads');
    if (!$base || !is_dir($base)) return [];
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if (!$file->isFile()) continue;
        if (strtolower($file->getExtension()) !== 'pdf') continue;
        $real = $file->getRealPath();
        if (!$real || !str_starts_with($real, $base.DIRECTORY_SEPARATOR)) continue;
        $relative = 'uploads/'.str_replace('\\','/', substr($real, strlen($base)+1));
        if (str_starts_with($relative, 'uploads/signatures/')) continue;
        $type = ge_signature_pdf_type_label($relative);
        $filename = basename($real);
        $out[] = [
            'value' => $relative,
            'type' => $type,
            'filename' => $filename,
            'label' => $type.' — '.$filename,
            'mtime' => @filemtime($real) ?: 0,
            'size' => @filesize($real) ?: 0,
        ];
    }
    usort($out, fn($a,$b) => ($b['mtime'] <=> $a['mtime']) ?: strcmp($a['label'], $b['label']));
    return $out;
}

function ge_signature_status_label(array $row): string {
    $status = (string)($row['status'] ?? 'sent');
    if ($status === 'signed') return 'Signé';
    if (!empty($row['email_error'])) return 'Erreur email';
    if (!empty($row['opened_at'])) return 'Consulté / non signé';
    if ($status === 'sent') return 'Envoyé / non signé';
    return 'Non signé';
}

function ge_signature_status_class(array $row): string {
    $status = (string)($row['status'] ?? 'sent');
    if ($status === 'signed') return 'success';
    if (!empty($row['email_error'])) return 'danger';
    if (!empty($row['opened_at'])) return 'warning';
    return 'info';
}

function ge_signature_default_message(string $clientName, string $docLabel, string $link): string {
    $hello = trim($clientName) !== '' ? 'Bonjour '.$clientName.',' : 'Bonjour,';
    return $hello."\n\nVeuillez consulter et signer le document suivant :\n".$docLabel."\n\nLien de signature :\n".$link."\n\nAprès ouverture, vous pouvez lire le PDF puis confirmer votre signature dans la zone prévue.\n\nSincèrement,\nGlobal Energie";
}

function ge_signature_client_default_email(array $docOption): string {
    // Best effort: infer an email from the document filename/ref by checking existing sales objects.
    $filename = strtolower((string)($docOption['filename'] ?? ''));
    $collections = ['quotes','orders','invoices'];
    foreach ($collections as $collection) {
        foreach (data_read($collection, []) as $row) {
            $ref = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string)($row['ref'] ?? '')));
            if ($ref !== '' && str_contains($filename, strtolower($ref))) {
                $tierId = (int)($row['client_id'] ?? $row['tier_id'] ?? 0);
                if ($tierId > 0) {
                    $tier = find_row_by_id(data_read('tiers', []), $tierId);
                    if ($tier && filter_var($tier['email'] ?? '', FILTER_VALIDATE_EMAIL)) return (string)$tier['email'];
                }
            }
        }
    }
    return '';
}
?>
