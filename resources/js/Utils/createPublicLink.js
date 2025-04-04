function slugify(text) {
    return text
      .toString()
      .toLowerCase()
      .trim()
      .replace(/\s+/g, '-')     // Replace spaces with -
      .replace(/[^\w-]+/g, '') // Remove all non-word chars (except -)
      .replace(/--+/g, '-')   // Replace multiple - with single -
      .replace(/^-+/, '')     // Trim - from start of text
      .replace(/-+$/, '');    // Trim - from end of text
}

const currentUrl = window.location.origin;

function createStoreUrl(storeName) {
    const storeSlug = slugify(storeName);
    return `${currentUrl}/public/${storeSlug}`;
  }

export default createStoreUrl