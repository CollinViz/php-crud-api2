<?php
use Tqdev\PhpCrudApi\Api;
use Tqdev\PhpCrudApi\Config;
use Tqdev\PhpCrudApi\Database\GenericDB;
use Tqdev\PhpCrudApi\Request;

spl_autoload_register(function ($class) {
    include str_replace('\\', '/', "src\\$class.php");
});

function runDir(Api $api, String $dir, String $match): array
{
    $success = 0;
    $total = 0;
    $entries = scandir($dir);
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $file = "$dir/$entry";
        if (is_file($file)) {
            if (substr($entry, -4) != '.log') {
                continue;
            }
            if ($match != '') {
                if (!preg_match('/' . preg_quote($match) . '/', $entry)) {
                    continue;
                }
            }
            $success += runTest($api, $file);
            $total += 1;
        }
    }
    $failed = $total - $success;
    return compact('total', 'success', 'failed');
}

function runTest(Api $api, String $file): int
{
    $title = ucwords(str_replace('_', ' ', substr(basename($file), 0, -4)));
    $line1 = "=====[$title]=====";
    $len = strlen($line1);
    $line2 = str_repeat("=", $len);
    $parts = preg_split('/^[=]+([\r\n]+|$)/m', file_get_contents($file));
    $dirty = false;
    $success = 1;
    for ($i = 0; $i < count($parts); $i += 2) {
        $recording = false;
        if (empty($parts[$i + 1])) {
            if (substr($parts[$i], -1) != "\n") {
                $parts[$i] .= "\n";
            }
            $parts[$i + 1] = '';
            $recording = true;
            $dirty = true;
        }
        $in = $parts[$i];
        $exp = $parts[$i + 1];
        $out = $api->handle(Request::fromString($in));
        if ($recording) {
            $parts[$i + 1] = $out;
        } else if ($out != $exp) {
            echo "$line1\n$exp\n$line2\n$out\n$line2\n";
            $success = 0;
        }
    }
    if ($dirty) {
        file_put_contents($file, implode("===\n", $parts));
    }
    return $success;
}

function loadFixture(String $dir, Config $config)
{
    $driver = $config->getDriver();
    $filename = "$dir/fixtures/blog_$driver.sql";
    $file = file_get_contents($filename);
    $db = new GenericDB(
        $config->getDriver(),
        $config->getAddress(),
        $config->getPort(),
        $config->getDatabase(),
        $config->getUsername(),
        $config->getPassword()
    );
    $pdo = $db->pdo();
    $file = preg_replace('/--.*$/m', '', $file);
    if ($driver == 'sqlsrv') {
        $statements = preg_split('/\n\s*GO\s*\n/s', $file);
    } else {
        $statements = preg_split('/(?<=;)\n/s', $file);
    }
    foreach ($statements as $i => $statement) {
        $statement = trim($statement);
        if ($statement) {
            try {
                $pdo->exec($statement);
            } catch (\PDOException $e) {
                $error = print_r($pdo->errorInfo(), true);
                $statement = var_export($statement, true);
                echo "Loading '$filename' failed on statemement #$i:\n$statement\nwith error:\n$error\n";
                exit(1);
            }
        }
    }
}

function run(array $drivers, String $dir, String $match)
{
    foreach ($drivers as $driver) {
        if (!extension_loaded("pdo_$driver")) {
            echo sprintf("%s: skipped, driver not loaded\n", $driver);
            continue;
        }
        $start = microtime(true);
        $ini = parse_ini_file(sprintf("$dir/config/config_%s.ini", $driver));
        $config = new Config($ini);
        loadFixture($dir, $config);
        $api = new Api($config);
        $stats = runDir($api, "$dir/functional", $match);
        $end = microtime(true);
        $time = ($end - $start) * 1000;
        $total = $stats['total'];
        $failed = $stats['failed'];
        echo sprintf("%s: %d tests ran in %d ms, %d failed\n", $driver, $total, $time, $failed);
    }
}

run(['mysql', 'pgsql', 'sqlsrv'], __DIR__ . '/tests', isset($argv[1]) ? $argv[1] : '');
