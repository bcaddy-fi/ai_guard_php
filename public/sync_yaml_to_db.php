<?php
require __DIR__ . '/../app/controllers/db.php';

function parseYaml($file) {
    $lines = file($file);
    $data = ['version' => '1.0.0', 'name' => '', 'description' => '', 'title' => '', 'tone' => '', 'action' => ''];
    foreach ($lines as $line) {
        if (preg_match('/^#\s*version:\s*(.+)/i', $line, $m)) $data['version'] = trim($m[1]);
        elseif (preg_match('/^\s*name:\s*(.+)/', $line, $m)) $data['name'] = trim($m[1]);
        elseif (preg_match('/^\s*description:\s*\|?\s*(.*)/', $line, $m)) {
            $desc = trim($m[1]);
            $data['description'] = $desc;
        }
        elseif (preg_match('/^\s*title:\s*(.+)/', $line, $m)) $data['title'] = trim($m[1]);
        elseif (preg_match('/^\s*tone:\s*(.+)/', $line, $m)) $data['tone'] = trim($m[1]);
        elseif (preg_match('/^\s*action:\s*(.+)/', $line, $m)) $data['action'] = trim($m[1]);
    }
    return $data;
}

function syncTable($pdo, $dir, $type) {
    $base = realpath(__DIR__ . '/../' . $dir);
    $files = glob("$base/*.yaml");

    foreach ($files as $file) {
        $parsed = parseYaml($file);
        $name = $parsed['name'];
        if (!$name) continue;

        $relPath = str_replace(realpath(__DIR__ . '/..'), '', $file);
        if ($type === 'persona') {
            $stmt = $pdo->prepare("SELECT id FROM personas WHERE name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE personas SET description = ?, title = ?, tone = ?, last_reviewed = NOW(), file_path = ? WHERE name = ?");
                $stmt->execute([$parsed['description'], $parsed['title'], $parsed['tone'], $relPath, $name]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO personas (name, title, description, tone, last_reviewed, file_path) VALUES (?, ?, ?, ?, NOW(), ?)");
                $stmt->execute([$name, $parsed['title'], $parsed['description'], $parsed['tone'], $relPath]);
            }
        } elseif ($type === 'guardrail') {
            $stmt = $pdo->prepare("SELECT id FROM guardrails WHERE name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE guardrails SET description = ?, action = ?, file_path = ? WHERE name = ?");
                $stmt->execute([$parsed['description'], $parsed['action'], $relPath, $name]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO guardrails (name, description, action, file_path) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $parsed['description'], $parsed['action'], $relPath]);
            }
        }
    }
}

// Sync both personas and guardrails
syncTable($pdo, 'data/persona', 'persona');
syncTable($pdo, 'data/guardrails', 'guardrail');

echo "Sync completed.\n";
