/* eslint-disable */
// @ts-nocheck
/**
 * Placeholder GraphQL SDK.
 * Replace by running `npm run graphql:codegen` once GQL_HOST is live.
 */
export * from './schema'
import type { GraphQLClient, RequestOptions } from 'graphql-request'

type GraphQLClientRequestHeaders = RequestOptions['requestHeaders']

const notReady = async () => {
  throw new Error(
    'WooNuxt GraphQL SDK is not generated yet. Set GQL_HOST to a live WPGraphQL endpoint and run `npm run graphql:codegen`. SNF pages using snf/v1 REST still work.',
  )
}

export type SdkFunctionWrapper = <T>(
  action: (requestHeaders?: Record<string, string>) => Promise<T>,
  operationName: string,
  operationType?: string,
  variables?: any,
) => Promise<T>

export function getSdk(client: GraphQLClient, withWrapper: SdkFunctionWrapper = (action) => action()) {
  const run = (name: string) => (variables?: any, requestHeaders?: GraphQLClientRequestHeaders) =>
    withWrapper((wrappedHeaders) => notReady(), name, 'query', variables)

  return new Proxy(
    {},
    {
      get: (_t, prop: string) => run(prop),
    },
  ) as Sdk
}

export type Sdk = Record<string, (variables?: any, requestHeaders?: GraphQLClientRequestHeaders) => Promise<any>>
