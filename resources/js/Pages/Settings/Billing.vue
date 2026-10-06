<template>
    <AppLayout title="Pengaturan Billing & Invoice" subtitle="Konfigurasi otomatisasi siklus tagihan, jatuh tempo, dan isolir layanan pelanggan">
        <div class="max-w-6xl flex flex-col lg:flex-row gap-6">
            <!-- Form Section -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden lg:w-2/3">
                <div class="p-6 sm:p-8">
                    <form @submit.prevent="submit" class="space-y-8">
                        
                        <!-- Tipe Billing -->
                        <div>
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900">Siklus Penagihan</h3>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                                
                                <label class="relative flex cursor-pointer rounded-xl border p-4 shadow-sm focus:outline-none transition-all duration-200 hover:border-indigo-300 hover:shadow-md" :class="form.billing_type === 'prorata' ? 'border-indigo-600 bg-indigo-50/30 ring-1 ring-indigo-600' : 'border-gray-200 bg-white'">
                                    <input type="radio" name="billing_type" value="prorata" v-model="form.billing_type" class="sr-only">
                                    <span class="flex flex-1">
                                        <span class="flex flex-col">
                                            <span class="block text-sm font-bold" :class="form.billing_type === 'prorata' ? 'text-indigo-900' : 'text-gray-900'">Prorata</span>
                                            <span class="mt-1 flex items-center text-xs" :class="form.billing_type === 'prorata' ? 'text-indigo-700' : 'text-gray-500'">Hitungan per hari (Cut-off date)</span>
                                        </span>
                                    </span>
                                    <div class="flex items-center">
                                        <div class="w-5 h-5 rounded-full border flex items-center justify-center transition-colors" :class="form.billing_type === 'prorata' ? 'border-indigo-600 bg-indigo-600' : 'border-gray-300'">
                                            <svg v-if="form.billing_type === 'prorata'" class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div v-if="form.errors.billing_type" class="mt-1 text-sm text-red-600">{{ form.errors.billing_type }}</div>
                        </div>

                        <!-- Opsi Rumus Prorata -->
                        <div v-if="form.billing_type === 'prorata'" class="bg-indigo-50/50 rounded-xl p-5 border border-indigo-100">
                            <h4 class="text-sm font-bold text-indigo-900 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                                Rumusan Prorata
                            </h4>
                            <div class="space-y-3">
                                <label class="flex items-start p-3 bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-indigo-300 transition-colors" :class="form.prorata_formula === 'exact_days' ? 'border-indigo-500 ring-1 ring-indigo-500' : ''">
                                    <div class="flex items-center h-5">
                                        <input type="radio" v-model="form.prorata_formula" value="exact_days" class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 focus:ring-indigo-500">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <span class="font-bold text-gray-900 block">Sangat Akurat (Bulan Berjalan) <span class="ml-2 text-[10px] font-medium bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">Disarankan</span></span>
                                        <span class="text-gray-500 text-xs mt-1 block">Harga / Total Hari di Bulan Ini x Hari Pemakaian (Sangat adil untuk pelanggan)</span>
                                    </div>
                                </label>
                                
                                <label class="flex items-start p-3 bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-indigo-300 transition-colors" :class="form.prorata_formula === 'fixed_30' ? 'border-indigo-500 ring-1 ring-indigo-500' : ''">
                                    <div class="flex items-center h-5">
                                        <input type="radio" v-model="form.prorata_formula" value="fixed_30" class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 focus:ring-indigo-500">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <span class="font-bold text-gray-900 block">Konstan 30 Hari</span>
                                        <span class="text-gray-500 text-xs mt-1 block">Harga / 30 x Hari Pemakaian (Bulan apapun dianggap rata 30 hari)</span>
                                    </div>
                                </label>

                                <label class="flex items-start p-3 bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-indigo-300 transition-colors" :class="form.prorata_formula === 'mid_month' ? 'border-indigo-500 ring-1 ring-indigo-500' : ''">
                                    <div class="flex items-center h-5">
                                        <input type="radio" v-model="form.prorata_formula" value="mid_month" class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 focus:ring-indigo-500">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <span class="font-bold text-gray-900 block">Paruh Bulan (Cut-Off Tengah)</span>
                                        <span class="text-gray-500 text-xs mt-1 block">Aktivasi sebelum pertengahan siklus = Bayar Penuh. Lewat pertengahan = Diskon 50%</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <hr class="border-gray-100 border-dashed">

                        <!-- Timeline Pengaturan -->
                        <div>
                            <div class="flex items-center gap-2 mb-6">
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
                        </div>
                        
                        <hr class="border-gray-100 border-dashed">

                        <!-- Pengaturan Bank -->
                        <div>
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    </div>
                                    <h3 class="text-lg font-bold text-gray-900">Rekening Pembayaran</h3>
                                </div>
                                <button type="button" @click="addBank" class="px-3 py-1.5 text-xs font-bold bg-blue-50 text-blue-600 rounded-lg border border-blue-200 hover:bg-blue-100 transition-colors flex items-center gap-1">
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
                                        <button type="button" @click="removeBank(index)" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 hover:bg-red-50 p-1 rounded-md transition-colors" title="Hapus Rekening">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                        
                                        <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4 mr-6">
                                            <div>
                                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Nama Bank</label>
                                                <input type="text" v-model="bank.bank_name" placeholder="BCA / Mandiri / BRI" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Atas Nama</label>
                                                <input type="text" v-model="bank.account_name" placeholder="PT Contoh Indo" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Nomor Rekening</label>
                                                <input type="text" v-model="bank.account_number" placeholder="123456789" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                            </div>
                                        </div>
                                    </div>
                                </TransitionGroup>
                            </div>
                        </div>

                        <hr class="border-gray-100 border-dashed">

                        <!-- Pengaturan Payment Gateway -->
                        <div>
                            <div class="flex items-center gap-2 mb-6">
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
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-6 border-t border-gray-100 flex items-center justify-between">
                            <Transition name="fade">
                                <p v-if="form.recentlySuccessful" class="text-sm font-medium text-emerald-600 flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    Pengaturan berhasil disimpan.
                                </p>
                                <span v-else></span>
                            </Transition>
                            
                            <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center px-6 py-2.5 border border-transparent text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all shadow-sm shadow-indigo-200 disabled:opacity-75 disabled:cursor-wait">
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Konfigurasi' }}
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

                        <!-- Removed Jatuh Tempo Simulation -->

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

                        <div v-if="form.billing_type === 'prorata'" class="bg-indigo-800/50 border border-indigo-500/50 rounded-xl p-4 mt-2">
                            <div class="flex items-center gap-2 mb-3">
                                <svg class="w-4 h-4 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                <h4 class="text-xs font-bold text-indigo-200 uppercase tracking-wide">Kalkulator Prorata Aktif</h4>
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
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    billing_type: String,
    prorata_formula: String,
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
});

const form = useForm({
    billing_type: props.billing_type || 'prabayar',
    prorata_formula: props.prorata_formula || 'exact_days',
    invoice_issue_date: props.invoice_issue_date || '1',
    isolate_days: props.isolate_days || '3',
    isolate_time: props.isolate_time || '00:00',
    payment_banks: props.payment_banks || [],
    pg_provider: props.pg_provider || 'none',
    pg_environment: props.pg_environment || 'sandbox',
    pg_merchant_id: props.pg_merchant_id || '',
    pg_api_key: props.pg_api_key || '',
    pg_private_key: props.pg_private_key || '',
    pg_callback_token: props.pg_callback_token || '',
});

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

// Simulation Logic
const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
const today = new Date();
// We'll simulate for the next month
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
    // We want the day before the issue date, e.g. if issue is 1st, then previous end of month.
    const d = new Date(issueDateObj.value);
    d.setDate(d.getDate() - 1);
    return `${d.getDate()} ${monthNames[d.getMonth()]} ${d.getFullYear()}`;
});

const totalDaysInCycle = computed(() => {
    const diffTime = Math.abs(issueDateObj.value - prevIssueDateObj.value);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
});

const daysUsed = computed(() => {
    // From today until the next issue date
    let start = today;
    let end = issueDateObj.value;
    // If today is past the issue date, the "next" issue date is actually next month.
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
        const daysPassed = totalDaysInCycle.value - daysUsed.value; // Hari dari prevIssueDate ke today
        if (daysPassed > midPointDays) {
            return amount / 2;
        } else {
            return amount;
        }
    }
    // default: exact_days
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

function submit() {
    form.post('/settings/billing', {
        preserveScroll: true,
    });
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
