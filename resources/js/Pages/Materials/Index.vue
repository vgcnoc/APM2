<template>
    <AppLayout title="Data Material / Barang" subtitle="Kelola persediaan material infrastruktur jaringan">
        <div class="space-y-6">
            <!-- Header Actions -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="w-full sm:w-96 relative">
                    <input 
                        type="text" 
                        v-model="search" 
                        placeholder="Cari nama atau kategori barang..." 
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm"
                        @keyup.enter="performSearch"
                    >
                    <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <button @click="openModal()" class="btn-primary shrink-0 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Material
                </button>
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="mb-4 flex items-center gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 animate-fade-in-up">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
            </div>

            <div v-if="$page.props.flash?.error" class="mb-4 flex items-center gap-3 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-600 animate-fade-in-up">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">{{ $page.props.flash.error }}</span>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100">
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Barang</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Kategori</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Stok</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">H. Modal / Jual</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in materials.data" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-4 px-6">
                                    <p class="text-sm font-semibold text-gray-900">{{ item.name }}</p>
                                    <p v-if="item.supplier" class="text-xs text-blue-600 mt-0.5">Supplier: {{ item.supplier }}</p>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                        {{ item.category || 'Lainnya' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex flex-col items-end gap-1">
                                        <div class="flex items-center gap-1.5" title="Stok Awal">
                                            <span class="text-[10px] font-bold text-gray-400 uppercase">Awal:</span>
                                            <span class="text-xs font-semibold text-gray-600">{{ formatNumber(item.initial_stock) }} {{ item.unit }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5" title="Sisa Stok Saat Ini">
                                            <span class="text-[10px] font-bold text-gray-400 uppercase">Sisa:</span>
                                            <span class="text-sm font-bold text-blue-600">{{ formatNumber(item.stock) }}</span>
                                            <span class="text-xs text-gray-500">{{ item.unit }}</span>
                                        </div>
                                        <div v-if="item.category === 'Kabel' && item.total_rolls" class="text-xs text-gray-400 mt-0.5">
                                            ({{ item.total_rolls }} roll)
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-right text-sm">
                                    <p class="text-gray-900">M: Rp {{ formatNumber(item.price_per_unit) }}</p>
                                    <p class="text-emerald-600 font-medium">J: Rp {{ formatNumber(item.selling_price) }}</p>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openAddStockModal(item)" title="Tambah Stok Masuk" class="p-1.5 text-emerald-500 hover:text-emerald-700 rounded-lg hover:bg-emerald-50 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                        </button>
                                        <button @click="openModal(item)" title="Edit Data Barang" class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                        </button>
                                        <button @click="deleteData(item.id)" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="materials.data.length === 0">
                                <td colspan="5" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        <p class="text-sm font-medium">Belum ada data material</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="materials.links && materials.data.length > 0" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-xs text-gray-500">Menampilkan {{ materials.from }} - {{ materials.to }} dari {{ materials.total }} data</p>
                    <div class="flex gap-1">
                        <Link 
                            v-for="(link, i) in materials.links" 
                            :key="i"
                            :href="link.url || '#'"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                            :class="[
                                link.active ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100',
                                !link.url && 'opacity-50 cursor-not-allowed'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Form -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="closeModal"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-y-auto max-h-[90vh] animate-fade-in-up">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10">
                    <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit Produk' : 'Tambah Produk Baru' }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Produk *</label>
                            <input v-model="form.name" type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required placeholder="Contoh: Kabel FO 12 Core / Isolasi Hitam">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                            <input v-model="form.supplier" type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="Nama supplier / distributor">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                                <select v-model="form.category" @change="handleCategoryChange" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                    <option value="">Pilih Kategori...</option>
                                    <option value="Kabel">Kabel</option>
                                    <option value="Konektor / Frecon">Konektor / Frecon</option>
                                    <option value="Isolasi">Isolasi</option>
                                    <option value="Paku Klem">Paku Klem</option>
                                    <option value="Perangkat Aktif">Perangkat Aktif</option>
                                    <option value="Aksesoris">Aksesoris Lainnya</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Satuan</label>
                                <input v-if="form.category === 'Kabel'" type="text" v-model="form.unit" disabled class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-gray-500 cursor-not-allowed">
                                <template v-else>
                                    <input type="text" v-model="form.unit" list="unit-options" placeholder="Ketik atau pilih satuan..." class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                    <datalist id="unit-options">
                                        <option value="pcs"></option>
                                        <option value="meter"></option>
                                        <option value="cm"></option>
                                        <option value="rol"></option>
                                        <option value="pack"></option>
                                        <option value="unit"></option>
                                        <option value="set"></option>
                                        <option value="box"></option>
                                    </datalist>
                                </template>
                            </div>
                        </div>

                        <!-- Kabel Calculator -->
                        <div v-if="form.category === 'Kabel'" class="bg-blue-50/50 border border-blue-100 rounded-xl p-4">
                            <div class="flex items-center gap-2 mb-3 text-blue-600 font-medium text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                Kalkulator Kabel (Roll ↔ Meter)
                            </div>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Meter per Roll</label>
                                    <input v-model="form.meter_per_roll" @input="calculateCableStock" type="number" step="0.01" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Jumlah Roll</label>
                                    <input v-model="form.total_rolls" @input="calculateCableStock" type="number" step="0.01" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Harga Modal (per roll)</label>
                                    <input v-model="form.price_per_unit" type="number" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Harga Jual (per roll)</label>
                                    <input v-model="form.selling_price" type="number" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mt-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Harga Modal (per meter)</label>
                                    <div class="w-full px-3 py-2 rounded-lg border border-gray-100 bg-gray-50 text-sm font-semibold text-gray-700">
                                        Rp {{ formatNumber(hargaModalPerMeter) }}
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Harga Jual (per meter)</label>
                                    <div class="w-full px-3 py-2 rounded-lg border border-emerald-100 bg-emerald-50 text-sm font-semibold text-emerald-700">
                                        Rp {{ formatNumber(hargaJualPerMeter) }}
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 pt-3 border-t border-blue-100 flex justify-between items-center">
                                <span class="text-xs text-blue-700">Total Stok Tersimpan (Otomatis):</span>
                                <span class="text-sm font-bold text-blue-700">{{ form.stock }} Meter</span>
                            </div>
                        </div>

                        <!-- Paku Klem Calculator -->
                        <div v-else-if="form.category === 'Paku Klem'" class="bg-indigo-50/50 border border-indigo-100 rounded-xl p-4">
                            <div class="flex items-center gap-2 mb-3 text-indigo-600 font-medium text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                Kalkulator Paku Klem (Bungkus ↔ Pcs)
                            </div>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Isi Pcs per Bungkus</label>
                                    <input v-model="form.pcs_per_pack" @input="calculatePackStock" type="number" step="1" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Jumlah Bungkus / Dus</label>
                                    <input v-model="form.total_packs" @input="calculatePackStock" type="number" step="0.01" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Harga Modal (per bungkus)</label>
                                    <input v-model="form.price_per_unit" type="number" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Harga Jual (per bungkus)</label>
                                    <input v-model="form.selling_price" type="number" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mt-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Harga Modal (per pcs)</label>
                                    <div class="w-full px-3 py-2 rounded-lg border border-gray-100 bg-gray-50 text-sm font-semibold text-gray-700">
                                        Rp {{ formatNumber(hargaModalPerPackPcs) }}
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Harga Jual (per pcs)</label>
                                    <div class="w-full px-3 py-2 rounded-lg border border-emerald-100 bg-emerald-50 text-sm font-semibold text-emerald-700">
                                        Rp {{ formatNumber(hargaJualPerPackPcs) }}
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 pt-3 border-t border-indigo-100 flex justify-between items-center">
                                <span class="text-xs text-indigo-700">Total Stok Tersimpan (Otomatis):</span>
                                <span class="text-sm font-bold text-indigo-700">{{ form.stock }} Pcs</span>
                            </div>
                        </div>

                        <!-- Isolasi Calculator -->
                        <div v-else-if="form.category === 'Isolasi'" class="bg-amber-50/50 border border-amber-100 rounded-xl p-4">
                            <div class="flex items-center gap-2 mb-3 text-amber-600 font-medium text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l-7-7m7 7l-2.828 2.828M15 9l-6 6"/>
                                </svg>
                                Kalkulator Isolasi (Pcs ↔ Cm)
                            </div>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Panjang per Pcs (cm)</label>
                                    <input v-model="form.cm_per_pcs" @input="calculateIsolasiStock" type="number" step="0.1" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Jumlah Pcs (Roll Kecil)</label>
                                    <input v-model="form.total_pieces" @input="calculateIsolasiStock" type="number" step="0.01" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Harga Modal (per pcs)</label>
                                    <input v-model="form.price_per_unit" type="number" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Harga Jual (per pcs)</label>
                                    <input v-model="form.selling_price" type="number" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mt-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Harga Modal (per cm)</label>
                                    <div class="w-full px-3 py-2 rounded-lg border border-gray-100 bg-gray-50 text-sm font-semibold text-gray-700">
                                        Rp {{ formatNumber(hargaModalPerCm) }}
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Harga Jual (per cm)</label>
                                    <div class="w-full px-3 py-2 rounded-lg border border-emerald-100 bg-emerald-50 text-sm font-semibold text-emerald-700">
                                        Rp {{ formatNumber(hargaJualPerCm) }}
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 pt-3 border-t border-amber-100 flex justify-between items-center">
                                <span class="text-xs text-amber-700">Total Stok Tersimpan (Otomatis):</span>
                                <span class="text-sm font-bold text-amber-700">{{ form.stock }} Cm</span>
                            </div>
                        </div>

                        <!-- Normal Stock & Price Calculator (Non Kabel, Klem, Isolasi) -->
                        <div v-else class="bg-blue-50/50 border border-blue-100 rounded-xl p-4">
                            <div class="flex items-center gap-2 mb-4 text-blue-600 font-medium text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                Kalkulator Stok & Harga (Otomatis)
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Total Stok Masuk ({{ form.unit || 'pcs' }}) *</label>
                                <input v-model="form.stock" type="number" step="0.01" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-500 bg-white" required>
                            </div>

                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Total Harga Beli (Semua Stok)</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 text-gray-500 text-sm">Rp</span>
                                        <input v-model="tempTotalModal" @input="calculateUnitPrice('modal')" type="number" min="0" class="w-full pl-9 pr-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-500 bg-white" placeholder="Cth: 1000000">
                                    </div>
                                    <p class="text-[10px] text-gray-400 mt-1">Opsional: Ketik total tagihan, harga satuan akan dihitung</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Harga Modal Satuan (per {{ form.unit || 'pcs' }})</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 text-gray-500 text-sm">Rp</span>
                                        <input v-model="form.price_per_unit" @input="calculateTotalPrice('modal')" type="number" min="0" class="w-full pl-9 pr-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-500 bg-white" placeholder="Cth: 100000">
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Total Harga Jual (Semua Stok)</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 text-gray-500 text-sm">Rp</span>
                                        <input v-model="tempTotalJual" @input="calculateUnitPrice('jual')" type="number" min="0" class="w-full pl-9 pr-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-500 bg-white" placeholder="Opsional...">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Harga Jual Satuan (per {{ form.unit || 'pcs' }})</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 text-gray-500 text-sm">Rp</span>
                                        <input v-model="form.selling_price" @input="calculateTotalPrice('jual')" type="number" min="0" class="w-full pl-9 pr-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-500 bg-white" placeholder="Cth: 150000">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                            <textarea v-model="form.description" rows="3" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"></textarea>
                        </div>
                    </form>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 sticky bottom-0 z-10">
                    <button type="button" @click="closeModal" class="px-5 py-2.5 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors">Batal</button>
                    <button type="button" @click="submit" :disabled="form.processing" class="btn-primary text-sm">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Data' }}
                    </button>
                </div>
            </div>
        </div>
        <!-- Modal Add Stock -->
        <div v-if="showAddStockModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeAddStockModal"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden animate-fade-in-up">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-white">
                    <h3 class="text-lg font-bold text-gray-900">Tambah Stok Masuk</h3>
                    <button @click="closeAddStockModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <div class="p-6 overflow-y-auto max-h-[calc(100vh-12rem)]">
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-5">
                        <h4 class="font-bold text-blue-900">{{ selectedMaterial?.name }}</h4>
                        <div class="flex gap-4 mt-2 text-sm text-blue-800">
                            <div>Sisa Stok: <span class="font-bold">{{ formatNumber(selectedMaterial?.stock) }} {{ selectedMaterial?.unit }}</span></div>
                            <div v-if="selectedMaterial?.category === 'Kabel' && selectedMaterial?.total_rolls">Total: <span class="font-bold">{{ selectedMaterial?.total_rolls }} roll</span></div>
                        </div>
                    </div>

                    <form @submit.prevent="submitAddStock" class="space-y-5">
                        <div v-if="selectedMaterial?.category === 'Kabel'" class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tambah Roll Baru *</label>
                                <div class="flex items-center gap-2">
                                    <input v-model="addStockForm.added_rolls" @input="calculateAddedCableStock" type="number" step="0.01" min="0" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required>
                                    <span class="text-gray-500 text-sm">roll</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Total Meter (Otomatis)</label>
                                <div class="flex items-center gap-2">
                                    <input v-model="addStockForm.added_stock" type="number" step="0.01" min="0.01" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-gray-700" readonly>
                                    <span class="text-gray-500 text-sm">m</span>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1">@ {{ selectedMaterial?.meter_per_roll }} m/roll</p>
                            </div>
                        </div>
                        <div v-else-if="selectedMaterial?.category === 'Paku Klem'" class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tambah Bungkus Baru *</label>
                                <div class="flex items-center gap-2">
                                    <input v-model="addStockForm.added_packs" @input="calculateAddedPackStock" type="number" step="0.01" min="0" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required>
                                    <span class="text-gray-500 text-sm">bungkus</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Total Pcs (Otomatis)</label>
                                <div class="flex items-center gap-2">
                                    <input v-model="addStockForm.added_stock" type="number" step="0.01" min="0.01" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-gray-700" readonly>
                                    <span class="text-gray-500 text-sm">pcs</span>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1">@ {{ selectedMaterial?.pcs_per_pack }} pcs/bungkus</p>
                            </div>
                        </div>
                        <div v-else-if="selectedMaterial?.category === 'Isolasi'" class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tambah Pcs Baru *</label>
                                <div class="flex items-center gap-2">
                                    <input v-model="addStockForm.added_pieces" @input="calculateAddedIsolasiStock" type="number" step="0.01" min="0" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required>
                                    <span class="text-gray-500 text-sm">pcs</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Total Cm (Otomatis)</label>
                                <div class="flex items-center gap-2">
                                    <input v-model="addStockForm.added_stock" type="number" step="0.01" min="0.01" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-gray-700" readonly>
                                    <span class="text-gray-500 text-sm">cm</span>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1">@ {{ selectedMaterial?.cm_per_pcs }} cm/pcs</p>
                            </div>
                        </div>
                        <div v-else>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Stok Masuk *</label>
                            <div class="flex items-center gap-2">
                                <input v-model="addStockForm.added_stock" type="number" step="0.01" min="0.01" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required>
                                <span class="text-gray-500 text-sm">{{ selectedMaterial?.unit }}</span>
                            </div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                            <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Perbarui Harga (Opsional)
                            </h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Hrg Modal Baru</label>
                                    <input v-model="addStockForm.price_per_unit" type="number" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Hrg Jual Baru</label>
                                    <input v-model="addStockForm.selling_price" type="number" min="0" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-2">Kosongkan/biarkan jika harga tidak berubah.</p>
                        </div>
                    </form>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 sticky bottom-0 z-10">
                    <button type="button" @click="closeAddStockModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="button" @click="submitAddStock" :disabled="addStockForm.processing" class="px-5 py-2 text-sm font-medium text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-200 transition-all disabled:opacity-50 flex items-center gap-2">
                        <svg v-if="addStockForm.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Simpan Stok Baru
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    materials: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const showModal = ref(false);
const showAddStockModal = ref(false);
const selectedMaterial = ref(null);
const isEditing = ref(false);
const editingId = ref(null);
const tempTotalModal = ref(null);
const tempTotalJual = ref(null);

const form = useForm({
    name: '',
    supplier: '',
    category: '',
    unit: 'pcs',
    meter_per_roll: 0,
    total_rolls: 0,
    pcs_per_pack: 0,
    total_packs: 0,
    cm_per_pcs: 0,
    total_pieces: 0,
    stock: 0,
    price_per_unit: 0,
    selling_price: 0,
    description: '',
});

const addStockForm = useForm({
    added_stock: '',
    added_rolls: '',
    added_packs: '',
    added_pieces: '',
    price_per_unit: '',
    selling_price: ''
});

const formatNumber = (num) => {
    if (!num) return '0';
    return Number(num).toLocaleString('id-ID');
};

const hargaModalPerMeter = computed(() => {
    const meterPerRoll = parseFloat(form.meter_per_roll) || 0;
    const hargaPerRoll = parseFloat(form.price_per_unit) || 0;
    if (meterPerRoll <= 0) return 0;
    return Math.round(hargaPerRoll / meterPerRoll);
});

const hargaJualPerMeter = computed(() => {
    const meterPerRoll = parseFloat(form.meter_per_roll) || 0;
    const hargaPerRoll = parseFloat(form.selling_price) || 0;
    if (meterPerRoll <= 0) return 0;
    return Math.round(hargaPerRoll / meterPerRoll);
});

const hargaModalPerPackPcs = computed(() => {
    const pcsPerPack = parseFloat(form.pcs_per_pack) || 0;
    const hargaPerPack = parseFloat(form.price_per_unit) || 0;
    if (pcsPerPack <= 0) return 0;
    return Math.round(hargaPerPack / pcsPerPack);
});

const hargaJualPerPackPcs = computed(() => {
    const pcsPerPack = parseFloat(form.pcs_per_pack) || 0;
    const hargaPerPack = parseFloat(form.selling_price) || 0;
    if (pcsPerPack <= 0) return 0;
    return Math.round(hargaPerPack / pcsPerPack);
});

const hargaModalPerCm = computed(() => {
    const cmPerPcs = parseFloat(form.cm_per_pcs) || 0;
    const hargaPerPcs = parseFloat(form.price_per_unit) || 0;
    if (cmPerPcs <= 0) return 0;
    return Math.round(hargaPerPcs / cmPerPcs);
});

const hargaJualPerCm = computed(() => {
    const cmPerPcs = parseFloat(form.cm_per_pcs) || 0;
    const hargaPerPcs = parseFloat(form.selling_price) || 0;
    if (cmPerPcs <= 0) return 0;
    return Math.round(hargaPerPcs / cmPerPcs);
});

const performSearch = () => {
    router.get('/materials', { search: search.value }, { preserveState: true });
};

const calculateUnitPrice = (type) => {
    const stock = parseFloat(form.stock) || 0;
    if (stock <= 0) return;
    
    if (type === 'modal') {
        const total = parseFloat(tempTotalModal.value) || 0;
        form.price_per_unit = total > 0 ? Math.round(total / stock) : 0;
    } else {
        const total = parseFloat(tempTotalJual.value) || 0;
        form.selling_price = total > 0 ? Math.round(total / stock) : 0;
    }
};

const calculateTotalPrice = (type) => {
    const stock = parseFloat(form.stock) || 0;
    
    if (type === 'modal') {
        const unitPrice = parseFloat(form.price_per_unit) || 0;
        tempTotalModal.value = stock > 0 && unitPrice > 0 ? stock * unitPrice : null;
    } else {
        const unitPrice = parseFloat(form.selling_price) || 0;
        tempTotalJual.value = stock > 0 && unitPrice > 0 ? stock * unitPrice : null;
    }
};

// Calculate total when stock changes for non-cable and non-klem items
watch(() => form.stock, (newVal) => {
    if (form.category !== 'Kabel' && form.category !== 'Paku Klem' && form.category !== 'Isolasi') {
        calculateTotalPrice('modal');
        calculateTotalPrice('jual');
    }
});

const handleCategoryChange = () => {
    if (form.category === 'Kabel') {
        form.unit = 'meter'; // Base unit for cable is ALWAYS meter
        calculateCableStock();
    } else if (form.category === 'Paku Klem') {
        form.unit = 'pcs'; // Base unit for paku klem is ALWAYS pcs
        calculatePackStock();
    } else if (form.category === 'Isolasi') {
        form.unit = 'cm'; // Base unit for isolasi is ALWAYS cm
        calculateIsolasiStock();
    } else {
        if (form.unit === 'meter' || form.unit === 'roll' || form.unit === 'cm') {
            form.unit = 'pcs';
        }
    }
};

const calculateCableStock = () => {
    const meter = parseFloat(form.meter_per_roll) || 0;
    const rolls = parseFloat(form.total_rolls) || 0;
    form.stock = meter * rolls;
};

const calculatePackStock = () => {
    const pcs = parseFloat(form.pcs_per_pack) || 0;
    const packs = parseFloat(form.total_packs) || 0;
    form.stock = pcs * packs;
};

const calculateIsolasiStock = () => {
    const cm = parseFloat(form.cm_per_pcs) || 0;
    const pcs = parseFloat(form.total_pieces) || 0;
    form.stock = cm * pcs;
};

const openModal = (item = null) => {
    if (item) {
        isEditing.value = true;
        editingId.value = item.id;
        form.name = item.name;
        form.supplier = item.supplier || '';
        form.category = item.category || '';
        form.unit = item.unit || 'pcs';
        form.meter_per_roll = item.meter_per_roll || 0;
        form.total_rolls = item.total_rolls || 0;
        form.pcs_per_pack = item.pcs_per_pack || 0;
        form.total_packs = item.total_packs || 0;
        form.cm_per_pcs = item.cm_per_pcs || 0;
        form.total_pieces = item.total_pieces || 0;
        form.stock = item.stock || 0;
        form.price_per_unit = item.price_per_unit || 0;
        form.selling_price = item.selling_price || 0;
        form.description = item.description || '';
        if (form.category !== 'Kabel' && form.category !== 'Paku Klem' && form.category !== 'Isolasi') {
            calculateTotalPrice('modal');
            calculateTotalPrice('jual');
        }
    } else {
        isEditing.value = false;
        editingId.value = null;
        form.reset();
        tempTotalModal.value = null;
        tempTotalJual.value = null;
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const openAddStockModal = (item) => {
    selectedMaterial.value = item;
    addStockForm.reset();
    addStockForm.added_stock = '';
    addStockForm.added_rolls = '';
    addStockForm.added_packs = '';
    addStockForm.price_per_unit = item.price_per_unit;
    addStockForm.selling_price = item.selling_price;
    showAddStockModal.value = true;
};

const closeAddStockModal = () => {
    showAddStockModal.value = false;
    selectedMaterial.value = null;
    addStockForm.reset();
};

const calculateAddedCableStock = () => {
    if (selectedMaterial.value?.category === 'Kabel') {
        const rolls = parseFloat(addStockForm.added_rolls) || 0;
        const meterPerRoll = parseFloat(selectedMaterial.value.meter_per_roll) || 0;
        addStockForm.added_stock = rolls * meterPerRoll;
    }
};

const calculateAddedPackStock = () => {
    if (selectedMaterial.value?.category === 'Paku Klem') {
        const packs = parseFloat(addStockForm.added_packs) || 0;
        const pcsPerPack = parseFloat(selectedMaterial.value.pcs_per_pack) || 0;
        addStockForm.added_stock = packs * pcsPerPack;
    }
};

const calculateAddedIsolasiStock = () => {
    if (selectedMaterial.value?.category === 'Isolasi') {
        const pieces = parseFloat(addStockForm.added_pieces) || 0;
        const cmPerPcs = parseFloat(selectedMaterial.value.cm_per_pcs) || 0;
        addStockForm.added_stock = pieces * cmPerPcs;
    }
};

const submitAddStock = () => {
    if (selectedMaterial.value) {
        addStockForm.post(`/materials/${selectedMaterial.value.id}/add-stock`, {
            onSuccess: () => closeAddStockModal(),
        });
    }
};

const submit = () => {
    // Pastikan stok terhitung
    if (form.category === 'Kabel') {
        calculateCableStock();
    } else if (form.category === 'Paku Klem') {
        calculatePackStock();
    } else if (form.category === 'Isolasi') {
        calculateIsolasiStock();
    }
    
    if (isEditing.value) {
        form.post(`/materials/${editingId.value}/update`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/materials', {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteData = (id) => {
    if (confirm('Yakin ingin menghapus data material ini?')) {
        router.post(`/materials/${id}/delete`);
    }
};
</script>
