import type { BonusRecord } from '@/modules/bonus/types';
import type { ServiceDefinition } from '@/modules/services/types';

export interface BookingServiceOption {
    id: number;
    title: string;
}

export interface BookingHeroCopy {
    eyebrow: string;
    title: string;
    subtitle: string;
}

export interface BookingHeroLabels {
    stepOne: BookingHeroCopy;
    stepTwo: BookingHeroCopy;
}

export function isServiceBookingType(serviceType: number): boolean {
    return serviceType === 1;
}

export function isSkiServiceBooking(
    serviceType: number,
    serviceId: number,
): boolean {
    return isServiceBookingType(serviceType) && [1, 11].includes(serviceId);
}

export function isEquipServiceBooking(
    serviceType: number,
    serviceId: number,
): boolean {
    return isServiceBookingType(serviceType) && serviceId === 1;
}

export function isCardServiceBooking(
    serviceType: number,
    serviceId: number,
): boolean {
    return isServiceBookingType(serviceType) && serviceId === 1;
}

export function buildActiveBookingServiceOptions(
    serviceType: number,
    services: ServiceDefinition[],
    bonusServices: BonusRecord[],
): BookingServiceOption[] {
    if (serviceType === 2) {
        return bonusServices.map((bonus) => ({
            id: bonus.id,
            title: bonus.title,
        }));
    }

    return services.map((service) => ({
        id: service.id,
        title: service.title,
    }));
}

export function buildBookingHeroCopy(
    createStep: number,
    labels: BookingHeroLabels,
): BookingHeroCopy {
    return createStep === 2 ? labels.stepTwo : labels.stepOne;
}
