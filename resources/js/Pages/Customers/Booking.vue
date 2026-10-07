<template>
    <AppLayout title="Data Booking" subtitle="Calon pelanggan yang baru mendaftar">
        
        <!-- Statistik -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Total Booking -->
            <div class="glass-card p-6 animate-fade-in-up" style="animation-delay: 0.1s">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Pendaftar</p>
                        <h3 class="text-3xl font-bold text-gray-900">{{ stats.total }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Menunggu Survey -->
            <div class="glass-card p-6 animate-fade-in-up" style="animation-delay: 0.2s">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Baru (Menunggu Survey)</p>
                        <h3 class="text-3xl font-bold text-gray-900">{{ stats.baru }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-yellow-50 text-yellow-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Telah Disurvey -->
            <div class="glass-card p-6 animate-fade-in-up" style="animation-delay: 0.3s">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Terkirim (Data Survey)</p>
                        <h3 class="text-3xl font-bold text-gray-900">{{ stats.disurvey }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-green-50 text-green-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <DataTable
            :columns="columns"
            :data="customers.data"
            :pagination="customers"
            searchPlaceholder="Cari nama, telepon..."
            searchRoute="/customers/booking"
            selectable
            v-model:selected="selectedIds"
        >
            <template #filters>
                <div class="flex items-center gap-2">
                    <input type="date" v-model="filterDate" class="form-input w-36 text-sm" title="Tanggal Daftar" />
                    <select v-model="filterArea" class="form-select w-36 text-sm">
                        <option value="">Semua Area</option>
                        <option v-for="area in areas" :key="area" :value="area">{{ area }}</option>
                    </select>
                    <select v-model="filterStatus" class="form-select w-36 text-sm">
                        <option value="">Semua Status</option>
                        <option value="booking">Baru (Booking)</option>
                        <option value="survey">Disurvey</option>
                        <option value="installing">Proses Pasang</option>
                        <option value="active">Aktif</option>
                    </select>
                    <button @click="applyFilters" class="px-3 py-2 bg-blue-50 text-blue-600 font-medium rounded-lg hover:bg-blue-100 transition-colors text-sm border border-blue-200">
                        Tampilkan
                    </button>
                    <button @click="resetFilters" class="px-3 py-2 bg-gray-50 text-gray-600 font-medium rounded-lg hover:bg-gray-100 transition-colors text-sm border border-gray-200">
                        Reset
                    </button>
                </div>
            </template>

            <template #actions>
                <div class="flex items-center gap-2">
                    <button v-if="canDelete && selectedIds.length > 0" 
                        @click="confirmDeleteAll" 
                        class="px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 font-medium text-sm rounded-xl transition-colors border border-red-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus Terpilih ({{ selectedIds.length }})
                    </button>
                    <Link href="/customers/create" class="btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Booking Baru
                    </Link>
                </div>
            </template>

            <template #row="{ row }">
                <td>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-yellow-500 to-orange-500 flex items-center justify-center text-sm font-bold text-gray-900 shrink-0">
                            {{ row.name.charAt(0) }}
                        </div>
                        <div>
                            <Link :href="`/customers/${row.id}`" class="text-gray-900 font-medium hover:text-blue-400 transition-colors">
                                {{ row.name }}
                            </Link>
                            <p class="text-xs text-gray-500 font-mono">{{ row.customer_code }}</p>
                        </div>
                    </div>
                </td>
                <td class="text-gray-500">{{ row.phone }}</td>
                <td class="max-w-[250px] truncate text-gray-500 text-xs">{{ row.address }}</td>
                <td>
                    <span v-if="row.package" class="inline-flex px-2 py-1 bg-purple-50 text-purple-600 rounded-md text-xs font-medium border border-purple-100">
                        {{ row.package.name }}
                    </span>
                    <span v-else class="text-xs text-gray-400 italic">-</span>
                </td>
                <td class="text-xs text-gray-700 font-medium">
                    <span v-if="row.sales" class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        {{ row.sales.name }}
                    </span>
                    <span v-else class="text-gray-400 italic">-</span>
                </td>
                <td class="text-xs text-gray-500">{{ row.registration_date }}</td>
                <td>
                    <div v-if="row.area" class="text-xs font-medium text-gray-700 bg-gray-100 px-2 py-1 rounded-md inline-block">
                        {{ row.area }}
                    </div>
                    <span v-else class="text-xs text-gray-400 italic">Belum ada</span>
                </td>
            </template>

            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-1">
                    <button @click="openWhatsApp(row)" class="p-2 rounded-lg text-gray-500 hover:bg-green-50 hover:text-green-500 transition-all" title="Kirim Pesan WhatsApp">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.666.598 1.236.786 1.41.874.174.086.275.072.376-.043l.42-.519c.101-.116.202-.097.361-.044.159.058 1.012.477 1.185.563.173.087.289.129.332.202.043.073.043.423-.101.827z"/>
                        </svg>
                    </button>
                    <button v-if="row.status === 'booking'" @click="requestSurvey(row)" class="p-2 rounded-lg text-gray-500 hover:bg-orange-50 hover:text-orange-500 transition-all" title="Pindah ke Data Survey">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <span v-else class="p-2 rounded-lg text-orange-400 font-medium text-xs flex items-center" title="Sudah Masuk Proses Selanjutnya">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Terkirim
                    </span>
                    <button @click="viewCustomer(row)" class="p-2 rounded-lg text-gray-500 hover:bg-white hover:text-blue-400 transition-all" title="Detail Booking">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                    <Link :href="`/customers/${row.id}/edit`" class="p-2 rounded-lg text-gray-500 hover:bg-white hover:text-yellow-400 transition-all" title="Edit Booking">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                    </Link>
                    <button @click="sendWa(row)" class="p-2 rounded-lg text-green-500 hover:bg-green-50 hover:text-green-600 transition-colors" title="Kirim Pesan WA ke Petugas">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </button>
                    <button v-if="canDelete" 
                        @click="confirmDelete(row)" 
                        class="p-2 rounded-lg text-gray-500 hover:bg-red-50 hover:text-red-500 transition-all" title="Hapus Booking">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </template>
        </DataTable>

        <WaModal :show="showWaModal" :customerRow="waCustomerRow" @close="showWaModal = false" />

        <!-- Modal Detail Booking -->
        <Teleport to="body">
            <div v-if="showViewModal && selectedCustomer" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm cursor-pointer" @click="closeViewModal"></div>
                <div class="relative bg-white border border-gray-200 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto animate-fade-in-up">
                    <div class="sticky top-0 bg-white/90 backdrop-blur-xl border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span class="p-1.5 rounded-lg bg-blue-500/20 text-blue-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </span>
                            Detail Form Booking
                        </h3>
                        <button @click="closeViewModal" class="text-gray-500 hover:text-gray-900 transition-colors bg-gray-50 hover:bg-white p-2 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    
                    <div class="p-6 space-y-6">
                        <!-- Info Utama -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Nama Lengkap</p>
                                <p class="text-sm text-gray-900 font-medium">{{ selectedCustomer.name }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Kode Pelanggan</p>
                                <p class="text-sm text-blue-400 font-mono">{{ selectedCustomer.customer_code }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Area / Wilayah</p>
                                <p class="text-sm text-gray-900 font-medium">
                                    <span class="inline-flex px-2 py-1 bg-blue-500/20 text-blue-400 rounded-md border border-blue-500/30">
                                        {{ selectedCustomer.area || '-' }}
                                    </span>
                                </p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Nomor HP / WA</p>
                                <p class="text-sm text-gray-900">{{ selectedCustomer.phone }}</p>
                            </div>
                        </div>

                        <hr class="border-white/5">

                        <!-- Alamat & Paket -->
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 gap-6">
                                <div class="space-y-1">
                                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Alamat Lengkap</p>
                                    <p class="text-sm text-gray-600 leading-relaxed">{{ selectedCustomer.address }}</p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                                <div class="space-y-1">
                                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Titik Koordinat Lokasi</p>
                                    <div v-if="selectedCustomer.latitude && selectedCustomer.longitude" class="flex flex-col items-start gap-2 mt-1">
                                        <p class="text-sm text-cyan-400 font-mono">{{ selectedCustomer.latitude }}, {{ selectedCustomer.longitude }}</p>
                                        <a :href="`https://www.google.com/maps?q=${selectedCustomer.latitude},${selectedCustomer.longitude}`" target="_blank" class="text-xs bg-white hover:bg-white/20 text-gray-900 px-3 py-1.5 rounded-lg transition-colors inline-flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                            Buka di Maps
                                        </a>
                                    </div>
                                    <p v-else class="text-sm text-gray-500">-</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Paket Langganan</p>
                                    <p class="text-sm text-gray-900 font-medium">
                                        <span class="inline-flex px-2 py-1 bg-purple-500/20 text-purple-400 rounded-md border border-purple-500/30">
                                            {{ selectedCustomer.package?.name || 'Belum dipilih' }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <hr class="border-white/5">

                        <!-- Catatan Khusus -->
                        <!-- Foto KTP & Catatan -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-4">
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Foto KTP</p>
                                <div v-if="selectedCustomer.identity_photo" class="mt-2 relative group overflow-hidden rounded-xl border border-gray-200">
                                    <img :src="`/storage/${selectedCustomer.identity_photo}`" alt="Foto KTP" class="w-full h-auto max-h-48 object-cover transition-transform duration-300 group-hover:scale-105" />
                                    <div class="absolute inset-0 bg-gray-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <a :href="`/storage/${selectedCustomer.identity_photo}`" target="_blank" class="px-3 py-1.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg text-xs font-medium text-gray-900 transition-colors">Lihat Penuh</a>
                                    </div>
                                </div>
                                <div v-else class="bg-gray-50 rounded-xl p-4 border border-gray-200 text-sm text-gray-500 flex items-center justify-center min-h-[100px] italic">
                                    Tidak ada foto KTP
                                </div>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Catatan Tambahan</p>
                                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 text-sm text-gray-600 min-h-[100px]">
                                    {{ selectedCustomer.notes || 'Tidak ada catatan.' }}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sticky bottom-0 bg-white/90 backdrop-blur-xl border-t border-gray-200 px-6 py-4 flex justify-end">
                        <button @click="closeViewModal" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-900 rounded-xl text-sm font-medium transition-colors">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Konfirmasi Hapus Satu Data -->
        <Teleport to="body">
            <div v-if="showDeleteModal && selectedCustomerToDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm cursor-pointer" @click="showDeleteModal = false"></div>
                <div class="relative bg-white border border-gray-200 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center animate-fade-in-up">
                    <div class="w-16 h-16 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Hapus Data Booking?</h3>
                    <p class="text-sm text-gray-500 mb-6">
                        Apakah Anda yakin ingin menghapus data booking <b>{{ selectedCustomerToDelete.name }}</b>? Tindakan ini tidak dapat dibatalkan.
                    </p>
                    <div class="flex items-center justify-center gap-3">
                        <button @click="showDeleteModal = false" class="btn-secondary w-full">Batal</button>
                        <button @click="deleteCustomer" :disabled="isDeleting" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium text-sm rounded-xl transition-colors w-full flex justify-center items-center">
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
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                        Hapus {{ selectedIds.length }} Data Terpilih?
                    </h3>
                    <p class="text-sm text-gray-500 mb-6">
                        Data terpilih akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.
                    </p>
                    <div class="flex items-center justify-center gap-3">
                        <button @click="showDeleteAllModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-sm rounded-xl transition-colors w-full">Batal</button>
                        <button @click="deleteAllBooking" :disabled="isDeletingAll" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium text-sm rounded-xl transition-colors w-full flex justify-center items-center shadow-sm shadow-red-200">
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
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import WaModal from '@/Components/WaModal.vue';

const props = defineProps({ customers: Object, filters: Object, areas: Array, stats: Object });
const page = usePage();

// WA Modal
const showWaModal = ref(false);
const waCustomerRow = ref(null);

function sendWa(row) {
    waCustomerRow.value = row;
    showWaModal.value = true;
}

const selectedIds = ref([]);

const canDelete = computed(() => {
    try {
        if (!page || !page.props || !page.props.auth || !page.props.auth.user) return false;
        
        const user = page.props.auth.user;
        
        // 1. Check Role String (insensitive and trimmed)
        if (user.role && typeof user.role === 'string') {
            const roleStr = user.role.toLowerCase().trim();
            if (roleStr === 'admin' || roleStr === 'super admin' || roleStr.includes('admin')) {
                return true;
            }
        }
        
        // 2. Check Roles Array (Spatie)
        let roles = [];
        if (Array.isArray(user.roles)) roles = user.roles;
        else if (user.roles) roles = Object.values(user.roles);
        
        for (let r of roles) {
            if (typeof r === 'string') {
                const rStr = r.toLowerCase().trim();
                if (rStr === 'admin' || rStr === 'super admin' || rStr.includes('admin')) return true;
            }
        }
        
        // 3. Check Permissions (Spatie)
        let perms = [];
        if (Array.isArray(user.permissions)) perms = user.permissions;
        else if (user.permissions) perms = Object.values(user.permissions);
        
        return perms.includes('menu_customers_booking') || perms.includes('customers_booking_delete') || perms.includes('customers_delete');
    } catch (e) {
        console.error("Auth check error:", e);
        return false;
    }
});

const filterDate = ref(props.filters?.date || '');
const filterArea = ref(props.filters?.area || '');
const filterStatus = ref(props.filters?.status || '');

function applyFilters() {
    router.get('/customers/booking', {
        date: filterDate.value || undefined,
        area: filterArea.value || undefined,
        status: filterStatus.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterDate.value = '';
    filterArea.value = '';
    filterStatus.value = '';
    router.get('/customers/booking', {}, { preserveState: true, preserveScroll: true });
}

const columns = [
    { key: 'name', label: 'Nama Pelanggan' },
    { key: 'phone', label: 'Telepon' },
    { key: 'address', label: 'Alamat' },
    { key: 'package', label: 'Paket Langganan' },
    { key: 'sales', label: 'Sales' },
    { key: 'date', label: 'Tgl Daftar' },
    { key: 'area', label: 'Area / Wilayah' },
];

const showViewModal = ref(false);
const selectedCustomer = ref(null);

function viewCustomer(customer) {
    selectedCustomer.value = customer;
    showViewModal.value = true;
}

function closeViewModal() {
    showViewModal.value = false;
    setTimeout(() => {
        selectedCustomer.value = null;
    }, 300);
}

function openWhatsApp(row) {
    const waNumber = '6281234567890'; // Bisa disesuaikan dengan nomor pembuat jadwal (admin)
    const message = `Halo Admin, mohon jadwalkan survey untuk pelanggan berikut:\n\nNama: ${row.name}\nKode: ${row.customer_code}\nAlamat: ${row.address}\nTelepon: ${row.phone}`;
    const waUrl = `https://wa.me/${waNumber}?text=${encodeURIComponent(message)}`;
    window.open(waUrl, '_blank');
}

function requestSurvey(row) {
    if (confirm('Pindahkan pelanggan ini ke Data Survey untuk dijadwalkan?')) {
        // Kirim request ke backend untuk ubah status ke 'survey'
        router.post(`/customers/${row.id}/request-survey`, {}, {
            preserveScroll: true
        });
    }
}

// Fitur Hapus Satu Data
const showDeleteModal = ref(false);
const selectedCustomerToDelete = ref(null);
const isDeleting = ref(false);

function confirmDelete(customer) {
    selectedCustomerToDelete.value = customer;
    showDeleteModal.value = true;
}

function deleteCustomer() {
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

// Fitur Hapus Semua Data Booking
const showDeleteAllModal = ref(false);
const isDeletingAll = ref(false);

function confirmDeleteAll() {
    showDeleteAllModal.value = true;
}

function deleteAllBooking() {
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
