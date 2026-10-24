<template>
    <AppLayout title="WhatsApp Blast & Gateway">
        <div class="max-w-7xl mx-auto space-y-6 font-sans">
            
            <!-- ── HERO HEADER BAR ── -->
            <div class="bg-gradient-to-r from-slate-900 via-emerald-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden border border-emerald-900/40">
                <div class="absolute -right-10 -top-10 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 text-xs font-bold mb-3 shadow-inner">
                            <span>⚡</span>
                            <span>Engine Baileys Multi-Device (100% Gratis &amp; Self-Hosted)</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white flex items-center gap-3">
                            <span>WhatsApp Blast Cerdas &amp; Barcode Scanner</span>
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-300 mt-1.5 max-w-2xl leading-relaxed">
                            Kirim pesan massal berpersonalisasi tinggi dengan pemindai barcode QR resmi WhatsApp Web, didukung teknologi Spintax Anti-Banned dan proteksi jeda acak cerdas.
                        </p>
                    </div>

                    <!-- Connection Status Badge Card -->
                    <div v-can="'whatsapp_card_gateway_status'" class="bg-white/10 backdrop-blur-md p-4 sm:p-5 rounded-2xl border border-white/15 flex items-center justify-between gap-4 shrink-0 shadow-lg">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-2xl flex items-center justify-center text-xl shrink-0"
                                :class="{
                                    'bg-emerald-500/20 text-emerald-400 border border-emerald-400/40 animate-pulse': liveStatus.status === 'connected',
                                    'bg-amber-500/20 text-amber-400 border border-amber-400/40 animate-bounce': liveStatus.status === 'scan_qr',
                                    'bg-rose-500/20 text-rose-400 border border-rose-400/40': liveStatus.status === 'offline' || liveStatus.status === 'disconnected',
                                }"
                            >
                                <span v-if="liveStatus.status === 'connected'">📱</span>
                                <span v-else-if="liveStatus.status === 'scan_qr'">📷</span>
                                <span v-else>⚠️</span>
                            </div>
                            <div>
                                <div class="text-[10px] font-bold tracking-wider uppercase"
                                    :class="{
                                        'text-emerald-300': liveStatus.status === 'connected',
                                        'text-amber-300': liveStatus.status === 'scan_qr',
                                        'text-rose-300': liveStatus.status === 'offline' || liveStatus.status === 'disconnected',
                                    }"
                                >
                                    {{ liveStatus.status === 'connected' ? '● TERHUBUNG (ONLINE)' : (liveStatus.status === 'scan_qr' ? '● SCAN BARCODE' : '○ DISCONNECTED') }}
                                </div>
                                <div class="text-sm font-extrabold text-white font-mono mt-0.5">
                                    {{ liveStatus.user?.phone ? '+' + liveStatus.user.phone : 'Belum Ada Perangkat' }}
                                </div>
                                <div class="text-[10px] text-slate-300 truncate max-w-[180px]">
                                    {{ liveStatus.user?.name || (liveStatus.status === 'connected' ? 'Akun WA Aktif' : 'Buka menu Barcode untuk tautkan') }}
                                </div>
                            </div>
                        </div>

                        <button 
                            @click="refreshStatus(true)" 
                            :disabled="isRefreshingStatus"
                            class="p-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white transition-colors border border-white/10" 
                            title="Segarkan Status"
                        >
                            <svg class="w-4 h-4" :class="{ 'animate-spin': isRefreshingStatus }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </button>
                    </div>
                </div>

                <!-- NAVIGATION TABS -->
                <div class="mt-8 flex items-center gap-2 overflow-x-auto border-t border-white/10 pt-4 scrollbar-none">
                    <button 
                        v-can="'whatsapp_tab_blast'"
                        @click="activeTab = 'campaign'" 
                        :class="activeTab === 'campaign' ? 'bg-emerald-500 text-white font-extrabold shadow-md' : 'text-slate-300 hover:text-white hover:bg-white/10 font-bold'"
                        class="px-4 py-2.5 rounded-xl text-xs transition-all flex items-center gap-2 shrink-0 cursor-pointer"
                    >
                        <span>🚀</span>
                        <span>Buat &amp; Kirim WhatsApp Blast</span>
                    </button>

                    <button 
                        v-can="'whatsapp_tab_device'"
                        @click="activeTab = 'device'" 
                        :class="activeTab === 'device' ? 'bg-emerald-500 text-white font-extrabold shadow-md' : 'text-slate-300 hover:text-white hover:bg-white/10 font-bold'"
                        class="px-4 py-2.5 rounded-xl text-xs transition-all flex items-center gap-2 shrink-0 cursor-pointer"
                    >
                        <span>📷</span>
                        <span>Koneksi Perangkat (Scan Barcode)</span>
                        <span v-if="liveStatus.status === 'scan_qr'" class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
                    </button>

                    <button 
                        v-can="'whatsapp_tab_history'"
                        @click="activeTab = 'history'" 
                        :class="activeTab === 'history' ? 'bg-emerald-500 text-white font-extrabold shadow-md' : 'text-slate-300 hover:text-white hover:bg-white/10 font-bold'"
                        class="px-4 py-2.5 rounded-xl text-xs transition-all flex items-center gap-2 shrink-0 cursor-pointer"
                    >
                        <span>📊</span>
                        <span>Riwayat Kampanye &amp; Log</span>
                    </button>

                    <button 
                        v-can="'whatsapp_tab_settings'"
                        @click="activeTab = 'settings'" 
                        :class="activeTab === 'settings' ? 'bg-emerald-500 text-white font-extrabold shadow-md' : 'text-slate-300 hover:text-white hover:bg-white/10 font-bold'"
                        class="px-4 py-2.5 rounded-xl text-xs transition-all flex items-center gap-2 shrink-0 cursor-pointer"
                    >
                        <span>⚙️</span>
                        <span>Pengaturan Anti-Banned &amp; Server</span>
                    </button>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <!-- TAB 1: SCAN BARCODE / KONEKSI WHATSAPP                                     -->
            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <div v-if="activeTab === 'device'" class="space-y-6 animate-fade-in">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    
                    <!-- Left: QR Barcode Card -->
                    <div class="lg:col-span-7 bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100 flex flex-col items-center text-center">
                        <!-- Kondisi 1: Siap Scan Barcode -->
                        <template v-if="liveStatus.status === 'scan_qr' && liveStatus.qr_image">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-bold border border-amber-200 mb-4">
                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                <span>Barcode Siap Dipindai</span>
                            </div>
                            <h2 class="text-xl font-black text-slate-900">Tautkan Akun WhatsApp Anda</h2>
                            <p class="text-xs text-slate-500 mt-1 max-w-md">Buka aplikasi WhatsApp di HP Anda &rarr; Perangkat Tertaut &rarr; Tautkan Perangkat, lalu arahkan kamera ke barcode di bawah.</p>

                            <!-- QR Frame -->
                            <div class="mt-6 p-4 rounded-3xl bg-white border-2 border-emerald-500/30 shadow-xl relative group">
                                <img :src="liveStatus.qr_image" alt="WhatsApp QR Code" class="w-64 h-64 sm:w-72 sm:h-72 rounded-2xl mx-auto object-contain" />
                                <div class="mt-3 text-[11px] font-mono font-bold text-slate-400 flex items-center justify-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span>Auto-refresh aktif setiap pergantian kode</span>
                                </div>
                            </div>
                        </template>

                        <!-- Kondisi 2: Sudah Terhubung (Connected) -->
                        <template v-else-if="liveStatus.status === 'connected'">
                            <div class="w-20 h-20 rounded-3xl bg-emerald-50 border-2 border-emerald-200 text-emerald-600 flex items-center justify-center text-4xl shadow-inner mb-4 animate-scale-up">
                                ✓
                            </div>
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-black uppercase tracking-wider mb-2">
                                <span>● TERHUBUNG SEMPURNA</span>
                            </div>
                            <h2 class="text-2xl font-black text-slate-900 font-mono mt-1">+{{ liveStatus.user?.phone }}</h2>
                            <p class="text-xs text-slate-500 mt-1">{{ liveStatus.user?.name || 'WhatsApp Admin' }} &bull; Sesi Multi-Device Siap Mengirim Blast</p>

                            <div class="mt-6 grid grid-cols-2 gap-3 w-full max-w-sm">
                                <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80 text-left">
                                    <div class="text-[10px] uppercase font-bold text-slate-400">Terhubung Sejak</div>
                                    <div class="text-xs font-bold text-slate-800 mt-0.5">
                                        {{ liveStatus.connected_at ? new Date(liveStatus.connected_at).toLocaleString('id-ID') : 'Baru saja' }}
                                    </div>
                                </div>
                                <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80 text-left">
                                    <div class="text-[10px] uppercase font-bold text-slate-400">Engine Gateway</div>
                                    <div class="text-xs font-bold text-emerald-700 mt-0.5">Baileys v6 Socket</div>
                                </div>
                            </div>

                            <!-- Tombol Aksi Logout & Test Send -->
                            <div class="mt-6 flex flex-wrap gap-2.5 justify-center">
                                <button 
                                    v-can="'whatsapp_btn_test_send'"
                                    @click="openTestSendModal" 
                                    class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow-sm transition-all flex items-center gap-1.5 cursor-pointer"
                                >
                                    <span>✉️</span>
                                    <span>Kirim Pesan Uji Coba</span>
                                </button>
                                <button 
                                    v-can="'whatsapp_btn_logout'"
                                    @click="logoutDevice" 
                                    :disabled="isLoggingOut"
                                    class="px-4 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                                >
                                    <span>🔌</span>
                                    <span>{{ isLoggingOut ? 'Memutuskan...' : 'Putuskan Koneksi / Logout' }}</span>
                                </button>
                            </div>
                        </template>

                        <!-- Kondisi 3: Layanan Offline / Menghubungkan -->
                        <template v-else>
                            <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-3xl mb-4">
                                ⏳
                            </div>
                            <h2 class="text-lg font-black text-slate-900">Menyiapkan Sesi Barcode...</h2>
                            <p class="text-xs text-slate-500 mt-1 max-w-md">Layanan sedang menginisialisasi soket WhatsApp Baileys. Barcode akan muncul dalam hitungan detik.</p>
                            <button 
                                @click="refreshStatus(true)" 
                                class="mt-5 px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition-colors"
                            >
                                Segarkan Barcode
                            </button>
                        </template>
                    </div>

                    <!-- Right: Petunjuk & Keunggulan -->
                    <div class="lg:col-span-5 space-y-5">
                        <!-- Card Panduan Scan -->
                        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
                            <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2 mb-3">
                                <span>📋</span>
                                <span>Cara Menghubungkan Barcode:</span>
                            </h3>
                            <ol class="space-y-3 text-xs text-slate-600 list-decimal ml-4">
                                <li>Buka aplikasi <strong>WhatsApp</strong> di smartphone Anda.</li>
                                <li>Pilih menu <strong>Titik Tiga (⋮)</strong> di Android atau menu <strong>Pengaturan (⚙️)</strong> di iPhone.</li>
                                <li>Ketuk menu <strong>Perangkat Tertaut (Linked Devices)</strong>.</li>
                                <li>Ketuk tombol <strong>Tautkan Perangkat (Link a Device)</strong>.</li>
                                <li>Arahkan kamera smartphone ke barcode di samping hingga terdengar getar sukses.</li>
                            </ol>
                        </div>

                        <!-- Card Keunggulan Baileys Gateway -->
                        <div class="bg-gradient-to-br from-indigo-50 to-white rounded-3xl p-6 border border-indigo-100 shadow-sm">
                            <h3 class="text-sm font-extrabold text-indigo-950 flex items-center gap-2 mb-3">
                                <span>💎</span>
                                <span>Keunggulan WhatsApp Gateway APM2:</span>
                            </h3>
                            <ul class="space-y-2 text-xs text-indigo-900">
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-600 font-bold">✓</span>
                                    <span><strong>100% Gratis Selamanya</strong>: Tidak memerlukan biaya langganan bulanan ke pihak ketiga seperti Fonnte/Wablas.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-600 font-bold">✓</span>
                                    <span><strong>Socket Ringan (Non-Chromium)</strong>: Berjalan dengan Baileys murni sehingga hemat RAM dan CPU di server VPS Anda.</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-600 font-bold">✓</span>
                                    <span><strong>Multi-Device Resmi</strong>: HP WhatsApp Anda tetap bisa dipakai normal dan tidak akan logout saat gateway aktif.</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <!-- TAB 2: BUAT & KIRIM WHATSAPP BLAST                                         -->
            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <div v-if="activeTab === 'campaign'" class="space-y-6 animate-fade-in">
                
                <!-- Notice jika belum terhubung -->
                <div v-if="liveStatus.status !== 'connected'" class="bg-amber-50 border-2 border-amber-300 rounded-3xl p-5 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">⚠️</span>
                        <div>
                            <div class="text-sm font-black text-amber-950">WhatsApp Belum Terhubung!</div>
                            <div class="text-xs text-amber-800 mt-0.5">Silakan tautkan akun WhatsApp Anda terlebih dahulu melalui tab Scan Barcode agar pesan dapat terkirim.</div>
                        </div>
                    </div>
                    <button 
                        @click="activeTab = 'device'" 
                        class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-extrabold shadow-sm transition-all whitespace-nowrap self-start sm:self-auto cursor-pointer"
                    >
                        Buka Scan Barcode &rarr;
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    
                    <!-- Left Form (7 Cols): Segmentasi, Template & Pesan -->
                    <div class="lg:col-span-7 space-y-6">
                        
                        <!-- 1. SEGMENTASI TARGET PENERIMA -->
                        <div v-can="'whatsapp_filter_target'" class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">1</span>
                                    <span>Pilih Segmentasi Target Penerima</span>
                                </h3>
                                <button 
                                    type="button" 
                                    @click="showRecipientsModal = true" 
                                    class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 cursor-pointer"
                                >
                                    <span>Lihat Daftar Kontak ({{ recipientList.length }}) &rarr;</span>
                                </button>
                            </div>

                            <!-- Target Type Buttons -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                <button
                                    v-for="target in targetOptions"
                                    :key="target.id"
                                    type="button"
                                    @click="selectTargetType(target.id)"
                                    :class="campaignForm.target_type === target.id ? 'bg-slate-900 text-white font-extrabold shadow-sm' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 font-semibold border border-slate-200/80'"
                                    class="p-3 rounded-2xl text-left text-xs transition-all flex flex-col justify-between cursor-pointer"
                                >
                                    <div class="text-base mb-1">{{ target.icon }}</div>
                                    <div class="truncate font-bold">{{ target.label }}</div>
                                    <div class="text-[10px] opacity-70 mt-0.5">{{ target.count }} Kontak</div>
                                </button>
                            </div>

                            <!-- Filter Tambahan: Area & Paket & Tagihan Unpaid -->
                            <div class="pt-3 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Filter Area / Wilayah</label>
                                    <select v-model="campaignForm.area_id" @change="fetchRecipients" class="w-full text-xs font-bold border-slate-200 rounded-xl focus:ring-emerald-500 focus:border-emerald-500 bg-slate-50">
                                        <option :value="null">Semua Area (Global)</option>
                                        <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Filter Paket Internet</label>
                                    <select v-model="campaignForm.package_id" @change="fetchRecipients" class="w-full text-xs font-bold border-slate-200 rounded-xl focus:ring-emerald-500 focus:border-emerald-500 bg-slate-50">
                                        <option :value="null">Semua Paket</option>
                                        <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">{{ pkg.name }}</option>
                                    </select>
                                </div>
                            </div>

                            <label class="flex items-center gap-2 pt-1 text-xs text-slate-700 cursor-pointer">
                                <input type="checkbox" v-model="campaignForm.only_unpaid" @change="fetchRecipients" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="font-bold">Hanya kirim ke pelanggan yang memiliki tagihan belum lunas (Unpaid)</span>
                            </label>
                        </div>

                        <!-- 2. SUSUN PESAN & TEMPLATE PINTAR -->
                        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">2</span>
                                    <span>Susun Konten Pesan &amp; Variabel Pintar</span>
                                </h3>
                                <div class="text-[11px] text-slate-400 font-mono">{{ campaignForm.message.length }} Karakter</div>
                            </div>

                            <!-- Judul Kampanye -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Judul / Nama Kampanye *</label>
                                <input 
                                    type="text" 
                                    v-model="campaignForm.title" 
                                    placeholder="Contoh: Pengingat Tagihan Periode Oktober 2026" 
                                    class="w-full text-xs font-bold border-slate-200 rounded-xl focus:ring-emerald-500 focus:border-emerald-500"
                                />
                            </div>

                            <!-- Preset Template Cepat -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Gunakan Template Cepat:</label>
                                <div class="flex flex-wrap gap-1.5">
                                    <button 
                                        v-for="(tpl, idx) in messageTemplates" 
                                        :key="idx"
                                        type="button"
                                        @click="applyTemplate(tpl)"
                                        class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 hover:bg-emerald-50 hover:text-emerald-800 text-slate-700 transition-colors border border-slate-200/80 cursor-pointer"
                                    >
                                        {{ tpl.name }}
                                    </button>
                                </div>
                            </div>

                            <!-- Tombol Sisipkan Variabel Pintar -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Klik untuk Sisipkan Variabel Personalisasi:</label>
                                <div class="flex flex-wrap gap-1.5">
                                    <button 
                                        v-for="v in smartVariables" 
                                        :key="v.tag"
                                        type="button"
                                        @click="insertTag(v.tag)"
                                        class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 transition-colors cursor-pointer"
                                        :title="v.desc"
                                    >
                                        {{ v.tag }}
                                    </button>
                                </div>
                            </div>

                            <!-- Textarea Konten Pesan -->
                            <div>
                                <textarea 
                                    ref="messageInput"
                                    v-model="campaignForm.message" 
                                    rows="7" 
                                    placeholder="Tulis pesan Anda di sini... Gunakan spintax {Halo|Hai|Selamat {salam}} agar teks diacak otomatis per penerima."
                                    class="w-full text-xs font-sans border-slate-200 rounded-2xl focus:ring-emerald-500 focus:border-emerald-500 leading-relaxed shadow-2xs"
                                ></textarea>
                            </div>

                            <!-- Tips Spintax Anti-Banned -->
                            <div class="p-3 bg-emerald-50/70 rounded-2xl border border-emerald-100 text-[11px] text-emerald-900 flex items-start gap-2">
                                <span class="text-base shrink-0">🛡️</span>
                                <div>
                                    <strong>Teknologi Spintax Anti-Banned Aktif:</strong> Gunakan pola <code class="bg-white px-1 py-0.5 rounded text-emerald-700">{Halo|Hai|Selamat {salam}}</code> untuk mengacak sapaan pembuka, sehingga WhatsApp mengidentifikasi setiap pesan sebagai pesan personal unik, bukan spam bot massal.
                                </div>
                            </div>

                            <!-- URL Lampiran Gambar (Opsional) -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1 flex items-center justify-between">
                                    <span>Lampiran Gambar / Flyer Promo (Opsional)</span>
                                    <span class="text-[10px] text-slate-400">URL Gambar (PNG/JPG)</span>
                                </label>
                                <input 
                                    type="url" 
                                    v-model="campaignForm.media_url" 
                                    placeholder="https://domain-anda.com/promo-banner.jpg" 
                                    class="w-full text-xs border-slate-200 rounded-xl focus:ring-emerald-500 focus:border-emerald-500 font-mono"
                                />
                            </div>
                        </div>

                        <!-- 3. TOMBOL LUNCURKAN BLAST -->
                        <div class="flex items-center justify-between p-5 bg-white rounded-3xl border border-slate-100 shadow-sm">
                            <div>
                                <div class="text-xs font-bold text-slate-900">Total Siap Kirim: <span class="text-emerald-600 font-black text-sm">{{ recipientList.length }} Kontak</span></div>
                                <div class="text-[11px] text-slate-400">Jeda acak: {{ gatewaySettings.anti_ban_delay_min }}s - {{ gatewaySettings.anti_ban_delay_max }}s per pesan</div>
                            </div>

                            <button 
                                v-can="'whatsapp_btn_start_blast'"
                                type="button"
                                @click="startBlastCampaign"
                                :disabled="recipientList.length === 0 || liveStatus.status !== 'connected' || !campaignForm.message || !campaignForm.title"
                                class="px-6 py-3 rounded-2xl text-xs font-black uppercase tracking-wider text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-md transition-all active:scale-95 disabled:opacity-40 cursor-pointer flex items-center gap-2"
                            >
                                <span>⚡</span>
                                <span>Mulai Kirim WhatsApp Blast</span>
                            </button>
                        </div>

                    </div>

                    <!-- Right Column (5 Cols): Live Smartphone Chat Mockup -->
                    <div class="lg:col-span-5 sticky top-6 space-y-4">
                        <div class="bg-slate-900 rounded-3xl p-4 text-white shadow-xl border border-slate-800">
                            <div class="flex items-center justify-between mb-3 px-2">
                                <div class="text-xs font-black text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>📱</span>
                                    <span>Pratinjau Live WhatsApp</span>
                                </div>
                                <span class="text-[10px] text-emerald-400 font-bold bg-emerald-500/20 px-2 py-0.5 rounded-full">Simulasi Realtime</span>
                            </div>

                            <!-- Phone Frame -->
                            <div class="bg-slate-950 rounded-2xl p-4 border border-slate-800 relative overflow-hidden font-sans">
                                <!-- Chat Header Bar -->
                                <div class="bg-emerald-800 text-white px-3 py-2.5 rounded-xl flex items-center gap-2.5 mb-3 shadow-sm">
                                    <div class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center font-bold text-xs">
                                        {{ (recipientList[0]?.name || 'P').charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-xs font-extrabold truncate">{{ recipientList[0]?.name || 'Nama Pelanggan (Sample)' }}</div>
                                        <div class="text-[9px] text-emerald-200">Online</div>
                                    </div>
                                </div>

                                <!-- WhatsApp Chat Bubble -->
                                <div class="bg-emerald-100 text-slate-900 rounded-2xl rounded-tl-none p-3.5 shadow-sm text-xs space-y-2 relative max-w-[92%] leading-relaxed">
                                    <!-- Image Preview if available -->
                                    <div v-if="campaignForm.media_url" class="rounded-xl overflow-hidden mb-2 max-h-40 bg-slate-200">
                                        <img :src="campaignForm.media_url" alt="Attachment" class="w-full h-full object-cover" />
                                    </div>

                                    <div class="whitespace-pre-wrap font-sans text-slate-900 text-xs">
                                        {{ renderedPreview || 'Ketik pesan di kolom kiri untuk melihat simulasi pesan di sini...' }}
                                    </div>

                                    <div class="text-right text-[9px] text-slate-500 font-medium flex items-center justify-end gap-1">
                                        <span>{{ new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}</span>
                                        <span class="text-sky-600 font-bold">✓✓</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <!-- TAB 3: RIWAYAT KAMPANYE & LOG PENGIRIMAN                                   -->
            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <div v-if="activeTab === 'history'" class="space-y-6 animate-fade-in">
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
                    <h3 class="text-base font-extrabold text-slate-900 mb-4 flex items-center gap-2">
                        <span>📊</span>
                        <span>Riwayat Kampanye WhatsApp Blast</span>
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 uppercase font-bold tracking-wider border-b border-slate-100">
                                    <th class="px-4 py-3">Nama Kampanye</th>
                                    <th class="px-4 py-3">Sasaran Target</th>
                                    <th class="px-4 py-3 text-center">Terkirim</th>
                                    <th class="px-4 py-3 text-center">Gagal</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                    <th class="px-4 py-3">Waktu Kirim</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="c in campaigns.data" :key="c.id" class="hover:bg-slate-50/70 transition-colors">
                                    <td class="px-4 py-3.5">
                                        <div class="font-extrabold text-slate-900 text-sm">{{ c.title }}</div>
                                        <div class="text-[11px] text-slate-400 mt-0.5 truncate max-w-sm">{{ c.message }}</div>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-slate-100 text-slate-700 capitalize">
                                            {{ c.target_type.replace('_', ' ') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-center font-bold text-emerald-600 font-mono">
                                        {{ c.sent_count }} / {{ c.total_recipients }}
                                    </td>
                                    <td class="px-4 py-3.5 text-center font-bold font-mono" :class="c.failed_count > 0 ? 'text-rose-600' : 'text-slate-400'">
                                        {{ c.failed_count }}
                                    </td>
                                    <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase border"
                                            :class="c.status === 'completed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                        >
                                            {{ c.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-slate-500 font-mono text-[11px]">
                                        {{ new Date(c.created_at).toLocaleString('id-ID') }}
                                    </td>
                                </tr>
                                <tr v-if="campaigns.data.length === 0">
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-400 text-xs italic">
                                        Belum ada riwayat kampanye pengiriman WhatsApp blast.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <!-- TAB 4: PENGATURAN ANTI-BANNED & GATEWAY SERVER                             -->
            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <div v-if="activeTab === 'settings'" class="space-y-6 animate-fade-in">
                <form @submit.prevent="saveSettings" class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100 space-y-6">
                    <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                        <span>⚙️</span>
                        <span>Konfigurasi WhatsApp Gateway &amp; Proteksi Anti-Banned</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">URL Gateway Service (Local / Server) *</label>
                            <input type="url" v-model="settingsForm.wa_gateway_url" class="w-full text-xs font-mono border-slate-200 rounded-xl" required />
                            <p class="text-[11px] text-slate-400 mt-1">Default internal: <code>http://127.0.0.1:3001</code></p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Jeda Acak Minimal Antar Pesan (Detik) *</label>
                            <input type="number" v-model="settingsForm.wa_anti_ban_delay_min" min="2" max="30" class="w-full text-xs border-slate-200 rounded-xl" required />
                            <p class="text-[11px] text-slate-400 mt-1">Rekomendasi aman: 5 detik</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Jeda Acak Maksimal Antar Pesan (Detik) *</label>
                            <input type="number" v-model="settingsForm.wa_anti_ban_delay_max" min="3" max="60" class="w-full text-xs border-slate-200 rounded-xl" required />
                            <p class="text-[11px] text-slate-400 mt-1">Rekomendasi aman: 12 detik</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Ukuran Batch Istirahat (Jumlah Pesan) *</label>
                            <input type="number" v-model="settingsForm.wa_batch_size" min="5" max="100" class="w-full text-xs border-slate-200 rounded-xl" required />
                            <p class="text-[11px] text-slate-400 mt-1">Sistem akan istirahat setelah mengirim sejumlah pesan ini</p>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end">
                        <button type="submit" :disabled="settingsForm.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl text-xs font-extrabold shadow-sm cursor-pointer disabled:opacity-50">
                            Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>

            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <!-- MODAL: LIVE BLAST EXECUTION MONITOR                                        -->
            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <div v-if="isBlasting" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-md"></div>
                
                <div class="relative w-full max-w-2xl bg-white rounded-3xl p-6 sm:p-8 shadow-2xl border border-slate-100 space-y-6 animate-scale-up">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold animate-spin">
                                ⚡
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold text-slate-900">Proses Pengiriman WhatsApp Blast</h3>
                                <p class="text-xs text-slate-500">Jangan tutup tab browser ini selama proses pengiriman berlangsung.</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-black text-emerald-600 font-mono">{{ blastProgressPercent }}%</span>
                        </div>
                    </div>

                    <!-- Animated Progress Bar -->
                    <div class="space-y-1.5">
                        <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden p-0.5">
                            <div 
                                class="bg-gradient-to-r from-emerald-500 to-teal-500 h-full rounded-full transition-all duration-300"
                                :style="{ width: blastProgressPercent + '%' }"
                            ></div>
                        </div>
                        <div class="flex justify-between text-[11px] text-slate-500 font-mono">
                            <span>Selesai: {{ blastStats.sent + blastStats.failed }} dari {{ blastStats.total }}</span>
                            <span v-if="blastStatusNotice" class="text-emerald-700 font-bold">{{ blastStatusNotice }}</span>
                        </div>
                    </div>

                    <!-- Counter Cards -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-emerald-50 p-3.5 rounded-2xl text-center border border-emerald-100">
                            <div class="text-[10px] uppercase font-bold text-emerald-600">Berhasil Terkirim</div>
                            <div class="text-xl font-black text-emerald-700 font-mono mt-0.5">{{ blastStats.sent }}</div>
                        </div>
                        <div class="bg-rose-50 p-3.5 rounded-2xl text-center border border-rose-100">
                            <div class="text-[10px] uppercase font-bold text-rose-600">Gagal Kirim</div>
                            <div class="text-xl font-black text-rose-700 font-mono mt-0.5">{{ blastStats.failed }}</div>
                        </div>
                        <div class="bg-amber-50 p-3.5 rounded-2xl text-center border border-amber-100">
                            <div class="text-[10px] uppercase font-bold text-amber-600">Dalam Antrean</div>
                            <div class="text-xl font-black text-amber-700 font-mono mt-0.5">{{ blastStats.remaining }}</div>
                        </div>
                    </div>

                    <!-- Realtime Sending Logs Table -->
                    <div class="space-y-1.5">
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Live Log Pengiriman:</div>
                        <div class="h-44 overflow-y-auto rounded-2xl border border-slate-100 bg-slate-50 p-3 space-y-2 text-xs font-mono">
                            <div 
                                v-for="(log, i) in liveBlastLogs" 
                                :key="i"
                                class="flex items-center justify-between p-2 rounded-xl bg-white border border-slate-200/60 shadow-2xs text-[11px]"
                            >
                                <div class="truncate max-w-[70%]">
                                    <strong class="text-slate-800">{{ log.name }}</strong> ({{ log.phone }}):
                                    <span class="text-slate-500 ml-1">{{ log.preview }}</span>
                                </div>
                                <span 
                                    class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase shrink-0"
                                    :class="log.status === 'sent' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                >
                                    {{ log.status === 'sent' ? '✓ TERKIRIM' : '✗ GAGAL' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons Pause / Stop -->
                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button 
                            v-if="!blastFinished"
                            type="button" 
                            @click="isPaused = !isPaused" 
                            class="px-4 py-2 rounded-xl text-xs font-bold border border-slate-300 hover:bg-slate-50 text-slate-700 cursor-pointer"
                        >
                            {{ isPaused ? '▶ Lanjutkan Blast' : '⏸ Jeda (Pause)' }}
                        </button>
                        <button 
                            v-if="!blastFinished"
                            type="button" 
                            @click="cancelBlast" 
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white cursor-pointer"
                        >
                            Hentikan (Stop)
                        </button>
                        <button 
                            v-else
                            type="button" 
                            @click="closeBlastModal" 
                            class="px-6 py-2.5 rounded-xl text-xs font-extrabold bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer"
                        >
                            Tutup &amp; Lihat Laporan
                        </button>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <!-- MODAL: DAFTAR TARGET PENERIMA                                              -->
            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <div v-if="showRecipientsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm cursor-pointer" @click="showRecipientsModal = false"></div>
                <div class="relative w-full max-w-4xl bg-white rounded-3xl p-6 shadow-2xl border border-slate-100 space-y-4 max-h-[85vh] flex flex-col">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900">Daftar Calon Penerima Pesan ({{ recipientList.length }} Kontak)</h3>
                            <p class="text-xs text-slate-500">Pratinjau data kontak yang akan menerima WhatsApp blast.</p>
                        </div>
                        <button @click="showRecipientsModal = false" class="text-slate-400 hover:text-slate-700 p-1 text-xl font-bold">&times;</button>
                    </div>

                    <div class="overflow-y-auto flex-1">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 uppercase font-bold sticky top-0 border-b border-slate-100">
                                    <th class="px-3.5 py-2.5">Nama</th>
                                    <th class="px-3.5 py-2.5">Nomor WA</th>
                                    <th class="px-3.5 py-2.5">Paket</th>
                                    <th class="px-3.5 py-2.5">Area</th>
                                    <th class="px-3.5 py-2.5 text-right">Tagihan Unpaid</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="r in recipientList" :key="r.id" class="hover:bg-slate-50/70">
                                    <td class="px-3.5 py-2 font-bold text-slate-800">{{ r.name }}</td>
                                    <td class="px-3.5 py-2 font-mono text-indigo-700">{{ r.phone }}</td>
                                    <td class="px-3.5 py-2 text-slate-600">{{ r.package_name }}</td>
                                    <td class="px-3.5 py-2 text-slate-500">{{ r.area_name }}</td>
                                    <td class="px-3.5 py-2 text-right font-mono font-bold" :class="r.unpaid_amount > 0 ? 'text-rose-600' : 'text-slate-400'">
                                        Rp {{ formatRupiah(r.unpaid_amount) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex justify-end">
                        <button @click="showRecipientsModal = false" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold">
                            Tutup Pratinjau
                        </button>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <!-- MODAL: KIRIM PESAN UJI COBA CEPAT                                         -->
            <!-- ══════════════════════════════════════════════════════════════════════════ -->
            <div v-if="showTestSendModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm cursor-pointer" @click="showTestSendModal = false"></div>
                <div class="relative w-full max-w-md bg-white rounded-3xl p-6 shadow-2xl border border-slate-100 space-y-4">
                    <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                        <span>✉️</span>
                        <span>Kirim Pesan Uji Coba (Test Send)</span>
                    </h3>
                    <p class="text-xs text-slate-500">Kirimkan pesan sampel langsung ke nomor WhatsApp Anda sendiri untuk memastikan koneksi gateway berjalan lancar.</p>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Nomor WhatsApp Tujuan *</label>
                        <input type="text" v-model="testSendForm.phone" placeholder="08xxxxxxxxxx" class="w-full text-xs font-mono border-slate-200 rounded-xl" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Pesan Uji Coba</label>
                        <textarea v-model="testSendForm.message" rows="4" class="w-full text-xs border-slate-200 rounded-xl"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button @click="showTestSendModal = false" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl text-xs font-bold">Batal</button>
                        <button @click="executeTestSend" :disabled="isSendingTest || !testSendForm.phone" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-extrabold disabled:opacity-50 flex items-center gap-1.5">
                            <span v-if="isSendingTest">Mengirim...</span>
                            <span v-else>Kirim Sekarang</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    areas: Array,
    packages: Array,
    stats: Object,
    campaigns: Object,
    gatewayStatus: Object,
    gatewaySettings: Object,
});

const activeTab = ref('campaign'); // 'campaign', 'device', 'history', 'settings'
const liveStatus = ref(props.gatewayStatus || {});
const isRefreshingStatus = ref(false);
const isLoggingOut = ref(false);

let statusPollInterval = null;

const refreshStatus = async (showNotification = false) => {
    isRefreshingStatus.value = true;
    try {
        const response = await axios.get(route('whatsapp-blast.status'));
        liveStatus.value = response.data;
        if (showNotification && response.data.status === 'connected') {
            alert('Status: WhatsApp Terhubung!');
        }
    } catch (e) {
        liveStatus.value = { status: 'offline', message: 'Gateway Offline' };
    } finally {
        isRefreshingStatus.value = false;
    }
};

onMounted(() => {
    // Poll status barcode & koneksi setiap 4 detik jika di tab device
    statusPollInterval = setInterval(() => {
        if (activeTab.value === 'device' || liveStatus.value.status === 'scan_qr') {
            refreshStatus();
        }
    }, 4000);

    fetchRecipients();
});

onUnmounted(() => {
    if (statusPollInterval) clearInterval(statusPollInterval);
});

const logoutDevice = async () => {
    if (confirm('Putuskan koneksi perangkat WhatsApp ini? Anda harus scan barcode ulang untuk menghubungkannya kembali.')) {
        isLoggingOut.value = true;
        try {
            await axios.post(route('whatsapp-blast.logout'));
            await refreshStatus();
        } catch (e) {
            alert('Gagal logout: ' + e.message);
        } finally {
            isLoggingOut.value = false;
        }
    }
};

// ── Target Options ──
const targetOptions = [
    { id: 'all_customers', label: 'Semua Pelanggan', count: props.stats?.total_customers || 0, icon: '👥' },
    { id: 'active', label: 'Pelanggan Aktif', count: props.stats?.active_customers || 0, icon: '🟢' },
    { id: 'isolir', label: 'Isolir / Nunggak', count: props.stats?.isolir_customers || 0, icon: '🔴' },
    { id: 'booking', label: 'Booking Baru', count: props.stats?.booking_customers || 0, icon: '🟡' },
    { id: 'resellers', label: 'Mitra Reseller', count: props.stats?.total_resellers || 0, icon: '🤝' },
    { id: 'technicians', label: 'Tim Teknisi', count: props.stats?.total_technicians || 0, icon: '🔧' },
];

const recipientList = ref([]);
const showRecipientsModal = ref(false);

const campaignForm = ref({
    title: 'Pengingat Tagihan & Info Layanan',
    target_type: 'all_customers',
    area_id: null,
    package_id: null,
    only_unpaid: false,
    message: '{Halo|Hai|Selamat {salam}} Bpk/Ibu *{nama}*,\n\nKami menginformasikan bahwa tagihan internet layanan *{paket}* Anda sebesar *{tagihan}* dengan batas jatuh tempo pada tanggal *{jatuh_tempo}*.\n\nMohon lakukan pembayaran tepat waktu agar koneksi internet Anda tetap lancar dan tidak terisolir otomatis.\n\nTerima kasih,\n*{perusahaan}*',
    media_url: '',
});

const selectTargetType = (typeId) => {
    campaignForm.value.target_type = typeId;
    fetchRecipients();
};

const fetchRecipients = async () => {
    try {
        const response = await axios.post(route('whatsapp-blast.recipients'), {
            target_type: campaignForm.value.target_type,
            area_id: campaignForm.value.area_id,
            package_id: campaignForm.value.package_id,
            only_unpaid: campaignForm.value.only_unpaid,
        });
        recipientList.value = response.data.recipients || [];
    } catch (e) {
        console.error('Failed to fetch recipients', e);
    }
};

// ── Smart Variables & Template Presets ──
const smartVariables = [
    { tag: '{nama}', desc: 'Nama Penerima' },
    { tag: '{id_pelanggan}', desc: 'ID / No Layanan' },
    { tag: '{paket}', desc: 'Nama Paket Internet' },
    { tag: '{tagihan}', desc: 'Nominal Tagihan (Rp)' },
    { tag: '{nomor_invoice}', desc: 'Nomor Invoice' },
    { tag: '{jatuh_tempo}', desc: 'Tanggal Jatuh Tempo' },
    { tag: '{alamat}', desc: 'Alamat Pelanggan' },
    { tag: '{area}', desc: 'Area / Wilayah' },
    { tag: '{telepon}', desc: 'Nomor HP WhatsApp' },
    { tag: '{salam}', desc: 'Salam Pagi/Siang/Malam' },
    { tag: '{perusahaan}', desc: 'Nama Perusahaan/Brand' },
];

const messageTemplates = [
    {
        name: '🔔 Pengingat Tagihan',
        title: 'Pengingat Tagihan Bulanan',
        content: '{Halo|Hai|Selamat {salam}} Bpk/Ibu *{nama}*,\n\nKami menginformasikan bahwa tagihan internet layanan *{paket}* Anda sebesar *{tagihan}* akan jatuh tempo pada *{jatuh_tempo}* (Invoice: #{nomor_invoice}).\n\nSilakan lakukan pembayaran tepat waktu agar koneksi internet rumah Anda tetap aktif lancar.\n\nTerima kasih,\n*{perusahaan}*'
    },
    {
        name: '⚠️ Notifikasi Isolir',
        title: 'Pemberitahuan Isolir Layanan',
        content: 'Yth. Bpk/Ibu *{nama}* ({id_pelanggan}),\n\nKami menginformasikan bahwa per hari ini layanan internet Anda berstatus *ISOLIR SEMENTARA* karena tagihan tertunggak sebesar *{tagihan}*.\n\nUntuk membuka kembali koneksi internet, silakan segera selesaikan tagihan Anda melalui transfer bank resmi kami. Hubungi CS setelah pembayaran.\n\nSalam hormat,\n*{perusahaan}*'
    },
    {
        name: '🛠️ Info Maintenance Jalur',
        title: 'Pemberitahuan Maintenance Jaringan Massal',
        content: '{Pemberitahuan|Informasi} Resmi *{perusahaan}*:\n\nYth. Bpk/Ibu *{nama}* di wilayah *{area}*,\n\nSedang dilakukan pemeliharaan & peningkatan kapasitas jaringan fiber optic di area Anda. Layanan mungkin akan mengalami penurunan performa sementara.\n\nEstimasi normal kembali: 1-2 jam ke depan. Mohon maaf atas ketidaknyamanan Anda.\n\nTerima kasih atas pengertiannya.'
    },
    {
        name: '🎁 Promo Upgrade Speed',
        title: 'Promo Upgrade Kecepatan Internet',
        content: '{Halo|Hai} Bpk/Ibu *{nama}*!\n\nKabar gembira untuk pelanggan setia paket *{paket}*! Nikmati kesempatan *UPGRADE SPEED HINGGA 2X LIPAT* dengan biaya hemat.\n\nStreaming lebih lancar, gaming bebas lag, dan browsing super cepat sekeluarga.\n\nBalas pesan ini untuk klaim promo sekarang juga!'
    }
];

const messageInput = ref(null);

const applyTemplate = (tpl) => {
    campaignForm.value.title = tpl.title;
    campaignForm.value.message = tpl.content;
};

const insertTag = (tag) => {
    campaignForm.value.message += ` ${tag} `;
};

// ── Realtime Simulated Message Preview ──
const renderedPreview = computed(() => {
    const raw = campaignForm.value.message;
    if (!raw) return '';
    const sample = recipientList.value[0] || {
        name: 'Budi Santoso',
        id_pelanggan: 'CUST-0891',
        package_name: 'Paket Home 20 Mbps',
        unpaid_amount: 165000,
        invoice_number: 'INV/2026/10/0129',
        due_date: '20 Okt 2026',
        area_name: 'Cabang Timur',
    };

    // Replace Spintax for preview (ambil opsi pertama)
    let text = raw.replace(/\{([^{}]*)\}/g, (match, p1) => {
        const parts = p1.split('|');
        return parts[0];
    });

    const vars = {
        '{nama}': sample.name,
        '{id_pelanggan}': sample.id_pelanggan,
        '{telepon}': sample.phone || '08123456789',
        '{paket}': sample.package_name,
        '{tagihan}': 'Rp ' + formatRupiah(sample.unpaid_amount || 165000),
        '{nomor_invoice}': sample.invoice_number,
        '{jatuh_tempo}': sample.due_date,
        '{alamat}': sample.address || 'Jl. Mangga No. 12',
        '{area}': sample.area_name,
        '{salam}': 'Pagi',
        '{perusahaan}': 'ISP Network',
    };

    for (const [k, v] of Object.entries(vars)) {
        text = text.replaceAll(k, v);
    }
    return text;
});

// ── Execution Blast Loop ──
const isBlasting = ref(false);
const isPaused = ref(false);
const blastFinished = ref(false);
const currentCampaignId = ref(null);
const blastStats = ref({ sent: 0, failed: 0, total: 0, remaining: 0 });
const liveBlastLogs = ref([]);
const blastStatusNotice = ref('');

const blastProgressPercent = computed(() => {
    if (blastStats.value.total === 0) return 0;
    return Math.round(((blastStats.value.sent + blastStats.value.failed) / blastStats.value.total) * 100);
});

const startBlastCampaign = async () => {
    if (recipientList.value.length === 0) {
        alert('Tidak ada kontak penerima.');
        return;
    }

    if (!confirm(`Mulai pengiriman WhatsApp Blast ke ${recipientList.value.length} kontak?\n\nProteksi jeda acak anti-banned akan otomatis diterapkan.`)) {
        return;
    }

    isBlasting.value = true;
    isPaused.value = false;
    blastFinished.value = false;
    liveBlastLogs.value = [];
    blastStats.value = {
        sent: 0,
        failed: 0,
        total: recipientList.value.length,
        remaining: recipientList.value.length,
    };

    try {
        // 1. Inisialisasi kampanye di DB
        const initRes = await axios.post(route('whatsapp-blast.campaigns.create'), {
            title: campaignForm.value.title,
            target_type: campaignForm.value.target_type,
            target_area_id: campaignForm.value.area_id,
            message: campaignForm.value.message,
            media_url: campaignForm.value.media_url,
            total_recipients: recipientList.value.length,
            delay_seconds: 5,
        });

        currentCampaignId.value = initRes.data.campaign_id;

        // 2. Loop pengiriman dengan jeda acak anti-banned
        await executeBlastLoop();

    } catch (e) {
        alert('Gagal memulai kampanye: ' + (e.response?.data?.message || e.message));
        isBlasting.value = false;
    }
};

const executeBlastLoop = async () => {
    const list = [...recipientList.value];
    const minDelay = Number(props.gatewaySettings?.anti_ban_delay_min) || 5;
    const maxDelay = Number(props.gatewaySettings?.anti_ban_delay_max) || 12;

    for (let i = 0; i < list.length; i++) {
        // Cek jika dibatalkan
        if (!isBlasting.value) break;

        // Cek jika di-pause
        while (isPaused.value && isBlasting.value) {
            blastStatusNotice.value = 'Pengiriman dijeda...';
            await new Promise(r => setTimeout(r, 1000));
        }

        const recipient = list[i];
        blastStatusNotice.value = `Mengirim ke ${recipient.name} (${recipient.phone})...`;

        try {
            const res = await axios.post(route('whatsapp-blast.campaigns.dispatch', currentCampaignId.value), {
                recipient: recipient
            });

            if (res.data.success) {
                blastStats.value.sent++;
                liveBlastLogs.value.unshift({
                    name: recipient.name,
                    phone: recipient.phone,
                    status: 'sent',
                    preview: res.data.sent_message_preview,
                });
            } else {
                blastStats.value.failed++;
                liveBlastLogs.value.unshift({
                    name: recipient.name,
                    phone: recipient.phone,
                    status: 'failed',
                    preview: res.data.error || 'Gagal',
                });
            }
        } catch (err) {
            blastStats.value.failed++;
            liveBlastLogs.value.unshift({
                name: recipient.name,
                phone: recipient.phone,
                status: 'failed',
                preview: err.response?.data?.message || err.message,
            });
        }

        blastStats.value.remaining = blastStats.value.total - (blastStats.value.sent + blastStats.value.failed);

        // Anti-ban random delay jika masih ada kontak berikutnya
        if (i < list.length - 1) {
            const randomSec = Math.floor(Math.random() * (maxDelay - minDelay + 1)) + minDelay;
            blastStatusNotice.value = `Jeda anti-banned: istirahat ${randomSec} detik...`;
            await new Promise(r => setTimeout(r, randomSec * 1000));
        }
    }

    blastStatusNotice.value = 'Semua pesan telah selesai diproses!';
    blastFinished.value = true;

    // Selesaikan kampanye di DB
    if (currentCampaignId.value) {
        await axios.post(route('whatsapp-blast.campaigns.complete', currentCampaignId.value), {
            status: 'completed'
        });
    }
};

const cancelBlast = () => {
    if (confirm('Yakin ingin menghentikan pengiriman WhatsApp blast ini?')) {
        isBlasting.value = false;
        blastStatusNotice.value = 'Dibatalkan oleh admin.';
        if (currentCampaignId.value) {
            axios.post(route('whatsapp-blast.campaigns.complete', currentCampaignId.value), {
                status: 'canceled'
            });
        }
    }
};

const closeBlastModal = () => {
    isBlasting.value = false;
    router.reload({ preserveScroll: true });
};

// ── Test Send Modal ──
const showTestSendModal = ref(false);
const isSendingTest = ref(false);
const testSendForm = ref({
    phone: '',
    message: '{Halo|Hai} ini pesan uji coba resmi dari sistem WhatsApp Gateway APM2! Koneksi berhasil dan berjalan dengan baik.',
});

const openTestSendModal = () => {
    testSendForm.value.phone = liveStatus.value.user?.phone || '';
    showTestSendModal.value = true;
};

const executeTestSend = async () => {
    isSendingTest.value = true;
    try {
        const res = await axios.post(route('whatsapp-blast.test-send'), {
            phone: testSendForm.value.phone,
            message: testSendForm.value.message,
        });
        alert(res.data.message);
        showTestSendModal.value = false;
    } catch (e) {
        alert('Gagal test send: ' + (e.response?.data?.message || e.message));
    } finally {
        isSendingTest.value = false;
    }
};

// ── Settings Form ──
const settingsForm = useForm({
    wa_gateway_url: props.gatewaySettings?.gateway_url || 'http://127.0.0.1:3001',
    wa_anti_ban_delay_min: props.gatewaySettings?.anti_ban_delay_min || 5,
    wa_anti_ban_delay_max: props.gatewaySettings?.anti_ban_delay_max || 12,
    wa_batch_size: props.gatewaySettings?.batch_size || 20,
    wa_batch_pause: props.gatewaySettings?.batch_pause || 25,
});

const saveSettings = () => {
    settingsForm.post(route('whatsapp-blast.settings.update'), {
        preserveScroll: true,
        onSuccess: () => alert('Pengaturan WhatsApp Gateway berhasil disimpan!'),
    });
};

const formatRupiah = (num) => {
    return new Intl.NumberFormat('id-ID').format(num || 0);
};
</script>
