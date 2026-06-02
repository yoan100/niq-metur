<?php
require __DIR__ . '/db.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

// Само за влезли потребители
if (empty($_SESSION['niauth'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Не сте влезли.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$in     = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $in['action'] ?? ($_GET['action'] ?? 'state');
$data   = load_data();
$levels = level_thresholds();

function clean(string $s, int $max): string {
    $s = trim($s);
    if (function_exists('mb_substr')) $s = mb_substr($s, 0, $max);
    else $s = substr($s, 0, $max);
    return $s;
}

switch ($action) {

    // -------- Текущо състояние --------
    case 'state':
        break;

    // -------- Добра / лоша постъпка --------
    case 'deed':
        $type = ($in['type'] ?? '') === 'bad' ? 'bad' : 'good';
        $note = clean((string)($in['note'] ?? ''), 120);
        $delta = $type === 'good' ? POINTS_GOOD : -POINTS_BAD;
        $data['points'] += $delta;
        array_unshift($data['history'], [
            'id'    => new_id(),
            'type'  => $type,
            'note'  => $note,
            'delta' => $delta,
            'ts'    => time(),
        ]);
        $data['history'] = array_slice($data['history'], 0, 100);
        save_data($data);
        break;

    // -------- Добавяне на награда --------
    case 'add_reward':
        if (count($data['rewards']) >= MAX_REWARDS) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Може да имаш най-много ' . MAX_REWARDS . ' награди.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $name  = clean((string)($in['name'] ?? ''), 40);
        $desc  = clean((string)($in['description'] ?? ''), 200);
        $level = (string)($in['level'] ?? '');
        $photo = (string)($in['photo'] ?? '');

        if ($name === '' || !isset($levels[$level])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Липсва име или ниво на послушание.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        // приемаме само вградена снимка (data:image) и пазим разумен размер
        if ($photo !== '' && !preg_match('#^data:image/#', $photo)) $photo = '';
        if (strlen($photo) > 2500000) $photo = '';

        $data['rewards'][] = [
            'id'          => new_id(),
            'name'        => $name,
            'description' => $desc,
            'photo'       => $photo,
            'level'       => $level,
            'threshold'   => $levels[$level],
            'claimed'     => false,
            'created'     => time(),
        ];
        // подреждаме по праг (по-лесните по-напред по пътеката)
        usort($data['rewards'], fn($a, $b) => $a['threshold'] <=> $b['threshold']);
        save_data($data);
        break;

    // -------- Вземане на награда --------
    case 'claim_reward':
        $id = (string)($in['id'] ?? '');
        foreach ($data['rewards'] as &$r) {
            if ($r['id'] === $id) {
                if ($data['points'] < $r['threshold']) {
                    http_response_code(400);
                    echo json_encode(['ok' => false, 'error' => 'Ния още не е стигнала тази награда.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                $r['claimed'] = true;
                $r['claimedAt'] = time();
            }
        }
        unset($r);
        save_data($data);
        break;

    // -------- Изтриване на награда --------
    case 'delete_reward':
        $id = (string)($in['id'] ?? '');
        $data['rewards'] = array_values(array_filter($data['rewards'], fn($r) => $r['id'] !== $id));
        save_data($data);
        break;

    // -------- Нулиране на точките --------
    case 'reset_points':
        $data['points'] = 0;
        array_unshift($data['history'], [
            'id' => new_id(), 'type' => 'reset', 'note' => 'Нов старт',
            'delta' => 0, 'ts' => time(),
        ]);
        $data['history'] = array_slice($data['history'], 0, 100);
        save_data($data);
        break;

    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Непознато действие.'], JSON_UNESCAPED_UNICODE);
        exit;
}

// Връщаме пълното състояние след всяко действие
echo json_encode([
    'ok'         => true,
    'points'     => $data['points'],
    'good'       => POINTS_GOOD,
    'bad'        => POINTS_BAD,
    'maxRewards' => MAX_REWARDS,
    'levels'     => $levels,
    'rewards'    => array_values($data['rewards']),
    'history'    => array_slice($data['history'], 0, 20),
], JSON_UNESCAPED_UNICODE);
