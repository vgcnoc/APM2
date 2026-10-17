<template>
    <AppLayout title="Data Migration Center">
        <div class="py-8 bg-slate-50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                
                <!-- HEADER & STATUS BAR -->
                <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl shadow-xl p-6 lg:p-8 text-white relative overflow-hidden border border-indigo-900/50">
                    <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute -left-10 -top-10 w-64 h-64 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div>
                            <div class="flex items-center gap-3">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/30 text-indigo-300 border border-indigo-500/40 tracking-wide uppercase">
                                    Enterprise Data Center
                                </span>
                                <span class="flex items-center gap-1.5 text-xs text-emerald-400 font-medium">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    Sistem Siap Migrasi
                                </span>
                            </div>
                            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight mt-2 text-white">
                                ⚡ Data Migration Center
                            </h1>
                            <p class="text-sm sm:text-base text-slate-300 mt-1 max-w-3xl leading-relaxed">
                                Pusat migrasi data massal terintegrasi (Excel & CSV) untuk aplikasi billing ISP. Dilengkapi simulasi 
                                <span class="text-indigo-300 font-semibold">Dry-Run</span>, verifikasi integritas database, dan pelindung 
                                <span class="text-indigo-300 font-semibold">Safe Bypass</span> untuk mencegah efek samping pada operasional berjalan.
                            </p>
                        </div>

                        <!-- System Reference Chips -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                            <div class="bg-slate-800/80 backdrop-blur rounded-xl p-3 border border-slate-700/60">
                                <span class="text-slate-400 block text-[11px]">Master Area</span>
                                <span class="text-base font-bold text-indigo-400">{{ references?.areas?.length || 0 }}</span>
                                <span class="text-slate-500 ml-1">wilayah</span>
                            </div>
                            <div class="bg-slate-800/80 backdrop-blur rounded-xl p-3 border border-slate-700/60">
                                <span class="text-slate-400 block text-[11px]">Paket Aktif</span>
                                <span class="text-base font-bold text-cyan-400">{{ references?.packages?.length || 0 }}</span>
                                <span class="text-slate-500 ml-1">layanan</span>
                            </div>
                            <div class="bg-slate-800/80 backdrop-blur rounded-xl p-3 border border-slate-700/60">
                                <span class="text-slate-400 block text-[11px]">Perangkat FTTH</span>
                                <span class="text-base font-bold text-emerald-400">
                                    {{ references?.olts?.length || 0 }} OLT / {{ references?.odps?.length || 0 }} ODP
                                </span>
                            </div>
                            <div class="bg-slate-800/80 backdrop-blur rounded-xl p-3 border border-slate-700/60">
                                <span class="text-slate-400 block text-[11px]">Karyawan & SDM</span>
                                <span class="text-base font-bold text-amber-400">{{ references?.employees_count || 0 }}</span>
                                <span class="text-slate-500 ml-1">orang</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CATEGORY SELECTION TABS -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-2 sm:p-3">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-2">
                        <button
                            v-for="(cat, key) in categories"
                            :key="key"
                            @click="selectCategory(key)"
                            :class="[
                                'relative flex flex-col items-center text-center p-3 sm:p-4 rounded-xl transition-all duration-200 text-xs sm:text-sm font-semibold',
                                selectedCategoryKey === key
                                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/25 ring-2 ring-indigo-600'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                            ]"
                        >
                            <span v-if="key === 'customers'" class="absolute -top-1.5 -right-1 px-1.5 py-0.5 rounded-full text-[9px] font-black bg-amber-400 text-amber-950 shadow-sm animate-pulse z-10">
                                MASTER 6-IN-1
                            </span>
                            <!-- Tab Icon -->
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center mb-2"
                                :class="selectedCategoryKey === key ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'">
                                <svg v-if="key === 'customers'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <svg v-else-if="key === 'network'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                <svg v-else-if="key === 'booking_survey'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                </svg>
                                <svg v-else-if="key === 'installation_activation'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                <svg v-else-if="key === 'invoices'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <svg v-else-if="key === 'payments'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <svg v-else-if="key === 'employees'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                </svg>
                            </div>

                            <span class="truncate max-w-full">{{ cat.title }}</span>
                            <span class="text-[10px] opacity-80 mt-0.5 font-normal">
                                {{ Object.keys(cat.headers).length }} Kolom
                            </span>
                        </button>
                    </div>
                </div>

                <!-- MAIN WORKFLOW CARD -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                    
                    <!-- CATEGORY BANNER & TEMPLATE DOWNLOAD -->
                    <div class="p-6 border-b border-slate-100 bg-gradient-to-r from-slate-50 via-white to-indigo-50/30 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-indigo-100 text-indigo-700 uppercase">
                                    Modul {{ currentCategory.title }}
                                </span>
                                <span v-if="selectedCategoryKey === 'customers'" class="px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300 flex items-center gap-1">
                                    ⭐ MASTER TERLENGKAP (Booking s/d Aktivasi Sekaligus)
                                </span>
                                <span class="text-xs text-slate-400">Tabel: <code class="text-indigo-600 bg-indigo-50 px-1 py-0.5 rounded">{{ currentCategory.target_table }}</code></span>
                            </div>
                            <h2 class="text-lg sm:text-xl font-bold text-slate-800 mt-1">
                                {{ currentCategory.title }}
                            </h2>
                            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                                {{ currentCategory.description }}
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <!-- Toggle Guide Modal -->
                            <button
                                @click="showGuideModal = true"
                                type="button"
                                class="px-3.5 py-2 text-xs font-medium text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-xl transition flex items-center gap-2 shadow-sm"
                            >
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Panduan Kolom
                            </button>

                            <!-- Download Template Excel Button -->
                            <button
                                @click="downloadExcelTemplate"
                                type="button"
                                class="px-3.5 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition flex items-center gap-2 shadow-sm shadow-emerald-600/20"
                                title="Unduh berkas Microsoft Excel (.xlsx) dengan kolom yang otomatis terpisah rapi"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Unduh Template Excel (.xlsx)
                            </button>

                            <!-- Download Template CSV Button -->
                            <a
                                :href="`/settings/data-migration/template/${selectedCategoryKey}`"
                                target="_blank"
                                class="px-3 py-2 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-xl transition flex items-center gap-1.5 shadow-sm"
                                title="Unduh berkas format teks CSV"
                            >
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Unduh CSV (.csv)
                            </a>
                        </div>
                    </div>

                    <div class="p-6 space-y-8">

                        <!-- STEP 1: FILE INGESTION (DRAG & DROP) -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <label class="text-xs sm:text-sm font-bold text-slate-800 flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-xs flex items-center justify-center font-bold">1</span>
                                    Unggah Berkas Migrasi (.xlsx, .xls, .csv)
                                </label>
                                <span v-if="uploadedFile" class="text-xs text-slate-500">
                                    Ukuran: {{ (uploadedFile.size / 1024).toFixed(1) }} KB
                                </span>
                            </div>

                            <!-- Dropzone Container -->
                            <div
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="handleFileDrop"
                                :class="[
                                    'border-2 border-dashed rounded-2xl p-6 sm:p-8 text-center transition-all cursor-pointer',
                                    isDragging ? 'border-indigo-500 bg-indigo-50/50 scale-[0.99]' : 'border-slate-300 hover:border-indigo-400 bg-slate-50/60 hover:bg-slate-50'
                                ]"
                                @click="triggerFileInput"
                            >
                                <input
                                    ref="fileInputRef"
                                    type="file"
                                    accept=".csv, .xlsx, .xls, .txt, text/csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                                    class="hidden"
                                    @change="handleFileInput"
                                />

                                <div v-if="!uploadedFile && !isParsing" class="flex flex-col items-center justify-center space-y-3">
                                    <div class="w-12 h-12 rounded-2xl bg-indigo-100/80 text-indigo-600 flex items-center justify-center shadow-inner">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-700">
                                            Tarik & lepas file Excel/CSV di sini, atau <span class="text-indigo-600 underline">pilih dari komputer</span>
                                        </p>
                                        <p class="text-xs text-slate-400 mt-1">
                                            Mendukung Microsoft Excel (.xlsx, .xls) dan CSV ber-BOM UTF-8 (Maksimal 15 MB)
                                        </p>
                                    </div>
                                </div>

                                <div v-else-if="isParsing" class="flex flex-col items-center justify-center py-4 space-y-3">
                                    <div class="animate-spin w-8 h-8 border-3 border-indigo-600 border-t-transparent rounded-full"></div>
                                    <p class="text-xs sm:text-sm font-medium text-slate-600">
                                        Membedah dan memvalidasi struktur file...
                                    </p>
                                </div>

                                <div v-else class="flex flex-col sm:flex-row items-center justify-between gap-4 p-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <div class="text-left">
                                            <p class="text-sm font-bold text-slate-800 break-all">{{ uploadedFile.name }}</p>
                                            <p class="text-xs text-slate-500">
                                                Terdeteksi <span class="font-semibold text-indigo-600">{{ parsedRows.length }} baris</span> data siap diproses.
                                            </p>
                                        </div>
                                    </div>
                                    <button
                                        @click.stop="resetFile"
                                        type="button"
                                        class="px-3 py-1.5 text-xs text-rose-600 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition font-medium border border-rose-200"
                                    >
                                        Ganti Berkas
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 2: PREVIEW & SMART AUTO-MAPPER -->
                        <div v-if="parsedRows.length > 0" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-xs sm:text-sm font-bold text-slate-800 flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-xs flex items-center justify-center font-bold">2</span>
                                    Pratinjau Data (5 Baris Pertama)
                                </label>
                                <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-600">
                                    {{ detectedHeaders.length }} Kolom Terbaca
                                </span>
                            </div>

                            <div class="overflow-x-auto border border-slate-200 rounded-xl bg-white shadow-inner max-h-64">
                                <table class="min-w-full divide-y divide-slate-200 text-xs">
                                    <thead class="bg-slate-50 sticky top-0 z-10">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-bold text-slate-500 uppercase tracking-wider w-12">#</th>
                                            <th
                                                v-for="h in detectedHeaders"
                                                :key="h"
                                                class="px-3 py-2 text-left font-bold text-slate-700 whitespace-nowrap"
                                            >
                                                {{ h }}
                                                <span
                                                    v-if="currentCategory.headers[h]?.required"
                                                    class="text-rose-500 ml-0.5"
                                                    title="Wajib"
                                                >*</span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="(row, idx) in parsedRows.slice(0, 5)" :key="idx" class="hover:bg-slate-50/70">
                                            <td class="px-3 py-2 text-slate-400 font-mono text-[11px]">{{ idx + 1 }}</td>
                                            <td
                                                v-for="h in detectedHeaders"
                                                :key="h"
                                                class="px-3 py-2 text-slate-700 whitespace-nowrap font-mono text-[11px]"
                                            >
                                                {{ formatDisplayValue(h, row[h]) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- STEP 3: SAFETY & HISTORICAL BYPASS CONFIGURATION -->
                        <div class="bg-slate-50/80 rounded-2xl p-5 border border-slate-200/90 space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                                <div>
                                    <h3 class="text-xs sm:text-sm font-bold text-slate-800 flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-xs flex items-center justify-center font-bold">3</span>
                                        Konfigurasi Perlindungan Sistem (Safe Mode Bypass)
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        Opsi keselamatan untuk mencegah pemicu otomatis (efek samping) saat mengimpor data lampau.
                                    </p>
                                </div>
                                <span class="px-2 py-0.5 text-[11px] font-bold rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    Shield Aktif
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Mode Duplikasi -->
                                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm space-y-2">
                                    <label class="text-xs font-bold text-slate-700 block">Strategi Data Duplikat</label>
                                    <div class="flex flex-col gap-2">
                                        <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                            <input
                                                type="radio"
                                                v-model="migrationOptions.duplicate_mode"
                                                value="skip"
                                                class="text-indigo-600 focus:ring-indigo-500 rounded border-slate-300"
                                            />
                                            <span><strong>Lewati (Skip):</strong> Jangan ubah data jika sudah ada di database. (Rekomendasi)</span>
                                        </label>
                                        <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                            <input
                                                type="radio"
                                                v-model="migrationOptions.duplicate_mode"
                                                value="update"
                                                class="text-indigo-600 focus:ring-indigo-500 rounded border-slate-300"
                                            />
                                            <span><strong>Perbarui (Update):</strong> Timpa atribut data existing dengan data baru dari file.</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Bypass Toggles -->
                                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm space-y-2.5">
                                    <label class="text-xs font-bold text-slate-700 block">Pelindung Efek Samping (Historical Bypass)</label>
                                    
                                    <div class="space-y-2 text-xs">
                                        <label class="flex items-center justify-between text-slate-700 cursor-pointer">
                                            <span>🛡️ Bypass Komisi & Insentif Payroll</span>
                                            <input
                                                type="checkbox"
                                                v-model="migrationOptions.bypass_commission"
                                                class="text-indigo-600 focus:ring-indigo-500 rounded border-slate-300"
                                            />
                                        </label>
                                        <label class="flex items-center justify-between text-slate-700 cursor-pointer">
                                            <span>📦 Bypass Pemotongan Stok Gudang Material</span>
                                            <input
                                                type="checkbox"
                                                v-model="migrationOptions.bypass_material_stock"
                                                class="text-indigo-600 focus:ring-indigo-500 rounded border-slate-300"
                                            />
                                        </label>
                                        <label class="flex items-center justify-between text-slate-700 cursor-pointer">
                                            <span>💬 Bypass Notifikasi Otomatis WhatsApp</span>
                                            <input
                                                type="checkbox"
                                                v-model="migrationOptions.bypass_whatsapp"
                                                class="text-indigo-600 focus:ring-indigo-500 rounded border-slate-300"
                                            />
                                        </label>
                                        <label class="flex items-center justify-between text-slate-700 cursor-pointer">
                                            <span>📡 Bypass Mikrotik / FreeRADIUS Disconnect</span>
                                            <input
                                                type="checkbox"
                                                v-model="migrationOptions.bypass_radius"
                                                class="text-indigo-600 focus:ring-indigo-500 rounded border-slate-300"
                                            />
                                        </label>
                                        <label class="flex items-center justify-between text-slate-700 cursor-pointer border-t border-slate-100 pt-2">
                                            <span class="text-indigo-700 font-medium">📍 Buat Area Otomatis jika belum terdaftar</span>
                                            <input
                                                type="checkbox"
                                                v-model="migrationOptions.auto_create_areas"
                                                class="text-indigo-600 focus:ring-indigo-500 rounded border-slate-300"
                                            />
                                        </label>
                                        <label class="flex items-center justify-between text-slate-700 cursor-pointer pt-1.5">
                                            <span class="text-indigo-700 font-medium">⚡ Pencocokan Cerdas ODP (Slash/Ganda) & Toleransi Typo Nama Sales</span>
                                            <input
                                                type="checkbox"
                                                v-model="migrationOptions.smart_matching"
                                                class="text-indigo-600 focus:ring-indigo-500 rounded border-slate-300"
                                            />
                                        </label>
                                        <label class="flex items-center justify-between text-slate-700 cursor-pointer pt-1.5">
                                            <span class="text-indigo-700 font-medium">📡 Buat ODP Otomatis jika belum terdaftar di Master ODP</span>
                                            <input
                                                type="checkbox"
                                                v-model="migrationOptions.auto_create_odps"
                                                class="text-indigo-600 focus:ring-indigo-500 rounded border-slate-300"
                                            />
                                        </label>
                                        <label class="flex items-center justify-between text-slate-700 cursor-pointer pt-1.5">
                                            <span class="text-indigo-700 font-medium">👤 Buat Akun Sales / Karyawan Otomatis jika belum ada di sistem</span>
                                            <input
                                                type="checkbox"
                                                v-model="migrationOptions.auto_create_sales"
                                                class="text-indigo-600 focus:ring-indigo-500 rounded border-slate-300"
                                            />
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 4: DRY-RUN SIMULATION & VALIDATION RESULTS -->
                        <div class="space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-xs sm:text-sm font-bold text-slate-800 flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-xs flex items-center justify-center font-bold">4</span>
                                        Simulasi & Validasi Data (Dry-Run)
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        Uji coba validasi foreign key, format, dan duplikasi data tanpa menyentuh database.
                                    </p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button
                                        @click="runDryRun"
                                        :disabled="parsedRows.length === 0 || isDryRunning || isExecuting"
                                        type="button"
                                        class="px-4 py-2 text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-xl transition flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm"
                                    >
                                        <svg v-if="isDryRunning" class="animate-spin w-4 h-4 text-indigo-700" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <svg v-else class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                        {{ isDryRunning ? 'Sedang Memvalidasi...' : 'Jalankan Dry Run Sekarang' }}
                                    </button>

                                    <!-- Download Error Report Button -->
                                    <button
                                        v-if="dryRunSummary && dryRunSummary.error_count > 0"
                                        @click="downloadErrorReport"
                                        type="button"
                                        class="px-3.5 py-2 text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-xl transition flex items-center gap-1.5 shadow-sm"
                                    >
                                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        Unduh Laporan Error CSV
                                    </button>
                                </div>
                            </div>

                            <!-- DRY RUN SUMMARY STATS -->
                            <div v-if="dryRunSummary" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div class="bg-indigo-50/70 border border-indigo-200/80 rounded-xl p-3.5">
                                    <span class="text-xs text-indigo-700 font-medium block">Total Diperiksa</span>
                                    <span class="text-xl sm:text-2xl font-black text-indigo-950">{{ dryRunSummary.total_rows }}</span>
                                    <span class="text-[11px] text-indigo-600 ml-1">baris data</span>
                                </div>
                                <div class="bg-emerald-50/70 border border-emerald-200/80 rounded-xl p-3.5">
                                    <span class="text-xs text-emerald-700 font-medium block">Valid (Siap Impor)</span>
                                    <span class="text-xl sm:text-2xl font-black text-emerald-950">{{ dryRunSummary.valid_count }}</span>
                                    <span class="text-[11px] text-emerald-600 ml-1">baris lolos</span>
                                </div>
                                <div class="bg-amber-50/70 border border-amber-200/80 rounded-xl p-3.5">
                                    <span class="text-xs text-amber-700 font-medium block">Peringatan (Warning)</span>
                                    <span class="text-xl sm:text-2xl font-black text-amber-950">{{ dryRunSummary.warning_count }}</span>
                                    <span class="text-[11px] text-amber-600 ml-1">catatan</span>
                                </div>
                                <div class="bg-rose-50/70 border border-rose-200/80 rounded-xl p-3.5">
                                    <span class="text-xs text-rose-700 font-medium block">Gagal / Error</span>
                                    <span class="text-xl sm:text-2xl font-black text-rose-950">{{ dryRunSummary.error_count }}</span>
                                    <span class="text-[11px] text-rose-600 ml-1">harus diperbaiki</span>
                                </div>
                            </div>

                            <!-- DRY RUN FILTER & DETAIL TABLE -->
                            <div v-if="dryRunRows.length > 0" class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-sm space-y-3 p-4">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="flex items-center gap-1.5 overflow-x-auto text-xs">
                                        <button
                                            @click="activeStatusFilter = 'all'"
                                            :class="['px-3 py-1.5 rounded-lg font-medium transition', activeStatusFilter === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200']"
                                        >
                                            Semua ({{ dryRunRows.length }})
                                        </button>
                                        <button
                                            @click="activeStatusFilter = 'valid'"
                                            :class="['px-3 py-1.5 rounded-lg font-medium transition', activeStatusFilter === 'valid' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100']"
                                        >
                                            Valid Saja ({{ dryRunSummary?.valid_count || 0 }})
                                        </button>
                                        <button
                                            @click="activeStatusFilter = 'warning'"
                                            :class="['px-3 py-1.5 rounded-lg font-medium transition', activeStatusFilter === 'warning' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100']"
                                        >
                                            Peringatan ({{ dryRunSummary?.warning_count || 0 }})
                                        </button>
                                        <button
                                            @click="activeStatusFilter = 'error'"
                                            :class="['px-3 py-1.5 rounded-lg font-medium transition', activeStatusFilter === 'error' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100']"
                                        >
                                            Error Saja ({{ dryRunSummary?.error_count || 0 }})
                                        </button>
                                    </div>

                                    <div class="relative w-full sm:w-64">
                                        <input
                                            type="text"
                                            v-model="dryRunSearch"
                                            placeholder="Cari baris data / nama / kode..."
                                            class="w-full text-xs pl-8 pr-3 py-1.5 border border-slate-200 rounded-lg focus:ring-1 focus:ring-indigo-500"
                                        />
                                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>
                                </div>

                                <div class="overflow-x-auto border border-slate-100 rounded-xl max-h-80">
                                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                                        <thead class="bg-slate-50 sticky top-0 z-10">
                                            <tr>
                                                <th class="px-3 py-2 text-left font-bold text-slate-500 w-16">Baris</th>
                                                <th class="px-3 py-2 text-left font-bold text-slate-500">Status</th>
                                                <th class="px-3 py-2 text-left font-bold text-slate-700">Identitas / Ringkasan Data</th>
                                                <th class="px-3 py-2 text-left font-bold text-slate-700">Detail Masalah / Catatan</th>
                                                <th class="px-3 py-2 text-right font-bold text-slate-500 w-24">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <tr
                                                v-for="item in filteredDryRunRows"
                                                :key="item.row_index"
                                                :class="[
                                                    item.status === 'error' ? 'bg-rose-50/40' : item.status === 'warning' ? 'bg-amber-50/40' : 'hover:bg-slate-50/60'
                                                ]"
                                            >
                                                <td class="px-3 py-2 font-mono text-[11px] text-slate-500 font-semibold">
                                                    #{{ item.row_index }}
                                                </td>
                                                <td class="px-3 py-2 whitespace-nowrap">
                                                    <span
                                                        v-if="item.status === 'valid'"
                                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800"
                                                    >
                                                        ✓ Valid
                                                    </span>
                                                    <span
                                                        v-else-if="item.status === 'warning'"
                                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800"
                                                    >
                                                        ⚠ Warning
                                                    </span>
                                                    <span
                                                        v-else
                                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800"
                                                    >
                                                        ✕ Error
                                                    </span>
                                                </td>
                                                <td class="px-3 py-2 text-slate-800 font-medium">
                                                    <div class="truncate max-w-xs font-mono text-[11px]">
                                                        {{ getRowIdentifier(item.raw) }}
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2">
                                                    <div v-if="item.errors && item.errors.length > 0" class="space-y-1">
                                                        <span
                                                            v-for="(err, eIdx) in item.errors"
                                                            :key="eIdx"
                                                            class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold bg-rose-100 text-rose-800 mr-1"
                                                        >
                                                            {{ err }}
                                                        </span>
                                                    </div>
                                                    <div v-if="item.warnings && item.warnings.length > 0" class="space-y-1 mt-0.5">
                                                        <span
                                                            v-for="(w, wIdx) in item.warnings"
                                                            :key="wIdx"
                                                            class="inline-block px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-800 mr-1"
                                                        >
                                                            {{ w }}
                                                        </span>
                                                    </div>
                                                    <span v-if="item.status === 'valid' && (!item.warnings || item.warnings.length === 0)" class="text-slate-400 text-[11px]">
                                                        Siap diimpor
                                                    </span>
                                                </td>
                                                <td class="px-3 py-2 text-right whitespace-nowrap">
                                                    <button
                                                        @click="inspectRow(item)"
                                                        type="button"
                                                        class="text-indigo-600 hover:text-indigo-900 font-medium text-[11px] underline"
                                                    >
                                                        Lihat Raw
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 5: FINAL DATABASE TRANSACTION EXECUTION -->
                        <div class="pt-6 border-t border-slate-200">
                            <div class="bg-gradient-to-r from-indigo-900 to-slate-900 rounded-2xl p-6 text-white flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-lg">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-full bg-emerald-400 text-slate-950 text-xs flex items-center justify-center font-bold">5</span>
                                        <h3 class="text-base sm:text-lg font-bold text-white">
                                            Komitmen Data ke Database (Eksekusi Impor)
                                        </h3>
                                    </div>
                                    <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-2xl">
                                        Semua data valid akan dimasukkan ke database dalam 
                                        <span class="text-emerald-400 font-semibold">Database Transaction</span> tunggal. 
                                        Jika terjadi galat fatal tak terduga, database akan di-rollback secara otomatis ke kondisi semula.
                                    </p>
                                </div>

                                <div class="flex flex-col sm:flex-row items-center gap-3">
                                    <button
                                        @click="executeImport"
                                        :disabled="isExecuting || isDryRunning || parsedRows.length === 0"
                                        type="button"
                                        class="w-full sm:w-auto px-6 py-3.5 rounded-xl font-bold text-sm text-slate-950 bg-emerald-400 hover:bg-emerald-300 transition shadow-lg shadow-emerald-400/20 flex items-center justify-center gap-2.5 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                                    >
                                        <svg v-if="isExecuting" class="animate-spin w-5 h-5 text-slate-950" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <svg v-else class="w-5 h-5 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        {{ isExecuting ? 'Memproses Impor...' : 'Eksekusi Impor ke Database' }}
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- SHORTCUTS & RECENT AUDIT LOGS -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Direct Verification Shortcuts -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6 space-y-4">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            Verifikasi Data pada Menu Existing
                        </h3>
                        <p class="text-xs text-slate-500">
                            Buka modul terkait untuk langsung memastikan data hasil migrasi telah muncul secara normal:
                        </p>

                        <div class="space-y-2">
                            <a
                                href="/customers"
                                class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 border border-slate-100 transition text-xs font-semibold text-slate-700"
                            >
                                <span class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                    Menu Data Pelanggan
                                </span>
                                <span class="text-slate-400">/customers →</span>
                            </a>
                            <a
                                href="/network-data"
                                class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 border border-slate-100 transition text-xs font-semibold text-slate-700"
                            >
                                <span class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                                    Menu Data Jaringan FTTH
                                </span>
                                <span class="text-slate-400">/network-data →</span>
                            </a>
                            <a
                                href="/invoices"
                                class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 border border-slate-100 transition text-xs font-semibold text-slate-700"
                            >
                                <span class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    Menu Invoice & Billing
                                </span>
                                <span class="text-slate-400">/invoices →</span>
                            </a>
                            <a
                                href="/payments"
                                class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 border border-slate-100 transition text-xs font-semibold text-slate-700"
                            >
                                <span class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Menu Pembayaran Kasir
                                </span>
                                <span class="text-slate-400">/payments →</span>
                            </a>
                            <a
                                href="/employees"
                                class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 border border-slate-100 transition text-xs font-semibold text-slate-700"
                            >
                                <span class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-violet-500"></span>
                                    Menu Data Karyawan (HR)
                                </span>
                                <span class="text-slate-400">/employees →</span>
                            </a>
                        </div>
                    </div>

                    <!-- Recent Audit Logs Table -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6 space-y-4 lg:col-span-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Riwayat Log Migrasi Terakhir
                            </h3>
                            <span class="text-[11px] text-slate-400">AuditLog Eksekusi</span>
                        </div>

                        <div class="overflow-x-auto border border-slate-100 rounded-xl max-h-60">
                            <table class="min-w-full divide-y divide-slate-200 text-xs">
                                <thead class="bg-slate-50 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-bold text-slate-500">Waktu</th>
                                        <th class="px-3 py-2 text-left font-bold text-slate-700">Aktivitas Migrasi</th>
                                        <th class="px-3 py-2 text-left font-bold text-slate-500">Operator</th>
                                        <th class="px-3 py-2 text-left font-bold text-slate-500">IP Address</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-50/60">
                                        <td class="px-3 py-2 text-slate-500 whitespace-nowrap text-[11px]">
                                            {{ formatDate(log.created_at) }}
                                        </td>
                                        <td class="px-3 py-2 text-slate-800 font-medium">
                                            <div>{{ log.action }}</div>
                                            <div class="text-[11px] text-slate-500">{{ log.description }}</div>
                                        </td>
                                        <td class="px-3 py-2 text-slate-600 whitespace-nowrap">
                                            {{ log.user ? log.user.name : 'System' }}
                                        </td>
                                        <td class="px-3 py-2 text-slate-400 font-mono text-[11px]">
                                            {{ log.ip_address || '-' }}
                                        </td>
                                    </tr>
                                    <tr v-if="!logs || logs.length === 0">
                                        <td colspan="4" class="px-3 py-8 text-center text-slate-400">
                                            Belum ada riwayat aktivitas migrasi data tercatat.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <!-- MODAL: PANDUAN KOLOM -->
        <div v-if="showGuideModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[85vh] flex flex-col overflow-hidden border border-slate-200">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                    <div>
                        <h3 class="text-base font-bold text-slate-800">
                            Struktur & Format Kolom: {{ currentCategory.title }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Gunakan nama kolom di bawah pada header Excel atau CSV Anda.
                        </p>
                    </div>
                    <button @click="showGuideModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1">
                        ✕
                    </button>
                </div>

                <div class="p-5 overflow-y-auto space-y-4">
                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-bold text-slate-600">Nama Header (CSV)</th>
                                <th class="px-3 py-2 text-left font-bold text-slate-600">Keterangan</th>
                                <th class="px-3 py-2 text-left font-bold text-slate-600">Wajib?</th>
                                <th class="px-3 py-2 text-left font-bold text-slate-600">Kolom Database</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-mono">
                            <tr v-for="(col, key) in currentCategory.headers" :key="key" class="hover:bg-slate-50/60">
                                <td class="px-3 py-2 font-bold text-indigo-700">{{ key }}</td>
                                <td class="px-3 py-2 font-sans text-slate-700">{{ col.label }}</td>
                                <td class="px-3 py-2">
                                    <span v-if="col.required" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">Wajib</span>
                                    <span v-else class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600">Opsional</span>
                                </td>
                                <td class="px-3 py-2 text-slate-500 font-mono text-[11px]">{{ col.db }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-100 bg-slate-50 flex justify-end">
                    <button
                        @click="showGuideModal = false"
                        type="button"
                        class="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-100"
                    >
                        Tutup Panduan
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL: INSPECT RAW ROW DATA -->
        <div v-if="inspectedRow" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full max-h-[85vh] flex flex-col overflow-hidden border border-slate-200">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                    <h4 class="text-sm font-bold text-slate-800">
                        Detail Data Baris #{{ inspectedRow.row_index }}
                    </h4>
                    <button @click="inspectedRow = null" class="text-slate-400 hover:text-slate-600 font-bold p-1">
                        ✕
                    </button>
                </div>
                <div class="p-4 overflow-y-auto">
                    <pre class="bg-slate-900 text-slate-100 p-3 rounded-xl text-xs overflow-x-auto font-mono">{{ JSON.stringify(inspectedRow.raw, null, 2) }}</pre>
                </div>
                <div class="p-3 border-t border-slate-100 bg-slate-50 flex justify-end">
                    <button @click="inspectedRow = null" class="px-3.5 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    categories: { type: Object, required: true },
    references: { type: Object, default: () => ({}) },
    logs: { type: Array, default: () => [] },
});

// Category State
const urlParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
const initialCategory = urlParams?.get('category');
const selectedCategoryKey = ref(initialCategory && props.categories[initialCategory] ? initialCategory : 'customers');
const currentCategory = computed(() => props.categories[selectedCategoryKey.value] || {});

// File State
const fileInputRef = ref(null);
const uploadedFile = ref(null);
const isDragging = ref(false);
const isParsing = ref(false);
const parsedRows = ref([]);
const detectedHeaders = ref([]);

// Modal State
const showGuideModal = ref(false);
const inspectedRow = ref(null);

// Safety & Bypass Options
const migrationOptions = ref({
    duplicate_mode: 'skip',
    bypass_commission: true,
    bypass_material_stock: true,
    bypass_whatsapp: true,
    bypass_radius: true,
    auto_create_areas: true,
    auto_create_odps: true,
    auto_create_sales: true,
    smart_matching: true,
});

// Dry Run State
const isDryRunning = ref(false);
const dryRunSummary = ref(null);
const dryRunRows = ref([]);
const dryRunSearch = ref('');
const activeStatusFilter = ref('all');

// Execution State
const isExecuting = ref(false);

// Methods
function selectCategory(key) {
    if (parsedRows.value.length > 0) {
        if (!confirm('Mengganti kategori akan mereset data file yang sedang terbuka. Lanjutkan?')) {
            return;
        }
    }
    selectedCategoryKey.value = key;
    resetFile();
}

function triggerFileInput() {
    if (fileInputRef.value) {
        fileInputRef.value.click();
    }
}

function handleFileInput(e) {
    const file = e.target.files?.[0];
    if (file) {
        processFile(file);
    }
}

function handleFileDrop(e) {
    isDragging.value = false;
    const file = e.dataTransfer.files?.[0];
    if (file) {
        processFile(file);
    }
}

function resetFile() {
    uploadedFile.value = null;
    parsedRows.value = [];
    detectedHeaders.value = [];
    dryRunSummary.value = null;
    dryRunRows.value = [];
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
}

async function processFile(file) {
    resetFile();
    uploadedFile.value = file;
    isParsing.value = true;

    try {
        const ext = file.name.split('.').pop().toLowerCase();

        if (ext === 'xlsx' || ext === 'xls') {
            await parseExcelFile(file);
        } else {
            await parseCsvFile(file);
        }

        if (parsedRows.value.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Berkas Kosong',
                text: 'Tidak ada baris data yang ditemukan di dalam berkas ini.',
            });
        }
    } catch (err) {
        console.error('Error parsing file:', err);
        Swal.fire({
            icon: 'error',
            title: 'Gagal Membaca Berkas',
            text: err.message || 'Format berkas tidak didukung atau rusak.',
        });
        resetFile();
    } finally {
        isParsing.value = false;
    }
}

// Download native Excel template (.xlsx) using SheetJS
async function downloadExcelTemplate() {
    try {
        const XLSX = await loadSheetJs();
        const def = currentCategory.value;
        if (!def) return;

        const headers = Object.keys(def.headers || {});
        const sampleRows = (def.sample && def.sample.length > 0) ? def.sample : [{}];

        // Build data: row 1 headers, subsequent rows sample rows
        const data = [
            headers,
            ...sampleRows.map(row => headers.map(h => row[h] !== undefined ? row[h] : ''))
        ];

        const ws = XLSX.utils.aoa_to_sheet(data);

        // Auto column widths
        ws['!cols'] = headers.map(h => ({
            wch: Math.max(h.length + 4, 16)
        }));

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Template ' + (def.title || 'Data').slice(0, 20));

        const fileName = `template_migrasi_${selectedCategoryKey.value}_${new Date().toISOString().slice(0, 10)}.xlsx`;
        XLSX.writeFile(wb, fileName);
    } catch (err) {
        console.error('Error downloading Excel template:', err);
        // Fallback to CSV
        window.open(`/settings/data-migration/template/${selectedCategoryKey.value}`, '_blank');
    }
}

// Client-side CSV Parser
function parseCsvFile(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                let text = e.target.result;
                const rows = parseCsvText(text);
                parsedRows.value = rows;
                if (rows.length > 0) {
                    detectedHeaders.value = Object.keys(rows[0]);
                }
                resolve();
            } catch (err) {
                reject(err);
            }
        };
        reader.onerror = () => reject(new Error('Gagal membaca file dari disk.'));
        reader.readAsText(file, 'UTF-8');
    });
}

function parseCsvText(text) {
    const lines = [];
    let row = [''];
    let inQuotes = false;
    let i = 0;

    // Strip BOM
    if (text.charCodeAt(0) === 0xFEFF) {
        text = text.slice(1);
    }

    // Strip sep= directive if present at the top
    if (text.startsWith('sep=')) {
        const nlIdx = text.indexOf('\n');
        if (nlIdx !== -1) {
            text = text.slice(nlIdx + 1);
        }
    }

    // Detect delimiter: check first line
    let delimiter = ',';
    const firstLineEnd = text.indexOf('\n');
    const sampleLine = firstLineEnd !== -1 ? text.slice(0, firstLineEnd) : text;
    if ((sampleLine.match(/;/g) || []).length > (sampleLine.match(/,/g) || []).length) {
        delimiter = ';';
    } else if ((sampleLine.match(/\t/g) || []).length > (sampleLine.match(/,/g) || []).length) {
        delimiter = '\t';
    }

    while (i < text.length) {
        const char = text[i];
        const next = text[i + 1];

        if (char === '"') {
            if (inQuotes && next === '"') {
                row[row.length - 1] += '"';
                i += 2;
                continue;
            } else {
                inQuotes = !inQuotes;
                i++;
                continue;
            }
        }

        if (char === delimiter && !inQuotes) {
            row.push('');
            i++;
            continue;
        }

        if ((char === '\r' || char === '\n') && !inQuotes) {
            if (char === '\r' && next === '\n') {
                i++;
            }
            if (row.length > 1 || row[0].trim() !== '') {
                lines.push(row);
            }
            row = [''];
            i++;
            continue;
        }

        row[row.length - 1] += char;
        i++;
    }

    if (row.length > 1 || row[0].trim() !== '') {
        lines.push(row);
    }

    if (lines.length < 2) return [];

    const rawHeaders = lines[0].map(h => normalizeKey(h));
    const results = [];

    for (let r = 1; r < lines.length; r++) {
        const line = lines[r];
        const obj = {};
        let hasValue = false;

        for (let c = 0; c < rawHeaders.length; c++) {
            const key = rawHeaders[c];
            const rawVal = (line[c] !== undefined ? line[c] : '').trim();
            const val = formatCellDateIfSerial(key, rawVal);
            obj[key] = val;
            if (val !== '') hasValue = true;
        }

        if (hasValue) {
            results.push(obj);
        }
    }

    return results;
}

// Smart auto-formatter for date values (including Excel serial numbers like 43770 -> 2019-11-01)
function formatCellDateIfSerial(key, val) {
    if (val === null || val === undefined || val === '') return '';

    // If SheetJS parsed it directly into a JavaScript Date object
    if (val instanceof Date) {
        const y = val.getUTCFullYear();
        const m = String(val.getUTCMonth() + 1).padStart(2, '0');
        const d = String(val.getUTCDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    // If it's a numeric Excel serial date (e.g. 43770, 44089, 44470, 45363)
    const num = Number(val);
    const isDateField = /tgl|tanggal|date|join|tempo|bayar|aktivasi|survey|registrasi/i.test(key);
    const isNotCurrencyOrPhone = !/gaji|tarif|biaya|amount|price|port|meter|rx|power|telepon|phone|hp|wa|rek|rekening|nip|iak/i.test(key);

    if (!isNaN(num) && num >= 10000 && num <= 75000 && (isDateField || (typeof val === 'number' && isNotCurrencyOrPhone))) {
        // Convert Excel serial days to UTC date (accounting for 1900 leap year bug offset: 25569)
        const date = new Date(Math.round((num - 25569) * 86400 * 1000));
        if (!isNaN(date.getTime())) {
            const y = date.getUTCFullYear();
            const m = String(date.getUTCMonth() + 1).padStart(2, '0');
            const d = String(date.getUTCDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }
    }

    return typeof val === 'string' ? val.trim() : val;
}

function formatDisplayValue(header, val) {
    if (val === undefined || val === null || val === '') return '-';
    return formatCellDateIfSerial(header, val);
}

// Client-side Excel Parser with Dynamic SheetJS CDN
async function parseExcelFile(file) {
    const XLSX = await loadSheetJs();
    const data = await file.arrayBuffer();
    const workbook = XLSX.read(data, { type: 'array', cellDates: true, dateNF: 'yyyy-mm-dd' });
    const firstSheetName = workbook.SheetNames[0];
    const worksheet = workbook.Sheets[firstSheetName];
    const rawJson = XLSX.utils.sheet_to_json(worksheet, { defval: '', raw: false });

    if (!rawJson || rawJson.length === 0) {
        parsedRows.value = [];
        return;
    }

    const normalized = rawJson.map(row => {
        const item = {};
        for (const [k, v] of Object.entries(row)) {
            const cleanKey = normalizeKey(k);
            item[cleanKey] = formatCellDateIfSerial(cleanKey, v);
        }
        return item;
    });

    parsedRows.value = normalized;
    if (normalized.length > 0) {
        detectedHeaders.value = Object.keys(normalized[0]);
    }
}

function loadSheetJs() {
    if (window.XLSX) return Promise.resolve(window.XLSX);
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js';
        script.onload = () => resolve(window.XLSX);
        script.onerror = () => {
            const fallback = document.createElement('script');
            fallback.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
            fallback.onload = () => resolve(window.XLSX);
            fallback.onerror = () => reject(new Error('Gagal memuat Excel parser. Anda dapat menyimpan file Excel Anda sebagai CSV (.csv) untuk melanjutkan secara langsung.'));
            document.head.appendChild(fallback);
        };
        document.head.appendChild(script);
    });
}

function normalizeKey(str) {
    return String(str || '')
        .trim()
        .toLowerCase()
        .replace(/[\s\-_/\\()]+/g, '_')
        .replace(/^_+|_+$/g, '');
}

function getRowIdentifier(raw) {
    if (!raw) return '-';
    return raw.kode_pelanggan || raw.customer_code || raw.nama_lengkap || raw.email || raw.nomor_iak || raw.nama_odp || raw.sn_ont || raw.nomor_invoice || raw.nomor_referensi || Object.values(raw).find(v => v) || '-';
}

function inspectRow(item) {
    inspectedRow.value = item;
}

// Dry Run Call
async function runDryRun() {
    if (parsedRows.value.length === 0) return;

    isDryRunning.value = true;
    try {
        const payload = {
            category: selectedCategoryKey.value,
            rows: parsedRows.value,
            options: migrationOptions.value,
        };

        const res = await axios.post('/settings/data-migration/dry-run', payload);

        if (res.data && res.data.success) {
            const summary = res.data.summary || {};
            const errorCount = Number(summary.error_count ?? summary.errors ?? 0);
            const validCount = Number(summary.valid_count ?? summary.valid ?? 0);
            const warningCount = Number(summary.warning_count ?? summary.warnings ?? 0);
            const totalRows = Number(summary.total_rows ?? summary.total ?? (res.data.rows?.length || 0));

            dryRunSummary.value = {
                ...summary,
                error_count: errorCount,
                valid_count: validCount,
                warning_count: warningCount,
                total_rows: totalRows,
            };
            dryRunRows.value = res.data.rows || [];
            activeStatusFilter.value = 'all';

            if (errorCount === 0) {
                Swal.fire({
                    icon: 'success',
                    title: 'Dry-Run Berhasil!',
                    text: `Seluruh ${validCount} baris data lolos validasi dan siap diimpor ke database.`,
                    timer: 2500,
                    showConfirmButton: false,
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Ditemukan Kesalahan Validasi',
                    text: `Terdapat ${errorCount} baris yang bermasalah. Anda dapat meneliti rincian di tabel bawah atau mengunduh laporan error.`,
                });
            }
        } else {
            throw new Error(res.data.message || 'Gagal menjalankan validasi.');
        }
    } catch (err) {
        console.error('Dry-run error:', err);
        Swal.fire({
            icon: 'error',
            title: 'Gagal Validasi',
            text: err.response?.data?.message || err.message || 'Terjadi kesalahan sistem saat dry-run.',
        });
    } finally {
        isDryRunning.value = false;
    }
}

// Filtered Dry Run Rows
const filteredDryRunRows = computed(() => {
    let rows = dryRunRows.value;

    if (activeStatusFilter.value !== 'all') {
        rows = rows.filter(r => r.status === activeStatusFilter.value);
    }

    if (dryRunSearch.value.trim() !== '') {
        const q = dryRunSearch.value.toLowerCase();
        rows = rows.filter(r => {
            const ident = String(getRowIdentifier(r.raw)).toLowerCase();
            const errors = (r.errors || []).join(' ').toLowerCase();
            const warnings = (r.warnings || []).join(' ').toLowerCase();
            const rowIdx = String(r.row_index);
            return ident.includes(q) || errors.includes(q) || warnings.includes(q) || rowIdx.includes(q);
        });
    }

    return rows;
});

// Download Error Report CSV
async function downloadErrorReport() {
    if (!dryRunRows.value || dryRunRows.value.length === 0) return;

    const errorRows = dryRunRows.value.filter(r => r.status === 'error' || r.status === 'warning');

    try {
        const response = await axios.post(
            '/settings/data-migration/error-report',
            {
                category: selectedCategoryKey.value,
                errors: errorRows,
            },
            { responseType: 'blob' }
        );

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `laporan_error_migrasi_${selectedCategoryKey.value}_${Date.now()}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } catch (err) {
        console.error('Download error report failed:', err);
        Swal.fire({
            icon: 'error',
            title: 'Gagal Mengunduh',
            text: 'Tidak dapat mengunduh laporan kesalahan.',
        });
    }
}

// Final Execution Call with Chunked Batching & Real-time Progress Bar
async function executeImport() {
    if (parsedRows.value.length === 0) return;

    const totalRows = parsedRows.value.length;

    const confirmResult = await Swal.fire({
        title: 'Konfirmasi Eksekusi Migrasi',
        html: `
            <div class="text-left text-xs space-y-2 text-slate-700">
                <p>Anda akan melakukan migrasi data secara permanen ke database:</p>
                <div class="bg-slate-100 p-3 rounded-lg space-y-1">
                    <div><strong>Modul:</strong> ${currentCategory.value.title}</div>
                    <div><strong>Total Baris:</strong> ${totalRows} baris</div>
                    <div><strong>Mode Duplikat:</strong> ${migrationOptions.value.duplicate_mode.toUpperCase()}</div>
                    <div><strong>Bypass Protections:</strong> Aktif (Komisi, Stok, WA, RADIUS aman)</div>
                </div>
                <p class="text-rose-600 font-semibold">Tindakan ini akan mencatat log resmi ke AuditLog sistem.</p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Jalankan Impor Sekarang',
        cancelButtonText: 'Batal',
    });

    if (!confirmResult.isConfirmed) return;

    isExecuting.value = true;

    // Show Progress Modal
    Swal.fire({
        title: 'Memproses Migrasi Data...',
        html: `
            <div class="text-left text-xs space-y-3 py-2 text-slate-700">
                <div class="flex justify-between items-center text-xs font-semibold text-slate-700">
                    <span id="batch-progress-status">Menyiapkan batch pengiriman...</span>
                    <span id="batch-progress-percent" class="text-indigo-600 font-bold">0%</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-3 overflow-hidden shadow-inner">
                    <div id="batch-progress-bar" class="bg-gradient-to-r from-indigo-500 to-emerald-500 h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 text-xs grid grid-cols-2 gap-2 text-slate-600">
                    <div>Progress: <strong id="batch-progress-count" class="text-slate-900">0</strong> / ${totalRows} baris</div>
                    <div>Baris Baru: <strong id="batch-progress-created" class="text-emerald-600 font-bold">0</strong></div>
                    <div>Diperbarui: <strong id="batch-progress-updated" class="text-blue-600 font-bold">0</strong></div>
                    <div>Dilewati/Skip: <strong id="batch-progress-skipped" class="text-amber-600 font-bold">0</strong></div>
                </div>
                <p class="text-[11px] text-slate-400 italic text-center">Mohon jangan menutup jendela browser selama proses berjalan...</p>
            </div>
        `,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const batchSize = 75;
    const totalBatches = Math.ceil(totalRows / batchSize);

    let totalCreated = 0;
    let totalUpdated = 0;
    let totalSkipped = 0;
    let totalFailed = 0;
    let batchErrors = [];

    try {
        for (let b = 0; b < totalBatches; b++) {
            const start = b * batchSize;
            const end = Math.min(start + batchSize, totalRows);
            const chunk = parsedRows.value.slice(start, end);

            // Update UI Elements
            const currentPercent = Math.round((start / totalRows) * 100);
            const statusElem = document.getElementById('batch-progress-status');
            const percentElem = document.getElementById('batch-progress-percent');
            const barElem = document.getElementById('batch-progress-bar');
            const countElem = document.getElementById('batch-progress-count');
            const createdElem = document.getElementById('batch-progress-created');
            const updatedElem = document.getElementById('batch-progress-updated');
            const skippedElem = document.getElementById('batch-progress-skipped');

            if (statusElem) statusElem.textContent = `Memproses batch ${b + 1} dari ${totalBatches} (${start + 1}-${end})...`;
            if (percentElem) percentElem.textContent = `${currentPercent}%`;
            if (barElem) barElem.style.width = `${currentPercent}%`;
            if (countElem) countElem.textContent = `${start}`;
            if (createdElem) createdElem.textContent = `${totalCreated}`;
            if (updatedElem) updatedElem.textContent = `${totalUpdated}`;
            if (skippedElem) skippedElem.textContent = `${totalSkipped}`;

            const payload = {
                category: selectedCategoryKey.value,
                rows: chunk,
                options: migrationOptions.value,
            };

            try {
                const res = await axios.post('/settings/data-migration/execute', payload);
                if (res.data && res.data.success) {
                    totalCreated += res.data.created_count || 0;
                    totalUpdated += res.data.updated_count || 0;
                    totalSkipped += res.data.skipped_count || 0;
                    totalFailed += res.data.failed_count || 0;
                } else {
                    totalFailed += chunk.length;
                    batchErrors.push(`Batch ${b + 1} (${start + 1}-${end}): ${res.data?.message || 'Gagal memproses batch'}`);
                }
            } catch (chunkErr) {
                totalFailed += chunk.length;
                const errDetail = chunkErr.response?.data?.message || chunkErr.message || 'Galat jaringan / server';
                batchErrors.push(`Batch ${b + 1} (${start + 1}-${end}): ${errDetail}`);
                console.error(`Error in batch ${b + 1}:`, chunkErr);
            }
        }

        // Final UI update to 100%
        const percentElem = document.getElementById('batch-progress-percent');
        const barElem = document.getElementById('batch-progress-bar');
        const countElem = document.getElementById('batch-progress-count');
        const createdElem = document.getElementById('batch-progress-created');
        const updatedElem = document.getElementById('batch-progress-updated');
        const skippedElem = document.getElementById('batch-progress-skipped');
        if (percentElem) percentElem.textContent = '100%';
        if (barElem) barElem.style.width = '100%';
        if (countElem) countElem.textContent = `${totalRows}`;
        if (createdElem) createdElem.textContent = `${totalCreated}`;
        if (updatedElem) updatedElem.textContent = `${totalUpdated}`;
        if (skippedElem) skippedElem.textContent = `${totalSkipped}`;

        // Give a slight delay so user sees 100% completion
        await new Promise(r => setTimeout(r, 600));

        if (batchErrors.length === 0) {
            Swal.fire({
                icon: 'success',
                title: 'Migrasi Selesai!',
                html: `
                    <div class="text-left text-xs space-y-2 text-slate-700">
                        <p class="text-emerald-700 font-bold text-sm">Semua data (${totalRows} baris) telah berhasil diproses:</p>
                        <div class="bg-emerald-50 border border-emerald-200 p-3 rounded-lg space-y-1">
                            <div>✓ Berhasil Dibuat: <strong>${totalCreated}</strong> baris</div>
                            <div>✓ Berhasil Diperbarui: <strong>${totalUpdated}</strong> baris</div>
                            <div>- Dilewati (Skip): <strong>${totalSkipped}</strong> baris</div>
                            <div>✕ Gagal: <strong>${totalFailed}</strong> baris</div>
                        </div>
                    </div>
                `,
                confirmButtonText: 'Selesai & Muat Ulang',
            }).then(() => {
                router.reload();
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Migrasi Selesai dengan Catatan',
                html: `
                    <div class="text-left text-xs space-y-2 text-slate-700">
                        <div class="bg-amber-50 border border-amber-200 p-3 rounded-lg space-y-1">
                            <div>✓ Berhasil Dibuat: <strong>${totalCreated}</strong> baris</div>
                            <div>✓ Berhasil Diperbarui: <strong>${totalUpdated}</strong> baris</div>
                            <div>- Dilewati (Skip): <strong>${totalSkipped}</strong> baris</div>
                            <div>✕ Gagal: <strong>${totalFailed}</strong> baris</div>
                        </div>
                        <div class="text-rose-600 font-semibold mt-2">Daftar Kesalahan Batch:</div>
                        <div class="max-h-32 overflow-y-auto text-[11px] bg-rose-50 border border-rose-200 p-2 rounded text-rose-700">
                            ${batchErrors.map(e => `<div>• ${e}</div>`).join('')}
                        </div>
                    </div>
                `,
                confirmButtonText: 'Tutup & Muat Ulang',
            }).then(() => {
                router.reload();
            });
        }
    } catch (err) {
        console.error('Execution error:', err);
        Swal.fire({
            icon: 'error',
            title: 'Galat Eksekusi Impor',
            text: err.response?.data?.message || err.message || 'Terjadi kesalahan saat menyimpan data.',
        });
    } finally {
        isExecuting.value = false;
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch (e) {
        return dateStr;
    }
}
</script>
