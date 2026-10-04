<template>
    <AppLayout title="Mikrotik (NAS)">
        <div class="p-6 max-w-6xl mx-auto space-y-6">
            
            <!-- Warning Header -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden text-gray-900">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-2xl font-bold">Mikrotik (Nas)</h2>
                    <p class="text-red-600 font-bold mt-1 uppercase text-lg">
                        JANGAN MERUBAH SCRIPT YANG SUDAH KAMI SIAPKAN DISINI, PROSES CUKUP SATU KALI PASTE SAJA
                    </p>
                    <p class="text-sm text-gray-500 mt-2">Generate Script</p>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Catatan -->
                    <div class="text-sm space-y-1">
                        <p class="text-gray-700 mb-2 uppercase font-semibold">Catatan :</p>
                        <p class="text-red-500">- Pastikan APM2 ada di urutan teratas pada menu Mikrotik-Radius</p>
                        <p class="text-red-500">- Jika kamu menggunakan <span class="font-bold">Loadbalancing</span>, silahkan routing IP VPS kami ke satu sumber internet saja</p>
                        <p class="text-red-500">- Langsung copy dan Paste Script kami ke terminal mikrotik</p>
                        <p class="text-red-500 font-bold">- JANGAN PASTE SCRIPT YANG SAMA KE LEBIH DARI 1 MIKROTIK</p>
                    </div>

                    <!-- Dropdowns -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">RouterOS</label>
                            <select v-model="selectedOs" @change="generateScript" class="w-full bg-gray-50 border border-gray-200 text-gray-900 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 px-4 py-2.5">
                                <option value="v7">RouterOS v7 (Rekomendasi)</option>
                                <option value="v6">RouterOS v6</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nama Router</label>
                            <select v-model="selectedNasId" @change="generateScript" class="w-full bg-gray-50 border border-gray-200 text-gray-900 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 px-4 py-2.5">
                                <option value="">-- Pilih Router (Atau Buat Baru) --</option>
                                <option v-for="n in nas.data" :key="n.id" :value="n.id">
                                    {{ n.shortname || n.nasname }} ({{ n.nasname }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Script Box -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 flex justify-between">
                            <span>Generated Script</span>
                            <button @click="copyScript" v-if="generatedScript" class="text-indigo-600 hover:text-indigo-500 text-xs font-semibold flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                Copy Script
                            </button>
                        </label>
                        <textarea 
                            v-model="generatedScript" 
                            readonly 
                            rows="8" 
                            class="w-full bg-gray-900 font-mono text-green-400 text-sm border border-gray-800 rounded-lg p-4 focus:ring-2 focus:ring-indigo-500"
                            placeholder="Silakan pilih router untuk memunculkan script..."></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-3 pt-2">
                        <button @click="openCreateModal" class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                            Generate Script Mikrotik Baru
                        </button>
                        <button v-if="selectedNasId" @click="generateScript" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors shadow-sm">
                            Generate Ulang Script Mikrotik (Force)
                        </button>
                    </div>
                </div>
            </div>

            <!-- Management Table (Data Router) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mt-8">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-gray-900 text-lg">Daftar Router (NAS) Terdaftar</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100">
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">IP / Hostname</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Nama</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Tipe</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Secret</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="n in nas.data" :key="n.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ n.nasname }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ n.shortname || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ n.type || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 font-mono text-xs">
                                    <span class="bg-gray-100 px-2 py-1 rounded select-all">{{ n.secret }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right flex gap-3 justify-end">
                                    <button @click="openEditModal(n)" class="text-indigo-600 hover:text-indigo-900 font-medium">Edit</button>
                                    <button @click="deleteNas(n.id)" class="text-red-600 hover:text-red-900 font-medium">Hapus</button>
                                </td>
                            </tr>
                            <tr v-if="nas.data.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500 text-sm">
                                    Belum ada data NAS.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Tambah/Edit -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden my-auto">
                <div class="bg-white px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit NAS' : 'Tambah NAS Baru' }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submit">
                    <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto bg-gray-50/30">
                        <!-- Helper Text -->
                        <div class="bg-blue-50 text-blue-800 p-3 rounded-lg text-xs mb-4">
                            Biarkan kolom <b>IP Address</b> terisi <b>0.0.0.0/0</b> jika Anda tidak memiliki IP Publik Statis di Mikrotik Anda.
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">IP Address / Hostname (nasname)</label>
                            <input v-model="form.nasname" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="192.168.1.1 atau 0.0.0.0/0" required />
                            <p v-if="form.errors.nasname" class="text-red-500 text-xs mt-1">{{ form.errors.nasname }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Shortname (Nama Singkat)</label>
                            <input v-model="form.shortname" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Router-Pusat" />
                            <p v-if="form.errors.shortname" class="text-red-500 text-xs mt-1">{{ form.errors.shortname }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Type</label>
                            <select v-model="form.type" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="mikrotik">Mikrotik</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">RADIUS Secret</label>
                            <div class="flex gap-2">
                                <input v-model="form.secret" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="secretpassword123" required />
                                <button type="button" @click="generateRandomSecret" class="px-3 bg-gray-200 text-gray-700 rounded-lg text-xs font-bold hover:bg-gray-300">Acak</button>
                            </div>
                            <p v-if="form.errors.secret" class="text-red-500 text-xs mt-1">{{ form.errors.secret }}</p>
                        </div>
                    </div>
                    
                    <div class="bg-white p-6 border-t border-gray-100 flex justify-end">
                        <button type="button" @click="closeModal" class="px-5 py-2.5 mr-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            Simpan & Generate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    nas: Object,
    filters: Object,
    serverIp: String,
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editId = ref(null);

const selectedOs = ref('v7');
const selectedNasId = ref('');
const generatedScript = ref('');

const form = useForm({
    nasname: '0.0.0.0/0',
    shortname: '',
    type: 'mikrotik',
    ports: null,
    secret: '',
    server: null,
    community: null,
    description: ''
});

function generateRandomSecret() {
    form.secret = Math.random().toString(36).substring(2, 12);
}

function generateScript() {
    if (!selectedNasId.value) {
        generatedScript.value = '';
        return;
    }

    const router = props.nas.data.find(n => n.id === selectedNasId.value);
    if (!router) return;

    const ip = props.serverIp || '157.66.140.17'; // default to VPS IP
    const secret = router.secret;
    const name = router.shortname || 'RADIUS_VPS';
    
    // Generate unique API user for this Mikrotik
    const apiUser = 'APM2_' + Math.random().toString(36).substring(2, 8).toUpperCase();
    const apiPass = Math.random().toString(36).substring(2, 14);

    let script = `# Script Konfigurasi RADIUS APM2 untuk Mikrotik ${selectedOs.value.toUpperCase()}\n`;
    script += `# Mohon paste keseluruhan script di bawah ini ke New Terminal Mikrotik Anda:\n`;
    script += `#############################################################\n\n`;

    script += `/system identity set name="${name}";\n`;
    script += `/ip dns set allow-remote-requests=yes;\n\n`;
    
    script += `# 1. BUAT USER API UNTUK KONEKSI BILLING APM2\n`;
    script += `/user rem [find comment~"APM2"];\n`;
    script += `/user add name="${apiUser}" password="${apiPass}" group=write comment="USER FOR APM2 BILLING API";\n\n`;

    script += `# 2. KONFIGURASI RADIUS\n`;
    script += `/radius incoming set accept=yes port=3799;\n`;
    script += `/radius rem [find comment~"APM2RADIUS"];\n`;
    script += `/radius add address=${ip} comment="APM2RADIUS" authentication-port=1812 accounting-port=1813 secret="${secret}" service=ppp,hotspot timeout=3s;\n\n`;

    script += `# 3. INTEGRASI PPP & HOTSPOT KE RADIUS\n`;
    script += `/ppp aaa set use-radius=yes accounting=yes interim-update=5m;\n`;
    script += `/ip hotspot profile set login-by=http-chap,http-pap,cookie,mac-cookie http-cookie-lifetime=4w2d use-radius=yes radius-accounting=yes [find]\n\n`;

    script += `# 4. SISTEM ISOLIR (SUSPEND)\n`;
    script += `/ip firewall address-list add address=${ip} comment="DEFAULT BY APM2 (DONT CHANGE IT)" list=APM2BYPASS\n`;
    script += `/ip proxy set enabled=yes port=8097;\n`;
    script += `### Konfigurasi Redirect Isolir ###\n`;
    script += `/ip proxy access rem [find comment~"APM2"]\n`;
    script += `/ip proxy access add action=redirect action-data="isolir.apm2.com" comment="DENY OTHER THAN THE ISOLIR IP THAT GOES TO THE WEB PROXY BY APM2" dst-address=!${ip} local-port=8097 ;\n`;
    script += `/ip firewall nat remove [find src-address-list~"APM2ISOLIR"]\n`;
    script += `/ip firewall nat add action=redirect chain=dstnat comment=APM2ISOLIR dst-address-list=!APM2BYPASS dst-port=80,443 protocol=tcp src-address-list=APM2ISOLIR to-ports=8097;\n`;
    script += `/ip firewall filter remove [find src-address-list~"APM2ISOLIR"]\n`;
    script += `/ip firewall filter add action=drop chain=forward comment=APM2ISOLIR dst-address=!${ip} dst-port=!53,5353 protocol=udp src-address-list=APM2ISOLIR;\n`;
    script += `/ip firewall filter add action=drop chain=forward comment=APM2ISOLIR dst-address=!${ip} protocol=tcp src-address-list=APM2ISOLIR;\n\n`;
    
    script += `# 5. L2TP VPN CONNECTION TO SERVER\n`;
    script += `/interface l2tp-client remove [find name="APM2-VPN"];\n`;
    script += `/interface l2tp-client add connect-to="${ip}" disabled=no name="APM2-VPN" user="${apiUser}" password="${apiPass}" profile="default" comment="VPN APM2 RADIUS";\n\n`;

    script += `# PERHATIAN: Simpan Data API Mikrotik ini ke dalam Data Router APM2 Anda!\n`;
    script += `# Username API/VPN: ${apiUser}\n`;
    script += `# Password API/VPN: ${apiPass}\n`;

    generatedScript.value = script;
}

async function copyScript() {
    if (generatedScript.value) {
        try {
            await navigator.clipboard.writeText(generatedScript.value);
            alert('Script berhasil disalin ke clipboard!');
        } catch (err) {
            console.error('Failed to copy: ', err);
        }
    }
}

function openCreateModal() {
    isEditing.value = false;
    editId.value = null;
    form.reset();
    form.nasname = '0.0.0.0/0'; // default to allow any IP securely
    form.type = 'mikrotik';
    generateRandomSecret();
    form.clearErrors();
    isModalOpen.value = true;
}

function openEditModal(n) {
    isEditing.value = true;
    editId.value = n.id;
    form.nasname = n.nasname;
    form.shortname = n.shortname || '';
    form.type = n.type || 'mikrotik';
    form.ports = n.ports;
    form.secret = n.secret;
    form.server = n.server || null;
    form.community = n.community || null;
    form.description = n.description || '';
    form.clearErrors();
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    setTimeout(() => form.reset(), 300);
}

function submit() {
    if (isEditing.value) {
        form.post(`/radius/nas/${editId.value}/update`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/radius/nas', {
            onSuccess: (page) => {
                closeModal();
                // Select the newly added router (assuming it's the latest in list)
                // Need to reload slightly delayed to catch the new ID, or just rely on reactivity
                setTimeout(() => {
                    const newNas = page.props.nas.data[page.props.nas.data.length - 1];
                    if (newNas) {
                        selectedNasId.value = newNas.id;
                        generateScript();
                    }
                }, 100);
            },
        });
    }
}

function deleteNas(id) {
    if (confirm('Apakah Anda yakin ingin menghapus NAS ini? (Pelanggan yang terhubung mungkin tidak bisa authentikasi)')) {
        router.post(`/radius/nas/${id}/delete`, {}, {
            onSuccess: () => {
                if (selectedNasId.value === id) {
                    selectedNasId.value = '';
                    generatedScript.value = '';
                }
            }
        });
    }
}

onMounted(() => {
    // If there are NAS items, select the first one by default
    if (props.nas && props.nas.data && props.nas.data.length > 0) {
        selectedNasId.value = props.nas.data[0].id;
        generateScript();
    }
});
</script>
