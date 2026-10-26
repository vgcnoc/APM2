<template>
    <AppLayout title="Jadwal & Material PGK" subtitle="Penjadwalan Teknisi & Penyiapan Material oleh Kepala Teknisi">
        <div class="space-y-6">


            <!-- ════════════ STATS SUMMARY CARDS ════════════ -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
                <!-- Card 1: Total Antrean -->
                <div 
                    @click="filterByCard('all')"
                    class="bg-white p-4 rounded-2xl border transition-all duration-200 cursor-pointer shadow-2xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="(filterStatus === 'all' && (!filterMaterial || filterMaterial === 'all')) ? 'border-slate-800 ring-2 ring-slate-400/30 bg-slate-50/50' : 'border-gray-200/80 hover:border-slate-300'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Total Antrean</span>
                        <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-sm font-black group-hover:scale-110 transition-transform">
                            📋
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-black text-gray-900 tracking-tight">{{ counts?.total || 0 }}</div>
                    <div class="mt-1 text-[11px] text-gray-500 font-medium flex items-center gap-1">
                        <span>Semua Kasus PGK</span>
                    </div>
                </div>

                <!-- Card 2: Menunggu Jadwal -->
                <div 
                    @click="filterByCard('waiting')"
                    class="bg-white p-4 rounded-2xl border transition-all duration-200 cursor-pointer shadow-2xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="filterStatus === 'waiting' ? 'border-amber-600 ring-2 ring-amber-500/30 bg-amber-50/30' : 'border-gray-200/80 hover:border-amber-300'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-amber-700 uppercase tracking-wider">Menunggu Jadwal</span>
                        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center text-sm font-black group-hover:scale-110 transition-transform">
                            ⏳
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-black text-amber-900 tracking-tight">{{ counts?.waiting || 0 }}</div>
                    <div class="mt-1 text-[11px] text-amber-600 font-medium flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        <span>Perlu Disiapkan</span>
                    </div>
                </div>

                <!-- Card 3: Terjadwal & Lapangan -->
                <div 
                    @click="filterByCard('scheduled')"
                    class="bg-white p-4 rounded-2xl border transition-all duration-200 cursor-pointer shadow-2xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="filterStatus === 'scheduled' ? 'border-indigo-600 ring-2 ring-indigo-500/30 bg-indigo-50/30' : 'border-gray-200/80 hover:border-indigo-300'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-indigo-700 uppercase tracking-wider">Siap & Lapangan</span>
                        <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center text-sm font-black group-hover:scale-110 transition-transform">
                            🔧
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-black text-indigo-900 tracking-tight">{{ counts?.scheduled || 0 }}</div>
                    <div class="mt-1 text-[11px] text-indigo-600 font-medium flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                        <span>Sedang Berjalan</span>
                    </div>
                </div>

                <!-- Card 4: Selesai Dikerjakan -->
                <div 
                    @click="filterByCard('completed')"
                    class="bg-white p-4 rounded-2xl border transition-all duration-200 cursor-pointer shadow-2xs hover:shadow-md hover:-translate-y-0.5 group"
                    :class="filterStatus === 'completed' ? 'border-emerald-600 ring-2 ring-emerald-500/30 bg-emerald-50/30' : 'border-gray-200/80 hover:border-emerald-300'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">Selesai Dikerjakan</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-sm font-black group-hover:scale-110 transition-transform">
                            ✅
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-black text-emerald-900 tracking-tight">{{ counts?.completed || 0 }}</div>
                    <div class="mt-1 text-[11px] text-emerald-600 font-medium flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span>Laporan Lengkap</span>
                    </div>
                </div>

                <!-- Card 5: Kebutuhan Material & ONT -->
                <div 
                    @click="filterByCard('materials')"
                    class="bg-white p-4 rounded-2xl border transition-all duration-200 cursor-pointer shadow-2xs hover:shadow-md hover:-translate-y-0.5 group col-span-2 sm:col-span-1"
                    :class="filterMaterial === 'with_materials' ? 'border-orange-600 ring-2 ring-orange-500/30 bg-orange-50/30' : 'border-gray-200/80 hover:border-orange-300'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-orange-700 uppercase tracking-wider">Material & ONT</span>
                        <div class="w-8 h-8 rounded-xl bg-orange-50 text-orange-700 flex items-center justify-center text-sm font-black group-hover:scale-110 transition-transform">
                            📦
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-black text-orange-900 tracking-tight">{{ counts?.with_materials || 0 }}</div>
                    <div class="mt-1 text-[11px] text-orange-600 font-medium flex items-center gap-1">
                        <span>Alokasi & Ganti</span>
                    </div>
                </div>
            </div>

            <!-- ════════════ SMART FILTER BAR ════════════ -->
            <div v-if="can('pgk_filter')" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2.5">
                    <!-- Search Input Canggih -->
                    <div class="relative lg:col-span-2">
                        <input 
                            type="text" 
                            v-model="search" 
                            placeholder="Cari No. PGK, pelanggan, pelapor, ODP, telp, masalah..." 
                            class="w-full pl-9 pr-8 py-2 rounded-xl border border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-xs transition-all"
                            @keyup.enter="applyFilters()"
                        >
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <button v-if="search" @click="search = ''; applyFilters({ search: '' })" class="absolute right-2.5 top-2.5 text-gray-400 hover:text-gray-600 text-xs font-bold cursor-pointer">✕</button>
                    </div>

                    <!-- Filter Status -->
                    <div>
                        <select v-model="filterStatus" @change="applyFilters()" class="w-full px-3 py-2 rounded-xl border border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-xs bg-gray-50/50 cursor-pointer font-medium">
                            <option value="active">⚡ Antrean Aktif (Default)</option>
                            <option value="waiting">⏳ Menunggu Jadwal</option>
                            <option value="scheduled">🔧 Sudah Dijadwalkan</option>
                            <option value="in_progress">⚙️ Sedang di Lapangan</option>
                            <option value="completed">✅ Selesai Dikerjakan</option>
                            <option value="all">📋 Semua Status Tiket</option>
                        </select>
                    </div>

                    <!-- Filter Prioritas -->
                    <div>
                        <select v-model="filterPriority" @change="applyFilters()" class="w-full px-3 py-2 rounded-xl border border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-xs bg-gray-50/50 cursor-pointer font-medium">
                            <option value="">Semua Prioritas</option>
                            <option value="critical">🔴 Critical (Mendesak)</option>
                            <option value="high">🟠 High (Tinggi)</option>
                            <option value="medium">🟡 Medium (Normal)</option>
                            <option value="low">🟢 Low (Rendah)</option>
                        </select>
                    </div>

                    <!-- Filter Area -->
                    <div>
                        <select v-model="filterArea" @change="applyFilters()" class="w-full px-3 py-2 rounded-xl border border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-xs bg-gray-50/50 cursor-pointer font-medium">
                            <option value="">Semua Area / Wilayah</option>
                            <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>
                    </div>

                    <!-- Filter Teknisi -->
                    <div>
                        <select v-model="filterTechnician" @change="applyFilters()" class="w-full px-3 py-2 rounded-xl border border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-xs bg-gray-50/50 cursor-pointer font-medium">
                            <option value="">Semua Teknisi</option>
                            <option v-for="t in technicians" :key="t.id" :value="t.id">👤 {{ t.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Secondary Filter Row -->
                <div class="flex flex-wrap items-center justify-between gap-2.5 pt-2 border-t border-gray-100 text-xs">
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Filter Periode Waktu -->
                        <select v-model="filterPeriod" @change="applyFilters()" class="px-3 py-1.5 rounded-xl border border-gray-200 focus:border-amber-500 text-xs bg-gray-50/50 cursor-pointer font-medium">
                            <option value="">Semua Jadwal Tanggal</option>
                            <option value="today">📅 Hari Ini</option>
                            <option value="tomorrow">📅 Besok</option>
                            <option value="this_week">📅 Minggu Ini</option>
                            <option value="this_month">📅 Bulan Ini</option>
                            <option value="custom">📅 Pilih Rentang Tanggal...</option>
                        </select>

                        <!-- Custom Date Picker -->
                        <div v-if="filterPeriod === 'custom'" class="flex items-center gap-1.5 bg-gray-50 p-1.5 rounded-xl border border-gray-200">
                            <input type="date" v-model="filterStartDate" class="text-xs px-2 py-1 border border-gray-300 rounded-lg" />
                            <span class="text-xs text-gray-500">s/d</span>
                            <input type="date" v-model="filterEndDate" class="text-xs px-2 py-1 border border-gray-300 rounded-lg" />
                            <button @click="applyFilters()" class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold cursor-pointer">Cari</button>
                        </div>

                        <!-- Filter Kebutuhan Material -->
                        <select v-model="filterMaterial" @change="applyFilters()" class="px-3 py-1.5 rounded-xl border border-gray-200 focus:border-amber-500 text-xs bg-gray-50/50 cursor-pointer font-medium">
                            <option value="">Semua Kebutuhan Material</option>
                            <option value="with_materials">📦 Ada Bekal Material Gudang</option>
                            <option value="request_pending">🔔 Ada Request Material Pending</option>
                            <option value="with_ont">🔄 Ada Pergantian Unit ONT</option>
                            <option value="with_dismantle">♻️ Ada Material Bongkaran</option>
                        </select>

                        <!-- Tombol Terapkan Search -->
                        <button @click="applyFilters()" class="px-3.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold transition-all shadow-sm cursor-pointer flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            <span>Filter</span>
                        </button>

                        <!-- Reset Filter -->
                        <button v-if="hasActiveFilters" @click="resetFilters" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-1">
                            <span>↺ Reset</span>
                        </button>
                    </div>

                    <div class="text-xs text-gray-400 font-medium">
                        Ditemukan <b class="text-gray-800 font-mono">{{ tickets.total || 0 }}</b> tiket
                    </div>
                </div>
            </div>

            <!-- Quick Status Tabs -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                <button
                    type="button"
                    @click="setStatusFilter('active')"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap shadow-xs cursor-pointer"
                    :class="filterStatus === 'active' ? 'bg-amber-600 text-white shadow-amber-600/30 ring-2 ring-amber-600/20' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'"
                >
                    <span>⚡ Antrean Aktif</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="filterStatus === 'active' ? 'bg-amber-700/60 text-white' : 'bg-gray-100 text-gray-700'">
                        {{ counts?.active || 0 }}
                    </span>
                </button>
                <button
                    type="button"
                    @click="setStatusFilter('waiting')"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap shadow-xs cursor-pointer"
                    :class="filterStatus === 'waiting' ? 'bg-amber-600 text-white shadow-amber-600/30 ring-2 ring-amber-600/20' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'"
                >
                    <span>⏳ Menunggu Jadwal</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="filterStatus === 'waiting' ? 'bg-amber-700/60 text-white' : 'bg-amber-50 text-amber-800 font-bold'">
                        {{ counts?.waiting || 0 }}
                    </span>
                </button>
                <button
                    type="button"
                    @click="setStatusFilter('scheduled')"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap shadow-xs cursor-pointer"
                    :class="filterStatus === 'scheduled' ? 'bg-amber-600 text-white shadow-amber-600/30 ring-2 ring-amber-600/20' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'"
                >
                    <span>🔧 Terjadwal & Lapangan</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="filterStatus === 'scheduled' ? 'bg-amber-700/60 text-white' : 'bg-blue-50 text-blue-800 font-bold'">
                        {{ counts?.scheduled || 0 }}
                    </span>
                </button>
                <button
                    type="button"
                    @click="setStatusFilter('completed')"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap shadow-xs cursor-pointer"
                    :class="filterStatus === 'completed' ? 'bg-emerald-600 text-white shadow-emerald-600/30 ring-2 ring-emerald-600/20' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'"
                >
                    <span>✅ Selesai Dikerjakan</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="filterStatus === 'completed' ? 'bg-emerald-700/60 text-white' : 'bg-emerald-50 text-emerald-800 font-bold'">
                        {{ counts?.completed || 0 }}
                    </span>
                </button>
                <button
                    type="button"
                    @click="setStatusFilter('all')"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap shadow-xs cursor-pointer"
                    :class="filterStatus === 'all' ? 'bg-slate-800 text-white shadow-slate-800/30 ring-2 ring-slate-800/20' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'"
                >
                    <span>📋 Semua Tiket</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px]" :class="filterStatus === 'all' ? 'bg-slate-900 text-white' : 'bg-gray-100 text-gray-700'">
                        {{ counts?.total || counts?.all || 0 }}
                    </span>
                </button>
            </div>

            <!-- Flash Alert -->
            <div v-if="$page.props.flash?.success" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-semibold">{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash?.error" class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-semibold">{{ $page.props.flash.error }}</span>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/70 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                                <th class="p-4">No. Tiket & Pelanggan</th>
                                <th class="p-4">Alasan Eskalasi & Keluhan</th>
                                <th class="p-4">Status & Jadwal Kunjungan</th>
                                <th class="p-4">Teknisi & Material Disiapkan</th>
                                <th class="p-4 text-center">Aksi Ka. Teknisi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-amber-50/20 transition-colors group">
                                <!-- 1. No Tiket & Pelanggan -->
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-amber-600 text-xs">{{ ticket.ticket_number }}</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border" :class="ticket.priority === 'critical' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-gray-50 text-gray-700 border-gray-200'">
                                            {{ ticket.priority }}
                                        </span>
                                    </div>
                                    <div class="font-bold text-gray-900 mt-1">
                                        {{ ticket.customer?.name || '-' }}
                                    </div>
                                    <div class="text-[11px] text-gray-500 flex flex-wrap items-center gap-1.5 mt-0.5">
                                        <span class="px-1.5 py-0.2 rounded bg-gray-100 border text-gray-700 font-mono text-[10px]">{{ ticket.customer?.customer_code }}</span>
                                        <span>· {{ ticket.customer?.phone || '-' }}</span>
                                    </div>
                                    <div class="text-[11px] text-gray-500 mt-1 truncate max-w-xs" :title="ticket.customer?.address">
                                        📍 {{ ticket.customer?.address || '-' }}
                                    </div>
                                    <div class="text-[10px] text-amber-700 font-semibold mt-0.5">
                                        ODP: {{ ticket.customer?.ont?.odp?.name || '-' }} · Area: {{ ticket.area?.name || ticket.customer?.area_model?.name || '-' }}
                                    </div>

                                    <!-- Multi Titik Koordinat (Tikor) -->
                                    <div v-if="ticket.incident_coordinates && ticket.incident_coordinates.length > 0" class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                        <span class="text-[10px] font-bold text-sky-800 bg-sky-50 px-2 py-0.5 rounded-md border border-sky-200 flex items-center gap-1">
                                            <span>📍</span> {{ ticket.incident_coordinates.length }} Tikor Insiden
                                        </span>
                                        <a :href="`https://www.google.com/maps?q=${ticket.incident_coordinates[0].latitude},${ticket.incident_coordinates[0].longitude}`" target="_blank" class="text-[10px] text-sky-700 hover:text-sky-900 underline font-semibold">
                                            Buka Maps ↗
                                        </a>
                                    </div>

                                    <!-- Alert Permintaan Pergantian ONT dari Teknisi Lapangan -->
                                    <div v-if="ticket.ont_replacement_status === 'requested'" class="mt-2 p-2 rounded-xl bg-indigo-100 border border-indigo-300 text-indigo-950 text-xs flex items-center justify-between gap-2 shadow-sm animate-pulse">
                                        <div class="flex items-center gap-1.5 font-bold">
                                            <span class="text-base">🔄</span>
                                            <span>Ada Request Ganti ONT Lapangan!</span>
                                        </div>
                                        <button 
                                            type="button" 
                                            @click="openOntPrepareModal(ticket)" 
                                            class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-[10px] shadow-sm transition-all shrink-0"
                                        >
                                            Siapkan ONT &rarr;
                                        </button>
                                    </div>
                                    <div v-else-if="ticket.ont_replacement_status === 'prepared'" class="mt-2 p-1.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 text-[11px] flex items-center justify-between gap-1 shadow-sm">
                                        <div class="flex items-center gap-1 font-semibold truncate">
                                            <span>📦 ONT Disiapkan:</span>
                                            <b class="font-mono text-blue-800">{{ ticket.prepared_ont?.serial_number || 'Unit Baru' }}</b>
                                        </div>
                                        <button 
                                            type="button" 
                                            @click="openOntPrepareModal(ticket)" 
                                            class="text-blue-700 hover:text-blue-900 underline font-bold shrink-0 text-[10px]"
                                        >
                                            Kelola
                                        </button>
                                    </div>

                                    <!-- Alert Permintaan Tambahan Material dari Teknisi Lapangan -->
                                    <div v-if="ticket.material_requests?.some(r => r.status === 'pending')" class="mt-2 p-2 rounded-xl bg-amber-100 border border-amber-300 text-amber-900 text-xs flex items-center justify-between gap-2 shadow-sm animate-pulse">
                                        <div class="flex items-center gap-1.5 font-bold">
                                            <span class="text-base">🔔</span>
                                            <span>Ada {{ ticket.material_requests.filter(r => r.status === 'pending').length }} Permintaan Material Lapangan!</span>
                                        </div>
                                        <button 
                                            type="button" 
                                            @click="openMaterialReviewModal(ticket)" 
                                            class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-lg text-[10px] shadow-sm transition-all shrink-0"
                                        >
                                            Beri Tambahan &rarr;
                                        </button>
                                    </div>
                                </td>

                                <!-- 2. Alasan Eskalasi & Keluhan -->
                                <td class="p-4">
                                    <div class="text-xs font-semibold text-gray-800">
                                        Kategori: {{ ticket.category }}
                                    </div>
                                    <p class="text-xs text-red-600 font-medium mt-1">
                                        Keluhan: "{{ ticket.reported_issue }}"
                                    </p>
                                    <div class="mt-2 p-2 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900">
                                        <div class="font-bold text-[11px] uppercase text-amber-800">Alasan Eskalasi Admin:</div>
                                        <div class="mt-0.5">{{ ticket.escalation_reason || '-' }}</div>
                                    </div>
                                </td>

                                <!-- 3. Status & Jadwal -->
                                <td class="p-4">
                                    <span 
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider" 
                                        :class="{
                                            'bg-emerald-50 text-emerald-700 border border-emerald-200': ticket.status === 'completed',
                                            'bg-sky-50 text-sky-700 border border-sky-200': ticket.status === 'in_progress',
                                            'bg-indigo-50 text-indigo-700 border border-indigo-200': ticket.status === 'scheduled',
                                            'bg-amber-50 text-amber-700 border border-amber-200': ticket.status === 'escalated_to_lead' || !ticket.status,
                                        }"
                                    >
                                        <span 
                                            class="w-1.5 h-1.5 rounded-full" 
                                            :class="{
                                                'bg-emerald-500': ticket.status === 'completed',
                                                'bg-sky-500 animate-pulse': ticket.status === 'in_progress',
                                                'bg-indigo-500': ticket.status === 'scheduled',
                                                'bg-amber-500 animate-pulse': ticket.status === 'escalated_to_lead' || !ticket.status,
                                            }"
                                        ></span>
                                        <span>
                                            {{ 
                                                ticket.status === 'completed' ? 'Selesai Dikerjakan' :
                                                (ticket.status === 'in_progress' ? 'Sedang Dikerjakan' :
                                                (ticket.status === 'scheduled' ? 'Sudah Dijadwalkan' : 'Menunggu Jadwal'))
                                            }}
                                        </span>
                                    </span>

                                    <div v-if="ticket.scheduled_date" class="mt-2 text-xs text-gray-700 font-semibold flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>{{ formatDate(ticket.scheduled_date) }}</span>
                                        <span class="text-gray-500 font-normal">({{ ticket.scheduled_time || 'Fleksibel' }})</span>
                                    </div>
                                    <div v-if="ticket.status === 'completed' && ticket.resolved_at" class="mt-1 text-[11px] text-emerald-700 font-semibold flex items-center gap-1">
                                        <span>✓ Selesai:</span>
                                        <span>{{ formatDate(ticket.resolved_at) }}</span>
                                    </div>
                                </td>

                                <!-- 4. Teknisi & Material -->
                                <td class="p-4">
                                    <!-- Daftar Teknisi -->
                                    <div class="text-xs">
                                        <div class="text-[10px] text-gray-400 font-bold uppercase">Teknisi Ditugaskan:</div>
                                        <div v-if="ticket.technicians && ticket.technicians.length > 0" class="font-bold text-gray-800 mt-0.5">
                                            {{ ticket.technicians.map(t => t.name).join(', ') }}
                                        </div>
                                        <div v-else class="text-gray-400 italic text-xs mt-0.5">Belum ditentukan</div>
                                    </div>

                                    <!-- Material Disiapkan -->
                                    <div class="mt-2 text-xs">
                                        <div class="text-[10px] text-gray-400 font-bold uppercase">Material Disiapkan:</div>
                                        <div v-if="ticket.materials && ticket.materials.length > 0" class="space-y-1 mt-1">
                                            <div v-for="m in ticket.materials" :key="m.id" class="text-[11px] bg-gray-50 px-2 py-0.5 rounded border border-gray-100 flex justify-between items-center gap-1">
                                                <div class="flex items-center gap-1.5 truncate">
                                                    <span class="font-medium text-gray-700 truncate">{{ m.material?.name }}</span>
                                                    <span v-if="m.trx_number" class="text-[9px] font-mono font-bold text-amber-700 bg-amber-100 px-1 py-0.5 rounded border border-amber-200">PO: {{ m.trx_number }}</span>
                                                </div>
                                                <div class="shrink-0 flex items-center gap-1.5">
                                                    <span class="text-gray-600 font-bold" title="Jumlah Disiapkan">Siap: {{ parseFloat(m.quantity_prepared) }}</span>
                                                    <span v-if="ticket.status === 'completed'" class="font-bold text-emerald-700 bg-emerald-50 px-1 rounded border border-emerald-200" title="Jumlah Terpakai di Pelanggan">
                                                        Pakai: {{ parseFloat(m.quantity_used || 0) }}
                                                    </span>
                                                    <span class="text-gray-400 text-[10px]">{{ m.unit || m.material?.unit }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="text-gray-400 italic text-xs mt-0.5">Belum ada material disiapkan</div>
                                    </div>

                                    <!-- Status Pengembalian / Retur Sisa Material ke Gudang -->
                                    <div v-if="ticket.materials && ticket.materials.some(m => parseFloat(m.quantity_returned) > 0)" class="mt-2 pt-1 border-t border-gray-100">
                                        <div class="text-[10px] text-blue-700 font-bold uppercase flex items-center gap-1">
                                            <span>↩️ Retur Sisa ke Gudang:</span>
                                        </div>
                                        <div class="space-y-0.5 mt-0.5">
                                            <div v-for="m in ticket.materials.filter(m => parseFloat(m.quantity_returned) > 0)" :key="'ret-m-' + m.id" class="text-[11px] bg-blue-50 px-2 py-0.5 rounded border border-blue-200 flex justify-between text-blue-900 font-medium">
                                                <span>{{ m.material?.name }}</span>
                                                <span class="font-bold text-blue-800">+{{ parseFloat(m.quantity_returned) }} {{ m.unit || m.material?.unit }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status Permintaan Tambahan Material Lapangan -->
                                    <div v-if="ticket.material_requests && ticket.material_requests.length > 0" class="mt-2 pt-2 border-t border-gray-100">
                                        <div class="text-[10px] text-gray-500 font-bold uppercase flex items-center justify-between">
                                            <span>Permintaan Lapangan:</span>
                                            <span v-if="ticket.material_requests.some(r => r.status === 'pending')" class="text-amber-600 font-bold">● Ada Pending</span>
                                        </div>
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            <span 
                                                v-for="mr in ticket.material_requests" 
                                                :key="'tag-' + mr.id" 
                                                class="px-2 py-0.5 rounded text-[10px] font-semibold border flex items-center gap-1"
                                                :class="{
                                                    'bg-amber-50 text-amber-800 border-amber-200': mr.status === 'pending',
                                                    'bg-emerald-50 text-emerald-800 border-emerald-200': mr.status === 'approved',
                                                    'bg-rose-50 text-rose-800 border-rose-200': mr.status === 'rejected',
                                                }"
                                            >
                                                <span>{{ mr.item_name || mr.material?.name || 'Material' }}: {{ parseFloat(mr.quantity) }} {{ mr.unit }}</span>
                                                <span v-if="mr.status === 'pending'">⏳</span>
                                                <span v-else-if="mr.status === 'approved'">✓</span>
                                                <span v-else-if="mr.status === 'rejected'">✗</span>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Status Material Bongkaran / Diganti Lapangan -->
                                    <div v-if="ticket.dismantled_materials && ticket.dismantled_materials.length > 0" class="mt-2 pt-2 border-t border-gray-100">
                                        <div class="text-[10px] text-amber-800 font-bold uppercase flex items-center justify-between">
                                            <span>🔄 Material Bongkaran:</span>
                                            <span v-if="ticket.dismantled_materials.some(d => d.handover_status === 'pending_handover')" class="text-amber-600 font-black animate-pulse">● Perlu Serah Terima</span>
                                        </div>
                                        <div class="space-y-1 mt-1">
                                            <div v-for="d in ticket.dismantled_materials" :key="'dm-' + d.id" class="text-[11px] px-2 py-0.5 rounded border flex justify-between items-center"
                                                :class="d.handover_status === 'pending_handover' ? 'bg-amber-50 border-amber-200 text-amber-900' : 'bg-gray-50 border-gray-200 text-gray-700'">
                                                <div class="truncate">
                                                    <span class="font-bold">{{ d.material?.name || d.item_name }}</span>
                                                    <span class="text-[10px] text-gray-500 ml-1">({{ d.replacement_type === 'full' ? 'Total' : 'Sebagian' }})</span>
                                                </div>
                                                <div class="shrink-0 flex items-center gap-1 font-mono font-bold">
                                                    <span>{{ parseFloat(d.quantity) }} {{ d.unit }}</span>
                                                    <span v-if="d.handover_status === 'pending_handover'" class="text-amber-600" title="Menunggu Diterima Gudang/Ka.Teknisi">⏳</span>
                                                    <span v-else class="text-emerald-600" title="Sudah Diterima">✓</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- 5. Aksi Ka. Teknisi -->
                                <td class="p-4 text-center">
                                    <div class="flex flex-col sm:flex-row items-center justify-center gap-1.5">
                                        <button 
                                            v-if="can('pgk_btn_assign')"
                                            @click="openScheduleModal(ticket)" 
                                            class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl shadow-md shadow-amber-500/20 transition-all flex items-center gap-1 shrink-0"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                            <span>{{ ticket.status === 'scheduled' ? 'Ubah Jadwal' : 'Atur Jadwal' }}</span>
                                        </button>

                                        <!-- Tombol Review Permintaan Material Lapangan -->
                                        <button 
                                            v-if="ticket.material_requests && ticket.material_requests.length > 0 && can('pgk_btn_assign')"
                                            @click="openMaterialReviewModal(ticket)" 
                                            class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1 border shadow-sm shrink-0"
                                            :class="ticket.material_requests.some(r => r.status === 'pending') ? 'bg-amber-500 hover:bg-amber-600 text-white border-amber-500 shadow-amber-500/20 animate-pulse' : 'bg-gray-50 hover:bg-gray-100 text-gray-700 border-gray-200'"
                                            title="Review Permintaan Tambahan Material dari Teknisi"
                                        >
                                            <span>📦</span>
                                            <span>Review Material</span>
                                            <span v-if="ticket.material_requests.some(r => r.status === 'pending')" class="px-1.5 py-0.2 rounded-full bg-white text-amber-700 font-mono text-[10px]">
                                                {{ ticket.material_requests.filter(r => r.status === 'pending').length }}
                                            </span>
                                        </button>

                                        <!-- Tombol Siapkan Unit ONT dari Gudang -->
                                        <button 
                                            v-if="can('pgk_btn_assign') && ticket.status !== 'completed'"
                                            @click="openOntPrepareModal(ticket)" 
                                            class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1 border shadow-sm shrink-0"
                                            :class="ticket.ont_replacement_status === 'requested' ? 'bg-indigo-600 hover:bg-indigo-700 text-white border-indigo-600 shadow-indigo-600/20 animate-pulse' : (ticket.ont_replacement_status === 'prepared' ? 'bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-gray-50 hover:bg-gray-100 text-gray-700 border-gray-200')"
                                            title="Siapkan Unit ONT Pengganti dari Stok Gudang"
                                        >
                                            <span>🔄</span>
                                            <span>{{ ticket.ont_replacement_status === 'prepared' ? 'ONT Disiapkan' : 'Siapkan ONT' }}</span>
                                        </button>

                                        <!-- Shortcut Laporan untuk Tiket Selesai -->
                                        <a 
                                            v-if="ticket.status === 'completed'"
                                            :href="`/pgk/laporan?search=${ticket.ticket_number}`" 
                                            class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl text-xs font-bold transition-all flex items-center gap-1 shrink-0"
                                            title="Buka Laporan Lengkap Penanganan"
                                        >
                                            <span>📋</span>
                                            <span>Laporan</span>
                                        </a>

                                        <a :href="`/pgk/${ticket.id}/print`" target="_blank" title="Cetak SPK Tugas Lapangan" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        </a>

                                        <!-- Tombol Batalkan & Hapus Tiket -->
                                        <button 
                                            v-if="can('pgk_btn_delete') || $page.props.auth?.user?.roles?.includes('admin')"
                                            @click="deleteTicket(ticket)" 
                                            class="p-2 text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-xl transition-colors shrink-0"
                                            title="Batalkan & Hapus Tiket (Material Kembali ke Gudang)"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="tickets.data.length === 0">
                                <td colspan="5" class="p-8 text-center text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span v-if="currentStatus === 'completed'">Tidak ada riwayat tiket gangguan komplain yang sudah selesai dikerjakan pada filter ini.</span>
                                    <span v-else-if="currentStatus === 'waiting'">Tidak ada tiket gangguan yang membutuhkan penjadwalan saat ini.</span>
                                    <span v-else-if="currentStatus === 'scheduled'">Tidak ada tiket gangguan yang sedang terjadwal/di lapangan saat ini.</span>
                                    <span v-else>Tidak ada tiket gangguan komplain pada tab ini saat ini.</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="tickets.links && tickets.links.length > 3" class="p-4 border-t border-gray-100 flex items-center justify-between">
                    <div class="text-xs text-gray-500">
                        Menampilkan {{ tickets.from || 0 }} - {{ tickets.to || 0 }} dari {{ tickets.total || 0 }} komplain
                    </div>
                    <div class="flex gap-1">
                        <template v-for="(link, idx) in tickets.links" :key="idx">
                            <Link 
                                v-if="link.url" 
                                :href="link.url" 
                                v-html="link.label"
                                preserve-scroll
                                preserve-state
                                class="px-3 py-1 text-xs rounded-lg transition-colors border"
                                :class="link.active ? 'bg-amber-500 text-white border-amber-500 font-bold' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-200'"
                            />
                            <span v-else v-html="link.label" class="px-3 py-1 text-xs rounded-lg text-gray-300 border border-transparent"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════ MODAL ATUR JADWAL & MATERIAL ════════════ -->
        <div v-if="showScheduleModal" class="fixed inset-0 z-50 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-gray-100 overflow-hidden animate-fade-in-up">
                <div class="px-6 py-5 bg-gradient-to-r from-amber-500 to-orange-600 text-white flex justify-between items-center">
                    <div>
                        <h3 class="font-black text-lg">Jadwal Teknisi & Material PGK #{{ selectedTicket?.ticket_number }}</h3>
                        <p class="text-xs text-amber-100 mt-0.5">Tentukan teknisi pelaksana, jam kunjungan, dan bawa material gudang</p>
                    </div>
                    <button @click="showScheduleModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <form @submit.prevent="submitSchedule" class="p-6 space-y-4 max-h-[85vh] overflow-y-auto">
                    <!-- Ringkasan Keluhan & Pelanggan -->
                    <div class="p-3.5 rounded-2xl bg-amber-50/70 border border-amber-100 text-xs text-amber-900 space-y-1">
                        <div class="font-bold flex justify-between">
                            <span>Pelanggan: {{ selectedTicket?.customer?.name }} ({{ selectedTicket?.customer?.customer_code }})</span>
                            <span>Area: {{ selectedTicket?.area?.name || selectedTicket?.customer?.area_model?.name || '-' }}</span>
                        </div>
                        <div>Alamat: {{ selectedTicket?.customer?.address || '-' }}</div>
                        <div class="text-red-700 font-medium">Keluhan: "{{ selectedTicket?.reported_issue }}"</div>
                        <div class="text-amber-800 font-semibold">Alasan Eskalasi: "{{ selectedTicket?.escalation_reason }}"</div>
                    </div>

                    <!-- Tanggal & Jam Penanganan -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tanggal Kunjungan Lapangan <span class="text-red-500">*</span></label>
                            <input 
                                type="date" 
                                v-model="scheduleForm.scheduled_date" 
                                class="w-full rounded-xl border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-sm" 
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Estimasi Jam Kunjungan</label>
                            <input 
                                type="text" 
                                v-model="scheduleForm.scheduled_time" 
                                placeholder="Contoh: 10:00 - 12:00 WIB"
                                class="w-full rounded-xl border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-sm" 
                            />
                        </div>
                    </div>

                    <!-- Pilih Teknisi Pelaksana -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Pilih Teknisi Pelaksana <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 p-3 rounded-2xl bg-gray-50 border border-gray-200 max-h-36 overflow-y-auto">
                            <label 
                                v-for="t in technicians" 
                                :key="t.id" 
                                class="flex items-center gap-2 p-2 rounded-xl bg-white border border-gray-200 text-xs font-semibold cursor-pointer hover:border-amber-400 transition-colors"
                            >
                                <input 
                                    type="checkbox" 
                                    :value="t.id" 
                                    v-model="scheduleForm.technician_ids" 
                                    class="rounded text-amber-500 focus:ring-amber-400"
                                />
                                <span class="truncate">{{ t.name }}</span>
                            </label>
                        </div>
                        <p v-if="scheduleForm.technician_ids.length === 0" class="text-[11px] text-red-500 mt-1">Pilih minimal 1 orang teknisi pelaksana.</p>
                        <p v-if="scheduleForm.errors.technicians || scheduleForm.errors.technician_ids" class="text-[11px] text-red-500 mt-1 font-semibold">{{ scheduleForm.errors.technicians || scheduleForm.errors.technician_ids }}</p>
                    </div>

                    <!-- Catatan Instruksi Ka. Teknisi -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Instruksi Kerja dari Kepala Teknisi</label>
                        <textarea 
                            v-model="scheduleForm.lead_notes" 
                            rows="2" 
                            placeholder="Contoh: Cek core 2 ODP-BTR-04, lakukan sambung ulang (splicing) dan ukur redaman sebelum pasang ke ONT."
                            class="w-full rounded-xl border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-sm"
                        ></textarea>
                    </div>

                    <!-- Review Permintaan Material Lapangan (Jika Ada) -->
                    <div v-if="selectedTicket?.material_requests && selectedTicket.material_requests.length > 0" class="p-4 rounded-2xl bg-amber-50/90 border border-amber-300 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="text-xs font-bold text-amber-900 flex items-center gap-1.5">
                                <span class="text-base">🔔</span>
                                <span>Permintaan Tambahan Material Lapangan ({{ selectedTicket.material_requests.length }})</span>
                            </div>
                            <span v-if="selectedTicket.material_requests.some(r => r.status === 'pending')" class="text-[10px] font-bold text-amber-800 bg-amber-200 px-2 py-0.5 rounded-full border border-amber-400 animate-pulse">
                                {{ selectedTicket.material_requests.filter(r => r.status === 'pending').length }} Menunggu Persetujuan
                            </span>
                        </div>

                        <div class="space-y-2">
                            <div 
                                v-for="mr in selectedTicket.material_requests" 
                                :key="'sched-mr-' + mr.id" 
                                class="p-3 rounded-xl bg-white border border-gray-200 text-xs space-y-2"
                            >
                                <div class="flex items-center justify-between flex-wrap gap-1">
                                    <div class="font-bold text-gray-900 flex items-center gap-1.5">
                                        <span class="text-sm">📦</span>
                                        <span>{{ mr.material?.name }}</span>
                                        <span class="px-2 py-0.5 rounded bg-gray-100 font-mono font-bold text-gray-800">
                                            {{ parseFloat(mr.quantity) }} {{ mr.unit }}
                                        </span>
                                    </div>
                                    <div>
                                        <span v-if="mr.status === 'pending'" class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold border border-amber-300 text-[10px]">⏳ Menunggu Persetujuan</span>
                                        <span v-else-if="mr.status === 'approved'" class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold border border-emerald-300 text-[10px]">✅ Disetujui</span>
                                        <span v-else class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 font-bold border border-rose-300 text-[10px]">❌ Ditolak</span>
                                    </div>
                                </div>

                                <div class="text-gray-700 bg-gray-50 p-2 rounded-lg border border-gray-100 text-[11px]">
                                    <span class="font-semibold text-gray-500">Alasan Lapangan:</span> "{{ mr.notes }}"
                                </div>

                                <div class="flex items-center justify-between pt-1 flex-wrap gap-2">
                                    <div class="text-[11px] text-gray-500">
                                        Stok gudang: <b class="font-mono text-gray-800">{{ parseFloat(mr.material?.stock || 0) }} {{ mr.unit }}</b>
                                    </div>
                                    <div v-if="mr.status === 'pending'" class="flex items-center gap-1.5">
                                        <button 
                                            type="button" 
                                            @click="rejectRequest(selectedTicket, mr)" 
                                            class="px-2.5 py-1 bg-white hover:bg-rose-50 border border-rose-300 text-rose-700 rounded-lg text-[11px] font-bold transition-colors"
                                        >
                                            ✗ Tolak
                                        </button>
                                        <button 
                                            type="button" 
                                            @click="approveRequest(selectedTicket, mr)" 
                                            class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-bold shadow-sm transition-all flex items-center gap-1"
                                        >
                                            <span>✓ Setujui & Beri Tambahan</span>
                                        </button>
                                    </div>
                                    <div v-else-if="mr.status === 'approved'" class="text-[11px] text-emerald-700 font-semibold">
                                        ✓ Telah disetujui & otomatis masuk ke bekal penugasan.
                                    </div>
                                    <div v-else class="text-[11px] text-rose-700 font-semibold">
                                        ✗ Ditolak: "{{ mr.rejection_reason || '-' }}"
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Penyiapan Material dari Inventory Gudang -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase">Material dari Inventory Gudang</label>
                                <span class="text-[11px] text-gray-500">Ambil material dari persediaan inventory gudang & area pelanggan</span>
                            </div>
                            <button type="button" @click="addMaterialRow" class="text-xs font-bold text-amber-600 hover:text-amber-700 bg-amber-50 hover:bg-amber-100 px-2.5 py-1.5 rounded-lg border border-amber-200 flex items-center gap-1 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>+ Tambah Material dari Gudang</span>
                            </button>
                        </div>

                        <div v-if="scheduleForm.materials.length > 0" class="space-y-2.5">
                            <div v-for="(item, idx) in scheduleForm.materials" :key="idx" class="p-2.5 rounded-xl bg-gray-50 border border-gray-200/80 shadow-xs space-y-1.5">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 min-w-0">
                                        <select 
                                            v-model="item.selected_key" 
                                            @change="onMaterialItemChange(item)"
                                            class="w-full rounded-xl border-gray-300 text-xs py-2 bg-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500 truncate" 
                                            required
                                        >
                                            <option value="">-- Pilih Material dari Inventory Gudang --</option>
                                            <option v-for="ord in availableMaterialItems" :key="ord.unique_key" :value="ord.unique_key">
                                                {{ ord.name }} — Stok Gudang: {{ ord.area_stock }} {{ ord.unit }} {{ ord.trx_number ? '(PO: ' + ord.trx_number + ')' : '' }} [{{ ord.area_name }}]
                                            </option>
                                        </select>
                                    </div>

                                    <div class="w-24 shrink-0 flex items-center gap-1">
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            min="0.01" 
                                            :max="item.max_stock || undefined"
                                            v-model.number="item.quantity" 
                                            @input="validateMaterialQty(item)"
                                            placeholder="Qty" 
                                            class="w-full rounded-xl border-gray-300 text-xs py-2 text-center font-bold text-amber-700 bg-amber-50/50 focus:border-amber-500" 
                                            required
                                        />
                                    </div>

                                    <span class="text-xs font-semibold text-gray-500 w-12 text-center truncate shrink-0">{{ item.unit || '-' }}</span>

                                    <button type="button" @click="removeMaterialRow(idx)" class="p-2 text-red-500 hover:bg-red-50 rounded-xl transition-colors shrink-0" title="Hapus Material">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <div v-if="item.trx_number || item.max_stock" class="flex items-center justify-between text-[10px] text-gray-500 px-1">
                                    <span class="truncate">
                                        <span class="font-medium text-gray-400">Sumber:</span> 
                                        <b class="font-mono text-amber-800 ml-1">{{ item.trx_number ? 'PO ' + item.trx_number : 'Inventory Gudang' }}</b>
                                    </span>
                                    <span v-if="item.max_stock" class="shrink-0 text-emerald-700 font-medium">
                                        Stok Tersedia: {{ item.max_stock }} {{ item.unit }}
                                    </span>
                                </div>
                            </div>
                            <p class="text-[11px] text-gray-400 italic">* Material diambil langsung dari persediaan Inventory Gudang wilayah terkait dan dialokasikan untuk penugasan ini. Saat pekerjaan selesai, sisa/kelebihan material otomatis dikembalikan ke Inventory Gudang.</p>
                        </div>
                        <div v-else class="p-3 rounded-xl bg-amber-50/40 border border-dashed border-amber-200 text-center text-xs text-amber-700">
                            Belum ada material yang ditambahkan dari Inventory Gudang. Klik <b>+ Tambah Material dari Gudang</b> jika teknisi membawa bekal material.
                        </div>
                    </div>

                    <div class="pt-3 border-t flex justify-end gap-2">
                        <button type="button" @click="showScheduleModal = false" class="px-4 py-2 border rounded-xl text-gray-600 text-sm font-semibold hover:bg-gray-50">Batal</button>
                        <button 
                            type="submit" 
                            :disabled="scheduleForm.processing || scheduleForm.technician_ids.length === 0" 
                            class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-bold shadow-md shadow-amber-500/20 disabled:opacity-50"
                        >
                            {{ scheduleForm.processing ? 'Menyimpan...' : 'Simpan Jadwal & Tugaskan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ════════════ MODAL REVIEW PERMINTAAN MATERIAL ════════════ -->
        <div v-if="showMaterialReviewModal" class="fixed inset-0 z-50 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-gray-100 overflow-hidden animate-fade-in-up">
                <div class="px-6 py-5 bg-gradient-to-r from-amber-500 to-orange-600 text-white flex justify-between items-center">
                    <div>
                        <h3 class="font-black text-lg flex items-center gap-2">
                            <span>📦</span> Review Permintaan Tambahan Material PGK #{{ reviewTicket?.ticket_number }}
                        </h3>
                        <p class="text-xs text-amber-100 mt-0.5">Permintaan material lapangan yang diajukan oleh teknisi penanganan</p>
                    </div>
                    <button @click="showMaterialReviewModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <div class="p-6 space-y-4 max-h-[85vh] overflow-y-auto">
                    <!-- Info Pelanggan & Lokasi -->
                    <div class="p-3.5 rounded-2xl bg-amber-50/70 border border-amber-100 text-xs text-amber-900 space-y-1">
                        <div class="font-bold flex justify-between">
                            <span>Pelanggan: {{ reviewTicket?.customer?.name }} ({{ reviewTicket?.customer?.customer_code }})</span>
                            <span>Area: {{ reviewTicket?.area?.name || reviewTicket?.customer?.area_model?.name || '-' }}</span>
                        </div>
                        <div>Alamat: {{ reviewTicket?.customer?.address || '-' }}</div>
                        <div class="text-gray-600">Teknisi Lapangan: <b>{{ reviewTicket?.technicians?.map(t => t.name).join(', ') || 'Belum ada teknisi' }}</b></div>
                    </div>

                    <!-- Daftar Permintaan Material -->
                    <div class="space-y-4">
                        <div 
                            v-for="mr in reviewTicket?.material_requests" 
                            :key="'rev-' + mr.id"
                            class="p-4 rounded-2xl border transition-all text-xs space-y-3"
                            :class="{
                                'bg-amber-50/70 border-amber-300 shadow-sm': mr.status === 'pending',
                                'bg-emerald-50/70 border-emerald-300': mr.status === 'approved',
                                'bg-rose-50/70 border-rose-300': mr.status === 'rejected'
                            }"
                        >
                            <!-- Header Info Permintaan Teknisi -->
                            <div class="flex items-start justify-between gap-2 flex-wrap">
                                <div>
                                    <div class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                        <span>📦 Kebutuhan: {{ mr.item_name || mr.material?.name || 'Material Tambahan' }}</span>
                                        <span class="px-2.5 py-0.5 rounded-lg bg-white border font-mono font-black text-amber-700">
                                            {{ parseFloat(mr.quantity) }} {{ mr.unit || mr.material?.unit }}
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-gray-500 mt-0.5">
                                        Diajukan oleh: <b>{{ mr.requester?.name || 'Teknisi Lapangan' }}</b> · {{ formatDate(mr.created_at) }}
                                    </div>
                                </div>

                                <div>
                                    <span v-if="mr.status === 'pending'" class="px-3 py-1 rounded-full text-xs font-bold bg-amber-200 text-amber-900 border border-amber-300 flex items-center gap-1.5 animate-pulse">
                                        <span class="w-2 h-2 rounded-full bg-amber-600"></span>
                                        <span>Menunggu Persetujuan Ka. Teknisi</span>
                                    </span>
                                    <span v-else-if="mr.status === 'approved'" class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-200 text-emerald-900 border border-emerald-300">
                                        ✅ Telah Disetujui & Masuk Bekal
                                    </span>
                                    <span v-else class="px-3 py-1 rounded-full text-xs font-bold bg-rose-200 text-rose-900 border border-rose-300">
                                        ❌ Ditolak
                                    </span>
                                </div>
                            </div>

                            <!-- Alasan Kebutuhan Teknisi di Lapangan -->
                            <div class="bg-white/90 p-3 rounded-xl border border-gray-200 text-xs">
                                <div class="font-bold text-gray-500 text-[10px] uppercase mb-0.5">Keterangan / Kondisi Lapangan dari Teknisi:</div>
                                <div class="text-gray-800 font-medium">"{{ mr.notes }}"</div>
                            </div>

                            <!-- Form Alokasi Material Gudang oleh Ka. Teknisi jika Status PENDING -->
                            <div v-if="mr.status === 'pending'" class="p-3.5 rounded-xl bg-white border border-amber-300 space-y-3">
                                <div class="flex items-center justify-between text-xs font-bold text-amber-950">
                                    <span class="flex items-center gap-1.5">
                                        <span>⚙️</span> Tentukan Material Gudang untuk Dialokasikan:
                                    </span>
                                    <span v-if="allocationForms[mr.id]?.material_id" class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                        ✓ Material Terpilih
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5">
                                    <!-- Dropdown Material Gudang -->
                                    <div class="sm:col-span-8">
                                        <label class="block text-[10px] font-bold text-gray-700 uppercase mb-0.5">Pilih Material dari Gudang *</label>
                                        <select 
                                            v-model="allocationForms[mr.id].material_id" 
                                            class="w-full rounded-xl border-gray-300 text-xs py-1.5 focus:border-emerald-500 bg-white font-medium"
                                        >
                                            <option value="">-- Pilih Material Gudang yang Sesuai --</option>
                                            <option v-for="mat in materials" :key="mat.id" :value="mat.id">
                                                {{ mat.name }} (Stok Gudang: {{ parseFloat(mat.stock) }} {{ mat.unit }})
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Approved Qty -->
                                    <div class="sm:col-span-4">
                                        <label class="block text-[10px] font-bold text-gray-700 uppercase mb-0.5">
                                            Jumlah Alokasi ({{ getSelectedMaterial(allocationForms[mr.id]?.material_id)?.unit || mr.unit }}) *
                                        </label>
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            min="0.01" 
                                            v-model="allocationForms[mr.id].quantity" 
                                            class="w-full rounded-xl border-gray-300 text-xs py-1.5 text-center font-black focus:border-emerald-500 bg-white"
                                            placeholder="Jumlah"
                                        />
                                    </div>
                                </div>

                                <!-- Status Ketersediaan Stok Gudang -->
                                <div v-if="allocationForms[mr.id]?.material_id" class="flex items-center justify-between text-xs px-3 py-2 rounded-xl border" :class="isStockSufficient(mr) ? 'bg-emerald-50/70 border-emerald-200 text-emerald-900' : 'bg-red-50 border-red-200 text-red-900'">
                                    <div class="flex items-center gap-1.5">
                                        <span>📦 Stok Gudang:</span>
                                        <b class="font-mono text-sm">{{ parseFloat(getSelectedMaterial(allocationForms[mr.id].material_id)?.stock || 0) }} {{ getSelectedMaterial(allocationForms[mr.id].material_id)?.unit }}</b>
                                    </div>
                                    <div v-if="!isStockSufficient(mr)" class="text-[11px] font-bold text-red-700 animate-pulse">
                                        ⚠️ Stok gudang kurang untuk jumlah alokasi ini!
                                    </div>
                                    <div v-else class="text-[11px] font-semibold text-emerald-700">
                                        ✓ Stok mencukupi
                                    </div>
                                </div>
                            </div>

                            <!-- Reviewer info if resolved -->
                            <div v-if="mr.status === 'approved'" class="text-emerald-800 text-[11px] font-semibold flex items-center gap-1 bg-emerald-50 p-2.5 rounded-xl border border-emerald-200">
                                <span>🏷️ Dialokasikan dari gudang sebagai <b>{{ mr.material?.name }}</b> ({{ parseFloat(mr.quantity) }} {{ mr.unit }}) oleh <b>{{ mr.reviewer?.name || 'Ka. Teknisi' }}</b> pada {{ formatDate(mr.reviewed_at) }}. Stok gudang otomatis terpotong & dialokasikan ke bekal tiket.</span>
                            </div>
                            <div v-else-if="mr.status === 'rejected'" class="text-rose-800 text-[11px] font-semibold flex items-center gap-1 bg-rose-50 p-2.5 rounded-xl border border-rose-200">
                                <span>✗ Ditolak oleh <b>{{ mr.reviewer?.name || 'Ka. Teknisi' }}</b>: "{{ mr.rejection_reason || '-' }}"</span>
                            </div>

                            <!-- Tombol Aksi Persetujuan jika Status PENDING -->
                            <div v-if="mr.status === 'pending'" class="pt-2 flex items-center justify-end gap-2 border-t border-amber-200/80">
                                <button 
                                    type="button" 
                                    @click="rejectRequest(reviewTicket, mr)" 
                                    :disabled="processingRequest[mr.id]"
                                    class="px-3.5 py-1.5 rounded-xl border border-rose-300 bg-white hover:bg-rose-50 text-rose-700 text-xs font-bold transition-all shadow-sm disabled:opacity-50 cursor-pointer"
                                >
                                    ✗ Tolak Permintaan
                                </button>
                                <button 
                                    type="button" 
                                    @click="approveRequest(reviewTicket, mr)" 
                                    :disabled="processingRequest[mr.id] || !allocationForms[mr.id]?.material_id"
                                    class="px-4 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-bold transition-all shadow-md shadow-emerald-600/20 flex items-center gap-1.5 cursor-pointer"
                                >
                                    <svg v-if="processingRequest[mr.id]" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>{{ processingRequest[mr.id] ? 'Memproses...' : '✓ Setujui & Keluarkan Stok Gudang' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t flex justify-end">
                        <button type="button" @click="showMaterialReviewModal = false" class="px-5 py-2 border rounded-xl text-gray-700 text-xs font-bold hover:bg-gray-50">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════ MODAL SIAPKAN UNIT ONT DARI GUDANG ════════ -->
        <div v-if="showOntPrepareModal" class="fixed inset-0 z-50 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
            <div class="bg-white rounded-3xl max-w-xl w-full shadow-2xl border border-gray-100 overflow-hidden animate-fade-in-up my-auto flex flex-col max-h-[90vh]">
                <!-- Header Modal -->
                <div class="px-6 py-4 bg-gradient-to-r from-indigo-700 via-indigo-600 to-blue-700 text-white flex justify-between items-center shrink-0">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-black text-lg">Siapkan ONT Baru dari Inventory Gudang</h3>
                            <span class="px-2 py-0.5 rounded-md bg-white/20 text-white font-mono text-xs font-bold">#{{ ontTicket?.ticket_number }}</span>
                        </div>
                        <p class="text-xs text-indigo-100 mt-0.5">Pilih unit modem ONT siap pakai dari stok gudang untuk dibekalkan ke teknisi</p>
                    </div>
                    <button @click="showOntPrepareModal = false" class="text-white/80 hover:text-white text-2xl font-bold leading-none p-1 rounded-lg hover:bg-white/10 transition-colors">&times;</button>
                </div>

                <div class="p-6 space-y-4 overflow-y-auto flex-1">
                    <!-- Ringkasan Tiket & Pelanggan -->
                    <div class="p-3.5 rounded-2xl bg-indigo-50/70 border border-indigo-100 text-xs text-indigo-950 space-y-1">
                        <div class="font-bold flex justify-between">
                            <span>Pelanggan: {{ ontTicket?.customer?.name }} ({{ ontTicket?.customer?.customer_code }})</span>
                            <span>Area: {{ ontTicket?.area?.name || ontTicket?.customer?.area_model?.name || '-' }}</span>
                        </div>
                        <div>Alamat: {{ ontTicket?.customer?.address || '-' }}</div>
                        <div class="text-gray-600">Teknisi Ditugaskan: <b>{{ ontTicket?.technicians?.map(t => t.name).join(', ') || 'Belum ada teknisi' }}</b></div>
                    </div>

                    <!-- Info ONT Lama Pelanggan -->
                    <div class="p-3 rounded-xl bg-white border border-gray-200 text-xs space-y-1">
                        <div class="text-[10px] font-bold text-gray-400 uppercase">ONT Lama Pelanggan Saat Ini:</div>
                        <div class="flex items-center justify-between">
                            <span class="font-mono font-bold text-gray-800">{{ ontTicket?.customer?.ont?.serial_number || 'Tidak terdeteksi / Belum ada' }}</span>
                            <span class="text-gray-500 font-medium">{{ ontTicket?.customer?.ont?.brand || '' }} {{ ontTicket?.customer?.ont?.model || '' }}</span>
                        </div>
                    </div>

                    <!-- Catatan Permintaan dari Teknisi (Jika Ada) -->
                    <div v-if="ontTicket?.requested_ont_notes" class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900 space-y-1">
                        <div class="font-bold flex items-center gap-1.5 text-amber-800 text-[11px]">
                            <span>📢</span> Catatan Kebutuhan dari Teknisi Lapangan:
                        </div>
                        <div class="italic bg-white/70 p-2 rounded-lg border border-amber-200">
                            "{{ ontTicket.requested_ont_notes }}"
                        </div>
                        <div class="text-[10px] text-amber-700">
                            Diajukan oleh: <b>{{ ontTicket.requested_by_user?.name || 'Teknisi' }}</b> ({{ formatDate(ontTicket.requested_ont_at) }})
                        </div>
                    </div>

                    <!-- Jika Unit Sudah Disiapkan Saat Ini -->
                    <div v-if="ontTicket?.ont_replacement_status === 'prepared'" class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-300 text-xs text-emerald-950 space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="font-bold text-emerald-900 flex items-center gap-1.5">
                                <span>📦</span>
                                <span>Unit Saat Ini yang Sedang Disiapkan:</span>
                            </div>
                            <button 
                                type="button" 
                                @click="cancelPreparedOntFromLead(ontTicket)" 
                                class="px-2.5 py-1 rounded-lg bg-rose-100 hover:bg-rose-200 text-rose-800 font-bold border border-rose-300 text-[11px] transition-all shadow-sm"
                            >
                                ↩️ Batalkan & Retur ke Gudang
                            </button>
                        </div>
                        <div class="bg-white p-2.5 rounded-xl border border-emerald-200 font-mono text-sm font-bold text-emerald-700 flex justify-between items-center">
                            <span>SN: {{ ontTicket.prepared_ont?.serial_number }}</span>
                            <span class="text-xs text-gray-500 font-sans font-medium">({{ ontTicket.prepared_ont?.brand }} {{ ontTicket.prepared_ont?.model }})</span>
                        </div>
                        <p class="text-[10px] text-emerald-700 italic">
                            Jika ingin mengganti dengan unit lain, pilih unit baru di bawah dan klik konfirmasi simpan.
                        </p>
                    </div>

                    <!-- Pilih Unit ONT dari Stok Inventory Gudang -->
                    <div class="space-y-2 pt-1">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-gray-800 uppercase tracking-wider">
                                Pilih Unit ONT Baru dari Gudang (Tersedia: {{ availableOnts?.length || 0 }} unit) *
                            </label>
                        </div>

                        <!-- Search Input -->
                        <div class="relative">
                            <input 
                                type="text" 
                                v-model="ontSearchQuery" 
                                placeholder="Ketik nomor SN atau Merk ONT untuk memfilter..." 
                                class="w-full pl-9 pr-3 py-2 rounded-xl border border-gray-200 text-xs focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 bg-gray-50/50"
                            />
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>

                        <!-- List Pilihan Unit ONT -->
                        <div class="max-h-48 overflow-y-auto space-y-1.5 border border-gray-200 rounded-2xl p-2 bg-gray-50/30">
                            <label 
                                v-for="ont in filteredAvailableOnts" 
                                :key="'avail-ont-' + ont.id"
                                class="p-2.5 rounded-xl border flex items-center justify-between gap-2 cursor-pointer transition-all"
                                :class="ontPrepareForm.prepared_ont_id == ont.id ? 'bg-indigo-50 border-indigo-500 ring-2 ring-indigo-200' : 'bg-white hover:bg-gray-50 border-gray-200'"
                            >
                                <div class="flex items-center gap-2.5">
                                    <input type="radio" v-model="ontPrepareForm.prepared_ont_id" :value="ont.id" class="text-indigo-600 focus:ring-indigo-500">
                                    <div>
                                        <div class="font-mono font-bold text-xs text-gray-900">{{ ont.serial_number }}</div>
                                        <div class="text-[10px] text-gray-500">{{ ont.brand || 'ONT' }} {{ ont.model || '' }} · ID: {{ ont.ont_id || '-' }}</div>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    {{ ont.status || 'Siap' }}
                                </span>
                            </label>

                            <div v-if="filteredAvailableOnts.length === 0" class="p-4 text-center text-xs text-gray-400 italic">
                                Tidak ada unit ONT yang sesuai dengan pencarian atau stok gudang kosong.
                            </div>
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="pt-3 border-t flex items-center justify-end gap-2">
                        <button 
                            type="button" 
                            @click="showOntPrepareModal = false" 
                            class="px-4 py-2 border border-gray-200 rounded-xl text-gray-700 text-xs font-bold hover:bg-gray-50"
                        >
                            Tutup
                        </button>
                        <button 
                            type="button" 
                            @click="submitPrepareOnt" 
                            :disabled="ontPrepareForm.processing || !ontPrepareForm.prepared_ont_id" 
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black shadow-md shadow-indigo-600/20 disabled:opacity-50 transition-all flex items-center gap-1.5"
                        >
                            <span>{{ ontPrepareForm.processing ? 'Menyimpan...' : '✓ Konfirmasi Siapkan ONT untuk Teknisi' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermission } from '@/Composables/usePermission';

const { can } = usePermission();

const props = defineProps({
    tickets: Object,
    areas: Array,
    technicians: Array,
    materials: Array,
    materialTransactions: {
        type: Array,
        default: () => [],
    },
    availableOnts: {
        type: Array,
        default: () => [],
    },
    counts: {
        type: Object,
        default: () => ({ active: 0, waiting: 0, scheduled: 0, completed: 0, all: 0 }),
    },
    filters: Object,
});

const search = ref(props.filters?.search || '');
const filterArea = ref(props.filters?.area_id || '');
const filterStatus = ref(props.filters?.status || 'active');
const filterPriority = ref(props.filters?.priority || '');
const filterTechnician = ref(props.filters?.technician_id || '');
const filterPeriod = ref(props.filters?.period || '');
const filterStartDate = ref(props.filters?.start_date || '');
const filterEndDate = ref(props.filters?.end_date || '');
const filterMaterial = ref(props.filters?.material_filter || '');
const currentStatus = filterStatus;

const showScheduleModal = ref(false);
const selectedTicket = ref(null);

const showMaterialReviewModal = ref(false);
const reviewTicket = ref(null);

// Modal Siapkan ONT dari Gudang State
const showOntPrepareModal = ref(false);
const ontTicket = ref(null);
const ontSearchQuery = ref('');
const ontPrepareForm = useForm({
    prepared_ont_id: '',
});

const openOntPrepareModal = (ticket) => {
    ontTicket.value = ticket;
    ontSearchQuery.value = '';
    ontPrepareForm.prepared_ont_id = ticket.prepared_ont_id || '';
    showOntPrepareModal.value = true;
};

const filteredAvailableOnts = computed(() => {
    const q = ontSearchQuery.value.toLowerCase().trim();
    const list = props.availableOnts || [];
    if (!q) return list;
    return list.filter(o => 
        (o.serial_number && o.serial_number.toLowerCase().includes(q)) ||
        (o.brand && o.brand.toLowerCase().includes(q)) ||
        (o.model && o.model.toLowerCase().includes(q)) ||
        (o.ont_id && o.ont_id.toLowerCase().includes(q))
    );
});

const submitPrepareOnt = () => {
    if (!ontPrepareForm.prepared_ont_id) {
        alert('Silakan pilih salah satu unit ONT dari stok gudang!');
        return;
    }
    ontPrepareForm.post(`/pgk/${ontTicket.value.id}/prepare-ont`, {
        preserveScroll: true,
        onSuccess: () => {
            showOntPrepareModal.value = false;
            ontTicket.value = null;
        }
    });
};

const cancelPreparedOntFromLead = (ticket) => {
    if (!confirm('Apakah Anda yakin ingin membatalkan unit ONT ini? Unit ONT akan dikembalikan ke stok inventory gudang.')) {
        return;
    }
    router.post(`/pgk/${ticket.id}/cancel-ont`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            showOntPrepareModal.value = false;
            ontTicket.value = null;
        }
    });
};

const deleteTicket = (ticket) => {
    if (!ticket) return;
    if (confirm(`Apakah Anda yakin ingin membatalkan/menghapus tiket PGK #${ticket.ticket_number} (${ticket.customer?.name || 'Pelanggan'})? Seluruh material yang telah dialokasikan/disiapkan akan otomatis dikembalikan ke stok gudang.`)) {
        router.delete(`/pgk/${ticket.id}`);
    }
};

const customerAreaId = computed(() => {
    return selectedTicket.value?.area_id || 
           selectedTicket.value?.area?.id || 
           selectedTicket.value?.customer?.area_id || 
           selectedTicket.value?.customer?.areaModel?.id || 
           null;
});

const customerAreaName = computed(() => {
    return selectedTicket.value?.area?.name || 
           selectedTicket.value?.customer?.area || 
           selectedTicket.value?.customer?.areaModel?.name || 
           '';
});

const filteredMaterialTransactions = computed(() => {
    const allTrx = props.materialTransactions || [];
    const areaId = customerAreaId.value;
    const areaName = customerAreaName.value ? customerAreaName.value.toLowerCase().trim() : '';

    if (!areaId && !areaName) return allTrx;

    const matched = allTrx.filter(trx => {
        if (areaId && trx.area_id && trx.area_id == areaId) return true;
        if (areaName && trx.area && trx.area.toLowerCase().trim() === areaName) return true;
        if (areaName && trx.technician_name && trx.technician_name.toLowerCase().trim() === areaName) return true;
        if (areaName && trx.area_model?.name && trx.area_model.name.toLowerCase().trim() === areaName) return true;
        return false;
    });

    return matched.length > 0 ? matched : allTrx;
});

const availableMaterialItems = computed(() => {
    let items = [];
    const areaId = customerAreaId.value;
    const handledMaterialIds = new Set();

    // 1. Ambil dari Order Toko / Restock Area jika ada
    const trxs = filteredMaterialTransactions.value || [];
    trxs.forEach(trx => {
        if (trx.items && trx.items.length > 0) {
            trx.items.forEach(item => {
                if (!item.material) return;
                const name = (item.material.name || '').toLowerCase();
                const category = (item.material.category || '').toLowerCase();
                const isRegistered = item.is_registered_to_ont === 1 || item.is_registered_to_ont === true;

                if (!isRegistered && !name.includes('modem') && !category.includes('modem')) {
                    let remainingInOrder = typeof item.remaining_quantity !== 'undefined'
                        ? parseFloat(item.remaining_quantity)
                        : parseFloat(item.quantity);

                    let areaStock = null;
                    if (item.material.stocks && areaId) {
                        const stockObj = item.material.stocks.find(s => s.area_id == areaId);
                        if (stockObj) {
                            areaStock = parseFloat(stockObj.stock) || 0;
                        }
                    }

                    let availableQty = remainingInOrder;
                    if (areaStock !== null && areaStock < availableQty) {
                        availableQty = areaStock;
                    }

                    if (availableQty > 0) {
                        handledMaterialIds.add(item.material_id);
                        items.push({
                            unique_key: 'ord-' + item.id,
                            id: item.id,
                            material_id: item.material_id,
                            trx_number: trx.transaction_number,
                            trx_date: trx.date,
                            purpose: trx.purpose,
                            area_name: trx.area_model?.name || trx.area || trx.technician_name || 'Order Toko Area',
                            name: item.material.name,
                            category: item.material.category,
                            area_stock: availableQty,
                            unit: item.unit || item.material.unit || 'pcs',
                            material: item.material,
                        });
                    }
                }
            });
        }
    });

    // 2. Sertakan SEMUA Material dari Inventory Gudang
    const allMaterials = props.materials || [];
    allMaterials.forEach(m => {
        const name = (m.name || '').toLowerCase();
        const category = (m.category || '').toLowerCase();
        if (name.includes('modem') || category.includes('modem') || name.includes('ont') || category.includes('ont')) {
            return;
        }

        let areaStock = 0;
        if (m.stocks && areaId) {
            const stockObj = m.stocks.find(s => s.area_id == areaId);
            if (stockObj) {
                areaStock = parseFloat(stockObj.stock) || 0;
            }
        }
        if (areaStock <= 0 && m.stock) {
            areaStock = parseFloat(m.stock) || 0;
        }

        if (!handledMaterialIds.has(m.id)) {
            items.push({
                unique_key: 'mat-' + m.id,
                id: 'mat-' + m.id,
                material_id: m.id,
                trx_number: '',
                area_name: 'Inventory Gudang',
                name: m.name,
                category: m.category,
                area_stock: areaStock,
                unit: m.unit || 'pcs',
                material: m,
            });
        }
    });

    return items;
});

const scheduleForm = useForm({
    scheduled_date: '',
    scheduled_time: '',
    lead_notes: '',
    technician_ids: [],
    technicians: [],
    materials: [],
});

const hasActiveFilters = computed(() => {
    return Boolean(
        search.value ||
        (filterArea.value && filterArea.value !== '') ||
        (filterStatus.value && filterStatus.value !== 'active') ||
        (filterPriority.value && filterPriority.value !== '') ||
        (filterTechnician.value && filterTechnician.value !== '') ||
        (filterPeriod.value && filterPeriod.value !== '') ||
        (filterMaterial.value && filterMaterial.value !== '')
    );
});

const applyFilters = (overrides = {}) => {
    const params = {
        search: search.value,
        area_id: filterArea.value,
        status: filterStatus.value,
        priority: filterPriority.value,
        technician_id: filterTechnician.value,
        period: filterPeriod.value,
        start_date: filterStartDate.value,
        end_date: filterEndDate.value,
        material_filter: filterMaterial.value,
        ...overrides,
    };

    Object.keys(params).forEach(k => {
        if (params[k] === '' || params[k] === null || params[k] === undefined) {
            delete params[k];
        }
    });

    router.get('/pgk/jadwal', params, { preserveState: true, preserveScroll: true, replace: true });
};

const filterByCard = (cardType) => {
    if (cardType === 'all') {
        filterStatus.value = 'all';
        filterMaterial.value = '';
        applyFilters({ status: 'all', material_filter: '' });
    } else if (cardType === 'waiting') {
        filterStatus.value = 'waiting';
        applyFilters({ status: 'waiting' });
    } else if (cardType === 'scheduled') {
        filterStatus.value = 'scheduled';
        applyFilters({ status: 'scheduled' });
    } else if (cardType === 'completed') {
        filterStatus.value = 'completed';
        applyFilters({ status: 'completed' });
    } else if (cardType === 'materials') {
        filterStatus.value = 'all';
        filterMaterial.value = 'with_materials';
        applyFilters({ status: 'all', material_filter: 'with_materials' });
    }
};

const setStatusFilter = (st) => {
    filterStatus.value = st;
    applyFilters({ status: st });
};

const resetFilters = () => {
    search.value = '';
    filterArea.value = '';
    filterStatus.value = 'active';
    filterPriority.value = '';
    filterTechnician.value = '';
    filterPeriod.value = '';
    filterStartDate.value = '';
    filterEndDate.value = '';
    filterMaterial.value = '';
    applyFilters({
        search: '',
        area_id: '',
        status: 'active',
        priority: '',
        technician_id: '',
        period: '',
        start_date: '',
        end_date: '',
        material_filter: ''
    });
};

const openScheduleModal = (ticket) => {
    selectedTicket.value = ticket;
    scheduleForm.reset();

    scheduleForm.scheduled_date = ticket.scheduled_date || new Date().toISOString().split('T')[0];
    scheduleForm.scheduled_time = ticket.scheduled_time || '09:00 - 12:00';
    scheduleForm.lead_notes = ticket.lead_notes || '';
    const techIds = ticket.technicians ? ticket.technicians.map(t => t.id) : [];
    scheduleForm.technician_ids = [...techIds];
    scheduleForm.technicians = [...techIds];

    // Muat data material yang sudah disiapkan sebelumnya jika ada
    if (ticket.materials && ticket.materials.length > 0) {
        scheduleForm.materials = ticket.materials.map(m => {
            const matchedOrder = availableMaterialItems.value.find(
                i => i.id === m.material_transaction_item_id || (i.material_id === m.material_id && (!m.trx_number || i.trx_number === m.trx_number))
            );
            const selKey = m.material_transaction_item_id ? ('ord-' + m.material_transaction_item_id) : (matchedOrder ? matchedOrder.unique_key : ('mat-' + m.material_id));
            return {
                selected_key: selKey,
                material_transaction_item_id: m.material_transaction_item_id || null,
                material_id: m.material_id,
                trx_number: m.trx_number || (matchedOrder ? matchedOrder.trx_number : ''),
                name: m.material?.name || 'Material',
                quantity: parseFloat(m.quantity_prepared || m.quantity || 1),
                quantity_prepared: parseFloat(m.quantity_prepared || m.quantity || 1),
                unit: m.unit || m.material?.unit || '',
                max_stock: matchedOrder ? matchedOrder.area_stock : (parseFloat(m.material?.stock) || 9999),
                notes: m.notes || '',
            };
        });
    } else {
        scheduleForm.materials = [];
    }

    showScheduleModal.value = true;
};

const addMaterialRow = () => {
    scheduleForm.materials.push({
        selected_key: '',
        material_transaction_item_id: null,
        material_id: '',
        trx_number: '',
        name: '',
        quantity: 1,
        quantity_prepared: 1,
        unit: '',
        max_stock: null,
        notes: '',
    });
};

const removeMaterialRow = (index) => {
    scheduleForm.materials.splice(index, 1);
};

const onMaterialItemChange = (item) => {
    const selectedKey = item.selected_key || item.material_transaction_item_id;
    const selectedOrder = availableMaterialItems.value.find(i => i.unique_key === selectedKey || i.id === selectedKey || i.material_id === selectedKey);
    if (selectedOrder) {
        item.material_id = selectedOrder.material_id;
        item.trx_number = selectedOrder.trx_number || '';
        item.name = selectedOrder.name;
        item.unit = selectedOrder.unit;
        item.max_stock = selectedOrder.area_stock;
        item.material_transaction_item_id = selectedOrder.unique_key?.startsWith('ord-') ? (selectedOrder.id || null) : null;
        
        if (item.quantity && item.quantity > selectedOrder.area_stock) {
            item.quantity = selectedOrder.area_stock;
        } else if (!item.quantity || item.quantity <= 0) {
            item.quantity = 1;
        }
    }
};

const validateMaterialQty = (item) => {
    if (item.max_stock !== undefined && item.max_stock !== null) {
        if (item.quantity > item.max_stock) {
            item.quantity = item.max_stock;
            alert(`Stok tidak mencukupi! Maksimal stok untuk material dari order ini adalah ${item.max_stock} ${item.unit}`);
        }
    }
};

const submitSchedule = () => {
    if (!selectedTicket.value) return;

    // Pastikan technicians dan technician_ids terisi sinkron
    scheduleForm.technicians = [...scheduleForm.technician_ids];

    // Pastikan quantity dan quantity_prepared terisi
    scheduleForm.materials.forEach(m => {
        m.quantity_prepared = m.quantity;
    });

    scheduleForm.post(`/pgk/${selectedTicket.value.id}/schedule`, {
        preserveScroll: true,
        onSuccess: () => {
            showScheduleModal.value = false;
            selectedTicket.value = null;
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
            const msg = Object.values(errors).flat().join('\n');
            alert('Gagal menyimpan jadwal:\n' + msg);
        }
    });
};

// ── Review & Approval Permintaan Material Lapangan ───────────
const allocationForms = ref({});
const processingRequest = ref({});

const initAllocationForm = (mr) => {
    if (!allocationForms.value[mr.id]) {
        // Smart keyword matcher
        let matchedMaterialId = mr.material_id || '';
        if (!matchedMaterialId && (mr.item_name || mr.material?.name) && props.materials?.length > 0) {
            const searchStr = (mr.item_name || mr.material?.name || '').toLowerCase();
            const found = props.materials.find(m => {
                const mName = m.name.toLowerCase();
                if (mName.includes(searchStr) || searchStr.includes(mName)) return true;
                const keywords = ['dropcore', 'precon', 'kabel', 'fast connector', 'patchcord', 'clamp', 'klem', 'sleeve', 'roset', 'adapter', 'isolasi'];
                for (const kw of keywords) {
                    if (searchStr.includes(kw) && mName.includes(kw)) return true;
                }
                return false;
            });
            if (found) {
                matchedMaterialId = found.id;
            }
        }

        allocationForms.value[mr.id] = {
            material_id: matchedMaterialId,
            quantity: parseFloat(mr.quantity) || 1,
            notes: '',
        };
    }
    return allocationForms.value[mr.id];
};

const getSelectedMaterial = (materialId) => {
    if (!materialId || !props.materials) return null;
    return props.materials.find(m => m.id == materialId) || null;
};

const isStockSufficient = (mr) => {
    const form = allocationForms.value[mr.id];
    if (!form || !form.material_id) return true;
    const mat = getSelectedMaterial(form.material_id);
    if (!mat) return true;
    return parseFloat(mat.stock || 0) >= (parseFloat(form.quantity) || 0);
};

const openMaterialReviewModal = (ticket) => {
    reviewTicket.value = ticket;
    if (ticket.material_requests) {
        ticket.material_requests.forEach(mr => {
            initAllocationForm(mr);
        });
    }
    showMaterialReviewModal.value = true;
};

const approveRequest = (ticket, req) => {
    const form = allocationForms.value[req.id];
    if (!form || !form.material_id) {
        alert('Silakan pilih material gudang yang akan dialokasikan untuk permintaan ini.');
        return;
    }
    if (!form.quantity || form.quantity <= 0) {
        alert('Jumlah material yang disetujui harus lebih dari 0.');
        return;
    }

    const selectedMat = getSelectedMaterial(form.material_id);
    const matName = selectedMat ? selectedMat.name : 'Material Gudang';

    if (!confirm(`Setujui alokasi ${form.quantity} ${selectedMat?.unit || 'satuan'} '${matName}' untuk kebutuhan lapangan (${req.item_name || req.material?.name || 'Material'})?\n\nStok gudang akan otomatis dipotong dan dialokasikan ke bekal tiket ini.`)) {
        return;
    }

    processingRequest.value[req.id] = true;

    router.post(`/pgk/${ticket.id}/material-requests/${req.id}/approve`, {
        material_id: form.material_id,
        quantity: form.quantity,
        notes: form.notes || '',
    }, {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.flash?.error) {
                alert('Peringatan: ' + page.props.flash.error);
                return;
            }
            if (page.props.flash?.success) {
                alert(page.props.flash.success);
            }
            const freshTickets = page.props.tickets?.data || props.tickets?.data || [];
            const updated = freshTickets.find(t => t.id === ticket.id);
            if (updated) {
                reviewTicket.value = updated;
                if (updated.material_requests) {
                    updated.material_requests.forEach(mr => {
                        initAllocationForm(mr);
                    });
                }
                if (selectedTicket.value?.id === ticket.id) {
                    selectedTicket.value = updated;
                    if (updated.materials) {
                        scheduleForm.materials = updated.materials.map(m => ({
                            material_id: m.material_id,
                            quantity: parseFloat(m.quantity_prepared || m.quantity || 1),
                            quantity_prepared: parseFloat(m.quantity_prepared || m.quantity || 1),
                            unit: m.unit || m.material?.unit || '',
                        }));
                    }
                }
            }
        },
        onError: (errors) => {
            const msg = Object.values(errors).flat().join('\n');
            alert('Gagal menyetujui permintaan: ' + msg);
        },
        onFinish: () => {
            processingRequest.value[req.id] = false;
        }
    });
};

const rejectRequest = (ticket, req) => {
    const reason = prompt('Masukkan alasan penolakan permintaan material ini:');
    if (reason === null) return;
    if (!reason.trim()) {
        alert('Mohon tulis alasan penolakan.');
        return;
    }

    processingRequest.value[req.id] = true;

    router.post(`/pgk/${ticket.id}/material-requests/${req.id}/reject`, {
        rejection_reason: reason.trim()
    }, {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.flash?.error) {
                alert('Peringatan: ' + page.props.flash.error);
                return;
            }
            if (page.props.flash?.success) {
                alert(page.props.flash.success);
            }
            const freshTickets = page.props.tickets?.data || props.tickets?.data || [];
            const updated = freshTickets.find(t => t.id === ticket.id);
            if (updated) {
                reviewTicket.value = updated;
                if (updated.material_requests) {
                    updated.material_requests.forEach(mr => {
                        initAllocationForm(mr);
                    });
                }
                if (selectedTicket.value?.id === ticket.id) {
                    selectedTicket.value = updated;
                }
            }
        },
        onError: (errors) => {
            const msg = Object.values(errors).flat().join('\n');
            alert('Gagal menolak permintaan: ' + msg);
        },
        onFinish: () => {
            processingRequest.value[req.id] = false;
        }
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    } catch (e) {
        return dateStr;
    }
};
</script>
