<template>
    <AppLayout title="Branding" subtitle="Pengaturan identitas aplikasi">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-200 bg-gray-50/50">
                    <h3 class="text-lg font-bold text-gray-900">Logo Aplikasi</h3>
                    <p class="text-sm text-gray-500 mt-1">Logo ini akan digunakan sebagai branding utama pada sidebar, header, dan halaman login.</p>
                </div>

                <div class="p-6">
                    <form @submit.prevent="submitForm">
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Aplikasi</label>
                            <input type="text" v-model="form.app_name" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Contoh: ISP Manager" />
                            <p class="text-xs text-gray-500 mt-2">Nama ini akan ditampilkan jika logo belum diupload.</p>
                            <div v-if="form.errors.app_name" class="text-red-500 text-xs mt-1">{{ form.errors.app_name }}</div>
                        </div>

                        <div class="mb-8">
                            <label class="block text-sm font-semibold text-gray-700 mb-4">Preview Logo Saat Ini</label>
                            
                            <div class="bg-gray-100 rounded-xl p-8 flex items-center justify-center border-2 border-dashed border-gray-300 relative min-h-[160px]">
                                <img v-if="(previewUrl || $page.props.app_logo) && !imageError" 
                                     :src="previewUrl || $page.props.app_logo" 
                                     class="max-h-[80px] w-auto object-contain" 
                                     alt="Logo Preview"
                                     @error="handleImageError" />
                                
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
                            <div v-if="form.errors.app_logo" class="text-red-500 text-xs mt-1">{{ form.errors.app_logo }}</div>
                        </div>

                        <div class="flex items-center gap-3 pt-6 border-t border-gray-100">
                            <button type="submit" :disabled="form.processing || (!form.app_name && !form.app_logo && !form.remove_logo && form.app_name === $page.props.app_name)" class="btn-primary">
                                Simpan Perubahan
                            </button>
                            <button type="button" v-if="$page.props.app_logo" @click="removeLogo" :disabled="form.processing" class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors">
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
import { ref } from 'vue';
import { usePage, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const page = usePage();

const fileInput = ref(null);
const previewUrl = ref(null);
const imageError = ref(false);

const handleImageError = () => {
    imageError.value = true;
};

const form = useForm({
    app_name: page.props.app_name || '',
    app_logo: null,
    remove_logo: false
});

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.app_logo = file;
        form.remove_logo = false;
        previewUrl.value = URL.createObjectURL(file);
        imageError.value = false;
    } else {
        form.app_logo = null;
        previewUrl.value = null;
        imageError.value = false;
    }
};

const submitForm = () => {
    form.post(route('settings.branding.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.app_logo = null;
            form.remove_logo = false;
            if (fileInput.value) fileInput.value.value = '';
            previewUrl.value = null;
        }
    });
};

const removeLogo = () => {
    if (confirm('Apakah Anda yakin ingin menghapus logo ini dan kembali ke logo default?')) {
        form.remove_logo = true;
        form.app_logo = null;
        previewUrl.value = null;
        submitForm();
    }
};
</script>
