function find_matching_rules($prompt, $response, $yamlRules) {
    $matched = [];
    foreach ($yamlRules as $rule) {
        if (stripos($prompt, $rule) !== false || stripos($response, $rule) !== false) {
            $matched[] = $rule;
        }
    }
    return $matched;
}
