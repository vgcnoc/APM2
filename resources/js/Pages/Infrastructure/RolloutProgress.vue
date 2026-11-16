<template>
    <AppLayout title="Progress Rollout Pembangunan ODC & ODP">
        <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- HEADER & FILTER TOOLBAR                                         -->
            <!-- ═══════════════════════════════════════════════════════════════ -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
                <div>
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-2xl bg-cyan-50 dark:bg-cyan-950/50 text-cyan-600 dark:text-cyan-400 flex items-center justify-center font-bold text-xl shadow-2xs">
                            ⚡
                        </div>
                        <div>
                            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">
                                Progress Rollout ODC & ODP
                            </h1>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Laporan Terpadu Penarikan Kabel, Splicing, Pemasangan ODC/ODP, & Uji Redaman OPM
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Filters: Date & Area -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Quick Date Shortcuts -->
                    <div class="hidden sm:flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl text-xs font-semibold">
                        <button
                            type="button"
                            @click="setQuickDate(-1)"
                            class="px-2.5 py-1 rounded-lg hover:bg-white dark:hover:bg-slate-900 hover:shadow-xs transition-all cursor-pointer text-slate-600 dark:text-slate-300"
                        >
                            Kemarin
                        </button>
                        <button
                            type="button"
                            @click="setQuickDate(0)"
                            class="px-2.5 py-1 rounded-lg transition-all cursor-pointer"
                            :class="filterDate === getLocalDateString(0) ? 'bg-cyan-600 text-white font-bold shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-900'"
                        >
                            Hari Ini
                        </button>
                        <button
                            type="button"
                            @click="setQuickDate(1)"
                            class="px-2.5 py-1 rounded-lg hover:bg-white dark:hover:bg-slate-900 hover:shadow-xs transition-all cursor-pointer text-slate-600 dark:text-slate-300"
                        >
                            Besok
                        </button>
                    </div>

                    <!-- Date Input -->
                    <div class="relative">
                        <input
                            type="date"
                            v-model="filterDate"
                            @change="applyFilter"
                            class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-cyan-500 cursor-pointer"
                        />
                    </div>

                    <!-- Area Filter (Scoped / Global) -->
                    <div class="relative" v-if="areas.length > 1">
                        <select
                            v-model="filterArea"
                            @change="applyFilter"
                            class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-cyan-500 cursor-pointer"
                        >
                            <option value="" v-if="canViewAllAreas">Semua Wilayah / Area</option>
                            <option v-for="ar in areas" :key="ar.id" :value="ar.id">
                                {{ ar.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- STATS KPI SUMMARY CARDS (6 KOLOM)                               -->
            <!-- ═══════════════════════════════════════════════════════════════ -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                
                <!-- 1. Total Rollout -->
                <div class="p-4 rounded-2xl border bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 shadow-2xs">
                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                        <span class="text-[11px] font-black uppercase tracking-wider">⚡ Total WO Rollout</span>
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800">{{ summary.total || 0 }}</span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ summary.total || 0 }}
                    </div>
                    <div class="flex items-center gap-1.5 text-[10px] text-slate-500 dark:text-slate-400 mt-1">
                        <span class="font-bold text-cyan-600">{{ summary.total_odp || 0 }} ODP</span>
                        <span>·</span>
                        <span class="font-bold text-purple-600">{{ summary.total_odc || 0 }} ODC</span>
                    </div>
                </div>

                <!-- 2. Selesai & Aktif GIS -->
                <div class="p-4 rounded-2xl border bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 shadow-2xs">
                    <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400">
                        <span class="text-[11px] font-black uppercase tracking-wider">✅ Selesai di GIS</span>
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-900/60">{{ summary.completed || 0 }}</span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ summary.completed || 0 }}
                    </div>
                    <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold mt-1">
                        Aktif dan terintegrasi
                    </div>
                </div>

                <!-- 3. Review QC NOC -->
                <div class="p-4 rounded-2xl border bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 shadow-2xs">
                    <div class="flex items-center justify-between text-purple-600 dark:text-purple-400">
                        <span class="text-[11px] font-black uppercase tracking-wider">🔍 Review QC NOC</span>
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded-md bg-purple-100 dark:bg-purple-900/60">{{ summary.under_review || 0 }}</span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ summary.under_review || 0 }}
                    </div>
                    <div class="text-[10px] text-purple-600 dark:text-purple-400 font-bold mt-1">
                        Menunggu verifikasi NOC
                    </div>
                </div>

                <!-- 4. Sedang Dikerjakan -->
                <div class="p-4 rounded-2xl border bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 shadow-2xs">
                    <div class="flex items-center justify-between text-blue-600 dark:text-blue-400">
                        <span class="text-[11px] font-black uppercase tracking-wider">🛠️ Dikerjakan</span>
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded-md bg-blue-100 dark:bg-blue-900/60">{{ summary.in_progress || 0 }}</span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ summary.in_progress || 0 }}
                    </div>
                    <div class="text-[10px] text-blue-600 dark:text-blue-400 font-bold mt-1">
                        Sedang proses fisik
                    </div>
                </div>

                <!-- 5. Material Siap -->
                <div class="p-4 rounded-2xl border bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 shadow-2xs">
                    <div class="flex items-center justify-between text-sky-600 dark:text-sky-400">
                        <span class="text-[11px] font-black uppercase tracking-wider">📦 Material Siap</span>
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded-md bg-sky-100 dark:bg-sky-900/60">{{ summary.material_ready || 0 }}</span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ summary.material_ready || 0 }}
                    </div>
                    <div class="text-[10px] text-sky-600 dark:text-sky-400 font-bold mt-1">
                        Siap dibawa tim lapangan
                    </div>
                </div>

                <!-- 6. Menunggu Material -->
                <div class="p-4 rounded-2xl border bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 shadow-2xs">
                    <div class="flex items-center justify-between text-amber-600 dark:text-amber-400">
                        <span class="text-[11px] font-black uppercase tracking-wider">⏳ Tunggu Material</span>
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded-md bg-amber-100 dark:bg-amber-900/60">{{ summary.pending_material || 0 }}</span>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ summary.pending_material || 0 }}
                    </div>
                    <div class="text-[10px] text-amber-600 dark:text-amber-400 font-bold mt-1">
                        Menunggu alokasi gudang
                    </div>
                </div>

            </div>

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- MAIN TWO COLUMNS LAYOUT                                         -->
            <!-- ═══════════════════════════════════════════════════════════════ -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- LEFT COLUMN: DETAIL DATA ROLLOUT (7 COLS) -->
                <div class="lg:col-span-7 space-y-4">
                    
                    <!-- Sub-filter & Quick Action Header -->
                    <div class="flex items-center justify-between gap-2 pb-1 flex-wrap">
                        <div class="flex items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl text-xs font-semibold">
                            <button
                                type="button"
                                @click="filterOnlyMyRollout = false"
                                class="px-3 py-1.5 rounded-lg transition-all cursor-pointer"
                                :class="!filterOnlyMyRollout ? 'bg-white dark:bg-slate-900 text-cyan-700 dark:text-cyan-400 font-bold shadow-xs' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'"
                            >
                                👥 Semua Petugas ({{ rolloutList.length }})
                            </button>
                            <button
                                type="button"
                                @click="filterOnlyMyRollout = true"
                                class="px-3 py-1.5 rounded-lg transition-all cursor-pointer flex items-center gap-1"
                                :class="filterOnlyMyRollout ? 'bg-cyan-600 text-white font-bold shadow-xs' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'"
                            >
                                <span>👤 Tugas Saya</span>
                                <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="filterOnlyMyRollout ? 'bg-white/20' : 'bg-slate-200 dark:bg-slate-700'">
                                    {{ myRolloutList.length }}
                                </span>
                            </button>
                        </div>

                        <div class="flex items-center gap-2">
                            <a
                                :href="route('infrastructure.work-orders.create')"
                                class="px-3 py-1.5 text-xs font-bold rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white shadow-xs transition-all cursor-pointer inline-flex items-center gap-1"
                            >
                                <span>+</span>
                                <span>WO Baru</span>
                            </a>
                            <a
                                :href="route('infrastructure.work-orders.index')"
                                class="px-3 py-1.5 text-xs font-bold rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-all cursor-pointer inline-flex items-center gap-1"
                            >
                                <span>📋</span>
                                <span>Tabel Work Orders ↗</span>
                            </a>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="filteredRolloutList.length === 0" class="bg-white dark:bg-slate-900 rounded-3xl p-10 text-center border border-slate-200/80 dark:border-slate-800">
                        <div class="w-16 h-16 mx-auto rounded-2xl bg-cyan-50 dark:bg-cyan-950/50 text-cyan-600 dark:text-cyan-400 flex items-center justify-center text-3xl mb-3">
                            ⚡
                        </div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">
                            {{ filterOnlyMyRollout ? 'Tidak Ada Penugasan Rollout yang Ditugaskan ke Anda' : 'Nihil Pembangunan Rollout ODC & ODP Hari Ini' }}
                        </h3>
                        <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto">
                            {{ filterOnlyMyRollout ? 'Anda belum memiliki work order penarikan kabel, pembangunan ODC atau ODP pada tanggal ini.' : 'Belum ada tiket work order rollout ODC atau ODP yang dijadwalkan / aktif pada tanggal dan wilayah yang dipilih.' }}
                        </p>
                        <div class="mt-4" v-if="!filterOnlyMyRollout">
                            <a
                                :href="route('infrastructure.work-orders.create')"
                                class="px-4 py-2 text-xs font-bold rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white shadow-md shadow-cyan-600/20 transition-all cursor-pointer inline-flex items-center gap-1.5"
                            >
                                <span>+</span>
                                <span>Buat Work Order Rollout Baru</span>
                            </a>
                        </div>
                    </div>

                    <!-- Rollout Cards List -->
                    <div 
                        v-for="(item, idx) in filteredRolloutList" 
                        :key="'rollout-' + item.id"
                        class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 hover:shadow-md transition-all space-y-3"
                    >
                        <!-- Card Header -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-cyan-100 dark:bg-cyan-950/70 text-cyan-700 dark:text-cyan-300 text-xs font-black flex items-center justify-center shrink-0">
                                    {{ idx + 1 }}
                                </span>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 flex-wrap">
                                        <span>{{ item.target_name }}</span>
                                        <span 
                                            class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider border shadow-2xs"
                                            :class="item.device_type === 'ODC' ? 'bg-purple-100 text-purple-800 dark:bg-purple-950/80 dark:text-purple-300 border-purple-300' : 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950/80 dark:text-cyan-300 border-cyan-300'"
                                        >
                                            {{ item.device_type === 'ODC' ? '🗄️ ODC Master' : '📦 ODP Distribusi' }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                            {{ item.type_label }}
                                        </span>
                                    </h4>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2 mt-0.5 flex-wrap">
                                        <span class="font-mono font-bold text-slate-700 dark:text-slate-300">{{ item.wo_number }}</span>
                                        <span class="text-slate-300">·</span>
                                        <span class="font-semibold text-slate-700 dark:text-slate-300">
                                            👷 {{ item.technicians }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <span 
                                class="px-2.5 py-1 rounded-full text-[11px] font-bold inline-flex items-center gap-1.5 shrink-0 border"
                                :class="getRolloutStatusBadgeClass(item.status)"
                            >
                                {{ item.status_label }}
                            </span>
                        </div>

                        <!-- Specs & Detail Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl">
                            <div>
                                <span class="text-slate-400 text-[10px] font-bold block uppercase">Wilayah & Lokasi Pemasangan</span>
                                <span class="text-slate-700 dark:text-slate-300 font-semibold">{{ item.location || '-' }}</span>
                                <span v-if="item.area_name && item.area_name !== '-'" class="text-[10px] text-slate-400 block">({{ item.area_name }})</span>
                                <div v-if="item.actual_latitude || item.planned_latitude" class="mt-0.5">
                                    <a 
                                        :href="'https://www.google.com/maps?q=' + (item.actual_latitude || item.planned_latitude) + ',' + (item.actual_longitude || item.planned_longitude)" 
                                        target="_blank" 
                                        class="inline-flex items-center gap-1 text-[11px] text-blue-600 dark:text-blue-400 hover:underline font-mono"
                                    >
                                        📍 {{ Number(item.actual_latitude || item.planned_latitude).toFixed(5) }}, {{ Number(item.actual_longitude || item.planned_longitude).toFixed(5) }} ↗
                                    </a>
                                </div>
                            </div>

                            <div>
                                <span class="text-slate-400 text-[10px] font-bold block uppercase">Uplink & OLT Server</span>
                                <div class="space-y-0.5">
                                    <div v-if="item.olt_name" class="text-slate-700 dark:text-slate-300 font-medium">
                                        🖥️ OLT: <span class="font-bold">{{ item.olt_name }}</span> (PON: {{ item.pon_port || '-' }})
                                    </div>
                                    <div v-if="item.parent_odc" class="text-purple-600 dark:text-purple-400 font-medium">
                                        🗄️ Parent ODC: <span class="font-bold">{{ item.parent_odc }}</span> (Port Splitter: {{ item.odc_splitter_port || '-' }})
                                    </div>
                                    <div v-if="!item.olt_name && !item.parent_odc" class="text-slate-400">-</div>
                                </div>
                            </div>

                            <div>
                                <span class="text-slate-400 text-[10px] font-bold block uppercase">Kapasitas & Konfigurasi Core</span>
                                <span class="text-slate-700 dark:text-slate-300 font-mono font-semibold">
                                    Port: <strong class="text-cyan-600 dark:text-cyan-400">{{ item.capacity || 0 }} Port</strong>
                                    <span v-if="item.core_capacity"> · {{ item.core_capacity }} Core</span>
                                    <span v-if="item.splitter_ratio"> · Ratio {{ item.splitter_ratio }}</span>
                                </span>
                            </div>

                            <div>
                                <span class="text-slate-400 text-[10px] font-bold block uppercase">Penarikan Kabel Fiber Optik</span>
                                <div v-if="item.cable_length_used || item.cable_pull" class="text-slate-700 dark:text-slate-300 font-medium">
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400">📏 {{ item.cable_length_used || item.cable_pull }} Meter</span>
                                    <span v-if="item.cable_meter_start || item.cable_meter_end" class="text-[10px] text-slate-400 block font-mono">
                                        (Start: {{ item.cable_meter_start || 0 }}m - End: {{ item.cable_meter_end || 0 }}m)
                                    </span>
                                </div>
                                <div v-else class="text-slate-400">-</div>
                                <div v-if="item.start_point || item.end_point" class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                                    Rute: {{ item.start_point || '-' }} ➔ {{ item.end_point || '-' }}
                                </div>
                            </div>

                            <!-- Pengukuran Redaman OPM -->
                            <div v-if="item.optical_checks && item.optical_checks.length > 0" class="sm:col-span-2 bg-emerald-50/60 dark:bg-emerald-950/30 p-2.5 rounded-xl border border-emerald-200/80 dark:border-emerald-900/50 space-y-1.5">
                                <span class="text-emerald-800 dark:text-emerald-300 text-[10px] font-black uppercase tracking-wider flex items-center gap-1">
                                    <span>⚡</span>
                                    <span>Hasil Pengukuran Redaman OPM ({{ item.optical_checks.length }} Port)</span>
                                </span>
                                <div class="flex flex-wrap gap-1.5">
                                    <span 
                                        v-for="oc in item.optical_checks" 
                                        :key="'oc-' + oc.id"
                                        class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border"
                                        :class="oc.passed ? 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-900/60 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-900/60 dark:text-rose-300'"
                                    >
                                        Port {{ oc.port_number }}: {{ oc.measured_loss ? oc.measured_loss + ' dBm' : '-' }} {{ oc.passed ? '✓' : '✗' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Material Rollout yang Digunakan -->
                            <div v-if="item.materials && item.materials.length > 0" class="sm:col-span-2 bg-blue-50/50 dark:bg-blue-950/30 p-2.5 rounded-xl border border-blue-200/80 dark:border-blue-900/50 space-y-1.5">
                                <span class="text-blue-800 dark:text-blue-300 text-[10px] font-black uppercase tracking-wider flex items-center gap-1">
                                    <span>📦</span>
                                    <span>Material Rollout Terpakai</span>
                                </span>
                                <div class="flex flex-wrap gap-1.5">
                                    <span 
                                        v-for="mat in item.materials" 
                                        :key="'mat-' + mat.id"
                                        class="px-2 py-0.5 rounded text-[10px] bg-white dark:bg-slate-900 border border-blue-200 dark:border-blue-800 text-slate-700 dark:text-slate-300 font-medium"
                                    >
                                        <strong>{{ formatMatQty(mat) }} {{ mat.unit }}</strong> {{ mat.name }}
                                    </span>
                                </div>
                            </div>

                            <!-- Catatan / Instruksi Lapangan -->
                            <div v-if="item.notes" class="sm:col-span-2">
                                <span class="text-slate-400 text-[10px] font-bold block uppercase">Catatan / Instruksi Kerja</span>
                                <span class="text-slate-700 dark:text-slate-300 italic">{{ item.notes }}</span>
                            </div>
                        </div>

                        <!-- Footer Actions: Foto & Direct Link WO -->
                        <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-100 dark:border-slate-800 flex-wrap">
                            <div class="flex items-center gap-2">
                                <button
                                    v-if="item.photo_pole || item.photo_splicing || item.photo_opm || item.photo_odp"
                                    type="button"
                                    @click="viewRolloutPhotos(item)"
                                    class="px-2.5 py-1 text-[11px] font-bold text-cyan-700 dark:text-cyan-300 bg-cyan-50 dark:bg-cyan-950/50 hover:bg-cyan-100 dark:hover:bg-cyan-900/60 rounded-lg transition-colors flex items-center gap-1 cursor-pointer border border-cyan-200 dark:border-cyan-800"
                                >
                                    <span>📷</span>
                                    <span>Lihat Foto Dokumentasi</span>
                                </button>
                                <span v-else class="text-[11px] text-slate-400 italic">
                                    Belum ada foto dokumentasi diunggah
                                </span>
                            </div>

                            <a
                                :href="route('infrastructure.work-orders.index', { search: item.wo_number })"
                                class="px-3 py-1 text-[11px] font-bold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors flex items-center gap-1 cursor-pointer"
                                title="Buka rincian lengkap work order pembangunan"
                            >
                                <span>Buka Rincian Work Order ↗</span>
                            </a>
                        </div>
                    </div>

                </div>

                <!-- RIGHT COLUMN: FORMAT BROADCAST WHATSAPP (5 COLS) -->
                <div class="lg:col-span-5 space-y-4 lg:sticky lg:top-6">
                    <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
                        
                        <!-- Header Preview -->
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-sm">
                                    📱
                                </div>
                                <div>
                                    <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider">
                                        Format Broadcast WhatsApp
                                    </h3>
                                    <p class="text-[10px] text-slate-400">Siap kirim ke grup proyek rollout & NOC</p>
                                </div>
                            </div>
                            <span 
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold flex items-center gap-1"
                                :class="waGatewayStatus ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="waGatewayStatus ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'"></span>
                                {{ waGatewayStatus ? 'GW CONNECTED' : 'GW STANDBY' }}
                            </span>
                        </div>

                        <!-- Custom Note Input -->
                        <div class="space-y-1">
                            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                📝 Custom Note / Pesan Semangat:
                            </label>
                            <input
                                type="text"
                                v-model="customNote"
                                placeholder="Contoh: Tetap utamakan keselamatan kerja & standardisasi pemasangan! ⚡"
                                class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-cyan-500"
                            />
                        </div>

                        <!-- WhatsApp Text Box Preview -->
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                <span>Preview Pesan WhatsApp</span>
                                <span>{{ activeWaText.length }} karakter</span>
                            </div>
                            <div class="relative group">
                                <textarea
                                    readonly
                                    rows="14"
                                    :value="activeWaText"
                                    class="w-full p-3.5 text-xs font-mono leading-relaxed rounded-2xl bg-slate-950 text-emerald-400 border border-slate-800 focus:outline-none scrollbar-thin select-all"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="space-y-2 pt-1">
                            <!-- 1-Click Copy -->
                            <button
                                type="button"
                                @click="copyWaText"
                                class="w-full py-2.5 px-4 text-xs font-bold rounded-xl text-white transition-all flex items-center justify-center gap-2 shadow-md cursor-pointer"
                                :class="copied ? 'bg-emerald-600' : 'bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100'"
                            >
                                <span>{{ copied ? '✅ Format Berhasil Disalin!' : '📋 Salin Format WA (1-Click Copy)' }}</span>
                            </button>

                            <!-- Open WA Web -->
                            <a
                                :href="'https://web.whatsapp.com/send?text=' + encodeURIComponent(activeWaText)"
                                target="_blank"
                                class="w-full py-2 px-4 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all flex items-center justify-center gap-1.5 cursor-pointer text-center block"
                            >
                                <span>💬 Buka Langsung di WhatsApp Web ↗</span>
                            </a>

                            <!-- Send via Gateway -->
                            <button
                                type="button"
                                @click="openSendModal"
                                class="w-full py-2 px-4 text-xs font-bold rounded-xl text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/80 border border-emerald-200 dark:border-emerald-800 transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <span>🚀 Kirim Otomatis via WhatsApp Gateway</span>
                            </button>
                        </div>

                    </div>
                </div>

            </div>

        </div>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- MODAL: PREVIEW FOTO DOKUMENTASI                                 -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <div 
            v-if="showPhotoModal && selectedPhotoItem" 
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-xs p-4"
            @click.self="showPhotoModal = false"
        >
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4 animate-in fade-in zoom-in-95 duration-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        {{ selectedPhotoItem.title || 'Foto Dokumentasi Rollout Lapangan' }}
                    </h3>
                    <button @click="showPhotoModal = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 cursor-pointer">✕</button>
                </div>

                <div v-if="selectedPhotoItem.rollout_photos && selectedPhotoItem.rollout_photos.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[70vh] overflow-y-auto pr-1">
                    <div v-for="(p, pIdx) in selectedPhotoItem.rollout_photos" :key="'rp-' + pIdx" class="space-y-1.5 text-center">
                        <span class="text-xs font-bold text-cyan-600 dark:text-cyan-400 uppercase tracking-wider block">{{ p.title }}</span>
                        <a :href="p.url" target="_blank" class="block group relative overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm bg-slate-100 dark:bg-slate-800">
                            <img :src="p.url" :alt="p.title" class="w-full h-48 object-cover group-hover:scale-105 transition-all duration-200" />
                            <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs font-bold transition-opacity">
                                🔍 Lihat Ukuran Penuh ↗
                            </div>
                        </a>
                    </div>
                </div>

                <div class="text-right pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button 
                        @click="showPhotoModal = false"
                        class="px-4 py-2 text-xs font-bold bg-slate-900 text-white dark:bg-white dark:text-slate-900 rounded-xl cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- MODAL: KIRIM OTOMATIS VIA GATEWAY WA                            -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <div 
            v-if="showSendModal" 
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4"
            @click.self="showSendModal = false"
        >
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4 animate-in fade-in zoom-in-95 duration-200">
                
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-emerald-500 text-white flex items-center justify-center font-bold text-lg">
                            🚀
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Kirim Laporan Rollout ke WA</h3>
                            <p class="text-[11px] text-slate-400">Kirim langsung via WhatsApp Gateway Server</p>
                        </div>
                    </div>
                    <button @click="showSendModal = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 cursor-pointer">✕</button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Nomor WhatsApp Tujuan atau JID Grup:
                        </label>
                        <input
                            type="text"
                            v-model="targetPhone"
                            placeholder="Contoh: 08123456789 atau 1203630xxx@g.us"
                            class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        />
                        <p class="text-[10px] text-slate-400 mt-1">Bisa nomor koordinator rollout atau ID grup WhatsApp proyek.</p>
                    </div>

                    <!-- Preset Target Suggestions -->
                    <div class="space-y-1">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Pintasan Target Cepat:</span>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                type="button"
                                @click="targetPhone = '120363024823901234@g.us'"
                                class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 cursor-pointer"
                            >
                                ⚡ Grup Rollout / Proyek
                            </button>
                            <button
                                type="button"
                                @click="targetPhone = '120363024823909999@g.us'"
                                class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 cursor-pointer"
                            >
                                🛡️ Grup NOC & FTTH
                            </button>
                        </div>
                    </div>

                    <!-- Gateway Status Warning -->
                    <div v-if="!waGatewayStatus" class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-900/60 text-xs text-amber-800 dark:text-amber-300">
                        ⚠️ <strong>Peringatan:</strong> Layanan Gateway WhatsApp terdeteksi sedang offline atau belum terhubung QR. Anda tetap bisa menggunakan opsi <strong>Salin Format WA</strong> atau <strong>Buka di WhatsApp Web</strong>.
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button
                        type="button"
                        @click="showSendModal = false"
                        class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="executeSendWa"
                        :disabled="isSending || !targetPhone"
                        class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 rounded-xl transition-all flex items-center gap-2 shadow-md shadow-emerald-600/20 cursor-pointer"
                    >
                        <svg v-if="isSending" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>{{ isSending ? 'Mengirim...' : 'Kirim Sekarang 🚀' }}</span>
                    </button>
                </div>

            </div>
        </div>

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const page = usePage();

const props = defineProps({
    filters: { type: Object, default: () => ({}) },
    areas: { type: Array, default: () => [] },
    indonesianDate: { type: String, default: '' },
    areaLabel: { type: String, default: '' },
    rolloutList: { type: Array, default: () => [] },
    activeTechNames: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
    waText: { type: String, default: '' },
    waGatewayStatus: { type: Boolean, default: false },
    canViewAllAreas: { type: Boolean, default: true },
    isAreaScoped: { type: Boolean, default: false },
});

// Helper Local Date (WIB / Browser Local timezone, bebas dari bug UTC)
function getLocalDateString(offsetDays = 0) {
    const d = new Date();
    d.setDate(d.getDate() + offsetDays);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Filters State
const filterDate = ref(props.filters?.date || getLocalDateString(0));
const filterArea = ref(props.filters?.area_id || (!props.canViewAllAreas && props.areas?.[0]?.id ? props.areas[0].id : ''));
const customNote = ref(props.filters?.note || 'Tetap utamakan keselamatan kerja & standardisasi pemasangan! ⚡');

// Filter Tugas Saya vs Semua Petugas
const filterOnlyMyRollout = ref(false);
const currentUserId = computed(() => page.props.auth?.user?.id);
const currentUserName = computed(() => (page.props.auth?.user?.name || '').toLowerCase());

const myRolloutList = computed(() => {
    return (props.rolloutList || []).filter(item => {
        if (item.technician_ids && Array.isArray(item.technician_ids) && item.technician_ids.includes(currentUserId.value)) {
            return true;
        }
        if (item.technicians && currentUserName.value && item.technicians.toLowerCase().includes(currentUserName.value)) {
            return true;
        }
        return false;
    });
});

const filteredRolloutList = computed(() => {
    if (filterOnlyMyRollout.value) {
        return myRolloutList.value;
    }
    return props.rolloutList || [];
});

// Dynamic WhatsApp message text calculation
const activeWaText = computed(() => {
    const baseText = props.waText || '';
    if (!baseText) return '';
    
    const noteMarker = '📝 *NOTE*';
    const markerIndex = baseText.indexOf(noteMarker);
    if (markerIndex !== -1) {
        return baseText.substring(0, markerIndex) + noteMarker + '\n' + (customNote.value || 'Tetap utamakan keselamatan kerja & standardisasi pemasangan! ⚡');
    }
    return baseText + '\n\n📝 *NOTE*\n' + (customNote.value || 'Tetap utamakan keselamatan kerja & standardisasi pemasangan! ⚡');
});

// Quick Date Change
function setQuickDate(offsetDays) {
    filterDate.value = getLocalDateString(offsetDays);
    applyFilter();
}

function applyFilter() {
    router.get(route('infrastructure.rollout-progress.index'), {
        date: filterDate.value,
        area_id: filterArea.value || null,
        note: customNote.value || null,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

// 1-Click Copy
const copied = ref(false);
function copyWaText() {
    if (!activeWaText.value) return;
    navigator.clipboard.writeText(activeWaText.value).then(() => {
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2500);
    }).catch(err => {
        console.error('Gagal menyalin:', err);
        const ta = document.createElement('textarea');
        ta.value = activeWaText.value;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2500);
    });
}

// MODAL FOTO DOKUMENTASI
const showPhotoModal = ref(false);
const selectedPhotoItem = ref(null);

function viewRolloutPhotos(item) {
    const photos = [];
    if (item.photo_pole) photos.push({ title: 'Foto Tiang & Penarikan Kabel', url: item.photo_pole });
    if (item.photo_splicing) photos.push({ title: 'Foto Splicing Core Tray', url: item.photo_splicing });
    if (item.photo_opm) photos.push({ title: 'Foto Pengukuran OPM / Redaman', url: item.photo_opm });
    if (item.photo_odp) photos.push({ title: `Foto Box & Label ${item.device_type || 'ODP'}`, url: item.photo_odp });

    selectedPhotoItem.value = {
        title: `Dokumentasi ${item.device_type || 'ODP'} - ${item.target_name} (${item.wo_number})`,
        rollout_photos: photos,
    };
    showPhotoModal.value = true;
}

function getRolloutStatusBadgeClass(status) {
    switch (status) {
        case 'completed':
        case 'approved':
        case 'commissioned':
            return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800';
        case 'in_progress':
            return 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800 animate-pulse';
        case 'under_review':
            return 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-800 font-bold';
        case 'material_ready':
            return 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-800';
        case 'pending_material':
            return 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800';
        case 'rejected':
            return 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200 dark:border-rose-800';
        default:
            return 'bg-slate-50 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700';
    }
}

function formatMatQty(mat) {
    if (!mat) return '0';
    const val = (mat.used_qty > 0) ? mat.used_qty : ((mat.requested_qty > 0) ? mat.requested_qty : (mat.quantity || mat.quantity_used || mat.quantity_requested || 0));
    const num = Number(val);
    if (isNaN(num)) return val;
    return Number.isInteger(num) ? num : parseFloat(num.toFixed(2));
}

// MODAL WHATSAPP GATEWAY
const showSendModal = ref(false);
const targetPhone = ref('');
const isSending = ref(false);

function openSendModal() {
    showSendModal.value = true;
}

async function executeSendWa() {
    if (!targetPhone.value) {
        alert('Silakan masukkan nomor tujuan atau ID Grup WhatsApp terlebih dahulu.');
        return;
    }
    isSending.value = true;
    try {
        const response = await axios.post(route('infrastructure.rollout-progress.send-wa'), {
            phone: targetPhone.value,
            message: activeWaText.value,
        });

        if (response.data?.success) {
            alert('✅ ' + (response.data?.message || 'Laporan progress rollout berhasil dikirim!'));
            showSendModal.value = false;
        } else {
            alert('⚠️ ' + (response.data?.message || 'Gagal mengirim pesan WhatsApp.'));
        }
    } catch (err) {
        console.error('Gagal mengirim WhatsApp:', err);
        const errMsg = err.response?.data?.message || err.message || 'Terjadi kesalahan sistem saat mengirim via WA Gateway.';
        alert('❌ Error: ' + errMsg);
    } finally {
        isSending.value = false;
    }
}
</script>

