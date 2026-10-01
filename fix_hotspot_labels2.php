<?php
$file = 'resources/js/Pages/Customers/Show.vue';
$content = file_get_contents($file);

// Add HOTSPOT option
$content = preg_replace('/<option value="DHCP">DHCP \/ Dynamic<\/option>/', '<option value="DHCP">DHCP / Dynamic</option>
                                    <option value="HOTSPOT">Hotspot</option>', $content);

// Change labels for PPPoE Username
$content = preg_replace('/<label class="block text-sm font-medium text-gray-700 mb-1">PPPoE Username<\/label>\s*<input v-model="activationForm\.pppoe_user"/', '<label class="block text-sm font-medium text-gray-700 mb-1">{{ activationForm.access_mode === \'HOTSPOT\' ? \'Username Hotspot\' : \'PPPoE Username\' }}</label>
                                <input v-model="activationForm.pppoe_user"', $content);

// Change labels for PPPoE Password
$content = preg_replace('/<label class="block text-sm font-medium text-gray-700 mb-1">PPPoE Password<\/label>\s*<input v-model="activationForm\.pppoe_password"/', '<label class="block text-sm font-medium text-gray-700 mb-1">{{ activationForm.access_mode === \'HOTSPOT\' ? \'Password Hotspot\' : \'PPPoE Password\' }}</label>
                                <input v-model="activationForm.pppoe_password"', $content);

// For activeConfigForm
$content = preg_replace('/<label class="block text-sm font-medium text-gray-700 mb-1">PPPoE Username<\/label>\s*<input v-model="activeConfigForm\.pppoe_user"/', '<label class="block text-sm font-medium text-gray-700 mb-1">{{ activeConfigForm.access_mode === \'HOTSPOT\' ? \'Username Hotspot\' : \'PPPoE Username\' }}</label>
                                <input v-model="activeConfigForm.pppoe_user"', $content);

$content = preg_replace('/<label class="block text-sm font-medium text-gray-700 mb-1">PPPoE Password<\/label>\s*<input v-model="activeConfigForm\.pppoe_password"/', '<label class="block text-sm font-medium text-gray-700 mb-1">{{ activeConfigForm.access_mode === \'HOTSPOT\' ? \'Password Hotspot\' : \'PPPoE Password\' }}</label>
                                <input v-model="activeConfigForm.pppoe_password"', $content);

file_put_contents($file, $content);
echo "Replaced with Regex\n";
