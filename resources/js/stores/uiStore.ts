import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useUiStore = defineStore('ui', () => {
    const isSidebarOpen = ref<boolean>(true);
    const isBusy = ref<boolean>(false);

    const setSidebarOpen = (value: boolean): void => {
        isSidebarOpen.value = value;
    };

    const setBusy = (value: boolean): void => {
        isBusy.value = value;
    };

    return {
        isSidebarOpen,
        isBusy,
        setSidebarOpen,
        setBusy,
    };
});
