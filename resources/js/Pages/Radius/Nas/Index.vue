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
                            rows="16" 
                            class="w-full bg-gray-900 font-mono text-green-400 text-sm border border-gray-800 rounded-lg p-4 focus:ring-2 focus:ring-indigo-500"
                            placeholder="Silakan pilih router untuk memunculkan script..."></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-3 pt-2">
                        <button @click="openCreateModal" class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                            Generate Script Mikrotik Baru
                        </button>
                        <button v-if="selectedNasId" @click="forceRegenerate" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors shadow-sm">
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
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ n.nasname }}
                                    <div v-if="n.vpn_ip" class="text-xs text-indigo-600 font-mono mt-0.5">VPN: {{ n.vpn_ip }}</div>
                                </td>
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
    vpn: Object,
    clientPool: Object,
    isolirUrl: String,
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

// Escape teks script RouterOS agar aman dimasukkan ke dalam string "..." (on-event scheduler)
function rosEscape(src) {
    return src
        .replace(/\\/g, '\\\\')
        .replace(/"/g, '\\"')
        .replace(/\$/g, '\\$')
        .replace(/\r?\n/g, '\\r\\n');
}

function generateScript() {
    if (!selectedNasId.value) {
        generatedScript.value = '';
        return;
    }

    const nasItem = props.nas.data.find(n => n.id === selectedNasId.value);
    if (!nasItem) return;

    const v6 = selectedOs.value === 'v6';
    const publicIp = props.serverIp || '157.66.140.17';
    const vpnOn = props.vpn?.enabled && nasItem.vpn_user && nasItem.vpn_ip;
    // RADIUS & isolir diakses lewat IP tunnel jika VPN aktif, jika tidak lewat IP publik
    const radiusIp = vpnOn ? props.vpn.gateway : publicIp;
    const endpoints = (props.vpn?.endpoints?.length ? props.vpn.endpoints : [publicIp]);
    const pool = props.clientPool || { name: 'APM2POOL', network: '10.200.192.0/20', local_address: '10.200.192.1', ranges: '10.200.192.2-10.200.207.254' };
    const isolirUrl = props.isolirUrl || 'isolir.apm2.com';
    const secret = nasItem.secret;
    const name = (nasItem.shortname || 'APM2-ROUTER').replace(/"/g, '');
    const apiUser = nasItem.api_user || 'APM2API';
    const apiPass = nasItem.api_password || '';
    const profiles = [
        { name: 'APM2RADIUS', cookie: '1w', refresh: '1m', keepalive: '2m' },
        { name: 'APM2RADIUS-Short', cookie: '1w', refresh: '1m', keepalive: '2m' },
        { name: 'APM2RADIUS-Long', cookie: '4w2d', refresh: '6d10m', keepalive: '6d' },
    ];

    const L = [];
    L.push(`# COPY PASTE ALL SCRIPTS TO THE NEW MIKROTIK TERMINAL`);
    L.push(`# APM2 RADIUS - RouterOS ${v6 ? 'v6' : 'v7'} - Router: ${name}`);
    L.push(`#############################################################`);
    L.push(`/system identity set name="${name}";`);
    L.push(`/ip dns set allow-remote-requests=yes;`);
    L.push(``);

    L.push(`### 1. USER API (UNTUK BILLING APM2: CEK PING, ONLINE USER, TROUBLESHOOT) ###`);
    L.push(`/user rem [find comment~"APM2"];`);
    L.push(`/user add name="${apiUser}" password="${apiPass}" group=write comment="USER FOR APM2 BILLING API (DON'T CHANGE IT)";`);
    L.push(`/ip service set api disabled=no port=8728;`);
    L.push(``);

    L.push(`### 2. RADIUS ###`);
    L.push(`/ppp aaa set use-radius=yes accounting=yes interim-update=5m;`);
    L.push(`/radius incoming set accept=yes port=3799;`);
    L.push(`/radius rem [find comment~"APM2RADIUS"];`);
    L.push(`/radius add address=${radiusIp}${vpnOn ? ` src-address=${nasItem.vpn_ip}` : ''} comment="APM2RADIUS" authentication-port=1812 accounting-port=1813 secret="${secret}" service=ppp,hotspot timeout=3s;`);
    L.push(``);

    L.push(`### 3. IP POOL PELANGGAN ###`);
    L.push(`:if ([:len [/ip pool find name="${pool.name}"]] = 0) do={ /ip pool add name=${pool.name} ranges=${pool.ranges} comment="Network : ${pool.network}" } else={ /ip pool set [find name="${pool.name}"] ranges=${pool.ranges} comment="Network : ${pool.network}" };`);
    L.push(``);

    L.push(`### 4. HOTSPOT PROFILE & USER PROFILE ###`);
    L.push(`/ip hotspot profile set login-by=http-chap,http-pap,cookie,mac-cookie http-cookie-lifetime=4w2d use-radius=yes radius-accounting=yes [find];`);
    profiles.forEach(p => L.push(`/ip hotspot user profile remove [find name="${p.name}"];`));
    profiles.forEach(p => L.push(`/ip hotspot user profile add name=${p.name} mac-cookie-timeout=${p.cookie} status-autorefresh=${p.refresh} keepalive-timeout=${p.keepalive} shared-users=unlimited;`));
    L.push(`/ip hotspot user profile set [find default=yes] keepalive-timeout=2m mac-cookie-timeout=1w shared-users=unlimited status-autorefresh=1m;`);
    L.push(``);

    L.push(`### 5. PPP PROFILE ###`);
    profiles.forEach(p => L.push(`/ppp profile remove [find name="${p.name}"];`));
    profiles.forEach(p => L.push(`/ppp profile add name=${p.name} insert-queue-before=first local-address=${pool.local_address} remote-address=${pool.name} only-one=default;`));
    L.push(``);

    L.push(`### 6. SISTEM ISOLIR (WEB PROXY REDIRECT) ###`);
    L.push(`/ip firewall address-list rem [find list=APM2BYPASS];`);
    L.push(`/ip firewall address-list add address=${publicIp} comment="DEFAULT BY APM2 (DONT CHANGE IT)" list=APM2BYPASS;`);
    L.push(`/ip proxy set enabled=yes port=8097;`);
    L.push(`/ip proxy access rem [find comment~"APM2"];`);
    if (v6) {
        L.push(`/ip proxy access add action=deny redirect-to="${isolirUrl}" comment="DENY OTHER THAN THE ISOLIR IP THAT GOES TO THE WEB PROXY BY APM2" dst-address=!${publicIp} local-port=8097;`);
    } else {
        L.push(`/ip proxy access add action=redirect action-data="${isolirUrl}" comment="DENY OTHER THAN THE ISOLIR IP THAT GOES TO THE WEB PROXY BY APM2" dst-address=!${publicIp} local-port=8097;`);
    }
    L.push(`/ip firewall nat remove [find comment="APM2ISOLIR"];`);
    L.push(`/ip firewall nat add action=redirect chain=dstnat comment=APM2ISOLIR dst-address-list=!APM2BYPASS dst-port=80,443 protocol=tcp src-address-list=APM2ISOLIR to-ports=8097;`);
    L.push(`/ip firewall filter remove [find comment="APM2ISOLIR"];`);
    L.push(`/ip firewall filter add action=drop chain=forward comment=APM2ISOLIR dst-address=!${publicIp} dst-port=!53,5353 protocol=udp src-address-list=APM2ISOLIR;`);
    L.push(`/ip firewall filter add action=drop chain=forward comment=APM2ISOLIR dst-address=!${publicIp} protocol=tcp src-address-list=APM2ISOLIR;`);
    L.push(``);

    if (vpnOn) {
        L.push(`### 7. VPN KE SERVER APM2 (CUKUP 1 YANG AKTIF, SISANYA CADANGAN FAILOVER) ###`);
        L.push(`/system scheduler rem [find name~"apm2failovervpn"];`);
        L.push(`/interface l2tp-client remove [find name~"APM2-VPN"];`);
        L.push(`/interface sstp-client remove [find name~"APM2-VPN"];`);
        L.push(`/interface ovpn-client remove [find name~"APM2-VPN"];`);
        L.push(`/ppp profile remove [find name="APM2VPN"];`);
        L.push(`/ppp profile add name=APM2VPN change-tcp-mss=yes only-one=default use-encryption=yes comment="DEFAULT BY APM2 (DON'T CHANGE IT)";`);
        endpoints.forEach((ep, i) => {
            L.push(`/interface l2tp-client add disabled=${i === 0 ? 'no' : 'yes'} connect-to=${ep} name="APM2-VPN-${i + 1}" profile=APM2VPN user="${nasItem.vpn_user}" password="${nasItem.vpn_password}" add-default-route=no allow=chap,mschap2 comment="CUKUP AKTIFKAN 1 SAJA IPADDR : ${nasItem.vpn_ip}";`);
        });
        L.push(``);

        L.push(`### 8. STATIC ROUTE RADIUS LEWAT VPN ###`);
        L.push(`/ip route remove [find comment="STATIC ROUTE BY APM2"];`);
        L.push(`/ip route add dst-address=${radiusIp}/32 gateway=APM2-VPN-1 comment="STATIC ROUTE BY APM2";`);
        L.push(``);

        // Scheduler failover: ping RADIUS via tunnel, jika gagal pindah ke VPN berikutnya
        const failover = [
            `{`,
            `:global apm2VpnIndex`,
            `:local targetIP "${radiusIp}"`,
            `:local srcIP "${nasItem.vpn_ip}"`,
            `:local vpnIfaces [/interface find name~"^APM2-VPN-"]`,
            `:local vpnCount [:len $vpnIfaces]`,
            `:if ($vpnCount > 0) do={`,
            `  :if ([:typeof $apm2VpnIndex] != "num") do={ :set apm2VpnIndex 0 }`,
            `  :local pingResult 0`,
            `  :do { :set pingResult [/ping $targetIP count=5 src-address=$srcIP] } on-error={ :set pingResult 0 }`,
            `  :if ($pingResult = 0) do={`,
            `    :set apm2VpnIndex (($apm2VpnIndex + 1) % $vpnCount)`,
            `    :local selectedIface ($vpnIfaces->$apm2VpnIndex)`,
            `    :local selectedName [/interface get $selectedIface name]`,
            `    :foreach i in=$vpnIfaces do={ /interface set $i disabled=yes }`,
            `    /interface set $selectedIface disabled=no`,
            `    /ip route set [find comment="STATIC ROUTE BY APM2"] gateway=$selectedName`,
            `    :log warning ("APM2 VPN FAILOVER -> " . $selectedName)`,
            `  }`,
            `} else={ :log error "NO APM2-VPN INTERFACE FOUND" }`,
            `}`,
        ].join('\n');

        L.push(`### 9. SCHEDULER FAILOVER VPN (CEK TIAP 10 DETIK) ###`);
        L.push(`/system scheduler add interval=10s name=apm2failovervpn start-time=startup policy=ftp,reboot,read,write,policy,test,password,sniff,sensitive,romon on-event="${rosEscape(failover)}";`);
        L.push(``);
    }

    L.push(`#############################################################`);
    L.push(`# DATA UNTUK MENU "DATA ROUTER" APM2:`);
    L.push(`#   Host API      : ${vpnOn ? nasItem.vpn_ip + ' (IP VPN)' : '(IP publik router)'}`);
    L.push(`#   Port API      : 8728`);
    L.push(`#   Username API  : ${apiUser}`);
    L.push(`#   Password API  : ${apiPass}`);

    generatedScript.value = L.join('\n');
}

function forceRegenerate() {
    if (!selectedNasId.value) return;
    if (!confirm('Password VPN & API router ini akan diganti. Script lama di Mikrotik tidak akan bisa konek lagi sampai Anda paste script baru. Lanjutkan?')) return;
    router.post(`/radius/nas/${selectedNasId.value}/regenerate`, {}, {
        preserveScroll: true,
        onSuccess: () => generateScript(),
    });
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
                    const list = page.props.nas.data || [];
                    const newNas = list.reduce((a, b) => (!a || b.id > a.id ? b : a), null);
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
