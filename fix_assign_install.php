<?php
$file = 'app/Http/Controllers/CustomerController.php';
$content = file_get_contents($file);

$target = "                          \$ont->update(['customer_id' => \$customer->id]);";
$replacement = <<<PHP
                          \$ont->update([
                              'customer_id' => \$customer->id,
                              'rx_power' => null,
                              'start_time' => null,
                              'end_time' => null,
                              'photo_odp' => null,
                              'photo_installation' => null,
                              'photo_ont' => null,
                              'photo_customer' => null,
                              'photo_redaman' => null
                          ]);
PHP;

$content = str_replace($target, $replacement, $content);
file_put_contents($file, $content);
echo "Replaced successfully\n";
