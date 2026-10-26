<template>
    <div class="gis-controls" @keydown.esc="dismiss">
        <button v-if="panel" class="gis-dismiss" aria-label="Tutup panel peta" @click="panel = null"></button>

        <div class="gis-topbar gis-surface">
            <div class="gis-heading">
                <span class="gis-app-icon"><MapIcon /></span>
                <div><span class="gis-eyebrow">NETWORK EXPLORER</span><h2>Peta jaringan <span class="gis-live"><i></i> Live</span></h2></div>
            </div>
            <div class="gis-primary-tools" aria-label="Alat peta">
                <button :class="{ selected: overview }" :aria-pressed="overview" @click="toggleOverview" title="Ringkasan jaringan" aria-label="Ringkasan jaringan"><Squares2X2Icon /><span>Ringkasan</span></button>
                <button :class="{ selected: panel === 'layers' }" :aria-expanded="panel === 'layers'" aria-label="Layer jaringan" @click="togglePanel('layers')"><Square3Stack3DIcon /><span>Layer</span><b>{{ enabledLayers }}</b></button>
                <button :class="{ selected: panel === 'fiber' }" :aria-expanded="panel === 'fiber'" aria-label="Pengaturan jalur kabel" @click="togglePanel('fiber')"><ShareIcon /><span>Kabel</span></button>
                <span class="gis-tool-divider"></span>
                <button v-if="routeAllowed" :class="{ selected: activeTool === 'route' }" :aria-pressed="activeTool === 'route'" aria-label="Rute jalan" @click="$emit('route')"><ArrowTrendingUpIcon /><span>Rute jalan</span></button>
                <button v-if="finderAllowed" :class="{ selected: activeTool === 'finder' }" :aria-pressed="activeTool === 'finder'" aria-label="Cari ODP terdekat" @click="$emit('finder')"><ViewfinderCircleIcon /><span>Cari ODP</span></button>
            </div>
        </div>

        <aside v-if="overview && !['route', 'finder'].includes(activeTool)" class="gis-overview gis-surface" :class="{ 'gis-overlay-hidden': ['layers', 'fiber'].includes(panel) }" aria-label="Ringkasan dan pencarian jaringan">
            <div class="gis-panel-heading"><div><span class="gis-eyebrow">RUANG KERJA GIS</span><h3>Eksplorasi jaringan</h3></div><button class="gis-icon-close" aria-label="Tutup ringkasan" @click="overview = false"><XMarkIcon /></button></div>
            <div class="gis-search">
                <MagnifyingGlassIcon />
                <input :value="query" @input="$emit('update:query', $event.target.value)" @focus="$emit('update:searchFocused', true)" @blur="$emit('update:searchFocused', false)" @keydown.esc="$emit('update:searchFocused', false)" placeholder="Cari pelanggan, ODP, ODC…" aria-label="Cari pelanggan, ODP, ODC, atau wilayah" />
                <button v-if="query" @click="$emit('update:query', '')" aria-label="Hapus pencarian"><XMarkIcon /></button>
                <div v-if="searchFocused && query.trim().length >= 2" class="gis-results">
                    <button v-for="item in results" :key="`${item.type}-${item.id}`" @pointerdown.prevent @click="$emit('select', item)">
                        <span class="gis-result-icon" :data-kind="item.type"><component :is="resultIcons[item.type] || MapPinIcon" /></span>
                        <span><strong>{{ item.title }}</strong><small>{{ item.subtitle }}</small></span><ChevronRightIcon />
                    </button>
                    <p v-if="!results.length">Tidak ada lokasi yang cocok.</p>
                </div>
            </div>
            <div class="gis-area-select"><MapPinIcon /><select :value="area" @change="$emit('update:area', $event.target.value)" aria-label="Filter wilayah"><option value="all">Semua wilayah</option><option v-for="item in areas" :key="item.id" :value="item.id">{{ item.name }}</option></select></div>
            <div class="gis-metrics">
                <div v-for="item in metrics" :key="item.key"><span class="gis-metric-icon" :data-kind="item.key"><component :is="item.icon" /></span><div><strong>{{ counts[item.key] || 0 }}</strong><span>{{ item.label }}</span></div></div>
            </div>
            <div class="gis-connection-summary">
                <div class="gis-section-heading"><span>Koneksi pelanggan</span><button @click="$emit('refresh')" :disabled="refreshing" title="Perbarui status koneksi" aria-label="Perbarui status koneksi"><ArrowPathIcon :class="{ spinning: refreshing }" /></button></div>
                <div class="gis-status-grid"><div v-for="item in statuses" :key="item.key"><i :class="`gis-dot-${item.key}`"></i><strong>{{ connections[item.key] || 0 }}</strong><span>{{ item.label }}</span></div></div>
                <p class="gis-update" :class="{ 'has-error': error }" aria-live="polite">{{ updateLabel }}</p>
            </div>
            <div class="gis-overview-footer"><ShieldCheckIcon /><span>Status koneksi diperbarui setiap 30 detik</span></div>
        </aside>

        <section v-if="panel === 'layers'" class="gis-layer-panel gis-surface" aria-label="Pengaturan layer peta">
            <div class="gis-panel-heading"><div><span class="gis-eyebrow">VISIBILITAS</span><h3>Layer jaringan</h3></div><button class="gis-icon-close" @click="panel = null" aria-label="Tutup pengaturan layer"><XMarkIcon /></button></div>
            <label v-for="item in layerOptions" :key="item.key" class="gis-layer-row"><span class="gis-layer-symbol" :data-kind="item.key"><component :is="item.icon" /></span><span><strong>{{ item.label }}</strong><small>{{ item.description }}</small></span><input type="checkbox" :checked="layers[item.key]" @change="$emit('layer', item.key, $event.target.checked)" /><span class="gis-switch" aria-hidden="true"></span></label>
        </section>

        <section v-if="panel === 'fiber'" class="gis-layer-panel gis-surface" aria-label="Pengaturan jalur kabel">
            <div class="gis-panel-heading"><div><span class="gis-eyebrow">TOPOLOGI FIBER</span><h3>Jalur kabel</h3></div><button class="gis-icon-close" @click="panel = null" aria-label="Tutup pengaturan kabel"><XMarkIcon /></button></div>
            <div class="gis-fiber-modes" role="group" aria-label="Mode jalur kabel">
                <button :aria-pressed="fiberMode === 'straight'" :class="{ selected: fiberMode === 'straight' }" @click="$emit('fiber-mode', 'straight')"><span class="gis-straight-icon"></span><strong>Lurus</strong><small>Langsung antar titik</small></button>
                <button :aria-pressed="fiberMode === 'road'" :class="{ selected: fiberMode === 'road' }" @click="$emit('fiber-mode', 'road')"><ArrowTrendingUpIcon /><strong>Ikuti jalan</strong><small>Sesuai jalan pada peta</small></button>
            </div>
            <p class="gis-fiber-description">Berlaku untuk kabel feeder ODC–ODP dan drop ODP–pelanggan. Pilihan disimpan di perangkat ini.</p>
            <div class="gis-fiber-progress" aria-live="polite">
                <p v-if="!layers.fiberLines">Layer jalur fiber sedang disembunyikan. Aktifkan melalui menu Layer.</p>
                <template v-else-if="fiberMode === 'road'">
                    <p v-if="fiberStats.loading"><ArrowPathIcon class="spinning" />Menghitung {{ fiberStats.completed }}/{{ fiberStats.total }} jalur…</p>
                    <p v-else>{{ fiberStats.road }} dari {{ fiberStats.total }} jalur mengikuti jalan.</p>
                    <div v-if="fiberStats.loading" class="gis-fiber-progress-track"><span :style="{ width: `${fiberStats.total ? fiberStats.completed / fiberStats.total * 100 : 0}%` }"></span></div>
                    <p v-if="fiberStats.fallback" class="gis-fiber-fallback">{{ fiberStats.fallback }} jalur memakai garis lurus karena rute jalan belum tersedia.</p>
                    <button v-if="!fiberStats.loading && fiberStats.fallback" @click="$emit('retry-fiber')"><ArrowPathIcon />Coba hitung kembali</button>
                </template>
                <p v-else>{{ fiberStats.total }} jalur lurus antarperangkat.</p>
            </div>
            <p class="gis-fiber-disclaimer">Jalur mengikuti jalan merupakan estimasi, bukan hasil survei jalur kabel sebenarnya.</p>
        </section>

        <div class="gis-map-tools gis-surface" aria-label="Kontrol navigasi peta">
            <button @click="$emit('fit')" title="Tampilkan seluruh jaringan" aria-label="Tampilkan seluruh jaringan"><ArrowsPointingOutIcon /></button>
            <button @click="$emit('locate')" :disabled="locating" title="Lokasi saya" aria-label="Pusatkan ke lokasi saya"><MapPinIcon :class="{ spinning: locating }" /></button>
            <button @click="$emit('ruler')" :class="{ selected: activeTool === 'ruler' }" :aria-pressed="activeTool === 'ruler'" title="Ukur jarak fiber" aria-label="Ukur jarak fiber"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.5 3.5 6 6a1.4 1.4 0 0 1 0 2l-9 9a1.4 1.4 0 0 1-2 0l-6-6a1.4 1.4 0 0 1 0-2l9-9a1.4 1.4 0 0 1 2 0ZM10 6l3 3m-6 0 2 2m-5 1 3 3m3 3 2-2m1-5 3 3m0-6 2 2" /></svg></button>
            <span></span>
            <button @click="$emit('fullscreen')" title="Layar penuh" aria-label="Layar penuh"><component :is="fullscreen ? ArrowsPointingInIcon : ArrowTopRightOnSquareIcon" /></button>
        </div>

        <section class="gis-basemap gis-surface" :class="{ 'is-expanded': panel === 'basemap' }" aria-label="Pilihan tampilan peta">
            <button class="gis-basemap-heading" @click="togglePanel('basemap')" :aria-expanded="panel === 'basemap'"><span class="gis-basemap-thumb" :class="`preview-${basemap}`"></span><span><small>TAMPILAN PETA</small><strong>{{ currentBasemap?.label }}</strong></span><ChevronUpIcon :class="{ rotated: panel === 'basemap' }" /></button>
            <div v-if="basemapLoading || basemapError" class="gis-basemap-status" :class="{ 'has-error': basemapError }" role="status">
                <template v-if="basemapLoading"><ArrowPathIcon class="spinning" /><span>Memuat {{ currentBasemap?.label?.toLowerCase() }}…</span></template>
                <template v-else><span>{{ basemapError }}</span><button @click="$emit('retry-basemap')" aria-label="Coba muat tampilan peta lagi"><ArrowPathIcon /></button></template>
            </div>
            <div v-if="panel === 'basemap'" class="gis-basemap-options"><button v-for="item in basemaps" :key="item.id" :class="{ selected: item.id === basemap }" :aria-pressed="item.id === basemap" @click="$emit('update:basemap', item.id)"><span class="gis-basemap-preview" :class="`preview-${item.id}`"><CheckIcon v-if="item.id === basemap" /></span><strong>{{ item.label }}</strong></button></div>
            <label v-if="basemap === 'google_sat'" class="gis-label-toggle"><span><MapIcon /><span>Nama jalan & lokasi</span></span><input type="checkbox" :checked="labels" @change="$emit('update:labels', $event.target.checked)" /></label>
        </section>

        <section v-if="!['layers', 'fiber'].includes(panel)" class="gis-legend gis-surface" :class="{ collapsed: !legend }" aria-label="Legenda peta">
            <button class="gis-legend-heading" @click="toggleLegend" :aria-expanded="legend"><span><span class="gis-legend-key"><i></i><i></i><i></i></span><strong>{{ basemap === 'google_sat' ? 'Legenda satelit' : 'Legenda jaringan' }}</strong></span><ChevronDownIcon :class="{ rotated: !legend }" /></button>
            <div v-if="legend" class="gis-legend-body">
                <p v-if="basemap === 'google_sat'" class="gis-satellite-note">Citra satelit dengan lapisan jaringan Anda.</p>
                <div class="gis-legend-grid"><span><i class="gis-symbol gis-symbol-odc"></i>ODC / kabinet</span><span><i class="gis-symbol gis-symbol-odp"></i>ODP tersedia</span><span><i class="gis-symbol gis-symbol-limited"></i>ODP sisa 1 port</span><span><i class="gis-symbol gis-symbol-full"></i>ODP penuh</span><span><i class="gis-symbol gis-symbol-area"></i>Area layanan</span><span><i class="gis-symbol gis-symbol-cluster">3</i>Grup titik</span></div>
                <div class="gis-line-legend" :class="{ 'gis-cable-road': fiberMode === 'road' }"><span><i class="gis-line-feeder"></i>Feeder ODC–ODP</span><span><i class="gis-line-drop"></i>Drop ke pelanggan</span><span><i class="gis-line-route"></i>Rute jalan</span></div>
                <div class="gis-cable-caption" aria-live="polite"><span>Jalur kabel: <strong>{{ fiberMode === 'road' ? 'Ikuti jalan' : 'Lurus' }}</strong></span><span v-if="layers.fiberLines && fiberStats.loading"><ArrowPathIcon class="spinning" />{{ fiberStats.completed }}/{{ fiberStats.total }}</span></div>
                <p v-if="fiberMode === 'road' && layers.fiberLines && fiberStats.fallback" class="gis-fiber-fallback">Garis putus-putus: {{ fiberStats.fallback }} rute belum tersedia.</p>
                <p v-if="fiberMode === 'road'" class="gis-routing-credit">Estimasi rute: <a href="https://project-osrm.org/" target="_blank" rel="noopener noreferrer">OSRM</a> · <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">© OpenStreetMap</a> · <a href="https://www.openstreetmap.org/fixthemap" target="_blank" rel="noopener noreferrer">Koreksi peta</a></p>
                <div class="gis-legend-status"><span v-for="item in statuses" :key="item.key"><i :class="`gis-dot-${item.key}`"></i>{{ item.label }}</span></div>
                <button class="gis-guide-link" @click="$emit('guide')">Panduan simbol & status <ArrowUpRightIcon /></button>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { MapIcon, Squares2X2Icon, Square3Stack3DIcon, ArrowTrendingUpIcon, ViewfinderCircleIcon, MagnifyingGlassIcon, MapPinIcon, XMarkIcon, ChevronRightIcon, ChevronDownIcon, ChevronUpIcon, CheckIcon, UsersIcon, ServerStackIcon, CircleStackIcon, GlobeAltIcon, ArrowPathIcon, ShieldCheckIcon, ArrowsPointingOutIcon, ArrowsPointingInIcon, ArrowTopRightOnSquareIcon, ArrowUpRightIcon, ShareIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    query: { type: String, default: '' }, searchFocused: Boolean, results: { type: Array, default: () => [] },
    area: { type: [String, Number], default: 'all' }, areas: { type: Array, default: () => [] },
    counts: { type: Object, default: () => ({}) }, connections: { type: Object, default: () => ({}) },
    updateLabel: String, error: String, refreshing: Boolean, basemap: String, basemaps: Array, labels: Boolean,
    basemapLoading: Boolean, basemapError: String,
    layers: { type: Object, default: () => ({}) }, activeTool: String, routeAllowed: Boolean, finderAllowed: Boolean, locating: Boolean, fullscreen: Boolean,
    fiberMode: { type: String, default: 'straight' }, fiberStats: { type: Object, default: () => ({ total: 0, completed: 0, road: 0, fallback: 0, loading: false }) },
});
defineEmits(['update:query', 'update:searchFocused', 'update:area', 'update:basemap', 'update:labels', 'retry-basemap', 'select', 'layer', 'fiber-mode', 'retry-fiber', 'refresh', 'route', 'finder', 'ruler', 'locate', 'fit', 'fullscreen', 'guide']);
const overview = ref(true);
const legend = ref(true);
let mobileQuery;
const onMobileLayout = event => { if (event.matches) legend.value = false; };
onMounted(() => { mobileQuery = window.matchMedia('(max-width: 700px)'); onMobileLayout(mobileQuery); mobileQuery.addEventListener('change', onMobileLayout); });
onUnmounted(() => mobileQuery?.removeEventListener('change', onMobileLayout));
const toggleOverview = () => { overview.value = !overview.value; if (overview.value && window.matchMedia('(max-width: 700px)').matches) legend.value = false; };
const toggleLegend = () => { legend.value = !legend.value; if (legend.value && window.matchMedia('(max-width: 700px)').matches) overview.value = false; };
const panel = ref(null);
const togglePanel = name => { panel.value = panel.value === name ? null : name; };
const dismiss = () => { panel.value = null; };
defineExpose({ dismiss });
const currentBasemap = computed(() => props.basemaps?.find(item => item.id === props.basemap));
const enabledLayers = computed(() => Object.values(props.layers).filter(Boolean).length);
const metrics = [{ key: 'customers', label: 'Pelanggan', icon: UsersIcon }, { key: 'odps', label: 'Titik ODP', icon: CircleStackIcon }, { key: 'odcs', label: 'Kabinet ODC', icon: ServerStackIcon }, { key: 'areas', label: 'Wilayah', icon: GlobeAltIcon }];
const statuses = [{ key: 'online', label: 'Online' }, { key: 'offline', label: 'Offline' }, { key: 'unknown', label: 'Belum diketahui' }];
const resultIcons = { customer: UsersIcon, odp: CircleStackIcon, odc: ServerStackIcon, area: GlobeAltIcon };
const layerOptions = [
    { key: 'customers', label: 'Pelanggan', description: 'Titik & status koneksi', icon: UsersIcon },
    { key: 'odps', label: 'Optical Distribution Point', description: 'ODP & kapasitas port', icon: CircleStackIcon },
    { key: 'odcs', label: 'Kabinet distribusi', description: 'Pusat distribusi ODC', icon: ServerStackIcon },
    { key: 'areas', label: 'Area layanan', description: 'Batas & nama wilayah', icon: GlobeAltIcon },
    { key: 'fiberLines', label: 'Jalur fiber', description: 'Hubungan ODC, ODP & pelanggan', icon: ShareIcon },
    { key: 'cluster', label: 'Kelompokkan titik', description: 'Gabungkan marker berdekatan', icon: Squares2X2Icon },
];
</script>

<style scoped>
.gis-controls { --gis-bg: #fff; --gis-subtle: #f5f7fa; --gis-ink: #172b46; --gis-muted: #75859a; --gis-border: #e4eaf0; position: absolute; inset: 0; z-index: 990; pointer-events: none; color: var(--gis-ink); font-size: 12px; }
.gis-controls svg { width: 18px; height: 18px; flex: none; stroke-width: 1.7; }
.gis-controls button, .gis-controls input, .gis-controls select { font: inherit; }
.gis-controls button { cursor: pointer; }
.gis-controls button:disabled { cursor: wait; opacity: .5; }
.gis-controls button:focus-visible, .gis-controls input:focus-visible, .gis-controls select:focus-visible { outline: 2px solid #3b82f6; outline-offset: 3px; }
.gis-surface { pointer-events: auto; background: var(--gis-bg); border: 1px solid var(--gis-border); border-radius: 16px; box-shadow: 0 4px 24px #162a4510, 0 1px 3px #162a450a; }
.gis-dismiss { position: absolute; inset: 0; border: 0; background: transparent; pointer-events: auto; }
.gis-topbar { position: absolute; left: 18px; right: 18px; top: 16px; display: flex; align-items: center; justify-content: space-between; gap: 16px; min-height: 66px; padding: 10px 14px; z-index: 4; }
.gis-heading { display: flex; align-items: center; gap: 11px; }
.gis-app-icon { display: grid; place-items: center; width: 39px; height: 39px; border-radius: 12px; background: #edf4ff; color: #3471c9; }
.gis-app-icon svg { width: 22px; height: 22px; }
.gis-eyebrow { display: block; color: var(--gis-muted); font-size: 9px; font-weight: 700; letter-spacing: 1.5px; line-height: 1.5; }
.gis-heading h2 { display: flex; align-items: center; gap: 12px; margin: 2px 0 0; font-size: 16px; color: var(--gis-ink); font-weight: 700; }
.gis-live { display: flex; align-items: center; gap: 5px; color: #168766; font-size: 10px; font-weight: 600; }
.gis-live i { width: 5px; height: 5px; border-radius: 50%; background: #19b487; box-shadow: 0 0 0 3px #19b48718; }
.gis-primary-tools { display: flex; align-items: center; gap: 5px; }
.gis-primary-tools button { display: flex; align-items: center; gap: 7px; min-height: 37px; padding: 8px 11px; border-radius: 9px; border: 1px solid transparent; background: transparent; color: #60718a; font-size: 11px; font-weight: 600; white-space: nowrap; }
.gis-primary-tools button:hover, .gis-map-tools button:hover { background: var(--gis-subtle); color: #3274d0; }
.gis-primary-tools button.selected, .gis-map-tools button.selected { background: #edf4ff; color: #286bcb; border-color: #dbe8fa; }
.gis-primary-tools b { background: #dfeafb; font-size: 9px; padding: 1px 5px; border-radius: 5px; color: #3471c9; }
.gis-tool-divider { width: 1px; height: 22px; background: var(--gis-border); margin: 0 5px; }
.gis-overview { position: absolute; left: 18px; top: 96px; width: 290px; padding: 18px; max-height: calc(100% - 225px); overflow: visible; z-index: 2; }
.gis-panel-heading { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 17px; }
.gis-panel-heading h3 { color: var(--gis-ink); font-size: 15px; font-weight: 700; margin: 4px 0 0; }
.gis-icon-close { display: grid; place-items: center; padding: 3px; border: 0; border-radius: 6px; color: var(--gis-muted); background: transparent; }
.gis-search { position: relative; display: flex; align-items: center; gap: 8px; border: 1px solid #dce5f0; border-radius: 9px; padding: 0 10px; background: var(--gis-bg); }
.gis-search > svg { width: 16px; color: #6384b0; }
.gis-search input { min-width: 0; width: 100%; padding: 11px 0; font-size: 11px; border: 0; color: var(--gis-ink); background: transparent; outline: 0; box-shadow: none; }
.gis-search input::placeholder { color: #94a2b5; }
.gis-controls .gis-search input { background: transparent !important; color: var(--gis-ink) !important; }
.gis-controls .gis-area-select select { background-color: transparent !important; color: var(--gis-ink) !important; }
:global(#workspace-content .gis-controls .gis-search input), :global(#workspace-content .gis-controls .gis-area-select select) { min-height: 0; background-color: transparent !important; color: var(--gis-ink) !important; border: 0 !important; box-shadow: none !important; border-radius: 0; font-size: 11px; }
.gis-search > button { padding: 0; border: 0; background: transparent; color: var(--gis-muted); }
.gis-search > button svg { width: 14px; }
.gis-results { position: absolute; left: -1px; top: calc(100% + 7px); width: max(100%, 305px); max-height: 300px; overflow: auto; padding: 5px; background: var(--gis-bg); border: 1px solid var(--gis-border); border-radius: 12px; box-shadow: 0 12px 35px #102b4926; z-index: 5; }
.gis-results button { display: flex; align-items: center; gap: 9px; text-align: left; width: 100%; padding: 10px 6px; border: 0; border-radius: 7px; background: transparent; color: var(--gis-ink); }
.gis-results button:hover { background: var(--gis-subtle); }
.gis-results button > span:nth-child(2) { min-width: 0; flex: 1; }
.gis-results strong, .gis-results small { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.gis-results strong { font-size: 11px; font-weight: 600; }
.gis-results small { font-size: 10px; color: var(--gis-muted); margin-top: 3px; }
.gis-results p { padding: 12px; color: var(--gis-muted); font-size: 11px; }
.gis-result-icon { display: grid; place-items: center; width: 30px; height: 30px; color: #3977ca; background: #edf4ff; border-radius: 8px; }
.gis-area-select { display: flex; align-items: center; gap: 8px; margin-top: 10px; padding-left: 10px; background: var(--gis-subtle); border-radius: 9px; }
.gis-area-select > svg { width: 15px; color: var(--gis-muted); }
.gis-area-select select { width: 100%; padding: 10px 26px 10px 0; border: 0; background-color: transparent; box-shadow: none; color: #62748e; font-size: 11px; }
.gis-metrics { display: grid; grid-template-columns: 1fr 1fr; margin: 18px 0; gap: 17px 12px; }
.gis-metrics > div { display: flex; align-items: center; gap: 9px; }
.gis-metric-icon { display: grid; place-items: center; width: 33px; height: 35px; border-radius: 9px; background: #ecf5ff; color: #3979d2; }
.gis-metric-icon[data-kind="odps"] { background: #eaf8f3; color: #199d7d; }
.gis-metric-icon[data-kind="odcs"] { background: #edf0ff; color: #6573dc; }
.gis-metric-icon[data-kind="areas"] { background: #f5f0ff; color: #9662cf; }
.gis-metrics strong, .gis-metrics div div > span { display: block; }
.gis-metrics strong { font-size: 20px; line-height: 1.2; font-weight: 700; font-variant-numeric: tabular-nums; }
.gis-metrics div div > span { color: var(--gis-muted); font-size: 10px; margin-top: 3px; }
.gis-connection-summary { border-top: 1px solid var(--gis-border); padding-top: 14px; }
.gis-section-heading { display: flex; align-items: center; justify-content: space-between; font-size: 10px; font-weight: 600; }
.gis-section-heading button { border: 0; padding: 3px; background: transparent; color: var(--gis-muted); }
.gis-section-heading svg { width: 14px; height: 14px; }
.gis-status-grid { display: grid; grid-template-columns: .85fr .85fr 1.3fr; gap: 7px; margin-top: 10px; }
.gis-status-grid > div { display: grid; grid-template-columns: 7px 1fr; gap: 4px 5px; align-items: center; }
.gis-status-grid strong { font-size: 16px; line-height: 1.2; font-weight: 650; }
.gis-status-grid span { grid-column: 1 / -1; color: var(--gis-muted); font-size: 9px; }
[class^="gis-dot-"] { display: inline-block; width: 6px; height: 6px; border-radius: 50%; flex: none; background: #94a3b8; }
.gis-dot-online { background: #12b886; }.gis-dot-offline { background: #ef5870; }
.gis-update { color: var(--gis-muted); font-size: 9px; line-height: 1.6; margin-top: 12px; }
.gis-update.has-error { color: #dc644a; }
.gis-overview-footer { display: flex; align-items: center; gap: 6px; padding-top: 12px; margin-top: 12px; border-top: 1px solid var(--gis-border); color: var(--gis-muted); font-size: 9px; }
.gis-overview-footer svg { width: 13px; height: 13px; color: #70988e; }
.gis-layer-panel { position: absolute; right: 18px; top: 96px; width: 306px; max-height: calc(100% - 120px); overflow-y: auto; padding: 18px; z-index: 5; }
.gis-layer-row { display: flex; align-items: center; gap: 9px; padding: 12px 0; border-bottom: 1px solid var(--gis-border); cursor: pointer; }
.gis-layer-row:last-child { border: 0; }
.gis-layer-symbol { width: 31px; height: 31px; display: grid; place-items: center; color: #3979d2; border: 1px solid var(--gis-border); border-radius: 8px; }
.gis-layer-row > span:nth-child(2) { flex: 1; }
.gis-layer-row strong { display: block; font-size: 11px; font-weight: 600; }
.gis-layer-row small { display: block; color: var(--gis-muted); font-size: 9px; margin-top: 4px; }
.gis-layer-row input { position: absolute; opacity: 0; width: 30px; right: 18px; }
.gis-switch { position: relative; width: 29px; height: 17px; border-radius: 20px; background: #cbd5e1; flex: none; }
.gis-switch::after { content: ''; position: absolute; width: 11px; height: 11px; left: 3px; top: 3px; background: #fff; border-radius: 50%; transition: transform .15s; }
.gis-layer-row input:checked + .gis-switch { background: #3c7cd4; }.gis-layer-row input:checked + .gis-switch::after { transform: translateX(12px); }.gis-layer-row input:focus-visible + .gis-switch { outline: 2px solid #3b82f6; outline-offset: 3px; }
.gis-map-tools { position: absolute; right: 18px; top: 96px; padding: 5px; display: flex; flex-direction: column; gap: 4px; border-radius: 12px; }
.gis-fiber-modes { display: grid; grid-template-columns: 1fr 1fr; gap: 9px; }
.gis-fiber-modes button { display: flex; flex-direction: column; align-items: flex-start; gap: 8px; padding: 13px 10px; border: 1px solid var(--gis-border); border-radius: 11px; color: var(--gis-muted); background: var(--gis-subtle); text-align: left; }
.gis-fiber-modes button.selected { color: #3b7dd8; border-color: #729ee4; background: #3b82f60b; box-shadow: inset 0 0 0 1px #3b82f630; }
.gis-fiber-modes strong { font-size: 11px; font-weight: 650; }.gis-fiber-modes small { font-size: 9px; font-weight: 400; line-height: 1.5; }
.gis-straight-icon { display: block; width: 18px; height: 18px; background: linear-gradient(145deg, transparent 44%, currentColor 45%, currentColor 52%, transparent 53%); }
.gis-fiber-description, .gis-fiber-disclaimer { color: var(--gis-muted); font-size: 10px; line-height: 1.7; margin-top: 13px; }
.gis-fiber-disclaimer { padding-top: 13px; border-top: 1px solid var(--gis-border); font-size: 9px; }
.gis-fiber-progress { background: var(--gis-subtle); border-radius: 10px; padding: 11px; margin-top: 14px; color: var(--gis-ink); font-size: 10px; line-height: 1.6; }
.gis-fiber-progress p { display: flex; align-items: center; gap: 6px; }.gis-fiber-progress svg { width: 13px; height: 13px; }
.gis-fiber-progress-track { height: 4px; background: var(--gis-border); border-radius: 5px; overflow: hidden; margin-top: 10px; }.gis-fiber-progress-track span { display: block; height: 100%; background: #4e8bdb; transition: width .2s; }
.gis-fiber-fallback { color: #b68535; font-size: 9px; line-height: 1.6; margin-top: 8px; }
.gis-fiber-progress button { display: flex; align-items: center; gap: 5px; border: 0; background: transparent; color: #4384db; font-size: 10px; padding: 0; margin-top: 10px; }
.gis-cable-road .gis-line-feeder, .gis-cable-road .gis-line-drop { border-style: solid; }
.gis-cable-caption { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 12px; font-size: 9px; color: var(--gis-muted); }.gis-cable-caption > span { display: flex; align-items: center; gap: 5px; }.gis-cable-caption strong { font-weight: 600; }.gis-cable-caption svg { width: 11px; height: 11px; }
.gis-routing-credit { font-size: 8px; color: var(--gis-muted); margin-top: 8px; }.gis-routing-credit a { color: #5087c6; }
.gis-map-tools button { display: grid; place-items: center; width: 33px; height: 33px; background: transparent; border: 0; border-radius: 7px; color: #60718a; }
.gis-map-tools > span { height: 1px; background: var(--gis-border); margin: 1px 3px; }
.gis-basemap { position: absolute; bottom: 27px; left: 18px; width: 244px; padding: 8px; z-index: 3; }
.gis-basemap-heading { display: flex; align-items: center; gap: 10px; width: 100%; padding: 2px; border: 0; background: transparent; text-align: left; color: var(--gis-ink); }
.gis-basemap-heading > span:nth-child(2) { flex: 1; }.gis-basemap-heading small { display: block; font-size: 8px; letter-spacing: 1.2px; color: var(--gis-muted); font-weight: 600; }.gis-basemap-heading strong { display: block; font-size: 11px; margin-top: 3px; font-weight: 650; }
.gis-basemap-heading > svg { width: 15px; color: var(--gis-muted); margin-right: 5px; }.rotated { transform: rotate(180deg); }
.gis-basemap-thumb { display: block; width: 44px; height: 34px; border: 1px solid #9aaec040; border-radius: 7px; }
.gis-basemap-options { display: grid; grid-template-columns: repeat(4, 1fr); gap: 7px; padding-top: 12px; }
.gis-basemap-status { display: flex; align-items: center; gap: 6px; padding: 9px 3px 2px; color: var(--gis-muted); font-size: 9px; line-height: 1.5; }.gis-basemap-status.has-error { color: #b68535; }.gis-basemap-status > span { flex: 1; }.gis-basemap-status svg { width: 12px; height: 12px; }.gis-basemap-status button { padding: 3px; color: #508bd3; background: transparent; border: 0; }
.gis-basemap-options button { min-width: 0; padding: 0; border: 0; background: transparent; color: var(--gis-muted); font-size: 9px; text-align: center; }
.gis-basemap-options button strong { font-weight: 500; }.gis-basemap-options button.selected { color: #3374cc; }.gis-basemap-preview { display: grid; place-items: center; height: 45px; margin-bottom: 6px; border: 2px solid transparent; border-radius: 8px; }.selected .gis-basemap-preview { border-color: #3b82f6; }.gis-basemap-preview svg { padding: 2px; width: 19px; height: 19px; border-radius: 50%; background: #347bdb; color: white; }
.preview-osm { background: linear-gradient(32deg, transparent 40%, #fff 41%, #fff 46%, transparent 47%), linear-gradient(118deg, transparent 40%, #83b5d3 41%, #83b5d3 45%, transparent 46%), repeating-linear-gradient(0deg, #e7ecd9 0 11px, #f5f1e8 12px 22px); }
.preview-google_sat { background: linear-gradient(30deg, transparent 37%, #c4c2a7 38%, #c4c2a7 42%, transparent 43%), repeating-conic-gradient(#394e36 0% 25%, #617251 0% 50%) 50% / 20px 25px; }
.preview-carto_dark { background: linear-gradient(32deg, transparent 40%, #617087 41%, #617087 44%, transparent 45%), repeating-linear-gradient(90deg, #192939 0 13px, #2c3e50 14px 15px); }
.preview-topo { background: repeating-radial-gradient(ellipse at 80% 120%, #d6debd 0 5px, #aebf99 6px 7px, #e8ecd9 8px 13px); }
.gis-label-toggle { display: flex; align-items: center; justify-content: space-between; gap: 10px; font-size: 10px; border-top: 1px solid var(--gis-border); margin-top: 10px; padding: 9px 3px 2px; color: #60718a; cursor: pointer; }.gis-label-toggle > span { display: flex; align-items: center; gap: 7px; }.gis-label-toggle svg { width: 14px; height: 14px; }.gis-label-toggle input { width: 13px; height: 13px; border: 1px solid #c4cfde; border-radius: 3px; color: #3b82f6; }
.gis-legend { position: absolute; right: 75px; bottom: 27px; width: 290px; padding: 14px; }
.gis-legend-heading { display: flex; width: 100%; align-items: center; justify-content: space-between; border: 0; padding: 0; background: transparent; color: var(--gis-ink); }.gis-legend-heading > span { display: flex; align-items: center; gap: 8px; }.gis-legend-heading strong { font-size: 11px; font-weight: 650; }.gis-legend-heading > svg { width: 14px; height: 14px; color: var(--gis-muted); }.gis-legend-key { display: flex; gap: 2px; align-items: end; }.gis-legend-key i { display: block; width: 3px; height: 10px; border-radius: 2px; background: #4186de; }.gis-legend-key i:nth-child(2) { height: 7px; background: #26b18b; }.gis-legend-key i:nth-child(3) { height: 12px; background: #9d7cda; }
.gis-legend-body { padding-top: 14px; }.gis-satellite-note { margin: -3px 0 12px; color: var(--gis-muted); font-size: 9px; line-height: 1.6; }
.gis-legend-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 8px; }.gis-legend-grid > span { display: flex; align-items: center; gap: 7px; font-size: 9px; color: #60718a; }
.gis-symbol { display: inline-block; width: 11px; height: 11px; flex: none; border: 1px solid #fff; box-shadow: 0 0 0 1px #ced7e3; border-radius: 50%; }.gis-symbol-odc { border-radius: 3px; background: #5368e4; }.gis-symbol-odp { background: #19ae88; }.gis-symbol-limited { background: #f3b528; }.gis-symbol-full { background: #ed5471; }.gis-symbol-area { border: 1px dashed #9f75dd; box-shadow: none; background: #aa86de19; }.gis-symbol-cluster { display: grid; place-items: center; font-size: 7px; color: #9c7522; background: #fff0c6; border: 0; box-shadow: 0 0 0 2px #fae9b450; }
.gis-line-legend { display: flex; flex-wrap: wrap; gap: 10px 12px; border-top: 1px solid var(--gis-border); margin-top: 14px; padding-top: 12px; }.gis-line-legend > span { display: flex; align-items: center; gap: 6px; color: #60718a; font-size: 9px; }.gis-line-legend i { display: block; width: 17px; border-top: 2px dashed #58a0eb; }.gis-line-legend .gis-line-drop { border-color: #25b997; }.gis-line-legend .gis-line-route { border-color: #6366f1; border-style: solid; }
.gis-legend-status { display: flex; flex-wrap: wrap; gap: 10px; padding-top: 12px; color: #60718a; font-size: 9px; }.gis-legend-status span { display: flex; align-items: center; gap: 5px; }.gis-guide-link { display: flex; align-items: center; gap: 6px; margin-top: 13px; padding: 0; border: 0; background: transparent; color: #3c7dce; font-size: 9px; font-weight: 600; }.gis-guide-link svg { width: 12px; height: 12px; }
.spinning { animation: gis-control-spin 1s linear infinite; }@keyframes gis-control-spin { to { transform: rotate(360deg); } }
:global(html.dark .gis-controls) { --gis-bg: #132033; --gis-subtle: #1b2b40; --gis-ink: #e5edf7; --gis-muted: #91a3bb; --gis-border: #29394f; }
:global(html.dark .gis-primary-tools button), :global(html.dark .gis-area-select select), :global(html.dark .gis-legend-grid > span), :global(html.dark .gis-line-legend > span), :global(html.dark .gis-legend-status), :global(html.dark .gis-label-toggle) { color: #a5b6cc; }
:global(html.dark .gis-primary-tools button.selected), :global(html.dark .gis-map-tools button.selected) { background: #223d60; border-color: #36557b; color: #96c1ff; }
:global(html.dark .gis-search) { border-color: #334860; }
:global(html.dark .gis-fiber-modes button.selected) { color: #9bc6ff; border-color: #6b9bda; }
@media (max-height: 700px) and (min-width: 701px) { .gis-overview { padding: 14px; }.gis-overview-footer { display: none; }.gis-metrics { margin: 12px 0; gap: 12px; }.gis-panel-heading { margin-bottom: 12px; }.gis-update { margin-top: 7px; } }
@media (max-width: 1000px) { .gis-topbar { gap: 8px; }.gis-primary-tools button { padding: 8px; }.gis-heading h2 { font-size: 14px; }.gis-primary-tools button:first-child > span { display: none; }.gis-legend { width: 260px; }.gis-overview { width: 260px; padding: 15px; }.gis-results { width: max(100%, 275px); } }
@media (max-width: 700px) { .gis-topbar { top: 10px; left: 10px; right: 10px; padding: 10px; min-height: 59px; border-radius: 13px; }.gis-app-icon { width: 31px; height: 33px; border-radius: 9px; }.gis-app-icon svg { width: 18px; }.gis-heading { gap: 8px; }.gis-heading .gis-eyebrow { font-size: 7px; letter-spacing: 1px; }.gis-heading h2 { font-size: 12px; gap: 7px; }.gis-live { font-size: 8px; }.gis-primary-tools { gap: 2px; }.gis-primary-tools button { padding: 7px; min-height: 32px; }.gis-primary-tools button > span, .gis-primary-tools b, .gis-tool-divider { display: none; }.gis-primary-tools svg { width: 16px; height: 16px; }.gis-overview { left: 10px; top: 80px; width: min(290px, calc(100% - 78px)); padding: 13px; max-height: calc(100% - 175px); overflow-y: auto; }.gis-panel-heading { margin-bottom: 12px; }.gis-panel-heading h3 { font-size: 13px; }.gis-overview .gis-eyebrow { font-size: 8px; }.gis-results { width: calc(100% + 2px); }.gis-metrics { gap: 12px; margin: 14px 0; }.gis-metrics strong { font-size: 17px; }.gis-overview-footer { display: none; }.gis-map-tools { top: 80px; right: 10px; }.gis-layer-panel { top: 80px; right: 10px; width: min(306px, calc(100% - 20px)); }.gis-basemap { left: 10px; bottom: 26px; width: 166px; border-radius: 12px; }.gis-basemap.is-expanded { width: min(270px, calc(100% - 20px)); z-index: 5; }.gis-basemap-thumb { width: 33px; height: 30px; }.gis-basemap-heading { gap: 7px; }.gis-basemap-heading small { font-size: 7px; }.gis-basemap-heading strong { font-size: 10px; }.gis-legend { bottom: 26px; right: 10px; width: min(290px, calc(100% - 20px)); padding: 12px; z-index: 3; }.gis-legend.collapsed { width: 153px; }.gis-legend-heading strong { font-size: 10px; }.gis-legend:not(.collapsed) { bottom: 98px; }.gis-label-toggle > span { gap: 5px; font-size: 9px; } }
@media (prefers-reduced-motion: reduce) { .spinning { animation: none; } }
@media (max-width: 700px) { .gis-overview.gis-overlay-hidden { display: none; } }
</style>
