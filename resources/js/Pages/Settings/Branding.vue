<template>
    <AppLayout title="Branding" subtitle="Pengaturan identitas aplikasi">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-200 bg-gray-50/50">
                    <h3 class="text-lg font-bold text-gray-900">Logo Aplikasi</h3>
                    <p class="text-sm text-gray-500 mt-1">Logo ini akan digunakan sebagai branding utama pada sidebar, header, dan halaman login.</p>
                </div>

                <div class="p-6">
                    <!-- Success message -->
                    <div v-if="$page.props.flash?.success" class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm font-medium">
                        ✅ {{ $page.props.flash.success }}
                    </div>

                    <form @submit.prevent="submitForm" enctype="multipart/form-data">
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Aplikasi</label>
                            <input type="text" v-model="appName" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Contoh: ISP Manager" />
                            <p class="text-xs text-gray-500 mt-2">Nama ini akan ditampilkan jika logo belum diupload. Kosongkan jika ingin full logo.</p>
                        </div>

                        <div class="mb-8">
                            <label class="block text-sm font-semibold text-gray-700 mb-4">Preview Logo Saat Ini</label>
                            
                            <div class="bg-gray-100 rounded-xl p-8 flex items-center justify-center border-2 border-dashed border-gray-300 relative min-h-[160px]">
                                <img v-if="logoPreview" 
                                     :src="logoPreview" 
                                     class="max-h-[80px] w-auto object-contain" 
                                     alt="Logo Preview"
                                     @error="onImgError" />
                                
                                <div v-else class="text-center">
                                    <div class="w-16 h-16 mx-auto mb-3 text-gray-400">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <span class="text-gray-500 font-medium">Belum ada logo custom</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Upload Logo Baru</label>
                            <input type="file" ref="fileInput" @change="handleFileChange" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer" />
                            <p class="text-xs text-gray-500 mt-2">Gunakan format PNG, JPG, atau SVG dengan background transparan. Disarankan ukuran horizontal (landscape).</p>
                        </div>

                        <div class="flex items-center gap-3 pt-6 border-t border-gray-100">
                            <button type="submit" :disabled="isSubmitting" class="btn-primary">
                                <span v-if="isSubmitting">Menyimpan...</span>
                                <span v-else>Simpan Perubahan</span>
                            </button>
                            <button type="button" v-if="props.current_logo" @click="removeLogo" :disabled="isSubmitting" class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors">
                                Hapus Logo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    current_logo: String,
    current_app_name: String,
});

const page = usePage();
const fileInput = ref(null);
const selectedFile = ref(null);
const isSubmitting = ref(false);
const appName = ref(props.current_app_name || '');

// Logo preview: use selected file preview, or current logo from props
const logoPreview = ref(props.current_logo || null);

const onImgError = () => {
    logoPreview.value = null;
};

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        selectedFile.value = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const submitForm = () => {
    isSubmitting.value = true;
    
    const formData = new FormData();
    formData.append('app_name', appName.value);
    
    if (selectedFile.value) {
        formData.append('app_logo', selectedFile.value);
    }

    // Get CSRF token from cookie (Laravel sets XSRF-TOKEN cookie)
    const xsrfToken = document.cookie
        .split('; ')
        .find(row => row.startsWith('XSRF-TOKEN='))
        ?.split('=')[1];

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
    .then(response => {
        isSubmitting.value = false;
        // Force full page reload to refresh everything including sidebar
        window.location.href = route('settings.branding');
    })
    .catch(error => {
        isSubmitting.value = false;
        alert('Gagal menyimpan: ' + error.message);
    });
};

const removeLogo = () => {
    if (confirm('Apakah Anda yakin ingin menghapus logo ini?')) {
        isSubmitting.value = true;
        
        const formData = new FormData();
        formData.append('remove_logo', '1');
        formData.append('app_name', appName.value);
        
        const xsrfToken = document.cookie
            .split('; ')
            .find(row => row.startsWith('XSRF-TOKEN='))
            ?.split('=')[1];

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
            window.location.href = route('settings.branding');
        })
        .catch(error => {
            isSubmitting.value = false;
            alert('Gagal menghapus: ' + error.message);
        });
    }
};
</script>
