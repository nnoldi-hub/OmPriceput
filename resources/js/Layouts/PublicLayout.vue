<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useCart } from '@/cart';

const page = usePage();
const mobileOpen = ref(false);
const { itemCount } = useCart();

const nav = computed(() => {
    const items = [
        { name: 'Acasa', href: () => route('public.home') },
        { name: 'Despre noi', href: () => route('public.about') },
        { name: 'Servicii & pachete', href: () => route('public.services') },
        { name: 'Cerere de deviz', href: () => route('public.quote') },
    ];

    if (page.props.siteSettings?.shop_enabled === '1') {
        items.push({ name: 'Magazin', href: () => route('public.shop.index') });
    }

    items.push({ name: 'Blog', href: () => route('public.blog.index') });
    items.push({ name: 'Contact', href: () => route('public.contact') });

    return items;
});

const currentYear = new Date().getFullYear();
const settings = page.props.siteSettings ?? {};
const companyName = settings.company_name || 'Om Priceput';
const companyAddress = settings.company_address || 'Str. Petre Ionel nr. 205, Branesti, Ilfov, 077030';
const mapSrc = `https://maps.google.com/maps?q=${encodeURIComponent(companyAddress)}&z=15&output=embed`;
</script>

<template>
    <div class="flex min-h-screen flex-col bg-white">
        <header class="sticky top-0 z-30 border-b border-slate-700/80 bg-[#021a2d]">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                <Link :href="route('public.home')" class="flex items-center gap-2 text-white">
    <span class="inline-flex items-center rounded-xl bg-white px-2 py-1">
        <img src="/branding/logo-trim.png" :alt="companyName" class="h-10 w-auto max-w-[200px] object-contain sm:h-12" />
    </span>
</Link>

                <div class="hidden items-center gap-7 lg:flex">
                    <Link
                        v-for="item in nav"
                        :key="item.name"
                        :href="item.href()"
                        class="text-sm font-medium text-slate-300 transition hover:text-white"
                    >
                        {{ item.name }}
                    </Link>
                </div>

                <div class="hidden items-center gap-3 lg:flex">
                    <Link
                        v-if="page.props.siteSettings?.shop_enabled === '1'"
                        :href="route('public.shop.cart')"
                        class="relative rounded-md px-3 py-2 text-sm font-medium text-slate-300 hover:text-white"
                    >
                        Cos
                        <span v-if="itemCount > 0" class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-orange-500 text-xs font-bold text-white">{{ itemCount }}</span>
                    </Link>
                    <Link
                        v-if="page.props.auth.user"
                        :href="page.props.auth.roles?.includes('client') || page.props.auth.roles?.includes('client-manager') ? route('client.dashboard') : route('dashboard')"
                        class="rounded-md px-3 py-2 text-sm font-medium text-slate-300 hover:text-white"
                    >
                        Contul meu
                    </Link>
                    <Link
                        v-else
                        :href="route('login')"
                        class="rounded-md px-3 py-2 text-sm font-medium text-slate-300 hover:text-white"
                    >
                        Autentificare
                    </Link>
                    <Link
                        :href="route('public.quote')"
                        class="rounded-md bg-orange-500 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-orange-500/25 transition hover:bg-orange-400"
                    >
                        Cereti un deviz
                    </Link>
                </div>

                <button
                    type="button"
                    class="text-slate-300 lg:hidden"
                    @click="mobileOpen = !mobileOpen"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            v-if="!mobileOpen"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                        <path
                            v-else
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </nav>

            <div v-if="mobileOpen" class="border-t border-slate-800 px-4 pb-4 lg:hidden">
                <Link
                    v-for="item in nav"
                    :key="item.name"
                    :href="item.href()"
                    class="block py-2 text-sm font-medium text-slate-300 hover:text-white"
                    @click="mobileOpen = false"
                >
                    {{ item.name }}
                </Link>
                <Link
                    v-if="page.props.siteSettings?.shop_enabled === '1'"
                    :href="route('public.shop.cart')"
                    class="block py-2 text-sm font-medium text-slate-300 hover:text-white"
                    @click="mobileOpen = false"
                >
                    Cos ({{ itemCount }})
                </Link>
                <Link
                    v-if="page.props.auth.user"
                    :href="page.props.auth.roles?.includes('client') || page.props.auth.roles?.includes('client-manager') ? route('client.dashboard') : route('dashboard')"
                    class="block py-2 text-sm font-medium text-slate-300 hover:text-white"
                >
                    Contul meu
                </Link>
                <Link
                    v-else
                    :href="route('login')"
                    class="block py-2 text-sm font-medium text-slate-300 hover:text-white"
                >
                    Autentificare
                </Link>
                <Link
                    :href="route('public.quote')"
                    class="mt-2 block rounded-md bg-orange-500 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-orange-400"
                >
                    Cereti un deviz
                </Link>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t border-slate-800 bg-brand-navy text-slate-400">
            <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-10 lg:grid-cols-2">
                    <div class="grid grid-cols-1 gap-8 sm:grid-cols-3">
                        <div>
    <div class="flex items-center gap-2 text-white">
        <span class="inline-flex items-center rounded-lg bg-white px-2 py-1">
            <img src="/branding/logo-trim.png" :alt="companyName" class="h-10 w-auto max-w-[160px] object-contain" />
        </span>
    </div>
    <p class="mt-3 text-sm">
        Reparatii, montaje si intretinere pentru casa si apartament.
        Executam personal lucrarile sau implicam meseriasul potrivit.
    </p>
</div>
                        <div>
                            <h3 class="text-sm font-semibold text-white">Navigare</h3>
                            <ul class="mt-3 space-y-2 text-sm">
                                <li v-for="item in nav" :key="item.name">
                                    <Link :href="item.href()" class="hover:text-white">{{ item.name }}</Link>
                                </li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-white">Contact</h3>
                            <ul class="mt-3 space-y-2 text-sm">
                                <li>Telefon: {{ settings.company_phone || '0700 000 000' }}</li>
                                <li>Email: {{ settings.company_email || 'contact@ompriceput.test' }}</li>
                                <li>Program de lucru: {{ settings.company_hours || 'Luni - Vineri, 09:00 - 18:00' }}</li>
                                <li>{{ companyAddress }}</li>
                                <li class="flex gap-3 pt-2">
                                    <a v-if="settings.social_facebook" :href="settings.social_facebook" target="_blank" rel="noopener" class="hover:text-white">Facebook</a>
                                    <a v-if="settings.social_instagram" :href="settings.social_instagram" target="_blank" rel="noopener" class="hover:text-white">Instagram</a>
                                    <a v-if="settings.social_linkedin" :href="settings.social_linkedin" target="_blank" rel="noopener" class="hover:text-white">LinkedIn</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="overflow-hidden rounded-xl border border-slate-800">
                        <iframe
                            :src="mapSrc"
                            class="h-56 w-full border-0"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                        />
                    </div>
                </div>
                <div class="mt-8 border-t border-slate-800 pt-6 text-xs">
                    &copy; {{ currentYear }} {{ companyName }}. Toate drepturile rezervate.
                </div>
            </div>
        </footer>
    </div>
</template>
