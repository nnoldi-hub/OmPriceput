<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Icon from '@/Components/Icon.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    period: { type: String, default: '7days' },
    kpis: { type: Object, required: true },
    funnel: {
        type: Object,
        default: () => ({
            visitors: 0,
            quoteVisitors: 0,
            quoteViews: 0,
            leads: 0,
            visitorsToQuote: 0,
            quoteToLead: 0,
            visitorsToLead: 0,
        }),
    },
    dailyTrend: { type: Array, default: () => [] },
    topPages: { type: Array, default: () => [] },
    topSources: { type: Array, default: () => [] },
    utmCampaigns: { type: Array, default: () => [] },
    devices: { type: Object, default: () => ({ desktop: 0, mobile: 0, tablet: 0 }) },
    browsers: { type: Array, default: () => [] },
    recentSessions: { type: Array, default: () => [] },
});

const activeSession = ref(null);

function changePeriod(newPeriod) {
    router.get(route('admin.traffic.index'), { period: newPeriod }, { preserveState: true, preserveScroll: true });
}

function toggleSession(id) {
    activeSession.value = activeSession.value === id ? null : id;
}

const totalDevicesCount = (props.devices.desktop || 0) + (props.devices.mobile || 0) + (props.devices.tablet || 0);

function devicePercent(count) {
    if (!totalDevicesCount) return 0;
    return Math.round((count / totalDevicesCount) * 100);
}

function maxTrendViews() {
    if (!props.dailyTrend.length) return 1;
    return Math.max(...props.dailyTrend.map((d) => d.views || 0), 1);
}
</script>

<template>
    <Head title="Trafic & Analitică Vizitatori" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-slate-800">Trafic & Analitică Vizitatori</h2>
                    <p class="mt-1 text-sm text-slate-500">Monitorizează activitatea vizitatorilor pe site, sursele de promovare și traseele de navigare. Sunt incluse doar vizitele umane.</p>
                </div>

                <div class="flex flex-wrap gap-1 rounded-lg bg-slate-200 p-1 text-xs font-medium">
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 transition"
                        :class="period === 'today' ? 'bg-white font-semibold text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                        @click="changePeriod('today')"
                    >
                        Azi
                    </button>
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 transition"
                        :class="period === '7days' ? 'bg-white font-semibold text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                        @click="changePeriod('7days')"
                    >
                        Ultimele 7 zile
                    </button>
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 transition"
                        :class="period === '30days' ? 'bg-white font-semibold text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                        @click="changePeriod('30days')"
                    >
                        Ultimele 30 zile
                    </button>
                    <button
                        type="button"
                        class="rounded-md px-3 py-1.5 transition"
                        :class="period === 'this_month' ? 'bg-white font-semibold text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                        @click="changePeriod('this_month')"
                    >
                        Luna aceasta
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">
                <!-- KPI Summary Cards -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-xl bg-white p-5 shadow-sm border border-slate-100">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Vizualizări Totale</span>
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                <Icon name="globe" class="h-4 w-4" />
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-2xl font-bold text-slate-900">{{ kpis.totalViews.toLocaleString('ro-RO') }}</span>
                            <span
                                v-if="kpis.viewsGrowth !== 0"
                                class="text-xs font-medium"
                                :class="kpis.viewsGrowth > 0 ? 'text-emerald-600' : 'text-rose-600'"
                            >
                                {{ kpis.viewsGrowth > 0 ? '+' : '' }}{{ kpis.viewsGrowth }}%
                            </span>
                        </div>
                        <span class="text-xs text-slate-400 mt-1 block">față de perioada anterioară</span>
                    </div>

                    <div class="rounded-xl bg-white p-5 shadow-sm border border-slate-100">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Vizitatori Unici</span>
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                <Icon name="users" class="h-4 w-4" />
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-2xl font-bold text-slate-900">{{ kpis.uniqueVisitors.toLocaleString('ro-RO') }}</span>
                            <span
                                v-if="kpis.visitorsGrowth !== 0"
                                class="text-xs font-medium"
                                :class="kpis.visitorsGrowth > 0 ? 'text-emerald-600' : 'text-rose-600'"
                            >
                                {{ kpis.visitorsGrowth > 0 ? '+' : '' }}{{ kpis.visitorsGrowth }}%
                            </span>
                        </div>
                        <span class="text-xs text-slate-400 mt-1 block">față de perioada anterioară</span>
                    </div>

                    <div class="rounded-xl bg-white p-5 shadow-sm border border-slate-100">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Sesiuni Totale</span>
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                                <Icon name="clock" class="h-4 w-4" />
                            </span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-2xl font-bold text-slate-900">{{ kpis.totalSessions.toLocaleString('ro-RO') }}</span>
                            <span
                                v-if="kpis.sessionsGrowth !== 0"
                                class="text-xs font-medium"
                                :class="kpis.sessionsGrowth > 0 ? 'text-emerald-600' : 'text-rose-600'"
                            >
                                {{ kpis.sessionsGrowth > 0 ? '+' : '' }}{{ kpis.sessionsGrowth }}%
                            </span>
                        </div>
                        <span class="text-xs text-slate-400 mt-1 block">Sesiuni de navigare distincte</span>
                    </div>

                    <div class="rounded-xl bg-white p-5 shadow-sm border border-slate-100">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Pagini / Sesiune</span>
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                                <Icon name="file-text" class="h-4 w-4" />
                            </span>
                        </div>
                        <div class="mt-3">
                            <span class="text-2xl font-bold text-slate-900">{{ kpis.pagesPerSession }}</span>
                        </div>
                        <span class="text-xs text-slate-400 mt-1 block">Media de pagini explorate</span>
                    </div>
                </div>

                <div class="flex items-start gap-3 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">
                    <Icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
                    <p>
                        Cifrele includ doar trafic uman. Boții, cererile de prefetch, utilizatorii autentificați și rutele interne
                        (admin, client, API, calendar) sunt excluse din toate metricile.
                    </p>
                </div>

                <!-- Conversion Funnel -->
                <div class="rounded-xl bg-white p-6 shadow-sm border border-slate-100">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Pâlnie de conversie</h3>
                            <p class="text-xs text-slate-500 mt-0.5">De la vizită la cerere de deviz trimisă</p>
                        </div>
                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Estimare orientativă</span>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-lg border border-slate-200 p-4">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">1</span>
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Vizitatori unici</span>
                            </div>
                            <div class="mt-3 text-2xl font-bold text-slate-900">{{ funnel.visitors.toLocaleString('ro-RO') }}</div>
                            <div class="text-xs text-slate-400 mt-1">trafic uman în perioadă</div>
                        </div>

                        <div class="rounded-lg border border-slate-200 p-4">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">2</span>
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Au deschis cererea</span>
                            </div>
                            <div class="mt-3 flex items-baseline gap-2">
                                <span class="text-2xl font-bold text-slate-900">{{ funnel.quoteVisitors.toLocaleString('ro-RO') }}</span>
                                <span class="text-xs font-medium text-emerald-600">{{ funnel.visitorsToQuote }}% din vizitatori</span>
                            </div>
                            <div class="text-xs text-slate-400 mt-1">{{ funnel.quoteViews.toLocaleString('ro-RO') }} vizualizări ale paginii /cerere-deviz</div>
                        </div>

                        <div class="rounded-lg border border-slate-200 p-4">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-purple-100 text-xs font-bold text-purple-700">3</span>
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Cereri deviz</span>
                            </div>
                            <div class="mt-3 flex items-baseline gap-2">
                                <span class="text-2xl font-bold text-slate-900">{{ funnel.leads.toLocaleString('ro-RO') }}</span>
                                <span class="text-xs font-medium text-purple-600">{{ funnel.visitorsToLead }}% din vizitatori</span>
                            </div>
                            <div class="text-xs text-slate-400 mt-1">{{ funnel.quoteToLead }}% din vizitatorii paginii de cerere</div>
                        </div>
                    </div>

                    <p class="mt-3 text-[11px] text-slate-400">
                        Cererile sunt lead-uri web create în perioadă, fără comenzile din magazin. Vizitatorii și lead-urile nu sunt legate direct,
                        deci procentele sunt orientative.
                    </p>
                </div>

                <!-- Daily Trend Chart & Devices -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="rounded-xl bg-white p-6 shadow-sm border border-slate-100 lg:col-span-2">
                        <h3 class="text-base font-bold text-slate-900">Evoluție Trafic Zilnic</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Număr de vizualizări și vizitatori unici per zi</p>

                        <div v-if="dailyTrend.length" class="mt-6 flex h-48 items-end gap-2 border-b border-slate-100 pb-2">
                            <div
                                v-for="day in dailyTrend"
                                :key="day.date"
                                class="group relative flex flex-1 flex-col items-center justify-end h-full"
                            >
                                <!-- Tooltip -->
                                <div class="absolute -top-12 z-10 hidden rounded bg-slate-900 px-2 py-1 text-center text-xs text-white shadow group-hover:block whitespace-nowrap">
                                    <div class="font-bold">{{ day.date }}</div>
                                    <div>{{ day.views }} vizualizări ({{ day.visitors }} unici)</div>
                                </div>

                                <div class="flex w-full items-end justify-center gap-1 h-full">
                                    <div
                                        class="w-full max-w-[18px] rounded-t bg-blue-500 transition-all hover:bg-blue-600"
                                        :style="{ height: `${Math.max(Math.round((day.views / maxTrendViews()) * 100), 4)}%` }"
                                    />
                                </div>
                                <span class="mt-2 text-[10px] text-slate-400 font-medium truncate max-w-full">
                                    {{ day.date ? day.date.substring(5) : '' }}
                                </span>
                            </div>
                        </div>
                        <p v-else class="py-12 text-center text-sm text-slate-400">Nu există date suficiente pentru grafic în această perioadă.</p>
                    </div>

                    <!-- Dispozitive & Browsere -->
                    <div class="rounded-xl bg-white p-6 shadow-sm border border-slate-100 space-y-6">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Dispozitive</h3>
                            <p class="text-xs text-slate-500">De pe ce dispozitive intră vizitatorii</p>

                            <div class="mt-4 space-y-3">
                                <div>
                                    <div class="flex justify-between text-xs font-medium text-slate-700 mb-1">
                                        <span>Desktop / Laptop</span>
                                        <span>{{ devices.desktop }} ({{ devicePercent(devices.desktop) }}%)</span>
                                    </div>
                                    <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full bg-blue-600 rounded-full" :style="{ width: `${devicePercent(devices.desktop)}%` }" />
                                    </div>
                                </div>

                                <div>
                                    <div class="flex justify-between text-xs font-medium text-slate-700 mb-1">
                                        <span>Mobil</span>
                                        <span>{{ devices.mobile }} ({{ devicePercent(devices.mobile) }}%)</span>
                                    </div>
                                    <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full bg-emerald-500 rounded-full" :style="{ width: `${devicePercent(devices.mobile)}%` }" />
                                    </div>
                                </div>

                                <div>
                                    <div class="flex justify-between text-xs font-medium text-slate-700 mb-1">
                                        <span>Tabletă</span>
                                        <span>{{ devices.tablet }} ({{ devicePercent(devices.tablet) }}%)</span>
                                    </div>
                                    <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full bg-purple-500 rounded-full" :style="{ width: `${devicePercent(devices.tablet)}%` }" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-slate-100 pt-4">
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Browsere Principale</h4>
                            <div class="space-y-2">
                                <div v-for="b in browsers" :key="b.browser" class="flex justify-between text-xs text-slate-600">
                                    <span class="font-medium text-slate-800">{{ b.browser || 'Necunoscut' }}</span>
                                    <span>{{ b.count }} vizite</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Pages & Traffic Sources -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Top Pagini / Produse Vizitate -->
                    <div class="rounded-xl bg-white p-6 shadow-sm border border-slate-100">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Cele mai vizitate Pagini / Produse</h3>
                                <p class="text-xs text-slate-500">Află ce produse și secțiuni atrag atenția</p>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-600">
                                <thead class="bg-slate-50 text-slate-500 uppercase">
                                    <tr>
                                        <th class="py-2 px-3 font-semibold">Pagină</th>
                                        <th class="py-2 px-3 text-right font-semibold">Vizualizări</th>
                                        <th class="py-2 px-3 text-right font-semibold">Unici</th>
                                        <th class="py-2 px-3 text-right font-semibold">%</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="page in topPages" :key="page.path" class="hover:bg-slate-50/80">
                                        <td class="py-2.5 px-3 max-w-[220px] truncate">
                                            <div class="font-semibold text-slate-800 truncate" :title="page.resolved_title">{{ page.resolved_title }}</div>
                                            <div class="text-[11px] text-slate-400 font-mono truncate" :title="page.path">{{ page.path }}</div>
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-bold text-slate-800">{{ page.views }}</td>
                                        <td class="py-2.5 px-3 text-right text-slate-500">{{ page.visitors }}</td>
                                        <td class="py-2.5 px-3 text-right font-medium text-blue-600">{{ page.percentage }}%</td>
                                    </tr>
                                    <tr v-if="!topPages.length">
                                        <td colspan="4" class="py-6 text-center text-slate-400">Nu există vizite înregistrate în această perioadă.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Surse de Trafic & Campanii Marketing -->
                    <div class="space-y-6">
                        <div class="rounded-xl bg-white p-6 shadow-sm border border-slate-100">
                            <h3 class="text-base font-bold text-slate-900 mb-1">Surse de Trafic (Referrers)</h3>
                            <p class="text-xs text-slate-500 mb-4">De unde ajung vizitatorii pe site (Google, Facebook, Direct, etc.)</p>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs text-slate-600">
                                    <thead class="bg-slate-50 text-slate-500 uppercase">
                                        <tr>
                                            <th class="py-2 px-3 font-semibold">Sursă</th>
                                            <th class="py-2 px-3 text-right font-semibold">Vizualizări</th>
                                            <th class="py-2 px-3 text-right font-semibold">Vizitatori Unici</th>
                                            <th class="py-2 px-3 text-right font-semibold">% Trafic</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="source in topSources" :key="source.referer_domain" class="hover:bg-slate-50/80">
                                            <td class="py-2.5 px-3 font-semibold text-slate-800">{{ source.referer_domain }}</td>
                                            <td class="py-2.5 px-3 text-right font-bold text-slate-800">{{ source.views }}</td>
                                            <td class="py-2.5 px-3 text-right text-slate-500">{{ source.visitors }}</td>
                                            <td class="py-2.5 px-3 text-right font-medium text-emerald-600">{{ source.percentage }}%</td>
                                        </tr>
                                        <tr v-if="!topSources.length">
                                            <td colspan="4" class="py-6 text-center text-slate-400">Fără date despre surse în această perioadă.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- UTM Campaigns -->
                        <div v-if="utmCampaigns.length" class="rounded-xl bg-white p-6 shadow-sm border border-slate-100">
                            <h3 class="text-base font-bold text-slate-900 mb-1">Campanii Marketing (UTM)</h3>
                            <p class="text-xs text-slate-500 mb-4">Performanța reclamelor (Facebook Ads, Google Ads, etc.)</p>

                            <div class="space-y-2">
                                <div v-for="utm in utmCampaigns" :key="utm.utm_source + utm.utm_campaign" class="flex items-center justify-between rounded-lg bg-slate-50 p-3 text-xs">
                                    <div>
                                        <span class="font-bold text-slate-800">{{ utm.utm_campaign || 'Campanie fără nume' }}</span>
                                        <div class="text-[11px] text-slate-500">Sursă: {{ utm.utm_source }} | Medium: {{ utm.utm_medium || '-' }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-slate-900">{{ utm.views }} accesări</div>
                                        <div class="text-[11px] text-slate-400">{{ utm.visitors }} unici</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Visitor Journeys ("Ce fac vizitatorii in timp real") -->
                <div class="rounded-xl bg-white p-6 shadow-sm border border-slate-100">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Ce fac vizitatorii - Trasee Navigare în Timp Real</h3>
                            <p class="text-xs text-slate-500">Urmărește pas cu pas ce pagini și produse a explorat fiecare vizitator recente</p>
                        </div>
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">Live Activity Stream</span>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="session in recentSessions"
                            :key="session.session_id"
                            class="rounded-xl border border-slate-200 transition bg-white"
                        >
                            <div
                                class="flex flex-col sm:flex-row sm:items-center justify-between p-4 cursor-pointer hover:bg-slate-50 rounded-xl"
                                @click="toggleSession(session.session_id)"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600 font-mono text-xs">
                                        {{ session.device_type === 'mobile' ? '📱' : session.device_type === 'tablet' ? '🖊️' : '💻' }}
                                    </span>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800 text-sm">Vizitator {{ session.visitor_id ? session.visitor_id.substring(0, 8) : 'Anonim' }}</span>
                                            <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600 uppercase">{{ session.browser }}</span>
                                            <span class="rounded bg-blue-50 px-1.5 py-0.5 text-[10px] font-medium text-blue-700">Sursă: {{ session.referer_domain }}</span>
                                        </div>
                                        <div class="text-xs text-slate-500 mt-0.5">
                                            Prima accesare: <span class="font-medium text-slate-700">{{ session.first_seen }}</span> &bull;
                                            Pagini văzute: <span class="font-bold text-blue-600">{{ session.page_count }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-2 sm:mt-0 flex items-center gap-3 text-xs">
                                    <div class="text-right hidden sm:block">
                                        <div class="text-slate-500">Pagină intrare: <span class="font-mono text-slate-700">{{ session.landing_page }}</span></div>
                                        <div class="text-slate-500">Ultima pagină: <span class="font-mono text-slate-700">{{ session.exit_page }}</span></div>
                                    </div>
                                    <button type="button" class="rounded bg-slate-100 px-3 py-1.5 font-semibold text-slate-700 hover:bg-slate-200 text-xs">
                                        {{ activeSession === session.session_id ? 'Ascunde traseu' : 'Vezi traseu complet ↓' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Expanded Journey Details -->
                            <div v-if="activeSession === session.session_id" class="border-t border-slate-100 bg-slate-50/50 p-4 rounded-b-xl">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Istoric Navigare Pas cu Pas (Chronological Journey)</h4>
                                <div class="relative pl-6 space-y-3 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-blue-200">
                                    <div v-for="(pv, idx) in session.pages" :key="idx" class="relative flex items-start justify-between text-xs">
                                        <div class="absolute -left-6 top-0.5 flex h-3.5 w-3.5 items-center justify-center rounded-full bg-blue-600 text-[9px] text-white font-bold">
                                            {{ idx + 1 }}
                                        </div>
                                        <div>
                                            <span class="font-semibold text-slate-900">{{ pv.title }}</span>
                                            <span class="ml-2 font-mono text-[11px] text-slate-500">({{ pv.path }})</span>
                                        </div>
                                        <span class="font-mono text-slate-400 text-[11px] whitespace-nowrap">{{ pv.time }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p v-if="!recentSessions.length" class="py-8 text-center text-sm text-slate-400">Nu există sesiuni recente de vizitatori.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
