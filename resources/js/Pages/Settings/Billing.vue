<template>
    <AppLayout title="Pengaturan Billing & Invoice" subtitle="Konfigurasi otomatisasi siklus tagihan, jatuh tempo, dan isolir layanan pelanggan">
        <div class="max-w-6xl flex flex-col lg:flex-row gap-6">
            <!-- Form Section -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden lg:w-2/3">
                <div class="p-6 sm:p-8">
                    <form @submit.prevent="saveAll" class="space-y-8">
                        
                        <!-- 1. Tipe Billing -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-2 mb-1">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900">Siklus Penagihan Default</h3>
                            </div>
                            <p class="text-xs text-gray-500 mb-2 ml-10">Tentukan standar jenis siklus penagihan sistem. Pilihan ini akan menjadi nilai default saat melakukan Aktivasi Pelanggan (dapat disesuaikan secara fleksibel per pelanggan).</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label class="relative flex cursor-pointer rounded-xl border p-4 shadow-sm focus:outline-none transition-all duration-200 hover:border-indigo-300 hover:shadow-md" :class="form.billing_type === 'prabayar' ? 'border-indigo-600 bg-indigo-50/30 ring-1 ring-indigo-600' : 'border-gray-200 bg-white'">
                                    <input type="radio" name="billing_type" value="prabayar" v-model="form.billing_type" class="sr-only">
                                    <span class="flex flex-1">
                                        <span class="flex flex-col">
                                            <span class="block text-sm font-bold" :class="form.billing_type === 'prabayar' ? 'text-indigo-900' : 'text-gray-900'">Prabayar</span>
                                            <span class="mt-1 flex items-center text-xs" :class="form.billing_type === 'prabayar' ? 'text-indigo-700' : 'text-gray-500'">Bayar dulu baru pakai (Prepaid)</span>
                                        </span>
                                    </span>
                                    <div class="flex items-center">
                                        <div class="w-5 h-5 rounded-full border flex items-center justify-center transition-colors" :class="form.billing_type === 'prabayar' ? 'border-indigo-600 bg-indigo-600' : 'border-gray-300'">
                                            <svg v-if="form.billing_type === 'prabayar'" class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        </div>
                                    </div>
                                </label>
                                
                                <label class="relative flex cursor-pointer rounded-xl border p-4 shadow-sm focus:outline-none transition-all duration-200 hover:border-indigo-300 hover:shadow-md" :class="form.billing_type === 'pascabayar' ? 'border-indigo-600 bg-indigo-50/30 ring-1 ring-indigo-600' : 'border-gray-200 bg-white'">
                                    <input type="radio" name="billing_type" value="pascabayar" v-model="form.billing_type" class="sr-only">
                                    <span class="flex flex-1">
                                        <span class="flex flex-col">
                                            <span class="block text-sm font-bold" :class="form.billing_type === 'pascabayar' ? 'text-indigo-900' : 'text-gray-900'">Pasca Bayar</span>
                                            <span class="mt-1 flex items-center text-xs" :class="form.billing_type === 'pascabayar' ? 'text-indigo-700' : 'text-gray-500'">Pakai dulu baru bayar (Postpaid)</span>
                                        </span>
                                    </span>
                                    <div class="flex items-center">
                                        <div class="w-5 h-5 rounded-full border flex items-center justify-center transition-colors" :class="form.billing_type === 'pascabayar' ? 'border-indigo-600 bg-indigo-600' : 'border-gray-300'">
                                            <svg v-if="form.billing_type === 'pascabayar'" class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div v-if="form.errors.billing_type" class="mt-1 text-sm text-red-600">{{ form.errors.billing_type }}</div>

                            <!-- Opsi Prorata saat Pascabayar -->
                            <div v-if="form.billing_type === 'pascabayar'" class="bg-indigo-50/50 rounded-xl p-5 border border-indigo-100 space-y-4">
                                <div class="flex items-start justify-between gap-4 pb-3 border-b border-indigo-100">
                                    <div>
                                        <h4 class="text-sm font-bold text-indigo-900 flex items-center gap-2">
                                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Prorata Tagihan Pertama (Pascabayar)
                                        </h4>
                                        <p class="text-xs text-indigo-700 mt-1">
                                            Aktifkan prorata otomatis untuk tagihan pertama pelanggan baru. Pengaturan ini <strong>hanya berlaku 1 kali</strong> saat bulan aktivasi, bulan berikutnya otomatis normal pascabayar.
                                        </p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                        <input type="checkbox" v-model="form.prorata_first_month" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                    </label>
                                </div>

                                <div v-if="form.prorata_first_month" class="space-y-3">
                                    <label class="block text-xs font-bold text-indigo-900">Pilih Rumusan Prorata:</label>
                                    <label class="flex items-start p-3 bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-indigo-300 transition-colors" :class="form.prorata_formula === 'exact_days' ? 'border-indigo-500 ring-1 ring-indigo-500' : ''">
                                        <div class="flex items-center h-5">
                                            <input type="radio" v-model="form.prorata_formula" value="exact_days" class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 focus:ring-indigo-500">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <span class="font-bold text-gray-900 block">Sangat Akurat (Bulan Berjalan) <span class="ml-2 text-[10px] font-medium bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">Disarankan</span></span>
                                            <span class="text-gray-500 text-xs mt-0.5 block">Harga / Total Hari di Bulan Ini x Hari Pemakaian (Adil untuk pelanggan)</span>
                                        </div>
                                    </label>
                                    
                                    <label class="flex items-start p-3 bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-indigo-300 transition-colors" :class="form.prorata_formula === 'fixed_30' ? 'border-indigo-500 ring-1 ring-indigo-500' : ''">
                                        <div class="flex items-center h-5">
                                            <input type="radio" v-model="form.prorata_formula" value="fixed_30" class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 focus:ring-indigo-500">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <span class="font-bold text-gray-900 block">Konstan 30 Hari</span>
                                            <span class="text-gray-500 text-xs mt-0.5 block">Harga / 30 x Hari Pemakaian (Bulan apapun dianggap rata 30 hari)</span>
                                        </div>
                                    </label>

                                    <label class="flex items-start p-3 bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-indigo-300 transition-colors" :class="form.prorata_formula === 'mid_month' ? 'border-indigo-500 ring-1 ring-indigo-500' : ''">
                                        <div class="flex items-center h-5">
                                            <input type="radio" v-model="form.prorata_formula" value="mid_month" class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 focus:ring-indigo-500">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <span class="font-bold text-gray-900 block">Paruh Bulan (Cut-Off Tengah)</span>
                                            <span class="text-gray-500 text-xs mt-0.5 block">Aktivasi sebelum pertengahan siklus = Bayar Penuh. Lewat pertengahan = Diskon 50%</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Tombol Simpan Siklus Penagihan -->
                            <div class="flex items-center justify-between pt-2">
                                <Transition name="fade">
                                    <span v-if="savedSection === 'billing_type'" class="text-xs font-semibold text-emerald-600 flex items-center gap-1.5 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200">
                                        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        Siklus penagihan tersimpan!
                                    </span>
                                    <span v-else></span>
                                </Transition>
                                <button type="button" @click="saveSection('billing_type', 'Siklus Penagihan')" :disabled="savingSection !== null" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:scale-95 disabled:opacity-50 text-white text-xs font-semibold rounded-xl shadow-sm shadow-indigo-100 transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 cursor-pointer">
                                    <svg v-if="savingSection === 'billing_type'" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    {{ savingSection === 'billing_type' ? 'Menyimpan...' : 'Simpan Siklus Penagihan' }}
                                </button>
                            </div>
                        </div>
                        
                        <hr class="border-gray-100 border-dashed">

                        <!-- 2. Timeline Pengaturan -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900">Timeline & Penjadwalan</h3>
                            </div>
                            
                            <div class="grid grid-cols-1 gap-y-6">
                                <!-- Tanggal Terbit -->
                                <div class="bg-gray-50/50 p-5 rounded-xl border border-gray-100 flex flex-col md:flex-row md:items-start gap-4">
                                    <div class="flex-1">
                                        <label class="block text-sm font-bold text-gray-900 mb-1">Tanggal Terbit Invoice</label>
                                        <p class="text-[11px] text-gray-500 mb-3 leading-relaxed">Tanggal rutin di setiap bulannya dimana sistem akan men-generate invoice baru untuk seluruh pelanggan aktif.</p>
                                    </div>
                                    <div class="w-full md:w-56">
                                        <div class="flex items-center rounded-lg border border-gray-300 shadow-sm focus-within:ring-1 focus-within:ring-indigo-500 focus-within:border-indigo-500 overflow-hidden bg-white">
                                            <div class="pl-3 pr-2 py-2.5 flex items-center justify-center text-gray-400 bg-gray-50 border-r border-gray-200">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            </div>
                                            <input type="number" v-model="form.invoice_issue_date" min="1" max="28" class="form-input flex-1 border-0 focus:ring-0 sm:text-sm font-medium py-2.5 px-3 min-w-0" />
                                            <div class="pr-3 pl-2 py-2.5 flex items-center justify-center bg-gray-50 text-gray-500 text-xs font-medium border-l border-gray-200">
                                                Tiap bln
                                            </div>
                                        </div>
                                        <div v-if="form.errors.invoice_issue_date" class="mt-1 text-sm text-red-600">{{ form.errors.invoice_issue_date }}</div>
                                    </div>
                                </div>

                                <!-- Isolir -->
                                <div class="bg-red-50/30 p-5 rounded-xl border border-red-100 flex flex-col md:flex-row md:items-start gap-4">
                                    <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-sm font-bold text-gray-900 mb-1">Batas Masa Tenggang & Jam Isolir</label>
                                        <p class="text-[11px] text-gray-500 mb-4 leading-relaxed">
                                            Layanan akan di-suspend secara otomatis apabila menunggak sekian hari sejak invoice terbit, persis pada jam yang Anda tentukan di bawah ini.
                                        </p>
                                        
                                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                                            <div class="w-full sm:w-48">
                                                <div class="flex items-center rounded-lg border border-red-200 shadow-sm focus-within:ring-1 focus-within:ring-red-500 focus-within:border-red-500 overflow-hidden bg-white">
                                                    <input type="number" v-model="form.isolate_days" min="0" class="form-input flex-1 border-0 focus:ring-0 sm:text-sm font-medium py-2 px-3 min-w-0" />
                                                    <div class="pr-3 pl-2 py-2 flex items-center justify-center bg-red-50 text-red-500 text-xs font-medium border-l border-red-200">
                                                        Hari
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-sm font-bold text-gray-400 uppercase tracking-widest hidden sm:block">Pukul</div>
                                            <div class="relative w-full sm:w-32">
                                                <input type="time" v-model="form.isolate_time" class="form-input block w-full rounded-lg border-red-200 px-3 focus:border-red-500 focus:ring-red-500 sm:text-sm font-medium bg-white shadow-sm" />
                                            </div>
                                        </div>
                                        <div v-if="form.errors.isolate_days" class="mt-1 text-sm text-red-600">{{ form.errors.isolate_days }}</div>
                                        <div v-if="form.errors.isolate_time" class="mt-1 text-sm text-red-600">{{ form.errors.isolate_time }}</div>
                                        <span class="text-xs font-medium text-red-500 mt-2 block" v-if="form.isolate_days == 0">Peringatan: Jika di set 0, internet langsung terisolir di hari tagihan terbit.</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tombol Simpan Timeline & Penjadwalan -->
                            <div class="flex items-center justify-between pt-2">
                                <Transition name="fade">
                                    <span v-if="savedSection === 'timeline'" class="text-xs font-semibold text-emerald-600 flex items-center gap-1.5 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200">
                                        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        Timeline & penjadwalan tersimpan!
                                    </span>
                                    <span v-else></span>
                                </Transition>
                                <button type="button" @click="saveSection('timeline', 'Timeline & Penjadwalan')" :disabled="savingSection !== null" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:scale-95 disabled:opacity-50 text-white text-xs font-semibold rounded-xl shadow-sm shadow-emerald-100 transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 cursor-pointer">
                                    <svg v-if="savingSection === 'timeline'" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    {{ savingSection === 'timeline' ? 'Menyimpan...' : 'Simpan Timeline & Penjadwalan' }}
                                </button>
                            </div>
                        </div>
                        
                        <hr class="border-gray-100 border-dashed">

                        <!-- 3. Pengaturan Pajak -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-8 h-8 rounded-lg bg-yellow-50 text-yellow-600 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">Pajak & Regulasi</h3>
                                    <p class="text-[11px] text-gray-500 mt-0.5">Persentase (%) ini akan ditambahkan ke total tagihan pelanggan secara otomatis.</p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- PPN -->
                                <div class="bg-gray-50/50 p-4 rounded-xl border border-gray-100">
                                    <label class="block text-sm font-bold text-gray-900 mb-1">PPN</label>
                                    <p class="text-[10px] text-gray-500 mb-3">Pajak Pertambahan Nilai</p>
                                    <div class="flex items-center rounded-lg border border-gray-300 shadow-sm focus-within:ring-1 focus-within:ring-indigo-500 focus-within:border-indigo-500 overflow-hidden bg-white">
                                        <input type="number" step="0.01" min="0" max="100" v-model="form.tax_ppn" class="form-input flex-1 border-0 focus:ring-0 sm:text-sm font-medium py-2 px-3 min-w-0" placeholder="0" />
                                        <div class="pr-3 pl-2 py-2 flex items-center justify-center bg-gray-50 text-gray-500 text-xs font-medium border-l border-gray-200">%</div>
                                    </div>
                                    <div v-if="form.errors.tax_ppn" class="mt-1 text-sm text-red-600">{{ form.errors.tax_ppn }}</div>
                                </div>

                                <!-- BHP -->
                                <div class="bg-gray-50/50 p-4 rounded-xl border border-gray-100">
                                    <label class="block text-sm font-bold text-gray-900 mb-1">BHP</label>
                                    <p class="text-[10px] text-gray-500 mb-3">Biaya Hak Penggunaan</p>
                                    <div class="flex items-center rounded-lg border border-gray-300 shadow-sm focus-within:ring-1 focus-within:ring-indigo-500 focus-within:border-indigo-500 overflow-hidden bg-white">
                                        <input type="number" step="0.01" min="0" max="100" v-model="form.tax_bhp" class="form-input flex-1 border-0 focus:ring-0 sm:text-sm font-medium py-2 px-3 min-w-0" placeholder="0" />
                                        <div class="pr-3 pl-2 py-2 flex items-center justify-center bg-gray-50 text-gray-500 text-xs font-medium border-l border-gray-200">%</div>
                                    </div>
                                    <div v-if="form.errors.tax_bhp" class="mt-1 text-sm text-red-600">{{ form.errors.tax_bhp }}</div>
                                </div>

                                <!-- USO -->
                                <div class="bg-gray-50/50 p-4 rounded-xl border border-gray-100">
                                    <label class="block text-sm font-bold text-gray-900 mb-1">USO</label>
                                    <p class="text-[10px] text-gray-500 mb-3">Universal Service Obligation</p>
                                    <div class="flex items-center rounded-lg border border-gray-300 shadow-sm focus-within:ring-1 focus-within:ring-indigo-500 focus-within:border-indigo-500 overflow-hidden bg-white">
                                        <input type="number" step="0.01" min="0" max="100" v-model="form.tax_uso" class="form-input flex-1 border-0 focus:ring-0 sm:text-sm font-medium py-2 px-3 min-w-0" placeholder="0" />
                                        <div class="pr-3 pl-2 py-2 flex items-center justify-center bg-gray-50 text-gray-500 text-xs font-medium border-l border-gray-200">%</div>
                                    </div>
                                    <div v-if="form.errors.tax_uso" class="mt-1 text-sm text-red-600">{{ form.errors.tax_uso }}</div>
                                </div>
                            </div>

                            <!-- Tombol Simpan Pajak & Regulasi -->
                            <div class="flex items-center justify-between pt-2">
                                <Transition name="fade">
                                    <span v-if="savedSection === 'tax'" class="text-xs font-semibold text-emerald-600 flex items-center gap-1.5 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200">
                                        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        Pajak & regulasi tersimpan!
                                    </span>
                                    <span v-else></span>
                                </Transition>
                                <button type="button" @click="saveSection('tax', 'Pajak & Regulasi')" :disabled="savingSection !== null" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 active:scale-95 disabled:opacity-50 text-white text-xs font-semibold rounded-xl shadow-sm shadow-amber-100 transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 cursor-pointer">
                                    <svg v-if="savingSection === 'tax'" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    {{ savingSection === 'tax' ? 'Menyimpan...' : 'Simpan Pajak & Regulasi' }}
                                </button>
                            </div>
                        </div>

                        <hr class="border-gray-100 border-dashed">

                        <!-- 4. Pengaturan Bank -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    </div>
                                    <h3 class="text-lg font-bold text-gray-900">Rekening Pembayaran</h3>
                                </div>
                                <button type="button" @click="addBank" class="px-3 py-1.5 text-xs font-bold bg-blue-50 text-blue-600 rounded-lg border border-blue-200 hover:bg-blue-100 transition-colors flex items-center gap-1 cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Tambah Rekening
                                </button>
                            </div>
                            
                            <div class="space-y-4">
                                <div v-if="form.payment_banks.length === 0" class="text-center py-8 bg-gray-50 border border-dashed border-gray-200 rounded-xl">
                                    <p class="text-sm text-gray-500">Belum ada rekening bank yang ditambahkan.</p>
                                </div>
                                
                                <TransitionGroup name="fade" tag="div" class="space-y-4">
                                    <div v-for="(bank, index) in form.payment_banks" :key="index" class="p-4 bg-white border border-gray-200 shadow-sm rounded-xl flex flex-col md:flex-row gap-4 md:items-start relative">
                                        <button type="button" @click="removeBank(index)" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 hover:bg-red-50 p-1 rounded-md transition-colors cursor-pointer" title="Hapus Rekening">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                        
                                        <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4 mr-6">
                                            <div>
                                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Nama Bank</label>
                                                <input type="text" v-model="bank.bank_name" placeholder="BCA / Mandiri / BRI" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Atas Nama</label>
                                                <input type="text" v-model="bank.account_name" placeholder="PT Contoh Indo" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Nomor Rekening</label>
                                                <input type="text" v-model="bank.account_number" placeholder="123456789" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            </div>
                                        </div>
                                    </div>
                                </TransitionGroup>
                            </div>

                            <!-- Tombol Simpan Rekening Pembayaran -->
                            <div class="flex items-center justify-between pt-2">
                                <Transition name="fade">
                                    <span v-if="savedSection === 'banks'" class="text-xs font-semibold text-emerald-600 flex items-center gap-1.5 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200">
                                        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        Rekening pembayaran tersimpan!
                                    </span>
                                    <span v-else></span>
                                </Transition>
                                <button type="button" @click="saveSection('banks', 'Rekening Bank')" :disabled="savingSection !== null" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 active:scale-95 disabled:opacity-50 text-white text-xs font-semibold rounded-xl shadow-sm shadow-blue-100 transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 cursor-pointer">
                                    <svg v-if="savingSection === 'banks'" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    {{ savingSection === 'banks' ? 'Menyimpan...' : 'Simpan Rekening Bank' }}
                                </button>
                            </div>
                        </div>

                        <hr class="border-gray-100 border-dashed">

                        <!-- 5. Pengaturan Payment Gateway -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">Payment Gateway (Otomatis)</h3>
                                    <p class="text-xs text-gray-500">Konfigurasi pihak ketiga untuk pembayaran otomatis.</p>
                                </div>
                            </div>
                            
                            <div class="space-y-5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Provider Gateway</label>
                                        <select v-model="form.pg_provider" class="form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="none">Tidak Menggunakan (Manual)</option>
                                            <option value="tripay">Tripay</option>
                                            <option value="midtrans">Midtrans</option>
                                            <option value="duitku">Duitku</option>
                                        </select>
                                    </div>
                                    <div v-if="form.pg_provider !== 'none'">
                                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Environment</label>
                                        <select v-model="form.pg_environment" class="form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="sandbox">Sandbox (Testing)</option>
                                            <option value="production">Production (Live)</option>
                                        </select>
                                    </div>
                                </div>

                                <Transition name="fade">
                                    <div v-if="form.pg_provider !== 'none'" class="grid grid-cols-1 md:grid-cols-2 gap-5 bg-gray-50 p-5 rounded-xl border border-gray-200">
                                        <div>
                                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Merchant Code / ID</label>
                                            <input type="text" v-model="form.pg_merchant_id" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. TXXXX">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">API Key</label>
                                            <input type="text" v-model="form.pg_api_key" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. dev-xxx...">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Private Key</label>
                                            <input type="password" v-model="form.pg_private_key" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. xxxx-xxxx-xxxx">
                                        </div>
                                        <div v-if="form.pg_provider === 'tripay'">
                                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Callback Token</label>
                                            <input type="password" v-model="form.pg_callback_token" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Token dari webhook (optional)">
                                        </div>
                                    </div>
                                </Transition>
                            </div>

                            <!-- Tombol Simpan Payment Gateway -->
                            <div class="flex items-center justify-between pt-2">
                                <Transition name="fade">
                                    <span v-if="savedSection === 'pg'" class="text-xs font-semibold text-emerald-600 flex items-center gap-1.5 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200">
                                        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        Payment gateway tersimpan!
                                    </span>
                                    <span v-else></span>
                                </Transition>
                                <button type="button" @click="saveSection('pg', 'Payment Gateway')" :disabled="savingSection !== null" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:scale-95 disabled:opacity-50 text-white text-xs font-semibold rounded-xl shadow-sm shadow-indigo-100 transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 cursor-pointer">
                                    <svg v-if="savingSection === 'pg'" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    {{ savingSection === 'pg' ? 'Menyimpan...' : 'Simpan Payment Gateway' }}
                                </button>
                            </div>
                        </div>

                        <hr class="border-gray-100 border-dashed">

                        <!-- 6. Template & Desain Invoice -->
                        <div class="space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">Template & Desain Invoice</h3>
                                        <p class="text-xs text-gray-500">Pilih format tampilan cetak invoice standar untuk pelanggan Anda.</p>
                                    </div>
                                </div>
                                <a :href="'/settings/invoice-template/preview?template=' + form.invoice_template" target="_blank" class="px-3.5 py-1.5 text-xs font-bold bg-violet-50 text-violet-700 hover:bg-violet-100 rounded-lg border border-violet-200 transition-colors inline-flex items-center gap-1.5 shadow-sm self-start sm:self-auto cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Lihat Pratinjau Desain
                                </a>
                            </div>

                            <!-- Pilihan 4 Template -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- 1. Modern Clean -->
                                <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all hover:shadow-md" :class="form.invoice_template === 'modern' ? 'border-indigo-600 bg-indigo-50/20 ring-2 ring-indigo-500' : 'border-gray-200 bg-white hover:border-gray-300'">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="text-lg">🌟</span>
                                            <span class="font-bold text-sm text-gray-900">Modern Clean</span>
                                        </div>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Rekomendasi</span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2 leading-relaxed">
                                        Header gradien biru elegan, card info pelanggan rapi, tabel biaya terstruktur, dan kartu rekening transfer jelas. Sangat cocok untuk kirim via WhatsApp PDF & Cetak A4.
                                    </p>
                                    <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-400">
                                        <span>Ukuran: Kertas A4 / Surat</span>
                                        <input type="radio" value="modern" v-model="form.invoice_template" class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                    </div>
                                </label>

                                <!-- 2. Classic Corporate -->
                                <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all hover:shadow-md" :class="form.invoice_template === 'classic' ? 'border-indigo-600 bg-indigo-50/20 ring-2 ring-indigo-500' : 'border-gray-200 bg-white hover:border-gray-300'">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="text-lg">🏛️</span>
                                            <span class="font-bold text-sm text-gray-900">Classic Corporate</span>
                                        </div>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">Formal</span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2 leading-relaxed">
                                        Format surat resmi perusahaan dengan kop surat garis ganda, rincian perpajakan (PPN/DPP) lengkap, serta kolom tanda tangan basah & stempel resmi.
                                    </p>
                                    <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-400">
                                        <span>Cocok: Instansi & Bisnis B2B</span>
                                        <input type="radio" value="classic" v-model="form.invoice_template" class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                    </div>
                                </label>

                                <!-- 3. Struk Thermal POS -->
                                <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all hover:shadow-md" :class="form.invoice_template === 'thermal' ? 'border-indigo-600 bg-indigo-50/20 ring-2 ring-indigo-500' : 'border-gray-200 bg-white hover:border-gray-300'">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="text-lg">🧾</span>
                                            <span class="font-bold text-sm text-gray-900">Struk Thermal (POS)</span>
                                        </div>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">Hemat Kertas</span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2 leading-relaxed">
                                        Format struk monospaced ringkas khusus printer kasir atau printer mini bluetooth (58mm / 80mm). Praktis untuk penagih lapangan & loket kasir biller.
                                    </p>
                                    <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-400">
                                        <span>Ukuran: 58mm / 80mm Thermal</span>
                                        <input type="radio" value="thermal" v-model="form.invoice_template" class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                    </div>
                                </label>

                                <!-- 4. Elegant Minimalist -->
                                <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all hover:shadow-md" :class="form.invoice_template === 'minimalist' ? 'border-indigo-600 bg-indigo-50/20 ring-2 ring-indigo-500' : 'border-gray-200 bg-white hover:border-gray-300'">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="text-lg">🎨</span>
                                            <span class="font-bold text-sm text-gray-900">Elegant Minimalist</span>
                                        </div>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-100 text-purple-700">Modern ISP</span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2 leading-relaxed">
                                        Desain monokromatik kontras tinggi dengan kartu sisa tagihan beraksen hitam gelap modern. Tampilan bersih, tipografi tajam, dan hemat tinta cetak.
                                    </p>
                                    <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-400">
                                        <span>Gaya: Monokrom & Tech ISP</span>
                                        <input type="radio" value="minimalist" v-model="form.invoice_template" class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                    </div>
                                </label>
                            </div>

                            <!-- Tombol Simpan Template Invoice -->
                            <div class="flex items-center justify-between pt-2">
                                <Transition name="fade">
                                    <span v-if="savedSection === 'template'" class="text-xs font-semibold text-emerald-600 flex items-center gap-1.5 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200">
                                        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        Template invoice tersimpan!
                                    </span>
                                    <span v-else></span>
                                </Transition>
                                <button type="button" @click="saveSection('template', 'Template Invoice')" :disabled="savingSection !== null" class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 active:scale-95 disabled:opacity-50 text-white text-xs font-semibold rounded-xl shadow-sm shadow-violet-100 transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 cursor-pointer">
                                    <svg v-if="savingSection === 'template'" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    {{ savingSection === 'template' ? 'Menyimpan...' : 'Simpan Template Invoice' }}
                                </button>
                            </div>
                        </div>

                        <!-- 7. Master Action Buttons: Simpan Semua Konfigurasi -->
                        <div class="pt-6 border-t-2 border-dashed border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4 bg-gray-50/60 -mx-6 -mb-6 sm:-mx-8 sm:-mb-8 p-6 rounded-b-2xl">
                            <div>
                                <p class="text-xs font-semibold text-gray-800">Simpan Seluruh Pengaturan Sekaligus</p>
                                <p class="text-[11px] text-gray-500 mt-0.5">Semua perubahan pada setiap fitur di atas akan disimpan bersamaan.</p>
                            </div>
                            
                            <button type="button" @click="saveAll" :disabled="savingSection !== null" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 border border-transparent text-sm font-semibold rounded-xl text-white bg-slate-900 hover:bg-slate-800 active:scale-95 disabled:opacity-75 disabled:cursor-wait shadow-sm transition-all cursor-pointer">
                                <svg v-if="savingSection === 'all'" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                                {{ savingSection === 'all' ? 'Menyimpan Semua...' : 'Simpan Semua Konfigurasi' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Simulation Panel -->
            <div class="lg:w-1/3">
                <div class="bg-gradient-to-br from-indigo-900 to-indigo-800 rounded-2xl shadow-lg border border-indigo-700 text-white p-6 sticky top-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-white/10 rounded-lg backdrop-blur-sm">
                            <svg class="w-5 h-5 text-indigo-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg">Simulasi AI</h3>
                            <p class="text-xs text-indigo-200">Berdasarkan pengaturan saat ini</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="bg-white/10 backdrop-blur-md border border-white/10 rounded-xl p-4">
                            <div class="flex items-center gap-3 mb-1">
                                <div class="w-2 h-2 rounded-full bg-blue-400"></div>
                                <span class="text-xs font-medium text-indigo-100 uppercase tracking-wider">Terbit Tagihan</span>
                            </div>
                            <p class="text-sm font-semibold pl-5">Tgl {{ form.invoice_issue_date }} {{ nextMonthName }}</p>
                            <p class="text-[10px] text-indigo-300 pl-5 mt-0.5">Sistem men-generate invoice pelanggan</p>
                        </div>

                        <div class="bg-white/10 backdrop-blur-md border border-red-500/30 rounded-xl p-4 relative overflow-hidden">
                            <div class="absolute right-0 top-0 w-16 h-16 bg-red-500/10 rounded-bl-full"></div>
                            <div class="flex items-center gap-3 mb-1">
                                <div class="w-2 h-2 rounded-full bg-red-400 animate-pulse"></div>
                                <span class="text-xs font-medium text-red-200 uppercase tracking-wider">Isolir Layanan</span>
                            </div>
                            <p class="text-sm font-bold text-white pl-5">Tgl {{ isolateDateFormatted }}</p>
                            <p class="text-[10px] text-red-200 pl-5 mt-0.5">Internet pelanggan otomatis terputus</p>
                        </div>
                    </div>

                    <div v-if="form.billing_type === 'pascabayar' && form.prorata_first_month" class="bg-indigo-800/50 border border-indigo-500/50 rounded-xl p-4 mt-2">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-4 h-4 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            <h4 class="text-xs font-bold text-indigo-200 uppercase tracking-wide">Simulasi Prorata Tagihan Pertama</h4>
                        </div>
                        
                        <div class="space-y-2 text-xs text-indigo-100">
                            <div class="flex justify-between items-end border-b border-indigo-700/50 pb-1">
                                <span>Simulasi Harga Paket</span>
                                <span class="font-medium text-white">{{ formatCurrency(300000) }} / bln</span>
                            </div>
                            <div class="flex justify-between items-end border-b border-indigo-700/50 pb-1">
                                <span>Asumsi Aktivasi Hari Ini</span>
                                <span class="font-medium text-white">{{ todayFormatted }}</span>
                            </div>
                            <div class="flex justify-between items-end border-b border-indigo-700/50 pb-1">
                                <span>Siklus Penagihan ({{ totalDaysInCycle }} Hari)</span>
                                <span class="font-medium text-white">s/d {{ prevIssueDateFormatted }}</span>
                            </div>
                            <div class="flex justify-between items-end pt-1">
                                <span class="text-indigo-300">Tagihan Pertama (<span class="font-bold text-white">{{ daysUsed }}</span> hari pemakaian)</span>
                                <span class="font-bold text-lg text-emerald-400">{{ formatCurrency(prorataAmount) }}</span>
                            </div>
                        </div>
                        <p class="text-[9px] text-indigo-400 mt-3 leading-tight italic">
                            Rumus: 
                            <span v-if="form.prorata_formula === 'fixed_30'">(Harga Paket ÷ 30) × Jumlah Hari Pemakaian</span>
                            <span v-else-if="form.prorata_formula === 'mid_month'">Jika aktivasi lewat pertengahan bulan, diskon 50%. Jika tidak, bayar penuh.</span>
                            <span v-else>(Harga Paket ÷ Total Hari Siklus Berjalan) × Jumlah Hari Pemakaian</span>
                        </p>
                    </div>

                    <div class="mt-6 pt-5 border-t border-indigo-700/50">
                        <p class="text-[10px] text-indigo-300 leading-relaxed">
                            <span class="font-semibold text-white">Catatan:</span> Simulasi ini menggunakan patokan kalender bulan berjalan. Perhitungan sistem sesungguhnya akan selalu sangat akurat menyesuaikan tanggal aktivasi sebenarnya.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    billing_type: String,
    prorata_formula: String,
    prorata_first_month: Boolean,
    invoice_issue_date: String,
    isolate_days: String,
    isolate_time: String,
    payment_banks: Array,
    pg_provider: String,
    pg_environment: String,
    pg_merchant_id: String,
    pg_api_key: String,
    pg_private_key: String,
    pg_callback_token: String,
    tax_ppn: [String, Number],
    tax_bhp: [String, Number],
    tax_uso: [String, Number],
    invoice_template: String,
});

const form = useForm({
    billing_type: props.billing_type || 'prabayar',
    prorata_formula: props.prorata_formula || 'exact_days',
    prorata_first_month: props.prorata_first_month !== undefined ? props.prorata_first_month : true,
    invoice_issue_date: props.invoice_issue_date || '1',
    isolate_days: props.isolate_days || '3',
    isolate_time: props.isolate_time || '00:00',
    invoice_template: props.invoice_template || 'modern',
    payment_banks: props.payment_banks ? JSON.parse(JSON.stringify(props.payment_banks)) : [],
    pg_provider: props.pg_provider || 'none',
    pg_environment: props.pg_environment || 'sandbox',
    pg_merchant_id: props.pg_merchant_id || '',
    pg_api_key: props.pg_api_key || '',
    pg_private_key: props.pg_private_key || '',
    pg_callback_token: props.pg_callback_token || '',
    tax_ppn: props.tax_ppn || 0,
    tax_bhp: props.tax_bhp || 0,
    tax_uso: props.tax_uso || 0,
});

const savingSection = ref(null);
const savedSection = ref(null);

function addBank() {
    form.payment_banks.push({
        bank_name: '',
        account_name: '',
        account_number: '',
    });
}

function removeBank(index) {
    form.payment_banks.splice(index, 1);
}

function sanitizeBanks() {
    // Drop completely empty bank rows
    form.payment_banks = form.payment_banks.filter(b => {
        return (b.bank_name && b.bank_name.trim() !== '') ||
               (b.account_name && b.account_name.trim() !== '') ||
               (b.account_number && b.account_number.trim() !== '');
    });
}

function saveSection(sectionKey, sectionLabel) {
    if (savingSection.value) return;

    if (sectionKey === 'banks') {
        // If specifically saving banks, ensure no partially filled rows
        for (let i = 0; i < form.payment_banks.length; i++) {
            const b = form.payment_banks[i];
            const hasAny = (b.bank_name && b.bank_name.trim()) || (b.account_name && b.account_name.trim()) || (b.account_number && b.account_number.trim());
            const hasAll = (b.bank_name && b.bank_name.trim()) && (b.account_name && b.account_name.trim()) && (b.account_number && b.account_number.trim());
            if (hasAny && !hasAll) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Data Rekening Belum Lengkap',
                    text: `Mohon lengkapi Nama Bank, Atas Nama, dan Nomor Rekening pada rekening baris ke-${i + 1}.`,
                    confirmButtonColor: '#3b82f6'
                });
                return;
            }
        }
    }

    // Always strip blank rows so other sections never fail validation
    sanitizeBanks();

    savingSection.value = sectionKey;

    form.transform(data => ({
        ...data,
        _section: sectionLabel
    })).post('/settings/billing', {
        preserveScroll: true,
        onSuccess: () => {
            savingSection.value = null;
            savedSection.value = sectionKey;
            setTimeout(() => {
                if (savedSection.value === sectionKey) {
                    savedSection.value = null;
                }
            }, 3500);

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Berhasil Disimpan!',
                text: `Pengaturan ${sectionLabel} berhasil diperbarui.`,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        },
        onError: (errors) => {
            savingSection.value = null;
            const firstError = Object.values(errors)[0] || 'Periksa kembali data yang dimasukkan.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: firstError,
                confirmButtonColor: '#4f46e5'
            });
        }
    });
}

function saveAll() {
    if (savingSection.value) return;
    sanitizeBanks();
    savingSection.value = 'all';

    form.transform(data => ({
        ...data,
        _section: 'Semua Konfigurasi Billing'
    })).post('/settings/billing', {
        preserveScroll: true,
        onSuccess: () => {
            savingSection.value = null;
            savedSection.value = 'all';
            setTimeout(() => {
                if (savedSection.value === 'all') {
                    savedSection.value = null;
                }
            }, 3500);

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Berhasil Disimpan!',
                text: 'Semua pengaturan billing & invoice berhasil disimpan.',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        },
        onError: (errors) => {
            savingSection.value = null;
            const firstError = Object.values(errors)[0] || 'Periksa kembali data yang dimasukkan.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: firstError,
                confirmButtonColor: '#4f46e5'
            });
        }
    });
}

// Simulation Logic
const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
const today = new Date();
const nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);

const nextMonthName = computed(() => {
    return monthNames[nextMonth.getMonth()] + ' ' + nextMonth.getFullYear();
});

const issueDateObj = computed(() => {
    let day = parseInt(form.invoice_issue_date) || 1;
    if(day > 28) day = 28;
    return new Date(nextMonth.getFullYear(), nextMonth.getMonth(), day);
});

const isolateDateObj = computed(() => {
    const d = new Date(issueDateObj.value);
    d.setDate(d.getDate() + (parseInt(form.isolate_days) || 0));
    return d;
});

const isolateDateFormatted = computed(() => {
    return `${isolateDateObj.value.getDate()} ${monthNames[isolateDateObj.value.getMonth()]} ${isolateDateObj.value.getFullYear()} ${form.isolate_time || '00:00'}`;
});

// Prorata Variables
const todayFormatted = computed(() => {
    return `${today.getDate()} ${monthNames[today.getMonth()]} ${today.getFullYear()}`;
});

const prevIssueDateObj = computed(() => {
    const d = new Date(issueDateObj.value);
    d.setMonth(d.getMonth() - 1);
    return d;
});

const prevIssueDateFormatted = computed(() => {
    const d = new Date(issueDateObj.value);
    d.setDate(d.getDate() - 1);
    return `${d.getDate()} ${monthNames[d.getMonth()]} ${d.getFullYear()}`;
});

const totalDaysInCycle = computed(() => {
    const diffTime = Math.abs(issueDateObj.value - prevIssueDateObj.value);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
});

const daysUsed = computed(() => {
    let start = today;
    let end = issueDateObj.value;
    if (today.getDate() >= (parseInt(form.invoice_issue_date) || 1)) {
        end = new Date(today.getFullYear(), today.getMonth() + 1, parseInt(form.invoice_issue_date) || 1);
    } else {
        end = new Date(today.getFullYear(), today.getMonth(), parseInt(form.invoice_issue_date) || 1);
    }
    const diffTime = Math.abs(end - start);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
});

const prorataAmount = computed(() => {
    let amount = 300000;
    if (form.prorata_formula === 'fixed_30') {
        return Math.round((amount / 30) * daysUsed.value);
    } else if (form.prorata_formula === 'mid_month') {
        const midPointDays = Math.floor(totalDaysInCycle.value / 2);
        const daysPassed = totalDaysInCycle.value - daysUsed.value;
        if (daysPassed > midPointDays) {
            return amount / 2;
        } else {
            return amount;
        }
    }
    const dailyRate = amount / totalDaysInCycle.value;
    return Math.round(dailyRate * daysUsed.value);
});

function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
