<script setup lang="ts">
import { useBookingPageContext } from '@/pages/crm/composables/bookingPageContext';

const { onCardToggle, onEquipToggle, state, t } = useBookingPageContext();

defineProps<{
    isCardService: boolean;
    isEquipService: boolean;
}>();
</script>

<template>
    <template v-if="isCardService || isEquipService">
        <hr class="section-divider" />

        <div class="card-head">
            <h3>{{ t('bookings.options') }}</h3>
        </div>

        <div v-if="isCardService" class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <div class="checkbox">
                    <label>
                        <input
                            type="checkbox"
                            :checked="state.form.card_id > 0"
                            @change="onCardToggle"
                        />
                        {{ t('bookings.card') }}
                    </label>
                </div>
            </div>
        </div>

        <div v-if="isCardService && state.form.card_id > 0" class="form-group">
            <label class="col-sm-2 control-label">{{
                t('bookings.select_card')
            }}</label>
            <div class="col-sm-10">
                <select
                    v-model.number="state.form.card_id"
                    class="form-control"
                    :disabled="state.loadingSupport || state.cards.length === 0"
                >
                    <option
                        v-for="card in state.cards"
                        :key="card.id"
                        :value="card.id"
                    >
                        {{ card.title }}
                    </option>
                </select>
            </div>
        </div>

        <div v-if="isEquipService" class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <div class="checkbox">
                    <label>
                        <input
                            type="checkbox"
                            :checked="state.form.equip_enabled"
                            @change="onEquipToggle"
                        />
                        {{ t('bookings.equipment') }}
                    </label>
                </div>
            </div>
        </div>

        <div
            v-if="isEquipService && state.form.equip_enabled"
            class="form-group"
        >
            <label class="col-sm-2 control-label">{{
                t('bookings.select_equipment')
            }}</label>
            <div class="col-sm-10">
                <select
                    v-model.number="state.form.equip_id"
                    class="form-control"
                    :disabled="
                        state.loadingSupport || state.equips.length === 0
                    "
                >
                    <option
                        v-for="equip in state.equips"
                        :key="equip.id"
                        :value="equip.id"
                    >
                        {{ equip.title }}
                    </option>
                </select>
            </div>
        </div>
    </template>
</template>
