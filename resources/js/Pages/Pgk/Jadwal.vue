<template>
    <AppLayout title="Jadwal & Material PGK" subtitle="Penjadwalan Teknisi & Penyiapan Material oleh Kepala Teknisi">
        <div class="space-y-6">


            <!-- Toolbar & Filter -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4">
                <div v-if="can('pgk_filter')" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
                    <div class="relative flex-1 sm:w-80">
                        <input 
                            type="text" 
                            v-model="search" 
                            placeholder="Cari No. PGK, pelanggan, pelapor, ODP..." 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-sm transition-all"
                            @keyup.enter="performSearch"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <select v-model="filterArea" @change="performSearch" class="px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-sm bg-gray-50/50">
                        <option value="">Semua Area / Wilayah</option>
                        <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>
                </div>
                
                <div class="text-xs text-gray-500 font-medium">
                    Tiket komplain yang dieskalasi admin untuk dijadwalkan teknisi & disiapkan materialnya.
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
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider" :class="ticket.status === 'scheduled' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-amber-50 text-amber-700 border border-amber-200'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="ticket.status === 'scheduled' ? 'bg-indigo-500' : 'bg-amber-500 animate-pulse'"></span>
                                        {{ ticket.status === 'scheduled' ? 'Sudah Dijadwalkan' : 'Menunggu Jadwal' }}
                                    </span>

                                    <div v-if="ticket.scheduled_date" class="mt-2 text-xs text-gray-700 font-semibold flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>{{ formatDate(ticket.scheduled_date) }}</span>
                                        <span class="text-gray-500 font-normal">({{ ticket.scheduled_time || 'Fleksibel' }})</span>
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
                                                <span class="font-bold text-gray-900 shrink-0">{{ parseFloat(m.quantity_prepared) }} {{ m.unit || m.material?.unit }}</span>
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
                                                <span>{{ mr.material?.name }}: {{ parseFloat(mr.quantity) }} {{ mr.unit }}</span>
                                                <span v-if="mr.status === 'pending'">⏳</span>
                                                <span v-else-if="mr.status === 'approved'">✓</span>
                                                <span v-else-if="mr.status === 'rejected'">✗</span>
                                            </span>
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
                                            v-if="can('pgk_btn_assign')"
                                            @click="openOntPrepareModal(ticket)" 
                                            class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1 border shadow-sm shrink-0"
                                            :class="ticket.ont_replacement_status === 'requested' ? 'bg-indigo-600 hover:bg-indigo-700 text-white border-indigo-600 shadow-indigo-600/20 animate-pulse' : (ticket.ont_replacement_status === 'prepared' ? 'bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-gray-50 hover:bg-gray-100 text-gray-700 border-gray-200')"
                                            title="Siapkan Unit ONT Pengganti dari Stok Gudang"
                                        >
                                            <span>🔄</span>
                                            <span>{{ ticket.ont_replacement_status === 'prepared' ? 'ONT Disiapkan' : 'Siapkan ONT' }}</span>
                                        </button>

                                        <a :href="`/pgk/${ticket.id}/print`" target="_blank" title="Cetak SPK Tugas Lapangan" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="tickets.data.length === 0">
                                <td colspan="5" class="p-8 text-center text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Tidak ada tiket gangguan yang membutuhkan penjadwalan saat ini.
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
                    <div class="space-y-3">
                        <div 
                            v-for="mr in reviewTicket?.material_requests" 
                            :key="'rev-' + mr.id"
                            class="p-4 rounded-2xl border transition-all text-xs space-y-2.5"
                            :class="{
                                'bg-amber-50/70 border-amber-300 shadow-sm': mr.status === 'pending',
                                'bg-emerald-50/70 border-emerald-300': mr.status === 'approved',
                                'bg-rose-50/70 border-rose-300': mr.status === 'rejected'
                            }"
                        >
                            <div class="flex items-start justify-between gap-2 flex-wrap">
                                <div>
                                    <div class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                        <span>📦 {{ mr.material?.name || 'Material' }}</span>
                                        <span class="px-2 py-0.5 rounded-lg bg-white border font-mono font-black text-amber-700">
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

                            <!-- Alasan Teknisi -->
                            <div class="bg-white/90 p-3 rounded-xl border border-gray-200 text-xs">
                                <div class="font-bold text-gray-500 text-[10px] uppercase mb-0.5">Keterangan / Alasan Kebutuhan di Lapangan:</div>
                                <div class="text-gray-800 font-medium">"{{ mr.notes }}"</div>
                            </div>

                            <!-- Status Stok Gudang Saat Ini -->
                            <div class="flex items-center justify-between text-xs text-gray-600 bg-gray-50/80 px-3 py-2 rounded-xl border border-gray-100 flex-wrap gap-2">
                                <div class="flex items-center gap-1">
                                    <span>Sisa Stok di Gudang:</span>
                                    <b class="text-gray-900 font-mono">{{ parseFloat(mr.material?.stock || 0) }} {{ mr.unit }}</b>
                                </div>
                                <div v-if="parseFloat(mr.material?.stock || 0) < parseFloat(mr.quantity)" class="text-red-600 font-bold text-[11px]">
                                    ⚠️ Stok gudang kurang dari jumlah yang diminta!
                                </div>
                            </div>

                            <!-- Reviewer info if resolved -->
                            <div v-if="mr.status === 'approved'" class="text-emerald-800 text-[11px] font-semibold flex items-center gap-1">
                                ✓ Telah disetujui oleh <b>{{ mr.reviewer?.name || 'Ka. Teknisi' }}</b> pada {{ formatDate(mr.reviewed_at) }}. Transaksi pengeluaran barang telah dicatat dan stok gudang otomatis terpotong.
                            </div>
                            <div v-else-if="mr.status === 'rejected'" class="text-rose-800 text-[11px] font-semibold flex items-center gap-1">
                                ✗ Telah ditolak oleh <b>{{ mr.reviewer?.name || 'Ka. Teknisi' }}</b>: "{{ mr.rejection_reason || '-' }}"
                            </div>

                            <!-- Tombol Aksi Persetujuan jika Status PENDING -->
                            <div v-if="mr.status === 'pending'" class="pt-2 flex items-center justify-end gap-2 border-t border-amber-200/80">
                                <button 
                                    type="button" 
                                    @click="rejectRequest(reviewTicket, mr)" 
                                    class="px-3 py-1.5 rounded-xl border border-rose-300 bg-white hover:bg-rose-50 text-rose-700 text-xs font-bold transition-all shadow-sm"
                                >
                                    ✗ Tolak Permintaan
                                </button>
                                <button 
                                    type="button" 
                                    @click="approveRequest(reviewTicket, mr)" 
                                    class="px-4 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-all shadow-md shadow-emerald-600/20 flex items-center gap-1.5"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>✓ Setujui & Keluarkan Stok Gudang</span>
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
    filters: Object,
});

const search = ref(props.filters?.search || '');
const filterArea = ref(props.filters?.area_id || '');

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

const performSearch = () => {
    router.get('/pgk/jadwal', {
        search: search.value,
        area_id: filterArea.value,
    }, { preserveState: true, replace: true });
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
const openMaterialReviewModal = (ticket) => {
    reviewTicket.value = ticket;
    showMaterialReviewModal.value = true;
};

const approveRequest = (ticket, req) => {
    if (!confirm(`Setujui penambahan material ${parseFloat(req.quantity)} ${req.unit} ${req.material?.name}?\n\nStok gudang akan otomatis dipotong dan dialokasikan ke tiket ini.`)) {
        return;
    }
    router.post(`/pgk/${ticket.id}/material-requests/${req.id}/approve`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            const updated = props.tickets.data.find(t => t.id === ticket.id);
            if (updated) {
                reviewTicket.value = updated;
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
    router.post(`/pgk/${ticket.id}/material-requests/${req.id}/reject`, {
        rejection_reason: reason.trim()
    }, {
        preserveScroll: true,
        onSuccess: () => {
            const updated = props.tickets.data.find(t => t.id === ticket.id);
            if (updated) {
                reviewTicket.value = updated;
                if (selectedTicket.value?.id === ticket.id) {
                    selectedTicket.value = updated;
                }
            }
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
