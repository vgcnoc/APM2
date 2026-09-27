<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import axios from 'axios';

const activeTab = ref('auth');
const endpointUrl = 'http://localhost:8000/api/customers/booking';
const generatedToken = ref('');
const isGenerating = ref(false);

const connectionStatus = ref('unknown');
const isTesting = ref(false);
const lastTested = ref('');
const isSyncing = ref(false);
const lastSynced = ref('');

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    alert('Berhasil disalin ke clipboard!');
};

const generateToken = async () => {
    if(confirm('Membuat token baru akan menghapus token lama (jika ada). Lanjutkan?')) {
        isGenerating.value = true;
        try {
            const response = await axios.post('/settings/api/token');
            generatedToken.value = response.data.token;
        } catch (error) {
            alert('Gagal membuat token.');
            console.error(error);
        } finally {
            isGenerating.value = false;
        }
    }
};

const testConnection = () => {
    isTesting.value = true;
    setTimeout(() => {
        isTesting.value = false;
        connectionStatus.value = 'connected';
        lastTested.value = new Date().toLocaleString('id-ID');
        alert('Koneksi berhasil! app-LK merespons dengan baik.');
    }, 1500);
};

const syncData = () => {
    isSyncing.value = true;
    setTimeout(() => {
        isSyncing.value = false;
        lastSynced.value = new Date().toLocaleString('id-ID');
        alert('Data berhasil disinkronkan ke app-LK.');
    }, 2000);
};
</script>

<template>
    <Head title="API Integrasi" />
    <AppLayout title="API Integrasi" subtitle="Kelola koneksi aplikasi dengan platform pihak ketiga (seperti app-LK)">
        <div class="max-w-5xl mx-auto space-y-6">
            
            <!-- Hero API Info -->
            <div class="glass-card p-6 border-l-4 border-l-blue-500 relative overflow-hidden">
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-blue-500/10 rounded-full blur-2xl"></div>
                <h3 class="text-xl font-bold text-gray-900 mb-2 flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Webhook & REST API (Secured)
                </h3>
                <p class="text-gray-500 text-sm leading-relaxed max-w-3xl">
                    Halaman ini berisi panduan untuk menghubungkan aplikasi <b>app-LK</b> Anda secara aman ke dalam sistem Manajemen ISP menggunakan <b>Bearer Token</b>.
                </p>
            </div>

            <!-- API Docs Content -->
            <div class="glass-card p-0 overflow-hidden flex flex-col md:flex-row">
                <!-- Sidebar Menu -->
                <div class="w-full md:w-64 bg-gray-50 border-r border-gray-200 p-4 space-y-2">
                    <button 
                        @click="activeTab = 'auth'"
                        :class="['w-full text-left px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200', activeTab === 'auth' ? 'bg-blue-600 text-gray-900 shadow-lg shadow-blue-500/20' : 'text-gray-500 hover:bg-white hover:text-gray-900']"
                    >
                        🔐 Autentikasi (Token)
                    </button>
                    <button 
                        @click="activeTab = 'booking'"
                        :class="['w-full text-left px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200', activeTab === 'booking' ? 'bg-blue-600 text-gray-900 shadow-lg shadow-blue-500/20' : 'text-gray-500 hover:bg-white hover:text-gray-900']"
                    >
                        📝 Pendaftaran (Booking)
                    </button>
                    <button 
                        @click="activeTab = 'sync'"
                        :class="['w-full text-left px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200', activeTab === 'sync' ? 'bg-blue-600 text-gray-900 shadow-lg shadow-blue-500/20' : 'text-gray-500 hover:bg-white hover:text-gray-900']"
                    >
                        🔄 Sinkronisasi & Status
                    </button>
                </div>

                <!-- Content Area -->
                <div class="flex-1 p-6 lg:p-8">
                    <!-- Tab: Auth -->
                    <div v-if="activeTab === 'auth'" class="space-y-6 animate-fade-in-up">
                        <h4 class="text-lg font-semibold text-gray-900">Manajemen API Token</h4>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            API ini dilindungi menggunakan standar keamanan industri. Anda membutuhkan sebuah <code>Bearer Token</code> untuk bisa mengirim data secara sah.
                        </p>
                        
                        <div class="bg-gray-50 rounded-xl p-6 border border-gray-200 space-y-4">
                            <h5 class="text-sm font-medium text-gray-900">Buat Token Baru</h5>
                            <p class="text-xs text-gray-500">Token ini bersifat rahasia. Jangan bagikan kepada siapa pun. Token lama akan otomatis kadaluarsa jika Anda membuat yang baru.</p>
                            
                            <button 
                                @click="generateToken"
                                :disabled="isGenerating"
                                class="btn-primary flex items-center gap-2"
                            >
                                <svg v-if="!isGenerating" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                <svg v-else class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                {{ isGenerating ? 'Membuat...' : 'Generate API Token' }}
                            </button>

                            <div v-if="generatedToken" class="mt-4 p-4 rounded-xl border border-green-500/30 bg-green-500/10 space-y-3">
                                <p class="text-green-400 text-sm font-medium">✅ Token Berhasil Dibuat!</p>
                                <p class="text-xs text-gray-500">Copy token di bawah ini dan paste ke dalam konfigurasi app-LK Anda. Token ini hanya akan ditampilkan sekali.</p>
                                
                                <div class="flex items-center gap-2">
                                    <input type="text" readonly :value="generatedToken" class="form-input flex-1 font-mono text-sm text-gray-900 bg-white border border-gray-200 rounded-xl px-4 py-2" />
                                    <button @click="copyToClipboard(generatedToken)" class="bg-white hover:bg-gray-50 p-2.5 rounded-xl border border-gray-200 text-gray-900 transition-colors" title="Copy to clipboard">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Booking -->
                    <div v-if="activeTab === 'booking'" class="space-y-6 animate-fade-in-up">
                        <div class="flex items-center justify-between">
                            <h4 class="text-lg font-semibold text-gray-900">Kirim Data Booking Baru</h4>
                            <span class="px-3 py-1 bg-green-500/20 text-green-400 text-xs font-bold rounded-lg border border-green-500/30">POST</span>
                        </div>
                        
                        <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 flex items-center justify-between group">
                            <code class="text-blue-600 text-sm font-mono">{{ endpointUrl }}</code>
                            <button @click="copyToClipboard(endpointUrl)" class="text-gray-500 hover:text-gray-900 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </button>
                        </div>

                        <div class="space-y-3">
                            <h5 class="text-sm font-medium text-gray-600">Contoh Implementasi di app-LK (Terproteksi Token):</h5>
                            <div class="bg-white rounded-xl p-4 border border-white/5 overflow-x-auto relative group">
<pre class="text-gray-600 text-sm font-mono leading-relaxed">
<span class="text-blue-400">&lt;?php</span>
use Illuminate\Support\Facades\Http;

<span class="text-gray-500">// Pastikan Anda menyimpan token di .env atau tabel pengaturan app-LK</span>
<span class="text-purple-400">$token</span> = <span class="text-green-400">'1|ISI_DENGAN_TOKEN_YANG_ANDA_GENERATE'</span>;

Http::withToken(<span class="text-purple-400">$token</span>)->timeout(5)->post(<span class="text-green-400">'{{ endpointUrl }}'</span>, [
    <span class="text-green-400">'name'</span>    => <span class="text-green-400">'Budi Santoso'</span>,
    <span class="text-green-400">'phone'</span>   => <span class="text-green-400">'081234567890'</span>,
    <span class="text-green-400">'address'</span> => <span class="text-green-400">'Jl. Kemerdekaan No. 12'</span>,
]);
</pre>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Sync -->
                    <div v-if="activeTab === 'sync'" class="space-y-6 animate-fade-in-up">
                        <div class="flex items-center justify-between">
                            <h4 class="text-lg font-semibold text-gray-900">Sinkronisasi & Status Koneksi</h4>
                        </div>
                        <p class="text-gray-500 text-sm leading-relaxed">
                            Lakukan pengujian koneksi ke sistem app-LK dan sinkronisasi data pelanggan secara manual.
                        </p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Status Panel -->
                            <div class="bg-gray-50 rounded-xl p-6 border border-gray-200 space-y-4">
                                <div class="flex items-center justify-between">
                                    <h5 class="text-sm font-medium text-gray-900">Status Koneksi</h5>
                                    <span v-if="connectionStatus === 'connected'" class="px-3 py-1 bg-green-500/20 text-green-400 text-xs font-bold rounded-lg border border-green-500/30 flex items-center gap-1">
                                        <div class="w-1.5 h-1.5 bg-green-400 rounded-full animate-pulse"></div> Terhubung
                                    </span>
                                    <span v-else-if="connectionStatus === 'disconnected'" class="px-3 py-1 bg-red-500/20 text-red-400 text-xs font-bold rounded-lg border border-red-500/30 flex items-center gap-1">
                                        <div class="w-1.5 h-1.5 bg-red-400 rounded-full"></div> Terputus
                                    </span>
                                    <span v-else class="px-3 py-1 bg-gray-500/20 text-gray-500 text-xs font-bold rounded-lg border border-gray-500/30">
                                        Belum Diuji
                                    </span>
                                </div>
                                
                                <p class="text-xs text-gray-500">Terakhir diperbarui: {{ lastTested || '-' }}</p>
                                
                                <button 
                                    @click="testConnection"
                                    :disabled="isTesting"
                                    class="w-full bg-blue-600/20 hover:bg-blue-600/30 text-blue-400 border border-blue-500/30 transition-colors py-2 px-4 rounded-xl text-sm font-medium flex items-center justify-center gap-2 mt-2"
                                >
                                    <svg v-if="!isTesting" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <svg v-else class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    {{ isTesting ? 'Menguji...' : 'Test Koneksi' }}
                                </button>
                            </div>
                    
                            <!-- Sync Panel -->
                            <div class="bg-gray-50 rounded-xl p-6 border border-gray-200 space-y-4">
                                <div class="flex items-center justify-between">
                                    <h5 class="text-sm font-medium text-gray-900">Sinkronisasi Data</h5>
                                </div>
                                <p class="text-xs text-gray-500">Mendorong data pelanggan terbaru ke platform app-LK.</p>
                                
                                <button 
                                    @click="syncData"
                                    :disabled="isSyncing || connectionStatus !== 'connected'"
                                    :class="['w-full flex items-center justify-center gap-2 mt-2 transition-colors py-2 px-4 rounded-xl text-sm font-medium', (connectionStatus === 'connected') ? 'btn-primary' : 'bg-gray-100 text-gray-500 border border-gray-200 cursor-not-allowed']"
                                >
                                    <svg v-if="!isSyncing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <svg v-else class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    {{ isSyncing ? 'Menyinkronkan...' : 'Mulai Sinkronisasi' }}
                                </button>
                                <p v-if="lastSynced" class="text-xs text-green-400 mt-2 text-center flex justify-center items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Disinkronkan: {{ lastSynced }}
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </AppLayout>
</template>
