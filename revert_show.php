<?php
$file = 'resources/js/Pages/Customers/Show.vue';
$content = file_get_contents($file);

$target = <<<EOF
            // Lanjut ke aksi audit otomatis setelah delay kecil agar toast terlihat
            setTimeout(() => {
                showAuditModal.value = true;
            }, 500);
EOF;

// Since it might have \r\n, we use regex replacement
$content = preg_replace('/\s*\/\/\s*Lanjut ke aksi audit otomatis setelah delay kecil agar toast terlihat\s*setTimeout\(\(\) => \{\s*showAuditModal\.value = true;\s*\}, 500\);/ms', '', $content);

file_put_contents($file, $content);
echo "Reverted auto-audit\n";
