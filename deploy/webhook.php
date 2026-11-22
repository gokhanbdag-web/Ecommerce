<?php
declare(strict_types=1);

// GitHub webhook: verify the exact repository, branch and signed request.
$secretFile = '/home/kodcommerce.kodvadisi.com.tr/.ecommerce-webhook-secret';
$script = '/home/kodcommerce.kodvadisi.com.tr/deploy-main.sh';
$secret = is_readable($secretFile) ? trim((string) file_get_contents($secretFile)) : '';
if (strlen($secret) < 32 || !is_file($script) || !is_readable($script)) {
    http_response_code(503); exit('Deployment not configured');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit; }
$body = file_get_contents('php://input', false, null, 0, 1048577);
if ($body === false || strlen($body) > 1048576) { http_response_code(413); exit; }
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
if (!hash_equals('sha256=' . hash_hmac('sha256', $body, $secret), $signature)) {
    http_response_code(401); exit('Invalid signature');
}
$event = json_decode($body, true);
if (($_SERVER['HTTP_X_GITHUB_EVENT'] ?? '') !== 'push'
    || ($event['repository']['full_name'] ?? '') !== 'gokhanbdag-web/Ecommerce'
    || ($event['ref'] ?? '') !== 'refs/heads/main'
    || !is_string($event['after'] ?? null)
    || !preg_match('/^[a-f0-9]{40}$/', $event['after'])) {
    http_response_code(202); exit('Ignored');
}
// Execute immediately; no cron or polling. Argument is validated SHA only.
// Do not expose the shell command or its output in the HTTP response.
$command = '/usr/bin/bash ' . escapeshellarg($script) . ' ' . escapeshellarg($event['after']);
exec($command . ' >/dev/null 2>&1 &');
http_response_code(202);
echo 'Deployment triggered; result must be checked in deploy log';
