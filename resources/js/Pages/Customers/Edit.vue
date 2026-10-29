<template>
    <AppLayout title="Edit Pelanggan" :subtitle="customer.customer_code">
        <div class="max-w-3xl mx-auto py-8">
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden animate-fade-in-up">
                
                <!-- Header -->
                <div class="p-6 flex items-start justify-between border-b border-gray-100">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-yellow-50 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Edit Data Pelanggan</h3>
                            <p class="text-sm text-gray-500 mt-1">{{ customer.name }} - {{ customer.customer_code }}</p>
                        </div>
                    </div>
                    <Link :href="`/customers/${customer.id}`" class="text-gray-500 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </Link>
                </div>

                <!-- Error Summary -->
                <div v-if="Object.keys(form.errors).length > 0" class="p-4 mx-6 mt-6 bg-red-50 border border-red-200 rounded-lg text-red-600">
                    <p class="font-bold text-sm mb-2">Terjadi kesalahan pada data yang Anda masukkan:</p>
                    <ul class="list-disc pl-5 text-xs space-y-1">
                        <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                    </ul>
                </div>

                <!-- Form Body -->
                <form @submit.prevent="submit" class="bg-gray-50/30">
                    <div class="p-4 sm:p-6 space-y-8">
                        
                        <!-- Section 1: Data Pelanggan -->
                        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                            <h4 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2 pb-2 border-b border-gray-50">
                                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                Informasi Personal
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="md:col-span-2">
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Nama Pelanggan <span class="text-red-500">*</span></label>
                                    <input v-model="form.name" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all" placeholder="Contoh: Budi Santoso" required />
                                    <p v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</p>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">No WA <span class="text-red-500">*</span></label>
                                    <input v-model="form.phone" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all" placeholder="Contoh: 08123456789" required />
                                    <p v-if="form.errors.phone" class="text-red-400 text-xs mt-1">{{ form.errors.phone }}</p>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Email (Opsional)</label>
                                    <input v-model="form.email" type="email" :class="['w-full px-4 py-2.5 bg-gray-50 border rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none transition-all', form.errors.email ? 'border-red-400 bg-red-50/20 ring-2 ring-red-100' : 'border-gray-200 focus:ring-2 focus:ring-yellow-500']" placeholder="Contoh: budi@gmail.com" />
                                    <p v-if="form.errors.email" class="text-red-500 font-medium text-xs mt-1.5 flex items-center gap-1">
                                        <span>⚠️</span> {{ form.errors.email }}
                                    </p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Foto KTP (Opsional)</label>
                                    <div class="flex flex-col sm:flex-row items-start sm:items-center w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg gap-4">
                                        <label class="cursor-pointer bg-white border border-yellow-200 text-yellow-600 px-4 py-2 sm:py-1.5 rounded-md text-sm sm:text-xs font-semibold hover:bg-yellow-50 transition-colors w-full sm:w-auto text-center shrink-0 shadow-sm">
                                            Pilih File KTP
                                            <input type="file" @change="handleKtpUpload" accept="image/*" class="hidden" />
                                        </label>
                                        <div v-if="ktpPreviewUrl" class="flex flex-col gap-1 w-full mt-0.5">
                                            <div class="flex items-center gap-3">
                                                <img :src="ktpPreviewUrl" class="h-12 w-12 sm:h-10 sm:w-10 object-cover rounded border border-gray-200 cursor-pointer hover:opacity-80 transition-opacity" @click="showPreviewModal = true" />
                                                <span class="text-sm text-gray-500 truncate flex-1">{{ form.identity_photo && typeof form.identity_photo === 'object' ? form.identity_photo.name : 'Foto Tersimpan' }}</span>
                                            </div>
                                            <a :href="ktpPreviewUrl" download="Foto_KTP.jpg" class="text-[10px] sm:text-xs text-blue-600 hover:underline w-fit ml-0 sm:ml-1 mt-1 sm:mt-0">Download Foto KTP</a>
                                        </div>
                                        <span v-else class="text-sm text-gray-400 truncate mt-1.5 hidden sm:block">Belum ada file KTP yang dipilih</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Lokasi Pemasangan -->
                        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                            <h4 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2 pb-2 border-b border-gray-50">
                                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                Lokasi Pemasangan
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Area / Wilayah</label>
                                    <select v-if="!isNewArea" v-model="form.area" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all" @change="checkNewArea">
                                        <option value="">-- Pilih Area --</option>
                                        <option v-for="area in areas" :key="area" :value="area">{{ area }}</option>
                                        <option value="new">+ Tambah Area Baru...</option>
                                    </select>
                                    <div v-else class="flex gap-2">
                                        <input v-model="form.area" type="text" class="flex-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all" placeholder="Ketik area baru..." />
                                        <button v-if="areas && areas.length > 0" type="button" @click="cancelNewArea" class="px-3 py-2.5 bg-gray-100 border border-gray-300 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                                            Batal
                                        </button>
                                    </div>
                                    <p class="text-[11px] text-amber-800 mt-1.5 flex items-center gap-1.5 font-medium bg-amber-50/80 p-2 rounded-lg border border-amber-200/60">
                                        <span>⚡</span> <span><strong>Auto Sync:</strong> Mengubah area akan otomatis memperbarui perangkat ONT, jadwal teknisi, surat jalan material gudang, survey, tiket, & akun pelanggan.</span>
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Kecamatan</label>
                                    <input v-model="form.district" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all" placeholder="Contoh: Sukasari" />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Desa / Kelurahan</label>
                                    <input v-model="form.village" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all" placeholder="Contoh: Maju Jaya" />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">RT / RW</label>
                                    <input v-model="form.rt_rw" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all" placeholder="Contoh: 02/04" />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Detail Jalan / Nomor Rumah <span class="text-red-500">*</span></label>
                                    <textarea v-model="form.address_detail" rows="2" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all resize-y" placeholder="Jl. Melati No. 12..." required></textarea>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Titik Koordinat Lokasi (Opsional)</label>
                                    <div class="flex flex-col sm:flex-row gap-3">
                                        <div class="flex-1">
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span class="text-[10px] font-bold text-gray-400">LAT</span>
                                                </div>
                                                <input v-model="form.latitude" @input="handleCoordinatePaste('lat')" type="text" class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all font-mono" placeholder="-6.200000" />
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span class="text-[10px] font-bold text-gray-400">LNG</span>
                                                </div>
                                                <input v-model="form.longitude" @input="handleCoordinatePaste('lng')" type="text" class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all font-mono" placeholder="106.816666" />
                                            </div>
                                        </div>
                                        <button type="button" @click="getLocation" class="w-full sm:w-auto px-4 py-2.5 bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-lg text-sm font-semibold hover:bg-yellow-100 transition-colors flex justify-center items-center gap-2 shadow-sm shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            <span class="hidden sm:inline">Auto GPS</span>
                                            <span class="sm:hidden">Ambil GPS Otomatis</span>
                                        </button>
                                        <a v-if="form.latitude && form.longitude" :href="`/network-radar?lat=${form.latitude}&lng=${form.longitude}`" target="_blank" class="w-full sm:w-auto px-4 py-2.5 bg-indigo-50 border border-indigo-200 text-indigo-700 rounded-lg text-sm font-semibold hover:bg-indigo-100 transition-colors flex justify-center items-center gap-2 shadow-sm shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                            Radar ODP Terdekat
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Layanan & Biaya -->
                        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                            <h4 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2 pb-2 border-b border-gray-50">
                                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                Detail Layanan & Tagihan
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="md:col-span-2">
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Tipe Pendaftaran / Layanan <span class="text-red-500">*</span></label>
                                    <select v-model="registration_type" class="w-full px-4 py-2.5 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-900 font-bold focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all">
                                        <option value="customer">🌐 Pelanggan Internet Biasa</option>
                                        <option value="both">🌐+🎟️ Pelanggan Internet + Reseller Voucher</option>
                                        <option value="reseller">🎟️ Khusus Reseller Voucher (Tanpa Internet)</option>
                                    </select>
                                </div>
                                
                                <div v-if="registration_type === 'reseller'" class="md:col-span-2 px-4 py-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700 font-bold flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    Pendaftaran Khusus Reseller Voucher
                                </div>
                                
                                <template v-else>
                                    <!-- Pilih Paket Langganan (Ditiadakan jika Free Home) -->
                                    <div v-if="form.service_status !== 'free_home'">
                                        <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Pilih Paket Langganan <span class="text-red-500">*</span></label>
                                        <select v-model="form.package_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all" :required="form.service_status !== 'free_home'">
                                            <option value="">-- Pilih Paket Langganan --</option>
                                            <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                                {{ pkg.name }} - Rp {{ Number(pkg.price).toLocaleString('id-ID') }}/bln{{ Number(pkg.price) > 0 && ppnPercent > 0 ? ` (+ PPN ${ppnPercent}%: Rp ${Number(Math.round(Number(pkg.price) * (1 + ppnPercent / 100))).toLocaleString('id-ID')})` : '' }}
                                            </option>
                                        </select>
                                    </div>
                                    <div :class="form.service_status === 'free_home' ? 'md:col-span-2' : ''">
                                        <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Status Layanan <span class="text-red-500">*</span></label>
                                        <select 
                                            v-model="form.service_status" 
                                            :disabled="isAffiliateAccount"
                                            class="w-full px-4 py-2.5 border rounded-lg text-sm font-semibold transition-all disabled:bg-gray-100 disabled:text-gray-600 disabled:cursor-not-allowed" 
                                            :class="{
                                                'bg-emerald-50 border-emerald-300 text-emerald-800 focus:ring-emerald-500': form.service_status === 'gratis',
                                                'bg-amber-50 border-amber-300 text-amber-900 focus:ring-amber-500': form.service_status === 'free_home',
                                                'bg-gray-50 border-gray-200 text-gray-800 focus:ring-yellow-500': form.service_status === 'berbayar'
                                            }" 
                                            required
                                        >
                                            <option value="berbayar">💳 Berbayar (Dikenakan Tagihan Bulanan)</option>
                                            <option v-if="!isAffiliateAccount" value="gratis">🎁 Gratis (Bebas Tagihan / Non-Invoice)</option>
                                            <option v-if="!isAffiliateAccount" value="free_home">🏠 Free Home (Voucher Rumahan ONT - Bebas Tagihan)</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Smart Banner Pelanggan Gratis -->
                                    <div v-if="form.service_status === 'gratis' && !isAffiliateAccount" class="md:col-span-2 p-3.5 bg-gradient-to-r from-emerald-50 via-teal-50 to-green-50 border border-emerald-200 rounded-xl flex items-start gap-3 shadow-sm animate-fade-in">
                                        <div class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-sm text-sm">
                                            🎁
                                        </div>
                                        <div class="text-xs">
                                            <p class="font-bold text-emerald-900 flex items-center gap-1.5">
                                                Mode Pelanggan Gratis Aktif
                                                <span class="text-[9px] px-2 py-0.5 rounded-full bg-emerald-200/80 text-emerald-900 font-extrabold uppercase tracking-wide">Bebas Invoice Selamanya</span>
                                            </p>
                                            <p class="text-emerald-700 mt-0.5 leading-relaxed">
                                                Menyetel status ke Gratis otomatis membebaskan pelanggan dari invoice baru, mengatur tarif Rp 0, dan otomatis menghapus antrean tagihan yang belum terbayar.
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Smart Banner Pelanggan Free Home -->
                                    <div v-if="form.service_status === 'free_home' && !isAffiliateAccount" class="md:col-span-2 p-3.5 bg-gradient-to-r from-amber-50 via-orange-50 to-yellow-50 border border-amber-200 rounded-xl flex items-start gap-3 shadow-sm animate-fade-in">
                                        <div class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-sm text-sm">
                                            🏠
                                        </div>
                                        <div class="text-xs">
                                            <p class="font-bold text-amber-900 flex items-center gap-1.5">
                                                Mode Layanan Free Home Aktif (Voucher Rumahan ONT)
                                                <span class="text-[9px] px-2 py-0.5 rounded-full bg-amber-200/85 text-amber-950 font-extrabold uppercase tracking-wide">Tanpa Paket & Bebas Tagihan</span>
                                            </p>
                                            <p class="text-amber-800 mt-0.5 leading-relaxed">
                                                Pelanggan menggunakan perangkat ONT di rumah khusus untuk voucher hotspot agar koneksi stabil. <strong>Paket langganan ditiadakan, tarif bulanan Rp 0, dan bebas biaya invoice</strong>. Jika ingin beralih ke langganan bulanan berbayar, cukup ubah status ke <strong>Berbayar</strong> lalu pilih paket langganan.
                                            </p>
                                        </div>
                                    </div>
                                </template>

                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2 flex items-center justify-between">
                                        <span>Tarif Bulanan (Rp) <span class="text-red-500">*</span></span>
                                        <span v-if="form.service_status === 'gratis' && !isAffiliateAccount" class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded">Otomatis Rp 0 (Gratis)</span>
                                        <span v-else-if="form.service_status === 'free_home' && !isAffiliateAccount" class="text-[10px] text-amber-700 font-bold bg-amber-50 px-2 py-0.5 rounded">Otomatis Rp 0 (Free Home)</span>
                                        <span v-else-if="Number(form.base_amount) > 0 && form.enable_tax && ppnPercent > 0" class="text-[10px] font-bold px-2 py-0.5 rounded-md border bg-yellow-50 text-yellow-800 border-yellow-200">
                                            Total + PPN ({{ ppnPercent }}%): Rp {{ formatCurrency(totalWithPpn) }} / bln
                                        </span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <span class="text-gray-500 text-sm font-semibold">Rp</span>
                                        </div>
                                        <input v-model="form.base_amount" type="number" :disabled="(form.service_status === 'gratis' || form.service_status === 'free_home') && !isAffiliateAccount" class="w-full pl-11 pr-4 py-2.5 border rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:border-transparent transition-all font-mono font-medium disabled:bg-gray-100 disabled:text-gray-600 disabled:font-bold disabled:border-gray-200 disabled:cursor-not-allowed" :class="form.service_status === 'gratis' ? 'focus:ring-emerald-500' : (form.service_status === 'free_home' ? 'focus:ring-amber-500' : 'bg-gray-50 border-gray-200 focus:ring-yellow-500')" required />
                                    </div>

                                    <!-- Rincian Lengkap Harga Pokok + PPN -->
                                    <div v-if="Number(form.base_amount) > 0 && form.service_status === 'berbayar'" class="mt-2.5 p-3.5 rounded-xl border transition-all text-xs"
                                        :class="form.enable_tax && ppnPercent > 0 
                                            ? 'bg-gradient-to-br from-yellow-50/70 via-amber-50/40 to-slate-50 border-yellow-200/90' 
                                            : 'bg-slate-50 border-slate-200'">
                                        
                                        <div class="flex items-center justify-between font-bold text-[11px] uppercase tracking-wider text-yellow-900 pb-2 border-b border-yellow-100">
                                            <span class="flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                Rincian Tarif &amp; PPN
                                            </span>
                                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold"
                                                :class="form.enable_tax && ppnPercent > 0 
                                                    ? 'bg-yellow-100 text-yellow-800' 
                                                    : 'bg-emerald-100 text-emerald-700'">
                                                {{ form.enable_tax && ppnPercent > 0 ? `PPN ${ppnPercent}% Aktif` : 'Bebas PPN (0%)' }}
                                            </span>
                                        </div>

                                        <div class="space-y-1.5 py-2 border-b border-yellow-100/70">
                                            <div class="flex items-center justify-between text-slate-600 text-xs">
                                                <span>Harga Dasar Tarif (DPP)</span>
                                                <span class="font-mono font-semibold text-slate-800">Rp {{ formatCurrency(form.base_amount) }}</span>
                                            </div>
                                            
                                            <div v-if="form.enable_tax && ppnPercent > 0" class="flex items-center justify-between text-xs">
                                                <span class="text-slate-600 flex items-center gap-1.5">
                                                    <span>PPN ({{ ppnPercent }}%)</span>
                                                    <span class="text-[9px] bg-yellow-100 text-yellow-800 font-semibold px-1.5 py-0.2 rounded">Pajak Pertambahan Nilai</span>
                                                </span>
                                                <span class="font-mono font-bold text-yellow-700">+ Rp {{ formatCurrency(ppnAmount) }}</span>
                                            </div>
                                            <div v-else class="flex items-center justify-between text-xs text-slate-500">
                                                <span>PPN (0%)</span>
                                                <span class="font-mono font-medium text-emerald-600">Rp 0 (Non-PPN)</span>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-between pt-2">
                                            <div>
                                                <span class="font-bold text-slate-900 block text-xs">Total Tagihan Pelanggan</span>
                                                <span class="text-[10px] text-slate-500">Harga Pokok + PPN per bulan</span>
                                            </div>
                                            <div class="text-right">
                                                <span class="font-mono font-extrabold text-base" :class="form.enable_tax && ppnPercent > 0 ? 'text-yellow-700' : 'text-slate-900'">
                                                    Rp {{ formatCurrency(totalWithPpn) }}
                                                </span>
                                                <span class="text-[10px] text-slate-500 block -mt-0.5">/ bulan</span>
                                            </div>
                                        </div>

                                        <!-- Keterangan Lengkap -->
                                        <div class="mt-2.5 pt-2 border-t border-yellow-100/70 text-[11px] leading-relaxed text-slate-600">
                                            <div class="flex items-start gap-1.5">
                                                <span class="text-yellow-600 font-bold shrink-0">ℹ️</span>
                                                <p v-if="form.enable_tax && ppnPercent > 0">
                                                    <strong>Keterangan Lengkap:</strong> Tarif pokok pelanggan adalah <strong>Rp {{ formatCurrency(form.base_amount) }}</strong>. Dikenakan PPN sebesar <strong>{{ ppnPercent }}% (Rp {{ formatCurrency(ppnAmount) }})</strong>. Total tagihan invoice bulanan pelanggan adalah <strong>Rp {{ formatCurrency(totalWithPpn) }} / bulan</strong> (sudah termasuk PPN).
                                                </p>
                                                <p v-else>
                                                    <strong>Keterangan Lengkap:</strong> Pelanggan ini bebas pajak (Non-PPN). Total tagihan bulanan pada invoice pelanggan adalah tarif normal <strong>Rp {{ formatCurrency(form.base_amount) }} / bulan</strong> tanpa tambahan pajak.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Biaya Pasang Baru (Rp)</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <span class="text-gray-500 text-sm font-semibold">Rp</span>
                                        </div>
                                        <input v-model="form.installation_fee" type="number" class="w-full pl-11 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all font-mono font-medium" />
                                    </div>
                                </div>
                                
                                <div class="md:col-span-2 pt-2 border-t border-gray-100 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <label for="edit_enable_tax" class="flex items-center gap-2 cursor-pointer select-none">
                                            <input 
                                                type="checkbox" 
                                                id="edit_enable_tax" 
                                                v-model="form.enable_tax" 
                                                class="rounded border-gray-300 text-yellow-600 focus:ring-yellow-500 h-4 w-4 cursor-pointer" 
                                            />
                                            <span class="text-[11px] font-bold text-gray-700 uppercase tracking-wider">
                                                Pengaturan Pajak Pelanggan
                                            </span>
                                        </label>
                                        <span 
                                            class="text-[10px] font-bold px-2 py-0.5 rounded-full border transition-all"
                                            :class="form.enable_tax 
                                                ? 'bg-yellow-100 text-yellow-800 border-yellow-200' 
                                                : 'bg-gray-100 text-gray-500 border-gray-200'"
                                        >
                                            {{ form.enable_tax ? 'PPN Aktif (' + (form.tax_ppn || 11) + '%)' : 'Bebas PPN (Nonaktif)' }}
                                        </span>
                                    </div>
                                    <div v-if="form.enable_tax" class="max-w-xs pt-1">
                                        <label class="block text-[10px] font-bold text-gray-500 mb-1">PPN (%)</label>
                                        <div class="relative">
                                            <input v-model="form.tax_ppn" type="number" step="0.01" class="w-full pr-8 pl-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 focus:bg-white focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all font-semibold" placeholder="11" />
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
                                        </div>
                                    </div>
                                    <div v-else class="text-xs text-gray-500 flex items-center gap-1.5 bg-gray-50 p-2.5 rounded-lg border border-gray-100">
                                        <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>Pelanggan ini bebas pajak (tagihan tanpa PPN / 0%).</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Tanggal Registrasi</label>
                                    <input v-model="form.registration_date" type="date" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all" />
                                </div>
                                
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Petugas Sales / Karyawan Referral</label>
                                    <div v-if="canChangeSales">
                                        <select v-model="form.sales_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all">
                                            <option value="">-- Tanpa Sales / Referral --</option>
                                            <option v-for="person in sales" :key="person.id" :value="person.id">
                                                {{ person.display_name || person.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div v-else class="relative">
                                        <input 
                                            type="text" 
                                            :value="currentSalesName" 
                                            readonly 
                                            disabled 
                                            class="w-full px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-lg text-sm font-semibold text-gray-700 cursor-not-allowed select-none" 
                                            title="Hanya Super Admin yang dapat mengubah Petugas Sales/Referral"
                                        />
                                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs text-gray-400 font-medium">
                                            🔒 Terkunci
                                        </span>
                                    </div>
                                </div>

                                <div class="md:col-span-2 pt-2 border-t border-gray-100">
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Siklus Penagihan Tagihan (Billing Cycle)</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <label :class="form.billing_type === 'prabayar' ? 'border-yellow-500 bg-yellow-50/50 text-yellow-900 ring-2 ring-yellow-200' : 'border-gray-200 hover:border-gray-300 text-gray-700'" class="flex items-center gap-2 p-3 border rounded-xl cursor-pointer transition-all">
                                            <input type="radio" v-model="form.billing_type" value="prabayar" class="text-yellow-600 focus:ring-yellow-500">
                                            <div>
                                                <span class="text-xs font-bold block">Prabayar (Prepaid)</span>
                                                <span class="text-[10px] text-gray-500">Bayar di awal pemakaian</span>
                                            </div>
                                        </label>
                                        <label :class="form.billing_type === 'pascabayar' ? 'border-yellow-500 bg-yellow-50/50 text-yellow-900 ring-2 ring-yellow-200' : 'border-gray-200 hover:border-gray-300 text-gray-700'" class="flex items-center gap-2 p-3 border rounded-xl cursor-pointer transition-all">
                                            <input type="radio" v-model="form.billing_type" value="pascabayar" class="text-yellow-600 focus:ring-yellow-500">
                                            <div>
                                                <span class="text-xs font-bold block">Pascabayar (Postpaid)</span>
                                                <span class="text-[10px] text-gray-500">Pakai dulu, bayar di akhir</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Siklus Tanggal Tagihan (Billing Cycle) -->
                                <div class="col-span-full pt-1">
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">
                                        Siklus Tanggal Tagihan
                                    </label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <select v-model="form.billing_cycle" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-xs font-semibold text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                                <option value="default">Ikuti Default Sistem ({{ page.props.billingSettings?.billing_cycle === 'anniversary' ? 'Sesuai Tgl Aktivasi' : 'Kalender Serentak' }})</option>
                                                <option value="anniversary">Sesuai Tanggal Aktivasi (Anniversary)</option>
                                                <option value="calendar">Siklus Kalender (Tanggal Serentak)</option>
                                                <option value="custom_date">Pilih Tanggal Khusus Sendiri</option>
                                            </select>
                                        </div>
                                        <div v-if="form.billing_cycle === 'custom_date'" class="flex items-center gap-2">
                                            <span class="text-xs text-gray-600 font-medium">Tanggal Tagihan Rutin:</span>
                                            <input type="number" v-model="form.billing_day" min="1" max="28" class="w-20 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-bold text-yellow-800" placeholder="1-28" />
                                            <span class="text-xs text-gray-500">tiap bulan</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 4: Status & Catatan Khusus -->
                        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                            <h4 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2 pb-2 border-b border-gray-50">
                                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                Status & Catatan
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Status <span class="text-red-500">*</span></label>
                                    <select v-model="form.status" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 font-bold focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all" required>
                                        <option value="booking">Booking</option>
                                        <option value="survey">Survey</option>
                                        <option value="installing">Proses Pasang</option>
                                        <option value="active">Aktif</option>
                                        <option value="suspended">Isolir</option>
                                        <option value="terminated">Terminated</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Catatan Khusus</label>
                                    <textarea v-model="form.notes" rows="1" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all resize-y" placeholder="Catatan tambahan..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- ONT Info (read-only) -->
                        <div v-if="customer.ont" class="bg-gray-50 rounded-xl p-5 border border-gray-200 shadow-inner">
                            <h4 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Perangkat Terhubung (ONT)
                            </h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <span class="text-xs text-gray-500 block mb-1">ONT S/N</span>
                                    <span class="text-cyan-600 font-mono font-bold bg-cyan-50 px-2 py-1 rounded">{{ customer.ont.serial_number }}</span>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 block mb-1">ODP</span>
                                    <span class="text-gray-800 font-medium">{{ customer.ont.odp?.name || '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 block mb-1">Port</span>
                                    <span class="text-gray-800 font-medium">#{{ customer.ont.port_number }}</span>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 block mb-1">Rx Power</span>
                                    <span class="text-gray-800 font-medium">{{ customer.ont.rx_power ? `${customer.ont.rx_power} dBm` : '-' }}</span>
                                </div>
                            </div>
                        </div>

                    </div>
                    
                    <!-- Footer Actions -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col-reverse sm:flex-row justify-end gap-3 rounded-b-2xl">
                        <Link :href="`/customers/${customer.id}`" class="w-full sm:w-auto text-center px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-bold text-gray-600 bg-white hover:bg-gray-50 transition-colors shadow-sm">
                            Batal
                        </Link>
                        <button type="submit" :disabled="form.processing" class="w-full sm:w-auto justify-center px-6 py-2.5 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg text-sm font-bold transition-all flex items-center gap-2 shadow-sm hover:shadow focus:ring-4 focus:ring-yellow-100">
                            <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                        </button>
                    </div>
                </form>

            </div>
        </div>
        
        <!-- Image Preview Modal -->
        <Teleport to="body">
            <div v-if="showPreviewModal && ktpPreviewUrl" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/90 backdrop-blur-sm cursor-pointer" @click="showPreviewModal = false"></div>
                <div class="relative max-w-4xl w-full max-h-[90vh] overflow-y-auto h-full flex flex-col items-center justify-center animate-fade-in-up">
                    <button type="button" @click="showPreviewModal = false" class="absolute top-4 right-4 text-white hover:text-red-500 bg-white/20 hover:bg-white/30 p-2 rounded-full z-10 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                    <img :src="ktpPreviewUrl" class="max-h-[85vh] max-w-full object-contain rounded-lg shadow-2xl" />
                    <p class="mt-4 text-white font-medium text-lg">Foto KTP</p>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { watch, ref, onMounted, computed } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    customer: Object,
    packages: Array,
    sales: Array,
    areas: Array,
    isAffiliate: {
        type: Boolean,
        default: false,
    },
    taxSettings: {
        type: Object,
        default: () => ({}),
    },
});

const isNewArea = ref(false);
const page = usePage();
const isAdmin = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return false;
    const r = String(user.role || '').toLowerCase().trim();
    if (['admin', 'super admin', 'superadmin', 'super-admin'].includes(r) || user.id === 1) return true;
    let roles = [];
    if (Array.isArray(user.roles)) roles = user.roles.map(x => String(typeof x === 'string' ? x : x.name).toLowerCase().trim());
    else if (user.roles) roles = Object.values(user.roles).map(x => String(typeof x === 'string' ? x : x.name).toLowerCase().trim());
    return roles.includes('admin') || roles.includes('super admin') || roles.includes('superadmin') || roles.includes('super-admin');
});

// Hanya Super Admin yang dapat mengubah Petugas Sales/Referral
const canChangeSales = computed(() => {
    return isAdmin.value;
});

const currentSalesName = computed(() => {
    const sId = form.sales_id || props.customer?.sales_id;
    if (!sId) return '-- Tanpa Sales / Referral --';
    const found = props.sales?.find(s => s.id === sId);
    return found ? (found.display_name || found.name) : (props.customer?.sales?.name || `Sales #${sId}`);
});

const isAffiliateAccount = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return false;
    const r = String(user.role || '').toLowerCase();
    
    // Internal staff are NEVER restricted affiliate accounts
    if (['admin', 'super admin', 'superadmin', 'super-admin', 'admin cs', 'cs', 'manager', 'noc', 'kepala teknisi', 'teknisi', 'maintenance', 'dispatcher', 'finance', 'hrd'].includes(r)) {
        return false;
    }
    
    if (props.isAffiliate && (r === 'affiliate' || r === 'reseller')) return true;
    if (r === 'affiliate' || r === 'reseller') return true;
    return false;
});

watch(isAffiliateAccount, (val) => {
    if (val && form.service_status === 'gratis') {
        form.service_status = 'berbayar';
    }
}, { immediate: true });

const ktpPreviewUrl = ref(props.customer.identity_photo ? `/storage/${props.customer.identity_photo}` : null);
const showPreviewModal = ref(false);

function handleKtpUpload(e) {
    const file = e.target.files[0];
    form.identity_photo = file;
    if (file) {
        if (ktpPreviewUrl.value && !ktpPreviewUrl.value.startsWith('/storage/')) {
            URL.revokeObjectURL(ktpPreviewUrl.value);
        }
        ktpPreviewUrl.value = URL.createObjectURL(file);
    } else {
        ktpPreviewUrl.value = props.customer.identity_photo ? `/storage/${props.customer.identity_photo}` : null;
    }
}

// Parse Address
let initialAddressDetail = props.customer.address || '';
let initialRtRw = '';
let initialVillage = '';
let initialDistrict = '';

if (initialAddressDetail) {
    const match = initialAddressDetail.match(/^(.*?)(?:,\s*RT\/RW:\s*(.*?))?(?:,\s*Kel\.\s*(.*?))?(?:,\s*Kec\.\s*(.*?))?$/);
    if (match) {
        initialAddressDetail = match[1] || '';
        initialRtRw = match[2] || '';
        initialVillage = match[3] || '';
        initialDistrict = match[4] || '';
    }
}

const initialLatitude = props.customer.latitude || '';
const initialLongitude = props.customer.longitude || '';

onMounted(() => {
    if (!props.areas || props.areas.length === 0 || (props.customer.area && !props.areas.includes(props.customer.area))) {
        if (props.customer.area && !props.areas?.includes(props.customer.area)) {
            isNewArea.value = true;
        } else if (!props.customer.area && (!props.areas || props.areas.length === 0)) {
            isNewArea.value = true;
        }
    }
});

function checkNewArea(e) {
    if (e.target.value === 'new') {
        isNewArea.value = true;
        form.area = '';
    }
}

function cancelNewArea() {
    isNewArea.value = false;
    form.area = '';
}

const form = useForm({
    name: props.customer.name || '',
    phone: props.customer.phone || '',
    email: props.customer.email || '',
    area: props.customer.area || '',
    package_id: props.customer.package_id || '',
    district: initialDistrict,
    village: initialVillage,
    rt_rw: initialRtRw,
    address_detail: initialAddressDetail,
    identity_photo: null,
    latitude: initialLatitude,
    longitude: initialLongitude,
    base_amount: props.customer.base_amount || 0,
    registration_date: props.customer.registration_date || new Date().toISOString().split('T')[0],
    installation_fee: props.customer.installation_fee || 0,
    sales_id: props.customer.sales_id || '',
    status: props.customer.status || 'booking',
    notes: props.customer.notes || '',
    is_reseller: props.customer.is_reseller == 1 ? true : false,
    service_status: props.customer.service_status || 'berbayar',
    enable_tax: (props.customer.tax_ppn !== null && props.customer.tax_ppn !== undefined)
        ? (Number(props.customer.tax_ppn) > 0 || Number(props.customer.tax_bhp) > 0 || Number(props.customer.tax_uso) > 0)
        : false,
    tax_ppn: props.customer.tax_ppn,
    tax_bhp: props.customer.tax_bhp,
    tax_uso: props.customer.tax_uso,
    billing_type: props.customer.billing_type || page.props.billingSettings?.billing_type || 'prabayar',
    billing_cycle: props.customer.billing_cycle || 'default',
    billing_day: props.customer.billing_day || null,
});

function formatCurrency(value) {
    return Number(value || 0).toLocaleString('id-ID');
}

const ppnPercent = computed(() => {
    if (!form.enable_tax) return 0;
    if (form.tax_ppn !== undefined && form.tax_ppn !== null && form.tax_ppn !== '') {
        return Number(form.tax_ppn);
    }
    if (props.taxSettings?.tax_ppn !== undefined && props.taxSettings?.tax_ppn !== null) {
        return Number(props.taxSettings.tax_ppn);
    }
    const globalPpn = page.props.billingSettings?.tax_ppn ?? page.props.taxSettings?.tax_ppn;
    return Number(globalPpn !== undefined && globalPpn !== null ? globalPpn : 11);
});

const ppnAmount = computed(() => {
    if (form.service_status === 'gratis' || form.service_status === 'free_home') return 0;
    const base = Number(form.base_amount || 0);
    if (base <= 0 || !form.enable_tax || ppnPercent.value <= 0) return 0;
    return Math.round(base * (ppnPercent.value / 100));
});

const totalWithPpn = computed(() => {
    if (form.service_status === 'gratis' || form.service_status === 'free_home') return 0;
    const base = Number(form.base_amount || 0);
    return base + ppnAmount.value;
});

const registration_type = ref('customer');
if (form.is_reseller && !form.package_id) {
    registration_type.value = 'reseller';
} else if (form.is_reseller && form.package_id) {
    registration_type.value = 'both';
} else {
    registration_type.value = 'customer';
}

watch(registration_type, (type) => {
    if (type === 'reseller') {
        form.is_reseller = true;
        form.package_id = '';
        form.base_amount = 0;
    } else if (type === 'both') {
        form.is_reseller = true;
    } else {
        form.is_reseller = false;
    }
});

watch(() => form.service_status, (newStatus) => {
    if (newStatus === 'gratis') {
        form.base_amount = 0;
    } else if (newStatus === 'free_home') {
        form.package_id = '';
        form.base_amount = 0;
    } else {
        const pkg = props.packages.find(p => p.id === form.package_id);
        if (pkg) {
            form.base_amount = pkg.price;
        } else if (Number(form.base_amount) === 0) {
            form.base_amount = props.customer.base_amount || 150000;
        }
    }
});

watch(() => form.package_id, (newId) => {
    if (registration_type.value === 'reseller') return;
    if (form.service_status === 'gratis' || form.service_status === 'free_home') {
        form.base_amount = 0;
        return;
    }
    const pkg = props.packages.find(p => p.id === newId);
    if (pkg) {
        form.base_amount = pkg.price;
    }
});

function handleCoordinatePaste(field) {
    const raw = String(field === 'lat' ? form.latitude : form.longitude).trim();
    if (!raw) return;

    // Check Google Maps URL
    const urlMatch = raw.match(/@(-?\d+(?:[.,]\d+)?),(-?\d+(?:[.,]\d+)?)/) ||
                     raw.match(/[?&]q=(-?\d+(?:[.,]\d+)?),(-?\d+(?:[.,]\d+)?)/);
    if (urlMatch) {
        let lat = parseFloat(urlMatch[1].replace(',', '.'));
        let lng = parseFloat(urlMatch[2].replace(',', '.'));
        if (Math.abs(lat) > 90 || (lat > 20 && lng <= 20)) { const t = lat; lat = lng; lng = t; }
        if (lat > 5.0 && lat < 12.0 && lng >= 95.0 && lng <= 145.0) lat = -Math.abs(lat);
        if (lng < 0 && Math.abs(lng) >= 95.0 && Math.abs(lng) <= 145.0) lng = Math.abs(lng);
        form.latitude = lat.toFixed(6);
        form.longitude = lng.toFixed(6);
        return;
    }

    // Check paired coordinates
    const pairMatch = raw.match(/^(-?\d+(?:[.,]\d+)?)\s*[,/ ;|]\s*(-?\d+(?:[.,]\d+)?)$/);
    if (pairMatch) {
        let lat = parseFloat(pairMatch[1].replace(',', '.'));
        let lng = parseFloat(pairMatch[2].replace(',', '.'));
        if (!isNaN(lat) && !isNaN(lng)) {
            if (Math.abs(lat) > 90 || (lat > 20 && lng <= 20)) { const t = lat; lat = lng; lng = t; }
            if (lat > 5.0 && lat < 12.0 && lng >= 95.0 && lng <= 145.0) lat = -Math.abs(lat);
            if (lng < 0 && Math.abs(lng) >= 95.0 && Math.abs(lng) <= 145.0) lng = Math.abs(lng);
            form.latitude = lat.toFixed(6);
            form.longitude = lng.toFixed(6);
            return;
        }
    }

    // Single number comma normalization
    if (raw.includes(',')) {
        if (field === 'lat') form.latitude = raw.replace(',', '.');
        else form.longitude = raw.replace(',', '.');
    }
}

function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                form.latitude = position.coords.latitude.toFixed(6);
                form.longitude = position.coords.longitude.toFixed(6);
            },
            (error) => {
                alert('Gagal mendapatkan lokasi: ' + error.message);
            }
        );
    } else {
        alert('Geolocation tidak didukung oleh browser Anda.');
    }
}

function submit() {
    const addressParts = [
        form.address_detail,
        form.rt_rw ? `RT/RW: ${form.rt_rw}` : '',
        form.village ? `Kel. ${form.village}` : '',
        form.district ? `Kec. ${form.district}` : ''
    ].filter(Boolean).join(', ');
    
    form.transform((data) => ({
        ...data,
        address: addressParts || '-',
    })).post(`/customers/${props.customer.id}/update`, {
        onError: (errors) => {
            if (errors.email) {
                Swal.fire({
                    icon: 'error',
                    title: 'Email Sudah Terdaftar',
                    text: errors.email,
                });
            }
        }
    });
}
</script>
