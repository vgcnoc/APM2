<template>
    <AppLayout title="Pelanggan Stop Permanen" subtitle="Manajemen pelanggan yang dinonaktifkan permanen, penarikan perangkat ONT, pemutihan piutang & reaktivasi.">
        
        <!-- Flash Alert / Info Banner -->
        <div v-if="stats.pending_ont > 0" class="mb-6 p-4 rounded-2xl bg-amber-50/80 border border-amber-200/80 flex items-start sm:items-center justify-between gap-4 text-amber-900 shadow-sm animate-fade-in-up">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-amber-900">Perhatian Inventaris: {{ stats.pending_ont }} Perangkat ONT Belum Ditarik</h4>
                    <p class="text-xs text-amber-700 mt-0.5">Terdapat {{ stats.pending_ont }} pelanggan yang statusnya telah berhenti namun perangkat modem ONT belum dikembalikan ke stok gudang.</p>
                </div>
            </div>
            <button 
                @click="filterByOntPending" 
                class="px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs rounded-xl transition-all shadow-sm shrink-0 whitespace-nowrap"
            >
                Tampilkan Daftar ONT
            </button>
        </div>

        <!-- KPI Statistik Ringkasan -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
            <!-- 1. Total Terminated -->
            <div class="glass-card p-5 animate-fade-in-up border border-slate-200/80 hover:shadow-md transition-shadow" style="animation-delay: 0.05s">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Stop Permanen</p>
                        <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900">{{ stats.total }}</h3>
                        <p class="text-[11px] text-slate-500 mt-1">Pelanggan dihentikan resmi</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center shadow-inner">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- 2. Berhenti Bulan Ini -->
            <div class="glass-card p-5 animate-fade-in-up border border-slate-200/80 hover:shadow-md transition-shadow" style="animation-delay: 0.1s">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Berhenti Bulan Ini</p>
                        <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900">{{ stats.this_month }}</h3>
                        <p class="text-[11px] text-slate-500 mt-1">Bulan {{ currentMonthName }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-700 flex items-center justify-center shadow-inner">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- 3. ONT Belum Ditarik -->
            <div class="glass-card p-5 animate-fade-in-up border border-slate-200/80 hover:shadow-md transition-shadow" style="animation-delay: 0.15s">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">ONT Belum Ditarik</p>
                        <h3 class="text-2xl sm:text-3xl font-extrabold" :class="stats.pending_ont > 0 ? 'text-amber-600' : 'text-slate-900'">{{ stats.pending_ont }}</h3>
                        <p class="text-[11px] text-slate-500 mt-1">Masih terpasang di lokasi</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl" :class="stats.pending_ont > 0 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600'" flex items-center justify-center shadow-inner>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- 4. Total Sisa Piutang -->
            <div class="glass-card p-5 animate-fade-in-up border border-slate-200/80 hover:shadow-md transition-shadow" style="animation-delay: 0.2s">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Sisa Piutang</p>
                        <h3 class="text-xl sm:text-2xl font-extrabold" :class="stats.total_piutang > 0 ? 'text-rose-600' : 'text-emerald-600'">
                            {{ formatRupiah(stats.total_piutang) }}
                        </h3>
                        <p class="text-[11px] text-slate-500 mt-1">Tagihan belum lunas / diputihkan</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-inner">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- DataTable Pelanggan Stop Permanen -->
        <DataTable
            :data="customers.data"
            :columns="columns"
            :pagination="customers"
            search-placeholder="Cari nama, ID pelanggan, nomor telepon, alamat..."
            search-route="/customers/terminated"
        >
            <!-- Slot Filters -->
            <template #filters>
                <div class="flex flex-wrap items-center gap-3 w-full text-xs">
                    <!-- Filter Area -->
                    <div class="flex items-center gap-1.5">
                        <label class="font-semibold text-slate-600">Area:</label>
                        <select 
                            v-model="currentArea" 
                            @change="applyFilters"
                            class="rounded-lg border-slate-300 text-xs py-1.5 px-2.5 focus:ring-blue-500 focus:border-blue-500 bg-white"
                        >
                            <option value="">Semua Area</option>
                            <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>
                    </div>

                    <!-- Filter Paket -->
                    <div class="flex items-center gap-1.5">
                        <label class="font-semibold text-slate-600">Paket:</label>
                        <select 
                            v-model="currentPackage" 
                            @change="applyFilters"
                            class="rounded-lg border-slate-300 text-xs py-1.5 px-2.5 focus:ring-blue-500 focus:border-blue-500 bg-white"
                        >
                            <option value="">Semua Paket</option>
                            <option v-for="p in packages" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>

                    <!-- Filter Status ONT -->
                    <div class="flex items-center gap-1.5">
                        <label class="font-semibold text-slate-600">Perangkat ONT:</label>
                        <select 
                            v-model="currentOntStatus" 
                            @change="applyFilters"
                            class="rounded-lg border-slate-300 text-xs py-1.5 px-2.5 focus:ring-blue-500 focus:border-blue-500 bg-white"
                        >
                            <option value="">Semua Status ONT</option>
                            <option value="pending">⚠️ Belum Ditarik (Masih di Lokasi)</option>
                            <option value="returned">✅ Sudah Ditarik / Bebas ONT</option>
                        </select>
                    </div>

                    <!-- Filter Status Piutang -->
                    <div class="flex items-center gap-1.5">
                        <label class="font-semibold text-slate-600">Status Piutang:</label>
                        <select 
                            v-model="currentBillingStatus" 
                            @change="applyFilters"
                            class="rounded-lg border-slate-300 text-xs py-1.5 px-2.5 focus:ring-blue-500 focus:border-blue-500 bg-white"
                        >
                            <option value="">Semua Tagihan</option>
                            <option value="unpaid">🔴 Ada Tunggakan Piutang</option>
                            <option value="paid">🟢 Bebas Piutang / Lunas</option>
                        </select>
                    </div>

                    <!-- Reset Filters Button -->
                    <button 
                        v-if="hasActiveFilters" 
                        @click="resetFilters"
                        class="px-3 py-1.5 text-slate-500 hover:text-slate-800 bg-slate-200/60 hover:bg-slate-200 rounded-lg transition-colors font-medium ml-auto flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Reset Filter
                    </button>
                </div>
            </template>

            <!-- Table Rows -->
            <template #row="{ row }">
                <!-- 1. Pelanggan -->
                <td>
                    <div class="flex items-start gap-3 py-1">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-red-600 text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-sm mt-0.5">
                            {{ row.name ? row.name.charAt(0).toUpperCase() : '?' }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <a :href="`/customers/${row.id}`" class="font-bold text-slate-900 hover:text-blue-600 transition-colors text-sm truncate block">
                                    {{ row.name }}
                                </a>
                                <span class="px-2 py-0.5 bg-rose-100 text-rose-700 text-[10px] font-bold rounded-full uppercase tracking-wider">
                                    Stop Permanen
                                </span>
                            </div>
                            <div class="flex items-center gap-2 mt-0.5 text-xs text-slate-500 font-mono">
                                <span>{{ row.customer_code }}</span>
                                <span v-if="row.area_model?.name" class="font-sans px-1.5 py-0.2 bg-slate-100 text-slate-600 rounded text-[10px]">
                                    {{ row.area_model.name }}
                                </span>
                            </div>
                            <div v-if="row.phone" class="flex items-center gap-1.5 mt-1 text-xs">
                                <a :href="getWhatsAppLink(row.phone)" target="_blank" class="text-emerald-600 hover:text-emerald-700 flex items-center gap-1 font-medium">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.885-9.89 9.885m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.746.953 3.71 1.455 5.712 1.457h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.415z"/>
                                    </svg>
                                    {{ row.phone }}
                                </a>
                            </div>
                        </div>
                    </div>
                </td>

                <!-- 2. Paket -->
                <td>
                    <div class="py-1">
                        <div class="font-semibold text-slate-800 text-sm">
                            {{ row.package?.name || 'Non-Paket' }}
                        </div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            Rp {{ formatNumber(row.package?.price || 0) }}/bln
                        </div>
                    </div>
                </td>

                <!-- 3. Penghentian (Tgl, Alasan & Masa Kontrak) -->
                <td>
                    <div class="py-1 space-y-1">
                        <div class="flex items-center gap-1.5 text-xs text-slate-700 font-medium">
                            <svg class="w-3.5 h-3.5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="font-bold">{{ formatDate(row.cancel_date) }}</span>
                        </div>
                        <div class="text-xs text-slate-600 line-clamp-2 max-w-xs" :title="row.cancel_reason">
                            <span class="font-semibold text-slate-700">Alasan:</span> {{ row.cancel_reason || 'Permintaan pelanggan' }}
                        </div>

                        <!-- Masa Aktif / Status Kontrak 4 Bulan -->
                        <div v-if="row.active_duration && row.active_duration.text" class="flex flex-wrap items-center gap-1 pt-0.5">
                            <span 
                                v-if="row.active_duration.is_under_4_months"
                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200"
                                :title="'Masa aktif: ' + row.active_duration.text + ' (Kurang dari 4 bulan)'"
                            >
                                <span>⚠️ Stop Dini</span>
                                <span class="font-normal text-amber-700">({{ row.active_duration.text }})</span>
                            </span>
                            <span 
                                v-else
                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                :title="'Masa aktif: ' + row.active_duration.text"
                            >
                                <span>✅ Lulus Kontrak</span>
                                <span class="font-normal text-emerald-600">({{ row.active_duration.text }})</span>
                            </span>
                        </div>

                        <!-- Pemotongan Fee Booking Afiliasi Otomatis -->
                        <div v-if="row.clawback_commission" class="pt-0.5">
                            <span 
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-50 border border-rose-200 text-rose-700 text-[10px] font-bold shadow-xs"
                                :title="'Fee booking otomatis dipotong dari ' + (row.clawback_commission.status === 'payroll' ? 'Slip Gaji / Payroll Karyawan ' : 'saldo dompet ') + (row.sales?.name || 'Afiliasi')"
                            >
                                <span>✂️ Auto-Potong Booking:</span>
                                <span class="font-mono">{{ formatRupiah(Math.abs(row.clawback_commission.amount)) }}</span>
                                <span v-if="row.clawback_commission.status === 'payroll'" class="px-1.5 py-0.2 bg-purple-100 text-purple-700 rounded text-[9px] font-bold">[SLIP GAJI]</span>
                                <span v-else class="px-1.5 py-0.2 bg-blue-100 text-blue-700 rounded text-[9px] font-bold">[DOMPET MITRA]</span>
                                <span v-if="row.sales?.name" class="font-normal text-slate-600 text-[9px]">• {{ row.sales.name }}</span>
                            </span>
                        </div>
                    </div>
                </td>

                <!-- 4. Perangkat ONT -->
                <td>
                    <div class="py-1">
                        <!-- Jika ada ONT terpasang -->
                        <div v-if="row.ont" class="p-2.5 rounded-xl bg-amber-50/70 border border-amber-200/70 text-xs">
                            <div class="flex items-center justify-between gap-1 mb-1">
                                <span class="px-1.5 py-0.5 rounded bg-amber-200 text-amber-800 font-bold text-[10px]">
                                    ⚠️ BELUM DITARIK
                                </span>
                                <button 
                                    @click="openPullOntModal(row)"
                                    class="text-[11px] font-semibold text-amber-700 hover:text-amber-900 underline transition-colors"
                                >
                                    Tarik ke Gudang
                                </button>
                            </div>
                            <div class="font-mono font-bold text-slate-800 tracking-wider">
                                SN: {{ row.ont.serial_number }}
                            </div>
                            <div class="text-[11px] text-slate-500 mt-0.5 truncate">
                                {{ row.ont.model || 'ONT' }} <span v-if="row.ont.odp">• ODP: {{ row.ont.odp.name }}</span>
                            </div>
                        </div>

                        <!-- Jika ONT sudah ditarik / tidak ada -->
                        <div v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-semibold">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Bebas Perangkat (Di Gudang)</span>
                        </div>
                    </div>
                </td>

                <!-- 5. Tagihan / Piutang -->
                <td>
                    <div class="py-1">
                        <div v-if="row.unpaid_invoices_count > 0" class="p-2.5 rounded-xl bg-rose-50/80 border border-rose-200/80 text-xs">
                            <div class="flex items-center justify-between gap-1 mb-0.5">
                                <span class="text-[10px] font-bold text-rose-600 uppercase">
                                    {{ row.unpaid_invoices_count }} Tagihan Menunggak
                                </span>
                                <button 
                                    @click="openWriteOffModal(row)"
                                    class="text-[10px] font-bold text-rose-700 hover:text-rose-900 underline transition-colors"
                                    title="Hapuskan sisa tagihan untuk pelanggan ini"
                                >
                                    Pemutihan
                                </button>
                            </div>
                            <div class="font-extrabold text-rose-700 text-sm">
                                Rp {{ formatNumber(row.total_unpaid_amount) }}
                            </div>
                        </div>
                        <div v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold">
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Lunas / Bebas Piutang</span>
                        </div>
                    </div>
                </td>

                <!-- 6. Aksi Lengkap -->
                <td class="text-right">
                    <div class="flex items-center justify-end gap-1.5 py-1">
                        <!-- Quick Reaktivasi Button -->
                        <button 
                            @click="openReactivateModal(row)"
                            class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-all flex items-center gap-1"
                            title="Aktifkan Kembali Layanan Pelanggan Ini"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Reaktivasi</span>
                        </button>

                        <!-- Cetak BAB -->
                        <a 
                            :href="`/customers/${row.id}/print-termination`"
                            target="_blank"
                            class="p-1.5 text-slate-600 hover:text-blue-600 hover:bg-blue-50 border border-slate-200 rounded-lg transition-colors"
                            title="Cetak Berita Acara Berhenti (BAB)"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                        </a>

                        <!-- Dropdown Aksi Lainnya -->
                        <div class="relative inline-block text-left" v-click-outside="() => activeDropdownId = null">
                            <button 
                                @click.stop="toggleDropdown(row.id)"
                                class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div 
                                v-if="activeDropdownId === row.id"
                                class="origin-top-right absolute right-0 mt-1 w-52 rounded-xl shadow-xl bg-white ring-1 ring-black ring-opacity-5 divide-y divide-slate-100 z-30 animate-fade-in-up text-xs text-left"
                            >
                                <div class="p-1">
                                    <!-- Tarik ONT -->
                                    <button 
                                        v-if="row.ont"
                                        @click="openPullOntModal(row); activeDropdownId = null"
                                        class="w-full text-left px-3 py-2 text-amber-700 hover:bg-amber-50 rounded-lg flex items-center gap-2 font-medium"
                                    >
                                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                                        Tarik ONT ke Gudang
                                    </button>

                                    <!-- Pemutihan Piutang -->
                                    <button 
                                        v-if="row.unpaid_invoices_count > 0"
                                        @click="openWriteOffModal(row); activeDropdownId = null"
                                        class="w-full text-left px-3 py-2 text-rose-700 hover:bg-rose-50 rounded-lg flex items-center gap-2 font-medium"
                                    >
                                        <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/></svg>
                                        Pemutihan Sisa Tagihan
                                    </button>

                                    <!-- WhatsApp Pilihan -->
                                    <button 
                                        v-if="row.phone"
                                        @click="openWhatsAppModal(row); activeDropdownId = null"
                                        class="w-full text-left px-3 py-2 text-emerald-700 hover:bg-emerald-50 rounded-lg flex items-center gap-2 font-medium"
                                    >
                                        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.885-9.89 9.885m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.746.953 3.71 1.455 5.712 1.457h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.415z"/></svg>
                                        Kirim WhatsApp
                                    </button>

                                    <!-- Detail Pelanggan -->
                                    <a 
                                        :href="`/customers/${row.id}`"
                                        class="w-full text-left px-3 py-2 text-slate-700 hover:bg-slate-50 rounded-lg flex items-center gap-2 font-medium"
                                    >
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Lihat Detail Lengkap
                                    </a>
                                </div>

                                <div class="p-1">
                                    <!-- Hapus Permanen -->
                                    <button 
                                        @click="openDeleteModal(row); activeDropdownId = null"
                                        class="w-full text-left px-3 py-2 text-rose-600 hover:bg-rose-50 rounded-lg flex items-center gap-2 font-medium"
                                    >
                                        <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Hapus Permanen Data
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
            </template>
        </DataTable>

        <!-- ========================================== -->
        <!-- MODAL REAKTIVASI LAYANAN                   -->
        <!-- ========================================== -->
        <Teleport to="body">
            <div v-if="showReactivateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm cursor-pointer" @click="showReactivateModal = false"></div>
                <div class="relative bg-white border border-slate-200 rounded-2xl shadow-2xl w-full max-w-md p-6 animate-fade-in-up">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Reaktivasi Layanan Pelanggan</h3>
                            <p class="text-xs text-slate-500">Aktifkan kembali pelanggan ke sistem dan sinkronkan RADIUS.</p>
                        </div>
                    </div>

                    <div v-if="selectedCustomer" class="p-3 rounded-xl bg-slate-50 border border-slate-100 mb-4 text-xs space-y-1">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Nama Pelanggan:</span>
                            <span class="font-bold text-slate-800">{{ selectedCustomer.name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">ID Pelanggan:</span>
                            <span class="font-mono text-slate-700">{{ selectedCustomer.customer_code }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Paket Terakhir:</span>
                            <span class="font-semibold text-slate-700">{{ selectedCustomer.package?.name || '-' }}</span>
                        </div>
                    </div>

                    <form @submit.prevent="submitReactivate" class="space-y-4 text-xs">
                        <!-- Pilihan Paket -->
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Paket Layanan</label>
                            <select 
                                v-model="reactivateForm.package_id" 
                                required
                                class="w-full rounded-xl border-slate-300 py-2 px-3 text-xs focus:ring-emerald-500 focus:border-emerald-500"
                            >
                                <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                    {{ pkg.name }} (Rp {{ formatNumber(pkg.price) }}/bln)
                                </option>
                            </select>
                        </div>

                        <!-- Target Status -->
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Target Status Reaktivasi</label>
                            <select 
                                v-model="reactivateForm.target_status" 
                                class="w-full rounded-xl border-slate-300 py-2 px-3 text-xs focus:ring-emerald-500 focus:border-emerald-500"
                            >
                                <option value="active">Langsung Aktif (Aktifkan Akun & RADIUS Sekarang)</option>
                                <option value="installing">Proses Pasang Ulang (Butuh Teknisi Pasang Perangkat)</option>
                                <option value="survey">Survey Ulang (Cek Kondisi Jalur/ODP Baru)</option>
                            </select>
                        </div>

                        <!-- Tanggal Aktivasi -->
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Tanggal Mulai Efektif</label>
                            <input 
                                type="date" 
                                v-model="reactivateForm.activation_date" 
                                class="w-full rounded-xl border-slate-300 py-2 px-3 text-xs focus:ring-emerald-500 focus:border-emerald-500"
                            />
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2 pt-3">
                            <button 
                                type="button" 
                                @click="showReactivateModal = false" 
                                class="w-full py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition-colors text-center"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                :disabled="isSubmitting"
                                class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl transition-all shadow-md flex items-center justify-center gap-2 disabled:opacity-50"
                            >
                                <svg v-if="isSubmitting" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ isSubmitting ? 'Memproses...' : 'Proses Reaktivasi' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ========================================== -->
        <!-- MODAL KONFIRMASI TARIK ONT                 -->
        <!-- ========================================== -->
        <Teleport to="body">
            <div v-if="showPullOntModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm cursor-pointer" @click="showPullOntModal = false"></div>
                <div class="relative bg-white border border-amber-200 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center animate-fade-in-up">
                    <div class="w-14 h-14 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center mx-auto mb-4 border-4 border-amber-50">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">Tarik ONT ke Gudang?</h3>
                    <p class="text-xs text-slate-500 mb-4">
                        Perangkat ONT milik pelanggan <strong class="text-slate-800">{{ selectedCustomer?.name }}</strong> akan dilepas dan statusnya dikembalikan ke <strong>Stok Tersedia di Gudang (In Stock)</strong>.
                    </p>

                    <div v-if="selectedCustomer?.ont" class="p-3 bg-amber-50/70 border border-amber-100 rounded-xl mb-5 text-xs text-left space-y-1 font-mono">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Serial Number:</span>
                            <span class="font-bold text-slate-900">{{ selectedCustomer.ont.serial_number }}</span>
                        </div>
                        <div class="flex justify-between font-sans">
                            <span class="text-slate-500">Model:</span>
                            <span class="text-slate-700">{{ selectedCustomer.ont.model || 'ONT Standar' }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button 
                            @click="showPullOntModal = false" 
                            class="w-full py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition-colors"
                        >
                            Batal
                        </button>
                        <button 
                            @click="submitPullOnt" 
                            :disabled="isSubmitting"
                            class="w-full py-2.5 px-4 bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs rounded-xl transition-all shadow-md flex items-center justify-center gap-1.5 disabled:opacity-50"
                        >
                            <svg v-if="isSubmitting" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>{{ isSubmitting ? 'Memproses...' : 'Tarik ke Gudang' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ========================================== -->
        <!-- MODAL PEMUTIHAN SISA PIUTANG               -->
        <!-- ========================================== -->
        <Teleport to="body">
            <div v-if="showWriteOffModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm cursor-pointer" @click="showWriteOffModal = false"></div>
                <div class="relative bg-white border border-rose-200 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center animate-fade-in-up">
                    <div class="w-14 h-14 bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center mx-auto mb-4 border-4 border-rose-50">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">Pemutihan Sisa Piutang?</h3>
                    <p class="text-xs text-slate-500 mb-4">
                        Seluruh sisa tagihan belum lunas milik pelanggan <strong class="text-slate-800">{{ selectedCustomer?.name }}</strong> akan dibatalkan/dihapus (pemutihan piutang).
                    </p>

                    <div v-if="selectedCustomer" class="p-3 bg-rose-50/70 border border-rose-100 rounded-xl mb-5 text-xs text-left space-y-1">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Jumlah Invoice:</span>
                            <span class="font-bold text-slate-800">{{ selectedCustomer.unpaid_invoices_count }} Tagihan</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Total Nominal:</span>
                            <span class="font-extrabold text-rose-600">Rp {{ formatNumber(selectedCustomer.total_unpaid_amount) }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button 
                            @click="showWriteOffModal = false" 
                            class="w-full py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition-colors"
                        >
                            Batal
                        </button>
                        <button 
                            @click="submitWriteOff" 
                            :disabled="isSubmitting"
                            class="w-full py-2.5 px-4 bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs rounded-xl transition-all shadow-md flex items-center justify-center gap-1.5 disabled:opacity-50"
                        >
                            <svg v-if="isSubmitting" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>{{ isSubmitting ? 'Memproses...' : 'Proses Pemutihan' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ========================================== -->
        <!-- MODAL PILIHAN PESAN WHATSAPP               -->
        <!-- ========================================== -->
        <Teleport to="body">
            <div v-if="showWhatsAppModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm cursor-pointer" @click="showWhatsAppModal = false"></div>
                <div class="relative bg-white border border-emerald-200 rounded-2xl shadow-2xl w-full max-w-md p-6 animate-fade-in-up">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.885-9.89 9.885m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.746.953 3.71 1.455 5.712 1.457h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.415z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Kirim WhatsApp</h3>
                            <p class="text-xs text-slate-500">Pilih template pesan resmi untuk {{ selectedCustomer?.name }}</p>
                        </div>
                    </div>

                    <div class="space-y-3 mb-5">
                        <!-- Template 1: Konfirmasi Penutupan & Tarik ONT -->
                        <div 
                            @click="selectedWaTemplate = 'closing'"
                            :class="selectedWaTemplate === 'closing' ? 'border-emerald-500 bg-emerald-50/50' : 'border-slate-200 hover:border-slate-300'"
                            class="p-3.5 rounded-xl border-2 cursor-pointer transition-all text-xs"
                        >
                            <div class="font-bold text-slate-900 flex items-center justify-between">
                                <span>1. Konfirmasi Penutupan & Penarikan Perangkat</span>
                                <span v-if="selectedWaTemplate === 'closing'" class="text-emerald-600 font-extrabold">&check;</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-1 line-clamp-2">
                                Pemberitahuan bahwa layanan telah dinonaktifkan permanen dan koordinasi jadwal penarikan alat ONT.
                            </p>
                        </div>

                        <!-- Template 2: Promo Reaktivasi -->
                        <div 
                            @click="selectedWaTemplate = 'promo'"
                            :class="selectedWaTemplate === 'promo' ? 'border-emerald-500 bg-emerald-50/50' : 'border-slate-200 hover:border-slate-300'"
                            class="p-3.5 rounded-xl border-2 cursor-pointer transition-all text-xs"
                        >
                            <div class="font-bold text-slate-900 flex items-center justify-between">
                                <span>2. Promo Penawaran Reaktivasi</span>
                                <span v-if="selectedWaTemplate === 'promo'" class="text-emerald-600 font-extrabold">&check;</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-1 line-clamp-2">
                                Mengajak pelanggan mengaktifkan kembali layanan dengan promo gratis biaya pasang / diskon langganan.
                            </p>
                        </div>

                        <!-- Template 3: Sisa Piutang -->
                        <div 
                            v-if="selectedCustomer?.unpaid_invoices_count > 0"
                            @click="selectedWaTemplate = 'debt'"
                            :class="selectedWaTemplate === 'debt' ? 'border-emerald-500 bg-emerald-50/50' : 'border-slate-200 hover:border-slate-300'"
                            class="p-3.5 rounded-xl border-2 cursor-pointer transition-all text-xs"
                        >
                            <div class="font-bold text-slate-900 flex items-center justify-between">
                                <span>3. Konfirmasi Sisa Tagihan Administrasi</span>
                                <span v-if="selectedWaTemplate === 'debt'" class="text-emerald-600 font-extrabold">&check;</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-1 line-clamp-2">
                                Pengingat sisa tagihan tertunggak sebesar Rp {{ formatNumber(selectedCustomer.total_unpaid_amount) }}.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button 
                            @click="showWhatsAppModal = false" 
                            class="w-full py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition-colors"
                        >
                            Batal
                        </button>
                        <button 
                            @click="sendWhatsAppMessage" 
                            class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl transition-all shadow-md flex items-center justify-center gap-1.5"
                        >
                            Buka WhatsApp Web / App
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ========================================== -->
        <!-- MODAL KONFIRMASI HAPUS PERMANEN            -->
        <!-- ========================================== -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-rose-950/60 backdrop-blur-sm cursor-pointer" @click="showDeleteModal = false"></div>
                <div class="relative bg-white border border-rose-200 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center animate-fade-in-up">
                    <div class="w-14 h-14 bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center mx-auto mb-4 border-4 border-rose-50">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-900 mb-1">Hapus Data Permanen?</h3>
                    <p class="text-xs text-slate-500 mb-5">
                        Data pelanggan <strong class="text-slate-800">{{ selectedCustomer?.name }}</strong> ({{ selectedCustomer?.customer_code }}) akan dihapus secara permanen dari sistem. Tindakan ini tidak dapat dibatalkan.
                    </p>

                    <div class="flex items-center gap-2">
                        <button 
                            @click="showDeleteModal = false" 
                            class="w-full py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition-colors"
                        >
                            Batal
                        </button>
                        <button 
                            @click="submitDeleteCustomer" 
                            :disabled="isSubmitting"
                            class="w-full py-2.5 px-4 bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs rounded-xl transition-all shadow-md flex items-center justify-center gap-1.5 disabled:opacity-50"
                        >
                            <svg v-if="isSubmitting" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>{{ isSubmitting ? 'Menghapus...' : 'Ya, Hapus Data' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    customers: Object,
    packages: Array,
    areas: Array,
    stats: Object,
    filters: Object,
    company: Object,
});

// Table columns
const columns = [
    { key: 'name', label: 'Pelanggan' },
    { key: 'package', label: 'Paket Terakhir' },
    { key: 'cancel_details', label: 'Penghentian' },
    { key: 'ont_device', label: 'Perangkat ONT' },
    { key: 'billing', label: 'Sisa Piutang' },
    { key: 'actions', label: 'Aksi', class: 'text-right' },
];

// Active dropdown tracker
const activeDropdownId = ref(null);
function toggleDropdown(id) {
    activeDropdownId.value = activeDropdownId.value === id ? null : id;
}

// Click outside directive implementation
const vClickOutside = {
    mounted(el, binding) {
        el.clickOutsideEvent = (event) => {
            if (!(el === event.target || el.contains(event.target))) {
                binding.value();
            }
        };
        document.body.addEventListener('click', el.clickOutsideEvent);
    },
    unmounted(el) {
        document.body.removeEventListener('click', el.clickOutsideEvent);
    }
};

// Filter states
const currentArea = ref(props.filters?.area_id || '');
const currentPackage = ref(props.filters?.package_id || '');
const currentOntStatus = ref(props.filters?.ont_status || '');
const currentBillingStatus = ref(props.filters?.billing_status || '');

const hasActiveFilters = computed(() => {
    return !!(currentArea.value || currentPackage.value || currentOntStatus.value || currentBillingStatus.value);
});

function applyFilters() {
    router.get('/customers/terminated', {
        search: props.filters?.search || undefined,
        area_id: currentArea.value || undefined,
        package_id: currentPackage.value || undefined,
        ont_status: currentOntStatus.value || undefined,
        billing_status: currentBillingStatus.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function filterByOntPending() {
    currentOntStatus.value = 'pending';
    applyFilters();
}

function resetFilters() {
    currentArea.value = '';
    currentPackage.value = '';
    currentOntStatus.value = '';
    currentBillingStatus.value = '';
    router.get('/customers/terminated', {}, {
        preserveState: false,
    });
}

// Current month name in Indonesian
const currentMonthName = computed(() => {
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return months[new Date().getMonth()];
});

// Formatters
function formatNumber(num) {
    if (!num) return '0';
    return Number(num).toLocaleString('id-ID');
}

function formatRupiah(num) {
    return 'Rp ' + formatNumber(num);
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch (e) {
        return dateStr;
    }
}

function cleanPhone(phone) {
    if (!phone) return '';
    let cleaned = phone.replace(/[^0-9]/g, '');
    if (cleaned.startsWith('0')) {
        cleaned = '62' + cleaned.substring(1);
    }
    return cleaned;
}

function getWhatsAppLink(phone) {
    return 'https://wa.me/' + cleanPhone(phone);
}

// Modal States & Selected Customer
const selectedCustomer = ref(null);
const isSubmitting = ref(false);

// 1. Reaktivasi
const showReactivateModal = ref(false);
const reactivateForm = ref({
    package_id: '',
    target_status: 'active',
    activation_date: new Date().toISOString().split('T')[0],
});

function openReactivateModal(customer) {
    selectedCustomer.value = customer;
    reactivateForm.value = {
        package_id: customer.package_id || (props.packages[0]?.id || ''),
        target_status: 'active',
        activation_date: new Date().toISOString().split('T')[0],
    };
    showReactivateModal.value = true;
}

function submitReactivate() {
    if (!selectedCustomer.value) return;
    isSubmitting.value = true;
    router.post(`/customers/${selectedCustomer.value.id}/reactivate`, reactivateForm.value, {
        onSuccess: () => {
            showReactivateModal.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Reaktivasi Berhasil',
                text: `Layanan pelanggan ${selectedCustomer.value.name} telah berhasil diaktifkan kembali.`,
                timer: 2500,
                showConfirmButton: false,
            });
        },
        onError: (err) => {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Reaktivasi',
                text: Object.values(err)[0] || 'Terjadi kesalahan saat reaktivasi.',
            });
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
}

// 2. Tarik ONT
const showPullOntModal = ref(false);
function openPullOntModal(customer) {
    selectedCustomer.value = customer;
    showPullOntModal.value = true;
}

function submitPullOnt() {
    if (!selectedCustomer.value) return;
    isSubmitting.value = true;
    router.post(`/customers/${selectedCustomer.value.id}/pull-ont`, {}, {
        onSuccess: () => {
            showPullOntModal.value = false;
            Swal.fire({
                icon: 'success',
                title: 'ONT Berhasil Ditarik',
                text: 'Perangkat ONT telah berhasil dikembalikan ke stok gudang.',
                timer: 2000,
                showConfirmButton: false,
            });
        },
        onError: (err) => {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menarik ONT',
                text: Object.values(err)[0] || 'Terjadi kesalahan saat menarik ONT.',
            });
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
}

// 3. Pemutihan Piutang
const showWriteOffModal = ref(false);
function openWriteOffModal(customer) {
    selectedCustomer.value = customer;
    showWriteOffModal.value = true;
}

function submitWriteOff() {
    if (!selectedCustomer.value) return;
    isSubmitting.value = true;
    router.post(`/customers/${selectedCustomer.value.id}/write-off-invoices`, {}, {
        onSuccess: () => {
            showWriteOffModal.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Pemutihan Piutang Berhasil',
                text: `Seluruh sisa tagihan pelanggan ${selectedCustomer.value.name} telah dibatalkan.`,
                timer: 2000,
                showConfirmButton: false,
            });
        },
        onError: (err) => {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Pemutihan',
                text: Object.values(err)[0] || 'Terjadi kesalahan saat pemutihan piutang.',
            });
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
}

// 4. WhatsApp Sender
const showWhatsAppModal = ref(false);
const selectedWaTemplate = ref('closing');

function openWhatsAppModal(customer) {
    selectedCustomer.value = customer;
    selectedWaTemplate.value = customer.unpaid_invoices_count > 0 ? 'closing' : 'promo';
    showWhatsAppModal.value = true;
}

function sendWhatsAppMessage() {
    if (!selectedCustomer.value || !selectedCustomer.value.phone) return;
    const c = selectedCustomer.value;
    const companyName = props.company?.name || 'ISP Provider';
    let text = '';

    if (selectedWaTemplate.value === 'closing') {
        text = `Halo Bpk/Ibu *${c.name}*,\n\nKami dari *${companyName}*. Menindaklanjuti status permohonan berhenti berlangganan (ID: *${c.customer_code}*), layanan internet telah resmi dinonaktifkan.\n\nPetugas/teknisi kami akan menjadwalkan pengambilan perangkat modem/ONT di lokasi Anda. Terima kasih banyak atas kepercayaan Anda telah menjadi bagian dari keluarga kami. 🙏`;
    } else if (selectedWaTemplate.value === 'promo') {
        text = `Halo Bpk/Ibu *${c.name}*,\n\nKami dari *${companyName}*. Kami merindukan Anda! 🤗\n\nSaat ini kami menghadirkan *Promo Reaktivasi Spesial*: Bebas biaya pasang ulang dan penawaran paket internet hemat. Jika Bpk/Ibu berminat untuk mengaktifkan kembali layanan internet, silakan balas pesan ini ya. Terima kasih!`;
    } else if (selectedWaTemplate.value === 'debt') {
        text = `Halo Bpk/Ibu *${c.name}*,\n\nKami dari *${companyName}*. Menginformasikan terkait penutupan layanan internet ID *${c.customer_code}*, tercatat masih terdapat sisa tagihan administrasi sebesar *Rp ${formatNumber(c.total_unpaid_amount)}*.\n\nMohon konfirmasi penyelesaian administrasi atau hubungi kantor layanan kami. Terima kasih atas kerja samanya.`;
    }

    const url = `https://wa.me/${cleanPhone(c.phone)}?text=${encodeURIComponent(text)}`;
    window.open(url, '_blank');
    showWhatsAppModal.value = false;
}

// 5. Hapus Permanen
const showDeleteModal = ref(false);
function openDeleteModal(customer) {
    selectedCustomer.value = customer;
    showDeleteModal.value = true;
}

function submitDeleteCustomer() {
    if (!selectedCustomer.value) return;
    isSubmitting.value = true;
    router.delete(`/customers/${selectedCustomer.value.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Data Dihapus',
                text: 'Pelanggan berhasil dihapus secara permanen dari sistem.',
                timer: 2000,
                showConfirmButton: false,
            });
        },
        onError: (err) => {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menghapus',
                text: Object.values(err)[0] || 'Terjadi kesalahan saat menghapus data.',
            });
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
}
</script>
