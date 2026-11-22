<?php
declare(strict_types=1);
/** @var callable $escape */
/** @var callable $translate */
?><!doctype html><html lang="<?= $escape(substr($values['site_language'] ?? 'en_US',0,2)) ?>"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $escape($translate('Settings')) ?> | Ecommerce</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head>
<body><main class="container py-5" style="max-width:720px">
<h1><?= $escape($translate($path === 'login' ? 'Log In' : 'General Settings')) ?></h1>
<?php if ($error !== ''): ?><div class="alert alert-danger"><?= $escape($error) ?></div><?php endif; ?>
<?php if ($path === 'login'): ?>
<form method="post"><input type="hidden" name="csrf" value="<?= $escape($csrf) ?>">
<label class="form-label"><?= $escape($translate('Email')) ?><input class="form-control" type="email" name="email" required></label>
<label class="form-label d-block"><?= $escape($translate('Password')) ?><input class="form-control" type="password" name="password" required></label>
<button class="btn btn-primary"><?= $escape($translate('Log In')) ?></button></form>
<?php else: ?>
<form method="post"><input type="hidden" name="csrf" value="<?= $escape($csrf) ?>">
<?php foreach (['site_title'=>'Site Title','site_description'=>'Tagline','site_email'=>'Administration Email','site_timezone'=>'Timezone'] as $key=>$label): ?>
<div class="mb-3"><label class="form-label"><?= $escape($translate($label)) ?><input class="form-control" name="<?= $escape($key) ?>" value="<?= $escape((string) ($values[$key] ?? '')) ?>"></label></div>
<?php endforeach; ?>
<div class="mb-3"><label class="form-label"><?= $escape($translate('Site Language')) ?><select class="form-select" name="site_language">
<?php foreach (['en_US'=>'English (United States)','tr_TR'=>'Turkish (Türkiye)'] as $locale=>$label): ?>
<option value="<?= $locale ?>" <?= ($values['site_language'] ?? 'en_US') === $locale ? 'selected' : '' ?>><?= $escape($translate($label)) ?></option>
<?php endforeach; ?></select></label></div>
<button class="btn btn-primary"><?= $escape($translate('Save Changes')) ?></button></form>
<form method="post" action="/admin/logout" class="mt-4"><input type="hidden" name="csrf" value="<?= $escape($csrf) ?>"><button class="btn btn-outline-secondary"><?= $escape($translate('Log Out')) ?></button></form>
<?php endif; ?></main></body></html>
