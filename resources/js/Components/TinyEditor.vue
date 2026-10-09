<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

import tinymce from 'tinymce/tinymce';
import 'tinymce/models/dom/model.min.js';
import 'tinymce/themes/silver/theme.min.js';
import 'tinymce/icons/default/icons.min.js';
import 'tinymce/skins/ui/oxide/skin.js';
import 'tinymce/skins/ui/oxide/content.js';
import 'tinymce/skins/content/default/content.js';
import 'tinymce/plugins/advlist/plugin.min.js';
import 'tinymce/plugins/anchor/plugin.min.js';
import 'tinymce/plugins/autolink/plugin.min.js';
import 'tinymce/plugins/charmap/plugin.min.js';
import 'tinymce/plugins/code/plugin.min.js';
import 'tinymce/plugins/fullscreen/plugin.min.js';
import 'tinymce/plugins/image/plugin.min.js';
import 'tinymce/plugins/insertdatetime/plugin.min.js';
import 'tinymce/plugins/link/plugin.min.js';
import 'tinymce/plugins/lists/plugin.min.js';
import 'tinymce/plugins/media/plugin.min.js';
import 'tinymce/plugins/preview/plugin.min.js';
import 'tinymce/plugins/searchreplace/plugin.min.js';
import 'tinymce/plugins/table/plugin.min.js';
import 'tinymce/plugins/visualblocks/plugin.min.js';
import 'tinymce/plugins/wordcount/plugin.min.js';

const props = defineProps({
    modelValue: { type: String, default: '' },
    height: { type: Number, default: 640 },
    uploadUrl: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'media']);

const textarea = ref(null);
let editor = null;
let ready = false;

function syncValue() {
    if (editor && ready) {
        emit('update:modelValue', editor.getContent());
    }
}

function insertImage(url, alt = '') {
    if (!editor || !url) return;
    editor.insertContent(`<img src="${url}" alt="${String(alt).replace(/"/g, '&quot;')}" />`);
    syncValue();
}

defineExpose({ insertImage, sync: syncValue });

onMounted(() => {
    tinymce.init({
        target: textarea.value,
        license_key: 'gpl',
        promotion: false,
        branding: false,
        height: props.height,
        menubar: false,
        statusbar: true,
        plugins: 'advlist anchor autolink charmap code fullscreen image insertdatetime link lists media preview searchreplace table visualblocks wordcount',
        toolbar:
            'undo redo | blocks | bold italic underline | '
            + 'alignleft aligncenter alignright alignjustify | '
            + 'bullist numlist outdent indent | link image media medialibrary | '
            + 'blockquote table | code preview fullscreen',
        block_formats: 'Paragraf=p; Titlu 2=h2; Titlu 3=h3; Titlu 4=h4; Citat=blockquote',
        content_css: 'default',
        content_style: 'body { font-family: Inter, system-ui, sans-serif; font-size: 16px; line-height: 1.7; } img { max-width: 100%; height: auto; }',
        valid_elements: '*[*]',
        images_upload_handler: props.uploadUrl
            ? (blobInfo) => new Promise((resolve, reject) => {
                const data = new FormData();
                data.append('file', blobInfo.blob(), blobInfo.filename());

                window.axios.post(props.uploadUrl, data)
                    .then((response) => resolve(response.data.location))
                    .catch(() => reject({ message: 'Incarcarea imaginii a esuat.', remove: true }));
            })
            : undefined,
        setup(instance) {
            instance.ui.registry.addButton('medialibrary', {
                icon: 'image',
                tooltip: 'Insereaza din biblioteca media',
                onAction: () => emit('media'),
            });

            instance.on('change keyup undo redo input', syncValue);
        },
        init_instance_callback: (instance) => {
            editor = instance;

            const initial = props.modelValue || '';

            if (instance.getContent() !== initial) {
                instance.setContent(initial);
            }

            ready = true;
        },
    });
});

watch(
    () => props.modelValue,
    (value) => {
        if (!ready || !editor || value === editor.getContent()) {
            return;
        }

        editor.setContent(value || '');
    },
);

onBeforeUnmount(() => {
    ready = false;
    editor?.remove();
    editor = null;
});
</script>

<template>
    <div class="tinymce-host">
        <textarea ref="textarea"></textarea>
    </div>
</template>

<style>
.tinymce-host .tox-tinymce {
    border-radius: 0.5rem;
    border-color: #cbd5e1;
}
</style>
