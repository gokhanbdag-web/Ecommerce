<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
$root = dirname(__DIR__);
$db = new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
(new Ecommerce\Database\Installer($db,$root))->install();
$auth = new Ecommerce\Admin\Auth($db);
if (!$auth->createFirst('admin@example.com','a-very-long-password')) throw new RuntimeException('Admin creation failed');
if ($auth->createFirst('other@example.com','a-very-long-password')) throw new RuntimeException('Second initial admin accepted');
if ($auth->createFirst('bad','short')) throw new RuntimeException('Invalid admin accepted');
session_start();
if ($auth->login('admin@example.com','wrong')) throw new RuntimeException('Invalid password accepted');
if (!$auth->login('admin@example.com','a-very-long-password')) throw new RuntimeException('Valid login rejected');
if (!$auth->authenticated()) throw new RuntimeException('Session not authenticated');
$settings = new Ecommerce\Admin\Settings($db);
$settings->save(['site_language'=>'tr_TR','site_title'=>'Deneme']);
if ($settings->all()['site_language'] !== 'tr_TR') throw new RuntimeException('Language not persisted');
try { $settings->save(['site_language'=>'invalid']); throw new RuntimeException('Invalid language accepted'); }
catch (InvalidArgumentException $expected) {}
$auth->logout();
if ($auth->authenticated()) throw new RuntimeException('Logout failed');
echo "Admin and settings tests passed\n";
