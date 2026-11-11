<template>
    <AppLayout title="Laporan Pajak Pelanggan" subtitle="Kalkulasi PPN, BHP, dan USO Pelanggan">
        <div id="print-section" class="w-full max-w-7xl mx-auto min-w-0 space-y-5 sm:space-y-6 pb-8">
            
            <!-- Alert Banner jika Mode Privasi / Masking Aktif -->
            <div v-if="masking_info?.is_masked" class="p-4 bg-gradient-to-r from-purple-900 via-indigo-900 to-slate-900 text-white rounded-3xl border border-purple-700/60 shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center text-xl shrink-0 shadow-inner">
                        🔒
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h4 class="text-xs font-black uppercase tracking-wider text-purple-200">Mode Penyamaran Data Aktif</h4>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-400 text-emerald-950 uppercase tracking-wide">
                                {{ masking_info.mode === 'omset' ? 'Target Omset' : 'Target Pelanggan' }}
                            </span>
                            <span v-if="masking_info.preview_mode" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-400 text-amber-950 uppercase">
                                Pratinjau Super Admin
                            </span>
                        </div>
                        <p class="text-xs text-purple-100 mt-1 leading-relaxed">
                            Data dibatasi menampilkan <b>{{ customers.total }} pelanggan</b> dengan akumulasi omset sebesar <b>{{ formatCurrency(totals.grand_total) }}</b>.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 shrink-0 self-end sm:self-center">
                    <button 
                        v-if="masking_info?.is_super_admin" 
                        type="button"
                        @click="togglePreviewSession" 
                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all shadow-xs"
                        :class="masking_info.preview_mode ? 'bg-amber-400 text-amber-950 hover:bg-amber-300' : 'bg-white text-indigo-950 hover:bg-indigo-50'">
                        {{ masking_info.preview_mode ? 'Tutup Pratinjau (Lihat Asli 100%)' : 'Lihat Data Asli 100%' }}
                    </button>
                    <button 
                        v-if="can_manage_masking" 
                        type="button"
                        @click="openMaskingModal" 
                        class="px-3.5 py-2 rounded-xl text-xs font-bold bg-white/10 hover:bg-white/20 text-white border border-white/20 transition-all flex items-center gap-1.5">
                        <span>⚙️</span>
                        <span>Ubah Target</span>
                    </button>
                </div>
            </div>

            <!-- Header section & Aggregates -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <!-- Base Price -->
                <div class="bg-white rounded-2xl p-5 min-w-0 border border-gray-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative flex justify-between items-start gap-2">
                        <div>
                            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Total Dasar (DPP)</p>
                            <h3 class="text-xl sm:text-2xl font-black text-gray-900 tracking-tight break-words leading-tight">{{ formatCurrency(totals.base_price) }}</h3>
                        </div>
                        <div class="p-2.5 bg-blue-100/50 rounded-xl text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="mt-4 text-xs font-medium text-gray-400">Total Harga Paket Internet</div>
                </div>

                <!-- Total PPN -->
                <div class="bg-white rounded-2xl p-5 min-w-0 border border-gray-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative flex justify-between items-start gap-2">
                        <div>
                            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Total PPN</p>
                            <h3 class="text-xl sm:text-2xl font-black text-gray-900 tracking-tight break-words leading-tight">{{ formatCurrency(totals.ppn) }}</h3>
                        </div>
                        <div class="p-2.5 bg-indigo-100/50 rounded-xl text-indigo-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                    </div>
                    <div class="mt-4 text-xs font-medium text-gray-400">Pajak Pertambahan Nilai</div>
                </div>

                <!-- Total BHP & USO -->
                <div class="bg-white rounded-2xl p-5 min-w-0 border border-gray-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-br from-amber-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative flex justify-between items-start gap-2">
                        <div>
                            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Total BHP + USO</p>
                            <h3 class="text-xl sm:text-2xl font-black text-gray-900 tracking-tight break-words leading-tight">{{ formatCurrency(totals.bhp + totals.uso) }}</h3>
                        </div>
                        <div class="p-2.5 bg-amber-100/50 rounded-xl text-amber-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-1 text-[10px] font-bold text-gray-500">
                        <span>BHP: {{ formatCurrency(totals.bhp) }}</span>
                        <span>USO: {{ formatCurrency(totals.uso) }}</span>
                    </div>
                </div>

                <!-- Grand Total -->
                <div class="bg-gradient-to-br from-indigo-600 to-indigo-800 rounded-2xl p-5 min-w-0 border border-indigo-700 shadow-md relative overflow-hidden text-white">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-white opacity-5 rounded-full blur-2xl"></div>
                    <div class="relative flex justify-between items-start gap-2">
                        <div>
                            <p class="text-[11px] font-bold text-indigo-200 uppercase tracking-wider mb-1">Grand Total</p>
                            <h3 class="text-xl sm:text-2xl font-black tracking-tight drop-shadow-sm break-words leading-tight">{{ formatCurrency(totals.grand_total) }}</h3>
                        </div>
                        <div class="p-2.5 bg-white/10 rounded-xl backdrop-blur-sm border border-white/20">
                            <svg class="w-6 h-6 text-indigo-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        </div>
                    </div>
                    <div class="mt-4 text-xs font-medium text-indigo-200">
                        {{ masking_info?.is_masked ? 'Omset Pelanggan Terpilih' : 'Total Tagihan Lunas (Terbayar)' }}
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                <!-- Toolbar -->
                <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gray-50/50">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-gray-900">Rincian Pajak Pelanggan</h3>
                            <span v-if="masking_info?.is_masked" class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-purple-100 text-purple-700 border border-purple-200">
                                Mode Privasi ({{ customers.total }} Data)
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">Data kalkulasi pajak berdasarkan pelanggan yang sudah bayar (lunas).</p>
                    </div>

                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5 w-full sm:w-auto">
                        <!-- Filter Periode (Bulan & Tahun) -->
                        <div class="flex items-center gap-1.5 shrink-0">
                            <select 
                                v-model="selectedMonth" 
                                @change="onPeriodChange" 
                                class="px-2.5 py-2 rounded-xl border border-gray-200 text-xs font-bold text-gray-800 bg-white focus:border-indigo-500 shadow-sm"
                                title="Pilih Bulan Laporan Pajak">
                                <option v-for="(mName, mIdx) in monthNames" :key="mIdx" :value="mIdx + 1">{{ mName }}</option>
                            </select>

                            <select 
                                v-model="selectedYear" 
                                @change="onPeriodChange" 
                                class="px-2.5 py-2 rounded-xl border border-gray-200 text-xs font-bold text-gray-800 bg-white focus:border-indigo-500 shadow-sm"
                                title="Pilih Tahun Laporan Pajak">
                                <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </div>

                        <div class="relative flex-1 sm:flex-initial min-w-0">
                            <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input 
                                type="text" 
                                v-model="search"
                                placeholder="Cari pelanggan..." 
                                class="pl-10 pr-4 py-2 w-full sm:w-52 border-gray-200 rounded-xl text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition-shadow"
                                @keyup.enter="doSearch"
                            >
                        </div>

                        <!-- Tombol Pengaturan Target Omset & Privasi -->
                        <button 
                            v-if="can_manage_masking" 
                            type="button"
                            @click="openMaskingModal" 
                            class="flex items-center justify-center gap-2 px-3.5 py-2 bg-purple-50 text-purple-700 hover:bg-purple-100 rounded-xl font-bold text-sm transition-colors border border-purple-200 shadow-xs whitespace-nowrap">
                            <span>🧮</span>
                            <span>Target Omset</span>
                        </button>

                        <button @click="printReport" class="flex items-center justify-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-xl font-semibold text-sm transition-colors border border-indigo-100 shadow-sm whitespace-nowrap">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            <span>Cetak</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto w-full overscroll-x-contain">
                    <table class="min-w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                <th class="px-6 py-4">Pelanggan</th>
                                <th class="px-6 py-4">Paket & Harga (DPP)</th>
                                <th class="px-6 py-4">PPN</th>
                                <th class="px-6 py-4">BHP & USO</th>
                                <th class="px-6 py-4 text-right">Total Tagihan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-for="customer in customers.data" :key="customer.id" class="hover:bg-gray-50/50 transition-colors group">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 shrink-0 rounded-full bg-gradient-to-br from-indigo-100 to-indigo-50 flex items-center justify-center text-indigo-600 font-bold text-xs border border-indigo-100 shadow-sm">
                                            {{ customer.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-gray-900 group-hover:text-indigo-600 transition-colors">{{ customer.name }}</p>
                                            <p class="text-[11px] font-medium text-gray-500 flex items-center gap-1 mt-0.5">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                                                {{ customer.customer_code }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-gray-900">{{ customer.package_name }}</span>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            <span class="text-xs font-semibold text-gray-500 font-mono">{{ formatCurrency(customer.base_price) }}</span>
                                            <span v-if="customer.is_prorata" class="px-1.5 py-0.2 bg-amber-50 text-amber-700 border border-amber-200/60 rounded text-[9px] font-bold">Prorata</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-gray-900 font-mono">{{ formatCurrency(customer.ppn_amount) }}</span>
                                        <span class="text-[10px] text-gray-400 font-mono">Tarif: {{ customer.ppn_percent }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-gray-900 font-mono">{{ formatCurrency(customer.bhp_amount + customer.uso_amount) }}</span>
                                        <span class="text-[10px] text-gray-400 font-mono">BHP: {{ formatCurrency(customer.bhp_amount) }} | USO: {{ formatCurrency(customer.uso_amount) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex flex-col items-end">
                                        <span class="text-sm font-black text-indigo-600 font-mono">{{ formatCurrency(customer.total_price) }}</span>
                                        <span class="text-[10px] text-gray-400 font-mono">Total Pajak: {{ formatCurrency(customer.total_tax) }}</span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!customers.data || customers.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 mb-4">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-gray-900">Tidak ada data ditemukan</h3>
                                    <p class="text-sm text-gray-500 mt-1">Belum ada pelanggan aktif atau pencarian tidak cocok.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50" v-if="customers.links && customers.data.length > 0">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <p class="text-sm text-gray-500">
                            Menampilkan <span class="font-semibold text-gray-900">{{ customers.from }}</span> - <span class="font-semibold text-gray-900">{{ customers.to }}</span> dari <span class="font-semibold text-gray-900">{{ customers.total }}</span> pelanggan
                        </p>
                        <div class="flex flex-wrap items-center justify-center sm:justify-end gap-1">
                            <template v-for="(link, k) in customers.links" :key="k">
                                <div v-if="link.url === null" class="px-3 py-1.5 text-sm font-medium text-gray-400 bg-gray-50 border border-gray-200 rounded-lg cursor-not-allowed" v-html="link.label"></div>
                                <Link v-else :href="link.url" class="px-3 py-1.5 text-sm font-medium rounded-lg border transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1" :class="link.active ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600 shadow-sm' : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50 hover:text-gray-900'" v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Interactive Quick Drawer / Modal: Pengaturan Target Omset & Masking -->
        <div v-if="showMaskingModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-gray-100 overflow-hidden transform transition-all">
                
                <!-- Modal Header -->
                <div class="p-6 bg-gradient-to-r from-indigo-900 to-purple-900 text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center text-lg">🧮</div>
                        <div>
                            <h3 class="text-base font-bold">Kalkulator Target Omset & Privasi</h3>
                            <p class="text-xs text-indigo-200 mt-0.5">Ketik omset yang ingin ditampilkan, sistem menghitung otomatis.</p>
                        </div>
                    </div>
                    <button type="button" @click="showMaskingModal = false" class="text-white/70 hover:text-white p-2 rounded-xl hover:bg-white/10 transition-colors">
                        ✕
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                    
                    <!-- Toggle Aktifkan Penyamaran -->
                    <div class="p-4 rounded-2xl border flex items-center justify-between transition-colors"
                        :class="modalForm.enabled ? 'bg-indigo-50/50 border-indigo-200' : 'bg-gray-50 border-gray-200'">
                        <div>
                            <p class="text-xs font-bold text-gray-900">Aktifkan Penyamaran Data Laporan</p>
                            <p class="text-[11px] text-gray-500 mt-0.5">Jika aktif, data laporan dibatasi sesuai target yang diatur di bawah.</p>
                        </div>
                        <button 
                            type="button" 
                            @click="modalForm.enabled = !modalForm.enabled"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                            :class="modalForm.enabled ? 'bg-indigo-600' : 'bg-gray-300'">
                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                :class="modalForm.enabled ? 'translate-x-5' : 'translate-x-0'"></span>
                        </button>
                    </div>

                    <!-- Mode Pilihan -->
                    <div class="grid grid-cols-2 gap-3">
                        <label 
                            class="p-3 rounded-2xl border text-xs font-bold cursor-pointer transition-all flex items-center justify-between"
                            :class="modalForm.mode === 'omset' ? 'border-indigo-600 bg-indigo-50/60 text-indigo-900' : 'border-gray-200 hover:border-gray-300 text-gray-700'">
                            <div class="flex items-center gap-2">
                                <input type="radio" value="omset" v-model="modalForm.mode" @change="runModalSimulation" class="text-indigo-600">
                                <span>Target Total Omset (Rp)</span>
                            </div>
                        </label>

                        <label 
                            class="p-3 rounded-2xl border text-xs font-bold cursor-pointer transition-all flex items-center justify-between"
                            :class="modalForm.mode === 'customer_count' ? 'border-indigo-600 bg-indigo-50/60 text-indigo-900' : 'border-gray-200 hover:border-gray-300 text-gray-700'">
                            <div class="flex items-center gap-2">
                                <input type="radio" value="customer_count" v-model="modalForm.mode" @change="runModalSimulation" class="text-indigo-600">
                                <span>Target Jumlah Pelanggan</span>
                            </div>
                        </label>
                    </div>

                    <!-- Input Nilai Target -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-gray-700">
                            {{ modalForm.mode === 'omset' ? 'Ketik Nominal Target Omset:' : 'Ketik Jumlah Pelanggan:' }}
                        </label>
                        
                        <div v-if="modalForm.mode === 'omset'" class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 font-bold text-sm">Rp</span>
                            <input 
                                type="text" 
                                :value="modalFormattedOmset" 
                                @input="onModalOmsetInput"
                                placeholder="Contoh: 100.000.000"
                                class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-gray-200 rounded-xl text-base font-black text-gray-900 focus:bg-white focus:border-indigo-500 font-mono"
                            >
                        </div>

                        <div v-else class="relative">
                            <input 
                                type="number" 
                                v-model.number="modalForm.target_customers" 
                                @input="runModalSimulation"
                                placeholder="Contoh: 300"
                                min="1"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-gray-200 rounded-xl text-base font-black text-gray-900 focus:bg-white focus:border-indigo-500 font-mono"
                            >
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">Pelanggan</span>
                        </div>

                        <!-- Presets -->
                        <div v-if="modalForm.mode === 'omset'" class="flex flex-wrap gap-1.5 pt-1">
                            <button 
                                v-for="amt in [50000000, 100000000, 150000000, 200000000, 300000000]" 
                                :key="amt"
                                type="button" 
                                @click="modalForm.target_omset = amt; runModalSimulation();"
                                class="px-2.5 py-1 rounded-lg text-[11px] font-bold border transition-colors"
                                :class="modalForm.target_omset == amt ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100'">
                                {{ formatShortNumber(amt) }}
                            </button>
                        </div>
                    </div>

                    <!-- Live Calculation Card Preview inside Modal -->
                    <div class="p-4 bg-slate-900 text-white rounded-2xl space-y-3 font-mono text-xs">
                        <div class="flex items-center justify-between text-indigo-300 font-sans pb-2 border-b border-white/10">
                            <span class="font-bold text-[11px] uppercase">Hasil Perhitungan Cerdas</span>
                            <span v-if="modalCalculating" class="animate-pulse text-amber-300">Menghitung...</span>
                            <span v-else class="text-emerald-400">✓ Akurat</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-gray-400 block text-[10px]">Pelanggan Terpilih:</span>
                                <span class="font-black text-white text-base font-mono">{{ modalSim.selected_count || 0 }} Org</span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[10px]">Omset Terpenuhi:</span>
                                <span class="font-black text-emerald-400 text-base font-mono">{{ formatCurrency(modalSim.reached_omset || 0) }}</span>
                            </div>
                        </div>
                        <div class="pt-2 border-t border-white/10 grid grid-cols-3 gap-2 text-[10px]">
                            <div>
                                <span class="text-gray-400 block">DPP:</span>
                                <span class="text-white font-bold">{{ formatCurrency(modalSim.base_price || 0) }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400 block">PPN:</span>
                                <span class="text-white font-bold">{{ formatCurrency(modalSim.ppn || 0) }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400 block">BHP + USO:</span>
                                <span class="text-white font-bold">{{ formatCurrency(modalSim.bhp_uso || 0) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Pilihan Target Akun -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-gray-700">Terapkan Pada Akun:</label>
                        <select v-model="modalForm.scope" class="w-full px-3 py-2 text-xs border-gray-200 rounded-xl focus:border-indigo-500 font-medium">
                            <option value="all_except_super_admin">Semua Akun Kecuali Super Admin (Rekomendasi)</option>
                            <option value="everyone">Semua Akun Termasuk Super Admin</option>
                            <option value="specific_accounts">Hanya Akun Tertentu (Atur di Halaman Lengkap)</option>
                        </select>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="p-5 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-3">
                    <Link href="/settings/data-masking" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                        <span>⚙️ Buka Pengaturan Lengkap</span>
                        <span>&rarr;</span>
                    </Link>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="showMaskingModal = false" class="px-4 py-2 rounded-xl border border-gray-200 text-xs font-bold text-gray-700 hover:bg-gray-100">
                            Batal
                        </button>
                        <button 
                            type="button" 
                            @click="saveModalSettings" 
                            :disabled="modalSaving"
                            class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-100 flex items-center gap-1.5 disabled:opacity-50">
                            <span v-if="modalSaving" class="animate-spin">⏳</span>
                            <span>{{ modalSaving ? 'Menerapkan...' : 'Simpan & Terapkan' }}</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    customers: Object,
    filters: Object,
    totals: Object,
    period: { type: Object, default: () => ({ month: new Date().getMonth() + 1, year: new Date().getFullYear() }) },
    masking_info: { type: Object, default: () => ({}) },
    masking_settings: { type: Object, default: () => ({}) },
    can_manage_masking: { type: Boolean, default: false },
    available_roles: { type: Array, default: () => [] },
    available_users: { type: Array, default: () => [] },
});

const monthNames = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
const yearOptions = [2024, 2025, 2026, 2027, 2028];

const selectedMonth = ref(props.period?.month || (new Date().getMonth() + 1));
const selectedYear = ref(props.period?.year || new Date().getFullYear());

const search = ref(props.filters.search || '');

let searchTimeout = null;
const doSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/tax-reports', {
            search: search.value,
            month: selectedMonth.value,
            year: selectedYear.value,
        }, { preserveState: true, replace: true });
    }, 300);
};

const onPeriodChange = () => {
    router.get('/tax-reports', {
        search: search.value,
        month: selectedMonth.value,
        year: selectedYear.value,
    }, { preserveState: true, replace: true });
};

watch(search, (val) => {
    doSearch();
});

function formatCurrency(value) {
    if (value === null || value === undefined) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
}

function formatShortNumber(val) {
    if (val >= 1000000000) return (val / 1000000000) + ' M';
    if (val >= 1000000) return (val / 1000000) + ' Juta';
    return Number(val).toLocaleString('id-ID');
}

function printReport() {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    params.set('month', selectedMonth.value);
    params.set('year', selectedYear.value);
    window.open('/tax-reports/print?' + params.toString(), '_blank');
}

function togglePreviewSession() {
    router.post('/settings/data-masking/toggle-preview', {}, {
        preserveScroll: true,
    });
}

// Interactive Quick Modal Logic
const showMaskingModal = ref(false);
const modalSaving = ref(false);
const modalCalculating = ref(false);
const modalSim = ref(props.masking_settings?.summary || {});

const modalForm = ref({
    enabled: !!props.masking_settings?.enabled,
    mode: props.masking_settings?.mode || 'omset',
    target_omset: props.masking_settings?.target_omset || 100000000,
    target_customers: props.masking_settings?.target_customers || 500,
    selection_method: props.masking_settings?.selection_method || 'fifo',
    scope: props.masking_settings?.scope || 'all_except_super_admin',
    target_roles: props.masking_settings?.target_roles || [],
    target_users: props.masking_settings?.target_users || [],
    hide_sidebar_menu: props.masking_settings?.hide_sidebar_menu !== false,
    modules: props.masking_settings?.modules || [
        'tax_report', 'customers', 'invoices', 'billing', 'financial_reports',
        'booking', 'survey', 'installed', 'activation', 'monitoring_pelanggan',
        'network_data', 'onts', 'network_map', 'pgk_tickets', 'technician_monitoring'
    ],
});

const modalFormattedOmset = computed(() => {
    if (!modalForm.value.target_omset) return '';
    return Number(modalForm.value.target_omset).toLocaleString('id-ID');
});

const onModalOmsetInput = (e) => {
    const raw = e.target.value.replace(/[^0-9]/g, '');
    modalForm.value.target_omset = raw ? Number(raw) : 0;
    debounceModalSim();
};

let modalSimTimeout = null;
const debounceModalSim = () => {
    clearTimeout(modalSimTimeout);
    modalSimTimeout = setTimeout(() => {
        runModalSimulation();
    }, 400);
};

const runModalSimulation = async () => {
    modalCalculating.value = true;
    try {
        const res = await axios.post('/settings/data-masking/simulate', {
            mode: modalForm.value.mode,
            target_omset: modalForm.value.target_omset,
            target_customers: modalForm.value.target_customers,
            selection_method: modalForm.value.selection_method,
        });
        if (res.data?.status === 'success') {
            modalSim.value = res.data.data;
        }
    } catch (e) {
        console.error('Modal simulation error:', e);
    } finally {
        modalCalculating.value = false;
    }
};

const openMaskingModal = () => {
    showMaskingModal.value = true;
    runModalSimulation();
};

const saveModalSettings = () => {
    modalSaving.value = true;
    router.post('/settings/data-masking', modalForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            showMaskingModal.value = false;
        },
        onFinish: () => {
            modalSaving.value = false;
        }
    });
};
</script>
