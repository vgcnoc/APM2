<template>
    <AppLayout title="Profil Perusahaan" subtitle="Pengaturan identitas dan informasi kontak perusahaan">
        <div class="max-w-[1600px] w-full mx-auto space-y-6 font-sans">
            <!-- Success message -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-2">
                <span>✅</span>
                <span>{{ $page.props.flash.success }}</span>
            </div>
            
            <form @submit.prevent="submitForm" enctype="multipart/form-data">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Kolom 1: Profil & Logo Aplikasi -->
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                        <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Logo &amp; Nama Aplikasi</h3>
                                <p class="text-xs text-gray-400 mt-0.5">Identitas utama aplikasi di sidebar dan menu.</p>
                            </div>
                        </div>
                        <div class="p-6 space-y-5 flex-1">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Nama Aplikasi <span class="text-red-500">*</span></label>
                                <input type="text" v-model="form.appName" class="block w-full rounded-xl border-gray-200 bg-gray-50/50 focus:bg-white shadow-xs focus:border-indigo-500 focus:ring-indigo-500 text-xs font-semibold py-2.5 px-3 transition-all" placeholder="Contoh: VIZBILL" required />
                                <p class="text-[11px] text-gray-400 mt-1.5">Nama ini akan ditampilkan di sidebar jika logo belum diupload.</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-2">Preview Logo Aplikasi Saat Ini</label>
                                <div class="bg-gray-50/60 rounded-2xl p-6 flex items-center justify-center border-2 border-dashed border-gray-200 relative min-h-[160px] group transition-all hover:bg-gray-50 hover:border-indigo-300">
                                    <img v-if="appLogoPreview" :src="appLogoPreview" class="max-h-[80px] w-auto object-contain drop-shadow-xs transition-transform group-hover:scale-105" @error="onAppImgError" />
                                    <div v-else class="text-center text-gray-400">
                                        <svg class="w-12 h-12 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        <span class="text-xs font-bold block text-gray-600">Belum ada logo aplikasi</span>
                                        <span class="text-[11px] mt-0.5 block text-gray-400">Sistem menggunakan teks nama aplikasi</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Upload Logo Aplikasi Baru</label>
                                <input type="file" ref="appFileInput" @change="handleAppFileChange" accept="image/*" class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer transition-colors" />
                                <p class="text-[11px] text-gray-400 mt-1">Format: PNG transparan (Landscape disarankan). Maks. 2MB.</p>
                            </div>
                            
                            <div class="pt-1">
                                <button type="button" v-if="props.current_app_logo" @click="removeAppLogo" :disabled="isSubmitting" class="text-xs font-bold text-red-500 hover:text-red-700 transition-colors cursor-pointer">
                                    Hapus Logo Aplikasi
                                </button>
                            </div>
                            
                            <hr class="border-gray-100 my-2">
                            
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-2">Preview Favicon (Icon Tab)</label>
                                <div class="bg-gray-50/60 rounded-2xl p-4 flex items-center justify-center border-2 border-dashed border-gray-200 relative min-h-[90px] group transition-all hover:bg-gray-50 hover:border-indigo-300">
                                    <img v-if="appFaviconPreview" :src="appFaviconPreview" class="max-h-[40px] w-auto object-contain drop-shadow-xs transition-transform group-hover:scale-110" @error="onFaviconError" />
                                    <div v-else class="text-center text-gray-400">
                                        <svg class="w-7 h-7 mx-auto mb-1 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                        <span class="text-[11px] font-bold block text-gray-500">Belum ada favicon khusus</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Upload Favicon Baru</label>
                                <input type="file" ref="faviconInput" @change="handleFaviconChange" accept="image/png, image/x-icon, image/jpeg, image/gif, image/svg+xml" class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer transition-colors" />
                                <p class="text-[11px] text-gray-400 mt-1">Format: PNG, ICO, SVG. Disarankan rasio kotak (32x32 atau 64x64).</p>
                            </div>
                            
                            <div class="pt-1">
                                <button type="button" v-if="props.current_app_favicon" @click="removeFavicon" :disabled="isSubmitting" class="text-xs font-bold text-red-500 hover:text-red-700 transition-colors cursor-pointer">
                                    Hapus Favicon
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom 2: Info Perusahaan & Logo Perusahaan -->
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                        <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Data &amp; Logo Perusahaan</h3>
                                <p class="text-xs text-gray-400 mt-0.5">Digunakan untuk kop surat, invoice, dan kwitansi.</p>
                            </div>
                        </div>
                        <div class="p-6 space-y-5 flex-1">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Nama Perusahaan / PT</label>
                                <input type="text" v-model="form.companyName" class="block w-full rounded-xl border-gray-200 bg-gray-50/50 focus:bg-white shadow-xs focus:border-indigo-500 focus:ring-indigo-500 text-xs font-semibold py-2.5 px-3 transition-all" placeholder="Contoh: PT. Maju Bersama Internet" />
                            </div>
                            
                            <!-- Logo Perusahaan -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Logo Perusahaan (Untuk Invoice)</label>
                                    <div class="bg-gray-50/60 rounded-2xl p-4 flex items-center justify-center border-2 border-dashed border-gray-200 relative min-h-[100px] group transition-all hover:bg-gray-50 hover:border-blue-300">
                                        <img v-if="compLogoPreview" :src="compLogoPreview" class="max-h-[60px] w-auto object-contain drop-shadow-xs transition-transform group-hover:scale-105" @error="onCompImgError" />
                                        <div v-else class="text-center text-gray-400">
                                            <svg class="w-8 h-8 mx-auto mb-1 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                            <span class="text-[11px] font-bold block text-gray-500">Tanpa Logo Invoice</span>
                                        </div>
                                    </div>
                                    <div class="pt-2 flex justify-between items-center">
                                        <input type="file" ref="compFileInput" @change="handleCompFileChange" accept="image/*" class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer transition-colors" />
                                    </div>
                                    <button type="button" v-if="props.current_company_logo" @click="removeCompLogo" :disabled="isSubmitting" class="text-xs font-bold text-red-500 hover:text-red-700 transition-colors mt-1 block cursor-pointer">
                                        Hapus Logo Invoice
                                    </button>
                                </div>
                                <div class="text-[11px] text-gray-600 bg-blue-50/50 p-4 rounded-2xl border border-blue-100/80 leading-relaxed">
                                    <p class="font-bold text-blue-900 mb-1">💡 Tips Logo Invoice:</p>
                                    <p>Logo perusahaan biasanya berbentuk kotak atau horizontal dan akan dicetak pada kop surat invoice PDF, kwitansi, serta bukti pembayaran pelanggan.</p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Nomor Telepon / WA</label>
                                    <input type="text" v-model="form.companyPhone" class="block w-full rounded-xl border-gray-200 bg-gray-50/50 focus:bg-white shadow-xs focus:border-indigo-500 focus:ring-indigo-500 text-xs font-semibold py-2.5 px-3 transition-all" placeholder="08123456789" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Email Perusahaan</label>
                                    <input type="email" v-model="form.companyEmail" class="block w-full rounded-xl border-gray-200 bg-gray-50/50 focus:bg-white shadow-xs focus:border-indigo-500 focus:ring-indigo-500 text-xs font-semibold py-2.5 px-3 transition-all" placeholder="cs@perusahaan.com" />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Website</label>
                                <input type="text" v-model="form.companyWebsite" class="block w-full rounded-xl border-gray-200 bg-gray-50/50 focus:bg-white shadow-xs focus:border-indigo-500 focus:ring-indigo-500 text-xs font-semibold py-2.5 px-3 transition-all" placeholder="https://www.perusahaan.com" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Alamat Lengkap</label>
                                <textarea v-model="form.companyAddress" rows="3" class="block w-full rounded-xl border-gray-200 bg-gray-50/50 focus:bg-white shadow-xs focus:border-indigo-500 focus:ring-indigo-500 text-xs font-medium py-2.5 px-3 transition-all resize-none" placeholder="Alamat lengkap perusahaan..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex flex-col-reverse sm:flex-row items-center justify-end gap-3 p-5 bg-white rounded-3xl shadow-sm border border-gray-100">
                    <button type="submit" :disabled="isSubmitting" class="w-full sm:w-auto px-7 py-3 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-all shadow-sm shadow-indigo-200 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                        <svg v-if="isSubmitting" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span v-if="isSubmitting">Menyimpan Perubahan...</span>
                        <span v-else>Simpan Semua Pengaturan</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    current_app_logo: String,
    current_app_favicon: String,
    current_company_logo: String,
    current_app_name: String,
    company_name: String,
    company_address: String,
    company_phone: String,
    company_email: String,
    company_website: String,
});

const appFileInput = ref(null);
const faviconInput = ref(null);
const compFileInput = ref(null);

const selectedAppFile = ref(null);
const selectedFaviconFile = ref(null);
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
const appFaviconPreview = ref(props.current_app_favicon || null);
const compLogoPreview = ref(props.current_company_logo || null);

watch(() => props.current_app_logo, (val) => { appLogoPreview.value = val; });
watch(() => props.current_app_favicon, (val) => { appFaviconPreview.value = val; });
watch(() => props.current_company_logo, (val) => { compLogoPreview.value = val; });
watch(() => props.current_app_name, (val) => { form.appName = val || ''; });
watch(() => props.company_name, (val) => { form.companyName = val || ''; });
watch(() => props.company_address, (val) => { form.companyAddress = val || ''; });
watch(() => props.company_phone, (val) => { form.companyPhone = val || ''; });
watch(() => props.company_email, (val) => { form.companyEmail = val || ''; });
watch(() => props.company_website, (val) => { form.companyWebsite = val || ''; });

const onAppImgError = () => { appLogoPreview.value = null; };
const onFaviconError = () => { appFaviconPreview.value = null; };
const onCompImgError = () => { compLogoPreview.value = null; };

const handleAppFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        selectedAppFile.value = file;
        appLogoPreview.value = URL.createObjectURL(file);
    }
};

const handleFaviconChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        selectedFaviconFile.value = file;
        appFaviconPreview.value = URL.createObjectURL(file);
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
    if (selectedFaviconFile.value) formData.append('app_favicon', selectedFaviconFile.value);
    if (selectedCompFile.value) formData.append('company_logo', selectedCompFile.value);

    router.post(route('settings.branding.update'), formData, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            selectedAppFile.value = null;
            selectedFaviconFile.value = null;
            selectedCompFile.value = null;
            if (appFileInput.value) appFileInput.value.value = '';
            if (faviconInput.value) faviconInput.value.value = '';
            if (compFileInput.value) compFileInput.value.value = '';
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Berhasil Disimpan!',
                text: 'Profil dan logo aplikasi berhasil diperbarui.',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        },
        onError: (errors) => {
            isSubmitting.value = false;
            const msg = Object.values(errors || {})[0] || 'Gagal menyimpan pengaturan.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: msg,
                confirmButtonColor: '#dc2626'
            });
        }
    });
};

const removeAppLogo = () => {
    Swal.fire({
        title: 'Hapus Logo Aplikasi?',
        text: 'Logo aplikasi pada sidebar akan dihapus dan kembali menampilkan teks nama aplikasi.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus Logo',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b'
    }).then((res) => {
        if (res.isConfirmed) {
            isSubmitting.value = true;
            router.post(route('settings.branding.update'), { remove_app_logo: '1' }, {
                preserveScroll: true,
                onSuccess: () => {
                    isSubmitting.value = false;
                    appLogoPreview.value = null;
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Logo Aplikasi Dihapus',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                },
                onError: () => {
                    isSubmitting.value = false;
                }
            });
        }
    });
};

const removeFavicon = () => {
    Swal.fire({
        title: 'Hapus Favicon?',
        text: 'Favicon tab browser akan dihapus dan kembali menggunakan favicon default.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus Favicon',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b'
    }).then((res) => {
        if (res.isConfirmed) {
            isSubmitting.value = true;
            router.post(route('settings.branding.update'), { remove_app_favicon: '1' }, {
                preserveScroll: true,
                onSuccess: () => {
                    isSubmitting.value = false;
                    appFaviconPreview.value = null;
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Favicon Dihapus',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                },
                onError: () => {
                    isSubmitting.value = false;
                }
            });
        }
    });
};

const removeCompLogo = () => {
    Swal.fire({
        title: 'Hapus Logo Perusahaan?',
        text: 'Logo perusahaan untuk invoice dan kop surat akan dihapus.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus Logo',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b'
    }).then((res) => {
        if (res.isConfirmed) {
            isSubmitting.value = true;
            router.post(route('settings.branding.update'), { remove_company_logo: '1' }, {
                preserveScroll: true,
                onSuccess: () => {
                    isSubmitting.value = false;
                    compLogoPreview.value = null;
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Logo Perusahaan Dihapus',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                },
                onError: () => {
                    isSubmitting.value = false;
                }
            });
        }
    });
};
</script>
