<?php
class Htpasswd {
    private $path;
    private $backupDir;
    private $pattern = '/^[^:\\s]+:\\$.+/';
    public function __construct($path, $backupDir) {
        $this->path = $path;
        $this->backupDir = $backupDir;
    }

    public function read() {
        if (!file_exists($this->path)) {
            return [];
        }
        $lines = file($this->path, FILE_IGNORE_NEW_LINES);
        $entries = [];
        $comments = [];
        foreach ($lines as $line) {
            if ($line === '') {
                $comments[] = '';
                continue;
            }
            if ($line[0] === '#') {
                $rest = ltrim(substr($line, 1));
                if (preg_match($this->pattern, $rest)) {
                    list($user, $hash) = explode(':', $rest, 2);
                    $entries[] = [
                        'active' => false,
                        'username' => $user,
                        'hash' => $hash,
                        'comments' => $comments
                    ];
                    $comments = [];
                } else {
                    $comments[] = $rest;
                }
            } else if (preg_match($this->pattern, $line)) {
                list($user, $hash) = explode(':', $line, 2);
                $entries[] = [
                    'active' => true,
                    'username' => $user,
                    'hash' => $hash,
                    'comments' => $comments
                ];
                $comments = [];
            } else {
                $comments[] = $line;
            }
        }
        return $entries;
    }

    public function write($entries) {
        $lines = [];
        foreach ($entries as $e) {
            if (!is_array($e) || !isset($e['username'], $e['hash'], $e['active'], $e['comments']) ||
                preg_match('/[\s:]/', $e['username']) || preg_match('/[\r\n:]/', $e['hash']) ||
                !is_array($e['comments'])) {
                throw new InvalidArgumentException('Invalid entry');
            }
            $line = $e['username'] . ':' . $e['hash'];
            if (!preg_match($this->pattern, $line)) {
                throw new InvalidArgumentException('Invalid entry');
            }
            foreach ($e['comments'] as $c) {
                if (preg_match('/[\r\n]/', $c)) {
                    throw new InvalidArgumentException('Invalid comment');
                }
                if ($c === '') {
                    $lines[] = '';
                } else {
                    $lines[] = '# ' . $c;
                }
            }
            if (!$e['active']) {
                $line = '# ' . $line;
            }
            $lines[] = $line;
        }
        $this->backup();
        $data = implode("\n", $lines) . "\n";
        if (file_put_contents($this->path, $data, LOCK_EX) === false) {
            throw new RuntimeException('Unable to write htpasswd file');
        }
        @chmod($this->path, 0600);
    }

    public function backup() {
        if (!$this->backupDir) {
            return;
        }
        if (!is_dir($this->backupDir)) {
            if (!mkdir($this->backupDir, 0700, true) && !is_dir($this->backupDir)) {
                throw new RuntimeException('Unable to create backup directory');
            }
        } else {
            @chmod($this->backupDir, 0700);
        }
        $backupFile = rtrim($this->backupDir, '/') . '/' . basename($this->path) . '.' . date('YmdHis');
        if (file_exists($this->path)) {
            if (!copy($this->path, $backupFile)) {
                throw new RuntimeException('Unable to create backup copy');
            }
        } else {
            if (file_put_contents($backupFile, '') === false) {
                throw new RuntimeException('Unable to create empty backup copy');
            }
        }
        @chmod($backupFile, 0600);
    }

    public function listBackups() {
        if (!$this->backupDir || !is_dir($this->backupDir)) {
            return [];
        }
        $pattern = rtrim($this->backupDir, '/') . '/' . basename($this->path) . '.*';
        $files = glob($pattern);
        rsort($files);
        $result = [];
        foreach ($files as $f) {
            $name = basename($f);
            $ts = substr($name, strlen(basename($this->path)) + 1);
            $dt = DateTime::createFromFormat('YmdHis', $ts);
            $result[] = [
                'file' => $name,
                'time' => $dt ? $dt->format('Y-m-d H:i:s') : $name
            ];
        }
        return $result;
    }

    public function restore($name) {
        if (!$this->backupDir) {
            throw new InvalidArgumentException('No backup dir');
        }
        $file = rtrim($this->backupDir, '/') . '/' . basename($name);
        if (!is_file($file)) {
            throw new InvalidArgumentException('Backup not found');
        }
        $backupDirReal = realpath($this->backupDir);
        $fileReal = realpath($file);
        if ($backupDirReal === false || $fileReal === false || strpos($fileReal, $backupDirReal . DIRECTORY_SEPARATOR) !== 0) {
            throw new InvalidArgumentException('Invalid backup file');
        }
        $data = file_get_contents($file);
        $this->backup();
        if (file_put_contents($this->path, $data, LOCK_EX) === false) {
            throw new RuntimeException('Unable to restore htpasswd file');
        }
        @chmod($this->path, 0600);
    }
}
?>
