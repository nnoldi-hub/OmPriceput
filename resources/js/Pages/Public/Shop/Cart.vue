<script setup>
import SeoHead from '@/Components/SeoHead.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useCart } from '@/cart';

const props = defineProps({
    prefill: Object,
    freeShippingThreshold: Number,
    shippingCost: Number,
});

const { items, subtotal, updateQuantity, removeItem, clear } = useCart();

const shippingEstimate = computed(() => (subtotal.value >= props.freeShippingThreshold ? 0 : props.shippingCost));
const totalEstimate = computed(() => subtotal.value + shippingEstimate.value);

const form = useForm({
    name: props.prefill?.name ?? '',
    email: props.prefill?.email ?? '',
    phone: props.prefill?.phone ?? '',
    shipping_address: props.prefill?.shipping_address ?? '',
    shipping_city: props.prefill?.shipping_city ?? '',
    notes: '',
    wants_installation: false,
    payment_method: 'cod',
    items: [],
});

function submit() {
    form.items = items.value.map((item) => ({ equipment_id: item.equipment_id, quantity: item.quantity }));
    form.post(route('public.shop.checkout'), {
        onSuccess: () => clear(),
    });
}
</script>

<template>
    <SeoHead />

    <PublicLayout>
        <section class="mx-auto max-w-4xl px-4 py-16 sm:px-6 lg:px-8">
            <h1 class="font-display text-2xl font-bold text-slate-900">Cosul dumneavoastra</h1>

            <div v-if="!items.length" class="mt-8 rounded-xl border border-dashed border-slate-300 p-10 text-center text-slate-500">
                Cosul este gol. <Link :href="route('public.shop.index')" class="text-blue-600">Consultati produsele din magazin</Link>.
            </div>

            <template v-else>
                <div class="mt-8 overflow-x-auto rounded-xl border border-slate-200">
                    <table class="min-w-full divide-y">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs uppercase text-slate-500">Produs</th>
                                <th class="px-4 py-3 text-right text-xs uppercase text-slate-500">Pret</th>
                                <th class="px-4 py-3 text-center text-xs uppercase text-slate-500">Cantitate</th>
                                <th class="px-4 py-3 text-right text-xs uppercase text-slate-500">Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="item in items" :key="item.equipment_id">
                                <td class="px-4 py-3 font-medium text-slate-800">{{ item.name }}</td>
                                <td class="px-4 py-3 text-right">{{ item.shop_price.toFixed(2) }} lei</td>
                                <td class="px-4 py-3 text-center">
                                    <input
                                        type="number"
                                        min="1"
                                        class="w-16 rounded-lg border-slate-300 text-center text-sm"
                                        :value="item.quantity"
                                        @change="updateQuantity(item.equipment_id, Number($event.target.value))"
                                    />
                                </td>
                                <td class="px-4 py-3 text-right font-medium">{{ (item.shop_price * item.quantity).toFixed(2) }} lei</td>
                                <td class="px-4 py-3 text-right">
                                    <button class="text-xs text-red-600" @click="removeItem(item.equipment_id)">Elimina</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 ml-auto max-w-sm space-y-1 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span>{{ subtotal.toFixed(2) }} lei</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Transport</span><span>{{ shippingEstimate === 0 ? 'Gratuit' : shippingEstimate.toFixed(2) + ' lei' }}</span></div>
                    <div class="flex justify-between text-base font-bold text-slate-900"><span>Total</span><span>{{ totalEstimate.toFixed(2) }} lei</span></div>
                    <p v-if="shippingEstimate > 0" class="text-xs text-slate-400">Transport gratuit peste {{ freeShippingThreshold }} lei.</p>
                </div>

                <form class="mt-10 grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
                    <div class="sm:col-span-2"><h2 class="font-semibold text-slate-900">Date de livrare</h2></div>
                    <div>
                        <label class="text-sm text-slate-600">Nume complet</label>
                        <input v-model="form.name" type="text" class="mt-1 w-full rounded-lg border-slate-300" required />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-slate-600">Telefon</label>
                        <input v-model="form.phone" type="text" class="mt-1 w-full rounded-lg border-slate-300" required />
                        <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">{{ form.errors.phone }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-slate-600">Email (optional)</label>
                        <input v-model="form.email" type="email" class="mt-1 w-full rounded-lg border-slate-300" />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-slate-600">Oras</label>
                        <input v-model="form.shipping_city" type="text" class="mt-1 w-full rounded-lg border-slate-300" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm text-slate-600">Adresa de livrare</label>
                        <input v-model="form.shipping_address" type="text" class="mt-1 w-full rounded-lg border-slate-300" required />
                        <p v-if="form.errors.shipping_address" class="mt-1 text-xs text-red-600">{{ form.errors.shipping_address }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm text-slate-600">Observatii (optional)</label>
                        <textarea v-model="form.notes" rows="3" class="mt-1 w-full rounded-lg border-slate-300"></textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm text-slate-600">Metoda de plata</label>
                        <select v-model="form.payment_method" class="mt-1 w-full rounded-lg border-slate-300">
                            <option value="cod">Ramburs la livrare</option>
                            <option value="transfer">Transfer bancar</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2 sm:col-span-2">
                        <input id="wants_installation" v-model="form.wants_installation" type="checkbox" class="rounded border-slate-300" />
                        <label for="wants_installation" class="text-sm text-slate-600">Doresc montaj realizat de un meseras Om Priceput (se stabileste separat)</label>
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="w-full rounded-lg bg-brand-navy px-6 py-3 font-semibold text-white hover:bg-slate-800" :disabled="form.processing">
                            Trimite comanda
                        </button>
                    </div>
                </form>
            </template>
        </section>
    </PublicLayout>
</template>
