<template>
    <AppLayout title="Pelanggan Free Home" subtitle="Manajemen pelanggan Voucher Rumahan ONT (Non-PPPoE & Non-Invoice)">
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
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gradient-to-r from-amber-600 via-orange-600 to-rose-600 p-6 rounded-3xl text-white shadow-lg relative overflow-hidden">
                <div class="absolute -right-8 -bottom-8 w-48 h-48 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                <div class="relative z-10 space-y-1">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-xs font-bold uppercase tracking-wider text-amber-100 border border-white/20">
                        <span>🏠</span> Voucher Rumahan ONT • Bebas Tagihan
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-white">Data Pelanggan Free Home</h2>
                    <p class="text-xs sm:text-sm text-amber-100 max-w-xl leading-relaxed">
                        Layanan internet berbasis Voucher Rumahan menggunakan ONT stabil tanpa sinkronisasi PPPoE RADIUS dan bebas dari invoice tagihan bulanan.
                    </p>
                </div>
                <div class="relative z-10 flex items-center gap-2">
                    <Link 
                        v-can="'customers_all_create'"
                        :href="route('customers.create', { service_status: 'free_home' })"
                        class="inline-flex items-center gap-2 bg-white text-orange-800 hover:bg-orange-50 px-4 py-2.5 rounded-xl font-bold text-xs sm:text-sm shadow-md transition-all hover:shadow-lg cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>+ Booking Pelanggan Free Home</span>
                    </Link>
                </div>
            </div>

            <!-- ── 4 RINGKASAN METRIK CERDAS ── -->
            <div v-can="'customers_card_summary'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Card 1: Total Pelanggan Free Home -->
                <div class="bg-white rounded-2xl p-5 border border-amber-100 shadow-sm hover:border-amber-300 transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-amber-800 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            Total Free Home
                        </span>
                        <span class="text-xs bg-amber-50 text-amber-700 px-2 py-0.5 rounded-full font-bold">Voucher ONT</span>
                    </div>
                    <div class="text-3xl font-black text-gray-900">
                        {{ stats.total || 0 }}
                    </div>
                    <div class="mt-2 text-xs text-gray-500 font-medium flex items-center justify-between">
                        <span>Bebas Invoice Rutin</span>
                        <span class="text-amber-600 font-bold">Terdaftar</span>
                    </div>
                </div>

                <!-- Card 2: Layanan Aktif -->
                <div class="bg-white rounded-2xl p-5 border border-amber-100 shadow-sm hover:border-amber-300 transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            Pelanggan Aktif
                        </span>
                        <span class="text-xs bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full font-bold">Siap Pakai</span>
                    </div>
                    <div class="text-3xl font-black text-gray-900">
                        {{ stats.active || 0 }}
                    </div>
                    <div class="mt-2 text-xs text-gray-500 font-medium flex items-center justify-between">
                        <span>{{ stats.total > 0 ? Math.round((stats.active / stats.total) * 100) : 0 }}% dari total Free Home</span>
                        <span class="text-emerald-600 font-bold">Aktif</span>
                    </div>
                </div>

                <!-- Card 3: Terpasang ONT -->
                <div class="bg-white rounded-2xl p-5 border border-amber-100 shadow-sm hover:border-amber-300 transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-blue-700 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                            Terpasang ONT
                        </span>
                        <span class="text-xs bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full font-bold">Modem Rumahan</span>
                    </div>
                    <div class="text-3xl font-black text-blue-700">
                        {{ stats.installed_ont || 0 }}
                    </div>
                    <div class="mt-2 text-xs text-gray-500 font-medium flex items-center justify-between">
                        <span>Modem di lokasi pelanggan</span>
                        <span class="text-blue-600 font-bold">Terpasang</span>
                    </div>
                </div>

                <!-- Card 4: Terhubung ODP -->
                <div class="bg-white rounded-2xl p-5 border border-amber-100 shadow-sm hover:border-amber-300 transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-700 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                            Terhubung ke ODP
                        </span>
                        <span class="text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-full font-bold">Distribusi</span>
                    </div>
                    <div class="text-3xl font-black text-indigo-700">
                        {{ stats.with_odp || 0 }}
                    </div>
                    <div class="mt-2 text-xs text-gray-500 font-medium flex items-center justify-between">
                        <span>Jalur kabel dropcore ODP</span>
                        <span class="text-indigo-600 font-bold">Fiber Optik</span>
                    </div>
                </div>

            </div>

            <!-- ── FILTER BAR ── -->
            <div v-can="'customers_filter_search'" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5 transition-all">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                    
                    <!-- Search Input -->
                    <div class="lg:col-span-5">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                            Pencarian Data
                        </label>
                        <div class="relative">
                            <input 
                                type="text" 
                                v-model="filterForm.search" 
                                @input="onSearchInput"
                                @keyup.enter="applyFilters"
                                placeholder="Cari nama, kode pelanggan, no HP, SN / model ONT..." 
                                class="block w-full text-xs rounded-xl border-gray-300 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 pl-8 pr-3 py-2 transition-colors"
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
                            class="block w-full text-xs rounded-xl border-gray-300 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 py-2 transition-colors cursor-pointer"
                        >
                            <option value="">Semua Wilayah</option>
                            <option v-for="a in areas" :key="a.id" :value="a.id">
                                {{ a.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Filter Status Pelanggan -->
                    <div class="lg:col-span-2">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1">
                            Status PSB
                        </label>
                        <select 
                            v-model="filterForm.status" 
                            @change="applyFilters"
                            class="block w-full text-xs rounded-xl border-gray-300 bg-gray-50/50 focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 py-2 transition-colors cursor-pointer"
                        >
                            <option value="">Semua Status</option>
                            <option value="active">Aktif</option>
                            <option value="installing">Proses Pasang</option>
                            <option value="survey">Survey</option>
                            <option value="booking">Booking</option>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="lg:col-span-2 flex items-center gap-2">
                        <button 
                            @click="applyFilters" 
                            type="button" 
                            class="flex-1 inline-flex items-center justify-center gap-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold py-2 px-3 rounded-xl transition shadow-sm cursor-pointer"
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

            <!-- ── TABEL DAFTAR PELANGGAN FREE HOME ── -->
            <div class="bg-white rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span>🏠</span> Daftar Pelanggan Free Home (Voucher Rumahan)
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Pelanggan internet menggunakan modem ONT rumahan tanpa akun RADIUS PPPoE dan bebas penagihan rutin.</p>
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
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Perangkat ONT &amp; ODP</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status Layanan</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Wilayah &amp; Alamat</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tahap PSB</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-for="c in customers.data" :key="c.id" class="hover:bg-gray-50/50 transition-colors">
                                <!-- Pelanggan Info -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="relative shrink-0">
                                            <div :class="[
                                                'w-10 h-10 rounded-2xl font-bold flex items-center justify-center text-sm shadow-xs border',
                                                isCustomerOnline(c) 
                                                    ? 'bg-emerald-100 text-emerald-800 border-emerald-300 ring-2 ring-emerald-400/60' 
                                                    : 'bg-amber-100 text-amber-800 border-amber-200'
                                            ]">
                                                🏠
                                            </div>
                                            <span v-if="isCustomerOnline(c)" class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-white ring-1 ring-emerald-300"></span>
                                        </div>
                                        <div>
                                            <div :class="isCustomerOnline(c) ? 'text-emerald-600' : 'text-gray-900'" class="text-sm font-bold flex items-center gap-1.5">
                                                <span v-if="isCustomerOnline(c)" class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                                                <span>{{ c.name }}</span>
                                                <span v-if="c.is_reseller" class="text-[9px] font-black uppercase px-1.5 py-0.2 bg-purple-100 text-purple-700 rounded">Reseller</span>
                                            </div>
                                            <div :class="isCustomerOnline(c) ? 'text-emerald-700/80 font-medium' : 'text-gray-500'" class="text-xs font-mono mt-0.5">
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

                                <!-- Perangkat ONT & ODP -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div v-if="c.ont">
                                        <div class="text-sm font-bold text-gray-900 flex items-center gap-1.5">
                                            <span>📟 {{ c.ont.model || 'ONT Standar' }}</span>
                                        </div>
                                        <div class="text-xs text-gray-500 font-mono mt-0.5">
                                            SN: <span class="font-bold text-gray-700">{{ c.ont.sn || '-' }}</span>
                                        </div>
                                        <div class="text-[11px] text-indigo-600 font-medium mt-1 flex items-center gap-1">
                                            <span>📦 ODP:</span>
                                            <span class="font-bold">{{ c.ont.odp?.name || 'Belum terhubung ODP' }}</span>
                                            <span v-if="c.ont.odp_port" class="text-gray-500 font-mono">(Port {{ c.ont.odp_port }})</span>
                                        </div>
                                    </div>
                                    <div v-else class="text-xs text-amber-600 italic flex items-center gap-1">
                                        <span>⚠️ Belum terpasang ONT</span>
                                    </div>
                                </td>

                                <!-- Status Layanan -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1 items-start">
                                        <span class="inline-flex items-center gap-1.5 text-xs font-black px-2.5 py-1 rounded-xl bg-amber-100 text-amber-800 border border-amber-300 shadow-xs">
                                            <span>🏠</span>
                                            <span>FREE HOME (Rp 0)</span>
                                        </span>
                                        <span class="text-[10px] text-amber-700 font-bold bg-amber-50 px-2 py-0.5 rounded-md">
                                            Voucher Rumahan • Non-PPPoE
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

                                <!-- Tahap PSB -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span 
                                        class="text-xs uppercase px-2.5 py-1 rounded-xl font-extrabold inline-flex items-center gap-1 shadow-xs"
                                        :class="{
                                            'bg-emerald-100 text-emerald-800 border border-emerald-300': c.status === 'active',
                                            'bg-blue-100 text-blue-800 border border-blue-300': c.status === 'installing',
                                            'bg-amber-100 text-amber-800 border border-amber-300': c.status === 'survey',
                                            'bg-purple-100 text-purple-800 border border-purple-300': c.status === 'booking',
                                            'bg-gray-100 text-gray-700 border border-gray-300': !['active','installing','survey','booking'].includes(c.status)
                                        }"
                                    >
                                        <span v-if="c.status === 'active'">⚡ Aktif</span>
                                        <span v-else-if="c.status === 'installing'">🛠️ Pasang ONT</span>
                                        <span v-else-if="c.status === 'survey'">🔍 Survey</span>
                                        <span v-else-if="c.status === 'booking'">📝 Booking</span>
                                        <span v-else>{{ c.status }}</span>
                                    </span>
                                    <div v-if="c.sales" class="text-[10px] text-gray-400 mt-1">
                                        Sales: <span class="text-gray-600 font-medium">{{ c.sales.name }}</span>
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
                                            class="inline-flex items-center gap-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 px-3 py-1.5 rounded-xl border border-emerald-300 transition text-xs font-bold cursor-pointer shadow-xs"
                                            title="Ubah status menjadi Pelanggan Berbayar"
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
                                        <div class="w-14 h-14 rounded-3xl bg-amber-50 text-amber-600 flex items-center justify-center text-3xl mb-3 shadow-inner">
                                            🏠
                                        </div>
                                        <p class="font-bold text-gray-800 text-base">Tidak ada pelanggan Free Home</p>
                                        <p class="text-xs text-gray-400 mt-1">Belum ada pelanggan Free Home yang cocok dengan filter atau pencarian Anda.</p>
                                        <button 
                                            v-if="hasActiveFilters" 
                                            @click="resetFilters" 
                                            class="mt-3 text-xs text-amber-600 font-bold hover:underline cursor-pointer"
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
                        Menampilkan <span class="font-bold">{{ customers.from || 0 }}</span> - <span class="font-bold">{{ customers.to || 0 }}</span> dari <span class="font-bold">{{ customers.total || 0 }}</span> pelanggan Free Home
                    </div>
                    <div class="flex flex-wrap gap-1">
                        <Link 
                            v-for="(link, i) in customers.links" 
                            :key="i" 
                            :href="link.url || '#'" 
                            v-html="link.label"
                            class="px-3 py-1.5 text-xs rounded-lg border transition-all"
                            :class="[
                                link.active ? 'bg-amber-600 text-white font-bold border-amber-600 shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-50 border-gray-200',
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
                <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg p-6 overflow-hidden animate-fade-in-up border border-gray-100">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-lg">
                                💳
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Ubah Menjadi Pelanggan Berbayar</h3>
                                <p class="text-xs text-gray-500">{{ selectedCustomer.name }} ({{ selectedCustomer.customer_code }})</p>
                            </div>
                        </div>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-lg cursor-pointer">✕</button>
                    </div>

                    <form @submit.prevent="submitSwitchStatus" class="space-y-4 pt-4">
                        <div class="bg-amber-50 border border-amber-200 p-3.5 rounded-2xl text-xs text-amber-900 leading-relaxed">
                            <p class="font-bold flex items-center gap-1.5">
                                <span>⚡</span> Transisi ke Langganan Berbayar
                            </p>
                            <p class="mt-1">
                                Pelanggan Free Home tidak memiliki paket langganan aktif. Silakan pilih <strong>Paket Internet</strong> dan tentukan <strong>Tarif Bulanan</strong> untuk memulai siklus penagihan invoice rutin.
                            </p>
                        </div>

                        <!-- Pilih Paket Internet -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Pilih Paket Internet <span class="text-rose-500">*</span>
                            </label>
                            <select 
                                v-model="switchForm.package_id" 
                                @change="onPackageChange"
                                required
                                class="w-full text-xs rounded-xl border-gray-300 p-2.5 focus:ring-emerald-500 focus:border-emerald-500 cursor-pointer"
                            >
                                <option value="" disabled>-- Pilih Paket Langganan --</option>
                                <option v-for="p in packages" :key="p.id" :value="p.id">
                                    {{ p.name }} ({{ p.speed_mbps }} Mbps) - Rp {{ formatRupiah(p.price) }}/bln
                                </option>
                            </select>
                        </div>

                        <!-- Tarif Bulanan -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Tarif Tagihan Bulanan (Rp) <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                v-model.number="switchForm.base_amount" 
                                min="0" 
                                required
                                placeholder="Nominal tagihan bulanan..."
                                class="w-full text-xs rounded-xl border-gray-300 p-2.5 focus:ring-emerald-500 focus:border-emerald-500 font-mono font-bold text-gray-800"
                            />
                            <p class="text-[11px] text-gray-500 mt-1">Tarif ini akan dijadikan dasar pembuatan invoice bulanan pelanggan.</p>
                        </div>

                        <!-- Catatan Pengalihan -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Catatan / Alasan Pengalihan</label>
                            <textarea 
                                v-model="switchForm.notes" 
                                rows="2" 
                                placeholder="Contoh: Pelanggan mengajukan upgrade ke paket langganan rumah 20 Mbps..."
                                class="w-full text-xs rounded-xl border-gray-300 p-2.5 focus:ring-emerald-500 focus:border-emerald-500"
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
                                :disabled="switchForm.processing || !switchForm.package_id"
                                class="px-5 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm transition cursor-pointer disabled:opacity-50"
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
    status: props.filters?.status || '',
});

const hasActiveFilters = computed(() => {
    return !!(filterForm.search || filterForm.area_id || filterForm.status);
});

let searchTimeout = null;
const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 450);
};

const applyFilters = () => {
    router.get(route('customers.free-home'), {
        search: filterForm.search || undefined,
        area_id: filterForm.area_id || undefined,
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
    filterForm.status = '';
    applyFilters();
};

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID').format(val || 0);
};

const isCustomerOnline = (c) => {
    return !!(c.ont?.pppoe_user && props.onlineUsernames?.includes(c.ont.pppoe_user));
};

// Modal switch status ke berbayar
const showModal = ref(false);
const selectedCustomer = ref(null);
const switchForm = useForm({
    service_status: 'berbayar',
    package_id: '',
    base_amount: 0,
    notes: '',
});

const onPackageChange = () => {
    const pkg = (props.packages || []).find(p => p.id === switchForm.package_id);
    if (pkg) {
        switchForm.base_amount = pkg.price || 0;
    }
};

const openSwitchModal = (customer) => {
    selectedCustomer.value = customer;
    switchForm.service_status = 'berbayar';
    switchForm.package_id = props.packages?.[0]?.id || '';
    switchForm.base_amount = props.packages?.[0]?.price || 0;
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
