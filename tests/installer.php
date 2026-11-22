<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
$root = dirname(__DIR__);
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$installer = new Ecommerce\Database\Installer($db, $root);
$installer->install();
if ((int) $db->query('SELECT COUNT(*) FROM pages')->fetchColumn() !== 1) {
    throw new RuntimeException('Homepage seed failed');
}
$installer->install();
if ((int) $db->query('SELECT COUNT(*) FROM pages')->fetchColumn() !== 1) {
    throw new RuntimeException('Installer not idempotent');
}
if ($db->query("SELECT value FROM settings WHERE name='site_language'")->fetchColumn() !== 'en_US') {
    throw new RuntimeException('Default language missing');
}
$translator = new Ecommerce\I18n\Translator($root, 'tr_TR');
if ($translator->translate('Welcome') !== 'Hoş geldiniz') {
    throw new RuntimeException('Translation lookup failed');
}
echo "Installer and localization tests passed\n";
