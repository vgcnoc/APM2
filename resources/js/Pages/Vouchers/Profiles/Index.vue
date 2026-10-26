<template>
    <AppLayout title="Profil Voucher" subtitle="Kelola daftar profil, durasi, kecepatan, shared user, dan fee reseller">
        <!-- HEADER -->
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Daftar Profil Voucher Hotspot</h1>
                <p class="text-xs text-slate-500 mt-0.5">Kelola template profil voucher MikroTik RADIUS dengan kontrol batas shared user, durasi sesi, dan fee reseller.</p>
            </div>
            <button @click="openCreateModal" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-sm hover:shadow transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Tambah Profil Baru
            </button>
        </div>

        <!-- TABLE -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Nama Profil</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Tarif & Modal Reseller</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Durasi Sesi</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Limit Kecepatan</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Shared Users</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr v-for="profile in profiles" :key="profile.id" class="hover:bg-slate-50/60 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-bold text-slate-900">{{ profile.name }}</span>
                                    <span v-if="profile.is_customer_free" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-purple-100 text-purple-800 border border-purple-200 shadow-sm">
                                        ⭐ Hotspot Gratis Pelanggan
                                    </span>
                                </div>
                                <div v-if="profile.description" class="text-xs text-slate-500 mt-1 line-clamp-1">{{ profile.description }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div v-if="profile.is_customer_free">
                                    <span class="text-sm font-extrabold text-purple-600">Gratis (Rp 0)</span>
                                </div>
                                <div v-else>
                                    <div class="text-sm font-extrabold text-emerald-600">
                                        Rp {{ formatPrice(profile.price) }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-medium flex items-center gap-1.5 mt-0.5">
                                        <span>Modal: <strong class="text-slate-700">Rp {{ formatPrice(Math.max(0, profile.price - (profile.fee_reseller || 0))) }}</strong></span>
                                        <span class="text-slate-300">•</span>
                                        <span class="text-indigo-600">Fee: Rp {{ formatPrice(profile.fee_reseller || 0) }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5 text-xs font-bold px-2.5 py-1 rounded-lg" :class="profile.is_customer_free ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-slate-100 text-slate-700'">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ profile.is_customer_free ? 'Masa Aktif Pelanggan' : profile.duration }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5 text-xs font-mono font-semibold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    {{ profile.limit_rate || 'Unlimited' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div v-if="profile.shared_users == 1" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    1 User (Strict)
                                </div>
                                <div v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                    <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    {{ profile.shared_users }} Users
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="openEditModal(profile)" class="px-3 py-1.5 bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-600 rounded-lg text-xs font-bold transition-colors">
                                        Edit
                                    </button>
                                    <button v-if="!profile.is_customer_free" @click="deleteProfile(profile.id)" class="px-3 py-1.5 bg-slate-100 hover:bg-red-50 text-slate-700 hover:text-red-600 rounded-lg text-xs font-bold transition-colors">
                                        Hapus
                                    </button>
                                    <span v-else class="text-slate-400 text-xs italic font-medium px-2">Sistem</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="profiles.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <p class="text-slate-500 text-base font-medium">Belum ada data profil voucher.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL FORM -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-3 sm:p-4 animate-fade-in">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col border border-slate-200">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/70">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                            <span class="p-1.5 bg-indigo-100 text-indigo-600 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </span>
                            {{ editMode ? 'Edit Profil Voucher' : 'Tambah Profil Voucher Baru' }}
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Atur tarif, durasi sesi MikroTik, limit kecepatan, batas shared user, dan fee reseller.</p>
                    </div>
                    <button @click="closeModal" class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body with Scroll Reset -->
                <div ref="modalBodyRef" class="p-6 overflow-y-auto max-h-[75vh] space-y-5 bg-white text-slate-800">
                    <form @submit.prevent="submitForm" id="profileForm" class="space-y-5">
                        <!-- Switch Khusus Hotspot Pelanggan -->
                        <div class="p-3.5 bg-purple-50/80 rounded-xl border border-purple-200">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" v-model="form.is_customer_free" @change="onToggleCustomerFree" class="mt-0.5 rounded border-slate-300 text-purple-600 focus:ring-purple-500 w-4 h-4">
                                <div>
                                    <span class="text-xs font-bold text-purple-950">Profil Khusus Hotspot Gratis Pelanggan</span>
                                    <p class="text-[11px] text-purple-700 mt-0.5">Voucher khusus 1 user hotspot gratis yang diberikan saat aktivasi pelanggan. Masa aktif otomatis mengikuti status aktif pelanggan.</p>
                                </div>
                            </label>
                        </div>

                        <!-- SECTION 1: NAMA & DESKRIPSI -->
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Profil Voucher <span class="text-red-500">*</span></label>
                                <input v-model="form.name" type="text" class="w-full border-slate-300 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 px-3" required placeholder="Contoh: 1 Jam - Rp 3.000 / 1 Hari Unlimited">
                                <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Deskripsi Profil (Opsional)</label>
                                <textarea v-model="form.description" rows="2" class="w-full border-slate-300 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 px-3" placeholder="Contoh: Cocok untuk browsing cepat, streaming YouTube tanpa buffering"></textarea>
                            </div>
                        </div>

                        <!-- SECTION 2: TARIF & FEE RESELLER DENGAN KALKULATOR MODAL CERDAS -->
                        <div v-if="!form.is_customer_free" class="p-4 bg-slate-50/80 rounded-2xl border border-slate-200 space-y-3.5">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Tarif & Bagi Hasil Reseller / Mitra
                                </h3>
                                <span class="text-[11px] font-semibold text-slate-500">Kalkulator Otomatis</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">
                                        Harga Jual ke Pelanggan <span class="text-red-500">*</span>
                                    </label>
                                    <div class="flex shadow-sm rounded-xl overflow-hidden border border-slate-300 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
                                        <span class="inline-flex items-center px-3 bg-slate-100 text-xs font-bold text-slate-600 border-r border-slate-300">Rp</span>
                                        <input v-model.number="form.price" type="number" min="0" step="500" class="w-full border-0 focus:ring-0 text-sm font-bold text-slate-900 py-2 px-3" required placeholder="0">
                                    </div>
                                    <p v-if="form.errors.price" class="text-xs text-red-500 mt-1">{{ form.errors.price }}</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center justify-between">
                                        <span>Fee Reseller / Margin Mitra <span class="text-red-500">*Req</span></span>
                                    </label>
                                    <div class="flex shadow-sm rounded-xl overflow-hidden border border-slate-300 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
                                        <span class="inline-flex items-center px-3 bg-slate-100 text-xs font-bold text-slate-600 border-r border-slate-300">Rp</span>
                                        <input v-model.number="form.fee_reseller" type="number" min="0" step="100" class="w-full border-0 focus:ring-0 text-sm font-bold text-indigo-700 py-2 px-3" required placeholder="0">
                                    </div>
                                    <p v-if="form.errors.fee_reseller" class="text-xs text-red-500 mt-1">{{ form.errors.fee_reseller }}</p>
                                </div>
                            </div>

                            <!-- Live Breakdown Card -->
                            <div class="p-3.5 rounded-xl border transition-all" :class="isFeeExceeded ? 'bg-red-50 border-red-200' : 'bg-white border-slate-200 shadow-sm'">
                                <div v-if="isFeeExceeded" class="flex items-start gap-2 text-xs text-red-700 font-bold">
                                    <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span>Peringatan: Fee Reseller (Rp {{ formatPrice(form.fee_reseller) }}) tidak boleh melebihi Harga Jual (Rp {{ formatPrice(form.price) }}). Harap sesuaikan kembali.</span>
                                </div>
                                <div v-else class="grid grid-cols-3 gap-2 text-center divide-x divide-slate-100">
                                    <div>
                                        <div class="text-[10px] uppercase font-bold text-slate-400">Harga Jual</div>
                                        <div class="text-xs sm:text-sm font-extrabold text-slate-800 mt-0.5">Rp {{ formatPrice(form.price) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] uppercase font-bold text-indigo-500">Margin Reseller</div>
                                        <div class="text-xs sm:text-sm font-extrabold text-indigo-600 mt-0.5">Rp {{ formatPrice(form.fee_reseller) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] uppercase font-bold text-emerald-600">Modal Reseller</div>
                                        <div class="text-xs sm:text-sm font-extrabold text-emerald-700 mt-0.5">Rp {{ formatPrice(resellerCost) }}</div>
                                    </div>
                                </div>
                                <p v-if="!isFeeExceeded" class="text-[10px] text-slate-500 text-center mt-2 font-medium">
                                    💡 Saat reseller generate voucher, saldo mereka dipotong <strong>Rp {{ formatPrice(resellerCost) }}</strong> (Modal) dan mendapat keuntungan <strong>Rp {{ formatPrice(form.fee_reseller) }}</strong> per voucher.
                                </p>
                            </div>
                        </div>

                        <!-- SECTION 3: DURASI & MIKROTIK SESSION TIMEOUT -->
                        <div v-if="!form.is_customer_free" class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Durasi Sesi (MikroTik Format) <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <select v-model="durationChoice" class="w-full border-slate-300 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 px-3" required>
                                    <option value="" disabled>-- Pilih Durasi --</option>
                                    <option v-for="d in durationOptions" :key="d.value" :value="d.value">{{ d.label }} ({{ d.value }})</option>
                                    <option value="__custom">Kustom (Isi Sendiri)</option>
                                </select>
                                <input v-if="durationChoice === '__custom'" v-model="form.duration" type="text" class="w-full border-slate-300 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm py-2 px-3" required placeholder="Contoh: 30m, 2h, 1d, 7d">
                            </div>
                            <div v-if="form.duration" class="p-2.5 bg-blue-50/70 border border-blue-200/80 rounded-xl text-xs text-blue-800 flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>MikroTik Session-Timeout & Max-All-Session: <strong>{{ parsedDurationHuman }}</strong>. Router otomatis memutus koneksi saat waktu habis.</span>
                            </div>
                            <p v-if="form.errors.duration" class="text-xs text-red-500 mt-1">{{ form.errors.duration }}</p>
                        </div>
                        <div v-else class="p-3 bg-purple-50/50 rounded-xl border border-purple-100 text-xs text-purple-800">
                            <strong>Durasi:</strong> Mengikuti masa aktif langganan pelanggan (selama status pelanggan aktif).
                        </div>

                        <!-- SECTION 4: LIMIT RATE -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                                <span>Limit Rate (Kecepatan Download/Upload)</span>
                                <span class="text-[11px] text-slate-400 font-normal">Format: Upload/Download (Contoh: 1M/1M)</span>
                            </label>
                            <LimitRateCalculator v-model="form.limit_rate" />
                            <!-- Quick Chips -->
                            <div class="flex items-center gap-1.5 flex-wrap mt-2">
                                <span class="text-[11px] text-slate-400 font-semibold mr-1">Preset Cepat:</span>
                                <button v-for="rate in ['512k/512k', '1M/1M', '2M/2M', '3M/3M', '5M/5M', '10M/10M']" :key="rate" type="button" @click="form.limit_rate = rate" class="px-2 py-0.5 rounded-lg text-xs font-mono font-bold bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-600 border border-slate-200 transition-colors">
                                    {{ rate }}
                                </button>
                            </div>
                        </div>

                        <!-- SECTION 5: SHARED USERS (CERDAS & KETAT) -->
                        <div v-if="!form.is_customer_free" class="p-4 bg-slate-50/80 rounded-2xl border border-slate-200 space-y-2">
                            <label class="block text-xs font-bold text-slate-700">
                                Shared Users (Batas Perangkat Simultan) <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center gap-3">
                                <div class="w-32">
                                    <input v-model.number="form.shared_users" type="number" min="1" max="20" class="w-full border-slate-300 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-extrabold text-slate-900 py-2 px-3 text-center" required>
                                </div>
                                <span class="text-xs text-slate-500 font-medium">Perangkat bersamaan</span>
                            </div>

                            <!-- Indicator Badge & Explanation -->
                            <div v-if="form.shared_users == 1" class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-900 space-y-1">
                                <div class="font-bold flex items-center gap-1.5 text-amber-800">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    Proteksi Ketat 1 Perangkat Aktif (Strict Port-Limit & Simultaneous-Use)
                                </div>
                                <p class="text-[11px] text-amber-700">
                                    1 kode voucher ini <strong>hanya dapat digunakan oleh 1 HP/Laptop dalam satu waktu</strong>. Jika perangkat kedua mencoba login saat voucher sedang aktif, router MikroTik dan RADIUS akan <strong>langsung menolak login</strong>.
                                </p>
                            </div>
                            <div v-else class="p-3 bg-blue-50 rounded-xl border border-blue-200 text-xs text-blue-900 space-y-1">
                                <div class="font-bold flex items-center gap-1.5 text-blue-800">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    Multi-Device Login (Maksimal {{ form.shared_users }} Perangkat Simultan)
                                </div>
                                <p class="text-[11px] text-blue-700">
                                    Maksimal <strong>{{ form.shared_users }} perangkat</strong> dapat login bersamaan menggunakan kode voucher yang sama. Perangkat ke-{{ form.shared_users + 1 }} dan seterusnya akan ditolak otomatis.
                                </p>
                            </div>
                            <p v-if="form.errors.shared_users" class="text-xs text-red-500 mt-1">{{ form.errors.shared_users }}</p>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/70 flex justify-between items-center gap-3">
                    <button type="button" @click="closeModal" class="px-4 py-2 border border-slate-300 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-100 transition-colors">
                        Batal
                    </button>
                    <button type="button" @click="triggerSubmit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-sm transition-all flex items-center gap-2" :disabled="form.processing || isFeeExceeded">
                        <svg v-if="form.processing" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span>{{ form.processing ? 'Menyimpan & Sinkron RADIUS...' : 'Simpan Profil' }}</span>
                    </button>
                </div>
            </div>
        </div>

    </AppLayout>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';
import LimitRateCalculator from '@/Components/LimitRateCalculator.vue';

const props = defineProps({
    profiles: Array,
});

const formatPrice = (price) => {
    return parseFloat(price || 0).toLocaleString('id-ID');
};

const durationOptions = [
    { value: '30m', label: '30 Menit' },
    { value: '1h', label: '1 Jam' },
    { value: '2h', label: '2 Jam' },
    { value: '3h', label: '3 Jam' },
    { value: '6h', label: '6 Jam' },
    { value: '12h', label: '12 Jam' },
    { value: '1d', label: '1 Hari (24 Jam)' },
    { value: '2d', label: '2 Hari' },
    { value: '3d', label: '3 Hari' },
    { value: '7d', label: '7 Hari (1 Minggu)' },
    { value: '15d', label: '15 Hari' },
    { value: '30d', label: '30 Hari (1 Bulan)' },
];

const customDuration = ref(false);
const durationChoice = computed({
    get: () => {
        if (customDuration.value) return '__custom';
        if (!form.duration) return '';
        return durationOptions.some(d => d.value === form.duration) ? form.duration : '__custom';
    },
    set: (val) => {
        if (val === '__custom') {
            customDuration.value = true;
            if (durationOptions.some(d => d.value === form.duration)) form.duration = '';
        } else {
            customDuration.value = false;
            form.duration = val;
        }
    },
});

const parsedDurationHuman = computed(() => {
    if (form.is_customer_free) return 'Mengikuti status aktif pelanggan';
    if (!form.duration) return '-';
    const raw = String(form.duration).toLowerCase().trim();
    const match = raw.match(/^(\d+)\s*([wdhms])$/);
    if (!match) return `${raw}`;
    const val = parseInt(match[1]);
    const unit = match[2];
    const map = {
        'm': `${val} Menit (${val * 60} Detik)`,
        'h': `${val} Jam (${(val * 3600).toLocaleString('id-ID')} Detik)`,
        'd': `${val} Hari (${(val * 86400).toLocaleString('id-ID')} Detik)`,
        'w': `${val} Minggu (${(val * 604800).toLocaleString('id-ID')} Detik)`,
        's': `${val} Detik`,
    };
    return map[unit] || raw;
});

const showModal = ref(false);
const editMode = ref(false);
const editId = ref(null);
const modalBodyRef = ref(null);

const form = useForm({
    name: '',
    price: 0,
    fee_admin: 0,
    fee_reseller: 0,
    fee_partner: 0,
    duration: '',
    limit_rate: '',
    shared_users: 1,
    is_customer_free: false,
    description: '',
});

const resellerCost = computed(() => {
    if (form.is_customer_free) return 0;
    const price = parseFloat(form.price || 0);
    const fee = parseFloat(form.fee_reseller || 0);
    return Math.max(0, price - fee);
});

const isFeeExceeded = computed(() => {
    if (form.is_customer_free) return false;
    const price = parseFloat(form.price || 0);
    const fee = parseFloat(form.fee_reseller || 0);
    return fee > price;
});

const onToggleCustomerFree = () => {
    if (form.is_customer_free) {
        form.price = 0;
        form.duration = 'Mengikuti Pelanggan';
        form.shared_users = 1;
        form.fee_reseller = 0;
        form.fee_admin = 0;
        form.fee_partner = 0;
    } else {
        if (form.duration === 'Mengikuti Pelanggan') form.duration = '1d';
    }
};

const openCreateModal = () => {
    editMode.value = false;
    editId.value = null;
    customDuration.value = false;
    form.reset();
    form.shared_users = 1;
    form.price = 0;
    form.fee_reseller = 0;
    showModal.value = true;
    nextTick(() => {
        if (modalBodyRef.value) {
            modalBodyRef.value.scrollTop = 0;
        }
    });
};

const openEditModal = (profile) => {
    editMode.value = true;
    editId.value = profile.id;
    customDuration.value = false;
    form.name = profile.name;
    form.price = parseFloat(profile.price || 0);
    form.fee_admin = parseFloat(profile.fee_admin || 0);
    form.fee_reseller = parseFloat(profile.fee_reseller || 0);
    form.fee_partner = parseFloat(profile.fee_partner || 0);
    form.duration = profile.duration;
    form.limit_rate = profile.limit_rate;
    form.shared_users = profile.shared_users || 1;
    form.is_customer_free = !!profile.is_customer_free;
    form.description = profile.description;
    showModal.value = true;
    nextTick(() => {
        if (modalBodyRef.value) {
            modalBodyRef.value.scrollTop = 0;
        }
    });
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const triggerSubmit = () => {
    if (isFeeExceeded.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Fee Reseller Melebihi Harga Jual',
            text: 'Fee reseller tidak boleh melebihi harga jual voucher.',
        });
        return;
    }
    submitForm();
};

const submitForm = () => {
    if (editMode.value) {
        form.post(route('vouchers.profiles.update.post', editId.value), {
            onSuccess: () => {
                closeModal();
                Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Profil Voucher diperbarui dan disinkronkan ke RADIUS.' });
            }
        });
    } else {
        form.post(route('vouchers.profiles.store'), {
            onSuccess: () => {
                closeModal();
                Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Profil Voucher ditambahkan dan disinkronkan ke RADIUS.' });
            }
        });
    }
};

const deleteProfile = (id) => {
    Swal.fire({
        title: 'Hapus Profil?',
        text: "Anda yakin ingin menghapus profil ini? Template dan konfigurasi RADIUS profil ini akan dibersihkan.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('vouchers.profiles.destroy.post', id), {}, {
                onSuccess: () => {
                    Swal.fire('Terhapus!', 'Profil berhasil dihapus dari sistem.', 'success');
                }
            });
        }
    });
};
</script>
