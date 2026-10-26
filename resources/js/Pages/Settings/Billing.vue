<template>
    <AppLayout title="Pengaturan Billing & Invoice" subtitle="Konfigurasi otomatisasi siklus tagihan, jatuh tempo, dan isolir layanan pelanggan">
        <div class="max-w-[1600px] w-full mx-auto space-y-6 font-sans">
            <!-- Flash message -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-2">
                <span>✅</span>
                <span>{{ $page.props.flash.success }}</span>
            </div>

            <div class="flex flex-col lg:flex-row gap-6">
                <!-- Form Section -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden lg:w-7/12 xl:w-2/3">
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
                            <p class="text-xs text-gray-500 mb-4 ml-10">Tentukan standar jenis siklus dan metode penagihan sistem. Nilai ini menjadi acuan default saat aktivasi pelanggan baru dan dapat disesuaikan per pelanggan.</p>
                            
                            <!-- Pilihan Mode Siklus Tanggal Tagihan (Anniversary vs Calendar) -->
                            <div class="space-y-3 p-4 bg-slate-50/70 border border-slate-200/80 rounded-2xl">
                                <div>
                                    <label class="block text-xs font-bold text-slate-800">1. Mode Penentuan Tanggal Tagihan (Billing Cycle Mode):</label>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Pilih bagaimana sistem menentukan tanggal terbit invoice rutin bulanan untuk pelanggan.</p>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <label class="relative flex cursor-pointer rounded-xl border p-4 shadow-sm focus:outline-none transition-all duration-200 hover:border-indigo-400 hover:shadow-md" :class="form.billing_cycle === 'anniversary' ? 'border-indigo-600 bg-white ring-2 ring-indigo-500' : 'border-gray-200 bg-white/70'">
                                        <input type="radio" name="billing_cycle" value="anniversary" v-model="form.billing_cycle" class="sr-only">
                                        <span class="flex flex-1">
                                            <span class="flex flex-col">
                                                <span class="flex items-center gap-2">
                                                    <span class="block text-sm font-bold" :class="form.billing_cycle === 'anniversary' ? 'text-indigo-900' : 'text-gray-900'">Sesuai Tanggal Aktivasi</span>
                                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Disarankan</span>
                                                </span>
                                                <span class="mt-1 text-xs leading-relaxed" :class="form.billing_cycle === 'anniversary' ? 'text-indigo-700' : 'text-gray-500'">Anniversary Billing — Tagihan terbit mengikuti tanggal aktivasi tiap pelanggan (misal aktif tgl 12 &rarr; ditagih tiap tgl 12). Tagihan merata tiap hari tanpa prorata.</span>
                                            </span>
                                        </span>
                                        <div class="flex items-center ml-2">
                                            <div class="w-5 h-5 rounded-full border flex items-center justify-center transition-colors" :class="form.billing_cycle === 'anniversary' ? 'border-indigo-600 bg-indigo-600' : 'border-gray-300'">
                                                <svg v-if="form.billing_cycle === 'anniversary'" class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            </div>
                                        </div>
                                    </label>

                                    <label class="relative flex cursor-pointer rounded-xl border p-4 shadow-sm focus:outline-none transition-all duration-200 hover:border-indigo-400 hover:shadow-md" :class="form.billing_cycle === 'calendar' ? 'border-indigo-600 bg-white ring-2 ring-indigo-500' : 'border-gray-200 bg-white/70'">
                                        <input type="radio" name="billing_cycle" value="calendar" v-model="form.billing_cycle" class="sr-only">
                                        <span class="flex flex-1">
                                            <span class="flex flex-col">
                                                <span class="block text-sm font-bold" :class="form.billing_cycle === 'calendar' ? 'text-indigo-900' : 'text-gray-900'">Tanggal Serentak (Kalender)</span>
                                                <span class="mt-1 text-xs leading-relaxed" :class="form.billing_cycle === 'calendar' ? 'text-indigo-700' : 'text-gray-500'">Fixed Calendar — Semua tagihan diterbitkan serentak pada satu tanggal tertentu (misal tgl 1). Pelanggan baru di tengah bulan dapat dikenakan prorata.</span>
                                            </span>
                                        </span>
                                        <div class="flex items-center ml-2">
                                            <div class="w-5 h-5 rounded-full border flex items-center justify-center transition-colors" :class="form.billing_cycle === 'calendar' ? 'border-indigo-600 bg-indigo-600' : 'border-gray-300'">
                                                <svg v-if="form.billing_cycle === 'calendar'" class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <div v-if="form.errors.billing_cycle" class="mt-1 text-sm text-red-600">{{ form.errors.billing_cycle }}</div>
                            </div>

                            <!-- Pilihan Metode Pembayaran (Prabayar vs Pascabayar) -->
                            <div class="space-y-3 pt-2">
                                <label class="block text-xs font-bold text-slate-800">2. Metode Pembayaran Tagihan Default:</label>
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
                            </div>
                            <div v-if="form.errors.billing_type" class="mt-1 text-sm text-red-600">{{ form.errors.billing_type }}</div>

                            <!-- Opsi Prorata saat Pascabayar -->
                            <div v-if="form.billing_type === 'pascabayar'" class="space-y-4">
                                <!-- Mode Anniversary: Flat 1 Bulan Penuh -->
                                <div v-if="form.billing_cycle === 'anniversary'" class="bg-purple-50/70 rounded-xl p-4 border border-purple-200 flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <div class="text-xs">
                                        <span class="font-bold text-purple-900 block">Siklus Sesuai Tanggal Aktivasi (Anniversary Billing)</span>
                                        <p class="text-purple-700 mt-1 leading-relaxed">
                                            Pada mode Anniversary, tagihan pelanggan berjalan <strong>1 bulan penuh (flat)</strong> terhitung sejak tanggal aktivasi tanpa pemotongan prorata kalender. Tagihan rutin berikutnya otomatis terbit di tanggal yang sama setiap bulan.
                                        </p>
                                    </div>
                                </div>

                                <!-- Mode Kalender: Pilihan Prorata Tagihan Pertama -->
                                <div v-else class="bg-indigo-50/50 rounded-xl p-5 border border-indigo-100 space-y-4">
                                    <div class="flex items-start justify-between gap-4 pb-3 border-b border-indigo-100">
                                        <div>
                                            <h4 class="text-sm font-bold text-indigo-900 flex items-center gap-2">
                                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                Prorata Tagihan Pertama (Kalender Serentak)
                                            </h4>
                                            <p class="text-xs text-indigo-700 mt-1">
                                                Aktifkan prorata otomatis untuk tagihan pertama pelanggan baru. Pengaturan ini <strong>hanya berlaku 1 kali</strong> saat bulan aktivasi, bulan berikutnya otomatis normal pascabayar serentak.
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
                            </div>

                            <!-- Tombol Simpan Siklus Penagihan -->
                            <div class="flex items-center justify-between pt-2">
                                <Transition name="fade">
                                    <span v-if="savedSection === 'billing_type'" class="text-xs font-semibold text-emerald-600 flex items-center gap-1.5 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200">
                                        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        Siklus penagihan tersimpan!
                                    </span>
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
                                <!-- Info Anniversary Billing -->
                                <div v-if="form.billing_cycle === 'anniversary'" class="p-4 bg-indigo-50/80 border border-indigo-200 rounded-xl flex items-start gap-3">
                                    <span class="text-xl">📅</span>
                                    <div>
                                        <h4 class="text-xs font-bold text-indigo-900">Siklus Tanggal Aktivasi Aktif (Anniversary Billing)</h4>
                                        <p class="text-xs text-indigo-700 mt-0.5 leading-relaxed">
                                            Sistem otomatis memeriksa setiap hari dan menerbitkan invoice pada tanggal aktivasi masing-masing pelanggan (contoh: aktif tanggal 12 &rarr; terbit tagihan tanggal 12 setiap bulan). Tanggal terbit di bawah ini menjadi nilai acuan jika tanggal aktivasi pelanggan belum terdaftar atau pelanggan memilih siklus kalender khusus.
                                        </p>
                                    </div>
                                </div>

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
                                        <label class="block text-sm font-bold text-gray-900 mb-1">Tanggal & Jam Isolir</label>
                                        <p class="text-[11px] text-gray-500 mb-4 leading-relaxed">
                                            Pelanggan yang masih menunggak akan diisolir pada tanggal dan jam berikut (WIB), dihitung dari tanggal terbit invoice. Jika tanggal isolir lebih kecil dari tanggal terbit, jadwal masuk bulan berikutnya.
                                        </p>
                                        
                                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                                            <div class="w-full sm:w-48">
                                                <div class="flex items-center rounded-lg border border-red-200 shadow-sm focus-within:ring-1 focus-within:ring-red-500 focus-within:border-red-500 overflow-hidden bg-white">
                                                    <input type="number" aria-label="Tanggal isolir setiap bulan" v-model="form.isolate_day" min="1" max="31" class="form-input flex-1 border-0 focus:ring-0 sm:text-sm font-medium py-2 px-3 min-w-0" />
                                                    <div class="pr-3 pl-2 py-2 flex items-center justify-center bg-red-50 text-red-500 text-xs font-medium border-l border-red-200">
                                                        Tanggal
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-sm font-bold text-gray-400 uppercase tracking-widest hidden sm:block">Pukul</div>
                                            <div class="relative w-full sm:w-32">
                                                <input type="time" aria-label="Jam isolir WIB" v-model="form.isolate_time" class="form-input block w-full rounded-lg border-red-200 px-3 focus:border-red-500 focus:ring-red-500 sm:text-sm font-medium bg-white shadow-sm" />
                                            </div>
                                        </div>
                                        <div v-if="form.errors.isolate_day" class="mt-1 text-sm text-red-600">{{ form.errors.isolate_day }}</div>
                                        <div v-if="form.errors.isolate_time" class="mt-1 text-sm text-red-600">{{ form.errors.isolate_time }}</div>
                                        <span class="text-[11px] text-gray-500 mt-2 block">Tanggal 29–31 menyesuaikan hari terakhir pada bulan yang lebih pendek. Perubahan berlaku untuk invoice yang belum lunas; janji bayar aktif tetap dihormati.</span>
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
                                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Pilihan Gateway Provider</label>
                                        <select v-model="form.pg_provider" class="form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-medium">
                                            <option value="none">❌ Tidak Menggunakan (Hanya Rekening Bank Manual)</option>
                                            <option value="tripay">⚡ Tripay (QRIS, VA Bank Lengkap, Alfamart & Indomaret)</option>
                                            <option value="midtrans">💳 Midtrans (Snap PopUp, QRIS Gopay/ShopeePay, VA Lengkap)</option>
                                            <option value="xendit">🚀 Xendit (Invoice XenPayments, VA Terlengkap, QRIS, E-Wallet)</option>
                                            <option value="duitku">🪙 Duitku (PopUp & Redirect, Fee Kompetitif, Semua Bank)</option>
                                            <option value="ipaymu">🎯 iPaymu (Direct Payment, Populer ISP Lokal & RT/RW Net)</option>
                                            <option value="paydisini">💰 Paydisini (Fee Termurah & Instan, Favorit ISP MikroTik)</option>
                                            <option value="mayar">🌟 Mayar (Invoice Modern Digital & Link Pembayaran Instan)</option>
                                            <option value="simulator">🧪 Sandbox Simulator Cerdas (Testing Otomatis Tanpa API Key)</option>
                                        </select>
                                    </div>
                                    <div v-if="form.pg_provider !== 'none'">
                                        <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">Mode Environment</label>
                                        <select v-model="form.pg_environment" class="form-select block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-medium">
                                            <option value="sandbox">Sandbox / Uji Coba (Development)</option>
                                            <option value="production">Production / Live Transaksi Nyata</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Preview Badge & Channel Supported -->
                                <Transition name="fade">
                                    <div v-if="form.pg_provider !== 'none'" class="p-4 rounded-xl border space-y-3" :class="form.pg_provider === 'simulator' ? 'bg-amber-50/50 border-amber-200' : 'bg-indigo-50/40 border-indigo-100'">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                            <div class="flex items-center gap-2">
                                                <span class="w-2.5 h-2.5 rounded-full" :class="form.pg_environment === 'production' ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500'"></span>
                                                <span class="text-xs font-bold text-gray-900 uppercase">
                                                    Status Gateway: {{ form.pg_provider.toUpperCase() }} ({{ form.pg_environment === 'production' ? 'LIVE PRODUCTION' : 'SANDBOX TESTING' }})
                                                </span>
                                            </div>
                                            <!-- Tombol Salin Webhook -->
                                            <div class="flex items-center gap-2">
                                                <button type="button" @click="copyWebhookUrl" class="inline-flex items-center gap-1.5 px-3 py-1 bg-white hover:bg-gray-50 text-indigo-700 text-xs font-semibold rounded-lg border border-indigo-200 shadow-2xs transition-colors cursor-pointer" title="Salin URL Webhook Callback">
                                                    <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                    Salin Webhook URL
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Webhook URL Display -->
                                        <div class="flex flex-col sm:flex-row sm:items-center gap-2 bg-white/90 p-2.5 rounded-lg border border-gray-200 text-xs">
                                            <span class="font-bold text-gray-500 shrink-0">Callback URL:</span>
                                            <code class="font-mono text-indigo-700 bg-gray-50 px-2 py-0.5 rounded text-[11px] select-all flex-1 break-all">{{ webhookUrl }}</code>
                                            <span class="text-[10px] text-gray-400 shrink-0">(Daftarkan di dashboard {{ form.pg_provider }})</span>
                                        </div>

                                        <!-- Channel Badges -->
                                        <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mr-1">Metode Didukung:</span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white text-gray-700 border border-gray-200 shadow-2xs">QRIS (Semua E-Wallet)</span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white text-gray-700 border border-gray-200 shadow-2xs">BCA VA</span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white text-gray-700 border border-gray-200 shadow-2xs">BRI VA</span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white text-gray-700 border border-gray-200 shadow-2xs">BNI VA</span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white text-gray-700 border border-gray-200 shadow-2xs">Mandiri VA</span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white text-gray-700 border border-gray-200 shadow-2xs">Permata / CIMB / BSI</span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white text-gray-700 border border-gray-200 shadow-2xs">Alfamart & Indomaret</span>
                                        </div>
                                    </div>
                                </Transition>

                                <!-- Form Kredensial Gateway Sesuai Provider Yang Dipilih -->
                                <Transition name="fade">
                                    <div v-if="form.pg_provider !== 'none' && form.pg_provider !== 'simulator'" class="grid grid-cols-1 md:grid-cols-2 gap-5 bg-gray-50 p-5 rounded-xl border border-gray-200">
                                        <!-- Merchant ID / Code / VA -->
                                        <div v-if="['tripay', 'midtrans', 'duitku', 'ipaymu'].includes(form.pg_provider)">
                                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">
                                                {{ form.pg_provider === 'tripay' ? 'Merchant Code' : (form.pg_provider === 'midtrans' ? 'Merchant ID' : (form.pg_provider === 'duitku' ? 'Merchant Code Duitku' : 'Nomor Virtual Account / User ID iPaymu')) }}
                                            </label>
                                            <input type="text" v-model="form.pg_merchant_id" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono" :placeholder="form.pg_provider === 'tripay' ? 'e.g. T12345' : (form.pg_provider === 'duitku' ? 'e.g. D1234' : 'e.g. 000000XXXXXXXXXX')">
                                            <p class="text-[10px] text-gray-400 mt-1">Ditemukan pada menu Pengaturan Akun / Merchant di dashboard.</p>
                                        </div>

                                        <!-- API Key / Server Key / Secret Key -->
                                        <div>
                                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">
                                                {{ form.pg_provider === 'midtrans' ? 'Server Key' : (form.pg_provider === 'xendit' ? 'Secret API Key' : 'API Key') }}
                                            </label>
                                            <input type="text" v-model="form.pg_api_key" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono" :placeholder="form.pg_provider === 'xendit' ? 'xnd_development_... / xnd_production_...' : (form.pg_provider === 'midtrans' ? 'SB-Mid-server-... / Mid-server-...' : 'API Key gateway...')">
                                            <p class="text-[10px] text-gray-400 mt-1">Kunci API utama untuk membuat transaksi tagihan pelanggan.</p>
                                        </div>

                                        <!-- Private Key / Client Key -->
                                        <div v-if="['tripay', 'midtrans'].includes(form.pg_provider)">
                                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">
                                                {{ form.pg_provider === 'midtrans' ? 'Client Key' : 'Private Key' }}
                                            </label>
                                            <input type="password" v-model="form.pg_private_key" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono" placeholder="Kunci rahasia private/client key...">
                                            <p class="text-[10px] text-gray-400 mt-1">Digunakan untuk validasi signature transaksi & callback.</p>
                                        </div>

                                        <!-- Callback Token / Webhook Verification -->
                                        <div v-if="['tripay', 'xendit', 'paydisini'].includes(form.pg_provider)">
                                            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">
                                                {{ form.pg_provider === 'xendit' ? 'Webhook Verification Token (x-callback-token)' : 'Callback Secret / Token' }}
                                            </label>
                                            <input type="password" v-model="form.pg_callback_token" class="form-input block w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono" placeholder="Token verifikasi webhook...">
                                            <p class="text-[10px] text-gray-400 mt-1">Digunakan untuk memastikan webhook benar-benar dikirim dari gateway.</p>
                                        </div>
                                    </div>

                                    <div v-else-if="form.pg_provider === 'simulator'" class="p-4 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800 space-y-1">
                                        <div class="font-bold flex items-center gap-1.5 text-amber-900">
                                            <svg class="w-4 h-4 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                                            Mode Simulator Sandbox Aktif
                                        </div>
                                        <p>
                                            Sistem akan mensimulasikan QRIS dan Virtual Account secara otomatis tanpa membutuhkan akun/API Key eksternal. Anda dan pelanggan dapat menguji alur pembayaran dan menekan tombol simulasi sukses untuk verifikasi otomatis.
                                        </p>
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

                            <!-- Pilihan 10 Template Invoice Bermotif & Elegan -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label
                                    v-for="tpl in invoiceTemplates"
                                    :key="tpl.id"
                                    class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition-all hover:shadow-md"
                                    :class="form.invoice_template === tpl.id ? 'border-indigo-600 bg-indigo-50/20 ring-2 ring-indigo-500 shadow-sm' : 'border-gray-200 bg-white hover:border-gray-300'"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xl">{{ tpl.icon }}</span>
                                            <span class="font-bold text-sm text-gray-900">{{ tpl.name }}</span>
                                        </div>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" :class="tpl.badgeClass">
                                            {{ tpl.badge }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2 leading-relaxed flex-1">
                                        {{ tpl.desc }}
                                    </p>
                                    <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-400">
                                        <div class="flex items-center gap-2">
                                            <span>{{ tpl.tag }}</span>
                                            <a
                                                :href="'/settings/invoice-template/preview?template=' + tpl.id"
                                                target="_blank"
                                                @click.stop
                                                class="text-indigo-600 hover:text-indigo-800 font-bold underline text-[11px] inline-flex items-center gap-0.5 cursor-pointer"
                                                title="Buka pratinjau invoice dengan template ini"
                                            >
                                                Pratinjau ↗
                                            </a>
                                        </div>
                                        <input type="radio" :value="tpl.id" v-model="form.invoice_template" class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
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
            <div class="lg:w-5/12 xl:w-1/3">
                <div class="bg-gradient-to-br from-indigo-950 via-indigo-900 to-slate-900 rounded-3xl shadow-xl border border-indigo-700/60 text-white p-6 sticky top-6 max-h-[calc(100vh-3rem)] overflow-y-auto space-y-5 custom-scrollbar">
                    
                    <!-- Header -->
                    <div class="flex items-start justify-between gap-3 pb-4 border-b border-indigo-700/40">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center shadow-md shadow-indigo-500/20">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-base text-white flex items-center gap-2">
                                    Simulasi AI
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wide uppercase shadow-sm" :class="form.billing_type === 'pascabayar' ? (form.billing_cycle === 'anniversary' ? 'bg-purple-500/20 text-purple-300 border border-purple-400/30' : (form.prorata_first_month ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-400/30' : 'bg-amber-500/20 text-amber-300 border border-amber-400/30')) : 'bg-cyan-500/20 text-cyan-300 border border-cyan-400/30'">
                                        {{ form.billing_type === 'pascabayar' ? (form.billing_cycle === 'anniversary' ? 'Pascabayar (Anniversary - Penuh)' : (form.prorata_first_month ? 'Pascabayar (Prorata Kalender)' : 'Pascabayar (Normal)')) : 'Prabayar (Prepaid)' }}
                                    </span>
                                </h3>
                                <p class="text-[11px] text-indigo-200 mt-0.5">Kalkulasi cerdas live sesuai pengaturan</p>
                            </div>
                        </div>
                    </div>

                    <!-- Sandbox Interactive Controls -->
                    <div class="bg-indigo-900/40 rounded-xl p-3.5 border border-indigo-700/50 space-y-3">
                        <div class="flex items-center justify-between text-xs font-semibold text-indigo-200">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                                Uji Coba Parameter
                            </span>
                            <span class="text-[10px] text-indigo-400 font-normal">Klik untuk simulasi</span>
                        </div>

                        <!-- Harga Paket -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-[10px] uppercase tracking-wider text-indigo-300 font-bold">Harga Paket / Bln</label>
                                <span class="text-xs font-bold text-white">{{ formatCurrency(simPrice) }}</span>
                            </div>
                            <div class="grid grid-cols-4 gap-1.5 mb-1.5">
                                <button v-for="price in quickPrices" :key="price" type="button" @click="simPrice = price" class="py-1 px-1 text-[11px] rounded-lg font-medium transition-all text-center cursor-pointer" :class="simPrice === price ? 'bg-indigo-500 text-white shadow-sm ring-1 ring-white/50 font-bold' : 'bg-white/10 text-indigo-200 hover:bg-white/20'">
                                    {{ price / 1000 }}rb
                                </button>
                            </div>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-xs text-indigo-400 font-semibold">Rp</span>
                                <input type="number" v-model.number="simPrice" step="10000" min="0" class="w-full pl-8 pr-2 py-1 text-xs bg-white/10 border border-indigo-700/60 rounded-lg text-white placeholder-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400" placeholder="Custom harga...">
                            </div>
                        </div>

                        <!-- Tanggal Aktivasi -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-[10px] uppercase tracking-wider text-indigo-300 font-bold">Simulasi Tanggal Aktivasi</label>
                                <span class="text-[11px] font-semibold text-emerald-300">{{ simActivationDateFormatted }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 mb-1.5">
                                <input type="date" v-model="simActivationDate" class="w-full px-2 py-1 text-xs bg-white/10 border border-indigo-700/60 rounded-lg text-white focus:outline-none focus:ring-1 focus:ring-indigo-400 cursor-pointer">
                            </div>
                            <div class="flex flex-wrap gap-1">
                                <button type="button" @click="setQuickDate('today')" class="px-2 py-0.5 rounded text-[10px] bg-white/10 hover:bg-white/20 text-indigo-200 cursor-pointer">Hari Ini</button>
                                <button type="button" @click="setQuickDate('day1')" class="px-2 py-0.5 rounded text-[10px] bg-white/10 hover:bg-white/20 text-indigo-200 cursor-pointer">Tgl 1</button>
                                <button type="button" @click="setQuickDate('day10')" class="px-2 py-0.5 rounded text-[10px] bg-white/10 hover:bg-white/20 text-indigo-200 cursor-pointer">Tgl 10</button>
                                <button type="button" @click="setQuickDate('day15')" class="px-2 py-0.5 rounded text-[10px] bg-white/10 hover:bg-white/20 text-indigo-200 cursor-pointer">Tgl 15</button>
                                <button type="button" @click="setQuickDate('day20')" class="px-2 py-0.5 rounded text-[10px] bg-white/10 hover:bg-white/20 text-indigo-200 cursor-pointer">Tgl 20</button>
                                <button type="button" @click="setQuickDate('day25')" class="px-2 py-0.5 rounded text-[10px] bg-white/10 hover:bg-white/20 text-indigo-200 cursor-pointer">Tgl 25</button>
                            </div>
                        </div>
                    </div>

                    <!-- Siklus Terbit & Isolir -->
                    <div class="grid grid-cols-1 gap-2.5">
                        <div class="bg-white/10 backdrop-blur-md border border-white/10 rounded-xl p-3.5">
                            <div class="flex items-center gap-2 mb-1">
                                <div class="w-2 h-2 rounded-full" :class="form.billing_type === 'prabayar' ? 'bg-cyan-400' : 'bg-blue-400'"></div>
                                <span class="text-[11px] font-bold text-indigo-200 uppercase tracking-wider">
                                    {{ form.billing_type === 'prabayar' ? 'Tagihan Pertama (Aktivasi)' : 'Terbit Tagihan Rutin' }}
                                </span>
                            </div>
                            <p class="text-sm font-semibold pl-4 text-white">{{ nextRoutineBillingDateFormatted }}</p>
                            <p class="text-[10px] text-indigo-300 pl-4 mt-0.5">
                                {{ form.billing_type === 'prabayar' ? 'Pelanggan prabayar membayar invoice saat aktivasi sebelum layanan aktif' : (form.billing_cycle === 'anniversary' ? 'Tagihan otomatis terbit di tanggal yang sama tiap bulan (Anniversary)' : 'Sistem otomatis generate invoice pelanggan tiap bulannya') }}
                            </p>
                        </div>

                        <div class="bg-white/10 backdrop-blur-md border border-red-500/30 rounded-xl p-3.5 relative overflow-hidden">
                            <div class="absolute right-0 top-0 w-16 h-16 bg-red-500/10 rounded-bl-full pointer-events-none"></div>
                            <div class="flex items-center gap-2 mb-1">
                                <div class="w-2 h-2 rounded-full bg-red-400 animate-pulse"></div>
                                <span class="text-[11px] font-bold text-red-200 uppercase tracking-wider">Tanggal & Jam Isolir</span>
                            </div>
                            <p class="text-sm font-bold text-white pl-4">Tgl {{ isolateDateFormatted }}</p>
                            <p class="text-[10px] text-red-200 pl-4 mt-0.5">Tanggal {{ form.isolate_day }}, pukul {{ form.isolate_time || '00:00' }} WIB · diperiksa setiap menit</p>
                        </div>
                    </div>

                    <!-- Hasil Perhitungan Tagihan Pertama (Simulasi) -->
                    <!-- Skenario 1: Pascabayar + Kalender Serentak + Prorata Aktif -->
                    <div v-if="form.billing_type === 'pascabayar' && form.billing_cycle === 'calendar' && form.prorata_first_month" class="bg-gradient-to-b from-indigo-900/60 to-indigo-950/80 border border-indigo-500/40 rounded-xl p-4 space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-indigo-700/50">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                <h4 class="text-xs font-bold text-emerald-300 uppercase tracking-wide">Rincian Prorata Kalender</h4>
                            </div>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-bold">1x di Bulan Pertama</span>
                        </div>
                        
                        <div class="space-y-2 text-xs text-indigo-100">
                            <div class="flex justify-between items-center border-b border-indigo-800/40 pb-1.5">
                                <span class="text-indigo-300">Harga Paket Standar:</span>
                                <span class="font-semibold text-white">{{ formatCurrency(simPrice) }} / bln</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-indigo-800/40 pb-1.5">
                                <span class="text-indigo-300">Tanggal Aktivasi:</span>
                                <span class="font-semibold text-white">{{ simActivationDateFormatted }}</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-indigo-800/40 pb-1.5">
                                <span class="text-indigo-300">Siklus Kalender ({{ simCycleDays }} Hari):</span>
                                <span class="font-semibold text-white">s/d {{ prevIssueDateFormatted }}</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-indigo-800/40 pb-1.5">
                                <span class="text-indigo-300">Hari Pemakaian Aktif:</span>
                                <span class="font-bold text-emerald-300 bg-emerald-950/60 px-2 py-0.5 rounded border border-emerald-500/30">{{ simDaysUsed }} Hari Aktif</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-indigo-800/40 pb-1.5">
                                <span class="text-indigo-300">Subtotal Prorata:</span>
                                <span class="font-bold text-white">{{ formatCurrency(simSubtotal) }}</span>
                            </div>
                            <div v-if="simTaxRate > 0" class="flex justify-between items-center border-b border-indigo-800/40 pb-1.5">
                                <span class="text-indigo-300">PPN ({{ simTaxRate }}%):</span>
                                <span class="font-medium text-amber-300">+ {{ formatCurrency(simTax) }}</span>
                            </div>
                            <div class="flex justify-between items-baseline pt-1">
                                <span class="text-xs font-bold text-white">Total Tagihan Pertama:</span>
                                <span class="font-extrabold text-xl text-emerald-400">{{ formatCurrency(simGrandTotal) }}</span>
                            </div>
                        </div>

                        <div class="bg-indigo-950/70 p-2.5 rounded-lg border border-indigo-800/50 text-[10px] space-y-1">
                            <p class="text-indigo-300">
                                <strong class="text-white">Rumusan Aktif:</strong>
                                <span v-if="form.prorata_formula === 'fixed_30'"> Konstan 30 Hari &mdash; ({{ formatCurrency(simPrice) }} ÷ 30) × {{ simDaysUsed }} hr</span>
                                <span v-else-if="form.prorata_formula === 'mid_month'"> Paruh Bulan &mdash; {{ simActivationDay > 15 ? 'Aktivasi > tgl 15 = Diskon 50%' : 'Aktivasi <= tgl 15 = 100% Penuh' }}</span>
                                <span v-else> Sangat Akurat (Bulan Berjalan) &mdash; ({{ formatCurrency(simPrice) }} ÷ {{ simCycleDays }} hr) × {{ simDaysUsed }} hr</span>
                            </p>
                            <p class="text-[9px] text-indigo-400 italic">
                                * Bulan ke-2 dan seterusnya tagihan otomatis normal {{ formatCurrency(simGrandTotalNormal) }} per tanggal kalender serentak.
                            </p>
                        </div>
                    </div>

                    <!-- Skenario 2: Pascabayar + Sesuai Tanggal Aktivasi (Anniversary Billing) -->
                    <div v-else-if="form.billing_type === 'pascabayar' && form.billing_cycle === 'anniversary'" class="bg-gradient-to-b from-purple-900/40 to-indigo-950/80 border border-purple-500/40 rounded-xl p-4 space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-purple-700/40">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <h4 class="text-xs font-bold text-purple-300 uppercase tracking-wide">Siklus Anniversary (1 Bulan Penuh)</h4>
                            </div>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-300 font-bold">Tanpa Prorata</span>
                        </div>
                        
                        <div class="space-y-2 text-xs text-indigo-100">
                            <div class="flex justify-between items-center border-b border-indigo-800/40 pb-1.5">
                                <span class="text-indigo-300">Biaya Paket (1 Bulan):</span>
                                <span class="font-semibold text-white">{{ formatCurrency(simPrice) }}</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-indigo-800/40 pb-1.5">
                                <span class="text-indigo-300">Tanggal Aktivasi:</span>
                                <span class="font-semibold text-white">{{ simActivationDateFormatted }}</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-indigo-800/40 pb-1.5">
                                <span class="text-indigo-300">Periode Berjalan:</span>
                                <span class="font-medium text-white">{{ simActivationDateFormatted }} s/d {{ prabayarEndDateFormatted }}</span>
                            </div>
                            <div v-if="simTaxRate > 0" class="flex justify-between items-center border-b border-indigo-800/40 pb-1.5">
                                <span class="text-indigo-300">PPN ({{ simTaxRate }}%):</span>
                                <span class="font-medium text-purple-300">+ {{ formatCurrency(simTaxNormal) }}</span>
                            </div>
                            <div class="flex justify-between items-baseline pt-1">
                                <span class="text-xs font-bold text-white">Total Tagihan Pertama:</span>
                                <span class="font-extrabold text-xl text-purple-400">{{ formatCurrency(simGrandTotalNormal) }}</span>
                            </div>
                        </div>

                        <div class="bg-purple-950/40 p-2.5 rounded-lg border border-purple-800/40 text-[10px] text-purple-200">
                            Tagihan dihitung flat 1 bulan penuh terhitung sejak tanggal aktivasi. Tagihan rutin bulan berikutnya otomatis terbit pada <strong>{{ nextRoutineBillingDateFormatted }}</strong>.
                        </div>
                    </div>

                    <!-- Skenario 3: Pascabayar Kalender + Prorata Non-Aktif -->
                    <div v-else-if="form.billing_type === 'pascabayar' && !form.prorata_first_month" class="bg-gradient-to-b from-amber-900/40 to-indigo-950/80 border border-amber-500/40 rounded-xl p-4 space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-amber-700/40">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <h4 class="text-xs font-bold text-amber-300 uppercase tracking-wide">Prorata Tagihan Pertama Non-Aktif</h4>
                            </div>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 font-bold">1 Bulan Penuh</span>
                        </div>
                        
                        <div class="space-y-2 text-xs text-indigo-100">
                            <div class="flex justify-between items-center border-b border-indigo-800/40 pb-1.5">
                                <span class="text-indigo-300">Biaya Paket Standar:</span>
                                <span class="font-semibold text-white">{{ formatCurrency(simPrice) }}</span>
                            </div>
                            <div v-if="simTaxRate > 0" class="flex justify-between items-center border-b border-indigo-800/40 pb-1.5">
                                <span class="text-indigo-300">PPN ({{ simTaxRate }}%):</span>
                                <span class="font-medium text-amber-300">+ {{ formatCurrency(simTaxNormal) }}</span>
                            </div>
                            <div class="flex justify-between items-baseline pt-1">
                                <span class="text-xs font-bold text-white">Total Tagihan Pertama:</span>
                                <span class="font-extrabold text-xl text-amber-400">{{ formatCurrency(simGrandTotalNormal) }}</span>
                            </div>
                        </div>

                        <div class="bg-amber-950/40 p-2.5 rounded-lg border border-amber-800/40 text-[10px] text-amber-200">
                            Pelanggan baru langsung dikenakan tagihan normal 1 bulan penuh pascabayar tanpa kalkulasi pemotongan hari.
                        </div>
                    </div>

                    <!-- Skenario 4: Prabayar (Prepaid) -->
                    <div v-else class="bg-gradient-to-b from-cyan-900/40 to-indigo-950/80 border border-cyan-500/40 rounded-xl p-4 space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-cyan-700/40">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                <h4 class="text-xs font-bold text-cyan-300 uppercase tracking-wide">Siklus Prabayar (Prepaid)</h4>
                            </div>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-cyan-500/20 text-cyan-300 font-bold">Bayar di Muka</span>
                        </div>
                        
                        <div class="space-y-2 text-xs text-indigo-100">
                            <div class="flex justify-between items-center border-b border-indigo-800/40 pb-1.5">
                                <span class="text-indigo-300">Biaya Paket (1 Bulan):</span>
                                <span class="font-semibold text-white">{{ formatCurrency(simPrice) }}</span>
                            </div>
                            <div v-if="simTaxRate > 0" class="flex justify-between items-center border-b border-indigo-800/40 pb-1.5">
                                <span class="text-indigo-300">PPN ({{ simTaxRate }}%):</span>
                                <span class="font-medium text-cyan-300">+ {{ formatCurrency(simTaxNormal) }}</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-indigo-800/40 pb-1.5">
                                <span class="text-indigo-300">Masa Aktif Layanan:</span>
                                <span class="font-medium text-white">{{ simActivationDateFormatted }} s/d {{ prabayarEndDateFormatted }}</span>
                            </div>
                            <div class="flex justify-between items-baseline pt-1">
                                <span class="text-xs font-bold text-white">Total Bayar saat Aktivasi:</span>
                                <span class="font-extrabold text-xl text-cyan-400">{{ formatCurrency(simGrandTotalNormal) }}</span>
                            </div>
                        </div>

                        <div class="bg-cyan-950/40 p-2.5 rounded-lg border border-cyan-800/40 text-[10px] text-cyan-200">
                            Pelanggan membayar penuh di muka sebelum internet diaktifkan. Tagihan perpanjangan bulan berikutnya terbit otomatis sebelum masa aktif habis.
                        </div>
                    </div>

                    <!-- Catatan Sistem -->
                    <div class="pt-3 border-t border-indigo-700/40">
                        <p class="text-[10px] text-indigo-300 leading-relaxed">
                            <span class="font-semibold text-white">Sinkronisasi Penuh:</span> Logika simulasi ini 100% identik dengan alur aktivasi pelanggan baru di menu Pelanggan & generator invoice otomatis sistem.
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';
import { billingIsolationDate } from '@/Utils/billingIsolation';

const props = defineProps({
    billing_cycle: String,
    billing_type: String,
    prorata_formula: String,
    prorata_first_month: Boolean,
    invoice_issue_date: [String, Number],
    isolate_days: [String, Number],
    isolate_day: [String, Number],
    billing_timezone: String,
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
    billing_cycle: props.billing_cycle || 'anniversary',
    billing_type: props.billing_type || 'prabayar',
    prorata_formula: props.prorata_formula || 'exact_days',
    prorata_first_month: props.prorata_first_month !== undefined ? props.prorata_first_month : true,
    invoice_issue_date: props.invoice_issue_date || '1',
    isolate_day: props.isolate_day ?? 4,
    isolate_time: props.isolate_time || '00:00',
    invoice_template: props.invoice_template || 'modern',
    payment_banks: props.payment_banks ? JSON.parse(JSON.stringify(props.payment_banks)) : [],
    pg_provider: props.pg_provider || 'none',
    pg_environment: props.pg_environment || 'sandbox',
    pg_merchant_id: props.pg_merchant_id || '',
    pg_api_key: props.pg_api_key || '',
    pg_private_key: props.pg_private_key || '',
    pg_callback_token: props.pg_callback_token || '',
    tax_ppn: props.tax_ppn !== undefined ? props.tax_ppn : 0,
    tax_bhp: props.tax_bhp !== undefined ? props.tax_bhp : 0,
    tax_uso: props.tax_uso !== undefined ? props.tax_uso : 0,
});

// Sync watchers when props change (e.g. after save redirect)
watch(() => props.billing_cycle, (val) => { if (val) form.billing_cycle = val; });
watch(() => props.billing_type, (val) => { if (val) form.billing_type = val; });
watch(() => props.prorata_formula, (val) => { if (val) form.prorata_formula = val; });
watch(() => props.prorata_first_month, (val) => { form.prorata_first_month = val !== undefined ? val : true; });
watch(() => props.invoice_issue_date, (val) => { if (val) form.invoice_issue_date = val; });
watch(() => props.isolate_day, (val) => { if (val != null) form.isolate_day = val; });
watch(() => props.isolate_time, (val) => { if (val) form.isolate_time = val; });
watch(() => props.payment_banks, (val) => { if (val) form.payment_banks = JSON.parse(JSON.stringify(val)); });
watch(() => props.pg_provider, (val) => { if (val) form.pg_provider = val; });
watch(() => props.pg_environment, (val) => { if (val) form.pg_environment = val; });
watch(() => props.pg_merchant_id, (val) => { form.pg_merchant_id = val || ''; });
watch(() => props.pg_api_key, (val) => { form.pg_api_key = val || ''; });
watch(() => props.pg_private_key, (val) => { form.pg_private_key = val || ''; });
watch(() => props.pg_callback_token, (val) => { form.pg_callback_token = val || ''; });
watch(() => props.tax_ppn, (val) => { form.tax_ppn = val !== undefined ? val : 0; });
watch(() => props.tax_bhp, (val) => { form.tax_bhp = val !== undefined ? val : 0; });
watch(() => props.invoice_template, (val) => { if (val) form.invoice_template = val; });

const invoiceTemplates = [
    {
        id: 'modern',
        name: 'Modern Clean',
        icon: '🌟',
        badge: 'Standar Populer',
        badgeClass: 'bg-indigo-100 text-indigo-700',
        desc: 'Header gradien biru elegan, card info pelanggan rapi, tabel biaya terstruktur, dan kartu rekening transfer jelas. Paling fleksibel untuk kirim via WhatsApp PDF & Cetak A4.',
        tag: 'Ukuran: A4 / WhatsApp PDF',
    },
    {
        id: 'geometric_mesh',
        name: 'Futuristic Fiber Mesh',
        icon: '⚡',
        badge: 'Motif Heksagonal',
        badgeClass: 'bg-sky-100 text-sky-700 border border-sky-200',
        desc: 'Header gelap kosmik bermotif jaring heksagonal serat optik (fiber mesh), aksen neon cyan cyber, telemetry card, dan stempel verifikasi NOC high-speed.',
        tag: 'Gaya: Motif Serat Fiber',
    },
    {
        id: 'luxury_gold',
        name: 'Royal Gold & Carbon',
        icon: '👑',
        badge: 'Motif Karbon VIP',
        badgeClass: 'bg-amber-100 text-amber-800 border border-amber-200',
        desc: 'Tampilan ultra premium bermotif tenun serat karbon (carbon-fiber weave), garis ganda emas sampanye (metallic gold), tipografi klasik berkelas, dan segel resmi VIP.',
        tag: 'Gaya: Enterprise & VIP',
    },
    {
        id: 'cyber_circuit',
        name: 'Cyber NOC Circuit',
        icon: '🌐',
        badge: 'Motif PCB Emerald',
        badgeClass: 'bg-emerald-100 text-emerald-800 border border-emerald-200',
        desc: 'Tema Network Operations Center (NOC) dengan motif jalur sirkuit PCB hijau neon, tipografi terminal monospaced, dan hash enkripsi digital verification.',
        tag: 'Gaya: Tech NOC Terminal',
    },
    {
        id: 'corporate_wave',
        name: 'Guilloche Security Wave',
        icon: '🌊',
        badge: 'Motif Gelombang Sekuritas',
        badgeClass: 'bg-teal-100 text-teal-800 border border-teal-200',
        desc: 'Format tagihan perbankan resmi dengan motif pola gelombang harmonik guilloche sekuritas, ornamen sertifikat anti-pemalsuan, dan watermark resmi.',
        tag: 'Gaya: Banknote Security',
    },
    {
        id: 'aurora_flow',
        name: 'Dynamic Aurora Flow',
        icon: '✨',
        badge: 'Motif Gradasi Prisma',
        badgeClass: 'bg-purple-100 text-purple-700 border border-purple-200',
        desc: 'Gradasi dinamis aurora violet-cyan modern dengan lengkungan wave kontur prisma, kartu membulat modern glassmorphism, dan tampilan ramah pengguna.',
        tag: 'Gaya: Vibrant Modern Tech',
    },
    {
        id: 'retro_kraft',
        name: 'Vintage Postal Kraft',
        icon: '📜',
        badge: 'Motif Perangko Klasik',
        badgeClass: 'bg-yellow-100 text-yellow-800 border border-yellow-200',
        desc: 'Sentuhan kertas kraft vintage bertekstur titik halus dengan cap pos (postmark) stempel perangko tinta merah klasik, garis buku kas ganda, dan font mesin ketik.',
        tag: 'Gaya: Retro & Authentic Ledger',
    },
    {
        id: 'classic',
        name: 'Classic Corporate',
        icon: '🏛️',
        badge: 'Formal Surat',
        badgeClass: 'bg-slate-100 text-slate-700',
        desc: 'Format surat resmi perusahaan dengan kop surat garis ganda, rincian perpajakan (PPN/DPP) lengkap, serta kolom tanda tangan basah & stempel resmi.',
        tag: 'Cocok: Instansi & Bisnis B2B',
    },
    {
        id: 'minimalist',
        name: 'Elegant Minimalist',
        icon: '🎨',
        badge: 'Modern Tech',
        badgeClass: 'bg-purple-100 text-purple-700',
        desc: 'Desain monokromatik kontras tinggi dengan kartu sisa tagihan beraksen hitam gelap modern. Tampilan bersih, tipografi tajam, dan hemat tinta cetak.',
        tag: 'Gaya: Monokrom & Tech ISP',
    },
    {
        id: 'thermal',
        name: 'Struk Thermal (POS)',
        icon: '🧾',
        badge: 'Hemat Kertas',
        badgeClass: 'bg-blue-100 text-blue-700',
        desc: 'Format struk monospaced ringkas khusus printer kasir atau printer mini bluetooth (58mm / 80mm). Praktis untuk penagih lapangan & loket kasir biller.',
        tag: 'Ukuran: 58mm / 80mm Thermal',
    },
];

const savingSection = ref(null);
const savedSection = ref(null);

const webhookUrl = computed(() => {
    return typeof window !== 'undefined' ? `${window.location.origin}/api/payment-gateway/callback` : '/api/payment-gateway/callback';
});

function copyWebhookUrl() {
    if (typeof navigator !== 'undefined' && navigator.clipboard) {
        navigator.clipboard.writeText(webhookUrl.value);
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Webhook URL Berhasil Disalin!',
            text: 'Tempelkan URL ini pada menu Webhook / Notification URL di dashboard gateway Anda.',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    }
}

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

    let payload = { _section: sectionLabel };

    if (sectionKey === 'billing_type') {
        payload.billing_cycle = form.billing_cycle;
        payload.billing_type = form.billing_type;
        payload.prorata_formula = form.prorata_formula;
        payload.prorata_first_month = form.prorata_first_month;
    } else if (sectionKey === 'timeline') {
        payload.invoice_issue_date = form.invoice_issue_date;
        payload.isolate_day = form.isolate_day;
        payload.isolate_time = form.isolate_time;
    } else if (sectionKey === 'tax') {
        payload.tax_ppn = form.tax_ppn;
        payload.tax_bhp = form.tax_bhp;
        payload.tax_uso = form.tax_uso;
    } else if (sectionKey === 'banks') {
        payload.payment_banks = form.payment_banks;
    } else if (sectionKey === 'pg') {
        payload.pg_provider = form.pg_provider;
        payload.pg_environment = form.pg_environment;
        payload.pg_merchant_id = form.pg_merchant_id;
        payload.pg_api_key = form.pg_api_key;
        payload.pg_private_key = form.pg_private_key;
        payload.pg_callback_token = form.pg_callback_token;
    } else if (sectionKey === 'template') {
        payload.invoice_template = form.invoice_template;
    } else {
        payload = {
            ...form.data(),
            _section: sectionLabel
        };
    }

    router.post(route('settings.billing.update'), payload, {
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
            const firstError = Object.values(errors || {})[0] || 'Periksa kembali data yang dimasukkan.';
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

    const payload = {
        ...form.data(),
        _section: 'Semua Konfigurasi Billing'
    };

    router.post(route('settings.billing.update'), payload, {
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
            const firstError = Object.values(errors || {})[0] || 'Periksa kembali data yang dimasukkan.';
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

// Interactive Simulator Controls
const quickPrices = [100000, 150000, 200000, 300000];
const simPrice = ref(150000);

function formatDateToInput(d) {
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

const simActivationDate = ref(formatDateToInput(new Date()));

const simActivationDateObj = computed(() => {
    if (!simActivationDate.value) return new Date();
    const parts = simActivationDate.value.split('-').map(Number);
    if (parts.length === 3 && parts[0] && parts[1] && parts[2]) {
        return new Date(parts[0], parts[1] - 1, parts[2]);
    }
    return new Date();
});

const simActivationDay = computed(() => {
    return simActivationDateObj.value.getDate();
});

const simActivationDateFormatted = computed(() => {
    const d = simActivationDateObj.value;
    return `${d.getDate()} ${monthNames[d.getMonth()]} ${d.getFullYear()}`;
});

function setQuickDate(type) {
    const cur = simActivationDateObj.value;
    const year = cur.getFullYear();
    const month = cur.getMonth();
    if (type === 'today') {
        simActivationDate.value = formatDateToInput(new Date());
    } else if (type === 'day1') {
        simActivationDate.value = formatDateToInput(new Date(year, month, 1));
    } else if (type === 'day10') {
        simActivationDate.value = formatDateToInput(new Date(year, month, 10));
    } else if (type === 'day15') {
        simActivationDate.value = formatDateToInput(new Date(year, month, 15));
    } else if (type === 'day20') {
        simActivationDate.value = formatDateToInput(new Date(year, month, 20));
    } else if (type === 'day25') {
        simActivationDate.value = formatDateToInput(new Date(year, month, 25));
    }
}

// Next routine billing date / cut-off date matching CustomerController.php & Activation.vue
const nextRoutineBillingDateObj = computed(() => {
    const act = simActivationDateObj.value;
    let issueDay = parseInt(form.invoice_issue_date) || 1;
    if (issueDay > 28) issueDay = 28;

    if (form.billing_type === 'prabayar') {
        return new Date(act.getFullYear(), act.getMonth(), act.getDate());
    }

    if (form.billing_cycle === 'anniversary') {
        return new Date(act.getFullYear(), act.getMonth() + 1, act.getDate());
    }

    if (act.getDate() >= issueDay) {
        return new Date(act.getFullYear(), act.getMonth() + 1, issueDay);
    } else {
        return new Date(act.getFullYear(), act.getMonth(), issueDay);
    }
});

const nextRoutineBillingDateFormatted = computed(() => {
    const d = nextRoutineBillingDateObj.value;
    if (form.billing_type === 'prabayar') {
        return `Tgl ${d.getDate()} ${monthNames[d.getMonth()]} ${d.getFullYear()} (Saat Aktivasi)`;
    }
    if (form.billing_cycle === 'anniversary') {
        return `Tgl ${d.getDate()} ${monthNames[d.getMonth()]} ${d.getFullYear()} (Siklus 1 Bulan)`;
    }
    return `Tgl ${d.getDate()} ${monthNames[d.getMonth()]} ${d.getFullYear()}`;
});

// Isolate date based on routine billing date / activation date
const isolateDateObj = computed(() => {
    return billingIsolationDate(nextRoutineBillingDateObj.value, form);
});

const isolateDateFormatted = computed(() => {
    const d = isolateDateObj.value;
    return `${d.getDate()} ${monthNames[d.getMonth()]} ${d.getFullYear()} ${form.isolate_time || '00:00'} WIB`;
});

// Total days in activation month cycle (matching endOfMonth in CustomerController.php)
const simCycleDays = computed(() => {
    const d = simActivationDateObj.value;
    return new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
});

const prevIssueDateFormatted = computed(() => {
    const d = simActivationDateObj.value;
    const endOfMonth = new Date(d.getFullYear(), d.getMonth() + 1, 0);
    return `${endOfMonth.getDate()} ${monthNames[endOfMonth.getMonth()]} ${endOfMonth.getFullYear()}`;
});

// Active days used from activation date till end of month
const simDaysUsed = computed(() => {
    const d = simActivationDateObj.value;
    const endOfMonth = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
    return Math.max(1, endOfMonth - d.getDate() + 1);
});

// Prorata calculation matching CustomerController.php
const simSubtotal = computed(() => {
    const base = Math.round(Number(simPrice.value) || 0);
    if (base <= 0) return 0;

    // Mode Anniversary, Prabayar, atau tanpa Prorata: Bayar 1 bulan penuh (flat)
    if (form.billing_type === 'prabayar' || form.billing_cycle === 'anniversary' || !form.prorata_first_month) {
        return base;
    }

    const days = Math.round(simDaysUsed.value);
    const totalDays = Math.round(simCycleDays.value);

    if (days >= totalDays) return base;

    if (form.prorata_formula === 'fixed_30') {
        return Math.round((base / 30) * days);
    } else if (form.prorata_formula === 'mid_month') {
        if (simActivationDay.value > 15) {
            return Math.round(base / 2);
        }
        return base;
    } else {
        // default: exact_days
        return Math.round((base / totalDays) * days);
    }
});

// Tax rates & calculations
const simTaxRate = computed(() => {
    return Number(form.tax_ppn) || 0;
});

const simTax = computed(() => {
    return Math.round(simSubtotal.value * (simTaxRate.value / 100));
});

const simGrandTotal = computed(() => {
    return Math.round(simSubtotal.value + simTax.value);
});

const simTaxNormal = computed(() => {
    return Math.round((Math.round(Number(simPrice.value) || 0)) * (simTaxRate.value / 100));
});

const simGrandTotalNormal = computed(() => {
    return Math.round((Math.round(Number(simPrice.value) || 0)) + simTaxNormal.value);
});

// Prepaid 1-month service period
const prabayarEndDateFormatted = computed(() => {
    const d = new Date(simActivationDateObj.value);
    d.setMonth(d.getMonth() + 1);
    return `${d.getDate()} ${monthNames[d.getMonth()]} ${d.getFullYear()}`;
});

function formatCurrency(value) {
    const rounded = Math.round(Number(value) || 0);
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(rounded);
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

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(15, 23, 42, 0.4);
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(99, 102, 241, 0.5);
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(99, 102, 241, 0.8);
}
</style>
