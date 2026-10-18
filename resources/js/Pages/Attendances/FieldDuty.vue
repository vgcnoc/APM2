<template>
    <AppLayout title="Absen Tugas Luar">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- 1. TOP HEADER SECTION SESUAI GAMBAR -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                        Absen Tugas Luar
                    </h1>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">
                        Absen kembali sebagai pengganti pulang reguler · Uang tugas:
                        <span :class="settings.duty_allowance_enabled ? 'text-emerald-600 font-bold' : 'text-slate-600 font-semibold'">
                            {{ settings.duty_allowance_enabled ? 'Aktif (Rp ' + Number(settings.duty_allowance_amount).toLocaleString('id-ID') + ')' : 'Nonaktif' }}
                        </span>
                    </p>
                </div>

                <!-- RIGHT ACTION BUTTONS: + BUAT TUGAS & PENGATURAN -->
                <div class="flex items-center gap-2.5">
                    <button
                        type="button"
                        @click="openCreateDutyModal"
                        class="px-4 py-2 bg-[#1e3a8a] hover:bg-[#172554] text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center gap-1.5 active:scale-95"
                    >
                        <span>+</span>
                        <span>Buat Tugas</span>
                    </button>

                    <button
                        type="button"
                        @click="openSettingsModal"
                        class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition shadow-2xs flex items-center gap-1.5 active:scale-95"
                    >
                        <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        <span>Pengaturan</span>
                    </button>
                </div>
            </div>

            <!-- 2. TAB NAVIGATOR (TUGAS AKTIF vs SEMUA RIWAYAT & FILTER TIPE) -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <!-- Left Tabs: Tugas Aktif vs Riwayat -->
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="switchTab('active')"
                        :class="[
                            currentTab === 'active'
                                ? 'bg-[#1e3a8a] text-white shadow-sm'
                                : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200 hover:bg-slate-50',
                            'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5'
                        ]"
                    >
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <span>Tugas Aktif</span>
                        <span v-if="activeCount > 0" class="ml-1 px-1.5 py-0.2 bg-blue-500/30 text-white rounded-full text-[10px]">
                            {{ activeCount }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="switchTab('history')"
                        :class="[
                            currentTab === 'history'
                                ? 'bg-[#1e3a8a] text-white shadow-sm'
                                : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200 hover:bg-slate-50',
                            'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5'
                        ]"
                    >
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>Semua Riwayat</span>
                        <span v-if="historyCount > 0" class="ml-1 px-1.5 py-0.2 bg-slate-200 text-slate-700 rounded-full text-[10px]">
                            {{ historyCount }}
                        </span>
                    </button>
                </div>

                <!-- Right Type Filters: Semua, Reguler, Non-Reguler -->
                <div class="flex items-center gap-1.5 bg-white border border-slate-200 p-1 rounded-xl shadow-2xs self-start">
                    <button
                        type="button"
                        @click="switchType('all')"
                        :class="[
                            currentType === 'all' ? 'bg-slate-100 font-bold text-slate-900' : 'text-slate-500 hover:text-slate-700 font-medium',
                            'px-3 py-1 rounded-lg text-xs transition'
                        ]"
                    >
                        Semua
                    </button>
                    <button
                        type="button"
                        @click="switchType('reguler')"
                        :class="[
                            currentType === 'reguler' ? 'bg-slate-100 font-bold text-slate-900' : 'text-slate-500 hover:text-slate-700 font-medium',
                            'px-3 py-1 rounded-lg text-xs transition'
                        ]"
                    >
                        Reguler
                    </button>
                    <button
                        type="button"
                        @click="switchType('non_reguler')"
                        :class="[
                            currentType === 'non_reguler' ? 'bg-slate-100 font-bold text-slate-900' : 'text-slate-500 hover:text-slate-700 font-medium',
                            'px-3 py-1 rounded-lg text-xs transition'
                        ]"
                    >
                        Non-Reguler
                    </button>
                </div>
            </div>

            <!-- 3. SEARCH & ACTIONS TOOLBAR -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs">
                <!-- Search Input -->
                <div class="relative flex-1 max-w-md">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </div>
                    <input
                        type="text"
                        v-model="searchQuery"
                        @input="debounceSearch"
                        placeholder="Cari karyawan/lokasi..."
                        class="w-full rounded-xl border-slate-200 pl-9 pr-3 text-xs focus:border-blue-500 focus:ring-blue-500/20 font-medium"
                    />
                </div>

                <!-- Date Range & Action Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-1.5 bg-slate-50/80 border border-slate-200 rounded-xl px-2 py-1">
                        <input
                            type="date"
                            v-model="startDate"
                            @change="applyFilter"
                            class="bg-transparent border-none p-0 text-xs text-slate-700 font-semibold focus:ring-0"
                        />
                        <span class="text-xs text-slate-400">—</span>
                        <input
                            type="date"
                            v-model="endDate"
                            @change="applyFilter"
                            class="bg-transparent border-none p-0 text-xs text-slate-700 font-semibold focus:ring-0"
                        />
                        <button
                            v-if="startDate || endDate"
                            type="button"
                            @click="clearDateFilter"
                            class="text-slate-400 hover:text-rose-500 font-bold px-1 text-xs"
                            title="Reset Filter Tanggal"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Export, Import, Print -->
                    <button
                        type="button"
                        @click="exportCsv"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs"
                    >
                        <svg class="w-3.5 h-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        <span>Export</span>
                    </button>

                    <button
                        type="button"
                        @click="openImportModal"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs"
                    >
                        <svg class="w-3.5 h-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <span>Import</span>
                    </button>

                    <button
                        type="button"
                        @click="windowPrint"
                        class="px-3 py-1.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs"
                    >
                        <svg class="w-3.5 h-3.5 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                        <span>Print</span>
                    </button>
                </div>
            </div>

            <!-- 4. DATA TABLE SESUAI SCREENSHOT -->
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-50/90 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="p-3.5 w-10 text-center">
                                    <input
                                        type="checkbox"
                                        @change="toggleSelectAll"
                                        :checked="isAllSelected"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                </th>
                                <th class="p-3.5">Karyawan</th>
                                <th class="p-3.5">Tipe</th>
                                <th class="p-3.5">Tanggal</th>
                                <th class="p-3.5">Lokasi Tujuan</th>
                                <th class="p-3.5">Mode Absen</th>
                                <th class="p-3.5">Status</th>
                                <th class="p-3.5">Approval</th>
                                <th class="p-3.5">Jadwal</th>
                                <th class="p-3.5">Jam</th>
                                <th class="p-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <tr
                                v-for="item in duties.data"
                                :key="item.id"
                                class="hover:bg-slate-50/70 transition-colors"
                            >
                                <!-- Checkbox -->
                                <td class="p-3.5 text-center">
                                    <input
                                        type="checkbox"
                                        :value="item.id"
                                        v-model="selectedIds"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    />
                                </td>

                                <!-- Karyawan -->
                                <td class="p-3.5">
                                    <div class="font-black text-slate-900 text-xs">
                                        {{ item.employee?.name || 'Karyawan' }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 font-medium">
                                        {{ item.employee?.position || 'Admin/Cs' }}
                                    </div>
                                </td>

                                <!-- Tipe: Reguler / Non-Reguler -->
                                <td class="p-3.5">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border border-blue-200 bg-blue-50 text-blue-700">
                                        {{ item.duty_type === 'reguler' ? 'Reguler' : 'Non-Reguler' }}
                                    </span>
                                </td>

                                <!-- Tanggal -->
                                <td class="p-3.5 font-medium text-slate-600 whitespace-nowrap">
                                    {{ formatDateOnly(item.date) }}
                                </td>

                                <!-- Lokasi Tujuan -->
                                <td class="p-3.5">
                                    <div class="font-bold text-slate-900">
                                        {{ item.destination_location }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 truncate max-w-xs">
                                        {{ item.purpose || '-' }}
                                    </div>
                                </td>

                                <!-- Mode Absen -->
                                <td class="p-3.5 whitespace-nowrap">
                                    <span
                                        v-if="item.attendance_mode === 'replace_both'"
                                        class="px-2 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 font-bold border border-indigo-200 text-[10px] flex items-center gap-1 w-max"
                                    >
                                        <span>📥📤</span>
                                        <span>Masuk & Pulang</span>
                                    </span>
                                    <span
                                        v-else-if="item.attendance_mode === 'replace_in'"
                                        class="px-2 py-0.5 rounded-lg bg-blue-50 text-blue-700 font-bold border border-blue-200 text-[10px] flex items-center gap-1 w-max"
                                    >
                                        <span>📥</span>
                                        <span>Masuk</span>
                                    </span>
                                    <span
                                        v-else-if="item.attendance_mode === 'replace_out'"
                                        class="px-2 py-0.5 rounded-lg bg-teal-50 text-teal-700 font-bold border border-teal-200 text-[10px] flex items-center gap-1 w-max"
                                    >
                                        <span>📤</span>
                                        <span>Pulang</span>
                                    </span>
                                    <span
                                        v-else
                                        class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-600 font-bold text-[10px] flex items-center gap-1 w-max"
                                    >
                                        <span>📍</span>
                                        <span>Tambahan</span>
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="p-3.5 whitespace-nowrap">
                                    <span
                                        v-if="item.status === 'active'"
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border border-blue-200 bg-blue-50 text-blue-700"
                                    >
                                        Tugas Aktif
                                    </span>
                                    <span
                                        v-else-if="item.status === 'completed'"
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border border-emerald-200 bg-emerald-50 text-emerald-700"
                                    >
                                        Selesai
                                    </span>
                                    <span
                                        v-else
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border border-slate-200 bg-slate-100 text-slate-600"
                                    >
                                        {{ item.status }}
                                    </span>
                                </td>

                                <!-- Approval -->
                                <td class="p-3.5 whitespace-nowrap">
                                    <span
                                        v-if="item.approval_status === 'auto' || item.approval_status === 'approved'"
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                    >
                                        {{ item.approval_status === 'auto' ? 'Auto' : 'Approved' }}
                                    </span>
                                    <span
                                        v-else
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200"
                                    >
                                        Pending
                                    </span>
                                </td>

                                <!-- Jadwal -->
                                <td class="p-3.5 whitespace-nowrap text-slate-600 font-mono text-[11px]">
                                    {{ formatSchedule(item) }}
                                </td>

                                <!-- Jam Aktual -->
                                <td class="p-3.5 whitespace-nowrap text-slate-700 font-mono text-[11px]">
                                    <span v-if="item.check_in_time || item.check_out_time">
                                        {{ item.check_in_time ? item.check_in_time.slice(0, 5) : '-' }}
                                        -
                                        {{ item.check_out_time ? item.check_out_time.slice(0, 5) : '-' }}
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>

                                <!-- Aksi -->
                                <td class="p-3.5 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Tombol Check In / Check Out Kamera -->
                                        <button
                                            v-if="item.status === 'active' && !item.check_in_time"
                                            type="button"
                                            @click="openCameraModal(item, 'in')"
                                            class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[10px] font-bold transition flex items-center gap-1"
                                            title="Check-In Tugas Luar"
                                        >
                                            <span>📷</span>
                                            <span>Check-In</span>
                                        </button>

                                        <button
                                            v-else-if="item.status === 'active' && item.check_in_time && !item.check_out_time"
                                            type="button"
                                            @click="openCameraModal(item, 'out')"
                                            class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[10px] font-bold transition flex items-center gap-1"
                                            title="Check-Out / Selesai Tugas Luar"
                                        >
                                            <span>🏁</span>
                                            <span>Check-Out</span>
                                        </button>

                                        <!-- Tombol View Detail / Foto -->
                                        <button
                                            v-if="item.check_in_photo || item.check_out_photo"
                                            type="button"
                                            @click="openDetailModal(item)"
                                            class="p-1.5 text-slate-500 hover:text-blue-600 rounded-lg hover:bg-slate-100 transition"
                                            title="Lihat Detail & Foto"
                                        >
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </button>

                                        <!-- Delete Icon Merah Sesuai Screenshot -->
                                        <button
                                            type="button"
                                            @click="deleteDuty(item)"
                                            class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition"
                                            title="Hapus Tugas"
                                        >
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!duties.data || duties.data.length === 0">
                                <td colspan="11" class="p-8 text-center text-slate-400 space-y-2">
                                    <span class="text-3xl block">📋</span>
                                    <p class="text-xs font-medium">Tidak ada penugasan luar yang ditemukan.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div v-if="duties.links && duties.links.length > 3" class="p-3.5 border-t border-slate-200 bg-slate-50 flex items-center justify-between text-xs">
                    <span class="text-slate-500">
                        Menampilkan {{ duties.from || 0 }} - {{ duties.to || 0 }} dari {{ duties.total || 0 }} tugas
                    </span>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, idx) in duties.links" :key="idx">
                            <button
                                v-if="link.url"
                                @click="router.get(link.url, {}, { preserveState: true })"
                                v-html="link.label"
                                :class="[
                                    link.active ? 'bg-blue-600 text-white font-bold' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200',
                                    'px-2.5 py-1 rounded-lg text-xs'
                                ]"
                            ></button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Floating Bulk Action Bar -->
            <transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-2"
            >
                <div
                    v-if="selectedIds.length > 0"
                    class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-slate-900/95 backdrop-blur-sm text-white px-5 py-3 rounded-2xl shadow-2xl flex items-center gap-4 text-xs font-bold border border-slate-700 animate-in fade-in"
                >
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        <span>{{ selectedIds.length }} tugas luar terpilih</span>
                    </span>
                    <button
                        type="button"
                        @click="bulkDeleteDuties"
                        class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl transition flex items-center gap-1 shadow-sm"
                    >
                        <span>🗑️</span>
                        <span>Hapus Masal</span>
                    </button>
                    <button
                        type="button"
                        @click="selectedIds = []; isAllSelected = false"
                        class="text-slate-400 hover:text-white transition"
                    >
                        Batal
                    </button>
                </div>
            </transition>

            <!-- ========================================================= -->
            <!-- MODAL 1: + BUAT TUGAS                                     -->
            <!-- ========================================================= -->
            <div
                v-if="isCreateModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
            >
                <div class="bg-white rounded-3xl max-w-xl w-full overflow-hidden shadow-2xl border border-slate-200 p-6 space-y-4 max-h-[90vh] flex flex-col animate-in fade-in zoom-in-95 duration-150">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>➕</span>
                            <span>Buat Penugasan Luar / Lapangan</span>
                        </h3>
                        <button type="button" @click="isCreateModalOpen = false" class="text-slate-400 font-bold hover:text-slate-600">✕</button>
                    </div>

                    <form @submit.prevent="submitCreateDuty" class="space-y-3.5 text-xs overflow-y-auto pr-1">
                        <!-- Karyawan -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Pilih Karyawan Ditugaskan *</label>
                            <select
                                v-model="createForm.employee_id"
                                required
                                class="w-full rounded-xl border-slate-200 font-bold text-xs"
                            >
                                <option value="" disabled>-- Pilih Karyawan --</option>
                                <option v-for="emp in allEmployees" :key="emp.id" :value="emp.id">
                                    {{ emp.name }} ({{ emp.position || 'Staff' }} - {{ emp.branch || 'Pusat' }})
                                </option>
                            </select>
                        </div>

                        <!-- Tanggal & Tipe -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Tanggal Tugas *</label>
                                <input
                                    type="date"
                                    v-model="createForm.date"
                                    required
                                    class="w-full rounded-xl border-slate-200 font-bold text-xs"
                                />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Tipe Penugasan</label>
                                <select
                                    v-model="createForm.duty_type"
                                    class="w-full rounded-xl border-slate-200 font-bold text-xs"
                                >
                                    <option value="reguler">Reguler</option>
                                    <option value="non_reguler">Non-Reguler</option>
                                </select>
                            </div>
                        </div>

                        <!-- Lokasi Tujuan & Maksud -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Lokasi Tujuan *</label>
                            <input
                                type="text"
                                v-model="createForm.destination_location"
                                required
                                class="w-full rounded-xl border-slate-200 font-bold text-xs"
                                placeholder="Contoh: Cilandak Kaum / Cigombong / Kantor Klien"
                            />
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Maksud / Keterangan Tugas</label>
                            <input
                                type="text"
                                v-model="createForm.purpose"
                                class="w-full rounded-xl border-slate-200 font-bold text-xs"
                                placeholder="Contoh: Market BC 1 / Refresh skill / Instalasi Fiber"
                            />
                        </div>

                        <!-- Mode Pengganti Absensi Reguler (INTI FITUR) -->
                        <div class="p-3 bg-blue-50/60 rounded-xl border border-blue-200 space-y-1.5">
                            <label class="block font-bold text-blue-900">⚡ Mode Pengganti Absensi Reguler *</label>
                            <select
                                v-model="createForm.attendance_mode"
                                class="w-full rounded-xl border-blue-200 bg-white font-bold text-xs text-blue-900"
                            >
                                <option value="replace_both">📥📤 Pengganti Masuk & Pulang Reguler (Berangkat & Pulang Langsung dari Lokasi)</option>
                                <option value="replace_in">📥 Pengganti Masuk Reguler Saja (Berangkat dari Rumah, Pulang ke Kantor)</option>
                                <option value="replace_out">📤 Pengganti Pulang Reguler Saja (Pagi di Kantor, Langsung Pulang dari Lokasi)</option>
                                <option value="none">📍 Hanya Catatan Tugas (Tidak Menggantikan Absen Reguler)</option>
                            </select>
                            <p class="text-[11px] text-blue-700 font-medium">
                                Check-in dan check-out tugas luar otomatis mengisi kehadiran harian karyawan tanpa denda telat.
                            </p>
                        </div>

                        <!-- Jadwal Jam -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Jam Mulai Terjadwal</label>
                                <input
                                    type="time"
                                    v-model="createForm.scheduled_start_time"
                                    class="w-full rounded-xl border-slate-200 font-bold text-xs"
                                />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Jam Selesai Terjadwal</label>
                                <input
                                    type="time"
                                    v-model="createForm.scheduled_end_time"
                                    class="w-full rounded-xl border-slate-200 font-bold text-xs"
                                />
                            </div>
                        </div>

                        <!-- Catatan Tambahan -->
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Catatan Tambahan (Opsional)</label>
                            <textarea
                                v-model="createForm.notes"
                                rows="2"
                                class="w-full rounded-xl border-slate-200 text-xs"
                                placeholder="Detail peralatan kerja, nomor kontak klien, dll..."
                            ></textarea>
                        </div>

                        <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="isCreateModalOpen = false"
                                class="px-4 py-2 bg-slate-100 rounded-xl font-bold"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="createForm.processing"
                                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold shadow-sm"
                            >
                                Simpan Penugasan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- MODAL 2: ⚙️ PENGATURAN TUGAS LUAR                          -->
            <!-- ========================================================= -->
            <div
                v-if="isSettingsModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
            >
                <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-200 p-6 space-y-4 animate-in fade-in zoom-in-95 duration-150">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>⚙️</span>
                            <span>Pengaturan Absen Tugas Luar</span>
                        </h3>
                        <button type="button" @click="isSettingsModalOpen = false" class="text-slate-400 font-bold hover:text-slate-600">✕</button>
                    </div>

                    <form @submit.prevent="submitSettings" class="space-y-4 text-xs">
                        <!-- Toggle Pengganti Masuk -->
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                            <div>
                                <div class="font-bold text-slate-900">Pengganti Absen Masuk Reguler</div>
                                <p class="text-[11px] text-slate-500">Check-in tugas luar otomatis mengisi jam masuk presensi harian</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="settingsForm.duty_replace_regular_in" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <!-- Toggle Pengganti Pulang -->
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                            <div>
                                <div class="font-bold text-slate-900">Pengganti Absen Pulang Reguler</div>
                                <p class="text-[11px] text-slate-500">Check-out tugas luar otomatis mengisi jam pulang presensi harian</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="settingsForm.duty_replace_regular_out" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <!-- Toggle Uang Tugas -->
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-bold text-slate-900">Uang Tugas / Insentif Lapangan</div>
                                    <p class="text-[11px] text-slate-500">Otomatis berikan uang saku/tugas saat penugasan selesai</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="settingsForm.duty_allowance_enabled" class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            <div v-show="settingsForm.duty_allowance_enabled" class="pt-2 border-t border-slate-200">
                                <label class="block font-bold text-slate-700 mb-1">Nominal Uang Tugas (Rp / Tugas)</label>
                                <input
                                    type="number"
                                    v-model="settingsForm.duty_allowance_amount"
                                    class="w-full rounded-xl border-slate-200 font-bold text-xs"
                                    placeholder="25000"
                                />
                            </div>
                        </div>

                        <!-- Toggle Auto Approval -->
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                            <div>
                                <div class="font-bold text-slate-900">Auto Approval Tugas Luar</div>
                                <p class="text-[11px] text-slate-500">Tugas luar otomatis disetujui tanpa menunggu verifikasi manual</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="settingsForm.duty_auto_approval" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <div class="pt-2 flex items-center justify-end gap-2">
                            <button
                                type="button"
                                @click="isSettingsModalOpen = false"
                                class="px-4 py-2 bg-slate-100 rounded-xl font-bold"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="settingsForm.processing"
                                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold shadow-sm"
                            >
                                Simpan Pengaturan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- MODAL 3: KAMERA WEBCAM CHECK-IN / CHECK-OUT               -->
            <!-- ========================================================= -->
            <div
                v-if="isCameraModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
            >
                <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-200 space-y-4 p-5 animate-in fade-in zoom-in-95 duration-150">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>📷</span>
                            <span>{{ cameraActionType === 'in' ? 'Check-In Tugas Luar' : 'Check-Out / Selesai Tugas' }}</span>
                        </h3>
                        <button type="button" @click="closeCameraModal" class="text-slate-400 font-bold hover:text-slate-600">✕</button>
                    </div>

                    <div class="text-xs text-slate-600">
                        Lokasi: <strong>{{ activeDuty?.destination_location }}</strong>
                        <span class="text-slate-400">({{ activeDuty?.employee?.name }})</span>
                    </div>

                    <!-- Video Preview Area -->
                    <div class="relative rounded-2xl overflow-hidden bg-slate-900 aspect-4/3 flex items-center justify-center border border-slate-800">
                        <video
                            v-show="!capturedPhoto && !cameraError"
                            ref="videoRef"
                            autoplay
                            playsinline
                            class="w-full h-full object-cover"
                            :class="{ '-scale-x-100': cameraFacing === 'user' }"
                        ></video>

                        <!-- Error State jika Kamera Terblokir / Tidak Ada -->
                        <div v-if="cameraError && !capturedPhoto" class="p-6 text-center text-white space-y-3">
                            <span class="text-3xl block">📷⚠️</span>
                            <p class="text-xs text-rose-300 font-semibold">{{ cameraError }}</p>
                            <p class="text-[11px] text-slate-300">Kamera perangkat tidak dapat dibuka langsung. Anda dapat memilih foto dari galeri/kamera HP.</p>
                            <button
                                type="button"
                                @click="fileInputRef?.click()"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-sm inline-flex items-center gap-1.5"
                            >
                                <span>📁</span>
                                <span>Pilih Foto dari Perangkat</span>
                            </button>
                        </div>

                        <img
                            v-if="capturedPhoto"
                            :src="capturedPhoto"
                            class="w-full h-full object-cover"
                        />

                        <!-- Overlay Switch Kamera -->
                        <button
                            v-if="!capturedPhoto && !cameraError"
                            type="button"
                            @click="switchCameraFacing"
                            class="absolute top-3 right-3 px-3 py-1.5 bg-black/50 text-white rounded-full text-xs font-semibold backdrop-blur-xs hover:bg-black/70 flex items-center gap-1.5"
                        >
                            <span>🔄 Putar Kamera</span>
                        </button>
                    </div>

                    <canvas ref="canvasRef" class="hidden"></canvas>

                    <!-- Hidden Input File untuk Fallback Kamera/Upload -->
                    <input
                        type="file"
                        ref="fileInputRef"
                        accept="image/*"
                        capture="user"
                        class="hidden"
                        @change="handleFileUpload"
                    />

                    <!-- Tombol Opsi Unggah Foto jika kamera aktif tapi user ingin upload -->
                    <div v-if="!capturedPhoto && !cameraError" class="flex justify-center">
                        <button
                            type="button"
                            @click="fileInputRef?.click()"
                            class="text-[11px] text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1"
                        >
                            <span>📁</span>
                            <span>Atau pilih foto dari file / kamera HP</span>
                        </button>
                    </div>

                    <!-- Catatan Pelaksanaan -->
                    <div class="space-y-1.5 text-xs">
                        <label class="block font-bold text-slate-700">Catatan Pelaksanaan / Hasil Lapangan</label>
                        <textarea
                            v-model="attendanceForm.notes"
                            rows="2"
                            class="w-full rounded-xl border-slate-200 text-xs font-medium"
                            placeholder="Contoh: Sudah tiba di lokasi, koordinasi dengan PIC..."
                        ></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-2.5 pt-2">
                        <button
                            type="button"
                            @click="closeCameraModal"
                            class="px-4 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold"
                        >
                            Batal
                        </button>

                        <button
                            v-if="!capturedPhoto && !cameraError"
                            type="button"
                            @click="takeSnapshot"
                            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center gap-2"
                        >
                            <span>📸 Ambil Foto Selfie</span>
                        </button>

                        <button
                            v-if="!capturedPhoto && cameraError"
                            type="button"
                            @click="fileInputRef?.click()"
                            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center gap-2"
                        >
                            <span>📁 Pilih Foto</span>
                        </button>

                        <button
                            v-if="capturedPhoto"
                            type="button"
                            @click="retakeSnapshot"
                            class="px-4 py-2.5 bg-slate-200 text-slate-700 rounded-xl text-xs font-bold"
                        >
                            Foto Ulang
                        </button>

                        <button
                            v-if="capturedPhoto"
                            type="button"
                            @click="submitAttendance"
                            :disabled="attendanceForm.processing"
                            class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-sm"
                        >
                            <span v-if="attendanceForm.processing">Mengirim...</span>
                            <span v-else>Kirim Absensi</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- MODAL 4: DETAIL & FOTO LIGHTBOX                           -->
            <!-- ========================================================= -->
            <div
                v-if="isDetailModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-xs"
                @click.self="isDetailModalOpen = false"
            >
                <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl p-6 space-y-4 text-xs animate-in fade-in zoom-in-95 duration-150">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-black text-slate-900">
                                Detail Tugas: {{ selectedDetailItem?.destination_location }}
                            </h3>
                            <p class="text-xs text-slate-500 font-medium">
                                {{ selectedDetailItem?.employee?.name }} ({{ selectedDetailItem?.employee?.position || 'Staff' }})
                            </p>
                        </div>
                        <button type="button" @click="isDetailModalOpen = false" class="text-slate-400 font-bold hover:text-slate-600">✕</button>
                    </div>

                    <!-- Pratinjau 2 Foto (In & Out) -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <span class="font-bold text-slate-700 block">Foto Check-In:</span>
                            <div class="aspect-4/3 rounded-xl overflow-hidden bg-slate-100 border border-slate-200">
                                <img
                                    v-if="selectedDetailItem?.check_in_photo"
                                    :src="'/storage/' + selectedDetailItem.check_in_photo"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-slate-400">
                                    Belum ada foto
                                </div>
                            </div>
                            <span class="text-[10px] text-slate-400 block font-mono">
                                {{ selectedDetailItem?.check_in_time || '-' }} WIB
                            </span>
                        </div>

                        <div class="space-y-1">
                            <span class="font-bold text-slate-700 block">Foto Check-Out:</span>
                            <div class="aspect-4/3 rounded-xl overflow-hidden bg-slate-100 border border-slate-200">
                                <img
                                    v-if="selectedDetailItem?.check_out_photo"
                                    :src="'/storage/' + selectedDetailItem.check_out_photo"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-slate-400">
                                    Belum ada foto
                                </div>
                            </div>
                            <span class="text-[10px] text-slate-400 block font-mono">
                                {{ selectedDetailItem?.check_out_time || '-' }} WIB
                            </span>
                        </div>
                    </div>

                    <!-- Info Integrasi & Catatan -->
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1 text-slate-700">
                        <p><strong>Maksud Tugas:</strong> {{ selectedDetailItem?.purpose || '-' }}</p>
                        <p><strong>Catatan Lapangan:</strong> {{ selectedDetailItem?.notes || '-' }}</p>
                        <p v-if="selectedDetailItem?.is_regular_attendance_synced" class="text-emerald-700 font-bold">
                            ✓ Tersinkronisasi ke Presensi Reguler
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="button"
                            @click="isDetailModalOpen = false"
                            class="px-4 py-2 bg-slate-200 hover:bg-slate-300 font-bold rounded-xl text-slate-800"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- MODAL 5: IMPORT PENUGASAN LUAR DARI CSV                    -->
            <!-- ========================================================= -->
            <div
                v-if="isImportModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
            >
                <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-200 p-6 space-y-4 animate-in fade-in zoom-in-95 duration-150 text-xs">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>📥</span>
                            <span>Import Penugasan Luar (CSV)</span>
                        </h3>
                        <button type="button" @click="isImportModalOpen = false" class="text-slate-400 font-bold hover:text-slate-600">✕</button>
                    </div>

                    <p class="text-slate-500 leading-relaxed">
                        Unggah file CSV berisi daftar penugasan luar karyawan. Sistem akan otomatis memetakan nama/email karyawan dan jadwal tugas ke presensi harian.
                    </p>

                    <div class="p-4 bg-slate-50 rounded-2xl border border-dashed border-slate-300 text-center space-y-2">
                        <input
                            type="file"
                            ref="csvFileInputRef"
                            accept=".csv,text/csv,text/plain"
                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                            @change="handleCsvFileChange"
                        />
                        <p class="text-[11px] text-slate-400">Format yang didukung: .csv (Maksimal 5MB)</p>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <button
                            type="button"
                            @click="downloadImportTemplate"
                            class="text-blue-600 hover:text-blue-800 font-bold text-xs flex items-center gap-1.5"
                        >
                            <span>📥</span>
                            <span>Download Template CSV</span>
                        </button>
                    </div>

                    <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="isImportModalOpen = false"
                            class="px-4 py-2 bg-slate-100 rounded-xl font-bold text-slate-700"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitImport"
                            :disabled="!importFile || importForm.processing"
                            class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold shadow-sm disabled:opacity-50"
                        >
                            <span v-if="importForm.processing">Mengimpor...</span>
                            <span v-else>Mulai Import</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    duties: Object,
    activeCount: Number,
    historyCount: Number,
    settings: Object,
    allEmployees: Array,
    filters: Object,
});

// Tab & Type State
const currentTab = ref(props.filters?.tab || 'active');
const currentType = ref(props.filters?.type || 'all');
const searchQuery = ref(props.filters?.search || '');
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');
const selectedIds = ref([]);

// Filter Handlers
const switchTab = (tab) => {
    currentTab.value = tab;
    applyFilter();
};

const switchType = (type) => {
    currentType.value = type;
    applyFilter();
};

let debounceTimer = null;
const debounceSearch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        applyFilter();
    }, 400);
};

const applyFilter = () => {
    router.get(
        route('field-duties.index'),
        {
            tab: currentTab.value,
            type: currentType.value,
            search: searchQuery.value,
            start_date: startDate.value,
            end_date: endDate.value,
        },
        { preserveState: true }
    );
};

const clearDateFilter = () => {
    startDate.value = '';
    endDate.value = '';
    applyFilter();
};

// Checkbox Selection
const isAllSelected = ref(false);
const toggleSelectAll = () => {
    isAllSelected.value = !isAllSelected.value;
    if (isAllSelected.value) {
        selectedIds.value = (props.duties.data || []).map((d) => d.id);
    } else {
        selectedIds.value = [];
    }
};

// Date & Time Helpers
const formatDateOnly = (dateStr) => {
    if (!dateStr) return '-';
    return String(dateStr).slice(0, 10);
};

const formatSchedule = (item) => {
    if (!item.scheduled_start_time) return '-';
    const s = item.scheduled_start_time.slice(0, 5);
    const e = item.scheduled_end_time ? item.scheduled_end_time.slice(0, 5) : '-';
    return `${s}-${e} (${item.scheduled_duration_minutes || 60}m)`;
};

// Export CSV
const exportCsv = () => {
    const rows = [
        ['ID', 'Karyawan', 'Tanggal', 'Tipe', 'Lokasi Tujuan', 'Maksud', 'Mode Absen', 'Status', 'Jadwal Mulai', 'Jadwal Selesai', 'Jam Masuk', 'Jam Pulang'],
    ];
    (props.duties.data || []).forEach((d) => {
        rows.push([
            d.id,
            d.employee?.name || '-',
            formatDateOnly(d.date),
            d.duty_type,
            `"${d.destination_location}"`,
            `"${d.purpose || '-'}"`,
            d.attendance_mode,
            d.status,
            d.scheduled_start_time || '-',
            d.scheduled_end_time || '-',
            d.check_in_time || '-',
            d.check_out_time || '-',
        ]);
    });
    const csvContent = 'data:text/csv;charset=utf-8,' + rows.map((e) => e.join(',')).join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `absen_tugas_luar_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const windowPrint = () => {
    window.print();
};

// ========================================================
// MODAL 1: + BUAT TUGAS
// ========================================================
const isCreateModalOpen = ref(false);
const createForm = useForm({
    employee_id: '',
    date: new Date().toISOString().slice(0, 10),
    duty_type: 'reguler',
    destination_location: '',
    purpose: '',
    attendance_mode: 'replace_both',
    scheduled_start_time: '14:00',
    scheduled_end_time: '15:00',
    duty_allowance_amount: props.settings?.duty_allowance_amount ?? 0,
    notes: '',
});

const openCreateDutyModal = () => {
    createForm.reset();
    createForm.date = new Date().toISOString().slice(0, 10);
    createForm.attendance_mode = 'replace_both';
    createForm.duty_allowance_amount = props.settings?.duty_allowance_enabled ? props.settings.duty_allowance_amount : 0;
    isCreateModalOpen.value = true;
};

const submitCreateDuty = () => {
    createForm.post(route('field-duties.store'), {
        onSuccess: () => {
            isCreateModalOpen.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Tugas Dibuat!',
                text: 'Penugasan luar berhasil ditambahkan.',
                timer: 1800,
                showConfirmButton: false,
            });
        },
        onError: (errs) => {
            const firstErr = Object.values(errs)[0] || 'Gagal membuat tugas luar.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: firstErr,
            });
        }
    });
};

// ========================================================
// MODAL 2: ⚙️ PENGATURAN TUGAS LUAR
// ========================================================
const isSettingsModalOpen = ref(false);
const settingsForm = useForm({
    duty_replace_regular_in: props.settings?.duty_replace_regular_in ?? true,
    duty_replace_regular_out: props.settings?.duty_replace_regular_out ?? true,
    duty_allowance_enabled: props.settings?.duty_allowance_enabled ?? false,
    duty_allowance_amount: props.settings?.duty_allowance_amount ?? 25000,
    duty_auto_approval: props.settings?.duty_auto_approval ?? true,
});

const openSettingsModal = () => {
    settingsForm.duty_replace_regular_in = props.settings?.duty_replace_regular_in ?? true;
    settingsForm.duty_replace_regular_out = props.settings?.duty_replace_regular_out ?? true;
    settingsForm.duty_allowance_enabled = props.settings?.duty_allowance_enabled ?? false;
    settingsForm.duty_allowance_amount = props.settings?.duty_allowance_amount ?? 25000;
    settingsForm.duty_auto_approval = props.settings?.duty_auto_approval ?? true;
    isSettingsModalOpen.value = true;
};

const submitSettings = () => {
    settingsForm.post(route('field-duties.settings.update'), {
        onSuccess: () => {
            isSettingsModalOpen.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Tersimpan',
                text: 'Pengaturan absen tugas luar berhasil disimpan.',
                timer: 1800,
                showConfirmButton: false,
            });
        },
    });
};

// ========================================================
// MODAL 3: KAMERA WEBCAM SELFIE, FALLBACK UPLOAD & WATERMARK
// ========================================================
const isCameraModalOpen = ref(false);
const activeDuty = ref(null);
const cameraActionType = ref('in'); // 'in' or 'out'
const videoRef = ref(null);
const canvasRef = ref(null);
const capturedPhoto = ref(null);
const cameraFacing = ref('user');
const cameraError = ref(null);
const fileInputRef = ref(null);
let mediaStream = null;

const attendanceForm = useForm({
    photo: '',
    latitude: null,
    longitude: null,
    location_name: '',
    notes: '',
});

const userCoords = ref({ lat: null, lng: null });

const openCameraModal = (item, action) => {
    activeDuty.value = item;
    cameraActionType.value = action;
    attendanceForm.photo = '';
    attendanceForm.notes = '';
    attendanceForm.location_name = item.destination_location;
    cameraError.value = null;
    isCameraModalOpen.value = true;
    startCamera();
    fetchGeolocation();
};

const fetchGeolocation = () => {
    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                userCoords.value = {
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                };
            },
            (err) => console.warn('GPS warning: ' + err.message),
            { enableHighAccuracy: true, timeout: 8000 }
        );
    }
};

const startCamera = async () => {
    try {
        cameraError.value = null;
        capturedPhoto.value = null;
        if (mediaStream) {
            mediaStream.getTracks().forEach((t) => t.stop());
        }
        mediaStream = await navigator.mediaDevices.getUserMedia({
            video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: cameraFacing.value },
            audio: false,
        });
        if (videoRef.value) {
            videoRef.value.srcObject = mediaStream;
        }
    } catch (err) {
        console.warn('Camera warning: ' + err.message);
        cameraError.value = err.message || 'Kamera tidak dapat diakses.';
    }
};

const switchCameraFacing = () => {
    cameraFacing.value = cameraFacing.value === 'user' ? 'environment' : 'user';
    startCamera();
};

const takeSnapshot = () => {
    if (!videoRef.value || !canvasRef.value) return;

    const video = videoRef.value;
    const canvas = canvasRef.value;
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;

    const ctx = canvas.getContext('2d');
    if (cameraFacing.value === 'user') {
        ctx.translate(canvas.width, 0);
        ctx.scale(-1, 1);
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        ctx.setTransform(1, 0, 0, 1, 0, 0);
    } else {
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    }

    // Watermark Digital
    drawDutyWatermark(ctx, canvas.width, canvas.height);

    capturedPhoto.value = canvas.toDataURL('image/jpeg', 0.88);
    attendanceForm.photo = capturedPhoto.value;
    attendanceForm.latitude = userCoords.value.lat;
    attendanceForm.longitude = userCoords.value.lng;

    stopCamera();
};

const handleFileUpload = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        const img = new Image();
        img.onload = () => {
            const canvas = canvasRef.value;
            if (!canvas) return;

            let targetW = img.width;
            let targetH = img.height;
            const maxDim = 800;
            if (targetW > maxDim || targetH > maxDim) {
                if (targetW > targetH) {
                    targetH = Math.round((targetH * maxDim) / targetW);
                    targetW = maxDim;
                } else {
                    targetW = Math.round((targetW * maxDim) / targetH);
                    targetH = maxDim;
                }
            }

            canvas.width = targetW;
            canvas.height = targetH;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, targetW, targetH);

            drawDutyWatermark(ctx, targetW, targetH);

            capturedPhoto.value = canvas.toDataURL('image/jpeg', 0.88);
            attendanceForm.photo = capturedPhoto.value;
            attendanceForm.latitude = userCoords.value.lat;
            attendanceForm.longitude = userCoords.value.lng;

            stopCamera();
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
};

const drawDutyWatermark = (ctx, w, h) => {
    ctx.save();
    const barHeight = 46;
    ctx.fillStyle = 'rgba(15, 23, 42, 0.78)';
    ctx.fillRect(0, h - barHeight, w, barHeight);

    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 12px sans-serif';
    const empName = activeDuty.value?.employee?.name || 'Karyawan';
    const loc = activeDuty.value?.destination_location || 'Tugas Luar';
    const timeStr = `${new Date().toLocaleDateString('id-ID')} ${new Date().toLocaleTimeString('id-ID')} WIB`;
    ctx.fillText(`${empName} • ${loc} • ${timeStr}`, 12, h - 26);

    ctx.fillStyle = '#38bdf8';
    ctx.font = '10px sans-serif';
    ctx.fillText('PENGGANTI ABSEN REGULER • RESMI OPERASIONAL', 12, h - 10);
    ctx.restore();
};

const retakeSnapshot = () => {
    capturedPhoto.value = null;
    attendanceForm.photo = '';
    startCamera();
};

const stopCamera = () => {
    if (mediaStream) {
        mediaStream.getTracks().forEach((t) => t.stop());
        mediaStream = null;
    }
};

const closeCameraModal = () => {
    stopCamera();
    isCameraModalOpen.value = false;
    capturedPhoto.value = null;
    cameraError.value = null;
};

const submitAttendance = () => {
    if (!activeDuty.value) return;
    const routeName = cameraActionType.value === 'in' ? 'field-duties.check-in' : 'field-duties.check-out';
    attendanceForm.post(route(routeName, activeDuty.value.id), {
        onSuccess: () => {
            closeCameraModal();
            Swal.fire({
                icon: 'success',
                title: cameraActionType.value === 'in' ? 'Check-In Berhasil!' : 'Check-Out Berhasil!',
                text: 'Absensi tugas luar berhasil dicatat dan disinkronkan.',
                timer: 2000,
                showConfirmButton: false,
            });
        },
        onError: (errs) => {
            const firstErr = Object.values(errs)[0] || 'Gagal menyimpan absensi tugas luar.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Absen',
                text: firstErr,
            });
        }
    });
};

// ========================================================
// MODAL 4: DETAIL & FOTO LIGHTBOX
// ========================================================
const isDetailModalOpen = ref(false);
const selectedDetailItem = ref(null);

const openDetailModal = (item) => {
    selectedDetailItem.value = item;
    isDetailModalOpen.value = true;
};

// ========================================================
// MODAL 5: IMPORT PENUGASAN LUAR DARI CSV
// ========================================================
const isImportModalOpen = ref(false);
const importFile = ref(null);
const csvFileInputRef = ref(null);
const importForm = useForm({
    file: null,
});

const openImportModal = () => {
    importFile.value = null;
    if (csvFileInputRef.value) csvFileInputRef.value.value = '';
    isImportModalOpen.value = true;
};

const handleCsvFileChange = (e) => {
    importFile.value = e.target.files?.[0] || null;
};

const downloadImportTemplate = () => {
    const csvHeader = 'Karyawan,Tanggal,Tipe,Lokasi Tujuan,Maksud,Mode Absen,Jam Mulai,Jam Selesai,Catatan\n';
    const sample1 = 'Super Admin,2026-10-19,reguler,Cilandak Kaum,Pengecekan ODP & Fiber,replace_both,08:30,17:00,Bawa Fusion Splicer\n';
    const sample2 = 'Herli Robiansyah,2026-10-19,non_reguler,Cigombong,Maintenance Insidental,replace_out,14:00,18:00,Instalasi baru\n';
    const blob = new Blob([csvHeader + sample1 + sample2], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'template_import_tugas_luar.csv';
    link.click();
    URL.revokeObjectURL(url);
};

const submitImport = () => {
    if (!importFile.value) return;
    importForm.file = importFile.value;
    importForm.post(route('field-duties.import'), {
        onSuccess: () => {
            isImportModalOpen.value = false;
            importFile.value = null;
            if (csvFileInputRef.value) csvFileInputRef.value.value = '';
            Swal.fire({
                icon: 'success',
                title: 'Import Selesai',
                text: 'Data penugasan luar berhasil diimpor.',
                timer: 2000,
                showConfirmButton: false,
            });
        },
        onError: (errs) => {
            const firstErr = Object.values(errs)[0] || 'Gagal mengimpor file CSV.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Import',
                text: firstErr,
            });
        }
    });
};

// Delete Single Duty
const deleteDuty = async (item) => {
    const confirmResult = await Swal.fire({
        title: 'Hapus Tugas Luar?',
        text: `Apakah Anda yakin ingin menghapus data tugas '${item.destination_location}' (${item.employee?.name})?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
    });

    if (!confirmResult.isConfirmed) return;

    router.delete(route('field-duties.destroy', item.id), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Terhapus',
                text: 'Data tugas luar berhasil dihapus.',
                timer: 1800,
                showConfirmButton: false,
            });
        }
    });
};

// Bulk Delete Duties
const bulkDeleteDuties = async () => {
    if (selectedIds.value.length === 0) return;

    const confirmResult = await Swal.fire({
        title: 'Hapus Masal Tugas Luar?',
        text: `Apakah Anda yakin ingin menghapus ${selectedIds.value.length} penugasan luar yang dipilih?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: `Ya, Hapus (${selectedIds.value.length})!`,
        cancelButtonText: 'Batal',
    });

    if (!confirmResult.isConfirmed) return;

    router.post(
        route('field-duties.bulk-delete'),
        { ids: selectedIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
                isAllSelected.value = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Terhapus',
                    text: 'Tugas luar terpilih berhasil dihapus.',
                    timer: 1800,
                    showConfirmButton: false,
                });
            },
        }
    );
};

onMounted(() => {
    fetchGeolocation();
});

onUnmounted(() => {
    stopCamera();
});
</script>
