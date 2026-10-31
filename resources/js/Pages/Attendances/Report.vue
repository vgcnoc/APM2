<template>
    <AppLayout title="Laporan Absensi & Kedisiplinan" subtitle="Rekapitulasi log presensi harian, keterlambatan, dan performa disiplin karyawan">
        <div class="space-y-6">

            <!-- ========================================== -->
            <!-- 1. FILTER & ACTION BAR                     -->
            <!-- ========================================== -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs transition-colors">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">Filter & Periode Laporan</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Periode Aktif: <span class="font-bold text-blue-600 dark:text-blue-400">{{ filterPeriodLabel }}</span></p>
                        </div>
                    </div>

                    <!-- Action Export & Cetak -->
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            @click="exportCsv"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl border border-emerald-300 dark:border-emerald-700/60 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition cursor-pointer"
                            title="Export data ke spreadsheet Excel / CSV"
                        >
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Export Excel (CSV)</span>
                        </button>

                        <button
                            type="button"
                            @click="printReport"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl border border-blue-300 dark:border-blue-700/60 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition cursor-pointer"
                            title="Buka format siap cetak PDF / Print A4"
                        >
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <span>Cetak PDF / Print</span>
                        </button>
                    </div>
                </div>

                <!-- Form Filter Controls -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 pt-4">
                    <!-- 1. Mode Periode -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Mode Periode</label>
                        <select
                            v-model="filterForm.period_mode"
                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 py-2 px-3 focus:ring-2 focus:ring-blue-500 font-medium"
                        >
                            <option value="month">Per Bulan Penuh</option>
                            <option value="custom">Rentang Tanggal Kustom</option>
                        </select>
                    </div>

                    <!-- 2. Pilihan Bulan atau Tanggal Mulai -->
                    <div v-if="filterForm.period_mode === 'month'">
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Pilih Bulan</label>
                        <input
                            type="month"
                            v-model="filterForm.month"
                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 py-2 px-3 focus:ring-2 focus:ring-blue-500 font-medium"
                        />
                    </div>
                    <div v-else>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1">Dari Tanggal</label>
                        <input
                            type="date"
                            v-model="filterForm.start_date"
                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 py-2 px-3 focus:ring-2 focus:ring-blue-500 font-medium"
                        />
                    </div>

                    <!-- 3. Tanggal Akhir jika Kustom atau Filter Wilayah -->
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
                            class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 py-2 px-3 focus:ring-2 focus:ring-blue-500 font-medium"
                        >
                            <option value="">Semua Karyawan</option>
                            <option v-for="emp in filteredEmployeesDropdown" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                        </select>
                    </div>

                    <!-- 5. Tombol Terapkan -->
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
                            @click="resetFilters"
                            class="px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 font-bold text-xs rounded-xl transition cursor-pointer"
                            title="Reset Filter ke Bulan Ini"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 2. SUMMARY STATS CARDS                     -->
            <!-- ========================================== -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
                <!-- Card 1: Total Log Presensi -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-0.5">Total Presensi</p>
                        <h3 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ stats.total_logs }} <span class="text-xs font-semibold text-slate-400">Log</span></h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">{{ stats.total_employees }} Karyawan Aktif</p>
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

                <!-- Card 3: Terlambat & Menit -->
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

                <!-- Card 4: Tugas Luar / Lapangan -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-0.5">Dinas / Luar</p>
                        <h3 class="text-xl font-extrabold text-blue-600 dark:text-blue-400">{{ stats.total_field_duty }} <span class="text-xs font-semibold text-slate-400">Tugas</span></h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">{{ stats.total_missing_out }}x Tanpa Clock Out</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Card 5: Estimasi Potongan Sanksi -->
                <div class="col-span-2 sm:col-span-2 lg:col-span-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-0.5">Est. Potongan Sanksi</p>
                        <h3 class="text-xl font-extrabold text-rose-600 dark:text-rose-400">{{ formatRupiah(stats.total_penalty_amount) }}</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Terlambat & Lupa Absen</p>
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
                                <th class="py-3 px-4 w-12 text-center">No</th>
                                <th class="py-3 px-4">Karyawan</th>
                                <th class="py-3 px-4">Wilayah & Jabatan</th>
                                <th class="py-3 px-3 text-center">Hadir</th>
                                <th class="py-3 px-3 text-center text-emerald-600 dark:text-emerald-400">Tepat Waktu</th>
                                <th class="py-3 px-3 text-center text-amber-600 dark:text-amber-400">Terlambat</th>
                                <th class="py-3 px-3 text-center">Dinas Luar</th>
                                <th class="py-3 px-3 text-center">Tanpa Out</th>
                                <th class="py-3 px-4 text-center">Tingkat Disiplin</th>
                                <th class="py-3 px-4 text-right">Est. Potongan</th>
                                <th class="py-3 px-4 text-center">Status Payroll</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            <tr
                                v-for="(emp, i) in employeeRecap"
                                :key="emp.employee_id"
                                class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition"
                            >
                                <td class="py-3 px-4 text-center text-slate-400 font-semibold">{{ i + 1 }}</td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ emp.name }}</div>
                                    <div class="text-[11px] text-slate-400">IAK: {{ emp.iak_number || '-' }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 mr-1.5">
                                        {{ emp.area_name }}
                                    </span>
                                    <div class="text-[11px] text-slate-500 mt-0.5">{{ emp.position || '-' }}</div>
                                </td>
                                <td class="py-3 px-3 text-center font-extrabold text-slate-800 dark:text-slate-200">
                                    {{ emp.total_present }}
                                </td>
                                <td class="py-3 px-3 text-center font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ emp.total_on_time }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span v-if="emp.total_late > 0" class="inline-flex items-center gap-1 font-bold text-amber-600 dark:text-amber-400">
                                        {{ emp.total_late }}x
                                        <span class="text-[10px] font-normal text-slate-400">({{ emp.total_late_minutes }}m)</span>
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="py-3 px-3 text-center font-medium text-slate-600 dark:text-slate-300">
                                    {{ emp.total_field_duty > 0 ? emp.total_field_duty : '-' }}
                                </td>
                                <td class="py-3 px-3 text-center font-medium">
                                    <span v-if="emp.total_missing_out > 0" class="text-rose-600 dark:text-rose-400 font-bold">
                                        {{ emp.total_missing_out }}x
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="inline-flex items-center gap-1.5">
                                        <div class="w-12 bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                            <div
                                                class="h-full rounded-full"
                                                :class="emp.discipline_rate >= 80 ? 'bg-emerald-500' : (emp.discipline_rate >= 50 ? 'bg-amber-500' : 'bg-rose-500')"
                                                :style="{ width: `${Math.min(100, emp.discipline_rate)}%` }"
                                            ></div>
                                        </div>
                                        <span class="font-extrabold text-xs" :class="emp.discipline_rate >= 80 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'">
                                            {{ emp.discipline_rate }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-right font-extrabold" :class="emp.estimated_penalty > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400'">
                                    {{ emp.estimated_penalty > 0 ? formatRupiah(emp.estimated_penalty) : 'Rp 0' }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span
                                        v-if="emp.is_all_synced"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"
                                    >
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Tersinkron
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800"
                                    >
                                        Pending
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="inline-flex items-center gap-2">
                                        <button
                                            type="button"
                                            @click="filterByEmployee(emp.employee_id)"
                                            class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-bold text-xs hover:underline cursor-pointer"
                                        >
                                            Lihat Log
                                        </button>
                                        <button
                                            v-if="canDelete"
                                            type="button"
                                            @click="confirmDeleteEmployeeRecap(emp)"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 font-bold text-xs transition cursor-pointer"
                                            title="Hapus / Reset semua log absensi karyawan ini pada periode yang dipilih"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="employeeRecap.length === 0">
                                <td colspan="12" class="py-10 text-center text-slate-400">
                                    Tidak ada data rekapitulasi absensi pada filter yang dipilih.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 5. TAB 2: LOG RINCI HARIAN (DETAIL LOG)    -->
            <!-- ========================================== -->
            <div v-if="activeTab === 'detail'" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs transition-colors">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-4">Tanggal & Hari</th>
                                <th class="py-3 px-4">Karyawan</th>
                                <th class="py-3 px-4">Shift & Wilayah</th>
                                <th class="py-3 px-4">Masuk (In)</th>
                                <th class="py-3 px-4">Pulang (Out)</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-center">Tipe Presensi</th>
                                <th class="py-3 px-4">Keterangan / Lokasi</th>
                                <th class="py-3 px-4 text-center">Bukti / Foto</th>
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
                                    <div class="text-[11px] text-slate-400">{{ log.position }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-slate-800 dark:text-slate-200">{{ log.shift_name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ log.area_name }} &middot; {{ log.shift_time }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div v-if="log.clock_in" class="font-extrabold text-slate-900 dark:text-white text-xs">
                                        {{ log.clock_in }} WIB
                                        <div v-if="log.distance_in !== null" class="text-[10px] text-slate-400 font-normal">
                                            Radius: {{ log.distance_in }}m
                                        </div>
                                    </div>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="py-3 px-4">
                                    <div v-if="log.clock_out" class="font-extrabold text-slate-900 dark:text-white text-xs">
                                        {{ log.clock_out }} WIB
                                        <div v-if="log.distance_out !== null" class="text-[10px] text-slate-400 font-normal">
                                            Radius: {{ log.distance_out }}m
                                        </div>
                                    </div>
                                    <span v-else-if="log.clock_in" class="text-amber-500 font-bold text-[11px]">Belum Pulang</span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border',
                                            log.status_badge === 'green' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' : '',
                                            log.status_badge === 'amber' ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800' : '',
                                            log.status_badge === 'blue' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800' : '',
                                            log.status_badge === 'red' ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800' : '',
                                            log.status_badge === 'slate' ? 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700' : '',
                                        ]"
                                    >
                                        {{ log.status_label }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        {{ log.attendance_type_label }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="text-[11px] text-slate-600 dark:text-slate-300 max-w-xs truncate" :title="log.notes || log.duty_location">
                                        {{ log.notes || log.duty_location || '-' }}
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <button
                                        v-if="log.photo_in || log.photo_out"
                                        type="button"
                                        @click="openMediaModal(log)"
                                        class="inline-flex items-center gap-1 text-[11px] text-blue-600 dark:text-blue-400 hover:underline font-bold cursor-pointer"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        Foto/GPS
                                    </button>
                                    <span v-else class="text-slate-400 text-[10px]">Tanpa Foto</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <button
                                        v-if="canDelete"
                                        type="button"
                                        @click="confirmDeleteLog(log)"
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 font-bold text-xs transition cursor-pointer"
                                        title="Hapus catatan presensi ini"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        <span>Hapus</span>
                                    </button>
                                    <span v-else class="text-slate-400 text-[10px]">-</span>
                                </td>
                            </tr>
                            <tr v-if="paginatedLogs.length === 0">
                                <td colspan="10" class="py-10 text-center text-slate-400">
                                    Tidak ada catatan log absensi pada filter yang dipilih.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination for Detail Logs -->
                <div v-if="paginatedDetails && paginatedDetails.links && paginatedDetails.links.length > 3" class="px-5 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-wrap items-center justify-between gap-3 text-xs">
                    <span class="text-slate-500">
                        Menampilkan {{ paginatedDetails.from || 0 }} - {{ paginatedDetails.to || 0 }} dari {{ paginatedDetails.total || 0 }} data
                    </span>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, idx) in paginatedDetails.links"
                            :key="idx"
                            :href="link.url || '#'"
                            v-html="link.label"
                            :class="[
                                'px-2.5 py-1 rounded-lg text-xs font-semibold transition',
                                link.active ? 'bg-blue-600 text-white font-bold' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800',
                                !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                            ]"
                        />
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 6. MODAL BUKTI FOTO & LOKASI GPS           -->
            <!-- ========================================== -->
            <div v-if="selectedLogForModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl p-6 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Bukti Presensi & Titik Lokasi GPS</h3>
                            <p class="text-xs text-slate-500">{{ selectedLogForModal.employee_name }} &middot; {{ selectedLogForModal.formatted_date }}</p>
                        </div>
                        <button type="button" @click="selectedLogForModal = null" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-500 flex items-center justify-center cursor-pointer">
                            &times;
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <!-- Foto Masuk -->
                        <div class="border border-slate-200 dark:border-slate-800 rounded-2xl p-3 text-center space-y-2">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Selfie Masuk (In)</span>
                            <div class="w-full h-40 bg-slate-100 dark:bg-slate-800 rounded-xl overflow-hidden flex items-center justify-center">
                                <img v-if="selectedLogForModal.photo_in" :src="selectedLogForModal.photo_in" alt="Selfie In" class="w-full h-full object-cover" />
                                <span v-else class="text-xs text-slate-400">Tidak Ada Foto</span>
                            </div>
                            <div class="text-[11px] text-slate-500 font-medium">Jam: {{ selectedLogForModal.clock_in || '-' }} WIB</div>
                        </div>

                        <!-- Foto Pulang -->
                        <div class="border border-slate-200 dark:border-slate-800 rounded-2xl p-3 text-center space-y-2">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-blue-600 dark:text-blue-400">Selfie Pulang (Out)</span>
                            <div class="w-full h-40 bg-slate-100 dark:bg-slate-800 rounded-xl overflow-hidden flex items-center justify-center">
                                <img v-if="selectedLogForModal.photo_out" :src="selectedLogForModal.photo_out" alt="Selfie Out" class="w-full h-full object-cover" />
                                <span v-else class="text-xs text-slate-400">Tidak Ada Foto</span>
                            </div>
                            <div class="text-[11px] text-slate-500 font-medium">Jam: {{ selectedLogForModal.clock_out || '-' }} WIB</div>
                        </div>
                    </div>

                    <!-- Info GPS & Titik Peta -->
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl text-xs space-y-1">
                        <div class="flex items-center justify-between text-slate-700 dark:text-slate-300">
                            <span>Lokasi Presensi:</span>
                            <span class="font-bold">{{ selectedLogForModal.area_name }}</span>
                        </div>
                        <div v-if="selectedLogForModal.lat_in && selectedLogForModal.lng_in" class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                            <span>Koordinat GPS In:</span>
                            <span class="font-mono">{{ selectedLogForModal.lat_in }}, {{ selectedLogForModal.lng_in }}</span>
                        </div>
                        <div v-if="selectedLogForModal.lat_in && selectedLogForModal.lng_in" class="pt-1">
                            <a
                                :href="`https://www.google.com/maps?q=${selectedLogForModal.lat_in},${selectedLogForModal.lng_in}`"
                                target="_blank"
                                class="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400 font-bold hover:underline"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                Buka di Google Maps
                            </a>
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
    employeeRecap: { type: Array, default: () => [] },
    detailLogs: { type: Array, default: () => [] },
    paginatedDetails: { type: Object, default: null },
    areas: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    filterPeriodLabel: { type: String, default: '' },
    canDelete: { type: Boolean, default: false },
});

const page = usePage();
const canDelete = computed(() => {
    if (props.canDelete) return true;
    const role = (page.props.auth?.user?.role || '').toLowerCase();
    return ['admin', 'hrd', 'finance'].includes(role) || Boolean(page.props.auth?.can?.['attendance_report_btn_delete']);
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

function resetFilters() {
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
                    start_date: props.filters?.start_date,
                    end_date: props.filters?.end_date,
                    period_mode: filterForm.value.period_mode,
                    month: filterForm.value.month,
                },
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Dihapus',
                        text: 'Catatan presensi karyawan pada periode ini telah dihapus.',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                },
                onError: (err) => {
                    Swal.fire('Gagal!', err?.message || 'Terjadi kesalahan saat menghapus data.', 'error');
                }
            });
        }
    });
}

function confirmDeleteLog(log) {
    Swal.fire({
        title: 'Hapus Log Presensi?',
        html: `Hapus catatan log presensi <b>${log.employee_name}</b> tanggal <b>${log.formatted_date}</b>?`,
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
                        title: 'Log Terhapus',
                        text: 'Catatan presensi harian berhasil dihapus.',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                },
                onError: (err) => {
                    Swal.fire('Gagal!', err?.message || 'Terjadi kesalahan saat menghapus log.', 'error');
                }
            });
        }
    });
}
</script>

