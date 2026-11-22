<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';
if (!extension_loaded('pdo_sqlite')) {
    fwrite(STDERR, "pdo_sqlite extension required\n");
    exit(1);
}
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec(file_get_contents(dirname(__DIR__) . '/database/schema.sql'));
$db->exec("INSERT INTO pages(title,slug,status,content_json) VALUES ('Home','home','published','[]'),('About','about','published','[]')");
$db->exec("INSERT INTO settings(name,value) VALUES ('homepage_page_id','1')");
$db->exec("INSERT INTO menus(name,location) VALUES ('Main','primary')");
$db->exec("INSERT INTO menu_items(menu_id,page_id,label,position) VALUES (1,2,'About',1)");
$pages = new Ecommerce\PageRepository($db);
assert($pages->findForPath('')['title'] === 'Home');
assert($pages->findForPath('about')['title'] === 'About');
assert($pages->findForPath('missing') === null);
assert(count((new Ecommerce\MenuRepository($db))->itemsForLocation('primary')) === 1);
echo "Foundation tests passed\n";
