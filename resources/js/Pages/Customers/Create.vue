<template>
    <AppLayout title="Tambah Pelanggan Baru" subtitle="Pendaftaran pelanggan ISP baru (Booking)">
        <div class="max-w-3xl mx-auto py-8">
            <!-- Modal Card Replica -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden animate-fade-in-up">
                
                <!-- Header -->
                <div class="p-4 sm:p-6 flex items-start justify-between border-b border-gray-100">
                    <div class="flex items-start gap-3 sm:gap-4">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-indigo-50 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg sm:text-xl font-bold text-gray-800">Tambah Booking Baru</h3>
                            <p class="text-xs sm:text-sm text-gray-500 mt-1">Masukkan data master pelanggan baru ke dalam sistem.</p>
                        </div>
                    </div>
                    <Link href="/customers/booking" class="p-1 sm:p-2 text-gray-500 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </Link>
                </div>

                <!-- Error Summary -->
                <div v-if="Object.keys(form.errors).length > 0" class="p-4 mx-4 sm:mx-6 mt-4 sm:mt-6 bg-red-50 border border-red-200 rounded-lg text-red-600">
                    <p class="font-bold text-sm mb-2">Terjadi kesalahan pada data yang Anda masukkan:</p>
                    <ul class="list-disc pl-5 text-xs space-y-1">
                        <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                    </ul>
                </div>

                <!-- Form Body -->
                <form @submit.prevent="submit">
                    <div class="p-4 sm:p-6 space-y-4 sm:space-y-5">
                        
                        <!-- Row 1 -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-5">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Nama Pelanggan <span class="text-red-500">*</span></label>
                                <input v-model="form.name" type="text" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="Contoh: Budi Santoso" required />
                                <p v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">No WA</label>
                                <input v-model="form.phone" type="text" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="Contoh: 08123456789" />
                                <p v-if="form.errors.phone" class="text-red-400 text-xs mt-1">{{ form.errors.phone }}</p>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Email (Opsional)</label>
                                <input v-model="form.email" type="email" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="Contoh: budi@gmail.com" />
                                <p v-if="form.errors.email" class="text-red-400 text-xs mt-1">{{ form.errors.email }}</p>
                            </div>
                        </div>

                        <!-- Row 2 -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Area / Wilayah <span class="text-red-500">*</span></label>
                                <select v-if="!isNewArea" v-model="form.area" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" @change="checkNewArea" required>
                                    <option value="">-- Pilih Area --</option>
                                    <option v-for="area in areas" :key="area" :value="area">{{ area }}</option>
                                    <option value="new">+ Tambah Area Baru...</option>
                                </select>
                                <div v-else class="flex gap-2">
                                    <input v-model="form.area" type="text" class="flex-1 px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="Ketik area baru..." required />
                                    <button v-if="areas.length > 0" type="button" @click="cancelNewArea" class="px-3 py-2.5 bg-gray-100 border border-gray-300 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                                        Batal
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Paket Langganan</label>
                                <select v-model="form.package_id" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                                    <option value="">-- Pilih Paket Langganan --</option>
                                    <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                        {{ pkg.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Row 3: Kecamatan -->
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Kecamatan</label>
                            <input v-model="form.district" type="text" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="Contoh: Sukasari" />
                        </div>

                        <!-- Row 4: Desa / Kelurahan -->
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Desa / Kelurahan</label>
                            <input v-model="form.village" type="text" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="Contoh: Sukamaju" />
                        </div>

                        <!-- Row 5: RT / RW -->
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">RT / RW</label>
                            <input v-model="form.rt_rw" type="text" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="Contoh: 02/04" />
                        </div>

                        <!-- Row 6: Detail Jalan -->
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Detail Jalan / Nomor Rumah</label>
                            <textarea v-model="form.address_detail" rows="3" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all resize-y" placeholder="Jl. Melati No. 12..."></textarea>
                        </div>

                        <!-- Row 7: Foto KTP -->
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Foto KTP (Opsional)</label>
                            <div class="flex flex-col sm:flex-row items-start sm:items-center w-full px-3 py-2 bg-white border border-gray-300 rounded-lg gap-4">
                                <label class="cursor-pointer bg-indigo-50 text-indigo-600 px-4 py-2 sm:py-1.5 rounded-md text-sm sm:text-xs font-semibold hover:bg-indigo-100 transition-colors w-full sm:w-auto text-center shrink-0">
                                    Choose File
                                    <input type="file" @change="handleKtpUpload" accept="image/*" class="hidden" />
                                </label>
                                <div v-if="ktpPreviewUrl" class="flex flex-col gap-1 w-full mt-0.5">
                                    <div class="flex items-center gap-3">
                                        <img :src="ktpPreviewUrl" class="h-12 w-12 sm:h-10 sm:w-10 object-cover rounded border border-gray-200 cursor-pointer hover:opacity-80 transition-opacity" @click="showPreviewModal = true" />
                                        <span class="text-sm text-gray-500 truncate flex-1">{{ form.identity_photo ? form.identity_photo.name : '' }}</span>
                                    </div>
                                    <a :href="ktpPreviewUrl" download="Foto_KTP.jpg" class="text-[10px] sm:text-xs text-blue-600 hover:underline w-fit ml-0 sm:ml-1 mt-1 sm:mt-0">Download Foto KTP</a>
                                </div>
                                <span v-else class="text-sm text-gray-500 truncate mt-1.5 hidden sm:block">No file chosen</span>
                            </div>
                        </div>

                        <!-- Row 8: Titik Koordinat -->
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Titik Koordinat</label>
                            <div class="flex flex-col sm:flex-row gap-2 sm:gap-2">
                                <input v-model="form.coordinates" type="text" class="flex-1 px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="Contoh: -6.200000, 106.816666" />
                                <button type="button" @click="getLocation" class="w-full sm:w-auto px-4 py-2.5 bg-gray-100 border border-gray-300 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors flex justify-center items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Auto GPS
                                </button>
                            </div>
                        </div>

                        <!-- Row 9 -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Tarif / Base Amount (Rp) <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <span class="text-gray-500 text-sm font-semibold">Rp</span>
                                    </div>
                                    <input v-model="form.base_amount" type="number" class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" required />
                                </div>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Tanggal Registrasi</label>
                                <input v-model="form.registration_date" type="date" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" />
                            </div>
                        </div>

                        <!-- Row 10 -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Biaya Pasang Baru (Rp)</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <span class="text-gray-500 text-sm font-semibold">Rp</span>
                                    </div>
                                    <input v-model="form.installation_fee" type="number" class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Sales / Afiliator</label>
                                <select v-model="form.sales_id" :disabled="!isAdmin" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all disabled:bg-gray-100 disabled:text-gray-500">
                                    <option value="">-- Tanpa Sales --</option>
                                    <option v-for="person in sales" :key="person.id" :value="person.id">
                                        {{ person.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Row 11: Reseller Options -->
                        <div class="mt-4 p-4 bg-indigo-50 border border-indigo-100 rounded-xl">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <div class="relative flex items-center">
                                    <input v-model="form.is_reseller" type="checkbox" class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 cursor-pointer" />
                                </div>
                                <div>
                                    <span class="block text-sm font-bold text-indigo-900">Jadikan sebagai Reseller Voucher</span>
                                    <span class="block text-xs text-indigo-600 mt-0.5">Pilih ini jika pelanggan juga akan menjual voucher hotspot. Sistem akan otomatis membuatkan dompet saldo reseller.</span>
                                </div>
                            </label>
                        </div>

                    </div>
                    
                    <!-- Footer Actions -->
                    <div class="p-4 sm:p-6 bg-gray-50 border-t border-gray-100 flex flex-col-reverse sm:flex-row justify-end gap-3 rounded-b-2xl">
                        <Link href="/customers/booking" class="w-full sm:w-auto text-center px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            Batal
                        </Link>
                        <button type="submit" :disabled="form.processing" class="w-full sm:w-auto justify-center px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                            <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Pelanggan' }}
                        </button>
                    </div>
                </form>

            </div>
        </div>
        <!-- Image Preview Modal -->
        <Teleport to="body">
            <div v-if="showPreviewModal && ktpPreviewUrl" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/90 backdrop-blur-sm cursor-pointer" @click="showPreviewModal = false"></div>
                <div class="relative max-w-4xl w-full max-h-[90vh] overflow-y-auto h-full flex flex-col items-center justify-center animate-fade-in-up">
                    <button type="button" @click="showPreviewModal = false" class="absolute top-4 right-4 text-white hover:text-red-500 bg-white/20 hover:bg-white/30 p-2 rounded-full z-10 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                    <img :src="ktpPreviewUrl" class="max-h-[85vh] max-w-full object-contain rounded-lg shadow-2xl" />
                    <p class="mt-4 text-white font-medium text-lg">Foto KTP</p>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { watch, ref, onMounted, computed } from 'vue';

const props = defineProps({
    packages: Array,
    sales: Array,
    areas: Array,
});

const isNewArea = ref(false);
const page = usePage();
const isAdmin = computed(() => page.props.auth.user?.role === 'admin' || page.props.auth.user?.role === 'super-admin');

onMounted(() => {
    if (!props.areas || props.areas.length === 0) {
        isNewArea.value = true;
    }
    
    // Auto-select sales if current user is in the sales list
    const user = page.props.auth.user;
    if (user && props.sales.some(s => s.id === user.id)) {
        form.sales_id = user.id;
    }
});

function checkNewArea(e) {
    if (e.target.value === 'new') {
        isNewArea.value = true;
        form.area = '';
    }
}

function cancelNewArea() {
    isNewArea.value = false;
    form.area = '';
}

const ktpPreviewUrl = ref(null);
const showPreviewModal = ref(false);

function handleKtpUpload(e) {
    const file = e.target.files[0];
    form.identity_photo = file;
    if (file) {
        if (ktpPreviewUrl.value) URL.revokeObjectURL(ktpPreviewUrl.value);
        ktpPreviewUrl.value = URL.createObjectURL(file);
    } else {
        ktpPreviewUrl.value = null;
    }
}

const form = useForm({
    name: '',
    email: '',
    phone: '',
    area: '',
    package_id: '',
    district: '',
    village: '',
    rt_rw: '',
    address_detail: '',
    identity_photo: null,
    coordinates: '',
    base_amount: 150000,
    registration_date: new Date().toISOString().split('T')[0],
    installation_fee: 0,
    sales_id: '',
    is_reseller: false,
});

watch(() => form.package_id, (newId) => {
    const pkg = props.packages.find(p => p.id === newId);
    if (pkg) {
        form.base_amount = pkg.price;
    }
});

function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                form.coordinates = `${position.coords.latitude.toFixed(6)}, ${position.coords.longitude.toFixed(6)}`;
            },
            (error) => {
                alert('Gagal mendapatkan lokasi: ' + error.message);
            }
        );
    } else {
        alert('Geolocation tidak didukung oleh browser Anda.');
    }
}

function submit() {
    // Combine address parts into one string to match the backend expectation
    const addressParts = [
        form.address_detail,
        form.rt_rw ? `RT/RW: ${form.rt_rw}` : '',
        form.village ? `Kel. ${form.village}` : '',
        form.district ? `Kec. ${form.district}` : ''
    ].filter(Boolean).join(', ');
    
    let lat = null;
    let lng = null;
    if (form.coordinates) {
        const parts = form.coordinates.split(',');
        lat = parts[0] ? parts[0].trim() : null;
        lng = parts[1] ? parts[1].trim() : null;
    }

    form.transform((data) => ({
        ...data,
        address: addressParts || '-',
        latitude: lat,
        longitude: lng,
    })).post('/customers', {
        onSuccess: () => {
            // Sukses diarahkan oleh controller
        }
    });
}
</script>
