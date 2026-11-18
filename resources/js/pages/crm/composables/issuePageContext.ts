import { createStrictContext } from '@/composables/createStrictContext';
import type { useIssuesPage } from '@/pages/crm/composables/useIssuesPage';

export type IssuePageContext = ReturnType<typeof useIssuesPage>;

const issuePageContext =
    createStrictContext<IssuePageContext>('Issue page context');

export const provideIssuePageContext = issuePageContext.provideContext;
export const useIssuePageContext = issuePageContext.useContext;
