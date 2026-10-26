<template>
    <AppLayout title="CBP / Cabut Perangkat" subtitle="Manajemen Pencabutan dan Stop Permanen Layanan">
        <div class="space-y-5">
            <!-- Stats Overview Cards (Canggih & Interaktif) -->
            <div v-if="can('cbp_card_stats')" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
                <!-- Card 1: Semua Data -->
                <div 
                    @click="filterByStatus('all')" 
                    class="bg-white p-4 rounded-2xl border transition-all duration-200 cursor-pointer hover:shadow-md relative overflow-hidden group select-none" 
                    :class="(!filterStatus || filterStatus === 'all') ? 'border-slate-800 ring-2 ring-slate-200/80 shadow-sm bg-gradient-to-br from-white to-slate-50' : 'border-gray-100 hover:border-slate-300'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black text-slate-500 uppercase tracking-wider">Total CBP</span>
                        <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600 group-hover:scale-110 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 mt-2 tracking-tight">{{ counts?.all || 0 }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5 truncate">Semua tiket cabut</div>
                </div>

                <!-- Card 2: Menunggu Disposisi -->
                <div 
                    @click="filterByStatus('not_sent')" 
                    class="bg-white p-4 rounded-2xl border transition-all duration-200 cursor-pointer hover:shadow-md relative overflow-hidden group select-none" 
                    :class="filterStatus === 'not_sent' ? 'border-amber-500 ring-2 ring-amber-100 shadow-sm bg-gradient-to-br from-white to-amber-50/30' : 'border-gray-100 hover:border-amber-300'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black text-amber-700 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            Menunggu
                        </span>
                        <div class="w-7 h-7 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600 group-hover:scale-110 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-amber-600 mt-2 tracking-tight">{{ counts?.not_sent || 0 }}</div>
                    <div class="text-[10px] text-amber-600/70 mt-0.5 truncate">Belum ke jadwal</div>
                </div>

                <!-- Card 3: Sudah Masuk Jadwal -->
                <div 
                    @click="filterByStatus('sent_to_schedule')" 
                    class="bg-white p-4 rounded-2xl border transition-all duration-200 cursor-pointer hover:shadow-md relative overflow-hidden group select-none" 
                    :class="filterStatus === 'sent_to_schedule' ? 'border-sky-500 ring-2 ring-sky-100 shadow-sm bg-gradient-to-br from-white to-sky-50/30' : 'border-gray-100 hover:border-sky-300'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black text-sky-700 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-sky-500 animate-pulse"></span>
                            Di Jadwal
                        </span>
                        <div class="w-7 h-7 rounded-lg bg-sky-50 flex items-center justify-center text-sky-600 group-hover:scale-110 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-sky-600 mt-2 tracking-tight">{{ counts?.sent_to_schedule || 0 }}</div>
                    <div class="text-[10px] text-sky-600/70 mt-0.5 truncate">Siap penugasan</div>
                </div>

                <!-- Card 4: Ditugaskan (Assigned) -->
                <div 
                    @click="filterByStatus('assigned')" 
                    class="bg-white p-4 rounded-2xl border transition-all duration-200 cursor-pointer hover:shadow-md relative overflow-hidden group select-none" 
                    :class="filterStatus === 'assigned' ? 'border-blue-500 ring-2 ring-blue-100 shadow-sm bg-gradient-to-br from-white to-blue-50/30' : 'border-gray-100 hover:border-blue-300'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black text-blue-700 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            Ditugaskan
                        </span>
                        <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 group-hover:scale-110 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-blue-600 mt-2 tracking-tight">{{ counts?.assigned || 0 }}</div>
                    <div class="text-[10px] text-blue-600/70 mt-0.5 truncate">Proses teknisi</div>
                </div>

                <!-- Card 5: Selesai (Completed) -->
                <div 
                    @click="filterByStatus('completed')" 
                    class="bg-white p-4 rounded-2xl border transition-all duration-200 cursor-pointer hover:shadow-md relative overflow-hidden group select-none" 
                    :class="filterStatus === 'completed' ? 'border-emerald-500 ring-2 ring-emerald-100 shadow-sm bg-gradient-to-br from-white to-emerald-50/30' : 'border-gray-100 hover:border-emerald-300'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black text-emerald-700 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Selesai
                        </span>
                        <div class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-emerald-600 mt-2 tracking-tight">{{ counts?.completed || 0 }}</div>
                    <div class="text-[10px] text-emerald-600/70 mt-0.5 truncate">Perangkat dicabut</div>
                </div>

                <!-- Card 6: Dibatalkan (Canceled) -->
                <div 
                    @click="filterByStatus('canceled')" 
                    class="bg-white p-4 rounded-2xl border transition-all duration-200 cursor-pointer hover:shadow-md relative overflow-hidden group select-none" 
                    :class="filterStatus === 'canceled' ? 'border-rose-500 ring-2 ring-rose-100 shadow-sm bg-gradient-to-br from-white to-rose-50/30' : 'border-gray-100 hover:border-rose-300'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black text-rose-700 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            Dibatalkan
                        </span>
                        <div class="w-7 h-7 rounded-lg bg-rose-50 flex items-center justify-center text-rose-600 group-hover:scale-110 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-rose-600 mt-2 tracking-tight">{{ counts?.canceled || 0 }}</div>
                    <div class="text-[10px] text-rose-600/70 mt-0.5 truncate">Batal cabut</div>
                </div>
            </div>

            <!-- Toolbar & Filter Canggih -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 space-y-3">
                <div class="flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-3">
                    <!-- Left: Search & Filter Controls -->
                    <div v-if="can('cbp_filter')" class="flex flex-wrap items-center gap-2.5 flex-1">
                        <!-- Search Box with Clear Button -->
                        <div class="relative flex-1 min-w-[240px] sm:max-w-xs">
                            <input 
                                type="text" 
                                v-model="search" 
                                placeholder="Cari ID CBP, pelanggan, HP, ODP..." 
                                class="w-full pl-9 pr-8 py-2 rounded-xl border border-gray-200 focus:border-red-500 focus:ring-2 focus:ring-red-100 text-xs transition-all placeholder:text-gray-400 font-medium"
                                @keyup.enter="performSearch"
                            >
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <button v-if="search" @click="search = ''; performSearch()" class="absolute right-2.5 top-2.5 text-gray-400 hover:text-gray-600">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        
                        <!-- Status Filter Dropdown -->
                        <select v-model="filterStatus" @change="performSearch" class="px-3 py-2 rounded-xl border border-gray-200 focus:border-red-500 focus:ring-2 focus:ring-red-100 text-xs bg-gray-50/60 font-semibold text-slate-700 cursor-pointer">
                            <option value="">Semua Status</option>
                            <option value="not_sent">⏳ Menunggu Disposisi (Belum ke Jadwal)</option>
                            <option value="sent_to_schedule">📅 Sudah Masuk Jadwal CBP</option>
                            <option value="pending">🟡 Semua Pending</option>
                            <option value="assigned">🔵 Dalam Proses (Assigned)</option>
                            <option value="completed">🟢 Selesai (Completed)</option>
                            <option value="canceled">🔴 Dibatalkan</option>
                        </select>

                        <!-- Area Filter Dropdown -->
                        <select v-model="filterArea" @change="performSearch" class="px-3 py-2 rounded-xl border border-gray-200 focus:border-red-500 focus:ring-2 focus:ring-red-100 text-xs bg-gray-50/60 font-semibold text-slate-700 cursor-pointer">
                            <option value="">Semua Wilayah / Area</option>
                            <option v-for="area in areas" :key="area.id" :value="area.id">📍 {{ area.name }}</option>
                        </select>

                        <!-- Periode Filter Dropdown -->
                        <select v-model="filterPeriod" @change="performSearch" class="px-3 py-2 rounded-xl border border-gray-200 focus:border-red-500 focus:ring-2 focus:ring-red-100 text-xs bg-gray-50/60 font-semibold text-slate-700 cursor-pointer">
                            <option value="">Semua Periode</option>
                            <option value="today">Hari Ini</option>
                            <option value="this_week">Minggu Ini</option>
                            <option value="this_month">Bulan Ini</option>
                            <option value="last_month">Bulan Lalu</option>
                            <option value="custom">Rentang Tanggal (Custom)...</option>
                        </select>

                        <!-- Reset Filter Button -->
                        <button 
                            v-if="hasActiveFilters" 
                            @click="resetFilters" 
                            type="button" 
                            class="px-3 py-2 rounded-xl text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-colors flex items-center gap-1.5"
                            title="Reset Semua Filter"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Reset Filter</span>
                        </button>
                    </div>

                    <!-- Right: Action Buttons -->
                    <div class="flex items-center gap-2 shrink-0 flex-wrap">
                        <!-- Tombol Hapus Terpilih -->
                        <button 
                            v-if="selectedIds.length > 0 && (can('cbp_btn_delete') || can('cbp_btn_delete_all'))"
                            @click="openDeleteModal('selected')"
                            type="button"
                            class="px-3.5 py-2 bg-rose-50 text-rose-700 hover:bg-rose-100 font-bold text-xs rounded-xl border border-rose-200 shadow-sm flex items-center gap-1.5 transition-all animate-fade-in"
                        >
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>Hapus Terpilih ({{ selectedIds.length }})</span>
                        </button>

                        <!-- Tombol Hapus Semua -->
                        <button 
                            v-if="can('cbp_btn_delete_all')"
                            @click="openDeleteModal('all')"
                            type="button"
                            class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs rounded-xl border border-rose-200 shadow-sm flex items-center gap-1.5 transition-all"
                            title="Hapus Seluruh Data CBP"
                        >
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>Hapus Semua</span>
                        </button>

                        <!-- Tombol Buat Form CBP -->
                        <button v-if="can('cbp_btn_request')" @click="openModal()" class="bg-gradient-to-r from-red-600 to-rose-600 text-white px-4 py-2 rounded-xl font-bold text-xs shadow-md shadow-red-500/20 hover:shadow-lg hover:shadow-red-500/30 hover:-translate-y-0.5 transition-all w-full sm:w-auto flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>+ Buat Form CBP</span>
                        </button>
                    </div>
                </div>

                <!-- Custom Date Range Bar (Expanded if filterPeriod === 'custom') -->
                <transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="transform opacity-0 -translate-y-2"
                    enter-to-class="transform opacity-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="transform opacity-100 translate-y-0"
                    leave-to-class="transform opacity-0 -translate-y-2"
                >
                    <div v-if="filterPeriod === 'custom'" class="pt-2 border-t border-gray-100 flex flex-wrap items-center gap-3 text-xs bg-slate-50/70 p-3 rounded-xl border border-slate-200/60">
                        <span class="font-bold text-slate-700">Pilih Rentang Tanggal:</span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-slate-500 font-medium">Dari:</span>
                            <input type="date" v-model="startDate" @change="performSearch" class="px-2.5 py-1.5 border border-gray-200 rounded-lg text-xs bg-white font-medium focus:border-red-500 focus:ring-1 focus:ring-red-200">
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-slate-500 font-medium">Sampai:</span>
                            <input type="date" v-model="endDate" @change="performSearch" class="px-2.5 py-1.5 border border-gray-200 rounded-lg text-xs bg-white font-medium focus:border-red-500 focus:ring-1 focus:ring-red-200">
                        </div>
                    </div>
                </transition>
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="flex items-center gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 animate-fade-in-up">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash?.error" class="flex items-center gap-3 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-600 animate-fade-in-up">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span class="text-sm font-medium">{{ $page.props.flash.error }}</span>
            </div>

            <!-- Data Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/70 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <th class="p-4 w-12 text-center">
                                    <input 
                                        type="checkbox" 
                                        :checked="isAllSelected" 
                                        @change="toggleSelectAll" 
                                        class="rounded border-gray-300 text-rose-600 focus:ring-rose-500 cursor-pointer"
                                        title="Pilih Semua Halaman Ini"
                                    >
                                </th>
                                <th class="p-4 w-40">Nomor CBP</th>
                                <th class="p-4">Pelanggan</th>
                                <th class="p-4">Alasan Cabut</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Teknisi</th>
                                <th class="p-4">Waktu Selesai</th>
                                <th class="p-4 w-52 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr v-for="req in requests.data" :key="req.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="p-4 text-center">
                                    <input 
                                        type="checkbox" 
                                        :value="req.id" 
                                        v-model="selectedIds" 
                                        class="rounded border-gray-300 text-rose-600 focus:ring-rose-500 cursor-pointer"
                                    >
                                </td>
                                <td class="p-4 font-mono font-medium text-red-600">{{ req.cbp_number }}</td>
                                <td class="p-4">
                                    <div class="font-bold text-gray-900">{{ req.customer?.name || '-' }}</div>
                                    <div class="text-[11px] text-gray-500 flex flex-wrap items-center gap-1.5 mt-0.5">
                                        <span class="px-1.5 py-0.5 rounded bg-gray-100 border border-gray-200 font-mono">{{ req.customer?.customer_code || '-' }}</span>
                                        <span v-if="req.customer?.area_model" class="px-1.5 py-0.5 rounded bg-slate-100 border text-slate-600 text-[10px]">📍 {{ req.customer?.area_model?.name }}</span>
                                    </div>
                                    <div v-if="req.customer?.phone" class="text-[11px] text-gray-400 mt-0.5 font-mono">
                                        📞 {{ req.customer.phone }}
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div class="text-gray-700 max-w-xs truncate" :title="req.reason">{{ req.reason || '-' }}</div>
                                    <div v-if="req.dispatch_notes" class="text-[11px] font-medium text-blue-700 bg-blue-50 border border-blue-200/60 rounded-md px-2 py-0.5 mt-1 flex items-start gap-1 max-w-xs truncate" :title="'Disposisi: ' + req.dispatch_notes">
                                        <span class="font-bold shrink-0">Disposisi:</span>
                                        <span class="truncate">{{ req.dispatch_notes }}</span>
                                    </div>
                                    <div v-if="req.notes" class="text-[10px] text-gray-400 mt-0.5 truncate" :title="req.notes">
                                        Catatan: {{ req.notes }}
                                    </div>
                                </td>
                                <td class="p-4">
                                    <!-- Belum Dikirim ke Jadwal (Draft/Review) -->
                                    <span v-if="req.status === 'pending' && !req.sent_to_schedule_at" class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border flex items-center gap-1.5 w-max bg-amber-50 text-amber-700 border-amber-200" title="Permintaan CBP belum dikirim ke Jadwal">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        Menunggu Disposisi
                                    </span>
                                    <!-- Sudah Masuk Jadwal tapi belum ditugaskan teknisi -->
                                    <span v-else-if="req.status === 'pending' && req.sent_to_schedule_at" class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border flex items-center gap-1.5 w-max bg-sky-50 text-sky-700 border-sky-200" title="Sudah berada di Menu 2. Jadwal CBP">
                                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500 animate-pulse"></span>
                                        Masuk Jadwal
                                    </span>
                                    <!-- Assigned -->
                                    <span v-else-if="req.status === 'assigned'" class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border flex items-center gap-1.5 w-max bg-blue-50 text-blue-700 border-blue-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                        Ditugaskan
                                    </span>
                                    <!-- Completed -->
                                    <span v-else-if="req.status === 'completed'" class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border flex items-center gap-1.5 w-max bg-emerald-50 text-emerald-700 border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Selesai
                                    </span>
                                    <!-- Canceled -->
                                    <span v-else class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border flex items-center gap-1.5 w-max bg-rose-50 text-rose-700 border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Dibatalkan
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div v-if="req.technicians && req.technicians.length" class="flex flex-col gap-1">
                                        <div v-for="tech in req.technicians" :key="tech.id" class="flex items-center gap-2">
                                            <div class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center text-[9px] font-bold text-blue-700">
                                                {{ tech.name.charAt(0) }}
                                            </div>
                                            <span class="text-xs font-medium text-gray-700">{{ tech.name }}</span>
                                        </div>
                                    </div>
                                    <span v-else class="text-xs text-gray-400 italic">Belum ditugaskan</span>
                                </td>
                                <td class="p-4">
                                    <span v-if="req.completed_at" class="text-xs text-gray-600 font-medium">{{ formatDate(req.completed_at) }}</span>
                                    <span v-else class="text-xs text-gray-400">-</span>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                        <!-- Tombol Canggih: Kirim ke Jadwal CBP -->
                                        <button 
                                            v-if="!req.sent_to_schedule_at && req.status === 'pending' && can(['cbp_btn_dispatch', 'cbp_btn_request', 'cbp_manage'])" 
                                            type="button" 
                                            @click="openDispatchModal(req)" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-bold rounded-xl shadow-sm shadow-blue-500/20 hover:shadow-md hover:shadow-blue-500/30 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 whitespace-nowrap group" 
                                            title="Kirim Tiket ini ke Menu 2. Jadwal CBP"
                                        >
                                            <svg class="w-3.5 h-3.5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <span>Kirim ke Jadwal</span>
                                        </button>

                                        <!-- Badge Status Sudah di Jadwal + Opsi Tarik Kembali -->
                                        <div v-else-if="req.sent_to_schedule_at && req.status === 'pending'" class="inline-flex items-center gap-1">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg whitespace-nowrap" :title="'Terkirim ke Jadwal pada ' + formatDate(req.sent_to_schedule_at)">
                                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Di Jadwal
                                            </span>
                                            <button 
                                                v-if="can(['cbp_btn_dispatch', 'cbp_btn_request', 'cbp_manage'])" 
                                                type="button" 
                                                @click="retractCbp(req)" 
                                                class="p-1.5 text-amber-600 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-lg transition-colors" 
                                                title="Tarik Kembali dari Jadwal CBP"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                            </button>
                                        </div>

                                        <!-- WhatsApp Share -->
                                        <button type="button" @click="shareWhatsApp(req)" class="p-1.5 text-emerald-600 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg transition-colors" title="Kirim Disposisi ke Kepala Teknisi (WhatsApp)">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                        </button>

                                        <!-- Riwayat -->
                                        <button type="button" @click="openHistoryModal(req)" class="p-1.5 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg transition-colors" title="Riwayat CBP">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>

                                        <!-- Batalkan Request CBP -->
                                        <button v-if="req.status === 'pending' && can('cbp_btn_cancel')" type="button" @click="cancelCbp(req)" class="p-1.5 text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition-colors" title="Batalkan CBP">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>

                                        <!-- Hapus Tiket CBP (Salah Satu) -->
                                        <button v-if="can('cbp_btn_delete')" type="button" @click="openDeleteModal('single', req)" class="p-1.5 text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition-colors" title="Hapus Tiket CBP">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!requests.data.length">
                                <td colspan="8" class="p-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </div>
                                        <p class="font-bold text-slate-700">Tidak ada data tiket CBP ditemukan.</p>
                                        <p class="text-xs text-slate-400 mt-1 max-w-sm">Coba sesuaikan filter status, wilayah, periode, atau kata kunci pencarian Anda.</p>
                                        <button v-if="hasActiveFilters" @click="resetFilters" class="mt-3 px-4 py-1.5 rounded-xl text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-colors">
                                            Reset Semua Filter
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="requests.links?.length > 3" class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 bg-gray-50/40">
                    <span class="text-xs text-gray-500">Menampilkan <strong class="text-slate-800">{{ requests.from || 0 }}</strong> - <strong class="text-slate-800">{{ requests.to || 0 }}</strong> dari <strong class="text-slate-800">{{ requests.total || 0 }}</strong> data</span>
                    <div class="flex gap-1 flex-wrap justify-center">
                        <template v-for="link in requests.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" :class="['px-3 py-1.5 text-xs font-medium rounded-lg transition-colors', link.active ? 'bg-red-50 text-red-600 font-bold border border-red-200 shadow-xs' : 'text-gray-600 hover:bg-gray-100 border border-transparent']" v-html="link.label"></Link>
                            <span v-else class="px-3 py-1.5 text-xs text-gray-400" v-html="link.label"></span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Floating Selection Banner -->
            <transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="transform opacity-0 translate-y-4"
                enter-to-class="transform opacity-100 translate-y-0"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="transform opacity-100 translate-y-0"
                leave-to-class="transform opacity-0 translate-y-4"
            >
                <div 
                    v-if="selectedIds.length > 0" 
                    class="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-40 bg-slate-900/95 backdrop-blur-md text-white px-5 py-3 rounded-2xl shadow-2xl flex items-center gap-4 border border-slate-700 animate-fade-in"
                >
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-rose-600 text-white text-xs font-black flex items-center justify-center">{{ selectedIds.length }}</span>
                        <span class="text-sm font-bold">Tiket CBP Terpilih</span>
                    </div>
                    <div class="h-4 w-px bg-slate-700"></div>
                    <button 
                        @click="openDeleteModal('selected')" 
                        type="button"
                        class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-md shadow-rose-900/40 flex items-center gap-1.5 transition-all hover:scale-105 active:scale-95"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Hapus Terpilih</span>
                    </button>
                    <button 
                        @click="selectedIds = []" 
                        type="button"
                        class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition-all"
                    >
                        Batal
                    </button>
                </div>
            </transition>
        </div>

        <!-- Create Modal (Ultra Modern) -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">
                <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-md transition-opacity" @click="closeModal"></div>
                <div class="bg-white rounded-3xl shadow-[0_20px_60px_-15px_rgba(0,0,0,0.2)] w-full max-w-lg relative z-10 animate-fade-in-up border border-white/40 overflow-hidden flex flex-col max-h-[90vh]">
                    
                    <!-- Decorative background blur -->
                    <div class="absolute -top-24 -right-24 w-48 h-48 bg-rose-400/20 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute top-1/2 -left-24 w-40 h-40 bg-red-400/10 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="px-7 py-5 flex items-center justify-between bg-white/60 backdrop-blur-xl border-b border-gray-100 z-10 relative shrink-0">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-rose-500 to-red-600 flex items-center justify-center shadow-lg shadow-rose-500/30 text-white shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-extrabold text-slate-800 tracking-tight">Buat Eskalasi CBP</h3>
                                <p class="text-xs font-medium text-slate-500 mt-0.5">Penjadwalan cabut perangkat (Stop Permanen)</p>
                            </div>
                        </div>
                        <button @click="closeModal" class="text-slate-400 hover:text-rose-500 hover:bg-rose-50 p-2.5 rounded-full transition-all hover:rotate-90 duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitCreate" class="flex flex-col flex-1 overflow-hidden relative z-10">
                        <div class="p-7 overflow-y-auto custom-scrollbar space-y-6">
                            
                            <!-- Notice -->
                            <div class="bg-gradient-to-r from-rose-50 to-red-50/50 border border-rose-200/60 rounded-2xl p-5 flex gap-4 shadow-sm relative overflow-hidden group">
                                <div class="absolute -right-4 -top-4 opacity-5 group-hover:opacity-10 transition-opacity">
                                    <svg class="w-24 h-24 text-rose-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L1 21h22L12 2zm0 3.99L19.53 19H4.47L12 5.99zM11 16h2v2h-2zm0-6h2v4h-2z"/></svg>
                                </div>
                                <div class="shrink-0 w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center text-rose-600 relative z-10">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </div>
                                <div class="relative z-10">
                                    <h4 class="text-sm font-bold text-rose-900">Peringatan Penting!</h4>
                                    <p class="text-[11px] text-rose-700 mt-1 leading-relaxed">Menyimpan form ini akan mengubah status pelanggan menjadi <strong class="text-rose-900 bg-rose-200/50 px-1 rounded">Stop Permanen</strong>, mengisolir internet, dan menghentikan penagihan bulan berikutnya.</p>
                                </div>
                            </div>

                            <!-- Area Selection -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-2.5">Filter Wilayah/Area</label>
                                <div class="relative">
                                    <select v-model="selectedArea" class="w-full pl-4 pr-10 py-3 bg-slate-50 border-2 border-slate-200 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:outline-none focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 transition-all appearance-none cursor-pointer">
                                        <option value="" disabled>Pilih Area...</option>
                                        <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Customer Selection & Info -->
                            <transition
                                enter-active-class="transition duration-300 ease-out"
                                enter-from-class="transform opacity-0 -translate-y-4"
                                enter-to-class="transform opacity-100 translate-y-0"
                                leave-active-class="transition duration-200 ease-in"
                                leave-from-class="transform opacity-100 translate-y-0"
                                leave-to-class="transform opacity-0 -translate-y-4"
                            >
                                <div v-if="selectedArea" class="space-y-4">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-2.5">Pilih Pelanggan</label>
                                        <div class="relative">
                                            <select v-model="form.customer_id" class="w-full pl-4 pr-10 py-3 bg-slate-50 border-2 border-slate-200 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:outline-none focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 transition-all appearance-none cursor-pointer" required>
                                                <option value="" disabled>Pilih Pelanggan Aktif...</option>
                                                <option v-for="cust in filteredCustomers" :key="cust.id" :value="cust.id">{{ cust.customer_code }} - {{ cust.name }}</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Customer Data Display -->
                                    <div v-if="selectedCustomerData" class="relative overflow-hidden bg-gradient-to-br from-slate-50 to-slate-100 rounded-2xl p-5 border border-slate-200/60 shadow-sm group hover:border-rose-200 transition-colors">
                                        <h4 class="text-[11px] font-black text-slate-800 mb-3 border-b border-slate-200 pb-2 flex items-center gap-2 tracking-widest uppercase">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Detail Pelanggan
                                        </h4>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm relative z-10">
                                            <div class="bg-white/60 p-3 rounded-xl border border-white">
                                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Nama Lengkap</span>
                                                <span class="text-slate-900 font-bold">{{ selectedCustomerData.name }}</span>
                                            </div>
                                            <div class="bg-white/60 p-3 rounded-xl border border-white">
                                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Paket Aktif</span>
                                                <span class="text-slate-900 font-medium">{{ selectedCustomerData.package?.name || '-' }}</span>
                                            </div>
                                            <div class="bg-white/60 p-3 rounded-xl border border-white">
                                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">No. HP / WA</span>
                                                <span class="text-slate-900 font-medium font-mono">{{ selectedCustomerData.phone || '-' }}</span>
                                            </div>
                                            <div class="bg-white/60 p-3 rounded-xl border border-white">
                                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Alamat</span>
                                                <span class="text-slate-900 font-medium truncate block" :title="selectedCustomerData.address">{{ selectedCustomerData.address || '-' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </transition>

                            <!-- Alasan -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-2.5">Alasan Pencabutan / Stop Permanen</label>
                                <textarea v-model="form.reason" rows="3" class="w-full px-4 py-3 bg-slate-50 border-2 border-slate-200 rounded-2xl text-sm focus:bg-white focus:outline-none focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 transition-all placeholder-slate-400 resize-none custom-scrollbar" placeholder="Jelaskan secara singkat alasan pelanggan ini berhenti berlangganan..." required></textarea>
                            </div>
                        </div>

                        <div class="px-7 py-5 bg-slate-50/80 backdrop-blur-md border-t border-slate-100 flex justify-end gap-3 shrink-0 rounded-b-3xl">
                            <button type="button" @click="closeModal" class="px-6 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-200/50 rounded-xl transition-colors">Batal</button>
                            <button type="submit" :disabled="form.processing || !form.customer_id" class="relative group overflow-hidden bg-rose-600 text-white px-7 py-2.5 rounded-xl text-sm font-bold shadow-[0_8px_20px_-6px_rgba(225,29,72,0.5)] hover:bg-rose-700 hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:hover:translate-y-0 disabled:cursor-not-allowed">
                                <span class="relative z-10 flex items-center gap-2">
                                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    {{ form.processing ? 'Menyimpan...' : 'Eskalasi CBP' }}
                                </span>
                                <div class="absolute inset-0 h-full w-full bg-gradient-to-r from-rose-500 to-red-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Dispatch to Schedule Modal (Canggih & Sempurna) -->
        <Teleport to="body">
            <div v-if="showDispatchModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="closeDispatchModal"></div>
                <div class="bg-white rounded-3xl shadow-[0_20px_60px_-15px_rgba(0,0,0,0.25)] w-full max-w-lg relative z-10 animate-fade-in-up border border-white/60 overflow-hidden flex flex-col">
                    
                    <!-- Decorative ambient gradient lights -->
                    <div class="absolute -top-20 -right-20 w-44 h-44 bg-blue-500/15 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute -bottom-20 -left-20 w-44 h-44 bg-indigo-500/15 rounded-full blur-3xl pointer-events-none"></div>

                    <!-- Header -->
                    <div class="px-7 py-5 flex items-center justify-between bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white relative z-10">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-blue-500 to-indigo-500 flex items-center justify-center shadow-lg shadow-blue-500/30 text-white ring-2 ring-white/10 shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-black tracking-tight text-white flex items-center gap-2">
                                    Kirim ke Jadwal CBP
                                    <span class="text-xs font-mono font-medium px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-300 border border-blue-400/30">{{ selectedDispatchCbp?.cbp_number }}</span>
                                </h3>
                                <p class="text-xs font-medium text-slate-300 mt-0.5">Disposisi tiket pencabutan ke menu 2. Jadwal CBP</p>
                            </div>
                        </div>
                        <button @click="closeDispatchModal" class="text-slate-400 hover:text-white hover:bg-white/10 p-2 rounded-full transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <form @submit.prevent="submitDispatch" class="flex flex-col">
                        <div class="p-7 space-y-5">
                            <!-- Info Card -->
                            <div class="bg-gradient-to-br from-slate-50 to-blue-50/30 rounded-2xl p-4 border border-slate-200/70 space-y-2.5">
                                <div class="flex items-center justify-between text-xs pb-2 border-b border-slate-200/60">
                                    <span class="text-slate-500 font-medium">Pelanggan:</span>
                                    <span class="font-bold text-slate-900">{{ selectedDispatchCbp?.customer?.name }} ({{ selectedDispatchCbp?.customer?.customer_code }})</span>
                                </div>
                                <div class="flex items-center justify-between text-xs pb-2 border-b border-slate-200/60">
                                    <span class="text-slate-500 font-medium">No. Telepon / WA:</span>
                                    <span class="font-semibold text-slate-800 font-mono">{{ selectedDispatchCbp?.customer?.phone || '-' }}</span>
                                </div>
                                <div class="flex items-start justify-between text-xs pb-2 border-b border-slate-200/60">
                                    <span class="text-slate-500 font-medium shrink-0">Alamat:</span>
                                    <span class="text-slate-700 text-right font-medium max-w-[280px] truncate" :title="selectedDispatchCbp?.customer?.address">{{ selectedDispatchCbp?.customer?.address || '-' }}</span>
                                </div>
                                <div class="flex items-start justify-between text-xs">
                                    <span class="text-slate-500 font-medium shrink-0">Alasan Cabut:</span>
                                    <span class="font-semibold text-rose-700 text-right">{{ selectedDispatchCbp?.reason || '-' }}</span>
                                </div>
                            </div>

                            <!-- Catatan Disposisi -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    Catatan Tambahan untuk Jadwal (Opsional)
                                </label>
                                <textarea 
                                    v-model="dispatchForm.dispatch_notes" 
                                    rows="3" 
                                    class="w-full px-4 py-3 bg-slate-50 border-2 border-slate-200 rounded-2xl text-sm focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all placeholder-slate-400 resize-none custom-scrollbar" 
                                    placeholder="Contoh: Tolong dikerjakan segera, bawa tangga 6 meter, atau koordinasikan dulu dengan pelanggan..."
                                    maxlength="500"
                                ></textarea>
                                <p class="text-[11px] text-slate-400 mt-1">Catatan ini akan tampil di menu 2. Jadwal CBP dan diteruskan ke WhatsApp Kepala Teknisi.</p>
                            </div>

                            <div class="bg-blue-50 border border-blue-200/70 rounded-xl p-3 flex items-center gap-3 text-xs text-blue-800">
                                <svg class="w-5 h-5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Setelah dikirim, tiket ini akan langsung muncul di menu <strong>2. Jadwal CBP</strong> dan siap ditugaskan ke Teknisi.</span>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-7 py-4 bg-slate-50/80 backdrop-blur-md border-t border-slate-100 flex justify-end gap-3 rounded-b-3xl">
                            <button type="button" @click="closeDispatchModal" class="px-5 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-200/60 rounded-xl transition-colors">
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                :disabled="dispatchForm.processing" 
                                class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-md shadow-blue-500/25 hover:shadow-lg hover:shadow-blue-500/35 hover:-translate-y-0.5 active:translate-y-0 transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg v-if="dispatchForm.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                                <span>{{ dispatchForm.processing ? 'Mengirim...' : 'Kirim ke Jadwal CBP' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- History Modal -->
        <Teleport to="body">
            <div v-if="showHistoryModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeHistoryModal"></div>
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg relative z-10 animate-fade-in-up">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <h3 class="text-lg font-bold text-gray-900">Riwayat CBP</h3>
                        <button @click="closeHistoryModal" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-6 max-h-[60vh] overflow-y-auto custom-scrollbar">
                        <div v-if="!selectedHistoryCbp?.audit_logs || selectedHistoryCbp.audit_logs.length === 0" class="text-center py-8 text-gray-500">
                            Tidak ada riwayat ditemukan.
                        </div>
                        <div v-else class="space-y-6 relative before:absolute before:inset-0 before:ml-5 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-gray-200 before:to-transparent">
                            <div v-for="log in selectedHistoryCbp.audit_logs" :key="log.id" class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active">
                                <!-- Icon -->
                                <div class="flex items-center justify-center w-10 h-10 rounded-full border-4 border-white bg-indigo-100 text-indigo-600 shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 z-10 relative">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <!-- Card -->
                                <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] bg-white p-4 rounded-xl border border-gray-100 shadow-sm relative">
                                    <div class="flex items-center justify-between mb-1">
                                        <div class="font-bold text-gray-900 text-sm">{{ log.action }}</div>
                                        <div class="text-[10px] text-gray-500 font-mono">{{ formatDate(log.created_at) }}</div>
                                    </div>
                                    <div class="text-xs text-gray-600 mb-2">{{ log.notes }}</div>
                                    <div class="flex items-center gap-2 mt-2 pt-2 border-t border-gray-50">
                                        <span class="text-[10px] font-medium text-gray-500">Oleh: <span class="text-gray-700 font-bold">{{ log.user_name }}</span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Confirmation Modal (Ultra Modern) -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="closeDeleteModal"></div>
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md relative z-10 animate-fade-in-up border border-slate-100 overflow-hidden">
                    <div class="p-6 text-center space-y-4">
                        <div class="w-16 h-16 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center mx-auto border-4 border-rose-100/50 shadow-inner">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-black text-slate-800">
                                {{ deleteMode === 'single' 
                                    ? `Hapus Tiket CBP ${itemToDelete?.cbp_number}?` 
                                    : (deleteMode === 'selected' ? `Hapus ${selectedIds.length} Tiket CBP Terpilih?` : 'Hapus Seluruh Data Tiket CBP?') }}
                            </h3>
                            <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                                {{ deleteMode === 'single'
                                    ? `Tiket ${itemToDelete?.cbp_number} untuk pelanggan ${itemToDelete?.customer?.name || ''} akan dihapus secara permanen dari sistem.`
                                    : (deleteMode === 'selected' 
                                        ? `Tindakan ini akan menghapus ${selectedIds.length} data tiket CBP yang Anda pilih secara permanen.`
                                        : `Tindakan ini akan menghapus SEMUA data tiket CBP (${counts?.all || requests.total || 0} tiket) yang ada di sistem secara permanen!`)
                                }}
                            </p>
                        </div>

                        <!-- Konfirmasi Text HAPUS untuk Hapus Semua -->
                        <div v-if="deleteMode === 'all'" class="text-left space-y-1.5 pt-1">
                            <label class="block text-xs font-bold text-slate-700">
                                Ketik <span class="text-rose-600 font-mono font-black">HAPUS</span> untuk konfirmasi:
                            </label>
                            <input 
                                v-model="deleteConfirmInput" 
                                type="text" 
                                placeholder="Ketik HAPUS" 
                                class="w-full px-3.5 py-2.5 border-2 border-rose-200 rounded-xl text-sm text-center uppercase tracking-widest font-mono font-black focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 focus:outline-none"
                            />
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <button 
                                type="button" 
                                @click="closeDeleteModal" 
                                class="flex-1 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors"
                            >
                                Batal
                            </button>
                            <button 
                                type="button" 
                                @click="confirmDelete" 
                                :disabled="isDeleting || (deleteMode === 'all' && deleteConfirmInput.toUpperCase() !== 'HAPUS')"
                                class="flex-1 px-4 py-2.5 bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white font-bold text-xs rounded-xl transition-all shadow-md shadow-rose-500/30 flex items-center justify-center gap-2"
                            >
                                <svg v-if="isDeleting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ isDeleting ? 'Menghapus...' : (deleteMode === 'all' ? 'Ya, Hapus Semua' : 'Ya, Hapus') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermission } from '@/Composables/usePermission';

const { can } = usePermission();

const props = defineProps({
    requests: Object,
    counts: Object,
    areas: Array,
    customers: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const filterStatus = ref(props.filters?.status || '');
const filterArea = ref(props.filters?.area_id || '');
const filterPeriod = ref(props.filters?.period || '');
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');

const hasActiveFilters = computed(() => {
    return !!(search.value || filterStatus.value || filterArea.value || filterPeriod.value || startDate.value || endDate.value);
});

function filterByStatus(status) {
    if (status === 'all') {
        filterStatus.value = '';
    } else if (filterStatus.value === status) {
        filterStatus.value = '';
    } else {
        filterStatus.value = status;
    }
    performSearch();
}

function performSearch() {
    router.get('/cbp', {
        search: search.value || undefined,
        status: filterStatus.value || undefined,
        area_id: filterArea.value || undefined,
        period: filterPeriod.value || undefined,
        start_date: filterPeriod.value === 'custom' ? (startDate.value || undefined) : undefined,
        end_date: filterPeriod.value === 'custom' ? (endDate.value || undefined) : undefined,
    }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    search.value = '';
    filterStatus.value = '';
    filterArea.value = '';
    filterPeriod.value = '';
    startDate.value = '';
    endDate.value = '';
    selectedIds.value = [];
    router.get('/cbp', {}, { preserveState: true, preserveScroll: true });
}

// Bulk & Single Delete State & Methods
const selectedIds = ref([]);
const showDeleteModal = ref(false);
const deleteMode = ref('single'); // 'single', 'selected', 'all'
const itemToDelete = ref(null);
const deleteConfirmInput = ref('');
const isDeleting = ref(false);

const isAllSelected = computed(() => {
    if (!props.requests.data || props.requests.data.length === 0) return false;
    return props.requests.data.every(req => selectedIds.value.includes(req.id));
});

function toggleSelectAll(event) {
    if (event.target.checked) {
        const pageIds = props.requests.data.map(req => req.id);
        selectedIds.value = Array.from(new Set([...selectedIds.value, ...pageIds]));
    } else {
        const pageIds = new Set(props.requests.data.map(req => req.id));
        selectedIds.value = selectedIds.value.filter(id => !pageIds.has(id));
    }
}

function openDeleteModal(mode, item = null) {
    deleteMode.value = mode;
    itemToDelete.value = item;
    deleteConfirmInput.value = '';
    showDeleteModal.value = true;
}

function closeDeleteModal() {
    showDeleteModal.value = false;
    itemToDelete.value = null;
    deleteConfirmInput.value = '';
}

function confirmDelete() {
    if (deleteMode.value === 'single') {
        if (!itemToDelete.value) return;
        isDeleting.value = true;
        router.delete(`/cbp/${itemToDelete.value.id}`, {
            preserveScroll: true,
            onFinish: () => {
                isDeleting.value = false;
                closeDeleteModal();
                selectedIds.value = selectedIds.value.filter(id => id !== itemToDelete.value?.id);
            }
        });
    } else if (deleteMode.value === 'selected') {
        if (selectedIds.value.length === 0) return;
        isDeleting.value = true;
        router.post('/cbp/bulk-delete', {
            type: 'selected',
            ids: selectedIds.value
        }, {
            preserveScroll: true,
            onFinish: () => {
                isDeleting.value = false;
                closeDeleteModal();
                selectedIds.value = [];
            }
        });
    } else if (deleteMode.value === 'all') {
        if (deleteConfirmInput.value.toUpperCase() !== 'HAPUS') return;
        isDeleting.value = true;
        router.post('/cbp/bulk-delete', {
            type: 'all'
        }, {
            preserveScroll: true,
            onFinish: () => {
                isDeleting.value = false;
                closeDeleteModal();
                selectedIds.value = [];
            }
        });
    }
}

// Create Modal
const showModal = ref(false);
const selectedArea = ref('');

const form = useForm({
    customer_id: '',
    reason: '',
});

const filteredCustomers = computed(() => {
    if (!selectedArea.value) return [];
    return props.customers.filter(c => c.area_id === selectedArea.value);
});

const selectedCustomerData = computed(() => {
    if (!form.customer_id) return null;
    return props.customers.find(c => c.id === form.customer_id);
});

function openModal() {
    form.reset();
    selectedArea.value = '';
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    form.reset();
    selectedArea.value = '';
}

function submitCreate() {
    form.post('/cbp', {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
}

// Dispatch to Schedule Modal
const showDispatchModal = ref(false);
const selectedDispatchCbp = ref(null);
const dispatchForm = useForm({
    dispatch_notes: '',
});

function openDispatchModal(cbp) {
    selectedDispatchCbp.value = cbp;
    dispatchForm.dispatch_notes = cbp.dispatch_notes || '';
    showDispatchModal.value = true;
}

function closeDispatchModal() {
    showDispatchModal.value = false;
    selectedDispatchCbp.value = null;
    dispatchForm.reset();
}

function submitDispatch() {
    if (!selectedDispatchCbp.value) return;
    dispatchForm.post(`/cbp/${selectedDispatchCbp.value.id}/dispatch-schedule`, {
        preserveScroll: true,
        onSuccess: () => closeDispatchModal(),
    });
}

// Retract from Schedule
function retractCbp(cbp) {
    if (confirm(`Tarik kembali tiket ${cbp.cbp_number} dari Jadwal CBP? Tiket tidak akan muncul di menu 2. Jadwal CBP sampai Anda mengirimnya kembali.`)) {
        router.post(`/cbp/${cbp.id}/retract-schedule`, {}, {
            preserveScroll: true
        });
    }
}

// History Modal
const showHistoryModal = ref(false);
const selectedHistoryCbp = ref(null);

function openHistoryModal(cbp) {
    selectedHistoryCbp.value = cbp;
    showHistoryModal.value = true;
}

function closeHistoryModal() {
    showHistoryModal.value = false;
    selectedHistoryCbp.value = null;
}

// Cancel
function cancelCbp(cbp) {
    if (confirm(`Yakin ingin membatalkan request CBP (${cbp.cbp_number}) ini?`)) {
        router.post(`/cbp/${cbp.id}/cancel`, {}, {
            preserveScroll: true
        });
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return isNaN(d.getTime()) ? dateStr : d.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// WhatsApp Dispatch to Lead Technician
function shareWhatsApp(req) {
    const cust = req.customer || {};
    const ont = cust.ont || {};
    let ontInfo = '';
    if (ont.brand || ont.serial_number) {
        ontInfo = `📟 *Perangkat ONT:* ${ont.brand || ''} ${ont.model || ''} (SN: ${ont.serial_number || '-'})` + (ont.odp ? ` | ODP: ${ont.odp.name || ont.odp}` : '') + `\n`;
    }

    const text = `*DISPOSISI CABUT PERANGKAT (CBP)*\n` +
        `--------------------------------------\n` +
        `📌 *No. Tiket:* ${req.cbp_number}\n` +
        `👤 *Pelanggan:* ${cust.name || '-'} (${cust.customer_code || '-'})\n` +
        `📞 *No. HP:* ${cust.phone || '-'}\n` +
        `📍 *Alamat:* ${cust.address || '-'}\n` +
        ontInfo +
        `⚠️ *Alasan:* ${req.reason || '-'}\n` +
        (req.dispatch_notes ? `📋 *Catatan Disposisi:* ${req.dispatch_notes}\n` : '') +
        (req.notes ? `📝 *Catatan Lain:* ${req.notes}\n` : '') +
        `--------------------------------------\n` +
        `Kepada: *Kepala Teknisi*\n` +
        `Mohon jadwalkan tim teknisi untuk penarikan/pencabutan perangkat ONT.\n` +
        `Jadwal sistem: ${window.location.origin}/cbp/jadwal`;

    const url = `https://wa.me/?text=${encodeURIComponent(text)}`;
    window.open(url, '_blank');
}
</script>
