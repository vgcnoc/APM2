<template>
    <AppLayout title="Penanganan Sigap (PGS)" subtitle="Manajemen Gangguan Pelanggan & Alur Penanganan Cepat">
        <div class="space-y-6">


            <!-- Stats Overview Cards -->
            <div v-if="can('pgs_card_summary')" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <div @click="filterByStatus('all')" class="bg-white p-4 rounded-2xl border transition-all cursor-pointer hover:shadow-md" :class="activeStatus === 'all' ? 'border-blue-500 ring-2 ring-blue-100 shadow-sm' : 'border-gray-100'">
                    <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Total Kasus PGS</div>
                    <div class="text-2xl font-black text-gray-900 mt-1">{{ counts.all || 0 }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">Seluruh tiket gangguan</div>
                </div>

                <div @click="filterByStatus('admin_handling')" class="bg-white p-4 rounded-2xl border transition-all cursor-pointer hover:shadow-md" :class="activeStatus === 'admin_handling' ? 'border-blue-500 ring-2 ring-blue-100 shadow-sm' : 'border-gray-100'">
                    <div class="text-[11px] font-bold text-blue-600 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                        Ditangani Admin
                    </div>
                    <div class="text-2xl font-black text-blue-700 mt-1">{{ counts.admin_handling || 0 }}</div>
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
                    <div class="text-[11px] text-gray-400 mt-1">Material siap / ditugaskan</div>
                </div>

                <div @click="filterByStatus('completed')" class="bg-white p-4 rounded-2xl border transition-all cursor-pointer hover:shadow-md col-span-2 sm:col-span-1" :class="activeStatus === 'completed' ? 'border-emerald-500 ring-2 ring-emerald-100 shadow-sm' : 'border-gray-100'">
                    <div class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Selesai
                    </div>
                    <div class="text-2xl font-black text-emerald-700 mt-1">{{ counts.completed || 0 }}</div>
                    <div class="text-[11px] text-gray-400 mt-1">Kasus terselesaikan</div>
                </div>
            </div>

            <!-- Toolbar & Action Filter -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4">
                <div v-if="can('pgs_filter')" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
                    <div class="relative flex-1 sm:w-80">
                        <input 
                            type="text" 
                            v-model="search" 
                            placeholder="Cari ID PGS, nama pelanggan, kode, kendala..." 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-sm transition-all"
                            @keyup.enter="performSearch"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <select v-model="filterPriority" @change="performSearch" class="px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-sm bg-gray-50/50">
                        <option value="">Semua Prioritas</option>
                        <option value="low">Rendah (Low)</option>
                        <option value="medium">Sedang (Medium)</option>
                        <option value="high">Tinggi (High)</option>
                        <option value="critical">Kritis (Critical)</option>
                    </select>

                    <select v-model="filterArea" @change="performSearch" class="px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-sm bg-gray-50/50">
                        <option value="">Semua Area / Wilayah</option>
                        <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>
                </div>

                <button v-if="can('pgs_btn_create')" @click="openCreateModal" class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-md shadow-blue-500/20 hover:shadow-lg hover:shadow-blue-500/30 hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Buat Penanganan Sigap</span>
                </button>
            </div>

            <!-- Flash Alert -->
            <div v-if="$page.props.flash?.success" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-semibold">{{ $page.props.flash.success }}</span>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/70 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                                <th class="p-4">No. Tiket & Pelanggan</th>
                                <th class="p-4">Gangguan & Prioritas</th>
                                <th class="p-4">Status Alur</th>
                                <th class="p-4">Penanganan Awal / Jadwal</th>
                                <th class="p-4 text-center">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-blue-50/20 transition-colors group">
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-blue-600 text-xs">{{ ticket.ticket_number }}</span>
                                        <span class="text-[10px] text-gray-400">· {{ formatDate(ticket.created_at) }}</span>
                                    </div>
                                    <div class="font-bold text-gray-900 mt-1 group-hover:text-blue-600 transition-colors">
                                        {{ ticket.customer?.name || '-' }}
                                    </div>
                                    <div class="text-[11px] text-gray-500 flex flex-wrap items-center gap-1.5 mt-0.5">
                                        <span class="px-1.5 py-0.2 rounded bg-gray-100 border text-gray-700 font-mono text-[10px]">{{ ticket.customer?.customer_code }}</span>
                                        <span>· {{ ticket.customer?.phone || '-' }}</span>
                                        <span v-if="ticket.area?.name || ticket.customer?.area_model?.name" class="px-1.5 py-0.5 rounded-md bg-blue-50 text-blue-700 font-semibold text-[10px] border border-blue-200">
                                            📍 {{ ticket.area?.name || ticket.customer?.area_model?.name }}
                                        </span>
                                    </div>
                                </td>

                                <td class="p-4 max-w-xs">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs font-bold text-gray-900">{{ ticket.category }}</span>
                                        <span :class="priorityBadgeClass(ticket.priority)" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border">
                                            {{ ticket.priority }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-600 line-clamp-2" :title="ticket.reported_issue">
                                        {{ ticket.reported_issue }}
                                    </p>
                                </td>

                                <td class="p-4">
                                    <span :class="statusBadgeClass(ticket.status)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold border">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass(ticket.status)"></span>
                                        {{ statusLabel(ticket.status) }}
                                    </span>
                                    <div v-if="ticket.status === 'escalated_to_lead'" class="text-[10px] text-amber-700 font-medium mt-1">
                                        Menunggu jadwal Ka. Teknisi
                                    </div>
                                    <div v-else-if="ticket.status === 'scheduled'" class="text-[10px] text-indigo-700 font-medium mt-1">
                                        Dijadwalkan: {{ ticket.scheduled_date ? formatDate(ticket.scheduled_date) : '-' }}
                                    </div>
                                </td>

                                <td class="p-4 max-w-xs">
                                    <div v-if="ticket.status === 'admin_handling'">
                                        <span class="text-[11px] text-blue-700 font-medium bg-blue-50 px-2 py-0.5 rounded border border-blue-100">
                                            Admin: {{ ticket.creator?.name || 'Sistem' }}
                                        </span>
                                        <p class="text-[11px] text-gray-500 mt-1 italic line-clamp-2">
                                            {{ ticket.admin_notes || 'Sedang dicoba penanganan sistemik...' }}
                                        </p>
                                    </div>
                                    <div v-else-if="ticket.status === 'escalated_to_lead'">
                                        <span class="text-[11px] text-amber-700 font-medium bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                            Alasan Eskalasi:
                                        </span>
                                        <p class="text-[11px] text-gray-700 mt-1 line-clamp-2">
                                            {{ ticket.escalation_reason || '-' }}
                                        </p>
                                    </div>
                                    <div v-else-if="ticket.status === 'scheduled' || ticket.status === 'completed'">
                                        <div v-if="ticket.technicians?.length" class="flex items-center gap-1.5">
                                            <div class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-[10px] font-black">
                                                {{ ticket.technicians[0].name.charAt(0) }}
                                            </div>
                                            <span class="text-xs font-semibold text-gray-800">
                                                {{ ticket.technicians.map(t => t.name).join(', ') }}
                                            </span>
                                        </div>
                                        <p v-if="ticket.action_taken" class="text-[11px] text-gray-600 mt-1 line-clamp-2">
                                            Hasil: {{ ticket.action_taken }}
                                        </p>
                                    </div>
                                </td>

                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Jika Admin Handling: Opsi Selesaikan Langsung ATAU Serahkan ke Ka. Teknisi -->
                                        <template v-if="ticket.status === 'admin_handling'">
                                            <button 
                                                v-if="can('pgs_btn_resolve')"
                                                @click="openResolveAdminModal(ticket)" 
                                                class="px-2.5 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg transition-colors flex items-center gap-1 shadow-sm"
                                                title="Selesaikan langsung di sistem"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                <span>Selesai</span>
                                            </button>
                                            
                                            <button 
                                                v-if="can('pgs_btn_escalate')"
                                                @click="openEscalateModal(ticket)" 
                                                class="px-2.5 py-1.5 text-xs font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-300 rounded-lg transition-colors flex items-center gap-1 shadow-sm"
                                                title="Tidak bisa ditangani, serahkan ke Kepala Teknisi"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                                <span>Ke Ka. Teknisi</span>
                                            </button>
                                        </template>

                                        <!-- Jika sudah dieskalasi: Tombol pintas ke Jadwal jika admin / lead -->
                                        <template v-else-if="ticket.status === 'escalated_to_lead'">
                                            <Link 
                                                href="/pgs/jadwal" 
                                                class="px-2.5 py-1.5 text-xs font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition-colors flex items-center gap-1 shadow-sm"
                                            >
                                                <span>Jadwalkan</span>
                                            </Link>
                                        </template>

                                        <!-- Tombol Detail / Riwayat -->
                                        <button 
                                            @click="openDetailModal(ticket)" 
                                            class="p-1.5 text-gray-600 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg transition-colors"
                                            title="Lihat Alur & Detail Tiket"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>

                                        <!-- Tombol Cetak SPK -->
                                        <button 
                                            @click="printTicket(ticket)" 
                                            class="p-1.5 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg transition-colors"
                                            title="Cetak SPK Penanganan"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!tickets.data.length">
                                <td colspan="5" class="p-12 text-center text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span class="font-bold text-gray-600">Tidak ada tiket Penanganan Sigap</span>
                                        <span class="text-xs text-gray-400 mt-0.5">Belum ada data atau tidak sesuai dengan kriteria pencarian</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="tickets.links && tickets.data.length" class="p-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-500">
                    <div>
                        Menampilkan <span class="font-bold text-gray-800">{{ tickets.from }}</span> - <span class="font-bold text-gray-800">{{ tickets.to }}</span> dari <span class="font-bold text-gray-800">{{ tickets.total }}</span> tiket
                    </div>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, i) in tickets.links" :key="i">
                            <div v-if="link.url === null" class="px-3 py-1.5 border border-gray-200 rounded-lg text-gray-300" v-html="link.label"></div>
                            <Link v-else :href="link.url" class="px-3 py-1.5 border rounded-lg transition-colors font-medium" :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'" v-html="link.label"></Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════ MODAL 1: BUAT TIKET PGS (ADMIN) ════════════ -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-xl w-full shadow-2xl border border-gray-100 overflow-hidden animate-fade-in-up">
                <div class="px-6 py-5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white flex justify-between items-center">
                    <div>
                        <h3 class="font-black text-lg">Buat Penanganan Sigap (PGS)</h3>
                        <p class="text-xs text-blue-100 mt-0.5">Catat temuan gangguan di sistem untuk penanganan cepat</p>
                    </div>
                    <button @click="showCreateModal = false" class="p-1 rounded-full hover:bg-white/20 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="p-6 space-y-4">
                    <!-- Area / Wilayah -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">
                            Area / Wilayah <span class="text-[11px] font-normal text-gray-400 lowercase">(filter pilihan pelanggan)</span>
                        </label>
                        <select 
                            v-model="createForm.area_id" 
                            @change="onAreaChange" 
                            class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-sm bg-gray-50/50"
                        >
                            <option value="">-- Semua Area / Wilayah --</option>
                            <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>
                    </div>

                    <!-- Pilih Pelanggan -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Pelanggan Terganggu <span class="text-red-500">*</span></label>
                        <select 
                            v-model="createForm.customer_id" 
                            @change="onCustomerChange"
                            class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-sm" 
                            required
                        >
                            <option value="">-- Pilih Pelanggan ({{ filteredCustomers.length }} Pelanggan) --</option>
                            <option v-for="c in filteredCustomers" :key="c.id" :value="c.id">
                                {{ c.name }} ({{ c.customer_code }}) - {{ c.address || 'Alamat tidak ada' }}
                            </option>
                        </select>
                        <!-- Info Ringkas Pelanggan Terpilih -->
                        <div v-if="selectedCustomerInfo" class="mt-2 p-3 rounded-xl bg-blue-50/70 border border-blue-100 text-xs text-blue-900 space-y-1">
                            <div class="font-bold flex justify-between">
                                <span>Paket: {{ selectedCustomerInfo.package?.name || '-' }}</span>
                                <span>Status ONT: {{ selectedCustomerInfo.ont?.status || 'N/A' }}</span>
                            </div>
                            <div class="flex flex-wrap items-center justify-between gap-1">
                                <div>ODP: {{ selectedCustomerInfo.ont?.odp?.name || '-' }} · SN: {{ selectedCustomerInfo.ont?.serial_number || '-' }}</div>
                                <div v-if="customerAreaName" class="font-semibold text-blue-700">📍 Area: {{ customerAreaName }}</div>
                            </div>
                            <div v-if="selectedCustomerInfo.ont?.rx_power" class="text-amber-700 font-semibold">Redaman RX Terakhir: {{ selectedCustomerInfo.ont.rx_power }} dBm</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Kategori Gangguan -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Kategori Gangguan <span class="text-red-500">*</span></label>
                            <select v-model="createForm.category" class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-sm" required>
                                <option value="LOS / Kabel Putus">LOS / Kabel Optik Putus</option>
                                <option value="Redaman Tinggi / Drop">Redaman Tinggi / Drop Power</option>
                                <option value="ONT / Modem Mati">ONT / Modem Mati / Rusak</option>
                                <option value="Internet Lambat / Lemot">Internet Lambat / Sinyal Drop</option>
                                <option value="Konfigurasi / PPPoE / VLAN">Konfigurasi / PPPoE / VLAN</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>

                        <!-- Prioritas -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tingkat Prioritas <span class="text-red-500">*</span></label>
                            <select v-model="createForm.priority" class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-sm" required>
                                <option value="low">Rendah (Low)</option>
                                <option value="medium">Sedang (Medium)</option>
                                <option value="high">Tinggi (High)</option>
                                <option value="critical">Kritis (Critical/Mendesak)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Keterangan Temuan Kendala -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Indikasi Masalah / Keluhan <span class="text-red-500">*</span></label>
                        <textarea 
                            v-model="createForm.reported_issue" 
                            rows="2" 
                            placeholder="Contoh: Lampu optik merah berkedip sejak pukul 14:00, redaman tidak terbaca di OLT."
                            class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-sm"
                            required
                        ></textarea>
                    </div>

                    <!-- Catatan Penanganan Awal Admin -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Penanganan Awal oleh Admin (Jarak Jauh)</label>
                        <textarea 
                            v-model="createForm.admin_notes" 
                            rows="2" 
                            placeholder="Contoh: Sudah dicoba reboot port OLT dan reset konfigurasi ONT, namun status tetap offline."
                            class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-sm"
                        ></textarea>
                        <p class="text-[11px] text-gray-400 mt-1">Langkah pengecekan sistem yang telah dicoba sebelum diteruskan.</p>
                    </div>

                    <div class="pt-3 border-t flex justify-end gap-2">
                        <button type="button" @click="showCreateModal = false" class="px-4 py-2 border rounded-xl text-gray-600 text-sm font-semibold hover:bg-gray-50">Batal</button>
                        <button type="submit" :disabled="createForm.processing" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-md shadow-blue-500/20 disabled:opacity-50">
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
                        <h3 class="font-black text-lg">Selesaikan Gangguan (Tingkat Admin)</h3>
                        <p class="text-xs text-emerald-100 mt-0.5">Penanganan berhasil diselesaikan jarak jauh tanpa teknisi lapangan</p>
                    </div>
                    <button @click="showResolveAdminModal = false" class="p-1 rounded-full hover:bg-white/20 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submitResolveAdmin" class="p-6 space-y-4">
                    <div class="p-3 bg-gray-50 rounded-xl text-xs space-y-1">
                        <div><strong class="text-gray-700">Tiket:</strong> {{ selectedTicket?.ticket_number }}</div>
                        <div><strong class="text-gray-700">Pelanggan:</strong> {{ selectedTicket?.customer?.name }}</div>
                        <div><strong class="text-gray-700">Kendala:</strong> {{ selectedTicket?.reported_issue }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Catatan Solusi / Perbaikan Admin <span class="text-red-500">*</span></label>
                        <textarea 
                            v-model="resolveAdminForm.admin_notes" 
                            rows="3" 
                            placeholder="Contoh: Dilakukan reboot port OLT dan unbind profile PPPoE, ONT pelanggan kembali online dan internet lancar."
                            class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm"
                            required
                        ></textarea>
                    </div>

                    <div class="pt-3 border-t flex justify-end gap-2">
                        <button type="button" @click="showResolveAdminModal = false" class="px-4 py-2 border rounded-xl text-gray-600 text-sm font-semibold hover:bg-gray-50">Batal</button>
                        <button type="submit" :disabled="resolveAdminForm.processing" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold shadow-md shadow-emerald-500/20 disabled:opacity-50">
                            {{ resolveAdminForm.processing ? 'Menyimpan...' : 'Konfirmasi Selesai' }}
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
                        <h3 class="font-black text-lg">Serahkan ke Kepala Teknisi</h3>
                        <p class="text-xs text-amber-100 mt-0.5">Eskalasi kendala fisik untuk dijadwalkan & disiapkan material</p>
                    </div>
                    <button @click="showEscalateModal = false" class="p-1 rounded-full hover:bg-white/20 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submitEscalate" class="p-6 space-y-4">
                    <div class="p-3 bg-amber-50/70 border border-amber-200 rounded-xl text-xs space-y-1 text-amber-900">
                        <div><strong>Tiket:</strong> {{ selectedTicket?.ticket_number }}</div>
                        <div><strong>Pelanggan:</strong> {{ selectedTicket?.customer?.name }} ({{ selectedTicket?.customer?.address }})</div>
                        <div><strong>Kendala:</strong> {{ selectedTicket?.reported_issue }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Alasan Penyerahan ke Kepala Teknisi <span class="text-red-500">*</span></label>
                        <textarea 
                            v-model="escalateForm.escalation_reason" 
                            rows="3" 
                            placeholder="Contoh: Sudah dicek di OLT redaman Loss (-38 dBm / LOS), dugaan kuat kabel dropcore putus di tiang. Butuh penanganan fisik oleh teknisi."
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

                    <div class="pt-3 border-t flex justify-end gap-2">
                        <button type="button" @click="showEscalateModal = false" class="px-4 py-2 border rounded-xl text-gray-600 text-sm font-semibold hover:bg-gray-50">Batal</button>
                        <button type="submit" :disabled="escalateForm.processing" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-sm font-bold shadow-md shadow-amber-500/20 disabled:opacity-50">
                            {{ escalateForm.processing ? 'Menyerahkan...' : 'Serahkan ke Kepala Teknisi' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ════════════ MODAL 4: DETAIL & TIMELINE ALUR TIKET ════════════ -->
        <div v-if="showDetailModal" class="fixed inset-0 z-50 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] flex flex-col shadow-2xl border border-gray-100 overflow-hidden animate-fade-in-up">
                <div class="px-6 py-4 bg-gray-50 border-b flex justify-between items-center shrink-0">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono font-black text-blue-600">{{ selectedTicket?.ticket_number }}</span>
                            <span :class="statusBadgeClass(selectedTicket?.status)" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border">
                                {{ statusLabel(selectedTicket?.status) }}
                            </span>
                        </div>
                        <h4 class="text-base font-bold text-gray-900 mt-0.5">{{ selectedTicket?.customer?.name }}</h4>
                    </div>
                    <button @click="showDetailModal = false" class="p-1.5 rounded-xl hover:bg-gray-200 text-gray-500 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto space-y-6 text-sm">
                    <!-- Info Pelanggan -->
                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-200/80 grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                        <div>
                            <span class="text-gray-400 block font-bold">KODE PELANGGAN</span>
                            <span class="font-bold text-gray-800">{{ selectedTicket?.customer?.customer_code }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block font-bold">TELEPON</span>
                            <span class="font-bold text-gray-800">{{ selectedTicket?.customer?.phone || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block font-bold">PAKET INTERNET</span>
                            <span class="font-bold text-gray-800">{{ selectedTicket?.customer?.package?.name || '-' }}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-gray-400 block font-bold">ALAMAT</span>
                            <span class="text-gray-800">{{ selectedTicket?.customer?.address || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block font-bold">ODP & AREA</span>
                            <span class="text-gray-800">{{ selectedTicket?.customer?.ont?.odp?.name || '-' }} ({{ selectedTicket?.area?.name || selectedTicket?.customer?.area_model?.name || '-' }})</span>
                        </div>
                    </div>

                    <!-- Timeline Alur PGS -->
                    <div class="space-y-4">
                        <h5 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Histori Alur Penanganan PGS</h5>

                        <!-- Step 1: Temuan Admin -->
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div class="w-7 h-7 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-black">1</div>
                                <div class="w-0.5 h-full bg-gray-200 my-1"></div>
                            </div>
                            <div class="pb-4">
                                <div class="text-xs font-bold text-blue-700">TEMUAN GANGGUAN DI SISTEM</div>
                                <div class="text-[11px] text-gray-400">Dibuat oleh {{ selectedTicket?.creator?.name || 'Admin' }} · {{ formatDate(selectedTicket?.created_at) }}</div>
                                <div class="mt-1 text-xs text-gray-800 bg-white p-2.5 rounded-xl border border-gray-100 shadow-2xs">
                                    <strong>Kategori:</strong> {{ selectedTicket?.category }} (Prioritas: {{ selectedTicket?.priority }})<br>
                                    <strong>Kendala:</strong> {{ selectedTicket?.reported_issue }}<br>
                                    <span v-if="selectedTicket?.admin_notes" class="text-gray-500"><strong>Catatan Admin:</strong> {{ selectedTicket?.admin_notes }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Eskalasi ke Ka. Teknisi -->
                        <div v-if="selectedTicket?.escalated_at || selectedTicket?.status !== 'admin_handling'" class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div class="w-7 h-7 rounded-full bg-amber-500 text-white flex items-center justify-center text-xs font-black">2</div>
                                <div class="w-0.5 h-full bg-gray-200 my-1"></div>
                            </div>
                            <div class="pb-4">
                                <div class="text-xs font-bold text-amber-700">DISERAHKAN KE KEPALA TEKNISI</div>
                                <div class="text-[11px] text-gray-400">Eskalasi pada {{ formatDate(selectedTicket?.escalated_at) }}</div>
                                <div class="mt-1 text-xs text-amber-900 bg-amber-50/60 p-2.5 rounded-xl border border-amber-200">
                                    <strong>Alasan Tidak Bisa Ditangani Admin:</strong><br>
                                    {{ selectedTicket?.escalation_reason || '-' }}
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Penjadwalan & Material -->
                        <div v-if="selectedTicket?.scheduled_date || selectedTicket?.status === 'scheduled' || selectedTicket?.status === 'completed'" class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div class="w-7 h-7 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-black">3</div>
                                <div class="w-0.5 h-full bg-gray-200 my-1"></div>
                            </div>
                            <div class="pb-4">
                                <div class="text-xs font-bold text-indigo-700">PENJADWALAN & PENYIAPAN MATERIAL</div>
                                <div class="text-[11px] text-gray-400">Oleh Ka. Teknisi: {{ selectedTicket?.lead_technician?.name || 'Kepala Teknisi' }}</div>
                                <div class="mt-1 text-xs text-gray-800 bg-white p-2.5 rounded-xl border border-gray-100 shadow-2xs space-y-1">
                                    <div><strong>Jadwal:</strong> {{ selectedTicket?.scheduled_date ? formatDate(selectedTicket.scheduled_date) : '-' }} (Pukul: {{ selectedTicket?.scheduled_time || 'Fleksibel' }})</div>
                                    <div><strong>Teknisi Ditugaskan:</strong> {{ selectedTicket?.technicians?.map(t => t.name).join(', ') || 'Belum dipilih' }}</div>
                                    <div v-if="selectedTicket?.lead_notes"><strong>Instruksi Ka. Teknisi:</strong> {{ selectedTicket.lead_notes }}</div>
                                    
                                    <!-- Material Disiapkan -->
                                    <div v-if="selectedTicket?.materials?.length" class="mt-2 pt-2 border-t border-gray-100">
                                        <div class="font-bold text-[11px] text-gray-500 uppercase">Material Disiapkan:</div>
                                        <ul class="list-disc list-inside mt-0.5 text-[11px] text-gray-700">
                                            <li v-for="m in selectedTicket.materials" :key="m.id">
                                                {{ m.material?.name }}: {{ m.quantity_prepared }} {{ m.unit || m.material?.unit }}
                                                <span v-if="m.quantity_used > 0" class="text-emerald-600">(Terpakai: {{ m.quantity_used }})</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Laporan Hasil Penanganan -->
                        <div v-if="selectedTicket?.status === 'completed' || selectedTicket?.status === 'resolved_by_admin'" class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div class="w-7 h-7 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-black">4</div>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-emerald-700">PENANGANAN SELESAI & LAPORAN AKHIR</div>
                                <div class="text-[11px] text-gray-400">Diselesaikan oleh {{ selectedTicket?.resolved_by_user?.name || 'Petugas' }} · {{ formatDate(selectedTicket?.resolved_at) }}</div>
                                <div class="mt-1 text-xs text-emerald-900 bg-emerald-50/60 p-3 rounded-xl border border-emerald-200 space-y-1.5">
                                    <div v-if="selectedTicket?.action_taken"><strong>Tindakan Perbaikan:</strong> {{ selectedTicket.action_taken }}</div>
                                    <div v-if="selectedTicket?.optical_power_before || selectedTicket?.optical_power_after" class="flex gap-4">
                                        <span v-if="selectedTicket?.optical_power_before">Redaman Awal: <strong>{{ selectedTicket.optical_power_before }}</strong></span>
                                        <span v-if="selectedTicket?.optical_power_after">Redaman Akhir: <strong>{{ selectedTicket.optical_power_after }}</strong></span>
                                    </div>
                                    <div v-if="selectedTicket?.resolution_notes"><strong>Catatan Solusi:</strong> {{ selectedTicket.resolution_notes }}</div>
                                    
                                    <!-- Foto Bukti -->
                                    <div v-if="selectedTicket?.photo_before || selectedTicket?.photo_after" class="pt-2 flex gap-3">
                                        <div v-if="selectedTicket?.photo_before" class="text-center">
                                            <div class="text-[10px] text-gray-500 font-bold mb-1">FOTO SEBELUM</div>
                                            <img :src="'/storage/' + selectedTicket.photo_before" class="w-24 h-24 object-cover rounded-lg border shadow-sm cursor-pointer" @click="openImage('/storage/' + selectedTicket.photo_before)">
                                        </div>
                                        <div v-if="selectedTicket?.photo_after" class="text-center">
                                            <div class="text-[10px] text-gray-500 font-bold mb-1">FOTO SESUDAH</div>
                                            <img :src="'/storage/' + selectedTicket.photo_after" class="w-24 h-24 object-cover rounded-lg border shadow-sm cursor-pointer" @click="openImage('/storage/' + selectedTicket.photo_after)">
                                        </div>
                                    </div>

                                    <!-- Multi Titik Koordinat (Tikor) Insiden -->
                                    <div v-if="selectedTicket?.incident_coordinates && selectedTicket.incident_coordinates.length > 0" class="mt-2.5 pt-2 border-t border-emerald-200">
                                        <div class="text-[11px] font-bold text-emerald-800 flex items-center gap-1.5 mb-1.5">
                                            <span>📍</span>
                                            <span>Titik Koordinat Insiden / Penanganan ({{ selectedTicket.incident_coordinates.length }} Tikor):</span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            <div v-for="(tikor, tIdx) in selectedTicket.incident_coordinates" :key="'tikor-' + tIdx" class="bg-white p-2 rounded-lg border border-emerald-200 text-[11px] flex items-center justify-between gap-2 shadow-2xs">
                                                <div class="truncate">
                                                    <span class="font-bold text-gray-800 block truncate">{{ tikor.label || ('Titik #' + (tIdx + 1)) }}</span>
                                                    <span class="font-mono text-gray-500 text-[10px]">{{ tikor.latitude }}, {{ tikor.longitude }}</span>
                                                    <span v-if="tikor.description" class="text-gray-600 block text-[10px] italic truncate">{{ tikor.description }}</span>
                                                </div>
                                                <a :href="`https://www.google.com/maps?q=${tikor.latitude},${tikor.longitude}`" target="_blank" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-bold text-[10px] shrink-0 inline-flex items-center gap-1 shadow-xs">
                                                    <span>Maps</span> ↗
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Material Pelanggan Dicabut / Diganti (Dismantled) -->
                                    <div v-if="selectedTicket?.dismantled_materials && selectedTicket.dismantled_materials.length > 0" class="mt-2.5 pt-2 border-t border-emerald-200">
                                        <div class="text-[11px] font-bold text-amber-900 flex items-center gap-1.5 mb-1.5">
                                            <span>🔄</span>
                                            <span>Material Pelanggan Diganti / Dicabut ({{ selectedTicket.dismantled_materials.length }} Item):</span>
                                        </div>
                                        <div class="space-y-1.5">
                                            <div v-for="dismantle in selectedTicket.dismantled_materials" :key="'dismantle-' + dismantle.id" class="bg-white p-2.5 rounded-lg border border-amber-200 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-2xs">
                                                <div>
                                                    <div class="font-bold text-gray-900 flex items-center gap-2">
                                                        <span>{{ dismantle.material?.name || dismantle.item_name }}</span>
                                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-black uppercase" :class="dismantle.replacement_type === 'full' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-blue-100 text-blue-800 border border-blue-200'">
                                                            {{ dismantle.replacement_type === 'full' ? 'Ganti Total (100%)' : 'Ganti Sebagian' }}
                                                        </span>
                                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold" :class="dismantle.condition === 'rusak_scrap' ? 'bg-rose-100 text-rose-800 border border-rose-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200'">
                                                            {{ dismantle.condition === 'rusak_scrap' ? '🔴 Rusak / Scrap' : (dismantle.condition === 'normal_reusable' ? '🟢 Layak Pakai' : '🟡 Perlu Cek') }}
                                                        </span>
                                                    </div>
                                                    <div class="text-[11px] text-gray-600 mt-0.5">
                                                        Jumlah: <strong class="text-gray-900 font-mono">{{ parseFloat(dismantle.quantity) }} {{ dismantle.unit }}</strong>
                                                        <span v-if="dismantle.damage_reason" class="text-gray-500"> · Alasan: "{{ dismantle.damage_reason }}"</span>
                                                    </div>
                                                </div>
                                                <div class="shrink-0 flex items-center gap-1.5 text-[11px]">
                                                    <span v-if="dismantle.handover_status === 'pending_handover'" class="px-2 py-1 rounded bg-amber-50 text-amber-800 border border-amber-300 font-bold flex items-center gap-1">
                                                        ⏳ Menunggu Gudang/Ka.Teknisi
                                                    </span>
                                                    <span v-else class="px-2 py-1 rounded bg-emerald-50 text-emerald-800 border border-emerald-300 font-bold flex items-center gap-1">
                                                        ✓ Diterima Gudang ({{ dismantle.disposition === 'restocked' ? 'Restock Gudang' : 'Scrap/Afkir' }})
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-gray-50 border-t flex justify-between items-center shrink-0">
                    <button @click="printTicket(selectedTicket)" class="px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>Cetak SPK / Berita Acara</span>
                    </button>
                    <button @click="showDetailModal = false" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl text-xs font-bold">Tutup</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermission } from '@/Composables/usePermission';

const { can } = usePermission();

const props = defineProps({
    tickets: Object,
    counts: Object,
    customers: Array,
    areas: Array,
    technicians: Array,
    leadTechnicians: Array,
    materials: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const activeStatus = ref(props.filters?.status || 'all');
const filterPriority = ref(props.filters?.priority || '');
const filterArea = ref(props.filters?.area_id || '');

// Modals
const showCreateModal = ref(false);
const showResolveAdminModal = ref(false);
const showEscalateModal = ref(false);
const showDetailModal = ref(false);
const selectedTicket = ref(null);

// Forms
const createForm = useForm({
    area_id: '',
    customer_id: '',
    category: 'LOS / Kabel Putus',
    priority: 'medium',
    reported_issue: '',
    admin_notes: '',
});

const resolveAdminForm = useForm({
    admin_notes: '',
});

const escalateForm = useForm({
    escalation_reason: '',
    lead_technician_id: '',
});

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

const selectedCustomerInfo = computed(() => {
    return props.customers?.find(c => c.id === createForm.customer_id);
});

const customerAreaName = computed(() => {
    if (!selectedCustomerInfo.value) return '';
    if (selectedCustomerInfo.value.area_model?.name) return selectedCustomerInfo.value.area_model.name;
    if (selectedCustomerInfo.value.area) return selectedCustomerInfo.value.area;
    const a = props.areas?.find(ar => String(ar.id) === String(selectedCustomerInfo.value.area_id || selectedCustomerInfo.value.customer_area_id));
    return a?.name || '';
});

function onAreaChange() {
    if (createForm.customer_id && createForm.area_id) {
        const cust = props.customers?.find(c => c.id === createForm.customer_id);
        const selectedArea = props.areas?.find(a => String(a.id) === String(createForm.area_id));
        const matchesAreaId = cust && (String(cust.area_id) === String(createForm.area_id) || String(cust.customer_area_id) === String(createForm.area_id));
        const matchesAreaName = selectedArea && cust && (cust.area?.trim().toLowerCase() === selectedArea.name.trim().toLowerCase() || cust.area_model?.name?.trim().toLowerCase() === selectedArea.name.trim().toLowerCase());
        if (!matchesAreaId && !matchesAreaName) {
            createForm.customer_id = '';
        }
    }
}

function onCustomerChange() {
    if (createForm.customer_id) {
        const cust = props.customers?.find(c => c.id === createForm.customer_id);
        const custAreaId = cust?.area_id || cust?.customer_area_id;
        if (custAreaId && !createForm.area_id) {
            createForm.area_id = custAreaId;
        } else if (!createForm.area_id && cust?.area) {
            const foundArea = props.areas?.find(a => a.name.trim().toLowerCase() === cust.area.trim().toLowerCase());
            if (foundArea) createForm.area_id = foundArea.id;
        }
    }
}

function performSearch() {
    router.get('/pgs', {
        search: search.value,
        status: activeStatus.value,
        priority: filterPriority.value,
        area_id: filterArea.value,
    }, { preserveState: true, replace: true });
}

function filterByStatus(st) {
    activeStatus.value = st;
    performSearch();
}

function openCreateModal() {
    createForm.reset();
    if (filterArea.value) {
        createForm.area_id = filterArea.value;
    }
    showCreateModal.value = true;
}

function submitCreate() {
    createForm.post('/pgs', {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        }
    });
}

function openResolveAdminModal(ticket) {
    selectedTicket.value = ticket;
    resolveAdminForm.admin_notes = '';
    showResolveAdminModal.value = true;
}

function submitResolveAdmin() {
    if (!selectedTicket.value) return;
    resolveAdminForm.post(`/pgs/${selectedTicket.value.id}/resolve-admin`, {
        onSuccess: () => {
            showResolveAdminModal.value = false;
        }
    });
}

function openEscalateModal(ticket) {
    selectedTicket.value = ticket;
    escalateForm.escalation_reason = '';
    escalateForm.lead_technician_id = ticket.lead_technician_id || '';
    showEscalateModal.value = true;
}

function submitEscalate() {
    if (!selectedTicket.value) return;
    escalateForm.post(`/pgs/${selectedTicket.value.id}/escalate`, {
        onSuccess: () => {
            showEscalateModal.value = false;
        }
    });
}

function openDetailModal(ticket) {
    selectedTicket.value = ticket;
    showDetailModal.value = true;
}

function printTicket(ticket) {
    if (!ticket) return;
    window.open(`/pgs/${ticket.id}/print`, '_blank');
}

function openImage(url) {
    window.open(url, '_blank');
}

function formatDate(dt) {
    if (!dt) return '-';
    return new Date(dt).toLocaleString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function statusLabel(st) {
    const map = {
        admin_handling: 'Ditangani Admin',
        escalated_to_lead: 'Diserahkan ke Ka. Teknisi',
        scheduled: 'Dijadwalkan',
        in_progress: 'Dalam Pengerjaan',
        resolved_by_admin: 'Selesai (Admin)',
        completed: 'Selesai (Teknisi)',
        canceled: 'Dibatalkan',
    };
    return map[st] || st;
}

function statusBadgeClass(st) {
    const map = {
        admin_handling: 'bg-blue-50 text-blue-700 border-blue-200',
        escalated_to_lead: 'bg-amber-50 text-amber-700 border-amber-200',
        scheduled: 'bg-indigo-50 text-indigo-700 border-indigo-200',
        in_progress: 'bg-purple-50 text-purple-700 border-purple-200',
        resolved_by_admin: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        completed: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        canceled: 'bg-gray-100 text-gray-600 border-gray-200',
    };
    return map[st] || 'bg-gray-50 text-gray-700 border-gray-200';
}

function statusDotClass(st) {
    const map = {
        admin_handling: 'bg-blue-500 animate-pulse',
        escalated_to_lead: 'bg-amber-500 animate-pulse',
        scheduled: 'bg-indigo-500',
        in_progress: 'bg-purple-500 animate-pulse',
        resolved_by_admin: 'bg-emerald-500',
        completed: 'bg-emerald-500',
        canceled: 'bg-gray-400',
    };
    return map[st] || 'bg-gray-400';
}

function priorityBadgeClass(pr) {
    const map = {
        low: 'bg-gray-50 text-gray-600 border-gray-200',
        medium: 'bg-blue-50 text-blue-700 border-blue-200',
        high: 'bg-orange-50 text-orange-700 border-orange-200',
        critical: 'bg-red-50 text-red-700 border-red-200 font-black animate-pulse',
    };
    return map[pr] || 'bg-gray-50 text-gray-600 border-gray-200';
}
</script>
