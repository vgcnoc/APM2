<template>
    <AppLayout title="Bagi Hasil & Kemitraan" subtitle="Sistem manajemen profit sharing, investor jaringan, dan bagi hasil mitra ISP">
        <div class="max-w-[1600px] w-full mx-auto space-y-6 font-sans">
            <!-- Flash notification -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-2xl text-xs font-bold flex items-center gap-3 shadow-xs animate-fade-in-up">
                <span class="text-lg">✅</span>
                <span>{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash?.error" class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 rounded-2xl text-xs font-bold flex items-center gap-3 shadow-xs animate-fade-in-up">
                <span class="text-lg">⚠️</span>
                <span>{{ $page.props.flash.error }}</span>
            </div>

            <!-- Header Banner -->
            <div class="rounded-3xl p-6 sm:p-8 bg-gradient-to-br from-slate-900 via-indigo-950 to-blue-950 text-white border border-slate-800 shadow-xl relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                
                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-400/30">
                            <span>🤝</span>
                            <span>SISTEM PROFIT SHARING & REVENUE SHARING</span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                            Kerjasama & Bagi Hasil Mitra
                        </h2>
                        <p class="text-xs sm:text-sm text-indigo-200 max-w-2xl leading-relaxed">
                            Otomatisasi pembagian hasil omset pelanggan antara ISP dan Mitra Jaringan (Investor Area, Reseller, Tokoh Wilayah). Transparan, presisi, dan siap cetak.
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        <button 
                            type="button" 
                            @click="openPartnershipModal()" 
                            class="px-5 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-md shadow-indigo-900/40 transition-all active:scale-95 flex items-center gap-2">
                            <span>➕</span>
                            <span>Hubungkan Kemitraan</span>
                        </button>
                        <button 
                            type="button" 
                            @click="openSchemeModal()" 
                            class="px-5 py-3 rounded-2xl bg-white/10 hover:bg-white/20 text-white border border-white/20 text-xs font-bold transition-all active:scale-95 flex items-center gap-2">
                            <span>📋</span>
                            <span>Tambah Skema</span>
                        </button>
                    </div>
                </div>

                <!-- KPI Metric Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mt-8 pt-6 border-t border-white/10">
                    <div class="p-4 rounded-2xl bg-white/5 border border-white/10">
                        <p class="text-[10px] font-bold text-indigo-300 uppercase tracking-wider">Total Omset Kemitraan</p>
                        <p class="text-xl sm:text-2xl font-black text-white font-mono mt-1">{{ formatCurrency(summary.total_gross_revenue) }}</p>
                        <p class="text-[10px] text-indigo-300/70 mt-0.5">Tagihan Lunas {{ period.label }}</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-white/5 border border-white/10">
                        <p class="text-[10px] font-bold text-amber-300 uppercase tracking-wider">Hak Bagi Hasil Mitra</p>
                        <p class="text-xl sm:text-2xl font-black text-amber-400 font-mono mt-1">{{ formatCurrency(summary.total_partner_share) }}</p>
                        <p class="text-[10px] text-amber-300/70 mt-0.5">Kewajiban Penyaluran</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-white/5 border border-white/10">
                        <p class="text-[10px] font-bold text-emerald-300 uppercase tracking-wider">Pendapatan Bersih ISP</p>
                        <p class="text-xl sm:text-2xl font-black text-emerald-400 font-mono mt-1">{{ formatCurrency(summary.total_isp_share) }}</p>
                        <p class="text-[10px] text-emerald-300/70 mt-0.5">Setelah Bagi Hasil</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-white/5 border border-white/10">
                        <p class="text-[10px] font-bold text-blue-300 uppercase tracking-wider">Total Pelanggan Kemitraan</p>
                        <p class="text-xl sm:text-2xl font-black text-blue-400 font-mono mt-1">{{ summary.total_customers }} <span class="text-xs font-sans text-blue-300/70">Orang</span></p>
                        <p class="text-[10px] text-blue-300/70 mt-0.5">{{ summary.active_partnerships }} Kemitraan Aktif</p>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation & Period Selector Bar -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <!-- Tabs -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0 scrollbar-none">
                    <button 
                        type="button" 
                        @click="activeTab = 'live'" 
                        class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer"
                        :class="activeTab === 'live' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-100' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'">
                        <span>📊</span>
                        <span>Rekap Bulanan (Live)</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-white/20 text-white font-mono font-normal">{{ live_calculations.length }}</span>
                    </button>

                    <button 
                        type="button" 
                        @click="activeTab = 'calculator'" 
                        class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer"
                        :class="activeTab === 'calculator' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-100' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'">
                        <span>⚡</span>
                        <span>Kalkulator Cepat</span>
                    </button>

                    <button 
                        type="button" 
                        @click="activeTab = 'schemes'" 
                        class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer"
                        :class="activeTab === 'schemes' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-100' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'">
                        <span>📋</span>
                        <span>Master Skema ({{ schemes.length }})</span>
                    </button>

                    <button 
                        type="button" 
                        @click="activeTab = 'partnerships'" 
                        class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer"
                        :class="activeTab === 'partnerships' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-100' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'">
                        <span>🤝</span>
                        <span>Daftar Kemitraan ({{ partnerships.length }})</span>
                    </button>

                    <button 
                        type="button" 
                        @click="activeTab = 'settlements'" 
                        class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer"
                        :class="activeTab === 'settlements' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-100' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'">
                        <span>📜</span>
                        <span>Riwayat Payout</span>
                    </button>
                </div>

                <!-- Period Selector -->
                <div class="flex items-center gap-2 shrink-0">
                    <select 
                        v-model="selectedMonth" 
                        @change="changePeriod" 
                        class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs font-bold text-gray-800 dark:text-gray-200 focus:ring-indigo-500">
                        <option v-for="(mName, mIdx) in monthNames" :key="mIdx" :value="mIdx + 1">{{ mName }}</option>
                    </select>

                    <select 
                        v-model="selectedYear" 
                        @change="changePeriod" 
                        class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs font-bold text-gray-800 dark:text-gray-200 focus:ring-indigo-500">
                        <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                    </select>

                    <button 
                        type="button" 
                        @click="setThisMonth" 
                        class="px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 text-xs font-bold text-gray-700 dark:text-gray-300 transition-colors">
                        Bulan Ini
                    </button>
                </div>
            </div>

            <!-- TAB 1: REKAP BULANAN LIVE -->
            <div v-if="activeTab === 'live'" class="space-y-4">
                <div v-if="live_calculations.length === 0" class="p-12 text-center bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 space-y-4">
                    <span class="text-4xl">🤝</span>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Belum Ada Kemitraan yang Terhubung</h3>
                    <p class="text-xs text-gray-500 max-w-md mx-auto">
                        Hubungkan wilayah cabang (Area) atau akun mitra ke skema profit sharing untuk mulai menghitung otomatis.
                    </p>
                    <button 
                        type="button" 
                        @click="openPartnershipModal()" 
                        class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700">
                        + Tambah Kemitraan Sekarang
                    </button>
                </div>

                <div v-else class="grid grid-cols-1 gap-4">
                    <div 
                        v-for="calc in live_calculations" 
                        :key="calc.partnership_id" 
                        class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-200/80 dark:border-gray-700 shadow-sm space-y-5 hover:border-indigo-300 dark:hover:border-indigo-600 transition-all">
                        
                        <!-- Top Row: Mitra Name & Status -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-700/60">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-base">🤝</span>
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ calc.partner_name }}</h3>
                                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase"
                                        :class="calc.partner_type === 'area' ? 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300'">
                                        {{ calc.target_display }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 flex items-center gap-2">
                                    <span>Skema: <strong class="text-indigo-600 dark:text-indigo-400">{{ calc.scheme_name }}</strong></span>
                                    <span>•</span>
                                    <span>Model: {{ calc.scheme_model_label }}</span>
                                </p>
                            </div>

                            <!-- Settlement Status Badge & Action -->
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 rounded-full text-xs font-bold"
                                    :class="{
                                        'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300': calc.status === 'unsettled',
                                        'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300': calc.status === 'draft',
                                        'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300': calc.status === 'approved',
                                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300': calc.status === 'paid',
                                    }">
                                    {{ calc.status === 'unsettled' ? 'Belum Dikunci' : (calc.status === 'draft' ? 'Draft Dikunci' : (calc.status === 'approved' ? 'Disetujui' : 'Lunas Disalurkan')) }}
                                </span>

                                <button 
                                    v-if="calc.status === 'unsettled'" 
                                    type="button" 
                                    @click="lockSettlement(calc)" 
                                    class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-xs">
                                    🔒 Kunci Rekap
                                </button>

                                <button 
                                    v-if="calc.settlement_id" 
                                    type="button" 
                                    @click="printSettlement(calc.settlement_id)" 
                                    class="px-3.5 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-xs font-bold text-gray-700 dark:text-gray-300">
                                    🖨️ Cetak Slip
                                </button>

                                <button 
                                    type="button" 
                                    @click="shareWhatsApp(calc)" 
                                    class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold flex items-center gap-1 shadow-xs">
                                    <span>📲</span>
                                    <span>WhatsApp</span>
                                </button>
                            </div>
                        </div>

                        <!-- Mid Row: Metrics Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                            <div class="p-3 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800">
                                <span class="text-gray-400 font-bold block text-[10px] uppercase">Pelanggan Lunas</span>
                                <span class="text-base font-black text-gray-800 dark:text-gray-100 font-mono mt-0.5 block">
                                    {{ calc.total_customers }} <span class="text-[10px] font-normal text-gray-500">({{ calc.total_paid_invoices }} Inv)</span>
                                </span>
                            </div>

                            <div class="p-3 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-800">
                                <span class="text-gray-400 font-bold block text-[10px] uppercase">Total Omset Wilayah</span>
                                <span class="text-base font-black text-gray-900 dark:text-white font-mono mt-0.5 block">
                                    {{ formatCurrency(calc.total_gross_revenue) }}
                                </span>
                            </div>

                            <div class="p-3 rounded-2xl bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200/50 dark:border-amber-800/40">
                                <span class="text-amber-700 dark:text-amber-400 font-bold block text-[10px] uppercase">Hak Bagi Hasil Mitra</span>
                                <span class="text-base font-black text-amber-600 dark:text-amber-400 font-mono mt-0.5 block">
                                    {{ formatCurrency(calc.partner_share_amount) }}
                                </span>
                            </div>

                            <div class="p-3 rounded-2xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200/50 dark:border-emerald-800/40">
                                <span class="text-emerald-700 dark:text-emerald-400 font-bold block text-[10px] uppercase">Bagian Bersih ISP</span>
                                <span class="text-base font-black text-emerald-600 dark:text-emerald-400 font-mono mt-0.5 block">
                                    {{ formatCurrency(calc.isp_share_amount) }}
                                </span>
                            </div>
                        </div>

                        <!-- Bottom Detail: Rumus & Rekening Bank -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between text-xs text-gray-500 pt-2 gap-2">
                            <div class="flex items-center gap-1.5 font-mono text-[11px] bg-slate-100 dark:bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800">
                                <span class="text-indigo-600 dark:text-indigo-400 font-bold">Rumus:</span>
                                <span>{{ calc.formula_detail }}</span>
                            </div>

                            <div v-if="calc.bank_info?.bank_account_number" class="text-[11px] font-mono text-gray-600 dark:text-gray-400">
                                <span>Tujuan: {{ calc.bank_info.bank_name }} - {{ calc.bank_info.bank_account_number }} a/n {{ calc.bank_info.bank_account_name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: KALKULATOR CEPAT (INSTANT SIMULATOR) -->
            <div v-if="activeTab === 'calculator'" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <div class="lg:col-span-7 bg-white dark:bg-gray-800 rounded-3xl p-6 sm:p-8 border border-gray-200/80 dark:border-gray-700 shadow-sm space-y-6">
                    <div class="space-y-1">
                        <div class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950 px-2.5 py-0.5 rounded-full">
                            <span>⚡</span>
                            <span>Simulasi Langsung</span>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Kalkulator Pembagian Omset Instan</h3>
                        <p class="text-xs text-gray-500">Cukup masukkan total omset dan persentase yang disepakati untuk menghitung bagian mitra dan ISP.</p>
                    </div>

                    <div class="space-y-4">
                        <!-- Model Kerjasama -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Pilih Model Kerjasama:</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <label 
                                    v-for="(label, key) in model_labels" 
                                    :key="key" 
                                    class="p-3 rounded-2xl border text-xs cursor-pointer transition-all flex items-start gap-2.5"
                                    :class="calcForm.model === key ? 'border-indigo-600 bg-indigo-50/40 dark:bg-indigo-950/30 text-indigo-900 dark:text-indigo-200 font-bold' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'">
                                    <input type="radio" :value="key" v-model="calcForm.model" @change="runQuickCalc" class="text-indigo-600 mt-0.5">
                                    <div>
                                        <p class="text-xs leading-snug">{{ label }}</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Total Omset -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Total Omset Tagihan (Rp):</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-gray-400">Rp</span>
                                <input 
                                    type="text" 
                                    :value="calcFormattedOmset" 
                                    @input="onCalcOmsetInput" 
                                    class="w-full pl-12 pr-4 py-3 rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-base font-black font-mono text-gray-900 dark:text-white focus:ring-indigo-500" 
                                    placeholder="Contoh: 30.000.000">
                            </div>
                            <div class="flex items-center gap-1.5 mt-2">
                                <button type="button" v-for="amt in [10000000, 25000000, 50000000, 100000000]" :key="amt" @click="calcForm.omset = amt; runQuickCalc()" class="px-2.5 py-1 rounded-lg border border-gray-200 dark:border-gray-700 text-[10px] font-bold text-gray-600 dark:text-gray-400 hover:bg-gray-100">
                                    {{ amt / 1000000 }} Jt
                                </button>
                            </div>
                        </div>

                        <!-- Persentase / Flat Amount -->
                        <div v-if="calcForm.model !== 'flat_per_customer'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Persen Mitra (%):</label>
                                <div class="relative">
                                    <input 
                                        type="number" 
                                        v-model="calcForm.percentage" 
                                        @input="runQuickCalc" 
                                        min="0" 
                                        max="100" 
                                        step="0.5" 
                                        class="w-full px-4 py-2.5 rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-sm font-bold font-mono text-gray-900 dark:text-white focus:ring-indigo-500">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">%</span>
                                </div>
                                <div class="flex items-center gap-1.5 mt-2">
                                    <button type="button" v-for="pct in [10, 20, 30, 40, 50]" :key="pct" @click="calcForm.percentage = pct; runQuickCalc()" class="px-2 py-0.5 rounded-lg border border-gray-200 dark:border-gray-700 text-[10px] font-bold text-gray-600 dark:text-gray-400 hover:bg-gray-100">
                                        {{ pct }}%
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Estimasi Pelanggan:</label>
                                <input 
                                    type="number" 
                                    v-model="calcForm.customer_count" 
                                    @input="runQuickCalc" 
                                    min="1" 
                                    class="w-full px-4 py-2.5 rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-sm font-bold font-mono text-gray-900 dark:text-white focus:ring-indigo-500">
                            </div>
                        </div>

                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Nominal Flat per User (Rp):</label>
                                <input 
                                    type="number" 
                                    v-model="calcForm.flat_amount" 
                                    @input="runQuickCalc" 
                                    min="0" 
                                    step="1000" 
                                    class="w-full px-4 py-2.5 rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-sm font-bold font-mono text-gray-900 dark:text-white focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Jumlah Pelanggan Aktif:</label>
                                <input 
                                    type="number" 
                                    v-model="calcForm.customer_count" 
                                    @input="runQuickCalc" 
                                    min="1" 
                                    class="w-full px-4 py-2.5 rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-sm font-bold font-mono text-gray-900 dark:text-white focus:ring-indigo-500">
                            </div>
                        </div>

                        <!-- Opsi Potongan Pajak & Operasional -->
                        <div v-if="calcForm.model === 'net_percentage'" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-3">
                            <label class="flex items-center gap-2 cursor-pointer text-xs">
                                <input type="checkbox" v-model="calcForm.deduct_tax" @change="runQuickCalc" class="rounded text-indigo-600">
                                <span class="font-bold text-gray-800 dark:text-gray-200">Potong Pajak Resmi (PPN 11% & BHP/USO ~1.75%) sebelum bagi hasil</span>
                            </label>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Potongan Operasional / Bandwidth Flat (Rp):</label>
                                <input 
                                    type="number" 
                                    v-model="calcForm.operational_deduction" 
                                    @input="runQuickCalc" 
                                    class="w-full px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs font-bold font-mono text-gray-900 dark:text-white">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Card: Live Result -->
                <div class="lg:col-span-5 bg-gradient-to-br from-indigo-900 via-indigo-950 to-slate-900 text-white rounded-3xl p-6 sm:p-8 border border-indigo-700/50 shadow-xl space-y-6">
                    <div class="space-y-1">
                        <span class="text-xs font-bold text-indigo-300 uppercase tracking-wider">Hasil Perhitungan Detik Ini</span>
                        <h4 class="text-xl font-black">Ringkasan Pembagian</h4>
                    </div>

                    <div class="space-y-3">
                        <div class="p-4 rounded-2xl bg-white/5 border border-white/10 space-y-1">
                            <span class="text-[10px] font-bold text-indigo-300 uppercase">Total Omset</span>
                            <p class="text-2xl font-black text-white font-mono">{{ formatCurrency(quickResult.omset || 0) }}</p>
                        </div>

                        <div class="p-4 rounded-2xl bg-amber-500/20 border border-amber-400/30 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold text-amber-300 uppercase">Hak Bagian Mitra</span>
                                <span class="text-xs font-bold text-amber-300 font-mono">{{ quickResult.effective_percentage || 0 }}%</span>
                            </div>
                            <p class="text-3xl font-black text-amber-400 font-mono">{{ formatCurrency(quickResult.partner_share || 0) }}</p>
                        </div>

                        <div class="p-4 rounded-2xl bg-emerald-500/20 border border-emerald-400/30 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold text-emerald-300 uppercase">Hak Pendapatan ISP</span>
                                <span class="text-xs font-bold text-emerald-300 font-mono">{{ 100 - (quickResult.effective_percentage || 0) }}%</span>
                            </div>
                            <p class="text-3xl font-black text-emerald-400 font-mono">{{ formatCurrency(quickResult.isp_share || 0) }}</p>
                        </div>
                    </div>

                    <div class="p-3.5 bg-black/20 rounded-2xl border border-white/10 text-xs font-mono space-y-1">
                        <p class="text-indigo-300 text-[10px] font-bold uppercase">Rincian Formula:</p>
                        <p class="text-white">{{ quickResult.formula_detail || '-' }}</p>
                    </div>

                    <button 
                        type="button" 
                        @click="copyWhatsAppSimulator" 
                        class="w-full py-3.5 rounded-2xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs flex items-center justify-center gap-2 transition-all active:scale-95 shadow-md">
                        <span>📲</span>
                        <span>{{ copiedWA ? 'Teks WhatsApp Tersalin!' : 'Salin Format Pesan WhatsApp' }}</span>
                    </button>
                </div>
            </div>

            <!-- TAB 3: MASTER SKEMA KERJASAMA -->
            <div v-if="activeTab === 'schemes'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Daftar Skema Kerjasama</h3>
                        <p class="text-xs text-gray-500">Buat beragam aturan bagi hasil sesuai kesepakatan masing-masing mitra.</p>
                    </div>
                    <button 
                        type="button" 
                        @click="openSchemeModal()" 
                        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold flex items-center gap-1.5 shadow-xs">
                        <span>➕</span>
                        <span>Tambah Skema</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div 
                        v-for="s in schemes" 
                        :key="s.id" 
                        class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-gray-200/80 dark:border-gray-700 shadow-sm space-y-4 flex flex-col justify-between">
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] font-mono px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-bold">{{ s.code }}</span>
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold"
                                    :class="s.is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-gray-100 text-gray-500'">
                                    {{ s.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>

                            <h4 class="text-sm font-bold text-gray-900 dark:text-white leading-snug">{{ s.name }}</h4>
                            <p class="text-xs text-indigo-600 dark:text-indigo-400 font-medium">{{ s.model_label }}</p>
                            <p class="text-[11px] text-gray-500 leading-relaxed">{{ s.description || 'Tidak ada keterangan khusus.' }}</p>

                            <!-- Key Specs -->
                            <div class="pt-2 border-t border-gray-100 dark:border-gray-700/60 text-xs space-y-1">
                                <div v-if="s.model === 'flat_per_customer'" class="flex justify-between">
                                    <span class="text-gray-400">Nominal Flat:</span>
                                    <span class="font-bold text-gray-800 dark:text-gray-200">{{ formatCurrency(s.flat_per_customer_amount) }} / user</span>
                                </div>
                                <div v-else class="flex justify-between">
                                    <span class="text-gray-400">Bagi Hasil:</span>
                                    <span class="font-bold text-amber-600">{{ s.partner_percentage }}% Mitra</span>
                                </div>
                                <div v-if="s.registration_bonus > 0" class="flex justify-between">
                                    <span class="text-gray-400">Bonus PSB:</span>
                                    <span class="font-bold text-emerald-600">+ {{ formatCurrency(s.registration_bonus) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between text-xs">
                            <span class="text-[10px] text-gray-400">{{ s.partnerships_count || 0 }} Kemitraan Memakai</span>
                            <div class="flex items-center gap-1.5">
                                <button type="button" @click="editScheme(s)" class="px-2.5 py-1 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold text-[11px]">Edit</button>
                                <button v-if="!s.partnerships_count" type="button" @click="deleteScheme(s)" class="px-2.5 py-1 rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 font-bold text-[11px]">Hapus</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: DATA KEMITRAAN (PARTNERSHIPS) -->
            <div v-if="activeTab === 'partnerships'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Daftar Kemitraan Aktif</h3>
                        <p class="text-xs text-gray-500">Hubungkan wilayah (Area) atau akun mitra perorangan ke skema bagi hasil.</p>
                    </div>
                    <button 
                        type="button" 
                        @click="openPartnershipModal()" 
                        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold flex items-center gap-1.5 shadow-xs">
                        <span>➕</span>
                        <span>Hubungkan Kemitraan Baru</span>
                    </button>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-200/80 dark:border-gray-700 overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-100 dark:border-gray-700 text-gray-500 font-bold uppercase tracking-wider">
                                <tr>
                                    <th class="py-3.5 px-4">Nama Mitra</th>
                                    <th class="py-3.5 px-4">Tipe Kemitraan</th>
                                    <th class="py-3.5 px-4">Skema Kerjasama</th>
                                    <th class="py-3.5 px-4">Kontak / Bank</th>
                                    <th class="py-3.5 px-4 text-center">Status</th>
                                    <th class="py-3.5 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 text-gray-700 dark:text-gray-300">
                                <tr v-for="p in partnerships" :key="p.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="py-3 px-4 font-bold text-gray-900 dark:text-white">
                                        {{ p.partner_name }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                            :class="p.partner_type === 'area' ? 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300'">
                                            {{ p.target_display_name }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ p.scheme?.name || '-' }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-[11px] text-gray-500 font-mono">
                                        <p v-if="p.partner_phone">{{ p.partner_phone }}</p>
                                        <p v-if="p.bank_name">{{ p.bank_name }} - {{ p.bank_account_number }}</p>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                            :class="p.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'">
                                            {{ p.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" @click="editPartnership(p)" class="px-2.5 py-1 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-100 text-gray-700 dark:text-gray-300 font-bold">Edit</button>
                                            <button type="button" @click="deletePartnership(p)" class="px-2.5 py-1 rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 font-bold">Hapus</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 5: RIWAYAT SETTLEMENTS -->
            <div v-if="activeTab === 'settlements'" class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Riwayat Rekon & Payout Bagi Hasil</h3>
                        <p class="text-xs text-gray-500">Daftar rekapitulasi bagi hasil bulanan yang telah dikunci atau disalurkan ke mitra.</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-200/80 dark:border-gray-700 overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-100 dark:border-gray-700 text-gray-500 font-bold uppercase tracking-wider">
                                <tr>
                                    <th class="py-3.5 px-4">No. Settlement</th>
                                    <th class="py-3.5 px-4">Mitra / Wilayah</th>
                                    <th class="py-3.5 px-4">Periode</th>
                                    <th class="py-3.5 px-4 text-right">Omset Tagihan</th>
                                    <th class="py-3.5 px-4 text-right">Hak Mitra</th>
                                    <th class="py-3.5 px-4 text-right">Hak ISP</th>
                                    <th class="py-3.5 px-4 text-center">Status</th>
                                    <th class="py-3.5 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 text-gray-700 dark:text-gray-300">
                                <tr v-for="s in settlements" :key="s.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="py-3 px-4 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ s.settlement_number }}
                                    </td>
                                    <td class="py-3 px-4 font-bold text-gray-900 dark:text-white">
                                        {{ s.partnership?.partner_name || '-' }}
                                    </td>
                                    <td class="py-3 px-4">
                                        {{ s.period_label }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-bold">
                                        {{ formatCurrency(s.total_gross_revenue) }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-amber-600">
                                        {{ formatCurrency(s.partner_share_amount) }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-emerald-600">
                                        {{ formatCurrency(s.isp_share_amount) }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <select 
                                            :value="s.status" 
                                            @change="updateSettlementStatus(s, $event.target.value)" 
                                            class="px-2 py-1 rounded-lg text-[10px] font-bold border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                                            <option value="draft">Draft</option>
                                            <option value="approved">Disetujui</option>
                                            <option value="paid">Lunas Disalurkan</option>
                                        </select>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <button 
                                            type="button" 
                                            @click="printSettlement(s.id)" 
                                            class="px-2.5 py-1 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 text-xs font-bold text-gray-700 dark:text-gray-300">
                                            🖨️ Cetak
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- MODAL TAMBAH/EDIT SKEMA KERJASAMA -->
            <div v-if="schemeModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
                <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-xl w-full p-6 sm:p-8 space-y-5 border border-gray-200 dark:border-gray-700 shadow-2xl max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ schemeForm.id ? 'Edit Skema Kerjasama' : 'Tambah Skema Kerjasama Baru' }}
                        </h3>
                        <button type="button" @click="schemeModalOpen = false" class="text-gray-400 hover:text-gray-600 text-lg">✕</button>
                    </div>

                    <form @submit.prevent="submitScheme" class="space-y-4 text-xs">
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Nama Skema:</label>
                            <input type="text" v-model="schemeForm.name" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white" placeholder="Contoh: Investor Jaringan Desa 30%">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Kode Skema:</label>
                                <input type="text" v-model="schemeForm.code" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 font-mono text-gray-900 dark:text-white" placeholder="SCH-001">
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Model Kerjasama:</label>
                                <select v-model="schemeForm.model" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white">
                                    <option v-for="(lbl, k) in model_labels" :key="k" :value="k">{{ lbl }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Param Model -->
                        <div v-if="schemeForm.model !== 'flat_per_customer'" class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Persen Mitra (%):</label>
                                <input type="number" v-model="schemeForm.partner_percentage" min="0" max="100" step="0.5" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 font-mono text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Persen ISP (%):</label>
                                <input type="number" :value="100 - (schemeForm.partner_percentage || 0)" disabled class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 font-mono text-gray-500">
                            </div>
                        </div>

                        <div v-else>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Nominal Flat per Pelanggan Aktif (Rp):</label>
                            <input type="number" v-model="schemeForm.flat_per_customer_amount" min="0" step="1000" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 font-mono text-gray-900 dark:text-white">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Bonus Pasang Baru / User (Rp):</label>
                                <input type="number" v-model="schemeForm.registration_bonus" min="0" step="1000" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 font-mono text-gray-900 dark:text-white" placeholder="0">
                            </div>
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Potongan Operasional Flat (Rp):</label>
                                <input type="number" v-model="schemeForm.operational_deduction" min="0" step="50000" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 font-mono text-gray-900 dark:text-white" placeholder="0">
                            </div>
                        </div>

                        <div>
                            <label class="flex items-center gap-2 cursor-pointer font-bold text-gray-700 dark:text-gray-300">
                                <input type="checkbox" v-model="schemeForm.deduct_tax" class="rounded text-indigo-600">
                                <span>Potong Pajak Resmi (PPN & BHP/USO) sebelum perhitungan bagi hasil</span>
                            </label>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Keterangan / Catatan Skema:</label>
                            <textarea v-model="schemeForm.description" rows="2" class="w-full px-3.5 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white" placeholder="Contoh: Kesepakatan berlaku untuk desa Sirnajaya per November 2026"></textarea>
                        </div>

                        <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-2.5">
                            <button type="button" @click="schemeModalOpen = false" class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-600 font-bold">Batal</button>
                            <button type="submit" :disabled="submittingScheme" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold">
                                {{ submittingScheme ? 'Menyimpan...' : 'Simpan Skema' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL HUBUNGKAN KEMITRAAN (PARTNERSHIP) -->
            <div v-if="partnershipModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
                <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-5 border border-gray-200 dark:border-gray-700 shadow-2xl max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            {{ partnershipForm.id ? 'Edit Kemitraan' : 'Hubungkan Kemitraan Baru' }}
                        </h3>
                        <button type="button" @click="partnershipModalOpen = false" class="text-gray-400 hover:text-gray-600 text-lg">✕</button>
                    </div>

                    <form @submit.prevent="submitPartnership" class="space-y-4 text-xs">
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Nama Mitra / Investor / PIC:</label>
                            <input type="text" v-model="partnershipForm.partner_name" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white" placeholder="Contoh: Pak Budi - Investor Sirnajaya">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Pilih Skema Kerjasama:</label>
                            <select v-model="partnershipForm.scheme_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white">
                                <option value="" disabled>-- Pilih Skema --</option>
                                <option v-for="s in schemes" :key="s.id" :value="s.id">{{ s.name }} ({{ s.model_label }})</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Tipe Kemitraan:</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="p-3 rounded-xl border text-xs cursor-pointer flex items-center gap-2" :class="partnershipForm.partner_type === 'area' ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-950 font-bold text-indigo-900' : 'border-gray-200'">
                                    <input type="radio" value="area" v-model="partnershipForm.partner_type">
                                    <span>Wilayah / Area Cabang</span>
                                </label>
                                <label class="p-3 rounded-xl border text-xs cursor-pointer flex items-center gap-2" :class="partnershipForm.partner_type === 'user' ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-950 font-bold text-indigo-900' : 'border-gray-200'">
                                    <input type="radio" value="user" v-model="partnershipForm.partner_type">
                                    <span>Akun User / Mitra Sales</span>
                                </label>
                            </div>
                        </div>

                        <div v-if="partnershipForm.partner_type === 'area'">
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Pilih Wilayah / Area Cabang:</label>
                            <select v-model="partnershipForm.area_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white">
                                <option value="" disabled>-- Pilih Master Area --</option>
                                <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                            </select>
                        </div>

                        <div v-else>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Pilih Akun Mitra / User Sales:</label>
                            <select v-model="partnershipForm.user_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white">
                                <option value="" disabled>-- Pilih Akun User --</option>
                                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.role || 'user' }})</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">No. WhatsApp Mitra:</label>
                                <input type="text" v-model="partnershipForm.partner_phone" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 font-mono text-gray-900 dark:text-white" placeholder="08XXXXXXXXXX">
                            </div>
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Nama Bank:</label>
                                <input type="text" v-model="partnershipForm.bank_name" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white" placeholder="BCA / Mandiri / BRI">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Nomor Rekening:</label>
                                <input type="text" v-model="partnershipForm.bank_account_number" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 font-mono text-gray-900 dark:text-white" placeholder="1234567890">
                            </div>
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Nama Pemilik Rekening:</label>
                                <input type="text" v-model="partnershipForm.bank_account_name" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white" placeholder="Sesuai buku tabungan">
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-2.5">
                            <button type="button" @click="partnershipModalOpen = false" class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-600 font-bold">Batal</button>
                            <button type="submit" :disabled="submittingPartnership" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold">
                                {{ submittingPartnership ? 'Menyimpan...' : 'Simpan Kemitraan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    period: { type: Object, default: () => ({ month: 11, year: 2026, label: '' }) },
    summary: { type: Object, default: () => ({}) },
    live_calculations: { type: Array, default: () => [] },
    schemes: { type: Array, default: () => [] },
    partnerships: { type: Array, default: () => [] },
    settlements: { type: Array, default: () => [] },
    model_labels: { type: Object, default: () => ({}) },
    areas: { type: Array, default: () => [] },
    users: { type: Array, default: () => [] },
    packages: { type: Array, default: () => [] },
});

const activeTab = ref('live');
const selectedMonth = ref(props.period.month);
const selectedYear = ref(props.period.year);

const monthNames = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const yearOptions = [2024, 2025, 2026, 2027, 2028];

const changePeriod = () => {
    router.get('/profit-sharing', {
        month: selectedMonth.value,
        year: selectedYear.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const setThisMonth = () => {
    selectedMonth.value = new Date().getMonth() + 1;
    selectedYear.value = new Date().getFullYear();
    changePeriod();
};

// ── QUICK CALCULATOR ───────────────────────────────────────────
const calcForm = ref({
    model: 'gross_percentage',
    omset: 30000000,
    percentage: 30,
    customer_count: 150,
    flat_amount: 10000,
    deduct_tax: false,
    operational_deduction: 0,
});

const quickResult = ref({
    omset: 30000000,
    partner_share: 9000000,
    isp_share: 21000000,
    effective_percentage: 30,
    formula_detail: '30% dari Omset Kotor Rp 30.000.000',
});

const calcFormattedOmset = computed(() => {
    return Number(calcForm.value.omset || 0).toLocaleString('id-ID');
});

const onCalcOmsetInput = (e) => {
    const raw = e.target.value.replace(/[^0-9]/g, '');
    calcForm.value.omset = raw ? Number(raw) : 0;
    runQuickCalc();
};

let calcTimeout = null;
const runQuickCalc = () => {
    clearTimeout(calcTimeout);
    calcTimeout = setTimeout(async () => {
        try {
            const res = await axios.post('/profit-sharing/simulate', calcForm.value);
            if (res.data?.status === 'success') {
                quickResult.value = res.data.data;
            }
        } catch (e) {
            console.error(e);
        }
    }, 200);
};

const copiedWA = ref(false);
const copyWhatsAppSimulator = () => {
    const text = `*RINCIAN REKONSILIASI BAGI HASIL MITRA*
Periode: ${props.period.label}
----------------------------------
Total Omset Tagihan: ${formatCurrency(quickResult.value.omset)}
Jumlah Pelanggan: ${calcForm.value.customer_count} Pelanggan
Rumus Kesepakatan: ${quickResult.value.formula_detail}
----------------------------------
*Hak Bagi Hasil Mitra:* ${formatCurrency(quickResult.value.partner_share)} (${quickResult.value.effective_percentage}%)
*Hak Bagian ISP:* ${formatCurrency(quickResult.value.isp_share)}
----------------------------------
Terima kasih atas kerjasama yang baik.`;

    navigator.clipboard.writeText(text);
    copiedWA.value = true;
    setTimeout(() => copiedWA.value = false, 3000);
};

// ── SHARE WHATSAPP PER MITRA ──────────────────────────────────
const shareWhatsApp = (calc) => {
    const text = `*LAPORAN BAGI HASIL MITRA - ${calc.partner_name.toUpperCase()}*
Wilayah: ${calc.target_display}
Periode: ${calc.period_label}
----------------------------------
Total Pelanggan Lunas: ${calc.total_customers} Orang (${calc.total_paid_invoices} Tagihan)
Total Omset Masuk: ${formatCurrency(calc.total_gross_revenue)}
Formula Skema: ${calc.formula_detail}
----------------------------------
*HAK BAGI HASIL MITRA:* ${formatCurrency(calc.partner_share_amount)}
*HAK BAGIAN ISP:* ${formatCurrency(calc.isp_share_amount)}
----------------------------------
${calc.bank_info?.bank_account_number ? `Rekening Tujuan: ${calc.bank_info.bank_name} ${calc.bank_info.bank_account_number} a/n ${calc.bank_info.bank_account_name}\n----------------------------------` : ''}
Laporan resmi dicetak otomatis dari sistem VIZBILL ISP Management.`;

    const url = `https://wa.me/${calc.bank_info?.phone ? calc.bank_info.phone.replace(/[^0-9]/g, '') : ''}?text=${encodeURIComponent(text)}`;
    window.open(url, '_blank');
};

// ── PRINT SLIP ────────────────────────────────────────────────
const printSettlement = (settlementId) => {
    window.open(`/profit-sharing/settlement/${settlementId}/print`, '_blank');
};

// ── LOCK SETTLEMENT ───────────────────────────────────────────
const lockSettlement = (calc) => {
    if (!confirm(`Kunci dan simpan rekapitulasi bagi hasil untuk ${calc.partner_name} periode ${calc.period_label}?`)) return;
    router.post('/profit-sharing/settlement', {
        partnership_id: calc.partnership_id,
        month: calc.period_month,
        year: calc.period_year,
    }, {
        preserveScroll: true,
    });
};

const updateSettlementStatus = (settlement, newStatus) => {
    router.post(`/profit-sharing/settlement/${settlement.id}/status`, {
        status: newStatus,
    }, {
        preserveScroll: true,
    });
};

// ── SCHEME MODAL & CRUD ───────────────────────────────────────
const schemeModalOpen = ref(false);
const submittingScheme = ref(false);
const schemeForm = ref({
    id: null,
    name: '',
    code: '',
    model: 'gross_percentage',
    partner_percentage: 30,
    flat_per_customer_amount: 10000,
    registration_bonus: 0,
    operational_deduction: 0,
    deduct_tax: false,
    description: '',
});

const openSchemeModal = () => {
    schemeForm.value = {
        id: null,
        name: '',
        code: 'SCH-' + Math.floor(100 + Math.random() * 900),
        model: 'gross_percentage',
        partner_percentage: 30,
        flat_per_customer_amount: 10000,
        registration_bonus: 0,
        operational_deduction: 0,
        deduct_tax: false,
        description: '',
    };
    schemeModalOpen.value = true;
};

const editScheme = (s) => {
    schemeForm.value = {
        id: s.id,
        name: s.name,
        code: s.code,
        model: s.model,
        partner_percentage: Number(s.partner_percentage || 30),
        flat_per_customer_amount: Number(s.flat_per_customer_amount || 0),
        registration_bonus: Number(s.registration_bonus || 0),
        operational_deduction: Number(s.operational_deduction || 0),
        deduct_tax: !!s.deduct_tax,
        description: s.description || '',
    };
    schemeModalOpen.value = true;
};

const submitScheme = () => {
    submittingScheme.value = true;
    router.post('/profit-sharing/schemes', schemeForm.value, {
        preserveScroll: true,
        onFinish: () => {
            submittingScheme.value = false;
            schemeModalOpen.value = false;
        }
    });
};

const deleteScheme = (s) => {
    if (!confirm(`Hapus skema '${s.name}'?`)) return;
    router.delete(`/profit-sharing/schemes/${s.id}`, { preserveScroll: true });
};

// ── PARTNERSHIP MODAL & CRUD ──────────────────────────────────
const partnershipModalOpen = ref(false);
const submittingPartnership = ref(false);
const partnershipForm = ref({
    id: null,
    scheme_id: '',
    partner_type: 'area',
    area_id: '',
    user_id: '',
    partner_name: '',
    partner_phone: '',
    bank_name: '',
    bank_account_number: '',
    bank_account_name: '',
});

const openPartnershipModal = () => {
    partnershipForm.value = {
        id: null,
        scheme_id: props.schemes[0]?.id || '',
        partner_type: 'area',
        area_id: props.areas[0]?.id || '',
        user_id: props.users[0]?.id || '',
        partner_name: '',
        partner_phone: '',
        bank_name: '',
        bank_account_number: '',
        bank_account_name: '',
    };
    partnershipModalOpen.value = true;
};

const editPartnership = (p) => {
    partnershipForm.value = {
        id: p.id,
        scheme_id: p.scheme_id,
        partner_type: p.partner_type,
        area_id: p.area_id,
        user_id: p.user_id,
        partner_name: p.partner_name,
        partner_phone: p.partner_phone || '',
        bank_name: p.bank_name || '',
        bank_account_number: p.bank_account_number || '',
        bank_account_name: p.bank_account_name || '',
    };
    partnershipModalOpen.value = true;
};

const submitPartnership = () => {
    submittingPartnership.value = true;
    router.post('/profit-sharing/partnerships', partnershipForm.value, {
        preserveScroll: true,
        onFinish: () => {
            submittingPartnership.value = false;
            partnershipModalOpen.value = false;
        }
    });
};

const deletePartnership = (p) => {
    if (!confirm(`Hapus kemitraan '${p.partner_name}'?`)) return;
    router.delete(`/profit-sharing/partnerships/${p.id}`, { preserveScroll: true });
};

const formatCurrency = (val) => {
    return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
};
</script>

