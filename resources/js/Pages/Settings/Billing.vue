<template>
    <AppLayout title="Pengaturan Billing" subtitle="Konfigurasi tagihan, tanggal jatuh tempo, dan isolir pelanggan">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden max-w-4xl">
            <div class="p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Tipe Billing -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-900 mb-2">Tipe Billing / Siklus Tagihan</label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none" :class="form.billing_type === 'prabayar' ? 'border-indigo-600 ring-1 ring-indigo-600' : 'border-gray-300'">
                                    <input type="radio" name="billing_type" value="prabayar" v-model="form.billing_type" class="sr-only">
                                    <span class="flex flex-1">
                                        <span class="flex flex-col">
                                            <span class="block text-sm font-medium text-gray-900">Prabayar</span>
                                            <span class="mt-1 flex items-center text-xs text-gray-500">Bayar dulu baru pakai (Prepaid)</span>
                                        </span>
                                    </span>
                                    <svg class="h-5 w-5 text-indigo-600" :class="form.billing_type === 'prabayar' ? 'visible' : 'invisible'" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                </label>
                                <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none" :class="form.billing_type === 'pascabayar' ? 'border-indigo-600 ring-1 ring-indigo-600' : 'border-gray-300'">
                                    <input type="radio" name="billing_type" value="pascabayar" v-model="form.billing_type" class="sr-only">
                                    <span class="flex flex-1">
                                        <span class="flex flex-col">
                                            <span class="block text-sm font-medium text-gray-900">Pasca Bayar</span>
                                            <span class="mt-1 flex items-center text-xs text-gray-500">Pakai dulu baru bayar (Postpaid)</span>
                                        </span>
                                    </span>
                                    <svg class="h-5 w-5 text-indigo-600" :class="form.billing_type === 'pascabayar' ? 'visible' : 'invisible'" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                </label>
                                <label class="relative flex cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none" :class="form.billing_type === 'prorata' ? 'border-indigo-600 ring-1 ring-indigo-600' : 'border-gray-300'">
                                    <input type="radio" name="billing_type" value="prorata" v-model="form.billing_type" class="sr-only">
                                    <span class="flex flex-1">
                                        <span class="flex flex-col">
                                            <span class="block text-sm font-medium text-gray-900">Prorata</span>
                                            <span class="mt-1 flex items-center text-xs text-gray-500">Hitungan per hari (Cut-off date)</span>
                                        </span>
                                    </span>
                                    <svg class="h-5 w-5 text-indigo-600" :class="form.billing_type === 'prorata' ? 'visible' : 'invisible'" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                </label>
                            </div>
                            <div v-if="form.errors.billing_type" class="mt-1 text-sm text-red-600">{{ form.errors.billing_type }}</div>
                        </div>

                        <!-- Tanggal Terbit Invoice -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-1">Tanggal Terbit Invoice</label>
                            <p class="text-xs text-gray-500 mb-2">Tanggal di setiap bulannya saat invoice dibuat dan dikirim ke pelanggan.</p>
                            <div class="relative rounded-md shadow-sm">
                                <input type="number" v-model="form.invoice_issue_date" min="1" max="28" class="form-input block w-full rounded-md border-gray-300 pl-4 pr-12 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="1" />
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <span class="text-gray-500 sm:text-sm">tiap bulan</span>
                                </div>
                            </div>
                            <div v-if="form.errors.invoice_issue_date" class="mt-1 text-sm text-red-600">{{ form.errors.invoice_issue_date }}</div>
                        </div>

                        <!-- Jatuh Tempo -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-1">Jatuh Tempo (Due Date)</label>
                            <p class="text-xs text-gray-500 mb-2">Berapa hari setelah invoice terbit tagihan harus dilunasi.</p>
                            <div class="relative rounded-md shadow-sm">
                                <input type="number" v-model="form.due_date_days" min="0" class="form-input block w-full rounded-md border-gray-300 pl-4 pr-16 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="7" />
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <span class="text-gray-500 sm:text-sm">hari</span>
                                </div>
                            </div>
                            <div v-if="form.errors.due_date_days" class="mt-1 text-sm text-red-600">{{ form.errors.due_date_days }}</div>
                        </div>

                        <!-- Isolir -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-900 mb-1">Isolir / Suspend Otomatis</label>
                            <p class="text-xs text-gray-500 mb-2">Layanan pelanggan diisolir otomatis sekian hari setelah jatuh tempo.</p>
                            <div class="relative rounded-md shadow-sm">
                                <input type="number" v-model="form.isolate_days" min="0" class="form-input block w-full rounded-md border-gray-300 pl-4 pr-16 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="3" />
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <span class="text-gray-500 sm:text-sm">hari</span>
                                </div>
                            </div>
                            <div v-if="form.errors.isolate_days" class="mt-1 text-sm text-red-600">{{ form.errors.isolate_days }}</div>
                        </div>
                    </div>

                    <div class="pt-5 border-t border-gray-100 flex justify-end">
                        <button type="submit" :disabled="form.processing" class="btn-primary px-6 py-2">
                            <span v-if="form.processing">Menyimpan...</span>
                            <span v-else>Simpan Pengaturan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    billing_type: String,
    invoice_issue_date: String,
    due_date_days: String,
    isolate_days: String,
});

const form = useForm({
    billing_type: props.billing_type || 'prabayar',
    invoice_issue_date: props.invoice_issue_date || '1',
    due_date_days: props.due_date_days || '7',
    isolate_days: props.isolate_days || '3',
});

function submit() {
    form.post('/settings/billing', {
        preserveScroll: true,
    });
}
</script>
