<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { Head, Link, usePage } from '@inertiajs/vue3';

const { canManage } = usePermissions();

const props = defineProps({
    client: Object,
    summary: Object,
});
const page = usePage();
const roles = page.props.auth.roles ?? [];
const canAdmin = roles.includes('admin');
const canTechnical = roles.includes('admin') || roles.includes('tehnic') || roles.includes('suport');

const statusLabels = { lead: 'Lead', client: 'Client', inactive: 'Inactiv' };
const statusClasses = {
    lead: 'bg-amber-100 text-amber-800',
    client: 'bg-green-100 text-green-800',
    inactive: 'bg-slate-100 text-slate-600',
};

const offerStatusClasses = {
    draft: 'bg-slate-100 text-slate-600',
    sent: 'bg-blue-100 text-blue-700',
    accepted: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-700',
    expired: 'bg-slate-100 text-slate-500',
};

const ticketStatusClasses = {
    open: 'bg-amber-100 text-amber-800',
    in_progress: 'bg-blue-100 text-blue-700',
    resolved: 'bg-green-100 text-green-800',
    closed: 'bg-slate-100 text-slate-600',
};

function money(value) {
    return Number(value).toLocaleString('ro-RO', { minimumFractionDigits: 2 });
}
</script>

<template>
    <Head :title="client.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ client.name }}</h2>
                <div class="flex gap-2">
                    <Link v-if="canManage('offers')" :href="route('sales.offers.create', { client_id: client.id })" class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                        Oferta noua
                    </Link>
                    <Link v-if="canManage('clients')" :href="route('sales.clients.edit', client.id)" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Editeaza
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-6 sm:px-6 lg:grid-cols-3 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm lg:col-span-1">
                    <h3 class="text-sm font-semibold text-slate-500">Detalii client</h3>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div v-if="client.company_name">
                            <dt class="text-slate-400">Firma</dt>
                            <dd class="text-slate-900">{{ client.company_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Telefon</dt>
                            <dd class="text-slate-900">{{ client.phone ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Email</dt>
                            <dd class="text-slate-900">{{ client.email ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Adresa</dt>
                            <dd class="text-slate-900">{{ [client.address, client.city, client.county].filter(Boolean).join(', ') || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Sursa</dt>
                            <dd class="capitalize text-slate-900">{{ client.source }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Status</dt>
                            <dd>
                                <span class="rounded-full px-2 py-1 text-xs font-medium" :class="statusClasses[client.status]">
                                    {{ statusLabels[client.status] }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Portal client</dt>
                            <dd v-if="client.user" class="text-slate-900">
                                {{ client.user.name }} <span class="text-xs text-slate-500">({{ client.user.email }})</span>
                            </dd>
                            <dd v-else class="text-slate-400">Neasociat</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Asignat</dt>
                            <dd class="text-slate-900">{{ client.assigned_to?.name ?? '-' }}</dd>
                        </div>
                        <div v-if="client.notes">
                            <dt class="text-slate-400">Notite</dt>
                            <dd class="whitespace-pre-line text-slate-900">{{ client.notes }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="space-y-6 lg:col-span-2">
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                        <div class="rounded-lg bg-white p-4 shadow-sm">
                            <div class="text-xs text-slate-500">Valoare facturata</div>
                            <div class="mt-1 text-lg font-semibold text-slate-900">{{ money(summary.invoiceTotal) }} lei</div>
                        </div>
                        <div class="rounded-lg bg-white p-4 shadow-sm">
                            <div class="text-xs text-slate-500">Total achitat</div>
                            <div class="mt-1 text-lg font-semibold text-green-600">{{ money(summary.invoicePaid) }} lei</div>
                        </div>
                        <div class="rounded-lg bg-white p-4 shadow-sm">
                            <div class="text-xs text-slate-500">Sold client</div>
                            <div class="mt-1 text-lg font-semibold" :class="summary.invoiceBalance > 0 ? 'text-red-600' : 'text-slate-900'">{{ money(summary.invoiceBalance) }} lei</div>
                            <div v-if="summary.overdueInvoices" class="text-xs text-red-500">{{ summary.overdueInvoices }} facturi restante</div>
                        </div>
                        <div class="rounded-lg bg-white p-4 shadow-sm">
                            <div class="text-xs text-slate-500">Activitati in asteptare</div>
                            <div class="mt-1 text-lg font-semibold text-slate-900">{{ summary.pendingActivities }}</div>
                        </div>
                        <div class="rounded-lg bg-white p-4 shadow-sm">
                            <div class="text-xs text-slate-500">Tichete deschise</div>
                            <div class="mt-1 text-lg font-semibold text-slate-900">{{ summary.openTickets }}</div>
                        </div>
                        <div class="rounded-lg bg-white p-4 shadow-sm">
                            <div class="text-xs text-slate-500">Abonamente active</div>
                            <div class="mt-1 text-lg font-semibold text-slate-900">{{ summary.activeSubscriptions }}</div>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-500">Oferte ({{ client.offers.length }})</h3>
                        <div v-if="client.offers.length" class="mt-4 divide-y divide-slate-100">
                            <Link
                                v-for="offer in client.offers"
                                :key="offer.id"
                                :href="route('sales.offers.show', offer.id)"
                                class="flex items-center justify-between py-3 hover:bg-slate-50"
                            >
                                <div>
                                    <div class="font-medium text-slate-900">{{ offer.title }}</div>
                                    <div class="text-xs text-slate-400">{{ new Date(offer.created_at).toLocaleDateString('ro-RO') }}</div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-semibold text-slate-900">{{ money(offer.total_amount) }} lei</span>
                                    <span class="rounded-full px-2 py-1 text-xs font-medium" :class="offerStatusClasses[offer.status]">
                                        {{ offer.status }}
                                    </span>
                                </div>
                            </Link>
                        </div>
                        <p v-else class="mt-4 text-sm text-slate-400">Nicio oferta pentru acest client inca.</p>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-500">Activitati ({{ client.activities.length }})</h3>
                            <Link v-if="canManage('activities')" :href="route('sales.activities.create', { client_id: client.id })" class="text-sm font-medium text-blue-600 hover:text-blue-500">Adauga</Link>
                        </div>
                        <div v-if="client.activities.length" class="mt-4 divide-y divide-slate-100">
                            <div v-for="activity in client.activities" :key="activity.id" class="flex items-center justify-between py-3">
                                <div><div class="font-medium text-slate-900">{{ activity.title }}</div><div class="text-xs text-slate-400">{{ activity.due_at ? new Date(activity.due_at).toLocaleString('ro-RO') : 'Fara termen' }} · {{ activity.assigned_to?.name ?? 'Neasignat' }}</div></div>
                                <span class="rounded-full px-2 py-1 text-xs font-medium" :class="activity.status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'">{{ activity.status === 'completed' ? 'Finalizata' : 'In asteptare' }}</span>
                            </div>
                        </div>
                        <p v-else class="mt-4 text-sm text-slate-400">Nicio activitate pentru acest client.</p>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-500">Abonamente ({{ client.subscriptions.length }})</h3>
                            <Link v-if="canAdmin" :href="route('admin.subscriptions.create', { client_id: client.id })" class="text-sm font-medium text-blue-600 hover:text-blue-500">Adauga</Link>
                        </div>
                        <div v-if="client.subscriptions.length" class="mt-4 divide-y divide-slate-100">
                            <div v-for="subscription in client.subscriptions" :key="subscription.id" class="flex items-center justify-between py-3"><div><div class="font-medium text-slate-900">{{ subscription.plan }}</div><div class="text-xs text-slate-400">{{ money(subscription.price) }} lei / {{ subscription.billing_cycle === 'yearly' ? 'an' : 'luna' }}</div></div><span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium capitalize text-slate-600">{{ subscription.status }}</span></div>
                        </div>
                        <p v-else class="mt-4 text-sm text-slate-400">Niciun abonament pentru acest client.</p>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-500">Tichete suport ({{ client.tickets.length }})</h3>
                            <Link v-if="canTechnical" :href="route('technical.tickets.create', { client_id: client.id })" class="text-sm font-medium text-blue-600 hover:text-blue-500">Tichet nou</Link>
                        </div>
                        <div v-if="client.tickets.length" class="mt-4 divide-y divide-slate-100">
                            <Link v-for="ticket in client.tickets" :key="ticket.id" :href="route('technical.tickets.show', ticket.id)" class="flex items-center justify-between py-3 hover:bg-slate-50"><div><div class="font-medium text-slate-900">{{ ticket.subject }}</div><div class="text-xs text-slate-400">Asignat: {{ ticket.assigned_to?.name ?? '-' }}</div></div><span class="rounded-full px-2 py-1 text-xs font-medium" :class="ticketStatusClasses[ticket.status]">{{ ticket.status.replace('_', ' ') }}</span></Link>
                        </div>
                        <p v-else class="mt-4 text-sm text-slate-400">Niciun tichet pentru acest client.</p>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-slate-500">Instalari ({{ client.installations.length }})</h3>
                        <div v-if="client.installations.length" class="mt-4 divide-y divide-slate-100">
                            <div v-for="installation in client.installations" :key="installation.id" class="flex items-center justify-between py-3">
                                <div>
                                    <div class="font-medium text-slate-900">{{ installation.address ?? 'Adresa nespecificata' }}</div>
                                    <div class="text-xs text-slate-400">
                                        {{ installation.scheduled_at ? new Date(installation.scheduled_at).toLocaleString('ro-RO') : 'Neprogramat' }}
                                    </div>
                                </div>
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium capitalize text-slate-600">
                                    {{ installation.status.replace('_', ' ') }}
                                </span>
                            </div>
                        </div>
                        <p v-else class="mt-4 text-sm text-slate-400">Nicio instalare programata.</p>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-500">Facturi ({{ client.invoices.length }})</h3>
                            <div class="flex items-center gap-3">
                                <a :href="route('sales.clients.statement.pdf', client.id)" target="_blank" class="text-sm font-medium text-slate-600 hover:text-slate-900">Situatie PDF</a>
                                <a :href="route('sales.clients.statement.excel', client.id)" class="text-sm font-medium text-slate-600 hover:text-slate-900">Situatie Excel</a>
                                <Link v-if="canAdmin" :href="route('admin.invoices.create', { client_id: client.id })" class="text-sm font-medium text-blue-600 hover:text-blue-500">Factura noua</Link>
                            </div>
                        </div>
                        <div v-if="client.invoices.length" class="mt-4 divide-y divide-slate-100">
                            <div v-for="invoice in client.invoices" :key="invoice.id" class="flex items-center justify-between py-3">
                                <div>
                                    <Link v-if="canAdmin" :href="route('admin.invoices.show', invoice.id)" class="font-medium text-blue-600">{{ invoice.invoice_number }}</Link>
                                    <div v-else class="font-medium text-slate-900">{{ invoice.invoice_number }}</div>
                                    <div class="text-xs text-slate-400">Achitat {{ money(invoice.paid_amount) }} lei · Sold {{ money(Math.max(Number(invoice.amount) - Number(invoice.paid_amount), 0)) }} lei</div>
                                    <div v-if="invoice.payments?.length" class="text-xs text-slate-400">{{ invoice.payments.length }} plati inregistrate</div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-semibold text-slate-900">{{ money(invoice.amount) }} lei</span>
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium capitalize text-slate-600">
                                        {{ invoice.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <p v-else class="mt-4 text-sm text-slate-400">Nicio factura emisa.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
