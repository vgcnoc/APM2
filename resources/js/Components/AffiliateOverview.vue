<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowRightIcon, ArrowUpRightIcon, BanknotesIcon, ClipboardDocumentIcon, PlusIcon, ShareIcon, UserGroupIcon, UsersIcon, WalletIcon } from '@heroicons/vue/24/outline';

const props = defineProps({ profile: Object, kpi: Object, settings: Object, customers: Array, isEmployee: Boolean, pendingPayroll: Number, customerShareUrl: String, affiliateShareUrl: String });
defineEmits(['copy', 'withdraw']);
const currency = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value || 0);
const stats = computed(() => [
    { label: 'Total booking saya', value: props.kpi.total_customers, detail: `${props.kpi.pending_bookings} menunggu proses`, icon: UsersIcon, tone: 'blue' },
    { label: 'Pelanggan aktif', value: props.kpi.active_customers, detail: 'Pelanggan langsung milik Anda', icon: UsersIcon, tone: 'green' },
    { label: 'Total pendapatan', value: currency(props.profile.total_earned), detail: 'Akumulasi komisi Anda', icon: BanknotesIcon, tone: 'blue' },
    { 
        label: 'Jaringan afiliasi', 
        value: `${props.kpi.total_downlines} Mitra`, 
        detail: props.kpi.total_downlines_active_customers 
            ? `${props.kpi.total_downlines_active_customers} Pelanggan Aktif (${currency(props.kpi.potential_monthly_network_commission)}/bln)` 
            : `Level 1: ${props.kpi.downlines_l1_count} · Level 2: ${props.kpi.downlines_l2_count}`, 
        icon: UserGroupIcon, 
        tone: 'violet' 
    },
]);
const latestCustomers = computed(() => (props.customers || []).slice(0, 5));
</script>

<template>
    <div class="sales-overview">
        <div class="sales-intro"><div><p class="sales-eyebrow">RINGKASAN AKTIVITAS</p><h1>Halo, {{ profile.name }}<span>.</span></h1><p>Pantau hasil Anda. Bangun peluang berikutnya.</p></div><span class="sales-account-badge"><span></span>{{ isEmployee ? 'Karyawan internal' : 'Mitra afiliasi' }}</span></div>

        <div class="sales-welcome-grid">
            <section class="sales-referral-hero">
                <div class="hero-orbit" aria-hidden="true"></div>
                <p class="sales-eyebrow">IDENTITAS AFILIASI ANDA</p>
                <h2>Langkah kecil.<br /><span>Peluang lebih besar.</span></h2>
                <p class="hero-description">Gunakan nomor afiliasi Anda untuk booking pelanggan dan mengembangkan jaringan.</p>
                <div class="sales-referral-code"><div><span>NOMOR AFILIASI</span><code>{{ profile.referral_code }}</code></div><button type="button" aria-label="Salin nomor afiliasi" @click="$emit('copy', profile.referral_code, 'Nomor afiliasi berhasil disalin.')"><ClipboardDocumentIcon /><span>Salin</span></button></div>
                <p v-if="profile.upline_name" class="sales-sponsor">Sponsor upline: <strong>{{ profile.upline_name }}</strong></p>
            </section>

            <section class="sales-balance-card">
                <div class="balance-heading"><span>{{ isEmployee ? 'INSENTIF MENUNGGU PAYROLL' : 'SALDO KOMISI ANDA' }}</span><WalletIcon /></div>
                <strong class="balance-amount">{{ currency(isEmployee ? pendingPayroll : profile.balance) }}</strong>
                <p>{{ isEmployee ? 'Estimasi insentif yang akan disalurkan melalui slip gaji.' : 'Saldo komisi tersedia. Pencairan mengikuti jadwal dan ketentuan.' }}</p>
                <div class="balance-divider"></div>
                <div class="balance-secondary"><span>{{ isEmployee ? 'Penyaluran insentif' : 'Total sudah dicairkan' }}</span><strong>{{ isEmployee ? 'Slip gaji / payroll' : currency(profile.total_withdrawn) }}</strong></div>
                <Link v-if="isEmployee" href="/affiliate/dashboard?tab=commissions" class="sales-primary-action">Lihat histori insentif <ArrowRightIcon /></Link>
                <button v-else type="button" class="sales-primary-action" @click="$emit('withdraw')">Ajukan pencairan <ArrowUpRightIcon /></button>
            </section>
        </div>

        <div class="sales-stats-grid"><section v-for="stat in stats" :key="stat.label" class="sales-metric-card" :class="`metric-${stat.tone}`"><div class="metric-heading"><span>{{ stat.label }}</span><component :is="stat.icon" /></div><strong>{{ stat.value }}</strong><p>{{ stat.detail }}</p></section></div>

        <div class="sales-overview-lower">
            <section class="sales-section-card sales-activity-card">
                <div class="sales-section-heading"><div><h2>Booking terbaru</h2><p>Pelanggan yang Anda daftarkan langsung.</p></div><Link href="/affiliate/dashboard?tab=customers">Lihat semua <ArrowUpRightIcon /></Link></div>
                <div v-if="latestCustomers.length" class="sales-recent-list"><div v-for="customer in latestCustomers" :key="customer.id" class="sales-recent-customer"><span class="customer-avatar">{{ customer.name?.charAt(0) || 'P' }}</span><div><strong>{{ customer.name }}</strong><span>{{ customer.customer_code }} · {{ customer.package_name }}</span></div><span class="customer-state" :class="customer.status === 'active' ? 'state-active' : 'state-pending'">{{ customer.status === 'active' ? 'Aktif' : customer.status }}</span></div></div>
                <div v-else class="sales-empty-booking"><span><UsersIcon /></span><h3>Mulai dari pelanggan pertama.</h3><p>Daftarkan booking baru dan pantau prosesnya di sini.</p><Link href="/affiliate/booking/create" class="sales-primary-action"><PlusIcon /> Booking pelanggan</Link></div>
                <div class="sales-commission-breakdown"><div><span>Komisi booking</span><strong>{{ currency(kpi.booking_commissions) }}</strong></div><div><span>Komisi bulanan &amp; downline</span><strong>{{ currency(kpi.recurring_commissions) }}</strong></div></div>
            </section>

            <section class="sales-section-card sales-share-card">
                <div class="share-card-icon"><ShareIcon /></div><p class="sales-eyebrow">TUMBUH BERSAMA</p><h2>Perluas jaringan Anda.</h2><p>Ajak pelanggan baru atau undang rekan menjadi bagian dari tim afiliasi Anda.</p>
                <div class="sales-share-rewards"><span>Booking <strong>{{ currency(settings.affiliate_commission_booking) }}</strong></span><span>Bulanan <strong>{{ currency(settings.affiliate_commission_monthly) }}/pelanggan</strong></span></div>
                <div class="sales-share-actions"><a :href="customerShareUrl" target="_blank" rel="noopener noreferrer">Bagikan kode ke WhatsApp <ArrowUpRightIcon /></a><button type="button" @click="$emit('copy', profile.referral_link_affiliate, 'Link pendaftaran mitra berhasil disalin.')">Salin link ajak rekan <ClipboardDocumentIcon /></button><a :href="affiliateShareUrl" target="_blank" rel="noopener noreferrer">Undang rekan via WhatsApp <ArrowUpRightIcon /></a></div>
                <Link href="/affiliate/dashboard?tab=downlines" class="sales-team-link">Lihat afiliasi &amp; downline <ArrowRightIcon /></Link>
            </section>
        </div>
    </div>
</template>

<style scoped>
.sales-overview { display: grid; gap: 24px; margin-bottom: 24px; }
.sales-intro { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
.sales-eyebrow { color: var(--sales-muted); font-size: 9px; font-weight: 650; letter-spacing: .14em; margin: 0; }
.sales-intro h1 { font-size: 26px; letter-spacing: -.04em; font-weight: 650; margin: 9px 0; color: var(--sales-text); line-height: 1.25; }
.sales-intro h1 > span { color: var(--sales-accent); }
.sales-intro div > p:last-child { color: var(--sales-muted); font-size: 12px; }
.sales-account-badge { display: flex; align-items: center; gap: 7px; padding: 8px 11px; border: 1px solid var(--sales-line); border-radius: 7px; color: var(--sales-muted); font-size: 9px; white-space: nowrap; }
.sales-account-badge > span { width: 5px; height: 5px; border-radius: 50%; background: #68ad99; }
.sales-welcome-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px; }
.sales-referral-hero { position: relative; overflow: hidden; padding: 28px; border: 1px solid #7bacc72a; border-radius: 18px; background: linear-gradient(115deg, #16364a, #11263c); color: #edf4ff; }
.hero-orbit { position: absolute; width: 300px; height: 300px; right: -190px; top: -100px; border: 1px solid #a2d7e321; border-radius: 50%; box-shadow: 0 0 0 45px #a2d7e307, 0 0 0 90px #a2d7e305; pointer-events: none; }
.sales-referral-hero .sales-eyebrow { color: #97bdce; position: relative; }
.sales-referral-hero h2 { color: #f0f6ff; font-size: 27px; font-weight: 600; line-height: 1.25; letter-spacing: -.035em; margin: 14px 0; position: relative; }
.sales-referral-hero h2 > span { color: #a8c7d7; }
.hero-description { color: #b2c6d7; font-size: 11px; line-height: 1.8; max-width: 320px; position: relative; }
.sales-referral-code { display: flex; gap: 16px; align-items: center; justify-content: space-between; margin-top: 22px; border-top: 1px solid #a2d7e323; padding-top: 17px; position: relative; }
.sales-referral-code div { min-width: 0; }
.sales-referral-code div > span { display: block; color: #96b6cb; font-size: 8px; letter-spacing: .11em; margin-bottom: 7px; }
.sales-referral-code code { font-size: 17px; color: #e9f5ff; overflow-wrap: anywhere; letter-spacing: .025em; font-weight: 600; }
.sales-referral-code button { display: inline-flex; gap: 6px; padding: 9px 10px; border: 1px solid #a2d7e32e; border-radius: 8px; color: #d4e7f3; font-size: 10px; background: #ffffff08; flex-shrink: 0; }
.sales-referral-code button:hover { background: #ffffff12; }
.sales-referral-code button svg { width: 13px; height: 13px; }
.sales-sponsor { margin-top: 14px; color: #97b4c8; font-size: 10px; }
.sales-sponsor strong { color: #c8dce8; font-weight: 500; }
.sales-balance-card { display: flex; flex-direction: column; padding: 26px; border: 1px solid var(--sales-line); background: var(--sales-surface); border-radius: 18px; }
.balance-heading { display: flex; align-items: center; justify-content: space-between; gap: 14px; color: var(--sales-muted); font-size: 8px; font-weight: 600; letter-spacing: .09em; }
.balance-heading svg { width: 19px; height: 19px; color: var(--sales-accent); flex-shrink: 0; }
.balance-amount { font-size: clamp(22px, 2.1vw, 30px); letter-spacing: -.04em; font-weight: 650; margin: 19px 0 10px; line-height: 1.2; color: var(--sales-text); overflow-wrap: anywhere; }
.sales-balance-card > p { color: var(--sales-muted); font-size: 11px; line-height: 1.8; max-width: 270px; }
.balance-divider { height: 1px; background: var(--sales-line); margin: 21px 0 15px; }
.balance-secondary { display: flex; justify-content: space-between; gap: 12px; align-items: center; font-size: 9px; margin-bottom: 22px; color: var(--sales-muted); }
.balance-secondary strong { font-weight: 550; color: var(--sales-text); }
.sales-balance-card > .sales-primary-action { margin-top: auto; width: 100%; }
.sales-stats-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
.sales-metric-card { border: 1px solid var(--sales-line); background: var(--sales-surface); border-radius: 13px; padding: 20px; min-width: 0; }
.metric-heading { display: flex; gap: 10px; align-items: center; justify-content: space-between; font-size: 10px; color: var(--sales-muted); }
.metric-heading svg { width: 17px; height: 17px; color: var(--sales-accent); flex-shrink: 0; }
.sales-metric-card > strong { display: block; margin: 17px 0 8px; color: var(--sales-text); font-size: clamp(18px, 1.8vw, 25px); line-height: 1.2; letter-spacing: -.035em; font-weight: 650; overflow-wrap: anywhere; }
.sales-metric-card > p { font-size: 9px; color: var(--sales-muted); line-height: 1.6; }
.metric-green .metric-heading svg { color: #66b89f; }
.metric-violet .metric-heading svg { color: #a6a3d8; }
.sales-overview-lower { display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px; }
.sales-section-card { padding: 24px; border: 1px solid var(--sales-line); background: var(--sales-surface); border-radius: 16px; min-width: 0; }
.sales-section-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; }
.sales-section-heading h2 { color: var(--sales-text); font-size: 13px; font-weight: 600; }
.sales-section-heading p { color: var(--sales-muted); font-size: 10px; line-height: 1.7; margin-top: 6px; }
.sales-section-heading > a { display: flex; align-items: center; gap: 5px; color: var(--sales-accent); font-size: 9px; white-space: nowrap; margin-top: 2px; }
.sales-section-heading svg { width: 13px; height: 13px; }
.sales-activity-card { display: flex; flex-direction: column; }
.sales-empty-booking { text-align: center; padding: 36px 0; }
.sales-empty-booking > span { display: grid; place-items: center; margin: 0 auto 15px; width: 45px; height: 45px; border-radius: 14px; background: var(--sales-subtle); color: var(--sales-muted); }
.sales-empty-booking svg { width: 21px; height: 21px; }
.sales-empty-booking h3 { color: var(--sales-text); font-size: 12px; font-weight: 550; }
.sales-empty-booking p { color: var(--sales-muted); font-size: 10px; line-height: 1.8; margin: 8px 0 18px; }
.sales-empty-booking .sales-primary-action svg { width: 14px; height: 14px; }
.sales-commission-breakdown { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; border-top: 1px solid var(--sales-line); padding-top: 20px; margin-top: auto; }
.sales-commission-breakdown > div + div { border-left: 1px solid var(--sales-line); padding-left: 18px; }
.sales-commission-breakdown span { display: block; color: var(--sales-muted); font-size: 9px; }
.sales-commission-breakdown strong { display: block; margin-top: 8px; font-size: 14px; color: var(--sales-text); font-weight: 600; }
.sales-recent-list { padding: 18px 0; }
.sales-recent-customer { display: flex; gap: 10px; align-items: center; padding: 12px 0; }
.sales-recent-customer + .sales-recent-customer { border-top: 1px solid var(--sales-line); }
.customer-avatar { display: grid; place-items: center; width: 31px; height: 31px; border: 1px solid var(--sales-line); border-radius: 10px; color: var(--sales-accent); background: var(--sales-subtle); font-size: 11px; flex-shrink: 0; }
.sales-recent-customer > div { flex: 1; min-width: 0; }
.sales-recent-customer strong { display: block; color: var(--sales-text); font-size: 10px; font-weight: 600; }
.sales-recent-customer div > span { display: block; color: var(--sales-muted); font-size: 9px; margin-top: 4px; overflow-wrap: anywhere; }
.customer-state { font-size: 8px; padding: 4px 7px; border-radius: 5px; text-transform: capitalize; flex-shrink: 0; }
.state-active { color: #589c88; background: #65b99c14; }
.state-pending { color: #b69862; background: #cbb07814; }
html.dark .state-active { color: #94cebb; }
html.dark .state-pending { color: #dbc393; }
.share-card-icon { display: grid; place-items: center; width: 36px; height: 36px; color: var(--sales-accent); border-radius: 10px; background: var(--sales-subtle); margin-bottom: 18px; }
.share-card-icon svg { width: 18px; height: 18px; }
.sales-share-card h2 { font-size: 18px; font-weight: 600; letter-spacing: -.025em; color: var(--sales-text); margin: 8px 0 12px; }
.sales-share-card > p:not(.sales-eyebrow) { color: var(--sales-muted); font-size: 11px; line-height: 1.8; }
.sales-share-rewards { display: grid; gap: 7px; padding: 15px 0; color: var(--sales-muted); font-size: 9px; }
.sales-share-rewards strong { color: var(--sales-text); font-weight: 550; margin-left: 5px; }
.sales-share-actions { display: grid; gap: 7px; margin-top: 4px; }
.sales-share-actions :is(a, button) { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 11px 12px; border: 1px solid var(--sales-line); border-radius: 8px; color: var(--sales-text); font-size: 10px; text-align: left; }
.sales-share-actions :is(a, button):hover { background: var(--sales-subtle); }
.sales-share-actions svg { width: 13px; height: 13px; color: var(--sales-muted); }
.sales-team-link { display: flex; align-items: center; justify-content: center; gap: 8px; color: var(--sales-accent); font-size: 10px; margin-top: 18px; padding: 6px; }
.sales-team-link svg { width: 13px; height: 13px; }
@media (max-width: 1250px) { .sales-welcome-grid, .sales-overview-lower { grid-template-columns: 1.2fr 1fr; } .sales-metric-card { padding: 17px; } }
@media (max-width: 1150px) and (min-width: 1024px), (max-width: 760px) {
    .sales-welcome-grid, .sales-overview-lower { grid-template-columns: 1fr; }
    .sales-stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .balance-amount { font-size: 30px; }
}
@media (max-width: 600px) {
    .sales-overview { gap: 20px; }
    .sales-intro { align-items: flex-start; flex-direction: column; gap: 14px; }
    .sales-intro h1 { font-size: 23px; }
    .sales-intro div > p:last-child { font-size: 11px; }
    .sales-referral-hero { padding: 24px; }
    .sales-referral-hero h2 { font-size: 26px; }
    .sales-referral-code { gap: 10px; }
    .sales-referral-code code { font-size: 15px; }
    .sales-balance-card, .sales-section-card { padding: 22px; }
    .sales-stats-grid { gap: 12px; }
    .sales-metric-card { padding: 16px 14px; }
    .metric-heading { font-size: 9px; gap: 8px; }
    .metric-heading svg { width: 15px; height: 15px; }
    .sales-metric-card > strong { font-size: 19px; }
    .sales-metric-card > p { font-size: 8px; }
    .sales-commission-breakdown strong { font-size: 12px; }
}
</style>
