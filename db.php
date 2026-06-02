<?php
// ============================================================
//  НияМетър — общ слой за данни и настройки
// ============================================================

// ПИН код за вход (родители и роднини)
const PIN_CODE = '0402';

// Файл за съхранение на данните
const DATA_FILE = __DIR__ . '/data/data.json';

// Максимален брой награди едновременно
const MAX_REWARDS = 3;

// Точки за добра / лоша постъпка
const POINTS_GOOD = 10;
const POINTS_BAD  = 10;

// Колко точки иска всяко ниво на послушание
//   малко  -> ~4–5 послушни дни
//   средно -> ~7–8 послушни дни
//   много  -> ~2–3 седмици послушание
function level_thresholds(): array {
    return [
        'малко'  => 100,
        'средно' => 200,
        'много'  => 400,
    ];
}

// Зарежда данните (създава файла при първо стартиране)
function load_data(): array {
    $default = ['points' => 0, 'rewards' => [], 'history' => []];

    if (!is_dir(dirname(DATA_FILE))) {
        @mkdir(dirname(DATA_FILE), 0775, true);
    }
    if (!file_exists(DATA_FILE)) {
        save_data($default);
        return $default;
    }

    $raw  = file_get_contents(DATA_FILE);
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = $default;
    }
    // подсигуряваме структурата
    $data += $default;
    $data['points']  = (int)($data['points'] ?? 0);
    $data['rewards'] = is_array($data['rewards'] ?? null) ? $data['rewards'] : [];
    $data['history'] = is_array($data['history'] ?? null) ? $data['history'] : [];
    return $data;
}

// Записва данните със заключване на файла
function save_data(array $data): void {
    if (!is_dir(dirname(DATA_FILE))) {
        @mkdir(dirname(DATA_FILE), 0775, true);
    }
    $fp = fopen(DATA_FILE, 'c+');
    if (!$fp) return;
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    ));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

// Прост генератор на ID
function new_id(): string {
    return bin2hex(random_bytes(6));
}
