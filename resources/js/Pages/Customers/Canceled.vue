<template>
    <AppLayout title="Data Pembatalan" subtitle="Pelanggan yang dibatalkan pendaftarannya">
        
        <!-- Panduan & SOP Penggunaan Fitur -->
        <PipelineGuideCard stage="canceled" />
        
        <!-- Statistik -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="glass-card p-6 animate-fade-in-up" style="animation-delay: 0.1s">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Pelanggan Dibatalkan</p>
                        <h3 class="text-3xl font-bold text-gray-900">{{ stats.total }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <DataTable
            :data="customers.data"
            :columns="columns"
            :pagination="customers"
            search-placeholder="Cari nama, kode, nomor telepon, alamat..."
            search-route="/customers/canceled"
            :selectable="canDelete"
            v-model:selected="selectedIds"
        >
            <template #actions>
                <div class="flex items-center gap-2">
                    <button 
                        v-if="canRestore && selectedIds.length > 0" 
                        @click="showBulkRestoreModal = true"
                        class="px-4 py-2 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 font-medium text-sm rounded-xl transition-colors border border-emerald-200 flex items-center gap-2 shadow-sm"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Pulihkan Terpilih ({{ selectedIds.length }})
                    </button>

                    <button 
                        v-if="canDelete && selectedIds.length > 0" 
                        @click="showDeleteAllModal = true" 
                        class="px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 font-medium text-sm rounded-xl transition-colors border border-red-200 flex items-center gap-2 shadow-sm"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Hapus Terpilih ({{ selectedIds.length }})
                    </button>
                </div>
            </template>

            <template #row="{ row }">
                <!-- 1. Pelanggan -->
                <td>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-sm font-bold shrink-0">
                            {{ (row.name || '?').charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <button @click="viewCustomer(row)" class="text-left font-semibold text-gray-900 hover:text-red-600 transition-colors">
                                {{ row.name }}
                            </button>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-xs font-mono text-gray-500">{{ row.customer_code }}</span>
                                <span :class="['px-2 py-0.5 rounded text-[10px] font-bold flex items-center gap-1', getCancelInfo(row).badgeClass]">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="getCancelInfo(row).dotClass"></span>
                                    {{ getCancelInfo(row).label }}
                                </span>
                                <span v-if="row.status === 'terminated'" class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-rose-100 text-rose-700">
                                    Survey Tidak Layak
                                </span>
                            </div>
                        </div>
                    </div>
                </td>

                <!-- 2. Telepon -->
                <td class="text-sm text-gray-600 whitespace-nowrap">
                    <a v-if="row.phone" :href="`https://wa.me/${cleanPhone(row.phone)}`" target="_blank" class="hover:text-emerald-600 transition-colors flex items-center gap-1.5" title="Chat WhatsApp">
                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                        {{ row.phone }}
                    </a>
                    <span v-else class="text-gray-400 italic">-</span>
                </td>

                <!-- 3. Alamat & Area -->
                <td class="max-w-[220px]">
                    <div class="truncate text-xs text-gray-700 font-medium" :title="row.address">
                        {{ row.address || '-' }}
                    </div>
                    <div v-if="row.area || row.area_model?.name" class="text-[11px] text-gray-500 mt-0.5">
                        <span class="inline-block px-1.5 py-0.5 bg-gray-100 rounded text-gray-600">
                            {{ row.area || row.area_model?.name }}
                        </span>
                    </div>
                </td>

                <!-- 4. Paket -->
                <td>
                    <span v-if="row.package" class="inline-flex px-2 py-0.5 bg-purple-50 text-purple-600 rounded-md text-xs font-medium border border-purple-100">
                        {{ row.package.name }}
                    </span>
                    <span v-else class="text-xs text-gray-400 italic">-</span>
                </td>

                <!-- 5. Alasan Batal -->
                <td class="max-w-[280px]">
                    <div class="p-2.5 bg-red-50/60 rounded-xl border border-red-100 space-y-1">
                        <div class="flex items-center gap-1.5">
                            <span :class="['px-1.5 py-0.5 rounded text-[10px] font-bold tracking-wide uppercase', getCancelInfo(row).badgeClass]">
                                {{ getCancelInfo(row).label }}
                            </span>
                        </div>
                        <p class="text-xs text-red-900 line-clamp-2 font-medium" :title="row.cancel_reason">
                            {{ row.cancel_reason || (row.status === 'terminated' ? 'Hasil survey: Tidak layak' : 'Dibatalkan tanpa keterangan') }}
                        </p>
                    </div>
                </td>

                <!-- 6. Tanggal Batal -->
                <td class="text-xs text-gray-500 whitespace-nowrap">
                    {{ formatDate(row.cancel_date || row.updated_at) }}
                </td>
            </template>

            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-1">
                    <!-- Detail Pelanggan -->
                    <button 
                        @click="viewCustomer(row)" 
                        class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-blue-600 transition-colors" 
                        title="Lihat Detail Lengkap"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>

                    <!-- Pulihkan ke Booking -->
                    <button 
                        v-if="canRestore"
                        @click="openRestoreModal(row)" 
                        class="p-2 rounded-lg text-gray-500 hover:bg-emerald-50 hover:text-emerald-600 transition-colors" 
                        title="Kembalikan ke Booking"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>

                    <!-- Hapus Permanen -->
                    <button 
                        v-if="canDelete" 
                        @click="openDeleteModal(row)" 
                        class="p-2 rounded-lg text-gray-500 hover:bg-red-50 hover:text-red-500 transition-colors" 
                        title="Hapus Permanen"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- Modal Detail Pelanggan -->
        <Teleport to="body">
            <div v-if="showDetailModal && selectedCustomer" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm cursor-pointer" @click="closeDetailModal"></div>
                <div class="relative bg-white border border-gray-200 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto animate-fade-in-up">
                    <div class="sticky top-0 bg-white/95 backdrop-blur-xl border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span class="p-1.5 rounded-lg bg-red-100 text-red-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </span>
                            Detail Pelanggan Dibatalkan
                        </h3>
                        <button @click="closeDetailModal" class="text-gray-500 hover:text-gray-900 transition-colors bg-gray-50 hover:bg-gray-100 p-2 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- Box Alasan Pembatalan Utama -->
                        <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-red-100 text-red-600 rounded-lg shrink-0 mt-0.5">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-sm font-bold text-red-900">Alasan Pembatalan</h4>
                                        <span :class="['px-2 py-0.5 rounded text-[10px] font-bold', getCancelInfo(selectedCustomer).badgeClass]">
                                            {{ getCancelInfo(selectedCustomer).label }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-red-700 mt-1 whitespace-pre-line">
                                        {{ selectedCustomer.cancel_reason || (selectedCustomer.status === 'terminated' ? 'Hasil survey: Tidak layak (Unfeasible)' : 'Dibatalkan tanpa keterangan.') }}
                                    </p>
                                    <p class="text-xs text-red-500 mt-2 font-mono">
                                        Tanggal Batal: {{ formatDate(selectedCustomer.cancel_date || selectedCustomer.updated_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Data Utama -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Nama Pelanggan</p>
                                <p class="text-sm text-gray-900 font-semibold">{{ selectedCustomer.name }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Kode Pelanggan</p>
                                <p class="text-sm font-mono text-gray-700 font-medium">{{ selectedCustomer.customer_code }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Nomor Telepon / WA</p>
                                <p class="text-sm text-gray-900">{{ selectedCustomer.phone || '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Email</p>
                                <p class="text-sm text-gray-900">{{ selectedCustomer.email || '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Area / Wilayah</p>
                                <p class="text-sm text-gray-900 font-medium">
                                    <span class="inline-flex px-2 py-0.5 bg-gray-100 text-gray-700 rounded-md text-xs">
                                        {{ selectedCustomer.area || selectedCustomer.area_model?.name || '-' }}
                                    </span>
                                </p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Paket Langganan</p>
                                <p class="text-sm text-gray-900 font-medium">
                                    <span class="inline-flex px-2 py-0.5 bg-purple-50 text-purple-600 rounded-md text-xs font-semibold border border-purple-100">
                                        {{ selectedCustomer.package?.name || 'Belum dipilih' }}
                                    </span>
                                </p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Marketing / Sales</p>
                                <p class="text-sm text-gray-900">{{ selectedCustomer.sales?.name || '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Tanggal Registrasi</p>
                                <p class="text-sm text-gray-900">{{ formatDate(selectedCustomer.registration_date) }}</p>
                            </div>
                        </div>

                        <!-- Alamat -->
                        <div class="space-y-1">
                            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Alamat Lengkap</p>
                            <div class="bg-gray-50 rounded-xl p-3 border border-gray-200 text-sm text-gray-700">
                                {{ selectedCustomer.address || 'Tidak ada alamat.' }}
                            </div>
                        </div>

                        <!-- Foto KTP & Catatan -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Foto KTP</p>
                                <div v-if="selectedCustomer.identity_photo" class="mt-1 relative group overflow-hidden rounded-xl border border-gray-200">
                                    <img :src="`/storage/${selectedCustomer.identity_photo}`" alt="Foto KTP" class="w-full h-auto max-h-48 object-cover" />
                                    <div class="absolute inset-0 bg-gray-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <a :href="`/storage/${selectedCustomer.identity_photo}`" target="_blank" class="px-3 py-1.5 bg-white/80 hover:bg-white rounded-lg text-xs font-medium text-gray-900 transition-colors">Lihat Foto</a>
                                    </div>
                                </div>
                                <div v-else class="bg-gray-50 rounded-xl p-4 border border-gray-200 text-xs text-gray-400 italic text-center">
                                    Tidak ada foto KTP
                                </div>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Catatan Tambahan</p>
                                <div class="bg-gray-50 rounded-xl p-3 border border-gray-200 text-xs text-gray-600 min-h-[80px]">
                                    {{ selectedCustomer.notes || 'Tidak ada catatan.' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sticky bottom-0 bg-white/95 backdrop-blur-xl border-t border-gray-200 px-6 py-4 flex items-center justify-between">
                        <button 
                            v-if="canRestore"
                            @click="closeDetailModal(); openRestoreModal(selectedCustomer)" 
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-xl transition-colors flex items-center gap-2 shadow-sm shadow-emerald-200"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Kembalikan ke Booking
                        </button>
                        <div v-else></div>
                        <button @click="closeDetailModal" class="px-5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-xl text-sm font-medium transition-colors">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Konfirmasi Restore Tunggal -->
        <Teleport to="body">
            <div v-if="showRestoreModal && selectedCustomerToRestore" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm cursor-pointer" @click="showRestoreModal = false"></div>
                <div class="relative bg-white border border-gray-200 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center animate-fade-in-up">
                    <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-emerald-50">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                        Kembalikan ke Booking?
                    </h3>
                    <p class="text-sm text-gray-500 mb-6">
                        Pelanggan <strong>{{ selectedCustomerToRestore.name }}</strong> akan dipindahkan kembali ke menu antrean <strong>Data Booking</strong>.
                    </p>
                    <div class="flex items-center justify-center gap-3">
                        <button @click="showRestoreModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-sm rounded-xl transition-colors w-full">
                            Batal
                        </button>
                        <button 
                            @click="submitRestore" 
                            :disabled="isRestoring" 
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-xl transition-colors w-full flex justify-center items-center shadow-sm shadow-emerald-200 disabled:opacity-50"
                        >
                            <svg v-if="isRestoring" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span v-else>Ya, Kembalikan</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Konfirmasi Bulk Restore -->
        <Teleport to="body">
            <div v-if="showBulkRestoreModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm cursor-pointer" @click="showBulkRestoreModal = false"></div>
                <div class="relative bg-white border border-gray-200 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center animate-fade-in-up">
                    <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-emerald-50">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                        Pulihkan {{ selectedIds.length }} Pelanggan?
                    </h3>
                    <p class="text-sm text-gray-500 mb-6">
                        Semua data terpilih akan dipindahkan kembali ke menu <strong>Data Booking</strong> agar dapat diproses kembali.
                    </p>
                    <div class="flex items-center justify-center gap-3">
                        <button @click="showBulkRestoreModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-sm rounded-xl transition-colors w-full">
                            Batal
                        </button>
                        <button 
                            @click="submitBulkRestore" 
                            :disabled="isRestoring" 
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-xl transition-colors w-full flex justify-center items-center shadow-sm shadow-emerald-200 disabled:opacity-50"
                        >
                            <svg v-if="isRestoring" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span v-else>Pulihkan Semua</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Konfirmasi Hapus Satu Data -->
        <Teleport to="body">
            <div v-if="showDeleteModal && selectedCustomerToDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-red-900/60 backdrop-blur-sm cursor-pointer" @click="showDeleteModal = false"></div>
                <div class="relative bg-white border border-red-200 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center animate-fade-in-up">
                    <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-red-50">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Hapus Data Permanen?</h3>
                    <p class="text-sm text-gray-500 mb-6">
                        Data pelanggan <strong>{{ selectedCustomerToDelete.name }}</strong> akan dihapus secara permanen dari sistem. Tindakan ini tidak dapat dibatalkan.
                    </p>
                    <div class="flex items-center justify-center gap-3">
                        <button @click="showDeleteModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-sm rounded-xl transition-colors w-full">Batal</button>
                        <button @click="submitDelete" :disabled="isDeleting" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium text-sm rounded-xl transition-colors w-full flex justify-center items-center shadow-sm shadow-red-200 disabled:opacity-50">
                            <svg v-if="isDeleting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span v-else>Hapus</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Konfirmasi Hapus Semua Data -->
        <Teleport to="body">
            <div v-if="showDeleteAllModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-red-900/60 backdrop-blur-sm cursor-pointer" @click="showDeleteAllModal = false"></div>
                <div class="relative bg-white border border-red-200 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center animate-fade-in-up">
                    <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-red-50">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                        Hapus {{ selectedIds.length }} Data Terpilih?
                    </h3>
                    <p class="text-sm text-gray-500 mb-6">
                        Data terpilih akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.
                    </p>
                    <div class="flex items-center justify-center gap-3">
                        <button @click="showDeleteAllModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-sm rounded-xl transition-colors w-full">Batal</button>
                        <button @click="submitBulkDelete" :disabled="isDeletingAll" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium text-sm rounded-xl transition-colors w-full flex justify-center items-center shadow-sm shadow-red-200 disabled:opacity-50">
                            <svg v-if="isDeletingAll" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span v-else>Hapus Semua</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PipelineGuideCard from '@/Components/Customers/PipelineGuideCard.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({
    customers: Object,
    filters: Object,
    stats: Object,
});

const page = usePage();
const selectedIds = ref([]);

const columns = [
    { key: 'name', label: 'Pelanggan' },
    { key: 'phone', label: 'Telepon / WA' },
    { key: 'address', label: 'Alamat & Area' },
    { key: 'package', label: 'Paket' },
    { key: 'cancel_reason', label: 'Alasan Batal' },
    { key: 'date', label: 'Tgl Batal' },
];

// Helper Clean Phone for WhatsApp
function cleanPhone(phone) {
    if (!phone) return '';
    let cleaned = phone.replace(/[^0-9]/g, '');
    if (cleaned.startsWith('0')) {
        cleaned = '62' + cleaned.substring(1);
    }
    return cleaned;
}

// Helper Deteksi Tahap Pembatalan
function getCancelInfo(customer) {
    if (!customer) return { stage: 'booking', label: 'Batal Booking', badgeClass: 'bg-amber-50 text-amber-700 border border-amber-200', dotClass: 'bg-amber-500' };
    
    let stage = customer.cancel_stage;
    const reason = (customer.cancel_reason || '').toLowerCase();
    
    if (!stage) {
        if (customer.status === 'terminated' || reason.includes('survey')) {
            stage = 'survey';
        } else if (reason.includes('pasang') || reason.includes('instal')) {
            stage = 'installing';
        } else if (reason.includes('aktivasi')) {
            stage = 'activation';
        } else {
            stage = 'booking';
        }
    }

    switch (stage) {
        case 'survey':
            return {
                stage: 'survey',
                label: 'Batal Survey',
                badgeClass: 'bg-blue-50 text-blue-700 border border-blue-200',
                dotClass: 'bg-blue-500',
            };
        case 'installing':
            return {
                stage: 'installing',
                label: 'Batal Pasang',
                badgeClass: 'bg-indigo-50 text-indigo-700 border border-indigo-200',
                dotClass: 'bg-indigo-500',
            };
        case 'activation':
            return {
                stage: 'activation',
                label: 'Batal Aktivasi',
                badgeClass: 'bg-purple-50 text-purple-700 border border-purple-200',
                dotClass: 'bg-purple-500',
            };
        case 'booking':
        default:
            return {
                stage: 'booking',
                label: 'Batal Booking',
                badgeClass: 'bg-amber-50 text-amber-700 border border-amber-200',
                dotClass: 'bg-amber-500',
            };
    }
}

// Format Date
function formatDate(dateStr) {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    } catch (e) {
        return dateStr;
    }
}

// Permissions Check
const canDelete = computed(() => {
    try {
        if (!page?.props?.auth?.user) return false;
        const user = page.props.auth.user;
        
        if (user.role && typeof user.role === 'string') {
            const roleStr = user.role.toLowerCase().trim();
            if (roleStr === 'admin' || roleStr === 'super admin' || roleStr.includes('admin')) {
                return true;
            }
        }
        
        let roles = [];
        if (Array.isArray(user.roles)) roles = user.roles;
        else if (user.roles) roles = Object.values(user.roles);
        
        for (let r of roles) {
            if (typeof r === 'string') {
                const rStr = r.toLowerCase().trim();
                if (rStr === 'admin' || rStr === 'super admin' || rStr.includes('admin')) return true;
            }
        }
        
        let perms = [];
        if (Array.isArray(user.permissions)) perms = user.permissions;
        else if (user.permissions) perms = Object.values(user.permissions);
        
        return perms.includes('menu_customers_booking') || perms.includes('customers_booking_delete') || perms.includes('customers_delete');
    } catch (e) {
        return false;
    }
});

const canRestore = computed(() => {
    try {
        if (!page?.props?.auth?.user) return false;
        const user = page.props.auth.user;
        
        if (user.role && typeof user.role === 'string') {
            const roleStr = user.role.toLowerCase().trim();
            if (roleStr === 'admin' || roleStr === 'super admin' || roleStr.includes('admin')) {
                return true;
            }
        }
        
        let roles = [];
        if (Array.isArray(user.roles)) roles = user.roles;
        else if (user.roles) roles = Object.values(user.roles);
        
        for (let r of roles) {
            if (typeof r === 'string') {
                const rStr = r.toLowerCase().trim();
                if (rStr === 'admin' || rStr === 'super admin' || rStr.includes('admin')) return true;
            }
        }
        
        let perms = [];
        if (Array.isArray(user.permissions)) perms = user.permissions;
        else if (user.permissions) perms = Object.values(user.permissions);
        
        return perms.includes('menu_customers_booking') || perms.includes('customers_booking_edit') || perms.includes('customers_edit');
    } catch (e) {
        return false;
    }
});

// Modal Detail Pelanggan
const showDetailModal = ref(false);
const selectedCustomer = ref(null);

function viewCustomer(customer) {
    selectedCustomer.value = customer;
    showDetailModal.value = true;
}

function closeDetailModal() {
    showDetailModal.value = false;
    selectedCustomer.value = null;
}

// Modal Restore Tunggal
const showRestoreModal = ref(false);
const selectedCustomerToRestore = ref(null);
const isRestoring = ref(false);

function openRestoreModal(customer) {
    selectedCustomerToRestore.value = customer;
    showRestoreModal.value = true;
}

function submitRestore() {
    if (!selectedCustomerToRestore.value) return;
    
    isRestoring.value = true;
    router.post(`/customers/${selectedCustomerToRestore.value.id}/restore`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            showRestoreModal.value = false;
            selectedCustomerToRestore.value = null;
        },
        onFinish: () => {
            isRestoring.value = false;
        }
    });
}

// Modal Restore Massal
const showBulkRestoreModal = ref(false);

function submitBulkRestore() {
    if (selectedIds.value.length === 0) return;
    
    isRestoring.value = true;
    router.post('/customers/bulk-restore', {
        ids: selectedIds.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showBulkRestoreModal.value = false;
            selectedIds.value = [];
        },
        onFinish: () => {
            isRestoring.value = false;
        }
    });
}

// Modal Hapus Tunggal
const showDeleteModal = ref(false);
const selectedCustomerToDelete = ref(null);
const isDeleting = ref(false);

function openDeleteModal(customer) {
    selectedCustomerToDelete.value = customer;
    showDeleteModal.value = true;
}

function submitDelete() {
    if (!selectedCustomerToDelete.value) return;
    
    isDeleting.value = true;
    router.post(`/customers/${selectedCustomerToDelete.value.id}/delete`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            selectedCustomerToDelete.value = null;
        },
        onFinish: () => {
            isDeleting.value = false;
        }
    });
}

// Modal Hapus Massal
const showDeleteAllModal = ref(false);
const isDeletingAll = ref(false);

function submitBulkDelete() {
    if (selectedIds.value.length === 0) return;
    
    isDeletingAll.value = true;
    router.post('/customers/bulk-destroy', {
        ids: selectedIds.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteAllModal.value = false;
            selectedIds.value = [];
        },
        onFinish: () => {
            isDeletingAll.value = false;
        }
    });
}
</script>
