<script setup lang="ts">
import GermanDateInput from '@/components/GermanDateInput.vue';
import { useSeasonPageContext } from '@/pages/crm/composables/seasonPageContext';

const {
    selectedSeason,
    state,
    submitCreateSeason,
    submitDefaults,
    submitDeleteSeason,
    submitUpdateSeason,
    t,
} = useSeasonPageContext();
</script>

<template>
    <section class="season-admin-grid">
        <form class="season-admin-card" @submit.prevent="submitDefaults">
            <div class="card-head">
                <h3>{{ t('seasons.defaults_title') }}</h3>
            </div>
            <div class="form-grid season-defaults-grid">
                <div class="field">
                    <label for="default-start">{{
                        t('seasons.start_day_month')
                    }}</label>
                    <input
                        id="default-start"
                        v-model="state.defaultsForm.default_start_md"
                        type="text"
                        class="form-control"
                        placeholder="01-09"
                        required
                    />
                </div>
                <div class="field">
                    <label for="default-end">{{
                        t('seasons.end_day_month')
                    }}</label>
                    <input
                        id="default-end"
                        v-model="state.defaultsForm.default_end_md"
                        type="text"
                        class="form-control"
                        placeholder="31-08"
                        required
                    />
                </div>
            </div>
            <div class="form-actions season-actions">
                <button
                    type="submit"
                    class="btn btn-outline"
                    :disabled="state.submittingDefaults"
                >
                    {{
                        state.submittingDefaults
                            ? t('seasons.saving')
                            : t('seasons.save_defaults')
                    }}
                </button>
            </div>
        </form>

        <form class="season-admin-card" @submit.prevent="submitCreateSeason">
            <div class="card-head">
                <h3>{{ t('seasons.create_title') }}</h3>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label for="season-name-create">{{
                        t('seasons.season_name')
                    }}</label>
                    <input
                        id="season-name-create"
                        v-model="state.createForm.season_name"
                        type="text"
                        class="form-control"
                        :placeholder="t('seasons.name_example')"
                    />
                </div>
                <div class="field">
                    <label for="season-start-create">{{
                        t('seasons.start_date')
                    }}</label>
                    <GermanDateInput
                        id="season-start-create"
                        v-model="state.createForm.season_start"
                        class="form-control"
                        required
                    />
                </div>
                <div class="field">
                    <label for="season-end-create">{{
                        t('seasons.end_date')
                    }}</label>
                    <GermanDateInput
                        id="season-end-create"
                        v-model="state.createForm.season_end"
                        class="form-control"
                        required
                    />
                </div>
            </div>
            <div class="form-actions season-actions">
                <button
                    type="submit"
                    class="btn btn-solid"
                    :disabled="state.submittingCreate"
                >
                    {{
                        state.submittingCreate
                            ? t('seasons.adding')
                            : t('seasons.add_season')
                    }}
                </button>
            </div>
        </form>

        <form
            v-if="selectedSeason"
            class="season-admin-card"
            @submit.prevent="submitUpdateSeason"
        >
            <div class="card-head">
                <h3>{{ t('seasons.edit_title') }}</h3>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label for="season-name-edit">{{
                        t('seasons.season_name')
                    }}</label>
                    <input
                        id="season-name-edit"
                        v-model="state.editForm.season_name"
                        type="text"
                        class="form-control"
                    />
                </div>
                <div class="field">
                    <label for="season-start-edit">{{
                        t('seasons.start_date')
                    }}</label>
                    <GermanDateInput
                        id="season-start-edit"
                        v-model="state.editForm.season_start"
                        class="form-control"
                        required
                    />
                </div>
                <div class="field">
                    <label for="season-end-edit">{{
                        t('seasons.end_date')
                    }}</label>
                    <GermanDateInput
                        id="season-end-edit"
                        v-model="state.editForm.season_end"
                        class="form-control"
                        required
                    />
                </div>
            </div>
            <div class="form-actions season-actions">
                <button
                    type="submit"
                    class="btn btn-solid"
                    :disabled="state.submittingUpdate"
                >
                    {{
                        state.submittingUpdate
                            ? t('seasons.saving')
                            : t('seasons.save_season')
                    }}
                </button>
            </div>
        </form>

        <article
            v-if="selectedSeason"
            class="season-admin-card season-delete-card"
        >
            <div class="card-head">
                <h3>{{ t('seasons.delete_title') }}</h3>
            </div>
            <div class="filters-note">
                {{ t('seasons.delete_note') }}
            </div>
            <div class="form-actions season-actions">
                <button
                    type="button"
                    class="btn btn-danger"
                    :disabled="state.deletingSeason"
                    @click="submitDeleteSeason"
                >
                    {{
                        state.deletingSeason
                            ? t('seasons.deleting')
                            : t('seasons.delete_season')
                    }}
                </button>
            </div>
        </article>
    </section>
</template>
