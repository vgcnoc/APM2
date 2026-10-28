<template>
    <AppLayout title="Master Fee Teknisi & Sales">
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
                            💰
                        </span>
                        <span>Master Fee</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">Kelola standar tarif komisi booking, survey, instalasi pasang baru, freelance, dan bonus target</p>
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        @click="openModal()" 
                        class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-extrabold text-xs rounded-xl shadow-sm hover:shadow transition-all cursor-pointer"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Tambah Fee</span>
                    </button>
                </div>
            </div>

            <!-- KPI Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Fee Booking -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 flex items-center justify-between hover:border-blue-300 transition-colors">
                    <div>
                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Fee Booking</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 font-mono">{{ stats.booking || 0 }}</p>
                        <p class="text-[11px] text-blue-600 font-bold mt-0.5">
                            Rp {{ formatCurrency(stats.booking_nominal || 0) }} /trx
                        </p>
                    </div>
                    <div class="w-11 h-11 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-xl shadow-xs border border-blue-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                </div>

                <!-- Fee Survey -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 flex items-center justify-between hover:border-orange-300 transition-colors">
                    <div>
                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Fee Survey</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 font-mono">{{ stats.survey || 0 }}</p>
                        <p class="text-[11px] text-orange-600 font-bold mt-0.5">
                            Rp {{ formatCurrency(stats.survey_nominal || 0) }} /trx
                        </p>
                    </div>
                    <div class="w-11 h-11 bg-orange-50 text-orange-600 rounded-2xl flex items-center justify-center text-xl shadow-xs border border-orange-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    </div>
                </div>

                <!-- Fee Pasang -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 flex items-center justify-between hover:border-emerald-300 transition-colors">
                    <div>
                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Fee Pasang</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 font-mono">{{ stats.pasang || 0 }}</p>
                        <p class="text-[11px] text-emerald-600 font-bold mt-0.5">
                            Rp {{ formatCurrency(stats.pasang_nominal || 0) }} /trx
                        </p>
                    </div>
                    <div class="w-11 h-11 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl shadow-xs border border-emerald-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    </div>
                </div>

                <!-- Fee Freelance -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 flex items-center justify-between hover:border-purple-300 transition-colors">
                    <div>
                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Freelance Paket</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 font-mono">{{ stats.freelance || 0 }}</p>
                        <p class="text-[11px] text-purple-600 font-bold mt-0.5">
                            Rp {{ formatCurrency(stats.freelance_nominal || 0) }} /trx
                        </p>
                    </div>
                    <div class="w-11 h-11 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center text-xl shadow-xs border border-purple-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                </div>

                <!-- Bonus Target Booking -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 flex items-center justify-between hover:border-rose-300 transition-colors">
                    <div>
                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Bonus Target</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 font-mono">{{ stats.target || 0 }}</p>
                        <p class="text-[11px] text-rose-600 font-bold mt-0.5">
                            Rp {{ formatCurrency(stats.target_nominal || 0) }}
                        </p>
                    </div>
                    <div class="w-11 h-11 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center text-xl shadow-xs border border-rose-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
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
                        placeholder="Cari nama atau keterangan fee..." 
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
                        @click="setTab('Fee Booking')" 
                        :class="[currentTab === 'Fee Booking' ? 'bg-white text-indigo-700 shadow-xs ring-1 ring-slate-200 font-black' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60 font-bold', 'px-3.5 py-1.5 text-xs rounded-xl transition-all cursor-pointer flex items-center gap-1.5']"
                    >
                        <span>Fee Booking</span>
                        <span class="px-1.5 py-0.5 rounded-md text-[10px]" :class="currentTab === 'Fee Booking' ? 'bg-blue-50 text-blue-700' : 'bg-slate-200/70 text-slate-600'">{{ stats.booking || 0 }}</span>
                    </button>

                    <button 
                        @click="setTab('Fee Survey')" 
                        :class="[currentTab === 'Fee Survey' ? 'bg-white text-indigo-700 shadow-xs ring-1 ring-slate-200 font-black' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60 font-bold', 'px-3.5 py-1.5 text-xs rounded-xl transition-all cursor-pointer flex items-center gap-1.5']"
                    >
                        <span>Fee Survey</span>
                        <span class="px-1.5 py-0.5 rounded-md text-[10px]" :class="currentTab === 'Fee Survey' ? 'bg-orange-50 text-orange-700' : 'bg-slate-200/70 text-slate-600'">{{ stats.survey || 0 }}</span>
                    </button>

                    <button 
                        @click="setTab('Fee Pasang')" 
                        :class="[currentTab === 'Fee Pasang' ? 'bg-white text-indigo-700 shadow-xs ring-1 ring-slate-200 font-black' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60 font-bold', 'px-3.5 py-1.5 text-xs rounded-xl transition-all cursor-pointer flex items-center gap-1.5']"
                    >
                        <span>Fee Pasang</span>
                        <span class="px-1.5 py-0.5 rounded-md text-[10px]" :class="currentTab === 'Fee Pasang' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-200/70 text-slate-600'">{{ stats.pasang || 0 }}</span>
                    </button>

                    <button 
                        @click="setTab('Fee Freelance per Paket')" 
                        :class="[currentTab === 'Fee Freelance per Paket' ? 'bg-white text-indigo-700 shadow-xs ring-1 ring-slate-200 font-black' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60 font-bold', 'px-3.5 py-1.5 text-xs rounded-xl transition-all cursor-pointer flex items-center gap-1.5']"
                    >
                        <span>Fee Freelance per Paket</span>
                        <span class="px-1.5 py-0.5 rounded-md text-[10px]" :class="currentTab === 'Fee Freelance per Paket' ? 'bg-purple-50 text-purple-700' : 'bg-slate-200/70 text-slate-600'">{{ stats.freelance || 0 }}</span>
                    </button>

                    <button 
                        @click="setTab('Bonus Target Booking')" 
                        :class="[currentTab === 'Bonus Target Booking' ? 'bg-white text-indigo-700 shadow-xs ring-1 ring-slate-200 font-black' : 'text-slate-600 hover:text-slate-900 hover:bg-white/60 font-bold', 'px-3.5 py-1.5 text-xs rounded-xl transition-all cursor-pointer flex items-center gap-1.5']"
                    >
                        <span>Bonus Target Booking</span>
                        <span class="px-1.5 py-0.5 rounded-md text-[10px]" :class="currentTab === 'Bonus Target Booking' ? 'bg-rose-50 text-rose-700' : 'bg-slate-200/70 text-slate-600'">{{ stats.target || 0 }}</span>
                    </button>
                </nav>
            </div>

            <!-- Table Card -->
            <div class="bg-white shadow-sm border border-slate-200/80 rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black text-slate-500 uppercase tracking-wider">Nama Fee</th>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black text-slate-500 uppercase tracking-wider">Tipe</th>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black text-slate-500 uppercase tracking-wider">Nominal Standar</th>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black text-slate-500 uppercase tracking-wider">Mode</th>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black text-slate-500 uppercase tracking-wider">Keterangan</th>
                                <th scope="col" class="px-6 py-4 text-center text-[11px] font-black text-slate-500 uppercase tracking-wider">Status Aktif</th>
                                <th scope="col" class="px-6 py-4 text-center text-[11px] font-black text-slate-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <tr v-for="fee in fees.data" :key="fee.id" class="hover:bg-slate-50/70 transition-colors">
                                <!-- Nama Fee -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-extrabold text-slate-900 uppercase text-sm tracking-tight">{{ fee.name }}</div>
                                    <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="fee.is_active ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                                        <span>{{ fee.is_active ? 'Sedang Digunakan' : 'Dinonaktifkan' }}</span>
                                    </div>
                                </td>

                                <!-- Tipe Fee -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getTypeBadgeClass(fee.type)" class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-xl border">
                                        {{ fee.type }}
                                    </span>
                                </td>

                                <!-- Nominal -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-black text-slate-900 font-mono text-sm">
                                        Rp {{ formatCurrency(fee.nominal) }}
                                    </span>
                                </td>

                                <!-- Mode -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span 
                                        :class="fee.mode === 'Auto' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-slate-100 text-slate-700 border-slate-200'" 
                                        class="px-2.5 py-1 inline-flex text-xs leading-4 font-bold rounded-xl items-center border"
                                    >
                                        <svg v-if="fee.mode === 'Auto'" class="w-3.5 h-3.5 mr-1 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        <svg v-else class="w-3.5 h-3.5 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11"/></svg>
                                        {{ fee.mode }}
                                    </span>
                                </td>

                                <!-- Keterangan -->
                                <td class="px-6 py-4">
                                    <div class="text-xs text-slate-500 max-w-xs truncate">{{ fee.description || '-' }}</div>
                                </td>

                                <!-- Toggle Aktif -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button 
                                        type="button"
                                        @click="toggleActive(fee)" 
                                        :class="[fee.is_active ? 'bg-indigo-600' : 'bg-slate-200', 'relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500']"
                                        :title="fee.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'"
                                    >
                                        <span class="sr-only">Toggle Status</span>
                                        <span 
                                            aria-hidden="true" 
                                            :class="[fee.is_active ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200']"
                                        ></span>
                                    </button>
                                </td>

                                <!-- Aksi -->
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex items-center justify-center gap-1">
                                        <button 
                                            @click="openModal(fee)" 
                                            class="text-indigo-600 hover:text-indigo-900 p-2 hover:bg-indigo-50 rounded-xl transition-all cursor-pointer"
                                            title="Edit Fee"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button 
                                            @click="deleteFee(fee)" 
                                            class="text-rose-600 hover:text-rose-900 p-2 hover:bg-rose-50 rounded-xl transition-all cursor-pointer"
                                            title="Hapus Fee"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!fees.data || fees.data.length === 0">
                                <td colspan="7" class="px-6 py-16 text-center text-slate-500">
                                    <div class="max-w-xs mx-auto space-y-3">
                                        <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 flex items-center justify-center text-2xl">
                                            🔍
                                        </div>
                                        <div class="font-extrabold text-slate-800 text-sm">Tidak ada master fee ditemukan</div>
                                        <p class="text-xs text-slate-400">
                                            {{ searchQuery || currentTab !== 'semua' ? 'Coba ubah kata kunci pencarian atau tab kategori.' : 'Belum ada data master fee yang ditambahkan.' }}
                                        </p>
                                        <div v-if="searchQuery || currentTab !== 'semua'" class="pt-1">
                                            <button 
                                                @click="resetAllFilters" 
                                                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-all cursor-pointer"
                                            >
                                                Tampilkan Semua
                                            </button>
                                        </div>
                                        <div v-else class="pt-1">
                                            <button 
                                                @click="openModal()" 
                                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition-all cursor-pointer"
                                            >
                                                + Tambah Fee Pertama
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="fees.links && fees.links.length > 3" class="bg-white px-4 py-3 border-t border-slate-100 sm:px-6 flex justify-between items-center">
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs text-slate-600 font-medium">
                                Menampilkan <span class="font-bold text-slate-900">{{ fees.from || 0 }}</span> sampai <span class="font-bold text-slate-900">{{ fees.to || 0 }}</span> dari <span class="font-bold text-slate-900">{{ fees.total || 0 }}</span> data
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-xl shadow-xs -space-x-px overflow-hidden border border-slate-200">
                                <template v-for="(link, k) in fees.links" :key="k">
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
                                    {{ editMode ? 'Edit Master Fee' : 'Tambah Master Fee Baru' }}
                                </h3>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    {{ editMode ? 'Perbarui nominal atau konfigurasi perhitungan fee' : 'Daftarkan aturan fee komisi atau bonus baru' }}
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
                                <!-- Nama Fee -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                        Nama Fee <span class="text-rose-500">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        v-model="form.name" 
                                        placeholder="Contoh: FEE PASANG STANDAR"
                                        class="block w-full text-xs sm:text-sm border-slate-300 rounded-xl shadow-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 uppercase font-bold text-slate-800" 
                                        required
                                    >
                                    <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600 font-medium">{{ form.errors.name }}</p>
                                </div>

                                <!-- Tipe & Mode -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                            Tipe Fee <span class="text-rose-500">*</span>
                                        </label>
                                        <select 
                                            v-model="form.type" 
                                            class="block w-full text-xs sm:text-sm border-slate-300 rounded-xl shadow-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 font-medium text-slate-800" 
                                            required
                                        >
                                            <option value="Fee Booking">Fee Booking</option>
                                            <option value="Fee Survey">Fee Survey</option>
                                            <option value="Fee Pasang">Fee Pasang</option>
                                            <option value="Fee Freelance per Paket">Fee Freelance per Paket</option>
                                            <option value="Bonus Target Booking">Bonus Target Booking</option>
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
                                            <option value="Auto">Auto (Otomatis)</option>
                                            <option value="Manual">Manual (Koreksi)</option>
                                        </select>
                                        <p v-if="form.errors.mode" class="mt-1 text-xs text-rose-600 font-medium">{{ form.errors.mode }}</p>
                                    </div>
                                </div>

                                <!-- Nominal -->
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                            Nominal Tarif (Rp) <span class="text-rose-500">*</span>
                                        </label>
                                        <span class="text-xs font-black text-indigo-600 font-mono">
                                            Rp {{ formatCurrency(form.nominal) }}
                                        </span>
                                    </div>
                                    <input 
                                        type="number" 
                                        v-model.number="form.nominal" 
                                        min="0" 
                                        step="500"
                                        placeholder="0"
                                        class="block w-full text-xs sm:text-sm border-slate-300 rounded-xl shadow-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 font-bold font-mono text-slate-900" 
                                        required
                                    >
                                    <p v-if="form.type === 'Fee Pasang'" class="mt-1.5 text-[11px] text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200">
                                        ⚡ <b>Ketentuan Insentif:</b> Jika pemasangan dilakukan oleh lebih dari 1 teknisi (misal 2 orang), nominal ini diberikan penuh untuk masing-masing teknisi (Rp {{ formatCurrency(form.nominal) }} per orang).
                                    </p>
                                    <p v-if="form.errors.nominal" class="mt-1 text-xs text-rose-600 font-medium">{{ form.errors.nominal }}</p>
                                </div>

                                <!-- Keterangan -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                        Keterangan / Catatan
                                    </label>
                                    <textarea 
                                        v-model="form.description" 
                                        rows="2" 
                                        placeholder="Opsional: Keterangan target, syarat paket, atau ketentuan komisi..."
                                        class="block w-full text-xs sm:text-sm border-slate-300 rounded-xl shadow-xs focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-slate-700"
                                    ></textarea>
                                    <p v-if="form.errors.description" class="mt-1 text-xs text-rose-600 font-medium">{{ form.errors.description }}</p>
                                </div>

                                <!-- Aktif Checkbox -->
                                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200/60 flex items-center justify-between">
                                    <div>
                                        <label for="is_active" class="block text-xs font-bold text-slate-800 cursor-pointer">Status Aktif</label>
                                        <p class="text-[11px] text-slate-500">Fee aktif akan otomatis dijadikan referensi perhitungan sistem</p>
                                    </div>
                                    <input 
                                        id="is_active" 
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
                                        <span>{{ form.processing ? 'Menyimpan...' : (editMode ? 'Simpan Perubahan' : 'Simpan Fee') }}</span>
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
    fees: Object,
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
    type: 'Fee Booking',
    mode: 'Auto',
    nominal: 0,
    description: '',
    is_active: true,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID').format(value || 0);
};

const getTypeBadgeClass = (type) => {
    switch(type) {
        case 'Fee Booking': 
            return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'Fee Survey': 
            return 'bg-orange-50 text-orange-700 border-orange-200';
        case 'Fee Pasang': 
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'Fee Freelance per Paket': 
            return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'Bonus Target Booking': 
            return 'bg-rose-50 text-rose-700 border-rose-200';
        default: 
            return 'bg-slate-100 text-slate-800 border-slate-200';
    }
};

const fetchData = () => {
    router.get(route('master-fees.index'), {
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
    router.get(route('master-fees.index'), {}, { preserveState: true, replace: true });
};

const goToPage = (url) => {
    if (!url) return;
    router.get(url, {
        tab: currentTab.value !== 'semua' ? currentTab.value : undefined,
        search: searchQuery.value || undefined,
    }, { preserveState: true, preserveScroll: true });
};

const openModal = (fee = null) => {
    form.clearErrors();
    if (fee) {
        editMode.value = true;
        form.id = fee.id;
        form.name = fee.name;
        form.type = fee.type;
        form.mode = fee.mode;
        form.nominal = Number(fee.nominal);
        form.description = fee.description || '';
        form.is_active = Boolean(fee.is_active);
    } else {
        editMode.value = false;
        form.reset();
        form.id = null;
        form.type = currentTab.value !== 'semua' ? currentTab.value : 'Fee Booking';
        form.mode = 'Auto';
        form.nominal = 0;
        form.description = '';
        form.is_active = true;
    }
    showModal.value = true;
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
        ? route('master-fees.update.post', form.id)
        : route('master-fees.store');

    form.post(url, {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Berhasil!',
                text: editMode.value ? 'Master fee berhasil diperbarui.' : 'Master fee baru berhasil ditambahkan.',
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

const toggleActive = (fee) => {
    router.post(route('master-fees.toggle', fee.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Status Diperbarui',
                text: `Status master fee ${fee.name} berhasil diperbarui.`,
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
                text: 'Terjadi kesalahan saat memperbarui status fee.',
                showConfirmButton: false,
                timer: 2500,
            });
        }
    });
};

const deleteFee = (fee) => {
    Swal.fire({
        title: 'Hapus Master Fee?',
        html: `Apakah Anda yakin ingin menghapus fee <strong>"${fee.name}"</strong> (Rp ${formatCurrency(fee.nominal)})?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus Fee',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('master-fees.destroy.post', fee.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Berhasil Dihapus',
                        text: `Master fee ${fee.name} berhasil dihapus.`,
                        showConfirmButton: false,
                        timer: 2500,
                        timerProgressBar: true
                    });
                },
                onError: (err) => {
                    const firstErr = Object.values(err || {})[0] || 'Tidak dapat menghapus fee.';
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
