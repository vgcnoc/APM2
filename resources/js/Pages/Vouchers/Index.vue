<template>
    <component :is="layoutComponent" title="Data Voucher" subtitle="Kelola dan generate voucher hotspot">
        <div v-if="layoutComponent === ClientAreaLayout" class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Data Voucher</h1>
            <p class="text-sm text-gray-500">Kelola dan lihat semua voucher Anda</p>
        </div>
        <!-- HEADER -->
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex gap-2 w-full sm:w-auto">
                <div class="relative flex-1 sm:w-64">
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Cari kode/username/reseller..." 
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                        @keyup.enter="doFilter"
                    >
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <select v-model="statusFilter" @change="doFilter" class="border-gray-300 rounded-lg text-sm">
                    <option value="">Semua Status (Aktif & Tersedia)</option>
                    <option value="available">Tersedia</option>
                    <option value="used">Digunakan</option>
                </select>
                <select v-if="layoutComponent === AppLayout" v-model="resellerFilter" @change="doFilter" class="border-gray-300 rounded-lg text-sm">
                    <option value="">Semua Pemilik / Reseller</option>
                    <option value="system">🏢 Kantor / System</option>
                    <option v-for="res in resellers" :key="res.id" :value="res.id">
                        👤 {{ res.customer?.name || ('Reseller #' + res.id) }}
                    </option>
                </select>
            </div>
            
            <div class="flex gap-2">
                <button v-if="selectedVouchers.length > 0" @click="printSelected" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700 shadow-sm flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print ({{ selectedVouchers.length }})
                </button>
                <button v-if="selectedVouchers.length > 0" @click="bulkDelete" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-700 shadow-sm flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Hapus ({{ selectedVouchers.length }})
                </button>
                <button @click="openGenerateModal" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Generate Voucher
                </button>
            </div>
        </div>

        <!-- TABLE -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-10">
                                <input type="checkbox" @change="toggleSelectAll" :checked="isAllSelected" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kode / User</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Password</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Profil</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Reseller</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Terpakai Sejak</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Aktif Sampai</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Penggunaan</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="voucher in vouchers.data" :key="voucher.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <input type="checkbox" v-model="selectedVouchers" :value="voucher.id" class="rounded border-gray-300 text-indigo-600 shadow-sm">
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900 font-mono">{{ voucher.code }}</div>
                                <div class="text-xs text-gray-500" v-if="voucher.username !== voucher.code">User: {{ voucher.username }}</div>
                                <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                                    <span v-if="voucher.is_free" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        🎁 Gratis
                                    </span>
                                    <span v-else class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                        💰 Berbayar
                                    </span>
                                    <span v-if="voucher.payment_method === 'cash'" class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-200" title="Bayar tunai di kantor / non-saldo">
                                        💵 Tunai/Kantor
                                    </span>
                                    <span v-else-if="voucher.payment_method === 'balance'" class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-50 text-purple-700 border border-purple-200" title="Potong saldo reseller">
                                        💳 Saldo
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-mono">
                                {{ voucher.password }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <div class="font-bold text-gray-900">{{ voucher.profile?.name }}</div>
                                <div v-if="voucher.profile?.duration" class="text-xs text-gray-500 font-medium">{{ voucher.profile.duration }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div v-if="voucher.reseller?.customer?.name" class="flex flex-col items-start gap-1">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-2xs">
                                        <svg class="w-3.5 h-3.5 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <span class="font-bold">{{ voucher.reseller.customer.name }}</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span v-if="voucher.payment_method === 'cash'" class="text-[10px] font-medium text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200" title="Bayar Tunai di Kantor">
                                            💵 Tunai (Kantor)
                                        </span>
                                        <span v-else-if="voucher.payment_method === 'balance'" class="text-[10px] font-medium text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200" title="Potong Saldo Reseller">
                                            💳 Saldo Reseller
                                        </span>
                                    </div>
                                </div>
                                <div v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    <span>Kantor / System</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="voucher.status === 'available'" class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded">TERSEDIA</span>
                                <span v-else-if="voucher.status === 'used'" class="px-2 py-1 bg-emerald-100 text-emerald-700 text-xs font-bold rounded">DIGUNAKAN</span>
                                <span v-else class="px-2 py-1 bg-red-100 text-red-700 text-xs font-bold rounded">EXPIRED</span>

                                <div class="mt-2 flex flex-col gap-1 items-start">
                                    <span v-if="!voucher.is_active" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[10px] font-bold bg-rose-100 text-rose-700 uppercase tracking-widest">
                                        Nonaktif
                                    </span>
                                    <span v-else-if="onlineUsernames.includes(voucher.username)" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-semibold bg-emerald-100 text-emerald-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Aktif
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-semibold bg-rose-50 text-rose-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Offline
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <div v-if="usageStats[voucher.username]?.first_login">
                                    {{ new Date(usageStats[voucher.username].first_login).toLocaleString('id-ID', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                                </div>
                                <div v-else class="text-gray-400">-</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <div v-if="usageStats[voucher.username]?.first_login && voucher.profile?.duration">
                                    {{ calculateExpiration(usageStats[voucher.username].first_login, voucher.profile.duration) }}
                                </div>
                                <div v-else class="text-gray-400">-</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <div v-if="usageStats[voucher.username]?.total_time !== undefined">
                                    <span :class="usageStats[voucher.username].total_time > 0 ? 'text-indigo-600 font-bold' : ''">
                                        {{ formatSeconds(usageStats[voucher.username].total_time) }} 
                                    </span>
                                    <span class="text-gray-400 text-xs ml-1" v-if="voucher.profile?.duration">
                                        / {{ voucher.profile.duration }}
                                    </span>
                                </div>
                                <div v-else class="text-gray-400">-</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-3">
                                    <button 
                                        @click="toggleStatus(voucher)" 
                                        :disabled="toggling === voucher.id"
                                        :class="voucher.is_active ? 'text-orange-600 hover:text-orange-900' : 'text-emerald-600 hover:text-emerald-900'"
                                        class="font-semibold transition-colors disabled:opacity-50"
                                    >
                                        {{ voucher.is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                    <button @click="deleteVoucher(voucher.id)" class="text-red-600 hover:text-red-900 font-semibold">Hapus</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="vouchers.data.length === 0">
                            <td colspan="10" class="px-6 py-12 text-center">
                                <p class="text-gray-500 text-base font-medium">Belum ada data voucher.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-200 flex justify-center gap-2" v-if="vouchers.links && vouchers.links.length > 3">
                <template v-for="(link, idx) in vouchers.links" :key="idx">
                    <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-3 py-1 border rounded text-sm" :class="link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"></Link>
                    <span v-else v-html="link.label" class="px-3 py-1 border rounded bg-gray-100 text-gray-400 border-gray-300 cursor-not-allowed text-sm"></span>
                </template>
            </div>
        </div>

        <!-- MODAL GENERATE VOUCHER (MODERN DUAL-TAB) -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 overflow-y-auto">
                <div class="relative bg-white border border-gray-200 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[92vh] flex flex-col overflow-hidden animate-fade-in-up">
                    
                    <!-- MODAL HEADER -->
                    <div class="px-6 py-4 bg-gradient-to-r from-indigo-900 via-indigo-800 to-slate-900 text-white flex justify-between items-center shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center border border-white/20 text-indigo-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-base font-bold tracking-tight text-white flex items-center gap-2">
                                    Generator Voucher Hotspot
                                    <span class="px-2 py-0.5 text-[10px] font-semibold bg-indigo-500/30 text-indigo-200 rounded-full border border-indigo-400/30">Mikrotik / RADIUS</span>
                                </h2>
                                <p class="text-xs text-indigo-200/80">Buat voucher kustom satuan atau cetak voucher massal otomatis</p>
                            </div>
                        </div>
                        <button @click="closeModal" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-white/80 hover:text-white flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- TAB SELECTOR -->
                    <div class="bg-gray-50 border-b border-gray-200 px-6 pt-3 flex gap-2 shrink-0">
                        <button 
                            type="button" 
                            @click="activeTab = 'single'"
                            :class="activeTab === 'single' ? 'bg-white text-indigo-600 border-indigo-600 shadow-sm font-bold' : 'text-gray-500 hover:text-gray-700 border-transparent font-medium'"
                            class="px-4 py-2.5 text-xs rounded-t-xl border-b-2 flex items-center gap-2 transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span>Single Voucher (Satuan)</span>
                            <span class="px-1.5 py-0.2 bg-indigo-100 text-indigo-700 text-[10px] rounded-full">Kustom</span>
                        </button>
                        <button 
                            type="button" 
                            @click="activeTab = 'bulk'"
                            :class="activeTab === 'bulk' ? 'bg-white text-indigo-600 border-indigo-600 shadow-sm font-bold' : 'text-gray-500 hover:text-gray-700 border-transparent font-medium'"
                            class="px-4 py-2.5 text-xs rounded-t-xl border-b-2 flex items-center gap-2 transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            <span>Generate Massal (Batch)</span>
                            <span class="px-1.5 py-0.2 bg-gray-100 text-gray-600 text-[10px] rounded-full">Otomatis</span>
                        </button>
                    </div>

                    <!-- MODAL BODY -->
                    <div class="p-6 overflow-y-auto space-y-5">
                        
                        <!-- ================= TAB 1: SINGLE VOUCHER ================= -->
                        <form v-if="activeTab === 'single'" @submit.prevent="submitSingleForm" class="space-y-5">
                            
                            <!-- 1. Pilih Profil Paket -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Pilih Profil Paket Hotspot <span class="text-red-500">*</span>
                                </label>
                                <select v-model="singleForm.voucher_profile_id" class="w-full border-gray-300 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500 transition-all" required>
                                    <option value="" disabled>-- Pilih Profil Paket --</option>
                                    <option v-for="prof in sellableProfiles" :key="prof.id" :value="prof.id">
                                        {{ prof.name }} • Durasi: {{ prof.duration || '-' }} • {{ isReseller ? 'Harga Reseller: Rp' + Number((prof.fee_reseller && prof.fee_reseller > 0) ? prof.fee_reseller : prof.price).toLocaleString('id-ID') : 'Tarif: Rp' + Number(prof.price).toLocaleString('id-ID') }}
                                    </option>
                                </select>
                            </div>

                            <!-- 2. Jenis Voucher (Berbayar vs Gratis) -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Jenis Tarif Voucher <span class="text-red-500">*</span>
                                </label>
                                <div :class="isReseller ? 'grid grid-cols-1' : 'grid grid-cols-2 gap-3'">
                                    <label 
                                        :class="singleForm.cost_type === 'paid' ? 'border-indigo-500 bg-indigo-50/50 ring-2 ring-indigo-500/20' : 'border-gray-200 bg-white hover:bg-gray-50'"
                                        class="border rounded-xl p-3 cursor-pointer flex items-center gap-3 transition-all"
                                    >
                                        <input type="radio" value="paid" v-model="singleForm.cost_type" class="text-indigo-600 focus:ring-indigo-500">
                                        <div>
                                            <div class="text-xs font-bold text-gray-900 flex items-center gap-1.5">
                                                <span>💰 Harus Bayar (Berbayar)</span>
                                            </div>
                                            <p class="text-[11px] text-gray-500 mt-0.5">Sesuai tarif profil</p>
                                        </div>
                                    </label>

                                    <!-- KOTAK MERAH 1: Hilangkan untuk Reseller -->
                                    <label 
                                        v-if="!isReseller"
                                        :class="singleForm.cost_type === 'free' ? 'border-emerald-500 bg-emerald-50/50 ring-2 ring-emerald-500/20' : 'border-gray-200 bg-white hover:bg-gray-50'"
                                        class="border rounded-xl p-3 cursor-pointer flex items-center gap-3 transition-all"
                                    >
                                        <input type="radio" value="free" v-model="singleForm.cost_type" class="text-emerald-600 focus:ring-emerald-500">
                                        <div>
                                            <div class="text-xs font-bold text-gray-900 flex items-center gap-1.5">
                                                <span>🎁 Voucher Gratis (Free)</span>
                                            </div>
                                            <p class="text-[11px] text-gray-500 mt-0.5">Rp 0 / Bebas Biaya</p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- 3. Tipe Kredensial Login -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Format Kredensial Login <span class="text-red-500">*</span>
                                </label>
                                <div class="grid grid-cols-2 gap-3">
                                    <button 
                                        type="button" 
                                        @click="singleForm.login_type = 'username_only'"
                                        :class="singleForm.login_type === 'username_only' ? 'bg-indigo-600 text-white font-bold shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 font-medium'"
                                        class="px-3 py-2.5 rounded-xl text-xs flex items-center justify-center gap-2 transition-all"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        <span>Hanya Username</span>
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="singleForm.login_type = 'username_password'"
                                        :class="singleForm.login_type === 'username_password' ? 'bg-indigo-600 text-white font-bold shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 font-medium'"
                                        class="px-3 py-2.5 rounded-xl text-xs flex items-center justify-center gap-2 transition-all"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                        <span>Username & Password</span>
                                    </button>
                                </div>
                                <p class="text-[11px] text-gray-400 mt-1" v-if="singleForm.login_type === 'username_only'">
                                    ℹ️ Mode Satu Kode: Pengguna login cukup mengetik Username (Password otomatis sama dengan Username).
                                </p>
                            </div>

                            <!-- 4. Input Username & Password (Ketik Manual / Tombol Acak) -->
                            <div class="bg-gray-50/70 p-4 rounded-xl border border-gray-200 space-y-4">
                                <!-- Username -->
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="block text-xs font-bold text-gray-700">Username / Kode Login <span class="text-red-500">*</span></label>
                                        <button 
                                            type="button" 
                                            @click="generateRandomUsername"
                                            class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1 transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            Acak Username
                                        </button>
                                    </div>
                                    <div class="relative">
                                        <input 
                                            v-model="singleForm.username" 
                                            type="text" 
                                            placeholder="Ketik username manual (contoh: user01 / KODEX)" 
                                            class="w-full font-mono text-sm border-gray-300 rounded-xl uppercase pr-10 focus:ring-indigo-500 focus:border-indigo-500" 
                                            required
                                        />
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        </div>
                                    </div>
                                    <div v-if="singleForm.errors.username" class="text-red-500 text-xs mt-1">{{ singleForm.errors.username }}</div>
                                </div>

                                <!-- Password (hanya jika mode username_password) -->
                                <div v-if="singleForm.login_type === 'username_password'">
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="block text-xs font-bold text-gray-700">Password <span class="text-red-500">*</span></label>
                                        <button 
                                            type="button" 
                                            @click="generateRandomPassword"
                                            class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1 transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            Acak Password
                                        </button>
                                    </div>
                                    <div class="relative">
                                        <input 
                                            v-model="singleForm.password" 
                                            :type="showSinglePassword ? 'text' : 'password'" 
                                            placeholder="Ketik password manual..." 
                                            class="w-full font-mono text-sm border-gray-300 rounded-xl pr-10 focus:ring-indigo-500 focus:border-indigo-500" 
                                            required
                                        />
                                        <button 
                                            type="button" 
                                            @click="showSinglePassword = !showSinglePassword"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                                        >
                                            <svg v-if="!showSinglePassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                        </button>
                                    </div>
                                    <div v-if="singleForm.errors.password" class="text-red-500 text-xs mt-1">{{ singleForm.errors.password }}</div>
                                </div>
                            </div>

                            <!-- 5. Kepemilikan Reseller & Opsi Pembayaran (KOTAK MERAH 2: Khusus Admin/Kantor) -->
                            <div v-if="!isReseller" class="bg-indigo-50/40 p-4 rounded-xl border border-indigo-100 space-y-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">
                                        Peruntukan Reseller (Opsional)
                                    </label>
                                    <select v-model="singleForm.reseller_id" class="w-full border-gray-300 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">-- System / Kantor (Bukan Reseller) --</option>
                                        <option v-for="reseller in resellers" :key="reseller.id" :value="reseller.id">
                                            {{ reseller.customer?.name || ('Reseller #' + reseller.id) }} (Saldo: Rp{{ Number(reseller.balance).toLocaleString('id-ID') }})
                                        </option>
                                    </select>
                                    <div v-if="singleForm.errors.reseller_id" class="text-red-500 text-xs mt-1">{{ singleForm.errors.reseller_id }}</div>
                                </div>

                                <!-- Opsi Pembayaran Reseller jika Berbayar -->
                                <div v-if="singleForm.reseller_id && singleForm.cost_type === 'paid'" class="pt-2 border-t border-indigo-100">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                        Metode Pembayaran Reseller:
                                    </label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label class="flex items-center gap-2 p-2 bg-white rounded-lg border border-indigo-200 cursor-pointer text-xs">
                                            <input type="radio" value="balance" v-model="singleForm.reseller_payment" class="text-indigo-600 focus:ring-indigo-500">
                                            <div>
                                                <span class="font-bold text-gray-800">💳 Potong Saldo</span>
                                                <p class="text-[10px] text-gray-500">Otomatis kurangi saldo</p>
                                            </div>
                                        </label>
                                        <label class="flex items-center gap-2 p-2 bg-white rounded-lg border border-indigo-200 cursor-pointer text-xs">
                                            <input type="radio" value="cash" v-model="singleForm.reseller_payment" class="text-indigo-600 focus:ring-indigo-500">
                                            <div>
                                                <span class="font-bold text-emerald-700">💵 Bayar Tunai / Kantor</span>
                                                <p class="text-[10px] text-gray-500">Tanpa potong saldo</p>
                                            </div>
                                        </label>
                                    </div>
                                    <p v-if="singleForm.reseller_payment === 'cash'" class="text-[11px] text-emerald-700 font-medium mt-1.5 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Voucher dicetak atas nama reseller tanpa memotong saldo akunnya (cocok untuk reseller yang bayar cash di kantor).
                                    </p>
                                </div>
                            </div>
                            <!-- Info Akun Reseller (Tampil Khusus Login Reseller) -->
                            <div v-else class="bg-indigo-50/60 border border-indigo-200/80 rounded-xl p-3.5 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                                        💳
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-gray-800">Akun: {{ currentReseller?.customer?.name || $page.props.auth.user.name }}</p>
                                        <p class="text-[11px] text-indigo-700 font-medium">Biaya voucher otomatis memotong Saldo Reseller Anda</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] uppercase font-bold text-gray-500">Saldo Anda</span>
                                    <p class="text-sm font-extrabold text-indigo-700">Rp{{ Number(currentReseller?.balance || 0).toLocaleString('id-ID') }}</p>
                                </div>
                            </div>

                            <!-- LIVE PREVIEW VOUCHER CARD -->
                            <div v-if="singleForm.username" class="p-3.5 bg-gradient-to-br from-slate-900 to-indigo-950 rounded-xl text-white shadow-md border border-indigo-800/50">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                        <span class="text-[10px] uppercase font-bold tracking-wider text-indigo-300">Live Preview Voucher</span>
                                    </div>
                                    <span v-if="singleForm.cost_type === 'free'" class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-500 text-white shadow-sm">
                                        GRATIS (Rp 0)
                                    </span>
                                    <span v-else class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-indigo-500 text-white shadow-sm">
                                        Rp {{ getSelectedProfilePrice(singleForm.voucher_profile_id) }}
                                    </span>
                                </div>
                                <div class="bg-white/10 backdrop-blur-sm p-3 rounded-lg border border-white/10 flex justify-between items-center">
                                    <div>
                                        <p class="text-xs text-indigo-200">Username: <span class="font-mono font-bold text-white text-sm">{{ singleForm.username }}</span></p>
                                        <p v-if="singleForm.login_type === 'username_password'" class="text-xs text-indigo-200">
                                            Password: <span class="font-mono font-bold text-amber-300">{{ singleForm.password || '-' }}</span>
                                        </p>
                                        <p v-else class="text-[11px] text-indigo-300/80 italic">User = Password</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs font-semibold text-white">{{ getSelectedProfileName(singleForm.voucher_profile_id) }}</p>
                                        <p class="text-[10px] text-indigo-300">{{ getSelectedProfileDuration(singleForm.voucher_profile_id) }}</p>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="hidden" id="submitSingleBtn"></button>
                        </form>

                        <!-- ================= TAB 2: BULK / MASSAL ================= -->
                        <form v-else @submit.prevent="submitBulkForm" class="space-y-4">
                            <!-- 1. Pilih Profil -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Pilih Profil <span class="text-red-500">*</span></label>
                                <select v-model="bulkForm.voucher_profile_id" class="w-full border-gray-300 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                    <option value="" disabled>-- Pilih Profil --</option>
                                    <option v-for="prof in sellableProfiles" :key="prof.id" :value="prof.id">
                                        {{ prof.name }} ({{ isReseller ? 'Harga Reseller: Rp' + Number((prof.fee_reseller && prof.fee_reseller > 0) ? prof.fee_reseller : prof.price).toLocaleString('id-ID') : 'Rp' + Number(prof.price).toLocaleString('id-ID') }})
                                    </option>
                                </select>
                            </div>
                            
                            <!-- 2. Jenis Biaya Bulk -->
                            <div :class="isReseller ? 'grid grid-cols-1' : 'grid grid-cols-2 gap-3'">
                                <label 
                                    :class="bulkForm.cost_type === 'paid' ? 'border-indigo-500 bg-indigo-50/50 ring-2 ring-indigo-500/20' : 'border-gray-200 bg-white hover:bg-gray-50'"
                                    class="border rounded-xl p-2.5 cursor-pointer flex items-center gap-2.5 transition-all text-xs"
                                >
                                    <input type="radio" value="paid" v-model="bulkForm.cost_type" class="text-indigo-600 focus:ring-indigo-500">
                                    <span class="font-bold text-gray-900">💰 Harus Bayar (Normal)</span>
                                </label>
                                <label 
                                    v-if="!isReseller"
                                    :class="bulkForm.cost_type === 'free' ? 'border-emerald-500 bg-emerald-50/50 ring-2 ring-emerald-500/20' : 'border-gray-200 bg-white hover:bg-gray-50'"
                                    class="border rounded-xl p-2.5 cursor-pointer flex items-center gap-2.5 transition-all text-xs"
                                >
                                    <input type="radio" value="free" v-model="bulkForm.cost_type" class="text-emerald-600 focus:ring-emerald-500">
                                    <span class="font-bold text-gray-900">🎁 Voucher Gratis (Rp 0)</span>
                                </label>
                            </div>

                            <!-- 3. Jumlah Generate & Quick Pick -->
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Jumlah Generate <span class="text-red-500">*</span></label>
                                    <div class="flex gap-1.5">
                                        <button type="button" @click="bulkForm.amount = 10" class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">10</button>
                                        <button type="button" @click="bulkForm.amount = 25" class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">25</button>
                                        <button type="button" @click="bulkForm.amount = 50" class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">50</button>
                                        <button type="button" @click="bulkForm.amount = 100" class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">100</button>
                                    </div>
                                </div>
                                <input v-model="bulkForm.amount" type="number" min="1" max="500" class="w-full border-gray-300 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                            </div>

                            <!-- 4. Panjang Karakter & Prefix -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Panjang Karakter <span class="text-red-500">*</span></label>
                                    <input v-model="bulkForm.length" type="number" min="4" max="12" class="w-full border-gray-300 rounded-xl text-sm" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Prefix (Opsional)</label>
                                    <input v-model="bulkForm.prefix" type="text" maxlength="4" placeholder="Contoh: VC" class="w-full border-gray-300 rounded-xl text-sm uppercase">
                                </div>
                            </div>

                            <!-- 5. Kombinasi Username & Tipe Login -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Kombinasi <span class="text-red-500">*</span></label>
                                    <select v-model="bulkForm.combination" class="w-full border-gray-300 rounded-xl text-sm" required>
                                        <option value="alphanumeric">Huruf & Angka (A1B2C)</option>
                                        <option value="numeric">Hanya Angka (12345)</option>
                                        <option value="alpha">Hanya Huruf (ABCDE)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tipe Login <span class="text-red-500">*</span></label>
                                    <select v-model="bulkForm.type" class="w-full border-gray-300 rounded-xl text-sm" required>
                                        <option value="vc">Satu Kode (User = Password)</option>
                                        <option value="up">Username & Password Berbeda</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- 6. Reseller Assignment -->
                            <div v-if="!isReseller" class="bg-gray-50 p-3.5 rounded-xl border border-gray-200 space-y-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Generate Untuk Reseller (Opsional)</label>
                                    <select v-model="bulkForm.reseller_id" class="w-full border-gray-300 rounded-xl text-sm">
                                        <option value="">-- System (Tanpa Reseller) --</option>
                                        <option v-for="reseller in resellers" :key="reseller.id" :value="reseller.id">
                                            {{ reseller.customer?.name || ('Reseller #' + reseller.id) }} (Saldo: Rp{{ Number(reseller.balance).toLocaleString('id-ID') }})
                                        </option>
                                    </select>
                                    <div v-if="bulkForm.errors.reseller_id" class="text-red-500 text-xs mt-1">{{ bulkForm.errors.reseller_id }}</div>
                                </div>

                                <div v-if="bulkForm.reseller_id && bulkForm.cost_type === 'paid'" class="pt-2 border-t border-gray-200">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Metode Pembayaran Reseller:</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label class="flex items-center gap-2 p-2 bg-white rounded-lg border border-gray-200 cursor-pointer text-xs">
                                            <input type="radio" value="balance" v-model="bulkForm.reseller_payment" class="text-indigo-600 focus:ring-indigo-500">
                                            <div>
                                                <span class="font-bold text-gray-800">💳 Potong Saldo</span>
                                            </div>
                                        </label>
                                        <label class="flex items-center gap-2 p-2 bg-white rounded-lg border border-gray-200 cursor-pointer text-xs">
                                            <input type="radio" value="cash" v-model="bulkForm.reseller_payment" class="text-indigo-600 focus:ring-indigo-500">
                                            <div>
                                                <span class="font-bold text-emerald-700">💵 Bayar Tunai di Kantor</span>
                                            </div>
                                        </label>
                                    </div>
                                    <p v-if="bulkForm.reseller_payment === 'cash'" class="text-[11px] text-emerald-700 font-medium mt-1">
                                        ✓ Saldo reseller tidak akan dipotong. Voucher tetap terhubung ke akun reseller ini.
                                    </p>
                                </div>
                            </div>
                            <!-- Info Akun Reseller jika Reseller -->
                            <div v-else class="bg-indigo-50/60 border border-indigo-200/80 rounded-xl p-3.5 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                                        💳
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-gray-800">Akun: {{ currentReseller?.customer?.name || $page.props.auth.user.name }}</p>
                                        <p class="text-[11px] text-indigo-700 font-medium">Pembayaran batch otomatis memotong Saldo Reseller Anda</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] uppercase font-bold text-gray-500">Saldo Anda</span>
                                    <p class="text-sm font-extrabold text-indigo-700">Rp{{ Number(currentReseller?.balance || 0).toLocaleString('id-ID') }}</p>
                                </div>
                            </div>
                            
                            <button type="submit" class="hidden" id="submitBulkBtn"></button>
                        </form>

                    </div>

                    <!-- MODAL FOOTER -->
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-between items-center shrink-0">
                        <button type="button" @click="closeModal" class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-bold text-gray-700 hover:bg-gray-100 transition-colors">
                            Batal
                        </button>
                        <div class="flex items-center gap-2">
                            <button 
                                v-if="activeTab === 'single'" 
                                type="button" 
                                @click="$el.querySelector('#submitSingleBtn').click()" 
                                :disabled="singleForm.processing || !singleForm.username || !singleForm.voucher_profile_id" 
                                class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-200 hover:shadow-lg transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg v-if="singleForm.processing" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ singleForm.processing ? 'Menyimpan...' : 'Buat 1 Voucher' }}</span>
                            </button>

                            <button 
                                v-else 
                                type="button" 
                                @click="$el.querySelector('#submitBulkBtn').click()" 
                                :disabled="bulkForm.processing || !bulkForm.voucher_profile_id" 
                                class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-200 hover:shadow-lg transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg v-if="bulkForm.processing" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ bulkForm.processing ? 'Generating...' : `Generate ${bulkForm.amount} Voucher` }}</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </Teleport>

    </component>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ClientAreaLayout from '@/Layouts/ClientAreaLayout.vue';
import Swal from 'sweetalert2';

const page = usePage();
const layoutComponent = computed(() => {
    return page.props.auth?.user?.role === 'reseller' || page.props.auth?.user?.role === 'customer' 
        ? ClientAreaLayout 
        : AppLayout;
});

const props = defineProps({
    vouchers: Object,
    profiles: Array,
    resellers: Array,
    currentReseller: Object,
    filters: Object,
    onlineUsernames: Array,
    usageStats: Object,
});

const isReseller = computed(() => {
    return page.props.auth?.user?.role === 'reseller' || !!props.currentReseller;
});

const sellableProfiles = computed(() => {
    return (props.profiles || []).filter(p => !p.is_customer_free);
});

const formatSeconds = (seconds) => {
    if (!seconds) return '0 mnt';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    if (h > 0) return `${h} jam ${m} mnt`;
    return `${m} mnt`;
};

const parseDurationToSeconds = (duration) => {
    if (!duration) return 0;
    const str = duration.toString().toLowerCase().trim();
    if (/^\d+$/.test(str)) return parseInt(str);
    
    let seconds = 0;
    const regex = /(\d+)\s*([wdhms])/g;
    let match;
    const units = { w: 604800, d: 86400, h: 3600, m: 60, s: 1 };
    
    while ((match = regex.exec(str)) !== null) {
        seconds += parseInt(match[1]) * units[match[2]];
    }
    return seconds;
};

const calculateExpiration = (firstLoginIso, durationStr) => {
    if (!firstLoginIso || !durationStr) return '-';
    const seconds = parseDurationToSeconds(durationStr);
    if (!seconds) return '-';
    
    const date = new Date(firstLoginIso);
    date.setSeconds(date.getSeconds() + seconds);
    
    return date.toLocaleString('id-ID', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const resellerFilter = ref(props.filters.reseller_id || '');
const showModal = ref(false);
const activeTab = ref('single');
const showSinglePassword = ref(false);
const selectedVouchers = ref([]);
const toggling = ref(null);

const toggleStatus = (voucher) => {
    const action = voucher.is_active ? 'Nonaktifkan' : 'Aktifkan';
    if (!confirm(`${action} voucher ${voucher.code}?`)) return;
    toggling.value = voucher.id;
    router.post(`/vouchers/${voucher.id}/toggle-status`, {}, {
        preserveScroll: true,
        onFinish: () => (toggling.value = null),
    });
};

// Form Single Voucher
const singleForm = useForm({
    mode: 'single',
    voucher_profile_id: '',
    login_type: 'username_only', // 'username_only' atau 'username_password'
    username: '',
    password: '',
    cost_type: 'paid', // 'paid' atau 'free'
    reseller_id: '',
    reseller_payment: 'balance', // 'balance' atau 'cash'
});

// Form Bulk Voucher
const bulkForm = useForm({
    mode: 'bulk',
    voucher_profile_id: '',
    amount: 10,
    length: 6,
    prefix: '',
    type: 'vc',
    combination: 'alphanumeric',
    cost_type: 'paid', // 'paid' atau 'free'
    reseller_id: '',
    reseller_payment: 'balance', // 'balance' atau 'cash'
});

// Helper Generate Random Strings
function getRandomChars(len = 6, numericOnly = false) {
    const chars = numericOnly ? '0123456789' : 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let res = '';
    for (let i = 0; i < len; i++) {
        res += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return res;
}

function generateRandomUsername() {
    singleForm.username = 'VC' + getRandomChars(5);
}

function generateRandomPassword() {
    singleForm.password = getRandomChars(6);
}

// Helpers Info Profil
function getSelectedProfilePrice(profileId) {
    if (!profileId) return '0';
    const prof = props.profiles.find(p => p.id === profileId);
    if (!prof) return '0';
    if (isReseller.value) {
        const cost = (prof.fee_reseller && prof.fee_reseller > 0) ? prof.fee_reseller : prof.price;
        return Number(cost).toLocaleString('id-ID');
    }
    return Number(prof.price).toLocaleString('id-ID');
}

function getSelectedProfileName(profileId) {
    if (!profileId) return 'Pilih Profil';
    const prof = props.profiles.find(p => p.id === profileId);
    return prof ? prof.name : 'Pilih Profil';
}

function getSelectedProfileDuration(profileId) {
    if (!profileId) return '';
    const prof = props.profiles.find(p => p.id === profileId);
    return prof?.duration ? `Durasi: ${prof.duration}` : '';
}

const doFilter = () => {
    router.get(route('vouchers.index'), { 
        search: search.value, 
        status: statusFilter.value,
        reseller_id: resellerFilter.value 
    }, { preserveState: true, replace: true });
};

const openGenerateModal = () => {
    singleForm.reset();
    singleForm.clearErrors();
    singleForm.voucher_profile_id = sellableProfiles.value[0]?.id || props.profiles[0]?.id || '';
    if (isReseller.value) {
        singleForm.cost_type = 'paid';
        singleForm.reseller_id = props.currentReseller?.id || '';
        singleForm.reseller_payment = 'balance';
    }
    generateRandomUsername();
    
    bulkForm.reset();
    bulkForm.clearErrors();
    bulkForm.voucher_profile_id = sellableProfiles.value[0]?.id || props.profiles[0]?.id || '';
    if (isReseller.value) {
        bulkForm.cost_type = 'paid';
        bulkForm.reseller_id = props.currentReseller?.id || '';
        bulkForm.reseller_payment = 'balance';
    }

    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const submitSingleForm = () => {
    if (isReseller.value) {
        singleForm.cost_type = 'paid';
        singleForm.reseller_id = props.currentReseller?.id || '';
        singleForm.reseller_payment = 'balance';
    }
    singleForm.post(route('vouchers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Dibuat',
                text: `Single Voucher ${singleForm.username} berhasil dibuat (${singleForm.cost_type === 'free' ? 'Gratis' : 'Berbayar'}).`,
            });
        }
    });
};

const submitBulkForm = () => {
    if (isReseller.value) {
        bulkForm.cost_type = 'paid';
        bulkForm.reseller_id = props.currentReseller?.id || '';
        bulkForm.reseller_payment = 'balance';
    }
    bulkForm.post(route('vouchers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Generate',
                text: `${bulkForm.amount} Voucher berhasil digenerate (${bulkForm.cost_type === 'free' ? 'Gratis' : 'Berbayar'}).`,
            });
        }
    });
};

const deleteVoucher = (id) => {
    Swal.fire({
        title: 'Hapus Voucher?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('vouchers.destroy.post', id), {}, {
                onSuccess: () => {
                    Swal.fire('Terhapus!', 'Voucher berhasil dihapus.', 'success');
                }
            });
        }
    });
};

const bulkDelete = () => {
    Swal.fire({
        title: 'Hapus ' + selectedVouchers.value.length + ' Voucher?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus Semua!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('vouchers.bulk-destroy'), { ids: selectedVouchers.value }, {
                onSuccess: () => {
                    selectedVouchers.value = [];
                    Swal.fire('Terhapus!', 'Voucher berhasil dihapus.', 'success');
                }
            });
        }
    });
};

const printSelected = () => {
    if (selectedVouchers.value.length === 0) return;
    
    // Convert array of IDs to query string parameters: ids[]=1&ids[]=2
    const queryParams = new URLSearchParams();
    selectedVouchers.value.forEach(id => {
        queryParams.append('ids[]', id);
    });
    
    const url = route('vouchers.print') + '?' + queryParams.toString();
    window.open(url, '_blank');
};

const isAllSelected = computed(() => {
    return props.vouchers.data.length > 0 && selectedVouchers.value.length === props.vouchers.data.length;
});

const toggleSelectAll = (e) => {
    if (e.target.checked) {
        selectedVouchers.value = props.vouchers.data.map(v => v.id);
    } else {
        selectedVouchers.value = [];
    }
};
</script>
