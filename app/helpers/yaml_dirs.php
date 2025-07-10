<?php
function get_yaml_directory(string $type): string {
    $map = [
        'persona'     => __DIR__ . '/../../data/persona/',
        'agent'       => __DIR__ . '/../../data/agent_rules/',
        'model'       => __DIR__ . '/../../data/models/',
        'guardrail'   => __DIR__ . '/../../data/guardrails/',
        'guardrails'  => __DIR__ . '/../../data/guardrails/',  // add this line
        'models'      => __DIR__ . '/../../data/models/',      // optional alias
        'personas'    => __DIR__ . '/../../data/persona/',     // optional alias
        'agents'      => __DIR__ . '/../../data/agent_rules/'  // optional alias
    ];

    if (!isset($map[$type])) {
        throw new Exception("Invalid YAML type: $type");
    }

    return $map[$type];
}
