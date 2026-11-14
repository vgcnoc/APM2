<template>
    <AppLayout title="Privasi & Target Omset" subtitle="Atur penyamaran data pelanggan, kalkulator target omset, dan batasi tampilan laporan pajak">
        <div class="max-w-[1600px] w-full mx-auto space-y-6 font-sans">
            <!-- Flash notification -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-2xl text-xs font-bold flex items-center gap-3 shadow-xs">
                <span class="text-lg">✅</span>
                <span>{{ $page.props.flash.success }}</span>
            </div>

            <!-- Header Banner Status -->
            <div class="rounded-3xl p-6 sm:p-8 border shadow-sm relative overflow-hidden transition-all duration-300"
                :class="form.enabled 
                    ? 'bg-gradient-to-br from-indigo-900 via-indigo-800 to-purple-900 text-white border-indigo-700' 
                    : 'bg-white text-gray-900 border-gray-100'">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-2">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold"
                            :class="form.enabled ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-400/30' : 'bg-gray-100 text-gray-600'">
                            <span class="w-2 h-2 rounded-full" :class="form.enabled ? 'bg-emerald-400 animate-pulse' : 'bg-gray-400'"></span>
                            <span>{{ form.enabled ? 'FITUR PENYAMARAN DATA AKTIF' : 'FITUR NONAKTIF (DATA RIIL 100%)' }}</span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-black tracking-tight" :class="form.enabled ? 'text-white' : 'text-gray-900'">
                            {{ form.enabled ? 'Mode Privasi & Penyamaran Omset Aktif' : 'Penyamaran Data & Kalkulator Target Omset' }}
                        </h2>
                        <p class="text-xs sm:text-sm max-w-2xl leading-relaxed" :class="form.enabled ? 'text-indigo-200' : 'text-gray-500'">
                            Sembunyikan sebagian data pelanggan, tagihan, hingga laporan pajak dengan mudah cukup menentukan target omset atau jumlah pelanggan yang diinginkan.
                        </p>
                    </div>

                    <!-- Master Toggle & Preview Button -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <button 
                            type="button" 
                            @click="toggleMasterEnabled" 
                            class="px-6 py-3.5 rounded-2xl font-bold text-sm shadow-md transition-all flex items-center justify-center gap-2.5 active:scale-95"
                            :class="form.enabled 
                                ? 'bg-white text-indigo-900 hover:bg-indigo-50 border border-white/50' 
                                : 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-indigo-100'">
                            <span>{{ form.enabled ? 'Matikan Penyamaran' : 'Aktifkan Penyamaran Sekarang' }}</span>
                            <span class="text-lg">{{ form.enabled ? '⏹️' : '▶️' }}</span>
                        </button>

                        <button 
                            v-if="masking_info.is_super_admin"
                            type="button" 
                            @click="togglePreviewSession"
                            class="px-5 py-3.5 rounded-2xl font-bold text-xs transition-all flex items-center justify-center gap-2 border"
                            :class="masking_info.preview_mode 
                                ? 'bg-amber-500 text-white border-amber-400 shadow-amber-200' 
                                : (form.enabled ? 'bg-white/10 hover:bg-white/20 text-white border-white/20' : 'bg-gray-50 hover:bg-gray-100 text-gray-700 border-gray-200')">
                            <span>👁️</span>
                            <span>{{ masking_info.preview_mode ? 'Pratinjau Aktif (Klik Tutup)' : 'Uji Pratinjau Tersamar' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Grid: Form Settings & Live Calculator -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- Left Column: Settings Configuration (7 cols) -->
                <div class="lg:col-span-7 space-y-6">
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 sm:p-8 space-y-8">
                        
                        <!-- 1. Pilihan Mode Pembatasan -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">1</div>
                                <h3 class="text-base font-bold text-gray-900">Metode Penyamaran Data</h3>
                            </div>
                            <p class="text-xs text-gray-500">Pilih bagaimana sistem membatasi data pelanggan dan laporan pajak yang ditampilkan.</p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <!-- Mode Target Omset -->
                                <label 
                                    class="relative flex cursor-pointer rounded-2xl border p-4 shadow-xs transition-all duration-200 hover:shadow-md"
                                    :class="form.mode === 'omset' ? 'border-indigo-600 bg-indigo-50/40 ring-2 ring-indigo-500/20' : 'border-gray-200 bg-white hover:border-gray-300'">
                                    <input type="radio" value="omset" v-model="form.mode" @change="runSimulation" class="sr-only">
                                    <div class="flex flex-col gap-1 w-full">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-bold text-gray-900 flex items-center gap-1.5">
                                                <span>🧮</span>
                                                <span>Target Total Omset</span>
                                            </span>
                                            <span v-if="form.mode === 'omset'" class="text-indigo-600 text-xs font-black">✓ Aktif</span>
                                        </div>
                                        <p class="text-[11px] text-gray-500 leading-relaxed mt-1">
                                            Ketik nominal omset (misal 100 Juta). Sistem otomatis menghitung berapa pelanggan yang pas untuk ditampilkan.
                                        </p>
                                    </div>
                                </label>

                                <!-- Mode Target Jumlah Pelanggan -->
                                <label 
                                    class="relative flex cursor-pointer rounded-2xl border p-4 shadow-xs transition-all duration-200 hover:shadow-md"
                                    :class="form.mode === 'customer_count' ? 'border-indigo-600 bg-indigo-50/40 ring-2 ring-indigo-500/20' : 'border-gray-200 bg-white hover:border-gray-300'">
                                    <input type="radio" value="customer_count" v-model="form.mode" @change="runSimulation" class="sr-only">
                                    <div class="flex flex-col gap-1 w-full">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-bold text-gray-900 flex items-center gap-1.5">
                                                <span>👥</span>
                                                <span>Target Jumlah Pelanggan</span>
                                            </span>
                                            <span v-if="form.mode === 'customer_count'" class="text-indigo-600 text-xs font-black">✓ Aktif</span>
                                        </div>
                                        <p class="text-[11px] text-gray-500 leading-relaxed mt-1">
                                            Ketik jumlah pelanggan (misal 300). Sistem hanya menampilkan sebanyak itu dan menghitung omsetnya.
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- 2. Input Nilai Target (Omset / Pelanggan) -->
                        <div class="space-y-4 pt-4 border-t border-gray-100">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">2</div>
                                <h3 class="text-base font-bold text-gray-900">
                                    {{ form.mode === 'omset' ? 'Kalkulator Target Omset (Rupiah)' : 'Jumlah Pelanggan yang Ingin Ditampilkan' }}
                                </h3>
                            </div>

                            <!-- Input Mode Omset -->
                            <div v-if="form.mode === 'omset'" class="space-y-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Ketik Nominal Target Omset:</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 font-bold text-sm">
                                            Rp
                                        </div>
                                        <input 
                                            type="text" 
                                            :value="formattedOmsetInput" 
                                            @input="onOmsetInput" 
                                            placeholder="Contoh: 100.000.000"
                                            class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-gray-200 rounded-2xl text-lg font-black text-gray-900 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all font-mono"
                                        >
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-1">Ketik nominal seperti kalkulator, sistem langsung menghitung akumulasi pelanggan.</p>
                                </div>

                                <!-- Preset Chips Omset -->
                                <div class="flex flex-wrap items-center gap-2 pt-1">
                                    <span class="text-[11px] font-bold text-gray-400">Pilihan Cepat:</span>
                                    <button 
                                        v-for="preset in [50000000, 100000000, 150000000, 200000000, 300000000, 500000000]" 
                                        :key="preset"
                                        type="button" 
                                        @click="applyOmsetPreset(preset)"
                                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all border"
                                        :class="form.target_omset == preset 
                                            ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' 
                                            : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100'">
                                        {{ formatShortNumber(preset) }}
                                    </button>
                                </div>
                            </div>

                            <!-- Input Mode Pelanggan -->
                            <div v-else class="space-y-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Ketik Jumlah Pelanggan:</label>
                                    <div class="relative">
                                        <input 
                                            type="number" 
                                            v-model.number="form.target_customers" 
                                            @input="runSimulation" 
                                            placeholder="Contoh: 300"
                                            min="1"
                                            class="w-full px-4 py-3 bg-slate-50 border border-gray-200 rounded-2xl text-lg font-black text-gray-900 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all font-mono"
                                        >
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">Pelanggan</span>
                                    </div>
                                </div>

                                <!-- Preset Chips Pelanggan -->
                                <div class="flex flex-wrap items-center gap-2 pt-1">
                                    <span class="text-[11px] font-bold text-gray-400">Pilihan Cepat:</span>
                                    <button 
                                        v-for="preset in [100, 250, 500, 750, 1000]" 
                                        :key="preset"
                                        type="button" 
                                        @click="applyCustomerPreset(preset)"
                                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all border"
                                        :class="form.target_customers == preset 
                                            ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' 
                                            : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100'">
                                        {{ preset }} Pelanggan
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Metode Seleksi Pelanggan -->
                        <div class="space-y-4 pt-4 border-t border-gray-100">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">3</div>
                                <h3 class="text-base font-bold text-gray-900">Prioritas Pemilihan Pelanggan</h3>
                            </div>
                            <p class="text-xs text-gray-500">Urutan pelanggan yang diambil oleh kalkulator untuk mencapai target.</p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label 
                                    class="p-3.5 rounded-2xl border flex items-center gap-3 cursor-pointer transition-all"
                                    :class="form.selection_method === 'fifo' ? 'border-indigo-600 bg-indigo-50/30' : 'border-gray-200 hover:border-gray-300'">
                                    <input type="radio" value="fifo" v-model="form.selection_method" @change="runSimulation" class="text-indigo-600 focus:ring-indigo-500">
                                    <div>
                                        <p class="text-xs font-bold text-gray-900">Paling Awal Terdaftar (FIFO)</p>
                                        <p class="text-[10px] text-gray-500">Prioritas pelanggan terlama. Paling stabil & natural.</p>
                                    </div>
                                </label>

                                <label 
                                    class="p-3.5 rounded-2xl border flex items-center gap-3 cursor-pointer transition-all"
                                    :class="form.selection_method === 'alphabetical' ? 'border-indigo-600 bg-indigo-50/30' : 'border-gray-200 hover:border-gray-300'">
                                    <input type="radio" value="alphabetical" v-model="form.selection_method" @change="runSimulation" class="text-indigo-600 focus:ring-indigo-500">
                                    <div>
                                        <p class="text-xs font-bold text-gray-900">Berdasarkan Abjad Nama (A-Z)</p>
                                        <p class="text-[10px] text-gray-500">Urut dari nama huruf awal A ke Z.</p>
                                    </div>
                                </label>

                                <label 
                                    class="p-3.5 rounded-2xl border flex items-center gap-3 cursor-pointer transition-all"
                                    :class="form.selection_method === 'price_asc' ? 'border-indigo-600 bg-indigo-50/30' : 'border-gray-200 hover:border-gray-300'">
                                    <input type="radio" value="price_asc" v-model="form.selection_method" @change="runSimulation" class="text-indigo-600 focus:ring-indigo-500">
                                    <div>
                                        <p class="text-xs font-bold text-gray-900">Paket Termurah Dahulu</p>
                                        <p class="text-[10px] text-gray-500">Menjangkau lebih banyak pelanggan kecil.</p>
                                    </div>
                                </label>

                                <label 
                                    class="p-3.5 rounded-2xl border flex items-center gap-3 cursor-pointer transition-all"
                                    :class="form.selection_method === 'price_desc' ? 'border-indigo-600 bg-indigo-50/30' : 'border-gray-200 hover:border-gray-300'">
                                    <input type="radio" value="price_desc" v-model="form.selection_method" @change="runSimulation" class="text-indigo-600 focus:ring-indigo-500">
                                    <div>
                                        <p class="text-xs font-bold text-gray-900">Paket Termahal Dahulu</p>
                                        <p class="text-[10px] text-gray-500">Fokus ke pelanggan paket besar.</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- 4. Target Akun / Hak Akses ("Bisa di tampilkan di akun yang di tentukan") -->
                        <div class="space-y-4 pt-4 border-t border-gray-100">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">4</div>
                                <h3 class="text-base font-bold text-gray-900">Terapkan Pada Akun Tertentu</h3>
                            </div>
                            <p class="text-xs text-gray-500">Tentukan akun atau hak akses mana saja yang melihat data yang disamarkan ini.</p>

                            <div class="space-y-3">
                                <!-- Pilihan Scope -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <label 
                                        class="p-3 rounded-2xl border cursor-pointer transition-all flex flex-col justify-between"
                                        :class="form.scope === 'all_except_super_admin' ? 'border-indigo-600 bg-indigo-50/40' : 'border-gray-200 bg-white'">
                                        <div class="flex items-center gap-2">
                                            <input type="radio" value="all_except_super_admin" v-model="form.scope" class="text-indigo-600">
                                            <span class="text-xs font-bold text-gray-900">Semua Kecuali Super Admin</span>
                                        </div>
                                        <p class="text-[10px] text-gray-500 mt-2">Semua staf/auditor melihat data tersamar, Super Admin tetap data asli.</p>
                                    </label>

                                    <label 
                                        class="p-3 rounded-2xl border cursor-pointer transition-all flex flex-col justify-between"
                                        :class="form.scope === 'specific_accounts' ? 'border-indigo-600 bg-indigo-50/40' : 'border-gray-200 bg-white'">
                                        <div class="flex items-center gap-2">
                                            <input type="radio" value="specific_accounts" v-model="form.scope" class="text-indigo-600">
                                            <span class="text-xs font-bold text-gray-900">Hanya Akun/Role Tertentu</span>
                                        </div>
                                        <p class="text-[10px] text-gray-500 mt-2">Pilih nama user atau role tertentu yang dikenakan penyamaran.</p>
                                    </label>

                                    <label 
                                        class="p-3 rounded-2xl border cursor-pointer transition-all flex flex-col justify-between"
                                        :class="form.scope === 'everyone' ? 'border-indigo-600 bg-indigo-50/40' : 'border-gray-200 bg-white'">
                                        <div class="flex items-center gap-2">
                                            <input type="radio" value="everyone" v-model="form.scope" class="text-indigo-600">
                                            <span class="text-xs font-bold text-gray-900">Semua Akun (Global)</span>
                                        </div>
                                        <p class="text-[10px] text-gray-500 mt-2">Berlaku untuk semua pengguna termasuk saat presentasi sistem.</p>
                                    </label>
                                </div>

                                <!-- Detail Pemilihan Akun (Jika specific_accounts) -->
                                <div v-if="form.scope === 'specific_accounts'" class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-4">
                                    <!-- Pilih Role -->
                                    <div>
                                        <label class="block text-xs font-bold text-slate-800 mb-2">Pilih Role yang Dikenakan Penyamaran:</label>
                                        <div class="flex flex-wrap gap-2 max-h-36 overflow-y-auto p-1">
                                            <label 
                                                v-for="role in roles" 
                                                :key="role" 
                                                class="px-3 py-1.5 rounded-xl border text-xs font-medium cursor-pointer transition-all flex items-center gap-1.5"
                                                :class="form.target_roles.includes(role) ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-200 hover:border-gray-300'">
                                                <input type="checkbox" :value="role" v-model="form.target_roles" class="sr-only">
                                                <span>{{ role }}</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Pilih User Spesifik -->
                                    <div class="pt-3 border-t border-slate-200">
                                        <label class="block text-xs font-bold text-slate-800 mb-2">Atau Pilih Pengguna (Akun) Tertentu:</label>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-44 overflow-y-auto p-1">
                                            <label 
                                                v-for="u in users" 
                                                :key="u.id" 
                                                class="p-2 rounded-xl border text-xs cursor-pointer transition-all flex items-center justify-between"
                                                :class="form.target_users.includes(u.id) ? 'bg-indigo-50 border-indigo-500 text-indigo-900 font-bold' : 'bg-white text-gray-700 border-gray-200'">
                                                <div class="flex items-center gap-2 truncate">
                                                    <input type="checkbox" :value="u.id" v-model="form.target_users" class="rounded text-indigo-600">
                                                    <span class="truncate">{{ u.name }}</span>
                                                </div>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 ml-1">{{ u.role || 'user' }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 5. Cakupan Modul yang Disembunyikan / Dibatasi -->
                        <div class="space-y-5 pt-5 border-t border-gray-100">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">5</div>
                                    <div>
                                        <h3 class="text-base font-bold text-gray-900">Modul yang Disembunyikan & Disamarkan</h3>
                                        <p class="text-xs text-gray-500">Pilih modul apa saja yang datanya disembunyikan/disamarkan saat mode privasi aktif.</p>
                                    </div>
                                </div>
                                
                                <!-- Quick Select Buttons -->
                                <div class="flex items-center gap-2 shrink-0">
                                    <button 
                                        type="button" 
                                        @click="selectAllModules" 
                                        class="px-3 py-1.5 rounded-xl border border-indigo-200 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold transition-colors">
                                        ✓ Pilih Semua ({{ allModuleKeys.length }})
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="deselectAllModules" 
                                        class="px-3 py-1.5 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 text-xs font-bold transition-colors">
                                        ✕ Kosongkan
                                    </button>
                                </div>
                            </div>

                            <!-- Opsi Sembunyikan Menu Sidebar -->
                            <div class="p-4 rounded-2xl bg-indigo-50/50 border border-indigo-100 flex items-start gap-3.5">
                                <input 
                                    type="checkbox" 
                                    id="hide_sidebar_menu" 
                                    v-model="form.hide_sidebar_menu" 
                                    class="mt-1 rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer">
                                <label for="hide_sidebar_menu" class="cursor-pointer text-xs space-y-0.5">
                                    <span class="font-bold text-indigo-950 block">Sembunyikan juga item menu dari navigasi sidebar & mobile (Mode Steril)</span>
                                    <span class="text-indigo-700/80 block leading-relaxed">
                                        Bila dicentang, menu-menu yang dipilih di bawah tidak akan muncul sama sekali di sidebar navigasi akun target. Akun Super Admin tetap melihat menu secara normal.
                                    </span>
                                </label>
                            </div>

                            <!-- Modul Terkategori -->
                            <div class="space-y-4">
                                <div 
                                    v-for="cat in moduleCategories" 
                                    :key="cat.key" 
                                    class="p-4 rounded-2xl border border-gray-200/80 bg-gray-50/40 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="text-base">{{ cat.icon }}</span>
                                            <h4 class="text-xs font-bold text-gray-900 tracking-tight">{{ cat.title }}</h4>
                                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 font-bold">
                                                {{ cat.modules.filter(m => form.modules.includes(m.key)).length }} / {{ cat.modules.length }} Aktif
                                            </span>
                                        </div>
                                        <button 
                                            type="button" 
                                            @click="toggleCategory(cat)" 
                                            class="text-[11px] text-indigo-600 hover:text-indigo-800 font-bold">
                                            {{ isCategoryFullySelected(cat) ? 'Batal Pilih' : 'Pilih Kategori Ini' }}
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                        <label 
                                            v-for="mod in cat.modules" 
                                            :key="mod.key" 
                                            class="p-3 rounded-xl border transition-all flex items-start gap-3 cursor-pointer"
                                            :class="form.modules.includes(mod.key) ? 'border-indigo-500 bg-white shadow-2xs ring-1 ring-indigo-500/10' : 'border-gray-200 bg-white hover:border-gray-300'">
                                            <input 
                                                type="checkbox" 
                                                :value="mod.key" 
                                                v-model="form.modules" 
                                                class="rounded text-indigo-600 focus:ring-indigo-500 mt-0.5 shrink-0">
                                            <div class="space-y-0.5 min-w-0">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="text-xs font-bold text-gray-900 truncate">{{ mod.label }}</span>
                                                    <span class="text-[10px] px-1.5 py-0.2 rounded font-mono bg-gray-100 text-gray-500 shrink-0">{{ mod.badge }}</span>
                                                </div>
                                                <p class="text-[11px] text-gray-500 leading-snug">{{ mod.desc }}</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Submit -->
                        <div class="pt-6 border-t border-gray-100 flex items-center justify-end gap-3">
                            <Link href="/tax-reports" class="px-5 py-3 rounded-2xl border border-gray-200 text-xs font-bold text-gray-700 hover:bg-gray-50 transition-colors">
                                Kembali ke Laporan Pajak
                            </Link>
                            <button 
                                type="button" 
                                @click="saveSettings" 
                                :disabled="saving"
                                class="px-7 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-md shadow-indigo-100 flex items-center gap-2 transition-all active:scale-95 disabled:opacity-50">
                                <span v-if="saving" class="animate-spin text-base">⏳</span>
                                <span v-else>💾</span>
                                <span>{{ saving ? 'Menyimpan & Menghitung...' : 'Simpan & Terapkan Pengaturan' }}</span>
                            </button>
                        </div>

                    </div>
                </div>

                <!-- Right Column: Live Interactive Calculator Card (5 cols) -->
                <div class="lg:col-span-5 space-y-6 lg:sticky lg:top-6">
                    <div class="bg-gradient-to-br from-slate-900 to-indigo-950 rounded-3xl p-6 sm:p-7 text-white shadow-xl border border-indigo-800/50 relative overflow-hidden">
                        <div class="absolute -right-10 -bottom-10 w-44 h-44 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

                        <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-5">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">🧮</span>
                                <h3 class="text-sm font-bold uppercase tracking-wider text-indigo-300">Hasil Kalkulasi Live</h3>
                            </div>
                            <span v-if="calculating" class="text-[11px] font-bold text-amber-400 flex items-center gap-1.5 animate-pulse">
                                <span>⏳</span>
                                <span>Menghitung...</span>
                            </span>
                            <span v-else class="text-[11px] font-bold text-emerald-400 flex items-center gap-1">
                                <span>⚡</span>
                                <span>Real-time</span>
                            </span>
                        </div>

                        <!-- Stat Cards Grid -->
                        <div class="space-y-4">
                            <!-- Main Reached Omset -->
                            <div class="p-4 bg-white/5 rounded-2xl border border-white/10 backdrop-blur-sm">
                                <p class="text-[11px] font-bold text-indigo-200 uppercase tracking-wider">
                                    {{ form.mode === 'omset' ? 'Omset Aktual yang Terpenuhi' : 'Total Omset yang Dihasilkan' }}
                                </p>
                                <div class="mt-1 flex items-baseline justify-between gap-2 flex-wrap">
                                    <h4 class="text-2xl sm:text-3xl font-black text-white font-mono tracking-tight">
                                        {{ formatCurrency(simResult.reached_omset || 0) }}
                                    </h4>
                                    <span v-if="form.mode === 'omset' && form.target_omset > 0" class="text-[11px] font-bold px-2 py-0.5 rounded-full"
                                        :class="Math.abs(simResult.difference || 0) <= 100000 ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300'">
                                        Selisih: {{ formatCurrency(Math.abs(simResult.difference || 0)) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Selected Customers vs Total -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
                                    <p class="text-[10px] font-bold text-indigo-200 uppercase">Pelanggan Terpilih</p>
                                    <div class="mt-1 flex items-baseline gap-1">
                                        <span class="text-2xl font-black text-white font-mono">{{ simResult.selected_count || 0 }}</span>
                                        <span class="text-xs text-indigo-300 font-bold">Orang</span>
                                    </div>
                                    <p class="text-[10px] text-indigo-300/70 mt-1">Ditampilkan di laporan</p>
                                </div>

                                <div class="p-4 bg-white/5 rounded-2xl border border-white/10">
                                    <p class="text-[10px] font-bold text-indigo-200 uppercase">Total Riil Sistem</p>
                                    <div class="mt-1 flex items-baseline gap-1">
                                        <span class="text-2xl font-black text-white/60 font-mono">{{ simResult.total_active || 0 }}</span>
                                        <span class="text-xs text-indigo-300/60 font-bold">Orang</span>
                                    </div>
                                    <p class="text-[10px] text-indigo-300/70 mt-1">
                                        {{ Math.round(((simResult.selected_count || 0) / (simResult.total_active || 1)) * 100) }}% ditampilkan
                                    </p>
                                </div>
                            </div>

                            <!-- Tax Breakdown -->
                            <div class="p-4 bg-white/5 rounded-2xl border border-white/10 space-y-2.5 text-xs font-mono">
                                <div class="flex items-center justify-between text-indigo-200">
                                    <span class="text-[11px]">Total Dasar (DPP):</span>
                                    <span class="font-bold text-white">{{ formatCurrency(simResult.base_price || 0) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-indigo-200">
                                    <span class="text-[11px]">Estimasi PPN:</span>
                                    <span class="font-bold text-white">{{ formatCurrency(simResult.ppn || 0) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-indigo-200">
                                    <span class="text-[11px]">Estimasi BHP & USO:</span>
                                    <span class="font-bold text-white">{{ formatCurrency(simResult.bhp_uso || 0) }}</span>
                                </div>
                                <div class="pt-2 border-t border-white/10 flex items-center justify-between text-sm font-bold text-emerald-300">
                                    <span>Grand Total:</span>
                                    <span>{{ formatCurrency(simResult.grand_total || simResult.reached_omset || 0) }}</span>
                                </div>
                            </div>

                            <!-- Sample Pelanggan Preview -->
                            <div v-if="simResult.sample_customers && simResult.sample_customers.length > 0" class="pt-2">
                                <p class="text-[11px] font-bold text-indigo-300 mb-2 flex items-center justify-between">
                                    <span>Contoh Pelanggan yang Masuk:</span>
                                    <span class="text-[10px] text-indigo-400">Sample Teratas</span>
                                </p>
                                <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                                    <div 
                                        v-for="c in simResult.sample_customers" 
                                        :key="c.id"
                                        class="p-2 bg-white/5 hover:bg-white/10 rounded-xl flex items-center justify-between text-xs transition-colors">
                                        <div class="truncate mr-2">
                                            <p class="font-bold text-white truncate">{{ c.name }}</p>
                                            <p class="text-[10px] text-indigo-300 font-mono">{{ c.customer_code }} • {{ c.package_name }}</p>
                                        </div>
                                        <span class="font-mono text-emerald-300 font-bold shrink-0">{{ formatCurrency(c.total_price) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
    roles: { type: Array, default: () => [] },
    users: { type: Array, default: () => [] },
    masking_info: { type: Object, default: () => ({}) },
});

const form = ref({
    enabled: !!props.settings.enabled,
    mode: props.settings.mode || 'omset',
    target_omset: props.settings.target_omset || 100000000,
    target_customers: props.settings.target_customers || 500,
    selection_method: props.settings.selection_method || 'fifo',
    scope: props.settings.scope || 'all_except_super_admin',
    target_roles: props.settings.target_roles || [],
    target_users: props.settings.target_users || [],
    hide_sidebar_menu: props.settings.hide_sidebar_menu !== false,
    modules: props.settings.modules || [
        'tax_report', 'customers', 'invoices', 'billing', 'financial_reports',
        'monitoring_pelanggan',
        'network_data', 'onts', 'network_map', 'pgk_tickets', 'technician_monitoring'
    ],
});

const moduleCategories = [
    {
        key: 'psb',
        title: 'Siklus Pemasangan Baru (PSB)',
        icon: '📋',
        modules: [
            { key: 'booking', label: '1. Booking Baru', badge: '/customers/booking', desc: 'Sembunyikan antrean booking & prospek pasang baru.' },
            { key: 'survey', label: '2. Survey Lokasi', badge: '/customers/survey', desc: 'Sembunyikan jadwal survey & kelayakan teknis ODP.' },
            { key: 'installed', label: '3. Instalasi Jaringan', badge: '/customers/installed', desc: 'Sembunyikan jadwal pasang & progres teknisi lapangan.' },
            { key: 'activation', label: '4. Aktivasi Layanan', badge: '/customers/activation', desc: 'Sembunyikan antrean aktivasi akun PPPoE/Hotspot.' },
        ]
    },
    {
        key: 'customers',
        title: 'Data Pelanggan & Pantauan',
        icon: '👥',
        modules: [
            { key: 'customers', label: 'Semua Pelanggan Aktif', badge: '/customers', desc: 'Batasi jumlah data pelanggan sesuai kalkulator omset.' },
            { key: 'monitoring_pelanggan', label: 'Pantauan Pelanggan (Isolir & Tunggakan)', badge: '/monitoring-pelanggan', desc: 'Batasi data menunggak, janji bayar, dan isolir.' },
        ]
    },
    {
        key: 'billing',
        title: 'Billing & Keuangan',
        icon: '💵',
        modules: [
            { key: 'invoices', label: 'Invoice Pelanggan', badge: '/invoices', desc: 'Hanya tampilkan tagihan yang masuk kuota omset.' },
            { key: 'billing', label: 'Ruang Penagihan & Setoran', badge: '/customer-billing', desc: 'Sembunyikan antrean tagihan penagih lapangan.' },
            { key: 'tax_report', label: 'Laporan Pajak & Cetak PDF', badge: '/tax-reports', desc: 'Laporan pajak resmi, PPN, dan BHP/USO sesuai omset.' },
            { key: 'financial_reports', label: 'Laporan Pelanggan & Buku Kas', badge: '/financial-reports', desc: 'Sinkronkan mutasi kas & rekapitulasi pelanggan.' },
        ]
    },
    {
        key: 'network',
        title: 'Infrastruktur & Jaringan',
        icon: '🌐',
        modules: [
            { key: 'network_data', label: 'Data Jaringan & Port ODP', badge: '/network-data', desc: 'Samarkan nama pelanggan pada port ODP & ODC.' },
            { key: 'onts', label: 'Data Perangkat Modem ONT', badge: '/onts', desc: 'Sembunyikan modem yang terpasang di luar kuota tersamar.' },
            { key: 'network_map', label: 'Peta GIS & Radar Jaringan', badge: '/network-map', desc: 'Sembunyikan titik koordinat & rute pelanggan di peta.' },
        ]
    },
    {
        key: 'support',
        title: 'Layanan & Operasional',
        icon: '🎫',
        modules: [
            { key: 'pgk_tickets', label: 'Komplain Pelanggan (PGK)', badge: '/pgk', desc: 'Sembunyikan tiket gangguan dan aduan pelanggan.' },
            { key: 'technician_monitoring', label: 'Monitoring Kinerja Teknisi', badge: '/monitoring-teknisi', desc: 'Samarkan riwayat pekerjaan teknisi di pelanggan.' },
        ]
    }
];

const allModuleKeys = computed(() => {
    return moduleCategories.flatMap(c => c.modules.map(m => m.key));
});

const selectAllModules = () => {
    form.value.modules = [...allModuleKeys.value];
};

const deselectAllModules = () => {
    form.value.modules = [];
};

const isCategoryFullySelected = (cat) => {
    return cat.modules.every(m => form.value.modules.includes(m.key));
};

const toggleCategory = (cat) => {
    const catKeys = cat.modules.map(m => m.key);
    if (isCategoryFullySelected(cat)) {
        form.value.modules = form.value.modules.filter(k => !catKeys.includes(k));
    } else {
        const set = new Set([...form.value.modules, ...catKeys]);
        form.value.modules = Array.from(set);
    }
};

const saving = ref(false);
const calculating = ref(false);
const simResult = ref(props.settings.summary || {});

// Currency formatting for input field
const formattedOmsetInput = computed(() => {
    if (!form.value.target_omset) return '';
    return Number(form.value.target_omset).toLocaleString('id-ID');
});

const onOmsetInput = (e) => {
    const raw = e.target.value.replace(/[^0-9]/g, '');
    form.value.target_omset = raw ? Number(raw) : 0;
    debounceSimulation();
};

const applyOmsetPreset = (amount) => {
    form.value.target_omset = amount;
    runSimulation();
};

const applyCustomerPreset = (count) => {
    form.value.target_customers = count;
    runSimulation();
};

let simTimeout = null;
const debounceSimulation = () => {
    clearTimeout(simTimeout);
    simTimeout = setTimeout(() => {
        runSimulation();
    }, 400);
};

const runSimulation = async () => {
    calculating.value = true;
    try {
        const res = await axios.post('/settings/data-masking/simulate', {
            mode: form.value.mode,
            target_omset: form.value.target_omset,
            target_customers: form.value.target_customers,
            selection_method: form.value.selection_method,
        });
        if (res.data?.status === 'success') {
            simResult.value = res.data.data;
        }
    } catch (e) {
        console.error('Simulation error:', e);
    } finally {
        calculating.value = false;
    }
};

const saveSettings = () => {
    saving.value = true;
    router.post('/settings/data-masking', form.value, {
        preserveScroll: true,
        onFinish: () => {
            saving.value = false;
        }
    });
};

const toggleMasterEnabled = () => {
    form.value.enabled = !form.value.enabled;
    saveSettings();
};

const togglePreviewSession = () => {
    router.post('/settings/data-masking/toggle-preview', {}, {
        preserveScroll: true,
    });
};

const formatCurrency = (val) => {
    return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
};

const formatShortNumber = (val) => {
    if (val >= 1000000000) return (val / 1000000000) + ' M';
    if (val >= 1000000) return (val / 1000000) + ' Juta';
    return Number(val).toLocaleString('id-ID');
};

onMounted(() => {
    runSimulation();
});
</script>

