<template>
    <AppLayout title="Detail Pelanggan" :subtitle="customer.customer_code">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Profile Card -->
            <div class="glass-card p-6 animate-fade-in-up">
                <div class="flex flex-col items-center text-center mb-6">
                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-3xl font-bold text-white mb-4 shadow-xl shadow-blue-500/25">
                        {{ customer.name.charAt(0) }}
                    </div>
                    <h2 class="text-xl font-bold text-white">{{ customer.name }}</h2>
                    <p class="text-sm font-mono text-gray-500">{{ customer.customer_code }}</p>
                    <StatusBadge :status="customer.status" class="mt-2" />
                </div>

                <div class="space-y-3 border-t border-white/10 pt-4">
                    <InfoRow icon="📞" label="Telepon" :value="customer.phone" />
                    <InfoRow icon="📧" label="Email" :value="customer.email || '-'" />
                    <InfoRow icon="📍" label="Alamat" :value="customer.address" />
                    <InfoRow icon="📦" label="Paket" :value="customer.package?.name || 'Belum pilih'" />
                    <InfoRow icon="📅" label="Registrasi" :value="customer.registration_date" />
                    <InfoRow icon="✅" label="Aktivasi" :value="customer.activation_date || '-'" />
                </div>

                <div class="flex gap-2 mt-6">
                    <Link :href="`/customers/${customer.id}/edit`" class="btn-primary flex-1 justify-center">
                        Edit
                    </Link>
                    <Link href="/customers" class="btn-ghost flex-1 justify-center">
                        Kembali
                    </Link>
                </div>
            </div>

            <!-- Right Column -->
            <div class="lg:col-span-2 space-y-6">

                <!-- ONT & Network Info -->
                <div class="glass-card p-6 animate-fade-in-up delay-1">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-white">🔌 Perangkat & Jaringan</h3>
                        <button
                            v-if="!customer.ont"
                            @click="showAssignOnt = true"
                            class="btn-success text-xs"
                        >
                            + Pasang ONT
                        </button>
                    </div>

                    <div v-if="customer.ont" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-white/5 rounded-xl p-4 space-y-2">
                            <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider">ONT Info</p>
                            <InfoRow label="Serial Number" :value="customer.ont.serial_number" mono />
                            <InfoRow label="MAC Address" :value="customer.ont.mac_address || '-'" mono />
                            <InfoRow label="Brand/Model" :value="`${customer.ont.brand || '-'} ${customer.ont.model || ''}`" />
                            <InfoRow label="Status">
                                <StatusBadge :status="customer.ont.status" />
                            </InfoRow>
                        </div>
                        <div class="bg-white/5 rounded-xl p-4 space-y-2">
                            <p class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Topologi Jaringan</p>
                            <InfoRow label="OLT" :value="customer.ont.odp?.odc?.olt?.name || '-'" />
                            <InfoRow label="ODC" :value="customer.ont.odp?.odc?.name || '-'" />
                            <InfoRow label="ODP" :value="customer.ont.odp?.name || '-'" />
                            <InfoRow label="Port" :value="`Port #${customer.ont.port_number}`" />
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">Rx Power</span>
                                <span :class="signalClass(customer.ont.rx_power)" class="text-sm font-mono font-bold">
                                    {{ customer.ont.rx_power ? `${customer.ont.rx_power} dBm` : '-' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center py-8 text-center">
                        <svg class="w-16 h-16 text-gray-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0"/>
                        </svg>
                        <p class="text-gray-500 text-sm">ONT belum dipasang</p>
                        <p class="text-gray-600 text-xs mt-1">Klik "Pasang ONT" untuk menghubungkan perangkat</p>
                    </div>
                </div>

                <!-- Invoice History -->
                <div class="glass-card p-6 animate-fade-in-up delay-2">
                    <h3 class="text-lg font-semibold text-white mb-4">💳 Riwayat Invoice</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No. Invoice</th>
                                <th>Periode</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="inv in customer.invoices" :key="inv.id">
                                <td class="font-mono text-xs text-blue-400">{{ inv.invoice_number }}</td>
                                <td>{{ getMonthName(inv.period_month) }} {{ inv.period_year }}</td>
                                <td>Rp {{ Number(inv.amount).toLocaleString('id-ID') }}</td>
                                <td><StatusBadge :status="inv.status" /></td>
                            </tr>
                            <tr v-if="!customer.invoices?.length">
                                <td colspan="4" class="text-center text-gray-600 py-6">Belum ada invoice</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Ticket History -->
                <div class="glass-card p-6 animate-fade-in-up delay-3">
                    <h3 class="text-lg font-semibold text-white mb-4">🎫 Riwayat Tiket</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No. Tiket</th>
                                <th>Kategori</th>
                                <th>Prioritas</th>
                                <th>Status</th>
                                <th>Teknisi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="tkt in customer.tickets" :key="tkt.id">
                                <td class="font-mono text-xs text-blue-400">{{ tkt.ticket_number }}</td>
                                <td class="capitalize">{{ tkt.category?.replace('_', ' ') }}</td>
                                <td class="capitalize">{{ tkt.priority }}</td>
                                <td><StatusBadge :status="tkt.status" /></td>
                                <td>{{ tkt.assignee?.name || '-' }}</td>
                            </tr>
                            <tr v-if="!customer.tickets?.length">
                                <td colspan="5" class="text-center text-gray-600 py-6">Belum ada tiket</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Assign ONT Modal -->
        <Teleport to="body">
            <div v-if="showAssignOnt" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showAssignOnt = false"></div>
                <div class="relative glass-card p-6 max-w-lg w-full animate-fade-in-up">
                    <h3 class="text-lg font-semibold text-white mb-4">🔌 Pasang ONT Baru</h3>
                    <form @submit.prevent="submitOnt" class="space-y-4">
                        <div>
                            <label class="form-label">ODP *</label>
                            <select v-model="ontForm.odp_id" class="form-select" required>
                                <option value="">-- Pilih ODP --</option>
                                <!-- ODP options would be loaded dynamically -->
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Serial Number *</label>
                                <input v-model="ontForm.serial_number" type="text" class="form-input" required />
                            </div>
                            <div>
                                <label class="form-label">Port Number *</label>
                                <input v-model="ontForm.port_number" type="number" min="1" class="form-input" required />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">MAC Address</label>
                                <input v-model="ontForm.mac_address" type="text" class="form-input" placeholder="AA:BB:CC:DD:EE:FF" />
                            </div>
                            <div>
                                <label class="form-label">Brand</label>
                                <input v-model="ontForm.brand" type="text" class="form-input" placeholder="ZTE / Huawei" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Rx Power (dBm)</label>
                                <input v-model="ontForm.rx_power" type="number" step="0.01" class="form-input" />
                            </div>
                            <div>
                                <label class="form-label">Tx Power (dBm)</label>
                                <input v-model="ontForm.tx_power" type="number" step="0.01" class="form-input" />
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-white/10">
                            <button type="button" @click="showAssignOnt = false" class="btn-ghost">Batal</button>
                            <button type="submit" :disabled="ontForm.processing" class="btn-success">Pasang ONT</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({ customer: Object });

const showAssignOnt = ref(false);

const ontForm = useForm({
    odp_id: '',
    serial_number: '',
    port_number: '',
    mac_address: '',
    brand: '',
    model: '',
    rx_power: '',
    tx_power: '',
});

function submitOnt() {
    ontForm.post(`/customers/${props.customer.id}/assign-ont`, {
        onSuccess: () => { showAssignOnt.value = false; },
    });
}

function signalClass(rx) {
    if (rx === null || rx === undefined) return 'text-gray-500';
    if (rx >= -20) return 'text-emerald-400';
    if (rx >= -25) return 'text-green-400';
    if (rx >= -28) return 'text-yellow-400';
    return 'text-red-400';
}

function getMonthName(m) {
    return ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'][m] || '';
}

// InfoRow sub-component
</script>

<script>
export default {
    components: {
        InfoRow: {
            props: {
                icon: { type: String, default: '' },
                label: { type: String, required: true },
                value: { type: String, default: '' },
                mono: { type: Boolean, default: false },
            },
            template: `
                <div class="flex items-start justify-between gap-2">
                    <span class="text-xs text-gray-500 shrink-0">{{ icon }} {{ label }}</span>
                    <span :class="['text-sm text-right', mono ? 'font-mono text-cyan-400' : 'text-gray-300']">
                        <slot>{{ value }}</slot>
                    </span>
                </div>
            `,
        },
    },
};
</script>
