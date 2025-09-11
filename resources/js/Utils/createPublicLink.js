function slugify(text) {
    return encodeURIComponent(text
      .toString()
      .trim()
      .replace(/\s+/g, '_'))     // Replace spaces with _
}

const currentUrl = window.location.origin;

function createStoreUrl(shopNameOrSlug, shopSlug = null, shopId = null) {
    // If shopSlug is provided, use it
    if (shopSlug) {
        return `${currentUrl}/publicstore/${shopSlug}`;
    }
    
    // If no slug but have shopId, create fallback with name + id
    if (shopId) {
        const nameSlug = slugify(shopNameOrSlug);
        return `${currentUrl}/publicstore/${nameSlug}-${shopId}`;
    }
    
    // Final fallback: just use the name
    const nameSlug = slugify(shopNameOrSlug);
    return `${currentUrl}/publicstore/${nameSlug}`;
}

export default createStoreUrl