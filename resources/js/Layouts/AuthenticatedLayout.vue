<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import GlobalSearch from '@/Components/GlobalSearch.vue';
import Icon from '@/Components/Icon.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const page = usePage();
const roles = computed(() => page.props.auth.roles ?? []);
const permissions = computed(() => page.props.auth.permissions ?? []);
const hasRole = (...names) => names.some((name) => roles.value.includes(name));
const hasPermission = (...names) => names.some((name) => permissions.value.includes(name));
const hasAccess = (rolesToCheck, permissionsToCheck = []) =>
    hasRole(...rolesToCheck) || hasPermission(...permissionsToCheck);

const sections = [
    {
        key: 'sales',
        name: 'Vanzari',
        icon: 'briefcase',
        roles: ['admin', 'vanzari'],
        permissions: ['clients.view', 'clients.manage', 'offers.view', 'offers.manage', 'activities.view', 'activities.manage'],
        items: [
            { name: 'Clienti', route: 'sales.clients.index', permissions: ['clients.view', 'clients.manage'] },
            { name: 'Lead-uri', route: 'sales.clients.index', query: { status: 'lead' }, permissions: ['clients.view', 'clients.manage'] },
            { name: 'Oferte', route: 'sales.offers.index', permissions: ['offers.view', 'offers.manage'] },
            { name: 'Oferte acceptate', route: 'sales.offers.index', query: { status: 'accepted' }, permissions: ['offers.view', 'offers.manage'] },
            { name: 'Activitati', route: 'sales.activities.index', permissions: ['activities.view', 'activities.manage'] },
        ],
    },
    {
        key: 'shop',
        name: 'Magazin',
        icon: 'shopping-cart',
        roles: ['admin'],
        items: [
            { name: 'Comenzi magazin', route: 'admin.shop-orders.index' },
            { name: 'Reduceri magazin', route: 'admin.discounts.index' },
            { name: 'Produse in magazin', route: 'technical.equipment.index', query: { shop_visible: 1 } },
        ],
    },
    {
        key: 'technical',
        name: 'Tehnic',
        icon: 'wrench',
        roles: ['admin', 'tehnic', 'suport'],
        permissions: ['equipment.view', 'equipment.manage', 'installations.view', 'installations.manage', 'tickets.view', 'tickets.manage'],
        items: [
            { name: 'Echipamente', route: 'technical.equipment.index', permissions: ['equipment.view'] },
            { name: 'Furnizori / Import materiale', route: 'technical.suppliers.index', permissions: ['equipment.view'] },
            { name: 'Comenzi furnizori', route: 'technical.purchase-orders.index', permissions: ['equipment.view'] },
            { name: 'Servicii', route: 'technical.services.index', permissions: ['equipment.view'] },
            { name: 'Stoc scazut', route: 'technical.equipment.index', query: { low_stock: 1 }, permissions: ['equipment.view'] },
            { name: 'Instalari', route: 'technical.installations.index', permissions: ['installations.view'] },
            { name: 'Calendar programari', route: 'technical.installations.calendar', permissions: ['installations.view'] },
            { name: 'Tichete', route: 'technical.tickets.index', permissions: ['tickets.view'] },
        ],
    },
    {
        key: 'financial',
        name: 'Financiar',
        icon: 'credit-card',
        roles: ['admin'],
        items: [
            { name: 'Facturi', route: 'admin.invoices.index' },
            { name: 'Cheltuieli si achizitii', route: 'admin.expenses.index' },
            { name: 'Abonamente', route: 'admin.subscriptions.index' },
            { name: 'KPI / Rapoarte', route: 'admin.reports' },
            { name: 'Raport profit', route: 'admin.reports.profit' },
        ],
    },
    {
        key: 'content',
        name: 'Site & Continut',
        icon: 'globe',
        roles: ['admin'],
        items: [
            { name: 'Trafic & Analitica', route: 'admin.traffic.index' },
            { name: 'Pachete site', route: 'admin.site-packages.index' },
            { name: 'Pagini publice', route: 'admin.pages.index' },
            { name: 'Blog', route: 'admin.blog.index' },
            { name: 'Statistici', route: 'admin.stats.index' },
        ],
    },
    {
        key: 'system',
        name: 'Sistem',
        icon: 'shield-check',
        roles: ['admin'],
        items: [
            { name: 'Ghid utilizare', route: 'admin.help' },
            { name: 'Utilizatori', route: 'admin.users.index' },
            { name: 'Log SMS', route: 'admin.sms-logs' },
            { name: 'Jurnal audit', route: 'admin.audit-logs' },
            { name: 'Setari', route: 'admin.settings.edit' },
        ],
    },
];

function itemVisible(item, section) {
    return hasAccess(item.roles ?? section.roles, item.permissions ?? section.permissions);
}

const visibleSections = computed(() =>
    sections
        .filter((section) => hasAccess(section.roles, section.permissions))
        .map((section) => ({ ...section, items: section.items.filter((item) => itemVisible(item, section)) }))
);

function itemHref(item) {
    return route(item.route, item.query ?? {});
}

function isItemActive(item, section) {
    if (!route().current(item.route)) return false;

    const params = new URLSearchParams(window.location.search);

    if (item.query) {
        return Object.entries(item.query).every(([key, value]) => params.get(key) === String(value));
    }

    const siblingsWithQuery = section.items.filter((i) => i.route === item.route && i.query);
    if (!siblingsWithQuery.length) return true;

    return !siblingsWithQuery.some((sibling) =>
        Object.entries(sibling.query).every(([key, value]) => params.get(key) === String(value))
    );
}

function sectionHasActiveItem(section) {
    return section.items.some((item) => isItemActive(item, section));
}

// --- collapse / expand persistence ---
const collapsed = ref(localStorage.getItem('sidebar-collapsed') === '1');
function toggleCollapsed() {
    collapsed.value = !collapsed.value;
    localStorage.setItem('sidebar-collapsed', collapsed.value ? '1' : '0');
}

const openSections = reactive({});
visibleSections.value.forEach((section) => {
    const stored = localStorage.getItem(`sidebar-open-${section.key}`);
    openSections[section.key] = stored !== null ? stored === '1' : sectionHasActiveItem(section);
});

function toggleSection(key) {
    openSections[key] = !openSections[key];
    localStorage.setItem(`sidebar-open-${key}`, openSections[key] ? '1' : '0');
}

const mobileOpen = ref(false);
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-gray-100">
        <!-- Mobile overlay -->
        <div v-if="mobileOpen" class="fixed inset-0 z-40 bg-black/50 lg:hidden" @click="mobileOpen = false" />

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 flex flex-col bg-brand-navy transition-all duration-200 lg:static lg:flex"
            :class="[collapsed ? 'lg:w-20' : 'lg:w-64', mobileOpen ? 'w-64 translate-x-0' : 'w-64 -translate-x-full lg:translate-x-0']"
        >
            <div class="flex h-20 flex-shrink-0 items-center gap-2 border-b border-white/10 px-4">
                <Link :href="route('dashboard')" class="flex min-w-0 items-center gap-2">
                    <img
                        src="/branding/logo-trim.png"
                        alt="Om Priceput"
                        class="h-12 w-auto max-w-[190px] flex-shrink-0 object-contain"
                    />
                </Link>
                <button type="button" class="ml-auto text-slate-400 hover:text-white lg:hidden" @click="mobileOpen = false">
                    <Icon name="close" class="h-5 w-5" />
                </button>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-2 py-4">
                <Link
                    :href="route('dashboard')"
                    class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition"
                    :class="route().current('dashboard') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white'"
                >
                    <Icon name="dashboard" class="h-5 w-5 flex-shrink-0" />
                    <span v-if="!collapsed">Dashboard</span>
                </Link>

                <div v-for="section in visibleSections" :key="section.key" class="border-t border-white/5 pt-1">
                    <Link
                        v-if="collapsed"
                        :href="itemHref(section.items[0])"
                        :title="section.name"
                        class="flex w-full items-center justify-center rounded-md px-3 py-2 text-sm font-semibold transition"
                        :class="sectionHasActiveItem(section) ? 'text-white' : 'text-slate-200 hover:text-white'"
                    >
                        <Icon :name="section.icon" class="h-5 w-5 flex-shrink-0" />
                    </Link>
                    <button
                        v-else
                        type="button"
                        class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-semibold transition"
                        :class="sectionHasActiveItem(section) ? 'text-white' : 'text-slate-200 hover:text-white'"
                        @click="toggleSection(section.key)"
                    >
                        <Icon :name="section.icon" class="h-5 w-5 flex-shrink-0" />
                        <span class="flex-1 text-left">{{ section.name }}</span>
                        <Icon
                            :name="openSections[section.key] ? 'chevron-down' : 'chevron-right'"
                            class="h-4 w-4 flex-shrink-0 text-slate-400"
                        />
                    </button>
                    <div v-if="openSections[section.key] && !collapsed" class="ml-8 mt-1 space-y-1 pb-2">
                        <Link
                            v-for="item in section.items"
                            :key="item.name"
                            :href="itemHref(item)"
                            class="block rounded-md px-3 py-1.5 text-sm transition"
                            :class="isItemActive(item, section) ? 'font-medium text-orange-400' : 'text-slate-400 hover:text-white'"
                        >
                            {{ item.name }}
                        </Link>
                    </div>
                </div>
            </nav>

            <div class="flex-shrink-0 border-t border-white/10 p-2">
                <button
                    type="button"
                    class="mb-1 hidden w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-slate-300 transition hover:bg-white/5 hover:text-white lg:flex"
                    :title="collapsed ? 'Extinde meniul' : 'Restrange meniul'"
                    @click="toggleCollapsed"
                >
                    <Icon name="panel-left" class="h-5 w-5 flex-shrink-0" />
                    <span v-if="!collapsed">Restrange meniul</span>
                    <span v-else class="sr-only">Extinde meniul</span>
                </button>

                <div v-if="!collapsed" class="px-3 py-2 text-xs text-slate-400">
                    <div class="truncate font-medium text-slate-200">{{ page.props.auth.user.name }}</div>
                    <div class="truncate">{{ page.props.auth.user.email }}</div>
                </div>
                <Link
                    :href="route('profile.edit')"
                    class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-slate-300 transition hover:bg-white/5 hover:text-white"
                >
                    <Icon name="user" class="h-5 w-5 flex-shrink-0" />
                    <span v-if="!collapsed">Profil</span>
                </Link>
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-slate-300 transition hover:bg-white/5 hover:text-white"
                >
                    <Icon name="log-out" class="h-5 w-5 flex-shrink-0" />
                    <span v-if="!collapsed">Logout</span>
                </Link>
            </div>
        </aside>

        <!-- Main column -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <div class="flex h-16 flex-shrink-0 items-center gap-4 border-b border-gray-200 bg-white px-4 sm:px-6 lg:px-8">
                <button type="button" class="text-gray-500 hover:text-gray-700 lg:hidden" @click="mobileOpen = true">
                    <Icon name="menu" class="h-6 w-6" />
                </button>
                <div class="hidden flex-1 sm:block">
                    <GlobalSearch />
                </div>
                <Link
                    v-if="hasAccess(['admin'])"
                    :href="route('admin.help')"
                    class="hidden sm:flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100 transition"
                    title="Ghid de utilizare si configurare aplicatie"
                >
                    <Icon name="help-circle" class="h-4 w-4 text-blue-600" />
                    <span>Ghid utilizare</span>
                </Link>
                <button
                    v-if="page.props.auth.unreadNotifications"
                    type="button"
                    class="rounded-md bg-orange-50 px-3 py-2 text-sm font-medium text-orange-700 hover:bg-orange-100"
                    @click="router.patch(route('notifications.read-all'), {}, { preserveScroll: true })"
                >
                    {{ page.props.auth.unreadNotifications }} notificari noi
                </button>
            </div>

            <header v-if="$slots.header" class="flex-shrink-0 bg-white shadow-sm">
                <div class="px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <main class="flex-1 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
