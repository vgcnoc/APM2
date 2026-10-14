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
                            { id: 'diagram', label: 'Arsitektur & Diagram Cerdas', icon: '🧠' },
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

                <!-- ════ TAB DIAGRAM: ARSITEKTUR & SKEMA AFILIASI CERDAS ════ -->
                <div v-if="currentTab === 'diagram'" class="p-6 sm:p-8 space-y-8">
                    <!-- Filter Pills -->
                    <div class="flex flex-wrap items-center gap-2 pb-2 border-b border-slate-100">
                        <span class="text-xs font-bold text-slate-500 mr-2">Fokus Alur:</span>
                        <button
                            v-for="f in [
                                { id: 'all', label: '🌐 Semua Alur Sistem' },
                                { id: 'employee', label: '👔 Jalur Karyawan (Slip Gaji)' },
                                { id: 'external', label: '🤝 Jalur Mitra Luar (Dompet)' },
                                { id: 'multitier', label: '🌳 Jaringan Multi-Tier' },
                                { id: 'clawback', label: '✂️ Smart Pinalti < 4 Bulan' },
                                { id: 'sim', label: '🧮 Simulator Pasif Income' },
                            ]"
                            :key="f.id"
                            type="button"
                            @click="diagramActiveFilter = f.id"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all"
                            :class="diagramActiveFilter === f.id ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        >
                            {{ f.label }}
                        </button>
                    </div>

                    <!-- 1. SMART DUAL-ROUTE ENGINE (KARYAWAN VS MITRA LUAR) -->
                    <div v-if="['all', 'employee', 'external'].includes(diagramActiveFilter)" class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-lg border border-indigo-500/20">
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
                                            <span class="font-bold text-white block">Pinalti Stop Dini (&lt; 4 Bulan):</span>
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
                                            <span class="font-bold text-white block">Pinalti Stop Dini (&lt; 4 Bulan):</span>
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

                    <!-- 2. SKEMA MULTI-TIER PASIF INCOME REALTIME -->
                    <div v-if="['all', 'multitier'].includes(diagramActiveFilter)" class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-7 shadow-xs space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                    <span>🌳</span>
                                    <span>Struktur Jaringan Multi-Tier &amp; Aliran Pasif Income</span>
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Model Unilevel Terbuka (Unlimited Width) &bull; Pasif income mengalir otomatis dari 2 Level binaan setiap invoice lunas.
                                </p>
                            </div>
                            <span class="inline-flex self-start sm:self-auto text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-3 py-1 rounded-full">
                                ♾️ Rekrut Downline Tanpa Batas Kuota
                            </span>
                        </div>

                        <!-- 3 Kartu Tingkatan Bertingkat -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Tier 0 -->
                            <div class="p-5 rounded-2xl bg-emerald-50/70 border border-emerald-200 space-y-2 relative overflow-hidden">
                                <div class="inline-flex items-center gap-1 text-[10px] uppercase font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">
                                    🟢 Tingkat 0: Direct Sales
                                </div>
                                <div class="text-2xl font-black text-emerald-700">
                                    {{ formatRupiah(settingsForm.affiliate_commission_monthly) }}
                                    <span class="text-xs font-normal text-slate-500">/ cust / bln</span>
                                </div>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Didapatkan oleh mitra/sales yang mendaftarkan pelanggan secara langsung setiap kali invoice bulanan lunas.
                                </p>
                            </div>

                            <!-- Tier 1 -->
                            <div class="p-5 rounded-2xl bg-indigo-50/70 border border-indigo-200 space-y-2 relative overflow-hidden">
                                <div class="inline-flex items-center gap-1 text-[10px] uppercase font-bold text-indigo-700 bg-indigo-100 px-2 py-0.5 rounded-full">
                                    🔵 Tingkat 1: Upline Level 1
                                </div>
                                <div class="text-2xl font-black text-indigo-700">
                                    {{ formatRupiah(settingsForm.affiliate_commission_upline_1) }}
                                    <span class="text-xs font-normal text-slate-500">/ cust / bln</span>
                                </div>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Pasif income yang dinikmati Upline dari seluruh pembayaran tagihan milik pelanggan mitra binaan langsung (Generasi 1).
                                </p>
                            </div>

                            <!-- Tier 2 -->
                            <div class="p-5 rounded-2xl bg-purple-50/70 border border-purple-200 space-y-2 relative overflow-hidden">
                                <div class="inline-flex items-center gap-1 text-[10px] uppercase font-bold text-purple-700 bg-purple-100 px-2 py-0.5 rounded-full">
                                    🟣 Tingkat 2: Upline Level 2
                                </div>
                                <div class="text-2xl font-black text-purple-700">
                                    {{ formatRupiah(settingsForm.affiliate_commission_upline_2) }}
                                    <span class="text-xs font-normal text-slate-500">/ cust / bln</span>
                                </div>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Pasif income ekstra dari pelanggan yang didaftarkan oleh mitra di bawah mitra Anda (Generasi 2).
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 3. SMART GUARD KONTRAK 4 BULAN (PINALTI STOP DINI) -->
                    <div v-if="['all', 'clawback', 'employee', 'external'].includes(diagramActiveFilter)" class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-7 shadow-xs space-y-6">
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

                    <!-- 4. LIVE INTERACTIVE PASSIVE INCOME SIMULATOR -->
                    <div v-if="['all', 'multitier', 'sim'].includes(diagramActiveFilter)" class="bg-gradient-to-br from-indigo-900 via-slate-900 to-indigo-950 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-indigo-400/20 space-y-6">
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
                            <!-- Input 1: Downline L1 -->
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

                            <!-- Input 2: Cust per L1 -->
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

                            <!-- Input 3: Downline L2 -->
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

                            <!-- Input 4: Cust per L2 -->
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
                                    <td class="px-4 py-3 text-slate-600">{{ a.upline_name }}</td>
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
                                        <button
                                            type="button"
                                            @click="toggleAffiliateStatus(a)"
                                            class="px-2.5 py-1 text-[11px] font-bold rounded-lg transition-colors border"
                                            :class="a.status === 'active' ? 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'"
                                        >
                                            {{ a.status === 'active' ? 'Suspend' : 'Aktifkan' }}
                                        </button>
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
                                                'bg-rose-100 text-rose-800 border border-rose-200': c.type === 'clawback_booking',
                                            }"
                                        >
                                            <template v-if="c.type === 'booking'">🎯 Booking Baru</template>
                                            <template v-else-if="c.type === 'monthly_direct'">💰 Pasif Bulanan</template>
                                            <template v-else-if="c.type === 'upline_tier_1'">🚀 Upline Tier 1</template>
                                            <template v-else-if="c.type === 'upline_tier_2'">🌟 Upline Tier 2</template>
                                            <template v-else-if="c.type === 'clawback_booking'">✂️ Potong Booking (&lt; 4 Bln)</template>
                                            <template v-else>{{ c.type }}</template>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ c.description }}</td>
                                    <td class="px-4 py-3 text-right font-extrabold whitespace-nowrap" :class="c.amount < 0 || c.type === 'clawback_booking' ? 'text-rose-600' : 'text-emerald-600'">
                                        {{ (c.amount < 0 || c.type === 'clawback_booking') ? '-' : '+' }}{{ formatRupiah(Math.abs(c.amount)) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span 
                                            class="text-[10px] font-semibold uppercase px-2 py-0.5 rounded-full border"
                                            :class="{
                                                'bg-rose-50 text-rose-700 border-rose-200': c.type === 'clawback_booking',
                                                'bg-purple-50 text-purple-700 border-purple-200': c.status === 'payroll' && c.type !== 'clawback_booking',
                                                'bg-emerald-50 text-emerald-700 border-emerald-200': c.status === 'approved' && c.type !== 'clawback_booking'
                                            }"
                                        >
                                            {{ c.type === 'clawback_booking' ? (c.status === 'payroll' ? 'Potong Gaji' : 'Dipotong') : (c.status === 'payroll' ? 'Slip Gaji' : c.status) }}
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
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    tab: String,
    settings: Object,
    affiliates: Object,
    commissions: Object,
    withdrawals: Object,
    kpi: Object,
    filters: Object,
});

const currentTab = ref(props.tab || 'settings');
const searchQuery = ref(props.filters?.search || '');
const diagramActiveFilter = ref('all');

const settingsForm = useForm({
    affiliate_commission_booking: props.settings.affiliate_commission_booking || 35000,
    affiliate_commission_monthly: props.settings.affiliate_commission_monthly || 5000,
    affiliate_commission_upline_1: props.settings.affiliate_commission_upline_1 || 2000,
    affiliate_commission_upline_2: props.settings.affiliate_commission_upline_2 || 1000,
    affiliate_min_withdraw: props.settings.affiliate_min_withdraw || 50000,
    affiliate_contract_min_months: props.settings.affiliate_contract_min_months || 4,
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
</script>
