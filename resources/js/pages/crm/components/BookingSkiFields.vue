<script setup lang="ts">
import { useBookingPageContext } from '@/pages/crm/composables/bookingPageContext';

defineProps<{
    isSkiService: boolean;
}>();

const { state, t } = useBookingPageContext();
</script>

<template>
    <div v-if="isSkiService" id="skill_div" class="form-group">
        <label class="col-sm-2 control-label">{{
            t('bookings.customer_level')
        }}</label>
        <div class="col-sm-10">
            <div class="row">
                <div class="col-sm-3">
                    <label class="radio-inline">
                        <input
                            v-model.number="state.form.skill_id"
                            type="radio"
                            name="skill"
                            :value="1"
                        />
                        {{ t('bookings.level_1') }}
                    </label>
                </div>
                <div class="col-sm-3">
                    <label class="radio-inline">
                        <input
                            v-model.number="state.form.skill_id"
                            type="radio"
                            name="skill"
                            :value="2"
                        />
                        {{ t('bookings.level_2') }}
                    </label>
                </div>
                <div class="col-sm-3">
                    <label class="radio-inline">
                        <input
                            v-model.number="state.form.skill_id"
                            type="radio"
                            name="skill"
                            :value="3"
                        />
                        {{ t('bookings.level_3') }}
                    </label>
                </div>
                <div class="col-sm-3">
                    <label class="radio-inline">
                        <input
                            v-model.number="state.form.skill_id"
                            type="radio"
                            name="skill"
                            :value="4"
                        />
                        {{ t('bookings.level_4') }}
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div v-if="isSkiService" id="no_equip_extra_div" class="form-group">
        <label class="col-sm-2 control-label">{{
            t('bookings.shoe_size')
        }}</label>
        <div class="col-sm-3">
            <input
                v-model.number="state.form.ski_shoe_size"
                type="number"
                min="1"
                class="form-control"
            />
        </div>
        <label class="col-sm-2 control-label">{{ t('bookings.height') }}</label>
        <div class="col-sm-5">
            <input
                v-model.number="state.form.ski_height"
                type="number"
                min="1"
                class="form-control"
            />
        </div>
    </div>
</template>
