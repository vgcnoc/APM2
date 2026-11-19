<template>
    <AppLayout title="Mikrotik (NAS FreeRADIUS)">
        <div class="max-w-[1600px] w-full mx-auto space-y-6 font-sans px-4 sm:px-6 lg:px-8 py-6">
            <!-- Flash message banner -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <span class="text-base">✅</span>
                    <span>{{ $page.props.flash.success }}</span>
                </div>
                <button @click="$page.props.flash.success = null" class="text-emerald-500 hover:text-emerald-700 text-xs">✕</button>
            </div>
            <div v-if="$page.props.flash?.error" class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <span class="text-base">⚠️</span>
                    <span>{{ $page.props.flash.error }}</span>
                </div>
                <button @click="$page.props.flash.error = null" class="text-rose-500 hover:text-rose-700 text-xs">✕</button>
            </div>

            <!-- Configuration Pending / Applied Status Banner -->
            <div 
                class="p-4 sm:p-5 rounded-2xl border flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-xs transition-all"
                :class="configuration?.pending ? 'bg-amber-50/90 border-amber-200 text-amber-950' : 'bg-emerald-50/80 border-emerald-200 text-emerald-950'"
            >
                <div class="flex items-start gap-3">
                    <div 
                        class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 text-lg shadow-2xs"
                        :class="configuration?.pending ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'"
                    >
                        <span>{{ configuration?.pending ? '⚠️' : '🛡️' }}</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-extrabold tracking-tight">
                                {{ configuration?.pending ? 'Konfigurasi NAS Belum Diterapkan ke FreeRADIUS' : 'Konfigurasi NAS Sudah Diterapkan & Aktif' }}
                            </h2>
                            <span 
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                :class="configuration?.pending ? 'bg-amber-200/70 text-amber-900' : 'bg-emerald-200/70 text-emerald-900'"
                            >
                                {{ configuration?.pending ? 'Pending Reload' : 'Live Synced' }}
                            </span>
                        </div>
                        <p class="text-xs mt-0.5" :class="configuration?.pending ? 'text-amber-800' : 'text-emerald-800'">
                            Satu router menggunakan satu IP sumber RADIUS. IP VPN OpenVPN dialokasikan otomatis tanpa wildcard.
                            <span v-if="configuration?.applied_at" class="font-mono text-[11px] block sm:inline sm:ml-1">
                                (Terakhir diterapkan: {{ formatDateTime(configuration.applied_at) }})
                            </span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 shrink-0">
                    <button 
                        v-if="configuration?.enabled" 
                        type="button" 
                        @click="applyConfiguration" 
                        :disabled="isApplying"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white rounded-xl text-xs font-extrabold shadow-sm hover:shadow transition-all flex items-center gap-2 disabled:opacity-60 cursor-pointer"
                    >
                        <svg v-if="isApplying" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ isApplying ? 'Memvalidasi & Menerapkan...' : 'Validasi & Terapkan NAS' }}</span>
                    </button>
                </div>
            </div>

            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
                        <span class="p-2.5 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center shadow-xs">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                            </svg>
                        </span>
                        <span>Mikrotik (NAS FreeRADIUS)</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Generator script konfigurasi RouterOS Mikrotik, manajemen klien RADIUS, OpenVPN tunnel, dan sinkronisasi live.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link 
                        href="/routers" 
                        class="px-4 py-2.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-2xs"
                    >
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2" />
                        </svg>
                        <span>Ke Data Router</span>
                    </Link>

                    <button 
                        @click="openCreateModal" 
                        class="px-4 sm:px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white rounded-xl text-xs font-extrabold shadow-sm hover:shadow transition-all flex items-center gap-2 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah NAS Baru</span>
                    </button>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total NAS Klien</span>
                        <div class="text-2xl font-black text-slate-900 mt-1 font-mono">{{ stats?.total_nas || nas.total || 0 }}</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Router terdaftar di FreeRADIUS</div>
                    </div>
                    <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center shadow-2xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-600">NAS / Router Online</span>
                        <div class="text-2xl font-black text-emerald-700 mt-1 font-mono">
                            {{ countOnlineNas }}
                            <span class="text-xs font-normal text-slate-400">/ {{ nas.data?.length || 0 }}</span>
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">API terhubung &amp; responsif</div>
                    </div>
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center shadow-2xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-600">NAS / Router Offline</span>
                        <div class="text-2xl font-black text-rose-700 mt-1 font-mono">
                            {{ countOfflineNas }}
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Periksa tunnel VPN router</div>
                    </div>
                    <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center shadow-2xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-sky-600">Status FreeRADIUS</span>
                        <div class="text-base font-extrabold text-slate-900 mt-1 flex items-center gap-1.5">
                            <span 
                                class="w-2.5 h-2.5 rounded-full"
                                :class="configuration?.enabled ? (configuration.pending ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500') : 'bg-slate-400'"
                            ></span>
                            <span>{{ configuration?.enabled ? (configuration.pending ? 'Perlu Diterapkan' : 'Aktif & Mutakhir') : 'Nonaktif' }}</span>
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Daemon FreeRADIUS VPS</div>
                    </div>
                    <div class="w-12 h-12 bg-sky-50 text-sky-600 rounded-2xl flex items-center justify-center shadow-2xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Script Generator Box -->
            <div id="script-generator-section" class="bg-white rounded-3xl shadow-xs border border-slate-200/80 overflow-hidden">
                <!-- Section Header -->
                <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/50">
                    <div>
                        <h2 class="text-lg font-extrabold text-slate-900 flex items-center gap-2">
                            <span>💻</span>
                            <span>Generator Script Konfigurasi Router MikroTik</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Pilih router tujuan untuk men-generate script RouterOS yang siap dipastekan ke terminal.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button 
                            @click="openCreateModal" 
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition-all shadow-2xs flex items-center gap-1.5 cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Generate Script Baru</span>
                        </button>

                        <button 
                            v-if="selectedNasId" 
                            @click="forceRegenerate" 
                            class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold rounded-xl transition-all shadow-2xs flex items-center gap-1.5 cursor-pointer"
                            title="Generate ulang password VPN & API router ini"
                        >
                            <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Regenerate Kredensial (Force)</span>
                        </button>
                    </div>
                </div>

                <div class="p-6 space-y-5">
                    <!-- Petunjuk & Aturan Penting -->
                    <div class="bg-amber-50/70 border border-amber-200/80 rounded-2xl p-4 text-xs text-amber-900 space-y-2">
                        <div class="font-extrabold flex items-center gap-2 text-amber-950">
                            <span class="text-base">📌</span>
                            <span>Catatan Penting Sebelum Paste Script ke Terminal MikroTik:</span>
                        </div>
                        <ul class="grid grid-cols-1 md:grid-cols-2 gap-2 text-[11px] leading-relaxed text-amber-900 font-medium">
                            <li class="flex items-start gap-1.5">
                                <span class="text-amber-500 shrink-0 font-bold">•</span>
                                <span>Pastikan item RADIUS <strong>VIZBILL</strong> berada di urutan teratas pada menu <em>MikroTik &gt; Radius</em>.</span>
                            </li>
                            <li class="flex items-start gap-1.5">
                                <span class="text-amber-500 shrink-0 font-bold">•</span>
                                <span>Jika menggunakan <strong>Load Balancing / Multi-WAN</strong>, routing IP VPS/Tunnel ke 1 sumber internet stabil.</span>
                            </li>
                            <li class="flex items-start gap-1.5">
                                <span class="text-amber-500 shrink-0 font-bold">•</span>
                                <span>Cukup copy dan paste seluruh isi script ke <strong>New Terminal Winbox</strong> satu kali saja.</span>
                            </li>
                            <li class="flex items-start gap-1.5">
                                <span class="text-rose-600 shrink-0 font-bold">•</span>
                                <span class="text-rose-800 font-bold">JANGAN paste script yang sama ke lebih dari 1 router MikroTik!</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Dropdowns Selector -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Versi RouterOS</label>
                            <select 
                                v-model="selectedOs" 
                                @change="generateScript" 
                                class="w-full bg-slate-50 border border-slate-200 text-slate-900 font-bold rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white px-4 py-2.5 text-xs transition-all"
                            >
                                <option value="v7">RouterOS v7 (Rekomendasi Modern)</option>
                                <option value="v6">RouterOS v6 (Legacy)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Nama Router / NAS Tujuan</label>
                            <select 
                                v-model="selectedNasId" 
                                @change="generateScript" 
                                class="w-full bg-slate-50 border border-slate-200 text-slate-900 font-bold rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white px-4 py-2.5 text-xs transition-all"
                            >
                                <option value="">-- Pilih Router (Atau Buat Baru) --</option>
                                <option v-for="n in nas.data" :key="n.id" :value="n.id">
                                    {{ n.shortname || n.nasname }} ({{ n.vpn_ip ? `VPN: ${n.vpn_ip}` : n.nasname }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Terminal Script Box -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-extrabold uppercase tracking-wider text-slate-600 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                </svg>
                                <span>Output Script RouterOS</span>
                            </span>

                            <button 
                                v-if="generatedScript" 
                                @click="copyScript" 
                                class="px-3.5 py-1.5 rounded-xl text-xs font-extrabold transition-all flex items-center gap-1.5 cursor-pointer shadow-2xs"
                                :class="isScriptCopied ? 'bg-emerald-600 text-white' : 'bg-indigo-600 hover:bg-indigo-700 text-white'"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span>{{ isScriptCopied ? 'Tersalin ke Clipboard! ✅' : 'Salin Script Terminal' }}</span>
                            </button>
                        </div>

                        <div class="relative">
                            <textarea 
                                :value="generatedScript" 
                                readonly 
                                rows="15" 
                                class="w-full bg-slate-900 font-mono text-emerald-400 text-xs border border-slate-800 rounded-2xl p-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed selection:bg-indigo-600 selection:text-white"
                                placeholder="Silakan pilih router di atas untuk memunculkan script konfigurasi..."
                            ></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Management Table (Daftar NAS Terdaftar) -->
            <div class="bg-white rounded-3xl shadow-xs border border-slate-200/80 overflow-hidden">
                <!-- Toolbar -->
                <div class="px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/50">
                    <div class="flex items-center gap-2.5">
                        <h3 class="font-extrabold text-slate-900 text-base">Daftar Klien Router (NAS) Terdaftar</h3>
                        <span class="text-xs bg-slate-200/70 text-slate-700 font-bold px-2 py-0.5 rounded-full font-mono">
                            {{ nas.total || nas.data?.length || 0 }}
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <!-- Search Input with Debounce -->
                        <div class="relative w-full sm:w-72">
                            <input 
                                v-model="searchQuery" 
                                @input="onSearchInput"
                                type="text" 
                                placeholder="Cari nama, IP NAS, IP VPN..." 
                                class="w-full pl-9 pr-8 py-2 text-xs bg-white border border-slate-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all font-medium text-slate-800"
                            />
                            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <button 
                                v-if="searchQuery" 
                                @click="clearSearch" 
                                class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 text-xs font-bold"
                            >
                                ✕
                            </button>
                        </div>

                        <!-- Cek Semua Koneksi -->
                        <button 
                            @click="checkConnections(true)" 
                            :disabled="isPingingAll"
                            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50 shadow-2xs"
                            title="Uji ulang semua koneksi NAS"
                        >
                            <svg 
                                class="w-3.5 h-3.5 text-slate-600" 
                                :class="{ 'animate-spin': isPingingAll }"
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>{{ isPingingAll ? 'Memeriksa...' : 'Cek Koneksi' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200/80">
                                <th class="px-6 py-4 text-[11px] font-black uppercase tracking-wider text-slate-500">IP Sumber NAS &amp; VPN</th>
                                <th class="px-6 py-4 text-[11px] font-black uppercase tracking-wider text-slate-500">Nama Router / NAS</th>
                                <th class="px-6 py-4 text-[11px] font-black uppercase tracking-wider text-slate-500">Tipe &amp; CoA Port</th>
                                <th class="px-6 py-4 text-[11px] font-black uppercase tracking-wider text-slate-500">RADIUS Secret</th>
                                <th class="px-6 py-4 text-[11px] font-black uppercase tracking-wider text-slate-500">Status Koneksi</th>
                                <th class="px-6 py-4 text-[11px] font-black uppercase tracking-wider text-slate-500 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr 
                                v-for="n in nas.data" 
                                :key="n.id" 
                                class="hover:bg-slate-50/70 transition-colors"
                                :class="{ 'bg-indigo-50/30': selectedNasId === n.id }"
                            >
                                <!-- IP Sumber NAS & VPN -->
                                <td class="px-6 py-4">
                                    <div class="text-xs font-black font-mono text-slate-900 tracking-tight">{{ n.nasname }}</div>
                                    <div v-if="n.vpn_ip" class="inline-flex items-center gap-1 text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded text-[10px] font-mono font-bold mt-1 border border-indigo-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                        VPN: {{ n.vpn_ip }}
                                    </div>
                                    <div v-else class="text-[10px] text-slate-400 italic mt-0.5">Direct IP / Tanpa VPN</div>
                                </td>

                                <!-- Nama Router / NAS -->
                                <td class="px-6 py-4">
                                    <div class="text-xs font-bold text-slate-900">{{ n.shortname || '-' }}</div>
                                    <div v-if="n.description" class="text-[10px] text-slate-400 mt-0.5 max-w-48 truncate">{{ n.description }}</div>
                                </td>

                                <!-- Tipe & CoA -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-slate-100 text-slate-700">
                                            {{ n.type || 'mikrotik' }}
                                        </span>
                                        <span class="text-[11px] font-mono text-slate-500" title="Port CoA / Disconnect">
                                            CoA: {{ n.coa_port || 3799 }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Secret -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs text-slate-700 bg-slate-100 px-2 py-1 rounded-md border border-slate-200/60">
                                            {{ showSecretMap[n.id] ? n.secret : '••••••••' }}
                                        </span>
                                        <button 
                                            type="button" 
                                            @click="toggleSecret(n.id)" 
                                            class="p-1 text-slate-400 hover:text-indigo-600 transition-colors"
                                            :title="showSecretMap[n.id] ? 'Sembunyikan secret' : 'Tampilkan secret'"
                                        >
                                            <svg v-if="!showSecretMap[n.id]" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>

                                <!-- Status Koneksi -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div>
                                            <span 
                                                v-if="connectionStatuses[n.id] === 'online'" 
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                Online
                                            </span>
                                            <span 
                                                v-else-if="connectionStatuses[n.id] === 'offline'" 
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 shadow-2xs"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                Offline
                                            </span>
                                            <span 
                                                v-else 
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200"
                                            >
                                                <svg class="animate-spin h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Memeriksa...
                                            </span>
                                        </div>

                                        <!-- Refresh single connection -->
                                        <button 
                                            @click="pingSingleNas(n.id, true)"
                                            :disabled="connectionStatuses[n.id] === 'loading'"
                                            class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-indigo-600 transition-colors disabled:opacity-40 cursor-pointer"
                                            title="Uji ulang koneksi ke router ini"
                                        >
                                            <svg 
                                                class="w-3.5 h-3.5" 
                                                :class="{ 'animate-spin': connectionStatuses[n.id] === 'loading' }" 
                                                fill="none" 
                                                stroke="currentColor" 
                                                viewBox="0 0 24 24"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p 
                                        v-if="connectionDetails[n.id]?.message" 
                                        class="mt-1 text-[11px] text-slate-500 max-w-52 leading-tight"
                                    >
                                        {{ connectionDetails[n.id].message }}
                                    </p>
                                </td>

                                <!-- Aksi -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Tombol Pilih & Lihat Script -->
                                        <button 
                                            @click="viewNasScript(n.id)" 
                                            class="px-2.5 py-1.5 rounded-xl text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200/60 shadow-2xs transition-colors flex items-center gap-1 cursor-pointer"
                                            title="Tampilkan script MikroTik untuk NAS ini"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                            </svg>
                                            <span>Script</span>
                                        </button>

                                        <!-- Edit Button -->
                                        <button 
                                            @click="openEditModal(n)" 
                                            class="px-2.5 py-1.5 rounded-xl text-xs font-bold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200/80 shadow-2xs transition-colors flex items-center gap-1 cursor-pointer"
                                            title="Edit data NAS"
                                        >
                                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            <span>Edit</span>
                                        </button>

                                        <!-- Hapus Button -->
                                        <button 
                                            @click="confirmDeleteNas(n)" 
                                            class="px-2.5 py-1.5 rounded-xl text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200/80 shadow-2xs transition-colors flex items-center gap-1 cursor-pointer"
                                            title="Hapus data NAS"
                                        >
                                            <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty Row -->
                            <tr v-if="!nas.data || nas.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                    <div class="max-w-xs mx-auto space-y-2">
                                        <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 mx-auto">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div class="text-sm font-bold text-slate-800">Tidak ada data NAS</div>
                                        <p class="text-xs text-slate-400">
                                            {{ searchQuery ? 'Tidak ada NAS yang sesuai dengan pencarian Anda.' : 'Belum ada NAS router yang terdaftar di sistem.' }}
                                        </p>
                                        <button 
                                            v-if="searchQuery" 
                                            @click="clearSearch" 
                                            class="mt-2 text-xs text-indigo-600 font-bold hover:underline"
                                        >
                                            Reset Pencarian
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500" v-if="nas.total > 0">
                    <div>
                        Menampilkan <span class="font-bold text-slate-700">{{ nas.from || 0 }}</span> - <span class="font-bold text-slate-700">{{ nas.to || 0 }}</span> dari <span class="font-bold text-slate-700">{{ nas.total }}</span> Klien NAS
                    </div>
                    <div class="flex items-center gap-1.5" v-if="nas.links && nas.links.length > 3">
                        <template v-for="(link, idx) in nas.links" :key="idx">
                            <Link 
                                v-if="link.url" 
                                :href="link.url" 
                                v-html="link.label" 
                                class="px-3 py-1.5 border rounded-xl text-xs font-bold transition-colors" 
                                :class="link.active ? 'bg-indigo-600 text-white border-indigo-600 shadow-2xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                                preserve-state
                            />
                            <span 
                                v-else 
                                v-html="link.label" 
                                class="px-3 py-1.5 border rounded-xl bg-slate-50 text-slate-400 border-slate-200 text-xs cursor-not-allowed"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tambah/Edit NAS -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg my-auto overflow-hidden border border-slate-100 animate-in fade-in zoom-in-95 duration-200">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900">{{ isEditing ? 'Edit Klien NAS' : 'Tambah Klien NAS Baru' }}</h3>
                            <p class="text-xs text-slate-500">Konfigurasi alamat FreeRADIUS, secret, dan port CoA.</p>
                        </div>
                    </div>
                    <button @click="closeModal" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center text-xs font-bold transition-colors">
                        ✕
                    </button>
                </div>
                
                <form @submit.prevent="submit">
                    <div class="p-6 space-y-4 max-h-[72vh] overflow-y-auto">
                        <!-- Helper Text -->
                        <div class="bg-indigo-50/70 text-indigo-900 p-3.5 rounded-2xl text-xs border border-indigo-200/80 space-y-1">
                            <div class="font-extrabold flex items-center gap-1.5">
                                <span>ℹ️</span>
                                <span>Alokasi Alamat IP NAS:</span>
                            </div>
                            <p class="text-indigo-800 leading-relaxed">
                                Pilih opsi VPN untuk mendapatkan IP tunnel unik otomatis. Jika direct connection, gunakan IP spesifik. Wildcard <code>0.0.0.0/0</code> dilarang.
                            </p>
                        </div>

                        <!-- Opsi VPN & nasname -->
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/70 space-y-3">
                            <label v-if="!isEditing && vpn?.enabled" class="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
                                <input type="checkbox" v-model="form.use_vpn" class="rounded text-indigo-600 focus:ring-indigo-500" />
                                <span>Gunakan OpenVPN Tunnel Billing (Alokasi IP Otomatis)</span>
                            </label>

                            <div>
                                <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">IP Sumber NAS (nasname)</label>
                                <input 
                                    v-model="form.nasname" 
                                    type="text" 
                                    :readonly="form.use_vpn" 
                                    :required="!form.use_vpn" 
                                    class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-mono font-bold focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900" 
                                    :placeholder="form.use_vpn ? 'Dialokasikan otomatis dari pool OpenVPN' : 'IP sumber router, contoh: 192.168.1.1'" 
                                />
                                <p v-if="form.errors.nasname" class="text-rose-600 text-xs mt-1 font-medium">{{ form.errors.nasname }}</p>
                            </div>
                        </div>
                        
                        <!-- Shortname -->
                        <div>
                            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Shortname (Nama Singkat Router)</label>
                            <input 
                                v-model="form.shortname" 
                                type="text" 
                                class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900 uppercase" 
                                placeholder="Contoh: MIKROTIK-PUSAT" 
                            />
                            <p v-if="form.errors.shortname" class="text-rose-600 text-xs mt-1 font-medium">{{ form.errors.shortname }}</p>
                        </div>
                        
                        <!-- Type -->
                        <div>
                            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Tipe Klien NAS</label>
                            <select 
                                v-model="form.type" 
                                class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="mikrotik">Mikrotik (RouterOS)</option>
                                <option value="other">Other / Standard NAS</option>
                            </select>
                        </div>
                        
                        <!-- RADIUS Secret -->
                        <div>
                            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">RADIUS Secret Key</label>
                            <div class="flex gap-2">
                                <input 
                                    v-model="form.secret" 
                                    type="text" 
                                    class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-mono font-bold focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900" 
                                    placeholder="Ketik secret key..." 
                                    required 
                                />
                                <button 
                                    type="button" 
                                    @click="generateRandomSecret" 
                                    class="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold shrink-0 shadow-2xs transition-colors cursor-pointer"
                                >
                                    Acak
                                </button>
                            </div>
                            <p v-if="form.errors.secret" class="text-rose-600 text-xs mt-1 font-medium">{{ form.errors.secret }}</p>
                        </div>

                        <!-- CoA Port -->
                        <div>
                            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-1.5">Port CoA / Disconnect</label>
                            <input 
                                v-model="form.coa_port" 
                                type="number" 
                                min="1" 
                                max="65535" 
                                required 
                                class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-mono font-bold focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-900" 
                            />
                            <p class="text-[11px] text-slate-400 mt-1">Default FreeRADIUS Change of Authorization: 3799.</p>
                            <p v-if="form.errors.coa_port" class="text-rose-600 text-xs mt-1 font-medium">{{ form.errors.coa_port }}</p>
                        </div>
                    </div>
                    
                    <div class="bg-slate-50/80 px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button 
                            type="button" 
                            @click="closeModal" 
                            class="px-5 py-2.5 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors shadow-2xs"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing" 
                            class="px-6 py-2.5 text-xs font-extrabold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 disabled:opacity-50 transition-all shadow-sm hover:shadow flex items-center gap-2 cursor-pointer"
                        >
                            <svg v-if="form.processing" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ isEditing ? 'Simpan Perubahan' : 'Simpan & Generate Script' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    nas: Object,
    stats: Object,
    filters: Object,
    serverIp: String,
    vpn: Object,
    clientPool: Object,
    isolirUrl: String,
    configuration: Object,
    createdNasId: Number,
});

// Search State
const searchQuery = ref(props.filters?.search || '');
let searchTimer = null;

function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        applySearch();
    }, 350);
}

function clearSearch() {
    searchQuery.value = '';
    applySearch();
}

function applySearch() {
    router.get('/radius/nas', {
        search: searchQuery.value || undefined,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}

// Modal State
const isModalOpen = ref(false);
const isEditing = ref(false);
const editId = ref(null);

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

function openCreateModal() {
    isEditing.value = false;
    editId.value = null;
    form.reset();
    form.nasname = '';
    form.use_vpn = Boolean(props.vpn?.enabled);
    form.type = 'mikrotik';
    form.coa_port = 3799;
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
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/radius/nas', {
            preserveScroll: true,
            onSuccess: (page) => {
                closeModal();
                setTimeout(() => {
                    const list = page.props.nas?.data || [];
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

// Secret Toggle Map
const showSecretMap = ref({});
function toggleSecret(id) {
    showSecretMap.value[id] = !showSecretMap.value[id];
}

// Script Generation
const selectedOs = ref('v7');
const selectedNasId = ref('');
const generatedScript = ref('');
const isScriptCopied = ref(false);

function rosEscape(src) {
    if (!src) return '';
    return String(src)
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

    const nasItem = props.nas?.data?.find(n => n.id === selectedNasId.value);
    if (!nasItem) {
        generatedScript.value = '';
        return;
    }

    const v6 = selectedOs.value === 'v6';
    const publicIp = props.serverIp || window.location.hostname || '157.66.140.17';
    const vpnOn = props.vpn?.enabled && nasItem.vpn_user && nasItem.vpn_ip;
    const radiusIp = vpnOn ? (props.vpn?.gateway || '10.254.0.1') : publicIp;
    const radiusHostMask = radiusIp.includes(':') ? 128 : 32;
    const endpoints = (props.vpn?.endpoints?.length ? props.vpn.endpoints : [publicIp]);
    const backup = props.vpn?.backup || null;
    const backupOn = Boolean(vpnOn && backup?.enabled && backup?.l2tp_enabled && backup?.endpoint && backup?.ipsec_secret);
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

    L.push(`### 1. USER API (MONITORING DAN DISABLE/ENABLE KONEKSI PELANGGAN) ###`);
    L.push(`:if ([:len [/user group find name="VIZBILL-API"]] = 0) do={ /user group add name="VIZBILL-API" policy=read,write,api,test } else={ /user group set [find name="VIZBILL-API"] policy=read,write,api,test };`);
    L.push(`/user rem [find comment~"APM2"];`);
    L.push(`/user rem [find comment~"VIZBILL"];`);
    L.push(`/user add name="${apiUser}" password="${apiPass}" group="VIZBILL-API" address=${radiusIp}/${radiusHostMask} comment="USER FOR VIZBILL BILLING API (DON'T CHANGE IT)";`);
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
    profiles.forEach(p => L.push(`/ip hotspot user profile add name=${p.name} mac-cookie-timeout=${p.cookie} status-autorefresh=${p.refresh} keepalive-timeout=${p.keepalive} shared-users=1;`));
    L.push(`/ip hotspot user profile set [find default=yes] keepalive-timeout=2m mac-cookie-timeout=1w shared-users=1 status-autorefresh=1m;`);
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
        L.push(`### 7. VPN OPENVPN UTAMA${backupOn ? ' + L2TP/IPSEC CADANGAN' : ''} KE SERVER VIZBILL ###`);
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
            L.push(`/interface ovpn-client add disabled=${i === 0 ? 'no' : 'yes'} connect-to=${ep} port=1195 name="VIZBILL-VPN-${i + 1}" profile=VIZBILLVPN user="${nasItem.vpn_user}" password="${nasItem.vpn_password}" add-default-route=no cipher=aes256 auth=sha1 certificate=none comment="OpenVPN NAS billing; IP tunnel: ${nasItem.vpn_ip}";`);
        });
        if (backupOn) {
            L.push(`/interface l2tp-client add disabled=yes connect-to=${backup.endpoint} name="VIZBILL-VPN-BACKUP-L2TP" profile=VIZBILLVPN user="${nasItem.vpn_user}" password="${nasItem.vpn_password}" use-ipsec=yes ipsec-secret="${rosEscape(backup.ipsec_secret)}" add-default-route=no comment="L2TP/IPsec backup NAS billing; IP tunnel: ${nasItem.vpn_ip}";`);
        }
        L.push(``);

        L.push(`### 8. STATIC ROUTE RADIUS LEWAT VPN ###`);
        L.push(`/ip route remove [find comment="STATIC ROUTE BY APM2"];`);
        L.push(`/ip route remove [find comment="STATIC ROUTE BY VIZBILL"];`);
        L.push(`/ip route add dst-address=${radiusIp}/32 gateway=VIZBILL-VPN-1 comment="STATIC ROUTE BY VIZBILL";`);
        L.push(``);

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
    L.push(`#   Host API      : ${vpnOn ? nasItem.vpn_ip + ' (IP OpenVPN)' : (nasItem.nasname || '(IP router)')}`);
    L.push(`#   Port API      : ${apiPort}`);
    L.push(`#   Username API  : ${apiUser}`);
    L.push(`#   Password API  : ${apiPass}`);

    generatedScript.value = L.join('\n');
}

function viewNasScript(nasId) {
    selectedNasId.value = nasId;
    generateScript();
    const el = document.getElementById('script-generator-section');
    if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
    }
}

async function copyScript() {
    if (generatedScript.value) {
        try {
            await navigator.clipboard.writeText(generatedScript.value);
            isScriptCopied.value = true;
            setTimeout(() => {
                isScriptCopied.value = false;
            }, 3000);
        } catch (err) {
            console.error('Failed to copy: ', err);
        }
    }
}

function forceRegenerate() {
    if (!selectedNasId.value) return;
    Swal.fire({
        title: 'Regenerate Kredensial VPN & API?',
        text: 'Password VPN dan API router ini akan diganti. Script lama di MikroTik tidak akan bisa konek lagi sampai Anda paste script baru ini ke terminal MikroTik. Lanjutkan?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Generate Ulang',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-3xl',
            confirmButton: 'rounded-xl text-xs font-bold px-4 py-2.5',
            cancelButton: 'rounded-xl text-xs font-bold px-4 py-2.5',
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(`/radius/nas/${selectedNasId.value}/regenerate`, {}, {
                preserveScroll: true,
                onSuccess: () => generateScript(),
            });
        }
    });
}

function confirmDeleteNas(nasItem) {
    Swal.fire({
        title: `Hapus Klien NAS ${nasItem.shortname || nasItem.nasname}?`,
        html: `
            <div class="text-xs text-slate-600">
                <p>Data NAS FreeRADIUS ini akan dihapus permanen.</p>
                <p class="mt-2 text-rose-700 bg-rose-50 p-2.5 rounded-xl font-medium border border-rose-200">
                    Perhatian: Pelanggan yang terhubung melalui router ini tidak akan bisa melakukan autentikasi RADIUS sampai router dihubungkan kembali.
                </p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus NAS',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-3xl',
            confirmButton: 'rounded-xl text-xs font-bold px-4 py-2.5',
            cancelButton: 'rounded-xl text-xs font-bold px-4 py-2.5',
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(`/radius/nas/${nasItem.id}/delete`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    if (selectedNasId.value === nasItem.id) {
                        selectedNasId.value = '';
                        generatedScript.value = '';
                    }
                }
            });
        }
    });
}

const isApplying = ref(false);
function applyConfiguration() {
    Swal.fire({
        title: 'Terapkan Konfigurasi NAS?',
        text: 'Sistem akan memvalidasi skema NAS dan memuat ulang daemon FreeRADIUS di VPS secara aman.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Terapkan Sekarang',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-3xl',
            confirmButton: 'rounded-xl text-xs font-bold px-4 py-2.5',
            cancelButton: 'rounded-xl text-xs font-bold px-4 py-2.5',
        }
    }).then((result) => {
        if (result.isConfirmed) {
            isApplying.value = true;
            router.post('/radius/nas/apply', {}, {
                preserveScroll: true,
                onFinish: () => {
                    isApplying.value = false;
                }
            });
        }
    });
}

// Connection Testing
const connectionStatuses = ref({});
const connectionDetails = ref({});
const isPingingAll = ref(false);

const countOnlineNas = computed(() => {
    return Object.values(connectionStatuses.value).filter(s => s === 'online').length;
});

const countOfflineNas = computed(() => {
    return Object.values(connectionStatuses.value).filter(s => s === 'offline').length;
});

function pingSingleNas(nasId, forceRefresh = false) {
    connectionStatuses.value[nasId] = 'loading';
    const url = `/radius/nas/${nasId}/ping${forceRefresh ? '?refresh=1' : ''}`;
    axios.get(url)
        .then(res => {
            connectionStatuses.value[nasId] = res.data.status || 'offline';
            connectionDetails.value[nasId] = res.data;
        })
        .catch(err => {
            connectionStatuses.value[nasId] = 'offline';
            connectionDetails.value[nasId] = {
                status: 'offline',
                message: err?.response?.data?.message || 'Gagal menghubungi server.',
            };
        });
}

function checkConnections(forceRefresh = false) {
    if (!props.nas?.data || props.nas.data.length === 0) return;
    isPingingAll.value = true;
    let completed = 0;
    const total = props.nas.data.length;

    props.nas.data.forEach(n => {
        connectionStatuses.value[n.id] = 'loading';
        const url = `/radius/nas/${n.id}/ping${forceRefresh ? '?refresh=1' : ''}`;
        axios.get(url)
            .then(res => {
                connectionStatuses.value[n.id] = res.data.status || 'offline';
                connectionDetails.value[n.id] = res.data;
            })
            .catch(err => {
                connectionStatuses.value[n.id] = 'offline';
                connectionDetails.value[n.id] = {
                    status: 'offline',
                    message: err?.response?.data?.message || 'Gagal menghubungi server.',
                };
            })
            .finally(() => {
                completed++;
                if (completed >= total) {
                    isPingingAll.value = false;
                }
            });
    });
}

function formatDateTime(val) {
    if (!val) return '';
    try {
        const d = new Date(val);
        return d.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
    } catch {
        return val;
    }
}

// Lifecycle
onMounted(() => {
    if (!selectedNasId.value && props.nas?.data?.length) {
        selectedNasId.value = props.nas.data[0].id;
    }
    generateScript();
    checkConnections();
});

watch(() => props.nas?.data, () => {
    if (!props.nas?.data?.some(n => n.id === selectedNasId.value)) {
        selectedNasId.value = props.nas?.data?.[0]?.id || '';
    }
    generateScript();
    checkConnections();
});
</script>
