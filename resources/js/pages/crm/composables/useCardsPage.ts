import {
    createCard,
    deleteCard,
    listCards,
    updateCard,
} from '@/modules/catalog/catalogService';
import type {
    CardCatalogPayload,
    CardCatalogRecord,
} from '@/modules/catalog/types';
import { useCatalogPage } from './useCatalogPage';

export function useCardsPage() {
    const page = useCatalogPage<CardCatalogRecord, CardCatalogPayload>({
        deepLinkKeys: ['card', 'q'],
        listRows: listCards,
        createRow: createCard,
        updateRow: updateCard,
        deleteRow: deleteCard,
        messages: {
            confirmDelete: 'cards.confirm_delete',
            deepLinkBody: 'cards.deep_link_info_body',
            deepLinkTitle: 'cards.deep_link_info_title',
            errorCreate: 'cards.error_create',
            errorDelete: 'cards.error_delete',
            errorLoad: 'cards.error_load',
            errorLoadServices: 'cards.error_load_services',
            errorUpdate: 'cards.error_update',
            modalAdd: 'cards.modal_add',
            modalEdit: 'cards.modal_edit',
            summaryEmpty: 'cards.summary_empty',
            summaryTotal: 'cards.summary_total',
        },
    });

    return {
        ...page,
        loadCards: page.loadRows,
        removeCard: page.removeRow,
        submitCard: page.submitRow,
    };
}
