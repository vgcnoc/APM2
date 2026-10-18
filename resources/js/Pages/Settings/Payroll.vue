<template>
    <AppLayout title="Pengaturan Gaji & Auto Transfer">
        <div class="max-w-[1600px] w-full mx-auto space-y-6 font-sans">
            <!-- Flash message banner -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-2">
                <span>✅</span>
                <span>{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash?.error" class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-2">
                <span>⚠️</span>
                <span>{{ $page.props.flash.error }}</span>
            </div>

            <!-- Header Title -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
                        <span class="p-2 bg-indigo-50 text-indigo-600 rounded-2xl text-xl">💼</span>
                        <span>Pengaturan Sistem Gaji &amp; Auto Transfer</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">Kelola preferensi siklus gaji, memo slip gaji, serta integrasi API Flip for Business &amp; Xendit untuk auto transfer karyawan</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- 1. PILIH PROVIDER AUTO TRANSFER UTAMA -->
                <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-sm border border-slate-200/80">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-5 border-b border-slate-100">
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                                <span>🚀</span>
                                <span>Provider Auto Transfer Penggajian (Disbursement)</span>
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">Pilih penyedia layanan transfer otomatis yang aktif digunakan saat tombol "Cairkan Gaji" ditekan</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold px-3 py-1 rounded-full uppercase" :class="getActiveProviderBadgeClass">
                                {{ getActiveProviderLabel }}
                            </span>
                        </div>
                    </div>

                    <!-- Pilihan Radio Provider -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-5">
                        <!-- 1. Flip for Business -->
                        <div 
                            @click="selectProvider('flip')" 
                            class="relative p-5 rounded-2xl border-2 transition-all cursor-pointer flex flex-col justify-between"
                            :class="form.active_disbursement_provider === 'flip' ? 'border-amber-500 bg-amber-50/40 shadow-sm ring-2 ring-amber-500/20' : 'border-slate-200 hover:border-slate-300 bg-white'"
                        >
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-2xl">🟠</span>
                                    <input 
                                        type="radio" 
                                        value="flip" 
                                        :checked="form.active_disbursement_provider === 'flip'" 
                                        @change="selectProvider('flip')"
                                        class="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 cursor-pointer"
                                    />
                                </div>
                                <h3 class="text-sm font-black text-slate-900">Flip for Business (Big Flip)</h3>
                                <p class="text-[11px] text-amber-800 font-semibold mt-1">⭐ Biaya Hemat: Rp 1.500 – Rp 3.000 / tx</p>
                                <p class="text-[11px] text-slate-500 mt-1">Bisa didaftarkan oleh <strong>Perorangan / UMKM</strong> tanpa harus memiliki badan hukum PT.</p>
                            </div>
                            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px]">
                                <span class="text-slate-400">Status Modul:</span>
                                <span :class="form.flip_disbursement_enabled ? 'text-emerald-700 font-bold' : 'text-slate-500 font-medium'">
                                    {{ form.flip_disbursement_enabled ? '● Aktif' : '○ Nonaktif' }}
                                </span>
                            </div>
                        </div>

                        <!-- 2. Xendit -->
                        <div 
                            @click="selectProvider('xendit')" 
                            class="relative p-5 rounded-2xl border-2 transition-all cursor-pointer flex flex-col justify-between"
                            :class="form.active_disbursement_provider === 'xendit' ? 'border-indigo-500 bg-indigo-50/40 shadow-sm ring-2 ring-indigo-500/20' : 'border-slate-200 hover:border-slate-300 bg-white'"
                        >
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-2xl">⚡</span>
                                    <input 
                                        type="radio" 
                                        value="xendit" 
                                        :checked="form.active_disbursement_provider === 'xendit'" 
                                        @change="selectProvider('xendit')"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 cursor-pointer"
                                    />
                                </div>
                                <h3 class="text-sm font-black text-slate-900">Xendit (XenDisburse)</h3>
                                <p class="text-[11px] text-indigo-800 font-semibold mt-1">Biaya: Rp 5.000 / tx</p>
                                <p class="text-[11px] text-slate-500 mt-1">Memerlukan badan usaha resmi (PT, CV, atau PT Perorangan).</p>
                            </div>
                            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px]">
                                <span class="text-slate-400">Status Modul:</span>
                                <span :class="form.xendit_disbursement_enabled ? 'text-emerald-700 font-bold' : 'text-slate-500 font-medium'">
                                    {{ form.xendit_disbursement_enabled ? '● Aktif' : '○ Nonaktif' }}
                                </span>
                            </div>
                        </div>

                        <!-- 3. Manual / Nonaktif -->
                        <div 
                            @click="selectProvider('none')" 
                            class="relative p-5 rounded-2xl border-2 transition-all cursor-pointer flex flex-col justify-between"
                            :class="form.active_disbursement_provider === 'none' ? 'border-slate-700 bg-slate-50 shadow-sm ring-2 ring-slate-500/20' : 'border-slate-200 hover:border-slate-300 bg-white'"
                        >
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-2xl">💵</span>
                                    <input 
                                        type="radio" 
                                        value="none" 
                                        :checked="form.active_disbursement_provider === 'none'" 
                                        @change="selectProvider('none')"
                                        class="h-4 w-4 text-slate-700 focus:ring-slate-500 border-gray-300 cursor-pointer"
                                    />
                                </div>
                                <h3 class="text-sm font-black text-slate-900">Nonaktif (Pencatatan Manual)</h3>
                                <p class="text-[11px] text-slate-600 font-semibold mt-1">Tanpa Biaya Gateway (Rp 0)</p>
                                <p class="text-[11px] text-slate-500 mt-1">Gaji ditransfer manual via M-Banking kantor atau tunai (cash). Sistem hanya mencatat pembukuan.</p>
                            </div>
                            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px]">
                                <span class="text-slate-400">Status:</span>
                                <span class="text-slate-700 font-bold">Manual Cash/Bank</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Switcher Konfigurasi API -->
                    <div class="flex items-center gap-2 mt-6 pt-5 border-t border-slate-100">
                        <span class="text-xs font-bold text-slate-500 mr-1">Konfigurasi API:</span>
                        <button 
                            type="button" 
                            @click="activeTab = 'flip'" 
                            class="px-4 py-2 rounded-xl text-xs font-extrabold flex items-center gap-1.5 transition-all cursor-pointer"
                            :class="activeTab === 'flip' ? 'bg-amber-500 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                        >
                            <span>🟠</span>
                            <span>Konfigurasi Flip for Business</span>
                        </button>
                        <button 
                            type="button" 
                            @click="activeTab = 'xendit'" 
                            class="px-4 py-2 rounded-xl text-xs font-extrabold flex items-center gap-1.5 transition-all cursor-pointer"
                            :class="activeTab === 'xendit' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                        >
                            <span>⚡</span>
                            <span>Konfigurasi Xendit</span>
                        </button>
                    </div>
                </div>

                <!-- 2A. FORM KONFIGURASI FLIP FOR BUSINESS -->
                <div v-show="activeTab === 'flip'" class="bg-gradient-to-br from-slate-900 via-amber-950/80 to-slate-900 rounded-3xl p-6 sm:p-7 text-white shadow-xl relative overflow-hidden border border-amber-900/50 animate-fade-in">
                    <div class="absolute -right-12 -top-12 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="relative z-10">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-white/10">
                            <div class="flex items-center gap-3.5">
                                <div class="w-12 h-12 rounded-2xl bg-amber-500/20 border border-amber-400/30 flex items-center justify-center text-2xl shadow-inner">
                                    🟠
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h2 class="text-lg font-black tracking-tight text-white">Integrasi Flip for Business (Big Flip)</h2>
                                        <span :class="form.flip_disbursement_enabled ? 'bg-emerald-500/20 text-emerald-300 border-emerald-400/30' : 'bg-white/10 text-slate-400 border-white/10'" class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase border">
                                            {{ form.flip_disbursement_enabled ? '● AKTIF' : '○ NONAKTIF' }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-amber-200/80 mt-0.5">Transfer gaji &amp; insentif teknisi langsung ke 100+ bank &amp; e-wallet (DANA, GoPay, OVO, ShopeePay)</p>
                                </div>
                            </div>

                            <!-- Toggle Switch Enable Flip -->
                            <label class="relative inline-flex items-center cursor-pointer self-start sm:self-auto">
                                <input 
                                    type="checkbox" 
                                    :checked="form.flip_disbursement_enabled" 
                                    @change="toggleFlip($event.target.checked)"
                                    class="sr-only peer"
                                >
                                <div class="w-14 h-7 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-amber-500 shadow-inner"></div>
                                <span class="ml-3 text-xs font-bold text-slate-200">
                                    {{ form.flip_disbursement_enabled ? 'Fitur Aktif' : 'Aktifkan' }}
                                </span>
                            </label>
                        </div>

                        <!-- Form Input Flip -->
                        <div class="mt-6 space-y-5">
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                                <!-- Secret API Key -->
                                <div class="lg:col-span-2">
                                    <label class="block text-xs font-bold text-amber-200 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                        <span>Secret API Key Flip *</span>
                                        <span class="text-[10px] font-medium text-slate-400">Didapat dari Dashboard Flip for Business</span>
                                    </label>
                                    <div class="relative">
                                        <input 
                                            :type="showFlipKey ? 'text' : 'password'" 
                                            v-model="form.flip_secret_key" 
                                            placeholder="Contoh: secret_api_key_flip_live_..." 
                                            class="w-full bg-slate-800/80 border border-slate-700 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 text-white rounded-xl px-4 py-2.5 text-xs font-mono shadow-sm transition-all"
                                        />
                                        <button 
                                            type="button" 
                                            @click="showFlipKey = !showFlipKey" 
                                            class="absolute right-3 top-2.5 text-slate-400 hover:text-white text-xs font-bold transition-colors"
                                        >
                                            {{ showFlipKey ? 'Sembunyikan' : 'Lihat' }}
                                        </button>
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-1">Dapatkan di menu <strong>Integrasi API &rarr; Secret Key</strong> di Dashboard Flip for Business Anda.</p>
                                </div>

                                <!-- Environment Sandbox / Production -->
                                <div>
                                    <label class="block text-xs font-bold text-amber-200 uppercase tracking-wider mb-1.5">
                                        Environment *
                                    </label>
                                    <select 
                                        v-model="form.flip_environment" 
                                        class="w-full bg-slate-800/80 border border-slate-700 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 text-white rounded-xl px-4 py-2.5 text-xs font-semibold shadow-sm transition-all"
                                    >
                                        <option value="sandbox">Development (Sandbox / Uji Coba)</option>
                                        <option value="production">Production (Live Transaksi Nyata)</option>
                                    </select>
                                    <p class="text-[11px] text-slate-400 mt-1">Pilih <em>Production</em> jika akun Flip sudah terverifikasi.</p>
                                </div>

                                <!-- Validation Token (Webhook) -->
                                <div class="lg:col-span-3">
                                    <label class="block text-xs font-bold text-amber-200 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                        <span>Validation Token (Webhook Verification)</span>
                                        <span class="text-[10px] text-slate-400">(Opsional untuk verifikasi callback)</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        v-model="form.flip_validation_token" 
                                        placeholder="Contoh: token_validasi_webhook_flip..." 
                                        class="w-full bg-slate-800/80 border border-slate-700 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 text-white rounded-xl px-4 py-2.5 text-xs font-mono shadow-sm transition-all"
                                    />
                                    <p class="text-[11px] text-slate-400 mt-1">Digunakan untuk memastikan update status transfer benar-benar dikirim dari server Flip.</p>
                                </div>
                            </div>

                            <!-- Opsi Otomatisasi Pencairan Flip -->
                            <div class="pt-2">
                                <label class="flex items-start gap-3 p-3.5 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors cursor-pointer">
                                    <input 
                                        type="checkbox" 
                                        v-model="form.flip_auto_disburse_on_approve" 
                                        class="rounded border-slate-700 bg-slate-800 text-amber-500 focus:ring-amber-400 mt-0.5"
                                    />
                                    <div>
                                        <span class="text-xs font-extrabold text-white block">Auto Disburse Flip Otomatis Saat Tombol "Cairkan" Ditekan</span>
                                        <span class="text-[11px] text-slate-300 block mt-0.5">Jika dicentang dan Flip aktif, menekan tombol Cairkan / Cairkan Semua di menu Payroll akan langsung memicu transfer dana via Flip for Business.</span>
                                    </div>
                                </label>
                            </div>

                            <!-- Action Bar: Test Connection & Webhook Info Flip -->
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 pt-3 border-t border-white/10">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <button 
                                        type="button" 
                                        @click="testFlip" 
                                        :disabled="isTestingFlip || !form.flip_secret_key" 
                                        class="px-4 py-2 rounded-xl text-xs font-extrabold bg-amber-500 hover:bg-amber-600 active:scale-95 text-slate-950 transition-all shadow-md flex items-center gap-2 disabled:opacity-50 cursor-pointer"
                                    >
                                        <svg v-if="isTestingFlip" class="animate-spin h-3.5 w-3.5 text-slate-950" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        <span v-else>🟠</span>
                                        <span>{{ isTestingFlip ? 'Menguji & Mengambil Saldo...' : 'Tes Koneksi & Cek Saldo Flip' }}</span>
                                    </button>

                                    <button 
                                        type="button"
                                        @click="copyFlipWebhookUrl"
                                        class="px-3 py-2 rounded-xl text-xs font-semibold bg-white/10 hover:bg-white/15 text-amber-200 border border-white/10 transition-colors flex items-center gap-1.5 cursor-pointer"
                                        title="Salin Webhook URL untuk didaftarkan di Flip"
                                    >
                                        <span>📋</span>
                                        <span>Salin Webhook URL Flip</span>
                                    </button>
                                </div>

                                <div v-if="flipTestResult" class="text-xs">
                                    <span v-if="flipTestResult.status === 'success'" class="text-emerald-400 font-bold flex items-center gap-1.5">
                                        <span>✓</span>
                                        <span>{{ flipTestResult.message }}</span>
                                    </span>
                                    <span v-else class="text-rose-400 font-bold flex items-center gap-1.5">
                                        <span>⚠️</span>
                                        <span>{{ flipTestResult.message }}</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Box Saldo Kas Flip -->
                            <div v-if="flipTestResult && flipTestResult.status === 'success'" class="p-4 rounded-2xl bg-amber-500/10 border border-amber-400/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 animate-fade-in">
                                <div>
                                    <div class="text-[11px] uppercase tracking-wider text-amber-300 font-bold">Saldo Kas Flip for Business Siap Transfer</div>
                                    <div class="text-2xl font-black text-white font-mono mt-0.5">Rp {{ formatRupiah(flipTestResult.balance) }}</div>
                                    <div class="text-[10px] text-amber-200/80 mt-0.5">Environment: {{ flipTestResult.environment }}</div>
                                </div>
                                <div class="text-right">
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-500/20 text-emerald-300 font-bold text-xs border border-emerald-400/30">
                                        ✓ API Flip Terkoneksi
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2B. FORM KONFIGURASI XENDIT -->
                <div v-show="activeTab === 'xendit'" class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-7 text-white shadow-xl relative overflow-hidden border border-indigo-900/50 animate-fade-in">
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
                                <input 
                                    type="checkbox" 
                                    :checked="form.xendit_disbursement_enabled" 
                                    @change="toggleXendit($event.target.checked)"
                                    class="sr-only peer"
                                >
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
                                            :type="showXenditKey ? 'text' : 'password'" 
                                            v-model="form.xendit_secret_key" 
                                            placeholder="Contoh: xnd_development_abcdef123456..." 
                                            class="w-full bg-slate-800/80 border border-slate-700 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/20 text-white rounded-xl px-4 py-2.5 text-xs font-mono shadow-sm transition-all"
                                        />
                                        <button 
                                            type="button" 
                                            @click="showXenditKey = !showXenditKey" 
                                            class="absolute right-3 top-2.5 text-slate-400 hover:text-white text-xs font-bold transition-colors"
                                        >
                                            {{ showXenditKey ? 'Sembunyikan' : 'Lihat' }}
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

                            <!-- Opsi Otomatisasi Pencairan Xendit -->
                            <div class="pt-2">
                                <label class="flex items-start gap-3 p-3.5 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors cursor-pointer">
                                    <input 
                                        type="checkbox" 
                                        v-model="form.xendit_auto_disburse_on_approve" 
                                        class="rounded border-slate-700 bg-slate-800 text-indigo-500 focus:ring-indigo-400 mt-0.5"
                                    />
                                    <div>
                                        <span class="text-xs font-extrabold text-white block">Auto Disburse Xendit Otomatis Saat Tombol "Cairkan" Ditekan</span>
                                        <span class="text-[11px] text-slate-300 block mt-0.5">Jika dicentang dan Xendit aktif, menekan tombol Cairkan / Cairkan Semua di menu Payroll akan otomatis memicu transfer dana via Xendit.</span>
                                    </div>
                                </label>
                            </div>

                            <!-- Action Bar: Test Connection & Webhook Info Xendit -->
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 pt-3 border-t border-white/10">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <button 
                                        type="button" 
                                        @click="testXendit" 
                                        :disabled="isTestingXendit || !form.xendit_secret_key" 
                                        class="px-4 py-2 rounded-xl text-xs font-extrabold bg-indigo-500 hover:bg-indigo-600 active:scale-95 text-white transition-all shadow-md flex items-center gap-2 disabled:opacity-50 cursor-pointer"
                                    >
                                        <svg v-if="isTestingXendit" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        <span v-else>⚡</span>
                                        <span>{{ isTestingXendit ? 'Menguji & Mengambil Saldo...' : 'Tes Koneksi & Cek Saldo Xendit' }}</span>
                                    </button>

                                    <button 
                                        type="button"
                                        @click="copyXenditWebhookUrl"
                                        class="px-3 py-2 rounded-xl text-xs font-semibold bg-white/10 hover:bg-white/15 text-indigo-200 border border-white/10 transition-colors flex items-center gap-1.5 cursor-pointer"
                                        title="Salin Webhook URL untuk didaftarkan di Xendit"
                                    >
                                        <span>📋</span>
                                        <span>Salin Webhook URL Xendit</span>
                                    </button>
                                </div>

                                <div v-if="xenditTestResult" class="text-xs">
                                    <span v-if="xenditTestResult.status === 'success'" class="text-emerald-400 font-bold flex items-center gap-1.5">
                                        <span>✓</span>
                                        <span>{{ xenditTestResult.message }}</span>
                                    </span>
                                    <span v-else class="text-rose-400 font-bold flex items-center gap-1.5">
                                        <span>⚠️</span>
                                        <span>{{ xenditTestResult.message }}</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Box Saldo Kas & Panduan Webhook -->
                            <div v-if="xenditTestResult && xenditTestResult.status === 'success'" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-400/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 animate-fade-in">
                                <div>
                                    <div class="text-[11px] uppercase tracking-wider text-emerald-300 font-bold">Saldo Kas Xendit Siap Transfer (Disbursement)</div>
                                    <div class="text-2xl font-black text-white font-mono mt-0.5">Rp {{ formatRupiah(xenditTestResult.balance) }}</div>
                                    <div class="text-[10px] text-emerald-200/80 mt-0.5">Environment: {{ xenditTestResult.environment }}</div>
                                </div>
                                <div class="text-right">
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-500/20 text-emerald-300 font-bold text-xs border border-emerald-400/30">
                                        ✓ API Xendit Terkoneksi
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. PENGATURAN SISTEM GAJI, CUTOFF & MEMO -->
                <div class="bg-white shadow-sm rounded-3xl border border-slate-100 p-6 sm:p-7">
                    <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2 mb-4">
                        <span>📅</span>
                        <span>Siklus Periode &amp; Cutoff Penggajian</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Tanggal Cutoff -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                Tanggal Cutoff Gaji (1 - 28) *
                            </label>
                            <input 
                                type="number" 
                                min="1" 
                                max="28" 
                                v-model="form.payroll_cutoff_date" 
                                class="w-full bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 text-slate-800 rounded-xl px-4 py-2.5 text-sm font-semibold transition-all"
                                required
                            />
                            <p class="text-[11px] text-slate-400 mt-1.5 leading-relaxed">
                                Batas akhir perhitungan absensi, lembur, dan insentif bulanan. Contoh: Jika diset tanggal <strong>1</strong>, data dihitung dari tgl 2 bulan lalu s/d tgl 1 bulan ini.
                            </p>
                        </div>

                        <!-- Tanggal Gajian (Payment Date) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                Tanggal Pembayaran Gaji / Gajian (1 - 28) *
                            </label>
                            <input 
                                type="number" 
                                min="1" 
                                max="28" 
                                v-model="form.payroll_payment_date" 
                                class="w-full bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 text-slate-800 rounded-xl px-4 py-2.5 text-sm font-semibold transition-all"
                                required
                            />
                            <p class="text-[11px] text-slate-400 mt-1.5 leading-relaxed">
                                Jadwal resmi transfer/pembagian gaji kepada karyawan setiap bulannya.
                            </p>
                        </div>
                    </div>

                    <!-- Mode Pencairan Insentif -->
                    <div class="mt-6 pt-6 border-t border-slate-100">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Mode Pencairan Insentif Staf Lapangan *
                        </label>
                        <select 
                            v-model="form.payroll_incentive_mode" 
                            class="w-full bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 text-slate-800 rounded-xl px-4 py-2.5 text-sm font-semibold transition-all"
                        >
                            <option value="Bulanan (sekali sebulan)">Bulanan (sekali sebulan bersamaan dengan Gaji Pokok)</option>
                            <option value="Mingguan (setiap akhir pekan)">Mingguan (setiap akhir pekan)</option>
                            <option value="Harian (langsung saat selesai pekerjaan)">Harian (langsung saat selesai pekerjaan)</option>
                        </select>
                    </div>

                    <!-- Catatan / Memo Default Slip Gaji -->
                    <div class="mt-6 pt-6 border-t border-slate-100">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center justify-between">
                            <span>Catatan / Memo Default Slip Gaji (Opsional)</span>
                            <span class="text-[10px] text-slate-400 font-normal">Tampil di bagian bawah slip gaji karyawan</span>
                        </label>
                        <textarea 
                            v-model="form.payroll_note" 
                            rows="2" 
                            placeholder="Contoh: Terima kasih atas dedikasi dan kerja keras Anda."
                            class="w-full bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 text-slate-800 rounded-xl px-4 py-2.5 text-xs font-medium transition-all"
                        ></textarea>
                        <p class="text-[11px] text-slate-400 mt-1.5 leading-relaxed">
                            Pesan atau catatan standar yang dicetak pada slip gaji fisik maupun slip digital setiap periode penggajian.
                        </p>
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
                            <li>Provider Aktif: <strong class="text-slate-900">{{ getActiveProviderLabel }}</strong></li>
                            <li>Flip for Business: <strong :class="form.flip_disbursement_enabled ? 'text-amber-700' : 'text-slate-500'">{{ form.flip_disbursement_enabled ? 'Aktif (' + form.flip_environment + ')' : 'Nonaktif' }}</strong></li>
                            <li>Xendit: <strong :class="form.xendit_disbursement_enabled ? 'text-indigo-700' : 'text-slate-500'">{{ form.xendit_disbursement_enabled ? 'Aktif' : 'Nonaktif' }}</strong></li>
                            <li>Cutoff Gaji: Tanggal <strong class="text-slate-900">{{ form.payroll_cutoff_date }}</strong> | Gajian: Tanggal <strong class="text-slate-900">{{ form.payroll_payment_date }}</strong></li>
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
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    payroll_cutoff_date: [String, Number],
    payroll_payment_date: [String, Number],
    payroll_components: Array,
    payroll_note: String,
    payroll_incentive_mode: String,
    active_disbursement_provider: String,
    // Flip
    flip_disbursement_enabled: Boolean,
    flip_secret_key: String,
    flip_validation_token: String,
    flip_environment: String,
    flip_auto_disburse_on_approve: Boolean,
    // Xendit
    xendit_disbursement_enabled: Boolean,
    xendit_secret_key: String,
    xendit_webhook_token: String,
    xendit_auto_disburse_on_approve: Boolean,
});

const activeTab = ref(props.active_disbursement_provider === 'xendit' ? 'xendit' : 'flip');

const form = useForm({
    payroll_cutoff_date: Number(props.payroll_cutoff_date) || 1,
    payroll_payment_date: Number(props.payroll_payment_date) || 12,
    payroll_components: props.payroll_components || ['insentif', 'potongan'],
    payroll_note: props.payroll_note || '',
    payroll_incentive_mode: props.payroll_incentive_mode || 'Bulanan (sekali sebulan)',
    active_disbursement_provider: props.active_disbursement_provider || (props.flip_disbursement_enabled ? 'flip' : (props.xendit_disbursement_enabled ? 'xendit' : 'none')),
    // Flip
    flip_disbursement_enabled: props.active_disbursement_provider === 'flip' ? true : Boolean(props.flip_disbursement_enabled),
    flip_secret_key: props.flip_secret_key || '',
    flip_validation_token: props.flip_validation_token || '',
    flip_environment: props.flip_environment || 'sandbox',
    flip_auto_disburse_on_approve: Boolean(props.flip_auto_disburse_on_approve),
    // Xendit
    xendit_disbursement_enabled: props.active_disbursement_provider === 'xendit' ? true : Boolean(props.xendit_disbursement_enabled),
    xendit_secret_key: props.xendit_secret_key || '',
    xendit_webhook_token: props.xendit_webhook_token || '',
    xendit_auto_disburse_on_approve: Boolean(props.xendit_auto_disburse_on_approve),
});

// Sinkronisasi Form jika props berubah dari backend
watch(() => props.active_disbursement_provider, (val) => {
    form.active_disbursement_provider = val || 'none';
});
watch(() => props.flip_disbursement_enabled, (val) => {
    form.flip_disbursement_enabled = Boolean(val);
});
watch(() => props.xendit_disbursement_enabled, (val) => {
    form.xendit_disbursement_enabled = Boolean(val);
});
watch(() => props.payroll_note, (val) => {
    form.payroll_note = val || '';
});

// Logika sinkronisasi pemilihan Provider & Modul
const selectProvider = (provider) => {
    form.active_disbursement_provider = provider;
    if (provider === 'flip') {
        form.flip_disbursement_enabled = true;
        activeTab.value = 'flip';
    } else if (provider === 'xendit') {
        form.xendit_disbursement_enabled = true;
        activeTab.value = 'xendit';
    }
};

const toggleFlip = (checked) => {
    form.flip_disbursement_enabled = checked;
    if (checked) {
        form.active_disbursement_provider = 'flip';
        activeTab.value = 'flip';
    } else {
        if (form.active_disbursement_provider === 'flip') {
            form.active_disbursement_provider = 'none';
        }
    }
};

const toggleXendit = (checked) => {
    form.xendit_disbursement_enabled = checked;
    if (checked) {
        form.active_disbursement_provider = 'xendit';
        activeTab.value = 'xendit';
    } else {
        if (form.active_disbursement_provider === 'xendit') {
            form.active_disbursement_provider = 'none';
        }
    }
};

const showFlipKey = ref(false);
const isTestingFlip = ref(false);
const flipTestResult = ref(null);

const showXenditKey = ref(false);
const isTestingXendit = ref(false);
const xenditTestResult = ref(null);

const getActiveProviderLabel = computed(() => {
    if (form.active_disbursement_provider === 'flip') return '🟠 Flip for Business Aktif';
    if (form.active_disbursement_provider === 'xendit') return '⚡ Xendit Aktif';
    return 'Manual / Cash (Nonaktif)';
});

const getActiveProviderBadgeClass = computed(() => {
    if (form.active_disbursement_provider === 'flip') return 'bg-amber-100 text-amber-800 border border-amber-300';
    if (form.active_disbursement_provider === 'xendit') return 'bg-indigo-100 text-indigo-800 border border-indigo-300';
    return 'bg-slate-100 text-slate-700 border border-slate-300';
});

const testFlip = async () => {
    isTestingFlip.value = true;
    flipTestResult.value = null;
    try {
        const response = await axios.post(route('settings.payroll.test-flip'), {
            secret_key: form.flip_secret_key,
            environment: form.flip_environment,
        });
        flipTestResult.value = response.data;
        if (response.data?.status === 'success') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Koneksi Flip Berhasil!',
                text: `Saldo kas siap transfer: Rp ${formatRupiah(response.data.balance)}`,
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Tes Koneksi Flip Gagal',
                text: response.data?.message || 'Gagal verifikasi secret key Flip.',
                confirmButtonColor: '#d97706'
            });
        }
    } catch (e) {
        const msg = e.response?.data?.message || e.message || 'Gagal menghubungi server Flip.';
        flipTestResult.value = {
            status: 'error',
            message: msg
        };
        Swal.fire({
            icon: 'error',
            title: 'Kesalahan Jaringan / Server Flip',
            text: msg,
            confirmButtonColor: '#d97706'
        });
    } finally {
        isTestingFlip.value = false;
    }
};

const copyFlipWebhookUrl = () => {
    const origin = window.location.origin;
    const url = `${origin}/api/webhooks/flip/disbursement`;
    navigator.clipboard.writeText(url);
    Swal.fire({
        icon: 'success',
        title: 'Webhook URL Flip Disalin',
        html: `<p class="text-xs text-slate-600 mb-2">URL Webhook berhasil disalin ke clipboard:</p><code class="text-xs bg-slate-100 p-2 rounded-xl block text-amber-700 font-mono break-all mb-3">${url}</code><p class="text-[11px] text-slate-500">Daftarkan URL ini di <strong>Dashboard Flip for Business &rarr; Pengaturan &rarr; Webhook &rarr; URL Callback</strong>.</p>`,
        confirmButtonColor: '#d97706',
        confirmButtonText: 'Mengerti'
    });
};

const testXendit = async () => {
    isTestingXendit.value = true;
    xenditTestResult.value = null;
    try {
        const response = await axios.post(route('settings.payroll.test-xendit'), {
            api_key: form.xendit_secret_key
        });
        xenditTestResult.value = response.data;
        if (response.data?.status === 'success') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Koneksi Xendit Berhasil!',
                text: `Saldo kas siap transfer: Rp ${formatRupiah(response.data.balance)}`,
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Tes Koneksi Xendit Gagal',
                text: response.data?.message || 'Gagal verifikasi API key Xendit.',
                confirmButtonColor: '#4f46e5'
            });
        }
    } catch (e) {
        const msg = e.response?.data?.message || e.message || 'Gagal menghubungi server Xendit.';
        xenditTestResult.value = {
            status: 'error',
            message: msg
        };
        Swal.fire({
            icon: 'error',
            title: 'Kesalahan Jaringan / Server Xendit',
            text: msg,
            confirmButtonColor: '#4f46e5'
        });
    } finally {
        isTestingXendit.value = false;
    }
};

const copyXenditWebhookUrl = () => {
    const origin = window.location.origin;
    const url = `${origin}/api/webhooks/xendit/disbursement`;
    navigator.clipboard.writeText(url);
    Swal.fire({
        icon: 'success',
        title: 'Webhook URL Xendit Disalin',
        html: `<p class="text-xs text-slate-600 mb-2">URL Webhook berhasil disalin ke clipboard:</p><code class="text-xs bg-slate-100 p-2 rounded-xl block text-indigo-700 font-mono break-all mb-3">${url}</code><p class="text-[11px] text-slate-500">Daftarkan URL ini di <strong>Dashboard Xendit &rarr; Settings &rarr; Callbacks &rarr; Disbursements</strong>.</p>`,
        confirmButtonColor: '#4f46e5',
        confirmButtonText: 'Mengerti'
    });
};

const formatRupiah = (num) => {
    return new Intl.NumberFormat('id-ID').format(num || 0);
};

const submit = () => {
    form.post(route('settings.payroll.update'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Berhasil Disimpan!',
                text: 'Pengaturan sistem gaji & auto transfer berhasil diperbarui.',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        },
        onError: (errors) => {
            const firstError = Object.values(errors || {})[0] || 'Periksa kembali formulir pengaturan.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: firstError,
                confirmButtonColor: '#4f46e5'
            });
        }
    });
};
</script>
