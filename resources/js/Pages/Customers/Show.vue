<template>
    <AppLayout title="Detail Pelanggan" :subtitle="customer.customer_code">
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
                            <InfoRow icon="📦" label="Paket" :value="customer.package?.name || 'Belum pilih'" />
                            <div class="pt-3 mt-3 border-t border-slate-200 space-y-3">
                                <InfoRow icon="📅" label="Registrasi" :value="customer.registration_date" />
                                <InfoRow icon="✅" label="Aktivasi" :value="customer.activation_date || '-'" />
                            </div>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <Link :href="`/customers/${customer.id}/edit`" class="btn-primary flex-1 justify-center py-2.5 text-sm shadow-md hover:shadow-lg transition-all">
                                Edit Profil
                            </Link>
                            <Link href="/customers" class="btn-ghost flex-1 justify-center py-2.5 text-sm transition-all hover:bg-slate-100">
                                Kembali
                            </Link>
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
                <div v-if="!isLaporanPasang" class="flex flex-wrap gap-1 bg-white p-1 rounded-xl shadow-sm border border-gray-100">
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
                <div v-show="activeTab === 'booking'" class="glass-card p-6 animate-fade-in-up">
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
                </div>

                <!-- Konfigurasi Layanan & ONT Section -->
                <div v-show="activeTab === 'audit' && customer.status === 'active' && source !== 'instalasi'" class="glass-card p-6 mt-6 animate-fade-in-up border border-indigo-100 shadow-md">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span class="bg-indigo-100 text-indigo-600 p-2 rounded-lg">⚙️</span>
                            Konfigurasi Layanan & ONT
                        </h3>
                        <div class="flex gap-2">
                            <button v-if="!isEditingOnt" type="button" @click="isEditingOnt = true" class="text-xs font-bold px-4 py-2 rounded-lg transition-all bg-gray-100 text-gray-700 hover:bg-gray-200 hover:text-gray-900 shadow-sm">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    Edit Konfigurasi
                                </span>
                            </button>
                            <template v-else>
                                <button type="button" @click="isEditingOnt = false" class="text-xs font-bold px-4 py-2 rounded-lg transition-all bg-gray-100 text-gray-700 hover:bg-gray-200 shadow-sm">
                                    Batal
                                </button>
                                <button type="button" @click="saveOntData" :disabled="isSavingOnt" class="text-xs font-bold px-4 py-2 rounded-lg transition-all shadow-sm" :class="isSavingOnt ? 'bg-emerald-100 text-emerald-400 cursor-not-allowed' : 'bg-emerald-500 text-white hover:bg-emerald-600 hover:shadow-md'">
                                    <span class="flex items-center gap-2">
                                        <svg v-if="isSavingOnt" class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        {{ isSavingOnt ? 'Menyimpan...' : 'Simpan Perubahan' }}
                                    </span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">PPPoE Username</label>
                                <input v-model="activationForm.pppoe_user" type="text" class="w-full border rounded-lg px-4 py-2.5 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-700 cursor-not-allowed font-medium' : 'bg-white border-blue-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-medium'" placeholder="user@isp" :readonly="!isEditingOnt" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">PPPoE Password</label>
                                <input v-model="activationForm.pppoe_password" type="text" class="w-full border rounded-lg px-4 py-2.5 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-700 cursor-not-allowed font-medium' : 'bg-white border-blue-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-medium'" placeholder="***" :readonly="!isEditingOnt" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Mode Akses</label>
                                <select v-model="activationForm.access_mode" class="w-full border rounded-lg px-4 py-2.5 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-700 cursor-not-allowed font-medium' : 'bg-white border-blue-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-medium'" :disabled="!isEditingOnt">
                                    <option value="PPPOE">PPPoE</option>
                                    <option value="STATIC">Static IP</option>
                                    <option value="DHCP">DHCP / Dynamic</option>
                                    <option value="HOTSPOT">Hotspot</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">VLAN Mode</label>
                                <select v-model="activationForm.vlan_mode" class="w-full border rounded-lg px-4 py-2.5 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-700 cursor-not-allowed font-medium' : 'bg-white border-blue-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-medium'" :disabled="!isEditingOnt">
                                    <option value="">-- Pilih --</option>
                                    <option value="Route">Route</option>
                                    <option value="Bridge">Bridge</option>
                                    <option value="VLAN">VLAN (Tagged)</option>
                                    <option value="Untagged">Untagged</option>
                                </select>
                            </div>
                            
                            <div v-if="activationForm.vlan_mode === 'VLAN'" class="col-span-full">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">No VLAN ID</label>
                                <input v-model="activationForm.vlan_id" type="text" class="w-full border rounded-lg px-4 py-2.5 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-700 cursor-not-allowed font-medium' : 'bg-white border-blue-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-medium'" placeholder="Misal: 100" :readonly="!isEditingOnt" />
                            </div>
                        </div>
                        
                        <div class="pt-4 border-t border-gray-200 grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">IP Login ONT</label>
                                <input v-model="activationForm.ip_login" type="text" class="w-full border rounded-lg px-4 py-2 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-700 cursor-not-allowed text-sm' : 'bg-white border-blue-300 focus:ring-2 focus:ring-blue-500 text-sm'" placeholder="192.168.1.1" :readonly="!isEditingOnt" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">User ONT</label>
                                <input v-model="activationForm.login_user" type="text" class="w-full border rounded-lg px-4 py-2 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-700 cursor-not-allowed text-sm' : 'bg-white border-blue-300 focus:ring-2 focus:ring-blue-500 text-sm'" placeholder="admin" :readonly="!isEditingOnt" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Pass ONT</label>
                                <input v-model="activationForm.login_password" type="text" class="w-full border rounded-lg px-4 py-2 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-700 cursor-not-allowed text-sm' : 'bg-white border-blue-300 focus:ring-2 focus:ring-blue-500 text-sm'" placeholder="admin" :readonly="!isEditingOnt" />
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Audit Data Pemasangan Section -->
                <div v-show="activeTab === 'audit' && (isAudit || customer.status === 'active')" class="glass-card p-6 mt-6 animate-fade-in-up border-2 border-amber-500/20">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="bg-amber-100 text-amber-600 p-2 rounded-lg">🛡️</span>
                        Data Instalasi & Audit
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
                                <InfoRow label="Waktu Mulai" :value="customer.ont?.start_time || '-'" />
                                <InfoRow label="Waktu Selesai" :value="customer.ont?.end_time || '-'" />
                            </div>
                            <div class="space-y-2">
                                <InfoRow label="Port ODP" :value="`Port ${customer.ont?.port_number}`" />
                                <InfoRow label="Serial Number (Auto)" :value="customer.ont?.serial_number" />
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
                            <button v-if="!customer.is_audited" @click="showAuditModal = true" class="btn-primary w-full md:w-auto text-lg py-3 shadow-lg hover:shadow-xl bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 border-0 text-white">
                                Selesaikan Audit
                            </button>
                            <button v-else-if="source !== 'instalasi'" @click="showActivationModal = true" class="btn-primary w-full md:w-auto text-lg py-3 shadow-lg hover:shadow-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 border-0 text-white">
                                Aktivasi Pelanggan
                            </button>
                        </div>
                    </div>
                <!-- Instalasi Section (Blank State) -->
                <div v-show="activeTab === 'installation' && (isAudit || customer.status === 'active')" class="glass-card p-8 mt-6 animate-fade-in-up border border-gray-200 text-center space-y-4">
                    <div class="text-5xl mb-2">✅</div>
                    <h3 class="text-2xl font-bold text-gray-900">Instalasi Telah Selesai</h3>
                    <p class="text-gray-500 max-w-lg mx-auto">Laporan pemasangan lapangan telah disubmit. Anda dapat melihat dokumentasi lapangan (foto ODP, pelanggan, redaman, dll) di tab <strong>Audit Instalasi</strong>.</p>
                    <div class="pt-4">
                        <button @click="activeTab = 'audit'" class="btn-primary inline-flex items-center gap-2 bg-purple-500 hover:bg-purple-600 border-none px-6 py-2 shadow-md">
                            Lihat Audit Instalasi
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Instalasi Section (Form) -->
                <div v-show="activeTab === 'installation' && isLaporanPasang" class="glass-card p-6 mt-6 animate-fade-in-up border-2 border-emerald-500/20">
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

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Aktivasi</label>
                            <input v-model="activationForm.activation_date" type="date" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 shadow-sm transition-all" required />
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
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ activationForm.access_mode === 'HOTSPOT' ? 'Username Hotspot' : 'PPPoE Username' }}</label>
                                <input v-model="activationForm.pppoe_user" type="text" class="w-full border rounded-lg px-4 py-2.5 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white border-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500'" placeholder="user@isp" :readonly="!isEditingOnt" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ activationForm.access_mode === 'HOTSPOT' ? 'Password Hotspot' : 'PPPoE Password' }}</label>
                                <input v-model="activationForm.pppoe_password" type="text" class="w-full border rounded-lg px-4 py-2.5 shadow-sm transition-all" :class="!isEditingOnt ? 'bg-gray-100 border-gray-200 text-gray-500 cursor-not-allowed' : 'bg-white border-gray-300 focus:ring-2 focus:ring-amber-500 focus:border-amber-500'" placeholder="***" :readonly="!isEditingOnt" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mode Akses</label>
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
                            
                            <div class="text-gray-500 font-medium">Material:</div>
                            <div class="font-bold">{{ hardwareItems.length }} item</div>
                            
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
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ activeConfigForm.access_mode === 'HOTSPOT' ? 'Username Hotspot' : 'PPPoE Username' }}</label>
                                <input v-model="activeConfigForm.pppoe_user" type="text" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all" placeholder="user@isp" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ activeConfigForm.access_mode === 'HOTSPOT' ? 'Password Hotspot' : 'PPPoE Password' }}</label>
                                <input v-model="activeConfigForm.pppoe_password" type="text" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all" placeholder="***" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mode Akses</label>
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
    </AppLayout>
</template>

<script setup>
import { ref, h, computed, watch, onMounted } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({ customer: Object, availableOdps: Array, availableOnts: Array, source: String });

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
    
    // Restore hardware status from localStorage
    const savedHardware = localStorage.getItem(`apm_hardware_${props.customer.id}`);
    if (savedHardware) {
        try {
            const installedIds = JSON.parse(savedHardware);
            hardwareItems.value.forEach(item => {
                if (installedIds.includes(item.id)) {
                    item.isInstalled = true;
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
    const installedIds = hardwareItems.value.filter(i => i.isInstalled).map(i => i.id);
    localStorage.setItem(`apm_hardware_${props.customer.id}`, JSON.stringify(installedIds));
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
});

const selectedOdp = computed(() => props.availableOdps?.find(o => o.id == ontForm.odp_id));

const isJamMulaiValid = computed(() => !!ontForm.start_time);
const isMaterialValid = computed(() => hardwareItems.value.length === 0 || hardwareItems.value.every(i => i.isInstalled));
const isOdpValid = computed(() => !!ontForm.odp_id && selectedOdp.value && selectedOdp.value.area_id === props.customer.area_id);
const isPortValid = computed(() => !!ontForm.port_number && !isPortUsed(selectedOdp.value, ontForm.port_number));
const isRedamanValid = computed(() => !!ontForm.rx_power && !isNaN(parseFloat(ontForm.rx_power)));
const isFotoValid = computed(() => !!ontForm.photo_odp && !!ontForm.photo_installation && !!ontForm.photo_ont && !!ontForm.photo_customer && !!ontForm.photo_redaman);

const validationErrors = computed(() => {
    const errors = [];
    if (!isJamMulaiValid.value) errors.push('Jam Mulai Pekerjaan belum diisi.');
    if (!isMaterialValid.value) errors.push('Masih ada material wajib yang belum diselesaikan.');
    if (!isOdpValid.value) errors.push('ODP belum dipilih atau Area ODP tidak sesuai pelanggan.');
    if (!isPortValid.value) errors.push('Port ODP belum dipilih atau sudah digunakan.');
    if (!isRedamanValid.value) errors.push('Redaman belum diisi dengan angka valid.');
    if (!isFotoValid.value) {
        if (!ontForm.photo_odp) errors.push('Foto ODP / Port wajib diunggah.');
        if (!ontForm.photo_installation) errors.push('Foto Instalasi di Rumah wajib diunggah.');
        if (!ontForm.photo_ont) errors.push('Foto Posisi ONT wajib diunggah.');
        if (!ontForm.photo_customer) errors.push('Foto Selfie Pelanggan wajib diunggah.');
        if (!ontForm.photo_redaman) errors.push('Foto Redaman wajib diunggah.');
    }
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
            
            const photoFields = ['photo_odp', 'photo_installation', 'photo_ont', 'photo_customer', 'photo_redaman'];
            photoFields.forEach(field => localStorage.removeItem(`apm_${field}_${props.customer.id}`));
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

const showActivationModal = ref(false);
const activationForm = useForm({
    activation_date: new Date().toISOString().split('T')[0],
    pppoe_user: props.customer?.ont?.pppoe_user || '',
    pppoe_password: props.customer?.ont?.pppoe_password || '',
    vlan_mode: props.customer?.ont?.vlan_mode || '',
    vlan_id: props.customer?.ont?.vlan_id || '',
    access_mode: props.customer?.ont?.access_mode || 'PPPOE',
    ip_login: props.customer?.ont?.ip_login || '192.168.1.1',
    login_user: props.customer?.ont?.login_user || 'admin',
    login_password: props.customer?.ont?.login_password || 'admin',
    notes: ''
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
</script>
