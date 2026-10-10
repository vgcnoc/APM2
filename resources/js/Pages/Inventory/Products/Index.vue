<template>
    <AppLayout title="Master Data Produk">
        <div class="min-h-screen bg-slate-50/50 pb-20">
            <!-- Header Section with Glassmorphism -->
            <div class="relative bg-white/70 backdrop-blur-xl border-b border-gray-100 shadow-sm z-20">
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute -top-24 -right-24 w-96 h-96 bg-gradient-to-br from-emerald-100/40 to-teal-100/40 rounded-full blur-3xl"></div>
                    <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-gradient-to-tr from-blue-100/40 to-cyan-100/40 rounded-full blur-3xl"></div>
                </div>

                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                        <div>
                            <div class="inline-flex items-center justify-center p-2 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl mb-4 shadow-lg shadow-emerald-500/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <h1 class="text-3xl font-black text-gray-900 tracking-tight">Katalog Produk</h1>
                            <p class="mt-2 text-sm text-gray-500 max-w-xl">
                                Kelola master data barang, material, dan perangkat. Sistem cerdas otomatis mengkonversi satuan beli (Roll/Pack) ke satuan pakai (Meter/Pcs).
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <button @click="openModal()" class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-bold text-white transition-all duration-200 bg-gradient-to-r from-emerald-600 to-teal-500 border border-transparent rounded-xl shadow-md hover:shadow-lg hover:from-emerald-500 hover:to-teal-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 overflow-hidden">
                                <span class="absolute inset-0 w-full h-full -mt-1 rounded-lg opacity-30 bg-gradient-to-b from-transparent via-transparent to-black"></span>
                                <svg class="w-5 h-5 mr-2 -ml-1 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Tambah Produk Baru
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative z-10">
                <!-- Filters -->
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col sm:flex-row gap-4 items-center justify-between mb-8">
                    <div class="relative w-full sm:w-96 group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400 group-focus-within:text-emerald-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" v-model="search" @input="debouncedSearch" class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm transition-all" placeholder="Cari nama barang, kategori, atau supplier..." />
                    </div>

                    <div class="flex items-center gap-3 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
                        <button @click="setCategory('all')" :class="['px-4 py-2 rounded-lg text-sm font-semibold transition-all whitespace-nowrap', category === 'all' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600 hover:bg-gray-200']">
                            Semua Kategori
                        </button>
                        <button v-for="cat in categories" :key="cat" @click="setCategory(cat)" :class="['px-4 py-2 rounded-lg text-sm font-semibold transition-all whitespace-nowrap', category === cat ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600 hover:bg-gray-200']">
                            {{ cat }}
                        </button>
                    </div>
                </div>

                <!-- Products List -->
                <div v-if="products.data.length > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-gray-100">
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Nama & Kategori</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right whitespace-nowrap">Total Stok</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Smart Unit</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center whitespace-nowrap">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="product in products.data" :key="product.id" class="border-b border-gray-200 last:border-0 hover:bg-slate-50/50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-gray-900 group-hover:text-emerald-600 transition-colors">{{ product.name }}</span>
                                            <div class="flex items-center gap-2 mt-1.5">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100 uppercase tracking-wider">
                                                    {{ product.category || 'Uncategorized' }}
                                                </span>
                                                <span class="text-xs text-gray-500 flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                                    {{ product.supplier || '-' }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex flex-col items-end justify-center">
                                            <span class="text-base font-black text-slate-800">{{ formatNum(Number(product.stock)) }}</span>
                                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ product.unit }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-1.5 text-emerald-600">
                                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                            <span class="text-xs font-medium text-slate-600 leading-snug" v-html="formatSmartUnit(product)"></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button @click="viewProduct(product)" class="p-2 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors inline-flex items-center justify-center" title="Lihat Produk">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            </button>
                                            <button @click="openModal(product)" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors inline-flex items-center justify-center" title="Edit Produk">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                            </button>
                                            <button @click="deleteProductDirect(product)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors inline-flex items-center justify-center" title="Hapus Produk">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-24 px-6 bg-white rounded-3xl border border-gray-100 border-dashed shadow-sm">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-slate-50 mb-6 border border-slate-100">
                        <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Belum ada data produk</h3>
                    <p class="text-gray-500 max-w-sm mx-auto mb-8">Katalog Anda masih kosong atau tidak ada produk yang cocok dengan pencarian.</p>
                    <button @click="openModal()" class="inline-flex items-center justify-center px-6 py-3 text-sm font-bold text-emerald-700 bg-emerald-100 rounded-xl hover:bg-emerald-200 transition-colors">
                        <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Buat Produk Pertama
                    </button>
                </div>

                <!-- Pagination -->
                <div v-if="products.links && products.links.length > 3" class="mt-8 flex justify-center">
                    <div class="inline-flex bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <template v-for="(link, k) in products.links" :key="k">
                            <div v-if="link.url === null" class="px-4 py-2 text-sm text-gray-400 border-r border-gray-100 last:border-0 bg-gray-50" v-html="link.label"></div>
                            <Link v-else :href="link.url" :class="['px-4 py-2 text-sm border-r border-gray-100 last:border-0 transition-colors hover:bg-emerald-50 hover:text-emerald-600', link.active ? 'bg-emerald-50 text-emerald-700 font-bold' : 'text-gray-600']" v-html="link.label" preserve-state preserve-scroll></Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Dialog :open="isModalOpen" @close="closeModal" class="relative z-50">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" aria-hidden="true" />
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <DialogPanel class="w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-hidden transform transition-all">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                        <DialogTitle class="text-lg font-black text-gray-900 flex items-center gap-2">
                            <div class="p-1.5 bg-emerald-100 rounded-lg text-emerald-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" v-if="!form.id" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" v-else />
                                </svg>
                            </div>
                            {{ form.id ? 'Edit Data Produk' : 'Tambah Produk Baru' }}
                        </DialogTitle>
                        <button @click="closeModal" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <form @submit.prevent="submitForm">
                        <div class="p-6 max-h-[70vh] overflow-y-auto">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Basic Info -->
                                <div class="col-span-1 md:col-span-2">
                                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Informasi Dasar</h4>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-bold text-gray-700 mb-1">Nama Barang / Material <span class="text-red-500">*</span></label>
                                            <input type="text" v-model="form.name" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" placeholder="Contoh: Kabel Drop Core 1 Core" required>
                                            <div v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-bold text-gray-700 mb-1">Kategori <span class="text-red-500">*</span></label>
                                                <div v-if="!isNewCategory">
                                                    <select v-model="form.category" @change="checkNewCategory" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" required>
                                                        <option value="" disabled>Pilih Kategori...</option>
                                                        <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                                                        <option value="new_category_option" class="font-bold text-emerald-600">+ Tambah Kategori Baru...</option>
                                                    </select>
                                                </div>
                                                <div v-else class="flex gap-2">
                                                    <input type="text" v-model="form.category" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" placeholder="Ketik kategori baru..." required autofocus />
                                                    <button type="button" @click="cancelNewCategory" class="px-3 py-2 bg-gray-100 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-200 transition-colors">
                                                        Batal
                                                    </button>
                                                </div>
                                                <div v-if="form.errors.category" class="text-xs text-red-500 mt-1">{{ form.errors.category }}</div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-bold text-gray-700 mb-1">Supplier / Merk</label>
                                                <input type="text" v-model="form.supplier" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 transition-all" placeholder="Contoh: ZTE / FiberStar">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Specialized Unit Configs based on Category -->
                                <div class="col-span-1 md:col-span-2">
                                    
                                    <!-- Kalkulator Kabel (Khusus Kabel & Patchcord) -->
                                    <div v-if="['Kabel', 'Patchcord'].includes(form.category)" class="bg-blue-50/50 rounded-2xl border border-blue-200 overflow-hidden shadow-sm">
                                        <div class="bg-blue-100/50 px-5 py-3 border-b border-blue-200 flex items-center gap-2">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                            <h4 class="text-sm font-bold text-blue-800">Kalkulator Kabel (Roll ↔ Meter)</h4>
                                        </div>
                                        <div class="p-5">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                                <div>
                                                    <label class="block text-sm font-bold text-gray-700 mb-1">Meter per Roll</label>
                                                    <input type="number" step="0.01" v-model="form.meter_per_roll" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 bg-white" placeholder="0">
                                                </div>
                                                <div v-if="!form.id">
                                                    <label class="block text-sm font-bold text-gray-700 mb-1">Jumlah Roll (Stok Awal)</label>
                                                    <input type="number" step="0.01" v-model="initialRolls" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 bg-white" placeholder="0">
                                                    <p class="text-xs text-blue-600 mt-1 font-medium">Otomatis terkonversi: {{ (form.meter_per_roll * initialRolls) || 0 }} Meter</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Kalkulator Isolasi (Pack → CM) -->
                                    <div v-else-if="isIsolasi" class="bg-amber-50/50 rounded-2xl border border-amber-200 overflow-hidden shadow-sm">
                                        <div class="bg-amber-100/50 px-5 py-3 border-b border-amber-200 flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                                <h4 class="text-sm font-bold text-amber-800">Kalkulator Isolasi (Pack → CM)</h4>
                                            </div>
                                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-white text-amber-700 border border-amber-200">Satuan stok: CM</span>
                                        </div>
                                        <div class="p-5 space-y-4">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                                <div>
                                                    <label class="block text-sm font-bold text-gray-700 mb-1">Isi per Pack</label>
                                                    <div class="relative">
                                                        <input id="isolasi-pcs-per-pack" type="number" step="0.01" min="0" v-model="form.pcs_per_pack" class="w-full pl-4 pr-16 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 bg-white" placeholder="Cth: 10">
                                                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-xs font-bold text-gray-400 pointer-events-none">gulung</span>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-bold text-gray-700 mb-1">Panjang per Gulung</label>
                                                    <div class="relative">
                                                        <input id="isolasi-cm-per-pcs" type="number" step="0.01" min="0" v-model="form.cm_per_pcs" class="w-full pl-4 pr-12 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 bg-white" placeholder="Cth: 1000">
                                                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-xs font-bold text-gray-400 pointer-events-none">cm</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-between rounded-xl bg-white border border-amber-200 px-4 py-2.5">
                                                <span class="text-sm text-gray-600">1 Pack =</span>
                                                <span class="text-base font-black text-amber-700">{{ formatNum(cmPerPack) }} cm</span>
                                            </div>
                                            <div v-if="!form.id">
                                                <label class="block text-sm font-bold text-gray-700 mb-1">Jumlah Pack (Stok Awal)</label>
                                                <input id="isolasi-initial-packs" type="number" step="0.01" min="0" v-model="initialPacks" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 bg-white" placeholder="0">
                                                <p class="text-xs text-amber-700 mt-1 font-medium">Otomatis terkonversi: {{ formatNum((Number(initialPacks) || 0) * cmPerPack) }} cm</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Konfigurasi Satuan Generik (Untuk kategori lain) -->
                                    <div v-else>
                                        <div class="flex gap-4 mb-4">
                                            <div class="w-full md:w-1/2">
                                                <label class="block text-sm font-bold text-gray-700 mb-1">Satuan Pemakaian <span class="text-red-500">*</span></label>
                                                <select v-model="form.unit" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 bg-white" required>
                                                    <option value="pcs">Pcs (Satuan utuh)</option>
                                                    <option value="meter">Meter</option>
                                                    <option value="cm">Centimeter (CM)</option>
                                                    <option value="pack">Pack</option>
                                                    <option value="roll">Roll</option>
                                                </select>
                                            </div>
                                            <div v-if="!form.id" class="w-full md:w-1/2">
                                                <label class="block text-sm font-bold text-gray-700 mb-1">Stok Awal ({{ form.unit }})</label>
                                                <input type="number" step="0.01" v-model="form.stock" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 bg-white" placeholder="0">
                                            </div>
                                        </div>

                                        <!-- Konfigurasi Pack Khusus Aksesoris/Klem/Konektor dll -->
                                        <div v-if="isPackCategory">
                                            <h4 class="text-xs font-bold text-emerald-600 uppercase tracking-wider mb-4 border-b border-emerald-100 pb-2 flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                                Konfigurasi Satuan Cerdas (Smart Unit)
                                            </h4>
                                            
                                            <div class="bg-emerald-50/50 rounded-2xl p-5 border border-emerald-100 space-y-4">
                                                <p class="text-xs text-emerald-700 font-medium">Jika barang ini dibeli dalam bentuk paketan besar (Pack), tentukan nilai konversinya agar saat Order Toko, sistem otomatis memecahnya menjadi Base Unit.</p>
                                                
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                                                    <div>
                                                        <label class="block text-xs font-bold text-gray-600 mb-1">Jika beli per PACK</label>
                                                        <div class="relative">
                                                            <input type="number" step="0.01" v-model="form.pcs_per_pack" class="w-full pl-3 pr-16 py-2 text-sm border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 bg-white" placeholder="Cth: 50">
                                                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                                <span class="text-xs font-bold text-gray-400">Pcs</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Pricing & Status -->
                                <div class="col-span-1 md:col-span-2 mt-4">
                                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Manajemen Harga & Status</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div>
                                            <label class="block text-sm font-bold text-gray-700 mb-1">{{ ['Kabel', 'Patchcord'].includes(form.category) ? 'Harga Modal (per roll)' : (isIsolasi ? 'Harga Modal (per cm)' : 'Harga Modal / Beli') }}</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                    <span class="text-gray-500 sm:text-sm">Rp</span>
                                                </div>
                                                <input type="number" step="0.01" v-model="form.price_per_unit" class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 transition-all bg-white" placeholder="0">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-bold text-gray-700 mb-1">{{ ['Kabel', 'Patchcord'].includes(form.category) ? 'Harga Jual (per roll)' : (isIsolasi ? 'Harga Jual (per cm)' : 'Harga Jual') }}</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                    <span class="text-gray-500 sm:text-sm">Rp</span>
                                                </div>
                                                <input type="number" step="0.01" v-model="form.selling_price" class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 transition-all bg-white" placeholder="0">
                                            </div>
                                        </div>
                                        <div class="flex items-end pb-2">
                                            <label class="flex items-center cursor-pointer">
                                                <div class="relative">
                                                    <input type="checkbox" v-model="form.is_active" class="sr-only">
                                                    <div :class="['block w-10 h-6 rounded-full transition-colors', form.is_active ? 'bg-emerald-500' : 'bg-gray-300']"></div>
                                                    <div :class="['dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform', form.is_active ? 'transform translate-x-4' : '']"></div>
                                                </div>
                                                <div class="ml-3 text-sm font-bold text-gray-700">
                                                    Produk Aktif
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Device Configuration (Tampil jika kategori berkaitan dengan perangkat) -->
                                <div v-if="['ONT', 'Router', 'Switch', 'OLT'].includes(form.category)" class="col-span-1 md:col-span-2 mt-4">
                                    <h4 class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-4 border-b border-blue-100 pb-2 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" /></svg>
                                        Konfigurasi Perangkat Lanjutan
                                    </h4>
                                    
                                    <div class="bg-blue-50/50 rounded-2xl p-5 border border-blue-100">
                                        <p class="text-xs text-blue-700 leading-relaxed mb-4">
                                            Aktifkan fitur ini jika perangkat memerlukan pencatatan Serial Number (S/N) atau MAC Address saat transaksi (stok masuk/keluar) maupun saat instalasi pelanggan.
                                        </p>
                                        
                                        <div class="flex flex-col sm:flex-row gap-6">
                                            <label class="flex items-center cursor-pointer group">
                                                <div class="relative">
                                                    <input type="checkbox" v-model="form.requires_sn" class="sr-only">
                                                    <div :class="['block w-12 h-7 rounded-full transition-colors', form.requires_sn ? 'bg-blue-600' : 'bg-gray-300']"></div>
                                                    <div :class="['dot absolute left-1 top-1 bg-white w-5 h-5 rounded-full transition-transform', form.requires_sn ? 'transform translate-x-5' : '']"></div>
                                                </div>
                                                <div class="ml-3">
                                                    <span class="block text-sm font-bold text-gray-800">Wajib Serial Number (S/N)</span>
                                                    <span class="block text-xs text-gray-500">Lacak SN tiap unit</span>
                                                </div>
                                            </label>

                                            <label class="flex items-center cursor-pointer group">
                                                <div class="relative">
                                                    <input type="checkbox" v-model="form.requires_mac" class="sr-only">
                                                    <div :class="['block w-12 h-7 rounded-full transition-colors', form.requires_mac ? 'bg-blue-600' : 'bg-gray-300']"></div>
                                                    <div :class="['dot absolute left-1 top-1 bg-white w-5 h-5 rounded-full transition-transform', form.requires_mac ? 'transform translate-x-5' : '']"></div>
                                                </div>
                                                <div class="ml-3">
                                                    <span class="block text-sm font-bold text-gray-800">Wajib MAC Address</span>
                                                    <span class="block text-xs text-gray-500">Lacak MAC tiap unit</span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-span-1 md:col-span-2 mt-4">
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Keterangan / Catatan Tambahan</label>
                                    <textarea v-model="form.description" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 transition-all"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between rounded-b-3xl">
                            <button v-if="form.id" type="button" @click="confirmDelete" class="text-sm font-bold text-red-600 hover:text-red-700 hover:bg-red-50 px-4 py-2 rounded-xl transition-colors">
                                Hapus Produk
                            </button>
                            <div v-else></div>
                            
                            <div class="flex items-center gap-3">
                                <button type="button" @click="closeModal" class="px-5 py-2.5 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 transition-colors">
                                    Batal
                                </button>
                                <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center px-6 py-2.5 text-sm font-bold text-white transition-all bg-emerald-600 border border-transparent rounded-xl shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg v-if="form.processing" class="w-4 h-4 mr-2 -ml-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    {{ form.id ? 'Simpan Perubahan' : 'Tambahkan Produk' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </DialogPanel>
            </div>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue';

import { debounce } from 'lodash';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    products: Object,
    categories: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const category = ref(props.filters.category || 'all');
const isNewCategory = ref(false);
const initialRolls = ref(0);
const initialPacks = ref(0);

const isIsolasi = computed(() => (form.category || '').toLowerCase().includes('isolasi') || (form.name || '').toLowerCase().includes('isolasi'));

const cmPerPack = computed(() => {
    const ppp = Number(form.pcs_per_pack) > 0 ? Number(form.pcs_per_pack) : 1;
    const cpp = Number(form.cm_per_pcs) > 0 ? Number(form.cm_per_pcs) : 0;
    return ppp * cpp;
});

const formatNum = (n) => new Intl.NumberFormat('id-ID').format(n || 0);

const isPackCategory = computed(() => {
    if (!form.category) return false;
    if (isNewCategory.value) return true;
    const cat = form.category.toLowerCase();
    return ['aksesoris', 'klem', 'konektor', 'splitter', 'fast', 'adapter'].some(k => cat.includes(k));
});

const checkNewCategory = (e) => {
    if (e.target.value === 'new_category_option') {
        isNewCategory.value = true;
        form.category = '';
    }
};

const cancelNewCategory = () => {
    isNewCategory.value = false;
    form.category = '';
};

const debouncedSearch = debounce(() => {
    router.get('/produk', { search: search.value, category: category.value !== 'all' ? category.value : null }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300);

const setCategory = (cat) => {
    category.value = cat;
    debouncedSearch();
};

const isModalOpen = ref(false);

const form = useForm({
    id: null,
    name: '',
    category: '',
    supplier: '',
    unit: 'pcs',
    meter_per_roll: null,
    pcs_per_pack: null,
    cm_per_pcs: null,
    stock: null,
    price_per_unit: 0,
    selling_price: 0,
    is_active: true,
    requires_sn: false,
    requires_mac: false,
    description: '',
});

const formatSmartUnit = (product) => {
    let html = [];
    if ((product.category || '').toLowerCase().includes('isolasi')) {
        const ppp = product.pcs_per_pack > 0 ? Number(product.pcs_per_pack) : 1;
        const cpp = product.cm_per_pcs > 0 ? Number(product.cm_per_pcs) : 50;
        return `1 Pack = <b>${formatNum(ppp * cpp)} CM</b>`;
    }
    if (product.meter_per_roll > 0) html.push(`1 Roll = <b>${product.meter_per_roll} M</b>`);
    if (product.pcs_per_pack > 0) html.push(`1 Pack = <b>${product.pcs_per_pack} Pcs</b>`);
    if (product.cm_per_pcs > 0) html.push(`1 Pcs = <b>${product.cm_per_pcs} CM</b>`);
    
    if (html.length === 0) return `1:1 (${product.unit})`;
    return html.join(' <span class="mx-1 text-slate-300">|</span> ');
};

const openModal = (product = null) => {
    if (product) {
        form.id = product.id;
        form.name = product.name;
        form.category = product.category;
        form.supplier = product.supplier;
        form.unit = product.unit;
        form.meter_per_roll = product.meter_per_roll ? Number(product.meter_per_roll) : null;
        form.pcs_per_pack = product.pcs_per_pack ? Number(product.pcs_per_pack) : null;
        form.cm_per_pcs = product.cm_per_pcs ? Number(product.cm_per_pcs) : null;
        form.price_per_unit = product.price_per_unit ? Number(product.price_per_unit) : 0;
        form.selling_price = product.selling_price ? Number(product.selling_price) : 0;
        form.is_active = product.is_active ?? true;
        form.requires_sn = product.requires_sn || false;
        form.requires_mac = product.requires_mac || false;
        form.description = product.description;
        isNewCategory.value = !props.categories.includes(product.category);
    } else {
        form.reset();
        form.id = null;
        isNewCategory.value = false;
        initialRolls.value = 0;
        initialPacks.value = 0;
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    setTimeout(() => form.reset(), 200);
};

const submitForm = () => {
    // If it's a cable and we are setting initial rolls, calculate the stock
    if (!form.id && ['Kabel', 'Patchcord'].includes(form.category)) {
        form.unit = 'meter';
        if (initialRolls.value > 0 && form.meter_per_roll > 0) {
            form.stock = parseFloat(initialRolls.value) * parseFloat(form.meter_per_roll);
        }
    }

    // Isolasi: satuan stok selalu CM, stok awal dihitung dari jumlah pack
    if (isIsolasi.value) {
        form.unit = 'cm';
        if (!form.id && Number(initialPacks.value) > 0 && cmPerPack.value > 0) {
            form.stock = Number(initialPacks.value) * cmPerPack.value;
        }
    }

    if (form.id) {
        form.put(`/produk/${form.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/produk', {
            onSuccess: () => closeModal(),
        });
    }
};

const confirmDelete = () => {
    if (confirm('Yakin ingin menghapus produk ini? Semua histori transaksi terkait produk ini mungkin akan terdampak!')) {
        form.delete(`/produk/${form.id}`, {
            onSuccess: () => closeModal(),
        });
    }
};

const viewProduct = (product) => {
    router.get(`/produk/${product.id}`);
};

const deleteProductDirect = (product) => {
    if (confirm(`Yakin ingin menghapus produk "${product.name}"? Semua histori transaksi terkait produk ini mungkin akan terdampak!`)) {
        router.delete(`/produk/${product.id}`, {
            preserveScroll: true,
        });
    }
};
</script>
