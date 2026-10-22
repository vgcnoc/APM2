<template>
    <AppLayout title="Laporan Restorasi Jalur (PGJ)" subtitle="Pelaporan Hasil Pengerjaan Lapangan, Pengukuran Core FO & Material Terpakai oleh Teknisi">
        <div class="space-y-6">


            <!-- Toolbar & Filter -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4">
                <div v-if="can('pgj_filter')" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
                    <div class="relative flex-1 sm:w-80">
                        <input 
                            type="text" 
                            v-model="search" 
                            placeholder="Cari No. PGJ, ODC, ODP, tindakan..." 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm transition-all"
                            @keyup.enter="performSearch"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <select v-model="filterArea" @change="performSearch" class="px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm bg-gray-50/50">
                        <option value="">Semua Area</option>
                        <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>
                </div>
                
                <div class="text-xs text-gray-500 font-medium">
                    Halaman ini digunakan tim teknisi untuk melaporkan hasil penyambungan core FO, optical loss, foto, dan material terpakai.
                </div>
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
                                <th class="p-4">No. Tiket & Titik Segmen</th>
                                <th class="p-4">Instruksi & Material Disiapkan</th>
                                <th class="p-4">Status & Waktu Restorasi</th>
                                <th class="p-4">Hasil Tindakan & Optical Loss</th>
                                <th class="p-4 text-center">Aksi Teknisi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-emerald-50/20 transition-colors group">
                                <!-- 1. No Tiket & Titik Segmen -->
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-emerald-600 text-xs">{{ ticket.ticket_number }}</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border" :class="ticket.priority === 'critical' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-gray-50 text-gray-700 border-gray-200'">
                                            {{ ticket.priority }}
                                        </span>
                                    </div>
                                    <div class="font-bold text-gray-900 mt-1">
                                        {{ ticket.location_name }}
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

                                    <!-- Titik Koordinat GPS Badges -->
                                    <div v-if="ticket.incident_coordinates && ticket.incident_coordinates.length > 0" class="mt-1.5 flex flex-wrap gap-1">
                                        <a 
                                            v-for="(c, cIdx) in ticket.incident_coordinates" 
                                            :key="cIdx"
                                            :href="`https://www.google.com/maps?q=${c.latitude},${c.longitude}`" 
                                            target="_blank" 
                                            class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 hover:shadow-xs transition-all"
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
                                            class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 hover:shadow-xs transition-all"
                                        >
                                            <span>📍 Buka Google Maps</span>
                                            <svg class="w-2.5 h-2.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    </div>
                                </td>

                                <!-- 2. Instruksi & Material Disiapkan -->
                                <td class="p-4">
                                    <div class="text-xs font-semibold text-gray-800">
                                        Kerusakan: "{{ ticket.reported_issue }}"
                                    </div>
                                    <div v-if="ticket.lead_notes" class="mt-1.5 p-2 rounded-xl bg-amber-50/80 border border-amber-100 text-xs text-amber-900">
                                        <span class="font-bold text-amber-800">Instruksi Ka. Teknisi:</span> {{ ticket.lead_notes }}
                                    </div>
                                    <!-- Material disiapkan -->
                                    <div v-if="ticket.materials && ticket.materials.length > 0" class="mt-2 text-xs space-y-1">
                                        <div class="text-[10px] text-gray-400 font-bold uppercase">Material FO Dibawa:</div>
                                        <div v-for="m in ticket.materials" :key="m.id" class="text-[11px] bg-gray-50 px-2 py-0.5 rounded border border-gray-100 flex justify-between">
                                            <span class="text-gray-700 font-medium">{{ m.material?.name }}</span>
                                            <span class="text-gray-900 font-bold">{{ parseFloat(m.quantity_prepared) }} {{ m.unit || m.material?.unit }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- 3. Status & Waktu Restorasi -->
                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider" :class="ticket.status === 'completed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-indigo-50 text-indigo-700 border border-indigo-200'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="ticket.status === 'completed' ? 'bg-emerald-500' : 'bg-indigo-500 animate-pulse'"></span>
                                        {{ ticket.status === 'completed' ? 'Restorasi Selesai' : 'Siap Ditangani' }}
                                    </span>

                                    <div class="mt-2 text-xs text-gray-600">
                                        <div class="text-[10px] text-gray-400 font-bold uppercase">Jadwal:</div>
                                        <div class="font-semibold text-gray-800">{{ formatDate(ticket.scheduled_date) }} ({{ ticket.scheduled_time || 'Segera' }})</div>
                                    </div>

                                    <div v-if="ticket.start_time || ticket.end_time" class="mt-1 text-xs text-gray-500">
                                        Jam Kerja: {{ ticket.start_time || '-' }} s/d {{ ticket.end_time || '-' }}
                                    </div>
                                </td>

                                <!-- 4. Hasil Tindakan & Optical Loss -->
                                <td class="p-4">
                                    <div v-if="ticket.status === 'completed'">
                                        <div class="text-xs font-bold text-gray-900">
                                            {{ ticket.action_taken || '-' }}
                                        </div>
                                        <div v-if="ticket.core_details" class="mt-0.5 text-xs text-indigo-700 font-medium">
                                            Core: {{ ticket.core_details }}
                                        </div>
                                        <div class="mt-1 text-xs text-gray-600 flex items-center gap-2">
                                            <span>Loss:</span>
                                            <span class="font-mono text-gray-500">{{ ticket.optical_loss_before || '-' }}</span>
                                            <span>&rarr;</span>
                                            <span class="font-mono font-bold text-emerald-600">{{ ticket.optical_loss_after || '-' }}</span>
                                        </div>
                                        <div v-if="ticket.resolution_notes" class="mt-1 text-[11px] text-gray-500 italic">
                                            "{{ ticket.resolution_notes }}"
                                        </div>
                                        <div v-if="(ticket.handling_photos && ticket.handling_photos.length > 0) || ticket.photo_after || ticket.photo_before" class="mt-1.5 flex items-center gap-1.5">
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                                                📷 {{ (ticket.handling_photos?.length) || (ticket.photo_after && ticket.photo_before ? 2 : 1) }} Foto Penanganan
                                            </span>
                                        </div>
                                    </div>
                                    <div v-else class="text-xs text-gray-400 italic">
                                        Menunggu tim teknisi melaporkan hasil penyambungan & restorasi...
                                    </div>
                                </td>

                                <!-- 5. Aksi Teknisi -->
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button 
                                            v-if="can('pgj_btn_report')"
                                            @click="openReportModal(ticket)" 
                                            class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/20 transition-all flex items-center gap-1.5"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            <span>{{ ticket.status === 'completed' ? 'Edit Laporan' : 'Input Laporan' }}</span>
                                        </button>

                                        <a :href="`/pgj/${ticket.id}/print`" target="_blank" title="Cetak Berita Acara & SPK Jalur" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="tickets.data.length === 0">
                                <td colspan="5" class="p-8 text-center text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Tidak ada tiket restorasi jalur yang terjadwal saat ini.
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
                                :class="link.active ? 'bg-emerald-600 text-white border-emerald-600 font-bold' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-200'"
                            />
                            <span v-else v-html="link.label" class="px-3 py-1 text-xs rounded-lg text-gray-300 border border-transparent"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════ MODAL INPUT LAPORAN RESTORASI JALUR (PGJ) ════════════ -->
        <div v-if="showReportModal" class="fixed inset-0 z-50 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
            <div class="bg-white rounded-3xl max-w-3xl w-full shadow-2xl border border-gray-100 overflow-hidden animate-fade-in-up my-auto flex flex-col max-h-[92vh]">
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 text-white flex justify-between items-center shrink-0">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-black text-lg">Laporan Restorasi Lapangan PGJ</h3>
                            <span class="px-2 py-0.5 rounded-md bg-white/20 text-white font-mono text-xs font-bold">#{{ selectedTicket?.ticket_number }}</span>
                        </div>
                        <p class="text-xs text-emerald-100 mt-0.5">Catat tindakan perbaikan, detail core FO, optical loss, rekonsiliasi material & dokumentasi</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span v-if="hasSavedDraft" class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-400/20 text-amber-200 border border-amber-300/30 text-[11px] font-medium" title="Data tersimpan otomatis di browser ini">
                            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                            Draft Otomatis Tersimpan
                        </span>
                        <button @click="closeReportModal" class="text-white/80 hover:text-white text-2xl font-bold leading-none p-1 rounded-lg hover:bg-white/10 transition-colors">&times;</button>
                    </div>
                </div>

                <form @submit.prevent="submitReport" class="p-6 space-y-6 overflow-y-auto flex-1">
                    <!-- Alert Draft Restored -->
                    <div v-if="hasSavedDraft" class="p-3 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-base">💾</span>
                            <span><strong>Draft Terdeteksi:</strong> Formulir dan timer pengerjaan telah dimuat otomatis dari sesi sebelumnya.</span>
                        </div>
                        <button type="button" @click="clearDraft" class="text-rose-600 hover:text-rose-800 font-bold hover:underline shrink-0 text-xs">
                            Buang Draft
                        </button>
                    </div>

                    <!-- Ringkasan Gangguan & Dampak -->
                    <div class="p-4 rounded-2xl border text-xs space-y-2" :class="selectedTicket?.segment_type === 'backbone' ? 'bg-gradient-to-r from-purple-50/80 via-emerald-50/50 to-teal-50/80 border-purple-200 text-purple-950' : 'bg-emerald-50/70 border-emerald-100 text-emerald-950'">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="font-bold text-sm text-gray-900 flex items-center gap-2">
                                <span>Titik: {{ selectedTicket?.location_name }}</span>
                                <span class="px-2 py-0.5 rounded text-xs font-bold border uppercase" :class="getSegmentBadgeClass(selectedTicket?.segment_type)">
                                    Segmen: {{ getSegmentLabel(selectedTicket?.segment_type) }}
                                </span>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg bg-emerald-100/80 font-bold text-emerald-800 text-[11px]">
                                Area: {{ selectedTicket?.area?.name || 'Semua Area / Global' }}
                            </span>
                        </div>
                        <div v-if="selectedTicket?.segment_type === 'backbone'" class="space-y-0.5 p-2 rounded-xl bg-white/80 border border-purple-200 font-semibold text-xs">
                            <div v-if="selectedTicket?.olt" class="text-purple-900">Perangkat OLT / POP: <strong>{{ selectedTicket.olt.name }}</strong> (IP: {{ selectedTicket.olt.ip_address || '-' }})</div>
                            <div v-if="selectedTicket?.provider_name" class="text-indigo-900">Upstream Provider: <strong>{{ selectedTicket.provider_name }}</strong> <span v-if="selectedTicket?.circuit_id">(CID: {{ selectedTicket.circuit_id }})</span></div>
                            <div v-if="selectedTicket?.backbone_source" class="text-gray-700">Tipe Jalur: {{ selectedTicket.backbone_source }}</div>
                        </div>
                        <div class="text-gray-700">
                            <strong>Temuan Gangguan:</strong> <span class="text-red-700 font-medium">"{{ selectedTicket?.reported_issue }}"</span>
                        </div>
                        <div v-if="selectedTicket?.segment_type !== 'backbone' && (selectedTicket?.odc || selectedTicket?.odp)" class="text-gray-600 flex flex-wrap gap-3">
                            <span v-if="selectedTicket?.odc"><strong>ODC:</strong> {{ selectedTicket.odc.name }}</span>
                            <span v-if="selectedTicket?.odp"><strong>ODP:</strong> {{ selectedTicket.odp.name }}</span>
                        </div>
                        <div v-if="selectedTicket?.lead_notes" class="p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 font-medium">
                            <span class="font-bold text-amber-800">Instruksi Ka. Teknisi:</span> "{{ selectedTicket?.lead_notes }}"
                        </div>
                    </div>

                    <!-- ════════ SECTION 1: JAM MULAI PENGERJAAN & LIVE STOPWATCH ════════ -->
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider">
                                    ⏱️ 1. Jam Mulai Pengerjaan Lapangan
                                </label>
                                <p class="text-[11px] text-gray-500">Tekan tombol mulai saat Anda tiba di lokasi jalur FO. Waktu akan terus berjalan meski keluar aplikasi.</p>
                            </div>
                            <!-- Live Running Stopwatch Indicator -->
                            <div v-if="isWorking" class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-100 border border-emerald-300 text-emerald-800 self-start sm:self-auto">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 animate-ping"></span>
                                <span class="text-xs font-bold uppercase tracking-wider">Sedang Berjalan:</span>
                                <span class="font-mono text-sm font-black text-emerald-950">{{ formatSecondsToClock(elapsedSeconds) }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center pt-1">
                            <div>
                                <div class="flex items-center gap-2">
                                    <input 
                                        type="time" 
                                        v-model="reportForm.start_time" 
                                        :readonly="isWorking"
                                        class="flex-1 rounded-xl border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm font-semibold text-gray-800 bg-white" 
                                        required
                                    />
                                    <button 
                                        type="button" 
                                        @click="startWork" 
                                        :class="isWorking ? 'bg-emerald-700 hover:bg-emerald-800' : 'bg-emerald-600 hover:bg-emerald-700'"
                                        class="px-4 py-2 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-1.5 shrink-0"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>{{ reportForm.start_time ? 'Reset Jam Mulai' : 'Mulai Pengerjaan' }}</span>
                                    </button>
                                </div>
                            </div>
                            <div class="text-[11px] text-gray-500 italic">
                                <span v-if="reportForm.start_time" class="text-emerald-700 font-semibold">
                                    ✓ Dimulai pukul <strong>{{ reportForm.start_time }} WIB</strong>. Data isian otomatis tersimpan aman di perangkat Anda.
                                </span>
                                <span v-else class="text-amber-700">
                                    * Klik tombol "Mulai Pengerjaan" saat penanganan lapangan dimulai.
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- ════════ SECTION 2: TINDAKAN RESTORASI & DETAIL CORE FO ════════ -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                                Tindakan Restorasi Fisik Lapangan <span class="text-red-500">*</span>
                            </label>
                            <textarea 
                                v-model="reportForm.action_taken" 
                                rows="2" 
                                placeholder="Contoh: Penarikan span kabel FO 24 core baru sepanjang 65 meter, penggantian closure dome di tiang no 12, dan penyambungan (splicing) seluruh core."
                                class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm" 
                                required
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                                Detail Core FO / Tube yang Disambung
                            </label>
                            <input 
                                type="text" 
                                v-model="reportForm.core_details" 
                                :placeholder="selectedTicket?.segment_type === 'backbone' ? 'Contoh: Tube Biru Core 1-12 Feeder OLT Utama disambung rapih, Tube Orange Core 13-24 Standby Spare (Loss 0.02 dB/splice)' : 'Contoh: Core 1-8 tube biru active (ODC-02), Core 9-12 tube orange standby spare'"
                                class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm" 
                            />
                        </div>
                    </div>

                    <!-- ════════ SECTION 3: OPTICAL LOSS SEBELUM & SESUDAH ════════ -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Optical Loss Sebelum (dB / dBm)</label>
                            <input 
                                type="text" 
                                v-model="reportForm.optical_loss_before" 
                                placeholder="Contoh: LOS / Cut Cable / -34 dBm"
                                class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm" 
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Optical Loss Sesudah (dB / dBm)</label>
                            <input 
                                type="text" 
                                v-model="reportForm.optical_loss_after" 
                                placeholder="Contoh: 0.05 dB / -18.2 dBm"
                                class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm" 
                            />
                        </div>
                    </div>

                    <!-- ════════ SECTION 4: REKONSILIASI MATERIAL FO TERPAKAI CANGGIH ════════ -->
                    <div class="p-4 rounded-2xl bg-amber-50/50 border border-amber-200/80 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block text-xs font-black text-amber-950 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>📦</span> Rekonsiliasi Material FO Terpakai & Pengembalian
                                </label>
                                <p class="text-[11px] text-amber-800">
                                    Catat jumlah material terpakai. Sisa material otomatis dikembalikan ke stok gudang.
                                </p>
                            </div>
                        </div>

                        <!-- Daftar Material Disiapkan Gudang / Jadwal -->
                        <div class="space-y-2">
                            <div class="text-[11px] font-bold text-gray-600 uppercase flex items-center justify-between">
                                <span>Material Disiapkan Gudang / Jadwal</span>
                                <span class="text-gray-400 font-normal">({{ reportForm.materials.length }} jenis)</span>
                            </div>

                            <div v-if="reportForm.materials.length === 0" class="p-3 rounded-xl bg-white border border-gray-200 text-xs text-gray-400 italic text-center">
                                Tidak ada material yang disiapkan di jadwal penanganan jalur ini.
                            </div>

                            <div v-else class="space-y-2">
                                <div 
                                    v-for="(m, idx) in reportForm.materials" 
                                    :key="idx" 
                                    class="p-3 rounded-2xl bg-white border transition-all shadow-sm"
                                    :class="m.quantity_used == m.quantity_prepared ? 'border-emerald-300 bg-emerald-50/20' : (m.quantity_used == 0 ? 'border-amber-300 bg-amber-50/20' : 'border-blue-300 bg-blue-50/20')"
                                >
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <h4 class="font-bold text-gray-900 text-sm truncate">{{ m.name }}</h4>
                                                <span 
                                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                                    :class="m.quantity_used == m.quantity_prepared ? 'bg-emerald-100 text-emerald-800' : (m.quantity_used == 0 ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800')"
                                                >
                                                    {{ m.quantity_used == m.quantity_prepared ? '✓ Habis Terpakai' : (m.quantity_used == 0 ? '↩️ Retur Penuh' : '↩️ Sebagian Sisa') }}
                                                </span>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-0.5">
                                                Disiapkan: <strong class="text-gray-800">{{ m.quantity_prepared }} {{ m.unit }}</strong>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-3 shrink-0">
                                            <!-- Control Terpakai Stepper -->
                                            <div class="flex flex-col items-center">
                                                <span class="text-[10px] font-bold text-gray-500 uppercase mb-0.5">Terpakai:</span>
                                                <div class="flex items-center border border-gray-300 rounded-xl overflow-hidden bg-white shadow-sm">
                                                    <button 
                                                        type="button" 
                                                        @click="stepPreparedMaterial(m, -1)" 
                                                        class="px-2.5 py-1 text-gray-600 hover:bg-gray-100 font-bold text-sm select-none"
                                                    >
                                                        -
                                                    </button>
                                                    <input 
                                                        type="number" 
                                                        step="0.01" 
                                                        min="0" 
                                                        :max="m.quantity_prepared"
                                                        v-model="m.quantity_used" 
                                                        @input="calculateReturned(m)"
                                                        class="w-16 border-0 text-xs py-1 text-center font-black text-emerald-700 focus:ring-0 p-0" 
                                                        required
                                                    />
                                                    <button 
                                                        type="button" 
                                                        @click="stepPreparedMaterial(m, 1)" 
                                                        class="px-2.5 py-1 text-gray-600 hover:bg-gray-100 font-bold text-sm select-none"
                                                    >
                                                        +
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Tombol Cepat (All vs 0) -->
                                            <div class="flex flex-col gap-1">
                                                <button 
                                                    type="button" 
                                                    @click="setPreparedMaterialQty(m, m.quantity_prepared)"
                                                    class="px-2 py-0.5 text-[10px] font-bold rounded bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition-colors"
                                                    title="Set terpakai semua"
                                                >
                                                    Semua
                                                </button>
                                                <button 
                                                    type="button" 
                                                    @click="setPreparedMaterialQty(m, 0)"
                                                    class="px-2 py-0.5 text-[10px] font-bold rounded bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-200 transition-colors"
                                                    title="Set tidak terpakai (retur semua)"
                                                >
                                                    Nol
                                                </button>
                                            </div>

                                            <!-- Sisa Gudang (Dikembalikan) -->
                                            <div class="flex flex-col items-center">
                                                <span class="text-[10px] font-bold text-blue-700 uppercase mb-0.5">Sisa Kembali:</span>
                                                <div class="w-20 py-1 text-center font-black text-xs text-blue-800 bg-blue-50/90 rounded-xl border border-blue-200">
                                                    {{ m.quantity_returned }} {{ m.unit }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Banner Rincian Pengembalian Kelebihan Material (Retur Gudang) -->
                        <div v-if="returnedMaterialsList.length > 0" class="p-3.5 rounded-2xl bg-blue-50/90 border border-blue-200 text-xs text-blue-950 space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="font-bold flex items-center gap-1.5 text-blue-900">
                                    <span class="text-base">↩️</span>
                                    <span>Alur Pengembalian Kelebihan Material (Retur ke Gudang)</span>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-200 text-blue-900 border border-blue-300">
                                    {{ returnedMaterialsList.length }} Item Harus Dikembalikan
                                </span>
                            </div>
                            <p class="text-[11px] text-blue-800 leading-relaxed">
                                Sisa material yang tidak habis terpakai wajib diserahkan kembali ke gudang/kantor. Saat laporan disimpan, sistem otomatis membuat transaksi retur masuk (<b>IN-PGJ-RET-...</b>) dan <b>menambah kembali stok gudang</b>:
                            </p>
                            <div class="space-y-1.5">
                                <div v-for="ret in returnedMaterialsList" :key="'ret-' + ret.pgj_material_id" class="p-2.5 rounded-xl bg-white border border-blue-200 flex items-center justify-between shadow-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-gray-900">{{ ret.name }}</span>
                                        <span class="text-[11px] text-gray-500">(Bawa: {{ ret.quantity_prepared }} {{ ret.unit }} | Terpakai: {{ ret.quantity_used }} {{ ret.unit }})</span>
                                    </div>
                                    <span class="font-mono font-black text-xs text-blue-800 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-200">
                                        ↩️ Sisa Kembali: {{ ret.quantity_returned }} {{ ret.unit }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Catatan Ringkas Gudang -->
                        <div class="p-2.5 rounded-xl bg-white border border-gray-200 text-[11px] text-gray-500 flex items-center justify-between flex-wrap gap-2">
                            <span>* Sisa material/kabel FO disiapkan akan otomatis dikembalikan ke stok gudang saat laporan disimpan.</span>
                            <div class="flex items-center gap-2 font-bold text-gray-700">
                                <span>Terpakai: {{ countUsedMaterials }} item</span> · 
                                <span class="text-blue-700">Kembali: {{ countReturnedMaterials }} item</span>
                            </div>
                        </div>
                    </div>

                    <!-- ════════ SECTION 5: TITIK KOORDINAT INSIDEN LAPANGAN (BISA > 1) ════════ -->
                    <div class="p-4 rounded-2xl bg-emerald-50/40 border border-emerald-200/80 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <label class="block text-xs font-black text-emerald-950 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>📍</span> 3. Titik Koordinat Insiden & Penanganan Lapangan (Bisa > 1)
                                </label>
                                <p class="text-[11px] text-emerald-800">
                                    Catat titik kabel putus, letak closure jointing baru, tiang belokan, atau ODC/ODP untuk navigasi & pemetaan akurat.
                                </p>
                            </div>
                            <button 
                                type="button" 
                                @click="addCoordinatePoint" 
                                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5 shrink-0 self-start sm:self-auto"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>+ Tambah Titik</span>
                            </button>
                        </div>

                        <!-- Empty state -->
                        <div v-if="reportForm.incident_coordinates.length === 0" class="p-4 bg-white rounded-xl border border-dashed border-emerald-200 text-center text-xs text-gray-500 space-y-1">
                            <div>Belum ada titik koordinat yang dicatat.</div>
                            <button type="button" @click="addCoordinatePoint" class="text-xs font-bold text-emerald-700 hover:underline">
                                + Klik di sini untuk menambah titik koordinat lapangan
                            </button>
                        </div>

                        <!-- Coordinate Points List -->
                        <div v-else class="space-y-2.5">
                            <div 
                                v-for="(coord, idx) in reportForm.incident_coordinates" 
                                :key="idx" 
                                class="p-3.5 rounded-2xl bg-white border border-gray-200 shadow-xs space-y-2.5 transition-all hover:border-emerald-300"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 flex-1">
                                        <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 font-bold text-[11px] shrink-0">
                                            Titik #{{ idx + 1 }}
                                        </span>
                                        <input 
                                            type="text" 
                                            v-model="coord.name" 
                                            placeholder="Label Titik (Contoh: Titik Putus Tiang 12 / Closure Dome Baru)"
                                            class="flex-1 rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-200 text-xs font-semibold text-gray-800 py-1.5 px-2.5"
                                        />
                                    </div>
                                    <button 
                                        type="button" 
                                        @click="removeCoordinatePoint(idx)" 
                                        class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors shrink-0"
                                        title="Hapus titik koordinat ini"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>

                                <!-- Latitude, Longitude, and GPS/Maps Buttons -->
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
                                    <div class="sm:col-span-4">
                                        <div class="relative">
                                            <span class="absolute left-2.5 top-2 text-[10px] font-bold text-gray-400 uppercase">Lat:</span>
                                            <input 
                                                type="text" 
                                                v-model="coord.latitude" 
                                                placeholder="-6.2088..." 
                                                class="w-full pl-9 pr-2 py-1.5 rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-200 text-xs font-mono font-semibold text-gray-800"
                                            />
                                        </div>
                                    </div>

                                    <div class="sm:col-span-4">
                                        <div class="relative">
                                            <span class="absolute left-2.5 top-2 text-[10px] font-bold text-gray-400 uppercase">Lng:</span>
                                            <input 
                                                type="text" 
                                                v-model="coord.longitude" 
                                                placeholder="106.8456..." 
                                                class="w-full pl-9 pr-2 py-1.5 rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-200 text-xs font-mono font-semibold text-gray-800"
                                            />
                                        </div>
                                    </div>

                                    <div class="sm:col-span-4 flex items-center gap-1.5">
                                        <!-- GPS Button -->
                                        <button 
                                            type="button" 
                                            @click="getGpsForCoordinate(idx)" 
                                            :disabled="gpsLoadingIndex === idx"
                                            class="flex-1 px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1 disabled:opacity-50"
                                            title="Ambil koordinat GPS perangkat saat ini"
                                        >
                                            <svg v-if="gpsLoadingIndex === idx" class="w-3.5 h-3.5 animate-spin text-emerald-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <svg v-else class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            <span>{{ gpsLoadingIndex === idx ? 'Mencari...' : 'GPS Saya' }}</span>
                                        </button>

                                        <!-- Google Maps Button -->
                                        <button 
                                            type="button" 
                                            @click="openGoogleMaps(coord.latitude, coord.longitude)" 
                                            :disabled="!coord.latitude || !coord.longitude"
                                            class="flex-1 px-2.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1 disabled:opacity-40 disabled:cursor-not-allowed"
                                            title="Buka titik ini di Google Maps"
                                        >
                                            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                            <span>Maps</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Notes per point -->
                                <div>
                                    <input 
                                        type="text" 
                                        v-model="coord.notes" 
                                        placeholder="Keterangan titik (Opsional, misal: tiang no 12 dekat persimpangan jalan)"
                                        class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-200 text-[11px] text-gray-600 py-1 px-2.5"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ════════ SECTION 6: DOKUMENTASI FOTO PENANGANAN LAPANGAN (BISA > 1) ════════ -->
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <label class="block text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>📸</span> 4. Dokumentasi Foto Penanganan Lapangan (Bisa > 1)
                                </label>
                                <p class="text-[11px] text-gray-500">
                                    Unggah bukti kerusakan kabel, proses penyambungan core, closure dome, hasil ukur OPM, & kerapihan jalur.
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-600/20 transition-all flex items-center gap-1.5 cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span>+ Ambil / Tambah Foto Lapangan</span>
                                    <input 
                                        type="file" 
                                        accept="image/*" 
                                        multiple 
                                        @change="onMultiPhotosSelected" 
                                        class="hidden" 
                                    />
                                </label>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="handlingPhotosList.length === 0" class="p-6 bg-white rounded-2xl border-2 border-dashed border-gray-200 text-center space-y-2">
                            <svg class="w-10 h-10 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <div class="text-xs font-semibold text-gray-600">Belum ada foto dokumentasi penanganan</div>
                            <p class="text-[11px] text-gray-400">Gunakan tombol di atas untuk mengambil foto langsung lewat kamera atau memilih galeri perangkat.</p>
                        </div>

                        <!-- Photo Cards Grid -->
                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div 
                                v-for="(photo, pIdx) in handlingPhotosList" 
                                :key="photo.id" 
                                class="p-2.5 rounded-2xl bg-white border border-gray-200 shadow-xs flex flex-col justify-between group hover:border-emerald-300 transition-all space-y-2"
                            >
                                <div class="relative overflow-hidden rounded-xl bg-gray-100 aspect-video flex items-center justify-center">
                                    <img 
                                        :src="photo.preview" 
                                        alt="Foto Penanganan" 
                                        class="w-full h-full object-cover cursor-pointer group-hover:scale-105 transition-transform duration-200"
                                        @click="openPhotoZoom(photo.preview)"
                                    />
                                    <!-- Status Badges -->
                                    <div class="absolute top-1.5 left-1.5 flex gap-1">
                                        <span 
                                            class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase backdrop-blur-md shadow-xs"
                                            :class="photo.is_existing ? 'bg-black/60 text-white' : 'bg-emerald-600/90 text-white'"
                                        >
                                            {{ photo.is_existing ? 'Tersimpan' : 'Foto Baru' }}
                                        </span>
                                    </div>
                                    <!-- Zoom Overlay Button -->
                                    <button 
                                        type="button" 
                                        @click="openPhotoZoom(photo.preview)" 
                                        class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white"
                                        title="Klik untuk memperbesar foto"
                                    >
                                        <svg class="w-6 h-6 drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                                    </button>
                                </div>

                                <!-- Caption Input & Quick Suggestions -->
                                <div class="space-y-1.5">
                                    <input 
                                        type="text" 
                                        v-model="photo.caption" 
                                        placeholder="Keterangan foto (Contoh: Splicing Tube Biru)" 
                                        class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-200 text-xs font-semibold text-gray-800 py-1 px-2"
                                    />
                                    <!-- Fast Suggestion Chips -->
                                    <div class="flex flex-wrap gap-1">
                                        <button 
                                            type="button" 
                                            v-for="tag in ['Kabel Putus', 'Splicing Core', 'Closure Dome', 'Hasil Ukur OPM', 'Kerapihan Tiang']" 
                                            :key="tag" 
                                            @click="photo.caption = (photo.caption ? photo.caption + ' - ' : '') + tag" 
                                            class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-gray-100 hover:bg-emerald-50 hover:text-emerald-700 text-gray-600 border border-gray-200 transition-colors"
                                        >
                                            + {{ tag }}
                                        </button>
                                    </div>
                                </div>

                                <div class="pt-1.5 border-t border-gray-100 flex items-center justify-between">
                                    <span class="text-[10px] text-gray-400 truncate">{{ photo.uploaded_at || 'Siap disimpan' }}</span>
                                    <button 
                                        type="button" 
                                        @click="removeHandlingPhoto(pIdx)" 
                                        class="text-[11px] font-bold text-rose-600 hover:text-rose-800 hover:underline flex items-center gap-1"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        <span>Hapus</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ════════ SECTION 6: CATATAN AKHIR RESTORASI ════════ -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Catatan Akhir Restorasi & Hasil Uji Coba</label>
                        <textarea 
                            v-model="reportForm.resolution_notes" 
                            rows="2" 
                            placeholder="Contoh: Splicing 12 core selesai dengan loss rata-rata 0.04 dB. Seluruh ONT pelanggan di ODC telah online kembali dan traffic berjalan normal."
                            class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm" 
                        ></textarea>
                    </div>

                    <!-- ════════ SECTION 7: JAM SELESAI RESTORASI DI BAWAH ════════ -->
                    <div class="p-4 rounded-2xl bg-gradient-to-r from-blue-50/70 via-indigo-50/60 to-blue-50/70 border border-blue-200 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <label class="block text-xs font-black text-blue-950 uppercase tracking-wider">
                                    🏁 2. Jam Selesai Restorasi Lapangan
                                </label>
                                <p class="text-[11px] text-blue-800">Tekan tombol di bawah saat pengerjaan lapangan telah selesai tuntas.</p>
                            </div>
                            <div v-if="reportForm.end_time" class="px-3 py-1 rounded-full bg-blue-100 text-blue-800 text-xs font-bold flex items-center gap-1.5 self-start sm:self-auto">
                                <span>✓ Selesai: {{ reportForm.end_time }} WIB</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center pt-1">
                            <div>
                                <div class="flex items-center gap-2">
                                    <input 
                                        type="time" 
                                        v-model="reportForm.end_time" 
                                        class="flex-1 rounded-xl border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-sm font-semibold text-gray-800 bg-white" 
                                        required
                                    />
                                    <button 
                                        type="button" 
                                        @click="finishWork" 
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-1.5 shrink-0"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>{{ reportForm.end_time ? 'Update Jam Selesai' : 'Selesai Sekarang' }}</span>
                                    </button>
                                </div>
                            </div>
                            <!-- Durasi Text Display -->
                            <div class="p-2.5 rounded-xl bg-white border border-blue-100 shadow-sm flex items-center justify-between">
                                <span class="text-xs text-gray-500 font-medium">Total Waktu Pengerjaan:</span>
                                <span class="text-sm font-black text-blue-900">{{ calculatedDurationText }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="pt-4 border-t flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                        <div>
                            <button 
                                v-if="hasSavedDraft" 
                                type="button" 
                                @click="clearDraft" 
                                class="text-xs text-gray-400 hover:text-rose-600 transition-colors font-medium underline"
                            >
                                🗑️ Hapus Draft & Muat Ulang
                            </button>
                        </div>
                        <div class="flex items-center justify-end gap-2">
                            <button 
                                type="button" 
                                @click="closeReportModal" 
                                class="px-5 py-2.5 border border-gray-200 rounded-xl text-gray-700 text-sm font-semibold hover:bg-gray-50 transition-colors"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                :disabled="reportForm.processing || !reportForm.action_taken" 
                                class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-black shadow-lg shadow-emerald-600/20 disabled:opacity-50 transition-all flex items-center gap-2"
                            >
                                <svg v-if="reportForm.processing" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ reportForm.processing ? 'Menyimpan...' : 'Simpan Restorasi & Nyatakan Pulih' }}</span>
                            </button>
                        </div>
                    </div>
                </form>
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
import { ref, computed, watch, onUnmounted } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermission } from '@/Composables/usePermission';

const { can } = usePermission();

const props = defineProps({
    tickets: Object,
    areas: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const filterArea = ref(props.filters?.area_id || '');

const showReportModal = ref(false);
const selectedTicket = ref(null);
const hasSavedDraft = ref(false);

// Timer & Stopwatch State
const startedAt = ref(null);
const elapsedSeconds = ref(0);
const isWorking = ref(false);
let timerInterval = null;

// Handling Photos List & Lightbox
const handlingPhotosList = ref([]);
const previewModalImage = ref(null);

// GPS Loading Index
const gpsLoadingIndex = ref(null);

// Backward compatibility photo previews
const photoBeforePreview = ref(null);
const photoAfterPreview = ref(null);

const reportForm = useForm({
    action_taken: '',
    core_details: '',
    optical_loss_before: '',
    optical_loss_after: '',
    start_time: '',
    end_time: '',
    resolution_notes: '',
    photo_before: null,
    photo_after: null,
    incident_coordinates: [],
    materials: [],
});

const performSearch = () => {
    router.get('/pgj/laporan', {
        search: search.value,
        area_id: filterArea.value,
    }, { preserveState: true, replace: true });
};

// ── Live Timer Logic ───────────────────────────────────────────
const startLiveTimer = () => {
    stopLiveTimer();
    updateElapsed();
    timerInterval = setInterval(() => {
        updateElapsed();
    }, 1000);
};

const stopLiveTimer = () => {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
};

const updateElapsed = () => {
    if (startedAt.value) {
        const diff = Math.floor((Date.now() - startedAt.value) / 1000);
        elapsedSeconds.value = Math.max(0, diff);
    }
};

const formatSecondsToClock = (totalSec) => {
    if (!totalSec || totalSec < 0) totalSec = 0;
    const hours = Math.floor(totalSec / 3600);
    const minutes = Math.floor((totalSec % 3600) / 60);
    const seconds = totalSec % 60;
    const pad = (n) => String(n).padStart(2, '0');
    return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
};

const calculatedDurationText = computed(() => {
    if (!reportForm.start_time) return 'Belum dimulai';
    if (!reportForm.end_time) {
        if (isWorking.value) {
            return `${formatSecondsToClock(elapsedSeconds.value)} (Sedang berjalan)`;
        }
        return 'Sedang berjalan';
    }

    try {
        const [sh, sm] = reportForm.start_time.split(':').map(Number);
        const [eh, em] = reportForm.end_time.split(':').map(Number);
        let diffMinutes = (eh * 60 + em) - (sh * 60 + sm);
        if (diffMinutes < 0) diffMinutes += 24 * 60; // Cross midnight
        const hours = Math.floor(diffMinutes / 60);
        const mins = diffMinutes % 60;
        if (hours > 0) {
            return `${hours} Jam ${mins} Menit`;
        }
        return `${mins} Menit`;
    } catch (e) {
        return '-';
    }
});

const startWork = () => {
    const now = new Date();
    const hrs = String(now.getHours()).padStart(2, '0');
    const mins = String(now.getMinutes()).padStart(2, '0');
    reportForm.start_time = `${hrs}:${mins}`;
    reportForm.end_time = '';
    
    startedAt.value = Date.now();
    isWorking.value = true;
    startLiveTimer();
    saveDraft();
};

const finishWork = () => {
    const now = new Date();
    const hrs = String(now.getHours()).padStart(2, '0');
    const mins = String(now.getMinutes()).padStart(2, '0');
    reportForm.end_time = `${hrs}:${mins}`;
    
    isWorking.value = false;
    stopLiveTimer();
    saveDraft();
};

// ── Multi-Coordinates Helpers ──────────────────────────────────
const addCoordinatePoint = () => {
    reportForm.incident_coordinates.push({
        name: `Titik Lapangan ${reportForm.incident_coordinates.length + 1}`,
        latitude: '',
        longitude: '',
        notes: '',
    });
    saveDraft();
};

const removeCoordinatePoint = (index) => {
    reportForm.incident_coordinates.splice(index, 1);
    saveDraft();
};

const getGpsForCoordinate = (index) => {
    if (!navigator.geolocation) {
        alert('Fitur Geolocation tidak didukung di browser ini.');
        return;
    }
    gpsLoadingIndex.value = index;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            gpsLoadingIndex.value = null;
            reportForm.incident_coordinates[index].latitude = parseFloat(position.coords.latitude.toFixed(7));
            reportForm.incident_coordinates[index].longitude = parseFloat(position.coords.longitude.toFixed(7));
            saveDraft();
        },
        (error) => {
            gpsLoadingIndex.value = null;
            alert('Gagal mendeteksi lokasi GPS: ' + error.message);
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
};

const openGoogleMaps = (lat, lng) => {
    if (!lat || !lng) return;
    window.open(`https://www.google.com/maps?q=${lat},${lng}`, '_blank');
};

// ── Multi-Photos Helpers ───────────────────────────────────────
const onMultiPhotosSelected = (e) => {
    const files = Array.from(e.target.files || []);
    if (!files.length) return;
    files.forEach(file => {
        const preview = URL.createObjectURL(file);
        handlingPhotosList.value.push({
            id: 'temp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
            file: file,
            preview: preview,
            caption: '',
            is_existing: false,
            uploaded_at: 'Foto baru',
        });
    });
    e.target.value = '';
    saveDraft();
};

const removeHandlingPhoto = (index) => {
    handlingPhotosList.value.splice(index, 1);
    saveDraft();
};

const openPhotoZoom = (imgUrl) => {
    previewModalImage.value = imgUrl;
};

// ── Draft Persistence (localStorage) ───────────────────────────
const getDraftKey = (ticketId) => `pgj_report_draft_${ticketId}`;

const saveDraft = () => {
    if (!selectedTicket.value) return;
    try {
        const draft = {
            ticket_id: selectedTicket.value.id,
            action_taken: reportForm.action_taken,
            core_details: reportForm.core_details,
            optical_loss_before: reportForm.optical_loss_before,
            optical_loss_after: reportForm.optical_loss_after,
            start_time: reportForm.start_time,
            end_time: reportForm.end_time,
            resolution_notes: reportForm.resolution_notes,
            incident_coordinates: reportForm.incident_coordinates,
            handling_photos: handlingPhotosList.value.map(p => ({
                id: p.id,
                caption: p.caption,
                is_existing: p.is_existing,
                path: p.path,
                preview: p.preview,
                uploaded_at: p.uploaded_at,
            })),
            started_at: startedAt.value,
            is_working: isWorking.value,
            materials: reportForm.materials.map(m => ({
                pgj_material_id: m.pgj_material_id,
                material_id: m.material_id,
                name: m.name,
                unit: m.unit,
                quantity_prepared: m.quantity_prepared,
                quantity_used: m.quantity_used,
                quantity_returned: m.quantity_returned,
            })),
            saved_at: new Date().toISOString(),
        };
        localStorage.setItem(getDraftKey(selectedTicket.value.id), JSON.stringify(draft));
        hasSavedDraft.value = true;
    } catch (e) {
        console.error('Gagal menyimpan draft:', e);
    }
};

const clearDraft = () => {
    if (!selectedTicket.value) return;
    if (confirm('Apakah Anda yakin ingin membuang draft tersimpan dan memuat ulang data awal tiket?')) {
        localStorage.removeItem(getDraftKey(selectedTicket.value.id));
        hasSavedDraft.value = false;
        openReportModal(selectedTicket.value, true);
    }
};

// ── Modal Actions & Initialization ──────────────────────────────
const openReportModal = (ticket, ignoreDraft = false) => {
    selectedTicket.value = ticket;
    stopLiveTimer();
    reportForm.reset();
    hasSavedDraft.value = false;
    photoBeforePreview.value = ticket.photo_before ? `/storage/${ticket.photo_before}` : null;
    photoAfterPreview.value = ticket.photo_after ? `/storage/${ticket.photo_after}` : null;

    const draftKey = getDraftKey(ticket.id);
    const savedDraftRaw = !ignoreDraft ? localStorage.getItem(draftKey) : null;
    let draftLoaded = false;

    if (savedDraftRaw) {
        try {
            const draft = JSON.parse(savedDraftRaw);
            reportForm.action_taken = draft.action_taken || '';
            reportForm.core_details = draft.core_details || '';
            reportForm.optical_loss_before = draft.optical_loss_before || '';
            reportForm.optical_loss_after = draft.optical_loss_after || '';
            reportForm.start_time = draft.start_time || '';
            reportForm.end_time = draft.end_time || '';
            reportForm.resolution_notes = draft.resolution_notes || '';

            if (Array.isArray(draft.incident_coordinates)) {
                reportForm.incident_coordinates = draft.incident_coordinates;
            } else {
                initCoordinates(ticket);
            }

            if (Array.isArray(draft.handling_photos)) {
                handlingPhotosList.value = draft.handling_photos;
            } else {
                initHandlingPhotos(ticket);
            }

            if (Array.isArray(draft.materials) && draft.materials.length > 0) {
                reportForm.materials = draft.materials;
            } else {
                initPreparedMaterials(ticket);
            }

            if (draft.started_at && !draft.end_time) {
                startedAt.value = draft.started_at;
                isWorking.value = true;
                const elapsed = Math.floor((Date.now() - draft.started_at) / 1000);
                elapsedSeconds.value = Math.max(0, elapsed);
                startLiveTimer();
            } else if (draft.started_at && draft.end_time) {
                startedAt.value = draft.started_at;
                isWorking.value = false;
            }

            hasSavedDraft.value = true;
            draftLoaded = true;
        } catch (e) {
            console.error('Error loading draft:', e);
        }
    }

    if (!draftLoaded) {
        reportForm.action_taken = ticket.action_taken || '';
        reportForm.core_details = ticket.core_details || '';
        reportForm.optical_loss_before = ticket.optical_loss_before || '';
        reportForm.optical_loss_after = ticket.optical_loss_after || '';
        reportForm.start_time = ticket.start_time || '';
        reportForm.end_time = ticket.end_time || '';
        reportForm.resolution_notes = ticket.resolution_notes || '';

        initCoordinates(ticket);
        initHandlingPhotos(ticket);
        initPreparedMaterials(ticket);

        if (ticket.start_time && !ticket.end_time) {
            isWorking.value = true;
            const [h, m] = ticket.start_time.split(':').map(Number);
            const d = new Date();
            d.setHours(h, m, 0, 0);
            startedAt.value = d.getTime();
            elapsedSeconds.value = Math.max(0, Math.floor((Date.now() - startedAt.value) / 1000));
            startLiveTimer();
        } else {
            isWorking.value = false;
            startedAt.value = null;
            elapsedSeconds.value = 0;
        }
    }

    showReportModal.value = true;
};

const closeReportModal = () => {
    stopLiveTimer();
    showReportModal.value = false;
    selectedTicket.value = null;
    handlingPhotosList.value = [];
    previewModalImage.value = null;
};

const initCoordinates = (ticket) => {
    if (Array.isArray(ticket.incident_coordinates) && ticket.incident_coordinates.length > 0) {
        reportForm.incident_coordinates = JSON.parse(JSON.stringify(ticket.incident_coordinates));
    } else if (ticket.latitude || ticket.longitude) {
        reportForm.incident_coordinates = [
            {
                name: ticket.location_name || 'Titik Insiden Lapangan',
                latitude: ticket.latitude || '',
                longitude: ticket.longitude || '',
                notes: 'Koordinat lokasi tiket',
            }
        ];
    } else {
        reportForm.incident_coordinates = [
            {
                name: 'Titik Putus / Kerusakan Fisik',
                latitude: '',
                longitude: '',
                notes: '',
            }
        ];
    }
};

const initHandlingPhotos = (ticket) => {
    handlingPhotosList.value = [];
    if (Array.isArray(ticket.handling_photos) && ticket.handling_photos.length > 0) {
        ticket.handling_photos.forEach((hp, idx) => {
            handlingPhotosList.value.push({
                id: 'existing_' + idx,
                file: null,
                preview: `/storage/${hp.path}`,
                caption: hp.caption || '',
                is_existing: true,
                path: hp.path,
                uploaded_at: hp.uploaded_at || '',
            });
        });
    } else {
        if (ticket.photo_before) {
            handlingPhotosList.value.push({
                id: 'existing_before',
                file: null,
                preview: `/storage/${ticket.photo_before}`,
                caption: 'Foto Bukti Kerusakan Fisik (Sebelum)',
                is_existing: true,
                path: ticket.photo_before,
                uploaded_at: '',
            });
        }
        if (ticket.photo_after) {
            handlingPhotosList.value.push({
                id: 'existing_after',
                file: null,
                preview: `/storage/${ticket.photo_after}`,
                caption: 'Foto Hasil Restorasi / Splicing (Sesudah)',
                is_existing: true,
                path: ticket.photo_after,
                uploaded_at: '',
            });
        }
    }
};

const initPreparedMaterials = (ticket) => {
    if (ticket.materials && ticket.materials.length > 0) {
        reportForm.materials = ticket.materials.map(m => {
            const prep = parseFloat(m.quantity_prepared) || 0;
            const used = m.quantity_used !== null ? parseFloat(m.quantity_used) : prep;
            return {
                pgj_material_id: m.id,
                material_id: m.material_id,
                name: m.material?.name || 'Material FO',
                unit: m.unit || m.material?.unit || '',
                quantity_prepared: prep,
                quantity_used: used,
                quantity_returned: Math.max(0, parseFloat((prep - used).toFixed(2))),
            };
        });
    } else {
        reportForm.materials = [];
    }
};

// ── Material Reconciliation Helpers ────────────────────────────
const calculateReturned = (item) => {
    const prep = parseFloat(item.quantity_prepared) || 0;
    let used = parseFloat(item.quantity_used);
    if (isNaN(used) || used < 0) used = 0;
    if (used > prep) used = prep;
    item.quantity_used = used;
    item.quantity_returned = Math.max(0, parseFloat((prep - used).toFixed(2)));
    saveDraft();
};

const stepPreparedMaterial = (item, delta) => {
    let current = parseFloat(item.quantity_used) || 0;
    current = Math.max(0, Math.min(parseFloat(item.quantity_prepared) || 0, current + delta));
    item.quantity_used = current;
    calculateReturned(item);
};

const setPreparedMaterialQty = (item, qty) => {
    item.quantity_used = qty;
    calculateReturned(item);
};

const countUsedMaterials = computed(() => {
    return reportForm.materials.filter(m => parseFloat(m.quantity_used) > 0).length;
});

const countReturnedMaterials = computed(() => {
    return reportForm.materials.filter(m => parseFloat(m.quantity_returned) > 0).length;
});

const returnedMaterialsList = computed(() => {
    return reportForm.materials.filter(m => parseFloat(m.quantity_returned) > 0);
});

// Photo Change Handlers (Legacy)
const onPhotoBeforeChange = (e) => {
    const file = e.target.files[0] || null;
    reportForm.photo_before = file;
    if (file) {
        photoBeforePreview.value = URL.createObjectURL(file);
    }
};

const onPhotoAfterChange = (e) => {
    const file = e.target.files[0] || null;
    reportForm.photo_after = file;
    if (file) {
        photoAfterPreview.value = URL.createObjectURL(file);
    }
};

// ── Submit Report ──────────────────────────────────────────────
const submitReport = () => {
    if (!selectedTicket.value) return;

    // Filter valid coordinate points
    const validCoords = (reportForm.incident_coordinates || []).filter(c => c.latitude || c.longitude || c.name);

    // Filter existing photos vs new files
    const existingPhotos = handlingPhotosList.value
        .filter(p => p.is_existing)
        .map(p => ({
            path: p.path,
            caption: p.caption,
            uploaded_at: p.uploaded_at,
        }));

    const newPhotoFiles = handlingPhotosList.value.filter(p => !p.is_existing && p.file).map(p => p.file);
    const newPhotoCaptions = handlingPhotosList.value.filter(p => !p.is_existing && p.file).map(p => p.caption || 'Dokumentasi Penanganan Lapangan');

    reportForm.transform((data) => ({
        ...data,
        incident_coordinates: validCoords,
        existing_handling_photos: JSON.stringify(existingPhotos),
        handling_photos_files: newPhotoFiles,
        handling_photos_captions: newPhotoCaptions,
    })).post(`/pgj/${selectedTicket.value.id}/report`, {
        forceFormData: true,
        onSuccess: () => {
            if (selectedTicket.value) {
                localStorage.removeItem(getDraftKey(selectedTicket.value.id));
            }
            stopLiveTimer();
            showReportModal.value = false;
            selectedTicket.value = null;
            handlingPhotosList.value = [];
            previewModalImage.value = null;
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

// Deep Watcher to Auto-Save to localStorage
watch([
    () => reportForm.action_taken,
    () => reportForm.core_details,
    () => reportForm.optical_loss_before,
    () => reportForm.optical_loss_after,
    () => reportForm.start_time,
    () => reportForm.end_time,
    () => reportForm.resolution_notes,
    () => reportForm.materials,
    () => reportForm.incident_coordinates,
    () => handlingPhotosList.value,
], () => {
    if (showReportModal.value && selectedTicket.value) {
        saveDraft();
    }
}, { deep: true });

onUnmounted(() => {
    stopLiveTimer();
});
</script>
