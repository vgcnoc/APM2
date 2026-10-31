<template>
    <AppLayout title="Laporan Absensi & Kedisiplinan" subtitle="Rekapitulasi log presensi harian, keterlambatan, dan performa disiplin karyawan">
        <div class="space-y-6">

            <!-- ========================================== -->
            <!-- 1. SMART PERIOD BANNER & QUICK NAVIGATOR   -->
            <!-- ========================================== -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs transition-colors">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold shadow-xs">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Perhitungan Presensi Bulanan</h2>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                                    Auto & Cerdas
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Periode: <span class="font-bold text-blue-600 dark:text-blue-400">{{ filterPeriodLabel }}</span>
                                <span v-if="periodDetails" class="ml-2 font-medium text-slate-400">
                                    ({{ periodDetails.total_work_days }} Hari Kerja Sebulan • {{ periodDetails.elapsed_work_days }} Hari Berjalan)
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Action Bar: Sync Payroll, Export & Cetak -->
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Tombol Cerdas: Sinkronkan ke Payroll Otomatis -->
                        <button
                            v-if="canSyncPayroll"
                            type="button"
                            @click="confirmSyncPayroll"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white shadow-xs transition cursor-pointer"
                            title="Kalkulasi otomatis & sinkronkan seluruh potongan absensi bulan ini ke Penggajian"
                        >
                            <svg class="w-4 h-4 text-amber-300 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
                            </svg>
                            <span>Sinkronkan ke Payroll</span>
                        </button>

                        <button
                            type="button"
                            @click="exportCsv"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl border border-emerald-300 dark:border-emerald-700/60 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition cursor-pointer"
                            title="Export data ke spreadsheet Excel / CSV"
                        >
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Export Excel</span>
                        </button>

                        <button
                            type="button"
                            @click="printReport"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition cursor-pointer"
                            title="Buka format cetak PDF / Print A4"
                        >
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <span>Cetak</span>
                        </button>
                    </div>
                </div>

                <!-- Form Filter Controls & Quick Month Switcher -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 pt-4">
                    <!-- 1. Mode Periode -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Mode Periode</label>
                        <select
                            v-model="filterForm.period_mode"
                            @change="onPeriodModeChange"
                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 py-2 px-3 focus:ring-2 focus:ring-blue-500 font-medium"
                        >
                            <option value="month">Awal Bulan s/d Akhir Bulan (Auto)</option>
                            <option value="custom">Rentang Tanggal Kustom</option>
                        </select>
                    </div>

                    <!-- 2. Pilihan Bulan (Awal s/d Akhir Bulan Otomatis) -->
                    <div v-if="filterForm.period_mode === 'month'">
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Pilih Bulan</label>
                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                @click="stepMonth(-1)"
                                class="px-2 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-xl transition text-xs font-bold"
                                title="Bulan Sebelumnya"
                            >
                                ◀
                            </button>
                            <input
                                type="month"
                                v-model="filterForm.month"
                                @change="applyFilters"
                                class="flex-1 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 py-2 px-3 focus:ring-2 focus:ring-blue-500 font-medium"
                            />
                            <button
                                type="button"
                                @click="stepMonth(1)"
                                class="px-2 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-xl transition text-xs font-bold"
                                title="Bulan Berikutnya"
                            >
                                ▶
                            </button>
                        </div>
                    </div>
                    <div v-else>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Dari Tanggal</label>
                        <input
                            type="date"
                            v-model="filterForm.start_date"
                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 py-2 px-3 focus:ring-2 focus:ring-blue-500 font-medium"
                        />
                    </div>

                    <!-- 3. Tanggal Akhir jika Kustom atau Wilayah -->
                    <div v-if="filterForm.period_mode === 'custom'">
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Sampai Tanggal</label>
                        <input
                            type="date"
                            v-model="filterForm.end_date"
                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 py-2 px-3 focus:ring-2 focus:ring-blue-500 font-medium"
                        />
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Wilayah / Area</label>
                        <select
                            v-model="filterForm.area_id"
                            @change="applyFilters"
                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 py-2 px-3 focus:ring-2 focus:ring-blue-500 font-medium"
                        >
                            <option value="">Semua Wilayah</option>
                            <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>
                    </div>

                    <!-- 4. Filter Karyawan -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Karyawan</label>
                        <select
                            v-model="filterForm.employee_id"
                            @change="applyFilters"
                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 py-2 px-3 focus:ring-2 focus:ring-blue-500 font-medium"
                        >
                            <option value="">Semua Karyawan</option>
                            <option v-for="emp in filteredEmployeesDropdown" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                        </select>
                    </div>

                    <!-- 5. Tombol Aksi Terapkan & Bulan Ini -->
                    <div class="flex items-end gap-2">
                        <button
                            type="button"
                            @click="applyFilters"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs py-2 px-4 rounded-xl shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            <span>Terapkan</span>
                        </button>
                        <button
                            type="button"
                            @click="resetToCurrentMonth"
                            class="px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 font-bold text-xs rounded-xl transition cursor-pointer"
                            title="Kembali ke Bulan Ini (01 s/d Akhir Bulan)"
                        >
                            Bulan Ini
                        </button>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 2. SUMMARY STATS CARDS (CERDAS)           -->
            <!-- ========================================== -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
                <!-- Card 1: Total Presensi & Hari Kerja -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-0.5">Total Presensi</p>
                        <h3 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ stats.total_logs }} <span class="text-xs font-semibold text-slate-400">Log</span></h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">{{ stats.total_employees }} Karyawan Aktif • {{ stats.total_work_days || 26 }} Hari Kerja</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Card 2: Hadir Tepat Waktu -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-0.5">Tepat Waktu</p>
                        <h3 class="text-xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ stats.total_on_time }} <span class="text-xs font-semibold text-slate-400">Hari</span></h3>
                        <p class="text-[11px] text-emerald-500 dark:text-emerald-400 mt-0.5 font-bold">Disiplin {{ stats.avg_discipline_rate }}%</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Card 3: Terlambat & Akumulasi Menit -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-0.5">Keterlambatan</p>
                        <h3 class="text-xl font-extrabold text-amber-600 dark:text-amber-400">{{ stats.total_late }} <span class="text-xs font-semibold text-slate-400">x Telat</span></h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Total {{ stats.total_late_minutes }} Menit</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>

                <!-- Card 4: Alpha & Mangkir (Otomatis Cerdas) -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-0.5">Alpha / Mangkir</p>
                        <h3 class="text-xl font-extrabold text-rose-500 dark:text-rose-400">{{ stats.total_alpha }} <span class="text-xs font-semibold text-slate-400">Hari</span></h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">{{ stats.total_missing_out }}x Tanpa Out • {{ stats.total_field_duty }} Dinas</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-500 dark:text-rose-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Card 5: Estimasi Total Potongan Sanksi -->
                <div class="col-span-2 sm:col-span-2 lg:col-span-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-0.5">Est. Potongan Sanksi</p>
                        <h3 class="text-xl font-extrabold text-rose-600 dark:text-rose-400">{{ formatRupiah(stats.total_penalty_amount) }}</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Telat, Lupa Out & Alpha</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 3. TABS NAVIGATION & SEARCH BAR           -->
            <!-- ========================================== -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-3">
                <!-- Tab Buttons -->
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="switchTab('recap')"
                        :class="[
                            'px-4 py-2 text-xs font-bold rounded-xl transition cursor-pointer flex items-center gap-2',
                            activeTab === 'recap'
                                ? 'bg-blue-600 text-white shadow-xs'
                                : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300'
                        ]"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>Rekap Per Karyawan ({{ employeeRecap.length }})</span>
                    </button>

                    <button
                        type="button"
                        @click="switchTab('detail')"
                        :class="[
                            'px-4 py-2 text-xs font-bold rounded-xl transition cursor-pointer flex items-center gap-2',
                            activeTab === 'detail'
                                ? 'bg-blue-600 text-white shadow-xs'
                                : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300'
                        ]"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <span>Log Rinci Presensi</span>
                    </button>
                </div>

                <!-- Search Input & Type Filter -->
                <div class="flex items-center gap-2">
                    <div class="relative w-full sm:w-64">
                        <input
                            type="text"
                            v-model="filterForm.search"
                            @keyup.enter="applyFilters"
                            placeholder="Cari nama / NIK..."
                            class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 font-medium"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <select
                        v-if="activeTab === 'detail'"
                        v-model="filterForm.status"
                        @change="applyFilters"
                        class="text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 py-1.5 px-2.5 focus:ring-2 focus:ring-blue-500 font-medium"
                    >
                        <option value="all">Semua Status</option>
                        <option value="on_time">Tepat Waktu</option>
                        <option value="late">Terlambat</option>
                        <option value="field_duty">Dinas Luar</option>
                        <option value="missing_out">Tanpa Clock Out</option>
                    </select>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 4. TAB 1: REKAPITULASI PER KARYAWAN        -->
            <!-- ========================================== -->
            <div v-if="activeTab === 'recap'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs transition-colors">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-3 w-10 text-center">No</th>
                                <th class="py-3 px-3">Karyawan</th>
                                <th class="py-3 px-3">Wilayah & Jabatan</th>
                                <th class="py-3 px-2 text-center" title="Hari Kerja Sebulan / Hari Berjalan">Hari Kerja</th>
                                <th class="py-3 px-2 text-center font-extrabold text-slate-800 dark:text-slate-200">Hadir</th>
                                <th class="py-3 px-2 text-center text-emerald-600 dark:text-emerald-400">Tepat</th>
                                <th class="py-3 px-2 text-center text-amber-600 dark:text-amber-400">Telat</th>
                                <th class="py-3 px-2 text-center text-rose-500 dark:text-rose-400" title="Alpha Otomatis (Hari kerja tanpa absen)">Alpha</th>
                                <th class="py-3 px-2 text-center">Dinas</th>
                                <th class="py-3 px-2 text-center text-rose-600 dark:text-rose-400" title="Lupa Clock Out">No Out</th>
                                <th class="py-3 px-3 text-center">Kehadiran</th>
                                <th class="py-3 px-3 text-center">Disiplin</th>
                                <th class="py-3 px-3 text-right">Est. Potongan</th>
                                <th class="py-3 px-3 text-center">Status Payroll</th>
                                <th class="py-3 px-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            <tr
                                v-for="(emp, i) in employeeRecap"
                                :key="emp.employee_id"
                                class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition"
                            >
                                <td class="py-3 px-3 text-center text-slate-400 font-semibold">{{ i + 1 }}</td>
                                <td class="py-3 px-3">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ emp.name }}</div>
                                    <div class="text-[10px] text-slate-400">IAK: {{ emp.iak_number || '-' }}</div>
                                </td>
                                <td class="py-3 px-3">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 mr-1">
                                        {{ emp.area_name }}
                                    </span>
                                    <div class="text-[10px] text-slate-500 mt-0.5">{{ emp.position || '-' }}</div>
                                </td>
                                <td class="py-3 px-2 text-center text-slate-600 dark:text-slate-400 font-medium">
                                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ emp.elapsed_work_days }}</span>
                                    <span class="text-[10px] text-slate-400">/{{ emp.total_work_days }}h</span>
                                </td>
                                <td class="py-3 px-2 text-center font-extrabold text-blue-600 dark:text-blue-400">
                                    {{ emp.total_present }}
                                </td>
                                <td class="py-3 px-2 text-center font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ emp.total_on_time }}
                                </td>
                                <td class="py-3 px-2 text-center">
                                    <span v-if="emp.total_late > 0" class="inline-flex items-center gap-0.5 font-bold text-amber-600 dark:text-amber-400">
                                        {{ emp.total_late }}x
                                        <span class="text-[9px] font-normal text-slate-400">({{ emp.total_late_minutes }}m)</span>
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="py-3 px-2 text-center font-bold">
                                    <span v-if="emp.total_alpha > 0" class="text-rose-500 dark:text-rose-400 font-extrabold">
                                        {{ emp.total_alpha }}h
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="py-3 px-2 text-center font-medium text-slate-600 dark:text-slate-300">
                                    {{ emp.total_field_duty > 0 ? emp.total_field_duty : '-' }}
                                </td>
                                <td class="py-3 px-2 text-center font-medium">
                                    <span v-if="emp.total_missing_out > 0" class="text-rose-600 dark:text-rose-400 font-bold">
                                        {{ emp.total_missing_out }}x
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        <div class="w-10 bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                            <div
                                                class="h-full rounded-full"
                                                :class="emp.attendance_rate >= 85 ? 'bg-blue-500' : (emp.attendance_rate >= 50 ? 'bg-amber-500' : 'bg-rose-500')"
                                                :style="{ width: `${Math.min(100, emp.attendance_rate)}%` }"
                                            ></div>
                                        </div>
                                        <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300">{{ emp.attendance_rate }}%</span>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-extrabold"
                                        :class="emp.discipline_rate >= 80 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : (emp.discipline_rate >= 50 ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300')"
                                    >
                                        {{ emp.discipline_rate }}%
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right font-extrabold text-slate-900 dark:text-white">
                                    <span :class="emp.estimated_penalty > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400'">
                                        {{ formatRupiah(emp.estimated_penalty) }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span
                                        v-if="emp.is_all_synced"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60"
                                    >
                                        <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        <span>Tersinkron</span>
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60"
                                    >
                                        <svg class="w-3 h-3 text-amber-500 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        <span>Belum Sync</span>
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="filterByEmployee(emp.employee_id)"
                                            class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-bold text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/50 dark:hover:bg-blue-900/50 rounded-lg transition cursor-pointer"
                                            title="Lihat riwayat log rinci karyawan ini"
                                        >
                                            <span>Lihat Log</span>
                                        </button>

                                        <!-- Tombol Hapus Khusus HRD & Finance -->
                                        <button
                                            v-if="canDelete"
                                            type="button"
                                            @click="confirmDeleteEmployeeRecap(emp)"
                                            class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-bold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/50 dark:hover:bg-rose-900/50 rounded-lg transition cursor-pointer"
                                            title="Hapus seluruh data presensi karyawan ini dalam periode terpilih"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="employeeRecap.length === 0">
                                <td colspan="15" class="py-12 text-center text-slate-400 italic">
                                    Tidak ada data rekapitulasi karyawan yang sesuai dengan filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 5. TAB 2: LOG RINCI PRESENSI (DETAIL)      -->
            <!-- ========================================== -->
            <div v-if="activeTab === 'detail'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs transition-colors">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-4">Tanggal & Hari</th>
                                <th class="py-3 px-4">Karyawan</th>
                                <th class="py-3 px-4">Shift & Jadwal</th>
                                <th class="py-3 px-4 text-center">Jam Masuk</th>
                                <th class="py-3 px-4 text-center">Jam Pulang</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-center">Tipe Presensi</th>
                                <th class="py-3 px-4 text-center">Foto / GPS</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            <tr
                                v-for="log in paginatedLogs"
                                :key="log.id"
                                class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition"
                            >
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ log.formatted_date }}</div>
                                    <div class="text-[11px] text-slate-400">{{ log.day_name }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ log.employee_name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ log.area_name }} • {{ log.position }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-slate-800 dark:text-slate-200">{{ log.shift_name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ log.shift_time }}</div>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span v-if="log.clock_in" class="font-extrabold text-slate-900 dark:text-white">
                                        {{ log.clock_in }} <span class="text-[10px] font-normal text-slate-400">WIB</span>
                                    </span>
                                    <span v-else class="text-slate-400 italic">Belum Masuk</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span v-if="log.clock_out" class="font-extrabold text-slate-900 dark:text-white">
                                        {{ log.clock_out }} <span class="text-[10px] font-normal text-slate-400">WIB</span>
                                    </span>
                                    <span v-else-if="log.clock_in" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                        Belum Pulang
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold"
                                        :class="{
                                            'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300': log.status_badge === 'green',
                                            'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300': log.status_badge === 'amber',
                                            'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300': log.status_badge === 'blue',
                                            'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300': log.status_badge === 'red',
                                            'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300': log.status_badge === 'slate'
                                        }"
                                    >
                                        {{ log.status_label }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        {{ log.attendance_type_label }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <button
                                        v-if="log.photo_in || log.lat_in"
                                        type="button"
                                        @click="openMediaModal(log)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-lg transition cursor-pointer"
                                    >
                                        <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span>Lihat GPS</span>
                                    </button>
                                    <span v-else class="text-slate-400 text-[11px]">-</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <!-- Tombol Hapus Log Rinci Khusus HRD & Finance -->
                                    <button
                                        v-if="canDelete"
                                        type="button"
                                        @click="confirmDeleteLog(log)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/50 dark:hover:bg-rose-900/50 rounded-lg transition cursor-pointer"
                                        title="Hapus log presensi ini"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <span>Hapus</span>
                                    </button>
                                    <span v-else class="text-slate-400 text-[11px]">-</span>
                                </td>
                            </tr>
                            <tr v-if="paginatedLogs.length === 0">
                                <td colspan="9" class="py-12 text-center text-slate-400 italic">
                                    Belum ada catatan log presensi harian pada periode ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination for Details -->
                <div v-if="paginatedDetails && paginatedDetails.links && paginatedDetails.links.length > 3" class="px-5 py-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <p class="text-xs text-slate-500">
                        Menampilkan <span class="font-bold">{{ paginatedDetails.from || 0 }}</span> s/d <span class="font-bold">{{ paginatedDetails.to || 0 }}</span> dari <span class="font-bold">{{ paginatedDetails.total || 0 }}</span> data
                    </p>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, idx) in paginatedDetails.links"
                            :key="idx"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1 text-xs rounded-lg font-medium transition',
                                link.active ? 'bg-blue-600 text-white font-bold' : (link.url ? 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200' : 'text-slate-300 dark:text-slate-700 cursor-not-allowed')
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 6. MODAL DETAIL FOTO / GPS                 -->
            <!-- ========================================== -->
            <div
                v-if="selectedLogForModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
                @click.self="selectedLogForModal = null"
            >
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-5 space-y-4 shadow-xl">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-sm">Bukti Presensi & Titik Lokasi</h3>
                            <p class="text-xs text-slate-400">{{ selectedLogForModal.employee_name }} • {{ selectedLogForModal.formatted_date }}</p>
                        </div>
                        <button
                            type="button"
                            @click="selectedLogForModal = null"
                            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg font-bold p-1 cursor-pointer"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="space-y-3 text-xs">
                        <!-- Foto Masuk -->
                        <div v-if="selectedLogForModal.photo_in" class="space-y-1">
                            <span class="font-bold text-slate-500 uppercase tracking-wider text-[10px]">Foto Clock In:</span>
                            <div class="rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 max-h-48 flex items-center justify-center bg-slate-950">
                                <img :src="selectedLogForModal.photo_in" alt="Foto Masuk" class="max-h-48 object-contain" />
                            </div>
                        </div>

                        <!-- Data GPS Masuk -->
                        <div v-if="selectedLogForModal.lat_in" class="bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-700 dark:text-slate-300">Koordinat Masuk:</span>
                                <span class="font-mono text-slate-600 dark:text-slate-400">{{ selectedLogForModal.lat_in }}, {{ selectedLogForModal.lng_in }}</span>
                            </div>
                            <div v-if="selectedLogForModal.distance_in !== null" class="flex items-center justify-between">
                                <span class="font-bold text-slate-700 dark:text-slate-300">Jarak ke Kantor / Pos:</span>
                                <span class="font-extrabold text-blue-600 dark:text-blue-400">{{ selectedLogForModal.distance_in }} meter</span>
                            </div>
                            <div class="pt-1">
                                <a
                                    :href="`https://www.google.com/maps?q=${selectedLogForModal.lat_in},${selectedLogForModal.lng_in}`"
                                    target="_blank"
                                    class="text-blue-600 dark:text-blue-400 hover:underline font-bold inline-flex items-center gap-1"
                                >
                                    <span>Buka di Google Maps</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            </div>
                        </div>

                        <!-- Keterangan / Tugas Luar -->
                        <div v-if="selectedLogForModal.notes || selectedLogForModal.duty_location" class="p-3 bg-blue-50 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/50 rounded-xl">
                            <span class="font-bold text-blue-900 dark:text-blue-200 block mb-0.5">Keterangan / Lokasi Tugas:</span>
                            <p class="text-blue-800 dark:text-blue-300">{{ selectedLogForModal.notes || selectedLogForModal.duty_location }}</p>
                        </div>
                    </div>

                    <div class="pt-2 text-right">
                        <button
                            type="button"
                            @click="selectedLogForModal = null"
                            class="px-4 py-2 bg-slate-900 text-white dark:bg-white dark:text-slate-900 font-bold text-xs rounded-xl hover:opacity-90 transition cursor-pointer"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    stats: { type: Object, required: true },
    periodDetails: { type: Object, default: null },
    employeeRecap: { type: Array, default: () => [] },
    detailLogs: { type: Array, default: () => [] },
    paginatedDetails: { type: Object, default: null },
    areas: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    filterPeriodLabel: { type: String, default: '' },
    canDelete: { type: Boolean, default: false },
    canSyncPayroll: { type: Boolean, default: false },
});

const page = usePage();
const canDelete = computed(() => {
    if (props.canDelete) return true;
    const role = (page.props.auth?.user?.role || '').toLowerCase();
    return ['admin', 'hrd', 'finance'].includes(role) || Boolean(page.props.auth?.can?.['attendance_report_btn_delete']);
});

const canSyncPayroll = computed(() => {
    if (props.canSyncPayroll) return true;
    const role = (page.props.auth?.user?.role || '').toLowerCase();
    return ['admin', 'hrd', 'finance'].includes(role) || Boolean(page.props.auth?.can?.['attendances_sync_payroll']);
});

const activeTab = ref(props.filters.tab || 'recap');
const selectedLogForModal = ref(null);

const filterForm = ref({
    period_mode: props.filters.period_mode || 'month',
    month: props.filters.month || new Date().toISOString().slice(0, 7),
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    area_id: props.filters.area_id || '',
    employee_id: props.filters.employee_id || '',
    status: props.filters.status || 'all',
    attendance_type: props.filters.attendance_type || 'all',
    tab: activeTab.value,
    search: props.filters.search || '',
});

const filteredEmployeesDropdown = computed(() => {
    if (!filterForm.value.area_id) {
        return props.employees;
    }
    return props.employees.filter(e => String(e.area_id) === String(filterForm.value.area_id));
});

const paginatedLogs = computed(() => {
    if (props.paginatedDetails && props.paginatedDetails.data) {
        return props.paginatedDetails.data;
    }
    return props.detailLogs;
});

function applyFilters() {
    filterForm.value.tab = activeTab.value;
    router.get(route('attendance-reports.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
}

function onPeriodModeChange() {
    if (filterForm.value.period_mode === 'month') {
        filterForm.value.start_date = '';
        filterForm.value.end_date = '';
    }
    applyFilters();
}

function stepMonth(offset) {
    const current = filterForm.value.month || new Date().toISOString().slice(0, 7);
    const [yStr, mStr] = current.split('-');
    let y = parseInt(yStr, 10);
    let m = parseInt(mStr, 10) + offset;
    if (m < 1) {
        m = 12;
        y--;
    } else if (m > 12) {
        m = 1;
        y++;
    }
    filterForm.value.month = `${y}-${String(m).padStart(2, '0')}`;
    filterForm.value.period_mode = 'month';
    applyFilters();
}

function resetToCurrentMonth() {
    const currentMonth = new Date().toISOString().slice(0, 7);
    filterForm.value = {
        period_mode: 'month',
        month: currentMonth,
        start_date: '',
        end_date: '',
        area_id: '',
        employee_id: '',
        status: 'all',
        attendance_type: 'all',
        tab: activeTab.value,
        search: '',
    };
    applyFilters();
}

function switchTab(tabName) {
    activeTab.value = tabName;
    filterForm.value.tab = tabName;
    applyFilters();
}

function filterByEmployee(employeeId) {
    filterForm.value.employee_id = employeeId;
    activeTab.value = 'detail';
    filterForm.value.tab = 'detail';
    applyFilters();
}

function exportCsv() {
    const params = new URLSearchParams({
        ...filterForm.value,
        tab: activeTab.value,
    });
    window.location.href = route('attendance-reports.export') + '?' + params.toString();
}

function printReport() {
    const params = new URLSearchParams({
        ...filterForm.value,
        tab: activeTab.value,
    });
    window.open(route('attendance-reports.print') + '?' + params.toString(), '_blank');
}

function openMediaModal(log) {
    selectedLogForModal.value = log;
}

function formatRupiah(num) {
    if (!num) return 'Rp 0';
    return 'Rp ' + Number(num).toLocaleString('id-ID');
}

function confirmSyncPayroll() {
    Swal.fire({
        title: 'Sinkronkan Presensi ke Payroll?',
        html: `Sistem secara otomatis akan menghitung akumulasi presensi periode <b>${props.filterPeriodLabel}</b>:<br><br>
        <div class="text-left text-xs bg-slate-50 p-3 rounded-xl space-y-1 font-medium text-slate-700">
            <div>• Keterlambatan (${props.stats.total_late || 0}x telat, ${props.stats.total_late_minutes || 0} menit)</div>
            <div>• Lupa Absen Pulang (${props.stats.total_missing_out || 0}x tanpa checkout)</div>
            <div>• Alpha / Mangkir (${props.stats.total_alpha || 0} hari kerja)</div>
            <div class="font-bold text-rose-600 pt-1">Total Est. Potongan: ${formatRupiah(props.stats.total_penalty_amount)}</div>
        </div><br>
        Seluruh potongan di atas akan otomatis dicatat pada slip penggajian karyawan periode tersebut. Lanjutkan?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Sinkronkan Sekarang',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('attendance-reports.sync-payroll'), {
                month: filterForm.value.month,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sinkronisasi Berhasil!',
                        text: 'Seluruh data potongan absensi periode ini telah tersinkron ke modul Penggajian (Payroll).',
                        timer: 2500,
                        showConfirmButton: false,
                    });
                },
                onError: (err) => {
                    Swal.fire('Gagal!', err?.message || 'Gagal menyinkronkan data presensi ke penggajian.', 'error');
                }
            });
        }
    });
}

function confirmDeleteEmployeeRecap(emp) {
    Swal.fire({
        title: 'Hapus Rekap Absensi?',
        html: `Apakah Anda yakin ingin menghapus data presensi karyawan <b>${emp.name}</b> pada periode <b>${props.filterPeriodLabel}</b>?<br><br><span class="text-rose-600 text-xs font-semibold">Tindakan ini permanen dan akan menghapus semua log presensi karyawan tersebut pada periode yang dipilih.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus Data',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/attendance-reports/employee/${emp.employee_id}`, {
                data: {
                    month: filterForm.value.month,
                    period_mode: filterForm.value.period_mode,
                    start_date: filterForm.value.start_date,
                    end_date: filterForm.value.end_date,
                },
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Dihapus',
                        text: `Data presensi ${emp.name} periode ${props.filterPeriodLabel} berhasil dihapus.`,
                        timer: 2000,
                        showConfirmButton: false,
                    });
                },
                onError: (err) => {
                    Swal.fire('Gagal!', err?.message || 'Gagal menghapus data presensi.', 'error');
                }
            });
        }
    });
}

function confirmDeleteLog(log) {
    Swal.fire({
        title: 'Hapus Log Presensi?',
        html: `Apakah Anda yakin ingin menghapus log presensi <b>${log.employee_name}</b> tanggal <b>${log.formatted_date}</b>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/attendance-reports/log/${log.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Dihapus',
                        text: 'Log presensi berhasil dihapus.',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                },
                onError: (err) => {
                    Swal.fire('Gagal!', err?.message || 'Gagal menghapus log presensi.', 'error');
                }
            });
        }
    });
}
</script>
