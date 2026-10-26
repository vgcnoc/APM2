<template>
    <AppLayout title="Insentif & Potongan">
        <div class="max-w-[1600px] w-full mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6 font-sans">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-3xl border border-gray-100 shadow-sm">
                <div>
                    <h1 class="text-xl font-extrabold text-gray-900 tracking-tight">Insentif &amp; Potongan</h1>
                    <p class="text-xs text-gray-500 mt-0.5">Daftar semua bonus, insentif, dan potongan karyawan</p>
                </div>
                <div class="flex gap-2">
                    <button v-if="can('payroll_incentives') || can('payroll_deductions') || can('payroll_manage')" @click="openCreateModal" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 border border-transparent rounded-xl font-bold text-xs text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all cursor-pointer">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        Tambah Manual
                    </button>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5 flex items-center justify-between relative overflow-hidden">
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Insentif</p>
                        <p class="mt-1 text-2xl font-black text-emerald-600 font-mono tracking-tight">Rp {{ formatCurrency(kpi.totalIncentive) }}</p>
                    </div>
                    <div class="w-11 h-11 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    </div>
                </div>
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5 flex items-center justify-between relative overflow-hidden">
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Potongan</p>
                        <p class="mt-1 text-2xl font-black text-rose-600 font-mono tracking-tight">Rp {{ formatCurrency(kpi.totalDeduction) }}</p>
                    </div>
                    <div class="w-11 h-11 bg-rose-50 rounded-2xl flex items-center justify-center text-rose-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                    </div>
                </div>
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5 flex items-center justify-between relative overflow-hidden">
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Otomatis</p>
                        <p class="mt-1 text-2xl font-black text-gray-900 tracking-tight">{{ kpi.countAuto }}</p>
                    </div>
                    <div class="w-11 h-11 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                </div>
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5 flex items-center justify-between relative overflow-hidden">
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Manual</p>
                        <p class="mt-1 text-2xl font-black text-gray-900 tracking-tight">{{ kpi.countManual }}</p>
                    </div>
                    <div class="w-11 h-11 bg-teal-50 rounded-2xl flex items-center justify-center text-teal-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-5 rounded-3xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-50">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-gray-700 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            Filter Otomatis
                        </span>
                        <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                            Auto Filter Aktif
                        </span>
                    </div>
                    <button
                        v-if="hasActiveFilters"
                        @click="resetFilters"
                        type="button"
                        class="text-xs text-rose-600 hover:text-rose-700 font-bold flex items-center gap-1 hover:underline cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Reset Filter
                    </button>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Bulan -->
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Bulan
                        </label>
                        <select v-model="filter.month" class="block w-full border-gray-200 bg-gray-50/50 rounded-xl shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-xs font-bold py-2.5 px-3 transition-all">
                            <option v-for="(name, index) in months" :key="index" :value="index + 1">{{ name }}</option>
                        </select>
                    </div>

                    <!-- Tahun -->
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Tahun
                        </label>
                        <select v-model="filter.year" class="block w-full border-gray-200 bg-gray-50/50 rounded-xl shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-xs font-bold py-2.5 px-3 transition-all">
                            <option v-for="y in [2024, 2025, 2026, 2027, 2028]" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>

                    <!-- Area / Wilayah Filter -->
                    <div v-if="can('payroll_view_all') || can('payroll_view_area')">
                        <label class="block text-xs font-bold text-gray-600 mb-1 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Area / Wilayah
                        </label>
                        <select v-model="filter.area_id" class="block w-full border-gray-200 bg-gray-50/50 rounded-xl shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-xs font-bold py-2.5 px-3 transition-all">
                            <option value="">Semua Wilayah</option>
                            <option v-for="area in (areas || [])" :key="area.id" :value="area.id">{{ area.name }}</option>
                        </select>
                    </div>

                    <!-- Pencarian Otomatis -->
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            Pencarian
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input
                                type="text"
                                v-model="filter.search"
                                placeholder="Cari karyawan / keterangan..."
                                class="block w-full pl-9 pr-8 border-gray-200 bg-gray-50/50 rounded-xl shadow-xs focus:ring-indigo-500 focus:border-indigo-500 text-xs font-medium py-2.5 transition-all"
                            >
                            <button
                                v-if="filter.search"
                                @click="filter.search = ''"
                                type="button"
                                class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                                title="Hapus pencarian"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200">
                <nav class="flex space-x-2 overflow-x-auto pb-1" aria-label="Tabs">
                    <button @click="setTab('semua')" :class="[filter.tab === 'semua' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'py-3 px-3 text-xs sm:text-sm font-bold border-b-2 transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap']">
                        <span>Semua</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-indigo-50 text-indigo-700">{{ counts?.semua ?? records.total }}</span>
                    </button>
                    <button @click="setTab('insentif')" :class="[filter.tab === 'insentif' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'py-3 px-3 text-xs sm:text-sm font-bold border-b-2 transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap']">
                        <span>Insentif</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-50 text-emerald-700">{{ counts?.insentif ?? 0 }}</span>
                    </button>
                    <button @click="setTab('potongan')" :class="[filter.tab === 'potongan' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'py-3 px-3 text-xs sm:text-sm font-bold border-b-2 transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap']">
                        <span>Potongan</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-rose-50 text-rose-700">{{ counts?.potongan ?? 0 }}</span>
                    </button>
                    <button @click="setTab('auto')" :class="[filter.tab === 'auto' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'py-3 px-3 text-xs sm:text-sm font-bold border-b-2 transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap']">
                        <span>Auto</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-blue-50 text-blue-700">{{ counts?.auto ?? 0 }}</span>
                    </button>
                    <button @click="setTab('manual')" :class="[filter.tab === 'manual' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'py-3 px-3 text-xs sm:text-sm font-bold border-b-2 transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap']">
                        <span>Manual</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-teal-50 text-teal-700">{{ counts?.manual ?? 0 }}</span>
                    </button>
                </nav>
            </div>

            <!-- Table -->
            <div class="bg-white shadow-sm border border-gray-100 rounded-3xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs min-w-[980px] divide-y divide-gray-100">
                        <thead class="bg-gray-50/80 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                            <tr>
                                <th scope="col" class="px-6 py-4">Karyawan &amp; Area</th>
                                <th scope="col" class="px-5 py-4">Tipe</th>
                                <th scope="col" class="px-5 py-4">Mode</th>
                                <th scope="col" class="px-5 py-4">Jumlah</th>
                                <th scope="col" class="px-6 py-4">Keterangan</th>
                                <th scope="col" class="px-5 py-4">Tanggal</th>
                                <th v-if="can('payroll_incentives_delete') || can('payroll_incentives') || can('payroll_manage')" scope="col" class="px-5 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 font-medium">
                            <tr v-for="record in records.data" :key="record.id" class="hover:bg-gray-50/70 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-bold text-gray-900 text-sm">{{ record.user_name }}</div>
                                    <div class="text-[11px] text-gray-500 flex items-center gap-1 mt-0.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                            📍 {{ record.area_name || 'Semua Area' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span :class="[record.record_type === 'Insentif' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-rose-50 text-rose-700 border border-rose-200', 'px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full']">
                                        {{ record.record_type }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span :class="[record.mode === 'Auto' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-teal-50 text-teal-700 border border-teal-200', 'px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full']">
                                        {{ record.mode }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap font-mono font-bold text-sm">
                                    <span :class="record.record_type === 'Insentif' ? 'text-emerald-600' : 'text-rose-600'">
                                        {{ record.record_type === 'Insentif' ? '+' : '-' }}Rp {{ formatCurrency(record.amount) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-xs text-gray-800 font-semibold leading-relaxed">{{ record.description }}</div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-xs text-gray-500 font-medium">
                                    {{ formatDate(record.date) }}
                                </td>
                                <td v-if="can('payroll_incentives_delete') || can('payroll_incentives') || can('payroll_manage')" class="px-5 py-4 whitespace-nowrap text-center text-xs font-medium">
                                    <button v-if="record.status === 'pending'" @click="deleteRecord(record)" class="text-rose-500 hover:text-rose-700 p-2 hover:bg-rose-50 rounded-xl transition-colors cursor-pointer" title="Hapus Data">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                    <span v-else class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">Sudah Cair</span>
                                </td>
                            </tr>
                            <tr v-if="records.data.length === 0">
                                <td :colspan="(can('payroll_incentives_delete') || can('payroll_incentives') || can('payroll_manage')) ? 7 : 6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                        <span class="text-sm font-bold text-gray-700">Tidak Ada Data Ditemukan</span>
                                        <p class="text-xs text-gray-400 mt-1">Belum ada insentif atau potongan di filter periode ini.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="records.links && records.links.length > 3" class="bg-white px-4 py-3 border-t border-gray-100 sm:px-6 flex justify-between items-center">
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Menampilkan <span class="font-medium">{{ records.from }}</span> sampai <span class="font-medium">{{ records.to }}</span> dari <span class="font-medium">{{ records.total }}</span> data
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <template v-for="(link, k) in records.links" :key="k">
                                    <button v-if="link.url" @click.prevent="goToPage(link.url)" v-html="link.label"
                                        :class="[link.active ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50', 'relative inline-flex items-center px-4 py-2 border text-sm font-medium']">
                                    </button>
                                    <span v-else v-html="link.label" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-300"></span>
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Tambah Manual -->
            <div v-if="showManualModal" class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showManualModal = false"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full border border-gray-100">
                        <div class="bg-gray-50/50 px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="text-lg leading-6 font-bold text-gray-900 flex items-center gap-2">
                                <span class="bg-indigo-100 text-indigo-600 p-1.5 rounded-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                </span>
                                Tambah Insentif / Potongan
                            </h3>
                            <button @click="showManualModal = false" class="text-gray-400 hover:text-gray-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        
                        <div class="px-6 py-5">
                            <form @submit.prevent="submitManual" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Jenis</label>
                                    <select v-model="manualForm.type" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                        <option value="incentive">Bonus / Insentif</option>
                                        <option value="deduction">Potongan</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Karyawan</label>
                                    <select v-model="manualForm.user_id" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                        <option value="" disabled>Pilih Karyawan</option>
                                        <option v-for="user in users" :key="user.id" :value="user.id">
                                            {{ user.name }} {{ user.area ? `(${user.area.name})` : '' }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tanggal</label>
                                    <input type="date" v-model="manualForm.date" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nominal (Rp)</label>
                                    <input type="number" v-model="manualForm.amount" min="1" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500 font-bold text-lg" placeholder="Contoh: 50000" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Kategori / Keterangan</label>
                                    <input list="category-list-global" v-model="manualForm.description" @input="handleDescriptionChange" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Pilih atau ketik keterangan..." required>
                                    <datalist id="category-list-global">
                                        <template v-if="manualForm.type === 'incentive'">
                                            <option v-for="cat in (payrollCategories || []).filter(c => c.type === 'incentive' && c.mode === 'manual')" :key="cat.id" :value="cat.name"></option>
                                        </template>
                                        <template v-else>
                                            <option v-for="cat in (payrollCategories || []).filter(c => c.type === 'deduction' && c.mode === 'manual')" :key="cat.id" :value="cat.name"></option>
                                        </template>
                                    </datalist>
                                </div>
                                
                                <div class="pt-4 flex justify-end gap-3">
                                    <button type="button" @click="showManualModal = false" class="bg-white py-2.5 px-5 border border-gray-300 rounded-xl shadow-sm text-sm font-bold text-gray-700 hover:bg-gray-50 transition-all">
                                        Batal
                                    </button>
                                    <button type="submit" :disabled="manualForm.processing" class="bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500 inline-flex justify-center py-2.5 px-5 border border-transparent shadow-sm text-sm font-bold rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all disabled:opacity-50">
                                        {{ manualForm.processing ? 'Menyimpan...' : 'Simpan Data' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';
import { usePermission } from '@/Composables/usePermission';

const { can } = usePermission();

const props = defineProps({
    records: Object,
    kpi: Object,
    filters: Object,
    users: Array,
    payrollCategories: Array,
    areas: Array,
    counts: Object,
});

const months = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const filter = reactive({
    tab: props.filters?.tab || 'semua',
    month: Number(props.filters?.month) || new Date().getMonth() + 1,
    year: Number(props.filters?.year) || new Date().getFullYear(),
    area_id: props.filters?.area_id || '',
    search: props.filters?.search || '',
});

const showManualModal = ref(false);

const openCreateModal = () => {
    manualForm.reset();
    manualForm.user_id = '';
    const now = new Date();
    const isCurrentPeriod = (now.getMonth() + 1 === Number(filter.month) && now.getFullYear() === Number(filter.year));
    manualForm.date = isCurrentPeriod 
        ? now.toISOString().split('T')[0] 
        : `${filter.year}-${String(filter.month).padStart(2, '0')}-01`;
    showManualModal.value = true;
};

const manualForm = useForm({
    type: 'incentive',
    user_id: '',
    amount: '',
    description: '',
    date: new Date().toISOString().split('T')[0],
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID').format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric'
    }).format(date);
};

const hasActiveFilters = computed(() => {
    return Boolean(
        filter.area_id !== '' ||
        filter.search.trim() !== '' ||
        Number(filter.month) !== (new Date().getMonth() + 1) ||
        Number(filter.year) !== new Date().getFullYear()
    );
});

const resetFilters = () => {
    filter.month = new Date().getMonth() + 1;
    filter.year = new Date().getFullYear();
    filter.area_id = '';
    filter.search = '';
    fetchData();
};

const fetchData = () => {
    router.get(route('insentif-potongan.index'), filter, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// ── Auto-Filter Watchers ──
let debounceTimer = null;
const applyFilter = (immediate = false) => {
    if (debounceTimer) clearTimeout(debounceTimer);
    if (immediate) {
        fetchData();
    } else {
        debounceTimer = setTimeout(() => {
            fetchData();
        }, 350);
    }
};

watch(() => filter.search, () => {
    applyFilter(false); // debounced 350ms
});

watch([() => filter.month, () => filter.year, () => filter.area_id], () => {
    applyFilter(true); // immediate
});

const setTab = (tab) => {
    filter.tab = tab;
    fetchData();
};

const goToPage = (url) => {
    if (!url) return;
    const urlObj = new URL(url);
    const page = urlObj.searchParams.get('page');
    router.get(route('insentif-potongan.index'), { ...filter, page }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const deleteRecord = (record) => {
    Swal.fire({
        title: `Hapus ${record.record_type}?`,
        text: `Apakah Anda yakin ingin menghapus ${record.record_type.toLowerCase()} untuk ${record.user_name} sejumlah Rp ${formatCurrency(record.amount)}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus Data',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b'
    }).then((res) => {
        if (res.isConfirmed) {
            router.delete(route('insentif-potongan.destroy'), {
                data: { id: record.id },
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: `${record.record_type} Dihapus!`,
                        text: `Data ${record.record_type.toLowerCase()} berhasil dihapus.`,
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                },
                onError: (err) => {
                    const msg = Object.values(err || {})[0] || 'Gagal menghapus data.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: msg,
                        confirmButtonColor: '#dc2626'
                    });
                }
            });
        }
    });
};

const handleDescriptionChange = () => {
    const category = (props.payrollCategories || []).find(c => c.name === manualForm.description && c.type === manualForm.type);
    if (category && category.default_amount > 0) {
        manualForm.amount = category.default_amount;
    }
};

const submitManual = () => {
    const isInc = manualForm.type === 'incentive';
    const routeName = isInc ? 'payroll.incentive.store' : 'payroll.deduction.store';
    
    // Kirim payload dengan mapping parameter yang sesuai backend (incentive_date/deduction_date & date)
    const payload = {
        amount: manualForm.amount,
        description: manualForm.description,
        incentive_date: manualForm.date,
        deduction_date: manualForm.date,
        date: manualForm.date,
    };

    router.post(route(routeName, manualForm.user_id), payload, {
        preserveScroll: true,
        onSuccess: () => {
            showManualModal.value = false;
            manualForm.reset();
            manualForm.user_id = '';
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Data Berhasil Disimpan!',
                text: `${isInc ? 'Insentif' : 'Potongan'} manual berhasil ditambahkan.`,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        },
        onError: (err) => {
            const msg = Object.values(err || {})[0] || 'Gagal menyimpan data manual.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: msg,
                confirmButtonColor: '#dc2626'
            });
        }
    });
};
</script>
