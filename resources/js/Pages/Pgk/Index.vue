<template>
    <AppLayout title="Penanganan Gangguan Komplain (PGK)" subtitle="Penerimaan Komplain Pelanggan & Penanganan Jarak Jauh oleh Admin">
        <div class="space-y-6">


            <!-- Stats Overview Cards -->
            <div v-if="can('pgk_card_summary')" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <div @click="filterByStatus('all')" class="bg-white p-4 rounded-2xl border transition-all cursor-pointer hover:shadow-md" :class="activeStatus === 'all' ? 'border-sky-500 ring-2 ring-sky-100 shadow-sm' : 'border-gray-100'">
                    <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Total Komplain PGK</div>
                    <div class="text-2xl font-black text-gray-900 mt-1">{{ counts.all || 0 }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">Seluruh aduan pelanggan</div>
                </div>

                <div @click="filterByStatus('admin_handling')" class="bg-white p-4 rounded-2xl border transition-all cursor-pointer hover:shadow-md" :class="activeStatus === 'admin_handling' ? 'border-sky-500 ring-2 ring-sky-100 shadow-sm' : 'border-gray-100'">
                    <div class="text-[11px] font-bold text-sky-600 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-sky-500 animate-pulse"></span>
                        Ditangani Admin
                    </div>
                    <div class="text-2xl font-black text-sky-700 mt-1">{{ counts.admin_handling || 0 }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">Penanganan jarak jauh</div>
                </div>

                <div @click="filterByStatus('escalated_to_lead')" class="bg-white p-4 rounded-2xl border transition-all cursor-pointer hover:shadow-md" :class="activeStatus === 'escalated_to_lead' ? 'border-amber-500 ring-2 ring-amber-100 shadow-sm' : 'border-gray-100'">
                    <div class="text-[11px] font-bold text-amber-600 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        Eskalasi Ka. Teknisi
                    </div>
                    <div class="text-2xl font-black text-amber-700 mt-1">{{ counts.escalated_to_lead || 0 }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">Perlu dijadwalkan</div>
                </div>

                <div @click="filterByStatus('scheduled')" class="bg-white p-4 rounded-2xl border transition-all cursor-pointer hover:shadow-md" :class="activeStatus === 'scheduled' ? 'border-indigo-500 ring-2 ring-indigo-100 shadow-sm' : 'border-gray-100'">
                    <div class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        Dijadwalkan
                    </div>
                    <div class="text-2xl font-black text-indigo-700 mt-1">{{ counts.scheduled || 0 }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">Teknisi & material siap</div>
                </div>

                <div @click="filterByStatus('completed')" class="bg-white p-4 rounded-2xl border transition-all cursor-pointer hover:shadow-md col-span-2 sm:col-span-1" :class="activeStatus === 'completed' ? 'border-emerald-500 ring-2 ring-emerald-100 shadow-sm' : 'border-gray-100'">
                    <div class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Selesai
                    </div>
                    <div class="text-2xl font-black text-emerald-700 mt-1">{{ counts.completed || 0 }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">Komplain teratasi</div>
                </div>
            </div>

            <!-- Toolbar & Filter -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4">
                <div v-if="can('pgk_filter')" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1 flex-wrap">
                    <div class="relative flex-1 sm:w-72">
                        <input 
                            type="text" 
                            v-model="search" 
                            placeholder="Cari ID PGK, pelanggan, pelapor, kendala..." 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-sky-500 focus:ring-2 focus:ring-sky-100 text-sm transition-all"
                            @keyup.enter="performSearch"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <select v-model="filterPriority" @change="performSearch" class="px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-sky-500 focus:ring-2 focus:ring-sky-100 text-sm bg-gray-50/50">
                        <option value="">Semua Prioritas</option>
                        <option value="low">Rendah (Low)</option>
                        <option value="medium">Sedang (Medium)</option>
                        <option value="high">Tinggi (High)</option>
                        <option value="critical">Kritis (Critical)</option>
                    </select>

                    <select v-model="filterArea" @change="performSearch" class="px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-sky-500 focus:ring-2 focus:ring-sky-100 text-sm bg-gray-50/50">
                        <option value="">Semua Area / Wilayah</option>
                        <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>
                </div>

                <button v-if="can('pgk_btn_create')" @click="openCreateModal" class="bg-gradient-to-r from-sky-600 to-blue-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-md shadow-sky-500/20 hover:shadow-lg hover:shadow-sky-500/30 hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Terima Komplain Baru</span>
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
                                <th class="p-4">No. Tiket & Saluran</th>
                                <th class="p-4">Pelanggan & Pelapor</th>
                                <th class="p-4">Kategori & Keluhan</th>
                                <th class="p-4">Status & Alur</th>
                                <th class="p-4 text-center">Aksi Admin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-sky-50/20 transition-colors group">
                                <!-- 1. No Tiket & Saluran -->
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-sky-600 text-xs">{{ ticket.ticket_number }}</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border" :class="getPriorityBadgeClass(ticket.priority)">
                                            {{ ticket.priority }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1.5 mt-1.5 text-xs text-gray-600 font-medium">
                                        <span class="px-2 py-0.5 rounded-full bg-slate-100 border border-slate-200 text-slate-700 text-[10px] font-bold uppercase">
                                            {{ ticket.channel || 'WhatsApp' }}
                                        </span>
                                        <span class="text-[11px] text-gray-400">{{ formatDate(ticket.reported_at || ticket.created_at) }}</span>
                                    </div>
                                    <div class="text-[10px] text-gray-400 mt-1">
                                        Diterima: <span class="text-gray-600 font-medium">{{ ticket.creator?.name || 'Admin' }}</span>
                                    </div>
                                </td>

                                <!-- 2. Data Pelanggan & Pelapor -->
                                <td class="p-4">
                                    <div class="font-bold text-gray-900">
                                        {{ ticket.customer?.name || '-' }}
                                    </div>
                                    <div class="text-[11px] text-gray-500 flex flex-wrap items-center gap-1.5 mt-0.5">
                                        <span class="px-1.5 py-0.2 rounded bg-gray-100 border text-gray-700 font-mono text-[10px]">{{ ticket.customer?.customer_code }}</span>
                                        <span>· {{ ticket.customer?.phone || '-' }}</span>
                                    </div>
                                    <!-- Detail Pelapor jika beda dengan pelanggan -->
                                    <div class="mt-1.5 p-1.5 rounded-lg bg-gray-50 border border-gray-100 text-[11px] text-gray-600 flex items-center gap-1">
                                        <span class="text-gray-400 font-medium">Pelapor:</span>
                                        <strong class="text-gray-800">{{ ticket.complainant_name }}</strong>
                                        <span v-if="ticket.complainant_phone" class="text-gray-500">({{ ticket.complainant_phone }})</span>
                                    </div>
                                    <div class="text-[11px] text-gray-500 mt-1 truncate max-w-xs" :title="ticket.customer?.address">
                                        📍 {{ ticket.customer?.address || 'Alamat tidak ada' }}
                                    </div>
                                    <div class="text-[10px] text-sky-700 font-semibold mt-0.5">
                                        ODP: {{ ticket.customer?.ont?.odp?.name || '-' }} · Paket: {{ ticket.customer?.package?.name || '-' }}
                                    </div>
                                </td>

                                <!-- 3. Kategori & Keluhan -->
                                <td class="p-4">
                                    <span class="inline-block px-2.5 py-1 rounded-md text-xs font-semibold bg-gray-100 text-gray-800">
                                        {{ ticket.category }}
                                    </span>
                                    <p class="text-xs text-red-600 font-medium mt-1.5 line-clamp-2" :title="ticket.reported_issue">
                                        "{{ ticket.reported_issue }}"
                                    </p>
                                    <div v-if="ticket.admin_notes" class="mt-1.5 text-[11px] text-sky-800 bg-sky-50/80 p-2 rounded-lg border border-sky-100">
                                        <span class="font-bold text-sky-900">Penanganan Admin:</span> {{ ticket.admin_notes }}
                                    </div>
                                    <div v-if="ticket.escalation_reason" class="mt-1 text-[11px] text-amber-800 bg-amber-50/80 p-2 rounded-lg border border-amber-100">
                                        <span class="font-bold text-amber-900">Alasan Eskalasi:</span> {{ ticket.escalation_reason }}
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
                                        <div class="text-[10px] text-gray-400 font-bold uppercase">Teknisi:</div>
                                        <div class="font-semibold text-gray-800">{{ ticket.technicians.map(t => t.name).join(', ') }}</div>
                                    </div>

                                    <div v-if="ticket.scheduled_date" class="text-[11px] text-gray-500 mt-1">
                                        📅 {{ formatDate(ticket.scheduled_date) }} ({{ ticket.scheduled_time || 'Fleksibel' }})
                                    </div>

                                    <div v-if="ticket.status === 'completed'" class="mt-2 text-[11px] text-emerald-700 bg-emerald-50 px-2 py-1 rounded border border-emerald-100">
                                        ✓ Selesai: {{ ticket.resolved_at ? formatDate(ticket.resolved_at) : '-' }}
                                    </div>
                                </td>

                                <!-- 5. Aksi Admin -->
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                        <!-- Tombol Detail -->
                                        <button @click="openDetailModal(ticket)" title="Lihat Detail Komplain" class="p-2 text-gray-500 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>

                                        <!-- Aksi jika masih status admin_handling -->
                                        <template v-if="ticket.status === 'admin_handling'">
                                            <!-- Selesaikan Langsung (Jarak Jauh) -->
                                            <button v-if="can('pgk_btn_resolve_admin')" @click="openResolveAdminModal(ticket)" title="Selesaikan Jarak Jauh (Remotely)" class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs rounded-lg border border-emerald-200 transition-colors flex items-center gap-1">
                                                 <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                 <span>Selesaikan</span>
                                            </button>

                                            <!-- Eskalasi ke Kepala Teknisi -->
                                            <button v-if="can('pgk_btn_escalate')" @click="openEscalateModal(ticket)" title="Eskalasikan ke Kepala Teknisi Lapangan" class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 font-bold text-xs rounded-lg border border-amber-200 transition-colors flex items-center gap-1">
                                                 <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                                 <span>Eskalasi Ka. Teknisi</span>
                                            </button>
                                        </template>

                                        <!-- Cetak Surat Tugas / Berita Acara -->
                                        <a :href="`/pgk/${ticket.id}/print`" target="_blank" title="Cetak SPK & Berita Acara PGK" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
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
                                    Tidak ada tiket gangguan komplain yang ditemukan.
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
                                :class="link.active ? 'bg-sky-600 text-white border-sky-600 font-bold' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-200'"
                            />
                            <span v-else v-html="link.label" class="px-3 py-1 text-xs rounded-lg text-gray-300 border border-transparent"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════ MODAL 1: TERIMA KOMPLAIN BARU (ADMIN) ════════════ -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-gray-100 overflow-hidden animate-fade-in-up">
                <div class="px-6 py-5 bg-gradient-to-r from-sky-600 to-blue-600 text-white flex justify-between items-center">
                    <div>
                        <h3 class="font-black text-lg">Penerimaan Komplain Pelanggan (PGK)</h3>
                        <p class="text-xs text-sky-100 mt-0.5">Catat komplain yang masuk dari saluran WhatsApp, telepon, atau kantor</p>
                    </div>
                    <button @click="showCreateModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <form @submit.prevent="submitCreate" class="p-6 space-y-4 max-h-[85vh] overflow-y-auto">
                    <!-- Filter Area Pencarian Pelanggan -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Filter Area / Wilayah Pelanggan</label>
                        <select 
                            v-model="createForm.area_id" 
                            @change="onAreaChange" 
                            class="w-full rounded-xl border-gray-200 focus:border-sky-500 focus:ring-2 focus:ring-sky-100 text-sm bg-gray-50/50"
                        >
                            <option value="">-- Semua Area / Wilayah --</option>
                            <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>
                    </div>

                    <!-- Pilih Pelanggan -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Pilih Pelanggan <span class="text-red-500">*</span></label>
                        <select 
                            v-model="createForm.customer_id" 
                            @change="onCustomerChange"
                            class="w-full rounded-xl border-gray-200 focus:border-sky-500 focus:ring-2 focus:ring-sky-100 text-sm" 
                            required
                        >
                            <option value="">-- Pilih Pelanggan ({{ filteredCustomers.length }} Pelanggan) --</option>
                            <option v-for="c in filteredCustomers" :key="c.id" :value="c.id">
                                {{ c.name }} ({{ c.customer_code }}) - {{ c.address || 'Alamat tidak ada' }}
                            </option>
                        </select>

                        <!-- Info Ringkas Pelanggan Terpilih -->
                        <div v-if="selectedCustomerInfo" class="mt-2 p-3 rounded-xl bg-sky-50/70 border border-sky-100 text-xs text-sky-900 space-y-1">
                            <div class="font-bold flex justify-between">
                                <span>Paket: {{ selectedCustomerInfo.package?.name || '-' }}</span>
                                <span>Status ONT: {{ selectedCustomerInfo.ont?.status || 'N/A' }}</span>
                            </div>
                            <div class="flex flex-wrap items-center justify-between gap-1">
                                <div>ODP: {{ selectedCustomerInfo.ont?.odp?.name || '-' }} · SN: {{ selectedCustomerInfo.ont?.serial_number || '-' }}</div>
                                <div v-if="customerAreaName" class="font-semibold text-sky-700">📍 Area: {{ customerAreaName }}</div>
                            </div>
                            <div v-if="selectedCustomerInfo.ont?.rx_power" class="text-amber-700 font-semibold">
                                Redaman RX Terakhir: {{ selectedCustomerInfo.ont.rx_power }} dBm
                            </div>
                        </div>
                    </div>

                    <!-- Data Pelapor & Saluran Komplain -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Pelapor <span class="text-red-500">*</span></label>
                            <input 
                                type="text" 
                                v-model="createForm.complainant_name" 
                                placeholder="Nama orang yang melapor"
                                class="w-full rounded-xl border-gray-200 focus:border-sky-500 focus:ring-2 focus:ring-sky-100 text-sm" 
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">No. Telp / WA Pelapor</label>
                            <input 
                                type="text" 
                                v-model="createForm.complainant_phone" 
                                placeholder="08xxxxxxxxxx"
                                class="w-full rounded-xl border-gray-200 focus:border-sky-500 focus:ring-2 focus:ring-sky-100 text-sm" 
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Saluran Komplain <span class="text-red-500">*</span></label>
                            <select v-model="createForm.channel" class="w-full rounded-xl border-gray-200 focus:border-sky-500 focus:ring-2 focus:ring-sky-100 text-sm" required>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="telepon">Telepon / Call</option>
                                <option value="kantor">Datang ke Kantor</option>
                                <option value="sosial media">Sosial Media</option>
                                <option value="aplikasi">Aplikasi / Web Portal</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                    </div>

                    <!-- Kategori & Prioritas -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Kategori Gangguan <span class="text-red-500">*</span></label>
                            <select v-model="createForm.category" class="w-full rounded-xl border-gray-200 focus:border-sky-500 focus:ring-2 focus:ring-sky-100 text-sm" required>
                                <option value="LOS / Kabel Putus">LOS / Kabel Optik Putus (Lampu Merah)</option>
                                <option value="Redaman Tinggi / Drop">Redaman Tinggi / Drop Power</option>
                                <option value="ONT / Modem Mati">ONT / Modem Mati / Rusak Adaptor</option>
                                <option value="Internet Lambat / Lemot">Internet Lambat / Sering RTO</option>
                                <option value="Tidak Bisa Akses / Isolir">Tidak Bisa Browsing / Pembayaran / Isolir</option>
                                <option value="Konfigurasi / WiFi / Password">Lupa Sandi / Ganti Nama WiFi</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tingkat Prioritas <span class="text-red-500">*</span></label>
                            <select v-model="createForm.priority" class="w-full rounded-xl border-gray-200 focus:border-sky-500 focus:ring-2 focus:ring-sky-100 text-sm" required>
                                <option value="low">Rendah (Low)</option>
                                <option value="medium">Sedang (Medium)</option>
                                <option value="high">Tinggi (High)</option>
                                <option value="critical">Kritis (Critical/Mendesak)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Uraian Keluhan / Masalah -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Keluhan Pelanggan / Indikasi Masalah <span class="text-red-500">*</span></label>
                        <textarea 
                            v-model="createForm.reported_issue" 
                            rows="2" 
                            placeholder="Contoh: Lampu indikator LOS kedap-kedip merah sejak pagi, pelanggan tidak bisa WFH."
                            class="w-full rounded-xl border-gray-200 focus:border-sky-500 focus:ring-2 focus:ring-sky-100 text-sm"
                            required
                        ></textarea>
                    </div>

                    <!-- Catatan Penanganan Awal Admin -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Penanganan Awal oleh Admin (Jarak Jauh / Remotely)</label>
                        <textarea 
                            v-model="createForm.admin_notes" 
                            rows="2" 
                            placeholder="Contoh: Sudah dicek via OLT port 3, status ONT DyingGasp / LOS. Dicoba instruksikan cabut colok adaptor belum pulih."
                            class="w-full rounded-xl border-gray-200 focus:border-sky-500 focus:ring-2 focus:ring-sky-100 text-sm"
                        ></textarea>
                        <p class="text-[11px] text-gray-400 mt-1">Langkah teknis jarak jauh yang sudah dicoba admin sebelum tiket dieskalasi.</p>
                    </div>

                    <div class="pt-3 border-t flex justify-end gap-2">
                        <button type="button" @click="showCreateModal = false" class="px-4 py-2 border rounded-xl text-gray-600 text-sm font-semibold hover:bg-gray-50">Batal</button>
                        <button type="submit" :disabled="createForm.processing" class="px-5 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-sm font-bold shadow-md shadow-sky-500/20 disabled:opacity-50">
                            {{ createForm.processing ? 'Menyimpan...' : 'Simpan & Mulai Penanganan' }}
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
                        <h3 class="font-black text-lg">Selesaikan Komplain (Jarak Jauh)</h3>
                        <p class="text-xs text-emerald-100 mt-0.5">Kasus selesai tanpa perlu kunjungan teknisi lapangan</p>
                    </div>
                    <button @click="showResolveAdminModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <form @submit.prevent="submitResolveAdmin" class="p-6 space-y-4">
                    <div class="p-3 rounded-xl bg-gray-50 border border-gray-100 text-xs text-gray-600">
                        <div>No. Tiket: <strong class="text-gray-900">{{ selectedTicket?.ticket_number }}</strong></div>
                        <div>Pelanggan: <strong class="text-gray-900">{{ selectedTicket?.customer?.name }}</strong></div>
                        <div class="mt-1 text-red-600">Kendala: "{{ selectedTicket?.reported_issue }}"</div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Catatan Solusi Admin (Jarak Jauh) <span class="text-red-500">*</span></label>
                        <textarea 
                            v-model="resolveForm.admin_notes" 
                            rows="3" 
                            placeholder="Contoh: Berhasil dipulihkan dengan reboot port OLT / ganti konfigurasi PPPoE / restart ONT dari jarak jauh. Koneksi internet kembali aktif normal."
                            class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm"
                            required
                        ></textarea>
                    </div>

                    <div class="pt-3 border-t flex justify-end gap-2">
                        <button type="button" @click="showResolveAdminModal = false" class="px-4 py-2 border rounded-xl text-gray-600 text-sm font-semibold hover:bg-gray-50">Batal</button>
                        <button type="submit" :disabled="resolveForm.processing" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold shadow-md shadow-emerald-500/20 disabled:opacity-50">
                            {{ resolveForm.processing ? 'Menyimpan...' : 'Selesaikan Tiket Sekarang' }}
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
                        <h3 class="font-black text-lg">Eskalasi ke Kepala Teknisi</h3>
                        <p class="text-xs text-amber-100 mt-0.5">Teruskan tiket untuk penjadwalan teknisi & penyiapan material lapangan</p>
                    </div>
                    <button @click="showEscalateModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <form @submit.prevent="submitEscalate" class="p-6 space-y-4">
                    <div class="p-3 rounded-xl bg-gray-50 border border-gray-100 text-xs text-gray-600">
                        <div>No. Tiket: <strong class="text-gray-900">{{ selectedTicket?.ticket_number }}</strong></div>
                        <div>Pelanggan: <strong class="text-gray-900">{{ selectedTicket?.customer?.name }}</strong></div>
                        <div>Area: <strong class="text-gray-900">{{ selectedTicket?.area?.name || selectedTicket?.customer?.area_model?.name || '-' }}</strong></div>
                        <div class="mt-1 text-red-600">Kendala: "{{ selectedTicket?.reported_issue }}"</div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Alasan Eskalasi ke Ka. Teknisi <span class="text-red-500">*</span></label>
                        <textarea 
                            v-model="escalateForm.escalation_reason" 
                            rows="2" 
                            placeholder="Contoh: Diperlukan pengecekan kabel dropcore di tiang ODP, dicurigai kabel terputus oleh dahan pohon."
                            class="w-full rounded-xl border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-sm"
                            required
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Catatan Tambahan Admin</label>
                        <textarea 
                            v-model="escalateForm.admin_notes" 
                            rows="2" 
                            placeholder="Catatan tambahan untuk tim teknisi lapangan..."
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

        <!-- ════════════ MODAL 4: DETAIL LENGKAP TIKET PGK ════════════ -->
        <div v-if="showDetailModal" class="fixed inset-0 z-50 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-gray-100 overflow-hidden animate-fade-in-up">
                <div class="px-6 py-5 bg-gray-900 text-white flex justify-between items-center">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-black text-lg">Tiket {{ detailTicket?.ticket_number }}</h3>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border" :class="getPriorityBadgeClass(detailTicket?.priority)">
                                {{ detailTicket?.priority }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">Detail Lengkap Alur Penanganan Komplain Pelanggan</p>
                    </div>
                    <button @click="showDetailModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <div class="p-6 space-y-4 max-h-[80vh] overflow-y-auto text-sm">
                    <!-- Status Badge -->
                    <div class="flex items-center justify-between p-3 rounded-2xl bg-gray-50 border border-gray-100">
                        <span class="text-xs font-bold text-gray-500 uppercase">Status Terkini:</span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider" :class="getStatusBadgeClass(detailTicket?.status)">
                            <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(detailTicket?.status)"></span>
                            {{ getStatusLabel(detailTicket?.status) }}
                        </span>
                    </div>

                    <!-- Pelanggan & Pelapor -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3.5 rounded-2xl bg-sky-50/50 border border-sky-100">
                        <div>
                            <div class="text-[10px] font-bold uppercase text-sky-800">Pelanggan</div>
                            <div class="font-bold text-gray-900">{{ detailTicket?.customer?.name }} ({{ detailTicket?.customer?.customer_code }})</div>
                            <div class="text-xs text-gray-600">Telp: {{ detailTicket?.customer?.phone || '-' }}</div>
                            <div class="text-xs text-gray-600">Alamat: {{ detailTicket?.customer?.address || '-' }}</div>
                            <div class="text-xs text-sky-700 font-semibold mt-1">Paket: {{ detailTicket?.customer?.package?.name || '-' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold uppercase text-sky-800">Pelapor & Saluran</div>
                            <div class="font-bold text-gray-900">{{ detailTicket?.complainant_name }}</div>
                            <div class="text-xs text-gray-600">Telp Pelapor: {{ detailTicket?.complainant_phone || '-' }}</div>
                            <div class="text-xs text-gray-600">Saluran Aduan: <strong class="uppercase">{{ detailTicket?.channel || 'WhatsApp' }}</strong></div>
                            <div class="text-xs text-gray-500 mt-1">Waktu: {{ formatDate(detailTicket?.reported_at || detailTicket?.created_at) }}</div>
                        </div>
                    </div>

                    <!-- Kendala & Penanganan Admin -->
                    <div class="space-y-2">
                        <div class="p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <div class="text-[10px] font-bold uppercase text-gray-500">Kategori & Keluhan:</div>
                            <div class="font-bold text-gray-900 mt-0.5">{{ detailTicket?.category }}</div>
                            <div class="text-xs text-red-600 mt-1">"{{ detailTicket?.reported_issue }}"</div>
                        </div>

                        <div v-if="detailTicket?.admin_notes" class="p-3 rounded-xl bg-sky-50 border border-sky-100">
                            <div class="text-[10px] font-bold uppercase text-sky-700">Penanganan Jarak Jauh (Admin):</div>
                            <div class="text-xs text-sky-900 mt-0.5">{{ detailTicket?.admin_notes }}</div>
                        </div>

                        <div v-if="detailTicket?.escalation_reason" class="p-3 rounded-xl bg-amber-50 border border-amber-100">
                            <div class="text-[10px] font-bold uppercase text-amber-700">Alasan Eskalasi Lapangan:</div>
                            <div class="text-xs text-amber-900 mt-0.5">{{ detailTicket?.escalation_reason }}</div>
                        </div>
                    </div>

                    <!-- Jadwal & Teknisi -->
                    <div v-if="detailTicket?.status === 'scheduled' || detailTicket?.status === 'completed'" class="p-3.5 rounded-2xl bg-indigo-50/50 border border-indigo-100 space-y-1">
                        <div class="text-[10px] font-bold uppercase text-indigo-800">Jadwal & Teknisi Pelaksana:</div>
                        <div class="text-xs text-gray-700">
                            Tanggal: <strong>{{ formatDate(detailTicket?.scheduled_date) }}</strong> ({{ detailTicket?.scheduled_time || 'Fleksibel' }})
                        </div>
                        <div class="text-xs text-gray-700">
                            Teknisi: <strong>{{ detailTicket?.technicians?.map(t => t.name).join(', ') || '-' }}</strong>
                        </div>
                        <div v-if="detailTicket?.lead_notes" class="text-xs text-indigo-900 mt-1 italic">
                            Instruksi Ka. Teknisi: "{{ detailTicket?.lead_notes }}"
                        </div>
                    </div>

                    <!-- Laporan Hasil Pengerjaan -->
                    <div v-if="detailTicket?.status === 'completed'" class="p-3.5 rounded-2xl bg-emerald-50/50 border border-emerald-100 space-y-2">
                        <div class="text-[10px] font-bold uppercase text-emerald-800">Hasil Penanganan Selesai:</div>
                        <div class="text-xs text-gray-800">Tindakan: <strong>{{ detailTicket?.action_taken || '-' }}</strong></div>
                        <div class="text-xs text-gray-800">
                            Redaman: Sebelum <strong>{{ detailTicket?.optical_power_before || '-' }}</strong> | Sesudah <strong class="text-emerald-700">{{ detailTicket?.optical_power_after || '-' }}</strong>
                        </div>
                        <div v-if="detailTicket?.resolution_notes" class="text-xs text-gray-700">
                            Catatan Akhir: {{ detailTicket?.resolution_notes }}
                        </div>
                    </div>

                    <!-- Aksi Cetak -->
                    <div class="pt-3 border-t flex justify-end gap-2">
                        <a :href="`/pgk/${detailTicket?.id}/print`" target="_blank" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm">
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
    customers: Array,
    areas: Array,
    technicians: Array,
    counts: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const activeStatus = ref(props.filters?.status || 'all');
const filterPriority = ref(props.filters?.priority || '');
const filterArea = ref(props.filters?.area_id || '');

const showCreateModal = ref(false);
const showResolveAdminModal = ref(false);
const showEscalateModal = ref(false);
const showDetailModal = ref(false);

const selectedTicket = ref(null);
const detailTicket = ref(null);

const createForm = useForm({
    customer_id: '',
    area_id: '',
    complainant_name: '',
    complainant_phone: '',
    channel: 'whatsapp',
    category: 'LOS / Kabel Putus',
    priority: 'medium',
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

// Pelanggan terfilter berdasarkan area yang dipilih di form
const filteredCustomers = computed(() => {
    if (!props.customers || !props.customers.length) return [];
    if (!createForm.area_id) return props.customers;
    const selectedArea = props.areas?.find(a => String(a.id) === String(createForm.area_id));
    return props.customers.filter(c => {
        if (c.area_id && String(c.area_id) === String(createForm.area_id)) return true;
        if (c.customer_area_id && String(c.customer_area_id) === String(createForm.area_id)) return true;
        if (selectedArea && c.area && c.area.trim().toLowerCase() === selectedArea.name.trim().toLowerCase()) return true;
        if (selectedArea && c.area_model?.name && c.area_model.name.trim().toLowerCase() === selectedArea.name.trim().toLowerCase()) return true;
        return false;
    });
});

// Info pelanggan terpilih
const selectedCustomerInfo = computed(() => {
    if (!createForm.customer_id) return null;
    return (props.customers || []).find(c => c.id == createForm.customer_id);
});

const customerAreaName = computed(() => {
    if (!selectedCustomerInfo.value) return null;
    if (selectedCustomerInfo.value.area_model?.name) return selectedCustomerInfo.value.area_model.name;
    if (selectedCustomerInfo.value.area) return selectedCustomerInfo.value.area;
    const a = props.areas?.find(ar => String(ar.id) === String(selectedCustomerInfo.value.area_id || selectedCustomerInfo.value.customer_area_id));
    return a?.name || '-';
});

const onAreaChange = () => {
    if (createForm.customer_id && createForm.area_id) {
        const cust = props.customers?.find(c => c.id === createForm.customer_id);
        const selectedArea = props.areas?.find(a => String(a.id) === String(createForm.area_id));
        const matchesAreaId = cust && (String(cust.area_id) === String(createForm.area_id) || String(cust.customer_area_id) === String(createForm.area_id));
        const matchesAreaName = selectedArea && cust && (cust.area?.trim().toLowerCase() === selectedArea.name.trim().toLowerCase() || cust.area_model?.name?.trim().toLowerCase() === selectedArea.name.trim().toLowerCase());
        if (!matchesAreaId && !matchesAreaName) {
            createForm.customer_id = '';
        }
    }
};

const onCustomerChange = () => {
    if (selectedCustomerInfo.value) {
        const custAreaId = selectedCustomerInfo.value.area_id || selectedCustomerInfo.value.customer_area_id;
        if (!createForm.area_id && custAreaId) {
            createForm.area_id = custAreaId;
        } else if (!createForm.area_id && selectedCustomerInfo.value.area) {
            const foundArea = props.areas?.find(a => a.name.trim().toLowerCase() === selectedCustomerInfo.value.area.trim().toLowerCase());
            if (foundArea) createForm.area_id = foundArea.id;
        }
        // Auto-fill nama dan no telepon pelapor dengan data pelanggan jika masih kosong
        if (!createForm.complainant_name) {
            createForm.complainant_name = selectedCustomerInfo.value.name;
        }
        if (!createForm.complainant_phone) {
            createForm.complainant_phone = selectedCustomerInfo.value.phone || '';
        }
    }
};

const performSearch = () => {
    router.get('/pgk', {
        search: search.value,
        status: activeStatus.value === 'all' ? '' : activeStatus.value,
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
    createForm.channel = 'whatsapp';
    createForm.category = 'LOS / Kabel Putus';
    createForm.priority = 'medium';
    if (filterArea.value) {
        createForm.area_id = filterArea.value;
    }
    showCreateModal.value = true;
};

const submitCreate = () => {
    createForm.post('/pgk', {
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
    resolveForm.post(`/pgk/${selectedTicket.value.id}/resolve-admin`, {
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
    escalateForm.post(`/pgk/${selectedTicket.value.id}/escalate`, {
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
    if (confirm(`Hapus tiket komplain PGK #${ticket.ticket_number}?`)) {
        router.delete(`/pgk/${ticket.id}`);
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

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'admin_handling': return 'bg-sky-50 text-sky-700 border border-sky-200';
        case 'escalated_to_lead': return 'bg-amber-50 text-amber-700 border border-amber-200';
        case 'scheduled': return 'bg-indigo-50 text-indigo-700 border border-indigo-200';
        case 'completed': return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
        default: return 'bg-gray-50 text-gray-700 border border-gray-200';
    }
};

const getStatusDotClass = (status) => {
    switch (status) {
        case 'admin_handling': return 'bg-sky-500 animate-pulse';
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
        case 'completed': return 'Selesai';
        default: return status;
    }
};
</script>
