<script setup lang="ts">
import type { useSeasonsPage } from '@/pages/crm/composables/useSeasonsPage';

type SeasonPage = ReturnType<typeof useSeasonsPage>;

defineProps<{
    selectBonusTitle: SeasonPage['selectBonusTitle'];
    selectService: SeasonPage['selectService'];
    state: SeasonPage['state'];
    switchType: SeasonPage['switchType'];
    t: SeasonPage['t'];
}>();
</script>

<template>
    <section class="season-toolbar">
        <div class="type-tabs">
            <button
                type="button"
                class="btn"
                :class="
                    state.selectedType === 'service'
                        ? 'btn-solid'
                        : 'btn-outline'
                "
                @click="switchType('service')"
            >
                {{ t('seasons.type_services') }}
            </button>
            <button
                type="button"
                class="btn"
                :class="
                    state.selectedType === 'bonus' ? 'btn-solid' : 'btn-outline'
                "
                @click="switchType('bonus')"
            >
                {{ t('seasons.type_bonus') }}
            </button>
        </div>

        <div class="season-filter">
            <select
                v-if="state.selectedType === 'service'"
                :value="state.selectedServiceId"
                class="form-control"
                @change="
                    selectService(
                        Number(($event.target as HTMLSelectElement).value),
                    )
                "
            >
                <option
                    v-for="service in state.overview?.services ?? []"
                    :key="service.id"
                    :value="service.id"
                >
                    {{ service.title }}
                </option>
            </select>

            <select
                v-else
                :value="state.selectedBonusTitle"
                class="form-control"
                @change="
                    selectBonusTitle(($event.target as HTMLSelectElement).value)
                "
            >
                <option
                    v-for="title in state.overview?.bonus_titles ?? []"
                    :key="title"
                    :value="title"
                >
                    {{ title }}
                </option>
            </select>
        </div>
    </section>

    <section v-if="state.selectedType === 'service'" class="service-tabs">
        <button
            v-for="service in state.overview?.services ?? []"
            :key="service.id"
            type="button"
            class="service-tab"
            :class="state.selectedServiceId === service.id ? 'is-active' : ''"
            @click="selectService(service.id)"
        >
            {{ service.title }}
        </button>
    </section>

    <section v-else class="service-tabs">
        <button
            v-for="title in state.overview?.bonus_titles ?? []"
            :key="title"
            type="button"
            class="service-tab"
            :class="state.selectedBonusTitle === title ? 'is-active' : ''"
            @click="selectBonusTitle(title)"
        >
            {{ title }}
        </button>
    </section>
</template>
