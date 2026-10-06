<template>
    <div class="relative">
        <div class="flex">
            <input 
                type="text" 
                :value="modelValue" 
                @input="$emit('update:modelValue', $event.target.value)"
                class="w-full border-gray-300 rounded-l-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm" 
                :placeholder="placeholder"
            >
            <button 
                type="button" 
                @click="showCalculator = true"
                class="px-3 py-2 bg-indigo-50 text-indigo-600 border border-l-0 border-gray-300 rounded-r-lg hover:bg-indigo-100 font-medium text-sm flex items-center gap-1 transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                Kalkulator
            </button>
        </div>

        <!-- Calculator Modal -->
        <div v-if="showCalculator" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showCalculator = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    
                    <div class="bg-indigo-600 px-6 py-4 flex justify-between items-center">
                        <h3 class="text-lg leading-6 font-bold text-white flex items-center gap-2" id="modal-title">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            Limit Rate Kalkulator (MikroTik)
                        </h3>
                        <button @click="showCalculator = false" class="text-white hover:text-indigo-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="px-6 py-5 bg-gray-50">
                        <!-- Result String -->
                        <div class="mb-6 bg-gray-900 rounded-xl p-4 shadow-inner border border-gray-700">
                            <div class="text-xs font-bold text-gray-400 mb-1 uppercase tracking-wider">Hasil Limit Rate String:</div>
                            <div class="text-green-400 font-mono text-lg break-all">{{ generatedString || 'Kosong' }}</div>
                            <div class="text-[10px] text-gray-500 mt-2 font-mono">Format: rx-rate/tx-rate rx-burst-rate/tx-burst-rate rx-burst-threshold/tx-burst-threshold rx-burst-time/tx-burst-time priority rx-rate-min/tx-rate-min</div>
                        </div>

                        <div class="space-y-6">
                            <!-- Basic Rate -->
                            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                                <h4 class="text-sm font-bold text-indigo-900 mb-4 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                    Limit Utama (Max Limit)
                                </h4>
                                <div class="grid grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-2">Upload (rx-rate)</label>
                                        <div class="flex">
                                            <input v-model="calc.rx" type="number" min="1" class="w-full border-gray-300 rounded-l-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                            <select v-model="calc.rxUnit" class="border-l-0 border-gray-300 rounded-r-lg bg-gray-50 text-sm focus:ring-indigo-500 focus:border-indigo-500 font-bold">
                                                <option value="k">Kbps</option>
                                                <option value="M">Mbps</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-2">Download (tx-rate)</label>
                                        <div class="flex">
                                            <input v-model="calc.tx" type="number" min="1" class="w-full border-gray-300 rounded-l-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                            <select v-model="calc.txUnit" class="border-l-0 border-gray-300 rounded-r-lg bg-gray-50 text-sm focus:ring-indigo-500 focus:border-indigo-500 font-bold">
                                                <option value="k">Kbps</option>
                                                <option value="M">Mbps</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Enable Burst Toggle -->
                            <div class="flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border border-gray-100 cursor-pointer" @click="enableBurst = !enableBurst">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900">Aktifkan Fitur Burst & Advanced</h4>
                                    <p class="text-xs text-gray-500 mt-1">Konfigurasi Burst Rate, Threshold, Time, Priority & Limit At</p>
                                </div>
                                <div class="relative">
                                    <input type="checkbox" v-model="enableBurst" class="sr-only" @click.stop>
                                    <div class="block w-10 h-6 rounded-full transition-colors" :class="enableBurst ? 'bg-indigo-500' : 'bg-gray-300'"></div>
                                    <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform" :class="enableBurst ? 'transform translate-x-4' : ''"></div>
                                </div>
                            </div>

                            <!-- Burst Settings -->
                            <div v-if="enableBurst" class="space-y-4 transition-all">
                                <div class="bg-indigo-50/50 p-4 rounded-xl border border-indigo-100">
                                    <h4 class="text-sm font-bold text-indigo-900 mb-4">Burst Limit (Kecepatan Maksimal Sementara)</h4>
                                    <div class="grid grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-2">Upload Burst</label>
                                            <div class="flex">
                                                <input v-model="calc.burstRx" type="number" min="1" class="w-full border-gray-300 rounded-l-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                                <select v-model="calc.burstRxUnit" class="border-l-0 border-gray-300 rounded-r-lg bg-gray-50 text-sm focus:ring-indigo-500 focus:border-indigo-500 font-bold">
                                                    <option value="k">Kbps</option>
                                                    <option value="M">Mbps</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-2">Download Burst</label>
                                            <div class="flex">
                                                <input v-model="calc.burstTx" type="number" min="1" class="w-full border-gray-300 rounded-l-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                                <select v-model="calc.burstTxUnit" class="border-l-0 border-gray-300 rounded-r-lg bg-gray-50 text-sm focus:ring-indigo-500 focus:border-indigo-500 font-bold">
                                                    <option value="k">Kbps</option>
                                                    <option value="M">Mbps</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-indigo-50/50 p-4 rounded-xl border border-indigo-100">
                                    <h4 class="text-sm font-bold text-indigo-900 mb-4">Burst Threshold (Batas Kecepatan Pemicu Burst)</h4>
                                    <div class="grid grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-2">Upload Threshold</label>
                                            <div class="flex">
                                                <input v-model="calc.threshRx" type="number" min="1" class="w-full border-gray-300 rounded-l-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                                <select v-model="calc.threshRxUnit" class="border-l-0 border-gray-300 rounded-r-lg bg-gray-50 text-sm focus:ring-indigo-500 focus:border-indigo-500 font-bold">
                                                    <option value="k">Kbps</option>
                                                    <option value="M">Mbps</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-2">Download Threshold</label>
                                            <div class="flex">
                                                <input v-model="calc.threshTx" type="number" min="1" class="w-full border-gray-300 rounded-l-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                                <select v-model="calc.threshTxUnit" class="border-l-0 border-gray-300 rounded-r-lg bg-gray-50 text-sm focus:ring-indigo-500 focus:border-indigo-500 font-bold">
                                                    <option value="k">Kbps</option>
                                                    <option value="M">Mbps</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-indigo-50/50 p-4 rounded-xl border border-indigo-100">
                                    <h4 class="text-sm font-bold text-indigo-900 mb-4">Burst Time & Priority</h4>
                                    <div class="grid grid-cols-3 gap-6">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-2">Upload Time (s)</label>
                                            <input v-model="calc.timeRx" type="number" min="1" class="w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm" placeholder="detik">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-2">Download Time (s)</label>
                                            <input v-model="calc.timeTx" type="number" min="1" class="w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm" placeholder="detik">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-2">Priority (1-8)</label>
                                            <input v-model="calc.priority" type="number" min="1" max="8" class="w-full border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-indigo-50/50 p-4 rounded-xl border border-indigo-100">
                                    <h4 class="text-sm font-bold text-indigo-900 mb-4">Minimum Limit (Limit At / GARANSI)</h4>
                                    <div class="grid grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-2">Upload Limit At</label>
                                            <div class="flex">
                                                <input v-model="calc.minRx" type="number" min="1" class="w-full border-gray-300 rounded-l-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                                <select v-model="calc.minRxUnit" class="border-l-0 border-gray-300 rounded-r-lg bg-gray-50 text-sm focus:ring-indigo-500 focus:border-indigo-500 font-bold">
                                                    <option value="k">Kbps</option>
                                                    <option value="M">Mbps</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-2">Download Limit At</label>
                                            <div class="flex">
                                                <input v-model="calc.minTx" type="number" min="1" class="w-full border-gray-300 rounded-l-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                                <select v-model="calc.minTxUnit" class="border-l-0 border-gray-300 rounded-r-lg bg-gray-50 text-sm focus:ring-indigo-500 focus:border-indigo-500 font-bold">
                                                    <option value="k">Kbps</option>
                                                    <option value="M">Mbps</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white px-6 py-4 border-t flex justify-end gap-3 rounded-b-2xl">
                        <button type="button" @click="showCalculator = false" class="px-5 py-2.5 border border-gray-300 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-100">Batal</button>
                        <button type="button" @click="applyLimit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 shadow-md">Terapkan Limit</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: 'Contoh: 1M/1M'
    }
});

const emit = defineEmits(['update:modelValue']);

const showCalculator = ref(false);
const enableBurst = ref(false);

const calc = ref({
    rx: 1, rxUnit: 'M',
    tx: 1, txUnit: 'M',
    
    burstRx: 2, burstRxUnit: 'M',
    burstTx: 2, burstTxUnit: 'M',
    
    threshRx: 512, threshRxUnit: 'k',
    threshTx: 512, threshTxUnit: 'k',
    
    timeRx: 8,
    timeTx: 8,
    
    priority: 8,
    
    minRx: 512, minRxUnit: 'k',
    minTx: 512, minTxUnit: 'k'
});

// Helper to parse existing string (if user opens calculator on existing value)
const parseExistingValue = () => {
    if (!props.modelValue) return;
    
    const parts = props.modelValue.trim().split(/\s+/);
    if (parts.length > 0 && parts[0].includes('/')) {
        const [rx, tx] = parts[0].split('/');
        extractValAndUnit(rx, 'rx');
        extractValAndUnit(tx, 'tx');
    }
    
    if (parts.length >= 6) {
        enableBurst.value = true;
        
        const [brx, btx] = parts[1].split('/');
        extractValAndUnit(brx, 'burstRx');
        extractValAndUnit(btx, 'burstTx');
        
        const [thrx, thtx] = parts[2].split('/');
        extractValAndUnit(thrx, 'threshRx');
        extractValAndUnit(thtx, 'threshTx');
        
        const [trx, ttx] = parts[3].split('/');
        calc.value.timeRx = parseInt(trx) || 8;
        calc.value.timeTx = parseInt(ttx) || 8;
        
        calc.value.priority = parseInt(parts[4]) || 8;
        
        const [mrx, mtx] = parts[5].split('/');
        extractValAndUnit(mrx, 'minRx');
        extractValAndUnit(mtx, 'minTx');
    } else {
        enableBurst.value = false;
    }
};

const extractValAndUnit = (str, keyPrefix) => {
    if (!str) return;
    const match = str.match(/^(\d+)([kKMm]?)$/);
    if (match) {
        calc.value[keyPrefix] = parseInt(match[1]);
        calc.value[keyPrefix + 'Unit'] = (match[2] || 'k').toUpperCase() === 'M' ? 'M' : 'k';
    }
};

onMounted(() => {
    parseExistingValue();
});

watch(showCalculator, (val) => {
    if (val) parseExistingValue();
});

const generatedString = computed(() => {
    if (!calc.value.rx || !calc.value.tx) return '';
    
    const base = `${calc.value.rx}${calc.value.rxUnit}/${calc.value.tx}${calc.value.txUnit}`;
    
    if (!enableBurst.value) return base;
    
    const burst = `${calc.value.burstRx}${calc.value.burstRxUnit}/${calc.value.burstTx}${calc.value.burstTxUnit}`;
    const thresh = `${calc.value.threshRx}${calc.value.threshRxUnit}/${calc.value.threshTx}${calc.value.threshTxUnit}`;
    const time = `${calc.value.timeRx}/${calc.value.timeTx}`;
    const prio = `${calc.value.priority}`;
    const min = `${calc.value.minRx}${calc.value.minRxUnit}/${calc.value.minTx}${calc.value.minTxUnit}`;
    
    return `${base} ${burst} ${thresh} ${time} ${prio} ${min}`;
});

const applyLimit = () => {
    emit('update:modelValue', generatedString.value);
    showCalculator.value = false;
};
</script>
