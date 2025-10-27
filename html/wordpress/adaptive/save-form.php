<?php
declare(strict_types=1);

/**
 * save-form.php (adaptive)
 * - Обработчик формы адаптации с нейтральным полем "Особенности здоровья" (diagnosis).
 * - Логика по аналогии с aquastart, но таблица ClickHouse: spt.adaptive.
 * - Конфиг ищется в adaptive/config.json или /opt/adaptive/config.json.
 */

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use ClickHouseDB\Client as ClickHouseClient;

date_default_timezone_set('Asia/Yekaterinburg');

ini_set('display_errors', '0');
error_reporting(E_ALL);

// logs
$defaultLog = __DIR__ . '/log.txt';
$bootstrapLog = __DIR__ . '/adaptive_bootstrap.log';

function safe_file_append(string $path, string $data): bool {
    $dir = dirname($path);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $res = @file_put_contents($path, $data, FILE_APPEND | LOCK_EX);
    if ($res === false) {
        global $defaultLog;
        @file_put_contents($defaultLog, $data, FILE_APPEND | LOCK_EX);
        return false;
    }
    return true;
}
function log_step(string $msg): void {
    global $bootstrapLog;
    $ts = (new DateTime('now', new DateTimeZone('Asia/Yekaterinburg')))->format('Y-m-d\TH:i:sP');
    safe_file_append($bootstrapLog, "{$ts} | {$msg}\n");
}
register_shutdown_function(function(): void {
    $err = error_get_last();
    if ($err !== null) {
        log_step("SHUTDOWN ERROR: type={$err['type']} file={$err['file']} line={$err['line']} message={$err['message']}");
    } else {
        log_step("SHUTDOWN: normal exit");
    }
});

// load config
$configPaths = [ __DIR__ . '/config.json', '/opt/adaptive/config.json' ];
$config = [];
$configPathUsed = null;
foreach ($configPaths as $p) {
    log_step("Check cfg: {$p}");
    if (is_readable($p)) {
        $raw = @file_get_contents($p);
        $parsed = $raw !== false ? json_decode($raw, true) : null;
        if (is_array($parsed)) { $config = $parsed; $configPathUsed = $p; break; }
    }
}
if ($configPathUsed) log_step("Config loaded: {$configPathUsed}"); else log_step("Config not found, using defaults");

$logPath = $config['log_path'] ?? $defaultLog;
if (!preg_match('#^/#', $logPath)) $logPath = __DIR__ . '/' . $logPath;
$bootstrapLog = $logPath;
log_step("Effective log: {$bootstrapLog}");

function cfg(array $keys, $default = null) {
    global $config;
    $cur = $config;
    foreach ($keys as $k) {
        if (!is_array($cur) || !array_key_exists($k, $cur)) return $default;
        $cur = $cur[$k];
    }
    return $cur;
}

// ensure log writable
@file_put_contents($bootstrapLog, '', FILE_APPEND | LOCK_EX);

// read POST
$post = $_POST ?? [];
$js_token = trim((string)($post['js_token'] ?? ''));
if ($js_token === '' || strlen($js_token) < 6) {
    log_step("ERROR: invalid js_token");
    http_response_code(400);
    echo "Ошибка проверки токена";
    exit;
}

$name   = trim((string)($post['child_name'] ?? $post['name'] ?? ''));
$phone  = trim((string)($post['phone'] ?? ''));
$email  = trim((string)($post['email'] ?? ''));
$birth  = trim((string)($post['child_birthdate'] ?? $post['birth'] ?? ''));
$parent = trim((string)($post['parent_name'] ?? $post['parent'] ?? ''));
$wishes = trim((string)($post['wishes'] ?? ''));
$diagnosis = trim((string)($post['diagnosis'] ?? $post['health_notes'] ?? '')); // нейтральное поле с формы
$ip     = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

log_step("POST: name={$name}, phone={$phone}, email={$email}, birth={$birth}, parent={$parent}, diagnosis-len=" . mb_strlen($diagnosis) . ", wishes-len=" . mb_strlen($wishes) . ", ip={$ip}");

if ($name === '' || $phone === '' || $email === '' || $birth === '' || $parent === '' || $wishes === '') {
    http_response_code(400);
    echo "Заполните все обязательные поля формы.";
    exit;
}

// ClickHouse insert
try {
    $chHost = cfg(['clickhouse','host'], '');
    if ($chHost) {
        $chConfig = [
            'host'     => $chHost,
            'port'     => intval(cfg(['clickhouse','port'], 8123)),
            'username' => cfg(['clickhouse','user'], cfg(['clickhouse','username'], '')),
            'password' => cfg(['clickhouse','password'], ''),
            'database' => cfg(['clickhouse','database'], 'default'),
        ];
        if (cfg(['clickhouse','https'], false)) $chConfig['https'] = true;

        $client = new ClickHouseClient($chConfig);
        $dbWanted = $chConfig['database'];

        $tableName = $dbWanted . '.adaptive';
        $insert_cols = ['name','phone','email','birth','parent','diagnosis','wishes','ip','created_at'];
        $insert_data = [[$name,$phone,$email,$birth,$parent,$diagnosis,$wishes,$ip, date('Y-m-d H:i:s')]];
        if (method_exists($client, 'insert')) {
            $res = $client->insert($tableName, $insert_data, $insert_cols);
            log_step("CH insert into {$tableName}: " . json_encode($res));
        } else {
            log_step("CH client has no insert(); skipping");
        }
    } else {
        log_step("CH config missing; skip insert");
    }
} catch (Throwable $e) {
    log_step("ClickHouse ERROR: " . $e->getMessage());
}

// Telegram
function tg_notify(string $text): void {
    $token = cfg(['telegram','token'], '');
    $chat  = cfg(['telegram','chat_id'], '');
    if (!$token || !$chat) { log_step("tg: skip, no cfg"); return; }
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $payload = ['chat_id'=>$chat, 'text'=>$text, 'parse_mode'=>'HTML'];
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>$payload,
            CURLOPT_TIMEOUT=>6,
            CURLOPT_CONNECTTIMEOUT=>6,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($resp === false || $code >= 400) log_step("tg ERROR code={$code} err={$err}");
    } else {
        @file_get_contents($url, false, stream_context_create(['http'=>[
            'method'=>'POST',
            'header'=>'Content-Type: application/x-www-form-urlencoded',
            'content'=>http_build_query($payload),
            'timeout'=>6
        ]]));
    }
}
try {
    $tg = "✅ Заявка на группу адаптации:\n"
        . "👤 " . htmlspecialchars($name) . "\n"
        . "📞 " . htmlspecialchars($phone) . "\n"
        . "✉️ " . htmlspecialchars($email) . "\n"
        . "🎂 " . htmlspecialchars($birth) . "\n"
        . "👪 " . htmlspecialchars($parent) . "\n"
        . "🩺 Особенности: " . htmlspecialchars(mb_substr($diagnosis,0,500)) . "\n"
        . "💬 Пожелания: " . htmlspecialchars(mb_substr($wishes,0,500)) . "\n"
        . "IP: " . $ip;
    tg_notify($tg);
} catch (Throwable $e) {
    log_step("tg notify exception: " . $e->getMessage());
}

// Application log
try {
    $app_log_path = cfg(['log_path'], __DIR__ . '/log.txt');
    if (!preg_match('#^/#', $app_log_path)) $app_log_path = __DIR__ . '/' . $app_log_path;
    $line = (new DateTime('now', new DateTimeZone('Asia/Yekaterinburg')))->format('Y-m-d H:i:s')
        . " | {$name} | {$phone} | {$email} | {$birth} | {$parent} | " . str_replace(["\n","\r"], [' ',' '], $diagnosis)
        . " | " . str_replace(["\n","\r"], [' ',' '], $wishes) . " | {$ip}\n";
    safe_file_append($app_log_path, $line);
} catch (Throwable $e) {
    log_step("App log error: " . $e->getMessage());
}

// Mail (optional confirmation to parent)
try {
    $smtp = cfg(['smtp'], []);
    if (!empty($smtp['host'])) {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host = $smtp['host'] ?? '';
        $mail->SMTPAuth = true;
        $mail->Username = $smtp['username'] ?? $smtp['user'] ?? '';
        $mail->Password = $smtp['password'] ?? '';
        $port = intval($smtp['port'] ?? 465);
        $mail->Port = $port;
        if (($smtp['encryption'] ?? '') === 'tls' || $port === 587) $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        else $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

        if (!empty($smtp['allow_self_signed'])) {
            $mail->SMTPOptions = ['ssl'=>[
                'verify_peer'=>false,'verify_peer_name'=>false,'allow_self_signed'=>true
            ]];
        }

        $mail->setFrom($smtp['from'] ?? 'noreply@localhost', 'ДВВС Екатеринбург');
        $mail->addAddress($email, $parent !== '' ? $parent : $name);
        $mail->Subject = "Ваша заявка на группу адаптации принята";
        $mail->isHTML(true);
        $safeDiag = htmlspecialchars($diagnosis);
        $mail->Body = <<<HTML
            <p>Здравствуйте, <b>{$parent}</b>!</p>
            <p>Спасибо за вашу заявку в <b>группу адаптации</b>. Наш специалист свяжется с вами в ближайшее время.</p>
            <p><i>Если вы указали особенности здоровья</i>: мы учтём эту информацию при подборе тренера и группы.</p>
            <p>С уважением, команда Дворца водных видов спорта.<br>
            Тел. +7(343)222-22-33</p>
            HTML;
        $mail->send();
        log_step("Mail sent to {$email}");
    } else {
        log_step("SMTP config missing; skip mail");
    }
} catch (PHPMailerException $e) {
    log_step("Mail error: " . $e->getMessage());
} catch (Throwable $e) {
    log_step("Mail other error: " . $e->getMessage());
}

http_response_code(200);
echo "Спасибо! Мы вам перезвоним.";
log_step('END OK');