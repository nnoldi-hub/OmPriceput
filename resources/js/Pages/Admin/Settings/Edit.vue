<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    settings: Object,
});

const page = usePage();

const form = useForm({
    company_name: props.settings.company_name,
    company_email: props.settings.company_email,
    company_phone: props.settings.company_phone,
    company_address: props.settings.company_address,
    company_hours: props.settings.company_hours,
    social_facebook: props.settings.social_facebook,
    social_instagram: props.settings.social_instagram,
    social_linkedin: props.settings.social_linkedin,
    invoice_series: props.settings.invoice_series,
    vat_percentage: Number(props.settings.vat_percentage),
    minimum_profit_margin: Number(props.settings.minimum_profit_margin),
    operational_reminders_email_enabled: props.settings.operational_reminders_email_enabled === '1',
    shop_enabled: props.settings.shop_enabled === '1',
    shop_free_shipping_threshold: Number(props.settings.shop_free_shipping_threshold),
    shop_shipping_cost: Number(props.settings.shop_shipping_cost),
    google_analytics_id: props.settings.google_analytics_id ?? '',
    google_tag_manager_id: props.settings.google_tag_manager_id ?? '',
    meta_pixel_id: props.settings.meta_pixel_id ?? '',
    pricing_area_m2: Number(props.settings.pricing_area_m2),
    pricing_perimeter_ml: Number(props.settings.pricing_perimeter_ml),
});

function submit() {
    form.put(route('admin.settings.update'));
}
</script>

<template>
    <Head title="Setari generale" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Setari generale</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div v-if="page.props.flash.success" class="mb-4 rounded-md bg-green-50 p-4 text-sm font-medium text-green-800">
                    {{ page.props.flash.success }}
                </div>

                <form class="space-y-6 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">Nume firma *</label>
                            <input v-model="form.company_name" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            <p v-if="form.errors.company_name" class="mt-1 text-sm text-red-600">{{ form.errors.company_name }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">Program *</label>
                            <input v-model="form.company_hours" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div class="sm:col-span-2 border-t border-slate-200 pt-5">
                            <h3 class="text-sm font-semibold text-slate-900">Retele sociale</h3>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Facebook</label>
                            <input v-model="form.social_facebook" type="url" placeholder="https://facebook.com/..." class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Instagram</label>
                            <input v-model="form.social_instagram" type="url" placeholder="https://instagram.com/..." class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">LinkedIn</label>
                            <input v-model="form.social_linkedin" type="url" placeholder="https://linkedin.com/..." class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Email firma *</label>
                            <input v-model="form.company_email" type="email" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Telefon firma *</label>
                            <input v-model="form.company_phone" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">Adresa firma</label>
                            <input v-model="form.company_address" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Serie facturi *</label>
                            <input v-model="form.invoice_series" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">TVA (%) *</label>
                            <input v-model.number="form.vat_percentage" type="number" min="0" max="100" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Marja minima profit (%)</label>
                            <input v-model.number="form.minimum_profit_margin" type="number" min="0" max="100" step="0.01" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                            <p class="mt-1 text-xs text-slate-500">Pragul pentru avertizarea ofertelor cu profit redus.</p>
                        </div>
                        <div class="sm:col-span-2 border-t border-slate-200 pt-5">
                            <h3 class="text-sm font-semibold text-slate-900">Notificari</h3>
                        </div>
                        <div class="sm:col-span-2 flex items-start gap-3">
                            <input
                                id="operational_reminders_email_enabled"
                                v-model="form.operational_reminders_email_enabled"
                                type="checkbox"
                                class="mt-1 rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500"
                            />
                            <label for="operational_reminders_email_enabled" class="text-sm text-slate-700">
                                Trimite si pe email notificarile operationale (facturi restante, programari de maine, stoc sub prag)
                                <p class="mt-1 text-xs text-slate-500">Notificarile raman intotdeauna vizibile in aplicatie; aceasta optiune trimite suplimentar un email catre utilizatorii vizati.</p>
                            </label>
                        </div>
                        <div class="sm:col-span-2 border-t border-slate-200 pt-5">
                            <h3 class="text-sm font-semibold text-slate-900">Magazin online</h3>
                        </div>
                        <div class="sm:col-span-2 flex items-start gap-3">
                            <input
                                id="shop_enabled"
                                v-model="form.shop_enabled"
                                type="checkbox"
                                class="mt-1 rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500"
                            />
                            <label for="shop_enabled" class="text-sm text-slate-700">
                                Activeaza magazinul online
                                <p class="mt-1 text-xs text-slate-500">Cand este dezactivat, pagina de magazin si linkul din meniu nu sunt vizibile public.</p>
                            </label>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Prag transport gratuit (lei)</label>
                            <input v-model.number="form.shop_free_shipping_threshold" type="number" min="0" step="0.01" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Cost livrare sub prag (lei)</label>
                            <input v-model.number="form.shop_shipping_cost" type="number" min="0" step="0.01" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                        </div>
                        <div class="sm:col-span-2 border-t border-slate-200 pt-5">
                            <h3 class="text-sm font-semibold text-slate-900">Tarife masuratori</h3>
                            <p class="mt-1 text-xs text-slate-500">Preturile de estimare folosite la calculul ofertelor generate din masuratori.</p>
                            <Link :href="route('admin.pricing.index')" class="mt-2 inline-block text-sm font-semibold text-blue-600 hover:text-blue-500">Configureaza nomenclatorul de preturi pe tip (priza, traseu, tablou, suport) &rarr;</Link>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Pret estimare pe mp (lei)</label>
                            <input v-model.number="form.pricing_area_m2" type="number" min="0" step="0.01" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                            <p v-if="form.errors.pricing_area_m2" class="mt-1 text-sm text-red-600">{{ form.errors.pricing_area_m2 }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Pret estimare pe ml (lei)</label>
                            <input v-model.number="form.pricing_perimeter_ml" type="number" min="0" step="0.01" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" />
                            <p v-if="form.errors.pricing_perimeter_ml" class="mt-1 text-sm text-red-600">{{ form.errors.pricing_perimeter_ml }}</p>
                        </div>
                        <div class="sm:col-span-2 border-t border-slate-200 pt-5">
                            <h3 class="text-sm font-semibold text-slate-900">Integrari Marketing & Analitica (Google & Facebook)</h3>
                            <p class="mt-1 text-xs text-slate-500">Adauga ID-urile de urmarire pentru a activa automat scripturile pe paginile publice.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Google Analytics GA4 ID</label>
                            <input v-model="form.google_analytics_id" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" placeholder="Ex: G-XXXXXXXXXX" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Google Tag Manager ID</label>
                            <input v-model="form.google_tag_manager_id" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" placeholder="Ex: GTM-XXXXXXX" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">Meta (Facebook) Pixel ID</label>
                            <input v-model="form.meta_pixel_id" type="text" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" placeholder="Ex: 123456789012345" />
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50">
                            Salveaza setarile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
