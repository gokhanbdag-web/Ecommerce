<?php
declare(strict_types=1);

// GitHub push receiver. Configure secrets on the server, never in Git.
$secret = getenv('ECOMMERCE_WEBHOOK_SECRET') ?: '';
$queue = getenv('ECOMMERCE_DEPLOY_QUEUE') ?: '';
if ($secret === '' || $queue === '') { http_response_code(503); exit('Not configured'); }
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit; }
$body = file_get_contents('php://input');
if ($body === false || strlen($body) > 1048576) { http_response_code(413); exit; }
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
if (!hash_equals('sha256=' . hash_hmac('sha256', $body, $secret), $signature)) {
    http_response_code(401); exit('Invalid signature');
}
$data = json_decode($body, true);
if (($_SERVER['HTTP_X_GITHUB_EVENT'] ?? '') !== 'push'
    || ($data['repository']['full_name'] ?? '') !== 'gokhanbdag-web/Ecommerce'
    || ($data['ref'] ?? '') !== 'refs/heads/main'
    || !preg_match('/^[a-f0-9]{40}$/', $data['after'] ?? '')) {
    http_response_code(202); exit('Ignored');
}
$tmp = $queue . '.' . bin2hex(random_bytes(8)) . '.tmp';
if (file_put_contents($tmp, $data['after'] . "\n", LOCK_EX) === false || !rename($tmp, $queue)) {
    http_response_code(500); exit('Queue failed');
}
http_response_code(202);
echo 'Queued, not yet deployed';
