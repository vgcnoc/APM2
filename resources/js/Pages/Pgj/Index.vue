<template>
    <AppLayout title="Penanganan Gangguan Jalur (PGJ)" subtitle="Monitoring & Penanganan Gangguan Infrastruktur Backbone, ODC, ODP, & Jalur Distribusi">
        <div class="space-y-6">


            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <div @click="filterByStatus('all')" class="bg-white p-4 rounded-2xl border transition-all cursor-pointer hover:shadow-md" :class="activeStatus === 'all' ? 'border-rose-500 ring-2 ring-rose-100 shadow-sm' : 'border-gray-100'">
                    <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Total Kasus Jalur</div>
                    <div class="text-2xl font-black text-gray-900 mt-1">{{ counts.all || 0 }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">Seluruh gangguan fisik jalur</div>
                </div>

                <div @click="filterByStatus('admin_handling')" class="bg-white p-4 rounded-2xl border transition-all cursor-pointer hover:shadow-md" :class="activeStatus === 'admin_handling' ? 'border-rose-500 ring-2 ring-rose-100 shadow-sm' : 'border-gray-100'">
                    <div class="text-[11px] font-bold text-rose-600 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                        Ditangani Admin
                    </div>
                    <div class="text-2xl font-black text-rose-700 mt-1">{{ counts.admin_handling || 0 }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">Identifikasi & NOC remote</div>
                </div>

                <div @click="filterByStatus('escalated_to_lead')" class="bg-white p-4 rounded-2xl border transition-all cursor-pointer hover:shadow-md" :class="activeStatus === 'escalated_to_lead' ? 'border-amber-500 ring-2 ring-amber-100 shadow-sm' : 'border-gray-100'">
                    <div class="text-[11px] font-bold text-amber-600 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        Eskalasi Ka. Teknisi
                    </div>
                    <div class="text-2xl font-black text-amber-700 mt-1">{{ counts.escalated_to_lead || 0 }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">Perlu tim & material FO</div>
                </div>

                <div @click="filterByStatus('scheduled')" class="bg-white p-4 rounded-2xl border transition-all cursor-pointer hover:shadow-md" :class="activeStatus === 'scheduled' ? 'border-indigo-500 ring-2 ring-indigo-100 shadow-sm' : 'border-gray-100'">
                    <div class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        Dijadwalkan
                    </div>
                    <div class="text-2xl font-black text-indigo-700 mt-1">{{ counts.scheduled || 0 }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">Tim lapangan bertugas</div>
                </div>

                <div @click="filterByStatus('completed')" class="bg-white p-4 rounded-2xl border transition-all cursor-pointer hover:shadow-md col-span-2 sm:col-span-1" :class="activeStatus === 'completed' ? 'border-emerald-500 ring-2 ring-emerald-100 shadow-sm' : 'border-gray-100'">
                    <div class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Restorasi Selesai
                    </div>
                    <div class="text-2xl font-black text-emerald-700 mt-1">{{ counts.completed || 0 }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">Jalur kembali normal</div>
                </div>
            </div>

            <!-- Toolbar & Filter -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1 flex-wrap">
                    <div class="relative flex-1 sm:w-64">
                        <input 
                            type="text" 
                            v-model="search" 
                            placeholder="Cari No. PGJ, ODC, ODP, lokasi, core..." 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 text-sm transition-all"
                            @keyup.enter="performSearch"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <select v-model="filterSegment" @change="performSearch" class="px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 text-sm bg-gray-50/50">
                        <option value="">Semua Segmen Jalur</option>
                        <option value="backbone">Jalur Utama / Backbone</option>
                        <option value="odc">Perangkat & Jalur ODC</option>
                        <option value="odp">Perangkat & Jalur ODP</option>
                        <option value="distribution">Jalur Distribusi</option>
                        <option value="other">Infrastruktur Lainnya</option>
                    </select>

                    <select v-model="filterPriority" @change="performSearch" class="px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 text-sm bg-gray-50/50">
                        <option value="">Semua Prioritas</option>
                        <option value="critical">Kritis (Critical)</option>
                        <option value="high">Tinggi (High)</option>
                        <option value="medium">Sedang (Medium)</option>
                        <option value="low">Rendah (Low)</option>
                    </select>

                    <select v-model="filterArea" @change="performSearch" class="px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 text-sm bg-gray-50/50">
                        <option value="">Semua Area</option>
                        <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>
                </div>

                <button @click="openCreateModal" class="bg-gradient-to-r from-rose-600 to-red-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-md shadow-rose-500/20 hover:shadow-lg hover:shadow-rose-500/30 hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Catat Gangguan Jalur</span>
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
                                <th class="p-4">No. Tiket & Segmen</th>
                                <th class="p-4">Titik Infrastruktur & Dampak</th>
                                <th class="p-4">Kerusakan & Indikasi OTDR</th>
                                <th class="p-4">Status & Alur Penanganan</th>
                                <th class="p-4 text-center">Aksi Admin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-rose-50/20 transition-colors group">
                                <!-- 1. No Tiket & Segmen -->
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-rose-600 text-xs">{{ ticket.ticket_number }}</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border" :class="getPriorityBadgeClass(ticket.priority)">
                                            {{ ticket.priority }}
                                        </span>
                                    </div>
                                    <div class="mt-1.5 flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase border" :class="getSegmentBadgeClass(ticket.segment_type)">
                                            {{ getSegmentLabel(ticket.segment_type) }}
                                        </span>
                                    </div>
                                    <div class="text-[10px] text-gray-400 mt-1">
                                        Sumber: <span class="text-gray-600 font-medium">{{ ticket.source_report }}</span>
                                    </div>
                                    <div class="text-[10px] text-gray-400">
                                        {{ formatDate(ticket.created_at) }}
                                    </div>
                                </td>

                                <!-- 2. Titik Infrastruktur & Dampak Pelanggan -->
                                <td class="p-4">
                                    <div class="font-bold text-gray-900 flex items-center gap-1.5">
                                        <span>{{ ticket.location_name || '-' }}</span>
                                    </div>
                                    <div class="text-xs text-gray-600 mt-0.5 flex flex-wrap gap-2">
                                        <span v-if="ticket.odc" class="font-semibold text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-100">
                                            ODC: {{ ticket.odc.name }}
                                        </span>
                                        <span v-if="ticket.odp" class="font-semibold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-100">
                                            ODP: {{ ticket.odp.name }}
                                        </span>
                                        <span class="text-gray-500">Area: {{ ticket.area?.name || '-' }}</span>
                                    </div>
                                    
                                    <!-- Badge Estimasi Dampak Pelanggan -->
                                    <div class="mt-2 inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-50 border border-red-200 text-xs font-bold text-red-700">
                                        <svg class="w-3.5 h-3.5 text-red-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <span>Dampak: {{ ticket.affected_customers_count }} Pelanggan</span>
                                    </div>
                                </td>

                                <!-- 3. Kerusakan & Indikasi OTDR -->
                                <td class="p-4">
                                    <div class="font-semibold text-xs text-gray-900">
                                        {{ ticket.category }}
                                    </div>
                                    <p class="text-xs text-red-600 font-medium mt-1 line-clamp-2" :title="ticket.reported_issue">
                                        "{{ ticket.reported_issue }}"
                                    </p>
                                    <div v-if="ticket.otdr_distance" class="mt-1.5 text-[11px] font-mono text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100 inline-block">
                                        📍 Jarak OTDR: {{ ticket.otdr_distance }}
                                    </div>
                                    <div v-if="ticket.admin_notes" class="mt-1 text-[11px] text-gray-600 bg-gray-50 p-1.5 rounded border border-gray-100">
                                        <span class="font-bold text-gray-700">Tindakan NOC:</span> {{ ticket.admin_notes }}
                                    </div>
                                </td>

                                <!-- 4. Status Alur -->
                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider" :class="getStatusBadgeClass(ticket.status)">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(ticket.status)"></span>
                                        {{ getStatusLabel(ticket.status) }}
                                    </span>

                                    <!-- Info Penugasan Teknisi -->
                                    <div v-if="ticket.technicians && ticket.technicians.length > 0" class="mt-2 text-xs text-gray-600">
                                        <div class="text-[10px] text-gray-400 font-bold uppercase">Tim Lapangan:</div>
                                        <div class="font-semibold text-gray-800">{{ ticket.technicians.map(t => t.name).join(', ') }}</div>
                                    </div>

                                    <div v-if="ticket.scheduled_date" class="text-[11px] text-gray-500 mt-1">
                                        📅 {{ formatDate(ticket.scheduled_date) }} ({{ ticket.scheduled_time || 'Segera' }})
                                    </div>

                                    <div v-if="ticket.status === 'completed'" class="mt-2 text-[11px] text-emerald-700 bg-emerald-50 px-2 py-1 rounded border border-emerald-100">
                                        ✓ Pulih: {{ ticket.resolved_at ? formatDate(ticket.resolved_at) : '-' }}
                                    </div>
                                </td>

                                <!-- 5. Aksi Admin -->
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                        <!-- Tombol Detail -->
                                        <button @click="openDetailModal(ticket)" title="Lihat Detail Gangguan Jalur" class="p-2 text-gray-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>

                                        <!-- Aksi jika masih status admin_handling -->
                                        <template v-if="ticket.status === 'admin_handling'">
                                            <!-- Selesaikan Langsung (Jarak Jauh / False Alarm) -->
                                            <button @click="openResolveAdminModal(ticket)" title="Selesaikan Jarak Jauh (NOC)" class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs rounded-lg border border-emerald-200 transition-colors flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                <span>Selesaikan</span>
                                            </button>

                                            <!-- Eskalasi ke Kepala Teknisi -->
                                            <button @click="openEscalateModal(ticket)" title="Eskalasikan ke Kepala Teknisi Lapangan" class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 font-bold text-xs rounded-lg border border-amber-200 transition-colors flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                                <span>Eskalasi Ka. Teknisi</span>
                                            </button>
                                        </template>

                                        <!-- Cetak SPK & Berita Acara Jalur -->
                                        <a :href="`/pgj/${ticket.id}/print`" target="_blank" title="Cetak SPK Restorasi Jalur" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        </a>

                                        <!-- Hapus Tiket -->
                                        <button @click="deleteTicket(ticket)" title="Hapus Tiket" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="tickets.data.length === 0">
                                <td colspan="5" class="p-8 text-center text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Tidak ada tiket gangguan jalur yang ditemukan.
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
                                class="px-3 py-1 text-xs rounded-lg transition-colors border"
                                :class="link.active ? 'bg-rose-600 text-white border-rose-600 font-bold' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-200'"
                            />
                            <span v-else v-html="link.label" class="px-3 py-1 text-xs rounded-lg text-gray-300 border border-transparent"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════ MODAL 1: CATAT GANGGUAN JALUR BARU (ADMIN) ════════════ -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-gray-100 overflow-hidden animate-fade-in-up">
                <div class="px-6 py-5 bg-gradient-to-r from-rose-600 to-red-600 text-white flex justify-between items-center">
                    <div>
                        <h3 class="font-black text-lg">Penerimaan & Temuan Gangguan Jalur (PGJ)</h3>
                        <p class="text-xs text-rose-100 mt-0.5">Identifikasi kerusakan kabel fiber optik, perangkat ODC/ODP, & backbone</p>
                    </div>
                    <button @click="showCreateModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <form @submit.prevent="submitCreate" class="p-6 space-y-4 max-h-[85vh] overflow-y-auto">
                    <!-- Segmen Jalur & Area -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Segmen Jalur Jaringan <span class="text-red-500">*</span></label>
                            <select v-model="createForm.segment_type" @change="onSegmentChange" class="w-full rounded-xl border-gray-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 text-sm" required>
                                <option value="backbone">Jalur Utama / Backbone (Kabel Feeder OLT)</option>
                                <option value="odc">Perangkat & Jalur ODC</option>
                                <option value="odp">Perangkat & Jalur ODP</option>
                                <option value="distribution">Jalur Distribusi (ODC ke ODP)</option>
                                <option value="other">Infrastruktur Tiang / Jalur Lainnya</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Area / Wilayah <span class="text-red-500">*</span></label>
                            <select v-model="createForm.area_id" @change="onAreaChange" class="w-full rounded-xl border-gray-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 text-sm" required>
                                <option value="">-- Pilih Area --</option>
                                <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Pemilihan ODC & ODP -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">ODC Terkait</label>
                            <select v-model="createForm.odc_id" @change="onOdcChange" class="w-full rounded-xl border-gray-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 text-sm">
                                <option value="">-- Pilih ODC (Jika Ada) --</option>
                                <option v-for="o in filteredOdcs" :key="o.id" :value="o.id">
                                    {{ o.name }} ({{ o.location || 'Lokasi ODC' }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">ODP Terkait</label>
                            <select v-model="createForm.odp_id" @change="onOdpChange" class="w-full rounded-xl border-gray-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 text-sm">
                                <option value="">-- Pilih ODP (Jika Ada) --</option>
                                <option v-for="p in filteredOdps" :key="p.id" :value="p.id">
                                    {{ p.name }} ({{ p.address || 'Alamat ODP' }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Banner Estimasi Dampak Otomatis -->
                    <div class="p-3.5 rounded-2xl bg-rose-50/80 border border-rose-200 flex items-center justify-between text-xs text-rose-900">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🚨</span>
                            <div>
                                <span class="font-bold">Estimasi Dampak Pelanggan Otomatis:</span>
                                <div class="text-[11px] text-rose-700 mt-0.5">Sistem akan menghitung total perangkat ONT di bawah jalur ini</div>
                            </div>
                        </div>
                        <span class="text-base font-black px-3 py-1 rounded-xl bg-white border border-rose-300 text-rose-700">
                            {{ estimatedImpact }} Pelanggan
                        </span>
                    </div>

                    <!-- Titik Lokasi & Jarak Putus OTDR -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Titik Lokasi / Alamat Kerusakan <span class="text-red-500">*</span></label>
                            <input 
                                type="text" 
                                v-model="createForm.location_name" 
                                placeholder="Contoh: Jl. Raya Bantargebang Km 7, Depan Tiang No. 12"
                                class="w-full rounded-xl border-gray-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 text-sm" 
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Estimasi Jarak OTDR / Titik Putus</label>
                            <input 
                                type="text" 
                                v-model="createForm.otdr_distance" 
                                placeholder="Contoh: 1.45 km dari OLT / Span tiang 8-9"
                                class="w-full rounded-xl border-gray-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 text-sm" 
                            />
                        </div>
                    </div>

                    <!-- Kategori, Sumber Laporan & Prioritas -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Kategori Kerusakan <span class="text-red-500">*</span></label>
                            <select v-model="createForm.category" class="w-full rounded-xl border-gray-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 text-sm" required>
                                <option value="Kabel FO Putus Total (Cut Cable)">Kabel FO Putus Total (Cut Cable)</option>
                                <option value="Kabel Tertekuk / Bending Core">Kabel Tertekuk / Bending Core</option>
                                <option value="Splitter ODC/ODP Rusak">Splitter ODC/ODP Rusak</option>
                                <option value="Tiang Roboh / Tertabrak">Tiang Roboh / Tertabrak</option>
                                <option value="Kabel Terbakar / Sambaran Petir">Kabel Terbakar / Sambaran Petir</option>
                                <option value="Splicing Core Loss / Degradasi Core">Splicing Core Loss / Degradasi Core</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Sumber Temuan <span class="text-red-500">*</span></label>
                            <select v-model="createForm.source_report" class="w-full rounded-xl border-gray-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 text-sm" required>
                                <option value="Monitoring NMS">Monitoring NMS (Alarm OLT/LOS Masal)</option>
                                <option value="Patroli Teknisi">Patroli Tim Teknisi Lapangan</option>
                                <option value="Laporan Warga / Pihak Luar">Laporan Warga / Pihak Luar</option>
                                <option value="Aduan Masal Pelanggan">Aduan Masal Pelanggan</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tingkat Prioritas <span class="text-red-500">*</span></label>
                            <select v-model="createForm.priority" class="w-full rounded-xl border-gray-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 text-sm" required>
                                <option value="critical">Kritis (Critical - Jalur Lumpuh)</option>
                                <option value="high">Tinggi (High - Redaman Drop)</option>
                                <option value="medium">Sedang (Medium)</option>
                                <option value="low">Rendah (Low)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Uraian Indikasi Masalah -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Indikasi Masalah / Uraian Kerusakan <span class="text-red-500">*</span></label>
                        <textarea 
                            v-model="createForm.reported_issue" 
                            rows="2" 
                            placeholder="Contoh: Terdeteksi 32 ONT offline serentak pada PON port 2. Diduga kabel feeder 24 core tersangkut truk muatan tinggi di perempatan jalan."
                            class="w-full rounded-xl border-gray-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 text-sm"
                            required
                        ></textarea>
                    </div>

                    <!-- Tindakan Penanganan Awal NOC / Admin -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Penanganan Awal oleh NOC / Admin</label>
                        <textarea 
                            v-model="createForm.admin_notes" 
                            rows="2" 
                            placeholder="Contoh: Sudah dicek via NMS OTDR tracer, terindikasi loss tak hingga di 1.4 km. Telah dipersiapkan pengalihan traffic backup jika tersedia."
                            class="w-full rounded-xl border-gray-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 text-sm"
                        ></textarea>
                    </div>

                    <div class="pt-3 border-t flex justify-end gap-2">
                        <button type="button" @click="showCreateModal = false" class="px-4 py-2 border rounded-xl text-gray-600 text-sm font-semibold hover:bg-gray-50">Batal</button>
                        <button type="submit" :disabled="createForm.processing" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-bold shadow-md shadow-rose-500/20 disabled:opacity-50">
                            {{ createForm.processing ? 'Menyimpan...' : 'Simpan & Buka Tiket Jalur' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ════════════ MODAL 2: SELESAIKAN LANGSUNG OLEH ADMIN ════════════ -->
        <div v-if="showResolveAdminModal" class="fixed inset-0 z-50 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-lg w-full shadow-2xl border border-gray-100 overflow-hidden animate-fade-in-up">
                <div class="px-6 py-5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white flex justify-between items-center">
                    <div>
                        <h3 class="font-black text-lg">Selesaikan Gangguan Jalur (NOC / Remote)</h3>
                        <p class="text-xs text-emerald-100 mt-0.5">Kasus normalisasi remote atau alarm palsu tanpa perlu tim lapangan</p>
                    </div>
                    <button @click="showResolveAdminModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <form @submit.prevent="submitResolveAdmin" class="p-6 space-y-4">
                    <div class="p-3 rounded-xl bg-gray-50 border border-gray-100 text-xs text-gray-600">
                        <div>No. Tiket: <strong class="text-gray-900">{{ selectedTicket?.ticket_number }}</strong></div>
                        <div>Segmen: <strong class="text-gray-900 uppercase">{{ selectedTicket?.segment_type }}</strong></div>
                        <div>Lokasi: <strong class="text-gray-900">{{ selectedTicket?.location_name }}</strong></div>
                        <div class="mt-1 text-red-600">Kendala: "{{ selectedTicket?.reported_issue }}"</div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Catatan Solusi / Normalisasi NOC <span class="text-red-500">*</span></label>
                        <textarea 
                            v-model="resolveForm.admin_notes" 
                            rows="3" 
                            placeholder="Contoh: False alarm karena listrik PLN padam di gardu setempat, setelah genset aktif port PON kembali up dan seluruh ONT online normal."
                            class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm"
                            required
                        ></textarea>
                    </div>

                    <div class="pt-3 border-t flex justify-end gap-2">
                        <button type="button" @click="showResolveAdminModal = false" class="px-4 py-2 border rounded-xl text-gray-600 text-sm font-semibold hover:bg-gray-50">Batal</button>
                        <button type="submit" :disabled="resolveForm.processing" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold shadow-md shadow-emerald-500/20 disabled:opacity-50">
                            {{ resolveForm.processing ? 'Menyimpan...' : 'Selesaikan Tiket Jalur' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ════════════ MODAL 3: ESKALASI KE KEPALA TEKNISI ════════════ -->
        <div v-if="showEscalateModal" class="fixed inset-0 z-50 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-lg w-full shadow-2xl border border-gray-100 overflow-hidden animate-fade-in-up">
                <div class="px-6 py-5 bg-gradient-to-r from-amber-500 to-orange-600 text-white flex justify-between items-center">
                    <div>
                        <h3 class="font-black text-lg">Eskalasi ke Kepala Teknisi Jalur</h3>
                        <p class="text-xs text-amber-100 mt-0.5">Tugaskan tim teknisi lapangan untuk penyambungan fisik & penyiapan material</p>
                    </div>
                    <button @click="showEscalateModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <form @submit.prevent="submitEscalate" class="p-6 space-y-4">
                    <div class="p-3 rounded-xl bg-gray-50 border border-gray-100 text-xs text-gray-600 space-y-0.5">
                        <div>No. Tiket: <strong class="text-gray-900">{{ selectedTicket?.ticket_number }}</strong></div>
                        <div>Segmen: <strong class="text-gray-900 uppercase">{{ selectedTicket?.segment_type }}</strong></div>
                        <div>Titik: <strong class="text-gray-900">{{ selectedTicket?.location_name }}</strong></div>
                        <div class="text-red-700 font-bold">Dampak: {{ selectedTicket?.affected_customers_count }} Pelanggan Terputus</div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Alasan Eskalasi Lapangan <span class="text-red-500">*</span></label>
                        <textarea 
                            v-model="escalateForm.escalation_reason" 
                            rows="2" 
                            placeholder="Contoh: Kabel FO 24 core terputus fisik, perlu tim splicer, penarikan kabel span baru 60m, dan closure dome baru."
                            class="w-full rounded-xl border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-sm"
                            required
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Catatan Tambahan Admin</label>
                        <textarea 
                            v-model="escalateForm.admin_notes" 
                            rows="2" 
                            placeholder="Instruksi tambahan koordinasi lapangan..."
                            class="w-full rounded-xl border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-sm"
                        ></textarea>
                    </div>

                    <div class="pt-3 border-t flex justify-end gap-2">
                        <button type="button" @click="showEscalateModal = false" class="px-4 py-2 border rounded-xl text-gray-600 text-sm font-semibold hover:bg-gray-50">Batal</button>
                        <button type="submit" :disabled="escalateForm.processing" class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-bold shadow-md shadow-amber-500/20 disabled:opacity-50">
                            {{ escalateForm.processing ? 'Memproses...' : 'Kirim ke Kepala Teknisi' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ════════════ MODAL 4: DETAIL LENGKAP TIKET PGJ ════════════ -->
        <div v-if="showDetailModal" class="fixed inset-0 z-50 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-gray-100 overflow-hidden animate-fade-in-up">
                <div class="px-6 py-5 bg-gray-900 text-white flex justify-between items-center">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-black text-lg">Kasus Jalur {{ detailTicket?.ticket_number }}</h3>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border" :class="getPriorityBadgeClass(detailTicket?.priority)">
                                {{ detailTicket?.priority }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">Detail Restorasi & Dampak Infrastruktur Jaringan</p>
                    </div>
                    <button @click="showDetailModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <div class="p-6 space-y-4 max-h-[80vh] overflow-y-auto text-sm">
                    <!-- Status & Dampak -->
                    <div class="flex items-center justify-between p-3.5 rounded-2xl bg-gray-50 border border-gray-100">
                        <div>
                            <span class="text-xs font-bold text-gray-500 uppercase">Status Terkini:</span>
                            <div class="mt-1">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider" :class="getStatusBadgeClass(detailTicket?.status)">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(detailTicket?.status)"></span>
                                    {{ getStatusLabel(detailTicket?.status) }}
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-bold text-red-600 uppercase">Dampak Pelanggan:</span>
                            <div class="text-xl font-black text-red-700 mt-0.5">{{ detailTicket?.affected_customers_count }} Pelanggan</div>
                        </div>
                    </div>

                    <!-- Titik Lokasi & Segmen -->
                    <div class="p-3.5 rounded-2xl bg-rose-50/50 border border-rose-100 space-y-2">
                        <div class="font-bold text-gray-900 flex justify-between">
                            <span>Segmen: <strong class="uppercase text-rose-700">{{ detailTicket?.segment_type }}</strong></span>
                            <span>Area: {{ detailTicket?.area?.name || '-' }}</span>
                        </div>
                        <div class="text-xs text-gray-700">Titik / Alamat: <strong>{{ detailTicket?.location_name || '-' }}</strong></div>
                        <div v-if="detailTicket?.odc" class="text-xs text-gray-700">ODC Terkait: <strong>{{ detailTicket.odc.name }}</strong> ({{ detailTicket.odc.location || '-' }})</div>
                        <div v-if="detailTicket?.odp" class="text-xs text-gray-700">ODP Terkait: <strong>{{ detailTicket.odp.name }}</strong> ({{ detailTicket.odp.address || '-' }})</div>
                        <div v-if="detailTicket?.otdr_distance" class="text-xs font-mono text-indigo-700">Jarak OTDR: {{ detailTicket.otdr_distance }}</div>
                    </div>

                    <!-- Kerusakan & Penanganan -->
                    <div class="space-y-2">
                        <div class="p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <div class="text-[10px] font-bold uppercase text-gray-500">Kategori & Uraian Masalah:</div>
                            <div class="font-bold text-gray-900 mt-0.5">{{ detailTicket?.category }}</div>
                            <div class="text-xs text-red-600 mt-1">"{{ detailTicket?.reported_issue }}"</div>
                        </div>

                        <div v-if="detailTicket?.admin_notes" class="p-3 rounded-xl bg-blue-50 border border-blue-100">
                            <div class="text-[10px] font-bold uppercase text-blue-700">Penanganan Awal Admin / NOC:</div>
                            <div class="text-xs text-blue-900 mt-0.5">{{ detailTicket?.admin_notes }}</div>
                        </div>

                        <div v-if="detailTicket?.escalation_reason" class="p-3 rounded-xl bg-amber-50 border border-amber-100">
                            <div class="text-[10px] font-bold uppercase text-amber-700">Alasan Eskalasi Ka. Teknisi:</div>
                            <div class="text-xs text-amber-900 mt-0.5">{{ detailTicket?.escalation_reason }}</div>
                        </div>
                    </div>

                    <!-- Jadwal & Tim Teknisi -->
                    <div v-if="detailTicket?.status === 'scheduled' || detailTicket?.status === 'completed'" class="p-3.5 rounded-2xl bg-indigo-50/50 border border-indigo-100 space-y-1">
                        <div class="text-[10px] font-bold uppercase text-indigo-800">Jadwal & Tim Teknisi:</div>
                        <div class="text-xs text-gray-700">Tanggal: <strong>{{ formatDate(detailTicket?.scheduled_date) }}</strong> ({{ detailTicket?.scheduled_time || 'Segera' }})</div>
                        <div class="text-xs text-gray-700">Tim Pelaksana: <strong>{{ detailTicket?.technicians?.map(t => t.name).join(', ') || '-' }}</strong></div>
                        <div v-if="detailTicket?.lead_notes" class="text-xs text-indigo-900 mt-1 italic">
                            Instruksi Ka. Teknisi: "{{ detailTicket?.lead_notes }}"
                        </div>
                    </div>

                    <!-- Hasil Restorasi Selesai -->
                    <div v-if="detailTicket?.status === 'completed'" class="p-3.5 rounded-2xl bg-emerald-50/50 border border-emerald-100 space-y-1.5">
                        <div class="text-[10px] font-bold uppercase text-emerald-800">Hasil Restorasi Lapangan:</div>
                        <div class="text-xs text-gray-800">Tindakan: <strong>{{ detailTicket?.action_taken || '-' }}</strong></div>
                        <div v-if="detailTicket?.core_details" class="text-xs text-gray-800">Detail Core: <strong>{{ detailTicket.core_details }}</strong></div>
                        <div class="text-xs text-gray-800">
                            Optical Loss: Sebelum <strong>{{ detailTicket?.optical_loss_before || '-' }}</strong> | Sesudah <strong class="text-emerald-700">{{ detailTicket?.optical_loss_after || '-' }}</strong>
                        </div>
                        <div v-if="detailTicket?.resolution_notes" class="text-xs text-gray-700">Catatan Akhir: {{ detailTicket?.resolution_notes }}</div>
                    </div>

                    <div class="pt-3 border-t flex justify-end gap-2">
                        <a :href="`/pgj/${detailTicket?.id}/print`" target="_blank" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            <span>Cetak Berita Acara & SPK</span>
                        </a>
                        <button type="button" @click="showDetailModal = false" class="px-4 py-2 border rounded-xl text-gray-600 text-xs font-semibold hover:bg-gray-50">Tutup</button>
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
    odcs: Array,
    odps: Array,
    technicians: Array,
    counts: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const activeStatus = ref(props.filters?.status || 'all');
const filterSegment = ref(props.filters?.segment_type || '');
const filterPriority = ref(props.filters?.priority || '');
const filterArea = ref(props.filters?.area_id || '');

const showCreateModal = ref(false);
const showResolveAdminModal = ref(false);
const showEscalateModal = ref(false);
const showDetailModal = ref(false);

const selectedTicket = ref(null);
const detailTicket = ref(null);

const createForm = useForm({
    segment_type: 'odc',
    area_id: '',
    odc_id: '',
    odp_id: '',
    location_name: '',
    latitude: '',
    longitude: '',
    otdr_distance: '',
    source_report: 'Monitoring NMS',
    category: 'Kabel FO Putus Total (Cut Cable)',
    priority: 'critical',
    reported_issue: '',
    admin_notes: '',
});

const resolveForm = useForm({
    admin_notes: '',
});

const escalateForm = useForm({
    escalation_reason: '',
    admin_notes: '',
});

// Filter ODC berdasarkan Area
const filteredOdcs = computed(() => {
    if (!createForm.area_id) return props.odcs || [];
    return (props.odcs || []).filter(o => o.area_id == createForm.area_id);
});

// Filter ODP berdasarkan ODC & Area
const filteredOdps = computed(() => {
    let list = props.odps || [];
    if (createForm.odc_id) {
        list = list.filter(p => p.odc_id == createForm.odc_id);
    } else if (createForm.area_id) {
        list = list.filter(p => p.area_id == createForm.area_id);
    }
    return list;
});

// Hitung estimasi dampak pelanggan di form secara live
const estimatedImpact = computed(() => {
    if (createForm.odp_id) {
        const odp = (props.odps || []).find(p => p.id == createForm.odp_id);
        return odp?.used_ports || 8; // Estimasi port terpakai di ODP
    }
    if (createForm.odc_id) {
        const odc = (props.odcs || []).find(o => o.id == createForm.odc_id);
        // Rata-rata 1 ODC = jumlah ODP * 8
        const odpCount = odc?.odps_count || 4;
        return odpCount * 8;
    }
    if (createForm.segment_type === 'backbone') {
        return 64; // Jalur utama estimasi masal
    }
    return 16;
});

const onSegmentChange = () => {
    if (createForm.segment_type === 'backbone') {
        createForm.priority = 'critical';
    }
};

const onAreaChange = () => {
    createForm.odc_id = '';
    createForm.odp_id = '';
};

const onOdcChange = () => {
    createForm.odp_id = '';
    const odc = (props.odcs || []).find(o => o.id == createForm.odc_id);
    if (odc) {
        if (!createForm.location_name) {
            createForm.location_name = odc.location || `Lokasi ${odc.name}`;
        }
        createForm.latitude = odc.latitude || '';
        createForm.longitude = odc.longitude || '';
    }
};

const onOdpChange = () => {
    const odp = (props.odps || []).find(p => p.id == createForm.odp_id);
    if (odp) {
        if (!createForm.odc_id && odp.odc_id) {
            createForm.odc_id = odp.odc_id;
        }
        createForm.location_name = odp.address || `Lokasi ${odp.name}`;
        createForm.latitude = odp.latitude || '';
        createForm.longitude = odp.longitude || '';
    }
};

const performSearch = () => {
    router.get('/pgj', {
        search: search.value,
        status: activeStatus.value === 'all' ? '' : activeStatus.value,
        segment_type: filterSegment.value,
        priority: filterPriority.value,
        area_id: filterArea.value,
    }, { preserveState: true, replace: true });
};

const filterByStatus = (status) => {
    activeStatus.value = status;
    performSearch();
};

const openCreateModal = () => {
    createForm.reset();
    createForm.segment_type = 'odc';
    createForm.source_report = 'Monitoring NMS';
    createForm.category = 'Kabel FO Putus Total (Cut Cable)';
    createForm.priority = 'critical';
    showCreateModal.value = true;
};

const submitCreate = () => {
    createForm.post('/pgj', {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        }
    });
};

const openResolveAdminModal = (ticket) => {
    selectedTicket.value = ticket;
    resolveForm.reset();
    showResolveAdminModal.value = true;
};

const submitResolveAdmin = () => {
    if (!selectedTicket.value) return;
    resolveForm.post(`/pgj/${selectedTicket.value.id}/resolve-admin`, {
        onSuccess: () => {
            showResolveAdminModal.value = false;
            selectedTicket.value = null;
        }
    });
};

const openEscalateModal = (ticket) => {
    selectedTicket.value = ticket;
    escalateForm.reset();
    showEscalateModal.value = true;
};

const submitEscalate = () => {
    if (!selectedTicket.value) return;
    escalateForm.post(`/pgj/${selectedTicket.value.id}/escalate`, {
        onSuccess: () => {
            showEscalateModal.value = false;
            selectedTicket.value = null;
        }
    });
};

const openDetailModal = (ticket) => {
    detailTicket.value = ticket;
    showDetailModal.value = true;
};

const deleteTicket = (ticket) => {
    if (confirm(`Hapus tiket gangguan jalur #${ticket.ticket_number}?`)) {
        router.delete(`/pgj/${ticket.id}`);
    }
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

const getPriorityBadgeClass = (priority) => {
    switch (priority) {
        case 'critical': return 'bg-red-50 text-red-700 border-red-200';
        case 'high': return 'bg-orange-50 text-orange-700 border-orange-200';
        case 'medium': return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'low': return 'bg-gray-50 text-gray-700 border-gray-200';
        default: return 'bg-gray-50 text-gray-700 border-gray-200';
    }
};

const getSegmentBadgeClass = (segment) => {
    switch (segment) {
        case 'backbone': return 'bg-purple-100 text-purple-800 border-purple-200';
        case 'odc': return 'bg-rose-100 text-rose-800 border-rose-200';
        case 'odp': return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'distribution': return 'bg-cyan-100 text-cyan-800 border-cyan-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

const getSegmentLabel = (segment) => {
    switch (segment) {
        case 'backbone': return 'Backbone / Feeder';
        case 'odc': return 'ODC';
        case 'odp': return 'ODP';
        case 'distribution': return 'Distribusi';
        default: return segment;
    }
};

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'admin_handling': return 'bg-rose-50 text-rose-700 border border-rose-200';
        case 'escalated_to_lead': return 'bg-amber-50 text-amber-700 border border-amber-200';
        case 'scheduled': return 'bg-indigo-50 text-indigo-700 border border-indigo-200';
        case 'completed': return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
        default: return 'bg-gray-50 text-gray-700 border border-gray-200';
    }
};

const getStatusDotClass = (status) => {
    switch (status) {
        case 'admin_handling': return 'bg-rose-500 animate-pulse';
        case 'escalated_to_lead': return 'bg-amber-500';
        case 'scheduled': return 'bg-indigo-500';
        case 'completed': return 'bg-emerald-500';
        default: return 'bg-gray-400';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'admin_handling': return 'Ditangani Admin';
        case 'escalated_to_lead': return 'Eskalasi Ka. Teknisi';
        case 'scheduled': return 'Dijadwalkan Lapangan';
        case 'completed': return 'Restorasi Pulih';
        default: return status;
    }
};
</script>
