<template>
    <AppLayout title="Master Insentif & Potongan">
        <div class="max-w-[1600px] w-full mx-auto space-y-6 font-sans py-2 px-4 sm:px-6 lg:px-8">
            <!-- Flash message banner -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-2 shadow-xs">
                <span>✅</span>
                <span>{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash?.error" class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-2 shadow-xs">
                <span>⚠️</span>
                <span>{{ $page.props.flash.error }}</span>
            </div>

            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
                        <span class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100 shadow-xs text-xl">
                            ⚖️
                        </span>
                        <span>Master Insentif & Potongan</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">Kelola pos kategori insentif pendapatan tambahan dan potongan gaji karyawan</p>
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        @click="openModal()" 
                        class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-extrabold text-xs rounded-xl shadow-sm hover:shadow transition-all cursor-pointer"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Tambah Kategori</span>
                    </button>
                </div>
            </div>

            <!-- KPI Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Kategori -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 flex items-center justify-between hover:border-indigo-300 transition-colors">
                    <div>
                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Total Kategori</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 font-mono">{{ stats.total || 0 }}</p>
                        <p class="text-[11px] text-slate-400 font-medium mt-0.5">Semua pos insentif & potongan</p>
                    </div>
                    <div class="w-11 h-11 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center text-xl shadow-xs border border-indigo-100">
                        📑
                    </div>
                </div>

                <!-- Total Insentif -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 flex items-center justify-between hover:border-emerald-300 transition-colors">
                    <div>
                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Jenis Insentif</p>
                        <p class="mt-1 text-2xl font-black text-emerald-600 font-mono">{{ stats.total_incentive || 0 }}</p>
                        <p class="text-[11px] text-emerald-600 font-bold mt-0.5">Penambah gaji / komisi</p>
                    </div>
                    <div class="w-11 h-11 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl shadow-xs border border-emerald-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                </div>

                <!-- Total Potongan -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 flex items-center justify-between hover:border-rose-300 transition-colors">
                    <div>
                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Jenis Potongan</p>
                        <p class="mt-1 text-2xl font-black text-rose-600 font-mono">{{ stats.total_deduction || 0 }}</p>
                        <p class="text-[11px] text-rose-600 font-bold mt-0.5">Pengurang kasbon / denda</p>
                    </div>
                    <div class="w-11 h-11 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center text-xl shadow-xs border border-rose-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                    </div>
                </div>

                <!-- Kategori Aktif -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 flex items-center justify-between hover:border-blue-300 transition-colors">
                    <div>
                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Kategori Aktif</p>
                        <p class="mt-1 text-2xl font-black text-blue-600 font-mono">{{ stats.active || 0 }}</p>
                        <p class="text-[11px] text-blue-600 font-bold mt-0.5">Siap digunakan payroll</p>
                    </div>
                    <div class="w-11 h-11 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-xl shadow-xs border border-blue-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>

            <!-- Filters & Search -->
            <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3">
                <div class="relative w-full sm:max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input 
                        type="text" 
                        v-model="searchQuery" 
                        @input="onSearch"
                        placeholder="Cari nama atau keterangan kategori..." 
                        class="block w-full pl-10 pr-9 py-2 text-xs sm:text-sm bg-white border border-slate-300 rounded-xl shadow-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium text-slate-800 placeholder-slate-400"
                    >
                    <button 
                        v-if="searchQuery" 
                        @click="resetSearch" 
                        type="button" 
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer text-xs"
                    >
                        ✕
                    </button>
                </div>

                <div v-if="searchQuery || currentTab !== 'semua'" class="flex items-center gap-2">
                    <button 
                        @click="resetAllFilters" 
                        class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Reset Filter</span>
                    </button>
                </div>
            </div>

            <!-- Tabs -->
            <div class="overflow-x-auto pb-1">
                <nav class="flex space-x-1.5 whitespace-nowrap bg-slate-100/80 p-1.5 rounded-2xl w-fit border border-slate-200/60" aria-label="Tabs">
                    <button 
                        @click="setTab('semua')" 
                        :class="[currentTab === 'semua' ? 'bg-white text-indigo-700 shadow-xs ring-1 ring-slate-200 font-black' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60 font-bold', 'px-3.5 py-1.5 text-xs rounded-xl transition-all cursor-pointer flex items-center gap-1.5']"
                    >
                        <span>Semua</span>
                        <span class="px-1.5 py-0.5 rounded-md text-[10px]" :class="currentTab === 'semua' ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-200/70 text-slate-600'">{{ stats.total || 0 }}</span>
                    </button>

                    <button 
                        @click="setTab('incentive')" 
                        :class="[currentTab === 'incentive' ? 'bg-white text-emerald-700 shadow-xs ring-1 ring-slate-200 font-black' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60 font-bold', 'px-3.5 py-1.5 text-xs rounded-xl transition-all cursor-pointer flex items-center gap-1.5']"
                    >
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Insentif</span>
                        <span class="px-1.5 py-0.5 rounded-md text-[10px]" :class="currentTab === 'incentive' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-200/70 text-slate-600'">{{ stats.total_incentive || 0 }}</span>
                    </button>

                    <button 
                        @click="setTab('deduction')" 
                        :class="[currentTab === 'deduction' ? 'bg-white text-rose-700 shadow-xs ring-1 ring-slate-200 font-black' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60 font-bold', 'px-3.5 py-1.5 text-xs rounded-xl transition-all cursor-pointer flex items-center gap-1.5']"
                    >
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        <span>Potongan</span>
                        <span class="px-1.5 py-0.5 rounded-md text-[10px]" :class="currentTab === 'deduction' ? 'bg-rose-50 text-rose-700' : 'bg-slate-200/70 text-slate-600'">{{ stats.total_deduction || 0 }}</span>
                    </button>
                </nav>
            </div>

            <!-- Table Card -->
            <div class="bg-white shadow-sm border border-slate-200/80 rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black text-slate-500 uppercase tracking-wider">Nama Kategori</th>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black text-slate-500 uppercase tracking-wider">Tipe</th>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black text-slate-500 uppercase tracking-wider">Mode</th>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black text-slate-500 uppercase tracking-wider">Nominal Default</th>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black text-slate-500 uppercase tracking-wider">Keterangan</th>
                                <th scope="col" class="px-6 py-4 text-center text-[11px] font-black text-slate-500 uppercase tracking-wider">Status Aktif</th>
                                <th scope="col" class="px-6 py-4 text-center text-[11px] font-black text-slate-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <tr v-for="cat in categories.data" :key="cat.id" class="hover:bg-slate-50/70 transition-colors">
                                <!-- Nama Kategori -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-extrabold text-slate-900 text-sm tracking-tight">{{ cat.name }}</div>
                                    <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="cat.is_active ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                                        <span>{{ cat.is_active ? 'Aktif dalam Sistem' : 'Nonaktif' }}</span>
                                    </div>
                                </td>

                                <!-- Tipe -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span 
                                        :class="cat.type === 'incentive' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'" 
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-extrabold rounded-xl border items-center gap-1"
                                    >
                                        <span v-if="cat.type === 'incentive'">➕ Insentif</span>
                                        <span v-else>➖ Potongan</span>
                                    </span>
                                </td>

                                <!-- Mode -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span 
                                        :class="cat.mode === 'auto' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-slate-100 text-slate-700 border-slate-200'" 
                                        class="px-2.5 py-1 inline-flex text-xs leading-4 font-bold rounded-xl items-center border"
                                    >
                                        <svg v-if="cat.mode === 'auto'" class="w-3.5 h-3.5 mr-1 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        <svg v-else class="w-3.5 h-3.5 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11"/></svg>
                                        {{ cat.mode === 'auto' ? 'Auto' : 'Manual' }}
                                    </span>
                                </td>

                                <!-- Nominal Default -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="cat.default_amount > 0" class="font-black text-slate-900 font-mono text-sm">
                                        Rp {{ formatCurrency(cat.default_amount) }}
                                    </span>
                                    <span v-else class="text-xs text-slate-400 italic">
                                        Fleksibel (Saat input)
                                    </span>
                                </td>

                                <!-- Keterangan -->
                                <td class="px-6 py-4">
                                    <div class="text-xs text-slate-500 max-w-xs truncate">{{ cat.description || '-' }}</div>
                                </td>

                                <!-- Toggle Aktif -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button 
                                        type="button"
                                        @click="toggleActive(cat)" 
                                        :class="[cat.is_active ? 'bg-indigo-600' : 'bg-slate-200', 'relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500']"
                                        :title="cat.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'"
                                    >
                                        <span class="sr-only">Toggle Status</span>
                                        <span 
                                            aria-hidden="true" 
                                            :class="[cat.is_active ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200']"
                                        ></span>
                                    </button>
                                </td>

                                <!-- Aksi -->
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex items-center justify-center gap-1">
                                        <button 
                                            @click="openModal(cat)" 
                                            class="text-indigo-600 hover:text-indigo-900 p-2 hover:bg-indigo-50 rounded-xl transition-all cursor-pointer"
                                            title="Edit Kategori"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button 
                                            @click="deleteCategory(cat)" 
                                            class="text-rose-600 hover:text-rose-900 p-2 hover:bg-rose-50 rounded-xl transition-all cursor-pointer"
                                            title="Hapus Kategori"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!categories.data || categories.data.length === 0">
                                <td colspan="7" class="px-6 py-16 text-center text-slate-500">
                                    <div class="max-w-md mx-auto space-y-3">
                                        <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 flex items-center justify-center text-2xl">
                                            ⚖️
                                        </div>
                                        <div class="font-extrabold text-slate-800 text-sm">Tidak ada data kategori insentif & potongan</div>
                                        <p class="text-xs text-slate-400">
                                            {{ searchQuery || currentTab !== 'semua' ? 'Coba ubah kata kunci pencarian atau tab kategori.' : 'Belum ada kategori yang didaftarkan. Anda dapat menambahkan pos insentif/potongan manual pertama.' }}
                                        </p>
                                        <div v-if="searchQuery || currentTab !== 'semua'" class="pt-1">
                                            <button 
                                                @click="resetAllFilters" 
                                                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-all cursor-pointer"
                                            >
                                                Tampilkan Semua
                                            </button>
                                        </div>
                                        <div v-else class="pt-2 flex flex-wrap justify-center gap-2">
                                            <button 
                                                @click="openModalWithPreset('incentive', 'Insentif Lembur')" 
                                                class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 font-bold text-xs rounded-xl transition-all cursor-pointer"
                                            >
                                                + Insentif Lembur
                                            </button>
                                            <button 
                                                @click="openModalWithPreset('deduction', 'Kasbon Karyawan')" 
                                                class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs rounded-xl transition-all cursor-pointer"
                                            >
                                                + Kasbon Karyawan
                                            </button>
                                            <button 
                                                @click="openModal()" 
                                                class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition-all cursor-pointer"
                                            >
                                                + Tambah Custom
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="categories.links && categories.links.length > 3" class="bg-white px-4 py-3 border-t border-slate-100 sm:px-6 flex justify-between items-center">
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs text-slate-600 font-medium">
                                Menampilkan <span class="font-bold text-slate-900">{{ categories.from || 0 }}</span> sampai <span class="font-bold text-slate-900">{{ categories.to || 0 }}</span> dari <span class="font-bold text-slate-900">{{ categories.total || 0 }}</span> data
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-xl shadow-xs -space-x-px overflow-hidden border border-slate-200">
                                <template v-for="(link, k) in categories.links" :key="k">
                                    <button 
                                        v-if="link.url" 
                                        @click.prevent="goToPage(link.url)" 
                                        v-html="link.label"
                                        :class="[link.active ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-700 font-bold' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50', 'relative inline-flex items-center px-3.5 py-1.5 border text-xs cursor-pointer transition-colors']"
                                    ></button>
                                    <span 
                                        v-else 
                                        v-html="link.label" 
                                        class="relative inline-flex items-center px-3.5 py-1.5 border border-slate-200 bg-slate-50 text-xs text-slate-300 select-none"
                                    ></span>
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Form (Teleported to body) -->
            <Teleport to="body">
                <div 
                    v-if="showModal" 
                    class="fixed inset-0 z-[100] overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" 
                    aria-labelledby="modal-title" 
                    role="dialog" 
                    aria-modal="true"
                >
                    <div class="fixed inset-0" @click="closeModal"></div>
                    
                    <div class="relative bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-md w-full border border-slate-100 z-10">
                        <!-- Modal Header -->
                        <div class="bg-slate-50/70 px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-extrabold text-slate-900">
                                    {{ editMode ? 'Edit Kategori Payroll' : 'Tambah Kategori Baru' }}
                                </h3>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    {{ editMode ? 'Perbarui informasi pos insentif atau potongan' : 'Daftarkan jenis insentif atau potongan baru untuk karyawan' }}
                                </p>
                            </div>
                            <button 
                                @click="closeModal" 
                                class="text-slate-400 hover:text-slate-600 p-1 rounded-xl hover:bg-slate-100 transition-colors cursor-pointer"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        
                        <!-- Modal Body -->
                        <div class="px-6 py-5">
                            <form @submit.prevent="submit" class="space-y-4">
                                <!-- Quick Presets Suggestions (Create mode only) -->
                                <div v-if="!editMode" class="space-y-1.5">
                                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                        Rekomendasi Cepat (Klik untuk memilih):
                                    </label>
                                    <div class="flex flex-wrap gap-1.5">
                                        <template v-if="form.type === 'incentive'">
                                            <button 
                                                v-for="preset in ['Insentif Lembur', 'Insentif On-Call', 'Bonus Target Bulanan', 'Tunjangan Khusus']" 
                                                :key="preset"
                                                type="button" 
                                                @click="form.name = preset"
                                                class="px-2.5 py-1 text-[11px] font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition-colors cursor-pointer"
                                            >
                                                {{ preset }}
                                            </button>
                                        </template>
                                        <template v-else>
                                            <button 
                                                v-for="preset in ['Kasbon Karyawan', 'Potongan Keterlambatan', 'Ganti Rugi Material / Alat', 'Iuran BPJS Mandiri']" 
                                                :key="preset"
                                                type="button" 
                                                @click="form.name = preset"
                                                class="px-2.5 py-1 text-[11px] font-semibold bg-rose-50 text-rose-700 hover:bg-rose-100 rounded-lg border border-rose-200 transition-colors cursor-pointer"
                                            >
                                                {{ preset }}
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <!-- Nama Kategori -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                        Nama Kategori <span class="text-rose-500">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        v-model="form.name" 
                                        placeholder="Contoh: Insentif Lembur / Kasbon Karyawan"
                                        class="block w-full text-xs sm:text-sm border-slate-300 rounded-xl shadow-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 font-bold text-slate-800" 
                                        required
                                    >
                                    <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600 font-medium">{{ form.errors.name }}</p>
                                </div>

                                <!-- Tipe & Mode -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                            Tipe <span class="text-rose-500">*</span>
                                        </label>
                                        <select 
                                            v-model="form.type" 
                                            class="block w-full text-xs sm:text-sm border-slate-300 rounded-xl shadow-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 font-bold text-slate-800" 
                                            required
                                        >
                                            <option value="incentive">➕ Insentif (Penambah)</option>
                                            <option value="deduction">➖ Potongan (Pengurang)</option>
                                        </select>
                                        <p v-if="form.errors.type" class="mt-1 text-xs text-rose-600 font-medium">{{ form.errors.type }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                            Mode Perhitungan <span class="text-rose-500">*</span>
                                        </label>
                                        <select 
                                            v-model="form.mode" 
                                            class="block w-full text-xs sm:text-sm border-slate-300 rounded-xl shadow-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 font-medium text-slate-800" 
                                            required
                                        >
                                            <option value="manual">Manual (Input saat transaksi)</option>
                                            <option value="auto">Auto (Dihitung sistem)</option>
                                        </select>
                                        <p v-if="form.errors.mode" class="mt-1 text-xs text-rose-600 font-medium">{{ form.errors.mode }}</p>
                                    </div>
                                </div>

                                <!-- Nominal Default -->
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                            Nominal Default (Rp)
                                        </label>
                                        <span class="text-xs font-black text-indigo-600 font-mono">
                                            Rp {{ formatCurrency(form.default_amount) }}
                                        </span>
                                    </div>
                                    <input 
                                        type="number" 
                                        v-model.number="form.default_amount" 
                                        min="0" 
                                        step="1000"
                                        placeholder="0"
                                        class="block w-full text-xs sm:text-sm border-slate-300 rounded-xl shadow-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 font-bold font-mono text-slate-900" 
                                    >
                                    <p class="text-[11px] text-slate-400 mt-1">Isi 0 jika besaran nominal selalu bervariasi saat penginputan transaksi.</p>
                                    <p v-if="form.errors.default_amount" class="mt-1 text-xs text-rose-600 font-medium">{{ form.errors.default_amount }}</p>
                                </div>

                                <!-- Keterangan -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                        Keterangan / Deskripsi
                                    </label>
                                    <textarea 
                                        v-model="form.description" 
                                        rows="2" 
                                        placeholder="Opsional: Penjelasan aturan atau ketentuan pos ini..."
                                        class="block w-full text-xs sm:text-sm border-slate-300 rounded-xl shadow-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-700"
                                    ></textarea>
                                    <p v-if="form.errors.description" class="mt-1 text-xs text-rose-600 font-medium">{{ form.errors.description }}</p>
                                </div>

                                <!-- Status Aktif -->
                                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200/60 flex items-center justify-between">
                                    <div>
                                        <label for="is_active_cat" class="block text-xs font-bold text-slate-800 cursor-pointer">Status Aktif</label>
                                        <p class="text-[11px] text-slate-500">Kategori aktif dapat langsung dipilih pada menu transaksi insentif & potongan</p>
                                    </div>
                                    <input 
                                        id="is_active_cat" 
                                        type="checkbox" 
                                        v-model="form.is_active" 
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-300 rounded cursor-pointer"
                                    >
                                </div>
                                
                                <!-- Modal Actions -->
                                <div class="pt-3 flex justify-end gap-2.5">
                                    <button 
                                        type="button" 
                                        @click="closeModal" 
                                        class="bg-white py-2 px-4 border border-slate-300 rounded-xl shadow-xs text-xs font-extrabold text-slate-700 hover:bg-slate-50 transition-all cursor-pointer"
                                    >
                                        Batal
                                    </button>
                                    <button 
                                        type="submit" 
                                        :disabled="form.processing" 
                                        class="bg-indigo-600 hover:bg-indigo-700 active:scale-95 inline-flex items-center justify-center py-2 px-5 border border-transparent shadow-xs text-xs font-extrabold rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all disabled:opacity-50 cursor-pointer"
                                    >
                                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>{{ form.processing ? 'Menyimpan...' : (editMode ? 'Simpan Perubahan' : 'Simpan Kategori') }}</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

const props = defineProps({
    categories: Object,
    stats: Object,
    filters: Object,
});

const currentTab = ref(props.filters?.tab || 'semua');
const searchQuery = ref(props.filters?.search || '');
const showModal = ref(false);
const editMode = ref(false);

const form = useForm({
    id: null,
    name: '',
    type: 'incentive',
    mode: 'manual',
    default_amount: 0,
    description: '',
    is_active: true,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID').format(value || 0);
};

const fetchData = () => {
    router.get(route('payroll-categories.index'), {
        tab: currentTab.value !== 'semua' ? currentTab.value : undefined,
        search: searchQuery.value || undefined,
    }, { 
        preserveState: true, 
        replace: true 
    });
};

const onSearch = debounce(() => {
    fetchData();
}, 300);

const resetSearch = () => {
    searchQuery.value = '';
    fetchData();
};

const setTab = (tab) => {
    currentTab.value = tab;
    fetchData();
};

const resetAllFilters = () => {
    currentTab.value = 'semua';
    searchQuery.value = '';
    router.get(route('payroll-categories.index'), {}, { preserveState: true, replace: true });
};

const goToPage = (url) => {
    if (!url) return;
    router.get(url, {
        tab: currentTab.value !== 'semua' ? currentTab.value : undefined,
        search: searchQuery.value || undefined,
    }, { preserveState: true, preserveScroll: true });
};

const openModal = (cat = null) => {
    form.clearErrors();
    if (cat) {
        editMode.value = true;
        form.id = cat.id;
        form.name = cat.name;
        form.type = cat.type;
        form.mode = cat.mode;
        form.default_amount = Number(cat.default_amount || 0);
        form.description = cat.description || '';
        form.is_active = Boolean(cat.is_active);
    } else {
        editMode.value = false;
        form.reset();
        form.id = null;
        form.type = currentTab.value !== 'semua' ? currentTab.value : 'incentive';
        form.mode = 'manual';
        form.default_amount = 0;
        form.description = '';
        form.is_active = true;
    }
    showModal.value = true;
};

const openModalWithPreset = (type, name) => {
    openModal();
    form.type = type;
    form.name = name;
};

const closeModal = () => {
    showModal.value = false;
    setTimeout(() => {
        form.reset();
        form.clearErrors();
        editMode.value = false;
    }, 200);
};

const submit = () => {
    const url = editMode.value && form.id
        ? route('payroll-categories.update.post', form.id)
        : route('payroll-categories.store');

    form.post(url, {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Berhasil!',
                text: editMode.value ? 'Kategori berhasil diperbarui.' : 'Kategori baru berhasil ditambahkan.',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        },
        onError: (err) => {
            const firstErr = Object.values(err || {})[0] || 'Periksa kembali formulir input.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: firstErr,
                confirmButtonColor: '#4f46e5'
            });
        }
    });
};

const toggleActive = (cat) => {
    router.post(route('payroll-categories.toggle', cat.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Status Diperbarui',
                text: `Status kategori ${cat.name} berhasil diperbarui.`,
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
        },
        onError: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'Gagal',
                text: 'Terjadi kesalahan saat memperbarui status kategori.',
                showConfirmButton: false,
                timer: 2500,
            });
        }
    });
};

const deleteCategory = (cat) => {
    Swal.fire({
        title: 'Hapus Kategori?',
        html: `Apakah Anda yakin ingin menghapus kategori <strong>"${cat.name}"</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus Kategori',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('payroll-categories.destroy.post', cat.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Berhasil Dihapus',
                        text: `Kategori ${cat.name} berhasil dihapus.`,
                        showConfirmButton: false,
                        timer: 2500,
                        timerProgressBar: true
                    });
                },
                onError: (err) => {
                    const firstErr = Object.values(err || {})[0] || 'Tidak dapat menghapus kategori.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menghapus',
                        text: firstErr,
                        confirmButtonColor: '#e11d48'
                    });
                }
            });
        }
    });
};
</script>
