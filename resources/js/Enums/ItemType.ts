export enum ItemType {
  DEVICE            = 'device',
  ACCESSORY         = 'accessory',
  FEE             = 'fee',
  PAYPAL_FEE      = 'paypal_fee',
  SHIPPING_FEE    = 'shipping_fee',
  COMMISSION_FEE  = 'commission_fee',
}

export const ITEM_TYPE_OPTIONS = Object.values(ItemType).map(value => ({
  label: getItemTypeLabel(value),
  value,
}));

/**
 * Devuelve la label capitalizada de un tipo
 */
export function getItemTypeLabel(value: string | ItemType): string {
  return (value as string)
    .replace(/_/g, ' ')
    .split(' ')
    .map(w => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ');
}