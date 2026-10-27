<template>
    <AppLayout :title="source === 'instalasi' ? 'Detail Instalasi' : 'Detail Pelanggan'" :subtitle="customer.customer_code">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left Column -->
            <div class="space-y-6">
                <!-- Profile Card -->
                <div class="glass-card overflow-hidden animate-fade-in-up">
                    <div class="h-28 bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-700 relative">
                        <div class="absolute -bottom-12 left-1/2 -translate-x-1/2">
                            <div class="w-24 h-24 rounded-2xl bg-white p-1 shadow-xl shadow-indigo-500/20">
                                <div class="w-full h-full rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-4xl font-bold text-white shadow-inner">
                                    {{ customer.name.charAt(0).toUpperCase() }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="pt-16 pb-6 px-6 flex flex-col items-center text-center">
                        <h2 class="text-xl font-bold text-gray-900 mb-1">{{ customer.name }}</h2>
                        <p class="text-xs font-mono text-indigo-600 font-bold bg-indigo-50 px-3 py-1 rounded-full mb-3">{{ customer.customer_code }}</p>
                        <StatusBadge :status="customer.status" />
                    </div>

                    <div class="px-6 pb-6 space-y-4">
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-3">
                            <InfoRow icon="📞" label="Telepon" :value="customer.phone" />
                            <InfoRow icon="📧" label="Email" :value="customer.email || '-'" />
                            <InfoRow icon="📍" label="Alamat" :value="customer.address" />
                            <InfoRow icon="📦" label="Paket" :value="customer.service_status === 'free_home' ? 'Free Home (Voucher Rumahan)' : (customer.package?.name || 'Belum pilih')" />
                            <InfoRow icon="💳" label="Layanan" :value="customer.service_status === 'free_home' ? 'Free Home (Rp 0)' : (customer.service_status === 'gratis' ? 'Gratis (Rp 0)' : 'Berbayar')" />
                            <div class="pt-3 mt-3 border-t border-slate-200 space-y-3">
                                <InfoRow icon="📅" label="Registrasi" :value="formatDate(customer.registration_date)" />
                                <InfoRow icon="✅" label="Aktivasi" :value="formatDate(customer.activation_date) || '-'" />
                            </div>
                        </div>

                        <div v-if="canEditProfile" class="flex flex-col gap-2 pt-2">
                            <Link :href="`/customers/${customer.id}/edit`" class="btn-primary w-full justify-center py-2.5 text-sm shadow-md hover:shadow-lg transition-all">
                                Edit Profil
                            </Link>
                            <button v-if="!customer.user_id" @click="openAccountModal" class="btn-primary w-full justify-center py-2.5 text-sm shadow-md hover:shadow-lg transition-all bg-indigo-600 hover:bg-indigo-700 text-white">
                                + Buat Akun Client Area
                            </button>
                            <div v-else class="flex flex-col gap-2">
                                <div class="text-center py-2 px-3 bg-green-50 text-green-700 border border-green-200 rounded-lg text-xs font-bold flex flex-col items-center justify-center gap-1">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Memiliki Akun Login
                                    </div>
                                    <span class="text-[10px] font-normal text-green-600 block mt-1 break-all" v-if="customer.user">Email Login: {{ customer.user.email }}</span>
                                </div>
                                <button @click="openResetPasswordModal()" class="text-xs w-full py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-semibold shadow-sm transition-all">
                                    🔑 Ubah Password Akun
                                </button>
                            </div>

                            <!-- Stop Permanen Action -->
                            <div v-if="customer.status !== 'terminated'" class="pt-1">
                                <button @click="showTerminateModal = true" class="w-full py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    Stop Permanen Pelanggan
                                </button>
                            </div>
                            <div v-else class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-center space-y-1.5 mt-1">
                                <span class="text-xs font-bold text-rose-700 block">Status: Stop Permanen</span>
                                <span class="text-[11px] text-rose-600 block">Alasan: {{ customer.cancel_reason || '-' }}</span>
                                <div class="flex items-center justify-center gap-2 pt-1">
                                    <a :href="`/customers/${customer.id}/print-termination`" target="_blank" class="px-2.5 py-1 bg-white border border-rose-300 text-rose-700 hover:bg-rose-100 text-xs font-semibold rounded-lg shadow-sm">
                                        Cetak BAB
                                    </a>
                                    <a href="/customers/terminated" class="px-2.5 py-1 bg-rose-600 text-white hover:bg-rose-700 text-xs font-semibold rounded-lg shadow-sm">
                                        Menu Stop Permanen &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- Network Path -->
            <div v-if="customer.ont" class="glass-card overflow-hidden mt-6 animate-fade-in-up border border-indigo-100">
                <div class="bg-gradient-to-r from-indigo-50 to-blue-50/30 p-5 border-b border-indigo-100 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-indigo-900 flex items-center gap-2 uppercase tracking-wider">
                        <span class="bg-indigo-100 text-indigo-600 p-1.5 rounded-lg shadow-sm">🔗</span> Network Path
                    </h3>
                    <Link :href="`/network-topology?area_id=${customer.area_id}`" class="text-[10px] font-bold text-indigo-700 bg-white border border-indigo-200 hover:border-indigo-400 hover:text-indigo-900 px-3 py-1.5 rounded-lg shadow-sm transition-all">
                        LIHAT MAP
                    </Link>
                </div>
                <div class="p-6 bg-slate-50/50">
                    <div class="flex flex-col gap-3 relative ml-2">
                        <div class="absolute left-[7px] top-4 bottom-4 w-[2px] bg-gradient-to-b from-slate-200 via-indigo-200 to-blue-300 rounded-full"></div>
                        
                        <div class="flex items-center gap-4 relative z-10 group">
                            <div class="w-4 h-4 rounded-full border-[3px] border-white shadow-md flex items-center justify-center bg-slate-400 group-hover:scale-125 transition-transform"></div>
                            <div class="flex flex-col bg-white px-3 py-2 rounded-xl border border-slate-200 shadow-sm w-full group-hover:border-slate-300 transition-colors">
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">SERVER / AREA</span>
                                <span class="text-xs font-bold text-slate-700">{{ customer.area || 'Unknown Area' }}</span>
                            </div>
                        </div>
                        
                        <div v-if="customer.ont?.odp?.odc?.olt" class="flex items-center gap-4 relative z-10 group">
                            <div class="w-4 h-4 rounded-full border-[3px] border-white shadow-md flex items-center justify-center bg-indigo-500 group-hover:scale-125 transition-transform"></div>
                            <div class="flex flex-col bg-white px-3 py-2 rounded-xl border border-indigo-100 shadow-sm w-full group-hover:border-indigo-300 transition-colors">
                                <span class="text-[9px] font-bold text-indigo-400 uppercase tracking-widest">OLT</span>
                                <span class="text-xs font-bold text-indigo-900">{{ customer.ont?.odp.odc.olt.name }}</span>
                            </div>
                        </div>
                        
                        <div v-if="customer.ont?.odp?.odc?.pon" class="flex items-center gap-4 relative z-10 group">
                            <div class="w-4 h-4 rounded-full border-[3px] border-white shadow-md flex items-center justify-center bg-purple-500 group-hover:scale-125 transition-transform"></div>
                            <div class="flex flex-col bg-white px-3 py-2 rounded-xl border border-purple-100 shadow-sm w-full group-hover:border-purple-300 transition-colors">
                                <span class="text-[9px] font-bold text-purple-400 uppercase tracking-widest">PON PORT {{ customer.ont?.odp.odc.pon.port_number }}</span>
                                <span class="text-xs font-bold text-purple-900">{{ customer.ont?.odp.odc.pon.name || `PON ${customer.ont?.odp.odc.pon.port_number}` }}</span>
                            </div>
                        </div>
                        
                        <div v-if="customer.ont?.odp?.odc" class="flex items-center gap-4 relative z-10 group">
                            <div class="w-4 h-4 rounded-full border-[3px] border-white shadow-md flex items-center justify-center bg-orange-500 group-hover:scale-125 transition-transform"></div>
                            <div class="flex flex-col bg-white px-3 py-2 rounded-xl border border-orange-100 shadow-sm w-full group-hover:border-orange-300 transition-colors">
                                <span class="text-[9px] font-bold text-orange-400 uppercase tracking-widest">ODC</span>
                                <span class="text-xs font-bold text-orange-900">{{ customer.ont?.odp.odc.name }}</span>
                            </div>
                        </div>
                        
                        <div v-if="customer.ont?.odp" class="flex items-center gap-4 relative z-10 group">
                            <div class="w-4 h-4 rounded-full border-[3px] border-white shadow-md flex items-center justify-center bg-sky-500 group-hover:scale-125 transition-transform"></div>
                            <div class="flex flex-col bg-white px-3 py-2 rounded-xl border border-sky-100 shadow-sm w-full group-hover:border-sky-300 transition-colors">
                                <span class="text-[9px] font-bold text-sky-400 uppercase tracking-widest">ODP</span>
                                <span class="text-xs font-bold text-sky-900">{{ customer.ont?.odp.name }}</span>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-4 relative z-10 group">
                            <div class="w-5 h-5 rounded-full border-[3px] border-white shadow-md flex items-center justify-center bg-blue-500 -ml-0.5 group-hover:scale-110 transition-transform"></div>
                            <div class="flex flex-col bg-blue-50 px-3 py-2 rounded-xl border border-blue-200 shadow-sm w-full group-hover:border-blue-400 transition-colors">
                                <span class="text-[9px] font-bold text-blue-500 uppercase tracking-widest">PORT ONT</span>
                                <span class="text-xs font-bold text-blue-900">Port {{ customer.ont?.port_number }} ({{ customer.ont?.serial_number }})</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            </div>

        <!-- Right Column -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Tabs Navigation -->
                <div v-if="source !== 'instalasi' && !isLaporanPasang" class="flex flex-wrap gap-1 bg-white p-1 rounded-xl shadow-sm border border-gray-100">
                    <button @click="activeTab = 'booking'" :class="activeTab === 'booking' ? 'bg-blue-50 text-blue-600 shadow-sm ring-1 ring-blue-100' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'" class="flex-1 py-2 px-3 text-xs sm:text-sm font-bold rounded-lg transition-all flex items-center justify-center gap-2">
                        📋 Booking & Survey
                    </button>
                    <button v-if="customer.status !== 'booking' && customer.status !== 'survey'" @click="activeTab = 'installation'" :class="activeTab === 'installation' ? 'bg-emerald-50 text-emerald-600 shadow-sm ring-1 ring-emerald-100' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'" class="flex-1 py-2 px-3 text-xs sm:text-sm font-bold rounded-lg transition-all flex items-center justify-center gap-2">
                        🚀 Instalasi
                    </button>
                    <button v-if="isAudit || customer.status === 'active'" @click="activeTab = 'audit'" :class="activeTab === 'audit' ? 'bg-purple-50 text-purple-600 shadow-sm ring-1 ring-purple-100' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'" class="flex-1 py-2 px-3 text-xs sm:text-sm font-bold rounded-lg transition-all flex items-center justify-center gap-2">
                        ✅ {{ (customer.is_audited || customer.status === 'active') && source !== 'instalasi' ? 'Aktivasi Layanan' : 'Audit Instalasi' }}
                    </button>
                </div>
                
                <!-- Survey & Booking Info Section -->
                <div v-show="source !== 'instalasi' && activeTab === 'booking'" class="glass-card p-6 animate-fade-in-up">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">📋 Data Booking & Survey</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <!-- 1. INFO LOKASI BOOKING -->
                        <div class="bg-gray-50 rounded-xl p-4 space-y-2">
                            <div class="flex items-center justify-between pb-1 border-b border-gray-200/70 mb-2">
                                <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Info Lokasi Booking</p>
                                <button type="button" @click="openEditBookingModal" class="text-xs font-semibold px-2.5 py-1 rounded-md bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors flex items-center gap-1 shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    Edit Booking
                                </button>
                            </div>
                            <InfoRow label="Tgl Booking" :value="formatDate(customer.registration_date)" />
                            <InfoRow label="Sales / Referal" :value="customer.sales?.name || (customer.sales_id ? 'Sales #' + customer.sales_id : '-')" />
                            <div v-if="customer.latitude || customer.longitude" class="py-1">
                                <span class="text-xs text-gray-500 block mb-1">Koordinat:</span>
                                <div class="flex flex-wrap items-center gap-1.5 font-mono text-xs">
                                    <span class="bg-white text-gray-800 px-2 py-0.5 rounded border border-gray-200">Lat: {{ customer.latitude || '-' }}</span>
                                    <span class="bg-white text-gray-800 px-2 py-0.5 rounded border border-gray-200">Long: {{ customer.longitude || '-' }}</span>
                                </div>
                            </div>
                            <InfoRow v-else label="Koordinat" value="-" />
                            <InfoRow label="Keterangan / Notes" :value="customer.notes || '-'" />
                            <div class="flex gap-3 mt-2">
                                <a v-if="customer.latitude && customer.longitude" :href="`https://www.google.com/maps?q=${customer.latitude},${customer.longitude}`" target="_blank" class="text-xs text-blue-500 hover:underline inline-flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Buka Maps</a>
                                <a v-if="customer.latitude && customer.longitude" :href="`/network-radar?lat=${customer.latitude}&lng=${customer.longitude}`" target="_blank" class="text-xs text-indigo-600 font-bold hover:underline inline-flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>Radar ODP</a>
                            </div>
                        </div>
                        
                        <!-- 2. JADWAL SURVEY -->
                        <div class="bg-gray-50 rounded-xl p-4 space-y-2">
                            <div class="flex items-center justify-between pb-1 border-b border-gray-200/70 mb-2">
                                <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Jadwal Survey</p>
                                <button type="button" @click="openEditSurveyScheduleModal" class="text-xs font-semibold px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors flex items-center gap-1 shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    {{ customer.technician_schedules?.find(s => s.type === 'survey') ? 'Edit Jadwal' : '+ Atur Jadwal' }}
                                </button>
                            </div>
                            <template v-if="customer.technician_schedules?.find(s => s.type === 'survey')">
                                <InfoRow label="Teknisi" :value="getAssignedTechnicians('survey')" />
                                <InfoRow label="Tanggal" :value="formatDate(customer.technician_schedules.find(s => s.type === 'survey').scheduled_date)" />
                                <InfoRow label="Waktu" :value="customer.technician_schedules.find(s => s.type === 'survey').scheduled_time || '-'" />
                                <InfoRow label="Status">
                                    <StatusBadge :status="customer.technician_schedules.find(s => s.type === 'survey').status" />
                                </InfoRow>
                                <InfoRow v-if="customer.technician_schedules.find(s => s.type === 'survey').notes" label="Catatan" :value="customer.technician_schedules.find(s => s.type === 'survey').notes" />
                            </template>
                            <div v-else class="text-center py-4 space-y-2">
                                <p class="text-gray-400 text-sm">Belum ada jadwal survey</p>
                                <button type="button" @click="openEditSurveyScheduleModal" class="text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition-colors shadow-sm inline-flex items-center gap-1">
                                    + Atur Jadwal Survey
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 3. HASIL LAPORAN SURVEY -->
                    <div class="bg-gray-50 rounded-xl p-4 space-y-4">
                        <div class="flex justify-between items-center mb-2 border-b border-gray-200 pb-2">
                            <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Hasil Laporan Survey</p>
                            <div class="flex items-center gap-2">
                                <StatusBadge v-if="customer.surveys?.length" :status="customer.surveys[0].feasibility" />
                                <span v-else class="text-xs text-gray-500 italic px-2 py-1 rounded-md bg-gray-50 border border-gray-200">Menunggu Laporan</span>
                                <button type="button" @click="openEditSurveyReportModal" class="text-xs font-semibold px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-colors flex items-center gap-1 shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    {{ customer.surveys?.length ? 'Edit Survey' : '+ Input Survey' }}
                                </button>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <InfoRow label="Surveyor" :value="customer.surveys?.[0]?.surveyor?.name || '-'" />
                                <InfoRow label="Tanggal Survey" :value="formatDate(customer.surveys?.[0]?.survey_date) || '-'" />
                                <InfoRow label="Catatan" :value="customer.surveys?.[0]?.notes || '-'" />
                            </div>
                            <div class="space-y-2">
                                <InfoRow label="Rekomendasi ODP" :value="customer.surveys?.[0]?.odp?.name || '-'" />
                                <InfoRow label="Port ODP" :value="customer.surveys?.[0]?.port_number ? `Port ${customer.surveys[0].port_number}` : '-'" />
                                <InfoRow label="Redaman Optik" :value="customer.surveys?.[0]?.signal_loss ? `${customer.surveys[0].signal_loss} dBm` : '-'" />
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
                </div>

                <!-- Konfigurasi Layanan & ONT Section -->
                <div v-show="activeTab === 'audit' && (customer.status === 'active' || isAudit) && source !== 'instalasi'" class="glass-card p-6 mt-6 animate-fade-in-up border border-indigo-100 shadow-md">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span class="bg-indigo-100 text-indigo-600 p-2 rounded-lg">⚙️</span>
                            Konfigurasi Layanan & ONT
                        </h3>
                        <div class="flex gap-2">
                            <button type="button" @click="openEditActivationModal" class="text-xs font-bold px-4 py-2 rounded-lg transition-all bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 shadow-sm flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                Edit Aktivasi & Layanan
                            </button>
                        </div>
                    </div>

                    <!-- Ringkasan Status Layanan & Tagihan -->
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 p-4 mb-5 bg-white rounded-xl border border-indigo-100 shadow-sm">
                        <div>
                            <span class="text-[10px] text-gray-500 uppercase font-bold block mb-1">Tanggal Aktivasi</span>
                            <span class="text-sm font-bold text-gray-900">{{ formatDate(customer.activation_date) }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-gray-500 uppercase font-bold block mb-1">Status Layanan</span>
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full inline-block" :class="customer.service_status === 'free_home' ? 'bg-amber-100 text-amber-800' : (customer.service_status === 'gratis' ? 'bg-purple-100 text-purple-800' : 'bg-emerald-100 text-emerald-800')">
                                {{ customer.service_status === 'free_home' ? 'Free Home (Rp 0)' : (customer.service_status === 'gratis' ? 'Gratis (Rp 0)' : 'Berbayar') }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[10px] text-gray-500 uppercase font-bold block mb-1">Jenis Tagihan</span>
                            <span class="text-sm font-bold text-indigo-700">{{ customer.billing_type === 'pascabayar' ? 'Pasca Bayar' : 'Prabayar' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-gray-500 uppercase font-bold block mb-1">Paket Internet</span>
                            <span class="text-sm font-bold text-gray-900">{{ customer.package?.name || '-' }}</span>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">PPPoE/Member Username</label>
                                <input :value="customer.ont?.pppoe_user || '-'" type="text" class="w-full border rounded-lg px-4 py-2.5 bg-gray-100 border-gray-200 text-gray-700 font-medium" readonly />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">PPPoE/Member Password</label>
                                <input :value="customer.ont?.pppoe_password || '-'" type="text" class="w-full border rounded-lg px-4 py-2.5 bg-gray-100 border-gray-200 text-gray-700 font-medium" readonly />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Mode Akses</label>
                                <input :value="customer.ont?.access_mode || 'PPPOE'" type="text" class="w-full border rounded-lg px-4 py-2.5 bg-gray-100 border-gray-200 text-gray-700 font-medium" readonly />
                            </div>
                            
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">VLAN Mode & ID</label>
                                <input :value="customer.ont?.vlan_mode ? `${customer.ont.vlan_mode} ${customer.ont.vlan_id ? '(ID: ' + customer.ont.vlan_id + ')' : ''}` : '-'" type="text" class="w-full border rounded-lg px-4 py-2.5 bg-gray-100 border-gray-200 text-gray-700 font-medium" readonly />
                            </div>
                        </div>
                        
                        <div class="pt-4 border-t border-gray-200 grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">IP Login ONT</label>
                                <input :value="customer.ont?.ip_login || '192.168.1.1'" type="text" class="w-full border rounded-lg px-4 py-2 bg-gray-100 border-gray-200 text-gray-700 text-sm" readonly />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">User ONT</label>
                                <input :value="customer.ont?.login_user || 'admin'" type="text" class="w-full border rounded-lg px-4 py-2 bg-gray-100 border-gray-200 text-gray-700 text-sm" readonly />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Pass ONT</label>
                                <input :value="customer.ont?.login_password || 'admin'" type="text" class="w-full border rounded-lg px-4 py-2 bg-gray-100 border-gray-200 text-gray-700 text-sm" readonly />
                            </div>
                        </div>

                        <div v-if="customer.ont?.free_hotspot" class="mt-5 p-4 bg-amber-50 rounded-xl border border-amber-200">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="text-sm font-bold text-amber-900 uppercase tracking-wider flex items-center gap-2">
                                    <span class="bg-amber-200 p-1 rounded-md text-amber-700">🎁</span>
                                    Gratis 1 User Hotspot Aktif
                                </span>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Username Hotspot</label>
                                    <input :value="customer.ont?.hotspot_user || '-'" type="text" class="w-full border rounded-lg px-4 py-2 bg-amber-100/50 border-amber-200 text-amber-900 text-sm font-medium" readonly />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Password Hotspot</label>
                                    <input :value="customer.ont?.hotspot_password || '-'" type="text" class="w-full border rounded-lg px-4 py-2 bg-amber-100/50 border-amber-200 text-amber-900 text-sm font-medium" readonly />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">VLAN ID</label>
                                    <input :value="customer.ont?.hotspot_vlan_id || '-'" type="text" class="w-full border rounded-lg px-4 py-2 bg-amber-100/50 border-amber-200 text-amber-900 text-sm font-medium" readonly />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div v-show="(!isEditingInstallation && !isLaporanPasang) && (source === 'instalasi' || ((activeTab === 'audit' || activeTab === 'installation') && (isAudit || customer.status === 'active' || customer.status === 'installing')))" class="glass-card p-6 mt-6 animate-fade-in-up border-2 border-amber-500/20">
                    <div v-if="source !== 'instalasi'" class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span class="bg-amber-100 text-amber-600 p-2 rounded-lg">🚀</span>
                            {{ activeTab === 'audit' ? 'Data Instalasi & ONT' : 'Hasil Laporan Instalasi & ONT' }}
                        </h3>
                        <button type="button" @click="openEditInstallationReportModal" class="btn-primary text-xs px-3 py-1.5 shadow-sm bg-blue-100 text-blue-700 border-none hover:bg-blue-200 flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            Edit Laporan Instalasi & ONT
                        </button>
                    </div>
                        
                    <!-- 4. JADWAL PASANG -->
                    <div class="bg-indigo-50 p-4 rounded-xl border border-indigo-100 mb-6 space-y-2">
                        <div class="flex items-center justify-between pb-1 border-b border-indigo-200/60 mb-2">
                            <p class="text-xs font-semibold text-indigo-800 uppercase tracking-wider">Info Penugasan Pasang (Jadwal)</p>
                            <button type="button" @click="openEditInstallScheduleModal" class="text-xs font-semibold px-2.5 py-1 rounded-md bg-white border border-indigo-200 text-indigo-700 hover:bg-indigo-100 transition-colors flex items-center gap-1 shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                {{ customer.technician_schedules?.find(s => s.type === 'installation') ? 'Edit Jadwal Pasang' : '+ Atur Jadwal Pasang' }}
                            </button>
                        </div>
                        <template v-if="customer.technician_schedules?.find(s => s.type === 'installation')">
                            <InfoRow label="Teknisi" :value="getAssignedTechnicians('installation')" />
                            <InfoRow label="Tanggal Pasang" :value="formatDate(customer.technician_schedules.find(s => s.type === 'installation').scheduled_date)" />
                            <InfoRow label="Waktu Target" :value="customer.technician_schedules.find(s => s.type === 'installation').scheduled_time || '-'" />
                            <InfoRow label="Status">
                                <StatusBadge :status="customer.technician_schedules.find(s => s.type === 'installation').status" />
                            </InfoRow>
                            <InfoRow v-if="installationNotes || customer.technician_schedules.find(s => s.type === 'installation').notes" label="Catatan Khusus" :value="installationNotes || customer.technician_schedules.find(s => s.type === 'installation').notes" />
                        </template>
                        <div v-else class="text-center py-3 text-indigo-600 text-sm">
                            Belum ada jadwal penugasan pasang teknisi.
                        </div>
                    </div>
                        
                        <!-- RIWAYAT ALOKASI & SERAH TERIMA MATERIAL & ONT -->
                        <div v-if="hardwareItems.length > 0 || detailedReturnItems.length > 0" class="bg-gradient-to-br from-amber-50/70 via-white to-amber-50/40 p-5 rounded-2xl border border-amber-200/90 shadow-sm mb-6 space-y-4">
                            <!-- Header -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-amber-200/70 gap-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-base">📦</span>
                                        <p class="text-xs font-bold text-amber-900 uppercase tracking-wider">Riwayat Alokasi & Serah Terima Material / ONT</p>
                                    </div>
                                    <p class="text-[11px] text-amber-700/80 mt-0.5">Rincian perangkat terpasang di pelanggan dan sisa material yang dikembalikan ke Gudang.</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span v-if="detailedReturnItems.length > 0" class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 border border-amber-300 flex items-center gap-1 shadow-2xs">
                                        <span>↩️</span> {{ detailedReturnItems.length }} Item Retur Gudang
                                    </span>
                                    <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1 shadow-2xs">
                                        <span>✓</span> {{ hardwareItems.length }} Terpasang
                                    </span>
                                </div>
                            </div>

                            <!-- 1. PERANGKAT & MATERIAL TERPASANG DI PELANGGAN -->
                            <div class="space-y-2">
                                <p class="text-[11px] font-bold text-emerald-900 uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                                    Perangkat & Material Terpasang di Pelanggan (Pemakaian):
                                </p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                                    <div v-for="item in hardwareItems" :key="item.id" class="text-sm bg-white p-3 rounded-xl border border-gray-200 shadow-2xs hover:border-emerald-300 transition-all flex items-center justify-between">
                                        <div class="min-w-0 pr-2">
                                            <div class="flex items-center gap-1.5 mb-0.5">
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded" :class="item.type === 'ONT' ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-800'">
                                                    {{ item.type }}
                                                </span>
                                                <span v-if="item.initialAllocated" class="text-[10px] text-gray-500 font-medium">
                                                    Bekal Awal: {{ item.initialAllocated }}
                                                </span>
                                            </div>
                                            <span class="font-bold text-gray-900 text-xs sm:text-sm block truncate" :title="item.name">{{ item.name }}</span>
                                            <span v-if="item.returnedNote" class="text-[11px] text-amber-700 block mt-0.5 font-medium">
                                                {{ item.returnedNote }}
                                            </span>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <span class="text-xs text-emerald-700 font-bold bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200 flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Dipasang
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. DETAIL MATERIAL YANG DIKEMBALIKAN KE GUDANG (RETUR FISIK) -->
                            <div v-if="detailedReturnItems.length > 0" class="pt-3 border-t border-amber-200/70 space-y-2">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <p class="text-[11px] font-bold text-amber-950 uppercase tracking-wider flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>
                                        Detail Material yang Dikembalikan ke Gudang (Retur Fisik):
                                    </p>
                                    <!-- Badge Status Serah Terima Gudang -->
                                    <div v-if="latestReturnTrx" class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-[10px] text-gray-500 font-mono font-medium bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                                            {{ latestReturnTrx.transaction_number }}
                                        </span>
                                        <span v-if="latestReturnTrx.handover_status === 'received' || latestReturnTrx.handover_status === 'handover_approved' || latestReturnTrx.status === 'completed'" class="text-[10px] font-bold px-2.5 py-0.5 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1">
                                            ✓ Diterima Gudang
                                        </span>
                                        <span v-else-if="latestReturnTrx.handover_status === 'rejected'" class="text-[10px] font-bold px-2.5 py-0.5 rounded-md bg-rose-100 text-rose-800 border border-rose-300">
                                            ✕ Ditolak
                                        </span>
                                        <span v-else class="text-[10px] font-bold px-2.5 py-0.5 rounded-md bg-amber-100 text-amber-800 border border-amber-300 flex items-center gap-1">
                                            ⏳ Menunggu Verifikasi Gudang
                                        </span>
                                    </div>
                                </div>

                                <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-3.5 space-y-2">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        <div v-for="(ret, idx) in detailedReturnItems" :key="'ret-item-'+idx" class="bg-white p-3 rounded-lg border border-amber-200/90 shadow-2xs flex items-center justify-between">
                                            <div>
                                                <div class="flex items-center gap-2 mb-1">
                                                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-amber-100 text-amber-900 border border-amber-200">
                                                        Sisa Retur
                                                    </span>
                                                    <span class="text-[10px] font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                                        {{ ret.condition || 'Layak Pakai' }}
                                                    </span>
                                                </div>
                                                <p class="font-bold text-gray-900 text-xs sm:text-sm">{{ ret.name }}</p>
                                                <p v-if="ret.note" class="text-[11px] text-gray-500 mt-0.5 italic">{{ ret.note }}</p>
                                            </div>
                                            <div class="text-right">
                                                <span class="text-sm font-extrabold text-amber-900 block">
                                                    {{ ret.qty }} {{ ret.unit }}
                                                </span>
                                                <span class="text-[10px] text-amber-700 font-medium">Dikembalikan</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="latestReturnTrx?.notes" class="text-[11px] text-amber-800/90 bg-amber-100/50 p-2.5 rounded-lg border border-amber-200/60 mt-2">
                                        <span class="font-semibold">Catatan Pengembalian Gudang:</span>
                                        <p class="whitespace-pre-line mt-0.5 font-mono text-[10px]">{{ latestReturnTrx.notes }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div class="space-y-2">
                                <InfoRow label="Waktu Mulai" :value="customer.ont?.start_time || '-'" />
                                <InfoRow label="Waktu Selesai" :value="customer.ont?.end_time || '-'" />
                                <InfoRow label="Redaman Saat Ini" :value="customer.ont?.rx_power ? `${customer.ont.rx_power} dBm` : '-'" />
                                <InfoRow v-if="customer.ont?.initial_rx_power" label="Redaman Awal Instalasi" :value="`${customer.ont.initial_rx_power} dBm`" />
                            </div>
                            <div class="space-y-2">
                                <InfoRow label="Nama ODP" :value="customer.ont?.odp?.name || '-'" />
                                <InfoRow label="Port ODP" :value="`Port ${customer.ont?.port_number}`" />
                                <InfoRow label="Serial Number (Auto)" :value="customer.ont?.serial_number" />
                                <InfoRow v-if="customer.ont?.last_rx_ticket_ref" label="Pembaruan Pasca Gangguan" :value="`Tiket ${customer.ont.last_rx_ticket_ref}`" />
                            </div>
                        </div>
                        
                        <div class="pt-4 border-t border-gray-200 mt-4">
                            <p class="text-xs text-gray-500 mb-3 uppercase font-semibold">Hasil Dokumentasi Lapangan:</p>
                            <div class="flex gap-4 overflow-x-auto pb-2">
                                <div v-if="customer.ont?.photo_odp" class="shrink-0 group relative">
                                    <img :src="`/storage/${customer.ont?.photo_odp}`" class="h-32 w-32 object-cover rounded-lg border border-gray-200 cursor-pointer hover:border-amber-500 transition-colors" @click="openImage(`/storage/${customer.ont?.photo_odp}`, 'Foto ODP / Port')" />
                                    <div class="absolute bottom-0 left-0 right-0 bg-black/60 p-1 rounded-b-lg text-[10px] text-center text-white truncate pointer-events-none">Foto ODP</div>
                                </div>
                                <div v-if="customer.ont?.photo_installation" class="shrink-0 group relative">
                                    <img :src="`/storage/${customer.ont?.photo_installation}`" class="h-32 w-32 object-cover rounded-lg border border-gray-200 cursor-pointer hover:border-amber-500 transition-colors" @click="openImage(`/storage/${customer.ont?.photo_installation}`, 'Foto Instalasi di Rumah')" />
                                    <div class="absolute bottom-0 left-0 right-0 bg-black/60 p-1 rounded-b-lg text-[10px] text-center text-white truncate pointer-events-none">Foto Instalasi</div>
                                </div>
                                <div v-if="customer.ont?.photo_ont" class="shrink-0 group relative">
                                    <img :src="`/storage/${customer.ont?.photo_ont}`" class="h-32 w-32 object-cover rounded-lg border border-gray-200 cursor-pointer hover:border-amber-500 transition-colors" @click="openImage(`/storage/${customer.ont?.photo_ont}`, 'Foto Posisi ONT')" />
                                    <div class="absolute bottom-0 left-0 right-0 bg-black/60 p-1 rounded-b-lg text-[10px] text-center text-white truncate pointer-events-none">Foto ONT</div>
                                </div>
                                <div v-if="customer.ont?.photo_customer" class="shrink-0 group relative">
                                    <img :src="`/storage/${customer.ont?.photo_customer}`" class="h-32 w-32 object-cover rounded-lg border border-gray-200 cursor-pointer hover:border-amber-500 transition-colors" @click="openImage(`/storage/${customer.ont?.photo_customer}`, 'Foto Selfie Pelanggan')" />
                                    <div class="absolute bottom-0 left-0 right-0 bg-black/60 p-1 rounded-b-lg text-[10px] text-center text-white truncate pointer-events-none">Foto Selfie</div>
                                </div>
                                <div v-if="customer.ont?.photo_redaman" class="shrink-0 group relative">
                                    <img :src="`/storage/${customer.ont?.photo_redaman}`" class="h-32 w-32 object-cover rounded-lg border border-gray-200 cursor-pointer hover:border-amber-500 transition-colors" @click="openImage(`/storage/${customer.ont?.photo_redaman}`, 'Foto Redaman')" />
                                    <div class="absolute bottom-0 left-0 right-0 bg-black/60 p-1 rounded-b-lg text-[10px] text-center text-white truncate pointer-events-none">Foto Redaman</div>
                                </div>
                            </div>
                        </div>

                        <div v-if="customer.status === 'installing'" class="flex justify-end pt-4 border-t border-gray-200 mt-6 gap-3">
                            <button v-if="!customer.is_audited && hasPermission('customers_installed_audit')" @click="showAuditModal = true" class="btn-primary w-full md:w-auto text-lg py-3 shadow-lg hover:shadow-xl bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 border-0 text-white">
                                Selesaikan Audit
                            </button>
                            <button v-else-if="customer.is_audited && hasPermission('customers_activation_activate')" @click="showActivationModal = true" class="btn-primary w-full md:w-auto text-lg py-3 shadow-lg hover:shadow-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 border-0 text-white">
                                Aktivasi Pelanggan
                            </button>
                        </div>
                    </div>

                <!-- Instalasi Section (Form) -->
                <div v-show="(source === 'instalasi' || activeTab === 'installation') && (isLaporanPasang || isEditingInstallation) && (hasPermission('customers_installed_report') || hasPermission('customers_installed_edit'))" class="glass-card p-6 mt-6 animate-fade-in-up border-2 border-emerald-500/20">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span class="bg-emerald-100 text-emerald-600 p-2 rounded-lg">🚀</span>
                            Form Laporan Selesai Instalasi
                        </h3>
                        <button v-if="isEditingInstallation" @click="isEditingInstallation = false" class="btn-ghost text-xs px-3 py-1.5 hover:bg-gray-100 rounded-lg">
                            Batal Edit
                        </button>
                    </div>

                    <!-- Patokan Hasil Survey Lapangan -->
                    <div v-if="latestSurvey" class="bg-gradient-to-br from-sky-50 via-cyan-50/50 to-emerald-50/40 p-4 sm:p-5 rounded-2xl border border-sky-200/80 shadow-xs mb-6 space-y-4">
                        <!-- Header -->
                        <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-sky-200/60">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-sky-600 text-white flex items-center justify-center font-bold text-base shadow-xs shrink-0">
                                    🔭
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-sky-950 uppercase tracking-wider flex items-center gap-1.5">
                                        Patokan Hasil Survey Lapangan
                                    </h4>
                                    <p class="text-[11px] text-sky-700/80">
                                        Surveyor: <span class="font-semibold text-sky-900">{{ latestSurvey.surveyor?.name || '-' }}</span> 
                                        <span v-if="latestSurvey.survey_date" class="text-sky-400 mx-1">•</span> 
                                        <span v-if="latestSurvey.survey_date">{{ formatDate(latestSurvey.survey_date) }}</span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button v-if="latestSurvey.odp_id && (!ontForm.odp_id || ontForm.odp_id != latestSurvey.odp_id || (latestSurvey.port_number && ontForm.port_number != latestSurvey.port_number))" 
                                        type="button" 
                                        @click="applySurveyToForm" 
                                        class="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-sky-600 hover:bg-sky-700 text-white shadow-xs transition-colors flex items-center gap-1 cursor-pointer"
                                        title="Otomatis gunakan ODP dan Port hasil survey ini pada form di bawah">
                                    <span>⚡</span> Gunakan Data Survey Ini
                                </button>
                                <span v-if="latestSurvey.feasibility === 'feasible'" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 shadow-2xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Feasible (Layak)
                                </span>
                                <span v-else-if="latestSurvey.feasibility === 'not_feasible'" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200 shadow-2xs">
                                    ⚠️ Unfeasible (Tidak Layak)
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200 shadow-2xs">
                                    ⏳ {{ latestSurvey.feasibility || 'Pending' }}
                                </span>
                            </div>
                        </div>

                        <!-- 4 Kolom Metrik Patokan Utama -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <!-- Rekomendasi ODP & Port -->
                            <div class="bg-white/95 backdrop-blur-xs p-3 rounded-xl border border-sky-100 shadow-2xs space-y-1">
                                <span class="text-[10px] font-bold text-sky-700 uppercase tracking-wider block">ODP & Port Target</span>
                                <div class="flex items-center justify-between gap-1">
                                    <div class="font-bold text-gray-900 text-sm truncate" :title="latestSurvey.odp?.name || '-'">
                                        {{ latestSurvey.odp?.name || '-' }}
                                    </div>
                                    <span v-if="latestSurvey.port_number" class="px-2 py-0.5 rounded-md bg-sky-100 text-sky-800 font-mono text-xs font-bold shrink-0">
                                        Port {{ latestSurvey.port_number }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-gray-500 truncate flex items-center justify-between gap-1 pt-0.5">
                                    <span v-if="latestSurvey.odp?.odc?.name" class="truncate" :title="'ODC: ' + latestSurvey.odp.odc.name">ODC: {{ latestSurvey.odp.odc.name }}</span>
                                    <span v-else class="truncate">{{ latestSurvey.odp?.address || '-' }}</span>
                                    <span :class="latestSurvey.port_available ? 'text-emerald-600 font-medium' : 'text-amber-600 font-medium'" class="shrink-0">
                                        {{ latestSurvey.port_available ? 'Port Bebas' : 'Penuh' }}
                                    </span>
                                </div>
                                <a v-if="latestSurvey.odp?.latitude && latestSurvey.odp?.longitude" 
                                   :href="`https://www.google.com/maps?q=${latestSurvey.odp.latitude},${latestSurvey.odp.longitude}`" 
                                   target="_blank" 
                                   class="mt-1 text-[11px] text-blue-600 hover:text-blue-800 font-medium inline-flex items-center gap-1 hover:underline">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Titik ODP di Maps
                                </a>
                            </div>

                            <!-- Estimasi Tarikan Kabel -->
                            <div class="bg-white/95 backdrop-blur-xs p-3 rounded-xl border border-sky-100 shadow-2xs space-y-1">
                                <span class="text-[10px] font-bold text-sky-700 uppercase tracking-wider block">Estimasi Tarikan Kabel</span>
                                <div class="font-extrabold text-gray-900 text-base flex items-baseline gap-1">
                                    <span>{{ latestSurvey.distance_meters ? Math.round(latestSurvey.distance_meters) : '-' }}</span>
                                    <span class="text-xs font-normal text-gray-500">meter (Dropcore)</span>
                                </div>
                                <p class="text-[11px] text-gray-500 leading-tight">
                                    Perkiraan kebutuhan panjang kabel dari ODP ke rumah.
                                </p>
                            </div>

                            <!-- Redaman Optik di ODP -->
                            <div class="bg-white/95 backdrop-blur-xs p-3 rounded-xl border border-sky-100 shadow-2xs space-y-1">
                                <span class="text-[10px] font-bold text-sky-700 uppercase tracking-wider block">Redaman Optik ODP</span>
                                <div class="font-extrabold font-mono text-base flex items-baseline gap-1" :class="latestSurvey.signal_loss ? 'text-cyan-700' : 'text-gray-400'">
                                    <span>{{ latestSurvey.signal_loss ? latestSurvey.signal_loss : '-' }}</span>
                                    <span v-if="latestSurvey.signal_loss" class="text-xs font-sans font-normal text-gray-500">dBm</span>
                                </div>
                                <p class="text-[11px] text-gray-500 leading-tight">
                                    Nilai acuan redaman port ODP sebelum ditarik ke pelanggan.
                                </p>
                            </div>

                            <!-- Surveyor Lapangan -->
                            <div class="bg-white/95 backdrop-blur-xs p-3 rounded-xl border border-sky-100 shadow-2xs space-y-1">
                                <span class="text-[10px] font-bold text-sky-700 uppercase tracking-wider block">Surveyor Lapangan</span>
                                <div class="font-bold text-gray-900 text-sm truncate">
                                    {{ latestSurvey.surveyor?.name || '-' }}
                                </div>
                                <p class="text-[11px] text-gray-500 truncate">
                                    Tgl: {{ latestSurvey.survey_date ? formatDate(latestSurvey.survey_date) : '-' }}
                                </p>
                                <span class="inline-flex items-center gap-1 text-[10px] text-sky-700 font-semibold bg-sky-50 px-2 py-0.5 rounded border border-sky-200/50">
                                    ✓ Data Terverifikasi
                                </span>
                            </div>
                        </div>

                        <!-- Catatan / Rute Penarikan Jalur -->
                        <div v-if="latestSurvey.notes" class="bg-white/90 p-3 rounded-xl border border-sky-100 text-xs text-gray-700 space-y-1">
                            <div class="flex items-center gap-1.5 text-sky-900 font-bold text-[11px] uppercase tracking-wider">
                                <span>📝</span>
                                <span>Catatan Rute & Penarikan Jalur:</span>
                            </div>
                            <p class="text-gray-800 leading-relaxed whitespace-pre-line pl-5">{{ latestSurvey.notes }}</p>
                        </div>

                        <!-- Foto Dokumentasi Hasil Survey -->
                        <div v-if="latestSurvey.photos && latestSurvey.photos.length > 0" class="pt-2 border-t border-sky-200/60">
                            <p class="text-[11px] font-bold text-sky-900 uppercase tracking-wider mb-2 flex items-center gap-1">
                                <span>📷</span> Dokumentasi Survey (Klik foto untuk perbesar):
                            </p>
                            <div class="flex gap-3 overflow-x-auto pb-1">
                                <div v-for="(photo, idx) in latestSurvey.photos" :key="idx" 
                                     class="relative group shrink-0 w-24 h-24 rounded-xl overflow-hidden border-2 border-white shadow-sm bg-gray-100 cursor-pointer hover:border-sky-500 hover:shadow-md transition-all"
                                     @click="openImage(getSurveyPhotoUrl(photo), getSurveyPhotoLabel(photo, idx))">
                                    <img :src="getSurveyPhotoUrl(photo)" :alt="getSurveyPhotoLabel(photo, idx)" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" />
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-transparent to-transparent opacity-90 group-hover:opacity-100 flex items-end p-1.5 transition-opacity">
                                        <p class="text-[10px] text-white font-medium truncate w-full leading-tight drop-shadow-xs">
                                            {{ getSurveyPhotoLabel(photo, idx) }}
                                        </p>
                                    </div>
                                    <div class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity bg-black/60 text-white rounded p-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Jika Tidak Ada Data Survey -->
                    <div v-else class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 mb-6 flex items-center justify-between text-xs text-slate-600">
                        <div class="flex items-center gap-2">
                            <span class="text-base">ℹ️</span>
                            <span><strong>Info Survey:</strong> Belum ada data survey lapangan untuk pelanggan ini (Pemasangan Langsung / Tanpa Riwayat Survey).</span>
                        </div>
                    </div>

                    <div v-if="customer.technician_schedules?.find(s => s.type === 'installation')" class="bg-indigo-50 p-4 rounded-xl border border-indigo-100 mb-6 space-y-2">
                            <p class="text-xs font-semibold text-indigo-800 uppercase tracking-wider mb-2">Info Penugasan Pasang</p>
                            <InfoRow label="Teknisi" :value="getAssignedTechnicians('installation')" />
                            <InfoRow label="Tanggal Pasang" :value="formatDate(customer.technician_schedules.find(s => s.type === 'installation').scheduled_date)" />
                            <InfoRow label="Waktu Target" :value="customer.technician_schedules.find(s => s.type === 'installation').scheduled_time || '-'" />
                        </div>
                        <div v-else class="bg-amber-50 p-6 rounded-xl border border-amber-200 text-center space-y-3 mb-6">
                            <div class="text-3xl">⚠️</div>
                            <h4 class="font-bold text-amber-800 text-lg">Belum Ada Jadwal Pasang</h4>
                            <p class="text-sm text-amber-700 max-w-lg mx-auto">Pelanggan ini belum dijadwalkan untuk pemasangan. Silakan kembali ke halaman <strong>Instalasi</strong> dan klik tombol <strong>Jadwalkan Pasang</strong> untuk memilih teknisi dan waktu instalasi.</p>
                            <div class="pt-2">
                                <Link href="/customers/installed" class="btn-primary inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 border-none px-6 py-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                    Kembali ke Data Instalasi
                                </Link>
                            </div>
                        </div>

                        <form v-if="customer.technician_schedules?.find(s => s.type === 'installation')" @submit.prevent="submitOnt" class="space-y-6">
                            
                            <!-- STEP 1: Mulai -->
                            <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100">
                                <p class="text-xs font-semibold text-blue-800 uppercase tracking-wider mb-3">1. Jam Mulai Pekerjaan</p>
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="block text-xs font-medium text-gray-500">Jam Mulai</label>
                                        <button type="button" @click="setNow('start_time')" :disabled="!!ontForm.start_time" :class="['text-[10px] px-3 py-1 rounded font-bold transition-colors shadow-sm', ontForm.start_time ? 'bg-gray-400 text-white cursor-not-allowed' : 'bg-blue-600 text-white hover:bg-blue-700']">MULAI SEKARANG</button>
                                    </div>
                                    <input v-model="ontForm.start_time" type="time" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-600 focus:ring-0 cursor-not-allowed" required readonly />
                                </div>
                            </div>

                            <!-- STEP 2: ONT & Material -->
                            <div v-if="customer.technician_schedules?.find(s => s.type === 'installation')" class="bg-amber-50 p-4 rounded-xl border border-amber-100">
                                <p class="text-xs font-semibold text-amber-800 uppercase tracking-wider mb-3">2. Kebutuhan ONT & Material</p>
                                
                                <!-- Catatan Khusus dari Admin (jika ada) -->
                                <div v-if="installationNotes" class="mb-3 p-3 bg-amber-100/80 border border-amber-200 rounded-lg text-xs text-amber-900 flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-amber-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <div>
                                        <strong class="font-bold text-amber-900 block mb-0.5">Catatan Tambahan:</strong>
                                        <span class="whitespace-pre-line">{{ installationNotes }}</span>
                                    </div>
                                </div>
                                
                                <div v-if="hardwareItems.length === 0" class="text-sm text-gray-500 italic p-3 bg-white rounded-lg border border-amber-200">
                                    Tidak ada catatan kebutuhan khusus.
                                </div>
                                
                                <div v-else class="space-y-3">
                                    <div v-for="item in hardwareItems" :key="item.id" class="text-sm text-gray-700 bg-white p-3.5 rounded-xl border border-amber-200 shadow-2xs flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-wrap items-center gap-1.5 mb-1.5">
                                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md" 
                                                      :class="item.type === 'ONT' ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-800'">
                                                    {{ item.type }}
                                                </span>
                                                <span v-if="item.isInstalled && item.type === 'Material'" class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-md border border-emerald-200 flex items-center gap-1 shadow-2xs">
                                                    ✓ Terpakai: {{ getMaterialUsageInfo(item)?.usedQty }} {{ getMaterialUsageInfo(item)?.unit }}
                                                </span>
                                                <span v-if="item.isInstalled && item.type === 'Material' && getMaterialUsageInfo(item)?.returnedQty > 0" class="text-[11px] font-bold text-amber-800 bg-amber-50 px-2.5 py-0.5 rounded-md border border-amber-300 flex items-center gap-1 shadow-2xs">
                                                    📦 Sisa Retur: {{ getMaterialUsageInfo(item)?.returnedQty }} {{ getMaterialUsageInfo(item)?.unit }} (ke Gudang)
                                                </span>
                                                <span v-if="item.isInstalled && item.type === 'ONT' && item.isBroken" class="text-[11px] font-bold text-rose-700 bg-rose-50 px-2.5 py-0.5 rounded-md border border-rose-200 flex items-center gap-1 shadow-2xs">
                                                    ⚠️ Rusak / Retur RMA ke Gudang
                                                </span>
                                                <span v-else-if="item.isInstalled && item.type === 'ONT'" class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-md border border-emerald-200 flex items-center gap-1 shadow-2xs">
                                                    ✓ Berfungsi Normal
                                                </span>
                                            </div>
                                            <div class="font-bold text-gray-900 truncate" :title="item.name">
                                                {{ item.name }}
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <button type="button" 
                                                :disabled="!ontForm.start_time" 
                                                @click="openInstallModal(item)"
                                                :class="['px-5 py-2 rounded-lg font-bold text-xs transition-all whitespace-nowrap flex items-center gap-2 cursor-pointer', 
                                                    !ontForm.start_time ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 
                                                    item.isInstalled ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200 shadow-2xs border border-emerald-200' : 'bg-amber-500 text-white hover:bg-amber-600 shadow-sm hover:shadow-md']">
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

                                    <!-- Kotak Ringkasan Serah Terima & Retur Material -->
                                    <div v-if="materialHandoverSummary.hasInstalled" class="mt-4 p-4 rounded-xl bg-white border border-amber-200/90 shadow-2xs space-y-3">
                                        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                                            <h5 class="text-xs font-bold text-gray-900 uppercase tracking-wider flex items-center gap-1.5">
                                                <span>📦</span> Ringkasan Serah Terima Material Lapangan
                                            </h5>
                                            <span class="text-[10px] text-gray-500 italic">Otomatis sinkron ke Gudang</span>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                            <!-- Terpasang di Pelanggan -->
                                            <div class="bg-emerald-50/70 p-3 rounded-lg border border-emerald-100 space-y-1.5">
                                                <span class="font-bold text-emerald-900 flex items-center gap-1 text-[11px] uppercase tracking-wider">
                                                    <span>🟢</span> Terpasang di Pelanggan (Potong Stok):
                                                </span>
                                                <ul class="list-disc pl-5 space-y-0.5 text-emerald-800">
                                                    <li v-for="(inst, i) in materialHandoverSummary.installed" :key="'inst-'+i">
                                                        <strong>{{ inst.name }}</strong>: {{ inst.qty }} {{ inst.unit }}
                                                    </li>
                                                </ul>
                                            </div>

                                            <!-- Sisa Retur ke Gudang -->
                                            <div class="p-3 rounded-lg border space-y-1.5" :class="materialHandoverSummary.hasReturned ? 'bg-amber-50/70 border-amber-200' : 'bg-slate-50 border-slate-200'">
                                                <span class="font-bold flex items-center gap-1 text-[11px] uppercase tracking-wider" :class="materialHandoverSummary.hasReturned ? 'text-amber-900' : 'text-slate-600'">
                                                    <span>↩️</span> Wajib Dikembalikan ke Gudang (Retur Fisik):
                                                </span>
                                                <div v-if="materialHandoverSummary.hasReturned">
                                                    <ul class="list-disc pl-5 space-y-0.5 text-amber-800">
                                                        <li v-for="(ret, r) in materialHandoverSummary.returned" :key="'ret-'+r">
                                                            <strong>{{ ret.name }}</strong>: {{ ret.qty }} {{ ret.unit }}
                                                        </li>
                                                    </ul>
                                                    <p class="text-[10px] text-amber-700/90 mt-1 pl-1">
                                                        * Sisa material di atas otomatis dibuatkan formulir serah terima retur ke Gudang saat laporan dikirim.
                                                    </p>
                                                </div>
                                                <div v-else class="text-[11px] text-slate-500 italic pl-1">
                                                    Seluruh material habis terpasang (Tidak ada sisa retur).
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Request Material List & Button -->
                            <div v-if="customer.technician_schedules?.find(s => s.type === 'installation')" class="bg-blue-50 p-4 rounded-xl border border-blue-100 mt-4">
                                <div class="flex justify-between items-center mb-3">
                                    <p class="text-xs font-semibold text-blue-800 uppercase tracking-wider">Permintaan Tambahan Material</p>
                                    <button type="button" @click="showRequestModal = true" class="text-[10px] px-3 py-1.5 bg-blue-600 text-white rounded font-bold hover:bg-blue-700 transition-colors shadow-sm flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        AJUKAN TAMBAHAN
                                    </button>
                                </div>
                                
                                <div v-if="!materialRequests || materialRequests.length === 0" class="text-sm text-gray-500 italic p-3 bg-white rounded-lg border border-blue-200">
                                    Belum ada permintaan tambahan material.
                                </div>
                                <div v-else class="space-y-2">
                                    <div v-for="req in materialRequests" :key="req.id" class="text-sm bg-white p-3 rounded-lg border border-blue-200 shadow-sm flex justify-between items-center">
                                        <div>
                                            <p class="font-bold text-gray-800">{{ req.request_number }}</p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <span v-for="(item, index) in req.items" :key="item.id">
                                                    {{ item.material?.name }} ({{ item.quantity }})<span v-if="index < req.items.length - 1">, </span>
                                                </span>
                                            </p>
                                        </div>
                                        <div>
                                            <span v-if="req.status === 'pending'" class="px-2 py-1 bg-amber-100 text-amber-700 text-xs font-bold rounded-md border border-amber-200">Menunggu Persetujuan</span>
                                            <span v-else-if="req.status === 'approved'" class="px-2 py-1 bg-emerald-100 text-emerald-700 text-xs font-bold rounded-md border border-emerald-200">Disetujui</span>
                                            <span v-else class="px-2 py-1 bg-red-100 text-red-700 text-xs font-bold rounded-md border border-red-200">Ditolak</span>
                                        </div>
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
                                            <template v-if="filteredAvailableOdps.length === 0">
                                                <option value="" disabled>Tidak ada ODP di area ini</option>
                                            </template>
                                            <template v-else>
                                                <option v-for="odp in filteredAvailableOdps" :key="odp.id" :value="odp.id" :disabled="(odp.total_ports - odp.used_ports) <= 0">
                                                    {{ odp.name }} - {{ (odp.total_ports - odp.used_ports) <= 0 ? 'FULL' : `Sisa ${Math.max(0, odp.total_ports - odp.used_ports)} port` }}
                                                </option>
                                            </template>
                                        </select>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Port ODP</label>
                                            <select v-model="ontForm.port_number" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" :disabled="!selectedOdp" :required="isInstallingHardware">
                                                <option value="">-- Pilih Port --</option>
                                                <template v-if="selectedOdp">
                                                    <option v-for="i in selectedOdp.total_ports" :key="i" :value="i" :disabled="isPortUsed(selectedOdp, i)">
                                                        Port {{ i }} {{ getPortStatus(selectedOdp, i) }}
                                                    </option>
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
                                        <label class="block text-xs font-medium text-gray-500">1. Foto ODP / Port (Opsional)</label>
                                        <div class="flex items-start gap-3">
                                            <input type="file" @change="e => handleFileUpload('photo_odp', e)" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                            <div v-if="previewUrls.photo_odp" class="flex flex-col gap-1 shrink-0">
                                                <img :src="previewUrls.photo_odp" class="h-10 w-10 object-cover rounded border border-gray-200 cursor-pointer hover:opacity-80 transition-opacity" @click="openImage(previewUrls.photo_odp, '1. Foto ODP / Port')" />
                                                <a :href="previewUrls.photo_odp" download="Foto_ODP.jpg" class="text-[10px] text-blue-600 hover:underline text-center">Download</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-xs font-medium text-gray-500">2. Foto Instalasi di Rumah (Opsional)</label>
                                        <div class="flex items-start gap-3">
                                            <input type="file" @change="e => handleFileUpload('photo_installation', e)" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                            <div v-if="previewUrls.photo_installation" class="flex flex-col gap-1 shrink-0">
                                                <img :src="previewUrls.photo_installation" class="h-10 w-10 object-cover rounded border border-gray-200 cursor-pointer hover:opacity-80 transition-opacity" @click="openImage(previewUrls.photo_installation, '2. Foto Instalasi di Rumah')" />
                                                <a :href="previewUrls.photo_installation" download="Foto_Instalasi.jpg" class="text-[10px] text-blue-600 hover:underline text-center">Download</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-xs font-medium text-gray-500">3. Foto Posisi ONT (Opsional)</label>
                                        <div class="flex items-start gap-3">
                                            <input type="file" @change="e => handleFileUpload('photo_ont', e)" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                            <div v-if="previewUrls.photo_ont" class="flex flex-col gap-1 shrink-0">
                                                <img :src="previewUrls.photo_ont" class="h-10 w-10 object-cover rounded border border-gray-200 cursor-pointer hover:opacity-80 transition-opacity" @click="openImage(previewUrls.photo_ont, '3. Foto Posisi ONT')" />
                                                <a :href="previewUrls.photo_ont" download="Foto_ONT.jpg" class="text-[10px] text-blue-600 hover:underline text-center">Download</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-xs font-medium text-gray-500">4. Foto Selfie Pelanggan (Opsional)</label>
                                        <div class="flex items-start gap-3">
                                            <input type="file" @change="e => handleFileUpload('photo_customer', e)" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                            <div v-if="previewUrls.photo_customer" class="flex flex-col gap-1 shrink-0">
                                                <img :src="previewUrls.photo_customer" class="h-10 w-10 object-cover rounded border border-gray-200 cursor-pointer hover:opacity-80 transition-opacity" @click="openImage(previewUrls.photo_customer, '4. Foto Selfie Pelanggan')" />
                                                <a :href="previewUrls.photo_customer" download="Foto_Selfie.jpg" class="text-[10px] text-blue-600 hover:underline text-center">Download</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="block text-xs font-medium text-gray-500">5. Foto Redaman (Opsional)</label>
                                        <div class="flex items-start gap-3">
                                            <input type="file" @change="e => handleFileUpload('photo_redaman', e)" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                            <div v-if="previewUrls.photo_redaman" class="flex flex-col gap-1 shrink-0">
                                                <img :src="previewUrls.photo_redaman" class="h-10 w-10 object-cover rounded border border-gray-200 cursor-pointer hover:opacity-80 transition-opacity" @click="openImage(previewUrls.photo_redaman, '5. Foto Redaman')" />
                                                <a :href="previewUrls.photo_redaman" download="Foto_Redaman.jpg" class="text-[10px] text-blue-600 hover:underline text-center">Download</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Status Kelengkapan & Validasi -->
                            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 mt-6">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Status Form Laporan</h4>
                                    <span :class="['px-3 py-1 rounded-full text-xs font-bold', isInstallationValid ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700']">
                                        {{ isInstallationValid ? '🟢 SIAP DISELESAIKAN' : '🟠 DATA BELUM LENGKAP' }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs mb-4">
                                    <div :class="['flex items-center gap-1.5', isJamMulaiValid ? 'text-green-600' : 'text-gray-400']"><span v-if="isJamMulaiValid">✓</span><span v-else>○</span> Jam Mulai</div>
                                    <div :class="['flex items-center gap-1.5', isMaterialValid ? 'text-green-600' : 'text-gray-400']"><span v-if="isMaterialValid">✓</span><span v-else>○</span> Material</div>
                                    <div :class="['flex items-center gap-1.5', isOdpValid ? 'text-green-600' : 'text-gray-400']"><span v-if="isOdpValid">✓</span><span v-else>○</span> ODP</div>
                                    <div :class="['flex items-center gap-1.5', isPortValid ? 'text-green-600' : 'text-gray-400']"><span v-if="isPortValid">✓</span><span v-else>○</span> Port ODP</div>
                                    <div :class="['flex items-center gap-1.5', isRedamanValid ? 'text-green-600' : 'text-gray-400']"><span v-if="isRedamanValid">✓</span><span v-else>○</span> Redaman</div>
                                    <div :class="['flex items-center gap-1.5', isFotoValid ? 'text-green-600' : 'text-gray-400']"><span v-if="isFotoValid">✓</span><span v-else>○</span> Dokumentasi</div>
                                </div>
                                
                                <div v-if="!isInstallationValid && validationErrors.length > 0" class="bg-red-50 text-red-600 p-3 rounded-lg border border-red-100 text-xs">
                                    <p class="font-bold mb-1 flex items-center gap-1"><span>❌</span> Lengkapi data berikut sebelum selesai:</p>
                                    <ul class="list-disc pl-5 space-y-0.5">
                                        <li v-for="err in validationErrors" :key="err">{{ err }}</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Waktu Selesai -->
                            <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100 mt-4">
                                <p class="text-xs font-semibold text-blue-800 uppercase tracking-wider mb-3">3. Jam Selesai Pekerjaan</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                                    <div>
                                        <div class="flex justify-between items-center mb-1">
                                            <label class="block text-xs font-medium text-gray-500">Jam Selesai</label>
                                            <button type="button" @click="handleSelesaiSekarang" :disabled="!isInstallationValid || !!ontForm.end_time" :class="['text-[10px] px-3 py-1 rounded font-bold transition-colors shadow-sm', !isInstallationValid ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : (ontForm.end_time ? 'bg-gray-400 text-white cursor-not-allowed' : 'bg-emerald-600 text-white hover:bg-emerald-700')]">SELESAI SEKARANG</button>
                                        </div>
                                        <input v-model="ontForm.end_time" type="time" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-600 focus:ring-0 cursor-not-allowed" :required="isInstallingHardware" readonly />
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

                            <div v-show="isInstallingHardware" class="flex justify-end pt-4 border-t border-gray-200 mt-6 flex-col items-end gap-3">
                                <div v-if="Object.keys(ontForm.errors).length > 0" class="w-full bg-red-50 text-red-600 p-3 rounded-lg border border-red-200 text-sm">
                                    <p class="font-bold mb-1">Gagal menyimpan laporan:</p>
                                    <ul class="list-disc pl-5">
                                        <li v-for="(error, field) in ontForm.errors" :key="field">{{ error }}</li>
                                    </ul>
                                </div>
                                <button type="button" @click="confirmSubmit" :disabled="!isReadyToSubmit || ontForm.processing" :class="['btn-primary w-full md:w-auto text-sm py-2 px-6 shadow-md transition-all', (!isReadyToSubmit || ontForm.processing) ? 'opacity-50 cursor-not-allowed bg-gray-400 hover:bg-gray-400 shadow-none' : 'hover:shadow-lg']">
                                    {{ ontForm.processing ? 'Mengirim...' : 'Kirim Laporan & Selesaikan Instalasi' }}
                                </button>
                            </div>

                        </form>
                    </div>

                    </div>

        </div> <!-- Close Grid -->
        <!-- Audit Modal -->
        <div v-if="showAuditModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto overflow-hidden animate-fade-in-up">
                <div class="p-5 border-b border-gray-200 flex items-center justify-between bg-gradient-to-r from-blue-50 to-blue-100/50">
                    <h3 class="text-lg font-bold text-blue-900 flex items-center gap-2">
                        <span class="text-2xl">📋</span> Audit Instalasi
                    </h3>
                    <button @click="showAuditModal = false" class="text-gray-500 hover:text-gray-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitAudit">
                    <div class="p-6 space-y-5">
                        <div class="bg-blue-50 text-blue-800 p-4 rounded-xl text-sm mb-4 border border-blue-200 shadow-sm flex gap-3">
                            <svg class="w-6 h-6 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p>Pastikan Anda sudah mengecek semua data instalasi, hasil foto, dan nilai redaman yang dilaporkan teknisi.</p>
                        </div>
                        
                        <!-- Ringkasan Data ONT -->
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Detail Data ONT & Pelanggan</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3 gap-x-4 text-sm">
                                <div>
                                    <span class="text-gray-500 block text-[10px] uppercase font-semibold">Nama Pelanggan</span>
                                    <span class="font-bold text-gray-900">{{ customer.name }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 block text-[10px] uppercase font-semibold">Area/Wilayah</span>
                                    <span class="font-bold text-gray-900">{{ customer.area || '-' }}</span>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-gray-500 block text-[10px] uppercase font-semibold">Paket Berlangganan</span>
                                    <span class="font-bold text-gray-900">{{ customer.package?.name || '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 block text-[10px] uppercase font-semibold">Nama ODP</span>
                                    <span class="font-bold text-gray-900">{{ customer.ont?.odp?.name || '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 block text-[10px] uppercase font-semibold">Port ODP</span>
                                    <span class="font-bold text-gray-900">Port {{ customer.ont?.port_number || '-' }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan Audit</label>
                            <textarea v-model="auditForm.notes" rows="4" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition-all" placeholder="Tuliskan keterangan atau hasil pemeriksaan audit di sini..."></textarea>
                        </div>
                    </div>
                    <div class="p-5 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 rounded-b-2xl">
                        <button type="button" @click="showAuditModal = false" class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors shadow-sm">Batal</button>
                        <button type="submit" :disabled="auditForm.processing" class="px-6 py-2.5 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg text-sm font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                            <svg v-if="auditForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            {{ auditForm.processing ? 'Memproses...' : 'Setujui & Selesaikan Audit' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Activation Modal -->
        <div v-if="showActivationModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto overflow-hidden animate-fade-in-up">
                <div class="p-5 border-b border-gray-200 flex items-center justify-between bg-gradient-to-r from-amber-50 to-amber-100/50">
                    <h3 class="text-lg font-bold text-amber-900 flex items-center gap-2">
                        <span class="text-2xl">⚡</span> Aktivasi Pelanggan
                    </h3>
                    <button @click="showActivationModal = false" class="text-gray-500 hover:text-gray-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitActivation">
                    <div class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
                        <div class="bg-amber-50 text-amber-800 p-4 rounded-xl text-sm mb-4 border border-amber-200 shadow-sm flex gap-3">
                            <svg class="w-6 h-6 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p>Pastikan semua data instalasi dan hasil audit foto sudah benar sebelum mengaktifkan pelanggan ini.</p>
                        </div>
                        
                        <!-- Ringkasan Data ONT -->
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Detail Data ONT & Pelanggan</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3 gap-x-4 text-sm">
                                <div>
                                    <span class="text-gray-500 block text-[10px] uppercase font-semibold">Nama Pelanggan</span>
                                    <span class="font-bold text-gray-900">{{ customer.name }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 block text-[10px] uppercase font-semibold">Area/Wilayah</span>
                                    <span class="font-bold text-gray-900">{{ customer.area || '-' }}</span>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-gray-500 block text-[10px] uppercase font-semibold">Paket Berlangganan</span>
                                    <span class="font-bold text-gray-900">{{ customer.package?.name || '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 block text-[10px] uppercase font-semibold">Nama ODP</span>
                                    <span class="font-bold text-gray-900">{{ customer.ont?.odp?.name || '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 block text-[10px] uppercase font-semibold">Port ODP</span>
                                    <span class="font-bold text-gray-900">Port {{ customer.ont?.port_number || '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Status Layanan Pelanggan -->
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center justify-between">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Status Layanan Pelanggan <span class="text-red-500">*</span>
                                </span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                <label :class="['flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all', activationForm.service_status === 'berbayar' ? 'border-amber-500 bg-amber-50/50 ring-1 ring-amber-500' : 'border-gray-200 bg-white hover:border-gray-300']">
                                    <div class="flex items-center gap-2">
                                        <div :class="['w-4 h-4 rounded-full border flex items-center justify-center shrink-0', activationForm.service_status === 'berbayar' ? 'border-amber-500 bg-amber-500' : 'border-gray-300']">
                                            <div v-if="activationForm.service_status === 'berbayar'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                        </div>
                                        <div>
                                            <span class="text-xs font-bold text-gray-900 block">Berbayar</span>
                                            <span class="text-[10px] text-gray-500">Tarif paket rutin</span>
                                        </div>
                                    </div>
                                    <input type="radio" value="berbayar" v-model="activationForm.service_status" class="sr-only">
                                </label>
                                
                                <label :class="['flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all', activationForm.service_status === 'gratis' ? 'border-emerald-500 bg-emerald-50/50 ring-1 ring-emerald-500' : 'border-gray-200 bg-white hover:border-gray-300']">
                                    <div class="flex items-center gap-2">
                                        <div :class="['w-4 h-4 rounded-full border flex items-center justify-center shrink-0', activationForm.service_status === 'gratis' ? 'border-emerald-500 bg-emerald-500' : 'border-gray-300']">
                                            <div v-if="activationForm.service_status === 'gratis'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                        </div>
                                        <div>
                                            <span class="text-xs font-bold text-gray-900 block">🎁 Gratis</span>
                                            <span class="text-[10px] text-gray-500">CSR / Fasilitas</span>
                                        </div>
                                    </div>
                                    <input type="radio" value="gratis" v-model="activationForm.service_status" class="sr-only">
                                </label>

                                <label :class="['flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all', activationForm.service_status === 'free_home' ? 'border-amber-500 bg-amber-50/50 ring-1 ring-amber-500' : 'border-gray-200 bg-white hover:border-gray-300']">
                                    <div class="flex items-center gap-2">
                                        <div :class="['w-4 h-4 rounded-full border flex items-center justify-center shrink-0', activationForm.service_status === 'free_home' ? 'border-amber-500 bg-amber-500' : 'border-gray-300']">
                                            <div v-if="activationForm.service_status === 'free_home'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                        </div>
                                        <div>
                                            <span class="text-xs font-bold text-gray-900 block">🏠 Free Home</span>
                                            <span class="text-[10px] text-gray-500">Voucher Rumahan</span>
                                        </div>
                                    </div>
                                    <input type="radio" value="free_home" v-model="activationForm.service_status" class="sr-only">
                                </label>
                            </div>

                            <!-- Banner Free Home Alert -->
                            <div v-if="activationForm.service_status === 'free_home'" class="p-3 bg-amber-50/80 border border-amber-200 rounded-xl flex items-start gap-2.5 animate-fade-in text-xs text-amber-900">
                                <span class="text-base shrink-0">🏠</span>
                                <div class="leading-relaxed">
                                    <p class="font-bold">Mode Pelanggan Free Home (Voucher Rumahan ONT)</p>
                                    <p class="text-[11px] text-amber-700 mt-0.5">
                                        Aktivasi ini <strong>tidak akan menerbitkan invoice billing apapun</strong> dan <strong>melewati sinkronisasi RADIUS PPPoE</strong>. Layanan menggunakan voucher ONT rumahan secara stabil.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Siklus Penagihan Invoice -->
                        <div v-if="activationForm.service_status !== 'free_home'" class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    Siklus Penagihan Invoice <span class="text-red-500">*</span>
                                </label>
                                <span class="text-[11px] text-amber-700 bg-amber-100/70 px-2 py-0.5 rounded-md font-semibold">
                                    Default: {{ formatBillingName(defaultBillingType) }}
                                </span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label :class="['relative flex flex-col p-3 rounded-xl border cursor-pointer transition-all', activationForm.billing_type === 'prabayar' ? 'border-amber-500 bg-amber-50/50 ring-1 ring-amber-500' : 'border-gray-200 bg-white hover:border-gray-300']">
                                    <input type="radio" value="prabayar" v-model="activationForm.billing_type" class="sr-only">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-bold" :class="activationForm.billing_type === 'prabayar' ? 'text-amber-900' : 'text-gray-900'">Prabayar</span>
                                        <div :class="['w-4 h-4 rounded-full border flex items-center justify-center', activationForm.billing_type === 'prabayar' ? 'border-amber-500 bg-amber-500' : 'border-gray-300']">
                                            <div v-if="activationForm.billing_type === 'prabayar'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                        </div>
                                    </div>
                                    <span class="text-[10px] text-gray-500">Bayar dulu baru pakai (Full 1 bulan di muka)</span>
                                </label>
                                
                                <label :class="['relative flex flex-col p-3 rounded-xl border cursor-pointer transition-all', activationForm.billing_type === 'pascabayar' ? 'border-amber-500 bg-amber-50/50 ring-1 ring-amber-500' : 'border-gray-200 bg-white hover:border-gray-300']">
                                    <input type="radio" value="pascabayar" v-model="activationForm.billing_type" class="sr-only">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-bold" :class="activationForm.billing_type === 'pascabayar' ? 'text-amber-900' : 'text-gray-900'">Pasca Bayar</span>
                                        <div :class="['w-4 h-4 rounded-full border flex items-center justify-center', activationForm.billing_type === 'pascabayar' ? 'border-amber-500 bg-amber-500' : 'border-gray-300']">
                                            <div v-if="activationForm.billing_type === 'pascabayar'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                        </div>
                                    </div>
                                    <span class="text-[10px] text-gray-500">Pakai dulu baru bayar (Postpaid)</span>
                                </label>
                            </div>

                            <!-- Pilihan Prorata saat Pascabayar -->
                            <div v-if="activationForm.billing_type === 'pascabayar'" class="p-3.5 bg-amber-50/70 border border-amber-200 rounded-xl space-y-2 animate-fade-in">
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center gap-2.5 cursor-pointer">
                                        <input type="checkbox" v-model="activationForm.is_prorata" class="w-4 h-4 text-amber-600 rounded border-gray-300 focus:ring-amber-500">
                                        <span class="text-xs font-bold text-amber-950">
                                            Hitung Tagihan Pertama secara Prorata
                                        </span>
                                    </label>
                                    <span class="text-[10px] font-bold px-2 py-0.5 bg-amber-200/80 text-amber-800 rounded-full">
                                        Hanya 1 Kali
                                    </span>
                                </div>
                                <p class="text-[11px] text-amber-700 leading-relaxed pl-6.5">
                                    Tagihan pertama dihitung proporsional sisa hari aktif hingga tanggal cut-off. 
                                    <strong>Setelah bulan depan, tagihan otomatis normal pascabayar (1 bulan penuh).</strong>
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Aktivasi</label>
                            <input v-model="activationForm.activation_date" type="date" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 shadow-sm transition-all" required />
                        </div>

                        <!-- Simulasi Tagihan Invoice Pertama -->
                        <div v-if="invoiceSimulation" class="bg-gradient-to-br from-slate-50 via-amber-50/40 to-slate-50 p-4 rounded-xl border border-amber-200/90 shadow-sm space-y-3">
                            <div class="flex items-center justify-between border-b border-amber-100 pb-2.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-amber-600 text-white flex items-center justify-center text-sm shadow-sm font-bold">
                                        🧾
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Simulasi Tagihan Pertama</h4>
                                        <p class="text-[10px] text-gray-500">Estimasi tagihan invoice yang langsung terbentuk</p>
                                    </div>
                                </div>
                                <span :class="[
                                    'text-[10px] font-bold px-2.5 py-0.5 rounded-full border shadow-2xs',
                                    invoiceSimulation.isGratis ? 'bg-emerald-100 text-emerald-800 border-emerald-300' :
                                    (invoiceSimulation.isProrata ? 'bg-amber-100 text-amber-900 border-amber-300' : 
                                    (invoiceSimulation.billingType === 'prabayar' ? 'bg-purple-100 text-purple-900 border-purple-200' : 'bg-blue-100 text-blue-900 border-blue-200'))
                                ]">
                                    {{ invoiceSimulation.badgeLabel }}
                                </span>
                            </div>

                            <!-- Rincian Biaya -->
                            <div class="space-y-1.5 text-xs">
                                <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                    <span class="text-gray-600">Paket Berlangganan</span>
                                    <span class="font-semibold text-gray-900">{{ invoiceSimulation.packageName }} ({{ formatRupiah(invoiceSimulation.basePrice) }}/bln)</span>
                                </div>
                                
                                <div class="flex justify-between items-start py-1 border-b border-gray-100">
                                    <div>
                                        <span class="text-gray-600 block">Periode Pemakaian</span>
                                        <span v-if="invoiceSimulation.formulaDesc" class="text-[10px] text-amber-800 font-mono block mt-0.5">
                                            Rumus: {{ invoiceSimulation.formulaDesc }}
                                        </span>
                                    </div>
                                    <span class="font-semibold text-gray-900 text-right">{{ invoiceSimulation.periodText }}</span>
                                </div>

                                <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                    <span class="text-gray-600">Biaya Pokok</span>
                                    <span class="font-bold text-gray-900">{{ formatRupiah(invoiceSimulation.subtotal) }}</span>
                                </div>

                                <div v-if="invoiceSimulation.totalTaxPercent > 0 && !invoiceSimulation.isGratis" class="flex justify-between items-center py-1 border-b border-gray-100 text-amber-800">
                                    <span class="flex items-center gap-1">
                                        Pajak ({{ invoiceSimulation.taxBreakdownText }})
                                    </span>
                                    <span class="font-semibold">+ {{ formatRupiah(invoiceSimulation.taxAmount) }}</span>
                                </div>

                                <div v-if="invoiceSimulation.isGratis" class="flex justify-between items-center py-1 border-b border-gray-100 text-emerald-600 font-semibold">
                                    <span>Status Layanan</span>
                                    <span>Gratis (Rp 0)</span>
                                </div>

                                <!-- Total Tagihan Pertama -->
                                <div class="flex justify-between items-center pt-2">
                                    <div>
                                        <span class="text-xs font-bold text-gray-800 block">Total Tagihan Pertama</span>
                                        <span class="text-[10px] text-gray-500">Tanggal Isolir: <strong class="text-rose-600 font-semibold">{{ invoiceSimulation.dueDate }}</strong></span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-sm sm:text-base font-extrabold text-amber-800 bg-amber-100/70 px-3 py-1 rounded-lg border border-amber-300">
                                            {{ formatRupiah(invoiceSimulation.totalAmount) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Catatan Bulan Berikutnya jika Prorata -->
                            <div v-if="invoiceSimulation.isProrata" class="bg-amber-50/80 border border-amber-200/80 rounded-lg p-2.5 text-[11px] text-amber-800 flex items-start gap-2">
                                <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <div class="leading-relaxed">
                                    Prorata hanya berlaku <strong>1 kali</strong> pada invoice pertama ini. Bulan berikutnya (<span class="font-semibold">{{ invoiceSimulation.nextBillingMonthName }}</span>), tagihan otomatis normal pascabayar penuh sebesar <strong class="text-amber-950">{{ formatRupiah(invoiceSimulation.nextMonthNormalTotal) }}</strong> (terbit setiap tgl {{ invoiceSimulation.issueDateSetting }}).
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-bold text-gray-700 border-l-4 border-amber-500 pl-2">Data Konfigurasi ONT</h4>
                            <div class="flex gap-2">
                                <button v-if="!isEditingOnt" type="button" @click="isEditingOnt = true" class="text-xs font-semibold px-3 py-1.5 rounded-md border transition-all bg-gray-100 text-gray-600 border-gray-200 hover:bg-gray-200">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        Edit Data
                                    </span>
                                </button>
                                <template v-else>
                                    <button type="button" @click="isEditingOnt = false" class="text-xs font-semibold px-3 py-1.5 rounded-md border transition-all bg-gray-100 text-gray-600 border-gray-200 hover:bg-gray-200">
                                        Batal
                                    </button>
                                    <button type="button" @click="saveOntData" :disabled="isSavingOnt" class="text-xs font-semibold px-3 py-1.5 rounded-md border transition-all" :class="isSavingOnt ? 'bg-emerald-50 text-emerald-400 border-emerald-100 cursor-not-allowed' : 'bg-emerald-100 text-emerald-700 border-emerald-200 hover:bg-emerald-200'">
                                        <span class="flex items-center gap-1">
                                            <svg v-if="isSavingOnt" class="animate-spin w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            {{ isSavingOnt ? 'Menyimpan...' : 'Simpan' }}
                                        </span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PPPoE/Member Username</label>
                                <input v-model="activationForm.pppoe_user" type="text" class="w-full border rounded-lg px-4 py-2.5 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white border-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500'" placeholder="user@isp" :readonly="!isEditingOnt" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PPPoE/Member Password</label>
                                <input v-model="activationForm.pppoe_password" type="text" class="w-full border rounded-lg px-4 py-2.5 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white border-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500'" placeholder="***" :readonly="!isEditingOnt" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mode Akses (Catatan)</label>
                                <select v-model="activationForm.access_mode" class="w-full border rounded-lg px-4 py-2.5 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white border-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500'" :disabled="!isEditingOnt">
                                    <option value="PPPOE">PPPoE</option>
                                    <option value="STATIC">Static IP</option>
                                    <option value="DHCP">DHCP / Dynamic</option>
                                    <option value="HOTSPOT">Hotspot</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">VLAN Mode</label>
                                <select v-model="activationForm.vlan_mode" class="w-full border rounded-lg px-4 py-2.5 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white border-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500'" :disabled="!isEditingOnt">
                                    <option value="">-- Pilih --</option>
                                    <option value="Route">Route</option>
                                    <option value="Bridge">Bridge</option>
                                    <option value="VLAN">VLAN (Tagged)</option>
                                    <option value="Untagged">Untagged</option>
                                </select>
                            </div>
                            
                            <div v-if="activationForm.vlan_mode === 'VLAN'" class="col-span-full">
                                <label class="block text-sm font-medium text-gray-700 mb-1">No VLAN ID</label>
                                <input v-model="activationForm.vlan_id" type="text" class="w-full border rounded-lg px-4 py-2.5 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white border-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500'" placeholder="Misal: 100" :readonly="!isEditingOnt" />
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-4">
                            <div class="flex items-center gap-2 mb-2">
                                <input type="checkbox" id="freeHotspot" v-model="activationForm.free_hotspot" class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500" :disabled="!isEditingOnt" />
                                <label for="freeHotspot" class="text-sm font-bold text-gray-700">Gratis 1 User Hotspot</label>
                            </div>
                            
                            <div v-if="activationForm.free_hotspot" class="space-y-4 animate-fade-in-up">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Username Hotspot</label>
                                        <input v-model="activationForm.hotspot_user" type="text" class="w-full border rounded-lg px-4 py-2.5 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white border-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500'" placeholder="username_hotspot" :readonly="!isEditingOnt" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Password Hotspot</label>
                                        <input v-model="activationForm.hotspot_password" type="text" class="w-full border rounded-lg px-4 py-2.5 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white border-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500'" placeholder="***" :readonly="!isEditingOnt" />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">VLAN ID Hotspot (Opsional)</label>
                                    <input v-model="activationForm.hotspot_vlan_id" type="text" class="w-full border rounded-lg px-4 py-2.5 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white border-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500'" placeholder="Misal: 200" :readonly="!isEditingOnt" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-4">
                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Akses Login ONT</h4>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">IP Login ONT</label>
                                <input v-model="activationForm.ip_login" type="text" class="w-full border rounded-lg px-4 py-2.5 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white border-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500'" placeholder="192.168.1.1" :readonly="!isEditingOnt" />
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Username ONT</label>
                                    <input v-model="activationForm.login_user" type="text" class="w-full border rounded-lg px-4 py-2.5 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white border-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500'" placeholder="admin" :readonly="!isEditingOnt" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Password ONT</label>
                                    <input v-model="activationForm.login_password" type="text" class="w-full border rounded-lg px-4 py-2.5 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white border-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500'" placeholder="admin" :readonly="!isEditingOnt" />
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pengaturan Pajak Pelanggan -->
                        <div class="bg-white border rounded-xl p-4 shadow-sm space-y-3 transition-all"
                            :class="activationForm.enable_tax ? 'border-amber-200 ring-1 ring-amber-100 bg-amber-50/20' : 'border-gray-200 bg-gray-50/50'">
                            
                            <div class="flex items-center justify-between">
                                <label for="show_enable_tax" class="flex items-center gap-2.5 cursor-pointer select-none">
                                    <input 
                                        type="checkbox" 
                                        id="show_enable_tax" 
                                        v-model="activationForm.enable_tax" 
                                        class="w-4 h-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500 cursor-pointer transition-colors"
                                    />
                                    <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        Pengaturan Pajak Pelanggan
                                    </span>
                                </label>
                                
                                <span 
                                    class="text-[10px] font-bold px-2 py-0.5 rounded-full border transition-all"
                                    :class="activationForm.enable_tax 
                                        ? 'bg-amber-100 text-amber-700 border-amber-200' 
                                        : 'bg-gray-100 text-gray-500 border-gray-200'"
                                >
                                    {{ activationForm.enable_tax ? 'PPN Aktif (' + (activationForm.tax_ppn || 11) + '%)' : 'Bebas PPN (Nonaktif)' }}
                                </span>
                            </div>

                            <!-- Form Input PPN (Muncul jika enable_tax aktif) -->
                            <div v-if="activationForm.enable_tax" class="pt-2 border-t border-amber-100/60 space-y-2">
                                <p class="text-[11px] text-gray-500">Pajak Pertambahan Nilai (PPN) yang ditambahkan ke invoice tagihan pelanggan:</p>
                                <div class="max-w-xs">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">PPN (%)</label>
                                    <div class="relative">
                                        <input v-model="activationForm.tax_ppn" type="number" step="0.01" min="0" max="100" class="w-full bg-white border border-gray-200 rounded-lg pr-7 pl-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 shadow-sm transition-all font-semibold text-amber-700" placeholder="11" />
                                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs font-semibold">%</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Informasi ketika Bebas Pajak -->
                            <div v-else class="pt-1.5 flex items-center gap-2 text-xs text-emerald-700 bg-emerald-50/70 p-2.5 rounded-lg border border-emerald-100">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Pelanggan ini <strong>bebas pajak</strong> (invoice tidak dikenakan PPN / 0%).</span>
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
                <div class="relative max-w-4xl w-full max-h-[90vh] overflow-y-auto h-full flex flex-col items-center justify-center animate-fade-in-up">
                    <button @click="previewImage = null" class="absolute top-4 right-4 text-white hover:text-red-500 bg-white/20 hover:bg-white/30 p-2 rounded-full z-10 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                    <img :src="previewImage.src" class="max-h-[85vh] max-w-full object-contain rounded-lg shadow-2xl" />
                    <p class="mt-4 text-white font-medium text-lg">{{ previewImage.label }}</p>
                </div>
            </div>
        </Teleport>
        <!-- Confirmation Modal -->
        <div v-if="showConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-hidden animate-fade-in-up">
                <div class="p-5 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-blue-100/50">
                    <h3 class="text-lg font-bold text-blue-900 flex items-center gap-2">
                        <span>Konfirmasi Selesai Instalasi</span>
                    </h3>
                </div>
                <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto text-sm">
                    <p class="font-medium text-gray-700">Apakah Anda yakin ingin menyelesaikan instalasi?</p>
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 space-y-2">
                        <div class="grid grid-cols-2 gap-2">
                            <div class="text-gray-500 font-medium">Pelanggan:</div>
                            <div class="font-bold">{{ customer.name }}</div>
                            
                            <div class="text-gray-500 font-medium">ODP:</div>
                            <div class="font-bold">{{ selectedOdp?.name }}</div>
                            
                            <div class="text-gray-500 font-medium">Port:</div>
                            <div class="font-bold">Port {{ ontForm.port_number }}</div>
                            
                            <div class="text-gray-500 font-medium">ONT:</div>
                            <div class="font-bold">{{ getInstalledOntName() }}</div>
                            
                            <div class="text-gray-500 font-medium">Jam Mulai:</div>
                            <div class="font-bold">{{ ontForm.start_time }}</div>
                            
                            <div class="text-gray-500 font-medium">Jam Selesai:</div>
                            <div class="font-bold">{{ ontForm.end_time }}</div>
                            
                            <div class="text-gray-500 font-medium">Total Durasi:</div>
                            <div class="font-bold text-blue-600">{{ durationText }}</div>
                            
                            <div class="text-gray-500 font-medium">Material Terpasang:</div>
                            <div class="font-bold text-emerald-600">{{ materialHandoverSummary.installed.length }} item terpasang</div>
                            
                            <template v-if="materialHandoverSummary.hasReturned">
                                <div class="text-amber-800 font-medium">Retur ke Gudang:</div>
                                <div class="font-bold text-amber-800">
                                    {{ materialHandoverSummary.returned.length }} item sisa dikembalikan
                                    <span class="block text-[11px] font-normal text-amber-700 mt-0.5">
                                        ({{ materialHandoverSummary.returned.map(r => `${r.name}: ${r.qty} ${r.unit}`).join(', ') }})
                                    </span>
                                </div>
                            </template>
                            
                            <div class="text-gray-500 font-medium">Dokumentasi:</div>
                            <div class="font-bold text-green-600">Lengkap ✓</div>
                        </div>
                    </div>
                </div>
                <div class="p-5 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                    <button type="button" @click="showConfirmModal = false" :disabled="ontForm.processing" class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-900 bg-white border border-gray-300 rounded-lg shadow-sm">Batal</button>
                    <button type="button" @click="executeSubmit" :disabled="ontForm.processing" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-bold shadow-md transition-all flex items-center gap-2">
                        {{ ontForm.processing ? 'Mengirim...' : 'Ya, Kirim & Selesaikan' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Edit Active Config Modal -->
        <div v-if="showEditActiveModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto overflow-hidden animate-fade-in-up">
                <div class="p-5 border-b border-gray-200 flex items-center justify-between bg-gradient-to-r from-amber-50 to-amber-100/50">
                    <h3 class="text-lg font-bold text-amber-900 flex items-center gap-2">
                        <span class="text-2xl">⚙️</span> Edit Konfigurasi ONT
                    </h3>
                    <button @click="showEditActiveModal = false" class="text-gray-500 hover:text-gray-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitEditActive">
                    <div class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
                        <div class="bg-amber-50 text-amber-800 p-4 rounded-xl text-sm mb-4 border border-amber-200 shadow-sm flex gap-3">
                            <svg class="w-6 h-6 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p>Perbarui data konfigurasi akses ONT untuk pelanggan ini.</p>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PPPoE/Member Username</label>
                                <input v-model="activeConfigForm.pppoe_user" type="text" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all" placeholder="user@isp" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PPPoE/Member Password</label>
                                <input v-model="activeConfigForm.pppoe_password" type="text" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all" placeholder="***" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mode Akses (Catatan)</label>
                                <select v-model="activeConfigForm.access_mode" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                                    <option value="PPPOE">PPPoE</option>
                                    <option value="STATIC">Static IP</option>
                                    <option value="DHCP">DHCP / Dynamic</option>
                                    <option value="HOTSPOT">Hotspot</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">VLAN Mode</label>
                                <select v-model="activeConfigForm.vlan_mode" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                                    <option value="">-- Pilih --</option>
                                    <option value="Route">Route</option>
                                    <option value="Bridge">Bridge</option>
                                    <option value="VLAN">VLAN (Tagged)</option>
                                    <option value="Untagged">Untagged</option>
                                </select>
                            </div>
                            
                            <div v-if="activeConfigForm.vlan_mode === 'VLAN'" class="col-span-full">
                                <label class="block text-sm font-medium text-gray-700 mb-1">No VLAN ID</label>
                                <input v-model="activeConfigForm.vlan_id" type="text" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all" placeholder="Misal: 100" />
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-4">
                            <div class="flex items-center gap-2 mb-2">
                                <input type="checkbox" id="editFreeHotspot" v-model="activeConfigForm.free_hotspot" class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500" />
                                <label for="editFreeHotspot" class="text-sm font-bold text-gray-700">Gratis 1 User Hotspot</label>
                            </div>
                            
                            <div v-if="activeConfigForm.free_hotspot" class="space-y-4 animate-fade-in-up">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Username Hotspot</label>
                                        <input v-model="activeConfigForm.hotspot_user" type="text" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all" placeholder="username_hotspot" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Password Hotspot</label>
                                        <input v-model="activeConfigForm.hotspot_password" type="text" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all" placeholder="***" />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">VLAN ID Hotspot (Opsional)</label>
                                    <input v-model="activeConfigForm.hotspot_vlan_id" type="text" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all" placeholder="Misal: 200" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-4">
                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Akses Login ONT</h4>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">IP Login ONT</label>
                                <input v-model="activeConfigForm.ip_login" type="text" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all" placeholder="192.168.1.1" />
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Username ONT</label>
                                    <input v-model="activeConfigForm.login_user" type="text" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all" placeholder="admin" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Password ONT</label>
                                    <input v-model="activeConfigForm.login_password" type="text" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all" placeholder="admin" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-5 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 rounded-b-2xl">
                        <button type="button" @click="showEditActiveModal = false" class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors shadow-sm">Batal</button>
                        <button type="submit" :disabled="activeConfigForm.processing" class="px-6 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white rounded-lg text-sm font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                            <svg v-if="activeConfigForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            {{ activeConfigForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Modal Stop Permanen -->
        <Teleport to="body">
            <div v-if="showTerminateModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm cursor-pointer" @click="showTerminateModal = false"></div>
                <div class="relative bg-white border border-rose-200 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-fade-in-up">
                    <div class="p-5 border-b border-rose-100 flex items-center justify-between bg-rose-50/50">
                        <h3 class="text-base font-bold text-rose-900 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center text-sm font-bold">⛔</span> Stop Permanen Pelanggan
                        </h3>
                        <button @click="showTerminateModal = false" class="text-gray-400 hover:text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form @submit.prevent="submitTerminate">
                        <div class="p-6 space-y-4 text-xs">
                            <div class="bg-rose-50 text-rose-800 p-3.5 rounded-xl border border-rose-200 shadow-sm leading-relaxed">
                                Pelanggan <strong>{{ customer.name }}</strong> ({{ customer.customer_code }}) akan dipindahkan ke menu <strong>Stop Permanen</strong> dan dinonaktifkan dari sistem serta RADIUS.
                            </div>

                            <!-- Peringatan Auto-Pinalti Kontrak < 4 Bulan -->
                            <div v-if="customerActiveTenure && customerActiveTenure.isUnder4" class="p-3.5 rounded-xl bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-300 text-amber-900 shadow-xs space-y-2 animate-fade-in">
                                <div class="flex items-center gap-1.5 font-bold text-xs text-amber-900">
                                    <span class="text-base">⚠️</span>
                                    <span>Peringatan Kontrak: Masa Aktif Baru {{ customerActiveTenure.text }} (&lt; 4 Bulan)</span>
                                </div>
                                <p class="text-[11px] text-amber-800 leading-relaxed">
                                    Pelanggan berhenti sebelum masa minimal komitmen <strong>4 bulan</strong>.
                                </p>
                                <!-- Pemotongan Fee Booking Afiliasi Otomatis -->
                                <div v-if="customerActiveTenure.hasSales" class="p-2.5 rounded-lg bg-white/90 border border-amber-200 text-[11px] space-y-1">
                                    <div class="font-bold flex items-center justify-between text-rose-800">
                                        <span class="flex items-center gap-1">✂️ Pemotongan Fee Booking Otomatis:</span>
                                        <span class="bg-rose-100 text-rose-700 px-1.5 py-0.2 rounded text-[10px] uppercase font-black">AUTO-POTONG</span>
                                    </div>
                                    <p class="text-slate-600">
                                        Fee booking yang pernah diterima oleh Sales/Afiliator <strong class="text-slate-900">{{ customerActiveTenure.salesName }}</strong> akan <strong>otomatis dipotong</strong> (dari slip gaji payroll jika karyawan, atau saldo dompet jika mitra).
                                    </p>
                                </div>
                                <div v-else class="text-[10px] text-slate-500 italic">
                                    (Pelanggan mendaftar tanpa sales afiliasi, tidak ada fee booking yang dipotong).
                                </div>

                                <!-- Pemotongan Fee Survey Teknisi Otomatis -->
                                <div v-if="customerActiveTenure.hasSurveyor" class="p-2.5 rounded-lg bg-white/90 border border-amber-200 text-[11px] space-y-1">
                                    <div class="font-bold flex items-center justify-between text-rose-800">
                                        <span class="flex items-center gap-1">✂️ Pemotongan Fee Survey Otomatis:</span>
                                        <span class="bg-rose-100 text-rose-700 px-1.5 py-0.2 rounded text-[10px] uppercase font-black">AUTO-POTONG</span>
                                    </div>
                                    <p class="text-slate-600">
                                        Fee survey yang pernah diterima oleh Teknisi Surveyor <strong class="text-slate-900">{{ customerActiveTenure.surveyorName }}</strong> akan <strong>otomatis dipotong</strong> (dari slip gaji payroll jika karyawan, atau saldo dompet jika mitra).
                                    </p>
                                </div>
                                <div v-else class="text-[10px] text-slate-500 italic">
                                    (Pelanggan tidak memiliki riwayat teknisi survey, tidak ada fee survey yang dipotong).
                                </div>
                            </div>

                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Tanggal Berhenti <span class="text-rose-500">*</span></label>
                                <input v-model="terminateForm.cancel_date" type="date" required class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-rose-500 focus:border-rose-500" />
                            </div>
                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Alasan Berhenti Berlangganan <span class="text-rose-500">*</span></label>
                                <select v-model="terminateForm.cancel_reason" required class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-rose-500 focus:border-rose-500 mb-2">
                                    <option value="">Pilih Alasan...</option>
                                    <option value="Pindah Rumah / Domisili">Pindah Rumah / Domisili</option>
                                    <option value="Kendala Finansial / Biaya">Kendala Finansial / Biaya</option>
                                    <option value="Kecepatan / Gangguan Berulang">Kecepatan / Gangguan Berulang</option>
                                    <option value="Beralih ke Provider Lain">Beralih ke Provider Lain</option>
                                    <option value="Tidak Lagi Membutuhkan Internet">Tidak Lagi Membutuhkan Internet</option>
                                    <option value="Lainnya">Alasan Lainnya</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Catatan Tambahan</label>
                                <textarea v-model="terminateForm.notes" rows="2" placeholder="Catatan opsional..." class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-rose-500 focus:border-rose-500"></textarea>
                            </div>
                        </div>
                        <div class="p-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-2 rounded-b-2xl">
                            <button type="button" @click="showTerminateModal = false" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-900 bg-white border border-gray-300 rounded-lg">Batal</button>
                            <button type="submit" :disabled="terminateForm.processing" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold transition-all disabled:opacity-50 flex items-center gap-1.5 shadow-sm">
                                <span>{{ terminateForm.processing ? 'Menyimpan...' : 'Stop Permanen Sekarang' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>

    <!-- Buat Akun Modal -->
    <div v-if="showAccountModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-fade-in-up">
            <div class="p-5 border-b border-gray-200 flex items-center justify-between bg-gradient-to-r from-indigo-50 to-indigo-100/50">
                <h3 class="text-lg font-bold text-indigo-900 flex items-center gap-2">
                    <span class="text-2xl">👤</span> Buat Akun Client Area
                </h3>
                <button @click="showAccountModal = false" class="text-gray-500 hover:text-gray-900">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form @submit.prevent="submitAccount">
                <div class="p-6 space-y-4">
                    <div class="bg-indigo-50 text-indigo-800 p-4 rounded-xl text-sm mb-4 border border-indigo-200 shadow-sm flex gap-3">
                        <svg class="w-6 h-6 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p>Buat akun ini agar pelanggan dapat login ke Client Area untuk mengecek tagihan dan laporan gangguan secara mandiri.</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email (Sebagai Username)</label>
                        <input v-model="accountForm.email" type="email" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" required />
                        <div v-if="accountForm.errors.email" class="text-xs text-red-500 mt-1">{{ accountForm.errors.email }}</div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input v-model="accountForm.password" type="password" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" required minlength="6" placeholder="Minimal 6 karakter" />
                        <div v-if="accountForm.errors.password" class="text-xs text-red-500 mt-1">{{ accountForm.errors.password }}</div>
                    </div>
                </div>
                <div class="p-5 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 rounded-b-2xl">
                    <button type="button" @click="showAccountModal = false" class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors shadow-sm">Batal</button>
                    <button type="submit" :disabled="accountForm.processing" class="px-6 py-2.5 bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white rounded-lg text-sm font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                        <svg v-if="accountForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        {{ accountForm.processing ? 'Menyimpan...' : 'Buat Akun' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Ubah Password Modal -->
    <div v-if="showResetPasswordModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-fade-in-up">
            <div class="p-5 border-b border-gray-200 flex items-center justify-between bg-gradient-to-r from-red-50 to-red-100/50">
                <h3 class="text-lg font-bold text-red-900 flex items-center gap-2">
                    <span class="text-2xl">🔑</span> Ubah Password Akun
                </h3>
                <button @click="showResetPasswordModal = false" class="text-gray-500 hover:text-gray-900">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form @submit.prevent="submitResetPassword">
                <div class="p-6 space-y-4">
                    <div class="bg-red-50 text-red-800 p-4 rounded-xl text-sm mb-4 border border-red-200 shadow-sm flex gap-3">
                        <svg class="w-6 h-6 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <p>Aksi ini akan merubah detail akun login pelanggan saat ini. Pastikan Anda memberikan username/password baru kepada pelanggan.</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Username (Email)</label>
                        <input v-model="resetPasswordForm.email" type="email" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all" placeholder="email@contoh.com" />
                        <div v-if="resetPasswordForm.errors.email" class="text-xs text-red-500 mt-1">{{ resetPasswordForm.errors.email }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                        <input v-model="resetPasswordForm.password" type="text" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all" minlength="6" placeholder="Kosongkan jika tidak ingin mengubah password (min. 6 karakter)" />
                        <div v-if="resetPasswordForm.errors.password" class="text-xs text-red-500 mt-1">{{ resetPasswordForm.errors.password }}</div>
                    </div>
                </div>
                <div class="p-5 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 rounded-b-2xl">
                    <button type="button" @click="showResetPasswordModal = false" class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors shadow-sm">Batal</button>
                    <button type="submit" :disabled="resetPasswordForm.processing" class="px-6 py-2.5 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-lg text-sm font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                        <svg v-if="resetPasswordForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        {{ resetPasswordForm.processing ? 'Menyimpan...' : 'Ubah Password' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
        <!-- Modal Pemakaian Aktual / RMA -->
        <div v-if="showInstallModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="font-bold text-gray-900 text-lg">
                        {{ installModalItem?.type === 'ONT' ? 'Konfirmasi ONT' : 'Pemakaian Aktual' }}
                    </h3>
                    <button @click="showInstallModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-4">
                        Barang: <span class="font-bold text-gray-900">{{ installModalItem?.name }}</span>
                    </p>
                    
                    <!-- Kebutuhan ONT -->
                    <div v-if="installModalItem?.type === 'ONT'" class="space-y-4">
                        <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 cursor-pointer hover:bg-blue-100 transition-colors" @click="installModalIsBroken = false">
                            <div class="flex items-center gap-3">
                                <input type="radio" v-model="installModalIsBroken" :value="false" class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                <div>
                                    <p class="font-bold text-blue-900 text-sm">ONT Berfungsi Normal</p>
                                    <p class="text-xs text-blue-700 mt-0.5">Berhasil dipasang di pelanggan.</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-red-50 border border-red-100 rounded-xl p-4 cursor-pointer hover:bg-red-100 transition-colors" @click="installModalIsBroken = true">
                            <div class="flex items-center gap-3">
                                <input type="radio" v-model="installModalIsBroken" :value="true" class="w-4 h-4 text-red-600 focus:ring-red-500 border-gray-300">
                                <div>
                                    <p class="font-bold text-red-900 text-sm">Tandai Rusak / RMA</p>
                                    <p class="text-xs text-red-700 mt-0.5">ONT bermasalah dan akan dikembalikan ke gudang.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kebutuhan Material -->
                    <div v-else class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Total Pemakaian Aktual</label>
                            <p class="text-xs text-gray-500 mb-2">Sisa material yang tidak dipakai otomatis akan dibuatkan formulir serah terima pengembalian ke Gudang.</p>
                            <div class="relative flex items-center">
                                <input type="number" v-model="installModalQty" min="0" step="0.01" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 text-lg font-bold pr-16">
                                <span class="absolute right-4 text-sm font-bold text-gray-500 uppercase">{{ installModalUnit }}</span>
                            </div>
                            <!-- Hitungan Real-time Sisa yang Dikembalikan -->
                            <div v-if="installModalReturnedCalc > 0" class="mt-2.5 p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-900 flex items-center justify-between">
                                <div class="flex items-center gap-1.5 font-semibold">
                                    <span>📦</span>
                                    <span>Sisa yang Dikembalikan ke Gudang:</span>
                                </div>
                                <span class="font-bold text-amber-950 bg-amber-200/80 px-2.5 py-0.5 rounded text-xs font-mono">
                                    {{ installModalReturnedCalc }} {{ installModalUnit }}
                                </span>
                            </div>
                            <div v-else class="mt-2.5 p-2 rounded-lg bg-emerald-50 border border-emerald-200 text-[11px] text-emerald-800 flex items-center gap-1.5">
                                <span>✓</span>
                                <span>Material habis terpakai penuh (Tidak ada sisa retur).</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                    <button @click="showInstallModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Batal</button>
                    <button @click="confirmInstall" class="px-4 py-2 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition-colors">Konfirmasi & Pasang</button>
                </div>
            </div>
        </div>
        <!-- Modal Ajukan Tambahan Material -->
        <div v-if="showRequestModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
            <div class="bg-white rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
                <div class="p-6 bg-blue-50 border-b border-blue-100 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold text-blue-900">Ajukan Tambahan Material</h3>
                        <p class="text-sm text-blue-700 mt-1">Request akan dikirim ke Admin Gudang</p>
                    </div>
                    <button @click="showRequestModal = false" class="text-blue-400 hover:text-blue-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <div class="p-6 overflow-y-auto flex-1">
                    <form @submit.prevent="submitMaterialRequest" class="space-y-4">
                        <div v-for="(item, index) in requestForm.items" :key="index" class="bg-gray-50 p-4 rounded-xl border border-gray-200 relative">
                            <button type="button" @click="removeRequestItem(index)" class="absolute top-2 right-2 text-red-400 hover:text-red-600 p-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Pilih Material</label>
                                    <select v-model="item.material_id" required class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">-- Pilih --</option>
                                        <option v-for="mat in materials" :key="mat.id" :value="mat.id">{{ mat.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Jumlah</label>
                                    <input type="number" v-model="item.quantity" min="0.1" step="0.1" required class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" @click="addRequestItem" class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Tambah Item Lain
                        </button>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Keterangan / Alasan (Opsional)</label>
                            <textarea v-model="requestForm.notes" rows="2" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Misal: Kabel awal kurang panjang karena rute memutar..."></textarea>
                        </div>
                    </form>
                </div>
                
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                    <button @click="showRequestModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Batal</button>
                    <button @click="submitMaterialRequest" :disabled="requestForm.processing" class="px-4 py-2 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition-colors disabled:opacity-50">Kirim Request</button>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- 1. MODAL EDIT BOOKING & LOKASI             -->
        <!-- ========================================== -->
        <div v-if="showEditBookingModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
            <div class="bg-white rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col max-h-[92vh]">
                <div class="p-5 bg-gradient-to-r from-blue-600 via-indigo-600 to-indigo-700 text-white flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-xl shrink-0">
                            📝
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-bold">Edit Pendaftaran Pelanggan (Booking)</h3>
                            <p class="text-xs text-blue-100">Formulir pendaftaran pelanggan baru & lokasi pemasangan</p>
                        </div>
                    </div>
                    <button type="button" @click="showEditBookingModal = false" class="text-white/80 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submitEditBooking" class="flex flex-col flex-1 overflow-hidden">
                    <div class="p-5 sm:p-6 overflow-y-auto space-y-5">
                        <!-- Section 1: Informasi Personal -->
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-4">
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                <span class="p-1 rounded bg-blue-100 text-blue-700">👤</span> Informasi Personal
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nama Lengkap Pelanggan <span class="text-red-500">*</span></label>
                                    <input v-model="bookingForm.name" type="text" required placeholder="Contoh: Budi Santoso" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">No. WhatsApp / HP <span class="text-red-500">*</span></label>
                                    <input v-model="bookingForm.phone" type="text" required placeholder="08123456789" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Email Pelanggan (Opsional)</label>
                                    <input v-model="bookingForm.email" type="email" placeholder="budi@example.com" :class="['w-full text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500', bookingForm.errors.email ? 'border-red-400 bg-red-50/20 ring-1 ring-red-300' : 'border-gray-300']" />
                                    <p v-if="bookingForm.errors.email" class="text-xs text-red-500 mt-1 font-medium flex items-center gap-1">
                                        <span>⚠️</span> {{ bookingForm.errors.email }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tanggal Booking / Registrasi</label>
                                    <input v-model="bookingForm.registration_date" type="date" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Sales / Referal</label>
                                    <div v-if="isSuperAdmin">
                                        <select v-model="bookingForm.sales_id" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">-- Tanpa Sales / Mandiri --</option>
                                            <option v-for="s in salesUsers" :key="s.id" :value="s.id">{{ s.name }} ({{ s.job_position || s.role || 'Sales' }})</option>
                                        </select>
                                    </div>
                                    <div v-else class="relative">
                                        <input 
                                            type="text" 
                                            :value="customer.sales?.name ? (customer.sales.name + (customer.sales.job_position || customer.sales.role ? ` (${customer.sales.job_position || customer.sales.role})` : '')) : '-- Tanpa Sales / Mandiri --'" 
                                            readonly 
                                            disabled 
                                            class="w-full text-sm bg-gray-100 border border-gray-200 rounded-lg px-3 py-2 font-medium text-gray-700 cursor-not-allowed select-none"
                                            title="Hanya Super Admin yang dapat mengubah Petugas Sales/Referral"
                                        />
                                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs text-gray-400 font-medium">
                                            🔒 Terkunci
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Lokasi Pemasangan & Koordinat -->
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-4">
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                <span class="p-1 rounded bg-emerald-100 text-emerald-700">📍</span> Lokasi Pemasangan & Titik Koordinat
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Area / Wilayah <span class="text-red-500">*</span></label>
                                    <select v-model="bookingForm.area_id" required class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">-- Pilih Area Wilayah --</option>
                                        <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Alamat Lengkap Pemasangan <span class="text-red-500">*</span></label>
                                    <textarea v-model="bookingForm.address" rows="2" required placeholder="Jl. Mawar No. 12, RT 01 / RW 02..." class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
                                </div>
                                <div class="sm:col-span-2">
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Titik Koordinat Lokasi (GPS)</label>
                                        <div class="flex gap-2">
                                            <button type="button" @click="getBookingLocation" class="text-xs text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1 bg-blue-50 hover:bg-blue-100 px-2.5 py-1 rounded-md transition-colors border border-blue-200">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                Auto GPS
                                            </button>
                                            <a v-if="bookingForm.latitude && bookingForm.longitude" :href="`/network-radar?lat=${bookingForm.latitude}&lng=${bookingForm.longitude}`" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1 rounded-md transition-colors border border-indigo-200">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                                Radar ODP
                                            </a>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-[10px] font-bold text-gray-400">LAT</span>
                                            <input v-model="bookingForm.latitude" type="text" placeholder="-6.200000" class="w-full pl-10 text-sm font-mono border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" />
                                        </div>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-[10px] font-bold text-gray-400">LNG</span>
                                            <input v-model="bookingForm.longitude" type="text" placeholder="106.816666" class="w-full pl-10 text-sm font-mono border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Detail Layanan & Catatan -->
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-4">
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                <span class="p-1 rounded bg-purple-100 text-purple-700">📦</span> Layanan & Catatan
                            </h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Paket Langganan Internet</label>
                                    <select v-model="bookingForm.package_id" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">-- Pilih Paket Internet --</option>
                                        <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">{{ pkg.name }} ({{ formatRupiah(pkg.price) }})</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Catatan Tambahan / Ancar-ancar Rumah</label>
                                    <textarea v-model="bookingForm.notes" rows="2" placeholder="Dekat masjid, pagar hitam, patokan lokasi..." class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 shrink-0">
                        <button type="button" @click="showEditBookingModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Batal</button>
                        <button type="submit" :disabled="bookingForm.processing" class="px-5 py-2 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-md transition-all disabled:opacity-50 flex items-center gap-2">
                            <svg v-if="bookingForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Simpan Perubahan Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- 2. MODAL EDIT JADWAL SURVEY                -->
        <!-- ========================================== -->
        <div v-if="showEditSurveyScheduleModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
            <div class="bg-white rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl flex flex-col max-h-[92vh]">
                <div class="p-5 bg-gradient-to-r from-indigo-600 via-indigo-700 to-purple-700 text-white flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-xl shrink-0">
                            📅
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-bold">Jadwalkan Survey Lapangan</h3>
                            <p class="text-xs text-indigo-100">Penugasan teknisi surveyor & jadwal kunjungan ODP</p>
                        </div>
                    </div>
                    <button type="button" @click="showEditSurveyScheduleModal = false" class="text-white/80 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submitEditSurveySchedule" class="flex flex-col flex-1 overflow-hidden">
                    <div class="bg-indigo-50/70 px-5 py-3 border-b border-indigo-100 flex flex-col gap-1 text-xs shrink-0">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 font-medium">Pelanggan:</span>
                            <span class="font-bold text-slate-800">{{ customer.name }} ({{ customer.customer_code }})</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 font-medium">Area / Alamat:</span>
                            <span class="font-semibold text-slate-700 truncate max-w-[280px]">{{ customer.area || '-' }} - {{ customer.address }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 font-medium">Paket Dipilih:</span>
                            <span class="font-semibold text-indigo-700">{{ customer.package?.name || '-' }}</span>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 overflow-y-auto space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Pilih Teknisi / Surveyor <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-1 gap-2 max-h-48 overflow-y-auto border border-gray-200 rounded-xl p-2 bg-slate-50">
                                <label v-for="t in technicians" :key="t.id" :class="['flex items-center justify-between p-2.5 rounded-lg border cursor-pointer transition-all', surveyScheduleForm.technician_id === t.id ? 'bg-indigo-50 border-indigo-300 ring-1 ring-indigo-500 text-indigo-950 font-bold' : 'bg-white border-gray-200 hover:border-gray-300 text-gray-700']">
                                    <div class="flex items-center gap-3">
                                        <input type="radio" :value="t.id" v-model="surveyScheduleForm.technician_id" required class="text-indigo-600 focus:ring-indigo-500 w-4 h-4" />
                                        <div>
                                            <span class="text-sm block">{{ t.name }}</span>
                                            <span class="text-[10px] text-gray-400 font-normal">{{ t.phone || t.role || 'Teknisi' }}</span>
                                        </div>
                                    </div>
                                    <span v-if="surveyScheduleForm.technician_id === t.id" class="text-xs text-indigo-600 font-bold">Terpilih</span>
                                </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tanggal Survey <span class="text-red-500">*</span></label>
                                <input v-model="surveyScheduleForm.scheduled_date" type="date" required class="w-full text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Waktu (Jam Target)</label>
                                <input v-model="surveyScheduleForm.scheduled_time" type="time" class="w-full text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Status Penugasan</label>
                            <select v-model="surveyScheduleForm.status" class="w-full text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="scheduled">Dijadwalkan (scheduled)</option>
                                <option value="on_the_way">Menuju Lokasi (on_the_way)</option>
                                <option value="working">Sedang Dikerjakan (working)</option>
                                <option value="done">Selesai (done)</option>
                                <option value="cancelled">Dibatalkan (cancelled)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Catatan / Instruksi Survey</label>
                            <textarea v-model="surveyScheduleForm.notes" rows="2" placeholder="Contoh: Koordinasi via WhatsApp sebelum ke lokasi..." class="w-full text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 shrink-0">
                        <button type="button" @click="showEditSurveyScheduleModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Batal</button>
                        <button type="submit" :disabled="surveyScheduleForm.processing" class="px-5 py-2 text-sm font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-md transition-all disabled:opacity-50 flex items-center gap-2">
                            <svg v-if="surveyScheduleForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Simpan Jadwal Survey
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- 3. MODAL EDIT LAPORAN SURVEY               -->
        <!-- ========================================== -->
        <div v-if="showEditSurveyReportModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
            <div class="bg-white rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col max-h-[92vh]">
                <div class="p-5 bg-gradient-to-r from-emerald-600 via-teal-600 to-teal-700 text-white flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-xl shrink-0">
                            📋
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-bold">Laporan Hasil Survey ODP</h3>
                            <p class="text-xs text-emerald-100">Pemeriksaan teknisi lapangan, rekomendasi ODP, jarak, & kelayakan</p>
                        </div>
                    </div>
                    <button type="button" @click="showEditSurveyReportModal = false" class="text-white/80 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submitEditSurveyReport" class="flex flex-col flex-1 overflow-hidden">
                    <div class="p-5 sm:p-6 overflow-y-auto space-y-5">
                        <!-- Petugas & Tanggal -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Petugas Surveyor <span class="text-red-500">*</span></label>
                                <select v-model="surveyReportForm.surveyor_id" required class="w-full text-sm border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                                    <option value="">-- Pilih Surveyor --</option>
                                    <option v-for="t in technicians" :key="t.id" :value="t.id">{{ t.name }} ({{ t.phone || t.role }})</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tanggal Survey <span class="text-red-500">*</span></label>
                                <input v-model="surveyReportForm.survey_date" type="date" required class="w-full text-sm border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500" />
                            </div>
                        </div>

                        <!-- Status Kelayakan (Radio Cards) -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Hasil Kelayakan Pemasangan <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <label :class="['flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all', surveyReportForm.feasibility === 'feasible' ? 'border-emerald-500 bg-emerald-50/70 ring-1 ring-emerald-500' : 'border-gray-200 bg-white hover:border-gray-300']">
                                    <input type="radio" value="feasible" v-model="surveyReportForm.feasibility" class="sr-only" />
                                    <div :class="['w-4 h-4 rounded-full border flex items-center justify-center shrink-0', surveyReportForm.feasibility === 'feasible' ? 'border-emerald-500 bg-emerald-500' : 'border-gray-300']">
                                        <div v-if="surveyReportForm.feasibility === 'feasible'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-emerald-950 block">🟢 Layak (Feasible)</span>
                                        <span class="text-[10px] text-emerald-700">Siap dipasang</span>
                                    </div>
                                </label>
                                <label :class="['flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all', surveyReportForm.feasibility === 'not_feasible' ? 'border-rose-500 bg-rose-50/70 ring-1 ring-rose-500' : 'border-gray-200 bg-white hover:border-gray-300']">
                                    <input type="radio" value="not_feasible" v-model="surveyReportForm.feasibility" class="sr-only" />
                                    <div :class="['w-4 h-4 rounded-full border flex items-center justify-center shrink-0', surveyReportForm.feasibility === 'not_feasible' ? 'border-rose-500 bg-rose-500' : 'border-gray-300']">
                                        <div v-if="surveyReportForm.feasibility === 'not_feasible'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-rose-950 block">🔴 Tidak Layak</span>
                                        <span class="text-[10px] text-rose-700">Unfeasible / batal</span>
                                    </div>
                                </label>
                                <label :class="['flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all', surveyReportForm.feasibility === 'conditional' ? 'border-amber-500 bg-amber-50/70 ring-1 ring-amber-500' : 'border-gray-200 bg-white hover:border-gray-300']">
                                    <input type="radio" value="conditional" v-model="surveyReportForm.feasibility" class="sr-only" />
                                    <div :class="['w-4 h-4 rounded-full border flex items-center justify-center shrink-0', surveyReportForm.feasibility === 'conditional' ? 'border-amber-500 bg-amber-500' : 'border-gray-300']">
                                        <div v-if="surveyReportForm.feasibility === 'conditional'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-amber-950 block">🟡 Bersyarat</span>
                                        <span class="text-[10px] text-amber-700">Perlu tiang/ODP baru</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Rekomendasi ODP & Port -->
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-4">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider">Rekomendasi ODP & Port</label>
                                <button type="button" @click="findNearestSurveyOdp" :disabled="isFindingSurveyOdp" class="text-xs text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1 bg-white border border-blue-200 px-2.5 py-1 rounded-md transition-colors disabled:opacity-50 shadow-sm">
                                    <svg v-if="isFindingSurveyOdp" class="animate-spin w-3.5 h-3.5 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <svg v-else class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    {{ isFindingSurveyOdp ? 'Mencari...' : 'Radar ODP Terdekat' }}
                                </button>
                            </div>
                            <p v-if="surveyOdpMsg" class="text-xs font-semibold text-emerald-600 bg-emerald-50 p-2 rounded-lg border border-emerald-200">{{ surveyOdpMsg }}</p>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Pilih ODP</label>
                                    <select v-model="surveyReportForm.odp_id" class="w-full text-sm border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                                        <option value="">-- Pilih ODP --</option>
                                        <option v-for="odp in filteredAvailableOdps" :key="odp.id" :value="odp.id">
                                            {{ odp.name }} (Port: {{ odp.used_ports }}/{{ odp.total_ports }})
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nomor Port ODP</label>
                                    <input v-model="surveyReportForm.port_number" type="number" min="1" max="64" class="w-full text-sm border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500" placeholder="1" />
                                </div>
                            </div>
                            <div class="flex items-center gap-2 pt-1">
                                <input type="checkbox" id="survey_port_avail" v-model="surveyReportForm.port_available" class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500" />
                                <label for="survey_port_avail" class="text-xs font-medium text-gray-700 cursor-pointer">Port ODP Tersedia & Siap Digunakan</label>
                            </div>
                        </div>

                        <!-- Jarak & Redaman Optik -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Estimasi Jarak Kabel (Meter)</label>
                                <input v-model="surveyReportForm.distance_meters" type="number" step="1" class="w-full text-sm border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500" placeholder="Contoh: 120" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Redaman Optik (dBm)</label>
                                <input v-model="surveyReportForm.signal_loss" type="text" class="w-full text-sm font-mono border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500" placeholder="Misal: -18.5" />
                            </div>
                        </div>

                        <!-- Dokumentasi Foto Survey -->
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider">Dokumentasi Foto Survey</label>
                                <button type="button" @click="addSurveyReportPhoto" class="text-xs text-emerald-700 bg-white border border-emerald-300 hover:bg-emerald-50 px-2.5 py-1 rounded-md font-semibold transition-colors flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Tambah Slot Foto
                                </button>
                            </div>
                            
                            <div class="space-y-3">
                                <div v-for="(photo, idx) in surveyReportPhotos" :key="idx" class="flex items-start gap-3 bg-white p-3 rounded-lg border border-gray-200 shadow-sm">
                                    <div class="flex-1 space-y-2">
                                        <input v-model="photo.label" type="text" placeholder="Label Foto (cth: Calon ODP, Tiang, Rumah)" class="w-full text-xs font-medium border-gray-200 rounded px-2.5 py-1.5 focus:ring-emerald-500" />
                                        <input type="file" @change="handleSurveyReportPhotoChange(idx, $event)" accept="image/*" class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer" />
                                    </div>
                                    <div v-if="photo.previewUrl" class="w-16 h-16 rounded-lg overflow-hidden shrink-0 border border-gray-200 shadow-sm relative group">
                                        <img :src="photo.previewUrl" class="w-full h-full object-cover" />
                                        <a :href="photo.previewUrl" target="_blank" class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-[10px] transition-opacity">Lihat</a>
                                    </div>
                                    <button v-if="surveyReportPhotos.length > 1" type="button" @click="removeSurveyReportPhoto(idx)" class="p-1 text-gray-400 hover:text-red-500 transition-colors" title="Hapus foto">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Catatan Tambahan -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Catatan Tambahan Hasil Survey</label>
                            <textarea v-model="surveyReportForm.notes" rows="2" placeholder="Kondisi jalur tarikan kabel, hambatan pohon/tiang..." class="w-full text-sm border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 shrink-0">
                        <button type="button" @click="showEditSurveyReportModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Batal</button>
                        <button type="submit" :disabled="surveyReportForm.processing" class="px-5 py-2 text-sm font-bold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 shadow-md transition-all disabled:opacity-50 flex items-center gap-2">
                            <svg v-if="surveyReportForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Simpan Laporan Survey
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- 4. MODAL EDIT JADWAL PASANG & MATERIAL     -->
        <!-- ========================================== -->
        <div v-if="showEditInstallScheduleModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
            <div class="bg-white rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col max-h-[92vh]">
                <div class="p-5 bg-gradient-to-r from-amber-600 via-orange-600 to-amber-700 text-white flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-xl shrink-0">
                            🚀
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-bold">Jadwal Penugasan Pasang Teknisi & Material</h3>
                            <p class="text-xs text-amber-100">Penugasan tim instalasi, alokasi ONT, dan material instalasi</p>
                        </div>
                    </div>
                    <button type="button" @click="showEditInstallScheduleModal = false" class="text-white/80 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submitEditInstallSchedule" class="flex flex-col flex-1 overflow-hidden">
                    <div class="bg-amber-50/70 px-5 py-3 border-b border-amber-100 flex flex-col gap-1 text-xs shrink-0">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 font-medium">Pelanggan:</span>
                            <span class="font-bold text-slate-800">{{ customer.name }} ({{ customer.customer_code }})</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 font-medium">Hasil Survey:</span>
                            <span class="font-semibold text-slate-700">{{ customer.surveys?.[0]?.odp?.name || 'ODP Belum Ada' }} • {{ customer.surveys?.[0]?.distance_meters ? customer.surveys[0].distance_meters + ' m' : '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 font-medium">Paket:</span>
                            <span class="font-semibold text-amber-800">{{ customer.package?.name || '-' }}</span>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 overflow-y-auto space-y-4">
                        <!-- 1. Pilih Teknisi -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Pilih Teknisi Instalasi <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-1 gap-2 max-h-40 overflow-y-auto border border-gray-200 rounded-xl p-2 bg-slate-50">
                                <label v-for="t in technicians" :key="t.id" :class="['flex items-center justify-between p-2.5 rounded-lg border cursor-pointer transition-all', installScheduleForm.technician_id === t.id ? 'bg-amber-50 border-amber-300 ring-1 ring-amber-500 text-amber-950 font-bold' : 'bg-white border-gray-200 hover:border-gray-300 text-gray-700']">
                                    <div class="flex items-center gap-3">
                                        <input type="radio" :value="t.id" v-model="installScheduleForm.technician_id" required class="text-amber-600 focus:ring-amber-500 w-4 h-4" />
                                        <div>
                                            <span class="text-sm block">{{ t.name }}</span>
                                            <span class="text-[10px] text-gray-400 font-normal">{{ t.phone || t.role || 'Teknisi' }}</span>
                                        </div>
                                    </div>
                                    <span v-if="installScheduleForm.technician_id === t.id" class="text-xs text-amber-600 font-bold">Terpilih</span>
                                </label>
                            </div>
                        </div>

                        <!-- 2. Tanggal & Jam Target -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tanggal Pasang <span class="text-red-500">*</span></label>
                                <input v-model="installScheduleForm.scheduled_date" type="date" required class="w-full text-sm border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Waktu Target</label>
                                <input v-model="installScheduleForm.scheduled_time" type="time" class="w-full text-sm border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500" />
                            </div>
                        </div>

                        <!-- 3. Status Penjadwalan -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Status Penugasan</label>
                            <select v-model="installScheduleForm.status" class="w-full text-sm border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500">
                                <option value="scheduled">Dijadwalkan (scheduled)</option>
                                <option value="on_the_way">Menuju Lokasi (on_the_way)</option>
                                <option value="working">Sedang Pasang (working)</option>
                                <option value="done">Selesai (done)</option>
                                <option value="cancelled">Dibatalkan (cancelled)</option>
                            </select>
                        </div>

                        <!-- 4. Data ONT (Surat Jalan / Stok) -->
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>📟</span> Data ONT / Modem (Surat Jalan / Stok)
                                </label>
                                <span class="text-[11px] text-slate-500 font-medium">{{ filteredOnts.length }} unit siap pasang</span>
                            </div>
                            <select v-model="installScheduleForm.ont_id" class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm text-slate-700 focus:ring-2 focus:ring-amber-500 shadow-sm">
                                <option value="">-- Pilih ONT dari Surat Jalan / Stok --</option>
                                <option v-for="ontOption in filteredOnts" :key="ontOption.id" :value="ontOption.id">
                                    {{ ontOption.brand }} {{ ontOption.model || '' }} - SN: {{ ontOption.serial_number }}
                                </option>
                            </select>
                            <div v-if="installScheduleForm.ont_id && getSelectedOntDetails(installScheduleForm.ont_id)" class="bg-amber-100/70 p-2.5 rounded-lg border border-amber-200 text-xs text-slate-700 grid grid-cols-2 gap-2">
                                <div><span class="text-slate-500">Merek/Model:</span> <span class="font-bold">{{ getSelectedOntDetails(installScheduleForm.ont_id).brand }} {{ getSelectedOntDetails(installScheduleForm.ont_id).model || '' }}</span></div>
                                <div><span class="text-slate-500">SN:</span> <span class="font-mono font-bold text-amber-900">{{ getSelectedOntDetails(installScheduleForm.ont_id).serial_number }}</span></div>
                            </div>
                        </div>

                        <!-- 5. Kebutuhan Material (Surat Jalan & Stok) -->
                        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                        <span>📦</span> Material (Surat Jalan)
                                    </label>
                                    <p class="text-[11px] text-gray-500">Alokasikan bahan dropcore, roset, fast connector, dll untuk teknisi</p>
                                </div>
                                <button type="button" @click="addScheduleMaterialItem" class="text-blue-600 hover:text-blue-700 hover:bg-blue-50 px-2.5 py-1.5 rounded-lg transition-colors border border-blue-200 text-xs font-bold flex items-center gap-1 shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Tambah Material
                                </button>
                            </div>

                            <div class="space-y-2.5">
                                <div v-for="(mItem, index) in installScheduleForm.material_items" :key="'mat-'+index" class="flex gap-2 items-center bg-slate-50 p-2 rounded-lg border border-slate-200">
                                    <select v-model="installScheduleForm.material_items[index].id" @change="updateScheduleMaterialDetails(index)" class="flex-1 bg-white border border-gray-300 rounded-lg px-2.5 py-2 text-xs text-slate-700 focus:ring-2 focus:ring-amber-500 min-w-0">
                                        <option value="">-- Pilih Material dari Surat Jalan --</option>
                                        <option v-for="item in availableMaterialItems" :key="item.id" :value="item.id">
                                            {{ item.name }} (SJ: {{ item.trx_number }}) - Stok: {{ item.area_stock }} {{ item.unit }}
                                        </option>
                                    </select>
                                    <div class="flex items-center gap-1 shrink-0" v-if="installScheduleForm.material_items[index].id">
                                        <input v-model="installScheduleForm.material_items[index].qty" @input="validateScheduleMaterialQty(index)" type="number" step="0.01" min="0" class="w-16 px-2 py-1.5 text-xs border border-gray-300 rounded-lg text-center font-bold text-amber-800 bg-white" placeholder="Qty">
                                        <span class="text-[11px] font-semibold text-gray-600 w-12 truncate">{{ installScheduleForm.material_items[index].unit }}</span>
                                    </div>
                                    <button type="button" @click="removeScheduleMaterialItem(index)" class="text-red-400 hover:text-red-600 p-1.5 rounded hover:bg-red-50 transition-colors shrink-0" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                                <div v-if="installScheduleForm.material_items.length === 0" class="text-xs text-gray-400 italic text-center py-2 border border-dashed border-gray-200 rounded-lg">
                                    Belum ada material yang ditambahkan (klik "Tambah Material" di atas)
                                </div>
                            </div>
                        </div>

                        <!-- 6. Catatan Khusus -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Catatan Khusus Instalasi</label>
                            <textarea v-model="installScheduleForm.notes" rows="2" placeholder="Catatan instruksi rute kabel, tiang, dll..." class="w-full text-sm border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"></textarea>
                        </div>

                        <!-- 7. Kirim Notifikasi WhatsApp ke Teknisi -->
                        <div class="bg-emerald-50/70 border border-emerald-200/80 rounded-xl p-3 flex items-center justify-between">
                            <label class="flex items-center gap-2.5 cursor-pointer select-none">
                                <input type="checkbox" v-model="installScheduleForm.send_wa" class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500">
                                <div>
                                    <span class="text-xs font-bold text-emerald-950 block">Kirim Notifikasi WhatsApp ke Teknisi</span>
                                    <span class="text-[11px] text-emerald-700">Kirim rincian jadwal, alamat pelanggan, ODP & bahan ke WA teknisi</span>
                                </div>
                            </label>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                                WA Otomatis
                            </span>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 shrink-0">
                        <button type="button" @click="showEditInstallScheduleModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Batal</button>
                        <button type="submit" :disabled="installScheduleForm.processing" class="px-5 py-2 text-sm font-bold text-white bg-amber-600 rounded-lg hover:bg-amber-700 shadow-md transition-all disabled:opacity-50 flex items-center gap-2">
                            <svg v-if="installScheduleForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Simpan Jadwal Pasang
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- 5. MODAL EDIT LAPORAN INSTALASI & ONT      -->
        <!-- ========================================== -->
        <div v-if="showEditInstallationReportModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
            <div class="bg-white rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col max-h-[92vh]">
                <div class="p-5 bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-700 text-white flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-xl shrink-0">
                            🔧
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-bold">Laporan Selesai Instalasi & Konfigurasi ONT</h3>
                            <p class="text-xs text-cyan-100">Pencatatan ODP terpasang, port, fisik modem ONT, jam kerja, & dokumentasi foto</p>
                        </div>
                    </div>
                    <button type="button" @click="showEditInstallationReportModal = false" class="text-white/80 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submitEditInstallationReport" class="flex flex-col flex-1 overflow-hidden">
                    <div class="p-5 sm:p-6 overflow-y-auto space-y-5">
                        <!-- Step 1: Waktu Pelaksanaan -->
                        <div class="bg-blue-50/60 p-4 rounded-xl border border-blue-100 space-y-3">
                            <h4 class="text-xs font-bold text-blue-900 uppercase tracking-wider flex items-center gap-2">
                                <span class="p-1 rounded bg-blue-200 text-blue-800">⏱️</span> 1. Jam Pelaksanaan Pekerjaan
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-semibold text-gray-700">Jam Mulai</label>
                                        <button type="button" @click="setInstallReportNow('start_time')" class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                                            MULAI SEKARANG
                                        </button>
                                    </div>
                                    <input v-model="installReportForm.start_time" type="time" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" />
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-semibold text-gray-700">Jam Selesai</label>
                                        <button type="button" @click="setInstallReportNow('end_time')" class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-600 text-white hover:bg-emerald-700 transition-colors">
                                            SELESAI SEKARANG
                                        </button>
                                    </div>
                                    <input v-model="installReportForm.end_time" type="time" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" />
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: ODP & Port Terpasang -->
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                <span class="p-1 rounded bg-indigo-100 text-indigo-700">🔌</span> 2. Titik Sambung ODP & Port
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">ODP Terpasang <span class="text-red-500">*</span></label>
                                    <select v-model="installReportForm.odp_id" required class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">-- Pilih ODP --</option>
                                        <option v-for="odp in availableOdps" :key="odp.id" :value="odp.id">
                                            {{ odp.name }} (Port: {{ odp.used_ports }}/{{ odp.total_ports }})
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nomor Port ODP <span class="text-red-500">*</span></label>
                                    <input v-model="installReportForm.port_number" type="number" min="1" max="64" required placeholder="Contoh: 1" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" />
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Spesifikasi Fisik ONT -->
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                <span class="p-1 rounded bg-amber-100 text-amber-700">📟</span> 3. Spesifikasi Fisik Modem ONT
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Serial Number (SN) ONT <span class="text-red-500">*</span></label>
                                    <input v-model="installReportForm.serial_number" type="text" required placeholder="ZTEGC1234567" class="w-full text-sm font-mono border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 uppercase" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">MAC Address ONT</label>
                                    <input v-model="installReportForm.mac_address" type="text" placeholder="E4:2D:7D:BB:7A:4F" class="w-full text-sm font-mono border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Merk / Model ONT</label>
                                    <input v-model="installReportForm.model" type="text" placeholder="ZTE F609 / Huawei / GM220" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Redaman Optik ONT (dBm)</label>
                                    <input v-model="installReportForm.rx_power" type="text" placeholder="-19.50" class="w-full text-sm font-mono border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" />
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: 5 Slot Dokumentasi Foto Lapangan -->
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-4">
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                <span class="p-1 rounded bg-teal-100 text-teal-700">📸</span> 4. Dokumentasi Foto Lapangan Lengkap
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- 1. Foto ODP -->
                                <div class="bg-white p-3 rounded-lg border border-gray-200 space-y-2">
                                    <label class="block text-xs font-bold text-gray-700">1. Foto ODP / Port</label>
                                    <div class="flex items-center gap-3">
                                        <div v-if="installPhotoPreviews.photo_odp" class="w-14 h-14 rounded-lg overflow-hidden shrink-0 border border-gray-200">
                                            <img :src="installPhotoPreviews.photo_odp" class="w-full h-full object-cover" />
                                        </div>
                                        <div class="flex-1">
                                            <input type="file" @change="handleInstallReportPhoto('photo_odp', $event)" accept="image/*" class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer" />
                                        </div>
                                    </div>
                                </div>
                                <!-- 2. Foto Instalasi -->
                                <div class="bg-white p-3 rounded-lg border border-gray-200 space-y-2">
                                    <label class="block text-xs font-bold text-gray-700">2. Foto Instalasi Rumah</label>
                                    <div class="flex items-center gap-3">
                                        <div v-if="installPhotoPreviews.photo_installation" class="w-14 h-14 rounded-lg overflow-hidden shrink-0 border border-gray-200">
                                            <img :src="installPhotoPreviews.photo_installation" class="w-full h-full object-cover" />
                                        </div>
                                        <div class="flex-1">
                                            <input type="file" @change="handleInstallReportPhoto('photo_installation', $event)" accept="image/*" class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer" />
                                        </div>
                                    </div>
                                </div>
                                <!-- 3. Foto ONT -->
                                <div class="bg-white p-3 rounded-lg border border-gray-200 space-y-2">
                                    <label class="block text-xs font-bold text-gray-700">3. Foto Posisi ONT</label>
                                    <div class="flex items-center gap-3">
                                        <div v-if="installPhotoPreviews.photo_ont" class="w-14 h-14 rounded-lg overflow-hidden shrink-0 border border-gray-200">
                                            <img :src="installPhotoPreviews.photo_ont" class="w-full h-full object-cover" />
                                        </div>
                                        <div class="flex-1">
                                            <input type="file" @change="handleInstallReportPhoto('photo_ont', $event)" accept="image/*" class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer" />
                                        </div>
                                    </div>
                                </div>
                                <!-- 4. Foto Selfie -->
                                <div class="bg-white p-3 rounded-lg border border-gray-200 space-y-2">
                                    <label class="block text-xs font-bold text-gray-700">4. Foto Selfie Pelanggan</label>
                                    <div class="flex items-center gap-3">
                                        <div v-if="installPhotoPreviews.photo_customer" class="w-14 h-14 rounded-lg overflow-hidden shrink-0 border border-gray-200">
                                            <img :src="installPhotoPreviews.photo_customer" class="w-full h-full object-cover" />
                                        </div>
                                        <div class="flex-1">
                                            <input type="file" @change="handleInstallReportPhoto('photo_customer', $event)" accept="image/*" class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer" />
                                        </div>
                                    </div>
                                </div>
                                <!-- 5. Foto Redaman -->
                                <div class="sm:col-span-2 bg-white p-3 rounded-lg border border-gray-200 space-y-2">
                                    <label class="block text-xs font-bold text-gray-700">5. Foto Redaman (OPM)</label>
                                    <div class="flex items-center gap-3">
                                        <div v-if="installPhotoPreviews.photo_redaman" class="w-14 h-14 rounded-lg overflow-hidden shrink-0 border border-gray-200">
                                            <img :src="installPhotoPreviews.photo_redaman" class="w-full h-full object-cover" />
                                        </div>
                                        <div class="flex-1">
                                            <input type="file" @change="handleInstallReportPhoto('photo_redaman', $event)" accept="image/*" class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 shrink-0">
                        <button type="button" @click="showEditInstallationReportModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Batal</button>
                        <button type="submit" :disabled="installReportForm.processing" class="px-5 py-2 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-md transition-all disabled:opacity-50 flex items-center gap-2">
                            <svg v-if="installReportForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Simpan Laporan Instalasi
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- 6. MODAL EDIT AKTIVASI & LAYANAN           -->
        <!-- ========================================== -->
        <div v-if="showEditActivationModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
            <div class="bg-white rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col max-h-[92vh]">
                <div class="p-5 bg-gradient-to-r from-purple-600 via-indigo-600 to-indigo-700 text-white flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-xl shrink-0">
                            ⚡
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-bold">Aktivasi & Konfigurasi Layanan Pelanggan</h3>
                            <p class="text-xs text-purple-100">Perbarui tanggal aktivasi, paket internet, tagihan, PPPoE, & ONT login</p>
                        </div>
                    </div>
                    <button type="button" @click="showEditActivationModal = false" class="text-white/80 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submitEditActivation" class="flex flex-col flex-1 overflow-hidden">
                    <div class="p-5 sm:p-6 overflow-y-auto space-y-5">
                        <!-- 1. Status Layanan Pelanggan (3 Radio Cards) -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Status Layanan Pelanggan <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                <label :class="['flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all', editActivationForm.service_status === 'berbayar' ? 'border-purple-600 bg-purple-50/70 ring-1 ring-purple-600' : 'border-gray-200 bg-white hover:border-gray-300']">
                                    <div class="flex items-center gap-2">
                                        <div :class="['w-4 h-4 rounded-full border flex items-center justify-center shrink-0', editActivationForm.service_status === 'berbayar' ? 'border-purple-600 bg-purple-600' : 'border-gray-300']">
                                            <div v-if="editActivationForm.service_status === 'berbayar'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                        </div>
                                        <div>
                                            <span class="text-xs font-bold text-gray-900 block">Berbayar</span>
                                            <span class="text-[10px] text-gray-500">Tarif paket rutin</span>
                                        </div>
                                    </div>
                                    <input type="radio" value="berbayar" v-model="editActivationForm.service_status" class="sr-only">
                                </label>
                                
                                <label :class="['flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all', editActivationForm.service_status === 'gratis' ? 'border-emerald-500 bg-emerald-50/70 ring-1 ring-emerald-500' : 'border-gray-200 bg-white hover:border-gray-300']">
                                    <div class="flex items-center gap-2">
                                        <div :class="['w-4 h-4 rounded-full border flex items-center justify-center shrink-0', editActivationForm.service_status === 'gratis' ? 'border-emerald-500 bg-emerald-500' : 'border-gray-300']">
                                            <div v-if="editActivationForm.service_status === 'gratis'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                        </div>
                                        <div>
                                            <span class="text-xs font-bold text-gray-900 block">🎁 Gratis</span>
                                            <span class="text-[10px] text-gray-500">CSR / Fasilitas</span>
                                        </div>
                                    </div>
                                    <input type="radio" value="gratis" v-model="editActivationForm.service_status" class="sr-only">
                                </label>

                                <label :class="['flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all', editActivationForm.service_status === 'free_home' ? 'border-amber-500 bg-amber-50/70 ring-1 ring-amber-500' : 'border-gray-200 bg-white hover:border-gray-300']">
                                    <div class="flex items-center gap-2">
                                        <div :class="['w-4 h-4 rounded-full border flex items-center justify-center shrink-0', editActivationForm.service_status === 'free_home' ? 'border-amber-500 bg-amber-500' : 'border-gray-300']">
                                            <div v-if="editActivationForm.service_status === 'free_home'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                        </div>
                                        <div>
                                            <span class="text-xs font-bold text-gray-900 block">🏠 Free Home</span>
                                            <span class="text-[10px] text-gray-500">Voucher Rumahan</span>
                                        </div>
                                    </div>
                                    <input type="radio" value="free_home" v-model="editActivationForm.service_status" class="sr-only">
                                </label>
                            </div>
                        </div>

                        <!-- Banner Free Home Alert -->
                        <div v-if="editActivationForm.service_status === 'free_home'" class="p-3 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-2.5 text-xs text-amber-900">
                            <span class="text-base shrink-0">🏠</span>
                            <div class="leading-relaxed">
                                <p class="font-bold">Mode Free Home (Voucher Rumahan ONT)</p>
                                <p class="text-[11px] text-amber-700 mt-0.5">
                                    Pelanggan ini tidak akan dikenakan invoice billing rutin dan tidak disinkronkan ke RADIUS PPPoE.
                                </p>
                            </div>
                        </div>

                        <!-- 2. Siklus Penagihan & Tanggal -->
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tanggal Aktivasi <span class="text-red-500">*</span></label>
                                    <input v-model="editActivationForm.activation_date" type="date" required class="w-full text-sm border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500" />
                                </div>
                                <div v-if="editActivationForm.service_status !== 'free_home'">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Jenis Tagihan <span class="text-red-500">*</span></label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label :class="['flex items-center justify-center py-2 px-3 rounded-lg border text-xs font-bold cursor-pointer transition-all', editActivationForm.billing_type === 'prabayar' ? 'bg-purple-50 border-purple-300 text-purple-900 ring-1 ring-purple-500' : 'bg-white border-gray-200 text-gray-600']">
                                            <input type="radio" value="prabayar" v-model="editActivationForm.billing_type" class="sr-only" />
                                            Prabayar
                                        </label>
                                        <label :class="['flex items-center justify-center py-2 px-3 rounded-lg border text-xs font-bold cursor-pointer transition-all', editActivationForm.billing_type === 'pascabayar' ? 'bg-purple-50 border-purple-300 text-purple-900 ring-1 ring-purple-500' : 'bg-white border-gray-200 text-gray-600']">
                                            <input type="radio" value="pascabayar" v-model="editActivationForm.billing_type" class="sr-only" />
                                            Pasca Bayar
                                        </label>
                                    </div>
                                </div>

                                <div class="col-span-full pt-1">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Siklus Tanggal Tagihan</label>
                                    <select v-model="editActivationForm.billing_cycle" class="w-full text-xs border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                                        <option value="default">Ikuti Default Sistem ({{ $page.props.billingSettings?.billing_cycle === 'anniversary' ? 'Sesuai Tgl Aktivasi' : 'Kalender Serentak' }})</option>
                                        <option value="anniversary">Sesuai Tanggal Aktivasi (Anniversary)</option>
                                        <option value="calendar">Siklus Kalender (Tanggal Serentak)</option>
                                        <option value="custom_date">Pilih Tanggal Khusus Sendiri</option>
                                    </select>
                                    <div v-if="editActivationForm.billing_cycle === 'custom_date'" class="flex items-center gap-2 mt-2 pt-2 border-t border-gray-200">
                                        <span class="text-xs text-gray-600 font-medium">Tanggal Tagihan Rutin:</span>
                                        <input type="number" v-model="editActivationForm.billing_day" min="1" max="28" class="w-20 px-2.5 py-1 text-xs border border-gray-300 rounded-lg focus:ring-purple-500 font-bold text-purple-800 bg-white" placeholder="1-28" />
                                        <span class="text-xs text-gray-500">tiap bulan</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Paket Berlangganan</label>
                                <select v-model="editActivationForm.package_id" class="w-full text-sm border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                                    <option value="">-- Pilih Paket Internet --</option>
                                    <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">{{ pkg.name }} ({{ formatRupiah(pkg.price) }})</option>
                                </select>
                            </div>
                        </div>

                        <!-- 3. Kredensial PPPoE & Akses Jaringan -->
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-4">
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                <span class="p-1 rounded bg-indigo-100 text-indigo-700">🌐</span> Kredensial Akses & VLAN
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">PPPoE / Member Username</label>
                                    <input v-model="editActivationForm.pppoe_user" type="text" placeholder="user@isp" class="w-full text-sm font-medium border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">PPPoE / Member Password</label>
                                    <input v-model="editActivationForm.pppoe_password" type="text" placeholder="***" class="w-full text-sm font-medium border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Mode Akses</label>
                                    <select v-model="editActivationForm.access_mode" class="w-full text-sm border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                                        <option value="PPPOE">PPPoE</option>
                                        <option value="STATIC">Static IP</option>
                                        <option value="DHCP">DHCP / Dynamic</option>
                                        <option value="HOTSPOT">Hotspot</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">VLAN Mode</label>
                                    <select v-model="editActivationForm.vlan_mode" class="w-full text-sm border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                                        <option value="">-- Pilih Mode --</option>
                                        <option value="Route">Route</option>
                                        <option value="Bridge">Bridge</option>
                                        <option value="VLAN">VLAN (Tagged)</option>
                                        <option value="Untagged">Untagged</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">VLAN ID</label>
                                    <input v-model="editActivationForm.vlan_id" type="text" placeholder="100" class="w-full text-sm font-mono border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500" />
                                </div>
                            </div>
                        </div>

                        <!-- 4. Akses Web GUI ONT -->
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-4">
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                <span class="p-1 rounded bg-blue-100 text-blue-700">💻</span> Akses Web GUI Admin ONT
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">IP Login ONT</label>
                                    <input v-model="editActivationForm.ip_login" type="text" placeholder="192.168.1.1" class="w-full text-sm font-mono border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">User Login ONT</label>
                                    <input v-model="editActivationForm.login_user" type="text" placeholder="admin" class="w-full text-sm border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Pass Login ONT</label>
                                    <input v-model="editActivationForm.login_password" type="text" placeholder="admin" class="w-full text-sm border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500" />
                                </div>
                            </div>
                        </div>

                        <!-- 5. Fasilitas Free Hotspot ONT -->
                        <div class="p-4 bg-amber-50 rounded-xl border border-amber-200 space-y-3">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" v-model="editActivationForm.free_hotspot" class="w-4 h-4 text-amber-600 rounded border-gray-300 focus:ring-amber-500" />
                                <span class="text-sm font-bold text-amber-900">🎁 Gratis 1 User Hotspot Aktif</span>
                            </label>
                            <div v-if="editActivationForm.free_hotspot" class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Username Hotspot</label>
                                    <input v-model="editActivationForm.hotspot_user" type="text" class="w-full text-sm border-amber-300 rounded-lg focus:ring-amber-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Password Hotspot</label>
                                    <input v-model="editActivationForm.hotspot_password" type="text" class="w-full text-sm border-amber-300 rounded-lg focus:ring-amber-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">VLAN ID (Opsional)</label>
                                    <input v-model="editActivationForm.hotspot_vlan_id" type="text" class="w-full text-sm border-amber-300 rounded-lg focus:ring-amber-500" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 shrink-0">
                        <button type="button" @click="showEditActivationModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Batal</button>
                        <button type="submit" :disabled="editActivationForm.processing" class="px-5 py-2 text-sm font-bold text-white bg-purple-600 rounded-lg hover:bg-purple-700 shadow-md transition-all disabled:opacity-50 flex items-center gap-2">
                            <svg v-if="editActivationForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Simpan Konfigurasi Aktivasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
</template>
<script setup>
import { ref, h, computed, watch, onMounted } from 'vue';
import { Link, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Swal from 'sweetalert2';
import { billingIsolationDate } from '@/Utils/billingIsolation';

const page = usePage();
const formatDate = (dateString) => {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' }) + ' ' + date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    } catch(e) {
        return dateString;
    }
};
const hasPermission = (permission) => {
    try {
        const user = page.props.auth?.user;
        if (!user) return false;
        if (user.role === 'admin' || user.role === 'Super Admin') return true;
        let roles = [];
        if (Array.isArray(user.roles)) roles = user.roles;
        else if (user.roles) roles = Object.values(user.roles);
        if (roles.includes('admin') || roles.includes('Super Admin')) return true;
        let perms = [];
        if (Array.isArray(user.permissions)) perms = user.permissions;
        else if (user.permissions) perms = Object.values(user.permissions);
        if (perms.includes(permission)) return true;
        return false;
    } catch (e) {
        return false;
    }
};

const isSuperAdmin = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return false;
    const r = String(user.role || '').toLowerCase().trim();
    if (['admin', 'super admin', 'superadmin', 'super-admin'].includes(r) || user.id === 1) return true;
    let roles = [];
    if (Array.isArray(user.roles)) roles = user.roles.map(x => String(typeof x === 'string' ? x : x.name).toLowerCase().trim());
    else if (user.roles) roles = Object.values(user.roles).map(x => String(typeof x === 'string' ? x : x.name).toLowerCase().trim());
    return roles.includes('admin') || roles.includes('super admin') || roles.includes('superadmin') || roles.includes('super-admin');
});

const props = defineProps({
    customer: Object,
    availableOdps: Array,
    availableOnts: Array,
    source: String,
    materials: Array,
    materialRequests: Array,
    materialTransactions: Array,
    installationReturns: Array,
    installationUsages: Array,
    technicians: Array,
    packages: Array,
    areas: Array,
    salesUsers: Array,
});

const canEditProfile = computed(() => {
    if (hasPermission('customers_all_edit')) return true;
    switch (props.customer.status) {
        case 'booking': return hasPermission('customers_booking_edit');
        case 'survey': return hasPermission('customers_survey_edit');
        case 'installing': return hasPermission('customers_installed_edit');
        case 'active': return hasPermission('customers_active_edit');
        default: return false;
    }
});

const isInstallationScheduled = computed(() => {
    return props.customer.technician_schedules && props.customer.technician_schedules.some(s => s.type === 'installation' && s.status === 'scheduled');
});

const isLaporanPasang = computed(() => {
    return props.customer.status === 'installing' && isInstallationScheduled.value;
});

const isAudit = computed(() => {
    return props.customer.status === 'installing' && !isInstallationScheduled.value && props.customer.ont && props.customer.ont.rx_power;
});

const activeTab = ref(
    isLaporanPasang.value ? 'installation' :
    (isAudit.value ? 'audit' : 
    (props.customer.status === 'installing' ? 'installation' : 
    (props.customer.status === 'active' ? 'audit' : 'booking')))
);

const filteredAvailableOdps = computed(() => {
    if (!props.customer?.area_id) return [];
    return props.availableOdps?.filter(odp => Number(odp.area_id) === Number(props.customer.area_id)) || [];
});

function isPortUsed(odp, portNumber) {
    if (!odp || !odp.onts) return false;
    return odp.onts.some(ont => ont.port_number == portNumber && ont.customer_id != props.customer.id);
}

function getPortStatus(odp, portNumber) {
    if (!odp || !odp.onts) return '';
    const ont = odp.onts.find(o => o.port_number == portNumber);
    if (!ont) return '';
    
    if (ont.customer_id == props.customer.id) return '(Port Pelanggan Ini)';
    if (ont.customer) return `(Terpakai - ${ont.customer.status})`;
    return '(Terpakai - Kosong)';
}

const getAssignedTechnicians = (type) => {
    if (!props.customer?.technician_schedules) return '-';
    const schedules = props.customer.technician_schedules.filter(s => s.type === type);
    if (schedules.length === 0) return '-';
    return schedules.map(s => s.technician?.name).filter(Boolean).join(', ') || '-';
};

const previewImage = ref(null);

const latestSurvey = computed(() => {
    if (!props.customer?.surveys || props.customer.surveys.length === 0) return null;
    const list = [...props.customer.surveys];
    return list.sort((a, b) => (b.id || 0) - (a.id || 0))[0] || list[0];
});

function getSurveyPhotoUrl(photo) {
    if (!photo) return '';
    const path = typeof photo === 'string' ? photo : (photo.path || photo.url || '');
    if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('/storage/')) {
        return path;
    }
    return `/storage/${path}`;
}

function getSurveyPhotoLabel(photo, index) {
    if (!photo) return `Foto ${index + 1}`;
    if (typeof photo === 'object' && photo.label) return photo.label;
    return `Dokumentasi Survey ${index + 1}`;
}

function applySurveyToForm() {
    if (!latestSurvey.value) return;
    if (latestSurvey.value.odp_id) {
        ontForm.odp_id = latestSurvey.value.odp_id;
        if (latestSurvey.value.port_number) {
            ontForm.port_number = latestSurvey.value.port_number;
        }
    }
}

const hardwareItems = ref([]);
const installationNotes = ref('');

const latestReturnTrx = computed(() => {
    if (props.installationReturns && props.installationReturns.length > 0) {
        return props.installationReturns[0];
    }
    if (props.materialRequests && props.materialRequests.length > 0) {
        const found = props.materialRequests.find(r => 
            r.request_type === 'return_handover' || 
            (r.transaction_number && r.transaction_number.startsWith('RTR-'))
        );
        if (found) return found;
    }
    return null;
});

const detailedReturnItems = computed(() => {
    const list = [];
    const seenKeys = new Set();

    // Prioritas 1: Dari transaksi serah terima retur di database
    const returnTrx = latestReturnTrx.value;
    if (returnTrx && returnTrx.items && returnTrx.items.length > 0) {
        returnTrx.items.forEach(it => {
            const name = it.material?.name || 'Material';
            const key = name.toLowerCase().trim();
            seenKeys.add(key);
            list.push({
                name: name,
                qty: it.quantity,
                unit: it.unit || 'pcs',
                condition: it.condition || 'Layak Pakai',
                note: `Ref: ${returnTrx.transaction_number}`
            });
        });
        return list;
    }

    // Prioritas 2: Dari catatan schedule installation ('Material Dikembalikan:')
    const schedule = props.customer?.technician_schedules?.find(s => s.type === 'installation');
    if (schedule && schedule.notes) {
        const lines = schedule.notes.split('\n');
        lines.forEach(line => {
            const trimmed = line.trim();
            if (trimmed.startsWith('Material Dikembalikan:')) {
                const parts = trimmed.replace('Material Dikembalikan:', '').split(',');
                parts.forEach(p => {
                    const match = p.trim().match(/^(.*?)\s*\((\d+(?:\.\d+)?)\s*([a-zA-Z]+)?\)$/);
                    if (match) {
                        const name = match[1].trim();
                        const key = name.toLowerCase();
                        if (!seenKeys.has(key)) {
                            seenKeys.add(key);
                            list.push({
                                name: name,
                                qty: parseFloat(match[2]),
                                unit: match[3] || 'pcs',
                                condition: 'Layak Pakai',
                                note: 'Tercatat di Laporan Pemasangan'
                            });
                        }
                    } else if (p.trim()) {
                        list.push({
                            name: p.trim(),
                            qty: 1,
                            unit: 'item',
                            condition: 'Layak Pakai',
                            note: 'Tercatat di Laporan Pemasangan'
                        });
                    }
                });
            }
        });
        if (list.length > 0) return list;
    }

    // Prioritas 3: Parse dari notes transaksi OUT-INSTALASI jika ada hitungan sisa
    if (props.installationUsages && props.installationUsages.length > 0) {
        const outTrx = props.installationUsages[0];
        if (outTrx && outTrx.notes) {
            const outLines = outTrx.notes.split('\n');
            outLines.forEach(l => {
                const m = l.match(/^-\s*(.*?):\s*(\d+(?:\.\d+)?)\s*([a-zA-Z]+)?\s*\(Bekal awal:\s*(\d+(?:\.\d+)?)\s*([a-zA-Z]+)?\)/i);
                if (m) {
                    const name = m[1].trim();
                    const used = parseFloat(m[2]);
                    const assigned = parseFloat(m[4]);
                    const unit = m[3] || m[5] || 'pcs';
                    const retQty = Math.max(0, Math.round((assigned - used) * 100) / 100);
                    if (retQty > 0) {
                        const key = name.toLowerCase();
                        if (!seenKeys.has(key)) {
                            seenKeys.add(key);
                            list.push({
                                name: name,
                                qty: retQty,
                                unit: unit,
                                condition: 'Layak Pakai',
                                note: `Sisa dari bekal ${assigned} ${unit}`
                            });
                        }
                    }
                }
            });
            if (list.length > 0) return list;
        }
    }

    return list;
});

watch(() => props.customer, () => {
    const schedule = props.customer?.technician_schedules?.find(s => s.type === 'installation');
    if (!schedule || !schedule.notes) {
        hardwareItems.value = [];
        installationNotes.value = '';
        return;
    }
    
    const lines = schedule.notes.split('\n');
    const items = [];
    const notesList = [];
    let idCounter = 0;
    const isScheduleDone = schedule.status === 'done';

    // Map bekal awal jika ada
    const initialAllocMap = {};
    const returnedMap = {};

    lines.forEach(line => {
        const text = line.trim();
        if (text.startsWith('Alokasi Awal:')) {
            const rawMats = text.replace('Alokasi Awal:', '').split(',');
            rawMats.forEach(rm => {
                const match = rm.trim().match(/^(.*?)\s*\((.*?)\)$/);
                if (match) {
                    initialAllocMap[match[1].trim().toLowerCase()] = match[2].trim();
                }
            });
        }
        if (text.startsWith('Material Dikembalikan:')) {
            const rawRets = text.replace('Material Dikembalikan:', '').split(',');
            rawRets.forEach(rr => {
                const match = rr.trim().match(/^(.*?)\s*\((.*?)\)$/);
                if (match) {
                    returnedMap[match[1].trim().toLowerCase()] = match[2].trim();
                }
            });
        }
    });

    // Juga map dari OUT-INSTALASI notes jika ada info bekal awal
    if (props.installationUsages && props.installationUsages.length > 0) {
        const outTrx = props.installationUsages[0];
        if (outTrx && outTrx.notes) {
            const outLines = outTrx.notes.split('\n');
            outLines.forEach(ol => {
                const om = ol.match(/^-\s*(.*?):\s*(\d+(?:\.\d+)?)\s*([a-zA-Z]+)?\s*\(Bekal awal:\s*(\d+(?:\.\d+)?\s*[a-zA-Z]+)\)/i);
                if (om) {
                    const cleanName = om[1].trim().toLowerCase();
                    initialAllocMap[cleanName] = om[4].trim();
                    const used = parseFloat(om[2]);
                    const assigned = parseFloat(om[4]);
                    const diff = assigned - used;
                    if (diff > 0) {
                        const unit = om[3] || 'pcs';
                        returnedMap[cleanName] = `${diff} ${unit}`;
                    }
                }
            });
        }
    }

    lines.forEach(line => {
        let text = line.trim();
        if (!text) return;
        if (text.startsWith('ONT: ')) {
            const onts = text.replace('ONT: ', '').split(', ');
            onts.forEach(ont => {
                if (ont.trim()) items.push({ id: idCounter++, type: 'ONT', name: ont.trim(), isInstalled: isScheduleDone });
            });
        } else if (text.startsWith('Material: ')) {
            const mats = text.replace('Material: ', '').split(', ');
            mats.forEach(mat => {
                if (mat.trim()) {
                    const cleanMatName = mat.trim();
                    const baseLower = cleanMatName.replace(/\(.*?\)/, '').trim().toLowerCase();
                    let initialAlloc = null;
                    let returnedNote = null;
                    for (const [k, v] of Object.entries(initialAllocMap)) {
                        if (baseLower.includes(k) || k.includes(baseLower)) {
                            initialAlloc = v;
                            break;
                        }
                    }
                    for (const [k, v] of Object.entries(returnedMap)) {
                        if (baseLower.includes(k) || k.includes(baseLower)) {
                            returnedNote = `↩️ Sisa ${v} dikembalikan ke Gudang`;
                            break;
                        }
                    }
                    items.push({ 
                        id: idCounter++, 
                        type: 'Material', 
                        name: cleanMatName, 
                        isInstalled: isScheduleDone,
                        initialAllocated: initialAlloc,
                        returnedNote: returnedNote
                    });
                }
            });
        } else if (text.startsWith('Catatan Tambahan:') || text.startsWith('Catatan:')) {
            const cleanNote = text.replace(/^Catatan(\s+Tambahan)?:\s*/i, '').trim();
            if (cleanNote) notesList.push(cleanNote);
        } else if (
            text.startsWith('Material diambil dari Surat Jalan') || 
            text === 'ONT:' || 
            text.startsWith('Alokasi Awal:') || 
            text.startsWith('Material Dikembalikan:') || 
            text.startsWith('Retur Gudang:')
        ) {
            // Abaikan header transaksi
        } else {
            if (text.toLowerCase().startsWith('catatan')) {
                const cleanNote = text.replace(/^catatan[^:]*:\s*/i, '').trim();
                if (cleanNote) notesList.push(cleanNote);
            } else {
                items.push({ id: idCounter++, type: 'Material', name: text, isInstalled: isScheduleDone });
            }
        }
    });
    hardwareItems.value = items;
    installationNotes.value = notesList.join('\n');
    
    // Restore hardware status from localStorage
    const savedHardware = localStorage.getItem(`apm_hardware_${props.customer.id}`);
    if (savedHardware) {
        try {
            const savedState = JSON.parse(savedHardware);
            hardwareItems.value.forEach(item => {
                const savedItem = savedState.find(s => s.id === item.id);
                if (savedItem && savedItem.isInstalled !== undefined) {
                    item.isInstalled = savedItem.isInstalled;
                }
                if (savedItem && savedItem.returned_qty) {
                    item.returned_qty = savedItem.returned_qty;
                }
                if (savedItem && savedItem.actual_qty !== undefined) {
                    item.actual_qty = savedItem.actual_qty;
                }
                if (savedItem && savedItem.isBroken) {
                    item.isBroken = savedItem.isBroken;
                    item.rmaNote = savedItem.rmaNote;
                }
            });
        } catch (e) {
            console.error('Failed to parse saved hardware state', e);
        }
    }
}, { immediate: true });

function toggleInstallItem(item) {
    item.isInstalled = !item.isInstalled;
    
    // Save to localStorage
    const savedState = hardwareItems.value.map(i => ({
        id: i.id,
        isInstalled: i.isInstalled,
        returned_qty: i.returned_qty,
        actual_qty: i.actual_qty,
        isBroken: i.isBroken,
        rmaNote: i.rmaNote
    }));
    localStorage.setItem(`apm_hardware_${props.customer.id}`, JSON.stringify(savedState));
}

// Request Material logic
const showRequestModal = ref(false);
const requestForm = useForm({
    items: [
        { material_id: '', quantity: '' }
    ],
    notes: ''
});

function addRequestItem() {
    requestForm.items.push({ material_id: '', quantity: '' });
}

function removeRequestItem(index) {
    if (requestForm.items.length > 1) {
        requestForm.items.splice(index, 1);
    }
}

function submitMaterialRequest() {
    requestForm.post(route('material-request', props.customer.id), {
        preserveScroll: true,
        onSuccess: () => {
            showRequestModal.value = false;
            requestForm.reset();
        }
    });
}

// Modal state for Install confirmation
const showInstallModal = ref(false);
const installModalItem = ref(null);
const installModalIsBroken = ref(false);
const installModalQty = ref(0);

const installModalUnit = computed(() => {
    if (!installModalItem.value) return '';
    const name = installModalItem.value.name;
    const match = name.match(/\((\d+(?:\.\d+)?)\s*([a-zA-Z]+)\)/);
    if (match) return match[2];
    if (name.toLowerCase().includes('kabel')) return 'meter';
    if (name.toLowerCase().includes('isolasi')) return 'cm';
    return 'pcs';
});

const installModalReturnedCalc = computed(() => {
    if (!installModalItem.value || installModalItem.value.type !== 'Material') return 0;
    const match = installModalItem.value.name.match(/\((\d+(?:\.\d+)?)\s/);
    const assignedQty = match ? parseFloat(match[1]) : 0;
    const actual = parseFloat(installModalQty.value) || 0;
    return Math.max(0, Math.round((assignedQty - actual) * 100) / 100);
});

function getMaterialUsageInfo(item) {
    if (!item || item.type !== 'Material') return null;
    const match = item.name.match(/\((\d+(?:\.\d+)?)\s/);
    const assignedQty = match ? parseFloat(match[1]) : 0;
    
    const uMatch = item.name.match(/\((\d+(?:\.\d+)?)\s*([a-zA-Z]+)\)/);
    let unit = 'pcs';
    if (uMatch) unit = uMatch[2];
    else if (item.name.toLowerCase().includes('kabel')) unit = 'meter';
    else if (item.name.toLowerCase().includes('isolasi')) unit = 'cm';
    
    let usedQty = 0;
    if (item.isInstalled) {
        if (item.actual_qty !== undefined && item.actual_qty !== null) {
            usedQty = parseFloat(item.actual_qty);
        } else if (item.returned_qty !== undefined && item.returned_qty !== null) {
            usedQty = Math.max(0, assignedQty - parseFloat(item.returned_qty));
        } else {
            usedQty = assignedQty;
        }
    }
    const returnedQty = Math.max(0, Math.round((assignedQty - usedQty) * 100) / 100);
    
    return {
        assignedQty,
        usedQty,
        returnedQty,
        unit,
        isPartial: item.isInstalled && returnedQty > 0
    };
}

const materialHandoverSummary = computed(() => {
    const installed = [];
    const returned = [];
    
    hardwareItems.value.forEach(item => {
        if (item.type === 'Material') {
            const info = getMaterialUsageInfo(item);
            if (!info) return;
            if (item.isInstalled && info.usedQty > 0) {
                installed.push({
                    name: item.name.replace(/\(.*?\)/, '').trim(),
                    qty: info.usedQty,
                    unit: info.unit
                });
            }
            if (item.isInstalled && info.returnedQty > 0) {
                returned.push({
                    name: item.name.replace(/\(.*?\)/, '').trim(),
                    qty: info.returnedQty,
                    unit: info.unit
                });
            }
        } else if (item.type === 'ONT' && item.isInstalled) {
            if (item.isBroken) {
                returned.push({
                    name: item.name,
                    qty: 1,
                    unit: 'unit (Rusak/RMA)'
                });
            } else {
                installed.push({
                    name: item.name,
                    qty: 1,
                    unit: 'unit'
                });
            }
        }
    });
    
    return {
        installed,
        returned,
        hasInstalled: installed.length > 0,
        hasReturned: returned.length > 0
    };
});

function openInstallModal(item) {
    if (item.isInstalled) {
        // If already installed, just toggle off (uninstall)
        toggleInstallItem(item);
        return;
    }
    installModalItem.value = item;
    installModalIsBroken.value = false;
    
    // Auto prefill default quantity from item.name (e.g. "Klem (50 pcs)" -> 50, "Kabel Frecon 150m (150 meter)" -> 150)
    const match = item.name.match(/\((\d+(?:\.\d+)?)\s/);
    const defaultQty = match ? parseFloat(match[1]) : 1;
    installModalQty.value = item.actual_qty !== undefined ? item.actual_qty : defaultQty;
    
    showInstallModal.value = true;
}

function confirmInstall() {
    const item = installModalItem.value;
    if (!item) return;

    if (item.type === 'ONT' && installModalIsBroken.value) {
        // Mark as installed but broken (RMA) — store status for later
        item.isBroken = true;
        item.rmaNote = 'ONT Rusak - RMA';
    }

    if (item.type === 'Material') {
        item.actual_qty = parseFloat(installModalQty.value) || 0;
    }

    toggleInstallItem(item);
    showInstallModal.value = false;
    installModalItem.value = null;
}

const isInstallingHardware = computed(() => {
    // Show hardware form if there are no items, or if at least one item is marked as installed.
    if (hardwareItems.value.length === 0) return true;
    return hardwareItems.value.some(item => item.isInstalled);
});

function openImage(src, label) {
    previewImage.value = { src, label };
}

const isEditingOnt = ref(false);
const isEditingInstallation = ref(false);

function editInstallation() {
    isEditingInstallation.value = true;
    activeTab.value = 'installation';
    
    // Populate form with existing data
    if (props.customer.ont) {
        ontForm.odp_id = props.customer.ont.odp_id;
        ontForm.port_number = props.customer.ont.port_number;
        ontForm.rx_power = props.customer.ont.rx_power;
        ontForm.start_time = props.customer.ont.start_time ? props.customer.ont.start_time.substring(0, 5) : '';
        ontForm.end_time = props.customer.ont.end_time ? props.customer.ont.end_time.substring(0, 5) : '';
    }
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

function compressAndSaveImage(field, file) {
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (event) => {
        const img = new Image();
        img.onload = () => {
            const canvas = document.createElement('canvas');
            let width = img.width;
            let height = img.height;
            const max_size = 1200;

            if (width > height) {
                if (width > max_size) {
                    height *= max_size / width;
                    width = max_size;
                }
            } else {
                if (height > max_size) {
                    width *= max_size / height;
                    height = max_size;
                }
            }
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, width, height);
            
            const dataUrl = canvas.toDataURL('image/jpeg', 0.7);
            try {
                localStorage.setItem(`apm_${field}_${props.customer.id}`, dataUrl);
            } catch (e) {
                console.error("LocalStorage full!", e);
            }
        };
        img.src = event.target.result;
    };
    reader.readAsDataURL(file);
}

function dataURLtoFile(dataurl, filename) {
    let arr = dataurl.split(','), mime = arr[0].match(/:(.*?);/)[1],
        bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
    while(n--){
        u8arr[n] = bstr.charCodeAt(n);
    }
    return new File([u8arr], filename, {type:mime});
}

function handleFileUpload(field, e) {
    const file = e.target.files[0];
    ontForm[field] = file;
    if (file) {
        if (previewUrls.value[field]) URL.revokeObjectURL(previewUrls.value[field]);
        previewUrls.value[field] = URL.createObjectURL(file);
        compressAndSaveImage(field, file);
    } else {
        previewUrls.value[field] = null;
        localStorage.removeItem(`apm_${field}_${props.customer.id}`);
    }
}

const ontForm = useForm({
    ont_id: '',
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
    materials_returned: [],
    material_usage: [],
    materials_used: [],
});

const selectedOdp = computed(() => props.availableOdps?.find(o => o.id == ontForm.odp_id));

const isJamMulaiValid = computed(() => !!ontForm.start_time);
const isMaterialValid = computed(() => hardwareItems.value.length === 0 || hardwareItems.value.every(i => i.isInstalled));
const isOdpValid = computed(() => !!ontForm.odp_id && selectedOdp.value && selectedOdp.value.area_id === props.customer.area_id);
const isPortValid = computed(() => !!ontForm.port_number && !isPortUsed(selectedOdp.value, ontForm.port_number));
const isRedamanValid = computed(() => !!ontForm.rx_power && !isNaN(parseFloat(ontForm.rx_power)));
const isFotoValid = computed(() => true); // Foto tidak diwajibkan (bisa diedit belakangan)

const validationErrors = computed(() => {
    const errors = [];
    if (!isJamMulaiValid.value) errors.push('Jam Mulai Pekerjaan belum diisi.');
    if (!isMaterialValid.value) errors.push('Masih ada material wajib yang belum diselesaikan.');
    if (!isOdpValid.value) errors.push('ODP belum dipilih atau Area ODP tidak sesuai pelanggan.');
    if (!isPortValid.value) errors.push('Port ODP belum dipilih atau sudah digunakan.');
    if (!isRedamanValid.value) errors.push('Redaman belum diisi dengan angka valid.');
    return errors;
});

const isInstallationValid = computed(() => {
    return isJamMulaiValid.value && isMaterialValid.value && isOdpValid.value && isPortValid.value && isRedamanValid.value && isFotoValid.value;
});

const isReadyToSubmit = computed(() => {
    return isInstallationValid.value && !!ontForm.end_time;
});

const showConfirmModal = ref(false);

function handleSelesaiSekarang() {
    if (!isInstallationValid.value) {
        alert("Lengkapi seluruh data instalasi terlebih dahulu.");
        return;
    }
    
    // Validasi jam selesai vs jam mulai
    const now = new Date();
    const currentHrs = String(now.getHours()).padStart(2, '0');
    const currentMins = String(now.getMinutes()).padStart(2, '0');
    const currentTimeStr = `${currentHrs}:${currentMins}`;
    
    if (ontForm.start_time) {
        if (currentTimeStr < ontForm.start_time) {
            alert("Jam selesai tidak boleh lebih awal dari jam mulai.");
            return;
        }
    }
    
    setNow('end_time');
}

function confirmSubmit() {
    if (!isReadyToSubmit.value) return;
    showConfirmModal.value = true;
}

function executeSubmit() {
    showConfirmModal.value = false;
    submitOnt();
}

function getInstalledOntName() {
    const ontItem = hardwareItems.value.find(i => i.type === 'ONT');
    return ontItem ? ontItem.name : '-';
}

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

onMounted(() => {
    // Cek apakah ada waktu yang tersimpan di localStorage (jika user tidak sengaja menutup halaman)
    const savedStart = localStorage.getItem(`apm_start_time_${props.customer.id}`);
    if (savedStart && !ontForm.start_time) {
        ontForm.start_time = savedStart;
    }
    
    const savedEnd = localStorage.getItem(`apm_end_time_${props.customer.id}`);
    if (savedEnd && !ontForm.end_time) {
        ontForm.end_time = savedEnd;
    }
    
    let initialOdpId = '';
    const savedOdp = localStorage.getItem(`apm_odp_id_${props.customer.id}`);
    if (savedOdp) initialOdpId = Number(savedOdp);
    else if (props.customer.ont?.odp_id) initialOdpId = props.customer.ont?.odp_id;
    else if (latestSurvey.value?.odp_id) initialOdpId = latestSurvey.value.odp_id;
    
    // Ensure initialOdpId matches the area
    if (initialOdpId) {
        const isValid = filteredAvailableOdps.value.some(o => o.id == initialOdpId);
        if (isValid) {
            ontForm.odp_id = initialOdpId;
        } else {
            localStorage.removeItem(`apm_odp_id_${props.customer.id}`);
        }
    }
    
    const savedPort = localStorage.getItem(`apm_port_number_${props.customer.id}`);
    if (savedPort && ontForm.odp_id) ontForm.port_number = Number(savedPort);
    else if (props.customer.ont?.port_number && ontForm.odp_id == props.customer.ont?.odp_id) ontForm.port_number = props.customer.ont?.port_number;
    else if (latestSurvey.value?.port_number && ontForm.odp_id == latestSurvey.value?.odp_id) ontForm.port_number = latestSurvey.value.port_number;
    
    const savedRx = localStorage.getItem(`apm_rx_power_${props.customer.id}`);
    if (savedRx) ontForm.rx_power = savedRx;
    
    // Restore photos
    const photoFields = ['photo_odp', 'photo_installation', 'photo_ont', 'photo_customer', 'photo_redaman'];
    photoFields.forEach(field => {
        const savedData = localStorage.getItem(`apm_${field}_${props.customer.id}`);
        if (savedData) {
            const file = dataURLtoFile(savedData, field + '.jpg');
            ontForm[field] = file;
            previewUrls.value[field] = URL.createObjectURL(file);
        }
    });
});

// Watch input fields and save to localStorage
watch(() => ontForm.odp_id, (val) => {
    if (val) localStorage.setItem(`apm_odp_id_${props.customer.id}`, val);
    else localStorage.removeItem(`apm_odp_id_${props.customer.id}`);
});
watch(() => ontForm.port_number, (val) => {
    if (val) localStorage.setItem(`apm_port_number_${props.customer.id}`, val);
    else localStorage.removeItem(`apm_port_number_${props.customer.id}`);
});
watch(() => ontForm.rx_power, (val) => {
    if (val) localStorage.setItem(`apm_rx_power_${props.customer.id}`, val);
    else localStorage.removeItem(`apm_rx_power_${props.customer.id}`);
});

watch(hardwareItems, (items) => {
    const savedState = items.map(i => ({
        id: i.id,
        isInstalled: i.isInstalled,
        returned_qty: i.returned_qty,
        actual_qty: i.actual_qty,
        isBroken: i.isBroken,
        rmaNote: i.rmaNote
    }));
    localStorage.setItem(`apm_hardware_${props.customer.id}`, JSON.stringify(savedState));
}, { deep: true });

function setNow(field) {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const timeString = `${hours}:${minutes}`;
    ontForm[field] = timeString;
    
    // Simpan ke localStorage agar tidak hilang kalau direfresh
    localStorage.setItem(`apm_${field}_${props.customer.id}`, timeString);
}

function submitOnt() {
    // Kumpulkan seluruh data pemakaian & sisa material yang ditugaskan
    const materialPayload = [];
    hardwareItems.value.forEach(item => {
        if (item.type === 'Material') {
            const match = item.name.match(/\((\d+(?:\.\d+)?)\s/);
            const originalQty = match ? parseFloat(match[1]) : 0;
            
            let usedQty = 0;
            let returnedQty = 0;
            
            if (item.isInstalled) {
                if (item.actual_qty !== undefined && item.actual_qty !== null) {
                    usedQty = parseFloat(item.actual_qty);
                } else if (item.returned_qty !== undefined && item.returned_qty !== null) {
                    usedQty = Math.max(0, originalQty - parseFloat(item.returned_qty));
                } else {
                    // Jika dipasang tanpa edit jumlah, default terpakai semua
                    usedQty = originalQty;
                }
                returnedQty = Math.max(0, originalQty - usedQty);
            } else {
                // Tidak dipasang sama sekali -> terpakai 0, dikembalikan semua
                usedQty = 0;
                returnedQty = originalQty;
            }
            
            materialPayload.push({
                name: item.name,
                assigned_qty: originalQty,
                used_qty: usedQty,
                returned_qty: returnedQty,
                is_installed: item.isInstalled
            });
        }
    });

    // materials_returned berisi seluruh material untuk diproses oleh backend (pemakaian & retur sisa)
    ontForm.materials_returned = materialPayload;

    // Collect used materials
    ontForm.materials_used = materialPayload
        .filter(i => i.is_installed && i.used_qty > 0)
        .map(i => ({
            name: i.name,
            actual_qty: i.used_qty
        }));

    // Collect material usage for updating schedule notes
    ontForm.material_usage = materialPayload
        .filter(i => i.is_installed && i.used_qty > 0)
        .map(i => ({
            name: i.name,
            type: 'Material',
            actual_qty: i.used_qty
        }));
        
        // Sanitasi rx_power sebelum dikirim ke backend
    if (ontForm.rx_power) {
        let clean = ontForm.rx_power.toString().replace(/dBm|dbm|\s/g, '').replace(',', '.');
        const num = parseFloat(clean);
        if (!isNaN(num)) {
            const finalVal = (num > 0 && num <= 40) ? -num : num;
            ontForm.rx_power = finalVal.toFixed(2);
        }
    }

    ontForm.post(`/customers/${props.customer.id}/assign-ont`, {
        preserveScroll: true,
        onSuccess: () => {
            // Bersihkan localStorage kalau sudah berhasil submit
            localStorage.removeItem(`apm_start_time_${props.customer.id}`);
            localStorage.removeItem(`apm_end_time_${props.customer.id}`);
            localStorage.removeItem(`apm_odp_id_${props.customer.id}`);
            localStorage.removeItem(`apm_port_number_${props.customer.id}`);
            localStorage.removeItem(`apm_rx_power_${props.customer.id}`);
            localStorage.removeItem(`apm_hardware_${props.customer.id}`);
            localStorage.removeItem(`apm_materials_used_${props.customer.id}`);
            
            const photoFields = ['photo_odp', 'photo_installation', 'photo_ont', 'photo_customer', 'photo_redaman'];
            photoFields.forEach(field => localStorage.removeItem(`apm_${field}_${props.customer.id}`));
            
            isEditingInstallation.value = false;
            activeTab.value = 'audit';

            Swal.fire({
                icon: 'success',
                title: 'Laporan Berhasil Disimpan!',
                text: 'Data instalasi telah tersimpan. Silakan lanjutkan dengan proses Audit.',
                timer: 2000,
                showConfirmButton: false
            });
        },
        onError: (errors) => {
            const errorMsg = Object.values(errors).flat().join('<br>');
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan Laporan',
                html: errorMsg || 'Terjadi kesalahan saat memproses laporan instalasi. Silakan periksa kembali kelengkapan data Anda.',
            });
        }
    });
}

const showAuditModal = ref(false);
const auditForm = useForm({
    notes: ''
});

function submitAudit() {
    auditForm.post(`/customers/${props.customer.id}/audit`, {
        preserveScroll: true,
        onSuccess: () => {
            showAuditModal.value = false;
        }
    });
}

const defaultBillingType = computed(() => {
    const t = usePage().props.billingSettings?.billing_type;
    return (t === 'pascabayar') ? 'pascabayar' : 'prabayar';
});

const formatBillingName = (type) => {
    if (type === 'pascabayar') return 'Pasca Bayar';
    return 'Prabayar';
};

const formatRupiah = (val) => {
    return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
};

const formatDateShort = (d) => {
    if (!d) return '-';
    try {
        const dateObj = typeof d === 'string' ? new Date(d) : d;
        return dateObj.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch (e) {
        return String(d);
    }
};

const showActivationModal = ref(false);

const custPpn = props.customer?.tax_ppn !== null && props.customer?.tax_ppn !== undefined ? Number(props.customer.tax_ppn) : null;
const globalPpn = Number(usePage().props.billingSettings?.tax_ppn || usePage().props.taxSettings?.tax_ppn || 11);

let initialTaxEnabled = true;
if (custPpn !== null && custPpn === 0) {
    initialTaxEnabled = false;
} else if (custPpn !== null && custPpn > 0) {
    initialTaxEnabled = true;
} else {
    initialTaxEnabled = (globalPpn > 0);
}

const activationForm = useForm({
    activation_date: new Date().toISOString().split('T')[0],
    billing_type: props.customer?.billing_type || defaultBillingType.value || 'prabayar',
    is_prorata: usePage().props.billingSettings?.prorata_first_month !== false,
    service_status: props.customer?.service_status || 'berbayar',
    enable_tax: initialTaxEnabled,
    pppoe_user: props.customer?.ont?.pppoe_user || '',
    pppoe_password: props.customer?.ont?.pppoe_password || '',
    vlan_mode: props.customer?.ont?.vlan_mode || '',
    vlan_id: props.customer?.ont?.vlan_id || '',
    access_mode: props.customer?.ont?.access_mode || 'PPPOE',
    free_hotspot: props.customer?.ont?.free_hotspot || false,
    hotspot_user: props.customer?.ont?.hotspot_user || '',
    hotspot_password: props.customer?.ont?.hotspot_password || '',
    hotspot_vlan_id: props.customer?.ont?.hotspot_vlan_id || '',
    ip_login: props.customer?.ont?.ip_login || '192.168.1.1',
    login_user: props.customer?.ont?.login_user || 'admin',
    login_password: props.customer?.ont?.login_password || 'admin',
    notes: '',
    tax_ppn: custPpn !== null ? custPpn : (globalPpn || 11),
    tax_bhp: 0,
    tax_uso: 0,
});

watch(() => activationForm.enable_tax, (val) => {
    if (val && !activationForm.tax_ppn) {
        activationForm.tax_ppn = globalPpn || 11;
    }
});

const invoiceSimulation = computed(() => {
    const cust = props.customer;
    if (!cust) return null;

    const basePrice = Number(cust.base_amount || cust.package?.price || 0);
    if (activationForm.service_status === 'free_home') return null;
    const isGratis = activationForm.service_status === 'gratis';
    const bType = activationForm.billing_type || 'prabayar';
    const isProrataChecked = bType === 'pascabayar' && Boolean(activationForm.is_prorata);

    const bSettings = usePage().props.billingSettings || {};
    const issueDateSetting = Math.max(1, Math.min(28, Number(bSettings.invoice_issue_date || 1)));
    const prorataFormula = bSettings.prorata_formula || 'exact_days';

    // Parse activation date safely (local date)
    let actDate = new Date();
    if (activationForm.activation_date) {
        const parts = activationForm.activation_date.split('-');
        if (parts.length === 3) {
            actDate = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
        }
    }

    // Tanggal akhir bulan berjalan (misal 31 Okt 2026):
    const endOfMonthDate = new Date(actDate.getFullYear(), actDate.getMonth() + 1, 0);
    const totalDaysInMonth = endOfMonthDate.getDate();

    let subtotal = basePrice;
    let daysUsed = 0;
    let totalDaysInCycle = totalDaysInMonth;
    let nextCutOffDate = null;
    let dueDate = null;
    let formulaDesc = '';
    let isProrataApplied = false;
    let periodText = '';
    let badgeLabel = 'Prabayar (Bayar di Muka)';

    if (bType === 'pascabayar') {
        // Tanggal cut-off tagihan bulan berikutnya (misal 1 Nov 2026):
        const actYear = actDate.getFullYear();
        const actMonth = actDate.getMonth();
        const actDay = actDate.getDate();

        if (actDay >= issueDateSetting) {
            nextCutOffDate = new Date(actYear, actMonth + 1, issueDateSetting);
        } else {
            nextCutOffDate = new Date(actYear, actMonth, issueDateSetting);
        }

        // Hari aktif dihitung dari tanggal aktivasi sampai akhir bulan berjalan (inklusif):
        daysUsed = Math.max(1, endOfMonthDate.getDate() - actDate.getDate() + 1);

        if (isProrataChecked && basePrice > 0 && daysUsed > 0 && daysUsed < totalDaysInCycle) {
            isProrataApplied = true;
            badgeLabel = 'Pascabayar - Prorata (1x)';
            if (prorataFormula === 'fixed_30') {
                subtotal = Math.round((basePrice / 30) * daysUsed);
                formulaDesc = `(${formatRupiah(basePrice)} / 30 hr) × ${daysUsed} hr`;
            } else if (prorataFormula === 'mid_month') {
                if (actDate.getDate() > 15) {
                    subtotal = Math.round(basePrice / 2);
                    formulaDesc = `Aktivasi > tengah bulan (50%)`;
                } else {
                    subtotal = basePrice;
                    formulaDesc = `Aktivasi awal bulan (100% normal)`;
                }
            } else {
                // exact_days: proporsional hari dalam bulan berjalan
                subtotal = Math.round((basePrice / totalDaysInCycle) * daysUsed);
                formulaDesc = `(${formatRupiah(basePrice)} / ${totalDaysInCycle} hr) × ${daysUsed} hr`;
            }
            periodText = `${formatDateShort(actDate)} s/d ${formatDateShort(endOfMonthDate)} (${daysUsed} hari aktif - Akhir Bulan)`;
        } else {
            badgeLabel = 'Pascabayar Normal (1 Bulan Penuh)';
            subtotal = basePrice;
            periodText = `1 Bulan Penuh (Cut-off tgl ${issueDateSetting} ${nextCutOffDate.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' })})`;
        }

        dueDate = billingIsolationDate(nextCutOffDate, bSettings);
    } else {
        badgeLabel = 'Prabayar (Bayar di Muka)';
        subtotal = basePrice;
        const endDate = new Date(actDate.getFullYear(), actDate.getMonth() + 1, actDate.getDate());
        periodText = `${formatDateShort(actDate)} s/d ${formatDateShort(endDate)} (1 Bulan di Muka)`;
        dueDate = billingIsolationDate(actDate, bSettings);
    }

    if (isGratis) {
        badgeLabel = 'Layanan Gratis (Rp 0)';
    }

    // Taxes (Hanya PPN yang ditagihkan ke invoice pelanggan)
    const isTaxEnabled = activationForm.enable_tax;
    const taxPpn = isTaxEnabled ? Number(activationForm.tax_ppn || 0) : 0;
    const totalTaxPercent = taxPpn;

    let taxAmount = 0;
    let totalAmount = subtotal;

    if (isGratis) {
        subtotal = 0;
        taxAmount = 0;
        totalAmount = 0;
    } else if (totalTaxPercent > 0) {
        taxAmount = Math.round(subtotal * (totalTaxPercent / 100));
        totalAmount = subtotal + taxAmount;
    }

    // Tax breakdown text
    const taxBreakdownText = `PPN ${taxPpn}%`;

    // Next month preview (normal postpaid)
    let nextMonthNormalSubtotal = basePrice;
    let nextMonthNormalTax = totalTaxPercent > 0 ? Math.round(basePrice * (totalTaxPercent / 100)) : 0;
    let nextMonthNormalTotal = isGratis ? 0 : (nextMonthNormalSubtotal + nextMonthNormalTax);
    let nextBillingMonthName = nextCutOffDate ? nextCutOffDate.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }) : '';

    return {
        billingType: bType,
        isProrata: isProrataApplied,
        isGratis,
        badgeLabel,
        packageName: cust.package?.name || 'Paket Internet',
        basePrice,
        subtotal,
        formulaDesc,
        daysUsed,
        totalDaysInCycle,
        endOfMonthText: formatDateShort(endOfMonthDate),
        nextCutOffDate: nextCutOffDate ? formatDateShort(nextCutOffDate) : null,
        periodText,
        dueDate: dueDate ? `${formatDateShort(dueDate)} ${bSettings.isolate_time || '00:00'} WIB` : null,
        taxPpn,
        taxBhp: 0,
        taxUso: 0,
        totalTaxPercent,
        taxBreakdownText,
        taxAmount,
        totalAmount,
        issueDateSetting,
        nextBillingMonthName,
        nextMonthNormalTotal,
    };
});

const isSavingOnt = ref(false);

function saveOntData() {
    isSavingOnt.value = true;
    activationForm.post(`/customers/${props.customer.id}/update-ont-inline`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            isEditingOnt.value = false;
            isSavingOnt.value = false;
        },
        onError: () => {
            isSavingOnt.value = false;
        }
    });
}

function submitActivation() {
    activationForm.post(`/customers/${props.customer.id}/activate`, {
        preserveScroll: true,
        onSuccess: () => {
            showActivationModal.value = false;
        }
    });
}

// ── Edit Active Configuration ───────────────────────────────
const showEditActiveModal = ref(false);
const activeConfigForm = useForm({
    ip_login: '',
    login_user: '',
    login_password: '',
    pppoe_user: '',
    pppoe_password: '',
    vlan_mode: '',
    vlan_id: '',
    access_mode: 'PPPOE',
    free_hotspot: false,
    hotspot_user: '',
    hotspot_password: '',
    hotspot_vlan_id: '',
});

function openEditActiveModal() {
    if (props.customer.ont) {
        activeConfigForm.ip_login = props.customer.ont?.ip_login || '';
        activeConfigForm.login_user = props.customer.ont?.login_user || '';
        activeConfigForm.login_password = props.customer.ont?.login_password || '';
        activeConfigForm.pppoe_user = props.customer.ont?.pppoe_user || '';
        activeConfigForm.pppoe_password = props.customer.ont?.pppoe_password || '';
        activeConfigForm.vlan_mode = props.customer.ont?.vlan_mode || '';
        activeConfigForm.vlan_id = props.customer.ont?.vlan_id || '';
        activeConfigForm.access_mode = props.customer.ont?.access_mode || 'PPPOE';
        activeConfigForm.free_hotspot = props.customer.ont?.free_hotspot || false;
        activeConfigForm.hotspot_user = props.customer.ont?.hotspot_user || '';
        activeConfigForm.hotspot_password = props.customer.ont?.hotspot_password || '';
        activeConfigForm.hotspot_vlan_id = props.customer.ont?.hotspot_vlan_id || '';
    }
    showEditActiveModal.value = true;
}

function submitEditActive() {
    activeConfigForm.post(`/customers/${props.customer.id}/update-ont-inline`, {
        preserveScroll: true,
        onSuccess: () => {
            showEditActiveModal.value = false;
            // router.reload doesn't automatically close toast so we rely on global flash
        }
    });
}

// ── Buat Akun ───────────────────────────────────────────────
const showAccountModal = ref(false);
const accountForm = useForm({
    email: '',
    password: ''
});

function openAccountModal() {
    accountForm.email = props.customer.email || (props.customer.customer_code ? props.customer.customer_code.toLowerCase().replace(/[^a-z0-9]/g, '') + '@customer.apm' : '');
    accountForm.password = '';
    accountForm.clearErrors();
    showAccountModal.value = true;
}

function submitAccount() {
    accountForm.post(`/customers/${props.customer.id}/create-account`, {
        preserveScroll: true,
        onSuccess: () => {
            showAccountModal.value = false;
            accountForm.reset();
        },
        onError: (errors) => {
            const msg = Object.values(errors).flat().join('<br>');
            if (typeof window !== 'undefined' && window.Swal) {
                window.Swal.fire({
                    icon: 'error',
                    title: 'Gagal Membuat Akun',
                    html: msg || 'Terjadi kesalahan saat membuat akun.',
                });
            }
        }
    });
}

// ── Ubah Password ───────────────────────────────────────────
const showResetPasswordModal = ref(false);
const resetPasswordForm = useForm({
    email: '',
    password: ''
});

function submitResetPassword() {
    resetPasswordForm.post(`/customers/${props.customer.id}/reset-password`, {
        preserveScroll: true,
        onSuccess: () => {
            showResetPasswordModal.value = false;
            resetPasswordForm.reset();
        },
        onError: (errors) => {
            const msg = Object.values(errors).flat().join('<br>');
            if (typeof window !== 'undefined' && window.Swal) {
                window.Swal.fire({
                    icon: 'error',
                    title: 'Gagal Ubah Password',
                    html: msg || 'Terjadi kesalahan saat mengubah password.',
                });
            }
        }
    });
}

function openResetPasswordModal() {
    resetPasswordForm.email = props.customer.user ? props.customer.user.email : props.customer.email || '';
    resetPasswordForm.password = '';
    resetPasswordForm.clearErrors();
    showResetPasswordModal.value = true;
}

// ── Stop Permanen ───────────────────────────────────────────
const showTerminateModal = ref(false);
const terminateForm = useForm({
    cancel_date: new Date().toISOString().split('T')[0],
    cancel_reason: '',
    notes: '',
});

const customerActiveTenure = computed(() => {
    if (!props.customer) return null;
    const startStr = props.customer.activation_date || props.customer.registration_date || props.customer.created_at;
    if (!startStr) return null;
    const start = new Date(startStr);
    const end = new Date(terminateForm.cancel_date || new Date());
    const diffTime = Math.max(0, end.getTime() - start.getTime());
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
    const months = Math.floor(diffDays / 30);
    const days = diffDays % 30;
    const isUnder4 = months < 4;

    // Deteksi Surveyor Teknisi
    const lastSurvey = props.customer.surveys && props.customer.surveys.length 
        ? props.customer.surveys[props.customer.surveys.length - 1] 
        : null;
    const surveySchedule = (props.customer.technician_schedules || props.customer.technicianSchedules || [])
        .find(s => s.type === 'survey');
    
    const surveyorName = lastSurvey?.surveyor?.name || surveySchedule?.technician?.name || null;
    const hasSurveyor = !!surveyorName;

    return {
        diffDays,
        months,
        days,
        isUnder4,
        text: months > 0 ? `${months} bulan ${days} hari` : `${diffDays} hari`,
        hasSales: !!props.customer.sales_id,
        salesName: props.customer.sales?.name || (props.customer.sales_id ? 'Sales #' + props.customer.sales_id : null),
        hasSurveyor,
        surveyorName,
    };
});

function submitTerminate() {
    terminateForm.post(`/customers/${props.customer.id}/terminate`, {
        preserveScroll: true,
        onSuccess: () => {
            showTerminateModal.value = false;
        }
    });
}

// ============================================================
// 6 TAHAP LIFECYCLE INLINE EDIT HANDLERS (PERSIS FORM PELAKSANAAN)
// ============================================================

// ── 1. MODAL EDIT BOOKING & LOKASI ──────────────────────────
const showEditBookingModal = ref(false);
const bookingForm = useForm({
    name: '',
    phone: '',
    email: '',
    latitude: '',
    longitude: '',
    notes: '',
    registration_date: '',
    sales_id: '',
    package_id: '',
    area_id: '',
    address: '',
});

function openEditBookingModal() {
    bookingForm.name = props.customer.name || '';
    bookingForm.phone = props.customer.phone || '';
    bookingForm.email = props.customer.email || '';
    bookingForm.latitude = props.customer.latitude || '';
    bookingForm.longitude = props.customer.longitude || '';
    bookingForm.notes = props.customer.notes || '';
    bookingForm.registration_date = props.customer.registration_date ? props.customer.registration_date.split('T')[0] : '';
    bookingForm.sales_id = props.customer.sales_id || '';
    bookingForm.package_id = props.customer.package_id || '';
    bookingForm.area_id = props.customer.area_id || '';
    bookingForm.address = props.customer.address || '';
    showEditBookingModal.value = true;
}

function getBookingLocation() {
    if (!navigator.geolocation) {
        alert('Geolocation tidak didukung oleh browser Anda.');
        return;
    }
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            bookingForm.latitude = pos.coords.latitude.toFixed(7);
            bookingForm.longitude = pos.coords.longitude.toFixed(7);
        },
        (err) => {
            alert('Gagal mengambil titik GPS: ' + err.message);
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

function submitEditBooking() {
    bookingForm.post(`/customers/${props.customer.id}/update-booking-inline`, {
        preserveScroll: true,
        onSuccess: () => {
            showEditBookingModal.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Data Booking Diperbarui!',
                timer: 2000,
                showConfirmButton: false
            });
        },
        onError: (errors) => {
            if (errors.email) {
                Swal.fire({
                    icon: 'error',
                    title: 'Email Sudah Terdaftar',
                    text: errors.email,
                });
            }
        }
    });
}

// ── 2. MODAL EDIT JADWAL SURVEY ─────────────────────────────
const showEditSurveyScheduleModal = ref(false);
const surveyScheduleForm = useForm({
    technician_id: '',
    scheduled_date: '',
    scheduled_time: '09:00',
    status: 'scheduled',
    notes: '',
});

function openEditSurveyScheduleModal() {
    const sched = props.customer.technician_schedules?.find(s => s.type === 'survey');
    surveyScheduleForm.technician_id = sched?.technician_id || (props.customer.surveys?.[0]?.surveyor_id || (props.technicians?.[0]?.id || ''));
    surveyScheduleForm.scheduled_date = sched?.scheduled_date ? sched.scheduled_date.split('T')[0] : (props.customer.registration_date ? props.customer.registration_date.split('T')[0] : new Date().toISOString().split('T')[0]);
    surveyScheduleForm.scheduled_time = sched?.scheduled_time ? sched.scheduled_time.substring(0, 5) : '09:00';
    surveyScheduleForm.status = sched?.status || 'scheduled';
    surveyScheduleForm.notes = sched?.notes || 'Jadwal survey lapangan';
    showEditSurveyScheduleModal.value = true;
}

function submitEditSurveySchedule() {
    surveyScheduleForm.post(`/customers/${props.customer.id}/update-survey-schedule`, {
        preserveScroll: true,
        onSuccess: () => {
            showEditSurveyScheduleModal.value = false;
        }
    });
}

// ── 3. MODAL EDIT LAPORAN SURVEY ────────────────────────────
const showEditSurveyReportModal = ref(false);
const isFindingSurveyOdp = ref(false);
const surveyOdpMsg = ref('');
const surveyReportPhotos = ref([]);

const surveyReportForm = useForm({
    surveyor_id: '',
    survey_date: '',
    odp_id: '',
    port_number: 1,
    distance_meters: 50,
    signal_loss: '',
    port_available: true,
    feasibility: 'feasible',
    notes: '',
    photos: [],
});

function openEditSurveyReportModal() {
    const s = props.customer.surveys?.[0];
    surveyReportForm.surveyor_id = s?.surveyor_id || (props.customer.technician_schedules?.find(ts => ts.type === 'survey')?.technician_id || (props.technicians?.[0]?.id || ''));
    surveyReportForm.survey_date = s?.survey_date ? s.survey_date.split('T')[0] : (props.customer.registration_date ? props.customer.registration_date.split('T')[0] : new Date().toISOString().split('T')[0]);
    surveyReportForm.odp_id = s?.odp_id || (props.customer.ont?.odp_id || (filteredAvailableOdps.value?.[0]?.id || ''));
    surveyReportForm.port_number = s?.port_number || 1;
    surveyReportForm.distance_meters = s?.distance_meters || 50;
    surveyReportForm.signal_loss = s?.signal_loss || '';
    surveyReportForm.port_available = s ? Boolean(s.port_available) : true;
    surveyReportForm.feasibility = s?.feasibility || 'feasible';
    surveyReportForm.notes = s?.notes || 'Hasil survey lapangan';
    surveyOdpMsg.value = '';

    if (s?.photos && s.photos.length > 0) {
        surveyReportPhotos.value = s.photos.map(p => ({
            label: p.label || 'Dokumentasi Survey',
            file: null,
            previewUrl: p.path ? '/storage/' + p.path : null
        }));
    } else {
        surveyReportPhotos.value = [
            { label: 'Foto Selfie Pelanggan & Petugas', file: null, previewUrl: null },
            { label: 'Foto Calon ODP', file: null, previewUrl: null },
            { label: 'Foto Tiang / Jalur Kabel', file: null, previewUrl: null }
        ];
    }
    showEditSurveyReportModal.value = true;
}

function addSurveyReportPhoto() {
    surveyReportPhotos.value.push({ label: 'Foto Tambahan', file: null, previewUrl: null });
}

function removeSurveyReportPhoto(index) {
    surveyReportPhotos.value.splice(index, 1);
}

function handleSurveyReportPhotoChange(index, event) {
    const file = event.target.files[0];
    surveyReportPhotos.value[index].file = file;
    if (file) {
        surveyReportPhotos.value[index].previewUrl = URL.createObjectURL(file);
    }
}

function findNearestSurveyOdp() {
    isFindingSurveyOdp.value = true;
    surveyOdpMsg.value = '';

    const lat = props.customer.latitude;
    const lng = props.customer.longitude;

    if (!lat || !lng) {
        if (!navigator.geolocation) {
            alert('Titik koordinat pelanggan belum ada dan Geolocation tidak didukung.');
            isFindingSurveyOdp.value = false;
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (pos) => calculateNearestOdp(pos.coords.latitude, pos.coords.longitude),
            () => {
                alert('Gagal mengambil titik GPS.');
                isFindingSurveyOdp.value = false;
            }
        );
        return;
    }

    calculateNearestOdp(parseFloat(lat), parseFloat(lng));
}

function calculateNearestOdp(userLat, userLon) {
    if (!filteredAvailableOdps.value || filteredAvailableOdps.value.length === 0) {
        surveyOdpMsg.value = '⚠️ Tidak ada ODP terdaftar pada area ini.';
        isFindingSurveyOdp.value = false;
        return;
    }

    let nearest = null;
    let minDistance = 9999999;

    filteredAvailableOdps.value.forEach(odp => {
        if (odp.latitude && odp.longitude) {
            const d = calculateDistance(userLat, userLon, parseFloat(odp.latitude), parseFloat(odp.longitude));
            if (d < minDistance) {
                minDistance = d;
                nearest = odp;
            }
        }
    });

    if (nearest) {
        surveyReportForm.odp_id = nearest.id;
        surveyReportForm.distance_meters = Math.round(minDistance);
        surveyOdpMsg.value = `📍 ODP Terdekat: ${nearest.name} (±${Math.round(minDistance)} meter)`;
    } else {
        surveyOdpMsg.value = '⚠️ Koordinat ODP belum diset pada area ini.';
    }
    isFindingSurveyOdp.value = false;
}

function submitEditSurveyReport() {
    surveyReportForm.photos = surveyReportPhotos.value.map(p => ({
        label: p.label,
        file: p.file
    }));

    surveyReportForm.post(`/customers/${props.customer.id}/update-survey-report`, {
        preserveScroll: true,
        onSuccess: () => {
            showEditSurveyReportModal.value = false;
        }
    });
}

// ── 4. MODAL EDIT JADWAL PASANG & MATERIAL ──────────────────
const showEditInstallScheduleModal = ref(false);
const installScheduleForm = useForm({
    technician_id: '',
    scheduled_date: '',
    scheduled_time: '10:00',
    status: 'scheduled',
    notes: '',
    ont_id: '',
    material_items: [],
    send_wa: true,
});

const customerAreaId = computed(() => {
    if (props.customer?.area_id) return props.customer.area_id;
    if (props.customer?.area && props.areas) {
        const found = props.areas.find(a => a.name?.toLowerCase().trim() === props.customer.area.toLowerCase().trim());
        if (found) return found.id;
    }
    return null;
});

const filteredOnts = computed(() => {
    let onts = (props.availableOnts || []);
    const areaId = customerAreaId.value;
    if (!areaId) return onts;
    const matched = onts.filter(ont => ont.area_id == areaId);
    return matched.length > 0 ? matched : onts;
});

function getSelectedOntDetails(val) {
    if (!val) return null;
    return (props.availableOnts || []).find(ont => ont.id === val || String(ont.id) === String(val)) || props.customer?.ont;
}

const availableMaterialItems = computed(() => {
    let items = [];
    const trxs = props.materialTransactions || [];
    const areaId = customerAreaId.value;

    if (trxs && trxs.length > 0) {
        trxs.forEach(trx => {
            if (trx.items) {
                trx.items.forEach(item => {
                    const name = item.material?.name?.toLowerCase() || '';
                    const category = item.material?.category?.toLowerCase() || '';
                    const isRegistered = item.is_registered_to_ont === 1 || item.is_registered_to_ont === true;
                    
                    if (!name.includes('ont') && !name.includes('modem') && !category.includes('ont') && !category.includes('modem') && !isRegistered) {
                        let areaStock = 0;
                        if (item.material?.stocks && areaId) {
                            const stockObj = item.material.stocks.find(s => s.area_id == areaId);
                            if (stockObj) areaStock = parseFloat(stockObj.stock) || 0;
                        }
                        if (areaStock <= 0 && item.material && parseFloat(item.material.stock) > 0) {
                            areaStock = parseFloat(item.material.stock) || 0;
                        }
                        const availableQty = areaStock > 0 ? areaStock : (parseFloat(item.quantity) || 0);
                        if (availableQty > 0) {
                            items.push({
                                id: item.id,
                                material_id: item.material_id,
                                name: item.material?.name || 'Unknown',
                                trx_number: trx.transaction_number,
                                area_stock: availableQty,
                                unit: item.material ? item.material.unit : (item.unit || 'pcs'),
                                material: item.material,
                            });
                        }
                    }
                });
            }
        });
    }

    if (props.materials && props.materials.length > 0) {
        props.materials.forEach(mat => {
            const name = mat.name.toLowerCase();
            const category = (mat.category || '').toLowerCase();
            if (!name.includes('ont') && !name.includes('modem') && !category.includes('ont') && !category.includes('modem')) {
                if (!items.some(i => i.name.toLowerCase() === name)) {
                    let stock = parseFloat(mat.stock) || 100;
                    items.push({
                        id: 'mat-' + mat.id,
                        material_id: mat.id,
                        name: mat.name,
                        trx_number: 'STOK-UTAMA',
                        area_stock: stock,
                        unit: mat.unit || 'pcs',
                        material: mat,
                    });
                }
            }
        });
    }

    return items;
});

function addScheduleMaterialItem() {
    installScheduleForm.material_items.push({
        id: '',
        name: '',
        qty: 1,
        unit: 'pcs',
        trx_number: '',
        area_stock: 0
    });
}

function removeScheduleMaterialItem(index) {
    installScheduleForm.material_items.splice(index, 1);
}

function updateScheduleMaterialDetails(index) {
    const selectedId = installScheduleForm.material_items[index].id;
    if (!selectedId) return;

    const item = availableMaterialItems.value.find(i => String(i.id) === String(selectedId));
    if (item) {
        let qty = 1;
        let unit = item.unit || 'pcs';

        if (item.material?.category === 'Isolasi') {
            unit = 'cm';
            qty = 50;
        } else if (item.material?.category === 'Paku Klem') {
            unit = 'pcs';
            qty = 10;
        } else if (item.material?.category?.includes('Kabel') || item.name.toLowerCase().includes('kabel') || item.name.toLowerCase().includes('drop')) {
            unit = 'meter';
            qty = props.customer.surveys?.[0]?.distance_meters || 50;
        }

        installScheduleForm.material_items[index].name = item.name;
        installScheduleForm.material_items[index].qty = qty;
        installScheduleForm.material_items[index].unit = unit;
        installScheduleForm.material_items[index].trx_number = item.trx_number;
        installScheduleForm.material_items[index].area_stock = item.area_stock;
    }
}

function validateScheduleMaterialQty(index) {
    const item = installScheduleForm.material_items[index];
    if (item.id && item.area_stock !== undefined && item.area_stock > 0) {
        if (item.qty > item.area_stock) {
            item.qty = item.area_stock;
            alert(`Stok tidak mencukupi! Maksimal stok untuk material ini adalah ${item.area_stock} ${item.unit}`);
        }
    }
}

function openEditInstallScheduleModal() {
    const sched = props.customer.technician_schedules?.find(s => s.type === 'installation');
    installScheduleForm.technician_id = sched?.technician_id || (props.technicians?.[0]?.id || '');
    installScheduleForm.scheduled_date = sched?.scheduled_date ? sched.scheduled_date.split('T')[0] : (props.customer.activation_date ? props.customer.activation_date.split('T')[0] : new Date().toISOString().split('T')[0]);
    installScheduleForm.scheduled_time = sched?.scheduled_time ? sched.scheduled_time.substring(0, 5) : '10:00';
    installScheduleForm.status = sched?.status || 'scheduled';
    installScheduleForm.ont_id = props.customer.ont?.id || (filteredOnts.value?.[0]?.id || '');
    installScheduleForm.send_wa = true;

    // Parse existing notes or load materials
    const existingNotes = sched?.notes || '';
    const cleanNotes = [];
    const matItems = [];

    if (existingNotes) {
        const lines = existingNotes.split('\n');
        lines.forEach(line => {
            const trimmed = line.trim();
            if (trimmed.startsWith('Material: ')) {
                const matMatch = trimmed.match(/^Material:\s*(.+?)\s*\(([\d\.]+)\s*(.+?)\)$/i);
                if (matMatch) {
                    matItems.push({
                        id: 'custom-' + Math.random(),
                        name: matMatch[1].trim(),
                        qty: parseFloat(matMatch[2]) || 1,
                        unit: matMatch[3].trim(),
                        trx_number: '',
                        area_stock: 999
                    });
                } else {
                    const rawMat = trimmed.replace('Material: ', '');
                    matItems.push({
                        id: 'custom-' + Math.random(),
                        name: rawMat,
                        qty: 1,
                        unit: 'pcs',
                        trx_number: '',
                        area_stock: 999
                    });
                }
            } else if (!trimmed.startsWith('Material diambil dari Surat Jalan') && !trimmed.startsWith('ONT: ')) {
                if (trimmed.startsWith('Catatan Khusus: ')) {
                    cleanNotes.push(trimmed.replace('Catatan Khusus: ', ''));
                } else if (trimmed) {
                    cleanNotes.push(trimmed);
                }
            }
        });
    }

    if (matItems.length > 0) {
        installScheduleForm.material_items = matItems;
    } else {
        // Rekomendasi material standar awal instalasi jika belum ada
        installScheduleForm.material_items = [
            {
                id: '',
                name: 'Kabel Dropcore 1 Core',
                qty: props.customer.surveys?.[0]?.distance_meters || 50,
                unit: 'meter',
                trx_number: '',
                area_stock: 500
            },
            {
                id: '',
                name: 'Fast Connector SC UPC',
                qty: 2,
                unit: 'pcs',
                trx_number: '',
                area_stock: 100
            },
            {
                id: '',
                name: 'Roset Optik 1 Port',
                qty: 1,
                unit: 'pcs',
                trx_number: '',
                area_stock: 50
            }
        ];
    }

    installScheduleForm.notes = cleanNotes.join('\n');
    showEditInstallScheduleModal.value = true;
}

function submitEditInstallSchedule() {
    installScheduleForm.post(`/customers/${props.customer.id}/update-installation-schedule`, {
        preserveScroll: true,
        onSuccess: () => {
            showEditInstallScheduleModal.value = false;
        }
    });
}

// ── 5. MODAL EDIT LAPORAN INSTALASI & ONT ───────────────────
const showEditInstallationReportModal = ref(false);
const installPhotoPreviews = ref({
    photo_odp: null,
    photo_installation: null,
    photo_ont: null,
    photo_customer: null,
    photo_redaman: null
});

const installReportForm = useForm({
    odp_id: '',
    port_number: 1,
    serial_number: '',
    mac_address: '',
    model: '',
    rx_power: '',
    start_time: '',
    end_time: '',
    photo_odp: null,
    photo_installation: null,
    photo_ont: null,
    photo_customer: null,
    photo_redaman: null,
});

function openEditInstallationReportModal() {
    const ont = props.customer.ont;
    installReportForm.odp_id = ont?.odp_id || (props.customer.surveys?.[0]?.odp_id || (filteredAvailableOdps.value?.[0]?.id || ''));
    installReportForm.port_number = ont?.port_number || (props.customer.surveys?.[0]?.port_number || 1);
    installReportForm.serial_number = ont?.serial_number || '';
    installReportForm.mac_address = ont?.mac_address || '';
    installReportForm.model = ont?.model || 'Standard ONT';
    installReportForm.rx_power = ont?.rx_power || '';
    installReportForm.start_time = ont?.start_time ? ont.start_time.substring(0, 5) : '09:00';
    installReportForm.end_time = ont?.end_time ? ont.end_time.substring(0, 5) : '11:00';

    installPhotoPreviews.value.photo_odp = ont?.photo_odp ? '/storage/' + ont.photo_odp : null;
    installPhotoPreviews.value.photo_installation = ont?.photo_installation ? '/storage/' + ont.photo_installation : null;
    installPhotoPreviews.value.photo_ont = ont?.photo_ont ? '/storage/' + ont.photo_ont : null;
    installPhotoPreviews.value.photo_customer = ont?.photo_customer ? '/storage/' + ont.photo_customer : null;
    installPhotoPreviews.value.photo_redaman = ont?.photo_redaman ? '/storage/' + ont.photo_redaman : null;

    showEditInstallationReportModal.value = true;
}

function handleInstallReportPhoto(field, event) {
    const file = event.target.files[0];
    if (file) {
        installReportForm[field] = file;
        installPhotoPreviews.value[field] = URL.createObjectURL(file);
    }
}

function setInstallReportNow(field) {
    const now = new Date();
    const h = String(now.getHours()).padStart(2, '0');
    const m = String(now.getMinutes()).padStart(2, '0');
    installReportForm[field] = `${h}:${m}`;
}

function submitEditInstallationReport() {
    if (installReportForm.rx_power) {
        let clean = installReportForm.rx_power.toString().replace(/dBm|dbm|\s/g, '').replace(',', '.');
        const num = parseFloat(clean);
        if (!isNaN(num)) {
            const finalVal = (num > 0 && num <= 40) ? -num : num;
            installReportForm.rx_power = finalVal.toFixed(2);
        }
    }

    installReportForm.post(`/customers/${props.customer.id}/update-installation-report`, {
        preserveScroll: true,
        onSuccess: () => {
            showEditInstallationReportModal.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Laporan Diperbarui!',
                text: 'Laporan instalasi berhasil diperbarui.',
                timer: 2000,
                showConfirmButton: false
            });
        },
        onError: (errors) => {
            const errorMsg = Object.values(errors).flat().join('<br>');
            Swal.fire({
                icon: 'error',
                title: 'Gagal Memperbarui Laporan',
                html: errorMsg || 'Terjadi kesalahan saat memperbarui laporan instalasi.',
            });
        }
    });
}

// ── 6. MODAL EDIT AKTIVASI & LAYANAN ────────────────────────
const showEditActivationModal = ref(false);
const editActivationForm = useForm({
    activation_date: '',
    service_status: 'berbayar',
    billing_type: defaultBillingType.value || 'prabayar',
    billing_cycle: 'default',
    billing_day: null,
    package_id: '',
    pppoe_user: '',
    pppoe_password: '',
    vlan_mode: '',
    vlan_id: '',
    access_mode: 'PPPOE',
    ip_login: '192.168.1.1',
    login_user: 'admin',
    login_password: 'admin',
    free_hotspot: false,
    hotspot_user: '',
    hotspot_password: '',
    hotspot_vlan_id: '',
});

function openEditActivationModal() {
    const cust = props.customer;
    const ont = cust.ont;
    editActivationForm.activation_date = cust.activation_date ? cust.activation_date.split('T')[0] : (cust.registration_date ? cust.registration_date.split('T')[0] : new Date().toISOString().split('T')[0]);
    editActivationForm.service_status = cust.service_status || 'berbayar';
    editActivationForm.billing_type = cust.billing_type || defaultBillingType.value || 'prabayar';
    editActivationForm.billing_cycle = cust.billing_cycle || 'default';
    editActivationForm.billing_day = cust.billing_day || null;
    editActivationForm.package_id = cust.package_id || '';
    editActivationForm.pppoe_user = ont?.pppoe_user || '';
    editActivationForm.pppoe_password = ont?.pppoe_password || '';
    editActivationForm.vlan_mode = ont?.vlan_mode || '';
    editActivationForm.vlan_id = ont?.vlan_id || '';
    editActivationForm.access_mode = ont?.access_mode || 'PPPOE';
    editActivationForm.ip_login = ont?.ip_login || '192.168.1.1';
    editActivationForm.login_user = ont?.login_user || 'admin';
    editActivationForm.login_password = ont?.login_password || 'admin';
    editActivationForm.free_hotspot = Boolean(ont?.free_hotspot);
    editActivationForm.hotspot_user = ont?.hotspot_user || '';
    editActivationForm.hotspot_password = ont?.hotspot_password || '';
    editActivationForm.hotspot_vlan_id = ont?.hotspot_vlan_id || '';
    showEditActivationModal.value = true;
}

function submitEditActivation() {
    editActivationForm.post(`/customers/${props.customer.id}/update-activation`, {
        preserveScroll: true,
        onSuccess: () => {
            showEditActivationModal.value = false;
        }
    });
}
</script>
