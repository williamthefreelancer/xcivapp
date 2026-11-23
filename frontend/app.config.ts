export default defineAppConfig({
  siteName: 'Super Ninety Four',
  shortDescription: 'Member club, events, and marketplace',
  description:
    'Super Ninety Four — club, commerce, and community on a WooNuxt + WordPress stack.',
  storeSettings: {
    autoOpenCart: false,
    cartMode: 'optimistic',
    showReviews: true,
    showFilters: true,
    showOrderByDropdown: true,
    showSKU: true,
    showRelatedProducts: true,
    showProductCategoriesOnSingleProduct: true,
    showBreadcrumbOnSingleProduct: true,
    showMoveToWishlist: false,
    hideBillingAddressForVirtualProducts: false,
    initStoreOnUserActionToReduceServerLoad: true,
    productGalleryThumbnailsPosition: 'bottom',
    saleBadge: 'percent',
    socialLoginsDisplay: 'buttons',
  },
})
