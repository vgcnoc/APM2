<template>
    <AppLayout title="Laporan Penanganan PGS" subtitle="Pelaporan Hasil Pengerjaan Lapangan & Material Terpakai oleh Teknisi">
        <div class="space-y-6">
            <!-- Toolbar & Filter -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4">
                <div v-if="can('pgs_filter')" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
                    <div class="relative flex-1 sm:w-80">
                        <input 
                            type="text" 
                            v-model="search" 
                            placeholder="Cari No. PGS, pelanggan, tindakan..." 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm transition-all"
                            @keyup.enter="performSearch"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <select v-model="filterArea" @change="performSearch" class="px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm bg-gray-50/50">
                        <option value="">Semua Area / Wilayah</option>
                        <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>
                </div>
                
                <div class="text-xs text-gray-500 font-medium">
                    Halaman ini digunakan teknisi untuk melaporkan hasil tindakan, redaman optik, foto, dan material terpakai.
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
                                <th class="p-4">No. Tiket & Pelanggan</th>
                                <th class="p-4">Instruksi & Material Disiapkan</th>
                                <th class="p-4">Status & Waktu Pengerjaan</th>
                                <th class="p-4">Hasil Tindakan & Redaman</th>
                                <th class="p-4 text-center">Aksi Teknisi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-emerald-50/20 transition-colors group">
                                <!-- 1. No Tiket & Pelanggan -->
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-emerald-600 text-xs">{{ ticket.ticket_number }}</span>
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
                                    <div class="text-[10px] text-emerald-700 font-semibold mt-0.5">
                                        ODP: {{ ticket.customer?.ont?.odp?.name || '-' }} · Area: {{ ticket.area?.name || ticket.customer?.area_model?.name || '-' }}
                                    </div>
                                    <!-- Badge Multi-Tikor -->
                                    <div v-if="ticket.incident_coordinates && ticket.incident_coordinates.length > 0" class="mt-1 flex items-center gap-1.5 flex-wrap">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 flex items-center gap-1">
                                            <span>📍</span> {{ ticket.incident_coordinates.length }} Tikor Insiden
                                        </span>
                                        <a 
                                            v-if="ticket.incident_coordinates[0]?.latitude && ticket.incident_coordinates[0]?.longitude"
                                            :href="`https://www.google.com/maps?q=${ticket.incident_coordinates[0].latitude},${ticket.incident_coordinates[0].longitude}`"
                                            target="_blank"
                                            class="text-[10px] text-purple-600 hover:text-purple-800 font-semibold underline flex items-center gap-0.5"
                                            title="Buka titik koordinat pertama di Google Maps"
                                        >
                                            <span>🗺️ Peta</span>
                                        </a>
                                    </div>
                                    <!-- Badge Status Pergantian ONT -->
                                    <div v-if="ticket.ont_replacement_status && ticket.ont_replacement_status !== 'none'" class="mt-1.5 flex items-center gap-1.5 flex-wrap">
                                        <span v-if="ticket.ont_replacement_status === 'requested'" class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300 animate-pulse">
                                            🔄 Req Ganti ONT
                                        </span>
                                        <span v-else-if="ticket.ont_replacement_status === 'prepared'" class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-300">
                                            📦 ONT Disiapkan: {{ ticket.prepared_ont?.serial_number || 'Unit Baru' }}
                                        </span>
                                        <span v-else-if="ticket.ont_replacement_status === 'installed'" class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            ✅ ONT Diganti: {{ ticket.prepared_ont?.serial_number || '-' }}
                                        </span>
                                        <span v-else-if="ticket.ont_replacement_status === 'cancelled'" class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-gray-100 text-gray-600 border border-gray-200">
                                            ↩️ Ganti Batal (Retur Gudang)
                                        </span>
                                    </div>
                                </td>

                                <!-- 2. Instruksi & Material Disiapkan -->
                                <td class="p-4">
                                    <div class="text-xs font-semibold text-gray-800">
                                        Temuan: "{{ ticket.reported_issue }}"
                                    </div>
                                    <div v-if="ticket.lead_notes" class="mt-1.5 p-2 rounded-xl bg-amber-50/80 border border-amber-100 text-xs text-amber-900">
                                        <span class="font-bold text-amber-800">Instruksi Ka. Teknisi:</span> {{ ticket.lead_notes }}
                                    </div>
                                    <!-- Material disiapkan -->
                                    <div v-if="ticket.materials && ticket.materials.length > 0" class="mt-2 text-xs space-y-1">
                                        <div class="text-[10px] text-gray-400 font-bold uppercase">Material Dibawa:</div>
                                        <div v-for="m in ticket.materials" :key="m.id" class="text-[11px] bg-gray-50 px-2 py-0.5 rounded border border-gray-100 flex justify-between">
                                            <span class="text-gray-700 font-medium">{{ m.material?.name }}</span>
                                            <span class="text-gray-900 font-bold">{{ parseFloat(m.quantity_prepared) }} {{ m.unit || m.material?.unit }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- 3. Status & Waktu Pengerjaan -->
                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider" :class="ticket.status === 'completed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-indigo-50 text-indigo-700 border border-indigo-200'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="ticket.status === 'completed' ? 'bg-emerald-500' : 'bg-indigo-500 animate-pulse'"></span>
                                        {{ ticket.status === 'completed' ? 'Selesai Dikerjakan' : 'Siap Ditangani' }}
                                    </span>

                                    <div class="mt-2 text-xs text-gray-600">
                                        <div class="text-[10px] text-gray-400 font-bold uppercase">Jadwal:</div>
                                        <div class="font-semibold text-gray-800">{{ formatDate(ticket.scheduled_date) }} ({{ ticket.scheduled_time || 'Fleksibel' }})</div>
                                    </div>

                                    <div v-if="ticket.start_time || ticket.end_time" class="mt-1 text-xs text-gray-500">
                                        Jam Kerja: {{ ticket.start_time || '-' }} s/d {{ ticket.end_time || '-' }}
                                    </div>
                                </td>

                                <!-- 4. Hasil Tindakan & Redaman -->
                                <td class="p-4">
                                    <div v-if="ticket.status === 'completed'">
                                        <div class="text-xs font-bold text-gray-900">
                                            {{ ticket.action_taken || '-' }}
                                        </div>
                                        <div class="mt-1 text-xs text-gray-600 flex items-center gap-2">
                                            <span>Redaman:</span>
                                            <span class="font-mono text-gray-500">{{ ticket.optical_power_before || '-' }} dBm</span>
                                            <span>&rarr;</span>
                                            <span class="font-mono font-bold text-emerald-600">{{ ticket.optical_power_after || '-' }} dBm</span>
                                        </div>
                                        <div v-if="ticket.resolution_notes" class="mt-1 text-[11px] text-gray-500 italic">
                                            "{{ ticket.resolution_notes }}"
                                        </div>
                                        <div v-if="ticket.materials && ticket.materials.some(m => parseFloat(m.quantity_returned) > 0)" class="mt-1.5 p-1.5 rounded-lg bg-blue-50 border border-blue-200 text-[11px] text-blue-900 flex items-center gap-1">
                                            <span class="font-bold">↩️ Sisa Dikembalikan:</span>
                                            <span>{{ ticket.materials.filter(m => parseFloat(m.quantity_returned) > 0).map(m => '+' + parseFloat(m.quantity_returned) + ' ' + (m.unit || m.material?.unit) + ' ' + m.material?.name).join(', ') }}</span>
                                        </div>
                                        <!-- Badge Material Bekas Bongkaran -->
                                        <div v-if="ticket.dismantled_materials && ticket.dismantled_materials.length > 0" class="mt-1.5 p-1.5 rounded-lg bg-amber-50 border border-amber-200 text-[11px] text-amber-900 flex items-center gap-1">
                                            <span class="font-bold">📦 Serah Terima Bekas:</span>
                                            <span>{{ ticket.dismantled_materials.map(d => d.item_name + ' (' + parseFloat(d.quantity) + ' ' + d.unit + ' - ' + (d.handover_status === 'pending_handover' ? '⏳ Antre Gudang' : '✅ Diterima') + ')').join(', ') }}</span>
                                        </div>
                                    </div>
                                    <div v-else class="text-xs text-gray-400 italic">
                                        Menunggu teknisi melaporkan hasil perbaikan lapangan...
                                    </div>
                                </td>

                                <!-- 5. Aksi Teknisi -->
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button 
                                            v-if="can('pgs_btn_report')"
                                            @click="openReportModal(ticket)" 
                                            class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/20 transition-all flex items-center gap-1.5"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            <span>{{ ticket.status === 'completed' ? 'Edit Laporan' : 'Input Laporan' }}</span>
                                        </button>

                                        <a :href="`/pgs/${ticket.id}/print`" target="_blank" title="Cetak Berita Acara & SPK" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="tickets.data.length === 0">
                                <td colspan="5" class="p-8 text-center text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Tidak ada tiket penanganan sigap yang dijadwalkan saat ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="tickets.links && tickets.links.length > 3" class="p-4 border-t border-gray-100 flex items-center justify-between">
                    <div class="text-xs text-gray-500">
                        Menampilkan {{ tickets.from || 0 }} - {{ tickets.to || 0 }} dari {{ tickets.total || 0 }} tiket
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

        <!-- ════════════ MODAL INPUT LAPORAN PENANGANAN PGS ════════════ -->
        <div v-if="showReportModal" class="fixed inset-0 z-50 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
            <div class="bg-white rounded-3xl max-w-3xl w-full shadow-2xl border border-gray-100 overflow-hidden animate-fade-in-up my-auto flex flex-col max-h-[92vh]">
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 text-white flex justify-between items-center shrink-0">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-black text-lg">Laporan Penanganan Lapangan PGS</h3>
                            <span class="px-2 py-0.5 rounded-md bg-white/20 text-white font-mono text-xs font-bold">#{{ selectedTicket?.ticket_number }}</span>
                        </div>
                        <p class="text-xs text-emerald-100 mt-0.5">Catat tindakan perbaikan, redaman optik, rekonsiliasi material & dokumentasi</p>
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

                    <!-- Ringkasan Keluhan & Pelanggan -->
                    <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-100 text-xs text-emerald-950 space-y-2">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="font-bold text-sm text-gray-900 flex items-center gap-2">
                                <span>{{ selectedTicket?.customer?.name }}</span>
                                <span class="px-2 py-0.5 rounded bg-white text-gray-700 border text-xs font-mono font-normal">{{ selectedTicket?.customer?.customer_code }}</span>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg bg-emerald-100/80 font-bold text-emerald-800 text-[11px]">
                                Area: {{ selectedTicket?.area?.name || selectedTicket?.customer?.area_model?.name || '-' }}
                            </span>
                        </div>
                        <div class="text-gray-700">
                            <strong>Temuan Gangguan:</strong> <span class="text-red-700 font-medium">"{{ selectedTicket?.reported_issue }}"</span>
                        </div>
                        <div v-if="selectedTicket?.customer?.ont?.odp?.name" class="text-gray-600">
                            <strong>ODP:</strong> {{ selectedTicket.customer.ont.odp.name }} · <strong>Alamat:</strong> {{ selectedTicket.customer?.address || '-' }}
                        </div>
                        <div v-if="selectedTicket?.lead_notes" class="p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 font-medium">
                            <span class="font-bold text-amber-800">Instruksi Ka. Teknisi:</span> "{{ selectedTicket?.lead_notes }}"
                        </div>

                        <!-- Quick Workflow Status Badges -->
                        <div class="pt-2 border-t border-emerald-200/60 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-white text-purple-900 border border-purple-200 shadow-2xs">
                                <span>📍</span> {{ incidentCoordinates.length }} Titik Koordinat Insiden
                            </span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-white text-teal-900 border border-teal-200 shadow-2xs">
                                <span>📦</span> {{ activeCustomerMaterials.length }} Material Terpasang di Pelanggan
                            </span>
                            <span v-if="materialReplacements.length > 0" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-300 animate-pulse shadow-2xs">
                                <span>🔄</span> {{ materialReplacements.length }} Item Akan Diganti & Diserahterimakan ke Gudang
                            </span>
                        </div>
                    </div>

                    <!-- ════════ SECTION 1: JAM MULAI PENGERJAAN & LIVE STOPWATCH ════════ -->
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider">
                                    ⏱️ 1. Jam Mulai Pengerjaan Lapangan
                                </label>
                                <p class="text-[11px] text-gray-500">Tekan tombol mulai saat Anda tiba di lokasi pelanggan. Waktu akan terus berjalan meski keluar aplikasi.</p>
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
                                    * Klik tombol "Mulai Pengerjaan" saat penanganan dimulai.
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- ════════ SECTION 2: TINDAKAN PERBAIKAN ════════ -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                            Tindakan Perbaikan Lapangan <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            v-model="reportForm.action_taken" 
                            rows="2" 
                            placeholder="Contoh: Pemotongan kabel dropcore yang tertekuk di tiang ODP, splicing ulang core 1 ODP-BTR-04, dan penggantian adapter SC-UPC."
                            class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm" 
                            required
                        ></textarea>
                    </div>

                    <!-- ════════ SECTION 2B: TITIK KOORDINAT INSIDEN (MULTI-TIKOR) ════════ -->
                    <div class="p-4 rounded-2xl bg-purple-50/60 border border-purple-200/80 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <label class="block text-xs font-black text-purple-950 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>📍</span> Titik Koordinat Insiden / Gangguan (Multi-Tikor)
                                </label>
                                <p class="text-[11px] text-purple-800">
                                    Catat titik lokasi penting saat penanganan (bisa lebih dari 1 titik: Titik Kabel Putus, ODP, Tiang, Sambungan FO, Rumah Pelanggan).
                                </p>
                            </div>
                            <div class="flex items-center gap-2 self-start sm:self-auto">
                                <button 
                                    type="button" 
                                    @click="getCurrentGpsLocation()" 
                                    :disabled="isFetchingGps"
                                    class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                                >
                                    <span v-if="isFetchingGps" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                    <span v-else>📡</span>
                                    <span>{{ isFetchingGps ? 'Mencari GPS...' : '📍 Ambil GPS Saya Sekarang' }}</span>
                                </button>
                                <button 
                                    type="button" 
                                    @click="addIncidentCoordinate('Titik Insiden Baru', 'incident_point')" 
                                    class="px-3 py-1.5 bg-white hover:bg-purple-100 text-purple-800 border border-purple-300 rounded-xl text-xs font-bold shadow-2xs transition-all flex items-center gap-1 cursor-pointer"
                                >
                                    <span>+ Tambah Tikor</span>
                                </button>
                            </div>
                        </div>

                        <!-- GPS Status notification -->
                        <div v-if="gpsStatusMessage" class="p-2 rounded-xl bg-purple-100/80 text-[11px] text-purple-900 font-medium flex items-center justify-between">
                            <span>{{ gpsStatusMessage }}</span>
                            <button type="button" @click="gpsStatusMessage = ''" class="text-purple-700 hover:text-purple-900 font-bold ml-2">✕</button>
                        </div>

                        <!-- Quick preset chips -->
                        <div class="p-2.5 rounded-xl bg-white border border-purple-200">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="text-[9px] font-bold text-purple-900 uppercase tracking-wide">Preset Label Cepat:</span>
                                <button 
                                    type="button" 
                                    v-for="chip in quickTikorChips" 
                                    :key="chip.label"
                                    @click="addIncidentCoordinate(chip.label, chip.category)"
                                    class="px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-purple-50 hover:bg-purple-200 text-purple-900 border border-purple-300 transition-colors shadow-2xs cursor-pointer"
                                >
                                    + {{ chip.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="incidentCoordinates.length === 0" class="p-3 rounded-xl bg-white/70 border border-dashed border-purple-200 text-center text-xs text-purple-700 italic">
                            Belum ada titik koordinat insiden yang dicatat. Klik <b>"📍 Ambil GPS Saya Sekarang"</b> atau tombol preset di atas.
                        </div>

                        <!-- List of Incident Coordinates -->
                        <div v-else class="space-y-2.5">
                            <div 
                                v-for="(coord, cIdx) in incidentCoordinates" 
                                :key="coord.id || cIdx"
                                class="p-3 bg-white rounded-xl border border-purple-200 shadow-2xs space-y-2"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 flex-1">
                                        <span class="w-5 h-5 rounded-full bg-purple-100 text-purple-800 font-bold text-[10px] flex items-center justify-center shrink-0">
                                            {{ cIdx + 1 }}
                                        </span>
                                        <input 
                                            type="text" 
                                            v-model="coord.label" 
                                            placeholder="Label titik (cth: Titik Kabel Putus, ODP V-CTG 01...)"
                                            class="text-xs font-bold text-gray-900 border-0 border-b border-gray-200 focus:border-purple-500 focus:ring-0 p-1 flex-1 bg-transparent"
                                        />
                                    </div>
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <button 
                                            type="button" 
                                            @click="getCurrentGpsLocation(coord)" 
                                            title="Perbarui titik ini dengan GPS saat ini"
                                            class="p-1.5 text-purple-600 hover:bg-purple-50 rounded-lg text-xs font-bold flex items-center gap-1 border border-purple-200 cursor-pointer"
                                        >
                                            <span>📡 GPS</span>
                                        </button>
                                        <a 
                                            v-if="coord.latitude && coord.longitude" 
                                            :href="`https://www.google.com/maps?q=${coord.latitude},${coord.longitude}`" 
                                            target="_blank"
                                            class="p-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg text-xs font-bold flex items-center gap-1 border border-emerald-200"
                                            title="Buka titik ini di Google Maps"
                                        >
                                            <span>🗺️ Maps</span>
                                        </a>
                                        <button 
                                            type="button" 
                                            @click="removeIncidentCoordinate(cIdx)" 
                                            class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg text-xs font-bold cursor-pointer"
                                            title="Hapus Titik"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 text-xs">
                                    <div class="sm:col-span-3">
                                        <label class="block text-[9px] font-bold text-gray-500 uppercase mb-0.5">Latitude *</label>
                                        <input 
                                            type="number" 
                                            step="any" 
                                            v-model="coord.latitude" 
                                            placeholder="-6.9123456" 
                                            class="w-full text-xs rounded-lg border-gray-300 font-mono py-1 px-2.5 focus:border-purple-500"
                                        />
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label class="block text-[9px] font-bold text-gray-500 uppercase mb-0.5">Longitude *</label>
                                        <input 
                                            type="number" 
                                            step="any" 
                                            v-model="coord.longitude" 
                                            placeholder="106.8234567" 
                                            class="w-full text-xs rounded-lg border-gray-300 font-mono py-1 px-2.5 focus:border-purple-500"
                                        />
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label class="block text-[9px] font-bold text-gray-500 uppercase mb-0.5">Patokan Lokasi</label>
                                        <input 
                                            type="text" 
                                            v-model="coord.address_reference" 
                                            placeholder="Depan Gang Material / Tiang PLN" 
                                            class="w-full text-xs rounded-lg border-gray-300 py-1 px-2.5 focus:border-purple-500"
                                        />
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label class="block text-[9px] font-bold text-gray-500 uppercase mb-0.5">Catatan Teknis</label>
                                        <input 
                                            type="text" 
                                            v-model="coord.notes" 
                                            placeholder="Kabel putus terlindas truk..." 
                                            class="w-full text-xs rounded-lg border-gray-300 py-1 px-2.5 focus:border-purple-500"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ════════ SECTION 3: REDAMAN OPTIK ════════ -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Redaman Optik Sebelum (dBm)</label>
                            <input 
                                type="text" 
                                v-model="reportForm.optical_power_before" 
                                placeholder="Contoh: -31.50 dBm / LOS"
                                class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm" 
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Redaman Optik Sesudah (dBm)</label>
                            <input 
                                type="text" 
                                v-model="reportForm.optical_power_after" 
                                placeholder="Contoh: -18.40 dBm"
                                class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm" 
                            />
                        </div>
                    </div>

                    <!-- ════════ SECTION 3B: MATERIAL SEBELUMNYA DI PELANGGAN & PERGANTIAN MATERIAL ════════ -->
                    <div class="p-4 rounded-2xl bg-teal-50/60 border border-teal-200/80 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <label class="block text-xs font-black text-teal-950 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>🔄</span> Material Terpasang Sebelumnya di Pelanggan & Pergantian Bekas
                                </label>
                                <p class="text-[11px] text-teal-800">
                                    Pilih material terpasang yang diganti (bisa <b>Ganti Seluruhnya</b> atau <b>Ganti Sebagian</b>). Material bekas wajib dibawa ke Gudang dan diserahterimakan dengan Gudang / Ka. Teknisi.
                                </p>
                            </div>
                            <button 
                                type="button" 
                                @click="isAddInstalledMatModalOpen = !isAddInstalledMatModalOpen" 
                                class="px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all flex items-center gap-1 shrink-0 cursor-pointer"
                            >
                                <span>+ Tambah Item Terpasang Baru</span>
                            </button>
                        </div>

                        <!-- Quick form tambah item terpasang baru jika ada hardware custom di rumah pelanggan -->
                        <div v-if="isAddInstalledMatModalOpen" class="p-3.5 rounded-xl bg-white border border-teal-300 shadow-sm space-y-2 animate-fade-in-up">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-teal-900">📝 Daftarkan Item Terpasang di Pelanggan:</span>
                                <button type="button" @click="isAddInstalledMatModalOpen = false" class="text-xs text-gray-400 hover:text-gray-600 font-bold cursor-pointer">✕ Tutup</button>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 text-xs">
                                <div class="sm:col-span-5">
                                    <input type="text" v-model="newInstalledMatForm.item_name" placeholder="Nama item (cth: S-Clamp 2 Pcs, Router Tambahan...)" class="w-full text-xs rounded-lg border-gray-300 py-1.5 px-2.5" />
                                </div>
                                <div class="sm:col-span-2">
                                    <input type="number" step="0.01" min="0.01" v-model="newInstalledMatForm.quantity" placeholder="Qty" class="w-full text-xs rounded-lg border-gray-300 py-1.5 text-center font-bold" />
                                </div>
                                <div class="sm:col-span-2">
                                    <select v-model="newInstalledMatForm.unit" class="w-full text-xs rounded-lg border-gray-300 py-1.5 px-2">
                                        <option value="pcs">pcs</option>
                                        <option value="meter">meter</option>
                                        <option value="unit">unit</option>
                                        <option value="set">set</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-3 flex gap-1">
                                    <button type="button" @click="saveNewInstalledMaterial" :disabled="!newInstalledMatForm.item_name" class="flex-1 py-1.5 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-lg text-xs disabled:opacity-50 cursor-pointer">
                                        Simpan Item
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Daftar Material Terpasang Aktif di Pelanggan -->
                        <div class="space-y-2">
                            <div class="text-[11px] font-bold text-teal-900 uppercase flex items-center justify-between">
                                <span>Daftar Material Terpasang Saat Ini</span>
                                <span class="text-teal-700 font-bold bg-teal-100/70 px-2 py-0.5 rounded-full text-[10px]">
                                    {{ activeCustomerMaterials.length }} item terpasang
                                </span>
                            </div>

                            <div v-if="activeCustomerMaterials.length === 0" class="p-3 rounded-xl bg-white border border-teal-200 text-xs text-gray-400 italic text-center">
                                Belum ada catatan material terpasang. Klik "+ Tambah Item Terpasang Baru" di atas.
                            </div>

                            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div 
                                    v-for="inst in activeCustomerMaterials" 
                                    :key="inst.id"
                                    class="p-3 rounded-xl bg-white border border-teal-100 shadow-2xs flex flex-col justify-between gap-2 transition-all hover:border-teal-300"
                                >
                                    <div>
                                        <div class="flex items-center justify-between gap-1">
                                            <span class="font-bold text-gray-900 text-xs truncate" :title="inst.item_name">{{ inst.item_name }}</span>
                                            <span class="font-mono font-bold text-xs text-teal-800 bg-teal-50 px-2 py-0.5 rounded border border-teal-200 shrink-0">
                                                {{ parseFloat(inst.quantity) }} {{ inst.unit }}
                                            </span>
                                        </div>
                                        <div v-if="inst.serial_number" class="text-[10px] font-mono text-gray-500 mt-0.5">
                                            SN: {{ inst.serial_number }}
                                        </div>
                                        <div v-if="inst.notes" class="text-[10px] text-gray-400 italic truncate mt-0.5">
                                            {{ inst.notes }}
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between pt-1.5 border-t border-gray-100">
                                        <span class="px-2 py-0.2 rounded text-[9px] font-bold uppercase tracking-wider" :class="inst.status === 'partially_replaced' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'">
                                            {{ inst.status === 'partially_replaced' ? 'Diganti Sebagian' : '✓ Terpasang' }}
                                        </span>
                                        <button 
                                            type="button" 
                                            @click="initReplacementForInstalledMaterial(inst)"
                                            :class="materialReplacements.some(r => r.installed_material_id === inst.id) ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-teal-50 text-teal-700 hover:bg-teal-100 border-teal-200'"
                                            class="px-2.5 py-1 rounded-lg border text-[11px] font-bold transition-all flex items-center gap-1 cursor-pointer"
                                        >
                                            <span>🔄</span>
                                            <span>{{ materialReplacements.some(r => r.installed_material_id === inst.id) ? 'Sedang Diganti' : 'Ganti Material' }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Antrean Pergantian Material (Form Detail Pergantian) -->
                        <div v-if="materialReplacements.length > 0" class="pt-3 border-t border-teal-200/80 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black text-amber-950 uppercase tracking-wide flex items-center gap-1.5">
                                    <span>⚠️</span> Rincian Material yang Diganti & Dibawa ke Gudang ({{ materialReplacements.length }} Item)
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-200 text-amber-900 border border-amber-300">
                                    Wajib Serah Terima Gudang / Ka. Teknisi
                                </span>
                            </div>

                            <div class="space-y-3">
                                <div 
                                    v-for="(rep, rIdx) in materialReplacements" 
                                    :key="rIdx"
                                    class="p-4 rounded-2xl bg-white border-2 border-amber-300 shadow-sm space-y-3"
                                >
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <div class="font-black text-gray-900 text-sm flex items-center gap-2">
                                                <span>📦</span>
                                                <span>{{ rep.item_name }}</span>
                                                <span class="text-xs font-normal text-gray-500">(Total Terpasang: {{ rep.total_installed_qty }} {{ rep.unit }})</span>
                                            </div>
                                            <p class="text-[11px] text-amber-800 mt-0.5">
                                                Pilih apakah material diganti seluruhnya (100%) atau sebagian (pemotongan/sambungan).
                                            </p>
                                        </div>
                                        <button 
                                            type="button" 
                                            @click="removeReplacement(rIdx)" 
                                            class="text-rose-500 hover:text-rose-700 text-xs font-bold p-1 rounded-lg hover:bg-rose-50 cursor-pointer"
                                            title="Batalkan Pergantian Item Ini"
                                        >
                                            ✕ Batalkan
                                        </button>
                                    </div>

                                    <!-- Pilihan Mode: Ganti Seluruhnya vs Ganti Sebagian -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <label 
                                            class="p-2.5 rounded-xl border flex items-center gap-2 cursor-pointer transition-all"
                                            :class="rep.replacement_type === 'full' ? 'bg-amber-50 border-amber-500 ring-2 ring-amber-200 text-amber-950 font-bold' : 'border-gray-200 text-gray-700'"
                                        >
                                            <input 
                                                type="radio" 
                                                v-model="rep.replacement_type" 
                                                value="full" 
                                                @change="rep.quantity = rep.total_installed_qty; rep.replacement_quantity = rep.total_installed_qty"
                                                class="text-amber-600 focus:ring-amber-500" 
                                            />
                                            <div>
                                                <div class="text-xs">🔄 Ganti Seluruhnya (100%)</div>
                                                <div class="text-[10px] text-gray-500 font-normal">Seluruh {{ rep.total_installed_qty }} {{ rep.unit }} ditarik/diganti unit baru</div>
                                            </div>
                                        </label>

                                        <label 
                                            class="p-2.5 rounded-xl border flex items-center gap-2 cursor-pointer transition-all"
                                            :class="rep.replacement_type === 'partial' ? 'bg-amber-50 border-amber-500 ring-2 ring-amber-200 text-amber-950 font-bold' : 'border-gray-200 text-gray-700'"
                                        >
                                            <input 
                                                type="radio" 
                                                v-model="rep.replacement_type" 
                                                value="partial" 
                                                class="text-amber-600 focus:ring-amber-500" 
                                            />
                                            <div>
                                                <div class="text-xs">✂️ Ganti Sebagian (Potong / Sambung)</div>
                                                <div class="text-[10px] text-gray-500 font-normal">Hanya sebagian yang diganti, sisa tetap terpasang</div>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Input Qty, Kondisi Bekas, dan Alasan Kerusakan -->
                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs pt-1">
                                        <div class="sm:col-span-3">
                                            <label class="block text-[10px] font-bold text-gray-700 uppercase mb-0.5">
                                                Jumlah Dibongkar *
                                            </label>
                                            <div class="flex items-center gap-1">
                                                <input 
                                                    type="number" 
                                                    step="0.01" 
                                                    min="0.01" 
                                                    :max="rep.total_installed_qty"
                                                    v-model="rep.quantity" 
                                                    class="w-full text-xs rounded-xl border-gray-300 font-black text-center py-1.5 focus:border-amber-500"
                                                    required 
                                                />
                                                <span class="text-gray-500 font-bold text-xs">{{ rep.unit }}</span>
                                            </div>
                                        </div>

                                        <div class="sm:col-span-4">
                                            <label class="block text-[10px] font-bold text-gray-700 uppercase mb-0.5">
                                                Kondisi Fisik Barang Bekas *
                                            </label>
                                            <select 
                                                v-model="rep.condition" 
                                                class="w-full text-xs rounded-xl border-gray-300 font-semibold py-1.5 px-2 focus:border-amber-500"
                                                required
                                            >
                                                <option value="rusak_scrap">🔴 Rusak / Scrap (Afkir)</option>
                                                <option value="normal_reusable">🟢 Layak Pakai (Bisa Dipakai Kembali)</option>
                                                <option value="perlu_cek">🟡 Perlu Pengecekan / Servis Lab</option>
                                            </select>
                                        </div>

                                        <div class="sm:col-span-5">
                                            <label class="block text-[10px] font-bold text-gray-700 uppercase mb-0.5">
                                                Alasan Kerusakan / Penggantian *
                                            </label>
                                            <input 
                                                type="text" 
                                                v-model="rep.damage_reason" 
                                                placeholder="Kabel putus terlindas / loss tinggi..." 
                                                class="w-full text-xs rounded-xl border-gray-300 py-1.5 px-2.5 focus:border-amber-500"
                                                required
                                            />
                                        </div>
                                    </div>

                                    <!-- Preset Chips Alasan Kerusakan -->
                                    <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Pilihan Cepat Alasan:</span>
                                        <button 
                                            type="button" 
                                            v-for="reason in quickDamageChips" 
                                            :key="reason"
                                            @click="rep.damage_reason = reason"
                                            class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-gray-100 hover:bg-amber-100 text-gray-700 hover:text-amber-900 border border-gray-200 transition-colors shadow-2xs cursor-pointer"
                                        >
                                            {{ reason }}
                                        </button>
                                    </div>

                                    <!-- Sumber Unit Pengganti Baru (Bekal Ka. Teknisi vs Permintaan Dadakan) -->
                                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/90 space-y-2 text-xs">
                                        <div class="flex items-center justify-between">
                                            <span class="text-[10px] font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                                <span>⚡</span> Sumber Unit Baru Pengganti:
                                            </span>
                                            <span class="text-[10px] text-gray-500 font-medium">
                                                Unit yang dipasangkan menggantikan barang bekas
                                            </span>
                                        </div>

                                        <!-- Opsi 1: Jika cocok dengan bekal Ka. Teknisi -->
                                        <div v-if="findMatchingPreparedMaterial(rep)" class="p-2.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-950 flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-2xs">
                                            <div>
                                                <div class="font-bold flex items-center gap-1.5 text-xs text-emerald-900">
                                                    <span>✓</span> Tersedia di Bekal Ka. Teknisi:
                                                    <span class="underline">{{ findMatchingPreparedMaterial(rep).name }}</span>
                                                </div>
                                                <div class="text-[11px] text-emerald-700 mt-0.5">
                                                    Bawa dari Kantor: <b>{{ findMatchingPreparedMaterial(rep).quantity_prepared }} {{ findMatchingPreparedMaterial(rep).unit }}</b> 
                                                    (Tercatat terpakai: <b>{{ findMatchingPreparedMaterial(rep).quantity_used }} {{ findMatchingPreparedMaterial(rep).unit }}</b>)
                                                </div>
                                            </div>
                                            <button 
                                                type="button" 
                                                @click="syncReplacementToPrepared(rep, findMatchingPreparedMaterial(rep))" 
                                                class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-[10px] shadow-sm flex items-center gap-1 shrink-0 self-start sm:self-auto cursor-pointer transition-all"
                                                title="Klik untuk otomatis sinkronkan kuantitas terpakai pada Bagian 4A"
                                            >
                                                <span>⚡ Pakai Bekal Ini (Sync Bagian 4A)</span>
                                            </button>
                                        </div>

                                        <!-- Opsi 2: Jika belum ada di bekal -> Tombol Ajukan Dadakan -->
                                        <div v-else class="p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-950 flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-2xs">
                                            <div>
                                                <div class="font-bold text-xs text-amber-900 flex items-center gap-1.5">
                                                    <span>⚠️</span> Belum Ada di Bekal Ka. Teknisi
                                                </div>
                                                <div class="text-[11px] text-amber-800 mt-0.5">
                                                    Tidak ada bekal yang cocok untuk item ini. Butuh pasokan dadakan dari kantor/gudang?
                                                </div>
                                            </div>
                                            <button 
                                                type="button" 
                                                @click="openEmergencyRequestForReplacement(rep)" 
                                                class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-lg text-[10px] shadow-sm flex items-center gap-1 shrink-0 self-start sm:self-auto cursor-pointer transition-all"
                                                title="Buka form pengajuan dadakan ke Ka. Teknisi dengan item ini terisi otomatis"
                                            >
                                                <span>📢 + Ajukan Permintaan Dadakan</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Info Serah Terima Notice -->
                                    <div class="p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-[11px] text-amber-900 flex items-center justify-between flex-wrap gap-2">
                                        <div class="flex items-center gap-1.5">
                                            <span>📦</span>
                                            <span>Barang bekas (<b>{{ rep.quantity }} {{ rep.unit }}</b>) otomatis berstatus <b>Menunggu Serah Terima</b> ke Gudang / Ka. Teknisi.</span>
                                        </div>
                                        <span class="font-bold text-amber-800 text-[10px] bg-white px-2 py-0.5 rounded border border-amber-200">
                                            Disposisi: {{ rep.condition === 'normal_reusable' ? '🟢 Restock Gudang' : '🔴 Scrap / Afkir' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ════════ SECTION 4: REKONSILIASI MATERIAL TERPAKAI CANGGIH ════════ -->
                    <div class="p-4 rounded-2xl bg-amber-50/50 border border-amber-200/80 space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block text-xs font-black text-amber-950 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>📦</span> Rekonsiliasi Material Terpakai & Pengembalian
                                </label>
                                <p class="text-[11px] text-amber-800">
                                    Catat jumlah material terpakai dan material tambahan lapangan. Sisa otomatis dikembalikan ke stok gudang.
                                </p>
                            </div>
                        </div>

                        <!-- 4A. Material Bekal Awal dari Ka. Teknisi / Jadwal -->
                        <div class="space-y-2">
                            <div class="text-[11px] font-bold text-gray-600 uppercase flex items-center justify-between">
                                <span>A. Material Disiapkan Gudang / Jadwal</span>
                                <span class="text-gray-400 font-normal">({{ reportForm.materials.length }} jenis)</span>
                            </div>

                            <div v-if="reportForm.materials.length === 0" class="p-3 rounded-xl bg-white border border-gray-200 text-xs text-gray-400 italic text-center">
                                Tidak ada material yang disiapkan di jadwal penanganan ini.
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
                                                    class="px-2 py-0.2 rounded-full text-[10px] font-bold uppercase"
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
                                Sisa material yang tidak habis terpakai wajib diserahkan kembali ke kantor/gudang. Saat laporan disimpan, sistem otomatis membuat transaksi retur masuk (<b>IN-PGS-RET-...</b>) dan <b>menambah kembali stok gudang</b>:
                            </p>
                            <div class="space-y-1.5">
                                <div v-for="ret in returnedMaterialsList" :key="'ret-' + ret.material_id" class="p-2.5 rounded-xl bg-white border border-blue-200 flex items-center justify-between shadow-sm">
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

                        <!-- 4B. Permintaan Tambahan Material Lapangan ke Ka. Teknisi -->
                        <div class="pt-3 border-t border-amber-200/60 space-y-3">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <div>
                                    <div class="text-[11px] font-bold text-gray-800 uppercase flex items-center gap-1.5">
                                        <span class="text-amber-500">📢</span> B. Permintaan Tambahan Material ke Kepala Teknisi
                                    </div>
                                    <p class="text-[10px] text-gray-500">
                                        Kekurangan material di lapangan? Ajukan ke Kepala Teknisi untuk disetujui di tab <b>Jadwal & Material</b>.
                                    </p>
                                </div>
                                <button 
                                    type="button" 
                                    @click="isRequestFormOpen = !isRequestFormOpen" 
                                    class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold shadow-sm transition-all flex items-center gap-1 shrink-0"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span>{{ isRequestFormOpen ? 'Tutup Form Pengajuan' : '+ Ajukan Tambahan Material' }}</span>
                                </button>
                            </div>

                            <!-- Form Ajukan Material ke Ka. Teknisi -->
                            <div v-if="isRequestFormOpen" class="p-4 rounded-2xl bg-amber-50/90 border border-amber-200 shadow-sm space-y-3 transition-all animate-fade-in-up">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-xs font-bold text-amber-900 flex items-center gap-1.5">
                                            <span>📝</span> Form Pengajuan Kebutuhan Tambahan ke Ka. Teknisi
                                        </div>
                                        <p class="text-[11px] text-amber-800 mt-0.5">
                                            Tuliskan daftar kebutuhan barang di lapangan (bisa lebih dari 1 material). Kepala Teknisi akan memverifikasi dan mengalokasikan stok barang dari gudang.
                                        </p>
                                    </div>
                                    <span class="text-[10px] font-bold text-amber-900 bg-amber-200/80 px-2.5 py-0.5 rounded-lg border border-amber-300 shrink-0">
                                        {{ materialRequestForm.items.length }} Material
                                    </span>
                                </div>

                                <!-- Quick Suggestion Chips Lapangan -->
                                <div class="p-2.5 rounded-xl bg-amber-100/60 border border-amber-200">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="text-[9px] font-bold text-amber-900 uppercase tracking-wide">Pilihan Cepat (Klik untuk tambah):</span>
                                        <button 
                                            type="button" 
                                            v-for="chip in quickMaterialChips" 
                                            :key="chip.name"
                                            @click="applyQuickMaterialChip(chip)"
                                            class="px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-white hover:bg-amber-200 text-amber-900 border border-amber-300 transition-colors shadow-2xs cursor-pointer"
                                        >
                                            + {{ chip.name }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Daftar Baris Material -->
                                <div class="space-y-2.5">
                                    <div 
                                        v-for="(item, idx) in materialRequestForm.items" 
                                        :key="idx" 
                                        class="p-3 bg-white rounded-xl border border-amber-200 shadow-2xs relative"
                                    >
                                        <div class="flex items-center justify-between mb-1.5">
                                            <span class="text-[10px] font-bold text-amber-800 uppercase flex items-center gap-1">
                                                <span class="w-4 h-4 rounded-full bg-amber-200 text-amber-900 flex items-center justify-center text-[9px] font-bold">{{ idx + 1 }}</span>
                                                Material #{{ idx + 1 }}
                                            </span>
                                            <button 
                                                v-if="materialRequestForm.items.length > 1"
                                                type="button" 
                                                @click="removeRequestItem(idx)"
                                                class="text-rose-500 hover:text-rose-700 text-[10px] font-bold flex items-center gap-0.5 px-1.5 py-0.5 rounded hover:bg-rose-50 transition-colors cursor-pointer"
                                                title="Hapus Baris Ini"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                <span>Hapus</span>
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                                            <!-- Input Nama / Kebutuhan Material -->
                                            <div class="sm:col-span-7">
                                                <label class="block text-[9px] font-bold text-gray-600 uppercase mb-0.5">Nama / Deskripsi Material *</label>
                                                <input 
                                                    type="text" 
                                                    v-model="item.item_name" 
                                                    placeholder="Contoh: Kabel Dropcore 1 Core, Fast Connector SC..."
                                                    class="w-full rounded-xl border-gray-300 text-xs py-1.5 px-3 focus:border-amber-500 bg-white font-medium shadow-2xs"
                                                    required
                                                />
                                            </div>

                                            <!-- Estimasi Jumlah -->
                                            <div class="sm:col-span-2">
                                                <label class="block text-[9px] font-bold text-gray-600 uppercase mb-0.5">Estimasi Jml *</label>
                                                <input 
                                                    type="number" 
                                                    step="0.01" 
                                                    min="0.01" 
                                                    v-model="item.quantity" 
                                                    class="w-full rounded-xl border-gray-300 text-xs py-1.5 text-center font-bold focus:border-amber-500 bg-white shadow-2xs"
                                                    placeholder="1"
                                                    required
                                                />
                                            </div>

                                            <!-- Pilihan Satuan -->
                                            <div class="sm:col-span-3">
                                                <label class="block text-[9px] font-bold text-gray-600 uppercase mb-0.5">Satuan *</label>
                                                <select 
                                                    v-model="item.unit" 
                                                    class="w-full rounded-xl border-gray-300 text-xs py-1.5 focus:border-amber-500 bg-white font-medium shadow-2xs"
                                                >
                                                    <option value="meter">meter</option>
                                                    <option value="pcs">pcs</option>
                                                    <option value="rol">rol</option>
                                                    <option value="set">set</option>
                                                    <option value="pack">pack</option>
                                                    <option value="buah">buah</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tombol Tambah Baris Material Lainnya -->
                                <div>
                                    <button 
                                        type="button" 
                                        @click="addRequestItem('', 1, 'pcs')"
                                        class="w-full py-2 border-2 border-dashed border-amber-300 hover:border-amber-400 bg-white hover:bg-amber-100/50 text-amber-900 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        <span>+ Tambah Baris Material Lainnya</span>
                                    </button>
                                </div>

                                <!-- Alasan Kebutuhan di Lapangan -->
                                <div class="pt-1">
                                    <label class="block text-[10px] font-bold text-gray-700 uppercase mb-0.5">Alasan / Kondisi di Lapangan (Untuk Seluruh Material Di Atas) *</label>
                                    <input 
                                        type="text" 
                                        v-model="materialRequestForm.notes" 
                                        placeholder="Contoh: Kabel dropcore terputus tertimpa dahan pohon dan konektor pecah..." 
                                        class="w-full rounded-xl border-gray-300 text-xs py-2 px-3 focus:border-amber-500 bg-white shadow-2xs"
                                        required
                                    />
                                    <!-- Template Alasan -->
                                    <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                        <span class="text-[9px] font-bold text-gray-500 uppercase tracking-wide">Template Alasan:</span>
                                        <button 
                                            type="button" 
                                            v-for="reason in quickReasonChips" 
                                            :key="reason"
                                            @click="applyQuickReasonChip(reason)"
                                            class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-amber-100/70 hover:bg-amber-100 text-amber-900 border border-amber-200 transition-colors shadow-2xs cursor-pointer"
                                        >
                                            {{ reason }}
                                        </button>
                                    </div>
                                </div>

                                <div class="flex justify-end gap-2 pt-2 border-t border-amber-200/60">
                                    <button 
                                        type="button" 
                                        @click="isRequestFormOpen = false" 
                                        class="px-3.5 py-1.5 rounded-xl border border-gray-300 bg-white text-gray-600 text-xs font-semibold hover:bg-gray-50 cursor-pointer"
                                    >
                                        Batal
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="sendMaterialRequest" 
                                        :disabled="materialRequestForm.processing || validRequestItemsCount === 0 || !materialRequestForm.notes"
                                        class="px-4 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow-sm transition-all disabled:opacity-50 flex items-center gap-1.5 cursor-pointer"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                        <span>{{ materialRequestForm.processing ? 'Mengirim...' : `Kirim Permintaan (${validRequestItemsCount} Material) ke Ka. Teknisi` }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Daftar Riwayat Permintaan Material Tiket Ini -->
                            <div class="space-y-2">
                                <div class="text-[11px] font-bold text-gray-600 uppercase flex items-center justify-between">
                                    <span>Status Permintaan Material ke Ka. Teknisi ({{ selectedTicket?.material_requests?.length || 0 }})</span>
                                    <span v-if="selectedTicket?.material_requests?.some(r => r.status === 'pending')" class="text-[10px] font-bold text-amber-700 bg-amber-100 px-2.5 py-0.5 rounded-full border border-amber-300 animate-pulse">
                                        ⏳ Menunggu Alokasi Ka. Teknisi
                                    </span>
                                </div>

                                <div v-if="!selectedTicket?.material_requests || selectedTicket.material_requests.length === 0" class="p-3 rounded-xl bg-white/70 border border-dashed border-gray-300 text-xs text-gray-400 italic text-center">
                                    Belum ada pengajuan permintaan tambahan material ke Ka. Teknisi untuk tiket ini.
                                </div>

                                <div v-else class="space-y-2">
                                    <div 
                                        v-for="req in selectedTicket.material_requests" 
                                        :key="req.id"
                                        class="p-3.5 rounded-2xl border transition-all text-xs"
                                        :class="{
                                            'bg-amber-50/80 border-amber-300': req.status === 'pending',
                                            'bg-emerald-50/80 border-emerald-300': req.status === 'approved',
                                            'bg-rose-50/80 border-rose-300': req.status === 'rejected'
                                        }"
                                    >
                                        <div class="flex items-start justify-between gap-2 flex-wrap">
                                            <div>
                                                <div class="font-bold text-gray-900 flex items-center gap-1.5">
                                                    <span class="text-sm">📦</span>
                                                    <span>{{ req.item_name || req.material?.name || 'Material Tambahan' }}</span>
                                                    <span class="font-mono text-xs px-2 py-0.5 rounded-lg bg-white border font-bold text-gray-800">
                                                        {{ parseFloat(req.quantity) }} {{ req.unit || req.material?.unit }}
                                                    </span>
                                                </div>
                                                <div v-if="req.status === 'approved' && req.material" class="text-[11px] text-emerald-800 font-semibold mt-1 flex items-center gap-1">
                                                    <span>🏷️ Dialokasikan dari Gudang: <b>{{ req.material.name }}</b></span>
                                                </div>
                                            </div>

                                            <!-- Status Pill -->
                                            <div>
                                                <span v-if="req.status === 'pending'" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-200 text-amber-900 border border-amber-300 inline-flex items-center gap-1">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-600 animate-pulse"></span>
                                                    <span>Menunggu Alokasi Ka. Teknisi</span>
                                                </span>
                                                <span v-else-if="req.status === 'approved'" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-200 text-emerald-900 border border-emerald-300 inline-flex items-center gap-1">
                                                    <span>✅ Disetujui (Masuk Bekal)</span>
                                                </span>
                                                <span v-else-if="req.status === 'rejected'" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-200 text-rose-900 border border-rose-300 inline-flex items-center gap-1">
                                                    <span>❌ Ditolak</span>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="mt-2 text-gray-700 bg-white/80 p-2.5 rounded-xl border border-gray-100">
                                            <span class="font-semibold text-gray-500">Alasan Kebutuhan:</span> "{{ req.notes }}"
                                        </div>

                                        <!-- Detail info persetujuan/penolakan -->
                                        <div v-if="req.status === 'approved'" class="mt-1.5 text-[11px] text-emerald-800 font-medium flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Disetujui oleh <b>{{ req.reviewer?.name || 'Kepala Teknisi' }}</b>. Stok gudang otomatis terpotong & ditambahkan ke daftar Bagian A di atas.</span>
                                        </div>
                                        <div v-else-if="req.status === 'rejected'" class="mt-1.5 text-[11px] text-rose-800 font-medium flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            <span>Ditolak oleh <b>{{ req.reviewer?.name || 'Kepala Teknisi' }}</b>: "{{ req.rejection_reason || '-' }}"</span>
                                        </div>
                                        <div v-else class="mt-1.5 text-[10px] text-amber-800 italic">
                                            ⏳ Permintaan ini sedang menunggu persetujuan Kepala Teknisi di tab <b>Jadwal & Material</b>.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4C. Catatan Otomatis Gudang -->
                        <div class="p-2.5 rounded-xl bg-white border border-gray-200 text-[11px] text-gray-500 flex items-center justify-between flex-wrap gap-2">
                            <span>* Sisa material disiapkan akan otomatis dikembalikan ke stok gudang saat laporan disimpan.</span>
                            <div class="flex items-center gap-2 font-bold text-gray-700">
                                <span>Terpakai: {{ countUsedMaterials }} item</span> · 
                                <span class="text-blue-700">Kembali: {{ countReturnedMaterials }} item</span>
                            </div>
                        </div>
                    </div>

                    <!-- ════════ SECTION 5: PERGANTIAN PERANGKAT ONT PELANGGAN ════════ -->
                    <div class="p-4 rounded-2xl bg-indigo-50/70 border border-indigo-200 space-y-3">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div>
                                <label class="block text-xs font-black text-indigo-950 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>🔄</span> 5. Pergantian Unit ONT Pelanggan
                                </label>
                                <p class="text-[11px] text-indigo-800">
                                    Alur pergantian ONT: Teknisi mengajukan permintaan ke Ka. Teknisi. Jika tidak jadi dipasang, unit ONT baru otomatis dikembalikan ke Inventory Gudang.
                                </p>
                            </div>
                            <!-- Status Badge -->
                            <span v-if="selectedTicket?.ont_replacement_status === 'requested'" class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-300 animate-pulse">
                                ⏳ Menunggu Ka. Teknisi Siapkan ONT
                            </span>
                            <span v-else-if="selectedTicket?.ont_replacement_status === 'prepared'" class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-300">
                                📦 ONT Baru Telah Disiapkan
                            </span>
                            <span v-else-if="selectedTicket?.ont_replacement_status === 'installed'" class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                ✅ ONT Telah Sukses Diganti
                            </span>
                            <span v-else-if="selectedTicket?.ont_replacement_status === 'cancelled'" class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-gray-100 text-gray-700 border border-gray-300">
                                ↩️ Dibatalkan / Retur Gudang
                            </span>
                        </div>

                        <!-- Info ONT Lama Pelanggan -->
                        <div class="p-3 rounded-xl bg-white border border-indigo-100 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-400">ONT Terpasang Saat Ini:</span>
                                <span class="font-mono font-bold text-gray-900">{{ selectedTicket?.customer?.ont?.serial_number || 'Tidak terdeteksi / Belum ada' }}</span>
                                <span v-if="selectedTicket?.customer?.ont?.brand" class="text-gray-500">({{ selectedTicket.customer.ont.brand }} {{ selectedTicket.customer.ont.model }})</span>
                            </div>
                            <span v-if="selectedTicket?.customer?.ont?.status" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-gray-100 text-gray-700">
                                Status: {{ selectedTicket.customer.ont.status }}
                            </span>
                        </div>

                        <!-- KONDISI 1: Belum ada request atau Dibatalkan -> Tombol Ajukan Request -->
                        <div v-if="!selectedTicket?.ont_replacement_status || selectedTicket?.ont_replacement_status === 'none' || selectedTicket?.ont_replacement_status === 'cancelled'" class="space-y-3">
                            <div v-if="!isOntRequestFormOpen" class="flex justify-end">
                                <button 
                                    type="button" 
                                    @click="isOntRequestFormOpen = true" 
                                    class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all flex items-center gap-1.5"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    <span>+ Ajukan Permintaan Pergantian ONT ke Ka. Teknisi</span>
                                </button>
                            </div>

                            <!-- Form Request Pergantian ONT -->
                            <div v-if="isOntRequestFormOpen" class="p-3.5 rounded-2xl bg-amber-50/90 border border-amber-300 space-y-3 animate-fade-in-up">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-amber-950">📝 Formulir Pengajuan Pergantian ONT</span>
                                    <button type="button" @click="isOntRequestFormOpen = false" class="text-xs text-amber-700 hover:text-amber-900 font-bold">✕ Tutup</button>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-amber-900 mb-1">Alasan Pergantian ONT *</label>
                                    <textarea 
                                        v-model="ontRequestForm.requested_ont_notes" 
                                        rows="2" 
                                        placeholder="Contoh: ONT mati total tersambar petir / port LAN rusak terbakar / redaman LOS tinggi mesin rusak..." 
                                        class="w-full text-xs rounded-xl border-amber-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 bg-white"
                                    ></textarea>
                                </div>
                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="isOntRequestFormOpen = false" class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs font-semibold bg-white text-gray-700">Batal</button>
                                    <button 
                                        type="button" 
                                        @click="sendOntRequest" 
                                        :disabled="ontRequestForm.processing || !ontRequestForm.requested_ont_notes" 
                                        class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold disabled:opacity-50 transition-all flex items-center gap-1"
                                    >
                                        <span>{{ ontRequestForm.processing ? 'Mengirim...' : 'Kirim Request ke Ka. Teknisi' }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- KONDISI 2: Request Sedang Menunggu Ka. Teknisi -->
                        <div v-else-if="selectedTicket?.ont_replacement_status === 'requested'" class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-950 space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="font-bold flex items-center gap-1.5 text-amber-900">
                                    <span>⏳</span>
                                    <span>Permintaan Pergantian ONT Sedang Ditinjau Ka. Teknisi</span>
                                </div>
                                <button type="button" @click="cancelPreparedOnt(selectedTicket)" class="text-rose-600 hover:text-rose-800 text-[11px] font-bold underline">
                                    Batalkan Permintaan
                                </button>
                            </div>
                            <div class="text-[11px] text-amber-800 bg-white/80 p-2 rounded-xl border border-amber-200">
                                <strong>Catatan Permintaan:</strong> "{{ selectedTicket.requested_ont_notes }}"
                                <span class="block text-[10px] text-amber-600 mt-0.5">Diajukan: {{ formatDate(selectedTicket.requested_ont_at) }}</span>
                            </div>
                            <p class="text-[10px] text-amber-700 italic">
                                Kepala Teknisi akan menyiapkan unit ONT pengganti dari persediaan Gudang melalui menu Jadwal.
                            </p>
                        </div>

                        <!-- KONDISI 3: Ka. Teknisi Telah Menyiapkan ONT Baru dari Gudang -->
                        <div v-else-if="selectedTicket?.ont_replacement_status === 'prepared'" class="p-4 rounded-2xl bg-emerald-50/90 border-2 border-emerald-300 text-xs text-emerald-950 space-y-3">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <div class="font-bold flex items-center gap-1.5 text-emerald-950 text-sm">
                                    <span>📦</span>
                                    <span>Unit ONT Baru Telah Disiapkan dari Gudang!</span>
                                </div>
                                <button 
                                    type="button" 
                                    @click="cancelPreparedOnt(selectedTicket)" 
                                    class="px-2.5 py-1 rounded-lg bg-rose-100 hover:bg-rose-200 text-rose-800 border border-rose-300 text-[11px] font-bold transition-all shadow-sm"
                                    title="Jika tidak jadi ganti ONT, klik untuk mengembalikan langsung ke inventory gudang"
                                >
                                    ↩️ Ganti Tidak Jadi & Retur ke Gudang Sekarang
                                </button>
                            </div>

                            <div class="p-3 rounded-xl bg-white border border-emerald-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-sm">
                                <div>
                                    <div class="text-[10px] font-bold text-gray-400 uppercase">Unit ONT Baru yang Dibawa:</div>
                                    <div class="font-mono font-black text-sm text-emerald-700">
                                        SN: {{ selectedTicket.prepared_ont?.serial_number || '-' }}
                                    </div>
                                    <div class="text-[11px] text-gray-600">
                                        Merk/Model: {{ selectedTicket.prepared_ont?.brand || 'ONT' }} {{ selectedTicket.prepared_ont?.model || '' }}
                                    </div>
                                </div>
                                <div class="text-[11px] text-emerald-800 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-100 self-start sm:self-auto">
                                    Disiapkan oleh: <b>{{ selectedTicket.prepared_by_user?.name || 'Kepala Teknisi' }}</b>
                                </div>
                            </div>

                            <!-- Pilihan Aksi di Laporan: Jadi vs Tidak Jadi -->
                            <div class="space-y-2 pt-2 border-t border-emerald-200">
                                <label class="block text-[11px] font-bold text-emerald-950 uppercase tracking-wider">
                                    Keputusan Pergantian Saat Penyelesaian:
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <label 
                                        class="p-3 rounded-xl border flex items-center gap-2.5 cursor-pointer transition-all"
                                        :class="reportForm.ont_replacement_action === 'installed' ? 'bg-white border-emerald-500 ring-2 ring-emerald-200 text-emerald-950 font-bold shadow-sm' : 'bg-emerald-50/50 border-emerald-200 text-gray-700'"
                                    >
                                        <input type="radio" v-model="reportForm.ont_replacement_action" value="installed" class="text-emerald-600 focus:ring-emerald-500">
                                        <div>
                                            <div class="text-xs">🟢 Pergantian Berhasil Dipasang</div>
                                            <div class="text-[10px] text-gray-500 font-normal">ONT baru dipasangkan ke pelanggan, ONT lama ditarik/rusak.</div>
                                        </div>
                                    </label>

                                    <label 
                                        class="p-3 rounded-xl border flex items-center gap-2.5 cursor-pointer transition-all"
                                        :class="reportForm.ont_replacement_action === 'cancelled' ? 'bg-white border-rose-500 ring-2 ring-rose-200 text-rose-950 font-bold shadow-sm' : 'bg-emerald-50/50 border-emerald-200 text-gray-700'"
                                    >
                                        <input type="radio" v-model="reportForm.ont_replacement_action" value="cancelled" class="text-rose-600 focus:ring-rose-500">
                                        <div>
                                            <div class="text-xs">🔴 Tidak Jadi Ganti (Retur Gudang)</div>
                                            <div class="text-[10px] text-gray-500 font-normal">ONT baru otomatis dikembalikan ke Inventory Gudang.</div>
                                        </div>
                                    </label>
                                </div>

                                <div v-if="reportForm.ont_replacement_action === 'cancelled'" class="mt-2 animate-fade-in-up">
                                    <label class="block text-[10px] font-bold text-rose-800 uppercase mb-0.5">Alasan Tidak Jadi / Retur Gudang:</label>
                                    <input 
                                        type="text" 
                                        v-model="reportForm.ont_replacement_notes" 
                                        placeholder="Contoh: Kabel putus sudah disambung, ONT lama ternyata masih normal..." 
                                        class="w-full text-xs rounded-xl border-rose-300 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 bg-white"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- KONDISI 4: Sudah Selesai Diganti -->
                        <div v-else-if="selectedTicket?.ont_replacement_status === 'installed'" class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-900 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-base">✅</span>
                                <span>ONT Pelanggan telah berhasil diganti dengan unit <b>SN: {{ selectedTicket.prepared_ont?.serial_number || '-' }}</b>.</span>
                            </div>
                        </div>
                    </div>

                    <!-- ════════ SECTION 6: UPLOAD FOTO BUKTI LAPANGAN ════════ -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-gray-700 uppercase">Foto Bukti Sebelum Perbaikan</label>
                            <input 
                                type="file" 
                                accept="image/*"
                                @change="onPhotoBeforeChange"
                                class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100" 
                            />
                            <div v-if="photoBeforePreview" class="mt-1">
                                <img :src="photoBeforePreview" class="h-20 w-auto rounded-lg border border-gray-200 object-cover" />
                            </div>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-gray-700 uppercase">Foto Bukti Sesudah Perbaikan</label>
                            <input 
                                type="file" 
                                accept="image/*"
                                @change="onPhotoAfterChange"
                                class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100" 
                            />
                            <div v-if="photoAfterPreview" class="mt-1">
                                <img :src="photoAfterPreview" class="h-20 w-auto rounded-lg border border-gray-200 object-cover" />
                            </div>
                        </div>
                    </div>

                    <!-- ════════ SECTION 6: CATATAN AKHIR PENANGANAN ════════ -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Catatan Akhir Penanganan</label>
                        <textarea 
                            v-model="reportForm.resolution_notes" 
                            rows="2" 
                            placeholder="Contoh: Koneksi internet pelanggan telah diuji coba streaming dan speedtest normal. Pelanggan telah konfirmasi puas."
                            class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm" 
                        ></textarea>
                    </div>

                    <!-- ════════ SECTION 7: JAM SELESAI PENGERJAAN DI BAWAH ════════ -->
                    <div class="p-4 rounded-2xl bg-gradient-to-r from-blue-50/70 via-indigo-50/60 to-blue-50/70 border border-blue-200 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <label class="block text-xs font-black text-blue-950 uppercase tracking-wider">
                                    🏁 2. Jam Selesai Pengerjaan Lapangan
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
                                <span>{{ reportForm.processing ? 'Menyimpan...' : 'Simpan Laporan & Selesaikan PGS' }}</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermission } from '@/Composables/usePermission';

const { can } = usePermission();

const props = defineProps({
    tickets: Object,
    areas: Array,
    materials: {
        type: Array,
        default: () => []
    },
    availableOnts: {
        type: Array,
        default: () => []
    },
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

// Material Tambahan Lapangan State
const additionalMaterials = ref([]);

// Permintaan Tambahan Material ke Ka. Teknisi State
const isRequestFormOpen = ref(false);
const materialRequestForm = useForm({
    items: [
        { item_name: '', quantity: 1, unit: 'meter' }
    ],
    notes: '',
});

const addRequestItem = (name = '', quantity = 1, unit = 'pcs') => {
    materialRequestForm.items.push({
        item_name: name,
        quantity: quantity,
        unit: unit,
    });
};

const removeRequestItem = (index) => {
    if (materialRequestForm.items.length > 1) {
        materialRequestForm.items.splice(index, 1);
    } else {
        materialRequestForm.items[0].item_name = '';
        materialRequestForm.items[0].quantity = 1;
        materialRequestForm.items[0].unit = 'meter';
    }
};

const validRequestItemsCount = computed(() => {
    return materialRequestForm.items.filter(i => i.item_name && i.item_name.trim() && parseFloat(i.quantity) > 0).length;
});

// Quick suggestion chips pintar untuk teknisi lapangan
const quickMaterialChips = [
    { name: 'Kabel Dropcore 1 Core', unit: 'meter', defaultQty: 30 },
    { name: 'Fast Connector SC-UPC', unit: 'pcs', defaultQty: 2 },
    { name: 'Patchcord SC-UPC 3m', unit: 'pcs', defaultQty: 1 },
    { name: 'Klem Buaya / S-Clamp', unit: 'pcs', defaultQty: 4 },
    { name: 'Protection Sleeve', unit: 'pcs', defaultQty: 2 },
    { name: 'Roset Optik 1 Core', unit: 'pcs', defaultQty: 1 },
    { name: 'Adapter SC-UPC', unit: 'pcs', defaultQty: 1 },
    { name: 'Isolasi Listrik Hitam', unit: 'pcs', defaultQty: 1 },
];

const quickReasonChips = [
    'Dropcore putus tertimpa dahan pohon',
    'Redaman dropcore tinggi / tekukan kabel parah',
    'Kurang panjang tarikan dropcore dari tiang ODP',
    'Konektor patah / pecah di lokasi pelanggan',
    'Aksesoris clamp tiang lepas / rusak',
];

const applyQuickMaterialChip = (chip) => {
    const emptyItem = materialRequestForm.items.find(i => !i.item_name || !i.item_name.trim());
    if (emptyItem) {
        emptyItem.item_name = chip.name;
        emptyItem.unit = chip.unit;
        emptyItem.quantity = chip.defaultQty;
    } else {
        addRequestItem(chip.name, chip.defaultQty, chip.unit);
    }
};

const applyQuickReasonChip = (reason) => {
    if (materialRequestForm.notes) {
        materialRequestForm.notes = materialRequestForm.notes + ' - ' + reason;
    } else {
        materialRequestForm.notes = reason;
    }
};

// Permintaan Pergantian ONT ke Ka. Teknisi State
const isOntRequestFormOpen = ref(false);
const ontRequestForm = useForm({
    requested_ont_notes: '',
});

// ── Multi-Tikor State & Logika ─────────────────────────────────
const incidentCoordinates = ref([]);
const isFetchingGps = ref(false);
const gpsStatusMessage = ref('');

const quickTikorChips = [
    { label: 'Titik Kabel Putus / Terjepit', category: 'incident_point' },
    { label: 'Titik ODP', category: 'odp_point' },
    { label: 'Tiang Distribusi / Pole', category: 'pole_point' },
    { label: 'Rumah Pelanggan', category: 'customer_point' },
    { label: 'Titik Joint Closure / Splicing', category: 'closure_point' },
];

const addIncidentCoordinate = (label = 'Titik Insiden Baru', category = 'incident_point', lat = '', lng = '', notes = '') => {
    incidentCoordinates.value.push({
        id: Date.now() + Math.random(),
        label,
        category,
        latitude: lat,
        longitude: lng,
        address_reference: '',
        notes
    });
    saveDraft();
};

const removeIncidentCoordinate = (index) => {
    incidentCoordinates.value.splice(index, 1);
    saveDraft();
};

const getCurrentGpsLocation = (targetCoord = null) => {
    if (!navigator.geolocation) {
        alert('Perangkat Anda tidak mendukung fitur GPS Geolocation.');
        return;
    }
    isFetchingGps.value = true;
    gpsStatusMessage.value = 'Mendeteksi titik koordinat satelit GPS...';
    
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const lat = parseFloat(pos.coords.latitude.toFixed(7));
            const lng = parseFloat(pos.coords.longitude.toFixed(7));
            const acc = Math.round(pos.coords.accuracy);
            isFetchingGps.value = false;
            gpsStatusMessage.value = `✓ GPS Berhasil: ${lat}, ${lng} (Akurasi ±${acc}m)`;
            
            if (targetCoord) {
                targetCoord.latitude = lat;
                targetCoord.longitude = lng;
                if (!targetCoord.notes) targetCoord.notes = `Akurasi GPS: ±${acc}m`;
            } else {
                addIncidentCoordinate('Titik Insiden Lapangan', 'incident_point', lat, lng, `Akurasi GPS: ±${acc}m`);
            }
            saveDraft();
        },
        (err) => {
            isFetchingGps.value = false;
            gpsStatusMessage.value = 'Gagal mengambil GPS: ' + err.message;
            alert('Gagal mengambil titik koordinat GPS: ' + err.message);
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
};

// ── Material Terpasang & Pergantian Bekas State & Logika ────────
const materialReplacements = ref([]);
const isAddInstalledMatModalOpen = ref(false);
const newInstalledMatForm = useForm({
    item_name: '',
    quantity: 1,
    unit: 'pcs',
    serial_number: '',
    notes: ''
});

const activeCustomerMaterials = computed(() => {
    const list = selectedTicket.value?.customer?.active_installed_materials || selectedTicket.value?.customer?.activeInstalledMaterials;
    if (Array.isArray(list) && list.length > 0) {
        return list;
    }
    const ont = selectedTicket.value?.customer?.ont;
    const ontName = ont ? `ONT ${ont.brand || 'Standar'} ${ont.model || ''}` : 'Unit ONT / Modem FTTH Standar';
    return [
        {
            id: 'fallback-ont',
            item_name: ontName,
            quantity: 1,
            unit: 'unit',
            serial_number: ont?.serial_number || null,
            status: 'installed',
            notes: ont ? `SN ONT: ${ont.serial_number}` : 'Modem ONT terpasang awal'
        },
        {
            id: 'fallback-dropcore',
            item_name: 'Kabel Dropcore 1 Core',
            quantity: 150,
            unit: 'meter',
            status: 'installed',
            notes: 'Kabel dropcore tarikan instalasi awal'
        },
        {
            id: 'fallback-fc',
            item_name: 'Fast Connector SC/UPC',
            quantity: 2,
            unit: 'pcs',
            status: 'installed',
            notes: 'Konektor optik ujung ODP & Roset'
        },
        {
            id: 'fallback-roset',
            item_name: 'Roset Optik 1 Core',
            quantity: 1,
            unit: 'pcs',
            status: 'installed',
            notes: 'Roset terminasi indoor dinding'
        },
        {
            id: 'fallback-patchcord',
            item_name: 'Patchcord SC-SC 3M',
            quantity: 1,
            unit: 'pcs',
            status: 'installed',
            notes: 'Kabel jumper roset ke modem ONT'
        }
    ];
});

const quickDamageChips = [
    'Kabel Putus / Terlindas Truk',
    'Redaman Drop / Klosur Banjir',
    'Konektor Pecah / Loss Tinggi',
    'ONT Mati Total / Terkena Petir',
    'Tergigit Hama Tikus / Terjepit',
    'Penggantian Jalur / Sambung Ulang'
];

const initReplacementForInstalledMaterial = (installedItem) => {
    const existing = materialReplacements.value.find(r => r.installed_material_id === installedItem.id);
    if (existing) return;

    materialReplacements.value.push({
        installed_material_id: installedItem.id,
        item_name: installedItem.item_name,
        material_id: installedItem.material_id || null,
        replacement_type: 'full',
        total_installed_qty: parseFloat(installedItem.quantity),
        quantity: parseFloat(installedItem.quantity),
        unit: installedItem.unit || 'pcs',
        condition: 'rusak_scrap',
        damage_reason: 'Kabel / material putus atau rusak di lapangan',
        replacement_material_id: installedItem.material_id || null,
        replacement_quantity: parseFloat(installedItem.quantity),
    });
    saveDraft();
};

const removeReplacement = (index) => {
    materialReplacements.value.splice(index, 1);
    saveDraft();
};

const findMatchingPreparedMaterial = (rep) => {
    if (!rep || !reportForm.materials || reportForm.materials.length === 0) return null;
    const repNameLower = (rep.item_name || '').toLowerCase();
    
    // 1. Direct match by material_id
    if (rep.material_id) {
        const found = reportForm.materials.find(m => m.material_id == rep.material_id);
        if (found) return found;
    }

    // 2. Keyword match
    const keywords = ['dropcore', 'fast connector', 'patchcord', 'roset', 'ont', 'modem', 'adapter', 'klem', 's-clamp', 'protection sleeve'];
    for (const kw of keywords) {
        if (repNameLower.includes(kw)) {
            const found = reportForm.materials.find(m => (m.name || '').toLowerCase().includes(kw));
            if (found) return found;
        }
    }

    // 3. Fallback match by first word
    const firstWord = repNameLower.split(' ')[0];
    if (firstWord && firstWord.length >= 3) {
        const found = reportForm.materials.find(m => (m.name || '').toLowerCase().includes(firstWord.toLowerCase()));
        if (found) return found;
    }

    return null;
};

const syncReplacementToPrepared = (rep, matched) => {
    if (!matched) return;
    rep.replacement_material_id = matched.material_id;
    // Set kuantitas terpakai pada bekal agar sesuai dengan yang dibongkar/diganti
    const qtyToUse = Math.min(parseFloat(matched.quantity_prepared) || 0, parseFloat(rep.quantity) || 0);
    matched.quantity_used = qtyToUse;
    calculateReturned(matched);
    saveDraft();
};

const openEmergencyRequestForReplacement = (rep) => {
    isRequestFormOpen.value = true;
    const emptyItem = materialRequestForm.items.find(i => !i.item_name || !i.item_name.trim());
    if (emptyItem) {
        emptyItem.item_name = rep.item_name;
        emptyItem.quantity = parseFloat(rep.quantity) || 1;
        emptyItem.unit = rep.unit || 'pcs';
    } else {
        addRequestItem(rep.item_name, parseFloat(rep.quantity) || 1, rep.unit || 'pcs');
    }
    if (!materialRequestForm.notes) {
        materialRequestForm.notes = `Kebutuhan mendesak pergantian material rusak (${rep.item_name}) di lokasi pelanggan`;
    }
    saveDraft();
};

const saveNewInstalledMaterial = () => {
    if (!selectedTicket.value || !newInstalledMatForm.item_name) return;

    newInstalledMatForm.post(`/pgs/${selectedTicket.value.id}/installed-materials`, {
        preserveScroll: true,
        onSuccess: () => {
            newInstalledMatForm.reset();
            isAddInstalledMatModalOpen.value = false;
            const fresh = props.tickets?.data?.find(t => t.id === selectedTicket.value?.id);
            if (fresh) {
                selectedTicket.value = fresh;
            }
        }
    });
};

// Photo Previews
const photoBeforePreview = ref(null);
const photoAfterPreview = ref(null);

const reportForm = useForm({
    action_taken: '',
    optical_power_before: '',
    optical_power_after: '',
    start_time: '',
    end_time: '',
    resolution_notes: '',
    photo_before: null,
    photo_after: null,
    ont_replacement_action: 'installed',
    ont_replacement_notes: '',
    materials: [],
});

const performSearch = () => {
    router.get('/pgs/laporan', {
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
    
    // Reset end_time when starting/restarting
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

// ── Draft Persistence (localStorage) ───────────────────────────
const getDraftKey = (ticketId) => `pgs_report_draft_${ticketId}`;

const saveDraft = () => {
    if (!selectedTicket.value) return;
    try {
        const draft = {
            ticket_id: selectedTicket.value.id,
            action_taken: reportForm.action_taken,
            optical_power_before: reportForm.optical_power_before,
            optical_power_after: reportForm.optical_power_after,
            start_time: reportForm.start_time,
            end_time: reportForm.end_time,
            resolution_notes: reportForm.resolution_notes,
            ont_replacement_action: reportForm.ont_replacement_action,
            ont_replacement_notes: reportForm.ont_replacement_notes,
            started_at: startedAt.value,
            is_working: isWorking.value,
            materials: reportForm.materials.map(m => ({
                pgs_material_id: m.pgs_material_id,
                material_id: m.material_id,
                name: m.name,
                unit: m.unit,
                quantity_prepared: m.quantity_prepared,
                quantity_used: m.quantity_used,
                quantity_returned: m.quantity_returned,
            })),
            additional_materials: additionalMaterials.value.map(am => ({
                material_id: am.material_id,
                quantity: am.quantity,
                unit: am.unit,
                notes: am.notes,
            })),
            incident_coordinates: incidentCoordinates.value,
            material_replacements: materialReplacements.value,
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
    additionalMaterials.value = [];
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
            reportForm.optical_power_before = draft.optical_power_before || '';
            reportForm.optical_power_after = draft.optical_power_after || '';
            reportForm.start_time = draft.start_time || '';
            reportForm.end_time = draft.end_time || '';
            reportForm.resolution_notes = draft.resolution_notes || '';
            reportForm.ont_replacement_action = draft.ont_replacement_action || 'installed';
            reportForm.ont_replacement_notes = draft.ont_replacement_notes || '';

            if (Array.isArray(draft.materials) && draft.materials.length > 0) {
                reportForm.materials = draft.materials;
            } else {
                initPreparedMaterials(ticket);
            }

            if (Array.isArray(draft.additional_materials)) {
                additionalMaterials.value = draft.additional_materials;
            }

            if (Array.isArray(draft.incident_coordinates)) {
                incidentCoordinates.value = draft.incident_coordinates;
            } else if (Array.isArray(ticket.incident_coordinates)) {
                incidentCoordinates.value = JSON.parse(JSON.stringify(ticket.incident_coordinates));
            }

            if (Array.isArray(draft.material_replacements)) {
                materialReplacements.value = draft.material_replacements;
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
        reportForm.optical_power_before = ticket.optical_power_before || '';
        reportForm.optical_power_after = ticket.optical_power_after || '';
        reportForm.start_time = ticket.start_time || '';
        reportForm.end_time = ticket.end_time || '';
        reportForm.resolution_notes = ticket.resolution_notes || '';
        reportForm.ont_replacement_action = ticket.ont_replacement_action || 'installed';
        reportForm.ont_replacement_notes = ticket.ont_replacement_notes || '';

        initPreparedMaterials(ticket);

        if (Array.isArray(ticket.incident_coordinates) && ticket.incident_coordinates.length > 0) {
            incidentCoordinates.value = JSON.parse(JSON.stringify(ticket.incident_coordinates));
        } else {
            incidentCoordinates.value = [];
        }
        materialReplacements.value = [];

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
};

const initPreparedMaterials = (ticket) => {
    if (ticket.materials && ticket.materials.length > 0) {
        reportForm.materials = ticket.materials.map(m => {
            const prep = parseFloat(m.quantity_prepared) || 0;
            const used = m.quantity_used !== null ? parseFloat(m.quantity_used) : prep;
            return {
                pgs_material_id: m.id,
                material_id: m.material_id,
                name: m.material?.name || 'Material',
                unit: m.unit || m.material?.unit || '',
                quantity_prepared: prep,
                quantity_used: used,
                quantity_returned: Math.max(0, prep - used),
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

// Additional Materials Lapangan
const addAdditionalMaterial = () => {
    additionalMaterials.value.push({
        material_id: '',
        quantity: 1,
        unit: 'pcs',
        notes: '',
    });
    saveDraft();
};

const removeAdditionalMaterial = (index) => {
    additionalMaterials.value.splice(index, 1);
    saveDraft();
};

const onAdditionalMaterialSelected = (item) => {
    const mat = (props.materials || []).find(m => m.id == item.material_id);
    if (mat) {
        item.unit = mat.unit || 'pcs';
    }
    saveDraft();
};

// ── Pengajuan Kebutuhan Tambahan ke Ka. Teknisi ─────────────────
const sendMaterialRequest = () => {
    if (!selectedTicket.value) return;

    const validItems = materialRequestForm.items.filter(i => i.item_name && i.item_name.trim());
    if (validItems.length === 0) {
        alert('Silakan tuliskan setidaknya 1 nama / kebutuhan material yang dibutuhkan.');
        return;
    }

    for (const item of validItems) {
        if (!item.quantity || parseFloat(item.quantity) <= 0) {
            alert(`Jumlah kebutuhan untuk material '${item.item_name}' harus lebih dari 0.`);
            return;
        }
    }

    if (!materialRequestForm.notes || !materialRequestForm.notes.trim()) {
        alert('Mohon tulis alasan atau kondisi kebutuhan di lapangan.');
        return;
    }

    materialRequestForm.items = validItems.map(i => ({
        item_name: i.item_name.trim(),
        quantity: parseFloat(i.quantity),
        unit: i.unit || 'pcs',
    }));

    materialRequestForm.post(`/pgs/${selectedTicket.value.id}/request-material`, {
        preserveScroll: true,
        onSuccess: () => {
            materialRequestForm.reset();
            materialRequestForm.items = [{ item_name: '', quantity: 1, unit: 'meter' }];
            materialRequestForm.notes = '';
            isRequestFormOpen.value = false;
            // Sinkronkan selectedTicket dengan data terbaru di props
            const fresh = props.tickets?.data?.find(t => t.id === selectedTicket.value?.id);
            if (fresh) {
                selectedTicket.value = fresh;
            }
        }
    });
};

const sendOntRequest = () => {
    if (!ontRequestForm.requested_ont_notes) {
        alert('Harap isi alasan pergantian ONT!');
        return;
    }
    ontRequestForm.post(`/pgs/${selectedTicket.value.id}/request-ont`, {
        preserveScroll: true,
        onSuccess: () => {
            ontRequestForm.reset();
            isOntRequestFormOpen.value = false;
            const fresh = props.tickets?.data?.find(t => t.id === selectedTicket.value?.id);
            if (fresh) {
                selectedTicket.value = fresh;
            }
        }
    });
};

const cancelPreparedOnt = (ticket) => {
    if (!confirm('Apakah Anda yakin ingin membatalkan pergantian ONT ini? Unit ONT baru akan otomatis dikembalikan ke stok Inventory Gudang.')) {
        return;
    }
    router.post(`/pgs/${ticket.id}/cancel-ont`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            const fresh = props.tickets?.data?.find(t => t.id === selectedTicket.value?.id);
            if (fresh) {
                selectedTicket.value = fresh;
            }
        }
    });
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

// Photo Change Handlers
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

    reportForm.transform((data) => ({
        ...data,
        additional_materials: additionalMaterials.value.filter(am => am.material_id && parseFloat(am.quantity) > 0),
        incident_coordinates: incidentCoordinates.value.filter(c => c.latitude && c.longitude),
        material_replacements: materialReplacements.value.filter(r => r.item_name && parseFloat(r.quantity) > 0),
    })).post(`/pgs/${selectedTicket.value.id}/report`, {
        forceFormData: true,
        onSuccess: () => {
            if (selectedTicket.value) {
                localStorage.removeItem(getDraftKey(selectedTicket.value.id));
            }
            stopLiveTimer();
            showReportModal.value = false;
            selectedTicket.value = null;
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

// Deep Watcher to Auto-Save to localStorage
watch([
    () => reportForm.action_taken,
    () => reportForm.optical_power_before,
    () => reportForm.optical_power_after,
    () => reportForm.start_time,
    () => reportForm.end_time,
    () => reportForm.resolution_notes,
    () => reportForm.materials,
    () => additionalMaterials.value,
], () => {
    if (showReportModal.value && selectedTicket.value) {
        saveDraft();
    }
}, { deep: true });

// Sinkronkan data jika ada material baru disetujui oleh Ka. Teknisi
watch(() => props.tickets, (newVal) => {
    if (selectedTicket.value && newVal?.data) {
        const fresh = newVal.data.find(t => t.id === selectedTicket.value.id);
        if (fresh) {
            selectedTicket.value = fresh;
            if (fresh.materials && fresh.materials.length > 0) {
                fresh.materials.forEach(fm => {
                    const existing = reportForm.materials.find(rm => rm.pgs_material_id === fm.id || rm.material_id === fm.material_id);
                    const prep = parseFloat(fm.quantity_prepared) || 0;
                    if (existing) {
                        existing.quantity_prepared = prep;
                        calculateReturned(existing);
                    } else {
                        const used = fm.quantity_used !== null ? parseFloat(fm.quantity_used) : prep;
                        reportForm.materials.push({
                            pgs_material_id: fm.id,
                            material_id: fm.material_id,
                            name: fm.material?.name || 'Material',
                            unit: fm.unit || fm.material?.unit || '',
                            quantity_prepared: prep,
                            quantity_used: used,
                            quantity_returned: Math.max(0, prep - used),
                        });
                    }
                });
            }
        }
    }
}, { deep: true });

onUnmounted(() => {
    stopLiveTimer();
});
</script>
