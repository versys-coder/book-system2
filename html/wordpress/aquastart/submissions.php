<?php
declare(strict_types=1);
ini_set('display_errors', 1);
/**
 * submissions.php
 * - Просмотр и экспорт заявок из ClickHouse
 * - Исправлена сортировка по дате рождения и возрасту (используем parseDateTimeBestEffort в ORDER BY)
 * - Сузил колонку "Дата подачи заявки"
 * - Улучшил отображение E-mail (не ломается перенос; показывает эллипсис + тултип)
 * - Остальной интерфейс — серо-белое чередование строк, тонкие линии
 *
 * Требуется: composer require phpoffice/phpspreadsheet
 */

require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/* ----------------------- Config ----------------------- */
function safe_read_config(): array {
    $paths = [
        __DIR__ . '/config.json',
        '/opt/aquastart/config.json'
    ];
    foreach ($paths as $p) {
        if (is_readable($p)) {
            $raw = @file_get_contents($p);
            if ($raw === false) continue;
            $parsed = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                return $parsed;
            }
        }
    }
    return [];
}
$config = safe_read_config();
$authPass = $config['web']['pass'] ?? 'aquastart123';

/* ----------------------- Simple front-page password (session) ----------------------- */
session_start();

if (!isset($_SESSION['aquastart_auth']) || $_SESSION['aquastart_auth'] !== true) {
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apass'])) {
        if ((string)$_POST['apass'] === (string)$authPass) {
            session_regenerate_id(true);
            $_SESSION['aquastart_auth'] = true;
            $redirect = strtok($_SERVER['REQUEST_URI'], '?');
            header('Location: ' . ($redirect === false ? '/' : $redirect));
            exit;
        } else {
            $error = 'Неверный пароль';
        }
    }

    // show password form
    ?>
    <!doctype html>
    <html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Вход — Aquastart</title>
        <style>
        body{font-family:Inter, Arial, Helvetica, sans-serif;background:#f3f6fa;color:#222;margin:0;height:100vh;display:flex;align-items:center;justify-content:center}
        .authbox{
            width:360px;
            max-width:100vw;
            padding:22px;
            background:#fff;
            border-radius:10px;
            box-shadow:0 6px 24px rgba(24,39,75,0.06);
        }
        h2{margin:0 0 12px 0;font-size:20px}
        .err{color:#b00020;margin-bottom:10px;font-size:14px;}
        label{display:block;margin-bottom:6px;font-size:13px}
        input[type=password]{
            width:100%;
            max-width:100%;
            box-sizing:border-box;
            padding:10px 12px;
            border:1px solid #e6eef8;
            border-radius:8px;
            font-size:16px;
            background:#fbfdff;
        }
        button{margin-top:14px;background:#0b69ff;color:#fff;padding:10px 14px;border-radius:8px;border:none;cursor:pointer;font-size:15px}
        .hint{margin-top:10px;color:#666;font-size:13px}
        </style>
    </head>
    <body>
        <form method="post" class="authbox" autocomplete="off">
            <h2>Введите пароль</h2>
            <?php if($error): ?><div class="err"><?=htmlspecialchars($error)?></div><?php endif; ?>
            <label for="apass">Пароль</label>
            <input type="password" name="apass" id="apass" required autofocus autocomplete="current-password">
            <button type="submit">Войти</button>
            <div class="hint">Введите пароль для доступа к заявкам.</div>
        </form>
    </body>
    </html>
    <?php
    exit;
}

/* ----------------------- Helpers ----------------------- */
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function ch_escape(string $s): string { return str_replace("'", "\\'", $s); }

function ch_query_json(string $sql): array {
    $conf = safe_read_config();
    $host = $conf['clickhouse']['host'] ?? '';
    $port = intval($conf['clickhouse']['port'] ?? 8123);
    $user = $conf['clickhouse']['user'] ?? ($conf['clickhouse']['username'] ?? '');
    $pass = $conf['clickhouse']['password'] ?? '';
    $https = !empty($conf['clickhouse']['https']);
    if ($host === '') throw new RuntimeException('ClickHouse host not configured');
    $scheme = $https ? 'https' : 'http';
    $base = "{$scheme}://{$host}:{$port}";
    $url = $base . "/?query=" . urlencode($sql);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
    if ($user !== '' || $pass !== '') curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $pass);
    if (!empty($conf['clickhouse']['allow_self_signed'])) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }
    $resp = curl_exec($ch);
    $errno = curl_errno($ch);
    $errstr = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($resp === false) throw new RuntimeException("ClickHouse HTTP error: ({$errno}) {$errstr}");
    if ($http_code >= 400) throw new RuntimeException("ClickHouse HTTP returned status {$http_code}: " . $resp);
    $json = json_decode($resp, true);
    if (json_last_error() !== JSON_ERROR_NONE) throw new RuntimeException('Invalid JSON from ClickHouse: ' . json_last_error_msg());
    return isset($json['data']) && is_array($json['data']) ? $json['data'] : (is_array($json) ? $json : []);
}

function ch_count(string $table, string $where_sql = ''): int {
    $sql = "SELECT count() AS cnt FROM {$table} " . ($where_sql ? $where_sql . ' ' : '') . "FORMAT JSON";
    $data = ch_query_json($sql);
    if (!empty($data) && isset($data[0]['cnt'])) return (int)$data[0]['cnt'];
    if (!empty($data) && array_key_exists('count()', $data[0] ?? [])) return (int)$data[0]['count()'];
    return 0;
}

function format_birth(string $s): string {
    $s = trim($s);
    if ($s === '') return '';
    if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $s)) return $s;
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $s, $m)) {
        return "{$m[3]}.{$m[2]}.{$m[1]}";
    }
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})\s+/', $s, $m2)) {
        return "{$m2[3]}.{$m2[2]}.{$m2[1]}";
    }
    return $s;
}

function parse_birth_to_date(?string $s): ?DateTimeImmutable {
    if ($s === null) return null;
    $s = trim($s);
    if ($s === '') return null;
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $s, $m)) {
        try { return new DateTimeImmutable("{$m[1]}-{$m[2]}-{$m[3]}"); } catch (Exception $e) { return null; }
    }
    if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})/', $s, $m2)) {
        try { return new DateTimeImmutable("{$m2[3]}-{$m2[2]}-{$m2[1]}"); } catch (Exception $e) { return null; }
    }
    try { return new DateTimeImmutable($s); } catch (Exception $e) { return null; }
}

function compute_age_years(?string $birth): ?int {
    $d = parse_birth_to_date($birth);
    if ($d === null) return null;
    $now = new DateTimeImmutable('today');
    $diff = $now->diff($d);
    return (int)$diff->y;
}

/* ----------------------- Request params ----------------------- */
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;
$q = trim((string)($_GET['q'] ?? ''));
$export = trim((string)($_GET['export'] ?? ''));

// Sorting: allowed columns
$allowed_sorts = [
    'created_at' => 'created_at',
    'name' => 'name',
    'birth' => 'birth',
    'age' => 'age', // virtual
    'parent' => 'parent',
    'phone' => 'phone',
    'email' => 'email',
    'wishes' => 'wishes'
];
$sort = strtolower((string)($_GET['sort'] ?? 'created_at'));
if (!array_key_exists($sort, $allowed_sorts)) $sort = 'created_at';
$order = strtolower((string)($_GET['order'] ?? 'desc'));
if (!in_array($order, ['asc','desc'], true)) $order = 'desc';

/* ----------------------- SQL & Columns ----------------------- */
$db = $config['clickhouse']['database'] ?? 'default';
$table = $db . '.aquastart';
$cols = ['name','birth','parent','phone','email','wishes','created_at'];

$where_parts = [];
if ($q !== '') {
    $q_esc = ch_escape($q);
    $where_parts[] = "(name LIKE '%{$q_esc}%' OR phone LIKE '%{$q_esc}%' OR email LIKE '%{$q_esc}%' OR wishes LIKE '%{$q_esc}%')";
}
$where_sql = '';
if (!empty($where_parts)) $where_sql = 'WHERE ' . implode(' AND ', $where_parts);

try {
    $total = ch_count($table, $where_sql);
} catch (Throwable $e) {
    http_response_code(500);
    echo "<!doctype html><html><head><meta charset='utf-8'><title>Ошибка</title></head><body>";
    echo "<h1>Ошибка запроса к ClickHouse</h1>";
    echo "<pre>" . h($e->getMessage()) . "</pre>";
    echo "</body></html>";
    exit;
}

// Build ORDER BY clause
// Use parseDateTimeBestEffort(birth) for stable ordering by birth; for age invert direction by birth
$order_by_sql = '';
if ($sort === 'age') {
    // age desc -> birth asc (older first)
    $birth_dir = ($order === 'desc') ? 'ASC' : 'DESC';
    // use parseDateTimeBestEffort to convert various formats to DateTime; NULLs go last
    $order_by_sql = "ORDER BY parseDateTimeBestEffort(birth) {$birth_dir} NULLS LAST";
} elseif ($sort === 'birth') {
    $order_by_sql = "ORDER BY parseDateTimeBestEffort(birth) " . strtoupper($order) . " NULLS LAST";
} else {
    $col_sql = $allowed_sorts[$sort];
    $order_by_sql = "ORDER BY {$col_sql} " . strtoupper($order);
}

$cols_sql = implode(',', $cols);

if ($export === 'xlsx') {
    // For export we don't need LIMIT
    $sql = "SELECT {$cols_sql} FROM {$table} {$where_sql} {$order_by_sql} FORMAT JSON";
} else {
    $sql = "SELECT {$cols_sql} FROM {$table} {$where_sql} {$order_by_sql} LIMIT {$per_page} OFFSET {$offset} FORMAT JSON";
}

try {
    $rows = ch_query_json($sql);
} catch (Throwable $e) {
    http_response_code(500);
    echo "<!doctype html><html><head><meta charset='utf-8'><title>Ошибка</title></head><body>";
    echo "<h1>Ошибка запроса к ClickHouse</h1>";
    echo "<pre>" . h($e->getMessage()) . "</pre>";
    echo "</body></html>";
    exit;
}
if (!is_array($rows)) $rows = [];

/* ----------------------- Export XLSX ----------------------- */
if ($export === 'xlsx') {
    if (!class_exists(Spreadsheet::class)) {
        http_response_code(500);
        echo "<!doctype html><html><head><meta charset='utf-8'><title>Export Error</title></head><body>";
        echo "<h1>Ошибка экспорта</h1>";
        echo "<p>Библиотека PhpSpreadsheet не установлена. Запустите в директории проекта:</p>";
        echo "<pre>composer require phpoffice/phpspreadsheet</pre>";
        echo "</body></html>";
        exit;
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Headers: created_at first (Дата подачи заявки)
    $headers = [
        'Дата подачи заявки',
        'Фамилия и имя ребёнка',
        'Дата рождения ребёнка',
        'Возраст (лет)',
        'ФИО родителя',
        'Моб. телефон родителя',
        'E-mail',
        'Пожелания'
    ];

    // Заголовки
    foreach ($headers as $i => $title) {
        $cell = Coordinate::stringFromColumnIndex($i + 1) . '1';
        $sheet->setCellValue($cell, $title);
    }

    // Данные: заполнить в том же порядке
    foreach ($rows as $rIndex => $r) {
        $created_at = $r['created_at'] ?? '';
        $name = $r['name'] ?? '';
        $birth_raw = $r['birth'] ?? '';
        $birth_fmt = format_birth((string)$birth_raw);
        $age = compute_age_years((string)$birth_raw);
        $age_str = $age === null ? '' : (string)$age;
        $parent = $r['parent'] ?? '';
        $phone = $r['phone'] ?? '';
        $email = $r['email'] ?? '';
        $wishes = $r['wishes'] ?? '';

        $values = [
            $created_at,
            $name,
            $birth_fmt,
            $age_str,
            $parent,
            $phone,
            $email,
            $wishes
        ];

        foreach ($values as $colIndex => $val) {
            $cell = Coordinate::stringFromColumnIndex($colIndex + 1) . ($rIndex + 2);
            $sheet->setCellValueExplicit($cell, (string)$val, DataType::TYPE_STRING);
        }
    }

    $safeName = 'submissions_' . date('Y-m-d_H-i') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $safeName . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

/* ----------------------- HTML output ----------------------- */
$total_pages = max(1, (int)ceil($total / $per_page));

// Helper to build query string keeping q, sort, order, page
function build_qs(array $overrides = []): string {
    $params = [];
    $params['q'] = $overrides['q'] ?? ($_GET['q'] ?? '');
    if (isset($overrides['sort'])) $params['sort'] = $overrides['sort'];
    elseif (isset($_GET['sort'])) $params['sort'] = $_GET['sort'];
    if (isset($overrides['order'])) $params['order'] = $overrides['order'];
    elseif (isset($_GET['order'])) $params['order'] = $_GET['order'];
    if (isset($overrides['page'])) $params['page'] = $overrides['page'];
    elseif (isset($_GET['page'])) $params['page'] = $_GET['page'];
    foreach ($params as $k => $v) {
        if ($v === '' || $v === null) unset($params[$k]);
    }
    return http_build_query($params);
}

function header_sort_link(string $col_key, string $label, string $current_sort, string $current_order, string $q): string {
    $is_current = ($col_key === $current_sort);
    $next_order = 'asc';
    if ($is_current && strtolower($current_order) === 'asc') $next_order = 'desc';
    $qs = build_qs(['q' => $q, 'sort' => $col_key, 'order' => $next_order, 'page' => 1]);
    $arrow = '';
    if ($is_current) {
        $arrow = strtolower($current_order) === 'asc' ? ' ↑' : ' ↓';
    }
    return '<a class="header-link" href="?' . h($qs) . '">' . h($label) . $arrow . '</a>';
}

?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Заявки — Aquastart</title>
<style>
:root{
  --bg:#f7f9fc;
  --card:#fff;
  --muted:#6b7280;
  --accent:#0b69ff;
  --border:#e9eef6;
  --thin-border:#eef3f8;
  --line:#e6eef8;
  --text:#0f1724;
}
body{font-family:Inter, Arial, Helvetica, sans-serif;margin:18px;background:var(--bg);color:var(--text)}
.container{max-width:1280px;margin:0 auto;background:var(--card);padding:18px;border-radius:12px;box-shadow:0 10px 30px rgba(16,24,40,0.06)}
h1{margin:0 0 12px 0;font-size:22px;display:flex;align-items:center;gap:16px}
.total-box{font-size:15px;color:var(--accent);font-weight:700;margin-left:4px}
.toolbar{display:flex;gap:10px;align-items:center;margin:12px 0 10px 0;flex-wrap:wrap}
input[type=text]{padding:10px 12px;border:1px solid var(--line); border-radius:10px;min-width:420px;font-size:14px;background:#fff}
button, a.button{background:var(--accent);color:#fff;padding:9px 14px;border-radius:8px;text-decoration:none;border:none;cursor:pointer;display:inline-block;font-size:14px}
.table-wrap{overflow:auto;border:1px solid var(--thin-border);border-radius:10px;background:linear-gradient(180deg,#fff,#fbfdff)}
table{width:100%;border-collapse:collapse;table-layout:auto;min-width:980px}
/* columns widths via colgroup classes; "Дата подачи" чуть уже */
col.col-created{width:110px;min-width:110px}
col.col-name{width:22%}
col.col-birth{width:10%}
col.col-age{width:6%}
col.col-parent{width:20%}
col.col-phone{width:10%}
col.col-email{width:12%}
col.col-wishes{width:10%}

/* header */
thead th{
    background:linear-gradient(180deg,#f8fbff,#ffffff);
    font-weight:700;
    text-align:left;
    padding:10px 12px;
    font-size:13px;
    color:#15305a;
    border-bottom:1px solid var(--border);
    border-right:1px solid var(--thin-border);
    white-space:normal;       /* allow wrap to avoid huge headers */
    line-height:1.1;
    vertical-align:bottom;
}
thead th:last-child{border-right:0}

/* body cells */
tbody td{
    padding:14px 12px;
    font-size:14px;
    color:var(--text);
    border-bottom:1px solid var(--thin-border);
    border-right:1px solid var(--thin-border);
    vertical-align:top;
    word-break:break-word;
    background-clip:padding-box;
}
tbody td:last-child{border-right:0}

/* zebra rows */
tbody tr:nth-child(odd){background:#fff}
tbody tr:nth-child(even){background:#fbfdff}

/* hover */
tbody tr:hover{background:#f4f8ff}

/* alignment specifics */
td.col-center {text-align:center}
td.col-monospaced {font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, "Roboto Mono", monospace; font-size:13px}

/* email ellipsis -- keep on single line, show full on hover via title */
span.ellipsis{
    display:inline-block;
    max-width:100%;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    vertical-align:middle;
    max-width:220px; /* tweak: max visible width for email */
}

/* header links styling */
.header-link{color:inherit;text-decoration:none;display:inline-block;max-width:100%}
.header-link:hover{text-decoration:underline}

/* smaller, muted helper text */
.small{color:var(--muted);font-size:13px}
.note{color:#6b7280;font-size:13px;margin-top:10px}

/* responsive tweaks */
@media (max-width:1100px){
  col.col-name{width:26%}
  col.col-parent{width:22%}
  col.col-email{width:16%}
  input[type=text]{min-width:220px}
}
@media (max-width:800px){
  table{min-width:900px}
  .toolbar{gap:8px}
  input[type=text]{min-width:160px}
}
</style>
</head>
<body>
<div class="container">
  <h1>Заявки на АкваСтарт <span class="total-box">Всего: <?= h((string)$total) ?></span></h1>

  <div class="toolbar">
    <form method="get" style="display:inline-flex;gap:8px;align-items:center;flex-wrap:wrap;">
      <input type="text" name="q" value="<?= h($q) ?>" placeholder="Полнотекстовый поиск: имя, телефон, email, пожелания" />
      <button type="submit">Поиск</button>
      <a class="button" href="?<?= ($q!=='' ? 'q='.urlencode($q).'&' : '') ?><?= $sort!=='' ? 'sort='.urlencode($sort).'&' : '' ?><?= $order!=='' ? 'order='.urlencode($order).'&' : '' ?>export=xlsx">Экспорт XLSX</a>
    </form>

    <div style="margin-left:auto" class="small">
      Страница <?= h((string)$page) ?> из <?= h((string)$total_pages) ?>
    </div>
  </div>

  <div class="table-wrap" aria-live="polite">
    <table role="table" aria-label="Заявки">
      <colgroup>
        <col class="col-created" />
        <col class="col-name" />
        <col class="col-birth" />
        <col class="col-age" />
        <col class="col-parent" />
        <col class="col-phone" />
        <col class="col-email" />
        <col class="col-wishes" />
      </colgroup>
      <thead>
        <tr>
          <th><?= header_sort_link('created_at', 'Дата подачи заявки', $sort, $order, $q) ?></th>
          <th><?= header_sort_link('name', 'Фамилия и имя ребёнка', $sort, $order, $q) ?></th>
          <th><?= header_sort_link('birth', 'Дата рождения ребёнка', $sort, $order, $q) ?></th>
          <th><?= header_sort_link('age', 'Возраст (лет)', $sort, $order, $q) ?></th>
          <th><?= header_sort_link('parent', 'ФИО родителя', $sort, $order, $q) ?></th>
          <th><?= header_sort_link('phone', 'Моб. телефон родителя', $sort, $order, $q) ?></th>
          <th><?= header_sort_link('email', 'E-mail', $sort, $order, $q) ?></th>
          <th><?= header_sort_link('wishes', 'Пожелания', $sort, $order, $q) ?></th>
        </tr>
      </thead>
      <tbody>
<?php if (empty($rows)): ?>
        <tr><td colspan="8" class="small">Записей не найдено.</td></tr>
<?php else: foreach ($rows as $r): ?>
        <tr>
          <td class="col-created col-monospaced"><?= h($r['created_at'] ?? '') ?></td>
          <td><?= h($r['name'] ?? '') ?></td>
          <td class="col-birth col-center"><?= h(format_birth((string)($r['birth'] ?? ''))) ?></td>
          <td class="col-age col-center"><?= h((string) (compute_age_years((string)($r['birth'] ?? '')) ?? '')) ?></td>
          <td><?= h($r['parent'] ?? '') ?></td>
          <td class="col-phone"><?= h($r['phone'] ?? '') ?></td>
          <td class="col-email"><span class="ellipsis" title="<?= h($r['email'] ?? '') ?>"><?= h($r['email'] ?? '') ?></span></td>
          <td><?= h(mb_substr($r['wishes'] ?? '', 0, 1000)) ?></td>
        </tr>
<?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="pagination" style="margin-top:12px;display:flex;gap:8px;align-items:center">
    <?php if ($page > 1): ?>
      <a class="button" href="?<?= build_qs(['page' => $page-1, 'q' => $q, 'sort' => $sort, 'order' => $order]) ?>">← Назад</a>
    <?php endif; ?>
    <?php if ($page < $total_pages): ?>
      <a class="button" href="?<?= build_qs(['page' => $page+1, 'q' => $q, 'sort' => $sort, 'order' => $order]) ?>">Вперёд →</a>
    <?php endif; ?>
  </div>

  <div class="note">
    Экспорт XLSX требует установленной библиотеки phpoffice/phpspreadsheet (composer).<br>
    Сортировка по "Возраст" использует дату рождения (поле birth). Мы используем parseDateTimeBestEffort(birth) для корректной сортировки по разным форматам даты; если дата не распознана — такие записи отображаются после распознанных (NULLS LAST).
  </div>
</div>
</body>
</html>