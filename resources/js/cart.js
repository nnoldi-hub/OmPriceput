import { reactive, watch, computed } from 'vue';

const STORAGE_KEY = 'op_shop_cart';

function loadInitial() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        return raw ? JSON.parse(raw) : [];
    } catch (e) {
        return [];
    }
}

const state = reactive({
    items: loadInitial(),
});

watch(
    () => state.items,
    (items) => {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
        } catch (e) {
            /* ignore storage errors */
        }
    },
    { deep: true }
);

export function useCart() {
    function addItem(product, quantity = 1) {
        const existing = state.items.find((item) => item.equipment_id === product.id);
        if (existing) {
            existing.quantity += quantity;
        } else {
            state.items.push({
                equipment_id: product.id,
                name: product.name,
                unit_price: product.unit_price,
                shop_price: product.shop_price,
                image_path: product.image_path,
                quantity,
            });
        }
    }

    function updateQuantity(equipmentId, quantity) {
        const item = state.items.find((item) => item.equipment_id === equipmentId);
        if (item) {
            item.quantity = Math.max(1, quantity);
        }
    }

    function removeItem(equipmentId) {
        const index = state.items.findIndex((item) => item.equipment_id === equipmentId);
        if (index !== -1) {
            state.items.splice(index, 1);
        }
    }

    function clear() {
        state.items.splice(0, state.items.length);
    }

    const itemCount = computed(() => state.items.reduce((total, item) => total + item.quantity, 0));
    const subtotal = computed(() => state.items.reduce((total, item) => total + item.shop_price * item.quantity, 0));

    return {
        items: computed(() => state.items),
        itemCount,
        subtotal,
        addItem,
        updateQuantity,
        removeItem,
        clear,
    };
}
