<template>
    <AppLayout title="Pengaturan Gaji & Auto Transfer">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 font-sans">
            <!-- Header Title -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
                        <span class="p-2 bg-indigo-50 text-indigo-600 rounded-2xl text-xl">💼</span>
                        <span>Pengaturan Sistem Gaji &amp; Auto Transfer</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">Kelola preferensi siklus gaji, komponen otomatis, serta integrasi API Xendit untuk auto transfer karyawan</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- 1. PENGATURAN API XENDIT (AUTO TRANSFER DISBURSEMENTS) -->
                <div class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-7 text-white shadow-xl relative overflow-hidden border border-indigo-900/50">
                    <div class="absolute -right-12 -top-12 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="relative z-10">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-white/10">
                            <div class="flex items-center gap-3.5">
                                <div class="w-12 h-12 rounded-2xl bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-2xl shadow-inner">
                                    ⚡
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h2 class="text-lg font-black tracking-tight text-white">Integrasi Xendit Auto Transfer (Disbursements)</h2>
                                        <span :class="form.xendit_disbursement_enabled ? 'bg-emerald-500/20 text-emerald-300 border-emerald-400/30' : 'bg-white/10 text-slate-400 border-white/10'" class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase border">
                                            {{ form.xendit_disbursement_enabled ? '● AKTIF' : '○ NONAKTIF' }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-indigo-200/80 mt-0.5">Kirim transfer gaji &amp; insentif langsung ke rekening bank karyawan secara real-time via API Xendit</p>
                                </div>
                            </div>

                            <!-- Toggle Switch Enable Xendit -->
                            <label class="relative inline-flex items-center cursor-pointer self-start sm:self-auto">
                                <input type="checkbox" v-model="form.xendit_disbursement_enabled" class="sr-only peer">
                                <div class="w-14 h-7 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-500 shadow-inner"></div>
                                <span class="ml-3 text-xs font-bold text-slate-200">
                                    {{ form.xendit_disbursement_enabled ? 'Fitur Aktif' : 'Aktifkan' }}
                                </span>
                            </label>
                        </div>

                        <!-- Form Input Xendit -->
                        <div class="mt-6 space-y-5">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                                <!-- Secret API Key -->
                                <div>
                                    <label class="block text-xs font-bold text-indigo-200 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                        <span>Secret API Key Xendit *</span>
                                        <span class="text-[10px] font-medium text-slate-400">Sandbox: <code class="text-indigo-300">xnd_development_...</code> | Live: <code class="text-emerald-300">xnd_production_...</code></span>
                                    </label>
                                    <div class="relative">
                                        <input 
                                            :type="showApiKey ? 'text' : 'password'" 
                                            v-model="form.xendit_secret_key" 
                                            placeholder="Contoh: xnd_development_abcdef123456..." 
                                            class="w-full bg-slate-800/80 border border-slate-700 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/20 text-white rounded-xl px-4 py-2.5 text-xs font-mono shadow-sm transition-all"
                                        />
                                        <button 
                                            type="button" 
                                            @click="showApiKey = !showApiKey" 
                                            class="absolute right-3 top-2.5 text-slate-400 hover:text-white text-xs font-bold transition-colors"
                                        >
                                            {{ showApiKey ? 'Sembunyikan' : 'Lihat' }}
                                        </button>
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-1">Dapatkan dari menu <strong>Settings &rarr; API Keys &rarr; Secret Key</strong> di Dashboard Xendit Anda.</p>
                                </div>

                                <!-- Webhook Callback Token -->
                                <div>
                                    <label class="block text-xs font-bold text-indigo-200 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                        <span>Webhook Verification Token</span>
                                        <span class="text-[10px] text-slate-400">(Opsional untuk validasi callback)</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        v-model="form.xendit_webhook_token" 
                                        placeholder="Contoh: token_verifikasi_webhook..." 
                                        class="w-full bg-slate-800/80 border border-slate-700 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/20 text-white rounded-xl px-4 py-2.5 text-xs font-mono shadow-sm transition-all"
                                    />
                                    <p class="text-[11px] text-slate-400 mt-1">Digunakan untuk memvalidasi header <code>x-callback-token</code> saat Xendit mengirim update status transfer.</p>
                                </div>
                            </div>

                            <!-- Opsi Otomatisasi Pencairan -->
                            <div class="pt-2">
                                <label class="flex items-start gap-3 p-3.5 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors cursor-pointer">
                                    <input 
                                        type="checkbox" 
                                        v-model="form.xendit_auto_disburse_on_approve" 
                                        class="rounded border-slate-700 bg-slate-800 text-indigo-500 focus:ring-indigo-400 mt-0.5"
                                    />
                                    <div>
                                        <span class="text-xs font-extrabold text-white block">Auto Disburse Otomatis Saat Tombol "Cairkan" Ditekan</span>
                                        <span class="text-[11px] text-slate-300 block mt-0.5">Jika dicentang, menekan tombol Cairkan / Cairkan Semua di menu Payroll akan otomatis memicu transfer dana via Xendit tanpa perlu konfirmasi manual ulang.</span>
                                    </div>
                                </label>
                            </div>

                            <!-- Action Bar: Test Connection & Webhook Info -->
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 pt-3 border-t border-white/10">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <button 
                                        type="button" 
                                        @click="testXendit" 
                                        :disabled="isTesting || !form.xendit_secret_key" 
                                        class="px-4 py-2 rounded-xl text-xs font-extrabold bg-indigo-500 hover:bg-indigo-600 active:scale-95 text-white transition-all shadow-md flex items-center gap-2 disabled:opacity-50 cursor-pointer"
                                    >
                                        <svg v-if="isTesting" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        <span v-else>⚡</span>
                                        <span>{{ isTesting ? 'Menguji & Mengambil Saldo...' : 'Tes Koneksi & Cek Saldo Xendit' }}</span>
                                    </button>

                                    <button 
                                        type="button"
                                        @click="copyWebhookUrl"
                                        class="px-3 py-2 rounded-xl text-xs font-semibold bg-white/10 hover:bg-white/15 text-indigo-200 border border-white/10 transition-colors flex items-center gap-1.5"
                                        title="Salin Webhook URL untuk didaftarkan di Xendit"
                                    >
                                        <span>📋</span>
                                        <span>Salin Webhook URL</span>
                                    </button>
                                </div>

                                <div v-if="testResult" class="text-xs">
                                    <span v-if="testResult.status === 'success'" class="text-emerald-400 font-bold flex items-center gap-1.5">
                                        <span>✓</span>
                                        <span>{{ testResult.message }}</span>
                                    </span>
                                    <span v-else class="text-rose-400 font-bold flex items-center gap-1.5">
                                        <span>⚠️</span>
                                        <span>{{ testResult.message }}</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Box Saldo Kas & Panduan Webhook -->
                            <div v-if="testResult && testResult.status === 'success'" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-400/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 animate-fade-in">
                                <div>
                                    <div class="text-[11px] uppercase tracking-wider text-emerald-300 font-bold">Saldo Kas Xendit Siap Transfer (Disbursement)</div>
                                    <div class="text-2xl font-black text-white font-mono mt-0.5">Rp {{ formatRupiah(testResult.balance) }}</div>
                                    <div class="text-[10px] text-emerald-200/80 mt-0.5">Environment: {{ testResult.environment }}</div>
                                </div>
                                <div class="text-right">
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-500/20 text-emerald-300 font-bold text-xs border border-emerald-400/30">
                                        ✓ API Terkoneksi
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. PENGATURAN SISTEM GAJI & CUTOFF -->
                <div class="bg-white shadow-sm rounded-3xl border border-slate-100 p-6 sm:p-7">
                    <h2 class="text-lg font-extrabold text-slate-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Siklus Periode &amp; Komponen Gaji</span>
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <span>Tanggal Cutoff (Periode Gaji)</span>
                            </label>
                            <input type="number" v-model="form.payroll_cutoff_date" min="1" max="28" class="block w-full border-slate-200 rounded-xl shadow-xs text-xs font-bold focus:ring-indigo-500 focus:border-indigo-500" required>
                            <p class="text-[11px] text-slate-500 mt-1">Tanggal penutupan perhitungan gaji (misal: 25 = cutoff tgl 26 bulan lalu s/d tgl 25 bulan ini)</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <span>Tanggal Gajian Bulanan</span>
                            </label>
                            <input type="number" v-model="form.payroll_payment_date" min="1" max="28" class="block w-full border-slate-200 rounded-xl shadow-xs text-xs font-bold focus:ring-indigo-500 focus:border-indigo-500" required>
                            <p class="text-[11px] text-slate-500 mt-1">Tanggal transfer gaji rutin ke staf setiap bulan</p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Komponen Gaji Otomatis</label>
                        <p class="text-xs text-slate-500 mb-3">Pilih komponen yang otomatis dihitung saat generate slip gaji</p>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <!-- Insentif -->
                            <label class="flex items-center p-3.5 border border-slate-200 rounded-2xl cursor-pointer hover:bg-slate-50 transition-colors">
                                <div class="relative flex items-center">
                                    <input type="checkbox" value="insentif" v-model="form.payroll_components" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </div>
                                <span class="ml-3 text-xs font-bold text-slate-900">Insentif</span>
                            </label>
                            <!-- Potongan -->
                            <label class="flex items-center p-3.5 border border-slate-200 rounded-2xl cursor-pointer hover:bg-slate-50 transition-colors">
                                <div class="relative flex items-center">
                                    <input type="checkbox" value="potongan" v-model="form.payroll_components" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </div>
                                <span class="ml-3 text-xs font-bold text-slate-900">Potongan</span>
                            </label>
                            <!-- Lembur -->
                            <label class="flex items-center p-3.5 border border-slate-200 rounded-2xl cursor-pointer hover:bg-slate-50 transition-colors">
                                <div class="relative flex items-center">
                                    <input type="checkbox" value="lembur" v-model="form.payroll_components" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </div>
                                <span class="ml-3 text-xs font-bold text-slate-900">Lembur</span>
                            </label>
                            <!-- Tunjangan -->
                            <label class="flex items-center p-3.5 border border-slate-200 rounded-2xl cursor-pointer hover:bg-slate-50 transition-colors">
                                <div class="relative flex items-center">
                                    <input type="checkbox" value="tunjangan" v-model="form.payroll_components" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </div>
                                <span class="ml-3 text-xs font-bold text-slate-900">Tunjangan</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Catatan / Catatan Kaki Slip Gaji</label>
                        <textarea v-model="form.payroll_note" rows="3" class="block w-full border-slate-200 rounded-xl shadow-xs text-xs focus:ring-indigo-500 focus:border-indigo-500" placeholder="Pesan / catatan yang akan dicetak di bagian bawah slip gaji..."></textarea>
                    </div>
                </div>

                <!-- 3. PENGATURAN PENCAIRAN INSENTIF -->
                <div class="bg-white shadow-sm rounded-3xl border border-slate-100 p-6 sm:p-7">
                    <h2 class="text-lg font-extrabold text-slate-900 mb-1 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span>Pencairan Bonus &amp; Insentif</span>
                    </h2>
                    <p class="text-xs text-slate-500 mb-4">Atur frekuensi pencairan insentif teknisi, sales &amp; staf</p>

                    <div class="mb-4">
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Mode Pencairan</label>
                        <select v-model="form.payroll_incentive_mode" class="block w-full border-slate-200 rounded-xl shadow-xs text-xs font-bold focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="Bulanan (sekali sebulan)">Bulanan (sekali sebulan bersama gaji)</option>
                            <option value="Mingguan (sekali seminggu)">Mingguan (setiap akhir pekan)</option>
                            <option value="Harian (langsung cair)">Harian (langsung cair per transaksi)</option>
                        </select>
                    </div>

                    <div class="bg-indigo-50/70 border border-indigo-100 rounded-2xl p-4 flex gap-3">
                        <span class="text-indigo-600 text-lg">💡</span>
                        <div>
                            <h4 class="text-xs font-extrabold text-indigo-950">Informasi Alur Pencairan:</h4>
                            <p class="text-xs text-indigo-800 mt-0.5">
                                <template v-if="form.payroll_incentive_mode.includes('Bulanan')">
                                    Insentif diakumulasi dan dicairkan bersamaan dengan gaji pokok pada tanggal {{ form.payroll_payment_date }}.
                                </template>
                                <template v-else-if="form.payroll_incentive_mode.includes('Mingguan')">
                                    Insentif diakumulasi dan dapat dicairkan setiap hari Jumat / akhir pekan.
                                </template>
                                <template v-else>
                                    Insentif dapat langsung dicairkan pada hari yang sama setelah pekerjaan/survei selesai.
                                </template>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 4. RINGKASAN PENGATURAN -->
                <div class="bg-slate-50 rounded-3xl border border-slate-200/80 p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h4 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider flex items-center gap-2 mb-1.5">
                            <span>📋</span>
                            <span>Ringkasan Pengaturan</span>
                        </h4>
                        <ul class="text-xs text-slate-600 space-y-1 ml-5 list-disc">
                            <li>Xendit Auto Transfer: <strong :class="form.xendit_disbursement_enabled ? 'text-emerald-700' : 'text-slate-700'">{{ form.xendit_disbursement_enabled ? 'Aktif' : 'Nonaktif (Pencatatan Manual)' }}</strong></li>
                            <li>Cutoff Gaji: Setiap tanggal <strong class="text-slate-900">{{ form.payroll_cutoff_date }}</strong> | Gajian: Tanggal <strong class="text-slate-900">{{ form.payroll_payment_date }}</strong></li>
                            <li>Komponen: <span class="capitalize text-slate-900 font-semibold">{{ form.payroll_components.join(', ') || 'Tidak ada' }}</span></li>
                        </ul>
                    </div>

                    <div class="flex justify-end">
                        <button 
                            type="submit" 
                            :disabled="form.processing" 
                            class="bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500 inline-flex justify-center items-center py-2.5 px-6 border border-transparent shadow-sm text-xs font-extrabold rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all disabled:opacity-50 cursor-pointer"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Simpan Semua Pengaturan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    payroll_cutoff_date: [String, Number],
    payroll_payment_date: [String, Number],
    payroll_components: Array,
    payroll_note: String,
    payroll_incentive_mode: String,
    xendit_disbursement_enabled: Boolean,
    xendit_secret_key: String,
    xendit_webhook_token: String,
    xendit_auto_disburse_on_approve: Boolean,
});

const form = useForm({
    payroll_cutoff_date: props.payroll_cutoff_date || 1,
    payroll_payment_date: props.payroll_payment_date || 12,
    payroll_components: props.payroll_components || ['insentif', 'potongan'],
    payroll_note: props.payroll_note || '',
    payroll_incentive_mode: props.payroll_incentive_mode || 'Bulanan (sekali sebulan)',
    xendit_disbursement_enabled: Boolean(props.xendit_disbursement_enabled),
    xendit_secret_key: props.xendit_secret_key || '',
    xendit_webhook_token: props.xendit_webhook_token || '',
    xendit_auto_disburse_on_approve: Boolean(props.xendit_auto_disburse_on_approve),
});

const showApiKey = ref(false);
const isTesting = ref(false);
const testResult = ref(null);

const testXendit = async () => {
    isTesting.value = true;
    testResult.value = null;
    try {
        const response = await axios.post(route('settings.payroll.test-xendit'), {
            api_key: form.xendit_secret_key
        });
        testResult.value = response.data;
    } catch (e) {
        testResult.value = {
            status: 'error',
            message: e.response?.data?.message || e.message || 'Gagal menghubungi server.'
        };
    } finally {
        isTesting.value = false;
    }
};

const copyWebhookUrl = () => {
    const origin = window.location.origin;
    const url = `${origin}/api/webhooks/xendit/disbursement`;
    navigator.clipboard.writeText(url);
    alert(`Webhook URL berhasil disalin:\n${url}\n\nDaftarkan URL ini di Dashboard Xendit: Settings -> Callbacks -> Disbursements.`);
};

const formatRupiah = (num) => {
    return new Intl.NumberFormat('id-ID').format(num || 0);
};

const submit = () => {
    form.post(route('settings.payroll.update'), {
        preserveScroll: true,
    });
};
</script>
