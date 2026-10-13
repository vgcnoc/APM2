<template>
    <AppLayout title="Laporan Restorasi Jalur (PGJ)" subtitle="Pelaporan Hasil Pengerjaan Lapangan, Pengukuran Core FO & Material Terpakai oleh Teknisi">
        <div class="space-y-6">
            <!-- Nav Tabs Alur PGJ -->
            <div class="flex items-center gap-2 border-b border-gray-200 pb-2 overflow-x-auto">
                <Link href="/pgj" class="px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 transition-all shrink-0 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200">
                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span>1. Temuan & Segmen Jalur (Admin)</span>
                </Link>
                <Link href="/pgj/jadwal" class="px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 transition-all shrink-0 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>2. Jadwal & Material Jalur (Ka. Teknisi)</span>
                </Link>
                <Link href="/pgj/laporan" class="px-4 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all shrink-0 bg-emerald-600 text-white shadow-sm shadow-emerald-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>3. Laporan Restorasi (Teknisi)</span>
                </Link>
            </div>

            <!-- Toolbar & Filter -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
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
                                        <span class="font-extrabold text-[10px] px-2 py-0.5 rounded uppercase" :class="getSegmentBadgeClass(ticket.segment_type)">
                                            {{ ticket.segment_type }}
                                        </span>
                                        <span v-if="ticket.odc" class="text-rose-700 font-semibold">ODC: {{ ticket.odc.name }}</span>
                                        <span v-if="ticket.odp" class="text-amber-700 font-semibold">ODP: {{ ticket.odp.name }}</span>
                                    </div>
                                    <div class="mt-1 text-xs text-red-600 font-bold">
                                        🚨 {{ ticket.affected_customers_count }} Pelanggan Terdampak
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
                                    </div>
                                    <div v-else class="text-xs text-gray-400 italic">
                                        Menunggu tim teknisi melaporkan hasil penyambungan & restorasi...
                                    </div>
                                </td>

                                <!-- 5. Aksi Teknisi -->
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button 
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

        <!-- ════════════ MODAL INPUT LAPORAN RESTORASI JALUR ════════════ -->
        <div v-if="showReportModal" class="fixed inset-0 z-50 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-gray-100 overflow-hidden animate-fade-in-up">
                <div class="px-6 py-5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white flex justify-between items-center">
                    <div>
                        <h3 class="font-black text-lg">Laporan Restorasi Lapangan PGJ #{{ selectedTicket?.ticket_number }}</h3>
                        <p class="text-xs text-emerald-100 mt-0.5">Catat tindakan perbaikan, detail core FO, optical loss, foto, dan material terpakai</p>
                    </div>
                    <button @click="showReportModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <form @submit.prevent="submitReport" class="p-6 space-y-4 max-h-[85vh] overflow-y-auto">
                    <!-- Ringkasan Gangguan & Dampak -->
                    <div class="p-3.5 rounded-2xl bg-emerald-50/70 border border-emerald-100 text-xs text-emerald-950 space-y-1">
                        <div class="font-bold flex justify-between">
                            <span>Titik: {{ selectedTicket?.location_name }}</span>
                            <span class="uppercase font-bold text-emerald-800">Segmen: {{ selectedTicket?.segment_type }}</span>
                        </div>
                        <div>Kerusakan: <span class="text-red-700 font-medium">"{{ selectedTicket?.reported_issue }}"</span></div>
                        <div v-if="selectedTicket?.lead_notes" class="text-amber-800 font-semibold">
                            Instruksi Ka. Teknisi: "{{ selectedTicket?.lead_notes }}"
                        </div>
                    </div>

                    <!-- Tindakan Restorasi Fisik -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tindakan Restorasi Fisik Lapangan <span class="text-red-500">*</span></label>
                        <textarea 
                            v-model="reportForm.action_taken" 
                            rows="2" 
                            placeholder="Contoh: Penarikan span kabel FO 24 core baru sepanjang 65 meter, penggantian closure dome di tiang no 12, dan penyambungan (splicing) seluruh core."
                            class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm" 
                            required
                        ></textarea>
                    </div>

                    <!-- Detail Core FO yang Diperbaiki -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Detail Core FO / Tube yang Disambung</label>
                        <input 
                            type="text" 
                            v-model="reportForm.core_details" 
                            placeholder="Contoh: Core 1-8 tube biru active (ODC-02), Core 9-12 tube orange standby spare"
                            class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm" 
                        />
                    </div>

                    <!-- Optical Loss Sebelum & Sesudah -->
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

                    <!-- Jam Kerja Restorasi -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Jam Mulai Pengerjaan</label>
                            <input 
                                type="time" 
                                v-model="reportForm.start_time" 
                                class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm" 
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Jam Selesai Restorasi</label>
                            <input 
                                type="time" 
                                v-model="reportForm.end_time" 
                                class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm" 
                            />
                        </div>
                    </div>

                    <!-- Rekonsiliasi Material FO Terpakai & Kembali -->
                    <div v-if="reportForm.materials.length > 0" class="space-y-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase">Rekonsiliasi Material FO Terpakai</label>
                        <div class="space-y-2">
                            <div v-for="(m, idx) in reportForm.materials" :key="idx" class="p-3 rounded-xl bg-gray-50 border border-gray-200 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 text-xs">
                                <div class="flex-1">
                                    <div class="font-bold text-gray-800">{{ m.name }}</div>
                                    <div class="text-[11px] text-gray-500">Disiapkan: <strong class="text-gray-900">{{ m.quantity_prepared }} {{ m.unit }}</strong></div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 uppercase">Terpakai:</label>
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            min="0" 
                                            :max="m.quantity_prepared"
                                            v-model="m.quantity_used" 
                                            @input="calculateReturned(m)"
                                            class="w-20 rounded-lg border-gray-200 text-xs py-1 text-center font-bold text-emerald-700 focus:border-emerald-500" 
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 uppercase">Sisa Gudang:</label>
                                        <div class="w-20 py-1 text-center font-bold text-blue-700 bg-blue-50 rounded-lg border border-blue-200">
                                            {{ m.quantity_returned }} {{ m.unit }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="text-[11px] text-gray-400 italic">* Sisa material/kabel FO yang tidak terpakai akan otomatis dikembalikan ke stok gudang.</p>
                    </div>

                    <!-- Upload Foto Lapangan -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Foto Bukti Kerusakan Fisik</label>
                            <input 
                                type="file" 
                                accept="image/*"
                                @change="onPhotoBeforeChange"
                                class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100" 
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Foto Hasil Restorasi / Splicing</label>
                            <input 
                                type="file" 
                                accept="image/*"
                                @change="onPhotoAfterChange"
                                class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100" 
                            />
                        </div>
                    </div>

                    <!-- Catatan Akhir Restorasi -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Catatan Akhir Restorasi & Hasil Uji Coba</label>
                        <textarea 
                            v-model="reportForm.resolution_notes" 
                            rows="2" 
                            placeholder="Contoh: Splicing 12 core selesai dengan loss rata-rata 0.04 dB. Seluruh ONT pelanggan di ODC telah online kembali dan traffic berjalan normal."
                            class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm" 
                        ></textarea>
                    </div>

                    <div class="pt-3 border-t flex justify-end gap-2">
                        <button type="button" @click="showReportModal = false" class="px-4 py-2 border rounded-xl text-gray-600 text-sm font-semibold hover:bg-gray-50">Batal</button>
                        <button 
                            type="submit" 
                            :disabled="reportForm.processing" 
                            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold shadow-md shadow-emerald-500/20 disabled:opacity-50"
                        >
                            {{ reportForm.processing ? 'Menyimpan...' : 'Simpan Restorasi & Nyatakan Pulih' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    tickets: Object,
    areas: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const filterArea = ref(props.filters?.area_id || '');

const showReportModal = ref(false);
const selectedTicket = ref(null);

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
    materials: [],
});

const performSearch = () => {
    router.get('/pgj/laporan', {
        search: search.value,
        area_id: filterArea.value,
    }, { preserveState: true, replace: true });
};

const openReportModal = (ticket) => {
    selectedTicket.value = ticket;
    reportForm.reset();

    reportForm.action_taken = ticket.action_taken || '';
    reportForm.core_details = ticket.core_details || '';
    reportForm.optical_loss_before = ticket.optical_loss_before || '';
    reportForm.optical_loss_after = ticket.optical_loss_after || '';
    reportForm.start_time = ticket.start_time || '';
    reportForm.end_time = ticket.end_time || '';
    reportForm.resolution_notes = ticket.resolution_notes || '';

    if (ticket.materials && ticket.materials.length > 0) {
        reportForm.materials = ticket.materials.map(m => {
            const prep = parseFloat(m.quantity_prepared) || 0;
            const used = m.quantity_used !== null ? parseFloat(m.quantity_used) : prep;
            return {
                pgj_material_id: m.id,
                name: m.material?.name || 'Material FO',
                unit: m.unit || m.material?.unit || '',
                quantity_prepared: prep,
                quantity_used: used,
                quantity_returned: Math.max(0, prep - used),
            };
        });
    } else {
        reportForm.materials = [];
    }

    showReportModal.value = true;
};

const calculateReturned = (item) => {
    const prep = parseFloat(item.quantity_prepared) || 0;
    const used = parseFloat(item.quantity_used) || 0;
    item.quantity_returned = Math.max(0, prep - used);
};

const onPhotoBeforeChange = (e) => {
    reportForm.photo_before = e.target.files[0] || null;
};

const onPhotoAfterChange = (e) => {
    reportForm.photo_after = e.target.files[0] || null;
};

const submitReport = () => {
    if (!selectedTicket.value) return;
    reportForm.post(`/pgj/${selectedTicket.value.id}/report`, {
        forceFormData: true,
        onSuccess: () => {
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

const getSegmentBadgeClass = (segment) => {
    switch (segment) {
        case 'backbone': return 'bg-purple-100 text-purple-800 border-purple-200';
        case 'odc': return 'bg-rose-100 text-rose-800 border-rose-200';
        case 'odp': return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'distribution': return 'bg-cyan-100 text-cyan-800 border-cyan-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};
</script>
