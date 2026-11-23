/* eslint-disable */
// @ts-nocheck
/**
 * Placeholder GraphQL schema types.
 * Replace by running `npm run graphql:codegen` once GQL_HOST is a live WPGraphQL endpoint
 * (install "Settings for WooNuxt" in WordPress).
 */
export type Maybe<T> = T | null
export type InputMaybe<T> = Maybe<T>
export type Exact<T extends { [key: string]: unknown }> = { [K in keyof T]: T[K] }
export type Scalars = {
  ID: { input: string; output: string }
  String: { input: string; output: string }
  Boolean: { input: boolean; output: boolean }
  Int: { input: number; output: number }
  Float: { input: number; output: number }
}

export enum StockStatusEnum {
  IN_STOCK = 'IN_STOCK',
  OUT_OF_STOCK = 'OUT_OF_STOCK',
  ON_BACKORDER = 'ON_BACKORDER',
}

export enum ProductTypesEnum {
  SIMPLE = 'SIMPLE',
  VARIABLE = 'VARIABLE',
  EXTERNAL = 'EXTERNAL',
  GROUPED = 'GROUPED',
}

export enum ProductsOrderByEnum {
  DATE = 'DATE',
  PRICE = 'PRICE',
  POPULARITY = 'POPULARITY',
  RATING = 'RATING',
  TITLE = 'TITLE',
  MENU_ORDER = 'MENU_ORDER',
}

export enum OrderStatusEnum {
  PENDING = 'PENDING',
  PROCESSING = 'PROCESSING',
  ON_HOLD = 'ON_HOLD',
  COMPLETED = 'COMPLETED',
  CANCELLED = 'CANCELLED',
  REFUNDED = 'REFUNDED',
  FAILED = 'FAILED',
}

export enum TaxonomyEnum {
  PRODUCTCATEGORY = 'PRODUCTCATEGORY',
  PRODUCTTAG = 'PRODUCTTAG',
  PA_COLOR = 'PA_COLOR',
  PA_SIZE = 'PA_SIZE',
}

export enum LoginProviderEnum {
  PASSWORD = 'PASSWORD',
}

export enum StripePaymentMethodEnum {
  CARD = 'CARD',
}

export type AddToCartInput = Record<string, unknown>
export type ProductAttributeInput = Record<string, unknown>
export type GetStripePaymentIntentQueryVariables = Record<string, unknown>
export type CheckoutMutation = Record<string, unknown>
export type GetCartQuery = Record<string, unknown>
export type GetCartSummaryQuery = Record<string, unknown>
