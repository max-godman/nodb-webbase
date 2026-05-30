<?php
$pageLevel = 20;
$pageTitle = 'Country Manage';
require_once '../inc/auth.php';

$countryFile = __DIR__ . '/../data/country.log';
$siteCountryFile = __DIR__ . '/../data/site_country.log';

$countries = [];
if (file_exists($countryFile)) {
    $data = file_get_contents($countryFile);
    $countries = json_decode($data, true);
    if (!is_array($countries)) $countries = [];
}

$message = '';
$error = '';
$searchAds = $_GET['search_ads'] ?? '';
$searchQ = trim($_GET['search_q'] ?? '');

function _findDuplicate($countries, $field, $value, $excludeId = null)
{
    foreach ($countries as $c) {
        if ($excludeId !== null && $c['id'] === $excludeId) continue;
        if (strtoupper($c[$field]) === strtoupper(trim($value))) return $c;
    }
    return null;
}

function _getNextId($countries)
{
    $max = 0;
    foreach ($countries as $c) {
        if ($c['id'] > $max) $max = $c['id'];
    }
    return $max + 1;
}

function _generateCountrySelectHtml($countries, $lang)
{
    $active = array_filter($countries, function ($c) { return $c['ads'] == 1; });
    $groups = [];
    foreach ($active as $c) {
        $letter = $c['letter'];
        if (!isset($groups[$letter])) $groups[$letter] = [];
        $groups[$letter][] = $c;
    }
    ksort($groups);
    $nameField = $lang === 'cn' ? 'cn' : 'country';
    $html = '<option value="">Select Country</option>' . "\n";
    foreach ($groups as $letter => $list) {
        $html .= '<option value="">-' . $letter . '---------------------------</option>' . "\n";
        foreach ($list as $c) {
            $label = $c['code'] . '-' . $c[$nameField];
            $html .= '<option value="' . htmlspecialchars($c['code'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>' . "\n";
        }
    }
    return $html;
}

function _writeSiteCountryFile($siteCountryFile, $countries)
{
    $en = _generateCountrySelectHtml($countries, 'en');
    $cn = _generateCountrySelectHtml($countries, 'cn');
    $content = "<?php return ['en' => " . var_export($en, true) . ", 'cn' => " . var_export($cn, true) . "];\n";
    file_put_contents($siteCountryFile, $content, LOCK_EX);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $country = trim($_POST['country'] ?? '');
        $cn = trim($_POST['cn'] ?? '');
        $ads = (int)($_POST['ads'] ?? 1);

        if (empty($code) || empty($country) || empty($cn)) {
            $error = 'Code, English name, and Chinese name are required';
        } elseif (!preg_match('/^[A-Z]{2}$/', $code)) {
            $error = 'Code must be exactly 2 letters';
        } elseif (_findDuplicate($countries, 'code', $code)) {
            $error = 'Duplicate code: ' . $code;
        } elseif (_findDuplicate($countries, 'country', $country)) {
            $error = 'Duplicate English name: ' . $country;
        } elseif (_findDuplicate($countries, 'cn', $cn)) {
            $error = 'Duplicate Chinese name: ' . $cn;
        } else {
            $countries[] = [
                'id' => _getNextId($countries),
                'ads' => $ads,
                'code' => $code,
                'country' => $country,
                'cn' => $cn,
                'letter' => $code[0],
            ];
            file_put_contents($countryFile, json_encode($countries, JSON_UNESCAPED_UNICODE), LOCK_EX);
            writeSysLog(1, $authUserid . ' added country: ' . $code . ' ' . $country);
            $message = 'Country added: ' . $code;
        }
    }

    if ($action === 'bulk_update') {
        $codes = $_POST['code'] ?? [];
        $countriesList = $_POST['country'] ?? [];
        $cns = $_POST['cn'] ?? [];
        $adsList = $_POST['ads'] ?? [];
        $ids = $_POST['id'] ?? [];
        $deletes = $_POST['delete'] ?? [];
        $newCountries = [];

        foreach ($codes as $i => $c) {
            $code = strtoupper(trim($codes[$i] ?? ''));
            $country = trim($countriesList[$i] ?? '');
            $cn = trim($cns[$i] ?? '');
            $ads = (int)($adsList[$i] ?? 1);
            if (empty($code) || empty($country) || empty($cn)) continue;
            if (isset($deletes[$i]) && $deletes[$i] === '1') continue;
            $id = (int)($ids[$i] ?? _getNextId($newCountries));
            $dup = _findDuplicate($newCountries, 'code', $code);
            if ($dup) continue;
            $newCountries[] = [
                'id' => $id,
                'ads' => $ads,
                'code' => $code,
                'country' => $country,
                'cn' => $cn,
                'letter' => $code[0],
            ];
        }

        $countries = $newCountries;
        file_put_contents($countryFile, json_encode($countries, JSON_UNESCAPED_UNICODE), LOCK_EX);
        writeSysLog(1, $authUserid . ' bulk updated countries');
        $message = 'Countries saved (' . count($countries) . ' entries)';
    }

    if ($action === 'generate') {
        _writeSiteCountryFile($siteCountryFile, $countries);
        writeSysLog(1, $authUserid . ' generated site_country.log');
        $message = 'Site country file generated (' . count($countries) . ' entries)';
    }
}

include '../tpl/adm_head.log';
?>

<?php if ($message): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="card" style="background:#e8f5e9; border:1px solid #c8e6c9;">
    <div class="card-title" style="color:#2e7d32;">Usage Guide</div>
    <p style="font-size:0.85rem; line-height:1.7; color:#1b5e20;">
        <b>Placeholder in templates</b> (after clicking "Regen Cache"):
    </p>
    <pre style="background:#f1f8e9; padding:12px; border-radius:4px; font-size:0.8rem; overflow:auto; line-height:1.6; margin-top:8px;">&lt;select name="country" required&gt;
  {site:country_en}
&lt;/select&gt;</pre>
    <p style="font-size:0.85rem; line-height:1.7; color:#1b5e20; margin-top:8px;">
        <b>Function call</b> (already built into <code>sys_inc.php</code>, available everywhere):
    </p>
    <pre style="background:#f1f8e9; padding:12px; border-radius:4px; font-size:0.8rem; overflow:auto; line-height:1.6; margin-top:8px;">&lt;?php echo getCountryName('US', 'en');  // United States of America
echo getCountryName('US', 'cn');  // 美国 ?&gt;</pre>
    <p style="font-size:0.85rem; color:#2e7d32; margin-top:8px;">
        Placeholder <code>{site:country_en}</code> and <code>{site:country_cn}</code> are available after clicking "Regen Cache".<br>
        Data file: <code>data/country.log</code> (JSON, <?php echo count($countries); ?> entries). Cached output: <code>data/site_country.log</code>.
    </p>
</div>

<div class="card">
    <div class="card-title">Add Country</div>
    <form method="post">
        <input type="hidden" name="action" value="add">
        <div class="d-flex gap-2 align-center" style="flex-wrap:wrap;">
            <div class="form-group" style="flex:0 0 80px;margin-bottom:0;">
                <label for="code">Code</label>
                <input type="text" id="code" name="code" placeholder="US" maxlength="2" style="width:100%;text-transform:uppercase;" required>
            </div>
            <div class="form-group" style="flex:1;min-width:150px;margin-bottom:0;">
                <label for="country">English</label>
                <input type="text" id="country" name="country" placeholder="United States" style="width:100%;" required>
            </div>
            <div class="form-group" style="flex:1;min-width:150px;margin-bottom:0;">
                <label for="cn">Chinese</label>
                <input type="text" id="cn" name="cn" placeholder="美国" style="width:100%;" required>
            </div>
            <div class="form-group" style="flex:0 0 100px;margin-bottom:0;">
                <label for="ads">Status</label>
                <select id="ads" name="ads" style="width:100%;">
                    <option value="1">Active</option>
                    <option value="0">Pending</option>
                </select>
            </div>
            <div style="padding-top:20px;">
                <button type="submit" class="btn btn-primary" onclick="return confirm('Confirm add?')">Add</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-title" style="display:flex;justify-content:space-between;align-items:center;">
        <span>Manage Countries (<?php echo count($countries); ?>)</span>
        <form method="post" style="display:inline;">
            <input type="hidden" name="action" value="generate">
            <button type="submit" class="btn btn-primary" style="background:#ff9800;border-color:#f57c00;" onclick="return confirm('Regenerate site_country.log from current data?')">Regen Cache</button>
        </form>
    </div>

    <form method="get" style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
            <select name="search_ads" style="width:100px;" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="1"<?php echo ($searchAds ?? '') === '1' ? ' selected' : ''; ?>>Active</option>
                <option value="0"<?php echo ($searchAds ?? '') === '0' ? ' selected' : ''; ?>>Pending</option>
            </select>
            <input type="text" name="search_q" placeholder="Keyword (code/english/chinese)" value="<?php echo htmlspecialchars($searchQ ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="flex:1;min-width:150px;">
            <button type="submit" class="btn btn-primary" style="background:#607d8b;border-color:#455a64;">Search</button>
        </form>

    <form method="post">
        <input type="hidden" name="action" value="bulk_update">

        <?php
        $filtered = $countries;
        if ($searchAds !== '') {
            $filtered = array_filter($filtered, function ($c) use ($searchAds) { return $c['ads'] == $searchAds; });
        }
        if ($searchQ !== '') {
            $sq = strtoupper($searchQ);
            $filtered = array_filter($filtered, function ($c) use ($sq) {
                return strpos(strtoupper($c['code']), $sq) !== false
                    || strpos(strtoupper($c['country']), $sq) !== false
                    || strpos(strtoupper($c['cn']), $sq) !== false;
            });
        }
        $filtered = array_values($filtered);
        ?>

        <table>
            <thead>
                <tr>
                    <th data-label="Code" style="width:60px;">Code</th>
                    <th data-label="English">English</th>
                    <th data-label="Chinese">Chinese</th>
                    <th data-label="Status" style="width:80px;">Status</th>
                    <th data-label="Del" style="width:50px;">Del</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($filtered)): ?>
                <tr><td colspan="5" class="text-center text-muted">No countries found.</td></tr>
                <?php endif; ?>
                <?php foreach ($filtered as $i => $c): ?>
                <tr>
                    <input type="hidden" name="id[<?php echo $i; ?>]" value="<?php echo (int)$c['id']; ?>">
                    <td data-label="Code"><input type="text" name="code[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($c['code'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="2" style="width:100%;text-transform:uppercase;"></td>
                    <td data-label="English"><input type="text" name="country[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($c['country'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;"></td>
                    <td data-label="Chinese"><input type="text" name="cn[<?php echo $i; ?>]" value="<?php echo htmlspecialchars($c['cn'], ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;"></td>
                    <td data-label="Status">
                        <select name="ads[<?php echo $i; ?>]" style="width:100%;">
                            <option value="1"<?php echo $c['ads'] == 1 ? ' selected' : ''; ?>>Active</option>
                            <option value="0"<?php echo $c['ads'] == 0 ? ' selected' : ''; ?>>Pending</option>
                        </select>
                    </td>
                    <td data-label="Del" class="text-center"><label><input type="checkbox" name="delete[<?php echo $i; ?>]" value="1"<?php echo $c['ads'] != 0 ? ' disabled' : ''; ?>> Del</label></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="submit" class="btn btn-primary mt-2" onclick="return confirm('Confirm save all changes?')">Save All</button>
    </form>
</div>

<?php include '../tpl/adm_foot.log'; ?>
