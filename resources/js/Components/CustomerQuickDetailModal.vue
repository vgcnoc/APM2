<script setup>
import { ref, reactive, watch, nextTick } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    customer: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'update:show', 'updated']);

// ── State ──
const isEditing = ref(false);
const saving = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

// GPS
const locatingGps = ref(false);
const gpsError = ref('');
const showPasteCoords = ref(false);
const pasteInput = ref('');

// Form Data
const form = reactive({
    phone: '',
    address: '',
    latitude: '',
    longitude: '',
    identity_photo: null,
    house_photo: null,
    remove_identity_photo: false,
    remove_house_photo: false,
});

// Image Previews & Lightbox
const previewKtp = ref(null);
const previewHouse = ref(null);
const ktpFileInput = ref(null);
const houseFileInput = ref(null);
const newKtpSelected = ref(false);
const newHouseSelected = ref(false);

const lightbox = reactive({
    show: false,
    title: '',
    url: '',
});

// Local clone of customer to reflect updates immediately
const currentCustomer = ref(null);

watch(
    () => props.customer,
    (newCust) => {
        if (newCust) {
            currentCustomer.value = { ...newCust };
            initForm();
        }
    },
    { immediate: true, deep: true }
);

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            initForm();
            isEditing.value = false;
            errorMessage.value = '';
            successMessage.value = '';
        }
    }
);

function initForm() {
    if (!currentCustomer.value) return;
    form.phone = currentCustomer.value.phone || '';
    form.address = currentCustomer.value.address || '';
    form.latitude = currentCustomer.value.latitude || '';
    form.longitude = currentCustomer.value.longitude || '';
    form.identity_photo = null;
    form.house_photo = null;
    form.remove_identity_photo = false;
    form.remove_house_photo = false;
    newKtpSelected.value = false;
    newHouseSelected.value = false;

    previewKtp.value = currentCustomer.value.identity_photo_url || null;
    previewHouse.value = currentCustomer.value.house_photo_url || null;
}

// ── Formatters & Helpers ──
function cleanPhone(phone) {
    if (!phone) return '';
    let p = String(phone).replace(/[^0-9]/g, '');
    if (p.startsWith('0')) {
        p = '62' + p.substring(1);
    }
    return p;
}

function openLightbox(title, url) {
    if (!url) return;
    lightbox.title = title;
    lightbox.url = url;
    lightbox.show = true;
}

function closeLightbox() {
    lightbox.show = false;
}

// ── GPS Geolocation ──
function getCurrentLocation() {
    if (!navigator.geolocation) {
        gpsError.value = 'Browser Anda tidak mendukung deteksi lokasi otomatis.';
        return;
    }
    locatingGps.value = true;
    gpsError.value = '';

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            form.latitude = pos.coords.latitude.toFixed(6);
            form.longitude = pos.coords.longitude.toFixed(6);
            locatingGps.value = false;
        },
        (err) => {
            locatingGps.value = false;
            let msg = 'Gagal membaca lokasi.';
            if (err.code === 1) msg = 'Izin akses GPS / lokasi ditolak di browser.';
            else if (err.code === 2) msg = 'Posisi lokasi tidak tersedia.';
            else if (err.code === 3) msg = 'Waktu permintaan lokasi habis.';
            gpsError.value = msg;
        },
        { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
    );
}

// ── Parse Coordinates / Google Maps Link ──
function parsePastedCoordinates() {
    if (!pasteInput.value) return;
    const text = pasteInput.value.trim();

    // 1. Coba pola "-6.123456, 106.123456" atau URL Google Maps
    const match = text.match(/(-?\d+\.\d+)[,\s]+(-?\d+\.\d+)/);
    if (match) {
        form.latitude = match[1];
        form.longitude = match[2];
        pasteInput.value = '';
        showPasteCoords.value = false;
        gpsError.value = '';
        return;
    }

    // 2. Coba cari pola @latitude,longitude pada URL maps
    const urlMatch = text.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);
    if (urlMatch) {
        form.latitude = urlMatch[1];
        form.longitude = urlMatch[2];
        pasteInput.value = '';
        showPasteCoords.value = false;
        gpsError.value = '';
        return;
    }

    gpsError.value = 'Format koordinat tidak dikenali. Pastikan berformat: Latitude, Longitude (contoh: -6.982312, 106.912381)';
}

// ── Image Handlers ──
function onKtpChange(e) {
    const file = e.target.files?.[0];
    if (!file) return;

    if (file.size > 15 * 1024 * 1024) {
        alert('Ukuran file foto KTP terlalu besar. Maksimal 15 MB.');
        return;
    }

    form.identity_photo = file;
    form.remove_identity_photo = false;
    newKtpSelected.value = true;
    previewKtp.value = URL.createObjectURL(file);
}

function cancelNewKtp() {
    form.identity_photo = null;
    newKtpSelected.value = false;
    form.remove_identity_photo = false;
    previewKtp.value = currentCustomer.value?.identity_photo_url || null;
    if (ktpFileInput.value) ktpFileInput.value.value = '';
}

function removeKtp() {
    form.identity_photo = null;
    form.remove_identity_photo = true;
    newKtpSelected.value = false;
    previewKtp.value = null;
    if (ktpFileInput.value) ktpFileInput.value.value = '';
}

function onHouseChange(e) {
    const file = e.target.files?.[0];
    if (!file) return;

    if (file.size > 15 * 1024 * 1024) {
        alert('Ukuran file foto rumah terlalu besar. Maksimal 15 MB.');
        return;
    }

    form.house_photo = file;
    form.remove_house_photo = false;
    newHouseSelected.value = true;
    previewHouse.value = URL.createObjectURL(file);
}

function cancelNewHouse() {
    form.house_photo = null;
    newHouseSelected.value = false;
    form.remove_house_photo = false;
    previewHouse.value = currentCustomer.value?.house_photo_url || null;
    if (houseFileInput.value) houseFileInput.value.value = '';
}

function removeHouse() {
    form.house_photo = null;
    form.remove_house_photo = true;
    newHouseSelected.value = false;
    previewHouse.value = null;
    if (houseFileInput.value) houseFileInput.value.value = '';
}

// ── Save Function ──
async function saveChanges() {
    if (!currentCustomer.value) return;

    saving.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    try {
        const formData = new FormData();
        formData.append('phone', form.phone || '');
        formData.append('address', form.address || '');
        formData.append('latitude', form.latitude || '');
        formData.append('longitude', form.longitude || '');

        if (form.identity_photo) {
            formData.append('identity_photo', form.identity_photo);
        }
        if (form.remove_identity_photo) {
            formData.append('remove_identity_photo', '1');
        }

        if (form.house_photo) {
            formData.append('house_photo', form.house_photo);
        }
        if (form.remove_house_photo) {
            formData.append('remove_house_photo', '1');
        }

        const response = await axios.post(`/customers/${currentCustomer.value.id}/quick-update`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        if (response.data && response.data.success) {
            successMessage.value = response.data.message || 'Perubahan berhasil disimpan!';
            
            // Perbarui data lokal
            if (response.data.customer) {
                currentCustomer.value = {
                    ...currentCustomer.value,
                    ...response.data.customer,
                };
                previewKtp.value = response.data.customer.identity_photo_url;
                previewHouse.value = response.data.customer.house_photo_url;
            }

            newKtpSelected.value = false;
            newHouseSelected.value = false;
            form.identity_photo = null;
            form.house_photo = null;

            // Emit ke komponen induk agar tabel langsung sinkron tanpa refresh
            emit('updated', currentCustomer.value);

            // Beralih kembali ke mode tampilan
            isEditing.value = false;

            // Hapus pesan sukses setelah 3 detik
            setTimeout(() => {
                successMessage.value = '';
            }, 3500);
        } else {
            errorMessage.value = response.data.message || 'Gagal menyimpan perubahan.';
        }
    } catch (err) {
        console.error('Error quick update customer:', err);
        if (err.response && err.response.data && err.response.data.errors) {
            const firstErr = Object.values(err.response.data.errors)[0];
            errorMessage.value = Array.isArray(firstErr) ? firstErr[0] : String(firstErr);
        } else if (err.response && err.response.data && err.response.data.message) {
            errorMessage.value = err.response.data.message;
        } else {
            errorMessage.value = 'Terjadi kesalahan sistem saat menyimpan. Pastikan koneksi internet stabil.';
        }
    } finally {
        saving.value = false;
    }
}

function handleClose() {
    emit('close');
    emit('update:show', false);
}
</script>

<template>
    <div v-if="show && currentCustomer" class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="handleClose"></div>

        <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
            <div class="relative bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-2xl sm:w-full border border-gray-100 dark:border-slate-800">
                
                <!-- ── HEADER MODAL ── -->
                <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 px-6 py-5 text-white relative border-b border-indigo-900/40">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-2xl shadow-inner">
                                👤
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-lg font-bold text-white tracking-tight">{{ currentCustomer.name }}</h3>
                                    <span v-if="currentCustomer.customer_code" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">
                                        {{ currentCustomer.customer_code }}
                                    </span>
                                </div>
                                <p class="text-xs text-indigo-200/80 mt-0.5 flex items-center gap-2 flex-wrap">
                                    <span v-if="currentCustomer.area">📍 {{ currentCustomer.area }}</span>
                                    <span v-if="currentCustomer.package">• Paket: {{ currentCustomer.package }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Action Header Buttons -->
                        <div class="flex items-center gap-2">
                            <!-- Toggle Edit/View Mode Button -->
                            <button
                                v-if="!isEditing"
                                type="button"
                                @click="isEditing = true"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-600/80 hover:bg-indigo-600 text-white text-xs font-bold border border-indigo-400/30 shadow-xs transition-all hover:scale-102"
                                title="Klik untuk mengedit alamat, titik GPS, foto KTP, foto rumah, dan nomor HP"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                                <span>Edit Data</span>
                            </button>
                            <button
                                v-else
                                type="button"
                                @click="initForm(); isEditing = false;"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium border border-slate-700 transition-colors"
                            >
                                <span>Lihat</span>
                            </button>

                            <!-- Close Button -->
                            <button 
                                type="button" 
                                @click="handleClose" 
                                class="text-slate-400 hover:text-white bg-slate-800/80 hover:bg-slate-700/80 p-2 rounded-xl transition-colors focus:outline-hidden"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Notifikasi / Toast -->
                    <div v-if="successMessage" class="mt-3 bg-emerald-500/20 border border-emerald-500/40 text-emerald-200 text-xs font-medium px-3.5 py-2 rounded-xl flex items-center justify-between">
                        <span>✅ {{ successMessage }}</span>
                    </div>
                    <div v-if="errorMessage" class="mt-3 bg-rose-500/20 border border-rose-500/40 text-rose-200 text-xs font-medium px-3.5 py-2 rounded-xl flex items-center justify-between">
                        <span>⚠️ {{ errorMessage }}</span>
                    </div>
                </div>

                <!-- ── MODAL BODY ── -->
                <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">

                    <!-- ============================================================== -->
                    <!-- 1. VIEW MODE                                                   -->
                    <!-- ============================================================== -->
                    <template v-if="!isEditing">
                        
                        <!-- 1. Lokasi & Titik Koordinat GPS -->
                        <div class="bg-gray-50/80 dark:bg-slate-800/50 rounded-2xl p-4 border border-gray-200/80 dark:border-slate-700/80">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Titik Koordinat & Alamat Pelanggan
                                </span>
                                <div class="flex items-center gap-1.5">
                                    <span v-if="currentCustomer.latitude && currentCustomer.longitude" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        GPS Aktif
                                    </span>
                                    <button
                                        type="button"
                                        @click="isEditing = true"
                                        class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline"
                                    >
                                        Edit ✏️
                                    </button>
                                </div>
                            </div>

                            <!-- Alamat Lengkap -->
                            <p class="text-xs text-gray-700 dark:text-gray-200 font-medium mb-3 bg-white dark:bg-slate-800 p-2.5 rounded-xl border border-gray-200/60 dark:border-slate-700">
                                {{ currentCustomer.address || 'Alamat tidak terdata' }}
                            </p>

                            <!-- Titik Koordinat GPS & Action Button -->
                            <div v-if="currentCustomer.latitude && currentCustomer.longitude" class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-indigo-50/60 dark:bg-indigo-950/30 p-3 rounded-xl border border-indigo-100 dark:border-indigo-900/50">
                                <div>
                                    <div class="text-[11px] font-semibold text-indigo-900 dark:text-indigo-300">Koordinat GPS:</div>
                                    <div class="font-mono text-xs font-bold text-indigo-700 dark:text-indigo-400">
                                        {{ currentCustomer.latitude }}, {{ currentCustomer.longitude }}
                                    </div>
                                </div>
                                <a 
                                    :href="`https://www.google.com/maps?q=${currentCustomer.latitude},${currentCustomer.longitude}`" 
                                    target="_blank" 
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-xs hover:shadow transition-all"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    Buka di Google Maps 📍
                                </a>
                            </div>
                            <div v-else class="text-xs text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/30 p-2.5 rounded-xl border border-amber-200 dark:border-amber-900/50 flex items-center justify-between">
                                <span>⚠️ Titik koordinat GPS belum tersimpan untuk pelanggan ini.</span>
                                <button
                                    type="button"
                                    @click="isEditing = true"
                                    class="text-xs font-bold text-amber-800 dark:text-amber-200 underline ml-2 shrink-0"
                                >
                                    + Tambah Titik GPS
                                </button>
                            </div>
                        </div>

                        <!-- 2. Foto KTP & Foto Rumah Pelanggan -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            
                            <!-- Foto KTP -->
                            <div class="bg-gray-50/80 dark:bg-slate-800/50 rounded-2xl p-4 border border-gray-200/80 dark:border-slate-700/80 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between mb-2.5">
                                        <span class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                                            🪪 Foto KTP
                                        </span>
                                        <div class="flex items-center gap-1.5">
                                            <span v-if="previewKtp" class="text-[10px] font-bold text-green-700 bg-green-100 dark:bg-green-900/40 dark:text-green-300 px-2 py-0.5 rounded-full border border-green-200 dark:border-green-800">
                                                Tersedia
                                            </span>
                                            <button
                                                type="button"
                                                @click="isEditing = true"
                                                class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline"
                                            >
                                                {{ previewKtp ? 'Ubah 📷' : '+ Unggah' }}
                                            </button>
                                        </div>
                                    </div>

                                    <div 
                                        v-if="previewKtp" 
                                        class="relative group rounded-xl overflow-hidden border border-gray-200 dark:border-slate-700 bg-black aspect-4/3 cursor-pointer shadow-inner" 
                                        @click="openLightbox('Foto KTP - ' + currentCustomer.name, previewKtp)"
                                    >
                                        <img 
                                            :src="previewKtp" 
                                            alt="Foto KTP Pelanggan" 
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                        />
                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold gap-1.5">
                                            <span>🔍 Perbesar Foto</span>
                                        </div>
                                    </div>
                                    <div 
                                        v-else 
                                        class="aspect-4/3 rounded-xl border-2 border-dashed border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 flex flex-col items-center justify-center text-gray-400 dark:text-gray-500 p-4 text-center cursor-pointer hover:border-indigo-300 transition-colors"
                                        @click="isEditing = true"
                                    >
                                        <svg class="w-8 h-8 mb-1.5 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                        </svg>
                                        <span class="text-xs font-medium">Foto KTP belum diunggah</span>
                                        <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold mt-1">+ Klik untuk unggah</span>
                                    </div>
                                </div>

                                <div v-if="previewKtp" class="mt-3">
                                    <button 
                                        type="button"
                                        @click="openLightbox('Foto KTP - ' + currentCustomer.name, previewKtp)"
                                        class="w-full text-center py-1.5 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 bg-white dark:bg-slate-800 hover:bg-indigo-50/50 rounded-xl border border-gray-200 dark:border-slate-700 transition-colors"
                                    >
                                        Lihat Foto Penuh &rarr;
                                    </button>
                                </div>
                            </div>

                            <!-- Foto Rumah -->
                            <div class="bg-gray-50/80 dark:bg-slate-800/50 rounded-2xl p-4 border border-gray-200/80 dark:border-slate-700/80 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between mb-2.5">
                                        <span class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                                            🏠 Foto Rumah
                                        </span>
                                        <div class="flex items-center gap-1.5">
                                            <span v-if="previewHouse" class="text-[10px] font-bold text-green-700 bg-green-100 dark:bg-green-900/40 dark:text-green-300 px-2 py-0.5 rounded-full border border-green-200 dark:border-green-800">
                                                Tersedia
                                            </span>
                                            <button
                                                type="button"
                                                @click="isEditing = true"
                                                class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:underline"
                                            >
                                                {{ previewHouse ? 'Ubah 📷' : '+ Unggah' }}
                                            </button>
                                        </div>
                                    </div>

                                    <div 
                                        v-if="previewHouse" 
                                        class="relative group rounded-xl overflow-hidden border border-gray-200 dark:border-slate-700 bg-black aspect-4/3 cursor-pointer shadow-inner" 
                                        @click="openLightbox('Foto Rumah - ' + currentCustomer.name, previewHouse)"
                                    >
                                        <img 
                                            :src="previewHouse" 
                                            alt="Foto Rumah Pelanggan" 
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                        />
                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold gap-1.5">
                                            <span>🔍 Perbesar Foto</span>
                                        </div>
                                    </div>
                                    <div 
                                        v-else 
                                        class="aspect-4/3 rounded-xl border-2 border-dashed border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 flex flex-col items-center justify-center text-gray-400 dark:text-gray-500 p-4 text-center cursor-pointer hover:border-indigo-300 transition-colors"
                                        @click="isEditing = true"
                                    >
                                        <svg class="w-8 h-8 mb-1.5 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                        <span class="text-xs font-medium">Foto rumah belum tersedia</span>
                                        <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold mt-1">+ Klik untuk unggah</span>
                                    </div>
                                </div>

                                <div v-if="previewHouse" class="mt-3">
                                    <button 
                                        type="button"
                                        @click="openLightbox('Foto Rumah - ' + currentCustomer.name, previewHouse)"
                                        class="w-full text-center py-1.5 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 bg-white dark:bg-slate-800 hover:bg-indigo-50/50 rounded-xl border border-gray-200 dark:border-slate-700 transition-colors"
                                    >
                                        Lihat Foto Penuh &rarr;
                                    </button>
                                </div>
                            </div>

                        </div>

                        <!-- 3. Hubungi Kontak Cepat -->
                        <div class="bg-emerald-50/70 dark:bg-emerald-950/30 rounded-2xl p-4 border border-emerald-200/80 dark:border-emerald-900/50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div>
                                <div class="text-xs font-bold text-emerald-900 dark:text-emerald-300 flex items-center gap-1.5">
                                    <span>📞 Kontak Pelanggan</span>
                                    <button
                                        type="button"
                                        @click="isEditing = true"
                                        class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-400 hover:underline ml-1"
                                    >
                                        Edit ✏️
                                    </button>
                                </div>
                                <div class="text-xs font-bold text-emerald-700 dark:text-emerald-400 mt-0.5">
                                    {{ currentCustomer.phone || 'Nomor HP belum diatur' }}
                                </div>
                            </div>
                            <div v-if="currentCustomer.phone" class="flex items-center gap-2 w-full sm:w-auto">
                                <a 
                                    :href="`https://wa.me/${cleanPhone(currentCustomer.phone)}?text=Halo%20Bapak%2FIbu%20${encodeURIComponent(currentCustomer.name)},%20kami%20dari%20petugas%20layanan%20internet...`" 
                                    target="_blank" 
                                    rel="noopener noreferrer"
                                    class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition-colors"
                                >
                                    💬 WhatsApp
                                </a>
                                <a 
                                    :href="`tel:${currentCustomer.phone}`" 
                                    class="inline-flex items-center justify-center px-3.5 py-2 rounded-xl bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-slate-700 text-xs font-bold shadow-xs transition-colors"
                                >
                                    Telepon
                                </a>
                            </div>
                        </div>

                    </template>

                    <!-- ============================================================== -->
                    <!-- 2. EDIT MODE (EDITABLE & SIMPAN)                               -->
                    <!-- ============================================================== -->
                    <template v-else>
                        
                        <div class="bg-indigo-50/50 dark:bg-indigo-950/20 p-3 rounded-2xl border border-indigo-100 dark:border-indigo-900/40 text-xs text-indigo-900 dark:text-indigo-300 flex items-start gap-2">
                            <span class="text-base shrink-0">💡</span>
                            <span>Silakan ubah alamat, koordinat GPS, foto KTP, foto rumah, atau nomor HP di bawah. Seluruh perubahan akan otomatis tersinkron ke peta jaringan, jadwal teknisi, dan invoice.</span>
                        </div>

                        <!-- 1. Edit Alamat -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1.5 flex items-center gap-1.5">
                                <span>📍 Alamat Lengkap Pelanggan</span>
                            </label>
                            <textarea
                                v-model="form.address"
                                rows="3"
                                placeholder="Contoh: Kp. Ciboyong pojok RT 02/03 desa Mangunjaya, Kec. Warungkiara"
                                class="w-full text-xs rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-white p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden transition-all shadow-xs"
                            ></textarea>
                        </div>

                        <!-- 2. Edit Titik Koordinat GPS -->
                        <div class="bg-gray-50/80 dark:bg-slate-800/40 rounded-2xl p-4 border border-gray-200/80 dark:border-slate-700/80 space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <label class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                                    <span>🧭 Titik Koordinat GPS (Latitude & Longitude)</span>
                                </label>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <!-- Ambil GPS Button -->
                                    <button
                                        type="button"
                                        @click="getCurrentLocation"
                                        :disabled="locatingGps"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition-colors disabled:opacity-50"
                                        title="Gunakan posisi GPS saat ini dari perangkat / browser"
                                    >
                                        <span v-if="locatingGps" class="animate-spin text-xs">⏳</span>
                                        <span v-else>📍</span>
                                        <span>{{ locatingGps ? 'Mendeteksi...' : 'GPS Saat Ini' }}</span>
                                    </button>

                                    <!-- Tempel Link Maps Toggle Button -->
                                    <button
                                        type="button"
                                        @click="showPasteCoords = !showPasteCoords"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold rounded-lg bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 transition-colors"
                                    >
                                        <span>📋 Tempel Link/Titik</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Error Geolocation -->
                            <div v-if="gpsError" class="text-xs text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/30 p-2 rounded-xl border border-rose-200 dark:border-rose-900/40">
                                ⚠️ {{ gpsError }}
                            </div>

                            <!-- Box Tempel Koordinat / Link Maps -->
                            <div v-if="showPasteCoords" class="p-3 bg-white dark:bg-slate-800 rounded-xl border border-indigo-200 dark:border-indigo-800 space-y-2">
                                <div class="text-[11px] font-medium text-gray-600 dark:text-gray-300">
                                    Tempel titik koordinat (contoh: <code class="bg-gray-100 dark:bg-slate-700 px-1 rounded">-6.982312, 106.912381</code>) atau link Google Maps:
                                </div>
                                <div class="flex items-center gap-2">
                                    <input
                                        v-model="pasteInput"
                                        type="text"
                                        placeholder="Paste di sini..."
                                        class="flex-1 text-xs rounded-lg border border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white px-2.5 py-1.5 focus:outline-hidden focus:ring-1 focus:ring-indigo-500"
                                        @keyup.enter="parsePastedCoordinates"
                                    />
                                    <button
                                        type="button"
                                        @click="parsePastedCoordinates"
                                        class="px-3 py-1.5 text-xs font-bold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors"
                                    >
                                        Terapkan
                                    </button>
                                </div>
                            </div>

                            <!-- Input Latitude & Longitude -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <span class="text-[11px] font-bold text-gray-600 dark:text-gray-400">Latitude</span>
                                    <input
                                        v-model="form.latitude"
                                        type="text"
                                        placeholder="Contoh: -6.982312"
                                        class="mt-1 w-full text-xs font-mono rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                    />
                                </div>
                                <div>
                                    <span class="text-[11px] font-bold text-gray-600 dark:text-gray-400">Longitude</span>
                                    <input
                                        v-model="form.longitude"
                                        type="text"
                                        placeholder="Contoh: 106.912381"
                                        class="mt-1 w-full text-xs font-mono rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                    />
                                </div>
                            </div>

                            <!-- Link cek lokasi jika koordinat ada -->
                            <div v-if="form.latitude && form.longitude" class="flex items-center justify-between text-[11px] text-gray-500 pt-1">
                                <span>Titik terisi: {{ form.latitude }}, {{ form.longitude }}</span>
                                <a
                                    :href="`https://www.google.com/maps?q=${form.latitude},${form.longitude}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline"
                                >
                                    Uji di Google Maps 📍
                                </a>
                            </div>
                        </div>

                        <!-- 3. Edit Foto KTP & Foto Rumah -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            
                            <!-- Foto KTP Edit -->
                            <div class="bg-gray-50/80 dark:bg-slate-800/40 rounded-2xl p-4 border border-gray-200/80 dark:border-slate-700/80 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                            🪪 Foto KTP
                                        </span>
                                        <span v-if="newKtpSelected" class="text-[10px] font-bold text-emerald-700 bg-emerald-100 dark:bg-emerald-900/50 px-2 py-0.5 rounded-full">
                                            Foto Baru Dipilih
                                        </span>
                                        <span v-else-if="form.remove_identity_photo" class="text-[10px] font-bold text-rose-700 bg-rose-100 dark:bg-rose-900/50 px-2 py-0.5 rounded-full">
                                            Akan Dihapus
                                        </span>
                                    </div>

                                    <!-- Preview Image or Placeholder -->
                                    <div class="relative rounded-xl overflow-hidden border border-gray-200 dark:border-slate-700 bg-gray-100 dark:bg-slate-900 aspect-4/3 flex items-center justify-center">
                                        <img 
                                            v-if="previewKtp && !form.remove_identity_photo" 
                                            :src="previewKtp" 
                                            alt="Preview Foto KTP" 
                                            class="w-full h-full object-cover"
                                        />
                                        <div v-else class="text-center p-3 text-gray-400">
                                            <svg class="w-8 h-8 mx-auto mb-1 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                            </svg>
                                            <span class="text-xs">Belum ada foto</span>
                                        </div>
                                    </div>

                                    <!-- Hidden File Input -->
                                    <input 
                                        type="file" 
                                        ref="ktpFileInput" 
                                        accept="image/*" 
                                        class="hidden" 
                                        @change="onKtpChange" 
                                    />
                                </div>

                                <!-- Action Buttons for KTP -->
                                <div class="mt-3 flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="$refs.ktpFileInput.click()"
                                        class="flex-1 py-1.5 px-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-colors text-center shadow-xs"
                                    >
                                        {{ previewKtp && !form.remove_identity_photo ? '📷 Ganti Foto' : '📷 Pilih Foto KTP' }}
                                    </button>

                                    <!-- Cancel New Selection -->
                                    <button
                                        v-if="newKtpSelected"
                                        type="button"
                                        @click="cancelNewKtp"
                                        class="py-1.5 px-2.5 rounded-xl bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold transition-colors"
                                        title="Batalkan pilihan foto baru"
                                    >
                                        ✖️
                                    </button>

                                    <!-- Delete Photo -->
                                    <button
                                        v-else-if="previewKtp && !form.remove_identity_photo"
                                        type="button"
                                        @click="removeKtp"
                                        class="py-1.5 px-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-bold border border-rose-200 transition-colors"
                                        title="Hapus foto KTP ini"
                                    >
                                        🗑️
                                    </button>

                                    <!-- Restore Removed Photo -->
                                    <button
                                        v-else-if="form.remove_identity_photo"
                                        type="button"
                                        @click="cancelNewKtp"
                                        class="py-1.5 px-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold transition-colors"
                                        title="Batal hapus"
                                    >
                                        ↩️ Batal Hapus
                                    </button>
                                </div>
                            </div>

                            <!-- Foto Rumah Edit -->
                            <div class="bg-gray-50/80 dark:bg-slate-800/40 rounded-2xl p-4 border border-gray-200/80 dark:border-slate-700/80 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                            🏠 Foto Rumah
                                        </span>
                                        <span v-if="newHouseSelected" class="text-[10px] font-bold text-emerald-700 bg-emerald-100 dark:bg-emerald-900/50 px-2 py-0.5 rounded-full">
                                            Foto Baru Dipilih
                                        </span>
                                        <span v-else-if="form.remove_house_photo" class="text-[10px] font-bold text-rose-700 bg-rose-100 dark:bg-rose-900/50 px-2 py-0.5 rounded-full">
                                            Akan Dihapus
                                        </span>
                                    </div>

                                    <!-- Preview Image or Placeholder -->
                                    <div class="relative rounded-xl overflow-hidden border border-gray-200 dark:border-slate-700 bg-gray-100 dark:bg-slate-900 aspect-4/3 flex items-center justify-center">
                                        <img 
                                            v-if="previewHouse && !form.remove_house_photo" 
                                            :src="previewHouse" 
                                            alt="Preview Foto Rumah" 
                                            class="w-full h-full object-cover"
                                        />
                                        <div v-else class="text-center p-3 text-gray-400">
                                            <svg class="w-8 h-8 mx-auto mb-1 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                            </svg>
                                            <span class="text-xs">Belum ada foto</span>
                                        </div>
                                    </div>

                                    <!-- Hidden File Input -->
                                    <input 
                                        type="file" 
                                        ref="houseFileInput" 
                                        accept="image/*" 
                                        class="hidden" 
                                        @change="onHouseChange" 
                                    />
                                </div>

                                <!-- Action Buttons for House Photo -->
                                <div class="mt-3 flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="$refs.houseFileInput.click()"
                                        class="flex-1 py-1.5 px-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-colors text-center shadow-xs"
                                    >
                                        {{ previewHouse && !form.remove_house_photo ? '📷 Ganti Foto' : '📷 Pilih Foto Rumah' }}
                                    </button>

                                    <!-- Cancel New Selection -->
                                    <button
                                        v-if="newHouseSelected"
                                        type="button"
                                        @click="cancelNewHouse"
                                        class="py-1.5 px-2.5 rounded-xl bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold transition-colors"
                                        title="Batalkan pilihan foto baru"
                                    >
                                        ✖️
                                    </button>

                                    <!-- Delete Photo -->
                                    <button
                                        v-else-if="previewHouse && !form.remove_house_photo"
                                        type="button"
                                        @click="removeHouse"
                                        class="py-1.5 px-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-bold border border-rose-200 transition-colors"
                                        title="Hapus foto rumah ini"
                                    >
                                        🗑️
                                    </button>

                                    <!-- Restore Removed Photo -->
                                    <button
                                        v-else-if="form.remove_house_photo"
                                        type="button"
                                        @click="cancelNewHouse"
                                        class="py-1.5 px-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold transition-colors"
                                        title="Batal hapus"
                                    >
                                        ↩️ Batal Hapus
                                    </button>
                                </div>
                            </div>

                        </div>

                        <!-- 4. Edit Kontak Pelanggan -->
                        <div class="bg-gray-50/80 dark:bg-slate-800/40 rounded-2xl p-4 border border-gray-200/80 dark:border-slate-700/80">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1.5 flex items-center gap-1.5">
                                <span>📞 Nomor Kontak Pelanggan / WhatsApp</span>
                            </label>
                            <input
                                v-model="form.phone"
                                type="text"
                                placeholder="Contoh: 085860000000 atau 6285860000000"
                                class="w-full text-xs rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-white px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden transition-all shadow-xs"
                            />
                            <p class="text-[11px] text-gray-500 mt-1">
                                Nomor ini terhubung ke pengiriman nota tagihan WhatsApp, verifikasi, dan kontak darurat teknisi.
                            </p>
                        </div>

                    </template>

                </div>

                <!-- ── MODAL FOOTER ── -->
                <div class="bg-gray-50 dark:bg-slate-800/80 px-6 py-4 flex items-center justify-between border-t border-gray-100 dark:border-slate-800">
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">
                        <span v-if="isEditing">Mode Edit Aktif</span>
                        <span v-else>ID: #{{ currentCustomer.id }}</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- If Viewing: -->
                        <template v-if="!isEditing">
                            <button 
                                type="button" 
                                @click="isEditing = true" 
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                                <span>Edit Data Pelanggan</span>
                            </button>
                            <button 
                                type="button" 
                                @click="handleClose" 
                                class="px-5 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-gray-800 dark:text-gray-200 text-xs font-bold rounded-xl transition-colors"
                            >
                                Tutup
                            </button>
                        </template>

                        <!-- If Editing: -->
                        <template v-else>
                            <button 
                                type="button" 
                                @click="initForm(); isEditing = false;" 
                                :disabled="saving"
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-gray-800 dark:text-gray-200 text-xs font-bold rounded-xl transition-colors disabled:opacity-50"
                            >
                                Batal
                            </button>
                            <button 
                                type="button" 
                                @click="saveChanges" 
                                :disabled="saving"
                                class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs hover:shadow transition-all disabled:opacity-50 flex items-center gap-1.5"
                            >
                                <svg v-if="saving" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ saving ? 'Menyimpan...' : '💾 Simpan Perubahan' }}</span>
                            </button>
                        </template>
                    </div>
                </div>

            </div>
        </div>

        <!-- ── INTEGRATED LIGHTBOX IMAGE PREVIEW MODAL ── -->
        <div v-if="lightbox.show" class="fixed inset-0 z-60 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen p-4 text-center">
                <div class="fixed inset-0 bg-black/85 backdrop-blur-sm transition-opacity" @click="closeLightbox"></div>
                <div class="relative bg-black rounded-2xl overflow-hidden max-w-4xl max-h-[90vh] shadow-2xl z-10 border border-white/10">
                    <div class="flex items-center justify-between px-4 py-3 bg-gray-900/90 text-white border-b border-white/10">
                        <h4 class="text-sm font-bold truncate">{{ lightbox.title }}</h4>
                        <div class="flex items-center gap-2">
                            <a 
                                :href="lightbox.url" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                class="text-xs text-indigo-300 hover:text-white px-2.5 py-1 rounded bg-white/10 hover:bg-white/20 transition-colors"
                            >
                                Buka Asli ↗
                            </a>
                            <button 
                                type="button" 
                                @click="closeLightbox" 
                                class="text-gray-400 hover:text-white p-1 rounded-lg focus:outline-hidden"
                            >
                                ✕
                            </button>
                        </div>
                    </div>
                    <div class="p-2 flex items-center justify-center bg-black/50">
                        <img 
                            :src="lightbox.url" 
                            :alt="lightbox.title" 
                            class="max-w-full max-h-[78vh] object-contain rounded-lg"
                        />
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>
