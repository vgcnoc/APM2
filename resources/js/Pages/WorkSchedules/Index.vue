<template>
    <AppLayout title="Jadwal Kerja">
        <div class="space-y-5 print:space-y-2 print:m-0 print:p-0">
            <!-- 1. HEADER SECTION -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 print:hidden">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white shadow-lg shadow-blue-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-800">Jadwal Kerja</h1>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">Kelola roster & pola shift karyawan</p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        @click="openPatternModal = true"
                        type="button"
                        class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-all shadow-sm active:scale-95"
                    >
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Buat Pola</span>
                    </button>

                    <button
                        @click="openGenerateModal = true"
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-all shadow-sm shadow-blue-500/20 active:scale-95"
                    >
                        <svg class="w-4 h-4 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                        <span>Generate Jadwal</span>
                    </button>
                </div>
            </div>

            <!-- 2. SUMMARY STAT CARDS (4 KARTU SEPERTI SCREENSHOT) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 print:hidden">
                <!-- Card 1: Karyawan -->
                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Karyawan</div>
                        <div class="text-2xl font-bold text-slate-800 leading-none mt-1">{{ stats.totalEmployees }}</div>
                    </div>
                </div>

                <!-- Card 2: Total Jadwal -->
                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total Jadwal</div>
                        <div class="text-2xl font-bold text-slate-800 leading-none mt-1">{{ stats.totalSchedules }}</div>
                    </div>
                </div>

                <!-- Card 3: Hari Libur -->
                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Hari Libur</div>
                        <div class="text-2xl font-bold text-slate-800 leading-none mt-1">{{ stats.totalOff }}</div>
                    </div>
                </div>

                <!-- Card 4: Shift Terbanyak -->
                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex flex-col justify-center">
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Shift Terbanyak</div>
                    <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-0.5">
                        <template v-if="Object.keys(stats.shiftCounts || {}).length > 0">
                            <span
                                v-for="(count, shiftName) in topShifts"
                                :key="shiftName"
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-slate-100/90 text-slate-700 whitespace-nowrap border border-slate-200/60"
                            >
                                <span>{{ shiftName }}:</span>
                                <span class="font-bold text-blue-600">{{ count }}</span>
                            </span>
                        </template>
                        <span v-else class="text-xs text-slate-400 italic">Belum ada data shift</span>
                    </div>
                </div>
            </div>

            <!-- 3. CONTROL TOOLBAR (NAV BULAN, FILTER, SEARCH, SIMPAN, DLL) -->
            <div class="bg-white rounded-2xl p-3 border border-slate-100 shadow-sm space-y-3 print:hidden">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <!-- Bulan Selector & Navigasi -->
                    <div class="flex items-center gap-2">
                        <button
                            @click="changeMonth(-1)"
                            type="button"
                            title="Bulan Sebelumnya"
                            class="w-8 h-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center text-slate-600 transition active:scale-95 shadow-2xs"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>

                        <div class="relative">
                            <input
                                type="month"
                                :value="currentMonthStr"
                                @change="onMonthInputChange($event)"
                                class="text-sm font-bold text-slate-800 bg-slate-50 border border-slate-200/80 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer"
                            />
                        </div>

                        <button
                            @click="changeMonth(1)"
                            type="button"
                            title="Bulan Berikutnya"
                            class="w-8 h-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center text-slate-600 transition active:scale-95 shadow-2xs"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>

                        <button
                            @click="goToToday"
                            type="button"
                            class="px-2.5 py-1.5 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition active:scale-95 ml-1"
                        >
                            Hari Ini
                        </button>
                    </div>

                    <!-- Filter Area/Wilayah, Jabatan, & Search -->
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Filter Area/Wilayah -->
                        <div class="relative min-w-[155px]">
                            <select
                                v-model="filterBranch"
                                @change="applyFilters"
                                class="w-full text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 pr-7 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer"
                            >
                                <option value="">🏢 Semua Area/Wilayah</option>
                                <option v-for="br in (areas || branches)" :key="br.id" :value="br.id">{{ br.name }}</option>
                            </select>
                        </div>

                        <!-- Filter Jabatan -->
                        <div class="relative min-w-[140px]">
                            <select
                                v-model="filterPosition"
                                @change="applyFilters"
                                class="w-full text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 pr-7 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer"
                            >
                                <option value="">💼 Semua Jabatan</option>
                                <option v-for="pos in positions" :key="pos.id" :value="pos.name">{{ pos.name }}</option>
                            </select>
                        </div>

                        <!-- Pencarian Karyawan -->
                        <div class="relative min-w-[160px]">
                            <input
                                v-model="searchQuery"
                                @input="applyFiltersDebounced"
                                type="text"
                                placeholder="🔍 Cari karyawan..."
                                class="w-full text-xs text-slate-700 bg-white border border-slate-200 rounded-xl px-3 py-1.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                            />
                        </div>
                    </div>

                    <!-- Tombol Aksi Kanan (Simpan, Print, Copy, Reset, dll) -->
                    <div class="flex flex-wrap items-center gap-2 ml-auto">
                        <!-- Tombol Simpan -->
                        <button
                            @click="saveBatchChanges"
                            :disabled="unsavedChangesCount === 0 || isSaving"
                            type="button"
                            :class="[
                                unsavedChangesCount > 0
                                    ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-500/20 animate-pulse'
                                    : 'bg-slate-800 text-white opacity-90 hover:opacity-100',
                                'inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-xl transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed'
                            ]"
                        >
                            <svg v-if="!isSaving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                            </svg>
                            <svg v-else class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            <span>Simpan</span>
                            <span v-if="unsavedChangesCount > 0" class="ml-1 px-1.5 py-0.2 text-[10px] bg-white text-blue-700 rounded-full font-bold">
                                {{ unsavedChangesCount }}
                            </span>
                        </button>

                        <!-- Print -->
                        <button
                            @click="printSchedule"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition active:scale-95 shadow-2xs"
                        >
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            <span>Print</span>
                        </button>

                        <!-- Copy Bulan -->
                        <button
                            @click="openCopyModal = true"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition active:scale-95 shadow-2xs"
                        >
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                            </svg>
                            <span>Copy Bulan</span>
                        </button>

                        <!-- Lanjutkan dari Bulan Lalu -->
                        <button
                            @click="continueFromPrevMonth"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50/70 border border-indigo-200/60 rounded-xl hover:bg-indigo-100/70 transition active:scale-95"
                        >
                            <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span>Lanjutkan dari Bulan Lalu</span>
                        </button>

                        <!-- Reset Bulan -->
                        <button
                            @click="confirmResetMonth"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-rose-600 bg-rose-50/50 border border-rose-200 rounded-xl hover:bg-rose-100/60 transition active:scale-95"
                        >
                            <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span>Reset Bulan</span>
                        </button>
                    </div>
                </div>

                <!-- 4. SHIFT LEGEND BAR -->
                <div class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2 text-xs">
                    <span class="text-slate-400 font-semibold text-[11px] uppercase tracking-wider">Legend:</span>
                    
                    <!-- Shift Badges di Legend -->
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span
                            v-for="shift in shifts"
                            :key="shift.id"
                            :class="[getShiftBadgeStyle(shift.color), 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold border cursor-default']"
                        >
                            <svg class="w-3 h-3 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>{{ shift.name }} ({{ shift.start_time }}-{{ shift.end_time }})</span>
                        </span>

                        <!-- Badge OFF -->
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-600 border border-rose-200">
                            OFF
                        </span>
                    </div>

                    <!-- Tombol Tambah Shift Cepat -->
                    <button
                        @click="openAddShiftModal = true"
                        type="button"
                        class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600 bg-white border border-dashed border-slate-300 rounded-full hover:bg-slate-50 hover:text-blue-600 transition ml-1"
                    >
                        <span>+ Tambah Shift</span>
                    </button>
                </div>
            </div>

            <!-- 5. TABEL MATRIKS ROSTER KALENDER (SANGAT DETAIL SESUAI SCREENSHOT) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden relative">
                <!-- Banner Unsaved Changes Notice -->
                <div v-if="unsavedChangesCount > 0" class="bg-amber-50 border-b border-amber-200 px-4 py-2 flex items-center justify-between text-xs text-amber-800 print:hidden">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                        <span class="font-medium">Ada <b>{{ unsavedChangesCount }}</b> perubahan jadwal yang belum disimpan.</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="discardChanges" class="underline text-amber-700 hover:text-amber-900">Batalkan</button>
                        <button @click="saveBatchChanges" class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-bold shadow-xs">Simpan Sekarang</button>
                    </div>
                </div>

                <div class="overflow-x-auto max-w-full">
                    <table class="w-full border-collapse text-center select-none">
                        <!-- HEADER TABEL (KARYAWAN + HARI/TANGGAL) -->
                        <thead>
                            <tr class="bg-slate-50/90 text-slate-600 text-[11px] font-semibold border-b border-slate-200">
                                <!-- Kolom Sticky Karyawan -->
                                <th class="sticky left-0 z-20 bg-slate-100/95 backdrop-blur-xs text-left px-4 py-3 min-w-[220px] max-w-[240px] uppercase tracking-wider text-slate-500 font-bold border-r border-slate-200 shadow-[2px_0_5px_rgba(0,0,0,0.03)]">
                                    Karyawan
                                </th>

                                <!-- Kolom Hari & Tanggal -->
                                <th
                                    v-for="day in days"
                                    :key="day.date"
                                    :class="[
                                        'px-1 py-2 min-w-[58px] border-r border-slate-200/70 last:border-r-0',
                                        day.is_sunday ? 'bg-rose-50/40 text-rose-600 font-bold' : '',
                                        day.is_today ? 'bg-blue-50/60 ring-1 ring-blue-400/30' : ''
                                    ]"
                                >
                                    <div :class="day.is_sunday ? 'text-rose-500 font-bold' : 'text-slate-500'">
                                        {{ day.day_name }}
                                    </div>
                                    <div :class="['text-xs font-bold mt-0.5', day.is_sunday ? 'text-rose-600' : 'text-slate-800']">
                                        {{ day.day }}
                                    </div>
                                </th>
                            </tr>
                        </thead>

                        <!-- BODY TABEL: LIST KARYAWAN -->
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <tr
                                v-for="emp in employees"
                                :key="emp.id"
                                class="hover:bg-blue-50/20 transition-colors group"
                            >
                                <!-- Sticky Karyawan Cell -->
                                <td class="sticky left-0 z-10 bg-white group-hover:bg-slate-50/90 text-left px-4 py-2.5 border-r border-slate-200 shadow-[2px_0_5px_rgba(0,0,0,0.03)] transition-colors">
                                    <div class="font-bold text-slate-800 leading-tight truncate" :title="emp.name">
                                        {{ emp.name }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 font-medium mt-0.5 truncate uppercase">
                                        <span>{{ emp.position || 'Staf' }}</span>
                                        <span v-if="emp.area?.name || emp.branch" class="text-slate-300 mx-1">•</span>
                                        <span v-if="emp.area?.name || emp.branch" class="text-slate-500 font-semibold">{{ emp.area?.name || emp.branch }}</span>
                                    </div>
                                </td>

                                <!-- Tanggal Cells (1 .. N) -->
                                <td
                                    v-for="day in days"
                                    :key="day.date"
                                    @click="openCellPicker(emp, day.date, $event)"
                                    :class="[
                                        'px-0.5 py-1.5 border-r border-slate-100/80 last:border-r-0 cursor-pointer transition hover:bg-blue-100/40 relative',
                                        day.is_sunday ? 'bg-rose-50/20' : '',
                                        hasChange(emp.id, day.date) ? 'ring-1 ring-inset ring-blue-500 bg-blue-50/50' : ''
                                    ]"
                                    :title="getCellTooltip(emp, day.date)"
                                >
                                    <!-- Cell Pill Content -->
                                    <div class="flex items-center justify-center min-h-[30px]">
                                        <!-- Jika ada jadwal terisi -->
                                        <span
                                            v-if="getSchedule(emp.id, day.date)"
                                            :class="[
                                                getCellBadgeClass(getSchedule(emp.id, day.date)),
                                                'inline-flex items-center justify-center px-1.5 py-1 rounded-md text-[10px] font-bold tracking-tight text-center leading-none max-w-[54px] truncate shadow-2xs'
                                            ]"
                                        >
                                            {{ getScheduleLabel(getSchedule(emp.id, day.date)) }}
                                        </span>
                                        
                                        <!-- Jika cell kosong -->
                                        <span v-else class="text-slate-200 text-xs font-light hover:text-slate-400">
                                            -
                                        </span>
                                    </div>

                                    <!-- Dot indikator jika diedit lokal -->
                                    <div
                                        v-if="hasChange(emp.id, day.date)"
                                        class="absolute top-1 right-1 w-1.5 h-1.5 rounded-full bg-blue-600"
                                    ></div>
                                </td>
                            </tr>

                            <!-- Empty State jika tidak ada karyawan -->
                            <tr v-if="employees.length === 0">
                                <td :colspan="days.length + 1" class="py-12 text-center text-slate-400 italic">
                                    Tidak ada data karyawan ditemukan dengan filter saat ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 6. FLOATING QUICK CELL PICKER (POPOVER) -->
            <div
                v-if="activePopover"
                :style="{ top: activePopover.top + 'px', left: activePopover.left + 'px' }"
                class="fixed z-50 bg-white rounded-2xl shadow-xl border border-slate-200 p-2.5 w-64 text-left animate-in fade-in zoom-in-95 duration-100"
            >
                <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100">
                    <div>
                        <div class="text-[11px] font-bold text-slate-800 leading-tight truncate max-w-[170px]">
                            {{ activePopover.employee.name }}
                        </div>
                        <div class="text-[10px] text-slate-400">
                            {{ formatLocalDate(activePopover.date) }}
                        </div>
                    </div>
                    <button
                        @click="closeCellPicker"
                        class="text-slate-400 hover:text-slate-600 p-1 rounded-md"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-1 max-h-56 overflow-y-auto pr-1">
                    <!-- Option OFF (Libur) -->
                    <button
                        @click="selectShiftForActiveCell('OFF')"
                        type="button"
                        class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 hover:bg-rose-100 transition"
                    >
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                            <span>OFF (Libur)</span>
                        </div>
                        <span class="text-[10px] text-rose-400">Hari Libur</span>
                    </button>

                    <!-- Shift Options -->
                    <button
                        v-for="shift in shifts"
                        :key="shift.id"
                        @click="selectShiftForActiveCell(shift.id)"
                        type="button"
                        class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-100 transition"
                    >
                        <div class="flex items-center gap-2 truncate">
                            <span :class="[getDotColor(shift.color), 'w-2.5 h-2.5 rounded-full shrink-0']"></span>
                            <span class="font-semibold text-slate-800 truncate">{{ shift.name }}</span>
                        </div>
                        <span class="text-[10px] text-slate-400 shrink-0 ml-1">{{ shift.start_time }}-{{ shift.end_time }}</span>
                    </button>

                    <!-- Reset / Kosongkan Jadwal -->
                    <button
                        @click="selectShiftForActiveCell(null)"
                        type="button"
                        class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition border-t border-slate-100 mt-1"
                    >
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span>Kosongkan Jadwal</span>
                    </button>
                </div>
            </div>

            <!-- BACKDROP UNTUK CELL PICKER -->
            <div
                v-if="activePopover"
                @click="closeCellPicker"
                class="fixed inset-0 z-40 bg-black/5"
            ></div>

            <!-- 7. MODAL GENERATE JADWAL OTOMATIS -->
            <div v-if="openGenerateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="openGenerateModal = false"></div>
                <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-800">Generate Jadwal Otomatis</h3>
                                <p class="text-xs text-slate-500">Terapkan pola perputaran roster untuk bulan {{ monthName }}</p>
                            </div>
                        </div>
                        <button @click="openGenerateModal = false" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitGenerateSchedule" class="space-y-4">
                        <!-- Pilih Karyawan -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold text-slate-700">Pilih Karyawan</label>
                                <button
                                    type="button"
                                    @click="toggleSelectAllEmployees"
                                    class="text-[11px] font-bold text-blue-600 hover:underline"
                                >
                                    {{ selectedGenEmployees.length === filteredGenEmployees.length ? 'Batal Semua' : 'Pilih Semua' }}
                                </button>
                            </div>

                            <!-- Filter Area/Wilayah & Jabatan di Modal Generate -->
                            <div class="grid grid-cols-2 gap-2 mb-2">
                                <div>
                                    <select
                                        v-model="modalFilterBranch"
                                        class="w-full text-[11px] font-medium text-slate-700 bg-slate-50 border border-slate-200 rounded-lg px-2 py-1.5 cursor-pointer"
                                    >
                                        <option value="">🏢 Semua Area/Wilayah</option>
                                        <option v-for="br in (areas || branches)" :key="br.id" :value="br.id">{{ br.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <select
                                        v-model="modalFilterPosition"
                                        class="w-full text-[11px] font-medium text-slate-700 bg-slate-50 border border-slate-200 rounded-lg px-2 py-1.5 cursor-pointer"
                                    >
                                        <option value="">💼 Semua Jabatan</option>
                                        <option v-for="pos in positions" :key="pos.id" :value="pos.name">{{ pos.name }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="max-h-40 overflow-y-auto border border-slate-200 rounded-xl p-2 space-y-1 text-xs">
                                <label
                                    v-for="emp in filteredGenEmployees"
                                    :key="emp.id"
                                    class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-slate-50 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        :value="emp.id"
                                        v-model="selectedGenEmployees"
                                        class="rounded text-blue-600 focus:ring-blue-500"
                                    />
                                    <div class="truncate">
                                        <span class="font-semibold text-slate-800">{{ emp.name }}</span>
                                        <span class="text-[11px] text-slate-400 ml-1.5">
                                            ({{ emp.position || 'Staf' }}<span v-if="emp.area?.name || emp.branch"> • {{ emp.area?.name || emp.branch }}</span>)
                                        </span>
                                    </div>
                                </label>
                                <div v-if="filteredGenEmployees.length === 0" class="text-center py-3 text-slate-400 text-xs italic">
                                    Tidak ada karyawan sesuai filter area/wilayah atau jabatan.
                                </div>
                            </div>
                        </div>

                        <!-- Pilih Pola -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Pola Roster</label>
                            <select
                                v-model="selectedPatternId"
                                @change="onGenPatternSelect"
                                class="w-full text-xs text-slate-800 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                            >
                                <option value="6-1">Pola Standar (6 Kerja : 1 Libur Hari Minggu)</option>
                                <option value="5-2">Pola 5 Kerja : 2 Libur (Sabtu & Minggu)</option>
                                <option v-for="p in patterns" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>

                        <!-- Pilih Shift Default jika pola standar -->
                        <div v-if="selectedPatternId === '6-1' || selectedPatternId === '5-2'">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Shift Kerja</label>
                            <select
                                v-model="genDefaultShiftId"
                                class="w-full text-xs text-slate-800 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                            >
                                <option v-for="s in shifts" :key="s.id" :value="s.id">{{ s.name }} ({{ s.start_time }}-{{ s.end_time }})</option>
                            </select>
                        </div>

                        <!-- Opsi Stagger Rotasi -->
                        <div class="flex items-center gap-2 pt-1">
                            <input
                                id="stagger"
                                type="checkbox"
                                v-model="genStagger"
                                class="rounded text-blue-600 focus:ring-blue-500"
                            />
                            <label for="stagger" class="text-xs text-slate-600 select-none cursor-pointer">
                                <b>Rotasi Selang-Seling:</b> Geser hari libur antar teknisi agar tidak libur bersamaan.
                            </label>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="openGenerateModal = false"
                                class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="selectedGenEmployees.length === 0 || isGenerating"
                                class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition shadow-md shadow-blue-500/20 disabled:opacity-50"
                            >
                                {{ isGenerating ? 'Memproses...' : 'Terapkan Pola' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 8. MODAL BUAT POLA ROSTER -->
            <div v-if="openPatternModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="openPatternModal = false"></div>
                <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-800">Master Pola Shift</h3>
                                <p class="text-xs text-slate-500">Buat template urutan shift berulang</p>
                            </div>
                        </div>
                        <button @click="openPatternModal = false" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Form Buat Pola Baru -->
                    <form @submit.prevent="submitCreatePattern" class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Pola</label>
                            <input
                                v-model="patternForm.name"
                                type="text"
                                placeholder="Contoh: Pola Rotasi Pagi-Siang-Malam-OFF"
                                required
                                class="w-full text-xs text-slate-800 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                            />
                        </div>

                        <!-- Urutan Hari Pola -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold text-slate-700">Urutan Hari Perputaran</label>
                                <button
                                    type="button"
                                    @click="addDayToPattern"
                                    class="text-[11px] font-bold text-blue-600 hover:underline"
                                >
                                    + Tambah Hari
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-2 max-h-44 overflow-y-auto p-1">
                                <div
                                    v-for="(val, idx) in patternForm.days"
                                    :key="idx"
                                    class="flex items-center gap-1.5 bg-slate-50 p-1.5 rounded-lg border border-slate-200 text-xs"
                                >
                                    <span class="font-bold text-slate-400 w-5">H{{ idx + 1 }}</span>
                                    <select
                                        v-model="patternForm.days[idx]"
                                        class="w-full text-xs bg-white border border-slate-200 rounded-md py-1 px-1.5"
                                    >
                                        <option value="OFF">🔴 OFF (Libur)</option>
                                        <option v-for="s in shifts" :key="s.id" :value="s.id">
                                            🟢 {{ s.name }}
                                        </option>
                                    </select>
                                    <button
                                        v-if="patternForm.days.length > 2"
                                        type="button"
                                        @click="removeDayFromPattern(idx)"
                                        class="text-rose-500 hover:text-rose-700 p-0.5"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="openPatternModal = false"
                                class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition"
                            >
                                Tutup
                            </button>
                            <button
                                type="submit"
                                :disabled="isSavingPattern"
                                class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition shadow-md shadow-indigo-500/20"
                            >
                                {{ isSavingPattern ? 'Menyimpan...' : 'Simpan Pola' }}
                            </button>
                        </div>
                    </form>

                    <!-- Daftar Pola yang Tersimpan -->
                    <div v-if="patterns.length > 0" class="pt-3 border-t border-slate-100 space-y-2">
                        <div class="text-[11px] font-bold uppercase text-slate-400 tracking-wider">Pola Tersimpan:</div>
                        <div class="space-y-1.5 max-h-32 overflow-y-auto">
                            <div
                                v-for="p in patterns"
                                :key="p.id"
                                class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-200 text-xs"
                            >
                                <div>
                                    <div class="font-bold text-slate-800">{{ p.name }}</div>
                                    <div class="text-[10px] text-slate-500">{{ (p.pattern_data || []).length }} Hari siklus</div>
                                </div>
                                <button
                                    @click="deletePattern(p.id)"
                                    class="text-rose-500 hover:text-rose-700 p-1"
                                    title="Hapus Pola"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 9. MODAL COPY BULAN -->
            <div v-if="openCopyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="openCopyModal = false"></div>
                <div class="relative bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-lg font-bold text-slate-800">Salin Jadwal Bulan</h3>
                        <button @click="openCopyModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <form @submit.prevent="submitCopyMonth" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Salin dari Bulan:</label>
                            <input
                                v-model="copySourceMonth"
                                type="month"
                                required
                                class="w-full text-xs text-slate-800 border border-slate-200 rounded-xl px-3 py-2"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Ke Bulan Tujuan:</label>
                            <input
                                :value="currentMonthStr"
                                disabled
                                class="w-full text-xs text-slate-500 bg-slate-100 border border-slate-200 rounded-xl px-3 py-2 font-bold cursor-not-allowed"
                            />
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="openCopyModal = false"
                                class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="isCopying"
                                class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition shadow-md shadow-blue-500/20"
                            >
                                {{ isCopying ? 'Menyalin...' : 'Salin Jadwal' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 10. MODAL QUICK ADD SHIFT -->
            <div v-if="openAddShiftModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="openAddShiftModal = false"></div>
                <div class="relative bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-lg font-bold text-slate-800">Tambah Shift Baru</h3>
                        <button @click="openAddShiftModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <form @submit.prevent="submitAddShift" class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Shift</label>
                            <input
                                v-model="newShiftForm.name"
                                type="text"
                                placeholder="Contoh: Shift 3 (Malam)"
                                required
                                class="w-full text-xs text-slate-800 border border-slate-200 rounded-xl px-3 py-2"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Jam Masuk</label>
                                <input
                                    v-model="newShiftForm.start_time"
                                    type="time"
                                    required
                                    class="w-full text-xs text-slate-800 border border-slate-200 rounded-xl px-3 py-2"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Jam Pulang</label>
                                <input
                                    v-model="newShiftForm.end_time"
                                    type="time"
                                    required
                                    class="w-full text-xs text-slate-800 border border-slate-200 rounded-xl px-3 py-2"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Warna Badge</label>
                            <div class="flex items-center gap-2">
                                <label
                                    v-for="color in ['blue', 'emerald', 'purple', 'amber', 'rose', 'cyan']"
                                    :key="color"
                                    :class="[
                                        getDotColor(color),
                                        'w-7 h-7 rounded-full cursor-pointer flex items-center justify-center transition',
                                        newShiftForm.color === color ? 'ring-2 ring-offset-2 ring-slate-800' : ''
                                    ]"
                                >
                                    <input type="radio" v-model="newShiftForm.color" :value="color" class="sr-only"/>
                                    <svg v-if="newShiftForm.color === color" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="openAddShiftModal = false"
                                class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="isSavingShift"
                                class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition shadow-md shadow-blue-500/20"
                            >
                                {{ isSavingShift ? 'Menyimpan...' : 'Simpan Shift' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    month: String,
    monthName: String,
    days: Array,
    employees: Array,
    shifts: Array,
    patterns: Array,
    schedulesMap: Object,
    branches: Array,
    areas: Array,
    positions: Array,
    filters: Object,
    stats: Object,
});

// State Filter Toolbar
const currentMonthStr = ref(props.month);
const filterBranch = ref(props.filters.branch || '');
const filterPosition = ref(props.filters.position || '');
const searchQuery = ref(props.filters.search || '');

// Local Schedules state (untuk batch edit real-time)
// localMap: { [empId]: { [dateStr]: { shift_id, status } } }
const localMap = ref(JSON.parse(JSON.stringify(props.schedulesMap || {})));
// List perubahan yang belum disimpan: { employee_id, date, shift_id, status }
const pendingChanges = ref({});
const isSaving = ref(false);

// Unsaved changes count
const unsavedChangesCount = computed(() => Object.keys(pendingChanges.value).length);

// Top shifts list computed for Card 4
const topShifts = computed(() => {
    const counts = props.stats?.shiftCounts || {};
    // ambil maksimal 3 teratas
    const entries = Object.entries(counts).slice(0, 3);
    const res = {};
    for (const [k, v] of entries) {
        res[k] = v;
    }
    return res;
});

// Cell Popover State
const activePopover = ref(null);

const openCellPicker = (employee, date, event) => {
    const rect = event.currentTarget.getBoundingClientRect();
    const popoverWidth = 260;
    const popoverHeight = 280;

    let left = rect.left + window.scrollX;
    let top = rect.bottom + window.scrollY + 6;

    // Boundary check window width
    if (left + popoverWidth > window.innerWidth) {
        left = window.innerWidth - popoverWidth - 20;
    }

    // Boundary check window height
    if (top + popoverHeight > window.innerHeight + window.scrollY) {
        top = rect.top + window.scrollY - popoverHeight - 6;
    }

    activePopover.value = {
        employee,
        date,
        top: Math.max(10, top),
        left: Math.max(10, left),
    };
};

const closeCellPicker = () => {
    activePopover.value = null;
};

// Select Shift untuk active cell
const selectShiftForActiveCell = (shiftVal) => {
    if (!activePopover.value) return;

    const empId = activePopover.value.employee.id;
    const date = activePopover.value.date;

    const changeKey = `${empId}_${date}`;

    let shiftId = null;
    let status = 'work';
    let shiftName = '';
    let shiftColor = '';

    if (shiftVal === 'OFF') {
        shiftId = null;
        status = 'off';
    } else if (shiftVal !== null) {
        shiftId = Number(shiftVal);
        status = 'work';
        const found = props.shifts.find(s => s.id === shiftId);
        if (found) {
            shiftName = found.name;
            shiftColor = found.color;
        }
    } else {
        // Kosongkan
        shiftId = null;
        status = '';
    }

    // Update localMap
    if (!localMap.value[empId]) {
        localMap.value[empId] = {};
    }

    if (status === '' && shiftId === null) {
        delete localMap.value[empId][date];
    } else {
        localMap.value[empId][date] = {
            employee_id: empId,
            date: date,
            shift_id: shiftId,
            status: status,
            shift_name: shiftName,
            shift_color: shiftColor,
        };
    }

    // Tambahkan ke pending changes
    pendingChanges.value[changeKey] = {
        employee_id: empId,
        date: date,
        shift_id: shiftVal === 'OFF' ? 'OFF' : shiftId,
        status: status,
    };

    closeCellPicker();
};

const hasChange = (empId, date) => {
    return !!pendingChanges.value[`${empId}_${date}`];
};

const getSchedule = (empId, date) => {
    return localMap.value?.[empId]?.[date] || null;
};

const getScheduleLabel = (sched) => {
    if (!sched) return '';
    if (sched.status === 'off' || sched.shift_id === null) return 'OFF';
    return sched.shift_name || 'Shift';
};

const getCellBadgeClass = (sched) => {
    if (!sched) return '';
    if (sched.status === 'off' || sched.shift_id === null) {
        return 'bg-rose-50 text-rose-600 border border-rose-200/80';
    }
    const color = sched.shift_color || 'blue';
    return getShiftBadgeStyle(color);
};

const getShiftBadgeStyle = (color) => {
    switch (color) {
        case 'emerald':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200/80';
        case 'purple':
            return 'bg-purple-50 text-purple-700 border-purple-200/80';
        case 'amber':
            return 'bg-amber-50 text-amber-700 border-amber-200/80';
        case 'rose':
            return 'bg-rose-50 text-rose-700 border-rose-200/80';
        case 'cyan':
            return 'bg-cyan-50 text-cyan-700 border-cyan-200/80';
        case 'blue':
        default:
            return 'bg-blue-50 text-blue-700 border-blue-200/80';
    }
};

const getDotColor = (color) => {
    switch (color) {
        case 'emerald': return 'bg-emerald-500';
        case 'purple': return 'bg-purple-500';
        case 'amber': return 'bg-amber-500';
        case 'rose': return 'bg-rose-500';
        case 'cyan': return 'bg-cyan-500';
        case 'blue':
        default: return 'bg-blue-500';
    }
};

const getCellTooltip = (emp, date) => {
    const sched = getSchedule(emp.id, date);
    if (!sched) return `${emp.name} (${date}): Belum ada jadwal`;
    if (sched.status === 'off') return `${emp.name} (${date}): Hari Libur (OFF)`;
    return `${emp.name} (${date}): ${sched.shift_name}`;
};

const formatLocalDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};

// Batch Save
const saveBatchChanges = () => {
    if (unsavedChangesCount.value === 0) return;
    isSaving.value = true;

    const payload = {
        month: currentMonthStr.value,
        changes: Object.values(pendingChanges.value),
    };

    router.post(route('work-schedules.save-batch'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            pendingChanges.value = {};
            isSaving.value = false;
        },
        onError: () => {
            isSaving.value = false;
        }
    });
};

const discardChanges = () => {
    pendingChanges.value = {};
    localMap.value = JSON.parse(JSON.stringify(props.schedulesMap || {}));
};

// Navigasi Bulan
const changeMonth = (delta) => {
    const [year, month] = currentMonthStr.value.split('-').map(Number);
    const newDate = new Date(year, month - 1 + delta, 1);
    const newMonthStr = `${newDate.getFullYear()}-${String(newDate.getMonth() + 1).padStart(2, '0')}`;
    navigateToMonth(newMonthStr);
};

const onMonthInputChange = (event) => {
    if (event.target.value) {
        navigateToMonth(event.target.value);
    }
};

const goToToday = () => {
    const now = new Date();
    const todayMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
    navigateToMonth(todayMonth);
};

const navigateToMonth = (monthStr) => {
    if (unsavedChangesCount.value > 0) {
        if (!confirm('Anda memiliki perubahan yang belum disimpan. Pindah bulan akan membatalkan perubahan tersebut. Lanjutkan?')) {
            return;
        }
    }
    router.get(route('work-schedules.index'), {
        month: monthStr,
        branch: filterBranch.value,
        position: filterPosition.value,
        search: searchQuery.value,
    }, { preserveState: false });
};

// Filter Cabang & Jabatan
let debounceTimeout = null;
const applyFiltersDebounced = () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
};

const applyFilters = () => {
    if (unsavedChangesCount.value > 0) {
        if (!confirm('Anda memiliki perubahan yang belum disimpan. Menerapkan filter akan membatalkan perubahan tersebut. Lanjutkan?')) {
            return;
        }
    }
    router.get(route('work-schedules.index'), {
        month: currentMonthStr.value,
        branch: filterBranch.value,
        position: filterPosition.value,
        search: searchQuery.value,
    }, { preserveState: false, preserveScroll: true });
};

// Print Schedule
const printSchedule = () => {
    window.print();
};

// Lanjutkan dari bulan lalu
const continueFromPrevMonth = () => {
    if (confirm(`Apakah Anda yakin ingin melanjutkan pola roster dari bulan lalu ke bulan ${props.monthName}?`)) {
        router.post(route('work-schedules.continue-previous-month'), {
            month: currentMonthStr.value,
        }, {
            preserveScroll: true,
        });
    }
};

// Reset Bulan
const confirmResetMonth = () => {
    if (confirm(`PERINGATAN: Seluruh jadwal kerja untuk bulan ${props.monthName} akan dikosongkan. Lanjutkan?`)) {
        router.post(route('work-schedules.reset-month'), {
            month: currentMonthStr.value,
        }, {
            preserveScroll: true,
        });
    }
};

// ======================== MODAL GENERATE ========================
const openGenerateModal = ref(false);
const isGenerating = ref(false);
const modalFilterBranch = ref('');
const modalFilterPosition = ref('');
const selectedGenEmployees = ref(props.employees.map(e => e.id));
const selectedPatternId = ref('6-1');
const genDefaultShiftId = ref(props.shifts[0]?.id || 1);
const genStagger = ref(false);

const filteredGenEmployees = computed(() => {
    return props.employees.filter(emp => {
        if (modalFilterBranch.value) {
            const bVal = modalFilterBranch.value;
            const matchArea = emp.area_id == bVal || emp.area?.name == bVal;
            const matchBranch = emp.branch == bVal;
            if (!matchArea && !matchBranch) return false;
        }
        if (modalFilterPosition.value) {
            if (emp.position !== modalFilterPosition.value) return false;
        }
        return true;
    });
});

const toggleSelectAllEmployees = () => {
    const currentFilteredIds = filteredGenEmployees.value.map(e => e.id);
    const allSelected = currentFilteredIds.length > 0 && currentFilteredIds.every(id => selectedGenEmployees.value.includes(id));
    if (allSelected) {
        selectedGenEmployees.value = selectedGenEmployees.value.filter(id => !currentFilteredIds.includes(id));
    } else {
        const union = new Set([...selectedGenEmployees.value, ...currentFilteredIds]);
        selectedGenEmployees.value = Array.from(union);
    }
};

const submitGenerateSchedule = () => {
    if (selectedGenEmployees.value.length === 0) return;
    isGenerating.value = true;

    let patternDays = [];
    if (selectedPatternId.value === '6-1') {
        patternDays = [
            genDefaultShiftId.value,
            genDefaultShiftId.value,
            genDefaultShiftId.value,
            genDefaultShiftId.value,
            genDefaultShiftId.value,
            genDefaultShiftId.value,
            'OFF'
        ];
    } else if (selectedPatternId.value === '5-2') {
        patternDays = [
            genDefaultShiftId.value,
            genDefaultShiftId.value,
            genDefaultShiftId.value,
            genDefaultShiftId.value,
            genDefaultShiftId.value,
            'OFF',
            'OFF'
        ];
    } else {
        const found = props.patterns.find(p => p.id == selectedPatternId.value);
        patternDays = found?.pattern_data || [genDefaultShiftId.value, 'OFF'];
    }

    router.post(route('work-schedules.generate'), {
        month: currentMonthStr.value,
        employee_ids: selectedGenEmployees.value,
        pattern_days: patternDays,
        stagger: genStagger.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            openGenerateModal.value = false;
            isGenerating.value = false;
        },
        onError: () => {
            isGenerating.value = false;
        }
    });
};

// ======================== MODAL BUAT POLA ========================
const openPatternModal = ref(false);
const isSavingPattern = ref(false);
const patternForm = reactive({
    name: '',
    days: [props.shifts[0]?.id || 1, props.shifts[0]?.id || 1, 'OFF'],
});

const addDayToPattern = () => {
    patternForm.days.push(props.shifts[0]?.id || 1);
};

const removeDayFromPattern = (idx) => {
    patternForm.days.splice(idx, 1);
};

const submitCreatePattern = () => {
    isSavingPattern.value = true;
    router.post(route('work-schedules.patterns.store'), {
        name: patternForm.name,
        pattern_data: patternForm.days,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            patternForm.name = '';
            patternForm.days = [props.shifts[0]?.id || 1, props.shifts[0]?.id || 1, 'OFF'];
            isSavingPattern.value = false;
        },
        onError: () => {
            isSavingPattern.value = false;
        }
    });
};

const deletePattern = (patternId) => {
    if (confirm('Hapus pola roster ini?')) {
        router.delete(route('work-schedules.patterns.destroy', patternId), {
            preserveScroll: true,
        });
    }
};

// ======================== MODAL COPY BULAN ========================
const openCopyModal = ref(false);
const isCopying = ref(false);
const copySourceMonth = ref('');

const submitCopyMonth = () => {
    if (!copySourceMonth.value) return;
    isCopying.value = true;
    router.post(route('work-schedules.copy-month'), {
        from_month: copySourceMonth.value,
        to_month: currentMonthStr.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            openCopyModal.value = false;
            isCopying.value = false;
        },
        onError: () => {
            isCopying.value = false;
        }
    });
};

// ======================== MODAL QUICK ADD SHIFT ========================
const openAddShiftModal = ref(false);
const isSavingShift = ref(false);
const newShiftForm = reactive({
    name: '',
    start_time: '08:00',
    end_time: '17:00',
    color: 'blue',
});

const submitAddShift = () => {
    isSavingShift.value = true;
    router.post(route('work-schedules.quick-shift'), newShiftForm, {
        preserveScroll: true,
        onSuccess: () => {
            openAddShiftModal.value = false;
            isSavingShift.value = false;
            newShiftForm.name = '';
        },
        onError: () => {
            isSavingShift.value = false;
        }
    });
};
</script>

<style scoped>
/* Styling khusus print landscape untuk cetak A4/Folio yang rapi */
@media print {
    @page {
        size: landscape;
        margin: 8mm;
    }
    body {
        background: white !important;
        color: black !important;
    }
    table {
        font-size: 8px !important;
    }
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
