<template>
    <AppLayout title="Laporan Keuangan Reseller" subtitle="Ringkasan kasbon, setoran, dan saldo beredar">
        <div class="w-full max-w-7xl mx-auto min-w-0 space-y-5 sm:space-y-6 pb-8">
            
            <!-- Dashboard Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5 gap-3.5 sm:gap-4">
                <!-- Saldo Beredar -->
                <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl sm:rounded-3xl p-4 sm:p-5 text-white shadow-[0_8px_30px_rgb(79,70,229,0.2)] relative overflow-hidden group min-w-0">
                    <div class="relative z-10 min-w-0">
                        <p class="text-indigo-100 font-medium tracking-wide uppercase text-[10px] mb-1">Saldo Beredar</p>
                        <h2 class="text-lg sm:text-xl 2xl:text-2xl font-black mb-0.5 break-words leading-tight">Rp {{ formatRupiah(stats.saldo_beredar) }}</h2>
                        <p class="text-[11px] text-indigo-100/80 mt-1">Saldo aktif dompet mitra</p>
                    </div>
                </div>

                <!-- Total Piutang Kasbon -->
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl sm:rounded-3xl p-4 sm:p-5 text-white shadow-[0_8px_30px_rgb(249,115,22,0.2)] relative overflow-hidden group min-w-0">
                    <div class="relative z-10 min-w-0">
                        <p class="text-orange-100 font-medium tracking-wide uppercase text-[10px] mb-1">Piutang Kasbon</p>
                        <h2 class="text-lg sm:text-xl 2xl:text-2xl font-black mb-0.5 break-words leading-tight">Rp {{ formatRupiah(stats.piutang) }}</h2>
                        <p class="text-[11px] text-orange-100/80 mt-1">Kasbon belum dilunasi</p>
                    </div>
                </div>

                <!-- Total Pemasukan -->
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl sm:rounded-3xl p-4 sm:p-5 text-white shadow-[0_8px_30px_rgb(34,197,94,0.2)] relative overflow-hidden group min-w-0">
                    <div class="relative z-10 min-w-0">
                        <p class="text-green-100 font-medium tracking-wide uppercase text-[10px] mb-1">Total Pemasukan</p>
                        <h2 class="text-lg sm:text-xl 2xl:text-2xl font-black mb-0.5 break-words leading-tight">Rp {{ formatRupiah(stats.pemasukan) }}</h2>
                        <p class="text-[11px] text-green-100/80 mt-1">Setoran kasbon &amp; top up tunai/transfer</p>
                    </div>
                </div>

                <!-- Beban Operasional Reseller -->
                <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-2xl sm:rounded-3xl p-4 sm:p-5 text-white shadow-[0_8px_30px_rgb(239,68,68,0.2)] relative overflow-hidden group min-w-0">
                    <div class="relative z-10 min-w-0">
                        <p class="text-red-100 font-medium tracking-wide uppercase text-[10px] mb-1">Beban Operasional Reseller</p>
                        <h2 class="text-lg sm:text-xl 2xl:text-2xl font-black mb-0.5 break-words leading-tight">Rp {{ formatRupiah(stats.pengeluaran) }}</h2>
                        <p class="text-[11px] text-red-100/80 mt-1">Beban khusus operasional kemitraan</p>
                    </div>
                </div>

                <!-- Kas Bersih Reseller -->
                <div class="sm:col-span-2 xl:col-span-2 2xl:col-span-1 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl sm:rounded-3xl p-4 sm:p-5 text-white shadow-[0_8px_30px_rgb(59,130,246,0.2)] relative overflow-hidden group ring-4 ring-blue-500/20 min-w-0">
                    <div class="relative z-10 min-w-0">
                        <p class="text-blue-100 font-medium tracking-wide uppercase text-[10px] mb-1">Kas Bersih Reseller</p>
                        <h2 class="text-lg sm:text-xl 2xl:text-2xl font-black mb-0.5 break-words leading-tight">Rp {{ formatRupiah(stats.kas_bersih) }}</h2>
                        <p class="text-[11px] text-blue-100/80 mt-1">Pemasukan dikurangi beban reseller</p>
                    </div>
                </div>
            </div>

            <!-- Tabel Reseller -->
            <div class="bg-white rounded-2xl sm:rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 overflow-hidden min-w-0">
                <div class="p-4 sm:p-6 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-gray-900">Rincian Per Reseller</h3>
                        <p class="text-xs sm:text-sm text-gray-500">Analisis keuangan dan performa masing-masing reseller.</p>
                    </div>
                    <Link :href="route('reseller-requests.index')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 text-xs font-bold transition">
                        <span>💳 Kelola Mutasi &amp; Saldo</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </Link>
                </div>

                <div class="overflow-x-auto w-full overscroll-x-contain">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Reseller</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Saldo Tersisa</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total Kasbon (Hutang)</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Sisa Piutang</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total Lunas</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="reseller in resellers" :key="reseller.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900 flex items-center gap-1.5 flex-wrap">
                                        <span>{{ reseller.name }}</span>
                                        <span 
                                            class="text-[10px] font-bold px-1.5 py-0.5 rounded border shrink-0"
                                            :class="reseller.has_customer ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-purple-50 text-purple-700 border-purple-200'"
                                        >
                                            {{ reseller.type }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5 flex items-center gap-2">
                                        <span>{{ reseller.phone }}</span>
                                        <a 
                                            v-if="reseller.phone && reseller.phone !== '-'" 
                                            :href="`https://wa.me/${formatWa(reseller.phone)}`" 
                                            target="_blank" 
                                            class="text-[10px] text-emerald-600 font-bold hover:underline"
                                        >
                                            💬 WA
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-black text-indigo-600">Rp {{ formatRupiah(reseller.balance) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-600">Rp {{ formatRupiah(reseller.total_kasbon) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-black text-red-500">Rp {{ formatRupiah(reseller.piutang) }}</div>
                                    <div v-if="reseller.piutang > 0" class="text-[10px] text-gray-400 italic">Perlu ditagih</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-green-600">Rp {{ formatRupiah(reseller.total_paid) }}</div>
                                </td>
                            </tr>
                            <tr v-if="!resellers.length">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <p class="font-medium">Belum ada data reseller terdaftar.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 sm:gap-6">
                <!-- Mutasi Transaksi -->
                <div class="bg-white rounded-2xl sm:rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 p-4 sm:p-6 min-w-0">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                        <h3 class="text-base sm:text-lg font-bold text-gray-900">Riwayat Mutasi Saldo &amp; Voucher</h3>
                        <span class="text-xs font-bold bg-indigo-50 text-indigo-700 px-2.5 py-1 rounded-lg shrink-0">15 Terakhir</span>
                    </div>
                    
                    <div v-if="recent_transactions.length" class="space-y-3">
                        <div v-for="trx in recent_transactions" :key="trx.id" class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 p-3.5 rounded-2xl border border-gray-100 hover:bg-gray-50 transition-colors min-w-0">
                            <div class="flex items-start sm:items-center gap-3 min-w-0 flex-1">
                                <div :class="trx.type === 'credit' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'" class="p-2.5 rounded-xl shrink-0">
                                    <svg v-if="trx.type === 'credit'" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    <svg v-else class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-bold text-gray-900 break-words">{{ getResellerName(trx.reseller) }}</p>
                                    <p class="text-xs text-gray-500 break-words" :title="trx.description">{{ trx.description }}</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">{{ new Date(trx.created_at).toLocaleString('id-ID') }}</p>
                                </div>
                            </div>
                            <div class="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center pl-11 sm:pl-0 shrink-0">
                                <p :class="trx.type === 'credit' ? 'text-green-600' : 'text-red-600'" class="text-sm font-black whitespace-nowrap">
                                    {{ trx.type === 'credit' ? '+' : '-' }} Rp {{ formatRupiah(trx.amount) }}
                                </p>
                                <p class="text-[10px] text-gray-400 uppercase tracking-wide font-bold">{{ trx.reference_id }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 bg-gray-50 rounded-2xl border border-gray-100 border-dashed text-gray-500 text-sm">
                        Belum ada riwayat mutasi transaksi. <br>
                        <span class="text-xs">Beli voucher atau tambah saldo untuk melihat transaksi di sini.</span>
                    </div>
                </div>

                <!-- Buku Kas Beban Reseller -->
                <div class="bg-white rounded-2xl sm:rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 p-4 sm:p-6 flex flex-col h-full min-w-0">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                        <div>
                            <h3 class="text-base sm:text-lg font-bold text-gray-900">Buku Kas Beban Reseller</h3>
                            <p class="text-xs text-gray-500">Biaya jamuan, ID card, spanduk &amp; operasional kemitraan.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <Link :href="route('expense-categories.index')" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-3 py-1.5 rounded-xl shadow-sm text-xs font-bold transition-all whitespace-nowrap">
                                <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                Kategori
                            </Link>
                            <button @click="showExpenseModal = true" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 bg-rose-600 hover:bg-rose-700 text-white px-3.5 py-1.5 rounded-xl shadow-sm transition-all hover:shadow text-xs font-bold cursor-pointer whitespace-nowrap">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Catat Beban
                            </button>
                        </div>
                    </div>
                    
                    <div v-if="recent_expenses.length" class="space-y-3 flex-1">
                        <div v-for="exp in recent_expenses" :key="exp.id" class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 p-3.5 rounded-2xl border border-gray-100 hover:bg-gray-50 transition-colors min-w-0">
                            <div class="flex items-start sm:items-center gap-3 min-w-0 flex-1">
                                <div class="bg-red-100 text-red-600 p-2.5 rounded-xl shrink-0">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <p class="text-sm font-bold text-gray-900 break-words">{{ exp.description }}</p>
                                        <span v-if="exp.category" class="text-[10px] font-bold px-2 py-0.5 rounded-md border shrink-0 bg-rose-50 text-rose-700 border-rose-200">
                                            {{ exp.category.name }}
                                        </span>
                                        <span v-if="exp.area" class="text-[10px] font-bold px-2 py-0.5 rounded-md border bg-emerald-50 text-emerald-700 border-emerald-200 shrink-0">
                                            Wilayah: {{ exp.area }}
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-gray-400 mt-0.5">{{ new Date(exp.expense_date).toLocaleDateString('id-ID') }} &middot; Oleh: {{ exp.user?.name || 'Sistem' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-end gap-3 pl-11 sm:pl-0 shrink-0">
                                <p class="text-sm font-black text-rose-600 whitespace-nowrap">
                                    - Rp {{ formatRupiah(exp.amount) }}
                                </p>
                                <button @click="deleteExpense(exp.id)" class="text-gray-400 hover:text-red-600 transition-colors p-1" title="Hapus Pengeluaran">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 bg-gray-50 rounded-2xl border border-gray-100 border-dashed text-gray-500 text-sm flex-1 flex flex-col justify-center">
                        Belum ada riwayat beban operasional reseller.<br>
                        <span class="text-xs text-gray-400 mt-1">Biaya seperti jamuan mitra, cetak brosur/spanduk, atau komisi dapat dicatat di sini.</span>
                    </div>
                </div>
            </div>

            <!-- Modal Form Pengeluaran -->
            <div v-if="showExpenseModal" class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="showExpenseModal = false"></div>
                <div class="relative bg-white rounded-2xl sm:rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:max-w-md w-full max-h-[90vh] flex flex-col border border-gray-100">
                        <div class="bg-gray-50/50 px-5 sm:px-6 py-4 sm:py-5 border-b border-gray-100 flex items-center justify-between shrink-0">
                            <h3 class="text-base sm:text-lg leading-6 font-bold text-gray-900 flex items-center gap-2">
                                <span class="bg-rose-100 text-rose-600 p-1.5 rounded-lg shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                </span>
                                Catat Beban Operasional Reseller
                            </h3>
                            <button @click="showExpenseModal = false" class="text-gray-400 hover:text-gray-500 cursor-pointer shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        
                        <div class="px-5 sm:px-6 py-4 sm:py-5 overflow-y-auto flex-1">
                            <form @submit.prevent="submitExpense" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tanggal Pengeluaran <span class="text-rose-500">*</span></label>
                                    <input type="date" v-model="expenseForm.expense_date" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-rose-500 focus:border-rose-500 text-sm" required>
                                    <p v-if="expenseForm.errors.expense_date" class="text-xs text-rose-500 mt-1 font-medium">{{ expenseForm.errors.expense_date }}</p>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between flex-wrap gap-1">
                                        <label class="block text-sm font-medium text-gray-700">Kategori Pengeluaran</label>
                                        <Link :href="route('expense-categories.index')" class="text-xs font-bold text-rose-600 hover:text-rose-800">
                                            + Kelola Kategori
                                        </Link>
                                    </div>
                                    <select v-model="expenseForm.expense_category_id" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-rose-500 focus:border-rose-500 text-sm font-medium">
                                        <option value="">-- Tanpa Kategori (Umum) --</option>
                                        <option v-for="cat in expense_categories" :key="cat.id" :value="cat.id">
                                            {{ cat.code ? '[' + cat.code + '] ' : '' }}{{ cat.name }}
                                        </option>
                                    </select>
                                    <p v-if="expenseForm.errors.expense_category_id" class="text-xs text-rose-500 mt-1 font-medium">{{ expenseForm.errors.expense_category_id }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Area / Wilayah</label>
                                    <select v-model="expenseForm.area_id" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-rose-500 focus:border-rose-500 text-sm font-medium">
                                        <option value="">-- Kantor Pusat / Semua Wilayah (Umum) --</option>
                                        <option v-for="a in areas" :key="a.id" :value="a.id">
                                            {{ a.name }}
                                        </option>
                                    </select>
                                    <p v-if="expenseForm.errors.area_id" class="text-xs text-rose-500 mt-1 font-medium">{{ expenseForm.errors.area_id }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nominal (Rp) <span class="text-rose-500">*</span></label>
                                    <div class="relative mt-1">
                                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-sm font-bold text-gray-400">Rp</span>
                                        <input type="number" v-model="expenseForm.amount" min="1" step="1" class="pl-10 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-rose-500 focus:border-rose-500 font-bold text-lg" placeholder="Contoh: 150000" required>
                                    </div>
                                    <p v-if="expenseForm.errors.amount" class="text-xs text-rose-500 mt-1 font-medium">{{ expenseForm.errors.amount }}</p>
                                    <p v-if="expenseForm.amount && !isNaN(expenseForm.amount) && Number(expenseForm.amount) > 0" class="text-xs text-emerald-600 font-semibold mt-1">
                                        Terbaca: Rp {{ formatRupiah(expenseForm.amount) }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Keterangan / Tujuan Pengeluaran <span class="text-rose-500">*</span></label>
                                    <textarea v-model="expenseForm.description" rows="2" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-rose-500 focus:border-rose-500 text-sm" placeholder="Contoh: Jamuan makan mitra, cetak spanduk voucher, dll" required></textarea>
                                    <p v-if="expenseForm.errors.description" class="text-xs text-rose-500 mt-1 font-medium">{{ expenseForm.errors.description }}</p>
                                </div>
                                
                                <div class="pt-4 flex flex-wrap justify-end gap-2.5 sm:gap-3">
                                    <button type="button" @click="showExpenseModal = false" class="flex-1 sm:flex-initial bg-white py-2.5 px-5 border border-gray-300 rounded-xl shadow-sm text-sm font-bold text-gray-700 hover:bg-gray-50 transition-all cursor-pointer">
                                        Batal
                                    </button>
                                    <button type="submit" :disabled="expenseForm.processing" class="flex-1 sm:flex-initial inline-flex justify-center items-center gap-1.5 py-2.5 px-5 border border-transparent shadow-sm text-sm font-bold rounded-xl text-white bg-rose-600 hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 transition-all disabled:opacity-50 cursor-pointer">
                                        <svg v-if="expenseForm.processing" class="animate-spin -ml-1 mr-1 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        <span>{{ expenseForm.processing ? 'Menyimpan...' : 'Simpan Beban Reseller' }}</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    stats: Object,
    resellers: Array,
    recent_transactions: Array,
    recent_expenses: Array,
    expense_categories: Array,
    areas: {
        type: Array,
        default: () => []
    }
});

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID').format(number || 0);
};

const formatWa = (phone) => {
    if (!phone) return '';
    return String(phone).replace(/^0/, '62').replace(/\D/g, '');
};

const getResellerName = (reseller) => {
    if (!reseller) return 'Unknown Reseller';
    return reseller.customer?.name || reseller.user?.name || ('Reseller #' + reseller.id);
};

const getTodayDate = () => {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const showExpenseModal = ref(false);

const expenseForm = useForm({
    amount: '',
    expense_category_id: '',
    area_id: '',
    description: '',
    expense_date: getTodayDate(),
});

const submitExpense = () => {
    // Pastikan penanda kemitraan disertakan
    const descLower = expenseForm.description.toLowerCase();
    if (!descLower.includes('reseller') && !descLower.includes('mitra')) {
        expenseForm.description = expenseForm.description + ' [Beban Mitra]';
    }

    expenseForm.post(route('expenses.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showExpenseModal.value = false;
            expenseForm.reset();
            expenseForm.expense_category_id = '';
            expenseForm.area_id = '';
            expenseForm.expense_date = getTodayDate();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Dicatat!',
                text: 'Catatan beban operasional kemitraan berhasil disimpan.',
                timer: 1800,
                showConfirmButton: false
            });
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0] || 'Terjadi kesalahan saat menyimpan pengeluaran.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: firstError
            });
        }
    });
};

const deleteExpense = (id) => {
    Swal.fire({
        title: 'Hapus Beban Reseller?',
        text: 'Apakah Anda yakin ingin menghapus catatan beban kemitraan ini? Laba bersih reseller akan dikalkulasi ulang.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('expenses.destroy', id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus!',
                        text: 'Catatan beban kemitraan berhasil dihapus.',
                        timer: 1600,
                        showConfirmButton: false
                    });
                },
                onError: (err) => {
                    const errorMsg = Object.values(err)[0] || 'Gagal menghapus beban kemitraan.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: errorMsg
                    });
                }
            });
        }
    });
};
</script>
