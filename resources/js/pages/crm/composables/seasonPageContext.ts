import { createStrictContext } from '@/composables/createStrictContext';
import type { useSeasonsPage } from '@/pages/crm/composables/useSeasonsPage';

export type SeasonPageContext = ReturnType<typeof useSeasonsPage>;

const seasonPageContext = createStrictContext<SeasonPageContext>(
    'Season page context',
);

export const provideSeasonPageContext = seasonPageContext.provideContext;
export const useSeasonPageContext = seasonPageContext.useContext;
