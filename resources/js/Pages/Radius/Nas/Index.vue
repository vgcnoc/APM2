<template>
    <AppLayout title="Mikrotik (NAS)">
        <div class="p-6 max-w-6xl mx-auto space-y-6">
            <div class="p-4 rounded-xl border flex flex-wrap items-center justify-between gap-3" :class="configuration?.pending ? 'bg-amber-50 border-amber-200 text-amber-900' : 'bg-emerald-50 border-emerald-200 text-emerald-900'">
                <div><p class="text-sm font-semibold">{{ configuration?.pending ? 'Konfigurasi NAS belum diterapkan' : 'Konfigurasi NAS sudah diterapkan' }}</p><p class="text-xs mt-1">Satu router menggunakan satu IP sumber RADIUS. IP VPN dialokasikan otomatis, tanpa wildcard.</p></div>
                <button v-if="configuration?.enabled" type="button" @click="applyConfiguration" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-xs font-semibold">Validasi &amp; Terapkan NAS</button>
            </div>
            
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
                        <p class="text-red-500">- Pastikan VIZBILL ada di urutan teratas pada menu Mikrotik-Radius</p>
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
                <div class="px-6 py-5 border-b border-gray-100 flex flex-wrap gap-3 items-center justify-between">
                    <h3 class="font-bold text-gray-900 text-lg">Daftar Router (NAS) Terdaftar</h3>
                    <form @submit.prevent="searchNas" class="flex gap-2">
                        <input v-model="search" type="search" aria-label="Cari NAS" placeholder="Cari nama atau IP NAS…" class="rounded-lg border-gray-200 text-sm" />
                        <button class="rounded-lg bg-indigo-600 text-white px-3 text-xs font-semibold">Cari</button>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100">
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">IP sumber NAS</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Nama</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Tipe</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Secret</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Koneksi</th>
                                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
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
                                    <span class="bg-gray-100 px-2 py-1 rounded">••••••••</span>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium">
                                    <span v-if="connectionStatuses[n.id] === 'online'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Online
                                    </span>
                                    <span v-else-if="connectionStatuses[n.id] === 'offline'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Offline
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1.5 text-xs text-gray-500">
                                        <svg class="animate-spin h-3 w-3 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        Memeriksa...
                                    </span>
                                    <p v-if="connectionDetails[n.id]?.message" class="mt-1 text-[11px] max-w-52 text-gray-500">{{ connectionDetails[n.id].message }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-right flex gap-3 justify-end">
                                    <button @click="openEditModal(n)" class="text-indigo-600 hover:text-indigo-900 font-medium">Edit</button>
                                    <button @click="deleteNas(n.id)" class="text-red-600 hover:text-red-900 font-medium">Hapus</button>
                                </td>
                            </tr>
                            <tr v-if="nas.data.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500 text-sm">
                                    Belum ada data NAS.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3 text-xs text-gray-500">
                    <span>{{ nas.from || 0 }}–{{ nas.to || 0 }} dari {{ nas.total || 0 }} NAS</span>
                    <div class="flex gap-2">
                        <Link v-if="nas.prev_page_url" :href="nas.prev_page_url" preserve-state class="rounded border border-gray-200 px-3 py-2">Sebelumnya</Link>
                        <span class="px-3 py-2">{{ nas.current_page }} / {{ nas.last_page }}</span>
                        <Link v-if="nas.next_page_url" :href="nas.next_page_url" preserve-state class="rounded border border-gray-200 px-3 py-2">Berikutnya</Link>
                    </div>
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
                            Pilih VPN untuk mendapatkan IP tunnel unik otomatis. Koneksi langsung wajib memakai IP sumber router yang spesifik. Wildcard <b>0.0.0.0/0</b> tidak diperbolehkan.
                        </div>

                        <div>
                            <label v-if="!isEditing && vpn?.enabled" class="flex items-center gap-2 mb-3 text-sm text-gray-700"><input type="checkbox" v-model="form.use_vpn" class="rounded" /> Gunakan VPN billing (IP NAS otomatis)</label>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">IP sumber NAS (nasname)</label>
                            <input v-model="form.nasname" type="text" :readonly="form.use_vpn" :required="!form.use_vpn" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500" :placeholder="form.use_vpn ? 'Dialokasikan otomatis dari pool VPN' : 'IP sumber router, contoh 192.168.1.1'" />
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
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Port CoA / Disconnect</label>
                            <input v-model="form.coa_port" type="number" min="1" max="65535" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm" />
                            <p v-if="form.errors.coa_port" class="text-red-500 text-xs mt-1">{{ form.errors.coa_port }}</p>
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
import { ref, onMounted, watch } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    nas: Object,
    filters: Object,
    serverIp: String,
    vpn: Object,
    clientPool: Object,
    isolirUrl: String,
    configuration: Object,
    createdNasId: Number,
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editId = ref(null);
const search = ref(props.filters?.search || '');
function searchNas() {
    router.get('/radius/nas', { search: search.value || undefined }, { preserveState: true, replace: true });
}

const selectedOs = ref('v7');
const selectedNasId = ref('');
const generatedScript = ref('');

const form = useForm({
    nasname: '',
    use_vpn: Boolean(props.vpn?.enabled),
    shortname: '',
    type: 'mikrotik',
    ports: null,
    coa_port: 3799,
    secret: '',
    server: null,
    community: null,
    description: ''
});

function generateRandomSecret() {
    const bytes = new Uint8Array(24);
    crypto.getRandomValues(bytes);
    form.secret = Array.from(bytes, value => value.toString(16).padStart(2, '0')).join('');
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
    const radiusHostMask = radiusIp.includes(':') ? 128 : 32;
    const endpoints = (props.vpn?.endpoints?.length ? props.vpn.endpoints : [publicIp]);
    const pool = props.clientPool || { name: 'VIZBILLPOOL', network: '10.200.192.0/20', local_address: '10.200.192.1', ranges: '10.200.192.2-10.200.207.254' };
    const isolirUrl = props.isolirUrl || 'isolir.apm2.com';
    const secret = rosEscape(nasItem.secret || '');
    const name = (nasItem.shortname || 'VIZBILL-ROUTER').replace(/"/g, '');
    const apiUser = rosEscape(nasItem.api_user || 'VIZBILLAPI');
    const apiPass = rosEscape(nasItem.api_password || '');
    const apiPort = nasItem.api_port || 8728;
    const coaPort = nasItem.coa_port || 3799;
    const profiles = [
        { name: 'VIZBILLRADIUS', cookie: '1w', refresh: '1m', keepalive: '2m' },
        { name: 'VIZBILLRADIUS-Short', cookie: '1w', refresh: '1m', keepalive: '2m' },
        { name: 'VIZBILLRADIUS-Long', cookie: '4w2d', refresh: '6d10m', keepalive: '6d' },
    ];

    const L = [];
    L.push(`# COPY PASTE ALL SCRIPTS TO THE NEW MIKROTIK TERMINAL`);
    L.push(`# VIZBILL RADIUS - RouterOS ${v6 ? 'v6' : 'v7'} - Router: ${name}`);
    L.push(`#############################################################`);
    L.push(`/system identity set name="${name}";`);
    L.push(`/ip dns set allow-remote-requests=yes;`);
    L.push(``);

    L.push(`### 1. USER API (UNTUK BILLING VIZBILL: CEK PING, ONLINE USER, TROUBLESHOOT) ###`);
    L.push(`/user rem [find comment~"APM2"];`);
    L.push(`/user rem [find comment~"VIZBILL"];`);
    L.push(`/user add name="${apiUser}" password="${apiPass}" group=read address=${radiusIp}/${radiusHostMask} comment="USER FOR VIZBILL BILLING API (DON'T CHANGE IT)";`);
    L.push(`/ip service set api disabled=no port=${apiPort};`);
    L.push(``);

    L.push(`### 2. RADIUS ###`);
    L.push(`/ppp aaa set use-radius=yes accounting=yes interim-update=1m;`);
    L.push(`/radius incoming set accept=yes port=${coaPort};`);
    L.push(`/radius rem [find comment~"APM2RADIUS"];`);
    L.push(`/radius rem [find comment~"VIZBILLRADIUS"];`);
    L.push(`/radius add address=${radiusIp}${vpnOn ? ` src-address=${nasItem.vpn_ip}` : ''} comment="VIZBILLRADIUS" authentication-port=1812 accounting-port=1813 secret="${secret}" service=ppp,hotspot timeout=3s;`);
    L.push(``);

    L.push(`### 3. IP POOL PELANGGAN ###`);
    L.push(`:if ([:len [/ip pool find name="${pool.name}"]] = 0) do={ /ip pool add name=${pool.name} ranges=${pool.ranges} comment="Network : ${pool.network}" } else={ /ip pool set [find name="${pool.name}"] ranges=${pool.ranges} comment="Network : ${pool.network}" };`);
    L.push(``);

    L.push(`### 4. HOTSPOT PROFILE & USER PROFILE ###`);
    L.push(`/ip hotspot profile set login-by=http-chap,http-pap,cookie,mac-cookie http-cookie-lifetime=4w2d use-radius=yes radius-accounting=yes [find];`);
    ['APM2RADIUS', 'APM2RADIUS-Short', 'APM2RADIUS-Long'].forEach(p => L.push(`/ip hotspot user profile remove [find name="${p}"];`));
    profiles.forEach(p => L.push(`/ip hotspot user profile remove [find name="${p.name}"];`));
    profiles.forEach(p => L.push(`/ip hotspot user profile add name=${p.name} mac-cookie-timeout=${p.cookie} status-autorefresh=${p.refresh} keepalive-timeout=${p.keepalive} shared-users=unlimited;`));
    L.push(`/ip hotspot user profile set [find default=yes] keepalive-timeout=2m mac-cookie-timeout=1w shared-users=unlimited status-autorefresh=1m;`);
    L.push(``);

    L.push(`### 5. PPP PROFILE ###`);
    ['APM2RADIUS', 'APM2RADIUS-Short', 'APM2RADIUS-Long'].forEach(p => L.push(`/ppp profile remove [find name="${p}"];`));
    profiles.forEach(p => L.push(`/ppp profile remove [find name="${p.name}"];`));
    profiles.forEach(p => L.push(`/ppp profile add name=${p.name} insert-queue-before=first local-address=${pool.local_address} remote-address=${pool.name} only-one=default;`));
    L.push(``);

    L.push(`### 6. SISTEM ISOLIR (WEB PROXY REDIRECT) ###`);
    L.push(`/ip firewall address-list rem [find list=APM2BYPASS];`);
    L.push(`/ip firewall address-list rem [find list=VIZBILLBYPASS];`);
    L.push(`/ip firewall address-list add address=${publicIp} comment="DEFAULT BY VIZBILL (DONT CHANGE IT)" list=VIZBILLBYPASS;`);
    L.push(`/ip proxy set enabled=yes port=8097;`);
    L.push(`/ip proxy access rem [find comment~"APM2"];`);
    L.push(`/ip proxy access rem [find comment~"VIZBILL"];`);
    if (v6) {
        L.push(`/ip proxy access add action=deny redirect-to="${isolirUrl}" comment="DENY OTHER THAN THE ISOLIR IP THAT GOES TO THE WEB PROXY BY VIZBILL" dst-address=!${publicIp} local-port=8097;`);
    } else {
        L.push(`/ip proxy access add action=redirect action-data="${isolirUrl}" comment="DENY OTHER THAN THE ISOLIR IP THAT GOES TO THE WEB PROXY BY VIZBILL" dst-address=!${publicIp} local-port=8097;`);
    }
    L.push(`/ip firewall nat remove [find comment="APM2ISOLIR"];`);
    L.push(`/ip firewall nat remove [find comment="VIZBILLISOLIR"];`);
    L.push(`/ip firewall nat add action=redirect chain=dstnat comment=VIZBILLISOLIR dst-address-list=!VIZBILLBYPASS dst-port=80,443 protocol=tcp src-address-list=VIZBILLISOLIR to-ports=8097;`);
    L.push(`/ip firewall filter remove [find comment="APM2ISOLIR"];`);
    L.push(`/ip firewall filter remove [find comment="VIZBILLISOLIR"];`);
    L.push(`/ip firewall filter add action=drop chain=forward comment=VIZBILLISOLIR dst-address=!${publicIp} dst-port=!53,5353 protocol=udp src-address-list=VIZBILLISOLIR;`);
    L.push(`/ip firewall filter add action=drop chain=forward comment=VIZBILLISOLIR dst-address=!${publicIp} protocol=tcp src-address-list=VIZBILLISOLIR;`);
    L.push(``);

    if (vpnOn) {
        L.push(`### 7. VPN KE SERVER VIZBILL (CUKUP 1 YANG AKTIF, SISANYA CADANGAN FAILOVER) ###`);
        L.push(`/system scheduler rem [find name~"apm2failovervpn"];`);
        L.push(`/system scheduler rem [find name~"vizbillfailovervpn"];`);
        L.push(`/interface l2tp-client remove [find name~"APM2-VPN"];`);
        L.push(`/interface sstp-client remove [find name~"APM2-VPN"];`);
        L.push(`/interface ovpn-client remove [find name~"APM2-VPN"];`);
        L.push(`/interface l2tp-client remove [find name~"VIZBILL-VPN"];`);
        L.push(`/interface sstp-client remove [find name~"VIZBILL-VPN"];`);
        L.push(`/interface ovpn-client remove [find name~"VIZBILL-VPN"];`);
        L.push(`/ppp profile remove [find name="APM2VPN"];`);
        L.push(`/ppp profile remove [find name="VIZBILLVPN"];`);
        L.push(`/ppp profile add name=VIZBILLVPN change-tcp-mss=yes only-one=default use-encryption=no comment="DEFAULT BY VIZBILL (DON'T CHANGE IT)";`);
        endpoints.forEach((ep, i) => {
            L.push(`/interface ovpn-client add disabled=${i === 0 ? 'no' : 'yes'} connect-to=${ep} port=1195 name="VIZBILL-VPN-${i + 1}" profile=VIZBILLVPN user="${nasItem.vpn_user}" password="${nasItem.vpn_password}" add-default-route=no cipher=aes256 auth=sha1 certificate=none comment="CUKUP AKTIFKAN 1 SAJA IPADDR : ${nasItem.vpn_ip}";`);
        });
        L.push(``);

        L.push(`### 8. STATIC ROUTE RADIUS LEWAT VPN ###`);
        L.push(`/ip route remove [find comment="STATIC ROUTE BY APM2"];`);
        L.push(`/ip route remove [find comment="STATIC ROUTE BY VIZBILL"];`);
        L.push(`/ip route add dst-address=${radiusIp}/32 gateway=VIZBILL-VPN-1 comment="STATIC ROUTE BY VIZBILL";`);
        L.push(``);

        // Scheduler failover: ping RADIUS via tunnel, jika gagal pindah ke VPN berikutnya
        const failover = [
            `{`,
            `:global vizbillVpnIndex`,
            `:local targetIP "${radiusIp}"`,
            `:local srcIP "${nasItem.vpn_ip}"`,
            `:local vpnIfaces [/interface find name~"^VIZBILL-VPN-"]`,
            `:local vpnCount [:len $vpnIfaces]`,
            `:if ($vpnCount > 0) do={`,
            `  :if ([:typeof $vizbillVpnIndex] != "num") do={ :set vizbillVpnIndex 0 }`,
            `  :local pingResult 0`,
            `  :do { :set pingResult [/ping $targetIP count=5 src-address=$srcIP] } on-error={ :set pingResult 0 }`,
            `  :if ($pingResult = 0) do={`,
            `    :set vizbillVpnIndex (($vizbillVpnIndex + 1) % $vpnCount)`,
            `    :local selectedIface ($vpnIfaces->$vizbillVpnIndex)`,
            `    :local selectedName [/interface get $selectedIface name]`,
            `    :foreach i in=$vpnIfaces do={ /interface set $i disabled=yes }`,
            `    /interface set $selectedIface disabled=no`,
            `    /ip route set [find comment="STATIC ROUTE BY VIZBILL"] gateway=$selectedName`,
            `    :log warning ("VIZBILL VPN FAILOVER -> " . $selectedName)`,
            `  }`,
            `} else={ :log error "NO VIZBILL-VPN INTERFACE FOUND" }`,
            `}`,
        ].join('\n');

        L.push(`### 9. SCHEDULER FAILOVER VPN (CEK TIAP 10 DETIK) ###`);
        L.push(`/system scheduler add interval=10s name=vizbillfailovervpn start-time=startup policy=ftp,reboot,read,write,policy,test,password,sniff,sensitive,romon on-event="${rosEscape(failover)}";`);
        L.push(``);
    }

    L.push(`#############################################################`);
    L.push(`# DATA UNTUK MENU "DATA ROUTER" VIZBILL:`);
    L.push(`#   Host API      : ${vpnOn ? nasItem.vpn_ip + ' (IP VPN)' : '(IP publik router)'}`);
    L.push(`#   Port API      : ${apiPort}`);
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
    form.nasname = '';
    form.use_vpn = Boolean(props.vpn?.enabled);
    form.type = 'mikrotik';
    generateRandomSecret();
    form.clearErrors();
    isModalOpen.value = true;
}

function openEditModal(n) {
    isEditing.value = true;
    editId.value = n.id;
    form.nasname = n.vpn_ip || n.nasname;
    form.use_vpn = Boolean(n.vpn_ip);
    form.coa_port = n.coa_port || 3799;
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
                // Select the exact ID returned by the server, never guess from ordering.
                setTimeout(() => {
                    const list = page.props.nas.data || [];
                    const newNas = list.find(n => n.id === page.props.createdNasId);
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
    checkConnections();
});

const connectionStatuses = ref({});
const connectionDetails = ref({});

function checkConnections() {
    if (!props.nas || !props.nas.data) return;
    props.nas.data.forEach(n => {
        connectionStatuses.value[n.id] = 'loading';
        axios.get(`/radius/nas/${n.id}/ping`)
            .then(res => {
                connectionStatuses.value[n.id] = res.data.status;
                connectionDetails.value[n.id] = res.data;
            })
            .catch(() => {
                connectionStatuses.value[n.id] = 'offline';
            });
    });
}

function applyConfiguration() {
    if (confirm('Validasi dan terapkan konfigurasi NAS? FreeRADIUS dapat dimuat ulang singkat untuk menerapkan daftar router terbaru.')) router.post('/radius/nas/apply', {}, { preserveScroll: true });
}
watch(() => props.nas?.data, () => {
    if (!props.nas?.data?.some(n => n.id === selectedNasId.value)) selectedNasId.value = props.nas?.data?.[0]?.id || '';
    generateScript();
    checkConnections();
});
</script>
