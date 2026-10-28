<template>
    <component :is="affiliatePortal ? AffiliateLayout : AppLayout" title="Tambah Pelanggan Baru" subtitle="Pendaftaran pelanggan ISP baru (Booking)">
        <div class="max-w-3xl mx-auto py-8">
            <!-- Modal Card Replica -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden animate-fade-in-up">
                
                <!-- Header -->
                <div class="p-4 sm:p-6 flex items-start justify-between border-b border-gray-100">
                    <div class="flex items-start gap-3 sm:gap-4">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg sm:text-xl font-bold text-gray-800">Tambah Booking Baru</h3>
                            <p class="text-xs sm:text-sm text-gray-500 mt-1">Masukkan data master pelanggan baru ke dalam sistem.</p>
                        </div>
                    </div>
                    <Link :href="affiliatePortal ? '/affiliate/dashboard' : '/customers/booking'" class="p-1 sm:p-2 text-gray-500 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </Link>
                </div>

                <!-- Error Summary -->
                <div v-if="Object.keys(form.errors).length > 0" class="p-4 mx-4 sm:mx-6 mt-4 sm:mt-6 bg-red-50 border border-red-200 rounded-lg text-red-600">
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
                                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                Informasi Personal
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="md:col-span-2">
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Nama Pelanggan <span class="text-red-500">*</span></label>
                                    <input v-model="form.name" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="Contoh: Budi Santoso" required />
                                    <p v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</p>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">No WA</label>
                                    <input v-model="form.phone" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="Contoh: 08123456789" />
                                    <p v-if="form.errors.phone" class="text-red-400 text-xs mt-1">{{ form.errors.phone }}</p>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Email (Opsional)</label>
                                    <input v-model="form.email" type="email" :class="['w-full px-4 py-2.5 bg-gray-50 border rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none transition-all', form.errors.email ? 'border-red-400 bg-red-50/20 ring-2 ring-red-100' : 'border-gray-200 focus:ring-2 focus:ring-indigo-500']" placeholder="Contoh: budi@gmail.com" />
                                    <p v-if="form.errors.email" class="text-red-500 font-medium text-xs mt-1.5 flex items-center gap-1">
                                        <span>⚠️</span> {{ form.errors.email }}
                                    </p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Foto KTP (Opsional)</label>
                                    <div class="flex flex-col sm:flex-row items-start sm:items-center w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg gap-4">
                                        <label class="cursor-pointer bg-white border border-indigo-200 text-indigo-600 px-4 py-2 sm:py-1.5 rounded-md text-sm sm:text-xs font-semibold hover:bg-indigo-50 transition-colors w-full sm:w-auto text-center shrink-0 shadow-sm">
                                            Pilih File KTP
                                            <input type="file" @change="handleKtpUpload" accept="image/*" class="hidden" />
                                        </label>
                                        <div v-if="ktpPreviewUrl" class="flex flex-col gap-1 w-full mt-0.5">
                                            <div class="flex items-center gap-3">
                                                <img :src="ktpPreviewUrl" class="h-12 w-12 sm:h-10 sm:w-10 object-cover rounded border border-gray-200 cursor-pointer hover:opacity-80 transition-opacity" @click="showPreviewModal = true" />
                                                <span class="text-sm text-gray-500 truncate flex-1">{{ form.identity_photo ? form.identity_photo.name : '' }}</span>
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
                                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                Lokasi Pemasangan
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Area / Wilayah <span class="text-red-500">*</span></label>
                                    <select v-if="!isNewArea" v-model="form.area" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" @change="checkNewArea" required>
                                        <option value="">-- Pilih Area --</option>
                                        <option v-for="area in areas" :key="area" :value="area">{{ area }}</option>
                                        <option v-if="!affiliatePortal" value="new">+ Tambah Area Baru...</option>
                                    </select>
                                    <div v-else class="flex gap-2">
                                        <input v-model="form.area" type="text" class="flex-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="Ketik area baru..." required />
                                        <button v-if="areas.length > 0" type="button" @click="cancelNewArea" class="px-3 py-2.5 bg-gray-100 border border-gray-300 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                                            Batal
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Kecamatan</label>
                                    <input v-model="form.district" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="Contoh: Sukasari" />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2 flex items-center justify-between">
                                        <span>Desa / Kelurahan</span>
                                        <span v-if="suggestedVillages.length > 0" class="text-[9px] font-semibold text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded border border-indigo-200">
                                            {{ suggestedVillages.length }} Sesuai Area
                                        </span>
                                    </label>
                                    <input 
                                        v-model="form.village" 
                                        list="village-suggestions" 
                                        type="text" 
                                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" 
                                        placeholder="Pilih atau ketik desa..." 
                                    />
                                    <datalist id="village-suggestions">
                                        <option v-for="v in suggestedVillages" :key="v" :value="v" />
                                    </datalist>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">RT / RW</label>
                                    <input v-model="form.rt_rw" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="Contoh: 02/04" />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Detail Jalan / Nomor Rumah</label>
                                    <textarea v-model="form.address_detail" rows="2" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all resize-y" placeholder="Jl. Melati No. 12..."></textarea>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Titik Koordinat Lokasi (Opsional)</label>
                                    <div class="flex flex-col sm:flex-row gap-3">
                                        <div class="flex-1">
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span class="text-[10px] font-bold text-gray-400">LAT</span>
                                                </div>
                                                <input v-model="form.latitude" @input="handleCoordinatePaste('lat')" type="text" class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all font-mono" placeholder="-6.200000" />
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span class="text-[10px] font-bold text-gray-400">LNG</span>
                                                </div>
                                                <input v-model="form.longitude" @input="handleCoordinatePaste('lng')" type="text" class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all font-mono" placeholder="106.816666" />
                                            </div>
                                        </div>
                                        <button type="button" @click="getLocation" class="w-full sm:w-auto px-4 py-2.5 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg text-sm font-semibold hover:bg-blue-100 transition-colors flex justify-center items-center gap-2 shadow-sm shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            <span class="hidden sm:inline">Auto GPS</span>
                                            <span class="sm:hidden">Ambil GPS Otomatis</span>
                                        </button>
                                        <a v-if="!affiliatePortal && form.latitude && form.longitude" :href="`/network-radar?lat=${form.latitude}&lng=${form.longitude}`" target="_blank" class="w-full sm:w-auto px-4 py-2.5 bg-indigo-50 border border-indigo-200 text-indigo-700 rounded-lg text-sm font-semibold hover:bg-indigo-100 transition-colors flex justify-center items-center gap-2 shadow-sm shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                            <span class="hidden sm:inline">Radar ODP</span>
                                            <span class="sm:hidden">Cek ODP Terdekat</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Layanan & Biaya -->
                        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                            <h4 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2 pb-2 border-b border-gray-50">
                                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                Detail Layanan & Tagihan
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="md:col-span-2">
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Tipe Pendaftaran / Layanan <span class="text-red-500">*</span></label>
                                    <select v-model="registration_type" class="w-full px-4 py-2.5 bg-indigo-50 border border-indigo-200 rounded-lg text-sm text-indigo-900 font-bold focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                                        <option value="customer">🌐 Pelanggan Internet Biasa</option>
                                        <option v-if="!affiliatePortal" value="both">🌐+🎟️ Pelanggan Internet + Reseller Voucher</option>
                                        <option v-if="!affiliatePortal" value="reseller">🎟️ Khusus Reseller Voucher (Tanpa Internet)</option>
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
                                        <select v-model="form.package_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" :required="form.service_status !== 'free_home'">
                                            <option value="">-- Pilih Paket Langganan --</option>
                                            <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                                {{ pkg.name }} - Rp {{ formatCurrency(packageMonthlyTariff(pkg)) }}/bulan
                                            </option>
                                        </select>
                                        <p class="mt-1.5 text-[10px] text-slate-500">Hanya paket aktif yang disediakan di Master Data yang dapat dipilih.</p>
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
                                                'bg-gray-50 border-gray-200 text-gray-800 focus:ring-indigo-500': form.service_status === 'berbayar'
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
                                                <span class="text-[9px] px-2 py-0.5 rounded-full bg-emerald-200/80 text-emerald-900 font-extrabold uppercase tracking-wide">Otomatis Bebas Tagihan</span>
                                            </p>
                                            <p class="text-emerald-700 mt-0.5 leading-relaxed">
                                                Pelanggan ini <strong>tidak akan pernah diterbitkan invoice</strong> (baik aktivasi maupun tagihan bulanan). Tarif diatur otomatis Rp 0 dan terdata di menu khusus <strong>Pelanggan Gratis</strong>.
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
                                                Pelanggan dipasang perangkat ONT di rumah khusus untuk voucher hotspot agar sinyal stabil. <strong>Paket langganan ditiadakan, tarif bulanan Rp 0, dan bebas biaya invoice</strong>. Tetap melewati alur PSB resmi (Survey, Tarik Dropcore, Pasang ONT, Aktivasi).
                                            </p>
                                        </div>
                                    </div>
                                </template>

                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2 flex items-center justify-between">
                                        <span>Tarif Bulanan (Rp) <span class="text-red-500">*</span></span>
                                        <span v-if="form.service_status === 'gratis' && !isAffiliateAccount" class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded">Otomatis Rp 0 (Gratis)</span>
                                        <span v-else-if="form.service_status === 'free_home' && !isAffiliateAccount" class="text-[10px] text-amber-700 font-bold bg-amber-50 px-2 py-0.5 rounded">Otomatis Rp 0 (Free Home)</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <span class="text-gray-500 text-sm font-semibold">Rp</span>
                                        </div>
                                        <input v-model="form.base_amount" type="number" readonly aria-readonly="true" class="w-full pl-11 pr-4 py-2.5 border rounded-lg text-sm text-gray-600 bg-gray-100 border-gray-200 font-mono font-bold cursor-not-allowed" required />
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider">
                                            Biaya Pasang Baru (PSB)
                                        </label>
                                        <span v-if="form.package_id && form.area" class="text-[10px] font-bold px-2 py-0.5 rounded-md border"
                                            :class="standardAreaInstallationFee === 0 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-indigo-50 text-indigo-700 border-indigo-200'">
                                            {{ standardAreaInstallationFee === 0 ? '🎁 Promo Rp 0' : `Standar ${form.area}: Rp ${formatCurrency(standardAreaInstallationFee)}` }}
                                        </span>
                                    </div>

                                    <!-- Opsi Pilihan HANYA untuk Portal Sales jika diperbolehkan pada paket -->
                                    <div v-if="isSalesPortal && availableInstallationOptions.length > 1 && form.service_status === 'berbayar'" class="space-y-1.5 mb-2">
                                        <span class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider block">Pilih Opsi Pasang Baru (Portal Sales):</span>
                                        <div class="grid grid-cols-2 gap-2">
                                            <button 
                                                v-for="opt in availableInstallationOptions" 
                                                :key="opt.value"
                                                type="button"
                                                @click="selectInstallationFee(opt.value)"
                                                class="p-2.5 rounded-xl border text-left transition-all cursor-pointer flex flex-col justify-between"
                                                :class="Number(form.installation_fee) === Number(opt.value) 
                                                    ? 'border-indigo-600 bg-indigo-50/80 ring-2 ring-indigo-500/30 shadow-xs' 
                                                    : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'"
                                            >
                                                <div class="flex items-center justify-between gap-1 mb-1">
                                                    <span class="text-[10px] font-bold uppercase tracking-wider" :class="opt.isPromo ? 'text-emerald-700' : 'text-indigo-700'">
                                                        {{ opt.tag }}
                                                    </span>
                                                    <span v-if="Number(form.installation_fee) === Number(opt.value)" class="w-2 h-2 rounded-full bg-indigo-600"></span>
                                                </div>
                                                <div class="text-sm font-black" :class="Number(form.installation_fee) === Number(opt.value) ? 'text-indigo-950' : 'text-gray-900'">
                                                    {{ opt.label }}
                                                </div>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Input Nominal: Readonly & Terkunci Sesuai Master Paket Internet -->
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <span class="text-gray-500 text-sm font-semibold">Rp</span>
                                        </div>
                                        <input 
                                            :value="form.installation_fee" 
                                            type="number" 
                                            readonly 
                                            aria-readonly="true"
                                            class="w-full pl-11 pr-4 py-2.5 border rounded-lg text-sm text-gray-700 bg-gray-100 border-gray-200 font-mono font-bold cursor-not-allowed" 
                                            required 
                                        />
                                    </div>

                                    <!-- Keterangan Otomatis & Cerdas -->
                                    <div class="mt-1.5 text-xs">
                                        <div v-if="form.service_status === 'gratis' || form.service_status === 'free_home'" class="flex items-center gap-1.5 text-emerald-700 font-semibold text-[11px]">
                                            <span>🎁</span>
                                            <span>Bebas biaya pasang baru untuk status layanan <b>{{ form.service_status === 'free_home' ? 'Free Home' : 'Gratis' }}</b>.</span>
                                        </div>
                                        <div v-else-if="form.package_id" class="flex flex-col gap-0.5">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border"
                                                    :class="standardAreaInstallationFee === 0 ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-indigo-50 text-indigo-800 border-indigo-200'">
                                                    <span>{{ standardAreaInstallationFee === 0 ? '🎁 Bebas Biaya (Rp 0)' : `Biaya Pasang: Rp ${formatCurrency(standardAreaInstallationFee)}` }}</span>
                                                </span>
                                                <span class="text-[11px] text-gray-600 font-medium">
                                                    Ditentukan otomatis dari Paket <b>{{ selectedPackage?.name }}</b>{{ form.area ? ` untuk Wilayah ${form.area}` : '' }}.
                                                </span>
                                            </div>
                                            <span class="text-[10px] text-gray-400">
                                                * Ditetapkan dan dikelola pada Master Data Paket Internet.
                                            </span>
                                        </div>
                                        <div v-else class="text-[11px] text-gray-400">
                                            Pilih Paket Langganan untuk menentukan Biaya Pasang Baru secara otomatis.
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Tanggal Registrasi</label>
                                    <input v-model="form.registration_date" type="date" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" />
                                </div>
                                
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Petugas Sales / Karyawan Referral</label>
                                    <div v-if="canChangeSales">
                                        <select v-model="form.sales_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                                            <option value="">-- Tanpa Sales / Referral --</option>
                                            <option v-for="person in sales" :key="person.id" :value="person.id">
                                                {{ person.display_name || person.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div v-else>
                                        <input 
                                            type="text" 
                                            :value="currentSalesName" 
                                            readonly 
                                            disabled 
                                            class="w-full px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-lg text-sm font-semibold text-gray-700 cursor-not-allowed select-none" 
                                            title="Terkunci atas nama akun Anda sendiri"
                                        />
                                        <p class="mt-1.5 flex items-center gap-1.5 text-[10px] font-medium text-gray-500">
                                            <span aria-hidden="true">🔒</span>
                                            <span>Petugas dikunci ke akun Anda.</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    
                    <!-- Footer Actions -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col-reverse sm:flex-row justify-end gap-3 rounded-b-2xl">
                        <Link :href="affiliatePortal ? '/affiliate/dashboard' : '/customers/booking'" class="w-full sm:w-auto text-center px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-bold text-gray-600 bg-white hover:bg-gray-50 transition-colors shadow-sm">
                            Batal
                        </Link>
                        <button type="submit" :disabled="form.processing" class="w-full sm:w-auto justify-center px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold transition-all flex items-center gap-2 shadow-sm hover:shadow focus:ring-4 focus:ring-indigo-100">
                            <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Pelanggan' }}
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
    </component>
</template>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AffiliateLayout from '@/Layouts/AffiliateLayout.vue';
import { watch, ref, onMounted, computed } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    packages: Array,
    sales: Array,
    areas: Array,
    affiliatePortal: Boolean,
    area_villages: {
        type: Object,
        default: () => ({}),
    },
    isAffiliate: {
        type: Boolean,
        default: false,
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

// Hanya Super Admin yang dapat memilih / mengubah Petugas Sales/Referral
const canChangeSales = computed(() => {
    if (props.affiliatePortal) return false;
    return isAdmin.value;
});

const currentSalesName = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return '-';
    const sId = form.sales_id || user.id;
    const found = props.sales?.find(s => s.id === sId);
    return found ? (found.display_name || found.name) : user.name;
});

const isAffiliateAccount = computed(() => {
    if (props.affiliatePortal) return true;
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

const isSalesPortal = computed(() => Boolean(props.affiliatePortal));

const registration_type = ref('customer');

// Rekomendasi desa yang pernah di-input di area yang dipilih
const suggestedVillages = computed(() => {
    if (form.area && props.area_villages && props.area_villages[form.area]) {
        return props.area_villages[form.area];
    }
    return [];
});

onMounted(() => {
    if (!props.affiliatePortal && (!props.areas || props.areas.length === 0)) {
        isNewArea.value = true;
    }
    
    // Penentuan Petugas Sales / Referral:
    // Selain Super Admin: Terkunci otomatis ke akun sendiri
    // Super Admin: Default ke kosong (-- Tanpa Sales / Referral --) agar tidak otomatis membebani Super Admin
    const user = page.props.auth?.user;
    if (!isAdmin.value && user) {
        form.sales_id = user.id;
    } else if (isAdmin.value) {
        form.sales_id = '';
    }

    // Auto default ke gratis atau free_home jika dibuka dari menu terkait (?service_status=...)
    try {
        const urlParams = new URLSearchParams(window.location.search);
        const reqStatus = urlParams.get('service_status') || urlParams.get('status');
        if (!isAffiliateAccount.value && reqStatus === 'gratis') {
            form.service_status = 'gratis';
            form.base_amount = 0;
        } else if (!isAffiliateAccount.value && (reqStatus === 'free_home' || reqStatus === 'free-home')) {
            form.service_status = 'free_home';
            form.package_id = '';
            form.base_amount = 0;
        } else if (isAffiliateAccount.value) {
            form.service_status = 'berbayar';
        }
    } catch (e) {}
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

const ktpPreviewUrl = ref(null);
const showPreviewModal = ref(false);

function handleKtpUpload(e) {
    const file = e.target.files[0];
    form.identity_photo = file;
    if (file) {
        if (ktpPreviewUrl.value) URL.revokeObjectURL(ktpPreviewUrl.value);
        ktpPreviewUrl.value = URL.createObjectURL(file);
    } else {
        ktpPreviewUrl.value = null;
    }
}

const form = useForm({
    name: '',
    email: '',
    phone: '',
    area: '',
    package_id: '',
    service_status: 'berbayar',
    district: '',
    village: '',
    rt_rw: '',
    address_detail: '',
    identity_photo: null,
    latitude: '',
    longitude: '',
    base_amount: 0,
    registration_date: new Date().toISOString().split('T')[0],
    installation_fee: 0,
    sales_id: '',
    is_reseller: false,
    billing_type: (page.props.billingSettings?.billing_type === 'pascabayar') ? 'pascabayar' : 'prabayar',
    billing_cycle: 'default',
    billing_day: null,
});

watch(isAffiliateAccount, (val) => {
    if (val && (form.service_status === 'gratis' || form.service_status === 'free_home')) {
        form.service_status = 'berbayar';
    }
}, { immediate: true });

watch(() => form.service_status, (newStatus) => {
    if (newStatus === 'gratis') {
        form.base_amount = 0;
        form.installation_fee = 0;
    } else if (newStatus === 'free_home') {
        form.package_id = '';
        form.base_amount = 0;
        form.installation_fee = 0;
    } else {
        applyPackagePricing();
    }
});

watch(() => form.package_id, (newId) => {
    if (registration_type.value === 'reseller') return;
    if (form.service_status === 'gratis' || form.service_status === 'free_home') {
        form.base_amount = 0;
        form.installation_fee = 0;
        return;
    }
    applyPackagePricing(newId);
});

watch(registration_type, (type) => {
    if (type === 'reseller') {
        form.is_reseller = true;
        form.package_id = '';
        form.base_amount = 0;
        form.installation_fee = 0;
    } else if (type === 'both') {
        form.is_reseller = true;
    } else {
        form.is_reseller = false;
    }
});

const selectedPackage = computed(() => {
    return props.packages?.find(p => String(p.id) === String(form.package_id)) || null;
});

// Poin 1: Tarif Standar Wilayah
const standardAreaInstallationFee = computed(() => {
    if (!selectedPackage.value) return 0;

    const areaFees = selectedPackage.value.area_installation_fees;
    if (areaFees && form.area) {
        const areaNameClean = form.area.toLowerCase().trim();
        if (Array.isArray(areaFees)) {
            const found = areaFees.find(item => 
                (item.area_name && item.area_name.toLowerCase().trim() === areaNameClean) ||
                (item.name && item.name.toLowerCase().trim() === areaNameClean)
            );
            if (found && found.fee !== undefined && found.fee !== null) {
                return Number(found.fee);
            }
        } else if (typeof areaFees === 'object') {
            for (const [key, val] of Object.entries(areaFees)) {
                if (key.toLowerCase().trim() === areaNameClean && val && val.fee !== undefined) {
                    return Number(val.fee);
                }
            }
        }
    }

    return Number(selectedPackage.value.installation_fee || 0);
});

// Poin 2: Opsi Pilihan Biaya Pasang Baru untuk Portal Sales
const availableInstallationOptions = computed(() => {
    if (!isSalesPortal.value || !selectedPackage.value || form.service_status !== 'berbayar') return [];

    let rawOptions = [];
    const areaFees = selectedPackage.value.area_installation_fees;
    if (areaFees && form.area) {
        const areaNameClean = form.area.toLowerCase().trim();
        if (Array.isArray(areaFees)) {
            const found = areaFees.find(item => 
                (item.area_name && item.area_name.toLowerCase().trim() === areaNameClean) ||
                (item.name && item.name.toLowerCase().trim() === areaNameClean)
            );
            if (found && Array.isArray(found.allowed_options) && found.allowed_options.length) {
                rawOptions = [...found.allowed_options];
            }
        }
    }

    // Pastikan standar area masuk dalam opsi jika opsi sales belum didefinisikan secara khusus
    const standardFee = standardAreaInstallationFee.value;
    if (!rawOptions.includes(standardFee)) {
        rawOptions.push(standardFee);
    }

    const uniqueVals = [...new Set(rawOptions.map(Number))].sort((a, b) => a - b);

    return uniqueVals.map(val => {
        let label = `Rp ${Number(val).toLocaleString('id-ID')}`;
        let tag = 'Opsi PSB';
        let isPromo = val === 0;
        if (isPromo) {
            tag = '🎁 Promo Gratis';
            label = 'Rp 0 (Bebas Biaya)';
        } else if (val === standardFee && standardFee > 0) {
            tag = `🏷️ Standar ${form.area || 'Area'}`;
        } else if (val === 120000) {
            tag = 'Biaya Normal Standar';
        }
        return {
            value: val,
            label,
            tag,
            isStandard: val === standardFee,
            isPromo,
        };
    });
});

function selectInstallationFee(fee) {
    form.installation_fee = Number(fee);
}

watch(() => form.area, (newArea) => {
    if (form.package_id && form.service_status === 'berbayar') {
        if (!isSalesPortal.value) {
            form.installation_fee = standardAreaInstallationFee.value;
        } else {
            const allowed = availableInstallationOptions.value.map(o => Number(o.value));
            if (!allowed.includes(Number(form.installation_fee))) {
                form.installation_fee = standardAreaInstallationFee.value;
            }
        }
    }
});

function packageMonthlyTariff(pkg) {
    return Number(pkg?.current_price ?? (pkg?.is_promo && pkg?.promo_price != null ? pkg.promo_price : pkg?.price) ?? 0);
}

function formatCurrency(value) {
    return Number(value || 0).toLocaleString('id-ID');
}

function applyPackagePricing(packageId = form.package_id) {
    const pkg = props.packages.find(p => String(p.id) === String(packageId));
    form.base_amount = pkg ? packageMonthlyTariff(pkg) : 0;
    if (form.service_status !== 'berbayar') {
        form.installation_fee = 0;
        return;
    }
    form.installation_fee = standardAreaInstallationFee.value;
}

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
    // Combine address parts into one string to match the backend expectation
    const addressParts = [
        form.address_detail,
        form.rt_rw ? `RT/RW: ${form.rt_rw}` : '',
        form.village ? `Kel. ${form.village}` : '',
        form.district ? `Kec. ${form.district}` : ''
    ].filter(Boolean).join(', ');
    
    form.transform((data) => ({
        ...data,
        address: addressParts || '-',
    })).post(props.affiliatePortal ? '/affiliate/booking' : '/customers', {
        onSuccess: () => {
            // Sukses diarahkan oleh controller
        },
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
