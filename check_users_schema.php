<?php
$db = new PDO('sqlite:includes/users.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$rows = $db->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo $row['cid'] . ':' . $row['name'] . ' ' . $row['type'] . ' ' . ($row['notnull'] ? 'NOTNULL' : 'NULL') . ' default=' . var_export($row['dflt_value'], true) . "\n";
}
