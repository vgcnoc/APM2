<template>
    <AppLayout title="Detail Pelanggan" :subtitle="customer.customer_code">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Profile Card -->
            <div class="glass-card p-6 animate-fade-in-up">
                <div class="flex flex-col items-center text-center mb-6">
                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-3xl font-bold text-gray-900 mb-4 shadow-xl shadow-blue-500/25">
                        {{ customer.name.charAt(0) }}
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">{{ customer.name }}</h2>
                    <p class="text-sm font-mono text-gray-500">{{ customer.customer_code }}</p>
                    <StatusBadge :status="customer.status" class="mt-2" />
                </div>

                <div class="space-y-3 border-t border-gray-200 pt-4">
                    <InfoRow icon="📞" label="Telepon" :value="customer.phone" />
                    <InfoRow icon="📧" label="Email" :value="customer.email || '-'" />
                    <InfoRow icon="📍" label="Alamat" :value="customer.address" />
                    <InfoRow icon="📦" label="Paket" :value="customer.package?.name || 'Belum pilih'" />
                    <InfoRow icon="📅" label="Registrasi" :value="customer.registration_date" />
                    <InfoRow icon="✅" label="Aktivasi" :value="customer.activation_date || '-'" />
                </div>

                <div class="flex gap-2 mt-6">
                    <Link :href="`/customers/${customer.id}/edit`" class="btn-primary flex-1 justify-center">
                        Edit
                    </Link>
                    <Link href="/customers" class="btn-ghost flex-1 justify-center">
                        Kembali
                    </Link>
                </div>
            </div>

            <!-- Right Column -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Survey & Booking Info -->
                <div class="glass-card p-6 animate-fade-in-up">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">📋 Data Booking & Survey</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="bg-gray-50 rounded-xl p-4 space-y-2">
                            <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Info Lokasi Booking</p>
                            <InfoRow label="Koordinat" :value="customer.latitude && customer.longitude ? `${customer.latitude}, ${customer.longitude}` : '-'" />
                            <InfoRow label="Keterangan / Notes" :value="customer.notes || '-'" />
                            <a v-if="customer.latitude && customer.longitude" :href="`https://www.google.com/maps?q=${customer.latitude},${customer.longitude}`" target="_blank" class="text-xs text-blue-400 hover:underline mt-2 inline-block">Lihat di Google Maps &rarr;</a>
                        </div>
                        
                        <div class="bg-gray-50 rounded-xl p-4 space-y-2">
                            <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Jadwal Survey</p>
                            <template v-if="customer.technician_schedules?.find(s => s.type === 'survey')">
                                <InfoRow label="Teknisi" :value="getAssignedTechnicians('survey')" />
                                <InfoRow label="Tanggal" :value="customer.technician_schedules.find(s => s.type === 'survey').scheduled_date" />
                                <InfoRow label="Waktu" :value="customer.technician_schedules.find(s => s.type === 'survey').scheduled_time" />
                                <InfoRow label="Status">
                                    <StatusBadge :status="customer.technician_schedules.find(s => s.type === 'survey').status" />
                                </InfoRow>
                            </template>
                            <div v-else class="text-center py-4 text-gray-500 text-sm">
                                Belum ada jadwal survey
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-xl p-4 space-y-4">
                        <div class="flex justify-between items-center mb-2 border-b border-gray-200 pb-2">
                            <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Hasil Laporan Survey</p>
                            <StatusBadge v-if="customer.surveys?.length" :status="customer.surveys[0].feasibility" />
                            <span v-else class="text-xs text-gray-500 italic px-2 py-1 rounded-md bg-gray-50 border border-gray-200">Menunggu Laporan</span>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <InfoRow label="Surveyor" :value="customer.surveys?.[0]?.surveyor?.name || '-'" />
                                <InfoRow label="Tanggal Survey" :value="customer.surveys?.[0]?.survey_date || '-'" />
                                <InfoRow label="Catatan" :value="customer.surveys?.[0]?.notes || '-'" />
                            </div>
                            <div class="space-y-2">
                                <InfoRow label="Rekomendasi ODP" :value="customer.surveys?.[0]?.odp?.name || '-'" />
                                <InfoRow label="Jarak Tarikan" :value="customer.surveys?.[0]?.distance_meters ? `${customer.surveys[0].distance_meters} Meter` : '-'" />
                                <InfoRow label="Ketersediaan Port" :value="customer.surveys?.length ? (customer.surveys[0].port_available ? 'Tersedia' : 'Penuh') : '-'" />
                            </div>
                        </div>

                        <div v-if="customer.surveys?.[0]?.photos?.length" class="pt-4 border-t border-gray-200 mt-4">
                            <p class="text-xs text-gray-500 mb-3">Dokumentasi Foto:</p>
                            <div class="flex gap-4 overflow-x-auto pb-2">
                                <div v-for="(photo, idx) in customer.surveys[0].photos" :key="idx" class="shrink-0 group relative">
                                    <img :src="`/storage/${photo.path}`" class="h-32 w-32 object-cover rounded-lg border border-gray-200 cursor-pointer hover:border-blue-500 transition-colors" @click="openImage(`/storage/${photo.path}`, photo.label)" />
                                    <div class="absolute bottom-0 left-0 right-0 bg-black/60 p-1 rounded-b-lg text-[10px] text-center text-gray-900 truncate pointer-events-none">
                                        {{ photo.label }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="customer.status === 'installing' && !customer.ont" class="glass-card p-6 mt-6 animate-fade-in-up border-2 border-emerald-500/20">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <span class="bg-emerald-100 text-emerald-600 p-2 rounded-lg">🚀</span>
                            Form Laporan Selesai Instalasi
                        </h3>
                        
                        <div v-if="customer.technician_schedules?.find(s => s.type === 'installation')" class="bg-indigo-50 p-4 rounded-xl border border-indigo-100 mb-6 space-y-2">
                            <p class="text-xs font-semibold text-indigo-800 uppercase tracking-wider mb-2">Info Penugasan Pasang</p>
                            <InfoRow label="Teknisi" :value="getAssignedTechnicians('installation')" />
                            <InfoRow label="Tanggal Pasang" :value="customer.technician_schedules.find(s => s.type === 'installation').scheduled_date" />
                            <InfoRow label="Waktu Target" :value="customer.technician_schedules.find(s => s.type === 'installation').scheduled_time || '-'" />
                        </div>

                        <form @submit.prevent="submitOnt" class="space-y-6">
                            
                            <!-- STEP 1: Mulai -->
                            <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100">
                                <p class="text-xs font-semibold text-blue-800 uppercase tracking-wider mb-3">1. Jam Mulai Pekerjaan</p>
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="block text-xs font-medium text-gray-500">Jam Mulai</label>
                                        <button type="button" @click="setNow('start_time')" class="text-[10px] bg-blue-600 text-white px-3 py-1 rounded font-bold hover:bg-blue-700 transition-colors shadow-sm">MULAI SEKARANG</button>
                                    </div>
                                    <input v-model="ontForm.start_time" type="time" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required />
                                </div>
                            </div>

                            <!-- STEP 2: ONT & Material -->
                            <div v-if="customer.technician_schedules?.find(s => s.type === 'installation')" class="bg-amber-50 p-4 rounded-xl border border-amber-100">
                                <p class="text-xs font-semibold text-amber-800 uppercase tracking-wider mb-3">2. Kebutuhan ONT & Material</p>
                                
                                <div v-if="hardwareItems.length === 0" class="text-sm text-gray-500 italic p-3 bg-white rounded-lg border border-amber-200">
                                    Tidak ada catatan kebutuhan khusus.
                                </div>
                                
                                <div v-else class="space-y-3">
                                    <div v-for="item in hardwareItems" :key="item.id" class="text-sm text-gray-700 bg-white p-3 rounded-lg border border-amber-200 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                        <div class="flex-1">
                                            <span class="text-xs text-gray-500 font-medium block mb-1">{{ item.type }}</span>
                                            <span class="font-bold text-gray-900">{{ item.name }}</span>
                                        </div>
                                        <button type="button" 
                                            :disabled="!ontForm.start_time" 
                                            @click="toggleInstallItem(item)"
                                            :class="['px-6 py-2 rounded-lg font-bold text-sm transition-all whitespace-nowrap flex items-center gap-2', 
                                                !ontForm.start_time ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 
                                                item.isInstalled ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 shadow-sm border border-emerald-200' : 'bg-amber-500 text-white hover:bg-amber-600 shadow-md hover:shadow-lg']">
                                            <template v-if="item.isInstalled">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Dipasang
                                            </template>
                                            <template v-else>
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                Pasang
                                            </template>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- STEP 3: Laporan Akhir (Muncul setelah Pasang diklik) -->
                            <div v-show="isInstallingHardware" class="space-y-6 animate-fade-in-up">
                                <!-- ODP & Port -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">ODP Terdekat (Auto Dropdown)</label>
                                        <select v-model="ontForm.odp_id" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" :required="isInstallingHardware">
                                            <option value="">-- Pilih ODP --</option>
                                            <option v-for="odp in availableOdps" :key="odp.id" :value="odp.id">
                                                {{ odp.name }} (Sisa {{ odp.total_ports - odp.used_ports }} port)
                                            </option>
                                        </select>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Port ODP</label>
                                            <select v-model="ontForm.port_number" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" :disabled="!selectedOdp" :required="isInstallingHardware">
                                                <option value="">-- Pilih Port --</option>
                                                <template v-if="selectedOdp">
                                                    <option v-for="i in selectedOdp.total_ports" :key="i" :value="i">Port {{ i }}</option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Redaman (dBm)</label>
                                            <input v-model="ontForm.rx_power" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="-15.50" :required="isInstallingHardware" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Dokumentasi Foto -->
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-4">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Dokumentasi (Foto Laporan)</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-1">
                                        <label class="block text-xs font-medium text-gray-500">1. Foto ODP / Port (Wajib)</label>
                                        <div class="flex items-start gap-3">
                                            <input type="file" @change="e => handleFileUpload('photo_odp', e)" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" :required="isInstallingHardware && !ontForm.photo_odp" />
                                            <div v-if="previewUrls.photo_odp" class="flex flex-col gap-1 shrink-0">
                                                <img :src="previewUrls.photo_odp" class="h-10 w-10 object-cover rounded border border-gray-200 cursor-pointer hover:opacity-80 transition-opacity" @click="openImage(previewUrls.photo_odp, '1. Foto ODP / Port')" />
                                                <a :href="previewUrls.photo_odp" download="Foto_ODP.jpg" class="text-[10px] text-blue-600 hover:underline text-center">Download</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-xs font-medium text-gray-500">2. Foto Instalasi di Rumah (Wajib)</label>
                                        <div class="flex items-start gap-3">
                                            <input type="file" @change="e => handleFileUpload('photo_installation', e)" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" :required="isInstallingHardware && !ontForm.photo_installation" />
                                            <div v-if="previewUrls.photo_installation" class="flex flex-col gap-1 shrink-0">
                                                <img :src="previewUrls.photo_installation" class="h-10 w-10 object-cover rounded border border-gray-200 cursor-pointer hover:opacity-80 transition-opacity" @click="openImage(previewUrls.photo_installation, '2. Foto Instalasi di Rumah')" />
                                                <a :href="previewUrls.photo_installation" download="Foto_Instalasi.jpg" class="text-[10px] text-blue-600 hover:underline text-center">Download</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-xs font-medium text-gray-500">3. Foto Posisi ONT (Wajib)</label>
                                        <div class="flex items-start gap-3">
                                            <input type="file" @change="e => handleFileUpload('photo_ont', e)" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" :required="isInstallingHardware && !ontForm.photo_ont" />
                                            <div v-if="previewUrls.photo_ont" class="flex flex-col gap-1 shrink-0">
                                                <img :src="previewUrls.photo_ont" class="h-10 w-10 object-cover rounded border border-gray-200 cursor-pointer hover:opacity-80 transition-opacity" @click="openImage(previewUrls.photo_ont, '3. Foto Posisi ONT')" />
                                                <a :href="previewUrls.photo_ont" download="Foto_ONT.jpg" class="text-[10px] text-blue-600 hover:underline text-center">Download</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-xs font-medium text-gray-500">4. Foto Selfie Pelanggan (Wajib)</label>
                                        <div class="flex items-start gap-3">
                                            <input type="file" @change="e => handleFileUpload('photo_customer', e)" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" :required="isInstallingHardware && !ontForm.photo_customer" />
                                            <div v-if="previewUrls.photo_customer" class="flex flex-col gap-1 shrink-0">
                                                <img :src="previewUrls.photo_customer" class="h-10 w-10 object-cover rounded border border-gray-200 cursor-pointer hover:opacity-80 transition-opacity" @click="openImage(previewUrls.photo_customer, '4. Foto Selfie Pelanggan')" />
                                                <a :href="previewUrls.photo_customer" download="Foto_Selfie.jpg" class="text-[10px] text-blue-600 hover:underline text-center">Download</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-xs font-medium text-gray-500">5. Foto Redaman (Wajib)</label>
                                        <div class="flex items-start gap-3">
                                            <input type="file" @change="e => handleFileUpload('photo_redaman', e)" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" :required="isInstallingHardware && !ontForm.photo_redaman" />
                                            <div v-if="previewUrls.photo_redaman" class="flex flex-col gap-1 shrink-0">
                                                <img :src="previewUrls.photo_redaman" class="h-10 w-10 object-cover rounded border border-gray-200 cursor-pointer hover:opacity-80 transition-opacity" @click="openImage(previewUrls.photo_redaman, '5. Foto Redaman')" />
                                                <a :href="previewUrls.photo_redaman" download="Foto_Redaman.jpg" class="text-[10px] text-blue-600 hover:underline text-center">Download</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Waktu Selesai -->
                            <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100 mt-4">
                                <p class="text-xs font-semibold text-blue-800 uppercase tracking-wider mb-3">3. Jam Selesai Pekerjaan</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                                    <div>
                                        <div class="flex justify-between items-center mb-1">
                                            <label class="block text-xs font-medium text-gray-500">Jam Selesai</label>
                                            <button type="button" @click="setNow('end_time')" class="text-[10px] bg-emerald-600 text-white px-3 py-1 rounded font-bold hover:bg-emerald-700 transition-colors shadow-sm">SELESAI SEKARANG</button>
                                        </div>
                                        <input v-model="ontForm.end_time" type="time" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" :required="isInstallingHardware" />
                                    </div>
                                    <div class="bg-white px-4 py-2 rounded-lg border border-gray-200 shadow-sm flex flex-col justify-center h-[38px]">
                                        <div class="flex items-center justify-between">
                                            <span class="text-[10px] text-gray-400 uppercase font-bold">Total Durasi:</span>
                                            <span class="text-sm font-bold text-gray-800">{{ durationText }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- END STEP 3 -->
                            </div>

                            <div v-show="isInstallingHardware" class="flex justify-end pt-4 border-t border-gray-200 mt-6">
                                <button type="submit" :disabled="ontForm.processing" class="btn-primary w-full md:w-auto text-sm py-2 px-6 shadow-md hover:shadow-lg">
                                    {{ ontForm.processing ? 'Menyimpan Laporan...' : 'Kirim Laporan & Selesaikan Instalasi' }}
                                </button>
                            </div>

                        </form>
                    </div>

                    <!-- Audit Data Pemasangan -->
                    <div v-if="customer.status === 'installing' && customer.ont" class="glass-card p-6 mt-6 animate-fade-in-up border-2 border-amber-500/20">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <span class="bg-amber-100 text-amber-600 p-2 rounded-lg">🛡️</span>
                            Audit Data Pemasangan
                        </h3>
                        
                        <div v-if="customer.technician_schedules?.find(s => s.type === 'installation')" class="bg-indigo-50 p-4 rounded-xl border border-indigo-100 mb-6 space-y-2">
                            <p class="text-xs font-semibold text-indigo-800 uppercase tracking-wider mb-2">Info Penugasan Pasang</p>
                            <InfoRow label="Teknisi" :value="getAssignedTechnicians('installation')" />
                            <InfoRow label="Tanggal Pasang" :value="customer.technician_schedules.find(s => s.type === 'installation').scheduled_date" />
                        </div>
                        
                        <div v-if="hardwareItems.length > 0" class="bg-amber-50 p-4 rounded-xl border border-amber-100 mb-6">
                            <p class="text-xs font-semibold text-amber-800 uppercase tracking-wider mb-3">Kebutuhan Material (Telah Dipasang)</p>
                            <div class="space-y-2">
                                <div v-for="item in hardwareItems" :key="item.id" class="text-sm text-gray-700 bg-white p-3 rounded-lg border border-amber-200 shadow-sm flex items-center justify-between">
                                    <div>
                                        <span class="text-xs text-gray-500 font-medium block mb-1">{{ item.type }}</span>
                                        <span class="font-bold text-gray-900">{{ item.name }}</span>
                                    </div>
                                    <div class="text-emerald-600 font-bold flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Dipasang
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div class="space-y-2">
                                <InfoRow label="Waktu Mulai" :value="customer.ont.start_time || '-'" />
                                <InfoRow label="Waktu Selesai" :value="customer.ont.end_time || '-'" />
                            </div>
                            <div class="space-y-2">
                                <InfoRow label="Port ODP" :value="`Port ${customer.ont.port_number}`" />
                                <InfoRow label="Serial Number (Auto)" :value="customer.ont.serial_number" />
                            </div>
                        </div>
                        
                        <div class="pt-4 border-t border-gray-200 mt-4">
                            <p class="text-xs text-gray-500 mb-3 uppercase font-semibold">Hasil Dokumentasi Lapangan:</p>
                            <div class="flex gap-4 overflow-x-auto pb-2">
                                <div v-if="customer.ont.photo_odp" class="shrink-0 group relative">
                                    <img :src="`/storage/${customer.ont.photo_odp}`" class="h-32 w-32 object-cover rounded-lg border border-gray-200 cursor-pointer hover:border-amber-500 transition-colors" @click="openImage(`/storage/${customer.ont.photo_odp}`, 'Foto ODP / Port')" />
                                    <div class="absolute bottom-0 left-0 right-0 bg-black/60 p-1 rounded-b-lg text-[10px] text-center text-white truncate pointer-events-none">Foto ODP</div>
                                </div>
                                <div v-if="customer.ont.photo_installation" class="shrink-0 group relative">
                                    <img :src="`/storage/${customer.ont.photo_installation}`" class="h-32 w-32 object-cover rounded-lg border border-gray-200 cursor-pointer hover:border-amber-500 transition-colors" @click="openImage(`/storage/${customer.ont.photo_installation}`, 'Foto Instalasi di Rumah')" />
                                    <div class="absolute bottom-0 left-0 right-0 bg-black/60 p-1 rounded-b-lg text-[10px] text-center text-white truncate pointer-events-none">Foto Instalasi</div>
                                </div>
                                <div v-if="customer.ont.photo_ont" class="shrink-0 group relative">
                                    <img :src="`/storage/${customer.ont.photo_ont}`" class="h-32 w-32 object-cover rounded-lg border border-gray-200 cursor-pointer hover:border-amber-500 transition-colors" @click="openImage(`/storage/${customer.ont.photo_ont}`, 'Foto Posisi ONT')" />
                                    <div class="absolute bottom-0 left-0 right-0 bg-black/60 p-1 rounded-b-lg text-[10px] text-center text-white truncate pointer-events-none">Foto ONT</div>
                                </div>
                                <div v-if="customer.ont.photo_customer" class="shrink-0 group relative">
                                    <img :src="`/storage/${customer.ont.photo_customer}`" class="h-32 w-32 object-cover rounded-lg border border-gray-200 cursor-pointer hover:border-amber-500 transition-colors" @click="openImage(`/storage/${customer.ont.photo_customer}`, 'Foto Selfie Pelanggan')" />
                                    <div class="absolute bottom-0 left-0 right-0 bg-black/60 p-1 rounded-b-lg text-[10px] text-center text-white truncate pointer-events-none">Foto Selfie</div>
                                </div>
                                <div v-if="customer.ont.photo_redaman" class="shrink-0 group relative">
                                    <img :src="`/storage/${customer.ont.photo_redaman}`" class="h-32 w-32 object-cover rounded-lg border border-gray-200 cursor-pointer hover:border-amber-500 transition-colors" @click="openImage(`/storage/${customer.ont.photo_redaman}`, 'Foto Redaman')" />
                                    <div class="absolute bottom-0 left-0 right-0 bg-black/60 p-1 rounded-b-lg text-[10px] text-center text-white truncate pointer-events-none">Foto Redaman</div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4 border-t border-gray-200 mt-6">
                            <button @click="showActivationModal = true" class="btn-primary w-full md:w-auto text-lg py-3 shadow-lg hover:shadow-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 border-0 text-white">
                                Selesaikan Audit & Aktivasi Pelanggan
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Activation Modal -->
        <div v-if="showActivationModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-xl w-full max-w-lg overflow-hidden animate-fade-in-up">
                <div class="p-5 border-b border-gray-200 flex items-center justify-between bg-gradient-to-r from-amber-50 to-amber-100/50">
                    <h3 class="text-lg font-bold text-amber-900 flex items-center gap-2">
                        <span class="text-2xl">⚡</span> Aktivasi Pelanggan
                    </h3>
                    <button @click="showActivationModal = false" class="text-gray-500 hover:text-gray-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitActivation">
                    <div class="p-6 space-y-5">
                        <div class="bg-amber-50 text-amber-800 p-4 rounded-xl text-sm mb-4 border border-amber-200 shadow-sm flex gap-3">
                            <svg class="w-6 h-6 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p>Pastikan semua data instalasi dan hasil audit foto sudah benar sebelum mengaktifkan pelanggan ini.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Aktivasi</label>
                            <input v-model="activationForm.activation_date" type="date" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 shadow-sm transition-all" required />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PPPoE Username</label>
                                <input v-model="activationForm.pppoe_user" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 shadow-sm transition-all" placeholder="user@isp" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PPPoE Password</label>
                                <input v-model="activationForm.pppoe_password" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 shadow-sm transition-all" placeholder="***" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">VLAN Mode</label>
                                <select v-model="activationForm.vlan_mode" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 shadow-sm transition-all">
                                    <option value="">-- Pilih --</option>
                                    <option value="Route">Route</option>
                                    <option value="Bridge">Bridge</option>
                                    <option value="VLAN">VLAN (Tagged)</option>
                                    <option value="Untagged">Untagged</option>
                                </select>
                            </div>
                            <div v-if="activationForm.vlan_mode === 'VLAN'">
                                <label class="block text-sm font-medium text-gray-700 mb-1">No VLAN ID</label>
                                <input v-model="activationForm.vlan_id" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 shadow-sm transition-all" placeholder="Misal: 100" />
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan (Opsional)</label>
                            <textarea v-model="activationForm.notes" rows="2" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 shadow-sm transition-all" placeholder="Catatan internal setelah aktivasi..."></textarea>
                        </div>
                    </div>
                    <div class="p-5 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 rounded-b-2xl">
                        <button type="button" @click="showActivationModal = false" class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors shadow-sm">Batal</button>
                        <button type="submit" :disabled="activationForm.processing" class="px-6 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white rounded-lg text-sm font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                            <svg v-if="activationForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            {{ activationForm.processing ? 'Memproses...' : 'Aktivasi Sekarang' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Image Preview Modal -->
        <Teleport to="body">
            <div v-if="previewImage" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/90 backdrop-blur-sm cursor-pointer" @click="previewImage = null"></div>
                <div class="relative max-w-4xl w-full h-full flex flex-col items-center justify-center animate-fade-in-up">
                    <button @click="previewImage = null" class="absolute top-4 right-4 text-white hover:text-red-500 bg-white/20 hover:bg-white/30 p-2 rounded-full z-10 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                    <img :src="previewImage.src" class="max-h-[85vh] max-w-full object-contain rounded-lg shadow-2xl" />
                    <p class="mt-4 text-white font-medium text-lg">{{ previewImage.label }}</p>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, h, computed, watch } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({ customer: Object, availableOdps: Array });

const getAssignedTechnicians = (type) => {
    if (!props.customer?.technician_schedules) return '-';
    const schedules = props.customer.technician_schedules.filter(s => s.type === type);
    if (schedules.length === 0) return '-';
    return schedules.map(s => s.technician?.name).filter(Boolean).join(', ') || '-';
};

const previewImage = ref(null);

const hardwareItems = ref([]);

watch(() => props.customer, () => {
    const schedule = props.customer?.technician_schedules?.find(s => s.type === 'installation');
    if (!schedule || !schedule.notes) {
        hardwareItems.value = [];
        return;
    }
    
    const lines = schedule.notes.split('\n');
    const items = [];
    let idCounter = 0;
    
    lines.forEach(line => {
        if (line.startsWith('ONT: ')) {
            const onts = line.replace('ONT: ', '').split(', ');
            onts.forEach(ont => {
                if(ont.trim()) items.push({ id: idCounter++, type: 'ONT', name: ont.trim(), isInstalled: false });
            });
        } else if (line.startsWith('Material: ')) {
            const mats = line.replace('Material: ', '').split(', ');
            mats.forEach(mat => {
                if(mat.trim()) items.push({ id: idCounter++, type: 'Material', name: mat.trim(), isInstalled: false });
            });
        }
    });
    
    hardwareItems.value = items;
}, { immediate: true });

function toggleInstallItem(item) {
    item.isInstalled = !item.isInstalled;
}

const isInstallingHardware = computed(() => {
    // Show hardware form if there are no items, or if at least one item is marked as installed.
    if (hardwareItems.value.length === 0) return true;
    return hardwareItems.value.some(item => item.isInstalled);
});

function openImage(src, label) {
    previewImage.value = { src, label };
}

// Functional component for InfoRow so it works without template compiler
const InfoRow = (props, context) => {
    return h('div', { class: 'flex items-start justify-between gap-2' }, [
        h('span', { class: 'text-xs text-gray-500 shrink-0' }, (props.icon ? props.icon + ' ' : '') + props.label),
        h('span', { class: ['text-sm text-right', props.mono ? 'font-mono text-cyan-400' : 'text-gray-600'] }, context.slots.default ? context.slots.default() : props.value)
    ]);
};
InfoRow.props = ['icon', 'label', 'value', 'mono'];

const previewUrls = ref({});

function handleFileUpload(field, e) {
    const file = e.target.files[0];
    ontForm[field] = file;
    if (file) {
        if (previewUrls.value[field]) URL.revokeObjectURL(previewUrls.value[field]);
        previewUrls.value[field] = URL.createObjectURL(file);
    } else {
        previewUrls.value[field] = null;
    }
}

const ontForm = useForm({
    odp_id: '',
    port_number: '',
    rx_power: '',
    start_time: '',
    end_time: '',
    photo_odp: null,
    photo_installation: null,
    photo_ont: null,
    photo_customer: null,
    photo_redaman: null,
});

const selectedOdp = computed(() => props.availableOdps?.find(o => o.id === ontForm.odp_id));

const durationText = computed(() => {
    if (!ontForm.start_time || !ontForm.end_time) return '-';
    const start = new Date(`2000-01-01T${ontForm.start_time}`);
    const end = new Date(`2000-01-01T${ontForm.end_time}`);
    if (end < start) return 'Waktu tidak valid';
    const diffMs = end - start;
    const diffMins = Math.round(diffMs / 60000);
    const hours = Math.floor(diffMins / 60);
    const mins = diffMins % 60;
    if (hours > 0) return `${hours} jam ${mins} menit`;
    return `${mins} menit`;
});

function setNow(field) {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    ontForm[field] = `${hours}:${minutes}`;
}

function submitOnt() {
    ontForm.post(`/customers/${props.customer.id}/assign-ont`, {
        preserveScroll: true,
        onSuccess: () => {
            // success handles redirect automatically based on backend
        }
    });
}

const showActivationModal = ref(false);
const activationForm = useForm({
    activation_date: new Date().toISOString().split('T')[0],
    pppoe_user: props.customer?.ont?.pppoe_user || '',
    pppoe_password: props.customer?.ont?.pppoe_password || '',
    vlan_mode: props.customer?.ont?.vlan_mode || '',
    vlan_id: props.customer?.ont?.vlan_id || '',
    notes: ''
});

function submitActivation() {
    activationForm.post(`/customers/${props.customer.id}/activate`, {
        preserveScroll: true,
        onSuccess: () => {
            showActivationModal.value = false;
        }
    });
}
</script>
