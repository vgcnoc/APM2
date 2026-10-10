<template>
    <AppLayout title="Order Toko (Restock)">
        <div class="min-h-screen bg-slate-50/50 pb-20">
            <!-- Header Section -->
            <div class="relative bg-white/70 backdrop-blur-xl border-b border-gray-100 shadow-sm z-20">
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute -top-24 -right-24 w-96 h-96 bg-gradient-to-br from-indigo-100/40 to-blue-100/40 rounded-full blur-3xl"></div>
                </div>

                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                        <div>
                            <div class="inline-flex items-center justify-center p-2 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-xl mb-4 shadow-lg shadow-indigo-500/20">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <h1 class="text-3xl font-black text-gray-900 tracking-tight">Order Toko (Restock)</h1>
                            <p class="mt-2 text-sm text-gray-500 max-w-xl">
                                Order material dari area/wilayah ke toko. Sistem otomatis mengkonversi satuan order menjadi stok gudang.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <button id="btn-open-order" @click="openModal" class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-bold text-white transition-all duration-200 bg-gradient-to-r from-indigo-600 to-blue-500 border border-transparent rounded-xl shadow-md hover:shadow-lg hover:from-indigo-500 hover:to-blue-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 overflow-hidden">
                                <span class="absolute inset-0 w-full h-full -mt-1 rounded-lg opacity-30 bg-gradient-to-b from-transparent via-transparent to-black"></span>
                                <svg class="w-5 h-5 mr-2 -ml-1 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Buat Order Baru
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative z-10">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal &amp; No. Ref</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Area / Wilayah</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Detail Barang Masuk (Terkonversi)</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total Biaya</th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="trx in transactions.data" :key="trx.id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900">{{ formatDate(trx.date) }}</div>
                                        <div class="text-xs text-gray-500 mt-1 font-mono">{{ trx.transaction_number }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-indigo-50 text-indigo-700 text-sm font-semibold">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            {{ trx.technician_name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="space-y-2">
                                            <div v-for="item in trx.items" :key="item.id" class="flex items-center gap-2 text-sm">
                                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                                                <span class="font-medium text-gray-900">{{ item.material ? item.material.name : 'Unknown' }}</span>
                                                <span class="text-gray-400">&mdash;</span>
                                                <span class="font-bold text-emerald-600">+{{ formatNumber(item.quantity) }} {{ item.material ? item.material.unit : '' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900">Rp {{ formatNumber(trx.total_cost) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            <button @click="viewOrder(trx)" class="p-2 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors inline-flex items-center justify-center" title="Lihat Detail">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            </button>
                                            <button v-if="hasOnt(trx)" @click="sendOnt(trx.id)" class="p-2 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors inline-flex items-center justify-center" title="Kirim ke Data ONT">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
                                            </button>
                                            <button @click="editOrder(trx)" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors inline-flex items-center justify-center" title="Edit Order">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                            </button>
                                            <button @click="confirmDelete(trx.id)" class="text-gray-400 hover:text-red-600 hover:bg-red-50 p-2 rounded-lg transition-colors" title="Batalkan Transaksi">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="transactions.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">Belum ada riwayat Order Toko.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="transactions.links && transactions.links.length > 3" class="mt-6 flex justify-center">
                    <div class="inline-flex bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <template v-for="(link, k) in transactions.links" :key="k">
                            <div v-if="link.url === null" class="px-4 py-2 text-sm text-gray-400 border-r border-gray-100 last:border-0 bg-gray-50" v-html="link.label"></div>
                            <Link v-else :href="link.url" :class="['px-4 py-2 text-sm border-r border-gray-100 last:border-0 transition-colors hover:bg-indigo-50 hover:text-indigo-600', link.active ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-gray-600']" v-html="link.label" preserve-state preserve-scroll></Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Order Modal -->
        <TransitionRoot appear :show="isModalOpen" as="template">
            <Dialog @close="closeModal" class="relative z-50">
                <TransitionChild as="template" enter="duration-200 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-150 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" aria-hidden="true" />
                </TransitionChild>

                <div class="fixed inset-0 flex items-center justify-center p-4">
                    <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 scale-95 translate-y-4" enter-to="opacity-100 scale-100 translate-y-0" leave="duration-150 ease-in" leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
                        <DialogPanel class="relative w-full max-w-xl bg-white rounded-3xl shadow-2xl shadow-indigo-900/20 ring-1 ring-black/5 flex flex-col max-h-[92vh] overflow-hidden">
                            <!-- Decorative glow -->
                            <div class="pointer-events-none absolute -top-20 -right-20 w-64 h-64 rounded-full bg-gradient-to-br from-indigo-400/20 to-cyan-300/20 blur-3xl"></div>
                            <div class="pointer-events-none absolute -bottom-24 -left-16 w-56 h-56 rounded-full bg-gradient-to-tr from-blue-400/10 to-violet-300/10 blur-3xl"></div>

                            <!-- Header -->
                            <div class="relative px-6 pt-6 pb-4 flex items-start justify-between shrink-0">
                                <div class="flex items-start gap-3">
                                    <div class="p-2.5 rounded-2xl shadow-lg" :class="isViewing ? 'bg-gradient-to-br from-emerald-500 to-teal-400 text-white shadow-emerald-500/30' : (form.id ? 'bg-gradient-to-br from-blue-600 to-cyan-500 text-white shadow-blue-500/30' : 'bg-gradient-to-br from-indigo-600 to-blue-500 text-white shadow-indigo-500/30')">
                                        <svg v-if="isViewing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        <svg v-else-if="form.id" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                    </div>
                                    <div>
                                        <DialogTitle class="text-lg font-black text-gray-900 tracking-tight">{{ isViewing ? 'Detail Order Toko' : (form.id ? 'Edit Order Toko' : 'Buat Order Baru') }}</DialogTitle>
                                        <p class="text-sm text-slate-500">{{ isViewing ? 'Informasi detail mengenai order ini' : 'Order material dari area/wilayah ke toko' }}</p>
                                    </div>
                                </div>
                                <button id="btn-close-order" type="button" @click="closeModal" class="p-2 -mr-2 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>

                            <form @submit.prevent="submitForm" class="relative flex flex-col flex-1 overflow-hidden">
                                <div class="px-6 pb-4 overflow-y-auto flex-1 space-y-5">
                                    <!-- Area / Wilayah -->
                                    <div>
                                        <label for="order-area" class="block text-sm font-semibold text-gray-800 mb-1.5">Area / Wilayah <span class="text-rose-500">*</span></label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-indigo-500 pointer-events-none">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            </span>
                                            <select id="order-area" v-model="form.area_name" required class="w-full pl-10 pr-10 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all appearance-none">
                                                <option value="" disabled>Pilih area / wilayah</option>
                                                <option v-for="area in areas" :key="area.id" :value="area.name">{{ area.name }}</option>
                                            </select>
                                        </div>
                                        <p v-if="form.errors.area_name" class="mt-1 text-xs text-rose-500">{{ form.errors.area_name }}</p>
                                        <p v-if="!areas || areas.length === 0" class="mt-1 text-xs text-amber-600">Belum ada data area. Tambahkan area terlebih dahulu.</p>
                                    </div>

                                    <!-- Keranjang Belanja -->
                                    <div class="rounded-2xl border border-slate-200 bg-gradient-to-b from-slate-50 to-white p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                                                Keranjang Belanja
                                                <span v-if="form.items.length" class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-[11px] font-bold rounded-full bg-indigo-600 text-white animate-pop">{{ form.items.length }}</span>
                                            </h4>
                                            <button v-if="form.items.length" type="button" @click="form.items = []" class="text-xs font-medium text-slate-400 hover:text-rose-500 transition-colors">Kosongkan</button>
                                        </div>

                                        <!-- Product picker -->
                                        <div class="flex gap-2">
                                            <div class="relative flex-1" ref="pickerRef">
                                                <button id="order-product-picker" type="button" @click="togglePicker" class="w-full flex items-center justify-between gap-2 px-3.5 py-2.5 text-sm bg-white border border-slate-200 rounded-xl hover:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all text-left">
                                                    <span v-if="selectedMaterial" class="truncate text-gray-900 font-medium">{{ selectedMaterial.name }}</span>
                                                    <span v-else class="text-slate-400">Pilih produk...</span>
                                                    <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform" :class="pickerOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                                </button>

                                                <transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-100 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                                    <div v-if="pickerOpen" class="absolute z-30 mt-2 w-full bg-white rounded-xl shadow-xl ring-1 ring-black/5 overflow-hidden">
                                                        <div class="p-2 border-b border-slate-100">
                                                            <input id="order-product-search" ref="searchRef" v-model="search" type="text" placeholder="Cari nama / kategori..." class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500" @keydown.enter.prevent="pickFirst">
                                                        </div>
                                                        <ul class="max-h-60 overflow-y-auto py-1">
                                                            <li v-for="mat in filteredMaterials" :key="mat.id">
                                                                <button type="button" @click="selectMaterial(mat)" class="w-full px-3 py-2 flex items-center justify-between gap-3 text-left transition-colors hover:bg-indigo-50" :class="[inCart(mat.id) ? 'bg-slate-50' : '']">
                                                                    <div class="min-w-0">
                                                                        <div class="text-sm font-medium text-gray-900 truncate">{{ mat.name }}</div>
                                                                        <div class="text-[11px] text-slate-400">
                                                                            {{ mat.category || 'Umum' }}
                                                                            <span v-if="inCart(mat.id)" class="text-indigo-500 font-semibold ml-1">✓ Di Keranjang</span>
                                                                        </div>
                                                                    </div>
                                                                    <span class="shrink-0 text-[11px] font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600">
                                                                        {{ formatNumber(mat.stock) }} {{ mat.unit }}
                                                                    </span>
                                                                </button>
                                                            </li>
                                                            <li v-if="filteredMaterials.length === 0" class="px-3 py-6 text-center text-sm text-slate-400">Produk tidak ditemukan</li>
                                                        </ul>
                                                    </div>
                                                </transition>
                                            </div>
                                            <button id="btn-add-to-cart" type="button" @click="addToCart" :disabled="!selectedMaterial" class="px-4 py-2.5 text-sm font-bold rounded-xl border border-slate-200 bg-white text-gray-800 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 active:scale-95 disabled:opacity-50 disabled:hover:bg-white disabled:hover:text-gray-800 disabled:hover:border-slate-200 disabled:cursor-not-allowed transition-all">
                                                Tambah
                                            </button>
                                        </div>

                                        <!-- Cart items -->
                                        <TransitionGroup tag="ul" name="cart" class="mt-3 space-y-2">
                                            <li v-for="(item, index) in form.items" :key="item.material_id" class="group flex flex-col gap-2 p-3 bg-white border border-slate-200 rounded-xl hover:border-indigo-200 hover:shadow-sm transition-all">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-9 h-9 shrink-0 rounded-lg bg-gradient-to-br from-indigo-50 to-blue-100 text-indigo-600 flex items-center justify-center text-xs font-black uppercase">
                                                        {{ (materialById(item.material_id)?.name || '?').slice(0, 2) }}
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <div class="text-sm font-semibold text-gray-900 truncate">{{ materialById(item.material_id)?.name }}</div>
                                                        <div class="text-[11px] text-slate-400 truncate">
                                                            <template v-if="conversionText(item)">= <span class="font-semibold text-emerald-600">{{ conversionText(item) }}</span> masuk stok</template>
                                                            <template v-else>{{ materialById(item.material_id)?.category || 'Umum' }}</template>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center rounded-lg border border-slate-200 overflow-hidden" :class="{'border-rose-300 ring-1 ring-rose-200': !isViewing && convertedQty(item) > Number(materialById(item.material_id)?.stock)}">
                                                        <button v-if="!isViewing" type="button" @click="decQty(item)" class="px-2 py-1.5 text-slate-500 hover:bg-slate-100">−</button>
                                                        <input :id="`order-qty-${index}`" v-model.number="item.quantity" type="number" min="0.01" step="any" required :disabled="isViewing" class="w-14 py-1.5 text-center text-sm border-0 focus:ring-0 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" :class="{'text-rose-600 font-bold': !isViewing && convertedQty(item) > Number(materialById(item.material_id)?.stock)}">
                                                        <button v-if="!isViewing" type="button" @click="item.quantity = (Number(item.quantity) || 0) + 1" class="px-2 py-1.5 text-slate-500 hover:bg-slate-100">+</button>
                                                    </div>
                                                    <select :id="`order-unit-${index}`" v-model="item.purchase_unit" :disabled="isViewing" class="w-24 py-1.5 pl-2 pr-7 text-xs font-medium bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500" :class="{'opacity-75 cursor-not-allowed': isViewing}">
                                                        <option v-for="u in unitOptions(materialById(item.material_id))" :key="u.value" :value="u.value">{{ u.label }}</option>
                                                    </select>
                                                    <button v-if="!isViewing" type="button" @click="form.items.splice(index, 1)" class="p-1.5 text-slate-300 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-colors" title="Hapus">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                    </button>
                                                </div>
                                                <div v-if="convertedQty(item) > Number(materialById(item.material_id)?.stock)" class="text-[11px] text-rose-500 font-semibold bg-rose-50 px-2 py-1 rounded-md ml-12">
                                                    Ditolak: Order melebihi stok gudang (Tersedia: {{ formatNumber(materialById(item.material_id)?.stock) }} {{ materialById(item.material_id)?.unit }})
                                                </div>
                                            </li>
                                        </TransitionGroup>
                                        <p v-if="form.errors.items" class="mt-2 text-xs text-rose-500">{{ form.errors.items }}</p>
                                    </div>

                                    <!-- Catatan -->
                                    <div>
                                        <label for="order-notes" class="block text-sm font-semibold text-gray-800 mb-1.5">Catatan</label>
                                        <textarea id="order-notes" v-model="form.notes" :disabled="isViewing" rows="3" placeholder="Tambahkan catatan untuk order ini (opsional)" class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all resize-y"></textarea>
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div class="px-6 py-4 border-t border-slate-100 bg-white/80 backdrop-blur flex items-center justify-between gap-3 shrink-0">
                                    <div class="text-xs text-slate-400">
                                        <span v-if="form.items.length">{{ form.items.length }} produk di keranjang</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button id="btn-cancel-order" type="button" @click="closeModal" class="px-5 py-2.5 text-sm font-bold text-gray-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">{{ isViewing ? 'Tutup' : 'Batal' }}</button>
                                        <button v-if="!isViewing" id="btn-submit-order" type="submit" :disabled="form.processing || form.items.length === 0 || !form.area_name || hasOverstockItems" class="inline-flex items-center px-5 py-2.5 text-sm font-bold text-white rounded-xl bg-gradient-to-r from-indigo-700 to-blue-600 shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 transition-all">
                                            <svg v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                            {{ form.id ? 'Simpan Perubahan' : 'Kirim Order' }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </TransitionRoot>
    </AppLayout>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue';

const props = defineProps({
    transactions: Object,
    materials: Array,
    areas: { type: Array, default: () => [] },
});

const isModalOpen = ref(false);
const pickerOpen = ref(false);
const search = ref('');
const selectedMaterial = ref(null);
const pickerRef = ref(null);
const searchRef = ref(null);
const isViewing = ref(false);

const form = useForm({
    id: null,
    area_name: '',
    date: new Date().toISOString().slice(0, 10),
    notes: '',
    items: [],
});

const materialById = (id) => props.materials.find(m => m.id === id);
const inCart = (id) => form.items.some(i => i.material_id === id);

const filteredMaterials = computed(() => {
    // Only show materials that have stock > 0
    const availableMaterials = props.materials.filter(m => Number(m.stock) > 0);
    
    const q = search.value.trim().toLowerCase();
    if (!q) return availableMaterials;
    
    return availableMaterials.filter(m =>
        (m.name || '').toLowerCase().includes(q) || (m.category || '').toLowerCase().includes(q)
    );
});

const hasOverstockItems = computed(() => {
    return form.items.some(item => {
        const mat = materialById(item.material_id);
        if (!mat) return false;
        return convertedQty(item) > Number(mat.stock);
    });
});

// Satuan order yang tersedia per material (selaras dengan konversi di PurchaseOrderController)
const unitOptions = (mat) => {
    if (!mat) return [{ value: 'base', label: 'pcs' }];
    const base = { value: 'base', label: mat.unit || 'pcs' };
    if (mat.category === 'Isolasi' || mat.name.toLowerCase().includes('isolasi')) {
        return [{ value: 'pcs', label: 'Pcs' }, { value: 'base', label: mat.unit === 'cm' ? 'cm' : 'cm' }];
    }
    if (mat.category === 'Paku Klem' || mat.name.toLowerCase().includes('klem')) {
        return [{ value: 'pack', label: 'Bungkus' }, { value: 'base', label: mat.unit || 'pcs' }];
    }
    if (Number(mat.meter_per_roll) > 0) return [{ value: 'roll', label: 'Roll' }, base];
    if (Number(mat.pcs_per_pack) > 0) return [{ value: 'pack', label: 'Pack' }, base];
    return [base];
};

const convertedQty = (item) => {
    const mat = materialById(item.material_id);
    const qty = Number(item.quantity) || 0;
    if (!mat) return qty;
    if (item.purchase_unit === 'roll' && Number(mat.meter_per_roll) > 0) return qty * Number(mat.meter_per_roll);
    if (mat.category === 'Isolasi' && item.purchase_unit === 'pcs') {
        const cpp = Number(mat.cm_per_pcs) > 0 ? Number(mat.cm_per_pcs) : 0;
        return qty * cpp;
    }
    if (item.purchase_unit === 'pack' && Number(mat.pcs_per_pack) > 0) return qty * Number(mat.pcs_per_pack);
    return qty;
};

const conversionText = (item) => {
    if (item.purchase_unit === 'base') return '';
    const mat = materialById(item.material_id);
    return `${formatNumber(convertedQty(item))} ${mat?.unit || ''}`;
};

const togglePicker = async () => {
    pickerOpen.value = !pickerOpen.value;
    if (pickerOpen.value) {
        search.value = '';
        await nextTick();
        searchRef.value?.focus();
    }
};

const selectMaterial = (mat) => {
    selectedMaterial.value = mat;
    pickerOpen.value = false;
};

const pickFirst = () => {
    if (filteredMaterials.value.length) selectMaterial(filteredMaterials.value[0]);
};

const addToCart = () => {
    const mat = selectedMaterial.value;
    if (!mat) return;
    const existing = form.items.find(i => i.material_id === mat.id);
    if (existing) {
        existing.quantity = (Number(existing.quantity) || 0) + 1;
    } else {
        form.items.push({ material_id: mat.id, purchase_unit: unitOptions(mat)[0].value, quantity: 1 });
    }
    selectedMaterial.value = null;
};

const decQty = (item) => {
    const q = (Number(item.quantity) || 0) - 1;
    item.quantity = q > 0 ? q : 1;
};

const onClickOutside = (e) => {
    if (pickerOpen.value && pickerRef.value && !pickerRef.value.contains(e.target)) {
        pickerOpen.value = false;
    }
};
onMounted(() => document.addEventListener('mousedown', onClickOutside));
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside));

const formatNumber = (num) => new Intl.NumberFormat('id-ID').format(num || 0);

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(date);
};

const openModal = () => {
    form.reset();
    form.clearErrors();
    form.id = null;
    form.date = new Date().toISOString().slice(0, 10);
    form.items = [];
    selectedMaterial.value = null;
    isViewing.value = false;
    isModalOpen.value = true;
};

const viewOrder = (trx) => {
    form.reset();
    form.clearErrors();
    form.id = trx.id;
    form.area_name = trx.technician_name;
    form.date = trx.date;
    form.notes = trx.notes;
    form.items = trx.items.map(item => ({
        material_id: item.material_id,
        purchase_unit: 'base',
        quantity: item.quantity,
        price: item.price_per_unit
    }));
    isViewing.value = true;
    isModalOpen.value = true;
};

const hasOnt = (trx) => {
    return trx.items.some(item => {
        if (item.material && item.material.category === 'ONT') {
            const alreadySent = item.onts ? item.onts.length : 0;
            return alreadySent < item.quantity;
        }
        return false;
    });
};

const sendOnt = (id) => {
    if (confirm('Kirim semua barang ONT dari order ini ke Data ONT?')) {
        router.post(route('order-toko.send-ont', id), {}, {
            preserveScroll: true,
        });
    }
};

const editOrder = (trx) => {
    form.reset();
    form.clearErrors();
    form.id = trx.id;
    form.area_name = trx.technician_name;
    form.date = trx.date;
    form.notes = trx.notes;
    form.items = trx.items.map(item => ({
        material_id: item.material_id,
        purchase_unit: 'base',
        quantity: item.quantity,
        price: item.price_per_unit
    }));
    isViewing.value = false;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    isViewing.value = false;
    setTimeout(() => {
        form.reset();
        form.id = null;
        form.clearErrors();
        search.value = '';
        pickerOpen.value = false;
    }, 200);
};

const submitForm = () => {
    if (form.items.length === 0) return;
    
    if (form.id) {
        form.put(`/order-toko/${form.id}`, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/order-toko', {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const confirmDelete = (id) => {
    if (confirm('Yakin ingin membatalkan transaksi ini? Stok barang akan dikurangi kembali!')) {
        useForm({}).delete(`/order-toko/${id}`, { preserveScroll: true });
    }
};
</script>

<style scoped>
.cart-enter-active, .cart-leave-active { transition: all 0.25s ease; }
.cart-enter-from { opacity: 0; transform: translateY(-6px) scale(0.98); }
.cart-leave-to { opacity: 0; transform: translateX(16px); }
.animate-pop { animation: pop 0.3s ease; }
@keyframes pop { 0% { transform: scale(0.6); } 60% { transform: scale(1.15); } 100% { transform: scale(1); } }
</style>
