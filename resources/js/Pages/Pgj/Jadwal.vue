<template>
    <AppLayout title="Jadwal & Material Jalur (PGJ)" subtitle="Penjadwalan Tim Lapangan & Alokasi Material FO oleh Kepala Teknisi">
        <div class="space-y-6">


            <!-- ════════════ STATS SUMMARY CARDS ════════════ -->
            <div v-if="can(['pgj_card_summary', 'pgj_card_stats'])" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
                <!-- 1. Antrean Aktif -->
                <div 
                    @click="setStatusFilter('active')"
                    class="bg-white p-4 rounded-2xl border transition-all duration-200 cursor-pointer hover:shadow-md hover:-translate-y-0.5 relative overflow-hidden group"
                    :class="currentStatus === 'active' ? 'border-amber-400 ring-2 ring-amber-400/30 shadow-md shadow-amber-500/10' : 'border-gray-100 hover:border-amber-200'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Antrean Aktif</span>
                        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-gray-900 mt-2 tracking-tight">{{ counts?.active || 0 }}</div>
                    <p class="text-[10px] text-amber-600 font-semibold mt-0.5">Menunggu & terjadwal</p>
                </div>

                <!-- 2. Menunggu Jadwal -->
                <div 
                    @click="setStatusFilter('waiting')"
                    class="bg-white p-4 rounded-2xl border transition-all duration-200 cursor-pointer hover:shadow-md hover:-translate-y-0.5 relative overflow-hidden group"
                    :class="currentStatus === 'waiting' ? 'border-rose-400 ring-2 ring-rose-400/30 shadow-md shadow-rose-500/10' : 'border-gray-100 hover:border-rose-200'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Menunggu Jadwal</span>
                        <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-gray-900 mt-2 tracking-tight">{{ counts?.waiting || 0 }}</div>
                    <p class="text-[10px] text-rose-600 font-semibold mt-0.5">Eskalasi Ka. Teknisi</p>
                </div>

                <!-- 3. Terjadwal Lapangan -->
                <div 
                    @click="setStatusFilter('scheduled')"
                    class="bg-white p-4 rounded-2xl border transition-all duration-200 cursor-pointer hover:shadow-md hover:-translate-y-0.5 relative overflow-hidden group"
                    :class="currentStatus === 'scheduled' ? 'border-indigo-400 ring-2 ring-indigo-400/30 shadow-md shadow-indigo-500/10' : 'border-gray-100 hover:border-indigo-200'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Terjadwal Tim</span>
                        <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-gray-900 mt-2 tracking-tight">{{ counts?.scheduled || 0 }}</div>
                    <p class="text-[10px] text-indigo-600 font-semibold mt-0.5">Sedang ditangani tim</p>
                </div>

                <!-- 4. Selesai Dikerjakan -->
                <div 
                    @click="setStatusFilter('completed')"
                    class="bg-white p-4 rounded-2xl border transition-all duration-200 cursor-pointer hover:shadow-md hover:-translate-y-0.5 relative overflow-hidden group"
                    :class="currentStatus === 'completed' ? 'border-emerald-400 ring-2 ring-emerald-400/30 shadow-md shadow-emerald-500/10' : 'border-gray-100 hover:border-emerald-200'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Selesai Pulih</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-gray-900 mt-2 tracking-tight">{{ counts?.completed || 0 }}</div>
                    <p class="text-[10px] text-emerald-600 font-semibold mt-0.5">Restorasi berhasil</p>
                </div>

                <!-- 5. Semua Kasus -->
                <div 
                    @click="setStatusFilter('all')"
                    class="bg-white p-4 rounded-2xl border transition-all duration-200 cursor-pointer hover:shadow-md hover:-translate-y-0.5 relative overflow-hidden group"
                    :class="currentStatus === 'all' ? 'border-slate-700 ring-2 ring-slate-700/30 shadow-md shadow-slate-900/10' : 'border-gray-100 hover:border-slate-300'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Semua Kasus</span>
                        <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-gray-900 mt-2 tracking-tight">{{ counts?.all || 0 }}</div>
                    <p class="text-[10px] text-slate-500 font-semibold mt-0.5">Total rekapitulasi</p>
                </div>
            </div>

            <!-- ════════════ SMART FILTER TOOLBAR ════════════ -->
            <div v-if="can('pgj_filter')" class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 space-y-3">
                <div class="flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-3">
                    <!-- Left: Search & Filter Controls -->
                    <div class="flex flex-wrap items-center gap-2.5 flex-1">
                        <!-- Search Box -->
                        <div class="relative w-full sm:w-64">
                            <input 
                                type="text" 
                                v-model="search" 
                                placeholder="Cari No. PGJ, ODC, ODP, lokasi..." 
                                class="w-full pl-9 pr-8 py-2 rounded-xl border border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-xs transition-all bg-gray-50/60 font-medium text-slate-800 placeholder-slate-400"
                                @keyup.enter="performSearch"
                            >
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <button v-if="search" @click="search = ''; performSearch()" class="absolute right-2.5 top-2 text-gray-400 hover:text-gray-600 font-bold text-sm">
                                &times;
                            </button>
                        </div>

                        <!-- Status Filter Dropdown -->
                        <select v-model="currentStatus" @change="performSearch" class="px-3 py-2 rounded-xl border border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-xs bg-gray-50/60 font-semibold text-slate-700 cursor-pointer">
                            <option value="active">⚡ Antrean Aktif (Menunggu & Terjadwal)</option>
                            <option value="waiting">⏳ Menunggu Jadwal</option>
                            <option value="scheduled">🔧 Terjadwal & Lapangan</option>
                            <option value="completed">✅ Selesai Dikerjakan</option>
                            <option value="all">📋 Semua Kasus</option>
                        </select>

                        <!-- Segmen Filter Dropdown -->
                        <select v-model="filterSegment" @change="performSearch" class="px-3 py-2 rounded-xl border border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-xs bg-gray-50/60 font-semibold text-slate-700 cursor-pointer">
                            <option value="">Semua Segmen Jaringan</option>
                            <option value="backbone">⚡ Backbone / Sumber Internet</option>
                            <option value="odc">Kabel Feeder ODC</option>
                            <option value="odp">Distribusi ODP</option>
                            <option value="distribution">Distribusi Tiang / Span</option>
                        </select>

                        <!-- Priority Filter Dropdown -->
                        <select v-model="filterPriority" @change="performSearch" class="px-3 py-2 rounded-xl border border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-xs bg-gray-50/60 font-semibold text-slate-700 cursor-pointer">
                            <option value="">Semua Prioritas</option>
                            <option value="critical">🚨 Kritis (Critical)</option>
                            <option value="high">⚠️ Tinggi (High)</option>
                            <option value="medium">Sedang (Medium)</option>
                            <option value="low">Rendah (Low)</option>
                        </select>

                        <!-- Area Filter Dropdown -->
                        <select v-model="filterArea" @change="performSearch" class="px-3 py-2 rounded-xl border border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-xs bg-gray-50/60 font-semibold text-slate-700 cursor-pointer">
                            <option value="">Semua Wilayah / Area</option>
                            <option v-for="a in areas" :key="a.id" :value="a.id">📍 {{ a.name }}</option>
                        </select>

                        <!-- Periode Filter Dropdown -->
                        <select v-model="filterPeriod" @change="performSearch" class="px-3 py-2 rounded-xl border border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-xs bg-gray-50/60 font-semibold text-slate-700 cursor-pointer">
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
                            class="px-3 py-2 rounded-xl text-xs font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 transition-colors flex items-center gap-1.5"
                            title="Reset Semua Filter"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Reset Filter</span>
                        </button>
                    </div>

                    <!-- Right Info / Help -->
                    <div class="text-xs text-gray-500 font-medium shrink-0 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span>Disposisi jadwal tim teknisi & alokasi material FO</span>
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
                            <input type="date" v-model="startDate" @change="performSearch" class="px-2.5 py-1.5 border border-gray-200 rounded-lg text-xs bg-white font-medium focus:border-amber-500 focus:ring-1 focus:ring-amber-200">
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-slate-500 font-medium">Sampai:</span>
                            <input type="date" v-model="endDate" @change="performSearch" class="px-2.5 py-1.5 border border-gray-200 rounded-lg text-xs bg-white font-medium focus:border-amber-500 focus:ring-1 focus:ring-amber-200">
                        </div>
                    </div>
                </transition>
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
                                <th class="p-4">No. Tiket & Segmen</th>
                                <th class="p-4">Alasan Eskalasi & Kerusakan</th>
                                <th class="p-4">Status & Jadwal Tim</th>
                                <th class="p-4">Tim & Material Disiapkan</th>
                                <th class="p-4 text-center">Aksi Ka. Teknisi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-amber-50/20 transition-colors group">
                                <!-- 1. No Tiket & Segmen -->
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-amber-600 text-xs">{{ ticket.ticket_number }}</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border" :class="ticket.priority === 'critical' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-gray-50 text-gray-700 border-gray-200'">
                                            {{ ticket.priority }}
                                        </span>
                                    </div>
                                    <div class="font-bold text-gray-900 mt-1">
                                        {{ ticket.location_name }}
                                    </div>
                                    <!-- Titik Koordinat Insiden (Bisa > 1) & Foto Status -->
                                    <div v-if="ticket.incident_coordinates && ticket.incident_coordinates.length > 0" class="mt-1 flex flex-wrap gap-1">
                                        <a 
                                            v-for="(coord, cIdx) in ticket.incident_coordinates" 
                                            :key="cIdx"
                                            :href="`https://www.google.com/maps?q=${coord.latitude},${coord.longitude}`" 
                                            target="_blank"
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 transition-colors"
                                            :title="`${coord.name || 'Titik Insiden'}: ${coord.latitude}, ${coord.longitude}`"
                                        >
                                            <span>📍</span>
                                            <span class="max-w-[130px] truncate">{{ coord.name || `Titik #${cIdx + 1}` }}</span>
                                        </a>
                                    </div>
                                    <div v-else-if="ticket.latitude && ticket.longitude" class="mt-1">
                                        <a 
                                            :href="`https://www.google.com/maps?q=${ticket.latitude},${ticket.longitude}`" 
                                            target="_blank"
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 transition-colors"
                                        >
                                            <span>📍 Buka Maps GPS</span>
                                        </a>
                                    </div>
                                    <div class="text-xs text-gray-600 mt-0.5 flex flex-wrap gap-1.5">
                                        <span class="font-extrabold text-[10px] px-2 py-0.5 rounded uppercase border" :class="getSegmentBadgeClass(ticket.segment_type)">
                                            {{ getSegmentLabel(ticket.segment_type) }}
                                        </span>
                                        <template v-if="ticket.segment_type === 'backbone'">
                                            <span v-if="ticket.olt" class="text-purple-700 font-bold bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200">OLT: {{ ticket.olt.name }}</span>
                                            <span v-if="ticket.provider_name" class="text-indigo-700 font-bold bg-indigo-50 px-1.5 py-0.5 rounded border border-indigo-200">ISP: {{ ticket.provider_name }}</span>
                                            <span v-if="ticket.backbone_source" class="text-gray-700 font-medium bg-gray-50 px-1.5 py-0.5 rounded border border-gray-200">{{ ticket.backbone_source }}</span>
                                        </template>
                                        <template v-else>
                                            <span v-if="ticket.odc" class="text-rose-700 font-semibold">ODC: {{ ticket.odc.name }}</span>
                                            <span v-if="ticket.odp" class="text-amber-700 font-semibold">ODP: {{ ticket.odp.name }}</span>
                                        </template>
                                    </div>
                                    <div class="mt-1 text-xs font-bold" :class="ticket.segment_type === 'backbone' ? 'text-red-700' : 'text-red-600'">
                                        🚨 {{ ticket.segment_type === 'backbone' ? 'Downtime Massal: ' : '' }}{{ ticket.affected_customers_count }} Pelanggan Terdampak
                                    </div>
                                </td>

                                <!-- 2. Alasan Eskalasi & Kerusakan -->
                                <td class="p-4">
                                    <div class="text-xs font-semibold text-gray-800">
                                        Kategori: {{ ticket.category }}
                                    </div>
                                    <p class="text-xs text-red-600 font-medium mt-1">
                                        "{{ ticket.reported_issue }}"
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
                                        <span class="text-gray-500 font-normal">({{ ticket.scheduled_time || 'Segera' }})</span>
                                    </div>
                                    <div v-if="ticket.status === 'completed' && ticket.resolved_at" class="mt-1 text-[11px] text-emerald-700 font-semibold flex items-center gap-1">
                                        <span>✓ Selesai:</span>
                                        <span>{{ formatDate(ticket.resolved_at) }}</span>
                                    </div>
                                </td>

                                <!-- 4. Tim & Material -->
                                <td class="p-4">
                                    <!-- Daftar Teknisi -->
                                    <div class="text-xs">
                                        <div class="text-[10px] text-gray-400 font-bold uppercase">Tim Ditugaskan:</div>
                                        <div v-if="ticket.technicians && ticket.technicians.length > 0" class="font-bold text-gray-800 mt-0.5">
                                            {{ ticket.technicians.map(t => t.name).join(', ') }}
                                        </div>
                                        <div v-else class="text-gray-400 italic text-xs mt-0.5">Belum ditentukan</div>
                                    </div>

                                    <!-- Material Disiapkan -->
                                    <div class="mt-2 text-xs">
                                        <div class="text-[10px] text-gray-400 font-bold uppercase">Material FO Disiapkan:</div>
                                        <div v-if="ticket.materials && ticket.materials.length > 0" class="space-y-1 mt-1">
                                            <div v-for="m in ticket.materials" :key="m.id" class="text-[11px] bg-gray-50 px-2 py-0.5 rounded border border-gray-100 flex justify-between items-center gap-1">
                                                <div class="flex items-center gap-1.5 truncate">
                                                    <span class="font-medium text-gray-700 truncate">{{ m.material?.name }}</span>
                                                    <span v-if="m.trx_number" class="text-[9px] font-mono font-bold text-amber-700 bg-amber-100 px-1 py-0.5 rounded border border-amber-200">PO: {{ m.trx_number }}</span>
                                                </div>
                                                <span class="font-bold text-gray-900 shrink-0">{{ parseFloat(m.quantity_prepared) }} {{ m.unit || m.material?.unit }}</span>
                                            </div>
                                        </div>
                                        <div v-else class="text-gray-400 italic text-xs mt-0.5">Belum ada material disiapkan</div>
                                    </div>
                                </td>

                                <!-- 5. Aksi Ka. Teknisi -->
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button 
                                            v-if="can('pgj_btn_assign')"
                                            @click="openScheduleModal(ticket)" 
                                            class="px-3.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl shadow-md shadow-amber-500/20 transition-all flex items-center gap-1.5"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                            <span>{{ ticket.status === 'scheduled' ? 'Ubah Jadwal' : 'Atur Jadwal & Tim' }}</span>
                                        </button>

                                        <a :href="`/pgj/${ticket.id}/print`" target="_blank" title="Cetak SPK Restorasi Jalur" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        </a>

                                        <!-- Tombol Batalkan & Hapus Tiket PGJ -->
                                        <button 
                                            v-if="can('pgj_btn_delete') || $page.props.auth?.user?.roles?.includes('admin')"
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
                                    <span v-if="currentStatus === 'completed'">Tidak ada riwayat kasus gangguan jalur yang sudah selesai diperbaiki pada filter ini.</span>
                                    <span v-else-if="currentStatus === 'waiting'">Tidak ada tiket gangguan jalur yang membutuhkan penjadwalan saat ini.</span>
                                    <span v-else-if="currentStatus === 'scheduled'">Tidak ada tiket gangguan jalur yang sedang terjadwal/ditangani tim saat ini.</span>
                                    <span v-else>Tidak ada tiket gangguan jalur pada tab ini saat ini.</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="tickets.links && tickets.links.length > 3" class="p-4 border-t border-gray-100 flex items-center justify-between">
                    <div class="text-xs text-gray-500">
                        Menampilkan {{ tickets.from || 0 }} - {{ tickets.to || 0 }} dari {{ tickets.total || 0 }} kasus jalur
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

        <!-- ════════════ MODAL ATUR JADWAL & MATERIAL JALUR ════════════ -->
        <div v-if="showScheduleModal" class="fixed inset-0 z-50 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-gray-100 overflow-hidden animate-fade-in-up">
                <div class="px-6 py-5 bg-gradient-to-r from-amber-500 to-orange-600 text-white flex justify-between items-center">
                    <div>
                        <h3 class="font-black text-lg">Jadwal Restorasi & Material Jalur PGJ #{{ selectedTicket?.ticket_number }}</h3>
                        <p class="text-xs text-amber-100 mt-0.5">Tugaskan tim teknisi, jadwalkan jam pengerjaan, dan bawa material FO gudang</p>
                    </div>
                    <button @click="showScheduleModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <form @submit.prevent="submitSchedule" class="p-6 space-y-4 max-h-[85vh] overflow-y-auto">
                    <!-- Ringkasan Gangguan & Dampak -->
                    <div class="p-3.5 rounded-2xl border text-xs space-y-1.5" :class="selectedTicket?.segment_type === 'backbone' ? 'bg-gradient-to-r from-purple-50 to-rose-50 border-purple-200 text-purple-950' : 'bg-amber-50/70 border-amber-100 text-amber-900'">
                        <div class="font-bold flex justify-between">
                            <span>Titik: {{ selectedTicket?.location_name }}</span>
                            <span class="uppercase font-extrabold" :class="selectedTicket?.segment_type === 'backbone' ? 'text-purple-700' : 'text-amber-800'">
                                Segmen: {{ selectedTicket?.segment_type === 'backbone' ? '⚡ Backbone / Sumber Internet' : selectedTicket?.segment_type }}
                            </span>
                        </div>
                        <div v-if="selectedTicket?.segment_type === 'backbone'" class="text-xs space-y-0.5 pt-1 border-t border-purple-200/60 font-semibold">
                            <div v-if="selectedTicket?.olt" class="text-purple-900">Perangkat OLT / POP: <strong>{{ selectedTicket.olt.name }}</strong> (IP: {{ selectedTicket.olt.ip_address || '-' }})</div>
                            <div v-if="selectedTicket?.provider_name" class="text-indigo-900">Upstream Provider: <strong>{{ selectedTicket.provider_name }}</strong> <span v-if="selectedTicket?.circuit_id">(CID: {{ selectedTicket.circuit_id }})</span></div>
                            <div v-if="selectedTicket?.backbone_source" class="text-gray-700">Tipe Jalur: {{ selectedTicket.backbone_source }}</div>
                        </div>
                        <div class="text-red-700 font-black text-sm">
                            {{ selectedTicket?.segment_type === 'backbone' ? '🚨 GANGGUAN MASSAL: ' : 'Dampak: ' }}{{ selectedTicket?.affected_customers_count }} Pelanggan Terputus
                        </div>
                        <div class="text-red-700 font-medium">Kerusakan: "{{ selectedTicket?.reported_issue }}"</div>
                        <div class="text-amber-800 font-semibold">Alasan Eskalasi: "{{ selectedTicket?.escalation_reason }}"</div>

                        <!-- Titik Koordinat Insiden Lapangan (Bisa > 1) -->
                        <div v-if="selectedTicket?.incident_coordinates && selectedTicket.incident_coordinates.length > 0" class="pt-2 border-t border-amber-200/60 space-y-1.5">
                            <div class="text-[10px] font-bold uppercase text-amber-900 flex items-center justify-between">
                                <span>📍 Titik Koordinat Insiden Lapangan ({{ selectedTicket.incident_coordinates.length }} Titik):</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                <a 
                                    v-for="(coord, cIdx) in selectedTicket.incident_coordinates" 
                                    :key="cIdx"
                                    :href="`https://www.google.com/maps?q=${coord.latitude},${coord.longitude}`" 
                                    target="_blank"
                                    class="p-2 rounded-xl bg-white border border-amber-200/80 hover:border-blue-400 hover:bg-blue-50/50 transition-all flex items-center justify-between gap-2 shadow-xs group"
                                >
                                    <div class="min-w-0">
                                        <div class="font-bold text-[11px] text-gray-800 truncate group-hover:text-blue-700">#{{ cIdx + 1 }} {{ coord.name || 'Titik Insiden' }}</div>
                                        <div class="font-mono text-[10px] text-gray-500">{{ coord.latitude }}, {{ coord.longitude }}</div>
                                        <div v-if="coord.notes" class="text-[10px] text-gray-600 italic truncate">{{ coord.notes }}</div>
                                    </div>
                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 group-hover:bg-blue-600 group-hover:text-white rounded-lg text-[10px] font-bold shrink-0 transition-colors">
                                        🗺️ Maps
                                    </span>
                                </a>
                            </div>
                        </div>
                        <div v-else-if="selectedTicket?.latitude && selectedTicket?.longitude" class="pt-2 border-t border-amber-200/60 flex items-center justify-between">
                            <span class="text-[11px] text-gray-600 font-mono">📍 GPS: {{ selectedTicket.latitude }}, {{ selectedTicket.longitude }}</span>
                            <a 
                                :href="`https://www.google.com/maps?q=${selectedTicket.latitude},${selectedTicket.longitude}`" 
                                target="_blank"
                                class="px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-lg text-[11px] font-bold"
                            >
                                🗺️ Buka di Google Maps
                            </a>
                        </div>
                    </div>

                    <!-- Tanggal & Jam Restorasi -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tanggal Restorasi Lapangan <span class="text-red-500">*</span></label>
                            <input 
                                type="date" 
                                v-model="scheduleForm.scheduled_date" 
                                class="w-full rounded-xl border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-sm" 
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Estimasi Jam Pengerjaan</label>
                            <input 
                                type="text" 
                                v-model="scheduleForm.scheduled_time" 
                                placeholder="Contoh: 13:00 - 17:00 WIB / Segera"
                                class="w-full rounded-xl border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-sm" 
                            />
                        </div>
                    </div>

                    <!-- Pilih Tim Teknisi Lapangan -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Pilih Tim Teknisi Lapangan (Splicer / Helper) <span class="text-red-500">*</span></label>
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
                    </div>

                    <!-- Instruksi Kerja Teknis Ka. Teknisi -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-gray-700 uppercase">Instruksi Teknis Kerja dari Kepala Teknisi</label>
                            <button 
                                v-if="selectedTicket?.segment_type === 'backbone'" 
                                type="button" 
                                @click="applyLeadBackboneTemplate" 
                                class="text-[11px] font-bold text-purple-700 hover:text-purple-900 bg-purple-100/70 hover:bg-purple-200/70 px-2.5 py-0.5 rounded-lg border border-purple-200 transition-colors"
                            >
                                ⚡ Rekomendasi Instruksi Backbone
                            </button>
                        </div>
                        <textarea 
                            v-model="scheduleForm.lead_notes" 
                            rows="2" 
                            placeholder="Contoh: Bawa Fusion Splicer, tangga 6m, closure dome 24 core. Cek tiang depan Alfamart, sambung kembali core 1-8 yang putus."
                            class="w-full rounded-xl border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-sm"
                        ></textarea>
                    </div>

                    <!-- Penyiapan Material Jalur FO dari Inventory Gudang -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase">Material dari Inventory Gudang</label>
                                <span class="text-[11px] text-gray-500">Pilih material kabel & aksesoris FO dari persediaan Inventory Gudang yang tersedia</span>
                            </div>
                            <button type="button" @click="addMaterialRow" class="text-xs font-bold text-amber-600 hover:text-amber-700 bg-amber-50 hover:bg-amber-100 px-2.5 py-1.5 rounded-lg border border-amber-200 flex items-center gap-1 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>+ Tambah Material Gudang</span>
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
                                            <!-- Fallback jika record sebelumnya tidak memiliki key baru -->
                                            <option v-if="!item.selected_key && item.material_id" :value="''">
                                                📦 {{ item.name || 'Material Sebelumnya' }} (Data Lama)
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
                            <p class="text-[11px] text-gray-400 italic">* Stok diambil langsung dari persediaan Inventory Gudang dan otomatis dialokasikan untuk penugasan ini. Sisa kabel/material dapat dikembalikan ke Inventory Gudang saat laporan teknisi selesai.</p>
                        </div>
                        <div v-else class="p-3 rounded-xl bg-amber-50/40 border border-dashed border-amber-200 text-center text-xs text-amber-700">
                            Belum ada material yang ditambahkan dari Inventory Gudang. Klik <b>+ Tambah Material Gudang</b> jika tim membawa material.
                        </div>
                    </div>

                    <div class="pt-3 border-t flex justify-end gap-2">
                        <button type="button" @click="showScheduleModal = false" class="px-4 py-2 border rounded-xl text-gray-600 text-sm font-semibold hover:bg-gray-50">Batal</button>
                        <button 
                            type="submit" 
                            :disabled="scheduleForm.processing || scheduleForm.technician_ids.length === 0" 
                            class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-bold shadow-md shadow-amber-500/20 disabled:opacity-50"
                        >
                            {{ scheduleForm.processing ? 'Menyimpan...' : 'Simpan Jadwal & Berangkatkan Tim' }}
                        </button>
                    </div>
                </form>
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
    filters: Object,
    counts: {
        type: Object,
        default: () => ({ active: 0, waiting: 0, scheduled: 0, completed: 0, all: 0 }),
    },
});

const search = ref(props.filters?.search || '');
const filterArea = ref(props.filters?.area_id || '');
const currentStatus = ref(props.filters?.status || 'active');
const filterSegment = ref(props.filters?.segment_type || '');
const filterPriority = ref(props.filters?.priority || '');
const filterPeriod = ref(props.filters?.period || '');
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');

const hasActiveFilters = computed(() => {
    return !!(
        search.value ||
        (currentStatus.value && currentStatus.value !== 'active') ||
        filterArea.value ||
        filterSegment.value ||
        filterPriority.value ||
        filterPeriod.value ||
        startDate.value ||
        endDate.value
    );
});

const resetFilters = () => {
    search.value = '';
    currentStatus.value = 'active';
    filterArea.value = '';
    filterSegment.value = '';
    filterPriority.value = '';
    filterPeriod.value = '';
    startDate.value = '';
    endDate.value = '';
    performSearch();
};

const showScheduleModal = ref(false);
const selectedTicket = ref(null);

const customerAreaId = computed(() => {
    return selectedTicket.value?.area_id || null;
});

const filteredMaterialTransactions = computed(() => {
    const allTrx = props.materialTransactions || [];
    const areaId = customerAreaId.value;
    if (!areaId) return allTrx;
    const matched = allTrx.filter(trx => !trx.area_id || trx.area_id == areaId);
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
                    let areaStock = 0;
                    if (item.material.stocks && areaId) {
                        const stockObj = item.material.stocks.find(s => s.area_id == areaId);
                        if (stockObj) {
                            areaStock = parseFloat(stockObj.stock) || 0;
                        }
                    }

                    if (areaStock <= 0 && item.material.stock) {
                        areaStock = parseFloat(item.material.stock) || 0;
                    }

                    const availableQty = areaStock > 0 ? areaStock : (parseFloat(item.quantity) || 0);

                    if (availableQty > 0) {
                        handledMaterialIds.add(item.material_id);
                        items.push({
                            unique_key: 'ord-' + item.id,
                            id: item.id,
                            material_id: item.material_id,
                            trx_number: trx.transaction_number,
                            trx_date: trx.date,
                            purpose: trx.purpose,
                            area_name: trx.area_model?.name || trx.area || 'Order Toko Area',
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
    materials: [],
});

const setStatusFilter = (st) => {
    currentStatus.value = st;
    performSearch();
};

const performSearch = () => {
    router.get('/pgj/jadwal', {
        search: search.value,
        status: currentStatus.value,
        area_id: filterArea.value,
        segment_type: filterSegment.value,
        priority: filterPriority.value,
        period: filterPeriod.value,
        start_date: startDate.value,
        end_date: endDate.value,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const openScheduleModal = (ticket) => {
    selectedTicket.value = ticket;
    scheduleForm.reset();

    scheduleForm.scheduled_date = ticket.scheduled_date || new Date().toISOString().split('T')[0];
    scheduleForm.scheduled_time = ticket.scheduled_time || 'Segera';
    scheduleForm.lead_notes = ticket.lead_notes || '';
    scheduleForm.technician_ids = ticket.technicians ? ticket.technicians.map(t => t.id) : [];

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
                name: m.material?.name || 'Material FO',
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

    scheduleForm.materials.forEach(m => {
        m.quantity_prepared = m.quantity;
    });

    scheduleForm.post(`/pgj/${selectedTicket.value.id}/schedule`, {
        preserveScroll: true,
        onSuccess: () => {
            showScheduleModal.value = false;
            selectedTicket.value = null;
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
            const msg = Object.values(errors).flat().join('\n');
            alert('Gagal menyimpan jadwal jalur:\n' + msg);
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

const applyLeadBackboneTemplate = () => {
    scheduleForm.lead_notes = 'PRIORITAS RESTORASI JALUR BACKBONE: Bawa Fusion Splicer utama, OTDR 1310/1550nm, closure dome, dan span kabel backbone cadangan. Lakukan kalibrasi loss OTDR dari arah OLT, potong bagian kabel rusak, dan sambung seluruh core aktif terlebih dahulu untuk mempercepat pemulihan massal.';
};

const getSegmentBadgeClass = (segment) => {
    switch (segment) {
        case 'backbone': return 'bg-gradient-to-r from-purple-100 to-rose-100 text-purple-900 border-purple-300 font-black shadow-xs';
        case 'odc': return 'bg-rose-100 text-rose-800 border-rose-200';
        case 'odp': return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'distribution': return 'bg-cyan-100 text-cyan-800 border-cyan-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

const getSegmentLabel = (segment) => {
    switch (segment) {
        case 'backbone': return '⚡ Backbone / Sumber Internet';
        case 'odc': return 'ODC';
        case 'odp': return 'ODP';
        case 'distribution': return 'Distribusi';
        default: return segment;
    }
};

const deleteTicket = (ticket) => {
    if (!ticket) return;
    if (confirm(`Apakah Anda yakin ingin membatalkan/menghapus tiket PGJ #${ticket.ticket_number} (${ticket.location_name || ticket.category})? Seluruh material yang telah dialokasikan/disiapkan akan otomatis dikembalikan ke stok gudang.`)) {
        router.delete(`/pgj/${ticket.id}`);
    }
};
</script>
