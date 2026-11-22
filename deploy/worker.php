<?php
declare(strict_types=1);

// CLI-only staging worker. Live activation is deliberately disabled until host paths are verified.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$queue = getenv('ECOMMERCE_DEPLOY_QUEUE') ?: '';
$token = getenv('ECOMMERCE_GITHUB_TOKEN') ?: '';
if ($queue === '') { fwrite(STDERR, "Queue not configured\n"); exit(1); }
$lock = fopen(dirname($queue) . '/deploy.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB) || !is_file($queue)) exit;
$sha = trim((string) file_get_contents($queue));
if (!preg_match('/^[a-f0-9]{40}$/', $sha)) exit(1);
if (!extension_loaded('curl') || !class_exists('ZipArchive')) exit(1);
$dir = sys_get_temp_dir() . '/ecommerce-' . bin2hex(random_bytes(8));
mkdir($dir, 0700);
$zipPath = $dir . '/release.zip';
$fp = fopen($zipPath, 'wb');
$headers = ['User-Agent: Ecommerce-Deploy', 'Accept: application/vnd.github+json'];
if ($token !== '') $headers[] = 'Authorization: Bearer ' . $token;
$ch = curl_init('https://api.github.com/repos/gokhanbdag-web/Ecommerce/zipball/' . $sha);
curl_setopt_array($ch, [CURLOPT_FILE => $fp, CURLOPT_HTTPHEADER => $headers,
    CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 90, CURLOPT_FAILONERROR => true,
    CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS]);
$ok = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);
fclose($fp);
if (!$ok) { fwrite(STDERR, $error . "\n"); exit(1); }
$zip = new ZipArchive();
if ($zip->open($zipPath) !== true) exit(1);
$stage = $dir . '/stage';
mkdir($stage);
for ($i = 0; $i < $zip->numFiles; $i++) {
    $entry = $zip->getNameIndex($i);
    $parts = explode('/', $entry);
    array_shift($parts); // Strip GitHub archive's root directory.
    if (in_array('..', $parts, true) || in_array('', array_slice($parts, 0, -1), true)) exit(1);
    $relative = implode('/', $parts);
    if ($relative === '') continue;
    $zip->getExternalAttributesIndex($i, $opsys, $attributes);
    if ((($attributes >> 16) & 0170000) === 0120000) exit(1); // No symlinks.
    $path = $stage . '/' . $relative;
    if (str_ends_with($entry, '/')) { if (!is_dir($path)) mkdir($path, 0755, true); continue; }
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
    $source = $zip->getStream($entry);
    $dest = fopen($path, 'wb');
    if (!$source || !$dest) exit(1);
    stream_copy_to_stream($source, $dest);
    fclose($source); fclose($dest);
}
$zip->close();
echo "Staged commit $sha at $stage; live activation NOT configured\n";
