export interface Customer {
  id: number;
  customer: string;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  phone_optional: string | null;
  account_number: string;
  website: string;
  notes: string;
  currency: string;
  billing_address: string;
  billing_address_optional: string | null;
  billing_address_country: string;
  billing_address_state: string;
  billing_address_city: string;
  billing_address_postal: string;
  ship_name: string;
  shipping_address: string;
  shipping_address_optional: string | null;
  shipping_address_country: string;
}

export interface Item {
  id: number;
  date: string;
  supplier: string | null;
  manufacturer: string;
  model: string;
  colour: string;
  battery: string;
  grade: string;
  issues: string | null;
  cost: number;
  imei: string | null;
  selling_price: number;
  sold: string | null;
  hold: string | null;
  sale_id: number | null;
  customer: string | null;
  discount: number | null;
  tax: number | null;
  subtotal: number | null;
  profit: number | null;
  created_at: string;
  updated_at: string;
  storage_id: number | null;
  position: number | null;
  sold_position: number | null;
  sold_storage_id: number | null;
  sold_storage_name: string | null;
  vendor_id: number;
  user_id: number;
  storage: {
    id: number;
    name: string;
    limit: number;
  } | null;
  vendor: {
    id: number;
    vendor: string;
  };
}

export interface Tab {
  id?: number;
  name: string;
  order: number;
}

export interface Contact {
  id: number;
  name: string;
  email: string;
  user_id: number;
  type: "customer" | "prospect";
  prospect_id: number | null;
  customer_id: number | null;
  created_at: string;
  updated_at: string;
}
