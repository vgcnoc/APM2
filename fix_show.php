<?php
$file = 'resources/js/Pages/Customers/Show.vue';
$content = file_get_contents($file);

$target = "            photoFields.forEach(field => localStorage.removeItem(`apm_\${field}_\${props.customer.id}`));\n        }";
$replacement = <<<EOF
            photoFields.forEach(field => localStorage.removeItem(`apm_\${field}_\${props.customer.id}`));
            
            // Lanjut ke aksi audit
            showAuditModal.value = true;
        }
EOF;

$content = str_replace($target, $replacement, $content);
file_put_contents($file, $content);
echo "Replaced successfully\n";
