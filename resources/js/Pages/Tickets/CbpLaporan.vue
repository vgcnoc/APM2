<template>
    <AppLayout title="Laporan CBP" subtitle="Pelaporan Pencabutan Perangkat oleh Teknisi">
        <div class="space-y-5">
            <!-- Stats Overview Cards (Laporan CBP) -->
            <div v-if="can('cbp_card_stats')" class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                <!-- Card 1: Total Laporan -->
                <div 
                    @click="filterByStatus('all')" 
                    class="bg-white p-4 rounded-2xl border transition-all duration-200 cursor-pointer hover:shadow-md relative overflow-hidden group select-none" 
                    :class="(!filterStatus || filterStatus === 'all') ? 'border-slate-800 ring-2 ring-slate-200/80 shadow-sm bg-gradient-to-br from-white to-slate-50' : 'border-gray-100 hover:border-slate-300'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black text-slate-500 uppercase tracking-wider">Total Laporan</span>
                        <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600 group-hover:scale-110 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 mt-2 tracking-tight">{{ counts?.all || 0 }}</div>
                    <div class="text-[10px] text-slate-400 mt-0.5 truncate">Semua data laporan</div>
                </div>

                <!-- Card 2: Perlu Dilaporkan (Ditugaskan) -->
                <div 
                    @click="filterByStatus('assigned')" 
                    class="bg-white p-4 rounded-2xl border transition-all duration-200 cursor-pointer hover:shadow-md relative overflow-hidden group select-none" 
                    :class="filterStatus === 'assigned' ? 'border-blue-500 ring-2 ring-blue-100 shadow-sm bg-gradient-to-br from-white to-blue-50/30' : 'border-gray-100 hover:border-blue-300'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black text-blue-700 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                            Perlu Dilaporkan
                        </span>
                        <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 group-hover:scale-110 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-blue-600 mt-2 tracking-tight">{{ counts?.assigned || 0 }}</div>
                    <div class="text-[10px] text-blue-600/70 mt-0.5 truncate">Menunggu input hasil pencabutan</div>
                </div>

                <!-- Card 3: Selesai Dilaporkan -->
                <div 
                    @click="filterByStatus('completed')" 
                    class="bg-white p-4 rounded-2xl border transition-all duration-200 cursor-pointer hover:shadow-md relative overflow-hidden group select-none" 
                    :class="filterStatus === 'completed' ? 'border-emerald-500 ring-2 ring-emerald-100 shadow-sm bg-gradient-to-br from-white to-emerald-50/30' : 'border-gray-100 hover:border-emerald-300'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black text-emerald-700 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Selesai Dilaporkan
                        </span>
                        <div class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-emerald-600 mt-2 tracking-tight">{{ counts?.completed || 0 }}</div>
                    <div class="text-[10px] text-emerald-600/70 mt-0.5 truncate">Perangkat berhasil ditarik</div>
                </div>
            </div>

            <!-- Header Actions & Filters -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-3">
                <div v-if="can('cbp_filter')" class="flex flex-wrap items-center gap-2.5 flex-1">
                    <div class="relative flex-1 min-w-[240px] sm:max-w-xs">
                        <input 
                            type="text" 
                            v-model="search" 
                            placeholder="Cari ID CBP, pelanggan, HP..." 
                            class="w-full pl-9 pr-8 py-2 rounded-xl border border-gray-200 focus:border-red-500 focus:ring-2 focus:ring-red-100 text-xs transition-all font-medium"
                            @keyup.enter="performSearch"
                        >
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <button v-if="search" @click="search = ''; performSearch()" class="absolute right-2.5 top-2.5 text-gray-400 hover:text-gray-600">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <select v-model="filterStatus" @change="performSearch" class="px-3 py-2 rounded-xl border border-gray-200 focus:border-red-500 focus:ring-2 focus:ring-red-100 text-xs bg-gray-50/60 font-semibold text-slate-700 cursor-pointer">
                        <option value="">Semua Status Laporan</option>
                        <option value="assigned">🔵 Perlu Dilaporkan (Ditugaskan)</option>
                        <option value="completed">🟢 Selesai Dilaporkan</option>
                    </select>

                    <select v-model="filterArea" @change="performSearch" class="px-3 py-2 rounded-xl border border-gray-200 focus:border-red-500 focus:ring-2 focus:ring-red-100 text-xs bg-gray-50/60 font-semibold text-slate-700 cursor-pointer">
                        <option value="">Semua Wilayah / Area</option>
                        <option v-for="area in areas" :key="area.id" :value="area.id">📍 {{ area.name }}</option>
                    </select>

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
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="mb-4 flex items-center gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 animate-fade-in-up">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
            </div>

            <!-- Data Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <th class="p-4 w-36">Nomor CBP</th>
                                <th class="p-4">Pelanggan</th>
                                <th class="p-4 w-40">Tgl Cabut</th>
                                <th class="p-4">Alasan Cabut</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Teknisi</th>
                                <th class="p-4 w-36 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr v-for="req in requests.data" :key="req.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="p-4 font-mono font-medium text-red-600">{{ req.cbp_number }}</td>
                                <td class="p-4">
                                    <div class="font-bold text-gray-900">{{ req.customer?.name || '-' }}</div>
                                    <div class="text-[11px] text-gray-500 flex items-center gap-1 mt-0.5">
                                        <span class="px-1.5 py-0.5 rounded bg-gray-100 border border-gray-200">{{ req.customer?.customer_code || '-' }}</span>
                                        <span v-if="req.customer?.areaModel?.name || req.customer?.area" class="text-slate-400">• {{ req.customer?.areaModel?.name || req.customer?.area }}</span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div class="flex flex-col gap-0.5 text-xs">
                                        <div v-if="req.completed_at" class="font-bold text-emerald-800 flex items-center gap-1">
                                            <span>✅ {{ formatDisplayDate(req.completed_at) }}</span>
                                        </div>
                                        <div v-else-if="req.scheduled_date" class="font-bold text-slate-800 flex items-center gap-1">
                                            <span>📅 {{ formatDisplayDate(req.scheduled_date) }}</span>
                                        </div>
                                        <div v-else class="text-slate-400 italic text-[11px]">-</div>
                                        <span v-if="req.scheduled_date && req.completed_at" class="text-[10px] text-slate-500">
                                            Rencana: {{ formatDisplayDate(req.scheduled_date) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div class="text-gray-700 max-w-xs truncate" :title="req.reason">{{ req.reason || '-' }}</div>
                                    <div v-if="req.notes" class="text-[10px] text-gray-400 mt-1 border-t border-gray-100 pt-1" :title="req.notes">
                                        Note: <span class="truncate">{{ req.notes }}</span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border"
                                        :class="{
                                            'bg-blue-50 text-blue-600 border-blue-200': req.status === 'assigned',
                                            'bg-emerald-50 text-emerald-600 border-emerald-200': req.status === 'completed',
                                            'bg-gray-50 text-gray-600 border-gray-200': req.status === 'canceled'
                                        }">
                                        <span :class="[
                                            'w-1.5 h-1.5 rounded-full',
                                            req.status === 'assigned' ? 'bg-blue-500' :
                                            req.status === 'completed' ? 'bg-emerald-500' :
                                            'bg-gray-500'
                                        ]"></span>
                                        {{ req.status === 'assigned' ? 'Ditugaskan' : req.status === 'completed' ? 'Selesai' : 'Dibatalkan' }}
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
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Jika Ditugaskan: Tombol Lapor Selesai / Lanjutkan Berjalan -->
                                        <button 
                                            v-if="req.status === 'assigned' && can('cbp_btn_report')" 
                                            @click="openProgressModal(req)" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-xl transition-all shadow-sm"
                                            :class="hasActiveTimer(req.id) ? 'bg-amber-100 text-amber-800 border border-amber-300 hover:bg-amber-200 animate-pulse' : 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200'"
                                            :title="hasActiveTimer(req.id) ? 'Lanjutkan Laporan Berjalan' : 'Selesaikan & Laporkan'"
                                        >
                                            <span v-if="hasActiveTimer(req.id)" class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                                            <svg v-else class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            <span>{{ hasActiveTimer(req.id) ? 'Lanjutkan (' + getActiveDuration(req.id) + ')' : 'Lapor Selesai' }}</span>
                                        </button>

                                        <!-- Jika Selesai: Aksi Berubah Jadi "Laporan Selesai" -->
                                        <button 
                                            v-else-if="req.status === 'completed'" 
                                            @click="openReportDetailModal(req)" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 rounded-xl transition-all shadow-sm hover:scale-105"
                                            title="Lihat Detail Hasil Laporan Selesai"
                                        >
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span>Laporan Selesai</span>
                                        </button>

                                        <!-- History Modal Button -->
                                        <button type="button" @click="openHistoryModal(req)" class="p-1.5 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg transition-colors" title="Riwayat CBP">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!requests.data.length">
                                <td colspan="7" class="p-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </div>
                                        <p class="font-bold text-slate-700">Tidak ada laporan CBP ditemukan.</p>
                                        <p class="text-xs text-slate-400 mt-1 max-w-sm">Coba sesuaikan filter status, wilayah, atau kata kunci pencarian Anda.</p>
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
                <div v-if="requests.links?.length > 3" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/30">
                    <span class="text-sm text-gray-500">Menampilkan {{ requests.from }} - {{ requests.to }} dari {{ requests.total }} data</span>
                    <div class="flex gap-1">
                        <template v-for="link in requests.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" :class="['px-3 py-1.5 text-sm rounded-lg transition-colors', link.active ? 'bg-red-50 text-red-600 font-bold border border-red-200' : 'text-gray-600 hover:bg-gray-100 border border-transparent']" v-html="link.label"></Link>
                            <span v-else class="px-3 py-1.5 text-sm text-gray-400" v-html="link.label"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- MODAL 1: FORM LAPORAN TEKNISI (MODERN, CANGGIH, SEMPURNA) -->
        <!-- ======================================================== -->
        <Teleport to="body">
            <div v-if="showProgressModal" class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity" @click="closeProgressModal"></div>
                <div class="bg-white rounded-3xl shadow-[0_25px_70px_-15px_rgba(0,0,0,0.3)] w-full max-w-lg relative z-10 animate-fade-in-up border border-slate-100 overflow-hidden flex flex-col my-auto max-h-[92vh]">
                    
                    <!-- Decorative background blur -->
                    <div class="absolute -top-24 -right-24 w-48 h-48 bg-emerald-400/20 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute top-1/2 -left-24 w-40 h-40 bg-teal-400/10 rounded-full blur-3xl pointer-events-none"></div>

                    <!-- Modal Header -->
                    <div class="px-6 py-4 flex items-center justify-between bg-white/80 backdrop-blur-xl border-b border-gray-100 z-10 shrink-0">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/25 text-white shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-lg font-black text-slate-800 tracking-tight">Laporan Teknisi</h3>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-slate-100 text-slate-700">{{ selectedCbp?.cbp_number }}</span>
                                </div>
                                <p class="text-xs font-medium text-slate-500">Laporan eksekusi hasil cabut perangkat</p>
                            </div>
                        </div>
                        <button @click="closeProgressModal" class="text-slate-400 hover:text-rose-500 hover:bg-rose-50 p-2 rounded-xl transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Modal Body Form -->
                    <form @submit.prevent="submitProgress" class="flex flex-col overflow-hidden flex-1">
                        <div class="p-5 sm:p-6 space-y-5 overflow-y-auto custom-scrollbar flex-1">
                            
                            <!-- 1. Customer & Location Dossier Card -->
                            <div class="bg-gradient-to-br from-slate-50 to-blue-50/40 border border-slate-200/80 rounded-2xl p-4 shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-sm font-black text-slate-900">{{ selectedCbp?.customer?.name }}</h4>
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-100 text-blue-700">
                                                {{ selectedCbp?.customer?.areaModel?.name || selectedCbp?.customer?.area || 'Area Default' }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-1 font-mono font-medium">
                                            ID: {{ selectedCbp?.customer?.customer_code }}
                                            <span v-if="selectedCbp?.customer?.phone" class="font-sans text-slate-600 font-semibold"> • 📞 {{ selectedCbp?.customer?.phone }}</span>
                                        </p>
                                        <p v-if="selectedCbp?.customer?.address" class="text-xs text-slate-600 mt-1 line-clamp-2 leading-relaxed">
                                            📍 {{ selectedCbp?.customer?.address }}
                                        </p>
                                    </div>
                                    <a 
                                        v-if="selectedCbp?.customer?.phone" 
                                        :href="getWhatsAppUrl(selectedCbp?.customer?.phone)" 
                                        target="_blank" 
                                        class="shrink-0 p-2 rounded-xl bg-emerald-50 text-emerald-600 hover:bg-emerald-100 border border-emerald-200 transition-colors"
                                        title="Chat WhatsApp Pelanggan"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                    </a>
                                </div>
                            </div>

                            <!-- 2. Jam Mulai & Live Stopwatch Ticker (Di Atas) -->
                            <div class="bg-slate-50/80 border-2 border-slate-200/90 rounded-2xl p-4 shadow-sm">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full" :class="activeTimerDuration ? 'bg-emerald-500 animate-ping' : 'bg-slate-400'"></span>
                                        <label class="block text-[11px] font-black text-slate-700 uppercase tracking-wider">
                                            Jam Mulai Pengerjaan
                                        </label>
                                    </div>
                                    <span v-if="activeTimerDuration" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 flex items-center gap-1">
                                        <span>⏱️ Berjalan:</span>
                                        <span class="font-mono font-black">{{ activeTimerDuration }}</span>
                                    </span>
                                </div>

                                <!-- Jika belum klik Mulai -->
                                <div v-if="!progressForm.start_time" class="space-y-2">
                                    <button 
                                        type="button" 
                                        @click="handleStartNow" 
                                        class="w-full py-3 px-4 rounded-xl text-sm font-black text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-md shadow-blue-500/25 flex items-center justify-center gap-2 transition-all hover:scale-[1.01] active:scale-[0.99]"
                                    >
                                        <svg class="w-5 h-5 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>MULAI SEKARANG</span>
                                    </button>
                                    <p class="text-[10px] text-center text-slate-400">
                                        💡 Jam mulai & timer akan tersimpan otomatis dan tetap berjalan meski aplikasi keluar.
                                    </p>
                                </div>

                                <!-- Jika sudah mulai -->
                                <div v-else class="flex items-center justify-between bg-white rounded-xl p-3 border border-slate-200">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <div>
                                            <div class="text-[10px] font-bold text-slate-400 uppercase">Waktu Dimulai</div>
                                            <div class="text-xl font-black text-slate-800 font-mono tracking-tight">{{ progressForm.start_time }} WIB</div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <button 
                                            type="button" 
                                            @click="handleRestartTimer" 
                                            class="text-[11px] font-bold text-slate-400 hover:text-rose-600 transition-colors"
                                            title="Ulangi Jam Mulai"
                                        >
                                            Reset Mulai
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Perangkat Terpasang (Instalasi) & Checklist Cabut -->
                            <div class="bg-amber-50/50 border border-amber-200/80 rounded-2xl p-4 shadow-sm">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                                        </div>
                                        <label class="block text-[11px] font-black text-slate-800 uppercase tracking-wider">Perangkat Terpasang (Instalasi)</label>
                                    </div>
                                    <span class="text-[10px] text-amber-700 font-bold">
                                        {{ progressForm.materials.length }} Item Ditandai Cabut
                                    </span>
                                </div>

                                <div v-if="hardwareItems.length > 0" class="space-y-2.5">
                                    <div 
                                        v-for="item in hardwareItems" 
                                        :key="item.id" 
                                        class="flex flex-col gap-2 bg-white rounded-xl p-3 border transition-all"
                                        :class="hasMaterial(item.id) ? 'border-emerald-300 ring-2 ring-emerald-100 shadow-sm' : 'border-slate-200'"
                                    >
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider" :class="item.type === 'ONT' ? 'bg-purple-100 text-purple-700' : 'bg-slate-100 text-slate-600'">
                                                    {{ item.type }}
                                                </span>
                                                <div class="text-xs font-bold text-slate-800 mt-0.5 break-words">{{ item.name }}</div>
                                            </div>
                                            
                                            <div class="flex items-center gap-2 shrink-0">
                                                <!-- Jika Material Kabel: Tampilkan input meter -->
                                                <template v-if="item.type === 'Material' && item.name.toLowerCase().includes('kabel')">
                                                    <div v-if="hasMaterial(item.id)" class="relative w-20">
                                                        <input 
                                                            type="number" 
                                                            v-model="getMaterial(item.id).quantity" 
                                                            min="1" 
                                                            class="w-full px-2 py-1 text-xs border-2 border-emerald-400 rounded-lg text-center font-bold focus:ring-emerald-500 focus:border-emerald-500" 
                                                            placeholder="Meter"
                                                            title="Panjang kabel yang berhasil ditarik"
                                                        >
                                                        <span class="absolute right-1.5 top-1 text-[10px] text-slate-400 pointer-events-none">m</span>
                                                    </div>
                                                </template>

                                                <!-- Tombol Toggle Cabut / Dicabut -->
                                                <button 
                                                    v-if="!hasMaterial(item.id)" 
                                                    type="button" 
                                                    @click="cabutItem(item)" 
                                                    class="text-xs bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 font-black px-3 py-1.5 rounded-lg transition-all shadow-sm active:scale-95"
                                                >
                                                    Cabut
                                                </button>
                                                <button 
                                                    v-else 
                                                    type="button" 
                                                    @click="uncabutItem(item)" 
                                                    class="text-xs bg-emerald-600 text-white font-black px-3 py-1.5 rounded-lg flex items-center gap-1 shadow-sm h-[32px] hover:bg-emerald-700 transition-all active:scale-95"
                                                    title="Klik untuk membatalkan status dicabut"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                    <span>Dicabut</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-xs text-slate-500 italic p-3 bg-white border border-slate-200 rounded-xl">
                                    Data instalasi tidak ditemukan pada riwayat.
                                </div>
                            </div>

                            <!-- 4. Foto Progress (Bisa Tambah Lebih dari 1 Foto) -->
                            <div class="space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <label class="block text-[11px] font-black text-slate-700 uppercase tracking-wider">
                                        Foto Progress Bukti Pencabutan <span class="text-emerald-600 font-bold">(Bisa > 1 Foto)</span>
                                    </label>
                                    <span v-if="selectedPhotos.length > 0" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">
                                        📸 {{ selectedPhotos.length }} Foto Terpilih
                                    </span>
                                </div>

                                <!-- Hidden Multiple File Input -->
                                <input 
                                    type="file" 
                                    ref="multiPhotoInputRef" 
                                    multiple 
                                    accept="image/*" 
                                    @change="handlePhotoUpload" 
                                    class="hidden"
                                >

                                <!-- Empty State Dropzone -->
                                <div 
                                    v-if="selectedPhotos.length === 0" 
                                    @click="triggerPhotoInput" 
                                    class="border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-2xl p-5 text-center cursor-pointer transition-all bg-slate-50/60 hover:bg-emerald-50/20 group"
                                >
                                    <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </div>
                                    <p class="text-xs font-bold text-slate-700 group-hover:text-emerald-700">
                                        + Ambil Foto / Pilih dari Galeri
                                    </p>
                                    <p class="text-[10px] text-slate-400 mt-1 max-w-xs mx-auto">
                                        Bisa upload lebih dari 1 foto (Foto ONT & SN, Kabel Dropcore yang dicabut, Foto Rumah/ODP).
                                    </p>
                                </div>

                                <!-- Gallery Preview Grid -->
                                <div v-else class="space-y-3">
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                        <div 
                                            v-for="(photo, idx) in selectedPhotos" 
                                            :key="idx" 
                                            class="relative group rounded-xl overflow-hidden border-2 border-slate-200 aspect-video bg-slate-100 shadow-sm"
                                        >
                                            <img :src="photo.preview" class="w-full h-full object-cover" :alt="'Foto ' + (idx + 1)">
                                            
                                            <!-- Overlay Badge & Remove Button -->
                                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent flex items-end justify-between p-2">
                                                <span class="text-[10px] font-bold text-white bg-black/40 px-1.5 py-0.5 rounded">
                                                    Foto {{ idx + 1 }}
                                                </span>
                                                <button 
                                                    type="button" 
                                                    @click="removePhoto(idx)" 
                                                    class="w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center hover:bg-rose-700 shadow transition-transform hover:scale-110"
                                                    title="Hapus foto ini"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Add More Card -->
                                        <button 
                                            type="button" 
                                            @click="triggerPhotoInput" 
                                            class="border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-xl aspect-video flex flex-col items-center justify-center text-slate-500 hover:text-emerald-600 bg-slate-50/60 hover:bg-emerald-50/20 transition-all group"
                                        >
                                            <svg class="w-6 h-6 text-slate-400 group-hover:text-emerald-600 group-hover:scale-110 transition-transform mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            <span class="text-[11px] font-bold">+ Tambah Foto</span>
                                        </button>
                                    </div>
                                </div>
                                <div v-if="progressForm.errors?.photos" class="text-xs text-rose-500 mt-1">{{ progressForm.errors.photos }}</div>
                            </div>

                            <!-- 5. Catatan Teknisi & Quick Chips -->
                            <div>
                                <label class="block text-[11px] font-black text-slate-700 uppercase tracking-wider mb-1.5">
                                    Catatan Teknisi <span class="text-slate-400 lowercase font-normal">(opsional)</span>
                                </label>
                                
                                <!-- Quick Chips -->
                                <div class="flex flex-wrap gap-1.5 mb-2">
                                    <button 
                                        v-for="chip in quickNotes" 
                                        :key="chip" 
                                        type="button" 
                                        @click="appendQuickNote(chip)" 
                                        class="text-[10px] font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1 rounded-lg border border-slate-200/80 transition-colors"
                                    >
                                        {{ chip }}
                                    </button>
                                </div>

                                <textarea 
                                    v-model="progressForm.notes" 
                                    rows="2" 
                                    class="w-full px-3.5 py-2.5 bg-slate-50 border-2 border-slate-200 rounded-2xl text-xs focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all placeholder-slate-400 resize-none custom-scrollbar" 
                                    placeholder="Tuliskan kondisi fisik perangkat, kondisi dropcore, kendala penarikan, atau catatan lainnya..."
                                ></textarea>
                            </div>

                            <!-- 6. Jam Selesai (Pindah ke Bawah, Tepat di Atas Simpan Laporan) -->
                            <div class="bg-gradient-to-br from-slate-50 to-emerald-50/30 border-2 border-emerald-200/80 rounded-2xl p-4 shadow-sm">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full" :class="progressForm.end_time ? 'bg-emerald-500' : 'bg-amber-400 animate-pulse'"></span>
                                        <label class="block text-[11px] font-black text-slate-800 uppercase tracking-wider">
                                            Jam Selesai Eksekusi
                                        </label>
                                    </div>
                                    <span v-if="calculatedDuration" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">
                                        ⏱️ Total: {{ calculatedDuration }}
                                    </span>
                                </div>

                                <!-- Belum Klik Selesai -->
                                <div v-if="!progressForm.end_time">
                                    <button 
                                        type="button" 
                                        @click="handleEndNow" 
                                        :disabled="!progressForm.start_time" 
                                        class="w-full py-3 px-4 rounded-xl text-sm font-black text-white shadow-md transition-all flex items-center justify-center gap-2"
                                        :class="!progressForm.start_time ? 'bg-slate-300 text-slate-500 cursor-not-allowed shadow-none' : 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-emerald-500/25 hover:scale-[1.01] active:scale-[0.99]'"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <span>SELESAI SEKARANG</span>
                                    </button>
                                    <p v-if="!progressForm.start_time" class="text-[10px] text-center text-amber-600 font-bold mt-1.5">
                                        ⚠️ Harap klik "MULAI SEKARANG" di bagian atas terlebih dahulu.
                                    </p>
                                </div>

                                <!-- Sudah Klik Selesai -->
                                <div v-else class="flex items-center justify-between bg-white rounded-xl p-3 border border-emerald-200">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <div>
                                            <div class="text-[10px] font-bold text-slate-400 uppercase">Waktu Selesai</div>
                                            <div class="text-xl font-black text-emerald-700 font-mono tracking-tight">{{ progressForm.end_time }} WIB</div>
                                        </div>
                                    </div>
                                    <button 
                                        type="button" 
                                        @click="progressForm.end_time = ''" 
                                        class="text-[11px] font-bold text-slate-400 hover:text-rose-600 transition-colors"
                                    >
                                        Ubah Selesai
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-3 shrink-0 rounded-b-3xl">
                            <button 
                                type="button" 
                                @click="closeProgressModal" 
                                class="px-5 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-200/60 rounded-xl transition-colors"
                            >
                                Batal
                            </button>
                            
                            <button 
                                type="submit" 
                                :disabled="progressForm.processing || !progressForm.start_time || !progressForm.end_time" 
                                class="relative group overflow-hidden bg-gradient-to-r from-emerald-600 to-teal-600 text-white px-7 py-2.5 rounded-xl text-xs font-black shadow-lg shadow-emerald-500/30 hover:from-emerald-700 hover:to-teal-700 hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:hover:translate-y-0 disabled:cursor-not-allowed"
                            >
                                <span class="relative z-10 flex items-center gap-2">
                                    <svg v-if="progressForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    {{ progressForm.processing ? 'Menyimpan...' : 'Simpan Laporan' }}
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ======================================================== -->
        <!-- MODAL 2: DETAIL HASIL LAPORAN SELESAI                     -->
        <!-- ======================================================== -->
        <Teleport to="body">
            <div v-if="showDetailModal" class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity" @click="closeDetailModal"></div>
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg relative z-10 animate-fade-in-up border border-slate-100 overflow-hidden flex flex-col my-auto max-h-[92vh]">
                    
                    <!-- Header Detail -->
                    <div class="px-6 py-4 flex items-center justify-between bg-emerald-50/70 border-b border-emerald-100 z-10 shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-bold shadow-md shadow-emerald-500/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-black text-slate-800">Detail Laporan Selesai</h3>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-emerald-200 text-emerald-800">SELESAI</span>
                                </div>
                                <p class="text-xs font-mono font-bold text-emerald-700">{{ selectedDetailCbp?.cbp_number }}</p>
                            </div>
                        </div>
                        <button @click="closeDetailModal" class="text-slate-400 hover:text-rose-500 hover:bg-rose-50 p-2 rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Body Detail -->
                    <div class="p-5 sm:p-6 space-y-4 overflow-y-auto custom-scrollbar flex-1">
                        <!-- Pelanggan & Lokasi -->
                        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200/80">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-black text-slate-900">{{ selectedDetailCbp?.customer?.name }}</h4>
                                    <p class="text-xs text-slate-500 font-mono mt-0.5">
                                        {{ selectedDetailCbp?.customer?.customer_code }} • {{ selectedDetailCbp?.customer?.areaModel?.name || selectedDetailCbp?.customer?.area || '-' }}
                                    </p>
                                    <p v-if="selectedDetailCbp?.customer?.address" class="text-xs text-slate-600 mt-1">
                                        📍 {{ selectedDetailCbp?.customer?.address }}
                                    </p>
                                </div>
                                <span v-if="selectedDetailCbp?.customer?.phone" class="text-xs font-bold text-slate-700 bg-white px-2 py-1 rounded-lg border border-slate-200">
                                    📞 {{ selectedDetailCbp?.customer?.phone }}
                                </span>
                            </div>
                        </div>

                        <!-- Waktu Eksekusi -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-blue-50/60 rounded-xl p-3 border border-blue-100">
                                <div class="text-[10px] font-bold text-blue-600 uppercase">Jam Mulai</div>
                                <div class="text-base font-black text-slate-800 font-mono mt-0.5">
                                    {{ selectedDetailCbp?.start_time ? selectedDetailCbp.start_time.substring(0, 5) + ' WIB' : '-' }}
                                </div>
                            </div>
                            <div class="bg-emerald-50/60 rounded-xl p-3 border border-emerald-100">
                                <div class="text-[10px] font-bold text-emerald-600 uppercase">Jam Selesai</div>
                                <div class="text-base font-black text-slate-800 font-mono mt-0.5">
                                    {{ selectedDetailCbp?.end_time ? selectedDetailCbp.end_time.substring(0, 5) + ' WIB' : '-' }}
                                </div>
                            </div>
                        </div>

                        <!-- Teknisi Pelaksana -->
                        <div class="bg-slate-50 rounded-xl p-3 border border-slate-200">
                            <div class="text-[10px] font-bold text-slate-400 uppercase mb-1.5">Teknisi Pelaksana</div>
                            <div v-if="selectedDetailCbp?.technicians?.length" class="flex flex-wrap gap-2">
                                <div v-for="t in selectedDetailCbp.technicians" :key="t.id" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-xs font-bold text-slate-800">
                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                    <span>{{ t.name }}</span>
                                </div>
                            </div>
                            <span v-else class="text-xs text-slate-400 italic">Tidak ada teknisi tercatat</span>
                        </div>

                        <!-- Catatan Teknisi -->
                        <div v-if="selectedDetailCbp?.notes" class="bg-slate-50 rounded-xl p-3 border border-slate-200">
                            <div class="text-[10px] font-bold text-slate-400 uppercase mb-1">Catatan Hasil Pencabutan</div>
                            <p class="text-xs text-slate-700 whitespace-pre-line leading-relaxed">{{ selectedDetailCbp.notes }}</p>
                        </div>

                        <!-- Galeri Foto Bukti -->
                        <div>
                            <div class="text-[11px] font-black text-slate-700 uppercase tracking-wider mb-2 flex items-center justify-between">
                                <span>Foto Bukti Pencabutan</span>
                                <span class="text-[10px] text-slate-400 font-normal">Klik untuk perbesar</span>
                            </div>

                            <div v-if="getDetailPhotos(selectedDetailCbp).length > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                <div 
                                    v-for="(photoUrl, pIdx) in getDetailPhotos(selectedDetailCbp)" 
                                    :key="pIdx" 
                                    @click="openLightbox(photoUrl)" 
                                    class="relative group rounded-xl overflow-hidden border border-slate-200 aspect-video bg-slate-100 cursor-pointer shadow-sm hover:shadow-md transition-all"
                                >
                                    <img :src="'/storage/' + photoUrl" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" :alt="'Foto Bukti ' + (pIdx + 1)">
                                    <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                        <svg class="w-5 h-5 drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-xs text-slate-400 italic p-3 bg-slate-50 rounded-xl border border-slate-200 text-center">
                                Tidak ada foto bukti yang diunggah.
                            </div>
                        </div>
                    </div>

                    <!-- Footer Detail -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between shrink-0 rounded-b-3xl">
                        <button 
                            type="button" 
                            @click="shareReportWhatsApp(selectedDetailCbp)" 
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-100 hover:bg-emerald-200 transition-colors"
                        >
                            <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                            <span>Bagikan Laporan WA</span>
                        </button>
                        <button 
                            type="button" 
                            @click="closeDetailModal" 
                            class="px-5 py-2 rounded-xl text-xs font-bold bg-slate-200 text-slate-700 hover:bg-slate-300 transition-colors"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Lightbox Modal untuk Zoom Foto -->
        <Teleport to="body">
            <div v-if="activeLightboxUrl" class="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm" @click="activeLightboxUrl = null">
                <button @click="activeLightboxUrl = null" class="absolute top-4 right-4 text-white hover:text-rose-400 p-2">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <img :src="'/storage/' + activeLightboxUrl" class="max-w-full max-h-[90vh] rounded-2xl object-contain shadow-2xl" @click.stop>
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
                                <div class="flex items-center justify-center w-10 h-10 rounded-full border-4 border-white bg-indigo-100 text-indigo-600 shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 z-10 relative">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] bg-white p-4 rounded-xl border border-gray-100 shadow-sm relative">
                                    <div class="flex items-center justify-between mb-1">
                                        <div class="font-bold text-gray-900 text-sm">{{ log.action }}</div>
                                        <div class="text-[10px] text-gray-500 font-mono">{{ new Date(log.created_at).toLocaleString('id-ID') }}</div>
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
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermission } from '@/Composables/usePermission';

const { can } = usePermission();

const props = defineProps({
    requests: Object,
    counts: Object,
    materials: Array,
    areas: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const filterStatus = ref(props.filters?.status || '');
const filterArea = ref(props.filters?.area_id || '');

const hasActiveFilters = computed(() => {
    return !!(search.value || filterStatus.value || filterArea.value);
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
    router.get('/cbp/laporan', {
        search: search.value || undefined,
        status: filterStatus.value || undefined,
        area_id: filterArea.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    search.value = '';
    filterStatus.value = '';
    filterArea.value = '';
    router.get('/cbp/laporan', {}, { preserveState: true, preserveScroll: true });
}

function formatDisplayDate(dateStr) {
    if (!dateStr) return '-';
    const cleanStr = String(dateStr).substring(0, 10);
    const parts = cleanStr.split('-');
    if (parts.length !== 3) return cleanStr;
    const y = Number(parts[0]);
    const m = Number(parts[1]);
    const d = Number(parts[2]);
    if (!y || !m || !d) return cleanStr;
    const shortMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${d} ${shortMonths[m - 1]} ${y}`;
}

function getWhatsAppUrl(phone) {
    if (!phone) return '#';
    let clean = phone.replace(/[^0-9]/g, '');
    if (clean.startsWith('0')) clean = '62' + clean.substring(1);
    return `https://wa.me/${clean}`;
}

// ========================================================
// PERSISTENT STOPWATCH & RUNNING DURATION
// ========================================================
const currentTimeTick = ref(Date.now());
let timerInterval = null;

onMounted(() => {
    timerInterval = setInterval(() => {
        currentTimeTick.value = Date.now();
    }, 1000);
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

function hasActiveTimer(cbpId) {
    try {
        const raw = localStorage.getItem(`cbp_timer_${cbpId}`);
        return !!raw;
    } catch (e) {
        return false;
    }
}

function getActiveDuration(cbpId) {
    try {
        const raw = localStorage.getItem(`cbp_timer_${cbpId}`);
        if (!raw) return '00:00';
        const data = JSON.parse(raw);
        if (!data.startTimestamp) return '00:00';
        const diffSec = Math.max(0, Math.floor((currentTimeTick.value - data.startTimestamp) / 1000));
        const m = Math.floor(diffSec / 60);
        const s = diffSec % 60;
        return `${m}m ${s < 10 ? '0' : ''}${s}s`;
    } catch (e) {
        return '00:00';
    }
}

// ========================================================
// PROGRESS MODAL (SUBMISSION & PERSISTENCE)
// ========================================================
const showProgressModal = ref(false);
const selectedCbp = ref(null);
const multiPhotoInputRef = ref(null);
const selectedPhotos = ref([]);

const quickNotes = [
    '✅ Perangkat & Dropcore Lengkap',
    '🔌 Adaptor Ada',
    '📦 Perangkat Masuk Gudang',
    '🏠 Pelanggan Izin Kerja',
    '⚡ Eksekusi Lancar'
];

const progressForm = useForm({
    start_time: '',
    end_time: '',
    notes: '',
    materials: [],
    photos: [],
});

const activeTimerDuration = computed(() => {
    if (!selectedCbp.value) return '';
    try {
        const raw = localStorage.getItem(`cbp_timer_${selectedCbp.value.id}`);
        if (!raw) return '';
        const data = JSON.parse(raw);
        if (!data.startTimestamp) return '';
        const diffSec = Math.max(0, Math.floor((currentTimeTick.value - data.startTimestamp) / 1000));
        const h = Math.floor(diffSec / 3600);
        const m = Math.floor((diffSec % 3600) / 60);
        const s = diffSec % 60;
        if (h > 0) {
            return `${h}:${m < 10 ? '0' : ''}${m}:${s < 10 ? '0' : ''}${s}`;
        }
        return `${m < 10 ? '0' : ''}${m}:${s < 10 ? '0' : ''}${s}`;
    } catch (e) {
        return '';
    }
});

const calculatedDuration = computed(() => {
    if (!progressForm.start_time || !progressForm.end_time) return '';
    try {
        const [h1, m1] = progressForm.start_time.split(':').map(Number);
        const [h2, m2] = progressForm.end_time.split(':').map(Number);
        let diff = (h2 * 60 + m2) - (h1 * 60 + m1);
        if (diff < 0) diff += 24 * 60; // Cross midnight
        return `${diff} Menit`;
    } catch (e) {
        return '';
    }
});

function handleStartNow() {
    const now = new Date();
    const timeString = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    progressForm.start_time = timeString;
    
    if (selectedCbp.value) {
        localStorage.setItem(`cbp_timer_${selectedCbp.value.id}`, JSON.stringify({
            startTimestamp: Date.now(),
            startTime: timeString,
        }));
        saveDraft();
    }
}

function handleRestartTimer() {
    if (confirm('Atur ulang jam mulai sekarang?')) {
        handleStartNow();
    }
}

function handleEndNow() {
    const now = new Date();
    const timeString = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    progressForm.end_time = timeString;
    saveDraft();
}

function appendQuickNote(chip) {
    if (!progressForm.notes) {
        progressForm.notes = chip;
    } else if (!progressForm.notes.includes(chip)) {
        progressForm.notes += '\n' + chip;
    }
    saveDraft();
}

function triggerPhotoInput() {
    if (multiPhotoInputRef.value) {
        multiPhotoInputRef.value.click();
    }
}

function handlePhotoUpload(e) {
    const files = Array.from(e.target.files || []);
    if (!files.length) return;

    files.forEach(file => {
        const previewUrl = URL.createObjectURL(file);
        selectedPhotos.value.push({
            file: file,
            preview: previewUrl,
            name: file.name,
            size: file.size,
        });
    });

    progressForm.photos = selectedPhotos.value.map(p => p.file);

    // Reset input so same file can be re-selected if removed
    if (multiPhotoInputRef.value) {
        multiPhotoInputRef.value.value = '';
    }
    saveDraft();
}

function removePhoto(idx) {
    selectedPhotos.value.splice(idx, 1);
    progressForm.photos = selectedPhotos.value.map(p => p.file);
    saveDraft();
}

// Auto save draft to localStorage
function saveDraft() {
    if (!selectedCbp.value) return;
    try {
        const draft = {
            notes: progressForm.notes,
            materials: progressForm.materials,
            start_time: progressForm.start_time,
            end_time: progressForm.end_time,
        };
        localStorage.setItem(`cbp_draft_${selectedCbp.value.id}`, JSON.stringify(draft));
    } catch (e) {}
}

// Watch inputs to auto-save draft
watch(() => progressForm.notes, () => saveDraft());
watch(() => progressForm.materials, () => saveDraft(), { deep: true });

const hardwareItems = computed(() => {
    if (!selectedCbp.value || !selectedCbp.value.customer) return [];
    
    // Parse from installation report
    const schedule = selectedCbp.value.customer.technician_schedules?.find(s => s.type === 'installation');
    let items = [];
    let idCounter = 1;
    
    if (schedule && schedule.notes) {
        const lines = schedule.notes.split('\n');
        let inNoteSection = false;
        lines.forEach(line => {
            let text = line.trim();
            if (!text) return;
            if (text.startsWith('ONT: ')) {
                inNoteSection = false;
                const onts = text.replace('ONT: ', '').split(', ');
                onts.forEach(ont => {
                    if (ont.trim()) items.push({ id: idCounter++, type: 'ONT', name: ont.trim() });
                });
            } else if (text.startsWith('Material: ') || text.startsWith('Material Terpasang: ')) {
                inNoteSection = false;
                const mats = text.replace(/^Material(\s+Terpasang)?:\s*/i, '').split(', ');
                mats.forEach(mat => {
                    if (mat.trim()) items.push({ id: idCounter++, type: 'Material', name: mat.trim() });
                });
            } else if (text.toLowerCase().startsWith('catatan')) {
                inNoteSection = true;
            } else if (
                inNoteSection ||
                text.startsWith('Material diambil dari Surat Jalan') ||
                text === 'ONT:' ||
                text === 'Material:' ||
                text === 'Jadwal pasang' ||
                text.startsWith('Alokasi Awal:') ||
                text.startsWith('Material Dikembalikan:') ||
                text.startsWith('Retur Gudang:') ||
                text.startsWith('WiFi:') ||
                text.startsWith('[') ||
                text.startsWith('---') ||
                /^\[?\d{2}\/\d{2}\s+\d{2}:\d{2}\]?/.test(text) ||
                text.includes('Pemasangan dimulai') ||
                text.includes('Pemasangan selesai') ||
                text.includes('ONT dipasang') ||
                text.includes('Material dipasang') ||
                text.includes('Port ODP dipilih')
            ) {
                // Skip logs / non-hardware lines
            } else {
                items.push({ id: idCounter++, type: 'Material', name: text });
            }
        });
    }

    // Fallback if installation notes didn't yield items
    if (items.length === 0 && selectedCbp.value.customer.ont) {
        items.push({
            id: idCounter++,
            type: 'ONT',
            name: `ONT ${selectedCbp.value.customer.ont.brand || ''} ${selectedCbp.value.customer.ont.model || ''} (SN: ${selectedCbp.value.customer.ont.serial_number || '-'})`.trim()
        });
        items.push({
            id: idCounter++,
            type: 'Material',
            name: 'Kabel Dropcore / Frecon 1 Core'
        });
    }
    
    return items;
});

function hasMaterial(id) {
    return progressForm.materials.some(m => m._id === id);
}

function getMaterial(id) {
    return progressForm.materials.find(m => m._id === id);
}

function cabutItem(item) {
    let unit = 'pcs';
    if (item.type === 'Material' && item.name.toLowerCase().includes('kabel')) unit = 'm';
    
    let matId = '';
    const nameLower = item.name.toLowerCase();
    
    if (item.type === 'ONT') {
        const ontMat = props.materials.find(m => m.category === 'ont' || m.name.toLowerCase().includes('ont'));
        if (ontMat) matId = ontMat.id;
    } else {
        const matched = props.materials.find(m => nameLower.includes(m.name.toLowerCase()) || m.name.toLowerCase().includes(nameLower));
        if (matched) {
            matId = matched.id;
        } else if (nameLower.includes('kabel')) {
            const kabelMat = props.materials.find(m => m.category === 'kabel' || m.name.toLowerCase().includes('kabel'));
            if (kabelMat) matId = kabelMat.id;
        }
    }
    
    progressForm.materials.push({
        material_id: matId || (props.materials[0]?.id || 1),
        quantity: unit === 'm' ? 100 : 1,
        unit: unit,
        _id: item.id,
        _name: item.name
    });
    saveDraft();
}

function uncabutItem(item) {
    const idx = progressForm.materials.findIndex(m => m._id === item.id);
    if (idx !== -1) {
        progressForm.materials.splice(idx, 1);
        saveDraft();
    }
}

function openProgressModal(req) {
    selectedCbp.value = req;
    selectedPhotos.value = [];
    progressForm.reset();
    progressForm.clearErrors();

    // 1. Restore timer from localStorage
    try {
        const rawTimer = localStorage.getItem(`cbp_timer_${req.id}`);
        if (rawTimer) {
            const parsedTimer = JSON.parse(rawTimer);
            progressForm.start_time = parsedTimer.startTime || '';
        }
    } catch (e) {}

    // 2. Restore draft from localStorage
    try {
        const rawDraft = localStorage.getItem(`cbp_draft_${req.id}`);
        if (rawDraft) {
            const parsedDraft = JSON.parse(rawDraft);
            progressForm.notes = parsedDraft.notes || req.notes || '';
            progressForm.materials = parsedDraft.materials || [];
            if (parsedDraft.start_time) progressForm.start_time = parsedDraft.start_time;
            if (parsedDraft.end_time) progressForm.end_time = parsedDraft.end_time;
        } else {
            progressForm.notes = req.notes || '';
        }
    } catch (e) {
        progressForm.notes = req.notes || '';
    }

    showProgressModal.value = true;
}

function closeProgressModal() {
    showProgressModal.value = false;
    selectedCbp.value = null;
    selectedPhotos.value = [];
    progressForm.reset();
    progressForm.clearErrors();
}

function submitProgress() {
    if (!selectedCbp.value) return;
    const cbpId = selectedCbp.value.id;

    progressForm.post(`/cbp/${cbpId}/status`, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            try {
                localStorage.removeItem(`cbp_timer_${cbpId}`);
                localStorage.removeItem(`cbp_draft_${cbpId}`);
            } catch (e) {}
            closeProgressModal();
        },
    });
}

// ========================================================
// DETAIL MODAL (LAPORAN SELESAI) & LIGHTBOX
// ========================================================
const showDetailModal = ref(false);
const selectedDetailCbp = ref(null);
const activeLightboxUrl = ref(null);

function openReportDetailModal(cbp) {
    selectedDetailCbp.value = cbp;
    showDetailModal.value = true;
}

function closeDetailModal() {
    showDetailModal.value = false;
    selectedDetailCbp.value = null;
    activeLightboxUrl.value = null;
}

function openLightbox(photoUrl) {
    activeLightboxUrl.value = photoUrl;
}

function getDetailPhotos(cbp) {
    if (!cbp) return [];
    if (cbp.all_photos && Array.isArray(cbp.all_photos) && cbp.all_photos.length > 0) {
        return cbp.all_photos;
    }
    if (cbp.photos && Array.isArray(cbp.photos) && cbp.photos.length > 0) {
        return cbp.photos;
    }
    if (cbp.photo) {
        return [cbp.photo];
    }
    return [];
}

function shareReportWhatsApp(cbp) {
    if (!cbp) return;
    const techNames = cbp.technicians?.map(t => t.name).join(', ') || 'Tim Teknisi';
    const text = `*LAPORAN HASIL PENCABUTAN PERANGKAT (CBP)*\n` +
        `----------------------------------------\n` +
        `*No CBP:* ${cbp.cbp_number}\n` +
        `*Pelanggan:* ${cbp.customer?.name || '-'} (${cbp.customer?.customer_code || '-'})\n` +
        `*Area:* ${cbp.customer?.areaModel?.name || cbp.customer?.area || '-'}\n` +
        `*Waktu:* ${cbp.start_time?.substring(0, 5) || '-'} - ${cbp.end_time?.substring(0, 5) || '-'} WIB\n` +
        `*Status:* Selesai Dicabut\n` +
        `*Teknisi:* ${techNames}\n` +
        (cbp.notes ? `*Catatan:* ${cbp.notes}\n` : '') +
        `----------------------------------------\n` +
        `_Laporan resmi sistem APM2_`;

    window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(text)}`, '_blank');
}

// ========================================================
// HISTORY AUDIT LOG MODAL
// ========================================================
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
</script>
