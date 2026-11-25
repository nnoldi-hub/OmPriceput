<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const { canManage } = usePermissions();

const props = defineProps({
    offer: Object,
    profitability: Object,
    summary: { type: Object, default: null },
    lineMargins: { type: Object, default: () => ({}) },
});

const sectionDefs = [
    { key: 'materials', title: 'A. Materiale necesare', priced: true, fallback: 'materials' },
    { key: 'labor', title: 'B. Manoperă', priced: true, fallback: 'labor' },
];

const chapters = computed(() => props.summary?.chapters ?? []);

function itemsForChapter(chapterId, section) {
    return props.offer.items.filter((item) => {
        const sameChapter = chapterId === null
            ? item.chapter_id === null
            : Number(item.chapter_id) === Number(chapterId);
        const itemSection = item.section || (item.equipment_id ? 'materials' : 'labor');

        return sameChapter && itemSection === section;
    });
}

const clientMaterials = computed(() => props.offer.items.filter((item) => {
    const section = item.section || (item.equipment_id ? 'materials' : 'labor');
    return section === 'client_materials';
}));

const subtotals = computed(() => ({
    materials: props.summary?.materials ?? sum(itemsForChapter(null, 'materials').concat(chapters.value.slice(1).flatMap((chapter) => itemsForChapter(chapter.id, 'materials')))),
    labor: props.summary?.labor ?? sum(itemsForChapter(null, 'labor').concat(chapters.value.slice(1).flatMap((chapter) => itemsForChapter(chapter.id, 'labor')))),
}));

const grandTotal = computed(() => props.summary?.subtotal ?? subtotals.value.materials + subtotals.value.labor);

function sum(items) {
    return items.reduce((total, item) => total + (Number(item.quantity) || 0) * (Number(item.unit_price) || 0), 0);
}

const statusOptions = [
    { value: 'draft', label: 'Draft' },
    { value: 'sent', label: 'Trimisa' },
    { value: 'accepted', label: 'Acceptata' },
    { value: 'rejected', label: 'Respinsa' },
    { value: 'expired', label: 'Expirata' },
];

const statusClasses = {
    draft: 'bg-slate-100 text-slate-600',
    sent: 'bg-blue-100 text-blue-700',
    accepted: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-700',
    expired: 'bg-slate-100 text-slate-500',
};

function money(value) {
    return Number(value || 0).toLocaleString('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function marginFor(item) {
    const margin = props.lineMargins?.[item.id]?.margin_percent;
    return margin === null || margin === undefined ? null : margin;
}

function isUncatalogued(item) {
    const flag = props.lineMargins?.[item.id]?.uncatalogued;
    return flag === undefined ? ! item.equipment_id && ! item.service_id && item.section !== 'client_materials' : flag;
}

function setStatus(status) {
    router.patch(route('sales.offers.status', props.offer.id), { status }, { preserveScroll: true });
}

function sendOffer() {
    const action = props.offer.status === 'sent' ? 'Retrimite oferta' : 'Trimite oferta';
    if (confirm(`${action}? Clientul va primi o nouă notificare.`)) {
        setStatus('sent');
    }
}

function destroy() {
    if (confirm('Ștergi această ofertă?')) {
        router.delete(route('sales.offers.destroy', props.offer.id));
    }
}

function addToCatalog(item) {
    router.post(route('sales.offers.items.catalog', [props.offer.id, item.id]), {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Oferta #${offer.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Oferta #{{ offer.id }} - {{ offer.title }}</h2>
                <div class="flex gap-2">
                    <a :href="route('sales.offers.pdf', offer.id)" target="_blank" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Descarcă PDF
                    </a>
                    <Link :href="route('sales.offers.edit', offer.id)" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Editează
                    </Link>
                    <button class="rounded-md border border-red-300 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50" @click="destroy">
                        Șterge
                    </button>
                    <button v-if="['draft', 'sent'].includes(offer.status)" class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500" @click="sendOffer">
                        {{ offer.status === 'sent' ? 'Retrimite oferta' : 'Trimite oferta' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto grid max-w-5xl grid-cols-1 gap-6 sm:px-6 lg:grid-cols-3 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm lg:col-span-1">
                    <h3 class="text-sm font-semibold text-slate-500">Client</h3>
                    <Link :href="route('sales.clients.show', offer.client.id)" class="mt-2 block font-medium text-blue-600 hover:text-blue-500">
                        {{ offer.client.name }}
                    </Link>
                    <p class="text-sm text-slate-500">{{ offer.client.phone }}</p>
                    <p class="text-sm text-slate-500">{{ offer.client.email }}</p>

                    <h3 class="mt-6 text-sm font-semibold text-slate-500">Status</h3>
                    <span class="mt-2 inline-block rounded-full px-2 py-1 text-xs font-medium" :class="statusClasses[offer.status]">
                        {{ offer.status }}
                    </span>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button
                            v-for="option in statusOptions"
                            :key="option.value"
                            :disabled="offer.status === option.value"
                            class="rounded-md border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                            @click="setStatus(option.value)"
                        >
                            {{ option.label }}
                        </button>
                    </div>

                    <div class="mt-6 text-sm text-slate-500">
                        <p>Creată de: {{ offer.user?.name }}</p>
                        <p>Creată la: {{ new Date(offer.created_at).toLocaleDateString('ro-RO') }}</p>
                        <p v-if="offer.valid_until">Valabilă până la: {{ new Date(offer.valid_until).toLocaleDateString('ro-RO') }}</p>
                    </div>
                </div>

                <div class="space-y-6 lg:col-span-2">
                    <div v-if="summary" class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-800">Situație de lucrări</h3>
                        <table class="mt-3 min-w-full divide-y divide-slate-200">
                            <thead>
                                <tr class="text-left text-xs font-medium uppercase text-slate-500">
                                    <th class="py-2">Capitol</th>
                                    <th class="py-2 text-right">Materiale</th>
                                    <th class="py-2 text-right">Manoperă</th>
                                    <th class="py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="chapter in summary.chapters" :key="chapter.ref ?? chapter.id ?? chapter.name">
                                    <td class="py-2 text-sm text-slate-700">{{ chapter.name }}</td>
                                    <td class="py-2 text-right text-sm text-slate-600">{{ money(chapter.materials) }} lei</td>
                                    <td class="py-2 text-right text-sm text-slate-600">{{ money(chapter.labor) }} lei</td>
                                    <td class="py-2 text-right text-sm font-semibold text-slate-900">{{ money(chapter.total) }} lei</td>
                                </tr>
                            </tbody>
                            <tfoot class="border-t border-slate-300 text-sm">
                                <tr>
                                    <td colspan="3" class="py-2 text-right text-slate-500">Total fără TVA</td>
                                    <td class="py-2 text-right font-semibold text-slate-900">{{ money(summary.subtotal) }} lei</td>
                                </tr>
                                <tr v-if="summary.is_vat_payer">
                                    <td colspan="3" class="py-2 text-right text-slate-500">TVA ({{ summary.vat_percentage }}%)</td>
                                    <td class="py-2 text-right font-semibold text-slate-900">{{ money(summary.vat_amount) }} lei</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="py-2 text-right font-medium text-slate-700">Total cu TVA</td>
                                    <td class="py-2 text-right text-lg font-bold text-slate-900">
                                        {{ money(summary.is_vat_payer ? summary.total_with_vat : summary.subtotal) }} lei
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                        <p v-if="!summary.is_vat_payer" class="mt-2 text-xs text-slate-500">Firma nu este plătitoare de TVA (cotă 0%).</p>
                    </div>

                    <div class="rounded-lg p-5 shadow-sm" :class="profitability.margin_percent < profitability.minimum_margin_percent ? 'border border-red-200 bg-red-50' : 'border border-emerald-200 bg-emerald-50'">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold" :class="profitability.margin_percent < profitability.minimum_margin_percent ? 'text-red-800' : 'text-emerald-800'">Profitabilitate estimată</h3>
                            <span class="text-lg font-bold" :class="profitability.margin_percent < profitability.minimum_margin_percent ? 'text-red-700' : 'text-emerald-700'">{{ money(profitability.margin_percent) }}% marjă</span>
                        </div>
                        <div class="mt-3 grid grid-cols-3 gap-3 text-sm">
                            <div><div class="text-slate-500">Cost estimat</div><strong>{{ money(profitability.estimated_cost) }} lei</strong></div>
                            <div><div class="text-slate-500">Profit estimat</div><strong>{{ money(profitability.estimated_profit) }} lei</strong></div>
                            <div><div class="text-slate-500">Prag minim</div><strong>{{ money(profitability.minimum_margin_percent) }}%</strong></div>
                        </div>
                        <p v-if="profitability.uncosted_items" class="mt-3 text-sm text-amber-800">Atenție: {{ profitability.uncosted_items }} articol(e) nu au cost configurat.</p>
                        <p v-if="profitability.margin_percent < profitability.minimum_margin_percent" class="mt-2 text-sm font-medium text-red-700">Oferta este sub marja minimă configurată. Verifică prețul înainte de acceptare.</p>
                    </div>

                    <div v-for="chapter in chapters" :key="`show-${chapter.id ?? chapter.name}`" class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-800">Capitol: {{ chapter.name }}</h3>
                            <span class="text-sm text-slate-500">Subtotal: <strong class="text-slate-900">{{ money(chapter.total) }} lei</strong></span>
                        </div>

                        <div v-for="section in sectionDefs" :key="section.key" class="mt-4">
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ section.title }}</h4>
                            <table class="mt-2 min-w-full divide-y divide-slate-200">
                                <thead>
                                    <tr class="text-left text-xs font-medium uppercase text-slate-500">
                                        <th class="py-2">Descriere</th>
                                        <th class="py-2 text-right">Cant.</th>
                                        <th class="py-2 text-right">UM</th>
                                        <th class="py-2 text-right">Preț unitar</th>
                                        <th class="py-2 text-right">Subtotal</th>
                                        <th class="py-2 text-right">Marjă</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="item in itemsForChapter(chapter.id, section.key)" :key="item.id">
                                        <td class="py-2">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span>{{ item.description }}</span>
                                                <span v-if="isUncatalogued(item)" class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800">necatalogat</span>
                                                <button
                                                    v-if="section.key === 'materials' && isUncatalogued(item) && canManage('offers')"
                                                    type="button"
                                                    class="rounded border border-blue-300 px-1.5 py-0.5 text-xs font-medium text-blue-600 hover:bg-blue-50"
                                                    title="Adaugă în catalogul de materiale"
                                                    @click="addToCatalog(item)"
                                                >
                                                    + catalog
                                                </button>
                                            </div>
                                        </td>
                                        <td class="py-2 text-right">{{ item.quantity }}</td>
                                        <td class="py-2 text-right">{{ item.unit }}</td>
                                        <td class="py-2 text-right">{{ money(item.unit_price) }} lei</td>
                                        <td class="py-2 text-right">{{ money(item.quantity * item.unit_price) }} lei</td>
                                        <td class="py-2 text-right text-xs" :class="marginFor(item) !== null && marginFor(item) < profitability.minimum_margin_percent ? 'text-red-600' : 'text-slate-500'">
                                            {{ marginFor(item) === null ? '—' : `${money(marginFor(item))}%` }}
                                        </td>
                                    </tr>
                                    <tr v-if="!itemsForChapter(chapter.id, section.key).length">
                                        <td colspan="6" class="py-2 text-sm text-slate-400">Nicio linie.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-if="clientMaterials.length" class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-500">C. Materiale achiziționate de client</h3>
                        <table class="mt-4 min-w-full divide-y divide-slate-200">
                            <thead>
                                <tr class="text-left text-xs font-medium uppercase text-slate-500">
                                    <th class="py-2">Descriere</th>
                                    <th class="py-2 text-right">Cant.</th>
                                    <th class="py-2 text-right">UM</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="item in clientMaterials" :key="item.id">
                                    <td class="py-2">{{ item.description }}</td>
                                    <td class="py-2 text-right">{{ item.quantity }}</td>
                                    <td class="py-2 text-right">{{ item.unit }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="mt-2 text-xs text-slate-500">Listă informativă, fără preț, exclusă din total.</p>
                    </div>

                    <div class="rounded-lg bg-slate-900 p-6 shadow-sm">
                        <div class="flex items-center justify-between text-white">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-400">Materiale: {{ money(subtotals.materials) }} lei</p>
                                <p class="text-xs uppercase tracking-wide text-slate-400">Manoperă: {{ money(subtotals.labor) }} lei</p>
                            </div>
                            <div class="text-right">
                                <span class="text-sm text-slate-400">Total fără TVA</span>
                                <p class="text-2xl font-bold">{{ money(grandTotal) }} lei</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="offer.notes" class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-500">Note interne</h3>
                        <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ offer.notes }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
