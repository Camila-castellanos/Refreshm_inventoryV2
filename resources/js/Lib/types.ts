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

export interface MailingList {
  id: number;
  title: string;
  names: string;
  emails: string;
  user_id: number;
  created_at: string;
  updated_at: string;
}
export interface Payment {
  id: number;
  sale_id: number;
  amount_paid: string;
  balance_remaining: string;
  payment_method: string;
  payment_account: string;
  payment_date: string;
  created_at: string;
  updated_at: string;
  date: string;
  paid: string;
}

export interface EmailTemplate {
  id: number;
  name: string;
  subject: string;
  body: string;
}

export interface PaymentResponse {
  id: number;
  date: string;
  customer: string;
  returned_items: any[];
  credited_items: any[];
  customer_id: string;
  customer_credit: number;
  customer_email: any;
  credit: any;
  total: string;
  amount_paid: string;
  balance_remaining: string;
  status: string;
  payments: Payment[];
  sale_id: number;
  payment_method: string;
  payment_account: string;
  tax: string;
  tax_id: number;
  discount: string;
  notes: any;
  sale_date: string;
}

export interface Expense {
  id?: number;
  date?: string | null | Date;
  name?: string;
  category?: string;
  subtotal?: number;
  user_id?: number;
  created_at?: string;
  updated_at?: string;
  tax?: number | null;
  total?: string | number;
  tax_id?: number;
}

export interface Tax {
  id: number;
  name: string;
  percentage: number;
  collected: number;
  paid: number;
  total_sales: number;
  total_purchases: number;
}

export interface Vendor {
  id: number;
  vendor: string;
  user_id: number;
  first_name: any;
  last_name: any;
  email: any;
  phone: any;
  phone_optional: any;
  website: any;
  notes: any;
  currency: any;
  address: any;
  address_optional: any;
  address_country: any;
  address_state: any;
  address_city: any;
  address_postal: any;
  created_at: string;
  updated_at: string;
  vendor_name: string;
}

export interface Bill {
  id: number;
  status: number;
  date: string;
  vendor: string;
  total: string;
  amount_paid: string;
  balance_remaining: string;
  user_id: number;
  vendor_id: string;
  subtotal: string;
  tax: string;
  flat_tax: string;
  invoice: any;
  tax_id: any;
  created_at: string;
  updated_at: string;
  payments: any[];
}

export interface Store {
  id: number;
  name: string;
  address: string;
  email: string;
  price_percent: number;
  deleted_at: any;
  header: string;
  footer: string;
  logo: string;
  created_at: any;
  updated_at: any;
}

export interface Location {
  id: number
  name: string
  address: string
  store_id: number
  deleted_at: any
  created_at: string
  updated_at: string
}

export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at: any;
  current_team_id: any;
  profile_photo_path: any;
  role: string;
  deleted_at: any;
  created_at: string;
  updated_at: string;
  store_id: any;
  location_id: any;
  invoice_header: any;
  invoice_footer: any;
  invoice_logo: any;
  headers: any;
  sold_headers: any;
  profile_photo_url: string;
}

export interface EmailTemplate {
  id: number;
  subject: string;
  content: string;
  user_id: number;
  created_at: string;
  updated_at: string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at: any;
  two_factor_confirmed_at: any;
  current_team_id: any;
  profile_photo_path: any;
  created_at: string;
  updated_at: string;
  store_id: number | any;
  location_id: any;
  invoice: any;
  column_headers: any;
  sold_headers: any;
  role: string;
  deleted_at: any;
  profile_photo_url: string;
  two_factor_enabled: boolean;
}

export interface CustomField {
  label: string;
  name: string;
  type: string;
}

export interface Field {
  id: number;
  created_at: string;
  updated_at: string;
  text: string;
  value: string;
  type: string;
  active: number;
  user_id: number;
}

export interface Storage {
  id: number;
  name: string;
  limit: number;
  created_at: string;
  updated_at: string;
  items: Item[];
}

export interface Dashboard {
  devicesInInventory: number;
  tradesThisMonth: number;
  soldThisMonth: number;
  costSoldThisMonth: number;
  costOfTaxedGoodsSold: number;
  inventoryValue: number;
  saleValue: number;
  soldValueThisMonth: number;
  profitThisMonth: number;
  startDate: string;
  endDate: string;
  cashOnHand: string;
  expensesThisMonth: number;
  accountsReceivableThisMonth: number;
  accountsPayableThisMonth: number;
  salesTaxCollected: number;
  salesTaxPaid: number;
  taxedSales: number;
  nonTaxedSales: number;
  totalPurchases: number;
}