<?php return array (
  'enabled' => true,
  'ip_mode' => 'allow_all_except',
  'block_ips' => 
  array (
    0 => '192.168.1.0',
    1 => '10.0.0.1',
  ),
  'country_mode' => 'block_all_except',
  'allow_countries' => 
  array (
    0 => 'US',
    1 => 'CA',
    2 => 'XX',
  ),
  'block_sql_injection' => true,
  'block_xss' => true,
  'rate_limit_enabled' => true,
  'json_response_enabled' => true,
  'captcha_enabled' => false,
  'hcaptcha_site_key' => 'YOUR OWN KEY',
  'hcaptcha_secret_key' => 'YOUR OWN SECRET',
);