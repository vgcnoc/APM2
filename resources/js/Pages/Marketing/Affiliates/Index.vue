<template>
    <AppLayout title="Manajemen Marketing & Afiliasi Sales">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 font-sans">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
                        <span class="p-2 bg-indigo-50 text-indigo-600 rounded-xl text-xl">🤝</span>
                        <span>Marketing &amp; Afiliasi Sales</span>
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Kelola jaringan agen marketing, komisi berjenjang (multi-tier), pendaftaran publik, dan pencairan komisi.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <a
                        :href="route('register-affiliate')"
                        target="_blank"
                        class="px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5"
                    >
                        <span>🔗 Buka Halaman Daftar Publik</span>
                        <span>&nearr;</span>
                    </a>
                </div>
            </div>

            <!-- KPI Cards Overview -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Agen Mitra</span>
                        <span class="text-2xl font-extrabold text-slate-900 mt-1 block">{{ kpi.total_affiliates }} Agen</span>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
                        👥
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Pelanggan Hasil Referral</span>
                        <span class="text-2xl font-extrabold text-slate-900 mt-1 block">{{ kpi.total_customers }} Pelanggan</span>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                        🎯
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Komisi Disalurkan</span>
                        <span class="text-xl font-extrabold text-emerald-600 mt-1 block">{{ formatRupiah(kpi.total_commissions_paid) }}</span>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                        💰
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Menunggu Pencairan</span>
                        <span class="text-xl font-extrabold text-amber-600 mt-1 block">{{ kpi.pending_withdrawals_count }} Permintaan</span>
                        <span class="text-[11px] text-slate-400 font-medium">({{ formatRupiah(kpi.pending_withdrawals_amount) }})</span>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                        ⏳
                    </div>
                </div>
            </div>

            <!-- Tab Navigation Header -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden mb-8">
                <div class="p-4 border-b border-slate-100 flex items-center gap-2 overflow-x-auto">
                    <button
                        v-for="t in [
                            { id: 'settings', label: 'Pengaturan Komisi', icon: '⚙️' },
                            { id: 'diagram', label: 'Diagram Multi-Tier Affiliate', icon: '🌳' },
                            { id: 'affiliates', label: 'Daftar Agen Afiliasi (' + affiliates.total + ')', icon: '👥' },
                            { id: 'commissions', label: 'Riwayat Log Komisi', icon: '📜' },
                            { id: 'withdrawals', label: 'Permintaan Pencairan (' + withdrawals.total + ')', icon: '💸' },
                        ]"
                        :key="t.id"
                        @click="switchTab(t.id)"
                        class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all whitespace-nowrap flex items-center gap-1.5"
                        :class="currentTab === t.id ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                    >
                        <span>{{ t.icon }}</span>
                        <span>{{ t.label }}</span>
                    </button>
                </div>

                <!-- ════ TAB 1: PENGATURAN KOMISI SALES & AFILIASI (PERSIS SEPERTI SCREENSHOT) ════ -->
                <div v-if="currentTab === 'settings'" class="p-6 sm:p-8">
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-7 shadow-xs">
                        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100">
                            <div>
                                <h2 class="text-base font-bold text-slate-900">
                                    Pengaturan Komisi Sales &amp; Afiliasi
                                </h2>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Pengaturan nilai komisi untuk agen sales dan afiliasi bertingkat.
                                </p>
                            </div>
                            <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm shadow-2xs">
                                💲
                            </div>
                        </div>

                        <form @submit.prevent="submitSettings" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- KOMISI BOOKING (SEKALI BAYAR) -->
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                        KOMISI BOOKING (SEKALI BAYAR)
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-xs font-bold">
                                            Rp
                                        </div>
                                        <input
                                            v-model="settingsForm.affiliate_commission_booking"
                                            type="number"
                                            min="0"
                                            step="500"
                                            required
                                            class="block w-full pl-10 pr-3.5 py-2.5 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 font-bold text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                        />
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-1">Diberikan 1 kali saat pelanggan baru mendaftar &amp; aktif.</p>
                                </div>

                                <!-- KOMISI BULANAN (DIRECT SALES) -->
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                        KOMISI BULANAN (DIRECT SALES)
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-xs font-bold">
                                            Rp
                                        </div>
                                        <input
                                            v-model="settingsForm.affiliate_commission_monthly"
                                            type="number"
                                            min="0"
                                            step="500"
                                            required
                                            class="block w-full pl-10 pr-3.5 py-2.5 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 font-bold text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                        />
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-1">Diberikan setiap bulan saat invoice pelanggan tersebut lunas.</p>
                                </div>

                                <!-- KOMISI UPLINE (DOWNLINE 1) / BULAN -->
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                        KOMISI UPLINE (DOWNLINE 1) / BULAN
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-xs font-bold">
                                            Rp
                                        </div>
                                        <input
                                            v-model="settingsForm.affiliate_commission_upline_1"
                                            type="number"
                                            min="0"
                                            step="500"
                                            required
                                            class="block w-full pl-10 pr-3.5 py-2.5 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 font-bold text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                        />
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-1">Diberikan kepada sponsor level 1 dari invoice bulanan binaannya.</p>
                                </div>

                                <!-- KOMISI UPLINE (DOWNLINE 2) / BULAN -->
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                        KOMISI UPLINE (DOWNLINE 2) / BULAN
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-xs font-bold">
                                            Rp
                                        </div>
                                        <input
                                            v-model="settingsForm.affiliate_commission_upline_2"
                                            type="number"
                                            min="0"
                                            step="500"
                                            required
                                            class="block w-full pl-10 pr-3.5 py-2.5 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 font-bold text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                        />
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-1">Diberikan kepada sponsor level 2 dari invoice bulanan binaannya.</p>
                                </div>

                                <!-- MINIMAL PENARIKAN (WITHDRAWAL) -->
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                                        MINIMAL PENARIKAN (WITHDRAWAL)
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-xs font-bold">
                                            Rp
                                        </div>
                                        <input
                                            v-model="settingsForm.affiliate_min_withdraw"
                                            type="number"
                                            min="10000"
                                            step="5000"
                                            required
                                            class="block w-full pl-10 pr-3.5 py-2.5 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 font-bold text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                        />
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-1">Batas saldo minimal agar mitra dapat mengajukan pencairan.</p>
                                </div>

                                <!-- MINIMAL MASA KONTRAK PELANGGAN (BULAN) -->
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5 flex items-center gap-1.5">
                                        <span>MINIMAL KONTRAK PELANGGAN</span>
                                        <span class="bg-rose-100 text-rose-700 px-1.5 py-0.2 rounded text-[10px] uppercase font-black">AUTO-POTONG</span>
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model="settingsForm.affiliate_contract_min_months"
                                            type="number"
                                            min="1"
                                            max="60"
                                            step="1"
                                            required
                                            class="block w-full px-3.5 py-2.5 bg-slate-50/70 border border-slate-300 rounded-xl text-slate-900 font-bold text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                        />
                                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400 text-xs font-bold">
                                            Bulan
                                        </div>
                                    </div>
                                    <p class="text-[11px] text-rose-600 font-medium mt-1">Jika pelanggan stop sebelum masa ini, fee booking otomatis dipotong dari saldo afiliasi.</p>
                                </div>

                                <!-- TANGGAL PENCAIRAN BULANAN (MITRA LUAR) -->
                                <div class="md:col-span-2 bg-amber-50/70 border border-amber-200/80 p-4 rounded-2xl">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                        <div>
                                            <label class="block text-xs font-bold uppercase tracking-wider text-amber-950 mb-0.5 flex items-center gap-1.5">
                                                <span>📅 JADWAL PENCAIRAN FEE MITRA LUAR</span>
                                                <span class="bg-amber-200 text-amber-900 px-2 py-0.5 rounded text-[10px] uppercase font-black">KHUSUS NON-KARYAWAN</span>
                                            </label>
                                            <p class="text-xs text-amber-800">
                                                Pencairan saldo komisi untuk mitra luar hanya dapat diajukan mulai tanggal ini setiap bulannya dan dengan syarat pelanggan yang dibooking sudah lunas tagihannya.
                                            </p>
                                        </div>
                                        <div class="w-full sm:w-48 flex items-center gap-2">
                                            <span class="text-xs font-bold text-slate-700 whitespace-nowrap">Tiap Tanggal:</span>
                                            <div class="relative flex-1">
                                                <input
                                                    v-model="settingsForm.affiliate_payout_day"
                                                    type="number"
                                                    min="1"
                                                    max="31"
                                                    step="1"
                                                    required
                                                    class="block w-full px-3 py-2 bg-white border border-amber-300 rounded-xl text-slate-900 font-extrabold text-sm text-center focus:outline-none focus:ring-2 focus:ring-amber-500 transition"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-slate-100 flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="settingsForm.processing"
                                    class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-1.5 disabled:opacity-50"
                                >
                                    <svg v-if="settingsForm.processing" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span>Simpan Pengaturan Komisi</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ════ TAB DIAGRAM: MULTI-TIER AFFILIATE DIAGRAM (PERSIS SCREENSHOT - LEBIH RAPIH & CANGGIH) ════ -->
                <div v-if="currentTab === 'diagram'" class="p-6 sm:p-8 space-y-6">
                    <!-- Top Container Card with Diagram & List Toggle -->
                    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden">
                        
                        <!-- Top Bar: Title, Search, and View Mode Toggle -->
                        <div class="p-4 sm:p-6 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center text-xl shadow-2xs">
                                    🌳
                                </div>
                                <div>
                                    <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                                        Diagram Multi-Tier Affiliate
                                    </h2>
                                    <p class="text-xs text-slate-500">
                                        Visualisasi struktur hierarki jaringan pemasaran, hubungan Upline - Downline, dan pasif income.
                                    </p>
                                </div>
                            </div>

                            <!-- Right Controls: Search bar & View Toggle -->
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="relative w-full sm:w-72">
                                    <input
                                        v-model="treeSearch"
                                        type="text"
                                        placeholder="Cari pelanggan, transaksi, atau area..."
                                        class="w-full pl-9 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all shadow-2xs"
                                    />
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">🔍</span>
                                    <button
                                        v-if="treeSearch"
                                        @click="treeSearch = ''"
                                        type="button"
                                        class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 text-xs"
                                    >
                                        ✕
                                    </button>
                                </div>

                                <!-- View Mode Toggles (Persis Screenshot) -->
                                <div class="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200">
                                    <button
                                        type="button"
                                        @click="treeViewMode = 'diagram'"
                                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 transition-all"
                                        :class="treeViewMode === 'diagram' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
                                        <span>Tampilan Diagram</span>
                                    </button>
                                    <button
                                        type="button"
                                        @click="treeViewMode = 'list'"
                                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 transition-all"
                                        :class="treeViewMode === 'list' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                                        <span>Tampilan List</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 4 Top KPI Cards (Persis Screenshot: 2 Upline Aktif, 24 Downline 1, 4 Downline 2, 30 Total Member) -->
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 p-4 sm:p-6 bg-slate-50/50 border-b border-slate-100">
                            <!-- Card 1: Upline Aktif (Blue) -->
                            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-3.5 hover:border-blue-300 hover:shadow-xs transition-all">
                                <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0">
                                    👤
                                </div>
                                <div>
                                    <div class="text-xl sm:text-2xl font-black text-slate-900">{{ tree_kpi?.upline_count ?? 1 }}</div>
                                    <div class="text-xs font-semibold text-slate-500">Upline Aktif</div>
                                </div>
                            </div>

                            <!-- Card 2: Downline 1 (Green) -->
                            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-3.5 hover:border-emerald-300 hover:shadow-xs transition-all">
                                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                                    👥
                                </div>
                                <div>
                                    <div class="text-xl sm:text-2xl font-black text-slate-900">{{ tree_kpi?.downline_1_count ?? 10 }}</div>
                                    <div class="text-xs font-semibold text-slate-500">Downline 1</div>
                                </div>
                            </div>

                            <!-- Card 3: Downline 2 (Amber) -->
                            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-3.5 hover:border-amber-300 hover:shadow-xs transition-all">
                                <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
                                    👥
                                </div>
                                <div>
                                    <div class="text-xl sm:text-2xl font-black text-slate-900">{{ tree_kpi?.downline_2_count ?? 1 }}</div>
                                    <div class="text-xs font-semibold text-slate-500">Downline 2</div>
                                </div>
                            </div>

                            <!-- Card 4: Total Member (Purple) -->
                            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-3.5 hover:border-purple-300 hover:shadow-xs transition-all">
                                <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl shrink-0">
                                    🌐
                                </div>
                                <div>
                                    <div class="text-xl sm:text-2xl font-black text-slate-900">{{ tree_kpi?.total_members ?? 12 }}</div>
                                    <div class="text-xs font-semibold text-slate-500">Total Member</div>
                                </div>
                            </div>
                        </div>

                        <!-- ══ MODE A: TAMPILAN DIAGRAM POHON INTERAKTIF ══ -->
                        <div v-if="treeViewMode === 'diagram'" class="relative">
                            
                            <!-- Canvas Action Subheader: Upline Selector & Zoom Controls -->
                            <div class="p-3 sm:px-6 bg-white/80 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-600">Pohon Upline:</span>
                                    <select
                                        v-if="tree_data && tree_data.length > 1"
                                        v-model="selectedUplineIndex"
                                        class="px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                    >
                                        <option v-for="(u, idx) in tree_data" :key="u.id" :value="idx">
                                            {{ u.name }} ({{ u.l1_count || u.downlines?.length || 0 }} Downline)
                                        </option>
                                    </select>
                                    <span v-else class="font-bold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-100">
                                        {{ activeUplineTree?.name }}
                                    </span>
                                </div>

                                <!-- Zoom controls -->
                                <div class="flex items-center gap-1.5">
                                    <span class="text-slate-400 font-medium">Zoom:</span>
                                    <div class="flex items-center bg-slate-100 p-0.5 rounded-lg border border-slate-200">
                                        <button
                                            type="button"
                                            @click="setTreeZoom(-10)"
                                            class="w-6 h-6 rounded flex items-center justify-center font-bold text-slate-600 hover:bg-white transition-colors"
                                            title="Zoom Out"
                                        >
                                            -
                                        </button>
                                        <span class="text-[11px] font-mono font-bold text-slate-700 px-2">{{ treeZoom }}%</span>
                                        <button
                                            type="button"
                                            @click="setTreeZoom(10)"
                                            class="w-6 h-6 rounded flex items-center justify-center font-bold text-slate-600 hover:bg-white transition-colors"
                                            title="Zoom In"
                                        >
                                            +
                                        </button>
                                    </div>
                                    <button
                                        type="button"
                                        @click="setTreeZoom(0)"
                                        class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold rounded-lg text-[11px] transition-colors"
                                    >
                                        Fit 100%
                                    </button>
                                </div>
                            </div>

                            <!-- Interactive Tree Canvas with Wavy SVG Background -->
                            <div class="relative overflow-x-auto min-h-[580px] bg-gradient-to-b from-slate-50/40 via-white to-indigo-50/30">
                                <!-- Wavy background decoration (Persis Screenshot) -->
                                <div class="absolute inset-x-0 bottom-0 pointer-events-none overflow-hidden h-64 z-0">
                                    <svg class="w-full h-full text-indigo-100/60 fill-current" viewBox="0 0 1440 320" preserveAspectRatio="none">
                                        <path fill-opacity="1" d="M0,192L48,197.3C96,203,192,213,288,208C384,203,480,181,576,181.3C672,181,768,203,864,218.7C960,235,1056,245,1152,229.3C1248,213,1344,171,1392,149.3L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                                    </svg>
                                </div>

                                <!-- Tree Nodes Container (Scalable) -->
                                <div
                                    class="py-10 px-6 flex flex-col items-center min-w-max relative z-10 transition-transform duration-200 origin-top"
                                    :style="{ transform: `scale(${treeZoom / 100})` }"
                                >
                                    <!-- 1. ROOT UPLINE NODE -->
                                    <div class="flex flex-col items-center mb-0">
                                        <!-- Top Capsule Badge -->
                                        <div class="relative z-10 -mb-3">
                                            <span class="inline-flex items-center gap-1 px-4 py-1 rounded-full bg-blue-600 text-white text-[11px] font-extrabold uppercase tracking-widest shadow-sm">
                                                <span>UPLINE</span>
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                                            </span>
                                        </div>

                                        <!-- Card Box -->
                                        <div
                                            @click="openMemberModal(activeUplineTree, 'Upline Utama')"
                                            class="w-64 bg-white rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-lg transition-all p-4 cursor-pointer relative group"
                                            :class="isNodeMatched(activeUplineTree) ? 'ring-4 ring-indigo-400 border-indigo-500 scale-105' : ''"
                                        >
                                            <div class="flex items-start justify-between gap-2">
                                                <div class="flex items-center gap-2.5 min-w-0">
                                                    <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center font-bold text-sm shrink-0">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <h4 class="text-xs font-extrabold text-slate-900 truncate group-hover:text-blue-600 transition-colors">
                                                            {{ activeUplineTree?.name }}
                                                        </h4>
                                                        <span class="text-[10px] text-slate-400 font-mono block">
                                                            ID: {{ activeUplineTree?.referral_code || ('SL-202301-' + String(activeUplineTree?.id).padStart(4, '0')) }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-1 shrink-0">
                                                    <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-bold border border-emerald-200 flex items-center gap-1">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                        <span>Aktif</span>
                                                    </span>
                                                    <button type="button" @click.stop="openMemberModal(activeUplineTree, 'Upline Utama')" class="text-slate-400 hover:text-slate-700 p-1 rounded-md hover:bg-slate-50">
                                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="2"></circle><circle cx="12" cy="12" r="2"></circle><circle cx="12" cy="19" r="2"></circle></svg>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Bottom Info Row -->
                                            <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                                                <div class="flex items-center gap-1">
                                                    <span>👤</span>
                                                    <span>Komisi : <b class="text-slate-700">0%</b></span>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <span>👥</span>
                                                    <span class="font-semibold text-blue-600">{{ activeUplineTree?.l1_count || activeUplineTree?.downlines?.length || 10 }} Downline 1</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Connector Circle (Checkmark) -->
                                        <div class="relative z-10 -mt-2.5">
                                            <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px] font-black shadow-xs ring-4 ring-white">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                            </div>
                                        </div>

                                        <!-- Vertical Stem Line to horizontal bus -->
                                        <div class="w-0.5 h-7 bg-blue-300"></div>
                                    </div>

                                    <!-- 2. DOWNLINE 1 BUS BAR & CARDS ROW -->
                                    <div v-if="activeUplineTree?.downlines && activeUplineTree.downlines.length > 0" class="flex justify-center items-start min-w-max px-4">
                                        <div
                                            v-for="(d1, idx) in activeUplineTree.downlines"
                                            :key="d1.id"
                                            class="flex flex-col items-center"
                                        >
                                            <!-- Responsive Bus Line segment -->
                                            <div class="w-full flex h-6">
                                                <div :class="['h-full border-t-2 border-blue-300 flex-1', idx === 0 ? 'invisible' : '']"></div>
                                                <div class="w-0.5 h-full bg-blue-300"></div>
                                                <div :class="['h-full border-t-2 border-blue-300 flex-1', idx === activeUplineTree.downlines.length - 1 ? 'invisible' : '']"></div>
                                            </div>

                                            <!-- Downline 1 Card Container with margins -->
                                            <div class="px-2 sm:px-3 flex flex-col items-center">
                                                <!-- Top Badge -->
                                                <div class="relative z-10 -mb-2.5">
                                                    <span class="inline-flex items-center gap-1 px-3 py-0.5 rounded-full bg-emerald-600 text-white text-[9px] font-black uppercase tracking-wider shadow-xs">
                                                        <span>DOWNLINE 1</span>
                                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                                                    </span>
                                                </div>

                                                <!-- Downline 1 Card -->
                                                <div
                                                    @click="openMemberModal(d1, 'Downline 1')"
                                                    class="w-56 bg-white rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-md transition-all p-3.5 cursor-pointer relative group"
                                                    :class="isNodeMatched(d1) ? 'ring-4 ring-emerald-400 border-emerald-500 scale-105' : ''"
                                                >
                                                    <div class="flex items-start justify-between gap-1.5">
                                                        <div class="flex items-center gap-2 min-w-0">
                                                            <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center font-bold text-xs shrink-0">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                                            </div>
                                                            <div class="min-w-0">
                                                                <h5 class="text-xs font-extrabold text-slate-800 truncate group-hover:text-emerald-600 transition-colors" :title="d1.name">
                                                                    {{ d1.name }}
                                                                </h5>
                                                                <span class="text-[9px] text-slate-400 font-mono block truncate">
                                                                    ID: {{ d1.referral_code || ('SL-202301-' + String(d1.id).padStart(4, '0')) }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center gap-0.5 shrink-0">
                                                            <span class="px-1.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600 text-[9px] font-bold border border-emerald-100">
                                                                Aktif
                                                            </span>
                                                            <button type="button" @click.stop="openMemberModal(d1, 'Downline 1')" class="text-slate-400 hover:text-slate-600 p-0.5">
                                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="2"></circle><circle cx="12" cy="12" r="2"></circle><circle cx="12" cy="19" r="2"></circle></svg>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Bottom Stats Row -->
                                                    <div class="mt-2.5 pt-2 border-t border-slate-100 grid grid-cols-2 gap-1 text-[10px] text-slate-500">
                                                        <div class="truncate">
                                                            <span>👤 Komisi : </span><b class="text-slate-700">0%</b>
                                                        </div>
                                                        <div class="truncate text-right">
                                                            <span v-if="d1.downlines && d1.downlines.length > 0" class="font-bold text-emerald-600">
                                                                👥 {{ d1.downlines.length }} Downline 2
                                                            </span>
                                                            <span v-else class="text-slate-400">
                                                                👥 0 Downline 2
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- 3. DOWNLINE 2 CONNECTOR & CARDS IF EXISTS (e.g. IMALULHAKIM -> SADIN) -->
                                                <div v-if="d1.downlines && d1.downlines.length > 0" class="flex flex-col items-center">
                                                    <!-- Expand/Collapse Button (Check/Arrow Circle) -->
                                                    <div class="relative z-10 -mt-2">
                                                        <button
                                                            type="button"
                                                            @click.stop="toggleDownlineExpand(d1.id)"
                                                            class="w-5 h-5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white flex items-center justify-center text-[9px] font-black shadow-xs ring-2 ring-white transition-transform duration-200"
                                                            :class="expandedDownlines[d1.id] ? 'rotate-180' : ''"
                                                            :title="expandedDownlines[d1.id] ? 'Tutup Downline 2' : 'Buka Downline 2'"
                                                        >
                                                            ▼
                                                        </button>
                                                    </div>

                                                    <!-- Vertical Drop Line & Downline 2 Children -->
                                                    <div v-if="expandedDownlines[d1.id]" class="flex flex-col items-center">
                                                        <div class="w-0.5 h-8 bg-emerald-400"></div>

                                                        <div class="flex items-start gap-4">
                                                            <div
                                                                v-for="d2 in d1.downlines"
                                                                :key="d2.id"
                                                                class="flex flex-col items-center"
                                                            >
                                                                <!-- Top Badge -->
                                                                <div class="relative z-10 -mb-2.5">
                                                                    <span class="inline-flex items-center gap-1 px-3 py-0.5 rounded-full bg-amber-500 text-white text-[9px] font-black uppercase tracking-wider shadow-xs">
                                                                        <span>DOWNLINE 2</span>
                                                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                                                                    </span>
                                                                </div>

                                                                <!-- Downline 2 Card -->
                                                                <div
                                                                    @click="openMemberModal(d2, 'Downline 2')"
                                                                    class="w-56 bg-white rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-md transition-all p-3.5 cursor-pointer relative group"
                                                                    :class="isNodeMatched(d2) ? 'ring-4 ring-amber-400 border-amber-500 scale-105' : ''"
                                                                >
                                                                    <div class="flex items-start justify-between gap-1.5">
                                                                        <div class="flex items-center gap-2 min-w-0">
                                                                            <div class="w-8 h-8 rounded-full bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center font-bold text-xs shrink-0">
                                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                                                            </div>
                                                                            <div class="min-w-0">
                                                                                <h5 class="text-xs font-extrabold text-slate-800 truncate group-hover:text-amber-600 transition-colors" :title="d2.name">
                                                                                    {{ d2.name }}
                                                                                </h5>
                                                                                <span class="text-[9px] text-slate-400 font-mono block truncate">
                                                                                    ID: {{ d2.referral_code || ('SL-202303-' + String(d2.id).padStart(4, '0')) }}
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                        <div class="flex items-center gap-0.5 shrink-0">
                                                                            <span class="px-1.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600 text-[9px] font-bold border border-emerald-100">
                                                                                Aktif
                                                                            </span>
                                                                            <button type="button" @click.stop="openMemberModal(d2, 'Downline 2')" class="text-slate-400 hover:text-slate-600 p-0.5">
                                                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="2"></circle><circle cx="12" cy="12" r="2"></circle><circle cx="12" cy="19" r="2"></circle></svg>
                                                                            </button>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Bottom Stats Row -->
                                                                    <div class="mt-2.5 pt-2 border-t border-slate-100 grid grid-cols-2 gap-1 text-[10px] text-slate-500">
                                                                        <div class="truncate">
                                                                            <span>👤 Komisi : </span><b class="text-slate-700">5%</b>
                                                                        </div>
                                                                        <div class="truncate text-right">
                                                                            <span class="text-slate-400">👥 0 Downline 3</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom Information Bar (Persis Screenshot) -->
                            <div class="p-4 sm:p-5 bg-blue-50/80 border-t border-blue-100 flex items-start gap-3">
                                <div class="w-7 h-7 rounded-xl bg-blue-600 text-white flex items-center justify-center text-xs font-bold shrink-0 shadow-xs">
                                    ℹ️
                                </div>
                                <div class="text-xs text-blue-900 leading-relaxed">
                                    <strong class="font-black text-blue-950 block sm:inline mr-1">Informasi:</strong>
                                    <span>Sistem Komisi Multi-Tier: Menikmati passive income pasif hingga 2 Level (Upline 1 &amp; Upline 2). Setiap Sales/Upline bebas memiliki Downline sebanyak-banyaknya tanpa batas.</span>
                                </div>
                            </div>
                        </div>

                        <!-- ══ MODE B: TAMPILAN LIST (TABEL STRUKTUR HIERARKI) ══ -->
                        <div v-else class="p-6">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                                <div class="flex items-center gap-1.5 overflow-x-auto">
                                    <button
                                        v-for="lvl in [
                                            { id: 'all', label: 'Semua Tingkat' },
                                            { id: 'upline', label: 'Upline Utama' },
                                            { id: 'l1', label: 'Downline 1' },
                                            { id: 'l2', label: 'Downline 2' },
                                        ]"
                                        :key="lvl.id"
                                        type="button"
                                        @click="listFilterLevel = lvl.id"
                                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap"
                                        :class="listFilterLevel === lvl.id ? 'bg-indigo-600 text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                    >
                                        {{ lvl.label }}
                                    </button>
                                </div>
                                <span class="text-xs text-slate-500 font-medium">
                                    Menampilkan <b>{{ flatMemberList.length }}</b> anggota jaringan
                                </span>
                            </div>

                            <div class="overflow-x-auto rounded-2xl border border-slate-200">
                                <table class="min-w-full divide-y divide-slate-100 text-xs">
                                    <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-left">
                                        <tr>
                                            <th class="px-4 py-3">Nama Member / Kode</th>
                                            <th class="px-4 py-3">Tingkatan</th>
                                            <th class="px-4 py-3">Sponsor / Upline</th>
                                            <th class="px-4 py-3">Jalur Sistem</th>
                                            <th class="px-4 py-3 text-center">Pelanggan Direct</th>
                                            <th class="px-4 py-3 text-right">Total Pendapatan</th>
                                            <th class="px-4 py-3 text-center">Status</th>
                                            <th class="px-4 py-3 text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <tr v-for="m in flatMemberList" :key="m.level_label + '-' + m.id" class="hover:bg-slate-50/70 transition-colors">
                                            <td class="px-4 py-3 font-medium text-slate-900">
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-7 h-7 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-xs shrink-0">
                                                        👤
                                                    </div>
                                                    <div>
                                                        <div class="font-bold text-slate-800">{{ m.name }}</div>
                                                        <div class="text-[10px] text-slate-400 font-mono">{{ m.referral_code || ('ID: SL-' + m.id) }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border" :class="m.level_badge">
                                                    {{ m.level_label }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-slate-600 font-medium">
                                                {{ m.sponsor_name }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <span v-if="m.is_employee" class="px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 text-[10px] font-bold border border-purple-200">
                                                    👔 Karyawan (Slip Gaji)
                                                </span>
                                                <span v-else class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">
                                                    🤝 Mitra Luar (Dompet)
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center font-bold text-slate-700">
                                                {{ m.total_customers || 0 }} Cust
                                            </td>
                                            <td class="px-4 py-3 text-right font-black text-emerald-600">
                                                {{ formatRupiah(m.total_earned || 0) }}
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">
                                                    Aktif
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <button
                                                    type="button"
                                                    @click="openMemberModal(m, m.level_label)"
                                                    class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-lg text-[11px] transition-colors"
                                                >
                                                    Detail
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="flatMemberList.length === 0">
                                            <td colspan="8" class="px-4 py-8 text-center text-slate-400 text-xs">
                                                Tidak ada member yang cocok dengan filter / pencarian.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Collapsible Section: Panduan Alur Sistem Cerdas & Simulator -->
                    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm p-6 sm:p-7">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div class="flex items-center gap-3">
                                <span class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
                                    💡
                                </span>
                                <div>
                                    <h3 class="text-sm sm:text-base font-bold text-slate-900">
                                        Panduan Alur Sistem Cerdas &amp; Simulator Interaktif
                                    </h3>
                                    <p class="text-xs text-slate-500">
                                        Pelajari aturan pemisahan Slip Gaji Karyawan vs Dompet Mitra, Pinalti Berhenti &lt; {{ settingsForm.affiliate_contract_min_months }} Bulan, dan coba Simulator Pasif.
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="showArchitectureGuide = !showArchitectureGuide"
                                class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all flex items-center gap-1.5"
                            >
                                <span>{{ showArchitectureGuide ? 'Sembunyikan' : 'Buka Detail' }}</span>
                                <span class="text-xs transition-transform duration-200" :class="showArchitectureGuide ? 'rotate-180' : ''">▼</span>
                            </button>
                        </div>

                        <!-- Guide Content when opened -->
                        <div v-if="showArchitectureGuide" class="mt-6 space-y-6">
                            <!-- 1. SMART DUAL-ROUTE ENGINE (KARYAWAN VS MITRA LUAR) -->
                            <div class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-lg border border-indigo-500/20">
                                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-6 pb-4 border-b border-white/10">
                                    <div>
                                        <span class="px-2.5 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-[10px] font-extrabold uppercase tracking-wider border border-indigo-400/30">
                                            Smart Dual-Route Engine &bull; 1 Akun Tunggal
                                        </span>
                                        <h3 class="text-lg sm:text-xl font-black text-white mt-1.5 flex items-center gap-2">
                                            <span>🧠</span>
                                            <span>Pembedaan Otomatis Jalur Karyawan vs Mitra Luar</span>
                                        </h3>
                                        <p class="text-xs text-indigo-200/80 mt-1">
                                            Sistem secara otomatis mendeteksi profil pengguna saat komisi dihasilkan tanpa memerlukan pendaftaran akun ganda.
                                        </p>
                                    </div>
                                    <span class="inline-flex self-start md:self-auto text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 px-3 py-1.5 rounded-xl">
                                        🛡️ Anti-Double Pay Active
                                    </span>
                                </div>

                                <!-- Grid Perbandingan 2 Jalur -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Jalur Ungu: Karyawan Internal -->
                                    <div class="bg-white/5 backdrop-blur-md rounded-2xl p-5 border border-purple-400/30 space-y-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-9 h-9 rounded-xl bg-purple-500/30 text-purple-200 flex items-center justify-center text-lg font-bold">
                                                    👔
                                                </div>
                                                <div>
                                                    <h4 class="font-bold text-white text-sm">Jalur Karyawan Internal</h4>
                                                    <span class="text-[10px] text-purple-300">Staff, Teknisi, Sales, Admin, NOC</span>
                                                </div>
                                            </div>
                                            <span class="px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-200 text-[10px] font-bold border border-purple-400/30">
                                                Slip Gaji / Payroll
                                            </span>
                                        </div>

                                        <div class="space-y-2.5 text-xs text-purple-100/90 pt-1">
                                            <div class="p-3 rounded-xl bg-white/5 border border-purple-400/20 flex items-start gap-2.5">
                                                <span class="text-base">🎯</span>
                                                <div>
                                                    <span class="font-bold text-white block">Fee Booking Pelanggan:</span>
                                                    Otomatis masuk ke tabel <b>Incentives (Pending)</b> dengan referensi unik <code>booking_cust_{id}</code>. Saldo dompet = Rp 0.
                                                </div>
                                            </div>
                                            <div class="p-3 rounded-xl bg-white/5 border border-purple-400/20 flex items-start gap-2.5">
                                                <span class="text-base">💰</span>
                                                <div>
                                                    <span class="font-bold text-white block">Pasif Downline L1 &amp; L2:</span>
                                                    Otomatis masuk ke tabel <b>Incentives (Pending)</b> setiap invoice pelanggan binaan lunas.
                                                </div>
                                            </div>
                                            <div class="p-3 rounded-xl bg-white/5 border border-purple-400/20 flex items-start gap-2.5">
                                                <span class="text-base">✂️</span>
                                                <div>
                                                    <span class="font-bold text-white block">Pinalti Stop Dini (&lt; {{ settingsForm.affiliate_contract_min_months }} Bulan):</span>
                                                    Otomatis masuk ke tabel <b>Deductions (Pending)</b>, otomatis memotong total gaji pada slip gaji bulanan.
                                                </div>
                                            </div>
                                            <div class="p-3 rounded-xl bg-purple-600/30 border border-purple-400/40 text-[11px] font-semibold text-purple-100 flex items-center justify-between">
                                                <span>📅 Muara Pencairan:</span>
                                                <span class="font-bold text-white">Slip Gaji Bulanan (Menu Payroll)</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Jalur Hijau: Mitra Luar -->
                                    <div class="bg-white/5 backdrop-blur-md rounded-2xl p-5 border border-emerald-400/30 space-y-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-9 h-9 rounded-xl bg-emerald-500/30 text-emerald-200 flex items-center justify-center text-lg font-bold">
                                                    🤝
                                                </div>
                                                <div>
                                                    <h4 class="font-bold text-white text-sm">Jalur Mitra Luar / Freelance</h4>
                                                    <span class="text-[10px] text-emerald-300">Agen Umum, Reseller, Partner Publik</span>
                                                </div>
                                            </div>
                                            <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-200 text-[10px] font-bold border border-emerald-400/30">
                                                Dompet E-Wallet
                                            </span>
                                        </div>

                                        <div class="space-y-2.5 text-xs text-emerald-100/90 pt-1">
                                            <div class="p-3 rounded-xl bg-white/5 border border-emerald-400/20 flex items-start gap-2.5">
                                                <span class="text-base">🎯</span>
                                                <div>
                                                    <span class="font-bold text-white block">Fee Booking Pelanggan:</span>
                                                    Otomatis menambah <b>Saldo Dompet Afiliasi (+{{ formatRupiah(settingsForm.affiliate_commission_booking) }})</b> dan tercatat di total pendapatan.
                                                </div>
                                            </div>
                                            <div class="p-3 rounded-xl bg-white/5 border border-emerald-400/20 flex items-start gap-2.5">
                                                <span class="text-base">💰</span>
                                                <div>
                                                    <span class="font-bold text-white block">Pasif Downline L1 &amp; L2:</span>
                                                    Otomatis menambah saldo dompet: L1 (+{{ formatRupiah(settingsForm.affiliate_commission_upline_1) }}) &amp; L2 (+{{ formatRupiah(settingsForm.affiliate_commission_upline_2) }}).
                                                </div>
                                            </div>
                                            <div class="p-3 rounded-xl bg-white/5 border border-emerald-400/20 flex items-start gap-2.5">
                                                <span class="text-base">✂️</span>
                                                <div>
                                                    <span class="font-bold text-white block">Pinalti Stop Dini (&lt; {{ settingsForm.affiliate_contract_min_months }} Bulan):</span>
                                                    Otomatis memotong langsung dari <b>Saldo Dompet (-{{ formatRupiah(settingsForm.affiliate_commission_booking) }})</b>.
                                                </div>
                                            </div>
                                            <div class="p-3 rounded-xl bg-emerald-600/30 border border-emerald-400/40 text-[11px] font-semibold text-emerald-100 flex items-center justify-between">
                                                <span>🏦 Muara Pencairan:</span>
                                                <span class="font-bold text-white">Transfer Bank / E-Wallet (Withdrawal)</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. SMART GUARD KONTRAK 4 BULAN -->
                            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-7 shadow-xs space-y-6">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                                    <div>
                                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                            <span>✂️</span>
                                            <span>Smart Guard: Pinalti Berhenti Dini (&lt; {{ settingsForm.affiliate_contract_min_months }} Bulan)</span>
                                        </h3>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Perlindungan finansial perusahaan agar mitra &amp; sales berkomitmen menjaga pelanggan tetap berlangganan minimal {{ settingsForm.affiliate_contract_min_months }} bulan.
                                        </p>
                                    </div>
                                    <span class="inline-flex self-start sm:self-auto text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200 px-3 py-1 rounded-full">
                                        Auto-Clawback Protection
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Lulus Kontrak -->
                                    <div class="p-5 rounded-2xl bg-emerald-50/60 border border-emerald-200 space-y-3">
                                        <div class="flex items-center justify-between">
                                            <span class="font-extrabold text-emerald-800 text-sm flex items-center gap-1.5">
                                                <span>✅</span>
                                                <span>Masa Aktif &ge; {{ settingsForm.affiliate_contract_min_months }} Bulan</span>
                                            </span>
                                            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded-full">
                                                LULUS KONTRAK
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-600 leading-relaxed">
                                            Jika pelanggan berhenti setelah melewati minimal {{ settingsForm.affiliate_contract_min_months }} bulan:
                                        </p>
                                        <div class="p-3 bg-white rounded-xl border border-emerald-200 text-xs text-emerald-800 font-semibold space-y-1">
                                            <div>&bull; Bebas pinalti pemotongan fee booking (0 rupiah).</div>
                                            <div>&bull; Fee booking awal tetap 100% menjadi hak sales/mitra.</div>
                                            <div>&bull; Status di menu Stop Permanen: <span class="text-emerald-700 font-bold">Lulus Kontrak</span>.</div>
                                        </div>
                                    </div>

                                    <!-- Stop Dini -->
                                    <div class="p-5 rounded-2xl bg-rose-50/60 border border-rose-200 space-y-3">
                                        <div class="flex items-center justify-between">
                                            <span class="font-extrabold text-rose-800 text-sm flex items-center gap-1.5">
                                                <span>⚠️</span>
                                                <span>Masa Aktif &lt; {{ settingsForm.affiliate_contract_min_months }} Bulan</span>
                                            </span>
                                            <span class="px-2 py-0.5 bg-rose-100 text-rose-800 text-[10px] font-bold rounded-full">
                                                POTONG FEE BOOKING
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-600 leading-relaxed">
                                            Jika pelanggan berhenti sebelum mencapai {{ settingsForm.affiliate_contract_min_months }} bulan:
                                        </p>
                                        <div class="p-3 bg-white rounded-xl border border-rose-200 text-xs text-rose-800 font-semibold space-y-1">
                                            <div>&bull; Otomatis dipotong fee booking senilai <b>{{ formatRupiah(settingsForm.affiliate_commission_booking) }}</b>.</div>
                                            <div>&bull; <b>Karyawan</b>: Masuk ke <b>Deductions</b> (Potongan Slip Gaji Payroll).</div>
                                            <div>&bull; <b>Mitra Luar</b>: Otomatis memotong <b>Saldo Dompet Afiliasi</b>.</div>
                                            <div>&bull; Badge transparan di menu Stop Permanen: <code>[SLIP GAJI]</code> atau <code>[DOMPET MITRA]</code>.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. LIVE INTERACTIVE PASSIVE INCOME SIMULATOR -->
                            <div class="bg-gradient-to-br from-indigo-900 via-slate-900 to-indigo-950 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-400/20 space-y-6">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-white/10">
                                    <div>
                                        <span class="px-2.5 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-[10px] font-extrabold uppercase tracking-wider border border-indigo-400/30">
                                            Kalkulator Proyeksi &bull; Interaktif
                                        </span>
                                        <h3 class="text-base sm:text-lg font-black text-white mt-1.5 flex items-center gap-2">
                                            <span>🧮</span>
                                            <span>Simulasi Potensi Pasif Income Jaringan Downline</span>
                                        </h3>
                                        <p class="text-xs text-indigo-200/80 mt-1">
                                            Ubah angka di bawah ini untuk melihat potensi pasif income bulanan yang diterima seorang Upline/Karyawan:
                                        </p>
                                    </div>
                                    <span class="text-xs font-mono text-indigo-300">
                                        L1: {{ formatRupiah(settingsForm.affiliate_commission_upline_1) }} &bull; L2: {{ formatRupiah(settingsForm.affiliate_commission_upline_2) }}
                                    </span>
                                </div>

                                <!-- Kontrol Input Simulasi -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div class="bg-white/5 backdrop-blur-md rounded-2xl p-4 border border-white/10">
                                        <label class="block text-[11px] font-bold uppercase tracking-wider text-indigo-200 mb-1">
                                            Jumlah Mitra Level 1
                                        </label>
                                        <input
                                            v-model.number="simDownlinesL1"
                                            type="number"
                                            min="0"
                                            class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-xl text-white font-mono font-bold text-sm focus:outline-none focus:bg-white/20"
                                        />
                                        <span class="text-[10px] text-slate-300 mt-1 block">Mitra binaan langsung</span>
                                    </div>

                                    <div class="bg-white/5 backdrop-blur-md rounded-2xl p-4 border border-white/10">
                                        <label class="block text-[11px] font-bold uppercase tracking-wider text-indigo-200 mb-1">
                                            Rata2 Pelanggan / Mitra L1
                                        </label>
                                        <input
                                            v-model.number="simCustPerL1"
                                            type="number"
                                            min="0"
                                            class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-xl text-white font-mono font-bold text-sm focus:outline-none focus:bg-white/20"
                                        />
                                        <span class="text-[10px] text-slate-300 mt-1 block">Total L1: {{ simTotalCustL1 }} pelanggan</span>
                                    </div>

                                    <div class="bg-white/5 backdrop-blur-md rounded-2xl p-4 border border-white/10">
                                        <label class="block text-[11px] font-bold uppercase tracking-wider text-purple-200 mb-1">
                                            Jumlah Mitra Level 2
                                        </label>
                                        <input
                                            v-model.number="simDownlinesL2"
                                            type="number"
                                            min="0"
                                            class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-xl text-white font-mono font-bold text-sm focus:outline-none focus:bg-white/20"
                                        />
                                        <span class="text-[10px] text-slate-300 mt-1 block">Binaan dari Mitra L1 Anda</span>
                                    </div>

                                    <div class="bg-white/5 backdrop-blur-md rounded-2xl p-4 border border-white/10">
                                        <label class="block text-[11px] font-bold uppercase tracking-wider text-purple-200 mb-1">
                                            Rata2 Pelanggan / Mitra L2
                                        </label>
                                        <input
                                            v-model.number="simCustPerL2"
                                            type="number"
                                            min="0"
                                            class="w-full px-3 py-2 bg-white/10 border border-white/20 rounded-xl text-white font-mono font-bold text-sm focus:outline-none focus:bg-white/20"
                                        />
                                        <span class="text-[10px] text-slate-300 mt-1 block">Total L2: {{ simTotalCustL2 }} pelanggan</span>
                                    </div>
                                </div>

                                <!-- Output Hasil Simulasi -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                                    <div class="bg-white/10 rounded-2xl p-4 border border-white/15">
                                        <span class="text-xs text-indigo-200 block">Total Pelanggan Jaringan Binaan</span>
                                        <span class="text-2xl font-black text-white mt-1 block">
                                            {{ simTotalCustL1 + simTotalCustL2 }} Pelanggan
                                        </span>
                                        <span class="text-[11px] text-slate-300">L1: {{ simTotalCustL1 }} &bull; L2: {{ simTotalCustL2 }}</span>
                                    </div>

                                    <div class="bg-emerald-500/20 rounded-2xl p-4 border border-emerald-400/30">
                                        <span class="text-xs text-emerald-200 block">Potensi Pasif Bulanan</span>
                                        <span class="text-2xl font-black text-emerald-300 mt-1 block">
                                            {{ formatRupiah(simTotalPassiveMonthly) }}
                                        </span>
                                        <span class="text-[11px] text-emerald-200/80">L1: {{ formatRupiah(simIncomeL1) }} | L2: {{ formatRupiah(simIncomeL2) }}</span>
                                    </div>

                                    <div class="bg-purple-500/20 rounded-2xl p-4 border border-purple-400/30">
                                        <span class="text-xs text-purple-200 block">Proyeksi Pasif Tahunan (12 Bulan)</span>
                                        <span class="text-2xl font-black text-purple-200 mt-1 block">
                                            {{ formatRupiah(simTotalPassiveYearly) }}
                                        </span>
                                        <span class="text-[11px] text-purple-200/80">Otomatis masuk slip gaji / dompet</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════ TAB 2: DAFTAR AGEN AFILIASI ════ -->
                <div v-if="currentTab === 'affiliates'" class="p-6">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-4">
                        <div class="relative w-full sm:w-72">
                            <input
                                v-model="searchQuery"
                                @keyup.enter="handleSearch"
                                type="text"
                                placeholder="Cari nama, email, kode referral..."
                                class="w-full pl-9 pr-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                🔍
                            </div>
                        </div>

                        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                            <button
                                type="button"
                                @click="openCreateAffiliateModal"
                                class="px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-xs font-bold rounded-xl shadow-xs transition-all flex items-center gap-1.5"
                            >
                                <span>➕</span>
                                <span>Daftarkan Agen / Downline Baru</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="affiliates.data && affiliates.data.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-left">
                                <tr>
                                    <th class="px-4 py-3">Nama Agen</th>
                                    <th class="px-4 py-3">Kode Referral</th>
                                    <th class="px-4 py-3">Upline Sponsor</th>
                                    <th class="px-4 py-3">Pelanggan</th>
                                    <th class="px-4 py-3">Jaringan Tim</th>
                                    <th class="px-4 py-3">Saldo Aktif</th>
                                    <th class="px-4 py-3">Total Komisi</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="a in affiliates.data" :key="a.id" class="hover:bg-slate-50/60">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-slate-900">{{ a.name }}</span>
                                            <span v-if="a.is_employee" class="px-1.5 py-0.2 bg-purple-100 text-purple-700 rounded text-[9px] font-bold">[Karyawan]</span>
                                        </div>
                                        <div class="text-[11px] text-slate-400">{{ a.email }} &bull; {{ a.whatsapp || '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 font-mono font-bold text-indigo-600">{{ a.referral_code }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1.5">
                                            <span :class="a.upline_name !== '-' ? 'font-bold text-indigo-900 bg-indigo-50 px-2 py-0.5 rounded-lg border border-indigo-100' : 'text-slate-400'">
                                                {{ a.upline_name }}
                                            </span>
                                            <button
                                                type="button"
                                                @click="openUplineModal(a)"
                                                class="text-[10px] text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 px-1.5 py-0.5 rounded transition-colors focus:outline-none"
                                                title="Ubah Upline Sponsor"
                                            >
                                                ✏️
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-slate-800">{{ a.total_customers }} Pelanggan</td>
                                    <td class="px-4 py-3 font-semibold text-purple-600">{{ a.total_downlines }} Mitra</td>
                                    <td class="px-4 py-3 font-extrabold text-emerald-600">{{ formatRupiah(a.balance) }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-700">{{ formatRupiah(a.total_earned) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase" :class="a.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'">
                                            {{ a.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="openUplineModal(a)"
                                                class="px-2.5 py-1 text-[11px] font-bold rounded-lg transition-colors border bg-indigo-50 text-indigo-700 border-indigo-200 hover:bg-indigo-100 flex items-center gap-1 shadow-2xs focus:outline-none"
                                                title="Atur siapa Upline Sponsor dari agen ini"
                                            >
                                                <span>🔗</span>
                                                <span>Atur Upline</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="toggleAffiliateStatus(a)"
                                                class="px-2.5 py-1 text-[11px] font-bold rounded-lg transition-colors border shadow-2xs"
                                                :class="a.status === 'active' ? 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'"
                                            >
                                                {{ a.status === 'active' ? 'Suspend' : 'Aktifkan' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-center py-12 text-slate-400">
                        Belum ada agen afiliasi terdaftar.
                    </div>
                </div>

                <!-- ════ TAB 3: RIWAYAT LOG KOMISI ════ -->
                <div v-if="currentTab === 'commissions'" class="p-6">
                    <div v-if="commissions.data && commissions.data.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-left">
                                <tr>
                                    <th class="px-4 py-3">Tanggal</th>
                                    <th class="px-4 py-3">Penerima Komisi</th>
                                    <th class="px-4 py-3">Tipe Komisi</th>
                                    <th class="px-4 py-3">Deskripsi Transaksi</th>
                                    <th class="px-4 py-3 text-right">Nominal</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="c in commissions.data" :key="c.id" class="hover:bg-slate-50/60">
                                    <td class="px-4 py-3 text-slate-500 whitespace-nowrap">{{ c.date }}</td>
                                    <td class="px-4 py-3 font-bold text-slate-800">{{ c.affiliate_name }}</td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                            :class="{
                                                'bg-indigo-100 text-indigo-800': c.type === 'booking',
                                                'bg-emerald-100 text-emerald-800': c.type === 'monthly_direct',
                                                'bg-purple-100 text-purple-800': c.type === 'upline_tier_1',
                                                'bg-amber-100 text-amber-800': c.type === 'upline_tier_2',
                                                'bg-rose-100 text-rose-800 border border-rose-200': c.type === 'clawback_booking' || c.type === 'clawback_survey',
                                            }"
                                        >
                                            <template v-if="c.type === 'booking'">🎯 Booking Baru</template>
                                            <template v-else-if="c.type === 'monthly_direct'">💰 Pasif Bulanan</template>
                                            <template v-else-if="c.type === 'upline_tier_1'">🚀 Upline Tier 1</template>
                                            <template v-else-if="c.type === 'upline_tier_2'">🌟 Upline Tier 2</template>
                                            <template v-else-if="c.type === 'clawback_booking'">✂️ Potong Booking (&lt; 4 Bln)</template>
                                            <template v-else-if="c.type === 'clawback_survey'">✂️ Potong Survey (&lt; 4 Bln)</template>
                                            <template v-else>{{ c.type }}</template>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ c.description }}</td>
                                    <td class="px-4 py-3 text-right font-extrabold whitespace-nowrap" :class="c.amount < 0 || c.type === 'clawback_booking' || c.type === 'clawback_survey' ? 'text-rose-600' : 'text-emerald-600'">
                                        {{ (c.amount < 0 || c.type === 'clawback_booking' || c.type === 'clawback_survey') ? '-' : '+' }}{{ formatRupiah(Math.abs(c.amount)) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span 
                                            class="text-[10px] font-semibold uppercase px-2 py-0.5 rounded-full border"
                                            :class="{
                                                'bg-rose-50 text-rose-700 border-rose-200': c.type === 'clawback_booking' || c.type === 'clawback_survey',
                                                'bg-purple-50 text-purple-700 border-purple-200': c.status === 'payroll' && c.type !== 'clawback_booking' && c.type !== 'clawback_survey',
                                                'bg-emerald-50 text-emerald-700 border-emerald-200': c.status === 'approved' && c.type !== 'clawback_booking' && c.type !== 'clawback_survey'
                                            }"
                                        >
                                            {{ (c.type === 'clawback_booking' || c.type === 'clawback_survey') ? (c.status === 'payroll' ? 'Potong Gaji' : 'Dipotong') : (c.status === 'payroll' ? 'Slip Gaji' : c.status) }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-center py-12 text-slate-400">
                        Belum ada riwayat komisi yang dicatat.
                    </div>
                </div>

                <!-- ════ TAB 4: PERMINTAAN PENARIKAN (WITHDRAWALS) ════ -->
                <div v-if="currentTab === 'withdrawals'" class="p-6">
                    <div v-if="withdrawals.data && withdrawals.data.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-left">
                                <tr>
                                    <th class="px-4 py-3">No. Referensi</th>
                                    <th class="px-4 py-3">Nama Mitra</th>
                                    <th class="px-4 py-3">Rekening Pencairan</th>
                                    <th class="px-4 py-3">Nominal Tarik</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                    <th class="px-4 py-3">Tanggal</th>
                                    <th class="px-4 py-3 text-center">Aksi Admin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="w in withdrawals.data" :key="w.id" class="hover:bg-slate-50/60">
                                    <td class="px-4 py-3 font-mono font-bold text-indigo-700">{{ w.reference_no }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-900">{{ w.affiliate_name }}</div>
                                        <div class="text-[11px] text-slate-400">{{ w.affiliate_phone || '-' }}</div>
                                        <div v-if="w.unpaid_customers_count > 0" class="mt-1">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200">
                                                ⚠️ {{ w.unpaid_customers_count }} Pelanggan Menunggak ({{ formatRupiah(w.unpaid_customers_amount) }})
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-800">{{ w.bank_name }} - {{ w.bank_account_number }}</div>
                                        <div class="text-[11px] text-slate-500">a.n {{ w.bank_account_holder }}</div>
                                    </td>
                                    <td class="px-4 py-3 font-extrabold text-slate-900">{{ formatRupiah(w.amount) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                            :class="{
                                                'bg-amber-100 text-amber-800': w.status === 'pending',
                                                'bg-emerald-100 text-emerald-800': w.status === 'approved',
                                                'bg-rose-100 text-rose-800': w.status === 'rejected',
                                            }"
                                        >
                                            {{ w.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-500 whitespace-nowrap">{{ w.date }}</td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <div v-if="w.status === 'pending'" class="flex items-center justify-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="openApproveModal(w)"
                                                class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] rounded-lg shadow-2xs"
                                            >
                                                Approve / Transfer
                                            </button>
                                            <button
                                                type="button"
                                                @click="openRejectModal(w)"
                                                class="px-2.5 py-1 bg-white hover:bg-rose-50 border border-rose-300 text-rose-700 font-bold text-[11px] rounded-lg"
                                            >
                                                Tolak
                                            </button>
                                        </div>
                                        <span v-else class="text-[11px] text-slate-400">
                                            Diproses oleh {{ w.processor_name || 'Admin' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-center py-12 text-slate-400">
                        Tidak ada permintaan penarikan dana komisi.
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Modal Approve Penarikan Dana ── -->
        <div
            v-if="showApproveModal"
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
            @click.self="showApproveModal = false"
        >
            <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-100 p-6 sm:p-7">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">
                        Konfirmasi Transfer Pencairan
                    </h3>
                    <button type="button" @click="showApproveModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div v-if="selectedWithdrawal" class="p-3 bg-emerald-50 border border-emerald-200 rounded-2xl mb-4 text-xs space-y-1">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Penerima:</span>
                        <span class="font-bold text-slate-800">{{ selectedWithdrawal.affiliate_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Tujuan:</span>
                        <span class="font-bold text-slate-800">{{ selectedWithdrawal.bank_name }} - {{ selectedWithdrawal.bank_account_number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Atas Nama:</span>
                        <span class="font-bold text-slate-800">{{ selectedWithdrawal.bank_account_holder }}</span>
                    </div>
                    <div class="flex justify-between text-emerald-800 font-extrabold text-sm pt-1 border-t border-emerald-200">
                        <span>Total Ditransfer:</span>
                        <span>{{ formatRupiah(selectedWithdrawal.amount) }}</span>
                    </div>
                </div>

                <div v-if="selectedWithdrawal && selectedWithdrawal.unpaid_customers_count > 0" class="p-3 bg-amber-50 border border-amber-200 rounded-2xl mb-4 text-xs text-amber-900">
                    <div class="font-bold flex items-center gap-1.5 text-amber-800">
                        <span>⚠️ Perhatian: Pelanggan Belum Lunas</span>
                    </div>
                    <p class="mt-1 text-[11px] text-amber-700 leading-relaxed">
                        Mitra ini memiliki <strong>{{ selectedWithdrawal.unpaid_customers_count }} pelanggan booking</strong> yang belum melunasi tagihannya (Total tunggakan: <strong>{{ formatRupiah(selectedWithdrawal.unpaid_customers_amount) }}</strong>). Sesuai kebijakan, komisi mitra luar seharusnya dicairkan setelah pelanggan melunasi tagihan.
                    </p>
                </div>

                <form @submit.prevent="submitApprove" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                            No. Referensi / Bukti Mutasi Bank (Opsional)
                        </label>
                        <input
                            v-model="approveForm.reference_no"
                            type="text"
                            placeholder="Contoh: TRF-20261014-9982"
                            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                            Upload Screenshot Bukti Transfer (Opsional)
                        </label>
                        <input
                            type="file"
                            accept="image/*"
                            @change="e => approveForm.receipt = e.target.files[0]"
                            class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
                        />
                    </div>

                    <div class="pt-2 flex justify-end gap-2">
                        <button type="button" @click="showApproveModal = false" class="px-4 py-2 border border-slate-300 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50">
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="approveForm.processing"
                            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition-colors"
                        >
                            {{ approveForm.processing ? 'Menyimpan...' : 'Tandai Berhasil Ditransfer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ── Modal Reject Penarikan Dana ── -->
        <div
            v-if="showRejectModal"
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
            @click.self="showRejectModal = false"
        >
            <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-100 p-6 sm:p-7">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-rose-700">
                        Tolak Pengajuan Penarikan
                    </h3>
                    <button type="button" @click="showRejectModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <p class="text-xs text-slate-600 mb-4">
                    Saldo sebesar <strong>{{ formatRupiah(selectedWithdrawal?.amount) }}</strong> akan otomatis dikembalikan ke saldo mitra.
                </p>

                <form @submit.prevent="submitReject" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                            Alasan Penolakan
                        </label>
                        <textarea
                            v-model="rejectForm.reason"
                            rows="3"
                            required
                            placeholder="Contoh: Nomor rekening tidak valid / atas nama berbeda"
                            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-xs focus:outline-none focus:ring-2 focus:ring-rose-500"
                        ></textarea>
                    </div>

                    <div class="pt-2 flex justify-end gap-2">
                        <button type="button" @click="showRejectModal = false" class="px-4 py-2 border border-slate-300 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50">
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="rejectForm.processing"
                            class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-sm transition-colors"
                        >
                            {{ rejectForm.processing ? 'Menyimpan...' : 'Tolak & Kembalikan Saldo' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ── Modal Detail Member Jaringan Affiliate ── -->
        <div
            v-if="showMemberModal && selectedMemberModal"
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
            @click.self="showMemberModal = false"
        >
            <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-100">
                <!-- Modal Header -->
                <div class="p-6 bg-gradient-to-r from-indigo-900 via-indigo-950 to-slate-900 text-white relative">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-white/10 border border-white/20 text-white flex items-center justify-center font-bold text-lg backdrop-blur-sm">
                                👤
                            </div>
                            <div>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-indigo-500/30 text-indigo-200 border border-indigo-400/30 inline-block mb-1">
                                    {{ selectedMemberModal.modal_level || 'Member Jaringan' }}
                                </span>
                                <h3 class="text-base font-extrabold text-white">
                                    {{ selectedMemberModal.name }}
                                </h3>
                                <span class="text-xs text-indigo-200 font-mono">
                                    ID: {{ selectedMemberModal.referral_code || ('ID: SL-' + selectedMemberModal.id) }}
                                </span>
                            </div>
                        </div>
                        <button type="button" @click="showMemberModal = false" class="text-white/60 hover:text-white p-1 rounded-lg">
                            ✕
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-5">
                    <!-- Status & Classification -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Status Keanggotaan</span>
                            <span class="inline-flex items-center gap-1.5 mt-1 font-bold text-xs text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span>Aktif Berjalan</span>
                            </span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Jalur Aliran Komisi</span>
                            <span v-if="selectedMemberModal.is_employee" class="inline-flex items-center gap-1 mt-1 font-bold text-xs text-purple-700 bg-purple-50 px-2.5 py-0.5 rounded-full border border-purple-200">
                                👔 Karyawan (Payroll)
                            </span>
                            <span v-else class="inline-flex items-center gap-1 mt-1 font-bold text-xs text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                                🤝 Mitra Luar (Dompet)
                            </span>
                        </div>
                    </div>

                    <!-- Contact & Communication -->
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-2 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Email:</span>
                            <span class="font-medium text-slate-800">{{ selectedMemberModal.email || '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">WhatsApp / Telp:</span>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-slate-800">{{ selectedMemberModal.phone || '-' }}</span>
                                <a
                                    v-if="selectedMemberModal.phone"
                                    :href="'https://wa.me/' + selectedMemberModal.phone.replace(/[^0-9]/g, '')"
                                    target="_blank"
                                    class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-[10px] font-bold transition-colors"
                                >
                                    Chat WA 💬
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="p-3 rounded-2xl bg-indigo-50/60 border border-indigo-100">
                            <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-wider block">Pelanggan</span>
                            <span class="text-lg font-black text-indigo-700 mt-0.5 block">
                                {{ selectedMemberModal.total_customers || 0 }}
                            </span>
                        </div>
                        <div class="p-3 rounded-2xl bg-emerald-50/60 border border-emerald-100">
                            <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider block">Total Pendapatan</span>
                            <span class="text-xs font-black text-emerald-700 mt-1.5 block">
                                {{ formatRupiah(selectedMemberModal.total_earned || 0) }}
                            </span>
                        </div>
                        <div class="p-3 rounded-2xl bg-purple-50/60 border border-purple-100">
                            <span class="text-[10px] font-bold text-purple-400 uppercase tracking-wider block">Saldo Dompet</span>
                            <span class="text-xs font-black text-purple-700 mt-1.5 block">
                                {{ formatRupiah(selectedMemberModal.balance || 0) }}
                            </span>
                        </div>
                    </div>

                    <!-- Downline Summary if exists -->
                    <div v-if="selectedMemberModal.downlines && selectedMemberModal.downlines.length > 0" class="pt-2 border-t border-slate-100">
                        <span class="text-xs font-bold text-slate-800 block mb-2">
                            Daftar Binaan Langsung ({{ selectedMemberModal.downlines.length }} Downline):
                        </span>
                        <div class="max-h-36 overflow-y-auto space-y-1.5 pr-1">
                            <div
                                v-for="dl in selectedMemberModal.downlines"
                                :key="dl.id"
                                class="p-2 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-400">↳</span>
                                    <span class="font-bold text-slate-700">{{ dl.name }}</span>
                                    <span class="text-[10px] font-mono text-slate-400">({{ dl.referral_code }})</span>
                                </div>
                                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                                    Aktif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                    <button
                        type="button"
                        @click="showMemberModal = false"
                        class="px-5 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl transition-colors"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- ════ MODAL: DAFTARKAN AGEN / DOWNLINE BARU ════ -->
        <Teleport to="body">
            <div v-if="showCreateAffiliateModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showCreateAffiliateModal = false">
                <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full overflow-hidden border border-slate-100 p-6 sm:p-7 max-h-[90vh] flex flex-col">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 shrink-0">
                        <div class="flex items-center gap-2.5">
                            <span class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg font-bold">➕</span>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Daftarkan Agen / Downline Baru</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Tambah agen penjualan dan hubungkan ke Upline Sponsor di jaringan multi-tier.</p>
                            </div>
                        </div>
                        <button type="button" @click="showCreateAffiliateModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors">✕</button>
                    </div>

                    <!-- Mode Tab: Akun Baru vs User yang Ada -->
                    <div class="flex items-center gap-2 p-1 bg-slate-100 rounded-xl mb-4 text-xs font-bold shrink-0">
                        <button
                            type="button"
                            @click="createForm.mode = 'new_user'; createForm.clearErrors()"
                            class="flex-1 py-2 rounded-lg transition-all flex items-center justify-center gap-1.5"
                            :class="createForm.mode === 'new_user' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        >
                            <span>➕</span>
                            <span>Akun Mitra Baru</span>
                        </button>
                        <button
                            type="button"
                            @click="createForm.mode = 'existing_user'; createForm.clearErrors()"
                            class="flex-1 py-2 rounded-lg transition-all flex items-center justify-center gap-1.5"
                            :class="createForm.mode === 'existing_user' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        >
                            <span>👤</span>
                            <span>Pilih User / Karyawan ({{ (candidate_users || []).length }})</span>
                        </button>
                    </div>

                    <form @submit.prevent="submitCreateAffiliate" class="space-y-4 text-xs overflow-y-auto pr-1 flex-1">
                        <!-- Alert Error Global -->
                        <div v-if="createForm.hasErrors" class="p-3.5 bg-rose-50 border border-rose-200 rounded-2xl text-rose-700 space-y-1">
                            <div class="flex items-center gap-1.5 font-bold">
                                <span>⚠️</span>
                                <span>Gagal mendaftarkan agen. Periksa kesalahan berikut:</span>
                            </div>
                            <ul class="list-disc list-inside space-y-0.5 pl-1 text-[11px] text-rose-600">
                                <li v-for="(errMsg, f) in createForm.errors" :key="f">{{ errMsg }}</li>
                            </ul>
                        </div>

                        <!-- Mode Existing User -->
                        <div v-if="createForm.mode === 'existing_user'" class="space-y-3">
                            <div>
                                <label class="block text-slate-700 font-bold mb-1">Pilih Pengguna / Karyawan *</label>
                                <select
                                    v-model="createForm.user_id"
                                    @change="onCandidateUserChanged"
                                    required
                                    class="w-full px-3 py-2 border rounded-xl font-medium focus:ring-2 focus:ring-indigo-500"
                                    :class="createForm.errors.user_id ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'"
                                >
                                    <option value="" disabled>-- Pilih Pengguna Aktif --</option>
                                    <option v-for="u in (candidate_users || [])" :key="u.id" :value="u.id">
                                        {{ u.name }} ({{ u.email }}) • {{ u.role || 'user' }}
                                    </option>
                                </select>
                                <p v-if="createForm.errors.user_id" class="text-rose-500 text-[11px] font-semibold mt-1">{{ createForm.errors.user_id }}</p>
                                <p v-if="!candidate_users || candidate_users.length === 0" class="text-amber-600 text-[11px] mt-1">
                                    Semua akun pengguna saat ini telah memiliki profil agen afiliasi. Anda dapat mendaftarkan akun baru pada tab di atas.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-700 font-bold mb-1">Nomor WhatsApp Agen</label>
                                    <input
                                        v-model="createForm.whatsapp"
                                        type="text"
                                        placeholder="08xxxxxxxxxx"
                                        class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500"
                                        :class="createForm.errors.whatsapp ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'"
                                    />
                                    <p v-if="createForm.errors.whatsapp" class="text-rose-500 text-[11px] font-semibold mt-1">{{ createForm.errors.whatsapp }}</p>
                                </div>
                                <div>
                                    <label class="block text-slate-700 font-bold mb-1">Kode Referral Kustom (Opsional)</label>
                                    <input
                                        v-model="createForm.referral_code"
                                        type="text"
                                        placeholder="Otomatis jika kosong (misal: MITRA-123)"
                                        class="w-full px-3 py-2 border rounded-xl uppercase font-mono focus:ring-2 focus:ring-indigo-500"
                                        :class="createForm.errors.referral_code ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'"
                                    />
                                    <p v-if="createForm.errors.referral_code" class="text-rose-500 text-[11px] font-semibold mt-1">{{ createForm.errors.referral_code }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Mode New User -->
                        <div v-else class="space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-700 font-bold mb-1">Nama Lengkap *</label>
                                    <input
                                        v-model="createForm.name"
                                        type="text"
                                        placeholder="Nama Lengkap Agen"
                                        required
                                        class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500"
                                        :class="createForm.errors.name ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'"
                                    />
                                    <p v-if="createForm.errors.name" class="text-rose-500 text-[11px] font-semibold mt-1">{{ createForm.errors.name }}</p>
                                </div>
                                <div>
                                    <label class="block text-slate-700 font-bold mb-1">Email Aktif *</label>
                                    <input
                                        v-model="createForm.email"
                                        type="email"
                                        placeholder="email@contoh.com"
                                        required
                                        class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500"
                                        :class="createForm.errors.email ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'"
                                    />
                                    <p v-if="createForm.errors.email" class="text-rose-500 text-[11px] font-semibold mt-1">{{ createForm.errors.email }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-700 font-bold mb-1">Nomor WhatsApp *</label>
                                    <input
                                        v-model="createForm.phone"
                                        type="text"
                                        placeholder="08xxxxxxxxxx"
                                        required
                                        class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500"
                                        :class="createForm.errors.phone ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'"
                                    />
                                    <p v-if="createForm.errors.phone" class="text-rose-500 text-[11px] font-semibold mt-1">{{ createForm.errors.phone }}</p>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-slate-700 font-bold">Password Login *</label>
                                        <button
                                            type="button"
                                            @click="generateRandomPassword"
                                            class="text-[11px] text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-0.5"
                                        >
                                            🎲 Acak Password
                                        </button>
                                    </div>
                                    <div class="relative">
                                        <input
                                            v-model="createForm.password"
                                            :type="showPassword ? 'text' : 'password'"
                                            placeholder="Minimal 6 karakter"
                                            required
                                            class="w-full px-3 py-2 pr-9 border rounded-xl focus:ring-2 focus:ring-indigo-500 font-mono text-xs"
                                            :class="createForm.errors.password ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'"
                                        />
                                        <button
                                            type="button"
                                            @click="showPassword = !showPassword"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 text-xs"
                                        >
                                            {{ showPassword ? '🙈' : '👁️' }}
                                        </button>
                                    </div>
                                    <p v-if="createForm.errors.password" class="text-rose-500 text-[11px] font-semibold mt-1">{{ createForm.errors.password }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-700 font-bold mb-1">Wilayah / Area Penempatan</label>
                                    <select
                                        v-model="createForm.area_id"
                                        class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-indigo-500 border-slate-300"
                                    >
                                        <option value="">-- Semua Area / Bebas --</option>
                                        <option v-for="area in (areas || [])" :key="area.id" :value="area.id">
                                            {{ area.name }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-slate-700 font-bold mb-1">Kode Referral Kustom (Opsional)</label>
                                    <input
                                        v-model="createForm.referral_code"
                                        type="text"
                                        placeholder="Otomatis jika kosong"
                                        class="w-full px-3 py-2 border rounded-xl uppercase font-mono focus:ring-2 focus:ring-indigo-500"
                                        :class="createForm.errors.referral_code ? 'border-rose-400 bg-rose-50/20' : 'border-slate-300'"
                                    />
                                    <p v-if="createForm.errors.referral_code" class="text-rose-500 text-[11px] font-semibold mt-1">{{ createForm.errors.referral_code }}</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-slate-700 font-bold mb-1">Alamat Domisili (Opsional)</label>
                                <input
                                    v-model="createForm.address"
                                    type="text"
                                    placeholder="Alamat lengkap tempat tinggal agen"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                        </div>

                        <!-- UPLINE SPONSOR (KUNCI DOWNLINE) -->
                        <div class="p-3.5 bg-indigo-50/70 border border-indigo-200 rounded-2xl space-y-1.5">
                            <label class="block font-bold text-indigo-900">
                                🌳 Upline Sponsor (Atasan / Induk Jaringan)
                            </label>
                            <p class="text-[11px] text-indigo-700 leading-relaxed">
                                Jika dipilih Upline, agen ini otomatis menjadi <strong>Downline Level 1</strong> dari agen tersebut. Komisi berjenjang (Tier 1 & Tier 2) akan mengalir ke upline saat ada pelanggan aktif.
                            </p>
                            <select
                                v-model="createForm.upline_id"
                                class="w-full mt-1 px-3 py-2 bg-white border border-indigo-300 rounded-xl font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500"
                                :class="createForm.errors.upline_id ? 'border-rose-400 bg-rose-50/20' : ''"
                            >
                                <option value="">-- Tanpa Upline (Menjadi Upline Utama / Direct Kantor) --</option>
                                <option v-for="up in available_uplines" :key="up.user_id" :value="up.user_id">
                                    {{ up.name }} (Kode: {{ up.referral_code }})
                                </option>
                            </select>
                            <p v-if="createForm.errors.upline_id" class="text-rose-500 text-[11px] font-semibold mt-1">{{ createForm.errors.upline_id }}</p>
                        </div>

                        <!-- DATA BANK / E-WALLET -->
                        <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl space-y-2.5">
                            <span class="font-bold text-slate-800 uppercase tracking-wider text-[10px] block">Rekening Bank / E-Wallet Pencairan Komisi (Opsional)</span>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                <div>
                                    <label class="block text-slate-600 font-bold mb-1 text-[11px]">Nama Bank / E-Wallet</label>
                                    <input
                                        v-model="createForm.bank_name"
                                        type="text"
                                        placeholder="BCA / BRI / DANA"
                                        class="w-full px-3 py-1.5 border border-slate-300 rounded-lg text-xs"
                                    />
                                </div>
                                <div>
                                    <label class="block text-slate-600 font-bold mb-1 text-[11px]">Nomor Rekening / No. WA</label>
                                    <input
                                        v-model="createForm.bank_account_number"
                                        type="text"
                                        placeholder="1234567890"
                                        class="w-full px-3 py-1.5 border border-slate-300 rounded-lg font-mono text-xs"
                                    />
                                </div>
                                <div>
                                    <label class="block text-slate-600 font-bold mb-1 text-[11px]">Atas Nama Rekening</label>
                                    <input
                                        v-model="createForm.bank_account_holder"
                                        type="text"
                                        :placeholder="createForm.name || 'Nama Pemilik'"
                                        class="w-full px-3 py-1.5 border border-slate-300 rounded-lg text-xs"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="pt-2 flex justify-end gap-2 shrink-0 border-t border-slate-100">
                            <button
                                type="button"
                                @click="showCreateAffiliateModal = false"
                                class="px-4 py-2 border border-slate-300 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition-colors"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="createForm.processing"
                                class="px-5 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5 disabled:opacity-50"
                            >
                                <span v-if="createForm.processing" class="inline-block animate-spin">⏳</span>
                                <span>{{ createForm.processing ? 'Menyimpan...' : 'Daftarkan Agen' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ════ MODAL: ATUR / EDIT UPLINE SPONSOR ════ -->
        <Teleport to="body">
            <div v-if="showUplineModal && selectedAffiliateForUpline" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showUplineModal = false">
                <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-100 p-6 sm:p-7">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Atur Upline Sponsor</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Tentukan atasan / sponsor untuk agen ini di jaringan multi-tier.</p>
                        </div>
                        <button type="button" @click="showUplineModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <form @submit.prevent="submitUpline" class="space-y-4 text-xs">
                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 space-y-1">
                            <div class="text-[11px] text-slate-500">Agen yang diatur:</div>
                            <div class="font-bold text-sm text-slate-900">{{ selectedAffiliateForUpline.name }}</div>
                            <div class="text-[11px] font-mono text-indigo-600 font-bold">Kode Referral: {{ selectedAffiliateForUpline.referral_code }}</div>
                            <div class="text-[11px] text-slate-600 mt-1">
                                Upline saat ini: <strong :class="selectedAffiliateForUpline.upline_name !== '-' ? 'text-indigo-900' : 'text-slate-500'">{{ selectedAffiliateForUpline.upline_name || '-' }}</strong>
                            </div>
                        </div>

                        <div>
                            <label class="block text-slate-800 font-bold mb-1.5">
                                Pilih Upline Sponsor Baru:
                            </label>
                            <select v-model="uplineForm.upline_id" class="w-full px-3.5 py-2.5 border border-indigo-300 rounded-xl font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500 bg-white">
                                <option value="">-- Tanpa Upline (Jadikan Upline Utama / Direct Kantor) --</option>
                                <option
                                    v-for="up in (available_uplines || []).filter(u => u.user_id !== selectedAffiliateForUpline.user_id)"
                                    :key="up.user_id"
                                    :value="up.user_id"
                                >
                                    {{ up.name }} (Kode: {{ up.referral_code }})
                                </option>
                            </select>
                            <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">
                                💡 Jika Anda memilih salah satu agen di atas, maka <strong>{{ selectedAffiliateForUpline.name }}</strong> akan menjadi <strong>Downline</strong>-nya.
                            </p>
                        </div>

                        <!-- Footer Actions -->
                        <div class="pt-2 flex justify-end gap-2">
                            <button type="button" @click="showUplineModal = false" class="px-4 py-2 border border-slate-300 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50">
                                Batal
                            </button>
                            <button type="submit" :disabled="uplineForm.processing" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors">
                                {{ uplineForm.processing ? 'Menyimpan...' : 'Simpan Upline' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    tab: String,
    settings: Object,
    affiliates: Object,
    available_uplines: Array,
    candidate_users: Array,
    areas: Array,
    commissions: Object,
    withdrawals: Object,
    kpi: Object,
    tree_data: Array,
    tree_kpi: Object,
    filters: Object,
});

const currentTab = ref(props.tab || 'diagram');
const searchQuery = ref(props.filters?.search || '');
const diagramActiveFilter = ref('all');

// Tree Diagram State
const treeViewMode = ref('diagram'); // 'diagram' | 'list'
const selectedUplineIndex = ref(0);
const treeSearch = ref('');
const treeZoom = ref(100);
const expandedDownlines = ref({ 8: true }); // expand IMALULHAKIM by default
const selectedMemberModal = ref(null);
const showMemberModal = ref(false);
const listFilterLevel = ref('all');
const showArchitectureGuide = ref(false);

const toggleDownlineExpand = (id) => {
    expandedDownlines.value[id] = !expandedDownlines.value[id];
};

const setTreeZoom = (delta) => {
    if (delta === 0) {
        treeZoom.value = 100;
        return;
    }
    const next = treeZoom.value + delta;
    if (next >= 60 && next <= 150) {
        treeZoom.value = next;
    }
};

const activeUplineTree = computed(() => {
    if (!props.tree_data || props.tree_data.length === 0) return null;
    const idx = Math.min(selectedUplineIndex.value, props.tree_data.length - 1);
    return props.tree_data[idx] || props.tree_data[0];
});

const isNodeMatched = (node) => {
    if (!treeSearch.value.trim() || !node) return false;
    const q = treeSearch.value.toLowerCase().trim();
    return (
        node.name?.toLowerCase().includes(q) ||
        node.referral_code?.toLowerCase().includes(q) ||
        String(node.id).includes(q)
    );
};

const openMemberModal = (member, level = 'Upline') => {
    if (!member) return;
    selectedMemberModal.value = {
        ...member,
        modal_level: level,
    };
    showMemberModal.value = true;
};

const flatMemberList = computed(() => {
    const list = [];
    if (!props.tree_data) return list;
    props.tree_data.forEach(upline => {
        list.push({
            ...upline,
            level_label: 'Upline Utama',
            level_badge: 'bg-blue-100 text-blue-700 border-blue-200',
            sponsor_name: '-',
        });
        (upline.downlines || []).forEach(l1 => {
            list.push({
                ...l1,
                level_label: 'Downline 1',
                level_badge: 'bg-emerald-100 text-emerald-700 border-emerald-200',
                sponsor_name: upline.name,
            });
            (l1.downlines || []).forEach(l2 => {
                list.push({
                    ...l2,
                    level_label: 'Downline 2',
                    level_badge: 'bg-amber-100 text-amber-700 border-amber-200',
                    sponsor_name: l1.name,
                });
            });
        });
    });

    return list.filter(m => {
        if (listFilterLevel.value === 'upline' && m.level_label !== 'Upline Utama') return false;
        if (listFilterLevel.value === 'l1' && m.level_label !== 'Downline 1') return false;
        if (listFilterLevel.value === 'l2' && m.level_label !== 'Downline 2') return false;
        if (treeSearch.value.trim()) {
            const q = treeSearch.value.toLowerCase().trim();
            return (
                m.name?.toLowerCase().includes(q) ||
                m.referral_code?.toLowerCase().includes(q) ||
                m.email?.toLowerCase().includes(q)
            );
        }
        return true;
    });
});

const settingsForm = useForm({
    affiliate_commission_booking: props.settings.affiliate_commission_booking || 35000,
    affiliate_commission_monthly: props.settings.affiliate_commission_monthly || 5000,
    affiliate_commission_upline_1: props.settings.affiliate_commission_upline_1 || 2000,
    affiliate_commission_upline_2: props.settings.affiliate_commission_upline_2 || 1000,
    affiliate_min_withdraw: props.settings.affiliate_min_withdraw || 50000,
    affiliate_contract_min_months: props.settings.affiliate_contract_min_months || 4,
    affiliate_payout_day: props.settings.affiliate_payout_day || 15,
});

// Simulator Pasif Income
const simDownlinesL1 = ref(10);
const simCustPerL1 = ref(20);
const simDownlinesL2 = ref(25);
const simCustPerL2 = ref(15);

const simTotalCustL1 = computed(() => Number(simDownlinesL1.value || 0) * Number(simCustPerL1.value || 0));
const simTotalCustL2 = computed(() => Number(simDownlinesL2.value || 0) * Number(simCustPerL2.value || 0));
const simIncomeL1 = computed(() => simTotalCustL1.value * Number(settingsForm.affiliate_commission_upline_1 || 2000));
const simIncomeL2 = computed(() => simTotalCustL2.value * Number(settingsForm.affiliate_commission_upline_2 || 1000));
const simTotalPassiveMonthly = computed(() => simIncomeL1.value + simIncomeL2.value);
const simTotalPassiveYearly = computed(() => simTotalPassiveMonthly.value * 12);

const selectedWithdrawal = ref(null);
const showApproveModal = ref(false);
const showRejectModal = ref(false);

const approveForm = useForm({
    reference_no: '',
    receipt: null,
});

const rejectForm = useForm({
    reason: '',
});

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const switchTab = (tabId) => {
    currentTab.value = tabId;
    router.get(route('admin.affiliates.index'), { tab: tabId }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const handleSearch = () => {
    router.get(route('admin.affiliates.index'), { tab: currentTab.value, search: searchQuery.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const submitSettings = () => {
    settingsForm.post(route('admin.affiliates.settings'), {
        preserveScroll: true,
    });
};

const toggleAffiliateStatus = (affiliate) => {
    if (confirm(`Ubah status akun ${affiliate.name}?`)) {
        router.post(route('admin.affiliates.toggle-status', affiliate.id), {}, {
            preserveScroll: true,
        });
    }
};

const openApproveModal = (w) => {
    selectedWithdrawal.value = w;
    approveForm.reference_no = w.reference_no || '';
    approveForm.receipt = null;
    showApproveModal.value = true;
};

const submitApprove = () => {
    if (!selectedWithdrawal.value) return;
    approveForm.post(route('admin.affiliates.withdrawals.approve', selectedWithdrawal.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showApproveModal.value = false;
            approveForm.reset();
        },
    });
};

const openRejectModal = (w) => {
    selectedWithdrawal.value = w;
    rejectForm.reason = '';
    showRejectModal.value = true;
};

const submitReject = () => {
    if (!selectedWithdrawal.value) return;
    rejectForm.post(route('admin.affiliates.withdrawals.reject', selectedWithdrawal.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showRejectModal.value = false;
            rejectForm.reset();
        },
    });
};

// ── Manual Create Affiliate State & Methods ──
const showCreateAffiliateModal = ref(false);
const showPassword = ref(false);
const createForm = useForm({
    mode: 'new_user',
    user_id: '',
    name: '',
    email: '',
    phone: '',
    whatsapp: '',
    password: '',
    upline_id: '',
    area_id: '',
    referral_code: '',
    bank_name: '',
    bank_account_number: '',
    bank_account_holder: '',
    address: '',
});

const generateRandomPassword = () => {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$';
    let pass = 'Ag';
    for (let i = 0; i < 7; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    createForm.password = pass;
    showPassword.value = true;
};

const onCandidateUserChanged = () => {
    if (!createForm.user_id) return;
    const selected = (props.candidate_users || []).find(u => u.id === createForm.user_id);
    if (selected) {
        if (selected.phone) {
            createForm.whatsapp = selected.phone;
        }
        createForm.bank_account_holder = selected.name;
    }
};

const openCreateAffiliateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.mode = 'new_user';
    showPassword.value = false;
    showCreateAffiliateModal.value = true;
};

const submitCreateAffiliate = () => {
    createForm.post(route('admin.affiliates.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateAffiliateModal.value = false;
            createForm.reset();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Agen afiliasi baru berhasil didaftarkan.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });
        },
    });
};

// ── Set / Update Upline State & Methods ──
const showUplineModal = ref(false);
const selectedAffiliateForUpline = ref(null);
const uplineForm = useForm({
    upline_id: '',
});

const openUplineModal = (affiliate) => {
    selectedAffiliateForUpline.value = affiliate;
    uplineForm.upline_id = affiliate.upline_id || '';
    uplineForm.clearErrors();
    showUplineModal.value = true;
};

const submitUpline = () => {
    if (!selectedAffiliateForUpline.value) return;
    uplineForm.post(route('admin.affiliates.update-upline', selectedAffiliateForUpline.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showUplineModal.value = false;
            uplineForm.reset();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Upline sponsor berhasil diperbarui.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });
        },
    });
};
</script>
