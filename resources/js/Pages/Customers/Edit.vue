<template>
    <AppLayout title="Edit Pelanggan" :subtitle="customer.customer_code">
        <div class="max-w-3xl">
            <form @submit.prevent="submit" class="glass-card p-6 space-y-6 animate-fade-in-up">
                <div class="flex items-center gap-3 pb-4 border-b border-gray-200">
                    <div class="w-10 h-10 rounded-xl bg-yellow-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Edit Data Pelanggan</h3>
                        <p class="text-sm text-gray-500">{{ customer.name }} - {{ customer.customer_code }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="md:col-span-2">
                        <label class="form-label">Nama Lengkap *</label>
                        <input v-model="form.name" type="text" class="form-input" required />
                        <p v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="form-label">Email</label>
                        <input v-model="form.email" type="email" class="form-input" />
                    </div>

                    <div>
                        <label class="form-label">No. Telepon *</label>
                        <input v-model="form.phone" type="text" class="form-input" required />
                    </div>

                    <div class="md:col-span-2">
                        <label class="form-label">Alamat Lengkap *</label>
                        <textarea v-model="form.address" class="form-input" rows="3" required></textarea>
                    </div>

                    <div>
                        <label class="form-label">Latitude</label>
                        <input v-model="form.latitude" type="number" step="0.00000001" class="form-input" />
                    </div>

                    <div>
                        <label class="form-label">Longitude</label>
                        <input v-model="form.longitude" type="number" step="0.00000001" class="form-input" />
                    </div>

                    <div>
                        <label class="form-label">Paket Internet</label>
                        <select v-model="form.package_id" class="form-select">
                            <option value="">-- Pilih Paket --</option>
                            <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                {{ pkg.name }} - Rp {{ Number(pkg.price).toLocaleString('id-ID') }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Status *</label>
                        <select v-model="form.status" class="form-select" required>
                            <option value="booking">Booking</option>
                            <option value="survey">Survey</option>
                            <option value="installing">Proses Pasang</option>
                            <option value="active">Aktif</option>
                            <option value="suspended">Suspended</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="form-label">Catatan</label>
                        <textarea v-model="form.notes" class="form-input" rows="2"></textarea>
                    </div>
                </div>

                <!-- ONT Info (read-only) -->
                <div v-if="customer.ont" class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-500 mb-3">Perangkat Terhubung</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                        <div>
                            <span class="text-gray-500">ONT S/N</span>
                            <p class="text-cyan-400 font-mono mt-1">{{ customer.ont.serial_number }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">ODP</span>
                            <p class="text-gray-600 mt-1">{{ customer.ont.odp?.name || '-' }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">Port</span>
                            <p class="text-gray-600 mt-1">#{{ customer.ont.port_number }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500">Rx Power</span>
                            <p class="text-gray-600 mt-1">{{ customer.ont.rx_power ? `${customer.ont.rx_power} dBm` : '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                    <Link :href="`/customers/${customer.id}`" class="btn-ghost">Batal</Link>
                    <button type="submit" :disabled="form.processing" class="btn-primary">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ customer: Object, packages: Array, availableOdps: Array });

const form = useForm({
    name: props.customer.name,
    email: props.customer.email || '',
    phone: props.customer.phone,
    address: props.customer.address,
    latitude: props.customer.latitude || '',
    longitude: props.customer.longitude || '',
    package_id: props.customer.package_id || '',
    status: props.customer.status,
    notes: props.customer.notes || '',
});

function submit() {
    form.post(`/customers/${props.customer.id}/update`);
}
</script>
