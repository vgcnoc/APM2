<template>
    <AppLayout title="Pelanggan Gratis" subtitle="Manajemen pelanggan non-berbayar, fasilitas kantor, kompensasi jalur & CSR">
        <div class="max-w-7xl mx-auto space-y-6 pb-12 font-sans">
            
            <!-- Alert Messages -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-sm font-medium shadow-sm flex items-center gap-2">
                <span class="text-lg">✅</span>
                <span>{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash?.error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl text-sm font-medium shadow-sm flex items-center gap-2">
                <span class="text-lg">❌</span>
                <span>{{ $page.props.flash.error }}</span>
            </div>

            <!-- Top Header & Action -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gradient-to-r from-emerald-600 via-teal-600 to-indigo-700 p-6 rounded-3xl text-white shadow-lg relative overflow-hidden">
                <div class="absolute -right-8 -bottom-8 w-44 h-44 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                <div class="relative z-10 space-y-1">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-xs font-bold uppercase tracking-wider text-emerald-100 border border-white/20">
                        <span>🎁</span> Bebas Tagihan & Invoice
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-white">Data Pelanggan Gratis</h2>
                    <p class="text-xs sm:text-sm text-emerald-100 max-w-xl">
                        Seluruh pelanggan di menu ini otomatis dibebaskan dari penerbitan invoice billing bulanan secara cerdas dan tersistem.
                    </p>
                </div>
                <div class="relative z-10 flex items-center gap-2">
                    <Link 
                        v-can="'customers_all_create'"
                        :href="route('customers.create', { service_status: 'gratis' })"
                        class="inline-flex items-center gap-2 bg-white text-emerald-800 hover:bg-emerald-50 px-4 py-2.5 rounded-xl font-bold text-xs sm:text-sm shadow-md transition-all hover:shadow-lg cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>+ Booking Pelanggan Gratis</span>
                    </Link>
                </div>
            </div>

            <!-- ── 4 RINGKASAN METRIK CERDAS ── -->
            <div v-can="'customers_card_summary'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Card 1: Total Pelanggan Gratis -->
                <div class="bg-white rounded-2xl p-5 border border-emerald-100 shadow-sm hover:border-emerald-300 transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            Total Pelanggan Gratis
                        </span>
                        <span class="text-xs bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full font-bold">100% Free</span>
                    </div>
                    <div class="text-3xl font-black text-gray-900">
                        {{ stats.total || 0 }}
                    </div>
                    <div class="mt-2 text-xs text-gray-500 font-medium flex items-center justify-between">
                        <span>Bebas dari Invoice Bulanan</span>
                        <span class="text-emerald-600 font-bold">Terdaftar</span>
                    </div>
                </div>

                <!-- Card 2: Layanan Aktif -->
                <div class="bg-white rounded-2xl p-5 border border-emerald-100 shadow-sm hover:border-emerald-300 transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-teal-700 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-teal-500"></span>
                            Pelanggan Aktif
                        </span>
                        <span class="text-xs bg-teal-50 text-teal-700 px-2 py-0.5 rounded-full font-bold">Status Aktif</span>
                    </div>
                    <div class="text-3xl font-black text-gray-900">
                        {{ stats.active || 0 }}
                    </div>
                    <div class="mt-2 text-xs text-gray-500 font-medium flex items-center justify-between">
                        <span>{{ stats.total > 0 ? Math.round((stats.active / stats.total) * 100) : 0 }}% Dari total gratis</span>
                        <span class="text-teal-600 font-bold">Terhubung</span>
                    </div>
                </div>

                <!-- Card 3: Total Bandwidth -->
                <div class="bg-white rounded-2xl p-5 border border-emerald-100 shadow-sm hover:border-emerald-300 transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-700 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                            Total Bandwidth Alokasi
                        </span>
                        <span class="text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-full font-bold">Kapasitas</span>
                    </div>
                    <div class="text-3xl font-black text-indigo-700">
                        {{ stats.total_bandwidth || 0 }} <span class="text-sm font-bold text-gray-500">Mbps</span>
                    </div>
                    <div class="mt-2 text-xs text-gray-500 font-medium flex items-center justify-between">
                        <span>Alokasi pipa jaringan</span>
                        <span class="text-indigo-600 font-bold">Dedicated/Up</span>
                    </div>
                </div>

                <!-- Card 4: Nilai CSR Bulanan -->
                <div class="bg-white rounded-2xl p-5 border border-emerald-100 shadow-sm hover:border-emerald-300 transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-purple-700 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                            Valuasi CSR / Bulan
                        </span>
                        <span class="text-xs bg-purple-50 text-purple-700 px-2 py-0.5 rounded-full font-bold">CSR Value</span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-purple-700 truncate">
                        Rp {{ formatRupiah(stats.monthly_csr_value || 0) }}
                    </div>
                    <div class="mt-2 text-xs text-gray-500 font-medium flex items-center justify-between">
                        <span>Nilai paket digratiskan</span>
                        <span class="text-purple-600 font-bold">Per Bulan</span>
                    </div>
                </div>

            </div>

            <!-- ── FILTER BAR ── -->
            <div v-can="'customers_filter_search'" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5 transition-all">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                    
                    <!-- Search Input -->
                    <div class="lg:col-span-4">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                            Pencarian Data
                        </label>
                        <div class="relative">
                            <input 
                                type="text" 
                                v-model="filterForm.search" 
                                @input="onSearchInput"
                                @keyup.enter="applyFilters"
                                placeholder="Cari nama, kode pelanggan, no HP, alamat..." 
                                class="block w-full text-xs rounded-xl border-gray-300 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 pl-8 pr-3 py-2 transition-colors"
                            />
                            <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Filter Area / Wilayah -->
                    <div class="lg:col-span-3">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                            Wilayah / Area
                        </label>
                        <select 
                            v-model="filterForm.area_id" 
                            @change="applyFilters"
                            class="block w-full text-xs rounded-xl border-gray-300 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 py-2 transition-colors cursor-pointer"
                        >
                            <option value="">Semua Wilayah</option>
                            <option v-for="a in areas" :key="a.id" :value="a.id">
                                {{ a.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Filter Paket Internet -->
                    <div class="lg:col-span-3">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                            Paket Layanan
                        </label>
                        <select 
                            v-model="filterForm.package_id" 
                            @change="applyFilters"
                            class="block w-full text-xs rounded-xl border-gray-300 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 py-2 transition-colors cursor-pointer"
                        >
                            <option value="">Semua Paket</option>
                            <option v-for="p in packages" :key="p.id" :value="p.id">
                                {{ p.name }} ({{ p.speed_mbps }} Mbps)
                            </option>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="lg:col-span-2 flex items-center gap-2">
                        <button 
                            @click="applyFilters"
                            type="button" 
                            class="flex-1 inline-flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-2 px-3 rounded-xl transition shadow-sm cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            Filter
                        </button>
                        <button 
                            v-if="hasActiveFilters"
                            @click="resetFilters" 
                            type="button" 
                            title="Reset Filter"
                            class="inline-flex items-center justify-center bg-gray-100 hover:bg-rose-50 hover:text-rose-600 text-gray-600 text-xs font-bold py-2 px-3 rounded-xl transition border border-gray-200 cursor-pointer"
                        >
                            Reset
                        </button>
                    </div>

                </div>
            </div>

            <!-- ── TABEL DAFTAR PELANGGAN GRATIS ── -->
            <div class="bg-white rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span>🎁</span> Daftar Pelanggan Non-Invoice (Gratis)
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Menampilkan seluruh data pelanggan dengan status non-berbayar.</p>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <Link 
                            :href="route('customers.index')" 
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-600 hover:text-gray-900 bg-white border border-gray-200 px-3 py-1.5 rounded-xl shadow-xs transition"
                        >
                            <span>Semua Pelanggan &rarr;</span>
                        </Link>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pelanggan</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Paket &amp; Bandwidth</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status Layanan</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Wilayah &amp; Alamat</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status Koneksi</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-for="c in customers.data" :key="c.id" class="hover:bg-gray-50/50 transition-colors">
                                <!-- Pelanggan Info -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center text-sm shadow-xs border border-emerald-200 shrink-0">
                                            {{ (c.name || 'P')[0].toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-gray-900 flex items-center gap-1.5">
                                                <span>{{ c.name }}</span>
                                                <span v-if="c.is_reseller" class="text-[9px] font-black uppercase px-1.5 py-0.2 bg-purple-100 text-purple-700 rounded">Reseller</span>
                                            </div>
                                            <div class="text-xs text-gray-500 font-mono mt-0.5">
                                                {{ c.customer_code }}
                                            </div>
                                            <div class="text-xs text-gray-400 flex items-center gap-2 mt-0.5">
                                                <span v-if="c.phone">{{ c.phone }}</span>
                                                <a v-if="c.phone" :href="`https://wa.me/${c.phone.replace(/^0/, '62')}`" target="_blank" class="text-[10px] text-emerald-600 font-bold hover:underline">
                                                    💬 WA
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Paket & Bandwidth -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">
                                        {{ c.package?.name || 'Paket Kustom' }}
                                    </div>
                                    <div class="flex items-center gap-1.5 mt-1">
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            ⚡ {{ c.package?.speed_mbps || 0 }} Mbps
                                        </span>
                                        <span class="text-[11px] text-gray-400 line-through">
                                            Rp {{ formatRupiah(c.package?.price || 0) }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Status Layanan -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1 items-start">
                                        <span class="inline-flex items-center gap-1.5 text-xs font-black px-2.5 py-1 rounded-xl bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-xs">
                                            <span>🎁</span>
                                            <span>GRATIS (Rp 0)</span>
                                        </span>
                                        <span class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded-md">
                                            Bebas Invoice Tagihan
                                        </span>
                                        <span v-if="c.notes" class="text-[11px] text-gray-500 italic line-clamp-1 max-w-[200px]" :title="c.notes">
                                            📝 {{ c.notes }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Wilayah & Alamat -->
                                <td class="px-6 py-4">
                                    <div class="text-xs font-bold text-gray-800">
                                        {{ c.areaModel?.name || c.area || '-' }}
                                    </div>
                                    <div class="text-[11px] text-gray-500 line-clamp-2 max-w-[220px] mt-0.5">
                                        {{ c.address }}
                                    </div>
                                    <div v-if="c.latitude && c.longitude" class="mt-1">
                                        <a :href="`https://www.google.com/maps?q=${c.latitude},${c.longitude}`" target="_blank" class="inline-flex items-center gap-1 text-[10px] text-blue-600 hover:underline font-bold">
                                            📍 Maps
                                        </a>
                                    </div>
                                </td>

                                <!-- Status Koneksi -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5">
                                        <span 
                                            class="w-2.5 h-2.5 rounded-full"
                                            :class="isCustomerOnline(c) ? 'bg-emerald-500 animate-pulse' : 'bg-gray-300'"
                                        ></span>
                                        <span 
                                            class="text-xs font-bold"
                                            :class="isCustomerOnline(c) ? 'text-emerald-700' : 'text-gray-500'"
                                        >
                                            {{ isCustomerOnline(c) ? 'Online' : 'Offline' }}
                                        </span>
                                    </div>
                                    <div class="text-[10px] text-gray-400 font-mono mt-0.5">
                                        {{ c.ont?.pppoe_user || 'PPPoE -' }}
                                    </div>
                                    <div class="mt-0.5">
                                        <span class="text-[9px] uppercase px-1.5 py-0.2 rounded font-bold" :class="c.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'">
                                            {{ c.status }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Lihat Detail -->
                                        <Link 
                                            :href="route('customers.show', c.id)" 
                                            class="p-2 rounded-xl text-gray-500 hover:bg-gray-100 hover:text-indigo-600 transition" 
                                            title="Lihat Profil Pelanggan"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </Link>

                                        <!-- Edit -->
                                        <Link 
                                            v-can="'customers_all_edit'"
                                            :href="route('customers.edit', c.id)" 
                                            class="p-2 rounded-xl text-gray-500 hover:bg-gray-100 hover:text-amber-600 transition" 
                                            title="Edit Data"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </Link>

                                        <!-- Ubah ke Berbayar -->
                                        <button 
                                            v-can="'customers_all_edit'"
                                            @click="openSwitchModal(c)" 
                                            class="inline-flex items-center gap-1 bg-amber-50 hover:bg-amber-100 text-amber-700 px-3 py-1.5 rounded-xl border border-amber-200 transition text-xs font-bold cursor-pointer"
                                            title="Ubah status ke Berbayar"
                                        >
                                            <span>💳 Jadikan Berbayar</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!customers.data.length">
                                <td colspan="6" class="px-6 py-16 text-center text-gray-500">
                                    <div class="max-w-sm mx-auto flex flex-col items-center">
                                        <div class="w-14 h-14 rounded-3xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-3xl mb-3 shadow-inner">
                                            🎁
                                        </div>
                                        <p class="font-bold text-gray-800 text-base">Tidak ada pelanggan gratis</p>
                                        <p class="text-xs text-gray-400 mt-1">Belum ada pelanggan berstatus gratis yang cocok dengan pencarian / filter Anda.</p>
                                        <button 
                                            v-if="hasActiveFilters" 
                                            @click="resetFilters" 
                                            class="mt-3 text-xs text-emerald-600 font-bold hover:underline cursor-pointer"
                                        >
                                            Reset Filter
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div v-if="customers.links && customers.links.length > 3" class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-xs text-gray-500">
                        Menampilkan <span class="font-bold">{{ customers.from || 0 }}</span> - <span class="font-bold">{{ customers.to || 0 }}</span> dari <span class="font-bold">{{ customers.total || 0 }}</span> pelanggan gratis
                    </div>
                    <div class="flex flex-wrap gap-1">
                        <Link 
                            v-for="(link, i) in customers.links" 
                            :key="i" 
                            :href="link.url || '#'" 
                            v-html="link.label"
                            class="px-3 py-1.5 text-xs rounded-lg border transition-all"
                            :class="[
                                link.active ? 'bg-emerald-600 text-white font-bold border-emerald-600 shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-50 border-gray-200',
                                !link.url ? 'opacity-40 pointer-events-none' : ''
                            ]"
                        />
                    </div>
                </div>

            </div>

        </div>

        <!-- ── MODAL BERALIH STATUS KE BERBAYAR ── -->
        <Teleport to="body">
            <div v-if="showModal && selectedCustomer" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showModal = false"></div>
                <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 overflow-hidden animate-fade-in-up border border-gray-100">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg">
                                💳
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Ubah Menjadi Berbayar</h3>
                                <p class="text-xs text-gray-500">{{ selectedCustomer.name }} ({{ selectedCustomer.customer_code }})</p>
                            </div>
                        </div>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-lg">✕</button>
                    </div>

                    <form @submit.prevent="submitSwitchStatus" class="space-y-4 pt-4">
                        <div class="bg-amber-50 border border-amber-200 p-3.5 rounded-2xl text-xs text-amber-800">
                            <p class="font-bold">Konfirmasi Pengalihan Layanan</p>
                            <p class="mt-1">
                                Mengubah pelanggan ini ke status <strong>Berbayar</strong> akan mengaktifkan kembali tarif bulanan normal (<strong>Rp {{ formatRupiah(selectedCustomer.package?.price || 0) }}</strong>) dan sistem billing akan mulai menerbitkan invoice rutin.
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Catatan / Alasan Pengalihan</label>
                            <textarea 
                                v-model="switchForm.notes" 
                                rows="2" 
                                placeholder="Contoh: Masa trial selesai / kesepakatan kompensasi berakhir..."
                                class="w-full text-xs rounded-xl border-gray-200 p-2.5 focus:ring-amber-500 focus:border-amber-500"
                            ></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
                            <button 
                                type="button" 
                                @click="showModal = false" 
                                class="px-4 py-2 rounded-xl text-xs font-bold bg-gray-100 text-gray-700 hover:bg-gray-200 transition cursor-pointer"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                :disabled="switchForm.processing"
                                class="px-5 py-2 rounded-xl text-xs font-bold bg-amber-600 text-white hover:bg-amber-700 shadow-sm transition cursor-pointer disabled:opacity-50"
                            >
                                {{ switchForm.processing ? 'Memproses...' : 'Ya, Ubah Jadi Berbayar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    customers: Object,
    packages: Array,
    areas: Array,
    filters: Object,
    onlineUsernames: Array,
    stats: Object,
});

const filterForm = reactive({
    search: props.filters?.search || '',
    area_id: props.filters?.area_id || '',
    package_id: props.filters?.package_id || '',
    status: props.filters?.status || '',
});

const hasActiveFilters = computed(() => {
    return !!(filterForm.search || filterForm.area_id || filterForm.package_id || filterForm.status);
});

let searchTimeout = null;
const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 450);
};

const applyFilters = () => {
    router.get(route('customers.gratis'), {
        search: filterForm.search || undefined,
        area_id: filterForm.area_id || undefined,
        package_id: filterForm.package_id || undefined,
        status: filterForm.status || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.search = '';
    filterForm.area_id = '';
    filterForm.package_id = '';
    filterForm.status = '';
    applyFilters();
};

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID').format(val || 0);
};

const isCustomerOnline = (customer) => {
    const user = customer.ont?.pppoe_user;
    if (!user) return false;
    return (props.onlineUsernames || []).includes(user);
};

// Modal switch status
const showModal = ref(false);
const selectedCustomer = ref(null);
const switchForm = useForm({
    service_status: 'berbayar',
    notes: '',
});

const openSwitchModal = (customer) => {
    selectedCustomer.value = customer;
    switchForm.service_status = 'berbayar';
    switchForm.notes = '';
    showModal.value = true;
};

const submitSwitchStatus = () => {
    if (!selectedCustomer.value) return;
    switchForm.post(route('customers.toggle-service-status', selectedCustomer.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
        }
    });
};
</script>
