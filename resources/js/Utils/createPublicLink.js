function slugify(text) {
    return encodeURIComponent(text
      .toString()
      .trim()
      .replace(/\s+/g, '_'))     // Replace spaces with -
}

const currentUrl = window.location.origin;

function createStoreUrl(companyName, shopName) {
    const storeSlug = slugify(companyName) + "/" + slugify(shopName);
    return `${currentUrl}/public/${storeSlug}`;
  }

export default createStoreUrl