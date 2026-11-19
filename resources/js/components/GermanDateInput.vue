<script setup lang="ts">
import { useUiI18n } from '@/composables/useUiI18n';
import {
    dateInputToIso,
    formatGermanDate,
} from '@/modules/calendar/dateDisplay';
import { CalendarDays } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

defineOptions({ inheritAttrs: false });
defineProps<{ disabled?: boolean }>();

const model = defineModel<string>({ required: true });
const { t } = useUiI18n();
const textInput = ref<HTMLInputElement | null>(null);
const datePicker = ref<HTMLInputElement | null>(null);
const text = ref('');
const invalid = ref(false);
const canOpenPicker = computed(
    () => typeof datePicker.value?.showPicker === 'function',
);

watch(
    model,
    (value) => {
        text.value = formatGermanDate(value, '');
        invalid.value = false;
        textInput.value?.setCustomValidity('');
    },
    { immediate: true },
);

function commit(value: string): void {
    text.value = value;
    const iso = value.trim() === '' ? '' : dateInputToIso(value);
    invalid.value = iso === null;
    textInput.value?.setCustomValidity(
        invalid.value ? t('common.invalid_date_format') : '',
    );

    if (iso !== null) model.value = iso;
}

function canonicalize(): void {
    if (!invalid.value) text.value = formatGermanDate(model.value, '');
}
</script>

<template>
    <div class="date-input">
        <input
            v-bind="$attrs"
            ref="textInput"
            :value="text"
            :disabled="disabled"
            :aria-invalid="invalid || undefined"
            type="text"
            autocomplete="off"
            placeholder="DD.MM.YYYY"
            maxlength="10"
            @input="commit(($event.target as HTMLInputElement).value)"
            @blur="canonicalize"
        />
        <input
            ref="datePicker"
            class="native-date-picker"
            type="date"
            lang="de-DE"
            :value="model"
            :disabled="disabled"
            aria-hidden="true"
            tabindex="-1"
            @input="
                commit(($event.target as HTMLInputElement).value);
                canonicalize();
            "
        />
        <button
            v-if="canOpenPicker"
            type="button"
            class="date-picker-button"
            :disabled="disabled"
            :aria-label="t('common.open_date_picker')"
            @click="datePicker?.showPicker()"
        >
            <CalendarDays aria-hidden="true" class="size-4" />
        </button>
    </div>
</template>

<style scoped>
.date-input {
    position: relative;
    display: flex;
    align-items: stretch;
    gap: 0.25rem;
    min-width: 0;
}

.date-input > input[type='text'] {
    width: 100%;
    min-width: 0;
}

.native-date-picker {
    position: absolute;
    inset: auto 0 0 auto;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none;
}

.date-picker-button {
    display: inline-flex;
    flex: none;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    border: 1px solid var(--input);
    border-radius: 0.5rem;
    background: var(--background);
    color: var(--muted-foreground);
}

.date-picker-button:hover:not(:disabled) {
    color: var(--foreground);
    background: var(--muted);
}
</style>
