<?php
/**
 * Tiny DB helper for tests/smoke_rbac.sh — keeps SQL out of shell
 * quoting hell. Usage:
 *   php tests/smoke_db.php setup <uniq>      — create society+student, promote society
 *   php tests/smoke_db.php getid <email>      — print user id
 *   php tests/smoke_db.php getrole <id>       — print role
 *   php tests/smoke_db.php setrole <id> <role>
 *   php tests/smoke_db.php getpostid <content>
 *   php tests/smoke_db.php clearlock <email>
 *   php tests/smoke_db.php cleanup <uniq>
 */
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/User.php';

$db = Database::getInstance()->getConnection();
$action = $argv[1] ?? '';
switch ($action) {
    case 'setup': {
        $u = $argv[2];
        $user = new User($db);
        $user->register("Smoke Society", "smoke_soc_{$u}@nu.edu.pk", "StrongPass123!");
        $user->register("Smoke Student", "smoke_stu_{$u}@nu.edu.pk", "StrongPass123!");
        $id = (int)$db->query("SELECT id FROM users WHERE email = 'smoke_soc_{$u}@nu.edu.pk'")->fetchColumn();
        $user->setRole($id, 'society_admin');
        echo "setup ok society_id=$id\n";
        break;
    }
    case 'getid':
        echo (int)$db->query("SELECT id FROM users WHERE email = '" . addslashes($argv[2]) . "'")->fetchColumn() . "\n";
        break;
    case 'getrole':
        echo $db->query("SELECT role FROM users WHERE id = " . (int)$argv[2])->fetchColumn() . "\n";
        break;
    case 'setrole': {
        $user = new User($db);
        var_export($user->setRole((int)$argv[2], $argv[3]));
        echo "\n";
        break;
    }
    case 'getpostid':
        echo (int)$db->query("SELECT id FROM posts WHERE content = '" . addslashes($argv[2]) . "' ORDER BY id DESC LIMIT 1")->fetchColumn() . "\n";
        break;
    case 'clearlock':
        $db->exec("DELETE FROM login_attempts WHERE email = '" . addslashes($argv[2]) . "'");
        echo "lock cleared\n";
        break;
    case 'cleanup': {
        $u = $argv[2];
        $db->exec("DELETE FROM posts WHERE content LIKE '%" . addslashes($u) . "'");
        $db->exec("DELETE FROM users WHERE email LIKE 'smoke\_%" . addslashes($u) . "@nu.edu.pk' ESCAPE '\\'");
        $db->exec("DELETE FROM login_attempts WHERE email LIKE 'smoke\_%" . addslashes($u) . "@nu.edu.pk' ESCAPE '\\'");
        echo "cleanup done\n";
        break;
    }
    default:
        fwrite(STDERR, "Unknown action: $action\n");
        exit(1);
}
