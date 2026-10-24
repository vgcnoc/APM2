<template>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-md">
        <!-- ═══ Widget Header ═══ -->
        <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-slate-50/80 via-white to-blue-50/30">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-blue-600 via-indigo-600 to-sky-500 text-white flex items-center justify-center font-bold shadow-md shadow-indigo-500/20 text-xl shrink-0">
                        🔍
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-base font-bold text-gray-900">Pencarian Pelanggan & Foto Rumah</h2>
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                                🏠 Foto & Alamat
                            </span>
                            <span v-if="hasSearched && !isLoading" class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                {{ results.length }} Ditemukan
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Cari cepat nama pelanggan untuk melihat alamat lengkap, status, dan dokumentasi foto rumah.
                        </p>
                    </div>
                </div>

                <!-- Quick Filter / Badges Info -->
                <div class="flex items-center gap-2">
                    <button
                        v-if="hasSearched || searchQuery"
                        @click="resetSearch"
                        type="button"
                        class="px-3 py-1.5 text-xs font-semibold text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-xl transition flex items-center gap-1 cursor-pointer"
                        title="Bersihkan Pencarian"
                    >
                        <span>✕</span>
                        <span>Reset</span>
                    </button>
                </div>
            </div>

            <!-- ═══ Search Input Bar ═══ -->
            <div class="mt-4 relative">
                <div class="relative flex items-center">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input
                        v-model="searchQuery"
                        @input="handleInput"
                        @keydown.enter.prevent="executeSearch"
                        type="text"
                        placeholder="Ketik nama pelanggan, ID pelanggan, alamat, atau no. telepon..."
                        class="w-full pl-11 pr-24 py-3 bg-white text-sm font-medium text-gray-800 placeholder-gray-400 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none shadow-2xs"
                    />
                    <div class="absolute inset-y-0 right-1.5 flex items-center gap-1.5">
                        <button
                            v-if="searchQuery"
                            @click="clearInput"
                            type="button"
                            class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition"
                            title="Hapus teks"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                        <button
                            @click="executeSearch"
                            type="button"
                            :disabled="isLoading"
                            class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-bold rounded-lg shadow-sm hover:shadow transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                        >
                            <svg v-if="isLoading" class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ isLoading ? 'Mencari...' : 'Cari' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Helper tip under input -->
                <div class="flex items-center justify-between text-[11px] text-gray-400 mt-2 px-1">
                    <span class="flex items-center gap-1">
                        <span>💡</span>
                        <span>Pencarian otomatis berjalan saat Anda mengetik minimal 2 huruf.</span>
                    </span>
                    <span v-if="areaId && areaId !== 'all'" class="text-blue-600 font-medium">
                        Wilayah aktif: {{ areaId }}
                    </span>
                </div>
            </div>
        </div>

        <!-- ═══ Search Content Area ═══ -->
        <div class="p-5">
            <!-- 1. Loading State -->
            <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <div v-for="i in 4" :key="i" class="bg-gray-50 rounded-2xl border border-gray-200/60 overflow-hidden animate-pulse">
                    <div class="h-44 bg-gray-200 w-full"></div>
                    <div class="p-4 space-y-3">
                        <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                        <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                        <div class="h-10 bg-gray-200 rounded w-full"></div>
                    </div>
                </div>
            </div>

            <!-- 2. Idle State (No search performed yet) -->
            <div v-else-if="!hasSearched" class="py-10 text-center">
                <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gradient-to-tr from-blue-50 to-indigo-50 border border-blue-100 flex items-center justify-center text-3xl shadow-xs">
                    🏡
                </div>
                <h3 class="text-sm font-bold text-gray-800">Cari Data Pelanggan & Foto Rumah</h3>
                <p class="text-xs text-gray-500 max-w-md mx-auto mt-1">
                    Ketik nama pelanggan pada kolom pencarian di atas untuk menampilkan foto rumah, alamat lengkap, paket internet, dan kontak langsung.
                </p>
                <div class="mt-4 flex items-center justify-center gap-2 flex-wrap">
                    <span class="text-[11px] font-medium text-gray-400">Contoh pencarian populer:</span>
                    <button
                        v-for="sample in ['Budi', 'Ahmad', 'Siti', 'Dewi']"
                        :key="sample"
                        @click="searchSample(sample)"
                        type="button"
                        class="px-2.5 py-1 text-xs font-semibold bg-gray-100 hover:bg-blue-50 hover:text-blue-600 text-gray-600 rounded-lg transition border border-gray-200/60"
                    >
                        "{{ sample }}"
                    </button>
                </div>
            </div>

            <!-- 3. Empty Results State -->
            <div v-else-if="hasSearched && results.length === 0" class="py-12 text-center">
                <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-amber-50 border border-amber-100 flex items-center justify-center text-3xl">
                    🔎
                </div>
                <h3 class="text-sm font-bold text-gray-800">Pelanggan Tidak Ditemukan</h3>
                <p class="text-xs text-gray-500 max-w-md mx-auto mt-1">
                    Tidak ditemukan data pelanggan dengan kata kunci <span class="font-bold text-gray-700">"{{ searchQuery }}"</span>. Pastikan ejaan nama atau nomor sudah benar.
                </p>
                <button
                    @click="clearInput"
                    type="button"
                    class="mt-4 px-4 py-2 text-xs font-bold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-xl transition"
                >
                    Coba Kata Kunci Lain
                </button>
            </div>

            <!-- 4. Results Grid -->
            <div v-else class="space-y-4">
                <div class="flex items-center justify-between text-xs text-gray-500 px-1">
                    <span>Menampilkan <strong>{{ results.length }}</strong> pelanggan untuk "<strong>{{ searchQuery }}</strong>"</span>
                    <span class="text-[11px] text-gray-400">Klik foto untuk melihat ukuran penuh</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <div
                        v-for="customer in results"
                        :key="customer.id"
                        class="bg-white rounded-2xl border border-gray-200/80 shadow-2xs hover:shadow-md hover:border-blue-300 transition-all duration-200 flex flex-col overflow-hidden group"
                    >
                        <!-- ═══ House Photo Container ═══ -->
                        <div class="relative w-full aspect-16/10 bg-slate-900 overflow-hidden cursor-pointer" @click="openLightbox(customer)">
                            <template v-if="customer.house_photo_url">
                                <img
                                    :src="customer.house_photo_url"
                                    :alt="'Foto Rumah ' + customer.name"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    loading="lazy"
                                />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-black/20 opacity-80 group-hover:opacity-60 transition-opacity"></div>
                                
                                <!-- Photo Source Tag -->
                                <div class="absolute top-2.5 left-2.5">
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-lg bg-black/60 text-white backdrop-blur-md border border-white/20 shadow-sm">
                                        🏠 {{ customer.house_photo_source || 'Foto Rumah' }}
                                    </span>
                                </div>

                                <!-- Photo Gallery Badge (if multiple photos) -->
                                <div v-if="customer.all_photos && customer.all_photos.length > 1" class="absolute top-2.5 right-2.5">
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-lg bg-blue-600/80 text-white backdrop-blur-md shadow-sm">
                                        📷 {{ customer.all_photos.length }} Foto
                                    </span>
                                </div>

                                <!-- Zoom Icon Hint on Hover -->
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                    <span class="bg-black/60 text-white text-xs font-bold px-3 py-1.5 rounded-full backdrop-blur-md flex items-center gap-1 border border-white/20 shadow-lg">
                                        🔍 Perbesar Foto
                                    </span>
                                </div>
                            </template>

                            <!-- Placeholder when no house photo -->
                            <template v-else>
                                <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-tr from-slate-100 to-slate-200 text-slate-400 p-4 text-center">
                                    <div class="w-12 h-12 rounded-2xl bg-white shadow-xs flex items-center justify-center text-2xl mb-2 text-slate-400">
                                        🏠
                                    </div>
                                    <span class="text-xs font-bold text-slate-600">Foto Rumah Belum Tersedia</span>
                                    <span class="text-[10px] text-slate-400 mt-0.5">Belum ada dokumentasi survey/instalasi</span>
                                </div>
                            </template>

                            <!-- Status Pill on bottom of image -->
                            <div class="absolute bottom-2.5 left-2.5 right-2.5 flex items-center justify-between">
                                <span class="font-mono text-[11px] font-bold text-white px-2 py-0.5 rounded bg-black/50 backdrop-blur-sm border border-white/10">
                                    {{ customer.customer_code }}
                                </span>
                                <span
                                    class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider shadow-sm"
                                    :class="getStatusBadgeClass(customer.status)"
                                >
                                    {{ formatStatusLabel(customer.status) }}
                                </span>
                            </div>
                        </div>

                        <!-- ═══ Customer Info Body ═══ -->
                        <div class="p-4 flex-1 flex flex-col justify-between">
                            <div>
                                <!-- Name -->
                                <div class="flex items-start justify-between gap-2">
                                    <h4 class="font-bold text-gray-900 text-sm leading-snug line-clamp-1" :title="customer.name">
                                        {{ customer.name }}
                                    </h4>
                                </div>

                                <!-- Package & Area -->
                                <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-md">
                                        🚀 {{ customer.package_name }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-gray-600 bg-gray-100 px-2 py-0.5 rounded-md">
                                        📍 {{ customer.area_name }}
                                    </span>
                                </div>

                                <!-- Address Highlight (Key Requirement!) -->
                                <div class="mt-3 bg-slate-50 border border-slate-200/70 rounded-xl p-2.5">
                                    <div class="flex items-start gap-1.5">
                                        <span class="text-xs mt-0.5 shrink-0">📍</span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-medium text-gray-800 line-clamp-2 leading-relaxed" :title="customer.address">
                                                {{ customer.address }}
                                            </p>
                                        </div>
                                    </div>
                                    <div v-if="customer.maps_url" class="mt-1.5 pt-1.5 border-t border-slate-200/50 flex items-center justify-between">
                                        <span class="text-[10px] text-gray-400">Koordinat GPS / Peta</span>
                                        <a
                                            :href="customer.maps_url"
                                            target="_blank"
                                            class="text-[10px] font-bold text-blue-600 hover:text-blue-700 hover:underline flex items-center gap-0.5"
                                        >
                                            Buka Google Maps ↗
                                        </a>
                                    </div>
                                </div>

                                <!-- Additional Details (ODP, Phone) -->
                                <div class="mt-2.5 space-y-1 text-xs">
                                    <!-- Phone / WhatsApp -->
                                    <div class="flex items-center justify-between text-gray-600">
                                        <span class="text-[11px] text-gray-400 flex items-center gap-1">
                                            <span>📱</span>
                                            <span>WhatsApp</span>
                                        </span>
                                        <template v-if="customer.phone">
                                            <a
                                                v-if="customer.wa_url"
                                                :href="customer.wa_url"
                                                target="_blank"
                                                class="font-semibold text-emerald-600 hover:text-emerald-700 hover:underline flex items-center gap-1"
                                                title="Hubungi via WhatsApp"
                                            >
                                                <span>{{ customer.phone }}</span>
                                                <span class="text-xs">💬</span>
                                            </a>
                                            <span v-else class="font-medium text-gray-700">{{ customer.phone }}</span>
                                        </template>
                                        <span v-else class="text-gray-400">-</span>
                                    </div>

                                    <!-- ODP & Port -->
                                    <div v-if="customer.odp_name" class="flex items-center justify-between text-gray-600">
                                        <span class="text-[11px] text-gray-400 flex items-center gap-1">
                                            <span>🔌</span>
                                            <span>ODP / Port</span>
                                        </span>
                                        <span class="font-semibold text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded text-[11px]">
                                            {{ customer.odp_name }} (Port {{ customer.port_number || '-' }})
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Action Buttons -->
                            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center gap-2">
                                <button
                                    @click="openDetailModal(customer)"
                                    type="button"
                                    class="flex-1 py-1.5 px-2 bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-xs rounded-xl transition text-center cursor-pointer flex items-center justify-center gap-1"
                                >
                                    <span>🔍</span>
                                    <span>Detail & Foto</span>
                                </button>
                                <a
                                    :href="`/customers/${customer.id}`"
                                    class="py-1.5 px-3 bg-gray-50 hover:bg-gray-100 text-gray-700 font-bold text-xs rounded-xl transition text-center border border-gray-200/80 cursor-pointer"
                                    title="Buka profil lengkap pelanggan"
                                >
                                    Profil →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════════ -->
        <!-- ═══ MODAL DETAIL LENGKAP & GALERI FOTO PELANGGAN             ═══ -->
        <!-- ════════════════════════════════════════════════════════════════ -->
        <div
            v-if="activeCustomerModal"
            class="fixed inset-0 z-50 overflow-y-auto bg-black/70 backdrop-blur-xs flex items-center justify-center p-4 animate-in fade-in duration-200"
            @click.self="activeCustomerModal = null"
        >
            <div class="bg-white rounded-3xl max-w-3xl w-full overflow-hidden shadow-2xl border border-gray-100 my-8">
                <!-- Modal Header -->
                <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-slate-50 via-white to-blue-50/40 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-lg shadow-md shadow-blue-500/20">
                            🏠
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-gray-900 text-base">{{ activeCustomerModal.name }}</h3>
                                <span class="font-mono text-xs font-bold bg-gray-100 px-2 py-0.5 rounded text-gray-700">
                                    {{ activeCustomerModal.customer_code }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500">Informasi detail pelanggan, alamat dan arsip foto dokumentasi</p>
                        </div>
                    </div>
                    <button
                        @click="activeCustomerModal = null"
                        type="button"
                        class="p-2 text-gray-400 hover:text-gray-600 rounded-xl hover:bg-gray-100 transition"
                    >
                        ✕
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                    <!-- Photo Display / Gallery -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                                <span>📸</span>
                                <span>Dokumentasi Foto Rumah & Lokasi</span>
                            </h4>
                            <span v-if="activeCustomerModal.all_photos?.length" class="text-xs text-gray-400">
                                {{ activeCustomerModal.all_photos.length }} Foto Tersimpan
                            </span>
                        </div>

                        <!-- Active Selected Photo in Modal -->
                        <div class="relative bg-slate-950 rounded-2xl overflow-hidden aspect-16/9 flex items-center justify-center border border-gray-800 group">
                            <template v-if="selectedModalPhotoUrl">
                                <img
                                    :src="selectedModalPhotoUrl"
                                    :alt="selectedModalPhotoLabel"
                                    class="w-full h-full object-contain"
                                />
                                <div class="absolute bottom-3 left-3 bg-black/70 backdrop-blur-md px-3 py-1.5 rounded-xl border border-white/10 text-white text-xs font-semibold flex items-center gap-2">
                                    <span>🏷️</span>
                                    <span>{{ selectedModalPhotoLabel }}</span>
                                </div>
                                <div class="absolute top-3 right-3 flex items-center gap-2">
                                    <a
                                        :href="selectedModalPhotoUrl"
                                        target="_blank"
                                        class="px-3 py-1.5 rounded-xl bg-white/20 hover:bg-white/40 text-white backdrop-blur-md text-xs font-bold transition flex items-center gap-1"
                                    >
                                        Buka Penuh ↗
                                    </a>
                                </div>
                            </template>
                            <template v-else>
                                <div class="text-center p-6 text-slate-400">
                                    <span class="text-4xl block mb-2">🏠</span>
                                    <p class="text-xs font-semibold text-slate-300">Belum ada foto rumah yang diunggah</p>
                                    <p class="text-[11px] text-slate-500 mt-1">Foto otomatis muncul saat petugas menyimpan hasil survey atau instalasi ONT.</p>
                                </div>
                            </template>
                        </div>

                        <!-- Photo Thumbnails Bar -->
                        <div v-if="activeCustomerModal.all_photos?.length > 1" class="mt-3 flex items-center gap-2.5 overflow-x-auto pb-1">
                            <button
                                v-for="(photo, idx) in activeCustomerModal.all_photos"
                                :key="idx"
                                @click="selectModalPhoto(photo)"
                                type="button"
                                class="shrink-0 w-20 h-14 rounded-xl overflow-hidden border-2 transition relative"
                                :class="selectedModalPhotoUrl === photo.url ? 'border-blue-600 ring-2 ring-blue-500/30' : 'border-gray-200 opacity-70 hover:opacity-100'"
                            >
                                <img :src="photo.url" :alt="photo.label" class="w-full h-full object-cover" />
                                <span class="absolute bottom-0 inset-x-0 bg-black/60 text-white text-[9px] truncate px-1 text-center font-medium">
                                    {{ photo.label }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Customer Detail Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Alamat Lengkap Box -->
                        <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200/80 md:col-span-2">
                            <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block mb-1">
                                📍 Alamat Lengkap Rumah Pelanggan
                            </span>
                            <p class="text-sm font-semibold text-gray-800 leading-relaxed">
                                {{ activeCustomerModal.address }}
                            </p>
                            <div v-if="activeCustomerModal.maps_url" class="mt-2.5 pt-2 border-t border-gray-200 flex items-center justify-between">
                                <span class="text-xs text-gray-500">
                                    Koordinat: {{ activeCustomerModal.latitude || '-' }}, {{ activeCustomerModal.longitude || '-' }}
                                </span>
                                <a
                                    :href="activeCustomerModal.maps_url"
                                    target="_blank"
                                    class="text-xs font-bold text-blue-600 hover:text-blue-700 bg-white px-3 py-1 rounded-lg border border-gray-200 shadow-2xs hover:shadow transition flex items-center gap-1"
                                >
                                    <span>🗺️ Buka di Google Maps</span>
                                </a>
                            </div>
                        </div>

                        <!-- Data Teknis -->
                        <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200/80 space-y-2.5 text-xs">
                            <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block mb-1">
                                🌐 Paket & Wilayah
                            </span>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Paket Internet</span>
                                <span class="font-bold text-blue-700">{{ activeCustomerModal.package_name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Wilayah / Area</span>
                                <span class="font-semibold text-gray-800">{{ activeCustomerModal.area_name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Status Langganan</span>
                                <span
                                    class="font-bold px-2 py-0.5 rounded-full text-[10px] uppercase"
                                    :class="getStatusBadgeClass(activeCustomerModal.status)"
                                >
                                    {{ formatStatusLabel(activeCustomerModal.status) }}
                                </span>
                            </div>
                        </div>

                        <!-- Data Kontak & ODP -->
                        <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200/80 space-y-2.5 text-xs">
                            <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block mb-1">
                                🔌 Sambungan & Kontak
                            </span>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">No. WhatsApp</span>
                                <a
                                    v-if="activeCustomerModal.wa_url"
                                    :href="activeCustomerModal.wa_url"
                                    target="_blank"
                                    class="font-bold text-emerald-600 hover:underline flex items-center gap-1"
                                >
                                    <span>{{ activeCustomerModal.phone }}</span>
                                    <span>💬</span>
                                </a>
                                <span v-else class="text-gray-400">-</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">ODP / Box</span>
                                <span class="font-semibold text-indigo-700">{{ activeCustomerModal.odp_name || 'Belum Terpasang' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Port ODP</span>
                                <span class="font-semibold text-gray-800">{{ activeCustomerModal.port_number ? 'Port ' + activeCustomerModal.port_number : '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                    <span class="text-xs text-gray-400">
                        ID Pelanggan: <strong>{{ activeCustomerModal.customer_code }}</strong>
                    </span>
                    <div class="flex items-center gap-2">
                        <button
                            @click="activeCustomerModal = null"
                            type="button"
                            class="px-4 py-2 text-xs font-semibold text-gray-600 bg-white border border-gray-200 hover:bg-gray-100 rounded-xl transition"
                        >
                            Tutup
                        </button>
                        <a
                            :href="`/customers/${activeCustomerModal.id}`"
                            class="px-4 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition flex items-center gap-1"
                        >
                            <span>Buka Halaman Lengkap Pelanggan →</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════════ -->
        <!-- ═══ LIGHTBOX PREVIEW MODAL (Full Resolution Image Viewer)    ═══ -->
        <!-- ════════════════════════════════════════════════════════════════ -->
        <div
            v-if="lightboxImage"
            class="fixed inset-0 z-50 bg-black/90 backdrop-blur-md flex flex-col justify-between p-4 sm:p-6 animate-in fade-in duration-200"
            @click.self="lightboxImage = null"
        >
            <!-- Top Controls -->
            <div class="flex items-center justify-between text-white z-10">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">🏠</span>
                    <div>
                        <h4 class="font-bold text-sm text-white">{{ lightboxTitle }}</h4>
                        <p class="text-xs text-gray-400">{{ lightboxSubtitle }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a
                        :href="lightboxImage"
                        :download="'foto_rumah_' + (lightboxCustomer?.customer_code || 'pelanggan') + '.jpg'"
                        class="px-3 py-1.5 rounded-xl bg-white/20 hover:bg-white/30 text-white text-xs font-bold transition flex items-center gap-1"
                        title="Unduh Foto"
                    >
                        <span>📥</span>
                        <span>Unduh</span>
                    </a>
                    <button
                        @click="lightboxImage = null"
                        type="button"
                        class="p-2 text-white/70 hover:text-white rounded-xl bg-white/10 hover:bg-white/20 transition"
                        title="Tutup (ESC)"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <!-- Centered Image Container -->
            <div class="flex-1 flex items-center justify-center p-2 sm:p-6 overflow-hidden">
                <img
                    :src="lightboxImage"
                    :alt="lightboxTitle"
                    class="max-h-[80vh] max-w-full rounded-2xl shadow-2xl object-contain border border-white/10"
                />
            </div>

            <!-- Bottom Caption & Address -->
            <div class="max-w-2xl mx-auto w-full bg-white/10 backdrop-blur-md rounded-2xl p-3 border border-white/10 text-white text-center text-xs">
                <p class="font-bold text-white mb-0.5">{{ lightboxCustomer?.name }} ({{ lightboxCustomer?.customer_code }})</p>
                <p class="text-gray-300 text-[11px] line-clamp-1">📍 {{ lightboxCustomer?.address }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    initialAreaId: {
        type: [String, Number],
        default: 'all',
    },
});

const searchQuery = ref('');
const areaId = ref(props.initialAreaId);
const isLoading = ref(false);
const hasSearched = ref(false);
const results = ref([]);
let debounceTimeout = null;

// Modal States
const activeCustomerModal = ref(null);
const selectedModalPhotoUrl = ref(null);
const selectedModalPhotoLabel = ref('');

// Lightbox States
const lightboxImage = ref(null);
const lightboxTitle = ref('');
const lightboxSubtitle = ref('');
const lightboxCustomer = ref(null);

watch(() => props.initialAreaId, (newVal) => {
    areaId.value = newVal;
    if (searchQuery.value.trim().length >= 2) {
        executeSearch();
    }
});

function handleInput() {
    clearTimeout(debounceTimeout);
    const query = searchQuery.value.trim();
    if (query.length >= 2) {
        debounceTimeout = setTimeout(() => {
            executeSearch();
        }, 350);
    } else if (query.length === 0) {
        results.value = [];
        hasSearched.value = false;
    }
}

async function executeSearch() {
    const query = searchQuery.value.trim();
    if (query.length < 2) return;

    isLoading.value = true;
    try {
        const response = await axios.get('/dashboard/search-customer', {
            params: {
                q: query,
                area_id: areaId.value !== 'all' ? areaId.value : undefined,
            },
        });

        if (response.data && response.data.success) {
            results.value = response.data.customers || [];
            hasSearched.value = true;
        }
    } catch (error) {
        console.error('Failed to search customer:', error);
    } finally {
        isLoading.value = false;
    }
}

function clearInput() {
    searchQuery.value = '';
    results.value = [];
    hasSearched.value = false;
}

function resetSearch() {
    clearInput();
}

function searchSample(name) {
    searchQuery.value = name;
    executeSearch();
}

function openLightbox(customer) {
    if (!customer.house_photo_url) {
        openDetailModal(customer);
        return;
    }
    lightboxImage.value = customer.house_photo_url;
    lightboxTitle.value = 'Foto Rumah - ' + customer.name;
    lightboxSubtitle.value = customer.house_photo_source || 'Dokumentasi Lokasi Pelanggan';
    lightboxCustomer.value = customer;
}

function openDetailModal(customer) {
    activeCustomerModal.value = customer;
    if (customer.house_photo_url) {
        selectedModalPhotoUrl.value = customer.house_photo_url;
        selectedModalPhotoLabel.value = customer.house_photo_source || 'Foto Rumah';
    } else if (customer.all_photos && customer.all_photos.length > 0) {
        selectedModalPhotoUrl.value = customer.all_photos[0].url;
        selectedModalPhotoLabel.value = customer.all_photos[0].label;
    } else {
        selectedModalPhotoUrl.value = null;
        selectedModalPhotoLabel.value = '';
    }
}

function selectModalPhoto(photo) {
    selectedModalPhotoUrl.value = photo.url;
    selectedModalPhotoLabel.value = photo.label;
}

function getStatusBadgeClass(status) {
    switch (status) {
        case 'active':
            return 'bg-emerald-500/20 text-emerald-700 ring-1 ring-emerald-500/30';
        case 'booking':
            return 'bg-yellow-500/20 text-yellow-800 ring-1 ring-yellow-500/30';
        case 'survey':
            return 'bg-blue-500/20 text-blue-700 ring-1 ring-blue-500/30';
        case 'installing':
            return 'bg-indigo-500/20 text-indigo-700 ring-1 ring-indigo-500/30';
        case 'suspended':
            return 'bg-amber-500/20 text-amber-800 ring-1 ring-amber-500/30';
        case 'terminated':
        case 'canceled':
            return 'bg-rose-500/20 text-rose-700 ring-1 ring-rose-500/30';
        default:
            return 'bg-gray-500/20 text-gray-700 ring-1 ring-gray-500/30';
    }
}

function formatStatusLabel(status) {
    const labels = {
        active: 'Aktif',
        booking: 'Booking',
        survey: 'Survey',
        installing: 'Instalasi',
        suspended: 'Isolir',
        terminated: 'Berhenti',
        canceled: 'Dibatalkan',
    };
    return labels[status] || status || 'Status';
}

function handleKeydown(e) {
    if (e.key === 'Escape') {
        if (lightboxImage.value) {
            lightboxImage.value = null;
        } else if (activeCustomerModal.value) {
            activeCustomerModal.value = null;
        }
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});
</script>
