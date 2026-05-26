/**
 * Format a date string for display in tables.
 * Extracts the date portion from ISO strings (with T) or space-separated strings.
 */
export function formatDate(value: string | null | undefined): string {
  if (!value) return '';
  // If the value is ISO format (contains 'T'), split by 'T' and take the first part
  if (value.includes('T')) {
    return value.split('T')[0];
  }
  // If the value has a space, split by space and take the first part
  return value.split(' ')[0];
}