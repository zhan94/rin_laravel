import {
    createEquip,
    deleteEquip,
    listEquips,
    updateEquip,
} from '@/modules/catalog/catalogService';
import type {
    EquipCatalogPayload,
    EquipCatalogRecord,
} from '@/modules/catalog/types';
import { useCatalogPage } from './useCatalogPage';

export function useEquipsPage() {
    const page = useCatalogPage<EquipCatalogRecord, EquipCatalogPayload>({
        deepLinkKeys: ['equip', 'q'],
        listRows: listEquips,
        createRow: createEquip,
        updateRow: updateEquip,
        deleteRow: deleteEquip,
        messages: {
            confirmDelete: 'equipment.confirm_delete',
            deepLinkBody: 'equipment.deep_link_body',
            deepLinkTitle: 'equipment.deep_link_title',
            errorCreate: 'equipment.error_create',
            errorDelete: 'equipment.error_delete',
            errorLoad: 'equipment.error_load',
            errorLoadServices: 'equipment.error_load_services',
            errorUpdate: 'equipment.error_update',
            modalAdd: 'equipment.modal_add',
            modalEdit: 'equipment.modal_edit',
            summaryEmpty: 'equipment.summary_empty',
            summaryTotal: 'equipment.summary_total',
        },
    });

    return {
        ...page,
        loadEquips: page.loadRows,
        removeEquip: page.removeRow,
        submitEquip: page.submitRow,
    };
}
