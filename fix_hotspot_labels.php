<?php
$file = 'resources/js/Pages/Customers/Show.vue';
$content = file_get_contents($file);

// Replace labels for activationForm
$target1 = <<<EOF
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">PPPoE Username</label>
                                    <input v-model="activationForm.pppoe_user" type="text" class="w-full border rounded-lg px-4 py-2.5 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white border-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500'" placeholder="user@isp" :readonly="!isEditingOnt" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">PPPoE Password</label>
EOF;

$replacement1 = <<<EOF
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ activationForm.access_mode === 'HOTSPOT' ? 'Username Hotspot' : 'PPPoE Username' }}</label>
                                    <input v-model="activationForm.pppoe_user" type="text" class="w-full border rounded-lg px-4 py-2.5 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white border-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500'" placeholder="user@isp" :readonly="!isEditingOnt" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ activationForm.access_mode === 'HOTSPOT' ? 'Password Hotspot' : 'PPPoE Password' }}</label>
EOF;

$content = str_replace($target1, $replacement1, $content);

// Replace access_mode options for activationForm
$target2 = <<<EOF
                                    <select v-model="activationForm.access_mode" class="w-full border rounded-lg px-4 py-2.5 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white border-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500'" :disabled="!isEditingOnt">
                                        <option value="PPPOE">PPPoE</option>
                                        <option value="STATIC">Static IP</option>
                                        <option value="DHCP">DHCP / Dynamic</option>
                                    </select>
EOF;

$replacement2 = <<<EOF
                                    <select v-model="activationForm.access_mode" class="w-full border rounded-lg px-4 py-2.5 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white border-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500'" :disabled="!isEditingOnt">
                                        <option value="PPPOE">PPPoE</option>
                                        <option value="STATIC">Static IP</option>
                                        <option value="DHCP">DHCP / Dynamic</option>
                                        <option value="HOTSPOT">Hotspot</option>
                                    </select>
EOF;

$content = str_replace($target2, $replacement2, $content);

// Replace labels for activeConfigForm
$target3 = <<<EOF
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">PPPoE Username</label>
                                    <input v-model="activeConfigForm.pppoe_user" type="text" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all" placeholder="user@isp" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">PPPoE Password</label>
EOF;

$replacement3 = <<<EOF
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ activeConfigForm.access_mode === 'HOTSPOT' ? 'Username Hotspot' : 'PPPoE Username' }}</label>
                                    <input v-model="activeConfigForm.pppoe_user" type="text" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all" placeholder="user@isp" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ activeConfigForm.access_mode === 'HOTSPOT' ? 'Password Hotspot' : 'PPPoE Password' }}</label>
EOF;

$content = str_replace($target3, $replacement3, $content);

// Replace access_mode options for activeConfigForm
$target4 = <<<EOF
                                    <select v-model="activeConfigForm.access_mode" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                                        <option value="PPPOE">PPPoE</option>
                                        <option value="STATIC">Static IP</option>
                                        <option value="DHCP">DHCP / Dynamic</option>
                                    </select>
EOF;

$replacement4 = <<<EOF
                                    <select v-model="activeConfigForm.access_mode" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                                        <option value="PPPOE">PPPoE</option>
                                        <option value="STATIC">Static IP</option>
                                        <option value="DHCP">DHCP / Dynamic</option>
                                        <option value="HOTSPOT">Hotspot</option>
                                    </select>
EOF;

$content = str_replace($target4, $replacement4, $content);

file_put_contents($file, $content);
echo "Replaced properly\n";
