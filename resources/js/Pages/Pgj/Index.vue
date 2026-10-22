<template>
    <AppLayout title="Penanganan Gangguan Jalur (PGJ)" subtitle="Monitoring & Penanganan Gangguan Infrastruktur Backbone, ODC, ODP, & Jalur Distribusi">
        <div class="space-y-6">


            <!-- Stats Overview Cards -->
            <div v-if="can('pgj_card_summary')" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
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
                <div v-if="can('pgj_filter')" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1 flex-wrap">
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

                    <select v-model="filterSegment" @change="performSearch" class="px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 text-sm bg-gray-50/50 font-medium">
                        <option value="">Semua Segmen Jalur</option>
                        <option value="backbone">⚡ Jalur Utama Backbone / Sumber Internet</option>
                        <option value="odc">Perangkat & Jalur ODC</option>
                        <option value="odp">Perangkat & Jalur ODP</option>
                        <option value="distribution">Jalur Distribusi (ODC ke ODP)</option>
                        <option value="other">Infrastruktur Tiang / Lainnya</option>
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

                <button v-if="can('pgj_btn_create')" @click="openCreateModal" class="bg-gradient-to-r from-rose-600 to-red-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-md shadow-rose-500/20 hover:shadow-lg hover:shadow-rose-500/30 hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2 shrink-0">
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
                                <th class="p-4">Kerusakan & Indikasi Masalah</th>
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
                                        <template v-if="ticket.segment_type === 'backbone'">
                                            <span v-if="ticket.olt" class="font-bold text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200">
                                                OLT: {{ ticket.olt.name }}
                                            </span>
                                            <span v-if="ticket.provider_name" class="font-bold text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded border border-indigo-200">
                                                Provider: {{ ticket.provider_name }}
                                            </span>
                                            <span v-if="ticket.backbone_source" class="font-medium text-gray-700 bg-gray-50 px-1.5 py-0.5 rounded border border-gray-200">
                                                {{ ticket.backbone_source }}
                                            </span>
                                        </template>
                                        <template v-else>
                                            <span v-if="ticket.odc" class="font-semibold text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-100">
                                                ODC: {{ ticket.odc.name }}
                                            </span>
                                            <span v-if="ticket.odp" class="font-semibold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-100">
                                                ODP: {{ ticket.odp.name }}
                                            </span>
                                        </template>
                                        <span class="text-gray-500">Area: {{ ticket.area?.name || 'Semua Area / Global' }}</span>
                                    </div>
                                    
                                    <!-- Badge Estimasi Dampak Pelanggan -->
                                    <div class="mt-2 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold" :class="ticket.segment_type === 'backbone' ? 'bg-red-100 border border-red-300 text-red-800' : 'bg-red-50 border border-red-200 text-red-700'">
                                        <svg class="w-3.5 h-3.5 text-red-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <span>{{ ticket.segment_type === 'backbone' ? '🚨 GANGGUAN MASSAL: ' : 'Dampak: ' }}{{ ticket.affected_customers_count }} Pelanggan</span>
                                    </div>

                                    <!-- Titik Koordinat GPS Badges -->
                                    <div v-if="ticket.incident_coordinates && ticket.incident_coordinates.length > 0" class="mt-1.5 flex flex-wrap gap-1">
                                        <a 
                                            v-for="(c, cIdx) in ticket.incident_coordinates" 
                                            :key="cIdx"
                                            :href="`https://www.google.com/maps?q=${c.latitude},${c.longitude}`" 
                                            target="_blank" 
                                            class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 transition-colors"
                                            :title="`${c.name}: ${c.latitude}, ${c.longitude}`"
                                        >
                                            <span>📍 {{ c.name || `Titik ${cIdx + 1}` }}</span>
                                            <svg class="w-2.5 h-2.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    </div>
                                    <div v-else-if="ticket.latitude && ticket.longitude" class="mt-1.5">
                                        <a 
                                            :href="`https://www.google.com/maps?q=${ticket.latitude},${ticket.longitude}`" 
                                            target="_blank" 
                                            class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 transition-colors"
                                        >
                                            <span>📍 Buka Google Maps</span>
                                            <svg class="w-2.5 h-2.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
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
                                    <div v-if="ticket.admin_notes" class="mt-1 text-[11px] text-gray-600 bg-gray-50 p-1.5 rounded border border-gray-100">
                                        <span class="font-bold text-gray-700">Tindakan NOC:</span> {{ ticket.admin_notes }}
                                    </div>
                                    <div v-if="(ticket.handling_photos && ticket.handling_photos.length > 0) || ticket.photo_after || ticket.photo_before" class="mt-1.5 flex items-center gap-1.5">
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                                            📷 {{ (ticket.handling_photos?.length) || (ticket.photo_after && ticket.photo_before ? 2 : 1) }} Foto
                                        </span>
                                    </div>
                                </td>

                                <!-- 4. Status Alur -->
                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider" :class="getStatusBadgeClass(ticket.status)">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(ticket.status)"></span>
                                        {{ getStatusLabel(ticket.status) }}
                                    </span>

                                    <!-- Info Penugasan Ka. Teknisi & Teknisi -->
                                    <div v-if="ticket.lead_technician || ticket.leadTechnician" class="mt-1 text-[11px] text-amber-800">
                                        <span class="font-bold text-gray-500">Ka. Teknisi:</span> {{ (ticket.lead_technician || ticket.leadTechnician)?.name }}
                                    </div>
                                    <div v-if="ticket.technicians && ticket.technicians.length > 0" class="mt-1 text-xs text-gray-600">
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
                                            <button v-if="can('pgj_btn_resolve')" @click="openResolveAdminModal(ticket)" title="Selesaikan Jarak Jauh (NOC)" class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs rounded-lg border border-emerald-200 transition-colors flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                <span>Selesaikan</span>
                                            </button>

                                            <!-- Eskalasi ke Kepala Teknisi -->
                                            <button v-if="can('pgj_btn_escalate')" @click="openEscalateModal(ticket)" title="Eskalasikan ke Kepala Teknisi Lapangan" class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 font-bold text-xs rounded-lg border border-amber-200 transition-colors flex items-center gap-1">
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
                            <select v-model="createForm.segment_type" @change="onSegmentChange" class="w-full rounded-xl border-gray-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 text-sm font-semibold" required>
                                <option value="backbone">⚡ Jalur Utama Backbone / Sumber Internet</option>
                                <option value="odc">Perangkat & Jalur ODC</option>
                                <option value="odp">Perangkat & Jalur ODP</option>
                                <option value="distribution">Jalur Distribusi (ODC ke ODP)</option>
                                <option value="other">Infrastruktur Tiang / Jalur Lainnya</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Area / Wilayah <span class="text-red-500">*</span></label>
                            <select v-model="createForm.area_id" @change="onAreaChange" class="w-full rounded-xl border-gray-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 text-sm" required>
                                <option value="">{{ createForm.segment_type === 'backbone' ? '-- Seluruh Wilayah / Core Backbone --' : '-- Pilih Area --' }}</option>
                                <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }} ({{ a.customers_count || 0 }} Pelanggan)</option>
                            </select>
                        </div>
                    </div>

                    <!-- ════════════════ FITUR CERDAS: JALUR UTAMA BACKBONE / SUMBER INTERNET ════════════════ -->
                    <div v-if="createForm.segment_type === 'backbone'" class="p-4 rounded-2xl bg-gradient-to-br from-purple-50 via-rose-50 to-amber-50 border border-purple-200 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="p-1.5 rounded-lg bg-purple-600 text-white text-xs">⚡</span>
                                <div>
                                    <h4 class="text-xs font-black uppercase text-purple-950 tracking-wider">Identifikasi Jalur Utama & Sumber Internet</h4>
                                    <p class="text-[11px] text-purple-700">Level backbone tidak menggunakan ODP. Sistem memfokuskan perangkat OLT, provider upstream & link utama.</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-purple-200/80 text-purple-900 border border-purple-300">
                                Jalur Strategis
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-800 uppercase mb-1">
                                    Tipe Jalur Backbone / Sumber Internet <span class="text-red-500">*</span>
                                </label>
                                <select v-model="createForm.backbone_source" @change="applyBackboneTemplate" class="w-full rounded-xl border-gray-200 focus:border-purple-500 focus:ring-2 focus:ring-purple-100 text-sm bg-white font-medium" required>
                                    <option value="Kabel Feeder OLT Utama (NOC/POP ke ODC Pusat)">Kabel Feeder OLT (NOC/POP ke ODC Pusat)</option>
                                    <option value="Link Uplink Provider / Sumber Internet (Transit / Upstream)">Link Uplink Provider / Sumber Internet (Upstream ISP)</option>
                                    <option value="Link Interkoneksi Antar-POP / Antar-Area (Backbone Metro FO)">Link Interkoneksi Antar-POP / Antar-Area (Metro FO)</option>
                                    <option value="Kabel Distribusi Ring Backbone Utama">Kabel Distribusi Ring Backbone Utama</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-800 uppercase mb-1">
                                    Perangkat OLT / POP Terkait
                                </label>
                                <select v-model="createForm.olt_id" @change="onOltChange" class="w-full rounded-xl border-gray-200 focus:border-purple-500 focus:ring-2 focus:ring-purple-100 text-sm bg-white">
                                    <option value="">-- Pilih OLT / POP (Jika Spesifik) --</option>
                                    <option v-for="l in filteredOlts" :key="l.id" :value="l.id">
                                        {{ l.name }} [IP: {{ l.ip_address || '-' }}] - {{ l.location || 'POP' }} ({{ oltCustomerCounts[l.id] || 0 }} Plg)
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Jika Sumber Internet Provider Dipilih -->
                        <div v-if="createForm.backbone_source === 'Link Uplink Provider / Sumber Internet (Transit / Upstream)'" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-purple-200/60">
                            <div>
                                <label class="block text-xs font-bold text-gray-800 uppercase mb-1">
                                    Nama Upstream Provider / Sumber Internet <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    v-model="createForm.provider_name" 
                                    @input="applyBackboneTemplate"
                                    placeholder="Contoh: Telkom Indonesia / Indosat / Moratelindo / IFORTE / FiberStar"
                                    class="w-full rounded-xl border-gray-200 focus:border-purple-500 focus:ring-2 focus:ring-purple-100 text-sm bg-white"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-800 uppercase mb-1">
                                    Circuit ID / No. Sirkit Provider (Jika Ada)
                                </label>
                                <input 
                                    type="text" 
                                    v-model="createForm.circuit_id" 
                                    placeholder="Contoh: CID-TKM-10G-9981 / L2-MOR-JKT-04"
                                    class="w-full rounded-xl border-gray-200 focus:border-purple-500 focus:ring-2 focus:ring-purple-100 text-sm bg-white"
                                />
                            </div>
                        </div>

                        <!-- ODC Hub Pusat Distribusi (Opsional jika Feeder) -->
                        <div v-if="createForm.backbone_source !== 'Link Uplink Provider / Sumber Internet (Transit / Upstream)'" class="pt-2 border-t border-purple-200/60">
                            <label class="block text-xs font-bold text-gray-800 uppercase mb-1">
                                ODC Hub / Titik Distribusi Utama (Opsional)
                            </label>
                            <select v-model="createForm.odc_id" @change="onOdcChange" class="w-full rounded-xl border-gray-200 focus:border-purple-500 focus:ring-2 focus:ring-purple-100 text-sm bg-white">
                                <option value="">-- Pilih ODC Hub (Jika Gangguan Menuju ODC Tertentu) --</option>
                                <option v-for="o in filteredOdcs" :key="o.id" :value="o.id">
                                    {{ o.name }} ({{ o.location || 'Lokasi ODC' }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- ════════════════ PEMILIHAN ODC & ODP STANDAR (NON-BACKBONE) ════════════════ -->
                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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

                    <!-- Banner Estimasi Dampak Otomatis (Cerdas) -->
                    <div 
                        class="p-4 rounded-2xl flex items-center justify-between text-xs transition-all"
                        :class="createForm.segment_type === 'backbone' ? 'bg-red-500/10 border-2 border-red-500 text-red-950 shadow-md' : 'bg-rose-50/80 border border-rose-200 text-rose-900'"
                    >
                        <div class="flex items-center gap-3">
                            <span class="text-2xl" :class="createForm.segment_type === 'backbone' ? 'animate-bounce' : ''">🚨</span>
                            <div>
                                <div class="font-black text-sm flex items-center gap-2">
                                    <span>{{ createForm.segment_type === 'backbone' ? 'INSIDEN GANGGUAN MASSAL JALUR UTAMA / BACKBONE' : 'Estimasi Dampak Pelanggan Otomatis' }}</span>
                                    <span v-if="createForm.segment_type === 'backbone'" class="px-2 py-0.5 rounded bg-red-600 text-white font-mono text-[10px] uppercase font-bold animate-pulse">KRITIS</span>
                                </div>
                                <div class="text-[11px] mt-0.5" :class="createForm.segment_type === 'backbone' ? 'text-red-700 font-semibold' : 'text-rose-700'">
                                    {{ createForm.segment_type === 'backbone' ? 'Jalur utama putus berdampak melumpuhkan seluruh perangkat ODC, ODP & ONT pelanggan di bawah segmen ini serentak.' : 'Sistem menghitung perkiraan perangkat ONT pelanggan di bawah jalur yang dipilih.' }}
                                </div>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-lg font-black px-4 py-1.5 rounded-xl border block shadow-xs" :class="createForm.segment_type === 'backbone' ? 'bg-red-600 text-white border-red-700' : 'bg-white border-rose-300 text-rose-700'">
                                {{ estimatedImpact }} Pelanggan
                            </span>
                            <span v-if="createForm.segment_type === 'backbone'" class="text-[10px] text-red-600 font-bold block mt-0.5">Downtime Massal</span>
                        </div>
                    </div>

                    <!-- Titik Lokasi / Alamat Kerusakan -->
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

                    <!-- Titik Koordinat Insiden (Bisa > 1) -->
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>📍</span> Titik Koordinat Insiden (Bisa Lebih Dari 1)
                                </label>
                                <p class="text-[11px] text-gray-500">
                                    Opsional: Catat 1 atau lebih titik koordinat GPS insiden (titik putus kabel, tiang tumbang, ODC/ODP).
                                </p>
                            </div>
                            <button 
                                type="button" 
                                @click="addCreateCoordinatePoint" 
                                class="px-2.5 py-1 bg-slate-700 hover:bg-slate-800 text-white font-bold text-[11px] rounded-lg shadow-xs transition-all flex items-center gap-1"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>+ Tambah Titik</span>
                            </button>
                        </div>

                        <div v-if="createForm.incident_coordinates.length === 0" class="p-2.5 bg-white rounded-xl border border-dashed border-gray-300 text-center text-xs text-gray-400">
                            Belum ada titik koordinat. Klik "+ Tambah Titik" jika sudah diketahui koordinat GPS insiden di peta.
                        </div>

                        <div v-else class="space-y-2">
                            <div 
                                v-for="(coord, idx) in createForm.incident_coordinates" 
                                :key="idx" 
                                class="p-3 rounded-xl bg-white border border-gray-200 space-y-2 shadow-xs"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">#{{ idx + 1 }}</span>
                                    <input 
                                        type="text" 
                                        v-model="coord.name" 
                                        placeholder="Label Titik (Contoh: Tiang 12 Kabel Putus)"
                                        class="flex-1 rounded-xl border-gray-200 text-xs font-semibold py-1 px-2.5 focus:border-rose-500 focus:ring-1 focus:ring-rose-200"
                                    />
                                    <button 
                                        type="button" 
                                        @click="removeCreateCoordinatePoint(idx)" 
                                        class="p-1 text-gray-400 hover:text-rose-600 rounded"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
                                    <div class="sm:col-span-4">
                                        <input 
                                            type="text" 
                                            v-model="coord.latitude" 
                                            placeholder="Latitude (cth: -6.2088)" 
                                            class="w-full rounded-xl border-gray-200 text-xs font-mono py-1 px-2.5 focus:border-rose-500 focus:ring-1 focus:ring-rose-200"
                                        />
                                    </div>
                                    <div class="sm:col-span-4">
                                        <input 
                                            type="text" 
                                            v-model="coord.longitude" 
                                            placeholder="Longitude (cth: 106.8456)" 
                                            class="w-full rounded-xl border-gray-200 text-xs font-mono py-1 px-2.5 focus:border-rose-500 focus:ring-1 focus:ring-rose-200"
                                        />
                                    </div>
                                    <div class="sm:col-span-4 flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            @click="getGpsForCreateCoord(idx)" 
                                            :disabled="createGpsLoadingIndex === idx"
                                            class="flex-1 px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold flex items-center justify-center gap-1"
                                            title="Ambil GPS perangkat saat ini"
                                        >
                                            <svg v-if="createGpsLoadingIndex === idx" class="w-3 h-3 animate-spin text-rose-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <span v-else>📍 GPS</span>
                                        </button>
                                        <button 
                                            type="button" 
                                            @click="openGoogleMaps(coord.latitude, coord.longitude)" 
                                            :disabled="!coord.latitude || !coord.longitude"
                                            class="flex-1 px-2 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-lg text-xs font-bold flex items-center justify-center gap-1 disabled:opacity-40"
                                            title="Buka di Maps"
                                        >
                                            🗺️ Maps
                                        </button>
                                    </div>
                                </div>
                            </div>
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
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-gray-700 uppercase">Indikasi Masalah / Uraian Kerusakan <span class="text-red-500">*</span></label>
                            <button 
                                v-if="createForm.segment_type === 'backbone'" 
                                type="button" 
                                @click="applyBackboneTemplate" 
                                class="text-[11px] font-bold text-purple-700 hover:text-purple-900 bg-purple-100/70 hover:bg-purple-200/70 px-2.5 py-0.5 rounded-lg border border-purple-200 transition-colors flex items-center gap-1"
                            >
                                <span>⚡ Gunakan Template Indikasi Backbone</span>
                            </button>
                        </div>
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
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tujukan Kepala Teknisi (Opsional)</label>
                        <select v-model="escalateForm.lead_technician_id" class="w-full rounded-xl border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-sm">
                            <option value="">-- Semua Kepala Teknisi / Default --</option>
                            <option v-for="lead in leadTechnicians" :key="lead.id" :value="lead.id">{{ lead.name }}</option>
                        </select>
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
                            <span>Segmen: <strong class="uppercase text-rose-700">{{ getSegmentLabel(detailTicket?.segment_type) }}</strong></span>
                            <span>Area: {{ detailTicket?.area?.name || 'Semua Area / Global' }}</span>
                        </div>
                        <div class="text-xs text-gray-700">Titik / Alamat: <strong>{{ detailTicket?.location_name || '-' }}</strong></div>
                        
                        <div v-if="detailTicket?.segment_type === 'backbone'" class="space-y-1.5 p-2.5 rounded-xl bg-purple-50/80 border border-purple-200 text-xs">
                            <div v-if="detailTicket?.olt" class="text-purple-950">
                                Perangkat OLT / POP: <strong>{{ detailTicket.olt.name }}</strong> (IP: {{ detailTicket.olt.ip_address || '-' }})
                            </div>
                            <div v-if="detailTicket?.backbone_source" class="text-purple-900">
                                Tipe Jalur Backbone: <strong>{{ detailTicket.backbone_source }}</strong>
                            </div>
                            <div v-if="detailTicket?.provider_name" class="text-purple-900">
                                Upstream Provider: <strong>{{ detailTicket.provider_name }}</strong> <span v-if="detailTicket?.circuit_id" class="font-mono text-purple-700 font-bold">(CID: {{ detailTicket.circuit_id }})</span>
                            </div>
                        </div>
                        
                        <div v-if="detailTicket?.odc && detailTicket?.segment_type !== 'backbone'" class="text-xs text-gray-700">ODC Terkait: <strong>{{ detailTicket.odc.name }}</strong> ({{ detailTicket.odc.location || '-' }})</div>
                        <div v-if="detailTicket?.odp && detailTicket?.segment_type !== 'backbone'" class="text-xs text-gray-700">ODP Terkait: <strong>{{ detailTicket.odp.name }}</strong> ({{ detailTicket.odp.address || '-' }})</div>
                    </div>

                    <!-- Titik Koordinat Insiden Lapangan (Bisa > 1) -->
                    <div v-if="detailTicket?.incident_coordinates && detailTicket.incident_coordinates.length > 0" class="p-3.5 rounded-2xl bg-emerald-50/70 border border-emerald-200 space-y-2">
                        <div class="text-[10px] font-bold uppercase text-emerald-800 flex items-center justify-between">
                            <span>📍 Titik Koordinat Insiden Lapangan ({{ detailTicket.incident_coordinates.length }} Titik):</span>
                        </div>
                        <div class="space-y-2">
                            <div 
                                v-for="(coord, cIdx) in detailTicket.incident_coordinates" 
                                :key="cIdx"
                                class="p-2.5 rounded-xl bg-white border border-emerald-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-xs"
                            >
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">#{{ cIdx + 1 }}</span>
                                        <strong class="text-xs text-gray-900 truncate">{{ coord.name || `Titik ${cIdx + 1}` }}</strong>
                                    </div>
                                    <div class="font-mono text-[11px] text-gray-500 mt-0.5">
                                        Lat: {{ coord.latitude || '-' }} · Lng: {{ coord.longitude || '-' }}
                                    </div>
                                    <div v-if="coord.notes" class="text-[11px] text-gray-600 italic mt-0.5">
                                        "{{ coord.notes }}"
                                    </div>
                                </div>
                                <a 
                                    v-if="coord.latitude && coord.longitude"
                                    :href="`https://www.google.com/maps?q=${coord.latitude},${coord.longitude}`" 
                                    target="_blank"
                                    class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 shrink-0"
                                >
                                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    <span>Buka di Google Maps</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="detailTicket?.latitude && detailTicket?.longitude" class="p-3 rounded-xl bg-emerald-50/70 border border-emerald-200 flex items-center justify-between">
                        <div class="text-xs text-emerald-950 font-mono">
                            <span>📍 Koordinat: {{ detailTicket.latitude }}, {{ detailTicket.longitude }}</span>
                        </div>
                        <a 
                            :href="`https://www.google.com/maps?q=${detailTicket.latitude},${detailTicket.longitude}`" 
                            target="_blank"
                            class="px-3 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-xl text-xs font-bold transition-all flex items-center gap-1"
                        >
                            <span>Buka Maps</span>
                        </a>
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

                    <!-- Info Eskalasi ke Ka. Teknisi jika belum dijadwalkan -->
                    <div v-if="detailTicket?.status === 'escalated_to_lead'" class="p-3.5 rounded-2xl bg-amber-50/70 border border-amber-200 space-y-1">
                        <div class="text-[10px] font-bold uppercase text-amber-800">Menunggu Penjadwalan Lapangan:</div>
                        <div class="text-xs text-amber-950">
                            Ditujukan ke: <strong>{{ (detailTicket?.lead_technician || detailTicket?.leadTechnician)?.name || 'Semua Kepala Teknisi / Default' }}</strong>
                        </div>
                    </div>

                    <!-- Jadwal & Tim Teknisi -->
                    <div v-if="detailTicket?.status === 'scheduled' || detailTicket?.status === 'completed'" class="p-3.5 rounded-2xl bg-indigo-50/50 border border-indigo-100 space-y-1">
                        <div class="text-[10px] font-bold uppercase text-indigo-800">Jadwal & Tim Teknisi:</div>
                        <div v-if="detailTicket?.lead_technician || detailTicket?.leadTechnician" class="text-xs text-amber-900">
                            Ka. Teknisi: <strong>{{ (detailTicket?.lead_technician || detailTicket?.leadTechnician)?.name }}</strong>
                        </div>
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

                    <!-- Galeri Foto Dokumentasi Penanganan (Bisa > 1) -->
                    <div v-if="(detailTicket?.handling_photos && detailTicket.handling_photos.length > 0) || detailTicket?.photo_before || detailTicket?.photo_after" class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-2.5">
                        <div class="text-[10px] font-bold uppercase text-slate-700 flex items-center justify-between">
                            <span>📸 Dokumentasi Foto Penanganan Lapangan:</span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                            <template v-if="detailTicket?.handling_photos && detailTicket.handling_photos.length > 0">
                                <div 
                                    v-for="(photo, pIdx) in detailTicket.handling_photos" 
                                    :key="pIdx"
                                    class="rounded-xl overflow-hidden bg-white border border-gray-200 shadow-xs flex flex-col group cursor-pointer hover:border-emerald-300 transition-all"
                                    @click="openPhotoZoom(`/storage/${photo.path}`)"
                                >
                                    <div class="aspect-video bg-gray-100 overflow-hidden relative">
                                        <img :src="`/storage/${photo.path}`" alt="Foto Penanganan" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" />
                                        <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                            <svg class="w-5 h-5 drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                                        </div>
                                    </div>
                                    <div class="p-2 text-[11px]">
                                        <div class="font-bold text-gray-800 line-clamp-1">{{ photo.caption || 'Foto Penanganan' }}</div>
                                        <div v-if="photo.uploaded_at" class="text-[9px] text-gray-400 mt-0.5">{{ photo.uploaded_at }}</div>
                                    </div>
                                </div>
                            </template>
                            <template v-else>
                                <div v-if="detailTicket?.photo_before" class="rounded-xl overflow-hidden bg-white border border-gray-200 shadow-xs flex flex-col group cursor-pointer" @click="openPhotoZoom(`/storage/${detailTicket.photo_before}`)">
                                    <div class="aspect-video bg-gray-100 overflow-hidden relative">
                                        <img :src="`/storage/${detailTicket.photo_before}`" alt="Sebelum" class="w-full h-full object-cover group-hover:scale-105 transition-transform" />
                                    </div>
                                    <div class="p-2 text-[11px] font-bold text-gray-800">Foto Kerusakan (Sebelum)</div>
                                </div>
                                <div v-if="detailTicket?.photo_after" class="rounded-xl overflow-hidden bg-white border border-gray-200 shadow-xs flex flex-col group cursor-pointer" @click="openPhotoZoom(`/storage/${detailTicket.photo_after}`)">
                                    <div class="aspect-video bg-gray-100 overflow-hidden relative">
                                        <img :src="`/storage/${detailTicket.photo_after}`" alt="Sesudah" class="w-full h-full object-cover group-hover:scale-105 transition-transform" />
                                    </div>
                                    <div class="p-2 text-[11px] font-bold text-emerald-800">Hasil Restorasi (Sesudah)</div>
                                </div>
                            </template>
                        </div>
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

        <!-- ════════════ LIGHTBOX MODAL (ZOOM FOTO PENANGANAN) ════════════ -->
        <div v-if="previewModalImage" class="fixed inset-0 z-[60] bg-black/90 backdrop-blur-md flex items-center justify-center p-4 animate-fade-in" @click.self="previewModalImage = null">
            <div class="relative max-w-4xl max-h-[90vh] flex flex-col items-center">
                <button 
                    type="button" 
                    @click="previewModalImage = null" 
                    class="absolute -top-10 right-0 text-white text-3xl font-bold hover:text-rose-400 transition-colors"
                    title="Tutup (Esc)"
                >
                    &times;
                </button>
                <img :src="previewModalImage" class="max-w-full max-h-[85vh] rounded-2xl object-contain shadow-2xl border border-white/20" />
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
    olts: Array,
    oltCustomerCounts: Object,
    totalActiveCustomers: Number,
    odcs: Array,
    odps: Array,
    technicians: Array,
    leadTechnicians: Array,
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
const previewModalImage = ref(null);
const createGpsLoadingIndex = ref(null);

const createForm = useForm({
    segment_type: 'odc',
    backbone_source: 'Kabel Feeder OLT Utama (NOC/POP ke ODC Pusat)',
    provider_name: '',
    circuit_id: '',
    area_id: '',
    olt_id: '',
    odc_id: '',
    odp_id: '',
    location_name: '',
    latitude: '',
    longitude: '',
    incident_coordinates: [],
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
    lead_technician_id: '',
    admin_notes: '',
});

// Filter OLT berdasarkan Area
const filteredOlts = computed(() => {
    if (!createForm.area_id) return props.olts || [];
    return (props.olts || []).filter(o => o.area_id == createForm.area_id || !o.area_id);
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
    if (createForm.segment_type === 'backbone') {
        // 1. Jika OLT dipilih, gunakan hitungan pelanggan di OLT tersebut
        if (createForm.olt_id && props.oltCustomerCounts && props.oltCustomerCounts[createForm.olt_id] !== undefined) {
            const count = props.oltCustomerCounts[createForm.olt_id];
            if (count > 0) return count;
        }
        // 2. Jika Area dipilih, gunakan jumlah pelanggan di Area tersebut
        if (createForm.area_id) {
            const area = (props.areas || []).find(a => a.id == createForm.area_id);
            if (area && area.customers_count) {
                return area.customers_count;
            }
        }
        // 3. Jika ODC dipilih
        if (createForm.odc_id) {
            const odc = (props.odcs || []).find(o => o.id == createForm.odc_id);
            const odpCount = odc?.odps_count || 4;
            return odpCount * 12;
        }
        // 4. Default Backbone / Sumber Internet: Total seluruh pelanggan aktif sistem atau fallback 150
        return props.totalActiveCustomers || 128;
    }

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
    return 16;
});

const onSegmentChange = () => {
    if (createForm.segment_type === 'backbone') {
        createForm.priority = 'critical';
        createForm.odp_id = ''; // Hilangkan ODP karena tidak relevan di backbone
        createForm.source_report = 'Monitoring NMS';
        createForm.category = 'Kabel FO Putus Total (Cut Cable)';
        if (!createForm.backbone_source) {
            createForm.backbone_source = 'Kabel Feeder OLT Utama (NOC/POP ke ODC Pusat)';
        }
        if (!createForm.reported_issue) {
            applyBackboneTemplate();
        }
    }
};

const applyBackboneTemplate = () => {
    const olt = (props.olts || []).find(o => o.id == createForm.olt_id);
    const area = (props.areas || []).find(a => a.id == createForm.area_id);
    const areaName = area ? area.name : 'Wilayah Terkait';
    const oltName = olt ? `OLT ${olt.name}` : 'POP Utama';
    
    if (createForm.backbone_source === 'Link Uplink Provider / Sumber Internet (Transit / Upstream)') {
        const prov = createForm.provider_name || 'Upstream ISP';
        createForm.reported_issue = `Terdeteksi Link Sumber Internet / Uplink Provider ${prov} mengalami LOS (Kabel Putus / Link Down). Trafik internet drop total, estimasi ${estimatedImpact.value} pelanggan mengalami gangguan akses internet serentak.`;
        createForm.admin_notes = `Eskalasi darurat ke NOC Provider ${prov} untuk cek tiket gangguan upstream. Siapkan pengalihan routing ke link failover jika ada.`;
    } else {
        createForm.reported_issue = `Terdeteksi alarm LOS serentak pada Jalur Utama Backbone ${oltName} (${areaName}). Seluruh ODC dan ODP down serentak. Estimasi ${estimatedImpact.value} pelanggan mati total. Diduga kabel feeder utama putus.`;
        createForm.admin_notes = `Pengecekan NMS menunjukkan loss total pada port uplink feeder. Tim splicer jalur utama dan OTDR segera diberangkatkan ke lokasi.`;
    }
};

const onOltChange = () => {
    const olt = (props.olts || []).find(o => o.id == createForm.olt_id);
    if (olt) {
        if (!createForm.area_id && olt.area_id) {
            createForm.area_id = olt.area_id;
        }
        createForm.location_name = olt.location || `POP / OLT ${olt.name}`;
        createForm.latitude = olt.latitude || '';
        createForm.longitude = olt.longitude || '';
        applyBackboneTemplate();
    }
};

const onAreaChange = () => {
    createForm.odc_id = '';
    createForm.odp_id = '';
    if (createForm.segment_type === 'backbone') {
        applyBackboneTemplate();
    }
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
    createForm.incident_coordinates = [];
    showCreateModal.value = true;
};

const addCreateCoordinatePoint = () => {
    createForm.incident_coordinates.push({
        name: `Titik Gangguan #${createForm.incident_coordinates.length + 1}`,
        latitude: '',
        longitude: '',
        notes: '',
    });
};

const removeCreateCoordinatePoint = (index) => {
    createForm.incident_coordinates.splice(index, 1);
};

const getGpsForCreateCoord = (index) => {
    if (!navigator.geolocation) {
        alert('Fitur Geolocation tidak didukung oleh browser Anda.');
        return;
    }
    createGpsLoadingIndex.value = index;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            if (createForm.incident_coordinates[index]) {
                createForm.incident_coordinates[index].latitude = pos.coords.latitude.toFixed(6);
                createForm.incident_coordinates[index].longitude = pos.coords.longitude.toFixed(6);
            }
            createGpsLoadingIndex.value = null;
        },
        (err) => {
            alert('Gagal mendeteksi lokasi GPS: ' + (err.message || 'Izin akses lokasi ditolak'));
            createGpsLoadingIndex.value = null;
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
};

const openGoogleMaps = (lat, lng) => {
    if (!lat || !lng) return;
    window.open(`https://www.google.com/maps?q=${lat},${lng}`, '_blank');
};

const openPhotoZoom = (imgSrc) => {
    previewModalImage.value = imgSrc;
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
    escalateForm.lead_technician_id = ticket.lead_technician_id || '';
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
