import { inject, provide, type InjectionKey } from 'vue';

export interface StrictContext<T> {
    provideContext: (context: T) => void;
    useContext: () => T;
}

export function createStrictContext<T>(name: string): StrictContext<T> {
    const key = Symbol(name) as InjectionKey<T>;

    function provideContext(context: T): void {
        provide(key, context);
    }

    function useContext(): T {
        const context = inject(key);

        if (context === undefined) {
            throw new Error(`${name} was not provided.`);
        }

        return context;
    }

    return {
        provideContext,
        useContext,
    };
}
