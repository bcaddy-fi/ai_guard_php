<?php
function generate_rule_yaml(array $ruleData): string {
    return yaml_emit([
        'version' => $ruleData['version'] ?? '1.0.0',
        'name' => $ruleData['name'],
        'description' => $ruleData['description'],
        'tone' => $ruleData['tone'],
        'categories' => array_map('trim', explode(',', $ruleData['categories'] ?? '')),
        'rules' => json_decode($ruleData['rules'] ?? '[]', true),
        'examples' => [
            'good' => json_decode($ruleData['examples_good'] ?? '[]', true),
            'bad' => json_decode($ruleData['examples_bad'] ?? '[]', true)
        ]
    ]);
}

function save_rule_yaml_file(string $name, string $yaml): bool {
    $filename = __DIR__ . "/../../data/agent_rules/" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name) . ".yaml";
    return file_put_contents($filename, $yaml) !== false;
}
