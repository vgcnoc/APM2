<template>
    <AppLayout title="Profil Perusahaan" subtitle="Pengaturan identitas dan informasi kontak perusahaan">
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Success message -->
            <div v-if="$page.props.flash?.success" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm font-medium">
                ✅ {{ $page.props.flash.success }}
            </div>
            
            <form @submit.prevent="submitForm" enctype="multipart/form-data">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Kolom 1: Profil & Logo Aplikasi -->
                    <div class="bg-white rounded-2xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 overflow-hidden flex flex-col">
                        <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Logo & Nama Aplikasi</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Identitas utama aplikasi di sidebar dan menu.</p>
                            </div>
                        </div>
                        <div class="p-6 space-y-6 flex-1">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Nama Aplikasi <span class="text-red-500">*</span></label>
                                <input type="text" v-model="form.appName" class="block w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 sm:text-sm transition-all" placeholder="Contoh: APM2 ISP" required />
                                <p class="text-xs text-gray-400 mt-2">Nama ini akan ditampilkan jika logo belum diupload.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-3">Preview Logo Aplikasi Saat Ini</label>
                                <div class="bg-gray-50 rounded-2xl p-6 flex items-center justify-center border-2 border-dashed border-gray-200 relative min-h-[160px] group transition-all hover:bg-gray-100 hover:border-indigo-300">
                                    <img v-if="appLogoPreview" :src="appLogoPreview" class="max-h-[80px] w-auto object-contain drop-shadow-sm transition-transform group-hover:scale-105" @error="onAppImgError" />
                                    <div v-else class="text-center text-gray-400">
                                        <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        <span class="text-sm font-bold block">Belum ada logo aplikasi</span>
                                        <span class="text-xs mt-1 block">Sistem menggunakan nama aplikasi</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Upload Logo Aplikasi Baru</label>
                                <input type="file" ref="appFileInput" @change="handleAppFileChange" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer transition-colors" />
                                <p class="text-xs text-gray-400 mt-2">Format: PNG transparan (Landscape). Maks. 2MB.</p>
                            </div>
                            
                            <div class="pt-2">
                                <button type="button" v-if="props.current_app_logo" @click="removeAppLogo" :disabled="isSubmitting" class="text-xs font-bold text-red-500 hover:text-red-700 transition-colors">
                                    Hapus Logo Aplikasi
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom 2: Info Perusahaan & Logo Perusahaan -->
                    <div class="bg-white rounded-2xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 overflow-hidden flex flex-col">
                        <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Data & Logo Perusahaan</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Digunakan untuk kop surat, invoice, dan kwitansi.</p>
                            </div>
                        </div>
                        <div class="p-6 space-y-6 flex-1">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Nama Perusahaan / PT</label>
                                <input type="text" v-model="form.companyName" class="block w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 sm:text-sm transition-all" placeholder="Contoh: PT. Maju Bersama Internet" />
                            </div>
                            
                            <!-- Logo Perusahaan -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Logo Perusahaan (Untuk Invoice)</label>
                                    <div class="bg-gray-50 rounded-xl p-4 flex items-center justify-center border-2 border-dashed border-gray-200 relative min-h-[100px] group transition-all hover:bg-gray-100 hover:border-blue-300">
                                        <img v-if="compLogoPreview" :src="compLogoPreview" class="max-h-[60px] w-auto object-contain drop-shadow-sm transition-transform group-hover:scale-105" @error="onCompImgError" />
                                        <div v-else class="text-center text-gray-400">
                                            <svg class="w-8 h-8 mx-auto mb-1 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                            <span class="text-xs font-bold block">Tanpa Logo Invoice</span>
                                        </div>
                                    </div>
                                    <div class="pt-2 flex justify-between items-center">
                                        <input type="file" ref="compFileInput" @change="handleCompFileChange" accept="image/*" class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer transition-colors" />
                                    </div>
                                    <button type="button" v-if="props.current_company_logo" @click="removeCompLogo" :disabled="isSubmitting" class="text-xs font-bold text-red-500 hover:text-red-700 transition-colors mt-1 block">
                                        Hapus Logo Invoice
                                    </button>
                                </div>
                                <div class="text-xs text-gray-500 bg-blue-50/50 p-4 rounded-xl border border-blue-100">
                                    <p><b>Tips:</b> Logo perusahaan biasanya berbentuk kotak atau bulat (aspek rasio 1:1) dan lebih cocok untuk kop surat resmi.</p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Nomor Telepon / WA</label>
                                    <input type="text" v-model="form.companyPhone" class="block w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 sm:text-sm transition-all" placeholder="08123456789" />
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Email Perusahaan</label>
                                    <input type="email" v-model="form.companyEmail" class="block w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 sm:text-sm transition-all" placeholder="cs@perusahaan.com" />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Website</label>
                                <input type="text" v-model="form.companyWebsite" class="block w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 sm:text-sm transition-all" placeholder="https://www.perusahaan.com" />
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Alamat Lengkap</label>
                                <textarea v-model="form.companyAddress" rows="3" class="block w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 sm:text-sm transition-all resize-none" placeholder="Alamat lengkap perusahaan..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 flex flex-col-reverse sm:flex-row items-center justify-end gap-3 p-6 bg-white rounded-2xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100">
                    <button type="submit" :disabled="isSubmitting" class="w-full sm:w-auto px-8 py-3 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-colors shadow-lg shadow-indigo-200 flex items-center justify-center gap-2">
                        <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span v-if="isSubmitting">Menyimpan Perubahan...</span>
                        <span v-else>Simpan Semua Pengaturan</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    current_app_logo: String,
    current_company_logo: String,
    current_app_name: String,
    company_name: String,
    company_address: String,
    company_phone: String,
    company_email: String,
    company_website: String,
});

const page = usePage();
const appFileInput = ref(null);
const compFileInput = ref(null);

const selectedAppFile = ref(null);
const selectedCompFile = ref(null);

const isSubmitting = ref(false);

const form = reactive({
    appName: props.current_app_name || '',
    companyName: props.company_name || '',
    companyAddress: props.company_address || '',
    companyPhone: props.company_phone || '',
    companyEmail: props.company_email || '',
    companyWebsite: props.company_website || '',
});

const appLogoPreview = ref(props.current_app_logo || null);
const compLogoPreview = ref(props.current_company_logo || null);

const onAppImgError = () => { appLogoPreview.value = null; };
const onCompImgError = () => { compLogoPreview.value = null; };

const handleAppFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        selectedAppFile.value = file;
        appLogoPreview.value = URL.createObjectURL(file);
    }
};

const handleCompFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        selectedCompFile.value = file;
        compLogoPreview.value = URL.createObjectURL(file);
    }
};

const submitForm = () => {
    isSubmitting.value = true;
    
    const formData = new FormData();
    formData.append('app_name', form.appName);
    formData.append('company_name', form.companyName);
    formData.append('company_address', form.companyAddress);
    formData.append('company_phone', form.companyPhone);
    formData.append('company_email', form.companyEmail);
    formData.append('company_website', form.companyWebsite);
    
    if (selectedAppFile.value) formData.append('app_logo', selectedAppFile.value);
    if (selectedCompFile.value) formData.append('company_logo', selectedCompFile.value);

    const xsrfToken = document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1];

    fetch(route('settings.branding.update'), {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            ...(xsrfToken ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfToken) } : {}),
        },
        credentials: 'same-origin',
    })
    .then(() => {
        isSubmitting.value = false;
        window.location.href = route('settings.branding');
    })
    .catch(error => {
        isSubmitting.value = false;
        alert('Gagal menyimpan: ' + error.message);
    });
};

const removeAppLogo = () => {
    if (confirm('Hapus Logo Aplikasi?')) {
        isSubmitting.value = true;
        const formData = new FormData();
        formData.append('remove_app_logo', '1');
        const xsrfToken = document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1];
        fetch(route('settings.branding.update'), {
            method: 'POST', body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', ...(xsrfToken ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfToken) } : {}) },
            credentials: 'same-origin',
        }).then(() => window.location.href = route('settings.branding'));
    }
};

const removeCompLogo = () => {
    if (confirm('Hapus Logo Perusahaan/Invoice?')) {
        isSubmitting.value = true;
        const formData = new FormData();
        formData.append('remove_company_logo', '1');
        const xsrfToken = document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1];
        fetch(route('settings.branding.update'), {
            method: 'POST', body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', ...(xsrfToken ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfToken) } : {}) },
            credentials: 'same-origin',
        }).then(() => window.location.href = route('settings.branding'));
    }
};
</script>
