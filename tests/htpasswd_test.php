<?php
require_once __DIR__ . '/../htpasswd.php';

$sample = "" .
"# note: something\n" .
"# another comment\n" .
"user1:\$hash1\n" .
"# disabled comment\n" .
"# user2:\$hash2\n";

$file = tempnam(sys_get_temp_dir(), 'htp');
$backupDir = sys_get_temp_dir() . '/htp_' . uniqid();
file_put_contents($file, $sample);

$ht = new Htpasswd($file, $backupDir);
$entries = $ht->read();

assert(count($entries) === 2);
assert($entries[0]['active'] === true);
assert($entries[0]['username'] === 'user1');
assert($entries[0]['hash'] === '$hash1');
assert($entries[0]['comments'] === ['note: something', 'another comment']);
assert($entries[1]['active'] === false);
assert($entries[1]['username'] === 'user2');
assert($entries[1]['hash'] === '$hash2');
assert($entries[1]['comments'] === ['disabled comment']);

$ht->write($entries);
$written = file_get_contents($file);
assert($written === $sample);
$perms = fileperms($file) & 0777;
assert($perms === 0600);
$list = $ht->listBackups();
assert(count($list) >= 1);
$backupPath = rtrim($backupDir, '/') . '/' . $list[0]['file'];
$backupPerms = fileperms($backupPath) & 0777;
assert($backupPerms === 0600);
file_put_contents($file, 'broken');
$ht->restore($list[0]['file']);
assert(file_get_contents($file) === $sample);
$perms = fileperms($file) & 0777;
assert($perms === 0600);

$symlink = $backupDir . '/malicious';
symlink(__FILE__, $symlink);
$caught = false;
try {
    $ht->restore(basename($symlink));
} catch (InvalidArgumentException $ex) {
    $caught = true;
}
assert($caught);
unlink($symlink);

$all = glob($backupDir . '/' . basename($file) . '.*');
foreach ($all as $b) { unlink($b); }
@rmdir($backupDir);

$bad = $entries;
$bad[0]['username'] = 'bad name';
$caught = false;
try {
    $ht->write($bad);
} catch (InvalidArgumentException $ex) {
    $caught = true;
}
assert($caught);

unlink($file);
echo "All tests passed\n";
