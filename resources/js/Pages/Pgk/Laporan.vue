<template>
    <AppLayout title="Laporan Penanganan PGK" subtitle="Pelaporan Hasil Pengerjaan Lapangan & Material Terpakai oleh Teknisi">
        <div class="space-y-6">


            <!-- Toolbar & Filter -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4">
                <div v-if="can('pgk_filter')" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
                    <div class="relative flex-1 sm:w-80">
                        <input 
                            type="text" 
                            v-model="search" 
                            placeholder="Cari No. PGK, pelanggan, tindakan..." 
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
                                        Keluhan: "{{ ticket.reported_issue }}"
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
                                            v-if="can('pgk_btn_report')"
                                            @click="openReportModal(ticket)" 
                                            class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-500/20 transition-all flex items-center gap-1.5"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            <span>{{ ticket.status === 'completed' ? 'Edit Laporan' : 'Input Laporan' }}</span>
                                        </button>

                                        <a :href="`/pgk/${ticket.id}/print`" target="_blank" title="Cetak Berita Acara & SPK" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="tickets.data.length === 0">
                                <td colspan="5" class="p-8 text-center text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Tidak ada tiket penanganan komplain yang dijadwalkan saat ini.
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
                                class="px-3 py-1 text-xs rounded-lg transition-colors border"
                                :class="link.active ? 'bg-emerald-600 text-white border-emerald-600 font-bold' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-200'"
                            />
                            <span v-else v-html="link.label" class="px-3 py-1 text-xs rounded-lg text-gray-300 border border-transparent"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════ MODAL INPUT LAPORAN PENANGANAN PGK ════════════ -->
        <div v-if="showReportModal" class="fixed inset-0 z-50 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
            <div class="bg-white rounded-3xl max-w-3xl w-full shadow-2xl border border-gray-100 flex flex-col max-h-[92vh] overflow-hidden my-auto animate-fade-in-up">
                <!-- Modal Header -->
                <div class="px-5 py-3.5 bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 text-white flex justify-between items-center shrink-0">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-black text-base sm:text-lg">Laporan Penanganan Lapangan PGK</h3>
                            <span class="px-2 py-0.5 rounded-md bg-white/20 text-white font-mono text-xs font-bold">#{{ selectedTicket?.ticket_number }}</span>
                        </div>
                        <p class="text-[11px] text-emerald-100 mt-0.5">Alur simpel 3 langkah: Tindakan & Tikor → Material & Bekal → Foto & Selesai</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span v-if="hasSavedDraft" class="hidden sm:inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-amber-400/20 text-amber-200 border border-amber-300/30 text-[10px] font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                            Draft Tersimpan
                        </span>
                        <button @click="closeReportModal" class="text-white/80 hover:text-white text-2xl font-bold leading-none p-1 rounded-lg hover:bg-white/10 transition-colors">&times;</button>
                    </div>
                </div>

                <!-- Alert Draft Restored (Compact) -->
                <div v-if="hasSavedDraft" class="px-5 py-2 bg-amber-50 border-b border-amber-200 text-amber-900 flex items-center justify-between text-xs shrink-0">
                    <span class="flex items-center gap-1.5 font-medium">
                        <span>💾</span> <b>Draft Aktif:</b> Data dan timer otomatis dimuat dari sesi sebelumnya.
                    </span>
                    <button type="button" @click="clearDraft" class="text-rose-600 hover:text-rose-800 font-bold hover:underline text-[11px] cursor-pointer">
                        ✕ Buang Draft
                    </button>
                </div>

                <!-- Ringkasan Pelanggan (Compact Header Bar) -->
                <div class="px-5 py-2.5 bg-emerald-50/60 border-b border-emerald-100/80 text-xs shrink-0">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 truncate">
                            <span class="font-black text-gray-900 text-sm truncate">{{ selectedTicket?.customer?.name }}</span>
                            <span class="px-1.5 py-0.5 rounded bg-white text-gray-700 border text-[10px] font-mono shrink-0">{{ selectedTicket?.customer?.customer_code }}</span>
                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold shrink-0">Area: {{ selectedTicket?.area?.name || selectedTicket?.customer?.area_model?.name || '-' }}</span>
                        </div>
                        <button type="button" @click="showCustomerDetail = !showCustomerDetail" class="text-[11px] text-emerald-700 hover:text-emerald-900 font-bold flex items-center gap-0.5 shrink-0 cursor-pointer">
                            <span>{{ showCustomerDetail ? 'Tutup Detail ▴' : 'Detail Pelanggan ▾' }}</span>
                        </button>
                    </div>
                    <!-- Detail Pelanggan Expandable -->
                    <div v-if="showCustomerDetail" class="mt-2 pt-2 border-t border-emerald-200/60 text-gray-700 space-y-1 animate-fade-in-up">
                        <div><strong>Keluhan Pelanggan:</strong> <span class="text-red-700 font-medium">"{{ selectedTicket?.reported_issue }}"</span></div>
                        <div v-if="selectedTicket?.customer?.ont?.odp?.name"><strong>ODP:</strong> {{ selectedTicket.customer.ont.odp.name }} · <strong>Alamat:</strong> {{ selectedTicket.customer?.address || '-' }}</div>
                        <div v-if="selectedTicket?.lead_notes" class="text-amber-900 font-medium"><strong>Instruksi Ka. Teknisi:</strong> "{{ selectedTicket?.lead_notes }}"</div>
                    </div>
                    <!-- Quick Badges Row -->
                    <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                        <span class="px-2 py-0.5 rounded-md bg-white text-purple-900 border border-purple-200 text-[10px] font-bold shadow-2xs">
                            📍 {{ incidentCoordinates.length }} Tikor
                        </span>
                        <span class="px-2 py-0.5 rounded-md bg-white text-teal-900 border border-teal-200 text-[10px] font-bold shadow-2xs">
                            📦 {{ activeCustomerMaterials.length }} Terpasang
                        </span>
                        <span v-if="materialReplacements.length > 0" class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-900 border border-amber-300 text-[10px] font-bold animate-pulse shadow-2xs">
                            🔄 {{ materialReplacements.length }} Diganti
                        </span>
                        <span v-if="countReturnedMaterials > 0" class="px-2 py-0.5 rounded-md bg-blue-100 text-blue-900 border border-blue-300 text-[10px] font-bold shadow-2xs">
                            ↩️ {{ countReturnedMaterials }} Retur
                        </span>
                    </div>
                </div>

                <!-- ════════ 3-STEP TAB NAVIGATION BAR ════════ -->
                <div class="px-5 py-2 bg-gray-50 border-b border-gray-200 flex items-center justify-between gap-1.5 shrink-0">
                    <div class="grid grid-cols-3 gap-1.5 w-full">
                        <button 
                            type="button" 
                            @click="activeReportTab = 'tindakan'"
                            :class="activeReportTab === 'tindakan' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200 font-medium'"
                            class="py-2 px-2 rounded-xl text-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer text-center truncate"
                        >
                            <span>🛠️</span>
                            <span class="truncate">1. Tindakan & Tikor</span>
                        </button>

                        <button 
                            type="button" 
                            @click="activeReportTab = 'material'"
                            :class="activeReportTab === 'material' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200 font-medium'"
                            class="py-2 px-2 rounded-xl text-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer text-center truncate relative"
                        >
                            <span>📦</span>
                            <span class="truncate">2. Material & Bekal</span>
                            <span v-if="materialReplacements.length > 0" class="w-2 h-2 rounded-full bg-amber-400 absolute top-1 right-1"></span>
                        </button>

                        <button 
                            type="button" 
                            @click="activeReportTab = 'selesai'"
                            :class="activeReportTab === 'selesai' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200 font-medium'"
                            class="py-2 px-2 rounded-xl text-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer text-center truncate"
                        >
                            <span>📸</span>
                            <span class="truncate">3. Foto & Selesai</span>
                        </button>
                    </div>
                </div>

                <!-- ════════ FORM WRAPPER WITH BODY AND FOOTER ════════ -->
                <form @submit.prevent="submitReport" class="flex flex-col flex-1 overflow-hidden min-h-0">
                    <div class="p-5 space-y-4 overflow-y-auto flex-1">
                        <!-- ═════════════════════════════════════════════════════ -->
                        <!-- TAB 1: TINDAKAN, REDAMAN, & MULTI-TIKOR               -->
                        <!-- ═════════════════════════════════════════════════════ -->
                        <div v-show="activeReportTab === 'tindakan'" class="space-y-4">
                            <!-- 1. Jam Mulai & Live Stopwatch (Compact Card) -->
                            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-800 uppercase tracking-wider mb-1">
                                        ⏱️ Jam Mulai Pengerjaan Lapangan <span class="text-red-500">*</span>
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input 
                                            type="time" 
                                            v-model="reportForm.start_time" 
                                            :readonly="isWorking"
                                            class="rounded-xl border-gray-300 py-1.5 px-3 text-xs font-bold text-gray-800 bg-white focus:border-emerald-500" 
                                            required
                                        />
                                        <button 
                                            type="button" 
                                            @click="startWork" 
                                            :class="isWorking ? 'bg-emerald-700 hover:bg-emerald-800' : 'bg-emerald-600 hover:bg-emerald-700'"
                                            class="px-3 py-1.5 text-white font-bold text-xs rounded-xl shadow-sm transition-all flex items-center gap-1.5 shrink-0 cursor-pointer"
                                        >
                                            <span>{{ reportForm.start_time ? 'Reset' : '▶ Mulai Pengerjaan' }}</span>
                                        </button>
                                    </div>
                                </div>
                                <!-- Live Running Stopwatch -->
                                <div v-if="isWorking" class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-100 border border-emerald-300 text-emerald-800 self-start sm:self-auto">
                                    <span class="w-2 h-2 rounded-full bg-emerald-600 animate-ping"></span>
                                    <span class="text-[10px] font-bold uppercase">Berjalan:</span>
                                    <span class="font-mono text-xs font-black text-emerald-950">{{ formatSecondsToClock(elapsedSeconds) }}</span>
                                </div>
                            </div>

                            <!-- 2. Tindakan Perbaikan Lapangan -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                                    Tindakan Perbaikan Lapangan <span class="text-red-500">*</span>
                                </label>
                                <textarea 
                                    v-model="reportForm.action_taken" 
                                    rows="3" 
                                    placeholder="Contoh: Pemotongan kabel dropcore tertekuk di tiang ODP, splicing ulang core 1 ODP-BTR-04, dan penggantian adapter SC-UPC."
                                    class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-xs" 
                                    required
                                ></textarea>
                            </div>

                            <!-- 3. Redaman Optik (dBm) -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Redaman Sebelum (dBm)</label>
                                    <input 
                                        type="text" 
                                        v-model="reportForm.optical_power_before" 
                                        placeholder="Contoh: -31.50 dBm / LOS"
                                        class="w-full rounded-xl border-gray-200 focus:border-emerald-500 py-1.5 text-xs" 
                                    />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Redaman Sesudah (dBm)</label>
                                    <input 
                                        type="text" 
                                        v-model="reportForm.optical_power_after" 
                                        placeholder="Contoh: -18.40 dBm"
                                        class="w-full rounded-xl border-gray-200 focus:border-emerald-500 py-1.5 text-xs font-bold text-emerald-700" 
                                    />
                                </div>
                            </div>

                            <!-- 4. Titik Koordinat Insiden (Multi-Tikor) -->
                            <div class="p-3.5 rounded-2xl bg-purple-50/60 border border-purple-200/80 space-y-2.5">
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <label class="text-xs font-bold text-purple-950 uppercase tracking-wider flex items-center gap-1.5">
                                        <span>📍</span> Titik Koordinat Insiden Lapangan (Tikor)
                                    </label>
                                    <div class="flex items-center gap-1.5">
                                        <button 
                                            type="button" 
                                            @click="getCurrentGpsLocation()" 
                                            :disabled="isFetchingGps"
                                            class="px-2.5 py-1 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold shadow-sm transition-all flex items-center gap-1 cursor-pointer disabled:opacity-50"
                                        >
                                            <span v-if="isFetchingGps" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                            <span v-else>📡</span>
                                            <span>{{ isFetchingGps ? 'Mencari...' : 'Ambil GPS Sekarang' }}</span>
                                        </button>
                                        <button 
                                            type="button" 
                                            @click="addIncidentCoordinate('Titik Insiden Baru', 'incident_point')" 
                                            class="px-2 py-1 bg-white hover:bg-purple-100 text-purple-800 border border-purple-300 rounded-lg text-xs font-semibold cursor-pointer"
                                        >
                                            + Tambah
                                        </button>
                                    </div>
                                </div>

                                <!-- GPS Status notification -->
                                <div v-if="gpsStatusMessage" class="p-2 rounded-lg bg-purple-100/90 text-[11px] text-purple-900 font-medium flex items-center justify-between">
                                    <span>{{ gpsStatusMessage }}</span>
                                    <button type="button" @click="gpsStatusMessage = ''" class="text-purple-700 font-bold ml-2">✕</button>
                                </div>

                                <!-- Quick preset chips -->
                                <div class="flex flex-wrap items-center gap-1">
                                    <span class="text-[9px] font-bold text-purple-900 uppercase">Preset:</span>
                                    <button 
                                        type="button" 
                                        v-for="chip in quickTikorChips" 
                                        :key="chip.label"
                                        @click="addIncidentCoordinate(chip.label, chip.category)"
                                        class="px-2 py-0.5 rounded text-[10px] bg-white hover:bg-purple-100 text-purple-900 border border-purple-200 shadow-2xs cursor-pointer"
                                    >
                                        + {{ chip.label }}
                                    </button>
                                </div>

                                <!-- Empty State -->
                                <div v-if="incidentCoordinates.length === 0" class="p-2.5 rounded-xl bg-white border border-dashed border-purple-200 text-center text-xs text-purple-600 italic">
                                    Belum ada koordinat dicatat. Klik <b>"Ambil GPS Sekarang"</b> saat berada di titik insiden.
                                </div>

                                <!-- Coordinates List -->
                                <div v-else class="space-y-2">
                                    <div 
                                        v-for="(coord, cIdx) in incidentCoordinates" 
                                        :key="coord.id || cIdx"
                                        class="p-2.5 bg-white rounded-xl border border-purple-200 shadow-2xs space-y-1.5"
                                    >
                                        <div class="flex items-center justify-between gap-1.5">
                                            <input 
                                                type="text" 
                                                v-model="coord.label" 
                                                placeholder="Label titik (cth: Kabel Putus Tiang 04...)"
                                                class="text-xs font-bold text-gray-900 border-0 border-b border-gray-200 focus:border-purple-500 focus:ring-0 p-0.5 flex-1 bg-transparent"
                                            />
                                            <div class="flex items-center gap-1 shrink-0">
                                                <button type="button" @click="getCurrentGpsLocation(coord)" title="Perbarui titik ini dengan GPS saat ini" class="p-1 text-purple-600 hover:bg-purple-50 rounded text-xs font-bold border border-purple-200 cursor-pointer">📡 GPS</button>
                                                <a v-if="coord.latitude && coord.longitude" :href="`https://www.google.com/maps?q=${coord.latitude},${coord.longitude}`" target="_blank" class="p-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded text-xs font-bold border border-emerald-200">🗺️</a>
                                                <button type="button" @click="removeIncidentCoordinate(cIdx)" class="p-1 text-rose-500 hover:bg-rose-50 rounded text-xs font-bold cursor-pointer">✕</button>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 text-xs">
                                            <input type="number" step="any" v-model="coord.latitude" placeholder="Lat (-6.xxx)" class="text-xs rounded-lg border-gray-300 font-mono py-1 px-2" />
                                            <input type="number" step="any" v-model="coord.longitude" placeholder="Lng (106.xxx)" class="text-xs rounded-lg border-gray-300 font-mono py-1 px-2" />
                                            <input type="text" v-model="coord.address_reference" placeholder="Patokan Tiang/Lokasi" class="text-xs rounded-lg border-gray-300 py-1 px-2" />
                                            <input type="text" v-model="coord.notes" placeholder="Catatan teknis" class="text-xs rounded-lg border-gray-300 py-1 px-2" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ═════════════════════════════════════════════════════ -->
                        <!-- TAB 2: REKONSILIASI MATERIAL, BEKAL & PERGANTIAN      -->
                        <!-- ═════════════════════════════════════════════════════ -->
                        <div v-show="activeReportTab === 'material'" class="space-y-4">
                            <!-- 2A. Material Bekal Ka. Teknisi (Stok Gudang yang Dibawa) -->
                            <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200/80 space-y-3 shadow-2xs">
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <div>
                                        <div class="text-xs font-black text-amber-950 uppercase tracking-wider flex items-center gap-1.5">
                                            <span>🎒</span> A. Bekal Material dari Ka. Teknisi ({{ reportForm.materials.length }} Jenis)
                                        </div>
                                        <p class="text-[11px] text-amber-800">
                                            Sesuaikan kuantitas terpakai. Sisa material otomatis dibuatkan transaksi retur kembali ke stok gudang.
                                        </p>
                                    </div>
                                    <button 
                                        type="button" 
                                        @click="isRequestFormOpen = !isRequestFormOpen" 
                                        class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-xs font-bold shadow-sm transition-all flex items-center gap-1 shrink-0 cursor-pointer"
                                    >
                                        <span>{{ isRequestFormOpen ? '✕ Tutup Form' : '📢 + Butuh Tambahan Dadakan?' }}</span>
                                    </button>
                                </div>

                                <!-- Form Permintaan Tambahan Dadakan ke Ka. Teknisi jika dibuka -->
                                <div v-if="isRequestFormOpen" class="p-3.5 rounded-xl bg-white border border-amber-300 space-y-2.5 animate-fade-in-up">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-amber-900">📝 Form Kebutuhan Dadakan ke Ka. Teknisi:</span>
                                        <span class="text-[10px] text-gray-500">Ka. Teknisi akan approve & alokasikan stok gudang</span>
                                    </div>
                                    <div class="space-y-2">
                                        <div v-for="(item, idx) in materialRequestForm.items" :key="'req-item-' + idx" class="grid grid-cols-12 gap-1.5">
                                            <input type="text" v-model="item.item_name" placeholder="Nama material (cth: Dropcore 50m...)" class="col-span-6 text-xs rounded-lg border-gray-300 py-1 px-2" />
                                            <input type="number" step="0.01" min="0.01" v-model="item.quantity" placeholder="Qty" class="col-span-3 text-xs rounded-lg border-gray-300 py-1 text-center font-bold" />
                                            <select v-model="item.unit" class="col-span-3 text-xs rounded-lg border-gray-300 py-1 px-1">
                                                <option value="meter">meter</option>
                                                <option value="pcs">pcs</option>
                                                <option value="rol">rol</option>
                                                <option value="set">set</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="flex gap-1.5 items-center">
                                        <input type="text" v-model="materialRequestForm.notes" placeholder="Alasan kebutuhan di lapangan..." class="flex-1 text-xs rounded-lg border-gray-300 py-1 px-2" />
                                        <button type="button" @click="sendMaterialRequest" :disabled="materialRequestForm.processing || validRequestItemsCount === 0 || !materialRequestForm.notes" class="px-3 py-1 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-lg text-xs disabled:opacity-50 cursor-pointer shrink-0">
                                            Kirim Permintaan
                                        </button>
                                    </div>
                                </div>

                                <!-- List Bekal Material Disiapkan -->
                                <div v-if="reportForm.materials.length === 0" class="p-3 rounded-xl bg-white border border-amber-200 text-xs text-gray-400 italic text-center">
                                    Tidak ada bekal material yang disiapkan pada tiket ini.
                                </div>

                                <div v-else class="space-y-2">
                                    <div 
                                        v-for="(m, idx) in reportForm.materials" 
                                        :key="'prep-' + idx" 
                                        class="p-2.5 rounded-xl bg-white border transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-2xs"
                                        :class="m.quantity_used == m.quantity_prepared ? 'border-emerald-300' : (m.quantity_used == 0 ? 'border-amber-300' : 'border-blue-300')"
                                    >
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5 truncate">
                                                <span class="font-bold text-gray-900 text-xs truncate">{{ m.name }}</span>
                                                <span class="text-[10px] text-gray-500">(Bawa: <b>{{ m.quantity_prepared }} {{ m.unit }}</b>)</span>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2 shrink-0">
                                            <!-- Stepper Terpakai -->
                                            <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden bg-white">
                                                <button type="button" @click="stepPreparedMaterial(m, -1)" class="px-2 py-0.5 text-gray-600 hover:bg-gray-100 font-bold text-xs select-none cursor-pointer">-</button>
                                                <input type="number" step="0.01" min="0" :max="m.quantity_prepared" v-model="m.quantity_used" @input="calculateReturned(m)" class="w-14 border-0 text-xs py-0.5 text-center font-black text-emerald-700 focus:ring-0 p-0" />
                                                <button type="button" @click="stepPreparedMaterial(m, 1)" class="px-2 py-0.5 text-gray-600 hover:bg-gray-100 font-bold text-xs select-none cursor-pointer">+</button>
                                            </div>

                                            <!-- Tombol Cepat All vs 0 -->
                                            <div class="flex gap-1">
                                                <button type="button" @click="setPreparedMaterialQty(m, m.quantity_prepared)" class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-emerald-50 text-emerald-700 border border-emerald-200 cursor-pointer" title="Pakai semua">All</button>
                                                <button type="button" @click="setPreparedMaterialQty(m, 0)" class="px-1.5 py-0.5 text-[9px] font-bold rounded bg-gray-50 text-gray-600 border border-gray-200 cursor-pointer" title="Retur semua">0</button>
                                            </div>

                                            <!-- Sisa Kembali -->
                                            <div class="px-2 py-0.5 text-center font-mono font-bold text-xs text-blue-800 bg-blue-50 rounded-lg border border-blue-200">
                                                ↩️ Sisa: {{ m.quantity_returned }} {{ m.unit }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2B. Material Terpasang di Pelanggan & Pergantian Bekas -->
                            <div class="p-4 rounded-2xl bg-teal-50/60 border border-teal-200/80 space-y-3 shadow-2xs">
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <div>
                                        <div class="text-xs font-black text-teal-950 uppercase tracking-wider flex items-center gap-1.5">
                                            <span>🔄</span> B. Material Terpasang di Pelanggan & Pergantian Bekas
                                        </div>
                                        <p class="text-[11px] text-teal-800">
                                            Pilih material yang rusak/diganti. Unit pengganti baru otomatis disinkronkan dengan bekal di atas.
                                        </p>
                                    </div>
                                    <button 
                                        type="button" 
                                        @click="isAddInstalledMatModalOpen = !isAddInstalledMatModalOpen" 
                                        class="px-2.5 py-1 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-xs font-bold shadow-sm cursor-pointer"
                                    >
                                        + Tambah Item Terpasang
                                    </button>
                                </div>

                                <!-- Form Tambah Item Terpasang Custom jika dibutuhkan -->
                                <div v-if="isAddInstalledMatModalOpen" class="p-3 bg-white rounded-xl border border-teal-300 space-y-2 animate-fade-in-up">
                                    <div class="grid grid-cols-12 gap-1.5 text-xs">
                                        <input type="text" v-model="newInstalledMatForm.item_name" placeholder="Nama item terpasang..." class="col-span-6 rounded-lg border-gray-300 py-1 px-2" />
                                        <input type="number" step="0.01" min="0.01" v-model="newInstalledMatForm.quantity" placeholder="Qty" class="col-span-2 rounded-lg border-gray-300 py-1 text-center font-bold" />
                                        <select v-model="newInstalledMatForm.unit" class="col-span-2 rounded-lg border-gray-300 py-1 px-1">
                                            <option value="pcs">pcs</option>
                                            <option value="meter">meter</option>
                                            <option value="unit">unit</option>
                                        </select>
                                        <button type="button" @click="saveNewInstalledMaterial" :disabled="!newInstalledMatForm.item_name" class="col-span-2 bg-teal-600 text-white rounded-lg font-bold text-xs disabled:opacity-50 cursor-pointer">Simpan</button>
                                    </div>
                                </div>

                                <!-- Grid Material Terpasang Aktif -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <div 
                                        v-for="inst in activeCustomerMaterials" 
                                        :key="'inst-' + inst.id"
                                        class="p-2.5 rounded-xl bg-white border border-teal-100 shadow-2xs flex items-center justify-between gap-2"
                                    >
                                        <div class="min-w-0">
                                            <div class="font-bold text-gray-900 text-xs truncate" :title="inst.item_name">{{ inst.item_name }}</div>
                                            <div class="text-[10px] text-gray-500 font-mono">
                                                {{ parseFloat(inst.quantity) }} {{ inst.unit }}
                                                <span v-if="inst.serial_number">· SN: {{ inst.serial_number }}</span>
                                            </div>
                                        </div>

                                        <button 
                                            type="button" 
                                            @click="initReplacementForInstalledMaterial(inst)"
                                            :class="materialReplacements.some(r => r.installed_material_id === inst.id) ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-teal-50 text-teal-700 hover:bg-teal-100 border-teal-200'"
                                            class="px-2 py-1 rounded-lg border text-[11px] font-bold transition-all flex items-center gap-1 shrink-0 cursor-pointer"
                                        >
                                            <span>🔄</span>
                                            <span>{{ materialReplacements.some(r => r.installed_material_id === inst.id) ? 'Sedang Diganti' : 'Ganti' }}</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Detail Form Pergantian (Jika ada item yang dipilih untuk diganti) -->
                                <div v-if="materialReplacements.length > 0" class="space-y-3 pt-2 border-t border-teal-200/80">
                                    <div class="text-xs font-bold text-amber-950 uppercase flex items-center gap-1">
                                        <span>⚠️</span> Rincian Material yang Diganti & Dibawa ke Gudang ({{ materialReplacements.length }} Item):
                                    </div>

                                    <div 
                                        v-for="(rep, rIdx) in materialReplacements" 
                                        :key="'rep-' + rIdx"
                                        class="p-3.5 rounded-2xl bg-white border-2 border-amber-300 shadow-sm space-y-2.5"
                                    >
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <span class="font-black text-gray-900 text-xs">📦 {{ rep.item_name }}</span>
                                                <span class="text-[11px] text-gray-500 ml-1">(Total Terpasang: {{ rep.total_installed_qty }} {{ rep.unit }})</span>
                                            </div>
                                            <button type="button" @click="removeReplacement(rIdx)" class="text-rose-500 hover:text-rose-700 text-xs font-bold cursor-pointer">✕ Batalkan</button>
                                        </div>

                                        <!-- Pilihan Mode: Ganti Seluruhnya vs Sebagian -->
                                        <div class="grid grid-cols-2 gap-2 text-xs">
                                            <label class="p-2 rounded-xl border flex items-center gap-1.5 cursor-pointer" :class="rep.replacement_type === 'full' ? 'bg-amber-50 border-amber-500 font-bold text-amber-950' : 'border-gray-200 text-gray-600'">
                                                <input type="radio" v-model="rep.replacement_type" value="full" @change="rep.quantity = rep.total_installed_qty; rep.replacement_quantity = rep.total_installed_qty" class="text-amber-600">
                                                <span>🔄 Ganti 100% Seluruhnya</span>
                                            </label>
                                            <label class="p-2 rounded-xl border flex items-center gap-1.5 cursor-pointer" :class="rep.replacement_type === 'partial' ? 'bg-amber-50 border-amber-500 font-bold text-amber-950' : 'border-gray-200 text-gray-600'">
                                                <input type="radio" v-model="rep.replacement_type" value="partial" class="text-amber-600">
                                                <span>✂️ Ganti Sebagian (Potong)</span>
                                            </label>
                                        </div>

                                        <!-- Qty Dibongkar & Kondisi Fisik -->
                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 text-xs">
                                            <div class="sm:col-span-3">
                                                <label class="block text-[9px] font-bold text-gray-600 uppercase mb-0.5">Jumlah Dibongkar *</label>
                                                <div class="flex items-center gap-1">
                                                    <input type="number" step="0.01" min="0.01" :max="rep.total_installed_qty" v-model="rep.quantity" class="w-full text-xs rounded-lg border-gray-300 font-black text-center py-1" required />
                                                    <span class="text-gray-500 font-bold text-xs">{{ rep.unit }}</span>
                                                </div>
                                            </div>

                                            <div class="sm:col-span-4">
                                                <label class="block text-[9px] font-bold text-gray-600 uppercase mb-0.5">Kondisi Fisik Bekas *</label>
                                                <select v-model="rep.condition" class="w-full text-xs rounded-lg border-gray-300 font-semibold py-1 px-1.5" required>
                                                    <option value="rusak_scrap">🔴 Rusak / Scrap (Afkir)</option>
                                                    <option value="normal_reusable">🟢 Layak Pakai (Bisa Dipakai)</option>
                                                    <option value="perlu_cek">🟡 Perlu Cek Lab / Servis</option>
                                                </select>
                                            </div>

                                            <div class="sm:col-span-5">
                                                <label class="block text-[9px] font-bold text-gray-600 uppercase mb-0.5">Alasan Kerusakan *</label>
                                                <input type="text" v-model="rep.damage_reason" placeholder="Putus / redaman drop / pecah..." class="w-full text-xs rounded-lg border-gray-300 py-1 px-2" required />
                                            </div>
                                        </div>

                                        <!-- Sumber Unit Baru Pengganti (Smart Bridge) -->
                                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200/90 text-xs">
                                            <!-- Opsi 1: Jika cocok dengan bekal Ka. Teknisi -->
                                            <div v-if="findMatchingPreparedMaterial(rep)" class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                                <div class="text-[11px] text-emerald-800">
                                                    <span>✓ Tersedia di Bekal Ka. Teknisi: <b>{{ findMatchingPreparedMaterial(rep).name }}</b> (Tercatat terpakai: {{ findMatchingPreparedMaterial(rep).quantity_used }} {{ findMatchingPreparedMaterial(rep).unit }})</span>
                                                </div>
                                                <button type="button" @click="syncReplacementToPrepared(rep, findMatchingPreparedMaterial(rep))" class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded text-[10px] shadow-sm flex items-center gap-1 cursor-pointer shrink-0">
                                                    <span>⚡ Sinkron Terpakai Bekal (Sync Bagian A)</span>
                                                </button>
                                            </div>
                                            <!-- Opsi 2: Jika belum ada di bekal -> Tombol Ajukan Dadakan -->
                                            <div v-else class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                                <span class="text-[11px] text-amber-800">⚠️ Belum ada bekal yang cocok untuk item ini.</span>
                                                <button type="button" @click="openEmergencyRequestForReplacement(rep)" class="px-2 py-0.5 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded text-[10px] shadow-sm flex items-center gap-1 cursor-pointer shrink-0">
                                                    <span>📢 + Ajukan Dadakan ke Ka. Teknisi</span>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="text-[10px] text-amber-800 bg-amber-50/80 p-2 rounded-lg flex items-center justify-between">
                                            <span>📦 Barang bekas (<b>{{ rep.quantity }} {{ rep.unit }}</b>) otomatis berstatus <b>Menunggu Serah Terima</b> ke Gudang / Ka. Teknisi.</span>
                                            <span class="font-bold text-[9px] uppercase px-1.5 py-0.2 bg-white rounded border border-amber-200">{{ rep.condition === 'normal_reusable' ? '🟢 Restock Gudang' : '🔴 Scrap / Afkir' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2C. Pergantian Unit ONT (Jika Ada) -->
                            <div class="p-3.5 rounded-2xl bg-indigo-50/70 border border-indigo-200 text-xs space-y-2">
                                <div class="flex items-center justify-between flex-wrap gap-1.5">
                                    <span class="font-bold text-indigo-950 flex items-center gap-1">
                                        <span>📶</span> C. Pergantian Unit ONT Pelanggan
                                    </span>
                                    <span v-if="selectedTicket?.ont_replacement_status === 'prepared'" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-300">
                                        📦 ONT Baru Disiapkan: {{ selectedTicket.prepared_ont?.serial_number }}
                                    </span>
                                </div>

                                <div v-if="selectedTicket?.ont_replacement_status === 'prepared'" class="space-y-1.5 pt-1">
                                    <div class="grid grid-cols-2 gap-2">
                                        <label class="p-2 rounded-xl border flex items-center gap-1.5 cursor-pointer" :class="reportForm.ont_replacement_action === 'installed' ? 'bg-white border-emerald-500 font-bold text-emerald-900 shadow-2xs' : 'border-gray-200 text-gray-600'">
                                            <input type="radio" v-model="reportForm.ont_replacement_action" value="installed" class="text-emerald-600">
                                            <span>🟢 Berhasil Dipasang</span>
                                        </label>
                                        <label class="p-2 rounded-xl border flex items-center gap-1.5 cursor-pointer" :class="reportForm.ont_replacement_action === 'cancelled' ? 'bg-white border-rose-500 font-bold text-rose-900 shadow-2xs' : 'border-gray-200 text-gray-600'">
                                            <input type="radio" v-model="reportForm.ont_replacement_action" value="cancelled" class="text-rose-600">
                                            <span>🔴 Retur Gudang (Tidak Jadi)</span>
                                        </label>
                                    </div>
                                </div>
                                <div v-else class="text-[11px] text-gray-500 flex items-center justify-between">
                                    <span>ONT terpasang: {{ selectedTicket?.customer?.ont?.serial_number || '-' }}</span>
                                    <button v-if="!selectedTicket?.ont_replacement_status || selectedTicket?.ont_replacement_status === 'none'" type="button" @click="isOntRequestFormOpen = !isOntRequestFormOpen" class="text-indigo-600 hover:text-indigo-800 font-bold underline cursor-pointer">
                                        + Ajukan Ganti ONT
                                    </button>
                                </div>
                                <!-- Form Request Ganti ONT jika dibuka -->
                                <div v-if="isOntRequestFormOpen" class="pt-2 border-t border-indigo-200 flex gap-1.5">
                                    <input type="text" v-model="ontRequestForm.requested_ont_notes" placeholder="Alasan ganti ONT (cth: mati tersambar petir)..." class="flex-1 text-xs rounded-lg border-indigo-300 py-1 px-2" />
                                    <button type="button" @click="sendOntRequest" :disabled="ontRequestForm.processing || !ontRequestForm.requested_ont_notes" class="px-3 py-1 bg-indigo-600 text-white rounded-lg font-bold text-xs cursor-pointer disabled:opacity-50">Kirim</button>
                                </div>
                            </div>
                        </div>

                        <!-- ═════════════════════════════════════════════════════ -->
                        <!-- TAB 3: FOTO, CATATAN SELESAI, & SUBMIT               -->
                        <!-- ═════════════════════════════════════════════════════ -->
                        <div v-show="activeReportTab === 'selesai'" class="space-y-4">
                            <!-- 1. Upload Foto Bukti Lapangan -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="p-3 rounded-2xl bg-gray-50 border border-gray-200 space-y-1.5">
                                    <label class="block text-xs font-bold text-gray-700 uppercase">📷 Foto Sebelum Perbaikan</label>
                                    <input 
                                        type="file" 
                                        accept="image/*"
                                        @change="onPhotoBeforeChange"
                                        class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100" 
                                    />
                                    <div v-if="photoBeforePreview" class="mt-1">
                                        <img :src="photoBeforePreview" class="h-20 w-auto rounded-lg border border-gray-200 object-cover" />
                                    </div>
                                </div>

                                <div class="p-3 rounded-2xl bg-gray-50 border border-gray-200 space-y-1.5">
                                    <label class="block text-xs font-bold text-gray-700 uppercase">📷 Foto Sesudah Perbaikan</label>
                                    <input 
                                        type="file" 
                                        accept="image/*"
                                        @change="onPhotoAfterChange"
                                        class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100" 
                                    />
                                    <div v-if="photoAfterPreview" class="mt-1">
                                        <img :src="photoAfterPreview" class="h-20 w-auto rounded-lg border border-gray-200 object-cover" />
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Jam Selesai & Durasi Pengerjaan -->
                            <div class="p-3.5 rounded-2xl bg-gradient-to-r from-blue-50/70 via-indigo-50/60 to-blue-50/70 border border-blue-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                                <div>
                                    <label class="block text-[11px] font-black text-blue-950 uppercase tracking-wider mb-1">
                                        🏁 Jam Selesai Pengerjaan Lapangan <span class="text-red-500">*</span>
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input 
                                            type="time" 
                                            v-model="reportForm.end_time" 
                                            class="rounded-xl border-gray-300 py-1.5 px-3 text-xs font-bold text-gray-800 bg-white focus:border-blue-500" 
                                            required
                                        />
                                        <button 
                                            type="button" 
                                            @click="finishWork" 
                                            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all flex items-center gap-1 shrink-0 cursor-pointer"
                                        >
                                            <span>{{ reportForm.end_time ? 'Update Jam' : '✓ Selesai Sekarang' }}</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="px-3 py-1.5 rounded-xl bg-white border border-blue-100 shadow-2xs flex items-center gap-2 self-start sm:self-auto">
                                    <span class="text-[11px] text-gray-500">Total Durasi:</span>
                                    <span class="text-xs font-black text-blue-900">{{ calculatedDurationText }}</span>
                                </div>
                            </div>

                            <!-- 3. Catatan Akhir Penanganan -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Catatan Akhir Penanganan</label>
                                <textarea 
                                    v-model="reportForm.resolution_notes" 
                                    rows="2" 
                                    placeholder="Contoh: Koneksi internet pelanggan telah diuji coba streaming dan speedtest normal. Pelanggan telah konfirmasi puas."
                                    class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-xs" 
                                ></textarea>
                            </div>

                            <!-- 4. Ringkasan Rekonsiliasi Otomatis Sebelum Submit -->
                            <div class="p-3 rounded-2xl bg-emerald-50/80 border border-emerald-200 text-xs text-emerald-950 space-y-1.5">
                                <div class="font-bold flex items-center gap-1.5 text-emerald-900">
                                    <span>⚡</span> Ringkasan Otomatis Saat Disimpan:
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px] pt-1">
                                    <div class="p-2 rounded-xl bg-white border border-emerald-100">
                                        <span class="text-gray-400 block text-[9px] uppercase font-bold">Waktu Mulai - Selesai</span>
                                        <span class="font-bold text-gray-800">{{ reportForm.start_time || '-' }} &rarr; {{ reportForm.end_time || '-' }}</span>
                                    </div>
                                    <div class="p-2 rounded-xl bg-white border border-emerald-100">
                                        <span class="text-gray-400 block text-[9px] uppercase font-bold">Bekal Terpakai</span>
                                        <span class="font-bold text-emerald-700">{{ countUsedMaterials }} jenis material</span>
                                    </div>
                                    <div class="p-2 rounded-xl bg-white border border-emerald-100">
                                        <span class="text-gray-400 block text-[9px] uppercase font-bold">Retur Gudang (IN-PGK)</span>
                                        <span class="font-bold text-blue-700">{{ countReturnedMaterials }} jenis sisa</span>
                                    </div>
                                    <div class="p-2 rounded-xl bg-white border border-emerald-100">
                                        <span class="text-gray-400 block text-[9px] uppercase font-bold">Serah Terima Bekas</span>
                                        <span class="font-bold text-amber-800">{{ materialReplacements.length }} item</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ════════ FIXED FOOTER (ALWAYS VISIBLE AT BOTTOM) ════════ -->
                    <div class="px-5 py-3 bg-white border-t border-gray-200 flex items-center justify-between gap-3 shrink-0">
                        <div>
                            <button 
                                v-if="activeReportTab !== 'tindakan'" 
                                type="button" 
                                @click="goToPrevTab" 
                                class="px-3.5 py-2 border border-gray-300 rounded-xl text-xs font-bold text-gray-700 hover:bg-gray-50 flex items-center gap-1 cursor-pointer transition-all shadow-2xs"
                            >
                                <span>⬅ Sebelumnya</span>
                            </button>
                            <button 
                                v-else 
                                type="button" 
                                @click="closeReportModal" 
                                class="px-3.5 py-2 border border-gray-200 rounded-xl text-xs font-semibold text-gray-500 hover:bg-gray-50 cursor-pointer"
                            >
                                Batal
                            </button>
                        </div>

                        <div class="flex items-center gap-2">
                            <button 
                                v-if="activeReportTab !== 'selesai'" 
                                type="button" 
                                @click="goToNextTab" 
                                class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-600/20 flex items-center gap-1.5 cursor-pointer transition-all"
                            >
                                <span>{{ activeReportTab === 'tindakan' ? 'Lanjut ke Material ➔' : 'Lanjut ke Foto & Selesai ➔' }}</span>
                            </button>
                            <button 
                                v-else 
                                type="submit" 
                                :disabled="reportForm.processing || !reportForm.action_taken" 
                                class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black shadow-lg shadow-emerald-600/20 disabled:opacity-50 flex items-center gap-2 cursor-pointer transition-all"
                            >
                                <svg v-if="reportForm.processing" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ reportForm.processing ? 'Menyimpan...' : '💾 Simpan Laporan & Selesaikan PGK' }}</span>
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
const activeReportTab = ref('tindakan');
const showCustomerDetail = ref(false);

const goToNextTab = () => {
    if (activeReportTab.value === 'tindakan') {
        if (!reportForm.action_taken || !reportForm.action_taken.trim()) {
            alert('Mohon isi Tindakan Perbaikan Lapangan terlebih dahulu!');
            return;
        }
        activeReportTab.value = 'material';
    } else if (activeReportTab.value === 'material') {
        activeReportTab.value = 'selesai';
    }
};

const goToPrevTab = () => {
    if (activeReportTab.value === 'selesai') {
        activeReportTab.value = 'material';
    } else if (activeReportTab.value === 'material') {
        activeReportTab.value = 'tindakan';
    }
};

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

    newInstalledMatForm.post(`/pgk/${selectedTicket.value.id}/installed-materials`, {
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
    router.get('/pgk/laporan', {
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
const getDraftKey = (ticketId) => `pgk_report_draft_${ticketId}`;

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
                pgk_material_id: m.pgk_material_id,
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
            active_tab: activeReportTab.value,
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
                pgk_material_id: m.id,
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

    materialRequestForm.post(`/pgk/${selectedTicket.value.id}/request-material`, {
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
    ontRequestForm.post(`/pgk/${selectedTicket.value.id}/request-ont`, {
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
    router.post(`/pgk/${ticket.id}/cancel-ont`, {}, {
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
    })).post(`/pgk/${selectedTicket.value.id}/report`, {
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
                    const existing = reportForm.materials.find(rm => rm.pgk_material_id === fm.id || rm.material_id === fm.material_id);
                    const prep = parseFloat(fm.quantity_prepared) || 0;
                    if (existing) {
                        existing.quantity_prepared = prep;
                        calculateReturned(existing);
                    } else {
                        const used = fm.quantity_used !== null ? parseFloat(fm.quantity_used) : prep;
                        reportForm.materials.push({
                            pgk_material_id: fm.id,
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
