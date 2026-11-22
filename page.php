<?php
declare(strict_types=1);
/** @var array $page */
/** @var array $menus */
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$blocks = json_decode((string) $page['content_json'], true);
$blocks = is_array($blocks) ? $blocks : [];
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $escape((string) $page['title']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body>
<nav class="navbar navbar-expand-lg bg-body-tertiary"><div class="container">
<a class="navbar-brand" href="/">Ecommerce</a><div class="navbar-nav">
<?php foreach ($menus as $item): ?>
<a class="nav-link" href="<?= $escape($item['page_slug'] !== null ? '/' . $item['page_slug'] : (string) $item['url']) ?>"><?= $escape((string) $item['label']) ?></a>
<?php endforeach; ?>
</div></div></nav>
<main class="container py-5">
<?php foreach ($blocks as $block): ?>
<?php if (!is_array($block)) continue; ?>
<?php if (($block['type'] ?? '') === 'heading'): ?>
<h2><?= $escape((string) ($block['text'] ?? '')) ?></h2>
<?php elseif (($block['type'] ?? '') === 'paragraph'): ?>
<p><?= nl2br($escape((string) ($block['text'] ?? ''))) ?></p>
<?php endif; ?>
<?php endforeach; ?>
</main></body></html>
