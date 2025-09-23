// This file contains utility functions for formatting various types of data.

export function formatPercentage(value) {
  return Number.isInteger(value)
    ? `${value}%`
    : `${value.toFixed(2)}%`
}

export function formatDeviceModel(model) {
  if (!model || typeof model !== 'string') return model;
  
  let formatted = model.trim();
  
  // Define replacement patterns
  const replacements = [
    // iPhone corrections
    { pattern: /\biphone\b/gi, replacement: 'iPhone' },
    { pattern: /\bipad\b/gi, replacement: 'iPad' },
    { pattern: /\bipod\b/gi, replacement: 'iPod' },
    
    // Model suffixes
    { pattern: /\bxr\b/gi, replacement: 'XR' },
    { pattern: /\bxs\b/gi, replacement: 'XS' },
    { pattern: /\bse\b/gi, replacement: 'SE' },
    { pattern: /\bpro\b/gi, replacement: 'Pro' },
    { pattern: /\bmax\b/gi, replacement: 'Max' },
    { pattern: /\bmini\b/gi, replacement: 'Mini' },
    { pattern: /\bplus\b/gi, replacement: 'Plus' },
    { pattern: /\bair\b/gi, replacement: 'Air' },
    
    // Generation formatting
    { pattern: /\b(\d+)(rd|nd|st|th)\s*gen\b/gi, replacement: (match, num, suffix) => `${num}${suffix.toLowerCase()} Gen` },
    { pattern: /\bgen\s*(\d+)/gi, replacement: 'Gen $1' },
    
    // Samsung models
    { pattern: /\bgalaxy\b/gi, replacement: 'Galaxy' },
    { pattern: /\bsamsung\b/gi, replacement: 'Samsung' },
    
    // Google models
    { pattern: /\bpixel\b/gi, replacement: 'Pixel' },
    
    // Clean up multiple spaces
    { pattern: /\s+/g, replacement: ' ' }
  ];
  
  // Apply all replacements
  replacements.forEach(({ pattern, replacement }) => {
    formatted = formatted.replace(pattern, replacement);
  });
  
  return formatted.trim();
}