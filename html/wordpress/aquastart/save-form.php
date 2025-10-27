<?php
declare(strict_types=1);

/**
 * save-form.php
 * Обработчик формы с расширенной диагностикой конфигурации и SMTP.
 * Время в логах и заявках — по Екатеринбургу (Asia/Yekaterinburg)
 *
 * Перед использованием:
 * - composer install (vendor/autoload.php)
 * - проверьте /opt/aquastart/config.json (скрипт ищет в двух путях)
 * - убедитесь, что веб-процесс может читать config и писать log_path (или используйте sudo для прав)
 */

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use ClickHouseDB\Client as ClickHouseClient;

// ---------- Timezone for Ekaterinburg ----------
date_default_timezone_set('Asia/Yekaterinburg');

ini_set('display_errors', '0');
error_reporting(E_ALL);

// ---------- utilities ----------
$defaultLog = __DIR__ . '/log.txt';
$bootstrapLog = __DIR__ . '/aquastart_bootstrap.log';

/**
 * Неблокирующая безопасная запись в лог с fallback
 */
function safe_file_append(string $path, string $data): bool {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $res = @file_put_contents($path, $data, FILE_APPEND | LOCK_EX);
    if ($res === false) {
        global $defaultLog;
        $res2 = @file_put_contents($defaultLog, $data, FILE_APPEND | LOCK_EX);
        if ($res2 === false) {
            error_log("LOG_WRITE_FAIL: " . trim($data));
            return false;
        }
    }
    return true;
}

function log_step(string $msg): void {
    global $bootstrapLog;
    // Екатеринбургское время
    $ts = (new DateTime('now', new DateTimeZone('Asia/Yekaterinburg')))->format('Y-m-d\TH:i:sP');
    $line = "{$ts} | {$msg}" . PHP_EOL;
    safe_file_append($bootstrapLog, $line);
}

// Catch fatal errors
register_shutdown_function(function(): void {
    $err = error_get_last();
    if ($err !== null) {
        log_step("SHUTDOWN ERROR: type={$err['type']} file={$err['file']} line={$err['line']} message={$err['message']}");
    } else {
        log_step("SHUTDOWN: normal exit");
    }
});

// bootstrap info
log_step('REAL SCRIPT: ' . __FILE__);
log_step('REAL CWD: ' . getcwd());
log_step('SCRIPT START, REQUEST_METHOD=' . ($_SERVER['REQUEST_METHOD'] ?? 'CLI') . ', POST=' . json_encode($_POST ?? []));

// ---------- load config ----------
$configPaths = [
    __DIR__ . '/config.json',
    '/opt/aquastart/config.json'
];
$config = [];
$configPathUsed = null;

foreach ($configPaths as $p) {
    log_step("Checking config path: {$p}");
    if (is_readable($p)) {
        $raw = @file_get_contents($p);
        if ($raw === false) {
            log_step("Failed to read config at {$p}");
            continue;
        }
        $parsed = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            log_step("Invalid JSON in {$p}: " . json_last_error_msg());
            continue;
        }
        $config = $parsed;
        $configPathUsed = $p;
        break;
    }
}
if ($configPathUsed === null) {
    log_step("Config not found in known locations; using empty config");
} else {
    log_step("Config loaded from: {$configPathUsed}");
}

// effective log path
$logPath = $config['log_path'] ?? $defaultLog;
if (!preg_match('#^/#', $logPath)) {
    $logPath = __DIR__ . '/' . $logPath;
}
$bootstrapLog = $logPath;
log_step("Effective log path: {$bootstrapLog}");

// mask sensitive fields for logging
function mask_config(array $cfg): array {
    $out = $cfg;
    if (isset($out['smtp']) && is_array($out['smtp'])) {
        if (!empty($out['smtp']['password'])) $out['smtp']['password'] = '***';
    }
    if (isset($out['clickhouse']) && is_array($out['clickhouse'])) {
        if (!empty($out['clickhouse']['password'])) $out['clickhouse']['password'] = '***';
    }
    return $out;
}

log_step("CONFIG DIAGNOSTIC (masked): " . json_encode(mask_config($config)));

// helper to read nested config keys
function cfg(array $keys, $default = null) {
    global $config;
    $cur = $config;
    foreach ($keys as $k) {
        if (!is_array($cur) || !array_key_exists($k, $cur)) {
            return $default;
        }
        $cur = $cur[$k];
    }
    return $cur;
}

// ---------- environment diagnostics ----------
function env_diagnostics(): void {
    log_step("ENV: PHP_VERSION=" . PHP_VERSION);
    log_step("ENV: PHP_OS=" . PHP_OS);
    log_step("ENV: OPENSSL_LOADED=" . (extension_loaded('openssl') ? 'yes' : 'no'));
    log_step("ENV: CURL_LOADED=" . (extension_loaded('curl') ? 'yes' : 'no'));
    log_step("ENV: SOCKETS_LOADED=" . (extension_loaded('sockets') ? 'yes' : 'no'));
    log_step("ENV: ALLOW_URL_FOPEN=" . (ini_get('allow_url_fopen') ? 'yes' : 'no'));
    // current process user
    $user = get_current_user();
    $uid = getmyuid();
    $gid = getmygid();
    log_step("ENV: process_user={$user} uid={$uid} gid={$gid}");
}
env_diagnostics();

// ---------- ensure log file writeable (diagnose) ----------
function ensure_log_writable(string $path): void {
    log_step("LOG CHECK: checking path {$path}");
    $dir = dirname($path);
    if (!is_dir($dir)) {
        log_step("LOG CHECK: directory {$dir} does not exist, attempting mkdir");
        @mkdir($dir, 0755, true);
    }
    // try to create or append
    $ok = @file_put_contents($path, '', FILE_APPEND | LOCK_EX);
    if ($ok === false) {
        log_step("LOG CHECK: cannot write to {$path} (permission denied). Trying fallback...");
        // try create in cwd
        $fallback = __DIR__ . '/log.txt';
        $res = @file_put_contents($fallback, "LOG FALLBACK\n", FILE_APPEND | LOCK_EX);
        if ($res === false) {
            log_step("LOG CHECK: fallback write failed too. file_put_contents will keep failing.");
        } else {
            log_step("LOG CHECK: fallback write ok to {$fallback}");
        }
    } else {
        log_step("LOG CHECK: OK write to {$path}");
    }
}
ensure_log_writable($bootstrapLog);

// ---------- read and validate POST ----------
$post = $_POST ?? [];
$js_token = trim((string)($post['js_token'] ?? ''));
if ($js_token === '' || strlen($js_token) < 6) {
    log_step("ERROR: invalid js_token len=" . strlen($js_token));
    http_response_code(400);
    echo "Ошибка проверки токена";
    exit;
}
log_step("js_token OK");

$name   = trim((string)($post['child_name'] ?? $post['name'] ?? ''));
$phone  = trim((string)($post['phone'] ?? ''));
$email  = trim((string)($post['email'] ?? ''));
$birth  = trim((string)($post['child_birthdate'] ?? $post['birth'] ?? ''));
$parent = trim((string)($post['parent_name'] ?? $post['parent'] ?? ''));
$wishes = trim((string)($post['wishes'] ?? ''));
$ip     = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

log_step("Form fields: name={$name}, phone={$phone}, email={$email}, birth={$birth}, parent={$parent}, wishes=" . mb_substr($wishes,0,120) . ", ip={$ip}");

if ($name === '' || $phone === '' || $email === '') {
    log_step("ERROR: required fields missing");
    http_response_code(400);
    echo "Заполните все поля формы.";
    exit;
}
log_step("Fields validated");

// ---------- ClickHouse insertion (as before) ----------
try {
    $chHost = cfg(['clickhouse','host'], '');
    if ($chHost) {
        log_step("ClickHouse: configuration present, attempting insert");
        $chConfig = [
            'host'     => $chHost,
            'port'     => intval(cfg(['clickhouse','port'], 8123)),
            'username' => cfg(['clickhouse','user'], cfg(['clickhouse','username'], '')),
            'password' => cfg(['clickhouse','password'], ''),
            'database' => cfg(['clickhouse','database'], 'default'),
        ];
        if (cfg(['clickhouse','https'], false)) {
            $chConfig['https'] = true;
        }
        $logCfg = $chConfig;
        if (!empty($logCfg['password'])) $logCfg['password'] = '***';
        log_step('ClickHouse: params ' . json_encode($logCfg));

        if (!class_exists(ClickHouseClient::class) && !class_exists('ClickHouseDB\\Client')) {
            log_step("ClickHouse ERROR: client class not found (composer/autoload issue)");
            throw new RuntimeException('ClickHouse client class not available');
        }
        $ch = new ClickHouseClient($chConfig);
        $dbWanted = $chConfig['database'];
        try {
            if (method_exists($ch, 'database')) {
                try {
                    $cur = $ch->database();
                    log_step("DEBUG: client->database() => " . var_export($cur, true));
                } catch (Throwable $e) {
                    log_step("DEBUG: client->database() getter threw: " . $e->getMessage());
                }
                try {
                    $ch->database($dbWanted);
                    log_step("DEBUG: client->database({$dbWanted}) called");
                } catch (Throwable $e) {
                    log_step("DEBUG: client->database({$dbWanted}) threw: " . $e->getMessage());
                }
            }
        } catch (Throwable $e) {
            log_step("DEBUG: DB diagnostic exception: " . $e->getMessage());
        }

        $insert_data = [[$name, $phone, $email, $birth, $parent, $wishes, $ip, date('Y-m-d H:i:s')]];
        $insert_cols = ['name','phone','email','birth','parent','wishes','ip','created_at'];
        $tableName = $dbWanted . '.aquastart';
        if (!method_exists($ch, 'insert')) {
            log_step("ClickHouse client does not have insert() method - skipping insert");
        } else {
            $res = $ch->insert($tableName, $insert_data, $insert_cols);
            log_step("ClickHouse insert called into {$tableName}, result: " . json_encode($res));
        }
    } else {
        log_step("ClickHouse: no configuration present, skipping");
    }
} catch (Throwable $e) {
    log_step("ClickHouse ERROR: " . $e->getMessage());
}

// ---------- Telegram notification ----------
function tg_notify(string $text): void {
    $token = cfg(['telegram','token'], '');
    $chat = cfg(['telegram','chat_id'], '');
    if (!$token || !$chat) {
        log_step("tg_notify: skipping, telegram config missing");
        return;
    }
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $payload = [
        'chat_id' => $chat,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    log_step("tg_notify: preview=" . mb_substr($text, 0, 200));
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($resp === false || $code >= 400) {
            log_step("tg_notify ERROR: http_code={$code} curl_err={$err} resp=" . mb_substr((string)$resp,0,300));
        } else {
            log_step("tg_notify OK: http_code={$code}");
        }
    } else {
        $opts = ['http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($payload),
            'timeout' => 6
        ]];
        $ctx = stream_context_create($opts);
        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false) {
            log_step("tg_notify fallback ERROR");
        } else {
            log_step("tg_notify fallback OK");
        }
    }
}

try {
    $tg_msg = "✅ Новая заявка:\n"
        . "👤 " . htmlspecialchars($name) . "\n"
        . "📞 " . htmlspecialchars($phone) . "\n"
        . "✉️ " . htmlspecialchars($email) . "\n"
        . "🎂 " . htmlspecialchars($birth) . "\n"
        . "👪 " . htmlspecialchars($parent) . "\n"
        . "💬 " . htmlspecialchars(mb_substr($wishes,0,500)) . "\n"
        . "IP: " . $ip;
    tg_notify($tg_msg);
    log_step("Telegram notify triggered");
} catch (Throwable $e) {
    log_step("Telegram notify exception: " . $e->getMessage());
}

// ---------- application log ----------
try {
    $app_log_path = cfg(['log_path'], __DIR__ . '/log.txt');
    if (!preg_match('#^/#', $app_log_path)) {
        $app_log_path = __DIR__ . '/' . $app_log_path;
    }
    // Екатеринбургское время
    $line = (new DateTime('now', new DateTimeZone('Asia/Yekaterinburg')))->format('Y-m-d H:i:s')
        . " | {$name} | {$phone} | {$email} | {$birth} | {$parent} | " . str_replace(["\n","\r"], [' ',' '], $wishes) . " | {$ip}" . PHP_EOL;
    safe_file_append($app_log_path, $line);
    log_step("Application log written to {$app_log_path}");
} catch (Throwable $e) {
    log_step("Application log write failed: " . $e->getMessage());
}

// ---------- SMTP diagnostics ----------
function smtp_diagnostics(array $smtp): array {
    $diag = [];
    $host = $smtp['host'] ?? '';
    $port = intval($smtp['port'] ?? 0);
    $enc  = strtolower((string)($smtp['encryption'] ?? ($port === 465 ? 'ssl' : ($port === 587 ? 'tls' : ''))));
    $diag['host'] = $host;
    $diag['port'] = $port;
    $diag['encryption'] = $enc;

    if ($host === '' || $port <= 0) {
        $diag['error'] = 'smtp host or port missing';
        return $diag;
    }

    // DNS resolution
    $resolved = gethostbyname($host);
    $diag['dns_resolve'] = $resolved;
    if ($resolved === $host) {
        // sometimes gethostbyname returns same string on failure; try dns_get_record
        $dns = dns_get_record($host, DNS_A + DNS_AAAA);
        $diag['dns_records'] = $dns ?: [];
    }

    // test TCP/SSL connection
    $timeout = 5;
    $transport = ($enc === 'ssl') ? 'ssl://' : '';
    $target = "{$transport}{$host}:{$port}";
    $errno = 0;
    $errstr = '';
    $ctx = stream_context_create([]);
    log_step("SMTP DIAG: attempting stream_socket_client to {$target} (timeout {$timeout}s)");
    $start = microtime(true);
    $fp = @stream_socket_client($target, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
    $elapsed = round(microtime(true) - $start, 3);
    if ($fp === false) {
        $diag['connect_ok'] = false;
        $diag['connect_error_no'] = $errno;
        $diag['connect_error_str'] = $errstr;
        log_step("SMTP DIAG: connect failed ({$errno}) {$errstr} after {$elapsed}s");
    } else {
        $diag['connect_ok'] = true;
        $diag['connect_time_s'] = $elapsed;
        stream_set_timeout($fp, 2);
        // try to read server banner (if any)
        $banner = @fgets($fp, 512);
        $diag['banner'] = $banner === false ? null : trim($banner);
        log_step("SMTP DIAG: connect OK, banner=" . ($diag['banner'] ?? '(none)'));
        fclose($fp);
    }

    // additional note about OpenSSL
    $diag['openssl_loaded'] = extension_loaded('openssl') ? 'yes' : 'no';
    return $diag;
}

$smtpCfg = cfg(['smtp'], []);
log_step("SMTP config from cfg(): " . json_encode($smtpCfg));
$diagSmtp = smtp_diagnostics($smtpCfg);
log_step("SMTP DIAGNOSTIC RESULT: " . json_encode($diagSmtp));

// ---------- Send email via PHPMailer (improved SMTP handling, debug -> log) ----------
try {
    $smtpHost = $smtpCfg['host'] ?? '';
    if ($smtpHost) {
        log_step("PHPMailer: smtp config present, attempting send");
        $mail = new PHPMailer(true);
        $mail->isSMTP();

        $mailDebug = (bool)($smtpCfg['debug'] ?? cfg(['smtp','debug'], false));
        $mail->SMTPDebug = $mailDebug ? 2 : 0;
        $mail->Debugoutput = function($str, $level) {
            log_step("PHPMailER DEBUG [{$level}]: " . trim($str));
        };

        $mail->CharSet = 'UTF-8';
        $mail->Host = $smtpCfg['host'] ?? $smtpCfg['hostname'] ?? '';
        $mail->SMTPAuth = true;
        $mail->Username = $smtpCfg['username'] ?? $smtpCfg['user'] ?? '';
        $mail->Password = $smtpCfg['password'] ?? '';

        $smtpPort = intval($smtpCfg['port'] ?? 0);
        $configEnc = strtolower((string)($smtpCfg['encryption'] ?? ''));
        if ($configEnc !== '') {
            if (in_array($configEnc, ['ssl','smtps'])) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif (in_array($configEnc, ['tls','starttls'])) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
        } else {
            if ($smtpPort === 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($smtpPort === 587) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
        }
        if ($smtpPort > 0) $mail->Port = $smtpPort;

        // Optional: allow self-signed for debugging
        if (!empty($smtpCfg['allow_self_signed'])) {
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            log_step("PHPMailer: SMTPOptions set to allow self-signed certs (debug)");
        }

        $from = $smtpCfg['from'] ?? 'noreply@localhost';
        $fromName = $smtpCfg['from_name'] ?? 'Дворец водных видов спорта, г. Екатеринбург';
        $mail->setFrom($from, $fromName);
        $mail->addAddress($email, $name);

        $mail->Subject = "Ваша заявка на занятия АкваСтарт принята";
        $mail->isHTML(true);

        $mail->Body = <<<HTML
            <p>Здравствуйте, <b>{$parent}</b>!</p>
            <p>Спасибо за вашу заявку на занятия <b>АкваСтарт</b>. Мы перезвоним Вам после 8 сентября.</p>
            <p>С уважением, команда Дворца водных видов спорта.<br>
            Тел. +7(343)222-22-33</p>
            HTML;
        // mask for config log
        $logSmtp = [
            'host' => $mail->Host,
            'port' => $mail->Port ?? $smtpPort,
            'username' => $mail->Username,
            'password' => $mail->Password ? '***' : '',
            'encryption' => $mail->SMTPSecure ?? '(none)'
        ];
        log_step("PHPMailer config: " . json_encode($logSmtp));

        $mail->send();
        log_step("PHPMailer: mail sent to {$email}");
    } else {
        log_step("PHPMailer: smtp config missing, skipping");
    }
} catch (PHPMailerException $e) {
    log_step("PHPMailer Exception: " . $e->getMessage());
    // attach smtp diagnostic to tg_notify if needed
    // send brief TG notification about mail error (avoid leaking passwords)
    try {
        tg_notify("❗ Ошибка отправки почты: " . htmlspecialchars($e->getMessage()));
    } catch (Throwable $te) {
        log_step("tg_notify failed: " . $te->getMessage());
    }
} catch (Throwable $e) {
    log_step("PHPMailer Other Error: " . $e->getMessage());
}

// ---------- final response ----------
http_response_code(200);
echo "Спасибо! Мы вам перезвоним.";
log_step('SCRIPT END - OK');