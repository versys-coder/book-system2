<?php
declare(strict_types=1);

/**
 * submissions.php (adaptive)
 * - Просмотр и экспорт заявок из ClickHouse для формы адаптации.
 * - Поиск включает diagnosis и wishes, экспорт XLSX с автошириной и жирными заголовками.
 * - Формат даты created_at в таблице и XLSX: DD.MM.YYYY HH:MM (без секунд).
 * - Телефон в таблице не переносится (nowrap).
 * - Пагинация: по 20 записей на страницу.
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
        '/opt/adaptive/config.json'
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
$authPass = $config['web']['pass'] ?? 'Adaptive';

/* ----------------------- Auth ----------------------- */
session_start();
if (!isset($_SESSION['adaptive_auth']) || $_SESSION['adaptive_auth'] !== true) {
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apass'])) {
        if ((string)$_POST['apass'] === (string)$authPass) {
            session_regenerate_id(true);
            $_SESSION['adaptive_auth'] = true;
            $redirect = strtok($_SERVER['REQUEST_URI'], '?');
            header('Location: ' . ($redirect === false ? '/' : $redirect));
            exit;
        } else $error = 'Неверный пароль';
    }
    ?>
    <!doctype html>
    <html lang="ru"><head>
        <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Вход — Adaptive</title>
        <style>
        body{font-family:Inter, Arial, Helvetica, sans-serif;background:#f5f7fb;color:#222;margin:0;height:100vh;display:flex;align-items:center;justify-content:center}
        .authbox{width:360px;max-width:100vw;padding:22px;background:#fff;border-radius:10px;box-shadow:0 6px 24px rgba(24,39,75,0.06)}
        h2{margin:0 0 12px 0;font-size:19px}
        .err{color:#b00020;margin-bottom:10px;font-size:14px;}
        label{display:block;margin-bottom:6px;font-size:13px}
        input[type=password]{width:100%;max-width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #e6eef8;border-radius:8px;font-size:15px;background:#fbfdff}
        button{margin-top:14px;background:#0b69ff;color:#fff;padding:10px 14px;border-radius:8px;border:none;cursor:pointer;font-size:14px}
        .hint{margin-top:10px;color:#666;font-size:12px}
        </style>
    </head><body>
        <form method="post" class="authbox" autocomplete="off">
            <h2>Введите пароль</h2>
            <?php if($error): ?><div class="err"><?=htmlspecialchars($error)?></div><?php endif; ?>
            <label for="apass">Пароль</label>
            <input type="password" name="apass" id="apass" required autofocus autocomplete="current-password">
            <button type="submit">Войти</button>
            <div class="hint">Доступ к заявкам адаптации.</div>
        </form>
    </body></html>
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
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $s, $m)) return "{$m[3]}.{$m[2]}.{$m[1]}";
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})\s+/', $s, $m2)) return "{$m2[3]}.{$m2[2]}.{$m2[1]}";
    return $s;
}
function parse_birth_to_date(?string $s): ?DateTimeImmutable {
    if ($s === null) return null;
    $s = trim($s);
    if ($s === '') return null;
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $s, $m)) { try { return new DateTimeImmutable("{$m[1]}-{$m[2]}-{$m[3]}"); } catch (Exception $e) { return null; } }
    if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})/', $s, $m2)) { try { return new DateTimeImmutable("{$m2[3]}-{$m2[2]}-{$m2[1]}"); } catch (Exception $e) { return null; } }
    try { return new DateTimeImmutable($s); } catch (Exception $e) { return null; }
}
function compute_age_years(?string $birth): ?int {
    $d = parse_birth_to_date($birth);
    if ($d === null) return null;
    $now = new DateTimeImmutable('today');
    return (int)$now->diff($d)->y;
}
function format_created_dt(?string $s): string {
    if (!$s) return '';
    $s = trim($s);
    if ($s === '') return '';
    // ClickHouse обычно отдает: 'YYYY-MM-DD HH:MM:SS' или 'YYYY-MM-DD'
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2}))?/', $s, $m)) {
        $h = isset($m[4]) ? $m[4] : '00';
        $i = isset($m[5]) ? $m[5] : '00';
        return sprintf('%02d.%02d.%04d %02d:%02d', (int)$m[3], (int)$m[2], (int)$m[1], (int)$h, (int)$i);
    }
    try {
        $dt = new DateTimeImmutable($s);
        return $dt->format('d.m.Y H:i');
    } catch (Throwable $e) {
        return $s;
    }
}

/* ----------------------- Request params ----------------------- */
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20; // требование: 20 на страницу
$offset = ($page - 1) * $per_page;
$q = trim((string)($_GET['q'] ?? ''));
$export = trim((string)($_GET['export'] ?? ''));

$allowed_sorts = [
    'created_at' => 'created_at',
    'name' => 'name',
    'birth' => 'birth',
    'age' => 'age', // virtual
    'parent' => 'parent',
    'phone' => 'phone',
    'email' => 'email',
    'diagnosis' => 'diagnosis',
    'wishes' => 'wishes'
];
$sort = strtolower((string)($_GET['sort'] ?? 'created_at'));
if (!array_key_exists($sort, $allowed_sorts)) $sort = 'created_at';
$order = strtolower((string)($_GET['order'] ?? 'desc'));
if (!in_array($order, ['asc','desc'], true)) $order = 'desc';

/* ----------------------- SQL & Columns ----------------------- */
$db = $config['clickhouse']['database'] ?? 'default';
$table = $db . '.adaptive';
$cols = ['name','birth','parent','phone','email','diagnosis','wishes','created_at'];

$where_parts = [];
if ($q !== '') {
    $q_esc = ch_escape($q);
    $where_parts[] = "(name LIKE '%{$q_esc}%' OR phone LIKE '%{$q_esc}%' OR email LIKE '%{$q_esc}%' OR wishes LIKE '%{$q_esc}%' OR diagnosis LIKE '%{$q_esc}%')";
}
$where_sql = '';
if (!empty($where_parts)) $where_sql = 'WHERE ' . implode(' AND ', $where_parts);

try { $total = ch_count($table, $where_sql); }
catch (Throwable $e) {
    http_response_code(500);
    echo "<!doctype html><html><head><meta charset='utf-8'><title>Ошибка</title></head><body>";
    echo "<h1>Ошибка запроса к ClickHouse</h1>";
    echo "<pre>" . h($e->getMessage()) . "</pre>";
    echo "</body></html>";
    exit;
}

if ($sort === 'age') {
    $birth_dir = ($order === 'desc') ? 'ASC' : 'DESC';
    $order_by_sql = "ORDER BY parseDateTimeBestEffort(birth) {$birth_dir} NULLS LAST";
} elseif ($sort === 'birth') {
    $order_by_sql = "ORDER BY parseDateTimeBestEffort(birth) " . strtoupper($order) . " NULLS LAST";
} else {
    $col_sql = $allowed_sorts[$sort];
    $order_by_sql = "ORDER BY {$col_sql} " . strtoupper($order);
}
$cols_sql = implode(',', $cols);

if ($export === 'xlsx') {
    $sql = "SELECT {$cols_sql} FROM {$table} {$where_sql} {$order_by_sql} FORMAT JSON";
} else {
    $sql = "SELECT {$cols_sql} FROM {$table} {$where_sql} {$order_by_sql} LIMIT {$per_page} OFFSET {$offset} FORMAT JSON";
}

try { $rows = ch_query_json($sql); }
catch (Throwable $e) {
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
        echo "<p>Библиотека PhpSpreadsheet не установлена. Запустите:</p>";
        echo "<pre>composer require phpoffice/phpspreadsheet</pre>";
        echo "</body></html>";
        exit;
    }
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $headers = [
        'Дата подачи заявки',
        'Фамилия и имя ребёнка',
        'Дата рождения ребёнка',
        'Возраст (лет)',
        'ФИО родителя',
        'Моб. телефон родителя',
        'E-mail',
        'Особенности здоровья',
        'Пожелания'
    ];
    foreach ($headers as $i => $title) {
        $cell = Coordinate::stringFromColumnIndex($i + 1) . '1';
        $sheet->setCellValue($cell, $title);
    }
    foreach ($rows as $rIndex => $r) {
        $created_at = format_created_dt((string)($r['created_at'] ?? '')); // DD.MM.YYYY HH:MM
        $name = (string)($r['name'] ?? '');
        $birth_raw = (string)($r['birth'] ?? '');
        $birth_fmt = format_birth($birth_raw);
        $age = compute_age_years($birth_raw);
        $age_str = $age === null ? '' : (string)$age;
        $parent = (string)($r['parent'] ?? '');
        $phone  = (string)($r['phone'] ?? '');
        $email  = (string)($r['email'] ?? '');
        $diag   = (string)($r['diagnosis'] ?? '');
        $wishes = (string)($r['wishes'] ?? '');

        $values = [$created_at,$name,$birth_fmt,$age_str,$parent,$phone,$email,$diag,$wishes];
        foreach ($values as $colIndex => $val) {
            $cell = Coordinate::stringFromColumnIndex($colIndex + 1) . ($rIndex + 2);
            $sheet->setCellValueExplicit($cell, (string)$val, DataType::TYPE_STRING);
        }
    }

    // Заголовки жирным и автоширина
    $highestColumn = $sheet->getHighestColumn();
    $sheet->getStyle('A1:' . $highestColumn . '1')->getFont()->setBold(true);
    $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
    for ($col = 1; $col <= $highestColumnIndex; $col++) {
        $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
        $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
    }

    $safeName = 'adaptive_submissions_' . date('Y-m-d_H-i') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $safeName . '"');
    header('Cache-Control: max-age=0');
    (new Xlsx($spreadsheet))->save('php://output');
    exit;
}

/* ----------------------- HTML ----------------------- */
$total_pages = max(1, (int)ceil($total / $per_page));
function build_qs(array $overrides = []): string {
    $params = [];
    $params['q'] = $overrides['q'] ?? ($_GET['q'] ?? '');
    if (isset($overrides['sort'])) $params['sort'] = $overrides['sort'];
    elseif (isset($_GET['sort'])) $params['sort'] = $_GET['sort'];
    if (isset($overrides['order'])) $params['order'] = $overrides['order'];
    elseif (isset($_GET['order'])) $params['order'] = $_GET['order'];
    if (isset($overrides['page'])) $params['page'] = $overrides['page'];
    elseif (isset($_GET['page'])) $params['page'] = $_GET['page'];
    foreach ($params as $k => $v) if ($v === '' || $v === null) unset($params[$k]);
    return http_build_query($params);
}
function header_sort_link(string $col_key, string $label, string $current_sort, string $current_order, string $q): string {
    $is_current = ($col_key === $current_sort);
    $next_order = ($is_current && strtolower($current_order) === 'asc') ? 'desc' : 'asc';
    $qs = build_qs(['q' => $q, 'sort' => $col_key, 'order' => $next_order, 'page' => 1]);
    $arrow = $is_current ? (strtolower($current_order) === 'asc' ? ' ↑' : ' ↓') : '';
    return '<a class="header-link" href="?' . h($qs) . '">' . h($label) . $arrow . '</a>';
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Заявки — Adaptive</title>
<style>
:root{
  --bg:#f7f9fc; --card:#fff; --muted:#6b7280; --accent:#0b69ff; --border:#e3e8f2; --thin-border:#eef3f8; --line:#e6eef8; --text:#0f1724;
}
html, body { font-size:15px; }
body{font-family:Inter, Arial, Helvetica, sans-serif;margin:14px;background:var(--bg);color:var(--text)}
.container{
  width: min(96vw, 1800px); /* расширяем вправо до 1800px */
  margin: 0 auto;
  background: var(--card);
  padding: 14px;
  border-radius: 12px;
  box-shadow: 0 6px 20px rgba(16,24,40,0.05);
}
h1{margin:0 0 10px 0;font-size:21px;display:flex;align-items:center;gap:12px}
.total-box{font-size:14px;color:var(--accent);font-weight:700;margin-left:4px}
.toolbar{display:flex;gap:8px;align-items:center;margin:10px 0 10px 0;flex-wrap:wrap}
input[type=text]{padding:8px 10px;border:1px solid var(--line); border-radius:8px;min-width:360px;font-size:13px;background:#fff}
button, a.button{background:var(--accent);color:#fff;padding:8px 12px;border-radius:8px;text-decoration:none;border:none;cursor:pointer;display:inline-block;font-size:13px}
.table-wrap{overflow:auto;border:1px solid var(--thin-border);border-radius:10px;background:#fff}
table{width:100%;border-collapse:collapse;table-layout:auto;min-width:1500px} /* шире базовая сетка */
col.col-created{width:160px;min-width:160px}
col.col-name{width:18%}
col.col-birth{width:10%}
col.col-age{width:6%}
col.col-parent{width:16%}
col.col-phone{width:10%}
col.col-email{width:12%}
col.col-diagnosis{width:22%;min-width:340px} /* расширяем */
col.col-wishes{width:22%;min-width:340px}    /* расширяем */
thead th{background:#f3f6fb;font-weight:700;text-align:left;padding:9px 10px;font-size:12px;color:#172b4d;border-bottom:1px solid var(--border);white-space:normal;line-height:1.1;vertical-align:bottom}
tbody td{padding:12px 10px;font-size:13px;color:var(--text);border-bottom:1px solid var(--thin-border);vertical-align:top;word-break:break-word;background-clip:padding-box}
tbody tr:nth-child(odd){background:#fff}
tbody tr:nth-child(even){background:#fbfdff}
tbody tr:hover{background:#f6f9ff}
td.col-center{text-align:center}
td.col-phone{white-space:nowrap} /* телефон не переносится */
.header-link{color:inherit;text-decoration:none}
.header-link:hover{text-decoration:underline}
.small{color:var(--muted);font-size:12px}
.note{color:#6b7280;font-size:12px;margin-top:8px}
.pagination a.button{background:#1f6feb}
@media (max-width:1200px){
  input[type=text]{min-width:220px}
}
@media (max-width:900px){
  table{min-width:1350px}
}
</style>
</head>
<body>
<div class="container">
  <h1>Заявки на адаптивную группу <span class="total-box">Всего: <?= h((string)$total) ?></span></h1>

  <div class="toolbar">
    <form method="get" style="display:inline-flex;gap:8px;align-items:center;flex-wrap:wrap;">
      <input type="text" name="q" value="<?= h($q) ?>" placeholder="Поиск: имя, телефон, email, особенности здоровья, пожелания" />
      <button type="submit">Поиск</button>
      <a class="button" href="?<?= ($q!=='' ? 'q='.urlencode($q).'&' : '') ?><?= $sort!=='' ? 'sort='.urlencode($sort).'&' : '' ?><?= $order!=='' ? 'order='.urlencode($order).'&' : '' ?>export=xlsx">Экспорт в XLSX</a>
    </form>
    <div style="margin-left:auto" class="small">Страница <?= h((string)$page) ?> из <?= h((string)$total_pages) ?></div>
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
        <col class="col-diagnosis" />
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
          <th><?= header_sort_link('diagnosis', 'Особенности здоровья', $sort, $order, $q) ?></th>
          <th><?= header_sort_link('wishes', 'Пожелания', $sort, $order, $q) ?></th>
        </tr>
      </thead>
      <tbody>
<?php if (empty($rows)): ?>
        <tr><td colspan="9" class="small">Записей не найдено.</td></tr>
<?php else: foreach ($rows as $r): ?>
        <tr>
          <td class="col-created"><?= h(format_created_dt((string)($r['created_at'] ?? ''))) ?></td>
          <td><?= h($r['name'] ?? '') ?></td>
          <td class="col-birth col-center"><?= h(format_birth((string)($r['birth'] ?? ''))) ?></td>
          <td class="col-age col-center"><?= h((string) (compute_age_years((string)($r['birth'] ?? '')) ?? '')) ?></td>
          <td><?= h($r['parent'] ?? '') ?></td>
          <td class="col-phone"><?= h($r['phone'] ?? '') ?></td>
          <td class="col-email"><?= h($r['email'] ?? '') ?></td>
          <td><?= h($r['diagnosis'] ?? '') ?></td>
          <td><?= h($r['wishes'] ?? '') ?></td>
        </tr>
<?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="pagination" style="margin-top:10px;display:flex;gap:8px;align-items:center">
    <?php if ($page > 1): ?>
      <a class="button" href="?<?= build_qs(['page' => $page-1, 'q' => $q, 'sort' => $sort, 'order' => $order]) ?>">← Назад</a>
    <?php endif; ?>
    <?php if ($page < $total_pages): ?>
      <a class="button" href="?<?= build_qs(['page' => $page+1, 'q' => $q, 'sort' => $sort, 'order' => $order]) ?>">Вперёд →</a>
    <?php endif; ?>
  </div>

  <div class="note">
    Данные загружаются из таблицы <?= h($table) ?>. Показаны записи постранично (по <?= h((string)$per_page) ?> на страницу).
  </div>
</div>
</body>
</html>